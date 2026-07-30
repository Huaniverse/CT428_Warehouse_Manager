<?php
// Xử lý nhập kho (đơn lẻ và hàng loạt)

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/partials/helpers-stock.php';

header('Content-Type: application/json; charset=utf-8');

requireDb($conn);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$viewActions = ['list', 'detail'];
if (in_array($action, $viewActions, true)) {
    if (!canViewProducts()) deny403();
} else {
    requireCanImportExport();
}

switch ($action) {

    // Tạo phiếu nhập đơn lẻ
    case 'create':
        requirePost();
        verifyCsrfToken();

        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity   = (int)($_POST['quantity'] ?? 0);
        $notes      = trim($_POST['notes'] ?? '');
        $created_by = $_SESSION['user_id'];

        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn sản phẩm.']);
            exit;
        }
        if ($quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Số lượng nhập phải lớn hơn 0.']);
            exit;
        }
        if (mb_strlen($notes) > 1000) {
            echo json_encode(['success' => false, 'message' => 'Ghi chú không được quá 1000 ký tự.']);
            exit;
        }

        $conn->begin_transaction();
        try {
            $lock = $conn->prepare("SELECT id, name FROM products WHERE id = ? AND is_active = 1 FOR UPDATE");
            $lock->bind_param("i", $product_id);
            $lock->execute();
            $locked_product = $lock->get_result()->fetch_assoc();
            $lock->close();

            if (!$locked_product) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại hoặc đã bị ẩn.']);
                exit;
            }

            $receipt_id = 'PN_' . date('YmdHis') . '_' . random_int(1000, 9999);

            $header = $conn->prepare("INSERT INTO import_receipts (id, created_by, created_at) VALUES (?, ?, NOW())");
            $header->bind_param("si", $receipt_id, $created_by);
            $header->execute();
            $header->close();

            $detail = $conn->prepare("INSERT INTO import_receipt_details (receipt_id, product_id, quantity, notes) VALUES (?, ?, ?, ?)");
            $detail->bind_param("siis", $receipt_id, $product_id, $quantity, $notes);
            $detail->execute();
            $detail->close();

            $update = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
            $update->bind_param("ii", $quantity, $product_id);
            $update->execute();
            $update->close();

            $fetch = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $fetch->bind_param("i", $product_id);
            $fetch->execute();
            $new_qty = (int)$fetch->get_result()->fetch_assoc()['stock_quantity'];
            $fetch->close();

            $conn->commit();
            echo json_encode([
                'success'       => true,
                'message'       => "Nhập kho thành công. Sản phẩm \"{$locked_product['name']}\": +{$quantity} → {$new_qty} sản phẩm.",
                'receipt_id'    => $receipt_id,
                'new_quantity'  => $new_qty
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Lỗi nhập kho: ' . $e->getMessage()]);
        }
        break;

    // Tạo phiếu nhập hàng loạt
    case 'create_batch':
        requirePost();
        verifyCsrfToken();

        $items_json = $_POST['items'] ?? '[]';
        $items = json_decode($items_json, true);

        if (!is_array($items) || count($items) === 0) {
            echo json_encode(['success' => false, 'message' => 'Không có sản phẩm nào trong phiếu.']);
            exit;
        }
        if (count($items) > 50) {
            echo json_encode(['success' => false, 'message' => 'Tối đa 50 sản phẩm mỗi phiếu.']);
            exit;
        }

        $created_by = $_SESSION['user_id'];
        $conn->begin_transaction();
        try {
            $receipt_id = 'PN_' . date('YmdHis') . '_' . random_int(1000, 9999);

            $header = $conn->prepare("INSERT INTO import_receipts (id, created_by, created_at) VALUES (?, ?, NOW())");
            $header->bind_param("si", $receipt_id, $created_by);
            $header->execute();
            $header->close();

            $detail_stmt = $conn->prepare("INSERT INTO import_receipt_details (receipt_id, product_id, quantity, notes) VALUES (?, ?, ?, ?)");
            $lock = $conn->prepare("SELECT id, name FROM products WHERE id = ? AND is_active = 1 FOR UPDATE");
            $update = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");

            $success_count = 0;
            $messages = [];

            foreach ($items as $item) {
                $product_id = (int)($item['product_id'] ?? 0);
                $quantity   = (int)($item['quantity'] ?? 0);
                $notes      = trim($item['notes'] ?? '');

                if ($product_id <= 0 || $quantity <= 0) continue;

                $lock->bind_param("i", $product_id);
                $lock->execute();
                $locked_product = $lock->get_result()->fetch_assoc();

                if (!$locked_product) continue;

                $detail_stmt->bind_param("siis", $receipt_id, $product_id, $quantity, $notes);
                $detail_stmt->execute();

                $update->bind_param("ii", $quantity, $product_id);
                $update->execute();

                $success_count++;
                $messages[] = "{$locked_product['name']}: +{$quantity}";
            }

            $detail_stmt->close();
            $lock->close();
            $update->close();

            if ($success_count === 0) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Không có sản phẩm hợp lệ nào được nhập.']);
                exit;
            }

            $conn->commit();
            $summary = implode(', ', $messages);
            echo json_encode([
                'success'    => true,
                'message'    => "Nhập kho thành công {$success_count} sản phẩm: {$summary}.",
                'receipt_id' => $receipt_id
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Lỗi nhập kho: ' . $e->getMessage()]);
        }
        break;

    // Danh sách phiếu nhập
    case 'list':
        echo json_encode(getStockList($conn, 'import'));
        break;

    // Chi tiết phiếu nhập
    case 'detail':
        $receipt_id = trim($_GET['receipt_id'] ?? '');
        if ($receipt_id === '') {
            echo json_encode(['success' => false, 'message' => 'Thiếu mã phiếu.']);
            exit;
        }
        echo json_encode(getStockDetail($conn, 'import', $receipt_id));
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}

$conn->close();

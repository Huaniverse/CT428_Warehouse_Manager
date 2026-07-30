<?php
// Xử lý xuất kho (đơn lẻ và hàng loạt)

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

    // Tạo phiếu xuất đơn lẻ
    case 'create':
        requirePost();
        verifyCsrfToken();

        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $note  = trim($_POST['note'] ?? '');
        $created_by = $_SESSION['user_id'];

        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn sản phẩm.']);
            exit;
        }
        if ($quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Số lượng xuất phải lớn hơn 0.']);
            exit;
        }
        if (mb_strlen($note) > 1000) {
            echo json_encode(['success' => false, 'message' => 'Ghi chú không được quá 1000 ký tự.']);
            exit;
        }

        $conn->begin_transaction();
        try {
            $lock = $conn->prepare("SELECT id, name, stock FROM sanpham WHERE id = ? AND is_active = 1 FOR UPDATE");
            $lock->bind_param("i", $product_id);
            $lock->execute();
            $locked_product = $lock->get_result()->fetch_assoc();
            $lock->close();

            if (!$locked_product) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại hoặc đã bị ẩn.']);
                exit;
            }

            if ($locked_product['stock'] < $quantity) {
                $conn->rollback();
                echo json_encode([
                    'success' => false,
                    'message' => "Không đủ hàng để xuất. Hiện còn {$locked_product['stock']} sản phẩm."
                ]);
                exit;
            }

            $code = 'PX_' . date('YmdHis') . '_' . random_int(1000, 9999);

            $header = $conn->prepare("INSERT INTO phieu_xuat (code, created_by, created_at) VALUES (?, ?, NOW())");
            $header->bind_param("si", $code, $created_by);
            $header->execute();
            $header->close();

            $detail = $conn->prepare("INSERT INTO chi_tiet_phieu_xuat (receipt_code, product_id, quantity, note) VALUES (?, ?, ?, ?)");
            $detail->bind_param("siis", $code, $product_id, $quantity, $note);
            $detail->execute();
            $detail->close();

            $update = $conn->prepare("UPDATE sanpham SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $update->bind_param("iii", $quantity, $product_id, $quantity);
            $update->execute();

            if ($update->affected_rows === 0) {
                $update->close();
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Không đủ hàng để xuất (đã có thay đổi tồn kho đồng thời).']);
                exit;
            }
            $update->close();

            $fetch = $conn->prepare("SELECT stock FROM sanpham WHERE id = ?");
            $fetch->bind_param("i", $product_id);
            $fetch->execute();
            $new_stock = (int)$fetch->get_result()->fetch_assoc()['stock'];
            $fetch->close();

            $conn->commit();
            echo json_encode([
                'success'       => true,
                'message'       => "Xuất kho thành công. Sản phẩm \"{$locked_product['name']}\": -{$quantity} → {$new_stock} sản phẩm.",
                'code'          => $code,
                'new_stock'     => $new_stock
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Lỗi xuất kho: ' . $e->getMessage()]);
        }
        break;

    // Tạo phiếu xuất hàng loạt
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
            $code = 'PX_' . date('YmdHis') . '_' . random_int(1000, 9999);

            $header = $conn->prepare("INSERT INTO phieu_xuat (code, created_by, created_at) VALUES (?, ?, NOW())");
            $header->bind_param("si", $code, $created_by);
            $header->execute();
            $header->close();

            $detail_stmt = $conn->prepare("INSERT INTO chi_tiet_phieu_xuat (receipt_code, product_id, quantity, note) VALUES (?, ?, ?, ?)");
            $lock = $conn->prepare("SELECT id, name, stock FROM sanpham WHERE id = ? AND is_active = 1 FOR UPDATE");
            $update = $conn->prepare("UPDATE sanpham SET stock = stock - ? WHERE id = ? AND stock >= ?");

            $success_count = 0;
            $messages = [];

            foreach ($items as $item) {
                $product_id = (int)($item['product_id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 0);
                $note  = trim($item['note'] ?? '');

                if ($product_id <= 0 || $quantity <= 0) continue;

                $lock->bind_param("i", $product_id);
                $lock->execute();
                $locked_product = $lock->get_result()->fetch_assoc();

                if (!$locked_product) {
                    throw new Exception("Sản phẩm ID {$product_id} không tồn tại.");
                }
                if ($locked_product['stock'] < $quantity) {
                    throw new Exception("Sản phẩm \"{$locked_product['name']}\" không đủ số lượng (tồn kho: {$locked_product['stock']}, yêu cầu: {$quantity}).");
                }

                $detail_stmt->bind_param("siis", $code, $product_id, $quantity, $note);
                $detail_stmt->execute();

                $update->bind_param("iii", $quantity, $product_id, $quantity);
                $update->execute();

                if ($update->affected_rows === 0) {
                    throw new Exception("Lỗi đồng bộ khi trừ tồn kho cho sản phẩm \"{$locked_product['name']}\".");
                }

                $success_count++;
                $messages[] = "{$locked_product['name']}: -{$quantity}";
            }

            $detail_stmt->close();
            $lock->close();
            $update->close();

            if ($success_count === 0) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Không có sản phẩm hợp lệ nào được xuất.']);
                exit;
            }

            $conn->commit();
            $summary = implode(', ', $messages);
            echo json_encode([
                'success'  => true,
                'message'  => "Xuất kho thành công {$success_count} sản phẩm: {$summary}.",
                'code' => $code
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Lỗi xuất kho: ' . $e->getMessage()]);
        }
        break;

    // Danh sách phiếu xuất
    case 'list':
        echo json_encode(getStockList($conn, 'export'));
        break;

    // Chi tiết phiếu xuất
    case 'detail':
        $code = trim($_GET['code'] ?? '');
        if ($code === '') {
            echo json_encode(['success' => false, 'message' => 'Thiếu mã phiếu.']);
            exit;
        }
        echo json_encode(getStockDetail($conn, 'export', $code));
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}

$conn->close();

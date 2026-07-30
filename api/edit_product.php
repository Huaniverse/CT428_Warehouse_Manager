<?php
// Lấy, cập nhật, ẩn/hiện sản phẩm

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/partials/helpers-products.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Chỉ admin/manager mới được update hoặc toggle hoặc delete
$isEditAction = in_array($action, ['update', 'toggle_active', 'delete'], true);
if ($isEditAction) {
    requireAdminOrManager();
} elseif (!canViewProducts()) {
    deny403();
}

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

switch ($action) {

    // Lấy thông tin sản phẩm
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.']);
            exit;
        }

        $stmt = $conn->prepare(
            "SELECT id, name, description, price, stock_quantity, category_id, is_active FROM products WHERE id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
            exit;
        }
        echo json_encode([
            'success' => true,
            'product' => $product,
            'can_edit' => isAdmin() || isManager(),
        ]);
        break;

    // Cập nhật thông tin sản phẩm
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
            exit;
        }
        // [SEC-01] Xác minh CSRF token
        verifyCsrfToken();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.']);
            exit;
        }

        // Nhận và làm sạch dữ liệu đầu vào, dùng helper để DRY
        $validation = validateProductInput($conn, $_POST, true);

        if (!$validation['success']) {
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            exit;
        }

        $data = $validation['data'];
        $name        = $data['name'];
        $category_id = $data['category_id'];
        $description = $data['description'];
        $price       = $data['price'];

        // Kiểm tra sản phẩm tồn tại
        $check_product = $conn->prepare("SELECT id FROM products WHERE id = ?");
        $check_product->bind_param("i", $id);
        $check_product->execute();
        if ($check_product->get_result()->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
            $check_product->close();
            exit;
        }
        $check_product->close();

        $stmt = $conn->prepare(
            "UPDATE products SET name = ?, description = ?, price = ?, category_id = ? WHERE id = ?"
        );
        $stmt->bind_param("ssdsi", $name, $description, $price, $category_id, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật sản phẩm thành công.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    // Ẩn hoặc hiện sản phẩm (soft delete / restore)
    case 'toggle_active':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
            exit;
        }
        // [SEC-01] Xác minh CSRF token
        verifyCsrfToken();

        $id        = (int)($_POST['id'] ?? 0);
        $new_state = (int)($_POST['is_active'] ?? 0) ? 1 : 0;

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE products SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_state, $id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $label = $new_state ? 'khôi phục' : 'ẩn';
            echo json_encode(['success' => true, 'message' => "Đã $label sản phẩm."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm hoặc không có thay đổi.']);
        }
        $stmt->close();
        break;

    // Chi tiết sản phẩm và lịch sử nhập xuất
    case 'detail':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.']);
            exit;
        }

        $stmt = $conn->prepare(
            "SELECT s.id, s.name, s.description, s.price, s.stock_quantity, s.category_id, s.is_active,
                    d.name AS category_name
             FROM products s
             JOIN categories d ON s.category_id = d.id
             WHERE s.id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
            exit;
        }

        $history_limit = 20;

        // Lịch sử nhập kho
        $import_sql = "SELECT ir.id as receipt_id, ct.quantity, sp.price AS unit_price,
                              (ct.quantity * sp.price) AS total_price,
                              u.full_name AS created_by_name, ir.created_at
                       FROM import_receipts ir
                       JOIN import_receipt_details ct ON ct.receipt_id = ir.id
                       JOIN products sp ON ct.product_id = sp.id
                       JOIN users u ON ir.created_by = u.id
                       WHERE ct.product_id = ?
                       ORDER BY ir.created_at DESC
                       LIMIT $history_limit";
        $import_stmt = $conn->prepare($import_sql);
        $import_stmt->bind_param("i", $id);
        $import_stmt->execute();
        $import_result = $import_stmt->get_result();
        $import_history = [];
        while ($row = $import_result->fetch_assoc()) {
            $import_history[] = $row;
        }
        $import_stmt->close();

        // Lịch sử xuất kho
        $export_sql = "SELECT er.id as receipt_id, ct.quantity, sp.price AS unit_price,
                              (ct.quantity * sp.price) AS total_price,
                              u.full_name AS created_by_name, er.created_at
                       FROM export_receipts er
                       JOIN export_receipt_details ct ON ct.receipt_id = er.id
                       JOIN products sp ON ct.product_id = sp.id
                       JOIN users u ON er.created_by = u.id
                       WHERE ct.product_id = ?
                       ORDER BY er.created_at DESC
                       LIMIT $history_limit";
        $export_stmt = $conn->prepare($export_sql);
        $export_stmt->bind_param("i", $id);
        $export_stmt->execute();
        $export_result = $export_stmt->get_result();
        $export_history = [];
        while ($row = $export_result->fetch_assoc()) {
            $export_history[] = $row;
        }
        $export_stmt->close();

        echo json_encode([
            'success'        => true,
            'product'        => $product,
            'import_history' => $import_history,
            'export_history' => $export_history,
            'can_edit'       => isAdmin() || isManager(),
        ]);
        break;

    // Xóa sản phẩm vĩnh viễn
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
            exit;
        }
        verifyCsrfToken();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.']);
            exit;
        }

        $conn->begin_transaction();
        $stmt = $conn->prepare("DELETE FROM import_receipt_details WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM export_receipt_details WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm.']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
        }
        $stmt->close();
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}

$conn->close();

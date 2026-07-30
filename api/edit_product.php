<?php
// Lấy, cập nhật, ẩn/hiện sản phẩm

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/partials/helpers-products.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Chỉ admin/manager mới được update hoặc toggle
$isEditAction = in_array($action, ['update', 'toggle_active'], true);
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
        $product_id = (int)($_GET['id'] ?? 0);
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.']);
            exit;
        }

        $stmt = $conn->prepare(
            "SELECT id, name, description, price, stock, category_code, is_active FROM sanpham WHERE id = ?"
        );

        $stmt->bind_param("i", $product_id);
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

        $product_id = (int)($_POST['id'] ?? 0);
        if ($product_id <= 0) {
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
        $name          = $data['name'];
        $category_code = $data['category_code'];
        $description   = $data['description'];
        $price         = $data['price'];

        // Kiểm tra sản phẩm tồn tại
        $check_sp = $conn->prepare("SELECT id FROM sanpham WHERE id = ?");
        $check_sp->bind_param("i", $product_id);
        $check_sp->execute();
        if ($check_sp->get_result()->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
            $check_sp->close();
            exit;
        }
        $check_sp->close();

        $stmt = $conn->prepare(
            "UPDATE sanpham SET name = ?, description = ?, price = ?, category_code = ? WHERE id = ?"
        );
        $stmt->bind_param("ssdsi", $name, $description, $price, $category_code, $product_id);

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

        $product_id = (int)($_POST['id'] ?? 0);
        $new_state = (int)($_POST['is_active'] ?? 0) ? 1 : 0;

        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE sanpham SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_state, $product_id);

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
        $product_id = (int)($_GET['id'] ?? 0);
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.']);
            exit;
        }

        $stmt = $conn->prepare(
            "SELECT s.id, s.name, s.description, s.price, s.stock, s.category_code, s.is_active,
                    d.name AS category_name
             FROM sanpham s
             JOIN danhmuc d ON s.category_code = d.code
             WHERE s.id = ?"
        );
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
            exit;
        }

        $history_limit = 20;

        // Lịch sử nhập kho
        $import_sql = "SELECT pn.code, ct.quantity, sp.price AS unit_price,
                              (ct.quantity * sp.price) AS total_amount,
                              u.full_name AS created_by_name, pn.created_at
                       FROM phieu_nhap pn
                       JOIN chi_tiet_phieu_nhap ct ON ct.receipt_code = pn.code
                       JOIN sanpham sp ON ct.product_id = sp.id
                       JOIN users u ON pn.created_by = u.id
                       WHERE ct.product_id = ?
                       ORDER BY pn.created_at DESC
                       LIMIT $history_limit";
        $import_stmt = $conn->prepare($import_sql);
        $import_stmt->bind_param("i", $product_id);
        $import_stmt->execute();
        $import_result = $import_stmt->get_result();
        $import_history = [];
        while ($row = $import_result->fetch_assoc()) {
            $import_history[] = $row;
        }
        $import_stmt->close();

        // Lịch sử xuất kho
        $export_sql = "SELECT px.code, ct.quantity, sp.price AS unit_price,
                              (ct.quantity * sp.price) AS total_amount,
                              u.full_name AS created_by_name, px.created_at
                       FROM phieu_xuat px
                       JOIN chi_tiet_phieu_xuat ct ON ct.receipt_code = px.code
                       JOIN sanpham sp ON ct.product_id = sp.id
                       JOIN users u ON px.created_by = u.id
                       WHERE ct.product_id = ?
                       ORDER BY px.created_at DESC
                       LIMIT $history_limit";
        $export_stmt = $conn->prepare($export_sql);
        $export_stmt->bind_param("i", $product_id);
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

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}

$conn->close();

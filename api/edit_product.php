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
        $ma_sp = (int)($_GET['id'] ?? 0);
        if ($ma_sp <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.']);
            exit;
        }

        $stmt = $conn->prepare(
            "SELECT MaSP, TenSP, MoTa, Gia, SoLuong, DanhMuc, is_active FROM sanpham WHERE MaSP = ?"
        );

        $stmt->bind_param("i", $ma_sp);
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

        $ma_sp = (int)($_POST['ma_sp'] ?? 0);
        if ($ma_sp <= 0) {
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
        $ten_sp   = $data['ten_sp'];
        $danhmuc  = $data['danhmuc'];
        $mota     = $data['mota'];
        $gia      = $data['gia'];

        // Kiểm tra sản phẩm tồn tại
        $check_sp = $conn->prepare("SELECT MaSP FROM sanpham WHERE MaSP = ?");
        $check_sp->bind_param("i", $ma_sp);
        $check_sp->execute();
        if ($check_sp->get_result()->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
            $check_sp->close();
            exit;
        }
        $check_sp->close();

        $stmt = $conn->prepare(
            "UPDATE sanpham SET TenSP = ?, MoTa = ?, Gia = ?, DanhMuc = ? WHERE MaSP = ?"
        );
        $stmt->bind_param("ssdsi", $ten_sp, $mota, $gia, $danhmuc, $ma_sp);

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

        $ma_sp     = (int)($_POST['ma_sp'] ?? 0);
        $new_state = (int)($_POST['is_active'] ?? 0) ? 1 : 0;

        if ($ma_sp <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE sanpham SET is_active = ? WHERE MaSP = ?");
        $stmt->bind_param("ii", $new_state, $ma_sp);

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
        $ma_sp = (int)($_GET['id'] ?? 0);
        if ($ma_sp <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.']);
            exit;
        }

        $stmt = $conn->prepare(
            "SELECT s.MaSP, s.TenSP, s.MoTa, s.Gia, s.SoLuong, s.DanhMuc, s.is_active,
                    d.TenDM
             FROM sanpham s
             JOIN danhmuc d ON s.DanhMuc = d.MaDM
             WHERE s.MaSP = ?"
        );
        $stmt->bind_param("i", $ma_sp);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
            exit;
        }

        $history_limit = 20;

        // Lịch sử nhập kho
        $import_sql = "SELECT pn.ma_phieu, ct.so_luong, sp.Gia AS don_gia,
                              (ct.so_luong * sp.Gia) AS thanh_tien,
                              u.full_name AS nguoi_tao_name, pn.ngay_tao
                       FROM phieu_nhap pn
                       JOIN chi_tiet_phieu_nhap ct ON ct.ma_phieu = pn.ma_phieu
                       JOIN sanpham sp ON ct.san_pham = sp.MaSP
                       JOIN users u ON pn.nguoi_tao = u.id
                       WHERE ct.san_pham = ?
                       ORDER BY pn.ngay_tao DESC
                       LIMIT $history_limit";
        $import_stmt = $conn->prepare($import_sql);
        $import_stmt->bind_param("i", $ma_sp);
        $import_stmt->execute();
        $import_result = $import_stmt->get_result();
        $import_history = [];
        while ($row = $import_result->fetch_assoc()) {
            $import_history[] = $row;
        }
        $import_stmt->close();

        // Lịch sử xuất kho
        $export_sql = "SELECT px.ma_phieu, ct.so_luong, sp.Gia AS don_gia,
                              (ct.so_luong * sp.Gia) AS thanh_tien,
                              u.full_name AS nguoi_tao_name, px.ngay_tao
                       FROM phieu_xuat px
                       JOIN chi_tiet_phieu_xuat ct ON ct.ma_phieu = px.ma_phieu
                       JOIN sanpham sp ON ct.san_pham = sp.MaSP
                       JOIN users u ON px.nguoi_tao = u.id
                       WHERE ct.san_pham = ?
                       ORDER BY px.ngay_tao DESC
                       LIMIT $history_limit";
        $export_stmt = $conn->prepare($export_sql);
        $export_stmt->bind_param("i", $ma_sp);
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

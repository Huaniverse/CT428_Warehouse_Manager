<?php
// admin/edit_product.php — API sửa sản phẩm / soft delete / restore (Admin only)

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/helpers.php'; // [FIX-07] Dùng helper validation
requireAdmin();

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

switch ($action) {

    // ── Lấy thông tin sản phẩm ─────────────────────────────────────────────
    case 'get':
        $ma_sp = (int)($_GET['id'] ?? 0);
        if ($ma_sp <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.']);
            exit;
        }

        $stmt = $conn->prepare(
            // [FIX-05] Chỉ lấy sản phẩm đang active — ngăn edit sản phẩm đã ẩn
            "SELECT MaSP, TenSP, MoTa, Gia, SoLuong, DanhMuc, is_active FROM sanpham WHERE MaSP = ? AND is_active = 1"
        );

        $stmt->bind_param("i", $ma_sp);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
            exit;
        }
        echo json_encode(['success' => true, 'product' => $product]);
        break;

    // ── Cập nhật thông tin sản phẩm ───────────────────────────────────────
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

    // ── Soft delete / Restore sản phẩm ────────────────────────────────────
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

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}

$conn->close();

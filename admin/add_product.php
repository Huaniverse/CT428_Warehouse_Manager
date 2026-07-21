<?php
// admin/add_product.php — API thêm sản phẩm mới (chỉ dành cho Admin)
// Response trả về dạng JSON

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/helpers.php'; // [FIX-07] Dùng helper validation
requireAdmin(); // Yêu cầu quyền Admin

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit;
}

// [SEC-01] Xác minh CSRF token — chống CSRF attack
verifyCsrfToken();


if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

// Nhận và làm sạch dữ liệu đầu vào, dùng helper để DRY
$validation = validateProductInput($conn, $_POST, false);

if (!$validation['success']) {
    echo json_encode(['success' => false, 'message' => $validation['message']]);
    exit;
}

$data = $validation['data'];
$ten_sp   = $data['ten_sp'];
$danhmuc  = $data['danhmuc'];
$mota     = $data['mota'];
$gia      = $data['gia'];
$so_luong = $data['so_luong'];

// Thực hiện thêm sản phẩm mới
$stmt = $conn->prepare(
    "INSERT INTO sanpham (TenSP, DanhMuc, MoTa, Gia, SoLuong) VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssdi", $ten_sp, $danhmuc, $mota, $gia, $so_luong);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Thêm sản phẩm mới thành công.',
        'ma_sp'   => $conn->insert_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi lưu sản phẩm vào cơ sở dữ liệu: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();

<?php
// admin/add_product.php — API thêm sản phẩm mới (chỉ dành cho Admin)
// Response trả về dạng JSON

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
requireAdmin(); // Yêu cầu quyền Admin

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit;
}

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

// Nhận và làm sạch dữ liệu đầu vào
$ten_sp    = trim($_POST['ten_sp'] ?? '');
$danhmuc    = trim($_POST['danhmuc'] ?? '');
$mota      = trim($_POST['mota'] ?? '');
$gia       = isset($_POST['gia']) ? (double)$_POST['gia'] : 0.0;
$so_luong  = isset($_POST['so_luong']) ? (int)$_POST['so_luong'] : 0;

// Kiểm tra dữ liệu đầu vào
if ($ten_sp === '') {
    echo json_encode(['success' => false, 'message' => 'Tên sản phẩm không được để trống.']);
    exit;
}

if ($danhmuc === '') {
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn danh mục sản phẩm.']);
    exit;
}

if ($gia < 0) {
    echo json_encode(['success' => false, 'message' => 'Giá bán phải lớn hơn hoặc bằng 0.']);
    exit;
}

if ($so_luong < 0) {
    echo json_encode(['success' => false, 'message' => 'Số lượng tồn kho phải lớn hơn hoặc bằng 0.']);
    exit;
}

// Kiểm tra danh mục tồn tại trong CSDL
$check_dm = $conn->prepare("SELECT MaDM FROM danhmuc WHERE MaDM = ?");
$check_dm->bind_param("s", $danhmuc);
$check_dm->execute();
$res_dm = $check_dm->get_result();
if ($res_dm->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Danh mục sản phẩm không tồn tại.']);
    $check_dm->close();
    exit;
}
$check_dm->close();

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

<?php
// admin/add_category.php — API thêm danh mục mới (chỉ dành cho Admin)
// Response trả về dạng JSON chứa danh sách danh mục đã cập nhật

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
$ma_dm  = strtoupper(trim($_POST['ma_dm'] ?? ''));
$ten_dm = trim($_POST['ten_dm'] ?? '');

// Kiểm tra dữ liệu đầu vào
if ($ma_dm === '') {
    echo json_encode(['success' => false, 'message' => 'Mã danh mục không được để trống.']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]{2,10}$/', $ma_dm)) {
    echo json_encode(['success' => false, 'message' => 'Mã danh mục chỉ gồm chữ cái, số, gạch dưới (2–10 ký tự).']);
    exit;
}

if ($ten_dm === '') {
    echo json_encode(['success' => false, 'message' => 'Tên danh mục không được để trống.']);
    exit;
}

// Kiểm tra trùng lặp mã danh mục
$check = $conn->prepare("SELECT MaDM FROM danhmuc WHERE MaDM = ?");
$check->bind_param("s", $ma_dm);
$check->execute();
$res = $check->get_result();
if ($res->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Mã danh mục này đã tồn tại. Vui lòng chọn mã khác.']);
    $check->close();
    exit;
}
$check->close();

// Thực hiện thêm danh mục mới
$stmt = $conn->prepare("INSERT INTO danhmuc (MaDM, TenDM) VALUES (?, ?)");
$stmt->bind_param("ss", $ma_dm, $ten_dm);

if ($stmt->execute()) {
    // Lấy lại toàn bộ danh sách danh mục để cập nhật giao diện
    $categories = [];
    $result = $conn->query("SELECT MaDM, TenDM FROM danhmuc ORDER BY TenDM ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }

    echo json_encode([
        'success'    => true,
        'message'    => 'Thêm danh mục mới thành công.',
        'categories' => $categories
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi lưu danh mục vào cơ sở dữ liệu: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();

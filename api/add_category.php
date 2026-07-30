<?php
// Thêm danh mục sản phẩm

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/auth.php';
requireAdminOrManager();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit;
}

verifyCsrfToken();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

// Nhận và làm sạch dữ liệu đầu vào
$code  = strtoupper(trim($_POST['code'] ?? ''));
$category_name = trim($_POST['name'] ?? '');

// Kiểm tra dữ liệu đầu vào
if ($code === '') {
    echo json_encode(['success' => false, 'message' => 'Mã danh mục không được để trống.']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]{2,10}$/', $code)) {
    echo json_encode(['success' => false, 'message' => 'Mã danh mục chỉ gồm chữ cái, số, gạch dưới (2–10 ký tự).']);
    exit;
}

if ($category_name === '') {
    echo json_encode(['success' => false, 'message' => 'Tên danh mục không được để trống.']);
    exit;
}

// Kiểm tra trùng lặp mã danh mục
$check = $conn->prepare("SELECT code FROM danhmuc WHERE code = ?");
$check->bind_param("s", $code);
$check->execute();
$res = $check->get_result();
if ($res->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Mã danh mục này đã tồn tại. Vui lòng chọn mã khác.']);
    $check->close();
    exit;
}
$check->close();

// Thực hiện thêm danh mục mới
$stmt = $conn->prepare("INSERT INTO danhmuc (code, name) VALUES (?, ?)");
$stmt->bind_param("ss", $code, $category_name);

if ($stmt->execute()) {
    // Lấy danh sách danh mục để cập nhật giao diện
    $categories = [];
    $result = $conn->query("SELECT code, name FROM danhmuc ORDER BY name ASC");
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

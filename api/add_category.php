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

// Nhận và làm sạch dữ liệu
$id   = trim($_POST['id'] ?? '');
$name = trim($_POST['name'] ?? '');

if ($id === '') {
    echo json_encode(['success' => false, 'message' => 'Mã danh mục không được để trống.']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]{2,10}$/', $id)) {
    echo json_encode(['success' => false, 'message' => 'Mã danh mục chỉ gồm chữ cái, số, gạch dưới (2-10 ký tự).']);
    exit;
}

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Tên danh mục không được để trống.']);
    exit;
}

// Kiểm tra mã danh mục đã tồn tại chưa
$check = $conn->prepare("SELECT id FROM categories WHERE id = ?");
$check->bind_param("s", $id);
$check->execute();
$res = $check->get_result();
if ($res->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Mã danh mục này đã tồn tại. Vui lòng chọn mã khác.']);
    $check->close();
    exit;
}
$check->close();

// Thêm danh mục mới
$stmt = $conn->prepare("INSERT INTO categories (id, name) VALUES (?, ?)");
$stmt->bind_param("ss", $id, $name);

if ($stmt->execute()) {
    // Lấy danh sách danh mục để cập nhật giao diện
    $categories = [];
    $result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
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

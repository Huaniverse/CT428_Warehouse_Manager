<?php

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/partials/helpers-products.php';
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
$name          = $data['name'];
$category_code = $data['category_code'];
$description   = $data['description'];
$price         = $data['price'];
$quantity      = $data['quantity'];

// Thực hiện thêm sản phẩm mới
$stmt = $conn->prepare(
    "INSERT INTO sanpham (name, category_code, description, price, stock) VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssdi", $name, $category_code, $description, $price, $quantity);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Thêm sản phẩm mới thành công.',
        'id'   => $conn->insert_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi lưu sản phẩm vào cơ sở dữ liệu: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();

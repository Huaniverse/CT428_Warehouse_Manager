<?php
// Thêm sản phẩm mới

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/partials/helpers-products.php';
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

// Dùng helper để kiểm tra dữ liệu
$validation = validateProductInput($conn, $_POST, false);

if (!$validation['success']) {
    echo json_encode(['success' => false, 'message' => $validation['message']]);
    exit;
}

$data = $validation['data'];
$name        = $data['name'];
$category_id = $data['category_id'];
$description = $data['description'];
$price       = $data['price'];
$stock_quantity = $data['stock_quantity'];

$stmt = $conn->prepare(
    "INSERT INTO products (name, category_id, description, price, stock_quantity) VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssdi", $name, $category_id, $description, $price, $stock_quantity);

if ($stmt->execute()) {
    $product_id = $conn->insert_id;

    // Tự động tạo phiếu nhập kho cho sản phẩm mới
    if ($stock_quantity > 0) {
        $receipt_id = 'PN_AUTO_' . date('YmdHis') . '_' . $product_id;
        $created_by = $_SESSION['user_id'] ?? null;
        if ($created_by) {
            $import_stmt = $conn->prepare("INSERT INTO import_receipts (id, created_by, notes) VALUES (?, ?, ?)");
            $notes = 'Tự động tạo khi thêm sản phẩm mới';
            $import_stmt->bind_param("sis", $receipt_id, $created_by, $notes);
            $import_stmt->execute();
            $import_stmt->close();

            $detail_stmt = $conn->prepare("INSERT INTO import_receipt_details (receipt_id, product_id, quantity, notes) VALUES (?, ?, ?, ?)");
            $detail_notes = 'Nhập kho ban đầu';
            $detail_stmt->bind_param("siis", $receipt_id, $product_id, $stock_quantity, $detail_notes);
            $detail_stmt->execute();
            $detail_stmt->close();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Thêm sản phẩm mới thành công.',
        'id'      => $product_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi lưu sản phẩm vào cơ sở dữ liệu: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();

<?php
// db.php — Kết nối cơ sở dữ liệu dùng chung
// Được require bởi: index.php, auth.php, login.php, filter_products.php, admin/users.php
// [IMP-04] Credentials được đọc từ config.php — không hardcode trực tiếp ở đây

require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log("DB Connection failed: " . $conn->connect_error);
    $conn = null;
} else {
    $conn->set_charset("utf8mb4");
}

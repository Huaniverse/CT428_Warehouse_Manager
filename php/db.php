<?php
// Kết nối cơ sở dữ liệu
require_once __DIR__ . '/config.php';

// Tạo kết nối MySQL
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    // Ghi log nếu kết nối thất bại
    error_log("DB Connection failed: " . $conn->connect_error);
    $conn = null;
} else {
    // Cài đặt charset UTF-8 cho database
    $conn->set_charset("utf8mb4");
}

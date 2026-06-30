<?php
// db.php — Kết nối cơ sở dữ liệu dùng chung
// Được require bởi: index.php, auth.php, login.php, filter_products.php, admin/users.php

$conn = new mysqli("localhost", "root", "", "warehouse_manager");
if ($conn->connect_error) {
    error_log("DB Connection failed: " . $conn->connect_error);
    $conn = null;
} else {
    $conn->set_charset("utf8mb4");
}

<?php
// Điểm vào chính của ứng dụng

// Khởi tạo session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Thiết lập cache để tránh lưu trang đăng nhập
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

define('ROOT_CONTEXT', true);

// Đọc tham số page từ URL
$page = $_GET['page'] ?? '';

// Nếu là trang login hoặc chưa đăng nhập thì hiển thị login
if ($page === 'login' || !isset($_SESSION['user_id'])) {
    require __DIR__ . '/php/Pages/login.php';
} else {
    require __DIR__ . '/php/Pages/index.php';
}

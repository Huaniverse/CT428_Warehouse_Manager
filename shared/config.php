<?php
if (file_exists(__DIR__ . '/config.local.php')) {
    // Môi trường có file local (dev/production đã setup đúng)
    require_once __DIR__ . '/config.local.php';
} else {
    // Fallback: định nghĩa mặc định (chỉ dùng khi chưa tạo config.local.php)
    // CẢNH BÁO: Thay đổi các giá trị này cho môi trường thật
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'warehouse_manager');
}

define('ROOT_PATH', dirname(__DIR__));

require_once __DIR__ . '/helpers.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

// Tự động phát hiện URL base path (hỗ trợ chạy trong subfolder如 XAMPP)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
define('BASE_URL', $protocol . '://' . $host . $scriptDir);

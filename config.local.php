<?php
// Credentials thực tế cho môi trường local/production
// [SEC-06] File này KHÔNG được commit vào git (xem .gitignore)
// Sao chép từ config.local.example.php và điền thông tin thật

define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // TODO: Thay bằng user có quyền hạn chế (không phải root)
define('DB_PASS', '');          // TODO: Đặt mật khẩu mạnh cho môi trường production
define('DB_NAME', 'warehouse_manager');

<?php
// index.php — Front controller (kiểu PersonalBlog)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ROOT_CONTEXT', true);

$page = $_GET['page'] ?? '';

if ($page === 'login' || !isset($_SESSION['user_id'])) {
    require __DIR__ . '/php/Pages/login.php';
} else {
    require __DIR__ . '/php/Pages/index.php';
}

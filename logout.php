<?php
// logout.php — Xử lý đăng xuất
session_start();

require_once __DIR__ . '/db.php';

// Xóa session khỏi bảng sessions trong DB
if ($conn && isset($_SESSION['session_token'])) {
    $token = $_SESSION['session_token'];
    $stmt = $conn->prepare("DELETE FROM sessions WHERE session_token = ?");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}

// Hủy PHP session hoàn toàn
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header('Location: login.php');
exit;

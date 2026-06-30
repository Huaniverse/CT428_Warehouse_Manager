<?php
// auth.php — Session Guard
// Require file này ở đầu mỗi trang cần bảo vệ (SAU khi include db.php)
// Sử dụng: require_once 'auth.php'; (hoặc đường dẫn tương ứng)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu chưa đăng nhập → redirect về login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
    header('Location: login.php');
    exit;
}

// Xác minh session token còn hợp lệ trong DB
if ($conn) {
    $token = $_SESSION['session_token'];
    $stmt = $conn->prepare(
        "SELECT s.user_id, s.expires_at, u.is_active, u.role, u.full_name
         FROM sessions s
         JOIN users u ON s.user_id = u.id
         WHERE s.session_token = ? AND s.expires_at > NOW() AND u.is_active = 1"
    );
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $session_data = $result->fetch_assoc();
        $stmt->close();

        if (!$session_data) {
            // Session hết hạn hoặc user bị vô hiệu hóa → đăng xuất
            $_SESSION = [];
            session_destroy();
            header('Location: login.php?expired=1');
            exit;
        }

        // Đồng bộ dữ liệu role (phòng trường hợp role thay đổi)
        $_SESSION['role']     = $session_data['role'];
        $_SESSION['name']     = $session_data['full_name'];
        $_SESSION['is_active'] = $session_data['is_active'];
    }
}

// ── Tiện ích kiểm tra quyền ──────────────────────────────────────────────────

function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function requireAdmin(): void {
    if (!isAdmin()) {
        http_response_code(403);
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']));
    }
}

function getCurrentUser(): array {
    return [
        'id'       => $_SESSION['user_id']  ?? 0,
        'username' => $_SESSION['username'] ?? '',
        'name'     => $_SESSION['name']     ?? '',
        'role'     => $_SESSION['role']     ?? '',
    ];
}

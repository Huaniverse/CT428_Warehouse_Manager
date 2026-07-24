<?php
// auth.php — Session Guard
// Require file này ở đầu mỗi trang cần bảo vệ (SAU khi include db.php)
// Sử dụng: require_once 'auth.php'; (hoặc đường dẫn tương ứng)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── CSRF Protection ───────────────────────────────────────────────────────────

/**
 * Sinh CSRF token vào session nếu chưa có.
 * Sử dụng random_bytes(32) → bin2hex để đạt entropy 256-bit.
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Xác minh CSRF token từ header X-CSRF-Token hoặc POST field csrf_token.
 * Nếu không hợp lệ → trả 403 JSON và dừng thực thi.
 */
function verifyCsrfToken(): void {
    $expected = $_SESSION['csrf_token'] ?? '';
    // Ưu tiên lấy từ header (AJAX pattern), fallback về POST field
    $received = $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $_SERVER['HTTP_X_CSRF-TOKEN']
        ?? $_POST['csrf_token']
        ?? '';

    if ($expected === '' || !hash_equals($expected, $received)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ. Vui lòng tải lại trang.']);
        exit;
    }
}

// Phát hiện AJAX request: fetch() gửi kèm header X-Requested-With
// hoặc Accept chứa application/json
function isAjaxRequest(): bool {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return str_contains($accept, 'application/json');
}

// Hàm chuyển hướng thống nhất: AJAX → JSON, trang thường → redirect
function redirectToLogin(string $reason = 'expired'): void {
    if (isAjaxRequest()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'  => false,
            'redirect' => 'login.php?expired=1',
            'message'  => $reason === 'expired'
                ? 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.'
                : 'Bạn chưa đăng nhập.',
        ]);
        exit;
    }
    header('Location: login.php?expired=1');
    exit;
}

// Nếu chưa đăng nhập → redirect về login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
    redirectToLogin('unauthenticated');
}

// Xác minh session token còn hợp lệ trong DB
if ($conn) {
    $token = $_SESSION['session_token'];
    $stmt = $conn->prepare(
        "SELECT s.user_id, s.expires_at, u.is_active, u.role, u.full_name, u.allow_import_export
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
            redirectToLogin('expired');
        }

        // Đồng bộ dữ liệu role và quyền (phòng trường hợp thay đổi)
        $_SESSION['role']                = $session_data['role'];
        $_SESSION['name']                = $session_data['full_name'];
        $_SESSION['is_active']           = $session_data['is_active'];
        $_SESSION['allow_import_export'] = $session_data['allow_import_export'] ?? 0;
    }
}

// Sinh CSRF token cho session (dùng ở mọi trang cần bảo vệ)
generateCsrfToken();


// ── Tiện ích kiểm tra quyền ──────────────────────────────────────────────────

function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function isStoreManager(): bool {
    return ($_SESSION['role'] ?? '') === 'store_manager';
}

function canImportExport(): bool {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin' || $role === 'store_manager') return true;
    if ($role === 'staff') return ($_SESSION['allow_import_export'] ?? 0) == 1;
    return false;
}

function requireAdmin(): void {
    if (!isAdmin()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']));
    }
}

function requireAdminOrStoreManager(): void {
    $role = $_SESSION['role'] ?? '';
    if ($role !== 'admin' && $role !== 'store_manager') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']));
    }
}

function requireLogin(): void {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
        redirectToLogin('unauthenticated');
    }
}

function getCurrentUser(): array {
    return [
        'id'                  => $_SESSION['user_id']  ?? 0,
        'username'            => $_SESSION['username'] ?? '',
        'name'                => $_SESSION['name']     ?? '',
        'role'                => $_SESSION['role']     ?? '',
        'allow_import_export' => $_SESSION['allow_import_export'] ?? 0,
    ];
}

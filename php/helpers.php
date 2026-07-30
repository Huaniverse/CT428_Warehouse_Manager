<?php
// Các hàm tiện ích dùng chung

// Kiểm tra phương thức request phải là POST
function requirePost(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
        exit;
    }
}

// Kiểm tra kết nối database còn hoạt động
function requireDb(mysqli $conn): void {
    if (!$conn) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối DB']);
        exit;
    }
}

// Từ chối nếu thao tác trên tài khoản của chính mình
function denyIfSelf(int $target_id): void {
    if ($target_id === (int)$_SESSION['user_id']) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Không thể thực hiện thao tác trên tài khoản của chính mình.']);
        exit;
    }
}

// Lấy vai trò của user theo ID
function getUserRole(mysqli $conn, int $user_id): ?string {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $role = $stmt->get_result()->fetch_assoc()['role'] ?? null;
    $stmt->close();
    return $role;
}

// Kiểm tra định dạng thời gian HH:MM
function isValidTime(string $time): bool {
    return (bool)preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time);
}

// Xóa tất cả session của một user
function deleteUserSessions(mysqli $conn, int $user_id): void {
    $stmt = $conn->prepare("DELETE FROM sessions WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// Tạo HTML option cho dropdown danh mục
function renderCategoryOptions(array $categories, string $selected = ''): string {
    $html = '';
    foreach ($categories as $row) {
        $sel = ($selected === $row['code']) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($row['code']) . '"' . $sel . '>'
               . htmlspecialchars($row['name']) . '</option>';
    }
    return $html;
}

// Kiểm tra lịch truy cập của user
function checkAccessSchedule(array $user): array {
    if (($user['role'] ?? '') === 'admin') {
        return ['allowed' => true, 'message' => ''];
    }
    if (empty($user['has_schedule'])) {
        return ['allowed' => true, 'message' => ''];
    }
    $now   = (new DateTime())->format('H:i:s');
    $start = $user['access_start'] ?? null;
    $end   = $user['access_end']   ?? null;
    if (!$start || !$end) {
        return ['allowed' => false, 'message' => 'Tài khoản chưa được cấu hình giờ truy cập.'];
    }
    $inRange = false;
    if ($start <= $end) {
        $inRange = ($now >= $start && $now <= $end);
    } else {
        $inRange = ($now >= $start || $now <= $end);
    }
    if (!$inRange) {
        $tempUntil = $user['temp_access_until'] ?? null;
        if ($tempUntil && $tempUntil > date('Y-m-d H:i:s')) {
            return ['allowed' => true, 'message' => ''];
        }
        $label = substr($start, 0, 5) . ' – ' . substr($end, 0, 5);
        return [
            'allowed' => false,
            'message' => 'Tài khoản của bạn chỉ được phép truy cập trong khoảng ' . $label . '.',
        ];
    }
    return ['allowed' => true, 'message' => ''];
}

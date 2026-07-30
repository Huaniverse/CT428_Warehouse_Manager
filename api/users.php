<?php
// Quản lý tài khoản người dùng (CRUD, phân quyền, session)

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/partials/helpers-users.php';
requireAdminOrManager();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // Danh sách users
    case 'list':
        requireDb($conn);

        $caller_role = $_SESSION['role'] ?? '';
        $sql = "SELECT u.id, u.username, u.full_name, u.role, u.is_active, u.allow_import_export,
                       u.has_schedule, u.access_start, u.access_end, u.temp_access_until,
                       u.created_at, u.last_login,
                       creator.full_name AS created_by_name
                FROM users u
                LEFT JOIN users creator ON u.created_by = creator.id";
        if ($caller_role === 'manager') {
            $sql .= " WHERE u.role = 'staff'";
        }
        $sql .= " ORDER BY FIELD(u.role, 'admin', 'manager', 'staff'), u.created_at DESC";
        $result = $conn->query($sql);
        $users  = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        echo json_encode(['success' => true, 'users' => $users]);
        break;

    // Tạo tài khoản mới
    case 'create':
        requirePost();
        verifyCsrfToken();
        requireDb($conn);

        $new_username  = trim($_POST['username']  ?? '');
        $new_fullname  = trim($_POST['full_name'] ?? '');
        $new_password  = $_POST['password']        ?? '';
        $new_role      = $_POST['role']            ?? 'staff';
        $creator_id    = $_SESSION['user_id'];

        if ($new_username === '' || $new_fullname === '' || $new_password === '') {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin.']);
            exit;
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $new_username)) {
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập chỉ được gồm chữ cái, số, dấu gạch dưới (3–50 ký tự).']);
            exit;
        }
        if (mb_strlen($new_fullname) > 100) {
            echo json_encode(['success' => false, 'message' => 'Họ và tên không được quá 100 ký tự.']);
            exit;
        }
        if (strlen($new_password) > 255) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu không được quá 255 ký tự.']);
            exit;
        }
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.']);
            exit;
        }
        if (!in_array($new_role, ['manager', 'staff'])) {
            $new_role = 'staff';
        }
        $caller_role = $_SESSION['role'] ?? '';
        if ($caller_role === 'manager' && $new_role !== 'staff') {
            echo json_encode(['success' => false, 'message' => 'Bạn chỉ có thể tạo tài khoản Staff.']);
            exit;
        }

        $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
        $allow_ie = (int)($_POST['allow_import_export'] ?? 0);

        $has_schedule = 0;
        $access_start = null;
        $access_end   = null;
        if ($new_role === 'staff') {
            $has_schedule = 1;
            $access_start = $_POST['access_start'] ?? null;
            $access_end   = $_POST['access_end']   ?? null;
            if (!$access_start || !$access_end) {
                echo json_encode(['success' => false, 'message' => 'Nhân viên phải có giờ truy cập.']);
                exit;
            }
            if (!isValidTime($access_start) || !isValidTime($access_end)) {
                echo json_encode(['success' => false, 'message' => 'Định dạng giờ không hợp lệ.']);
                exit;
            }
        } elseif ($new_role === 'manager') {
            $has_schedule = (int)($_POST['has_schedule'] ?? 0);
            if ($has_schedule) {
                $access_start = $_POST['access_start'] ?? null;
                $access_end   = $_POST['access_end']   ?? null;
                if (!$access_start || !$access_end) {
                    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ giờ truy cập.']);
                    exit;
                }
                if (!isValidTime($access_start) || !isValidTime($access_end)) {
                    echo json_encode(['success' => false, 'message' => 'Định dạng giờ không hợp lệ.']);
                    exit;
                }
            }
        }

        $stmt = $conn->prepare(
            "INSERT INTO users (username, password, full_name, role, allow_import_export, has_schedule, access_start, access_end, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssiissi", $new_username, $hash, $new_fullname, $new_role, $allow_ie, $has_schedule, $access_start, $access_end, $creator_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Tạo tài khoản thành công.', 'id' => $conn->insert_id]);
        } else {
            if ($conn->errno == 1062) {
                echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi tạo tài khoản: ' . $stmt->error]);
            }
        }
        $stmt->close();
        break;

    // Vô hiệu hóa / Kích hoạt lại tài khoản
    case 'toggle':
        requirePost();
        verifyCsrfToken();
        requireDb($conn);

        $target_id  = (int)($_POST['id'] ?? 0);
        $new_status = (int)($_POST['is_active'] ?? 0) ? 1 : 0;

        denyIfSelf($target_id);

        $check = checkManagerTarget($conn, $target_id);
        if (!$check['allowed']) {
            echo json_encode(['success' => false, 'message' => $check['message']]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $target_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            if ($new_status === 0) {
                deleteUserSessions($conn, $target_id);
            }
            $label = $new_status ? 'kích hoạt' : 'vô hiệu hóa';
            echo json_encode(['success' => true, 'message' => "Đã $label tài khoản."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản hoặc không có thay đổi.']);
        }
        $stmt->close();
        break;

    // Xóa tài khoản
    case 'delete':
        requirePost();
        verifyCsrfToken();
        requireDb($conn);

        $target_id = (int)($_POST['id'] ?? 0);

        denyIfSelf($target_id);

        $target_role = getUserRole($conn, $target_id);
        if (!$target_role) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản.']);
            exit;
        }
        if ($target_role === 'admin') {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản admin.']);
            exit;
        }
        $check = checkManagerTarget($conn, $target_id);
        if (!$check['allowed']) {
            echo json_encode(['success' => false, 'message' => $check['message']]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Đã xóa tài khoản.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi xóa tài khoản: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    // Danh sách phiên đăng nhập đang hoạt động
    case 'sessions':
        requireDb($conn);

        $caller_role = $_SESSION['role'] ?? '';
        $my_user_id  = (int)$_SESSION['user_id'];

        if ($caller_role === 'manager') {
            $sql = "SELECT s.session_token, s.user_id, u.username, u.full_name, u.role,
                           s.ip_address, s.user_agent, s.created_at, s.expires_at
                    FROM sessions s
                    JOIN users u ON s.user_id = u.id
                    WHERE s.expires_at > NOW() AND (u.role = 'staff' OR u.id = ?)
                    ORDER BY FIELD(u.role, 'admin', 'manager', 'staff'), s.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $my_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql = "SELECT s.session_token, s.user_id, u.username, u.full_name, u.role,
                           s.ip_address, s.user_agent, s.created_at, s.expires_at
                    FROM sessions s
                    JOIN users u ON s.user_id = u.id
                    WHERE s.expires_at > NOW()
                    ORDER BY FIELD(u.role, 'admin', 'manager', 'staff'), s.created_at DESC";
            $result = $conn->query($sql);
        }
        $sessions = [];
        $my_token = $_SESSION['session_token'];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['is_current'] = ($row['session_token'] === $my_token);
                $row['token_display'] = substr($row['session_token'], 0, 8) . '...';
                $sessions[] = $row;
            }
        }
        echo json_encode(['success' => true, 'sessions' => $sessions]);
        break;

    // Kick user (xóa phiên từ xa)
    case 'kick':
        requirePost();
        verifyCsrfToken();
        requireDb($conn);

        $kick_user_id = (int)($_POST['user_id'] ?? 0);

        denyIfSelf($kick_user_id);

        $check = checkManagerTarget($conn, $kick_user_id);
        if (!$check['allowed']) {
            echo json_encode(['success' => false, 'message' => $check['message']]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM sessions WHERE user_id = ? AND session_token != ?");
        $my_token = $_SESSION['session_token'];
        $stmt->bind_param("is", $kick_user_id, $my_token);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Đã đăng xuất user khỏi hệ thống.', 'rows' => $stmt->affected_rows]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    // Cấp quyền nhập/xuất kho cho staff
    case 'update_permissions':
        requirePost();
        verifyCsrfToken();
        requireDb($conn);

        $target_id       = (int)($_POST['id'] ?? 0);
        $allow_import    = (int)($_POST['allow_import_export'] ?? 0) ? 1 : 0;

        denyIfSelf($target_id);

        $target_role = getUserRole($conn, $target_id);
        if (!$target_role) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản.']);
            exit;
        }
        if ($target_role !== 'staff') {
            echo json_encode(['success' => false, 'message' => 'Chỉ có thể cấp quyền cho tài khoản Staff.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE users SET allow_import_export = ? WHERE id = ?");
        $stmt->bind_param("ii", $allow_import, $target_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $label = $allow_import ? 'cấp' : 'thu hồi';
            echo json_encode(['success' => true, 'message' => "Đã $label quyền nhập/xuất kho."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản hoặc không có thay đổi.']);
        }
        $stmt->close();
        break;

    // Lấy chi tiết tài khoản
    case 'get_detail':
        requireDb($conn);

        $target_id = (int)($_GET['id'] ?? 0);
        if ($target_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ.']);
            exit;
        }

        $stmt = $conn->prepare(
            "SELECT u.id, u.username, u.full_name, u.role, u.is_active, u.allow_import_export,
                    u.has_schedule, u.access_start, u.access_end,
                    u.created_at, u.last_login,
                    creator.full_name AS created_by_name
             FROM users u
             LEFT JOIN users creator ON u.created_by = creator.id
             WHERE u.id = ?"
        );
        $stmt->bind_param("i", $target_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản.']);
            exit;
        }

        $check = checkManagerTarget($conn, $target_id);
        if (!$check['allowed']) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xem tài khoản này.']);
            exit;
        }

        echo json_encode(['success' => true, 'user' => $user]);
        break;

    // Cập nhật lịch truy cập
    case 'update_schedule':
        requirePost();
        verifyCsrfToken();
        requireDb($conn);

        $target_id   = (int)($_POST['id'] ?? 0);
        $has_sched   = (int)($_POST['has_schedule'] ?? 0);
        $sched_start = $_POST['access_start'] ?? null;
        $sched_end   = $_POST['access_end']   ?? null;

        if ($target_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ.']);
            exit;
        }

        $target_role = getUserRole($conn, $target_id);
        if (!$target_role) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản.']);
            exit;
        }
        if ($target_role === 'admin') {
            echo json_encode(['success' => false, 'message' => 'Admin không bị áp dụng lịch truy cập.']);
            exit;
        }

        $check = checkManagerTarget($conn, $target_id);
        if (!$check['allowed']) {
            echo json_encode(['success' => false, 'message' => $check['message']]);
            exit;
        }

        if ($target_role === 'staff') {
            $has_sched = 1;
        }

        if ($has_sched) {
            if (!$sched_start || !$sched_end) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ giờ truy cập.']);
                exit;
            }
            if (!isValidTime($sched_start) || !isValidTime($sched_end)) {
                echo json_encode(['success' => false, 'message' => 'Định dạng giờ không hợp lệ.']);
                exit;
            }
        }

        $stmt = $conn->prepare("UPDATE users SET has_schedule = ?, access_start = ?, access_end = ? WHERE id = ?");
        $stmt->bind_param("issi", $has_sched, $sched_start, $sched_end, $target_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Đã cập nhật lịch truy cập.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    // Đặt lại mật khẩu
    case 'reset_password':
        requirePost();
        verifyCsrfToken();
        requireDb($conn);

        $target_id     = (int)($_POST['user_id'] ?? 0);
        $new_password  = $_POST['new_password']  ?? '';
        $confirm_pass  = $_POST['confirm_password'] ?? '';

        if ($target_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ.']);
            exit;
        }
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.']);
            exit;
        }
        if (strlen($new_password) > 255) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu không được quá 255 ký tự.']);
            exit;
        }
        if ($new_password !== $confirm_pass) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp.']);
            exit;
        }

        $target_role = getUserRole($conn, $target_id);
        if (!$target_role) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản.']);
            exit;
        }

        $check = checkManagerTarget($conn, $target_id);
        if (!$check['allowed']) {
            echo json_encode(['success' => false, 'message' => $check['message']]);
            exit;
        }

        $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $target_id);

        if ($stmt->execute()) {
            deleteUserSessions($conn, $target_id);
            echo json_encode(['success' => true, 'message' => 'Đã đặt lại mật khẩu. Người dùng sẽ phải đăng nhập lại.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi đặt lại mật khẩu: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    // Cập nhật thông tin tài khoản
    case 'update':
        requirePost();
        verifyCsrfToken();
        requireDb($conn);

        $target_id    = (int)($_POST['id'] ?? 0);
        $new_fullname = trim($_POST['full_name'] ?? '');
        $new_role     = $_POST['role'] ?? '';
        $has_schedule = isset($_POST['has_schedule']) ? (int)$_POST['has_schedule'] : null;
        $access_start = $_POST['access_start'] ?? null;
        $access_end   = $_POST['access_end']   ?? null;
        $allow_ie     = isset($_POST['allow_import_export']) ? (int)$_POST['allow_import_export'] : null;

        if ($target_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ.']);
            exit;
        }
        if ($new_fullname === '') {
            echo json_encode(['success' => false, 'message' => 'Họ và tên không được để trống.']);
            exit;
        }
        if (mb_strlen($new_fullname) > 100) {
            echo json_encode(['success' => false, 'message' => 'Họ và tên không được quá 100 ký tự.']);
            exit;
        }

        denyIfSelf($target_id);

        $target_role = getUserRole($conn, $target_id);
        if (!$target_role) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản.']);
            exit;
        }

        $check = checkManagerTarget($conn, $target_id);
        if (!$check['allowed']) {
            echo json_encode(['success' => false, 'message' => $check['message']]);
            exit;
        }

        $caller_role = $_SESSION['role'] ?? '';
        if ($caller_role === 'manager') {
            $new_role = $target_role;
        }

        if (!in_array($new_role, ['admin', 'manager', 'staff'])) {
            echo json_encode(['success' => false, 'message' => 'Vai trò không hợp lệ.']);
            exit;
        }
        if ($new_role === 'admin') {
            echo json_encode(['success' => false, 'message' => 'Không được phép cấp quyền Admin từ form sửa.']);
            exit;
        }

        if ($has_schedule !== null) {
            if ($new_role === 'admin') {
                $has_schedule = 0;
                $access_start = null;
                $access_end   = null;
            }
            if ($new_role === 'staff') {
                $has_schedule = 1;
            }
            if ($has_schedule) {
                if (!$access_start || !$access_end) {
                    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ giờ truy cập.']);
                    exit;
                }
                if (!isValidTime($access_start) || !isValidTime($access_end)) {
                    echo json_encode(['success' => false, 'message' => 'Định dạng giờ không hợp lệ.']);
                    exit;
                }
            }
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, role = ?, has_schedule = ?, access_start = ?, access_end = ?, allow_import_export = ? WHERE id = ?");
            $stmt->bind_param("ssissii", $new_fullname, $new_role, $has_schedule, $access_start, $access_end, $allow_ie, $target_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, role = ?, allow_import_export = ? WHERE id = ?");
            $stmt->bind_param("ssii", $new_fullname, $new_role, $allow_ie, $target_id);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Đã cập nhật thông tin tài khoản.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    // Cấp quyền truy cập tạm thời
    case 'grant_temp_access':
        requirePost();
        verifyCsrfToken();
        requireDb($conn);

        $target_id = (int)($_POST['user_id'] ?? 0);
        $minutes   = (int)($_POST['minutes'] ?? 10);
        $minutes   = max(1, min(120, $minutes));

        if ($target_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ.']);
            exit;
        }

        $target_role = getUserRole($conn, $target_id);
        if (!$target_role) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản.']);
            exit;
        }

        $permCheck = checkManagerTarget($conn, $target_id);
        if (!$permCheck['allowed']) {
            echo json_encode(['success' => false, 'message' => $permCheck['message']]);
            exit;
        }

        if ($target_role === 'admin') {
            echo json_encode(['success' => false, 'message' => 'Không thể cấp quyền tạm thời cho Admin.']);
            exit;
        }

        $until = date('Y-m-d H:i:s', time() + ($minutes * 60));
        $stmt = $conn->prepare("UPDATE users SET temp_access_until = ? WHERE id = ?");
        $stmt->bind_param("si", $until, $target_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => "Đã cấp quyền truy cập tạm thời {$minutes} phút.", 'temp_access_until' => $until]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    // Thu hồi quyền truy cập tạm thời
    case 'revoke_temp_access':
        requirePost();
        verifyCsrfToken();
        requireDb($conn);

        $target_id = (int)($_POST['user_id'] ?? 0);
        if ($target_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ.']);
            exit;
        }

        $permCheck = checkManagerTarget($conn, $target_id);
        if (!$permCheck['allowed']) {
            echo json_encode(['success' => false, 'message' => $permCheck['message']]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE users SET temp_access_until = NULL WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Đã thu hồi quyền truy cập tạm thời.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}

if ($conn) $conn->close();

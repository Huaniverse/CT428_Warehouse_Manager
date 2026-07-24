<?php
// admin/users.php — API quản lý tài khoản (chỉ Admin)
// Tất cả response trả về JSON

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
requireAdminOrStoreManager();

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ── Danh sách users ──────────────────────────────────────────────────────
    case 'list':
        if (!$conn) { echo json_encode(['success' => false, 'message' => 'Lỗi kết nối DB']); exit; }

        $sql = "SELECT u.id, u.username, u.full_name, u.role, u.is_active, u.allow_import_export,
                       u.created_at, u.last_login,
                       creator.full_name AS created_by_name
                FROM users u
                LEFT JOIN users creator ON u.created_by = creator.id
                ORDER BY u.created_at DESC";
        $result = $conn->query($sql);
        $users  = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        echo json_encode(['success' => true, 'users' => $users]);
        break;

    // ── Tạo tài khoản staff mới ──────────────────────────────────────────────
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']); exit;
        }
        verifyCsrfToken(); // [SEC-01] Chống CSRF
        if (!$conn) { echo json_encode(['success' => false, 'message' => 'Lỗi kết nối DB']); exit; }

        $new_username  = trim($_POST['username']  ?? '');
        $new_fullname  = trim($_POST['full_name'] ?? '');
        $new_password  = $_POST['password']        ?? '';
        $new_role      = $_POST['role']            ?? 'staff';
        $creator_id    = $_SESSION['user_id'];

        // Validation
        if ($new_username === '' || $new_fullname === '' || $new_password === '') {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin.']); exit;
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $new_username)) {
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập chỉ được gồm chữ cái, số, dấu gạch dưới (3–50 ký tự).']); exit;
        }
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.']); exit;
        }
        if (!in_array($new_role, ['admin', 'store_manager', 'staff'])) {
            $new_role = 'staff';
        }
        // Store manager chỉ có thể tạo tài khoản staff
        $caller_role = $_SESSION['role'] ?? '';
        if ($caller_role === 'store_manager' && $new_role !== 'staff') {
            $new_role = 'staff';
        }

        $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
        $allow_ie = (int)($_POST['allow_import_export'] ?? 0);

        $stmt = $conn->prepare(
            "INSERT INTO users (username, password, full_name, role, allow_import_export, created_by) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssii", $new_username, $hash, $new_fullname, $new_role, $allow_ie, $creator_id);

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

    // ── Vô hiệu hóa / Kích hoạt lại tài khoản ──────────────────────────────
    case 'toggle':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']); exit;
        }
        verifyCsrfToken(); // [SEC-01] Chống CSRF
        if (!$conn) { echo json_encode(['success' => false, 'message' => 'Lỗi kết nối DB']); exit; }

        $target_id  = (int)($_POST['id'] ?? 0);
        $new_status = (int)($_POST['is_active'] ?? 0) ? 1 : 0;

        // Không cho tự vô hiệu hóa bản thân
        if ($target_id === (int)$_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Không thể vô hiệu hóa tài khoản của chính mình.']); exit;
        }

        // Store manager chỉ được toggle tài khoản staff
        $caller_role = $_SESSION['role'] ?? '';
        if ($caller_role === 'store_manager') {
            $check_role = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $check_role->bind_param("i", $target_id);
            $check_role->execute();
            $target_role = $check_role->get_result()->fetch_assoc()['role'] ?? '';
            $check_role->close();
            if ($target_role !== 'staff') {
                echo json_encode(['success' => false, 'message' => 'Chỉ được thao tác trên tài khoản Staff.']); exit;
            }
        }

        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $target_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Nếu vô hiệu hóa → xóa hết sessions của user đó
            if ($new_status === 0) {
                $stmt2 = $conn->prepare("DELETE FROM sessions WHERE user_id = ?");
                $stmt2->bind_param("i", $target_id);
                $stmt2->execute();
                $stmt2->close();
            }
            $label = $new_status ? 'kích hoạt' : 'vô hiệu hóa';
            echo json_encode(['success' => true, 'message' => "Đã $label tài khoản."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản hoặc không có thay đổi.']);
        }
        $stmt->close();
        break;

    // ── Xóa tài khoản ────────────────────────────────────────────────────────
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']); exit;
        }
        verifyCsrfToken(); // [SEC-01] Chống CSRF
        if (!$conn) { echo json_encode(['success' => false, 'message' => 'Lỗi kết nối DB']); exit; }

        $target_id = (int)($_POST['id'] ?? 0);

        // Không cho tự xóa bản thân
        if ($target_id === (int)$_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản của chính mình.']); exit;
        }

        // Không cho xóa admin khác (chỉ xóa staff)
        $check = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $check->bind_param("i", $target_id);
        $check->execute();
        $target_user = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$target_user) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản.']); exit;
        }
        if ($target_user['role'] === 'admin') {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản admin.']); exit;
        }
        // Store manager chỉ được xóa tài khoản staff
        $caller_role = $_SESSION['role'] ?? '';
        if ($caller_role === 'store_manager' && $target_user['role'] !== 'staff') {
            echo json_encode(['success' => false, 'message' => 'Chỉ được xóa tài khoản Staff.']); exit;
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

    // ── Danh sách phiên đăng nhập đang hoạt động ────────────────────────────
    case 'sessions':
        if (!$conn) { echo json_encode(['success' => false, 'message' => 'Lỗi kết nối DB']); exit; }

        $sql = "SELECT s.session_token, s.user_id, u.username, u.full_name, u.role,
                       s.ip_address, s.user_agent, s.created_at, s.expires_at
                FROM sessions s
                JOIN users u ON s.user_id = u.id
                WHERE s.expires_at > NOW()
                ORDER BY s.created_at DESC";
        $result   = $conn->query($sql);
        $sessions = [];
        $my_token = $_SESSION['session_token'];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['is_current'] = ($row['session_token'] === $my_token);
                // Ẩn token khỏi response (chỉ cần prefix để hiển thị)
                $row['token_display'] = substr($row['session_token'], 0, 8) . '...';
                $sessions[] = $row;
            }
        }
        echo json_encode(['success' => true, 'sessions' => $sessions]);
        break;

    // ── Kick user (xóa phiên từ xa) ─────────────────────────────────────────
    case 'kick':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']); exit;
        }
        verifyCsrfToken(); // [SEC-01] Chống CSRF
        if (!$conn) { echo json_encode(['success' => false, 'message' => 'Lỗi kết nối DB']); exit; }

        $kick_user_id = (int)($_POST['user_id'] ?? 0);

        if ($kick_user_id === (int)$_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Không thể kick phiên của chính mình.']); exit;
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

    // ── Cấp quyền nhập/xuất kho cho staff ────────────────────────────────
    case 'update_permissions':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']); exit;
        }
        verifyCsrfToken(); // [SEC-01] Chống CSRF
        if (!$conn) { echo json_encode(['success' => false, 'message' => 'Lỗi kết nối DB']); exit; }

        $target_id       = (int)($_POST['id'] ?? 0);
        $allow_import    = (int)($_POST['allow_import_export'] ?? 0) ? 1 : 0;

        // Chỉ admin và store_manager mới được cấp quyền
        $caller_role = $_SESSION['role'] ?? '';
        if ($caller_role !== 'admin' && $caller_role !== 'store_manager') {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cấp quyền này.']); exit;
        }

        // Không cho thay đổi quyền của chính mình
        if ($target_id === (int)$_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Không thể thay đổi quyền của chính mình.']); exit;
        }

        // Kiểm tra target là staff
        $check = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $check->bind_param("i", $target_id);
        $check->execute();
        $target_user = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$target_user) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản.']); exit;
        }
        if ($target_user['role'] !== 'staff') {
            echo json_encode(['success' => false, 'message' => 'Chỉ có thể cấp quyền cho tài khoản Staff.']); exit;
        }

        $stmt = $conn->prepare("UPDATE users SET allow_import_export = ? WHERE id = ?");
        $stmt->bind_param("ii", $allow_import, $target_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) { // [FIX-04] >= 0 luôn true → sửa thành > 0

            $label = $allow_import ? 'cấp' : 'thu hồi';
            echo json_encode(['success' => true, 'message' => "Đã $label quyền nhập/xuất kho."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản hoặc không có thay đổi.']);
        }
        $stmt->close();
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}

if ($conn) $conn->close();

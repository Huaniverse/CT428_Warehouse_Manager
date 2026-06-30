<?php
// login.php — Trang đăng nhập
session_start();

// Nếu đã đăng nhập → chuyển thẳng vào trang chính
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/db.php';

$error   = '';
$success = '';

// Xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } elseif (!$conn) {
        $error = 'Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, full_name, role, is_active FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        } elseif (!$user['is_active']) {
            $error = 'Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        } else {
            // Xác thực thành công
            session_regenerate_id(true);

            // Tạo session token ngẫu nhiên
            $token      = bin2hex(random_bytes(64));
            $expires_at = date('Y-m-d H:i:s', strtotime('+8 hours'));
            $ip         = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua         = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

            // Ghi vào bảng sessions
            $stmt2 = $conn->prepare(
                "INSERT INTO sessions (session_token, user_id, ip_address, user_agent, expires_at)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt2->bind_param("sisss", $token, $user['id'], $ip, $ua, $expires_at);
            $stmt2->execute();
            $stmt2->close();

            // Cập nhật last_login
            $stmt3 = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt3->bind_param("i", $user['id']);
            $stmt3->execute();
            $stmt3->close();

            // Lưu session PHP
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['name']         = $user['full_name'];
            $_SESSION['role']         = $user['role'];
            $_SESSION['session_token'] = $token;

            $conn->close();
            header('Location: index.php');
            exit;
        }
    }
}

$expired = isset($_GET['expired']) && $_GET['expired'] == '1';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập — Quản Lí Kho</title>
  <meta name="description" content="Đăng nhập vào hệ thống Quản Lí Kho hàng.">
  <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
</head>
<body class="login_body">

  <div class="login_bg">
    <div class="login_bg_circle circle1"></div>
    <div class="login_bg_circle circle2"></div>
    <div class="login_bg_circle circle3"></div>
  </div>

  <div class="login_wrapper">
    <!-- Brand Panel -->
    <div class="login_brand">
      <div class="brand_logo">
        <span class="material-symbols-outlined">inventory_2</span>
      </div>
      <h1 class="brand_title">Quản Lí Kho</h1>
      <p class="brand_subtitle">Hệ thống quản lý kho hàng hiệu quả, theo dõi tồn kho theo thời gian thực.</p>
      <div class="brand_features">
        <div class="brand_feature">
          <span class="material-symbols-outlined">dashboard</span>
          <span>Tổng quan trực quan</span>
        </div>
        <div class="brand_feature">
          <span class="material-symbols-outlined">inventory</span>
          <span>Quản lý sản phẩm</span>
        </div>
        <div class="brand_feature">
          <span class="material-symbols-outlined">group</span>
          <span>Phân quyền nhân viên</span>
        </div>
      </div>
    </div>

    <!-- Login Card -->
    <div class="login_card">
      <div class="login_card_header">
        <h2>Chào mừng trở lại</h2>
        <p>Đăng nhập để tiếp tục quản lý kho hàng</p>
      </div>

      <?php if ($expired): ?>
        <div class="alert alert_warning" id="alert_expired">
          <span class="material-symbols-outlined">schedule</span>
          <span>Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.</span>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert_error" id="alert_error">
          <span class="material-symbols-outlined">error</span>
          <span><?php echo htmlspecialchars($error); ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" class="login_form" id="loginForm" novalidate>
        <div class="form_group">
          <label for="username">Tên đăng nhập</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">person</span>
            <input
              type="text"
              id="username"
              name="username"
              class="form_input"
              placeholder="Nhập tên đăng nhập..."
              value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
              autocomplete="username"
              required
            >
          </div>
        </div>

        <div class="form_group">
          <label for="password">Mật khẩu</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">lock</span>
            <input
              type="password"
              id="password"
              name="password"
              class="form_input"
              placeholder="Nhập mật khẩu..."
              autocomplete="current-password"
              required
            >
            <button type="button" class="toggle_password" id="togglePassword" title="Hiện/ẩn mật khẩu">
              <span class="material-symbols-outlined" id="toggleIcon">visibility</span>
            </button>
          </div>
        </div>

        <button type="submit" class="login_btn" id="loginBtn">
          <span class="material-symbols-outlined">login</span>
          <span>Đăng nhập</span>
        </button>
      </form>

      <p class="login_footer_note">
        <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle;">info</span>
        Liên hệ quản trị viên nếu quên mật khẩu.
      </p>
    </div>
  </div>

  <script>
    // Toggle hiển thị mật khẩu
    const toggleBtn = document.getElementById('togglePassword');
    const toggleIcon = document.getElementById('toggleIcon');
    const passwordInput = document.getElementById('password');

    toggleBtn.addEventListener('click', function() {
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = 'visibility_off';
      } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = 'visibility';
      }
    });

    // Hiệu ứng loading khi submit
    const loginForm = document.getElementById('loginForm');
    const loginBtn  = document.getElementById('loginBtn');
    loginForm.addEventListener('submit', function() {
      loginBtn.disabled = true;
      loginBtn.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span><span>Đang xử lý...</span>';
    });
  </script>
</body>
</html>

  <?php $current_user = $current_user ?? getCurrentUser(); ?>
  <header>
    <div class="header_left">
      <span class="material-symbols-outlined logo_icon">package_2</span>
      <h1>Quản Lí Kho</h1>
    </div>
    <div class="header_right">
      <!-- Dropdown tai khoan nguoi dung -->
      <div class="user_dropdown_wrapper" id="userDropdownWrapper">
        <button class="user_dropdown_trigger" id="userDropdownTrigger" title="Tài khoản của bạn">
          <span class="material-symbols-outlined">account_circle</span>
          <span class="user_name_display"><?php echo htmlspecialchars($current_user['name']); ?></span>
          <span class="user_role_badge <?php echo $current_user['role']; ?>">
            <?php
              $role_labels = ['admin' => 'Admin', 'manager' => 'Quản lý kho', 'staff' => 'Nhân viên'];
              echo $role_labels[$current_user['role']] ?? 'Staff';
            ?>
          </span>
          <span class="material-symbols-outlined icon_expand">expand_more</span>
        </button>
        <div class="user_dropdown_menu" id="userDropdownMenu">
          <div class="dropdown_user_info">
            <div class="u_name"><?php echo htmlspecialchars($current_user['name']); ?></div>
            <div class="u_username">@<?php echo htmlspecialchars($current_user['username']); ?></div>
          </div>
          <a href="php/Pages/logout.php" class="dropdown_item danger" id="logoutBtn">
            <span class="material-symbols-outlined">logout</span>
            <span>Đăng xuất</span>
          </a>
        </div>
      </div>
    </div>
  </header>

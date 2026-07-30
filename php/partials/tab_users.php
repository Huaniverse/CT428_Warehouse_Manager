      <?php
      // Tab quản lý nhân viên
      $is_admin         = $is_admin         ?? isAdmin();
      $is_manager = $is_manager ?? isManager();
      if ($is_admin || $is_manager):
      ?>
      <!-- Tab quan ly nhan vien -->
      <div id="content_caidat" class="tab_content">
        <h1 class="page_title">Quản lý nhân viên</h1>
        <p class="page_subtitle">Tạo và quản lý tài khoản nhân viên, theo dõi phiên đăng nhập đang hoạt động.</p>

        <!-- Danh sach tai khoan -->
        <div class="settings_section">
          <div class="settings_section_header">
            <h3>
              <span class="material-symbols-outlined">group</span>
              Danh sách tài khoản
            </h3>
             <button class="btn_primary" id="btnOpenCreateModal" <?php if (!$is_admin): ?>style="display:none"<?php endif; ?>>
              <span class="material-symbols-outlined">person_add</span>
              Tạo tài khoản
            </button>
          </div>
          <div class="settings_section_body" style="padding: 0;">
            <table class="users_table" id="usersTable">
              <thead>
                <tr>
                  <th>Nhân viên</th>
                  <th>Vai trò</th>
                  <th>Trạng thái</th>
                  <th>Lịch truy cập</th>
                  <th>Đăng nhập lần cuối</th>
                  <th>Người tạo</th>
                  <th style="width:120px;">Thao tác</th>
                </tr>
              </thead>
              <tbody id="usersTableBody">
                <tr><td colspan="7" class="table_loading">Đang tải...</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Phien dang nhap dang hoat dong -->
        <div class="settings_section">
          <div class="settings_section_header">
            <h3>
              <span class="material-symbols-outlined">devices</span>
              Phiên đang hoạt động
            </h3>
            <button class="btn_secondary" id="btnRefreshSessions" aria-label="Làm mới danh sách phiên">
              <span class="material-symbols-outlined" aria-hidden="true">refresh</span>
              Làm mới
            </button>
          </div>
          <div class="settings_section_body">
            <div class="session_list" id="sessionList">
              <div class="table_loading">Đang tải...</div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

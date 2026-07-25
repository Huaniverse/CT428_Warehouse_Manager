      <?php
      // shared/components/tab_users.php — Bảo đảm biến role tồn tại (tránh lỗi Undefined khi include độc lập)
      $is_admin         = $is_admin         ?? isAdmin();
      $is_store_manager = $is_store_manager ?? isStoreManager();
      if ($is_admin || $is_store_manager):
      ?>
      <!-- TAB QUẢN LÝ NGƯỜI DÙNG -->
      <div id="content_caidat" class="tab_content">
        <h1 style="font-size: 24px; font-weight: 600; color: #0f172a; margin: 0 0 4px 0;">Quản lý người dùng</h1>
        <p style="font-size: 14px; color: #64748b; margin: 0 0 24px 0;">Tạo và quản lý tài khoản nhân viên, theo dõi phiên đăng nhập đang hoạt động.</p>

        <!-- Section: Danh sách tài khoản -->
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
                  <th>Người dùng</th>
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

        <!-- Section: Phiên đăng nhập đang hoạt động -->
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

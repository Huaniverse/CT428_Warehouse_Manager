<?php
$is_admin        = $is_admin        ?? isAdmin();
$is_manager= $is_manager?? isManager();
?>
  <aside>
    <nav class="sidebar_menu">
      <a class="menu_item active" data-tab="tongquan">
        <span class="material-symbols-outlined">dashboard</span>
        <span>Tổng quan</span>
      </a>
      <a class="menu_item" data-tab="khohang">
        <span class="material-symbols-outlined">inventory</span>
        <span>Kho hàng</span>
      </a>
      <?php if (canImportExport()): ?>
      <a class="menu_item" data-tab="lichsu">
        <span class="material-symbols-outlined">history</span>
        <span>Lịch sử</span>
      </a>
      <?php endif; ?>
      <?php if ($is_admin || $is_manager): ?>
      <a class="menu_item" data-tab="caidat">
        <span class="material-symbols-outlined">manage_accounts</span>
        <span>Quản lý nhân viên</span>
      </a>
      <?php endif; ?>
    </nav>
  </aside>

<?php
// Kết nối DB và kiểm tra session
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$current_user = getCurrentUser();
$is_admin     = isAdmin();

// Fetch stats if connection is valid
$total_categories = 0;
$total_quantity   = 0;
$total_val        = 0;
$total_low        = 0;

$chart1_labels = [];
$chart1_data   = [];
$chart2_labels = [];
$chart2_data   = [];

if ($conn) {
    // 1. Total categories
    if ($res = $conn->query("SELECT COUNT(*) as total FROM danhmuc")) {
        $total_categories = $res->fetch_assoc()['total'];
    }

    // 2. Total items
    if ($res = $conn->query("SELECT SUM(SoLuong) as total FROM sanpham")) {
        $total_quantity = $res->fetch_assoc()['total'];
        if (is_null($total_quantity)) $total_quantity = 0;
    }

    // 3. Total inventory value
    if ($res = $conn->query("SELECT SUM(SoLuong * Gia) as total FROM sanpham")) {
        $total_val = $res->fetch_assoc()['total'];
        if (is_null($total_val)) $total_val = 0;
    }

    // 4. Low stock items (quantity < 30)
    if ($res = $conn->query("SELECT COUNT(*) as total FROM sanpham WHERE SoLuong < 30")) {
        $total_low = $res->fetch_assoc()['total'];
    }

    // 5. Quantity by Category
    $sql_chart1 = "SELECT d.TenDM, SUM(s.SoLuong) as TongSoLuong
                  FROM sanpham s
                  JOIN danhmuc d ON s.DanhMuc = d.MaDM
                  GROUP BY d.MaDM, d.TenDM";
    if ($res_chart1 = $conn->query($sql_chart1)) {
        while ($row = $res_chart1->fetch_assoc()) {
            $chart1_labels[] = $row['TenDM'];
            $chart1_data[]   = (int)$row['TongSoLuong'];
        }
    }

    // 6. Value by Category
    $sql_chart2 = "SELECT d.TenDM, SUM(s.SoLuong * s.Gia) as TongGiaTri
                  FROM sanpham s
                  JOIN danhmuc d ON s.DanhMuc = d.MaDM
                  GROUP BY d.MaDM, d.TenDM";
    if ($res_chart2 = $conn->query($sql_chart2)) {
        while ($row = $res_chart2->fetch_assoc()) {
            $chart2_labels[] = $row['TenDM'];
            $chart2_data[]   = (double)$row['TongGiaTri'];
        }
    }

    // 7. [FIX-07] Danh sách danh mục — lấy 1 lần, dùng lại cho cả 2 dropdown
    $categories_list = [];
    if ($res_cat = $conn->query("SELECT MaDM, TenDM FROM danhmuc ORDER BY TenDM ASC")) {
        while ($row = $res_cat->fetch_assoc()) {
            $categories_list[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Quản Lí Kho — Nhóm 10</title>
  <meta name="description" content="Hệ thống quản lý kho hàng, theo dõi tồn kho theo thời gian thực.">
  <!-- CSRF token — được sinh bởi auth.php::generateCsrfToken(), dùng bởi apiFetch() -->
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body>
  <header>
    <div class="header_left">
      <span class="material-symbols-outlined logo_icon">inventory_2</span>
      <h1>Quản Lí Kho</h1>
    </div>
    <div class="header_right">
      <!-- [FIX-05] Ẩn thanh tìm kiếm header và nút thông báo — chưa implement, dùng phương án A -->
      <div class="display_right" style="display:none;">
        <button class="search_button">
          <span class="material-symbols-outlined">search</span>
        </button>
        <input class="input_find" type="text" placeholder="Tìm kiếm nhanh...">
      </div>
      <button class="action_button" title="Thông báo" style="display:none;">
        <span class="material-symbols-outlined">notifications</span>
      </button>

      <!-- User dropdown -->
      <div class="user_dropdown_wrapper" id="userDropdownWrapper">
        <button class="user_dropdown_trigger" id="userDropdownTrigger" title="Tài khoản của bạn">
          <span class="material-symbols-outlined">account_circle</span>
          <span class="user_name_display"><?php echo htmlspecialchars($current_user['name']); ?></span>
          <span class="user_role_badge <?php echo $current_user['role']; ?>">
            <?php
              $role_labels = ['admin' => 'Admin', 'store_manager' => 'CH Trưởng', 'staff' => 'Staff'];
              echo $role_labels[$current_user['role']] ?? 'Staff';
            ?>
          </span>
          <span class="material-symbols-outlined" style="font-size:16px; color:#94a3b8;">expand_more</span>
        </button>
        <div class="user_dropdown_menu" id="userDropdownMenu">
          <div class="dropdown_user_info">
            <div class="u_name"><?php echo htmlspecialchars($current_user['name']); ?></div>
            <div class="u_username">@<?php echo htmlspecialchars($current_user['username']); ?></div>
          </div>
          <a href="logout.php" class="dropdown_item danger" id="logoutBtn">
            <span class="material-symbols-outlined">logout</span>
            <span>Đăng xuất</span>
          </a>
        </div>
      </div>
    </div>
  </header>

  <main>
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
        <?php if ($is_admin): ?>
        <a class="menu_item" data-tab="caidat">
          <span class="material-symbols-outlined">manage_accounts</span>
          <span>Quản lý người dùng</span>
        </a>
        <?php endif; ?>
      </nav>
    </aside>

    <div class="main_content">
      <!-- TAB TỔNG QUAN -->
      <div id="content_tongquan" class="tab_content active_tab">
        <h1 style="font-size: 24px; font-weight: 600; color: #0f172a; margin: 0 0 4px 0;">Tổng quan kho hàng</h1>
        <p style="font-size: 14px; color: #64748b; margin: 0 0 24px 0;">Thống kê và báo cáo số lượng, giá trị tồn kho theo thời gian thực</p>

        <div class="dashboard_grid">
          <!-- Card 1 -->
          <div class="kpi_card">
            <div class="kpi_icon blue">
              <span class="material-symbols-outlined">category</span>
            </div>
            <div class="kpi_info">
              <span class="kpi_title">Danh mục</span>
              <span class="kpi_value"><?php echo number_format($total_categories); ?></span>
            </div>
          </div>

          <!-- Card 2 -->
          <div class="kpi_card">
            <div class="kpi_icon green">
              <span class="material-symbols-outlined">inventory_2</span>
            </div>
            <div class="kpi_info">
              <span class="kpi_title">Tổng sản phẩm</span>
              <span class="kpi_value"><?php echo number_format($total_quantity); ?></span>
            </div>
          </div>

          <!-- Card 3 -->
          <div class="kpi_card">
            <div class="kpi_icon purple">
              <span class="material-symbols-outlined">payments</span>
            </div>
            <div class="kpi_info">
              <span class="kpi_title">Tổng giá trị</span>
              <span class="kpi_value"><?php echo number_format($total_val); ?>đ</span>
            </div>
          </div>

          <!-- Card 4 -->
          <div class="kpi_card">
            <div class="kpi_icon orange">
              <span class="material-symbols-outlined">warning</span>
            </div>
            <div class="kpi_info">
              <span class="kpi_title">Sắp hết hàng</span>
              <span class="kpi_value"><?php echo number_format($total_low); ?></span>
            </div>
          </div>
        </div>

        <div class="chart_grid">
          <div class="chart_card">
            <h3>Số lượng sản phẩm theo danh mục</h3>
            <div style="position: relative; height: 300px;">
              <canvas id="quantityChart"></canvas>
            </div>
          </div>
          <div class="chart_card">
            <h3>Giá trị tồn kho theo danh mục (triệu VNĐ)</h3>
            <div style="position: relative; height: 300px;">
              <canvas id="valueChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB KHO HÀNG -->
      <div id="content_khohang" class="tab_content">
        <h1 style="font-size: 24px; font-weight: 600; color: #0f172a; margin: 0 0 4px 0;">Kho hàng</h1>
        <p style="font-size: 14px; color: #64748b; margin: 0 0 24px 0;">Quản lý và theo dõi tồn kho theo thời gian thực</p>
        <div class="main_content_sort">
          <div class="search_field_wrapper">
            <label for="search_input_sort" class="search_label">Tìm kiếm</label>
            <div class="input_group">
              <button class="search_button">
                <span class="material-symbols-outlined">search</span>
              </button>
              <input id="search_input_sort" class="input_find" type="text" placeholder="Nhập sản phẩm...">
            </div>
          </div>
          <div class="search_field_wrapper">
            <label for="select_category" class="search_label">Danh mục</label>
            <div class="input_group">
              <select name="category" id="select_category">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($categories_list as $row): ?>
                  <option value="<?php echo htmlspecialchars($row['MaDM']); ?>"><?php echo htmlspecialchars($row['TenDM']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="search_field_wrapper">
            <label for="select_price" class="search_label">Giá</label>
            <div class="input_group">
              <select name="price_sort" id="select_price">
                <option value="">Mặc định</option>
                <option value="asc">Giá tăng dần</option>
                <option value="desc">Giá giảm dần</option>
              </select>
            </div>
          </div>
          <div class="search_field_wrapper">
            <label for="select_quantity" class="search_label">Số lượng hàng</label>
            <div class="input_group">
              <select name="qty_sort" id="select_quantity">
                <option value="">Mặc định</option>
                <option value="asc">Số lượng tăng dần</option>
                <option value="desc">Số lượng giảm dần</option>
              </select>
            </div>
          </div>
          <div class="search_field_wrapper">
            <label for="select_limit" class="search_label">Hiển thị</label>
            <div class="input_group">
              <select name="limit" id="select_limit">
                <option value="10">10 SP</option>
                <option value="20">20 SP</option>
                <option value="30">30 SP</option>
                <option value="40">40 SP</option>
                <option value="50">50 SP</option>
              </select>
            </div>
          </div>
          <div class="search_field_wrapper">
            <span class="search_label">Thao tác</span>
            <div style="display: flex; align-items: center; height: 38px; gap: 8px; flex-wrap: wrap;">
              <?php if ($is_admin): ?>
              <button class="add_product_button" id="btn_add_product" title="Thêm sản phẩm">
                <span class="material-symbols-outlined">add_box</span>
                <span>Thêm mới</span>
              </button>
              <button class="filter_button" id="btn_add_category" title="Thêm danh mục" style="background-color: #8b5cf6;">
                <span class="material-symbols-outlined">library_add</span>
                <span>Thêm danh mục</span>
              </button>
              <?php endif; ?>
              <?php if (canImportExport()): ?>
              <button class="filter_button" id="btn_import_stock" title="Nhập kho" style="background-color: #16a34a;">
                <span class="material-symbols-outlined">download</span>
                <span>Nhập kho</span>
              </button>
              <button class="filter_button" id="btn_export_stock" title="Xuất kho" style="background-color: #ea580c;">
                <span class="material-symbols-outlined">upload</span>
                <span>Xuất kho</span>
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Kết quả lọc sản phẩm -->
        <div id="filter_results_container" style="margin-top: 24px;">
          <div class="table_container">
            <table class="product_table">
              <thead>
                <tr>
                  <th style="width: 80px;">Mã SP</th>
                  <th>Tên sản phẩm</th>
                  <th>Danh mục</th>
                  <th style="width: 35%;">Mô tả</th>
                  <th>Giá bán</th>
                  <th>Số lượng</th>
                  <th style="width: 120px;">Trạng thái</th>
                  <?php if ($is_admin): ?>
                  <th style="width: 90px;">Thao tác</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody id="product_table_body">
                <!-- AJAX elements will render here -->
              </tbody>
            </table>
          </div>
          <div id="productPagination" style="padding:12px 0; display:flex; justify-content:center; gap:8px; flex-wrap:wrap; margin-top: 10px;"></div>
        </div>
      </div>

      <?php if (canImportExport()): ?>
      <!-- TAB LỊCH SỬ NHẬP/XUẤT KHO -->
      <div id="content_lichsu" class="tab_content">
        <h1 style="font-size: 24px; font-weight: 600; color: #0f172a; margin: 0 0 4px 0;">Lịch sử nhập / xuất kho</h1>
        <p style="font-size: 14px; color: #64748b; margin: 0 0 24px 0;">Xem danh sách các phiếu nhập kho và xuất kho đã thực hiện.</p>

        <!-- Tabs con: Nhập / Xuất -->
        <div style="display:flex; gap:8px; margin-bottom:20px;">
          <button class="filter_button history_tab_btn active" id="hist_tab_import" onclick="switchHistoryTab('import')" style="background-color:#16a34a;">
            <span class="material-symbols-outlined">download</span>
            <span>Phiếu nhập</span>
          </button>
          <button class="filter_button history_tab_btn" id="hist_tab_export" onclick="switchHistoryTab('export')" style="background-color:#ea580c; opacity:0.6;">
            <span class="material-symbols-outlined">upload</span>
            <span>Phiếu xuất</span>
          </button>
        </div>

        <!-- Bảng phiếu nhập -->
        <div id="history_import_panel">
          <div class="table_container">
            <table class="product_table">
              <thead>
                <tr>
                  <th style="width:70px;">Mã phiếu</th>
                  <th>Sản phẩm</th>
                  <th style="width:90px;">Số lượng</th>
                  <th>Ghi chú</th>
                  <th>Người tạo</th>
                  <th style="width:150px;">Ngày tạo</th>
                </tr>
              </thead>
              <tbody id="importHistoryBody">
                <tr><td colspan="6" class="table_loading">Đang tải...</td></tr>
              </tbody>
            </table>
          </div>
          <div id="importHistoryPagination" style="padding:12px 0; display:flex; justify-content:center; gap:8px; flex-wrap:wrap;"></div>
        </div>

        <!-- Bảng phiếu xuất (ẩn mặc định) -->
        <div id="history_export_panel" style="display:none;">
          <div class="table_container">
            <table class="product_table">
              <thead>
                <tr>
                  <th style="width:70px;">Mã phiếu</th>
                  <th>Sản phẩm</th>
                  <th style="width:90px;">Số lượng</th>
                  <th>Ghi chú</th>
                  <th>Người tạo</th>
                  <th style="width:150px;">Ngày tạo</th>
                </tr>
              </thead>
              <tbody id="exportHistoryBody">
                <tr><td colspan="6" class="table_loading">Đang tải...</td></tr>
              </tbody>
            </table>
          </div>
          <div id="exportHistoryPagination" style="padding:12px 0; display:flex; justify-content:center; gap:8px; flex-wrap:wrap;"></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($is_admin): ?>
      <!-- TAB QUẢN LÝ NGƯỜI DÙNG (chỉ admin) -->
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
            <button class="btn_primary" id="btnOpenCreateModal">
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
                  <th>Đăng nhập lần cuối</th>
                  <th>Người tạo</th>
                  <th style="width:100px;">Thao tác</th>
                </tr>
              </thead>
              <tbody id="usersTableBody">
                <tr><td colspan="6" class="table_loading">Đang tải...</td></tr>
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
    </div>
  </main>

  <!-- Modal tạo tài khoản -->
  <?php if ($is_admin): ?>
  <div class="modal_overlay" id="createUserModal">
    <div class="modal_card">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined">person_add</span>
          Tạo tài khoản mới
        </h3>
        <button class="modal_close" id="btnCloseModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body">
        <div class="form_group">
          <label for="new_username">Tên đăng nhập</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">alternate_email</span>
            <input type="text" id="new_username" class="form_input" placeholder="vd: nhanvien01">
          </div>
        </div>
        <div class="form_group">
          <label for="new_fullname">Họ và tên</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">badge</span>
            <input type="text" id="new_fullname" class="form_input" placeholder="vd: Nguyễn Văn A">
          </div>
        </div>
        <div class="form_group">
          <label for="new_password">Mật khẩu</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">lock</span>
            <input type="password" id="new_password" class="form_input" placeholder="Tối thiểu 6 ký tự">
          </div>
        </div>
        <div class="form_group">
          <label for="new_role">Vai trò</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">admin_panel_settings</span>
            <select id="new_role" class="form_input" style="cursor:pointer;">
              <option value="staff">Staff — Nhân viên kho</option>
              <option value="store_manager">CH Trưởng — Quản lý cửa hàng</option>
              <option value="admin">Admin — Quản trị viên</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal_footer">
        <button class="btn_secondary" id="btnCancelModal">Hủy</button>
        <button class="btn_primary" id="btnSubmitCreateUser">
          <span class="material-symbols-outlined">save</span>
          Tạo tài khoản
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal thêm sản phẩm -->
  <?php if ($is_admin): ?>
  <div class="modal_overlay" id="addProductModal">
    <div class="modal_card">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined">add_box</span>
          Thêm sản phẩm mới
        </h3>
        <button class="modal_close" id="btnCloseAddProductModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body">
        <div class="form_group">
          <label for="new_prod_name">Tên sản phẩm</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">shopping_bag</span>
            <input type="text" id="new_prod_name" class="form_input" placeholder="vd: Laptop ASUS Zenbook">
          </div>
        </div>
        <div class="form_group">
          <label for="new_prod_category">Danh mục</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">category</span>
            <select id="new_prod_category" class="form_input" style="cursor:pointer;">
              <option value="">-- Chọn danh mục --</option>
              <?php foreach ($categories_list as $row): ?>
                <option value="<?php echo htmlspecialchars($row['MaDM']); ?>"><?php echo htmlspecialchars($row['TenDM']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form_group">
          <label for="new_prod_price">Giá bán (VNĐ)</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">payments</span>
            <input type="number" id="new_prod_price" class="form_input" min="0" placeholder="vd: 15000000">
          </div>
        </div>
        <div class="form_group">
          <label for="new_prod_quantity">Số lượng tồn kho</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">inventory_2</span>
            <input type="number" id="new_prod_quantity" class="form_input" min="0" placeholder="vd: 20">
          </div>
        </div>
        <div class="form_group">
          <label for="new_prod_desc">Mô tả sản phẩm</label>
          <div class="form_input_wrapper" style="align-items: flex-start; padding: 6px 12px;">
            <span class="material-symbols-outlined form_icon" style="margin-top:6px;">description</span>
            <textarea id="new_prod_desc" class="form_input" rows="3" placeholder="Nhập mô tả..." style="resize:vertical; border:none; outline:none; background:transparent; width:100%; font-family:inherit;"></textarea>
          </div>
        </div>
      </div>
      <div class="modal_footer">
        <button class="btn_secondary" id="btnCancelAddProductModal">Hủy</button>
        <button class="btn_primary" id="btnSubmitAddProduct">
          <span class="material-symbols-outlined">save</span>
          Thêm sản phẩm
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal thêm danh mục -->
  <?php if ($is_admin): ?>
  <div class="modal_overlay" id="addCategoryModal">
    <div class="modal_card" style="width: 380px;">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined">library_add</span>
          Thêm danh mục mới
        </h3>
        <button class="modal_close" id="btnCloseAddCategoryModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body">
        <div class="form_group">
          <label for="new_cat_code">Mã danh mục (2–10 ký tự)</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">qr_code</span>
            <input type="text" id="new_cat_code" class="form_input" placeholder="vd: DIEN" maxlength="10" style="text-transform: uppercase;">
          </div>
        </div>
        <div class="form_group">
          <label for="new_cat_name">Tên danh mục</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">label</span>
            <input type="text" id="new_cat_name" class="form_input" placeholder="vd: Thiết bị điện tử">
          </div>
        </div>
      </div>
      <div class="modal_footer">
        <button class="btn_secondary" id="btnCancelAddCategoryModal">Hủy</button>
        <button class="btn_primary" id="btnSubmitAddCategory" style="background-color: #8b5cf6;">
          <span class="material-symbols-outlined">save</span>
          Thêm danh mục
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal sửa sản phẩm -->
  <?php if ($is_admin): ?>
  <div class="modal_overlay" id="editProductModal">
    <div class="modal_card">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined">edit</span>
          Sửa sản phẩm
        </h3>
        <button class="modal_close" id="btnCloseEditProductModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body">
        <input type="hidden" id="edit_prod_id">
        <div class="form_group">
          <label for="edit_prod_name">Tên sản phẩm</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">shopping_bag</span>
            <input type="text" id="edit_prod_name" class="form_input">
          </div>
        </div>
        <div class="form_group">
          <label for="edit_prod_category">Danh mục</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">category</span>
            <select id="edit_prod_category" class="form_input" style="cursor:pointer;">
              <option value="">-- Chọn danh mục --</option>
              <?php foreach ($categories_list as $row): ?>
                <option value="<?php echo htmlspecialchars($row['MaDM']); ?>"><?php echo htmlspecialchars($row['TenDM']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form_group">
          <label for="edit_prod_price">Giá bán (VNĐ)</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">payments</span>
            <input type="number" id="edit_prod_price" class="form_input" min="0">
          </div>
        </div>
        <div class="form_group">
          <label for="edit_prod_desc">Mô tả sản phẩm</label>
          <div class="form_input_wrapper" style="align-items: flex-start; padding: 6px 12px;">
            <span class="material-symbols-outlined form_icon" style="margin-top:6px;">description</span>
            <textarea id="edit_prod_desc" class="form_input" rows="3" style="resize:vertical; border:none; outline:none; background:transparent; width:100%; font-family:inherit;"></textarea>
          </div>
        </div>
      </div>
      <div class="modal_footer">
        <button class="btn_secondary" id="btnCancelEditProductModal">Hủy</button>
        <button class="btn_primary" id="btnSubmitEditProduct">
          <span class="material-symbols-outlined">save</span>
          Lưu thay đổi
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal nhập kho (Batch) -->
  <?php if (canImportExport()): ?>
  <div class="modal_overlay" id="importStockModal">
    <div class="modal_card" style="width: 680px; max-height: 90vh; display: flex; flex-direction: column;">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined" style="color:#16a34a;">download</span>
          Nhập kho
        </h3>
        <button class="modal_close" id="btnCloseImportModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body" style="overflow-y: auto; flex: 1;">
        <!-- Bảng danh sách hàng đã thêm vào phiếu -->
        <div id="importBatchTableWrapper" style="margin-bottom: 16px; display: none;">
          <label style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 8px; display: block;">
            <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle; margin-right:4px;">list_alt</span>
            Danh sách hàng nhập
          </label>
          <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
            <table class="data_table" style="margin: 0; font-size: 13px;">
              <thead>
                <tr>
                  <th style="width: 40px;">#</th>
                  <th>Sản phẩm</th>
                  <th style="width: 100px;">Số lượng</th>
                  <th>Ghi chú</th>
                  <th style="width: 50px;"></th>
                </tr>
              </thead>
              <tbody id="importBatchBody"></tbody>
            </table>
          </div>
        </div>

        <!-- Form thêm từng sản phẩm -->
        <div style="background: #f8fafc; border-radius: 10px; padding: 16px; border: 1px dashed #cbd5e1;">
          <label style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 10px; display: block;">
            <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle; margin-right:4px;">add_box</span>
            Thêm sản phẩm vào phiếu
          </label>
          <div class="form_group" style="margin-bottom: 10px;">
            <label for="import_product">Sản phẩm</label>
            <div class="product_combobox" id="import_combobox_wrapper">
              <div class="form_input_wrapper">
                <span class="material-symbols-outlined form_icon">inventory_2</span>
                <input type="text" id="import_product" class="form_input" placeholder="Gõ tên sản phẩm để tìm..." autocomplete="off">
                <input type="hidden" id="import_product_id">
                <span class="material-symbols-outlined combobox_clear" id="import_combobox_clear" style="display:none;cursor:pointer;font-size:18px;color:#94a3b8;">close</span>
              </div>
              <div class="combobox_dropdown" id="import_combobox_dropdown"></div>
            </div>
          </div>
          <div style="display: flex; gap: 12px;">
            <div class="form_group" style="margin-bottom: 10px; flex: 1;">
              <label for="import_quantity">Số lượng nhập</label>
              <div class="form_input_wrapper">
                <span class="material-symbols-outlined form_icon">add_circle</span>
                <input type="number" id="import_quantity" class="form_input" min="1" placeholder="Nhập số lượng...">
              </div>
            </div>
            <div class="form_group" style="margin-bottom: 10px; flex: 1.5;">
              <label for="import_note">Ghi chú</label>
              <div class="form_input_wrapper">
                <span class="material-symbols-outlined form_icon">description</span>
                <input type="text" id="import_note" class="form_input" placeholder="Lý do nhập kho...">
              </div>
            </div>
          </div>
          <button class="btn_primary" id="btnAddToBatch" type="button" style="background-color: #3b82f6; width: 100%; padding: 8px; font-size: 13px;">
            <span class="material-symbols-outlined" style="font-size: 18px;">playlist_add</span>
            Thêm vào phiếu
          </button>
        </div>
      </div>
      <div class="modal_footer">
        <button class="btn_secondary" id="btnCancelImportModal">Hủy</button>
        <button class="btn_primary" id="btnSubmitImport" style="background-color: #16a34a;" disabled>
          <span class="material-symbols-outlined">save</span>
          Xác nhận nhập kho (<span id="importBatchCount">0</span> sản phẩm)
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal xuất kho -->
  <?php if (canImportExport()): ?>
  <div class="modal_overlay" id="exportStockModal">
    <div class="modal_card" style="width: 680px; max-height: 90vh; display: flex; flex-direction: column;">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined" style="color:#ea580c;">upload</span>
          Xuất kho
        </h3>
        <button class="modal_close" id="btnCloseExportModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body" style="overflow-y: auto; flex: 1;">
        <!-- Bảng danh sách hàng đã thêm vào phiếu -->
        <div id="exportBatchTableWrapper" style="margin-bottom: 16px; display: none;">
          <label style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 8px; display: block;">
            <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle; margin-right:4px;">list_alt</span>
            Danh sách hàng xuất
          </label>
          <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
            <table class="data_table" style="margin: 0; font-size: 13px;">
              <thead>
                <tr>
                  <th style="width: 40px;">#</th>
                  <th>Sản phẩm</th>
                  <th style="width: 100px;">Số lượng</th>
                  <th>Ghi chú</th>
                  <th style="width: 50px;"></th>
                </tr>
              </thead>
              <tbody id="exportBatchBody"></tbody>
            </table>
          </div>
        </div>

        <!-- Form thêm từng sản phẩm -->
        <div style="background: #f8fafc; border-radius: 10px; padding: 16px; border: 1px dashed #cbd5e1;">
          <label style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 10px; display: block;">
            <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle; margin-right:4px;">add_box</span>
            Thêm sản phẩm vào phiếu
          </label>
          <div class="form_group" style="margin-bottom: 10px;">
            <label for="export_product">Sản phẩm</label>
            <div class="product_combobox" id="export_combobox_wrapper">
              <div class="form_input_wrapper">
                <span class="material-symbols-outlined form_icon">inventory_2</span>
                <input type="text" id="export_product" class="form_input" placeholder="Gõ tên sản phẩm để tìm..." autocomplete="off">
                <input type="hidden" id="export_product_id">
                <span class="material-symbols-outlined combobox_clear" id="export_combobox_clear" style="display:none;cursor:pointer;font-size:18px;color:#94a3b8;">close</span>
              </div>
              <div class="combobox_dropdown" id="export_combobox_dropdown"></div>
            </div>
          </div>
          <div id="export_stock_info" style="display:none; background:#fef3c7; border:1px solid #fbbf24; border-radius:8px; padding:10px 14px; margin-bottom:12px; font-size:13px; color:#92400e;">
            Tồn kho hiện tại: <strong id="export_current_stock">0</strong>
          </div>
          <div style="display: flex; gap: 12px;">
            <div class="form_group" style="margin-bottom: 10px; flex: 1;">
              <label for="export_quantity">Số lượng xuất</label>
              <div class="form_input_wrapper">
                <span class="material-symbols-outlined form_icon">remove_circle</span>
                <input type="number" id="export_quantity" class="form_input" min="1" placeholder="Nhập số lượng...">
              </div>
            </div>
            <div class="form_group" style="margin-bottom: 10px; flex: 1.5;">
              <label for="export_note">Ghi chú</label>
              <div class="form_input_wrapper">
                <span class="material-symbols-outlined form_icon">description</span>
                <input type="text" id="export_note" class="form_input" placeholder="Lý do xuất kho...">
              </div>
            </div>
          </div>
          <button class="btn_primary" id="btnAddToExportBatch" type="button" style="background-color: #3b82f6; width: 100%; padding: 8px; font-size: 13px;">
            <span class="material-symbols-outlined" style="font-size: 18px;">playlist_add</span>
            Thêm vào phiếu
          </button>
        </div>
      </div>
      <div class="modal_footer">
        <button class="btn_secondary" id="btnCancelExportModal">Hủy</button>
        <button class="btn_primary" id="btnSubmitExport" style="background-color: #ea580c;" disabled>
          <span class="material-symbols-outlined">save</span>
          Xác nhận xuất kho (<span id="exportBatchCount">0</span> sản phẩm)
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal cấp quyền cho staff -->
  <?php if ($is_admin): ?>
  <div class="modal_overlay" id="permissionsModal">
    <div class="modal_card" style="width: 380px;">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined">admin_panel_settings</span>
          Cấp quyền
        </h3>
        <button class="modal_close" id="btnClosePermissionsModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body">
        <input type="hidden" id="perm_user_id">
        <p style="font-size:14px; color:#475569; margin-bottom:16px;">
          Cấp quyền cho: <strong id="perm_user_name"></strong>
        </p>
        <div class="form_group">
          <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            <input type="checkbox" id="perm_import_export" style="width:18px; height:18px; cursor:pointer;">
            <span>Cho phép nhập/xuất kho</span>
          </label>
        </div>
      </div>
      <div class="modal_footer">
        <button class="btn_secondary" id="btnCancelPermissionsModal">Hủy</button>
        <button class="btn_primary" id="btnSubmitPermissions">
          <span class="material-symbols-outlined">save</span>
          Lưu quyền
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal chi tiết phiếu nhập/xuất -->
  <?php if (canImportExport()): ?>
  <div class="modal_overlay" id="receiptDetailModal">
    <div class="modal_card" style="width: 640px; max-height: 90vh; display: flex; flex-direction: column;">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined">receipt_long</span>
          <span id="receiptDetailTitle">Chi tiết phiếu</span>
        </h3>
        <button class="modal_close" id="btnCloseReceiptDetailModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body" style="overflow-y: auto; flex: 1;">
        <div id="receiptDetailInfo" style="margin-bottom: 16px;"></div>
        <div id="receiptDetailItems"></div>
      </div>
      <div class="modal_footer">
        <button class="btn_secondary" id="btnCloseReceiptDetailModalFooter">Đóng</button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Toast notifications -->
  <div class="toast_container" id="toastContainer"></div>

  <footer></footer>

  <script>
    // ─── Tab switching ────────────────────────────────────────────────────────
    document.querySelectorAll('.sidebar_menu .menu_item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.sidebar_menu .menu_item').forEach(el => el.classList.remove('active'));
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab');
            document.querySelectorAll('.tab_content').forEach(content => {
                content.classList.remove('active_tab');
            });
            const activeTab = document.getElementById('content_' + tabId);
            if (activeTab) activeTab.classList.add('active_tab');

            if (tabId === 'khohang') fetchFilteredProducts();
            if (tabId === 'lichsu') switchHistoryTab('import');
            if (tabId === 'caidat') {
                loadUsers();
                loadSessions();
            }
        });
    });

    // ─── User Dropdown ────────────────────────────────────────────────────────
    const dropdownTrigger = document.getElementById('userDropdownTrigger');
    const dropdownMenu    = document.getElementById('userDropdownMenu');

    if (dropdownTrigger && dropdownMenu) {
        dropdownTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('open');
        });
        document.addEventListener('click', function() {
            dropdownMenu.classList.remove('open');
        });
    }

    let productCurrentPage = 1;

    // ─── AJAX Product Filtering ───────────────────────────────────────────────
    function fetchFilteredProducts(page = 1) {
        productCurrentPage = page;
        const searchVal   = document.getElementById('search_input_sort').value;
        const categoryVal = document.getElementById('select_category').value;
        const priceSortVal = document.getElementById('select_price').value;
        const qtySortVal   = document.getElementById('select_quantity').value;
        const limitVal     = document.getElementById('select_limit')?.value || 10;

        const params = new URLSearchParams({
            search: searchVal, category: categoryVal,
            price_sort: priceSortVal, qty_sort: qtySortVal,
            limit: limitVal, page: page
        });

        const isAdm = <?php echo $is_admin ? 'true' : 'false'; ?>;
        const colspan = isAdm ? 8 : 7;
        const tbody = document.getElementById('product_table_body');
        tbody.innerHTML = '<tr><td colspan="' + colspan + '" style="text-align: center; padding: 32px; color: #64748b;">Đang tải dữ liệu...</td></tr>';

        fetch('filter_products.php?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => {
                const totalPages = parseInt(r.headers.get('X-Total-Pages') || '1');
                renderProductPagination(totalPages, page);
                return r.text();
            })
            .then(html => {
                // filter_products.php trả về HTML fragment — nếu bị redirect về
                // login page thì HTML sẽ chứa thẻ <html>, không phải <tr>.
                // Nếu session hết, auth.php trả JSON { redirect: '...' } thay vì HTML
                if (html.trim().startsWith('{')) {
                    try {
                        const json = JSON.parse(html);
                        if (json.redirect) { window.location.href = json.redirect; return; }
                    } catch(e) {}
                }
                if (html.trim().toLowerCase().startsWith('<!doctype') || html.includes('<html')) {
                    window.location.href = 'login.php?expired=1';
                    return;
                }
                tbody.innerHTML = html;
            })
            .catch(err => {
                console.error('Error:', err);
                tbody.innerHTML = '<tr><td colspan="' + colspan + '" style="text-align: center; padding: 32px; color: #ef4444;">Đã xảy ra lỗi khi tải dữ liệu. Vui lòng thử lại.</td></tr>';
            });
    }



    function renderProductPagination(totalPages, currentPage) {
        const container = document.getElementById('productPagination');
        if (!container) return;
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }
        let html = '';
        for (let i = 1; i <= totalPages; i++) {
            html += `<button class="filter_button" style="padding:4px 10px;font-size:12px;${i === currentPage ? 'opacity:1;' : 'opacity:0.5;'}" onclick="fetchFilteredProducts(${i})">${i}</button> `;
        }
        container.innerHTML = html;
    }

    // Auto-filter khi thay đổi dropdown (category, price, quantity, limit)
    ['select_category', 'select_price', 'select_quantity', 'select_limit'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => fetchFilteredProducts(1));
    });

    // [IMP-06] Debounce helper — trì hoãn gọi hàm sau khi người dùng ngừng gõ
    function debounce(fn, delay) {
        let timer;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // Tìm kiếm real-time khi gõ vào ô search (debounce 400ms)
    const searchInput = document.getElementById('search_input_sort');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => fetchFilteredProducts(1), 400));
    }

    // ─── Session-aware fetch wrapper ──────────────────────────────────────────
    // Gửi header X-Requested-With để PHP (auth.php) nhận biết là AJAX request.
    // Khi session hết hạn/bị kick, PHP trả 401 + JSON { redirect: '...' }
    // Khi server lỗi (5xx/4xx), ném Error để .catch() của caller bắt được.
    // [SEC-01] Tự động đính kèm X-CSRF-Token vào mọi POST/PUT/DELETE request
    //          để ngăn chặn CSRF attack theo Double-Submit Header pattern.
    const _csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function apiFetch(url, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        const defaultHeaders = { 'X-Requested-With': 'XMLHttpRequest' };

        // Gắn CSRF token vào tất cả request thay đổi dữ liệu
        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            defaultHeaders['X-CSRF-Token'] = _csrfToken;
        }

        options.headers = Object.assign(defaultHeaders, options.headers || {});
        return fetch(url, options)
            .then(r => {
                // [FIX-02] Kiểm tra HTTP status trước khi parse JSON
                if (r.status === 401) {
                    // Session hết hạn hoặc bị kick — parse JSON để lấy redirect URL
                    return r.json().then(data => {
                        window.location.href = data.redirect || 'login.php?expired=1';
                        return new Promise(() => {}); // dừng chain
                    });
                }
                if (!r.ok) {
                    // Lỗi HTTP khác (403, 500...) — ném lỗi để .catch() xử lý
                    return Promise.reject(new Error('Lỗi máy chủ: HTTP ' + r.status));
                }
                return r.json();
            })
            .then(data => {
                // Fallback: server trả 200 nhưng có field redirect (backward compat)
                if (data && data.redirect) {
                    window.location.href = data.redirect;
                    return new Promise(() => {});
                }
                return data;
            });
    }

    // ─── Toast ────────────────────────────────────────────────────────────────
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast ' + type;
        const icon = type === 'success' ? 'check_circle' : 'error';
        toast.innerHTML = `<span class="material-symbols-outlined" style="font-size:18px;">${icon}</span><span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'toast_out 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // ─── Helper: load product combobox (searchable dropdown) ────────────────
    const _comboData = {};

    function loadProductDropdown(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const prefix    = inputId.replace('_product', '') + '_combobox';
        const hidden    = document.getElementById(inputId + '_id');
        const dropdown  = document.getElementById(prefix + '_dropdown');
        const clearBtn  = document.getElementById(prefix + '_clear');
        const wrapper   = document.getElementById(prefix + '_wrapper');

        input.value = '';
        if (hidden) hidden.value = '';
        if (dropdown) { dropdown.innerHTML = ''; dropdown.style.display = 'none'; }
        if (clearBtn) clearBtn.style.display = 'none';
        input.placeholder = 'Đang tải danh sách...';

        // Only fetch once per inputId
        if (_comboData[inputId]) {
            input.placeholder = 'Gõ tên sản phẩm để tìm...';
            setupComboEvents(inputId);
            return;
        }

        fetch('filter_products.php?search=&category=&price_sort=&qty_sort=&limit=all', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString('<table>' + html + '</table>', 'text/html');
            const rows = doc.querySelectorAll('tr');
            const products = [];
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 2) {
                    const maSp = cells[0].textContent.trim();
                    const tenSp = cells[1].textContent.trim();
                    if (maSp && !isNaN(maSp)) {
                        products.push({ id: maSp, name: tenSp, label: tenSp + ' (Mã: ' + maSp + ')' });
                    }
                }
            });
            _comboData[inputId] = products;
            input.placeholder = 'Gõ tên sản phẩm để tìm...';
            setupComboEvents(inputId);
        })
        .catch(() => { input.placeholder = 'Lỗi tải danh sách sản phẩm'; });
    }

    function setupComboEvents(inputId) {
        const input   = document.getElementById(inputId);
        const prefix  = inputId.replace('_product', '') + '_combobox';
        const hidden  = document.getElementById(inputId + '_id');
        const dropdown = document.getElementById(prefix + '_dropdown');
        const clearBtn = document.getElementById(prefix + '_clear');
        const wrapper  = document.getElementById(prefix + '_wrapper');
        const products = _comboData[inputId] || [];

        // Remove old listeners by replacing element references via clone
        const oldInput = input;
        if (oldInput._comboBound) return; // already set up

        function filterAndShow(query) {
            const q = query.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const filtered = q === '' ? products : products.filter(p => {
                const name = p.name.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                return name.includes(q) || p.id.includes(q);
            });
            renderComboDropdown(dropdown, filtered);
        }

        input.addEventListener('input', function() {
            hidden.value = '';
            if (clearBtn) clearBtn.style.display = 'none';
            filterAndShow(this.value);
        });

        input.addEventListener('focus', function() {
            filterAndShow(this.value);
        });

        dropdown.addEventListener('click', function(e) {
            const item = e.target.closest('.combobox_item');
            if (!item) return;
            hidden.value = item.dataset.id;
            input.value = item.dataset.name;
            dropdown.style.display = 'none';
            if (clearBtn) clearBtn.style.display = 'block';
            input.dispatchEvent(new Event('productSelected'));
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                input.value = '';
                hidden.value = '';
                this.style.display = 'none';
                input.focus();
                var infoDiv = document.getElementById('export_stock_info');
                if (infoDiv) infoDiv.style.display = 'none';
            });
        }

        document.addEventListener('mousedown', function(e) {
            if (wrapper && !wrapper.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        input.addEventListener('keydown', function(e) {
            var items = dropdown.querySelectorAll('.combobox_item');
            var active = dropdown.querySelector('.combobox_item.active');
            var idx = Array.from(items).indexOf(active);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (active) active.classList.remove('active');
                idx = idx < items.length - 1 ? idx + 1 : 0;
                if (items[idx]) items[idx].classList.add('active');
                if (items[idx]) items[idx].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (active) active.classList.remove('active');
                idx = idx > 0 ? idx - 1 : items.length - 1;
                if (items[idx]) items[idx].classList.add('active');
                if (items[idx]) items[idx].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (active) active.click();
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
            }
        });

        input._comboBound = true;
    }

    function renderComboDropdown(dropdown, list) {
        if (list.length === 0) {
            dropdown.innerHTML = '<div class="combobox_empty">Không tìm thấy sản phẩm</div>';
            dropdown.style.display = 'block';
            return;
        }
        dropdown.innerHTML = list.map(p =>
            '<div class="combobox_item" data-id="' + p.id + '" data-name="' + escapeHtml(p.label) + '">' + escapeHtml(p.label) + '</div>'
        ).join('');
        dropdown.style.display = 'block';
    }

    // ─── Edit Product ────────────────────────────────────────────────────────
    function openEditProduct(maSp) {
        apiFetch('admin/edit_product.php?action=get&id=' + maSp)
            .then(data => {
                if (!data.success) { showToast(data.message, 'error'); return; }
                const p = data.product;
                document.getElementById('edit_prod_id').value = p.MaSP;
                document.getElementById('edit_prod_name').value = p.TenSP;
                document.getElementById('edit_prod_category').value = p.DanhMuc;
                document.getElementById('edit_prod_price').value = p.Gia;
                document.getElementById('edit_prod_desc').value = p.MoTa || '';
                document.getElementById('editProductModal').classList.add('open');
            })
            .catch(() => showToast('Không thể tải thông tin sản phẩm.', 'error'));
    }

    function toggleProductActive(maSp, newActive) {
        const label = newActive == 1 ? 'khôi phục' : 'ẩn';
        if (!confirm(`Bạn có chắc muốn ${label} sản phẩm này?`)) return;
        const fd = new FormData();
        fd.append('ma_sp', maSp);
        fd.append('is_active', newActive);
        apiFetch('admin/edit_product.php?action=toggle_active', { method: 'POST', body: fd })
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) fetchFilteredProducts();
            })
            .catch(() => showToast('Lỗi kết nối máy chủ.', 'error'));
    }

    // ─── Edit Product Modal ──────────────────────────────────────────────────
    const editProdModal = document.getElementById('editProductModal');
    if (editProdModal) {
        document.getElementById('btnCloseEditProductModal')?.addEventListener('click', () => editProdModal.classList.remove('open'));
        document.getElementById('btnCancelEditProductModal')?.addEventListener('click', () => editProdModal.classList.remove('open'));
        editProdModal.addEventListener('click', e => { if (e.target === editProdModal) editProdModal.classList.remove('open'); });

        document.getElementById('btnSubmitEditProduct')?.addEventListener('click', function() {
            const fd = new FormData();
            fd.append('ma_sp', document.getElementById('edit_prod_id').value);
            fd.append('ten_sp', document.getElementById('edit_prod_name').value.trim());
            fd.append('danhmuc', document.getElementById('edit_prod_category').value);
            fd.append('gia', document.getElementById('edit_prod_price').value);
            fd.append('mota', document.getElementById('edit_prod_desc').value.trim());

            if (fd.get('ten_sp') === '' || fd.get('danhmuc') === '') {
                showToast('Vui lòng nhập đầy đủ thông tin.', 'error'); return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...';

            apiFetch('admin/edit_product.php?action=update', { method: 'POST', body: fd })
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Lưu thay đổi';
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) { editProdModal.classList.remove('open'); fetchFilteredProducts(); }
                })
                .catch(() => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Lưu thay đổi';
                    showToast('Lỗi kết nối máy chủ.', 'error');
                });
        });
    }

    // ─── Import Stock Modal (Batch) ─────────────────────────────────────────
    const importModal = document.getElementById('importStockModal');
    let importBatchItems = [];

    function renderImportBatchTable() {
        const tbody = document.getElementById('importBatchBody');
        const wrapper = document.getElementById('importBatchTableWrapper');
        const countSpan = document.getElementById('importBatchCount');
        const submitBtn = document.getElementById('btnSubmitImport');
        if (!tbody || !wrapper) return;

        countSpan.textContent = importBatchItems.length;
        submitBtn.disabled = importBatchItems.length === 0;

        if (importBatchItems.length === 0) {
            wrapper.style.display = 'none';
            tbody.innerHTML = '';
            return;
        }
        wrapper.style.display = '';
        tbody.innerHTML = importBatchItems.map((item, idx) => `<tr>
            <td style="text-align:center; color:#64748b;">${idx + 1}</td>
            <td><span class="product_name">${escapeHtml(item.productName)}</span></td>
            <td style="text-align:center;"><strong>${number_format(item.quantity)}</strong></td>
            <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#64748b;" title="${escapeHtml(item.note)}">${escapeHtml(item.note || '—')}</td>
            <td style="text-align:center;">
                <button onclick="removeImportBatchItem(${idx})" style="background:none; border:none; cursor:pointer; color:#ef4444; padding:4px;" title="Xóa khỏi phiếu">
                    <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
                </button>
            </td>
        </tr>`).join('');
    }

    window.removeImportBatchItem = function(idx) {
        importBatchItems.splice(idx, 1);
        renderImportBatchTable();
    };

    if (importModal) {
        document.getElementById('btn_import_stock')?.addEventListener('click', () => {
            loadProductDropdown('import_product');
            document.getElementById('import_quantity').value = '';
            document.getElementById('import_note').value = '';
            importBatchItems = [];
            renderImportBatchTable();
            importModal.classList.add('open');
        });
        document.getElementById('btnCloseImportModal')?.addEventListener('click', () => importModal.classList.remove('open'));
        document.getElementById('btnCancelImportModal')?.addEventListener('click', () => importModal.classList.remove('open'));
        importModal.addEventListener('click', e => { if (e.target === importModal) importModal.classList.remove('open'); });

        // Nút "Thêm vào phiếu"
        document.getElementById('btnAddToBatch')?.addEventListener('click', function() {
            const productId = document.getElementById('import_product_id').value;
            const productName = document.getElementById('import_product').value;
            const quantity = parseInt(document.getElementById('import_quantity').value, 10);
            const note = document.getElementById('import_note').value.trim();

            if (!productId) { showToast('Vui lòng chọn sản phẩm.', 'error'); return; }
            if (!quantity || quantity <= 0) { showToast('Số lượng nhập phải lớn hơn 0.', 'error'); return; }

            // Kiểm tra sản phẩm đã có trong phiếu chưa
            const existing = importBatchItems.find(item => item.productId === productId);
            if (existing) {
                existing.quantity += quantity;
                if (note) existing.note = note;
                showToast(`Đã cộng thêm ${number_format(quantity)} vào "${productName}".`, 'success');
            } else {
                importBatchItems.push({ productId, productName, quantity, note });
                showToast(`Đã thêm "${productName}" vào phiếu.`, 'success');
            }

            renderImportBatchTable();
            // Reset form nhập
            document.getElementById('import_quantity').value = '';
            document.getElementById('import_note').value = '';
            document.getElementById('import_product').value = '';
            document.getElementById('import_product_id').value = '';
            document.getElementById('import_combobox_clear').style.display = 'none';
        });

        // Nút "Xác nhận nhập kho" — gửi tất cả
        document.getElementById('btnSubmitImport')?.addEventListener('click', function() {
            if (importBatchItems.length === 0) {
                showToast('Chưa có sản phẩm nào trong phiếu.', 'error'); return;
            }

            const fd = new FormData();
            fd.append('items', JSON.stringify(importBatchItems.map(item => ({
                san_pham: item.productId,
                so_luong: item.quantity,
                ghi_chu: item.note
            }))));

            this.disabled = true;
            this.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang xử lý...';

            apiFetch('admin/import_stock.php?action=create_batch', { method: 'POST', body: fd })
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Xác nhận nhập kho (<span id="importBatchCount">' + importBatchItems.length + '</span> sản phẩm)';
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        importBatchItems = [];
                        renderImportBatchTable();
                        importModal.classList.remove('open');
                        fetchFilteredProducts();
                    }
                })
                .catch(() => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Xác nhận nhập kho (<span id="importBatchCount">' + importBatchItems.length + '</span> sản phẩm)';
                    showToast('Lỗi kết nối máy chủ.', 'error');
                });
        });
    }

    // ─── Export Stock Modal (Batch) ──────────────────────────────────────────
    const exportModal = document.getElementById('exportStockModal');
    let exportBatchItems = [];

    function renderExportBatchTable() {
        const tbody = document.getElementById('exportBatchBody');
        const wrapper = document.getElementById('exportBatchTableWrapper');
        const countSpan = document.getElementById('exportBatchCount');
        const submitBtn = document.getElementById('btnSubmitExport');
        if (!tbody || !wrapper) return;

        countSpan.textContent = exportBatchItems.length;
        submitBtn.disabled = exportBatchItems.length === 0;

        if (exportBatchItems.length === 0) {
            wrapper.style.display = 'none';
            tbody.innerHTML = '';
            return;
        }
        wrapper.style.display = '';
        tbody.innerHTML = exportBatchItems.map((item, idx) => `<tr>
            <td style="text-align:center; color:#64748b;">${idx + 1}</td>
            <td><span class="product_name">${escapeHtml(item.productName)}</span></td>
            <td style="text-align:center;"><strong>${number_format(item.quantity)}</strong></td>
            <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#64748b;" title="${escapeHtml(item.note)}">${escapeHtml(item.note || '—')}</td>
            <td style="text-align:center;">
                <button onclick="removeExportBatchItem(${idx})" style="background:none; border:none; cursor:pointer; color:#ef4444; padding:4px;" title="Xóa khỏi phiếu">
                    <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
                </button>
            </td>
        </tr>`).join('');
    }

    window.removeExportBatchItem = function(idx) {
        exportBatchItems.splice(idx, 1);
        renderExportBatchTable();
    };

    if (exportModal) {
        document.getElementById('btn_export_stock')?.addEventListener('click', () => {
            loadProductDropdown('export_product');
            document.getElementById('export_quantity').value = '';
            document.getElementById('export_note').value = '';
            document.getElementById('export_stock_info').style.display = 'none';
            exportBatchItems = [];
            renderExportBatchTable();
            exportModal.classList.add('open');
        });
        document.getElementById('btnCloseExportModal')?.addEventListener('click', () => exportModal.classList.remove('open'));
        document.getElementById('btnCancelExportModal')?.addEventListener('click', () => exportModal.classList.remove('open'));
        exportModal.addEventListener('click', e => { if (e.target === exportModal) exportModal.classList.remove('open'); });

        // Hiển thị tồn kho khi chọn SP
        document.getElementById('export_product')?.addEventListener('productSelected', function() {
            const spId = document.getElementById('export_product_id').value;
            const infoDiv = document.getElementById('export_stock_info');
            if (!spId) { infoDiv.style.display = 'none'; return; }
            apiFetch('admin/edit_product.php?action=get&id=' + spId)
                .then(data => {
                    if (data.success) {
                        document.getElementById('export_current_stock').textContent = data.product.SoLuong;
                        infoDiv.style.display = 'block';
                        // Lưu trữ số lượng tối đa vào data attribute
                        document.getElementById('export_quantity').max = data.product.SoLuong;
                    }
                });
        });

        // Nút "Thêm vào phiếu"
        document.getElementById('btnAddToExportBatch')?.addEventListener('click', function() {
            const productId = document.getElementById('export_product_id').value;
            const productName = document.getElementById('export_product').value;
            const quantityInput = document.getElementById('export_quantity');
            const quantity = parseInt(quantityInput.value, 10);
            const note = document.getElementById('export_note').value.trim();
            const currentStock = parseInt(quantityInput.max || 0, 10);

            if (!productId) { showToast('Vui lòng chọn sản phẩm.', 'error'); return; }
            if (!quantity || quantity <= 0) { showToast('Số lượng xuất phải lớn hơn 0.', 'error'); return; }

            // Kiểm tra số lượng tổng có vượt tồn kho hiện tại không
            let totalQuantity = quantity;
            const existing = exportBatchItems.find(item => item.productId === productId);
            if (existing) {
                totalQuantity += existing.quantity;
            }

            if (totalQuantity > currentStock) {
                showToast(`Số lượng yêu cầu (${totalQuantity}) vượt quá tồn kho hiện tại (${currentStock}).`, 'error');
                return;
            }

            if (existing) {
                existing.quantity += quantity;
                if (note) existing.note = note;
                showToast(`Đã cộng thêm ${number_format(quantity)} vào "${productName}".`, 'success');
            } else {
                exportBatchItems.push({ productId, productName, quantity, note });
                showToast(`Đã thêm "${productName}" vào phiếu.`, 'success');
            }

            renderExportBatchTable();
            // Reset form nhập
            document.getElementById('export_quantity').value = '';
            document.getElementById('export_note').value = '';
            document.getElementById('export_product').value = '';
            document.getElementById('export_product_id').value = '';
            document.getElementById('export_combobox_clear').style.display = 'none';
            document.getElementById('export_stock_info').style.display = 'none';
        });

        // Nút "Xác nhận xuất kho"
        document.getElementById('btnSubmitExport')?.addEventListener('click', function() {
            if (exportBatchItems.length === 0) {
                showToast('Chưa có sản phẩm nào trong phiếu.', 'error'); return;
            }

            const fd = new FormData();
            fd.append('items', JSON.stringify(exportBatchItems.map(item => ({
                san_pham: item.productId,
                so_luong: item.quantity,
                ghi_chu: item.note
            }))));

            this.disabled = true;
            this.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang xử lý...';

            apiFetch('admin/export_stock.php?action=create_batch', { method: 'POST', body: fd })
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Xác nhận xuất kho (<span id="exportBatchCount">' + exportBatchItems.length + '</span> sản phẩm)';
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        exportBatchItems = [];
                        renderExportBatchTable();
                        exportModal.classList.remove('open');
                        fetchFilteredProducts();
                    }
                })
                .catch(() => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Xác nhận xuất kho (<span id="exportBatchCount">' + exportBatchItems.length + '</span> sản phẩm)';
                    showToast('Lỗi kết nối máy chủ.', 'error');
                });
        });
    }

    // ─── Import History (tab-based) ──────────────────────────────────────────
    function loadImportHistory(page = 1) {
        const tbody = document.getElementById('importHistoryBody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Đang tải...</td></tr>';

        apiFetch('admin/import_stock.php?action=list&page=' + page)
            .then(data => {
                if (!data.success || data.records.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6"><div class="empty_state"><span class="material-symbols-outlined">inventory_2</span><p>Chưa có phiếu nhập kho nào.</p></div></td></tr>';
                    document.getElementById('importHistoryPagination').innerHTML = '';
                    return;
                }
                tbody.innerHTML = data.records.map(r => {
                    const date = new Date(r.ngay_tao).toLocaleString('vi-VN');
                    const safeMaPhieu = escapeHtml(r.ma_phieu).replace(/'/g, "\\'");
                    return `<tr style="cursor:pointer;" onclick="openReceiptDetail('import', '${safeMaPhieu}')">
                        <td>${r.ma_phieu}</td>
                        <td><span class="product_name">${escapeHtml(r.TenSP)}</span></td>
                        <td><strong>${number_format(r.so_luong)}</strong></td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escapeHtml(r.ghi_chu || '')}">${escapeHtml(r.ghi_chu || '—')}</td>
                        <td>${escapeHtml(r.nguoi_tao_name)}</td>
                        <td style="font-size:13px;">${date}</td>
                    </tr>`;
                }).join('');

                const totalPages = Math.ceil(data.total / data.per_page);
                if (totalPages > 1) {
                    let pag = '';
                    for (let i = 1; i <= totalPages; i++) {
                        pag += `<button class="filter_button" style="padding:4px 10px;font-size:12px;${i === page ? 'opacity:1;' : 'opacity:0.5;'}" onclick="loadImportHistory(${i})">${i}</button> `;
                    }
                    document.getElementById('importHistoryPagination').innerHTML = pag;
                } else {
                    document.getElementById('importHistoryPagination').innerHTML = '';
                }
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Lỗi tải dữ liệu.</td></tr>';
            });
    }

    // ─── Export History (tab-based) ──────────────────────────────────────────
    function loadExportHistory(page = 1) {
        const tbody = document.getElementById('exportHistoryBody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Đang tải...</td></tr>';

        apiFetch('admin/export_stock.php?action=list&page=' + page)
            .then(data => {
                if (!data.success || data.records.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6"><div class="empty_state"><span class="material-symbols-outlined">inventory_2</span><p>Chưa có phiếu xuất kho nào.</p></div></td></tr>';
                    document.getElementById('exportHistoryPagination').innerHTML = '';
                    return;
                }
                tbody.innerHTML = data.records.map(r => {
                    const date = new Date(r.ngay_tao).toLocaleString('vi-VN');
                    const safeMaPhieu = escapeHtml(r.ma_phieu).replace(/'/g, "\\'");
                    return `<tr style="cursor:pointer;" onclick="openReceiptDetail('export', '${safeMaPhieu}')">
                        <td>${r.ma_phieu}</td>
                        <td><span class="product_name">${escapeHtml(r.TenSP)}</span></td>
                        <td><strong>${number_format(r.so_luong)}</strong></td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escapeHtml(r.ghi_chu || '')}">${escapeHtml(r.ghi_chu || '—')}</td>
                        <td>${escapeHtml(r.nguoi_tao_name)}</td>
                        <td style="font-size:13px;">${date}</td>
                    </tr>`;
                }).join('');

                const totalPages = Math.ceil(data.total / data.per_page);
                if (totalPages > 1) {
                    let pag = '';
                    for (let i = 1; i <= totalPages; i++) {
                        pag += `<button class="filter_button" style="padding:4px 10px;font-size:12px;${i === page ? 'opacity:1;' : 'opacity:0.5;'}" onclick="loadExportHistory(${i})">${i}</button> `;
                    }
                    document.getElementById('exportHistoryPagination').innerHTML = pag;
                } else {
                    document.getElementById('exportHistoryPagination').innerHTML = '';
                }
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Lỗi tải dữ liệu.</td></tr>';
            });
    }

    // ─── Switch history sub-tab (Nhập / Xuất) ──────────────────────────────
    function switchHistoryTab(type) {
        const importBtn = document.getElementById('hist_tab_import');
        const exportBtn = document.getElementById('hist_tab_export');
        const importPanel = document.getElementById('history_import_panel');
        const exportPanel = document.getElementById('history_export_panel');

        if (type === 'import') {
            importBtn.style.opacity = '1';
            exportBtn.style.opacity = '0.6';
            importPanel.style.display = '';
            exportPanel.style.display = 'none';
            loadImportHistory(1);
        } else {
            importBtn.style.opacity = '0.6';
            exportBtn.style.opacity = '1';
            importPanel.style.display = 'none';
            exportPanel.style.display = '';
            loadExportHistory(1);
        }
    }

    // ─── Receipt Detail Modal ──────────────────────────────────────────────
    const receiptDetailModal = document.getElementById('receiptDetailModal');
    if (receiptDetailModal) {
        document.getElementById('btnCloseReceiptDetailModal')?.addEventListener('click', () => receiptDetailModal.classList.remove('open'));
        document.getElementById('btnCloseReceiptDetailModalFooter')?.addEventListener('click', () => receiptDetailModal.classList.remove('open'));
        receiptDetailModal.addEventListener('click', e => { if (e.target === receiptDetailModal) receiptDetailModal.classList.remove('open'); });
    }

    function openReceiptDetail(type, maPhieu) {
        if (!receiptDetailModal) return;
        const infoDiv = document.getElementById('receiptDetailInfo');
        const itemsDiv = document.getElementById('receiptDetailItems');
        const titleSpan = document.getElementById('receiptDetailTitle');

        titleSpan.textContent = type === 'import' ? 'Chi tiết phiếu nhập' : 'Chi tiết phiếu xuất';
        infoDiv.innerHTML = '<p style="color:#64748b;">Đang tải...</p>';
        itemsDiv.innerHTML = '';

        const endpoint = type === 'import' ? 'admin/import_stock.php' : 'admin/export_stock.php';
        apiFetch(endpoint + '?action=detail&ma_phieu=' + encodeURIComponent(maPhieu))
            .then(data => {
                if (!data.success || !data.items || data.items.length === 0) {
                    infoDiv.innerHTML = '<p style="color:#dc2626;">Không tìm thấy phiếu.</p>';
                    return;
                }
                const ngayTao = new Date(data.ngay_tao).toLocaleString('vi-VN');
                const icon = type === 'import' ? 'download' : 'upload';
                const color = type === 'import' ? '#16a34a' : '#ea580c';
                const label = type === 'import' ? 'Nhập kho' : 'Xuất kho';

                infoDiv.innerHTML = `
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                        <span class="material-symbols-outlined" style="color:${color};font-size:28px;">${icon}</span>
                        <div>
                            <div style="font-size:16px;font-weight:600;color:#0f172a;">${label} — ${escapeHtml(data.ma_phieu)}</div>
                            <div style="font-size:13px;color:#64748b;">${ngayTao}</div>
                        </div>
                    </div>
                    <div style="display:flex;gap:24px;font-size:14px;color:#475569;">
                        <div><strong>Người tạo:</strong> ${escapeHtml(data.nguoi_tao)}</div>
                        <div><strong>Số mặt hàng:</strong> ${data.items.length}</div>
                    </div>
                `;

                let tongTien = 0;
                let rows = data.items.map((item, idx) => {
                    const gia = parseInt(item.Gia) || 0;
                    const thanhTien = gia * parseInt(item.so_luong);
                    tongTien += thanhTien;
                    const ghiChu = item.ghi_chu || '—';
                    return `
                        <tr>
                            <td style="text-align:center;">${idx + 1}</td>
                            <td><span class="product_name">${escapeHtml(item.TenSP)}</span></td>
                            <td style="text-align:right;">${number_format(gia)}đ</td>
                            <td style="text-align:center;"><strong>${number_format(item.so_luong)}</strong></td>
                            <td style="text-align:right;font-weight:600;">${number_format(thanhTien)}đ</td>
                            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escapeHtml(ghiChu)}">${escapeHtml(ghiChu)}</td>
                        </tr>
                    `;
                }).join('');

                itemsDiv.innerHTML = `
                    <table class="product_table" style="margin:0;">
                        <thead>
                            <tr>
                                <th style="width:40px;text-align:center;">#</th>
                                <th>Sản phẩm</th>
                                <th style="width:100px;text-align:right;">Đơn giá</th>
                                <th style="width:80px;text-align:center;">Số lượng</th>
                                <th style="width:110px;text-align:right;">Thành tiền</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align:right;font-weight:600;color:#0f172a;">Tổng cộng:</td>
                                <td style="text-align:right;font-weight:700;color:${color};font-size:15px;">${number_format(tongTien)}đ</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                `;

                receiptDetailModal.classList.add('open');
            })
            .catch(() => {
                infoDiv.innerHTML = '<p style="color:#dc2626;">Lỗi kết nối máy chủ.</p>';
            });
    }

    // ─── Permissions Modal ───────────────────────────────────────────────────
    function openPermissionsModal(userId, userName, currentVal) {
        document.getElementById('perm_user_id').value = userId;
        document.getElementById('perm_user_name').textContent = userName;
        document.getElementById('perm_import_export').checked = currentVal == 1;
        document.getElementById('permissionsModal').classList.add('open');
    }

    const permModal = document.getElementById('permissionsModal');
    if (permModal) {
        document.getElementById('btnClosePermissionsModal')?.addEventListener('click', () => permModal.classList.remove('open'));
        document.getElementById('btnCancelPermissionsModal')?.addEventListener('click', () => permModal.classList.remove('open'));
        permModal.addEventListener('click', e => { if (e.target === permModal) permModal.classList.remove('open'); });

        document.getElementById('btnSubmitPermissions')?.addEventListener('click', function() {
            const fd = new FormData();
            fd.append('id', document.getElementById('perm_user_id').value);
            fd.append('allow_import_export', document.getElementById('perm_import_export').checked ? 1 : 0);

            this.disabled = true;
            this.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...';

            apiFetch('admin/users.php?action=update_permissions', { method: 'POST', body: fd })
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Lưu quyền';
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) { permModal.classList.remove('open'); loadUsers(); }
                })
                .catch(() => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Lưu quyền';
                    showToast('Lỗi kết nối máy chủ.', 'error');
                });
        });
    }

    <?php if ($is_admin): ?>
    // ─── Load Users ───────────────────────────────────────────────────────────
    function loadUsers() {
        const tbody = document.getElementById('usersTableBody');
        tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Đang tải...</td></tr>';

        apiFetch('admin/users.php?action=list')
            .then(data => {
                if (!data.success) { tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Lỗi tải dữ liệu.</td></tr>'; return; }
                if (data.users.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6"><div class="empty_state"><span class="material-symbols-outlined">group_off</span><p>Chưa có tài khoản nào.</p></div></td></tr>';
                    return;
                }
                tbody.innerHTML = data.users.map(u => {
                    // [FIX-01] Escape tất cả giá trị string từ server trước khi chèn vào innerHTML
                    const safeName      = escapeHtml(u.full_name);
                    const safeUsername  = escapeHtml(u.username);
                    const safeCreatedBy = escapeHtml(u.created_by_name || '');

                    const initials    = safeName.replace(/&amp;|&lt;|&gt;|&quot;|&#039;/g, '').split(' ').map(w => w[0]).filter(Boolean).slice(-2).join('').toUpperCase();
                    const statusBadge = u.is_active == 1
                        ? '<span class="status_badge active">● Hoạt động</span>'
                        : '<span class="status_badge inactive">● Vô hiệu hóa</span>';
                    const roleBadge   = u.role === 'admin'
                        ? '<span class="user_role_badge admin">Admin</span>'
                        : u.role === 'store_manager'
                        ? '<span class="user_role_badge store_manager">Quản lý kho</span>'
                        : '<span class="user_role_badge staff">Nhân viên</span>';
                    const lastLogin   = u.last_login ? new Date(u.last_login).toLocaleString('vi-VN') : '— Chưa đăng nhập';
                    const createdBy   = safeCreatedBy || '— Hệ thống';
                    const isSelf      = u.id == <?php echo $current_user['id']; ?>;

                    const toggleTitle  = u.is_active == 1 ? 'Vô hiệu hóa' : 'Kích hoạt';
                    const toggleIcon   = u.is_active == 1 ? 'block' : 'check_circle';
                    const toggleStatus = u.is_active == 1 ? 0 : 1;

                    // Dùng data-attribute để truyền tên thay vì nhúng thẳng vào onclick
                    const actions = isSelf
                        ? '<span style="font-size:12px;color:#94a3b8;">Tài khoản của bạn</span>'
                        : `<div class="action_group">
                            <button class="btn_icon" title="${toggleTitle}" onclick="toggleUser(${u.id}, ${toggleStatus})">
                                <span class="material-symbols-outlined">${toggleIcon}</span>
                            </button>
                            ${u.role === 'staff' ? `<button class="btn_icon" title="Quyền nhập/xuất kho" onclick="openPermissionsModal(${u.id}, '${safeName}', ${u.allow_import_export || 0})">
                                <span class="material-symbols-outlined">admin_panel_settings</span>
                            </button>` : ''}
                            ${u.role !== 'admin' ? `<button class="btn_icon danger" title="Xóa tài khoản" data-uid="${u.id}" data-uname="${safeName}" onclick="deleteUser(this.dataset.uid, this.dataset.uname)">
                                <span class="material-symbols-outlined">delete</span>
                            </button>` : ''}
                           </div>`;

                    return `<tr>
                        <td>
                          <div class="user_info_cell">
                            <div class="user_avatar">${initials}</div>
                            <div>
                              <div class="u_fullname">${safeName}</div>
                              <div class="u_username">@${safeUsername}</div>
                            </div>
                          </div>
                        </td>
                        <td>${roleBadge}</td>
                        <td>${statusBadge}</td>
                        <td style="font-size:13px;">${lastLogin}</td>
                        <td style="font-size:13px;">${createdBy}</td>
                        <td>${actions}</td>
                    </tr>`;
                }).join('');
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Lỗi kết nối.</td></tr>';
            });
    }

    // ─── Toggle user active status ────────────────────────────────────────────
    function toggleUser(id, newStatus) {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('is_active', newStatus);
        apiFetch('admin/users.php?action=toggle', { method: 'POST', body: fd })
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) loadUsers();
            })
            .catch(err => showToast(err.message || 'Lỗi kết nối máy chủ.', 'error'));
    }

    // ─── Delete user ──────────────────────────────────────────────────────────
    function deleteUser(id, name) {
        if (!confirm(`Bạn có chắc muốn xóa tài khoản "${name}"? Hành động này không thể hoàn tác.`)) return;
        const fd = new FormData();
        fd.append('id', id);
        apiFetch('admin/users.php?action=delete', { method: 'POST', body: fd })
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) loadUsers();
            })
            .catch(err => showToast(err.message || 'Lỗi kết nối máy chủ.', 'error'));
    }

    // ─── Load active sessions ─────────────────────────────────────────────────
    function loadSessions() {
        const list = document.getElementById('sessionList');
        list.innerHTML = '<div class="table_loading">Đang tải...</div>';

        apiFetch('admin/users.php?action=sessions')
            .then(data => {
                if (!data.success || data.sessions.length === 0) {
                    list.innerHTML = '<div class="empty_state"><span class="material-symbols-outlined">sensors_off</span><p>Không có phiên hoạt động nào.</p></div>';
                    return;
                }
                list.innerHTML = data.sessions.map(s => {
                    // [FIX-01] Escape tất cả giá trị string từ server trước khi chèn vào innerHTML
                    const safeName      = escapeHtml(s.full_name);
                    const safeUsername  = escapeHtml(s.username);
                    const safeIp        = escapeHtml(s.ip_address);

                    const currentTag  = s.is_current ? '<span class="current_tag">Phiên này</span>' : '';
                    const loginTime   = new Date(s.created_at).toLocaleString('vi-VN');
                    const expireTime  = new Date(s.expires_at).toLocaleString('vi-VN');
                    const kickBtn     = !s.is_current
                        ? `<button class="btn_icon danger" title="Kick user" onclick="kickUser(${s.user_id})">
                               <span class="material-symbols-outlined">logout</span>
                           </button>`
                        : '';

                    return `<div class="session_item ${s.is_current ? 'current_session' : ''}">
                        <div class="session_info">
                            <div class="session_user">
                                ${safeName} (@${safeUsername}) ${currentTag}
                                <span class="user_role_badge ${s.role}" style="margin-left:6px;">${s.role === 'admin' ? 'Admin' : 'Staff'}</span>
                            </div>
                            <div class="session_meta">
                                IP: ${safeIp} &nbsp;|&nbsp; Đăng nhập: ${loginTime} &nbsp;|&nbsp; Hết hạn: ${expireTime}
                            </div>
                        </div>
                        ${kickBtn}
                    </div>`;
                }).join('');
            })
            .catch(() => {
                list.innerHTML = '<div class="empty_state"><span class="material-symbols-outlined">error</span><p>Không thể tải danh sách phiên.</p></div>';
            });
    }

    // ─── Kick user ────────────────────────────────────────────────────────────
    function kickUser(userId) {
        if (!confirm('Đăng xuất người dùng này khỏi tất cả phiên?')) return;
        const fd = new FormData();
        fd.append('user_id', userId);
        apiFetch('admin/users.php?action=kick', { method: 'POST', body: fd })
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) loadSessions();
            })
            .catch(err => showToast(err.message || 'Lỗi kết nối máy chủ.', 'error'));
    }

    // ─── Modal: Tạo tài khoản ─────────────────────────────────────────────────
    const createModal    = document.getElementById('createUserModal');
    const btnOpenModal   = document.getElementById('btnOpenCreateModal');
    const btnCloseModal  = document.getElementById('btnCloseModal');
    const btnCancelModal = document.getElementById('btnCancelModal');
    const btnSubmit      = document.getElementById('btnSubmitCreateUser');

    function openModal()  { createModal.classList.add('open'); document.getElementById('new_username').focus(); }
    function closeModal() {
        createModal.classList.remove('open');
        document.getElementById('new_username').value  = '';
        document.getElementById('new_fullname').value  = '';
        document.getElementById('new_password').value  = '';
        document.getElementById('new_role').value      = 'staff';
    }

    btnOpenModal.addEventListener('click', openModal);
    btnCloseModal.addEventListener('click', closeModal);
    btnCancelModal.addEventListener('click', closeModal);
    createModal.addEventListener('click', e => { if (e.target === createModal) closeModal(); });

    btnSubmit.addEventListener('click', function() {
        const fd = new FormData();
        fd.append('username',  document.getElementById('new_username').value.trim());
        fd.append('full_name', document.getElementById('new_fullname').value.trim());
        fd.append('password',  document.getElementById('new_password').value);
        fd.append('role',      document.getElementById('new_role').value);

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang tạo...';

        apiFetch('admin/users.php?action=create', { method: 'POST', body: fd })
            .then(data => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<span class="material-symbols-outlined">save</span> Tạo tài khoản';
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) { closeModal(); loadUsers(); }
            })
            .catch(() => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<span class="material-symbols-outlined">save</span> Tạo tài khoản';
                showToast('Lỗi kết nối máy chủ.', 'error');
            });
    });
    // ─── Modal: Thêm sản phẩm mới ─────────────────────────────────────────────
    const addProdModal         = document.getElementById('addProductModal');
    const btnOpenProdModal     = document.getElementById('btn_add_product');
    const btnCloseProdModal    = document.getElementById('btnCloseAddProductModal');
    const btnCancelProdModal   = document.getElementById('btnCancelAddProductModal');
    const btnSubmitProd        = document.getElementById('btnSubmitAddProduct');

    function openAddProdModal() {
        if (addProdModal) {
            addProdModal.classList.add('open');
            document.getElementById('new_prod_name').focus();
        }
    }

    function closeAddProdModal() {
        if (addProdModal) {
            addProdModal.classList.remove('open');
            document.getElementById('new_prod_name').value = '';
            document.getElementById('new_prod_category').value = '';
            document.getElementById('new_prod_price').value = '';
            document.getElementById('new_prod_quantity').value = '';
            document.getElementById('new_prod_desc').value = '';
        }
    }

    if (btnOpenProdModal)   btnOpenProdModal.addEventListener('click', openAddProdModal);
    if (btnCloseProdModal)  btnCloseProdModal.addEventListener('click', closeAddProdModal);
    if (btnCancelProdModal) btnCancelProdModal.addEventListener('click', closeAddProdModal);
    if (addProdModal) {
        addProdModal.addEventListener('click', e => {
            if (e.target === addProdModal) closeAddProdModal();
        });
    }

    if (btnSubmitProd) {
        btnSubmitProd.addEventListener('click', function() {
            const name     = document.getElementById('new_prod_name').value.trim();
            const category = document.getElementById('new_prod_category').value.trim();
            const price    = document.getElementById('new_prod_price').value.trim();
            const quantity = document.getElementById('new_prod_quantity').value.trim();
            const desc     = document.getElementById('new_prod_desc').value.trim();

            if (name === '' || category === '' || price === '' || quantity === '') {
                showToast('Vui lòng nhập đầy đủ thông tin bắt buộc.', 'error');
                return;
            }

            // [IMP-08] Validate kiểu dữ liệu trước khi gửi lên server
            const priceNum = parseFloat(price);
            const qtyNum   = parseInt(quantity, 10);

            if (isNaN(priceNum) || priceNum < 0) {
                showToast('Giá bán phải là số không âm.', 'error');
                return;
            }
            if (isNaN(qtyNum) || qtyNum < 0 || !Number.isInteger(qtyNum)) {
                showToast('Số lượng phải là số nguyên không âm.', 'error');
                return;
            }

            const fd = new FormData();
            fd.append('ten_sp', name);
            fd.append('danhmuc', category);
            fd.append('gia', price);
            fd.append('so_luong', quantity);
            fd.append('mota', desc);

            btnSubmitProd.disabled = true;
            btnSubmitProd.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...';

            apiFetch('admin/add_product.php', { method: 'POST', body: fd })
                .then(data => {
                    btnSubmitProd.disabled = false;
                    btnSubmitProd.innerHTML = '<span class="material-symbols-outlined">save</span> Thêm sản phẩm';
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        closeAddProdModal();
                        fetchFilteredProducts();
                    }
                })
                .catch(() => {
                    btnSubmitProd.disabled = false;
                    btnSubmitProd.innerHTML = '<span class="material-symbols-outlined">save</span> Thêm sản phẩm';
                    showToast('Lỗi kết nối máy chủ.', 'error');
                });
        });
    }
    // ─── Modal: Thêm danh mục mới ─────────────────────────────────────────────
    const addCatModal          = document.getElementById('addCategoryModal');
    const btnOpenCatModal      = document.getElementById('btn_add_category');
    const btnCloseCatModal     = document.getElementById('btnCloseAddCategoryModal');
    const btnCancelCatModal    = document.getElementById('btnCancelAddCategoryModal');
    const btnSubmitCat         = document.getElementById('btnSubmitAddCategory');

    function openAddCatModal() {
        if (addCatModal) {
            addCatModal.classList.add('open');
            document.getElementById('new_cat_code').focus();
        }
    }

    function closeAddCatModal() {
        if (addCatModal) {
            addCatModal.classList.remove('open');
            document.getElementById('new_cat_code').value = '';
            document.getElementById('new_cat_name').value = '';
        }
    }

    if (btnOpenCatModal)   btnOpenCatModal.addEventListener('click', openAddCatModal);
    if (btnCloseCatModal)  btnCloseCatModal.addEventListener('click', closeAddCatModal);
    if (btnCancelCatModal) btnCancelCatModal.addEventListener('click', closeAddCatModal);
    if (addCatModal) {
        addCatModal.addEventListener('click', e => {
            if (e.target === addCatModal) closeAddCatModal();
        });
    }

    // Tự động chuyển mã danh mục thành chữ in hoa
    const catCodeInput = document.getElementById('new_cat_code');
    if (catCodeInput) {
        catCodeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }

    // Helper escape HTML chống XSS
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Helper number format
    function number_format(num) {
        return parseInt(num).toLocaleString('vi-VN');
    }

    // Cập nhật tất cả các dropdown danh mục trên giao diện
    function updateCategoryDropdowns(categories) {
        // 1. Dropdown bộ lọc ở trang chính
        const filterSelect = document.getElementById('select_category');
        if (filterSelect) {
            const currentFilterVal = filterSelect.value;
            let optionsHtml = '<option value="">Tất cả danh mục</option>';
            categories.forEach(c => {
                optionsHtml += `<option value="${escapeHtml(c.MaDM)}">${escapeHtml(c.TenDM)}</option>`;
            });
            filterSelect.innerHTML = optionsHtml;
            filterSelect.value = currentFilterVal;
        }

        // 2. Dropdown danh mục ở Modal Thêm sản phẩm
        const prodSelect = document.getElementById('new_prod_category');
        if (prodSelect) {
            let optionsHtml = '<option value="">-- Chọn danh mục --</option>';
            categories.forEach(c => {
                optionsHtml += `<option value="${escapeHtml(c.MaDM)}">${escapeHtml(c.TenDM)}</option>`;
            });
            prodSelect.innerHTML = optionsHtml;
        }
    }

    if (btnSubmitCat) {
        btnSubmitCat.addEventListener('click', function() {
            const code = document.getElementById('new_cat_code').value.trim();
            const name = document.getElementById('new_cat_name').value.trim();

            if (code === '' || name === '') {
                showToast('Vui lòng nhập đầy đủ thông tin.', 'error');
                return;
            }

            if (code.length < 2 || code.length > 10) {
                showToast('Mã danh mục phải từ 2 đến 10 ký tự.', 'error');
                return;
            }

            const fd = new FormData();
            fd.append('ma_dm', code);
            fd.append('ten_dm', name);

            btnSubmitCat.disabled = true;
            btnSubmitCat.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...';

            apiFetch('admin/add_category.php', { method: 'POST', body: fd })
                .then(data => {
                    btnSubmitCat.disabled = false;
                    btnSubmitCat.innerHTML = '<span class="material-symbols-outlined">save</span> Thêm danh mục';
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        closeAddCatModal();
                        updateCategoryDropdowns(data.categories);
                    }
                })
                .catch(() => {
                    btnSubmitCat.disabled = false;
                    btnSubmitCat.innerHTML = '<span class="material-symbols-outlined">save</span> Thêm danh mục';
                    showToast('Lỗi kết nối máy chủ.', 'error');
                });
        });
    }

    document.getElementById('btnRefreshSessions').addEventListener('click', loadSessions);

    // Load users & sessions khi vào tab caidat
    <?php endif; ?>

    // ─── Charts ───────────────────────────────────────────────────────────────
    const chart1Labels = <?php echo json_encode($chart1_labels); ?>;
    const chart1Data   = <?php echo json_encode($chart1_data); ?>;
    const chart2Labels = <?php echo json_encode($chart2_labels); ?>;
    const chart2Data   = <?php echo json_encode($chart2_data); ?>;
    const chart2DataMillion = chart2Data.map(val => (val / 1000000).toFixed(2));

    const ctxQty = document.getElementById('quantityChart').getContext('2d');
    new Chart(ctxQty, {
        type: 'bar',
        data: {
            labels: chart1Labels,
            datasets: [{
                label: 'Số lượng sản phẩm',
                data: chart1Data,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: '#3b82f6',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });

    const ctxVal = document.getElementById('valueChart').getContext('2d');
    new Chart(ctxVal, {
        type: 'doughnut',
        data: {
            labels: chart2Labels,
            datasets: [{
                label: 'Giá trị (Triệu VNĐ)',
                data: chart2DataMillion,
                backgroundColor: ['#3b82f6','#10b981','#8b5cf6','#f59e0b','#ec4899','#06b6d4'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { boxWidth: 12, font: { family: "'Inter', sans-serif" } }
                }
            }
        }
    });
  </script>
</body>
<?php if ($conn) $conn->close(); ?>
</html>
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
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Quản Lí Kho — Nhóm 10</title>
  <meta name="description" content="Hệ thống quản lý kho hàng, theo dõi tồn kho theo thời gian thực.">
  <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
  <header>
    <div class="header_left">
      <span class="material-symbols-outlined logo_icon">inventory_2</span>
      <h1>Quản Lí Kho</h1>
    </div>
    <div class="header_right">
      <div class="display_right">
        <button class="search_button">
          <span class="material-symbols-outlined">search</span>
        </button>
        <input class="input_find" type="text" placeholder="Tìm kiếm nhanh...">
      </div>
      <button class="action_button" title="Thông báo">
        <span class="material-symbols-outlined">notifications</span>
      </button>

      <!-- User dropdown -->
      <div class="user_dropdown_wrapper" id="userDropdownWrapper">
        <button class="user_dropdown_trigger" id="userDropdownTrigger" title="Tài khoản của bạn">
          <span class="material-symbols-outlined">account_circle</span>
          <span class="user_name_display"><?php echo htmlspecialchars($current_user['name']); ?></span>
          <span class="user_role_badge <?php echo $current_user['role']; ?>">
            <?php echo $current_user['role'] === 'admin' ? 'Admin' : 'Staff'; ?>
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
                <?php
                if ($conn) {
                    $sql = "SELECT MaDM, TenDM FROM danhmuc";
                    if ($result = $conn->query($sql)) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['MaDM']) . '">' . htmlspecialchars($row['TenDM']) . '</option>';
                        }
                    }
                }
                ?>
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
            <span class="search_label">Thao tác</span>
            <div style="display: flex; align-items: center; height: 38px; gap: 8px;">
              <button class="filter_button" id="btn_filter" title="Lọc sản phẩm">
                <span class="material-symbols-outlined">filter_list</span>
                <span>Lọc</span>
              </button>
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
                </tr>
              </thead>
              <tbody id="product_table_body">
                <!-- AJAX elements will render here -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

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
            <button class="btn_secondary" id="btnRefreshSessions">
              <span class="material-symbols-outlined">refresh</span>
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
        <button class="modal_close" id="btnCloseModal">
          <span class="material-symbols-outlined">close</span>
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
        <button class="modal_close" id="btnCloseAddProductModal">
          <span class="material-symbols-outlined">close</span>
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
              <?php
              if ($conn) {
                  $sql = "SELECT MaDM, TenDM FROM danhmuc";
                  if ($result = $conn->query($sql)) {
                      while ($row = $result->fetch_assoc()) {
                          echo '<option value="' . htmlspecialchars($row['MaDM']) . '">' . htmlspecialchars($row['TenDM']) . '</option>';
                      }
                  }
              }
              ?>
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
        <button class="modal_close" id="btnCloseAddCategoryModal">
          <span class="material-symbols-outlined">close</span>
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

    // ─── AJAX Product Filtering ───────────────────────────────────────────────
    function fetchFilteredProducts() {
        const searchVal   = document.getElementById('search_input_sort').value;
        const categoryVal = document.getElementById('select_category').value;
        const priceSortVal = document.getElementById('select_price').value;
        const qtySortVal   = document.getElementById('select_quantity').value;

        const params = new URLSearchParams({
            search: searchVal, category: categoryVal,
            price_sort: priceSortVal, qty_sort: qtySortVal
        });

        const tbody = document.getElementById('product_table_body');
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 32px; color: #64748b;">Đang tải dữ liệu...</td></tr>';

        fetch('filter_products.php?' + params.toString())
            .then(r => r.text())
            .then(html => { tbody.innerHTML = html; })
            .catch(err => {
                console.error('Error:', err);
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 32px; color: #ef4444;">Đã xảy ra lỗi khi tải dữ liệu. Vui lòng thử lại.</td></tr>';
            });
    }

    const btnFilter = document.getElementById('btn_filter');
    if (btnFilter) {
        btnFilter.addEventListener('click', e => { e.preventDefault(); fetchFilteredProducts(); });
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

    <?php if ($is_admin): ?>
    // ─── Load Users ───────────────────────────────────────────────────────────
    function loadUsers() {
        const tbody = document.getElementById('usersTableBody');
        tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Đang tải...</td></tr>';

        fetch('admin/users.php?action=list')
            .then(r => r.json())
            .then(data => {
                if (!data.success) { tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Lỗi tải dữ liệu.</td></tr>'; return; }
                if (data.users.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6"><div class="empty_state"><span class="material-symbols-outlined">group_off</span><p>Chưa có tài khoản nào.</p></div></td></tr>';
                    return;
                }
                tbody.innerHTML = data.users.map(u => {
                    const initials    = u.full_name.split(' ').map(w => w[0]).slice(-2).join('').toUpperCase();
                    const statusBadge = u.is_active == 1
                        ? '<span class="status_badge active">● Hoạt động</span>'
                        : '<span class="status_badge inactive">● Vô hiệu hóa</span>';
                    const roleBadge   = u.role === 'admin'
                        ? '<span class="user_role_badge admin">Admin</span>'
                        : '<span class="user_role_badge staff">Staff</span>';
                    const lastLogin   = u.last_login ? new Date(u.last_login).toLocaleString('vi-VN') : '— Chưa đăng nhập';
                    const createdBy   = u.created_by_name || '— Hệ thống';
                    const isSelf      = u.id == <?php echo $current_user['id']; ?>;

                    const toggleTitle  = u.is_active == 1 ? 'Vô hiệu hóa' : 'Kích hoạt';
                    const toggleIcon   = u.is_active == 1 ? 'block' : 'check_circle';
                    const toggleStatus = u.is_active == 1 ? 0 : 1;

                    const actions = isSelf
                        ? '<span style="font-size:12px;color:#94a3b8;">Tài khoản của bạn</span>'
                        : `<div class="action_group">
                            <button class="btn_icon" title="${toggleTitle}" onclick="toggleUser(${u.id}, ${toggleStatus})">
                                <span class="material-symbols-outlined">${toggleIcon}</span>
                            </button>
                            ${u.role !== 'admin' ? `<button class="btn_icon danger" title="Xóa tài khoản" onclick="deleteUser(${u.id}, '${u.full_name.replace(/'/g,"\\'")}')">
                                <span class="material-symbols-outlined">delete</span>
                            </button>` : ''}
                           </div>`;

                    return `<tr>
                        <td>
                          <div class="user_info_cell">
                            <div class="user_avatar">${initials}</div>
                            <div>
                              <div class="u_fullname">${u.full_name}</div>
                              <div class="u_username">@${u.username}</div>
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
        fetch('admin/users.php?action=toggle', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) loadUsers();
            });
    }

    // ─── Delete user ──────────────────────────────────────────────────────────
    function deleteUser(id, name) {
        if (!confirm(`Bạn có chắc muốn xóa tài khoản "${name}"? Hành động này không thể hoàn tác.`)) return;
        const fd = new FormData();
        fd.append('id', id);
        fetch('admin/users.php?action=delete', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) loadUsers();
            });
    }

    // ─── Load active sessions ─────────────────────────────────────────────────
    function loadSessions() {
        const list = document.getElementById('sessionList');
        list.innerHTML = '<div class="table_loading">Đang tải...</div>';

        fetch('admin/users.php?action=sessions')
            .then(r => r.json())
            .then(data => {
                if (!data.success || data.sessions.length === 0) {
                    list.innerHTML = '<div class="empty_state"><span class="material-symbols-outlined">sensors_off</span><p>Không có phiên hoạt động nào.</p></div>';
                    return;
                }
                list.innerHTML = data.sessions.map(s => {
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
                                ${s.full_name} (@${s.username}) ${currentTag}
                                <span class="user_role_badge ${s.role}" style="margin-left:6px;">${s.role === 'admin' ? 'Admin' : 'Staff'}</span>
                            </div>
                            <div class="session_meta">
                                IP: ${s.ip_address} &nbsp;|&nbsp; Đăng nhập: ${loginTime} &nbsp;|&nbsp; Hết hạn: ${expireTime}
                            </div>
                        </div>
                        ${kickBtn}
                    </div>`;
                }).join('');
            });
    }

    // ─── Kick user ────────────────────────────────────────────────────────────
    function kickUser(userId) {
        if (!confirm('Đăng xuất người dùng này khỏi tất cả phiên?')) return;
        const fd = new FormData();
        fd.append('user_id', userId);
        fetch('admin/users.php?action=kick', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) loadSessions();
            });
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

        fetch('admin/users.php?action=create', { method: 'POST', body: fd })
            .then(r => r.json())
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

            const fd = new FormData();
            fd.append('ten_sp', name);
            fd.append('danhmuc', category);
            fd.append('gia', price);
            fd.append('so_luong', quantity);
            fd.append('mota', desc);

            btnSubmitProd.disabled = true;
            btnSubmitProd.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...';

            fetch('admin/add_product.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    btnSubmitProd.disabled = false;
                    btnSubmitProd.innerHTML = '<span class="material-symbols-outlined">save</span> Thêm sản phẩm';
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        closeAddProdModal();
                        fetchFilteredProducts(); // Reload product list automatically
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

            fetch('admin/add_category.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    btnSubmitCat.disabled = false;
                    btnSubmitCat.innerHTML = '<span class="material-symbols-outlined">save</span> Thêm danh mục';
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        closeAddCatModal();
                        updateCategoryDropdowns(data.categories); // Cập nhật dropdowns tức thì
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
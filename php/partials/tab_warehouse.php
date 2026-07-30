      <?php
      // Tab quản lý kho hàng
      $is_admin         = $is_admin         ?? isAdmin();
      $is_manager = $is_manager ?? isManager();
      $categories_list  = $categories_list  ?? [];
      ?>
      <div id="content_warehouse" class="tab_content">
        <h1 class="page_title">Kho hàng</h1>
        <p class="page_subtitle">Quản lý và theo dõi tồn kho theo thời gian thực</p>
        <div class="toolbar_wrap">
          <div class="toolbar_group toolbar_filters">
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
                  <?php echo renderCategoryOptions($categories_list); ?>
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
            <div class="search_field_wrapper action_buttons_wrapper clear_btn_wrapper filter-active">
              <button id="btnClearProductFilter" class="filter_button" onclick="resetProductFilter()">
                <span class="material-symbols-outlined" style="font-size:18px;">filter_list_off</span>
                <span>Xóa lọc</span>
              </button>
            </div>
          </div>
          <?php if ($is_admin || $is_manager || canImportExport()): ?>
          <div class="toolbar_group toolbar_actions">
            <div class="action_buttons_group">
              <?php if ($is_admin || $is_manager): ?>
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
          <?php endif; ?>
        </div>

        <!-- Bảng sản phẩm -->
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
                  <?php if (canViewProducts()): ?>
                  <th style="width: 90px;">Thao tác</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody id="product_table_body">
                  <!-- AJAX sẽ render dữ liệu vào đây -->
              </tbody>
            </table>
          </div>
          <div id="productPagination" class="pagination_wrap"></div>
        </div>
      </div>

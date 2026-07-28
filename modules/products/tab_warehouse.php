      <?php
      $is_admin         = $is_admin         ?? isAdmin();
      $is_store_manager = $is_store_manager ?? isStoreManager();
      $categories_list  = $categories_list  ?? [];
      ?>
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
          <div class="search_field_wrapper action_buttons_wrapper">
            <span class="search_label">Thao tác</span>
            <div class="action_buttons_group">
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
                  <?php if ($is_admin || $is_store_manager): ?>
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

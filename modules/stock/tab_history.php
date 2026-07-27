      <?php if (canImportExport()): ?>
      <!-- TAB LỊCH SỬ NHẬP/XUẤT KHO -->
      <div id="content_lichsu" class="tab_content">
        <h1 style="font-size: 24px; font-weight: 600; color: #0f172a; margin: 0 0 4px 0;">Lịch sử nhập / xuất kho</h1>
        <p style="font-size: 14px; color: #64748b; margin: 0 0 24px 0;">Xem danh sách các phiếu nhập kho và xuất kho đã thực hiện.</p>

        <!-- Tabs con: Nhập / Xuất -->
        <div style="display:flex; gap:8px; margin-bottom:20px;">
          <button class="filter_button history_tab_btn active" id="hist_tab_import" onclick="switchHistoryTab('import')">
            <span class="material-symbols-outlined">download</span>
            <span>Phiếu nhập</span>
          </button>
          <button class="filter_button history_tab_btn" id="hist_tab_export" onclick="switchHistoryTab('export')">
            <span class="material-symbols-outlined">upload</span>
            <span>Phiếu xuất</span>
          </button>
        </div>

        <!-- Bộ lọc tìm kiếm -->
        <?php
        $today = date('Y-m-d');
        $users_list = [];
        $max_price = 0;
        if ($conn) {
            $res = $conn->query("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name ASC");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $users_list[] = $row;
                }
            }
            $res2 = $conn->query("SELECT MAX(Gia) as max_price FROM sanpham");
            if ($res2) {
                $max_price = (int)($res2->fetch_assoc()['max_price'] ?? 0);
            }
        }
        ?>
        <div id="historyFilterBar" class="main_content_sort" style="margin-bottom:20px;">
          <div class="search_field_wrapper" style="flex:1; min-width:200px;">
            <label class="search_label">Tìm sản phẩm</label>
            <div class="input_group" style="width:100%;">
              <span class="material-symbols-outlined" style="font-size:18px; color:#97a5b8;">search</span>
              <input type="text" id="hist_search_product" class="input_find" placeholder="Tên sản phẩm..." style="width:100%;">
            </div>
          </div>
          <div class="search_field_wrapper">
            <label class="search_label">Từ ngày</label>
            <div class="input_group">
              <input type="date" id="hist_date_from" class="input_find" max="<?php echo $today; ?>" style="width:160px;">
            </div>
          </div>
          <div class="search_field_wrapper">
            <label class="search_label">Đến ngày</label>
            <div class="input_group">
              <input type="date" id="hist_date_to" class="input_find" max="<?php echo $today; ?>" style="width:160px;">
            </div>
          </div>
          <div class="search_field_wrapper">
            <label class="search_label">Danh mục</label>
            <div class="input_group">
              <select id="hist_category" style="width:180px;">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($categories_list as $cat): ?>
                  <option value="<?php echo htmlspecialchars($cat['MaDM'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cat['TenDM'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="search_field_wrapper">
            <label class="search_label">Người tạo</label>
            <div class="input_group">
              <select id="hist_user" style="width:180px;">
                <option value="">Tất cả người tạo</option>
                <?php foreach ($users_list as $u): ?>
                  <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="search_field_wrapper" style="min-width:280px;">
            <label class="search_label">Khoảng giá trị đơn hàng</label>
            <div class="price_range_wrapper">
              <div class="price_range_labels">
                <span id="hist_price_min_label"><?php echo number_format(0); ?>đ</span>
                <span id="hist_price_max_label"><?php echo number_format($max_price); ?>đ</span>
              </div>
              <div class="price_range_track">
                <input type="range" id="hist_price_min" min="0" max="<?php echo $max_price; ?>" value="0" step="100000" class="price_range_input price_range_min">
                <input type="range" id="hist_price_max" min="0" max="<?php echo $max_price; ?>" value="<?php echo $max_price; ?>" step="100000" class="price_range_input price_range_max">
              </div>
              <div class="price_range_limits">
                <span>0đ</span>
                <span><?php echo number_format($max_price); ?>đ</span>
              </div>
            </div>
          </div>
          <div class="search_field_wrapper" style="align-self:flex-end;">
            <button id="btnClearHistoryFilter" class="filter_button" style="height:40px; white-space:nowrap; display:none;">
              <span class="material-symbols-outlined" style="font-size:18px;">filter_list_off</span>
              <span>Xóa lọc</span>
            </button>
          </div>
        </div>

        <!-- Bảng phiếu nhập -->
        <div id="history_import_panel">
          <div class="table_container">
            <table class="product_table">
              <thead>
                <tr>
                  <th style="width:180px;">Mã phiếu</th>
                  <th style="width:100px;">Số loại hàng</th>
                  <th style="width:100px;">Tổng SL</th>
                  <th style="width:150px;">Tổng giá trị</th>
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
                  <th style="width:180px;">Mã phiếu</th>
                  <th style="width:100px;">Số loại hàng</th>
                  <th style="width:100px;">Tổng SL</th>
                  <th style="width:150px;">Tổng giá trị</th>
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

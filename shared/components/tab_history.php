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

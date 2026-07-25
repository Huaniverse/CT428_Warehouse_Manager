      <?php
      // shared/components/tab_dashboard.php — Bảo đảm biến dashboard tồn tại (tránh lỗi Undefined khi include độc lập)
      $total_categories = $total_categories ?? 0;
      $total_quantity   = $total_quantity   ?? 0;
      $total_val        = $total_val        ?? 0;
      $total_low        = $total_low        ?? 0;
      ?>
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

      <?php
      // shared/components/tab_dashboard.php — Bảo đảm biến dashboard tồn tại (tránh lỗi Undefined khi include độc lập)
      $total_categories    = $total_categories    ?? 0;
      $total_quantity      = $total_quantity      ?? 0;
      $total_val           = $total_val           ?? 0;
      $total_low           = $total_low           ?? 0;
      $total_out           = $total_out           ?? 0;
      $import_this_month   = $import_this_month   ?? 0;
      $export_this_month   = $export_this_month   ?? 0;
      $revenue_this_month  = $revenue_this_month  ?? 0;
      $low_stock_list      = $low_stock_list      ?? [];
      $top_selling_list    = $top_selling_list    ?? [];
      $recent_receipts     = $recent_receipts     ?? [];

      function format_receipt_code($ma_phieu) {
          $prefix = strtoupper(substr($ma_phieu, 0, 2));
          $label = $prefix === 'PN' ? 'Nhập' : ($prefix === 'PX' ? 'Xuất' : $ma_phieu);
          return $label;
      }
      ?>
      <div id="content_tongquan" class="tab_content active_tab">
        <h1 style="font-size: 24px; font-weight: 600; color: #0f172a; margin: 0 0 4px 0;">Tổng quan kho hàng</h1>
        <p style="font-size: 14px; color: #64748b; margin: 0 0 24px 0;">Thống kê và báo cáo số lượng, giá trị tồn kho theo thời gian thực</p>

        <!-- KPI Cards -->
        <div class="dashboard_grid">
          <div class="kpi_card">
            <div class="kpi_icon blue">
              <span class="material-symbols-outlined">category</span>
            </div>
            <div class="kpi_info">
              <span class="kpi_title">Danh mục</span>
              <span class="kpi_value"><?php echo number_format($total_categories); ?></span>
            </div>
          </div>

          <div class="kpi_card">
            <div class="kpi_icon green">
              <span class="material-symbols-outlined">inventory_2</span>
            </div>
            <div class="kpi_info">
              <span class="kpi_title">Tổng sản phẩm</span>
              <span class="kpi_value"><?php echo number_format($total_quantity); ?></span>
            </div>
          </div>

          <div class="kpi_card">
            <div class="kpi_icon purple">
              <span class="material-symbols-outlined">payments</span>
            </div>
            <div class="kpi_info">
              <span class="kpi_title">Tổng giá trị</span>
              <span class="kpi_value"><?php echo number_format($total_val); ?>đ</span>
            </div>
          </div>

          <div class="kpi_card">
            <div class="kpi_icon orange">
              <span class="material-symbols-outlined">warning</span>
            </div>
            <div class="kpi_info">
              <span class="kpi_title">Sắp hết hàng</span>
              <span class="kpi_value"><?php echo number_format($total_low); ?></span>
            </div>
          </div>

          <div class="kpi_card">
            <div class="kpi_icon red">
              <span class="material-symbols-outlined">block</span>
            </div>
            <div class="kpi_info">
              <span class="kpi_title">Hết hàng</span>
              <span class="kpi_value"><?php echo number_format($total_out); ?></span>
            </div>
          </div>

          <div class="kpi_card">
            <div class="kpi_icon cyan">
              <span class="material-symbols-outlined">download</span>
            </div>
            <div class="kpi_info">
              <span class="kpi_title">Phiếu nhập (tháng)</span>
              <span class="kpi_value"><?php echo number_format($import_this_month); ?></span>
            </div>
          </div>

          <div class="kpi_card">
            <div class="kpi_icon teal">
              <span class="material-symbols-outlined">upload</span>
            </div>
            <div class="kpi_info">
              <span class="kpi_title">Phiếu xuất (tháng)</span>
              <span class="kpi_value"><?php echo number_format($export_this_month); ?></span>
            </div>
          </div>

          <div class="kpi_card">
            <div class="kpi_icon indigo">
              <span class="material-symbols-outlined">trending_up</span>
            </div>
            <div class="kpi_info">
              <span class="kpi_title">Doanh thu (tháng)</span>
              <span class="kpi_value"><?php echo number_format($revenue_this_month); ?>đ</span>
            </div>
          </div>
        </div>

        <!-- Biểu đồ -->
        <div class="chart_grid">
          <div class="chart_card">
            <h3>Số lượng sản phẩm theo danh mục</h3>
            <div style="position: relative; height: 240px;">
              <canvas id="quantityChart"></canvas>
            </div>
          </div>
          <div class="chart_card">
            <h3>Giá trị tồn kho theo danh mục (triệu VNĐ)</h3>
            <div style="position: relative; height: 240px;">
              <canvas id="valueChart"></canvas>
            </div>
          </div>
        </div>

        <div class="chart_grid">
          <div class="chart_card">
            <h3>Xu hướng nhập / xuất kho (6 tháng)</h3>
            <div style="position: relative; height: 240px;">
              <canvas id="trendChart"></canvas>
            </div>
          </div>
          <div class="chart_card">
            <h3>Phân loại trạng thái kho</h3>
            <div style="position: relative; height: 240px;">
              <canvas id="statusChart"></canvas>
            </div>
          </div>
        </div>

        <div class="chart_grid" style="grid-template-columns: 1fr;">
          <div class="chart_card">
            <h3>Top 5 sản phẩm bán chạy nhất</h3>
            <div style="position: relative; height: 200px;">
              <canvas id="topSellingChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Bảng danh sách -->
        <div class="dashboard_tables_grid">
          <!-- Sắp hết hàng -->
          <div class="dashboard_table_card">
            <div class="dashboard_table_header">
              <span class="material-symbols-outlined" style="color:#f59e0b;">warning</span>
              <h3>Sắp hết hàng</h3>
            </div>
            <div class="table_container" style="margin-top:0;">
              <table class="product_table">
                <thead>
                  <tr>
                    <th>Tên sản phẩm</th>
                    <th style="width:80px;">SL</th>
                    <th style="width:120px;">Danh mục</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($low_stock_list)): ?>
                    <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:20px;">Không có sản phẩm sắp hết</td></tr>
                  <?php else: ?>
                    <?php foreach ($low_stock_list as $item): ?>
                      <tr>
                        <td><strong><?php echo htmlspecialchars($item['TenSP']); ?></strong></td>
                        <td style="text-align:center;"><span class="low_stock_badge"><?php echo (int)$item['SoLuong']; ?></span></td>
                        <td><?php echo htmlspecialchars($item['TenDM']); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Bán chạy nhất -->
          <div class="dashboard_table_card">
            <div class="dashboard_table_header">
              <span class="material-symbols-outlined" style="color:#10b981;">local_fire_department</span>
              <h3>Bán chạy nhất</h3>
            </div>
            <div class="table_container" style="margin-top:0;">
              <table class="product_table">
                <thead>
                  <tr>
                    <th>Tên sản phẩm</th>
                    <th style="width:80px;">Đã bán</th>
                    <th style="width:100px;">Giá</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($top_selling_list)): ?>
                    <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:20px;">Chưa có dữ liệu bán hàng</td></tr>
                  <?php else: ?>
                    <?php foreach ($top_selling_list as $item): ?>
                      <tr>
                        <td><strong><?php echo htmlspecialchars($item['TenSP']); ?></strong></td>
                        <td style="text-align:center;"><span class="top_selling_badge"><?php echo number_format($item['TongBan']); ?></span></td>
                        <td style="font-size:13px;"><?php echo number_format($item['Gia']); ?>đ</td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Phiếu gần đây -->
          <div class="dashboard_table_card dashboard_table_full">
            <div class="dashboard_table_header">
              <span class="material-symbols-outlined" style="color:#3b82f6;">history</span>
              <h3>Hoạt động gần đây</h3>
            </div>
            <div class="table_container" style="margin-top:0;">
              <table class="product_table">
                <thead>
                  <tr>
                    <th style="width:80px;">Loại</th>
                    <th>Mã phiếu</th>
                    <th style="width:80px;">SL loại</th>
                    <th style="width:80px;">Tổng SL</th>
                    <th>Người tạo</th>
                    <th style="width:150px;">Ngày tạo</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recent_receipts)): ?>
                    <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:20px;">Chưa có hoạt động nào</td></tr>
                  <?php else: ?>
                    <?php foreach ($recent_receipts as $r): ?>
                      <?php
                        $prefix = strtoupper(substr($r['ma_phieu'], 0, 2));
                        $is_import = ($prefix === 'PN');
                        $type_label = $is_import ? 'Nhập' : 'Xuất';
                        $type_class = $is_import ? 'import_badge' : 'export_badge';
                      ?>
                      <tr>
                        <td><span class="<?php echo $type_class; ?>"><?php echo $type_label; ?></span></td>
                        <td><span style="font-family:monospace; font-weight:600;"><?php echo htmlspecialchars($r['ma_phieu']); ?></span></td>
                        <td style="text-align:center;"><?php echo (int)$r['so_loai']; ?></td>
                        <td style="text-align:center;"><strong><?php echo number_format($r['tong_sl']); ?></strong></td>
                        <td><?php echo htmlspecialchars($r['nguoi_tao']); ?></td>
                        <td style="font-size:13px;"><?php echo date('d/m/Y H:i', strtotime($r['ngay_tao'])); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

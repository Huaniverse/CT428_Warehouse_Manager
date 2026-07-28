<?php
$is_admin         = $is_admin         ?? isAdmin();
$is_manager = $is_manager ?? isManager();
$categories_list  = $categories_list  ?? [];
?>
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
              <option value="manager">Quản lý kho</option>
            </select>
          </div>
        </div>
        <!-- Lịch truy cập khi tạo tài khoản -->
        <div id="create_staff_note" class="sched_note hidden">
          <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle;">info</span>
          Nhân viên bắt buộc phải có lịch truy cập.
        </div>
        <div class="form_group sched_toggle_row hidden" id="create_sched_toggle_group">
          <label class="checkbox_label">
            <input type="checkbox" id="create_has_schedule" class="checkbox_input">
            <span>Bật giới hạn giờ truy cập</span>
          </label>
        </div>
        <div id="create_time_fields">
          <div style="display:flex; gap:12px;">
            <div class="form_group" style="flex:1;">
              <label for="create_start">Giờ bắt đầu</label>
              <div class="form_input_wrapper">
                <span class="material-symbols-outlined form_icon">schedule</span>
                <input type="time" id="create_start" class="form_input" value="06:00">
              </div>
            </div>
            <div class="form_group" style="flex:1;">
              <label for="create_end">Giờ kết thúc</label>
              <div class="form_input_wrapper">
                <span class="material-symbols-outlined form_icon">schedule</span>
                <input type="time" id="create_end" class="form_input" value="22:00">
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal_footer">
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
              <?php echo renderCategoryOptions($categories_list); ?>
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
            <textarea id="new_prod_desc" class="form_input form_textarea" rows="3" placeholder="Nhập mô tả..."></textarea>
          </div>
        </div>
      </div>
      <div class="modal_footer">
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
    <div class="modal_card modal_card_sm">
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
        <button class="btn_primary" id="btnSubmitAddCategory" style="background-color: #8b5cf6;">
          <span class="material-symbols-outlined">save</span>
          Thêm danh mục
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal chi tiết sản phẩm -->
  <?php if ($is_admin || $is_manager || canImportExport()): ?>
  <div class="modal_overlay" id="productDetailModal">
    <div class="modal_card modal_card_lg">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined">inventory_2</span>
          <span id="productDetailTitle">Chi tiết sản phẩm</span>
        </h3>
        <button class="modal_close" id="btnCloseProductDetailModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body modal_body_scroll">
        <input type="hidden" id="detail_prod_id">

        <!-- Thông tin sản phẩm (hiển thị) -->
        <div id="productInfoDisplay">
          <div class="flex_between">
            <div class="flex_center">
              <span id="detail_prod_status_badge" class="badge in_stock"></span>
              <span id="detail_prod_stock_info" class="text_muted"></span>
            </div>
            <div class="flex_row">
              <button class="btn_primary btn_sm" id="btnAddToImportList" style="background:#16a34a;">
                <span class="material-symbols-outlined" style="font-size:18px;">bookmark_add</span>
                Nhập hàng
              </button>
              <button class="btn_primary btn_sm" id="btnAddToExportList" style="background:#ea580c;">
                <span class="material-symbols-outlined" style="font-size:18px;">bookmark_add</span>
                Xuất hàng
              </button>
              <?php if ($is_admin || $is_manager): ?>
              <button class="btn_primary btn_sm" id="btnToggleProdEditMode" style="background:#3b82f6;">
                <span class="material-symbols-outlined" style="font-size:18px;">edit</span>
                Chỉnh sửa
              </button>
              <?php endif; ?>
            </div>
          </div>
          <div class="product_detail_grid">
            <div class="detail_field">
              <span class="detail_field_label">Mã sản phẩm</span>
              <span class="detail_field_value" id="detail_prod_id_display"></span>
            </div>
            <div class="detail_field">
              <span class="detail_field_label">Tên sản phẩm</span>
              <span class="detail_field_value" id="detail_prod_name_display"></span>
            </div>
            <div class="detail_field">
              <span class="detail_field_label">Danh mục</span>
              <span class="detail_field_value" id="detail_prod_category_display"></span>
            </div>
            <div class="detail_field">
              <span class="detail_field_label">Giá bán</span>
              <span class="detail_field_value product_price" id="detail_prod_price_display"></span>
            </div>
            <div class="detail_field detail_field_full">
              <span class="detail_field_label">Mô tả</span>
              <span class="detail_field_value" style="white-space:pre-wrap;" id="detail_prod_desc_display"></span>
            </div>
          </div>
        </div>

        <!-- Form chỉnh sửa sản phẩm (ẩn mặc định, chỉ admin/manager) -->
        <?php if ($is_admin || $is_manager): ?>
        <div id="productInfoEdit" class="display_none">
          <div class="flex_between">
            <span class="text_section_title">Chỉnh sửa thông tin</span>
            <button class="btn_secondary btn_sm" id="btnCancelProdEdit">
              <span class="material-symbols-outlined" style="font-size:18px;">close</span>
              Hủy
            </button>
          </div>
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
                <?php echo renderCategoryOptions($categories_list); ?>
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
              <textarea id="edit_prod_desc" class="form_input form_textarea" rows="3"></textarea>
            </div>
          </div>
          <div class="flex_end">
            <button class="btn_secondary btn_sm" id="btnCancelProdEditBottom">Hủy</button>
            <button class="btn_primary btn_sm" id="btnSubmitEditProduct" style="background:#16a34a;">
              <span class="material-symbols-outlined" style="font-size:18px;">save</span>
              Lưu thay đổi
            </button>
          </div>
        </div>
        <?php endif; ?>

        <!-- Lịch sử nhập/xuất kho -->
        <div class="section_block">
          <div class="flex_center_gap8 section_block_mb">
            <span class="material-symbols-outlined" style="font-size:20px; color:#475569;">history</span>
            <span class="text_section_title">Lịch sử nhập/xuất kho</span>
          </div>
          <div class="flex_row section_block_mb">
            <button class="filter_button product_history_tab active" id="prod_hist_tab_import" onclick="switchProductHistoryTab('import')">
              <span class="material-symbols-outlined" style="font-size:16px;">download</span>
              <span>Nhập kho</span>
            </button>
            <button class="filter_button product_history_tab" id="prod_hist_tab_export" onclick="switchProductHistoryTab('export')">
              <span class="material-symbols-outlined" style="font-size:16px;">upload</span>
              <span>Xuất kho</span>
            </button>
          </div>
          <div id="productHistoryContent">
            <div class="table_container history_table_wrap">
              <table class="product_table history_table_wrap">
                <thead>
                  <tr>
                    <th style="width:160px;">Mã phiếu</th>
                    <th style="width:80px;">Số lượng</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                    <th>Người tạo</th>
                    <th style="width:140px;">Ngày tạo</th>
                  </tr>
                </thead>
                <tbody id="productHistoryBody">
                  <tr><td colspan="6" class="table_loading">Chọn sản phẩm để xem lịch sử.</td></tr>
                </tbody>
              </table>
            </div>
    </div>
  </div>
</div>
  </div>
</div>

  <?php endif; ?>

  <!-- Modal nhập kho (Batch) -->
  <?php if (canImportExport()): ?>
  <div class="modal_overlay" id="importStockModal">
    <div class="modal_card modal_card_lg">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined" style="color:#16a34a;">download</span>
          Nhập kho
        </h3>
        <button class="modal_close" id="btnCloseImportModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body modal_body_scroll">
        <!-- Bảng danh sách hàng đã thêm vào phiếu -->
        <div id="importBatchTableWrapper" class="section_block_mb display_none">
          <label class="label_section">
            <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle; margin-right:4px;">list_alt</span>
            Danh sách hàng nhập
          </label>
          <div class="batch_table_wrapper">
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
        <div class="add_form_card">
          <label class="label_section section_block_mb">
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
          <button class="btn_primary btn_full" id="btnAddToBatch" type="button" style="background-color: #3b82f6;">
            <span class="material-symbols-outlined" style="font-size: 18px;">playlist_add</span>
            Thêm vào phiếu
          </button>
        </div>

        <!-- Danh sách hàng cần nhập (gợi ý) -->
        <div id="importListSuggestions" class="suggestion_wrap">
          <div class="flex_between_wrap">
            <label class="label_section_inline">
              <span class="material-symbols-outlined" style="font-size:16px; color:#ea580c;">bookmark</span>
              Hàng cần nhập (<span id="importListCount">0</span>)
            </label>
            <button class="btn_secondary" id="btnClearImportList" style="padding:4px 10px; font-size:12px; height:28px; color:#dc2626; border-color:#fecaca; background:#fef2f2;">
              <span class="material-symbols-outlined" style="font-size:14px;">delete_sweep</span>
              Xóa hết
            </button>
          </div>
          <div id="importListItems" class="suggestion_items"></div>
        </div>
      </div>
      <div class="modal_footer">
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
    <div class="modal_card modal_card_lg">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined" style="color:#ea580c;">upload</span>
          Xuất kho
        </h3>
        <button class="modal_close" id="btnCloseExportModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body modal_body_scroll">
        <!-- Bảng danh sách hàng đã thêm vào phiếu -->
        <div id="exportBatchTableWrapper" class="section_block_mb display_none">
          <label class="label_section">
            <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle; margin-right:4px;">list_alt</span>
            Danh sách hàng xuất
          </label>
          <div class="batch_table_wrapper">
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
          <div id="export_stock_info" class="stock_info_box">
            Tồn kho hiện tại: <strong id="export_current_stock">0</strong>
          </div>
          <div class="flex_row_gap12">
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
          <button class="btn_primary btn_full" id="btnAddToExportBatch" type="button" style="background-color: #3b82f6;">
            <span class="material-symbols-outlined" style="font-size: 18px;">playlist_add</span>
            Thêm vào phiếu
          </button>
        </div>

        <!-- Danh sách hàng cần xuất (gợi ý) -->
        <div id="exportListSuggestions" class="suggestion_wrap">
          <div class="flex_between_wrap">
            <label class="label_section_inline">
              <span class="material-symbols-outlined" style="font-size:16px; color:#ea580c;">bookmark</span>
              Hàng cần xuất (<span id="exportListCount">0</span>)
            </label>
            <button class="btn_secondary" id="btnClearExportList" style="padding:4px 10px; font-size:12px; height:28px; color:#dc2626; border-color:#fecaca; background:#fef2f2;">
              <span class="material-symbols-outlined" style="font-size:14px;">delete_sweep</span>
              Xóa hết
            </button>
          </div>
          <div id="exportListItems" class="suggestion_items"></div>
        </div>
      </div>
      <div class="modal_footer">
        <button class="btn_primary" id="btnSubmitExport" style="background-color: #ea580c;" disabled>
          <span class="material-symbols-outlined">save</span>
          Xác nhận xuất kho (<span id="exportBatchCount">0</span> sản phẩm)
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal cấp quyền cho staff -->
  <?php if ($is_admin || $is_manager): ?>
  <div class="modal_overlay" id="permissionsModal">
    <div class="modal_card modal_card_sm">
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
          <label class="checkbox_label">
            <input type="checkbox" id="perm_import_export" class="checkbox_input">
            <span>Cho phép nhập/xuất kho</span>
          </label>
        </div>
      </div>
      <div class="modal_footer">
        <button class="btn_primary" id="btnSubmitPermissions">
          <span class="material-symbols-outlined">save</span>
          Lưu quyền
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal lịch truy cập -->
  <?php if ($is_admin || $is_manager): ?>
  <div class="modal_overlay" id="scheduleModal">
    <div class="modal_card" style="width: 420px;">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined">schedule</span>
          Lịch truy cập
        </h3>
        <button class="modal_close" id="btnCloseScheduleModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body">
        <input type="hidden" id="sched_user_id">
        <input type="hidden" id="sched_user_role">
        <p class="text_info">
          Thiết lập lịch cho: <strong id="sched_user_name"></strong>
        </p>
        <div id="sched_staff_note" class="stock_info_box" style="display:none;">
          <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle;">info</span>
          Nhân viên bắt buộc phải có lịch truy cập.
        </div>
        <div class="form_group" id="sched_toggle_group">
          <label class="checkbox_label">
            <input type="checkbox" id="sched_has_schedule" class="checkbox_input">
            <span>Bật giới hạn giờ truy cập</span>
          </label>
        </div>
        <div id="sched_time_fields" class="time_fields_hidden">
          <div class="time_fields_row">
            <div class="form_group form_group_flex">
              <label for="sched_start">Giờ bắt đầu</label>
              <div class="form_input_wrapper">
                <span class="material-symbols-outlined form_icon">schedule</span>
                <input type="time" id="sched_start" class="form_input" value="06:00">
              </div>
            </div>
            <div class="form_group form_group_flex">
              <label for="sched_end">Giờ kết thúc</label>
              <div class="form_input_wrapper">
                <span class="material-symbols-outlined form_icon">schedule</span>
                <input type="time" id="sched_end" class="form_input" value="22:00">
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal_footer">
        <button class="btn_primary" id="btnSubmitSchedule">
          <span class="material-symbols-outlined">save</span>
          Lưu lịch
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal cấp quyền truy cập tạm thời -->
  <?php if ($is_admin || $is_manager): ?>
  <div class="modal_overlay" id="tempAccessModal">
    <div class="modal_card modal_card_sm">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined">timer</span>
          Cấp quyền tạm thời
        </h3>
        <button class="modal_close" id="btnCloseTempAccessModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body">
        <input type="hidden" id="temp_user_id">
        <p class="text_info">
          Cho phép <strong id="temp_user_name"></strong> truy cập ngoài lịch trong:
        </p>
        <div class="flex_row section_block_mb" style="flex-wrap:wrap;">
          <button class="temp_duration_btn active" data-minutes="5">5 phút</button>
          <button class="temp_duration_btn" data-minutes="10">10 phút</button>
          <button class="temp_duration_btn" data-minutes="15">15 phút</button>
          <button class="temp_duration_btn" data-minutes="30">30 phút</button>
          <button class="temp_duration_btn" data-minutes="60">1 giờ</button>
        </div>
        <div class="note_box">
          <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle;">info</span>
          Sau khi hết giờ, tài khoản sẽ tự động khóa theo lịch trình.
        </div>
      </div>
      <div class="modal_footer">
        <button class="btn_primary" id="btnSubmitTempAccess">
          <span class="material-symbols-outlined">timer</span>
          Cấp quyền
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal chi tiết / sửa tài khoản -->
  <?php if ($is_admin || $is_manager): ?>
  <div class="modal_overlay" id="userDetailModal">
    <div class="modal_card modal_card_md">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined">edit</span>
          Chi tiết tài khoản
        </h3>
        <button class="modal_close" id="btnCloseUserDetail" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body modal_body_scroll">
        <input type="hidden" id="detail_user_id">
        <div class="user_detail_info">
          <div class="detail_row">
            <span class="detail_label">Tên đăng nhập:</span>
            <span class="detail_value" id="detail_username"></span>
          </div>
          <div class="detail_row">
            <span class="detail_label">Họ và tên:</span>
            <div class="form_input_wrapper detail_input">
              <input type="text" id="detail_fullname" class="form_input" placeholder="Nhập họ và tên">
            </div>
          </div>
          <div class="detail_row" id="detail_role_row">
            <span class="detail_label">Vai trò:</span>
            <div id="detail_role_edit" class="detail_input" style="display:none;">
              <div class="form_input_wrapper">
                <select id="detail_role_select" class="form_input" style="cursor:pointer;">
                  <option value="staff">Nhân viên kho</option>
                  <option value="manager">Quản lý kho</option>
                </select>
              </div>
            </div>
            <div id="detail_role_display" class="detail_right"></div>
          </div>
          <div class="detail_row">
            <span class="detail_label">Trạng thái:</span>
            <span id="detail_status_badge"></span>
          </div>
          <div class="detail_row" id="detail_perm_row" style="display:none;">
            <span class="detail_label">Quyền nhập/xuất:</span>
            <label id="detail_perm_label" style="display:flex; align-items:center; gap:6px; cursor:pointer;">
              <input type="checkbox" id="detail_allow_import_export" class="checkbox_input">
              <span style="font-size:13px;">Cho phép</span>
            </label>
          </div>
          <div class="detail_row" id="detail_sched_row">
            <span class="detail_label">Lịch truy cập:</span>
            <div id="detail_schedule_edit" class="detail_right" style="display:flex; align-items:center; gap:8px;">
              <label id="detail_sched_toggle_label" style="display:flex; align-items:center; gap:4px; cursor:pointer; flex-shrink:0;">
                <input type="checkbox" id="detail_has_schedule" class="checkbox_input">
                <span style="font-size:13px;">Bật</span>
              </label>
              <div id="detail_sched_time_fields" style="display:none; flex:1 1 auto; min-width:0;">
                <div style="display:flex; gap:6px; align-items:center; min-width:0;">
                  <div class="form_input_wrapper" style="flex:1 1 0%; min-width:100px; min-height:38px;">
                    <input type="time" id="detail_sched_start" class="form_input" value="06:00" style="padding-left:12px;">
                  </div>
                  <span style="color:#94a3b8; flex-shrink:0;">–</span>
                  <div class="form_input_wrapper" style="flex:1 1 0%; min-width:100px; min-height:38px;">
                    <input type="time" id="detail_sched_end" class="form_input" value="22:00" style="padding-left:12px;">
                  </div>
                </div>
              </div>
              <div id="detail_schedule_display"></div>
            </div>
          </div>
          <div class="detail_row">
            <span class="detail_label">Đặt lại MK:</span>
            <div class="detail_right" style="display:flex; gap:6px; align-items:center;">
              <div class="form_input_wrapper" style="flex:1; min-width:0;">
                <input type="password" id="detail_new_password" class="form_input" placeholder="Tối thiểu 6 ký tự" style="padding-right:32px;">
                <button type="button" class="toggle_password" onclick="toggleDetailPassword('detail_new_password')" tabindex="-1">
                  <span class="material-symbols-outlined">visibility</span>
                </button>
              </div>
              <button type="button" class="btn_primary" id="btnSavePassword" style="background:#dc2626; white-space:nowrap; padding:0 14px; font-size:13px; height:38px; flex-shrink:0;">
                Lưu
              </button>
            </div>
          </div>
          <div class="detail_row">
            <span class="detail_label">Đăng nhập cuối:</span>
            <span class="detail_value" id="detail_last_login"></span>
          </div>
        </div>
      </div>
      <div class="modal_footer">
        <button class="btn_primary" id="btnSaveUserInfo">
          <span class="material-symbols-outlined">save</span>
          Lưu thay đổi
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal chi tiết phiếu nhập/xuất -->
  <?php if (canImportExport()): ?>
  <div class="modal_overlay" id="receiptDetailModal">
    <div class="modal_card modal_card_lg">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined">receipt_long</span>
          <span id="receiptDetailTitle">Chi tiết phiếu</span>
        </h3>
        <button class="modal_close" id="btnCloseReceiptDetailModal" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body modal_body_scroll">
        <div id="receiptDetailInfo" style="margin-bottom: 16px;"></div>
        <div id="receiptDetailItems"></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal nhập số lượng từ danh sách -->
  <div class="modal_overlay" id="quantityPromptModal">
    <div class="modal_card modal_card_sm">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined" style="color:#ea580c;">edit_note</span>
          <span id="quantityPromptTitle">Nhập số lượng</span>
        </h3>
        <button class="modal_close" id="btnCloseQuantityPrompt" aria-label="Đóng modal">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal_body">
        <div class="form_group" style="margin-bottom: 0;">
          <label for="quantityPromptInput" id="quantityPromptLabel">Sản phẩm</label>
          <div class="form_input_wrapper">
            <span class="material-symbols-outlined form_icon">pinch</span>
            <input type="number" id="quantityPromptInput" class="form_input" min="1" value="10" placeholder="Nhập số lượng...">
          </div>
        </div>
      </div>
      <div class="modal_footer">
        <button class="btn_secondary" id="btnCancelQuantityPrompt" type="button">
          <span class="material-symbols-outlined">close</span>
          Hủy
        </button>
        <button class="btn_primary" id="btnConfirmQuantityPrompt" type="button" style="background-color:#16a34a;">
          <span class="material-symbols-outlined">check</span>
          Xác nhận
        </button>
      </div>
    </div>
  </div>

  <!-- Modal xác nhận (thay thế confirm()) -->
  <div class="modal_overlay" id="confirmModal">
    <div class="modal_card modal_card_sm">
      <div class="modal_header">
        <h3>
          <span class="material-symbols-outlined confirm_icon">warning</span>
          <span>Xác nhận</span>
        </h3>
      </div>
      <div class="modal_body">
        <p class="confirm_text" id="confirmMessage"></p>
      </div>
      <div class="modal_footer">
        <button class="btn_secondary" id="btnCancelAction" type="button">
          <span class="material-symbols-outlined">close</span>
          Hủy
        </button>
        <button class="btn_primary" id="btnConfirmAction" type="button">
          <span class="material-symbols-outlined">check</span>
          Xác nhận
        </button>
      </div>
    </div>
  </div>

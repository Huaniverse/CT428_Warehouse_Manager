// ─── Import/Export Stock Modal (Batch) ─────────────────────────────────────
let importModal, exportModal;
let importBatchItems = [], exportBatchItems = [];

// ─── Shared: Batch table render ────────────────────────────────────────────
function getBatchConfig(type) {
    return type === 'import'
        ? { items: importBatchItems, tbodyId: 'importBatchBody', wrapperId: 'importBatchTableWrapper', countId: 'importBatchCount', submitId: 'btnSubmitImport', removeFn: 'removeImportBatchItem', icon: 'add_circle', color: '#16a34a' }
        : { items: exportBatchItems, tbodyId: 'exportBatchBody', wrapperId: 'exportBatchTableWrapper', countId: 'exportBatchCount', submitId: 'btnSubmitExport', removeFn: 'removeExportBatchItem', icon: 'remove_circle', color: '#ea580c' };
}

function renderBatchTable(type) {
    const cfg = getBatchConfig(type);
    const tbody = document.getElementById(cfg.tbodyId);
    const wrapper = document.getElementById(cfg.wrapperId);
    const countSpan = document.getElementById(cfg.countId);
    const submitBtn = document.getElementById(cfg.submitId);
    if (!tbody || !wrapper) return;

    countSpan.textContent = cfg.items.length;
    submitBtn.disabled = cfg.items.length === 0;

    if (cfg.items.length === 0) {
        wrapper.style.display = 'none';
        tbody.innerHTML = '';
        return;
    }
        wrapper.style.display = 'block';
    tbody.innerHTML = cfg.items.map((item, idx) => `<tr>
        <td style="text-align:center; color:#64748b;">${idx + 1}</td>
        <td><span class="product_name">${escapeHtml(item.productName)}</span></td>
        <td style="text-align:center;"><strong>${number_format(item.quantity)}</strong></td>
        <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#64748b;" title="${escapeHtml(item.note)}">${escapeHtml(item.note || '—')}</td>
        <td style="text-align:center;">
            <button onclick="${cfg.removeFn}(${idx})" style="background:none; border:none; cursor:pointer; color:#ef4444; padding:4px;" title="Xóa khỏi phiếu">
                <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
            </button>
        </td>
    </tr>`).join('');
}

function resetBatchForm(type) {
    const prefix = type === 'import' ? 'import' : 'export';
    document.getElementById(prefix + '_quantity').value = '';
    document.getElementById(prefix + '_note').value = '';
    document.getElementById(prefix + '_product').value = '';
    document.getElementById(prefix + '_product_id').value = '';
    document.getElementById(prefix + '_combobox_clear').style.display = 'none';
}

window.removeImportBatchItem = function(idx) { importBatchItems.splice(idx, 1); renderBatchTable('import'); };
window.removeExportBatchItem = function(idx) { exportBatchItems.splice(idx, 1); renderBatchTable('export'); };

// ─── Import Modal ──────────────────────────────────────────────────────────
importModal = document.getElementById('importStockModal');
if (importModal) {
    document.getElementById('btn_import_stock')?.addEventListener('click', () => {
        loadProductDropdown('import_product');
        document.getElementById('import_quantity').value = '';
        document.getElementById('import_note').value = '';
        importBatchItems = [];
        renderBatchTable('import');
        renderListSuggestions('import');
        importModal.classList.add('open');
    });
    document.getElementById('btnCloseImportModal')?.addEventListener('click', () => importModal.classList.remove('open'));
    importModal.addEventListener('click', e => { if (e.target === importModal) importModal.classList.remove('open'); });

    document.getElementById('btnAddToBatch')?.addEventListener('click', function() {
        const productId = document.getElementById('import_product_id').value;
        const productName = document.getElementById('import_product').value;
        const quantity = parseInt(document.getElementById('import_quantity').value, 10);
        const note = document.getElementById('import_note').value.trim();

        if (!productId) { showToast('Vui lòng chọn sản phẩm.', 'error'); return; }
        if (!quantity || quantity <= 0) { showToast('Số lượng nhập phải lớn hơn 0.', 'error'); return; }

        const existing = importBatchItems.find(item => item.productId === productId);
        if (existing) {
            existing.quantity += quantity;
            if (note) existing.note = note;
            showToast(`Đã cộng thêm ${number_format(quantity)} vào "${productName}".`, 'success');
        } else {
            importBatchItems.push({ productId, productName, quantity, note });
            showToast(`Đã thêm "${productName}" vào phiếu.`, 'success');
        }

        renderBatchTable('import');
        resetBatchForm('import');
    });

    document.getElementById('btnSubmitImport')?.addEventListener('click', function() {
        if (importBatchItems.length === 0) {
            showToast('Chưa có sản phẩm nào trong phiếu.', 'error'); return;
        }

        const fd = new FormData();
        fd.append('items', JSON.stringify(importBatchItems.map(item => ({
            san_pham: item.productId, so_luong: item.quantity, ghi_chu: item.note
        }))));
        const originalLabel = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang xử lý...';

        ajaxCall(BASE + '/api/import_stock.php?action=create_batch', { method: 'POST', body: fd })
            .then(data => {
                this.disabled = false;
                this.innerHTML = originalLabel;
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    importBatchItems = [];
                    renderBatchTable('import');
                    importModal.classList.remove('open');
                    fetchFilteredProducts();
                }
            })
            .catch(() => {
                this.disabled = false;
                this.innerHTML = originalLabel;
                showToast('Lỗi kết nối máy chủ.', 'error');
            });
    });
}

// ─── Export Modal ───────────────────────────────────────────────────────────
exportModal = document.getElementById('exportStockModal');
if (exportModal) {
    document.getElementById('btn_export_stock')?.addEventListener('click', () => {
        loadProductDropdown('export_product');
        document.getElementById('export_quantity').value = '';
        document.getElementById('export_note').value = '';
        document.getElementById('export_stock_info').style.display = 'none';
        exportBatchItems = [];
        renderBatchTable('export');
        renderListSuggestions('export');
        exportModal.classList.add('open');
    });
    document.getElementById('btnCloseExportModal')?.addEventListener('click', () => exportModal.classList.remove('open'));
    exportModal.addEventListener('click', e => { if (e.target === exportModal) exportModal.classList.remove('open'); });

    document.getElementById('export_product')?.addEventListener('productSelected', function() {
        const spId = document.getElementById('export_product_id').value;
        const infoDiv = document.getElementById('export_stock_info');
        if (!spId) { infoDiv.style.display = 'none'; return; }
        ajaxCall(BASE + '/api/edit_product.php?action=get&id=' + spId)
            .then(data => {
                if (data.success) {
                    document.getElementById('export_current_stock').textContent = data.product.SoLuong;
                    infoDiv.style.display = 'block';
                    document.getElementById('export_quantity').max = data.product.SoLuong;
                }
            });
    });

    document.getElementById('btnAddToExportBatch')?.addEventListener('click', function() {
        const productId = document.getElementById('export_product_id').value;
        const productName = document.getElementById('export_product').value;
        const quantityInput = document.getElementById('export_quantity');
        const quantity = parseInt(quantityInput.value, 10);
        const note = document.getElementById('export_note').value.trim();
        const currentStock = parseInt(quantityInput.max || 0, 10);

        if (!productId) { showToast('Vui lòng chọn sản phẩm.', 'error'); return; }
        if (!quantity || quantity <= 0) { showToast('Số lượng xuất phải lớn hơn 0.', 'error'); return; }

        let totalQuantity = quantity;
        const existing = exportBatchItems.find(item => item.productId === productId);
        if (existing) totalQuantity += existing.quantity;
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

        renderBatchTable('export');
        resetBatchForm('export');
        document.getElementById('export_stock_info').style.display = 'none';
    });

    document.getElementById('btnSubmitExport')?.addEventListener('click', function() {
        if (exportBatchItems.length === 0) {
            showToast('Chưa có sản phẩm nào trong phiếu.', 'error'); return;
        }

        const fd = new FormData();
        fd.append('items', JSON.stringify(exportBatchItems.map(item => ({
            san_pham: item.productId, so_luong: item.quantity, ghi_chu: item.note
        }))));
        const originalLabel = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang xử lý...';

        ajaxCall(BASE + '/api/export_stock.php?action=create_batch', { method: 'POST', body: fd })
            .then(data => {
                this.disabled = false;
                this.innerHTML = originalLabel;
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    exportBatchItems = [];
                    renderBatchTable('export');
                    exportModal.classList.remove('open');
                    fetchFilteredProducts();
                }
            })
            .catch(() => {
                this.disabled = false;
                this.innerHTML = originalLabel;
                showToast('Lỗi kết nối máy chủ.', 'error');
            });
    });
}

// ─── Shared: History filter params ─────────────────────────────────────────
var __priceFilterDirty = false;
function getHistoryFilterParams() {
    const search   = (document.getElementById('hist_search_product')?.value || '').trim();
    const dateFrom = document.getElementById('hist_date_from')?.value || '';
    const dateTo   = document.getElementById('hist_date_to')?.value || '';
    const category = document.getElementById('hist_category')?.value || '';
    const userId   = document.getElementById('hist_user')?.value || '';
    const elPriceMin = document.getElementById('hist_price_min');
    const elPriceMax = document.getElementById('hist_price_max');
    const params = new URLSearchParams();
    if (search)   params.set('search', search);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo)   params.set('date_to', dateTo);
    if (category) params.set('category', category);
    if (userId)   params.set('user_id', userId);
    if (elPriceMin && elPriceMax && __priceFilterDirty) {
        params.set('price_min', Number(elPriceMin.value) || 0);
        params.set('price_max', Number(elPriceMax.value) || 0);
    }
    return params.toString();
}

// ─── Shared: Load history ──────────────────────────────────────────────────
function loadHistory(type, page = 1) {
    const cfg = type === 'import'
        ? { tbodyId: 'importHistoryBody', paginationId: 'importHistoryPagination', endpoint: 'import_stock.php', color: '#16a34a', emptyMsg: 'Chưa có phiếu nhập kho nào.' }
        : { tbodyId: 'exportHistoryBody', paginationId: 'exportHistoryPagination', endpoint: 'export_stock.php', color: '#ea580c', emptyMsg: 'Chưa có phiếu xuất kho nào.' };
    const tbody = document.getElementById(cfg.tbodyId);
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Đang tải...</td></tr>';

    const filterQS = getHistoryFilterParams();
    const sep = filterQS ? '&' : '';
    ajaxCall(BASE + '/api/' + cfg.endpoint + '?action=list&page=' + page + sep + filterQS)
        .then(data => {
            if (!data.success || data.records.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6"><div class="empty_state"><span class="material-symbols-outlined">inventory_2</span><p>${cfg.emptyMsg}</p></div></td></tr>`;
                document.getElementById(cfg.paginationId).innerHTML = '';
                return;
            }
            tbody.innerHTML = data.records.map(r => {
                const date = new Date(r.ngay_tao).toLocaleString('vi-VN');
                const safeMaPhieu = escapeHtml(r.ma_phieu).replace(/'/g, "\\'");
                const tongGia = number_format(parseInt(r.tong_gia_tien) || 0);
                return `<tr style="cursor:pointer;" onclick="openReceiptDetail('${type}', '${safeMaPhieu}')">
                    <td><span style="font-family:monospace;font-weight:600;">${escapeHtml(r.ma_phieu)}</span></td>
                    <td style="text-align:center;">${r.so_loai_hang} loại</td>
                    <td style="text-align:center;"><strong>${number_format(r.tong_so_luong)}</strong></td>
                    <td style="text-align:right;font-weight:600;color:${cfg.color};">${tongGia}đ</td>
                    <td>${escapeHtml(r.nguoi_tao_name)}</td>
                    <td style="font-size:13px;">${date}</td>
                </tr>`;
            }).join('');

            const totalPages = Math.ceil(data.total / data.per_page);
            document.getElementById(cfg.paginationId).innerHTML = totalPages > 1
                ? renderHistoryPagination(totalPages, page, type)
                : '';
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Lỗi tải dữ liệu.</td></tr>';
        });
}

function loadImportHistory(page = 1) { loadHistory('import', page); }
function loadExportHistory(page = 1) { loadHistory('export', page); }

// ─── Switch history sub-tab ────────────────────────────────────────────────
function switchHistoryTab(type) {
    const importBtn   = document.getElementById('hist_tab_import');
    const exportBtn   = document.getElementById('hist_tab_export');
    const importPanel = document.getElementById('history_import_panel');
    const exportPanel = document.getElementById('history_export_panel');

    importBtn.style.opacity = '';
    exportBtn.style.opacity = '';

    if (type === 'import') {
        importBtn.classList.add('active');
        exportBtn.classList.remove('active');
        importPanel.classList.remove('panel_hidden');
        exportPanel.classList.add('panel_hidden');
        loadImportHistory(1);
    } else {
        importBtn.classList.remove('active');
        exportBtn.classList.add('active');
        importPanel.classList.add('panel_hidden');
        exportPanel.classList.remove('panel_hidden');
        loadExportHistory(1);
    }
}

// ─── Receipt Detail Modal ──────────────────────────────────────────────────
const receiptDetailModal = document.getElementById('receiptDetailModal');
if (receiptDetailModal) {
    document.getElementById('btnCloseReceiptDetailModal')?.addEventListener('click', () => receiptDetailModal.classList.remove('open'));
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

    const endpoint = type === 'import' ? BASE + '/api/import_stock.php' : BASE + '/api/export_stock.php';
    ajaxCall(endpoint + '?action=detail&ma_phieu=' + encodeURIComponent(maPhieu))
        .then(data => {
            if (!data.success || !data.items || data.items.length === 0) {
                infoDiv.innerHTML = '<p style="color:#dc2626;">Không tìm thấy phiếu.</p>';
                return;
            }
            const ngayTao = new Date(data.ngay_tao).toLocaleString('vi-VN');
            const icon  = type === 'import' ? 'download' : 'upload';
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

// ─── Shared: History pagination ─────────────────────────────────────────────
window.renderHistoryPagination = function(totalPages, currentPage, type) {
    const onClickName = (type === 'import') ? 'loadImportHistory' : 'loadExportHistory';
    let html = '';
    html += `<button class="btn_secondary pagination_btn ${currentPage === 1 ? 'disabled' : ''}" ${currentPage === 1 ? 'disabled' : ''} onclick="${onClickName}(${currentPage - 1})">
               <span class="material-symbols-outlined" style="font-size:18px;">chevron_left</span>
             </button>`;
    const MAX_VISIBLE = 7;
    let start = Math.max(1, currentPage - Math.floor(MAX_VISIBLE / 2));
    let end = start + MAX_VISIBLE - 1;
    if (end > totalPages) { end = totalPages; start = Math.max(1, end - MAX_VISIBLE + 1); }
    if (start > 1) {
        html += `<button class="btn_secondary pagination_btn" onclick="${onClickName}(1)">1</button>`;
        if (start > 2) html += `<span class="pagination_ellipsis">…</span>`;
    }
    for (let i = start; i <= end; i++) {
        const activeCls = (i === currentPage) ? 'btn_primary active' : 'btn_secondary';
        html += `<button class="pagination_btn ${activeCls}" onclick="${onClickName}(${i})">${i}</button>`;
    }
    if (end < totalPages) {
        if (end < totalPages - 1) html += `<span class="pagination_ellipsis">…</span>`;
        html += `<button class="btn_secondary pagination_btn" onclick="${onClickName}(${totalPages})">${totalPages}</button>`;
    }
    html += `<button class="btn_secondary pagination_btn ${currentPage === totalPages ? 'disabled' : ''}" ${currentPage === totalPages ? 'disabled' : ''} onclick="${onClickName}(${currentPage + 1})">
               <span class="material-symbols-outlined" style="font-size:18px;">chevron_right</span>
             </button>`;
    return html;
};

// ─── History filter event listeners ─────────────────────────────────────────
(function() {
    let debounceTimer = null;
    const todayStr = new Date().toISOString().slice(0, 10);

    const elSearch    = document.getElementById('hist_search_product');
    const elDateFrom  = document.getElementById('hist_date_from');
    const elDateTo    = document.getElementById('hist_date_to');
    const elCategory  = document.getElementById('hist_category');
    const elUser      = document.getElementById('hist_user');
    const elPriceMin  = document.getElementById('hist_price_min');
    const elPriceMax  = document.getElementById('hist_price_max');
    const btnClear    = document.getElementById('btnClearHistoryFilter');

    const PRICE_MAX_CAP = elPriceMax ? (Number(elPriceMax.getAttribute('max')) || 0) : 0;

    function getCurrentHistoryTab() {
        return document.getElementById('hist_tab_import')?.classList.contains('active') ? 'import' : 'export';
    }

    function isFilterActive() {
        return (elSearch?.value || '').trim() !== ''
            || (elDateFrom?.value || '') !== ''
            || (elDateTo?.value || '') !== ''
            || (elCategory?.value || '') !== ''
            || (elUser?.value || '') !== ''
            || (elPriceMin && Number(elPriceMin.value) > 0)
            || (elPriceMax && Number(elPriceMax.value) < PRICE_MAX_CAP);
    }

    function toggleClearBtn() {
        if (!btnClear) return;
        btnClear.closest('.clear_btn_wrapper')?.classList.toggle('filter-active', isFilterActive());
    }

    function clampDate(el) {
        if (!el) return;
        if (el.value && el.value > todayStr) el.value = todayStr;
    }

    function applyHistoryFilter() {
        clampDate(elDateFrom);
        clampDate(elDateTo);
        if (elDateFrom && elDateTo && elDateFrom.value && elDateTo.value && elDateFrom.value > elDateTo.value) {
            elDateTo.value = elDateFrom.value;
        }
        toggleClearBtn();
        const tab = getCurrentHistoryTab();
        if (tab === 'import') loadImportHistory(1);
        else loadExportHistory(1);
    }

    elSearch?.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(applyHistoryFilter, 400);
    });
    elDateFrom?.addEventListener('change', applyHistoryFilter);
    elDateTo?.addEventListener('change', applyHistoryFilter);
    elCategory?.addEventListener('change', applyHistoryFilter);
    elUser?.addEventListener('change', applyHistoryFilter);

    elPriceMin?.addEventListener('input', function() {
        __priceFilterDirty = true;
        toggleClearBtn();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(applyHistoryFilter, 300);
    });
    elPriceMax?.addEventListener('input', function() {
        __priceFilterDirty = true;
        toggleClearBtn();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(applyHistoryFilter, 300);
    });

    if (btnClear) {
        btnClear.addEventListener('click', function() {
            if (elSearch)   elSearch.value = '';
            if (elDateFrom) elDateFrom.value = '';
            if (elDateTo)   elDateTo.value = '';
            if (elCategory) elCategory.value = '';
            if (elUser)     elUser.value = '';
            if (elPriceMin) elPriceMin.value = 0;
            if (elPriceMax) elPriceMax.value = PRICE_MAX_CAP;
            __priceFilterDirty = false;
            toggleClearBtn();
            const tab = getCurrentHistoryTab();
            if (tab === 'import') loadImportHistory(1);
            else loadExportHistory(1);
        });
    }

    toggleClearBtn();
})();

// ─── Shared: Storage list (localStorage) ───────────────────────────────────
function getListConfig(type) {
    return type === 'import'
        ? { key: 'warehouse_import_list', msg: 'Sản phẩm đã có trong danh sách.', addMsg: 'Đã thêm vào danh sách hàng cần nhập.', clearMsg: 'Xóa toàn bộ danh sách hàng cần nhập?', clearDoneMsg: 'Đã xóa danh sách hàng cần nhập.', chipClass: 'import_list_chip', promptLabel: 'Nhập số lượng cần nhập cho', suggestTitle: 'Nhấn để thêm nhanh vào phiếu nhập', quickNote: 'Thêm từ danh sách cần nhập', icon: 'add_circle', color: '#ea580c', countId: 'importListCount', containerId: 'importListSuggestions', itemsId: 'importListItems' }
        : { key: 'warehouse_export_list', msg: 'Sản phẩm đã có trong danh sách cần xuất.', addMsg: 'Đã thêm vào danh sách hàng cần xuất.', clearMsg: 'Xóa toàn bộ danh sách hàng cần xuất?', clearDoneMsg: 'Đã xóa danh sách hàng cần xuất.', chipClass: 'export_list_chip', promptLabel: 'Nhập số lượng cần xuất cho', suggestTitle: 'Nhấn để thêm nhanh vào phiếu xuất', quickNote: 'Thêm từ danh sách cần xuất', icon: 'remove_circle', color: '#ea580c', countId: 'exportListCount', containerId: 'exportListSuggestions', itemsId: 'exportListItems' };
}

function getList(type) {
    try { return JSON.parse(localStorage.getItem(getListConfig(type).key)) || []; }
    catch { return []; }
}

function saveList(type, list) {
    localStorage.setItem(getListConfig(type).key, JSON.stringify(list));
}

function addToList(type, productId, productName) {
    const cfg = getListConfig(type);
    const list = getList(type);
    if (list.some(item => item.productId === String(productId))) {
        showToast(cfg.msg, 'error');
        return false;
    }
    list.push({ productId: String(productId), productName });
    saveList(type, list);
    showToast(cfg.addMsg, 'success');
    return true;
}

function removeFromList(type, productId) {
    const list = getList(type).filter(item => item.productId !== String(productId));
    saveList(type, list);
    renderListSuggestions(type);
}

function clearList(type) {
    const cfg = getListConfig(type);
    showConfirm(cfg.clearMsg).then(confirmed => {
        if (!confirmed) return;
        saveList(type, []);
        renderListSuggestions(type);
        showToast(cfg.clearDoneMsg, 'success');
    });
}

function renderListSuggestions(type) {
    const cfg = getListConfig(type);
    const container = document.getElementById(cfg.containerId);
    const itemsDiv = document.getElementById(cfg.itemsId);
    const countSpan = document.getElementById(cfg.countId);
    if (!container || !itemsDiv) return;

    const list = getList(type);
    countSpan.textContent = list.length;

    if (list.length === 0) { container.style.display = 'none'; return; }
    container.style.display = 'block';

    itemsDiv.innerHTML = list.map(item => `
        <div class="${cfg.chipClass}" onclick="quickAddFromList('${type}', '${item.productId}', '${escapeHtml(item.productName).replace(/'/g, "\\'")}')" title="${cfg.suggestTitle}">
            <span class="material-symbols-outlined" style="font-size:16px; color:${cfg.color};">${cfg.icon}</span>
            <span class="chip_name">${escapeHtml(item.productName)}</span>
            <button class="chip_remove" onclick="event.stopPropagation(); removeFromList('${type}', '${item.productId}')" title="Xóa khỏi danh sách">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
    `).join('');
}

function quickAddFromList(type, productId, productName) {
    const cfg = getListConfig(type);
    const modal = document.getElementById('quantityPromptModal');
    const titleEl = document.getElementById('quantityPromptTitle');
    const labelEl = document.getElementById('quantityPromptLabel');
    const inputEl = document.getElementById('quantityPromptInput');
    const btnConfirm = document.getElementById('btnConfirmQuantityPrompt');
    const btnCancel = document.getElementById('btnCancelQuantityPrompt');
    const btnClose = document.getElementById('btnCloseQuantityPrompt');
    if (!modal || !inputEl) return;

    titleEl.textContent = cfg.promptLabel;
    labelEl.textContent = `"${productName}"`;
    inputEl.value = '10';
    modal.classList.add('open');
    setTimeout(() => inputEl.focus(), 50);

    function cleanup() {
        modal.classList.remove('open');
        btnConfirm.removeEventListener('click', onConfirm);
        btnCancel.removeEventListener('click', onCancel);
        btnClose.removeEventListener('click', onCancel);
        modal.removeEventListener('click', onOverlay);
        inputEl.removeEventListener('keydown', onKey);
    }
    function onOverlay(e) { if (e.target === modal) cleanup(); }
    function onCancel() { cleanup(); }
    function onKey(e) { if (e.key === 'Enter') onConfirm(); if (e.key === 'Escape') onCancel(); }
    function onConfirm() {
        const quantity = parseInt(inputEl.value, 10);
        if (!quantity || quantity <= 0) { inputEl.focus(); return; }
        cleanup();

        const batchItems = type === 'import' ? importBatchItems : exportBatchItems;
        const existing = batchItems.find(item => item.productId === productId);
        if (existing) {
            existing.quantity += quantity;
            showToast(`Đã cộng thêm ${number_format(quantity)} vào "${productName}".`, 'success');
        } else {
            batchItems.push({ productId, productName, quantity, note: cfg.quickNote });
            showToast(`Đã thêm "${productName}" (${number_format(quantity)}) vào phiếu.`, 'success');
        }

        renderBatchTable(type);
        removeFromList(type, productId);
    }

    btnConfirm.addEventListener('click', onConfirm);
    btnCancel.addEventListener('click', onCancel);
    btnClose.addEventListener('click', onCancel);
    modal.addEventListener('click', onOverlay);
    inputEl.addEventListener('keydown', onKey);
}

window.removeFromList = removeFromList;
window.quickAddFromList = quickAddFromList;

// Backward-compatible aliases (used by products.js)
window.addToImportList = (pid, pname) => addToList('import', pid, pname);
window.addToExportList  = (pid, pname) => addToList('export', pid, pname);

document.getElementById('btnClearImportList')?.addEventListener('click', () => clearList('import'));
document.getElementById('btnClearExportList')?.addEventListener('click', () => clearList('export'));

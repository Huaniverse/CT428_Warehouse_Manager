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
        renderImportListSuggestions();
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

        renderImportBatchTable();
        document.getElementById('import_quantity').value = '';
        document.getElementById('import_note').value = '';
        document.getElementById('import_product').value = '';
        document.getElementById('import_product_id').value = '';
        document.getElementById('import_combobox_clear').style.display = 'none';
    });

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

        apiFetch('modules/stock/api/import_stock.php?action=create_batch', { method: 'POST', body: fd })
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
        renderExportListSuggestions();
        exportModal.classList.add('open');
    });
    document.getElementById('btnCloseExportModal')?.addEventListener('click', () => exportModal.classList.remove('open'));
    exportModal.addEventListener('click', e => { if (e.target === exportModal) exportModal.classList.remove('open'); });

    document.getElementById('export_product')?.addEventListener('productSelected', function() {
        const spId = document.getElementById('export_product_id').value;
        const infoDiv = document.getElementById('export_stock_info');
        if (!spId) { infoDiv.style.display = 'none'; return; }
        apiFetch('modules/products/api/edit_product.php?action=get&id=' + spId)
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
        document.getElementById('export_quantity').value = '';
        document.getElementById('export_note').value = '';
        document.getElementById('export_product').value = '';
        document.getElementById('export_product_id').value = '';
        document.getElementById('export_combobox_clear').style.display = 'none';
        document.getElementById('export_stock_info').style.display = 'none';
    });

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

        apiFetch('modules/stock/api/export_stock.php?action=create_batch', { method: 'POST', body: fd })
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
        const minVal = Number(elPriceMin.value) || 0;
        const maxVal = Number(elPriceMax.value) || 0;
        params.set('price_min', minVal);
        params.set('price_max', maxVal);
    }
    return params.toString();
}

function loadImportHistory(page = 1) {
    const tbody = document.getElementById('importHistoryBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Đang tải...</td></tr>';

    const filterQS = getHistoryFilterParams();
    const sep = filterQS ? '&' : '';
    apiFetch('modules/stock/api/import_stock.php?action=list&page=' + page + sep + filterQS)
        .then(data => {
            if (!data.success || data.records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6"><div class="empty_state"><span class="material-symbols-outlined">inventory_2</span><p>Chưa có phiếu nhập kho nào.</p></div></td></tr>';
                document.getElementById('importHistoryPagination').innerHTML = '';
                return;
            }
            tbody.innerHTML = data.records.map(r => {
                const date = new Date(r.ngay_tao).toLocaleString('vi-VN');
                const safeMaPhieu = escapeHtml(r.ma_phieu).replace(/'/g, "\\'");
                const tongGia = number_format(parseInt(r.tong_gia_tien) || 0);
                return `<tr style="cursor:pointer;" onclick="openReceiptDetail('import', '${safeMaPhieu}')">
                    <td><span style="font-family:monospace;font-weight:600;">${escapeHtml(r.ma_phieu)}</span></td>
                    <td style="text-align:center;">${r.so_loai_hang} loại</td>
                    <td style="text-align:center;"><strong>${number_format(r.tong_so_luong)}</strong></td>
                    <td style="text-align:right;font-weight:600;color:#16a34a;">${tongGia}đ</td>
                    <td>${escapeHtml(r.nguoi_tao_name)}</td>
                    <td style="font-size:13px;">${date}</td>
                </tr>`;
            }).join('');

            const totalPages = Math.ceil(data.total / data.per_page);
            if (totalPages > 1) {
                document.getElementById('importHistoryPagination').innerHTML = renderHistoryPagination(totalPages, page, 'import');
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

    const filterQS = getHistoryFilterParams();
    const sep = filterQS ? '&' : '';
    apiFetch('modules/stock/api/export_stock.php?action=list&page=' + page + sep + filterQS)
        .then(data => {
            if (!data.success || data.records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6"><div class="empty_state"><span class="material-symbols-outlined">inventory_2</span><p>Chưa có phiếu xuất kho nào.</p></div></td></tr>';
                document.getElementById('exportHistoryPagination').innerHTML = '';
                return;
            }
            tbody.innerHTML = data.records.map(r => {
                const date = new Date(r.ngay_tao).toLocaleString('vi-VN');
                const safeMaPhieu = escapeHtml(r.ma_phieu).replace(/'/g, "\\'");
                const tongGia = number_format(parseInt(r.tong_gia_tien) || 0);
                return `<tr style="cursor:pointer;" onclick="openReceiptDetail('export', '${safeMaPhieu}')">
                    <td><span style="font-family:monospace;font-weight:600;">${escapeHtml(r.ma_phieu)}</span></td>
                    <td style="text-align:center;">${r.so_loai_hang} loại</td>
                    <td style="text-align:center;"><strong>${number_format(r.tong_so_luong)}</strong></td>
                    <td style="text-align:right;font-weight:600;color:#ea580c;">${tongGia}đ</td>
                    <td>${escapeHtml(r.nguoi_tao_name)}</td>
                    <td style="font-size:13px;">${date}</td>
                </tr>`;
            }).join('');

            const totalPages = Math.ceil(data.total / data.per_page);
            if (totalPages > 1) {
                document.getElementById('exportHistoryPagination').innerHTML = renderHistoryPagination(totalPages, page, 'export');
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
    const importBtn   = document.getElementById('hist_tab_import');
    const exportBtn   = document.getElementById('hist_tab_export');
    const importPanel = document.getElementById('history_import_panel');
    const exportPanel = document.getElementById('history_export_panel');

    // Xóa inline style cũ (để CSS quy định 100% opacity, active state, hover)
    importBtn.style.opacity = '';
    exportBtn.style.opacity = '';

    if (type === 'import') {
        importBtn.classList.add('active');
        exportBtn.classList.remove('active');
        importPanel.style.display = '';
        exportPanel.style.display = 'none';
        loadImportHistory(1);
    } else {
        importBtn.classList.remove('active');
        exportBtn.classList.add('active');
        importPanel.style.display = 'none';
        exportPanel.style.display = '';
        loadExportHistory(1);
    }
}

// ─── Receipt Detail Modal ──────────────────────────────────────────────
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

    const endpoint = type === 'import' ? 'modules/stock/api/import_stock.php' : 'modules/stock/api/export_stock.php';
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

// ─── Shared: History pagination (styled like project buttons) ─────────────
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

// ─── History filter event listeners ─────────────────────────────────────
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
        const importBtn = document.getElementById('hist_tab_import');
        return importBtn?.classList.contains('active') ? 'import' : 'export';
    }

    function getSliderMin() { return elPriceMin ? (Number(elPriceMin.value) || 0) : 0; }
    function getSliderMax() { return elPriceMax ? (Number(elPriceMax.value) || 0) : 0; }

    function isFilterActive() {
        return (elSearch?.value || '').trim() !== ''
            || (elDateFrom?.value || '') !== ''
            || (elDateTo?.value || '') !== ''
            || (elCategory?.value || '') !== ''
            || (elUser?.value || '') !== ''
            || getSliderMin() > 0
            || getSliderMax() < PRICE_MAX_CAP
            || (elPriceMin && Number(elPriceMin.value) > 0)
            || (elPriceMax && Number(elPriceMax.value) < PRICE_MAX_CAP);
    }

    function toggleClearBtn() {
        if (!btnClear) return;
        btnClear.closest('.clear_btn_wrapper')?.classList.toggle('filter-active', isFilterActive());
    }

    function clampDate(el) {
        if (!el) return;
        const v = el.value;
        if (v && v > todayStr) el.value = todayStr;
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

// ─── Import List (Danh sách hàng cần nhập — localStorage) ───────────────
const IMPORT_LIST_KEY = 'warehouse_import_list';

function getImportList() {
    try { return JSON.parse(localStorage.getItem(IMPORT_LIST_KEY)) || []; }
    catch { return []; }
}

function saveImportList(list) {
    localStorage.setItem(IMPORT_LIST_KEY, JSON.stringify(list));
}

function addToImportList(productId, productName) {
    const list = getImportList();
    if (list.some(item => item.productId === String(productId))) {
        showToast('Sản phẩm đã có trong danh sách.', 'error');
        return false;
    }
    list.push({ productId: String(productId), productName });
    saveImportList(list);
    showToast('Đã thêm vào danh sách hàng cần nhập.', 'success');
    return true;
}

function removeFromImportList(productId) {
    const list = getImportList().filter(item => item.productId !== String(productId));
    saveImportList(list);
    renderImportListSuggestions();
}

function clearImportList() {
    if (!confirm('Xóa toàn bộ danh sách hàng cần nhập?')) return;
    saveImportList([]);
    renderImportListSuggestions();
    showToast('Đã xóa danh sách hàng cần nhập.', 'success');
}

function isInImportList(productId) {
    return getImportList().some(item => item.productId === String(productId));
}

function renderImportListSuggestions() {
    const container = document.getElementById('importListSuggestions');
    const itemsDiv = document.getElementById('importListItems');
    const countSpan = document.getElementById('importListCount');
    if (!container || !itemsDiv) return;

    const list = getImportList();
    countSpan.textContent = list.length;

    if (list.length === 0) {
        container.style.display = 'none';
        return;
    }
    container.style.display = '';

    itemsDiv.innerHTML = list.map(item => `
        <div class="import_list_chip" onclick="quickAddFromImportList('${item.productId}', '${escapeHtml(item.productName).replace(/'/g, "\\'")}')" title="Nhấn để thêm nhanh vào phiếu nhập">
            <span class="material-symbols-outlined" style="font-size:16px; color:#ea580c;">add_circle</span>
            <span class="chip_name">${escapeHtml(item.productName)}</span>
            <button class="chip_remove" onclick="event.stopPropagation(); removeFromImportList('${item.productId}')" title="Xóa khỏi danh sách">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
    `).join('');
}

function quickAddFromImportList(productId, productName) {
    const quantity = parseInt(prompt(`Nhập số lượng cần nhập cho "${productName}":`, '10'), 10);
    if (!quantity || quantity <= 0) return;

    const existing = importBatchItems.find(item => item.productId === productId);
    if (existing) {
        existing.quantity += quantity;
        showToast(`Đã cộng thêm ${number_format(quantity)} vào "${productName}".`, 'success');
    } else {
        importBatchItems.push({ productId, productName, quantity, note: 'Thêm từ danh sách cần nhập' });
        showToast(`Đã thêm "${productName}" (${number_format(quantity)}) vào phiếu.`, 'success');
    }

    renderImportBatchTable();
    removeFromImportList(productId);
}

window.removeFromImportList = removeFromImportList;
window.quickAddFromImportList = quickAddFromImportList;

document.getElementById('btnClearImportList')?.addEventListener('click', clearImportList);

// ─── Export List (Danh sách hàng cần xuất — localStorage) ───────────────
const EXPORT_LIST_KEY = 'warehouse_export_list';

function getExportList() {
    try { return JSON.parse(localStorage.getItem(EXPORT_LIST_KEY)) || []; }
    catch { return []; }
}

function saveExportList(list) {
    localStorage.setItem(EXPORT_LIST_KEY, JSON.stringify(list));
}

function addToExportList(productId, productName) {
    const list = getExportList();
    if (list.some(item => item.productId === String(productId))) {
        showToast('Sản phẩm đã có trong danh sách cần xuất.', 'error');
        return false;
    }
    list.push({ productId: String(productId), productName });
    saveExportList(list);
    showToast('Đã thêm vào danh sách hàng cần xuất.', 'success');
    return true;
}

function removeFromExportList(productId) {
    const list = getExportList().filter(item => item.productId !== String(productId));
    saveExportList(list);
    renderExportListSuggestions();
}

function clearExportList() {
    if (!confirm('Xóa toàn bộ danh sách hàng cần xuất?')) return;
    saveExportList([]);
    renderExportListSuggestions();
    showToast('Đã xóa danh sách hàng cần xuất.', 'success');
}

function renderExportListSuggestions() {
    const container = document.getElementById('exportListSuggestions');
    const itemsDiv = document.getElementById('exportListItems');
    const countSpan = document.getElementById('exportListCount');
    if (!container || !itemsDiv) return;

    const list = getExportList();
    countSpan.textContent = list.length;

    if (list.length === 0) {
        container.style.display = 'none';
        return;
    }
    container.style.display = '';

    itemsDiv.innerHTML = list.map(item => `
        <div class="export_list_chip" onclick="quickAddFromExportList('${item.productId}', '${escapeHtml(item.productName).replace(/'/g, "\\'")}')" title="Nhấn để thêm nhanh vào phiếu xuất">
            <span class="material-symbols-outlined" style="font-size:16px; color:#ea580c;">remove_circle</span>
            <span class="chip_name">${escapeHtml(item.productName)}</span>
            <button class="chip_remove" onclick="event.stopPropagation(); removeFromExportList('${item.productId}')" title="Xóa khỏi danh sách">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
    `).join('');
}

function quickAddFromExportList(productId, productName) {
    const quantity = parseInt(prompt(`Nhập số lượng cần xuất cho "${productName}":`, '10'), 10);
    if (!quantity || quantity <= 0) return;

    const existing = exportBatchItems.find(item => item.productId === productId);
    if (existing) {
        existing.quantity += quantity;
        showToast(`Đã cộng thêm ${number_format(quantity)} vào "${productName}".`, 'success');
    } else {
        exportBatchItems.push({ productId, productName, quantity, note: 'Thêm từ danh sách cần xuất' });
        showToast(`Đã thêm "${productName}" (${number_format(quantity)}) vào phiếu.`, 'success');
    }

    renderExportBatchTable();
    removeFromExportList(productId);
}

window.removeFromExportList = removeFromExportList;
window.quickAddFromExportList = quickAddFromExportList;

document.getElementById('btnClearExportList')?.addEventListener('click', clearExportList);

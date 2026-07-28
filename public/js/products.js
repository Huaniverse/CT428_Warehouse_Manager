let productCurrentPage = 1;

function getProductStatus(r) {
    if (r.is_active == 0) return { cls: 'hidden_product', text: 'Ngừng kinh doanh' };
    if (r.SoLuong <= 0)   return { cls: 'out_of_stock', text: 'Hết hàng' };
    if (r.SoLuong < 30)   return { cls: 'low_stock', text: 'Sắp hết' };
    return { cls: 'in_stock', text: 'Còn hàng' };
}

function fetchFilteredProducts(page) {
    const searchEl    = document.getElementById('search_input_sort');
    const categoryEl  = document.getElementById('select_category');
    const priceEl     = document.getElementById('select_price');
    const qtyEl       = document.getElementById('select_quantity');
    const limitEl     = document.getElementById('select_limit');

    if (!searchEl || !categoryEl || !priceEl || !qtyEl || !limitEl) return;

    if (page === undefined || page === null || page < 1) {
        page = productCurrentPage || 1;
    }
    productCurrentPage = page;

    const searchVal    = searchEl.value;
    const categoryVal  = categoryEl.value;
    const priceSortVal = priceEl.value;
    const qtySortVal   = qtyEl.value;
    const limitVal     = limitEl.value || 10;

    const params = new URLSearchParams({
        search: searchVal, category: categoryVal,
        price_sort: priceSortVal, qty_sort: qtySortVal,
        limit: limitVal, page: page
    });

    const cfg = window.APP_CONFIG || {};
    const canManage = !!cfg.canManageProducts || !!cfg.isAdmin || cfg.role === 'store_manager';
    const colspan = canManage ? 8 : 7;
    const tbody = document.getElementById('product_table_body');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="' + colspan + '" style="text-align: center; padding: 32px; color: #64748b;">Đang tải dữ liệu...</td></tr>';

    apiFetch(BASE + '/api/filter_products.php?' + params.toString())
        .then(data => {
            const totalPages = Math.ceil(data.total / data.per_page);
            renderProductPagination(totalPages, data.page);
            const apiManage = (data.can_manage_products === true);
            const managePerm = canManage || apiManage;
            const col = managePerm ? 8 : 7;

            if (data.records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="' + col + '" class="no_results">Không tìm thấy sản phẩm nào phù hợp với bộ lọc.</td></tr>';
                return;
            }

            tbody.innerHTML = data.records.map(r => {
                const s = getProductStatus(r);
                const rowCls = r.is_active == 0 ? ' class="row_hidden"' : '';
                const desc = escapeHtml(r.MoTa || '');
                let actionCell = '';
                if (managePerm) {
                    const viewBtn = '<button class="btn_icon" title="Xem chi tiết" onclick="openProductDetail(' + r.MaSP + ')"><span class="material-symbols-outlined">visibility</span></button>';
                    const toggleBtn = r.is_active == 1
                        ? '<button class="btn_icon" title="Ẩn sản phẩm" onclick="toggleProductActive(' + r.MaSP + ', 0)"><span class="material-symbols-outlined">visibility_off</span></button>'
                        : '<button class="btn_icon" title="Khôi phục sản phẩm" onclick="toggleProductActive(' + r.MaSP + ', 1)"><span class="material-symbols-outlined">restore</span></button>';
                    actionCell = '<td class="action_cell"><div class="action_group">' + viewBtn + toggleBtn + '</div></td>';
                }
                return '<tr' + rowCls + '>'
                    + '<td>' + r.MaSP + '</td>'
                    + '<td><span class="product_name">' + escapeHtml(r.TenSP) + '</span></td>'
                    + '<td>' + escapeHtml(r.TenDM) + '</td>'
                    + '<td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="' + desc + '">' + desc + '</td>'
                    + '<td><span class="product_price">' + number_format(r.Gia) + ' đ</span></td>'
                    + '<td>' + number_format(r.SoLuong) + '</td>'
                    + '<td><span class="badge ' + s.cls + '">' + s.text + '</span></td>'
                    + actionCell
                    + '</tr>';
            }).join('');
        })
        .catch(err => {
            console.error('Error:', err);
            tbody.innerHTML = '<tr><td colspan="' + colspan + '" style="text-align: center; padding: 32px; color: #ef4444;">Đã xảy ra lỗi khi tải dữ liệu. Vui lòng thử lại.</td></tr>';
        });
}

function renderProductPagination(totalPages, currentPage) {
    const container = document.getElementById('productPagination');
    if (!container) return;
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    let html = '';
    html += `<button class="btn_secondary pagination_btn ${currentPage === 1 ? 'disabled' : ''}" ${currentPage === 1 ? 'disabled' : ''} onclick="fetchFilteredProducts(${currentPage - 1})">
               <span class="material-symbols-outlined" style="font-size:18px;">chevron_left</span>
             </button>`;
    const MAX_VISIBLE = window.innerWidth <= 480 ? 3 : window.innerWidth <= 768 ? 5 : 7;
    let start = Math.max(1, currentPage - Math.floor(MAX_VISIBLE / 2));
    let end = start + MAX_VISIBLE - 1;
    if (end > totalPages) { end = totalPages; start = Math.max(1, end - MAX_VISIBLE + 1); }
    if (start > 1) {
        html += `<button class="btn_secondary pagination_btn" onclick="fetchFilteredProducts(1)">1</button>`;
        if (start > 2) html += `<span class="pagination_ellipsis">…</span>`;
    }
    for (let i = start; i <= end; i++) {
        const activeCls = (i === currentPage) ? 'btn_primary active' : 'btn_secondary';
        html += `<button class="pagination_btn ${activeCls}" onclick="fetchFilteredProducts(${i})">${i}</button> `;
    }
    if (end < totalPages) {
        if (end < totalPages - 1) html += `<span class="pagination_ellipsis">…</span>`;
        html += `<button class="btn_secondary pagination_btn" onclick="fetchFilteredProducts(${totalPages})">${totalPages}</button>`;
    }
    html += `<button class="btn_secondary pagination_btn ${currentPage === totalPages ? 'disabled' : ''}" ${currentPage === totalPages ? 'disabled' : ''} onclick="fetchFilteredProducts(${currentPage + 1})">
               <span class="material-symbols-outlined" style="font-size:18px;">chevron_right</span>
             </button>`;
    container.innerHTML = html;
}

function resetProductFilter() {
    productCurrentPage = 1;
    const searchEl   = document.getElementById('search_input_sort');
    const categoryEl = document.getElementById('select_category');
    const priceEl    = document.getElementById('select_price');
    const qtyEl      = document.getElementById('select_quantity');
    const limitEl    = document.getElementById('select_limit');
    if (searchEl)   searchEl.value = '';
    if (categoryEl) categoryEl.value = '';
    if (priceEl)    priceEl.value = '';
    if (qtyEl)      qtyEl.value = '';
    if (limitEl)    limitEl.value = '10';
    fetchFilteredProducts(1);
}

['select_category', 'select_price', 'select_quantity', 'select_limit'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', () => fetchFilteredProducts(1));
});

const searchInput = document.getElementById('search_input_sort');
if (searchInput) {
    searchInput.addEventListener('input', debounce(() => fetchFilteredProducts(1), 400));
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); fetchFilteredProducts(1); }
    });
}

const searchBtn = document.querySelector('.search_button');
if (searchBtn) {
    searchBtn.addEventListener('click', function() {
        if (searchInput) searchInput.focus();
        fetchFilteredProducts(1);
    });
}

// ─── Product Detail ──────────────────────────────────────────────────────
let currentDetailProduct = null;
let currentProductHistoryType = 'import';

function openProductDetail(maSp) {
    const modal = document.getElementById('productDetailModal');
    if (!modal) return;

    document.getElementById('productInfoDisplay').style.display = '';
    document.getElementById('productInfoEdit').style.display = 'none';

    const tbody = document.getElementById('productHistoryBody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Đang tải...</td></tr>';

    modal.classList.add('open');

    apiFetch(BASE + '/api/edit_product.php?action=detail&id=' + maSp)
        .then(data => {
            if (!data.success) { showToast(data.message, 'error'); modal.classList.remove('open'); return; }
            currentDetailProduct = data;
            renderProductInfo(data.product);
            renderProductHistory(data);
        })
        .catch(() => { showToast('Không thể tải thông tin sản phẩm.', 'error'); modal.classList.remove('open'); });
}

function renderProductInfo(p) {
    document.getElementById('detail_prod_id').value = p.MaSP;
    document.getElementById('productDetailTitle').textContent = p.TenSP;
    document.getElementById('detail_prod_id_display').textContent = '#' + p.MaSP;
    document.getElementById('detail_prod_name_display').textContent = p.TenSP;
    document.getElementById('detail_prod_category_display').textContent = p.TenDM;
    document.getElementById('detail_prod_price_display').textContent = number_format(p.Gia) + ' đ';
    document.getElementById('detail_prod_desc_display').textContent = p.MoTa || 'Không có mô tả.';

    const statusEl = document.getElementById('detail_prod_status_badge');
    const stockInfo = document.getElementById('detail_prod_stock_info');
    const s = getProductStatus(p);
    statusEl.className = 'badge ' + s.cls;
    statusEl.textContent = s.text;
    stockInfo.textContent = 'Tồn kho: ' + number_format(p.SoLuong) + ' sản phẩm';
}

function renderProductHistory(data) {
    const tbody = document.getElementById('productHistoryBody');
    if (!tbody) return;

    const records = currentProductHistoryType === 'import' ? data.import_history : data.export_history;
    const color = currentProductHistoryType === 'import' ? '#16a34a' : '#ea580c';

    if (!records || records.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty_state"><span class="material-symbols-outlined">inventory_2</span><p>Chưa có lịch sử.</p></td></tr>';
        return;
    }

    tbody.innerHTML = records.map((r, idx) => {
        const date = new Date(r.ngay_tao).toLocaleString('vi-VN');
        return '<tr>'
            + '<td><span style="font-family:monospace;font-weight:600;">' + escapeHtml(r.ma_phieu) + '</span></td>'
            + '<td style="text-align:center;"><strong>' + number_format(r.so_luong) + '</strong></td>'
            + '<td style="text-align:right;">' + number_format(r.don_gia) + 'đ</td>'
            + '<td style="text-align:right;font-weight:600;color:' + color + ';">' + number_format(r.thanh_tien) + 'đ</td>'
            + '<td>' + escapeHtml(r.nguoi_tao_name) + '</td>'
            + '<td style="font-size:13px;">' + date + '</td>'
            + '</tr>';
    }).join('');
}

function switchProductHistoryTab(type) {
    currentProductHistoryType = type;
    const importBtn = document.getElementById('prod_hist_tab_import');
    const exportBtn = document.getElementById('prod_hist_tab_export');
    if (type === 'import') {
        importBtn.classList.add('active');
        exportBtn.classList.remove('active');
    } else {
        importBtn.classList.remove('active');
        exportBtn.classList.add('active');
    }
    if (currentDetailProduct) renderProductHistory(currentDetailProduct);
}

function toggleProductActive(maSp, newActive) {
    const label = newActive == 1 ? 'khôi phục' : 'ẩn';
    if (!confirm(`Bạn có chắc muốn ${label} sản phẩm này?`)) return;
    const fd = new FormData();
    fd.append('ma_sp', maSp);
    fd.append('is_active', newActive);
    apiFetch(BASE + '/api/edit_product.php?action=toggle_active', { method: 'POST', body: fd })
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) fetchFilteredProducts(productCurrentPage);
        })
        .catch(() => showToast('Lỗi kết nối máy chủ.', 'error'));
}

// ─── Product Detail Modal ────────────────────────────────────────────────
const prodDetailModal = document.getElementById('productDetailModal');
if (prodDetailModal) {
    document.getElementById('btnCloseProductDetailModal')?.addEventListener('click', () => prodDetailModal.classList.remove('open'));
    prodDetailModal.addEventListener('click', e => { if (e.target === prodDetailModal) prodDetailModal.classList.remove('open'); });

    document.getElementById('btnAddToImportList')?.addEventListener('click', function() {
        if (!currentDetailProduct) return;
        const p = currentDetailProduct.product;
        const added = addToImportList(p.MaSP, p.TenSP);
        if (added) {
            this.classList.add('btn_import_list_added');
            this.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">check_circle</span> Đã lưu';
            setTimeout(() => {
                this.classList.remove('btn_import_list_added');
                this.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">bookmark_add</span> Nhập hàng';
            }, 1500);
        }
    });

    document.getElementById('btnAddToExportList')?.addEventListener('click', function() {
        if (!currentDetailProduct) return;
        const p = currentDetailProduct.product;
        const added = addToExportList(p.MaSP, p.TenSP);
        if (added) {
            this.classList.add('btn_import_list_added');
            this.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">check_circle</span> Đã lưu';
            setTimeout(() => {
                this.classList.remove('btn_import_list_added');
                this.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">bookmark_add</span> Xuất hàng';
            }, 1500);
        }
    });

    document.getElementById('btnToggleProdEditMode')?.addEventListener('click', function() {
        if (!currentDetailProduct) return;
        const p = currentDetailProduct.product;
        document.getElementById('edit_prod_name').value = p.TenSP;
        document.getElementById('edit_prod_category').value = p.DanhMuc;
        document.getElementById('edit_prod_price').value = p.Gia;
        document.getElementById('edit_prod_desc').value = p.MoTa || '';
        document.getElementById('productInfoDisplay').style.display = 'none';
        document.getElementById('productInfoEdit').style.display = '';
    });

    function cancelProdEdit() {
        document.getElementById('productInfoDisplay').style.display = '';
        document.getElementById('productInfoEdit').style.display = 'none';
    }

    document.getElementById('btnCancelProdEdit')?.addEventListener('click', cancelProdEdit);
    document.getElementById('btnCancelProdEditBottom')?.addEventListener('click', cancelProdEdit);

    document.getElementById('btnSubmitEditProduct')?.addEventListener('click', function() {
        const fd = new FormData();
        fd.append('ma_sp', document.getElementById('detail_prod_id').value);
        fd.append('ten_sp', document.getElementById('edit_prod_name').value.trim());
        fd.append('danhmuc', document.getElementById('edit_prod_category').value);
        fd.append('gia', document.getElementById('edit_prod_price').value);
        fd.append('mota', document.getElementById('edit_prod_desc').value.trim());

        if (fd.get('ten_sp') === '' || fd.get('danhmuc') === '') {
            showToast('Vui lòng nhập đầy đủ thông tin.', 'error'); return;
        }

        submitForm(this, BASE + '/api/edit_product.php?action=update', fd, {
            loadingText: '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...',
            successLabel: '<span class="material-symbols-outlined" style="font-size:18px;">save</span> Lưu thay đổi',
            onSuccess: () => {
                fetchFilteredProducts(productCurrentPage);
                const maSp = document.getElementById('detail_prod_id').value;
                apiFetch(BASE + '/api/edit_product.php?action=detail&id=' + maSp)
                    .then(d => {
                        if (d.success) {
                            currentDetailProduct = d;
                            renderProductInfo(d.product);
                            renderProductHistory(d);
                        }
                    });
                cancelProdEdit();
            }
        });
    });
}

// ─── Modal: Thêm sản phẩm mới ─────────────────────────────────────────────
const addProdModal         = document.getElementById('addProductModal');
const btnOpenProdModal     = document.getElementById('btn_add_product');
const btnCloseProdModal    = document.getElementById('btnCloseAddProductModal');
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

        const priceNum = parseFloat(price);
        const qtyNum   = parseInt(quantity, 10);

        if (isNaN(priceNum) || priceNum < 0) {
            showToast('Giá bán phải là số không âm.', 'error');
            return;
        }
        if (isNaN(qtyNum) || qtyNum < 0 || !Number.isInteger(qtyNum)) {
            showToast('Số lượng phải là số nguyên không âm.', 'error');
            return;
        }

        const fd = new FormData();
        fd.append('ten_sp', name);
        fd.append('danhmuc', category);
        fd.append('gia', price);
        fd.append('so_luong', quantity);
        fd.append('mota', desc);

        submitForm(this, BASE + '/api/add_product.php', fd, {
            loadingText: '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...',
            successLabel: '<span class="material-symbols-outlined">save</span> Thêm sản phẩm',
            onSuccess: () => { closeAddProdModal(); fetchFilteredProducts(productCurrentPage); }
        });
    });
}

// ─── Modal: Thêm danh mục mới ─────────────────────────────────────────────
const addCatModal          = document.getElementById('addCategoryModal');
const btnOpenCatModal      = document.getElementById('btn_add_category');
const btnCloseCatModal     = document.getElementById('btnCloseAddCategoryModal');
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
if (addCatModal) {
    addCatModal.addEventListener('click', e => {
        if (e.target === addCatModal) closeAddCatModal();
    });
}

const catCodeInput = document.getElementById('new_cat_code');
if (catCodeInput) {
    catCodeInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
}

function updateCategoryDropdowns(categories) {
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

        submitForm(this, BASE + '/api/add_category.php', fd, {
            loadingText: '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...',
            successLabel: '<span class="material-symbols-outlined">save</span> Thêm danh mục',
            onSuccess: (data) => { closeAddCatModal(); updateCategoryDropdowns(data.categories); }
        });
    });
}

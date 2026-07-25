let productCurrentPage = 1;

// ─── Product status badge helper ───────────────────────────────────────────
function getProductStatus(r) {
    if (r.is_active == 0) return { cls: 'hidden_product', text: 'Ngừng kinh doanh' };
    if (r.SoLuong <= 0)   return { cls: 'out_of_stock', text: 'Hết hàng' };
    if (r.SoLuong < 30)   return { cls: 'low_stock', text: 'Sắp hết' };
    return { cls: 'in_stock', text: 'Còn hàng' };
}

// ─── AJAX Product Filtering ───────────────────────────────────────────────
function fetchFilteredProducts(page = 1) {
    productCurrentPage = page;
    const searchVal    = document.getElementById('search_input_sort').value;
    const categoryVal  = document.getElementById('select_category').value;
    const priceSortVal = document.getElementById('select_price').value;
    const qtySortVal   = document.getElementById('select_quantity').value;
    const limitVal     = document.getElementById('select_limit')?.value || 10;

    const params = new URLSearchParams({
        search: searchVal, category: categoryVal,
        price_sort: priceSortVal, qty_sort: qtySortVal,
        limit: limitVal, page: page
    });

    const cfg = window.APP_CONFIG || {};
    const canManage = !!cfg.canManageProducts || !!cfg.isAdmin || cfg.role === 'store_manager';
    const colspan = canManage ? 8 : 7;
    const tbody = document.getElementById('product_table_body');
    tbody.innerHTML = '<tr><td colspan="' + colspan + '" style="text-align: center; padding: 32px; color: #64748b;">Đang tải dữ liệu...</td></tr>';

    apiFetch('shared/api/filter_products.php?' + params.toString())
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
                    const editBtn = '<button class="btn_icon" title="Sửa sản phẩm" onclick="openEditProduct(' + r.MaSP + ')"><span class="material-symbols-outlined">edit</span></button>';
                    const toggleBtn = r.is_active == 1
                        ? '<button class="btn_icon" title="Ẩn sản phẩm" onclick="toggleProductActive(' + r.MaSP + ', 0)"><span class="material-symbols-outlined">visibility_off</span></button>'
                        : '<button class="btn_icon" title="Khôi phục sản phẩm" onclick="toggleProductActive(' + r.MaSP + ', 1)"><span class="material-symbols-outlined">restore</span></button>';
                    actionCell = '<td class="action_cell"><div class="action_group">' + editBtn + toggleBtn + '</div></td>';
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
    const MAX_VISIBLE = 7;
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

['select_category', 'select_price', 'select_quantity', 'select_limit'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', () => fetchFilteredProducts(1));
});

const searchInput = document.getElementById('search_input_sort');
if (searchInput) {
    searchInput.addEventListener('input', debounce(() => fetchFilteredProducts(1), 400));
}

// ─── Edit Product ────────────────────────────────────────────────────────
function openEditProduct(maSp) {
    apiFetch('shared/api/edit_product.php?action=get&id=' + maSp)
        .then(data => {
            if (!data.success) { showToast(data.message, 'error'); return; }
            const p = data.product;
            document.getElementById('edit_prod_id').value = p.MaSP;
            document.getElementById('edit_prod_name').value = p.TenSP;
            document.getElementById('edit_prod_category').value = p.DanhMuc;
            document.getElementById('edit_prod_price').value = p.Gia;
            document.getElementById('edit_prod_desc').value = p.MoTa || '';
            document.getElementById('editProductModal').classList.add('open');
        })
        .catch(() => showToast('Không thể tải thông tin sản phẩm.', 'error'));
}

function toggleProductActive(maSp, newActive) {
    const label = newActive == 1 ? 'khôi phục' : 'ẩn';
    if (!confirm(`Bạn có chắc muốn ${label} sản phẩm này?`)) return;
    const fd = new FormData();
    fd.append('ma_sp', maSp);
    fd.append('is_active', newActive);
    apiFetch('shared/api/edit_product.php?action=toggle_active', { method: 'POST', body: fd })
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) fetchFilteredProducts();
        })
        .catch(() => showToast('Lỗi kết nối máy chủ.', 'error'));
}

// ─── Edit Product Modal ──────────────────────────────────────────────────
const editProdModal = document.getElementById('editProductModal');
if (editProdModal) {
    document.getElementById('btnCloseEditProductModal')?.addEventListener('click', () => editProdModal.classList.remove('open'));
    document.getElementById('btnCancelEditProductModal')?.addEventListener('click', () => editProdModal.classList.remove('open'));
    editProdModal.addEventListener('click', e => { if (e.target === editProdModal) editProdModal.classList.remove('open'); });

    document.getElementById('btnSubmitEditProduct')?.addEventListener('click', function() {
        const fd = new FormData();
        fd.append('ma_sp', document.getElementById('edit_prod_id').value);
        fd.append('ten_sp', document.getElementById('edit_prod_name').value.trim());
        fd.append('danhmuc', document.getElementById('edit_prod_category').value);
        fd.append('gia', document.getElementById('edit_prod_price').value);
        fd.append('mota', document.getElementById('edit_prod_desc').value.trim());

        if (fd.get('ten_sp') === '' || fd.get('danhmuc') === '') {
            showToast('Vui lòng nhập đầy đủ thông tin.', 'error'); return;
        }

        this.disabled = true;
        this.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...';

        apiFetch('shared/api/edit_product.php?action=update', { method: 'POST', body: fd })
            .then(data => {
                this.disabled = false;
                this.innerHTML = '<span class="material-symbols-outlined">save</span> Lưu thay đổi';
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) { editProdModal.classList.remove('open'); fetchFilteredProducts(); }
            })
            .catch(() => {
                this.disabled = false;
                this.innerHTML = '<span class="material-symbols-outlined">save</span> Lưu thay đổi';
                showToast('Lỗi kết nối máy chủ.', 'error');
            });
    });
}

// ─── Modal: Thêm sản phẩm mới ─────────────────────────────────────────────
const addProdModal         = document.getElementById('addProductModal');
const btnOpenProdModal     = document.getElementById('btn_add_product');
const btnCloseProdModal    = document.getElementById('btnCloseAddProductModal');
const btnCancelProdModal   = document.getElementById('btnCancelAddProductModal');
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
if (btnCancelProdModal) btnCancelProdModal.addEventListener('click', closeAddProdModal);
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

        btnSubmitProd.disabled = true;
        btnSubmitProd.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...';

        apiFetch('shared/api/add_product.php', { method: 'POST', body: fd })
            .then(data => {
                btnSubmitProd.disabled = false;
                btnSubmitProd.innerHTML = '<span class="material-symbols-outlined">save</span> Thêm sản phẩm';
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeAddProdModal();
                    fetchFilteredProducts();
                }
            })
            .catch(() => {
                btnSubmitProd.disabled = false;
                btnSubmitProd.innerHTML = '<span class="material-symbols-outlined">save</span> Thêm sản phẩm';
                showToast('Lỗi kết nối máy chủ.', 'error');
            });
    });
}

// ─── Modal: Thêm danh mục mới ─────────────────────────────────────────────
const addCatModal          = document.getElementById('addCategoryModal');
const btnOpenCatModal      = document.getElementById('btn_add_category');
const btnCloseCatModal     = document.getElementById('btnCloseAddCategoryModal');
const btnCancelCatModal    = document.getElementById('btnCancelAddCategoryModal');
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
if (btnCancelCatModal) btnCancelCatModal.addEventListener('click', closeAddCatModal);
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

        btnSubmitCat.disabled = true;
        btnSubmitCat.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...';

        apiFetch('shared/api/add_category.php', { method: 'POST', body: fd })
            .then(data => {
                btnSubmitCat.disabled = false;
                btnSubmitCat.innerHTML = '<span class="material-symbols-outlined">save</span> Thêm danh mục';
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeAddCatModal();
                    updateCategoryDropdowns(data.categories);
                }
            })
            .catch(() => {
                btnSubmitCat.disabled = false;
                btnSubmitCat.innerHTML = '<span class="material-symbols-outlined">save</span> Thêm danh mục';
                showToast('Lỗi kết nối máy chủ.', 'error');
            });
    });
}

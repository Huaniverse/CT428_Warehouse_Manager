const _comboData = {};

function loadProductDropdown(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const prefix    = inputId.replace('_product', '') + '_combobox';
    const hidden    = document.getElementById(inputId + '_id');
    const dropdown  = document.getElementById(prefix + '_dropdown');
    const clearBtn  = document.getElementById(prefix + '_clear');
    const wrapper   = document.getElementById(prefix + '_wrapper');

    input.value = '';
    if (hidden) hidden.value = '';
    if (dropdown) { dropdown.innerHTML = ''; dropdown.style.display = 'none'; }
    if (clearBtn) clearBtn.style.display = 'none';
    input.placeholder = 'Đang tải danh sách...';

    if (_comboData[inputId]) {
        input.placeholder = 'Gõ tên sản phẩm để tìm...';
        setupComboEvents(inputId);
        return;
    }

    apiFetch(BASE + '/api/filter_products.php?search=&category=&price_sort=&qty_sort=&limit=all&active_only=1')
        .then(data => {
            const products = data.records.map(r => ({
                id: String(r.MaSP),
                name: r.TenSP,
                label: r.TenSP + ' (Mã: ' + r.MaSP + ')'
            }));
            _comboData[inputId] = products;
            input.placeholder = 'Gõ tên sản phẩm để tìm...';
            setupComboEvents(inputId);
        })
        .catch(() => { input.placeholder = 'Lỗi tải danh sách sản phẩm'; });
}

function setupComboEvents(inputId) {
    const input   = document.getElementById(inputId);
    const prefix  = inputId.replace('_product', '') + '_combobox';
    const hidden  = document.getElementById(inputId + '_id');
    const dropdown = document.getElementById(prefix + '_dropdown');
    const clearBtn = document.getElementById(prefix + '_clear');
    const wrapper  = document.getElementById(prefix + '_wrapper');
    const products = _comboData[inputId] || [];

    const oldInput = input;
    if (oldInput._comboBound) return;

    function filterAndShow(query) {
        const q = query.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const filtered = q === '' ? products : products.filter(p => {
            const name = p.name.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            return name.includes(q) || p.id.includes(q);
        });
        renderComboDropdown(dropdown, filtered);
    }

    input.addEventListener('input', function() {
        hidden.value = '';
        if (clearBtn) clearBtn.style.display = 'none';
        filterAndShow(this.value);
    });

    input.addEventListener('focus', function() {
        filterAndShow(this.value);
    });

    dropdown.addEventListener('click', function(e) {
        const item = e.target.closest('.combobox_item');
        if (!item) return;
        hidden.value = item.dataset.id;
        input.value = item.dataset.name;
        dropdown.style.display = 'none';
        if (clearBtn) clearBtn.style.display = 'block';
        input.dispatchEvent(new Event('productSelected'));
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            input.value = '';
            hidden.value = '';
            this.style.display = 'none';
            input.focus();
            var infoDiv = document.getElementById('export_stock_info');
            if (infoDiv) infoDiv.style.display = 'none';
        });
    }

    document.addEventListener('mousedown', function(e) {
        if (wrapper && !wrapper.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    input.addEventListener('keydown', function(e) {
        var items = dropdown.querySelectorAll('.combobox_item');
        var active = dropdown.querySelector('.combobox_item.active');
        var idx = Array.from(items).indexOf(active);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (active) active.classList.remove('active');
            idx = idx < items.length - 1 ? idx + 1 : 0;
            if (items[idx]) items[idx].classList.add('active');
            if (items[idx]) items[idx].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (active) active.classList.remove('active');
            idx = idx > 0 ? idx - 1 : items.length - 1;
            if (items[idx]) items[idx].classList.add('active');
            if (items[idx]) items[idx].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (active) active.click();
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
        }
    });

    var scrollParent = input.closest('.modal_body');
    if (scrollParent) {
        scrollParent.addEventListener('scroll', function() {
            if (dropdown.style.display === 'block') {
                positionComboDropdown(dropdown);
            }
        }, { passive: true });
    }
    window.addEventListener('resize', function() {
        if (dropdown.style.display === 'block') {
            positionComboDropdown(dropdown);
        }
    });

    input._comboBound = true;
}

function positionComboDropdown(dropdown) {
    const input = dropdown.previousElementSibling?.querySelector('input[type="text"]');
    if (!input) return;
    const rect = input.getBoundingClientRect();
    const maxHeight = 240;
    const spaceBelow = window.innerHeight - rect.bottom;
    const dropHeight = Math.min(maxHeight, dropdown.scrollHeight || maxHeight);
    dropdown.style.width = rect.width + 'px';
    dropdown.style.left = rect.left + 'px';
    if (spaceBelow < dropHeight && rect.top > dropHeight) {
        dropdown.style.top = (rect.top - dropHeight - 4) + 'px';
    } else {
        dropdown.style.top = (rect.bottom + 4) + 'px';
    }
}

function renderComboDropdown(dropdown, list) {
    if (list.length === 0) {
        dropdown.innerHTML = '<div class="combobox_empty">Không tìm thấy sản phẩm</div>';
        dropdown.style.display = 'block';
        positionComboDropdown(dropdown);
        return;
    }
    dropdown.innerHTML = list.map(p =>
        '<div class="combobox_item" data-id="' + p.id + '" data-name="' + escapeHtml(p.label) + '">' + escapeHtml(p.label) + '</div>'
    ).join('');
    dropdown.style.display = 'block';
    positionComboDropdown(dropdown);
}

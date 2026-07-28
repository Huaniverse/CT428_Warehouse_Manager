const BASE = window.BASE_URL || '';

function switchTab(tabId) {
    document.querySelectorAll('.sidebar_menu .menu_item').forEach(el => el.classList.remove('active'));
    const target = document.querySelector('.sidebar_menu .menu_item[data-tab="' + tabId + '"]');
    if (target) target.classList.add('active');

    document.querySelectorAll('.tab_content').forEach(content => content.classList.remove('active_tab'));
    const activeTab = document.getElementById('content_' + tabId);
    if (activeTab) activeTab.classList.add('active_tab');

    localStorage.setItem('activeTab', tabId);

    if (tabId === 'khohang') fetchFilteredProducts(1);
    if (tabId === 'lichsu')  switchHistoryTab('import');
    if (tabId === 'caidat')  { loadUsers(); loadSessions(); }
}

// ─── Tab switching ────────────────────────────────────────────────────────
document.querySelectorAll('.sidebar_menu .menu_item').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        switchTab(this.getAttribute('data-tab'));
    });
});

// ─── Restore active tab from localStorage ────────────────────────────────
(function() {
    const saved = localStorage.getItem('activeTab');
    if (saved && document.getElementById('content_' + saved)) {
        document.querySelectorAll('.tab_content').forEach(c => c.classList.remove('active_tab'));
        document.getElementById('content_' + saved).classList.add('active_tab');
        document.querySelectorAll('.sidebar_menu .menu_item').forEach(el => el.classList.remove('active'));
        const m = document.querySelector('.sidebar_menu .menu_item[data-tab="' + saved + '"]');
        if (m) m.classList.add('active');
        if (saved === 'khohang') setTimeout(function() { fetchFilteredProducts(productCurrentPage || 1); }, 50);
        if (saved === 'lichsu')  setTimeout(function() { switchHistoryTab('import'); }, 50);
        if (saved === 'caidat')  setTimeout(function() { loadUsers(); loadSessions(); }, 50);
    }
})();

// ─── User Dropdown ────────────────────────────────────────────────────────
const dropdownTrigger = document.getElementById('userDropdownTrigger');
const dropdownMenu    = document.getElementById('userDropdownMenu');

if (dropdownTrigger && dropdownMenu) {
    dropdownTrigger.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownMenu.classList.toggle('open');
    });
    document.addEventListener('click', function() {
        dropdownMenu.classList.remove('open');
    });
}

// [IMP-06] Debounce helper — trì hoãn gọi hàm sau khi người dùng ngừng gõ
function debounce(fn, delay) {
    let timer;
    return function(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

// ─── Session-aware fetch wrapper ──────────────────────────────────────────
const _csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function apiFetch(url, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const defaultHeaders = { 'X-Requested-With': 'XMLHttpRequest' };

    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        defaultHeaders['X-CSRF-Token'] = _csrfToken;
    }

    options.headers = Object.assign(defaultHeaders, options.headers || {});
    return fetch(url, options)
        .then(r => {
            if (r.status === 401) {
                return r.json().then(data => {
                    window.location.href = data.redirect || (BASE + '/php/Pages/login.php?expired=1');
                    return new Promise(() => {});
                });
            }
            if (!r.ok) {
                return Promise.reject(new Error('Lỗi máy chủ: HTTP ' + r.status));
            }
            return r.json();
        })
        .then(data => {
            if (data && data.redirect) {
                window.location.href = data.redirect;
                return new Promise(() => {});
            }
            return data;
        });
}

// ─── Toast ────────────────────────────────────────────────────────────────
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    const icon = type === 'success' ? 'check_circle' : 'error';
    toast.innerHTML = `<span class="material-symbols-outlined" style="font-size:18px;">${icon}</span><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'toast_out 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// Helper escape HTML chống XSS
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Helper number format
function number_format(num) {
    return parseInt(num).toLocaleString('vi-VN');
}

// ─── Submit form helper ────────────────────────────────────────────────────
async function submitForm(btn, url, fd, { loadingText, successLabel, onSuccess, onError } = {}) {
    const originalLabel = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = loadingText || '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang xử lý...';
    try {
        const data = await apiFetch(url, { method: 'POST', body: fd });
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success && onSuccess) await onSuccess(data);
    } catch {
        showToast('Lỗi kết nối máy chủ.', 'error');
        if (onError) onError();
    } finally {
        btn.disabled = false;
        btn.innerHTML = successLabel || originalLabel;
    }
}

// Các hàm tiện ích chung: chuyển đổi tab, menu thả, thông báo, ajax, xác nhận
const BASE = window.BASE_URL || '';

function switchTab(tabId) {
    document.querySelectorAll('.sidebar_menu .menu_item').forEach(el => el.classList.remove('active'));
    const target = document.querySelector('.sidebar_menu .menu_item[data-tab="' + tabId + '"]');
    if (target) target.classList.add('active');

    document.querySelectorAll('.tab_content').forEach(content => content.classList.remove('active_tab'));
    const activeTab = document.getElementById('content_' + tabId);
    if (activeTab) activeTab.classList.add('active_tab');

    localStorage.setItem('activeTab', tabId);

    if (tabId === 'warehouse') fetchFilteredProducts(1);
    if (tabId === 'history')  switchHistoryTab('import');
    if (tabId === 'users')  { loadUsers(); loadSessions(); }
}

// Chuyển đổi tab
document.querySelectorAll('.sidebar_menu .menu_item').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        switchTab(this.getAttribute('data-tab'));
    });
});

// Khôi phục tab đang hoạt động từ localStorage
(function() {
    const saved = localStorage.getItem('activeTab');
    if (saved && document.getElementById('content_' + saved)) {
        document.querySelectorAll('.tab_content').forEach(c => c.classList.remove('active_tab'));
        document.getElementById('content_' + saved).classList.add('active_tab');
        document.querySelectorAll('.sidebar_menu .menu_item').forEach(el => el.classList.remove('active'));
        const m = document.querySelector('.sidebar_menu .menu_item[data-tab="' + saved + '"]');
        if (m) m.classList.add('active');
        if (saved === 'warehouse') setTimeout(function() { fetchFilteredProducts(productCurrentPage || 1); }, 50);
        if (saved === 'history')  setTimeout(function() { switchHistoryTab('import'); }, 50);
        if (saved === 'users')  setTimeout(function() { loadUsers(); loadSessions(); }, 50);
    }
})();

// Menu thả người dùng
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

// AJAX wrapper có kiểm tra phiên đăng nhập
const _csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function ajaxCall(url, options = {}) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const method = (options.method || 'GET').toUpperCase();

        xhr.open(method, url);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            xhr.setRequestHeader('X-CSRF-Token', _csrfToken);
        }

        if (options.headers) {
            for (const [key, val] of Object.entries(options.headers)) {
                xhr.setRequestHeader(key, val);
            }
        }

        xhr.onload = function() {
            if (xhr.status === 401) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    window.location.href = data.redirect || (BASE + '/index.php?page=login&expired=1');
                } catch (e) {
                    window.location.href = BASE + '/index.php?page=login&expired=1';
                }
                return;
            }
            if (xhr.status < 200 || xhr.status >= 300) {
                reject(new Error('Lỗi máy chủ: HTTP ' + xhr.status));
                return;
            }
            try {
                const data = JSON.parse(xhr.responseText);
                if (data && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                resolve(data);
            } catch (e) {
                reject(e);
            }
        };

        xhr.onerror = function() {
            reject(new Error('Lỗi kết nối máy chủ.'));
        };

        xhr.send(options.body || null);
    });
}

// Modal xác nhận (thay thế confirm())
function showConfirm(message) {
    return new Promise(function(resolve) {
        var modal = document.getElementById('confirmModal');
        var msgEl = document.getElementById('confirmMessage');
        var btnConfirm = document.getElementById('btnConfirmAction');
        var btnCancel = document.getElementById('btnCancelAction');
        if (!modal || !msgEl || !btnConfirm || !btnCancel) {
            resolve(false);
            return;
        }
        msgEl.textContent = message;
        modal.classList.add('open');

        function cleanup() {
            modal.classList.remove('open');
            btnConfirm.removeEventListener('click', onConfirm);
            btnCancel.removeEventListener('click', onCancel);
            modal.removeEventListener('click', onOverlay);
        }

        function onConfirm() {
            cleanup();
            resolve(true);
        }

        function onCancel() {
            cleanup();
            resolve(false);
        }

        function onOverlay(e) {
            if (e.target === modal) onCancel();
        }

        btnConfirm.addEventListener('click', onConfirm);
        btnCancel.addEventListener('click', onCancel);
        modal.addEventListener('click', onOverlay);
    });
}

// Thông báo toast
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

// Hàm escape HTML chống XSS
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Hàm định dạng số
function number_format(num) {
    return parseInt(num).toLocaleString('vi-VN');
}

// Hỗ trợ gửi form
async function submitForm(btn, url, fd, { loadingText, successLabel, onSuccess, onError } = {}) {
    const originalLabel = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = loadingText || '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang xử lý...';
    try {
        const data = await ajaxCall(url, { method: 'POST', body: fd });
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

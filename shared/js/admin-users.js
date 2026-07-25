document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btnRefreshSessions')?.addEventListener('click', loadSessions);

    const createModal    = document.getElementById('createUserModal');
    const btnOpenModal   = document.getElementById('btnOpenCreateModal');
    const btnCloseModal  = document.getElementById('btnCloseModal');
    const btnCancelModal = document.getElementById('btnCancelModal');
    const btnSubmit      = document.getElementById('btnSubmitCreateUser');

    function openModal()  { createModal.classList.add('open'); document.getElementById('new_username').focus(); }
    function closeModal() {
        createModal.classList.remove('open');
        document.getElementById('new_username').value  = '';
        document.getElementById('new_fullname').value  = '';
        document.getElementById('new_password').value  = '';
        document.getElementById('new_role').value      = 'staff';
    }

    if (btnOpenModal) btnOpenModal.addEventListener('click', openModal);
    if (btnCloseModal) btnCloseModal.addEventListener('click', closeModal);
    if (btnCancelModal) btnCancelModal.addEventListener('click', closeModal);
    if (createModal) createModal.addEventListener('click', e => { if (e.target === createModal) closeModal(); });

    if (btnSubmit) btnSubmit.addEventListener('click', function() {
        const fd = new FormData();
        fd.append('username',  document.getElementById('new_username').value.trim());
        fd.append('full_name', document.getElementById('new_fullname').value.trim());
        fd.append('password',  document.getElementById('new_password').value);
        fd.append('role',      document.getElementById('new_role').value);

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang tạo...';

        apiFetch('admin/api/users.php?action=create', { method: 'POST', body: fd })
            .then(data => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<span class="material-symbols-outlined">save</span> Tạo tài khoản';
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) { closeModal(); loadUsers(); }
            })
            .catch(() => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<span class="material-symbols-outlined">save</span> Tạo tài khoản';
                showToast('Lỗi kết nối máy chủ.', 'error');
            });
    });

    const permModal = document.getElementById('permissionsModal');
    if (permModal) {
        document.getElementById('btnClosePermissionsModal')?.addEventListener('click', () => permModal.classList.remove('open'));
        document.getElementById('btnCancelPermissionsModal')?.addEventListener('click', () => permModal.classList.remove('open'));
        permModal.addEventListener('click', e => { if (e.target === permModal) permModal.classList.remove('open'); });
        document.getElementById('btnSubmitPermissions')?.addEventListener('click', function() {
            const fd = new FormData();
            fd.append('id', document.getElementById('perm_user_id').value);
            fd.append('allow_import_export', document.getElementById('perm_import_export').checked ? 1 : 0);
            this.disabled = true;
            this.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...';
            apiFetch('admin/api/users.php?action=update_permissions', { method: 'POST', body: fd })
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Lưu quyền';
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) { permModal.classList.remove('open'); loadUsers(); }
                })
                .catch(() => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Lưu quyền';
                    showToast('Lỗi kết nối máy chủ.', 'error');
                });
        });
    }
});

function loadUsers() {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Đang tải...</td></tr>';

    apiFetch('admin/api/users.php?action=list')
        .then(data => {
            if (!data.success) { tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Lỗi tải dữ liệu.</td></tr>'; return; }
            if (data.users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6"><div class="empty_state"><span class="material-symbols-outlined">group_off</span><p>Chưa có tài khoản nào.</p></div></td></tr>';
                return;
            }
            tbody.innerHTML = data.users.map(u => {
                const safeName      = escapeHtml(u.full_name);
                const safeUsername  = escapeHtml(u.username);
                const safeCreatedBy = escapeHtml(u.created_by_name || '');
                const initials    = safeName.replace(/&amp;|&lt;|&gt;|&quot;|&#039;/g, '').split(' ').map(w => w[0]).filter(Boolean).slice(-2).join('').toUpperCase();
                const statusBadge = u.is_active == 1
                    ? '<span class="status_badge active">● Hoạt động</span>'
                    : '<span class="status_badge inactive">● Vô hiệu hóa</span>';
                const roleBadge   = u.role === 'admin'
                    ? '<span class="user_role_badge admin">Admin</span>'
                    : u.role === 'store_manager'
                    ? '<span class="user_role_badge store_manager">Quản lý kho</span>'
                    : '<span class="user_role_badge staff">Nhân viên</span>';
                const lastLogin   = u.last_login ? new Date(u.last_login).toLocaleString('vi-VN') : '— Chưa đăng nhập';
                const createdBy   = safeCreatedBy || '— Hệ thống';
                const isSelf      = u.id == window.APP_CONFIG?.currentUserId;
                const toggleTitle  = u.is_active == 1 ? 'Vô hiệu hóa' : 'Kích hoạt';
                const toggleIcon   = u.is_active == 1 ? 'block' : 'check_circle';
                const toggleStatus = u.is_active == 1 ? 0 : 1;
                const canManage = window.APP_CONFIG?.isAdmin ? u.role !== 'admin' : u.role === 'staff';
                const actions = isSelf
                    ? '<span style="font-size:12px;color:#94a3b8;">Tài khoản của bạn</span>'
                    : `<div class="action_group">
                        ${canManage ? `<button class="btn_icon" title="${toggleTitle}" onclick="toggleUser(${u.id}, ${toggleStatus})"><span class="material-symbols-outlined">${toggleIcon}</span></button>` : ''}
                        ${u.role === 'staff' ? `<button class="btn_icon" title="Quyền nhập/xuất kho" onclick="openPermissionsModal(${u.id}, '${safeName}', ${u.allow_import_export || 0})"><span class="material-symbols-outlined">admin_panel_settings</span></button>` : ''}
                        ${canManage ? `<button class="btn_icon danger" title="Xóa tài khoản" onclick="deleteUser(${u.id}, '${safeName}')"><span class="material-symbols-outlined">delete</span></button>` : ''}
                       </div>`;
                return `<tr>
                    <td><div class="user_info_cell"><div class="user_avatar">${initials}</div><div><div class="u_fullname">${safeName}</div><div class="u_username">@${safeUsername}</div></div></div></td>
                    <td>${roleBadge}</td><td>${statusBadge}</td>
                    <td style="font-size:13px;">${lastLogin}</td>
                    <td style="font-size:13px;">${createdBy}</td>
                    <td>${actions}</td></tr>`;
            }).join('');
        })
        .catch(() => { tbody.innerHTML = '<tr><td colspan="6" class="table_loading">Lỗi kết nối.</td></tr>'; });
}

function toggleUser(id, newStatus) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('is_active', newStatus);
    apiFetch('admin/api/users.php?action=toggle', { method: 'POST', body: fd })
        .then(data => { showToast(data.message, data.success ? 'success' : 'error'); if (data.success) loadUsers(); })
        .catch(err => showToast(err.message || 'Lỗi kết nối máy chủ.', 'error'));
}

function deleteUser(id, name) {
    if (!confirm(`Bạn có chắc muốn xóa tài khoản "${name}"? Hành động này không thể hoàn tác.`)) return;
    const fd = new FormData();
    fd.append('id', id);
    apiFetch('admin/api/users.php?action=delete', { method: 'POST', body: fd })
        .then(data => { showToast(data.message, data.success ? 'success' : 'error'); if (data.success) loadUsers(); })
        .catch(err => showToast(err.message || 'Lỗi kết nối máy chủ.', 'error'));
}

function openPermissionsModal(userId, userName, currentVal) {
    document.getElementById('perm_user_id').value = userId;
    document.getElementById('perm_user_name').textContent = userName;
    document.getElementById('perm_import_export').checked = currentVal == 1;
    document.getElementById('permissionsModal').classList.add('open');
}

function loadSessions() {
    const list = document.getElementById('sessionList');
    list.innerHTML = '<div class="table_loading">Đang tải...</div>';

    apiFetch('admin/api/users.php?action=sessions')
        .then(data => {
            if (!data.success || data.sessions.length === 0) {
                list.innerHTML = '<div class="empty_state"><span class="material-symbols-outlined">sensors_off</span><p>Không có phiên hoạt động nào.</p></div>';
                return;
            }
            list.innerHTML = data.sessions.map(s => {
                const safeName     = escapeHtml(s.full_name);
                const safeUsername = escapeHtml(s.username);
                const safeIp       = escapeHtml(s.ip_address);
                const currentTag   = s.is_current ? '<span class="current_tag">Phiên này</span>' : '';
                const loginTime    = new Date(s.created_at).toLocaleString('vi-VN');
                const expireTime   = new Date(s.expires_at).toLocaleString('vi-VN');
                const roleLabel    = s.role === 'admin' ? 'Admin' : s.role === 'store_manager' ? 'Quản lý kho' : 'Staff';
                const kickBtn      = !s.is_current
                    ? `<button class="btn_icon danger" title="Kick user" onclick="kickUser(${s.user_id})"><span class="material-symbols-outlined">logout</span></button>`
                    : '';
                return `<div class="session_item ${s.is_current ? 'current_session' : ''}">
                    <div class="session_info">
                        <div class="session_user">${safeName} (@${safeUsername}) ${currentTag} <span class="user_role_badge ${s.role}" style="margin-left:6px;">${roleLabel}</span></div>
                        <div class="session_meta">IP: ${safeIp} &nbsp;|&nbsp; Đăng nhập: ${loginTime} &nbsp;|&nbsp; Hết hạn: ${expireTime}</div>
                    </div>${kickBtn}</div>`;
            }).join('');
        })
        .catch(() => { list.innerHTML = '<div class="empty_state"><span class="material-symbols-outlined">error</span><p>Không thể tải danh sách phiên.</p></div>'; });
}

function kickUser(userId) {
    if (!confirm('Đăng xuất người dùng này khỏi tất cả phiên?')) return;
    const fd = new FormData();
    fd.append('user_id', userId);
    apiFetch('admin/api/users.php?action=kick', { method: 'POST', body: fd })
        .then(data => { showToast(data.message, data.success ? 'success' : 'error'); if (data.success) loadSessions(); })
        .catch(err => showToast(err.message || 'Lỗi kết nối máy chủ.', 'error'));
}

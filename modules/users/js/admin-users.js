document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btnRefreshSessions')?.addEventListener('click', loadSessions);

    // ── Modal tạo tài khoản ────────────────────────────────────────────────
    const createModal    = document.getElementById('createUserModal');
    const btnOpenModal   = document.getElementById('btnOpenCreateModal');
    const btnCloseModal  = document.getElementById('btnCloseModal');
    const btnSubmit      = document.getElementById('btnSubmitCreateUser');
    const newRole        = document.getElementById('new_role');
    const createSchedToggle = document.getElementById('create_has_schedule');
    const createTimeFields  = document.getElementById('create_time_fields');
    const createSchedNote   = document.getElementById('create_staff_note');
    const createSchedToggleGroup = document.getElementById('create_sched_toggle_group');

    function setCreateFieldsDisabled(disabled) {
        if (!createTimeFields) return;
        createTimeFields.querySelectorAll('input[type="time"]').forEach(function(inp) { inp.disabled = disabled; });
        createTimeFields.classList.toggle('sched_disabled', disabled);
    }

    function updateCreateScheduleUI(role) {
        if (!createSchedNote || !createSchedToggle || !createTimeFields || !createSchedToggleGroup) return;
        var isStaff = (role === 'staff');
        var isManager = (role === 'store_manager');
        createSchedNote.classList.toggle('hidden', !isStaff);
        createSchedToggleGroup.classList.toggle('hidden', !isManager);
        if (isStaff) {
            createSchedToggle.checked = true;
            setCreateFieldsDisabled(false);
        } else if (isManager) {
            setCreateFieldsDisabled(!createSchedToggle.checked);
        } else {
            createSchedToggle.checked = false;
            setCreateFieldsDisabled(true);
        }
    }

    function resetCreateScheduleFields() {
        if (createSchedToggle) createSchedToggle.checked = false;
        setCreateFieldsDisabled(true);
        if (createSchedNote) createSchedNote.classList.add('hidden');
        if (createSchedToggleGroup) createSchedToggleGroup.classList.add('hidden');
        var cs = document.getElementById('create_start');
        var ce = document.getElementById('create_end');
        if (cs) cs.value = '06:00';
        if (ce) ce.value = '22:00';
    }

    function openModal() {
        if (!createModal) return;
        createModal.classList.add('open');
        document.getElementById('new_username').focus();
        updateCreateScheduleUI(newRole ? newRole.value : 'staff');
    }

    function closeModal() {
        if (!createModal) return;
        createModal.classList.remove('open');
        document.getElementById('new_username').value  = '';
        document.getElementById('new_fullname').value  = '';
        document.getElementById('new_password').value  = '';
        if (newRole) newRole.value = 'staff';
        resetCreateScheduleFields();
    }

    if (btnOpenModal) btnOpenModal.addEventListener('click', openModal);
    if (btnCloseModal) btnCloseModal.addEventListener('click', closeModal);
    if (createModal) createModal.addEventListener('click', e => { if (e.target === createModal) closeModal(); });

    if (newRole) {
        newRole.addEventListener('change', function() { updateCreateScheduleUI(this.value); });
        updateCreateScheduleUI(newRole.value);
    }
    if (createSchedToggle) {
        createSchedToggle.addEventListener('change', function() { setCreateFieldsDisabled(!this.checked); });
    }

    if (btnSubmit) btnSubmit.addEventListener('click', function() {
        const fd = new FormData();
        fd.append('username',  document.getElementById('new_username').value.trim());
        fd.append('full_name', document.getElementById('new_fullname').value.trim());
        fd.append('password',  document.getElementById('new_password').value);
        fd.append('role',      document.getElementById('new_role').value);
        const role = document.getElementById('new_role').value;
        if (role === 'staff' || (role === 'store_manager' && createSchedToggle.checked)) {
            fd.append('has_schedule', 1);
            fd.append('access_start', document.getElementById('create_start').value);
            fd.append('access_end',   document.getElementById('create_end').value);
        } else if (role === 'store_manager') {
            fd.append('has_schedule', 0);
        }

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang tạo...';

        apiFetch('modules/users/api/users.php?action=create', { method: 'POST', body: fd })
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

    // ── Modal quyền ────────────────────────────────────────────────────────
    const permModal = document.getElementById('permissionsModal');
    if (permModal) {
        document.getElementById('btnClosePermissionsModal')?.addEventListener('click', () => permModal.classList.remove('open'));
        permModal.addEventListener('click', e => { if (e.target === permModal) permModal.classList.remove('open'); });
        document.getElementById('btnSubmitPermissions')?.addEventListener('click', function() {
            const fd = new FormData();
            fd.append('id', document.getElementById('perm_user_id').value);
            fd.append('allow_import_export', document.getElementById('perm_import_export').checked ? 1 : 0);
            this.disabled = true;
            this.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...';
            apiFetch('modules/users/api/users.php?action=update_permissions', { method: 'POST', body: fd })
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

    // ── Modal lịch truy cập ───────────────────────────────────────────────
    const schedModal = document.getElementById('scheduleModal');
    if (schedModal) {
        document.getElementById('btnCloseScheduleModal')?.addEventListener('click', () => schedModal.classList.remove('open'));
        schedModal.addEventListener('click', e => { if (e.target === schedModal) schedModal.classList.remove('open'); });

        const schedToggle = document.getElementById('sched_has_schedule');
        const schedTimeFields = document.getElementById('sched_time_fields');
        if (schedToggle) {
            schedToggle.addEventListener('change', function() {
                schedTimeFields.style.display = this.checked ? 'block' : 'none';
            });
        }

        document.getElementById('btnSubmitSchedule')?.addEventListener('click', function() {
            const fd = new FormData();
            fd.append('id',           document.getElementById('sched_user_id').value);
            fd.append('has_schedule', schedToggle.checked ? 1 : 0);
            fd.append('access_start', document.getElementById('sched_start').value);
            fd.append('access_end',   document.getElementById('sched_end').value);

            this.disabled = true;
            this.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...';
            apiFetch('modules/users/api/users.php?action=update_schedule', { method: 'POST', body: fd })
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Lưu lịch';
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) { schedModal.classList.remove('open'); loadUsers(); }
                })
                .catch(() => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Lưu lịch';
                    showToast('Lỗi kết nối máy chủ.', 'error');
                });
        });
    }

    // ── Modal cấp quyền tạm thời ──────────────────────────────────────────
    const tempAccessModal = document.getElementById('tempAccessModal');
    if (tempAccessModal) {
        document.getElementById('btnCloseTempAccessModal')?.addEventListener('click', () => tempAccessModal.classList.remove('open'));
        tempAccessModal.addEventListener('click', e => { if (e.target === tempAccessModal) tempAccessModal.classList.remove('open'); });
        document.getElementById('btnSubmitTempAccess')?.addEventListener('click', grantTempAccess);
        document.querySelectorAll('.temp_duration_btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                _tempSelectedMinutes = parseInt(this.dataset.minutes);
                document.querySelectorAll('.temp_duration_btn').forEach(function(b) { b.classList.remove('active'); });
                this.classList.add('active');
            });
        });
    }

    // ── Modal chi tiết / sửa tài khoản ─────────────────────────────────────
    const detailModal = document.getElementById('userDetailModal');
    if (detailModal) {
        document.getElementById('btnCloseUserDetail')?.addEventListener('click', () => detailModal.classList.remove('open'));
        detailModal.addEventListener('click', e => { if (e.target === detailModal) detailModal.classList.remove('open'); });

        // Toggle lịch truy cập — làm mờ khi chưa check
        const detailSchedToggle = document.getElementById('detail_has_schedule');
        const detailSchedTime   = document.getElementById('detail_sched_time_fields');
        const detailSchedEdit   = document.getElementById('detail_schedule_edit');
        if (detailSchedToggle) {
            detailSchedToggle.addEventListener('change', function() {
                detailSchedEdit.style.opacity = this.checked ? '1' : '0.45';
                detailSchedTime.style.pointerEvents = this.checked ? 'auto' : 'none';
            });
        }

        // Khi đổi vai trò → staff bắt buộc lịch, hiện/ẩn quyền
        const detailRoleSelect = document.getElementById('detail_role_select');
        if (detailRoleSelect) {
            detailRoleSelect.addEventListener('change', function() {
                const isStaff = this.value === 'staff';
                const isAdmin = this.value === 'admin';
                const permRow = document.getElementById('detail_perm_row');
                permRow.style.display = isStaff ? 'flex' : 'none';
                // Schedule
                const schedRow       = document.getElementById('detail_sched_row');
                const schedEdit      = document.getElementById('detail_schedule_edit');
                const schedDisplay   = document.getElementById('detail_schedule_display');
                const schedToggleLbl = document.getElementById('detail_sched_toggle_label');
                const schedToggle    = document.getElementById('detail_has_schedule');
                const schedTimeFields = document.getElementById('detail_sched_time_fields');
                schedRow.style.display = 'flex';
                schedTimeFields.style.display = 'block';
                if (isAdmin) {
                    schedEdit.style.display = 'none';
                    schedDisplay.innerHTML = '<span class="schedule_badge none">Không áp dụng</span>';
                } else if (isStaff) {
                    schedEdit.style.display = 'flex';
                    schedDisplay.innerHTML = '';
                    schedToggle.disabled = true;
                    schedToggle.checked = true;
                    schedEdit.style.opacity = '1';
                    schedTimeFields.style.pointerEvents = 'auto';
                    schedToggleLbl.style.opacity = '0.6';
                    schedToggleLbl.style.cursor = 'default';
                } else {
                    schedEdit.style.display = 'flex';
                    schedDisplay.innerHTML = '';
                    schedToggle.disabled = false;
                    const checked = schedToggle.checked;
                    schedEdit.style.opacity = checked ? '1' : '0.45';
                    schedTimeFields.style.pointerEvents = checked ? 'auto' : 'none';
                    schedToggleLbl.style.opacity = '1';
                    schedToggleLbl.style.cursor = 'pointer';
                }
            });
        }

        // Lưu thông tin (full_name + role + schedule + permissions)
        document.getElementById('btnSaveUserInfo')?.addEventListener('click', function() {
            const fd = new FormData();
            fd.append('id',        document.getElementById('detail_user_id').value);
            fd.append('full_name', document.getElementById('detail_fullname').value.trim());
            fd.append('role',      document.getElementById('detail_role_select').value);
            fd.append('has_schedule', detailSchedToggle.checked ? 1 : 0);
            fd.append('access_start', document.getElementById('detail_sched_start').value);
            fd.append('access_end',   document.getElementById('detail_sched_end').value);
            const permCb = document.getElementById('detail_allow_import_export');
            if (permCb && permCb.offsetParent !== null) {
                fd.append('allow_import_export', permCb.checked ? 1 : 0);
            }

            this.disabled = true;
            this.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang lưu...';
            apiFetch('modules/users/api/users.php?action=update', { method: 'POST', body: fd })
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Lưu thay đổi';
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) { detailModal.classList.remove('open'); loadUsers(); }
                })
                .catch(() => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined">save</span> Lưu thay đổi';
                    showToast('Lỗi kết nối máy chủ.', 'error');
                });
        });

        // Đặt lại mật khẩu (nút nhỏ bên cạnh input)
        document.getElementById('btnSavePassword')?.addEventListener('click', function() {
            const userId  = document.getElementById('detail_user_id').value;
            const newPass = document.getElementById('detail_new_password').value;

            if (newPass.length < 6) {
                showToast('Mật khẩu phải có ít nhất 6 ký tự.', 'error');
                return;
            }

            const fd = new FormData();
            fd.append('user_id',          userId);
            fd.append('new_password',     newPass);
            fd.append('confirm_password', newPass);

            this.disabled = true;
            this.innerHTML = '<span class="material-symbols-outlined spin_icon" style="font-size:18px;">autorenew</span>';
            apiFetch('modules/users/api/users.php?action=reset_password', { method: 'POST', body: fd })
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">key</span> Lưu';
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        document.getElementById('detail_new_password').value = '';
                    }
                })
                .catch(() => {
                    this.disabled = false;
                    this.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">key</span> Lưu';
                    showToast('Lỗi kết nối máy chủ.', 'error');
                });
        });
    }
});

// ── Hàm tải danh sách users ──────────────────────────────────────────────
function loadUsers() {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="table_loading">Đang tải...</td></tr>';

    apiFetch('modules/users/api/users.php?action=list')
        .then(data => {
            if (!data.success) { tbody.innerHTML = '<tr><td colspan="7" class="table_loading">Lỗi tải dữ liệu.</td></tr>'; return; }
            if (data.users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7"><div class="empty_state"><span class="material-symbols-outlined">group_off</span><p>Chưa có tài khoản nào.</p></div></td></tr>';
                return;
            }
            tbody.innerHTML = data.users.map(u => {
                const safeName      = escapeHtml(u.full_name);
                const safeUsername  = escapeHtml(u.username);
                const safeCreatedBy = escapeHtml(u.created_by_name || '');
                const initials    = safeName.replace(/&amp;|&lt;|&gt;|&quot;|&#039;/g, '').split(' ').map(w => w[0]).filter(Boolean).slice(-2).join('').toUpperCase();
                const statusBadge = u.is_active == 1
                    ? '<span class="status_badge active">Hoạt động</span>'
                    : '<span class="status_badge inactive">Vô hiệu hóa</span>';
                const roleBadge   = u.role === 'admin'
                    ? '<span class="user_role_badge admin">Admin</span>'
                    : u.role === 'store_manager'
                    ? '<span class="user_role_badge store_manager">Cửa hàng trưởng</span>'
                    : '<span class="user_role_badge staff">Nhân viên</span>';
                const lastLogin   = u.last_login ? new Date(u.last_login).toLocaleString('vi-VN') : '— Chưa đăng nhập';
                const createdBy   = safeCreatedBy || '— Hệ thống';
                const isSelf      = u.id == window.APP_CONFIG?.currentUserId;

                // Lịch truy cập
                let schedBadge = '';
                if (u.role === 'admin') {
                    schedBadge = '<span class="schedule_badge none">Không áp dụng</span>';
                } else if (u.temp_access_until && new Date(u.temp_access_until) > new Date()) {
                    schedBadge = '<span class="schedule_badge temp_active" data-until="' + escapeHtml(u.temp_access_until) + '">'
                        + '<span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">timer</span> '
                        + '<span class="temp_countdown"></span></span>';
                } else if (u.has_schedule == 1 && u.access_start && u.access_end) {
                    schedBadge = '<span class="schedule_badge active">'
                        + '<span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">schedule</span> '
                        + escapeHtml(u.access_start.substring(0,5)) + ' – ' + escapeHtml(u.access_end.substring(0,5))
                        + '</span>';
                } else {
                    schedBadge = '<span class="schedule_badge none">Không giới hạn</span>';
                }

                const toggleTitle  = u.is_active == 1 ? 'Vô hiệu hóa' : 'Kích hoạt';
                const toggleIcon   = u.is_active == 1 ? 'block' : 'check_circle';
                const toggleStatus = u.is_active == 1 ? 0 : 1;
                const canManage = window.APP_CONFIG?.isAdmin ? u.role !== 'admin' : u.role === 'staff';
                const canGrantTemp = canManage && u.role !== 'admin' && u.has_schedule == 1;
                const hasTempAccess = u.temp_access_until && new Date(u.temp_access_until) > new Date();
                const canEditSched = u.role !== 'admin' && canManage;
                const schedStartVal = u.access_start ? u.access_start.substring(0,5) : '06:00';
                const schedEndVal   = u.access_end   ? u.access_end.substring(0,5)   : '22:00';
                const actions = isSelf
                    ? '<span style="font-size:12px;color:#94a3b8;">Tài khoản của bạn</span>'
                    : `<div class="action_group">
                        <button class="btn_icon" title="Xem / Sửa thông tin" onclick="openUserDetail(${u.id})"><span class="material-symbols-outlined">edit</span></button>
                        ${canEditSched ? `<button class="btn_icon" title="Chỉnh lịch truy cập" onclick="openScheduleModal(${u.id}, '${safeName}', '${u.role}', ${u.has_schedule}, '${schedStartVal}', '${schedEndVal}')"><span class="material-symbols-outlined">schedule</span></button>` : ''}
                        ${canGrantTemp ? (hasTempAccess
                            ? `<button class="btn_icon temp_revoke" title="Thu hồi quyền tạm thời" onclick="revokeTempAccess(${u.id})"><span class="material-symbols-outlined">timer_off</span></button>`
                            : `<button class="btn_icon temp_grant" title="Cấp quyền truy cập tạm thời" onclick="openTempAccessModal(${u.id}, '${safeName}')"><span class="material-symbols-outlined">timer</span></button>`) : ''}
                        ${canManage ? `<button class="btn_icon" title="${toggleTitle}" onclick="toggleUser(${u.id}, ${toggleStatus})"><span class="material-symbols-outlined">${toggleIcon}</span></button>` : ''}
                        ${canManage ? `<button class="btn_icon danger" title="Xóa tài khoản" onclick="deleteUser(${u.id}, '${safeName}')"><span class="material-symbols-outlined">delete</span></button>` : ''}
                       </div>`;
                return `<tr>
                    <td><div class="user_info_cell"><div class="user_avatar">${initials}</div><div><div class="u_fullname">${safeName}</div><div class="u_username">@${safeUsername}</div></div></div></td>
                    <td>${roleBadge}</td><td>${statusBadge}</td>
                    <td>${schedBadge}</td>
                    <td style="font-size:13px;">${lastLogin}</td>
                    <td style="font-size:13px;">${createdBy}</td>
                    <td>${actions}</td></tr>`;
            }).join('');
            updateTempCountdowns();
        })
        .catch(() => { tbody.innerHTML = '<tr><td colspan="7" class="table_loading">Lỗi kết nối.</td></tr>'; });
}

// ── Vô hiệu hóa / Kích hoạt ─────────────────────────────────────────────
function toggleUser(id, newStatus) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('is_active', newStatus);
    apiFetch('modules/users/api/users.php?action=toggle', { method: 'POST', body: fd })
        .then(data => { showToast(data.message, data.success ? 'success' : 'error'); if (data.success) loadUsers(); })
        .catch(err => showToast(err.message || 'Lỗi kết nối máy chủ.', 'error'));
}

// ── Xóa tài khoản ────────────────────────────────────────────────────────
function deleteUser(id, name) {
    if (!confirm(`Bạn có chắc muốn xóa tài khoản "${name}"? Hành động này không thể hoàn tác.`)) return;
    const fd = new FormData();
    fd.append('id', id);
    apiFetch('modules/users/api/users.php?action=delete', { method: 'POST', body: fd })
        .then(data => { showToast(data.message, data.success ? 'success' : 'error'); if (data.success) loadUsers(); })
        .catch(err => showToast(err.message || 'Lỗi kết nối máy chủ.', 'error'));
}

// ── Modal quyền ──────────────────────────────────────────────────────────
function openPermissionsModal(userId, userName, currentVal) {
    document.getElementById('perm_user_id').value = userId;
    document.getElementById('perm_user_name').textContent = userName;
    document.getElementById('perm_import_export').checked = currentVal == 1;
    document.getElementById('permissionsModal').classList.add('open');
}

// ── Modal lịch truy cập ──────────────────────────────────────────────────
function openScheduleModal(userId, userName, role, hasSchedule, startTime, endTime) {
    document.getElementById('sched_user_id').value   = userId;
    document.getElementById('sched_user_role').value = role;
    document.getElementById('sched_user_name').textContent = userName;

    const schedToggle = document.getElementById('sched_has_schedule');
    const timeFields  = document.getElementById('sched_time_fields');
    const toggleGroup = document.getElementById('sched_toggle_group');
    const staffNote   = document.getElementById('sched_staff_note');

    if (role === 'staff') {
        toggleGroup.style.display = 'none';
        staffNote.style.display   = 'block';
        schedToggle.checked       = true;
        timeFields.style.display  = 'block';
    } else {
        toggleGroup.style.display = 'flex';
        staffNote.style.display   = 'none';
        schedToggle.checked       = hasSchedule == 1;
        timeFields.style.display  = hasSchedule == 1 ? 'block' : 'none';
    }

    document.getElementById('sched_start').value = startTime ? startTime.substring(0, 5) : '06:00';
    document.getElementById('sched_end').value   = endTime   ? endTime.substring(0, 5)   : '22:00';

    document.getElementById('scheduleModal').classList.add('open');
}

// ── Modal chi tiết / sửa tài khoản ───────────────────────────────────────
function openUserDetail(userId) {
    apiFetch('modules/users/api/users.php?action=get_detail&id=' + userId)
        .then(data => {
            if (!data.success) { showToast(data.message, 'error'); return; }
            const u = data.user;
            document.getElementById('detail_user_id').value = u.id;
            document.getElementById('detail_username').textContent = escapeHtml(u.username);

            // Họ và tên — editable input
            document.getElementById('detail_fullname').value = u.full_name;

            // Vai trò — admin thấy select, store_manager thấy badge readonly
            const roleEdit    = document.getElementById('detail_role_edit');
            const roleDisplay = document.getElementById('detail_role_display');
            if (window.APP_CONFIG?.isAdmin) {
                roleEdit.style.display = 'block';
                roleDisplay.style.display = 'none';
                document.getElementById('detail_role_select').value = u.role;
            } else {
                roleEdit.style.display = 'none';
                roleDisplay.style.display = 'block';
                const badge = u.role === 'admin'
                    ? '<span class="user_role_badge admin">Admin</span>'
                    : u.role === 'store_manager'
                    ? '<span class="user_role_badge store_manager">Cửa hàng trưởng</span>'
                    : '<span class="user_role_badge staff">Nhân viên</span>';
                roleDisplay.innerHTML = badge;
            }

            const statusBadge = u.is_active == 1
                ? '<span class="status_badge active">Hoạt động</span>'
                : '<span class="status_badge inactive">Vô hiệu hóa</span>';
            document.getElementById('detail_status_badge').innerHTML = statusBadge;

            // Quyền nhập/xuất — chỉ hiện cho staff
            const permRow = document.getElementById('detail_perm_row');
            if (u.role === 'staff') {
                permRow.style.display = 'flex';
                document.getElementById('detail_allow_import_export').checked = u.allow_import_export == 1;
            } else {
                permRow.style.display = 'none';
            }

            // Lịch truy cập — luôn hiển thị, làm mờ khi chưa bật
            const schedRow       = document.getElementById('detail_sched_row');
            const schedEdit      = document.getElementById('detail_schedule_edit');
            const schedDisplay   = document.getElementById('detail_schedule_display');
            const schedToggleLbl = document.getElementById('detail_sched_toggle_label');
            const schedToggle    = document.getElementById('detail_has_schedule');
            const schedTimeFields = document.getElementById('detail_sched_time_fields');
            schedRow.style.display = 'flex';
            if (u.role === 'admin') {
                schedEdit.style.display = 'none';
                schedDisplay.innerHTML = '<span class="schedule_badge none">Không áp dụng</span>';
            } else if (window.APP_CONFIG?.isAdmin) {
                schedEdit.style.display = 'flex';
                schedDisplay.innerHTML = '';
                const hasSched = u.has_schedule == 1;
                schedToggle.checked = hasSched;
                schedTimeFields.style.display = 'block';
                document.getElementById('detail_sched_start').value = u.access_start ? u.access_start.substring(0,5) : '06:00';
                document.getElementById('detail_sched_end').value   = u.access_end   ? u.access_end.substring(0,5)   : '22:00';
                // Làm mờ khi chưa check
                schedEdit.style.opacity = hasSched ? '1' : '0.45';
                schedTimeFields.style.pointerEvents = hasSched ? 'auto' : 'none';
                if (u.role === 'staff') {
                    schedToggle.disabled = true;
                    schedToggle.checked = true;
                    schedEdit.style.opacity = '1';
                    schedTimeFields.style.pointerEvents = 'auto';
                    schedToggleLbl.style.opacity = '0.6';
                    schedToggleLbl.style.cursor = 'default';
                } else {
                    schedToggle.disabled = false;
                    schedToggleLbl.style.opacity = '1';
                    schedToggleLbl.style.cursor = 'pointer';
                }
            } else {
                schedEdit.style.display = 'none';
                if (u.has_schedule == 1 && u.access_start && u.access_end) {
                    schedDisplay.innerHTML = '<span class="schedule_badge active">'
                        + '<span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">schedule</span> '
                        + escapeHtml(u.access_start.substring(0,5)) + ' – ' + escapeHtml(u.access_end.substring(0,5))
                        + '</span>';
                } else {
                    schedDisplay.innerHTML = '<span class="schedule_badge none">Không giới hạn</span>';
                }
            }

            document.getElementById('detail_last_login').textContent = u.last_login
                ? new Date(u.last_login).toLocaleString('vi-VN')
                : '— Chưa đăng nhập';

            document.getElementById('detail_new_password').value = '';

            document.getElementById('userDetailModal').classList.add('open');
            document.getElementById('detail_fullname').focus();
        })
        .catch(() => showToast('Lỗi kết nối máy chủ.', 'error'));
}

// ── Toggle hiện/ẩn mật khẩu trong modal chi tiết ────────────────────────
function toggleDetailPassword(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const btn = input.parentElement.querySelector('.toggle_password');
    const icon = btn?.querySelector('.material-symbols-outlined');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.textContent = 'visibility_off';
    } else {
        input.type = 'password';
        if (icon) icon.textContent = 'visibility';
    }
}

// ── Phiên đăng nhập ─────────────────────────────────────────────────────
function loadSessions() {
    const list = document.getElementById('sessionList');
    list.innerHTML = '<div class="table_loading">Đang tải...</div>';

    apiFetch('modules/users/api/users.php?action=sessions')
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
                const roleLabel    = s.role === 'admin' ? 'Admin' : s.role === 'store_manager' ? 'Cửa hàng trưởng' : 'Nhân viên';
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
    apiFetch('modules/users/api/users.php?action=kick', { method: 'POST', body: fd })
        .then(data => { showToast(data.message, data.success ? 'success' : 'error'); if (data.success) loadSessions(); })
        .catch(err => showToast(err.message || 'Lỗi kết nối máy chủ.', 'error'));
}

// ── Cấp quyền truy cập tạm thời ────────────────────────────────────────
let _tempSelectedMinutes = 10;

function openTempAccessModal(userId, userName) {
    document.getElementById('temp_user_id').value = userId;
    document.getElementById('temp_user_name').textContent = userName;
    _tempSelectedMinutes = 10;
    document.querySelectorAll('.temp_duration_btn').forEach(function(btn) {
        btn.classList.toggle('active', parseInt(btn.dataset.minutes) === 10);
    });
    document.getElementById('tempAccessModal').classList.add('open');
}

function grantTempAccess() {
    var userId  = document.getElementById('temp_user_id').value;
    var btn     = document.getElementById('btnSubmitTempAccess');
    var fd      = new FormData();
    fd.append('user_id', userId);
    fd.append('minutes', _tempSelectedMinutes);
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang cấp...';
    apiFetch('modules/users/api/users.php?action=grant_temp_access', { method: 'POST', body: fd })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined">timer</span> Cấp quyền';
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) {
                document.getElementById('tempAccessModal').classList.remove('open');
                loadUsers();
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined">timer</span> Cấp quyền';
            showToast('Lỗi kết nối máy chủ.', 'error');
        });
}

function revokeTempAccess(userId) {
    if (!confirm('Thu hồi quyền truy cập tạm thời? Tài khoản sẽ bị khóa theo lịch trình ngay lập tức.')) return;
    var fd = new FormData();
    fd.append('user_id', userId);
    apiFetch('modules/users/api/users.php?action=revoke_temp_access', { method: 'POST', body: fd })
        .then(function(data) { showToast(data.message, data.success ? 'success' : 'error'); if (data.success) loadUsers(); })
        .catch(function(err) { showToast(err.message || 'Lỗi kết nối máy chủ.', 'error'); });
}

var _tempCountdownInterval = null;
function updateTempCountdowns() {
    if (_tempCountdownInterval) clearInterval(_tempCountdownInterval);
    _tempCountdownInterval = setInterval(function() {
        var badges = document.querySelectorAll('.schedule_badge.temp_active');
        if (badges.length === 0) { clearInterval(_tempCountdownInterval); return; }
        var now = new Date();
        badges.forEach(function(badge) {
            var until = new Date(badge.dataset.until);
            var diff  = Math.max(0, Math.floor((until - now) / 1000));
            if (diff <= 0) { loadUsers(); return; }
            var m = Math.floor(diff / 60);
            var s = diff % 60;
            badge.querySelector('.temp_countdown').textContent = 'Còn lại ' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        });
    }, 1000);
}

# Refactor Plan — Warehouse Manager

## Tổng quan
- **Thời gian tạo:** 2026-07-28
- **Mục tiêu:** Loại bỏ ~1.700 dòng code trùng lặp, tăng khả năng maintain
- **Phạm vi:** 20 file PHP, 3 file JS

---

## Phase 1 — Shared helpers & guards (nền tảng)

### 1.1 Tạo `shared/helpers.php`
Tạo file mới `shared/helpers.php` chứa các utility dùng chung:

```php
function requirePost(): void
// Kiểm tra REQUEST_METHOD === POST, nếu không thì trả JSON 405

function requireDb(mysqli $conn): void
// Kiểm tra $conn, nếu null thì trả JSON 500

function denyIfSelf(int $target_id): void
// Nếu $target_id === $_SESSION['user_id'] → trả JSON 403

function getUserRole(mysqli $conn, int $user_id): ?string
// SELECT role FROM users WHERE id = ? — trả role hoặc null

function isValidTime(string $time): bool
// Kiểm tra format HH:MM (00:00 - 23:59)

function renderCategoryOptions(array $categories, string $selected = ''): string
// Render HTML <option> cho danh mục

function deleteUserSessions(mysqli $conn, int $user_id): void
// DELETE FROM sessions WHERE user_id = ? — dùng ở login.php, users.php
```

### 1.2 Cập nhật `shared/config.php`
Thêm `require_once __DIR__ . '/helpers.php';` để mọi file đều có access.

### Files affected
- **Tạo mới:** `shared/helpers.php`
- **Sửa:** `shared/config.php` (thêm 1 dòng require)
- **Sửa:** `shared/auth.php` (xóa inline schedule check, gọi `checkAccessSchedule()` từ helpers)

---

## Phase 2 — Refactor `modules/users/api/users.php`

### 2.1 Thay thế boilerplate bằng helpers mới

| Pattern hiện tại (số lần) | Thay bằng |
|---|---|
| POST method guard 3 dòng (12 lần) | `requirePost()` |
| DB null check 3 dòng (11 lần) | `requireDb($conn)` |
| `SELECT role FROM users WHERE id = ?` (5 lần) | `getUserRole($conn, $target_id)` |
| Self-delete check (5 lần) | `denyIfSelf($target_id)` |
| Manual store_manager check (5 lần) | `checkStoreManagerTarget($conn, $target_id)` |
| Time regex (4 lần) | `isValidTime($time)` |
| Password length check (2 lần) | `strlen($pw) < 6` giữ nguyên, hoặc `isValidPassword()` |

### 2.2 Ước tính
- File hiện tại: ~759 dòng
- Sau refactor: ~550 dòng (giảm ~200 dòng)

### Files affected
- **Sửa:** `modules/users/api/users.php`
- **Sửa:** `modules/users/helpers.php` (di chuyển/dùng lại từ shared/helpers.php)

---

## Phase 3 — Refactor Import/Export stock API

### 3.1 Tạo `modules/stock/helpers.php`
Tạo file helper chứa logic dùng chung:

```php
function getStockList(mysqli $conn, string $type, array $filters): array
// type = 'import' | 'export'
// Xây dựng SQL query động dựa trên type và filters
// Trả về ['data' => [...], 'total' => int]

function getStockDetail(mysqli $conn, string $type, string $ma_phieu): array
// Lấy chi tiết phiếu nhập/xuất

function createStockReceipt(mysqli $conn, string $type, array $items, int $user_id): array
// Tạo phiếu nhập/xuất trong transaction
// type quyết định: +SoLuong (import) hay -SoLuong (export)
```

### 3.2 Refactor `import_stock.php` & `export_stock.php`
Thay thế 4 case blocks trùng lặp bằng gọi helper:

| Case hiện tại | Thay bằng |
|---|---|
| `list` (130 dòng × 2) | `getStockList($conn, $type, $filters)` |
| `detail` (55 dòng × 2) | `getStockDetail($conn, $type, $ma_phieu)` |
| `create_batch` (80 dòng × 2) | `createStockReceipt($conn, 'import', ...)` / `createStockReceipt($conn, 'export', ...)` |
| `create` (70 dòng × 2) | Giữ riêng vì import/export có logic khác (export cần check tồn kho) |

### 3.3 Ước tính
- 2 file hiện tại: ~780 dòng tổng
- Sau refactor: ~400 dòng tổng (giảm ~380 dòng)

### Files affected
- **Tạo mới:** `modules/stock/helpers.php`
- **Sửa:** `modules/stock/api/import_stock.php`
- **Sửa:** `modules/stock/api/export_stock.php`

---

## Phase 4 — Refactor Import/Export JS

### 4.1 Refactor `modules/stock/js/stock.js`
Thay thế 8 cặp hàm import/export đối xứng bằng hàm tham số hóa:

```javascript
// Thay 8 cặp hàm:
getImportList() / getExportList()        → getStorageList(type)
saveImportList() / saveExportList()      → saveStorageList(type, data)
addToImportList() / addToExportList()    → addToStorageList(type, item)
removeFromImportList/FromExportList()    → removeFromStorageList(type, id)
clearImportList() / clearExportList()    → clearStorageList(type)
quickAddFromImportList/quickAddFromExportList() → quickAddFromStorageList(type)
renderImportBatchTable/exportBatchTable() → renderBatchTable(type)
renderImportListSuggestions/exportListSuggestions() → renderListSuggestions(type)

loadImportHistory() / loadExportHistory() → loadHistory(type, page)
renderProductPagination() / renderHistoryPagination() → renderPagination(container, totalPages, currentPage, onClickName)
```

### 4.2 Ước tính
- File hiện tại: ~800 dòng
- Sau refactor: ~450 dòng (giảm ~350 dòng)

### Files affected
- **Sửa:** `modules/stock/js/stock.js`

---

## Phase 5 — Refactor Error Pages

### 5.1 Tạo `error.php` template chung
Thay thế 3 file `403.php`, `404.php`, `500.php` bằng 1 file:

```php
<?php
// error.php — Template chung cho error pages
// Usage: error.php?code=403  hoặc include với $error_code variable

$code = (int)($_GET['code'] ?? 500);

$configs = [
    403 => ['gradient_start' => '#E8937A', 'gradient_end' => '#F4C2A1', 'btn_bg' => '#C97B5A', 'msg' => 'Bạn không có quyền truy cập trang này!'],
    404 => ['gradient_start' => '#87CEEB', 'gradient_end' => '#B0E0E6', 'btn_bg' => '#5BA3D9', 'msg' => 'Oops, trang không tồn tại!'],
    500 => ['gradient_start' => '#9B8EA8', 'gradient_end' => '#C4B7CC', 'btn_bg' => '#7A6E86', 'msg' => 'Máy chủ gặp sự cố, vui lòng thử lại sau!'],
];

$config = $configs[$code] ?? $configs[500];
http_response_code($code);
?>
```

### 5.2 Cập nhật `.htaccess`
Thay thế 3 `ErrorDocument` directive bằng 1:
```apache
ErrorDocument 403 /error.php?code=403
ErrorDocument 404 /error.php?code=404
ErrorDocument 500 /error.php?code=500
```

### 5.3 Xóa các file cũ
- Xóa `403.php`
- Xóa `404.php`
- Xóa `500.php`

### 5.4 Ước tính
- 3 file hiện tại: ~750 dòng
- Sau refactor: ~250 dòng (giảm ~500 dòng)

### Files affected
- **Tạo mới:** `error.php`
- **Xóa:** `403.php`, `404.php`, `500.php`
- **Sửa:** `.htaccess`

---

## Phase 6 — Refactor UI duplications

### 6.1 Tạo `shared/components/category_options.php`
```php
<?php
// Render category <option> elements
// Requires: $categories_list, $selected (optional)
foreach ($categories_list as $row) {
    $sel = (($selected ?? '') === $row['MaDM']) ? ' selected' : '';
    echo '<option value="' . htmlspecialchars($row['MaDM']) . '"' . $sel . '>'
       . htmlspecialchars($row['TenDM']) . '</option>';
}
?>
```

### 6.2 Thay thế 4 chỗ render category dropdown
| File | Dòng | Thay bằng |
|---|---|---|
| `modals.php` (add product modal) | 118-120 | `<?php $selected = ''; include 'category_options.php'; ?>` |
| `modals.php` (edit product modal) | 279-281 | `<?php $selected = ''; include 'category_options.php'; ?>` |
| `tab_warehouse.php` (filter) | 24-26 | `<?php $selected = ''; include 'category_options.php'; ?>` |
| `tab_history.php` (filter) | 50-52 | `<?php $selected = ''; include 'category_options.php'; ?>` |

### 6.3 Ước tính
- Giảm ~12 dòng lặp, tăng maintainability khi đổi HTML structure

### Files affected
- **Tạo mới:** `shared/components/category_options.php`
- **Sửa:** `shared/components/modals.php`
- **Sửa:** `modules/products/tab_warehouse.php`
- **Sửa:** `modules/stock/tab_history.php`

---

## Phase 7 — Refactor JS submit pattern

### 7.1 Thêm utility function vào `shared/js/app.js`
```javascript
async function submitForm(btn, url, formData, { originalLabel, onSuccess, onError }) {
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined spin_icon">autorenew</span> Đang xử lý...';
    try {
        const res = await apiFetch(url, { method: 'POST', body: formData });
        showToast(res.message, res.success ? 'success' : 'error');
        if (res.success && onSuccess) onSuccess(res);
    } catch {
        showToast('Lỗi kết nối máy chủ.', 'error');
        if (onError) onError();
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalLabel;
    }
}
```

### 7.2 Thay thế ~12 handler trong JS
Mỗi handler hiện tại ~12 dòng → ~5 dòng gọi `submitForm()`

### 7.3 Ước tính
- Giảm ~80 dòng JS

### Files affected
- **Sửa:** `shared/js/app.js` (thêm function)
- **Sửa:** `modules/users/js/admin-users.js` (~6 handler)
- **Sửa:** `modules/products/js/products.js` (~3 handler)

---

## Tổng kết

| Phase | Mô tả | Dòng giảm ước tính | File tạo mới | File xóa |
|---|---|---|---|---|
| 1 | Shared helpers & guards | ~50 | `shared/helpers.php` | — |
| 2 | Refactor users.php | ~200 | — | — |
| 3 | Refactor Import/Export API | ~380 | `modules/stock/helpers.php` | — |
| 4 | Refactor Import/Export JS | ~350 | — | — |
| 5 | Error pages template | ~500 | `error.php` | `403.php`, `404.php`, `500.php` |
| 6 | UI duplications | ~12 | `shared/components/category_options.php` | — |
| 7 | JS submit pattern | ~80 | — | — |
| **Tổng** | | **~1,572 dòng** | **4 file mới** | **3 file xóa** |

---

## Thứ tự thực hiện

1. **Phase 1** (nền tảng) — phải làm trước vì các phase sau dùng shared helpers
2. **Phase 2** (users.php) — áp dụng helpers từ Phase 1
3. **Phase 5** (error pages) — độc lập, làm bất kỳ lúc nào
4. **Phase 3** (stock API) — tạo stock helpers
5. **Phase 4** (stock JS) — đi kèm Phase 3
6. **Phase 6** (UI) — nhanh, ít rủi ro
7. **Phase 7** (JS submit) — làm cuối cùng

## Lưu ý
- Mỗi phase nên commit riêng để dễ rollback
- Chạy lại toàn bộ chức năng sau mỗi phase để đảm bảo không regress
- Không thay đổi database schema
- Không thay đổi behavior/UI

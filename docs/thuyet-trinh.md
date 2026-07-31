# Tài liệu thuyết trình — CT428 Warehouse Manager

---

## 1. TỔNG QUAN DỰ ÁN

| Mục | Chi tiết |
|-----|----------|
| **Tên dự án** | Warehouse Manager (Hệ thống quản lý kho hàng) |
| **Môn học** | CT428 - Nhóm 10 |
| **Công nghệ** | PHP 8.2+ (thuần, không framework), MySQL, JavaScript thuần, Chart.js |
| **Mục tiêu** | Quản lý tồn kho, nhập/xuất hàng hóa, thống kê trực quan, phân quyền người dùng |
| **Loại ứng dụng** | Web app truyền thống (MPA + AJAX), chạy localhost |
| **Kiến trúc** | Procedural, layout hướng MVC (định tuyến thủ công) |
| **Front-end** | 14 CSS files, 6 JS files, Chart.js CDN |
| **Back-end** | 7 API endpoints, 12 partial views, 4 pages |
| **Database** | 8 tables, MySQL, charset utf8mb4 |

---

## 2. KIẾN TRÚC HỆ THỐNG

### 2.1. Tổng quan

Dự án gồm **~54 files** chia làm 4 tầng:

| Tầng | Thư mục | Số file | Công nghệ |
|------|---------|---------|-----------|
| Entry Point | `/` (root) | 6 | PHP, .htaccess |
| Frontend | `public/` | 20 | CSS (14), JS (6) |
| API | `api/` | 8 | PHP + .htaccess |
| Backend | `php/` | 19 | PHP (config, db, auth, helpers, Pages, partials) |
| Database | `warehouse_manager.sql` | 1 | MySQL |

### 2.2. Sơ đồ kiến trúc

```
┌──────────────────────────────────────────────────────────────────────┐
│                         index.php (Entry Point)                      │
│  session_start() → ?page=login | ?page=main → require php/Pages/*   │
│  Cache-Control: no-store                                            │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐    │
│  │                   TẦNG FRONTEND (public/)                    │    │
│  │                                                              │    │
│  │  ┌──────────────────────┐  ┌────────────────────────────┐    │    │
│  │  │  CSS (14 files)      │  │  JavaScript (6 files)      │    │    │
│  │  │                      │  │                            │    │    │
│  │  │  style.css (loader)  │  │  app.js       (core)       │    │    │
│  │  │  base.css            │  │  products.js   (SP CRUD)   │    │    │
│  │  │  layout.css          │  │  stock.js      (nhập/xuất) │    │    │
│  │  │  components.css      │  │  dashboard.js  (biểu đồ)   │    │    │
│  │  │  dashboard.css       │  │  combobox.js   (autocomplete│    │    │
│  │  │  table.css           │  │  admin-users.js(user CRUD) │    │    │
│  │  │  modal.css           │  └────────────────────────────┘    │    │
│  │  │  login.css           │                                     │    │
│  │  │  combobox.css        │  Chart.js CDN                       │    │
│  │  │  dropdown.css        │  Material Symbols CDN               │    │
│  │  │  history.css         │                                     │    │
│  │  │  users.css           │                                     │    │
│  │  │  user-detail.css     │                                     │    │
│  │  │  responsive.css      │                                     │    │
│  │  └──────────────────────┘                                     │    │
│  └──────────────────────────────────────────────────────────────┘    │
│                              │ AJAX (JSON)                          │
│                              ▼                                      │
│  ┌──────────────────────────────────────────────────────────────┐    │
│  │                   TẦNG API (api/)                            │    │
│  │                                                              │    │
│  │  .htaccess (chặn truy cập trực tiếp không AJAX)              │    │
│  │                                                              │    │
│  │  ┌────────────────────────────────────────────────────────┐  │    │
│  │  │  Endpoint              Hành động                       │  │    │
│  │  ├────────────────────────────────────────────────────────┤  │    │
│  │  │  filter_products.php   GET  — Search, sort, paginate   │  │    │
│  │  │  add_product.php       POST — Thêm SP                  │  │    │
│  │  │  edit_product.php      GET  — Detail                   │  │    │
│  │  │                        POST — Update / toggle / delete │  │    │
│  │  │  add_category.php      POST — Thêm danh mục            │  │    │
│  │  │  import_stock.php      POST — Nhập (đơn + batch)       │  │    │
│  │  │                        GET  — List / detail phiếu nhập │  │    │
│  │  │  export_stock.php      POST — Xuất (đơn + batch)       │  │    │
│  │  │                        GET  — List / detail phiếu xuất │  │    │
│  │  │  users.php             GET  — List / sessions / detail │  │    │
│  │  │                        POST — Create / toggle / delete │  │    │
│  │  │                              / kick / update / schedule│  │    │
│  │  │                              / reset_password          │  │    │
│  │  └────────────────────────────────────────────────────────┘  │    │
│  └──────────────────────────────────────────────────────────────┘    │
│                              │ require/include                       │
│                              ▼                                      │
│  ┌──────────────────────────────────────────────────────────────┐    │
│  │                   TẦNG BACKEND (php/)                        │    │
│  │                                                              │    │
│  │  ┌────────────────────────────────────────────────────────┐  │    │
│  │  │  Core Files                                           │  │    │
│  │  │  ───────────────────────────────────────────────────── │  │    │
│  │  │  config.php    — Hằng số DB, BASE_URL, timezone        │  │    │
│  │  │  db.php        — mysqli connection (utf8mb4)           │  │    │
│  │  │  auth.php      — Session, CSRF, role gates (170 dòng) │  │    │
│  │  │  helpers.php   — requirePost, requireDb, checkSchedule │  │    │
│  │  │  .htaccess     — Chặn truy cập backend trực tiếp       │  │    │
│  │  └────────────────────────────────────────────────────────┘  │    │
│  │                                                              │    │
│  │  ┌────────────────────────────────────────────────────────┐  │    │
│  │  │  Pages (Page Controllers)                              │  │    │
│  │  │  ───────────────────────────────────────────────────── │  │    │
│  │  │  login.php   (256 dòng) — Form + rate limiting + bcrypt│  │    │
│  │  │  index.php   (74 dòng)  — App shell: render tabs,     │  │    │
│  │  │                             inject APP_CONFIG + charts │  │    │
│  │  │  logout.php  (36 dòng)  — Hủy session + DB + cookie   │  │    │
│  │  │  error.php   (108 dòng) — 403/404/500 (animated)      │  │    │
│  │  └────────────────────────────────────────────────────────┘  │    │
│  │                                                              │    │
│  │  ┌────────────────────────────────────────────────────────┐  │    │
│  │  │  Partials (Views + Helpers)                           │  │    │
│  │  │  ───────────────────────────────────────────────────── │  │    │
│  │  │  head.php     — <head>: meta, fonts, Chart.js CDN      │  │    │
│  │  │  header.php   — Header + user dropdown                 │  │    │
│  │  │  sidebar.php  — Sidebar nav (ẩn/hiện theo role)       │  │    │
│  │  │  footer.php   — Toast container                        │  │    │
│  │  │  tab_dashboard.php — KPI cards, charts, 3 tables      │  │    │
│  │  │  tab_warehouse.php  — Filter bar + product table      │  │    │
│  │  │  tab_history.php    — Import/Export history + filters  │  │    │
│  │  │  tab_users.php      — Users table + sessions           │  │    │
│  │  │  modals.php (863 dòng) — 14 modal dialogs             │  │    │
│  │  │                                                       │  │    │
│  │  │  helpers-products.php — Validate SP (63 dòng)         │  │    │
│  │  │  helpers-stock.php    — Stock list/detail (209 dòng)  │  │    │
│  │  │  helpers-users.php    — Dashboard queries +           │  │    │
│  │  │                         checkManagerTarget (210 dòng) │  │    │
│  │  └────────────────────────────────────────────────────────┘  │    │
│  └──────────────────────────────────────────────────────────────┘    │
│                              │ mysqli                                │
│                              ▼                                      │
│  ┌──────────────────────────────────────────────────────────────┐    │
│  │                   TẦNG DATABASE (MySQL)                      │    │
│  │                                                              │    │
│  │  users (1) ─────── sessions (*)        [CASCADE DELETE]      │    │
│  │    │                                                         │    │
│  │  categories (1) ── products (*) → import_receipt_details (*) │    │
│  │        ↑                           ← import_receipts (1)     │    │
│  │        └─── export_receipt_details (*)                       │    │
│  │                                ← export_receipts (1)         │    │
│  │                                                              │    │
│  │  8 tables | utf8mb4 | InnoDB (transactions, FK, row locking) │    │
│  └──────────────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────────────┘
```

### 2.3. Luồng request

```
Trình duyệt: GET /index.php?page=main
  → index.php line 5-7: session_start()
  → index.php line 20-23: isset($_SESSION['user_id'])?
    → require 'php/Pages/index.php'
      → line 3-5: require config.php, db.php, auth.php
      → line 13: extract(getDashboardData($conn))
      → line 15: require head.php (meta, CDN, fonts)
      → line 18-19: require header.php, sidebar.php
      → line 22-25: require tab_dashboard, tab_warehouse, tab_history, tab_users
      → line 28: require modals.php (14 modals)
      → line 34-41: inject window.APP_CONFIG
      → line 44-54: load JS files (conditional theo role)
      → line 58-68: inject chart data (7 arrays)
      → line 70: load dashboard.js (draw charts)

Trình duyệt: GET /api/filter_products.php?search=&category=&page=1&limit=10
  → api/filter_products.php:
    line 3-4: require db.php, auth.php
    line 13-19: read & sanitize $_GET params
    line 21-22: check canViewProducts()
    line 24-40: build WHERE clause dynamically
    line 46-57: COUNT(*) query for pagination
    line 65-91: SELECT with JOIN, ORDER BY, LIMIT/OFFSET
    line 93-116: execute, build records array
    line 118-127: echo JSON

Trình duyệt: POST /api/add_product.php (FormData)
  → api/add_product.php:
    line 4-6: require db.php, auth.php, helpers-products.php
    line 7: requireAdminOrManager()
    line 16: verifyCsrfToken()
    line 24: validateProductInput($conn, $_POST, false)
    line 38-41: INSERT INTO products
    line 43-63: if stock_quantity > 0: INSERT import_receipts + details
    line 65-69: echo JSON {success, message, id}
```

### 2.4. Danh sách tất cả file/module

#### 2.4.1. Thư mục gốc `/`

| STT | File | Chức năng | Gọi từ / Liên quan đến |
|-----|------|-----------|----------------------|
| 1 | `index.php` | **Entry point duy nhất.** Khởi tạo session, set cache headers, đọc `?page=`, redirect login hoặc load app. | `php/Pages/login.php`, `php/Pages/index.php` |
| 2 | `.htaccess` | **Rewrite rules.** 403 cho `config.local.php`, 403 cho directory listing, 404 cho file không tồn tại → `php/Pages/error.php`. | `php/Pages/error.php` |
| 3 | `.gitignore` | Bỏ qua `DuLieu.xlsx` và `/diagrams` | — |
| 4 | `config.local.php` | **Override DB credentials** (KHÔNG commit). Định nghĩa DB_HOST, DB_USER, DB_PASS, DB_NAME. | `php/config.php` (line 3-5: require nếu tồn tại) |
| 5 | `warehouse_manager.sql` | **Full dump DB:** schema + dữ liệu mẫu (users, categories, products, receipts). | MySQL import |

#### 2.4.2. `php/` — Backend Core (4 files + .htaccess)

| STT | File | Chức năng | Gọi từ | Liên quan đến |
|-----|------|-----------|--------|---------------|
| 6 | `config.php` (34 dòng) | Load `config.local.php` hoặc dùng mặc định. Định nghĩa `ROOT_PATH`, `BASE_URL`. Set timezone `Asia/Ho_Chi_Minh`. Load `helpers.php`. | `db.php`, `php/Pages/login.php`, `php/Pages/index.php` | `config.local.php`, `helpers.php` |
| 7 | `db.php` (14 dòng) | **Kết nối MySQL** qua mysqli. Set charset utf8mb4. `$conn` global. Gán `$conn = null` nếu lỗi. | Mọi API + Pages | `config.php` |
| 8 | `auth.php` (170 dòng) | **Xác thực & phân quyền.** 10 functions: `generateCsrfToken()`, `verifyCsrfToken()`, `isAjaxRequest()`, `redirectToLogin()`, `isAdmin()`, `isManager()`, `canImportExport()`, `canViewProducts()`, `deny403()`, `requireAdmin()`, `requireAdminOrManager()`, `requireCanImportExport()`, `staffViewScope()`, `getCurrentUser()`. Tự động kiểm tra session token trong DB, access schedule. | `php/Pages/index.php`, mọi API | `config.php`, `db.php` (qua context), `helpers.php` (checkAccessSchedule) |
| 9 | `helpers.php` (97 dòng) | **Hàm tiện ích**: `requirePost()`, `requireDb()`, `denyIfSelf()`, `getUserRole()`, `isValidTime()`, `deleteUserSessions()`, `renderCategoryOptions()`, `checkAccessSchedule()`. | `api/users.php`, `api/import_stock.php`, `api/export_stock.php`, `modals.php` | — |
| — | `php/.htaccess` | Chặn truy cập trực tiếp vào `config.php`, `db.php`, `auth.php`, `helpers.php`, `helpers-*.php` bằng `Deny from all`. | Apache config | — |

#### 2.4.3. `php/Pages/` — Page Controllers (4 files)

| STT | File | Chức năng | Gọi từ | Liên quan đến |
|-----|------|-----------|--------|---------------|
| 10 | `login.php` (256 dòng) | **Trang đăng nhập.** Rate limiting (5 lần/5 phút). Password verify (bcrypt). Session regeneration. Tạo session token SHA256 → INSERT `sessions`. Update `last_login`. | `index.php` (line 20-21) | `config.php`, `db.php`, `helpers.php` (checkAccessSchedule) |
| 11 | `index.php` (74 dòng) | **App shell chính.** Load config + db + auth. Gọi `getDashboardData()`. Render head, header, sidebar, 4 tabs, modals, footer. Inject `window.APP_CONFIG`, chart data. Load JS files (có điều kiện theo role). | `index.php` (line 22-23) | `config.php`, `db.php`, `auth.php`, `helpers-users.php`, tất cả partials |
| 12 | `logout.php` (36 dòng) | **Đăng xuất.** Xóa session token khỏi DB. Hủy PHP session + cookie. Redirect về `index.php`. | Header user dropdown | `db.php` |
| 13 | `error.php` (108 dòng) | **Trang lỗi** 403/404/500. Hiển thị animated gradient + video + số lỗi responsive. | `.htaccess` (403, 404 redirect) | — |

#### 2.4.4. `php/partials/` — Views + Helpers (12 files)

**Layout Views (4 files):**

| STT | File | Chức năng | Gọi từ | Liên quan đến |
|-----|------|-----------|--------|---------------|
| 14 | `head.php` (19 dòng) | `<head>` HTML: meta, CSRF meta tag, CSS `style.css`, Google Fonts (Be Vietnam Pro), Material Symbols, Chart.js 4.4.1, Chart.js DataLabels 2.2.0 (cả CDN). | `php/Pages/index.php` line 15 | — |
| 15 | `header.php` (33 dòng) | **Header bar.** Logo, tên app, user dropdown (avatar, tên, role badge, nút đăng xuất). | `php/Pages/index.php` line 18 | `auth.php` (getCurrentUser) |
| 16 | `sidebar.php` (28 dòng) | **Sidebar nav.** 4 mục: Tổng quan, Kho hàng, Lịch sử (nếu có quyền), Quản lý nhân viên (nếu admin/manager). Gắn `data-tab` cho JS tab switching. | `php/Pages/index.php` line 20 | `auth.php` (canViewProducts, isAdmin, isManager) |
| 17 | `footer.php` (4 dòng) | Toast notification container `<div id="toastContainer">`. | `php/Pages/index.php` line 29 | `app.js` (showToast) |

**Tab Content (4 files):**

| STT | File | Chức năng | Liên quan đến |
|-----|------|-----------|---------------|
| 18 | `tab_dashboard.php` (252 dòng) | **Dashboard tab.** 8 KPI cards, 5 biểu đồ canvas, 3 bảng dữ liệu (sắp hết hàng, bán chạy, hoạt động gần đây). | `helpers-users.php` (getDashboardData), `dashboard.js` |
| 19 | `tab_warehouse.php` (122 dòng) | **Warehouse tab.** Filter bar (search, category, price sort, qty sort, limit), product table + pagination, nút [+ Thêm] (nếu có quyền), nút [+ Danh mục]. | `helpers-products.php`, `products.js`, `modals.php` |
| 20 | `tab_history.php` (141 dòng) | **History tab.** Tabs con (Nhập/Xuất), filter (search, category, date range, người tạo, giá). | `helpers-stock.php`, `stock.js` |
| 21 | `tab_users.php` (63 dòng) | **Users tab.** Bảng danh sách user, sessions đang hoạt động. Chỉ hiện nếu admin/manager. | `helpers-users.php` (checkManagerTarget), `admin-users.js` |

**Modal Definitions (1 file):**

| STT | File | Chức năng | Liên quan đến |
|-----|------|-----------|---------------|
| 22 | `modals.php` (863 dòng) | **14 modal dialogs**: tạo user, thêm SP, thêm danh mục, chi tiết SP (view + edit + history), nhập kho, xuất kho, cấp quyền staff, lịch truy cập, cấp quyền tạm thời, chi tiết user, chi tiết phiếu, nhập số lượng, confirm dialog. Render có điều kiện theo role. | `auth.php`, `helpers.php` (renderCategoryOptions), mọi JS file |

**Backend Helpers (3 files):**

| STT | File | Chức năng | Gọi từ | Liên quan đến |
|-----|------|-----------|--------|---------------|
| 23 | `helpers-products.php` (63 dòng) | **1 function**: `validateProductInput($conn, $post, $isEdit)` — validate tên (trống, ≤200), mô tả (≤1000), category_id (tồn tại trong DB), price (≥0), stock_quantity (≥0 nếu add). | `api/add_product.php`, `api/edit_product.php` | `db.php` |
| 24 | `helpers-stock.php` (209 dòng) | **3 functions**: `getStockTableConfig($type)` — cấu hình import/export, `getStockList($conn, $type)` — list phiếu có phân trang + filter, `getStockDetail($conn, $type, $receipt_id)` — chi tiết phiếu. | `api/import_stock.php`, `api/export_stock.php` | `db.php` |
| 25 | `helpers-users.php` (210 dòng) | **2 functions**: `getDashboardData($conn)` — 8 KPI queries + 5 chart queries + 3 table queries (tổng cộng ~20 SQL queries), `checkManagerTarget($conn, $target_id)` — kiểm tra manager có được phép thao tác trên target không. | `php/Pages/index.php` (line 6), `api/users.php` (line 6) | `db.php` |

#### 2.4.5. `api/` — API Endpoints (7 files + .htaccess)

| STT | File | Dòng | Method | Action | Chức năng | Gọi từ JS | Liên quan đến |
|-----|------|------|--------|--------|-----------|-----------|---------------|
| — | `api/.htaccess` | 17 | — | — | Chặn request không phải AJAX (thiếu `X-Requested-With: XMLHttpRequest` và `Accept: application/json`). Set `X-Frame-Options`, `X-Content-Type-Options`. | Apache | — |
| 26 | `filter_products.php` | 129 | GET | — | Search (LIKE name + description), filter category, sort price/qty, paginate. JOIN categories. Trả về records[], total, page, per_page, permissions. | `products.js` (fetchFilteredProducts, combobox) | `db.php`, `auth.php` |
| 27 | `add_product.php` | 78 | POST | — | Thêm SP mới. `requireAdminOrManager()`, `verifyCsrfToken()`, `validateProductInput()`. Nếu stock_quantity > 0: tự động INSERT import_receipts + import_receipt_details. | `products.js` (submitForm) | `db.php`, `auth.php`, `helpers-products.php` |
| 28 | `edit_product.php` | 260 | GET | get | Lấy thông tin SP (id, name, description, price, stock_quantity, category_id, is_active). | `products.js` (openProductDetail) | `db.php`, `auth.php`, `helpers-products.php` |
| | | | POST | update | Cập nhật SP (name, description, price, category_id). | `products.js` (submitEditProduct) | |
| | | | POST | toggle_active | Soft delete / restore (is_active = 0/1). | `products.js` (toggleProductActive) | |
| | | | POST | delete | Xóa cứng SP (transaction: xóa import_details → export_details → products). | `products.js` (deleteProduct) | |
| | | | GET | detail | Lấy chi tiết SP + lịch sử nhập/xuất (JOIN 4 tables). | `products.js` (openProductDetail) | |
| 29 | `add_category.php` | 80 | POST | — | Thêm danh mục mới. Validate mã (alphanumeric + underscore, 2-10 ký tự), kiểm tra trùng. Trả về danh sách categories mới. | `products.js` (btnSubmitAddCategory) | `db.php`, `auth.php` |
| 30 | `import_stock.php` | 197 | POST | create | Nhập kho đơn lẻ. Transaction: SELECT FOR UPDATE → INSERT receipt → INSERT detail → UPDATE stock +. | `stock.js` | `db.php`, `auth.php`, `helpers-stock.php` |
| | | | POST | create_batch | Nhập kho hàng loạt (tối đa 50 SP). JSON items. Transaction, FOR UPDATE, auto-rollback khi lỗi. | `stock.js` (submitImport) | |
| | | | GET | list | Danh sách phiếu nhập (phân trang + filter) | `stock.js` | |
| | | | GET | detail | Chi tiết phiếu nhập (JOIN products, users) | `stock.js` (openReceiptDetail) | |
| 31 | `export_stock.php` | 222 | POST | create | Xuất kho đơn lẻ. Transaction: SELECT FOR UPDATE (kiểm tra tồn) → INSERT receipt → INSERT detail → UPDATE stock - (WHERE stock_quantity >= ?). | `stock.js` | `db.php`, `auth.php`, `helpers-stock.php` |
| | | | POST | create_batch | Xuất kho hàng loạt. Giống import nhưng kiểm tra tồn + trừ stock. | `stock.js` (submitExport) | |
| | | | GET | list | Danh sách phiếu xuất (phân trang + filter) | `stock.js` | |
| | | | GET | detail | Chi tiết phiếu xuất | `stock.js` | |
| 32 | `users.php` | 611 | GET | list | Danh sách user (Manager chỉ thấy staff). JOIN creator, ORDER BY role. | `admin-users.js` | `db.php`, `auth.php`, `helpers-users.php` |
| | | | POST | create | Tạo user. Validate username (regex), password (≥6, bcrypt cost=12), role, schedule. Manager không tạo được admin. | `admin-users.js` | |
| | | | POST | toggle | Active/Inactive user. `denyIfSelf()`, `checkManagerTarget()`. Nếu inactive → xóa sessions. | `admin-users.js` | |
| | | | POST | delete | Xóa user (không xóa admin). | `admin-users.js` | |
| | | | GET | sessions | Danh sách phiên đang hoạt động. Manager chỉ thấy staff + chính mình. | `admin-users.js` | |
| | | | POST | kick | Xóa session của user khác (giữ session hiện tại). | `admin-users.js` | |
| | | | POST | update_permissions | Cấp/thu hồi allow_import_export cho staff. | `admin-users.js` | |
| | | | POST | update_schedule | Cập nhật lịch truy cập (has_schedule, access_start/end). | `admin-users.js` | |
| | | | POST | reset_password | Reset password + xóa tất cả sessions user đó. | `admin-users.js` | |
| | | | POST | update | Cập nhật thông tin user (full_name, role, schedule, permissions). | `admin-users.js` | |
| | | | POST | grant_temp_access | Cấp quyền truy cập tạm thời (1-120 phút). | `admin-users.js` | |
| | | | POST | revoke_temp_access | Thu hồi quyền truy cập tạm thời. | `admin-users.js` | |

#### 2.4.6. `public/css/` — Stylesheets (14 files)

| STT | File | Chức năng |
|-----|------|-----------|
| 33 | `style.css` | **Loader chính.** `@import` tất cả CSS files khác. Import `base.css`, `layout.css`, `components.css`, `dashboard.css`, `table.css`, `modal.css`, `combobox.css`, `dropdown.css`, `login.css`, `history.css`, `users.css`, `user-detail.css`, `responsive.css`. |
| 34 | `base.css` | Reset CSS, variables (colors, spacing, shadows), body/html base, typography (Be Vietnam Pro), scrollbar styles. |
| 35 | `layout.css` | Grid layout chính: sidebar (fixed 250px) + main content (flex), header bar, responsive breakpoints. |
| 36 | `components.css` | Reusable components: buttons (`.btn_primary`, `.btn_secondary`, `.btn_icon`), form inputs, badges, KPIs, tooltips, animations. |
| 37 | `dashboard.css` | Dashboard layout: KPI cards grid, chart containers, tables trong dashboard, responsive chart sizing. |
| 38 | `table.css` | Bảng dữ liệu: `.data_table`, `.product_table`, `.users_table`, header styling, row hover, cell padding, scrollable wrapper. |
| 39 | `modal.css` | Modal overlay + card: `.modal_overlay` (fixed, flex center), `.modal_card` (các size: sm/md/lg), header/body/footer, open/close animation. |
| 40 | `combobox.css` | Custom combobox: wrapper, dropdown (absolute positioned, z-index), items, highlight, clear button. |
| 41 | `dropdown.css` | User dropdown menu: trigger button, dropdown menu (position absolute), animation, user info section. |
| 42 | `login.css` | Login page: background gradient + circles, brand section (left), card (right), form inputs, alert messages, responsive. |
| 43 | `history.css` | Lịch sử tab: filter bar, sub-tabs, date inputs, receipt detail layout. |
| 44 | `users.css` | Users management tab: user cards, session list, action buttons, permission toggles. |
| 45 | `user-detail.css` | User detail modal: info grid, detail rows, inline edit, schedule time inputs. |
| 46 | `responsive.css` | Media queries: tablet (≤1024px), mobile (≤768px). Ẩn sidebar, chuyển layout, thu nhỏ font/khoảng cách. |

#### 2.4.7. `public/js/` — JavaScript (6 files)

| STT | File | Dòng | Chức năng | Sử dụng | Liên quan đến |
|-----|------|------|-----------|---------|---------------|
| 47 | `app.js` | 208 | **Core library.** `ajaxCall()` — XHR wrapper (Promise, CSRF, 401 handling, JSON parse). `showToast()` — toast notification (tự động ẩn 3.5s). `showConfirm()` — custom confirm modal (callback pattern). `submitForm()` — async form submit (loading, toast, callbacks). `escapeHtml()`, `number_format()`. Tab switching (localStorage). | Mọi JS file | `modals.php` (confirm, toast containers), `footer.php` (toast container) |
| 48 | `products.js` | 582 | **Quản lý sản phẩm.** `getProductStatus()` — badge class/text. `fetchFilteredProducts()` — filter + AJAX + render bảng + pagination. CRUD: add, edit, detail, toggle, delete. Category: add + refresh. Xử lý combobox filter với debounce. | `tab_warehouse.php`, `modals.php` | `app.js` (ajaxCall, submitForm, showConfirm), `api/filter_products.php`, `api/add_product.php`, `api/edit_product.php` |
| 49 | `stock.js` | 592 | **Nhập/xuất kho.** Batch processing với localStorage (warehouse_import_list, warehouse_export_list). Single + batch import/export. Autocomplete combobox (nhập/xuất). Receipt list + detail. `LIST_CONFIG` pattern (import/export dùng chung code). | `modals.php` (importModal, exportModal, receiptDetailModal) | `app.js`, `combobox.js`, `api/import_stock.php`, `api/export_stock.php` |
| 50 | `dashboard.js` | ~200 | **Biểu đồ Chart.js.** 5 charts: bar (SP/danh mục), donut (giá trị/danh mục), line (xu hướng 6 tháng), donut (trạng thái kho), horizontal bar (top 5 bán chạy). Dùng Chart.js DataLabels plugin. | `tab_dashboard.php` | Chart.js CDN, `php/Pages/index.php` (chart data variables) |
| 51 | `combobox.js` | ~200 | **Autocomplete combobox.** Debounce 200ms. Gọi `filter_products.php?search=...` → dropdown gợi ý. Keyboard navigation (↑↓ Enter). Click/chọn → tự điền ID + tên. Clear button. Dùng cho cả import và export. | `stock.js`, `modals.php` | `app.js` (ajaxCall), `api/filter_products.php` |
| 52 | `admin-users.js` | ~400 | **Quản lý user.** CRUD: create, update, toggle active, delete. Sessions: list, kick. Permissions: import/export, schedule, temp access. Reset password. Bulk operations. | `tab_users.php`, `modals.php` | `app.js` (ajaxCall, submitForm, showConfirm), `api/users.php` |

#### 2.4.8. Tài liệu

| STT | File | Chức năng |
|-----|------|-----------|
| 53 | `docs/thuyet-trinh.md` | Tài liệu hỗ trợ thuyết trình (15 mục, ~500 dòng) |

### 2.5. Bảng tổng hợp dependency giữa các tầng

```
index.php
  ├── php/Pages/login.php          → config.php → db.php → helpers.php
  ├── php/Pages/index.php
  │     ├── php/config.php         → config.local.php / defaults → helpers.php
  │     ├── php/db.php             → config.php
  │     ├── php/auth.php           → config.php (session) → helpers.php (checkAccessSchedule)
  │     ├── php/partials/helpers-users.php → db.php
  │     ├── php/partials/head.php
  │     ├── php/partials/header.php
  │     ├── php/partials/sidebar.php
  │     ├── php/partials/tab_dashboard.php
  │     ├── php/partials/tab_warehouse.php
  │     ├── php/partials/tab_history.php
  │     ├── php/partials/tab_users.php
  │     ├── php/partials/modals.php
  │     ├── php/partials/footer.php
  │     └── public/js/*            (6 files, load theo role)
  └── php/Pages/logout.php         → db.php

Các API file: (mỗi file)
  ├── php/db.php                   → config.php
  ├── php/auth.php                 → config.php (session) → helpers.php
  └── php/partials/helpers-*.php   → db.php

JS dependencies:
  app.js (core) ← products.js ← stock.js ← combobox.js
               ← dashboard.js (Chart.js CDN)
               ← admin-users.js


---

## 3. CƠ SỞ DỮ LIỆU

### 3.1. Danh sách bảng

| Bảng | Ý nghĩa | Cột PK | Cột FK | Cột quan trọng khác |
|------|---------|--------|--------|-------------------|
| `users` | Người dùng | id (INT AI) | — | username (UNIQUE), password (VARCHAR 255, bcrypt), full_name, role (ENUM: admin/manager/staff), is_active, allow_import_export, access_start/end (TIME), temp_access_until (DATETIME) |
| `sessions` | Phiên đăng nhập | id (INT AI) | user_id → users.id (CASCADE DELETE) | session_token (VARCHAR 64, SHA256, UNIQUE), ip_address, user_agent, created_at, expires_at |
| `categories` | Danh mục | id (VARCHAR 10, e.g. DTH, LAP) | — | name (VARCHAR 100) |
| `products` | Sản phẩm | id (INT AI) | category_id → categories.id | name (VARCHAR 200), description (TEXT), price (DECIMAL 15,0), stock_quantity (INT 0), is_active (TINYINT 1) |
| `import_receipts` | Phiếu nhập | id (VARCHAR 50, e.g. PN_20260701_235959_1) | created_by → users.id | created_at (DATETIME) |
| `import_receipt_details` | Chi tiết nhập | id (INT AI) | receipt_id → import_receipts.id, product_id → products.id | quantity (INT), notes (TEXT) |
| `export_receipts` | Phiếu xuất | id (VARCHAR 50, e.g. PX_20260727_...) | created_by → users.id | created_at (DATETIME) |
| `export_receipt_details` | Chi tiết xuất | id (INT AI) | receipt_id → export_receipts.id, product_id → products.id | quantity (INT), notes (TEXT) |

### 3.2. Quan hệ (ER Diagram dạng text)

```
categories (1) ─────< products (n)
  PK: id                  FK: category_id

products (1) ─────< import_receipt_details (n)
  PK: id                  FK: product_id

import_receipts (1) ──< import_receipt_details (n)
  PK: id                  FK: receipt_id

products (1) ─────< export_receipt_details (n)
  PK: id                  FK: product_id

export_receipts (1) ──< export_receipt_details (n)
  PK: id                  FK: receipt_id

users (1) ─────< import_receipts (n)
  PK: id                  FK: created_by

users (1) ─────< export_receipts (n)
  PK: id                  FK: created_by

users (1) ─────< sessions (n)
  PK: id                  FK: user_id (CASCADE DELETE)
```

### 3.3. Đặc điểm thiết kế

- **PK của receipt:** Dạng mã tự sinh `PN_AUTO_YYYYMMDDHHIISS_{product_id}` hoặc `PN_YYYYMMDD_HHIISS` — có ý nghĩa, dễ tra cứu
- **Giá:** DECIMAL(15,0) — không lẻ thập phân (phù hợp VND)
- **is_active (soft delete):** Thay vì xóa thật, set is_active = 0
- **sessions:** Lưu IP + User-Agent để phát hiện bất thường; tự động xóa khi user bị xóa (CASCADE)

---

## 4. PHÂN QUYỀN (RBAC)

### 4.1. Ba vai trò

| Role | Mô tả | Gọi hàm auth | Được làm |
|------|-------|-------------|----------|
| **Admin** | Quản trị hệ thống | `isAdmin()` | Toàn quyền: CRUD user, CRUD SP, nhập/xuất, xem thống kê, kick session, reset password |
| **Manager** | Quản lý kho | `isManager()` | Thêm/sửa SP, nhập/xuất, xem thống kê. **KHÔNG** được tạo/sửa Admin. Chỉ quản lý Staff |
| **Staff** | Nhân viên | — | Chỉ xem được khi được cấp quyền: `canViewProducts()`, `canImportExport()`. Có thể giới hạn theo khung giờ |

### 4.2. Cơ chế kiểm tra quyền

**Server-side (bắt buộc, mỗi API đều check):**
```php
// php/auth.php
function requireAdmin(): void {
    if (!isAdmin()) deny403();
}
function requireAdminOrManager(): void {
    if (!isAdmin() && !isManager()) deny403();
}
function canImportExport(): bool {
    if (isAdmin() || isManager()) return true;
    return ($_SESSION['allow_import_export'] ?? 0) == 1;
}
function canViewProducts(): bool { return true; } // Tất cả role đều xem được
```

**Client-side (hỗ trợ UX, không thay thế server):**
```js
// window.APP_CONFIG được PHP inject vào đầu trang
// Ví dụ products.js dùng:
const canManage = APP_CONFIG.can_manage_products;
const canView   = APP_CONFIG.can_view_products;

// Ẩn nút [Thêm sản phẩm] nếu không có quyền
```

### 4.3. Access schedule (Staff)

Staff có thể bị giới hạn khung giờ truy cập:
- `access_start` / `access_end` (TIME) — khung giờ được phép thao tác
- `temp_access_until` (DATETIME) — cho phép truy cập tạm thời đến thời điểm này
- Kiểm tra trong `auth.php` khi xác thực session

---

## 5. BẢO MẬT

### 5.1. Các biện pháp đã triển khai

| Biện pháp | Chi tiết | File |
|-----------|----------|------|
| **Bcrypt** | `password_hash(..., PASSWORD_BCRYPT, ['cost' => 12])` | `api/users.php` |
| **CSRF Token** | Double-submit cookie: sinh token lưu session → gửi header `X-CSRF-Token` → verify mỗi POST | `php/auth.php` → `verifyCsrfToken()` |
| **Prepared Statements** | Tất cả SQL đều dùng `$stmt = $conn->prepare()` + bind_param | Mọi API files |
| **Session HttpOnly** | Cookie: HttpOnly, SameSite=Strict — không đọc được từ JS | `php/auth.php` |
| **Rate limiting login** | 5 lần sai trong 5 phút → lock IP | `php/Pages/login.php` |
| **Session rotation** | Tạo session mới sau login thành công (tránh session fixation) | `php/Pages/login.php` |
| **IP + UA check** | Lưu IP và User-Agent khi tạo session, kiểm tra mỗi request | `php/auth.php` |
| **Cache headers** | `no-store, no-cache, must-revalidate, max-age=0` — chặn cache trang auth | `index.php` |
| **Permission gates** | Mỗi API gọi `requireAdmin()` / `requireAdminOrManager()` trước khi xử lý | Mọi API files |
| **Input validation** | Server-side validate: kiểu, độ dài, giá trị, tồn tại trong DB | `helpers-products.php`, từng API |
| **Output escaping** | `escapeHtml()` (htmlspecialchars) cho mọi output PHP lên HTML | `helpers.php` |

### 5.2. Luồng CSRF Token

```
1. index.php: generateCsrfToken()
   → Token lưu trong $_SESSION['csrf_token']

2. HTML <head>:
   <script>const _csrfToken = '...';</script>

3. JS ajaxCall():
   xhr.setRequestHeader('X-CSRF-Token', _csrfToken);

4. API PHP (mỗi POST):
   verifyCsrfToken();
   → Lấy token từ header, so sánh với $_SESSION['csrf_token']
   → Nếu không khớp: die(403 JSON)
```

---

## 6. CÁC CHỨC NĂNG CHÍNH

### 6.1. Dashboard (Trang tổng quan)

**KPI Cards (8 thẻ):**
1. Tổng danh mục — `SELECT COUNT(*) FROM categories`
2. Tổng sản phẩm — `SELECT COUNT(*) FROM products WHERE is_active = 1`
3. Tổng giá trị kho — `SELECT SUM(price * stock_quantity) FROM products WHERE is_active = 1`
4. Sắp hết hàng — `SELECT COUNT(*) FROM products WHERE stock_quantity < 30 AND stock_quantity > 0 AND is_active = 1`
5. Hết hàng — `SELECT COUNT(*) FROM products WHERE stock_quantity <= 0 AND is_active = 1`
6. Nhập tháng này — `SELECT COALESCE(SUM(d.quantity), 0) FROM import_receipts r JOIN import_receipt_details d ON r.id = d.receipt_id WHERE MONTH(r.created_at) = MONTH(CURRENT_DATE) AND YEAR(r.created_at) = YEAR(CURRENT_DATE)`
7. Xuất tháng này — tương tự với export_receipts
8. Doanh thu tháng — `SELECT COALESCE(SUM(d.quantity * p.price), 0) FROM export_receipts r JOIN export_receipt_details d ... JOIN products p ...`

**5 Biểu đồ Chart.js:**

| Biểu đồ | Loại | Dữ liệu | Xử lý PHP |
|---------|------|---------|-----------|
| Số lượng SP theo danh mục | Bar | categories LEFT JOIN products, COUNT | `helpers-users.php:138-150` |
| Giá trị tồn kho theo danh mục | Donut | categories LEFT JOIN products, SUM(price*stock_quantity) | `helpers-users.php:153-166` |
| Xu hướng nhập/xuất 6 tháng | Line | 6 tháng gần nhất, SUM quantity cho mỗi tháng | `helpers-users.php:37-87` |
| Phân loại trạng thái kho | Donut | COUNT theo trạng thái (sắp hết, hết, còn) | `helpers-users.php:89-99` |
| Top 5 sản phẩm bán chạy | Horizontal Bar | export_receipt_details GROUP BY product_id, SUM, LIMIT 5 | `helpers-users.php:168-184` |

**3 Bảng dưới cùng:**
- Sản phẩm sắp hết hàng (stock < 30, > 0)
- Sản phẩm bán chạy nhất (top 5 theo số lượng xuất)
- Hoạt động gần đây (5 nhập/xuất gần nhất, UNION)

### 6.2. Quản lý sản phẩm (Warehouse tab)

**Giao diện chính:**
- **Bộ lọc:** Search (tên + mô tả LIKE), danh mục (dropdown), sort (giá/số lượng ASC/DESC), phân trang (limit 10)
- **Bảng sản phẩm:** ID, Tên, Danh mục, Mô tả (cắt ngắn), Giá (format VND), Số lượng, Trạng thái (badge), Thao tác
- **Badge trạng thái:**

| Điều kiện | CSS class | Hiển thị |
|-----------|-----------|----------|
| `is_active == 0` | `hidden_product` | Ngừng kinh doanh (xám) |
| `stock_quantity <= 0` | `out_of_stock` | Hết hàng (đỏ) |
| `stock_quantity < 30` | `low_stock` | Sắp hết (cam) |
| Còn lại | `in_stock` | Còn hàng (xanh) |

**Chức năng CRUD:**

| Thao tác | API | JS function | Modal |
|----------|-----|-------------|-------|
| Thêm SP | `POST /api/add_product.php` | `products.js:459` → `submitForm()` | `#addProductModal` |
| Xem chi tiết | `GET /api/edit_product.php?action=get&id=X` | `products.js:97` → `openProductDetail(id)` | `#productDetailModal` |
| Sửa SP | `POST /api/edit_product.php?action=update` | `products.js:148` | (trong detail modal) |
| Xóa mềm (toggle) | `POST /api/edit_product.php?action=toggle_active` | `products.js:179` → `toggleProductActive(id)` | (không modal, xong refresh) |
| Xóa cứng | `POST /api/edit_product.php?action=delete` | `products.js:179` | `showConfirm()` trước khi xóa |

**Filter/Sort/Paginate (filter_products.php):**
```
Input:  ?search=...&category=...&sort_field=price&sort_dir=ASC&page=1
Output: {
  "records": [ { id, name, category_name, description, price, stock_quantity, is_active } ],
  "total": 45,
  "page": 1,
  "per_page": 10,
  "can_manage_products": true,
  "can_view_products": true
}
```

### 6.3. Nhập kho (Import tab)

**Luồng chi tiết:**

```
1. User mở tab Import
   → stock.js: khởi tạo import tab, check localStorage
   → Nếu có danh sách cũ (warehouse_import_list) → hỏi "Tiếp tục hay xóa?"

2. User thêm sản phẩm vào phiếu:
   a. Gõ tên SP vào combobox
   b. combobox.js: gọi GET /api/filter_products.php → dropdown gợi ý
   c. Chọn SP → hiện input số lượng + ghi chú
   d. Click [Thêm vào phiếu]
   e. stock.js: validate → thêm vào mảng list → lưu localStorage → render bảng tạm

3. User có thể:
   - Sửa số lượng từng dòng
   - Xóa SP khỏi phiếu
   - Xóa toàn bộ danh sách
   - Thêm nhiều SP (batch)

4. User click [Xác nhận nhập kho]:
   → Gọi POST /api/import_stock.php
   → Server:
     a. Bắt đầu transaction
     b. Tạo import_receipt (INSERT)
     c. FOR EACH product trong list:
        - SELECT ... FOR UPDATE (khóa dòng)
        - UPDATE products SET stock_quantity = stock_quantity + ?
        - INSERT import_receipt_details
     d. Commit transaction
   → Thành công: xóa localStorage list, showToast, refresh
   → Lỗi: rollback, showToast lỗi
```

**Code pattern batch list trong stock.js:**
```js
const LIST_CONFIG = {
    import: {
        key: 'warehouse_import_list',
        title: 'Nhập kho',
        addMsg: 'Đã thêm vào phiếu nhập.',
        clearDoneMsg: 'Đã nhập kho thành công!',
        api: BASE + '/api/import_stock.php'
    },
    export: {
        key: 'warehouse_export_list',
        title: 'Xuất kho',
        addMsg: 'Đã thêm vào phiếu xuất.',
        clearDoneMsg: 'Đã xuất kho thành công!',
        api: BASE + '/api/export_stock.php'
    }
};
```

### 6.4. Xuất kho (Export tab)

Tương tự Import nhưng điểm khác:

| Import | Export |
|--------|--------|
| `stock_quantity = stock_quantity + ?` | `stock_quantity = stock_quantity - ?` |
| Không cần kiểm tra tồn trước | **Phải kiểm tra:** `WHERE stock_quantity >= ?` và `affected_rows === 1` |
| Giá trị tồn kho tăng | Giá trị tồn kho giảm (doanh thu) |
| Mã phiếu: `PN_...` | Mã phiếu: `PX_...` |

### 6.5. Quản lý người dùng (Admin tab)

**Chức năng:**
- Danh sách user: bảng với role, trạng thái active, quyền import/export
- Thêm user: modal với form (username, password, full_name, role, permissions)
- Sửa user: modal tương tự, không cho sửa username
- Reset password: modal riêng, yêu cầu nhập mật khẩu mới
- Toggle active: vô hiệu hóa / kích hoạt user
- Xem sessions: danh sách session đang hoạt động của user (IP, thời gian, User-Agent)
- Kick session: xóa session từ xa (DELETE CASCADE)
- Bulk operations: chọn nhiều user → batch action

**Phân quyền trên user management:**
- Admin: CRUD mọi user (kể cả Admin khác, Manager, Staff)
- Manager: Chỉ CRUD được Staff. **KHÔNG** thấy/sửa được Admin hoặc Manager khác

### 6.6. Quản lý danh mục (Categories)

- Modal nhỏ: nhập mã (ID, 2-10 ký tự) + tên danh mục
- Gọi `POST /api/add_category.php` → validate → INSERT
- Refresh danh sách category trong dropdown filter và form

### 6.7. Lịch sử (History tab)

- Hai tab con: Nhập kho / Xuất kho
- Bảng danh sách phiếu: ID phiếu, ngày tạo, người tạo, tổng số lượng, tổng giá trị
- Click vào phiếu → modal chi tiết: danh sách sản phẩm trong phiếu (tên, số lượng, ghi chú)

---

## 7. LUỒNG XỬ LÝ CHI TIẾT

### 7.1. Luồng đăng nhập

```
1. GET /index.php (chưa login) → ?page=login → php/Pages/login.php
2. Render form: username + password
3. User submit POST (tự submit, không AJAX)
4. Server:
   a. Kiểm tra rate limiting (5 lần/5 phút theo IP)
   b. Kiểm tra captcha (nếu có)
   c. Query: SELECT * FROM users WHERE username = ? AND is_active = 1
   d. password_verify()
   e. Nếu đúng:
      - session_regenerate_id() (tránh session fixation)
      - Lưu user info vào $_SESSION
      - Tạo record trong bảng sessions (token SHA256)
      - Set cookie (HttpOnly, SameSite=Strict)
      - Redirect đến index.php?page=main
   f. Nếu sai: tăng counter, hiển thị lỗi
```

### 7.2. Luồng thêm sản phẩm mới

```
User click [+ Thêm]
  → products.js: openAddProdModal()
  → modals.php: #addProductModal hiện lên (class 'open')
  → Focus vào ô new_prod_name

User nhập: tên, danh mục (dropdown), giá, số lượng, mô tả
User click [Thêm sản phẩm]
  → products.js:459-496 handler click
    → Client-side validate:
      - name không trống
      - price là số không âm
      - quantity là số nguyên không âm
    → Tạo FormData: name, category_id, price, stock_quantity, description
    → submitForm(btn, '/api/add_product.php', fd, { onSuccess })
      → app.js:193 submitForm()
        → btn disabled, loading text
        → ajaxCall() POST → XHR với CSRF token
          → Server: api/add_product.php
            1. require config, db, auth, helpers-products
            2. requireAdminOrManager()
            3. verifyCsrfToken()
            4. validateProductInput($conn, $_POST)
               - Trim, cast các field
               - Check: name không trống, ≤ 200 ký tự
               - Check: description ≤ 1000 ký tự
               - Check: category_id tồn tại trong DB (SELECT id FROM categories WHERE id = ?)
               - Check: price ≥ 0
               - Check: stock_quantity ≥ 0
            5. INSERT INTO products (name, category_id, description, price, stock_quantity)
               VALUES (?, ?, ?, ?, ?)
            6. Nếu stock_quantity > 0: tự động tạo phiếu nhập
               - INSERT import_receipts (id, created_by, created_at)
                 id = 'PN_AUTO_' . date('YmdHis') . '_' . $product_id
               - INSERT import_receipt_details (receipt_id, product_id, quantity, notes)
                 notes = 'Nhập kho ban đầu'
            7. Trả JSON { success: true, message: '...', id: ... }
          → ajaxCall resolve(data) → submitForm:
            - showToast(data.message, 'success')
            - onSuccess(data)
              → products.js: closeAddProdModal() (reset form, ẩn modal)
              → fetchFilteredProducts(currentPage) → refresh bảng
```

### 7.3. Luồng nhập kho batch (Import)

```
1. Mở tab Import → stock.js khởi tạo
2. Load localStorage.getItem('warehouse_import_list')
3. Nếu có dữ liệu cũ → showConfirm('Tiếp tục?') → giữ hoặc xóa

4. User gõ tên SP:
   → Combobox gọi GET /api/filter_products.php?search=...&limit=10
   → Hiển thị dropdown gợi ý
   → Chọn SP → hiện form nhập số lượng + ghi chú

5. User click [Thêm vào phiếu]:
   → Validate: số lượng > 0
   → Kiểm tra: SP đã có trong list? → showToast('Đã có trong phiếu', 'error')
   → Thêm vào mảng list (JS)
   → Render bảng tạm (renderImportList() hoặc tương tự)
   → localStorage.setItem('warehouse_import_list', JSON.stringify(list))

6. User click [Xác nhận nhập kho]:
   → Gọi POST /api/import_stock.php (FormData: list JSON)
   → Server (api/import_stock.php):
     $conn->begin_transaction();
     INSERT import_receipts (id, created_by, created_at) VALUES (...);
     foreach (products in list):
       $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE");
       // Lock dòng, tránh race condition
       $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
       $stmt = $conn->prepare("INSERT import_receipt_details (receipt_id, product_id, quantity, notes) VALUES (?, ?, ?, ?)");
     $conn->commit();
   → Response: { success: true, message: '...' }
   → Xóa localStorage list
   → showToast('Thành công')
   → Cập nhật UI (clear bảng tạm, refresh)
```

---

## 8. KỸ THUẬT NỔI BẬT

### 8.1. SPA-like với PHP thuần (Tab System)

```js
// app.js:10-35 — Tab switching
function switchTab(tabId) {
    // Ẩn tất cả tab content
    document.querySelectorAll('.tab_content').forEach(t => t.classList.remove('active'));
    // Hiện tab được chọn
    const target = document.getElementById('tab_' + tabId);
    if (target) target.classList.add('active');
    // Update active state trên sidebar
    document.querySelectorAll('.sidebar_item').forEach(i => i.classList.remove('active'));
    // Lưu vào localStorage
    localStorage.setItem('activeTab', tabId);
}

// Khi load trang, đọc tab từ localStorage
const savedTab = localStorage.getItem('activeTab') || 'dashboard';
switchTab(savedTab);
```

- Tất cả tab content được PHP render sẵn trong HTML (hidden bằng CSS)
- Chuyển tab bằng JS (add/remove class `active`) — không cần gọi server
- Tab đang xem được lưu vào localStorage → nhớ sau khi reload

### 8.2. Batch Processing + localStorage

Cho phép user thêm nhiều sản phẩm vào phiếu tạm trước khi submit:

```js
// stock.js — quản lý danh sách tạm
function getList(type) {
    try {
        return JSON.parse(localStorage.getItem(getListConfig(type).key)) || [];
    } catch {
        return [];
    }
}
function saveList(type, list) {
    localStorage.setItem(getListConfig(type).key, JSON.stringify(list));
}
```

Lợi ích:
- Dùng lại danh sách nếu lỡ tay reload
- Tạo nhiều phiếu từ cùng một danh sách
- Giảm số lần gọi API

### 8.3. Transaction + Row-Level Locking

Chống race condition khi nhập/xuất đồng thời:

```php
// import_stock.php / export_stock.php
$conn->begin_transaction();
try {
    // SELECT ... FOR UPDATE — khóa dòng, các request khác phải chờ
    $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    // UPDATE (import)
    $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
    // hoặc (export với kiểm tra tồn)
    $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    // trả JSON lỗi
}
```

### 8.4. Ajax Utility Pattern

Hàm `ajaxCall()` là trung tâm của mọi giao tiếp client-server:

```js
// app.js:69
function ajaxCall(url, options = {}) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const method = (options.method || 'GET').toUpperCase();
        xhr.open(method, url);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            xhr.setRequestHeader('X-CSRF-Token', _csrfToken);
        }
        xhr.onload = function() {
            if (xhr.status === 401) { /* redirect login */ return; }
            if (xhr.status < 200 || xhr.status >= 300) { reject(...); return; }
            try {
                const data = JSON.parse(xhr.responseText);
                if (data && data.redirect) { window.location.href = data.redirect; return; }
                resolve(data);
            } catch (e) { reject(e); }
        };
        xhr.onerror = () => reject(new Error('Lỗi kết nối máy chủ.'));
        xhr.send(options.body || null);
    });
}
```

Đặc điểm:
- Promise-based (dùng được với async/await)
- Tự động gắn CSRF token cho POST
- Xử lý 401 → redirect login
- Xử lý redirect từ server (khi session hết hạn)
- Parse JSON tự động

### 8.5. Combobox Autocomplete

```js
// combobox.js — Custom combobox với tìm kiếm
// Gõ tên → gọi API filter → dropdown
// Dùng setTimeout debounce 200ms để tránh gọi API liên tục
// Khi chọn → tự điền product_id + tên
```

### 8.6. Custom Confirm Dialog

Thay vì native `confirm()`:

```js
// app.js:130-158 — showConfirm
function showConfirm(msg, cb) {
    // render modal overlay với message + OK/Cancel
    // OK → gọi cb(true)
    // Cancel → gọi cb(false)
}
```

Ưu điểm: có CSS đồng nhất, không bị chặn bởi browser, có thể tùy chỉnh giao diện.

### 8.7. Toast Notification System

```js
// app.js:163-181 — showToast
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    // icon: check_circle (success) hoặc error (error)
    toast.innerHTML = `<span>${icon}</span><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'toast_out 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}
```

Thông báo tự động biến mất sau 3.5 giây, không cần user click.

### 8.8. Response Format Thống Nhất

Mọi API đều trả về JSON format chuẩn:

```json
{
    "success": true | false,
    "message": "Thao tác thành công!" | "Lỗi: ..."
}
```

Các API list trả thêm:
```json
{
    "success": true,
    "records": [ ... ],
    "total": 45,
    "page": 1,
    "per_page": 10,
    "can_manage_products": true,
    "can_view_products": true
}
```

---

## 9. JAVASCRIPT ARCHITECTURE

### 9.1. File overview

| File | Vai trò | Số dòng | Phụ thuộc |
|------|---------|---------|-----------|
| `app.js` | Core: ajax, toast, confirm, submitForm, tab, utilities | ~208 | — |
| `products.js` | CRUD sản phẩm, filter, categories | ~582 | app.js |
| `stock.js` | Import/Export batch | ~592 | app.js, combobox.js |
| `dashboard.js` | 5 biểu đồ Chart.js | ~200+ | Chart.js CDN |
| `combobox.js` | Autocomplete dropdown | ~200 | app.js |
| `admin-users.js` | CRUD user, sessions | ~400+ | app.js |

### 9.2. Global Variables

```js
// Được inject từ PHP trong <head>
const BASE = '/CT428_Warehouse_Manager';            // Base URL
const _csrfToken = '...';                           // CSRF token
const APP_CONFIG = {                                // Permission flags
    can_manage_products: true/false,
    can_view_products: true/false,
    can_import_export: true/false,
    is_admin: true/false,
    is_manager: true/false,
    user_id: 1,
    full_name: 'Admin'
};
```

### 9.3. Error Handling Patterns

**Pattern 1 — Standard API call (promise):**
```js
ajaxCall(url)
    .then(data => {
        if (data.success) { /* xử lý */ }
        else { showToast(data.message, 'error'); }
    })
    .catch(() => showToast('Lỗi kết nối máy chủ.', 'error'));
```

**Pattern 2 — Form submit:**
```js
submitForm(btn, url, fd, {
    onSuccess: (data) => { /* refresh, close modal, ... */ },
    onError: () => { /* fallback */ }
});
// Tự động: showToast(data.message, data.success ? 'success' : 'error')
```

**Pattern 3 — localStorage safe read:**
```js
try { return JSON.parse(localStorage.getItem(key)) || []; }
catch { return []; }
```

**Pattern 4 — Server error (PHP):**
```php
if (!$stmt->execute()) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $stmt->error]);
    exit;
}
```

---

## 10. HẠN CHẾ & HƯỚNG PHÁT TRIỂN

### Hiện tại (MV1 — chấp nhận được cho đồ án SV):
- **Chưa quản lý hình ảnh** sản phẩm — cần upload, resize, CDN
- **Chưa notification/email** — thông báo tồn kho thấp
- **Code procedural** chứa trong file .php — dài, khó maintain
- **Chưa có unit test** — PHPUnit + Jest
- **Chưa dùng package manager** — Composer, npm
- **Chart.js từ CDN** — không fallback nếu mất mạng
- **Giá DECIMAL(15,0)** — không lẻ, phù hợp VND nhưng thiếu linh hoạt
- **Threshold low stock hardcode** (stock_quantity < 30) — nên configurable
- **Chưa phân trang Import/Export list** — chỉ dùng modal chi tiết
- **Chưa có tính năng tìm kiếm nâng cao** — filter theo giá, ngày

### Hướng phát triển (MV2+):
| Tính năng | Mô tả | Công nghệ đề xuất |
|-----------|-------|-------------------|
| Image management | Upload, compress, CDN, fallback | Dropzone.js, Cloudinary |
| REST API | Tách front-end / back-end | Laravel / Slim |
| Notification | Email/SMS khi tồn kho thấp | PHPMailer, Firebase |
| PDF/Excel export | Xuất báo cáo | PhpSpreadsheet, Dompdf |
| Docker | Môi trường chuẩn | Docker Compose |
| Testing | Unit + Integration | PHPUnit, Jest, Playwright |
| OOP | Tái cấu trúc code | Namespaces, Classes, PSR-4 |
| CI/CD | Tự động kiểm tra | GitHub Actions |
| Skeleton loading | UX mượt hơn | CSS animation |
| Infinite scroll | Thay vì phân trang | Intersection Observer |

---

## 11. FILE CẦN NHỚ KHI THUYẾT TRÌNH

### 11.1. Entry & Routing

| File | Dòng | Nội dung |
|------|------|----------|
| `index.php` | 1-30 | session_start(), routing login/main |
| `php/Pages/index.php` | Toàn bộ | App shell: include head, sidebar, header, các tab |

### 11.2. Authentication & Security

| File | Dòng | Nội dung |
|------|------|----------|
| `php/auth.php` | 8 | `generateCsrfToken()` |
| `php/auth.php` | 15 | `verifyCsrfToken()` |
| `php/auth.php` | 142 | `requireAdmin()` |
| `php/auth.php` | 146 | `requireAdminOrManager()` |
| `php/auth.php` | 150 | `requireCanImportExport()` |
| `php/auth.php` | 162 | `getCurrentUser()` |

### 11.3. Database & Config

| File | Dòng | Nội dung |
|------|------|----------|
| `php/config.php` | Toàn bộ | Hằng số DB, BASE_URL, timezone |
| `php/db.php` | Toàn bộ | Kết nối mysqli |
| `warehouse_manager.sql` | Toàn bộ | Schema + sample data |

### 11.4. Helpers (Business Logic)

| File | Dòng | Nội dung |
|------|------|----------|
| `php/partials/helpers-products.php` | 5-63 | `validateProductInput()` |
| `php/partials/helpers-stock.php` | — | `getStockList()`, `getStockDetail()` |
| `php/partials/helpers-users.php` | 5-194 | `getDashboardData()` — toàn bộ query cho Dashboard |

### 11.5. API Endpoints

| File | Method | Chức năng |
|------|--------|-----------|
| `api/filter_products.php` | GET | Search, filter, sort, paginate |
| `api/add_product.php` | POST | Thêm sản phẩm + auto nhập kho |
| `api/edit_product.php` | GET/POST | Detail, update, toggle_active, delete |
| `api/import_stock.php` | POST | Xử lý nhập kho batch |
| `api/export_stock.php` | POST | Xử lý xuất kho batch |
| `api/users.php` | GET/POST | CRUD user, sessions |
| `api/add_category.php` | POST | Thêm danh mục |

### 11.6. JavaScript

| File | Dòng | Nội dung |
|------|------|----------|
| `public/js/app.js` | 69 | `ajaxCall()` — core XHR wrapper |
| `public/js/app.js` | 193 | `submitForm()` — async form submit |
| `public/js/app.js` | 130 | `showConfirm()` — custom confirm |
| `public/js/app.js` | 163 | `showToast()` — toast notification |
| `public/js/products.js` | 4 | `getProductStatus()` — trả badge class/text |
| `public/js/products.js` | 11 | `fetchFilteredProducts()` — load bảng SP |
| `public/js/products.js` | 459 | `btnSubmitAddProduct` handler — thêm SP |
| `public/js/stock.js` | — | `getList()`, `saveList()` — batch list |
| `public/js/dashboard.js` | — | Vẽ 5 biểu đồ Chart.js |
| `public/js/combobox.js` | — | Autocomplete input |

### 11.7. Views (Templates)

| File | Nội dung |
|------|----------|
| `php/partials/modals.php` | Tất cả modal: add product, detail, import/export confirm, confirm dialog |
| `php/partials/tab_dashboard.php` | Dashboard HTML: KPI cards, chart canvases, 3 bảng |
| `php/partials/tab_warehouse.php` | Bảng sản phẩm + filters |
| `php/partials/tab_history.php` | Lịch sử nhập/xuất |

---

## 12. CẤU HÌNH & TRIỂN KHAI

### Yêu cầu hệ thống

| Thành phần | Yêu cầu |
|-----------|---------|
| PHP | 8.2+ (mysqli, json, session) |
| MySQL | 8.0+ |
| Web server | Apache / Nginx / PHP built-in |
| Trình duyệt | Chrome 90+, Firefox 90+, Edge 90+ |

### Cài đặt

```bash
# 1. Clone repo
git clone <repo-url> CT428_Warehouse_Manager

# 2. Tạo database
mysql -u root -p < warehouse_manager.sql

# 3. Cấu hình database (nếu khác mặc định)
cp config.local.example.php config.local.php
# Sửa config.local.php với thông tin DB của bạn

# 4. Chạy PHP built-in server
cd CT428_Warehouse_Manager
php -S localhost:8080

# 5. Mở trình duyệt: http://localhost:8080
```

### Cấu hình mặc định (`php/config.php`)

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'warehouse_manager');
date_default_timezone_set('Asia/Ho_Chi_Minh');
```

Có thể override bằng file `config.local.php` (không commit lên Git).

---

## 13. CÂU HỎI THƯỜNG GẶP (KHI THUYẾT TRÌNH)

### 13.1. Về công nghệ

**Q: "Tại sao không dùng framework như Laravel?"**
> A: Mục đích học thuật — chúng em muốn hiểu rõ cơ chế hoạt động của request-response, session, prepared statements, routing trước khi dùng framework che đi. Kiến trúc hiện tại đã có sẵn MVC-like để dễ dàng nâng cấp sau.

**Q: "Tại sao dùng JavaScript thuần, không dùng React/Vue?"**
> A: Ứng dụng tập trung vào nghiệp vụ nhập/xuất kho không cần reactive UI phức tạp. JS thuần + Fetch API đủ dùng, giảm bundle size, không cần build step. Khi ứng dụng lớn hơn thì chuyển sang framework.

**Q: "Tại sao dùng session auth thay vì JWT?"**
> A: Web truyền thống (web page, không phải mobile app) thì session an toàn hơn với HttpOnly/SameSite cookie, đơn giản để revoke. JWT phù hợp hơn khi có thêm mobile app hoặc API public.

### 13.2. Về bảo mật

**Q: "Bảo mật thế nào?"**
> A: 6 lớp: (1) CSRF token mỗi POST, (2) Prepared statements chống SQL injection, (3) Bcrypt cost=12 cho password, (4) Session HttpOnly + SameSite, (5) Rate limiting login 5 lần/5 phút, (6) Role check server-side trên mọi API.

**Q: "Chống SQL injection thế nào?"**
> A: Tuyệt đối không nối chuỗi SQL. Mọi query dùng prepared statements: `$stmt = $conn->prepare()` + `$stmt->bind_param()`.

**Q: "Có bị XSS không?"**
> A: Mọi output đều qua `escapeHtml()` (dùng `htmlspecialchars`) trước khi in ra HTML. Nhưng user input trong mô tả sản phẩm có thể chứa HTML — đây là hạn chế cần cải thiện.

### 13.3. Về nghiệp vụ

**Q: "Xử lý hai người cùng xuất kho một lúc?"**
> A: Dùng transaction + `SELECT ... FOR UPDATE` (row-level locking). Khi user A đang xử lý xuất SP X, user B sẽ phải chờ đến khi A commit/rollback.

**Q: "Tại sao thêm sản phẩm lại tự động tạo phiếu nhập?"**
> A: Để đảm bảo mọi biến động tồn kho đều có lịch sử. Khi nhập số lượng ban đầu cho SP mới, hệ thống tự ghi nhận là phiếu nhập "PN_AUTO_..." để sau này có thể trace được nguồn gốc.

**Q: "Tại sao không quản lý hình ảnh sản phẩm?"**
> A:Ở phiên bản MVP này, chúng em tập trung vào nghiệp vụ cốt lõi là nhập/xuất/tồn kho, thống kê và phân quyền. Hình ảnh cần thêm: upload, resize, CDN, fallback, security check file — sẽ phát triển ở phiên bản sau.

**Q: "Tại sao phân quyền chỉ có 3 role?"**
> A: 3 role (Admin, Manager, Staff) đáp ứng đủ mô hình quản lý kho điển hình: người quản trị, người quản lý, người thao tác. Có thể thêm role tùy chỉnh sau.

**Q: "Có tính năng gì nổi bật?"**
> A: (1) Batch import/export — thêm nhiều SP vào một phiếu, lưu tạm vào localStorage, (2) 5 biểu đồ Chart.js trực quan trên Dashboard, (3) Hệ thống phân quyền + access schedule cho staff, (4) Transaction + row locking chống race condition.

### 13.4. Về tổ chức code

**Q: "Cấu trúc thư mục thế nào?"**
> A: index.php (entry point) → api/ (controller, xử lý request) → php/partials/ (views + helpers) → php/Pages/ (page shells) → public/ (CSS + JS assets). Cấu trúc giống MVC, dễ nâng cấp sau.

**Q: "Code procedural, tại sao không OOP?"**
> A: Lựa chọn có chủ đích cho đồ án — code thẳng, dễ debug, dễ demo. Tuy nhiên các hàm đã được phân tách rõ ràng theo chức năng, sẵn sàng để OOP hóa sau.

### 13.5. Về kiểm thử

**Q: "Đã test chưa?"**
> A: Hiện tại chỉ test thủ công qua giao diện. Chưa có unit test tự động do hạn chế thời gian. Đây là một trong những hướng phát triển ưu tiên tiếp theo.

---

## 14. ĐIỂM CỘNG KHI TRÌNH BÀY

### Những điểm nên nhấn mạnh:

1. **Transaction + Row Locking**: Giải quyết bài toán race condition thực tế — điểm cộng lớn so với đồ án sinh viên thông thường.

2. **CSRF Protection**: Rất ít đồ án sinh viên làm CSRF. Có thể nói: "Chúng em implement CSRF token double-submit pattern để bảo vệ mọi POST request."

3. **Rate Limiting + Session Management**: Block IP sau 5 lần login sai, lưu IP + User-Agent trong session, session rotation — cho thấy hiểu biết về bảo mật web.

4. **Batch + localStorage**: UX thông minh — cho phép thêm/xóa/sửa nhiều sản phẩm trước khi submit, dùng lại danh sách nếu reload.

5. **Dashboard 5 biểu đồ**: Nhiều hơn đồ án trung bình. Dùng Chart.js với DataLabels plugin — thể hiện kỹ năng front-end.

6. **Role-based UI**: Không chỉ check server, còn ẩn/hiện UI theo role qua `APP_CONFIG` — UX chuyên nghiệp.

7. **Auto receipt on add product**: Khi thêm SP có số lượng → tự tạo phiếu nhập → trace được lịch sử — thể hiện tư duy toàn vẹn dữ liệu.

### Lưu ý khi trả lời:

- Nếu bị hỏi điểm yếu: thừa nhận thẳng thắn và nêu hướng cải thiện (mục 10)
- Nếu bị hỏi "ai làm phần nào": chuẩn bị trước câu trả lời về phân công
- Luôn quy về "mục đích học thuật" khi bị hỏi tại sao dùng công nghệ X thay vì Y

---

## 15. THỐNG KÊ DỰ ÁN

| Hạng mục | Số liệu |
|----------|---------|
| Tổng số file | ~50 file |
| Số dòng PHP | ~3,000+ |
| Số dòng JavaScript | ~2,000+ |
| Số dòng CSS | ~1,500+ |
| Số bảng database | 8 |
| Số API endpoint | 7 |
| Số biểu đồ | 5 (Chart.js) |
| Số role người dùng | 3 (Admin, Manager, Staff) |
| Số tab chính | 5 (Dashboard, Warehouse, Import, Export, Users) |

---

*Tài liệu hỗ trợ thuyết trình — CT428 Warehouse Manager - Nhóm 10*

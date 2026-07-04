# Tài Liệu Kỹ Thuật & Cấu Trúc Dự Án (AboutStructure)

Dự án **Quản Lí Kho** được phát triển trên mô hình Web kết hợp giữa xử lý Backend bằng PHP (XAMPP) và tương tác Frontend động không reload trang bằng AJAX (Vanilla JavaScript).

---

## 🛠️ Công nghệ sử dụng
1. **Ngôn ngữ phía Máy chủ (Backend):** PHP
2. **Cơ sở dữ liệu:** MySQL / MariaDB
3. **Thiết kế giao diện (Frontend):** HTML5, CSS3 (Vanilla CSS), Material Symbols (Google Icons).
4. **Biểu đồ dữ liệu:** Chart.js (thư viện JavaScript vẽ biểu đồ).
5. **Kỹ thuật tối ưu & Bảo mật:**
   - AJAX (Fetch API) để tải/cập nhật dữ liệu bất đồng bộ.
   - PHP Sessions kết hợp xác thực Database Session (token-based DB session).
   - Prepared Statements (Tham số hoá truy vấn) phòng chống SQL Injection.
   - Hàm `password_hash` thuật toán Bcrypt cost=12 mã hóa mật khẩu một chiều.
   - Xử lý Cache-busting CSS động thông qua PHP `filemtime`.

---

## 🔍 Trích dẫn Kỹ thuật & Đoạn code cụ thể

### 1. Kỹ thuật AJAX (Fetch API) không tải lại trang
AJAX được sử dụng rộng rãi để cập nhật giao diện thời gian thực.
- **Trích dẫn: Lọc sản phẩm động (File `index.php`)**:
```javascript
function fetchFilteredProducts() {
    const searchVal   = document.getElementById('search_input_sort').value;
    const categoryVal = document.getElementById('select_category').value;
    const priceSortVal = document.getElementById('select_price').value;
    const qtySortVal   = document.getElementById('select_quantity').value;

    const params = new URLSearchParams({
        search: searchVal, category: categoryVal,
        price_sort: priceSortVal, qty_sort: qtySortVal
    });

    const tbody = document.getElementById('product_table_body');
    tbody.innerHTML = '<tr><td colspan="7"...>Đang tải dữ liệu...</td></tr>';

    fetch('filter_products.php?' + params.toString())
        .then(r => r.text())
        .then(html => { tbody.innerHTML = html; })
        .catch(err => { ... });
}
```

- **Trích dẫn: Thêm danh mục mới và tự động cập nhật Dropdown (File `index.php`)**:
Sau khi API `admin/add_category.php` trả về dữ liệu JSON chứa danh mục mới, Javascript sẽ cập nhật danh sách chọn mà không cần tải lại trang:
```javascript
fetch('admin/add_category.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeAddCatModal();
            updateCategoryDropdowns(data.categories); // Cập nhật lại dropdown danh mục
        }
    })
```

---

### 2. Xác thực phiên đăng nhập an toàn kết hợp Database Session
Thay vì chỉ lưu Session ở file server mặc định của PHP, hệ thống sử dụng kết hợp bảng `sessions` trong Database để nâng cao tính bảo mật, cho phép giám sát thiết bị và đăng xuất tài khoản từ xa (Kick user).
- **Trích dẫn: Session Guard (File `auth.php`)**:
```php
if ($conn) {
    $token = $_SESSION['session_token'];
    $stmt = $conn->prepare(
        "SELECT s.user_id, s.expires_at, u.is_active, u.role, u.full_name
         FROM sessions s
         JOIN users u ON s.user_id = u.id
         WHERE s.session_token = ? AND s.expires_at > NOW() AND u.is_active = 1"
    );
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $session_data = $result->fetch_assoc();
        $stmt->close();
        if (!$session_data) {
            // Token không tồn tại hoặc hết hạn -> Đăng xuất cưỡng bức
            $_SESSION = [];
            session_destroy();
            header('Location: login.php?expired=1');
            exit;
        }
    }
}
```

- **Trích dẫn: Chức năng thu hồi phiên đăng nhập từ xa (File `admin/users.php`)**:
```php
case 'kick':
    $kick_user_id = (int)($_POST['user_id'] ?? 0);
    // Xóa session khỏi cơ sở dữ liệu (trừ phiên hiện tại của chính admin thực hiện)
    $stmt = $conn->prepare("DELETE FROM sessions WHERE user_id = ? AND session_token != ?");
    $my_token = $_SESSION['session_token'];
    $stmt->bind_param("is", $kick_user_id, $my_token);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Đã đăng xuất user khỏi hệ thống.']);
    break;
```

---

### 3. Prepared Statements chống tấn công SQL Injection
Toàn bộ các truy vấn chứa dữ liệu do người dùng nhập vào đều được xử lý qua Prepared Statement (tham số hóa).
- **Trích dẫn: API Thêm sản phẩm (File `admin/add_product.php`)**:
```php
$stmt = $conn->prepare(
    "INSERT INTO sanpham (TenSP, DanhMuc, MoTa, Gia, SoLuong) VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssdi", $ten_sp, $danhmuc, $mota, $gia, $so_luong);
$stmt->execute();
```

---

### 4. Phòng ngừa tấn công Session Fixation
Sau khi người dùng xác thực thông tin đăng nhập thành công, ID của PHP Session cũ sẽ bị huỷ bỏ và thay thế bằng ID mới để chống các cuộc tấn công đánh cắp session ID cũ.
- **Trích dẫn: Đăng nhập thành công (File `login.php`)**:
```php
if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true); // Huỷ session ID cũ, sinh session ID mới
    // Tiếp tục khởi tạo DB Session...
}
```

---

## 📁 Cây thư mục dự án & Chức năng từng file

```
Quanlikhohang/
│
├── index.php               ← Trang chính của ứng dụng. Gồm 3 tab chức năng:
│                              - Dashboard (biểu đồ tổng quan tồn kho)
│                              - Kho hàng (bảng lọc, thêm sản phẩm, thêm danh mục)
│                              - Quản lý người dùng (chỉ Admin)
│                              Tích hợp toàn bộ JavaScript AJAX, modal, toast.
│
├── login.php               ← Giao diện đăng nhập người dùng.
│                              Xử lý POST: xác thực username/password, tạo DB session,
│                              ghi bảng sessions, cập nhật last_login, chuyển hướng.
│
├── logout.php              ← Xử lý đăng xuất: xóa session trong bảng DB,
│                              hủy PHP session, redirect về login.php.
│
├── auth.php                ← Lớp bảo vệ bảo mật dùng chung (Session Guard).
│                              Kiểm tra PHP session + DB session token còn hạn,
│                              tài khoản còn hoạt động. Cung cấp hàm:
│                              - requireLogin() : chặn truy cập nếu chưa đăng nhập
│                              - requireAdmin() : chặn nếu không có quyền Admin
│                              - isAdmin()      : trả về true/false theo role
│
├── db.php                  ← Khởi tạo kết nối MySQL duy nhất ($conn) dùng chung
│                              cho toàn bộ các file PHP trong dự án.
│
├── filter_products.php     ← API AJAX trả về HTML fragment danh sách sản phẩm.
│                              Nhận tham số GET: search, category, price_sort, qty_sort.
│                              Dùng Prepared Statement để tránh SQL Injection.
│                              Yêu cầu phải đăng nhập (requireLogin).
│
├── admin/                  ← Thư mục chứa các API xử lý nghiệp vụ dành riêng Admin.
│   │
│   ├── users.php           ← API JSON quản lý tài khoản người dùng. Hỗ trợ các action:
│   │                          - list    : Lấy danh sách tài khoản
│   │                          - create  : Tạo tài khoản mới (bcrypt hash mật khẩu)
│   │                          - toggle  : Kích hoạt / Vô hiệu hoá tài khoản
│   │                          - delete  : Xóa tài khoản
│   │                          - sessions: Liệt kê các phiên đang hoạt động
│   │                          - kick    : Đăng xuất user từ xa (xóa DB session)
│   │
│   ├── add_product.php     ← API POST thêm sản phẩm mới vào bảng sanpham.
│   │                          Validate: tên, danh mục tồn tại, giá >= 0, số lượng >= 0.
│   │                          Yêu cầu quyền Admin (requireAdmin).
│   │
│   └── add_category.php    ← API POST thêm danh mục mới vào bảng danhmuc.
│                              Validate: mã (2-10 ký tự, không trùng), tên danh mục.
│                              Trả về danh sách danh mục mới nhất để cập nhật dropdown.
│                              Yêu cầu quyền Admin (requireAdmin).
│
└── assets/                 ← Thư mục chứa tài nguyên tĩnh của dự án.
    │
    ├── css/
    │   └── style.css       ← File CSS duy nhất, định dạng toàn bộ giao diện:
    │                          header, sidebar, bảng sản phẩm, biểu đồ,
    │                          trang login, modal, toast, dropdown user.
    │
    └── document/           ← Thư mục chứa tài liệu dự án.
        ├── Guide.md              ← Hướng dẫn cài đặt, khởi chạy, tài khoản mặc định,
        │                            bảng phân quyền Admin vs Staff.
        │
        └── AboutStructure.md     ← Tài liệu kỹ thuật: công nghệ sử dụng,
                                     trích dẫn code cụ thể theo từng kỹ thuật,
                                     và cây thư mục với chú thích chức năng (file này).
```

---

### 5. Cơ chế Xóa cache trình duyệt (CSS Cache-Busting)
Hệ thống sử dụng hàm `filemtime` của PHP để đọc thời gian chỉnh sửa mới nhất của file `style.css` và chèn vào URL dưới dạng tham số. Khi nhà phát triển thay đổi CSS, trình duyệt sẽ tự tải lại CSS mới mà không bị dính cache cũ.
- **Trích dẫn: Liên kết file CSS (File `index.php` & `login.php`)**:
```html
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
```


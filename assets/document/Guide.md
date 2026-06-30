# Hướng Dẫn Sử Dụng Hệ Thống Quản Lí Kho

Hệ thống **Quản Lí Kho** là ứng dụng web quản lý danh mục sản phẩm, tồn kho và phân quyền người dùng (Admin & Staff) theo thời gian thực.

---

## 🚀 Hướng dẫn Cài đặt & Khởi động nhanh

### 1. Chuẩn bị môi trường
- Cài đặt phần mềm **XAMPP** (hỗ trợ PHP 7.4 trở lên và MySQL/MariaDB).
- Sao chép thư mục `Quanlikhohang` vào thư mục gốc của máy chủ local: `C:\xampp\htdocs\`.

### 2. Thiết lập Cơ sở dữ liệu (Database)
- Khởi động **Apache** và **MySQL** trong ứng dụng XAMPP Control Panel.
- Truy cập công cụ quản trị **phpMyAdmin** qua trình duyệt tại địa chỉ: `http://localhost/phpmyadmin/`.
- Tạo một cơ sở dữ liệu mới có tên: `warehouse_manager`.
- Chọn tab **Import** (Nhập), nhấn nút chọn tệp và chọn tệp `setup_auth.sql` trong thư mục dự án để khởi tạo các bảng dữ liệu (`danhmuc`, `sanpham`, `users`, `sessions`).

### 3. Đăng nhập hệ thống
- Truy cập vào địa chỉ: `http://localhost/Quanlikhohang/login.php` trên trình duyệt.
- Đăng nhập bằng tài khoản Quản trị viên (Admin) mặc định:
  - **Tên đăng nhập (Username):** `admin`
  - **Mật khẩu (Password):** `Admin@123`

---

## 👥 Phân quyền & Tính năng các vai trò

### 1. Vai trò Quản trị viên (Admin)
Admin có toàn quyền kiểm soát hệ thống, bao gồm các tính năng:
- **Trang Tổng quan (Dashboard):** Xem các biểu đồ trực quan về số lượng sản phẩm và giá trị tồn kho theo từng danh mục.
- **Trang Kho hàng:**
  - Tìm kiếm, lọc sản phẩm theo danh mục, sắp xếp sản phẩm tăng/giảm theo giá hoặc số lượng.
  - **Thêm sản phẩm mới:** Nhập thông tin chi tiết của sản phẩm để lưu vào kho.
  - **Thêm danh mục mới:** Tạo các nhóm phân loại sản phẩm mới. Các dropdown chọn danh mục sẽ được tự động đồng bộ mà không cần tải lại trang.
- **Trang Quản lý người dùng:**
  - Xem danh sách toàn bộ tài khoản nhân viên.
  - **Tạo tài khoản:** Cấp tài khoản mới cho nhân viên kho (Staff) hoặc quản trị viên (Admin).
  - **Kích hoạt / Vô hiệu hóa:** Khoá tài khoản Staff khi cần (Staff sẽ tự động bị đăng xuất ngay lập tức).
  - **Xóa tài khoản:** Xóa các tài khoản Staff không còn làm việc.
  - **Quản lý phiên (Sessions):** Theo dõi danh sách thiết bị đang đăng nhập và có quyền đăng xuất từ xa (Kick) đối với bất kỳ tài khoản nào.

### 2. Vai trò Nhân viên (Staff)
Nhân viên kho (Staff) chỉ được cấp các quyền xem thông tin cơ bản:
- **Trang Tổng quan (Dashboard):** Xem báo cáo thống kê và biểu đồ tồn kho.
- **Trang Kho hàng:** Xem danh sách, tìm kiếm, lọc và sắp xếp sản phẩm.
- **Giới hạn bảo mật:** Các nút thao tác nghiệp vụ ("Thêm mới sản phẩm", "Thêm danh mục", "Quản lý người dùng") hoàn toàn bị ẩn và các API backend bị chặn truy cập.

---

## 📁 Cấu trúc thư mục dự án

```
Quanlikhohang/
├── index.php               # Trang chính (Tổng quan, Kho hàng, Quản lý tài khoản)
├── login.php               # Giao diện đăng nhập
├── logout.php              # Xử lý đăng xuất
├── filter_products.php     # API lấy và lọc danh sách sản phẩm (AJAX)
├── db.php                  # Kết nối Cơ sở dữ liệu dùng chung
├── auth.php                # Lớp bảo mật kiểm tra phiên đăng nhập & phân quyền
├── style.css               # File CSS chứa toàn bộ kiểu dáng giao diện
├── setup_auth.sql          # File SQL khởi tạo CSDL mẫu ban đầu
├── Guide.md                # File hướng dẫn sử dụng này
├── AboutStructure.md       # Tài liệu kỹ thuật và cấu trúc lập trình
└── admin/                  # Thư mục chứa các API xử lý nghiệp vụ của Admin
    ├── users.php           # API quản lý tài khoản (Thêm, Xóa, Khóa, Session, Kick)
    ├── add_product.php     # API thêm sản phẩm mới
    └── add_category.php    # API thêm danh mục mới
```

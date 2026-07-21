# Hệ thống Quản lí Kho (Warehouse Manager) - Đồ án Nhóm 10

Hệ thống quản lý kho hàng, theo dõi tồn kho theo thời gian thực sử dụng PHP, MySQL và AJAX.

## Yêu cầu hệ thống
- XAMPP (hoặc môi trường LAMP/WAMP tương đương)
- PHP >= 8.0
- MySQL / MariaDB

## Hướng dẫn cài đặt và triển khai (Deploy)

1. **Clone repository hoặc giải nén mã nguồn vào thư mục webroot** (ví dụ: `htdocs/CT428_Warehouse_Manager`).
2. **Khởi tạo Cơ sở dữ liệu:**
   - Mở phpMyAdmin (hoặc công cụ MySQL của bạn).
   - Tạo một database mới tên là `warehouse_manager`.
   - Import file `warehouse_manager.sql` vào database vừa tạo.
3. **Cấu hình kết nối DB:**
   - Đổi tên file `config.local.example.php` thành `config.local.php`.
   - Mở file `config.local.php` và cập nhật thông tin đăng nhập MySQL của bạn (DB_USER, DB_PASS).
   - *(Lưu ý: Không được commit file `config.local.php` lên Git)*.
4. **Cấu hình Web Server (Tùy chọn nhưng khuyến nghị):**
   - Đảm bảo Apache (hoặc Nginx) hỗ trợ `.htaccess` (`AllowOverride All` trong cấu hình thư mục của Apache). Hệ thống dùng `.htaccess` để bảo vệ các file hệ thống nhạy cảm.
5. **Chạy ứng dụng:**
   - Truy cập vào đường dẫn ứng dụng thông qua trình duyệt (ví dụ: `http://localhost/CT428_Warehouse_Manager`).
   - Đăng nhập bằng tài khoản Admin để kiểm tra.

## Tài khoản mặc định

Sau khi import file `.sql`, hệ thống có các tài khoản có sẵn (mật khẩu mặc định là `123456`):
- **Admin**: `admin` / `123456`
- **Quản lý kho**: `manager1` / `123456`
- **Nhân viên**: `staff1` / `123456`

## Các cập nhật bảo mật & tính năng (Gần đây)

Dự án đã được khắc phục các lỗi nghiêm trọng sau:
1. **Bảo vệ CSRF (Double-Submit Cookie/Meta Tag)**: Tất cả các thao tác thay đổi dữ liệu (thêm/sửa sản phẩm, nhập/xuất kho, thêm người dùng...) đều được bảo vệ bằng CSRF Token.
2. **Chống XSS (Cross-Site Scripting)**: Dữ liệu (đặc biệt là thuộc tính `title` HTML) đã được escape an toàn bằng `ENT_QUOTES`.
3. **Race Condition (Tồn kho)**: Thao tác nhập xuất kho sử dụng `SELECT ... FOR UPDATE` và atomic SQL update (`SoLuong = SoLuong + ?`) để ngăn lỗi mất đồng bộ dữ liệu khi thao tác đồng thời.
4. **Bảo mật File Cấu hình**: Cấu hình CSDL được tách ra `config.local.php` (bị loại khỏi Git), đồng thời `.htaccess` ngăn truy cập trực tiếp vào các tệp tin hệ thống quan trọng.
5. **DRY & Tái cấu trúc API (Một phần)**: Gộp chung helper validation, logic quyền đã được chuẩn hóa.

*(Các vấn đề cấu trúc như tách lớp MVC toàn bộ, chuẩn hóa 100% JSON API có thể được thực hiện trong tương lai tùy theo yêu cầu mở rộng)*.

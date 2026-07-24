-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th7 24, 2026 lúc 07:50 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `warehouse_manager`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_phieu_nhap`
--

CREATE TABLE `chi_tiet_phieu_nhap` (
  `id` int(11) NOT NULL,
  `ma_phieu` varchar(50) NOT NULL,
  `san_pham` int(11) NOT NULL,
  `so_luong` int(11) NOT NULL,
  `ghi_chu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_phieu_nhap`
--

INSERT INTO `chi_tiet_phieu_nhap` (`id`, `ma_phieu`, `san_pham`, `so_luong`, `ghi_chu`) VALUES
(1, 'PN_20260501090000_1001', 20, 200, 'Nhập kho đợt đầu — lô hàng iPhone 13'),
(2, 'PN_20260501091500_1002', 27, 200, 'Nhập kho đợt đầu — laptop học tập'),
(3, 'PN_20260501093000_1003', 28, 200, 'Nhập kho đợt đầu — laptop gaming'),
(4, 'PN_20260501094500_1004', 29, 250, 'Nhập kho đợt đầu — laptop giá rẻ'),
(5, 'PN_20260501100000_1005', 32, 170, 'Nhập kho đợt đầu — MacBook Pro'),
(6, 'PN_20260501101500_1006', 10, 350, 'Nhập lô phụ kiện bán chạy'),
(7, 'PN_20260501101500_1006', 13, 100, 'Nhập lô phụ kiện bán chạy'),
(8, 'PN_20260501101500_1006', 53, 230, 'Nhập lô phụ kiện bán chạy'),
(9, 'PN_20260501110000_1009', 57, 220, 'Nhập kho đợt đầu — gia dụng'),
(10, 'PN_20260501111500_1010', 65, 260, 'Nhập kho đợt đầu — gia dụng cao cấp'),
(11, 'PN_20260515090000_1011', 1, 50, 'Nhập bổ sung đợt 2'),
(12, 'PN_20260515090000_1011', 25, 200, 'Nhập bổ sung đợt 2'),
(13, 'PN_20260515090000_1011', 33, 120, 'Nhập bổ sung đợt 2'),
(14, 'PN_20260515090000_1011', 37, 70, 'Nhập bổ sung đợt 2'),
(15, 'PN_20260515110000_1015', 42, 160, 'Nhập bổ sung hub chuyển đổi'),
(16, 'PN_20260515113000_1016', 48, 120, 'Nhập bổ sung tai nghe giá rẻ'),
(17, 'PN_20260515120000_1017', 51, 160, 'Nhập bổ sung mic chuyên nghiệp'),
(18, 'PN_20260515123000_1018', 66, 210, 'Nhập bổ sung gia dụng'),
(19, 'PN_20260610090000_1019', 7, 160, 'Nhập lô mùa hè'),
(20, 'PN_20260610090000_1019', 30, 150, 'Nhập lô mùa hè'),
(21, 'PN_20260610090000_1019', 38, 70, 'Nhập lô mùa hè'),
(22, 'PN_20260610103000_1022', 44, 200, 'Nhập bổ sung kính cường lực'),
(23, 'PN_20260610110000_1023', 59, 190, 'Nhập bổ sung gia dụng'),
(24, 'PN_20260610113000_1024', 61, 100, 'Nhập bổ sung lò vi sóng'),
(25, 'PN_20260610120000_1025', 161, 190, 'Nhập bổ sung nồi chiên');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_phieu_xuat`
--

CREATE TABLE `chi_tiet_phieu_xuat` (
  `id` int(11) NOT NULL,
  `ma_phieu` varchar(50) NOT NULL,
  `san_pham` int(11) NOT NULL,
  `so_luong` int(11) NOT NULL,
  `ghi_chu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_phieu_xuat`
--

INSERT INTO `chi_tiet_phieu_xuat` (`id`, `ma_phieu`, `san_pham`, `so_luong`, `ghi_chu`) VALUES
(1, 'PX_20260510140000_2001', 20, 8, 'Xuất bán — đơn hàng khách lẻ'),
(2, 'PX_20260510143000_2002', 27, 15, 'Xuất bán — đơn hàng học sinh SV'),
(3, 'PX_20260510150000_2003', 29, 10, 'Xuất bán — đơn hàng online'),
(4, 'PX_20260510153000_2004', 13, 12, 'Xuất lô phụ kiện kèm điện thoại'),
(5, 'PX_20260510153000_2004', 53, 15, 'Xuất lô phụ kiện kèm điện thoại'),
(6, 'PX_20260510153000_2004', 10, 40, 'Xuất lô phụ kiện kèm điện thoại'),
(7, 'PX_20260525090000_2007', 1, 5, 'Xuất bán — đơn VIP'),
(8, 'PX_20260525093000_2008', 28, 6, 'Xuất bán — đơn hàng gaming'),
(9, 'PX_20260525100000_2009', 32, 10, 'Xuất lô doanh nghiệp'),
(10, 'PX_20260525100000_2009', 48, 8, 'Xuất lô doanh nghiệp'),
(11, 'PX_20260525100000_2009', 42, 12, 'Xuất lô doanh nghiệp'),
(12, 'PX_20260525113000_2012', 66, 10, 'Xuất bán — đơn gia dụng'),
(13, 'PX_20260615090000_2013', 25, 7, 'Xuất bán — đơn online'),
(14, 'PX_20260615093000_2014', 33, 7, 'Xuất bán — đơn văn phòng'),
(15, 'PX_20260615100000_2015', 7, 10, 'Xuất lô phụ kiện'),
(16, 'PX_20260615100000_2015', 30, 6, 'Xuất lô phụ kiện'),
(17, 'PX_20260615100000_2015', 44, 12, 'Xuất lô phụ kiện'),
(18, 'PX_20260615113000_2018', 59, 7, 'Xuất bán — đơn gia dụng');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danhmuc`
--

CREATE TABLE `danhmuc` (
  `MaDM` varchar(10) NOT NULL,
  `TenDM` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `danhmuc`
--

INSERT INTO `danhmuc` (`MaDM`, `TenDM`) VALUES
('ATH', 'Thiết bị âm thanh'),
('DTH', 'Điện thoại'),
('GDG', 'Gia dụng'),
('LAP', 'Laptop'),
('PKI', 'Phụ kiện'),
('QUA', 'Quà tặng');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieu_nhap`
--

CREATE TABLE `phieu_nhap` (
  `ma_phieu` varchar(50) NOT NULL,
  `nguoi_tao` int(11) NOT NULL,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phieu_nhap`
--

INSERT INTO `phieu_nhap` (`ma_phieu`, `nguoi_tao`, `ngay_tao`) VALUES
('PN_20260501090000_1001', 1, '2026-05-01 09:00:00'),
('PN_20260501091500_1002', 1, '2026-05-01 09:15:00'),
('PN_20260501093000_1003', 1, '2026-05-01 09:30:00'),
('PN_20260501094500_1004', 1, '2026-05-01 09:45:00'),
('PN_20260501100000_1005', 1, '2026-05-01 10:00:00'),
('PN_20260501101500_1006', 1, '2026-05-01 10:15:00'),
('PN_20260501110000_1009', 1, '2026-05-01 11:00:00'),
('PN_20260501111500_1010', 1, '2026-05-01 11:15:00'),
('PN_20260515090000_1011', 1, '2026-05-15 09:00:00'),
('PN_20260515110000_1015', 1, '2026-05-15 11:00:00'),
('PN_20260515113000_1016', 1, '2026-05-15 11:30:00'),
('PN_20260515120000_1017', 1, '2026-05-15 12:00:00'),
('PN_20260515123000_1018', 1, '2026-05-15 12:30:00'),
('PN_20260610090000_1019', 1, '2026-06-10 09:00:00'),
('PN_20260610103000_1022', 1, '2026-06-10 10:30:00'),
('PN_20260610110000_1023', 1, '2026-06-10 11:00:00'),
('PN_20260610113000_1024', 1, '2026-06-10 11:30:00'),
('PN_20260610120000_1025', 1, '2026-06-10 12:00:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieu_xuat`
--

CREATE TABLE `phieu_xuat` (
  `ma_phieu` varchar(50) NOT NULL,
  `nguoi_tao` int(11) NOT NULL,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phieu_xuat`
--

INSERT INTO `phieu_xuat` (`ma_phieu`, `nguoi_tao`, `ngay_tao`) VALUES
('PX_20260510140000_2001', 1, '2026-05-10 14:00:00'),
('PX_20260510143000_2002', 1, '2026-05-10 14:30:00'),
('PX_20260510150000_2003', 1, '2026-05-10 15:00:00'),
('PX_20260510153000_2004', 1, '2026-05-10 15:30:00'),
('PX_20260525090000_2007', 1, '2026-05-25 09:00:00'),
('PX_20260525093000_2008', 1, '2026-05-25 09:30:00'),
('PX_20260525100000_2009', 1, '2026-05-25 10:00:00'),
('PX_20260525113000_2012', 1, '2026-05-25 11:30:00'),
('PX_20260615090000_2013', 1, '2026-06-15 09:00:00'),
('PX_20260615093000_2014', 1, '2026-06-15 09:30:00'),
('PX_20260615100000_2015', 1, '2026-06-15 10:00:00'),
('PX_20260615113000_2018', 1, '2026-06-15 11:30:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sanpham`
--

CREATE TABLE `sanpham` (
  `MaSP` int(11) NOT NULL,
  `TenSP` varchar(200) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `Gia` decimal(15,0) NOT NULL DEFAULT 0,
  `SoLuong` int(11) NOT NULL DEFAULT 0,
  `DanhMuc` varchar(10) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `sanpham`
--

INSERT INTO `sanpham` (`MaSP`, `TenSP`, `MoTa`, `Gia`, `SoLuong`, `DanhMuc`, `is_active`) VALUES
(1, 'iPhone 15 Pro Max 256GB', 'Điện thoại Apple chính hãng, màu Titan tự nhiên', 30990000, 45, 'DTH', 1),
(2, 'Samsung Galaxy S24 Ultra', 'Điện thoại kèm bút S-Pen, hỗ trợ Galaxy AI', 27490000, 30, 'DTH', 1),
(3, 'iPad Air 6 M2', 'Máy tính bảng Apple hiệu năng cao, màn hình 11 inch', 16290000, 15, 'DTH', 0),
(4, 'MacBook Air M3 8GB/256GB', 'Laptop mỏng nhẹ, pin trâu, màu Midnight', 27990000, 25, 'LAP', 0),
(5, 'Asus ROG Strix G16', 'Laptop gaming cấu hình cao, RTX 4060, Intel i7', 34500000, 8, 'LAP', 1),
(6, 'Dell Inspiron 14 5430', 'Laptop văn phòng vỏ nhôm, core i5 đời mới', 16800000, 40, 'LAP', 1),
(7, 'Sạc dự phòng Anker 20000mAh', 'Sạc nhanh PowerIQ 22.5W, 2 cổng USB-C', 650000, 150, 'PKI', 1),
(8, 'Chuột không dây Logitech MX Master 3S', 'Chuột công thái học cao cấp cho lập trình viên', 2490000, 60, 'PKI', 1),
(9, 'Bàn phím cơ Keychron K2 V2', 'Bàn phím Bluetooth, Gateron Brown Switch', 1850000, 35, 'PKI', 1),
(10, 'Cáp sạc Baseus USB-C to Lightning', 'Cáp bọc dù siêu bền, hỗ trợ sạc nhanh PD 20W', 120000, 300, 'PKI', 1),
(11, 'Tai nghe Sony WH-1000XM5', 'Tai nghe chụp tai chống ồn chủ động cao cấp', 6990000, 12, 'ATH', 1),
(12, 'Loa Bluetooth JBL Charge 5', 'Loa kháng nước IP67, pin dùng 20 giờ liên tục', 3850000, 5, 'ATH', 1),
(13, 'Tai nghe AirPods Pro Gen 2', 'Tai nghe True Wireless chip H2, chống ồn tốt hơn', 5590000, 85, 'ATH', 1),
(14, 'Robot hút bụi Xiaomi Vacuum X20', 'Robot hút bụi lau nhà thông minh, lực hút 5000Pa', 7200000, 18, 'GDG', 1),
(15, 'Nồi chiên không dầu Philips HD9252', 'Dung tích 4.1L, công nghệ Rapid Air', 2350000, 22, 'GDG', 1),
(16, 'Thẻ quà tặng Got It', 'Thẻ mua sắm điện tử mệnh giá 500k', 500000, 100, 'QUA', 1),
(17, 'Oppo Reno11 Pro 5G', 'Điện thoại chụp ảnh chân dung chuyên nghiệp', 11990000, 168, 'DTH', 1),
(18, 'Xiaomi 14 Ultra', 'Điện thoại cao cấp camera Leica ống kính lớn', 29990000, 33, 'DTH', 1),
(19, 'Samsung Galaxy Z Fold5', 'Điện thoại màn hình gập cao cấp, đa nhiệm tốt', 33990000, 11, 'DTH', 1),
(20, 'iPhone 13 128GB', 'Điện thoại Apple quốc dân, hiệu năng ổn định', 13500000, 194, 'DTH', 1),
(21, 'iPad Pro 11 M4', 'Máy tính bảng màn hình Tandem OLED siêu mỏng', 28990000, 75, 'DTH', 1),
(22, 'Samsung Galaxy Tab S9', 'Máy tính bảng chống nước IP68 kèm bút S-Pen', 17490000, 67, 'DTH', 1),
(23, 'Xiaomi Pad 6', 'Máy tính bảng phân khúc tầm trung giải trí tốt', 7990000, 62, 'DTH', 1),
(24, 'Vivo V30 5G', 'Điện thoại thiết kế mỏng nhẹ, camera vòng sáng Aura', 10490000, 40, 'DTH', 1),
(25, 'Realme 12 Pro+', 'Điện thoại tầm trung có camera periscope zoom xa', 9990000, 193, 'DTH', 1),
(26, 'iPhone 15 128GB', 'Điện thoại Apple tiêu chuẩn, có Dynamic Island', 19990000, 31, 'DTH', 1),
(27, 'HP Pavilion 15', 'Laptop học tập văn phòng màn hình lớn, chip AMD', 14200000, 178, 'LAP', 1),
(28, 'Lenovo Legion 5', 'Laptop gaming quốc dân tản nhiệt tốt, Ryzen 7', 26900000, 194, 'LAP', 1),
(29, 'Acer Aspire 7', 'Laptop cấu hình khá có card rời giá rẻ', 15490000, 233, 'LAP', 1),
(30, 'MSI Cyborg 15', 'Laptop gaming mỏng nhẹ, thiết kế xuyên thấu', 19800000, 144, 'LAP', 1),
(31, 'Dell XPS 13 Plus', 'Laptop doanh nhân cao cấp, bàn phím tràn viền', 45000000, 27, 'LAP', 1),
(32, 'MacBook Pro 14 M3 Pro', 'Laptop đồ họa chuyên nghiệp, màn hình Liquid Retina XDR', 54990000, 156, 'LAP', 1),
(33, 'Asus Zenbook 14 OLED', 'Laptop màn hình OLED chuẩn màu, mỏng nhẹ thời trang', 24500000, 113, 'LAP', 1),
(34, 'LG Gram 16 2024', 'Laptop siêu nhẹ màn hình lớn 16 inch, pin siêu khỏe', 31000000, 13, 'LAP', 1),
(35, 'Lenovo Yoga Slim 7', 'Laptop cao cấp mỏng nhẹ vỏ nhôm nguyên khối', 22900000, 12, 'LAP', 1),
(36, 'Acer Predator Helios 16', 'Laptop gaming phân khúc cao cấp hiệu năng cực khủng', 42000000, 28, 'LAP', 1),
(37, 'Chuột Logitech G304 Wireless', 'Chuột gaming không dây pin lâu, mắt đọc HERO', 790000, 60, 'PKI', 1),
(38, 'Bàn phím Akko 3098B', 'Bàn phím cơ layout 98 phím, keycap PBT chất lượng', 165000, 64, 'PKI', 1),
(39, 'Củ sạc Ugreen GaN 65W', 'Củ sạc nhanh 3 cổng, công nghệ GaN nhỏ gọn', 480000, 134, 'PKI', 1),
(40, 'Ổ cứng di động SSD Samsung T7 1TB', 'Ổ cứng tốc độ cao, vỏ kim loại chống va đập', 2550000, 159, 'PKI', 1),
(41, 'Lót chuột cỡ lớn 80x30cm', 'Bàn di chuột may bo viền dày dặn chống trượt', 120000, 11, 'PKI', 1),
(42, 'Hub chuyển đổi Type-C Baseus 6 in 1', 'Mở rộng cổng HDMI, USB 3.0 và khe đọc thẻ', 390000, 148, 'PKI', 1),
(43, 'Giá đỡ Laptop kim loại', 'Kệ tản nhiệt hợp kim nhôm chỉnh độ cao linh hoạt', 180000, 55, 'PKI', 1),
(44, 'Kính cường lực iPhone 15 Pro', 'Kính full màn hình chống trầy xước va đập tốt', 95000, 188, 'PKI', 1),
(45, 'Bút cảm ứng Baseus Smooth Writing', 'Bút dùng cho iPad, viết mượt có từ tính hít', 450000, 171, 'PKI', 1),
(46, 'Đế sạc không dây 3 in 1', 'Sạc đồng thời iPhone, Apple Watch và AirPods', 680000, 184, 'PKI', 1),
(47, 'Loa Bluetooth Marshall Emberton II', 'Loa thiết kế cổ điển, âm thanh 360 độ đặc trưng', 4290000, 144, 'ATH', 1),
(48, 'Tai nghe Sony WF-C500', 'Tai nghe True Wireless nhỏ gọn pin trâu giá rẻ', 1450000, 112, 'ATH', 1),
(49, 'Loa thanh Soundbar Samsung HW-Q600C', 'Hệ thống âm thanh vòm Dolby Atmos sống động', 4990000, 61, 'ATH', 1),
(50, 'Tai nghe chụp tai Marshall Major IV', 'Tai nghe pin hơn 80 giờ, sạc không dây tiện lợi', 3690000, 119, 'ATH', 1),
(51, 'Mic thu âm không dây Rode Wireless GO II', 'Micro thu âm chuyên nghiệp cho vlogger, creator', 6200000, 155, 'ATH', 1),
(52, 'Loa Bluetooth Sony SRS-XE200', 'Loa kháng nước bụi IP67, âm thanh lan tỏa rộng', 2190000, 76, 'ATH', 1),
(53, 'Tai nghe JBL Tune 230NC', 'Tai nghe chống ồn chủ động, bass mạnh mẽ', 1690000, 212, 'ATH', 1),
(54, 'Loa Bluetooth Huawei Sound Joy', 'Loa đồng thiết kế Devialet âm bass uy lực', 2390000, 227, 'ATH', 1),
(55, 'Tai nghe Gaming Kingston HyperX Cloud II', 'Tai nghe giả lập âm thanh 7.1 chơi game chuẩn', 1890000, 6, 'ATH', 1),
(56, 'Loa vi tính Logitech Z313', 'Hệ thống loa 2.1 có trầm cho máy tính để bàn', 950000, 199, 'ATH', 1),
(57, 'Máy lọc không khí Xiaomi 4 Pro', 'Lọc bụi mịn PM2.5, khử mùi hiệu quả phòng lớn', 4150000, 211, 'GDG', 1),
(58, 'Quạt cây thông minh Xiaomi Gen 3', 'Quạt biến tần DC chạy êm, có pin tích điện dự phòng', 1950000, 45, 'GDG', 1),
(59, 'Bình đun siêu tốc giữ nhiệt Philips', 'Dung tích 1.7L, ruột inox 314 an toàn sức khỏe', 850000, 183, 'GDG', 1),
(60, 'Máy hút bụi cầm tay Deerma DX700', 'Thiết kế nhỏ gọn, lực hút mạnh đa năng', 650000, 113, 'GDG', 1),
(61, 'Lò vi sóng Sharp 20L', 'Lò vi sóng có chức năng nướng tiện lợi dễ dùng', 1750000, 92, 'GDG', 1),
(62, 'Nồi cơm điện cao tần Toshiba 1L', 'Công nghệ cao tần IH nấu cơm chín đều thơm ngon', 2890000, 76, 'GDG', 1),
(63, 'Máy tăm nước Panasonic EW1511', 'Công nghệ siêu âm làm sạch răng nướu nhẹ nhàng', 2400000, 44, 'GDG', 1),
(64, 'Bàn là hơi nước cầm tay Tefal', 'Thiết kế gấp gọn tiện mang đi du lịch, là nhanh', 790000, 60, 'GDG', 1),
(65, 'Máy pha cà phê Delonghi Dedica', 'Máy pha Espresso bán tự động bằng vỏ kim loại', 6800000, 250, 'GDG', 1),
(66, 'Máy xay sinh tố cầm tay Braun', 'Công suất lớn xay mịn đá và thực phẩm ăn dặm', 1590000, 200, 'GDG', 1),
(67, 'Samsung Galaxy A55 5G', 'Điện thoại tầm trung, thiết kế mặt lưng kính sang trọng', 9990000, 47, 'DTH', 1),
(68, 'Oppo Reno12 F', 'Điện thoại chụp ảnh selfie đẹp, hỗ trợ sạc nhanh SuperVOOC', 7490000, 127, 'DTH', 1),
(69, 'Xiaomi Redmi Note 13 Pro', 'Điện thoại cấu hình mạnh, camera 200MP siêu nét', 6890000, 126, 'DTH', 1),
(70, 'Vivo Y100 5G', 'Điện thoại thiết kế thời trang, pin lớn 5000mAh', 5590000, 54, 'DTH', 1),
(71, 'Realme C65', 'Điện thoại phân khúc giá rẻ hiệu năng ổn định mượt mà', 3690000, 190, 'DTH', 1),
(72, 'iPhone 14 Plus 128GB', 'Điện thoại Apple màn hình lớn, thời lượng pin cực trâu', 21490000, 110, 'DTH', 1),
(73, 'iPad Mini 6 Wi-Fi', 'Máy tính bảng Apple kích thước nhỏ gọn, chip A15 Bionic', 12990000, 197, 'DTH', 1),
(74, 'Samsung Galaxy Tab A9+', 'Máy tính bảng màn hình 11 inch giải trí mượt mà', 5490000, 99, 'DTH', 1),
(75, 'Lenovo Tab M11', 'Máy tính bảng học tập kèm bút cảm ứng tiện lợi', 4290000, 120, 'DTH', 1),
(76, 'Huawei MatePad 11.5', 'Máy tính bảng màn hình 120Hz mượt mà, hỗ trợ bàn phím', 6990000, 139, 'DTH', 1),
(77, 'iPhone 15 Pro 128GB', 'Điện thoại cao cấp vỏ Titan, chip A17 Pro mạnh mẽ', 24990000, 38, 'DTH', 1),
(78, 'Samsung Galaxy S23 FE', 'Điện thoại cấu hình cao cấp giá hợp lý cho giới trẻ', 12490000, 146, 'DTH', 1),
(79, 'Xiaomi Poco X6 Pro', 'Điện thoại chuyên game cấu hình cực khủng trong tầm giá', 8490000, 41, 'DTH', 1),
(80, 'Oppo A3 5G', 'Điện thoại phân khúc phổ thông độ bền chuẩn quân đội', 4990000, 30, 'DTH', 1),
(81, 'Vivo V40 Lite', 'Điện thoại chụp ảnh đêm đẹp, thiết kế mỏng nhẹ', 8290000, 198, 'DTH', 1),
(82, 'Honor 200 5G', 'Điện thoại màn hình cong, camera chụp chân dung cao cấp', 11990000, 126, 'DTH', 1),
(83, 'Sony Xperia 1 VI', 'Điện thoại cao cấp màn hình chuẩn điện ảnh, camera zoom', 28990000, 77, 'DTH', 1),
(84, 'Asus ROG Phone 8', 'Điện thoại gaming chuyên nghiệp có tản nhiệt đỉnh cao', 23990000, 22, 'DTH', 1),
(85, 'Samsung Galaxy Tab S9 FE', 'Máy tính bảng kháng nước kháng bụi, kèm bút S-Pen', 10490000, 178, 'DTH', 1),
(86, 'Xiaomi Pad 6 Pro', 'Máy tính bảng màn hình 2K siêu nét, sạc siêu nhanh', 9500000, 175, 'DTH', 1),
(87, 'Asus Vivobook 14 OLED', 'Laptop văn phòng màn hình OLED màu sắc chuẩn xác', 15990000, 62, 'LAP', 1),
(88, 'HP Envy x360 14', 'Laptop xoay gập 360 độ cảm ứng, vỏ nhôm mỏng nhẹ', 21800000, 95, 'LAP', 1),
(89, 'Lenovo IdeaPad Slim 3', 'Laptop phân khúc học sinh sinh viên giá tốt bền bỉ', 11200000, 68, 'LAP', 1),
(90, 'Acer Swift Go 14', 'Laptop mỏng nhẹ chuẩn Intel Evo, màn hình siêu đẹp', 17990000, 89, 'LAP', 1),
(91, 'MSI Thin 15', 'Laptop gaming mỏng nhẹ, card đồ họa rời GTX series', 16500000, 62, 'LAP', 1),
(92, 'Dell Vostro 3430', 'Laptop văn phòng phân khúc phổ thông siêu bền bỉ', 13400000, 55, 'LAP', 1),
(93, 'MacBook Pro 16 M3 Max', 'Máy trạm đồ họa Apple đỉnh cao cho nhà làm phim', 79990000, 46, 'LAP', 1),
(94, 'Asus TUF Gaming A15', 'Laptop gaming phân khúc tầm trung siêu bền chuẩn quân đội', 21490000, 58, 'LAP', 1),
(95, 'HP Victus 16 2024', 'Laptop gaming thiết kế thanh lịch gọn gàng hiệu năng cao', 22800000, 98, 'LAP', 1),
(96, 'Lenovo ThinkPad E14', 'Laptop doanh nghiệp bảo mật cao, bàn phím gõ êm', 18500000, 104, 'LAP', 1),
(97, 'Acer Nitro V 15', 'Laptop gaming quốc dân thế hệ mới tản nhiệt tốt', 19490000, 170, 'LAP', 1),
(98, 'Gigabyte G5 Gaming', 'Laptop chơi game đồ họa giá rẻ cấu hình mạnh', 17800000, 114, 'LAP', 1),
(99, 'Dell Latitude 3540', 'Laptop văn phòng màn hình lớn 15.6 inch tiện lợi', 14200000, 63, 'LAP', 1),
(100, 'Huawei MateBook D14', 'Laptop vỏ kim loại mỏng nhẹ, sạc Type-C đa năng', 13990000, 113, 'LAP', 1),
(101, 'Microsoft Surface Laptop 5', 'Laptop cao cấp màn hình tỉ lệ 3:2 cảm ứng mượt', 26500000, 128, 'LAP', 1),
(102, 'PC Asus ROG Strix G10', 'Máy tính để bàn gaming nguyên bộ đồng bộ cao cấp', 18900000, 152, 'LAP', 1),
(103, 'PC HP Pavilion TP01', 'Máy tính để bàn văn phòng nhỏ gọn hiệu năng ổn định', 12500000, 80, 'LAP', 1),
(104, 'Apple iMac 24 inch M3', 'Máy tính All-in-One màn hình Retina 4.5K siêu đẹp', 36990000, 106, 'LAP', 1),
(105, 'Apple Mac Mini M3', 'Máy tính để bàn mini siêu nhỏ gọn hiệu năng mạnh', 14990000, 51, 'LAP', 1),
(106, 'LG Gram Pro 16', 'Laptop siêu cao cấp trọng lượng dưới 1.2kg pin trâu', 38900000, 176, 'LAP', 1),
(107, 'Sạc nhanh Anker Nano 30W', 'Củ sạc siêu nhỏ gọn dùng công nghệ GaN cho iPhone', 320000, 173, 'PKI', 1),
(108, 'Chuột Gaming Razer DeathAdder V2', 'Chuột chơi game công thái học mắt đọc chính xác', 1250000, 41, 'PKI', 1),
(109, 'Bàn phím cơ Asus ROG Strix Scope', 'Bàn phím cơ chuyên game đèn LED RGB rực rỡ', 2890000, 56, 'PKI', 1),
(110, 'Cáp HDMI Ugreen 2.0 dài 2m', 'Cáp truyền hình ảnh âm thanh độ phân giải 4K', 150000, 11, 'PKI', 1),
(111, 'Túi chống sốc Laptop Tomtoc', 'Túi đựng bảo vệ laptop chống va đập tiêu chuẩn Mỹ', 550000, 164, 'PKI', 1),
(112, 'Ổ cứng SSD Kingston NV2 512GB', 'Ổ cứng lưu trữ SSD M.2 NVMe tốc độ đọc ghi cao', 980000, 111, 'PKI', 1),
(113, 'Thẻ nhớ MicroSD SanDisk 128GB', 'Thẻ nhớ tốc độ cao chuyên dụng cho camera, điện thoại', 290000, 47, 'PKI', 1),
(114, 'Bộ kích sóng Wi-Fi TP-Link', 'Thiết bị mở rộng vùng phủ sóng Wi-Fi gia đình', 350000, 154, 'PKI', 1),
(115, 'Chuột không dây yên lặng Logitech M220', 'Chuột văn phòng nút bấm êm ái không gây tiếng ồn', 290000, 51, 'PKI', 1),
(116, 'Bàn phím không dây Logitech K380', 'Bàn phím kết nối đa thiết bị nhỏ gọn thời trang', 650000, 59, 'PKI', 1),
(117, 'Đèn LED treo màn hình Baseus', 'Đèn chiếu sáng bàn làm việc bảo vệ mắt ban đêm', 480000, 52, 'PKI', 1),
(118, 'Giá đỡ điện thoại để bàn Baseus', 'Kệ đỡ bằng kim loại chắc chắn chỉnh góc linh hoạt', 120000, 16, 'PKI', 1),
(119, 'Quạt tản nhiệt sò lạnh cho điện thoại', 'Thiết bị làm mát điện thoại khi chơi game liên tục', 250000, 180, 'PKI', 1),
(120, 'USB Kingston DataTraveler 64GB', 'Thiết bị lưu trữ dữ liệu di động chuẩn USB 3.2', 160000, 70, 'PKI', 1),
(121, 'Bút trình chiếu Logitech R400', 'Thiết bị điều khiển slide thuyết trình từ xa', 420000, 124, 'PKI', 1),
(122, 'Pin sạc dự phòng Xiaomi 10000mAh', 'Sạc dự phòng vỏ nhôm hỗ trợ sạc nhanh 22.5W', 390000, 173, 'PKI', 1),
(123, 'Balo Laptop chống nước Xiaomi', 'Balo thời trang nhiều ngăn đựng vừa laptop 15.6 inch', 450000, 109, 'PKI', 1),
(124, 'Keo tản nhiệt MX-4 4g', 'Keo làm mát vi xử lý CPU máy tính hiệu năng cao', 150000, 42, 'PKI', 1),
(125, 'Tay cầm chơi game Sony DualSense', 'Tay cầm chơi game chính hãng máy PS5 màu trắng', 1690000, 169, 'PKI', 1),
(126, 'Dây cáp sạc đa năng 3 trong 1 Anker', 'Cáp tích hợp đầu Type-C, Lightning và Micro USB', 290000, 151, 'PKI', 1),
(127, 'Loa Bluetooth Sony SRS-XB100', 'Loa di động siêu nhỏ gọn, chống nước, âm trầm tốt', 1190000, 23, 'ATH', 1),
(128, 'Tai nghe chụp tai JBL Tune 520BT', 'Tai nghe không dây pin 57 tiếng, chất âm Pure Bass', 1290000, 165, 'ATH', 1),
(129, 'Loa Bluetooth Marshall Acton III', 'Loa cắm điện gia đình decor sang trọng, âm thanh chất', 6790000, 73, 'ATH', 1),
(130, 'Tai nghe True Wireless Soundpeats Clear', 'Tai nghe thiết kế trong suốt cá tính giá học sinh', 550000, 193, 'ATH', 1),
(131, 'Loa vi tính SoundMax A927', 'Hệ thống loa 2.1 kết nối Bluetooth nghe nhạc hay', 850000, 168, 'ATH', 1),
(132, 'Tai nghe Gaming Razer BlackShark V2 X', 'Tai nghe chơi game âm thanh vòm microphone lọc ồn', 1390000, 139, 'ATH', 1),
(133, 'Loa kiểm âm Edifier R1280DB', 'Loa bookshelf phòng làm việc kết nối quang học và BT', 2650000, 192, 'ATH', 1),
(134, 'Tai nghe Apple EarPods cổng Type-C', 'Tai nghe dây chính hãng Apple chất lượng thoại rõ nét', 490000, 84, 'ATH', 1),
(135, 'Loa Bluetooth JBL Go 4', 'Loa di động bỏ túi kháng nước thế hệ mới nhiều màu', 990000, 186, 'ATH', 1),
(136, 'Tai nghe chống ồn Bose QuietComfort Ultra', 'Tai nghe chụp tai cao cấp chống ồn đỉnh cao thế giới', 9490000, 161, 'ATH', 1),
(137, 'Loa Soundbar Sony HT-S20R', 'Dàn âm thanh rạp phim gia đình 5.1 công suất 400W', 4290000, 96, 'ATH', 1),
(138, 'Tai nghe True Wireless Samsung Galaxy Buds3', 'Tai nghe thiết kế công thái học mới hỗ trợ AI âm thanh', 3490000, 145, 'ATH', 1),
(139, 'Mic thu âm livestream Boya BY-M1', 'Micro cài áo dây dài 6m chuyên phỏng vấn quay video', 220000, 184, 'ATH', 1),
(140, 'Loa Bluetooth Harman Kardon Onyx Studio 8', 'Loa di động thiết kế hình hành tinh độc đáo bass sâu', 5290000, 97, 'ATH', 1),
(141, 'Tai nghe thể thao Shokz OpenMove', 'Tai nghe dẫn truyền xương không nhét tai an toàn', 1990000, 148, 'ATH', 1),
(142, 'Loa kéo di động Acnos CS445', 'Loa karaoke xách tay công suất lớn kèm 2 micro không dây', 4800000, 102, 'ATH', 1),
(143, 'Tai nghe chụp tai Sony WH-CH520', 'Tai nghe không dây phân khúc phổ thông pin 50 giờ', 1150000, 133, 'ATH', 1),
(144, 'Loa Bluetooth Anker Soundcore Motion+', 'Loa di động âm thanh Hi-Res công suất lớn 30W', 2100000, 112, 'ATH', 1),
(145, 'Tai nghe Sennheiser HD 206', 'Tai nghe kiểm âm dạng đóng giá rẻ cho phòng thu', 590000, 25, 'ATH', 1),
(146, 'Cáp âm thanh AUX 3.5mm Baseus', 'Cáp truyền tín hiệu âm thanh chống nhiễu bọc dù', 95000, 35, 'ATH', 1),
(147, 'Nồi cơm điện tử Tefal 1.8L', 'Nồi cơm lòng niêu nấu cơm ngon giữ ấm lâu', 1390000, 86, 'GDG', 1),
(148, 'Lò vi sóng có nướng Panasonic', 'Dung tích 23L, bảng điều khiển điện tử hiện đại', 2950000, 176, 'GDG', 1),
(149, 'Ấm siêu tốc thủy tinh LocknLock', 'Bình đun nước có đèn LED xanh, tự ngắt khi sôi', 550000, 121, 'GDG', 1),
(150, 'Máy hút bụi lau sàn Tineco Floor One', 'Máy hút bụi lau nhà khô và ướt thông minh tự giặt giẻ', 8900000, 74, 'GDG', 1),
(151, 'Máy làm sữa hạt Unie V8S', 'Máy xay nấu đa năng, làm sữa hạt, nấu cháo mịn', 2450000, 71, 'GDG', 1),
(152, 'Quạt tháp lọc không khí Ultty', 'Quạt không cánh cao cấp kết hợp màng lọc HEPA', 4500000, 190, 'GDG', 1),
(153, 'Bếp từ đơn Kangaroo', 'Bếp từ ăn lẩu nhỏ gọn kèm nồi, công suất 2000W', 790000, 192, 'GDG', 1),
(154, 'Bàn là hơi nước đứng Philips', 'Bàn ủi phun hơi nước mạnh mẽ ủi quần áo treo dễ dàng', 2390000, 121, 'GDG', 1),
(155, 'Máy ép chậm Panasonic', 'Máy ép trái cây tốc độ chậm giữ trọn vitamin dưỡng chất', 4200000, 141, 'GDG', 1),
(156, 'Máy tăm nước Oral-B', 'Thiết bị làm sạch kẽ răng bằng tia nước chuyên nghiệp', 1850000, 136, 'GDG', 1),
(157, 'Robot lau nhà Ecovacs Deebot T20', 'Robot hút bụi lau nhà giặt giẻ bằng nước nóng', 12500000, 159, 'GDG', 1),
(158, 'Máy lọc nước RO Kangaroo 10 lõi', 'Máy lọc nước gia đình cung cấp khoáng chất tinh khiết', 5490000, 75, 'GDG', 1),
(159, 'Đèn bàn học thông minh Xiaomi LED', 'Đèn chống cận thị điều chỉnh độ sáng qua app điện thoại', 650000, 47, 'GDG', 1),
(160, 'Máy sấy tóc ion âm Panasonic', 'Máy sấy công suất lớn bảo vệ tóc không bị khô xơ', 890000, 24, 'GDG', 1),
(161, 'Nồi chiên không dầu Cosori 5.5L', 'Nồi chiên thương hiệu Mỹ, điều khiển cảm ứng tiện lợi', 2650000, 183, 'GDG', 1),
(162, 'Máy pha cà phê viên nén Nespresso', 'Máy pha cà phê capsule nhỏ gọn tiện lợi cho văn phòng', 3990000, 159, 'GDG', 1),
(163, 'Cân sức khỏe điện tử Xiaomi', 'Cân đo chỉ số cơ thể kết nối Bluetooth theo dõi sức khỏe', 350000, 93, 'GDG', 1),
(164, 'Máy xay thịt Philips', 'Cối xay thịt bằng thủy tinh dày dặn lưỡi dao bén', 950000, 52, 'GDG', 1),
(165, 'Máy đánh trứng cầm tay Philips', 'Thiết bị làm bánh 5 tốc độ trộn công suất khỏe', 690000, 23, 'GDG', 1),
(166, 'Ổ cắm điện thông minh Wi-Fi Tuya', 'Ổ cắm điều khiển hẹn giờ tắt mở thiết bị từ xa', 180000, 80, 'GDG', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sessions`
--

CREATE TABLE `sessions` (
  `session_token` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `sessions`
--

INSERT INTO `sessions` (`session_token`, `user_id`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
('285ee61f10abcc4eae8c63c31c525aa18c19b6fb72e19dd4169565cb5f82a7b86da5bcc717c53b2b6b83713364b3aca78b4427325bb8f6b5e26659c0fbd9477a', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 17:49:58', '2026-07-25 03:49:58'),
('6092a4bb17f8ea3b33474757d76bbdeefde2755c627e9bda648c238a9786c87750684521e202768e17aa8e13f4a9738d4a85b65dd57414e7ffc841b5b87661c7', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-24 17:48:50', '2026-07-25 03:48:50'),
('e2e33571c1d56db9c82a0f8bd0455ade123e8976ed0f6cd2fb6ed7c0b0073337ef5336dd8968000e9e31a1297d5680e479111fb6860ca70a3e5f96738d0574ae', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '2026-07-24 17:35:14', '2026-07-25 03:35:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','store_manager','staff') NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `allow_import_export` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `is_active`, `allow_import_export`, `created_by`, `created_at`, `last_login`) VALUES
(1, 'admin', '$2y$12$tLxN.y5yFOlS4i676dUWJulxHF3T3imvv0VteXiNx7MphW6qhGjqW', 'Quản trị viên', 'admin', 1, 1, 1, '2026-07-15 16:06:35', '2026-07-24 17:35:14'),
(2, 'nv1', '$2y$12$Ty.RlU5SMnWGXZ.nL1tWe.rWUhEuvtD6OHm9w9rA8qM8E/6uxeWCK', 'nv1', 'staff', 1, 1, 1, '2026-07-17 07:24:39', '2026-07-24 17:48:50'),
(3, 'chtruong1', '$2y$12$/o3yao3oowPI6Te1Y0wYT.aWMRO73CEpD0aaPKyhS8.TARzXAPhR2', 'a', 'store_manager', 1, 0, 1, '2026-07-24 14:17:06', '2026-07-24 17:49:58');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `chi_tiet_phieu_nhap`
--
ALTER TABLE `chi_tiet_phieu_nhap`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ctpn_ma_phieu` (`ma_phieu`),
  ADD KEY `fk_ctpn_sanpham` (`san_pham`);

--
-- Chỉ mục cho bảng `chi_tiet_phieu_xuat`
--
ALTER TABLE `chi_tiet_phieu_xuat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ctpx_ma_phieu` (`ma_phieu`),
  ADD KEY `fk_ctpx_sanpham` (`san_pham`);

--
-- Chỉ mục cho bảng `danhmuc`
--
ALTER TABLE `danhmuc`
  ADD PRIMARY KEY (`MaDM`);

--
-- Chỉ mục cho bảng `phieu_nhap`
--
ALTER TABLE `phieu_nhap`
  ADD PRIMARY KEY (`ma_phieu`),
  ADD KEY `fk_pn_nguoi_tao` (`nguoi_tao`),
  ADD KEY `fk_pn_ngay_tao` (`ngay_tao`);

--
-- Chỉ mục cho bảng `phieu_xuat`
--
ALTER TABLE `phieu_xuat`
  ADD PRIMARY KEY (`ma_phieu`),
  ADD KEY `fk_px_nguoi_tao` (`nguoi_tao`),
  ADD KEY `fk_px_ngay_tao` (`ngay_tao`);

--
-- Chỉ mục cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  ADD PRIMARY KEY (`MaSP`),
  ADD KEY `FK_SP_DanhMuc` (`DanhMuc`);
ALTER TABLE `sanpham` ADD FULLTEXT KEY `ft_search` (`TenSP`,`MoTa`);

--
-- Chỉ mục cho bảng `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `chi_tiet_phieu_nhap`
--
ALTER TABLE `chi_tiet_phieu_nhap`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT cho bảng `chi_tiet_phieu_xuat`
--
ALTER TABLE `chi_tiet_phieu_xuat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `MaSP` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chi_tiet_phieu_nhap`
--
ALTER TABLE `chi_tiet_phieu_nhap`
  ADD CONSTRAINT `fk_ctpn_ma_phieu` FOREIGN KEY (`ma_phieu`) REFERENCES `phieu_nhap` (`ma_phieu`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ctpn_sanpham` FOREIGN KEY (`san_pham`) REFERENCES `sanpham` (`MaSP`);

--
-- Các ràng buộc cho bảng `chi_tiet_phieu_xuat`
--
ALTER TABLE `chi_tiet_phieu_xuat`
  ADD CONSTRAINT `fk_ctpx_ma_phieu` FOREIGN KEY (`ma_phieu`) REFERENCES `phieu_xuat` (`ma_phieu`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ctpx_sanpham` FOREIGN KEY (`san_pham`) REFERENCES `sanpham` (`MaSP`);

--
-- Các ràng buộc cho bảng `phieu_nhap`
--
ALTER TABLE `phieu_nhap`
  ADD CONSTRAINT `fk_pn_nguoi_tao` FOREIGN KEY (`nguoi_tao`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `phieu_xuat`
--
ALTER TABLE `phieu_xuat`
  ADD CONSTRAINT `fk_px_nguoi_tao` FOREIGN KEY (`nguoi_tao`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  ADD CONSTRAINT `FK_SP_DanhMuc` FOREIGN KEY (`DanhMuc`) REFERENCES `danhmuc` (`MaDM`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

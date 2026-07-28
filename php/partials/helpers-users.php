<?php
function getDashboardData(mysqli $conn): array
{
    $data = [
        'total_categories'   => 0,
        'total_quantity'     => 0,
        'total_val'          => 0,
        'total_low'          => 0,
        'total_out'          => 0,
        'import_this_month'  => 0,
        'export_this_month'  => 0,
        'revenue_this_month' => 0,
        'chart1_labels'      => [],
        'chart1_data'        => [],
        'chart2_labels'      => [],
        'chart2_data'        => [],
        'chart_trend_labels' => [],
        'chart_trend_import' => [],
        'chart_trend_export' => [],
        'chart_status_labels' => ['Còn hàng', 'Sắp hết', 'Hết hàng'],
        'chart_status_data'  => [0, 0, 0],
        'chart_top_labels'   => [],
        'chart_top_data'     => [],
        'low_stock_list'     => [],
        'top_selling_list'   => [],
        'recent_receipts'    => [],
        'categories_list'    => [],
    ];

    if (!$conn) return $data;

    // ── KPI cơ bản ──────────────────────────────────────────────────────
    if ($res = $conn->query("SELECT COUNT(*) as total FROM danhmuc")) {
        $data['total_categories'] = $res->fetch_assoc()['total'];
    }
    if ($res = $conn->query("SELECT SUM(SoLuong) as total FROM sanpham")) {
        $data['total_quantity'] = $res->fetch_assoc()['total'] ?? 0;
    }
    if ($res = $conn->query("SELECT SUM(SoLuong * Gia) as total FROM sanpham")) {
        $data['total_val'] = $res->fetch_assoc()['total'] ?? 0;
    }
    if ($res = $conn->query("SELECT COUNT(*) as total FROM sanpham WHERE SoLuong > 0 AND SoLuong < 30")) {
        $data['total_low'] = $res->fetch_assoc()['total'];
    }
    if ($res = $conn->query("SELECT COUNT(*) as total FROM sanpham WHERE SoLuong = 0")) {
        $data['total_out'] = $res->fetch_assoc()['total'];
    }

    // ── KPI tháng này ───────────────────────────────────────────────────
    $month_start = date('Y-m-01');
    $month_end   = date('Y-m-t 23:59:59');

    if ($res = $conn->query("SELECT COUNT(*) as total FROM phieu_nhap WHERE ngay_tao BETWEEN '$month_start' AND '$month_end'")) {
        $data['import_this_month'] = $res->fetch_assoc()['total'];
    }
    if ($res = $conn->query("SELECT COUNT(*) as total FROM phieu_xuat WHERE ngay_tao BETWEEN '$month_start' AND '$month_end'")) {
        $data['export_this_month'] = $res->fetch_assoc()['total'];
    }
    if ($res = $conn->query("
        SELECT SUM(ct.so_luong * sp.Gia) as total
        FROM chi_tiet_phieu_xuat ct
        JOIN sanpham sp ON ct.san_pham = sp.MaSP
        JOIN phieu_xuat px ON ct.ma_phieu = px.ma_phieu
        WHERE px.ngay_tao BETWEEN '$month_start' AND '$month_end'
    ")) {
        $data['revenue_this_month'] = (int)($res->fetch_assoc()['total'] ?? 0);
    }

    // ── Biểu đồ 1: Số lượng theo danh mục ──────────────────────────────
    $sql_chart1 = "SELECT d.TenDM, SUM(s.SoLuong) as TongSoLuong
                  FROM sanpham s JOIN danhmuc d ON s.DanhMuc = d.MaDM
                  GROUP BY d.MaDM, d.TenDM";
    if ($res = $conn->query($sql_chart1)) {
        while ($row = $res->fetch_assoc()) {
            $data['chart1_labels'][] = $row['TenDM'];
            $data['chart1_data'][]   = (int)$row['TongSoLuong'];
        }
    }

    // ── Biểu đồ 2: Giá trị theo danh mục ──────────────────────────────
    $sql_chart2 = "SELECT d.TenDM, SUM(s.SoLuong * s.Gia) as TongGiaTri
                  FROM sanpham s JOIN danhmuc d ON s.DanhMuc = d.MaDM
                  GROUP BY d.MaDM, d.TenDM";
    if ($res = $conn->query($sql_chart2)) {
        while ($row = $res->fetch_assoc()) {
            $data['chart2_labels'][] = $row['TenDM'];
            $data['chart2_data'][]   = (float)$row['TongGiaTri'];
        }
    }

    // ── Biểu đồ xu hướng 6 tháng ──────────────────────────────────────
    for ($i = 5; $i >= 0; $i--) {
        $m_start = date('Y-m-01', strtotime("-{$i} months"));
        $m_end   = date('Y-m-t 23:59:59', strtotime("-{$i} months"));
        $label   = date('m/Y', strtotime("-{$i} months"));

        $data['chart_trend_labels'][] = $label;

        $res_imp = $conn->query("SELECT COUNT(*) as total FROM phieu_nhap WHERE ngay_tao BETWEEN '$m_start' AND '$m_end'");
        $data['chart_trend_import'][] = (int)($res_imp->fetch_assoc()['total'] ?? 0);

        $res_exp = $conn->query("SELECT COUNT(*) as total FROM phieu_xuat WHERE ngay_tao BETWEEN '$m_start' AND '$m_end'");
        $data['chart_trend_export'][] = (int)($res_exp->fetch_assoc()['total'] ?? 0);
    }

    // ── Biểu đồ trạng thái kho ─────────────────────────────────────────
    if ($res = $conn->query("SELECT
        SUM(CASE WHEN SoLuong >= 30 THEN 1 ELSE 0 END) as in_stock,
        SUM(CASE WHEN SoLuong > 0 AND SoLuong < 30 THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN SoLuong = 0 THEN 1 ELSE 0 END) as out_stock
        FROM sanpham WHERE is_active = 1
    ")) {
        $row = $res->fetch_assoc();
        $data['chart_status_data'] = [
            (int)($row['in_stock'] ?? 0),
            (int)($row['low_stock'] ?? 0),
            (int)($row['out_stock'] ?? 0),
        ];
    }

    // ── Biểu đồ top 5 sản phẩm bán chạy ────────────────────────────────
    $sql_top = "SELECT sp.TenSP, SUM(ct.so_luong) as TongBan
                FROM chi_tiet_phieu_xuat ct
                JOIN sanpham sp ON ct.san_pham = sp.MaSP
                GROUP BY sp.MaSP, sp.TenSP
                ORDER BY TongBan DESC
                LIMIT 5";
    if ($res = $conn->query($sql_top)) {
        while ($row = $res->fetch_assoc()) {
            $data['chart_top_labels'][] = $row['TenSP'];
            $data['chart_top_data'][]   = (int)$row['TongBan'];
        }
    }

    // ── Danh sách sắp hết hàng ──────────────────────────────────────────
    $sql_low = "SELECT sp.MaSP, sp.TenSP, sp.SoLuong, sp.Gia, d.TenDM
                FROM sanpham sp JOIN danhmuc d ON sp.DanhMuc = d.MaDM
                WHERE sp.SoLuong > 0 AND sp.SoLuong < 30 AND sp.is_active = 1
                ORDER BY sp.SoLuong ASC LIMIT 5";
    if ($res = $conn->query($sql_low)) {
        while ($row = $res->fetch_assoc()) {
            $data['low_stock_list'][] = $row;
        }
    }

    // ── Danh sách bán chạy nhất ────────────────────────────────────────
    $sql_top5 = "SELECT sp.MaSP, sp.TenSP, sp.Gia, d.TenDM, IFNULL(SUM(ct.so_luong), 0) as TongBan
                 FROM sanpham sp
                 JOIN danhmuc d ON sp.DanhMuc = d.MaDM
                 LEFT JOIN chi_tiet_phieu_xuat ct ON sp.MaSP = ct.san_pham
                 WHERE sp.is_active = 1
                 GROUP BY sp.MaSP, sp.TenSP, sp.Gia, d.TenDM
                 ORDER BY TongBan DESC
                 LIMIT 5";
    if ($res = $conn->query($sql_top5)) {
        while ($row = $res->fetch_assoc()) {
            $data['top_selling_list'][] = $row;
        }
    }

    // ── Phiếu nhập/xuất gần đây ────────────────────────────────────────
    $sql_recent = "(SELECT 'import' as type, pn.ma_phieu, pn.ngay_tao, u.full_name as nguoi_tao,
                    COUNT(ct.id) as so_loai, IFNULL(SUM(ct.so_luong), 0) as tong_sl
                    FROM phieu_nhap pn
                    JOIN users u ON pn.nguoi_tao = u.id
                    LEFT JOIN chi_tiet_phieu_nhap ct ON pn.ma_phieu = ct.ma_phieu
                    GROUP BY pn.ma_phieu, pn.ngay_tao, u.full_name)
                   UNION ALL
                   (SELECT 'export' as type, px.ma_phieu, px.ngay_tao, u.full_name as nguoi_tao,
                    COUNT(ct.id) as so_loai, IFNULL(SUM(ct.so_luong), 0) as tong_sl
                    FROM phieu_xuat px
                    JOIN users u ON px.nguoi_tao = u.id
                    LEFT JOIN chi_tiet_phieu_xuat ct ON px.ma_phieu = ct.ma_phieu
                    GROUP BY px.ma_phieu, px.ngay_tao, u.full_name)
                   ORDER BY ngay_tao DESC
                   LIMIT 6";
    if ($res = $conn->query($sql_recent)) {
        while ($row = $res->fetch_assoc()) {
            $data['recent_receipts'][] = $row;
        }
    }

    // ── Danh mục cho filter ─────────────────────────────────────────────
    if ($res = $conn->query("SELECT MaDM, TenDM FROM danhmuc ORDER BY TenDM ASC")) {
        while ($row = $res->fetch_assoc()) {
            $data['categories_list'][] = $row;
        }
    }

    return $data;
}

function checkManagerTarget(mysqli $conn, int $target_id): array
{
    if (($_SESSION['role'] ?? '') !== 'manager') {
        return ['allowed' => true];
    }
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $target_id);
    $stmt->execute();
    $target_role = $stmt->get_result()->fetch_assoc()['role'] ?? '';
    $stmt->close();
    if ($target_role !== 'staff') {
        return ['allowed' => false, 'message' => 'Chỉ được thao tác trên tài khoản Staff.'];
    }
    return ['allowed' => true];
}

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
    if ($res = $conn->query("SELECT SUM(stock) as total FROM sanpham")) {
        $data['total_quantity'] = $res->fetch_assoc()['total'] ?? 0;
    }
    if ($res = $conn->query("SELECT SUM(stock * price) as total FROM sanpham")) {
        $data['total_val'] = $res->fetch_assoc()['total'] ?? 0;
    }
    if ($res = $conn->query("SELECT COUNT(*) as total FROM sanpham WHERE stock > 0 AND stock < 30")) {
        $data['total_low'] = $res->fetch_assoc()['total'];
    }
    if ($res = $conn->query("SELECT COUNT(*) as total FROM sanpham WHERE stock = 0")) {
        $data['total_out'] = $res->fetch_assoc()['total'];
    }

    // ── KPI tháng này ───────────────────────────────────────────────────
    $month_start = date('Y-m-01');
    $month_end   = date('Y-m-t 23:59:59');

    if ($res = $conn->query("SELECT COUNT(*) as total FROM phieu_nhap WHERE created_at BETWEEN '$month_start' AND '$month_end'")) {
        $data['import_this_month'] = $res->fetch_assoc()['total'];
    }
    if ($res = $conn->query("SELECT COUNT(*) as total FROM phieu_xuat WHERE created_at BETWEEN '$month_start' AND '$month_end'")) {
        $data['export_this_month'] = $res->fetch_assoc()['total'];
    }
    if ($res = $conn->query("
        SELECT SUM(ct.quantity * sp.price) as total
        FROM chi_tiet_phieu_xuat ct
        JOIN sanpham sp ON ct.product_id = sp.id
        JOIN phieu_xuat px ON ct.receipt_code = px.code
        WHERE px.created_at BETWEEN '$month_start' AND '$month_end'
    ")) {
        $data['revenue_this_month'] = (int)($res->fetch_assoc()['total'] ?? 0);
    }

    // ── Biểu đồ 1: Số lượng theo danh mục ──────────────────────────────
    $sql_chart1 = "SELECT d.name, SUM(s.stock) as total_quantity
                  FROM sanpham s JOIN danhmuc d ON s.category_code = d.code
                  GROUP BY d.code, d.name";
    if ($res = $conn->query($sql_chart1)) {
        while ($row = $res->fetch_assoc()) {
            $data['chart1_labels'][] = $row['name'];
            $data['chart1_data'][]   = (int)$row['total_quantity'];
        }
    }

    // ── Biểu đồ 2: Giá trị theo danh mục ──────────────────────────────
    $sql_chart2 = "SELECT d.name, SUM(s.stock * s.price) as total_value
                  FROM sanpham s JOIN danhmuc d ON s.category_code = d.code
                  GROUP BY d.code, d.name";
    if ($res = $conn->query($sql_chart2)) {
        while ($row = $res->fetch_assoc()) {
            $data['chart2_labels'][] = $row['name'];
            $data['chart2_data'][]   = (float)$row['total_value'];
        }
    }

    // ── Biểu đồ xu hướng 6 tháng ──────────────────────────────────────
    for ($i = 5; $i >= 0; $i--) {
        $m_start = date('Y-m-01', strtotime("-{$i} months"));
        $m_end   = date('Y-m-t 23:59:59', strtotime("-{$i} months"));
        $label   = date('m/Y', strtotime("-{$i} months"));

        $data['chart_trend_labels'][] = $label;

        $res_imp = $conn->query("SELECT COUNT(*) as total FROM phieu_nhap WHERE created_at BETWEEN '$m_start' AND '$m_end'");
        $data['chart_trend_import'][] = (int)($res_imp->fetch_assoc()['total'] ?? 0);

        $res_exp = $conn->query("SELECT COUNT(*) as total FROM phieu_xuat WHERE created_at BETWEEN '$m_start' AND '$m_end'");
        $data['chart_trend_export'][] = (int)($res_exp->fetch_assoc()['total'] ?? 0);
    }

    // ── Biểu đồ trạng thái kho ─────────────────────────────────────────
    if ($res = $conn->query("SELECT
        SUM(CASE WHEN stock >= 30 THEN 1 ELSE 0 END) as in_stock,
        SUM(CASE WHEN stock > 0 AND stock < 30 THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_stock
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
    $sql_top = "SELECT sp.name, SUM(ct.quantity) as total_sold
                FROM chi_tiet_phieu_xuat ct
                JOIN sanpham sp ON ct.product_id = sp.id
                GROUP BY sp.id, sp.name
                ORDER BY total_sold DESC
                LIMIT 5";
    if ($res = $conn->query($sql_top)) {
        while ($row = $res->fetch_assoc()) {
            $data['chart_top_labels'][] = $row['name'];
            $data['chart_top_data'][]   = (int)$row['total_sold'];
        }
    }

    // ── Danh sách sắp hết hàng ──────────────────────────────────────────
    $sql_low = "SELECT sp.id, sp.name, sp.stock, sp.price, d.name AS category_name
                FROM sanpham sp JOIN danhmuc d ON sp.category_code = d.code
                WHERE sp.stock > 0 AND sp.stock < 30 AND sp.is_active = 1
                ORDER BY sp.stock ASC LIMIT 5";
    if ($res = $conn->query($sql_low)) {
        while ($row = $res->fetch_assoc()) {
            $data['low_stock_list'][] = $row;
        }
    }

    // ── Danh sách bán chạy nhất ────────────────────────────────────────
    $sql_top5 = "SELECT sp.id, sp.name, sp.price, d.name AS category_name, IFNULL(SUM(ct.quantity), 0) as total_sold
                 FROM sanpham sp
                 JOIN danhmuc d ON sp.category_code = d.code
                 LEFT JOIN chi_tiet_phieu_xuat ct ON sp.id = ct.product_id
                 WHERE sp.is_active = 1
                 GROUP BY sp.id, sp.name, sp.price, d.name
                 ORDER BY total_sold DESC
                 LIMIT 5";
    if ($res = $conn->query($sql_top5)) {
        while ($row = $res->fetch_assoc()) {
            $data['top_selling_list'][] = $row;
        }
    }

    // ── Phiếu nhập/xuất gần đây ────────────────────────────────────────
    $sql_recent = "(SELECT 'import' as type, pn.code, pn.created_at, u.full_name as created_by_name,
                    COUNT(ct.id) as item_count, IFNULL(SUM(ct.quantity), 0) as total_quantity
                    FROM phieu_nhap pn
                    JOIN users u ON pn.created_by = u.id
                    LEFT JOIN chi_tiet_phieu_nhap ct ON pn.code = ct.receipt_code
                    GROUP BY pn.code, pn.created_at, u.full_name)
                   UNION ALL
                   (SELECT 'export' as type, px.code, px.created_at, u.full_name as created_by_name,
                    COUNT(ct.id) as item_count, IFNULL(SUM(ct.quantity), 0) as total_quantity
                    FROM phieu_xuat px
                    JOIN users u ON px.created_by = u.id
                    LEFT JOIN chi_tiet_phieu_xuat ct ON px.code = ct.receipt_code
                    GROUP BY px.code, px.created_at, u.full_name)
                   ORDER BY created_at DESC
                   LIMIT 6";
    if ($res = $conn->query($sql_recent)) {
        while ($row = $res->fetch_assoc()) {
            $data['recent_receipts'][] = $row;
        }
    }

    // ── Danh mục cho filter ─────────────────────────────────────────────
    if ($res = $conn->query("SELECT code, name FROM danhmuc ORDER BY name ASC")) {
        while ($row = $res->fetch_assoc()) {
            $data['categories_list'][] = $row;
        }
    }

    return $data;
}

function checkStoreManagerTarget(mysqli $conn, int $target_id): array
{
    if (($_SESSION['role'] ?? '') !== 'store_manager') {
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

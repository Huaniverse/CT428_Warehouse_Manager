<?php

function getStockTableConfig(string $type): array {
    return $type === 'import'
        ? [
            'receipt_table'   => 'phieu_nhap',
            'detail_table'    => 'chi_tiet_phieu_nhap',
            'alias'           => 'pn',
            'label'           => 'nhập',
            'prefix'          => 'PN_',
            'not_found_msg'   => 'Không tìm thấy phiếu nhập.',
        ]
        : [
            'receipt_table'   => 'phieu_xuat',
            'detail_table'    => 'chi_tiet_phieu_xuat',
            'alias'           => 'px',
            'label'           => 'xuất',
            'prefix'          => 'PX_',
            'not_found_msg'   => 'Không tìm thấy phiếu xuất.',
        ];
}

function getStockList(mysqli $conn, string $type): array {
    $cfg = getStockTableConfig($type);
    $a = $cfg['alias'];

    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 10;
    $offset = ($page - 1) * $limit;

    $filter_sp       = (int)($_GET['san_pham'] ?? 0);
    $filter_search   = trim($_GET['search'] ?? '');
    $filter_date_from = trim($_GET['date_from'] ?? '');
    $filter_date_to   = trim($_GET['date_to'] ?? '');
    $filter_category  = trim($_GET['category'] ?? '');
    $filter_user_id   = (int)($_GET['user_id'] ?? 0);
    $filter_price_min = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? (int)$_GET['price_min'] : -1;
    $filter_price_max = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (int)$_GET['price_max'] : -1;
    if ($filter_price_min >= 0 && $filter_price_max >= 0 && $filter_price_min > $filter_price_max) {
        [$filter_price_min, $filter_price_max] = [$filter_price_max, $filter_price_min];
    }

    $where = "1=1";
    $bind_types = "";
    $bind_values = [];

    $scope_user_id = staffViewScope();
    if ($scope_user_id !== null) {
        $where .= " AND {$a}.nguoi_tao = ?";
        $bind_types .= "i";
        $bind_values[] = $scope_user_id;
    }

    if ($filter_user_id > 0) {
        $where .= " AND {$a}.nguoi_tao = ?";
        $bind_types .= "i";
        $bind_values[] = $filter_user_id;
    }

    if ($filter_sp > 0) {
        $where .= " AND EXISTS (SELECT 1 FROM {$cfg['detail_table']} ct WHERE ct.ma_phieu = {$a}.ma_phieu AND ct.san_pham = ?)";
        $bind_types .= "i";
        $bind_values[] = $filter_sp;
    }

    if ($filter_search !== '') {
        $where .= " AND EXISTS (SELECT 1 FROM {$cfg['detail_table']} ct2 JOIN sanpham sp2 ON ct2.san_pham = sp2.MaSP WHERE ct2.ma_phieu = {$a}.ma_phieu AND sp2.TenSP LIKE ?)";
        $bind_types .= "s";
        $bind_values[] = "%{$filter_search}%";
    }

    if ($filter_date_from !== '') {
        $where .= " AND {$a}.ngay_tao >= ?";
        $bind_types .= "s";
        $bind_values[] = $filter_date_from . ' 00:00:00';
    }

    if ($filter_date_to !== '') {
        $where .= " AND {$a}.ngay_tao <= ?";
        $bind_types .= "s";
        $bind_values[] = $filter_date_to . ' 23:59:59';
    }

    if ($filter_category !== '') {
        $where .= " AND EXISTS (SELECT 1 FROM {$cfg['detail_table']} ct3 JOIN sanpham sp3 ON ct3.san_pham = sp3.MaSP WHERE ct3.ma_phieu = {$a}.ma_phieu AND sp3.DanhMuc = ?)";
        $bind_types .= "s";
        $bind_values[] = $filter_category;
    }

    if ($filter_price_min >= 0 || $filter_price_max >= 0) {
        $where .= " AND COALESCE((SELECT SUM(ct4.so_luong * sp4.Gia) FROM {$cfg['detail_table']} ct4 JOIN sanpham sp4 ON ct4.san_pham = sp4.MaSP WHERE ct4.ma_phieu = {$a}.ma_phieu), 0)";
        if ($filter_price_min >= 0 && $filter_price_max >= 0) {
            $where .= " BETWEEN ? AND ?";
            $bind_types .= "ii";
            $bind_values[] = $filter_price_min;
            $bind_values[] = $filter_price_max;
        } elseif ($filter_price_min >= 0) {
            $where .= " >= ?";
            $bind_types .= "i";
            $bind_values[] = $filter_price_min;
        } else {
            $where .= " <= ?";
            $bind_types .= "i";
            $bind_values[] = $filter_price_max;
        }
    }

    $count_sql = "SELECT COUNT(*) as total FROM {$cfg['receipt_table']} {$a} WHERE $where";
    $count_stmt = $conn->prepare($count_sql);
    if ($bind_types !== "") {
        $count_vals = $bind_values;
        $count_stmt->bind_param($bind_types, ...$count_vals);
    }
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();

    $sql = "SELECT {$a}.ma_phieu,
                   COUNT(ct.san_pham) as so_loai_hang,
                   SUM(ct.so_luong) as tong_so_luong,
                   SUM(ct.so_luong * sp.Gia) as tong_gia_tien,
                   {$a}.ngay_tao,
                   u.full_name AS nguoi_tao_name
            FROM {$cfg['receipt_table']} {$a}
            JOIN {$cfg['detail_table']} ct ON ct.ma_phieu = {$a}.ma_phieu
            JOIN sanpham sp ON ct.san_pham = sp.MaSP
            JOIN users u ON {$a}.nguoi_tao = u.id
            WHERE $where
            GROUP BY {$a}.ma_phieu, {$a}.ngay_tao, u.full_name
            ORDER BY {$a}.ngay_tao DESC
            LIMIT $limit OFFSET $offset";

    $stmt = $conn->prepare($sql);
    if ($bind_types !== "") {
        $data_vals = $bind_values;
        $stmt->bind_param($bind_types, ...$data_vals);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();

    return [
        'success'  => true,
        'records'  => $records,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $limit,
    ];
}

function getStockDetail(mysqli $conn, string $type, string $ma_phieu): array {
    $cfg = getStockTableConfig($type);
    $a = $cfg['alias'];

    $scope_user_id = staffViewScope();
    $where = "1=1";
    $bind_types = "";
    $bind_values = [];

    if ($scope_user_id !== null) {
        $where .= " AND {$a}.nguoi_tao = ?";
        $bind_types .= "i";
        $bind_values[] = $scope_user_id;
    }

    $sql = "SELECT ct.san_pham, sp.TenSP, sp.Gia,
                   ct.so_luong, ct.ghi_chu,
                   {$a}.ngay_tao, u.full_name AS nguoi_tao_name
            FROM {$cfg['receipt_table']} {$a}
            JOIN {$cfg['detail_table']} ct ON ct.ma_phieu = {$a}.ma_phieu
            JOIN sanpham sp ON ct.san_pham = sp.MaSP
            JOIN users u ON {$a}.nguoi_tao = u.id
            WHERE {$a}.ma_phieu = ?
            AND $where
            ORDER BY ct.id ASC";

    $bind_types = "s" . $bind_types;
    array_unshift($bind_values, $ma_phieu);

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($bind_types, ...$bind_values);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();

    if (empty($items)) {
        return ['success' => false, 'message' => $cfg['not_found_msg']];
    }

    return [
        'success'   => true,
        'items'     => $items,
        'ngay_tao'  => $items[0]['ngay_tao'],
        'nguoi_tao' => $items[0]['nguoi_tao_name'],
        'ma_phieu'  => $ma_phieu,
    ];
}

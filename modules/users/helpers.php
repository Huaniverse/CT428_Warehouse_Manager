<?php
// modules/users/helpers.php — Helper functions cho module Người dùng & Dashboard

/**
 * Lấy dữ liệu dashboard: KPI, biểu đồ, danh sách danh mục.
 * Trả về mảng chứa tất cả biến cần thiết cho tab Tổng quan.
 */
function getDashboardData(mysqli $conn): array
{
    $data = [
        'total_categories' => 0,
        'total_quantity'   => 0,
        'total_val'        => 0,
        'total_low'        => 0,
        'chart1_labels'    => [],
        'chart1_data'      => [],
        'chart2_labels'    => [],
        'chart2_data'      => [],
        'categories_list'  => [],
    ];

    if (!$conn) return $data;

    if ($res = $conn->query("SELECT COUNT(*) as total FROM danhmuc")) {
        $data['total_categories'] = $res->fetch_assoc()['total'];
    }
    if ($res = $conn->query("SELECT SUM(SoLuong) as total FROM sanpham")) {
        $data['total_quantity'] = $res->fetch_assoc()['total'] ?? 0;
    }
    if ($res = $conn->query("SELECT SUM(SoLuong * Gia) as total FROM sanpham")) {
        $data['total_val'] = $res->fetch_assoc()['total'] ?? 0;
    }
    if ($res = $conn->query("SELECT COUNT(*) as total FROM sanpham WHERE SoLuong < 30")) {
        $data['total_low'] = $res->fetch_assoc()['total'];
    }

    $sql_chart1 = "SELECT d.TenDM, SUM(s.SoLuong) as TongSoLuong
                  FROM sanpham s JOIN danhmuc d ON s.DanhMuc = d.MaDM
                  GROUP BY d.MaDM, d.TenDM";
    if ($res = $conn->query($sql_chart1)) {
        while ($row = $res->fetch_assoc()) {
            $data['chart1_labels'][] = $row['TenDM'];
            $data['chart1_data'][]   = (int)$row['TongSoLuong'];
        }
    }

    $sql_chart2 = "SELECT d.TenDM, SUM(s.SoLuong * s.Gia) as TongGiaTri
                  FROM sanpham s JOIN danhmuc d ON s.DanhMuc = d.MaDM
                  GROUP BY d.MaDM, d.TenDM";
    if ($res = $conn->query($sql_chart2)) {
        while ($row = $res->fetch_assoc()) {
            $data['chart2_labels'][] = $row['TenDM'];
            $data['chart2_data'][]   = (float)$row['TongGiaTri'];
        }
    }

    if ($res = $conn->query("SELECT MaDM, TenDM FROM danhmuc ORDER BY TenDM ASC")) {
        while ($row = $res->fetch_assoc()) {
            $data['categories_list'][] = $row;
        }
    }

    return $data;
}

/**
 * Kiểm tra store_manager có được phép thao tác trên target user không.
 * Trả về ['allowed' => true] nếu OK, hoặc ['allowed' => false, 'message' => ...] nếu từ chối.
 */
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

/**
 * Kiểm tra thời điểm hiện tại có nằm trong khoảng truy cập cho phép không.
 * Admin luôn được phép truy cập (không áp dụng lịch).
 * @return array ['allowed' => bool, 'message' => string]
 */
function checkAccessSchedule(array $user): array
{
    if (($user['role'] ?? '') === 'admin') {
        return ['allowed' => true, 'message' => ''];
    }
    if (empty($user['has_schedule'])) {
        return ['allowed' => true, 'message' => ''];
    }
    $now   = (new DateTime())->format('H:i:s');
    $start = $user['access_start'] ?? null;
    $end   = $user['access_end']   ?? null;
    if (!$start || !$end) {
        return ['allowed' => false, 'message' => 'Tài khoản chưa được cấu hình giờ truy cập.'];
    }
    $inRange = false;
    if ($start <= $end) {
        $inRange = ($now >= $start && $now <= $end);
    } else {
        $inRange = ($now >= $start || $now <= $end);
    }
    if (!$inRange) {
        $tempUntil = $user['temp_access_until'] ?? null;
        if ($tempUntil && $tempUntil > date('Y-m-d H:i:s')) {
            return ['allowed' => true, 'message' => ''];
        }
        $label = substr($start, 0, 5) . ' – ' . substr($end, 0, 5);
        return [
            'allowed' => false,
            'message' => 'Tài khoản của bạn chỉ được phép truy cập trong khoảng ' . $label . '.',
        ];
    }
    return ['allowed' => true, 'message' => ''];
}

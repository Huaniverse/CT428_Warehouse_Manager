<?php
// shared/helpers.php — Chứa các hàm tiện ích dùng chung

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
 * Validate input dùng chung cho add_product và edit_product.
 *
 * @param mysqli $conn    Kết nối CSDL (để check danh mục)
 * @param array  $post    Mảng dữ liệu POST ($_POST)
 * @param bool   $isEdit  Cờ xác định có phải đang edit hay không (không bắt buộc số lượng)
 * @return array ['success' => bool, 'message' => string, 'data' => array]
 */
function validateProductInput(mysqli $conn, array $post, bool $isEdit = false): array
{
    $ten_sp   = trim($post['ten_sp'] ?? '');
    $danhmuc  = trim($post['danhmuc'] ?? '');
    $mota     = trim($post['mota'] ?? '');
    $gia      = isset($post['gia']) ? (float)$post['gia'] : 0.0;

    // Nếu là thêm mới thì kiểm tra số lượng, edit thì không gửi số lượng lên
    $so_luong = isset($post['so_luong']) ? (int)$post['so_luong'] : 0;

    if ($ten_sp === '') {
        return ['success' => false, 'message' => 'Tên sản phẩm không được để trống.'];
    }

    if ($danhmuc === '') {
        return ['success' => false, 'message' => 'Vui lòng chọn danh mục sản phẩm.'];
    }

    if ($gia < 0) {
        return ['success' => false, 'message' => 'Giá bán phải lớn hơn hoặc bằng 0.'];
    }

    if (!$isEdit && $so_luong < 0) {
        return ['success' => false, 'message' => 'Số lượng tồn kho phải lớn hơn hoặc bằng 0.'];
    }

    // Kiểm tra danh mục tồn tại trong CSDL
    $check_dm = $conn->prepare("SELECT MaDM FROM danhmuc WHERE MaDM = ?");
    $check_dm->bind_param("s", $danhmuc);
    $check_dm->execute();
    $res_dm = $check_dm->get_result();
    $dm_exists = $res_dm->num_rows > 0;
    $check_dm->close();

    if (!$dm_exists) {
        return ['success' => false, 'message' => 'Danh mục sản phẩm không tồn tại.'];
    }

    $data = [
        'ten_sp'  => $ten_sp,
        'danhmuc' => $danhmuc,
        'mota'    => $mota,
        'gia'     => $gia,
    ];
    if (!$isEdit) {
        $data['so_luong'] = $so_luong;
    }

    return [
        'success' => true,
        'data'    => $data
    ];
}

<?php
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
    if (mb_strlen($ten_sp) > 200) {
        return ['success' => false, 'message' => 'Tên sản phẩm không được quá 200 ký tự.'];
    }
    if (mb_strlen($mota) > 1000) {
        return ['success' => false, 'message' => 'Mô tả sản phẩm không được quá 1000 ký tự.'];
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

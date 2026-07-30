<?php
// Hàm kiểm tra dữ liệu sản phẩm

// Kiểm tra tính hợp lệ của dữ liệu nhập từ form
function validateProductInput(mysqli $conn, array $post, bool $isEdit = false): array
{
    $name          = trim($post['name'] ?? '');
    $category_code = trim($post['category_code'] ?? '');
    $description   = trim($post['description'] ?? '');
    $price         = isset($post['price']) ? (float)$post['price'] : 0.0;

    $quantity = isset($post['quantity']) ? (int)$post['quantity'] : 0;

    // Nếu là thêm mới thì kiểm tra số lượng, edit thì không gửi số lượng lên
    if ($name === '') {
        return ['success' => false, 'message' => 'Tên sản phẩm không được để trống.'];
    }
    if (mb_strlen($name) > 200) {
        return ['success' => false, 'message' => 'Tên sản phẩm không được quá 200 ký tự.'];
    }
    if (mb_strlen($description) > 1000) {
        return ['success' => false, 'message' => 'Mô tả sản phẩm không được quá 1000 ký tự.'];
    }

    if ($category_code === '') {
        return ['success' => false, 'message' => 'Vui lòng chọn danh mục sản phẩm.'];
    }

    if ($price < 0) {
        return ['success' => false, 'message' => 'Giá bán phải lớn hơn hoặc bằng 0.'];
    }

    if (!$isEdit && $quantity < 0) {
        return ['success' => false, 'message' => 'Số lượng tồn kho phải lớn hơn hoặc bằng 0.'];
    }

    // Kiểm tra danh mục tồn tại trong CSDL
    $check_dm = $conn->prepare("SELECT code FROM danhmuc WHERE code = ?");
    $check_dm->bind_param("s", $category_code);
    $check_dm->execute();
    $res_dm = $check_dm->get_result();
    $dm_exists = $res_dm->num_rows > 0;
    $check_dm->close();

    if (!$dm_exists) {
        return ['success' => false, 'message' => 'Danh mục sản phẩm không tồn tại.'];
    }

    $data = [
        'name'          => $name,
        'category_code' => $category_code,
        'description'   => $description,
        'price'         => $price,
    ];
    if (!$isEdit) {
        $data['quantity'] = $quantity;
    }

    return [
        'success' => true,
        'data'    => $data
    ];
}

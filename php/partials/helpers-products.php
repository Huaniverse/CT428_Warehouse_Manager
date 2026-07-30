<?php
// Hàm kiểm tra dữ liệu sản phẩm

// Kiểm tra tính hợp lệ của dữ liệu nhập từ form
function validateProductInput(mysqli $conn, array $post, bool $isEdit = false): array
{
    $name        = trim($post['name'] ?? '');
    $category_id = trim($post['category_id'] ?? '');
    $description = trim($post['description'] ?? '');
    $price       = isset($post['price']) ? (float)$post['price'] : 0.0;

    // Nếu là thêm mới thì kiểm tra số lượng, edit thì không gửi số lượng lên
    $stock_quantity = isset($post['stock_quantity']) ? (int)$post['stock_quantity'] : 0;

    if ($name === '') {
        return ['success' => false, 'message' => 'Tên sản phẩm không được để trống.'];
    }
    if (mb_strlen($name) > 200) {
        return ['success' => false, 'message' => 'Tên sản phẩm không được quá 200 ký tự.'];
    }
    if (mb_strlen($description) > 1000) {
        return ['success' => false, 'message' => 'Mô tả sản phẩm không được quá 1000 ký tự.'];
    }

    if ($category_id === '') {
        return ['success' => false, 'message' => 'Vui lòng chọn danh mục sản phẩm.'];
    }

    if ($price < 0) {
        return ['success' => false, 'message' => 'Giá bán phải lớn hơn hoặc bằng 0.'];
    }

    if (!$isEdit && $stock_quantity < 0) {
        return ['success' => false, 'message' => 'Số lượng tồn kho phải lớn hơn hoặc bằng 0.'];
    }

    // Kiểm tra danh mục có tồn tại trong CSDL
    $check_cat = $conn->prepare("SELECT id FROM categories WHERE id = ?");
    $check_cat->bind_param("s", $category_id);
    $check_cat->execute();
    $res_cat = $check_cat->get_result();
    $cat_exists = $res_cat->num_rows > 0;
    $check_cat->close();

    if (!$cat_exists) {
        return ['success' => false, 'message' => 'Danh mục sản phẩm không tồn tại.'];
    }

    $data = [
        'name'        => $name,
        'category_id' => $category_id,
        'description' => $description,
        'price'       => $price,
    ];
    if (!$isEdit) {
        $data['stock_quantity'] = $stock_quantity;
    }

    return [
        'success' => true,
        'data'    => $data
    ];
}

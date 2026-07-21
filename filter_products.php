<?php
// Session guard — require đăng nhập mới được lấy dữ liệu sản phẩm
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (!$conn) {
    echo '<tr><td colspan="8" style="text-align: center; padding: 32px; color: #ef4444;">Không thể kết nối đến cơ sở dữ liệu.</td></tr>';
    exit;
}

$search      = isset($_GET['search']) ? trim($_GET['search']) : '';
$category    = isset($_GET['category']) ? trim($_GET['category']) : '';
$price_sort  = isset($_GET['price_sort']) ? trim($_GET['price_sort']) : '';
$qty_sort    = isset($_GET['qty_sort']) ? trim($_GET['qty_sort']) : '';
$page        = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit_param = isset($_GET['limit']) ? $_GET['limit'] : '10';
$is_admin    = isAdmin();

$where = "1=1";
$params = [];
$types = "";

if ($search !== '') {
    $where .= " AND (s.TenSP LIKE ? OR s.MoTa LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($category !== '') {
    $where .= " AND s.DanhMuc = ?";
    $params[] = $category;
    $types .= "s";
}

// Lấy tổng số lượng để phân trang
$sql_count = "SELECT COUNT(*) as total FROM sanpham s JOIN danhmuc d ON s.DanhMuc = d.MaDM WHERE $where";
$stmt_count = $conn->prepare($sql_count);
if ($stmt_count) {
    if ($types !== "") {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $res_count = $stmt_count->get_result();
    $total_records = $res_count->fetch_assoc()['total'];
    $stmt_count->close();
} else {
    $total_records = 0;
}

$limit = ($limit_param === 'all') ? $total_records : (int)$limit_param;
if ($limit <= 0) $limit = 10;
$total_pages = $limit > 0 ? ceil($total_records / $limit) : 1;
if ($page > $total_pages) $page = max(1, $total_pages);
$offset = ($page - 1) * $limit;

header("X-Total-Records: $total_records");
header("X-Total-Pages: $total_pages");
header("X-Current-Page: $page");
header("X-Per-Page: $limit");

$sql = "SELECT s.MaSP, s.TenSP, s.MoTa, s.Gia, s.SoLuong, s.is_active, d.TenDM 
        FROM sanpham s 
        JOIN danhmuc d ON s.DanhMuc = d.MaDM 
        WHERE $where";
$order_by_clauses = [];

if ($price_sort === 'asc') {
    $order_by_clauses[] = "s.Gia ASC";
} elseif ($price_sort === 'desc') {
    $order_by_clauses[] = "s.Gia DESC";
}

if ($qty_sort === 'asc') {
    $order_by_clauses[] = "s.SoLuong ASC";
} elseif ($qty_sort === 'desc') {
    $order_by_clauses[] = "s.SoLuong DESC";
}

if (count($order_by_clauses) > 0) {
    $sql .= " ORDER BY " . implode(", ", $order_by_clauses);
} else {
    $sql .= " ORDER BY s.MaSP ASC";
}

if ($limit_param !== 'all') {
    $sql .= " LIMIT $limit OFFSET $offset";
}

$colspan = $is_admin ? 8 : 7;

$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ma_sp     = (int)$row['MaSP'];
            $ten_sp    = htmlspecialchars($row['TenSP']);
            $mo_ta     = htmlspecialchars($row['MoTa'], ENT_QUOTES, 'UTF-8'); // [SEC-02] ENT_QUOTES ngăn XSS qua attribute title

            $gia       = number_format($row['Gia'], 0, ',', '.') . ' đ';
            $so_luong  = (int)$row['SoLuong'];
            $ten_dm    = htmlspecialchars($row['TenDM']);
            $is_active = (int)$row['is_active'];

            if ($is_active == 0) {
                $status_class = 'hidden_product';
                $status_text  = 'Ngừng kinh doanh';
            } elseif ($so_luong <= 0) {
                $status_class = 'out_of_stock';
                $status_text  = 'Hết hàng';
            } elseif ($so_luong < 30) {
                $status_class = 'low_stock';
                $status_text  = 'Sắp hết';
            } else {
                $status_class = 'in_stock';
                $status_text  = 'Còn hàng';
            }

            $row_class = $is_active == 0 ? ' class="row_hidden"' : '';
            
            echo '<tr' . $row_class . '>';
            echo '<td>' . $ma_sp . '</td>';
            echo '<td><span class="product_name">' . $ten_sp . '</span></td>';
            echo '<td>' . $ten_dm . '</td>';
            echo '<td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' . $mo_ta . '">' . $mo_ta . '</td>';
            echo '<td><span class="product_price">' . $gia . '</span></td>';
            echo '<td>' . number_format($so_luong) . '</td>';
            echo '<td><span class="badge ' . $status_class . '">' . $status_text . '</span></td>';

            if ($is_admin) {
                echo '<td class="action_cell">';
                echo '<div class="action_group">';
                // Nút Sửa (chỉ khi active)
                if ($is_active == 1) {
                    echo '<button class="btn_icon" title="Sửa sản phẩm" onclick="openEditProduct(' . $ma_sp . ')">';
                    echo '<span class="material-symbols-outlined">edit</span>';
                    echo '</button>';
                }
                // Nút Ẩn / Khôi phục
                if ($is_active == 1) {
                    echo '<button class="btn_icon" title="Ẩn sản phẩm" onclick="toggleProductActive(' . $ma_sp . ', 0)">';
                    echo '<span class="material-symbols-outlined">visibility_off</span>';
                    echo '</button>';
                } else {
                    echo '<button class="btn_icon" title="Khôi phục sản phẩm" onclick="toggleProductActive(' . $ma_sp . ', 1)">';
                    echo '<span class="material-symbols-outlined">restore</span>';
                    echo '</button>';
                }
                echo '</div>';
                echo '</td>';
            }

            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="' . $colspan . '" class="no_results">Không tìm thấy sản phẩm nào phù hợp với bộ lọc.</td></tr>';
    }
    $stmt->close();
} else {
    echo '<tr><td colspan="' . $colspan . '" style="text-align: center; padding: 32px; color: #ef4444;">Lỗi truy vấn cơ sở dữ liệu.</td></tr>';
    $conn->close(); // [FIX-08] Đóng kết nối khi prepare() thất bại
}

$conn->close();
?>

<?php
// Session guard — require đăng nhập mới được lấy dữ liệu sản phẩm
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (!$conn) {
    echo '<tr><td colspan="7" style="text-align: center; padding: 32px; color: #ef4444;">Không thể kết nối đến cơ sở dữ liệu.</td></tr>';
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$price_sort = isset($_GET['price_sort']) ? trim($_GET['price_sort']) : '';
$qty_sort = isset($_GET['qty_sort']) ? trim($_GET['qty_sort']) : '';

$sql = "SELECT s.MaSP, s.TenSP, s.MoTa, s.Gia, s.SoLuong, d.TenDM 
        FROM sanpham s 
        JOIN danhmuc d ON s.DanhMuc = d.MaDM 
        WHERE 1=1";

$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (s.TenSP LIKE ? OR s.MoTa LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($category !== '') {
    $sql .= " AND s.DanhMuc = ?";
    $params[] = $category;
    $types .= "s";
}

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

$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ma_sp = htmlspecialchars($row['MaSP']);
            $ten_sp = htmlspecialchars($row['TenSP']);
            $mo_ta = htmlspecialchars($row['MoTa']);
            $gia = number_format($row['Gia'], 0, ',', '.') . ' đ';
            $so_luong = (int)$row['SoLuong'];
            $ten_dm = htmlspecialchars($row['TenDM']);
            
            if ($so_luong <= 0) {
                $status_class = 'out_of_stock';
                $status_text = 'Hết hàng';
            } elseif ($so_luong < 30) {
                $status_class = 'low_stock';
                $status_text = 'Sắp hết';
            } else {
                $status_class = 'in_stock';
                $status_text = 'Còn hàng';
            }
            
            echo '<tr>';
            echo '<td>' . $ma_sp . '</td>';
            echo '<td><span class="product_name">' . $ten_sp . '</span></td>';
            echo '<td>' . $ten_dm . '</td>';
            echo '<td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' . $mo_ta . '">' . $mo_ta . '</td>';
            echo '<td><span class="product_price">' . $gia . '</span></td>';
            echo '<td>' . number_format($so_luong) . '</td>';
            echo '<td><span class="badge ' . $status_class . '">' . $status_text . '</span></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="7" class="no_results">Không tìm thấy sản phẩm nào phù hợp với bộ lọc.</td></tr>';
    }
    $stmt->close();
} else {
    echo '<tr><td colspan="7" style="text-align: center; padding: 32px; color: #ef4444;">Lỗi truy vấn cơ sở dữ liệu.</td></tr>';
}

$conn->close();
?>

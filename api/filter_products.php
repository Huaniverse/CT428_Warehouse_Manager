<?php
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Không thể kết nối đến cơ sở dữ liệu.']);
    exit;
}

$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
$category     = isset($_GET['category']) ? trim($_GET['category']) : '';
$price_sort   = isset($_GET['price_sort']) ? trim($_GET['price_sort']) : '';
$qty_sort     = isset($_GET['qty_sort']) ? trim($_GET['qty_sort']) : '';
$page         = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit_param  = isset($_GET['limit']) ? $_GET['limit'] : '10';
$active_only  = isset($_GET['active_only']) ? (int)$_GET['active_only'] : 0;

$can_manage_products = isAdmin() || isStoreManager();
$can_view_products = $can_manage_products || canImportExport();

$where = "1=1";
$params = [];
$types = "";

if ($search !== '') {
    $where .= " AND (s.name LIKE ? OR s.description LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($category !== '') {
    $where .= " AND s.category_code = ?";
    $params[] = $category;
    $types .= "s";
}

if ($active_only === 1) {
    $where .= " AND s.is_active = 1";
}

$sql_count = "SELECT COUNT(*) as total FROM sanpham s JOIN danhmuc d ON s.category_code = d.code WHERE $where";
$stmt_count = $conn->prepare($sql_count);
if ($stmt_count) {
    if ($types !== "") {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
    $stmt_count->close();
} else {
    $total_records = 0;
}

$limit = ($limit_param === 'all') ? $total_records : (int)$limit_param;
if ($limit <= 0) $limit = 10;
$total_pages = $limit > 0 ? ceil($total_records / $limit) : 1;
if ($page > $total_pages) $page = max(1, $total_pages);
$offset = ($page - 1) * $limit;

$sql = "SELECT s.id, s.name, s.description, s.price, s.stock, s.is_active, d.name AS category_name 
        FROM sanpham s 
        JOIN danhmuc d ON s.category_code = d.code 
        WHERE $where";
$order_by_clauses = [];

if ($price_sort === 'asc') {
    $order_by_clauses[] = "s.price ASC";
} elseif ($price_sort === 'desc') {
    $order_by_clauses[] = "s.price DESC";
}

if ($qty_sort === 'asc') {
    $order_by_clauses[] = "s.stock ASC";
} elseif ($qty_sort === 'desc') {
    $order_by_clauses[] = "s.stock DESC";
}

if (count($order_by_clauses) > 0) {
    $sql .= " ORDER BY " . implode(", ", $order_by_clauses);
} else {
    $sql .= " ORDER BY s.id ASC";
}

if ($limit_param !== 'all') {
    $sql .= " LIMIT $limit OFFSET $offset";
}

$stmt = $conn->prepare($sql);
$records = [];
if ($stmt) {
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $records[] = [
                'id'            => (int)$row['id'],
                'name'          => $row['name'],
                'description'   => $row['description'] ?? '',
                'price'         => (int)$row['price'],
                'stock'         => (int)$row['stock'],
                'category_name' => $row['category_name'],
                'is_active'     => (int)$row['is_active'],
            ];
        }
    }
    $stmt->close();
}

echo json_encode([
    'success'            => true,
    'records'            => $records,
    'total'              => $total_records,
    'page'               => $page,
    'per_page'           => $limit,
    'is_admin'           => isAdmin(),
    'can_manage_products'=> $can_manage_products,
    'can_view_products'  => $can_view_products,
]);

$conn->close();

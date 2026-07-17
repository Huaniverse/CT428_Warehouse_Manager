<?php
// admin/export_stock.php — API xuất kho (Admin + Store Manager + Staff có quyền)

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra quyền nhập/xuất kho
$role = $_SESSION['role'] ?? '';
$allow_import_export = ($_SESSION['allow_import_export'] ?? 0) == 1;
if ($role !== 'admin' && $role !== 'store_manager' && !($role === 'staff' && $allow_import_export)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

switch ($action) {

    // ── Tạo phiếu xuất ────────────────────────────────────────────────────
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
            exit;
        }

        $san_pham = (int)($_POST['san_pham'] ?? 0);
        $so_luong = (int)($_POST['so_luong'] ?? 0);
        $ghi_chu  = trim($_POST['ghi_chu'] ?? '');
        $nguoi_tao = $_SESSION['user_id'];

        if ($san_pham <= 0) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn sản phẩm.']);
            exit;
        }
        if ($so_luong <= 0) {
            echo json_encode(['success' => false, 'message' => 'Số lượng xuất phải lớn hơn 0.']);
            exit;
        }

        // Kiểm tra sản phẩm tồn tại và đang active
        $check = $conn->prepare("SELECT MaSP, TenSP, SoLuong FROM sanpham WHERE MaSP = ? AND is_active = 1");
        $check->bind_param("i", $san_pham);
        $check->execute();
        $product = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại hoặc đã bị ẩn.']);
            exit;
        }

        // Kiểm tra tồn kho
        if ($product['SoLuong'] < $so_luong) {
            echo json_encode([
                'success' => false,
                'message' => "Không đủ hàng để xuất. Hiện còn {$product['SoLuong']} sản phẩm."
            ]);
            exit;
        }

        // Transaction: insert phiếu + cập nhật tồn kho
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                "INSERT INTO phieu_xuat (san_pham, so_luong, ghi_chu, nguoi_tao, ngay_tao) VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param("iisi", $san_pham, $so_luong, $ghi_chu, $nguoi_tao);
            $stmt->execute();
            $ma_phieu = $conn->insert_id;
            $stmt->close();

            $new_qty = $product['SoLuong'] - $so_luong;
            $update = $conn->prepare("UPDATE sanpham SET SoLuong = ? WHERE MaSP = ?");
            $update->bind_param("ii", $new_qty, $san_pham);
            $update->execute();
            $update->close();

            $conn->commit();
            echo json_encode([
                'success'       => true,
                'message'       => "Xuất kho thành công. Sản phẩm \"{$product['TenSP']}\": -{$so_luong} → {$new_qty} sản phẩm.",
                'ma_phieu'      => $ma_phieu,
                'so_luong_moi'  => $new_qty
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Lỗi xuất kho: ' . $e->getMessage()]);
        }
        break;

    // ── Danh sách phiếu xuất ──────────────────────────────────────────────
    case 'list':
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        $where = "1=1";
        $params = [];
        $types  = "";

        $filter_sp = (int)($_GET['san_pham'] ?? 0);
        if ($filter_sp > 0) {
            $where .= " AND px.san_pham = ?";
            $params[] = $filter_sp;
            $types .= "i";
        }

        // Đếm tổng
        $count_sql = "SELECT COUNT(*) as total FROM phieu_xuat px WHERE $where";
        $count_stmt = $conn->prepare($count_sql);
        if ($types !== "") $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $total = $count_stmt->get_result()->fetch_assoc()['total'];
        $count_stmt->close();

        // Lấy dữ liệu
        $sql = "SELECT px.ma_phieu, px.so_luong, px.ghi_chu, px.ngay_tao,
                       sp.MaSP, sp.TenSP,
                       u.full_name AS nguoi_tao_name
                FROM phieu_xuat px
                JOIN sanpham sp ON px.san_pham = sp.MaSP
                JOIN users u ON px.nguoi_tao = u.id
                WHERE $where
                ORDER BY px.ngay_tao DESC
                LIMIT $limit OFFSET $offset";

        $stmt = $conn->prepare($sql);
        if ($types !== "") $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'success'   => true,
            'records'   => $records,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $limit
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}

$conn->close();

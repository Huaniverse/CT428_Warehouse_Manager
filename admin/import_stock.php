<?php
// admin/import_stock.php — API nhập kho (Admin + Store Manager + Staff có quyền)

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

    // ── Tạo phiếu nhập ────────────────────────────────────────────────────
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
            exit;
        }
        // [SEC-01] Xác minh CSRF token
        verifyCsrfToken();

        $san_pham = (int)($_POST['san_pham'] ?? 0);
        $so_luong = (int)($_POST['so_luong'] ?? 0);
        $ghi_chu  = trim($_POST['ghi_chu'] ?? '');
        $nguoi_tao = $_SESSION['user_id'];

        if ($san_pham <= 0) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn sản phẩm.']);
            exit;
        }
        if ($so_luong <= 0) {
            echo json_encode(['success' => false, 'message' => 'Số lượng nhập phải lớn hơn 0.']);
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

        // Transaction: insert phiếu + cập nhật tồn kho (atomic để tránh race condition)
        $conn->begin_transaction();
        try {
            // [FIX-RC] Khóa dòng sản phẩm trước khi insert để tránh race condition
            $lock = $conn->prepare("SELECT MaSP, TenSP FROM sanpham WHERE MaSP = ? AND is_active = 1 FOR UPDATE");
            $lock->bind_param("i", $san_pham);
            $lock->execute();
            $locked_product = $lock->get_result()->fetch_assoc();
            $lock->close();

            if (!$locked_product) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại hoặc đã bị ẩn.']);
                exit;
            }

            $ma_phieu_gop = 'PN_' . date('YmdHis') . '_' . rand(1000, 9999);
            $stmt = $conn->prepare(
                "INSERT INTO phieu_nhap (san_pham, so_luong, ghi_chu, nguoi_tao, ngay_tao, ma_phieu_gop) VALUES (?, ?, ?, ?, NOW(), ?)"
            );
            $stmt->bind_param("iisis", $san_pham, $so_luong, $ghi_chu, $nguoi_tao, $ma_phieu_gop);
            $stmt->execute();
            $ma_phieu = $conn->insert_id;
            $stmt->close();

            // [FIX-RC] Atomic UPDATE: cộng trực tiếp tại DB, không đọc-sửa-ghi
            $update = $conn->prepare("UPDATE sanpham SET SoLuong = SoLuong + ? WHERE MaSP = ?");
            $update->bind_param("ii", $so_luong, $san_pham);
            $update->execute();
            $update->close();

            // Lấy số lượng mới để hiển thị trong thông báo
            $fetch = $conn->prepare("SELECT SoLuong FROM sanpham WHERE MaSP = ?");
            $fetch->bind_param("i", $san_pham);
            $fetch->execute();
            $new_qty = (int)$fetch->get_result()->fetch_assoc()['SoLuong'];
            $fetch->close();

            $conn->commit();
            echo json_encode([
                'success'       => true,
                'message'       => "Nhập kho thành công. Sản phẩm \"{$locked_product['TenSP']}\": +{$so_luong} → {$new_qty} sản phẩm.",
                'ma_phieu'      => $ma_phieu,
                'so_luong_moi'  => $new_qty
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Lỗi nhập kho: ' . $e->getMessage()]);
        }
        break;


    // ── Tạo phiếu nhập hàng loạt ─────────────────────────────────────────
    case 'create_batch':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
            exit;
        }
        verifyCsrfToken();

        $items_json = $_POST['items'] ?? '[]';
        $items = json_decode($items_json, true);

        if (!is_array($items) || count($items) === 0) {
            echo json_encode(['success' => false, 'message' => 'Không có sản phẩm nào trong phiếu.']);
            exit;
        }

        if (count($items) > 50) {
            echo json_encode(['success' => false, 'message' => 'Tối đa 50 sản phẩm mỗi phiếu.']);
            exit;
        }

        $nguoi_tao = $_SESSION['user_id'];
        $conn->begin_transaction();
        try {
            $success_count = 0;
            $messages = [];
            $ma_phieu_gop = 'PN_' . date('YmdHis') . '_' . rand(1000, 9999);

            foreach ($items as $item) {
                $san_pham = (int)($item['san_pham'] ?? 0);
                $so_luong = (int)($item['so_luong'] ?? 0);
                $ghi_chu  = trim($item['ghi_chu'] ?? '');

                if ($san_pham <= 0 || $so_luong <= 0) continue;

                // Khóa dòng sản phẩm
                $lock = $conn->prepare("SELECT MaSP, TenSP FROM sanpham WHERE MaSP = ? AND is_active = 1 FOR UPDATE");
                $lock->bind_param("i", $san_pham);
                $lock->execute();
                $locked_product = $lock->get_result()->fetch_assoc();
                $lock->close();

                if (!$locked_product) continue;

                // Insert phiếu nhập
                $stmt = $conn->prepare(
                    "INSERT INTO phieu_nhap (san_pham, so_luong, ghi_chu, nguoi_tao, ngay_tao, ma_phieu_gop) VALUES (?, ?, ?, ?, NOW(), ?)"
                );
                $stmt->bind_param("iisis", $san_pham, $so_luong, $ghi_chu, $nguoi_tao, $ma_phieu_gop);
                $stmt->execute();
                $stmt->close();

                // Cập nhật tồn kho
                $update = $conn->prepare("UPDATE sanpham SET SoLuong = SoLuong + ? WHERE MaSP = ?");
                $update->bind_param("ii", $so_luong, $san_pham);
                $update->execute();
                $update->close();

                $success_count++;
                $messages[] = "{$locked_product['TenSP']}: +{$so_luong}";
            }

            if ($success_count === 0) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Không có sản phẩm hợp lệ nào được nhập.']);
                exit;
            }

            $conn->commit();
            $summary = implode(', ', $messages);
            echo json_encode([
                'success' => true,
                'message' => "Nhập kho thành công {$success_count} sản phẩm: {$summary}."
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Lỗi nhập kho: ' . $e->getMessage()]);
        }
        break;


    // ── Danh sách phiếu nhập ──────────────────────────────────────────────
    case 'list':
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        $role = $_SESSION['role'] ?? '';
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $filter_sp = (int)($_GET['san_pham'] ?? 0);

        // Xây dựng điều kiện WHERE
        $where = "1=1";
        $bind_types = "";
        $bind_values = [];

        if ($role !== 'admin' && $role !== 'store_manager') {
            $where .= " AND pn.nguoi_tao = ?";
            $bind_types .= "i";
            $bind_values[] = $user_id;
        }

        if ($filter_sp > 0) {
            $where .= " AND pn.san_pham = ?";
            $bind_types .= "i";
            $bind_values[] = $filter_sp;
        }

        // Đếm tổng
        $count_sql = "SELECT COUNT(DISTINCT pn.ma_phieu_gop) as total FROM phieu_nhap pn WHERE $where";
        $count_stmt = $conn->prepare($count_sql);
        if ($bind_types !== "") {
            $count_stmt->bind_param($bind_types, ...$bind_values);
        }
        $count_stmt->execute();
        $total = $count_stmt->get_result()->fetch_assoc()['total'];
        $count_stmt->close();

        // Lấy dữ liệu
        $sql = "SELECT pn.ma_phieu_gop as ma_phieu, 
                       SUM(pn.so_luong) as so_luong, 
                       GROUP_CONCAT(DISTINCT pn.ghi_chu SEPARATOR '; ') as ghi_chu, 
                       MAX(pn.ngay_tao) as ngay_tao,
                       GROUP_CONCAT(CONCAT(sp.TenSP, ' (', pn.so_luong, ')') SEPARATOR ', ') as TenSP,
                       MAX(u.full_name) AS nguoi_tao_name
                FROM phieu_nhap pn
                JOIN sanpham sp ON pn.san_pham = sp.MaSP
                JOIN users u ON pn.nguoi_tao = u.id
                WHERE $where
                GROUP BY pn.ma_phieu_gop
                ORDER BY MAX(pn.ngay_tao) DESC
                LIMIT $limit OFFSET $offset";

        $stmt = $conn->prepare($sql);
        if ($bind_types !== "") {
            $stmt->bind_param($bind_types, ...$bind_values);
        }
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

    // ── Chi tiết phiếu nhập ───────────────────────────────────────────────
    case 'detail':
        $ma_phieu_gop = trim($_GET['ma_phieu'] ?? '');

        if ($ma_phieu_gop === '') {
            echo json_encode(['success' => false, 'message' => 'Thiếu mã phiếu.']);
            exit;
        }

        $role = $_SESSION['role'] ?? '';
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $where = "1=1";
        $bind_types = "";
        $bind_values = [];

        if ($role !== 'admin' && $role !== 'store_manager') {
            $where .= " AND pn.nguoi_tao = ?";
            $bind_types .= "i";
            $bind_values[] = $user_id;
        }

        // Lấy tất cả dòng trong phiếu (batch hoặc đơn lẻ)
        $sql = "SELECT pn.ma_phieu, pn.san_pham, sp.TenSP, sp.Gia,
                       pn.so_luong, pn.ghi_chu, pn.ngay_tao,
                       u.full_name AS nguoi_tao_name
                FROM phieu_nhap pn
                JOIN sanpham sp ON pn.san_pham = sp.MaSP
                JOIN users u ON pn.nguoi_tao = u.id
                WHERE (pn.ma_phieu_gop = ? OR CAST(pn.ma_phieu AS CHAR) = ?)
                AND $where
                ORDER BY pn.ma_phieu ASC";

        $bind_types = "ss" . $bind_types;
        array_unshift($bind_values, $ma_phieu_gop, $ma_phieu_gop);

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
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu nhập.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'items'   => $items,
            'ngay_tao'    => $items[0]['ngay_tao'],
            'nguoi_tao'   => $items[0]['nguoi_tao_name'],
            'ma_phieu'    => $ma_phieu_gop
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}

$conn->close();

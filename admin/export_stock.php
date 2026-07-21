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

        // Transaction: insert phiếu + cập nhật tồn kho (atomic để tránh race condition)
        $conn->begin_transaction();
        try {
            // [FIX-RC] Khóa dòng sản phẩm và kiểm tra tồn kho ngay trong transaction
            $lock = $conn->prepare(
                "SELECT MaSP, TenSP, SoLuong FROM sanpham WHERE MaSP = ? AND is_active = 1 FOR UPDATE"
            );
            $lock->bind_param("i", $san_pham);
            $lock->execute();
            $locked_product = $lock->get_result()->fetch_assoc();
            $lock->close();

            if (!$locked_product) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại hoặc đã bị ẩn.']);
                exit;
            }

            // Kiểm tra tồn kho bên trong transaction (sau khi đã lock dòng)
            if ($locked_product['SoLuong'] < $so_luong) {
                $conn->rollback();
                echo json_encode([
                    'success' => false,
                    'message' => "Không đủ hàng để xuất. Hiện còn {$locked_product['SoLuong']} sản phẩm."
                ]);
                exit;
            }

            $stmt = $conn->prepare(
                "INSERT INTO phieu_xuat (san_pham, so_luong, ghi_chu, nguoi_tao, ngay_tao) VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param("iisi", $san_pham, $so_luong, $ghi_chu, $nguoi_tao);
            $stmt->execute();
            $ma_phieu = $conn->insert_id;
            $stmt->close();

            // [FIX-RC] Atomic UPDATE với guard SoLuong >= ? tránh số âm tại tầng DB
            $update = $conn->prepare(
                "UPDATE sanpham SET SoLuong = SoLuong - ? WHERE MaSP = ? AND SoLuong >= ?"
            );
            $update->bind_param("iii", $so_luong, $san_pham, $so_luong);
            $update->execute();

            if ($update->affected_rows === 0) {
                // Race condition: một request khác đã xuất hết hàng trước
                $update->close();
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Không đủ hàng để xuất (đã có thay đổi tồn kho đồng thời).']);
                exit;
            }
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
                'message'       => "Xuất kho thành công. Sản phẩm \"{$locked_product['TenSP']}\": -{$so_luong} → {$new_qty} sản phẩm.",
                'ma_phieu'      => $ma_phieu,
                'so_luong_moi'  => $new_qty
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Lỗi xuất kho: ' . $e->getMessage()]);
        }
        break;


    // ── Tạo phiếu xuất hàng loạt ─────────────────────────────────────────
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
            $ma_phieu_gop = 'PX_' . date('YmdHis') . '_' . rand(1000, 9999);

            foreach ($items as $item) {
                $san_pham = (int)($item['san_pham'] ?? 0);
                $so_luong = (int)($item['so_luong'] ?? 0);
                $ghi_chu  = trim($item['ghi_chu'] ?? '');

                if ($san_pham <= 0 || $so_luong <= 0) continue;

                // Khóa dòng sản phẩm
                $lock = $conn->prepare("SELECT MaSP, TenSP, SoLuong FROM sanpham WHERE MaSP = ? AND is_active = 1 FOR UPDATE");
                $lock->bind_param("i", $san_pham);
                $lock->execute();
                $locked_product = $lock->get_result()->fetch_assoc();
                $lock->close();

                if (!$locked_product) {
                    throw new Exception("Sản phẩm ID {$san_pham} không tồn tại.");
                }

                if ($locked_product['SoLuong'] < $so_luong) {
                    throw new Exception("Sản phẩm \"{$locked_product['TenSP']}\" không đủ số lượng (tồn kho: {$locked_product['SoLuong']}, yêu cầu: {$so_luong}).");
                }

                // Insert phiếu xuất
                $stmt = $conn->prepare(
                    "INSERT INTO phieu_xuat (san_pham, so_luong, ghi_chu, nguoi_tao, ngay_tao, ma_phieu_gop) VALUES (?, ?, ?, ?, NOW(), ?)"
                );
                $stmt->bind_param("iisis", $san_pham, $so_luong, $ghi_chu, $nguoi_tao, $ma_phieu_gop);
                $stmt->execute();
                $stmt->close();

                // Cập nhật tồn kho (có điều kiện WHERE bổ sung để an toàn thêm)
                $update = $conn->prepare("UPDATE sanpham SET SoLuong = SoLuong - ? WHERE MaSP = ? AND SoLuong >= ?");
                $update->bind_param("iii", $so_luong, $san_pham, $so_luong);
                $update->execute();
                
                if ($update->affected_rows === 0) {
                    $update->close();
                    throw new Exception("Lỗi đồng bộ khi trừ tồn kho cho sản phẩm \"{$locked_product['TenSP']}\".");
                }
                $update->close();

                $success_count++;
                $messages[] = "{$locked_product['TenSP']}: -{$so_luong}";
            }

            if ($success_count === 0) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Không có sản phẩm hợp lệ nào được xuất.']);
                exit;
            }

            $conn->commit();
            $summary = implode(', ', $messages);
            echo json_encode([
                'success' => true,
                'message' => "Xuất kho thành công {$success_count} sản phẩm: {$summary}."
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

        $role = $_SESSION['role'] ?? '';
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $filter_sp = (int)($_GET['san_pham'] ?? 0);

        // Xây dựng điều kiện WHERE
        $where = "1=1";
        $bind_types = "";
        $bind_values = [];

        if ($role !== 'admin') {
            $where .= " AND px.nguoi_tao = ?";
            $bind_types .= "i";
            $bind_values[] = $user_id;
        }

        if ($filter_sp > 0) {
            $where .= " AND px.san_pham = ?";
            $bind_types .= "i";
            $bind_values[] = $filter_sp;
        }

        // Đếm tổng
        $count_sql = "SELECT COUNT(DISTINCT px.ma_phieu_gop) as total FROM phieu_xuat px WHERE $where";
        $count_stmt = $conn->prepare($count_sql);
        if ($bind_types !== "") {
            $count_stmt->bind_param($bind_types, ...$bind_values);
        }
        $count_stmt->execute();
        $total = $count_stmt->get_result()->fetch_assoc()['total'];
        $count_stmt->close();

        // Lấy dữ liệu
        $sql = "SELECT px.ma_phieu_gop as ma_phieu, 
                       SUM(px.so_luong) as so_luong, 
                       GROUP_CONCAT(DISTINCT px.ghi_chu SEPARATOR '; ') as ghi_chu, 
                       MAX(px.ngay_tao) as ngay_tao,
                       GROUP_CONCAT(CONCAT(sp.TenSP, ' (', px.so_luong, ')') SEPARATOR ', ') as TenSP,
                       MAX(u.full_name) AS nguoi_tao_name
                FROM phieu_xuat px
                JOIN sanpham sp ON px.san_pham = sp.MaSP
                JOIN users u ON px.nguoi_tao = u.id
                WHERE $where
                GROUP BY px.ma_phieu_gop
                ORDER BY MAX(px.ngay_tao) DESC
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

    // ── Chi tiết phiếu xuất ───────────────────────────────────────────────
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

        if ($role !== 'admin') {
            $where .= " AND px.nguoi_tao = ?";
            $bind_types .= "i";
            $bind_values[] = $user_id;
        }

        $sql = "SELECT px.ma_phieu, px.san_pham, sp.TenSP, sp.Gia,
                       px.so_luong, px.ghi_chu, px.ngay_tao,
                       u.full_name AS nguoi_tao_name
                FROM phieu_xuat px
                JOIN sanpham sp ON px.san_pham = sp.MaSP
                JOIN users u ON px.nguoi_tao = u.id
                WHERE (px.ma_phieu_gop = ? OR CAST(px.ma_phieu AS CHAR) = ?)
                AND $where
                ORDER BY px.ma_phieu ASC";

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
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu xuất.']);
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

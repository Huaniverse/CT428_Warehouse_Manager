<?php

require_once __DIR__ . '/../../../shared/db.php';
require_once __DIR__ . '/../../../shared/auth.php';

header('Content-Type: application/json; charset=utf-8');

requireCanImportExport();

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

        $conn->begin_transaction();
        try {
            // Lock sản phẩm
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

            $ma_phieu = 'PN_' . date('YmdHis') . '_' . rand(1000, 9999);

            // Insert header
            $header = $conn->prepare("INSERT INTO phieu_nhap (ma_phieu, nguoi_tao, ngay_tao) VALUES (?, ?, NOW())");
            $header->bind_param("si", $ma_phieu, $nguoi_tao);
            $header->execute();
            $header->close();

            // Insert detail
            $detail = $conn->prepare("INSERT INTO chi_tiet_phieu_nhap (ma_phieu, san_pham, so_luong, ghi_chu) VALUES (?, ?, ?, ?)");
            $detail->bind_param("siis", $ma_phieu, $san_pham, $so_luong, $ghi_chu);
            $detail->execute();
            $detail->close();

            // Cập nhật tồn kho
            $update = $conn->prepare("UPDATE sanpham SET SoLuong = SoLuong + ? WHERE MaSP = ?");
            $update->bind_param("ii", $so_luong, $san_pham);
            $update->execute();
            $update->close();

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
            $ma_phieu = 'PN_' . date('YmdHis') . '_' . rand(1000, 9999);

            // Insert 1 header
            $header = $conn->prepare("INSERT INTO phieu_nhap (ma_phieu, nguoi_tao, ngay_tao) VALUES (?, ?, NOW())");
            $header->bind_param("si", $ma_phieu, $nguoi_tao);
            $header->execute();
            $header->close();

            $detail_stmt = $conn->prepare("INSERT INTO chi_tiet_phieu_nhap (ma_phieu, san_pham, so_luong, ghi_chu) VALUES (?, ?, ?, ?)");
            $lock = $conn->prepare("SELECT MaSP, TenSP FROM sanpham WHERE MaSP = ? AND is_active = 1 FOR UPDATE");
            $update = $conn->prepare("UPDATE sanpham SET SoLuong = SoLuong + ? WHERE MaSP = ?");

            $success_count = 0;
            $messages = [];

            foreach ($items as $item) {
                $san_pham = (int)($item['san_pham'] ?? 0);
                $so_luong = (int)($item['so_luong'] ?? 0);
                $ghi_chu  = trim($item['ghi_chu'] ?? '');

                if ($san_pham <= 0 || $so_luong <= 0) continue;

                $lock->bind_param("i", $san_pham);
                $lock->execute();
                $locked_product = $lock->get_result()->fetch_assoc();

                if (!$locked_product) continue;

                $detail_stmt->bind_param("siis", $ma_phieu, $san_pham, $so_luong, $ghi_chu);
                $detail_stmt->execute();

                $update->bind_param("ii", $so_luong, $san_pham);
                $update->execute();

                $success_count++;
                $messages[] = "{$locked_product['TenSP']}: +{$so_luong}";
            }

            $detail_stmt->close();
            $lock->close();
            $update->close();

            if ($success_count === 0) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Không có sản phẩm hợp lệ nào được nhập.']);
                exit;
            }

            $conn->commit();
            $summary = implode(', ', $messages);
            echo json_encode([
                'success' => true,
                'message' => "Nhập kho thành công {$success_count} sản phẩm: {$summary}.",
                'ma_phieu' => $ma_phieu
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

        $filter_sp       = (int)($_GET['san_pham'] ?? 0);
        $filter_search   = trim($_GET['search'] ?? '');
        $filter_date_from = trim($_GET['date_from'] ?? '');
        $filter_date_to   = trim($_GET['date_to'] ?? '');
        $filter_category  = trim($_GET['category'] ?? '');
        $filter_user_id   = (int)($_GET['user_id'] ?? 0);
        $filter_price_min = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? (int)$_GET['price_min'] : -1;
        $filter_price_max = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (int)$_GET['price_max'] : -1;
        if ($filter_price_min >= 0 && $filter_price_max >= 0 && $filter_price_min > $filter_price_max) {
            [$filter_price_min, $filter_price_max] = [$filter_price_max, $filter_price_min];
        }

        $where = "1=1";
        $bind_types = "";
        $bind_values = [];

        $scope_user_id = staffViewScope();
        if ($scope_user_id !== null) {
            $where .= " AND pn.nguoi_tao = ?";
            $bind_types .= "i";
            $bind_values[] = $scope_user_id;
        }

        if ($filter_user_id > 0) {
            $where .= " AND pn.nguoi_tao = ?";
            $bind_types .= "i";
            $bind_values[] = $filter_user_id;
        }

        if ($filter_sp > 0) {
            $where .= " AND EXISTS (SELECT 1 FROM chi_tiet_phieu_nhap ct WHERE ct.ma_phieu = pn.ma_phieu AND ct.san_pham = ?)";
            $bind_types .= "i";
            $bind_values[] = $filter_sp;
        }

        if ($filter_search !== '') {
            $where .= " AND EXISTS (SELECT 1 FROM chi_tiet_phieu_nhap ct2 JOIN sanpham sp2 ON ct2.san_pham = sp2.MaSP WHERE ct2.ma_phieu = pn.ma_phieu AND sp2.TenSP LIKE ?)";
            $bind_types .= "s";
            $bind_values[] = "%{$filter_search}%";
        }

        if ($filter_date_from !== '') {
            $where .= " AND pn.ngay_tao >= ?";
            $bind_types .= "s";
            $bind_values[] = $filter_date_from . ' 00:00:00';
        }

        if ($filter_date_to !== '') {
            $where .= " AND pn.ngay_tao <= ?";
            $bind_types .= "s";
            $bind_values[] = $filter_date_to . ' 23:59:59';
        }

        if ($filter_category !== '') {
            $where .= " AND EXISTS (SELECT 1 FROM chi_tiet_phieu_nhap ct3 JOIN sanpham sp3 ON ct3.san_pham = sp3.MaSP WHERE ct3.ma_phieu = pn.ma_phieu AND sp3.DanhMuc = ?)";
            $bind_types .= "s";
            $bind_values[] = $filter_category;
        }

        if ($filter_price_min >= 0 || $filter_price_max >= 0) {
            $where .= " AND COALESCE((SELECT SUM(ct4.so_luong * sp4.Gia) FROM chi_tiet_phieu_nhap ct4 JOIN sanpham sp4 ON ct4.san_pham = sp4.MaSP WHERE ct4.ma_phieu = pn.ma_phieu), 0)";
            if ($filter_price_min >= 0 && $filter_price_max >= 0) {
                $where .= " BETWEEN ? AND ?";
                $bind_types .= "ii";
                $bind_values[] = $filter_price_min;
                $bind_values[] = $filter_price_max;
            } elseif ($filter_price_min >= 0) {
                $where .= " >= ?";
                $bind_types .= "i";
                $bind_values[] = $filter_price_min;
            } else {
                $where .= " <= ?";
                $bind_types .= "i";
                $bind_values[] = $filter_price_max;
            }
        }

        error_log("[IMPORT_LIST] GET=" . json_encode($_GET) . " | price_min=$filter_price_min price_max=$filter_price_max | where=$where | bind_types=$bind_types | bind_values=" . json_encode($bind_values));

        $count_sql = "SELECT COUNT(*) as total FROM phieu_nhap pn WHERE $where";
        $count_stmt = $conn->prepare($count_sql);
        if ($bind_types !== "") {
            $count_vals = $bind_values;
            $count_stmt->bind_param($bind_types, ...$count_vals);
        }
        $count_stmt->execute();
        $total = $count_stmt->get_result()->fetch_assoc()['total'];
        $count_stmt->close();

        $sql = "SELECT pn.ma_phieu,
                       COUNT(ct.san_pham) as so_loai_hang,
                       SUM(ct.so_luong) as tong_so_luong,
                       SUM(ct.so_luong * sp.Gia) as tong_gia_tien,
                       pn.ngay_tao,
                       u.full_name AS nguoi_tao_name
                FROM phieu_nhap pn
                JOIN chi_tiet_phieu_nhap ct ON ct.ma_phieu = pn.ma_phieu
                JOIN sanpham sp ON ct.san_pham = sp.MaSP
                JOIN users u ON pn.nguoi_tao = u.id
                WHERE $where
                GROUP BY pn.ma_phieu, pn.ngay_tao, u.full_name
                ORDER BY pn.ngay_tao DESC
                LIMIT $limit OFFSET $offset";

        $stmt = $conn->prepare($sql);
        if ($bind_types !== "") {
            $data_vals = $bind_values;
            $stmt->bind_param($bind_types, ...$data_vals);
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
        $ma_phieu = trim($_GET['ma_phieu'] ?? '');

        if ($ma_phieu === '') {
            echo json_encode(['success' => false, 'message' => 'Thiếu mã phiếu.']);
            exit;
        }

        $scope_user_id = staffViewScope();
        $where = "1=1";
        $bind_types = "";
        $bind_values = [];

        if ($scope_user_id !== null) {
            $where .= " AND pn.nguoi_tao = ?";
            $bind_types .= "i";
            $bind_values[] = $scope_user_id;
        }

        $sql = "SELECT ct.san_pham, sp.TenSP, sp.Gia,
                       ct.so_luong, ct.ghi_chu,
                       pn.ngay_tao, u.full_name AS nguoi_tao_name
                FROM phieu_nhap pn
                JOIN chi_tiet_phieu_nhap ct ON ct.ma_phieu = pn.ma_phieu
                JOIN sanpham sp ON ct.san_pham = sp.MaSP
                JOIN users u ON pn.nguoi_tao = u.id
                WHERE pn.ma_phieu = ?
                AND $where
                ORDER BY ct.id ASC";

        $bind_types = "s" . $bind_types;
        array_unshift($bind_values, $ma_phieu);

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
            'success'   => true,
            'items'     => $items,
            'ngay_tao'  => $items[0]['ngay_tao'],
            'nguoi_tao' => $items[0]['nguoi_tao_name'],
            'ma_phieu'  => $ma_phieu
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}

$conn->close();

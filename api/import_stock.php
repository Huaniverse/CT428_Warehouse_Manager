<?php

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/partials/helpers-stock.php';

header('Content-Type: application/json; charset=utf-8');

requireCanImportExport();
requireDb($conn);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── Tạo phiếu nhập ────────────────────────────────────────────────────
    case 'create':
        requirePost();
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
        if (mb_strlen($ghi_chu) > 1000) {
            echo json_encode(['success' => false, 'message' => 'Ghi chú không được quá 1000 ký tự.']);
            exit;
        }

        $conn->begin_transaction();
        try {
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

            $ma_phieu = 'PN_' . date('YmdHis') . '_' . random_int(1000, 9999);

            $header = $conn->prepare("INSERT INTO phieu_nhap (ma_phieu, nguoi_tao, ngay_tao) VALUES (?, ?, NOW())");
            $header->bind_param("si", $ma_phieu, $nguoi_tao);
            $header->execute();
            $header->close();

            $detail = $conn->prepare("INSERT INTO chi_tiet_phieu_nhap (ma_phieu, san_pham, so_luong, ghi_chu) VALUES (?, ?, ?, ?)");
            $detail->bind_param("siis", $ma_phieu, $san_pham, $so_luong, $ghi_chu);
            $detail->execute();
            $detail->close();

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
        requirePost();
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
            $ma_phieu = 'PN_' . date('YmdHis') . '_' . random_int(1000, 9999);

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
        echo json_encode(getStockList($conn, 'import'));
        break;

    // ── Chi tiết phiếu nhập ───────────────────────────────────────────────
    case 'detail':
        $ma_phieu = trim($_GET['ma_phieu'] ?? '');
        if ($ma_phieu === '') {
            echo json_encode(['success' => false, 'message' => 'Thiếu mã phiếu.']);
            exit;
        }
        echo json_encode(getStockDetail($conn, 'import', $ma_phieu));
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}

$conn->close();

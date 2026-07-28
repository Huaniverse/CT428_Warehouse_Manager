<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../partials/helpers-users.php';

$current_user    = getCurrentUser();
$is_admin        = isAdmin();
$is_store_manager = isStoreManager();

extract(getDashboardData($conn));
?>
  <?php require_once ROOT_PATH . '/php/partials/head.php'; ?>

<body>
  <?php require_once ROOT_PATH . '/php/partials/header.php'; ?>
  <main>
    <?php require_once ROOT_PATH . '/php/partials/sidebar.php'; ?>
    <div class="main_content">
      <?php require_once ROOT_PATH . '/php/partials/tab_dashboard.php'; ?>
      <?php require_once ROOT_PATH . '/php/partials/tab_warehouse.php'; ?>
      <?php require_once ROOT_PATH . '/php/partials/tab_history.php'; ?>
      <?php require_once ROOT_PATH . '/php/partials/tab_users.php'; ?>
    </div>
  </main>
  <?php require_once ROOT_PATH . '/php/partials/modals.php'; ?>
  <?php require_once ROOT_PATH . '/php/partials/footer.php'; ?>

  <script>
      window.BASE_URL = '<?php echo BASE_URL; ?>';
    window.APP_CONFIG = {
      isAdmin: <?php echo $is_admin ? 'true' : 'false'; ?>,
      isStoreManager: <?php echo $is_store_manager ? 'true' : 'false'; ?>,
      canManageProducts: <?php echo ($is_admin || $is_store_manager) ? 'true' : 'false'; ?>,
      canViewProducts: <?php echo ($is_admin || $is_store_manager || canImportExport()) ? 'true' : 'false'; ?>,
      role: '<?php echo $current_user['role']; ?>',
      currentUserId: <?php echo $current_user['id']; ?>
    };
  </script>
<?php $p = defined('ROOT_CONTEXT') ? '' : '../../'; ?>
  <script src="<?= $p ?>public/js/app.js"></script>
  <script src="<?= $p ?>public/js/products.js"></script>
  <?php if (canImportExport()): ?>
    <script src="<?= $p ?>public/js/stock.js"></script>
    <script src="<?= $p ?>public/js/combobox.js"></script>
  <?php endif; ?>
  <?php if ($is_admin || $is_store_manager): ?>
    <script src="<?= $p ?>public/js/admin-users.js"></script>
  <?php endif; ?>

  <script>
    const chart1Labels = <?php echo json_encode($chart1_labels); ?>;
    const chart1Data = <?php echo json_encode($chart1_data); ?>;
    const chart2Labels = <?php echo json_encode($chart2_labels); ?>;
    const chart2Data = <?php echo json_encode($chart2_data); ?>;
    const chartTrendLabels = <?php echo json_encode($chart_trend_labels); ?>;
    const chartTrendImport = <?php echo json_encode($chart_trend_import); ?>;
    const chartTrendExport = <?php echo json_encode($chart_trend_export); ?>;
    const chartStatusLabels = <?php echo json_encode($chart_status_labels); ?>;
    const chartStatusData = <?php echo json_encode($chart_status_data); ?>;
    const chartTopLabels = <?php echo json_encode($chart_top_labels); ?>;
    const chartTopData = <?php echo json_encode($chart_top_data); ?>;
  </script>
  <script src="<?= $p ?>public/js/dashboard.js"></script>
</body>
<?php if ($conn) $conn->close(); ?>

</html>
<?php
// index.php — Entry point duy nhất, render theo role
require_once __DIR__ . '/shared/config.php';
require_once __DIR__ . '/shared/db.php';
require_once __DIR__ . '/shared/auth.php';
require_once __DIR__ . '/modules/users/helpers.php';

$current_user    = getCurrentUser();
$is_admin        = isAdmin();
$is_store_manager = isStoreManager();

extract(getDashboardData($conn));
?>
<?php require_once ROOT_PATH . '/shared/components/head.php'; ?>

<body>
  <?php require_once ROOT_PATH . '/shared/components/header.php'; ?>
  <main>
    <?php require_once ROOT_PATH . '/shared/components/sidebar.php'; ?>
    <div class="main_content">
      <?php require_once ROOT_PATH . '/modules/users/tab_dashboard.php'; ?>
      <?php require_once ROOT_PATH . '/modules/products/tab_warehouse.php'; ?>
      <?php require_once ROOT_PATH . '/modules/stock/tab_history.php'; ?>
      <?php require_once ROOT_PATH . '/modules/users/tab_users.php'; ?>
    </div>
  </main>
  <?php require_once ROOT_PATH . '/shared/components/modals.php'; ?>
  <?php require_once ROOT_PATH . '/shared/components/footer.php'; ?>

  <script>
      window.BASE_URL = '<?php echo BASE_URL; ?>';
    window.APP_CONFIG = {
      isAdmin: <?php echo $is_admin ? 'true' : 'false'; ?>,
      isStoreManager: <?php echo $is_store_manager ? 'true' : 'false'; ?>,
      canManageProducts: <?php echo ($is_admin || $is_store_manager) ? 'true' : 'false'; ?>,
      role: '<?php echo $current_user['role']; ?>',
      currentUserId: <?php echo $current_user['id']; ?>
    };
  </script>
  <script src="shared/js/app.js"></script>
  <script src="modules/products/js/products.js"></script>
  <?php if (canImportExport()): ?>
    <script src="modules/stock/js/stock.js"></script>
    <script src="modules/stock/js/combobox.js"></script>
  <?php endif; ?>
  <?php if ($is_admin || $is_store_manager): ?>
    <script src="modules/users/js/admin-users.js"></script>
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
  <script src="modules/users/js/dashboard.js"></script>
</body>
<?php if ($conn) $conn->close(); ?>

</html>
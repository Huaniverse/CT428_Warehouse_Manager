<?php
// index.php — Entry point duy nhất, render theo role
require_once __DIR__ . '/shared/config.php';
require_once __DIR__ . '/shared/db.php';
require_once __DIR__ . '/shared/auth.php';
require_once __DIR__ . '/shared/helpers.php';

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
      <?php require_once ROOT_PATH . '/shared/components/tab_dashboard.php'; ?>
      <?php require_once ROOT_PATH . '/shared/components/tab_warehouse.php'; ?>
      <?php require_once ROOT_PATH . '/shared/components/tab_history.php'; ?>
      <?php require_once ROOT_PATH . '/shared/components/tab_users.php'; ?>
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
  <script src="shared/js/products.js"></script>
  <?php if (canImportExport()): ?>
    <script src="shared/js/stock.js"></script>
    <script src="shared/js/combobox.js"></script>
  <?php endif; ?>
  <?php if ($is_admin || $is_store_manager): ?>
    <script src="admin/js/admin-products.js"></script>
  <?php endif; ?>

  <script>
    const chart1Labels = <?php echo json_encode($chart1_labels); ?>;
    const chart1Data = <?php echo json_encode($chart1_data); ?>;
    const chart2Labels = <?php echo json_encode($chart2_labels); ?>;
    const chart2Data = <?php echo json_encode($chart2_data); ?>;
    const chart2DataMillion = chart2Data.map(val => (val / 1000000).toFixed(2));
    const ctxQty = document.getElementById('quantityChart').getContext('2d');
    new Chart(ctxQty, {
      type: 'bar',
      data: {
        labels: chart1Labels,
        datasets: [{
          label: 'Số lượng sản phẩm',
          data: chart1Data,
          backgroundColor: 'rgba(59, 130, 246, 0.7)',
          borderColor: '#3b82f6',
          borderWidth: 1,
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: '#f1f5f9'
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });
    const ctxVal = document.getElementById('valueChart').getContext('2d');
    new Chart(ctxVal, {
      type: 'doughnut',
      data: {
        labels: chart2Labels,
        datasets: [{
          label: 'Giá trị (Triệu VNĐ)',
          data: chart2DataMillion,
          backgroundColor: ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4'],
          borderWidth: 2,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              boxWidth: 12,
              font: {
                family: "'Inter', sans-serif"
              }
            }
          }
        }
      }
    });
  </script>
</body>
<?php if ($conn) $conn->close(); ?>

</html>
<?php
session_start();
$_SESSION['user_id'] = 2; // Assuming 2 is store_manager
$_SESSION['role'] = 'store_manager';
$_SESSION['allow_import_export'] = 1;
$_SESSION['csrf_token'] = 'test';

// Test filter_products.php
$_GET['limit'] = 'all';
ob_start();
require 'filter_products.php';
$html = ob_get_clean();
echo "Filter Products length: " . strlen($html) . "\n";
echo "Filter Products snippet: \n" . substr($html, 0, 500) . "\n";

// Test import_stock.php
$_GET['action'] = 'list';
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
require 'admin/import_stock.php';
$json = ob_get_clean();
echo "Import Stock output: \n" . $json . "\n";


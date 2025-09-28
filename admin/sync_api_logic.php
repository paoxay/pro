<?php
// File: admin/sync_api_logic.php (The background worker for the sync page)
require_once 'db_connect.php';
require_once 'auth_check.php';

// Helper function (must be available here too)
function callAPI($supplier, $endpoint, $extra_params = []) {
    // ... Copy the full callAPI function from sync_page.php here ...
}

// Enable real-time output
header('Content-Type: text/plain; charset=utf-8');
ob_end_flush();
ob_implicit_flush(true);

set_time_limit(600);

$supplier_id = $_GET['id'] ?? 0;
if ($supplier_id <= 0) { die("ERROR: Invalid Supplier ID."); }

$stmt_supplier = $conn->prepare("SELECT * FROM api_suppliers WHERE id = ?");
$stmt_supplier->bind_param("i", $supplier_id);
$stmt_supplier->execute();
$supplier = $stmt_supplier->get_result()->fetch_assoc();

if (!$supplier) { die("ERROR: Supplier not found."); }

echo "Starting sync for supplier: " . htmlspecialchars($supplier['name']) . "...\n\n";

$conn->query("DELETE FROM api_cache_products WHERE api_supplier_id = " . (int)$supplier_id);
echo "--> Cleared old product cache for this supplier.\n";
flush();

$total_synced = 0;
$conn->begin_transaction();
try {
    $stmt_insert = $conn->prepare("REPLACE INTO api_cache_products (api_supplier_id, product_code, product_name, category, brand, jenis, cost_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $categories_response = callAPI($supplier, 'produk/category/list');
    if ($categories_response['success'] && !empty($categories_response['data'])) {
        echo "Found " . count($categories_response['data']) . " categories. Processing...\n\n";
        foreach ($categories_response['data'] as $category) {
            echo "[CATEGORY: {$category['nama']}]\n";
            $operators_response = callAPI($supplier, 'produk/operator/list', ['id' => $category['id']]);
            if ($operators_response['success'] && !empty($operators_response['data'])) {
                foreach ($operators_response['data'] as $operator) {
                     echo "  - Operator: {$operator['nama']}\n";
                     $jenis_response = callAPI($supplier, 'produk/jenis/list', ['id' => $operator['id']]);
                     if ($jenis_response['success'] && !empty($jenis_response['data'])) {
                         foreach ($jenis_response['data'] as $jenis) {
                             $packages_response = callAPI($supplier, 'produk/list', ['id_jenis' => $jenis['id']]);
                             if ($packages_response['success'] && !empty($packages_response['data'])) {
                                 foreach($packages_response['data'] as $package) {
                                    $stmt_insert->bind_param("isssssd", $supplier_id, $package['code'], $package['nama_produk'], $package['category_name'], $package['operator_produk'], $package['jenis_name'], $package['price']);
                                    $stmt_insert->execute();
                                    $total_synced++;
                                 }
                             }
                         }
                     }
                }
            }
        }
        $stmt_insert->close();
        $conn->commit();
        echo "\n----------------------------------------\nSYNC COMPLETE!\nSuccessfully synced $total_synced products.";
    } else {
        throw new Exception("Could not fetch categories from API.");
    }
} catch (Exception $e) {
    $conn->rollback();
    echo "AN ERROR OCCURRED: " . $e->getMessage() . "\n";
}
?>
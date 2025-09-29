<?php
// File: admin/ajax_update_prices.php (New file)
require_once 'db_connect.php';
require_once 'auth_check.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$supplier_id = $input['supplier_id'] ?? 0;

if ($supplier_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Supplier ID ບໍ່ຖືກຕ້ອງ.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Get the new exchange rate for the supplier
    $stmt_rate = $conn->prepare("SELECT exchange_rate FROM api_suppliers WHERE id = ?");
    $stmt_rate->bind_param("i", $supplier_id);
    $stmt_rate->execute();
    $result_rate = $stmt_rate->get_result();
    if ($result_rate->num_rows === 0) {
        throw new Exception("ບໍ່ພົບ Supplier ທີ່ມີ ID: $supplier_id");
    }
    $new_exchange_rate = $result_rate->fetch_assoc()['exchange_rate'];
    $stmt_rate->close();

    // 2. Select all packages that need updating
    $sql_select = "
        SELECT 
            gp.id,
            gp.price AS current_selling_price,
            gp.cost_price AS current_lak_cost,
            acp.cost_price AS api_cost_original
        FROM game_packages AS gp
        JOIN games AS g ON gp.game_id = g.id
        JOIN api_cache_products AS acp ON gp.api_product_code = acp.product_code AND g.api_supplier_id = acp.api_supplier_id
        WHERE g.api_supplier_id = ?
    ";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $supplier_id);
    $stmt_select->execute();
    $packages_to_update = $stmt_select->get_result();
    
    $updated_count = 0;
    
    if ($packages_to_update->num_rows > 0) {
        $stmt_update = $conn->prepare("UPDATE game_packages SET cost_price = ?, price = ? WHERE id = ?");

        while ($pkg = $packages_to_update->fetch_assoc()) {
            $current_lak_cost = (float)$pkg['current_lak_cost'];
            $current_selling_price = (float)$pkg['current_selling_price'];
            $api_cost_original = (float)$pkg['api_cost_original'];

            // Calculate new LAK cost price
            $new_lak_cost = $api_cost_original * $new_exchange_rate;

            // Preserve the existing markup ratio
            $markup_ratio = ($current_lak_cost > 0) ? ($current_selling_price / $current_lak_cost) : 1.15; // Default to 15% if old cost is 0

            // Calculate new selling price and round it
            $new_selling_price_raw = $new_lak_cost * $markup_ratio;
            $new_selling_price = ceil($new_selling_price_raw / 1000) * 1000;

            // Execute the update
            $stmt_update->bind_param("ddi", $new_lak_cost, $new_selling_price, $pkg['id']);
            $stmt_update->execute();
            $updated_count++;
        }
        $stmt_update->close();
    }
    
    $stmt_select->close();
    $conn->commit();

    echo json_encode(['success' => true, 'updated_packages' => $updated_count]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
<?php
// File: admin/price_updater_function.php

function updatePricesForSupplier($supplier_id, $conn) {
    if ($supplier_id <= 0) {
        return ['success' => false, 'message' => 'Supplier ID ບໍ່ຖືກຕ້ອງ.'];
    }

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
                gp.id, gp.markup_percent,
                acp.cost_price AS api_cost_original
            FROM game_packages AS gp
            JOIN games AS g ON gp.game_id = g.id
            JOIN api_cache_products AS acp ON gp.api_product_code = acp.product_code AND g.api_supplier_id = acp.api_supplier_id
            WHERE g.api_supplier_id = ? AND gp.api_product_code IS NOT NULL AND gp.api_product_code != ''
        ";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param("i", $supplier_id);
        $stmt_select->execute();
        $packages_to_update = $stmt_select->get_result();
        
        $updated_count = 0;
        
        if ($packages_to_update->num_rows > 0) {
            $stmt_update = $conn->prepare("UPDATE game_packages SET cost_price = ?, price = ? WHERE id = ?");

            while ($pkg = $packages_to_update->fetch_assoc()) {
                $markup_percent = ($pkg['markup_percent'] !== null) ? (float)$pkg['markup_percent'] : 15.0;
                $api_cost_original = (float)$pkg['api_cost_original'];

                // Calculate new LAK cost price
                $new_lak_cost = $api_cost_original * $new_exchange_rate;

                // Calculate new selling price based on the package's specific markup
                $new_selling_price_raw = $new_lak_cost * (1 + $markup_percent / 100);
                $new_selling_price = ($markup_percent == 0) ? $new_lak_cost : ceil($new_selling_price_raw / 1000) * 1000;

                // Execute the update
                $stmt_update->bind_param("ddi", $new_lak_cost, $new_selling_price, $pkg['id']);
                $stmt_update->execute();
                $updated_count++;
            }
            $stmt_update->close();
        }
        $stmt_select->close();
        
        return ['success' => true, 'updated_packages' => $updated_count];

    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>
<?php
// File: admin/ajax_update_prices.php (Simplified to use the central function)
require_once 'db_connect.php';
require_once 'auth_check.php';
require_once 'price_updater_function.php'; // ເອີ້ນໃຊ້ Function
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$supplier_id = $input['supplier_id'] ?? 0;

$conn->begin_transaction();
$result = updatePricesForSupplier($supplier_id, $conn);

if ($result['success']) {
    $conn->commit();
    echo json_encode($result);
} else {
    $conn->rollback();
    echo json_encode($result);
}

$conn->close();
?>
<?php
// File: admin/ajax_save_smileone_package_order.php
require_once 'db_connect.php';
require_once 'auth_check.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$package_ids = $input['package_ids'] ?? [];

if (empty($package_ids) || !is_array($package_ids)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit;
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE smileone_packages SET display_order = ? WHERE id = ?");
    
    foreach ($package_ids as $index => $id) {
        $order = $index + 1;
        $package_id = (int)$id;
        $stmt->bind_param("ii", $order, $package_id);
        $stmt->execute();
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
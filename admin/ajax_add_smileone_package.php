<?php
// File: admin/ajax_edit_smileone_package.php (Upgraded)
require_once 'db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? 0;
$name = trim($input['name'] ?? '');
$api_price = (float)($input['api_price'] ?? 0);
$cost_price = (float)($input['cost_price'] ?? 0);
$selling_price = (float)($input['selling_price'] ?? 0);

if ($id > 0 && !empty($name)) {
    $stmt = $conn->prepare("UPDATE smileone_packages SET name = ?, api_price = ?, cost_price = ?, selling_price = ? WHERE id = ?");
    $stmt->bind_param("sdddi", $name, $api_price, $cost_price, $selling_price, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
}
$conn->close();
?>
<?php
// File: admin/ajax_edit_package.php
require_once 'db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? 0;
$name = trim($input['name'] ?? '');
$price = $input['price'] ?? 0;
$cost_price = $input['cost_price'] ?? 0;

if ($id > 0 && !empty($name) && is_numeric($price) && is_numeric($cost_price)) {
    $stmt = $conn->prepare("UPDATE game_packages SET name = ?, price = ?, cost_price = ? WHERE id = ?");
    $stmt->bind_param("sddi", $name, $price, $cost_price, $id);
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
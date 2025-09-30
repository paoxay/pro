<?php
// File: admin/ajax_delete_smileone_package.php
require_once 'db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$package_id = $input['id'] ?? 0;

if ($package_id > 0) {
    $stmt = $conn->prepare("DELETE FROM smileone_packages WHERE id = ?");
    $stmt->bind_param("i", $package_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete package.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
}
$conn->close();
?>
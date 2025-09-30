<?php
// File: admin/ajax_update_smileone_package_status.php
require_once 'db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;
$status = $input['status'] ?? '';

if ($id > 0 && ($status == 'active' || $status == 'inactive')) {
    $stmt = $conn->prepare("UPDATE smileone_packages SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
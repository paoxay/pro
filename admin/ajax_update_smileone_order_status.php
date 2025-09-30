<?php
// File: admin/ajax_update_smileone_order_status.php
require_once 'db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['id'] ?? 0;
$new_status = $input['status'] ?? '';
$allowed_statuses = ['pending', 'processing', 'completed', 'cancelled'];

if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
    // ປ່ຽນຊື່ຕາຕະລາງເປັນ smileone_orders
    $stmt = $conn->prepare("UPDATE smileone_orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
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
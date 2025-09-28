<?php
// File: admin/ajax_update_order_status.php
require_once 'db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['id'] ?? 0;
$new_status = $input['status'] ?? '';
$allowed_statuses = ['pending', 'processing', 'completed', 'cancelled'];

// Get current status before update, in case we need to revert
$old_status = '';
$stmt_get = $conn->prepare("SELECT status FROM orders WHERE id = ?");
$stmt_get->bind_param("i", $order_id);
if ($stmt_get->execute()) {
    $result = $stmt_get->get_result();
    if($result->num_rows > 0) {
        $old_status = $result->fetch_assoc()['status'];
    }
}
$stmt_get->close();


if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        // (Optional) Add logic here if a 'completed' status should affect wallet balance, etc.
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed.', 'old_status' => $old_status]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input data.', 'old_status' => $old_status]);
}

$conn->close();
?>
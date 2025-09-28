<?php
// File: admin/ajax_adjust_balance.php
require_once 'db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$member_id = $input['member_id'] ?? 0;
$amount = $input['amount'] ?? 0;
$action = $input['action'] ?? '';
$notes = trim($input['notes'] ?? 'ປັບປຸງໂດຍແອັດມິນ');

if ($member_id > 0 && is_numeric($amount) && $amount > 0 && ($action == 'add' || $action == 'deduct')) {
    $conn->begin_transaction();
    try {
        $adjustment_amount = ($action == 'add') ? $amount : -$amount;
        $transaction_type = ($action == 'add') ? 'deposit_by_admin' : 'deduct_by_admin';

        $stmt_update = $conn->prepare("UPDATE members SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt_update->bind_param("di", $adjustment_amount, $member_id);
        $stmt_update->execute();

        $stmt_insert = $conn->prepare("INSERT INTO wallet_transactions (member_id, amount, transaction_type, notes) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("idss", $member_id, $adjustment_amount, $transaction_type, $notes);
        $stmt_insert->execute();
        
        $stmt_get_new_balance = $conn->prepare("SELECT wallet_balance FROM members WHERE id = ?");
        $stmt_get_new_balance->bind_param("i", $member_id);
        $stmt_get_new_balance->execute();
        $new_balance = $stmt_get_new_balance->get_result()->fetch_assoc()['wallet_balance'];

        $conn->commit();
        echo json_encode(['success' => true, 'new_balance' => $new_balance]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ຂໍ້ມູນບໍ່ຖືກຕ້ອງ.']);
}
$conn->close();
?>
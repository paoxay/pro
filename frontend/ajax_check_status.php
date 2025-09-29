<?php
// File: /frontend/ajax_check_status.php
session_start();
header('Content-Type: application/json');

require_once 'db.php';
require_once '../admin/api_helper.php';

if (!isset($_SESSION['member_loggedin']) || !isset($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

$member_id = $_SESSION['member_id'];
$order_id = (int)$_POST['order_id'];

// ດຶງຂໍ້ມູນທີ່ຈຳເປັນຈາກຖານຂໍ້ມູນ, ພ້ອມກວດສອບວ່າອໍເດີ้นี้เป็นของผู้ใช้ที่ล็อกอินอยู่จริง
$sql = "SELECT o.id, o.order_code, o.amount, o.status, 
               s.api_base_url, s.member_code, s.api_secret_key
        FROM orders o
        JOIN game_packages p ON o.package_id = p.id
        JOIN games g ON p.game_id = g.id
        JOIN api_suppliers s ON g.api_supplier_id = s.id
        WHERE o.id = ? AND o.member_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit;
}
$order = $result->fetch_assoc();

// ຖ້າສະຖານະບໍ່ແມ່ນ 'processing', ບໍ່ຈຳເປັນຕ້ອງກວດສອບອີກ
if ($order['status'] !== 'processing') {
    echo json_encode(['success' => true, 'status' => $order['status'], 'message' => 'Status is already final.']);
    exit;
}

// ເອີ້ນ API ເພື່ອກວດສອບສະຖານະ
$status_result = callV1API($order, 'transaksi/status', $order['order_code']);

if ($status_result['success'] && isset($status_result['data']['status'])) {
    $api_status = strtolower($status_result['data']['status']);
    $new_status = $order['status'];

    if ($api_status == 'sukses') {
        $new_status = 'completed';
        $conn->query("UPDATE orders SET status = 'completed' WHERE id = $order_id");
        echo json_encode(['success' => true, 'status' => $new_status, 'message' => 'Order completed!']);

    } elseif ($api_status == 'gagal') {
        $new_status = 'cancelled';
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE orders SET status = 'cancelled' WHERE id = $order_id");
            $refund_amount = (float)$order['amount'];
            $conn->query("UPDATE members SET wallet_balance = wallet_balance + $refund_amount WHERE id = $member_id");
            $notes_refund = "Refund for failed API order #" . $order['order_code'];
            $stmt_trans = $conn->prepare("INSERT INTO wallet_transactions (member_id, amount, transaction_type, notes) VALUES (?, ?, 'refund', ?)");
            $stmt_trans->bind_param("ids", $member_id, $refund_amount, $notes_refund);
            $stmt_trans->execute();
            $conn->commit();
            echo json_encode(['success' => true, 'status' => $new_status, 'message' => 'Order failed and has been refunded.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Refund failed: ' . $e->getMessage()]);
        }
    } else {
        // ຖ້າສະຖານະຍັງເປັນ pending, ບໍ່ຕ້ອງເຮັດຫຍັງ
        echo json_encode(['success' => true, 'status' => 'processing', 'message' => 'Order is still being processed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => $status_result['message'] ?? 'Failed to get status from API.']);
}
$conn->close();
?>
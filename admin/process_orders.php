<?php
// File: admin/process_orders.php (Final Version with Query Check)
set_time_limit(300);

// You can comment these out after everything is working
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "--- SCRIPT STARTED AT: " . date('Y-m-d H:i:s') . " ---\n\n";

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/api_helper.php';

$sql = "SELECT o.id, o.order_code, o.member_id, o.amount, 
               s.api_base_url, s.member_code, s.api_secret_key
        FROM orders o
        JOIN game_packages p ON o.package_id = p.id
        JOIN games g ON p.game_id = g.id
        JOIN api_suppliers s ON g.api_supplier_id = s.id
        WHERE o.status = 'processing'";
$processing_orders = $conn->query($sql);

if (!$processing_orders) {
    die("DATABASE QUERY FAILED: " . $conn->error);
}

if ($processing_orders->num_rows > 0) {
    echo "Found " . $processing_orders->num_rows . " orders to check.\n\n";

    while($order = $processing_orders->fetch_assoc()) {
        $order_id = $order['id'];
        $order_code_as_ref_id = $order['order_code'];
        
        echo "============================================\n";
        echo "CHECKING ORDER ID: $order_id (REF_ID: $order_code_as_ref_id)\n";
        
        $status_result = callV1API($order, 'transaksi/status', $order_code_as_ref_id);

        echo "API RESPONSE:\n";
        print_r($status_result);
        echo "\n";

        if ($status_result['success'] && isset($status_result['data']['status'])) {
            $api_status = strtolower($status_result['data']['status']);
            echo "API Status is: '$api_status'\n";

            if ($api_status == 'sukses') {
                // ***** START: ADDED QUERY CHECK *****
                $update_sql = "UPDATE orders SET status = 'completed' WHERE id = $order_id";
                if ($conn->query($update_sql) === TRUE) {
                    echo "ACTION: Successfully updated local status to COMPLETED.\n";
                } else {
                    echo "DATABASE ERROR: Failed to update status to COMPLETED. Reason: " . $conn->error . "\n";
                }
                // ***** END: ADDED QUERY CHECK *****

            } elseif ($api_status == 'gagal') {
                $conn->begin_transaction();
                try {
                    // ***** START: ADDED QUERY CHECK *****
                    $update_cancel_sql = "UPDATE orders SET status = 'cancelled' WHERE id = $order_id";
                    if(!$conn->query($update_cancel_sql)) {
                         throw new Exception("Failed to update order status to cancelled: " . $conn->error);
                    }
                    
                    $refund_amount = (float)$order['amount'];
                    $member_id = $order['member_id'];
                    $update_wallet_sql = "UPDATE members SET wallet_balance = wallet_balance + $refund_amount WHERE id = $member_id";
                    if(!$conn->query($update_wallet_sql)) {
                        throw new Exception("Failed to refund to member wallet: " . $conn->error);
                    }
                    // ***** END: ADDED QUERY CHECK *****
                    
                    $notes_refund = "Refund for failed API order #" . $order_code_as_ref_id;
                    $stmt_trans = $conn->prepare("INSERT INTO wallet_transactions (member_id, amount, transaction_type, notes) VALUES (?, ?, 'refund', ?)");
                    $stmt_trans->bind_param("ids", $member_id, $refund_amount, $notes_refund);
                    $stmt_trans->execute();
                    
                    $conn->commit();
                    echo "ACTION: Updated local status to CANCELLED and refunded $refund_amount.\n";
                } catch (Exception $e) {
                    $conn->rollback();
                    echo "ERROR: Could not process refund. " . $e->getMessage() . "\n";
                }
            } else {
                echo "ACTION: Status is still pending. No change needed.\n";
            }
        } else {
            echo "ERROR: Failed to get a valid status from API. Reason: " . ($status_result['message'] ?? 'Unknown') . "\n";
        }
        echo "============================================\n\n";
    }
} else {
    echo "No 'processing' orders found to check.\n";
}

echo "--- SCRIPT FINISHED AT: " . date('Y-m-d H:i:s') . " ---\n";
$conn->close();
?>
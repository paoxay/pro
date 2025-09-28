<?php
// File: admin/ajax_check_order_status.php (Final Logic Correction)
require_once 'db_connect.php';
require_once 'auth_check.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['order_id'] ?? 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Order ID.']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT o.id, o.api_transaction_id, s.member_code, s.api_secret_key, s.api_base_url FROM orders o JOIN game_packages p ON o.package_id = p.id JOIN games g ON p.game_id = g.id JOIN api_suppliers s ON g.api_supplier_id = s.id WHERE o.id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Order or Supplier details not found.");
    }
    $data = $result->fetch_assoc();
    $stmt->close();

    $ref_id = $data['api_transaction_id'];
    if (empty($ref_id)) { throw new Exception("Ref ID not found for this order."); }

    $member_code = $data['member_code'];
    $secret_key = $data['api_secret_key'];
    
    $stringToHash = $member_code . ":" . $secret_key . ":" . $ref_id;
    $signature = md5($stringToHash);
    $api_url = rtrim($data['api_base_url'], '/') . '/v1/transaksi/status';
    
    $post_data = ['member_code' => $member_code, 'ref_id' => $ref_id, 'signature' => $signature];

    $ch = curl_init();
    curl_setopt_array($ch, [ CURLOPT_URL => $api_url, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ["Content-Type: application/json"], CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($post_data) ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response_body = curl_exec($ch);
    $api_response = json_decode($response_body, true);
    curl_close($ch);

    // /// --- START: FINAL CORRECTED LOGIC --- ///
    if (isset($api_response['status'])) {
        $api_status_string = strtolower($api_response['status']);

        $new_status_in_db = 'processing';
        if ($api_status_string == 'sukses') {
            $new_status_in_db = 'completed';
        } elseif ($api_status_string == 'gagal') {
            $new_status_in_db = 'cancelled';
        }

        $sn = $api_response['sn'] ?? null;
        $message = $api_response['message'] ?? 'Status updated from API';

        $stmt_update = $conn->prepare("UPDATE orders SET status = ?, api_sn = ?, api_response_message = ? WHERE id = ?");
        $stmt_update->bind_param("sssi", $new_status_in_db, $sn, $message, $order_id);
        $stmt_update->execute();
        $stmt_update->close();

        echo json_encode([
            'success' => true, 
            'message' => 'Status updated successfully!',
            'new_status' => $new_status_in_db,
            'sn' => $sn
        ]);
    } else {
        throw new Exception($api_response['message'] ?? 'Failed to get a valid status from API.');
    }
    // /// --- END: FINAL CORRECTED LOGIC --- ///

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
<?php
// File: frontend/ajax_process_order.php (Corrected PENDING Logic)
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['member_loggedin']) || $_SESSION['member_loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'ກະລຸນາເຂົ້າສູ່ລະບົບໃໝ່.']);
    exit;
}

$member_id = $_SESSION['member_id'];
$stmt_wallet_check = $conn->prepare("SELECT wallet_balance FROM members WHERE id = ?");
$stmt_wallet_check->bind_param("i", $member_id);
$stmt_wallet_check->execute();
$wallet_balance = $stmt_wallet_check->get_result()->fetch_assoc()['wallet_balance'] ?? 0;
$stmt_wallet_check->close();

$input = json_decode(file_get_contents('php://input'), true);
$package_id = $input['package_id'] ?? 0;
$custom_fields_data = $input['fields'] ?? [];

$is_valid = true;
if (empty($custom_fields_data)) {
    $game_id = $input['game_id'] ?? 0;
    if ($game_id > 0) {
        $fields_result_check = $conn->query("SELECT id FROM game_fields WHERE game_id = " . (int)$game_id);
        if ($fields_result_check->num_rows > 0) $is_valid = false;
    }
} else { foreach($custom_fields_data as $value) { if (empty(trim($value))) $is_valid = false; } }

if (empty($package_id) || !$is_valid) {
    echo json_encode(['success' => false, 'message' => 'ກະລຸນາເລືອກແພັກເກັດ ແລະ ປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ.']);
    exit;
}

$game_user_info_json = json_encode($custom_fields_data, JSON_UNESCAPED_UNICODE);
$stmt_pkg = $conn->prepare("SELECT p.price, p.cost_price, p.name, p.api_product_code, g.api_supplier_id, s.api_base_url, s.member_code, s.api_secret_key FROM game_packages p JOIN games g ON p.game_id = g.id JOIN api_suppliers s ON g.api_supplier_id = s.id WHERE p.id = ?");
$stmt_pkg->bind_param("i", $package_id);
$stmt_pkg->execute();
$pkg_result = $stmt_pkg->get_result();

if ($pkg_result->num_rows === 1) {
    $package = $pkg_result->fetch_assoc();
    if ($wallet_balance >= $package['price']) {
        $conn->begin_transaction();
        try {
            $ref_id = "TT" . time() . rand(100,999); // Add random part for more uniqueness
            $balance_before = $wallet_balance;
            $balance_after = $wallet_balance - $package['price'];
            $profit = $package['price'] - $package['cost_price'];

            $stmt_wallet = $conn->prepare("UPDATE members SET wallet_balance = ? WHERE id = ?");
            $stmt_wallet->bind_param("di", $balance_after, $member_id);
            $stmt_wallet->execute();

            $stmt_order = $conn->prepare("INSERT INTO orders (member_id, order_code, package_id, game_user_info, amount, profit, balance_before, balance_after, status, api_transaction_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            $stmt_order->bind_param("isisdddsi", $member_id, $ref_id, $package_id, $game_user_info_json, $package['price'], $profit, $balance_before, $balance_after, $ref_id);
            $stmt_order->execute();
            $order_id = $conn->insert_id;
            
            // API Call Logic
            $tujuan = array_values($custom_fields_data)[0] ?? '';
            $server_id = array_values($custom_fields_data)[1] ?? '';
            $member_code = $package['member_code'];
            $secret_key = $package['api_secret_key'];
            $product_code = $package['api_product_code'];
            $stringToHash = $member_code . ":" . $secret_key . ":" . $ref_id;
            $signature = md5($stringToHash);
            $api_url = rtrim($package['api_base_url'], '/') . '/v1/transaksi';
            
            $post_data = ['ref_id' => $ref_id, 'produk' => $product_code, 'tujuan' => $tujuan, 'server_id' => $server_id, 'member_code' => $member_code, 'signature' => $signature];
            
            $ch = curl_init();
            curl_setopt_array($ch, [ CURLOPT_URL => $api_url, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ["Content-Type: application/json"], CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($post_data) ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response_body = curl_exec($ch);
            $api_response = json_decode($response_body, true);
            curl_close($ch);

            // /// --- START: CRITICAL FIX FOR PENDING STATUS --- ///
            // A request is considered successful if the API accepts it, even if the status is PENDING.
            // We check for the existence of 'rc' or if the message contains PENDING.
            $is_api_request_accepted = isset($api_response['rc']) || (isset($api_response['message']) && strpos($api_response['message'], 'PENDING') !== false);
            
            if ($is_api_request_accepted) {
                // The request was accepted by the API, so we keep the order.
                $initial_status = 'processing'; // We will check the final status later on the history page.
                $api_message = $api_response['message'] ?? $response_body; // Store the full response message
                $api_sn = $api_response['data']['sn'] ?? null;

                $stmt_update_order = $conn->prepare("UPDATE orders SET status = ?, api_response_message = ?, api_sn = ? WHERE id = ?");
                $stmt_update_order->bind_param("sssi", $initial_status, $api_message, $api_sn, $order_id);
                $stmt_update_order->execute();
            } else {
                // If API rejects the request outright (e.g., bad signature, invalid product), rollback everything.
                $error_msg = $api_response['message'] ?? 'API rejected the request.';
                throw new Exception($error_msg);
            }
            // /// --- END: CRITICAL FIX --- ///

            $purchase_amount = -$package['price'];
            $notes = "Order #" . $ref_id . " - ຊື້ '" . $package['name'] . "'";
            $stmt_trans = $conn->prepare("INSERT INTO wallet_transactions (member_id, amount, transaction_type, notes) VALUES (?, ?, 'purchase', ?)");
            $stmt_trans->bind_param("ids", $member_id, $purchase_amount, $notes);
            $stmt_trans->execute();

            $conn->commit();
            // Always return a generic success message to JavaScript as requested
            echo json_encode(['success' => true]);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => "ສັ່ງຊື້ບໍ່ສຳເລັດ: " . $e->getMessage()]);
            exit();
        }
    } else { // Not enough balance
        echo json_encode(['success' => false, 'message' => 'ຍອດເງິນໃນກະເປົາຂອງທ່ານບໍ່ພຽງພໍ!']);
        exit();
    }
}
echo json_encode(['success' => false, 'message' => 'ແພັກເກັດທີ່ເລືອກບໍ່ຖືກຕ້ອງ.']);
exit();
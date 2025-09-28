<?php
// File: frontend/debug_check_status.php
// This is a special script to test the API status check logic directly.

// --- STEP 1: CONFIGURE THE ORDER ID TO TEST ---
$order_id_to_check = 1; // <--- (ສຳຄັນ) ປ່ຽນເລກ 1 ເປັນ ID ຂອງອໍເດີ້ທີ່ທ່ານຕ້ອງການກວດສອບ

// --- DO NOT EDIT BELOW THIS LINE ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'db.php';
echo "<pre style='background-color: #111; color: #eee; padding: 20px; border-radius: 5px; font-family: monospace; line-height: 1.6;'>";
echo "<h1>DEBUGGING STATUS CHECK SCRIPT</h1><hr>";

try {
    echo "<b>[INFO]</b> Checking Order ID: $order_id_to_check\n\n";

    // 1. Fetch order and supplier details
    echo "<b>[DB]</b> Fetching order and supplier details from database...\n";
    $stmt = $conn->prepare("
        SELECT 
            o.id, o.api_transaction_id,
            s.member_code, s.api_secret_key, s.api_base_url
        FROM orders o
        JOIN game_packages p ON o.package_id = p.id
        JOIN games g ON p.game_id = g.id
        JOIN api_suppliers s ON g.api_supplier_id = s.id
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $order_id_to_check);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Order or Supplier details not found in database.");
    }
    $data = $result->fetch_assoc();
    $stmt->close();
    echo "<b style='color: #00ff00;'>[DB]</b> Success! Found data:\n";
    print_r($data);
    echo "\n";

    // 2. Prepare data for API call
    echo "<b>[API]</b> Preparing data for API call...\n";
    $ref_id = $data['api_transaction_id'];
    if (empty($ref_id)) { throw new Exception("Ref ID (api_transaction_id) is empty for this order in the database."); }
    
    $member_code = $data['member_code'];
    $secret_key = $data['api_secret_key'];
    
    // Build and show the string to be hashed for the signature
    $stringToHash = $member_code . ":" . $secret_key . ":" . $ref_id;
    $signature = md5($stringToHash);
    
    $api_url = rtrim($data['api_base_url'], '/') . '/v1/transaksi/status';
    
    $post_data = [
        'member_code' => $member_code,
        'ref_id'      => $ref_id,
        'signature'   => $signature
    ];

    echo "<b>[API]</b> String to be hashed for signature: " . htmlspecialchars($stringToHash) . "\n";
    echo "<b>[API]</b> Generated Signature: " . htmlspecialchars($signature) . "\n";
    echo "<b>[API]</b> Final Payload to be sent:\n";
    print_r($post_data);
    echo "\n";


    // 3. Call the API using cURL
    echo "<b>[cURL]</b> Sending POST request to: " . htmlspecialchars($api_url) . "\n";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($post_data)
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response_body = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    echo "<b>[cURL]</b> Request sent. Awaiting response...\n";
    if ($curl_error) {
        echo "<b style='color: #ff0000;'>[cURL]</b> cURL Error: " . htmlspecialchars($curl_error) . "\n";
    }
    echo "<b>[cURL]</b> Raw response from API:\n";
    echo "---------------------------------\n";
    echo htmlspecialchars($response_body);
    echo "\n---------------------------------\n\n";

    // 4. Process the response
    echo "<b>[LOGIC]</b> Decoding API response...\n";
    $api_response = json_decode($response_body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to decode JSON from API response.");
    }

    echo "<b>[LOGIC]</b> Decoded response:\n";
    print_r($api_response);
    echo "\n";
    
    echo "<b>[LOGIC]</b> Checking for 'status' key in response...\n";
    if (isset($api_response['status'])) {
        $api_status_string = strtolower($api_response['status']);
        echo "<b>[LOGIC]</b> Found status: '$api_status_string'\n";

        $new_status_in_db = 'processing';
        if ($api_status_string == 'sukses') {
            $new_status_in_db = 'completed';
        } elseif ($api_status_string == 'gagal') {
            $new_status_in_db = 'cancelled';
        }
        
        echo "<b style='color: #00ff00;'>[LOGIC]</b> Mapped API status to our system status: '$new_status_in_db'\n\n";
        echo "<hr><h1>FINAL RESULT: SUCCESS</h1>";
        echo "<p>The script successfully communicated with the API and determined the status should be <b>'$new_status_in_db'</b>.</p>";

    } else {
        throw new Exception("The key 'status' was not found in the API response.");
    }

} catch (Exception $e) {
    echo "<hr><h1 style='color: #ff0000;'>FINAL RESULT: ERROR</h1>";
    echo "<p style='color: #ff0000;'>" . $e->getMessage() . "</p>";
}

echo "</pre>";

$conn->close();
?>
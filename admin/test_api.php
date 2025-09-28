<?php
// File: admin/test_api.php (Corrected to use GET method)
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>API Connection Test Page</h1>";

require_once 'db_connect.php';

// ATTENTION: If your TokoVoucher supplier ID is not 1, change it here.
$supplier_id = 1;

echo "<p><b>Step 1:</b> Fetching credentials for Supplier ID: $supplier_id...</p>";

$stmt_supplier = $conn->prepare("SELECT * FROM api_suppliers WHERE id = ?");
$stmt_supplier->bind_param("i", $supplier_id);
$stmt_supplier->execute();
$supplier = $stmt_supplier->get_result()->fetch_assoc();
$stmt_supplier->close();

if (!$supplier) {
    die("<p style='color:red; font-weight:bold;'>ERROR: Could not find supplier with ID $supplier_id in the database. Please check 'Manage Suppliers' page.</p>");
}

echo "<p style='color:green; font-weight:bold;'>SUCCESS: Found Supplier in database.</p>";
echo "<ul style='list-style-type: none; padding-left: 20px; border-left: 2px solid #ccc; margin-left: 10px;'>";
echo "<li><b>Name:</b> " . htmlspecialchars($supplier['name']) . "</li>";
echo "<li><b>Base URL:</b> " . htmlspecialchars($supplier['api_base_url']) . "</li>";
echo "<li><b>Member Code:</b> " . htmlspecialchars($supplier['member_code']) . "</li>";
echo "<li><b>Signature:</b> " . htmlspecialchars($supplier['signature']) . "</li>";
echo "</ul><hr>";

// --- cURL function using GET ---
function callTokoVoucherAPI_GET_Debug($supplier, $endpoint, $extra_data = []) {
    $member_code = $supplier['member_code'];
    $signature = $supplier['signature'];

    $query_params = http_build_query(array_merge([
        'member_code' => $member_code,
        'signature'   => $signature,
    ], $extra_data));
    
    $url = rtrim($supplier['api_base_url'], '/') . '/member/' . $endpoint . '?' . $query_params;

    echo "<p><b>Step 2:</b> Preparing to call API...</p>";
    echo "<ul style='list-style-type: none; padding-left: 20px; border-left: 2px solid #ccc; margin-left: 10px;'>";
    echo "<li><b>Calling URL:</b> $url</li>";
    echo "<li><b>Method:</b> GET</li>";
    echo "</ul>";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // REMOVED: CURLOPT_POST and CURLOPT_POSTFIELDS to make it a GET request
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'LaoTopup/1.0');

    $response_body = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<p><b>Step 3:</b> Got response from server.</p>";
    echo "<ul style='list-style-type: none; padding-left: 20px; border-left: 2px solid #ccc; margin-left: 10px;'>";
    echo "<li><b>HTTP Status Code:</b> $http_code</li>";
    echo "<li><b>cURL Error (if any):</b> <span style='color:red;'>" . ($curl_error ?: 'None') . "</span></li>";
    echo "<li><b>Raw Response Body:</b></li>";
    echo "</ul>";
    echo "<pre style='background:#eee; padding:10px; border:1px solid #ddd; word-wrap:break-word;'>" . htmlspecialchars($response_body) . "</pre>";

    return json_decode($response_body, true);
}

echo "<hr><h2>Calling 'Get Categories' API...</h2>";
$result = callTokoVoucherAPI_GET_Debug($supplier, 'produk/category/list');

echo "<hr><h2>Final Parsed Result:</h2>";
echo "<pre style='background:#333; color: #fff; padding:10px; border-radius:5px;'>";
var_dump($result);
echo "</pre>";

?>
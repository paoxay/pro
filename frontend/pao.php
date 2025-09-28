<?php
// Config ของคุณ
$member_code = "M241013PPRB9467IF";
$secret      = "d583eba663aebc4ca7dbca51ea17a43da25044e5c1c3561221c5b1bf027e6938";

// Ref_id ของ transaksi ที่ต้องการเช็ค
$ref_id = "TT1759041051656";  // <-- เปลี่ยนเป็น ref_id จริงที่คุณยิงไป

// สร้าง signature
$stringToHash = $member_code . ":" . $secret . ":" . $ref_id;
$signature = md5($stringToHash);

// Payload
$data = [
    "ref_id"      => $ref_id,
    "member_code" => $member_code,
    "signature"   => $signature
];

// ส่ง API ด้วย cURL
$ch = curl_init("https://api.tokovoucher.net/v1/transaksi/status");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = curl_exec($ch);
curl_close($ch);

// แสดงผล
echo "=== Request Payload ===\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

echo "=== API Response ===\n";
echo $response;

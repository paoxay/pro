<?php
// File: admin/api_helper.php (Corrected Response Handling)

function callV1API($supplier_info, $endpoint, $ref_id, $extra_params = []) {
    if (empty($supplier_info['api_base_url']) || empty($supplier_info['member_code']) || empty($supplier_info['api_secret_key'])) {
        return ['success' => false, 'message' => 'Supplier credentials (member_code, secret_key) incomplete.'];
    }

    $member_code = $supplier_info['member_code'];
    $secret_key = $supplier_info['api_secret_key'];
    $signature = md5($member_code . ':' . $secret_key . ':' . $ref_id);

    $body_data = array_merge([
        'ref_id'      => $ref_id,
        'member_code' => $member_code,
        'signature'   => $signature
    ], $extra_params);
    $json_body = json_encode($body_data);

    $url = rtrim($supplier_info['api_base_url'], '/') . '/v1/' . ltrim($endpoint, '/');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'LaoTopup/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Content-Length: ' . strlen($json_body)
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response_body = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return ['success' => false, 'message' => 'cURL Error: ' . $curl_error, 'data' => null];
    }

    $decoded = json_decode($response_body, true);
    
    // ***** START: LOGIC ໃໝ່ທີ່ຖືກຕ້ອງ *****
    // ຖ້າການ decode JSON ລົ້ມເຫຼວ, ສະແດງວ່າ API ມີບັນຫາແທ້ໆ
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_message = 'API returned invalid JSON.';
        $raw_response_info = ' | Raw Response: ' . $response_body;
        return ['success' => false, 'message' => $error_message . $raw_response_info, 'data' => null];
    }
    
    // ຖ້າ decode ສຳເລັດ, ຖືວ່າການເຊື່ອມຕໍ່ API ສຳເລັດ.
    // ສົ່ງข้อมูลทั้งหมดกลับไปให้ไฟล์ที่เรียกใช้เป็นตัวตัดสินใจต่อ
    // ຖ້າ API ສົ່ງ error message ມາ, ກໍໃຫ້ສົ່ງ message ນັ້ນกลับไปด้วย
    $is_successful = isset($decoded['status']) && $decoded['status'] !== 'gagal';

    return [
        'success' => true, // ການເອີ້ນ API ສຳເລັດ
        'data' => $decoded  // ສົ່ງข้อมูลทั้งหมดกลับไป
    ];
    // ***** END: LOGIC ໃໝ່ *****
}
?>
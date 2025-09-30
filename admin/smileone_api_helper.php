<?php
// File: admin/smileone_api_helper.php (Final Solution - Handles multiple response types)

if (!function_exists('callSmileOneAPI')) {
    function callSmileOneAPI($supplier, $endpoint, $params = []) {
        if (!$supplier || empty($supplier['api_url']) || empty($supplier['uid']) || empty($supplier['email']) || empty($supplier['api_key'])) {
            return ['success' => false, 'message' => 'Supplier data is incomplete.'];
        }

        $api_url = rtrim($supplier['api_url'], '/');
        $uid = $supplier['uid'];
        $key = $supplier['api_key'];
        $email = $supplier['email'];

        $request_params = $params;
        $request_params['uid'] = $uid;
        $request_params['email'] = $email;
        $request_params['time'] = time();

        if ($endpoint === 'product') {
            // Endpoint 'product' ບໍ່ຕ້ອງການ parameter 'product'
            unset($request_params['product']);
        }

        ksort($request_params);

        $string_to_sign = "";
        foreach ($request_params as $k => $v) {
            $string_to_sign .= $k . '=' . $v . '&';
        }
        $str_final = $string_to_sign . $key;
        $sign = md5(md5($str_final));
        $request_params['sign'] = $sign;
        
        $full_api_url = $api_url . '/smilecoin/api/' . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $full_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request_params));
        curl_setopt($ch, CURLOPT_USERAGENT, 'PaoXay/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response_body = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return ['success' => false, 'message' => 'cURL Error: ' . $curl_error, 'raw_response' => $curl_error];
        }
        
        $decoded_response = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Invalid JSON response from API.', 'raw_response' => $response_body];
        }

        // --- START OF FINAL FIX ---
        // ກວດສອບຮູບແບບການຕອບกลับ
        // 1. ກໍລະນີສຳເລັດຂອງ endpoint 'product' (ຈະເປັນ array ໂດຍກົງ)
        if ($endpoint === 'product' && is_array($decoded_response) && !isset($decoded_response['status'])) {
            return ['success' => true, 'data' => $decoded_response];
        }
        
        // 2. ກໍລະນີສຳເລັດຂອງ endpoint ອື່ນໆ (ຈະມີ status 200 ຫຼື 201)
        if (isset($decoded_response['status']) && in_array($decoded_response['status'], [200, 201])) {
            return ['success' => true, 'data' => $decoded_response];
        }
        // --- END OF FINAL FIX ---

        // 3. ກໍລະນີ Error
        $error_from_api = $decoded_response['message'] ?? 'API did not return a valid success response.';
        return ['success' => false, 'message' => $error_from_api, 'raw_response' => $response_body];
    }
}
?>
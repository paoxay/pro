<?php
// File: admin/api_handler_smileone.php

function generate_smileone_sign($params, $api_key) {
    ksort($params); // Sort the parameters by key
    $sign_string = '';
    foreach ($params as $k => $v) {
        $sign_string .= $k . '=' . $v . '&';
    }
    $sign_string .= $api_key;
    return md5(md5($sign_string));
}

function call_smileone_topup_api($supplier, $user_id, $zone_id, $product_id) {
    $api_base_url = rtrim($supplier['api_base_url'], '/');
    $uid = $supplier['member_code'];
    $api_key = $supplier['api_secret_key'];
    
    // Smile One requires 'email' but it can be a placeholder from your config
    $email = 'placeholder@email.com'; 
    $product_name = 'mobilelegends';

    $params = [
        'uid'       => $uid,
        'email'     => $email,
        'product'   => $product_name,
        'userid'    => $user_id,
        'zoneid'    => $zone_id,
        'productid' => $product_id,
        'time'      => time()
    ];

    $params['sign'] = generate_smileone_sign($params, $api_key);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_base_url . '/createorder');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 seconds timeout

    $response_body = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return ['success' => false, 'message' => 'cURL Error: ' . $curl_error];
    }
    
    $result = json_decode($response_body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'Invalid JSON response from API.'];
    }

    if (isset($result['status']) && $result['status'] == 200) {
        return ['success' => true, 'order_id' => $result['order_id'], 'raw_response' => $result];
    } else {
        return ['success' => false, 'message' => $result['message'] ?? 'API call failed.', 'raw_response' => $result];
    }
}
?>
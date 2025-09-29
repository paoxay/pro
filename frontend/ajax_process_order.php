<?php
// File: /frontend/ajax_process_order.php (Corrected Success Logic)
session_start();
header('Content-Type: application/json');

require_once 'db.php';
require_once '../admin/api_helper.php';

if (!isset($_SESSION['member_loggedin']) || $_SESSION['member_loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'ກະລຸນາເຂົ້າສູ່ລະບົບກ່ອນ.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$member_id = $_SESSION['member_id'];
$package_id = $input['package_id'] ?? 0;
$custom_fields_data = $input['fields'] ?? [];

// ... (ໂຄດສ່ວນ Validation ແລະ ດຶງຂໍ້ມູນ package/wallet ແມ່ນຄືເກົ່າ) ...
// This part is condensed for brevity, use the full code from your previous working version.
$stmt_pkg_full = $conn->prepare("SELECT p.price, p.cost_price, p.name AS package_name, p.api_product_code, g.api_supplier_id, s.api_base_url, s.member_code, s.api_secret_key FROM game_packages p JOIN games g ON p.game_id = g.id JOIN api_suppliers s ON g.api_supplier_id = s.id WHERE p.id = ?");
$stmt_pkg_full->bind_param("i", $package_id);
$stmt_pkg_full->execute();
$pkg_result = $stmt_pkg_full->get_result();
if ($pkg_result->num_rows === 0) { echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບແພັກເກັດ.']); exit; }
$package_info = $pkg_result->fetch_assoc();
$package_price = (float)$package_info['price'];
$stmt_wallet = $conn->prepare("SELECT wallet_balance FROM members WHERE id = ?");
$stmt_wallet->bind_param("i", $member_id);
$stmt_wallet->execute();
$wallet_balance = (float)$stmt_wallet->get_result()->fetch_assoc()['wallet_balance'];
if ($wallet_balance < $package_price) { echo json_encode(['success' => false, 'message' => 'ຍອດເງິນບໍ່ພຽງພໍ.']); exit; }
// End of condensed part.

$conn->begin_transaction();
try {
    $order_code = 'PX' . date('YmdHis') . rand(100, 999);
    $balance_before = $wallet_balance;
    $balance_after = $wallet_balance - $package_price;
    $profit = $package_price - (float)$package_info['cost_price'];
    $game_user_info_json = json_encode($custom_fields_data, JSON_UNESCAPED_UNICODE);

    $user_game_id_values = array_values($custom_fields_data);
    $api_params = [
        'produk'    => $package_info['api_product_code'],
        'tujuan'    => $user_game_id_values[0] ?? '',
        'server_id' => $user_game_id_values[1] ?? ''
    ];

    // ເອີ້ນໃຊ້ API v1
    $api_result = callV1API($package_info, 'transaksi', $order_code, $api_params);
    
    // ***** START: LOGIC ໃໝ່ໃນການກວດສອບຜົນລັບ *****
    // ຖ້າ API ສົ່ງຄ່າ data ກັບມາ ແລະ ມີ status ເປັນ pending, ຖືວ່າການส่งคำสั่งสำเร็จ
    if ($api_result['success'] && isset($api_result['data']['status']) && $api_result['data']['status'] == 'pending') {
        
        // ຕັດເງິນອອກຈາກ Wallet
        $stmt_update_wallet = $conn->prepare("UPDATE members SET wallet_balance = ? WHERE id = ?");
        $stmt_update_wallet->bind_param("di", $balance_after, $member_id);
        $stmt_update_wallet->execute();
        
        // ບັນທຶກ Order ເປັນ processing
        $stmt_order = $conn->prepare("INSERT INTO orders (member_id, order_code, package_id, game_user_info, amount, profit, balance_before, balance_after, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'processing')");
        $stmt_order->bind_param("isisddds", $member_id, $order_code, $package_id, $game_user_info_json, $package_price, $profit, $balance_before, $balance_after);
        $stmt_order->execute();

        // ບັນທຶກ Transaction
        $purchase_amount = -$package_price;
        $notes = "Order #" . $order_code . " - ຊື້ '" . $package_info['package_name'] . "'";
        $stmt_trans = $conn->prepare("INSERT INTO wallet_transactions (member_id, amount, transaction_type, notes) VALUES (?, ?, 'purchase', ?)");
        $stmt_trans->bind_param("ids", $member_id, $purchase_amount, $notes);
        $stmt_trans->execute();

    } else {
        // ຖ້າ API ບໍ່ສຳເລັດ ຫຼື ຕອບกลับมาແປກໆ, ໃຫ້ຍົກເລີກ
        throw new Exception($api_result['message'] ?? 'API order failed');
    }
    // ***** END: LOGIC ໃໝ່ *****

    $conn->commit();
    // ປ່ຽນຂໍ້ຄວາມ Alert ຕາມທີ່ເຈົ້າຕ້ອງການ
    echo json_encode(['success' => true, 'message' => 'ທ່ານໄດ້ສັ່ງຊື້ສຳເລັດ, ກະລຸນາກວດສອບໃນປະຫວັດ.']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Order failed for member $member_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
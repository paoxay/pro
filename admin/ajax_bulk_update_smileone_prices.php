<?php
// File: admin/ajax_bulk_update_smileone_prices.php
require_once 'db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$game_id = (int)($input['game_id'] ?? 0);
$markup = (float)($input['markup'] ?? 15);

if ($game_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Game ID.']);
    exit;
}

// Find exchange rate for this game
$stmt_game = $conn->prepare("SELECT s.exchange_rate FROM smileone_games g JOIN smileone_suppliers s ON g.smileone_supplier_id = s.id WHERE g.id = ?");
$stmt_game->bind_param("i", $game_id);
$stmt_game->execute();
$game_data = $stmt_game->get_result()->fetch_assoc();
if (!$game_data) {
    echo json_encode(['success' => false, 'message' => 'Cannot find game or supplier.']);
    exit;
}
$exchange_rate = (float)$game_data['exchange_rate'];

// Get all packages for this game
$packages_result = $conn->query("SELECT id, api_price FROM smileone_packages WHERE game_id = $game_id");

$conn->begin_transaction();
try {
    $stmt_update = $conn->prepare("UPDATE smileone_packages SET cost_price = ?, selling_price = ? WHERE id = ?");
    while ($pkg = $packages_result->fetch_assoc()) {
        $cost_price = (float)$pkg['api_price'] * $exchange_rate;
        $selling_price = ceil($cost_price * (1 + ($markup / 100)));
        $stmt_update->bind_param("ddi", $cost_price, $selling_price, $pkg['id']);
        $stmt_update->execute();
    }
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt_update->close();
$conn->close();
?>
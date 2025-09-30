<?php
// File: admin/ajax_get_dashboard_stats.php (Upgraded to combine APIs)
require_once 'db_connect.php';
header('Content-Type: application/json');

// ຮັບຄ່າວັນທີ
$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

if (!$startDate || !$endDate) {
    echo json_encode(['success' => false, 'message' => 'Invalid date range.']);
    exit;
}

$startDateTime = $startDate . ' 00:00:00';
$endDateTime = $endDate . ' 23:59:59';

// --- 1. ຄຳນວນຍອດຂາຍ, ກຳໄລ, ແລະ ອໍເດີ້ລວມ ---
$sql_summary = "
    SELECT 
        COUNT(id) AS total_orders, 
        COALESCE(SUM(amount), 0) AS total_sales, 
        COALESCE(SUM(profit), 0) AS total_profit 
    FROM (
        SELECT id, amount, profit, created_at, status FROM orders
        UNION ALL
        SELECT id, amount, profit, created_at, status FROM smileone_orders
    ) AS combined_orders
    WHERE status = 'completed' AND created_at BETWEEN ? AND ?
";
$stmt_summary = $conn->prepare($sql_summary);
$stmt_summary->bind_param("ss", $startDateTime, $endDateTime);
$stmt_summary->execute();
$summary = $stmt_summary->get_result()->fetch_assoc();
$stmt_summary->close();

// --- 2. ຄຳນວນຕົ້ນທຶນຂອງ TOKO API ---
$sql_toko_cost = "
    SELECT COALESCE(SUM(p.cost_price), 0) AS total_cost_toko
    FROM orders o
    JOIN game_packages p ON o.package_id = p.id
    WHERE o.status = 'completed' AND o.created_at BETWEEN ? AND ?
";
$stmt_toko = $conn->prepare($sql_toko_cost);
$stmt_toko->bind_param("ss", $startDateTime, $endDateTime);
$stmt_toko->execute();
$toko_cost = $stmt_toko->get_result()->fetch_assoc();
$stmt_toko->close();

// --- 3. ຄຳນວນຕົ້ນທຶນ ແລະ ยอด Coins ຂອງ Smile One API ---
$sql_smileone_cost = "
    SELECT 
        COALESCE(SUM(p.cost_price), 0) AS total_cost_smileone,
        COALESCE(SUM(p.api_price), 0) AS total_coins_smileone
    FROM smileone_orders o
    JOIN smileone_packages p ON o.package_id = p.id
    WHERE o.status = 'completed' AND o.created_at BETWEEN ? AND ?
";
$stmt_smileone = $conn->prepare($sql_smileone_cost);
$stmt_smileone->bind_param("ss", $startDateTime, $endDateTime);
$stmt_smileone->execute();
$smileone_cost = $stmt_smileone->get_result()->fetch_assoc();
$stmt_smileone->close();

// --- ສົ່ງຂໍ້ມູນທັງໝົດກັບໄປເປັນ JSON ---
echo json_encode([
    'success' => true,
    'total_orders' => (int)$summary['total_orders'],
    'total_sales' => (float)$summary['total_sales'],
    'total_profit' => (float)$summary['total_profit'],
    'total_cost_toko' => (float)$toko_cost['total_cost_toko'],
    'total_cost_smileone' => (float)$smileone_cost['total_cost_smileone'],
    'total_coins_smileone' => (float)$smileone_cost['total_coins_smileone']
]);

$conn->close();
?>
<?php
// File: admin/ajax_get_dashboard_stats.php
require_once 'db_connect.php';
header('Content-Type: application/json');

// รับค่าวันที่เริ่มต้นและสิ้นสุด
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// ตรวจสอบว่าวันที่ถูกต้องหรือไม่
if (!$startDate || !$endDate) {
    echo json_encode(['success' => false, 'message' => 'Invalid date range.']);
    exit;
}

// เพิ่มเวลาเพื่อให้ครอบคลุมทั้งวันของ endDate
$startDateTime = $startDate . ' 00:00:00';
$endDateTime = $endDate . ' 23:59:59';

$sql = "SELECT 
            COUNT(id) AS total_orders, 
            COALESCE(SUM(amount), 0) AS total_sales, 
            COALESCE(SUM(profit), 0) AS total_profit 
        FROM orders 
        WHERE status = 'completed' AND created_at BETWEEN ? AND ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $startDateTime, $endDateTime);
$stmt->execute();
$result = $stmt->get_result();
$summary = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'total_orders' => (int)$summary['total_orders'],
    'total_sales' => (float)$summary['total_sales'],
    'total_profit' => (float)$summary['total_profit']
]);

$stmt->close();
$conn->close();
?>
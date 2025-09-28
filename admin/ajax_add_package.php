<?php
// File: admin/ajax_add_package.php
require_once 'db_connect.php';

// ตั้งค่า header ให้ตอบกลับเป็น JSON
header('Content-Type: application/json');

// รับข้อมูลที่ส่งมาแบบ JSON
$input = json_decode(file_get_contents('php://input'), true);

$game_id = $input['game_id'] ?? 0;
$name = trim($input['name'] ?? '');
$price = $input['price'] ?? 0;
$cost_price = $input['cost_price'] ?? 0;

// ตรวจสอบข้อมูลเบื้องต้น
if ($game_id > 0 && !empty($name) && is_numeric($price) && is_numeric($cost_price)) {
    try {
        $stmt = $conn->prepare("INSERT INTO game_packages (game_id, name, price, cost_price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isdd", $game_id, $name, $price, $cost_price);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            echo json_encode(['success' => true, 'new_id' => $new_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database insert failed.']);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
}

$conn->close();
?>
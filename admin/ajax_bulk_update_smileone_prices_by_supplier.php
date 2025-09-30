<?php
// File: admin/ajax_bulk_update_smileone_prices_by_supplier.php (Complete Version)
require_once 'db_connect.php';
require_once 'auth_check.php'; // ເພີ່ມການກວດສອບການລັອກອິນເພື່ອຄວາມປອດໄພ

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$supplier_id = (int)($input['supplier_id'] ?? 0);

if ($supplier_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Supplier ID.']);
    exit;
}

// 1. ດຶງອັດຕາແລກປ່ຽນຫຼ້າສຸດຂອງ Supplier ນີ້
$stmt_supplier = $conn->prepare("SELECT exchange_rate FROM smileone_suppliers WHERE id = ?");
$stmt_supplier->bind_param("i", $supplier_id);
$stmt_supplier->execute();
$supplier_data = $stmt_supplier->get_result()->fetch_assoc();

if (!$supplier_data) {
    echo json_encode(['success' => false, 'message' => 'Cannot find supplier.']);
    $stmt_supplier->close();
    $conn->close();
    exit;
}
$exchange_rate = (float)$supplier_data['exchange_rate'];
$stmt_supplier->close();

// 2. ດຶງລາຍຊື່ແພັກເກັດທັງໝົດທີ່ຂຶ້ນກັບ Supplier ນີ້ ພ້ອມກັບ % markup ຂອງມັນ
$sql_packages = "SELECT p.id, p.api_price, p.markup_percentage
                 FROM smileone_packages p
                 JOIN smileone_games g ON p.game_id = g.id
                 WHERE g.smileone_supplier_id = ?";
$stmt_packages = $conn->prepare($sql_packages);
$stmt_packages->bind_param("i", $supplier_id);
$stmt_packages->execute();
$packages_result = $stmt_packages->get_result();

if ($packages_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'No packages found for this supplier to update.']);
    $stmt_packages->close();
    $conn->close();
    exit;
}

// 3. ເລີ່ມຂະບວນການອັບເດດข้อมูล
$conn->begin_transaction();
try {
    $stmt_update = $conn->prepare("UPDATE smileone_packages SET cost_price = ?, selling_price = ? WHERE id = ?");
    $updated_rows = 0;
    while ($pkg = $packages_result->fetch_assoc()) {
        $cost_price = (float)$pkg['api_price'] * $exchange_rate;
        $markup = (float)$pkg['markup_percentage']; // ໃຊ້ % markup ຂອງແຕ່ລະແພັກເກັດ
        $selling_price = ceil($cost_price * (1 + ($markup / 100)));
        
        $pkg_id = $pkg['id'];
        $stmt_update->bind_param("ddi", $cost_price, $selling_price, $pkg_id);
        $stmt_update->execute();
        $updated_rows++;
    }
    $conn->commit();
    echo json_encode(['success' => true, 'updated_rows' => $updated_rows]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt_packages->close();
$stmt_update->close();
$conn->close();
?>
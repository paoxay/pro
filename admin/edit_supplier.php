<?php
// File: admin/edit_supplier.php

require_once 'auth_check.php';
require_once 'db_connect.php';
require_once 'price_updater_function.php'; // ເອີ້ນໃຊ້ Function ທີ່ສ້າງໃໝ່

$supplier_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($supplier_id <= 0) {
    header("Location: manage_suppliers.php");
    exit;
}

// --- FORM PROCESSING ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        $name = trim($_POST['name']);
        $api_base_url = trim($_POST['api_base_url']);
        $member_code = trim($_POST['member_code']);
        $signature = trim($_POST['signature']);
        $exchange_rate = (float)$_POST['exchange_rate'];

        $stmt = $conn->prepare("UPDATE api_suppliers SET name = ?, api_base_url = ?, member_code = ?, signature = ?, exchange_rate = ? WHERE id = ?");
        $stmt->bind_param("ssssdi", $name, $api_base_url, $member_code, $signature, $exchange_rate, $supplier_id);
        $stmt->execute();
        $stmt->close();
        
        // --- AUTO UPDATE PRICES ---
        // ຫຼັງຈາກບັນທຶກຂໍ້ມູນ Supplier, ໃຫ້ສັ່ງອັບເດດລາຄາທັນທີ
        $update_result = updatePricesForSupplier($supplier_id, $conn);
        
        if (!$update_result['success']) {
            throw new Exception("Update supplier info successful, but failed to auto-update prices: " . $update_result['message']);
        }

        $conn->commit();
        $_SESSION['success_message'] = "ອັບເດດຂໍ້ມູນ Supplier ແລະ ລາຄາ " . $update_result['updated_packages'] . " ລາຍການ ສຳເລັດ!";
        header("Location: manage_suppliers.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// --- FETCH DATA FOR FORM ---
$stmt_get = $conn->prepare("SELECT * FROM api_suppliers WHERE id = ?");
$stmt_get->bind_param("i", $supplier_id);
$stmt_get->execute();
$result = $stmt_get->get_result();
if ($result->num_rows === 0) { die("Supplier not found."); }
$supplier = $result->fetch_assoc();
$stmt_get->close();

require_once 'admin_header.php';
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">ແກ້ໄຂ Supplier: <?php echo htmlspecialchars($supplier['name']); ?></h1>
    <?php if (isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="edit_supplier.php?id=<?php echo $supplier_id; ?>" method="POST">
                <div class="mb-3">
                    <label class="form-label">ຊື່ Supplier</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($supplier['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">API Base URL</label>
                    <input type="text" name="api_base_url" class="form-control" value="<?php echo htmlspecialchars($supplier['api_base_url']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">ອັດຕາແລກປ່ຽນ (ເຊັ່ນ: 1.35)</label>
                    <input type="number" name="exchange_rate" class="form-control" value="<?php echo htmlspecialchars($supplier['exchange_rate']); ?>" step="0.0001" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Member Code</label>
                    <input type="text" name="member_code" class="form-control" value="<?php echo htmlspecialchars($supplier['member_code']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Signature</label>
                    <input type="text" name="signature" class="form-control" value="<?php echo htmlspecialchars($supplier['signature']); ?>">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> ບັນທຶກ ແລະ ອັບເດດລາຄາ
                </button>
                <a href="manage_suppliers.php" class="btn btn-secondary">ຍົກເລີກ</a>
            </form>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// File: admin/edit_supplier.php
require_once 'auth_check.php';
require_once 'db_connect.php';

$supplier_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($supplier_id <= 0) {
    header("Location: manage_suppliers.php");
    exit;
}

// Handle form submission for editing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_supplier'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $api_base_url = trim($_POST['api_base_url']);
    $member_code = trim($_POST['member_code']);
    $signature = trim($_POST['signature']);
    $api_secret_key = $_POST['api_secret_key'];
    $exchange_rate = (float)$_POST['exchange_rate'];

    $stmt = $conn->prepare("UPDATE api_suppliers SET name=?, api_base_url=?, member_code=?, signature=?, api_secret_key=?, exchange_rate=? WHERE id=?");
    $stmt->bind_param("sssssdi", $name, $api_base_url, $member_code, $signature, $api_secret_key, $exchange_rate, $id);
    
    if ($stmt->execute()) {
        header("Location: manage_suppliers.php");
        exit();
    }
    $stmt->close();
}

// Fetch existing supplier data
$stmt_get = $conn->prepare("SELECT * FROM api_suppliers WHERE id = ?");
$stmt_get->bind_param("i", $supplier_id);
$stmt_get->execute();
$result = $stmt_get->get_result();
if ($result->num_rows === 0) {
    header("Location: manage_suppliers.php");
    exit;
}
$supplier = $result->fetch_assoc();
$stmt_get->close();

require_once 'admin_header.php';
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">ແກ້ໄຂ Supplier: <?php echo htmlspecialchars($supplier['name']); ?></h1>
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="edit_supplier.php?id=<?php echo $supplier_id; ?>" method="POST">
                <input type="hidden" name="id" value="<?php echo $supplier['id']; ?>">
                <div class="mb-3"><label class="form-label">ຊື່ Supplier</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($supplier['name']); ?>" required></div>
                <div class="mb-3"><label class="form-label">API Base URL</label><input type="text" name="api_base_url" class="form-control" value="<?php echo htmlspecialchars($supplier['api_base_url']); ?>" required></div>
                <div class="mb-3"><label class="form-label">Member Code</label><input type="text" name="member_code" class="form-control" value="<?php echo htmlspecialchars($supplier['member_code']); ?>"></div>
                <div class="mb-3"><label class="form-label">Signature (MD5 Key)</label><input type="text" name="signature" class="form-control" value="<?php echo htmlspecialchars($supplier['signature']); ?>"></div>
                <div class="mb-3"><label class="form-label">Secret Key (ສຳລັບ API v1)</label><input type="password" name="api_secret_key" class="form-control" value="<?php echo htmlspecialchars($supplier['api_secret_key']); ?>"></div>
                <div class="mb-3"><label class="form-label">ອັດຕາແລກປ່ຽນ (Exchange Rate)</label><input type="number" name="exchange_rate" class="form-control" value="<?php echo htmlspecialchars($supplier['exchange_rate']); ?>" step="0.0001" required></div>
                <button type="submit" name="update_supplier" class="btn btn-primary">ອັບເດດຂໍ້ມູນ</button>
                <a href="manage_suppliers.php" class="btn btn-secondary">ຍົກເລີກ</a>
            </form>
        </div>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
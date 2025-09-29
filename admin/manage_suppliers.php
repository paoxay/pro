<?php
// File: admin/manage_suppliers.php (Upgraded with Exchange Rate)
require_once 'auth_check.php';
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_supplier'])) {
    $name = trim($_POST['name']);
    $api_base_url = trim($_POST['api_base_url']);
    $member_code = trim($_POST['member_code']);
    $signature = trim($_POST['signature']);
    $api_secret_key = trim($_POST['api_secret_key']);
    $exchange_rate = (float)($_POST['exchange_rate'] ?? 1.0);

    if (!empty($name) && !empty($api_base_url)) {
        $stmt = $conn->prepare("INSERT INTO api_suppliers (name, api_base_url, member_code, signature, api_secret_key, exchange_rate) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssd", $name, $api_base_url, $member_code, $signature, $api_secret_key, $exchange_rate);
        
        if ($stmt->execute()) {
            header("Location: manage_suppliers.php");
            exit();
        }
        $stmt->close();
    }
}

$suppliers_result = $conn->query("SELECT id, name, api_base_url, member_code, exchange_rate FROM api_suppliers ORDER BY name ASC");

require_once 'admin_header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">ຈັດການ API Suppliers</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">ລາຍການ Supplier ທີ່ມີໃນລະບົບ</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>ຊື່ Supplier</th>
                            <th>API Base URL</th>
                            <th>Member Code</th>
                            <th>ອັດຕາແລກປ່ຽນ</th>
                            <th class="text-center">ເຄື່ອງມື</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($suppliers_result && $suppliers_result->num_rows > 0): ?>
                            <?php while($supplier = $suppliers_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $supplier['id']; ?></td>
                                    <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['api_base_url']); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['member_code']); ?></td>
                                    <td class="fw-bold"><?php echo number_format($supplier['exchange_rate'], 4); ?></td>
                                    <td class="text-center">
                                        <a href="edit_supplier.php?id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-warning">ແກ້ໄຂ</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">ຍັງບໍ່ມີຂໍ້ມູນ Supplier.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">ເພີ່ມ Supplier ໃໝ່</h6></div>
        <div class="card-body">
            <form action="manage_suppliers.php" method="POST">
                <div class="mb-3"><label class="form-label">ຊື່ Supplier</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">API Base URL</label><input type="text" name="api_base_url" class="form-control" placeholder="https://api.tokovoucher.net" required></div>
                <div class="mb-3"><label class="form-label">Member Code</label><input type="text" name="member_code" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Signature (MD5 Key)</label><input type="text" name="signature" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Secret Key (ສຳລັບ API v1)</label><input type="password" name="api_secret_key" class="form-control"></div>
                <div class="mb-3"><label class="form-label">ອັດຕາແລກປ່ຽນ (Exchange Rate)</label><input type="number" name="exchange_rate" class="form-control" value="1.35" step="0.0001" required></div>
                <button type="submit" name="add_supplier" class="btn btn-success"><i class="fas fa-plus"></i> ເພີ່ມ Supplier</button>
            </form>
        </div>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
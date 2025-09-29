<?php
// File: admin/manage_suppliers.php (Updated with "Update Prices" button)

require_once 'auth_check.php';
require_once 'db_connect.php';

// Handle form submission for adding a new supplier
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_supplier'])) {
    $name = trim($_POST['name']);
    $api_base_url = trim($_POST['api_base_url']);
    $member_code = trim($_POST['member_code']);
    $signature = trim($_POST['signature']);
    $api_secret_key = trim($_POST['api_secret_key']);
    $exchange_rate = $_POST['exchange_rate'] ?? 1.0; // Get exchange rate from form

    if (!empty($name) && !empty($api_base_url)) {
        // Updated INSERT to include exchange_rate
        $stmt = $conn->prepare("INSERT INTO api_suppliers (name, api_base_url, member_code, signature, api_secret_key, exchange_rate) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssd", $name, $api_base_url, $member_code, $signature, $api_secret_key, $exchange_rate);
        
        if ($stmt->execute()) {
            header("Location: manage_suppliers.php");
            exit();
        }
        $stmt->close();
    }
}

// Fetch all existing suppliers to display in the table
$suppliers_result = $conn->query("SELECT * FROM api_suppliers ORDER BY name ASC");


require_once 'admin_header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">ຈັດການ API Suppliers</h1>
    <div id="alert-container"></div>

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
                            <th class="text-center" style="width: 250px;">ເຄື່ອງມື</th>
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
                                    <td class="fw-bold"><?php echo htmlspecialchars($supplier['exchange_rate']); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-info update-prices-btn" data-supplier-id="<?php echo $supplier['id']; ?>" data-supplier-name="<?php echo htmlspecialchars($supplier['name']); ?>">
                                            <i class="fas fa-sync-alt"></i> ອັບເດດລາຄາ
                                        </button>
                                        <a href="edit_supplier.php?id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-warning">ແກ້ໄຂ</a>
                                        <a href="delete_supplier.php?id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບ?');">ລຶບ</a>
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
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">ເພີ່ມ Supplier ໃໝ່</h6>
        </div>
        <div class="card-body">
            <form action="manage_suppliers.php" method="POST">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">ຊື່ Supplier</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">API Base URL</label>
                        <input type="text" name="api_base_url" class="form-control" placeholder="https://tokovoucher.net" required>
                    </div>
                     <div class="col-md-4 mb-3">
                        <label class="form-label">ອັດຕາແລກປ່ຽນ (ເຊັ່ນ: 1.35)</label>
                        <input type="number" name="exchange_rate" class="form-control" step="0.0001" value="1.35" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Member Code</label>
                        <input type="text" name="member_code" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Signature</label>
                        <input type="text" name="signature" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Secret Key</label>
                        <input type="password" name="api_secret_key" class="form-control">
                    </div>
                </div>
                <button type="submit" name="add_supplier" class="btn btn-success">
                    <i class="fas fa-plus"></i> ເພີ່ມ Supplier
                </button>
            </form>
        </div>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('update-prices-btn')) {
        const button = e.target;
        const supplierId = button.dataset.supplierId;
        const supplierName = button.dataset.supplierName;
        
        if (confirm(`ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການອັບເດດລາຄາທັງໝົດຂອງ '${supplierName}' ຕາມອັດຕາແລກປ່ຽນລ່າສຸດ?`)) {
            button.disabled = true;
            button.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ກำลังอัปเดต...`;

            fetch('ajax_update_prices.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ supplier_id: supplierId })
            })
            .then(response => response.json())
            .then(result => {
                const alertContainer = document.getElementById('alert-container');
                if (result.success) {
                    alertContainer.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>ສຳເລັດ!</strong> ອັບເດດລາຄາສຳລັບ ${result.updated_packages} ແພັກເກັດຮຽບຮ້ອຍ.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
                    // Optional: reload the page to see changes if prices are shown elsewhere
                    // location.reload(); 
                } else {
                    alertContainer.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>ຜິດພາດ!</strong> ${result.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
                }
            })
            .catch(error => console.error('Error:', error))
            .finally(() => {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-sync-alt"></i> ອັບເດດລາຄາ';
            });
        }
    }
});
</script>
</body>
</html>
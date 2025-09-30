<?php
// File: admin/manage_smileone_suppliers.php (Upgraded with Bulk Price Update)
require_once 'auth_check.php';
require_once 'db_connect.php';

// ຈັດການການເພີ່ມ/ແກ້ໄຂ supplier
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_supplier'])) {
    $name = trim($_POST['name']);
    $api_url = trim($_POST['api_url']);
    $uid = trim($_POST['uid']);
    $email = trim($_POST['email']);
    $api_key = trim($_POST['api_key']);
    $exchange_rate = (float)($_POST['exchange_rate'] ?? 1.0);
    $supplier_id = (int)($_POST['supplier_id'] ?? 0);

    if (!empty($name) && !empty($api_url) && !empty($uid) && !empty($email) && !empty($api_key)) {
        if ($supplier_id > 0) { // Update
            $stmt = $conn->prepare("UPDATE smileone_suppliers SET name=?, api_url=?, uid=?, email=?, api_key=?, exchange_rate=? WHERE id=?");
            $stmt->bind_param("sssssdi", $name, $api_url, $uid, $email, $api_key, $exchange_rate, $supplier_id);
        } else { // Insert
            $stmt = $conn->prepare("INSERT INTO smileone_suppliers (name, api_url, uid, email, api_key, exchange_rate) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssd", $name, $api_url, $uid, $email, $api_key, $exchange_rate);
        }
        if ($stmt->execute()) {
            header("Location: manage_smileone_suppliers.php");
            exit();
        }
        $stmt->close();
    }
}

$suppliers_result = $conn->query("SELECT * FROM smileone_suppliers ORDER BY name ASC");
require_once 'admin_header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">ຈັດການ API Smile One</h1>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">ລາຍການ Smile One API</h6>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#supplierModal" data-id="0"><i class="fas fa-plus"></i> ເພີ່ມ API ໃໝ່</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr><th>ID</th><th>ຊື່</th><th>API URL</th><th>UID</th><th>Email</th><th>ອັດຕາແລກປ່ຽນ</th><th class="text-center">ເຄື່ອງມື</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($suppliers_result && $suppliers_result->num_rows > 0): ?>
                            <?php while($s = $suppliers_result->fetch_assoc()): ?>
                                <tr id="supplier-<?php echo $s['id']; ?>" data-details='<?php echo json_encode($s, JSON_HEX_APOS); ?>'>
                                    <td><?php echo $s['id']; ?></td>
                                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                                    <td><?php echo htmlspecialchars($s['api_url']); ?></td>
                                    <td><?php echo htmlspecialchars($s['uid']); ?></td>
                                    <td><?php echo htmlspecialchars($s['email']); ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($s['exchange_rate']); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-info update-prices-btn" data-supplier-id="<?php echo $s['id']; ?>" data-supplier-name="<?php echo htmlspecialchars($s['name']); ?>">
                                            <i class="fas fa-sync"></i> ອັບເດດລາຄາ
                                        </button>
                                        <button class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#supplierModal" data-id="<?php echo $s['id']; ?>">
                                            <i class="fas fa-edit"></i> ແກ້ໄຂ
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="manage_smileone_suppliers.php" method="POST">
                <div class="modal-header"><h5 class="modal-title" id="modalTitle">ເພີ່ມ API ໃໝ່</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="supplier_id" id="supplier_id" value="0">
                    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">ຊື່</label><input type="text" id="name" name="name" class="form-control" required></div><div class="col-md-6 mb-3"><label class="form-label">API URL</label><input type="text" id="api_url" name="api_url" class="form-control" required></div></div>
                    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">UID</label><input type="text" id="uid" name="uid" class="form-control" required></div><div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" id="email" name="email" class="form-control" required></div></div>
                    <div class="row"><div class="col-md-8 mb-3"><label class="form-label">API Key (Signature Key)</label><input type="text" id="api_key" name="api_key" class="form-control" required></div><div class="col-md-4 mb-3"><label class="form-label">ອັດຕາແລກປ່ຽນ (Coins to LAK)</label><input type="number" id="exchange_rate" name="exchange_rate" class="form-control" step="0.0001" value="1.0000" required></div></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button><button type="submit" name="save_supplier" class="btn btn-primary">ບັນທຶກ</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="priceUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulkUpdateForm">
                <div class="modal-header"><h5 class="modal-title">ຢືນຢັນການອັບເດດລາຄາ</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="supplier_id" id="modal_supplier_id">
                    <p>ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການອັບເດດລາຄາຂາຍຂອງທຸກເກມໃນ: <strong id="modal_supplier_name"></strong>?</p>
                    <p class="text-muted small">ລະບົບຈະໃຊ້ "ອັດຕາແລກປ່ຽນ" ຫຼ້າສຸດ ແລະ "% Markup" ທີ່ທ່ານຕັ້ງໄວ້ຂອງແຕ່ລະແພັກເກັດ ເພື່ອຄຳນວນລາຄາໃໝ່ທັງໝົດ.</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button><button type="submit" class="btn btn-primary">ຢືນຢັນ</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const supplierModal = new bootstrap.Modal(document.getElementById('supplierModal'));
    const priceUpdateModal = new bootstrap.Modal(document.getElementById('priceUpdateModal'));

    document.addEventListener('click', function(e) {
        // Handle Edit Button
        if (e.target.matches('.edit-btn')) {
            const button = e.target;
            const supplierId = button.dataset.id;
            const form = document.querySelector('#supplierModal form');
            const modalTitle = document.querySelector('#supplierModal .modal-title');

            if (supplierId && supplierId != '0') {
                modalTitle.textContent = 'ແກ້ໄຂ API ID: ' + supplierId;
                const row = document.getElementById('supplier-' + supplierId);
                const details = JSON.parse(row.getAttribute('data-details'));
                form.querySelector('#supplier_id').value = details.id;
                form.querySelector('#name').value = details.name;
                form.querySelector('#api_url').value = details.api_url;
                form.querySelector('#uid').value = details.uid;
                form.querySelector('#email').value = details.email;
                form.querySelector('#api_key').value = details.api_key;
                form.querySelector('#exchange_rate').value = details.exchange_rate;
            }
        }
        
        // Handle Add New Button
        if (e.target.matches('[data-id="0"]')) {
             const form = document.querySelector('#supplierModal form');
             const modalTitle = document.querySelector('#supplierModal .modal-title');
             modalTitle.textContent = 'ເພີ່ມ API ໃໝ່';
             form.reset();
             form.querySelector('#supplier_id').value = '0';
        }

        // Handle Update Prices Button
        if (e.target.matches('.update-prices-btn')) {
            document.getElementById('modal_supplier_id').value = e.target.dataset.supplierId;
            document.getElementById('modal_supplier_name').innerText = e.target.dataset.supplierName;
            priceUpdateModal.show();
        }
    });

    document.getElementById('bulkUpdateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const supplierId = this.supplier_id.value;
        
        if (confirm(`ແນ່ໃຈບໍ່ວ່າຕ້ອງການອັບເດດລາຄາທັງໝົດຂອງ Supplier ນີ້?`)) {
            const submitBtn = this.querySelector('[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';
            
            fetch('ajax_bulk_update_smileone_prices_by_supplier.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ supplier_id: supplierId })
            }).then(res => res.json()).then(result => {
                
                if (result.success) {
                    alert('ອັບເດດລາຄາສຳເລັດ! (' + result.updated_rows + ' packages updated)');
                    priceUpdateModal.hide();
                } else {
                    alert('Error: ' + result.message);
                }
            }).finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerText = 'ຢືນຢັນການອັບເດດ';
            });
        }
    });
});
</script>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
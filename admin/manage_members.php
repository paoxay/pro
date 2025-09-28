<?php
// File: admin/manage_members.php (เวอร์ชันสมบูรณ์)
require_once 'admin_header.php';
require_once 'db_connect.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT id, username, wallet_balance, created_at FROM members";
if (!empty($search)) { $sql .= " WHERE username LIKE ?"; }
$sql .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($search)) {
    $searchTerm = "%{$search}%";
    $stmt->bind_param("s", $searchTerm);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 text-gray-800">ຈັດການສະມາຊິກ</h1>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMemberModal">
            <i class="fas fa-plus"></i> ເພີ່ມສະມາຊິກໃໝ່
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <form method="GET" action="manage_members.php">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="ຄົ້ນຫາຊື່ສະມາຊິກ..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="membersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ຊື່ສະມາຊິກ</th>
                            <th>ຍອດເງິນຄົງເຫຼືອ (ກີບ)</th>
                            <th>ວັນທີສະໝັກ</th>
                            <th class="text-center">ເຄື່ອງມື</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($member = $result->fetch_assoc()): ?>
                                <tr id="member-row-<?php echo $member['id']; ?>">
                                    <td><?php echo $member['id']; ?></td>
                                    <td><?php echo htmlspecialchars($member['username']); ?></td>
                                    <td class="wallet-balance fw-bold"><?php echo number_format($member['wallet_balance'], 2); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($member['created_at'])); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning adjust-balance-btn" data-id="<?php echo $member['id']; ?>" data-username="<?php echo htmlspecialchars($member['username']); ?>" data-bs-toggle="modal" data-bs-target="#balanceModal">
                                            <i class="fas fa-wallet"></i> ປັບຍອດເງິນ
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">ບໍ່ພົບຂໍ້ມູນສະມາຊິກ.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addMemberForm">
                <div class="modal-header">
                    <h5 class="modal-title">ເພີ່ມສະມາຊິກໃໝ່</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">ຊື່ຜູ້ໃຊ້:</label><input type="text" name="username" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">ລະຫັດຜ່ານ:</label><input type="password" name="password" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">ຍອດເງິນເລີ່ມຕົ້ນ:</label><input type="number" name="balance" class="form-control" value="0.00" step="0.01"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                    <button type="submit" class="btn btn-primary">ບັນທຶກ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="balanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="balanceForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="balanceModalTitle">ປັບຍອດເງິນ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="memberId" name="member_id">
                    <div class="mb-3"><label><input type="radio" name="action" value="add" checked> ເພີ່ມເງິນ</label> <label><input type="radio" name="action" value="deduct"> ຫັກເງິນ</label></div>
                    <div class="mb-3"><label class="form-label">ຈຳນວນເງິນ (ກີບ):</label><input type="number" name="amount" class="form-control" step="0.01" required></div>
                    <div class="mb-3"><label class="form-label">ໝາຍເຫດ:</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                    <button type="submit" class="btn btn-primary">ຢືນຢັນ</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// --- Add Member Logic ---
const addMemberModalEl = document.getElementById('addMemberModal');
const addMemberModal = new bootstrap.Modal(addMemberModalEl);
document.getElementById('addMemberForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const data = {
        username: this.username.value,
        password: this.password.value,
        balance: parseFloat(this.balance.value)
    };
    fetch('ajax_add_member.php', { method: 'POST', body: JSON.stringify(data), headers: {'Content-Type': 'application/json'} })
    .then(res => res.json()).then(result => {
        if (result.success) {
            alert('ເພີ່ມສະມາຊິກໃໝ່ສຳເລັດ!');
            location.reload(); // Reload page to show new member
        } else {
            alert('Error: ' + result.message);
        }
    });
});

// --- Adjust Balance Logic ---
const balanceModalEl = document.getElementById('balanceModal');
const balanceModal = new bootstrap.Modal(balanceModalEl);
balanceModalEl.addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const memberId = button.dataset.id;
    const username = button.dataset.username;
    this.querySelector('#memberId').value = memberId;
    this.querySelector('.modal-title').innerText = `ປັບຍອດເງິນ: ${username}`;
});

document.getElementById('balanceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const data = {
        member_id: parseInt(this.member_id.value),
        action: this.action.value,
        amount: parseFloat(this.amount.value),
        notes: this.notes.value
    };
    if (isNaN(data.amount) || data.amount <= 0) { alert('ກະລຸນາໃສ່ຈຳນວນເງິນທີ່ຖືກຕ້ອງ.'); return; }
    
    fetch('ajax_adjust_balance.php', { method: 'POST', body: JSON.stringify(data), headers: {'Content-Type': 'application/json'} })
    .then(res => res.json()).then(result => {
        if (result.success) {
            const row = document.getElementById('member-row-' + data.member_id);
            row.querySelector('.wallet-balance').innerText = parseFloat(result.new_balance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            balanceModal.hide();
            alert('ປັບຍອດເງິນສຳເລັດ!');
        } else {
            alert('Error: ' + result.message);
        }
    });
});
</script>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
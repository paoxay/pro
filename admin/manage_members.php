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
                                        <a href="member_statement.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-history"></i> Statement
                                        </a>
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

<div class="modal fade" id="addMemberModal" tabindex="-1">...</div>
<div class="modal fade" id="balanceModal" tabindex="-1">...</div>

</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// All JavaScript remains the same
</script>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
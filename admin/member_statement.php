<?php
// File: admin/member_statement.php
require_once 'admin_header.php';
require_once 'db_connect.php';

$member_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($member_id <= 0) {
    die("<h2>Error: Invalid Member ID</h2>");
}

// Fetch member details
$stmt_member = $conn->prepare("SELECT username, wallet_balance FROM members WHERE id = ?");
$stmt_member->bind_param("i", $member_id);
$stmt_member->execute();
$member_result = $stmt_member->get_result();
if ($member_result->num_rows === 0) {
    die("<h2>Error: Member not found</h2>");
}
$member = $member_result->fetch_assoc();
$stmt_member->close();

// Fetch Order History
$sql_orders = "SELECT 
                    o.created_at, o.order_code, g.name as game_name, p.name as package_name, 
                    o.amount, o.balance_before, o.balance_after
               FROM orders o
               JOIN game_packages p ON o.package_id = p.id
               JOIN games g ON p.game_id = g.id
               WHERE o.member_id = ? ORDER BY o.created_at DESC";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("i", $member_id);
$stmt_orders->execute();
$orders = $stmt_orders->get_result();

// Fetch Other Wallet Transactions
$sql_wallet = "SELECT created_at, transaction_type, notes, amount 
               FROM wallet_transactions 
               WHERE member_id = ? AND transaction_type != 'purchase' 
               ORDER BY created_at DESC";
$stmt_wallet = $conn->prepare($sql_wallet);
$stmt_wallet->bind_param("i", $member_id);
$stmt_wallet->execute();
$wallet_trans = $stmt_wallet->get_result();

?>

<div class="container-fluid">
    <a href="manage_members.php">&larr; ກັບໄປໜ້າຈັດການສະມາຊິກ</a>
    <h1 class="h3 my-4 text-gray-800">Statement ຂອງ: <span class="text-primary"><?php echo htmlspecialchars($member['username']); ?></span></h1>
    <h4 class="h5 mb-4">ຍອດເງິນປັດຈຸບັນ: <span class="text-success fw-bold"><?php echo number_format($member['wallet_balance'], 2); ?> ກີບ</span></h4>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-shopping-cart me-2"></i>ປະຫວັດການສັ່ງຊື້</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ວັນທີ-ເວລາ</th>
                            <th>ລາຍລະອຽດ</th>
                            <th class="text-end">ຍອດກ່ອນ (ກີບ)</th>
                            <th class="text-end">ຈຳນວນ (ກີບ)</th>
                            <th class="text-end">ຍອດຫຼັງ (ກີບ)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders->num_rows > 0): ?>
                            <?php while ($row = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['game_name'] . ' - ' . $row['package_name']); ?></td>
                                    <td class="text-end"><?php echo number_format($row['balance_before'], 2); ?></td>
                                    <td class="text-end text-danger fw-bold">-<?php echo number_format($row['amount'], 2); ?></td>
                                    <td class="text-end"><?php echo number_format($row['balance_after'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">ບໍ່ມີປະຫວັດການສັ່ງຊື້.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-wallet me-2"></i>ປະຫວັດທຸລະກຳອື່ນໆ (ເຕີມເງິນ/ຫັກເງິນ)</h6>
        </div>
        <div class="card-body">
             <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ວັນທີ-ເວລາ</th>
                            <th>ປະເພດ</th>
                            <th>ໝາຍເຫດ</th>
                            <th class="text-end">ຈຳນວນ (ກີບ)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($wallet_trans->num_rows > 0): ?>
                            <?php while ($row = $wallet_trans->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['transaction_type']); ?></td>
                                    <td><?php echo htmlspecialchars($row['notes']); ?></td>
                                    <td class="text-end fw-bold <?php echo $row['amount'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo number_format($row['amount'], 2); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">ບໍ່ມີທຸລະກຳອື່ນໆ.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
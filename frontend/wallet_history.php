<?php
// File: /wallet_history.php (Upgraded with Running Balance)
require_once 'header.php'; // ເອີ້ນໃຊ້ Header (ເຊິ່ງມີ $wallet_balance ຢູ່ແລ້ວ)

// ດຶງຂໍ້ມູນປະຫວັດທຸລະກຳ Wallet ທັງໝົດຂອງ member ທີ່ login อยู่
$member_id = $_SESSION['member_id'];
$sql = "SELECT amount, transaction_type, notes, created_at 
        FROM wallet_transactions 
        WHERE member_id = ? 
        ORDER BY created_at DESC, id DESC"; // Sắp xếp theo ID เพื่อความแม่นยำ

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$transactions_result = $stmt->get_result();

// ເກັບຂໍ້ມູນທັງໝົດໄວ້ໃນ array ເພື່ອຄຳນວນຍ້ອນຫຼັງ
$transactions = [];
if ($transactions_result) {
    while ($row = $transactions_result->fetch_assoc()) {
        $transactions[] = $row;
    }
}
?>

<h1 class="mb-4">ປະຫວັດທຸລະກຳກະເປົາເງິນ</h1>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 150px;">ວັນທີ-ເວລາ</th>
                        <th>ປະເພດ</th>
                        <th>ລາຍລະອຽດ</th>
                        <th class="text-end">ຍອດເງິນກ່ອນ (ກີບ)</th>
                        <th class="text-end">ຈຳນວນ (ກີບ)</th>
                        <th class="text-end">ຍອດເງິນຫຼັງ (ກີບ)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php
                        // ຕັ້ງຄ่ายอดเงินปัจจุบันเพื่อเริ่มคำนวณย้อนหลัง
                        $running_balance = $wallet_balance;
                        
                        foreach ($transactions as $trans):
                            $amount = $trans['amount'];
                            $balance_after = $running_balance;
                            $balance_before = $balance_after - $amount;
                        ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($trans['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($trans['transaction_type']))); ?></td>
                                <td><?php echo htmlspecialchars($trans['notes']); ?></td>
                                <td class="text-end text-muted"><?php echo number_format($balance_before, 2); ?></td>
                                <td class="text-end fw-bold">
                                    <?php
                                    if ($amount > 0) {
                                        echo '<span class="text-success">+ ' . number_format($amount, 2) . '</span>';
                                    } else {
                                        echo '<span class="text-danger">' . number_format($amount, 2) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-end fw-bold"><?php echo number_format($balance_after, 2); ?></td>
                            </tr>
                        <?php
                            // อัปเดต running_balance สำหรับรายการถัดไป (รายการที่เก่ากว่า)
                            $running_balance = $balance_before;
                        endforeach;
                        ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted p-4">ຍັງບໍ່ມີປະຫວັດທຸລະກຳ.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


</main> <footer class="container mt-4 text-center text-muted">
    <p>&copy; <?php echo date('Y'); ?> Topup Store</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
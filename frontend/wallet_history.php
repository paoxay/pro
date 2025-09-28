<?php
// File: /wallet_history.php
require_once 'header.php'; // ເອີ້ນໃຊ້ Header

// ดึงข้อมูลประวัติธุรกรรม Wallet เฉพาะของ member ที่ login อยู่
$member_id = $_SESSION['member_id'];
$sql = "SELECT amount, transaction_type, notes, created_at 
        FROM wallet_transactions 
        WHERE member_id = ? 
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$transactions_result = $stmt->get_result();
?>

<h1 class="mb-4">ປະຫວັດທຸລະກຳກະເປົາເງິນ</h1>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ວັນທີ-ເວລາ</th>
                        <th>ປະເພດທຸລະກຳ</th>
                        <th>ລາຍລະອຽດ</th>
                        <th class="text-end">ຈຳນວນເງິນ (ກີບ)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions_result && $transactions_result->num_rows > 0): ?>
                        <?php while($trans = $transactions_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($trans['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($trans['transaction_type']); ?></td>
                                <td><?php echo htmlspecialchars($trans['notes']); ?></td>
                                <td class="text-end fw-bold">
                                    <?php
                                    $amount = $trans['amount'];
                                    if ($amount > 0) {
                                        echo '<span class="text-success">+ ' . number_format($amount, 2) . '</span>';
                                    } else {
                                        echo '<span class="text-danger">' . number_format($amount, 2) . '</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">ຍັງບໍ່ມີປະຫວັດທຸລະກຳ.</td>
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
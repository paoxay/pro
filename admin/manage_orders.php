<?php
// File: admin/manage_orders.php (Upgraded with Status Check Button)
require_once 'admin_header.php';
require_once 'db_connect.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT 
            o.id, o.order_code, o.api_transaction_id, o.game_user_info, o.amount, o.status, o.created_at,
            o.balance_before, o.balance_after, m.username AS member_username,
            p.name AS package_name, g.name AS game_name
        FROM orders AS o
        JOIN members AS m ON o.member_id = m.id
        JOIN game_packages AS p ON o.package_id = p.id
        JOIN games AS g ON p.game_id = g.id";
if (!empty($search)) {
    $sql .= " WHERE (o.order_code LIKE ? OR m.username LIKE ? OR o.api_transaction_id LIKE ?)";
}
$sql .= " ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt === false) { die("SQL Prepare Failed: " . $conn->error); }
if (!empty($search)) {
    $searchTerm = "%{$search}%";
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<style>
    .table tbody tr { transition: background-color 0.3s ease; }
    .status-pending { background-color: rgba(255, 193, 7, 0.1); }
    .status-processing { background-color: rgba(13, 202, 240, 0.1); }
    .status-completed { background-color: rgba(25, 135, 84, 0.15) !important; }
    .status-cancelled { background-color: rgba(220, 53, 69, 0.1) !important; }
    .status-cancelled td { color: #6c757d; }
    .action-buttons .btn { margin-right: 5px; margin-bottom: 5px; }
</style>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">ຈັດການອໍເດີ້</h1>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <form method="GET" action="manage_orders.php">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="ຄົ້ນຫາເລກອໍເດີ້, Ref ID ຫຼື ຊື່ສະມາຊິກ..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ລະຫັດອໍເດີ້ / Ref ID</th>
                            <th>ເກມ / ແພັກເກັດ</th>
                            <th>ຂໍ້ມູນລູກຄ້າ</th>
                            <th>ລາຄາ</th>
                            <th>ສະຖານະ</th>
                            <th>ວັນທີ-ເວລາ</th>
                            <th style="width: 220px;">ເຄື່ອງມື (Actions)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($order = $result->fetch_assoc()): ?>
                                <tr id="order-row-<?php echo $order['id']; ?>" class="status-<?php echo $order['status']; ?>">
                                    <td class="fw-bold">
                                        <span class="text-primary"><?php echo htmlspecialchars($order['order_code']); ?></span><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['api_transaction_id']); ?></small>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($order['game_name']); ?></strong><br><small><?php echo htmlspecialchars($order['package_name']); ?></small></td>
                                    <td>
                                        <?php $user_info = json_decode($order['game_user_info'], true); if (is_array($user_info)) { foreach($user_info as $key => $value) { echo "<strong>" . htmlspecialchars(ucfirst($key)) . ":</strong> " . htmlspecialchars($value) . "<br>"; } } ?>
                                    </td>
                                    <td class="text-nowrap text-danger fw-bold"><?php echo number_format($order['amount']); ?> ກີບ</td>
                                    <td class="status-cell">
                                        <?php $status = $order['status']; $badge_class = 'bg-secondary';
                                            if ($status == 'pending') $badge_class = 'bg-warning text-dark';
                                            elseif ($status == 'processing') $badge_class = 'bg-info text-dark';
                                            elseif ($status == 'completed') $badge_class = 'bg-success';
                                            elseif ($status == 'cancelled') $badge_class = 'bg-danger';
                                            echo "<span class='badge {$badge_class} p-2'>" . htmlspecialchars(ucfirst($status)) . "</span>";
                                        ?>
                                    </td>
                                    <td class="text-nowrap"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <?php if ($order['status'] == 'pending' || $order['status'] == 'processing'): ?>
                                            <button class="btn btn-sm btn-secondary check-status-btn" data-order-id="<?php echo $order['id']; ?>">
                                                <i class="fas fa-sync"></i> ກວດສອບສະຖານະ
                                            </button>
                                            <button class="btn btn-sm btn-success action-btn" data-order-id="<?php echo $order['id']; ?>" data-new-status="completed"><i class="fas fa-check"></i></button>
                                            <button class="btn btn-sm btn-danger action-btn" data-order-id="<?php echo $order['id']; ?>" data-new-status="cancelled"><i class="fas fa-times"></i></button>
                                            <?php elseif ($order['status'] == 'completed'): ?>
                                            <span class="badge bg-success p-2"><i class="fas fa-check-circle"></i> ສຳເລັດແລ້ວ</span>
                                        <?php elseif ($order['status'] == 'cancelled'): ?>
                                            <span class="badge bg-danger p-2"><i class="fas fa-ban"></i> ຖືກຍົກເລີກ</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">ບໍ່ພົບຂໍ້ມູນອໍເດີ້.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click', function(e) {
    // Handle manual status change buttons (Completed/Cancelled)
    if (e.target.closest('.action-btn')) {
        const button = e.target.closest('.action-btn');
        const orderId = button.dataset.orderId;
        const newStatus = button.dataset.newStatus;
        if (confirm(`ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການປ່ຽນສະຖານະອໍເດີ້ #${orderId} ເປັນ ${newStatus}?`)) {
            button.disabled = true;
            fetch('ajax_update_order_status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: orderId, status: newStatus }) })
            .then(res => res.json()).then(result => {
                if (result.success) { location.reload(); } else { alert('Error: ' + result.message); button.disabled = false; }
            });
        }
    }

    // /// START: NEW JAVASCRIPT FOR STATUS CHECK ///
    // Handle API status check button
    if (e.target.closest('.check-status-btn')) {
        const button = e.target.closest('.check-status-btn');
        const orderId = button.dataset.orderId;
        const originalHtml = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Checking...`;

        fetch('ajax_check_order_status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ order_id: orderId }) })
        .then(res => res.json()).then(result => {
            if (result.success) {
                alert(result.message);
                location.reload(); // Reload to show the updated status and SN
            } else {
                alert('Error: ' + result.message);
                button.disabled = false;
                button.innerHTML = originalHtml;
            }
        }).catch(err => {
            alert('An error occurred while connecting to the server.');
            button.disabled = false;
            button.innerHTML = originalHtml;
        });
    }
    // /// END: NEW JAVASCRIPT FOR STATUS CHECK ///
});
</script>
</body>
</html>
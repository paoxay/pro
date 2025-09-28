<?php
// File: admin/manage_orders.php (New Button UI & Row Styling)
require_once 'admin_header.php';
require_once 'db_connect.php';

// --- PHP Logic for searching remains the same ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT 
            o.id, o.order_code, o.game_user_info, o.amount, o.status, o.created_at,
            o.balance_before, o.balance_after, m.username AS member_username,
            p.name AS package_name, g.name AS game_name
        FROM orders AS o
        JOIN members AS m ON o.member_id = m.id
        JOIN game_packages AS p ON o.package_id = p.id
        JOIN games AS g ON p.game_id = g.id";
if (!empty($search)) {
    $sql .= " WHERE (o.order_code LIKE ? OR m.username LIKE ?)";
}
$sql .= " ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt === false) { die("SQL Prepare Failed: " . $conn->error); }
if (!empty($search)) {
    $searchTerm = "%{$search}%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<style>
    .table tbody tr { transition: background-color 0.3s ease; }
    .status-pending { background-color: rgba(255, 193, 7, 0.1); }
    .status-processing { background-color: rgba(13, 202, 240, 0.1); }
    /* New beautiful styles for completed and cancelled */
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
                    <input type="text" name="search" class="form-control" placeholder="ຄົ້ນຫາເລກອໍເດີ້ ຫຼື ຊື່ສະມາຊິກ..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ລະຫັດອໍເດີ້</th>
                            <th>ເກມ / ແພັກເກັດ</th>
                            <th>ຂໍ້ມູນລູກຄ້າ</th>
                            <th>ລາຄາ</th>
                            <th>ຍອດເງິນ (ກ່ອນ &rarr; ຫຼັງ)</th>
                            <th>ວັນທີ-ເວລາ</th>
                            <th style="width: 220px;">ເຄື່ອງມື (Actions)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($order = $result->fetch_assoc()): ?>
                                <tr class="status-<?php echo $order['status']; ?>">
                                    <td class="fw-bold text-primary"><?php echo htmlspecialchars($order['order_code']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['game_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($order['package_name']); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                            $user_info = json_decode($order['game_user_info'], true);
                                            if (is_array($user_info)) {
                                                foreach($user_info as $key => $value) {
                                                    echo "<strong>" . htmlspecialchars(ucfirst($key)) . ":</strong> " . htmlspecialchars($value) . "<br>";
                                                }
                                            }
                                        ?>
                                    </td>
                                    <td class="text-nowrap text-danger fw-bold"><?php echo number_format($order['amount']); ?> ກີບ</td>
                                    <td class="text-nowrap">
                                        <?php echo number_format($order['balance_before'], 2); ?><br>
                                        &rarr; <?php echo number_format($order['balance_after'], 2); ?>
                                    </td>
                                    <td class="text-nowrap"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <?php if ($order['status'] == 'pending'): ?>
                                            <button class="btn btn-sm btn-info action-btn" data-order-id="<?php echo $order['id']; ?>" data-new-status="processing">
                                                <i class="fas fa-play"></i> ເລີ່ມດຳເນີນການ
                                            </button>
                                            <button class="btn btn-sm btn-danger action-btn" data-order-id="<?php echo $order['id']; ?>" data-new-status="cancelled">
                                                <i class="fas fa-times"></i> ຍົກເລີກ
                                            </button>
                                        <?php elseif ($order['status'] == 'processing'): ?>
                                            <button class="btn btn-sm btn-success action-btn" data-order-id="<?php echo $order['id']; ?>" data-new-status="completed">
                                                <i class="fas fa-check"></i> ສຳເລັດ
                                            </button>
                                            <button class="btn btn-sm btn-danger action-btn" data-order-id="<?php echo $order['id']; ?>" data-new-status="cancelled">
                                                <i class="fas fa-times"></i> ຍົກເລີກ
                                            </button>
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

</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('action-btn')) {
        const button = e.target;
        const orderId = button.dataset.orderId;
        const newStatus = button.dataset.newStatus;
        const actionText = button.textContent.trim();

        if (confirm(`ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການ "${actionText}" ອໍເດີ້ #${orderId} ?`)) {
            // Disable button to prevent double clicks
            button.disabled = true;
            button.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

            fetch('ajax_update_order_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: orderId, status: newStatus })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Reload the page to see the changes and updated buttons/styles
                    location.reload();
                } else {
                    alert('Error: ' + (result.message || 'An unknown error occurred.'));
                    // Re-enable button on failure
                    button.disabled = false;
                    button.innerHTML = actionText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.disabled = false;
                button.innerHTML = actionText;
            });
        }
    }
});
</script>
</body>
</html>
<?php
// File: /frontend/history.php (Full, Complete, and Polished Version)
require_once 'header.php'; // Assuming header.php is in the same folder

// --- PHP Logic with Search ---
$member_id = $_SESSION['member_id'];
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// === START EDIT 1: Updated SQL Query ===
$sql = "SELECT 
            o.id, 
            o.order_code,
            o.created_at, 
            o.game_user_info, 
            o.amount, 
            o.status,
            o.balance_before, 
            o.balance_after,
            g.name AS game_name,
            p.name AS package_name
        FROM orders AS o
        JOIN game_packages AS p ON o.package_id = p.id
        JOIN games AS g ON p.game_id = g.id
        WHERE o.member_id = ?";
// === END EDIT 1 ===

if (!empty($search_term)) {
    // UPDATED: Search by order_code or game_name
    $sql .= " AND (o.order_code LIKE ? OR g.name LIKE ?)";
}

$sql .= " ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);

if (!empty($search_term)) {
    $search_param = "%{$search_term}%";
    $stmt->bind_param("iss", $member_id, $search_param, $search_param);
} else {
    $stmt->bind_param("i", $member_id);
}

$stmt->execute();
$history_result = $stmt->get_result();
?>

<style>
    .history-item {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        transition: all 0.2s ease-in-out;
        margin-bottom: 1rem;
    }
    .history-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.08);
    }
    .status-badge { font-size: 0.9rem; padding: 0.5em 1em; }
    
    /* Receipt Modal Styles */
    .receipt-modal .modal-header { border-bottom: none; }
    .receipt-modal .status-header {
        padding: 1.5rem;
        border-radius: 5px;
        color: white;
        text-align: center;
        margin: -1px; /* To align with modal content padding */
    }
    .receipt-modal .status-completed { background-color: #198754; }
    .receipt-modal .status-pending { background-color: #ffc107; color: #000 !important; }
    .receipt-modal .status-cancelled { background-color: #dc3545; }
    .receipt-modal .status-processing { background-color: #0dcaf0; color: #000 !important; }
    .receipt-modal .receipt-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px dashed #ccc;
    }
    .receipt-modal .receipt-item:last-child { border-bottom: none; }
    .receipt-modal .receipt-item-label { color: #6c757d; }
</style>

<h1 class="mb-4 fw-bold">ປະຫວັດການສັ່ງຊື້</h1>

<?php
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="history.php">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="ຄົ້ນຫາເລກອໍເດີ້ ຫຼື ຊື່ເກມ..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> ຄົ້ນຫາ</button>
            </div>
        </form>
    </div>
</div>

<div class="history-list">
    <?php if ($history_result && $history_result->num_rows > 0): ?>
        <?php while($order = $history_result->fetch_assoc()): ?>
            <div class="history-item">
                <div class="d-flex align-items-center p-3">
                    <div class="flex-grow-1">
                        <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($order['game_name']); ?></h5>
                        <p class="mb-1 text-muted">ເລກອໍເດີ້: <?php echo htmlspecialchars($order['order_code']); ?></p>
                        
                        <div class="text-muted" style="font-size: 0.85em;">
                           ຍອດກ່ອນ: <?php echo number_format($order['balance_before']); ?> ກີບ <i class="fas fa-long-arrow-alt-right mx-1"></i> ຍອດຫຼັງ: <?php echo number_format($order['balance_after']); ?> ກີບ
                        </div>
                        <small class="text-muted"><?php echo date('d/m/Y, H:i', strtotime($order['created_at'])); ?></small>
                    </div>
                    <div class="text-end ms-3">
                        <h5 class="fw-bold text-danger mb-1"><?php echo number_format($order['amount']); ?> ກີບ</h5>
                        <?php
                            $status = $order['status'];
                            $badge_class = 'bg-secondary';
                            if ($status == 'pending') $badge_class = 'bg-warning text-dark';
                            elseif ($status == 'processing') $badge_class = 'bg-info text-dark';
                            elseif ($status == 'completed') $badge_class = 'bg-success';
                            elseif ($status == 'cancelled') $badge_class = 'bg-danger';
                            echo "<span class='badge {$badge_class} status-badge'>" . htmlspecialchars(ucfirst($status)) . "</span>";
                        ?>
                        <div class="mt-2">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#receiptModal"
                                data-order='<?php echo json_encode($order, JSON_UNESCAPED_UNICODE); ?>'>
                                <i class="fas fa-receipt"></i> ເບິ່ງໃບບິນ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center p-5 bg-light rounded">
            <p class="fs-5 text-muted">ທ່ານຍັງບໍ່ມີລາຍການສັ່ງຊື້.</p>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade receipt-modal" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ໃບບິນລາຍການສັ່ງຊື້</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modal-status-header">
                    <h2 id="modal-status" class="mb-0"></h2>
                </div>
                <div class="mt-4">
                    <div class="receipt-item">
                        <span class="receipt-item-label">ເລກອ້າງອີງ:</span>
                        <span id="modal-order-id" class="fw-bold"></span>
                    </div>
                    <div class="receipt-item">
                        <span class="receipt-item-label">ວັນທີ-ເວລາ:</span>
                        <span id="modal-date"></span>
                    </div>
                    <div class="receipt-item">
                        <span class="receipt-item-label">ເກມ:</span>
                        <span id="modal-game" class="fw-bold"></span>
                    </div>
                    <div class="receipt-item">
                        <span class="receipt-item-label">ແພັກເກັດ:</span>
                        <span id="modal-package"></span>
                    </div>
                    <div id="modal-user-info"></div>
                     <div class="receipt-item">
                        <span class="receipt-item-label fs-5">ລາຄາລວມ:</span>
                        <span id="modal-price" class="fs-5 fw-bold text-primary"></span>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <p class="text-muted mb-0">ຂໍຂອບໃຈທີ່ໃຊ້ບໍລິການ</p>
                </div>
            </div>
        </div>
    </div>
</div>

</main> 

<footer class="container mt-5 py-4 text-center text-muted border-top">
    <p>&copy; <?php echo date('Y'); ?> Topup Store</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const receiptModal = document.getElementById('receiptModal');
    receiptModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const orderData = JSON.parse(button.getAttribute('data-order'));
        
        const statusHeader = receiptModal.querySelector('#modal-status-header');
        const statusText = receiptModal.querySelector('#modal-status');
        statusText.textContent = orderData.status.charAt(0).toUpperCase() + orderData.status.slice(1);
        statusHeader.className = 'status-header status-' + orderData.status;

        receiptModal.querySelector('#modal-order-id').textContent = orderData.order_code; 
        
        const date = new Date(orderData.created_at.replace(' ', 'T'));
        receiptModal.querySelector('#modal-date').textContent = date.toLocaleString('lo-LA'); // Using Lao locale
        receiptModal.querySelector('#modal-game').textContent = orderData.game_name;
        receiptModal.querySelector('#modal-package').textContent = orderData.package_name;
        receiptModal.querySelector('#modal-price').textContent = parseFloat(orderData.amount).toLocaleString('en-US') + ' ກີບ';

        const userInfoContainer = receiptModal.querySelector('#modal-user-info');
        userInfoContainer.innerHTML = '';
        
        // Populate game user info (ID, Server, etc.)
        if (orderData.game_user_info) {
            try {
                const userInfo = JSON.parse(orderData.game_user_info);
                for (const key in userInfo) {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'receipt-item';
                    itemDiv.innerHTML = `<span class="receipt-item-label">${key.charAt(0).toUpperCase() + key.slice(1)}:</span><span>${userInfo[key]}</span>`;
                    userInfoContainer.appendChild(itemDiv);
                }
            } catch (e) {
                // Fallback for non-json data for safety
                const itemDiv = document.createElement('div');
                itemDiv.className = 'receipt-item';
                itemDiv.innerHTML = `<span class="receipt-item-label">Info:</span><span>${orderData.game_user_info}</span>`;
                userInfoContainer.appendChild(itemDiv);
            }
        }

        // === START EDIT 3: Add Balance info to Modal ===
        const balanceBeforeDiv = document.createElement('div');
        balanceBeforeDiv.className = 'receipt-item';
        balanceBeforeDiv.innerHTML = `<span class="receipt-item-label">ຍອດເງິນກ່ອນຊື້:</span><span class="fw-bold">${parseFloat(orderData.balance_before).toLocaleString('en-US', {minimumFractionDigits: 2})} ກີບ</span>`;
        userInfoContainer.appendChild(balanceBeforeDiv);

        const balanceAfterDiv = document.createElement('div');
        balanceAfterDiv.className = 'receipt-item';
        balanceAfterDiv.innerHTML = `<span class="receipt-item-label">ຍອດເງິນຫຼັງຊື້:</span><span class="fw-bold">${parseFloat(orderData.balance_after).toLocaleString('en-US', {minimumFractionDigits: 2})} ກີບ</span>`;
        userInfoContainer.appendChild(balanceAfterDiv);
        // === END EDIT 3 ===
    });
});
</script>

</body>
</html>
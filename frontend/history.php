<?php
// File: /frontend/history.php (Real-time Status Update FINAL Version)
require_once 'header.php';

$member_id = $_SESSION['member_id'];
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT o.id, o.order_code, o.api_transaction_id, o.api_sn, o.created_at, o.game_user_info, o.amount, o.status, g.name AS game_name, p.name AS package_name FROM orders AS o JOIN game_packages AS p ON o.package_id = p.id JOIN games AS g ON p.game_id = g.id WHERE o.member_id = ?";
if (!empty($search_term)) { $sql .= " AND (o.order_code LIKE ? OR g.name LIKE ? OR o.api_transaction_id LIKE ?)"; }
$sql .= " ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($search_term)) { $search_param = "%{$search_term}%"; $stmt->bind_param("isss", $member_id, $search_param, $search_param, $search_param); } else { $stmt->bind_param("i", $member_id); }
$stmt->execute();
$history_result = $stmt->get_result();
?>

<style> .history-item { background-color: #fff; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); transition: all 0.2s ease-in-out; margin-bottom: 1rem; } .history-item:hover { transform: translateY(-4px); box-shadow: 0 8px 16px rgba(0,0,0,0.08); } .status-badge { font-size: 0.9rem; padding: 0.5em 1em; } .receipt-modal .modal-header { border-bottom: none; } .receipt-modal .status-header { padding: 1.5rem; border-radius: 5px; color: white; text-align: center; margin: -1px; } .receipt-modal .status-completed { background-color: #198754; } .receipt-modal .status-pending { background-color: #ffc107; color: #000 !important; } .receipt-modal .status-cancelled { background-color: #dc3545; } .receipt-modal .status-processing { background-color: #0dcaf0; color: #000 !important; } .receipt-modal .receipt-item { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px dashed #ccc; } .receipt-modal .receipt-item:last-child { border-bottom: none; } .receipt-modal .receipt-item-label { color: #6c757d; } </style>
<h1 class="mb-4 fw-bold">ປະຫວັດການສັ່ງຊື້</h1>
<?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>'; unset($_SESSION['success_message']); } ?>
<div class="card shadow-sm mb-4"><div class="card-body"><form method="GET" action="history.php"><div class="input-group"><input type="text" name="search" class="form-control" placeholder="ຄົ້ນຫາເລກອໍເດີ້, Ref ID ຫຼື ຊື່ເກມ..." value="<?php echo htmlspecialchars($search_term); ?>"><button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> ຄົ້ນຫາ</button></div></form></div></div>

<div class="history-list">
    <?php if ($history_result && $history_result->num_rows > 0): ?>
        <?php while($order = $history_result->fetch_assoc()): ?>
            <div class="history-item" id="order-<?php echo $order['id']; ?>" data-id="<?php echo $order['id']; ?>" data-status="<?php echo $order['status']; ?>">
                <div class="d-flex align-items-center p-3">
                    <div class="flex-grow-1">
                        <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($order['game_name']); ?></h5>
                        <p class="mb-1 text-muted">ເລກອໍເດີ້: <?php echo htmlspecialchars($order['api_transaction_id'] ?: $order['order_code']); ?></p>
                        <small class="text-muted"><?php echo date('d/m/Y, H:i', strtotime($order['created_at'])); ?></small>
                    </div>
                    <div class="text-end ms-3">
                        <h5 class="fw-bold text-primary mb-1"><?php echo number_format($order['amount']); ?> ກີບ</h5>
                        <div class="status-container">
                            <?php $status = $order['status']; $badge_class = 'bg-secondary';
                                if ($status == 'pending') $badge_class = 'bg-warning text-dark';
                                elseif ($status == 'processing') $badge_class = 'bg-info text-dark';
                                elseif ($status == 'completed') $badge_class = 'bg-success';
                                elseif ($status == 'cancelled') $badge_class = 'bg-danger';
                                echo "<span class='badge {$badge_class} status-badge'>" . htmlspecialchars(ucfirst($status)) . "</span>";
                            ?>
                        </div>
                        <div class="mt-2"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#receiptModal" data-order='<?php echo json_encode($order, JSON_UNESCAPED_UNICODE); ?>'><i class="fas fa-receipt"></i> ເບິ່ງໃບບິນ</button></div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center p-5 bg-light rounded"><p class="fs-5 text-muted">ທ່ານຍັງບໍ່ມີລາຍການສັ່ງຊື້.</p></div>
    <?php endif; ?>
</div>
<div class="modal fade receipt-modal" id="receiptModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">ໃບບິນລາຍການສັ່ງຊື້</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><div id="modal-status-header"><h2 id="modal-status" class="mb-0"></h2></div><div class="mt-4"><div class="receipt-item"><span class="receipt-item-label">ເລກອ້າງອີງ (Ref ID):</span><span id="modal-order-id" class="fw-bold"></span></div><div class="receipt-item"><span class="receipt-item-label">ວັນທີ-ເວລາ:</span><span id="modal-date"></span></div><div class="receipt-item"><span class="receipt-item-label">ເກມ:</span><span id="modal-game" class="fw-bold"></span></div><div class="receipt-item"><span class="receipt-item-label">ແພັກເກັດ:</span><span id="modal-package"></span></div><div id="modal-user-info"></div><div id="modal-api-info"></div><div class="receipt-item"><span class="receipt-item-label fs-5">ລາຄາລວມ:</span><span id="modal-price" class="fs-5 fw-bold text-primary"></span></div></div><div class="text-center mt-4"><p class="text-muted mb-0">ຂໍຂອບໃຈທີ່ໃຊ້ບໍລິການ</p></div></div></div></div></div>
</main> 
<footer class="container mt-5 py-4 text-center text-muted border-top"><p>&copy; <?php echo date('Y'); ?> Topup Store</p></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// /// --- START: NEW REAL-TIME STATUS CHECK SCRIPT --- ///
document.addEventListener('DOMContentLoaded', function () {
    const checkOrderStatus = async (orderElement) => {
        // Prevent re-checking an order that is already being checked
        if (orderElement.classList.contains('is-checking')) {
            return;
        }
        orderElement.classList.add('is-checking');

        const orderId = orderElement.dataset.id;
        const statusContainer = orderElement.querySelector('.status-container');
        
        // Show visual feedback that checking has started
        statusContainer.innerHTML = `<span class='badge bg-light text-dark status-badge'><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ກວດສອບ...</span>`;

        try {
            const response = await fetch('ajax_check_status_frontend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await response.json();
            
            if (result.success) {
                updateOrderStatusUI(orderElement, result.new_status, result.sn);
            } else {
                // If checking failed (e.g. API error), revert to original status
                updateOrderStatusUI(orderElement, orderElement.dataset.status, null);
                console.error(`Failed to check order #${orderId}: ${result.message}`);
            }

        } catch (error) {
            console.error('Error fetching status:', error);
            updateOrderStatusUI(orderElement, orderElement.dataset.status, null); // Revert on error
        } finally {
            // Allow this order to be checked again later
            orderElement.classList.remove('is-checking');
        }
    };

    const updateOrderStatusUI = (orderElement, status, sn) => {
        if (orderElement.dataset.status === status) {
            // If status hasn't changed, just restore the original badge without a full redraw.
            let badgeClass = 'bg-secondary';
            if (status === 'pending') badgeClass = 'bg-warning text-dark';
            else if (status === 'processing') badgeClass = 'bg-info text-dark';
            
            orderElement.querySelector('.status-container').innerHTML = `<span class='badge ${badgeClass} status-badge'>${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
            return;
        }

        orderElement.dataset.status = status; // Update status for future checks
        
        const statusContainer = orderElement.querySelector('.status-container');
        let badgeClass = 'bg-secondary';
        if (status === 'pending') badgeClass = 'bg-warning text-dark';
        else if (status === 'processing') badgeClass = 'bg-info text-dark';
        else if (status === 'completed') badgeClass = 'bg-success';
        else if (status === 'cancelled') badgeClass = 'bg-danger';
        statusContainer.innerHTML = `<span class='badge ${badgeClass} status-badge'>${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;

        const receiptButton = orderElement.querySelector('[data-bs-target="#receiptModal"]');
        if (receiptButton) {
            let orderData = JSON.parse(receiptButton.getAttribute('data-order'));
            orderData.status = status;
            orderData.api_sn = sn;
            receiptButton.setAttribute('data-order', JSON.stringify(orderData));
        }
    };

    const checkAllPendingOrders = () => {
        const ordersToCheck = document.querySelectorAll('.history-item[data-status="processing"], .history-item[data-status="pending"]');
        if(ordersToCheck.length > 0) {
            console.log(`Checking status for ${ordersToCheck.length} orders...`);
            ordersToCheck.forEach(order => {
                checkOrderStatus(order);
            });
        } else {
            // If no more orders to check, we can stop the interval
            if (statusCheckInterval) {
                clearInterval(statusCheckInterval);
                console.log("All orders processed. Stopping real-time checks.");
            }
        }
    };

    // Initial check on page load
    setTimeout(checkAllPendingOrders, 500); // Start after a brief delay
    
    // Check again every 10 seconds
    const statusCheckInterval = setInterval(checkAllPendingOrders, 10000); 

    // --- The Modal logic remains the same ---
    const receiptModal = document.getElementById('receiptModal');
    receiptModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; const orderData = JSON.parse(button.getAttribute('data-order'));
        const statusHeader = receiptModal.querySelector('#modal-status-header'); const statusText = receiptModal.querySelector('#modal-status');
        statusText.textContent = orderData.status.charAt(0).toUpperCase() + orderData.status.slice(1); statusHeader.className = 'status-header status-' + orderData.status;
        receiptModal.querySelector('#modal-order-id').textContent = orderData.api_transaction_id || orderData.order_code;
        const date = new Date(orderData.created_at.replace(' ', 'T'));
        receiptModal.querySelector('#modal-date').textContent = date.toLocaleString('lo-LA'); receiptModal.querySelector('#modal-game').textContent = orderData.game_name;
        receiptModal.querySelector('#modal-package').textContent = orderData.package_name; receiptModal.querySelector('#modal-price').textContent = parseFloat(orderData.amount).toLocaleString('en-US') + ' ກີບ';
        const userInfoContainer = receiptModal.querySelector('#modal-user-info'); userInfoContainer.innerHTML = '';
        if (orderData.game_user_info) { try { const userInfo = JSON.parse(orderData.game_user_info); for (const key in userInfo) { userInfoContainer.innerHTML += `<div class="receipt-item"><span class="receipt-item-label">${key.charAt(0).toUpperCase() + key.slice(1)}:</span><span>${userInfo[key]}</span></div>`; } } catch (e) {} }
        const apiInfoContainer = receiptModal.querySelector('#modal-api-info'); apiInfoContainer.innerHTML = '';
        if (orderData.api_sn) { apiInfoContainer.innerHTML += `<div class="receipt-item"><span class="receipt-item-label">Serial Number (SN):</span><span class="fw-bold text-success">${orderData.api_sn}</span></div>`; }
    });
});
// /// --- END: REAL-TIME STATUS CHECK SCRIPT --- ///
</script>

</body>
</html>
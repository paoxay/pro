<?php
// File: /frontend/history.php (Real-time Polling Version)
require_once 'header.php'; 

$member_id = $_SESSION['member_id'];
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT o.id, o.order_code, o.created_at, o.game_user_info, o.amount, o.status,
            g.name AS game_name, p.name AS package_name
        FROM orders AS o
        JOIN game_packages AS p ON o.package_id = p.id
        JOIN games AS g ON p.game_id = g.id
        WHERE o.member_id = ?";

if (!empty($search_term)) {
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
    /* ... CSS styles ຄືເກົ່າ ... */
    .status-badge { transition: background-color 0.5s ease, color 0.5s ease; }
</style>

<h1 class="mb-4 fw-bold">ປະຫວັດການສັ່ງຊື້</h1>

<div class="history-list">
    <?php if ($history_result && $history_result->num_rows > 0): ?>
        <?php while($order = $history_result->fetch_assoc()): ?>
            <div class="history-item" data-order-id="<?php echo $order['id']; ?>" data-status="<?php echo $order['status']; ?>">
                <div class="d-flex align-items-center p-3">
                    <div class="flex-grow-1">
                        <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($order['game_name']); ?></h5>
                        <p class="mb-1 text-muted">ເລກອໍເດີ້: <?php echo htmlspecialchars($order['order_code']); ?></p>
                        <small class="text-muted"><?php echo date('d/m/Y, H:i', strtotime($order['created_at'])); ?></small>
                    </div>
                    <div class="text-end ms-3">
                        <h5 class="fw-bold text-primary mb-1"><?php echo number_format($order['amount']); ?> ກີບ</h5>
                        <?php
                            $status = $order['status'];
                            $badge_class = 'bg-secondary';
                            if ($status == 'pending') $badge_class = 'bg-warning text-dark';
                            elseif ($status == 'processing') $badge_class = 'bg-info text-dark';
                            elseif ($status == 'completed') $badge_class = 'bg-success';
                            elseif ($status == 'cancelled') $badge_class = 'bg-danger';
                            // ເພີ່ມ ID ໃຫ້ກັບ Badge ເພື່ອໃຫ້ JavaScript ຊອກຫາໄດ້
                            echo "<span id='status-badge-{$order['id']}' class='badge {$badge_class} status-badge'>" . htmlspecialchars(ucfirst($status)) . "</span>";
                        ?>
                        <div class="mt-2">
                             <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#receiptModal" data-order='<?php echo json_encode($order, JSON_UNESCAPED_UNICODE); ?>'>
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

</main>
<footer class="container mt-5 py-4 text-center text-muted border-top">
    <p>&copy; <?php echo date('Y'); ?> Topup Store</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ... ສ່ວນ JavaScript ຂອງ Modal ຄືເກົ່າ ...

// --- START: REAL-TIME POLLING SCRIPT ---
document.addEventListener('DOMContentLoaded', function () {
    const checkInterval = 15000; // ກວດສອບທຸກໆ 15 ວິນາທີ (15000ms)

    const checkOrderStatus = (orderItem) => {
        const orderId = orderItem.dataset.orderId;
        
        const formData = new FormData();
        formData.append('order_id', orderId);

        fetch('ajax_check_status.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(result => {
            if (result.success && result.status !== 'processing') {
                // ເມື່ອສະຖານະປ່ຽນແປງ, ໃຫ້ອັບເດດໜ້າຕາເວັບ
                updateOrderStatusOnPage(orderId, result.status);
            }
        }).catch(error => console.error('Polling Error:', error));
    };

    const updateOrderStatusOnPage = (orderId, newStatus) => {
        const orderItem = document.querySelector(`.history-item[data-order-id='${orderId}']`);
        if (orderItem) {
            orderItem.dataset.status = newStatus; // ອັບເດດ status ຂອງ element
            const statusBadge = document.getElementById(`status-badge-${orderId}`);
            
            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            
            // ປ່ຽນສີຂອງ Badge
            statusBadge.className = 'badge status-badge'; // Reset classes
            if (newStatus === 'completed') {
                statusBadge.classList.add('bg-success');
            } else if (newStatus === 'cancelled') {
                statusBadge.classList.add('bg-danger');
            }
        }
    };
    
    const findAndCheckProcessingOrders = () => {
        // ຊອກຫາສະເພາະອໍເດີ້ທີ່ຍັງເປັນ "processing"
        const processingItems = document.querySelectorAll(".history-item[data-status='processing']");
        if (processingItems.length > 0) {
            console.log(`Found ${processingItems.length} processing orders. Checking now...`);
            processingItems.forEach(item => {
                checkOrderStatus(item);
            });
        }
    };

    // ເລີ່ມການກວດສອບຄັ້ງທຳອິດຫຼັງຈາກໂຫຼດໜ້າเว็บ 5 ວິນາທີ
    setTimeout(findAndCheckProcessingOrders, 5000);
    
    // ຕັ້ງຄ່າໃຫ້ກວດສອບອັດຕະໂນມັດທຸກໆໄລຍະເວລາທີ່ກຳນົດ
    setInterval(findAndCheckProcessingOrders, checkInterval);
});
// --- END: REAL-TIME POLLING SCRIPT ---
</script>
</body>
</html>
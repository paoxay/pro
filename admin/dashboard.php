<?php
// File: admin/dashboard.php (Upgraded to show combined stats)
require_once 'admin_header.php';
require_once 'db_connect.php';

// --- PHP logic for getting initial data for TODAY ---
$today = date('Y-m-d');
$startDateTime = $today . ' 00:00:00';
$endDateTime = $today . ' 23:59:59';

// 1. Get combined summary
$sql_summary = "SELECT COUNT(id) AS total_orders, COALESCE(SUM(amount), 0) AS total_sales, COALESCE(SUM(profit), 0) AS total_profit FROM (SELECT id, amount, profit, created_at, status FROM orders UNION ALL SELECT id, amount, profit, created_at, status FROM smileone_orders) AS c WHERE status = 'completed' AND created_at BETWEEN ? AND ?";
$stmt_summary = $conn->prepare($sql_summary);
$stmt_summary->bind_param("ss", $startDateTime, $endDateTime);
$stmt_summary->execute();
$initial_summary = $stmt_summary->get_result()->fetch_assoc();
$stmt_summary->close();

// 2. Get TOKO cost
$sql_toko_cost = "SELECT COALESCE(SUM(p.cost_price), 0) AS total_cost_toko FROM orders o JOIN game_packages p ON o.package_id = p.id WHERE o.status = 'completed' AND o.created_at BETWEEN ? AND ?";
$stmt_toko = $conn->prepare($sql_toko_cost);
$stmt_toko->bind_param("ss", $startDateTime, $endDateTime);
$stmt_toko->execute();
$initial_toko_cost = $stmt_toko->get_result()->fetch_assoc();
$stmt_toko->close();

// 3. Get Smile One cost
$sql_smileone_cost = "SELECT COALESCE(SUM(p.cost_price), 0) AS total_cost_smileone, COALESCE(SUM(p.api_price), 0) AS total_coins_smileone FROM smileone_orders o JOIN smileone_packages p ON o.package_id = p.id WHERE o.status = 'completed' AND o.created_at BETWEEN ? AND ?";
$stmt_smileone = $conn->prepare($sql_smileone_cost);
$stmt_smileone->bind_param("ss", $startDateTime, $endDateTime);
$stmt_smileone->execute();
$initial_smileone_cost = $stmt_smileone->get_result()->fetch_assoc();
$stmt_smileone->close();

$conn->close();
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Dashboard</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">ເລືອກຊ່ວງເວລາ</h6></div>
        <div class="card-body">
            <form id="dateFilterForm" class="row g-3 align-items-center">
                <div class="col-auto"><label for="start_date" class="form-label">ແຕ່ວັນທີ:</label><input type="date" id="start_date" name="start_date" value="<?php echo $today; ?>" class="form-control"></div>
                <div class="col-auto"><label for="end_date" class="form-label">ເຖິງວັນທີ:</label><input type="date" id="end_date" name="end_date" value="<?php echo $today; ?>" class="form-control"></div>
                <div class="col-auto align-self-end"><button type="submit" class="btn btn-primary">ສະແດງຂໍ້ມູນ</button></div>
            </form>
        </div>
    </div>

    <h2 id="summaryTitle" class="h4 mb-3">ຂໍ້ມູນສະຫຼຸບລວມຂອງມື້ນີ້</h2>
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">ຍອດຂາຍລວມ (ທັງໝົດ)</div>
                    <div id="total_sales" class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($initial_summary['total_sales'], 2); ?> ກີບ</div>
                </div><div class="col-auto card-icon"><i class="fas fa-dollar-sign"></i></div></div></div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">ກຳໄລລວມ (ທັງໝົດ)</div>
                    <div id="total_profit" class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($initial_summary['total_profit'], 2); ?> ກີບ</div>
                </div><div class="col-auto card-icon"><i class="fas fa-piggy-bank"></i></div></div></div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">ຈຳນວນອໍເດີ້ລວມ</div>
                    <div id="total_orders" class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $initial_summary['total_orders']; ?> ລາຍການ</div>
                </div><div class="col-auto card-icon"><i class="fas fa-shopping-cart"></i></div></div></div>
            </div>
        </div>
    </div>

    <h2 class="h4 mb-3">ສະຫຼຸບຕົ້ນທຶນ API</h2>
     <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">ຕົ້ນທຶນ TOKO API</div>
                    <div id="total_cost_toko" class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($initial_toko_cost['total_cost_toko'], 2); ?> ກີບ</div>
                </div><div class="col-auto card-icon"><i class="fas fa-server"></i></div></div></div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">ຕົ້ນທຶນ Smile One API</div>
                    <div id="total_cost_smileone" class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($initial_smileone_cost['total_cost_smileone'], 2); ?> ກີບ</div>
                </div><div class="col-auto card-icon"><i class="fas fa-smile"></i></div></div></div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2">
                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">ຍອດໃຊ້ Smile Coins</div>
                    <div id="total_coins_smileone" class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($initial_smileone_cost['total_coins_smileone'], 2); ?> Coins</div>
                </div><div class="col-auto card-icon"><i class="fas fa-coins"></i></div></div></div>
            </div>
        </div>
    </div>
    </div>

<script>
document.getElementById('dateFilterForm').addEventListener('submit', function(e) {
    e.preventDefault(); 
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const summaryTitle = document.getElementById('summaryTitle');

    if (!startDate || !endDate) { alert('ກະລຸນາເລືອກທັງວັນທີເລີ່ມຕົ້ນ ແລະ ສິ້ນສຸດ'); return; }
    summaryTitle.innerText = `ຂໍ້ມູນສະຫຼຸບຕັ້ງແຕ່ວັນທີ ${startDate} ເຖິງ ${endDate}`;
    
    // Set all fields to Loading
    document.getElementById('total_sales').innerText = 'Loading...';
    document.getElementById('total_profit').innerText = 'Loading...';
    document.getElementById('total_orders').innerText = 'Loading...';
    document.getElementById('total_cost_toko').innerText = 'Loading...';
    document.getElementById('total_cost_smileone').innerText = 'Loading...';
    document.getElementById('total_coins_smileone').innerText = 'Loading...';

    fetch(`ajax_get_dashboard_stats.php?start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const formatter = (num) => parseFloat(num).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                // Update Main Summary Cards
                document.getElementById('total_sales').innerText = formatter(data.total_sales) + ' ກີບ';
                document.getElementById('total_profit').innerText = formatter(data.total_profit) + ' ກີບ';
                document.getElementById('total_orders').innerText = data.total_orders + ' ລາຍການ';
                
                // --- UPDATE NEW API COST CARDS ---
                document.getElementById('total_cost_toko').innerText = formatter(data.total_cost_toko) + ' ກີບ';
                document.getElementById('total_cost_smileone').innerText = formatter(data.total_cost_smileone) + ' ກີບ';
                document.getElementById('total_coins_smileone').innerText = formatter(data.total_coins_smileone) + ' Coins';

            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            alert('ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່.');
        });
});
</script>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
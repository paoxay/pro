<?php
// File: admin/dashboard.php (เวอร์ชัน Bootstrap)
require_once 'admin_header.php'; // เรียกใช้ Header ใหม่
require_once 'db_connect.php';

$today = date('Y-m-d');
// ... (PHP logic for getting initial data remains the same) ...
$startDateTime = $today . ' 00:00:00';
$endDateTime = $today . ' 23:59:59';
$sql = "SELECT COUNT(id) AS total_orders, COALESCE(SUM(amount), 0) AS total_sales, COALESCE(SUM(profit), 0) AS total_profit FROM orders WHERE status = 'completed' AND created_at BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $startDateTime, $endDateTime);
$stmt->execute();
$result = $stmt->get_result();
$initial_summary = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Dashboard</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">ເລືອກຊ່ວງເວລາ</h6>
        </div>
        <div class="card-body">
            <form id="dateFilterForm" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="start_date" class="form-label">ແຕ່ວັນທີ:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $today; ?>" class="form-control">
                </div>
                <div class="col-auto">
                    <label for="end_date" class="form-label">ເຖິງວັນທີ:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $today; ?>" class="form-control">
                </div>
                <div class="col-auto align-self-end">
                    <button type="submit" class="btn btn-primary">ສະແດງຂໍ້ມູນ</button>
                </div>
            </form>
        </div>
    </div>

    <h2 id="summaryTitle" class="h4 mb-3">ຂໍ້ມູນສະຫຼຸບຂອງມື້ນີ້</h2>
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">ຍອດຂາຍລວມ</div>
                            <div id="total_sales" class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($initial_summary['total_sales'], 2); ?> ກີບ</div>
                        </div>
                        <div class="col-auto card-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">ກຳໄລລວມ</div>
                            <div id="total_profit" class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($initial_summary['total_profit'], 2); ?> ກີບ</div>
                        </div>
                        <div class="col-auto card-icon">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">ຈຳນວນອໍເດີ້ (ສຳເລັດ)</div>
                            <div id="total_orders" class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $initial_summary['total_orders']; ?> ລາຍການ</div>
                        </div>
                        <div class="col-auto card-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ... (JavaScript code from the previous step remains exactly the same) ...
document.getElementById('dateFilterForm').addEventListener('submit', function(e) {
    e.preventDefault(); 

    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const summaryTitle = document.getElementById('summaryTitle');

    if (!startDate || !endDate) {
        alert('ກະລຸນາເລືອກທັງວັນທີເລີ່ມຕົ້ນ ແລະ ສິ້ນສຸດ');
        return;
    }
    
    summaryTitle.innerText = `ຂໍ້ມູນສະຫຼຸບຕັ້ງແຕ່ວັນທີ ${startDate} ເຖິງ ${endDate}`;
    
    document.getElementById('total_sales').innerText = 'Loading...';
    document.getElementById('total_profit').innerText = 'Loading...';
    document.getElementById('total_orders').innerText = 'Loading...';

    fetch(`ajax_get_dashboard_stats.php?start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('total_sales').innerText = parseFloat(data.total_sales).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ກີບ';
                document.getElementById('total_profit').innerText = parseFloat(data.total_profit).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ກີບ';
                document.getElementById('total_orders').innerText = data.total_orders + ' ລາຍການ';
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
</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
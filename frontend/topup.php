<?php
// File: /frontend/topup.php (The True, Complete, Final Version)
require_once 'header.php';

// --- PHP Data Fetching and Form Processing Logic ---
$game_id = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
if ($game_id <= 0) {
    echo "<main class='container mt-4'><div class='alert alert-danger'>ບໍ່ພົບ ID ເກມທີ່ລະບຸ.</div></main>";
    exit;
}

$stmt_game = $conn->prepare("SELECT * FROM games WHERE id = ? AND status = 'active'");
$stmt_game->bind_param("i", $game_id);
$stmt_game->execute();
$result_game = $stmt_game->get_result();
if ($result_game->num_rows === 0) {
    echo "<main class='container mt-4'><div class='alert alert-danger'>ບໍ່ພົບເກມນີ້ ຫຼື ເກມກຳລັງປິດปรับปรุง.</div></main>";
    exit;
}
$game = $result_game->fetch_assoc();
$stmt_game->close();

$stmt_fields = $conn->prepare("SELECT * FROM game_fields WHERE game_id = ? ORDER BY display_order ASC");
$stmt_fields->bind_param("i", $game_id);
$stmt_fields->execute();
$fields_result = $stmt_fields->get_result();

$stmt_packages = $conn->prepare("SELECT * FROM game_packages WHERE game_id = ? ORDER BY price ASC");
$stmt_packages->bind_param("i", $game_id);
$stmt_packages->execute();
$packages_result = $stmt_packages->get_result();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $package_id = $_POST['package_id'] ?? 0;
    
    $custom_fields_data = [];
    $is_valid = true;
    if (isset($_POST['fields']) && is_array($_POST['fields'])) {
        foreach ($_POST['fields'] as $name => $value) {
            $trimmed_value = trim($value);
            if (empty($trimmed_value)) {
                $is_valid = false;
            }
            $custom_fields_data[trim($name)] = $trimmed_value;
        }
    } else {
        $fields_result_check = $conn->query("SELECT id FROM game_fields WHERE game_id = $game_id");
        if($fields_result_check->num_rows > 0) $is_valid = false;
    }

    $game_user_info_json = json_encode($custom_fields_data, JSON_UNESCAPED_UNICODE);

    if (empty($package_id) || !$is_valid) {
        $_SESSION['error_message'] = "ກະລຸນາເລືອກແພັກເກັດ ແລະ ປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ.";
    } else {
        $stmt_pkg = $conn->prepare("SELECT price, cost_price, name FROM game_packages WHERE id = ?");
        $stmt_pkg->bind_param("i", $package_id);
        $stmt_pkg->execute();
        $pkg_result = $stmt_pkg->get_result();
        
        if ($pkg_result->num_rows === 1) {
            $package = $pkg_result->fetch_assoc();
            $package_price = $package['price'];
            
            if ($wallet_balance >= $package_price) {
                $conn->begin_transaction();
                try {
                    $order_code = date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                    $balance_before = $wallet_balance;
                    $balance_after = $wallet_balance - $package_price;

                    $stmt_wallet = $conn->prepare("UPDATE members SET wallet_balance = ? WHERE id = ?");
                    $stmt_wallet->bind_param("di", $balance_after, $_SESSION['member_id']);
                    $stmt_wallet->execute();

                    $profit = $package_price - $package['cost_price'];
                    $stmt_order = $conn->prepare("INSERT INTO orders (member_id, order_code, package_id, game_user_info, amount, profit, balance_before, balance_after, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt_order->bind_param("isisddds", $_SESSION['member_id'], $order_code, $package_id, $game_user_info_json, $package_price, $profit, $balance_before, $balance_after);
                    $stmt_order->execute();

                    $purchase_amount = -$package_price;
                    $notes = "Order #" . $order_code . " - ຊື້ '" . $package['name'] . "'";
                    $stmt_trans = $conn->prepare("INSERT INTO wallet_transactions (member_id, amount, transaction_type, notes) VALUES (?, ?, 'purchase', ?)");
                    $stmt_trans->bind_param("ids", $_SESSION['member_id'], $purchase_amount, $notes);
                    $stmt_trans->execute();

                    $conn->commit();
                    $_SESSION['success_message'] = "ສັ່ງຊື້ສຳເລັດ! (ເລກອໍເດີ້: $order_code)";
                    header("Location: history.php");
                    exit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error_message'] = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
                }
            } else { $_SESSION['error_message'] = "ຍອດເງິນໃນກະເປົາຂອງທ່ານບໍ່ພຽງພໍ!"; }
        } else { $_SESSION['error_message'] = "ແພັກເກັດທີ່ເລືອກບໍ່ຖືກຕ້ອງ."; }
    }
    header("Location: topup.php?game_id=" . $game_id);
    exit();
}
?>

<style>
    body { background: linear-gradient(to top, #f3f5f7, #ffffff); }
    .topup-container { max-width: 800px; margin: auto; }
    .game-title-card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.04);
        margin-bottom: 2rem;
    }
    .game-title-card h1 { font-weight: 700; color: #2c3e50; }
    .game-description { color: #555; line-height: 1.8; }
    .game-description img { max-width: 100%; height: auto; border-radius: 5px; }
    .step-heading { font-weight: 700; color: #34495e; margin-bottom: 1.5rem; }
    .package-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; }
    .package-item {
        cursor: pointer; border: 2px solid #e0e0e0; border-radius: 10px; transition: all 0.25s ease-in-out;
        position: relative; background: #fff; padding: 1rem;
    }
    .package-item:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(0,0,0,0.08); border-color: #74b9ff; }
    .package-item.selected { border-color: #0984e3; background-color: #dfefff; box-shadow: 0 8px 16px rgba(9,132,227,0.2); }
    .package-item.selected::after {
        content: '✔'; position: absolute; top: 8px; right: 12px; color: #0984e3;
        font-size: 1.4rem; font-weight: bold;
    }
    .package-name { font-weight: 500; font-size: 0.95rem; color: #34495e; }
    .package-price { font-weight: 700; font-size: 1.1rem; color: #d35400; }
    .form-control-lg:focus, .form-select-lg:focus { border-color: #0984e3; box-shadow: 0 0 0 0.25rem rgba(9,132,227,0.25); }
</style>

<div class="topup-container py-4">
    <div class="game-title-card text-center">
        <h1 class="display-5"><?php echo htmlspecialchars($game['name']); ?></h1>
        <hr class="w-50 mx-auto my-3">
        <div class="game-description text-start">
            <?php echo $game['description']; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4 p-lg-5">
            <?php
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
                    unset($_SESSION['error_message']);
                }
            ?>
            <form id="topupForm" action="topup.php?game_id=<?php echo $game_id; ?>" method="POST">
                
                <h4 class="step-heading">1. ປ້ອນຂໍ້ມູນບັນຊີເກມ</h4>
                <div id="dynamic-fields" class="mb-4">
                    <div class="row g-3">
                        <?php mysqli_data_seek($fields_result, 0); while($field = $fields_result->fetch_assoc()): ?>
                        <div class="col">
                            <label class="form-label fw-bold"><?php echo htmlspecialchars($field['field_label']); ?>:</label>
                            <?php if ($field['field_type'] == 'text' || $field['field_type'] == 'number'): ?>
                                <input type="<?php echo $field['field_type']; ?>" class="form-control form-control-lg" name="fields[<?php echo htmlspecialchars($field['field_name']); ?>]" placeholder="<?php echo htmlspecialchars($field['placeholder']); ?>" required>
                            <?php elseif ($field['field_type'] == 'select'): ?>
                                <select class="form-select form-select-lg" name="fields[<?php echo htmlspecialchars($field['field_name']); ?>]" required>
                                    <option value="" disabled selected><?php echo htmlspecialchars($field['placeholder'] ?: 'ກະລຸນາເລືອກ...'); ?></option>
                                    <?php $options = explode(',', $field['field_options']); foreach ($options as $option): $opt = trim($option); ?>
                                        <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <h4 class="step-heading">2. ເລືອກແພັກເກັດ</h4>
                <div class="package-grid mb-4">
                    <?php mysqli_data_seek($packages_result, 0); while($pkg = $packages_result->fetch_assoc()): ?>
                        <div class="package-item" data-package-id="<?php echo $pkg['id']; ?>" data-name="<?php echo htmlspecialchars($pkg['name']); ?>" data-price="<?php echo $pkg['price']; ?>">
                            <p class="package-name mb-1"><?php echo htmlspecialchars($pkg['name']); ?></p>
                            <p class="package-price mb-0"><?php echo number_format($pkg['price']); ?> ກີບ</p>
                        </div>
                    <?php endwhile; ?>
                </div>
                <input type="hidden" name="package_id" id="selectedPackageId" value="">

                <div class="d-grid mt-4">
                    <button type="button" id="mainOrderBtn" class="btn btn-primary btn-lg fw-bold py-3">
                        <i class="fas fa-shopping-cart me-2"></i> ສັ່ງຊື້
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ກະລຸນາກວດສອບລາຍການ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modal-summary"></div>
                <hr>
                <p class="fs-5 fw-bold mb-0">ລາຄາສຸດທ້າຍ: <span id="modal-final-price" class="text-danger"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                <button type="button" id="confirmOrderBtn" class="btn btn-primary">ຢືນຢັນການສັ່ງຊື້</button>
            </div>
        </div>
    </div>
</div>

</main> 

<footer class="container mt-5 py-4 text-center text-muted">
    <p>&copy; <?php echo date('Y'); ?> Topup Store</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const topupForm = document.getElementById('topupForm');
    const packageItems = document.querySelectorAll('.package-item');
    const selectedPackageIdInput = document.getElementById('selectedPackageId');
    const mainOrderBtn = document.getElementById('mainOrderBtn');
    const confirmOrderBtn = document.getElementById('confirmOrderBtn');
    const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    let selectedPackage = null;

    packageItems.forEach(card => {
        card.addEventListener('click', function() {
            packageItems.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            selectedPackage = { id: this.dataset.packageId, name: this.dataset.name, price: parseFloat(this.dataset.price) };
            selectedPackageIdInput.value = selectedPackage.id;
        });
    });

    mainOrderBtn.addEventListener('click', function() {
        let allFieldsValid = true;
        document.querySelectorAll('#dynamic-fields [required]').forEach(field => {
            field.classList.remove('is-invalid');
            if (!field.value) {
                allFieldsValid = false;
                field.classList.add('is-invalid');
            }
        });

        if (!selectedPackage) {
            alert('ກະລຸນາເລືອກແພັກເກັດທີ່ຕ້ອງການ.');
            return;
        }
        if (!allFieldsValid) {
            alert('ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ.');
            return;
        }

        let summaryHTML = `<p class="mb-2"><strong>ເກມ:</strong> <?php echo htmlspecialchars($game['name']); ?></p>`;
        summaryHTML += `<p class="mb-2"><strong>ແພັກເກັດ:</strong> ${selectedPackage.name}</p>`;
        
        document.querySelectorAll('#dynamic-fields .form-control, #dynamic-fields .form-select').forEach(field => {
            const label = field.closest('.col').querySelector('.form-label').textContent;
            summaryHTML += `<p class="mb-2"><strong>${label}</strong> ${field.value}</p>`;
        });
        
        document.getElementById('modal-summary').innerHTML = summaryHTML;
        document.getElementById('modal-final-price').textContent = selectedPackage.price.toLocaleString('en-US') + ' ກີບ';
        
        confirmationModal.show();
    });

    confirmOrderBtn.addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ກำลังดำเนินการ...`;
        topupForm.submit();
    });
});
</script>

</body>
</html>
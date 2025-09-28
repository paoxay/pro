<?php
// File: admin/import_from_cache.php (Reads from the fast local cache) - FINAL VERSION with FATAL ERROR FIX
require_once 'db_connect.php';
require_once 'auth_check.php';

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $supplier_id = $_GET['supplier_id'] ?? 0;
    if ($supplier_id <= 0) { die(json_encode(['success' => false, 'message' => 'Invalid Supplier ID'])); }
    
    $data = []; $sql = ''; $params = []; $types = '';
    
    switch ($_GET['action']) {
        case 'get_categories':
            $sql = "SELECT DISTINCT category AS val, category AS txt FROM api_cache_products WHERE api_supplier_id = ? ORDER BY category";
            $types = "i"; $params[] = $supplier_id;
            break;
        case 'get_operators':
            $category = $_GET['category'] ?? '';
            $sql = "SELECT DISTINCT brand AS val, brand AS txt FROM api_cache_products WHERE api_supplier_id = ? AND category = ? ORDER BY brand";
            $types = "is"; $params[] = $supplier_id; $params[] = $category;
            break;
        case 'get_jenis':
            $brand = $_GET['brand'] ?? '';
            $sql = "SELECT DISTINCT jenis AS val, jenis AS txt FROM api_cache_products WHERE api_supplier_id = ? AND brand = ? ORDER BY jenis";
            $types = "is"; $params[] = $supplier_id; $params[] = $brand;
            break;
        case 'get_packages':
            $jenis = $_GET['jenis'] ?? ''; $brand = $_GET['brand'] ?? '';
            $sql = "SELECT product_code, product_name, cost_price FROM api_cache_products WHERE api_supplier_id = ? AND jenis = ? AND brand = ? ORDER BY cost_price";
            $types = "iss"; $params[] = $supplier_id; $params[] = $jenis; $params[] = $brand;
            break;
    }
    
    if(!empty($sql)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if(!empty($types)) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) { $data[] = $row; }
            $stmt->close();
        }
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

$success_message = ""; $error_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_game'])) {
    $game_name = $_POST['game_name'];
    $operator_code = $_POST['operator_code'];
    $supplier_id = $_POST['supplier_id'];
    $exchange_rate = (float)($_POST['exchange_rate'] ?? 0.0014);
    $default_markup = (int)($_POST['default_markup'] ?? 15);
    $packages = $_POST['packages'] ?? [];

    if (empty($game_name) || empty($operator_code) || empty($supplier_id) || empty($packages)) {
        $error_message = "ຂໍ້ມູນບໍ່ຄົບຖ້ວນ.";
    } else {
        $conn->begin_transaction();
        try {
            $game_id = null;
            $stmt_check_game = $conn->prepare("SELECT id FROM games WHERE api_operator_code = ? AND api_supplier_id = ?");
            $stmt_check_game->bind_param("si", $operator_code, $supplier_id);
            $stmt_check_game->execute();
            $game_result = $stmt_check_game->get_result();
            if ($game_result->num_rows > 0) {
                $game_row = $game_result->fetch_assoc();
                $game_id = $game_row['id'];
                
                $stmt_update_game = $conn->prepare("UPDATE games SET name = ?, exchange_rate = ?, default_markup = ? WHERE id = ?");
                $stmt_update_game->bind_param("sdii", $game_name, $exchange_rate, $default_markup, $game_id);
                $stmt_update_game->execute();
                $stmt_update_game->close();
                $success_message .= "Game '$game_name' already exists. Updating settings and packages... <br>";

            } else {
                $stmt_game = $conn->prepare("INSERT INTO games (name, status, api_supplier_id, api_operator_code, exchange_rate, default_markup) VALUES (?, 'active', ?, ?, ?, ?)");
                
                // /// START: ຈຸດທີ່ແກ້ໄຂ ///
                // ປ່ຽນ "sisddi" (6 ໂຕ) ເປັນ "sisdd" (5 ໂຕ) ໃຫ້ກົງກັບຈຳນວນໂຕປ່ຽນ (?) ໃນ SQL
                $stmt_game->bind_param("sisdd", $game_name, $supplier_id, $operator_code, $exchange_rate, $default_markup);
                // /// END: ຈຸດທີ່ແກ້ໄຂ ///

                $stmt_game->execute();
                $game_id = $conn->insert_id;
                $stmt_game->close();
                $success_message .= "New game '$game_name' imported successfully. <br>";
            }
            $stmt_check_game->close();

            $stmt_insert_pkg = $conn->prepare("REPLACE INTO game_packages (game_id, name, price, cost_price, api_product_code) VALUES (?, ?, ?, ?, ?)");
            $upsert_count = 0;
            foreach($packages as $api_code => $pkg_data) {
                if(isset($pkg_data['import'])) {
                    $cost_price_lak = (float)$pkg_data['cost_price'];
                    $selling_price_lak = (float)$pkg_data['selling_price'];

                    $stmt_insert_pkg->bind_param("isdds", $game_id, $pkg_data['name'], $selling_price_lak, $cost_price_lak, $api_code);
                    $stmt_insert_pkg->execute();
                    $upsert_count++;
                }
            }
            $stmt_insert_pkg->close();
            $conn->commit();
            $success_message .= "Successfully inserted/updated $upsert_count packages.";
        } catch (Exception $e) { $conn->rollback(); $error_message = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage(); }
    }
}

require_once 'admin_header.php';
$suppliers = $conn->query("SELECT id, name FROM api_suppliers WHERE is_active = 1");
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Import Game from Cache</h1>
    <?php if(!empty($success_message)): ?> <div class="alert alert-success"><?php echo $success_message; ?></div> <?php endif; ?>
    <?php if(!empty($error_message)): ?> <div class="alert alert-danger"><?php echo $error_message; ?></div> <?php endif; ?>
    <div class="card shadow mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">ຂັ້ນຕອນ: ເລືອກເກມ ແລະ ແພັກເກັດຈາກ Cache</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label fw-bold">1. ເລືອກ Supplier:</label><select id="supplierSelect" class="form-select"><option value="">-- ກະລຸນາເລືອກ --</option><?php if ($suppliers && $suppliers->num_rows > 0): mysqli_data_seek($suppliers, 0); while($row = $suppliers->fetch_assoc()): ?><option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option><?php endwhile; endif; ?></select></div>
                <div class="col-md-3"><label class="form-label fw-bold">2. ເລືອກໝວດໝູ່:</label><select id="categorySelect" class="form-select" disabled></select></div>
                <div class="col-md-3"><label class="form-label fw-bold">3. ເລືອກເກມ:</label><select id="operatorSelect" class="form-select" disabled></select></div>
                <div class="col-md-3"><label class="form-label fw-bold">4. ເລືອກປະເພດ:</label><select id="jenisSelect" class="form-select" disabled></select></div>
            </div>
        </div>
    </div>
    <div id="packagesSection" style="display:none;">
        <form method="POST" action="import_from_cache.php">
            <div class="card shadow mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">ກຳຫນົດລາຄາຂາຍ ແລະ ນຳເຂົ້າ</h6></div>
                <div class="card-body">
                    <div class="row bg-light p-3 rounded mb-4 border">
                         <div class="col-md-6 mb-3">
                            <label for="exchangeRateInput" class="form-label fw-bold">5. ປ້ອນອັດຕາແລກປ່ຽນ (ຈາກ IDR):</label>
                            <input type="text" id="exchangeRateInput" name="exchange_rate" class="form-control" value="0.0014">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="markupInput" class="form-label fw-bold">6. ປ້ອນ % ກຳໄລເລີ່ມຕົ້ນ (ສຳລັບທຸກລາຍການ):</label>
                            <input type="number" id="markupInput" name="default_markup" class="form-control" value="20">
                        </div>
                    </div>
                    <div id="packagesContainer" class="table-responsive"></div>
                    <div id="hiddenInputsContainer"></div>
                    <button type="submit" name="import_game" class="btn btn-primary mt-3"><i class="fas fa-cloud-download-alt"></i> ນຳເຂົ້າ/ອັບເດດ ເກມນີ້</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const supplierSelect = document.getElementById('supplierSelect');
    const categorySelect = document.getElementById('categorySelect');
    const operatorSelect = document.getElementById('operatorSelect');
    const jenisSelect = document.getElementById('jenisSelect');
    const packagesSection = document.getElementById('packagesSection');
    const packagesContainer = document.getElementById('packagesContainer');
    const hiddenInputsContainer = document.getElementById('hiddenInputsContainer');
    const exchangeRateInput = document.getElementById('exchangeRateInput');
    const markupInput = document.getElementById('markupInput');

    function apiFetch(action, params = {}) {
        const url = new URL(window.location.href.split('?')[0]);
        url.searchParams.set('action', action);
        url.searchParams.set('supplier_id', supplierSelect.value);
        for(const key in params) { url.searchParams.set(key, params[key]); }
        return fetch(url).then(res => res.json());
    }
    function populateSelect(selectEl, data, valueKey, textKey, defaultText) {
        selectEl.innerHTML = `<option value="">${defaultText}</option>`;
        if (data && Array.isArray(data)) { data.forEach(item => { selectEl.innerHTML += `<option value="${item[valueKey]}">${item[textKey]}</option>`; }); }
    }
    function resetSelect(selectEl, defaultText) {
        selectEl.innerHTML = `<option value="">${defaultText}</option>`;
        selectEl.disabled = true;
    }

    function updateProfit(row) {
        const costPriceLAK = parseFloat(row.querySelector('input[name*="[cost_price]"]').value) || 0;
        const sellingPrice = parseFloat(row.querySelector('.selling-price-input').value) || 0;
        const profit = sellingPrice - costPriceLAK;
        
        const profitCell = row.querySelector('.profit-cell');
        profitCell.innerText = profit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        profitCell.style.color = profit >= 0 ? 'green' : 'red';
    }

    function recalculateRow(row) {
        const costPriceIDR = parseFloat(row.dataset.costIdr);
        const exchangeRate = parseFloat(exchangeRateInput.value) || 0.0014;
        const markupPercent = parseInt(row.querySelector('.markup-row-input').value) || 20;
        const sellingPriceInput = row.querySelector('.selling-price-input');
        
        const costPriceLAK = costPriceIDR * exchangeRate;
        const sellingPrice = costPriceLAK * (1 + (markupPercent / 100));
        const suggestedPrice = Math.ceil(sellingPrice / 1000) * 1000;

        row.querySelector('.cost-lak-cell').innerText = costPriceLAK.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        row.querySelector('input[name*="[cost_price]"]').value = costPriceLAK.toFixed(2);
        sellingPriceInput.value = suggestedPrice;
        
        updateProfit(row);
    }
    
    function renderPackagesTable(packages) {
        let gameName = operatorSelect.options[operatorSelect.selectedIndex].text;
        let operatorCode = operatorSelect.value;
        let defaultMarkup = parseInt(markupInput.value) || 20;

        let tableHTML = `<table class="table table-bordered table-hover align-middle">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="checkAll" checked></th>
                                    <th>ຊື່ແພັກເກັດ</th>
                                    <th class="text-end">ຕົ້ນທຶນ (IDR)</th>
                                    <th class="text-end">ຕົ້ນທຶນ (LAK)</th>
                                    <th style="width: 120px;">% ຂາຍ</th>
                                    <th style="width: 150px;">ລາຄາຂາຍ (LAK)</th>
                                    <th class="text-end" style="width: 150px;">ກຳໄລ (LAK)</th>
                                </tr>
                            </thead>
                            <tbody>`;
        
        if (packages.length === 0) { 
            tableHTML += `<tr><td colspan="7" class="text-center">ບໍ່ພົບແພັກເກັດ.</td></tr>`; 
        } else {
            packages.forEach(pkg => {
                let costPriceIDR = parseInt(pkg.cost_price);
                tableHTML += `<tr data-cost-idr="${costPriceIDR}">
                    <td><input type="checkbox" name="packages[${pkg.product_code}][import]" checked></td>
                    <td><input type="hidden" name="packages[${pkg.product_code}][name]" value="${pkg.product_name}">${pkg.product_name}</td>
                    <td class="text-end">${costPriceIDR.toLocaleString()}</td>
                    <td class="text-end cost-lak-cell">...</td>
                    <td><input type="number" class="form-control form-control-sm markup-row-input" value="${defaultMarkup}"></td>
                    <td>
                        <input type="hidden" name="packages[${pkg.product_code}][cost_price]" value="0">
                        <input type="number" class="form-control form-control-sm selling-price-input" name="packages[${pkg.product_code}][selling_price]" value="0" required>
                    </td>
                    <td class="text-end profit-cell fw-bold">...</td>
                </tr>`;
            });
        }
        tableHTML += `</tbody></table>`;
        packagesContainer.innerHTML = tableHTML;
        hiddenInputsContainer.innerHTML = `<input type="hidden" name="game_name" value="${gameName}"><input type="hidden" name="operator_code" value="${operatorCode}"><input type="hidden" name="supplier_id" value="${supplierSelect.value}">`;
        
        document.querySelectorAll('#packagesContainer tbody tr').forEach(row => recalculateRow(row));
        document.getElementById('checkAll').addEventListener('change', e => document.querySelectorAll('input[name*="[import]"]').forEach(c => c.checked = e.target.checked));
    }

    exchangeRateInput.addEventListener('input', function() {
        document.querySelectorAll('#packagesContainer tbody tr').forEach(row => recalculateRow(row));
    });

    markupInput.addEventListener('input', function() {
        const newMarkup = this.value;
        document.querySelectorAll('.markup-row-input').forEach(input => input.value = newMarkup);
        document.querySelectorAll('#packagesContainer tbody tr').forEach(row => recalculateRow(row));
    });
    
    packagesContainer.addEventListener('input', function(e) {
        if (e.target.classList.contains('markup-row-input')) {
            recalculateRow(e.target.closest('tr'));
        }
        if (e.target.classList.contains('selling-price-input')) {
            updateProfit(e.target.closest('tr'));
        }
    });

    supplierSelect.addEventListener('change', function() {
        resetSelect(categorySelect, '--'); resetSelect(operatorSelect, '--'); resetSelect(jenisSelect, '--');
        packagesSection.style.display = 'none';
        if (!this.value) return;
        categorySelect.disabled = false; categorySelect.innerHTML = '<option>Loading...</option>';
        apiFetch('get_categories').then(result => {
            if (result.success) populateSelect(categorySelect, result.data, 'val', 'txt', '-- ເລືອກໝວດໝູ່ --');
        });
    });
    categorySelect.addEventListener('change', function() {
        resetSelect(operatorSelect, '--'); resetSelect(jenisSelect, '--');
        packagesSection.style.display = 'none';
        if (!this.value) return;
        operatorSelect.disabled = false; operatorSelect.innerHTML = '<option>Loading...</option>';
        apiFetch('get_operators', { category: this.value }).then(result => {
            if (result.success) populateSelect(operatorSelect, result.data, 'val', 'txt', '-- ເລືອກເກມ --');
        });
    });
    operatorSelect.addEventListener('change', function() {
        resetSelect(jenisSelect, '--');
        packagesSection.style.display = 'none';
        if (!this.value) return;
        jenisSelect.disabled = false; jenisSelect.innerHTML = '<option>Loading...</option>';
        apiFetch('get_jenis', { brand: this.value }).then(result => {
            if (result.success) populateSelect(jenisSelect, result.data, 'val', 'txt', '-- ເລືອກປະເພດ --');
        });
    });
    jenisSelect.addEventListener('change', function() {
        packagesSection.style.display = 'none';
        if (!this.value) return;
        packagesContainer.innerHTML = `<div class="text-center p-4"><div class="spinner-border"></div></div>`;
        packagesSection.style.display = 'block';
        apiFetch('get_packages', { jenis: this.value, brand: operatorSelect.value }).then(result => {
            if (result.success && Array.isArray(result.data)) {
                renderPackagesTable(result.data);
            }
        });
    });
});
</script>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
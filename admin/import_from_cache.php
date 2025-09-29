<?php
// File: admin/import_from_cache.php (Version with Editable Name & Draggable Order)
require_once 'db_connect.php';
require_once 'auth_check.php';

// --- AJAX Action Handler (No changes here) ---
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

// --- Form Submission Handler (Updated for display_order) ---
$success_message = ""; $error_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_game'])) {
    $game_name = $_POST['game_name'];
    $operator_code = $_POST['operator_code'];
    $supplier_id = $_POST['supplier_id'];
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
                $game_id = $game_result->fetch_assoc()['id'];
            } else {
                $stmt_game = $conn->prepare("INSERT INTO games (name, status, api_supplier_id, api_operator_code) VALUES (?, 'active', ?, ?)");
                $stmt_game->bind_param("sis", $game_name, $supplier_id, $operator_code);
                $stmt_game->execute();
                $game_id = $conn->insert_id;
                $stmt_game->close();
            }
            $stmt_check_game->close();
            
            // --- CHANGE 1: Update SQL query to include display_order ---
            $stmt_insert_pkg = $conn->prepare("REPLACE INTO game_packages (game_id, name, price, cost_price, api_product_code, display_order) VALUES (?, ?, ?, ?, ?, ?)");
            $upsert_count = 0;
            $order_index = 0; // To keep track of the new order
            foreach($packages as $api_code => $pkg_data) {
                if(isset($pkg_data['import'])) {
                    $order_index++;
                    // --- CHANGE 2: Bind display_order parameter ---
                    $stmt_insert_pkg->bind_param("isddsi", $game_id, $pkg_data['name'], $pkg_data['selling_price'], $pkg_data['cost_price_lak'], $api_code, $order_index);
                    $stmt_insert_pkg->execute();
                    $upsert_count++;
                }
            }
            $stmt_insert_pkg->close();
            $conn->commit();
            $success_message = "ນຳເຂົ້າ/ອັບເດດສຳເລັດ " . $upsert_count . " ແພັກເກັດ.";

        } catch (Exception $e) { $conn->rollback(); $error_message = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage(); }
    }
}

require_once 'admin_header.php';
$suppliers = $conn->query("SELECT id, name, exchange_rate FROM api_suppliers WHERE is_active = 1");
?>
<style>
    /* Add styles for drag-and-drop functionality */
    .draggable-row { cursor: move; }
    .dragging { opacity: 0.5; background: #f0f8ff; }
</style>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Import Game from Cache</h1>
    <?php if(!empty($success_message)): ?> <div class="alert alert-success"><?php echo $success_message; ?></div> <?php endif; ?>
    <?php if(!empty($error_message)): ?> <div class="alert alert-danger"><?php echo $error_message; ?></div> <?php endif; ?>
    <div class="card shadow mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">ຂັ້ນຕອນ: ເລືອກເກມ ແລະ ແພັກເກັດຈາກ Cache</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label fw-bold">1. ເລືອກ Supplier:</label>
                    <select id="supplierSelect" class="form-select">
                        <option value="">-- ກະລຸນາເລືອກ --</option>
                        <?php if ($suppliers && $suppliers->num_rows > 0): mysqli_data_seek($suppliers, 0); while($row = $suppliers->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>" data-exchange-rate="<?php echo htmlspecialchars($row['exchange_rate'] ?: '1.0'); ?>">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label fw-bold">2. ເລືອກໝວດໝູ່:</label><select id="categorySelect" class="form-select" disabled></select></div>
                <div class="col-md-3"><label class="form-label fw-bold">3. ເລືອກເກມ:</label><select id="operatorSelect" class="form-select" disabled></select></div>
                <div class="col-md-3"><label class="form-label fw-bold">4. ເລືອກປະເພດ:</label><select id="jenisSelect" class="form-select" disabled></select></div>
            </div>
        </div>
    </div>
    <div id="packagesSection" style="display:none;">
        <form method="POST" action="import_from_cache.php">
            <div class="card shadow mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">ກຳຫນົດລາຄາຂາຍ ແລະ ນຳເຂົ້າ (ສາມາດລາກເພື່ອຈັດລຳດັບໄດ້)</h6></div>
                <div class="card-body">
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
    // --- Elements ---
    const supplierSelect = document.getElementById('supplierSelect');
    const categorySelect = document.getElementById('categorySelect');
    const operatorSelect = document.getElementById('operatorSelect');
    const jenisSelect = document.getElementById('jenisSelect');
    const packagesSection = document.getElementById('packagesSection');
    const packagesContainer = document.getElementById('packagesContainer');
    const hiddenInputsContainer = document.getElementById('hiddenInputsContainer');

    let currentExchangeRate = 1.0;
    
    // --- Helper Functions (No changes here) ---
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
    function resetSelect(selectEl, defaultText = '--') {
        selectEl.innerHTML = `<option value="">${defaultText}</option>`;
        selectEl.disabled = true;
    }

    // --- Event Listeners (No changes here) ---
    supplierSelect.addEventListener('change', function() {
        resetSelect(categorySelect); resetSelect(operatorSelect); resetSelect(jenisSelect);
        packagesSection.style.display = 'none';
        if (!this.value) return;
        const selectedOption = this.options[this.selectedIndex];
        currentExchangeRate = parseFloat(selectedOption.dataset.exchangeRate) || 1.0;
        categorySelect.disabled = false; categorySelect.innerHTML = '<option>Loading...</option>';
        apiFetch('get_categories').then(result => {
            if (result.success) populateSelect(categorySelect, result.data, 'val', 'txt', '-- ເລືອກໝວດໝູ່ --');
        });
    });
    categorySelect.addEventListener('change', function() {
        resetSelect(operatorSelect); resetSelect(jenisSelect);
        packagesSection.style.display = 'none';
        if (!this.value) return;
        operatorSelect.disabled = false; operatorSelect.innerHTML = '<option>Loading...</option>';
        apiFetch('get_operators', { category: this.value }).then(result => {
            if (result.success) populateSelect(operatorSelect, result.data, 'val', 'txt', '-- ເລືອກເກມ --');
        });
    });
    operatorSelect.addEventListener('change', function() {
        resetSelect(jenisSelect);
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

    // --- Main Logic: Table Rendering and Calculation ---
    function renderPackagesTable(packages) {
        let gameName = operatorSelect.options[operatorSelect.selectedIndex].text;
        let operatorCode = operatorSelect.value;
        
        let tableHTML = `<table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th style="width: 3%;"></th>
                    <th style="width: 5%;"><input type="checkbox" id="checkAll" checked></th>
                    <th>ຊື່ແພັກເກັດ</th>
                    <th>ຕົ້ນທຶນ (API)</th>
                    <th>ຕົ້ນທຶນ (LAK)</th>
                    <th style="width: 8%;">ກຳໄລ (%)</th>
                    <th>ລາຄາຂາຍ (LAK)</th>
                    <th>ກຳໄລ (LAK)</th>
                </tr>
            </thead>
            <tbody id="package-list-body">`;

        if (packages.length === 0) {
            tableHTML += `<tr><td colspan="8" class="text-center">ບໍ່ພົບແພັກເກັດ.</td></tr>`;
        } else {
            packages.forEach(pkg => {
                let costPriceAPI = parseFloat(pkg.cost_price);
                let costPriceLAK = costPriceAPI * currentExchangeRate;
                let markupPercent = 15;
                let sellingPrice = Math.ceil((costPriceLAK * (1 + markupPercent / 100)) / 1000) * 1000;
                let profitLAK = sellingPrice - costPriceLAK;

                // --- CHANGE 3: Add drag handle and make name editable ---
                tableHTML += `<tr class="draggable-row" draggable="true">
                    <td class="text-center"><i class="fas fa-bars text-muted"></i></td>
                    <td>
                        <input type="checkbox" class="form-check-input" name="packages[${pkg.product_code}][import]" checked>
                    </td>
                    <td><input type="text" class="form-control" name="packages[${pkg.product_code}][name]" value="${pkg.product_name}"></td>
                    <td>${costPriceAPI.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    <td class="cost-lak-cell">
                        ${costPriceLAK.toLocaleString('en-US', {minimumFractionDigits: 2})}
                        <input type="hidden" name="packages[${pkg.product_code}][cost_price_lak]" value="${costPriceLAK.toFixed(2)}">
                    </td>
                    <td><input type="number" class="form-control markup-percent" value="${markupPercent}" step="1"></td>
                    <td><input type="number" class="form-control selling-price" name="packages[${pkg.product_code}][selling_price]" value="${sellingPrice}" step="1000"></td>
                    <td class="profit-lak-cell">${profitLAK.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                </tr>`;
            });
        }
        tableHTML += `</tbody></table>`;
        packagesContainer.innerHTML = tableHTML;
        hiddenInputsContainer.innerHTML = `<input type="hidden" name="game_name" value="${gameName}"><input type="hidden" name="operator_code" value="${operatorCode}"><input type="hidden" name="supplier_id" value="${supplierSelect.value}">`;
        
        document.getElementById('checkAll').addEventListener('change', e => {
            document.querySelectorAll('input[name*="[import]"]').forEach(c => c.checked = e.target.checked);
        });

        // --- NEW: Add Event Listeners for Calculation & Drag-Drop ---
        addTableEventListeners();
    }

    function addTableEventListeners() {
        const packageBody = document.getElementById('package-list-body');
        if (!packageBody) return;

        // Dynamic calculation logic
        packageBody.addEventListener('input', function(e) {
            const row = e.target.closest('tr');
            if (!row) return;

            const costPriceLAK = parseFloat(row.querySelector('input[name*="[cost_price_lak]"]').value);
            const markupInput = row.querySelector('.markup-percent');
            const sellingPriceInput = row.querySelector('.selling-price');
            const profitCell = row.querySelector('.profit-lak-cell');

            if (e.target.classList.contains('markup-percent')) {
                let markup = parseFloat(e.target.value) || 0;
                let newSellingPrice = Math.ceil((costPriceLAK * (1 + markup / 100)) / 1000) * 1000;
                sellingPriceInput.value = newSellingPrice;
                profitCell.textContent = (newSellingPrice - costPriceLAK).toLocaleString('en-US', {minimumFractionDigits: 2});
            }

            if (e.target.classList.contains('selling-price')) {
                let sellingPrice = parseFloat(e.target.value) || 0;
                if (costPriceLAK > 0) {
                    let newMarkup = ((sellingPrice / costPriceLAK) - 1) * 100;
                    markupInput.value = newMarkup.toFixed(2);
                }
                profitCell.textContent = (sellingPrice - costPriceLAK).toLocaleString('en-US', {minimumFractionDigits: 2});
            }
        });

        // Drag and Drop Logic
        let draggingEle;
        const rows = packageBody.querySelectorAll('.draggable-row');
        rows.forEach(row => {
            row.addEventListener('dragstart', function() {
                draggingEle = this;
                this.classList.add('dragging');
            });
            row.addEventListener('dragend', function() {
                this.classList.remove('dragging');
            });
        });

        packageBody.addEventListener('dragover', function(e) {
            e.preventDefault();
            const afterElement = getDragAfterElement(packageBody, e.clientY);
            if (afterElement == null) {
                packageBody.appendChild(draggingEle);
            } else {
                packageBody.insertBefore(draggingEle, afterElement);
            }
        });
    }

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.draggable-row:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
});
</script>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
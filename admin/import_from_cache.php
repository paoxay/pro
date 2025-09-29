<?php
// File: admin/import_from_cache.php (Final Version with async/await fix)
require_once 'db_connect.php';
require_once 'auth_check.php';

// --- AJAX ACTION HANDLER (No changes) ---
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
        case 'get_supplier_details':
            $sql = "SELECT exchange_rate, default_markup FROM api_suppliers WHERE id = ?";
            $types = "i"; $params[] = $supplier_id;
            break;
    }
    if(!empty($sql)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if(!empty($types)) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($_GET['action'] == 'get_supplier_details') {
                $data = $result->fetch_assoc();
            } else { while ($row = $result->fetch_assoc()) { $data[] = $row; } }
            $stmt->close();
        }
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// --- FORM SUBMISSION HANDLER (No changes) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_game'])) {
    // This part is unchanged.
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
                <div class="col-md-3"><label class="form-label fw-bold">1. ເລືອກ Supplier:</label>
                    <select id="supplierSelect" class="form-select">
                        <option value="">-- ກະລຸນາເລືອກ --</option>
                        <?php if ($suppliers && $suppliers->num_rows > 0): ?>
                            <?php while($row = $suppliers->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label fw-bold">2. ເລືອກໝວດໝູ່:</label><select id="categorySelect" class="form-select" disabled></select></div>
                <div class="col-md-3"><label class="form-label fw-bold">3. ເລືອກເກມ:</label><select id="operatorSelect" class="form-select" disabled></select></div>
                <div class="col-md-3"><label class="form-label fw-bold">4. ເລືອກປະເພດ:</label><select id="jenisSelect" class="form-select" disabled></select></div>
            </div>
            <div id="debug-info" class="mt-3 p-2 bg-light border rounded" style="display: none;"></div>
        </div>
    </div>
    <div id="packagesSection" style="display:none;">
        <form method="POST" action="import_from_cache.php">
            <div class="card shadow mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">ກຳຫນົດລາຄາຂາຍ ແລະ ນຳເຂົ້າ</h6></div>
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
    const supplierSelect = document.getElementById('supplierSelect');
    const categorySelect = document.getElementById('categorySelect');
    const operatorSelect = document.getElementById('operatorSelect');
    const jenisSelect = document.getElementById('jenisSelect');
    const packagesSection = document.getElementById('packagesSection');
    const packagesContainer = document.getElementById('packagesContainer');
    const hiddenInputsContainer = document.getElementById('hiddenInputsContainer');
    const debugInfo = document.getElementById('debug-info');
    
    let currentSupplierDetails = { exchange_rate: 1.0, default_markup: 15 };

    // Converted to async function to use await
    async function apiFetch(action, params = {}) {
        const url = new URL(window.location.href.split('?')[0]);
        url.searchParams.set('action', action);
        url.searchParams.set('supplier_id', supplierSelect.value);
        for(const key in params) { url.searchParams.set(key, params[key]); }
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    }
    
    function populateSelect(selectEl, data, valueKey, textKey, defaultText) {
        selectEl.innerHTML = `<option value="">${defaultText}</option>`;
        if (data && Array.isArray(data)) { data.forEach(item => { selectEl.innerHTML += `<option value="${item[valueKey]}">${item[textKey]}</option>`; }); }
    }

    function resetSelect(selectEl, defaultText) {
        selectEl.innerHTML = `<option value="">${defaultText}</option>`;
        selectEl.disabled = true;
    }

    // ***** START: JAVASCRIPT FIX USING ASYNC/AWAIT *****
    supplierSelect.addEventListener('change', async function() {
        resetSelect(categorySelect, '--'); resetSelect(operatorSelect, '--'); resetSelect(jenisSelect, '--');
        packagesSection.style.display = 'none';
        debugInfo.style.display = 'none';
        if (!this.value) return;

        try {
            // Step 1: WAIT for supplier details to be fetched
            const detailsResult = await apiFetch('get_supplier_details');
            if (detailsResult.success && detailsResult.data && detailsResult.data.exchange_rate) {
                currentSupplierDetails.exchange_rate = parseFloat(detailsResult.data.exchange_rate);
                currentSupplierDetails.default_markup = parseInt(detailsResult.data.default_markup);
            } else {
                // Reset to default if fetch fails
                currentSupplierDetails.exchange_rate = 1.0;
                currentSupplierDetails.default_markup = 15;
            }
            
            debugInfo.style.display = 'block';
            debugInfo.innerHTML = `<strong>Debug Info:</strong> Current Exchange Rate = <strong>${currentSupplierDetails.exchange_rate}</strong> | Default Markup = <strong>${currentSupplierDetails.default_markup}%</strong>`;

            // Step 2: Now that we have details, WAIT for categories
            categorySelect.disabled = false; 
            categorySelect.innerHTML = '<option>Loading...</option>';
            const categoryResult = await apiFetch('get_categories');
            if (categoryResult.success) {
                populateSelect(categorySelect, categoryResult.data, 'val', 'txt', '-- ເລືອກໝວດໝູ່ --');
            }
        } catch (error) {
            console.error("Error during supplier selection:", error);
            debugInfo.innerHTML = "Error fetching supplier details or categories.";
            debugInfo.style.display = 'block';
        }
    });
    // ***** END JAVASCRIPT FIX *****
    
    // The rest of the event listeners can be async as well for consistency
    categorySelect.addEventListener('change', async function() {
        resetSelect(operatorSelect, '--'); resetSelect(jenisSelect, '--');
        packagesSection.style.display = 'none';
        if (!this.value) return;
        operatorSelect.disabled = false; operatorSelect.innerHTML = '<option>Loading...</option>';
        const result = await apiFetch('get_operators', { category: this.value });
        if (result.success) populateSelect(operatorSelect, result.data, 'val', 'txt', '-- ເລືອກເກມ --');
    });

    operatorSelect.addEventListener('change', async function() {
        resetSelect(jenisSelect, '--');
        packagesSection.style.display = 'none';
        if (!this.value) return;
        jenisSelect.disabled = false; jenisSelect.innerHTML = '<option>Loading...</option>';
        const result = await apiFetch('get_jenis', { brand: this.value });
        if (result.success) populateSelect(jenisSelect, result.data, 'val', 'txt', '-- ເລືອກປະເພດ --');
     });

    jenisSelect.addEventListener('change', async function() {
        packagesSection.style.display = 'none';
        if (!this.value) return;
        packagesContainer.innerHTML = `<div class="text-center p-4"><div class="spinner-border"></div></div>`;
        packagesSection.style.display = 'block';
        const result = await apiFetch('get_packages', { jenis: this.value, brand: operatorSelect.value });
        if (result.success && Array.isArray(result.data)) {
            renderPackagesTable(result.data, currentSupplierDetails);
        }
    });

    function renderPackagesTable(packages, supplierDetails) {
        let gameName = operatorSelect.options[operatorSelect.selectedIndex].text;
        let operatorCode = operatorSelect.value;
        let tableHTML = `<table class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 5%;"><input type="checkbox" id="checkAll" checked></th>
                    <th style="width: 30%;">ຊື່ແພັກເກັດ</th>
                    <th>ຕົ້ນທຶນ (API)</th>
                    <th>ຕົ້ນທຶນ (LAK)</th>
                    <th style="width: 10%;">ກຳໄລ (%)</th>
                    <th style="width: 15%;">ລາຄາຂາຍ (LAK)</th>
                    <th>ກຳໄລ (LAK)</th>
                </tr>
            </thead>
            <tbody>`;
        
        if (packages.length === 0) { 
            tableHTML += `<tr><td colspan="7" class="text-center">ບໍ່ພົບແພັກເກັດ.</td></tr>`; 
        } else {
            packages.forEach(pkg => {
                let apiCost = parseFloat(pkg.cost_price);
                let costPriceInLAK = apiCost * supplierDetails.exchange_rate; 
                let markupPercent = supplierDetails.default_markup;
                let suggestedPrice = Math.ceil(costPriceInLAK * (1 + (markupPercent / 100)) / 1000) * 1000;
                if (suggestedPrice < costPriceInLAK) { suggestedPrice = Math.ceil(costPriceInLAK / 1000) * 1000; }
                let profit = suggestedPrice - costPriceInLAK;

                tableHTML += `
                    <tr data-product-code="${pkg.product_code}">
                        <td><input type="checkbox" name="packages[${pkg.product_code}][import]" checked></td>
                        <td><input type="text" class="form-control form-control-sm" name="packages[${pkg.product_code}][name]" value="${pkg.product_name}"></td>
                        <td>${apiCost.toLocaleString('en-US')}</td>
                        <td class="cost-lak" data-cost-lak="${costPriceInLAK}">${costPriceInLAK.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td><input type="number" class="form-control form-control-sm profit-percent-input" value="${markupPercent}" step="1"></td>
                        <td>
                            <input type="hidden" name="packages[${pkg.product_code}][cost_price]" value="${Math.ceil(costPriceInLAK)}">
                            <input type="number" class="form-control form-control-sm selling-price-input" name="packages[${pkg.product_code}][selling_price]" value="${suggestedPrice}" required>
                        </td>
                        <td class="profit-lak-cell fw-bold" style="color: ${profit < 0 ? 'red' : 'green'};">${profit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    </tr>`;
            });
        }
        tableHTML += `</tbody></table>`;
        packagesContainer.innerHTML = tableHTML;
        hiddenInputsContainer.innerHTML = `<input type="hidden" name="game_name" value="${gameName}"><input type="hidden" name="operator_code" value="${operatorCode}"><input type="hidden" name="supplier_id" value="${supplierSelect.value}">`;
        
        const checkAll = document.getElementById('checkAll');
        if(checkAll) {
            checkAll.addEventListener('change', e => document.querySelectorAll('input[name*="[import]"]').forEach(c => c.checked = e.target.checked));
        }
    }

    function updateRowPrices(targetElement) {
        const row = targetElement.closest('tr');
        const costLak = parseFloat(row.querySelector('.cost-lak').dataset.costLak);
        const percentInput = row.querySelector('.profit-percent-input');
        const priceInput = row.querySelector('.selling-price-input');
        const profitCell = row.querySelector('.profit-lak-cell');

        if (targetElement.classList.contains('profit-percent-input')) {
            const newPercent = parseFloat(percentInput.value) || 0;
            let newPrice = costLak * (1 + (newPercent / 100));
            newPrice = Math.ceil(newPrice / 1000) * 1000;
            priceInput.value = newPrice;
        }

        const currentPrice = parseFloat(priceInput.value) || 0;
        const newProfit = currentPrice - costLak;
        
        if (targetElement.classList.contains('selling-price-input')) {
            if (costLak > 0) {
                 const newPercent = ((currentPrice / costLak) - 1) * 100;
                 percentInput.value = newPercent.toFixed(2);
            } else {
                 percentInput.value = 0;
            }
        }
        
        profitCell.textContent = newProfit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        profitCell.style.color = newProfit < 0 ? 'red' : 'green';
    }

    packagesContainer.addEventListener('input', function(e) {
        if (e.target.classList.contains('profit-percent-input') || e.target.classList.contains('selling-price-input')) {
            updateRowPrices(e.target);
        }
    });
});
</script>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
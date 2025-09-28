<?php
// File: admin/sync_page.php (Final Version with Live Progress Log)
require_once 'db_connect.php';
require_once 'auth_check.php';

// Helper function to call the API
function callAPI($supplier, $endpoint, $extra_params = []) {
    $member_code = $supplier['member_code'] ?? ''; $signature = $supplier['signature'] ?? ''; $base_url = $supplier['api_base_url'] ?? '';
    if (empty($member_code) || empty($signature) || empty($base_url)) { return ['success' => false, 'message' => 'Supplier credentials incomplete.']; }
    $params = array_merge(['member_code' => $member_code, 'signature' => $signature], $extra_params);
    $url = rtrim($base_url, '/') . '/member/' . $endpoint . '?' . http_build_query($params);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url); curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); curl_setopt($ch, CURLOPT_USERAGENT, 'LaoTopup/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response_body = curl_exec($ch); $curl_error = curl_error($ch); curl_close($ch);
    if ($curl_error) { return ['success' => false, 'message' => 'cURL Error: ' . $curl_error]; }
    $decoded_response = json_decode($response_body, true);
    if (json_last_error() !== JSON_ERROR_NONE || (isset($decoded_response['status']) && $decoded_response['status'] !== 1)) {
        return ['success' => false, 'message' => $decoded_response['message'] ?? 'API request failed.'];
    }
    return ['success' => true, 'data' => $decoded_response['data'] ?? []];
}

// This block handles AJAX requests for dropdowns and exits
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $supplier_id = $_GET['supplier_id'] ?? 0;
    $stmt = $conn->prepare("SELECT * FROM api_suppliers WHERE id = ?"); $stmt->bind_param("i", $supplier_id); $stmt->execute();
    $supplier = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$supplier) { die(json_encode(['success' => false, 'message' => 'Supplier not found.'])); }
    
    $response = ['success' => false, 'message' => 'Invalid action'];
    if ($_GET['action'] == 'get_categories') { $response = callAPI($supplier, 'produk/category/list'); }
    if ($_GET['action'] == 'get_operators') { $response = callAPI($supplier, 'produk/operator/list', ['id' => $_GET['category_id']]); }
    echo json_encode($response);
    exit;
}

// This block handles the main Sync form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_operators'])) {
    require_once 'admin_header.php'; // Show header for the log page
    echo '<div class="container-fluid"><h1 class="h3 mb-4 text-gray-800">Syncing Products...</h1>';
    echo '<div class="card shadow"><div class="card-body"><pre class="bg-dark text-white p-3 rounded" style="max-height: 600px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;">';
    
    // Force PHP to send output to the browser in real-time
    if (ob_get_level()) ob_end_flush();
    ob_implicit_flush(true);
    
    set_time_limit(600); // Set max execution time to 10 minutes
    
    $supplier_id = $_POST['supplier_id'];
    $operators_to_sync = $_POST['operators'] ?? [];
    $stmt_supplier = $conn->prepare("SELECT * FROM api_suppliers WHERE id = ?"); $stmt_supplier->bind_param("i", $supplier_id); $stmt_supplier->execute();
    $supplier = $stmt_supplier->get_result()->fetch_assoc();

    if ($supplier && !empty($operators_to_sync)) {
        echo "Starting sync for: " . htmlspecialchars($supplier['name']) . "\n\n";
        
        $total_synced = 0;
        $conn->begin_transaction();
        try {
            // Using REPLACE INTO is a simple UPSERT (update/insert) for the cache
            $stmt_insert = $conn->prepare("REPLACE INTO api_cache_products (api_supplier_id, product_code, product_name, category, brand, jenis, cost_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($operators_to_sync as $operator_id => $operator_data) {
                $operator_name = $operator_data['name'] ?? 'Unknown';
                echo "\n[OPERATOR: $operator_name]\n";
                flush();
                
                $jenis_response = callAPI($supplier, 'produk/jenis/list', ['id' => $operator_id]);
                if ($jenis_response['success'] && !empty($jenis_response['data'])) {
                    echo "  - Found " . count($jenis_response['data']) . " 'jenis' types. Fetching packages...\n";
                    flush();
                    foreach ($jenis_response['data'] as $jenis) {
                         $packages_response = callAPI($supplier, 'produk/list', ['id_jenis' => $jenis['id']]);
                         if ($packages_response['success'] && !empty($packages_response['data'])) {
                             foreach($packages_response['data'] as $package) {
                                $stmt_insert->bind_param("isssssd", $supplier_id, $package['code'], $package['nama_produk'], $package['category_name'], $package['operator_produk'], $package['jenis_name'], $package['price']);
                                $stmt_insert->execute();
                                $total_synced++;
                             }
                         }
                    }
                }
                echo "  -> Finished syncing for Operator: $operator_name.\n";
                flush();
            }
            $stmt_insert->close();
            $conn->commit();
            echo "\n----------------------------------------\n";
            echo "SYNC COMPLETE!\n";
            echo "Successfully synced/updated $total_synced products.\n";

        } catch (Exception $e) {
            $conn->rollback();
            echo "AN ERROR OCCURRED: " . $e->getMessage() . "\n";
        }
    } else {
        echo "ERROR: No operators were selected to sync.";
    }
    echo '</pre><a href="sync_page.php" class="btn btn-primary mt-3">Back to Sync Page</a></div></div></div>';
    // We add a final exit to stop the script after the log is shown
    exit;
}

// --- This part runs for the normal page load UI ---
require_once 'admin_header.php';
$suppliers = $conn->query("SELECT id, name FROM api_suppliers WHERE is_active = 1");
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Sync Products from API</h1>
    <div class="card shadow mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">ເລືອກເກມທີ່ຕ້ອງການ Sync ຂໍ້ມູນລົງ Cache</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label fw-bold">1. ເລືອກ Supplier:</label><select id="supplierSelect" class="form-select"><option value="">-- ກະລຸນາເລືອກ --</option><?php if ($suppliers && $suppliers->num_rows > 0): mysqli_data_seek($suppliers, 0); while($row = $suppliers->fetch_assoc()): ?><option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option><?php endwhile; endif; ?></select></div>
                <div class="col-md-6"><label class="form-label fw-bold">2. ເລືອກໝວດໝູ່ (Category):</label><select id="categorySelect" class="form-select" disabled></select></div>
            </div>
            <hr>
            <form method="POST" action="sync_page.php">
                <div id="operatorsContainer" style="display: none;">
                    <h6 class="fw-bold">3. ເລືອກເກມ (Operators) ທີ່ຕ້ອງການ Sync:</h6>
                    <div id="operatorsList" class="p-3 border rounded" style="max-height: 400px; overflow-y: auto;"></div>
                    <div id="hiddenInputsContainer"></div>
                    <button type="submit" name="sync_operators" class="btn btn-primary mt-3"><i class="fas fa-sync"></i> ເລີ່ມ Sync ເກມທີ່ເລືອກ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const supplierSelect = document.getElementById('supplierSelect');
    const categorySelect = document.getElementById('categorySelect');
    const operatorsContainer = document.getElementById('operatorsContainer');
    const operatorsList = document.getElementById('operatorsList');
    const hiddenInputsContainer = document.getElementById('hiddenInputsContainer');

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

    supplierSelect.addEventListener('change', function() {
        resetSelect(categorySelect, '-- ເລືອກ Supplier ກ່ອນ --');
        operatorsContainer.style.display = 'none';
        if (!this.value) return;
        
        categorySelect.disabled = false;
        categorySelect.innerHTML = '<option>Loading...</option>';
        apiFetch('get_categories').then(result => {
             if(result.success) {
                 populateSelect(categorySelect, result.data, 'id', 'nama', '-- ເລືອກໝວດໝູ່ --');
             } else {
                 alert('Error: ' + result.message);
             }
        });
    });
    
    categorySelect.addEventListener('change', function() {
        operatorsContainer.style.display = 'none';
        operatorsList.innerHTML = '';
        if (!this.value) return;

        operatorsList.innerHTML = `<div class="text-center p-3"><div class="spinner-border"></div></div>`;
        operatorsContainer.style.display = 'block';

        apiFetch('get_operators', { category_id: this.value }).then(result => {
            if(result.success && Array.isArray(result.data)) {
                let checkboxesHTML = `<div class="mb-2"><input type="checkbox" class="form-check-input" id="checkAll" checked> <label for="checkAll"><strong>ເລືອກທັງໝົດ</strong></label></div>`;
                result.data.forEach(op => {
                    checkboxesHTML += `
                        <div class="form-check">
                            <input class="form-check-input op-checkbox" type="checkbox" name="operators[${op.id}][selected]" id="op-${op.id}" checked>
                            <input type="hidden" name="operators[${op.id}][name]" value="${op.nama}">
                            <label class="form-check-label" for="op-${op.id}">${op.nama}</label>
                        </div>
                    `;
                });
                operatorsList.innerHTML = checkboxesHTML;
                hiddenInputsContainer.innerHTML = `<input type="hidden" name="supplier_id" value="${supplierSelect.value}">`;
                
                document.getElementById('checkAll').addEventListener('change', e => document.querySelectorAll('.op-checkbox').forEach(c => c.checked = e.target.checked));
            } else {
                operatorsList.innerHTML = `<p class="text-danger">Failed to load operators: ${result.message || ''}</p>`;
            }
        });
    });
});
</script>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
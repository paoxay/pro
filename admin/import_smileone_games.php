<?php
// File: admin/import_smileone_games.php (Final Version)
require_once 'db_connect.php';
require_once 'auth_check.php';
require_once 'smileone_api_helper.php'; 

// --- ຈັດການ AJAX request ເພື່ອດຶງລາຍຊື່ເກມ ---
if (isset($_GET['action']) && $_GET['action'] == 'get_games') {
    header('Content-Type: application/json');
    
    $supplier_id = $_GET['supplier_id'] ?? 0;
    if ($supplier_id <= 0) { exit(json_encode(['success' => false, 'message' => 'Invalid Supplier ID.'])); }

    $stmt = $conn->prepare("SELECT * FROM smileone_suppliers WHERE id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $supplier = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$supplier) { exit(json_encode(['success' => false, 'message' => 'Supplier not found.'])); }

    $api_response = callSmileOneAPI($supplier, 'product');

    // -- ແກ້ໄຂ Logic ການອ່ານຂໍ້ມູນ --
    if ($api_response['success'] && is_array($api_response['data'])) {
        $games_data = [];
        // Helper ຈະສົ່ງຂໍ້ມູນທີ່ເປັນ array ຂອງ string ມາໃຫ້โดยตรง
        foreach ($api_response['data'] as $gameName) {
            $games_data[] = [
                'val' => $gameName,
                'txt' => $gameName,
                'country' => 'global' // API ບໍ່ໄດ້ສົ່ງ country ມາ, ໃສ່ global ໄປກ່ອນ
            ];
        }
        echo json_encode(['success' => true, 'data' => $games_data]);
    } else {
        $error_message = $api_response['message'] ?? 'Unknown Error';
        if (isset($api_response['raw_response'])) {
             $error_message .= ' | Raw: ' . htmlspecialchars($api_response['raw_response']);
        }
        echo json_encode(['success' => false, 'message' => $error_message]);
    }
    exit; 
}


// --- ສ່ວນທີ່ເຫຼືອຂອງໂຄດຄືເກົ່າທັງໝົດ ---
$success_message = "";
$error_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_packages'])) {
    // ... (get POST data is the same) ...
    $supplier_id = $_POST['supplier_id'] ?? 0;
    $game_api_code = $_POST['game_api_code'] ?? '';
    $game_name_full = $_POST['game_name'] ?? '';
    $country_code = $_POST['country_code'] ?? '';
    $default_markup = (int)($_POST['default_markup'] ?? 15);

    $stmt_sup = $conn->prepare("SELECT * FROM smileone_suppliers WHERE id = ?");
    $stmt_sup->bind_param("i", $supplier_id);
    $stmt_sup->execute();
    $supplier = $stmt_sup->get_result()->fetch_assoc();
    $stmt_sup->close();

    $exchange_rate = (float)($supplier['exchange_rate'] ?? 1.0);

    if ($supplier && !empty($game_api_code)) {
        $api_response = callSmileOneAPI($supplier, 'productlist', ['product' => $game_api_code]);
        
        if ($api_response['success'] && isset($api_response['data']['data']['product'])) {
            $packages_from_api = $api_response['data']['data']['product'];
            $conn->begin_transaction();
            try {
                // ... (Find or Create Game logic is the same) ...
                $game_id = null;
                $stmt_check_game = $conn->prepare("SELECT id FROM smileone_games WHERE smileone_supplier_id = ? AND api_product_code = ? AND country_code = ?");
                $stmt_check_game->bind_param("iss", $supplier_id, $game_api_code, $country_code);
                $stmt_check_game->execute();
                $game_result = $stmt_check_game->get_result();
                if ($game_result->num_rows > 0) {
                    $game_id = $game_result->fetch_assoc()['id'];
                } else {
                    $stmt_game = $conn->prepare("INSERT INTO smileone_games (smileone_supplier_id, name, api_product_code, country_code, status) VALUES (?, ?, ?, ?, 'active')");
                    $stmt_game->bind_param("isss", $supplier_id, $game_name_full, $game_api_code, $country_code);
                    $stmt_game->execute();
                    $game_id = $conn->insert_id;
                    $stmt_game->close();
                }
                $stmt_check_game->close();

                // --- UPDATE THIS PART ---
                $stmt_pkg = $conn->prepare("REPLACE INTO smileone_packages (game_id, api_productid, name, api_price, cost_price, selling_price) VALUES (?, ?, ?, ?, ?, ?)");
                $upsert_count = 0;
                foreach($packages_from_api as $pkg) {
                    $api_price = (float)$pkg['price'];
                    $cost_price_lak = $api_price * $exchange_rate;
                    $selling_price = ceil($cost_price_lak * (1 + ($default_markup / 100))); 
                    $stmt_pkg->bind_param("issddd", $game_id, $pkg['id'], $pkg['spu'], $api_price, $cost_price_lak, $selling_price);
                    $stmt_pkg->execute();
                    $upsert_count++;
                }

                $stmt_pkg->close();
                $conn->commit();
                $success_message = "ນຳເຂົ້າຂໍ້ມູນສຳເລັດ! ເພີ່ມ/ອັບເດດແລ້ວ $upsert_count ແພັກເກັດ. <a href='manage_smileone_packages.php?game_id=$game_id'>ກົດທີ່ນີ້ເພື່ອຈັດການລາຄາ</a>";
            } catch (Exception $e) { $conn->rollback(); $error_message = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage(); }
        } else { $error_message = "ບໍ່ສາມາດດຶງລາຍຊື່ແພັກເກັດໄດ້: " . ($api_response['message'] ?? 'Unknown Error'); }
    } else { $error_message = "ຂໍ້ມູນບໍ່ຄົບຖ້ວນ ຫຼື ບໍ່ພົບຂໍ້ມູນ Supplier."; }
}



require_once 'admin_header.php';
$suppliers_result = $conn->query("SELECT id, name FROM smileone_suppliers WHERE is_active = 1");
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Import Game ຈາກ Smile One</h1>
    <?php if(!empty($success_message)): ?> <div class="alert alert-success"><?php echo $success_message; ?></div> <?php endif; ?>
    <?php if(!empty($error_message)): ?> <div class="alert alert-danger"><?php echo $error_message; ?></div> <?php endif; ?>
    <form method="POST" action="import_smileone_games.php">
        <div class="card shadow mb-4"><div class="card-header"><h6 class="m-0 font-weight-bold text-primary">ຂັ້ນຕອນ: ເລືອກເກມທີ່ຈະນຳເຂົ້າ</h6></div><div class="card-body"><div class="row g-3 align-items-end"><div class="col-md-4"><label class="form-label fw-bold">1. ເລືອກ Smile One Supplier:</label><select id="supplierSelect" name="supplier_id" class="form-select" required><option value="">-- ກະລຸນາເລືອກ --</option><?php if ($suppliers_result && $suppliers_result->num_rows > 0): mysqli_data_seek($suppliers_result, 0); while($row = $suppliers_result->fetch_assoc()): ?><option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option><?php endwhile; endif; ?></select></div><div class="col-md-5"><label class="form-label fw-bold">2. ເລືອກເກມຈາກ API:</label><select id="gameSelect" name="game_selection" class="form-select" disabled required></select><input type="hidden" name="game_api_code" id="game_api_code"><input type="hidden" name="game_name" id="game_name"><input type="hidden" name="country_code" id="country_code"></div><div class="col-md-2"><label class="form-label fw-bold">3. % ກຳໄລເລີ່ມຕົ້ນ:</label><input type="number" name="default_markup" class="form-control" value="15" required></div><div class="col-md-1"><button type="submit" name="import_packages" class="btn btn-primary w-100">ນຳເຂົ້າ</button></div></div></div></div>
    </form>
    <a href="manage_smileone_games.php" class="btn btn-secondary"> <i class="fas fa-arrow-left"></i> ກັບໄປໜ້າຈັດການເກມ Smile One</a>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const supplierSelect = document.getElementById('supplierSelect');
    const gameSelect = document.getElementById('gameSelect');
    function apiFetch(action, params = {}) { const url = new URL(window.location.href.split('?')[0]); url.searchParams.set('action', action); url.searchParams.set('supplier_id', supplierSelect.value); for(const key in params) { url.searchParams.set(key, params[key]); } return fetch(url).then(res => res.json()); }
    function populateSelect(selectEl, data, valueKey, textKey, countryKey, defaultText) { selectEl.innerHTML = `<option value="">${defaultText}</option>`; if (data && Array.isArray(data)) { data.forEach(item => { const option = document.createElement('option'); option.value = item[valueKey]; option.textContent = item[textKey]; option.dataset.country = item[countryKey]; selectEl.appendChild(option); }); } }
    supplierSelect.addEventListener('change', function() {
        gameSelect.innerHTML = '<option value="">--</option>'; gameSelect.disabled = true; document.getElementById('game_api_code').value = ''; document.getElementById('game_name').value = ''; document.getElementById('country_code').value = ''; if (!this.value) return;
        gameSelect.disabled = false; gameSelect.innerHTML = '<option>Loading...</option>';
        apiFetch('get_games').then(result => {
             if(result.success) {
                 populateSelect(gameSelect, result.data, 'val', 'txt', 'country', '-- ເລືອກເກມ --');
             } else {
                 alert('Error fetching games: ' + result.message);
                 gameSelect.innerHTML = `<option value="">-- Error --</option>`;
             }
        });
    });
    gameSelect.addEventListener('change', function() {
        if (!this.value) { document.getElementById('game_api_code').value = ''; document.getElementById('game_name').value = ''; document.getElementById('country_code').value = ''; return; }
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById('game_api_code').value = this.value; document.getElementById('game_name').value = selectedOption.textContent; document.getElementById('country_code').value = selectedOption.dataset.country || '';
    });
});
</script>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
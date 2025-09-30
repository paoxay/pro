<?php
// File: admin/manage_smileone_packages.php (Fully Upgraded & Complete Version)
require_once 'admin_header.php';
require_once 'db_connect.php';

$game_id = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
if ($game_id <= 0) { die("<h2>Error: Invalid Game ID</h2>"); }

$stmt_game = $conn->prepare("SELECT g.name, s.exchange_rate FROM smileone_games g JOIN smileone_suppliers s ON g.smileone_supplier_id = s.id WHERE g.id = ?");
$stmt_game->bind_param("i", $game_id);
$stmt_game->execute();
$game = $stmt_game->get_result()->fetch_assoc();
if (!$game) { die("<h2>Error: Game not found</h2>"); }
$stmt_game->close();
$exchange_rate = (float)$game['exchange_rate'];

// -- UPDATED QUERY: Order by display_order first, then by cost_price --
$packages_result = $conn->query("SELECT * FROM smileone_packages WHERE game_id = $game_id ORDER BY display_order ASC, cost_price ASC");
?>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<style>
    /* Style for visual feedback during drag */
    .sortable-ghost {
        opacity: 0.4;
        background-color: #c8ebfb;
    }
    /* Style for the drag handle */
    .sort-handle {
        cursor: grab;
        color: #b0b0b0;
    }
    .sort-handle:hover {
        color: #333;
    }
    #packagesTable input[type="number"] {
        min-width: 120px;
    }
</style>

<div class="container-fluid">
    <a href="manage_smileone_games.php" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-left"></i> ກັບໄປໜ້າຈັດການເກມ</a>
    <h2 class="h3 mb-3">ຈັດການແພັກເກັດ: <span class="text-primary"><?php echo htmlspecialchars($game['name']); ?></span></h2>
    <p>ອັດຕາແລກປ່ຽນປັດຈຸບັນ: <strong id="exchangeRate" data-rate="<?php echo $exchange_rate; ?>"><?php echo number_format($exchange_rate, 4); ?></strong></p>

    <div class="card shadow">
        <div class="card-body">
             <div class="table-responsive">
                <table id="packagesTable" class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 50px;">ລຽງ</th>
                            <th>ຊື່ແພັກເກັດ</th>
                            <th>ລາຄາ API (Coins)</th>
                            <th>ຕົ້ນທຶນ (LAK)</th>
                            <th>Markup (%)</th>
                            <th>ລາຄາຂາຍ (LAK)</th>
                            <th>ກຳໄລ (LAK)</th>
                            <th class="text-center">ສະຖານະ</th>
                            <th class="text-center" style="width: 100px;">ເຄື່ອງມື</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-packages">
                        <?php if ($packages_result && $packages_result->num_rows > 0): ?>
                            <?php while($pkg = $packages_result->fetch_assoc()): ?>
                            <tr data-id="<?php echo $pkg['id']; ?>" class="<?php echo $pkg['status'] === 'inactive' ? 'table-secondary text-muted' : ''; ?>">
                                <td class="text-center sort-handle"><i class="fas fa-arrows-alt-v"></i></td>
                                <td class="pkg-name"><?php echo htmlspecialchars($pkg['name']); ?></td>
                                <td class="api-price text-end"><?php echo number_format($pkg['api_price'], 2); ?></td>
                                <td class="cost-price text-end"><?php echo number_format($pkg['cost_price'], 2); ?></td>
                                <td class="markup-percent text-end fw-bold text-info"><?php echo number_format($pkg['markup_percentage'], 2); ?> %</td>
                                <td class="selling-price text-end fw-bold text-danger"><?php echo number_format($pkg['selling_price'], 2); ?></td>
                                <td class="profit text-end fw-bold text-success"><?php echo number_format($pkg['selling_price'] - $pkg['cost_price'], 2); ?></td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input status-toggle" type="checkbox" style="transform: scale(1.5);" <?php echo $pkg['status'] === 'active' ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning editBtn"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center">ບໍ່ພົບຂໍ້ມູນແພັກເກັດ.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('packagesTable');
    const exchangeRate = parseFloat(document.getElementById('exchangeRate').dataset.rate);
    let originalRowHTML = null;

    function updateCalculationsOnMarkup(row) {
        const apiPrice = parseFloat(row.querySelector('.api-price').innerText.replace(/,/g, '')) || 0;
        const markupInput = row.querySelector('input[name="markup_percentage"]');
        const sellingPriceInput = row.querySelector('input[name="selling_price"]');
        
        const markup = parseFloat(markupInput.value) || 0;
        const costPrice = apiPrice * exchangeRate;
        const newSellingPrice = Math.ceil(costPrice * (1 + (markup / 100)));
        const newProfit = newSellingPrice - costPrice;

        row.querySelector('.cost-price').innerText = costPrice.toLocaleString('en-US', {minimumFractionDigits: 2});
        sellingPriceInput.value = newSellingPrice.toFixed(0);
        row.querySelector('.profit').innerText = newProfit.toLocaleString('en-US', {minimumFractionDigits: 2});
    }

    function updateCalculationsOnPrice(row) {
        const sellingPriceInput = row.querySelector('input[name="selling_price"]');
        const markupInput = row.querySelector('input[name="markup_percentage"]');
        const costPrice = parseFloat(row.querySelector('.cost-price').innerText.replace(/,/g, '')) || 0;
        
        if (costPrice > 0) {
            const sellingPrice = parseFloat(sellingPriceInput.value) || 0;
            const newMarkup = ((sellingPrice / costPrice) - 1) * 100;
            const newProfit = sellingPrice - costPrice;

            markupInput.value = newMarkup.toFixed(2);
            row.querySelector('.profit').innerText = newProfit.toLocaleString('en-US', {minimumFractionDigits: 2});
        }
    }

    table.addEventListener('click', function(e) {
        const target = e.target.closest('button');
        if (!target) return;
        
        const row = target.closest('tr');
        if (!row) return;

        if (target.classList.contains('editBtn')) {
            if (document.querySelector('tr.editing')) { return; }
            
            originalRowHTML = row.innerHTML;
            row.classList.add('editing');
            
            const name = row.querySelector('.pkg-name').innerText;
            const api_price = parseFloat(row.querySelector('.api-price').innerText.replace(/,/g, ''));
            const markup = parseFloat(row.querySelector('.markup-percent').innerText.replace(/,/g, ''));
            const selling_price = parseFloat(row.querySelector('.selling-price').innerText.replace(/,/g, ''));

            row.querySelector('.pkg-name').innerHTML = `<input type="text" class="form-control form-control-sm" name="name" value="${name}">`;
            row.querySelector('.api-price').innerHTML = `<input type="number" class="form-control form-control-sm" name="api_price" value="${api_price.toFixed(2)}" step="0.01" disabled>`;
            row.querySelector('.markup-percent').innerHTML = `<input type="number" class="form-control form-control-sm" name="markup_percentage" value="${markup.toFixed(2)}" step="0.01">`;
            row.querySelector('.selling-price').innerHTML = `<input type="number" class="form-control form-control-sm" name="selling_price" value="${selling_price.toFixed(0)}" step="1">`;
            
            row.querySelector('td:last-child').innerHTML = `<div class="btn-group"><button class="btn btn-sm btn-primary saveEditBtn"><i class="fas fa-save"></i></button><button class="btn btn-sm btn-secondary cancelBtn"><i class="fas fa-times"></i></button></div>`;
        }

        if (target.classList.contains('cancelBtn')) {
            row.innerHTML = originalRowHTML;
            row.classList.remove('editing');
            originalRowHTML = null;
        }

        if (target.classList.contains('saveEditBtn')) {
            const data = {
                id: row.dataset.id,
                name: row.querySelector('input[name="name"]').value,
                api_price: parseFloat(row.querySelector('input[name="api_price"]').value),
                cost_price: parseFloat(row.querySelector('.cost-price').innerText.replace(/,/g, '')),
                markup_percentage: parseFloat(row.querySelector('input[name="markup_percentage"]').value),
                selling_price: parseFloat(row.querySelector('input[name="selling_price"]').value)
            };

            fetch('ajax_edit_smileone_package.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data) })
            .then(res => res.json()).then(result => {
                if (result.success) {
                    row.innerHTML = originalRowHTML;
                    row.querySelector('.pkg-name').innerText = data.name;
                    row.querySelector('.api-price').innerText = data.api_price.toLocaleString('en-US', {minimumFractionDigits: 2});
                    row.querySelector('.cost-price').innerText = data.cost_price.toLocaleString('en-US', {minimumFractionDigits: 2});
                    row.querySelector('.markup-percent').innerText = data.markup_percentage.toLocaleString('en-US', {minimumFractionDigits: 2}) + ' %';
                    row.querySelector('.selling-price').innerText = data.selling_price.toLocaleString('en-US', {minimumFractionDigits: 2});
                    row.querySelector('.profit').innerText = (data.selling_price - data.cost_price).toLocaleString('en-US', {minimumFractionDigits: 2});
                    row.classList.remove('editing');
                    originalRowHTML = null;
                } else { alert('Error: ' + result.message); }
            });
        }
    });

    table.addEventListener('input', function(e) {
        const target = e.target;
        if (target.tagName.toLowerCase() !== 'input') return;
        const row = target.closest('tr.editing');
        if (!row) return;

        if (target.name === 'markup_percentage') {
            updateCalculationsOnMarkup(row);
        } else if (target.name === 'selling_price') {
            updateCalculationsOnPrice(row);
        }
    });

    table.addEventListener('change', function(e) {
        if (e.target.classList.contains('status-toggle')) {
            const row = e.target.closest('tr');
            const data = {
                id: row.dataset.id,
                status: e.target.checked ? 'active' : 'inactive'
            };
            fetch('ajax_update_smileone_package_status.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data) })
            .then(res => res.json()).then(result => {
                if(result.success) {
                    row.classList.toggle('table-secondary', !e.target.checked);
                    row.classList.toggle('text-muted', !e.target.checked);
                } else {
                    alert('Error updating status');
                    e.target.checked = !e.target.checked;
                }
            });
        }
    });

    const sortableList = document.getElementById('sortable-packages');
    if (sortableList) {
        new Sortable(sortableList, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            handle: '.sort-handle',
            onEnd: function (evt) {
                const package_ids = Array.from(sortableList.querySelectorAll('tr')).map(row => row.dataset.id);
                fetch('ajax_save_smileone_package_order.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ package_ids: package_ids })
                })
                .then(res => res.json())
                .then(result => { if (!result.success) { alert('Error saving order: ' + result.message); }});
            }
        });
    }
});
</script>
</body>
</html>
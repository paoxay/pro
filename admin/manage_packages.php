<?php
// File: admin/manage_packages.php (FINAL - Compact UI Version)

require_once 'auth_check.php';
require_once 'db_connect.php';

$game_id = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
if ($game_id <= 0) {
    header("Location: manage_games.php");
    exit;
}

// --- FORM PROCESSING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    try {
        if (isset($_POST['default_markup'])) {
            $new_markup = (float)$_POST['default_markup'];
            $stmt_update_game_markup = $conn->prepare("UPDATE games SET default_markup = ? WHERE id = ?");
            $stmt_update_game_markup->bind_param("di", $new_markup, $game_id);
            $stmt_update_game_markup->execute();
            $stmt_update_game_markup->close();
        }

        if (isset($_POST['packages'])) {
            $stmt_update = $conn->prepare("UPDATE game_packages SET name = ?, cost_price = ?, price = ?, status = ?, display_order = ?, markup_percent = ? WHERE id = ?");
            $stmt_delete = $conn->prepare("DELETE FROM game_packages WHERE id = ?");
            
            foreach ($_POST['packages'] as $id => $data) {
                if (isset($data['delete'])) {
                    $stmt_delete->bind_param("i", $id);
                    $stmt_delete->execute();
                } else {
                    $stmt_update->bind_param("sddsidi", $data['name'], $data['cost_price'], $data['price'], $data['status'], $data['order'], $data['markup_percent'], $id);
                    $stmt_update->execute();
                }
            }
            $stmt_update->close();
            $stmt_delete->close();
        }

        if (isset($_POST['new_packages'])) {
            $stmt_insert = $conn->prepare("INSERT INTO game_packages (game_id, name, cost_price, price, status, display_order, api_product_code, markup_percent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($_POST['new_packages'] as $data) {
                if (!empty($data['name'])) {
                    $stmt_insert->bind_param("isdsdisd", $game_id, $data['name'], $data['cost_price'], $data['price'], $data['status'], $data['order'], $data['api_product_code'], $data['markup_percent']);
                    $stmt_insert->execute();
                }
            }
            $stmt_insert->close();
        }

        $conn->commit();
        $_SESSION['success_message'] = "ບັນທຶກການປ່ຽນແປງສຳເລັດ!";
        header("Location: manage_packages.php?game_id=" . $game_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error processing data: " . $e->getMessage();
        header("Location: manage_packages.php?game_id=" . $game_id);
        exit();
    }
}

// --- DATA FETCHING LOGIC ---
$stmt_game = $conn->prepare("SELECT name, default_markup FROM games WHERE id = ?");
$stmt_game->bind_param("i", $game_id);
$stmt_game->execute();
$result_game = $stmt_game->get_result();
if ($result_game->num_rows === 0) { die("<h2>Error: Game not found</h2>"); }
$game = $result_game->fetch_assoc();
$stmt_game->close();

$sql_packages = "
    SELECT 
        gp.id, gp.name, gp.price, gp.cost_price, gp.status, gp.api_product_code, gp.display_order, gp.markup_percent,
        acp.cost_price AS api_cost_original
    FROM game_packages AS gp
    LEFT JOIN api_cache_products AS acp ON gp.api_product_code = acp.product_code AND gp.game_id IN (SELECT g.id FROM games g WHERE g.api_supplier_id = acp.api_supplier_id)
    WHERE gp.game_id = ?
    GROUP BY gp.id
    ORDER BY gp.display_order ASC, gp.price ASC
";
$stmt_packages = $conn->prepare($sql_packages);
$stmt_packages->bind_param("i", $game_id);
$stmt_packages->execute();
$packages_result = $stmt_packages->get_result();

require_once 'admin_header.php';
?>
<style>
    .table-responsive { overflow-x: visible; } .draggable-row { cursor: move; } .dragging { opacity: 0.6; background-color: #e3f2fd; } .drag-handle { vertical-align: middle; } .form-control-sm { min-width: 120px; text-align: right; } .api-code { font-family: monospace; font-size: 0.9em; background-color: #f1f1f1; padding: 2px 4px; border-radius: 3px; } .profit-cell { color: #198754; font-weight: bold; } .package-info { line-height: 1.4; text-align: left; } input[readonly] { background-color: #e9ecef; cursor: not-allowed; }
    .package-api-cost { font-size: 0.85em; color: #0d6efd; font-weight: bold; }
</style>

<div class="container-fluid">
    <a href="manage_games.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> ກັບໄປໜ້າຈັດການເກມ</a>
    <h2 class="h3 mb-3">ຈັດການແພັກເກັດສຳລັບ: <span class="text-primary"><?php echo htmlspecialchars($game['name']); ?></span></h2>

    <?php
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $_SESSION['error_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['error_message']);
    }
    ?>

    <form method="POST" action="manage_packages.php?game_id=<?php echo $game_id; ?>">
        <div class="card shadow">
             <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">ລາຍການແພັກເກັດ</h6>
                <div class="d-flex align-items-center">
                    <label for="masterMarkup" class="form-label me-2 mb-0">ເປີເຊັນຂາຍທັງໝົດ (%):</label>
                    <input type="number" id="masterMarkup" name="default_markup" class="form-control form-control-sm" style="width: 100px;" value="<?php echo htmlspecialchars($game['default_markup']); ?>" step="any">
                    <button type="button" id="applyMarkupBtn" class="btn btn-sm btn-info ms-2">ນຳໃຊ້</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th></th>
                                <th style="width: 30%;">ຊື່ແພັກເກັດ</th>
                                <th>ຕົ້ນທຶນ (LAK)</th>
                                <th style="width: 10%;">ເປີເຊັນຂາຍ (%)</th>
                                <th>ລາຄາຂາຍ (LAK)</th>
                                <th>ກຳໄລ (LAK)</th>
                                <th>ສະຖານະ</th>
                                <th>ລຶບ</th>
                            </tr>
                        </thead>
                        <tbody id="packages-table-body">
                            <?php $order = 0; while($pkg = $packages_result->fetch_assoc()): $order++; 
                                $cost_lak = (float)($pkg['cost_price'] ?? 0);
                                $price_lak = (float)($pkg['price'] ?? 0);
                                if ($pkg['markup_percent'] !== null) {
                                    $profit_percent = (float)$pkg['markup_percent'];
                                } else {
                                    $profit_lak_calc = $price_lak - $cost_lak;
                                    $profit_percent = ($cost_lak > 0) ? ($profit_lak_calc / $cost_lak) * 100 : 0;
                                }
                            ?>
                            <tr class="draggable-row" draggable="true">
                                <td class="text-center drag-handle"><i class="fas fa-bars text-muted"></i></td>
                                <td>
                                    <input type="hidden" name="packages[<?php echo $pkg['id']; ?>][order]" class="display-order" value="<?php echo $order; ?>">
                                    <input type="text" class="form-control form-control-sm mb-1" name="packages[<?php echo $pkg['id']; ?>][name]" value="<?php echo htmlspecialchars($pkg['name']); ?>" required>
                                    <div class="package-info">
                                        <small class="text-muted">ID: <?php echo $pkg['id']; ?> | Code: <span class="api-code"><?php echo htmlspecialchars($pkg['api_product_code'] ?: '-'); ?></span></small><br>
                                        <small class="package-api-cost">API Cost: <?php echo ($pkg['api_cost_original']) ? number_format($pkg['api_cost_original'], 2) : '-'; ?></small>
                                    </div>
                                </td>
                                <td><input type="number" class="form-control form-control-sm cost-lak-input" name="packages[<?php echo $pkg['id']; ?>][cost_price]" value="<?php echo htmlspecialchars($cost_lak); ?>" step="any" required></td>
                                <td><input type="number" class="form-control form-control-sm selling-percent" name="packages[<?php echo $pkg['id']; ?>][markup_percent]" value="<?php echo number_format($profit_percent, 2); ?>" step="any"></td>
                                <td><input type="number" class="form-control form-control-sm price-lak-input" name="packages[<?php echo $pkg['id']; ?>][price]" value="<?php echo htmlspecialchars($price_lak); ?>" step="1000" readonly></td>
                                <td class="profit-lak-cell text-center profit-cell"></td>
                                <td>
                                    <select name="packages[<?php echo $pkg['id']; ?>][status]" class="form-select form-select-sm">
                                        <option value="active" <?php echo ($pkg['status'] == 'active') ? 'selected' : ''; ?>>ເປີດ</option>
                                        <option value="inactive" <?php echo ($pkg['status'] == 'inactive') ? 'selected' : ''; ?>>ປິດ</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="packages[<?php echo $pkg['id']; ?>][delete]" title="ໝາຍໄວ້ເພື່ອລຶບ">
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" id="addRowBtn" class="btn btn-success"><i class="fas fa-plus"></i> ເພີ່ມແພັກເກັດໃໝ່ (Manual)</button>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> ບັນທຶກການປ່ຽນແປງທັງໝົດ</button>
            </div>
        </div>
    </form>
</div>

<template id="newRowTemplate">
    <tr class="draggable-row" draggable="true">
        <td class="text-center drag-handle"><i class="fas fa-bars text-muted"></i></td>
        <td>
             <input type="text" class="form-control form-control-sm mb-1" name="new_packages[__INDEX__][name]" placeholder="ຊື່ແພັກເກັດໃໝ່" required>
             <div class="package-info">
                <small class="text-muted">ID: ໃໝ່ | Code: <input type="text" class="form-control-sm d-inline-block" style="width: 120px;" name="new_packages[__INDEX__][api_product_code]" placeholder="ລະຫັດ API"></small>
                <br><small class="package-api-cost">API Cost: -</small>
             </div>
             <input type="hidden" name="new_packages[__INDEX__][order]" class="display-order">
        </td>
        <td><input type="number" class="form-control form-control-sm cost-lak-input" name="new_packages[__INDEX__][cost_price]" value="0.00" step="any" required></td>
        <td><input type="number" class="form-control form-control-sm selling-percent" name="new_packages[__INDEX__][markup_percent]" value="15.00" step="any"></td>
        <td><input type="number" class="form-control form-control-sm price-lak-input" name="new_packages[__INDEX__][price]" value="0" step="1000" readonly></td>
        <td class="profit-lak-cell text-center profit-cell">0.00</td>
        <td>
            <select name="new_packages[__INDEX__][status]" class="form-select form-select-sm">
                <option value="active" selected>ເປີດ</option>
                <option value="inactive">ປິດ</option>
            </select>
        </td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-new-row-btn"><i class="fas fa-times"></i></button></td>
    </tr>
</template>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('packages-table-body');
    const addRowBtn = document.getElementById('addRowBtn');
    const newRowTemplate = document.getElementById('newRowTemplate');
    const applyMarkupBtn = document.getElementById('applyMarkupBtn');
    let newRowCounter = 0;

    function recalculateRow(row) {
        if (!row) return;
        const costInput = row.querySelector('.cost-lak-input');
        const priceInput = row.querySelector('.price-lak-input');
        const markupInput = row.querySelector('.selling-percent');
        const profitCell = row.querySelector('.profit-lak-cell');
        
        const cost = parseFloat(costInput.value) || 0;
        const markup = parseFloat(markupInput.value) || 0;
        
        let newPrice;
        if (markup === 0) {
            newPrice = cost;
        } else {
            const newPriceRaw = cost * (1 + markup / 100);
            newPrice = Math.ceil(newPriceRaw / 1000) * 1000;
        }
        
        const newProfit = newPrice - cost;

        priceInput.value = newPrice.toFixed(2);
        profitCell.textContent = newProfit.toLocaleString('en-US', { minimumFractionDigits: 2 });
    }
    
    tableBody.querySelectorAll('tr').forEach(recalculateRow);
    
    applyMarkupBtn.addEventListener('click', function() {
        const masterMarkup = document.getElementById('masterMarkup').value;
        if (masterMarkup === '') return;

        tableBody.querySelectorAll('tr').forEach(row => {
            const markupInput = row.querySelector('.selling-percent');
            if (markupInput) {
                markupInput.value = parseFloat(masterMarkup).toFixed(2);
                recalculateRow(row);
            }
        });
    });

    tableBody.addEventListener('input', function(e) {
        if (e.target.matches('.cost-lak-input, .selling-percent')) {
            recalculateRow(e.target.closest('tr'));
        }
    });
    
    addRowBtn.addEventListener('click', function() {
        let content = newRowTemplate.innerHTML.replace(/__INDEX__/g, newRowCounter);
        const newTr = document.createElement('tr');
        newTr.className = 'draggable-row';
        newTr.setAttribute('draggable', 'true');
        newTr.innerHTML = content;
        tableBody.appendChild(newTr);
        recalculateRow(newTr);
        newRowCounter++;
        updateDisplayOrder();
        addDragListeners(newTr);
    });

    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-new-row-btn')) { e.target.closest('tr').remove(); updateDisplayOrder(); }
    });

    let draggingEle;
    function addDragListeners(element) {
        element.addEventListener('dragstart', () => { draggingEle = element; setTimeout(() => element.classList.add('dragging'), 0); });
        element.addEventListener('dragend', () => { if(draggingEle) { draggingEle.classList.remove('dragging'); } updateDisplayOrder(); });
    }
    tableBody.querySelectorAll('.draggable-row').forEach(addDragListeners);
    tableBody.addEventListener('dragover', (e) => {
        e.preventDefault();
        const afterElement = getDragAfterElement(tableBody, e.clientY);
        if (draggingEle) { afterElement ? tableBody.insertBefore(draggingEle, afterElement) : tableBody.appendChild(draggingEle); }
    });
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.draggable-row:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            return (offset < 0 && offset > closest.offset) ? { offset, element: child } : closest;
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
    function updateDisplayOrder() {
        tableBody.querySelectorAll('tr').forEach((row, index) => {
            const orderInput = row.querySelector('.display-order');
            if(orderInput) orderInput.value = index + 1;
        });
    }
    updateDisplayOrder();
});
</script>
</body>
</html>
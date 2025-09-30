<?php
// File: admin/manage_smileone_games.php (Added Delete Button)
require_once 'admin_header.php';
require_once 'db_connect.php';

$result = $conn->query("SELECT g.*, s.name as supplier_name 
                        FROM smileone_games g 
                        JOIN smileone_suppliers s ON g.smileone_supplier_id = s.id 
                        ORDER BY g.id DESC");
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 text-gray-800">ຈັດການເກມ (Smile One)</h1>
        <a href="import_smileone_games.php" class="btn btn-info">
            <i class="fas fa-cloud-download-alt"></i> Import/Update ເກມຈາກ API
        </a>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">ລາຍການເກມ Smile One ໃນລະບົບ</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>ຮູບ</th>
                            <th>ຊື່ເກມ</th>
                            <th>Supplier</th>
                            <th>ສະຖານະ</th>
                            <th class="text-center" style="width: 320px;">ເຄື່ອງມື</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <?php if (!empty($row['image_url'])): ?>
                                        <img src="../<?php echo htmlspecialchars($row['image_url']); ?>" class="game-image">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($row['supplier_name']); ?></span></td>
                                <td>
                                    <?php if ($row['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="manage_smileone_packages.php?game_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary mb-1">ຈັດການແພັກເກັດ</a>
                                    <a href="edit_smileone_game.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning mb-1">ແກ້ໄຂ</a>
                                    <button class="btn btn-sm btn-info mb-1 update-prices-btn" data-game-id="<?php echo $row['id']; ?>" data-game-name="<?php echo htmlspecialchars($row['name']); ?>">ອັບເດດລາຄາ</button>
                                    
                                    <a href="delete_smileone_game.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('ແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບເກມນີ້? \n\n(ຂໍ້ມູນແພັກເກັດ ແລະ ອໍເດີ້ທີ່ກ່ຽວຂ້ອງຈະຖືກລຶບໄປນຳ!)');">
                                        <i class="fas fa-trash"></i> ລຶບ
                                    </a>
                                    </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">ຍັງບໍ່ມີຂໍ້ມູນເກມ. ກະລຸນາ Import ກ່ອນ.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="priceUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulkUpdateForm">
                <div class="modal-header"><h5 class="modal-title">ອັບເດດລາຄາທັງໝົດ</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="game_id" id="modal_game_id">
                    <p>ກຳລັງຈະອັບເດດລາຄາຂາຍຂອງເກມ: <strong id="modal_game_name"></strong></p>
                    <div class="input-group">
                        <span class="input-group-text">ຕົ້ນທຶນ (LAK) +</span>
                        <input type="number" name="markup_percentage" class="form-control" value="15" required>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button><button type="submit" class="btn btn-primary">ຢືນຢັນການອັບເດດ</button></div>
            </form>
        </div>
    </div>
</div>

</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// JavaScript code for the modal (from previous response)
document.addEventListener('DOMContentLoaded', function() {
    const priceUpdateModal = new bootstrap.Modal(document.getElementById('priceUpdateModal'));

    document.querySelectorAll('.update-prices-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('modal_game_id').value = this.dataset.gameId;
            document.getElementById('modal_game_name').innerText = this.dataset.gameName;
            priceUpdateModal.show();
        });
    });

    document.getElementById('bulkUpdateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const gameId = this.game_id.value;
        const markup = this.markup_percentage.value;
        if (confirm(`ແນ່ໃຈບໍ່ວ່າຕ້ອງການອັບເດດລາຄາທັງໝົດຂອງເກມນີ້ โดยใช้กำไร ${markup}%?`)) {
            const submitBtn = this.querySelector('[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';
            
            fetch('ajax_bulk_update_smileone_prices.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameId, markup: markup })
            }).then(res => res.json()).then(result => {
                if (result.success) {
                    alert('ອັບເດດລາຄາສຳເລັດ!');
                    priceUpdateModal.hide();
                } else {
                    alert('Error: ' + result.message);
                }
            }).finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerText = 'ຢືນຢັນການອັບເດດ';
            });
        }
    });
});
</script>
</body>
</html>
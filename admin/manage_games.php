<?php
// File: admin/manage_games.php (Corrected Links)
require_once 'admin_header.php';
require_once 'db_connect.php';
$result = $conn->query("SELECT g.*, s.name as supplier_name FROM games g LEFT JOIN api_suppliers s ON g.api_supplier_id = s.id ORDER BY g.id DESC");
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 text-gray-800">ຈັດການເກມ</h1>
        <div>
            <a href="import_from_cache.php" class="btn btn-info">
                <i class="fas fa-cloud-download-alt"></i> Import Game from Cache
            </a>
            <a href="add_game.php" class="btn btn-success">
                <i class="fas fa-plus"></i> ເພີ່ມເກມ (Manual)
            </a>
        </div>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">ລາຍການເກມໃນລະບົບ</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr><th>ID</th><th>ຮູບ</th><th>ຊື່ເກມ</th><th>ປະເພດ</th><th>ສະຖານະ</th><th>ເຄື່ອງມື</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php if (!empty($row['image_url'])): ?><img src="../<?php echo htmlspecialchars($row['image_url']); ?>" class="game-image"><?php endif; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td>
                                    <?php if ($row['supplier_name']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($row['supplier_name']); ?> (API)</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Manual</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td>
                                    <a href="manage_packages.php?game_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">ຈັດການແພັກເກັດ</a>
                                    <a href="edit_game.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">ແກ້ໄຂ</a>
                                    <a href="delete_game.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ແນ່ໃຈບໍ່?');">ລຶບ</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">ຍັງບໍ່ມີຂໍ້ມູນເກມ.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
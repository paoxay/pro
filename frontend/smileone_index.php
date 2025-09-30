<?php
// File: /frontend/smileone_index.php
require_once 'header.php'; 

// ດຶງຂໍ້ມູນເກມທັງໝົດຈາກຕາຕະລາງ smileone_games
$games_result = $conn->query("SELECT * FROM smileone_games WHERE status = 'active' ORDER BY name ASC");
?>

<style>
    /* ໃຊ້ Style ແບບດຽວກັບໜ້າ index.php ເດີມໄດ້ເລີຍ */
    .game-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
    @media (min-width: 768px) { .game-grid { grid-template-columns: repeat(6, 1fr); gap: 1.5rem; } }
    .game-card { background-color: #ffffff; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); overflow: hidden; text-decoration: none; color: inherit; display: block; position: relative; transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .game-card:hover { transform: translateY(-10px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
    .game-card-image-wrapper { aspect-ratio: 1 / 1; overflow: hidden; background-color: #f0f0f0; }
    .game-card img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
    .game-card:hover img { transform: scale(1.1); }
    .game-card-content { padding: 1rem; }
    .game-card-title { font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.9rem; }
    .game-card-cta { background-color: #fd7e14; color: white; text-align: center; padding: 0.75rem; font-weight: 500; transition: background-color 0.3s; }
    .game-card:hover .game-card-cta { background-color: #e86a00; }
</style>

<h1 class="mb-4 fw-bold">ເລືອກເກມທີ່ຕ້ອງການເຕີມ (Smile One)</h1>

<div class="game-grid">
    <?php if ($games_result && $games_result->num_rows > 0): ?>
        <?php while($game = $games_result->fetch_assoc()): ?>
            <a href="smileone_topup.php?game_id=<?php echo $game['id']; ?>" class="game-card">
                <div class="game-card-image-wrapper">
                    <img src="../admin/img/placeholder.png" alt="<?php echo htmlspecialchars($game['name']); ?>">
                </div>
                <div class="game-card-content">
                    <h5 class="game-card-title"><?php echo htmlspecialchars($game['name']); ?></h5>
                </div>
                <div class="game-card-cta">
                    <i class="fas fa-shopping-cart me-2"></i> ເຕີມເງິນ
                </div>
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <p class="text-center fs-5 text-muted">ຍັງບໍ່ມີເກມຈາກ Smile One ເປີດໃຫ້ບໍລິການ.</p>
        </div>
    <?php endif; ?>
</div>

</main> 
<footer class="container mt-5 py-4 text-center text-muted border-top">
    <p>&copy; <?php echo date('Y'); ?> Topup Store</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
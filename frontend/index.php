<?php
// File: /frontend/index.php (Updated to merge both game sources)
require_once 'header.php'; 

// --- ແກ້ໄຂ SQL Query ບ່ອນນີ້ ---
// ດຶງຂໍ້ມູນເກມຈາກທັງສອງລະບົບໂດຍໃຊ້ UNION ALL
$sql = "
    (SELECT 
        id, 
        name, 
        image_url, 
        'toko' as source_type 
     FROM games 
     WHERE status = 'active')
    UNION ALL
    (SELECT 
        id, 
        name, 
        image_url, 
        'smileone' as source_type 
     FROM smileone_games 
     WHERE status = 'active')
    ORDER BY name ASC
";

$games_result = $conn->query($sql);
?>

<style>
    /* Style ຕ່າງໆຄືເກົ່າ */
    .game-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    @media (min-width: 768px) {
        .game-grid {
            grid-template-columns: repeat(6, 1fr);
            gap: 1.5rem;
        }
    }
    .game-card {
        background-color: #ffffff;
        border-radius: 15px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        display: block;
        position: relative;
        opacity: 0;
        animation: fadeIn 0.5s forwards;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .game-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }
    .game-card-image-wrapper {
        aspect-ratio: 1 / 1;
        overflow: hidden;
        background-color: #f0f0f0;
    }
    .game-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }
    .game-card:hover img {
        transform: scale(1.1);
    }
    .game-card-content {
        padding: 1rem;
    }
    .game-card-title {
        font-weight: 500;
        margin-bottom: 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.9rem;
    }
    .game-card-cta {
        background-color: #0d6efd;
        color: white;
        text-align: center;
        padding: 0.75rem;
        font-weight: 500;
        transition: background-color 0.3s;
    }
    .game-card:hover .game-card-cta {
        background-color: #0b5ed7;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<h1 class="mb-4 fw-bold">ເລືອກເກມທີ່ຕ້ອງການເຕີມ</h1>

<div class="game-grid">
    <?php if ($games_result && $games_result->num_rows > 0): ?>
        <?php while($game = $games_result->fetch_assoc()): ?>
            <?php
                // --- ເພີ່ມເງື່ອນໄຂກວດສອບແຫຼ່ງຂໍ້ມູນຂອງເກມບ່ອນນີ້ ---
                $link_url = '';
                if ($game['source_type'] == 'toko') {
                    $link_url = "topup.php?game_id=" . $game['id'];
                } else { // source_type == 'smileone'
                    $link_url = "smileone_topup.php?game_id=" . $game['id'];
                }

                // ກວດສອບ URL ຮູບພາບ, ຖ້າບໍ່ມີໃຫ້ໃຊ້ຮູບ placeholder
                // ສົມມຸດວ່າເຈົ້າມີຮູບ placeholder.png ຢູ່ໃນ folder admin/img/
                $image_url = (!empty($game['image_url'])) ? '../' . htmlspecialchars($game['image_url']) : 'path/to/your/placeholder/image.png';
            ?>
            <a href="<?php echo $link_url; ?>" class="game-card">
                <div class="game-card-image-wrapper">
                    <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($game['name']); ?>">
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
            <p class="text-center fs-5 text-muted">ຍັງບໍ່ມີເກມເປີດໃຫ້ບໍລິການໃນຂະນະນີ້.</p>
        </div>
    <?php endif; ?>
</div>

</main> 
<footer class="container mt-5 py-4 text-center text-muted border-top">
    <p>&copy; <?php echo date('Y'); ?> Topup Store</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.game-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.05}s`;
    });
});
</script>

</body>
</html>
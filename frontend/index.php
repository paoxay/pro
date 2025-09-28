<?php
// File: /frontend/index.php (Corrected Path & Final UI)

// Correct path to find header.php outside the 'frontend' folder
require_once 'header.php'; 

// Fetch all active games from the database
$games_result = $conn->query("SELECT * FROM games WHERE status = 'active' ORDER BY name ASC");
?>

<style>
    .game-grid {
        display: grid;
        /* Mobile: 3 columns */
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }

    /* Desktop: 6 columns */
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
        opacity: 0; /* Hidden by default for the loading animation */
        animation: fadeIn 0.5s forwards;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .game-card:hover {
        transform: translateY(-10px); /* Lifts the card up on hover */
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }
    .game-card-image-wrapper {
        aspect-ratio: 1 / 1; /* Creates a perfect square for the image container */
        overflow: hidden;
        background-color: #f0f0f0; /* Placeholder color */
    }
    .game-card img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* This is the magic for 512x512 effect */
        transition: transform 0.4s ease;
    }
    .game-card:hover img {
        transform: scale(1.1); /* Zoom effect on hover */
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
            <a href="topup.php?game_id=<?php echo $game['id']; ?>" class="game-card">
                <div class="game-card-image-wrapper">
                    <img src="../<?php echo htmlspecialchars($game['image_url']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>">
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

</main> <footer class="container mt-5 py-4 text-center text-muted border-top">
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
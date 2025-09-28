<?php
// File: admin/delete_game.php (Hard Delete Version)
require_once 'auth_check.php';
require_once 'db_connect.php';

$game_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($game_id <= 0) {
    header("location: manage_games.php");
    exit;
}

// Start a transaction to ensure all deletions succeed or none at all
$conn->begin_transaction();

try {
    // 1. Get the image path before deleting the game record
    $stmt_get = $conn->prepare("SELECT image_url FROM games WHERE id = ?");
    $stmt_get->bind_param("i", $game_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    $image_path = null;
    if ($result->num_rows === 1) {
        $game = $result->fetch_assoc();
        $image_path = $game['image_url'];
    }
    $stmt_get->close();

    // 2. Find all package IDs associated with this game
    $stmt_find_packages = $conn->prepare("SELECT id FROM game_packages WHERE game_id = ?");
    $stmt_find_packages->bind_param("i", $game_id);
    $stmt_find_packages->execute();
    $packages_result = $stmt_find_packages->get_result();
    
    $package_ids = [];
    while ($row = $packages_result->fetch_assoc()) {
        $package_ids[] = $row['id'];
    }
    $stmt_find_packages->close();

    // 3. If there are packages, delete all orders linked to them
    if (!empty($package_ids)) {
        // Create placeholders for the IN clause (?, ?, ?)
        $placeholders = implode(',', array_fill(0, count($package_ids), '?'));
        // Define the type string (e.g., 'iii' for 3 IDs)
        $types = str_repeat('i', count($package_ids));
        
        $stmt_delete_orders = $conn->prepare("DELETE FROM orders WHERE package_id IN ($placeholders)");
        $stmt_delete_orders->bind_param($types, ...$package_ids);
        $stmt_delete_orders->execute();
        $stmt_delete_orders->close();
    }
    
    // 4. Now, delete the game itself. ON DELETE CASCADE will handle deleting the packages.
    $stmt_delete_game = $conn->prepare("DELETE FROM games WHERE id = ?");
    $stmt_delete_game->bind_param("i", $game_id);
    $stmt_delete_game->execute();
    $stmt_delete_game->close();

    // If all queries were successful, commit the changes
    $conn->commit();

    // 5. If database deletion was successful, delete the image file from the server
    if (!empty($image_path) && file_exists("../" . $image_path)) {
        unlink("../" . $image_path);
    }

} catch (Exception $e) {
    // If any query fails, roll back all changes
    $conn->rollback();
    // Optional: Set an error message to display on the next page
    // $_SESSION['error_message'] = "Failed to delete game: " . $e->getMessage();
}

$conn->close();

// Redirect back to the manage games page
header("location: manage_games.php");
exit;
?>
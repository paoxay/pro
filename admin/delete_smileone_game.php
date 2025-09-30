<?php
// File: admin/delete_smileone_game.php
require_once 'auth_check.php';
require_once 'db_connect.php';

$game_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($game_id <= 0) {
    header("location: manage_smileone_games.php");
    exit;
}

// ເລີ່ມ Transaction ເພື່ອຄວາມປອດໄພ
$conn->begin_transaction();

try {
    // 1. ດຶງ URL ຂອງຮູບພາບກ່ອນທີ່ຈະລຶບ record
    $stmt_get = $conn->prepare("SELECT image_url FROM smileone_games WHERE id = ?");
    $stmt_get->bind_param("i", $game_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    $image_path = null;
    if ($result->num_rows === 1) {
        $game = $result->fetch_assoc();
        $image_path = $game['image_url'];
    }
    $stmt_get->close();

    // 2. ຊອກຫາ ID ຂອງແພັກເກັດທັງໝົດທີ່ກ່ຽວຂ້ອງກັບເກມນີ້
    $stmt_find_packages = $conn->prepare("SELECT id FROM smileone_packages WHERE game_id = ?");
    $stmt_find_packages->bind_param("i", $game_id);
    $stmt_find_packages->execute();
    $packages_result = $stmt_find_packages->get_result();
    
    $package_ids = [];
    while ($row = $packages_result->fetch_assoc()) {
        $package_ids[] = $row['id'];
    }
    $stmt_find_packages->close();

    // 3. ຖ້າມີແພັກເກັດ, ໃຫ້ລຶບອໍເດີ້ທັງໝົດທີ່ອ້າງອີງເຖິງແພັກເກັດເຫຼົ່ານັ້ນກ່ອນ
    if (!empty($package_ids)) {
        $placeholders = implode(',', array_fill(0, count($package_ids), '?'));
        $types = str_repeat('i', count($package_ids));
        
        $stmt_delete_orders = $conn->prepare("DELETE FROM smileone_orders WHERE package_id IN ($placeholders)");
        $stmt_delete_orders->bind_param($types, ...$package_ids);
        $stmt_delete_orders->execute();
        $stmt_delete_orders->close();
    }
    
    // 4. ລຶບເກມ (ON DELETE CASCADE ຈະລຶບ packages ແລະ game_fields ໃຫ້ອັດຕະໂນມັດ)
    $stmt_delete_game = $conn->prepare("DELETE FROM smileone_games WHERE id = ?");
    $stmt_delete_game->bind_param("i", $game_id);
    $stmt_delete_game->execute();
    $stmt_delete_game->close();

    // ຖ້າທຸກຢ່າງສຳເລັດ, ໃຫ້ commit
    $conn->commit();

    // 5. ຖ້າລຶບຈາກຖານຂໍ້ມູນສຳເລັດ, ໃຫ້ລຶບໄຟລ໌ຮູບພາບອອກຈາກ server
    if (!empty($image_path) && file_exists("../" . $image_path)) {
        unlink("../" . $image_path);
    }

} catch (Exception $e) {
    // ຖ້າມີຂໍ້ຜິດພາດ, ໃຫ້ rollback ທັງໝົດ
    $conn->rollback();
    // ສາມາດຕັ້ງ error message ໄດ້ຖ້າຕ້ອງການ
    // $_SESSION['error_message'] = "Failed to delete game: " . $e->getMessage();
}

$conn->close();

// ກັບໄປໜ້າຈັດການເກມ
header("location: manage_smileone_games.php");
exit;
?>
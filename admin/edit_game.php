<?php
// File: admin/edit_game.php (Restructured and Corrected Version)

// --- PART 1: ALL PHP LOGIC FIRST (No HTML Output) ---
require_once 'auth_check.php'; // Includes session_start()
require_once 'db_connect.php';

$error_message = "";
$game_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($game_id <= 0) {
    header("location: manage_games.php");
    exit;
}

// Handle form submission BEFORE any HTML is outputted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        $game_id_post = $_POST['game_id'];
        $game_name = trim($_POST['game_name']);
        $description = $_POST['description'];
        $status = $_POST['status'];
        $exchange_rate = (float)($_POST['exchange_rate'] ?? 1.0);
        $default_markup = (int)($_POST['default_markup'] ?? 15);
        $current_image_path = $_POST['current_image'];
        $image_path = $current_image_path;

        if (isset($_FILES["game_image"]) && $_FILES["game_image"]["error"] == 0) {
            $target_dir = "../uploads/games/";
             if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
            $new_filename = uniqid() . '-' . basename($_FILES["game_image"]["name"]);
            if (move_uploaded_file($_FILES["game_image"]["tmp_name"], $target_dir . $new_filename)) {
                if (!empty($current_image_path) && file_exists("../" . $current_image_path)) {
                    unlink("../" . $current_image_path);
                }
                $image_path = "uploads/games/" . $new_filename;
            }
        }

        $stmt_game = $conn->prepare("UPDATE games SET name = ?, description = ?, status = ?, image_url = ?, exchange_rate = ?, default_markup = ? WHERE id = ?");
        $stmt_game->bind_param("ssssdii", $game_name, $description, $status, $image_path, $exchange_rate, $default_markup, $game_id_post);
        $stmt_game->execute();
        $stmt_game->close();

        $stmt_delete_fields = $conn->prepare("DELETE FROM game_fields WHERE game_id = ?");
        $stmt_delete_fields->bind_param("i", $game_id_post);
        $stmt_delete_fields->execute();
        $stmt_delete_fields->close();

        if (isset($_POST['field_label']) && is_array($_POST['field_label'])) {
            $stmt_field = $conn->prepare("INSERT INTO game_fields (game_id, field_label, field_name, placeholder, field_type, field_options, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_field->bind_param("isssssi", $game_id_post, $label_var, $name_var, $placeholder_var, $type_var, $options_var, $order_var);
            foreach ($_POST['field_label'] as $index => $label) {
                if (!empty($label)) {
                    $label_var = $label;
                    $name_var = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $label_var), '_'));
                    $placeholder_var = $label_var;
                    $type_var = $_POST['field_type'][$index] ?? 'text';
                    $options_var = ($type_var === 'select') ? ($_POST['field_options'][$index] ?? '') : null;
                    $order_var = $index + 1;
                    $stmt_field->execute();
                }
            }
            $stmt_field->close();
        }

        $conn->commit();
        // This header() call will now work perfectly!
        header("location: manage_games.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
    }
}

// --- PART 2: DATA FETCHING FOR PAGE DISPLAY ---
// This part runs only if it's a GET request or if the POST failed.
$stmt_get_game = $conn->prepare("SELECT * FROM games WHERE id = ?");
$stmt_get_game->bind_param("i", $game_id);
$stmt_get_game->execute();
$result_game = $stmt_get_game->get_result();
if ($result_game->num_rows === 0) {
    $error_message = "Error: Game not found";
    $game = []; // Create empty array to prevent errors in the HTML form
} else {
    $game = $result_game->fetch_assoc();
}
$stmt_get_game->close();

$stmt_get_fields = $conn->prepare("SELECT * FROM game_fields WHERE game_id = ? ORDER BY display_order ASC");
$stmt_get_fields->bind_param("i", $game_id);
$stmt_get_fields->execute();
$fields = $stmt_get_fields->get_result();


// --- PART 3: HTML OUTPUT STARTS HERE ---
// Now it's safe to include the header file.
require_once 'admin_header.php';
?>
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({ selector: 'textarea#game_description' });
</script>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">ແກ້ໄຂເກມ: <?php echo htmlspecialchars($game['name'] ?? 'Error'); ?></h1>
    <?php if(!empty($error_message)): ?> <div class="alert alert-danger"><?php echo $error_message; ?></div> <?php endif; ?>
    
    <form action="edit_game.php?id=<?php echo $game_id; ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
        <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($game['image_url']); ?>">

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="mb-3"><label class="form-label fw-bold">1. ຮູບພາບເກມ:</label>
                    <?php if(!empty($game['image_url'])): ?>
                    <div class="mb-2">
                        <img src="../<?php echo htmlspecialchars($game['image_url']); ?>" style="max-width: 100px; border-radius: 5px;">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="game_image" class="form-control" accept="image/*">
                    <small class="form-text text-muted">ເລືອກໄຟລ໌ໃໝ່ຖ້າຕ້ອງການປ່ຽນ</small>
                </div>
                <div class="mb-3"><label class="form-label fw-bold">2. ຊື່ເກມ:</label><input type="text" name="game_name" class="form-control" value="<?php echo htmlspecialchars($game['name']); ?>" required></div>
                <div class="mb-3"><label class="form-label fw-bold">3. ເນື້ອຫາ/ຄຳອະທິບາຍ:</label><textarea id="game_description" name="description"><?php echo htmlspecialchars($game['description']); ?></textarea></div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">ອັດຕາແລກປ່ຽນ (ຈາກ IDR, ຕົວຢ່າງ: 0.0014):</label>
                        <input type="text" name="exchange_rate" class="form-control" value="<?php echo htmlspecialchars($game['exchange_rate']); ?>">
                        <small class="form-text text-muted">ໃຊ້ສະເພາະເກມທີ່ດຶງຈາກ API TokoVoucher.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">ກຳໄລເລີ່ມຕົ້ນ (%):</label>
                        <input type="number" name="default_markup" class="form-control" value="<?php echo htmlspecialchars($game['default_markup']); ?>">
                        <small class="form-text text-muted">ລະບົບຈະເອົາ % ນີ້ໄປບວກໃສ່ລາຄາຕົ້ນທຶນ.</small>
                    </div>
                </div>

                <div class="mb-3"><label class="form-label fw-bold">ສະຖານະ:</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo ($game['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($game['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">4. ຕົວກຳຫນົດຊ່ອງຂໍ້ມູນ</h6>
                <button type="button" id="addFieldBtn" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> ເພີ່ມຊ່ອງຂໍ້ມູນ</button>
            </div>
            <div class="card-body" id="fieldsContainer">
                <?php while($field = $fields->fetch_assoc()): ?>
                    <div class="row g-2 mb-3 p-2 border rounded field-row align-items-center">
                        <div class="col-md-5"><input type="text" name="field_label[]" class="form-control" placeholder="ປ້າຍກຳກັບ" required value="<?php echo htmlspecialchars($field['field_label']); ?>"></div>
                        <div class="col-md-3">
                            <select name="field_type[]" class="form-select field-type-select">
                                <option value="text" <?php echo ($field['field_type'] == 'text') ? 'selected' : ''; ?>>Text Input</option>
                                <option value="select" <?php echo ($field['field_type'] == 'select') ? 'selected' : ''; ?>>Select Dropdown</option>
                                <option value="number" <?php echo ($field['field_type'] == 'number') ? 'selected' : ''; ?>>Number Input</option>
                            </select>
                        </div>
                        <div class="col-md-3 options-container" style="<?php echo ($field['field_type'] == 'select') ? '' : 'display: none;'; ?>">
                            <input name="field_options[]" class="form-control" placeholder="ໂຕເລືອກ (ຄັ່ນດ້ວຍ,)" value="<?php echo htmlspecialchars($field['field_options']); ?>">
                        </div>
                        <div class="col-md-1"><button type="button" class="btn btn-danger w-100 removeFieldBtn" title="ລຶບ"><i class="fas fa-trash"></i></button></div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">ອັບເດດຂໍ້ມູນເກມ</button>
        <a href="manage_games.php" class="btn btn-secondary btn-lg">ຍົກເລີກ</a>
    </form>
</div>

<template id="fieldRowTemplate">
    <div class="row g-2 mb-3 p-2 border rounded field-row align-items-center">
        <div class="col-md-5"><input type="text" name="field_label[]" class="form-control" placeholder="ປ້າຍກຳກັບ (ເຊັ່ນ: ກະລຸນາປ້ອນ User ID)" required></div>
        <div class="col-md-3">
            <select name="field_type[]" class="form-select field-type-select">
                <option value="text" selected>Text Input</option>
                <option value="select">Select Dropdown</option>
                <option value="number">Number Input</option>
            </select>
        </div>
        <div class="col-md-3 options-container" style="display: none;">
            <input name="field_options[]" class="form-control" placeholder="ໂຕເລືອກ (ຄັ່ນດ້ວຍ,) ເຊັ່ນ ASIA,EU">
        </div>
        <div class="col-md-1"><button type="button" class="btn btn-danger w-100 removeFieldBtn" title="ລຶບ"><i class="fas fa-trash"></i></button></div>
    </div>
</template>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('fieldsContainer');
    const template = document.getElementById('fieldRowTemplate');
    document.getElementById('addFieldBtn').addEventListener('click', function() {
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
    });
    container.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.removeFieldBtn');
        if (removeBtn) { removeBtn.closest('.field-row').remove(); }
    });
    container.addEventListener('change', function(e) {
        if (e.target.classList.contains('field-type-select')) {
            const row = e.target.closest('.field-row');
            const optionsContainer = row.querySelector('.options-container');
            optionsContainer.style.display = (e.target.value === 'select') ? 'block' : 'none';
        }
    });
});
</script>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// File: admin/edit_game.php (Restructured to fix "Headers already sent" error)

// --- STEP 1: All PHP LOGIC FIRST ---
// Move all form processing and potential redirects before any HTML output.
require_once 'auth_check.php'; // Must be included here for security
require_once 'db_connect.php';

$error_message = "";
$game_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($game_id <= 0) { 
    header("Location: manage_games.php"); // Redirect if ID is invalid
    exit;
}

// --- Process Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        $game_id_post = $_POST['game_id'];
        $game_name = trim($_POST['game_name']);
        $description = $_POST['description'];
        $status = $_POST['status'];
        $current_image_path = $_POST['current_image'];
        $image_path = $current_image_path;

        if (isset($_FILES["game_image"]) && $_FILES["game_image"]["error"] == 0) {
            $target_dir = "../uploads/games/";
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            $new_filename = uniqid() . '-' . basename($_FILES["game_image"]["name"]);
            if (move_uploaded_file($_FILES["game_image"]["tmp_name"], $target_dir . $new_filename)) {
                if (!empty($current_image_path) && file_exists("../" . $current_image_path)) {
                    @unlink("../" . $current_image_path);
                }
                $image_path = "uploads/games/" . $new_filename;
            }
        }

        $stmt_game = $conn->prepare("UPDATE games SET name = ?, description = ?, status = ?, image_url = ? WHERE id = ?");
        $stmt_game->bind_param("ssssi", $game_name, $description, $status, $image_path, $game_id_post);
        $stmt_game->execute();
        $stmt_game->close();

        $stmt_delete_fields = $conn->prepare("DELETE FROM game_fields WHERE game_id = ?");
        $stmt_delete_fields->bind_param("i", $game_id_post);
        $stmt_delete_fields->execute();
        $stmt_delete_fields->close();

        if (isset($_POST['field_label']) && is_array($_POST['field_label'])) {
            $stmt_field = $conn->prepare("INSERT INTO game_fields (game_id, field_label, field_name, placeholder, field_type, field_options, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($_POST['field_label'] as $index => $label) {
                if (!empty($label)) {
                    $label_var = $label;
                    $name_var = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $label_var), '_'));
                    $placeholder_var = $_POST['field_placeholder'][$index] ?? $label_var;
                    $type_var = $_POST['field_type'][$index] ?? 'text';
                    $options_var = ($type_var === 'select') ? ($_POST['field_options'][$index] ?? '') : null;
                    $order_var = $index + 1;
                    
                    $stmt_field->bind_param("isssssi", $game_id_post, $label_var, $name_var, $placeholder_var, $type_var, $options_var, $order_var);
                    $stmt_field->execute();
                }
            }
            $stmt_field->close();
        }

        $conn->commit();
        // This header() call will now work correctly because no HTML has been sent yet.
        header("location: manage_games.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
    }
}

// --- STEP 2: Start HTML Output ---
// Now that all logic is done, we can safely include the header and display the page.
require_once 'admin_header.php';

// --- Fetch Existing Data to show in form ---
$stmt_get_game = $conn->prepare("SELECT * FROM games WHERE id = ?");
$stmt_get_game->bind_param("i", $game_id);
$stmt_get_game->execute();
$result_game = $stmt_get_game->get_result();
if ($result_game->num_rows === 0) { 
    echo "</div></body></html>"; // Close HTML tags properly
    die("<h2>Error: Game not found</h2>"); 
}
$game = $result_game->fetch_assoc();
$stmt_get_game->close();

$stmt_get_fields = $conn->prepare("SELECT * FROM game_fields WHERE game_id = ? ORDER BY display_order ASC");
$stmt_get_fields->bind_param("i", $game_id);
$stmt_get_fields->execute();
$fields = $stmt_get_fields->get_result();

?>
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({ selector: 'textarea#game_description' });
</script>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">ແກ້ໄຂເກມ: <?php echo htmlspecialchars($game['name']); ?></h1>
    <?php if(!empty($error_message)): ?> <div class="alert alert-danger"><?php echo $error_message; ?></div> <?php endif; ?>
    
    <form action="edit_game.php?id=<?php echo $game_id; ?>" method="post" enctype="multipart/form-data" id="editGameForm">
        <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
        <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($game['image_url']); ?>">

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="mb-3"><label class="form-label fw-bold">ຮູບພາບເກມ:</label>
                    <?php if(!empty($game['image_url'])): ?>
                    <div class="mb-2">
                        <img src="../<?php echo htmlspecialchars($game['image_url']); ?>" style="max-width: 100px; border-radius: 5px;">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="game_image" class="form-control" accept="image/*">
                </div>
                <div class="mb-3"><label class="form-label fw-bold">ຊື່ເກມ:</label><input type="text" name="game_name" class="form-control" value="<?php echo htmlspecialchars($game['name']); ?>" required></div>
                <div class="mb-3"><label class="form-label fw-bold">ເນື້ອຫາ/ຄຳອະທິບາຍ:</label><textarea id="game_description" name="description"><?php echo htmlspecialchars($game['description']); ?></textarea></div>
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
                <h6 class="m-0 font-weight-bold text-primary">ຕົວກຳຫນົດຊ່ອງຂໍ້ມູນ</h6>
                <button type="button" id="addFieldBtn" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> ເພີ່ມຊ່ອງຂໍ້ມູນ</button>
            </div>
            <div class="card-body" id="fieldsContainer">
                <?php while($field = $fields->fetch_assoc()): 
                    $options = json_decode($field['field_options'], true);
                ?>
                    <div class="row g-2 mb-3 p-3 border rounded field-row align-items-center">
                        <div class="col-md-4"><label>ປ້າຍກຳກັບ (Label)</label><input type="text" name="field_label[]" class="form-control" placeholder="ເຊັ່ນ: ກະລຸນາເລືອກ Server" required value="<?php echo htmlspecialchars($field['field_label']); ?>"></div>
                        <div class="col-md-4"><label>Placeholder</label><input type="text" name="field_placeholder[]" class="form-control" placeholder="ເຊັ່ນ: ປ້ອນ User ID ຂອງທ່ານ" value="<?php echo htmlspecialchars($field['placeholder']); ?>"></div>
                        <div class="col-md-3">
                            <label>ປະເພດ</label>
                            <select name="field_type[]" class="form-select field-type-select">
                                <option value="text" <?php echo ($field['field_type'] == 'text') ? 'selected' : ''; ?>>Text Input</option>
                                <option value="select" <?php echo ($field['field_type'] == 'select') ? 'selected' : ''; ?>>Select Dropdown</option>
                                <option value="number" <?php echo ($field['field_type'] == 'number') ? 'selected' : ''; ?>>Number Input</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-danger w-100 removeFieldBtn" title="ລຶບ"><i class="fas fa-trash"></i></button></div>
                        <div class="col-12 mt-3 options-container" style="<?php echo ($field['field_type'] == 'select') ? '' : 'display: none;'; ?>">
                            <h6>ໂຕເລືອກ (Options)</h6>
                            <div class="options-list p-2 border rounded">
                                <?php if (is_array($options)): foreach ($options as $opt): ?>
                                <div class="row g-2 mb-2 option-row">
                                    <div class="col-5"><input type="text" class="form-control option-text" placeholder="ຊື່ທີ່ສະແດງ (ເຊັ່ນ: Asia)" value="<?php echo htmlspecialchars($opt['text']); ?>"></div>
                                    <div class="col-5"><input type="text" class="form-control option-value" placeholder="ຄ່າທີ່ຈະສົ່ງ (ເຊັ່ນ: asia_sv)" value="<?php echo htmlspecialchars($opt['value']); ?>"></div>
                                    <div class="col-2"><button type="button" class="btn btn-sm btn-outline-danger w-100 removeOptionBtn"><i class="fas fa-times"></i></button></div>
                                </div>
                                <?php endforeach; endif; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-info mt-2 addOptionBtn">+ ເພີ່ມໂຕເລືອກ</button>
                            <input type="hidden" class="real-options-input" name="field_options[]">
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">ອັບເດດຂໍ້ມູນເກມ</button>
    </form>
</div>

<template id="fieldRowTemplate">
    <div class="row g-2 mb-3 p-3 border rounded field-row align-items-center">
        <div class="col-md-4"><label>ປ້າຍກຳກັບ (Label)</label><input type="text" name="field_label[]" class="form-control" placeholder="ເຊັ່ນ: ກະລຸນາເລືອກ Server" required></div>
        <div class="col-md-4"><label>Placeholder</label><input type="text" name="field_placeholder[]" class="form-control" placeholder="ເຊັ່ນ: ປ້ອນ User ID ຂອງທ່ານ"></div>
        <div class="col-md-3"><label>ປະເພດ</label>
            <select name="field_type[]" class="form-select field-type-select">
                <option value="text" selected>Text Input</option>
                <option value="select">Select Dropdown</option>
                <option value="number">Number Input</option>
            </select>
        </div>
        <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-danger w-100 removeFieldBtn" title="ລຶບ"><i class="fas fa-trash"></i></button></div>
        <div class="col-12 mt-3 options-container" style="display: none;">
            <h6>ໂຕເລືອກ (Options)</h6>
            <div class="options-list p-2 border rounded"></div>
            <button type="button" class="btn btn-sm btn-info mt-2 addOptionBtn">+ ເພີ່ມໂຕເລືອກ</button>
            <input type="hidden" class="real-options-input" name="field_options[]">
        </div>
    </div>
</template>

<template id="optionRowTemplate">
    <div class="row g-2 mb-2 option-row">
        <div class="col-5"><input type="text" class="form-control option-text" placeholder="ຊື່ທີ່ສະແດງ (ເຊັ່ນ: Asia)"></div>
        <div class="col-5"><input type="text" class="form-control option-value" placeholder="ຄ່າທີ່ຈະສົ່ງ (ເຊັ່ນ: asia_sv)"></div>
        <div class="col-2"><button type="button" class="btn btn-sm btn-outline-danger w-100 removeOptionBtn"><i class="fas fa-times"></i></button></div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fieldsContainer = document.getElementById('fieldsContainer');
    const fieldRowTemplate = document.getElementById('fieldRowTemplate');
    const optionRowTemplate = document.getElementById('optionRowTemplate');

    document.getElementById('addFieldBtn').addEventListener('click', () => {
        const clone = fieldRowTemplate.content.cloneNode(true);
        fieldsContainer.appendChild(clone);
    });

    fieldsContainer.addEventListener('click', function(e) {
        if (e.target.closest('.removeFieldBtn')) {
            e.target.closest('.field-row').remove();
        }
        if (e.target.closest('.addOptionBtn')) {
            const optionsList = e.target.closest('.options-container').querySelector('.options-list');
            const clone = optionRowTemplate.content.cloneNode(true);
            optionsList.appendChild(clone);
        }
        if (e.target.closest('.removeOptionBtn')) {
            e.target.closest('.option-row').remove();
        }
    });

    fieldsContainer.addEventListener('change', function(e) {
        if (e.target.classList.contains('field-type-select')) {
            const row = e.target.closest('.field-row');
            const optionsContainer = row.querySelector('.options-container');
            optionsContainer.style.display = (e.target.value === 'select') ? 'block' : 'none';
        }
    });
    
    document.getElementById('editGameForm').addEventListener('submit', function(e) {
        const allFieldRows = fieldsContainer.querySelectorAll('.field-row');
        allFieldRows.forEach(row => {
            const type = row.querySelector('.field-type-select').value;
            const realOptionsInput = row.querySelector('.real-options-input');
            if (type === 'select') {
                const options = [];
                row.querySelectorAll('.option-row').forEach(optRow => {
                    const text = optRow.querySelector('.option-text').value.trim();
                    const value = optRow.querySelector('.option-value').value.trim();
                    if (text && value) {
                        options.push({ text: text, value: value });
                    }
                });
                realOptionsInput.value = JSON.stringify(options);
            } else {
                realOptionsInput.value = '';
            }
        });
    });
});
</script>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
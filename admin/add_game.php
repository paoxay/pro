<?php
// File: admin/add_game.php (Full Corrected Version)
require_once 'admin_header.php';
require_once 'db_connect.php';

$error_message = ""; // Initialize error message variable

// --- Process only if the form was submitted ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($_POST["game_name"]) || !isset($_POST["description"]) || !isset($_POST["status"])) {
        $error_message = "ຂໍ້ມູນບໍ່ຄົບຖ້ວນ, ກະລຸນາລອງໃໝ່.";
    } else {
        $conn->begin_transaction();
        try {
            // 1. Save main game data
            $game_name = trim($_POST["game_name"]);
            $description = $_POST["description"];
            $status = $_POST["status"];
            // -- ຄ່າໃໝ່ທີ່ເພີ່ມເຂົ້າມາ --
            $exchange_rate = (float)($_POST['exchange_rate'] ?? 1.0);
            $default_markup = (int)($_POST['default_markup'] ?? 15);
            $image_path = null;

            if (isset($_FILES["game_image"]) && $_FILES["game_image"]["error"] == 0) {
                $target_dir = "../uploads/games/";
                if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
                $new_filename = uniqid() . '-' . basename($_FILES["game_image"]["name"]);
                if (move_uploaded_file($_FILES["game_image"]["tmp_name"], $target_dir . $new_filename)) {
                    $image_path = "uploads/games/" . $new_filename;
                } else {
                    throw new Exception("ບໍ່ສາມາດອັບໂຫລດຮູບໄດ້.");
                }
            }

            // -- ອັບເດດຄຳສັ່ງ SQL ໃຫ້ຮອງຮັບຄ່າໃໝ່ --
            $stmt_game = $conn->prepare("INSERT INTO games (name, description, status, image_url, exchange_rate, default_markup) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt_game === false) {
                throw new Exception("SQL Prepare Failed for games: " . $conn->error);
            }
            // -- ອັບເດດ bind_param ໃຫ້ກົງກັນ --
            $stmt_game->bind_param("ssssdi", $game_name, $description, $status, $image_path, $exchange_rate, $default_markup);
            $stmt_game->execute();
            $game_id = $conn->insert_id;
            $stmt_game->close();

            // 2. Save Custom Fields (Corrected Logic)
            if (isset($_POST['field_label']) && is_array($_POST['field_label'])) {
                $sql_field = "INSERT INTO game_fields (game_id, field_label, field_name, placeholder, field_type, field_options, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_field = $conn->prepare($sql_field);

                if ($stmt_field === false) {
                    throw new Exception("SQL Prepare Failed for game_fields: " . $conn->error);
                }
                
                $stmt_field->bind_param("isssssi", $game_id, $label_var, $name_var, $placeholder_var, $type_var, $options_var, $order_var);

                foreach ($_POST['field_label'] as $index => $current_label) {
                    if (!empty($current_label)) {
                        $label_var = $current_label;
                        $name_var = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $label_var), '_'));
                        $placeholder_var = $label_var;
                        $type_var = $_POST['field_type'][$index] ?? 'text';
                        $options_var = ($type_var === 'select') ? ($_POST['field_options'][$index] ?? '') : null;
                        $order_var = $index + 1;
                        
                        if (!$stmt_field->execute()) {
                            throw new Exception("Execute failed for game_fields: " . $stmt_field->error);
                        }
                    }
                }
                $stmt_field->close();
            }

            $conn->commit();
            header("location: manage_games.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
        }
    }
}
?>
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: 'textarea#game_description',
    plugins: 'lists link image media table code help wordcount',
    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | help'
  });
</script>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">ເພີ່ມເກມໃໝ່</h1>
    <?php if(!empty($error_message)): ?> <div class="alert alert-danger"><?php echo $error_message; ?></div> <?php endif; ?>
    
    <form action="add_game.php" method="post" enctype="multipart/form-data">
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="mb-3"><label class="form-label fw-bold">1. ອັບໂຫລດຮູບພາບເກມ:</label><input type="file" name="game_image" class="form-control" accept="image/*" required></div>
                <div class="mb-3"><label class="form-label fw-bold">2. ໃສ່ຊື່ເກມ:</label><input type="text" name="game_name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label fw-bold">3. ເນື້ອຫາ/ຄຳອະທິບາຍ (ສະແດງໃນໜ້າເຕີມເກມ):</label><textarea id="game_description" name="description"></textarea></div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">ອັດຕາແລກປ່ຽນ (ຈາກ IDR, ຕົວຢ່າງ: 0.0014):</label>
                        <input type="text" name="exchange_rate" class="form-control" value="0.0014">
                        <small class="form-text text-muted">ໃຊ້ສະເພາະເກມທີ່ດຶງຈາກ API TokoVoucher.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">ກຳໄລເລີ່ມຕົ້ນ (%):</label>
                        <input type="number" name="default_markup" class="form-control" value="15">
                        <small class="form-text text-muted">ລະບົບຈະເອົາ % ນີ້ໄປບວກໃສ່ລາຄາຕົ້ນທຶນ.</small>
                    </div>
                </div>
                <div class="mb-3"><label class="form-label fw-bold">ສະຖານະ:</label><select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">4. ຕົວກຳຫນົດຊ່ອງຂໍ້ມູນ (UID, Server, etc.)</h6>
                <button type="button" id="addFieldBtn" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> ເພີ່ມຊ່ອງຂໍ້ມູນ</button>
            </div>
            <div class="card-body" id="fieldsContainer">
                </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">ບັນທຶກເກມ</button>
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
    
    function addField() {
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
    }
    
    addField();

    document.getElementById('addFieldBtn').addEventListener('click', addField);

    container.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.removeFieldBtn');
        if (removeBtn) {
            removeBtn.closest('.field-row').remove();
        }
    });

    container.addEventListener('change', function(e) {
        if (e.target.classList.contains('field-type-select')) {
            const row = e.target.closest('.field-row');
            const optionsContainer = row.querySelector('.options-container');
            if (e.target.value === 'select') {
                optionsContainer.style.display = 'block';
            } else {
                optionsContainer.style.display = 'none';
            }
        }
    });
});
</script>

</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
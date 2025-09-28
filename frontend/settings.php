<?php
// File: /frontend/settings.php (Hashed Password Version)
require_once 'header.php';

// --- Password change processing ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];
    $member_id = $_SESSION['member_id'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $_SESSION['error_message'] = "ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບທຸກຊ່ອງ.";
    } elseif ($new_password !== $confirm_new_password) {
        $_SESSION['error_message'] = "ລະຫັດຜ່ານໃໝ່ ແລະ ຢືນຢັນລະຫັດຜ່ານໃໝ່ບໍ່ຕົງກັນ.";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error_message'] = "ລະຫັດຜ່ານໃໝ່ຕ້ອງມີຢ່າງໜ້ອຍ 6 ຕົວອັກສອນ.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM members WHERE id = ?");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $member_data = $result->fetch_assoc();
        $db_password_hash = $member_data['password'];
        $stmt->close();

        // THE CHANGE IS HERE: Verify the current password against the hash
        if (password_verify($current_password, $db_password_hash)) {
            // If correct, hash the new password and update
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE members SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_password_hash, $member_id);
            if ($stmt_update->execute()) {
                $_SESSION['success_message'] = "ປ່ຽນລະຫັດຜ່ານສຳເລັດແລ້ວ!";
            } else {
                $_SESSION['error_message'] = "ເກີດຂໍ້ຜິດພາດໃນການອັບເດດລະຫັດຜ່ານ.";
            }
            $stmt_update->close();
        } else {
            $_SESSION['error_message'] = "ລະຫັດຜ່ານປັດຈຸບັນບໍ່ຖືກຕ້ອງ!";
        }
    }
    header("Location: settings.php");
    exit();
}
?>

<h1 class="mb-4">ຕັ້ງຄ່າບັນຊີ</h1>
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header"><h5 class="mb-0">ປ່ຽນລະຫັດຜ່ານ</h5></div>
            <div class="card-body">
                <?php
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
                    unset($_SESSION['error_message']);
                }
                ?>
                <form action="settings.php" method="POST">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">ລະຫັດຜ່ານປັດຈຸບັນ:</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">ລະຫັດຜ່ານໃໝ່:</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_new_password" class="form-label">ຢືນຢັນລະຫັດຜ່ານໃໝ່:</label>
                        <input type="password" name="confirm_new_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">ບັນທຶກການປ່ຽນແປງ</button>
                </form>
            </div>
        </div>
    </div>
</div>

</main>
<footer class="container mt-4 text-center text-muted">
    <p>&copy; <?php echo date('Y'); ?> Topup Store</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
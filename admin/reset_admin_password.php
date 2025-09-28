<?php
// File: admin/reset_admin_password.php
require_once 'db_connect.php';

// --- ຕັ້ງຄ່າ ---
$target_username = 'pao';
$new_password = '123'; // ຕັ້ງລະຫັດຜ່ານໃໝ່ທີ່ຕ້ອງການຢູ່ນີ້

// ---  процессинг ---
echo "<h1>Password Reset Script</h1>";

// เข้ารหัสรหัสผ่านใหม่
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// เตรียมคำสั่ง SQL เพื่ออัปเดต
$stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = ?");
$stmt->bind_param("ss", $hashed_password, $target_username);

// รันคำสั่ง
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "<p style='color:green;'>ລະຫັດຜ່ານຂອງ user '<strong>" . htmlspecialchars($target_username) . "</strong>' ຖືກຕັ້ງໃໝ່ສຳເລັດແລ້ວ.</p>";
        echo "<p>ລະຫັດຜ່ານໃໝ່ຄື: <strong>" . htmlspecialchars($new_password) . "</strong></p>";
        echo "<p style='color:red; font-weight:bold;'>ຢ່າລືມລຶບໄຟລ໌นี้ (reset_admin_password.php) ອອກທັນທີ!</p>";
    } else {
        echo "<p style='color:orange;'>ບໍ່ພົບ user '" . htmlspecialchars($target_username) . "' ໃນລະບົບ.</p>";
    }
} else {
    echo "<p style='color:red;'>ເກີດຂໍ້ຜິດພາດໃນການອັບເດດລະຫັດຜ່ານ: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();
?>
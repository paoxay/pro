<?php // File: admin/hash_tool.php
if (isset($_POST['password'])) {
    $password = $_POST['password'];
    echo "<h3>Hashed Password:</h3>";
    echo "<textarea rows='3' style='width:100%;'>" . password_hash($password, PASSWORD_DEFAULT) . "</textarea>";
}
?>
<form method="POST">
    <h2>Password Hash Generator</h2>
    <input type="text" name="password" placeholder="Enter password to hash" required>
    <button type="submit">Generate Hash</button>
</form>
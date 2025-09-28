<?php
// File: admin/index.php
session_start();
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    header("location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
</head>
<body>
    <h2>Admin Login</h2>
    <?php 
        if(isset($_SESSION['login_error'])){
            echo '<p style="color:red;">'.$_SESSION['login_error'].'</p>';
            unset($_SESSION['login_error']);
        }
    ?>
    <form action="check_login.php" method="POST">
        Username: <br>
        <input type="text" name="username" required><br><br>
        Password: <br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>
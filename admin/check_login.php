<?php
// File: admin/check_login.php (Hashed Password Version)
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $user, $hashed_password);
        $stmt->fetch();
        
        // THE CHANGE IS HERE: Use password_verify()
        if (password_verify($password, $hashed_password)) {
            $_SESSION['admin_loggedin'] = true;
            $_SESSION['admin_id'] = $id;
            $_SESSION['admin_username'] = $user;
            header("location: dashboard.php");
            exit();
        } else {
            $_SESSION['login_error'] = "ຊື່ຜູ້ໃຊ້ ຫຼື ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ!";
            header("location: index.php");
            exit();
        }
    } else {
        $_SESSION['login_error'] = "ຊື່ຜູ້ໃຊ້ ຫຼື ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ!";
        header("location: index.php");
        exit();
    }
    $stmt->close();
    $conn->close();
}
?>
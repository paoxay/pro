<?php
// File: /auth.php (Hashed Password Version)
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM members WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $user, $hashed_password);
        $stmt->fetch();
        
        // THE CHANGE IS HERE: Use password_verify()
        if (password_verify($password, $hashed_password)) {
            $_SESSION['member_loggedin'] = true;
            $_SESSION['member_id'] = $id;
            $_SESSION['member_username'] = $user;
            header("location: frontend/index.php");
            exit();
        } else {
            $_SESSION['error'] = "ຊື່ຜູ້ໃຊ້ ຫຼື ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ!";
            header("location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "ບໍ່ພົບຊື່ຜູ້ໃຊ້ນີ້ໃນລະບົບ!";
        header("location: login.php");
        exit();
    }
    $stmt->close();
    $conn->close();
}
?>
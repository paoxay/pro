<?php
// File: /frontend/header.php (Definitive Corrected Version)

// 1. ເລີ່ມ Session ກ່ອນທຸກຢ່າງສະເໝີ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. ເອີ້ນໃຊ້ໄຟລ໌ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
// This file creates the $conn variable.
require_once 'db.php';

// 3. ກວດສອບວ່າຜູ້ໃຊ້ Login ແລ້ວຫຼືບໍ່
if (!isset($_SESSION['member_loggedin']) || $_SESSION['member_loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// 4. ດຶງຂໍ້ມູນຜູ້ໃຊ້ (ຫຼັງຈາກແນ່ໃຈແລ້ວວ່າມີ Session ແລະ $conn)
$member_id = $_SESSION['member_id'];
$wallet_balance = 0; // Set default value

// Check if $conn is valid before using it.
if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT wallet_balance FROM members WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $member = $result->fetch_assoc();
            $wallet_balance = $member['wallet_balance'] ?? 0;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລະບົບເຕີມເກມ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        body { 
            font-family: 'Kanit', sans-serif;
            background-color: #f4f7f6; 
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-gamepad"></i> Topup Store</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="index.php">ໜ້າຫຼັກ</a></li>
                <li class="nav-item"><a class="nav-link" href="history.php">ປະຫວັດການສັ່ງຊື້</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['member_username']); ?> 
                        <span class="badge bg-success ms-1"><?php echo number_format($wallet_balance, 2); ?> ກີບ</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="wallet_history.php">ປະຫວັດທຸລະກຳ</a></li>
                        <li><a class="dropdown-item" href="settings.php">ຕັ້ງຄ່າ</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">ອອກຈາກລະບົບ</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container mt-4">
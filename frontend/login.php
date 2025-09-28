<?php
// File: /login.php
session_start();
if (isset($_SESSION['member_loggedin']) && $_SESSION['member_loggedin'] === true) {
    header("location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເຂົ້າສູ່ລະບົບ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f0f2f5; }
        .login-card { max-width: 400px; width: 100%; }
    </style>
</head>
<body>
    <div class="card login-card shadow-sm">
        <div class="card-body p-4">
            <h2 class="card-title text-center mb-4">ເຂົ້າສູ່ລະບົບ</h2>
            <?php 
                if(isset($_SESSION['error'])){
                    echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>';
                    unset($_SESSION['error']);
                }
            ?>
            <form action="auth.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">ຊື່ຜູ້ໃຊ້:</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">ລະຫັດຜ່ານ:</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">ເຂົ້າສູ່ລະບົບ</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
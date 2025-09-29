<?php
// File: /frontend/login.php (Redesigned Version)
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(to right top, #0c2b5b, #1a4a8d, #2b6cbf, #3c8ff2, #4db4ff);
        }
        .login-card {
            max-width: 420px;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        .form-control {
            height: 50px;
            border-radius: 10px;
        }
        .input-group-text {
            border-radius: 10px 0 0 10px;
        }
        .btn-primary {
            padding: 12px;
            border-radius: 10px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        .card-title {
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body p-4 p-md-5">

            <div class="text-center mb-4">
                <i class="fas fa-gamepad fa-3x text-primary"></i>
            </div>
            
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
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">ລະຫັດຜ່ານ:</label>
                     <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="ກະລຸນາປ້ອນລະຫັດຜ່ານ" required>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">ເຂົ້າສູ່ລະບົບ</button>
                </div>
            </form>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
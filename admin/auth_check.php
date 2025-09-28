<?php
// File: admin/auth_check.php
session_start();

// ຖ້າບໍ່ມີ session login ຫຼື session ເປັນ false, ໃຫ້ກັບໄປໜ້າ login
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}
?>
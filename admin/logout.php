<?php
// File: admin/logout.php
session_start();

// ລ້າງ session ທັງໝົດ
$_SESSION = array();
session_destroy();

// ກັບໄປໜ້າ login
header("location: index.php");
exit;
?>
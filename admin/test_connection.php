<?php
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "--- Database Connection Test ---\n\n";

// 1. Include ໄຟລ໌ເຊື່ອມຕໍ່ຕົວດຽວກັນກັບທີ່ໄຟລ໌ອື່ນໃຊ້
require_once 'db_connect.php';

// ກວດສອບວ່າການເຊື່ອມຕໍ່ສຳເລັດ ຫຼື ບໍ່
if ($conn->connect_error) {
    die("CONNECTION FAILED: " . $conn->connect_error);
}

echo "Successfully included db_connect.php\n";
echo "PHP is connecting to:\n";
echo "HOST: " . $DB_HOST . "\n";
echo "DATABASE NAME: " . $DB_NAME . "\n";
echo "USER: " . $DB_USER . "\n\n";

// 2. ລອງດຶງຂໍ້ມູນທັງໝົດຈາກຕາຕະລາງ โดยไม่สนเงื่อนไข is_active
$sql = "SELECT * FROM api_suppliers";
$result = $conn->query($sql);

if ($result === false) {
    die("QUERY FAILED! Error: " . $conn->error);
}

echo "Query was successful.\n";
echo "Number of suppliers found in 'api_suppliers' table: " . $result->num_rows . "\n\n";

if ($result->num_rows > 0) {
    echo "SUCCESS! Data Found:\n";
    print_r($result->fetch_assoc());
} else {
    echo "!!! CRITICAL PROBLEM !!!\n";
    echo "The script connected to the database but found ZERO rows in the 'api_suppliers' table.\n";
    echo "This PROVES that the connection details in your 'admin/db_connect.php' file are NOT pointing to the database you are seeing in phpMyAdmin.\n\n";
    echo "ACTION: Please open 'admin/db_connect.php' and verify that DB_HOST, DB_USER, DB_PASS, and especially DB_NAME are 100% correct.";
}

$conn->close();
?>
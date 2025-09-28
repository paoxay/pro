<?php
// File: /db.php
$DB_HOST = 'localhost';
$DB_USER = 'root'; // <-- ປ່ຽນໃຫ້ຖືກຕ້ອງ
$DB_PASS = ''; // <-- ປ່ຽນໃຫ້ຖືກຕ້ອງ
$DB_NAME = 'pro';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
  die("ການເຊື່ອມຕໍ່ຖານຂໍ້ມູນລົ້ມເຫຼວ: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
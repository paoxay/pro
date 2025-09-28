<?php
// File: admin/db_connect.php

// ຕັ້ງຄ່າການເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
$DB_HOST = 'localhost';
$DB_USER = 'root'; // <-- ປ່ຽນເປັນຊື່ຜູ້ໃຊ້ຖານຂໍ້ມູນຂອງເຈົ້າ
$DB_PASS = ''; // <-- ປ່ຽນເປັນລະຫັດຜ່ານຂອງເຈົ້າ
$DB_NAME = 'pro'; // <-- ຊື່ຖານຂໍ້ມູນທີ່ເຮົາສ້າງ

// ສ້າງການເຊື່ອມຕໍ່ດ້ວຍ an mysqli object
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// ກວດສອບການເຊື່ອມຕໍ່
if ($conn->connect_error) {
  die("ການເຊື່ອມຕໍ່ຖານຂໍ້ມູນລົ້ມເຫຼວ: " . $conn->connect_error);
}

// ຕັ້ງຄ່າ character set ເປັນ utf8mb4 ເພື່ອຮອງຮັບພາສາລາວ
$conn->set_charset("utf8mb4");
?>
<?php
$member = "M241013PPRB9467IF";
$secret = "d583eba663aebc4ca7dbca51ea17a43da25044e5c1c3561221c5b1bf027e6938";
$ref    = "PAOXAYYASAN123"; // <-- เปลี่ยนทุกครั้ง

$sign = md5($member . ":" . $secret . ":" . $ref);
echo $sign, PHP_EOL;

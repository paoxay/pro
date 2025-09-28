<?php
// File: admin/ajax_add_member.php (Hashed Password Version)
require_once 'db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$balance = $input['balance'] ?? 0.00;

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້ ແລະ ລະຫັດຜ່ານ.']);
    exit;
}

$stmt_check = $conn->prepare("SELECT id FROM members WHERE username = ?");
$stmt_check->bind_param("s", $username);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'ຊື່ຜູ້ໃຊ້ນີ້ຖືກໃຊ້ງານແລ້ວ. ກະລຸນາໃຊ້ຊື່ອື່ນ.']);
    $stmt_check->close();
    $conn->close();
    exit;
}
$stmt_check->close();

// THE CHANGE IS HERE: Hash the password before inserting
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt_insert = $conn->prepare("INSERT INTO members (username, password, wallet_balance) VALUES (?, ?, ?)");
$stmt_insert->bind_param("ssd", $username, $hashed_password, $balance);

if ($stmt_insert->execute()) {
    $new_id = $conn->insert_id;
    $created_at = date('d/m/Y');
    echo json_encode([
        'success' => true,
        'new_member' => [
            'id' => $new_id,
            'username' => $username,
            'wallet_balance' => number_format($balance, 2),
            'created_at' => $created_at
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກຂໍ້ມູນ.']);
}

$stmt_insert->close();
$conn->close();
?>
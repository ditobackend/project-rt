<?php
require_once __DIR__ . '/config/database.php';

$res = mysqli_query($conn, "SELECT id, kunci, nilai FROM pengaturan WHERE kunci IN ('iuran_bulanan', 'iuran_keamanan')");
while($row = mysqli_fetch_assoc($res)) {
    $clean_val = preg_replace('/[^0-9]/', '', $row['nilai']);
    $stmt = $conn->prepare("UPDATE pengaturan SET nilai = ? WHERE id = ?");
    $stmt->bind_param("si", $clean_val, $row['id']);
    $stmt->execute();
}
echo "DB values cleaned successfully.";
?>

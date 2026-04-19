<?php
require_once 'config/database.php';

echo "--- DATA PEMBAYARAN (BERHASIL) ---\n";
$res1 = $conn->query("SELECT * FROM pembayaran WHERE status = 'berhasil' ORDER BY id DESC LIMIT 5");
while($row = $res1->fetch_assoc()) {
    print_r($row);
}

echo "\n--- DATA KEUANGAN (TERBARU) ---\n";
$res2 = $conn->query("SELECT * FROM keuangan ORDER BY id DESC LIMIT 5");
while($row = $res2->fetch_assoc()) {
    print_r($row);
}
?>

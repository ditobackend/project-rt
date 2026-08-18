<?php
require_once __DIR__ . '/../config/database.php';

$res = $conn->query("SELECT * FROM pembayaran ORDER BY id DESC LIMIT 10");
echo "=== DATA PEMBAYARAN TERAKHIR ===\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

$res_k = $conn->query("SELECT * FROM keuangan ORDER BY id DESC LIMIT 10");
echo "\n=== DATA KEUANGAN TERAKHIR ===\n";
while ($row = $res_k->fetch_assoc()) {
    print_r($row);
}

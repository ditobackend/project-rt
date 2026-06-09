<?php
require_once __DIR__ . '/../config/database.php';

$res = $conn->query("DELETE FROM pengaturan WHERE kunci NOT IN ('iuran_bulanan', 'iuran_keamanan')");
if ($res) {
    echo "Deleted unused rows successfully. Remaining rows:\n";
    $res2 = $conn->query("SELECT * FROM pengaturan");
    while ($row = $res2->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error;
}
?>

<?php
require_once 'config/database.php';
$result = $conn->query("DESCRIBE keuangan");
while($row = $result->fetch_assoc()) {
    print_r($row);
}
$result = $conn->query("DESCRIBE pembayaran");
while($row = $result->fetch_assoc()) {
    print_r($row);
}
?>

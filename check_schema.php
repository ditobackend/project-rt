<?php
require_once 'config/database.php';
$res = $conn->query("DESCRIBE laporan");
while($row = $res->fetch_assoc()) {
    print_r($row);
    echo "<br>";
}
?>

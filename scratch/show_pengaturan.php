<?php
require_once __DIR__ . '/config/database.php';

$res = $conn->query("SELECT * FROM pengaturan");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

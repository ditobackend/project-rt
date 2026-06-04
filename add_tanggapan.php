<?php
require_once __DIR__ . '/config/database.php';

$query = "ALTER TABLE pengaduan ADD COLUMN tanggapan_admin TEXT DEFAULT NULL AFTER status";
if ($conn->query($query) === TRUE) {
    echo "Column added successfully";
} else {
    // Maybe column already exists
    echo "Error adding column: " . $conn->error;
}
?>

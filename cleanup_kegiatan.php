<?php
require_once 'config/database.php';

echo "<h3>Cleaning up 'kegiatan' table...</h3>";

$sql = "ALTER TABLE kegiatan DROP COLUMN IF EXISTS penyelenggara, DROP COLUMN IF EXISTS admin_id";

if ($conn->query($sql)) {
    echo "Selesai! Kolom 'penyelenggara' dan 'admin_id' telah dihapus.<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

echo "<br><a href='index.php'>Kembali ke Home</a>";
?>

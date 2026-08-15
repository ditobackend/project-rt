<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Pembersihan Database: Hapus Tabel Legacy data_keluarga</h2>";

// 1. Drop FK dari surat_pengantar jika merujuk ke data_keluarga
$fkCheck = $conn->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'surat_pengantar' AND REFERENCED_TABLE_NAME = 'data_keluarga'");
if ($fkCheck && $fkCheck->num_rows > 0) {
    while ($row = $fkCheck->fetch_assoc()) {
        $fkName = $row['CONSTRAINT_NAME'];
        if ($conn->query("ALTER TABLE `surat_pengantar` DROP FOREIGN KEY `$fkName`")) {
            echo "<p style='color:green;'>✅ Foreign Key '$fkName' di surat_pengantar berhasil dihapus.</p>";
        }
    }
}

// 2. Drop kolom data_keluarga_id dari surat_pengantar jika ada
$colCheck = $conn->query("SHOW COLUMNS FROM `surat_pengantar` LIKE 'data_keluarga_id'");
if ($colCheck && $colCheck->num_rows > 0) {
    if ($conn->query("ALTER TABLE `surat_pengantar` DROP COLUMN `data_keluarga_id`")) {
        echo "<p style='color:green;'>✅ Kolom 'data_keluarga_id' di surat_pengantar berhasil dihapus.</p>";
    }
}

// 3. Drop tabel data_keluarga
if ($conn->query("DROP TABLE IF EXISTS `data_keluarga`")) {
    echo "<p style='color:green; font-weight:bold;'>✅ Tabel 'data_keluarga' BERAHSIL DIHAPUS (DROP TABLE IF EXISTS data_keluarga).</p>";
} else {
    echo "<p style='color:red;'>❌ Gagal menghapus tabel data_keluarga: " . $conn->error . "</p>";
}

echo "<br><p>Pembersihan selesai! Aplikasi kini berjalan murni menggunakan tabel <b>kartu_keluarga</b> dan <b>anggota_keluarga</b>.</p>";
?>

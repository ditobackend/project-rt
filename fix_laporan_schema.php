<?php
require_once 'config/database.php';

echo "<h3>Memperbaiki Struktur Tabel Laporan...</h3>";

// 1. Cek kolom sumber_id
$check = $conn->query("SHOW COLUMNS FROM laporan LIKE 'sumber_id'");
if ($check && $check->num_rows == 0) {
    echo "Menambahkan kolom 'sumber_id' ke tabel laporan...<br>";
    $conn->query("ALTER TABLE laporan ADD COLUMN sumber_id INT AFTER jumlah");
}

// 2. Cek kolom admin_id
$check_admin = $conn->query("SHOW COLUMNS FROM laporan LIKE 'admin_id'");
if ($check_admin && $check_admin->num_rows == 0) {
    echo "Menambahkan kolom 'admin_id' ke tabel laporan...<br>";
    $conn->query("ALTER TABLE laporan ADD COLUMN admin_id INT AFTER sumber_id");
}

// 3. Pastikan kolom jenis adalah ENUM yang benar
$conn->query("ALTER TABLE laporan MODIFY COLUMN jenis ENUM('pemasukan', 'pengeluaran') NOT NULL");

echo "<strong>Selesai! Struktur tabel laporan sekarang sudah benar.</strong><br>";
echo "<br><a href='dashboard_admin.php?page=keuangan'>Kembali ke Keuangan</a>";
?>

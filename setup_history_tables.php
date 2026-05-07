<?php
// setup_history_tables.php
require_once 'config/database.php';

echo "Memulai setup tabel riwayat...<br>";

// 1. Cek apakah tabel laporan lama ada
$check_laporan = $conn->query("SHOW TABLES LIKE 'laporan'");
if ($check_laporan && $check_laporan->num_rows > 0) {
    // Jika ada, cek apakah ini skeleton lama (punya kolom 'jenis' enum pdf/excel)
    $describe = $conn->query("DESCRIBE laporan");
    $is_skeleton = false;
    while ($row = $describe->fetch_assoc()) {
        if ($row['Field'] == 'jenis' && strpos($row['Type'], "'pdf','excel'") !== false) {
            $is_skeleton = true;
            break;
        }
    }
    
    if ($is_skeleton) {
        echo "Menghapus tabel laporan lama (skeleton)...<br>";
        $conn->query("DROP TABLE laporan");
    }
}

// Catatan: Tabel pengeluaran telah dihapus.
// Data pengeluaran kini dikelola sepenuhnya melalui tabel keuangan.

// 2. Create laporan table (riwayat transaksi)
$sql_laporan = "CREATE TABLE IF NOT EXISTS laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    keterangan VARCHAR(255) NOT NULL,
    jenis ENUM('pemasukan', 'pengeluaran') NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    sumber_id INT,
    admin_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql_laporan)) {
    echo "Tabel 'laporan' (riwayat transaksi) siap.<br>";
} else {
    echo "Gagal membuat tabel 'laporan': " . $conn->error . "<br>";
}

// 4. Pastikan tabel keuangan punya admin_id
$check_keuangan = $conn->query("SHOW COLUMNS FROM keuangan LIKE 'admin_id'");
if ($check_keuangan && $check_keuangan->num_rows == 0) {
    echo "Menambahkan kolom 'admin_id' ke tabel keuangan...<br>";
    $conn->query("ALTER TABLE keuangan ADD COLUMN admin_id INT AFTER jumlah");
    $conn->query("ALTER TABLE keuangan ADD FOREIGN KEY (admin_id) REFERENCES users(id)");
}

echo "<strong>Setup selesai!</strong>";

?>

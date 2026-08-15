<?php
require_once __DIR__ . '/config/database.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS surat_pengantar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        anggota_keluarga_id INT NULL,
        jenis_surat VARCHAR(50) NOT NULL,
        keperluan TEXT NOT NULL,
        tanggal_pengajuan DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'disetujui', 'ditolak') DEFAULT 'pending',
        tanggal_disetujui DATETIME NULL,
        keterangan_tolak TEXT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

foreach ($queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Tabel berhasil dibuat atau sudah ada.\n";
    } else {
        echo "Error membuat tabel: " . $conn->error . "\n";
    }
}
$conn->close();
?>

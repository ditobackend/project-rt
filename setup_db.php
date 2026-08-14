<?php
require_once __DIR__ . '/config/database.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS data_keluarga (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        nik VARCHAR(16) NOT NULL,
        nama_lengkap VARCHAR(100) NOT NULL,
        tempat_lahir VARCHAR(50) NOT NULL,
        tanggal_lahir DATE NOT NULL,
        jenis_kelamin ENUM('L', 'P') NOT NULL,
        agama VARCHAR(20) NOT NULL,
        pendidikan VARCHAR(50),
        pekerjaan VARCHAR(50),
        status_perkawinan VARCHAR(20),
        status_hubungan VARCHAR(50) NOT NULL,
        foto_ktp VARCHAR(255),
        foto_kk VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS surat_pengantar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        data_keluarga_id INT NOT NULL,
        jenis_surat VARCHAR(50) NOT NULL,
        keperluan TEXT NOT NULL,
        tanggal_pengajuan DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'disetujui', 'ditolak') DEFAULT 'pending',
        tanggal_disetujui DATETIME NULL,
        keterangan_tolak TEXT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (data_keluarga_id) REFERENCES data_keluarga(id) ON DELETE CASCADE
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

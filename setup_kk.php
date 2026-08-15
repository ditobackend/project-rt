<?php
require_once __DIR__ . '/config/database.php';

$results = [];

// ============================================================
// 1. Buat tabel kartu_keluarga
// ============================================================
$sql1 = "CREATE TABLE IF NOT EXISTS `kartu_keluarga` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_kk` VARCHAR(16) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `nama_kepala_keluarga` VARCHAR(100) NOT NULL,
    `alamat` TEXT NOT NULL,
    `rt` VARCHAR(5) NOT NULL,
    `rw` VARCHAR(5) NOT NULL,
    `kode_pos` VARCHAR(10),
    `desa_kelurahan` VARCHAR(100) NOT NULL,
    `kecamatan` VARCHAR(100) NOT NULL,
    `kabupaten_kota` VARCHAR(100) NOT NULL,
    `provinsi` VARCHAR(100) NOT NULL,
    `foto_kk` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql1)) {
    $results[] = ['status' => 'OK', 'msg' => 'Tabel kartu_keluarga berhasil dibuat.'];
} else {
    $results[] = ['status' => 'ERROR', 'msg' => 'Gagal buat kartu_keluarga: ' . $conn->error];
}

// ============================================================
// 2. Buat tabel anggota_keluarga
// ============================================================
$sql2 = "CREATE TABLE IF NOT EXISTS `anggota_keluarga` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kk_id` INT NOT NULL,
    `nik` VARCHAR(16) NOT NULL UNIQUE,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `tempat_lahir` VARCHAR(100) NOT NULL,
    `tanggal_lahir` DATE NOT NULL,
    `jenis_kelamin` ENUM('L','P') NOT NULL,
    `golongan_darah` ENUM('A','B','AB','O','-') DEFAULT '-',
    `agama` VARCHAR(30),
    `pendidikan` VARCHAR(50),
    `jenis_pekerjaan` VARCHAR(100),
    `status_perkawinan` VARCHAR(50),
    `status_hubungan` VARCHAR(50) NOT NULL,
    `kewarganegaraan` VARCHAR(10) DEFAULT 'WNI',
    `nama_ayah` VARCHAR(100),
    `nama_ibu` VARCHAR(100),
    `foto_ktp` VARCHAR(255),
    `diinput_oleh` ENUM('admin','warga') DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`kk_id`) REFERENCES `kartu_keluarga`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql2)) {
    $results[] = ['status' => 'OK', 'msg' => 'Tabel anggota_keluarga berhasil dibuat.'];
} else {
    $results[] = ['status' => 'ERROR', 'msg' => 'Gagal buat anggota_keluarga: ' . $conn->error];
}

// ============================================================
// 3. Modifikasi tabel surat_pengantar
//    Tambah kolom anggota_keluarga_id (jika belum ada)
// ============================================================
$checkCol = $conn->query("SHOW COLUMNS FROM `surat_pengantar` LIKE 'anggota_keluarga_id'");
if ($checkCol->num_rows === 0) {
    $sql3a = "ALTER TABLE `surat_pengantar` ADD COLUMN `anggota_keluarga_id` INT NULL AFTER `user_id`";
    if ($conn->query($sql3a)) {
        $results[] = ['status' => 'OK', 'msg' => 'Kolom anggota_keluarga_id berhasil ditambahkan ke surat_pengantar.'];
    } else {
        $results[] = ['status' => 'ERROR', 'msg' => 'Gagal tambah kolom anggota_keluarga_id: ' . $conn->error];
    }

    // Tambah foreign key untuk anggota_keluarga_id
    $sql3b = "ALTER TABLE `surat_pengantar` 
              ADD CONSTRAINT `fk_surat_anggota` 
              FOREIGN KEY (`anggota_keluarga_id`) REFERENCES `anggota_keluarga`(`id`) ON DELETE SET NULL";
    if ($conn->query($sql3b)) {
        $results[] = ['status' => 'OK', 'msg' => 'Foreign key anggota_keluarga_id berhasil ditambahkan.'];
    } else {
        $results[] = ['status' => 'WARN', 'msg' => 'FK anggota_keluarga_id gagal (mungkin sudah ada): ' . $conn->error];
    }
} else {
    $results[] = ['status' => 'SKIP', 'msg' => 'Kolom anggota_keluarga_id sudah ada di surat_pengantar.'];
}

// Hapus foreign key & kolom legacy data_keluarga_id jika masih ada
$fkCheck = $conn->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'surat_pengantar' AND REFERENCED_TABLE_NAME = 'data_keluarga'");
if ($fkCheck && $fkCheck->num_rows > 0) {
    while ($row = $fkCheck->fetch_assoc()) {
        $fkName = $row['CONSTRAINT_NAME'];
        @$conn->query("ALTER TABLE `surat_pengantar` DROP FOREIGN KEY `$fkName`");
    }
}

$checkOldCol = $conn->query("SHOW COLUMNS FROM `surat_pengantar` LIKE 'data_keluarga_id'");
if ($checkOldCol && $checkOldCol->num_rows > 0) {
    $conn->query("ALTER TABLE `surat_pengantar` DROP COLUMN `data_keluarga_id`");
    $results[] = ['status' => 'OK', 'msg' => 'Kolom lama data_keluarga_id berhasil dihapus dari surat_pengantar.'];
}

// Hapus tabel lama data_keluarga jika masih ada
if ($conn->query("DROP TABLE IF EXISTS `data_keluarga`")) {
    $results[] = ['status' => 'OK', 'msg' => 'Tabel lama data_keluarga berhasil dihapus (DROP TABLE).'];
}

// ============================================================
// 4. Buat folder upload yang dibutuhkan
// ============================================================
$folders = [
    __DIR__ . '/uploads/kk/',
    __DIR__ . '/uploads/ktp/',
    __DIR__ . '/uploads/foto_kk_kartu/',
];
foreach ($folders as $folder) {
    if (!is_dir($folder)) {
        if (mkdir($folder, 0777, true)) {
            $results[] = ['status' => 'OK', 'msg' => "Folder $folder berhasil dibuat."];
        } else {
            $results[] = ['status' => 'WARN', 'msg' => "Gagal buat folder: $folder"];
        }
    } else {
        $results[] = ['status' => 'SKIP', 'msg' => "Folder sudah ada: $folder"];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Setup KK - Migrasi Database</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f4f8; margin: 0; padding: 30px; }
        .container { max-width: 700px; margin: 0 auto; }
        h1 { color: #1e293b; border-bottom: 3px solid #6366f1; padding-bottom: 12px; }
        .item { display: flex; align-items: flex-start; gap: 12px; padding: 10px 16px; margin-bottom: 8px; border-radius: 10px; font-size: 14px; }
        .OK   { background: #dcfce7; color: #166534; }
        .ERROR { background: #fee2e2; color: #991b1b; }
        .WARN  { background: #fef9c3; color: #854d0e; }
        .SKIP  { background: #f1f5f9; color: #475569; }
        .badge { font-weight: bold; min-width: 56px; text-align: center; padding: 2px 8px; border-radius: 6px; font-size: 11px; }
        .OK .badge { background: #166534; color: #fff; }
        .ERROR .badge { background: #991b1b; color: #fff; }
        .WARN .badge { background: #854d0e; color: #fff; }
        .SKIP .badge { background: #94a3b8; color: #fff; }
        .footer { margin-top: 24px; padding: 16px; background: #ede9fe; border-radius: 12px; color: #4c1d95; font-size: 13px; }
        a { color: #6366f1; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>🗄️ Setup KK — Migrasi Database</h1>
    <?php foreach ($results as $r): ?>
        <div class="item <?= $r['status'] ?>">
            <span class="badge"><?= $r['status'] ?></span>
            <span><?= htmlspecialchars($r['msg']) ?></span>
        </div>
    <?php endforeach; ?>
    <div class="footer">
        ✅ Migrasi selesai. Hapus file <strong>setup_kk.php</strong> setelah berhasil, atau 
        <a href="dashboard_admin.php">kembali ke Dashboard Admin</a>.
    </div>
</div>
</body>
</html>

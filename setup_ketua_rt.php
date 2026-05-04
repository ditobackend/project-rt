<?php
require_once 'config/database.php';

echo "Memulai pembaruan sistem untuk Aktor Ketua RT...<br>";

// 1. Tambahkan role ketua_rt ke tabel users
$sql1 = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'warga', 'ketua_rt') DEFAULT 'warga'";
if ($conn->query($sql1)) {
    echo "Role 'ketua_rt' berhasil ditambahkan.<br>";
}

// 2. Update tabel kegiatan untuk sistem persetujuan
// Menggunakan ALTER TABLE tanpa IF NOT EXISTS karena MySQL standar tidak mendukung IF NOT EXISTS pada ADD COLUMN
// Kita coba bungkus dalam try-catch atau cek kolom secara manual
$columns = $conn->query("SHOW COLUMNS FROM kegiatan");
$existing_cols = [];
while($col = $columns->fetch_assoc()) {
    $existing_cols[] = $col['Field'];
}

if (!in_array('diajukan_oleh', $existing_cols)) {
    $conn->query("ALTER TABLE kegiatan ADD COLUMN diajukan_oleh INT NULL");
}
if (!in_array('tempat', $existing_cols)) {
    $conn->query("ALTER TABLE kegiatan ADD COLUMN tempat VARCHAR(255) NULL");
}
if (!in_array('status_persetujuan', $existing_cols)) {
    $conn->query("ALTER TABLE kegiatan ADD COLUMN status_persetujuan ENUM('pending', 'disetujui', 'ditolak') DEFAULT 'pending'");
}
echo "Struktur tabel 'kegiatan' berhasil diperbarui.<br>";

// 3. Tambahkan Foreign Key
try {
    $conn->query("ALTER TABLE kegiatan ADD CONSTRAINT fk_diajukan_oleh FOREIGN KEY (diajukan_oleh) REFERENCES users(id)");
    echo "Relasi database berhasil dibuat.<br>";
} catch (Exception $e) {
    echo "Relasi mungkin sudah ada atau gagal dibuat, lanjut...<br>";
}

echo "<strong>Pembaruan database selesai!</strong><br><br>";

// 4. Buat akun Ketua RT default (opsional)
$check_ketua = $conn->query("SELECT id FROM users WHERE role = 'ketua_rt' LIMIT 1");
if ($check_ketua && $check_ketua->num_rows == 0) {
    $nama = "Ketua RT 06";
    $email = "ketua@email.com";
    $pass = "ketua123"; // Plain text as per current system
    $conn->query("INSERT INTO users (nama, email, password, role) VALUES ('$nama', '$email', '$pass', 'ketua_rt')");
    echo "<div style='background: #eef2ff; p: 20px; border-radius: 10px; border: 1px solid #6366f1; margin-top: 20px;'>
            <strong>Akun Ketua RT Default Dibuat:</strong><br>
            Email: ketua@email.com<br>
            Password: ketua123<br>
            <em>Silakan login melalui portal admin.</em>
          </div>";
}
?>

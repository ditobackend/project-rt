<?php
require_once 'config/database.php';

echo "Memulai migrasi data...\n";

// 1. Migrasi Pengeluaran dari Keuangan
$res_pengeluaran = $conn->query("SELECT * FROM keuangan WHERE jenis = 'pengeluaran'");
$count_pengeluaran = 0;
while ($row = $res_pengeluaran->fetch_assoc()) {
    // Cek apakah sudah ada di tabel pengeluaran
    $cek = $conn->prepare("SELECT id FROM pengeluaran WHERE keterangan = ? AND tanggal = ? AND jumlah = ?");
    $cek->bind_param("ssd", $row['keterangan'], $row['tanggal'], $row['jumlah']);
    $cek->execute();
    if ($cek->get_result()->num_rows == 0) {
        // Ambil kategori dari keterangan jika ada (format: Admin - Kategori - Nama)
        $parts = explode(' - ', $row['keterangan']);
        $kategori = $parts[1] ?? 'Lainnya';
        
        $stmt = $conn->prepare("INSERT INTO pengeluaran (tanggal, keterangan, jumlah, kategori, admin_id, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsis", $row['tanggal'], $row['keterangan'], $row['jumlah'], $kategori, $row['admin_id'], $row['created_at']);
        if($stmt->execute()) $count_pengeluaran++;
    }
}
echo "Migrasi pengeluaran: $count_pengeluaran data baru.\n";

// 2. Migrasi Riwayat Laporan (Pemasukan & Pengeluaran)
$res_all = $conn->query("SELECT * FROM keuangan");
$count_laporan = 0;
while ($row = $res_all->fetch_assoc()) {
    $cek = $conn->prepare("SELECT id FROM laporan WHERE keterangan = ? AND tanggal = ? AND jumlah = ?");
    $cek->bind_param("ssd", $row['keterangan'], $row['tanggal'], $row['jumlah']);
    $cek->execute();
    if ($cek->get_result()->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO laporan (tanggal, keterangan, jenis, jumlah, admin_id, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdis", $row['tanggal'], $row['keterangan'], $row['jenis'], $row['jumlah'], $row['admin_id'], $row['created_at']);
        if($stmt->execute()) $count_laporan++;
    }
}
echo "Migrasi laporan: $count_laporan data baru.\n";

echo "Migrasi selesai.\n";
?>

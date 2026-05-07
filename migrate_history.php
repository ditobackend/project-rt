<?php
require_once 'config/database.php';

echo "Memulai migrasi data...\n";

// Catatan: Tabel pengeluaran telah dihapus.
// Data pengeluaran kini dikelola sepenuhnya melalui tabel keuangan (jenis='pengeluaran').
// Blok migrasi ke tabel pengeluaran tidak lagi diperlukan.


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

<?php
require_once 'config/database.php';

echo "Memulai proses sinkronisasi data pembayaran ke keuangan...\n";

// Ambil semua pembayaran berhasil yang belum ada di keuangan
$sql = "SELECT p.*, u.nama 
        FROM pembayaran p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'berhasil'";

$res = $conn->query($sql);
$count = 0;

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $nama = $row['nama'];
        $kategori = $row['kategori'];
        $catatan = $row['catatan'] ?: '-';
        $jumlah = $row['jumlah'];
        $keterangan = "$nama - $kategori - $catatan";
        
        // Cek apakah sudah ada di keuangan
        $cek = $conn->prepare("SELECT id FROM keuangan WHERE keterangan = ? AND jumlah = ?");
        $cek->bind_param("sd", $keterangan, $jumlah);
        $cek->execute();
        $existing = $cek->get_result();
        
        if ($existing->num_rows === 0) {
            // Belum ada, maka masukkan
            $ins = $conn->prepare("INSERT INTO keuangan (tanggal, keterangan, jenis, jumlah) VALUES (?, ?, 'pemasukan', ?)");
            $tanggal = date('Y-m-d', strtotime($row['tanggal']));
            $ins->bind_param("ssd", $tanggal, $keterangan, $jumlah);
            
            if ($ins->execute()) {
                echo "DISINKRON: Transaksi {$row['order_id']} ($keterangan) senilai $jumlah berhasil dimasukkan.\n";
                $count++;
            } else {
                echo "GAGAL: {$row['order_id']} - Error: " . $ins->error . "\n";
            }
            $ins->close();
        }
        $cek->close();
    }
}

echo "\nSelesai! Berhasil mensinkronkan $count data baru ke tabel Keuangan.";
?>

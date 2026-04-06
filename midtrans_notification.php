<?php
// midtrans_notification.php - Handler untuk notifikasi Midtrans

require_once 'config/database.php';
require_once 'config/midtrans.php';

// Ambil data notifikasi dari Midtrans
$notif = new \Midtrans\Notification();

$transaction = $notif->transaction_status;
$fraud = $notif->fraud_status;
$order_id = $notif->order_id;
$status_code = $notif->status_code;

// Cari pembayaran berdasarkan order_id
$stmt = $conn->prepare("SELECT id, user_id, jumlah, kategori, metode FROM pembayaran WHERE order_id = ?");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $pembayaran = $result->fetch_assoc();
    $pembayaran_id = $pembayaran['id'];
    $user_id = $pembayaran['user_id'];
    $jumlah = $pembayaran['jumlah'];
    $kategori = $pembayaran['kategori'];
    $metode = $pembayaran['metode'];

    // Ambil nama pembayar dari tabel users
    $user_stmt = $conn->prepare("SELECT nama FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $nama = $user_result->num_rows > 0 ? $user_result->fetch_assoc()['nama'] : 'Unknown';
    $user_stmt->close();

    // Update status pembayaran
    if ($transaction == 'capture') {
        if ($fraud == 'challenge') {
            $status = 'challenge';
        } else if ($fraud == 'accept') {
            $status = 'berhasil';
        }
    } else if ($transaction == 'settlement') {
        $status = 'berhasil';
    } else if ($transaction == 'deny') {
        $status = 'gagal';
    } else if ($transaction == 'cancel') {
        $status = 'gagal';
    } else if ($transaction == 'expire') {
        $status = 'gagal';
    } else if ($transaction == 'failure') {
        $status = 'gagal';
    } else if ($transaction == 'pending') {
        $status = 'pending';
    }

    // Update pembayaran
    $update_stmt = $conn->prepare("UPDATE pembayaran SET status = ?, order_id = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $status, $order_id, $pembayaran_id);
    $update_stmt->execute();

    // Jika berhasil, tambahkan ke keuangan sebagai pemasukan (hindari duplikasi)
    if ($status == 'berhasil') {
        $keterangan = "$nama - $kategori - $metode";
        $cek_keuangan = $conn->prepare("SELECT id FROM keuangan WHERE keterangan = ?");
        $cek_keuangan->bind_param("s", $keterangan);
        $cek_keuangan->execute();
        $existing = $cek_keuangan->get_result();
        $sudahTerekam = $existing->num_rows > 0;

        if (!$sudahTerekam) {
            $keuangan_stmt = $conn->prepare("INSERT INTO keuangan (tanggal, keterangan, jenis, jumlah) VALUES (CURDATE(), ?, 'pemasukan', ?)");
            $keuangan_stmt->bind_param("sd", $keterangan, $jumlah);
            $keuangan_stmt->execute();
            $keuangan_stmt->close();
        }

        $cek_keuangan->close();
    }

    $stmt->close();
    $update_stmt->close();
}

$conn->close();
?>
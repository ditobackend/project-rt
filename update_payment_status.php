<?php
// update_payment_status.php - Update status pembayaran dan keuangan

require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = $_POST['order_id'] ?? '';
    $status = $_POST['status'] ?? 'berhasil'; // default berhasil

    if (!empty($order_id)) {
        // Update pembayaran
        $stmt = $conn->prepare("UPDATE pembayaran SET status = ? WHERE order_id = ?");
        $stmt->bind_param("ss", $status, $order_id);
        $stmt->execute();

        // Jika berhasil, ambil data dan tambahkan ke keuangan
        if ($status == 'berhasil') {
            $select_stmt = $conn->prepare("SELECT user_id, jumlah, kategori, metode, catatan FROM pembayaran WHERE order_id = ?");
            $select_stmt->bind_param("s", $order_id);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($result->num_rows > 0) {
                $data = $result->fetch_assoc();
                $user_id = $data['user_id'];
                $jumlah = $data['jumlah'];
                $kategori = $data['kategori'];
                $metode = $data['metode'];
                $catatan = $data['catatan'] ?: '-';
                
                $user_stmt = $conn->prepare("SELECT nama FROM users WHERE id = ?");
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $user_res = $user_stmt->get_result();
                $nama = $user_res->num_rows > 0 ? $user_res->fetch_assoc()['nama'] : 'Unknown';
                $user_stmt->close();
                
                $keterangan = "$nama - $kategori - $catatan";
                $cek_keuangan = $conn->prepare("SELECT id FROM keuangan WHERE keterangan = ?");
                $cek_keuangan->bind_param("s", $keterangan);
                $cek_keuangan->execute();
                $existing = $cek_keuangan->get_result();

                if ($existing->num_rows === 0) {
                    $keuangan_stmt = $conn->prepare("INSERT INTO keuangan (tanggal, keterangan, jenis, jumlah) VALUES (CURDATE(), ?, 'pemasukan', ?)");
                    $keuangan_stmt->bind_param("sd", $keterangan, $jumlah);
                    $keuangan_stmt->execute();
                    $keuangan_stmt->close();
                }

                $cek_keuangan->close();
            }
            $select_stmt->close();
        }

        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Order ID missing']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}

$conn->close();
?>
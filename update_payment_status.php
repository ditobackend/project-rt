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
        if (!$stmt->execute()) {
            file_put_contents('payment_error.log', "[" . date('Y-m-d H:i:s') . "] Update pembayaran failed: " . $stmt->error . "\n", FILE_APPEND);
        }

        // Jika berhasil, tambahkan ke keuangan
        if ($status == 'berhasil') {
            $select_stmt = $conn->prepare("SELECT user_id, jumlah, kategori, metode, catatan FROM pembayaran WHERE order_id = ?");
            $select_stmt->bind_param("s", $order_id);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($result->num_rows > 0) {
                $data = $result->fetch_assoc();
                $user_id = $data['user_id'];
                $jumlah  = $data['jumlah'];
                $kategori = $data['kategori'];
                $catatan = $data['catatan'] ?: '-';

                $user_stmt = $conn->prepare("SELECT nama FROM users WHERE id = ?");
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $user_res = $user_stmt->get_result();
                $nama = $user_res->num_rows > 0 ? $user_res->fetch_assoc()['nama'] : 'Unknown';
                $user_stmt->close();

                // Pastikan kolom order_id ada di keuangan & laporan
                $chk = $conn->query("SHOW COLUMNS FROM keuangan LIKE 'order_id'");
                if ($chk && $chk->num_rows == 0) {
                    $conn->query("ALTER TABLE keuangan ADD COLUMN order_id VARCHAR(100) NULL DEFAULT NULL");
                    $conn->query("ALTER TABLE laporan ADD COLUMN order_id VARCHAR(100) NULL DEFAULT NULL");
                }

                $keterangan = mb_substr("$nama - $kategori", 0, 255);

                // Cek duplikasi berdasarkan order_id
                $cek_keuangan = $conn->prepare("SELECT id FROM keuangan WHERE order_id = ?");
                $cek_keuangan->bind_param("s", $order_id);
                $cek_keuangan->execute();
                $existing = $cek_keuangan->get_result();
                $cek_keuangan->close();

                if ($existing->num_rows === 0) {
                    $keuangan_stmt = $conn->prepare("INSERT INTO keuangan (tanggal, keterangan, jenis, jumlah, order_id) VALUES (CURDATE(), ?, 'pemasukan', ?, ?)");
                    $keuangan_stmt->bind_param("sds", $keterangan, $jumlah, $order_id);
                    if (!$keuangan_stmt->execute()) {
                        file_put_contents('payment_error.log', "[" . date('Y-m-d H:i:s') . "] Insert keuangan failed: " . $keuangan_stmt->error . "\n", FILE_APPEND);
                    }
                    $keuangan_stmt->close();

                    // Simpan ke Laporan
                    $stmt_l = $conn->prepare("INSERT INTO laporan (tanggal, keterangan, jenis, jumlah, order_id) VALUES (CURDATE(), ?, 'pemasukan', ?, ?)");
                    $stmt_l->bind_param("sds", $keterangan, $jumlah, $order_id);
                    $stmt_l->execute();
                    $stmt_l->close();
                } else {
                    file_put_contents('payment_error.log', "[" . date('Y-m-d H:i:s') . "] Duplicate order_id $order_id skipped\n", FILE_APPEND);
                }
            } else {
                file_put_contents('payment_error.log', "[" . date('Y-m-d H:i:s') . "] Order ID $order_id not found in pembayaran\n", FILE_APPEND);
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
<?php
// pages/warga/process_payment.php

// Enable error reporting for debugging (bisa dimatikan di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/midtrans.php';

$user_id = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

// Ambil data JSON atau POST form biasa (kita akan pakai FormData di JS, jadi $_POST)
$nama = $_POST['nama'] ?? '';
$kategori = $_POST['kategori'] ?? '';
$jumlah_raw = $_POST['jumlah'] ?? '0';
$jumlah = (int)preg_replace('/[^0-9]/', '', $jumlah_raw);
$catatan = $_POST['catatan'] ?? '';

if (empty($nama) || empty($kategori) || $jumlah <= 0) {
    echo json_encode(['success' => false, 'message' => 'Mohon lengkapi semua data.']);
    exit;
}

// Cek pembayaran pending
$check_stmt = $conn->prepare("SELECT id FROM pembayaran WHERE user_id = ? AND status = 'pending'");
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Anda sudah memiliki pembayaran pending.']);
    $check_stmt->close();
    exit;
}
$check_stmt->close();

// Buat transaksi Midtrans
$order_id = 'ORDER-' . uniqid();
$params = [
    'transaction_details' => [
        'order_id' => $order_id,
        'gross_amount' => $jumlah,
    ],
    'customer_details' => [
        'first_name' => $nama,
        'email' => 'user@example.com', // Idealnya ambil dari DB user
    ],
    'item_details' => [
        [
            'id' => strtolower(str_replace(' ', '-', $kategori)),
            'price' => $jumlah,
            'quantity' => 1,
            'name' => $kategori,
        ],
    ],
];

try {
    $snapToken = \Midtrans\Snap::getSnapToken($params);
    
    // Simpan ke database
    $stmt = $conn->prepare("INSERT INTO pembayaran (user_id, jumlah, metode, status, order_id) VALUES (?, ?, 'midtrans', 'pending', ?)");
    $stmt->bind_param("iis", $user_id, $jumlah, $order_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'token' => $snapToken,
            'order_id' => $order_id
        ]);
    } else {
        throw new Exception("Gagal menyimpan ke database");
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

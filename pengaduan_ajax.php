<?php
/**
 * pengaduan_ajax.php
 * Endpoint AJAX khusus untuk aksi pengaduan admin (update status & hapus).
 * Dipanggil via fetch() dari pages/admin/pengaduan.php
 */
session_start();

// Hanya boleh diakses oleh admin/ketua yang sudah login
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'ketua'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';

$ajax_action = $_POST['ajax_action'] ?? '';
$id          = intval($_POST['id'] ?? 0);

if ($ajax_action === 'update_status' && $id > 0) {
    $row = $conn->query("SELECT status FROM pengaduan WHERE id = $id")->fetch_assoc();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan.']);
        exit;
    }

    $current  = strtolower($row['status']);
    $next_map = ['diterima' => 'Diproses', 'diproses' => 'Selesai'];

    if (!isset($next_map[$current])) {
        echo json_encode(['success' => false, 'message' => 'Status sudah final.']);
        exit;
    }

    $new_status = $next_map[$current];
    $stmt = $conn->prepare("UPDATE pengaduan SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'new_status' => $new_status]);
    exit;
}

if ($ajax_action === 'hapus' && $id > 0) {
    $stmt = $conn->prepare("DELETE FROM pengaduan WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);

<?php
/**
 * Script sekali pakai: Hapus tabel pengeluaran dari database.
 * Jalankan sekali via browser, lalu hapus file ini.
 */
require_once 'config/database.php';

echo "<pre style='font-family:monospace;font-size:14px;padding:20px;'>";
echo "=== DROP TABLE pengeluaran ===\n\n";

// Cek apakah tabel masih ada
$cek = $conn->query("SHOW TABLES LIKE 'pengeluaran'");
if ($cek && $cek->num_rows > 0) {
    echo "[INFO] Tabel 'pengeluaran' ditemukan. Memulai proses penghapusan...\n";

    // Matikan foreign key checks sementara
    $conn->query("SET FOREIGN_KEY_CHECKS=0");

    // Hapus tabel
    if ($conn->query("DROP TABLE pengeluaran")) {
        echo "[OK]   Tabel 'pengeluaran' berhasil dihapus.\n";
    } else {
        echo "[ERROR] Gagal menghapus tabel: " . $conn->error . "\n";
    }

    // Nyalakan kembali foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
} else {
    echo "[INFO] Tabel 'pengeluaran' tidak ditemukan (mungkin sudah dihapus sebelumnya).\n";
}

echo "\n--- Daftar tabel yang tersisa ---\n";
$tables = $conn->query("SHOW TABLES");
while ($row = $tables->fetch_row()) {
    $tabel = $row[0];
    $marker = ($tabel === 'pengeluaran') ? " <-- MASIH ADA! (Error)" : "";
    echo "  - $tabel$marker\n";
}

echo "\n[SELESAI] Silakan hapus file drop_pengeluaran.php ini setelah proses selesai.";
echo "</pre>";
?>

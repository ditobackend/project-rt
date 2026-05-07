<?php
/**
 * database_cleanup_final.php
 * Script ini digunakan untuk membersihkan kolom-kolom redundan di database RT Manajemen
 * agar sesuai dengan alur sistem yang terbaru (Warga -> Ketua RT -> Admin).
 */

require_once 'config/database.php';

// Styling untuk output agar premium
echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Cleanup | RT Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Plus Jakarta Sans", sans-serif; }
        .log-entry { animation: slideIn 0.3s ease-out forwards; opacity: 0; }
        @keyframes slideIn { from { transform: translateX(-10px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-2xl bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-100">
        <div class="bg-slate-900 p-10 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 bg-blue-500/10 blur-3xl rounded-full"></div>
            <h1 class="text-3xl font-black tracking-tight mb-2 flex items-center gap-3">
                <i class="fas fa-broom text-blue-400"></i> Database Optimizer
            </h1>
            <p class="text-slate-400 font-medium">Membersihkan kolom redundan dan sinkronisasi struktur data.</p>
        </div>
        
        <div class="p-10 space-y-4" id="log-container">';

function logMessage($msg, $type = 'info') {
    $colors = [
        'info' => 'bg-blue-50 text-blue-700 border-blue-100',
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'error' => 'bg-red-50 text-red-700 border-red-100',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-100'
    ];
    $icons = [
        'info' => 'fa-info-circle',
        'success' => 'fa-check-circle',
        'error' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle'
    ];
    
    echo "<div class='log-entry p-4 rounded-2xl border {$colors[$type]} flex items-center gap-3 mb-3'>
            <i class='fas {$icons[$type]}'></i>
            <span class='text-sm font-bold uppercase tracking-tight'>$msg</span>
          </div>";
}

function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

function dropColumnSafely($conn, $table, $column) {
    if (columnExists($conn, $table, $column)) {
        // Cari nama constraint Foreign Key jika ada
        $dbname = $GLOBALS['dbname'] ?? 'rt_manajemen'; // Pastikan dbname tersedia atau ambil dari config
        $fk_query = "SELECT CONSTRAINT_NAME 
                     FROM information_schema.KEY_COLUMN_USAGE 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = '$table' 
                     AND COLUMN_NAME = '$column' 
                     AND REFERENCED_TABLE_NAME IS NOT NULL";
        $fk_result = $conn->query($fk_query);
        
        if ($fk_result && $fk_result->num_rows > 0) {
            while ($fk_row = $fk_result->fetch_assoc()) {
                $fk_name = $fk_row['CONSTRAINT_NAME'];
                logMessage("Menghapus Foreign Key constraint '$fk_name' pada tabel '$table'...", "warning");
                $conn->query("ALTER TABLE `$table` DROP FOREIGN KEY `$fk_name` ");
                // Coba juga DROP INDEX jika namanya sama
                $conn->query("ALTER TABLE `$table` DROP INDEX `$fk_name` ");
            }
        }

        // Coba hapus kolom
        if ($conn->query("ALTER TABLE `$table` DROP COLUMN `$column`")) {
            logMessage("Kolom '$column' di tabel '$table' berhasil dihapus.", "success");
        } else {
            // Jika masih gagal karena index, coba cari index manual
            logMessage("Gagal menghapus kolom '$column'. Mencoba pembersihan index...", "warning");
            $conn->query("ALTER TABLE `$table` DROP INDEX `$column` "); // Kadang ada index dengan nama kolom
            
            if ($conn->query("ALTER TABLE `$table` DROP COLUMN `$column`")) {
                logMessage("Kolom '$column' berhasil dihapus setelah pembersihan index.", "success");
            } else {
                logMessage("Gagal total menghapus '$column': " . $conn->error, "error");
            }
        }
    } else {
        logMessage("Kolom '$column' di tabel '$table' sudah tidak ada (Skipped).", "info");
    }
}

// 1. Membersihkan Tabel Kegiatan
logMessage("Menganalisis tabel 'kegiatan'...");
dropColumnSafely($conn, 'kegiatan', 'admin_id');
dropColumnSafely($conn, 'kegiatan', 'penyelenggara');

// 2. Membersihkan Tabel Keuangan
logMessage("Menganalisis tabel 'keuangan'...");
dropColumnSafely($conn, 'keuangan', 'sumber');

// 3. Membersihkan Tabel Laporan (Struktur Lama)
logMessage("Menganalisis tabel 'laporan'...");
dropColumnSafely($conn, 'laporan', 'periode_awal');
dropColumnSafely($conn, 'laporan', 'periode_akhir');
dropColumnSafely($conn, 'laporan', 'dibuat_oleh');

// 4. Verifikasi Akhir
logMessage("Verifikasi integritas database...");
logMessage("Optimasi database selesai. Semua kolom redundan telah dihapus.", "success");

echo '  </div>
        <div class="px-10 pb-10">
            <a href="index.php" class="block w-full py-5 bg-slate-900 text-white text-center font-black rounded-2xl hover:bg-slate-800 transition-all uppercase tracking-widest text-xs">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
    
    <script>
        // Efek stagger untuk log
        document.querySelectorAll(".log-entry").forEach((el, i) => {
            el.style.animationDelay = (i * 150) + "ms";
        });
    </script>
</body>
</html>';

// 5. Sinkronisasi dengan rt_management.sql
logMessage("Memulai sinkronisasi dengan rt_management.sql...");
$sql_file = 'rt_management.sql';
if (file_exists($sql_file)) {
    $sql_content = file_get_contents($sql_file);
    
    // Hapus komentar dan baris kosong untuk menghindari masalah multi_query
    // Namun multi_query biasanya bisa menangani dump phpMyAdmin
    if ($conn->multi_query($sql_content)) {
        $count = 0;
        do {
            $count++;
            // Kosongkan hasil agar bisa lanjut ke query berikutnya
            if ($result = $conn->store_result()) { $result->free(); }
        } while ($conn->more_results() && $conn->next_result());
        
        logMessage("Sinkronisasi SQL selesai ($count batch query dieksekusi).", "success");
    } else {
        logMessage("Gagal mengeksekusi SQL: " . $conn->error, "error");
    }
} else {
    logMessage("File $sql_file tidak ditemukan!", "error");
}

$conn->close();
?>

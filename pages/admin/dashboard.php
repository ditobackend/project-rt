<?php
require_once __DIR__ . '/../../config/database.php';

// Data All Time untuk Top Cards
$sqlPemasukan = $conn->query("SELECT SUM(jumlah) as total FROM keuangan WHERE jenis = 'pemasukan'");
$pemasukanPenuh = $sqlPemasukan->fetch_assoc()['total'] ?? 0;

$sqlPengeluaran = $conn->query("SELECT SUM(jumlah) as total FROM keuangan WHERE jenis = 'pengeluaran'");
$pengeluaranPenuh = $sqlPengeluaran->fetch_assoc()['total'] ?? 0;

$saldoKasPenuh = $pemasukanPenuh - $pengeluaranPenuh;

$sqlKegiatan = $conn->query("SELECT COUNT(*) as total FROM kegiatan WHERE CONCAT(tanggal, ' ', jam_selesai) >= NOW()");
$kegiatanAktif = $sqlKegiatan->fetch_assoc()['total'] ?? 0;

// Data Bulan Ini untuk Ringkasan Keuangan
$bulanIni = date('Y-m');
$sqlPemasukanBulanIni = $conn->query("SELECT SUM(jumlah) as total FROM keuangan WHERE jenis = 'pemasukan' AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulanIni'");
$pemasukanBulanIni = $sqlPemasukanBulanIni->fetch_assoc()['total'] ?? 0;

$sqlPengeluaranBulanIni = $conn->query("SELECT SUM(jumlah) as total FROM keuangan WHERE jenis = 'pengeluaran' AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulanIni'");
$pengeluaranBulanIni = $sqlPengeluaranBulanIni->fetch_assoc()['total'] ?? 0;

// Riwayat Aktivitas Terbaru
$queryAktivitas = $conn->query("SELECT * FROM keuangan ORDER BY tanggal DESC, id DESC LIMIT 3");
?>
<h2 class="text-2xl font-bold mb-4">Dashboard Admin</h2>
<p class="mb-6 text-gray-600">Ringkasan data dan aktivitas RT</p>

<div class="grid md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white shadow rounded-xl p-6 flex items-center space-x-4">
        <div class="bg-green-100 p-3 rounded-full">
            <i class="fas fa-arrow-down text-green-600 text-2xl"></i>
        </div>
        <div>
            <p class="text-gray-600 text-sm whitespace-nowrap">Saldo Pemasukan</p>
            <p class="text-xl font-bold">Rp <?= number_format($pemasukanPenuh, 0, ',', '.') ?></p>
        </div>
    </div>
    <div class="bg-white shadow rounded-xl p-6 flex items-center space-x-4">
        <div class="bg-red-100 p-3 rounded-full">
            <i class="fas fa-arrow-up text-red-600 text-2xl"></i>
        </div>
        <div>
            <p class="text-gray-600 text-sm whitespace-nowrap">Saldo Pengeluaran</p>
            <p class="text-xl font-bold">Rp <?= number_format($pengeluaranPenuh, 0, ',', '.') ?></p>
        </div>
    </div>
    <div class="bg-white shadow rounded-xl p-6 flex items-center space-x-4">
        <div class="bg-blue-100 p-3 rounded-full">
            <i class="fas fa-wallet text-blue-600 text-2xl"></i>
        </div>
        <div>
            <p class="text-gray-600 text-sm whitespace-nowrap">Saldo Kas Saat Ini</p>
            <p class="text-xl font-bold">Rp <?= number_format($saldoKasPenuh, 0, ',', '.') ?></p>
        </div>
    </div>
    <div class="bg-white shadow rounded-xl p-6 flex items-center space-x-4">
        <div class="bg-yellow-100 p-3 rounded-full">
            <i class="fas fa-calendar-check text-yellow-600 text-2xl"></i>
        </div>
        <div>
            <p class="text-gray-600 text-sm whitespace-nowrap">Kegiatan Aktif</p>
            <p class="text-xl font-bold"><?= $kegiatanAktif ?></p>
        </div>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white shadow rounded-xl p-6">
        <h3 class="font-bold mb-4">Aktivitas Terbaru</h3>
        <ul class="space-y-3">
            <?php 
            if($queryAktivitas->num_rows > 0): 
                while($akt = $queryAktivitas->fetch_assoc()):
                    if($akt['jenis'] == 'pemasukan'){
                        $iconColor = "green";
                        $iconCls = "fa-plus-circle";
                        $text = "Pemasukan diterima - Rp " . number_format($akt['jumlah'],0,',','.');
                    } else {
                        $iconColor = "red";
                        $iconCls = "fa-minus-circle";
                        $text = "Pengeluaran dicatat - Rp " . number_format($akt['jumlah'],0,',','.');
                    }
                    // Extracting nama/kategori
                    $parts = explode(' - ', $akt['keterangan'], 3);
                    if(count($parts) >= 2) {
                        $infoText = $parts[0] != '-' ? $parts[0] . ' (' . $parts[1] . ')' : $parts[1];
                        $text .= " dari " . $infoText;
                    }
            ?>
            <li class="bg-<?= $iconColor ?>-50 p-3 rounded-lg flex items-center space-x-2">
                <i class="fas <?= $iconCls ?> text-<?= $iconColor ?>-600"></i>
                <span class="text-sm"><?= htmlspecialchars($text) ?></span>
            </li>
            <?php 
                endwhile;
            else: 
            ?>
            <li class="text-gray-500 text-sm">Belum ada aktivitas.</li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="bg-white shadow rounded-xl p-6">
        <h3 class="font-bold mb-4">Ringkasan Keuangan (Bulan <?= date('F Y') ?>)</h3>
        <ul class="space-y-3">
            <li class="bg-green-50 p-3 rounded-lg flex justify-between">
                <span>Pemasukan Bulan Ini</span>
                <span class="text-green-600 font-bold">Rp <?= number_format($pemasukanBulanIni, 0, ',', '.') ?></span>
            </li>
            <li class="bg-red-50 p-3 rounded-lg flex justify-between">
                <span>Pengeluaran Bulan Ini</span>
                <span class="text-red-600 font-bold">Rp <?= number_format($pengeluaranBulanIni, 0, ',', '.') ?></span>
            </li>
            <li class="bg-blue-50 p-3 rounded-lg flex justify-between">
                <span>Saldo Keseluruhan</span>
                <span class="text-blue-600 font-bold">Rp <?= number_format($saldoKasPenuh, 0, ',', '.') ?></span>
            </li>
        </ul>
    </div>
</div>

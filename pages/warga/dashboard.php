<?php
require_once __DIR__ . '/../../config/database.php';

// Data All Time untuk Total Saldo Kas
$sqlPemasukan = $conn->query("SELECT SUM(jumlah) as total FROM keuangan WHERE jenis = 'pemasukan'");
$pemasukanPenuh = $sqlPemasukan->fetch_assoc()['total'] ?? 0;

$sqlPengeluaran = $conn->query("SELECT SUM(jumlah) as total FROM keuangan WHERE jenis = 'pengeluaran'");
$pengeluaranPenuh = $sqlPengeluaran->fetch_assoc()['total'] ?? 0;

$saldoKasPenuh = $pemasukanPenuh - $pengeluaranPenuh;

// Data Bulan Ini
$bulanIni = date('Y-m');
$sqlPemasukanBulanIni = $conn->query("SELECT SUM(jumlah) as total FROM keuangan WHERE jenis = 'pemasukan' AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulanIni'");
$pemasukanBulanIni = $sqlPemasukanBulanIni->fetch_assoc()['total'] ?? 0;

$sqlPengeluaranBulanIni = $conn->query("SELECT SUM(jumlah) as total FROM keuangan WHERE jenis = 'pengeluaran' AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulanIni'");
$pengeluaranBulanIni = $sqlPengeluaranBulanIni->fetch_assoc()['total'] ?? 0;

// Kegiatan Terbaru (yang belum lewat / expired)
$queryKegiatan = $conn->query("SELECT * FROM kegiatan WHERE CONCAT(tanggal, ' ', jam_selesai) >= NOW() ORDER BY tanggal ASC LIMIT 3");
?>
<h2 class="text-2xl font-bold mb-2">Dashboard Warga</h2>
<p class="text-gray-600 mb-6">Selamat datang di portal informasi RT</p>

<div class="grid md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow flex items-center space-x-4">
        <div class="bg-green-100 p-3 rounded-full">
            <i class="fas fa-arrow-down text-green-600 text-2xl"></i>
        </div>
        <div>
            <p class="text-gray-600 text-sm whitespace-nowrap">Pemasukan Bulan Ini</p>
            <h3 class="text-xl font-bold text-green-600">Rp <?= number_format($pemasukanBulanIni, 0, ',', '.') ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow flex items-center space-x-4">
        <div class="bg-red-100 p-3 rounded-full">
            <i class="fas fa-arrow-up text-red-600 text-2xl"></i>
        </div>
        <div>
            <p class="text-gray-600 text-sm whitespace-nowrap">Pengeluaran Bulan Ini</p>
            <h3 class="text-xl font-bold text-red-600">Rp <?= number_format($pengeluaranBulanIni, 0, ',', '.') ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow flex items-center space-x-4">
        <div class="bg-blue-100 p-3 rounded-full">
            <i class="fas fa-wallet text-blue-600 text-2xl"></i>
        </div>
        <div>
            <p class="text-gray-600 text-sm whitespace-nowrap">Saldo Kas Keseluruhan</p>
            <h3 class="text-xl font-bold text-blue-600">Rp <?= number_format($saldoKasPenuh, 0, ',', '.') ?></h3>
        </div>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="font-bold mb-4">Kegiatan Terbaru</h3>
        <div class="space-y-3">
            <?php 
            if($queryKegiatan->num_rows > 0): 
                while($kgt = $queryKegiatan->fetch_assoc()):
            ?>
            <div class="p-4 bg-blue-50 rounded flex items-center space-x-3">
                <i class="fas fa-calendar text-blue-500 text-xl"></i>
                <div class="overflow-hidden">
                    <p class="font-semibold truncate" title="<?= htmlspecialchars($kgt['judul']) ?>"><?= htmlspecialchars($kgt['judul']) ?></p>
                    <span class="text-gray-600 text-sm"><?= date('d F Y', strtotime($kgt['tanggal'])) ?></span>
                </div>
            </div>
            <?php 
                endwhile;
            else: 
            ?>
            <p class="text-gray-500 text-sm">Tidak ada kegiatan terbaru.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow">
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

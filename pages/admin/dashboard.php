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
$queryAktivitas = $conn->query("SELECT * FROM keuangan ORDER BY tanggal DESC, id DESC LIMIT 5");
?>

<div class="mb-10">
    <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Ringkasan Eksekutif</h2>
    <p class="text-secondary-500 mt-1">Gambaran umum pengelolaan RT Anda secara real-time.</p>
</div>

<!-- Stats Grid -->
<div class="grid md:grid-cols-4 gap-6 mb-12">
    <!-- Card 1 -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary-100 card-modern overflow-hidden relative group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-green-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
        <div class="flex flex-col">
            <div class="bg-green-100 text-green-600 w-12 h-12 rounded-2xl flex items-center justify-center mb-4 shadow-inner">
                <i class="fas fa-arrow-down-long text-lg"></i>
            </div>
            <span class="text-secondary-500 text-sm font-semibold uppercase tracking-wider">Total Pemasukan</span>
            <span class="text-2xl font-black text-secondary-900 mt-1">Rp <?= number_format($pemasukanPenuh, 0, ',', '.') ?></span>
            <div class="mt-4 flex items-center text-xs font-bold text-green-600">
                <i class="fas fa-chart-line mr-1"></i> Seluruh Pemasukan
            </div>
        </div>
    </div>

    <!-- Card 2 -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary-100 card-modern overflow-hidden relative group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-red-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
        <div class="flex flex-col">
            <div class="bg-red-100 text-red-600 w-12 h-12 rounded-2xl flex items-center justify-center mb-4 shadow-inner">
                <i class="fas fa-arrow-up-long text-lg"></i>
            </div>
            <span class="text-secondary-500 text-sm font-semibold uppercase tracking-wider">Total Pengeluaran</span>
            <span class="text-2xl font-black text-secondary-900 mt-1">Rp <?= number_format($pengeluaranPenuh, 0, ',', '.') ?></span>
            <div class="mt-4 flex items-center text-xs font-bold text-red-600">
                <i class="fas fa-chart-pie mr-1"></i> Seluruh Pengeluaran
            </div>
        </div>
    </div>

    <!-- Card 3 -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary-100 card-modern overflow-hidden relative group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
        <div class="flex flex-col">
            <div class="bg-primary-100 text-primary-600 w-12 h-12 rounded-2xl flex items-center justify-center mb-4 shadow-inner">
                <i class="fas fa-wallet text-lg"></i>
            </div>
            <span class="text-secondary-500 text-sm font-semibold uppercase tracking-wider">Saldo Bersih</span>
            <span class="text-2xl font-black text-secondary-900 mt-1">Rp <?= number_format($saldoKasPenuh, 0, ',', '.') ?></span>
            <div class="mt-4 flex items-center text-xs font-bold text-primary-600">
                <i class="fas fa-shield-alt mr-1"></i> Likuiditas Saat Ini
            </div>
        </div>
    </div>

    <!-- Card 4 -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary-100 card-modern overflow-hidden relative group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-yellow-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
        <div class="flex flex-col">
            <div class="bg-yellow-100 text-yellow-600 w-12 h-12 rounded-2xl flex items-center justify-center mb-4 shadow-inner">
                <i class="fas fa-calendar-check text-lg"></i>
            </div>
            <span class="text-secondary-500 text-sm font-semibold uppercase tracking-wider">Kegiatan Aktif</span>
            <span class="text-2xl font-black text-secondary-900 mt-1"><?= $kegiatanAktif ?></span>
            <div class="mt-4 flex items-center text-xs font-bold text-yellow-600">
                <i class="fas fa-clock mr-1"></i> Mendatang & Berlangsung
            </div>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <!-- Left Column: Activity Feed -->
    <div class="lg:col-span-2 space-y-8">
        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-secondary-100">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-bold text-secondary-900 flex items-center">
                    <span class="w-2 h-8 bg-primary-500 rounded-full mr-3"></span>
                    Aktivitas Terbaru
                </h3>
                <a href="?page=keuangan" class="text-sm font-bold text-primary-600 hover:underline">Lihat Semua</a>
            </div>

            <div class="relative pl-8 space-y-8 before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-0.5 before:bg-secondary-100">
                <?php 
                if($queryAktivitas->num_rows > 0): 
                    while($akt = $queryAktivitas->fetch_assoc()):
                        $isIncome = ($akt['jenis'] == 'pemasukan');
                        $color = $isIncome ? "green" : "red";
                        $icon = $isIncome ? "fa-arrow-down" : "fa-arrow-up";
                        
                        // Parse name/cat
                        $parts = explode(' - ', $akt['keterangan'], 3);
                        $title = "Transaksi Keuangan";
                        $subtitle = $akt['keterangan'];
                        if(count($parts) >= 2) {
                            $title = $parts[0] != '-' ? $parts[0] : $parts[1];
                            $subtitle = $parts[count($parts)-1];
                        }
                ?>
                <div class="relative group">
                    <div class="absolute -left-[27px] top-1 w-[13px] h-[13px] rounded-full bg-<?= $color ?>-500 ring-4 ring-<?= $color ?>-50 group-hover:scale-125 transition-all"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-secondary-900 group-hover:text-primary-600 transition-colors"><?= htmlspecialchars($title) ?></p>
                            <p class="text-sm text-secondary-500 font-medium"><?= htmlspecialchars($subtitle) ?></p>
                            <span class="inline-block mt-2 text-[10px] font-black uppercase tracking-widest text-secondary-400 bg-secondary-50 px-2 py-0.5 rounded"><?= date('d M, H:i', strtotime($akt['tanggal'])) ?></span>
                        </div>
                        <div class="text-right">
                            <p class="font-black text-<?= $color ?>-600"><?= ($isIncome?'+':'-') ?> Rp <?= number_format($akt['jumlah'], 0, ',', '.') ?></p>
                            <span class="text-[10px] font-bold text-<?= $color ?>-500/50 uppercase"><?= $isIncome ? 'MASUK' : 'KELUAR' ?></span>
                        </div>
                    </div>
                </div>
                <?php 
                    endwhile;
                else: 
                ?>
                <div class="text-center py-10">
                    <i class="fas fa-box-open text-4xl text-secondary-200 mb-4 block"></i>
                    <p class="text-secondary-400 font-medium">Tidak ada aktivitas terbaru.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Monthly Summary -->
    <div class="space-y-8">
        <div class="bg-secondary-900 p-8 rounded-[2rem] shadow-xl text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-primary-500/20 blur-3xl rounded-full"></div>
            
            <h3 class="text-xl font-bold mb-6 flex items-center">
                <i class="fas fa-calendar-alt mr-3 text-primary-400"></i>
                Ringkasan Bulanan
            </h3>
            
            <div class="space-y-6 relative z-10">
                <div class="p-5 bg-white/5 rounded-2xl backdrop-blur-sm border border-white/5">
                    <p class="text-secondary-400 text-xs font-bold uppercase tracking-widest mb-1">Pemasukan Bulan Ini</p>
                    <p class="text-2xl font-black text-green-400">Rp <?= number_format($pemasukanBulanIni, 0, ',', '.') ?></p>
                </div>
                
                <div class="p-5 bg-white/5 rounded-2xl backdrop-blur-sm border border-white/5">
                    <p class="text-secondary-400 text-xs font-bold uppercase tracking-widest mb-1">Pengeluaran Bulan Ini</p>
                    <p class="text-2xl font-black text-red-400">Rp <?= number_format($pengeluaranBulanIni, 0, ',', '.') ?></p>
                </div>

                <div class="pt-4 border-t border-white/10">
                    <div class="flex items-center justify-between">
                        <span class="text-secondary-400 font-medium">Total Kas</span>
                        <span class="text-primary-400 font-black">Rp <?= number_format($saldoKasPenuh, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <a href="?page=laporan" class="block w-full bg-white text-secondary-900 text-center py-4 rounded-2xl font-black text-sm hover:bg-primary-500 hover:text-white transition-all">
                    Unduh Laporan Bulanan
                </a>
            </div>
        </div>

        <!-- Quick Help -->
        <div class="bg-primary-50 p-6 rounded-[2rem] border border-primary-100">
            <h4 class="font-bold text-primary-900 mb-2">Butuh Bantuan?</h4>
            <p class="text-sm text-primary-700/70 mb-4 leading-relaxed">Akses panduan pengelolaan RT atau hubungi tim teknis jika ada kendala.</p>
            <button class="text-sm font-black text-primary-600 flex items-center hover:translate-x-1 transition-transform">
                Baca Dokumentasi <i class="fas fa-arrow-right ml-2"></i>
            </button>
        </div>
    </div>
</div>

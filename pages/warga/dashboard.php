<?php
require_once __DIR__ . '/../../config/database.php';

// Data All Time untuk Total Saldo Kas (Dashboard Warga tetap tampilkan transparansi)
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

// Aktivitas Terakhir (Keuangan)
$queryAktivitas = $conn->query("SELECT * FROM keuangan ORDER BY tanggal DESC, id DESC LIMIT 5");
?>

<div class="mb-10">
    <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Ringkasan Warga</h2>
    <p class="text-secondary-500 mt-1">Pantau informasi terkini dan transparansi dana RT 06.</p>
</div>

<!-- Stats Grid -->
<div class="grid md:grid-cols-3 gap-6 mb-12">
    <!-- Card Pemasukan -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary-100 card-modern overflow-hidden relative group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-green-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
        <div class="flex items-center gap-4">
            <div class="bg-green-100 text-green-600 w-14 h-14 rounded-2xl flex items-center justify-center shadow-inner">
                <i class="fas fa-arrow-down-long text-xl"></i>
            </div>
            <div>
                <span class="text-secondary-500 text-[10px] font-black uppercase tracking-widest">Pemasukan Bulan Ini</span>
                <p class="text-xl font-black text-secondary-900 mt-0.5">Rp <?= number_format($pemasukanBulanIni, 0, ',', '.') ?></p>
            </div>
        </div>
    </div>

    <!-- Card Pengeluaran -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary-100 card-modern overflow-hidden relative group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-red-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
        <div class="flex items-center gap-4">
            <div class="bg-red-100 text-red-600 w-14 h-14 rounded-2xl flex items-center justify-center shadow-inner">
                <i class="fas fa-arrow-up-long text-xl"></i>
            </div>
            <div>
                <span class="text-secondary-500 text-[10px] font-black uppercase tracking-widest">Pengeluaran Bulan Ini</span>
                <p class="text-xl font-black text-secondary-900 mt-0.5">Rp <?= number_format($pengeluaranBulanIni, 0, ',', '.') ?></p>
            </div>
        </div>
    </div>

    <!-- Card Saldo Kas -->
    <div class="bg-primary-900 p-6 rounded-3xl shadow-xl overflow-hidden relative group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary-500/20 rounded-full group-hover:scale-150 transition-all duration-500"></div>
        <div class="flex items-center gap-4 relative z-10">
            <div class="bg-primary-500 text-white w-14 h-14 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-500/30">
                <i class="fas fa-wallet text-xl"></i>
            </div>
            <div>
                <span class="text-secondary-400 text-[10px] font-black uppercase tracking-widest">Saldo Kas RT</span>
                <p class="text-xl font-black text-white mt-0.5">Rp <?= number_format($saldoKasPenuh, 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <!-- Info Kegiatan -->
    <div class="lg:col-span-2 space-y-8">
        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-secondary-100">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-bold text-secondary-900 flex items-center">
                    <span class="w-2 h-8 bg-primary-500 rounded-full mr-3"></span>
                    Kegiatan Mendatang
                </h3>
                <a href="?page=kegiatan" class="text-sm font-bold text-primary-600 hover:underline">Lihat Semua</a>
            </div>

            <div class="grid md:grid-cols-1 gap-4">
                <?php if($queryKegiatan->num_rows > 0): ?>
                    <?php while($kgt = $queryKegiatan->fetch_assoc()): ?>
                    <div class="p-6 bg-secondary-50 hover:bg-primary-50/50 rounded-2xl transition-all border border-transparent hover:border-primary-100 group">
                        <div class="flex items-center gap-5">
                            <div class="bg-white w-14 h-14 rounded-2xl flex flex-col items-center justify-center shadow-sm text-secondary-900 group-hover:bg-primary-600 group-hover:text-white transition-all">
                                <span class="text-xs font-black uppercase"><?= date('M', strtotime($kgt['tanggal'])) ?></span>
                                <span class="text-xl font-black leading-none"><?= date('d', strtotime($kgt['tanggal'])) ?></span>
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-secondary-900 text-lg group-hover:text-primary-700 transition-colors"><?= htmlspecialchars($kgt['judul']) ?></p>
                                <div class="flex items-center gap-4 mt-1">
                                    <span class="text-xs font-medium text-secondary-400 flex items-center">
                                        <i class="far fa-clock mr-1.5 text-primary-500"></i>
                                        <?= substr($kgt['jam_mulai'],0,5) ?> - <?= substr($kgt['jam_selesai'],0,5) ?> WIB
                                    </span>
                                    <span class="text-xs font-black uppercase tracking-widest text-primary-500">MENDATANG</span>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-secondary-200 group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-10">
                        <i class="fas fa-calendar-xmark text-5xl text-secondary-100 mb-4 block"></i>
                        <p class="text-secondary-400 font-bold">Belum ada kegiatan yang direncanakan.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right: Transparansi Dana (Simple Feed) -->
    <div class="space-y-8">
        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-secondary-100">
            <h3 class="text-xl font-bold text-secondary-900 mb-8 flex items-center">
                <i class="fas fa-history mr-3 text-primary-500"></i>
                Catatan Dana
            </h3>

            <div class="space-y-6 relative before:absolute before:left-[15px] before:top-2 before:bottom-2 before:w-0.5 before:bg-secondary-50">
                <?php 
                if($queryAktivitas->num_rows > 0): 
                    while($akt = $queryAktivitas->fetch_assoc()):
                        $isInc = ($akt['jenis'] == 'pemasukan');
                        $color = $isInc ? "green" : "red";
                ?>
                <div class="relative pl-10 group">
                    <div class="absolute left-0 top-1.5 w-[31px] h-[31px] rounded-full bg-white border-2 border-<?= $color ?>-500 flex items-center justify-center z-10">
                        <i class="fas fa-<?= $isInc ? 'arrow-down' : 'arrow-up' ?> text-[10px] text-<?= $color ?>-500"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-secondary-900 leading-tight"><?= htmlspecialchars($akt['keterangan']) ?></p>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-[10px] font-medium text-secondary-400 capitalize"><?= date('d M Y', strtotime($akt['tanggal'])) ?></span>
                            <span class="text-xs font-black text-<?= $color ?>-600"><?= $isInc ? '+' : '-' ?> Rp <?= number_format($akt['jumlah'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
                <?php endwhile; else: ?>
                    <p class="text-secondary-400 text-center text-sm font-medium">Belum ada data keuangan.</p>
                <?php endif; ?>
            </div>

            <div class="mt-8">
                <a href="?page=keuangan" class="block w-full py-4 text-center bg-secondary-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-secondary-800 transition-all active:scale-95">
                    Laporan Lengkap
                </a>
            </div>
        </div>

        <div class="bg-primary-50 p-6 rounded-3xl border border-primary-100">
            <h4 class="font-bold text-primary-900 mb-2">Punya Pertanyaan?</h4>
            <p class="text-sm text-primary-700/70 mb-4 leading-relaxed">Hubungi admin RT melalui layanan pengaduan jika ada aspirasi atau kendala.</p>
            <a href="?page=pengaduan" class="inline-flex items-center text-sm font-black text-primary-600 hover:translate-x-1 transition-transform">
                Kirim Pengaduan <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</div>

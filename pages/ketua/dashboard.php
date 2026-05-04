<?php
// pages/ketua/dashboard.php

// Hitung statistik untuk Ketua RT
$totalKegiatanPending = 0;
$res = $conn->query("SELECT COUNT(*) as total FROM kegiatan WHERE status_persetujuan = 'pending'");
if($res) $totalKegiatanPending = $res->fetch_assoc()['total'];

$totalSaldo = 0;
$res = $conn->query("SELECT 
    (SUM(CASE WHEN jenis = 'pemasukan' THEN jumlah ELSE 0 END) - 
     SUM(CASE WHEN jenis = 'pengeluaran' THEN jumlah ELSE 0 END)) as saldo 
    FROM keuangan");
if($res) $totalSaldo = $res->fetch_assoc()['saldo'];

$totalPemasukan = 0;
$totalPengeluaran = 0;
$res = $conn->query("SELECT 
    SUM(CASE WHEN jenis = 'pemasukan' THEN jumlah ELSE 0 END) as masuk,
    SUM(CASE WHEN jenis = 'pengeluaran' THEN jumlah ELSE 0 END) as keluar
    FROM keuangan");
if($res) {
    $data = $res->fetch_assoc();
    $totalPemasukan = $data['masuk'];
    $totalPengeluaran = $data['keluar'];
}
?>

<div class="mb-10">
    <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Ringkasan Otoritas</h2>
    <p class="text-secondary-500 mt-1">Pantau persetujuan kegiatan dan laporan keuangan lingkungan.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
    <!-- Kas RT -->
    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-secondary-100 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-green-50 rounded-full group-hover:scale-110 transition-transform"></div>
        <div class="relative z-10">
            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-2xl flex items-center justify-center mb-4 shadow-inner">
                <i class="fas fa-wallet text-xl"></i>
            </div>
            <p class="text-secondary-400 text-[10px] font-black uppercase tracking-widest mb-1">Saldo Kas RT</p>
            <h3 class="text-2xl font-black text-secondary-900">Rp <?= number_format($totalSaldo, 0, ',', '.'); ?></h3>
        </div>
    </div>

    <!-- Pending Approval -->
    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-secondary-100 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary-50 rounded-full group-hover:scale-110 transition-transform"></div>
        <div class="relative z-10">
            <div class="w-12 h-12 bg-primary-100 text-primary-600 rounded-2xl flex items-center justify-center mb-4 shadow-inner">
                <i class="fas fa-clock text-xl"></i>
            </div>
            <p class="text-secondary-400 text-[10px] font-black uppercase tracking-widest mb-1">Menunggu Persetujuan</p>
            <h3 class="text-2xl font-black text-secondary-900"><?= $totalKegiatanPending ?> <span class="text-sm font-bold text-secondary-400">Kegiatan</span></h3>
        </div>
    </div>

    <!-- Ringkasan Keuangan -->
    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-secondary-100 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-50 rounded-full group-hover:scale-110 transition-transform"></div>
        <div class="relative z-10">
            <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mb-4 shadow-inner">
                <i class="fas fa-exchange-alt text-xl"></i>
            </div>
            <p class="text-secondary-400 text-[10px] font-black uppercase tracking-widest mb-1">Pemasukan & Pengeluaran</p>
            <div class="flex items-center gap-4">
                <div>
                    <span class="text-[9px] font-bold text-green-500 uppercase">Masuk</span>
                    <h3 class="text-lg font-black text-secondary-900 leading-none mt-1">Rp <?= number_format($totalPemasukan, 0, ',', '.'); ?></h3>
                </div>
                <div class="w-px h-8 bg-secondary-100"></div>
                <div>
                    <span class="text-[9px] font-bold text-red-500 uppercase">Keluar</span>
                    <h3 class="text-lg font-black text-secondary-900 leading-none mt-1">Rp <?= number_format($totalPengeluaran, 0, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Quick Actions -->
    <div class="bg-primary-900 p-10 rounded-[3rem] shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-primary-500/10 rounded-full -mr-20 -mt-20 blur-3xl"></div>
        <div class="relative z-10">
            <h3 class="text-2xl font-black text-white mb-2">Aksi Cepat Ketua RT</h3>
            <p class="text-primary-300 text-sm mb-8">Kelola persetujuan kegiatan warga dengan satu klik.</p>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="?page=kegiatan_approval" class="flex items-center justify-between p-5 bg-white/10 hover:bg-white/20 border border-white/10 rounded-3xl transition-all group">
                    <span class="text-white font-bold">Cek Pengajuan</span>
                    <i class="fas fa-arrow-right text-primary-400 group-hover:translate-x-1 transition-transform"></i>
                </a>
                <a href="?page=laporan" class="flex items-center justify-between p-5 bg-white/10 hover:bg-white/20 border border-white/10 rounded-3xl transition-all group">
                    <span class="text-white font-bold">Lihat Laporan</span>
                    <i class="fas fa-arrow-right text-primary-400 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Info -->
    <div class="bg-white p-10 rounded-[3rem] border border-secondary-100 shadow-sm">
        <h3 class="text-xl font-black text-secondary-900 mb-6">Informasi Sistem</h3>
        <ul class="space-y-4">
            <li class="flex items-start gap-4">
                <div class="w-2 h-2 bg-primary-500 rounded-full mt-2"></div>
                <p class="text-sm text-secondary-600 font-medium leading-relaxed">Anda memiliki otoritas penuh untuk menyetujui atau menolak setiap kegiatan yang diajukan oleh warga.</p>
            </li>
            <li class="flex items-start gap-4">
                <div class="w-2 h-2 bg-primary-500 rounded-full mt-2"></div>
                <p class="text-sm text-secondary-600 font-medium leading-relaxed">Data laporan keuangan kini dikelola langsung melalui dashboard Anda untuk memastikan transparansi.</p>
            </li>
        </ul>
    </div>
</div>

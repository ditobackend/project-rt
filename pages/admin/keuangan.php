<?php
// contoh ambil data dari database
include 'config/database.php';

// Hitung total pemasukan, pengeluaran, saldo
$totalPemasukan = 0;
$totalPengeluaran = 0;

$sql = $conn->query("SELECT jenis, SUM(jumlah) as total FROM keuangan GROUP BY jenis");
while ($row = $sql->fetch_assoc()) {
    if ($row['jenis'] == 'pemasukan') {
        $totalPemasukan = $row['total'];
    } elseif ($row['jenis'] == 'pengeluaran') {
        $totalPengeluaran = $row['total'];
    }
}
$saldoAkhir = $totalPemasukan - $totalPengeluaran;

// Ambil semua transaksi keuangan
$transaksi = $conn->query("SELECT * FROM keuangan ORDER BY tanggal DESC");
?>

<h2 class="text-2xl font-bold mb-4">Data Keuangan</h2>
<p class="mb-6 text-gray-600">Kelola keuangan RT</p>

<!-- Filter Bar -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 space-y-2 md:space-y-0">
    <!-- Group kiri -->
    <div class="flex flex-col sm:flex-row sm:space-x-2 space-y-2 sm:space-y-0 w-full md:w-auto">
        <select class="border px-3 py-2 rounded-lg w-full sm:w-auto">
            <option>Semua Jenis</option>
            <option>Pemasukan</option>
            <option>Pengeluaran</option>
        </select>
        <select class="border px-3 py-2 rounded-lg w-full sm:w-auto">
            <option>Januari 2024</option>
            <option>Februari 2024</option>
        </select>
        <button class="bg-green-600 text-white px-4 py-2 rounded-lg w-full sm:w-auto">
            <i class="fas fa-filter"></i> Filter
        </button>
    </div>

    <!-- Tombol kanan (hanya untuk pengeluaran) -->
    <button class="bg-red-600 text-white px-4 py-2 rounded-lg w-full md:w-auto">
        <i class="fas fa-minus mr-2"></i>Tambah Pengeluaran
    </button>
</div>

<!-- Cards Ringkasan -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white shadow rounded-xl p-6">
        <p class="text-gray-600">Total Pemasukan</p>
        <p class="text-2xl font-bold text-green-600">Rp <?= number_format($totalPemasukan, 0, ',', '.'); ?></p>
    </div>
    <div class="bg-white shadow rounded-xl p-6">
        <p class="text-gray-600">Total Pengeluaran</p>
        <p class="text-2xl font-bold text-red-600">Rp <?= number_format($totalPengeluaran, 0, ',', '.'); ?></p>
    </div>
    <div class="bg-white shadow rounded-xl p-6">
        <p class="text-gray-600">Saldo Akhir</p>
        <p class="text-2xl font-bold text-blue-600">Rp <?= number_format($saldoAkhir, 0, ',', '.'); ?></p>
    </div>
</div>

<!-- Tabel -->
<div class="bg-white shadow rounded-xl overflow-x-auto">
    <table class="w-full text-left min-w-[700px]">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-6 py-3">Tanggal</th>
                <th class="px-6 py-3">Keterangan</th>
                <th class="px-6 py-3">Jenis</th>
                <th class="px-6 py-3">Jumlah</th>
                <th class="px-6 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $transaksi->fetch_assoc()): ?>
                <tr class="border-t">
                    <td class="px-6 py-3"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                    <td class="px-6 py-3"><?= htmlspecialchars($row['keterangan']) ?></td>
                    <td class="px-6 py-3">
                        <?php if ($row['jenis'] == 'pemasukan'): ?>
                            <span class="bg-green-100 text-green-600 px-2 py-1 rounded">Pemasukan</span>
                        <?php else: ?>
                            <span class="bg-red-100 text-red-600 px-2 py-1 rounded">Pengeluaran</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-3 font-bold <?= $row['jenis']=='pemasukan'?'text-green-600': 'text-red-600' ?>">
                        <?= $row['jenis']=='pemasukan' ? '+ ' : '- ' ?>Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?>
                    </td>
                    <td class="px-6 py-3 flex space-x-2">
                        <button class="text-blue-600"><i class="fas fa-edit"></i></button>
                        <button class="text-red-600"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

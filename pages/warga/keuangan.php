<?php
include __DIR__ . '/../../config/database.php';

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
$transaksi = $conn->query("SELECT * FROM keuangan ORDER BY id DESC");
?>

<h2 class="text-2xl font-bold mb-2">Transparansi Keuangan</h2>
<p class="text-gray-600 mb-6">Informasi keuangan RT secara transparan</p>

<div class="grid md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white p-6 rounded-lg shadow">
        <p>Total Pemasukan</p>
        <h3 class="text-xl font-bold text-green-600">Rp <?= number_format($totalPemasukan, 0, ',', '.'); ?></h3>
    </div>
    <div class="bg-white p-6 rounded-lg shadow">
        <p>Total Pengeluaran</p>
        <h3 class="text-xl font-bold text-red-600">Rp <?= number_format($totalPengeluaran, 0, ',', '.'); ?></h3>
    </div>
    <div class="bg-white p-6 rounded-lg shadow">
        <p>Saldo Akhir</p>
        <h3 class="text-xl font-bold text-blue-600">Rp <?= number_format($saldoAkhir, 0, ',', '.'); ?></h3>
    </div>
</div>

<div class="flex space-x-4 mb-6">
    <select class="border rounded px-4 py-2">
        <option>Januari 2024</option>
        <option>Februari 2024</option>
    </select>
    <select class="border rounded px-4 py-2">
        <option>Semua</option>
        <option>Pemasukan</option>
        <option>Pengeluaran</option>
    </select>
    <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        <i class="fas fa-filter mr-1"></i>Filter
    </button>
</div>

<div class="bg-white p-6 rounded-lg shadow">
    <h3 class="font-bold mb-4">Riwayat Transaksi</h3>
    <table class="w-full">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left">Tanggal</th>
                <th class="p-2 text-left">Keterangan</th>
                <th class="p-2 text-left">Jenis</th>
                <th class="p-2 text-left">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $transaksi->fetch_assoc()): ?>
                <tr>
                    <td class="p-2"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                    <td class="p-2"><?= htmlspecialchars($row['keterangan']) ?></td>
                    <td class="p-2">
                        <?php if ($row['jenis'] == 'pemasukan'): ?>
                            <span class="bg-green-100 text-green-600 px-2 py-1 rounded">Pemasukan</span>
                        <?php else: ?>
                            <span class="bg-red-100 text-red-600 px-2 py-1 rounded">Pengeluaran</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-2 <?= $row['jenis']=='pemasukan'?'text-green-600': 'text-red-600' ?>">
                        <?= $row['jenis']=='pemasukan' ? '+ ' : '- ' ?>Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

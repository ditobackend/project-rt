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

// Proses Filter Keuangan
$filter_jenis = $_GET['filter_jenis'] ?? '';
$filter_bulan = $_GET['filter_bulan'] ?? '';
$where = [];
$params = [];
$types = '';

if (!empty($filter_jenis) && $filter_jenis != 'semua') {
    $where[] = "jenis = ?";
    $params[] = $filter_jenis;
    $types .= 's';
}

if (!empty($filter_bulan) && $filter_bulan != 'semua') {
    list($tahun, $bulan) = explode('-', $filter_bulan);
    $where[] = "MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
    $params[] = $bulan;
    $params[] = $tahun;
    $types .= 'ii';
}

$whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
$query_transaksi = "SELECT * FROM keuangan $whereClause ORDER BY tanggal DESC, id DESC";
$stmt_transaksi = $conn->prepare($query_transaksi);
if (!empty($params)) {
    $stmt_transaksi->bind_param($types, ...$params);
}
$stmt_transaksi->execute();
$transaksi = $stmt_transaksi->get_result();
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

<form method="GET" action="dashboard_warga.php" class="flex flex-col sm:flex-row sm:space-x-4 space-y-2 sm:space-y-0 mb-6 w-full">
    <input type="hidden" name="page" value="keuangan">
    <select name="filter_bulan" class="border rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="semua" <?= ($filter_bulan == 'semua' || empty($filter_bulan)) ? 'selected' : '' ?>>Semua Waktu</option>
        <?php
        for ($i = 0; $i < 12; $i++) {
            $time = strtotime("first day of -$i months");
            $val = date('Y-m', $time);
            $label = date('F Y', $time);
            $bulanindo = ['January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'];
            $label = strtr($label, $bulanindo);
            $selected = ($filter_bulan == $val) ? 'selected' : '';
            echo "<option value='$val' $selected>$label</option>";
        }
        ?>
    </select>
    <select name="filter_jenis" class="border rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="semua" <?= ($filter_jenis == 'semua' || empty($filter_jenis)) ? 'selected' : '' ?>>Semua Jenis</option>
        <option value="pemasukan" <?= $filter_jenis == 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
        <option value="pengeluaran" <?= $filter_jenis == 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
    </select>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 w-full sm:w-auto font-bold shadow-sm transition">
        <i class="fas fa-filter mr-1"></i> Terapkan Filter
    </button>
</form>

<div class="bg-white p-6 rounded-lg shadow overflow-x-auto">
    <h3 class="font-bold mb-4">Riwayat Transaksi</h3>
    <table class="w-full text-left min-w-[700px]">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2">Nama</th>
                <th class="p-2">Tanggal</th>
                <th class="p-2">Kategori</th>
                <th class="p-2">Jenis</th>
                <th class="p-2">Jumlah</th>
                <th class="p-2">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $transaksi->fetch_assoc()): ?>
                <tr class="border-t">
                    <?php 
                        $nama_warga = '-';
                        $kategori = '-';
                        $keterangan_tampil = $row['keterangan'];
                        
                        $parts = explode(' - ', $row['keterangan'], 3);
                        if (count($parts) >= 2) {
                            $nama_warga = $parts[0];
                            $kategori = $parts[1];
                            $keterangan_tampil = isset($parts[2]) ? $parts[2] : '-';
                        }
                    ?>
                    <td class="p-2"><?= htmlspecialchars($nama_warga) ?></td>
                    <td class="p-2"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                    <td class="p-2"><?= htmlspecialchars($kategori) ?></td>
                    <td class="p-2">
                        <?php if ($row['jenis'] == 'pemasukan'): ?>
                            <span class="bg-green-100 text-green-600 px-2 py-1 rounded">Pemasukan</span>
                        <?php else: ?>
                            <span class="bg-red-100 text-red-600 px-2 py-1 rounded">Pengeluaran</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-2 font-bold <?= $row['jenis']=='pemasukan'?'text-green-600': 'text-red-600' ?>">
                        <?= $row['jenis']=='pemasukan' ? '+ ' : '- ' ?>Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?>
                    </td>
                    <td class="p-2"><?= htmlspecialchars($keterangan_tampil) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

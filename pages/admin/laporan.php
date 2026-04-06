<?php
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
$query_laporan = "SELECT * FROM keuangan $whereClause ORDER BY tanggal DESC";
$stmt_laporan = $conn->prepare($query_laporan);
if (!empty($params)) {
    $stmt_laporan->bind_param($types, ...$params);
}
$stmt_laporan->execute();
$laporan = $stmt_laporan->get_result();
?>
<h2 class="text-2xl font-bold mb-4">Laporan</h2>
<p class="mb-6 text-gray-600">Generate laporan keuangan RT</p>

<!-- Filter Bar -->
<form method="GET" action="dashboard_admin.php" class="flex flex-col sm:flex-row sm:space-x-4 space-y-2 sm:space-y-0 mb-6 w-full">
    <input type="hidden" name="page" value="laporan">
    <select name="filter_bulan" class="border px-3 py-2 rounded-lg w-full sm:w-auto bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
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
    <select name="filter_jenis" class="border px-3 py-2 rounded-lg w-full sm:w-auto bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
        <option value="semua" <?= ($filter_jenis == 'semua' || empty($filter_jenis)) ? 'selected' : '' ?>>Semua Jenis</option>
        <option value="pemasukan" <?= $filter_jenis == 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
        <option value="pengeluaran" <?= $filter_jenis == 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
    </select>
    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg w-full sm:w-auto hover:bg-green-700 transition font-bold shadow-sm">
        <i class="fas fa-filter mr-1"></i> Terapkan Filter
    </button>
</form>

<!-- Table -->
<div class="bg-white shadow rounded-xl overflow-x-auto mb-6">
    <table class="w-full text-left min-w-[600px]">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-6 py-3">Tanggal</th>
                <th class="px-6 py-3">Keterangan</th>
                <th class="px-6 py-3">Jenis</th>
                <th class="px-6 py-3">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($laporan->num_rows > 0): ?>
                <?php while ($row = $laporan->fetch_assoc()): ?>
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
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr class="border-t">
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Belum ada data keuangan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Export Buttons -->
<div class="flex flex-col sm:flex-row sm:space-x-4 space-y-2 sm:space-y-0 w-full">
    <a href="exports/laporan_pdf.php?filter_jenis=<?= urlencode($filter_jenis) ?>&filter_bulan=<?= urlencode($filter_bulan) ?>" target="_blank" class="bg-red-600 text-white px-4 py-2 rounded-lg w-full sm:w-auto text-center">
        <i class="fas fa-file-pdf mr-2"></i> Export PDF
    </a>
    <a href="exports/laporan_excel.php" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded-lg w-full sm:w-auto text-center">
        <i class="fas fa-file-excel mr-2"></i> Export Excel
    </a>
</div>

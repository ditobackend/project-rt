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
    $parts = explode('-', $filter_bulan);
    if (count($parts) == 2) {
        $tahun = $parts[0];
        $bulan = $parts[1];
        $where[] = "MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
        $params[] = $bulan;
        $params[] = $tahun;
        $types .= 'ii';
    }
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

<div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4 text-left">
    <div>
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Laporan Keuangan</h2>
        <p class="text-secondary-500 mt-1">Audit transaksi dan ekspor laporan intelijen keuangan.</p>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="exports/laporan_pdf.php?filter_jenis=<?= urlencode($filter_jenis) ?>&filter_bulan=<?= urlencode($filter_bulan) ?>"
            target="_blank"
            class="inline-flex items-center px-6 py-3 bg-orange-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-orange-700 transition-all shadow-lg shadow-red-500/20 active:scale-95">
            <i class="fas fa-file-pdf mr-2"></i> Ekspor PDF
        </a>
        <a href="exports/laporan_excel.php" target="_blank"
            class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-green-700 transition-all shadow-lg shadow-green-500/20 active:scale-95">
            <i class="fas fa-file-excel mr-2"></i> Ekspor Excel
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="bg-white p-6 rounded-[2rem] shadow-sm border border-secondary-100 mb-8">
    <form method="GET" action="dashboard_admin.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <input type="hidden" name="page" value="laporan">

        <div class="md:col-span-2">
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Rentang
                Waktu</label>
            <select name="filter_bulan"
                class="w-full px-5 py-3 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium">
                <option value="semua" <?= ($filter_bulan == 'semua' || empty($filter_bulan)) ? 'selected' : '' ?>>Seluruh
                    Riwayat</option>
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
        </div>

        <div>
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Jenis
                Transaksi</label>
            <select name="filter_jenis"
                class="w-full px-5 py-3 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium">
                <option value="semua" <?= ($filter_jenis == 'semua' || empty($filter_jenis)) ? 'selected' : '' ?>>Semua
                    Jenis</option>
                <option value="pemasukan" <?= $filter_jenis == 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                <option value="pengeluaran" <?= $filter_jenis == 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
            </select>
        </div>

        <button type="submit"
            class="w-full px-6 py-3 bg-secondary-900 text-white font-black rounded-2xl hover:bg-secondary-800 transition-all active:scale-95">
            FILTER DATA
        </button>
    </form>
</div>

<!-- Table Container -->
<div class="bg-white rounded-[2.5rem] shadow-sm border border-secondary-100 overflow-hidden mb-8">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-secondary-50/50">
                    <th
                        class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">
                        Waktu Transaksi</th>
                    <th
                        class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">
                        Ringkasan Transaksi</th>
                    <th
                        class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">
                        Arus</th>
                    <th
                        class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100 text-right">
                        Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary-50">
                <?php if ($laporan->num_rows > 0): ?>
                    <?php while ($row = $laporan->fetch_assoc()):
                        $isInc = ($row['jenis'] == 'pemasukan');
                        $color = $isInc ? "green" : "red";
                        ?>
                        <tr class="hover:bg-primary-50/20 transition-all">
                            <td class="px-8 py-5">
                                <p class="text-sm font-bold text-secondary-900">
                                    <?php
                                    $bulan = ['Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agt', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'];
                                    echo strtr(date('d M Y', strtotime($row['tanggal'])), $bulan);
                                    ?>
                                </p>
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-sm text-secondary-600 font-medium"><?= htmlspecialchars($row['keterangan']) ?>
                                </p>
                            </td>
                            <td class="px-6 py-5">
                                <span
                                    class="inline-flex px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-<?= $color ?>-100 text-<?= $color ?>-600">
                                    <?= $row['jenis'] ?>
                                </span>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <p class="font-black text-<?= $color ?>-600">
                                    <?= $isInc ? '+' : '-' ?> Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?>
                                </p>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-8 py-20 text-center">
                            <i class="fas fa-folder-open text-6xl text-secondary-100 mb-4 block"></i>
                            <p class="text-secondary-400 font-bold tracking-tight">Tidak ada data tersedia untuk laporan
                                ini.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
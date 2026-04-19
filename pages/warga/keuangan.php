<?php
require_once __DIR__ . '/../../config/database.php';

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
    $parts = explode('-', $filter_bulan);
    if(count($parts) == 2) {
        $tahun = $parts[0];
        $bulan = $parts[1];
        $where[] = "MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
        $params[] = $bulan;
        $params[] = $tahun;
        $types .= 'ii';
    }
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

<div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Transparansi Dana</h2>
        <p class="text-secondary-500 mt-1">Laporan arus kas RT 06/08 secara terbuka dan akuntabel.</p>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary-100 flex items-center gap-4">
        <div class="w-12 h-12 bg-green-100 text-green-600 rounded-2xl flex items-center justify-center shadow-inner shrink-0">
            <i class="fas fa-arrow-down-long text-lg"></i>
        </div>
        <div>
            <p class="text-secondary-400 text-[10px] font-black uppercase tracking-widest">Total Masuk</p>
            <p class="text-xl font-black text-secondary-900">Rp <?= number_format($totalPemasukan, 0, ',', '.'); ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary-100 flex items-center gap-4">
        <div class="w-12 h-12 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center shadow-inner shrink-0">
            <i class="fas fa-arrow-up-long text-lg"></i>
        </div>
        <div>
            <p class="text-secondary-400 text-[10px] font-black uppercase tracking-widest">Total Keluar</p>
            <p class="text-xl font-black text-secondary-900">Rp <?= number_format($totalPengeluaran, 0, ',', '.'); ?></p>
        </div>
    </div>
    <div class="bg-primary-900 p-6 rounded-3xl shadow-xl flex items-center gap-4 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-20 h-20 bg-primary-500/20 rounded-full group-hover:scale-150 transition-transform"></div>
        <div class="w-12 h-12 bg-primary-500 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-primary-500/30 shrink-0 relative z-10">
            <i class="fas fa-wallet text-lg"></i>
        </div>
        <div class="relative z-10">
            <p class="text-secondary-300 text-[10px] font-black uppercase tracking-widest">Saldo Kas RT</p>
            <p class="text-xl font-black text-white">Rp <?= number_format($saldoAkhir, 0, ',', '.'); ?></p>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="bg-white p-6 rounded-[2rem] shadow-sm border border-secondary-100 mb-8">
    <form method="GET" action="dashboard_warga.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <input type="hidden" name="page" value="keuangan">
        
        <div>
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Jenis Dana</label>
            <select name="filter_jenis" class="w-full px-4 py-3 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium">
                <option value="semua" <?= ($filter_jenis == 'semua' || empty($filter_jenis)) ? 'selected' : '' ?>>Semua Transaksi</option>
                <option value="pemasukan" <?= $filter_jenis == 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                <option value="pengeluaran" <?= $filter_jenis == 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Periode</label>
            <select name="filter_bulan" class="w-full px-4 py-3 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium">
                <option value="semua" <?= ($filter_bulan == 'semua' || empty($filter_bulan)) ? 'selected' : '' ?>>Seluruh Riwayat</option>
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

        <button type="submit" class="w-full px-6 py-3 bg-secondary-900 text-white font-black rounded-2xl hover:bg-secondary-800 transition-all active:scale-95">
            FILTER DATA
        </button>
    </form>
</div>

<!-- Table Container -->
<div class="bg-white rounded-[2rem] shadow-sm border border-secondary-100 overflow-hidden">
    <div class="overflow-x-auto max-h-[500px] overflow-y-auto custom-scrollbar">
        <table class="w-full text-left border-separate border-spacing-0">
            <thead class="sticky top-0 z-10">
                <tr class="bg-secondary-50">
                    <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100">Nama/Subjek</th>
                    <th class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100">Tanggal</th>
                    <th class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100 text-right">Jumlah</th>
                    <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary-50">
                <?php if($transaksi->num_rows > 0): ?>
                    <?php while ($row = $transaksi->fetch_assoc()): 
                        $isInc = ($row['jenis'] == 'pemasukan');
                        $color = $isInc ? "green" : "red";
                        
                        $parts = explode(' - ', $row['keterangan'], 3);
                        $name = $parts[0] ?? '-';
                        $cat = $parts[1] ?? '-';
                        $note = $parts[2] ?? '-';
                    ?>
                    <tr class="group hover:bg-primary-50/20 transition-all">
                        <td class="px-8 py-5">
                            <p class="font-bold text-secondary-900 leading-tight"><?= htmlspecialchars($name) ?></p>
                            <span class="text-[10px] font-black text-secondary-400 uppercase tracking-widest"><?= htmlspecialchars($cat) ?></span>
                        </td>
                        <td class="px-6 py-5">
                            <p class="text-sm font-medium text-secondary-500">
                                <?php
                                $bulanIndoShort = ['Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agt', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'];
                                echo strtr(date('d M Y', strtotime($row['tanggal'])), $bulanIndoShort);
                                ?>
                            </p>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <p class="font-black text-<?= $color ?>-600">
                                <?= $isInc ? '+' : '-' ?> Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?>
                            </p>
                        </td>
                        <td class="px-8 py-5">
                            <p class="text-xs text-secondary-400 italic line-clamp-1"><?= htmlspecialchars($note) ?></p>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-8 py-20 text-center text-secondary-400 font-bold">
                            Tidak ada catatan keuangan ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

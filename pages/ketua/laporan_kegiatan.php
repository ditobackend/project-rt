<?php
$filter_status = $_GET['filter_status'] ?? 'semua';
$filter_bulan = $_GET['filter_bulan'] ?? 'semua';
$where = [];
$params = [];
$types = '';

if ($filter_status != 'semua') {
    $where[] = "k.status_persetujuan = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($filter_bulan != 'semua') {
    $parts = explode('-', $filter_bulan);
    if (count($parts) == 2) {
        $tahun = $parts[0];
        $bulan = $parts[1];
        $where[] = "MONTH(k.tanggal) = ? AND YEAR(k.tanggal) = ?";
        $params[] = $bulan;
        $params[] = $tahun;
        $types .= 'ii';
    }
}

$whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
$query_kegiatan = "
    SELECT k.*, u.nama as pengusul 
    FROM kegiatan k 
    LEFT JOIN users u ON k.diajukan_oleh = u.id 
    $whereClause 
    ORDER BY k.tanggal DESC
";

$stmt_kegiatan = $conn->prepare($query_kegiatan);
if (!empty($params)) {
    $stmt_kegiatan->bind_param($types, ...$params);
}
$stmt_kegiatan->execute();
$laporan = $stmt_kegiatan->get_result();
?>

<div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4 text-left">
    <div>
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Laporan Kegiatan</h2>
        <p class="text-secondary-500 mt-1">Audit riwayat pengajuan kegiatan dan ekspor ke PDF.</p>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="exports/kegiatan_pdf.php?filter_status=<?= urlencode($filter_status) ?>&filter_bulan=<?= urlencode($filter_bulan) ?>"
            target="_blank"
            class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/20 active:scale-95">
            <i class="fas fa-file-pdf mr-2"></i> Ekspor PDF
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="bg-white p-6 rounded-[2rem] shadow-sm border border-secondary-100 mb-8">
    <form method="GET" action="dashboard_ketua.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <input type="hidden" name="page" value="laporan_kegiatan">

        <div class="md:col-span-2">
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Rentang Waktu</label>
            <select name="filter_bulan" class="w-full px-5 py-3 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium">
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

        <div>
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Status Persetujuan</label>
            <select name="filter_status" class="w-full px-5 py-3 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium">
                <option value="semua" <?= ($filter_status == 'semua') ? 'selected' : '' ?>>Semua Status</option>
                <option value="disetujui" <?= $filter_status == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                <option value="ditolak" <?= $filter_status == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
            </select>
        </div>

        <button type="submit" class="w-full px-6 py-3 bg-secondary-900 text-white font-black rounded-2xl hover:bg-secondary-800 transition-all active:scale-95">
            FILTER DATA
        </button>
    </form>
</div>

<!-- Table Container -->
<div class="bg-white rounded-[2.5rem] shadow-sm border border-secondary-100 overflow-hidden mb-8">
    <div class="overflow-x-auto max-h-[550px] overflow-y-auto custom-scrollbar">
        <table class="w-full text-left border-separate border-spacing-0">
            <thead class="sticky top-0 z-10">
                <tr class="bg-secondary-50">
                    <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">Jadwal</th>
                    <th class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">Nama Kegiatan</th>
                    <th class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">Pengusul</th>
                    <th class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">Tempat</th>
                    <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary-50">
                <?php if ($laporan->num_rows > 0): ?>
                    <?php while ($row = $laporan->fetch_assoc()):
                        // Status styling
                        $status = strtolower($row['status_persetujuan'] ?? 'pending');
                        if ($status === 'disetujui') { $color = 'green'; }
                        elseif ($status === 'ditolak') { $color = 'red'; }
                        else { $color = 'amber'; $status = 'pending'; }
                    ?>
                        <tr class="hover:bg-primary-50/20 transition-all">
                            <td class="px-8 py-5">
                                <p class="text-sm font-bold text-secondary-900">
                                    <?php
                                    $bulanArr = ['Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agt', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'];
                                    echo strtr(date('d M Y', strtotime($row['tanggal'])), $bulanArr);
                                    ?>
                                </p>
                                <p class="text-xs text-secondary-500 mt-0.5">
                                    <?= date('H:i', strtotime($row['jam_mulai'])) ?> - <?= date('H:i', strtotime($row['jam_selesai'])) ?>
                                </p>
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-sm font-bold text-secondary-800"><?= htmlspecialchars($row['judul']) ?></p>
                            </td>
                            <td class="px-6 py-5 text-sm text-secondary-600 font-medium">
                                <?= htmlspecialchars($row['pengusul'] ?? 'Tidak diketahui') ?>
                            </td>
                            <td class="px-6 py-5 text-sm text-secondary-600">
                                <?= htmlspecialchars($row['tempat']) ?>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="inline-flex px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-<?= $color ?>-100 text-<?= $color ?>-700">
                                    <?= $status ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-8 py-20 text-center">
                            <i class="fas fa-folder-open text-6xl text-secondary-100 mb-4 block"></i>
                            <p class="text-secondary-400 font-bold tracking-tight">Tidak ada pengajuan kegiatan.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

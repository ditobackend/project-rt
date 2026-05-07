<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

// === FILTER ===
$statusFilter    = $_GET['status']      ?? '';
$bulanTahunFilter = $_GET['bulan_tahun'] ?? '';
$cariFilter      = $_GET['cari']        ?? '';

$where  = [];
$params = [];
$types  = '';

// Ambil daftar periode tersedia
$periodsQuery   = $conn->query("SELECT DATE_FORMAT(tanggal, '%m-%Y') as period FROM kegiatan GROUP BY period ORDER BY MIN(tanggal) DESC");
$availablePeriods = [];
$bulanIndo = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
while ($p = $periodsQuery->fetch_assoc()) {
    $parts = explode('-', $p['period']);
    $availablePeriods[] = ['value' => $p['period'], 'label' => $bulanIndo[(int)$parts[0]] . ' ' . $parts[1]];
}

// Cari
if (!empty($cariFilter)) {
    $where[]  = '(judul LIKE ? OR deskripsi LIKE ?)';
    $keyword  = '%' . $cariFilter . '%';
    $params[] = $keyword;
    $params[] = $keyword;
    $types   .= 'ss';
}

// Filter status waktu
if (empty($statusFilter) && empty($bulanTahunFilter)) {
    $where[] = "CONCAT(tanggal, ' ', jam_selesai) >= NOW()";
} else {
    if (!empty($statusFilter)) {
        if ($statusFilter === 'akan_datang')
            $where[] = "NOW() < CONCAT(tanggal, ' ', COALESCE(jam_mulai, '00:00:00'))";
        elseif ($statusFilter === 'berlangsung')
            $where[] = "NOW() >= CONCAT(tanggal, ' ', COALESCE(jam_mulai, '00:00:00')) AND NOW() <= CONCAT(tanggal, ' ', COALESCE(jam_selesai, '23:59:59'))";
        elseif ($statusFilter === 'selesai')
            $where[] = "CONCAT(tanggal, ' ', COALESCE(jam_selesai, '23:59:59')) < NOW()";
    }
}

if (!empty($bulanTahunFilter)) {
    $where[]  = "DATE_FORMAT(tanggal, '%m-%Y') = ?";
    $params[] = $bulanTahunFilter;
    $types   .= 's';
}

// Admin hanya lihat yang sudah disetujui oleh Ketua RT
$where[] = "(status_persetujuan = 'disetujui' OR diajukan_oleh IS NULL)";

$sql = "SELECT k.*, u.nama as pengusul FROM kegiatan k LEFT JOIN users u ON k.diajukan_oleh = u.id";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= " ORDER BY tanggal DESC, COALESCE(jam_mulai, '00:00:00') ASC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Info Kegiatan</h2>
        <p class="text-secondary-500 mt-1">Daftar kegiatan warga RT 06 yang telah divalidasi oleh Ketua RT.</p>
    </div>
    <!-- Badge info read-only -->
    <div class="flex items-center gap-2 px-5 py-3 bg-blue-50 border border-blue-100 rounded-2xl text-blue-600 text-xs font-bold">
        <i class="fas fa-shield-alt"></i>
        <span>Dikelola oleh Ketua RT</span>
    </div>
</div>

<!-- Filter -->
<div class="bg-white p-7 rounded-[2.5rem] shadow-sm border border-secondary-100 mb-8 overflow-hidden relative">
    <div class="absolute top-0 right-0 w-32 h-32 bg-primary-500/5 blur-3xl rounded-full"></div>

    <form id="filterForm" method="GET" action="dashboard_admin.php" class="space-y-6 relative z-10">
        <input type="hidden" name="page" value="kegiatan">
        <input type="hidden" name="status" id="statusInput" value="<?= htmlspecialchars($statusFilter) ?>">

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-end">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-3 ml-1 flex items-center">
                    <i class="fas fa-search mr-2 text-primary-500"></i> Cari Kegiatan
                </label>
                <div class="relative group">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-secondary-300 group-focus-within:text-primary-500 transition-colors"></i>
                    <input type="text" name="cari" placeholder="Cari judul atau deskripsi..."
                        value="<?= htmlspecialchars($cariFilter) ?>"
                        class="w-full pl-11 pr-4 py-3.5 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium placeholder-secondary-300 transition-all hover:bg-white">
                </div>
            </div>

            <!-- Period Selector -->
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-3 ml-1 flex items-center">
                    <i class="fas fa-calendar-alt mr-2 text-primary-500"></i> Pilih Periode
                </label>
                <div class="relative group">
                    <i class="far fa-calendar absolute left-5 top-1/2 -translate-y-1/2 text-secondary-300 transition-colors"></i>
                    <select name="bulan_tahun" onchange="this.form.submit()"
                        class="w-full pl-12 pr-12 py-3.5 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-bold appearance-none cursor-pointer transition-all hover:bg-white">
                        <option value="">Semua Periode</option>
                        <?php foreach ($availablePeriods as $period): ?>
                        <option value="<?= $period['value'] ?>" <?= ($bulanTahunFilter == $period['value']) ? 'selected' : '' ?>>
                            <?= $period['label'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-secondary-300 text-[10px] pointer-events-none"></i>
                </div>
            </div>
        </div>

        <!-- Status Chips -->
        <div class="w-full pt-2">
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-4 ml-1 flex items-center">
                <i class="fas fa-layer-group mr-2 text-primary-500"></i> Status Kegiatan
            </label>
            <div class="flex flex-wrap gap-2">
                <?php
                $statuses = [
                    'akan_datang' => ['label' => 'Mendatang',   'icon' => 'fa-clock'],
                    'berlangsung' => ['label' => 'Berlangsung',  'icon' => 'fa-play-circle'],
                    'selesai'     => ['label' => 'Selesai',      'icon' => 'fa-check-circle'],
                ];
                foreach ($statuses as $val => $data):
                    $active = ($statusFilter === $val);
                ?>
                <button type="button" onclick="setStatus('<?= $val ?>')"
                    class="group flex items-center gap-2 px-6 py-3 rounded-2xl text-xs font-black transition-all duration-300
                           <?= $active ? 'bg-primary-600 text-white shadow-lg shadow-primary-500/30' : 'bg-secondary-50 text-secondary-500 hover:bg-white hover:shadow-md hover:text-primary-600' ?>">
                    <i class="fas <?= $data['icon'] ?> text-sm <?= $active ? 'text-white' : 'text-secondary-300 group-hover:text-primary-500' ?>"></i>
                    <?= $data['label'] ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-[2rem] shadow-sm border border-secondary-100 overflow-hidden">
    <div class="overflow-x-auto max-h-[550px] overflow-y-auto custom-scrollbar">
        <table class="w-full text-left border-separate border-spacing-0">
            <thead class="sticky top-0 z-10">
                <tr class="bg-secondary-50">
                    <th class="px-8 py-5 text-xs font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">Detail Kegiatan</th>
                    <th class="px-8 py-5 text-xs font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">Jadwal</th>
                    <th class="px-8 py-5 text-xs font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">Tempat</th>
                    <th class="px-8 py-5 text-xs font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary-50">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="group hover:bg-primary-50/20 transition-all duration-300">
                        <!-- Detail -->
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-primary-100 text-primary-600 flex items-center justify-center shrink-0 shadow-inner group-hover:scale-110 transition-transform">
                                    <i class="fas fa-calendar-alt text-xl"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-secondary-900 leading-tight mb-1"><?= htmlspecialchars($row['judul']) ?></p>
                                    <p class="text-xs text-secondary-400 line-clamp-1 max-w-[200px]"><?= htmlspecialchars($row['deskripsi']) ?></p>
                                    <?php if (!empty($row['pengusul'])): ?>
                                    <p class="text-[9px] font-black text-primary-500 uppercase tracking-widest mt-2">
                                        <i class="fas fa-user-edit mr-1"></i> Oleh: <?= htmlspecialchars($row['pengusul']) ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>

                        <!-- Jadwal -->
                        <td class="px-8 py-6">
                            <div class="flex flex-col gap-1">
                                <span class="text-sm font-bold text-secondary-700 flex items-center">
                                    <i class="far fa-calendar-check mr-2 text-primary-500"></i>
                                    <?php
                                    $bln = ['Jan'=>'Jan','Feb'=>'Feb','Mar'=>'Mar','Apr'=>'Apr','May'=>'Mei','Jun'=>'Jun','Jul'=>'Jul','Aug'=>'Agt','Sep'=>'Sep','Oct'=>'Okt','Nov'=>'Nov','Dec'=>'Des'];
                                    echo strtr(date('d M Y', strtotime($row['tanggal'])), $bln);
                                    ?>
                                </span>
                                <span class="text-xs font-medium text-secondary-400 flex items-center">
                                    <i class="far fa-clock mr-2 text-secondary-300"></i>
                                    <?= substr($row['jam_mulai'] ?? '00:00', 0, 5) ?> - <?= substr($row['jam_selesai'] ?? '00:00', 0, 5) ?> WIB
                                </span>
                            </div>
                        </td>

                        <!-- Tempat -->
                        <td class="px-8 py-6">
                            <span class="text-sm text-secondary-500 flex items-center gap-2">
                                <i class="fas fa-map-marker-alt text-secondary-300"></i>
                                <?= htmlspecialchars($row['tempat'] ?? '-') ?>
                            </span>
                        </td>

                        <!-- Status -->
                        <td class="px-8 py-6">
                            <?php
                            date_default_timezone_set('Asia/Jakarta');
                            $now        = time();
                            $start_time = strtotime($row['tanggal'] . ' ' . ($row['jam_mulai']  ?? '00:00:00'));
                            $end_time   = strtotime($row['tanggal'] . ' ' . ($row['jam_selesai'] ?? '23:59:59'));
                            $isLive     = ($now >= $start_time && $now <= $end_time);
                            $isFinished = ($now > $end_time);
                            $color      = $isLive ? 'green' : ($isFinished ? 'secondary' : 'blue');
                            $label      = $isLive ? 'Berlangsung' : ($isFinished ? 'Selesai' : 'Mendatang');
                            ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-<?= $color ?>-100 text-<?= $color ?>-600 ring-4 ring-<?= $color ?>-50">
                                <span class="w-1.5 h-1.5 rounded-full bg-<?= $color ?>-600 mr-2 <?= $isLive ? 'animate-pulse' : '' ?>"></span>
                                <?= $label ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-8 py-20 text-center">
                            <i class="fas fa-calendar-xmark text-6xl text-secondary-100 mb-4 block"></i>
                            <p class="text-secondary-400 font-bold">Tidak ada kegiatan yang ditemukan.</p>
                            <p class="text-secondary-300 text-sm mt-1">Kegiatan akan muncul setelah divalidasi oleh Ketua RT.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function setStatus(val) {
    document.getElementById('statusInput').value = val;
    document.getElementById('filterForm').submit();
}
</script>
<?php
require_once __DIR__ . '/../../config/database.php';

// Ambil filter dari GET
$where = [];
$params = [];
$types = "";

$statusFilter = $_GET['status'] ?? "";
$bulanFilter = $_GET['bulan'] ?? "";

// Sembunyikan otomatis jika sesi jam sudah melewati waktu real-time
$where[] = "CONCAT(tanggal, ' ', jam_selesai) >= NOW()";

if (!empty($statusFilter)) {
    if ($statusFilter == 'akan_datang') {
        $where[] = "NOW() < CONCAT(tanggal, ' ', jam_mulai)";
    } elseif ($statusFilter == 'berlangsung') {
        $where[] = "NOW() >= CONCAT(tanggal, ' ', jam_mulai) AND NOW() <= CONCAT(tanggal, ' ', jam_selesai)";
    }
}

if (!empty($bulanFilter)) {
    $where[] = "MONTH(tanggal) = ?";
    $params[] = $bulanFilter;
    $types .= "i";
}

$sql = "SELECT * FROM kegiatan";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY tanggal ASC, jam_mulai ASC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Agenda Kegiatan</h2>
        <p class="text-secondary-500 mt-1">Jadwal aktivitas dan acara warga RT 06/08.</p>
    </div>
</div>

<!-- Filter Box -->
<div class="bg-white p-6 rounded-[2rem] shadow-sm border border-secondary-100 mb-8">
    <form method="GET" action="dashboard_warga.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <input type="hidden" name="page" value="kegiatan">
        
        <div class="md:col-span-1">
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Status</label>
            <select name="status" class="w-full px-4 py-3 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium appearance-none transition-all">
                <option value="">Semua Status</option>
                <option value="akan_datang" <?= ($statusFilter=="akan_datang") ? 'selected' : '' ?>>Akan Datang</option>
                <option value="berlangsung" <?= ($statusFilter=="berlangsung") ? 'selected' : '' ?>>Sedang Berlangsung</option>
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Bulan</label>
            <select name="bulan" class="w-full px-4 py-3 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium appearance-none transition-all">
                <option value="">Semua Bulan</option>
                <?php
                $bulanIndo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                foreach ($bulanIndo as $m => $name) {
                    echo "<option value='$m' ".(($bulanFilter==$m)?'selected':'').">$name</option>";
                }
                ?>
            </select>
        </div>

        <button type="submit" class="w-full px-6 py-3 bg-secondary-900 text-white font-black rounded-2xl hover:bg-secondary-800 transition-all active:scale-95">
            CARI JADWAL
        </button>
    </form>
</div>

<!-- Timeline / List Kegiatan -->
<div class="space-y-6">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): 
            date_default_timezone_set('Asia/Jakarta');
            $now = time();
            $start_time = strtotime($row['tanggal'] . ' ' . $row['jam_mulai']);
            $end_time = strtotime($row['tanggal'] . ' ' . $row['jam_selesai']);
            
            $isLive = ($now >= $start_time && $now <= $end_time);
            $color = $isLive ? "green" : "blue";
            $statusLabel = $isLive ? "Sedang Berlangsung" : "Terjadwal";
        ?>
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-secondary-100 hover:shadow-md transition-all group relative overflow-hidden">
            <div class="absolute top-0 left-0 w-2 h-full bg-<?= $color ?>-500"></div>
            
            <div class="flex flex-col md:flex-row gap-8 items-start md:items-center">
                <!-- Date Box -->
                <div class="bg-secondary-50 w-20 h-20 rounded-3xl flex flex-col items-center justify-center text-secondary-900 shrink-0 border border-secondary-100 group-hover:bg-<?= $color ?>-600 group-hover:text-white transition-all">
                    <span class="text-xs font-black uppercase tracking-tighter"><?= date("M", strtotime($row['tanggal'])) ?></span>
                    <span class="text-3xl font-black leading-none"><?= date("d", strtotime($row['tanggal'])) ?></span>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-3 mb-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-<?= $color ?>-100 text-<?= $color ?>-600">
                            <?= $statusLabel ?>
                        </span>
                        <?php if($isLive): ?>
                            <span class="flex h-2 w-2 rounded-full bg-green-500 animate-ping"></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-2xl font-black text-secondary-900 group-hover:text-<?= $color ?>-600 transition-colors mb-2"><?= htmlspecialchars($row['judul']) ?></h3>
                    <p class="text-secondary-500 leading-relaxed max-w-2xl"><?= htmlspecialchars($row['deskripsi']) ?></p>
                </div>

                <!-- Details List -->
                <div class="w-full md:w-auto flex flex-col gap-3 py-4 md:py-0 border-t md:border-t-0 md:border-l border-secondary-100 md:pl-8">
                    <div class="flex items-center gap-3 text-sm text-secondary-600 font-bold">
                        <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center">
                            <i class="far fa-clock"></i>
                        </div>
                        <?= substr($row['jam_mulai'],0,5) ?> - <?= substr($row['jam_selesai'],0,5) ?> WIB
                    </div>
                    <div class="flex items-center gap-3 text-sm text-secondary-600 font-bold">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                            <i class="far fa-user"></i>
                        </div>
                        <?= htmlspecialchars($row['penyelenggara'] ?? 'RT 06') ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="bg-white p-20 rounded-[3rem] shadow-sm border border-secondary-100 text-center">
            <i class="fas fa-calendar-day text-7xl text-secondary-100 mb-6 block"></i>
            <h3 class="text-xl font-bold text-secondary-900 mb-2">Belum Ada Agenda</h3>
            <p class="text-secondary-400">Silakan cek kembali nanti untuk update kegiatan RT terbaru.</p>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../../config/database.php';

// Ambil filter dari GET
$where = [];
$params = [];
$types = "";

$statusFilter = $_GET['status'] ?? "";
$bulanTahunFilter = $_GET['bulan_tahun'] ?? "";

// Ambil daftar bulan-tahun yang tersedia di database (termasuk yang sudah lewat)
$periodsQuery = $conn->query("SELECT DATE_FORMAT(tanggal, '%m-%Y') as period FROM kegiatan GROUP BY period ORDER BY MIN(tanggal) DESC");
$availablePeriods = [];
$bulanIndo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
while($p = $periodsQuery->fetch_assoc()) {
    $parts = explode('-', $p['period']);
    $m = (int)$parts[0];
    $y = $parts[1];
    $availablePeriods[] = [
        'value' => $p['period'],
        'label' => $bulanIndo[$m] . " " . $y
    ];
}

// Proses Pengajuan Kegiatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_kegiatan'])) {
    $judul = $_POST['judul'];
    $deskripsi = $_POST['deskripsi'];
    $tanggal = $_POST['tanggal'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $tempat = $_POST['tempat'];
    $user_id = $_SESSION['user_id'];
    
    $stmt_ins = $conn->prepare("INSERT INTO kegiatan (judul, deskripsi, tanggal, jam_mulai, jam_selesai, tempat, diajukan_oleh, status_persetujuan) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt_ins->bind_param("ssssssi", $judul, $deskripsi, $tanggal, $jam_mulai, $jam_selesai, $tempat, $user_id);
    
    if ($stmt_ins->execute()) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Pengajuan kegiatan telah dikirim ke Ketua RT.',
                    icon: 'success',
                    confirmButtonColor: '#4f46e5',
                    customClass: {
                        popup: 'rounded-[2rem] shadow-xl border-0',
                        confirmButton: 'rounded-xl px-6 py-2.5 font-bold'
                    }
                }).then(() => {
                    window.location.href='?page=kegiatan&status=pengajuan_saya';
                });
            });
        </script>";
    }
}

// Logic Filter
$current_user_id = $_SESSION['user_id'];

if ($statusFilter == 'pengajuan_saya') {
    $where[] = "diajukan_oleh = ?";
    $params[] = $current_user_id;
    $types .= "i";
} else {
    // Publik hanya melihat yang disetujui (atau kegiatan RT yang tidak punya pengusul)
    $where[] = "(status_persetujuan = 'disetujui' OR diajukan_oleh IS NULL)";
    
    if (empty($statusFilter) && empty($bulanTahunFilter)) {
        $where[] = "CONCAT(tanggal, ' ', COALESCE(jam_selesai, '23:59:59')) >= NOW()";
    } else if (!empty($statusFilter)) {
        if ($statusFilter == 'akan_datang') {
            $where[] = "NOW() < CONCAT(tanggal, ' ', COALESCE(jam_mulai, '00:00:00'))";
        } elseif ($statusFilter == 'berlangsung') {
            $where[] = "NOW() >= CONCAT(tanggal, ' ', COALESCE(jam_mulai, '00:00:00')) AND NOW() <= CONCAT(tanggal, ' ', COALESCE(jam_selesai, '23:59:59'))";
        } elseif ($statusFilter == 'selesai') {
            $where[] = "CONCAT(tanggal, ' ', COALESCE(jam_selesai, '23:59:59')) < NOW()";
        }
    }
}

if (!empty($bulanTahunFilter)) {
    $where[] = "DATE_FORMAT(tanggal, '%m-%Y') = ?";
    $params[] = $bulanTahunFilter;
    $types .= "s";
}

$sql = "SELECT * FROM kegiatan";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY tanggal DESC, COALESCE(jam_mulai, '00:00:00') ASC";

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
    
    <button onclick="showProposalModal()" class="inline-flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-bold py-3.5 px-6 rounded-2xl shadow-lg shadow-primary-500/20 transition-all active:scale-95 text-sm uppercase tracking-widest">
        <i class="fas fa-plus-circle"></i> Ajukan Kegiatan
    </button>
</div>

<!-- Filter Box -->
<div class="bg-white p-7 rounded-[2.5rem] shadow-sm border border-secondary-100 mb-8 overflow-hidden relative">
    <div class="absolute top-0 right-0 w-32 h-32 bg-primary-500/5 blur-3xl rounded-full"></div>
    
    <form id="filterForm" method="GET" action="dashboard_warga.php" class="flex flex-col lg:flex-row gap-8 items-end relative z-10">
        <input type="hidden" name="page" value="kegiatan">
        <input type="hidden" name="status" id="statusInput" value="<?= $statusFilter ?>">
        
        <div class="flex-1 w-full">
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-4 ml-1 flex items-center">
                <i class="fas fa-layer-group mr-2 text-primary-500"></i>
                Status Kegiatan
            </label>
            <div class="flex flex-wrap gap-2">
                <?php
                $statuses = [
                    '' => ['label' => 'Semua', 'icon' => 'fa-list'],
                    'akan_datang' => ['label' => 'Mendatang', 'icon' => 'fa-clock'],
                    'berlangsung' => ['label' => 'Berlangsung', 'icon' => 'fa-play-circle'],
                    'selesai' => ['label' => 'Selesai', 'icon' => 'fa-check-circle'],
                    'pengajuan_saya' => ['label' => 'Pengajuan Saya', 'icon' => 'fa-user-clock']
                ];
                foreach ($statuses as $val => $data):
                    $active = ($statusFilter == $val);
                ?>
                <button type="button" onclick="setStatus('<?= $val ?>')" 
                    class="group flex items-center gap-2 px-6 py-3 rounded-2xl text-xs font-black transition-all duration-300 <?= $active ? 'bg-primary-600 text-white shadow-lg shadow-primary-500/30' : 'bg-secondary-50 text-secondary-500 hover:bg-white hover:shadow-md hover:text-primary-600' ?>">
                    <i class="fas <?= $data['icon'] ?> text-sm <?= $active ? 'text-white' : 'text-secondary-300 group-hover:text-primary-500' ?>"></i>
                    <?= $data['label'] ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="w-full lg:w-72">
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-4 ml-1 flex items-center">
                <i class="fas fa-calendar-alt mr-2 text-primary-500"></i>
                Pilih Periode
            </label>
            <div class="relative group">
                <i class="far fa-calendar absolute left-5 top-1/2 -translate-y-1/2 text-secondary-300 group-focus-within:text-primary-500 transition-colors"></i>
                <select name="bulan_tahun" onchange="this.form.submit()" 
                    class="w-full pl-12 pr-12 py-3.5 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-bold appearance-none cursor-pointer transition-all hover:bg-white hover:shadow-sm">
                    <option value="">Semua Periode</option>
                    <?php foreach ($availablePeriods as $period): ?>
                        <option value="<?= $period['value'] ?>" <?= ($bulanTahunFilter == $period['value']) ? 'selected' : '' ?>>
                            <?= $period['label'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-secondary-300 text-[10px] pointer-events-none group-hover:text-primary-500 transition-colors"></i>
            </div>
        </div>
    </form>
</div>

<script>
function setStatus(val) {
    document.getElementById('statusInput').value = val;
    document.getElementById('filterForm').submit();
}

function showProposalModal() {
    Swal.fire({
        title: '<div class="text-left"><h3 class="text-2xl font-black text-secondary-900 tracking-tight">Ajukan Kegiatan</h3><p class="text-secondary-500 text-xs font-medium uppercase tracking-widest mt-1">Lengkapi rencana kegiatan Anda</p></div>',
        html: `
            <form id="proposalForm" method="POST" class="text-left mt-6 space-y-5">
                <input type="hidden" name="ajukan_kegiatan" value="1">
                <div>
                    <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Judul Kegiatan</label>
                    <input type="text" name="judul" required class="w-full px-5 py-3.5 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium transition-all" placeholder="Contoh: Kerja Bakti Blok A">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Deskripsi Rencana</label>
                    <textarea name="deskripsi" required rows="3" class="w-full px-5 py-3.5 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium transition-all" placeholder="Jelaskan tujuan dan rencana detail kegiatan..."></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Tanggal</label>
                        <input type="date" name="tanggal" required class="w-full px-5 py-3.5 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Lokasi/Tempat</label>
                        <input type="text" name="tempat" required class="w-full px-5 py-3.5 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium transition-all" placeholder="Pos / Lapangan">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Jam Mulai</label>
                        <input type="time" name="jam_mulai" required class="w-full px-5 py-3.5 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Jam Selesai</label>
                        <input type="time" name="jam_selesai" required class="w-full px-5 py-3.5 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium transition-all">
                    </div>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Kirim Pengajuan',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#ef4444',
        customClass: {
            popup: 'rounded-[3rem] p-10 border-0',
            confirmButton: 'rounded-2xl px-8 py-4 font-black uppercase tracking-widest text-xs',
            cancelButton: 'rounded-2xl px-8 py-4 font-black uppercase tracking-widest text-xs text-white'
        },
        preConfirm: () => {
            const form = document.getElementById('proposalForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('proposalForm').submit();
        }
    });
}
</script>

<!-- Timeline / List Kegiatan -->
<div class="space-y-6">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): 
            date_default_timezone_set('Asia/Jakarta');
            $now = time();
            $start_time = strtotime($row['tanggal'] . ' ' . ($row['jam_mulai'] ?? '00:00:00'));
            $end_time = strtotime($row['tanggal'] . ' ' . ($row['jam_selesai'] ?? '23:59:59'));
            
            $isLive = ($now >= $start_time && $now <= $end_time);
            $isFinished = ($now > $end_time);
            
            $color = $isLive ? "green" : ($isFinished ? "secondary" : "blue");
            $statusLabel = $isLive ? "Berlangsung" : ($isFinished ? "Selesai" : "Mendatang");

            // Status Persetujuan Badge
            $approvalColor = "orange";
            $approvalText = "Menunggu Persetujuan";
            if($row['status_persetujuan'] == 'disetujui') { $approvalColor = "green"; $approvalText = "Telah Disetujui"; }
            elseif($row['status_persetujuan'] == 'ditolak') { $approvalColor = "red"; $approvalText = "Ditolak Ketua RT"; }
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
                        <?php if($statusFilter == 'pengajuan_saya'): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-<?= $approvalColor ?>-100 text-<?= $approvalColor ?>-600">
                                <i class="fas fa-info-circle mr-1"></i> <?= $approvalText ?>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-<?= $color ?>-100 text-<?= $color ?>-600">
                                <?= $statusLabel ?>
                            </span>
                        <?php endif; ?>
                        <?php if($isLive && $row['status_persetujuan'] != 'ditolak'): ?>
                            <span class="flex h-2 w-2 rounded-full bg-green-500 animate-ping"></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-2xl font-black text-secondary-900 group-hover:text-<?= $color ?>-600 transition-colors mb-2"><?= htmlspecialchars($row['judul']) ?></h3>
                    <p class="text-secondary-500 leading-relaxed max-w-2xl"><?= htmlspecialchars($row['deskripsi']) ?></p>
                </div>

                <!-- Details List -->
                <div class="w-full md:w-auto flex flex-col gap-3 py-4 md:py-0 border-t md:border-t-0 md:border-l border-secondary-100 md:pl-8">
                    <?php if(!empty($row['jam_mulai'])): ?>
                    <div class="flex items-center gap-3 text-sm text-secondary-600 font-bold">
                        <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center">
                            <i class="far fa-clock"></i>
                        </div>
                        <?= substr($row['jam_mulai'],0,5) ?> - <?= substr($row['jam_selesai'],0,5) ?> WIB
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center gap-3 text-sm text-secondary-600 font-bold">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <?= htmlspecialchars($row['tempat'] ?? 'RT 06') ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="bg-white p-20 rounded-[3rem] shadow-sm border border-secondary-100 text-center">
            <i class="fas fa-calendar-day text-7xl text-secondary-100 mb-6 block"></i>
            <h3 class="text-xl font-bold text-secondary-900 mb-2">Belum Ada Agenda</h3>
            <p class="text-secondary-400">Silakan cek kembali nanti atau ajukan kegiatan baru.</p>
        </div>
    <?php endif; ?>
</div>

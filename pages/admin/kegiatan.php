<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

// Tambah kegiatan
if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    if (isset($_POST['tambah'])) {
        $judul = $_POST['judul'];
        $deskripsi = $_POST['deskripsi'];
        $tanggal = $_POST['tanggal'];
        $jam_mulai = $_POST['jam_mulai'];
        $jam_selesai = $_POST['jam_selesai'];
        $penyelenggara = $_POST['penyelenggara'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("INSERT INTO kegiatan (judul, deskripsi, tanggal, jam_mulai, jam_selesai, penyelenggara, status) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssss", $judul, $deskripsi, $tanggal, $jam_mulai, $jam_selesai, $penyelenggara, $status);
        $stmt->execute();

        echo "<script>window.location.href='dashboard_admin.php?page=kegiatan';</script>";
    }

    // Update kegiatan
    if (isset($_POST['update'])) {
        $id = $_POST['id'];
        $judul = $_POST['judul'];
        $deskripsi = $_POST['deskripsi'];
        $tanggal = $_POST['tanggal'];
        $jam_mulai = $_POST['jam_mulai'];
        $jam_selesai = $_POST['jam_selesai'];
        $penyelenggara = $_POST['penyelenggara'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE kegiatan SET judul=?, deskripsi=?, tanggal=?, jam_mulai=?, jam_selesai=?, penyelenggara=?, status=? WHERE id=?");
        $stmt->bind_param("sssssssi", $judul, $deskripsi, $tanggal, $jam_mulai, $jam_selesai, $penyelenggara, $status, $id);
        $stmt->execute();

        echo "<script>window.location.href='dashboard_admin.php?page=kegiatan';</script>";
    }

    // Hapus kegiatan
    if (isset($_GET['hapus'])) {
        $id = $_GET['hapus'];
        $stmt = $conn->prepare("DELETE FROM kegiatan WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo "<script>window.location.href='dashboard_admin.php?page=kegiatan';</script>";
    }
}

$where = [];
$params = [];
$types = "";

$statusFilter = $_GET['status'] ?? "";
$bulanTahunFilter = $_GET['bulan_tahun'] ?? "";
$cariFilter = $_GET['cari'] ?? "";

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

// Logic Filter
if (!empty($cariFilter)) {
    $where[] = "(judul LIKE ? OR deskripsi LIKE ? OR penyelenggara LIKE ?)";
    $keyword = "%" . $cariFilter . "%";
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= "sss";
}

if (empty($statusFilter) && empty($bulanTahunFilter)) {
    // Default: Tampilkan yang aktif/mendatang
    $where[] = "CONCAT(tanggal, ' ', jam_selesai) >= NOW()";
} else {
    if (!empty($statusFilter)) {
        if ($statusFilter == 'akan_datang') {
            $where[] = "NOW() < CONCAT(tanggal, ' ', jam_mulai)";
        } elseif ($statusFilter == 'berlangsung') {
            $where[] = "NOW() >= CONCAT(tanggal, ' ', jam_mulai) AND NOW() <= CONCAT(tanggal, ' ', jam_selesai)";
        } elseif ($statusFilter == 'selesai') {
            $where[] = "CONCAT(tanggal, ' ', jam_selesai) < NOW()";
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
$sql .= " ORDER BY tanggal DESC, jam_mulai ASC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Manajemen Kegiatan</h2>
        <p class="text-secondary-500 mt-1">Atur dan pantau aktivitas warga RT. 06</p>
    </div>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        <button id="btnTambah"
            class="inline-flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-6 rounded-2xl shadow-lg shadow-primary-500/20 transition-all active:scale-95 text-sm uppercase tracking-widest">
            <i class="fas fa-plus"></i> Kegiatan Baru
        </button>
    <?php endif; ?>
</div>

<!-- Advanced Search & Filter -->
<div class="bg-white p-7 rounded-[2.5rem] shadow-sm border border-secondary-100 mb-8 overflow-hidden relative">
    <div class="absolute top-0 right-0 w-32 h-32 bg-primary-500/5 blur-3xl rounded-full"></div>
    
    <form id="filterForm" method="GET" action="dashboard_admin.php" class="space-y-6 relative z-10">
        <input type="hidden" name="page" value="kegiatan">
        <input type="hidden" name="status" id="statusInput" value="<?= htmlspecialchars($statusFilter) ?>">
        
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-end">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-3 ml-1 flex items-center">
                    <i class="fas fa-search mr-2 text-primary-500"></i>
                    Cari Kegiatan
                </label>
                <div class="relative group">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-secondary-300 group-focus-within:text-primary-500 transition-colors"></i>
                    <input type="text" name="cari" placeholder="Cari judul, deskripsi, atau penyelenggara..." 
                        value="<?= htmlspecialchars($cariFilter) ?>"
                        class="w-full pl-11 pr-4 py-3.5 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium placeholder-secondary-300 transition-all hover:bg-white">
                </div>
            </div>

            <!-- Period Selector -->
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-3 ml-1 flex items-center">
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
        </div>

        <!-- Status Chips -->
        <div class="w-full pt-2">
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-4 ml-1 flex items-center">
                <i class="fas fa-layer-group mr-2 text-primary-500"></i>
                Status Kegiatan
            </label>
            <div class="flex flex-wrap gap-2">
                <?php
                $statuses = [
                    '' => ['label' => 'Aktif', 'icon' => 'fa-bolt'],
                    'akan_datang' => ['label' => 'Mendatang', 'icon' => 'fa-clock'],
                    'berlangsung' => ['label' => 'Berlangsung', 'icon' => 'fa-play-circle'],
                    'selesai' => ['label' => 'Selesai (Arsip)', 'icon' => 'fa-check-circle']
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
    </form>
</div>

<!-- Table Container -->
<div class="bg-white rounded-[2rem] shadow-sm border border-secondary-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-secondary-50/50">
                    <th
                        class="px-8 py-5 text-xs font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">
                        Detail Kegiatan</th>
                    <th
                        class="px-8 py-5 text-xs font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">
                        Jadwal</th>
                    <th
                        class="px-8 py-5 text-xs font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">
                        Status</th>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                        <th
                            class="px-8 py-5 text-xs font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100 text-right">
                            Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary-50">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="group hover:bg-primary-50/30 transition-all duration-300">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-12 h-12 rounded-2xl bg-primary-100 text-primary-600 flex items-center justify-center shrink-0 shadow-inner group-hover:scale-110 transition-transform">
                                        <i class="fas fa-calendar-alt text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-secondary-900 leading-tight mb-1">
                                            <?= htmlspecialchars($row['judul']) ?>
                                        </p>
                                        <p class="text-xs text-secondary-400 line-clamp-1 max-w-[200px]">
                                            <?= htmlspecialchars($row['deskripsi']) ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm font-bold text-secondary-700 flex items-center">
                                        <i class="far fa-calendar-check mr-2 text-primary-500"></i>
                                        <?php
                                        $bulan = ['Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agt', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'];
                                        echo strtr(date('d M Y', strtotime($row['tanggal'])), $bulan);
                                        ?>
                                    </span>
                                    <span class="text-xs font-medium text-secondary-400 flex items-center">
                                        <i class="far fa-clock mr-2 text-secondary-300"></i>
                                        <?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?> WIB
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <?php
                                date_default_timezone_set('Asia/Jakarta');
                                $now = time();
                                $start_time = strtotime($row['tanggal'] . ' ' . $row['jam_mulai']);
                                $end_time = strtotime($row['tanggal'] . ' ' . $row['jam_selesai']);

                                $isLive = ($now >= $start_time && $now <= $end_time);
                                $isFinished = ($now > $end_time);

                                $color = $isLive ? "green" : ($isFinished ? "secondary" : "blue");
                                $label = $isLive ? "Berlangsung" : ($isFinished ? "Selesai" : "Terjadwal");
                                ?>
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-<?= $color ?>-100 text-<?= $color ?>-600 ring-4 ring-<?= $color ?>-50">
                                    <span
                                        class="w-1.5 h-1.5 rounded-full bg-<?= $color ?>-600 mr-2 <?= ($isLive && !$isFinished) ? 'animate-pulse' : '' ?>"></span>
                                    <?= $label ?>
                                </span>
                            </td>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == "admin"): ?>
                                <td class="px-8 py-6 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button class="btnEdit p-2 hover:bg-blue-100 text-blue-600 rounded-xl transition-all"
                                            data-id="<?= $row['id'] ?>" data-judul="<?= htmlspecialchars($row['judul']) ?>"
                                            data-deskripsi="<?= htmlspecialchars($row['deskripsi']) ?>"
                                            data-tanggal="<?= $row['tanggal'] ?>" data-jam_mulai="<?= $row['jam_mulai'] ?>"
                                            data-jam_selesai="<?= $row['jam_selesai'] ?>"
                                            data-penyelenggara="<?= htmlspecialchars($row['penyelenggara']) ?>"
                                            data-status="<?= $row['status'] ?>">
                                            <i class="fas fa-edit text-lg"></i>
                                        </button>
                                        <a href="dashboard_admin.php?page=kegiatan&hapus=<?= $row['id'] ?>"
                                            class="p-2 hover:bg-red-100 text-red-600 rounded-xl transition-all"
                                            onclick="return confirm('Hapus kegiatan ini secara permanen?')">
                                            <i class="fas fa-trash-alt text-lg"></i>
                                        </a>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-8 py-20 text-center">
                            <i class="fas fa-calendar-xmark text-6xl text-secondary-100 mb-4 block"></i>
                            <p class="text-secondary-400 font-bold">Tidak ada kegiatan yang ditemukan.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Container (Reuse for Add/Edit) -->
<div id="modalContainer"
    class="fixed inset-0 bg-secondary-900/60 backdrop-blur-sm hidden z-[100] transition-all flex items-center justify-center p-4">
    <div
        class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-y-auto max-h-[90vh] animate-in fade-in zoom-in duration-300 custom-scrollbar">
        <div class="px-8 pt-8 pb-4 flex items-center justify-between sticky top-0 bg-white/80 backdrop-blur-md z-10">
            <h3 id="modalTitle" class="text-2xl font-black text-secondary-900 tracking-tight">Tambah Kegiatan Baru</h3>
            <button id="modalClose"
                class="w-10 h-10 rounded-full bg-secondary-50 text-secondary-400 hover:text-secondary-900 transition-all flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="eventForm" method="POST" class="px-8 pb-8 space-y-5">
            <input type="hidden" name="id" id="editId">
            <input type="hidden" name="status" id="editStatus" value="akan_datang">

            <div>
                <label
                    class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Judul
                    Kegiatan</label>
                <input type="text" name="judul" id="editJudul" placeholder="Apa rencana kegiatannya?"
                    class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-[1.25rem] text-secondary-900 font-medium transition-all"
                    required>
            </div>

            <div>
                <label
                    class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Deskripsi</label>
                <textarea name="deskripsi" id="editDeskripsi"
                    placeholder="Ceritakan lebih detail tentang kegiatannya..." rows="3"
                    class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-[1.25rem] text-secondary-900 font-medium transition-all"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label
                        class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Tanggal</label>
                    <input type="date" name="tanggal" id="editTanggal"
                        class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-[1.25rem] text-secondary-900 font-medium transition-all"
                        required>
                </div>
                <div>
                    <label
                        class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Jam
                        Mulai</label>
                    <input type="time" name="jam_mulai" id="editJamMulai"
                        class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-[1.25rem] text-secondary-900 font-medium transition-all"
                        required>
                </div>
                <div>
                    <label
                        class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Jam
                        Selesai</label>
                    <input type="time" name="jam_selesai" id="editJamSelesai"
                        class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-[1.25rem] text-secondary-900 font-medium transition-all"
                        required>
                </div>
            </div>

            <div>
                <label
                    class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Penyelenggara</label>
                <input type="text" name="penyelenggara" id="editPenyelenggara" placeholder="Siapa yang mengadakan?"
                    class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-[1.25rem] text-secondary-900 font-medium transition-all">
            </div>

            <div class="pt-4">
                <button type="submit" name="tambah" id="submitBtn"
                    class="w-full py-5 bg-primary-600 hover:bg-primary-700 text-white font-black rounded-[1.5rem] shadow-xl shadow-primary-500/20 transition-all uppercase tracking-widest text-sm">
                    Simpan Kegiatan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('modalContainer');
    const form = document.getElementById('eventForm');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');

    function showModal(mode = 'add', data = null) {
        modal.classList.remove('hidden');
        if (mode === 'edit') {
            modalTitle.innerText = "Perbarui Info Kegiatan";
            submitBtn.innerText = "Simpan Perubahan";
            submitBtn.name = "update";

            // Fill data
            document.getElementById('editId').value = data.id;
            document.getElementById('editJudul').value = data.judul;
            document.getElementById('editDeskripsi').value = data.deskripsi;
            document.getElementById('editTanggal').value = data.tanggal;
            document.getElementById('editJamMulai').value = data.jam_mulai;
            document.getElementById('editJamSelesai').value = data.jam_selesai;
            document.getElementById('editPenyelenggara').value = data.penyelenggara;
        } else {
            modalTitle.innerText = "Tambah Kegiatan Baru";
            submitBtn.innerText = "Publikasikan Kegiatan";
            submitBtn.name = "tambah";
            form.reset();
        }
    }

    document.getElementById('btnTambah')?.addEventListener('click', () => showModal('add'));
    document.getElementById('modalClose').addEventListener('click', () => modal.classList.add('hidden'));

    document.querySelectorAll('.btnEdit').forEach(btn => {
        btn.addEventListener('click', () => {
            showModal('edit', btn.dataset);
        });
    });

    // Close on outside click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.add('hidden');
    });

    function setStatus(val) {
        document.getElementById('statusInput').value = val;
        document.getElementById('filterForm').submit();
    }
</script>
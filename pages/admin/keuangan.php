<?php
require_once __DIR__ . '/../../config/database.php';

// Tambah Pengeluaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_pengeluaran'])) {
    $nama_pengeluaran = $_POST['nama_pengeluaran'];
    $jenis_pengeluaran = $_POST['jenis_pengeluaran'];
    $jumlah = preg_replace('/[^0-9]/', '', $_POST['jumlah']);
    $tanggal = $_POST['tanggal'];

    $keterangan = "Admin - $jenis_pengeluaran - $nama_pengeluaran";

    $admin_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO keuangan (tanggal, keterangan, jenis, jumlah, admin_id) VALUES (?, ?, 'pengeluaran', ?, ?)");
    $stmt->bind_param("ssdi", $tanggal, $keterangan, $jumlah, $admin_id);

    if ($stmt->execute()) {
        $keuangan_id = $conn->insert_id;

        // Simpan ke Riwayat Laporan (Consolidated)
        // sumber_id diisi dengan id dari tabel keuangan
        $check_table = $conn->query("SHOW COLUMNS FROM laporan LIKE 'sumber_id'");
        if ($check_table && $check_table->num_rows > 0) {
            $stmt_l = $conn->prepare("INSERT INTO laporan (tanggal, keterangan, jenis, jumlah, sumber_id, admin_id) VALUES (?, ?, 'pengeluaran', ?, ?, ?)");
            $stmt_l->bind_param("ssdii", $tanggal, $keterangan, $jumlah, $keuangan_id, $admin_id);
            $stmt_l->execute();
            $stmt_l->close();
        } else {
            // Fallback jika sumber_id belum ada
            $stmt_l = $conn->prepare("INSERT INTO laporan (tanggal, keterangan, jenis, jumlah, admin_id) VALUES (?, ?, 'pengeluaran', ?, ?)");
            $stmt_l->bind_param("ssdi", $tanggal, $keterangan, $jumlah, $admin_id);
            $stmt_l->execute();
            $stmt_l->close();
        }

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Catatan pengeluaran telah ditambahkan.',
                    icon: 'success',
                    confirmButtonColor: '#10b981',
                    customClass: {
                        popup: 'rounded-[2rem] shadow-xl border-0',
                        confirmButton: 'rounded-xl px-6 py-2.5 font-bold'
                    }
                }).then(() => {
                    window.location.href='?page=keuangan';
                });
            });
        </script>";
    }
    $stmt->close();
}

// Update Keuangan (hanya pengeluaran, nominal TIDAK bisa diubah — Opsi B)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_keuangan'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $catatan = $_POST['catatan'];
    $tanggal = $_POST['tanggal'];
    $jenis = $_POST['jenis'];

    // Validasi: hanya pengeluaran yang boleh diedit
    // Ambil data asli dari DB — jumlah dari POST DIABAIKAN (tidak bisa diubah)
    $stmt_check = $conn->prepare("SELECT jenis, jumlah FROM keuangan WHERE id = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    $row_check = $res_check->fetch_assoc();
    $stmt_check->close();

    if (!$row_check || $row_check['jenis'] !== 'pengeluaran') {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Akses Ditolak!',
                    text: 'Data pemasukan tidak dapat diedit oleh admin.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444',
                    customClass: {
                        popup: 'rounded-[2rem] shadow-xl border-0',
                        confirmButton: 'rounded-xl px-6 py-2.5 font-bold'
                    }
                }).then(() => {
                    window.location.href='?page=keuangan';
                });
            });
        </script>";
    } else {
        // Gunakan jumlah ASLI dari DB, bukan dari form POST
        $jumlah_asli = $row_check['jumlah'];
        $keterangan = "$nama - $kategori - $catatan";

        $stmt = $conn->prepare("UPDATE keuangan SET tanggal=?, keterangan=?, jenis=?, jumlah=? WHERE id=?");
        $stmt->bind_param("sssdi", $tanggal, $keterangan, $jenis, $jumlah_asli, $id);
        if ($stmt->execute()) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Data keuangan telah diperbarui.',
                        icon: 'success',
                        confirmButtonColor: '#10b981',
                        customClass: {
                            popup: 'rounded-[2rem] shadow-xl border-0',
                            confirmButton: 'rounded-xl px-6 py-2.5 font-bold'
                        }
                    }).then(() => {
                        window.location.href='?page=keuangan';
                    });
                });
            </script>";
        }
        $stmt->close();
    }
}

// Fitur hapus pengeluaran telah dinonaktifkan
// Pengeluaran hanya dapat diedit (nama, kategori, catatan, tanggal)
// Nominal tidak dapat diubah setelah dicatat

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
$query_transaksi = "SELECT k.*, u.nama as nama_admin FROM keuangan k LEFT JOIN users u ON k.admin_id = u.id $whereClause ORDER BY k.tanggal DESC, k.id DESC";
$stmt_transaksi = $conn->prepare($query_transaksi);
if (!empty($params)) {
    $stmt_transaksi->bind_param($types, ...$params);
}
$stmt_transaksi->execute();
$transaksi = $stmt_transaksi->get_result();
?>

<div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Buku Kas Keuangan</h2>
        <p class="text-secondary-500 mt-1">Pantau dan audit arus kas lingkungan RT secara berkala.</p>
    </div>

    <button type="button" onclick="showExpenseModal()"
        class="inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-2xl shadow-lg shadow-red-500/20 transition-all active:scale-95 text-sm uppercase tracking-widest">
        <i class="fas fa-minus-circle"></i> Catat Pengeluaran
    </button>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary-100 flex items-center gap-4">
        <div
            class="w-12 h-12 bg-green-100 text-green-600 rounded-2xl flex items-center justify-center shadow-inner shrink-0">
            <i class="fas fa-arrow-down-long text-lg"></i>
        </div>
        <div>
            <p class="text-secondary-400 text-[10px] font-black uppercase tracking-widest">Total Pemasukan</p>
            <p class="text-xl font-black text-secondary-900">Rp <?= number_format($totalPemasukan, 0, ',', '.'); ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary-100 flex items-center gap-4">
        <div
            class="w-12 h-12 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center shadow-inner shrink-0">
            <i class="fas fa-arrow-up-long text-lg"></i>
        </div>
        <div>
            <p class="text-secondary-400 text-[10px] font-black uppercase tracking-widest">Total Pengeluaran</p>
            <p class="text-xl font-black text-secondary-900">Rp <?= number_format($totalPengeluaran, 0, ',', '.'); ?>
            </p>
        </div>
    </div>
    <div class="bg-primary-900 p-6 rounded-3xl shadow-xl flex items-center gap-4 relative overflow-hidden group">
        <div
            class="absolute -right-4 -top-4 w-20 h-20 bg-primary-500/20 rounded-full group-hover:scale-150 transition-transform">
        </div>
        <div
            class="w-12 h-12 bg-primary-500 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-primary-500/30 shrink-0 relative z-10">
            <i class="fas fa-wallet text-lg"></i>
        </div>
        <div class="relative z-10">
            <p class="text-secondary-300 text-[10px] font-black uppercase tracking-widest">Saldo Kas Saat Ini</p>
            <p class="text-xl font-black text-white">Rp <?= number_format($saldoAkhir, 0, ',', '.'); ?></p>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="bg-white p-6 rounded-[2rem] shadow-sm border border-secondary-100 mb-8">
    <form method="GET" action="dashboard_admin.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <input type="hidden" name="page" value="keuangan">

        <div>
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Kategori
                Transaksi</label>
            <select name="filter_jenis"
                class="w-full px-4 py-3 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium">
                <option value="semua" <?= ($filter_jenis == 'semua' || empty($filter_jenis)) ? 'selected' : '' ?>>Semua
                    Jenis</option>
                <option value="pemasukan" <?= $filter_jenis == 'pemasukan' ? 'selected' : '' ?>>Pemasukan (Uang Masuk)
                </option>
                <option value="pengeluaran" <?= $filter_jenis == 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran (Uang
                    Keluar)</option>
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Periode
                Waktu</label>
            <select name="filter_bulan"
                class="w-full px-4 py-3 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium">
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

        <button type="submit"
            class="w-full px-6 py-3 bg-secondary-900 text-white font-black rounded-2xl hover:bg-secondary-800 transition-all active:scale-95">
            TELUSURI
        </button>
    </form>
</div>

<!-- Table Container -->
<div class="bg-white rounded-[2rem] shadow-sm border border-secondary-100 overflow-hidden">
    <div class="overflow-x-auto max-h-[550px] overflow-y-auto custom-scrollbar">
        <table class="w-full text-left border-separate border-spacing-0">
            <thead class="sticky top-0 z-10">
                <tr class="bg-secondary-50">
                    <th
                        class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100">
                        Subjek/Nama</th>
                    <th
                        class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100">
                        Tanggal</th>
                    <th
                        class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100">
                        Kategori</th>
                    <th
                        class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100 text-right">
                        Jumlah</th>
                    <th
                        class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100">
                        Catatan</th>
                    <th
                        class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100 text-right">
                        Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary-50">
                <?php if ($transaksi->num_rows > 0): ?>
                    <?php while ($row = $transaksi->fetch_assoc()):
                        $isInc = ($row['jenis'] == 'pemasukan');
                        $color = $isInc ? "green" : "red";

                        $parts = explode(' - ', $row['keterangan'], 3);
                        $name = $parts[0] ?? '-';
                        $cat = $parts[1] ?? '-';
                        $note = $parts[2] ?? '-';
                        ?>
                        <tr class="group hover:bg-primary-50/30 transition-all">
                            <td class="px-8 py-5">
                                <p class="font-bold text-secondary-900"><?= htmlspecialchars($name) ?></p>
                                <?php if (!empty($row['nama_admin'])): ?>
                                    <p class="text-[9px] font-black text-red-500 uppercase tracking-widest mt-1">
                                        <i class="fas fa-user-edit mr-1"></i> <?= htmlspecialchars($row['nama_admin']) ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-sm font-medium text-secondary-500">
                                    <?= date('d M Y', strtotime($row['tanggal'])) ?>
                                </p>
                            </td>
                            <td class="px-6 py-5">
                                <span
                                    class="text-xs font-bold text-secondary-400 bg-secondary-100/50 px-2.5 py-1 rounded-lg"><?= htmlspecialchars($cat) ?></span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <p class="font-black text-<?= $color ?>-600 whitespace-nowrap">
                                    <?= $isInc ? '+' : '-' ?> Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?>
                                </p>
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-xs text-secondary-400 line-clamp-1 max-w-[150px] italic">
                                    <?= htmlspecialchars($note) ?>
                                </p>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <?php if ($row['jenis'] === 'pengeluaran'): ?>
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button
                                            class="btnEditKeuangan p-2 hover:bg-blue-100 text-blue-600 rounded-xl transition-all"
                                            data-id="<?= $row['id'] ?>" data-nama="<?= htmlspecialchars($name) ?>"
                                            data-kategori="<?= htmlspecialchars($cat) ?>"
                                            data-catatan="<?= htmlspecialchars($note) ?>" data-jumlah="<?= $row['jumlah'] ?>"
                                            data-tanggal="<?= $row['tanggal'] ?>" data-jenis="<?= $row['jenis'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-[10px] font-black text-secondary-300 uppercase tracking-widest">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-8 py-20 text-center">
                            <p class="text-secondary-400 font-bold">Tidak ada data keuangan yang ditemukan.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Pengeluaran -->
<div id="modalPengeluaran"
    class="fixed inset-0 bg-secondary-900/60 backdrop-blur-sm hidden z-[100] transition-all flex items-center justify-center p-4">
    <div
        class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="px-10 pt-10 pb-4 flex items-center justify-between">
            <h3 class="text-2xl font-black text-secondary-900 tracking-tight">Catat Pengeluaran RT</h3>
            <button onclick="hideExpenseModal()"
                class="w-10 h-10 rounded-full bg-secondary-50 text-secondary-400 hover:text-secondary-900 flex items-center justify-center transition-all">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formTambahPengeluaran" method="POST" action="?page=keuangan" class="px-10 pb-10 space-y-5">
            <input type="hidden" name="tambah_pengeluaran" value="1">

            <div>
                <label
                    class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Detail
                    Pengeluaran / Judul Nota</label>
                <input type="text" name="nama_pengeluaran" placeholder="Contoh: Pembelian alat kebersihan" required
                    class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-red-500 rounded-[1.25rem] text-secondary-900 font-medium placeholder-secondary-300 transition-all">
            </div>

            <div>
                <label
                    class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Kategori
                    Fungsional</label>
                <select name="jenis_pengeluaran" required
                    class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-red-500 rounded-[1.25rem] text-secondary-900 font-medium transition-all appearance-none">
                    <option value="">Pilih Kategori</option>
                    <option value="Operasional">Operasional</option>
                    <option value="Kegiatan Sosial">Kegiatan Sosial</option>
                    <option value="Kebersihan">Kebersihan</option>
                    <option value="Keamanan">Keamanan</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label
                        class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Jumlah
                        (Rp)</label>
                    <input type="text" id="jumlah_pengeluaran" name="jumlah" placeholder="Rp 0" required
                        class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-red-500 rounded-[1.25rem] text-secondary-900 font-black transition-all">
                </div>
                <div>
                    <label
                        class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Tanggal
                        Transaksi</label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required
                        class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-red-500 rounded-[1.25rem] text-secondary-900 font-medium transition-all">
                </div>
            </div>

            <div class="pt-4">
                <button type="submit"
                    class="w-full py-5 bg-red-600 hover:bg-red-700 text-white font-black rounded-[1.5rem] shadow-xl shadow-red-500/20 transition-all uppercase tracking-widest text-sm">
                    Konfirmasi & Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Keuangan -->
<div id="modalEditKeuangan"
    class="fixed inset-0 bg-secondary-900/60 backdrop-blur-sm hidden z-[100] transition-all flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="px-10 pt-10 pb-4 flex items-center justify-between">
            <h3 class="text-2xl font-black text-secondary-900 tracking-tight">Edit Transaksi</h3>
            <button onclick="hideEditModalKeuangan()"
                class="w-10 h-10 rounded-full bg-secondary-50 text-secondary-400 hover:text-secondary-900 flex items-center justify-center transition-all">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="?page=keuangan" class="px-10 pb-10 space-y-5">
            <input type="hidden" name="update_keuangan" value="1">
            <input type="hidden" name="id" id="editKeuanganId">
            <input type="hidden" name="jenis" id="editKeuanganJenis">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label
                        class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Subjek/Nama</label>
                    <input type="text" name="nama" id="editKeuanganNama" required
                        class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-[1.25rem] text-secondary-900 font-medium transition-all">
                </div>
                <div>
                    <label
                        class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Kategori</label>
                    <input type="text" name="kategori" id="editKeuanganKategori" required
                        class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-[1.25rem] text-secondary-900 font-medium transition-all">
                </div>
            </div>

            <div>
                <label
                    class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Catatan/Keterangan</label>
                <input type="text" name="catatan" id="editKeuanganCatatan" required
                    class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-[1.25rem] text-secondary-900 font-medium transition-all">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="flex items-center gap-2 text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">
                        Jumlah (Rp)
                        <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-600 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-wide">
                            <i class="fas fa-lock text-[8px]"></i> Terkunci
                        </span>
                    </label>
                    <div class="relative">
                        <input type="text" id="editKeuanganJumlah" name="jumlah" readonly
                            class="w-full px-5 py-4 bg-secondary-100 border-0 rounded-[1.25rem] text-secondary-400 font-black cursor-not-allowed select-none transition-all">
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-secondary-300">
                            <i class="fas fa-lock text-sm"></i>
                        </div>
                    </div>
                </div>
                <div>
                    <label
                        class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Tanggal</label>
                    <input type="date" name="tanggal" id="editKeuanganTanggal" required
                        class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-[1.25rem] text-secondary-900 font-medium transition-all">
                </div>
            </div>

            <!-- Info: nominal terkunci permanen -->
            <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3">
                <i class="fas fa-lock text-amber-500 mt-0.5 shrink-0"></i>
                <p class="text-xs text-amber-700 font-medium leading-relaxed">
                    <span class="font-black">Nominal bersifat permanen.</span> Hanya nama, kategori, catatan, dan tanggal yang dapat diperbarui.
                </p>
            </div>

            <div class="pt-4">
                <button type="submit"
                    class="w-full py-5 bg-secondary-900 text-white font-black rounded-[1.5rem] shadow-xl shadow-secondary-900/10 transition-all uppercase tracking-widest text-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function showExpenseModal() {
        document.getElementById('modalPengeluaran').classList.remove('hidden');
    }
    function hideExpenseModal() {
        document.getElementById('modalPengeluaran').classList.add('hidden');
    }
    function hideEditModalKeuangan() {
        document.getElementById('modalEditKeuangan').classList.add('hidden');
    }

    function formatRupiahAdmin(angka) {
        return "Rp " + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Input mask untuk jumlah
    [document.getElementById("jumlah_pengeluaran"), document.getElementById("editKeuanganJumlah")].forEach(inp => {
        if (inp) {
            inp.addEventListener("input", function () {
                let value = this.value.replace(/[^0-9]/g, "");
                let number = parseInt(value);
                if (!isNaN(number) && number > 0) {
                    this.value = formatRupiahAdmin(number);
                } else {
                    this.value = "";
                }
            });
        }
    });

    // Logic Edit
    document.querySelectorAll('.btnEditKeuangan').forEach(btn => {
        btn.addEventListener('click', function () {
            const d = this.dataset;
            document.getElementById('editKeuanganId').value = d.id;
            document.getElementById('editKeuanganNama').value = d.nama;
            document.getElementById('editKeuanganKategori').value = d.kategori;
            document.getElementById('editKeuanganCatatan').value = d.catatan;
            document.getElementById('editKeuanganJumlah').value = formatRupiahAdmin(d.jumlah);
            document.getElementById('editKeuanganTanggal').value = d.tanggal;
            document.getElementById('editKeuanganJenis').value = d.jenis;
            document.getElementById('modalEditKeuangan').classList.remove('hidden');
        });
    });

    // Konfirmasi Tambah Pengeluaran
    const formTambah = document.getElementById('formTambahPengeluaran');
    if (formTambah) {
        formTambah.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const nominal = document.getElementById('jumlah_pengeluaran').value;
            const nama_pengeluaran = document.getElementsByName('nama_pengeluaran')[0].value;
            
            Swal.fire({
                title: 'Cek Kembali Nominal!',
                html: `Anda akan mencatat pengeluaran sebesar: <br><strong class="text-2xl text-red-600 mt-2 block">${nominal}</strong><br>Untuk: <strong>${nama_pengeluaran}</strong><br><br><span class="text-sm text-amber-600"><i class="fas fa-exclamation-triangle"></i> PENTING: Nominal ini akan dikunci permanen dan tidak dapat diedit atau dihapus setelah disimpan.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Simpan Data',
                cancelButtonText: 'Batal, Cek Lagi',
                customClass: {
                    popup: 'rounded-[2rem] shadow-xl border-0',
                    confirmButton: 'rounded-xl px-6 py-2.5 font-bold',
                    cancelButton: 'rounded-xl px-6 py-2.5 font-bold'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    formTambah.submit();
                }
            });
        });
    }

    // Fitur hapus pengeluaran telah dinonaktifkan

    // Close on outside click
    [document.getElementById('modalPengeluaran'), document.getElementById('modalEditKeuangan')].forEach(m => {
        m.addEventListener('click', (e) => {
            if (e.target === m) m.classList.add('hidden');
        });
    });
</script>
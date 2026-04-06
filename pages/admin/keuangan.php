<?php
// contoh ambil data dari database
include 'config/database.php';

// Hitung total pemasukan, pengeluaran, saldo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_pengeluaran'])) {
    $nama_pengeluaran = $_POST['nama_pengeluaran'];
    $jenis_pengeluaran = $_POST['jenis_pengeluaran'];
    $jumlah = preg_replace('/[^0-9]/', '', $_POST['jumlah']);
    $tanggal = $_POST['tanggal'];
    
    // Format: Nama Warga (Admin) - Kategori (jenis pengeluaran) - Catatan (nama pengeluaran)
    $keterangan = "Admin - $jenis_pengeluaran - $nama_pengeluaran";
    
    $stmt = $conn->prepare("INSERT INTO keuangan (tanggal, keterangan, jenis, jumlah) VALUES (?, ?, 'pengeluaran', ?)");
    $stmt->bind_param("ssd", $tanggal, $keterangan, $jumlah);
    if($stmt->execute()) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Berhasil!', 'Data pengeluaran ditambahkan', 'success').then(() => {
                    window.location.href='?page=keuangan';
                });
            });
        </script>";
    }
    $stmt->close();
}

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

<h2 class="text-2xl font-bold mb-4">Data Keuangan</h2>
<p class="mb-6 text-gray-600">Kelola keuangan RT</p>

<!-- Filter Bar -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 space-y-2 md:space-y-0">
    <!-- Group kiri -->
    <form method="GET" action="dashboard_admin.php" class="flex flex-col sm:flex-row sm:space-x-2 space-y-2 sm:space-y-0 w-full md:w-auto">
        <input type="hidden" name="page" value="keuangan">
        <select name="filter_jenis" class="border px-3 py-2 rounded-lg w-full sm:w-auto bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="semua" <?= ($filter_jenis == 'semua' || empty($filter_jenis)) ? 'selected' : '' ?>>Semua Jenis</option>
            <option value="pemasukan" <?= $filter_jenis == 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
            <option value="pengeluaran" <?= $filter_jenis == 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
        </select>
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
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg w-full sm:w-auto hover:bg-green-700 transition font-bold shadow-sm">
            <i class="fas fa-filter mr-1"></i> Terpakan Filter
        </button>
    </form>

    <!-- Tombol kanan (hanya untuk pengeluaran) -->
    <button type="button" onclick="document.getElementById('modalPengeluaran').classList.remove('hidden')" class="bg-red-600 text-white px-4 py-2 rounded-lg w-full md:w-auto hover:bg-red-700">
        <i class="fas fa-minus mr-2"></i>Tambah Pengeluaran
    </button>
</div>

<!-- Cards Ringkasan -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white shadow rounded-xl p-6">
        <p class="text-gray-600">Total Pemasukan</p>
        <p class="text-2xl font-bold text-green-600">Rp <?= number_format($totalPemasukan, 0, ',', '.'); ?></p>
    </div>
    <div class="bg-white shadow rounded-xl p-6">
        <p class="text-gray-600">Total Pengeluaran</p>
        <p class="text-2xl font-bold text-red-600">Rp <?= number_format($totalPengeluaran, 0, ',', '.'); ?></p>
    </div>
    <div class="bg-white shadow rounded-xl p-6">
        <p class="text-gray-600">Saldo Akhir</p>
        <p class="text-2xl font-bold text-blue-600">Rp <?= number_format($saldoAkhir, 0, ',', '.'); ?></p>
    </div>
</div>

<!-- Tabel -->
<div class="bg-white shadow rounded-xl overflow-x-auto">
    <table class="w-full text-left min-w-[700px]">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-6 py-3">Nama</th>
                <th class="px-6 py-3">Tanggal</th>
                <th class="px-6 py-3">Kategori</th>
                <th class="px-6 py-3">Jenis</th>
                <th class="px-6 py-3">Jumlah</th>
                <th class="px-6 py-3">Keterangan</th>
                <th class="px-6 py-3">Aksi</th>
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
                    <td class="px-6 py-3"><?= htmlspecialchars($nama_warga) ?></td>
                    <td class="px-6 py-3"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                    <td class="px-6 py-3"><?= htmlspecialchars($kategori) ?></td>
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
                    <td class="px-6 py-3"><?= htmlspecialchars($keterangan_tampil) ?></td>
                    <td class="px-6 py-3 flex space-x-2">
                        <button class="text-blue-600"><i class="fas fa-edit"></i></button>
                        <button class="text-red-600"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal Tambah Pengeluaran -->
<div id="modalPengeluaran" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center transition-opacity">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4 p-6 overflow-y-auto max-h-[90vh]">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-xl">Tambah Pengeluaran Baru</h3>
            <button onclick="document.getElementById('modalPengeluaran').classList.add('hidden')" class="text-gray-500 hover:text-red-500 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="?page=keuangan">
            <input type="hidden" name="tambah_pengeluaran" value="1">
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-1 font-semibold">Nama / Catatan Pengeluaran</label>
                <input type="text" name="nama_pengeluaran" placeholder="Contoh: Beli Sapu dan Plastik Sampah" required class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-1 font-semibold">Jenis (Kategori) Pengeluaran</label>
                <select name="jenis_pengeluaran" required class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">Pilih Jenis</option>
                    <option value="Operasional">Operasional</option>
                    <option value="Kegiatan Sosial">Kegiatan Sosial</option>
                    <option value="Kebersihan">Kebersihan</option>
                    <option value="Keamanan">Keamanan</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-1 font-semibold">Total Pengeluaran</label>
                <input type="text" id="jumlah_pengeluaran" name="jumlah" placeholder="Contoh: Rp 50.000" required class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 mb-1 font-semibold">Tanggal</label>
                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('modalPengeluaran').classList.add('hidden')" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 font-bold">Batal</button>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 font-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function formatRupiahAdmin(angka) {
    return "Rp " + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}
const inputJumlahExp = document.getElementById("jumlah_pengeluaran");
if(inputJumlahExp) {
    inputJumlahExp.addEventListener("input", function() {
        let value = this.value.replace(/[^0-9]/g, "");
        let number = parseInt(value);
        if (!isNaN(number) && number > 0) {
            this.value = formatRupiahAdmin(number);
        } else {
            this.value = "";
        }
    });
}
</script>

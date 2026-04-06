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

// filter cari
if (!empty($_GET['cari'])) {
    $where[] = "(judul LIKE ? OR deskripsi LIKE ? OR penyelenggara LIKE ?)";
    $keyword = "%" . $_GET['cari'] . "%";
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= "sss";
}

// filter status
if (!empty($_GET['status'])) {
    if ($_GET['status'] == 'akan_datang') {
        $where[] = "NOW() < CONCAT(tanggal, ' ', jam_mulai)";
    } elseif ($_GET['status'] == 'berlangsung') {
        $where[] = "NOW() >= CONCAT(tanggal, ' ', jam_mulai) AND NOW() <= CONCAT(tanggal, ' ', jam_selesai)";
    }
}

// Sembunyikan otomatis jika sesi jam sudah melewati waktu real-time
$where[] = "CONCAT(tanggal, ' ', jam_selesai) >= NOW()";

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
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Kegiatan</title>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="p-6 bg-gray-100">

<h2 class="text-2xl font-bold mb-4">Data Kegiatan</h2>
<p class="mb-6 text-gray-600">Kelola kegiatan RT</p>

<?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>

<!-- Filter + Tombol Tambah -->
<div class="flex flex-col sm:flex-row sm:items-start gap-2 w-full mb-4">
    <!-- Form Cari -->
    <form method="GET" action="dashboard_admin.php" 
          class="flex flex-col sm:flex-row sm:space-x-2 space-y-2 sm:space-y-0 w-full">
        <input type="hidden" name="page" value="kegiatan">
        <input type="text" name="cari" placeholder="Cari kegiatan..." 
               value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>" 
               class="border px-3 py-2 rounded-lg w-full sm:w-64">

        <select name="status" class="border px-3 py-2 rounded-lg w-full sm:w-auto">
            <option value="">Semua Status</option>
            <option value="akan_datang" <?= (isset($_GET['status']) && $_GET['status']=='akan_datang') ? 'selected' : '' ?>>Akan Datang</option>
            <option value="berlangsung" <?= (isset($_GET['status']) && $_GET['status']=='berlangsung') ? 'selected' : '' ?>>Sedang Berlangsung</option>
        </select>

        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg w-full sm:w-auto">
            <i class="fas fa-search"></i> Cari
        </button>
    </form>

    <!-- Tombol Tambah -->
    <button id="btnTambah" 
        class="flex items-center justify-center gap-2 
            bg-green-600 hover:bg-green-700 text-white 
            font-medium rounded-lg shadow-md 
            transition
            px-5 py-2.5 text-base w-full sm:w-auto 
            sm:px-6 sm:py-3 sm:text-lg">
        <i class="fas fa-plus"></i>
        <span class="whitespace-nowrap">Tambah Kegiatan</span>
    </button>
</div>

<!-- Modal Tambah -->
<div id="modalTambah" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-xl w-full max-w-lg">
        <h3 class="text-xl font-bold mb-4">Tambah Kegiatan</h3>
        <form method="POST">
            <input type="text" name="judul" placeholder="Judul Kegiatan" class="border px-3 py-2 rounded w-full mb-2" required>
            <textarea name="deskripsi" placeholder="Deskripsi" class="border px-3 py-2 rounded w-full mb-2"></textarea>
            <input type="date" name="tanggal" class="border px-3 py-2 rounded w-full mb-2" required>
            <div class="flex space-x-2 mb-2">
                <input type="time" name="jam_mulai" class="border px-3 py-2 rounded w-1/2" required>
                <input type="time" name="jam_selesai" class="border px-3 py-2 rounded w-1/2" required>
            </div>
            <input type="text" name="penyelenggara" placeholder="Penyelenggara" class="border px-3 py-2 rounded w-full mb-2">
            <select name="status" class="border px-3 py-2 rounded w-full mb-2 bg-gray-100 cursor-not-allowed pointer-events-none" tabindex="-1">
                <option value="akan_datang">Akan Datang</option>
            </select>
            <div class="flex justify-end space-x-2 mt-4">
                <button type="button" id="btnCloseTambah" class="px-4 py-2 rounded bg-gray-300">Batal</button>
                <button type="submit" name="tambah" class="px-4 py-2 rounded bg-green-600 text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<!-- Tabel Kegiatan -->
<div class="bg-white shadow rounded-xl overflow-x-auto mt-4">
    <table class="w-full text-left min-w-[600px]">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-6 py-3">Nama Kegiatan</th>
                <th class="px-6 py-3">Tanggal & Jam</th>
                <th class="px-6 py-3">Status</th>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <th class="px-6 py-3">Aksi</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr class="border-t">
                <td class="px-6 py-3">
                    <?= htmlspecialchars($row['judul']) ?><br>
                    <span class="text-gray-500 text-sm"><?= htmlspecialchars($row['deskripsi']) ?></span>
                </td>
                <td class="px-6 py-3">
                    <?= date('d M Y', strtotime($row['tanggal'])) ?><br>
                    <?= substr($row['jam_mulai'],0,5) ?> - <?= substr($row['jam_selesai'],0,5) ?>
                </td>
                <td class="px-6 py-3">
                    <?php
                        date_default_timezone_set('Asia/Jakarta');
                        $now = time();
                        $start_time = strtotime($row['tanggal'] . ' ' . $row['jam_mulai']);
                        $end_time = strtotime($row['tanggal'] . ' ' . $row['jam_selesai']);
                        
                        if ($now >= $start_time && $now <= $end_time) {
                            $color = "green";
                            $display_status = "Sedang Berlangsung";
                        } else {
                            $color = "blue";
                            $display_status = "Akan Datang";
                        }
                    ?>
                    <span class="bg-<?= $color ?>-100 text-<?= $color ?>-600 px-2 py-1 rounded">
                        <?= $display_status ?>
                    </span>
                </td>
                <?php if(isset($_SESSION['role']) && $_SESSION['role']=="admin"): ?>
                <td class="px-6 py-3 flex space-x-2">
                    <button 
                        class="text-blue-600 btnEdit"
                        data-id="<?= $row['id'] ?>"
                        data-judul="<?= htmlspecialchars($row['judul']) ?>"
                        data-deskripsi="<?= htmlspecialchars($row['deskripsi']) ?>"
                        data-tanggal="<?= $row['tanggal'] ?>"
                        data-jam_mulai="<?= $row['jam_mulai'] ?>"
                        data-jam_selesai="<?= $row['jam_selesai'] ?>"
                        data-penyelenggara="<?= htmlspecialchars($row['penyelenggara']) ?>"
                        data-status="<?= $row['status'] ?>"
                    ><i class="fas fa-edit"></i></button>
                    <a href="dashboard_admin.php?page=kegiatan&hapus=<?= $row['id'] ?>" 
                        class="text-red-600" 
                        onclick="return confirm('Yakin ingin hapus?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal Edit -->
<div id="modalEdit" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-xl w-full max-w-lg">
        <h3 class="text-xl font-bold mb-4">Edit Kegiatan</h3>
        <form method="POST">
            <input type="hidden" name="id" id="editId">
            <input type="text" name="judul" id="editJudul" placeholder="Judul Kegiatan" class="border px-3 py-2 rounded w-full mb-2" required>
            <textarea name="deskripsi" id="editDeskripsi" placeholder="Deskripsi" class="border px-3 py-2 rounded w-full mb-2"></textarea>
            <input type="date" name="tanggal" id="editTanggal" class="border px-3 py-2 rounded w-full mb-2" required>
            <div class="flex space-x-2 mb-2">
                <input type="time" name="jam_mulai" id="editJamMulai" class="border px-3 py-2 rounded w-1/2" required>
                <input type="time" name="jam_selesai" id="editJamSelesai" class="border px-3 py-2 rounded w-1/2" required>
            </div>
            <input type="text" name="penyelenggara" id="editPenyelenggara" placeholder="Penyelenggara" class="border px-3 py-2 rounded w-full mb-2">
            <!-- Status dikalkulasikan otomatis -> Input dibekukan -->
            <select name="status" id="editStatus" class="border px-3 py-2 rounded w-full mb-2 bg-gray-100 pointer-events-none" tabindex="-1">
                <option value="akan_datang">Akan Datang</option>
            </select>
            <div class="flex justify-end space-x-2 mt-4">
                <button type="button" id="btnCloseEdit" class="px-4 py-2 rounded bg-gray-300">Batal</button>
                <button type="submit" name="update" class="px-4 py-2 rounded bg-blue-600 text-white">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Script Modal -->
<script>
const btnTambah = document.getElementById('btnTambah');
const modalTambah = document.getElementById('modalTambah');
const btnCloseTambah = document.getElementById('btnCloseTambah');
if(btnTambah){
    btnTambah.addEventListener('click', () => modalTambah.classList.remove('hidden'));
}
if(btnCloseTambah){
    btnCloseTambah.addEventListener('click', () => modalTambah.classList.add('hidden'));
}

// Edit modal
const modalEdit = document.getElementById('modalEdit');
const btnCloseEdit = document.getElementById('btnCloseEdit');
const editButtons = document.querySelectorAll('.btnEdit');

editButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('editId').value = btn.dataset.id;
        document.getElementById('editJudul').value = btn.dataset.judul;
        document.getElementById('editDeskripsi').value = btn.dataset.deskripsi;
        document.getElementById('editTanggal').value = btn.dataset.tanggal;
        document.getElementById('editJamMulai').value = btn.dataset.jam_mulai;
        document.getElementById('editJamSelesai').value = btn.dataset.jam_selesai;
        document.getElementById('editPenyelenggara').value = btn.dataset.penyelenggara;
        document.getElementById('editStatus').value = btn.dataset.status;
        modalEdit.classList.remove('hidden');
    });
});

if(btnCloseEdit){
    btnCloseEdit.addEventListener('click', () => modalEdit.classList.add('hidden'));
}
</script>
</body>
</html>

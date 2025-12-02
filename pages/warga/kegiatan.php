<?php
// pages/warga/kegiatan.php
include __DIR__ . '/../../config/database.php';

// Ambil filter dari GET
$where = [];
$params = [];
$types = "";

$statusFilter = $_GET['status'] ?? "";
$bulanFilter = $_GET['bulan'] ?? "";

if (!empty($statusFilter)) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
    $types .= "s";
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
$sql .= " ORDER BY tanggal DESC, jam_mulai ASC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<h2 class="text-2xl font-bold mb-2">Informasi Kegiatan</h2>
<p class="text-gray-600 mb-6">Daftar kegiatan RT 006/008</p>

<!-- Filter Form -->
<div class="mb-6">
    <form method="GET" action="dashboard_warga.php" class="flex flex-col md:flex-row gap-3 md:gap-4">
        <input type="hidden" name="page" value="kegiatan">

        <select name="status" class="border rounded px-4 py-2 w-full md:w-auto">
            <option value="">Semua Status</option>
            <option value="akan_datang" <?= ($statusFilter=="akan_datang") ? 'selected' : '' ?>>Akan Datang</option>
            <option value="berlangsung" <?= ($statusFilter=="berlangsung") ? 'selected' : '' ?>>Berlangsung</option>
            <option value="selesai" <?= ($statusFilter=="selesai") ? 'selected' : '' ?>>Selesai</option>
        </select>

        <select name="bulan" class="border rounded px-4 py-2 w-full md:w-auto">
            <option value="">Semua Bulan</option>
            <?php
            for ($m = 1; $m <= 12; $m++) {
                $monthName = date("F Y", mktime(0, 0, 0, $m, 1));
                echo "<option value='$m' ".(($bulanFilter==$m)?'selected':'').">$monthName</option>";
            }
            ?>
        </select>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full md:w-auto">
            <i class="fas fa-search mr-1"></i>Cari
        </button>
    </form>
</div>

<?php
if ($result && $result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
        // Ambil status langsung dari database
        $status = $row['status'] ?? 'tidak_diketahui';

        // Tentukan warna badge
        if ($status === "berlangsung") {
            $badge = "bg-green-100 text-green-600";
            $statusLabel = "Berlangsung";
        } elseif ($status === "akan_datang") {
            $badge = "bg-blue-100 text-blue-600";
            $statusLabel = "Akan Datang";
        } elseif ($status === "selesai") {
            $badge = "bg-red-100 text-red-600";
            $statusLabel = "Selesai";
        } else {
            $badge = "bg-gray-100 text-gray-600";
            $statusLabel = "Tidak Diketahui";
        }
?>
<div class="bg-white p-6 rounded-lg shadow mb-4">
    <h3 class="font-bold"><?= htmlspecialchars($row['judul']) ?></h3>
    <p class="text-gray-600"><?= htmlspecialchars($row['deskripsi']) ?></p>
    <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_1fr_auto] items-center text-sm mt-3 text-gray-500 gap-2">
        <span class="flex items-center"><i class="fas fa-calendar-alt mr-1"></i><?= date("d F Y", strtotime($row['tanggal'])) ?></span>
        <span class="flex items-center"><i class="fas fa-clock mr-1"></i><?= date("H:i", strtotime($row['jam_mulai'])) ?> - <?= date("H:i", strtotime($row['jam_selesai'])) ?> WIB</span>
        <span class="flex items-center"><i class="fas fa-user mr-1"></i><?= htmlspecialchars($row['penyelenggara'] ?? '-') ?></span>
        <span class="<?= $badge ?> px-3 py-1 rounded-full justify-self-end"><?= $statusLabel ?></span>
    </div>
</div>
<?php
    endwhile;
else:
    echo "<div class='p-4 bg-yellow-100 text-yellow-700 rounded'>Belum ada kegiatan.</div>";
endif;
?>

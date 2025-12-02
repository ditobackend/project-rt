<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// Proses update/hapus (letakkan di paling atas sebelum echo/HTML)
if (isset($_GET['aksi'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $aksi = $_GET['aksi'];

    if ($aksi === "diproses") {
        $conn->query("UPDATE pengaduan SET status='Diproses' WHERE id=$id");
    } elseif ($aksi === "selesai") {
        $conn->query("UPDATE pengaduan SET status='Selesai' WHERE id=$id");
    } elseif ($aksi === "hapus") {
        $conn->query("DELETE FROM pengaduan WHERE id=$id");
    }
}

// Ambil data pengaduan
$sql = "
    SELECT p.id, u.nama, p.judul, p.isi, p.status, p.created_at 
    FROM pengaduan p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
";
$result = $conn->query($sql);
?>

<h2 class="text-2xl font-bold mb-4">Pengaduan Warga</h2>
<p class="mb-6 text-gray-600">Kelola pengaduan warga</p>

<div class="bg-white shadow rounded-lg overflow-x-auto">
    <table class="w-full text-left min-w-[800px]">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-6 py-3">Tanggal</th>
                <th class="px-6 py-3">Nama</th>
                <th class="px-6 py-3">Judul</th>
                <th class="px-6 py-3">Deskripsi</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="border-t">
                <td class="px-6 py-3"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars($row['nama']) ?></td>
                <td class="px-6 py-3 font-semibold"><?= htmlspecialchars($row['judul']) ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars($row['isi']) ?></td>
                <td class="px-6 py-3">
                    <span class="px-2 py-1 rounded 
                        <?= $row['status']=='Diterima'?'bg-blue-100 text-blue-600':
                           ($row['status']=='Diproses'?'bg-yellow-100 text-yellow-600':'bg-green-100 text-green-600') ?>">
                        <?= $row['status'] ?>
                    </span>
                </td>
                <td class="px-6 py-3 space-x-2">
                    <?php if ($row['status'] === 'Diterima'): ?>
                        <a href="dashboard_admin.php?page=pengaduan&aksi=diproses&id=<?= $row['id'] ?>" class="text-yellow-600">Diproses</a>
                    <?php elseif ($row['status'] === 'Diproses'): ?>
                        <a href="dashboard_admin.php?page=pengaduan&aksi=selesai&id=<?= $row['id'] ?>" class="text-green-600">Selesai</a>
                    <?php endif; ?>
                    <a href="dashboard_admin.php?page=pengaduan&aksi=hapus&id=<?= $row['id'] ?>" 
                       onclick="return confirm('Yakin hapus pengaduan ini?')" 
                       class="text-red-600">Hapus</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// Proses update/hapus
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
    // Redirect to clear GET params
    header("Location: dashboard_admin.php?page=pengaduan");
    exit;
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

<div class="mb-10">
    <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Pengaduan Warga</h2>
    <p class="text-secondary-500 mt-1">Kelola dan respon setiap aspirasi maupun laporan dari warga.</p>
</div>

<!-- Table Container -->
<div class="bg-white rounded-[2rem] shadow-sm border border-secondary-100 overflow-hidden">
    <div class="overflow-x-auto max-h-[550px] overflow-y-auto custom-scrollbar">
        <table class="w-full text-left border-separate border-spacing-0">
            <thead class="sticky top-0 z-10">
                <tr class="bg-secondary-50">
                    <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100">Pelapor</th>
                    <th class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100">Judul Laporan</th>
                    <th class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100">Deskripsi</th>
                    <th class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100">Waktu</th>
                    <th class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100 text-center">Status</th>
                    <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-b border-secondary-100 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary-50">
                <?php if($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        $status = $row['status'];
                        $color = "blue";
                        if($status == 'Diproses') $color = "yellow";
                        if($status == 'Selesai') $color = "green";
                    ?>
                    <tr class="group hover:bg-primary-50/30 transition-all duration-300">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-secondary-100 flex items-center justify-center text-secondary-500 font-bold shrink-0">
                                    <?= substr($row['nama'], 0, 1) ?>
                                </div>
                                <p class="font-bold text-secondary-900"><?= htmlspecialchars($row['nama']) ?></p>
                            </div>
                        </td>
                        <td class="px-6 py-6 font-bold text-secondary-800 tracking-tight"><?= htmlspecialchars($row['judul']) ?></td>
                        <td class="px-6 py-6">
                            <p class="text-sm text-secondary-500 line-clamp-2 max-w-xs"><?= htmlspecialchars($row['isi']) ?></p>
                        </td>
                        <td class="px-6 py-6">
                            <p class="text-xs font-medium text-secondary-400">
                                <?php
                                $bulan = ['Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agt', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'];
                                echo strtr(date('d M Y, H:i', strtotime($row['created_at'])), $bulan);
                                ?>
                            </p>
                        </td>
                        <td class="px-6 py-6 text-center">
                            <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-<?= $color ?>-100 text-<?= $color ?>-600 ring-4 ring-<?= $color ?>-50">
                                <?= $status ?>
                            </span>
                        </td>
                        <td class="px-8 py-6 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <?php if ($status === 'Diterima'): ?>
                                    <a href="dashboard_admin.php?page=pengaduan&aksi=diproses&id=<?= $row['id'] ?>" class="px-4 py-2 bg-yellow-500 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-yellow-600 transition-all shadow-lg shadow-yellow-500/20">Proses</a>
                                <?php elseif ($status === 'Diproses'): ?>
                                    <a href="dashboard_admin.php?page=pengaduan&aksi=selesai&id=<?= $row['id'] ?>" class="px-4 py-2 bg-green-500 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-green-600 transition-all shadow-lg shadow-green-500/20">Selesai</a>
                                <?php endif; ?>
                                <a href="dashboard_admin.php?page=pengaduan&aksi=hapus&id=<?= $row['id'] ?>" 
                                   onclick="return confirm('Hapus laporan pengaduan ini?')" 
                                   class="p-2 text-secondary-300 hover:text-red-500 transition-colors">
                                   <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-8 py-20 text-center">
                            <i class="fas fa-comment-slash text-6xl text-secondary-100 mb-4 block"></i>
                            <p class="text-secondary-400 font-bold">Belum ada pengaduan warga.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

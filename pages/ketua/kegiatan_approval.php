<?php
// pages/ketua/kegiatan_approval.php

// Proses Persetujuan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_approval'])) {
    $id = $_POST['id'];
    $aksi = $_POST['aksi']; // 'disetujui' atau 'ditolak'
    
    $stmt = $conn->prepare("UPDATE kegiatan SET status_persetujuan = ? WHERE id = ?");
    $stmt->bind_param("si", $aksi, $id);
    
    if ($stmt->execute()) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Status pengajuan kegiatan telah diperbarui.',
                    icon: 'success',
                    confirmButtonColor: '#4f46e5',
                    customClass: {
                        popup: 'rounded-[2rem] shadow-xl border-0',
                        confirmButton: 'rounded-xl px-6 py-2.5 font-bold'
                    }
                }).then(() => {
                    window.location.href='?page=kegiatan_approval';
                });
            });
        </script>";
    }
}

// Ambil filter status
$filterStatus = $_GET['status'] ?? 'pending';

// Ambil kegiatan sesuai status
$sql = "SELECT k.*, u.nama as pemohon 
        FROM kegiatan k 
        LEFT JOIN users u ON k.diajukan_oleh = u.id 
        WHERE k.status_persetujuan = ? 
        ORDER BY k.created_at DESC";
$stmt_list = $conn->prepare($sql);
$stmt_list->bind_param("s", $filterStatus);
$stmt_list->execute();
$result = $stmt_list->get_result();
?>

<div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
    <div>
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Persetujuan Kegiatan</h2>
        <p class="text-secondary-500 mt-1">Evaluasi dan pantau riwayat pengajuan kegiatan warga.</p>
    </div>
    
    <!-- Filter Tabs -->
    <div class="flex bg-white p-1.5 rounded-2xl shadow-sm border border-secondary-100">
        <?php
        $tabs = [
            'pending' => ['label' => 'Menunggu', 'color' => 'orange'],
            'disetujui' => ['label' => 'Disetujui', 'color' => 'green'],
            'ditolak' => ['label' => 'Ditolak', 'color' => 'red']
        ];
        foreach($tabs as $val => $t):
            $active = ($filterStatus == $val);
        ?>
            <a href="?page=kegiatan_approval&status=<?= $val ?>" 
               class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all <?= $active ? "bg-{$t['color']}-500 text-white shadow-lg shadow-{$t['color']}-500/30" : "text-secondary-400 hover:text-secondary-600" ?>">
                <?= $t['label'] ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="grid grid-cols-1 gap-6">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-secondary-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 group hover:shadow-xl hover:shadow-secondary-900/5 transition-all">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <?php
                        $badgeColor = $row['status_persetujuan'] == 'pending' ? 'orange' : ($row['status_persetujuan'] == 'disetujui' ? 'green' : 'red');
                        $badgeLabel = $row['status_persetujuan'] == 'pending' ? 'Pengajuan Baru' : ($row['status_persetujuan'] == 'disetujui' ? 'Telah Disetujui' : 'Ditolak');
                        ?>
                        <span class="px-3 py-1 bg-<?= $badgeColor ?>-100 text-<?= $badgeColor ?>-600 text-[10px] font-black uppercase tracking-widest rounded-lg"><?= $badgeLabel ?></span>
                        <span class="text-secondary-400 text-xs font-bold"><i class="far fa-clock mr-1"></i> <?= date('d M Y', strtotime($row['created_at'])) ?></span>
                    </div>
                    <h3 class="text-xl font-black text-secondary-900 mb-2 group-hover:text-primary-600 transition-colors"><?= htmlspecialchars($row['judul']) ?></h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-8">
                        <div class="flex items-center gap-2 text-sm text-secondary-500">
                            <i class="fas fa-user text-primary-400 w-4"></i>
                            <span class="font-medium">Diajukan oleh: <span class="text-secondary-900 font-bold"><?= htmlspecialchars($row['pemohon'] ?? 'Warga') ?></span></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-secondary-500">
                            <i class="fas fa-map-marker-alt text-primary-400 w-4"></i>
                            <span class="font-medium">Lokasi: <span class="text-secondary-900 font-bold"><?= htmlspecialchars($row['tempat'] ?? '-') ?></span></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-secondary-500">
                            <i class="fas fa-calendar-day text-primary-400 w-4"></i>
                            <span class="font-medium">Waktu: <span class="text-secondary-900 font-bold"><?= date('d F Y', strtotime($row['tanggal'])) ?></span></span>
                        </div>
                    </div>
                    <p class="mt-4 text-sm text-secondary-500 leading-relaxed italic">"<?= htmlspecialchars($row['deskripsi']) ?>"</p>
                </div>

                <?php if ($filterStatus == 'pending'): ?>
                <div class="flex gap-3 shrink-0">
                    <form method="POST" class="inline">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="proses_approval" value="1">
                        <input type="hidden" name="aksi" value="ditolak">
                        <button type="submit" onclick="return confirm('Tolak kegiatan ini?')" class="w-12 h-12 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-2xl flex items-center justify-center transition-all shadow-sm">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                    
                    <form method="POST" class="inline">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="proses_approval" value="1">
                        <input type="hidden" name="aksi" value="disetujui">
                        <button type="submit" class="px-8 py-3 bg-primary-600 text-white font-black rounded-2xl hover:bg-primary-700 transition-all shadow-lg shadow-primary-500/20 uppercase tracking-widest text-xs flex items-center gap-3">
                            Setujui
                            <i class="fas fa-check"></i>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="bg-white p-20 rounded-[3rem] border border-dashed border-secondary-200 text-center">
            <div class="w-20 h-20 bg-secondary-50 text-secondary-200 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-clipboard-list text-3xl"></i>
            </div>
            <h3 class="text-xl font-black text-secondary-900 mb-2">Belum Ada Pengajuan</h3>
            <p class="text-secondary-500">Saat ini tidak ada rencana kegiatan warga yang menunggu persetujuan.</p>
        </div>
    <?php endif; ?>
</div>

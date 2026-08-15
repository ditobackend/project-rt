<?php
require_once __DIR__ . '/../../config/database.php';

// Cek Role, pastikan Ketua RT
if ($_SESSION['role'] !== 'ketua_rt') {
    echo "Akses Ditolak";
    exit;
}

// Handle Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id_surat = (int)$_POST['id_surat'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $sql = "UPDATE surat_pengantar SET status='disetujui', tanggal_disetujui=NOW() WHERE id=$id_surat";
        $conn->query($sql);
        header("Location: ?page=surat_masuk&msg=approved");
        exit;
    } elseif ($action === 'reject') {
        $alasan = $conn->real_escape_string($_POST['alasan_tolak']);
        $sql = "UPDATE surat_pengantar SET status='ditolak', keterangan_tolak='$alasan' WHERE id=$id_surat";
        $conn->query($sql);
        header("Location: ?page=surat_masuk&msg=rejected");
        exit;
    }
}

// Ambil semua data pengajuan
$query = $conn->query("
    SELECT sp.*, ak.nama_lengkap, ak.nik, ak.foto_ktp, kk.foto_kk, u.nama as nama_pengaju 
    FROM surat_pengantar sp 
    JOIN anggota_keluarga ak ON sp.anggota_keluarga_id = ak.id 
    JOIN kartu_keluarga kk ON ak.kk_id = kk.id
    JOIN users u ON sp.user_id = u.id 
    ORDER BY FIELD(sp.status, 'pending', 'disetujui', 'ditolak'), sp.tanggal_pengajuan DESC
");
?>

<div class="mb-10">
    <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Pengajuan Surat Warga</h2>
    <p class="text-secondary-500 mt-1">Kelola dan verifikasi permintaan surat pengantar dari warga.</p>
</div>



<div class="bg-white rounded-3xl shadow-sm border border-secondary-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-secondary-50 text-secondary-500 text-xs uppercase tracking-wider border-b border-secondary-100">
                    <th class="p-4 font-black">Waktu</th>
                    <th class="p-4 font-black">Warga (KK)</th>
                    <th class="p-4 font-black">Untuk Anggota</th>
                    <th class="p-4 font-black">Detail Keperluan</th>
                    <th class="p-4 font-black text-center">Status</th>
                    <th class="p-4 font-black text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary-50 text-sm">
                <?php if ($query->num_rows > 0): ?>
                    <?php while ($row = $query->fetch_assoc()): ?>
                        <tr class="hover:bg-secondary-50/50 transition-colors">
                            <td class="p-4 text-secondary-500 font-medium whitespace-nowrap">
                                <?= date('d M Y', strtotime($row['tanggal_pengajuan'])) ?><br>
                                <span class="text-xs"><?= date('H:i', strtotime($row['tanggal_pengajuan'])) ?> WIB</span>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-secondary-900"><?= htmlspecialchars($row['nama_pengaju']) ?></p>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-secondary-700"><?= htmlspecialchars($row['nama_lengkap']) ?></p>
                                <p class="text-xs text-secondary-500 font-mono"><?= htmlspecialchars($row['nik']) ?></p>
                                <div class="flex gap-2 mt-2">
                                    <?php if($row['foto_ktp']): ?>
                                        <a href="<?= htmlspecialchars($row['foto_ktp']) ?>" target="_blank" class="text-[10px] font-bold bg-secondary-100 text-secondary-600 px-2 py-1 rounded hover:bg-secondary-200">Cek KTP</a>
                                    <?php endif; ?>
                                    <?php if($row['foto_kk']): ?>
                                        <a href="<?= htmlspecialchars($row['foto_kk']) ?>" target="_blank" class="text-[10px] font-bold bg-secondary-100 text-secondary-600 px-2 py-1 rounded hover:bg-secondary-200">Cek KK</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-primary-700"><?= htmlspecialchars($row['jenis_surat']) ?></p>
                                <p class="text-xs text-secondary-600 mt-1 max-w-xs break-words"><?= htmlspecialchars($row['keperluan']) ?></p>
                            </td>
                            <td class="p-4 text-center">
                                <?php if ($row['status'] == 'pending'): ?>
                                    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold inline-flex items-center gap-1">
                                        <i class="fas fa-clock"></i> Menunggu
                                    </span>
                                <?php elseif ($row['status'] == 'disetujui'): ?>
                                    <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold inline-flex items-center gap-1">
                                        <i class="fas fa-check"></i> Disetujui
                                    </span>
                                <?php elseif ($row['status'] == 'ditolak'): ?>
                                    <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold inline-flex items-center gap-1" title="<?= htmlspecialchars($row['keterangan_tolak']) ?>">
                                        <i class="fas fa-times"></i> Ditolak
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center">
                                <?php if ($row['status'] == 'pending'): ?>
                                    <div class="flex flex-col gap-2">
                                        <form method="POST" onsubmit="confirmApprove(event, this)">
                                            <input type="hidden" name="id_surat" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="w-full px-3 py-1.5 bg-emerald-500 text-white hover:bg-emerald-600 rounded-lg font-bold text-xs shadow-md shadow-emerald-500/20 transition-all">Setujui</button>
                                        </form>
                                        <button onclick="openTolakModal(<?= $row['id'] ?>)" class="w-full px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white rounded-lg font-bold text-xs transition-all">Tolak</button>
                                    </div>
                                <?php elseif ($row['status'] == 'disetujui'): ?>
                                    <a href="cetak_surat.php?id=<?= $row['id'] ?>" target="_blank" class="px-3 py-1.5 bg-primary-50 text-primary-600 hover:bg-primary-600 hover:text-white rounded-lg font-bold text-xs transition-colors inline-block">
                                        <i class="fas fa-print mr-1"></i> Cetak Surat
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="p-8 text-center text-secondary-400 font-medium">Belum ada pengajuan surat.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tolak -->
<div id="modalTolak" class="fixed inset-0 z-50 hidden bg-secondary-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm flex flex-col overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="modalTolakContent">
        <div class="p-6 border-b border-secondary-100 flex justify-between items-center bg-red-50">
            <h3 class="text-lg font-black text-red-600">Tolak Pengajuan</h3>
            <button onclick="closeTolakModal()" class="text-red-400 hover:text-red-600 transition-colors"><i class="fas fa-times text-xl"></i></button>
        </div>
        
        <div class="p-6">
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id_surat" id="tolak_id_surat">
                
                <label class="block text-xs font-bold text-secondary-600 mb-2">Alasan Penolakan *</label>
                <textarea name="alasan_tolak" required rows="3" placeholder="Misal: Foto KTP tidak jelas / Dokumen kurang..." class="w-full px-4 py-3 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-red-500 transition-all text-secondary-900 font-medium resize-none mb-4"></textarea>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeTolakModal()" class="px-5 py-2 text-secondary-600 font-bold hover:bg-secondary-200 rounded-xl transition-colors text-sm">Batal</button>
                    <button type="submit" class="px-6 py-2 bg-red-600 text-white font-bold rounded-xl shadow-lg shadow-red-600/30 hover:bg-red-700 active:scale-95 transition-all text-sm">Tolak Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const modalTolak = document.getElementById('modalTolak');
    const modalTolakContent = document.getElementById('modalTolakContent');
    
    function openTolakModal(id) {
        document.getElementById('tolak_id_surat').value = id;
        modalTolak.classList.remove('hidden');
        setTimeout(() => {
            modalTolakContent.classList.remove('scale-95', 'opacity-0');
            modalTolakContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
    
    function closeTolakModal() {
        modalTolakContent.classList.remove('scale-100', 'opacity-100');
        modalTolakContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modalTolak.classList.add('hidden');
        }, 300);
    }

    function confirmApprove(e, form) {
        e.preventDefault();
        Swal.fire({
            title: 'Setujui Surat?',
            text: "Apakah Anda yakin ingin menyetujui pengajuan surat ini?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'rounded-3xl shadow-xl border-0',
                confirmButton: 'rounded-xl px-6 py-2.5 font-bold',
                cancelButton: 'rounded-xl px-6 py-2.5 font-bold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');
        if (msg) {
            let title = '';
            let text = '';
            let icon = 'success';
            
            if (msg === 'approved') {
                title = 'Disetujui!';
                text = 'Surat pengajuan berhasil disetujui.';
            } else if (msg === 'rejected') {
                title = 'Ditolak!';
                text = 'Surat pengajuan telah ditolak.';
                icon = 'info';
            }
            
            if (title) {
                Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    confirmButtonColor: '#059669',
                    confirmButtonText: 'Tutup',
                    customClass: {
                        popup: 'rounded-3xl shadow-xl border-0',
                        confirmButton: 'rounded-xl px-6 py-2.5 font-bold'
                    }
                });
                
                window.history.replaceState(null, null, window.location.pathname + "?page=surat_masuk");
            }
        }
    });
</script>

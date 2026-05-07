<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

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
            <tbody class="divide-y divide-secondary-50" id="pengaduanTableBody">
                <?php if($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()):
                        $status    = $row['status'];
                        $statusLow = strtolower($status);
                        // Warna badge
                        $colorMap  = ['diterima' => 'blue', 'diproses' => 'yellow', 'selesai' => 'green'];
                        $color     = $colorMap[$statusLow] ?? 'blue';
                        // Tooltip tombol
                        $nextLabel = ['diterima' => 'Klik → Diproses', 'diproses' => 'Klik → Selesai', 'selesai' => 'Status Final'];
                        $tooltip   = $nextLabel[$statusLow] ?? '';
                        $isFinal   = ($statusLow === 'selesai');
                    ?>
                    <tr class="group hover:bg-primary-50/30 transition-all duration-300" id="row-<?= $row['id'] ?>">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-secondary-100 flex items-center justify-center text-secondary-500 font-bold shrink-0">
                                    <?= strtoupper(substr($row['nama'], 0, 1)) ?>
                                </div>
                                <p class="font-bold text-secondary-900"><?= htmlspecialchars($row['nama']) ?></p>
                            </div>
                        </td>
                        <td class="px-6 py-6 font-bold text-secondary-800 tracking-tight"><?= htmlspecialchars($row['judul'] ?? '-') ?></td>
                        <td class="px-6 py-6">
                            <p class="text-sm text-secondary-500 line-clamp-2 max-w-xs"><?= htmlspecialchars($row['isi']) ?></p>
                        </td>
                        <td class="px-6 py-6">
                            <p class="text-xs font-medium text-secondary-400">
                                <?php
                                $bulan = ['Jan'=>'Jan','Feb'=>'Feb','Mar'=>'Mar','Apr'=>'Apr','May'=>'Mei','Jun'=>'Jun','Jul'=>'Jul','Aug'=>'Agt','Sep'=>'Sep','Oct'=>'Okt','Nov'=>'Nov','Dec'=>'Des'];
                                echo strtr(date('d M Y, H:i', strtotime($row['created_at'])), $bulan);
                                ?>
                            </p>
                        </td>

                        <!-- STATUS BUTTON -->
                        <td class="px-6 py-6 text-center">
                            <?php if (!$isFinal): ?>
                            <button
                                onclick="updateStatus(<?= $row['id'] ?>, this)"
                                data-status="<?= htmlspecialchars($status) ?>"
                                title="<?= $tooltip ?>"
                                class="status-btn inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest
                                       bg-<?= $color ?>-100 text-<?= $color ?>-700 ring-4 ring-<?= $color ?>-50
                                       hover:ring-<?= $color ?>-200 hover:scale-105 active:scale-95
                                       cursor-pointer transition-all duration-200 select-none">
                                <span class="status-text"><?= $status ?></span>
                                <i class="fas fa-chevron-right text-[8px] opacity-60"></i>
                            </button>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest
                                         bg-green-100 text-green-700 ring-4 ring-green-50 cursor-default">
                                <i class="fas fa-check-circle text-[9px]"></i>
                                <span>Selesai</span>
                            </span>
                            <?php endif; ?>
                        </td>

                        <!-- AKSI (hapus saja) -->
                        <td class="px-8 py-6 text-right">
                            <button onclick="hapusPengaduan(<?= $row['id'] ?>)"
                                class="p-2 text-secondary-300 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr id="emptyRow">
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

<script>
// Keys = lowercase (sesuai nilai di database)
const STATUS_CONFIG = {
    'diterima': { next: 'Diproses', color: 'blue',   label: 'Tandai Diproses?' },
    'diproses': { next: 'Selesai',  color: 'yellow', label: 'Tandai Selesai?' },
};
// Keys = kapitalisasi (sesuai respons server)
const STATUS_CLASS = {
    'Diproses': 'bg-yellow-100 text-yellow-700 ring-4 ring-yellow-50 hover:ring-yellow-200',
    'Selesai':  'bg-green-100 text-green-700 ring-4 ring-green-50',
};

function updateStatus(id, btn) {
    // Normalize ke lowercase agar cocok dengan nilai DB
    const currentStatus = (btn.dataset.status || '').toLowerCase();
    const cfg = STATUS_CONFIG[currentStatus];
    if (!cfg) return;

    Swal.fire({
        title: cfg.label,
        text: `Laporan akan diupdate menjadi "${cfg.next}".`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Ya, Update',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-[2rem] shadow-xl border-0',
            confirmButton: 'rounded-xl px-6 py-2.5 font-bold',
            cancelButton: 'rounded-xl px-6 py-2.5 font-bold'
        }
    }).then(result => {
        if (!result.isConfirmed) return;

        // Loading state
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-wait');

        const form = new FormData();
        form.append('ajax_action', 'update_status');
        form.append('id', id);

        fetch('pengaduan_ajax.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: data.message, customClass: { popup: 'rounded-[2rem]' } });
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-wait');
                    return;
                }

                const newStatus = data.new_status;
                const row = document.getElementById('row-' + id);
                const td = btn.parentElement;

                if (newStatus === 'Selesai') {
                    // Ganti button → badge final
                    td.innerHTML = `<span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest bg-green-100 text-green-700 ring-4 ring-green-50 cursor-default"><i class="fas fa-check-circle text-[9px]"></i><span>Selesai</span></span>`;
                } else {
                    // Update button
                    const cls = STATUS_CLASS[newStatus] ?? '';
                    btn.className = `status-btn inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest ${cls} hover:scale-105 active:scale-95 cursor-pointer transition-all duration-200 select-none`;
                    btn.dataset.status = newStatus;
                    btn.querySelector('.status-text').textContent = newStatus;
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-wait');
                }

                // Row pulse animation
                row.classList.add('bg-emerald-50/60');
                setTimeout(() => row.classList.remove('bg-emerald-50/60'), 1500);

                Swal.mixin({ toast:true, position:'top-end', showConfirmButton:false, timer:2500, timerProgressBar:true })
                    .fire({ icon:'success', title:`Status diubah ke "${newStatus}"` });
            })
            .catch(() => {
                Swal.fire({ icon:'error', title:'Koneksi Gagal', text:'Coba lagi.', customClass:{popup:'rounded-[2rem]'} });
                btn.disabled = false;
                btn.classList.remove('opacity-50','cursor-wait');
            });
    });
}

function hapusPengaduan(id) {
    Swal.fire({
        title: 'Hapus Laporan?',
        text: 'Data tidak dapat dikembalikan setelah dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-[2rem] shadow-xl border-0',
            confirmButton: 'rounded-xl px-6 py-2.5 font-bold',
            cancelButton: 'rounded-xl px-6 py-2.5 font-bold'
        }
    }).then(result => {
        if (!result.isConfirmed) return;

        const form = new FormData();
        form.append('ajax_action', 'hapus');
        form.append('id', id);

        fetch('pengaduan_ajax.php', { method:'POST', body:form })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('row-' + id);
                    row.style.transition = 'opacity 0.4s, transform 0.4s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(30px)';
                    setTimeout(() => {
                        row.remove();
                        const tbody = document.getElementById('pengaduanTableBody');
                        if (!tbody.querySelector('tr[id^="row-"]')) {
                            tbody.innerHTML = `<tr id="emptyRow"><td colspan="6" class="px-8 py-20 text-center"><i class="fas fa-comment-slash text-6xl text-secondary-100 mb-4 block"></i><p class="text-secondary-400 font-bold">Belum ada pengaduan warga.</p></td></tr>`;
                        }
                    }, 400);
                    Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2000}).fire({icon:'success',title:'Laporan dihapus.'});
                }
            });
    });
}
</script>

<?php
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ketua_rt') {
    header("Location: ../../loginketua.php"); exit;
}

// ============================================================
// AJAX: ambil data anggota untuk modal detail
// ============================================================
if (isset($_GET['ajax_kk'])) {
    ob_clean(); // Bersihkan buffer HTML sebelumnya
    $kk_id_ajax = (int)$_GET['ajax_kk'];
    $kk_data = $conn->query("SELECT * FROM kartu_keluarga WHERE id=$kk_id_ajax")->fetch_assoc();
    $anggota_data = $conn->query("SELECT * FROM anggota_keluarga WHERE kk_id=$kk_id_ajax ORDER BY FIELD(status_hubungan,'Kepala Keluarga','Suami','Istri','Anak','Cucu','Orang Tua','Mertua','Famili Lain','Pembantu','Lainnya'), tanggal_lahir ASC");
    $anggota_arr = [];
    while ($a = $anggota_data->fetch_assoc()) $anggota_arr[] = $a;
    header('Content-Type: application/json');
    echo json_encode(['kk' => $kk_data, 'anggota' => $anggota_arr]);
    exit;
}

// ============================================================
// Ambil data KK
// ============================================================
$kk_list = $conn->query("
    SELECT kk.*, u.nama as nama_user, u.email,
           COUNT(ak.id) as jumlah_anggota
    FROM kartu_keluarga kk
    JOIN users u ON kk.user_id = u.id
    LEFT JOIN anggota_keluarga ak ON ak.kk_id = kk.id
    GROUP BY kk.id
    ORDER BY kk.created_at DESC
");
?>

<!-- Header -->
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Monitoring Data KK</h2>
        <p class="text-secondary-500 mt-1">Pantau Kartu Keluarga dan data anggota warga RT secara detail.</p>
    </div>
</div>

<!-- Stats Bar -->
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
    <?php
    $total_kk = $conn->query("SELECT COUNT(*) as c FROM kartu_keluarga")->fetch_assoc()['c'] ?? 0;
    $total_anggota = $conn->query("SELECT COUNT(*) as c FROM anggota_keluarga")->fetch_assoc()['c'] ?? 0;
    $total_warga = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='warga'")->fetch_assoc()['c'] ?? 0;
    ?>
    <div class="bg-white rounded-2xl p-4 border border-secondary-100 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center">
            <i class="fas fa-id-card"></i>
        </div>
        <div>
            <p class="text-xs text-secondary-400 font-bold uppercase tracking-wide">Total KK</p>
            <p class="text-2xl font-black text-secondary-900"><?= $total_kk ?></p>
        </div>
    </div>
    <div class="bg-white rounded-2xl p-4 border border-secondary-100 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <p class="text-xs text-secondary-400 font-bold uppercase tracking-wide">Total Anggota</p>
            <p class="text-2xl font-black text-secondary-900"><?= $total_anggota ?></p>
        </div>
    </div>
    <div class="bg-white rounded-2xl p-4 border border-secondary-100 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center">
            <i class="fas fa-user-clock"></i>
        </div>
        <div>
            <p class="text-xs text-secondary-400 font-bold uppercase tracking-wide">Warga Tanpa KK</p>
            <p class="text-2xl font-black text-secondary-900"><?= $total_warga - $total_kk ?></p>
        </div>
    </div>
</div>

<!-- Grid Card KK -->
<?php if ($kk_list && $kk_list->num_rows > 0): ?>
<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php while ($kk = $kk_list->fetch_assoc()): ?>
    <div class="bg-white rounded-3xl border border-secondary-100 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden group cursor-pointer"
         onclick="openDetailKK(<?= $kk['id'] ?>)"
         id="card-kk-<?= $kk['id'] ?>">

        <!-- Header Card -->
        <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="absolute -right-1 top-8 w-12 h-12 bg-white/5 rounded-full"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-black text-indigo-200 uppercase tracking-widest">Kartu Keluarga</span>
                    <span class="bg-white/20 text-white text-xs font-bold px-3 py-1 rounded-full">
                        <?= $kk['jumlah_anggota'] ?> Anggota
                    </span>
                </div>
                <p class="text-white font-black text-lg leading-tight"><?= htmlspecialchars($kk['nama_kepala_keluarga']) ?></p>
                <p class="text-indigo-200 text-xs font-mono mt-1 tracking-widest"><?= htmlspecialchars($kk['no_kk']) ?></p>
            </div>
        </div>

        <!-- Body Card -->
        <div class="p-5">
            <div class="flex items-start gap-2 mb-3">
                <i class="fas fa-map-marker-alt text-secondary-400 mt-0.5 text-sm flex-shrink-0"></i>
                <p class="text-secondary-600 text-sm leading-snug">
                    <?= htmlspecialchars($kk['alamat']) ?>, RT <?= $kk['rt'] ?>/RW <?= $kk['rw'] ?>,
                    <?= htmlspecialchars($kk['desa_kelurahan']) ?>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-xs font-black flex items-center justify-center flex-shrink-0">
                    <?= strtoupper(substr($kk['nama_user'], 0, 1)) ?>
                </div>
                <span class="text-secondary-500 text-xs font-medium"><?= htmlspecialchars($kk['nama_user']) ?> · <?= htmlspecialchars($kk['email']) ?></span>
            </div>
        </div>

        <!-- Footer Card -->
        <div class="px-5 pb-4 flex items-center justify-between">
            <span class="text-indigo-600 text-xs font-bold flex items-center gap-1">
                <i class="fas fa-mouse-pointer"></i> Klik untuk lihat anggota
            </span>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php else: ?>
<div class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-secondary-100">
    <div class="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mb-4">
        <i class="fas fa-id-card text-3xl text-indigo-300"></i>
    </div>
    <h3 class="text-xl font-bold text-secondary-400 mb-1">Belum Ada Data KK</h3>
    <p class="text-secondary-400 text-sm">Menunggu admin menginput data.</p>
</div>
<?php endif; ?>

<!-- ================================================================ -->
<!-- MODAL DETAIL KK (Daftar Anggota) -->
<!-- ================================================================ -->
<div id="modalDetail" class="fixed inset-0 z-50 hidden bg-secondary-900/60 backdrop-blur-sm flex items-start justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl my-8 flex flex-col overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="modalDetailContent">
        <div class="p-6 border-b border-secondary-100 flex justify-between items-center bg-white sticky top-0 z-10 shadow-sm">
            <div>
                <h3 class="text-xl font-black text-secondary-900" id="modalDetailTitle">Anggota KK</h3>
                <p class="text-secondary-500 text-xs mt-1 tracking-wide" id="modalDetailSubtitle"></p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeModalDetail()" class="text-secondary-400 hover:text-secondary-700 transition-colors bg-secondary-50 hover:bg-secondary-100 w-10 h-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-y-auto bg-white/50" id="modalDetailBody">
            <div class="text-center py-8 text-secondary-400">
                <i class="fas fa-spinner fa-spin text-2xl"></i>
                <p class="mt-2 text-sm">Memuat data...</p>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- MODAL: View Detail Anggota Spesifik -->
<!-- ================================================================ -->
<div id="modalViewMember" class="fixed inset-0 z-[60] hidden bg-secondary-900/60 backdrop-blur-sm flex items-start justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl my-8 flex flex-col overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="modalViewMemberContent">
        <div class="p-6 border-b border-secondary-100 flex justify-between items-center bg-primary-50">
            <div>
                <h3 class="text-lg font-black text-primary-900">Detail Anggota Keluarga</h3>
                <p class="text-primary-500 text-xs mt-0.5">Informasi lengkap warga</p>
            </div>
            <button onclick="closeModalViewMember()" class="text-primary-400 hover:text-primary-700 transition-colors"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6 overflow-y-auto" id="modalViewMemberBody">
            <!-- Injected via JS -->
        </div>
    </div>
</div>

<script>
// ============================================================
// MODAL DETAIL KK (Lihat Daftar Anggota)
// ============================================================
const modalDetail = document.getElementById('modalDetail');
const modalDetailContent = document.getElementById('modalDetailContent');

function openDetailKK(kkId) {
    modalDetail.classList.remove('hidden');
    setTimeout(() => {
        modalDetailContent.classList.remove('scale-95','opacity-0');
        modalDetailContent.classList.add('scale-100','opacity-100');
    }, 10);
    document.getElementById('modalDetailBody').innerHTML = '<div class="text-center py-8 text-secondary-400"><i class="fas fa-spinner fa-spin text-2xl"></i><p class="mt-2 text-sm">Memuat data...</p></div>';

    fetch(`?page=data_kk&ajax_kk=${kkId}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('modalDetailTitle').textContent = 'Anggota KK: ' + data.kk.nama_kepala_keluarga;
            document.getElementById('modalDetailSubtitle').textContent = 'No. KK: ' + data.kk.no_kk + ' · ' + data.kk.alamat + ', RT ' + data.kk.rt + '/RW ' + data.kk.rw;
            renderAnggotaTable(data.anggota, kkId);
        })
        .catch(err => {
            document.getElementById('modalDetailBody').innerHTML = '<div class="text-center py-8 text-red-500">Terjadi kesalahan saat memuat data.</div>';
        });
}

function renderAnggotaTable(anggota, kkId) {
    if (anggota.length === 0) {
        document.getElementById('modalDetailBody').innerHTML = `
            <div class="text-center py-10">
                <div class="w-16 h-16 bg-secondary-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-users text-2xl text-secondary-300"></i>
                </div>
                <p class="text-secondary-400 font-medium">Belum ada anggota keluarga terdaftar.</p>
            </div>`;
        return;
    }

    let html = `<div class="overflow-x-auto"><table class="w-full text-left border-collapse text-sm">
        <thead><tr class="bg-secondary-50 text-secondary-500 text-xs uppercase tracking-wider">
            <th class="px-3 py-3 font-black rounded-l-xl">Nama / NIK</th>
            <th class="px-3 py-3 font-black">Hub. Keluarga</th>
            <th class="px-3 py-3 font-black">TTL</th>
            <th class="px-3 py-3 font-black">Pekerjaan</th>
            <th class="px-3 py-3 font-black text-center rounded-r-xl">Aksi</th>
        </tr></thead>
        <tbody class="divide-y divide-secondary-50">`;

    anggota.forEach(a => {
        html += `<tr class="hover:bg-secondary-50/50 transition-colors">
            <td class="px-3 py-3">
                <p class="font-bold text-secondary-900">${a.nama_lengkap}</p>
                <p class="text-xs text-secondary-400 font-mono">${a.nik}</p>
            </td>
            <td class="px-3 py-3 text-secondary-600 font-medium">${a.status_hubungan}</td>
            <td class="px-3 py-3 text-secondary-600 text-xs">${a.tempat_lahir},<br>${a.tanggal_lahir}</td>
            <td class="px-3 py-3 text-secondary-600 text-xs">${a.jenis_pekerjaan || '-'}</td>
            <td class="px-3 py-3 text-center">
                <button onclick="viewMemberDetail(${JSON.stringify(a).replace(/"/g,'&quot;')})" class="text-primary-600 hover:text-primary-800 bg-primary-50 hover:bg-primary-100 px-3 py-1.5 rounded-lg transition-colors font-bold text-xs" title="Lihat Detail">
                    <i class="fas fa-eye mr-1"></i> View
                </button>
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    document.getElementById('modalDetailBody').innerHTML = html;
}

function closeModalDetail() {
    modalDetailContent.classList.remove('scale-100','opacity-100');
    modalDetailContent.classList.add('scale-95','opacity-0');
    setTimeout(() => modalDetail.classList.add('hidden'), 300);
}

// ============================================================
// MODAL VIEW DETAIL MEMBER
// ============================================================
const modalViewMember = document.getElementById('modalViewMember');
const modalViewMemberContent = document.getElementById('modalViewMemberContent');

function viewMemberDetail(data) {
    let fotoHtml = '';
    if (data.foto_ktp || data.foto_paspor || data.foto_kitas) {
        fotoHtml = `
            <div class="mt-6 mb-2">
                <h4 class="text-xs font-black text-secondary-400 uppercase tracking-widest mb-3 border-b border-secondary-100 pb-2">Dokumen Terlampir</h4>
                <div class="flex flex-wrap gap-4">
                    ${data.foto_ktp ? `
                    <a href="${data.foto_ktp}" target="_blank" class="block w-40 h-28 rounded-xl overflow-hidden border-2 border-secondary-100 shadow-sm hover:shadow-md transition-all relative group">
                        <img src="${data.foto_ktp}" alt="KTP" class="w-full h-full object-cover">
                        <span class="absolute bottom-0 inset-x-0 bg-black/60 text-white text-[10px] font-bold text-center py-1">Kartu Identitas / KTP</span>
                    </a>` : ''}
                    ${data.foto_paspor ? `
                    <a href="${data.foto_paspor}" target="_blank" class="block w-40 h-28 rounded-xl overflow-hidden border-2 border-secondary-100 shadow-sm hover:shadow-md transition-all relative group">
                        <img src="${data.foto_paspor}" alt="Paspor" class="w-full h-full object-cover">
                        <span class="absolute bottom-0 inset-x-0 bg-blue-900/70 text-white text-[10px] font-bold text-center py-1">Paspor</span>
                    </a>` : ''}
                    ${data.foto_kitas ? `
                    <a href="${data.foto_kitas}" target="_blank" class="block w-40 h-28 rounded-xl overflow-hidden border-2 border-secondary-100 shadow-sm hover:shadow-md transition-all relative group">
                        <img src="${data.foto_kitas}" alt="KITAS" class="w-full h-full object-cover">
                        <span class="absolute bottom-0 inset-x-0 bg-purple-900/70 text-white text-[10px] font-bold text-center py-1">KITAS / KITAP</span>
                    </a>` : ''}
                </div>
            </div>
        `;
    }

    const html = `
        <div class="grid grid-cols-2 gap-y-4 gap-x-6">
            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">NIK</p>
                <p class="font-mono text-secondary-900 font-bold">${data.nik}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Nama Lengkap</p>
                <p class="text-secondary-900 font-bold">${data.nama_lengkap}</p>
            </div>
            
            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Tempat, Tanggal Lahir</p>
                <p class="text-secondary-800">${data.tempat_lahir}, ${data.tanggal_lahir}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Jenis Kelamin</p>
                <p class="text-secondary-800">${data.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</p>
            </div>
            
            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Golongan Darah</p>
                <p class="text-secondary-800 font-bold">${data.golongan_darah || '-'}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Agama</p>
                <p class="text-secondary-800">${data.agama || '-'}</p>
            </div>

            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Pendidikan</p>
                <p class="text-secondary-800">${data.pendidikan || '-'}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Jenis Pekerjaan</p>
                <p class="text-secondary-800">${data.jenis_pekerjaan || '-'}</p>
            </div>

            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Status Perkawinan</p>
                <p class="text-secondary-800">${data.status_perkawinan || '-'}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Status Hubungan</p>
                <p class="text-secondary-800 font-bold text-primary-600">${data.status_hubungan}</p>
            </div>

            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Kewarganegaraan</p>
                <p class="text-secondary-800">${data.kewarganegaraan || '-'}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Diinput Oleh</p>
                <p class="text-secondary-800 uppercase text-xs font-bold">${data.diinput_oleh}</p>
            </div>

            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">No. Paspor</p>
                <p class="text-secondary-800">${data.no_paspor || '-'}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">No. KITAS / KITAP</p>
                <p class="text-secondary-800">${data.no_kitas || '-'}</p>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-secondary-100 grid grid-cols-2 gap-x-6">
            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Nama Ayah</p>
                <p class="text-secondary-800">${data.nama_ayah || '-'}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Nama Ibu</p>
                <p class="text-secondary-800">${data.nama_ibu || '-'}</p>
            </div>
        </div>

        ${fotoHtml}
    `;

    document.getElementById('modalViewMemberBody').innerHTML = html;
    
    modalViewMember.classList.remove('hidden');
    setTimeout(() => {
        modalViewMemberContent.classList.remove('scale-95','opacity-0');
        modalViewMemberContent.classList.add('scale-100','opacity-100');
    }, 10);
}

function closeModalViewMember() {
    modalViewMemberContent.classList.remove('scale-100','opacity-100');
    modalViewMemberContent.classList.add('scale-95','opacity-0');
    setTimeout(() => modalViewMember.classList.add('hidden'), 300);
}
</script>

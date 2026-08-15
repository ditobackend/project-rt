<?php
require_once __DIR__ . '/../../config/database.php';

$user_id = $_SESSION['user_id'];

// ============================================================
// Cek apakah warga sudah punya KK (diinput admin)
// ============================================================
$kk_query = $conn->query("SELECT * FROM kartu_keluarga WHERE user_id = $user_id LIMIT 1");
$kk_data  = $kk_query ? $kk_query->fetch_assoc() : null;
$anggota_query = null;
if ($kk_data) {
    $kk_id = $kk_data['id'];
    
    // Ambil semua anggota keluarga di KK ini
    $anggota_query = $conn->query("
        SELECT * FROM anggota_keluarga 
        WHERE kk_id = $kk_id 
        ORDER BY FIELD(status_hubungan,'Kepala Keluarga','Suami','Istri','Anak','Cucu','Orang Tua','Mertua','Famili Lain','Pembantu','Lainnya'), tanggal_lahir ASC
    ");
}
?>



<!-- ============================================================ -->
<!-- PAGE HEADER -->
<!-- ============================================================ -->
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Data Keluarga</h2>
        <p class="text-secondary-500 mt-1">Anggota keluarga Anda sesuai Kartu Keluarga.</p>
    </div>
</div>

<?php if (!$kk_data): ?>
<!-- ============================================================ -->
<!-- BELUM ADA KK — tampilkan pesan info -->
<!-- ============================================================ -->
<div class="bg-white rounded-3xl border border-secondary-100 shadow-sm p-12 flex flex-col items-center justify-center text-center">
    <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mb-4">
        <i class="fas fa-id-card text-3xl text-amber-400"></i>
    </div>
    <h3 class="text-xl font-bold text-secondary-700 mb-2">Kartu Keluarga Belum Terdaftar</h3>
    <p class="text-secondary-400 text-sm max-w-sm leading-relaxed">
        Data Kartu Keluarga Anda belum diinput oleh Admin RT. Silakan hubungi Admin RT untuk pendaftaran data KK Anda terlebih dahulu.
    </p>
    <div class="mt-6 p-4 bg-indigo-50 rounded-2xl border border-indigo-100 text-left max-w-xs">
        <p class="text-xs font-black text-indigo-600 uppercase tracking-wider mb-2"><i class="fas fa-info-circle mr-1"></i>Alur Pendaftaran</p>
        <ol class="text-xs text-indigo-700 space-y-1 list-decimal list-inside">
            <li>Hubungi Admin RT dengan membawa KK & KTP</li>
            <li>Admin mendaftarkan data KK Anda</li>
            <li>Anda bisa menambahkan anggota keluarga</li>
            <li>Pengajuan surat dapat dilakukan</li>
        </ol>
    </div>
</div>

<?php else: ?>
<!-- ============================================================ -->
<!-- INFO KK WARGA -->
<!-- ============================================================ -->
<div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-3xl p-6 mb-6 text-white relative overflow-hidden">
    <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full"></div>
    <div class="absolute -right-2 top-12 w-16 h-16 bg-white/5 rounded-full"></div>
    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-indigo-200 text-xs font-black uppercase tracking-widest mb-1">Kartu Keluarga Anda</p>
            <h3 class="text-xl font-black"><?= htmlspecialchars($kk_data['nama_kepala_keluarga']) ?></h3>
            <p class="text-indigo-200 font-mono text-sm tracking-widest mt-1"><?= htmlspecialchars($kk_data['no_kk']) ?></p>
        </div>
        <div class="text-sm text-indigo-100 space-y-0.5 md:text-right">
            <p><i class="fas fa-map-marker-alt mr-1 text-indigo-300"></i><?= htmlspecialchars($kk_data['alamat']) ?></p>
            <p>RT <?= $kk_data['rt'] ?> / RW <?= $kk_data['rw'] ?>, <?= htmlspecialchars($kk_data['desa_kelurahan']) ?></p>
            <p><?= htmlspecialchars($kk_data['kecamatan']) ?>, <?= htmlspecialchars($kk_data['kabupaten_kota']) ?></p>
            <p><?= htmlspecialchars($kk_data['provinsi']) ?><?= $kk_data['kode_pos'] ? ' · ' . $kk_data['kode_pos'] : '' ?></p>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- GRID ANGGOTA KELUARGA -->
<!-- ============================================================ -->
<?php if ($anggota_query && $anggota_query->num_rows > 0): ?>
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php while ($row = $anggota_query->fetch_assoc()): ?>
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary-100 relative group overflow-hidden">
        <div class="absolute right-0 top-0 w-24 h-24 bg-primary-50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform duration-300"></div>

        <!-- Header -->
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 bg-primary-100 text-primary-700 text-xs font-black rounded-lg uppercase tracking-wider">
                    <?= htmlspecialchars($row['status_hubungan']) ?>
                </span>
                <?php if ($row['diinput_oleh'] === 'admin'): ?>
                <span class="px-2 py-1 bg-indigo-100 text-indigo-600 text-[9px] font-black rounded-lg uppercase tracking-wider">
                    <i class="fas fa-shield-alt mr-0.5"></i> Resmi
                </span>
                <?php else: ?>
                <span class="px-2 py-1 bg-amber-100 text-amber-600 text-[9px] font-black rounded-lg uppercase tracking-wider">
                    Ditambahkan Anda
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Nama & NIK -->
        <h3 class="text-lg font-bold text-secondary-900 mb-0.5"><?= htmlspecialchars($row['nama_lengkap']) ?></h3>
        <p class="text-secondary-400 font-mono text-xs mb-4"><i class="far fa-id-card mr-1.5"></i><?= htmlspecialchars($row['nik']) ?></p>

        <!-- Info -->
        <div class="space-y-1.5 mb-4">
            <div class="flex text-sm">
                <span class="w-1/3 text-secondary-400 text-xs">Lahir</span>
                <span class="w-2/3 text-secondary-700 font-medium text-xs"><?= htmlspecialchars($row['tempat_lahir']) ?>, <?= date('d M Y', strtotime($row['tanggal_lahir'])) ?></span>
            </div>
            <div class="flex text-sm">
                <span class="w-1/3 text-secondary-400 text-xs">Kelamin</span>
                <span class="w-2/3 text-secondary-700 font-medium text-xs"><?= $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></span>
            </div>
            <?php if ($row['agama']): ?>
            <div class="flex text-sm">
                <span class="w-1/3 text-secondary-400 text-xs">Agama</span>
                <span class="w-2/3 text-secondary-700 font-medium text-xs"><?= htmlspecialchars($row['agama']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($row['jenis_pekerjaan']): ?>
            <div class="flex text-sm">
                <span class="w-1/3 text-secondary-400 text-xs">Pekerjaan</span>
                <span class="w-2/3 text-secondary-700 font-medium text-xs"><?= htmlspecialchars($row['jenis_pekerjaan']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($row['nama_ayah'] || $row['nama_ibu']): ?>
            <div class="flex text-sm">
                <span class="w-1/3 text-secondary-400 text-xs">Orang Tua</span>
                <span class="w-2/3 text-secondary-700 font-medium text-xs">
                    <?php if ($row['nama_ayah']): ?>Ayah: <?= htmlspecialchars($row['nama_ayah']) ?><br><?php endif; ?>
                    <?php if ($row['nama_ibu']): ?>Ibu: <?= htmlspecialchars($row['nama_ibu']) ?><?php endif; ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Foto KTP Link -->
        <?php if ($row['foto_ktp']): ?>
        <a href="<?= htmlspecialchars($row['foto_ktp']) ?>" target="_blank"
            class="w-full text-center py-2 text-xs font-bold bg-secondary-50 text-secondary-600 rounded-xl hover:bg-secondary-100 transition-colors block">
            <i class="fas fa-image mr-1"></i> Lihat KTP
        </a>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
</div>
<?php else: ?>
<div class="flex flex-col items-center justify-center py-16 bg-white rounded-3xl border border-secondary-100">
    <div class="w-16 h-16 bg-secondary-50 rounded-full flex items-center justify-center mb-3">
        <i class="fas fa-users text-2xl text-secondary-300"></i>
    </div>
    <p class="text-secondary-400 font-medium">Belum ada anggota keluarga.</p>
    <p class="text-secondary-300 text-sm">Silakan hubungi Admin RT untuk mendaftarkan anggota keluarga Anda.</p>
</div>
<?php endif; ?>
<?php endif; // end kk_data check ?>




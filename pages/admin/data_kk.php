<?php
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../loginadmin.php"); exit;
}

$message = $_SESSION['flash_msg'] ?? '';
unset($_SESSION['flash_msg']);

// ============================================================
// AJAX: ambil data anggota untuk modal detail
// Harus diletakkan di atas dengan ob_clean() agar response tidak 
// tercampur HTML dari dashboard_admin.php
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
// Upload folder
// ============================================================
$upload_kk  = __DIR__ . '/../../uploads/foto_kk_kartu/';
$upload_ktp = __DIR__ . '/../../uploads/ktp/';
if (!is_dir($upload_kk))  mkdir($upload_kk,  0777, true);
if (!is_dir($upload_ktp)) mkdir($upload_ktp, 0777, true);

// ============================================================
// DELETE KK
// ============================================================
if (isset($_GET['hapus_kk'])) {
    $id_del = (int)$_GET['hapus_kk'];
    $q = $conn->query("SELECT foto_kk FROM kartu_keluarga WHERE id=$id_del");
    if ($q && $row_f = $q->fetch_assoc()) {
        if ($row_f['foto_kk'] && file_exists(__DIR__.'/../../'.$row_f['foto_kk']))
            unlink(__DIR__.'/../../'.$row_f['foto_kk']);
    }
    $conn->query("DELETE FROM kartu_keluarga WHERE id=$id_del");
    $_SESSION['flash_msg'] = "<div class='p-3 mb-4 bg-green-50 text-green-700 rounded-xl border border-green-200 text-sm font-bold'>Data KK berhasil dihapus.</div>";
    header("Location: ?page=data_kk"); exit;
}

// ============================================================
// DELETE ANGGOTA
// ============================================================
if (isset($_GET['hapus_anggota'])) {
    $id_del = (int)$_GET['hapus_anggota'];
    $kk_back = (int)($_GET['kk_id'] ?? 0);
    $q = $conn->query("SELECT foto_ktp FROM anggota_keluarga WHERE id=$id_del");
    if ($q && $row_f = $q->fetch_assoc()) {
        if ($row_f['foto_ktp'] && file_exists(__DIR__.'/../../'.$row_f['foto_ktp']))
            unlink(__DIR__.'/../../'.$row_f['foto_ktp']);
    }
    $conn->query("DELETE FROM anggota_keluarga WHERE id=$id_del");
    $_SESSION['flash_msg'] = "<div class='p-3 mb-4 bg-green-50 text-green-700 rounded-xl border border-green-200 text-sm font-bold'>Data anggota berhasil dihapus.</div>";
    $_SESSION['open_kk'] = $kk_back;
    header("Location: ?page=data_kk"); exit;
}

// ============================================================
// POST: SIMPAN DATA KK
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_kk'])) {
    $id     = !empty($_POST['kk_id']) ? (int)$_POST['kk_id'] : null;
    $no_kk  = $conn->real_escape_string(trim($_POST['no_kk']));
    $user_id= (int)$_POST['user_id'];
    $nama_kk= $conn->real_escape_string(trim($_POST['nama_kepala_keluarga']));
    $alamat = $conn->real_escape_string(trim($_POST['alamat']));
    $rt     = $conn->real_escape_string(trim($_POST['rt']));
    $rw     = $conn->real_escape_string(trim($_POST['rw']));
    $kodepos= $conn->real_escape_string(trim($_POST['kode_pos']));
    $desa   = $conn->real_escape_string(trim($_POST['desa_kelurahan']));
    $kec    = $conn->real_escape_string(trim($_POST['kecamatan']));
    $kab    = $conn->real_escape_string(trim($_POST['kabupaten_kota']));
    $prov   = $conn->real_escape_string(trim($_POST['provinsi']));

    $foto_kk_val = '';
    if (isset($_FILES['foto_kk']) && $_FILES['foto_kk']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['foto_kk']['name'], PATHINFO_EXTENSION));
        $fname = 'KK_' . $no_kk . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['foto_kk']['tmp_name'], $upload_kk . $fname);
        $foto_kk_val = 'uploads/foto_kk_kartu/' . $fname;
    }

    if ($id) {
        $foto_sql = $foto_kk_val ? ", foto_kk='$foto_kk_val'" : '';
        $sql = "UPDATE kartu_keluarga SET no_kk='$no_kk', user_id=$user_id, nama_kepala_keluarga='$nama_kk',
                alamat='$alamat', rt='$rt', rw='$rw', kode_pos='$kodepos',
                desa_kelurahan='$desa', kecamatan='$kec', kabupaten_kota='$kab', provinsi='$prov' $foto_sql
                WHERE id=$id";
        $conn->query($sql);
        $_SESSION['flash_msg'] = "<div class='p-3 mb-4 bg-blue-50 text-blue-700 rounded-xl border border-blue-200 text-sm font-bold'>Data KK berhasil diperbarui.</div>";
    } else {
        $sql = "INSERT INTO kartu_keluarga (no_kk, user_id, nama_kepala_keluarga, alamat, rt, rw, kode_pos, desa_kelurahan, kecamatan, kabupaten_kota, provinsi, foto_kk)
                VALUES ('$no_kk', $user_id, '$nama_kk', '$alamat', '$rt', '$rw', '$kodepos', '$desa', '$kec', '$kab', '$prov', '$foto_kk_val')";
        if ($conn->query($sql)) {
            $_SESSION['flash_msg'] = "<div class='p-3 mb-4 bg-green-50 text-green-700 rounded-xl border border-green-200 text-sm font-bold'>Data KK berhasil ditambahkan.</div>";
        } else {
            $_SESSION['flash_msg'] = "<div class='p-3 mb-4 bg-red-50 text-red-700 rounded-xl border border-red-200 text-sm font-bold'>Error: " . $conn->error . "</div>";
        }
    }
    header("Location: ?page=data_kk"); exit;
}

// ============================================================
// POST: SIMPAN ANGGOTA KELUARGA
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_anggota'])) {
    $id_ang     = !empty($_POST['anggota_id']) ? (int)$_POST['anggota_id'] : null;
    $kk_id      = (int)$_POST['kk_id_anggota'];
    $nik        = $conn->real_escape_string(trim($_POST['nik']));
    $nama       = $conn->real_escape_string(trim($_POST['nama_lengkap']));
    $tempat     = $conn->real_escape_string(trim($_POST['tempat_lahir']));
    $tgl        = $conn->real_escape_string($_POST['tanggal_lahir']);
    $jk         = $conn->real_escape_string($_POST['jenis_kelamin']);
    $goldar     = $conn->real_escape_string($_POST['golongan_darah']);
    $agama      = $conn->real_escape_string($_POST['agama']);
    $pendidikan = $conn->real_escape_string($_POST['pendidikan']);
    $pekerjaan  = $conn->real_escape_string($_POST['jenis_pekerjaan']);
    $status_kaw = $conn->real_escape_string($_POST['status_perkawinan']);
    $status_hub = $conn->real_escape_string($_POST['status_hubungan']);
    $wn         = $conn->real_escape_string($_POST['kewarganegaraan']);
    $ayah       = $conn->real_escape_string(trim($_POST['nama_ayah']));
    $ibu        = $conn->real_escape_string(trim($_POST['nama_ibu']));

    $foto_ktp_val = '';
    $update_foto_sql = '';
    if (isset($_FILES['foto_ktp']) && $_FILES['foto_ktp']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['foto_ktp']['name'], PATHINFO_EXTENSION));
        $fname = 'KTP_' . $nik . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['foto_ktp']['tmp_name'], $upload_ktp . $fname);
        $foto_ktp_val = 'uploads/ktp/' . $fname;
        $update_foto_sql = ", foto_ktp='$foto_ktp_val'";
    }

    if ($id_ang) {
        $sql = "UPDATE anggota_keluarga SET nik='$nik', nama_lengkap='$nama', tempat_lahir='$tempat',
                tanggal_lahir='$tgl', jenis_kelamin='$jk', golongan_darah='$goldar', agama='$agama',
                pendidikan='$pendidikan', jenis_pekerjaan='$pekerjaan', status_perkawinan='$status_kaw',
                status_hubungan='$status_hub', kewarganegaraan='$wn', nama_ayah='$ayah', nama_ibu='$ibu'
                $update_foto_sql WHERE id=$id_ang";
        $conn->query($sql);
        $_SESSION['flash_msg'] = "<div class='p-3 mb-4 bg-blue-50 text-blue-700 rounded-xl border border-blue-200 text-sm font-bold'>Data anggota berhasil diperbarui.</div>";
    } else {
        $sql = "INSERT INTO anggota_keluarga (kk_id, nik, nama_lengkap, tempat_lahir, tanggal_lahir,
                jenis_kelamin, golongan_darah, agama, pendidikan, jenis_pekerjaan, status_perkawinan,
                status_hubungan, kewarganegaraan, nama_ayah, nama_ibu, foto_ktp, diinput_oleh)
                VALUES ($kk_id, '$nik', '$nama', '$tempat', '$tgl', '$jk', '$goldar', '$agama',
                '$pendidikan', '$pekerjaan', '$status_kaw', '$status_hub', '$wn', '$ayah', '$ibu', '$foto_ktp_val', 'admin')";
        if ($conn->query($sql)) {
            $_SESSION['flash_msg'] = "<div class='p-3 mb-4 bg-green-50 text-green-700 rounded-xl border border-green-200 text-sm font-bold'>Data anggota berhasil ditambahkan.</div>";
        } else {
            $_SESSION['flash_msg'] = "<div class='p-3 mb-4 bg-red-50 text-red-700 rounded-xl border border-red-200 text-sm font-bold'>Error: " . $conn->error . "</div>";
        }
    }
    $_SESSION['open_kk'] = $kk_id;
    header("Location: ?page=data_kk"); exit;
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

// Ambil users warga yang BELUM punya KK
$users_tanpa_kk = $conn->query("
    SELECT u.id, u.nama, u.email FROM users u
    WHERE u.role = 'warga'
    AND u.id NOT IN (SELECT user_id FROM kartu_keluarga)
    ORDER BY u.nama ASC
");

// Ambil SEMUA warga untuk edit (termasuk yang sudah punya KK)
$all_warga = $conn->query("SELECT id, nama, email FROM users WHERE role='warga' ORDER BY nama ASC");

$open_kk = $_SESSION['open_kk'] ?? 0;
unset($_SESSION['open_kk']);
?>

<!-- Flash Message -->
<?= $message ?>

<!-- Header -->
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Data KK Warga</h2>
        <p class="text-secondary-500 mt-1">Kelola Kartu Keluarga dan data anggota sesuai dokumen kependudukan resmi.</p>
    </div>
    <button onclick="openModalKK()"
        class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-primary-600/30 transition-all active:scale-95 whitespace-nowrap">
        <i class="fas fa-plus"></i> Tambah Data KK
    </button>
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
            <p class="text-xs text-secondary-400 font-bold uppercase tracking-wide">Warga Belum Terdaftar KK</p>
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
            <div class="flex gap-2">
                <button onclick="event.stopPropagation(); editKK(<?= htmlspecialchars(json_encode($kk)) ?>)"
                    class="text-secondary-400 hover:text-indigo-600 transition-colors p-1.5" title="Edit KK">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="event.stopPropagation(); confirmHapusKK(<?= $kk['id'] ?>, '<?= htmlspecialchars(addslashes($kk['nama_kepala_keluarga'])) ?>')"
                    class="text-secondary-400 hover:text-red-600 transition-colors p-1.5" title="Hapus KK">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
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
    <p class="text-secondary-400 text-sm">Klik tombol "Tambah Data KK" untuk memulai.</p>
</div>
<?php endif; ?>


<!-- ================================================================ -->
<!-- MODAL: Tambah / Edit Data KK -->
<!-- ================================================================ -->
<div id="modalKK" class="fixed inset-0 z-50 hidden bg-secondary-900/60 backdrop-blur-sm flex items-start justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl my-8 flex flex-col overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="modalKKContent">
        <div class="p-6 border-b border-secondary-100 flex justify-between items-center bg-indigo-50">
            <div>
                <h3 class="text-lg font-black text-indigo-900" id="modalKKTitle">Tambah Data Kartu Keluarga</h3>
                <p class="text-indigo-500 text-xs mt-0.5">Isi sesuai Kartu Keluarga resmi</p>
            </div>
            <button onclick="closeModalKK()" class="text-indigo-400 hover:text-indigo-700 transition-colors"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[70vh]">
            <form action="" method="POST" enctype="multipart/form-data" id="formKK">
                <input type="hidden" name="submit_kk" value="1">
                <input type="hidden" name="kk_id" id="kk_id_hidden">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- No KK -->
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Nomor Kartu Keluarga <span class="text-red-500">*</span></label>
                        <input type="text" name="no_kk" id="f_no_kk" maxlength="16" pattern="\d{16}" required placeholder="16 digit angka"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-indigo-500 font-mono text-secondary-900 text-lg tracking-widest placeholder:font-sans placeholder:text-sm placeholder:tracking-normal">
                    </div>

                    <!-- Nama Kepala Keluarga -->
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Nama Kepala Keluarga <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_kepala_keluarga" id="f_nama_kk" required placeholder="Sesuai KK"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-indigo-500 text-secondary-900 font-medium">
                    </div>

                    <!-- Alamat -->
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Alamat Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="alamat" id="f_alamat" required placeholder="Jl. / Gg. / Blok / No."
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-indigo-500 text-secondary-900 font-medium">
                    </div>

                    <!-- RT & RW -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">RT <span class="text-red-500">*</span></label>
                        <input type="text" name="rt" id="f_rt" required maxlength="5" placeholder="006"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-indigo-500 text-secondary-900 font-medium">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">RW <span class="text-red-500">*</span></label>
                        <input type="text" name="rw" id="f_rw" required maxlength="5" placeholder="008"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-indigo-500 text-secondary-900 font-medium">
                    </div>

                    <!-- Kode Pos -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Kode Pos</label>
                        <input type="text" name="kode_pos" id="f_kodepos" maxlength="10" placeholder="15414"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-indigo-500 text-secondary-900 font-medium">
                    </div>

                    <!-- Desa/Kelurahan -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Desa / Kelurahan <span class="text-red-500">*</span></label>
                        <input type="text" name="desa_kelurahan" id="f_desa" required placeholder="Serua Indah"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-indigo-500 text-secondary-900 font-medium">
                    </div>

                    <!-- Kecamatan -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Kecamatan <span class="text-red-500">*</span></label>
                        <input type="text" name="kecamatan" id="f_kecamatan" required placeholder="Ciputat"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-indigo-500 text-secondary-900 font-medium">
                    </div>

                    <!-- Kab/Kota -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Kabupaten / Kota <span class="text-red-500">*</span></label>
                        <input type="text" name="kabupaten_kota" id="f_kab" required placeholder="Kota Tangerang Selatan"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-indigo-500 text-secondary-900 font-medium">
                    </div>

                    <!-- Provinsi -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Provinsi <span class="text-red-500">*</span></label>
                        <input type="text" name="provinsi" id="f_provinsi" required placeholder="Banten"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-indigo-500 text-secondary-900 font-medium">
                    </div>

                    <!-- User Warga -->
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Akun Login Warga (Kepala KK) <span class="text-red-500">*</span></label>
                        <select name="user_id" id="f_user_id" required
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-indigo-500 text-secondary-900 font-medium">
                            <option value="">-- Pilih Akun Warga --</option>
                            <?php
                            // Re-query untuk dropdown (all warga untuk edit mode)
                            $all_w = $conn->query("SELECT id, nama, email FROM users WHERE role='warga' ORDER BY nama ASC");
                            while ($uw = $all_w->fetch_assoc()):
                            ?>
                            <option value="<?= $uw['id'] ?>"><?= htmlspecialchars($uw['nama']) ?> (<?= htmlspecialchars($uw['email']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                        <p class="text-xs text-secondary-400 mt-1">Akun yang belum terdaftar KK ditandai dengan nama saja.</p>
                    </div>

                    <!-- Foto KK -->
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Foto / Scan Kartu Keluarga</label>
                        <input type="file" name="foto_kk" accept="image/*,.pdf"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-indigo-500 text-secondary-700 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-100 file:text-indigo-700 file:font-bold file:text-xs">
                        <p id="foto_kk_current" class="text-xs text-secondary-400 mt-1"></p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-5 border-t border-secondary-100">
                    <button type="button" onclick="closeModalKK()" class="px-5 py-2.5 text-secondary-600 font-bold hover:bg-secondary-100 rounded-xl transition-colors text-sm">Batal</button>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 active:scale-95 transition-all text-sm">
                        <i class="fas fa-save mr-2"></i>Simpan Data KK
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ================================================================ -->
<!-- MODAL: Detail Anggota KK -->
<!-- ================================================================ -->
<div id="modalDetail" class="fixed inset-0 z-50 hidden bg-secondary-900/60 backdrop-blur-sm flex items-start justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl my-8 flex flex-col overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="modalDetailContent">
        <div class="p-6 border-b border-secondary-100 flex justify-between items-center" id="modalDetailHeader">
            <div>
                <h3 class="text-lg font-black text-secondary-900" id="modalDetailTitle">Data Anggota Keluarga</h3>
                <p class="text-secondary-400 text-xs mt-0.5" id="modalDetailSubtitle"></p>
            </div>
            <div class="flex items-center gap-2">
                <button id="btnTambahAnggota" onclick=""
                    class="flex items-center gap-1.5 text-xs font-bold bg-indigo-600 text-white px-4 py-2 rounded-xl hover:bg-indigo-700 transition-all shadow-sm">
                    <i class="fas fa-plus"></i> Tambah Anggota
                </button>
                <button onclick="closeModalDetail()" class="text-secondary-400 hover:text-secondary-700 transition-colors ml-2"><i class="fas fa-times text-xl"></i></button>
            </div>
        </div>
        <div class="p-6 overflow-y-auto max-h-[70vh]" id="modalDetailBody">
            <div class="text-center py-8 text-secondary-400"><i class="fas fa-spinner fa-spin text-2xl"></i></div>
        </div>
    </div>
</div>


<!-- ================================================================ -->
<!-- MODAL: Tambah / Edit Anggota Keluarga -->
<!-- ================================================================ -->
<div id="modalAnggota" class="fixed inset-0 z-[60] hidden bg-secondary-900/60 backdrop-blur-sm flex items-start justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl my-8 flex flex-col overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="modalAnggotaContent">
        <div class="p-6 border-b border-secondary-100 flex justify-between items-center bg-emerald-50">
            <div>
                <h3 class="text-lg font-black text-emerald-900" id="modalAnggotaTitle">Tambah Anggota Keluarga</h3>
                <p class="text-emerald-500 text-xs mt-0.5">Isi sesuai KK dan Kartu Identitas resmi</p>
            </div>
            <button onclick="closeModalAnggota()" class="text-emerald-400 hover:text-emerald-700 transition-colors"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[75vh]">
            <form action="" method="POST" enctype="multipart/form-data" id="formAnggota">
                <input type="hidden" name="submit_anggota" value="1">
                <input type="hidden" name="anggota_id" id="anggota_id_hidden">
                <input type="hidden" name="kk_id_anggota" id="kk_id_anggota_hidden">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- NIK -->
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">NIK (Nomor Induk Kependudukan) <span class="text-red-500">*</span></label>
                        <input type="text" name="nik" id="a_nik" maxlength="16" pattern="\d{16}" required placeholder="16 digit angka"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 font-mono text-secondary-900 text-lg tracking-widest placeholder:font-sans placeholder:text-sm placeholder:tracking-normal">
                    </div>

                    <!-- Nama Lengkap -->
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_lengkap" id="a_nama" required placeholder="Sesuai Kartu Identitas"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-900 font-medium">
                    </div>

                    <!-- Tempat Lahir -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Tempat Lahir <span class="text-red-500">*</span></label>
                        <input type="text" name="tempat_lahir" id="a_tempat" required placeholder="Kota kelahiran"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-900 font-medium">
                    </div>

                    <!-- Tanggal Lahir -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Tanggal Lahir <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_lahir" id="a_tgl" required
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-900 font-medium">
                    </div>

                    <!-- Jenis Kelamin -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Jenis Kelamin <span class="text-red-500">*</span></label>
                        <select name="jenis_kelamin" id="a_jk" required
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-900 font-medium">
                            <option value="">-- Pilih --</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>

                    <!-- Golongan Darah -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Golongan Darah</label>
                        <select name="golongan_darah" id="a_goldar"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-900 font-medium">
                            <option value="-">Tidak Diketahui / -</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="AB">AB</option>
                            <option value="O">O</option>
                        </select>
                    </div>

                    <!-- Agama -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Agama <span class="text-red-500">*</span></label>
                        <select name="agama" id="a_agama" required
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-900 font-medium">
                            <option value="">-- Pilih --</option>
                            <option>Islam</option>
                            <option>Kristen</option>
                            <option>Katolik</option>
                            <option>Hindu</option>
                            <option>Buddha</option>
                            <option>Konghucu</option>
                            <option>Kepercayaan Terhadap Tuhan YME</option>
                        </select>
                    </div>

                    <!-- Pendidikan -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Pendidikan Terakhir</label>
                        <select name="pendidikan" id="a_pendidikan"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-900 font-medium">
                            <option value="">-- Pilih --</option>
                            <option>Tidak/Belum Sekolah</option>
                            <option>Belum Tamat SD/Sederajat</option>
                            <option>Tamat SD/Sederajat</option>
                            <option>SLTP/Sederajat</option>
                            <option>SLTA/Sederajat</option>
                            <option>Diploma I/II</option>
                            <option>Diploma III</option>
                            <option>Diploma IV/Strata I</option>
                            <option>Strata II</option>
                            <option>Strata III</option>
                        </select>
                    </div>

                    <!-- Jenis Pekerjaan -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Jenis Pekerjaan</label>
                        <input type="text" name="jenis_pekerjaan" id="a_pekerjaan" placeholder="Misal: Karyawan Swasta"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-900 font-medium">
                    </div>

                    <!-- Status Perkawinan -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Status Perkawinan <span class="text-red-500">*</span></label>
                        <select name="status_perkawinan" id="a_status_kaw" required
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-900 font-medium">
                            <option value="">-- Pilih --</option>
                            <option>Belum Kawin</option>
                            <option>Kawin Tercatat</option>
                            <option>Kawin Belum Tercatat</option>
                            <option>Cerai Hidup</option>
                            <option>Cerai Mati</option>
                        </select>
                    </div>

                    <!-- Status Hubungan Keluarga -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Status Hubungan dalam Keluarga <span class="text-red-500">*</span></label>
                        <select name="status_hubungan" id="a_status_hub" required
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-900 font-medium">
                            <option value="">-- Pilih --</option>
                            <option>Kepala Keluarga</option>
                            <option>Suami</option>
                            <option>Istri</option>
                            <option>Anak</option>
                            <option>Menantu</option>
                            <option>Cucu</option>
                            <option>Orang Tua</option>
                            <option>Mertua</option>
                            <option>Famili Lain</option>
                            <option>Pembantu</option>
                            <option>Lainnya</option>
                        </select>
                    </div>

                    <!-- Kewarganegaraan -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Kewarganegaraan</label>
                        <select name="kewarganegaraan" id="a_wn"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-900 font-medium">
                            <option value="WNI">WNI</option>
                            <option value="WNA">WNA</option>
                        </select>
                    </div>

                    <!-- Nama Ayah -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Nama Ayah <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_ayah" id="a_ayah" required placeholder="Sesuai KK"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-900 font-medium">
                    </div>

                    <!-- Nama Ibu -->
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Nama Ibu <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_ibu" id="a_ibu" required placeholder="Sesuai KK"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-900 font-medium">
                    </div>

                    <!-- Foto Kartu Identitas -->
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5">Foto / Scan Kartu Identitas</label>
                        <input type="file" name="foto_ktp" accept="image/*"
                            class="w-full px-4 py-3 bg-secondary-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-secondary-700 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-100 file:text-emerald-700 file:font-bold file:text-xs">
                        <p id="foto_ktp_current" class="text-xs text-secondary-400 mt-1"></p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-5 border-t border-secondary-100">
                    <button type="button" onclick="closeModalAnggota()" class="px-5 py-2.5 text-secondary-600 font-bold hover:bg-secondary-100 rounded-xl transition-colors text-sm">Batal</button>
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30 hover:bg-emerald-700 active:scale-95 transition-all text-sm">
                        <i class="fas fa-save mr-2"></i>Simpan Data Anggota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
// ============================================================
// MODAL KK HELPERS
// ============================================================
const modalKK = document.getElementById('modalKK');
const modalKKContent = document.getElementById('modalKKContent');

function openModalKK() {
    document.getElementById('modalKKTitle').textContent = 'Tambah Data Kartu Keluarga';
    document.getElementById('kk_id_hidden').value = '';
    document.getElementById('formKK').reset();
    document.getElementById('foto_kk_current').textContent = '';
    modalKK.classList.remove('hidden');
    setTimeout(() => {
        modalKKContent.classList.remove('scale-95','opacity-0');
        modalKKContent.classList.add('scale-100','opacity-100');
    }, 10);
}

function closeModalKK() {
    modalKKContent.classList.remove('scale-100','opacity-100');
    modalKKContent.classList.add('scale-95','opacity-0');
    setTimeout(() => modalKK.classList.add('hidden'), 300);
}

function editKK(data) {
    document.getElementById('modalKKTitle').textContent = 'Edit Data Kartu Keluarga';
    document.getElementById('kk_id_hidden').value = data.id;
    document.getElementById('f_no_kk').value = data.no_kk;
    document.getElementById('f_nama_kk').value = data.nama_kepala_keluarga;
    document.getElementById('f_alamat').value = data.alamat;
    document.getElementById('f_rt').value = data.rt;
    document.getElementById('f_rw').value = data.rw;
    document.getElementById('f_kodepos').value = data.kode_pos || '';
    document.getElementById('f_desa').value = data.desa_kelurahan;
    document.getElementById('f_kecamatan').value = data.kecamatan;
    document.getElementById('f_kab').value = data.kabupaten_kota;
    document.getElementById('f_provinsi').value = data.provinsi;
    document.getElementById('f_user_id').value = data.user_id;
    if (data.foto_kk) {
        document.getElementById('foto_kk_current').textContent = 'File saat ini: ' + data.foto_kk;
    }
    modalKK.classList.remove('hidden');
    setTimeout(() => {
        modalKKContent.classList.remove('scale-95','opacity-0');
        modalKKContent.classList.add('scale-100','opacity-100');
    }, 10);
}

function confirmHapusKK(id, nama) {
    Swal.fire({
        title: 'Hapus Data KK?',
        html: `Semua data anggota keluarga di KK <strong>${nama}</strong> juga akan terhapus!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl font-bold', cancelButton: 'rounded-xl font-bold' }
    }).then(r => {
        if (r.isConfirmed) window.location.href = `?page=data_kk&hapus_kk=${id}`;
    });
}

// ============================================================
// MODAL DETAIL KK (Lihat Anggota)
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
            document.getElementById('btnTambahAnggota').onclick = () => openModalAnggota(kkId, data.kk.no_kk);
            renderAnggotaTable(data.anggota, kkId);
        });
}

function renderAnggotaTable(anggota, kkId) {
    if (anggota.length === 0) {
        document.getElementById('modalDetailBody').innerHTML = `
            <div class="text-center py-10">
                <div class="w-16 h-16 bg-secondary-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-users text-2xl text-secondary-300"></i>
                </div>
                <p class="text-secondary-400 font-medium">Belum ada anggota keluarga.</p>
                <p class="text-secondary-300 text-sm">Klik "Tambah Anggota" untuk menambahkan.</p>
            </div>`;
        return;
    }

    let html = `<div class="overflow-x-auto"><table class="w-full text-left border-collapse text-sm">
        <thead><tr class="bg-secondary-50 text-secondary-500 text-xs uppercase tracking-wider">
            <th class="px-3 py-3 font-black rounded-l-xl">Nama / NIK</th>
            <th class="px-3 py-3 font-black">Hub. Keluarga</th>
            <th class="px-3 py-3 font-black">TTL</th>
            <th class="px-3 py-3 font-black">Pekerjaan</th>
            <th class="px-3 py-3 font-black">Nama Orang Tua</th>
            <th class="px-3 py-3 font-black text-center rounded-r-xl">Aksi</th>
        </tr></thead>
        <tbody class="divide-y divide-secondary-50">`;

    anggota.forEach(a => {
        const badge = a.diinput_oleh === 'admin'
            ? `<span class="ml-1 text-[9px] font-black bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded-full uppercase">Resmi</span>`
            : `<span class="ml-1 text-[9px] font-black bg-amber-100 text-amber-600 px-1.5 py-0.5 rounded-full uppercase">Warga</span>`;

        const ktpLink = a.foto_ktp
            ? `<a href="${a.foto_ktp}" target="_blank" class="text-[10px] font-bold bg-secondary-100 text-secondary-600 px-2 py-0.5 rounded hover:bg-secondary-200">Kartu Identitas</a>`
            : '';

        html += `<tr class="hover:bg-secondary-50/50 transition-colors">
            <td class="px-3 py-3">
                <p class="font-bold text-secondary-900">${a.nama_lengkap} ${badge}</p>
                <p class="text-xs text-secondary-400 font-mono">${a.nik}</p>
                ${ktpLink}
            </td>
            <td class="px-3 py-3 text-secondary-600 font-medium">${a.status_hubungan}</td>
            <td class="px-3 py-3 text-secondary-600 text-xs">${a.tempat_lahir},<br>${a.tanggal_lahir}</td>
            <td class="px-3 py-3 text-secondary-600 text-xs">${a.jenis_pekerjaan || '-'}</td>
            <td class="px-3 py-3 text-secondary-600 text-xs">
                <span class="block">Ayah: ${a.nama_ayah || '-'}</span>
                <span class="block">Ibu: ${a.nama_ibu || '-'}</span>
            </td>
            <td class="px-3 py-3 text-center">
                <div class="flex gap-1 justify-center">
                    <button onclick="editAnggota(${JSON.stringify(a).replace(/"/g,'&quot;')}, ${kkId})" class="text-secondary-400 hover:text-indigo-600 transition-colors p-1.5" title="Edit">
                        <i class="fas fa-edit text-xs"></i>
                    </button>
                    <button onclick="confirmHapusAnggota(${a.id}, '${a.nama_lengkap.replace(/'/g,"\\'")}', ${kkId})" class="text-secondary-400 hover:text-red-600 transition-colors p-1.5" title="Hapus">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
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

function confirmHapusAnggota(id, nama, kkId) {
    Swal.fire({
        title: 'Hapus Anggota?',
        text: `Data "${nama}" akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl font-bold', cancelButton: 'rounded-xl font-bold' }
    }).then(r => {
        if (r.isConfirmed) window.location.href = `?page=data_kk&hapus_anggota=${id}&kk_id=${kkId}`;
    });
}

// ============================================================
// MODAL ANGGOTA HELPERS
// ============================================================
const modalAnggota = document.getElementById('modalAnggota');
const modalAnggotaContent = document.getElementById('modalAnggotaContent');

function openModalAnggota(kkId, noKk) {
    document.getElementById('modalAnggotaTitle').textContent = 'Tambah Anggota Keluarga (KK: ' + noKk + ')';
    document.getElementById('anggota_id_hidden').value = '';
    document.getElementById('kk_id_anggota_hidden').value = kkId;
    document.getElementById('formAnggota').reset();
    document.getElementById('foto_ktp_current').textContent = '';
    modalAnggota.classList.remove('hidden');
    setTimeout(() => {
        modalAnggotaContent.classList.remove('scale-95','opacity-0');
        modalAnggotaContent.classList.add('scale-100','opacity-100');
    }, 10);
}

function editAnggota(data, kkId) {
    document.getElementById('modalAnggotaTitle').textContent = 'Edit Data Anggota';
    document.getElementById('anggota_id_hidden').value = data.id;
    document.getElementById('kk_id_anggota_hidden').value = kkId;
    document.getElementById('a_nik').value = data.nik;
    document.getElementById('a_nama').value = data.nama_lengkap;
    document.getElementById('a_tempat').value = data.tempat_lahir;
    document.getElementById('a_tgl').value = data.tanggal_lahir;
    document.getElementById('a_jk').value = data.jenis_kelamin;
    document.getElementById('a_goldar').value = data.golongan_darah || '-';
    document.getElementById('a_agama').value = data.agama || '';
    document.getElementById('a_pendidikan').value = data.pendidikan || '';
    document.getElementById('a_pekerjaan').value = data.jenis_pekerjaan || '';
    document.getElementById('a_status_kaw').value = data.status_perkawinan || '';
    document.getElementById('a_status_hub').value = data.status_hubungan;
    document.getElementById('a_wn').value = data.kewarganegaraan || 'WNI';
    document.getElementById('a_ayah').value = data.nama_ayah || '';
    document.getElementById('a_ibu').value = data.nama_ibu || '';
    if (data.foto_ktp) {
        document.getElementById('foto_ktp_current').textContent = 'File saat ini: ' + data.foto_ktp;
    }
    modalAnggota.classList.remove('hidden');
    setTimeout(() => {
        modalAnggotaContent.classList.remove('scale-95','opacity-0');
        modalAnggotaContent.classList.add('scale-100','opacity-100');
    }, 10);
}

function closeModalAnggota() {
    modalAnggotaContent.classList.remove('scale-100','opacity-100');
    modalAnggotaContent.classList.add('scale-95','opacity-0');
    setTimeout(() => modalAnggota.classList.add('hidden'), 300);
}

// ============================================================
// Auto-open detail jika redirect dari hapus/tambah anggota
// ============================================================
<?php if ($open_kk): ?>
window.addEventListener('DOMContentLoaded', () => {
    openDetailKK(<?= $open_kk ?>);
});
<?php endif; ?>
</script>

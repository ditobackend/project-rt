<?php
require_once __DIR__ . '/../../config/database.php';

$user_id = $_SESSION['user_id'];

// Pastikan folder upload tersedia
$upload_dir_ktp = __DIR__ . '/../../uploads/ktp/';
$upload_dir_kk = __DIR__ . '/../../uploads/kk/';
if (!is_dir($upload_dir_ktp)) mkdir($upload_dir_ktp, 0777, true);
if (!is_dir($upload_dir_kk)) mkdir($upload_dir_kk, 0777, true);

// Handle Delete
if (isset($_GET['delete'])) {
    $id_del = (int)$_GET['delete'];
    
    // Hapus file lama jika ada
    $q_file = $conn->query("SELECT foto_ktp, foto_kk FROM data_keluarga WHERE id=$id_del AND user_id=$user_id");
    if ($q_file->num_rows > 0) {
        $row_file = $q_file->fetch_assoc();
        if ($row_file['foto_ktp'] && file_exists(__DIR__ . '/../../' . $row_file['foto_ktp'])) {
            unlink(__DIR__ . '/../../' . $row_file['foto_ktp']);
        }
        if ($row_file['foto_kk'] && file_exists(__DIR__ . '/../../' . $row_file['foto_kk'])) {
            unlink(__DIR__ . '/../../' . $row_file['foto_kk']);
        }
    }

    $conn->query("DELETE FROM data_keluarga WHERE id=$id_del AND user_id=$user_id");
    header("Location: ?page=keluarga&msg=deleted");
    exit;
}

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_keluarga'])) {
    $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $nik = $conn->real_escape_string($_POST['nik']);
    $nama = $conn->real_escape_string($_POST['nama_lengkap']);
    $tempat_lahir = $conn->real_escape_string($_POST['tempat_lahir']);
    $tanggal_lahir = $conn->real_escape_string($_POST['tanggal_lahir']);
    $jk = $conn->real_escape_string($_POST['jenis_kelamin']);
    $agama = $conn->real_escape_string($_POST['agama']);
    $pendidikan = $conn->real_escape_string($_POST['pendidikan']);
    $pekerjaan = $conn->real_escape_string($_POST['pekerjaan']);
    $status_kawin = $conn->real_escape_string($_POST['status_perkawinan']);
    $status_hubungan = $conn->real_escape_string($_POST['status_hubungan']);

    $foto_ktp = "";
    $foto_kk = "";
    $update_foto_ktp_sql = "";
    $update_foto_kk_sql = "";

    // Upload KTP
    if (isset($_FILES['foto_ktp']) && $_FILES['foto_ktp']['error'] == 0) {
        $ext = pathinfo($_FILES['foto_ktp']['name'], PATHINFO_EXTENSION);
        $ktp_name = 'KTP_' . $nik . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['foto_ktp']['tmp_name'], $upload_dir_ktp . $ktp_name);
        $foto_ktp = 'uploads/ktp/' . $ktp_name;
        $update_foto_ktp_sql = ", foto_ktp='$foto_ktp'";
    }

    // Upload KK
    if (isset($_FILES['foto_kk']) && $_FILES['foto_kk']['error'] == 0) {
        $ext = pathinfo($_FILES['foto_kk']['name'], PATHINFO_EXTENSION);
        $kk_name = 'KK_' . $nik . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['foto_kk']['tmp_name'], $upload_dir_kk . $kk_name);
        $foto_kk = 'uploads/kk/' . $kk_name;
        $update_foto_kk_sql = ", foto_kk='$foto_kk'";
    }

    if ($id) {
        // Update
        $sql = "UPDATE data_keluarga SET 
                nik='$nik', nama_lengkap='$nama', tempat_lahir='$tempat_lahir', tanggal_lahir='$tanggal_lahir',
                jenis_kelamin='$jk', agama='$agama', pendidikan='$pendidikan', pekerjaan='$pekerjaan',
                status_perkawinan='$status_kawin', status_hubungan='$status_hubungan' 
                $update_foto_ktp_sql $update_foto_kk_sql
                WHERE id=$id AND user_id=$user_id";
        $conn->query($sql);
        header("Location: ?page=keluarga&msg=updated");
    } else {
        // Insert
        $sql = "INSERT INTO data_keluarga 
                (user_id, nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, pendidikan, pekerjaan, status_perkawinan, status_hubungan, foto_ktp, foto_kk) 
                VALUES 
                ($user_id, '$nik', '$nama', '$tempat_lahir', '$tanggal_lahir', '$jk', '$agama', '$pendidikan', '$pekerjaan', '$status_kawin', '$status_hubungan', '$foto_ktp', '$foto_kk')";
        $conn->query($sql);
        header("Location: ?page=keluarga&msg=added");
    }
    exit;
}

// Ambil data
$keluarga_query = $conn->query("SELECT * FROM data_keluarga WHERE user_id=$user_id ORDER BY FIELD(status_hubungan, 'Kepala Keluarga', 'Istri', 'Suami', 'Anak', 'Lainnya'), tanggal_lahir ASC");
?>

<div class="mb-10 flex justify-between items-center">
    <div>
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Data Keluarga</h2>
        <p class="text-secondary-500 mt-1">Kelola data anggota keluarga Anda untuk keperluan administrasi dan persuratan otomatis.</p>
    </div>
    <button onclick="openModalKeluarga()" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-primary-600/30 transition-all active:scale-95 flex items-center gap-2">
        <i class="fas fa-plus"></i> Tambah Anggota
    </button>
</div>



<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if ($keluarga_query->num_rows > 0): ?>
        <?php while ($row = $keluarga_query->fetch_assoc()): ?>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary-100 relative group overflow-hidden">
                <div class="absolute right-0 top-0 w-24 h-24 bg-primary-50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform"></div>
                
                <div class="flex justify-between items-start mb-4">
                    <span class="px-3 py-1 bg-primary-100 text-primary-700 text-xs font-black rounded-lg uppercase tracking-wider">
                        <?= htmlspecialchars($row['status_hubungan']) ?>
                    </span>
                    <div class="flex gap-2">
                        <button onclick='editKeluarga(<?= json_encode($row) ?>)' class="text-secondary-400 hover:text-primary-600 transition-colors">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" onclick="confirmDelete(<?= $row['id'] ?>)" class="text-secondary-400 hover:text-red-600 transition-colors">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <h3 class="text-lg font-bold text-secondary-900 mb-1"><?= htmlspecialchars($row['nama_lengkap']) ?></h3>
                <p class="text-secondary-500 font-medium text-sm mb-4"><i class="far fa-id-card mr-2"></i><?= htmlspecialchars($row['nik']) ?></p>
                
                <div class="space-y-2 mb-6">
                    <div class="flex text-sm">
                        <span class="w-1/3 text-secondary-400">Lahir</span>
                        <span class="w-2/3 text-secondary-900 font-medium"><?= htmlspecialchars($row['tempat_lahir']) ?>, <?= date('d M Y', strtotime($row['tanggal_lahir'])) ?></span>
                    </div>
                    <div class="flex text-sm">
                        <span class="w-1/3 text-secondary-400">Gender</span>
                        <span class="w-2/3 text-secondary-900 font-medium"><?= $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></span>
                    </div>
                    <div class="flex text-sm">
                        <span class="w-1/3 text-secondary-400">Pekerjaan</span>
                        <span class="w-2/3 text-secondary-900 font-medium"><?= htmlspecialchars($row['pekerjaan']) ?></span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <?php if ($row['foto_ktp']): ?>
                        <a href="<?= htmlspecialchars($row['foto_ktp']) ?>" target="_blank" class="flex-1 py-2 text-center text-xs font-bold bg-secondary-50 text-secondary-600 rounded-xl hover:bg-secondary-100 transition-colors">Lihat KTP</a>
                    <?php else: ?>
                        <div class="flex-1 py-2 text-center text-xs font-medium bg-red-50 text-red-400 rounded-xl">Belum ada KTP</div>
                    <?php endif; ?>
                    
                    <?php if ($row['foto_kk']): ?>
                        <a href="<?= htmlspecialchars($row['foto_kk']) ?>" target="_blank" class="flex-1 py-2 text-center text-xs font-bold bg-secondary-50 text-secondary-600 rounded-xl hover:bg-secondary-100 transition-colors">Lihat KK</a>
                    <?php else: ?>
                        <div class="flex-1 py-2 text-center text-xs font-medium bg-red-50 text-red-400 rounded-xl">Belum ada KK</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-span-full text-center py-16 bg-white rounded-3xl border border-secondary-100 border-dashed">
            <i class="fas fa-users-slash text-5xl text-secondary-200 mb-4"></i>
            <h3 class="text-xl font-bold text-secondary-900">Belum Ada Data</h3>
            <p class="text-secondary-500 mt-2">Silakan tambahkan data anggota keluarga Anda terlebih dahulu.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Form Keluarga -->
<div id="modalKeluarga" class="fixed inset-0 z-50 hidden bg-secondary-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="modalKeluargaContent">
        <div class="p-6 border-b border-secondary-100 flex justify-between items-center bg-secondary-50">
            <h3 class="text-xl font-black text-secondary-900" id="modalTitle">Tambah Data Keluarga</h3>
            <button onclick="closeModalKeluarga()" class="text-secondary-400 hover:text-red-500 transition-colors"><i class="fas fa-times text-xl"></i></button>
        </div>
        
        <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
            <form id="formKeluarga" method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="id" id="keluarga_id">
                
                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-secondary-600 mb-1">NIK (Nomor Induk Kependudukan) *</label>
                        <input type="text" name="nik" id="nik" required class="w-full px-4 py-2.5 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-secondary-600 mb-1">Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" id="nama_lengkap" required class="w-full px-4 py-2.5 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-secondary-600 mb-1">Tempat Lahir *</label>
                        <input type="text" name="tempat_lahir" id="tempat_lahir" required class="w-full px-4 py-2.5 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-secondary-600 mb-1">Tanggal Lahir *</label>
                        <input type="date" name="tanggal_lahir" id="tanggal_lahir" required class="w-full px-4 py-2.5 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-secondary-600 mb-1">Jenis Kelamin *</label>
                        <select name="jenis_kelamin" id="jenis_kelamin" required class="w-full px-4 py-2.5 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-secondary-600 mb-1">Agama *</label>
                        <select name="agama" id="agama" required class="w-full px-4 py-2.5 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all">
                            <option value="Islam">Islam</option>
                            <option value="Kristen">Kristen</option>
                            <option value="Katolik">Katolik</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Buddha">Buddha</option>
                            <option value="Konghucu">Konghucu</option>
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-secondary-600 mb-1">Pendidikan</label>
                        <input type="text" name="pendidikan" id="pendidikan" class="w-full px-4 py-2.5 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-secondary-600 mb-1">Pekerjaan</label>
                        <input type="text" name="pekerjaan" id="pekerjaan" class="w-full px-4 py-2.5 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-secondary-600 mb-1">Status Perkawinan *</label>
                        <select name="status_perkawinan" id="status_perkawinan" required class="w-full px-4 py-2.5 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all">
                            <option value="Belum Kawin">Belum Kawin</option>
                            <option value="Kawin">Kawin</option>
                            <option value="Cerai Hidup">Cerai Hidup</option>
                            <option value="Cerai Mati">Cerai Mati</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-secondary-600 mb-1">Status Hubungan dlm Keluarga *</label>
                        <select name="status_hubungan" id="status_hubungan" required class="w-full px-4 py-2.5 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all">
                            <option value="Kepala Keluarga">Kepala Keluarga</option>
                            <option value="Istri">Istri</option>
                            <option value="Suami">Suami</option>
                            <option value="Anak">Anak</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>

                <div class="bg-primary-50 p-4 rounded-xl border border-primary-100 mt-4 space-y-4">
                    <p class="text-xs font-bold text-primary-700"><i class="fas fa-file-upload mr-1"></i> Unggah Dokumen (Opsional, max 2MB, JPG/PNG/PDF)</p>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-secondary-500 uppercase tracking-wider mb-1">Scan/Foto KTP</label>
                            <input type="file" name="foto_ktp" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-xs file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-white file:text-primary-700 hover:file:bg-primary-50 border border-secondary-200 rounded-xl bg-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-secondary-500 uppercase tracking-wider mb-1">Scan/Foto KK</label>
                            <input type="file" name="foto_kk" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-xs file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-white file:text-primary-700 hover:file:bg-primary-50 border border-secondary-200 rounded-xl bg-white">
                        </div>
                    </div>
                </div>

        </div>
        <div class="p-6 border-t border-secondary-100 flex justify-end gap-3 bg-secondary-50">
            <button type="button" onclick="closeModalKeluarga()" class="px-6 py-2.5 text-secondary-600 font-bold hover:bg-secondary-200 rounded-xl transition-colors">Batal</button>
            <button type="submit" name="submit_keluarga" class="px-8 py-2.5 bg-primary-600 text-white font-bold rounded-xl shadow-lg shadow-primary-600/30 hover:bg-primary-700 active:scale-95 transition-all">Simpan Data</button>
        </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('modalKeluarga');
    const modalContent = document.getElementById('modalKeluargaContent');
    const form = document.getElementById('formKeluarga');
    
    function openModalKeluarga() {
        form.reset();
        document.getElementById('keluarga_id').value = '';
        document.getElementById('modalTitle').innerText = 'Tambah Data Keluarga';
        modal.classList.remove('hidden');
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
    
    function closeModalKeluarga() {
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function editKeluarga(data) {
        document.getElementById('modalTitle').innerText = 'Edit Data Keluarga';
        document.getElementById('keluarga_id').value = data.id;
        document.getElementById('nik').value = data.nik;
        document.getElementById('nama_lengkap').value = data.nama_lengkap;
        document.getElementById('tempat_lahir').value = data.tempat_lahir;
        document.getElementById('tanggal_lahir').value = data.tanggal_lahir;
        document.getElementById('jenis_kelamin').value = data.jenis_kelamin;
        document.getElementById('agama').value = data.agama;
        document.getElementById('pendidikan').value = data.pendidikan;
        document.getElementById('pekerjaan').value = data.pekerjaan;
        document.getElementById('status_perkawinan').value = data.status_perkawinan;
        document.getElementById('status_hubungan').value = data.status_hubungan;
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Hapus Data Keluarga?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'rounded-3xl shadow-xl border-0',
                confirmButton: 'rounded-xl px-6 py-2.5 font-bold',
                cancelButton: 'rounded-xl px-6 py-2.5 font-bold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "?page=keluarga&delete=" + id;
            }
        })
    }
    
    // Notification for actions
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');
        if (msg) {
            let title = '';
            let text = '';
            if (msg === 'added') {
                title = 'Berhasil!';
                text = 'Data anggota keluarga berhasil ditambahkan.';
            } else if (msg === 'updated') {
                title = 'Diperbarui!';
                text = 'Data anggota keluarga berhasil diperbarui.';
            } else if (msg === 'deleted') {
                title = 'Terhapus!';
                text = 'Data anggota keluarga berhasil dihapus.';
            }
            
            if (title) {
                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'success',
                    confirmButtonColor: '#059669',
                    confirmButtonText: 'Tutup',
                    customClass: {
                        popup: 'rounded-3xl shadow-xl border-0',
                        confirmButton: 'rounded-xl px-6 py-2.5 font-bold'
                    }
                });
                
                // Clean URL
                window.history.replaceState(null, null, window.location.pathname + "?page=keluarga");
            }
        }
    });
</script>

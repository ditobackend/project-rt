<?php
require_once __DIR__ . '/../../config/database.php';

$user_id = $_SESSION['user_id'];

// Cek apakah warga sudah punya KK
$kk_query = $conn->query("SELECT id, no_kk FROM kartu_keluarga WHERE user_id = $user_id LIMIT 1");
$kk_data  = $kk_query ? $kk_query->fetch_assoc() : null;
$kk_id    = $kk_data ? $kk_data['id'] : 0;

// Auto-patch database: pastikan data_keluarga_id bersifat NULLABLE agar INSERT tidak error
@$conn->query("ALTER TABLE `surat_pengantar` MODIFY COLUMN `data_keluarga_id` INT NULL");

// Handle Submit Pengajuan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_surat'])) {
    if (!$kk_data) {
        header("Location: ?page=surat_pengajuan&msg=no_kk"); exit;
    }
    $anggota_id  = (int)$_POST['anggota_id'];
    $jenis_surat = $conn->real_escape_string($_POST['jenis_surat']);
    $keperluan   = $conn->real_escape_string($_POST['keperluan']);

    $sql = "INSERT INTO surat_pengantar (user_id, anggota_keluarga_id, jenis_surat, keperluan, status) 
            VALUES ($user_id, $anggota_id, '$jenis_surat', '$keperluan', 'pending')";
    $conn->query($sql);
    header("Location: ?page=surat_pengajuan&msg=added"); exit;
}

// Ambil data pengajuan
$query_surat = $conn->query("
    SELECT sp.*, ak.nama_lengkap, ak.nik 
    FROM surat_pengantar sp 
    JOIN anggota_keluarga ak ON sp.anggota_keluarga_id = ak.id 
    WHERE sp.user_id = $user_id 
    ORDER BY sp.tanggal_pengajuan DESC
");

// Ambil data anggota keluarga untuk dropdown
$query_anggota = $kk_id ? $conn->query("SELECT id, nama_lengkap, status_hubungan FROM anggota_keluarga WHERE kk_id = $kk_id ORDER BY nama_lengkap ASC") : null;

?>

<div class="mb-10 flex justify-between items-center">
    <div>
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Surat Pengantar</h2>
        <p class="text-secondary-500 mt-1">Ajukan surat pengantar RT secara otomatis untuk keperluan administrasi.</p>
    </div>
    <button onclick="openModalSurat()" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-primary-600/30 transition-all active:scale-95 flex items-center gap-2">
        <i class="fas fa-paper-plane"></i> Ajukan Surat
    </button>
</div>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'added'): ?>
    <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl mb-6 flex items-center gap-3 border border-emerald-200">
        <i class="fas fa-check-circle text-xl"></i>
        <span class="font-bold">Pengajuan surat berhasil dikirim! Menunggu persetujuan Ketua RT.</span>
    </div>
    <?php elseif ($_GET['msg'] === 'no_kk'): ?>
    <div class="bg-amber-50 text-amber-700 p-4 rounded-xl mb-6 flex items-center gap-3 border border-amber-200">
        <i class="fas fa-exclamation-triangle text-xl"></i>
        <span class="font-bold">Kartu Keluarga Anda belum terdaftar. Hubungi Admin RT terlebih dahulu.</span>
    </div>
    <?php endif; ?>
<?php endif; ?>

<div class="bg-white rounded-3xl shadow-sm border border-secondary-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-secondary-50 text-secondary-500 text-xs uppercase tracking-wider border-b border-secondary-100">
                    <th class="p-4 font-black">Tanggal</th>
                    <th class="p-4 font-black">Atas Nama</th>
                    <th class="p-4 font-black">Jenis Surat</th>
                    <th class="p-4 font-black">Keperluan</th>
                    <th class="p-4 font-black">Status</th>
                    <th class="p-4 font-black text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary-50 text-sm">
                <?php if ($query_surat->num_rows > 0): ?>
                    <?php while ($row = $query_surat->fetch_assoc()): ?>
                        <tr class="hover:bg-secondary-50/50 transition-colors">
                            <td class="p-4 text-secondary-500 font-medium whitespace-nowrap">
                                <?= date('d M Y H:i', strtotime($row['tanggal_pengajuan'])) ?>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-secondary-900"><?= htmlspecialchars($row['nama_lengkap']) ?></p>
                                <p class="text-xs text-secondary-400"><?= htmlspecialchars($row['nik']) ?></p>
                            </td>
                            <td class="p-4 font-bold text-secondary-700"><?= htmlspecialchars($row['jenis_surat']) ?></td>
                            <td class="p-4 text-secondary-600 max-w-xs truncate" title="<?= htmlspecialchars($row['keperluan']) ?>">
                                <?= htmlspecialchars($row['keperluan']) ?>
                            </td>
                            <td class="p-4">
                                <?php if ($row['status'] == 'pending'): ?>
                                    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold flex items-center w-max gap-1">
                                        <i class="fas fa-clock"></i> Menunggu
                                    </span>
                                <?php elseif ($row['status'] == 'disetujui'): ?>
                                    <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center w-max gap-1">
                                        <i class="fas fa-check"></i> Disetujui
                                    </span>
                                <?php elseif ($row['status'] == 'ditolak'): ?>
                                    <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold flex items-center w-max gap-1" title="<?= htmlspecialchars($row['keterangan_tolak']) ?>">
                                        <i class="fas fa-times"></i> Ditolak
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center">
                                <?php if ($row['status'] == 'disetujui'): ?>
                                    <a href="cetak_surat.php?id=<?= $row['id'] ?>" target="_blank" class="px-3 py-1.5 bg-primary-50 text-primary-600 hover:bg-primary-600 hover:text-white rounded-lg font-bold text-xs transition-colors">
                                        <i class="fas fa-print mr-1"></i> Cetak
                                    </a>
                                <?php else: ?>
                                    <span class="text-secondary-300 text-xs italic">Belum bisa dicetak</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="p-8 text-center text-secondary-400">Belum ada riwayat pengajuan surat.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Pengajuan Surat -->
<div id="modalSurat" class="fixed inset-0 z-50 hidden bg-secondary-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg flex flex-col overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="modalSuratContent">
        <div class="p-6 border-b border-secondary-100 flex justify-between items-center bg-secondary-50">
            <h3 class="text-xl font-black text-secondary-900">Ajukan Surat Pengantar</h3>
            <button onclick="closeModalSurat()" class="text-secondary-400 hover:text-red-500 transition-colors"><i class="fas fa-times text-xl"></i></button>
        </div>
        
        <div class="p-6">
            <?php if (!$kk_data): ?>
                <div class="text-center py-6">
                    <i class="fas fa-id-card text-4xl text-amber-400 mb-3"></i>
                    <p class="font-bold text-secondary-900">KK Belum Terdaftar</p>
                    <p class="text-sm text-secondary-500 mb-4">Hubungi Admin RT untuk mendaftarkan Kartu Keluarga Anda terlebih dahulu.</p>
                    <a href="?page=keluarga" class="inline-block px-6 py-2 bg-primary-600 text-white font-bold rounded-xl hover:bg-primary-700">Ke Data Keluarga</a>
                </div>
            <?php elseif (!$query_anggota || $query_anggota->num_rows == 0): ?>
                <div class="text-center py-6">
                    <i class="fas fa-users text-4xl text-secondary-300 mb-3"></i>
                    <p class="font-bold text-secondary-900">Belum Ada Anggota Keluarga</p>
                    <p class="text-sm text-secondary-500 mb-4">Tambahkan data anggota keluarga terlebih dahulu untuk bisa mengajukan surat.</p>
                    <a href="?page=keluarga" class="inline-block px-6 py-2 bg-primary-600 text-white font-bold rounded-xl hover:bg-primary-700">Ke Data Keluarga</a>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-secondary-600 mb-1">Pilih Anggota Keluarga *</label>
                        <select name="anggota_id" required class="w-full px-4 py-3 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all text-secondary-900 font-medium">
                            <option value="">-- Pilih Anggota --</option>
                            <?php while($k = $query_anggota->fetch_assoc()): ?>
                                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_lengkap']) ?> (<?= $k['status_hubungan'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                        <p class="text-[10px] text-secondary-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Data KTP anggota terpilih akan otomatis terlampir untuk Ketua RT.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-secondary-600 mb-1">Jenis Surat Pengantar *</label>
                        <select name="jenis_surat" required class="w-full px-4 py-3 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all text-secondary-900 font-medium">
                            <option value="">-- Pilih Jenis Surat --</option>
                            <option value="Pengantar Pembuatan KTP">Pengantar Pembuatan KTP</option>
                            <option value="Pengantar Pembuatan SKCK">Pengantar Pembuatan SKCK</option>
                            <option value="Keterangan Domisili">Keterangan Domisili</option>
                            <option value="Keterangan Usaha">Keterangan Usaha</option>
                            <option value="Pengantar Nikah">Pengantar Nikah</option>
                            <option value="Keterangan Tidak Mampu (SKTM)">Keterangan Tidak Mampu (SKTM)</option>
                            <option value="Lainnya">Lainnya...</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-secondary-600 mb-1">Keperluan Spesifik *</label>
                        <textarea name="keperluan" required rows="3" placeholder="Tuliskan keperluan secara spesifik..." class="w-full px-4 py-3 bg-secondary-50 border border-secondary-200 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all text-secondary-900 font-medium resize-none"></textarea>
                    </div>

                    <div class="pt-4 border-t border-secondary-100 flex justify-end gap-3">
                        <button type="button" onclick="closeModalSurat()" class="px-6 py-2.5 text-secondary-600 font-bold hover:bg-secondary-200 rounded-xl transition-colors">Batal</button>
                        <button type="submit" name="submit_surat" class="px-8 py-2.5 bg-primary-600 text-white font-bold rounded-xl shadow-lg shadow-primary-600/30 hover:bg-primary-700 active:scale-95 transition-all">Kirim Pengajuan</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const modalSurat = document.getElementById('modalSurat');
    const modalSuratContent = document.getElementById('modalSuratContent');
    
    function openModalSurat() {
        modalSurat.classList.remove('hidden');
        setTimeout(() => {
            modalSuratContent.classList.remove('scale-95', 'opacity-0');
            modalSuratContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
    
    function closeModalSurat() {
        modalSuratContent.classList.remove('scale-100', 'opacity-100');
        modalSuratContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modalSurat.classList.add('hidden');
        }, 300);
    }
</script>

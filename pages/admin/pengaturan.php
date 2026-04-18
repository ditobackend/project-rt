<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../config/database.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: loginadmin.php");
    exit;
}

$message = "";

// Proses Update Pengaturan
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($_POST['settings'] as $kunci => $nilai) {
        $final_nilai = $nilai;
        // Bersihkan titik (ribuan) jika ini adalah iuran agar tersimpan sebagai angka murni
        if ($kunci === 'iuran_bulanan' || $kunci === 'iuran_keamanan') {
            $final_nilai = preg_replace('/[^0-9]/', '', $nilai);
        }
        
        $stmt = $conn->prepare("UPDATE pengaturan SET nilai = ? WHERE kunci = ?");
        $stmt->bind_param("ss", $final_nilai, $kunci);
        $stmt->execute();
    }
    $message = "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Pengaturan sistem telah diperbarui.',
                icon: 'success',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    popup: 'rounded-[1.25rem] shadow-xl border-0',
                    confirmButton: 'rounded-xl px-6 py-2.5 font-bold'
                }
            });
        });
    </script>";
}

// Ambil Data Pengaturan
$settings = [];
$result = mysqli_query($conn, "SELECT kunci, nilai FROM pengaturan");
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['kunci']] = $row['nilai'];
}
?>

<?= $message ?>

<div class="mb-10">
    <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Pengaturan Sistem</h2>
    <p class="text-secondary-500 mt-1">Konfigurasikan tarif iuran dan informasi rekening pembayaran warga.</p>
</div>

<form action="" method="POST" class="space-y-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Pengaturan Iuran -->
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-secondary-100 h-full">
            <div class="flex items-center gap-4 mb-8 border-b border-secondary-50 pb-6">
                <div class="w-12 h-12 bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-secondary-900">Iuran & Tarif</h3>
                    <p class="text-xs text-secondary-400 font-bold uppercase tracking-widest mt-0.5">Atur Besaran Tagihan Warga</p>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Tarif Iuran Bulanan</label>
                    <div class="relative group">
                        <span class="absolute left-5 top-1/2 -translate-y-1/2 font-black text-secondary-400">Rp</span>
                        <input type="text" name="settings[iuran_bulanan]" value="<?= $settings['iuran_bulanan'] ?? '0' ?>" 
                            class="currency-input w-full pl-12 pr-6 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-black transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Tarif Iuran Keamanan</label>
                    <div class="relative group">
                        <span class="absolute left-5 top-1/2 -translate-y-1/2 font-black text-secondary-400">Rp</span>
                        <input type="text" name="settings[iuran_keamanan]" value="<?= $settings['iuran_keamanan'] ?? '0' ?>" 
                            class="currency-input w-full pl-12 pr-6 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-black transition-all">
                    </div>
                </div>

                <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 flex gap-4">
                    <i class="fas fa-exclamation-triangle text-amber-500 mt-1"></i>
                    <p class="text-xs text-amber-800 font-medium leading-relaxed">Nilai ini akan muncul sebagai referensi di halaman pembayaran warga.</p>
                </div>
            </div>
        </div>

        <!-- Pengaturan Rekening -->
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-secondary-100 h-full">
            <div class="flex items-center gap-4 mb-8 border-b border-secondary-50 pb-6">
                <div class="w-12 h-12 bg-secondary-900 text-white rounded-2xl flex items-center justify-center">
                    <i class="fas fa-university text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-secondary-900">Informasi Rekening</h3>
                    <p class="text-xs text-secondary-400 font-bold uppercase tracking-widest mt-0.5">Tujuan Transfer Manual</p>
                </div>
            </div>

        <!-- Pengaturan Rekening Utama -->
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-secondary-100 h-full">
            <div class="flex items-center gap-4 mb-8 border-b border-secondary-50 pb-6">
                <div class="w-12 h-12 bg-secondary-900 text-white rounded-2xl flex items-center justify-center">
                    <i class="fas fa-university text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-secondary-900">Rekening Utama</h3>
                    <p class="text-xs text-secondary-400 font-bold uppercase tracking-widest mt-0.5">Tujuan Transfer Manual</p>
                </div>
            </div>

            <div class="space-y-6">
                <div class="p-6 bg-secondary-50 rounded-3xl space-y-5">
                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Pilih Bank</label>
                        <div class="relative">
                            <select name="settings[rek_bank_pilihan]" 
                                class="w-full px-5 py-4 bg-white border-0 focus:ring-2 focus:ring-primary-500 rounded-xl text-secondary-900 font-bold appearance-none cursor-pointer transition-all">
                                <?php
                                $banks = ['Bank BCA', 'Bank Mandiri', 'Bank BNI', 'Bank BRI', 'Bank Permata', 'Bank CIMB Niaga', 'Bank Danamon'];
                                $selectedBank = $settings['rek_bank_pilihan'] ?? 'Bank BCA';
                                foreach($banks as $b): 
                                ?>
                                <option value="<?= $b ?>" <?= $selectedBank == $b ? 'selected' : '' ?>><?= $b ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-secondary-300 pointer-events-none"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Nomor Rekening</label>
                        <input type="text" name="settings[rek_bank_nomor]" value="<?= $settings['rek_bank_nomor'] ?? '' ?>" 
                            placeholder="Contoh: 123 456 7890"
                            class="w-full px-5 py-4 bg-white border-0 focus:ring-2 focus:ring-primary-500 rounded-xl text-secondary-900 font-bold tracking-wider transition-all">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Atas Nama (Pemilik)</label>
                        <input type="text" name="settings[rek_bank_atas_nama]" value="<?= $settings['rek_bank_atas_nama'] ?? '' ?>" 
                            placeholder="Contoh: Kas RT 06/08"
                            class="w-full px-5 py-4 bg-white border-0 focus:ring-2 focus:ring-primary-500 rounded-xl text-secondary-600 font-medium transition-all">
                    </div>
                </div>

                <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100 flex gap-4">
                    <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                    <p class="text-[11px] text-blue-800 font-medium leading-relaxed">Pilih bank dan masukkan detail rekening yang akan ditampilkan ke warga untuk metode transfer manual.</p>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-end pt-4">
        <button type="submit" 
            class="px-10 py-5 bg-secondary-900 text-white font-black rounded-2xl hover:bg-secondary-800 transition-all active:scale-95 shadow-xl shadow-secondary-900/10 uppercase tracking-widest text-sm flex items-center gap-3">
            <i class="fas fa-save"></i> Simpan Perubahan
        </button>
    </div>
</form>

<script>
    // Currency formatting for iuran inputs
    document.querySelectorAll('.currency-input').forEach(inp => {
        inp.addEventListener("input", function() {
            let value = this.value.replace(/[^0-9]/g, "");
            let number = parseInt(value);
            if (!isNaN(number)) {
                this.value = number.toLocaleString('id-ID');
            } else {
                this.value = "";
            }
        });
        
        // Initial format
        let val = inp.value.replace(/[^0-9]/g, "");
        if(val) inp.value = parseInt(val).toLocaleString('id-ID');
    });
</script>

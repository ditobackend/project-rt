<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: loginadmin.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($_POST['settings'] as $kunci => $nilai) {
        $final_nilai = $nilai;
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

$settings = [];
$result = mysqli_query($conn, "SELECT kunci, nilai FROM pengaturan");
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['kunci']] = $row['nilai'];
}
?>

<?= $message ?>

<div class="max-w-3xl mx-auto">
    <div class="mb-10">
        <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Pengaturan Tarif</h2>
        <p class="text-secondary-500 mt-1">Konfigurasikan besaran tagihan iuran bulanan dan keamanan warga.</p>
    </div>

    <form action="" method="POST" class="space-y-8">
    <!-- Card 1: Iuran & Tarif -->
    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-secondary-100 flex flex-col">
        <div class="flex items-center gap-4 mb-8 border-b border-secondary-50 pb-6">
            <div class="w-12 h-12 bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center shrink-0">
                <i class="fas fa-money-bill-wave text-xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-black text-secondary-900">Iuran &amp; Tarif</h3>
                <p class="text-xs text-secondary-400 font-bold uppercase tracking-widest mt-0.5">Atur Besaran Tagihan Warga</p>
            </div>
        </div>

        <div class="space-y-6 flex-1">
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
        </div>

        <div class="mt-6 p-4 bg-amber-50 rounded-2xl border border-amber-100 flex gap-4">
            <i class="fas fa-exclamation-triangle text-amber-500 mt-1 shrink-0"></i>
            <p class="text-xs text-amber-800 font-medium leading-relaxed">Nilai ini akan muncul sebagai referensi di halaman pembayaran warga.</p>
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
</div>

<script>
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

        let val = inp.value.replace(/[^0-9]/g, "");
        if (val) inp.value = parseInt(val).toLocaleString('id-ID');
    });
</script>

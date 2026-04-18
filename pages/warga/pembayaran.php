<?php
// pages/warga/pembayaran.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// panggil konfigurasi database dan Midtrans
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/midtrans.php';

$snapToken = null;
$autoOpenSnap = false;
$user_id = $_SESSION['user_id'] ?? null;
$error = '';
$success = '';

// jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle cancellation request first
    if (isset($_POST['cancel_pending'])) {
        // Ambil ID pembayaran pending user ini
        $check_stmt = $conn->prepare("SELECT id FROM pembayaran WHERE user_id = ? AND status = 'pending'");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $pending_data = $check_result->fetch_assoc();
            $cancel_stmt = $conn->prepare("UPDATE pembayaran SET status = 'gagal' WHERE id = ?");
            $cancel_stmt->bind_param("i", $pending_data['id']);
            $cancel_stmt->execute();
            $cancel_stmt->close();
            $success = "Pembayaran pending berhasil dibatalkan.";
        }
        $check_stmt->close();
    } else {
        // Normal payment flow
        $nama = $_POST['nama'] ?? '';
        $kategori = $_POST['kategori'] ?? '';
        $jumlah = isset($_POST['jumlah']) ? preg_replace('/[^0-9]/', '', $_POST['jumlah']) : 0;
        $catatan = $_POST['catatan'] ?? '';

        if (!$user_id) {
            $error = "Silakan login terlebih dahulu sebelum melakukan pembayaran.";
        } else {
            // cek apakah ada pembayaran pending untuk user ini
            $check_stmt = $conn->prepare("SELECT id FROM pembayaran WHERE user_id = ? AND status = 'pending'");
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error = "Anda memiliki pembayaran yang tertunda.";
                $show_cancel_btn = true;
            } else {
                // buat parameter transaksi
                $params = [
                    'transaction_details' => [
                        'order_id' => 'ORDER-' . uniqid(),
                        'gross_amount' => (int) $jumlah,
                    ],
                    'customer_details' => [
                        'first_name' => $nama,
                        'email' => $_SESSION['user_email'] ?? 'warga@rt06.com',
                    ],
                    'item_details' => [
                        [
                            'id' => strtolower(str_replace(' ', '-', $kategori)),
                            'price' => (int) $jumlah,
                            'quantity' => 1,
                            'name' => $kategori,
                        ],
                    ],
                ];

                // dapatkan snapToken dari Midtrans
                try {
                    $snapToken = \Midtrans\Snap::getSnapToken($params);
                    $autoOpenSnap = true;
                } catch (Exception $e) {
                    $error = "Gagal terhubung ke Midtrans: " . $e->getMessage();
                }

                // simpan pembayaran ke database
                if ($snapToken) {
                    $order_id = $params['transaction_details']['order_id'];
                    $stmt = $conn->prepare("INSERT INTO pembayaran (user_id, jumlah, metode, status, order_id, kategori, catatan) VALUES (?, ?, 'midtrans', 'pending', ?, ?, ?)");
                    $stmt->bind_param("iisss", $user_id, $jumlah, $order_id, $kategori, $catatan);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $check_stmt->close();
        }
    }
}

// Ambil Data Pengaturan
$settings = [];
$res_settings = mysqli_query($conn, "SELECT kunci, nilai FROM pengaturan");
while ($s_row = mysqli_fetch_assoc($res_settings)) {
    $settings[$s_row['kunci']] = $s_row['nilai'];
}

// Default values if not found (fallback)
$val_bulanan = isset($settings['iuran_bulanan']) ? number_format(preg_replace('/[^0-9]/', '', $settings['iuran_bulanan']), 0, ',', '.') : '50.000';
$val_keamanan = isset($settings['iuran_keamanan']) ? number_format(preg_replace('/[^0-9]/', '', $settings['iuran_keamanan']), 0, ',', '.') : '25.000';
$bank_pilihan = $settings['rek_bank_pilihan'] ?? 'Bank BCA';
$bank_no = $settings['rek_bank_nomor'] ?? '123 456 7890';
$bank_atas_nama = $settings['rek_bank_atas_nama'] ?? 'a.n. Kas RT 06/08';
?>

<div class="mb-10">
    <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Pembayaran Iuran</h2>
    <p class="text-secondary-500 mt-1">Lakukan pembayaran iuran bulanan, iuran keamanan, atau donasi</p>
</div>

<div class="grid lg:grid-cols-3 gap-8 items-start">
    <!-- Form Payment -->
    <div class="lg:col-span-2">
        <div class="bg-white p-8 md:p-10 rounded-[2.5rem] shadow-sm border border-secondary-100">
            <div class="flex items-center justify-between mb-8 border-b border-secondary-50 pb-6">
                <h3 class="text-xl font-black text-secondary-900">Form Pembayaran Digital</h3>
            </div>

            <?php if ($success): ?>
                <div
                    class="p-4 mb-6 bg-green-50 text-green-700 rounded-2xl border border-green-100 flex items-center gap-3 animate-in fade-in transition-all">
                    <i class="fas fa-check-circle"></i>
                    <p class="text-xs font-bold uppercase tracking-tight"><?= $success ?></p>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div
                    class="p-4 mb-6 bg-red-50 text-red-700 rounded-2xl border border-red-100 flex items-center gap-3 animate-in fade-in transition-all">
                    <i class="fas fa-exclamation-circle text-lg"></i>
                    <div class="flex-1">
                        <p class="text-xs font-bold uppercase tracking-tight"><?= $error ?></p>
                        <?php if (isset($show_cancel_btn)): ?>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="cancel_pending" value="1">
                                <button type="submit"
                                    class="text-[10px] font-black uppercase text-red-600 hover:underline">Batalkan & Buat
                                    Baru</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label
                            class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Nama
                            Pembayar</label>
                        <input type="text" name="nama" value="<?= $_SESSION['user_nama'] ?? '' ?>"
                            placeholder="Nama Lengkap" required
                            class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium transition-all">
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Kategori
                            Iuran</label>
                        <select name="kategori" required
                            class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium transition-all appearance-none cursor-pointer">
                            <option value="">Pilih Kategori</option>
                            <option value="Iuran Bulanan">Iuran Bulanan</option>
                            <option value="Iuran Keamanan">Iuran Keamanan</option>
                            <option value="Donasi Kegiatan">Donasi Kegiatan</option>
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label
                            class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Jumlah
                            Pembayaran</label>
                        <div class="relative">
                            <input type="text" id="jumlah" name="jumlah" placeholder="Rp 0" required
                                class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-black transition-all">
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Metode
                            Otomatis</label>
                        <div
                            class="w-full px-5 py-4 bg-secondary-100 text-secondary-400 border-0 rounded-2xl font-bold flex items-center gap-3">
                            <i class="fas fa-bolt text-primary-500"></i>
                            E-Wallet, VA, QRIS (Midtrans)
                        </div>
                    </div>
                </div>

                <div>
                    <label
                        class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Catatan
                        Tambahan (Opsional)</label>
                    <textarea name="catatan" placeholder="Tambahkan keterangan jika perlu..."
                        class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium transition-all resize-none h-24"></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full py-5 bg-primary-600 hover:bg-primary-700 text-white font-black rounded-2xl shadow-xl shadow-primary-500/20 transition-all uppercase tracking-widest text-sm flex items-center justify-center gap-3 active:scale-95">
                        <i class="fas fa-shield-alt"></i>
                        Proses Pembayaran Aman
                    </button>
                    <p class="text-center text-[10px] text-secondary-400 mt-4 uppercase tracking-widest font-bold">
                        Enkripsi 256-bit Secure Socket Layer</p>
                </div>
            </form>

            <?php if (!empty($snapToken)): ?>
                <div class="mt-6">
                    <button id="pay-button"
                        class="w-full py-4 bg-green-600 text-white font-black rounded-2xl shadow-lg shadow-green-500/20 hover:bg-green-700 transition-all uppercase tracking-widest text-xs animate-pulse">
                        Buka Jendela Pembayaran
                    </button>
                </div>
                <script src="https://app.sandbox.midtrans.com/snap/snap.js"
                    data-client-key="<?php echo \Midtrans\Config::$clientKey; ?>"></script>
                <script type="text/javascript">
                    (function () {
                        const snapToken = '<?= $snapToken ?>';
                        const currentOrderId = '<?= $order_id ?? "" ?>';

                        function updatePaymentStatus(orderId, status, callback) {
                            fetch('<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/update_payment_status.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'order_id=' + encodeURIComponent(orderId) + '&status=' + encodeURIComponent(status)
                            })
                                .then(res => res.json())
                                .then(data => { if (callback) callback(data); })
                                .catch(err => { console.error('Update status error:', err); if (callback) callback(null); });
                        }

                        function triggerSnapPay(token) {
                            if (!token) return;
                            snap.pay(token, {
                                onSuccess: function (result) {
                                    updatePaymentStatus(currentOrderId, 'berhasil', function () {
                                        Swal.fire('Berhasil!', 'Pembayaran telah diterima.', 'success').then(() => {
                                            window.location.href = 'dashboard_warga.php?page=pembayaran';
                                        });
                                    });
                                },
                                onPending: function (result) {
                                    Swal.fire('Menunggu!', 'Lengkapi pembayaran Anda.', 'info');
                                },
                                onError: function (result) {
                                    updatePaymentStatus(currentOrderId, 'gagal', function () {
                                        Swal.fire('Gagal!', 'Terjadi kesalahan sistem.', 'error');
                                    });
                                },
                                onClose: function () {
                                    console.log('Snap closed');
                                }
                            });
                        }

                        document.getElementById('pay-button').addEventListener('click', () => triggerSnapPay(snapToken));
                        <?php if ($autoOpenSnap): ?> triggerSnapPay(snapToken); <?php endif; ?>
                    })();
                </script>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info Column -->
    <div class="space-y-8">
        <!-- Informasi Iuran -->
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-secondary-100">
            <h3 class="text-lg font-black text-secondary-900 mb-6 flex items-center">
                <i class="fas fa-info-circle mr-3 text-primary-500"></i>
                Informasi Iuran
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-4 bg-secondary-50 rounded-2xl">
                    <span class="text-xs font-bold text-secondary-600 uppercase">Bulanan</span>
                    <span class="font-black text-secondary-900">Rp <?= $val_bulanan ?></span>
                </div>
                <div class="flex justify-between items-center p-4 bg-secondary-50 rounded-2xl">
                    <span class="text-xs font-bold text-secondary-600 uppercase">Keamanan</span>
                    <span class="font-black text-secondary-900">Rp <?= $val_keamanan ?></span>
                </div>
                <div class="p-4 border-2 border-dashed border-secondary-100 rounded-2xl text-center">
                    <p class="text-[10px] font-black text-secondary-300 uppercase tracking-widest mb-1">Donasi Kegiatan
                    </p>
                    <p class="text-sm font-bold text-secondary-500 italic">Nilai Sukarela</p>
                </div>
            </div>
        </div>

        <!-- Rekening Manual -->
        <div class="bg-secondary-900 p-8 rounded-[2.5rem] shadow-xl text-white relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/5 rounded-full"></div>
            <h3 class="text-lg font-black mb-6 flex items-center">
                <i class="fas fa-university mr-3 text-primary-400"></i>
                Transfer Manual
            </h3>
            <div class="space-y-4">
                <div
                    class="p-6 bg-white/5 rounded-3xl border border-white/10 hover:bg-white/10 transition-all cursor-pointer group">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-[10px] font-black uppercase tracking-widest text-primary-400"><?= $bank_pilihan ?></span>
                        <i class="far fa-copy text-sm opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                    <p class="text-2xl font-bold tracking-wider mb-1"><?= $bank_no ?></p>
                    <p class="text-xs text-secondary-400 font-medium"><?= $bank_atas_nama ?></p>
                </div>
            </div>
            <p class="mt-6 text-[10px] text-secondary-500 leading-relaxed italic text-center">
                *Simpan bukti transfer & konfirmasi ke pengurus RT melalui WhatsApp jika membayar manual.
            </p>
        </div>
    </div>
</div>

<script>
    const jumlahInput = document.getElementById("jumlah");
    jumlahInput.addEventListener("input", function () {
        let value = this.value.replace(/[^0-9]/g, "");
        let number = parseInt(value);
        if (!isNaN(number) && number > 0) {
            this.value = "Rp " + number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        } else {
            this.value = "";
        }
    });
</script>
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

// jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $jumlah = preg_replace('/[^0-9]/', '', $_POST['jumlah']); // hilangkan format Rp
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
            $error = "Anda sudah memiliki pembayaran yang sedang diproses. Harap selesaikan pembayaran sebelumnya.";
        } else {
            // buat parameter transaksi
            $params = [
                'transaction_details' => [
                    'order_id' => 'ORDER-' . uniqid(),
                    'gross_amount' => (int)$jumlah,
                ],
                'customer_details' => [
                    'first_name' => $nama,
                    'email' => 'user@example.com', // bisa ambil dari session user
                ],
                'item_details' => [
                    [
                        'id' => strtolower(str_replace(' ', '-', $kategori)),
                        'price' => (int)$jumlah,
                        'quantity' => 1,
                        'name' => $kategori,
                    ],
                ],
                'notification_url' => 'http://localhost/project-rt/midtrans_notification.php',
            ];

            // dapatkan snapToken dari Midtrans
            try {
                $snapToken = \Midtrans\Snap::getSnapToken($params);
                $autoOpenSnap = true;
            } catch (Exception $e) {
                $error = "Gagal mendapatkan token pembayaran: " . $e->getMessage();
            }

            // simpan pembayaran ke database
            if ($snapToken) {
                $order_id = $params['transaction_details']['order_id'];
                $stmt = $conn->prepare("INSERT INTO pembayaran (user_id, jumlah, metode, status, order_id) VALUES (?, ?, 'midtrans', 'pending', ?)");
                $stmt->bind_param("iis", $user_id, $jumlah, $order_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        $check_stmt->close();
    }
}
?>

<h2 class="text-2xl font-bold mb-2">Pembayaran</h2>
<p class="text-gray-600 mb-6">Lakukan pembayaran iuran dan donasi kegiatan</p>

<div class="grid md:grid-cols-2 gap-6">
    <!-- Form -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="font-bold mb-4">Form Pembayaran</h3>

        <form method="POST" class="space-y-4">
            <input type="text" name="nama" placeholder="Nama Warga" required class="w-full border rounded px-4 py-2">

            <select name="kategori" required class="w-full border rounded px-4 py-2">
                <option value="">Pilih kategori</option>
                <option value="Iuran Bulanan">Iuran Bulanan</option>
                <option value="Iuran Keamanan">Iuran Keamanan</option>
                <option value="Donasi Kegiatan">Donasi Kegiatan</option>
            </select>

            <!-- input jumlah pembayaran -->
            <input type="text" id="jumlah" name="jumlah" placeholder="Jumlah Pembayaran" required class="w-full border rounded px-4 py-2">

            <select class="w-full border rounded px-4 py-2">
                <option>Pilih metode</option>
                <option selected>Midtrans Payment Gateway</option>
            </select>

            <textarea name="catatan" placeholder="Catatan (Opsional)" class="w-full border rounded px-4 py-2"></textarea>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                <i class="fas fa-credit-card mr-2"></i>Bayar
            </button>
        </form>

        <?php if (isset($error)): ?>
        <div class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <?php echo $error; ?>
        </div>
        <?php elseif (!empty($snapToken)): ?>
        <div class="mt-4">
            <button id="pay-button" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">
                Lanjutkan Pembayaran
            </button>
        </div>

        <!-- Script Midtrans -->
        <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?php echo \Midtrans\Config::$clientKey; ?>"></script>
        <script type="text/javascript">
            (function () {
                const snapToken = '<?= $snapToken ?>';

                function triggerSnapPay(token) {
                    if (!token) {
                        return;
                    }

                    snap.pay(token, {
                        onSuccess: function(result){
                            alert("Pembayaran berhasil!");
                            console.log(result);
                            // Update status di server
                            fetch('update_payment_status.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'order_id=' + encodeURIComponent(result.order_id) + '&status=berhasil'
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    window.location.reload(); // Reload halaman
                                }
                            })
                            .catch(error => console.error('Error:', error));
                        },
                        onPending: function(result){
                            alert("Menunggu pembayaran...");
                            console.log(result);
                        },
                        onError: function(result){
                            alert("Pembayaran gagal!");
                            console.log(result);
                        },
                        onClose: function(){
                            alert("Anda menutup popup tanpa menyelesaikan pembayaran");
                        }
                    });
                }

                const payButton = document.getElementById('pay-button');
                if (payButton) {
                    payButton.addEventListener('click', function () {
                        triggerSnapPay(snapToken);
                    });
                }

                <?php if ($autoOpenSnap): ?>
                // Otomatis tampilkan modal setelah server mengembalikan token
                triggerSnapPay(snapToken);
                <?php endif; ?>
            })();
        </script>
        <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="space-y-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="font-bold mb-4">Informasi Iuran</h3>
            <ul class="space-y-2 text-gray-700">
                <li class="flex justify-between"><span>Iuran Bulanan</span><span>Rp 50.000</span></li>
                <li class="flex justify-between"><span>Iuran Keamanan</span><span>Rp 25.000</span></li>
                <li class="flex justify-between"><span>Donasi Kegiatan</span><span>Sukarela</span></li>
            </ul>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="font-bold mb-4">Rekening Pembayaran</h3>
            <div class="p-4 bg-blue-50 rounded mb-2">
                <p class="font-semibold">Bank BCA</p>
                <p>1234567890</p>
                <p class="text-sm text-gray-600">a.n. Kas RT 001</p>
            </div>
            <div class="p-4 bg-green-50 rounded">
                <p class="font-semibold">Bank Mandiri</p>
                <p>0987654321</p>
                <p class="text-sm text-gray-600">a.n. Kas RT 001</p>
            </div>
        </div>
    </div>
</div>

<!-- Script format Rupiah -->
<script>
function formatRupiah(angka) {
    return "Rp " + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}
const jumlahInput = document.getElementById("jumlah");
jumlahInput.addEventListener("input", function() {
    let value = this.value.replace(/[^0-9]/g, "");
    let number = parseInt(value);
    if (!isNaN(number) && number > 0) {
        this.value = formatRupiah(number);
    } else {
        this.value = "";
    }
});
</script>


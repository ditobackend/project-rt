<?php
// pages/warga/pembayaran.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/midtrans.php';

$snapToken = null;
$autoOpenSnap = false;
$user_id = $_SESSION['user_id'] ?? null;
$error = '';
$success = '';

$daftar_bulan = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember',
];

$tahun_pilihan = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
if ($tahun_pilihan < 2020 || $tahun_pilihan > 2030) {
    $tahun_pilihan = (int)date('Y');
}

// Ambil data bulan yang sudah lunas untuk user ini di tahun terpilih
$paid_months = [];
if ($user_id) {
    $stmt_paid = $conn->prepare("SELECT kategori, catatan FROM pembayaran WHERE user_id = ? AND status = 'berhasil'");
    $stmt_paid->bind_param("i", $user_id);
    $stmt_paid->execute();
    $res_paid = $stmt_paid->get_result();
    while ($r = $res_paid->fetch_assoc()) {
        $text = ($r['kategori'] ?? '') . ' ' . ($r['catatan'] ?? '');
        foreach ($daftar_bulan as $code => $m_name) {
            if (stripos($text, $m_name) !== false && stripos($text, (string)$tahun_pilihan) !== false) {
                $paid_months[$code] = true;
            }
        }
    }
    $stmt_paid->close();
}

// Handler POST form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['cancel_pending'])) {
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
        $nama = trim($_POST['nama'] ?? ($_SESSION['user_nama'] ?? 'Warga'));
        $jenis_pembayaran = $_POST['jenis_pembayaran'] ?? 'iuran_wajib';
        $user_catatan = trim($_POST['catatan'] ?? '');

        if (!$user_id) {
            $error = "Silakan login terlebih dahulu sebelum melakukan pembayaran.";
        } else {
            // Cek apakah ada pembayaran pending
            $check_stmt = $conn->prepare("SELECT id FROM pembayaran WHERE user_id = ? AND status = 'pending'");
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error = "Anda memiliki pembayaran yang tertunda. Silakan lunasi atau batalkan terlebih dahulu.";
                $show_cancel_btn = true;
            } else {
                $item_details = [];
                $gross_amount = 0;
                $kategori = '';
                $catatan = '';

                if ($jenis_pembayaran === 'iuran_wajib') {
                    $selected_months = $_POST['bulan'] ?? [];
                    $tahun = (int)($_POST['tahun'] ?? date('Y'));
                    $donasi_input = isset($_POST['donasi']) ? preg_replace('/[^0-9]/', '', $_POST['donasi']) : '0';
                    $donasi = (int)$donasi_input;

                    if (empty($selected_months) || !is_array($selected_months)) {
                        $error = "Silakan pilih minimal 1 bulan iuran yang ingin dibayar.";
                    } else {
                        sort($selected_months);
                        $count_months = count($selected_months);
                        $total_bulanan = 35000 * $count_months;
                        $total_keamanan = 20000 * $count_months;
                        $gross_amount = $total_bulanan + $total_keamanan + $donasi;

                        $nama_bulan_arr = [];
                        foreach ($selected_months as $m_code) {
                            if (isset($daftar_bulan[$m_code])) {
                                $nama_bulan_arr[] = $daftar_bulan[$m_code];
                            }
                        }
                        $str_bulan = implode(', ', $nama_bulan_arr);

                        $kategori = "Iuran Bulanan ($str_bulan $tahun)";
                        $catatan = "Periode: $str_bulan $tahun | Bulanan (Rp 35.000 x $count_months = Rp " . number_format($total_bulanan, 0, ',', '.') . "), Keamanan (Rp 20.000 x $count_months = Rp " . number_format($total_keamanan, 0, ',', '.') . ")";
                        if ($donasi > 0) {
                            $catatan .= ", Donasi: Rp " . number_format($donasi, 0, ',', '.');
                        }
                        if ($user_catatan) {
                            $catatan .= " | Catatan: " . $user_catatan;
                        }

                        $item_details[] = [
                            'id' => 'iuran-rt-' . $tahun,
                            'price' => $total_bulanan,
                            'quantity' => 1,
                            'name' => 'Iuran RT/Kebersihan (' . $str_bulan . ' ' . $tahun . ')'
                        ];
                        $item_details[] = [
                            'id' => 'iuran-keamanan-' . $tahun,
                            'price' => $total_keamanan,
                            'quantity' => 1,
                            'name' => 'Iuran Keamanan (' . $str_bulan . ' ' . $tahun . ')'
                        ];
                        if ($donasi > 0) {
                            $item_details[] = [
                                'id' => 'donasi-sukarela',
                                'price' => $donasi,
                                'quantity' => 1,
                                'name' => 'Donasi Sukarela Tambahan'
                            ];
                        }
                    }
                } else {
                    // Donasi Sukarela Saja
                    $jumlah_donasi_input = isset($_POST['jumlah_donasi_saja']) ? preg_replace('/[^0-9]/', '', $_POST['jumlah_donasi_saja']) : '0';
                    $jumlah_donasi = (int)$jumlah_donasi_input;

                    if ($jumlah_donasi <= 0) {
                        $error = "Silakan masukkan nominal donasi yang valid.";
                    } else {
                        $gross_amount = $jumlah_donasi;
                        $kategori = "Donasi Kegiatan Sukarela";
                        $catatan = "Donasi Sukarela: Rp " . number_format($jumlah_donasi, 0, ',', '.') . ($user_catatan ? " | Catatan: " . $user_catatan : "");

                        $item_details[] = [
                            'id' => 'donasi-kegiatan',
                            'price' => $jumlah_donasi,
                            'quantity' => 1,
                            'name' => 'Donasi Kegiatan RT'
                        ];
                    }
                }

                if (!$error && $gross_amount > 0) {
                    $order_id = 'ORDER-' . uniqid();
                    $params = [
                        'transaction_details' => [
                            'order_id' => $order_id,
                            'gross_amount' => $gross_amount,
                        ],
                        'customer_details' => [
                            'first_name' => $nama,
                            'email' => $_SESSION['user_email'] ?? 'warga@rt06.com',
                        ],
                        'item_details' => $item_details,
                    ];

                    try {
                        $snapToken = \Midtrans\Snap::getSnapToken($params);
                        $autoOpenSnap = true;
                    } catch (Exception $e) {
                        $error = "Gagal terhubung ke Midtrans: " . $e->getMessage();
                    }

                    if ($snapToken) {
                        $stmt = $conn->prepare("INSERT INTO pembayaran (user_id, jumlah, metode, status, order_id, kategori, catatan) VALUES (?, ?, 'midtrans', 'pending', ?, ?, ?)");
                        $stmt->bind_param("idsss", $user_id, $gross_amount, $order_id, $kategori, $catatan);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
            $check_stmt->close();
        }
    }
}

// Ambil riwayat pembayaran warga ini untuk riwayat di sidebar
$riwayat_pembayaran = [];
if ($user_id) {
    $res_r = $conn->query("SELECT * FROM pembayaran WHERE user_id = $user_id ORDER BY id DESC LIMIT 5");
    if ($res_r) {
        while ($r_row = $res_r->fetch_assoc()) {
            $riwayat_pembayaran[] = $r_row;
        }
    }
}
?>

<div class="mb-8">
    <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Pembayaran Iuran Warga</h2>
    <p class="text-secondary-500 mt-1">Pilih bulan dan tahun untuk membayar iuran bulanan wajib (Rp 35.000 + Rp 20.000 = Rp 55.000/bulan) atau donasi sukarela.</p>
</div>

<div class="grid lg:grid-cols-3 gap-8 items-start">
    <!-- Main Payment Section -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white p-6 md:p-8 rounded-[2.5rem] shadow-sm border border-secondary-100">
            <div class="flex items-center justify-between mb-6 border-b border-secondary-100 pb-4">
                <h3 class="text-xl font-black text-secondary-900 flex items-center gap-2">
                    <i class="fas fa-credit-card text-emerald-600"></i> Form Pembayaran Iuran
                </h3>
            </div>

            <?php if ($success): ?>
                <div class="p-4 mb-6 bg-green-50 text-green-700 rounded-2xl border border-green-200 flex items-center gap-3">
                    <i class="fas fa-check-circle text-lg"></i>
                    <p class="text-xs font-bold uppercase tracking-tight"><?= htmlspecialchars($success) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-4 mb-6 bg-red-50 text-red-700 rounded-2xl border border-red-200 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-lg"></i>
                    <div class="flex-1">
                        <p class="text-xs font-bold uppercase tracking-tight"><?= htmlspecialchars($error) ?></p>
                        <?php if (isset($show_cancel_btn)): ?>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="cancel_pending" value="1">
                                <button type="submit" class="text-[11px] font-black uppercase text-red-600 hover:underline">
                                    <i class="fas fa-undo mr-1"></i>Batalkan Transaksi Pending & Buat Baru
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" id="formPembayaran" class="space-y-6">
                <!-- Mode Pembayaran Selector -->
                <div>
                    <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Pilih Jenis Pembayaran</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition-all border-emerald-500 bg-emerald-50/40" id="label_mode_iuran">
                            <input type="radio" name="jenis_pembayaran" value="iuran_wajib" checked class="sr-only" onchange="togglePaymentMode()">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm font-bold shadow-sm">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-black text-secondary-900">Iuran Wajib Bulanan</p>
                                    <p class="text-[10px] text-emerald-700 font-bold mt-0.5">Rp 55.000 / bulan</p>
                                </div>
                            </div>
                        </label>

                        <label class="relative flex items-center p-4 rounded-2xl border-2 border-secondary-200 bg-secondary-50 cursor-pointer transition-all" id="label_mode_donasi">
                            <input type="radio" name="jenis_pembayaran" value="donasi_saja" class="sr-only" onchange="togglePaymentMode()">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm font-bold shadow-sm">
                                    <i class="fas fa-hand-holding-heart"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-black text-secondary-900">Donasi Sukarela Saja</p>
                                    <p class="text-[10px] text-secondary-500 font-medium mt-0.5">Nominal bebas sukarela</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Nama Pembayar -->
                <div>
                    <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5 ml-1">Nama Pembayar</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($_SESSION['user_nama'] ?? '') ?>" required
                        placeholder="Nama Lengkap Sesuai KK"
                        class="w-full px-4 py-3 bg-secondary-50 border-0 focus:ring-2 focus:ring-emerald-500 rounded-xl text-secondary-900 font-medium">
                </div>

                <!-- SECTION MODE: IURAN WAJIB BULANAN -->
                <div id="section_iuran_wajib" class="space-y-6">
                    <!-- Pilih Tahun -->
                    <div class="flex items-center justify-between bg-secondary-50 p-4 rounded-2xl border border-secondary-100">
                        <div>
                            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-0.5">Tahun Iuran</label>
                            <p class="text-xs text-secondary-500">Pilih tahun periode iuran yang ingin dibayar</p>
                        </div>
                        <select name="tahun" id="select_tahun" onchange="changeYear(this.value)"
                            class="px-4 py-2 bg-white border border-secondary-200 rounded-xl text-secondary-900 font-black text-sm focus:ring-2 focus:ring-emerald-500">
                            <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == $tahun_pilihan ? 'selected' : '' ?>>Tahun <?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Grid Pilih Bulan -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-[10px] font-black text-secondary-400 uppercase tracking-widest ml-1">Pilih Bulan (Bisa Lebih Dari 1 Bulan)</label>
                            <button type="button" onclick="selectAllMonths()" class="text-xs font-bold text-emerald-600 hover:text-emerald-800">
                                <i class="fas fa-check-double mr-1"></i>Pilih Semua Bulan Belum Lunas
                            </button>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            <?php foreach ($daftar_bulan as $m_code => $m_name): ?>
                                <?php
                                    $is_paid = isset($paid_months[$m_code]);
                                ?>
                                <label class="month-card relative block p-3.5 rounded-2xl border-2 transition-all cursor-pointer text-center select-none
                                    <?= $is_paid ? 'bg-green-50 border-green-300 opacity-80 cursor-not-allowed' : 'bg-secondary-50 border-secondary-200 hover:border-emerald-300' ?>"
                                    id="card_month_<?= $m_code ?>">
                                    <input type="checkbox" name="bulan[]" value="<?= $m_code ?>"
                                        <?= $is_paid ? 'disabled' : '' ?>
                                        onchange="calculateTotal()"
                                        class="month-checkbox sr-only">

                                    <div class="flex flex-col items-center justify-center gap-1">
                                        <span class="text-xs font-black text-secondary-900 month-title"><?= $m_name ?></span>
                                        <?php if ($is_paid): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-600 text-white text-[9px] font-black rounded-full uppercase">
                                                <i class="fas fa-check-circle"></i> Lunas
                                            </span>
                                        <?php else: ?>
                                            <span class="text-[10px] font-bold text-secondary-500 font-mono">Rp 55.000</span>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Donasi Tambahan (Opsional) -->
                    <div class="bg-amber-50/50 p-4 rounded-2xl border border-amber-200/60">
                        <label class="block text-[10px] font-black text-amber-800 uppercase tracking-widest mb-1.5">Donasi Sukarela Tambahan <span class="font-normal text-[9px] text-amber-600 lowercase">(opsional)</span></label>
                        <input type="text" id="donasi" name="donasi" placeholder="Rp 0" oninput="formatRupiahInput(this); calculateTotal();"
                            class="w-full px-4 py-3 bg-white border border-amber-200 rounded-xl text-secondary-900 font-black text-sm focus:ring-2 focus:ring-amber-500">
                        <p class="text-[10px] text-amber-600 mt-1"><i class="fas fa-heart mr-1 text-amber-500"></i>Opsional, jika Anda ingin menyumbang donasi kas kegiatan RT.</p>
                    </div>
                </div>

                <!-- SECTION MODE: DONASI SUKARELA SAJA -->
                <div id="section_donasi_saja" class="hidden space-y-4">
                    <div class="bg-amber-50/60 p-5 rounded-2xl border border-amber-200">
                        <label class="block text-[10px] font-black text-amber-900 uppercase tracking-widest mb-1.5 ml-1">Nominal Donasi Sukarela</label>
                        <input type="text" id="jumlah_donasi_saja" name="jumlah_donasi_saja" placeholder="Rp. 0" oninput="formatRupiahInput(this); calculateTotal();"
                            class="w-full px-4 py-3 bg-white border border-amber-300 rounded-xl text-secondary-900 font-black text-lg focus:ring-2 focus:ring-amber-500">
                        <p class="text-xs text-amber-700 mt-2 leading-relaxed">
                            <i class="fas fa-info-circle mr-1"></i>Pembayaran donasi kegiatan sukarela secara terpisah (tanpa melunasi iuran bulanan).
                        </p>
                    </div>
                </div>

                <!-- Catatan Tambahan -->
                <div>
                    <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1.5 ml-1">Catatan Tambahan <span class="font-normal text-[9px] lowercase text-secondary-400">(opsional)</span></label>
                    <textarea name="catatan" placeholder="Keterangan tambahan jika diperlukan..."
                        class="w-full px-4 py-3 bg-secondary-50 border-0 focus:ring-2 focus:ring-emerald-500 rounded-xl text-secondary-900 font-medium h-20 resize-none"></textarea>
                </div>

                <!-- Summary & Total Calculation Box -->
                <div class="bg-gradient-to-br from-emerald-900 to-emerald-950 text-white p-6 rounded-3xl shadow-lg space-y-4 relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/5 rounded-full blur-xl"></div>

                    <div class="flex justify-between items-center text-xs text-emerald-200 border-b border-emerald-800/80 pb-3">
                        <span id="summary_months_text">Iuran Wajib (0 Bulan)</span>
                        <span class="font-mono font-bold" id="summary_wajib_val">Rp 0</span>
                    </div>

                    <div class="flex justify-between items-center text-xs text-amber-200 border-b border-emerald-800/80 pb-3" id="row_summary_donasi">
                        <span>Donasi Sukarela</span>
                        <span class="font-mono font-bold" id="summary_donasi_val">Rp 0</span>
                    </div>

                    <div class="flex justify-between items-center pt-1">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-emerald-300">Total Pembayaran</p>
                            <p class="text-xs text-emerald-200/80">Iuran Bulanan (35k) + Keamanan (20k) + Donasi</p>
                        </div>
                        <p class="text-2xl font-black font-mono tracking-tight text-white" id="total_bayar_text">Rp 0</p>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full py-4 px-6 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-black rounded-2xl shadow-xl shadow-emerald-600/30 active:scale-[0.98] transition-all uppercase tracking-wider text-sm flex items-center justify-center gap-3">
                    <i class="fas fa-shield-alt text-lg"></i>
                    <span>Bayar Sekarang via Midtrans</span>
                </button>
            </form>

            <?php if (!empty($snapToken)): ?>
                <div class="mt-6">
                    <button id="pay-button"
                        class="w-full py-4 bg-green-600 text-white font-black rounded-2xl shadow-lg shadow-green-500/30 hover:bg-green-700 transition-all uppercase tracking-widest text-xs animate-pulse flex items-center justify-center gap-2">
                        <i class="fas fa-external-link-alt"></i> Buka Jendela Pembayaran Midtrans
                    </button>
                </div>
                <script src="https://app.sandbox.midtrans.com/snap/snap.js"
                    data-client-key="<?php echo \Midtrans\Config::$clientKey; ?>"></script>
                <script type="text/javascript">
                    (function () {
                        const snapToken = '<?= $snapToken ?>';
                        const currentOrderId = '<?= $order_id ?? "" ?>';

                        function updatePaymentStatus(orderId, status, callback) {
                            fetch('update_payment_status.php', {
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

    <!-- Sidebar Info -->
    <div class="space-y-6">
        <!-- Card Rincian Tarif -->
        <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-secondary-100">
            <h3 class="text-base font-black text-secondary-900 mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-emerald-600"></i> Rincian Tarif Iuran Wajib
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3.5 bg-emerald-50/50 rounded-2xl border border-emerald-100">
                    <div>
                        <p class="text-xs font-bold text-secondary-800">Iuran Kebersihan / RT</p>
                        <p class="text-[10px] text-secondary-500">Operasional lingkungan</p>
                    </div>
                    <span class="font-black text-emerald-800 text-sm">Rp 35.000</span>
                </div>
                <div class="flex justify-between items-center p-3.5 bg-emerald-50/50 rounded-2xl border border-emerald-100">
                    <div>
                        <p class="text-xs font-bold text-secondary-800">Iuran Keamanan</p>
                        <p class="text-[10px] text-secondary-500">Satpam & hansip RT</p>
                    </div>
                    <span class="font-black text-emerald-800 text-sm">Rp 20.000</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-emerald-900 text-white rounded-2xl shadow-sm">
                    <span class="text-xs font-black uppercase tracking-wider">Total / Bulan</span>
                    <span class="font-black text-lg font-mono">Rp 55.000</span>
                </div>
            </div>
        </div>

        <!-- Card Riwayat Terakhir -->
        <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-secondary-100">
            <h3 class="text-base font-black text-secondary-900 mb-4 flex items-center gap-2">
                <i class="fas fa-history text-secondary-400"></i> Riwayat Pembayaran Anda
            </h3>
            <?php if (!empty($riwayat_pembayaran)): ?>
                <div class="space-y-3">
                    <?php foreach ($riwayat_pembayaran as $r): ?>
                        <div class="p-3.5 rounded-2xl border border-secondary-100 bg-secondary-50/50 flex items-center justify-between text-xs">
                            <div>
                                <p class="font-bold text-secondary-900 line-clamp-1"><?= htmlspecialchars($r['kategori'] ?? 'Iuran') ?></p>
                                <p class="text-[10px] text-secondary-400 mt-0.5"><?= date('d M Y, H:i', strtotime($r['tanggal'])) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-black text-secondary-900 font-mono">Rp <?= number_format($r['jumlah'], 0, ',', '.') ?></p>
                                <?php if ($r['status'] === 'berhasil'): ?>
                                    <span class="inline-block text-[9px] font-black text-green-600 uppercase">Berhasil</span>
                                <?php elseif ($r['status'] === 'pending'): ?>
                                    <span class="inline-block text-[9px] font-black text-amber-600 uppercase">Pending</span>
                                <?php else: ?>
                                    <span class="inline-block text-[9px] font-black text-red-600 uppercase">Gagal</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-xs text-secondary-400 text-center py-4">Belum ada riwayat pembayaran.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changeYear(y) {
    window.location.href = '?page=pembayaran&tahun=' + y;
}

function togglePaymentMode() {
    const mode = document.querySelector('input[name="jenis_pembayaran"]:checked').value;
    const secIuran = document.getElementById('section_iuran_wajib');
    const secDonasi = document.getElementById('section_donasi_saja');
    const labelIuran = document.getElementById('label_mode_iuran');
    const labelDonasi = document.getElementById('label_mode_donasi');

    if (mode === 'iuran_wajib') {
        secIuran.classList.remove('hidden');
        secDonasi.classList.add('hidden');
        labelIuran.className = "relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition-all border-emerald-500 bg-emerald-50/40";
        labelDonasi.className = "relative flex items-center p-4 rounded-2xl border-2 border-secondary-200 bg-secondary-50 cursor-pointer transition-all";
    } else {
        secIuran.classList.add('hidden');
        secDonasi.classList.remove('hidden');
        labelDonasi.className = "relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition-all border-amber-500 bg-amber-50/40";
        labelIuran.className = "relative flex items-center p-4 rounded-2xl border-2 border-secondary-200 bg-secondary-50 cursor-pointer transition-all";
    }
    calculateTotal();
}

function selectAllMonths() {
    const checkboxes = document.querySelectorAll('.month-checkbox:not(:disabled)');
    let allChecked = true;
    checkboxes.forEach(cb => { if (!cb.checked) allChecked = false; });
    
    checkboxes.forEach(cb => {
        cb.checked = !allChecked;
    });
    calculateTotal();
}

function formatRupiahInput(input) {
    let value = input.value.replace(/[^0-9]/g, "");
    let number = parseInt(value);
    if (!isNaN(number) && number > 0) {
        input.value = "Rp " + number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    } else {
        input.value = "";
    }
}

function parseRupiah(str) {
    if (!str) return 0;
    const num = parseInt(str.replace(/[^0-9]/g, ""));
    return isNaN(num) ? 0 : num;
}

function calculateTotal() {
    const mode = document.querySelector('input[name="jenis_pembayaran"]:checked').value;
    let totalWajib = 0;
    let donasiVal = 0;
    let countMonths = 0;

    if (mode === 'iuran_wajib') {
        const checkboxes = document.querySelectorAll('.month-checkbox');
        checkboxes.forEach(cb => {
            const card = document.getElementById('card_month_' + cb.value);
            if (cb.checked) {
                countMonths++;
                if (card) {
                    card.classList.add('border-emerald-500', 'bg-emerald-50', 'shadow-md');
                    card.classList.remove('bg-secondary-50', 'border-secondary-200');
                }
            } else {
                if (card && !cb.disabled) {
                    card.classList.remove('border-emerald-500', 'bg-emerald-50', 'shadow-md');
                    card.classList.add('bg-secondary-50', 'border-secondary-200');
                }
            }
        });

        totalWajib = countMonths * 55000;
        donasiVal = parseRupiah(document.getElementById('donasi').value);
    } else {
        donasiVal = parseRupiah(document.getElementById('jumlah_donasi_saja').value);
    }

    const totalBayar = totalWajib + donasiVal;

    document.getElementById('summary_months_text').textContent = 'Iuran Wajib (' + countMonths + ' Bulan)';
    document.getElementById('summary_wajib_val').textContent = 'Rp ' + totalWajib.toLocaleString('id-ID');
    document.getElementById('summary_donasi_val').textContent = 'Rp ' + donasiVal.toLocaleString('id-ID');
    document.getElementById('total_bayar_text').textContent = 'Rp ' + totalBayar.toLocaleString('id-ID');
}

document.addEventListener('DOMContentLoaded', () => {
    calculateTotal();
});
</script>
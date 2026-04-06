<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div class="p-4 bg-red-100 text-red-700 rounded-2xl font-bold uppercase tracking-widest text-xs">Akses Ditolak: Anda harus login untuk mengirim pengaduan.</div>';
    return;
}

$user_id = (int) $_SESSION['user_id'];
$success = '';
$error = '';

$has_judul = false;
$colRes = $conn->query("SHOW COLUMNS FROM pengaduan LIKE 'judul'");
if ($colRes && $colRes->num_rows > 0) {
    $has_judul = true;
}

// === HANDLE ACTIONS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $isi = trim($_POST['isi'] ?? '');
        $judul = $has_judul ? trim($_POST['judul'] ?? '') : null;

        if ($isi === '') {
            $error = 'Isi laporan tidak boleh kosong.';
        } else {
            if ($has_judul) {
                $stmt = $conn->prepare("INSERT INTO pengaduan (user_id, judul, isi, status) VALUES (?, ?, ?, 'Diterima')");
                $stmt->bind_param("iss", $user_id, $judul, $isi);
            } else {
                $stmt = $conn->prepare("INSERT INTO pengaduan (user_id, isi, status) VALUES (?, ?, 'Diterima')");
                $stmt->bind_param("is", $user_id, $isi);
            }
            if ($stmt->execute()) {
                $success = 'Laporan Anda telah berhasil terkirim ke pengurus RT.';
            } else {
                $error = 'Sistem sibuk: ' . $stmt->error;
            }
            $stmt->close();
        }
    }

    elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $isi = trim($_POST['isi'] ?? '');
        $judul = $has_judul ? trim($_POST['judul'] ?? '') : null;

        if ($id <= 0 || $isi === '') {
            $error = 'Data tidak valid.';
        } else {
            $check = $conn->prepare("SELECT user_id, status FROM pengaduan WHERE id = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $checkRes = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$checkRes || (int)$checkRes['user_id'] !== $user_id) {
                $error = 'Izin ditolak.';
            } elseif ($checkRes['status'] !== 'Diterima') {
                $error = 'Laporan yang sedang diproses tidak dapat diubah.';
            } else {
                if ($has_judul) {
                    $stmt = $conn->prepare("UPDATE pengaduan SET judul = ?, isi = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $judul, $isi, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE pengaduan SET isi = ? WHERE id = ?");
                    $stmt->bind_param("si", $id);
                }
                if ($stmt->execute()) {
                    $success = 'Perubahan laporan berhasil disimpan.';
                }
                $stmt->close();
            }
        }
    }

    elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $check = $conn->prepare("SELECT user_id, status FROM pengaduan WHERE id = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $checkRes = $check->get_result()->fetch_assoc();
            $check->close();

            if ($checkRes && (int)$checkRes['user_id'] === $user_id && $checkRes['status'] === 'Diterima') {
                $stmt = $conn->prepare("DELETE FROM pengaduan WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                $success = 'Laporan telah dihapus.';
            } else {
                $error = 'Gagal menghapus: Laporan mungkin sudah diproses admin.';
            }
        }
    }
}

// === FORM EDIT ===
$edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit_row = null;
if ($edit_id > 0) {
    if ($has_judul) {
        $stmt = $conn->prepare("SELECT id, judul, isi FROM pengaduan WHERE id = ? AND user_id = ? AND status='Diterima'");
        $stmt->bind_param("ii", $edit_id, $user_id);
    } else {
        $stmt = $conn->prepare("SELECT id, isi FROM pengaduan WHERE id = ? AND user_id = ? AND status='Diterima'");
        $stmt->bind_param("ii", $edit_id, $user_id);
    }
    $stmt->execute();
    $edit_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// === LIST DATA ===
$stmt = $conn->prepare("SELECT id, judul, isi, status, created_at FROM pengaduan WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$list = $stmt->get_result();
$stmt->close();
?>

<div class="mb-10">
    <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Layanan Pengaduan</h2>
    <p class="text-secondary-500 mt-1">Sampaikan aspirasi, kendala, atau laporan Anda langsung ke pengurus RT.</p>
</div>

<div class="grid lg:grid-cols-3 gap-8 items-start">
    <!-- Form Section -->
    <div class="lg:col-span-1">
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-secondary-100 sticky top-24">
            <h3 class="text-xl font-black text-secondary-900 mb-8 flex items-center">
                <i class="fas fa-paper-plane mr-3 text-primary-500 text-sm"></i>
                <?= ($edit_row) ? 'Update Laporan' : 'Laporan Baru' ?>
            </h3>

            <?php if ($success): ?>
                <div class="p-4 mb-6 bg-green-50 text-green-700 rounded-2xl border border-green-100 flex items-center gap-3 animate-in fade-in transition-all">
                    <i class="fas fa-check-circle"></i>
                    <p class="text-xs font-bold uppercase tracking-tight"><?= $success ?></p>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-4 mb-6 bg-red-50 text-red-700 rounded-2xl border border-red-100 flex items-center gap-3 animate-in fade-in transition-all">
                    <i class="fas fa-exclamation-circle"></i>
                    <p class="text-xs font-bold uppercase tracking-tight"><?= $error ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="dashboard_warga.php?page=pengaduan" class="space-y-6">
                <input type="hidden" name="action" value="<?= ($edit_row) ? 'update' : 'create' ?>">
                <?php if($edit_row): ?>
                    <input type="hidden" name="id" value="<?= $edit_row['id'] ?>">
                <?php endif; ?>

                <?php if ($has_judul): ?>
                <div>
                    <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Subjek / Judul</label>
                    <input type="text" name="judul" value="<?= htmlspecialchars($edit_row['judul'] ?? '') ?>" placeholder="Misal: Lampu Jalan Padam" required
                           class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium placeholder-secondary-300 transition-all">
                </div>
                <?php endif; ?>

                <div>
                    <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-2 ml-1">Isi Laporan</label>
                    <textarea name="isi" rows="6" placeholder="Tuliskan detail aspirasi atau kendala Anda..." required
                              class="w-full px-5 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium placeholder-secondary-300 transition-all resize-none"><?= htmlspecialchars($edit_row['isi'] ?? '') ?></textarea>
                </div>

                <div class="pt-4 flex gap-2">
                    <button type="submit" class="flex-1 py-4 bg-primary-600 hover:bg-primary-700 text-white font-black rounded-2xl shadow-xl shadow-primary-500/20 transition-all uppercase tracking-widest text-sm">
                        <?= ($edit_row) ? 'Simpan' : 'Kirim Laporan' ?>
                    </button>
                    <?php if($edit_row): ?>
                        <a href="?page=pengaduan" class="py-4 px-6 bg-secondary-100 text-secondary-600 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- List Section -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-secondary-100 overflow-hidden">
            <div class="px-8 py-8 border-b border-secondary-50 bg-secondary-50/30 flex justify-between items-center">
                <h3 class="text-xl font-black text-secondary-900">Riwayat Pengaduan</h3>
                <span class="px-3 py-1 bg-white rounded-full text-[10px] font-black text-secondary-400 border border-secondary-100 uppercase tracking-tighter"><?= $list->num_rows ?> Laporan</span>
            </div>

            <div class="divide-y divide-secondary-50">
                <?php if ($list->num_rows === 0): ?>
                    <div class="p-20 text-center text-secondary-300">
                        <i class="fas fa-comment-slash text-6xl mb-6 block opacity-20"></i>
                        <p class="font-bold tracking-tight">Belum ada pengaduan yang dikirim.</p>
                    </div>
                <?php else: ?>
                    <?php while ($r = $list->fetch_assoc()): 
                        $status = $r['status'] ?? 'Diterima';
                        $color = "blue";
                        if($status == 'Diproses') $color = "yellow";
                        if($status == 'Selesai') $color = "green";
                    ?>
                    <div class="p-8 group hover:bg-primary-50/20 transition-all">
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-<?= $color ?>-100 text-<?= $color ?>-600">
                                        <?= $status ?>
                                    </span>
                                    <span class="text-[10px] font-bold text-secondary-400 flex items-center">
                                        <i class="far fa-clock mr-1.5"></i>
                                        <?php
                                        $bulanIndo = ['Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agt', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'];
                                        echo strtr(date('d M Y, H:i', strtotime($r['created_at'])), $bulanIndo);
                                        ?>
                                    </span>
                                </div>
                                <h4 class="text-lg font-black text-secondary-900 group-hover:text-primary-600 transition-colors mb-2"><?= htmlspecialchars($r['judul'] ?? 'Tanpa Subjek') ?></h4>
                                <p class="text-secondary-500 text-sm leading-relaxed"><?= nl2br(htmlspecialchars($r['isi'])) ?></p>
                            </div>

                            <?php if ($status === 'Diterima'): ?>
                            <div class="flex items-center gap-2 shrink-0 md:opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="?page=pengaduan&edit=<?= $r['id'] ?>" class="w-10 h-10 rounded-xl bg-secondary-100 text-secondary-400 flex items-center justify-center hover:bg-yellow-500 hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                <form method="POST" onsubmit="return confirm('Hapus laporan ini?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="w-10 h-10 rounded-xl bg-secondary-100 text-secondary-400 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

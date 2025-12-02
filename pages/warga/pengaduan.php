<?php
// pages/warga/pengaduan.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div class="p-4 bg-red-100 text-red-700 rounded">Anda harus login untuk mengirim pengaduan.</div>';
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
            $error = 'Isi pengaduan tidak boleh kosong.';
        } else {
            if ($has_judul) {
                $stmt = $conn->prepare("INSERT INTO pengaduan (user_id, judul, isi) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $judul, $isi);
            } else {
                $stmt = $conn->prepare("INSERT INTO pengaduan (user_id, isi) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $isi);
            }
            if ($stmt->execute()) {
                $success = 'Pengaduan berhasil dikirim.';
            } else {
                $error = 'Gagal menyimpan pengaduan: ' . $stmt->error;
            }
            $stmt->close();
        }
    }

elseif ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $isi = trim($_POST['isi'] ?? '');
    $judul = $has_judul ? trim($_POST['judul'] ?? '') : null;

    if ($id <= 0) {
        $error = 'ID pengaduan tidak valid.';
    } elseif ($isi === '') {
        $error = 'Isi pengaduan tidak boleh kosong.';
    } else {
        $check = $conn->prepare("SELECT user_id FROM pengaduan WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $checkRes = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$checkRes || (int)$checkRes['user_id'] !== $user_id) {
            $error = 'Anda tidak berhak mengubah pengaduan ini.';
        } else {
            if ($has_judul) {
                $stmt = $conn->prepare("UPDATE pengaduan SET judul = ?, isi = ? WHERE id = ?");
                $stmt->bind_param("ssi", $judul, $isi, $id);
            } else {
                $stmt = $conn->prepare("UPDATE pengaduan SET isi = ? WHERE id = ?");
                $stmt->bind_param("si", $isi, $id);
            }
            if ($stmt->execute()) {
                $success = 'Pengaduan berhasil diupdate.';
                // reset supaya balik ke form create
                $edit_id = 0;
                $edit_row = null;
            } else {
                $error = 'Gagal update: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}


    elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $check = $conn->prepare("SELECT user_id FROM pengaduan WHERE id = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $checkRes = $check->get_result()->fetch_assoc();
            $check->close();

            if ($checkRes && (int)$checkRes['user_id'] === $user_id) {
                $stmt = $conn->prepare("DELETE FROM pengaduan WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                $success = 'Pengaduan berhasil dihapus.';
            } else {
                $error = 'Anda tidak berhak menghapus pengaduan ini.';
            }
        }
    }
}

// === FORM EDIT ===
$edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit_row = null;
if ($edit_id > 0) {
    if ($has_judul) {
        $stmt = $conn->prepare("SELECT id, judul, isi FROM pengaduan WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $edit_id, $user_id);
    } else {
        $stmt = $conn->prepare("SELECT id, isi FROM pengaduan WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $edit_id, $user_id);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $edit_row = $res->fetch_assoc();
    $stmt->close();
    if (!$edit_row) {
        $error = 'Pengaduan yang ingin diedit tidak ditemukan atau bukan milik Anda.';
        $edit_id = 0;
    }
}

// === LIST DATA ===
if ($has_judul) {
    $stmt = $conn->prepare("SELECT id, judul, isi, status, created_at FROM pengaduan WHERE user_id = ? ORDER BY created_at DESC");
} else {
    $stmt = $conn->prepare("SELECT id, isi, status, created_at FROM pengaduan WHERE user_id = ? ORDER BY created_at DESC");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$list = $stmt->get_result();
$stmt->close();
?>

<!-- TAMPILAN HTML -->
<div class="space-y-6">
    <h2 class="text-2xl font-bold">Pengaduan Saya</h2>

    <?php if ($success): ?>
        <div class="p-3 bg-green-100 text-green-800 rounded"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="p-3 bg-red-100 text-red-800 rounded"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Form Create / Edit -->
    <div class="bg-white p-6 rounded-lg shadow">
        <?php if ($edit_id && $edit_row): ?>
            <h3 class="font-bold mb-4">Edit Pengaduan</h3>
            <form method="POST" class="space-y-4">
                <?php if ($has_judul): ?>
                    <input type="text" name="judul" class="w-full border rounded px-3 py-2" value="<?= htmlspecialchars($edit_row['judul']) ?>" placeholder="Judul (opsional)">
                <?php endif; ?>
                <textarea name="isi" rows="5" class="w-full border rounded px-3 py-2"><?= htmlspecialchars($edit_row['isi']) ?></textarea>
                <div class="flex space-x-2">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= $edit_row['id'] ?>">
                    <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded">Simpan Perubahan</button>
                    <a href="?page=pengaduan" class="bg-gray-200 px-4 py-2 rounded">Kembali</a>
                </div>
            </form>
        <?php else: ?>
            <h3 class="font-bold mb-4">Kirim Pengaduan</h3>
            <form method="POST" class="space-y-4">
                <?php if ($has_judul): ?>
                    <input type="text" name="judul" class="w-full border rounded px-3 py-2" placeholder="Judul (opsional)">
                <?php endif; ?>
                <textarea name="isi" rows="5" class="w-full border rounded px-3 py-2" placeholder="Tuliskan pengaduan Anda..." required></textarea>
                <div>
                    <input type="hidden" name="action" value="create">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Kirim Pengaduan</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Daftar Pengaduan -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="font-bold mb-4">Daftar Pengaduan (<?= $list->num_rows ?>)</h3>

        <?php if ($list->num_rows === 0): ?>
            <p class="text-gray-600">Belum ada pengaduan.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php while ($r = $list->fetch_assoc()): ?>
                    <div class="border rounded p-4">
                        <?php if ($has_judul && !empty($r['judul'])): ?>
                            <div class="font-semibold text-lg"><?= htmlspecialchars($r['judul']) ?></div>
                        <?php endif; ?>
                        <div class="text-gray-700 mt-1"><?= nl2br(htmlspecialchars($r['isi'])) ?></div>
                        <div class="flex items-center justify-between mt-3 text-sm text-gray-500">
                            <div>
                                <span class="mr-4"><i class="fas fa-clock mr-1"></i><?= date('d M Y H:i', strtotime($r['created_at'])) ?></span>
                                <span class="px-2 py-0.5 rounded-full <?= $r['status']=='diproses' ? 'bg-yellow-100 text-yellow-700' : ($r['status']=='selesai' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700') ?>">
                                    <?= htmlspecialchars($r['status']) ?>
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="?page=pengaduan&edit=<?= $r['id'] ?>" class="text-yellow-600">Edit</a>
                                <form method="POST" onsubmit="return confirm('Yakin ingin menghapus pengaduan ini?');" style="display:inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="text-red-600">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

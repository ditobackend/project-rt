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

// Ambil flash message jika ada
$message = $_SESSION['flash_msg'] ?? "";
unset($_SESSION['flash_msg']);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = "warga"; // default role

    $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nama, $email, $password, $role);

    if ($stmt->execute()) {
        $_SESSION['flash_msg'] = "
        <div class='flex items-center p-4 mb-4 text-green-800 rounded-[1.25rem] bg-green-50 border border-green-100 shadow-sm animate-in fade-in duration-500'>
            <i class='fas fa-check-circle mr-3'></i>
            <span class='text-sm font-bold tracking-tight uppercase'>Account successfully registered.</span>
        </div>";
        header("Location: dashboard_admin.php?page=registrasi");
        exit;
    } else {
        $message = "
        <div class='flex items-center p-4 mb-4 text-red-800 rounded-[1.25rem] bg-red-50 border border-red-100 shadow-sm animate-in fade-in duration-500'>
            <i class='fas fa-exclamation-circle mr-3'></i>
            <span class='text-sm font-bold tracking-tight uppercase tracking-widest'>Error: " . $stmt->error . "</span>
        </div>";
    }
}

// Proses Hapus User
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'warga'");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['flash_msg'] = "
        <div class='flex items-center p-4 mb-4 text-green-800 rounded-[1.25rem] bg-green-50 border border-green-100 shadow-sm animate-in fade-in duration-500'>
            <i class='fas fa-check-circle mr-3'></i>
            <span class='text-sm font-bold tracking-tight uppercase'>Akun berhasil dihapus.</span>
        </div>";
        header("Location: dashboard_admin.php?page=registrasi");
        exit;
    }
}
?>

<div class="mb-10">
    <h2 class="text-3xl font-extrabold text-secondary-900 tracking-tight">Registrasi Warga</h2>
    <p class="text-secondary-500 mt-1">Daftarkan warga baru dan atur kredensial akses mereka.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Form Registrasi -->
    <div class="lg:col-span-5">
        <div class="bg-white p-10 rounded-[2.5rem] shadow-sm border border-secondary-100 sticky top-24">
            <h3 class="text-xl font-black text-secondary-900 mb-8 border-b border-secondary-50 pb-4">Formulir
                Pendaftaran</h3>

            <?= $message ?>

            <form action="" method="POST" class="space-y-6">
                <div>
                    <label
                        class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Nama
                        Lengkap</label>
                    <input type="text" name="nama" placeholder="Contoh: Budi Santoso" required
                        class="w-full px-6 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium transition-all placeholder-secondary-300">
                </div>

                <div>
                    <label
                        class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Alamat
                        Email</label>
                    <input type="email" name="email" placeholder="budi@example.com" required
                        class="w-full px-6 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium transition-all placeholder-secondary-300">
                </div>

                <div>
                    <label
                        class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Kata
                        Sandi Akses</label>
                    <input type="password" name="password" placeholder="••••••••" required
                        class="w-full px-6 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-500 rounded-2xl text-secondary-900 font-medium transition-all placeholder-secondary-300">
                </div>

                <div>
                    <label
                        class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Peran
                        Akses</label>
                    <div
                        class="w-full px-6 py-4 bg-secondary-100/50 border-0 rounded-2xl text-secondary-400 font-black uppercase tracking-widest text-xs flex items-center">
                        <i class="fas fa-user mr-3"></i> Warga
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full py-5 bg-secondary-900 text-white font-black rounded-2xl hover:bg-secondary-800 transition-all active:scale-95 shadow-xl shadow-secondary-900/10 uppercase tracking-widest text-sm">
                        Daftarkan Warga
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar Akun Terdaftar -->
    <div class="lg:col-span-7">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-secondary-100 overflow-hidden">
            <div class="px-8 py-8 border-b border-secondary-50">
                <h3 class="text-xl font-black text-secondary-900">Warga Terdaftar</h3>
            </div>

            <div class="overflow-x-hidden max-h-[500px] overflow-y-auto custom-scrollbar">
                <table class="w-full text-left border-separate border-spacing-0">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-secondary-50">
                            <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">
                                Identitas</th>
                            <th class="px-6 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">
                                Email</th>
                            <th
                                class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100">
                                Peran Akses</th>
                            <th
                                class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest border-b border-secondary-100 text-right">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary-50">
                        <?php
                        $result = mysqli_query($conn, "SELECT id, nama, email, role FROM users WHERE role = 'warga' ORDER BY id DESC");
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $avatar = substr($row['nama'], 0, 1);
                                $roleDisplay = ucfirst($row['role']);
                                echo "<tr class='group hover:bg-primary-50/30 transition-all'>
                                        <td class='px-8 py-5'>
                                            <div class='flex items-center gap-3'>
                                                <div class='w-10 h-10 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-black group-hover:scale-110 transition-transform'>$avatar</div>
                                                <span class='font-bold text-secondary-900'>{$row['nama']}</span>
                                            </div>
                                        </td>
                                        <td class='px-6 py-5 text-sm text-secondary-500 font-medium'>{$row['email']}</td>
                                        <td class='px-8 py-5'>
                                            <span class='text-[10px] font-black uppercase tracking-widest text-primary-600 bg-primary-50 px-3 py-1 rounded-full'>$roleDisplay</span>
                                        </td>
                                        <td class='px-8 py-5 text-right'>
                                            <button onclick='confirmDelete({$row['id']}, \"{$row['nama']}\")' class='p-2 text-secondary-300 hover:text-red-500 transition-colors'>
                                                <i class='fas fa-trash-alt'></i>
                                            </button>
                                        </td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='px-8 py-20 text-center text-secondary-300 font-bold'>Belum ada data warga terdaftar.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Hapus Akun?',
        text: "Akun warga '" + nama + "' akan dihapus secara permanen dari sistem!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Akun',
        cancelButtonText: 'Batal',
        background: '#ffffff',
        customClass: {
            popup: 'rounded-[2.5rem] shadow-xl border-0',
            confirmButton: 'rounded-xl px-6 py-2.5 font-bold',
            cancelButton: 'rounded-xl px-6 py-2.5 font-bold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "?page=registrasi&hapus=" + id;
        }
    })
}
</script>
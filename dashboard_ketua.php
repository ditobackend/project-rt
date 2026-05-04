<?php
ob_start();
// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Koneksi database
include __DIR__ . '/config/database.php';

// Proteksi halaman ketua rt
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ketua_rt') {
    header("Location: loginadmin.php");
    exit;
}

// Tentukan halaman yang diminta (?page=...)
$page = $_GET['page'] ?? 'dashboard';
?>

<?php include 'includes/header.php'; ?>

<body class="bg-secondary-50 min-h-screen font-sans text-secondary-900">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Desktop -->
        <aside
            class="hidden md:flex flex-col w-72 bg-secondary-950 text-white transition-all duration-300 ease-in-out z-30 border-r border-white/5">
            <div class="flex flex-col h-full">
                <div class="p-6 pb-2">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="bg-primary-500 p-2 rounded-xl shadow-lg shadow-primary-500/30">
                            <i class="fas fa-user-tie text-xl"></i>
                        </div>
                        <span class="text-xl font-bold tracking-tight">Portal Ketua RT</span>
                    </div>
                </div>

                <div class="flex-1 overflow-hidden px-6">
                    <nav class="space-y-1 pb-2">
                        <a href="?page=dashboard"
                            class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl <?= $page == 'dashboard' ? 'sidebar-active' : 'text-secondary-400 hover:text-white hover:bg-white/5' ?>">
                            <i class="fas fa-tachometer-alt w-6"></i>
                            <span class="font-medium">Dashboard Utama</span>
                        </a>
                        <a href="?page=kegiatan_approval"
                            class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl <?= $page == 'kegiatan_approval' ? 'sidebar-active' : 'text-secondary-400 hover:text-white hover:bg-white/5' ?>">
                            <i class="fas fa-tasks w-6"></i>
                            <span class="font-medium">Pengajuan Kegiatan</span>
                        </a>
                        <a href="?page=laporan"
                            class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl <?= $page == 'laporan' ? 'sidebar-active' : 'text-secondary-400 hover:text-white hover:bg-white/5' ?>">
                            <i class="fas fa-file-invoice-dollar w-6"></i>
                            <span class="font-medium">Laporan Keuangan</span>
                        </a>
                    </nav>
                </div>

                <div class="p-6">
                    <button onclick="confirmLogout()"
                        class="flex items-center justify-center space-x-3 px-4 py-3 w-full rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all">
                        <i class="fas fa-sign-out-alt w-6"></i>
                        <span class="font-medium">Keluar</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top Header -->
            <header class="glass sticky top-0 z-20 flex items-center justify-between px-8 py-4">
                <div class="flex items-center md:hidden">
                    <button id="menuBtn" class="text-secondary-600 text-2xl">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="ml-4 font-bold text-lg">Ketua RT</span>
                </div>

                <div class="hidden md:block">
                    <h2 class="text-secondary-500 text-sm font-medium">Selamat datang, Pak RT</h2>
                    <p class="text-secondary-900 font-bold block"><?= $_SESSION['user_nama'] ?? 'Ketua RT 06' ?></p>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <?php
                        $hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
                        $bulan = ['January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'];
                        $date_str = strtr(date('l, d F Y'), array_merge($hari, $bulan));
                        ?>
                        <p class="text-xs text-secondary-500"><?= $date_str ?></p>
                        <p class="text-xs font-bold text-primary-600">Otoritas Ketua RT 06/08</p>
                    </div>
                    <div
                        class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold shadow-sm ring-2 ring-white">
                        <?= strtoupper(substr($_SESSION['user_nama'] ?? 'K', 0, 1)) . strtoupper(substr(explode(' ', $_SESSION['user_nama'] ?? 'RT')[1] ?? 'T', 0, 1)) ?>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-8 scroll-smooth">
                <div class="max-w-6xl mx-auto">
                    <?php
                    // Map pages for Ketua RT
                    $allowed_pages = ['dashboard', 'kegiatan_approval', 'laporan'];
                    if (in_array($page, $allowed_pages)) {
                        if ($page === 'dashboard') {
                            $file = __DIR__ . "/pages/ketua/dashboard.php";
                        } else {
                            $file = __DIR__ . "/pages/ketua/{$page}.php";
                        }
                        
                        if (file_exists($file)) {
                            include $file;
                        } else {
                            // Fallback to shared pages if applicable
                            $shared_file = __DIR__ . "/pages/admin/{$page}.php";
                            if (file_exists($shared_file)) {
                                include $shared_file;
                            } else {
                                echo "Halaman tidak ditemukan.";
                            }
                        }
                    } else {
                        echo "Akses ditolak.";
                    }
                    ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="overlay" class="fixed inset-0 bg-secondary-900/50 backdrop-blur-sm hidden z-40 transition-opacity"></div>

    <!-- Mobile Sidebar -->
    <aside id="sidebar"
        class="fixed top-0 left-0 w-72 h-full bg-secondary-950 text-white transform -translate-x-full transition-transform duration-300 ease-in-out z-50">
        <div class="p-8 flex flex-col h-full">
            <div class="flex justify-between items-center mb-10">
                <div class="flex items-center space-x-3">
                    <div class="bg-primary-500 p-2 rounded-xl shadow-lg">
                        <i class="fas fa-user-tie text-xl"></i>
                    </div>
                    <span class="text-xl font-bold tracking-tight">Ketua RT</span>
                </div>
                <button id="closeBtn" class="text-secondary-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>

            <nav class="space-y-1">
                <a href="?page=dashboard"
                    class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl <?= $page == 'dashboard' ? 'sidebar-active' : 'text-secondary-400 hover:text-white' ?>">
                    <i class="fas fa-tachometer-alt w-6"></i><span>Dashboard Utama</span>
                </a>
                <a href="?page=kegiatan_approval"
                    class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl <?= $page == 'kegiatan_approval' ? 'sidebar-active' : 'text-secondary-400 hover:text-white' ?>">
                    <i class="fas fa-tasks w-6"></i><span>Pengajuan Kegiatan</span>
                </a>
                <a href="?page=laporan"
                    class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl <?= $page == 'laporan' ? 'sidebar-active' : 'text-secondary-400 hover:text-white' ?>">
                    <i class="fas fa-file-invoice-dollar w-6"></i><span>Laporan Keuangan</span>
                </a>
            </nav>

            <div class="mt-auto pt-8">
                <button onclick="confirmLogout()"
                    class="flex items-center justify-center space-x-3 px-4 py-3 w-full rounded-xl bg-red-500/10 text-red-500">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span class="font-medium">Keluar</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function confirmLogout() {
            Swal.fire({
                title: 'Keluar Sistem?',
                text: "Apakah Anda yakin ingin mengakhiri sesi ketua RT saat ini?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal',
                background: '#ffffff',
                customClass: {
                    popup: 'rounded-3xl shadow-xl border-0',
                    confirmButton: 'rounded-xl px-6 py-2.5 font-bold',
                    cancelButton: 'rounded-xl px-6 py-2.5 font-bold'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "logout.php";
                }
            })
        }

        const menuBtn = document.getElementById("menuBtn");
        const closeBtn = document.getElementById("closeBtn");
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("overlay");

        if (menuBtn) {
            menuBtn.addEventListener("click", () => {
                sidebar.classList.remove("-translate-x-full");
                overlay.classList.remove("hidden");
                setTimeout(() => overlay.classList.add("opacity-100"), 10);
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener("click", closeModal);
        }

        if (overlay) {
            overlay.addEventListener("click", closeModal);
        }

        function closeModal() {
            sidebar.classList.add("-translate-x-full");
            overlay.classList.remove("opacity-100");
            setTimeout(() => overlay.classList.add("hidden"), 300);
        }
    </script>

    <?php include 'includes/footer.php'; ?>
<?php ob_end_flush(); ?>

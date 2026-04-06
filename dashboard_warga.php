<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'warga') {
    header("Location: loginwarga.php");
    exit;
}
$page = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php include 'includes/header.php'; ?>
    <title>Portal Warga - RT 06/08 Serua Indah</title>
</head>
<body class="bg-secondary-50 font-sans text-secondary-900 antialiased scroll-smooth">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Desktop -->
        <aside class="hidden md:flex flex-col w-72 bg-secondary-900 text-white transition-all duration-300 ease-in-out z-30">
            <div class="p-8">
                <div class="flex items-center space-x-3 mb-10">
                    <div class="bg-primary-500 p-2 rounded-xl shadow-lg shadow-primary-500/30">
                        <i class="fas fa-home text-xl"></i>
                    </div>
                    <span class="text-xl font-bold tracking-tight">Portal Warga</span>
                </div>

                <nav class="space-y-2">
                    <a href="?page=dashboard" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $page=='dashboard'?'sidebar-active':'text-secondary-400 hover:text-white hover:bg-secondary-800' ?>">
                        <i class="fas fa-tachometer-alt w-6"></i>
                        <span class="font-medium">Dashboard Utama</span>
                    </a>
                    <a href="?page=kegiatan" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $page=='kegiatan'?'sidebar-active':'text-secondary-400 hover:text-white hover:bg-secondary-800' ?>">
                        <i class="fas fa-calendar-alt w-6"></i>
                        <span class="font-medium">Info Kegiatan</span>
                    </a>
                    <a href="?page=keuangan" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $page=='keuangan'?'sidebar-active':'text-secondary-400 hover:text-white hover:bg-secondary-800' ?>">
                        <i class="fas fa-chart-line w-6"></i>
                        <span class="font-medium">Transparansi Dana</span>
                    </a>
                    <a href="?page=pengaduan" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $page=='pengaduan'?'sidebar-active':'text-secondary-400 hover:text-white hover:bg-secondary-800' ?>">
                        <i class="fas fa-comment-dots w-6"></i>
                        <span class="font-medium">Layanan Pengaduan</span>
                    </a>
                    <a href="?page=pembayaran" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $page=='pembayaran'?'sidebar-active':'text-secondary-400 hover:text-white hover:bg-secondary-800' ?>">
                        <i class="fas fa-credit-card w-6"></i>
                        <span class="font-medium">Iuran & Pembayaran</span>
                    </a>
                </nav>
            </div>

            <div class="mt-auto p-8">
                <button onclick="confirmExit()" class="flex items-center space-x-3 px-4 py-3 w-full rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span class="font-medium">Keluar Sistem</span>
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top Header -->
            <header class="glass sticky top-0 z-20 flex items-center justify-between px-8 py-4 border-b border-white/20">
                <div class="flex items-center md:hidden">
                    <button id="menuBtn" class="text-secondary-600 text-2xl">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="ml-4 font-bold text-lg">Portal Warga</span>
                </div>
                
                <div class="hidden md:block">
                    <h2 class="text-secondary-500 text-sm font-medium">Selamat datang,</h2>
                    <p class="text-secondary-900 font-bold block"><?= $_SESSION['user_nama'] ?? 'Warga RT 06' ?></p>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <?php
                        $hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
                        $bulan = ['January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'];
                        $date_str = strtr(date('l, d F Y'), array_merge($hari, $bulan));
                        ?>
                        <p class="text-xs text-secondary-500"><?= $date_str ?></p>
                        <p class="text-xs font-bold text-primary-600">RT 06/08 Serua Indah</p>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-primary-100 flex items-center justify-center text-primary-700 font-bold shadow-sm ring-2 ring-white">
                        <?= strtoupper(substr($_SESSION['user_nama'] ?? 'W', 0, 1)) ?>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-8 scroll-smooth bg-secondary-50/50">
                <div class="max-w-6xl mx-auto">
                    <?php
                    $file = "pages/warga/{$page}.php";
                    if (file_exists($file)) {
                        include $file;
                    } else {
                        echo "
                        <div class='flex flex-col items-center justify-center min-h-[60vh]'>
                            <i class='fas fa-exclamation-triangle text-6xl text-yellow-500 mb-4'></i>
                            <h2 class='text-2xl font-bold text-secondary-900'>Halaman Tidak Ditemukan</h2>
                            <p class='text-secondary-500'>Maaf, halaman yang Anda cari tidak tersedia dalam sistem.</p>
                            <a href='?page=dashboard' class='mt-6 px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-all'>Kembali ke Dashboard</a>
                        </div>";
                    }
                    ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="overlay" class="fixed inset-0 bg-secondary-900/50 backdrop-blur-sm hidden z-40 transition-opacity"></div>
    
    <!-- Mobile Sidebar -->
    <aside id="sidebar" class="fixed top-0 left-0 w-72 h-full bg-secondary-900 text-white transform -translate-x-full transition-transform duration-300 ease-in-out z-50">
        <div class="p-8 flex flex-col h-full">
            <div class="flex justify-between items-center mb-10">
                <div class="flex items-center space-x-3">
                    <div class="bg-primary-500 p-2 rounded-xl shadow-lg">
                        <i class="fas fa-home text-xl"></i>
                    </div>
                    <span class="text-xl font-bold tracking-tight">Portal Warga</span>
                </div>
                <button id="closeBtn" class="text-secondary-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            
            <nav class="space-y-2">
                <a href="?page=dashboard" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $page=='dashboard'?'sidebar-active':'text-secondary-400 hover:text-white' ?>">
                    <i class="fas fa-tachometer-alt w-6"></i><span>Dashboard Utama</span>
                </a>
                <a href="?page=kegiatan" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $page=='kegiatan'?'sidebar-active':'text-secondary-400 hover:text-white' ?>">
                    <i class="fas fa-calendar-alt w-6"></i><span>Info Kegiatan</span>
                </a>
                <a href="?page=keuangan" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $page=='keuangan'?'sidebar-active':'text-secondary-400 hover:text-white' ?>">
                    <i class="fas fa-chart-line w-6"></i><span>Transparansi Dana</span>
                </a>
                <a href="?page=pengaduan" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $page=='pengaduan'?'sidebar-active':'text-secondary-400 hover:text-white' ?>">
                    <i class="fas fa-comment-dots w-6"></i><span>Layanan Pengaduan</span>
                </a>
                <a href="?page=pembayaran" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= $page=='pembayaran'?'sidebar-active':'text-secondary-400 hover:text-white' ?>">
                    <i class="fas fa-credit-card w-6"></i><span>Iuran & Pembayaran</span>
                </a>
            </nav>

            <div class="mt-auto pt-8">
                <button onclick="confirmExit()" class="flex items-center space-x-3 px-4 py-3 w-full rounded-xl bg-red-500/10 text-red-500">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span class="font-medium">Keluar Sistem</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    const menuBtn = document.getElementById("menuBtn");
    const closeBtn = document.getElementById("closeBtn");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");

    function openSidebar() {
        sidebar.classList.remove("-translate-x-full");
        overlay.classList.remove("hidden");
        setTimeout(() => overlay.classList.add("opacity-100"), 10);
    }

    function closeSidebar() {
        sidebar.classList.add("-translate-x-full");
        overlay.classList.remove("opacity-100");
        setTimeout(() => overlay.classList.add("hidden"), 300);
    }

    menuBtn.addEventListener("click", openSidebar);
    closeBtn.addEventListener("click", closeSidebar);
    overlay.addEventListener("click", closeSidebar);

    function confirmExit() {
        Swal.fire({
            title: 'Keluar Sistem?',
            text: "Apakah Anda yakin ingin mengakhiri sesi portal warga?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
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
    </script>
</body>
</html>

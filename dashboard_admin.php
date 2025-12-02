<?php

// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Koneksi database
include __DIR__ . '/config/database.php';

// Proteksi halaman admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: loginadmin.php");
    exit;
}

// Tentukan halaman yang diminta (?page=...)
$page = $_GET['page'] ?? 'dashboard';
?>

<?php include 'includes/header.php'; ?>

<body class="bg-gradient-to-br from-blue-50 to-green-50 min-h-screen">

<!-- Header -->
<header class="bg-green-600 text-white shadow">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold flex items-center space-x-2">
                <i class="fas fa-user-shield"></i>
                <span>Pengurus RT</span>
            </h1>
            <p class="text-sm text-green-100">Pengelola Warga RT 06/08 Kelurahan Serua Indah</p>
        </div>
        <div class="flex items-center space-x-4">
            <span class="hidden md:inline">Selamat datang, <b>Admin</b></span>
            <!-- Tombol Logout (desktop) -->
            <button onclick="confirmLogout()" 
                    class="hidden md:inline-block bg-red-700 px-4 py-2 rounded-lg hover:bg-red-800 flex items-center space-x-2">
                <i class="fas fa-sign-out-alt"></i><span>Keluar</span>
            </button>
            <!-- Tombol hamburger (hanya mobile) -->
            <button id="menuBtn" class="md:hidden text-white text-2xl">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<!-- Navbar Desktop -->
<nav class="bg-white shadow-md hidden md:block">
    <div class="container mx-auto px-6 flex space-x-8">
        <a href="?page=dashboard" class="py-4 flex items-center space-x-2 <?= $page=='dashboard'?'text-green-600 border-b-2 border-green-600':'text-gray-600 hover:text-green-600' ?>">
            <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
        </a>
        <a href="?page=kegiatan" class="py-4 flex items-center space-x-2 <?= $page=='kegiatan'?'text-green-600 border-b-2 border-green-600':'text-gray-600 hover:text-green-600' ?>">
            <i class="fas fa-calendar-alt"></i><span>Data Kegiatan</span>
        </a>
        <a href="?page=keuangan" class="py-4 flex items-center space-x-2 <?= $page=='keuangan'?'text-green-600 border-b-2 border-green-600':'text-gray-600 hover:text-green-600' ?>">
            <i class="fas fa-chart-line"></i><span>Data Keuangan</span>
        </a>
        <a href="?page=pengaduan" class="py-4 flex items-center space-x-2 <?= $page=='pengaduan'?'text-green-600 border-b-2 border-green-600':'text-gray-600 hover:text-green-600' ?>">
            <i class="fas fa-comment-dots"></i><span>Pengaduan Warga</span>
        </a>
        <a href="?page=registrasi" class="py-4 flex items-center space-x-2 <?= $page=='registrasi'?'text-green-600 border-b-2 border-green-600':'text-gray-600 hover:text-green-600' ?>">
            <i class="fas fa-user-plus"></i><span>Registrasi Akun Warga</span>
        </a>
        <a href="?page=laporan" class="py-4 flex items-center space-x-2 <?= $page=='laporan'?'text-green-600 border-b-2 border-green-600':'text-gray-600 hover:text-green-600' ?>">
            <i class="fas fa-file-alt"></i><span>Laporan</span>
        </a>
    </div>
</nav>

<!-- Sidebar Mobile dengan animasi -->
<div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 hidden opacity-0 transition-opacity duration-300 ease-in-out z-40"></div>

<div id="sidebar" 
     class="fixed top-0 left-0 w-64 h-full bg-white shadow-lg transform -translate-x-full transition-transform duration-300 ease-in-out z-50">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="font-bold text-lg">Menu Admin</h2>
            <button id="closeBtn" class="text-gray-600 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <nav class="flex flex-col space-y-4">
            <a href="?page=dashboard" class="<?= $page=='dashboard'?'text-green-600 font-bold':'text-gray-700' ?> flex items-center space-x-2">
                <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
            </a>
            <a href="?page=kegiatan" class="<?= $page=='kegiatan'?'text-green-600 font-bold':'text-gray-700' ?> flex items-center space-x-2">
                <i class="fas fa-calendar-alt"></i><span>Data Kegiatan</span>
            </a>
            <a href="?page=keuangan" class="<?= $page=='keuangan'?'text-green-600 font-bold':'text-gray-700' ?> flex items-center space-x-2">
                <i class="fas fa-chart-line"></i><span>Data Keuangan</span>
            </a>
            <a href="?page=pengaduan" class="<?= $page=='pengaduan'?'text-green-600 font-bold':'text-gray-700' ?> flex items-center space-x-2">
                <i class="fas fa-comment-dots"></i><span>Pengaduan Warga</span>
            </a>
            <a href="?page=registrasi" class="<?= $page=='registrasi'?'text-green-600 font-bold':'text-gray-700' ?> flex items-center space-x-2">
                <i class="fas fa-user-plus"></i><span>Registrasi Akun Warga</span>
            </a>
            <a href="?page=laporan" class="<?= $page=='laporan'?'text-green-600 font-bold':'text-gray-700' ?> flex items-center space-x-2">
                <i class="fas fa-file-alt"></i><span>Laporan</span>
            </a>
            <!-- Tombol Logout (mobile) -->
            <button onclick="confirmLogout()" 
                    class="mt-4 bg-red-600 text-white px-4 py-2 rounded-lg text-center">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </button>
        </nav>
    </div>
</div>

<!-- Content -->
<main class="container mx-auto px-6 py-8">
    <?php
    $file = __DIR__ . "/pages/admin/{$page}.php";
    if (file_exists($file)) {
        include $file;
    } else {
        echo "<h2 class='text-xl font-bold text-red-600'>Halaman tidak ditemukan!</h2>";
    }
    ?>
</main>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function confirmLogout() {
    Swal.fire({
        title: 'Yakin ingin keluar?',
        text: "Kamu akan logout dari sistem",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "logout.php"; 
        }
    })
}

// Sidebar animasi
const menuBtn = document.getElementById("menuBtn");
const closeBtn = document.getElementById("closeBtn");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");

menuBtn.addEventListener("click", () => {
    sidebar.classList.remove("-translate-x-full");
    overlay.classList.remove("hidden");
});

closeBtn.addEventListener("click", () => {
    sidebar.classList.add("-translate-x-full");
    overlay.classList.add("hidden");
});

overlay.addEventListener("click", () => {
    sidebar.classList.add("-translate-x-full");
    overlay.classList.add("hidden");
});

// Smooth tutup sidebar setelah klik menu
document.querySelectorAll("#sidebar nav a").forEach(link => {
    link.addEventListener("click", () => {
        sidebar.classList.add("-translate-x-full");
        overlay.classList.add("hidden");
    });
});
</script>

<?php include 'includes/footer.php'; ?>

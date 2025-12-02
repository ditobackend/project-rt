<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'warga') {
    header("Location: loginwarga.php");
    exit;
}
?>

<?php include 'includes/header.php'; ?>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<!-- Header -->
<header class="bg-blue-600 text-white shadow">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold flex items-center space-x-2">
                <i class="fas fa-home"></i>
                <span>Portal Warga</span>
            </h1>
            <p class="text-sm text-blue-100">Kelurahan Serua Indah RT 06/08</p>
        </div>
        <div class="flex items-center space-x-4">
            <!-- Tombol hamburger (hanya tampil di mobile) -->
            <button id="menuBtn" class="md:hidden text-white text-2xl">
                <i class="fas fa-bars"></i>
            </button>
            <!-- Tombol keluar (desktop) -->
            <button onclick="confirmExit()" 
                    class="hidden md:inline-block bg-red-700 px-4 py-2 rounded-lg hover:bg-red-800 flex items-center space-x-2">
                <i class="fas fa-sign-out-alt"></i><span>Keluar</span>
            </button>
        </div>
    </div>
</header>

<!-- Navbar (desktop) -->
<?php $page = $_GET['page'] ?? 'dashboard'; ?>
<nav class="bg-white shadow-md hidden md:block">
    <div class="container mx-auto px-6 flex space-x-8">
        <a href="?page=dashboard" class="py-4 flex items-center space-x-2 <?= $page=='dashboard'?'text-blue-600 border-b-2 border-blue-600':'text-gray-600 hover:text-blue-600' ?>">
            <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
        </a>
        <a href="?page=kegiatan" class="py-4 flex items-center space-x-2 <?= $page=='kegiatan'?'text-blue-600 border-b-2 border-blue-600':'text-gray-600 hover:text-blue-600' ?>">
            <i class="fas fa-calendar-alt"></i><span>Kegiatan</span>
        </a>
        <a href="?page=keuangan" class="py-4 flex items-center space-x-2 <?= $page=='keuangan'?'text-blue-600 border-b-2 border-blue-600':'text-gray-600 hover:text-blue-600' ?>">
            <i class="fas fa-chart-line"></i><span>Keuangan</span>
        </a>
        <a href="?page=pengaduan" class="py-4 flex items-center space-x-2 <?= $page=='pengaduan'?'text-blue-600 border-b-2 border-blue-600':'text-gray-600 hover:text-blue-600' ?>">
            <i class="fas fa-comment-dots"></i><span>Pengaduan</span>
        </a>
        <a href="?page=pembayaran" class="py-4 flex items-center space-x-2 <?= $page=='pembayaran'?'text-blue-600 border-b-2 border-blue-600':'text-gray-600 hover:text-blue-600' ?>">
            <i class="fas fa-credit-card"></i><span>Pembayaran</span>
        </a>
    </div>
</nav>

<!-- Sidebar (mobile) dengan animasi -->
<div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 hidden opacity-0 transition-opacity duration-300 ease-in-out z-40"></div>

<div id="sidebar" 
     class="fixed top-0 left-0 w-64 h-full bg-white shadow-lg transform -translate-x-full transition-transform duration-300 ease-in-out z-50">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="font-bold text-lg">Menu</h2>
            <button id="closeBtn" class="text-gray-600 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <nav class="flex flex-col space-y-4">
            <a href="?page=dashboard" class="<?= $page=='dashboard'?'text-blue-600 font-bold':'text-gray-700' ?> flex items-center space-x-2">
                <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
            </a>
            <a href="?page=kegiatan" class="<?= $page=='kegiatan'?'text-blue-600 font-bold':'text-gray-700' ?> flex items-center space-x-2">
                <i class="fas fa-calendar-alt"></i><span>Kegiatan</span>
            </a>
            <a href="?page=keuangan" class="<?= $page=='keuangan'?'text-blue-600 font-bold':'text-gray-700' ?> flex items-center space-x-2">
                <i class="fas fa-chart-line"></i><span>Keuangan</span>
            </a>
            <a href="?page=pengaduan" class="<?= $page=='pengaduan'?'text-blue-600 font-bold':'text-gray-700' ?> flex items-center space-x-2">
                <i class="fas fa-comment-dots"></i><span>Pengaduan</span>
            </a>
            <a href="?page=pembayaran" class="<?= $page=='pembayaran'?'text-blue-600 font-bold':'text-gray-700' ?> flex items-center space-x-2">
                <i class="fas fa-credit-card"></i><span>Pembayaran</span>
            </a>
            <!-- Tombol keluar (mobile) -->
            <button onclick="confirmExit()" 
                    class="mt-4 bg-red-600 text-white px-4 py-2 rounded-lg text-center">
                <i class="fas fa-sign-out-alt mr-2"></i>Keluar
            </button>
        </nav>
    </div>
</div>

<!-- Content -->
<main class="container mx-auto px-6 py-8">
    <?php
        $file = "pages/warga/{$page}.php";
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
function confirmExit() {
    Swal.fire({
        title: 'Yakin ingin keluar?',
        text: "Kamu akan kembali ke halaman utama",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#e02222ff',
        confirmButtonText: 'Ya, Keluar',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "index.php"; 
        }
    })
}

// Animasi sidebar
const menuBtn = document.getElementById("menuBtn");
const closeBtn = document.getElementById("closeBtn");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");

function openSidebar() {
    sidebar.classList.remove("-translate-x-full");
    overlay.classList.remove("hidden", "opacity-0");
    overlay.classList.add("opacity-100");
}

function closeSidebar(callback) {
    sidebar.classList.add("-translate-x-full");
    overlay.classList.remove("opacity-100");
    overlay.classList.add("opacity-0");
    setTimeout(() => {
        overlay.classList.add("hidden");
        if (typeof callback === "function") callback();
    }, 300);
}

menuBtn.addEventListener("click", openSidebar);
closeBtn.addEventListener("click", () => closeSidebar());
overlay.addEventListener("click", () => closeSidebar());

// Intercept klik link di sidebar agar smooth close dulu
document.querySelectorAll("#sidebar a").forEach(link => {
    link.addEventListener("click", (e) => {
        e.preventDefault();
        const url = link.getAttribute("href");
        closeSidebar(() => {
            window.location.href = url;
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>

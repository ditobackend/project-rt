<?php include 'includes/header.php'; ?>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <main class="container mx-auto px-6 py-12">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-2xl font-semibold text-gray-800 mb-4">Manajemen Warga RT 006/008</h1>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Kelurahan Serua Indah</h2>
            <p class="text-gray-600 mb-10">Silakan pilih role sesuai dengan hak akses Anda</p>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- Warga -->
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Akses Warga</h3>
                    <p class="text-gray-600 mb-6">Informasi kegiatan, keuangan, dan Pengaduan</p>
                    <a href="loginwarga.php" class="block bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700">
                        Masuk untuk Warga
                    </a>
                </div>

                <!-- Admin -->
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Akses Admin</h3>
                    <p class="text-gray-600 mb-6">Kelola data kegiatan, keuangan, dan Pengaduan</p>
                    <a href="loginadmin.php" class="block bg-green-600 text-white py-3 px-6 rounded-lg hover:bg-green-700">
                        Masuk untuk Admin
                    </a>
                </div>
            </div>
        </div>
    </main>
<?php include 'includes/footer.php'; ?>

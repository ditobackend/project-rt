<?php include 'includes/header.php'; ?>
<body class="bg-secondary-50 font-sans text-secondary-900 antialiased min-h-screen flex items-center justify-center p-6">
    <main class="w-full max-w-5xl">
        <div class="text-center mb-16 animate-in fade-in slide-in-from-bottom-4 duration-1000">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-primary-600 rounded-[2rem] shadow-2xl shadow-primary-500/40 mb-8 transform hover:rotate-12 transition-transform cursor-pointer">
                <i class="fas fa-home text-white text-3xl"></i>
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-secondary-900 tracking-tight mb-4">
                Sistem Manajemen <span class="text-primary-600">RT 06/08</span>
            </h1>
            <p class="text-xl text-secondary-500 font-medium max-w-2xl mx-auto leading-relaxed">
                Portal informasi mandiri untuk warga Kelurahan Serua Indah. <br class="hidden md:block">
                Silakan pilih akses masuk sesuai dengan peran Anda di lingkungan.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            <!-- Akses Warga -->
            <div class="group bg-white p-10 rounded-[3rem] shadow-sm border border-secondary-100 hover:shadow-2xl hover:shadow-primary-500/10 transition-all duration-500 relative overflow-hidden">
                <div class="absolute -right-8 -top-8 w-32 h-32 bg-primary-500/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                
                <div class="relative z-10 text-center">
                    <div class="w-16 h-16 bg-primary-50 text-primary-600 rounded-3xl flex items-center justify-center mx-auto mb-8 group-hover:bg-primary-600 group-hover:text-white transition-all duration-500">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-secondary-900 mb-4">Portal Warga</h3>
                    <p class="text-secondary-500 mb-10 leading-relaxed font-medium">
                        Cek info kegiatan RT terbaru, pantau transparansi kas, kirim pengaduan, dan bayar iuran tanpa ribet.
                    </p>
                    <a href="loginwarga.php" class="inline-flex items-center justify-center w-full py-5 bg-secondary-900 text-white font-black rounded-2xl hover:bg-secondary-800 transition-all shadow-xl shadow-secondary-900/10 uppercase tracking-widest text-sm group-hover:bg-primary-600 group-hover:shadow-primary-500/20">
                        Masuk Sebagai Warga
                        <i class="fas fa-arrow-right ml-3 text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>

            <!-- Akses Admin -->
            <div class="group bg-secondary-900 p-10 rounded-[3rem] shadow-2xl shadow-secondary-900/10 hover:shadow-primary-500/20 transition-all duration-500 relative overflow-hidden text-white">
                <div class="absolute -left-8 -bottom-8 w-32 h-32 bg-white/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                
                <div class="relative z-10 text-center">
                    <div class="w-16 h-16 bg-white/10 text-primary-400 rounded-3xl flex items-center justify-center mx-auto mb-8 group-hover:bg-primary-500 group-hover:text-white transition-all duration-500">
                        <i class="fas fa-user-shield text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-black mb-4">Dashboard Admin</h3>
                    <p class="text-secondary-400 mb-10 leading-relaxed font-medium">
                        Kelola database warga, validasi iuran, atur agenda kegiatan, dan respon pengaduan secara real-time.
                    </p>
                    <a href="loginadmin.php" class="inline-flex items-center justify-center w-full py-5 bg-primary-600 text-white font-black rounded-2xl hover:bg-primary-500 transition-all shadow-xl shadow-primary-500/30 uppercase tracking-widest text-sm active:scale-95">
                        Masuk Sebagai Admin
                        <i class="fas fa-lock ml-3 text-xs"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-20 text-center text-secondary-300 font-bold text-xs uppercase tracking-[0.3em]">
            &copy; <?= date('Y') ?> RT 06 Serua Indah &bull; Modern Management System
        </div>
    </main>
<?php include 'includes/footer.php'; ?>

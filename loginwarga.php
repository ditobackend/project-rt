<?php
session_start();
include 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'warga'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = 'warga';

            header("Location: dashboard_warga.php");
            exit;
        } else {
            $error = "Kata sandi yang Anda masukkan salah.";
        }
    } else {
        $error = "Email tidak terdaftar sebagai warga.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Portal Warga | RT 06/08</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'], },
                    colors: {
                        primary: { 50: '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 300: '#6ee7b7', 400: '#34d399', 500: '#10b981', 600: '#059669', 700: '#047857', 800: '#065f46', 900: '#064e3b', 950: '#022c22', },
                        secondary: { 50: '#fafafa', 100: '#f4f4f5', 200: '#e4e4e7', 300: '#d4d4d8', 400: '#a1a1aa', 500: '#71717a', 600: '#52525b', 700: '#3f3f46', 800: '#27272a', 900: '#18181b', 950: '#09090b', }
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-image: radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.05) 0px, transparent 50%),
                              radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.05) 0px, transparent 50%);
        }
        .glass { 
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .input-premium:focus-within {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(5, 150, 105, 0.1);
        }
    </style>
</head>
<body class="bg-secondary-50 min-h-screen flex items-center justify-center p-6 relative">
    <!-- Decorative Elements -->
    <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 bg-primary-100 rounded-full blur-3xl opacity-50"></div>
    <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 bg-secondary-200 rounded-full blur-3xl opacity-50"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <a href="index.php" class="inline-flex items-center justify-center w-16 h-16 bg-primary-600 rounded-3xl shadow-xl shadow-primary-500/20 mb-4 hover:scale-105 transition-transform">
                <i class="fas fa-home text-white text-2xl"></i>
            </a>
            <h1 class="text-2xl font-black text-secondary-900 tracking-tight">Portal Warga</h1>
            <p class="text-secondary-500 font-medium">Masuk untuk akses layanan RT 06/08</p>
        </div>

        <!-- Login Card -->
        <div class="glass p-10 rounded-[2.5rem] shadow-2xl shadow-secondary-900/5 border border-white">
            <?php if (isset($error)) : ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-center gap-3 animate-in fade-in duration-500">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                    <p class="text-xs font-bold text-red-700 uppercase tracking-tight"><?= $error ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Alamat Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-secondary-300"></i>
                        <input type="email" name="email" placeholder="nama@email.com" required
                               class="w-full pl-12 pr-6 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-600 rounded-2xl text-secondary-900 font-medium placeholder-secondary-300 transition-all">
                    </div>
                </div>

                <div class="input-premium transition-all">
                    <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Kata Sandi</label>
                    <div class="relative group">
                        <i class="fas fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-secondary-300 group-focus-within:text-primary-500 transition-colors"></i>
                        <input type="password" name="password" id="passwordInput" placeholder="••••••••" required
                               class="w-full pl-12 pr-12 py-4 bg-secondary-50/50 border border-transparent focus:border-primary-500/20 focus:ring-4 focus:ring-primary-500/5 rounded-2xl text-secondary-900 font-medium placeholder-secondary-300 transition-all outline-none">
                        <button type="button" onclick="togglePassword()" class="absolute right-5 top-1/2 -translate-y-1/2 text-secondary-300 hover:text-primary-500 transition-colors focus:outline-none">
                            <i id="eyeIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full py-5 bg-primary-600 text-white font-black rounded-2xl hover:bg-primary-700 hover:shadow-2xl hover:shadow-primary-600/30 transition-all uppercase tracking-widest text-sm active:scale-[0.98] flex items-center justify-center gap-3">
                    Masuk Sekarang
                    <i class="fas fa-arrow-right text-xs"></i>
                </button>
            </form>

            <div class="mt-8 pt-8 border-t border-secondary-50 text-center">
                <p class="text-secondary-400 text-sm font-medium">Lupa akun? Hubungi <a href="#" class="text-primary-600 font-bold hover:underline">Ketua RT</a></p>
            </div>
        </div>

        <!-- Footer Link -->
        <div class="mt-8 text-center">
            <a href="index.php" class="text-secondary-400 text-xs font-bold uppercase tracking-widest hover:text-primary-600 transition-colors">
                <i class="fas fa-arrow-left mr-2 font-black"></i> Kembali Ke Beranda
            </a>
        </div>
    </div>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>

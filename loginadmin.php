<?php
session_start();
include 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['role'] = 'admin';

            header("Location: dashboard_admin.php");
            exit;
        } else {
            $error = "Kata sandi administrator salah.";
        }
    } else {
        $error = "Email tidak memiliki hak akses admin.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator | RT 06/08</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'], },
                    colors: {
                        primary: { 50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac', 400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d', 800: '#166534', 900: '#14532d', 950: '#052e16', },
                        secondary: { 50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1', 400: '#94a3b8', 500: '#64748b', 600: '#475569', 700: '#334155', 800: '#1e293b', 900: '#0f172a', 950: '#020617', }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-secondary-50 min-h-screen flex items-center justify-center p-6 relative">
    <!-- Decorative Elements -->
    <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 bg-primary-100 rounded-full blur-3xl opacity-50"></div>
    <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 bg-secondary-200 rounded-full blur-3xl opacity-50"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Header -->
        <div class="text-center mb-8">
            <a href="index.php" class="inline-flex items-center justify-center w-16 h-16 bg-secondary-900 rounded-3xl shadow-xl mb-4 hover:scale-105 transition-transform border border-white/20">
                <i class="fas fa-user-shield text-primary-500 text-2xl"></i>
            </a>
            <h1 class="text-2xl font-black text-secondary-900 tracking-tight">Admin Gate</h1>
            <p class="text-secondary-500 font-medium">Otoritas Pengurus RT 06/08</p>
        </div>

        <!-- Login Card -->
        <div class="glass p-10 rounded-[2.5rem] shadow-2xl shadow-secondary-900/5 border border-white">
            <?php if (isset($error)) : ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-center gap-3">
                    <i class="fas fa-exclamation-triangle text-red-500"></i>
                    <p class="text-xs font-bold text-red-700 uppercase tracking-tight"><?= $error ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Email Administrator</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-secondary-300"></i>
                        <input type="email" name="email" placeholder="admin@email.com" required
                               class="w-full pl-12 pr-6 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-600 rounded-2xl text-secondary-900 font-medium transition-all outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-2 ml-1">Password</label>
                    <div class="relative">
                        <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-secondary-300"></i>
                        <input type="password" name="password" placeholder="••••••••" required
                               class="w-full pl-12 pr-6 py-4 bg-secondary-50 border-0 focus:ring-2 focus:ring-primary-600 rounded-2xl text-secondary-900 font-medium transition-all outline-none">
                    </div>
                </div>

                <button type="submit" class="w-full py-5 bg-secondary-900 text-white font-black rounded-2xl hover:bg-secondary-800 transition-all shadow-xl shadow-secondary-900/10 uppercase tracking-widest text-sm active:scale-95 flex items-center justify-center gap-3">
                    Masuk Dashboard
                    <i class="fas fa-arrow-right text-xs text-primary-500"></i>
                </button>
            </form>

            <div class="mt-8 pt-8 border-t border-secondary-50 text-center">
                <p class="text-secondary-400 text-[10px] font-black uppercase tracking-widest">Akses Pengurus RT Saja</p>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="index.php" class="text-secondary-400 text-xs font-bold uppercase tracking-widest hover:text-primary-600 transition-colors">
                <i class="fas fa-chevron-left mr-2 font-black"></i> Kembali Ke Beranda
            </a>
        </div>
    </div>
</body>
</html>

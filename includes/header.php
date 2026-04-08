<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? 'guest';
$primaryPalette = [];
$secondaryPalette = [];

if ($role === 'warga') {
    // Emerald Theme (Option A)
    $primaryPalette = [
        50 => '#ecfdf5', 100 => '#d1fae5', 200 => '#a7f3d0', 300 => '#6ee7b7', 
        400 => '#34d399', 500 => '#10b981', 600 => '#059669', 700 => '#047857', 
        800 => '#065f46', 900 => '#064e3b', 950 => '#022c22'
    ];
    $secondaryPalette = [
        50 => '#fafafa', 100 => '#f4f4f5', 200 => '#e4e4e7', 300 => '#d4d4d8', 
        400 => '#a1a1aa', 500 => '#71717a', 600 => '#52525b', 700 => '#3f3f46', 
        800 => '#27272a', 900 => '#18181b', 950 => '#09090b'
    ];
    $sidebarGradient = ['#059669', '#10b981'];
} else {
    // Indigo Theme (Default/Admin)
    $primaryPalette = [
        50 => '#eef2ff', 100 => '#e0e7ff', 200 => '#c7d2fe', 300 => '#a5b4fc', 
        400 => '#818cf8', 500 => '#6366f1', 600 => '#4f46e5', 700 => '#4338ca', 
        800 => '#3730a3', 900 => '#312e81', 950 => '#1e1b4b'
    ];
    $secondaryPalette = [
        50 => '#f8fafc', 100 => '#f1f5f9', 200 => '#e2e8f0', 300 => '#cbd5e1', 
        400 => '#94a3b8', 500 => '#64748b', 600 => '#475569', 700 => '#334155', 
        800 => '#1e293b', 900 => '#0f172a', 950 => '#020617'
    ];
    $sidebarGradient = ['#4f46e5', '#6366f1'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Pengelolaan RT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        primary: <?= json_encode($primaryPalette) ?>,
                        secondary: <?= json_encode($secondaryPalette) ?>,
                        // Full palettes for explicit usage (e.g. on index.php)
                        emerald: {
                            50: '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 300: '#6ee7b7', 
                            400: '#34d399', 500: '#10b981', 600: '#059669', 700: '#047857', 
                            800: '#065f46', 900: '#064e3b', 950: '#022c22'
                        },
                        indigo: {
                            50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 
                            400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 
                            800: '#3730a3', 900: '#312e81', 950: '#1e1b4b'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .sidebar-active {
            background: linear-gradient(to right, <?= $sidebarGradient[0] ?>, <?= $sidebarGradient[1] ?>);
            color: white !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .card-modern:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1; /* slate-300 */
            border-radius: 20px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; /* slate-400 */
        }
    </style>
    <!-- Tambah Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

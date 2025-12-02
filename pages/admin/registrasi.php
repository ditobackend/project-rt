<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../config/database.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginadmin.php");
    exit;
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = "warga"; // default role

    $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nama, $email, $password, $role);

    if ($stmt->execute()) {
        $message = "<div class='p-3 bg-green-100 text-green-700 rounded'>Akun warga berhasil diregistrasi.</div>";
    } else {
        $message = "<div class='p-3 bg-red-100 text-red-700 rounded'>Gagal registrasi: " . $stmt->error . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Registrasi Warga</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Form Registrasi -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-lg font-bold mb-4">Form Registrasi Akun</h2>
        <?= $message ?>
        <form action="" method="POST" class="space-y-4"> <!-- action dikosongkan -->
            <div>
                <label class="block font-medium">Nama Warga</label>
                <input type="text" name="nama" required
                       class="w-full border rounded px-3 py-2 focus:ring focus:ring-green-300">
            </div>
            <div>
                <label class="block font-medium">Email</label>
                <input type="email" name="email" required
                       class="w-full border rounded px-3 py-2 focus:ring focus:ring-green-300">
            </div>
            <div>
                <label class="block font-medium">Password</label>
                <input type="password" name="password" required
                       class="w-full border rounded px-3 py-2 focus:ring focus:ring-green-300">
            </div>
            <div>
                <label class="block font-medium">Role</label>
                <input type="text" name="role" value="warga" readonly
                       class="w-full border rounded px-3 py-2 bg-gray-100">
            </div>
            <button type="submit"
                    class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Daftarkan
            </button>
        </form>
    </div>

    <!-- Daftar Akun Terdaftar -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-lg font-bold mb-4">Akun yang sudah terdaftar</h2>
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-3 py-2 text-left">Nama</th>
                    <th class="border px-3 py-2 text-left">Email</th>
                    <th class="border px-3 py-2 text-left">Role</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = mysqli_query($conn, "SELECT nama, email, role FROM users ORDER BY id DESC");
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>
                                <td class='border px-3 py-2'>{$row['nama']}</td>
                                <td class='border px-3 py-2'>{$row['email']}</td>
                                <td class='border px-3 py-2'>{$row['role']}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' class='text-center py-3 text-gray-500'>Belum ada akun terdaftar</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

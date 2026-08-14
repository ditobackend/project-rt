<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak. Silakan login.");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = $conn->query("
    SELECT sp.*, dk.nama_lengkap, dk.nik, dk.tempat_lahir, dk.tanggal_lahir, 
           dk.jenis_kelamin, dk.agama, dk.pekerjaan, dk.status_perkawinan,
           u.nama as nama_pengaju 
    FROM surat_pengantar sp 
    JOIN data_keluarga dk ON sp.data_keluarga_id = dk.id 
    JOIN users u ON sp.user_id = u.id 
    WHERE sp.id = $id AND sp.status = 'disetujui'
");

if ($query->num_rows == 0) {
    die("Surat tidak ditemukan atau belum disetujui.");
}

$surat = $query->fetch_assoc();

// Pastikan hanya warga yang mengajukan ATAU ketua RT yang bisa cetak
if ($_SESSION['role'] == 'warga' && $surat['user_id'] != $_SESSION['user_id']) {
    die("Akses ditolak.");
}

$hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$bulan = ['January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'];

$tanggal_cetak = strtr(date('d F Y'), $bulan);
$nomor_surat = sprintf("%03d", $surat['id']) . "/RT06-RW08/" . date('m/Y', strtotime($surat['tanggal_disetujui']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Pengantar - <?= htmlspecialchars($surat['nama_lengkap']) ?></title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.5;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            max-width: 800px;
            margin: 40px auto;
            padding: 40px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .kop-surat {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .kop-surat h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .kop-surat h2 {
            margin: 5px 0 0 0;
            font-size: 20px;
            font-weight: normal;
        }
        .kop-surat p {
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .judul-surat {
            text-align: center;
            margin-bottom: 30px;
        }
        .judul-surat h3 {
            margin: 0;
            font-size: 18px;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .judul-surat p {
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .content p {
            text-indent: 40px;
            text-align: justify;
        }
        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
        }
        td {
            padding: 5px;
            vertical-align: top;
        }
        td:first-child {
            width: 30%;
        }
        td:nth-child(2) {
            width: 2%;
        }
        .ttd-section {
            margin-top: 50px;
            display: flex;
            justify-content: flex-end;
        }
        .ttd-box {
            text-align: center;
            width: 250px;
        }
        .ttd-box p {
            margin: 0;
        }
        .ttd-space {
            height: 80px;
        }
        .nama-ttd {
            font-weight: bold;
            text-decoration: underline;
        }
        
        @media print {
            body { background: none; }
            .container { box-shadow: none; width: 100%; max-width: none; margin: 0; padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print" style="text-align: right; margin-bottom: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #000; color: #fff; border: none; cursor: pointer; font-weight: bold;">Cetak Surat / Simpan PDF</button>
        </div>

        <div class="kop-surat">
            <h1>RUKUN TETANGGA 06 / RUKUN WARGA 08</h1>
            <h2>KELURAHAN SERUA INDAH, KECAMATAN CIPUTAT</h2>
            <p>KOTA TANGERANG SELATAN, PROVINSI BANTEN</p>
        </div>

        <div class="judul-surat">
            <h3>SURAT PENGANTAR</h3>
            <p>Nomor : <?= $nomor_surat ?></p>
        </div>

        <div class="content">
            <p>Yang bertanda tangan di bawah ini selaku Ketua RT 06 / RW 08 Kelurahan Serua Indah, Kecamatan Ciputat, menerangkan dengan sebenarnya bahwa:</p>
            
            <table>
                <tr>
                    <td>Nama Lengkap</td>
                    <td>:</td>
                    <td><strong><?= htmlspecialchars($surat['nama_lengkap']) ?></strong></td>
                </tr>
                <tr>
                    <td>Tempat, Tanggal Lahir</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($surat['tempat_lahir']) ?>, <?= date('d M Y', strtotime($surat['tanggal_lahir'])) ?></td>
                </tr>
                <tr>
                    <td>Jenis Kelamin</td>
                    <td>:</td>
                    <td><?= $surat['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                </tr>
                <tr>
                    <td>Agama</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($surat['agama']) ?></td>
                </tr>
                <tr>
                    <td>Pekerjaan</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($surat['pekerjaan']) ?></td>
                </tr>
                <tr>
                    <td>Status Perkawinan</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($surat['status_perkawinan']) ?></td>
                </tr>
                <tr>
                    <td>NIK</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($surat['nik']) ?></td>
                </tr>
            </table>

            <p>Bahwa nama tersebut di atas benar adalah Warga RT 06 / RW 08 Kelurahan Serua Indah dan berdomisili di wilayah kami. Surat pengantar ini diberikan untuk keperluan:</p>
            
            <p style="text-align: center; font-weight: bold; margin: 15px 0;">"<?= htmlspecialchars($surat['jenis_surat']) ?> (<?= htmlspecialchars($surat['keperluan']) ?>)"</p>

            <p>Demikian surat pengantar ini dibuat dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya oleh pihak yang berwenang.</p>
        </div>

        <div class="ttd-section">
            <div class="ttd-box">
                <p>Serua Indah, <?= $tanggal_cetak ?></p>
                <p>Ketua RT 06 / RW 08</p>
                <div class="ttd-space"></div>
                <p class="nama-ttd">......................................</p>
            </div>
        </div>
    </div>
</body>
</html>

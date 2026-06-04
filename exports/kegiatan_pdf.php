<?php
require("../vendor/fpdf/fpdf.php");
require("../config/database.php");

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();

// KOP SURAT
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 8, 'PENGURUS RUKUN TETANGGA 06 / RW 08', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Kelurahan Contoh, Kecamatan Teladan, Kota Administratif', 0, 1, 'C');
$pdf->Cell(0, 6, 'Email: rt06@contoh.com | Telp: 0812-3456-7890', 0, 1, 'C');
$pdf->Line(10, 32, 200, 32);
$pdf->Line(10, 33, 200, 33); // Double line for Kop
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN REKAPITULASI KEGIATAN WARGA', 0, 1, 'C');
$pdf->Ln(2);

// Filter Input
$filter_status = $_GET['filter_status'] ?? 'semua';
$filter_bulan = $_GET['filter_bulan'] ?? 'semua';
$where = [];
$params = [];
$types = '';

if ($filter_status != 'semua') {
    $where[] = "k.status_persetujuan = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($filter_bulan != 'semua') {
    $parts = explode('-', $filter_bulan);
    if (count($parts) == 2) {
        $tahun = $parts[0];
        $bulan = $parts[1];
        $where[] = "MONTH(k.tanggal) = ? AND YEAR(k.tanggal) = ?";
        $params[] = $bulan;
        $params[] = $tahun;
        $types .= 'ii';
    }
}

$whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
$query = "
    SELECT k.*, u.nama as pengusul 
    FROM kegiatan k 
    LEFT JOIN users u ON k.diajukan_oleh = u.id 
    $whereClause 
    ORDER BY k.tanggal DESC
";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Kalkulasi Statistik
$total = 0;
$disetujui = 0;
$ditolak = 0;
$pending = 0;
$rows = [];

while($row = $result->fetch_assoc()) {
    $rows[] = $row;
    $total++;
    $stat = strtolower($row['status_persetujuan'] ?? 'pending');
    if ($stat == 'disetujui') $disetujui++;
    elseif ($stat == 'ditolak') $ditolak++;
    else $pending++;
}

// Info Filter
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(40, 6, 'Bulan/Tahun Laporan', 0, 0, 'L');
$pdf->Cell(5, 6, ':', 0, 0, 'C');
$pdf->Cell(0, 6, ($filter_bulan == 'semua' ? 'Keseluruhan' : $filter_bulan), 0, 1, 'L');

$pdf->Cell(40, 6, 'Filter Status', 0, 0, 'L');
$pdf->Cell(5, 6, ':', 0, 0, 'C');
$pdf->Cell(0, 6, ucfirst($filter_status), 0, 1, 'L');
$pdf->Ln(2);

// Ringkasan Statistik
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(190, 8, ' Ringkasan Statistik', 1, 1, 'L', true);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(47.5, 8, "Total Pengajuan: $total", 1, 0, 'C');
$pdf->Cell(47.5, 8, "Disetujui: $disetujui", 1, 0, 'C');
$pdf->Cell(47.5, 8, "Ditolak: $ditolak", 1, 0, 'C');
$pdf->Cell(47.5, 8, "Menunggu: $pending", 1, 1, 'C');
$pdf->Ln(5);

// Header Tabel Data
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(200, 220, 255);
$pdf->Cell(10, 10, 'No', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Tanggal', 1, 0, 'C', true);
$pdf->Cell(50, 10, 'Nama Kegiatan', 1, 0, 'C', true);
$pdf->Cell(45, 10, 'Tempat', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Pengusul', 1, 0, 'C', true);
$pdf->Cell(25, 10, 'Status', 1, 1, 'C', true);

// Body Tabel Data
$pdf->SetFont('Arial', '', 9);
if (count($rows) > 0) {
    $no = 1;
    foreach($rows as $row) {
        $tanggal = date('d M Y', strtotime($row['tanggal']));
        
        $judul = $row['judul'];
        if(strlen($judul) > 25) $judul = substr($judul, 0, 22) . '...';
        
        $tempat = $row['tempat'];
        if(strlen($tempat) > 25) $tempat = substr($tempat, 0, 22) . '...';
        
        $pengusul = $row['pengusul'] ?? '-';
        if(strlen($pengusul) > 15) $pengusul = substr($pengusul, 0, 12) . '...';
        
        $status = ucfirst($row['status_persetujuan'] ?? 'Pending');
        
        // Color coding for status
        if ($status == 'Disetujui') {
            $pdf->SetTextColor(0, 150, 0);
        } elseif ($status == 'Ditolak') {
            $pdf->SetTextColor(200, 0, 0);
        } elseif ($status == 'Pending') {
            $pdf->SetTextColor(200, 150, 0);
        } else {
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->Cell(10, 10, $no++, 1, 0, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(30, 10, $tanggal, 1, 0, 'C');
        $pdf->Cell(50, 10, $judul, 1, 0, 'L');
        $pdf->Cell(45, 10, $tempat, 1, 0, 'L');
        $pdf->Cell(30, 10, $pengusul, 1, 0, 'C');
        
        // restore color for status column
        if ($status == 'Disetujui') $pdf->SetTextColor(0, 150, 0);
        elseif ($status == 'Ditolak') $pdf->SetTextColor(200, 0, 0);
        elseif ($status == 'Pending') $pdf->SetTextColor(200, 150, 0);
        $pdf->Cell(25, 10, $status, 1, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }
} else {
    $pdf->Cell(190, 10, 'Tidak ada data kegiatan', 1, 1, 'C');
}

$pdf->Ln(15);

// Tanda Tangan
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(120, 5, '', 0, 0);
$pdf->Cell(70, 5, 'Jakarta, ' . date('d F Y'), 0, 1, 'C');
$pdf->Cell(120, 5, '', 0, 0);
$pdf->Cell(70, 5, 'Mengetahui,', 0, 1, 'C');
$pdf->Cell(120, 5, '', 0, 0);
$pdf->Cell(70, 5, 'Ketua RT 06', 0, 1, 'C');

$pdf->Ln(20);

$pdf->Cell(120, 5, '', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(70, 5, '( ...................................... )', 0, 1, 'C');

$pdf->Output();
?>

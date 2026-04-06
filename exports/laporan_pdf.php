<?php
require("../vendor/fpdf/fpdf.php");
require("../config/database.php");

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Laporan Keuangan RT',0,1,'C');
$pdf->Ln(5);

// Header
$pdf->SetFont('Arial','B',11);
$pdf->Cell(35,10,'Tanggal',1,0,'C');
$pdf->Cell(85,10,'Keterangan',1,0,'C');
$pdf->Cell(30,10,'Jenis',1,0,'C');
$pdf->Cell(40,10,'Jumlah',1,1,'C');

// Fetch Data
$filter_jenis = $_GET['filter_jenis'] ?? '';
$filter_bulan = $_GET['filter_bulan'] ?? '';
$where = [];
$params = [];
$types = '';

if (!empty($filter_jenis) && $filter_jenis != 'semua') {
    $where[] = "jenis = ?";
    $params[] = $filter_jenis;
    $types .= 's';
}

if (!empty($filter_bulan) && $filter_bulan != 'semua') {
    list($tahun, $bulan) = explode('-', $filter_bulan);
    $where[] = "MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
    $params[] = $bulan;
    $params[] = $tahun;
    $types .= 'ii';
}

$whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
$query = "SELECT tanggal, keterangan, jenis, jumlah FROM keuangan $whereClause ORDER BY tanggal DESC";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Data
$totalPemasukan = 0;
$totalPengeluaran = 0;

$pdf->SetFont('Arial','',10);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if ($row['jenis'] == 'pemasukan') {
            $totalPemasukan += $row['jumlah'];
        } else {
            $totalPengeluaran += $row['jumlah'];
        }
        
        $tanggal = date('d M Y', strtotime($row['tanggal']));
        
        $keterangan = $row['keterangan'];
        if(strlen($keterangan) > 40) {
            $keterangan = substr($keterangan, 0, 37) . '...';
        }
        
        $jenis = ucfirst($row['jenis']);
        
        $prefix = ($row['jenis'] == 'pemasukan') ? '+ Rp ' : '- Rp ';
        $jumlah = $prefix . number_format($row['jumlah'], 0, ',', '.');
        
        $pdf->Cell(35,10,$tanggal,1,0,'C');
        $pdf->Cell(85,10,$keterangan,1,0,'L');
        $pdf->Cell(30,10,$jenis,1,0,'C');
        
        if ($row['jenis'] == 'pemasukan') {
            $pdf->SetTextColor(0, 150, 0);
        } else {
            $pdf->SetTextColor(200, 0, 0);
        }
        $pdf->Cell(40,10,$jumlah,1,1,'R');
        $pdf->SetTextColor(0, 0, 0);
    }

    $saldoAkhir = $totalPemasukan - $totalPengeluaran;
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(150,10,'Total Keseluruhan (Saldo Akhir)',1,0,'R');
    if ($saldoAkhir >= 0) {
        $pdf->SetTextColor(0, 150, 0);
        $pdf->Cell(40,10,'Rp ' . number_format($saldoAkhir, 0, ',', '.'),1,1,'R');
    } else {
        $pdf->SetTextColor(200, 0, 0);
        $pdf->Cell(40,10,'- Rp ' . number_format(abs($saldoAkhir), 0, ',', '.'),1,1,'R');
    }
    $pdf->SetTextColor(0, 0, 0);

} else {
    $pdf->Cell(190,10,'Tidak ada data',1,1,'C');
}

$pdf->Output();
?>

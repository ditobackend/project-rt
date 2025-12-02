<?php
require("../vendor/fpdf/fpdf.php");

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Laporan Keuangan RT',0,1,'C');
$pdf->Ln(10);

// Header
$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Tanggal',1);
$pdf->Cell(80,10,'Keterangan',1);
$pdf->Cell(30,10,'Jenis',1);
$pdf->Cell(40,10,'Jumlah',1);
$pdf->Ln();

// Data contoh
$data = [
    ["10 Jan 2024", "Iuran Bulanan Warga", "Pemasukan", "+ Rp 5.000.000"],
    ["08 Jan 2024", "Pembelian Alat Kebersihan", "Pengeluaran", "- Rp 500.000"],
];

$pdf->SetFont('Arial','',12);
foreach($data as $row){
    foreach($row as $col)
        $pdf->Cell(40,10,$col,1);
    $pdf->Ln();
}

$pdf->Output();

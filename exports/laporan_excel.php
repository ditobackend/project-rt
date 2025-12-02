<?php
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_keuangan.xls");

echo "Tanggal\tKeterangan\tJenis\tJumlah\n";
echo "10 Jan 2024\tIuran Bulanan Warga\tPemasukan\t+ Rp 5.000.000\n";
echo "08 Jan 2024\tPembelian Alat Kebersihan\tPengeluaran\t- Rp 500.000\n";

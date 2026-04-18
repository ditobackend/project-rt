<?php
require_once __DIR__ . '/config/database.php';

$settings = [
    ['rek_bank_pilihan', 'Bank BCA', 'rekening', 'Bank yang dipilih'],
    ['rek_bank_nomor', '123 456 7890', 'rekening', 'Nomor Rekening'],
    ['rek_bank_atas_nama', 'a.n. Kas RT 06/08', 'rekening', 'Nama Pemilik Rekening']
];

foreach ($settings as $s) {
    $stmt = $conn->prepare("INSERT IGNORE INTO pengaturan (kunci, nilai, kategori, keterangan) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $s[0], $s[1], $s[2], $s[3]);
    $stmt->execute();
}
echo "New bank keys seeded successfully.";
?>

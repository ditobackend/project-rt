<?php
require_once __DIR__ . '/config/database.php';

$sql = "CREATE TABLE IF NOT EXISTS `pengaturan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kunci` varchar(100) NOT NULL UNIQUE,
  `nilai` text NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";

if ($conn->query($sql)) {
    echo "Table 'pengaturan' created successfully.<br>";
    
    $settings = [
        ['iuran_bulanan', '50000', 'iuran', 'Iuran Bulanan'],
        ['iuran_keamanan', '25000', 'iuran', 'Iuran Keamanan']
    ];

    foreach ($settings as $s) {
        $stmt = $conn->prepare("INSERT IGNORE INTO pengaturan (kunci, nilai, kategori, keterangan) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $s[0], $s[1], $s[2], $s[3]);
        $stmt->execute();
    }
    echo "Initial settings seeded successfully.";
} else {
    echo "Error creating table: " . $conn->error;
}
?>

-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 07, 2026 at 03:47 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rt_manajemen`
--

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan`
--

CREATE TABLE `kegiatan` (
  `id` int NOT NULL,
  `judul` varchar(200) NOT NULL,
  `deskripsi` text,
  `tanggal` date NOT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `status` enum('akan_datang','berlangsung','selesai') DEFAULT 'akan_datang',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `diajukan_oleh` int DEFAULT NULL,
  `tempat` varchar(255) DEFAULT NULL,
  `status_persetujuan` enum('pending','disetujui','ditolak') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kegiatan`
--

INSERT INTO `kegiatan` (`id`, `judul`, `deskripsi`, `tanggal`, `jam_mulai`, `jam_selesai`, `status`, `created_at`, `diajukan_oleh`, `tempat`, `status_persetujuan`) VALUES
(12, 'kerja bakti', 'bersihin lpgn', '2026-05-04', '19:26:00', '19:27:00', 'akan_datang', '2026-05-04 12:24:48', 3, 'lapangan', 'disetujui'),
(13, 'kerja bakti', 'sjsj', '2026-05-04', '19:31:00', '19:32:00', 'akan_datang', '2026-05-04 12:30:31', 3, 'lapangan', 'ditolak'),
(14, 'ngaji', 'tahlilan', '2026-05-04', '20:17:00', '20:18:00', 'akan_datang', '2026-05-04 13:16:08', 3, 'rumah kunyuk', 'disetujui');

-- --------------------------------------------------------

--
-- Table structure for table `keuangan`
--

CREATE TABLE `keuangan` (
  `id` int NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `jenis` enum('pemasukan','pengeluaran') NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `admin_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `keuangan`
--

INSERT INTO `keuangan` (`id`, `tanggal`, `keterangan`, `jenis`, `jumlah`, `admin_id`, `created_at`) VALUES
(17, '2026-04-06', 'ditto - Donasi Kegiatan - oke', 'pemasukan', '9.00', NULL, '2026-04-06 13:23:26'),
(18, '2026-04-06', 'Admin - Operasional - beli sapu', 'pengeluaran', '9.00', NULL, '2026-04-06 13:33:55'),
(19, '2026-04-07', 'ditto - Iuran Keamanan - wwefe', 'pemasukan', '25.00', NULL, '2026-04-07 13:23:41'),
(20, '2026-04-07', 'yono - Donasi Kegiatan - siap', 'pemasukan', '7.00', NULL, '2026-04-07 13:46:46'),
(22, '2025-12-03', 'ditto -  - -', 'pemasukan', '1.00', NULL, '2026-04-18 10:05:22'),
(23, '2025-12-03', 'ditto -  - -', 'pemasukan', '2.00', NULL, '2026-04-18 10:05:22'),
(24, '2025-12-05', 'ditto -  - -', 'pemasukan', '3.00', NULL, '2026-04-18 10:05:22'),
(25, '2025-12-24', 'ditto - Donasi Kegiatan - -', 'pemasukan', '2.00', NULL, '2026-04-18 10:05:22'),
(26, '2026-01-02', 'ditto - Iuran Bulanan - -', 'pemasukan', '2.00', NULL, '2026-04-18 10:05:22'),
(27, '2026-04-07', 'yono - Donasi Kegiatan - aman', 'pemasukan', '7.00', NULL, '2026-04-18 10:05:22'),
(28, '2026-04-18', 'ditto - Donasi Kegiatan - oke', 'pemasukan', '2.00', NULL, '2026-04-18 10:05:22'),
(29, '2026-04-18', 'ditto - Iuran Bulanan - siap', 'pemasukan', '12.00', NULL, '2026-04-18 10:08:36'),
(30, '2026-04-19', 'ditto - Donasi Kegiatan - free', 'pemasukan', '5.00', NULL, '2026-04-19 13:46:35'),
(31, '2026-04-19', 'Admin - Kegiatan Sosial - donasi panti', 'pengeluaran', '10.00', 1, '2026-04-19 13:47:36'),
(32, '2026-05-04', 'Admin - Lainnya - beli gorengan', 'pengeluaran', '18.00', 1, '2026-05-04 09:18:20'),
(33, '2026-05-04', 'Admin - Lainnya - beli minum', 'pengeluaran', '5.00', 1, '2026-05-04 12:47:11'),
(37, '2026-05-04', 'ditto - Iuran Bulanan - sudah', 'pemasukan', '10.00', NULL, '2026-05-04 13:18:07');

-- --------------------------------------------------------

--
-- Table structure for table `laporan`
--

CREATE TABLE `laporan` (
  `id` int NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `jenis` enum('pemasukan','pengeluaran') NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `admin_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `laporan`
--

INSERT INTO `laporan` (`id`, `tanggal`, `keterangan`, `jenis`, `jumlah`, `admin_id`, `created_at`) VALUES
(1, '2026-04-06', 'ditto - Donasi Kegiatan - oke', 'pemasukan', '9.00', NULL, '2026-04-06 13:23:26'),
(2, '2026-04-06', 'Admin - Operasional - beli sapu', 'pengeluaran', '9.00', NULL, '2026-04-06 13:33:55'),
(3, '2026-04-07', 'ditto - Iuran Keamanan - wwefe', 'pemasukan', '25.00', NULL, '2026-04-07 13:23:41'),
(4, '2026-04-07', 'yono - Donasi Kegiatan - siap', 'pemasukan', '7.00', NULL, '2026-04-07 13:46:46'),
(5, '2025-12-03', 'ditto -  - -', 'pemasukan', '1.00', NULL, '2026-04-18 10:05:22'),
(6, '2025-12-03', 'ditto -  - -', 'pemasukan', '2.00', NULL, '2026-04-18 10:05:22'),
(7, '2025-12-05', 'ditto -  - -', 'pemasukan', '3.00', NULL, '2026-04-18 10:05:22'),
(8, '2025-12-24', 'ditto - Donasi Kegiatan - -', 'pemasukan', '2.00', NULL, '2026-04-18 10:05:22'),
(9, '2026-01-02', 'ditto - Iuran Bulanan - -', 'pemasukan', '2.00', NULL, '2026-04-18 10:05:22'),
(10, '2026-04-07', 'yono - Donasi Kegiatan - aman', 'pemasukan', '7.00', NULL, '2026-04-18 10:05:22'),
(11, '2026-04-18', 'ditto - Donasi Kegiatan - oke', 'pemasukan', '2.00', NULL, '2026-04-18 10:05:22'),
(12, '2026-04-18', 'ditto - Iuran Bulanan - siap', 'pemasukan', '12.00', NULL, '2026-04-18 10:08:36'),
(13, '2026-04-19', 'ditto - Donasi Kegiatan - free', 'pemasukan', '5.00', NULL, '2026-04-19 13:46:35'),
(14, '2026-04-19', 'Admin - Kegiatan Sosial - donasi panti', 'pengeluaran', '10.00', 1, '2026-04-19 13:47:36'),
(15, '2026-05-04', 'Admin - Lainnya - beli gorengan', 'pengeluaran', '18.00', 1, '2026-05-04 09:18:20'),
(16, '2026-05-04', 'Admin - Lainnya - beli minum', 'pengeluaran', '5.00', 1, '2026-05-04 12:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `metode` enum('transfer','cash','midtrans') DEFAULT 'transfer',
  `status` enum('pending','berhasil','gagal') DEFAULT 'pending',
  `tanggal` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `order_id` varchar(50) DEFAULT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `catatan` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pembayaran`
--

INSERT INTO `pembayaran` (`id`, `user_id`, `jumlah`, `metode`, `status`, `tanggal`, `order_id`, `kategori`, `catatan`) VALUES
(26, 1, '1.00', 'midtrans', 'berhasil', '2025-12-02 07:06:41', 'ORDER-692e900196596-1764659201', NULL, NULL),
(27, 3, '1.00', 'midtrans', 'berhasil', '2025-12-02 22:45:21', 'ORDER-692f6c00c3720', NULL, NULL),
(28, 3, '1.00', 'midtrans', 'berhasil', '2025-12-02 22:46:13', 'ORDER-692f6c3576f27', NULL, NULL),
(29, 3, '1.00', 'midtrans', 'gagal', '2025-12-02 22:46:47', 'ORDER-692f6c57b144a', NULL, NULL),
(30, 3, '1.00', 'midtrans', 'gagal', '2025-12-02 22:46:54', 'ORDER-692f6c5dca77e', NULL, NULL),
(31, 3, '1.00', 'midtrans', 'gagal', '2025-12-02 22:46:58', 'ORDER-692f6c622d679', NULL, NULL),
(32, 3, '1.00', 'midtrans', 'berhasil', '2025-12-02 22:48:54', 'ORDER-692f6cd6c9a3e', NULL, NULL),
(33, 3, '2.00', 'midtrans', 'berhasil', '2025-12-02 22:50:31', 'ORDER-692f6d36d5dc7', NULL, NULL),
(34, 3, '1.00', 'midtrans', 'berhasil', '2025-12-05 02:32:23', 'ORDER-69324436da61a', NULL, NULL),
(35, 3, '3.00', 'midtrans', 'berhasil', '2025-12-05 02:42:30', 'ORDER-6932468b1b77f', NULL, NULL),
(36, 3, '1.00', 'midtrans', 'gagal', '2025-12-05 02:52:40', 'ORDER-693248f3b740f', NULL, NULL),
(37, 3, '1.00', 'midtrans', 'gagal', '2025-12-05 02:53:33', 'ORDER-693249268bd1a', NULL, NULL),
(38, 3, '1.00', 'midtrans', 'gagal', '2025-12-15 13:48:27', 'ORDER-694011ab8c344', NULL, NULL),
(39, 3, '1.00', 'midtrans', 'berhasil', '2025-12-23 07:57:55', 'ORDER-694a4b81c1f79', NULL, NULL),
(40, 3, '2.00', 'midtrans', 'berhasil', '2025-12-23 08:03:20', 'ORDER-694a4cc877e21', NULL, NULL),
(41, 3, '2.00', 'midtrans', 'berhasil', '2025-12-24 14:31:39', 'ORDER-694bf94a378f0', NULL, NULL),
(42, 3, '2.00', 'midtrans', 'berhasil', '2025-12-24 14:49:46', 'ORDER-694bfd89db6d3', 'Donasi Kegiatan', NULL),
(43, 3, '2.00', 'midtrans', 'berhasil', '2026-01-02 10:41:57', 'ORDER-6957a0f4f2801', 'Iuran Bulanan', NULL),
(44, 3, '9.00', 'midtrans', 'berhasil', '2026-04-06 13:21:49', 'ORDER-69d3b36ced71e', 'Donasi Kegiatan', 'oke'),
(45, 3, '25.00', 'midtrans', 'gagal', '2026-04-07 13:12:03', 'ORDER-69d502a2ce98e', 'Iuran Keamanan', 'semoga kampung makin aman'),
(46, 3, '25.00', 'midtrans', 'gagal', '2026-04-07 13:16:01', 'ORDER-69d5039154c9b', 'Donasi Kegiatan', 'ksq'),
(47, 3, '25.00', 'midtrans', 'berhasil', '2026-04-07 13:23:22', 'ORDER-69d50549f2c80', 'Iuran Keamanan', 'wwefe'),
(48, 5, '7.00', 'midtrans', 'berhasil', '2026-04-07 13:46:07', 'ORDER-69d50a9f245c0', 'Donasi Kegiatan', 'aman'),
(49, 3, '12.00', 'midtrans', 'gagal', '2026-04-18 09:55:05', 'ORDER-69e354f88c1fb', 'Donasi Kegiatan', 'done'),
(50, 3, '2.00', 'midtrans', 'berhasil', '2026-04-18 10:00:43', 'ORDER-69e3564ab4952', 'Donasi Kegiatan', 'oke'),
(51, 3, '12.00', 'midtrans', 'berhasil', '2026-04-18 10:08:04', 'ORDER-69e35804495f9', 'Iuran Bulanan', 'siap'),
(52, 3, '5.00', 'midtrans', 'berhasil', '2026-04-19 13:46:07', 'ORDER-69e4dc9edc186', 'Donasi Kegiatan', 'free'),
(53, 3, '10.00', 'midtrans', 'berhasil', '2026-05-04 13:17:32', 'ORDER-69f89c6c46257', 'Iuran Bulanan', 'sudah');

-- --------------------------------------------------------

--
-- Table structure for table `pengaduan`
--

CREATE TABLE `pengaduan` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `judul` varchar(150) DEFAULT NULL,
  `isi` text NOT NULL,
  `status` enum('diterima','diproses','selesai') DEFAULT 'diterima',
  `tanggapan_admin` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int NOT NULL,
  `kunci` varchar(100) NOT NULL,
  `nilai` text NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `kunci`, `nilai`, `kategori`, `keterangan`) VALUES
(1, 'iuran_bulanan', '25000', 'iuran', 'Iuran Bulanan'),
(2, 'iuran_keamanan', '15000', 'iuran', 'Iuran Keamanan'),
(3, 'bca_no_rek', '123 456 7890', 'rekening', 'Nomor Rekening BCA'),
(4, 'bca_nama', 'a.n. Kas RT 06/08', 'rekening', 'Nama Pemilik BCA'),
(5, 'mandiri_no_rek', '098 765 4321', 'rekening', 'Nomor Rekening Mandiri'),
(6, 'mandiri_nama', 'a.n. Kas RT 06/08', 'rekening', 'Nama Pemilik Mandiri'),
(13, 'rek_bank_pilihan', 'Bank BCA', 'rekening', 'Bank yang dipilih'),
(14, 'rek_bank_nomor', '123 456 7890', 'rekening', 'Nomor Rekening'),
(15, 'rek_bank_atas_nama', 'Kitabulloh', 'rekening', 'Nama Pemilik Rekening');


--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','warga','ketua_rt') DEFAULT 'warga',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin RT', 'admin@rt.com', '12345', 'admin', '2025-10-11 03:13:27'),
(2, 'putro', 'putro@rt.com', '12345', 'warga', '2025-10-11 03:24:35'),
(3, 'ditto', 'ditto@rt.com', '12345', 'warga', '2025-11-25 12:35:02'),
(5, 'yono', 'yono@rt.com', '12345', 'warga', '2025-12-23 08:22:04'),
(16, 'Ketua RT 06', 'ketua@email.com', 'ketua123', 'ketua_rt', '2026-05-04 12:07:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_diajukan_oleh` (`diajukan_oleh`);

--
-- Indexes for table `keuangan`
--
ALTER TABLE `keuangan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_keuangan_admin` (`admin_id`);

--
-- Indexes for table `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kunci` (`kunci`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kegiatan`
--
ALTER TABLE `kegiatan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `keuangan`
--
ALTER TABLE `keuangan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `pengaduan`
--
ALTER TABLE `pengaduan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD CONSTRAINT `fk_diajukan_oleh` FOREIGN KEY (`diajukan_oleh`) REFERENCES `users` (`id`);

--
-- Constraints for table `keuangan`
--
ALTER TABLE `keuangan`
  ADD CONSTRAINT `fk_keuangan_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `laporan`
--
ALTER TABLE `laporan`
  ADD CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD CONSTRAINT `pengaduan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

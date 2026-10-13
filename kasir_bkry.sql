-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2026 at 08:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kasir_bkry`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_pembelian`
--

CREATE TABLE `detail_pembelian` (
  `id` int(11) NOT NULL,
  `pembelian_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `subtotal` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `harga` int(11) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pembelian`
--

CREATE TABLE `pembelian` (
  `id` int(11) NOT NULL,
  `nama_pembeli` varchar(100) NOT NULL,
  `no_meja` int(11) NOT NULL,
  `tanggal_pembelian` date NOT NULL,
  `total_tagihan` int(11) NOT NULL,
  `status_bayar` enum('Belum','Lunas') DEFAULT 'Belum',
  `uang_bayar` int(11) DEFAULT NULL,
  `kembalian` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Kasir') DEFAULT 'Kasir',
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama_lengkap`, `username`, `password`, `role`, `email`) VALUES
(1, 'Khayla Almira', 'khayla', '896376696441fed538304b73f71857aa', 'Kasir', ''),
(2, '', 'mira', '1ba4e76c073e92cdddb8b09a062de7b5', 'Kasir', 'mira@gmail.com'),
(3, '', 'kayana', '6d331b7fea41e1f79e05b4c94089cbaa', 'Kasir', 'kyna@gmail.com'),
(4, '', 'rara', 'e31c3547c37b1bce2baf825d9ac9f955', 'Kasir', 'rara@gmail.com'),
(5, '', 'kayla', '9d746088cd8cef8274ba75fd1630114c', 'Kasir', 'kayla@gmail.com'),
(6, '', 'hafid', 'ac4bc4ee72797659f946e47a1dee27e8', 'Kasir', 'hafid@gmail.com'),
(7, '', 'kyla', 'cf5bdfb40421ac1f30cc4d45b66b5a81', 'Kasir', 'mira@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pembelian` (`pembelian_id`),
  ADD KEY `fk_menu` (`menu_id`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pembelian`
--
ALTER TABLE `pembelian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD CONSTRAINT `fk_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembelian` FOREIGN KEY (`pembelian_id`) REFERENCES `pembelian` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

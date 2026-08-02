-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 02, 2026 at 10:27 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kaih`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `foto_slideshow`
--

CREATE TABLE `foto_slideshow` (
  `id` int NOT NULL,
  `filename` varchar(255) NOT NULL,
  `judul` varchar(255) DEFAULT '',
  `urutan` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `id` bigint UNSIGNED NOT NULL,
  `nip` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_guru` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `no_hp` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guru`
--

INSERT INTO `guru` (`id`, `nip`, `nama_guru`, `kelas`, `alamat`, `no_hp`, `jabatan`, `created_at`, `updated_at`) VALUES
(1, '198501012010011001', 'Siti Rahayu, S.Pd.', '7B', 'Jl. Merdeka No. 10, Jakarta', '812345678', 'Guru B.Indonesia', '2026-03-15 19:13:54', '2026-07-30 14:44:11'),
(4, '123456789543', 'Alif S.Pd.', '7C', NULL, NULL, 'Informatika', '2026-07-25 07:58:47', '2026-07-30 14:37:39'),
(5, '134567898567', 'Aris Broto, S.Pd.', NULL, NULL, NULL, 'Kepala Sekolah', '2026-07-25 10:58:05', '2026-07-25 10:58:05'),
(6, '654345678987', 'Ferhan Niger Putih', '7A', NULL, NULL, 'Kimia', '2026-07-29 09:11:17', '2026-07-30 14:37:21'),
(8, '765438909654', 'KUSWANTO', '8C', NULL, NULL, 'Spanyol', '2026-07-30 13:11:22', '2026-07-30 14:36:57'),
(9, '9875445678909', 'KUSMIANTO', '8D', NULL, NULL, 'Biologi', '2026-07-30 13:57:31', '2026-07-30 14:37:12'),
(10, '169028735822', 'ADITIYA KUSUMA', '8G', NULL, NULL, 'Fisika', '2026-07-30 14:04:08', '2026-07-30 14:37:51');

-- --------------------------------------------------------

--
-- Table structure for table `kaih_kelas`
--

CREATE TABLE `kaih_kelas` (
  `id` bigint UNSIGNED NOT NULL,
  `nama_kelas` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kaih_kelas`
--

INSERT INTO `kaih_kelas` (`id`, `nama_kelas`, `created_at`, `updated_at`) VALUES
(14, '7A', NULL, NULL),
(15, '7B', NULL, NULL),
(16, '7C', NULL, NULL),
(17, '7D', NULL, NULL),
(18, '8A', NULL, NULL),
(19, '8B', NULL, NULL),
(20, '8C', NULL, NULL),
(21, '8D', NULL, NULL),
(22, '8G', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `laporan`
--

CREATE TABLE `laporan` (
  `id` bigint UNSIGNED NOT NULL,
  `siswa_id` bigint UNSIGNED NOT NULL,
  `guru_id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `kategori` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laporan_harian`
--

CREATE TABLE `laporan_harian` (
  `id` int NOT NULL,
  `siswa_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `bangun` tinyint(1) NOT NULL DEFAULT '0',
  `ibadah` tinyint(1) NOT NULL DEFAULT '0',
  `ibadah_catatan` varchar(255) DEFAULT NULL,
  `olahraga` tinyint(1) NOT NULL DEFAULT '0',
  `olahraga_jenis` varchar(50) DEFAULT NULL,
  `sarapan` tinyint(1) NOT NULL DEFAULT '0',
  `sarapan_menu` varchar(50) DEFAULT NULL,
  `membaca` tinyint(1) NOT NULL DEFAULT '0',
  `membaca_judul` varchar(255) DEFAULT NULL,
  `membaca_menit` int DEFAULT NULL,
  `membantu` tinyint(1) NOT NULL DEFAULT '0',
  `membantu_jenis` varchar(50) DEFAULT NULL,
  `menabung` tinyint(1) NOT NULL DEFAULT '0',
  `menabung_nominal` int DEFAULT NULL,
  `orang_tua_validated_at` datetime DEFAULT NULL,
  `guru_validated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id` bigint UNSIGNED NOT NULL,
  `nisn` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_siswa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wali_kelas_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id`, `nisn`, `nama_siswa`, `kelas`, `wali_kelas_id`, `created_at`, `updated_at`) VALUES
(5, '4567896543', 'Renata Moeloek', '7A', 14, '2026-07-22 11:18:22', '2026-07-24 08:49:23'),
(6, '1456785', 'Calvin Hidayat', '7A', 14, '2026-07-22 11:32:24', '2026-07-24 08:49:23'),
(7, '5898765', 'Rafif Budiana', '7B', 15, '2026-07-22 11:32:45', '2026-07-24 08:49:23'),
(9, '8765567890', 'Niger', '7C', 16, '2026-07-29 08:32:37', '2026-07-29 09:04:43'),
(10, '654567890', 'KIRUS', '8C', 20, '2026-07-30 13:10:44', '2026-07-30 14:05:42'),
(11, '87087833456789', 'KIRUA 569', '8D', 21, '2026-07-30 13:57:50', '2026-07-30 14:05:42'),
(12, '07989909899', 'FRINKA SIRAIT', '8G', 22, '2026-07-30 14:03:42', '2026-07-30 14:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','guru','siswa','orang_tua') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'siswa',
  `guru_id` bigint UNSIGNED DEFAULT NULL,
  `siswa_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `guru_id`, `siswa_id`, `created_at`, `updated_at`) VALUES
(3, 'admin', '$argon2id$v=19$m=65536,t=4,p=1$a2VxYVY0N0Rqa2QvQ0NUdQ$wrZXm5UxUct+gnJRLVyTKTEg/7RPcLqm+3nCV4Gh+18', 'admin', NULL, NULL, '2026-03-15 18:28:10', '2026-07-24 08:48:49'),
(6, '198501012010011001', '$2y$10$XQR1Ps3ywD4Wq8W8y26EsOvrsFlsk2DS7RD4/W.PGh2rZrJ0lN2yC', 'guru', 1, NULL, '2026-03-15 21:02:29', '2026-07-29 09:28:38'),
(13, '4567896543', '$2y$10$e92Vl7Ujg0R4qy4ry/Qci.r5ikGuiuhLXc1/6c4b3Ny.c9NkNs98u', 'siswa', NULL, 5, '2026-07-22 11:18:23', '2026-07-29 09:28:37'),
(14, 'ORT4567896543', '$2y$10$k1VgTkLrLKLs4Qe5BxJ8meNFlPDqy9DaAO1R4pXrruVvZmMxC7/EK', 'orang_tua', NULL, 5, '2026-07-22 11:18:23', '2026-07-29 09:28:37'),
(16, '1456785', '$2y$10$Wb046tCZuKpnhNNhQ0G39etuaxvD89Qe2WwGmA9dfRfa8cSmyJlKy', 'siswa', NULL, 6, '2026-07-22 11:32:25', '2026-07-29 09:28:37'),
(17, 'ORT1456785', '$2y$10$HIcv0js1xYOofyYz0pzr8ehMULO3AH9Csy/.4RLVSLtdGuLxqNxeO', 'orang_tua', NULL, 6, '2026-07-22 11:32:25', '2026-07-29 09:28:38'),
(18, '5898765', '$2y$10$BNQxprDdcKqgBJcGScluoe55F4eLackaEubsEcbXWmHkSTzpWKeJC', 'siswa', NULL, 7, '2026-07-22 11:32:45', '2026-07-29 09:28:38'),
(19, 'ORT5898765', '$2y$10$W4ZEtK6i8RNLv.Bj3bGAnudzKcKy3FxnWWSHwFvUGsrbHTd1/69LS', 'orang_tua', NULL, 7, '2026-07-22 11:32:45', '2026-07-29 09:28:38'),
(22, '123456789543', '$2y$10$oTMEZZoKfFsbn51HqdiH1eSg51I2jlfhwpmCv9QCcSLQsuCSSXbZ2', 'guru', 4, NULL, '2026-07-25 07:58:47', '2026-07-29 09:28:38'),
(23, '134567898567', '$2y$10$D.TdFX2GYvVTzY1bSZig3eoZRXgkq/y45og9Tv9U/IBaYWIy1dpJ.', 'guru', 5, NULL, '2026-07-25 10:58:05', '2026-07-29 09:28:39'),
(24, '8765567890', '$2y$10$QXGePyfUvxDVD7q35c/C1O3DiIBA9kgQpGI3.NqEs1V50yejFyKnS', 'siswa', NULL, 9, '2026-07-29 08:32:38', '2026-07-29 09:28:38'),
(25, 'ORT8765567890', '$2y$10$D7vid9eLttIDQN22MdwAyO.fuhSUUP/EAyvIFG4s13ewqXtjx9bh2', 'orang_tua', NULL, 9, '2026-07-29 08:32:38', '2026-07-29 09:28:38'),
(26, '654345678987', '$2y$10$6ApBtSWtOTouuAVqqHqEyepBYUK9cFbq5C6qIiA4ZDhUj0O6VePGq', 'guru', 6, NULL, '2026-07-29 09:11:17', '2026-07-29 09:28:39'),
(28, 'admin2', '$2y$10$z117taUboibwQhC51640H.Ei7wZ4aQegNbYMfHPDCyfhHV6v1KoEC', 'admin', NULL, NULL, '2026-07-29 09:17:39', '2026-07-29 09:17:39'),
(29, '654567890', '$2y$10$UINO3mgakyZyFjgUoJhifuoT2ECjKTS5EDOMp9imTyGaOlIX21ETS', 'siswa', NULL, 10, '2026-07-30 13:10:44', '2026-07-30 13:10:44'),
(30, 'ORT654567890', '$2y$10$OfKVi9v67uGjvSFw/.KXA.B/04yi8ZAg4hsC1c/U.75PU1o2qJG2S', 'orang_tua', NULL, 10, '2026-07-30 13:10:44', '2026-07-30 13:10:44'),
(31, '765438909654', '$2y$10$1HHCOVugFbRajOW3Y3GDdOKc27smI96Iug7H9qsKETNYJYqDRxeyK', 'guru', 8, NULL, '2026-07-30 13:11:23', '2026-07-30 13:11:23'),
(32, '9875445678909', '$2y$10$cQz7y83FTTIJKy6l2st2LeQWETA9v/Aj.gK2vffSfnfZTcEbt9TIC', 'guru', 9, NULL, '2026-07-30 13:57:31', '2026-07-30 13:57:31'),
(33, '87087833456789', '$2y$10$DmsvYoruMjvFN3uuN42r0Ozndt56rsHYg6DHCwkflv58pQbhswfyG', 'siswa', NULL, 11, '2026-07-30 13:57:50', '2026-07-30 13:57:50'),
(34, 'ORT87087833456789', '$2y$10$td1v436Le0RmzRCgH8I93.Gcfou6vj4t9rPTfen94NTSxSRahq6Je', 'orang_tua', NULL, 11, '2026-07-30 13:57:50', '2026-07-30 13:57:50'),
(35, '07989909899', '$2y$10$pmr7TNLqvhTykIuThuYcieXHEyinn/bGAXBYPxy5VSoRqhB7XNuam', 'siswa', NULL, 12, '2026-07-30 14:03:42', '2026-07-30 14:03:42'),
(36, 'ORT07989909899', '$2y$10$8dZJSuPnoIDMEyRGh7U1de2mSJ.nislSq/leHFwkpYn6lGtH5YKzi', 'orang_tua', NULL, 12, '2026-07-30 14:03:43', '2026-07-30 14:03:43'),
(37, '169028735822', '$2y$10$2XXH.e.ACeuWXRZ9wEnG9.SP7nHxbTbg910iEmTy9QNKY9wd0u3Xe', 'guru', 10, NULL, '2026-07-30 14:04:08', '2026-07-30 14:04:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `foto_slideshow`
--
ALTER TABLE `foto_slideshow`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kaih_kelas`
--
ALTER TABLE `kaih_kelas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laporan_siswa_id_foreign` (`siswa_id`),
  ADD KEY `laporan_guru_id_foreign` (`guru_id`);

--
-- Indexes for table `laporan_harian`
--
ALTER TABLE `laporan_harian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_siswa_tanggal` (`siswa_id`,`tanggal`),
  ADD KEY `idx_tanggal` (`tanggal`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_wali_kelas_id_foreign` (`wali_kelas_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `users_guru_id_foreign` (`guru_id`),
  ADD KEY `users_siswa_id_foreign` (`siswa_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `foto_slideshow`
--
ALTER TABLE `foto_slideshow`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `guru`
--
ALTER TABLE `guru`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `kaih_kelas`
--
ALTER TABLE `kaih_kelas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laporan_harian`
--
ALTER TABLE `laporan_harian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `laporan`
--
ALTER TABLE `laporan`
  ADD CONSTRAINT `laporan_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `laporan_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_wali_kelas_id_foreign` FOREIGN KEY (`wali_kelas_id`) REFERENCES `kaih_kelas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `users_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

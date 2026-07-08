
CREATE DATABASE IF NOT EXISTS `kaih` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `kaih`;

-- Dumping structure for table kaih.cache_locks
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table kaih.cache_locks: ~0 rows (approximately)

-- Dumping structure for table kaih.guru
CREATE TABLE IF NOT EXISTS `guru` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nip` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_guru` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `no_hp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table kaih.guru: ~0 rows (approximately)
INSERT INTO `guru` (`id`, `nip`, `nama_guru`, `kelas`, `alamat`, `no_hp`, `jabatan`, `created_at`, `updated_at`) VALUES
	(1, '198501012010011001', 'Siti Rahayu,Gr.S.Pd', 'Kelas 7A', 'Jl. Merdeka No. 10, Jakarta', '812345678', 'Guru B.Indonesia', '2026-03-16 03:13:54', '2026-03-16 08:25:59');

-- Dumping structure for table kaih.kaih_kelas
CREATE TABLE IF NOT EXISTS `kaih_kelas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table kaih.kaih_kelas: ~0 rows (approximately)
INSERT INTO `kaih_kelas` (`id`, `nama_kelas`, `created_at`, `updated_at`) VALUES
	(1, 'Kelas 7A', '2026-03-16 01:53:27', '2026-03-16 01:53:27'),
	(2, 'Kelas 7B', '2026-03-16 01:53:27', '2026-03-16 01:53:27'),
	(3, 'Kelas 8A', '2026-03-16 01:53:27', '2026-03-16 01:53:27'),
	(4, 'Kelas 8B', '2026-03-16 01:53:27', '2026-03-16 01:53:27'),
	(5, 'Kelas 9A', '2026-03-16 01:53:27', '2026-03-16 01:53:27'),
	(6, 'Kelas 9B', '2026-03-16 01:53:27', '2026-03-16 01:53:27'),
	(7, 'Kelas 10A', '2026-03-16 03:50:21', '2026-03-16 03:50:21'),
	(8, 'Kelas 7C', '2026-03-16 03:50:52', '2026-03-16 03:50:52');

-- Dumping structure for table kaih.laporan
CREATE TABLE IF NOT EXISTS `laporan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `siswa_id` bigint unsigned NOT NULL,
  `guru_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `kategori` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `laporan_siswa_id_foreign` (`siswa_id`),
  KEY `laporan_guru_id_foreign` (`guru_id`),
  CONSTRAINT `laporan_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `laporan_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table kaih.laporan: ~0 rows (approximately)

-- Dumping structure for table kaih.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table kaih.migrations: ~0 rows (approximately)

-- Dumping structure for table kaih.siswa
CREATE TABLE IF NOT EXISTS `siswa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nisn` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_siswa` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wali_kelas_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `siswa_wali_kelas_id_foreign` (`wali_kelas_id`),
  CONSTRAINT `siswa_wali_kelas_id_foreign` FOREIGN KEY (`wali_kelas_id`) REFERENCES `kaih_kelas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table kaih.siswa: ~0 rows (approximately)
INSERT INTO `siswa` (`id`, `nisn`, `nama_siswa`, `kelas`, `wali_kelas_id`, `created_at`, `updated_at`) VALUES
	(2, '1234567890', 'Budi Santoso', 'Kelas 7A', 1, '2026-03-16 07:47:09', '2026-03-16 07:47:09');

-- Dumping structure for table kaih.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','guru','siswa','orang_tua') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'siswa',
  `guru_id` bigint unsigned DEFAULT NULL,
  `siswa_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `users_guru_id_foreign` (`guru_id`),
  KEY `users_siswa_id_foreign` (`siswa_id`),
  CONSTRAINT `users_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `users_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table kaih.users: ~0 rows (approximately)
INSERT INTO `users` (`id`, `username`, `password`, `role`, `guru_id`, `siswa_id`, `created_at`, `updated_at`) VALUES
	(3, 'admin', '$2y$10$PROJzyFfja6R1YgltZgbqOGK4NsQpn0t7lFrEC46QgrjDYbQNxSy.', 'admin', NULL, NULL, '2026-03-16 02:28:10', '2026-03-16 02:28:10'),
	(6, '198501012010011001', '$2y$10$4WkwD0rrKgj./FOBBNaxPuhF6l2e0FJJF.hb.uFZ8RCrw8kymVOx6', 'guru', 1, NULL, '2026-03-16 05:02:29', '2026-03-16 08:25:59'),
	(7, '1234567890', '$2y$10$qkE/gHCZQuvCGZZzR1thSOwkygOyIIYZW2iJV8NQ1TwoLgk75jG8.', 'siswa', NULL, 2, '2026-03-16 07:47:09', '2026-03-16 07:47:09'),
	(8, 'ORT1234567890', '$2y$10$J8XgTupnMrPE8Dl5uFkkwuva9msJ4RGXFqK.Kr5jp5g1Ml9RmI.eC', 'orang_tua', NULL, 2, '2026-03-16 07:47:09', '2026-03-16 07:47:09');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

-- Migration for Buku Tamu
CREATE TABLE IF NOT EXISTS `buku_tamu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `instansi` varchar(150) NOT NULL,
  `kontak` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tujuan_bertemu` varchar(150) NOT NULL,
  `keperluan` text NOT NULL,
  `tanggal_kunjungan` date NOT NULL,
  `jam_kunjungan` time DEFAULT NULL,
  `jumlah_orang` int DEFAULT 1,
  `status` enum('menunggu', 'diterima', 'selesai') DEFAULT 'menunggu',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

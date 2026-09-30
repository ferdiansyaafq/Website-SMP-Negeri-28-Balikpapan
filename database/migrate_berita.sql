USE `kaih`;

CREATE TABLE IF NOT EXISTS `berita` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jenis` enum('berita','pengumuman') NOT NULL DEFAULT 'berita',
  `tanggal` date NOT NULL,
  `kategori` varchar(50) NOT NULL DEFAULT 'Umum',
  `judul` varchar(255) NOT NULL,
  `ringkasan` varchar(500) NOT NULL,
  `isi` text NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` enum('draft','terbit') NOT NULL DEFAULT 'terbit',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_berita_tanggal_judul` (`tanggal`, `judul`),
  KEY `idx_berita_publikasi` (`status`, `jenis`, `tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `berita` (`jenis`, `tanggal`, `kategori`, `judul`, `ringkasan`, `isi`) VALUES
  ('pengumuman', '2026-07-10', 'Pengumuman', 'PPDB Tahun Ajaran 2026/2027 Resmi Dibuka', 'Penerimaan Peserta Didik Baru untuk tahun ajaran 2026/2027 telah resmi dibuka.', 'Penerimaan Peserta Didik Baru (PPDB) Tahun Ajaran 2026/2027 telah resmi dibuka. Informasi jadwal, persyaratan, dan tahapan pendaftaran dapat diperoleh melalui kanal resmi sekolah.'),
  ('berita', '2026-07-05', 'Akademik', 'Implementasi Kurikulum Merdeka dengan Deep Learning', 'SMPN 28 Balikpapan resmi menerapkan pendekatan Pembelajaran Mendalam untuk meningkatkan kualitas pendidikan.', 'SMP Negeri 28 Balikpapan menerapkan pendekatan Pembelajaran Mendalam untuk memperkuat proses belajar yang bermakna, kontekstual, dan berpusat pada siswa.'),
  ('berita', '2026-07-01', 'Prestasi', 'Program 7 Kebiasaan Anak Indonesia Hebat Diluncurkan', 'Program KAIH resmi diluncurkan untuk membentuk karakter dan kebiasaan positif siswa setiap hari.', 'Program 7 Kebiasaan Anak Indonesia Hebat (KAIH) diluncurkan sebagai bagian dari pembiasaan positif siswa di lingkungan sekolah dan keluarga.');
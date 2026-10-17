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

INSERT IGNORE INTO `berita` (`jenis`, `tanggal`, `kategori`, `judul`, `ringkasan`, `isi`, `gambar`) VALUES
  ('pengumuman', '2026-07-10', 'Pengumuman', 'PPDB Tahun Ajaran 2026/2027 Resmi Dibuka', 'Penerimaan Peserta Didik Baru untuk tahun ajaran 2026/2027 telah resmi dibuka.', 'Penerimaan Peserta Didik Baru (PPDB) Tahun Ajaran 2026/2027 telah resmi dibuka. Informasi jadwal, persyaratan, dan tahapan pendaftaran dapat diperoleh melalui kanal resmi sekolah.', 'assets/img/berita/berita1.png'),
  ('berita', '2026-07-05', 'Akademik', 'Implementasi Kurikulum Merdeka dengan Deep Learning', 'SMPN 28 Balikpapan resmi menerapkan pendekatan Pembelajaran Mendalam untuk meningkatkan kualitas pendidikan.', 'SMP Negeri 28 Balikpapan menerapkan pendekatan Pembelajaran Mendalam untuk memperkuat proses belajar yang bermakna, kontekstual, dan berpusat pada siswa.', 'assets/img/berita/berita2.jpg'),
  ('berita', '2026-07-01', 'Prestasi', 'Program 7 Kebiasaan Anak Indonesia Hebat Diluncurkan', 'Program KAIH resmi diluncurkan untuk membentuk karakter dan kebiasaan positif siswa setiap hari.', 'Program 7 Kebiasaan Anak Indonesia Hebat (KAIH) diluncurkan sebagai bagian dari pembiasaan positif siswa di lingkungan sekolah dan keluarga.', 'assets/img/berita/berita3.png');

UPDATE `berita`
SET `gambar` = 'assets/img/berita/berita1.png'
WHERE `judul` = 'PPDB Tahun Ajaran 2026/2027 Resmi Dibuka';

UPDATE `berita`
SET `gambar` = 'assets/img/berita/berita2.jpg'
WHERE `judul` = 'Implementasi Kurikulum Merdeka dengan Deep Learning';

UPDATE `berita`
SET `gambar` = 'assets/img/berita/berita3.png'
WHERE `judul` = 'Program 7 Kebiasaan Anak Indonesia Hebat Diluncurkan';

INSERT INTO `berita` (`jenis`, `tanggal`, `kategori`, `judul`, `ringkasan`, `isi`, `gambar`, `status`)
SELECT 'berita', CURRENT_DATE, 'Prestasi', 'Roza Aghata Raih Prestasi Karate INKANAS',
       'Roza Aghata meraih Juara 1 Kata Regu Pemula Putra dan Juara 3 Kata Perorangan Putra Pemula pada Kejuaraan Karate INKANAS Kalimantan Timur.',
       'Siswa SMP Negeri 28 Balikpapan, Roza Aghata, meraih Juara 1 Kata Regu Pemula Putra dan Juara 3 Kata Perorangan Putra Pemula pada Kejuaraan Karate INKANAS Kalimantan Timur. Selamat atas prestasi yang diraih. Semoga pencapaian ini menginspirasi siswa untuk terus berlatih, disiplin, dan mengembangkan potensi.',
       'assets/img/prestasi/prestasi1.png', 'terbit'
WHERE NOT EXISTS (
    SELECT 1 FROM `berita` WHERE `judul` = 'Roza Aghata Raih Prestasi Karate INKANAS'
);

INSERT INTO `berita` (`jenis`, `tanggal`, `kategori`, `judul`, `ringkasan`, `isi`, `gambar`, `status`)
SELECT 'berita', CURRENT_DATE, 'Kegiatan', 'Kolaborasi Inovasi Sosial bersama Institut Teknologi Kalimantan',
       'SMP Negeri 28 Balikpapan berkolaborasi dengan Institut Teknologi Kalimantan dalam kegiatan inovasi sosial bersama warga sekolah.',
       'SMP Negeri 28 Balikpapan berkolaborasi dengan Institut Teknologi Kalimantan dalam kegiatan inovasi sosial yang melibatkan warga sekolah. Kolaborasi ini menjadi ruang untuk berbagi gagasan dan mendorong kontribusi positif bagi lingkungan sekolah.',
       'assets/img/berita/inovasisosial.jpg', 'terbit'
WHERE NOT EXISTS (
    SELECT 1 FROM `berita` WHERE `judul` = 'Kolaborasi Inovasi Sosial bersama Institut Teknologi Kalimantan'
);

INSERT INTO `berita` (`jenis`, `tanggal`, `kategori`, `judul`, `ringkasan`, `isi`, `gambar`, `status`)
SELECT 'berita', CURRENT_DATE, 'Kegiatan', 'Masa Pengenalan Lingkungan Sekolah (MPLS)',
       'SMP Negeri 28 Balikpapan menyambut peserta didik baru melalui kegiatan Masa Pengenalan Lingkungan Sekolah.',
       'Kegiatan Masa Pengenalan Lingkungan Sekolah (MPLS) menjadi kesempatan bagi peserta didik baru untuk mengenal lingkungan, warga, dan budaya sekolah. Selamat bergabung dan memulai perjalanan belajar di SMP Negeri 28 Balikpapan.',
       'assets/img/berita/mpls.jpg', 'terbit'
WHERE NOT EXISTS (
    SELECT 1 FROM `berita` WHERE `judul` = 'Masa Pengenalan Lingkungan Sekolah (MPLS)'
);
CREATE DATABASE IF NOT EXISTS `kaih`;
USE `kaih`;

-- 1.3 Kaih Kelas (Master Kelas)
CREATE TABLE `kaih_kelas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tingkat` enum('7','8','9') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wali_kelas_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nama_kelas` (`nama_kelas`),
  KEY `kaih_kelas_wali_kelas_id_foreign` (`wali_kelas_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.4 Guru (Master Guru)
CREATE TABLE `guru` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nip` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_guru` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_kelamin` enum('L','P') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `no_hp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nip` (`nip`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.5 Foto Slideshow (Beranda)
CREATE TABLE `foto_slideshow` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `urutan` int NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.1 Siswa (Merujuk ke kaih_kelas)
CREATE TABLE `siswa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nisn` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_siswa` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_kelamin` enum('L','P') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wali_kelas_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nisn` (`nisn`),
  KEY `siswa_wali_kelas_id_foreign` (`wali_kelas_id`),
  CONSTRAINT `siswa_wali_kelas_id_foreign` 
    FOREIGN KEY (`wali_kelas_id`) REFERENCES `kaih_kelas` (`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2 Users (Merujuk ke guru & siswa)
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','guru','siswa','orang_tua') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'siswa',
  `guru_id` bigint unsigned DEFAULT NULL,
  `siswa_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`),
  KEY `users_guru_id_foreign` (`guru_id`),
  KEY `users_siswa_id_foreign` (`siswa_id`),
  CONSTRAINT `users_guru_id_foreign` 
    FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `users_siswa_id_foreign` 
    FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.3 Absensi
CREATE TABLE `absensi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `siswa_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('hadir','izin','sakit','alpha') COLLATE utf8mb4_unicode_ci DEFAULT 'hadir',
  `deskripsi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Sesi kelas reguler',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_siswa_tanggal` (`siswa_id`, `tanggal`),
  KEY `absensi_siswa_id_foreign` (`siswa_id`),
  CONSTRAINT `absensi_siswa_id_foreign` 
    FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.4 Laporan (Guru ke Siswa)
CREATE TABLE `laporan` (
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
  CONSTRAINT `laporan_siswa_id_foreign` 
    FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `laporan_guru_id_foreign` 
    FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.5 Laporan Harian / KAIH
CREATE TABLE `laporan_harian` (
  `id` int NOT NULL AUTO_INCREMENT,
  `siswa_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `bangun` tinyint(1) NOT NULL DEFAULT 0,
  `bangun_keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ibadah` tinyint(1) NOT NULL DEFAULT 0,
  `ibadah_catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `olahraga` tinyint(1) NOT NULL DEFAULT 0,
  `olahraga_jenis` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sarapan` tinyint(1) NOT NULL DEFAULT 0,
  `sarapan_menu` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `membaca` tinyint(1) NOT NULL DEFAULT 0,
  `membaca_judul` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `membaca_menit` int DEFAULT NULL,
  `membantu` tinyint(1) NOT NULL DEFAULT 0,
  `membantu_jenis` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `menabung` tinyint(1) NOT NULL DEFAULT 0,
  `menabung_keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `menabung_nominal` int DEFAULT NULL,
  `orang_tua_validated_at` datetime DEFAULT NULL,
  `guru_validated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_siswa_tanggal` (`siswa_id`, `tanggal`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `laporan_harian_siswa_id_foreign` (`siswa_id`),
  CONSTRAINT `laporan_harian_siswa_id_foreign` 
    FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.6 Refleksi
CREATE TABLE `refleksi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `siswa_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `semester` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ganjil',
  `tahun_ajaran` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pelajaran_favorit` text COLLATE utf8mb4_unicode_ci,
  `pelajaran_sulit` text COLLATE utf8mb4_unicode_ci,
  `pencapaian` text COLLATE utf8mb4_unicode_ci,
  `kendala` text COLLATE utf8mb4_unicode_ci,
  `pengalaman_berkesan` text COLLATE utf8mb4_unicode_ci,
  `saran` text COLLATE utf8mb4_unicode_ci,
  `target_kedepan` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_siswa_tanggal` (`siswa_id`, `tanggal`),
  CONSTRAINT `refleksi_siswa_id_foreign` 
    FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.7 Laporan Bullying
CREATE TABLE `laporan_bullying` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_pelapor` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kontak` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_pelapor` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_korban` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas_korban` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_bullying` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_kejadian` date NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lokasi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `saksi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('baru','diproses','selesai') COLLATE utf8mb4_unicode_ci DEFAULT 'baru',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.8 Survei Pelayanan
CREATE TABLE `survei` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_pengisi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `peran` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `ulasan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.9 Berita dan Pengumuman
CREATE TABLE `berita` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jenis` enum('berita','pengumuman') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'berita',
  `tanggal` date NOT NULL,
  `kategori` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Umum',
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ringkasan` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `gambar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','terbit') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'terbit',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_berita_tanggal_judul` (`tanggal`, `judul`),
  KEY `idx_berita_publikasi` (`status`, `jenis`, `tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. DATA SAMPLE
-- ============================================================

CREATE TABLE `jurnal_kelas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kelas_id` bigint unsigned NOT NULL,
  `guru_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `mata_pelajaran` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `materi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah_hadir` int DEFAULT 0,
  `jumlah_tidak_hadir` int DEFAULT 0,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `jurnal_kelas_kelas_id_foreign` (`kelas_id`),
  KEY `jurnal_kelas_guru_id_foreign` (`guru_id`),
  KEY `idx_tanggal` (`tanggal`),
  CONSTRAINT `jurnal_kelas_kelas_id_foreign` 
    FOREIGN KEY (`kelas_id`) REFERENCES `kaih_kelas` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `jurnal_kelas_guru_id_foreign` 
    FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2.11 JURNAL GURU
-- ============================================================
CREATE TABLE `jurnal_guru` (
  `id` int NOT NULL AUTO_INCREMENT,
  `guru_id` bigint unsigned NOT NULL,
  `kelas_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `mata_pelajaran` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `materi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `metode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jumlah_hadir` int DEFAULT 0,
  `jumlah_tidak_hadir` int DEFAULT 0,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `jurnal_guru_guru_id_foreign` (`guru_id`),
  KEY `jurnal_guru_kelas_id_foreign` (`kelas_id`),
  KEY `idx_tanggal` (`tanggal`),
  CONSTRAINT `jurnal_guru_guru_id_foreign` 
    FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `jurnal_guru_kelas_id_foreign` 
    FOREIGN KEY (`kelas_id`) REFERENCES `kaih_kelas` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2.12 KEPUASAN MASYARAKAT & SISWA
-- ============================================================
CREATE TABLE `kepuasan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_pengisi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `peran` enum('Siswa','Orang Tua','Guru','Masyarakat') COLLATE utf8mb4_unicode_ci NOT NULL,
  `aspek` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `suka` enum('Suka','Tidak Suka') COLLATE utf8mb4_unicode_ci NOT NULL,
  `alasan_suka` text COLLATE utf8mb4_unicode_ci,
  `alasan_tidak_suka` text COLLATE utf8mb4_unicode_ci,
  `saran` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_peran` (`peran`),
  KEY `idx_rating` (`rating`),
  KEY `idx_suka` (`suka`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `berita` (`jenis`, `tanggal`, `kategori`, `judul`, `ringkasan`, `isi`) VALUES
  ('pengumuman', '2026-07-10', 'Pengumuman', 'PPDB Tahun Ajaran 2026/2027 Resmi Dibuka', 'Penerimaan Peserta Didik Baru untuk tahun ajaran 2026/2027 telah resmi dibuka.', 'Penerimaan Peserta Didik Baru (PPDB) Tahun Ajaran 2026/2027 telah resmi dibuka. Informasi jadwal, persyaratan, dan tahapan pendaftaran dapat diperoleh melalui kanal resmi sekolah.'),
  ('berita', '2026-07-05', 'Akademik', 'Implementasi Kurikulum Merdeka dengan Deep Learning', 'SMPN 28 Balikpapan resmi menerapkan pendekatan Pembelajaran Mendalam untuk meningkatkan kualitas pendidikan.', 'SMP Negeri 28 Balikpapan menerapkan pendekatan Pembelajaran Mendalam untuk memperkuat proses belajar yang bermakna, kontekstual, dan berpusat pada siswa.'),
  ('berita', '2026-07-01', 'Prestasi', 'Program 7 Kebiasaan Anak Indonesia Hebat Diluncurkan', 'Program KAIH resmi diluncurkan untuk membentuk karakter dan kebiasaan positif siswa setiap hari.', 'Program 7 Kebiasaan Anak Indonesia Hebat (KAIH) diluncurkan sebagai bagian dari pembiasaan positif siswa di lingkungan sekolah dan keluarga.');

-- Kaih Kelas
INSERT INTO `kaih_kelas` (`id`, `nama_kelas`, `tingkat`, `wali_kelas_id`, `created_at`, `updated_at`) VALUES
  (1, 'Kelas 7A', '7', 1, '2026-03-16 01:53:27', '2026-03-16 01:53:27'),
  (2, 'Kelas 7B', '7', 2, '2026-03-16 01:53:27', '2026-03-16 01:53:27'),
  (3, 'Kelas 8A', '8', NULL, '2026-03-16 01:53:27', '2026-03-16 01:53:27'),
  (4, 'Kelas 8B', '8', NULL, '2026-03-16 01:53:27', '2026-03-16 01:53:27'),
  (5, 'Kelas 9A', '9', NULL, '2026-03-16 01:53:27', '2026-03-16 01:53:27'),
  (6, 'Kelas 9B', '9', NULL, '2026-03-16 01:53:27', '2026-03-16 01:53:27');

-- Guru
INSERT INTO `guru` (`id`, `nip`, `nama_guru`, `kelas`, `jenis_kelamin`, `alamat`, `no_hp`, `jabatan`, `created_at`, `updated_at`) VALUES
  (1, '198501012010011001', 'Siti Rahayu, Gr.S.Pd', 'Kelas 7A', 'P', 'Jl. Merdeka No. 10, Balikpapan', '081234567890', 'Guru B. Indonesia', '2026-03-16 03:13:54', '2026-03-16 08:25:59'),
  (2, '198702022010012002', 'Ahmad Wijaya, S.Pd', 'Kelas 7B', 'L', 'Jl. Manggar Baru No. 5, Balikpapan', '081234567891', 'Guru Matematika', '2026-03-16 03:13:54', '2026-03-16 08:25:59');

-- Update wali_kelas_id di kaih_kelas
UPDATE `kaih_kelas` SET `wali_kelas_id` = 1 WHERE `id` = 1;

-- Siswa
INSERT INTO `siswa` (`id`, `nisn`, `nama_siswa`, `kelas`, `jenis_kelamin`, `wali_kelas_id`, `created_at`, `updated_at`) VALUES
  (1, '1234567890', 'Reyhan Adi Wijaya', 'Kelas 7A', 'L', 1, '2026-03-16 07:47:09', '2026-03-16 07:47:09'),
  (2, '1234567891', 'Reina Nur Aulia', 'Kelas 7A', 'P', 1, '2026-03-16 07:47:09', '2026-03-16 07:47:09'),
  (3, '1234567892', 'Andi Pratama', 'Kelas 7B', 'L', 2, '2026-03-16 07:47:09', '2026-03-16 07:47:09');

-- Users
INSERT INTO `users` (`id`, `username`, `password`, `role`, `guru_id`, `siswa_id`, `created_at`, `updated_at`) VALUES
  (1, 'admin', '$2y$10$PROJzyFfja6R1YgltZgbqOGK4NsQpn0t7lFrEC46QgrjDYbQNxSy.', 'admin', NULL, NULL, '2026-03-16 02:28:10', '2026-03-16 02:28:10'),
  (2, '198501012010011001', '$2y$12$yJawyd6W0GufM59WUjndieyWdy0vvcvsVitbWcqYGDWA7boaWTacq', 'guru', 1, NULL, '2026-03-16 05:02:29', '2026-03-16 08:25:59'),
  (3, '198702022010012002', '$2y$12$yJawyd6W0GufM59WUjndieyWdy0vvcvsVitbWcqYGDWA7boaWTacq', 'guru', 2, NULL, '2026-03-16 05:02:29', '2026-03-16 08:25:59'),
  (4, '1234567890', '$2y$10$qkE/gHCZQuvCGZZzR1thSOwkygOyIIYZW2iJV8NQ1TwoLgk75jG8.', 'siswa', NULL, 1, '2026-03-16 07:47:09', '2026-03-16 07:47:09'),
  (5, '1234567891', '$2y$10$qkE/gHCZQuvCGZZzR1thSOwkygOyIIYZW2iJV8NQ1TwoLgk75jG8.', 'siswa', NULL, 2, '2026-03-16 07:47:09', '2026-03-16 07:47:09'),
  (6, 'ORT1234567890', '$2y$10$J8XgTupnMrPE8Dl5uFkkwuva9msJ4RGXFqK.Kr5jp5g1Ml9RmI.eC', 'orang_tua', NULL, 1, '2026-03-16 07:47:09', '2026-03-16 07:47:09'),
  (7, 'ORT1234567891', '$2y$10$J8XgTupnMrPE8Dl5uFkkwuva9msJ4RGXFqK.Kr5jp5g1Ml9RmI.eC', 'orang_tua', NULL, 2, '2026-03-16 07:47:09', '2026-03-16 07:47:09');

-- Absensi Sample
INSERT INTO `absensi` (`siswa_id`, `tanggal`, `status`, `deskripsi`, `catatan`) VALUES
  (1, '2026-09-01', 'hadir', 'Sesi kelas reguler', 'Presensi mandiri'),
  (1, '2026-09-02', 'hadir', 'Sesi kelas reguler', 'Presensi mandiri'),
  (1, '2026-09-03', 'hadir', 'Sesi kelas reguler', 'Presensi mandiri'),
  (2, '2026-09-01', 'hadir', 'Sesi kelas reguler', 'Presensi mandiri'),
  (2, '2026-09-02', 'sakit', 'Sesi kelas reguler', 'Surat dokter');

-- ============================================================
-- 4. VIEW UNTUK LAPORAN
-- ============================================================

CREATE OR REPLACE VIEW `v_rekap_absensi` AS
SELECT 
    s.id AS siswa_id,
    s.nama_siswa,
    k.nama_kelas,
    COUNT(a.id) AS total_absensi,
    SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) AS total_hadir,
    SUM(CASE WHEN a.status = 'alpha' THEN 1 ELSE 0 END) AS total_alpha,
    SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) AS total_izin,
    SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) AS total_sakit
FROM siswa s
LEFT JOIN kaih_kelas k ON s.wali_kelas_id = k.id
LEFT JOIN absensi a ON s.id = a.siswa_id
GROUP BY s.id, s.nama_siswa, k.nama_kelas;

CREATE OR REPLACE VIEW `v_rekap_kaih` AS
SELECT 
    s.id AS siswa_id,
    s.nama_siswa,
    k.nama_kelas,
    COUNT(lh.id) AS total_hari,
    SUM(lh.bangun + lh.ibadah + lh.olahraga + lh.sarapan + lh.membaca + lh.membantu + lh.menabung) AS total_kebiasaan
FROM siswa s
LEFT JOIN kaih_kelas k ON s.wali_kelas_id = k.id
LEFT JOIN laporan_harian lh ON s.id = lh.siswa_id
GROUP BY s.id, s.nama_siswa, k.nama_kelas;

TRUNCATE TABLE `berita`;

INSERT INTO `berita` 
  (`jenis`, `tanggal`, `kategori`, `judul`, `ringkasan`, `isi`, `gambar`, `status`) 
VALUES
  ('pengumuman', '2026-07-10', 'Pengumuman',
   'PPDB Tahun Ajaran 2026/2027 Resmi Dibuka',
   'Penerimaan Peserta Didik Baru untuk tahun ajaran 2026/2027 telah resmi dibuka.',
   'Penerimaan Peserta Didik Baru (PPDB) Tahun Ajaran 2026/2027 telah resmi dibuka. Informasi jadwal, persyaratan, dan tahapan pendaftaran dapat diperoleh melalui kanal resmi sekolah atau datang langsung ke SMP Negeri 28 Balikpapan.',
   'assets/img/berita1.png',
   'terbit'),

  ('berita', '2026-07-05', 'Akademik',
   'Implementasi Kurikulum Merdeka dengan Deep Learning',
   'SMPN 28 Balikpapan resmi menerapkan pendekatan Pembelajaran Mendalam untuk meningkatkan kualitas pendidikan.',
   'SMP Negeri 28 Balikpapan menerapkan pendekatan Pembelajaran Mendalam (Deep Learning) untuk memperkuat proses belajar yang bermakna, kontekstual, dan berpusat pada siswa.',
   'assets/img/berita2.jpg',
   'terbit'),

  ('berita', '2026-07-01', 'Prestasi',
   'Program 7 Kebiasaan Anak Indonesia Hebat Diluncurkan',
   'Program KAIH resmi diluncurkan untuk membentuk karakter dan kebiasaan positif siswa setiap hari.',
   'Program 7 Kebiasaan Anak Indonesia Hebat (KAIH) diluncurkan sebagai bagian dari pembiasaan positif siswa di lingkungan sekolah dan keluarga.',
   'assets/img/berita3.png',
   'terbit');
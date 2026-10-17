SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET NAMES utf8mb4;

CREATE TABLE `absensi` (
  `id` int NOT NULL,
  `siswa_id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('hadir','izin','sakit','alpha') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'hadir',
  `deskripsi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Sesi kelas reguler',
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `absensi` (`id`, `siswa_id`, `tanggal`, `status`, `deskripsi`, `catatan`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-09-01', 'hadir', 'Sesi kelas reguler', 'Presensi mandiri', '2026-10-01 11:01:17', '2026-10-01 11:01:17'),
(2, 1, '2026-09-02', 'hadir', 'Sesi kelas reguler', 'Presensi mandiri', '2026-10-01 11:01:17', '2026-10-01 11:01:17'),
(3, 1, '2026-09-03', 'hadir', 'Sesi kelas reguler', 'Presensi mandiri', '2026-10-01 11:01:17', '2026-10-01 11:01:17'),
(4, 2, '2026-09-01', 'hadir', 'Sesi kelas reguler', 'Presensi mandiri', '2026-10-01 11:01:17', '2026-10-01 11:01:17'),
(5, 2, '2026-09-02', 'sakit', 'Sesi kelas reguler', 'Surat dokter', '2026-10-01 11:01:17', '2026-10-01 11:01:17'),
(6, 1, '2026-10-01', 'alpha', 'Sesi kelas reguler', 'Tidak absen (otomatis)', '2026-10-07 21:09:05', '2026-10-07 21:09:05'),
(7, 1, '2026-10-02', 'alpha', 'Sesi kelas reguler', 'Tidak absen (otomatis)', '2026-10-07 21:09:05', '2026-10-07 21:09:05'),
(8, 1, '2026-10-05', 'alpha', 'Sesi kelas reguler', 'Tidak absen (otomatis)', '2026-10-07 21:09:05', '2026-10-07 21:09:05'),
(9, 1, '2026-10-06', 'alpha', 'Sesi kelas reguler', 'Tidak absen (otomatis)', '2026-10-07 21:09:05', '2026-10-07 21:09:05');

CREATE TABLE `berita` (
  `id` int NOT NULL,
  `jenis` enum('berita','pengumuman') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'berita',
  `tanggal` date NOT NULL,
  `kategori` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Umum',
  `judul` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ringkasan` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `isi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gambar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','terbit') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'terbit',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `berita` (`id`, `jenis`, `tanggal`, `kategori`, `judul`, `ringkasan`, `isi`, `gambar`, `status`, `created_at`, `updated_at`) VALUES
(1, 'pengumuman', '2026-07-10', 'Pengumuman', 'PPDB Tahun Ajaran 2026/2027 Resmi Dibuka', 'Penerimaan Peserta Didik Baru untuk tahun ajaran 2026/2027 telah resmi dibuka.', 'Penerimaan Peserta Didik Baru (PPDB) Tahun Ajaran 2026/2027 telah resmi dibuka. Informasi jadwal, persyaratan, dan tahapan pendaftaran dapat diperoleh melalui kanal resmi sekolah atau datang langsung ke SMP Negeri 28 Balikpapan.', 'assets/img/berita1.png', 'terbit', '2026-10-01 11:01:18', '2026-10-01 11:01:18'),
(2, 'berita', '2026-07-05', 'Akademik', 'Implementasi Kurikulum Merdeka dengan Deep Learning', 'SMPN 28 Balikpapan resmi menerapkan pendekatan Pembelajaran Mendalam untuk meningkatkan kualitas pendidikan.', 'SMP Negeri 28 Balikpapan menerapkan pendekatan Pembelajaran Mendalam (Deep Learning) untuk memperkuat proses belajar yang bermakna, kontekstual, dan berpusat pada siswa.', 'assets/img/berita2.jpg', 'terbit', '2026-10-01 11:01:18', '2026-10-01 11:01:18'),
(3, 'berita', '2026-07-01', 'Prestasi', 'Program 7 Kebiasaan Anak Indonesia Hebat Diluncurkan', 'Program KAIH resmi diluncurkan untuk membentuk karakter dan kebiasaan positif siswa setiap hari.', 'Program 7 Kebiasaan Anak Indonesia Hebat (KAIH) diluncurkan sebagai bagian dari pembiasaan positif siswa di lingkungan sekolah dan keluarga.', 'assets/img/berita3.png', 'terbit', '2026-10-01 11:01:18', '2026-10-01 11:01:18');

CREATE TABLE `buku_tamu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `instansi` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kontak` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tujuan_bertemu` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `keperluan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_kunjungan` date NOT NULL,
  `jam_kunjungan` time DEFAULT NULL,
  `jumlah_orang` int DEFAULT '1',
  `status` enum('menunggu','diterima','selesai') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'menunggu',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `foto_slideshow` (
  `id` int NOT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `judul` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `urutan` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `guru` (
  `id` bigint UNSIGNED NOT NULL,
  `nip` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_guru` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_kelamin` enum('L','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `no_hp` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `guru` (`id`, `nip`, `nama_guru`, `kelas`, `jenis_kelamin`, `alamat`, `no_hp`, `jabatan`, `created_at`, `updated_at`) VALUES
(1, '198501012010011001', 'Siti Rahayu, Gr.S.Pd', 'Kelas 7A', 'P', 'Jl. Merdeka No. 10, Balikpapan', '081234567890', 'Guru B. Indonesia', '2026-03-15 19:13:54', '2026-03-16 00:25:59'),
(2, '198702022010012002', 'Ahmad Wijaya, S.Pd', 'Kelas 7B', 'L', 'Jl. Manggar Baru No. 5, Balikpapan', '081234567891', 'Guru Matematika', '2026-03-15 19:13:54', '2026-03-16 00:25:59');

CREATE TABLE `jendela_literasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `penulis` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `penerbit` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tahun_terbit` YEAR DEFAULT NULL,
  `kategori` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Umum',
  `sinopsis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_baca` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','terbit') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'terbit',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `jendela_literasi` (`id`, `judul`, `penulis`, `penerbit`, `tahun_terbit`, `kategori`, `sinopsis`, `cover`, `link_baca`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', '2005', 'Novel Inspiratif', 'Kisah perjuangan sepuluh anak di Belitung dalam menggapai cita-cita dengan penuh semangat dan keterbatasan sarana sekolah.', 'assets/img/literasi/1791383948_laskar-pelangi.webp', 'https://buku.kemdikbud.go.id', 'terbit', '2026-10-07 20:34:02', '2026-10-07 22:39:08'),
(2, 'Negeri 5 Menara', 'Ahmad Fuadi', 'Gramedia Pustaka Utama', '2009', 'Pendidikan Karakter', 'Kisah persahabatan santri di Pondok Madani dengan mantra ajaib "Man Jadda Wajada" yang membawa mereka menjelajah dunia.', 'assets/img/literasi/1791383783_shot.webp', 'https://buku.kemdikbud.go.id', 'terbit', '2026-10-07 20:34:02', '2026-10-07 22:36:23'),
(3, 'Bumi', 'Tere Liye', 'Gramedia Pustaka Utama', '2014', 'Fiksi & Petualangan', 'Petualangan fantasi tiga sahabat, Raib, Seli, dan Ali, dalam menjelajahi dunia paralel klan Bumi dan klan Bulan.', 'assets/img/literasi/1791383637_18759843._SX120_.jpg', 'https://buku.kemdikbud.go.id', 'terbit', '2026-10-07 20:34:02', '2026-10-07 22:33:57');

CREATE TABLE `jurnal_guru` (
  `id` int NOT NULL,
  `guru_id` bigint UNSIGNED NOT NULL,
  `kelas_id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `mata_pelajaran` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `materi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `metode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jumlah_hadir` int DEFAULT '0',
  `jumlah_tidak_hadir` int DEFAULT '0',
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jurnal_kelas` (
  `id` int NOT NULL,
  `kelas_id` bigint UNSIGNED NOT NULL,
  `guru_id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `mata_pelajaran` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `materi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah_hadir` int DEFAULT '0',
  `jumlah_tidak_hadir` int DEFAULT '0',
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kaih_kelas` (
  `id` bigint UNSIGNED NOT NULL,
  `nama_kelas` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tingkat` enum('7','8','9') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wali_kelas_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `kaih_kelas` (`id`, `nama_kelas`, `tingkat`, `wali_kelas_id`, `created_at`, `updated_at`) VALUES
(1, 'Kelas 7A', '7', 1, '2026-03-15 17:53:27', '2026-03-15 17:53:27'),
(2, 'Kelas 7B', '7', 2, '2026-03-15 17:53:27', '2026-03-15 17:53:27'),
(3, 'Kelas 8A', '8', NULL, '2026-03-15 17:53:27', '2026-03-15 17:53:27'),
(4, 'Kelas 8B', '8', NULL, '2026-03-15 17:53:27', '2026-03-15 17:53:27'),
(5, 'Kelas 9A', '9', NULL, '2026-03-15 17:53:27', '2026-03-15 17:53:27'),
(6, 'Kelas 9B', '9', NULL, '2026-03-15 17:53:27', '2026-03-15 17:53:27');

CREATE TABLE `kepuasan` (
  `id` int NOT NULL,
  `nama_pengisi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `peran` enum('Siswa','Orang Tua','Guru','Masyarakat') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aspek` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `suka` enum('Suka','Tidak Suka') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `alasan_suka` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `alasan_tidak_suka` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `saran` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `laporan_bullying` (
  `id` int NOT NULL,
  `nama_pelapor` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kontak` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_pelapor` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_korban` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas_korban` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_bullying` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_kejadian` date NOT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lokasi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `saksi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('baru','diproses','selesai') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'baru',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `laporan_bullying` (`id`, `nama_pelapor`, `kontak`, `status_pelapor`, `nama_korban`, `kelas_korban`, `jenis_bullying`, `tanggal_kejadian`, `deskripsi`, `lokasi`, `saksi`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Wicaksono', '087800001111', 'Lainnya', 'Dawwas', '8B', 'Cyber', '2026-10-01', 'Rasis dikatain hitam', 'Labter ITK', '', 'baru', '2026-10-01 11:04:12', '2026-10-01 11:04:12');

CREATE TABLE `laporan_harian` (
  `id` int NOT NULL,
  `siswa_id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `bangun` tinyint(1) NOT NULL DEFAULT '0',
  `bangun_keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ibadah` tinyint(1) NOT NULL DEFAULT '0',
  `ibadah_catatan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `olahraga` tinyint(1) NOT NULL DEFAULT '0',
  `olahraga_jenis` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sarapan` tinyint(1) NOT NULL DEFAULT '0',
  `sarapan_menu` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `membaca` tinyint(1) NOT NULL DEFAULT '0',
  `membaca_judul` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `membaca_menit` int DEFAULT NULL,
  `membantu` tinyint(1) NOT NULL DEFAULT '0',
  `membantu_jenis` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `menabung` tinyint(1) NOT NULL DEFAULT '0',
  `menabung_keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `menabung_nominal` int DEFAULT NULL,
  `orang_tua_validated_at` datetime DEFAULT NULL,
  `guru_validated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `laporan_harian` (`id`, `siswa_id`, `tanggal`, `bangun`, `bangun_keterangan`, `ibadah`, `ibadah_catatan`, `olahraga`, `olahraga_jenis`, `sarapan`, `sarapan_menu`, `membaca`, `membaca_judul`, `membaca_menit`, `membantu`, `membantu_jenis`, `menabung`, `menabung_keterangan`, `menabung_nominal`, `orang_tua_validated_at`, `guru_validated_at`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-10-01', 0, NULL, 0, NULL, 0, NULL, 0, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL, NULL, NULL, '2026-10-07 21:09:05', '2026-10-07 21:09:05'),
(2, 1, '2026-10-02', 0, NULL, 0, NULL, 0, NULL, 0, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL, NULL, NULL, '2026-10-07 21:09:05', '2026-10-07 21:09:05'),
(3, 1, '2026-10-05', 0, NULL, 0, NULL, 0, NULL, 0, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL, NULL, NULL, '2026-10-07 21:09:05', '2026-10-07 21:09:05'),
(4, 1, '2026-10-06', 0, NULL, 0, NULL, 0, NULL, 0, NULL, 0, NULL, 0, 0, NULL, 0, NULL, NULL, NULL, NULL, '2026-10-07 21:09:05', '2026-10-07 21:09:05');

CREATE TABLE `refleksi` (
  `id` int NOT NULL,
  `siswa_id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `semester` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ganjil',
  `tahun_ajaran` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pelajaran_favorit` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pelajaran_sulit` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pencapaian` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `kendala` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pengalaman_berkesan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `saran` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `target_kedepan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `siswa` (
  `id` bigint UNSIGNED NOT NULL,
  `nisn` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_siswa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_kelamin` enum('L','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wali_kelas_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `siswa` (`id`, `nisn`, `nama_siswa`, `kelas`, `jenis_kelamin`, `wali_kelas_id`, `created_at`, `updated_at`) VALUES
(1, '1234567890', 'Reyhan Adi Wijaya', 'Kelas 7A', 'L', 1, '2026-03-15 23:47:09', '2026-03-15 23:47:09'),
(2, '1234567891', 'Reina Nur Aulia', 'Kelas 7A', 'P', 1, '2026-03-15 23:47:09', '2026-03-15 23:47:09'),
(3, '1234567892', 'Andi Pratama', 'Kelas 7B', 'L', 2, '2026-03-15 23:47:09', '2026-03-15 23:47:09');

CREATE TABLE `survei` (
  `id` int NOT NULL,
  `nama_pengisi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `peran` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `ulasan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','guru','siswa','orang_tua','kepsek') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'siswa',
  `guru_id` bigint UNSIGNED DEFAULT NULL,
  `siswa_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password`, `role`, `guru_id`, `siswa_id`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$PROJzyFfja6R1YgltZgbqOGK4NsQpn0t7lFrEC46QgrjDYbQNxSy.', 'admin', NULL, NULL, '2026-03-15 18:28:10', '2026-03-15 18:28:10'),
(2, '198501012010011001', '$2y$12$yJawyd6W0GufM59WUjndieyWdy0vvcvsVitbWcqYGDWA7boaWTacq', 'guru', 1, NULL, '2026-03-15 21:02:29', '2026-03-16 00:25:59'),
(3, '198702022010012002', '$2y$12$yJawyd6W0GufM59WUjndieyWdy0vvcvsVitbWcqYGDWA7boaWTacq', 'guru', 2, NULL, '2026-03-15 21:02:29', '2026-03-16 00:25:59'),
(4, '1234567890', '$2y$10$qkE/gHCZQuvCGZZzR1thSOwkygOyIIYZW2iJV8NQ1TwoLgk75jG8.', 'siswa', NULL, 1, '2026-03-15 23:47:09', '2026-03-15 23:47:09'),
(5, '1234567891', '$2y$10$qkE/gHCZQuvCGZZzR1thSOwkygOyIIYZW2iJV8NQ1TwoLgk75jG8.', 'siswa', NULL, 2, '2026-03-15 23:47:09', '2026-03-15 23:47:09'),
(6, 'ORT1234567890', '$2y$10$J8XgTupnMrPE8Dl5uFkkwuva9msJ4RGXFqK.Kr5jp5g1Ml9RmI.eC', 'orang_tua', NULL, 1, '2026-03-15 23:47:09', '2026-03-15 23:47:09'),
(7, 'ORT1234567891', '$2y$10$J8XgTupnMrPE8Dl5uFkkwuva9msJ4RGXFqK.Kr5jp5g1Ml9RmI.eC', 'orang_tua', NULL, 2, '2026-03-15 23:47:09', '2026-03-15 23:47:09');

ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_siswa_tanggal` (`siswa_id`,`tanggal`),
  ADD KEY `absensi_siswa_id_foreign` (`siswa_id`);

ALTER TABLE `berita`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_berita_tanggal_judul` (`tanggal`,`judul`),
  ADD KEY `idx_berita_publikasi` (`status`,`jenis`,`tanggal`);

ALTER TABLE `foto_slideshow`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nip` (`nip`);

ALTER TABLE `jurnal_guru`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jurnal_guru_guru_id_foreign` (`guru_id`),
  ADD KEY `jurnal_guru_kelas_id_foreign` (`kelas_id`),
  ADD KEY `idx_tanggal` (`tanggal`);

ALTER TABLE `jurnal_kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jurnal_kelas_kelas_id_foreign` (`kelas_id`),
  ADD KEY `jurnal_kelas_guru_id_foreign` (`guru_id`),
  ADD KEY `idx_tanggal` (`tanggal`);

ALTER TABLE `kaih_kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nama_kelas` (`nama_kelas`),
  ADD KEY `kaih_kelas_wali_kelas_id_foreign` (`wali_kelas_id`);

ALTER TABLE `kepuasan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_peran` (`peran`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_suka` (`suka`);

ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laporan_siswa_id_foreign` (`siswa_id`),
  ADD KEY `laporan_guru_id_foreign` (`guru_id`);

ALTER TABLE `laporan_bullying`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

ALTER TABLE `laporan_harian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_siswa_tanggal` (`siswa_id`,`tanggal`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `laporan_harian_siswa_id_foreign` (`siswa_id`);

ALTER TABLE `refleksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_siswa_tanggal` (`siswa_id`,`tanggal`);

ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nisn` (`nisn`),
  ADD KEY `siswa_wali_kelas_id_foreign` (`wali_kelas_id`);

ALTER TABLE `survei`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD KEY `users_guru_id_foreign` (`guru_id`),
  ADD KEY `users_siswa_id_foreign` (`siswa_id`);

ALTER TABLE `absensi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

ALTER TABLE `berita`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `foto_slideshow`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `guru`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `jurnal_guru`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `jurnal_kelas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `kaih_kelas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `jendela_literasi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `kepuasan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `laporan`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `laporan_bullying`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `laporan_harian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `refleksi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `siswa`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `survei`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `jurnal_guru`
  ADD CONSTRAINT `jurnal_guru_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `jurnal_guru_kelas_id_foreign` FOREIGN KEY (`kelas_id`) REFERENCES `kaih_kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `jurnal_kelas`
  ADD CONSTRAINT `jurnal_kelas_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `jurnal_kelas_kelas_id_foreign` FOREIGN KEY (`kelas_id`) REFERENCES `kaih_kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `laporan`
  ADD CONSTRAINT `laporan_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `laporan_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `laporan_harian`
  ADD CONSTRAINT `laporan_harian_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `refleksi`
  ADD CONSTRAINT `refleksi_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_wali_kelas_id_foreign` FOREIGN KEY (`wali_kelas_id`) REFERENCES `kaih_kelas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `users`
  ADD CONSTRAINT `users_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `users_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;
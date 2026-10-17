-- Migration: Menambahkan role 'kepsek', akun kepala sekolah, dan modul supervisi guru
USE `kaih`;

-- 1. Update ENUM role pada tabel users
ALTER TABLE `users` 
MODIFY COLUMN `role` ENUM('admin','guru','siswa','orang_tua','kepsek') NOT NULL DEFAULT 'siswa';

-- 2. Pastikan data Kepala Sekolah ada di tabel guru untuk referensi profil
INSERT INTO `guru` (`nip`, `nama_guru`, `kelas`, `jenis_kelamin`, `alamat`, `no_hp`, `jabatan`, `created_at`, `updated_at`)
VALUES ('198301172009031005', 'Aris Broto, S.Pd', 'Ruang Pimpinan', 'L', 'Balikpapan', '081234567899', 'Kepala Sekolah', NOW(), NOW())
ON DUPLICATE KEY UPDATE `nama_guru` = VALUES(`nama_guru`), `jabatan` = 'Kepala Sekolah';

-- Dapatkan ID guru kepala sekolah
SET @kepsek_guru_id = (SELECT `id` FROM `guru` WHERE `nip` = '198301172009031005' LIMIT 1);

-- 3. Tambahkan akun user untuk Kepala Sekolah jika belum ada
-- Password '12345678' di-hash menggunakan password_hash bcrypt
-- Hash password: $2y$10$w0954p3qf81G0Yg8rW16u.V6075sFj4iY4i7E70wA1iQoYv0hN2W6
INSERT INTO `users` (`username`, `password`, `role`, `guru_id`, `siswa_id`, `created_at`, `updated_at`)
VALUES ('198301172009031005', '$2y$10$w0954p3qf81G0Yg8rW16u.V6075sFj4iY4i7E70wA1iQoYv0hN2W6', 'kepsek', @kepsek_guru_id, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    `password` = '$2y$10$w0954p3qf81G0Yg8rW16u.V6075sFj4iY4i7E70wA1iQoYv0hN2W6',
    `role` = 'kepsek',
    `guru_id` = @kepsek_guru_id;

-- 4. Tabel Master Rubrik Supervisi Akademik / Observasi Guru
CREATE TABLE IF NOT EXISTS `supervisi_rubrik` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `aspek` VARCHAR(100) NOT NULL,
    `kode_aspek` VARCHAR(20) NOT NULL,
    `indikator` VARCHAR(255) NOT NULL,
    `deskripsi` TEXT NULL,
    `urutan` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabel Hasil Penilaian Supervisi Observasi Guru oleh Kepala Sekolah
CREATE TABLE IF NOT EXISTS `supervisi_penilaian` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `guru_id` BIGINT UNSIGNED NOT NULL,
    `kepsek_id` BIGINT UNSIGNED NOT NULL,
    `tanggal_observasi` DATE NOT NULL,
    `semester` ENUM('Ganjil', 'Genap') NOT NULL DEFAULT 'Ganjil',
    `tahun_ajaran` VARCHAR(20) NOT NULL DEFAULT '2026/2027',
    `mata_pelajaran` VARCHAR(100) NOT NULL,
    `kelas` VARCHAR(50) NOT NULL,
    `skor_akhir` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `predikat` VARCHAR(30) NOT NULL DEFAULT 'Cukup',
    `catatan_kelebihan` TEXT NULL,
    `catatan_kelemahan` TEXT NULL,
    `rekomendasi_tindak_lanjut` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_supervisi_guru` (`guru_id`),
    KEY `idx_supervisi_kepsek` (`kepsek_id`),
    CONSTRAINT `fk_supervisi_guru` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_supervisi_kepsek` FOREIGN KEY (`kepsek_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabel Detail Poin Jawaban Rubrik per Indikator
CREATE TABLE IF NOT EXISTS `supervisi_penilaian_detail` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `penilaian_id` INT NOT NULL,
    `rubrik_id` INT NOT NULL,
    `pilihan` ENUM('A','B','C','D','E') NOT NULL,
    `skor_persen` DECIMAL(5,2) NOT NULL,
    `catatan_khusus` TEXT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_penilaian_rubrik` (`penilaian_id`, `rubrik_id`),
    KEY `idx_detail_rubrik` (`rubrik_id`),
    CONSTRAINT `fk_detail_penilaian` FOREIGN KEY (`penilaian_id`) REFERENCES `supervisi_penilaian` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_detail_rubrik` FOREIGN KEY (`rubrik_id`) REFERENCES `supervisi_rubrik` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Seed Indikator Rubrik Standar Supervisi Kurikulum Merdeka SMP
INSERT INTO `supervisi_rubrik` (`id`, `aspek`, `kode_aspek`, `indikator`, `deskripsi`, `urutan`) VALUES
(1, 'Perencanaan Pembelajaran', 'PERENCANAAN', 'Kesesuaian Modul Ajar / RPP dengan TP & Karakteristik Siswa', 'Kelengkapan tujuan pembelajaran, alur tujuan, serta relevansi materi dengan kesiapan belajar peserta didik.', 1),
(2, 'Perencanaan Pembelajaran', 'PERENCANAAN', 'Penyusunan Asesmen Awal dan Diferensiasi Pembelajaran', 'Rancangan strategi diagnostik untuk memetakan minat dan gaya belajar peserta didik serta diferensiasi konten/proses/produk.', 2),
(3, 'Perencanaan Pembelajaran', 'PERENCANAAN', 'Kesiapan Media, Sumber Belajar & Teknologi Digital', 'Ketersediaan bahan ajar, alat peraga, platform digital, atau LKPD interaktif yang disiapkan secara matang.', 3),
(4, 'Pelaksanaan Pembelajaran', 'PELAKSANAAN', 'Apersepsi Bermakna & Penyampaian Tujuan Pembelajaran', 'Kemampuan guru menghubungkan materi dengan dunia nyata murid, memotivasi, dan menyampaikan alur kegiatan dengan jelas.', 4),
(5, 'Pelaksanaan Pembelajaran', 'PELAKSANAAN', 'Penguasaan Materi & Penerapan Deep Learning', 'Kedalaman materi esensial, penguasaan konsep tanpa miskonsepsi, serta memantik berpikir kritis dan pemahaman mendalam.', 5),
(6, 'Pelaksanaan Pembelajaran', 'PELAKSANAAN', 'Keaktifan dan Keterlibatan Kolaboratif Siswa', 'Keterlibatan murid secara aktif berdiskusi kelompok, bertanya, memecahkan masalah, dan presentasi mandiri.', 6),
(7, 'Pelaksanaan Pembelajaran', 'PELAKSANAAN', 'Pemanfaatan Media Pembelajaran Interaktif / Digital', 'Efektivitas penggunaan sarana Chromebook/LCD/media visual dalam meningkatkan daya serap belajar siswa.', 7),
(8, 'Asesmen & Evaluasi Belajar', 'ASESMEN', 'Pemberian Umpan Balik (Feedback) Formatif Secara Real-time', 'Guru memantau pemahaman siswa saat proses belajar berlangsung dan memberikan bimbingan konstruktif.', 8),
(9, 'Asesmen & Evaluasi Belajar', 'ASESMEN', 'Pelaksanaan Refleksi Bersama Peserta Didik', 'Mengajak siswa merangkum pembelajaran, mengungkapkan perasaan belajar, dan mengukur ketercapaian tujuan.', 9),
(10, 'Pengelolaan Kelas & Budaya Positif', 'MANAJEMEN', 'Manajemen Waktu dan Suasana Kelas yang Kondusif', 'Pengelolaan durasi waktu pembuka-inti-penutup yang proporsional, serta disiplin positif tanpa hukuman fisik/verbal.', 10),
(11, 'Pengelolaan Kelas & Budaya Positif', 'MANAJEMEN', 'Komunikasi Positif, Empati, dan Penguatan Karakter', 'Penerapan nilai 7 Kebiasaan Anak Indonesia Hebat (KAIH), kesantunan berbahasa, dan kepedulian terhadap seluruh siswa.', 11)
ON DUPLICATE KEY UPDATE 
    `aspek` = VALUES(`aspek`),
    `kode_aspek` = VALUES(`kode_aspek`),
    `indikator` = VALUES(`indikator`),
    `deskripsi` = VALUES(`deskripsi`),
    `urutan` = VALUES(`urutan`);

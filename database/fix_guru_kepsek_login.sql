-- ==============================================================================
-- SQL MIGRATION: Perbaikan Akun Guru & Kepala Sekolah sesuai login_guru.pdf
-- Sistem KAIH SMP Negeri 28 Balikpapan
-- ==============================================================================

USE `kaih`;

-- 1. Koreksi NIP Guru yang typo agar sesuai dengan dokumen resmi login_guru.pdf
UPDATE `guru` SET `nip` = '199708152020122001' WHERE `nama_guru` LIKE '%Cindy Milenia%';
UPDATE `guru` SET `nip` = '199211082020122004' WHERE `nama_guru` LIKE '%Nurhabibah%';

-- 2. Koreksi Penugasan Ruang / Wali Kelas pada tabel guru
UPDATE `guru` SET `kelas` = 'Ruang Pimpinan' WHERE `nama_guru` = 'Aris Broto, S.Pd';
UPDATE `guru` SET `kelas` = 'Ruang Pimpinan' WHERE `nama_guru` = 'Angger Dalu Gede Wijayana, S.Pd';
UPDATE `guru` SET `kelas` = 'Ruang Kurikulum' WHERE `nama_guru` = 'Muhammad Alief Ramadhany, S.Pd';
UPDATE `guru` SET `kelas` = 'Kelas 9A' WHERE `nama_guru` = 'Angelyn May Rotua Lumban Batu, S.Pd';
UPDATE `guru` SET `kelas` = 'Kelas 8A' WHERE `nama_guru` = 'Cindy Milenia, S.Pd';
UPDATE `guru` SET `kelas` = 'Kelas 8B' WHERE `nama_guru` = 'Imil Mahmudah, S.Pd.I';
UPDATE `guru` SET `kelas` = 'Kelas 8C' WHERE `nama_guru` = 'Jenni Roslianthi, S.Pd';
UPDATE `guru` SET `kelas` = 'Kelas 8D' WHERE `nama_guru` = 'Nurhabibah, S.Kom';
UPDATE `guru` SET `kelas` = 'Kelas 9B' WHERE `nama_guru` = 'Eka Rahmayanti, S.Pd';
UPDATE `guru` SET `kelas` = 'Ruang BK' WHERE `nama_guru` = 'Rini Yulianti, S.Pd';
UPDATE `guru` SET `kelas` = 'Laboratorium IPA' WHERE `nama_guru` = 'Apryana Wulandari, S.Pd';
UPDATE `guru` SET `kelas` = 'Kelas 7B' WHERE `nama_guru` = 'Ahmad Wijaya, S.Pd';
UPDATE `guru` SET `kelas` = 'Kelas 7A' WHERE `nama_guru` = 'Siti Rahayu, Gr.S.Pd';

-- 3. Sinkronisasi wali kelas di tabel kaih_kelas
UPDATE `kaih_kelas` SET `wali_kelas_id` = (SELECT `id` FROM `guru` WHERE `nama_guru` = 'Siti Rahayu, Gr.S.Pd' LIMIT 1) WHERE `nama_kelas` = 'Kelas 7A';
UPDATE `kaih_kelas` SET `wali_kelas_id` = (SELECT `id` FROM `guru` WHERE `nama_guru` = 'Ahmad Wijaya, S.Pd' LIMIT 1) WHERE `nama_kelas` = 'Kelas 7B';
UPDATE `kaih_kelas` SET `wali_kelas_id` = (SELECT `id` FROM `guru` WHERE `nama_guru` = 'Cindy Milenia, S.Pd' LIMIT 1) WHERE `nama_kelas` = 'Kelas 8A';
UPDATE `kaih_kelas` SET `wali_kelas_id` = (SELECT `id` FROM `guru` WHERE `nama_guru` = 'Imil Mahmudah, S.Pd.I' LIMIT 1) WHERE `nama_kelas` = 'Kelas 8B';
UPDATE `kaih_kelas` SET `wali_kelas_id` = (SELECT `id` FROM `guru` WHERE `nama_guru` = 'Jenni Roslianthi, S.Pd' LIMIT 1) WHERE `nama_kelas` = 'Kelas 8C';
UPDATE `kaih_kelas` SET `wali_kelas_id` = (SELECT `id` FROM `guru` WHERE `nama_guru` = 'Nurhabibah, S.Kom' LIMIT 1) WHERE `nama_kelas` = 'Kelas 8D';
UPDATE `kaih_kelas` SET `wali_kelas_id` = (SELECT `id` FROM `guru` WHERE `nama_guru` = 'Angelyn May Rotua Lumban Batu, S.Pd' LIMIT 1) WHERE `nama_kelas` = 'Kelas 9A';
UPDATE `kaih_kelas` SET `wali_kelas_id` = (SELECT `id` FROM `guru` WHERE `nama_guru` = 'Eka Rahmayanti, S.Pd' LIMIT 1) WHERE `nama_kelas` = 'Kelas 9B';

-- 4. Perbaiki Akun Kepala Sekolah (Password: 12345678)
-- Hash Bcrypt valid untuk '12345678': $2y$10$yyk2.vm3bJ2BBn7WT.bU4eWpUf7/SVK8XFUqy1/RkcHik6STHicYq
INSERT INTO `users` (`username`, `password`, `role`, `guru_id`, `siswa_id`, `created_at`, `updated_at`)
VALUES (
    '198301172009031005',
    '$2y$10$yyk2.vm3bJ2BBn7WT.bU4eWpUf7/SVK8XFUqy1/RkcHik6STHicYq',
    'kepsek',
    (SELECT `id` FROM `guru` WHERE `nip` = '198301172009031005' LIMIT 1),
    NULL,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE 
    `password` = '$2y$10$yyk2.vm3bJ2BBn7WT.bU4eWpUf7/SVK8XFUqy1/RkcHik6STHicYq',
    `role` = 'kepsek',
    `guru_id` = (SELECT `id` FROM `guru` WHERE `nip` = '198301172009031005' LIMIT 1);

-- 5. Buat / Sinkronkan Akun 12 Guru (Password: 123456)
-- Hash Bcrypt valid untuk '123456': $2y$10$OKKSDZbRwYiWS2KPznqdLOaquCsqF5cCC8fJjr2L7OE13fgKjPyQa
INSERT INTO `users` (`username`, `password`, `role`, `guru_id`, `siswa_id`, `created_at`, `updated_at`)
SELECT 
    `nip` AS `username`,
    '$2y$10$OKKSDZbRwYiWS2KPznqdLOaquCsqF5cCC8fJjr2L7OE13fgKjPyQa' AS `password`,
    'guru' AS `role`,
    `id` AS `guru_id`,
    NULL AS `siswa_id`,
    NOW() AS `created_at`,
    NOW() AS `updated_at`
FROM `guru`
WHERE `jabatan` != 'Kepala Sekolah'
ON DUPLICATE KEY UPDATE
    `password` = '$2y$10$OKKSDZbRwYiWS2KPznqdLOaquCsqF5cCC8fJjr2L7OE13fgKjPyQa',
    `role` = 'guru',
    `guru_id` = VALUES(`guru_id`),
    `updated_at` = NOW();

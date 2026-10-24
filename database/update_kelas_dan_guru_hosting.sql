-- ==============================================================================
-- SQL MIGRATION: Update Daftar Kelas & Guru Resmi SMP Negeri 28 Balikpapan
-- Database Hosting: if0_43155152_kaih (sql105.infinityfree.com)
-- Berdasarkan: SK Pembagian Tugas Semester Ganjil TA 2026/2027 (Tanpa Firman)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- BAGIAN 1: PERBAIKI DAFTAR KELAS (7A-7D, 8A-8D, 9A-9D)
-- -----------------------------------------------------------------------------
-- Mengatasi duplikasi kelas pada dropdown dan menyusun urutan 7a-d, 8a-d, 9a-d
TRUNCATE TABLE `kaih_kelas`;

INSERT INTO `kaih_kelas` (`id`, `nama_kelas`, `tingkat`, `wali_kelas_id`, `created_at`, `updated_at`) VALUES
(1, 'Kelas 7A', '7', NULL, NOW(), NOW()),
(2, 'Kelas 7B', '7', NULL, NOW(), NOW()),
(3, 'Kelas 7C', '7', NULL, NOW(), NOW()),
(4, 'Kelas 7D', '7', NULL, NOW(), NOW()),
(5, 'Kelas 8A', '8', NULL, NOW(), NOW()),
(6, 'Kelas 8B', '8', NULL, NOW(), NOW()),
(7, 'Kelas 8C', '8', NULL, NOW(), NOW()),
(8, 'Kelas 8D', '8', NULL, NOW(), NOW()),
(9, 'Kelas 9A', '9', NULL, NOW(), NOW()),
(10, 'Kelas 9B', '9', NULL, NOW(), NOW()),
(11, 'Kelas 9C', '9', NULL, NOW(), NOW()),
(12, 'Kelas 9D', '9', NULL, NOW(), NOW()),
(13, 'Belum Ada Kelas', '7', NULL, NOW(), NOW());

-- -----------------------------------------------------------------------------
-- BAGIAN 2: BERSIHKAN GURU DUMMY LAMA (Siti Rahayu, Ahmad Wijaya, dll)
-- -----------------------------------------------------------------------------
DELETE FROM `users` WHERE `username` IN ('198501012010011001', '198702022010012002', '199308142022031002');
DELETE FROM `guru` WHERE `nip` IN ('198501012010011001', '198702022010012002', '199308142022031002') OR `nama_guru` LIKE '%Firman%';

-- -----------------------------------------------------------------------------
-- BAGIAN 3: MASUKKAN DATA GURU RESMI SESUAI SK (SELAIN FIRMAN)
-- -----------------------------------------------------------------------------
-- Total: 1 Kepala Sekolah + 10 Guru SK Resmi
INSERT INTO `guru` (`nip`, `nama_guru`, `kelas`, `jenis_kelamin`, `alamat`, `no_hp`, `jabatan`, `created_at`, `updated_at`)
VALUES
-- 1. Kepala Sekolah
('198301172009031005', 'Aris Broto, S.Pd.', 'Ruang Pimpinan', 'L', 'Balikpapan', '081234567890', 'Kepala Sekolah', NOW(), NOW()),

-- 2. Wali Kelas 7A: Rini Yuliyanti, S.Pd.
('198904222014022007', 'Rini Yuliyanti, S.Pd.', 'Kelas 7A', 'P', 'Balikpapan', '081234567891', 'Guru BK & Seni Budaya / Koordinator BK', NOW(), NOW()),

-- 3. Wali Kelas 7B: Imil Mahmudah, S.Pd.
('199105122019032002', 'Imil Mahmudah, S.Pd.', 'Kelas 7B', 'P', 'Balikpapan', '081234567892', 'Guru PAI / Operator Dapodik', NOW(), NOW()),

-- 4. Wali Kelas 7C: Jenni Roslianth, S.Pd.
('199307242019032003', 'Jenni Roslianth, S.Pd.', 'Kelas 7C', 'P', 'Balikpapan', '081234567893', 'Guru Matematika / Pembina OSIS', NOW(), NOW()),

-- 5. Wali Kelas 8A: Eka Rahmayanti, S.Pd.
('199009142015032006', 'Eka Rahmayanti, S.Pd.', 'Kelas 8A', 'P', 'Balikpapan', '081234567894', 'Guru IPS / Pembina Pramuka', NOW(), NOW()),

-- 6. Wali Kelas 8B: Apryana Wulandari, S.Pd.
('199504172022032009', 'Apryana Wulandari, S.Pd.', 'Kelas 8B', 'P', 'Balikpapan', '081234567895', 'Guru BK & Bhs Indonesia / Koordinator Pubdok', NOW(), NOW()),

-- 7. Wali Kelas 8C: Angelyn May Rotua Lumban Batu, S.Pd.
('199405192022032005', 'Angelyn May Rotua Lumban Batu, S.Pd.', 'Kelas 8C', 'P', 'Balikpapan', '081234567896', 'Guru Bhs Inggris / Koordinator UKS', NOW(), NOW()),

-- 8. Wali Kelas 8D: Nurhabibah
('199211082020122004', 'Nurhabibah', 'Kelas 8D', 'P', 'Balikpapan', '081234567897', 'Guru Informatika / Ka. Perpus & Lab Komputer', NOW(), NOW()),

-- 9. Wakasek Ur. Kesiswaan: Angger Dalu Gede Wijayana
('198610102011011008', 'Angger Dalu Gede Wijayana, S.Tr.Or.,Gr.', 'Ruang Pimpinan', 'L', 'Balikpapan', '081234567898', 'Wakasek Ur. Kesiswaan / Guru Penjaskes', NOW(), NOW()),

-- 10. Wakasek Ur. Kurikulum: Muhammad Alief Ramadhany
('199203152019031010', 'Muhammad Alief Ramadhany, S.Pd.,Gr.', 'Ruang Kurikulum', 'L', 'Balikpapan', '081234567899', 'Wakasek Ur. Kurikulum / Guru Bhs Indonesia', NOW(), NOW()),

-- 11. PPTK & Guru PKN: Cindy Milenia
('199708152020122001', 'Cindy Milenia, S.Pd.', 'Ruang Guru', 'P', 'Balikpapan', '081234567800', 'Guru PKN / PPTK', NOW(), NOW())

ON DUPLICATE KEY UPDATE
    `nama_guru` = VALUES(`nama_guru`),
    `kelas` = VALUES(`kelas`),
    `jenis_kelamin` = VALUES(`jenis_kelamin`),
    `jabatan` = VALUES(`jabatan`),
    `updated_at` = NOW();

-- -----------------------------------------------------------------------------
-- BAGIAN 4: SINKRONISASI RELASI WALI KELAS DI TABEL KAIH_KELAS
-- -----------------------------------------------------------------------------
UPDATE `kaih_kelas` k
JOIN `guru` g ON g.kelas = k.nama_kelas
SET k.wali_kelas_id = g.id;

-- Relasikan kembali siswa lama (jika ada) ke id kelas yang baru
UPDATE `siswa` s
JOIN `kaih_kelas` k ON s.kelas = k.nama_kelas
SET s.wali_kelas_id = k.id;

-- -----------------------------------------------------------------------------
-- BAGIAN 5: GENERATE / UPDATE AKUN LOGIN DI TABEL USERS
-- -----------------------------------------------------------------------------
-- 1. Akun Kepala Sekolah (Role: kepsek | Password default: 12345678)
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
    `guru_id` = (SELECT `id` FROM `guru` WHERE `nip` = '198301172009031005' LIMIT 1),
    `updated_at` = NOW();

-- 2. Akun 10 Guru SK Resmi (Role: guru | Password default: 123456)
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

SET FOREIGN_KEY_CHECKS = 1;

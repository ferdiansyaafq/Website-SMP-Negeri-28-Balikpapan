-- Active: 1789068490346@@127.0.0.1@3306@kaih
USE `kaih`;

ALTER TABLE `guru`
    ADD COLUMN IF NOT EXISTS `kelas` varchar(50) NULL AFTER `nama_guru`;

ALTER TABLE `siswa`
    ADD COLUMN IF NOT EXISTS `kelas` varchar(50) NULL AFTER `nama_siswa`;

UPDATE `siswa` s
LEFT JOIN `kaih_kelas` k ON k.id = s.wali_kelas_id
SET s.kelas = k.nama_kelas
WHERE (s.kelas IS NULL OR s.kelas = '') AND k.id IS NOT NULL;

UPDATE `guru` g
LEFT JOIN `kaih_kelas` k ON k.wali_kelas_id = g.id
SET g.kelas = k.nama_kelas
WHERE (g.kelas IS NULL OR g.kelas = '') AND k.id IS NOT NULL;

CREATE INDEX IF NOT EXISTS `idx_siswa_kelas` ON `siswa` (`kelas`);
CREATE INDEX IF NOT EXISTS `idx_guru_kelas` ON `guru` (`kelas`);

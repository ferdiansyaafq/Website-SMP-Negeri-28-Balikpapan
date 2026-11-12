<?php
// aplikasi/kepsek/init_supervisi.php
// Otomatis memastikan tabel supervisi & rubrik tersedia di database (mencegah HTTP 500/502 di hosting)

if (!function_exists('ensureSupervisiSchema')) {
    function ensureSupervisiSchema(PDO $pdo): void
    {
        if (!empty($_SESSION['_supervisi_schema_ready'])) {
            return;
        }

        try {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM supervisi_rubrik")->fetchColumn();
            $pdo->query("SELECT 1 FROM supervisi_penilaian LIMIT 1");
            $pdo->query("SELECT 1 FROM supervisi_penilaian_detail LIMIT 1");
            if ($count > 0) {
                $_SESSION['_supervisi_schema_ready'] = true;
                return;
            }
        } catch (PDOException $e) {
            // Tabel belum ada, lanjut buat otomatis
        }

        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `supervisi_rubrik` (
                    `id` INT NOT NULL AUTO_INCREMENT,
                    `aspek` VARCHAR(100) NOT NULL,
                    `kode_aspek` VARCHAR(20) NOT NULL,
                    `indikator` VARCHAR(255) NOT NULL,
                    `deskripsi` TEXT NULL,
                    `urutan` INT NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
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
                    KEY `idx_supervisi_kepsek` (`kepsek_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `supervisi_penilaian_detail` (
                    `id` INT NOT NULL AUTO_INCREMENT,
                    `penilaian_id` INT NOT NULL,
                    `rubrik_id` INT NOT NULL,
                    `pilihan` ENUM('A','B','C','D','E') NOT NULL,
                    `skor_persen` DECIMAL(5,2) NOT NULL,
                    `catatan_khusus` TEXT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_penilaian_rubrik` (`penilaian_id`, `rubrik_id`),
                    KEY `idx_detail_rubrik` (`rubrik_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
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
                    `urutan` = VALUES(`urutan`)
            ");

            $_SESSION['_supervisi_schema_ready'] = true;
        } catch (PDOException $e) {
            error_log('Supervisi schema auto-init failed: ' . $e->getMessage());
        }
    }
}

if (isset($pdo) && $pdo instanceof PDO) {
    ensureSupervisiSchema($pdo);
}

<?php
// database/fix_guru_kepsek_login.php
require_once __DIR__ . '/../config/database.php';

echo "=== MEMULAI SINKRONISASI & PERBAIKAN AKUN GURU DAN KEPSEK ===\n\n";

try {
    $pdo->beginTransaction();

    // 1. Perbaiki NIP Cindy Milenia dan Nurhabibah di tabel guru jika belum sesuai dokumen PDF
    echo "[1] Memperbaiki NIP di tabel guru agar sesuai lembar contekan login...\n";
    $stmtUpdNip1 = $pdo->prepare("UPDATE guru SET nip = '199708152020122001' WHERE nama_guru LIKE '%Cindy Milenia%'");
    $stmtUpdNip1->execute();
    echo "    - Cindy Milenia NIP diperbarui ke: 199708152020122001\n";

    $stmtUpdNip2 = $pdo->prepare("UPDATE guru SET nip = '199211082020122004' WHERE nama_guru LIKE '%Nurhabibah%'");
    $stmtUpdNip2->execute();
    echo "    - Nurhabibah NIP diperbarui ke: 199211082020122004\n";

    // 2. Perbaiki Wali Kelas / Ruang pada tabel guru sesuai dokumen
    echo "\n[2] Memperbarui penugasan ruang/wali kelas pada tabel guru...\n";
    $ruangGuru = [
        'Aris Broto, S.Pd' => 'Ruang Pimpinan',
        'Angger Dalu Gede Wijayana, S.Pd' => 'Ruang Pimpinan',
        'Muhammad Alief Ramadhany, S.Pd' => 'Ruang Kurikulum',
        'Angelyn May Rotua Lumban Batu, S.Pd' => 'Kelas 9A',
        'Cindy Milenia, S.Pd' => 'Kelas 8A',
        'Imil Mahmudah, S.Pd.I' => 'Kelas 8B',
        'Jenni Roslianthi, S.Pd' => 'Kelas 8C',
        'Nurhabibah, S.Kom' => 'Kelas 8D',
        'Eka Rahmayanti, S.Pd' => 'Kelas 9B',
        'Rini Yulianti, S.Pd' => 'Ruang BK',
        'Apryana Wulandari, S.Pd' => 'Laboratorium IPA',
        'Ahmad Wijaya, S.Pd' => 'Kelas 7B',
        'Siti Rahayu, Gr.S.Pd' => 'Kelas 7A',
    ];

    $stmtUpdRuang = $pdo->prepare("UPDATE guru SET kelas = ? WHERE nama_guru = ?");
    foreach ($ruangGuru as $nama => $ruang) {
        $stmtUpdRuang->execute([$ruang, $nama]);
        echo "    - {$nama} => {$ruang}\n";
    }

    // 3. Update wali_kelas_id di tabel kaih_kelas
    echo "\n[3] Menyinkronkan wali kelas di tabel kaih_kelas...\n";
    $waliKelasMap = [
        'Kelas 7A' => 'Siti Rahayu, Gr.S.Pd',
        'Kelas 7B' => 'Ahmad Wijaya, S.Pd',
        'Kelas 8A' => 'Cindy Milenia, S.Pd',
        'Kelas 8B' => 'Imil Mahmudah, S.Pd.I',
        'Kelas 8C' => 'Jenni Roslianthi, S.Pd',
        'Kelas 8D' => 'Nurhabibah, S.Kom',
        'Kelas 9A' => 'Angelyn May Rotua Lumban Batu, S.Pd',
        'Kelas 9B' => 'Eka Rahmayanti, S.Pd',
    ];

    $stmtGetGId = $pdo->prepare("SELECT id FROM guru WHERE nama_guru = ?");
    $stmtUpdKlsWali = $pdo->prepare("UPDATE kaih_kelas SET wali_kelas_id = ? WHERE nama_kelas = ?");
    foreach ($waliKelasMap as $kls => $gNama) {
        $stmtGetGId->execute([$gNama]);
        $gId = $stmtGetGId->fetchColumn();
        if ($gId) {
            $stmtUpdKlsWali->execute([$gId, $kls]);
            echo "    - {$kls} -> {$gNama} (ID: {$gId})\n";
        }
    }

    // 4. Perbaiki Akun Kepala Sekolah di tabel users
    echo "\n[4] Memperbaiki akun Kepala Sekolah (password default: 12345678)...\n";
    $passKepsek = password_hash('12345678', PASSWORD_DEFAULT);
    $stmtGetKepsekGId = $pdo->query("SELECT id FROM guru WHERE nip = '198301172009031005'")->fetchColumn();

    $stmtUpsertKepsek = $pdo->prepare("
        INSERT INTO users (username, password, role, guru_id, siswa_id, created_at, updated_at)
        VALUES ('198301172009031005', ?, 'kepsek', ?, NULL, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            password = VALUES(password),
            role = 'kepsek',
            guru_id = VALUES(guru_id),
            updated_at = NOW()
    ");
    $stmtUpsertKepsek->execute([$passKepsek, $stmtGetKepsekGId]);
    echo "    [✓] Akun Kepsek (198301172009031005) berhasil di-update dengan password hash yang valid!\n";

    // 5. Buat / Update Akun Semua Guru di tabel users
    echo "\n[5] Membuat dan menyinkronkan akun seluruh guru (password default: 123456)...\n";
    $passGuru = password_hash('123456', PASSWORD_DEFAULT);

    $stmtGurus = $pdo->query("SELECT id, nip, nama_guru, jabatan FROM guru WHERE jabatan != 'Kepala Sekolah'");
    $daftarGuru = $stmtGurus->fetchAll();

    $stmtUpsertGuru = $pdo->prepare("
        INSERT INTO users (username, password, role, guru_id, siswa_id, created_at, updated_at)
        VALUES (?, ?, 'guru', ?, NULL, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            password = VALUES(password),
            role = 'guru',
            guru_id = VALUES(guru_id),
            updated_at = NOW()
    ");

    foreach ($daftarGuru as $g) {
        $stmtUpsertGuru->execute([$g['nip'], $passGuru, $g['id']]);
        echo "    [✓] Akun Guru dibuat/diupdate: {$g['nama_guru']} (NIP: {$g['nip']})\n";
    }

    // 6. Pastikan Super Admin ada dan aktif
    echo "\n[6] Memastikan akun Super Admin (admin / admin123)...\n";
    $stmtAdmin = $pdo->query("SELECT id, password FROM users WHERE username = 'admin'");
    $admin = $stmtAdmin->fetch();
    if (!$admin || !password_verify('admin123', $admin['password'])) {
        $passAdmin = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("
            INSERT INTO users (username, password, role, created_at, updated_at)
            VALUES ('admin', ?, 'admin', NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                password = VALUES(password),
                role = 'admin',
                updated_at = NOW()
        ")->execute([$passAdmin]);
        echo "    [✓] Akun admin disinkronkan.\n";
    } else {
        echo "    [✓] Akun admin sudah valid.\n";
    }

    $pdo->commit();
    echo "\n=== SEMUA PERUBAHAN BERHASIL DISIMPAN KE DATABASE! ===\n\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

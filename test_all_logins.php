<?php
// test_all_logins.php
require_once __DIR__ . '/config/database.php';

$testAccounts = [
    [
        'no' => 1,
        'nama' => 'Administrator Sistem',
        'role' => 'admin',
        'username' => 'admin',
        'password' => 'admin123',
        'expected_redirect' => 'aplikasi/admin/index.php'
    ],
    [
        'no' => 2,
        'nama' => 'Aris Broto, S.Pd',
        'role' => 'kepsek',
        'username' => '198301172009031005',
        'password' => '12345678',
        'expected_redirect' => 'aplikasi/kepsek/index.php'
    ],
    [
        'no' => 3,
        'nama' => 'Angger Dalu Gede Wijayana, S.Pd',
        'role' => 'guru',
        'username' => '198610102011011008',
        'password' => '123456',
        'expected_redirect' => 'aplikasi/guru/index.php'
    ],
    [
        'no' => 4,
        'nama' => 'Muhammad Alief Ramadhany, S.Pd',
        'role' => 'guru',
        'username' => '199203152019031010',
        'password' => '123456',
        'expected_redirect' => 'aplikasi/guru/index.php'
    ],
    [
        'no' => 5,
        'nama' => 'Angelyn May Rotua Lumban Batu, S.Pd',
        'role' => 'guru',
        'username' => '199405192022032005',
        'password' => '123456',
        'expected_redirect' => 'aplikasi/guru/index.php'
    ],
    [
        'no' => 6,
        'nama' => 'Cindy Milenia, S.Pd',
        'role' => 'guru',
        'username' => '199708152020122001',
        'password' => '123456',
        'expected_redirect' => 'aplikasi/guru/index.php'
    ],
    [
        'no' => 7,
        'nama' => 'Imil Mahmudah, S.Pd.I',
        'role' => 'guru',
        'username' => '199105122019032002',
        'password' => '123456',
        'expected_redirect' => 'aplikasi/guru/index.php'
    ],
    [
        'no' => 8,
        'nama' => 'Jenni Roslianthi, S.Pd',
        'role' => 'guru',
        'username' => '199307242019032003',
        'password' => '123456',
        'expected_redirect' => 'aplikasi/guru/index.php'
    ],
    [
        'no' => 9,
        'nama' => 'Nurhabibah, S.Kom',
        'role' => 'guru',
        'username' => '199211082020122004',
        'password' => '123456',
        'expected_redirect' => 'aplikasi/guru/index.php'
    ],
    [
        'no' => 10,
        'nama' => 'Eka Rahmayanti, S.Pd',
        'role' => 'guru',
        'username' => '199009142015032006',
        'password' => '123456',
        'expected_redirect' => 'aplikasi/guru/index.php'
    ],
    [
        'no' => 11,
        'nama' => 'Rini Yulianti, S.Pd',
        'role' => 'guru',
        'username' => '198904222014022007',
        'password' => '123456',
        'expected_redirect' => 'aplikasi/guru/index.php'
    ],
    [
        'no' => 12,
        'nama' => 'Apryana Wulandari, S.Pd',
        'role' => 'guru',
        'username' => '199504172022032009',
        'password' => '123456',
        'expected_redirect' => 'aplikasi/guru/index.php'
    ],
    [
        'no' => 13,
        'nama' => 'Ahmad Wijaya, S.Pd',
        'role' => 'guru',
        'username' => '198702022010012002',
        'password' => '123456',
        'expected_redirect' => 'aplikasi/guru/index.php'
    ],
    [
        'no' => 14,
        'nama' => 'Siti Rahayu, Gr.S.Pd',
        'role' => 'guru',
        'username' => '198501012010011001',
        'password' => '123456',
        'expected_redirect' => 'aplikasi/guru/index.php'
    ],
];

echo "=== PENGUJIAN LOGIN SISTEM UNTUK SELURUH AKUN DOKUMEN ===\n\n";

$passCount = 0;
$failCount = 0;

foreach ($testAccounts as $acc) {
    $username = $acc['username'];
    $password = $acc['password'];
    
    // Logika identik dengan login.php:
    $stmt = $pdo->prepare("SELECT id, username, password, role, guru_id, siswa_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $redirect = '';
        switch ($user['role']) {
            case 'admin':
                $redirect = 'aplikasi/admin/index.php';
                break;
            case 'guru':
                $redirect = 'aplikasi/guru/index.php';
                break;
            case 'kepsek':
                $redirect = 'aplikasi/kepsek/index.php';
                break;
            default:
                $redirect = 'index.php';
        }

        if ($user['role'] === $acc['role'] && $redirect === $acc['expected_redirect']) {
            echo sprintf(
                "[✓ BERHASIL] No %2d | %-32s | User: %-18s | Role: %-6s (ID: %d, GuruID: %s) -> %s\n",
                $acc['no'],
                $acc['nama'],
                $acc['username'],
                $user['role'],
                $user['id'],
                $user['guru_id'] ?? 'NULL',
                $redirect
            );
            $passCount++;
        } else {
            echo sprintf(
                "[✕ SALAH ROLE] No %2d | %-32s | Expected Role: %s, Got: %s\n",
                $acc['no'],
                $acc['nama'],
                $acc['role'],
                $user['role']
            );
            $failCount++;
        }
    } else {
        echo sprintf(
            "[✕ GAGAL LOGIN] No %2d | %-32s | User: %-18s | Password: %s (User Ditemukan: %s)\n",
            $acc['no'],
            $acc['nama'],
            $acc['username'],
            $acc['password'],
            $user ? 'YA' : 'TIDAK'
        );
        $failCount++;
    }
}

echo "\n================ HASIL PENGUJIAN ================\n";
echo "Total Akun Diuji : " . count($testAccounts) . "\n";
echo "Berhasil Login   : $passCount\n";
echo "Gagal Login      : $failCount\n";
echo "Status Akhir     : " . ($failCount === 0 ? "SEMPURNA (ALL PASS)" : "ADA KESALAHAN") . "\n";

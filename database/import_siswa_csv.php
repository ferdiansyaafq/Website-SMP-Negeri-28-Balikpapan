<?php
// database/import_siswa_csv.php
require_once __DIR__ . '/../config/database.php';

echo "=========================================================\n";
echo " IMPORT SISWA & GENERATE AKUN PENGGUNA (SMPN 28 BALIKPAPAN)\n";
echo "=========================================================\n\n";

// 1. Pastikan kelas-kelas terdaftar di tabel kaih_kelas
$kelas_diperlukan = [
    ['nama_kelas' => 'Kelas 7A', 'tingkat' => '7'],
    ['nama_kelas' => 'Kelas 7B', 'tingkat' => '7'],
    ['nama_kelas' => 'Kelas 8A', 'tingkat' => '8'],
    ['nama_kelas' => 'Kelas 8B', 'tingkat' => '8'],
    ['nama_kelas' => 'Kelas 8C', 'tingkat' => '8'],
    ['nama_kelas' => 'Kelas 8D', 'tingkat' => '8'],
    ['nama_kelas' => 'Kelas 9A', 'tingkat' => '9'],
    ['nama_kelas' => 'Kelas 9B', 'tingkat' => '9'],
    ['nama_kelas' => 'Belum Ada Kelas', 'tingkat' => '7'] // Wadah murid baru sebelum dibagi
];

$stmtCekKelas = $pdo->prepare("SELECT id FROM kaih_kelas WHERE nama_kelas = ?");
$stmtInsertKelas = $pdo->prepare("INSERT INTO kaih_kelas (nama_kelas, tingkat, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");

foreach ($kelas_diperlukan as $k) {
    $stmtCekKelas->execute([$k['nama_kelas']]);
    $id = $stmtCekKelas->fetchColumn();
    if (!$id) {
        $stmtInsertKelas->execute([$k['nama_kelas'], $k['tingkat']]);
        echo " [+] Kelas ditambahkan ke kaih_kelas: {$k['nama_kelas']} (Tingkat {$k['tingkat']})\n";
    }
}

// Ambil map kelas => id
$mapKelas = $pdo->query("SELECT nama_kelas, id FROM kaih_kelas")->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. Hash password terstandar: "smpn28bpp"
$password_plain = 'smpn28bpp';
$password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

// Prepared statements untuk tabel siswa
$stmtInsertSiswa = $pdo->prepare("
    INSERT INTO siswa (nisn, nama_siswa, kelas, jenis_kelamin, wali_kelas_id, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
        nama_siswa = VALUES(nama_siswa),
        kelas = VALUES(kelas),
        jenis_kelamin = VALUES(jenis_kelamin),
        wali_kelas_id = VALUES(wali_kelas_id),
        updated_at = NOW()
");

$stmtGetSiswaId = $pdo->prepare("SELECT id FROM siswa WHERE nisn = ?");

// Prepared statements untuk tabel users (Akun Siswa)
$stmtUpsertUserSiswa = $pdo->prepare("
    INSERT INTO users (username, password, role, siswa_id, created_at, updated_at)
    VALUES (?, ?, 'siswa', ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
        password = VALUES(password),
        role = 'siswa',
        siswa_id = VALUES(siswa_id),
        updated_at = NOW()
");

// Prepared statements untuk tabel users (Akun Orang Tua)
$stmtUpsertUserOrtu = $pdo->prepare("
    INSERT INTO users (username, password, role, siswa_id, created_at, updated_at)
    VALUES (?, ?, 'orang_tua', ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
        password = VALUES(password),
        role = 'orang_tua',
        siswa_id = VALUES(siswa_id),
        updated_at = NOW()
");

$stats = [
    'kelas_8a' => 0,
    'kelas_8b' => 0,
    'kelas_8c' => 0,
    'kelas_8d' => 0,
    'murid_baru' => 0,
    'total_siswa' => 0,
    'total_user_siswa' => 0,
    'total_user_ortu' => 0,
];

// Helper fungsi proses 1 siswa
function prosesSiswa($nisn, $nama, $kelas, $jk, $pdo, $stmtInsertSiswa, $stmtGetSiswaId, $stmtUpsertUserSiswa, $stmtUpsertUserOrtu, $password_hash, $mapKelas) {
    $wali_kelas_id = $mapKelas[$kelas] ?? null;

    // 1. Simpan/Update Siswa
    $stmtInsertSiswa->execute([$nisn, $nama, $kelas, $jk, $wali_kelas_id]);

    // 2. Ambil ID Siswa
    $stmtGetSiswaId->execute([$nisn]);
    $siswa_id = $stmtGetSiswaId->fetchColumn();

    // 3. Akun Siswa (username = NISN)
    $stmtUpsertUserSiswa->execute([$nisn, $password_hash, $siswa_id]);

    // 4. Akun Orang Tua (username = ORT + NISN)
    $username_ortu = 'ORT' . $nisn;
    $stmtUpsertUserOrtu->execute([$username_ortu, $password_hash, $siswa_id]);

    return true;
}

// ========================================================
// 3. PROSES KELAS 8 (8A, 8B, 8C, 8D)
// ========================================================
$csv_kelas_8 = [
    'kelas_8a' => ['path' => __DIR__ . '/csv/1_kelas_8a.csv', 'kelas' => 'Kelas 8A'],
    'kelas_8b' => ['path' => __DIR__ . '/csv/2_kelas_8b.csv', 'kelas' => 'Kelas 8B'],
    'kelas_8c' => ['path' => __DIR__ . '/csv/3_kelas_8c.csv', 'kelas' => 'Kelas 8C'],
    'kelas_8d' => ['path' => __DIR__ . '/csv/4_kelas_8d.csv', 'kelas' => 'Kelas 8D'],
];

echo "\n--- Memproses Siswa Kelas 8 ---\n";
foreach ($csv_kelas_8 as $key => $info) {
    if (!file_exists($info['path'])) {
        echo " [!] File tidak ditemukan: {$info['path']}\n";
        continue;
    }

    $h = fopen($info['path'], 'r');
    fgetcsv($h, 0, ',', '"', '\\'); // Lewati header
    $count = 0;

    while (($row = fgetcsv($h, 0, ',', '"', '\\')) !== false) {
        $no = trim($row[1] ?? '');
        // Abaikan baris rekapitulasi (S, I, A, dll)
        if (in_array(strtoupper($no), ['S', 'I', 'A', ''])) continue;

        $rawNisn = trim(explode('/', $row[2] ?? '')[0]);
        $nama = trim($row[3] ?? '');
        $jk = strtoupper(trim($row[4] ?? ''));
        if ($jk !== 'L' && $jk !== 'P') {
            $jk = 'L'; // Default
        }

        // Penanganan siswa khusus Cantika (jika NISN belum terisi di csv)
        if (empty($rawNisn) && stripos($nama, 'Cantika') !== false) {
            $rawNisn = '0120000026';
            $jk = 'P';
        }

        if (empty($rawNisn) || empty($nama)) continue;

        prosesSiswa($rawNisn, $nama, $info['kelas'], $jk, $pdo, $stmtInsertSiswa, $stmtGetSiswaId, $stmtUpsertUserSiswa, $stmtUpsertUserOrtu, $password_hash, $mapKelas);
        $count++;
        $stats[$key]++;
        $stats['total_siswa']++;
        $stats['total_user_siswa']++;
        $stats['total_user_ortu']++;
    }
    fclose($h);
    echo " [✓] {$info['kelas']}: Berhasil memproses {$count} siswa & akun.\n";
}

// ========================================================
// 4. PROSES MURID BARU 2026/2027 (DIJADIKAN 1 KELAS: 'Belum Ada Kelas')
// ========================================================
echo "\n--- Memproses Murid Baru 2026/2027 ---\n";
$path_murid_baru = __DIR__ . '/csv/daftar_nama_murid_baru_2026_2027.csv';

if (file_exists($path_murid_baru)) {
    $h = fopen($path_murid_baru, 'r');
    fgetcsv($h, 0, ',', '"', '\\'); // Lewati baris 0 (Unnamed)
    fgetcsv($h, 0, ',', '"', '\\'); // Lewati baris 1 (Header nama kolom)
    $countMb = 0;

    while (($row = fgetcsv($h, 0, ',', '"', '\\')) !== false) {
        $no = trim($row[0] ?? '');
        $nisn = trim($row[2] ?? '');
        $nama = trim($row[6] ?? '');
        $jk_raw = trim($row[10] ?? '');
        $jk = (stripos($jk_raw, 'Perempuan') !== false) ? 'P' : 'L';

        if (empty($nisn) || empty($nama)) continue;

        // Kelas murid baru dijadikan 1 sebagai 'Belum Ada Kelas'
        $kelasMb = 'Belum Ada Kelas';

        prosesSiswa($nisn, $nama, $kelasMb, $jk, $pdo, $stmtInsertSiswa, $stmtGetSiswaId, $stmtUpsertUserSiswa, $stmtUpsertUserOrtu, $password_hash, $mapKelas);
        $countMb++;
        $stats['murid_baru']++;
        $stats['total_siswa']++;
        $stats['total_user_siswa']++;
        $stats['total_user_ortu']++;
    }
    fclose($h);
    echo " [✓] Murid Baru: Berhasil memproses {$countMb} siswa ke kelas 'Belum Ada Kelas'.\n";
} else {
    echo " [!] File murid baru tidak ditemukan: $path_murid_baru\n";
}

echo "\n=========================================================\n";
echo " HASIL REKAPITULASI IMPORT:\n";
echo "=========================================================\n";
echo " • Siswa Kelas 8A           : {$stats['kelas_8a']}\n";
echo " • Siswa Kelas 8B           : {$stats['kelas_8b']}\n";
echo " • Siswa Kelas 8C           : {$stats['kelas_8c']}\n";
echo " • Siswa Kelas 8D           : {$stats['kelas_8d']}\n";
echo " • Murid Baru (Belum Dibagi): {$stats['murid_baru']}\n";
echo " • TOTAL SISWA MASUK        : {$stats['total_siswa']}\n";
echo " • TOTAL AKUN SISWA DIBUAT  : {$stats['total_user_siswa']} (Username = NISN, Pass = 'smpn28bpp')\n";
echo " • TOTAL AKUN ORTU DIBUAT   : {$stats['total_user_ortu']} (Username = ORT+NISN, Pass = 'smpn28bpp')\n";
echo "=========================================================\n";

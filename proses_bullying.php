<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bullying.php');
    exit;
}

$fields = [
    'nama_pelapor',
    'kontak',
    'status_pelapor',
    'nama_korban',
    'kelas_korban',
    'jenis_bullying',
    'tanggal_kejadian',
    'deskripsi',
    'lokasi',
];

$data = [];
foreach ($fields as $field) {
    $data[$field] = trim((string) ($_POST[$field] ?? ''));
}
$data['saksi'] = trim((string) ($_POST['saksi'] ?? ''));

foreach ($fields as $field) {
    if ($data[$field] === '') {
        // Simpan error ke session agar bisa dilihat
        session_start();
        $_SESSION['error_msg'] = "Field '$field' tidak boleh kosong.";
        header('Location: bullying.php?status=error');
        exit;
    }
}

try {
    // Cek apakah tabel ada
    $pdo->query("SELECT 1 FROM laporan_bullying LIMIT 1");
} catch (PDOException $e) {
    session_start();
    $_SESSION['error_msg'] = "Tabel 'laporan_bullying' belum ada. Jalankan SQL untuk membuat tabel.";
    header('Location: bullying.php?status=error');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO laporan_bullying (
            nama_pelapor, kontak, status_pelapor, nama_korban, kelas_korban,
            jenis_bullying, tanggal_kejadian, deskripsi, lokasi, saksi
        ) VALUES (
            :nama_pelapor, :kontak, :status_pelapor, :nama_korban, :kelas_korban,
            :jenis_bullying, :tanggal_kejadian, :deskripsi, :lokasi, :saksi
        )'
    );
    $stmt->execute($data);
    header('Location: bullying.php?status=success');
} catch (PDOException $e) {
    // Simpan error asli ke session
    session_start();
    $_SESSION['error_msg'] = 'DB Error: ' . $e->getMessage();
    header('Location: bullying.php?status=error');
}
exit;
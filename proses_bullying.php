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
        header('Location: bullying.php?status=error');
        exit;
    }
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
    header('Location: bullying.php?status=error');
}
exit;

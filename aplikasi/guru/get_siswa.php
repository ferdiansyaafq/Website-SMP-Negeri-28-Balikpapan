<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$kelas_id = (int)($_GET['kelas_id'] ?? 0);

if (!$kelas_id) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, nisn, nama_siswa, jenis_kelamin
    FROM siswa
    WHERE wali_kelas_id = ?
    ORDER BY nama_siswa ASC
");
$stmt->execute([$kelas_id]);
$siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'total'   => count($siswa),
    'data'    => $siswa
]);
<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$guru_id = (int)($_GET['guru_id'] ?? ($_SESSION['guru_id'] ?? 0));
$minggu  = $_GET['minggu'] ?? date('Y-m-d');

$ts     = strtotime($minggu);
$senin  = date('Y-m-d', strtotime('monday this week', $ts));
$minggu_end = date('Y-m-d', strtotime('sunday this week', $ts));

$sql = "SELECT jg.*, k.nama_kelas
        FROM jurnal_guru jg
        LEFT JOIN kaih_kelas k ON jg.kelas_id = k.id
        WHERE jg.guru_id = ?
          AND jg.tanggal BETWEEN ? AND ?
        ORDER BY jg.tanggal ASC, jg.jam_mulai ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$guru_id, $senin, $minggu_end]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nama file
$filename = 'Jurnal_Guru_' . $senin . '_sd_' . $minggu_end . '.csv';

// Header CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// BOM agar Excel baca UTF-8 dengan benar
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

// Header kolom
fputcsv($out, [
    'No', 'Tanggal', 'Hari', 'Jam Ke-', 'Jam Mulai', 'Jam Selesai',
    'Kelas', 'Mata Pelajaran', 'Materi', 'Uraian Materi',
    'Jumlah Hadir', 'Jumlah Tidak Hadir', 'Catatan'
]);

$no = 1;
foreach ($rows as $r) {
    // Parsing catatan
    $cat = $r['catatan'] ?? '';
    preg_match('/Jam ke:\s*(.+)/', $cat, $mJam);
    preg_match('/Kehadiran:\s*(.+)/', $cat, $mHadir);
    preg_match('/Uraian:\s*(.+?)(?:\nCatatan:|$)/s', $cat, $mUraian);
    preg_match('/Catatan:\s*(.+)/s', $cat, $mCat);

    $jamKe = trim($mJam[1] ?? '-');
    $kehadiran = trim($mHadir[1] ?? '-');
    $uraian = trim($mUraian[1] ?? '-');
    $catatan = trim($mCat[1] ?? '-');

    fputcsv($out, [
        $no++,
        date('d/m/Y', strtotime($r['tanggal'])),
        date('l', strtotime($r['tanggal'])),
        $jamKe,
        substr($r['jam_mulai'], 0, 5),
        substr($r['jam_selesai'], 0, 5),
        $r['nama_kelas'] ?? '-',
        $r['mata_pelajaran'],
        $r['materi'],
        $uraian,
        $r['jumlah_hadir'],
        $r['jumlah_tidak_hadir'],
        $catatan
    ]);
}

fclose($out);
exit;
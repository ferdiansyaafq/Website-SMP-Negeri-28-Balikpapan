<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['guru', 'admin'])) {
    die("Akses ditolak. Silakan login sebagai Guru terlebih dahulu.");
}

$userRole = $_SESSION['role'] ?? '';
if ($userRole === 'guru') {
    $guru_id = (int)($_SESSION['guru_id'] ?? 0);
} else {
    $guru_id = (int)($_GET['guru_id'] ?? ($_SESSION['guru_id'] ?? 0));
}

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
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// BOM UTF-8 agar Excel membaca karakter Indonesia dengan benar
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header Kolom
fputcsv($output, [
    'No',
    'Hari / Tanggal',
    'Jam Mulai',
    'Jam Selesai',
    'Kelas',
    'Mata Pelajaran',
    'Materi / Topik',
    'Kegiatan Pembelajaran',
    'Catatan / Refleksi',
    'Jml Siswa Hadir',
    'Jml Siswa Tidak Hadir',
    'Siswa Tidak Hadir (Detail)',
]);

$hariMap = [
    'Sunday'    => 'Minggu',
    'Monday'    => 'Senin',
    'Tuesday'   => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday'  => 'Kamis',
    'Friday'    => 'Jumat',
    'Saturday'  => 'Sabtu',
];

$no = 1;
foreach ($rows as $r) {
    $dayName = $hariMap[date('l', strtotime($r['tanggal']))] ?? date('l', strtotime($r['tanggal']));
    $tglFmt  = $dayName . ', ' . date('d/m/Y', strtotime($r['tanggal']));
    $jamMulai = substr($r['jam_mulai'], 0, 5);
    $jamSelesai = $r['jam_selesai'] ? substr($r['jam_selesai'], 0, 5) : '-';

    fputcsv($output, [
        $no++,
        $tglFmt,
        $jamMulai,
        $jamSelesai,
        $r['nama_kelas'] ?? '-',
        $r['mata_pelajaran'],
        $r['materi'],
        $r['kegiatan'] ?? '-',
        $r['catatan'] ?? '-',
        $r['jumlah_hadir'],
        $r['jumlah_tidak_hadir'],
        $r['siswa_tidak_hadir'] ?? '-',
    ]);
}

fclose($output);
exit;

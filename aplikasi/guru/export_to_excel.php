<?php
// aplikasi/guru/export_excel.php
session_start();
require_once '../../config/database.php';

// Cek hak akses guru
$role = strtolower($_SESSION['portal_role'] ?? $_SESSION['role'] ?? '');
if ($role !== 'guru') {
    die("Akses ditolak. Hanya Guru yang dapat mengekspor laporan.");
}

$filterKelas = trim($_GET['kelas'] ?? '');
$filterTanggal = trim($_GET['tanggal'] ?? date('Y-m-d'));

if (empty($filterKelas)) {
    die("Silakan pilih kelas terlebih dahulu sebelum mengekspor.");
}

// Ambil Data Laporan Kelas
$stmt = $pdo->prepare('SELECT s.nisn, s.nama_siswa, lh.id AS laporan_id, lh.bangun, lh.ibadah, lh.olahraga, lh.sarapan, lh.membaca, lh.membantu, lh.menabung FROM siswa s LEFT JOIN laporan_harian lh ON lh.siswa_id = s.id AND lh.tanggal = ? WHERE s.kelas = ? ORDER BY s.nama_siswa ASC');
$stmt->execute([$filterTanggal, $filterKelas]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Header untuk memaksa browser mengunduh file sebagai Excel
header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Rekap_KAIH_Kelas_" . $filterKelas . "_" . $filterTanggal . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th colspan="11" style="font-size: 14pt; font-weight: bold; background-color: #dbeafe;">REKAP MONITORING KAIH KELAS <?= htmlspecialchars($filterKelas) ?></th>
            </tr>
            <tr>
                <th colspan="11" style="font-size: 12pt; background-color: #f1f5f9;">Tanggal: <?= htmlspecialchars($filterTanggal) ?></th>
            </tr>
            <tr>
                <th rowspan="2" style="background-color: #e2e8f0;">No</th>
                <th rowspan="2" style="background-color: #e2e8f0;">Nama Siswa</th>
                <th rowspan="2" style="background-color: #e2e8f0;">NISN</th>
                <th colspan="7" style="background-color: #e2e8f0;">Capaian 7 KAIH</th>
                <th rowspan="2" style="background-color: #e2e8f0;">Skor</th>
            </tr>
            <tr>
                <th style="background-color: #f8fafc;">Bangun Pagi</th>
                <th style="background-color: #f8fafc;">Ibadah</th>
                <th style="background-color: #f8fafc;">Olahraga</th>
                <th style="background-color: #f8fafc;">Sarapan</th>
                <th style="background-color: #f8fafc;">Membaca</th>
                <th style="background-color: #f8fafc;">Membantu Ortu</th>
                <th style="background-color: #f8fafc;">Menabung</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr>
                    <td colspan="11">Tidak ada data siswa untuk kelas ini.</td>
                </tr>
            <?php else: ?>
                <?php $no=1; foreach($data as $row): 
                    $sent = !empty($row['laporan_id']);
                    $skor = $row['bangun'] + $row['ibadah'] + $row['olahraga'] + $row['sarapan'] + $row['membaca'] + $row['membantu'] + $row['menabung'];
                ?>
                <tr>
                    <td style="text-align: center;"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                    <td>'<?= htmlspecialchars($row['nisn']) ?></td> <!-- Tanda kutip tunggal mencegah angka nol di depan hilang di Excel -->
                    <?php if ($sent): ?>
                        <td style="text-align: center;"><?= $row['bangun'] ? 'V' : '-' ?></td>
                        <td style="text-align: center;"><?= $row['ibadah'] ? 'V' : '-' ?></td>
                        <td style="text-align: center;"><?= $row['olahraga'] ? 'V' : '-' ?></td>
                        <td style="text-align: center;"><?= $row['sarapan'] ? 'V' : '-' ?></td>
                        <td style="text-align: center;"><?= $row['membaca'] ? 'V' : '-' ?></td>
                        <td style="text-align: center;"><?= $row['membantu'] ? 'V' : '-' ?></td>
                        <td style="text-align: center;"><?= $row['menabung'] ? 'V' : '-' ?></td>
                        <td style="text-align: center; font-weight: bold;"><?= $skor ?>/7</td>
                    <?php else: ?>
                        <td colspan="8" style="text-align: center; color: #666; font-style: italic;">Belum Lapor</td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
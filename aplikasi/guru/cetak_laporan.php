<?php
// aplikasi/guru/cetak_laporan.php
session_start();
require_once '../../config/database.php';

// Cek hak akses guru
$role = strtolower($_SESSION['portal_role'] ?? $_SESSION['role'] ?? '');
if ($role !== 'guru') {
    die("Akses ditolak. Hanya Guru yang dapat mencetak laporan.");
}

$filterKelas = trim($_GET['kelas'] ?? '');
$filterTanggal = trim($_GET['tanggal'] ?? date('Y-m-d'));

if (empty($filterKelas)) {
    die("Silakan pilih kelas terlebih dahulu sebelum mencetak.");
}

$bulanNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
function fmtDate($ymd) {
    global $bulanNames;
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);
    return $d ? (int)$d->format('j') . ' ' . $bulanNames[(int)$d->format('n')] . ' ' . $d->format('Y') : $ymd;
}

// Ambil Nama & NIP Guru untuk kolom Tanda Tangan
$guru_id = $_SESSION['guru_id'] ?? $_SESSION['user_id'] ?? 0;
$nama_guru = "........................................";
$nip_guru = "........................................";

if ($guru_id > 0) {
    $stmtG = $pdo->prepare("SELECT nama_guru, nip FROM guru WHERE id = ? OR nip = (SELECT username FROM users WHERE id = ?)");
    $stmtG->execute([$guru_id, $guru_id]);
    $rowG = $stmtG->fetch(PDO::FETCH_ASSOC);
    if ($rowG) {
        $nama_guru = $rowG['nama_guru'];
        $nip_guru = $rowG['nip'];
    }
}

// Ambil Data Laporan Kelas
$stmt = $pdo->prepare('SELECT s.nisn, s.nama_siswa, lh.id AS laporan_id, lh.bangun, lh.ibadah, lh.olahraga, lh.sarapan, lh.membaca, lh.membantu, lh.menabung FROM siswa s LEFT JOIN laporan_harian lh ON lh.siswa_id = s.id AND lh.tanggal = ? WHERE s.kelas = ? ORDER BY s.nama_siswa ASC');
$stmt->execute([$filterTanggal, $filterKelas]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan KAIH - Kelas <?= htmlspecialchars($filterKelas) ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; background: #fff; margin: 0; padding: 20px; }
        h2, h3, h4 { margin: 5px 0; text-align: center; }
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; text-align: center; }
        .info-bar { margin-bottom: 15px; width: 100%; display: flex; justify-content: space-between; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10pt; }
        th, td { border: 1px solid #000; padding: 6px 8px; }
        th { background-color: #f2f2f2; text-align: center; }
        .text-center { text-align: center; }
        .ttd-container { width: 100%; margin-top: 50px; display: flex; justify-content: flex-end; }
        .ttd-box { width: 250px; text-align: center; }
        .ttd-space { height: 80px; }
        
        @media print {
            @page { margin: 1.5cm; }
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom:20px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #cbd5e1; text-align: center;">
        <button onclick="window.print()" style="padding:10px 20px; background:#0284c7; color:white; border:none; cursor:pointer; font-weight:bold; border-radius: 6px; font-size: 14px;">🖨️ Cetak / Simpan PDF</button>
        <button onclick="window.close()" style="padding:10px 20px; background:#ef4444; color:white; border:none; cursor:pointer; font-weight:bold; border-radius: 6px; font-size: 14px;">❌ Tutup</button>
    </div>

    <div class="kop-surat">
        <h2>SMP NEGERI 28 BALIKPAPAN</h2>
        <h4>Jl. Mulawarman, Teritip, Kec. Balikpapan Timur, Kota Balikpapan, Kalimantan Timur</h4>
        <h3 style="margin-top:15px; text-decoration: underline;">LAPORAN MONITORING KAIH</h3>
    </div>

    <div class="info-bar">
        <span>Kelas: <?= htmlspecialchars($filterKelas) ?></span>
        <span>Tanggal: <?= fmtDate($filterTanggal) ?></span>
    </div>
    
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 30px;">No</th>
                <th rowspan="2">Nama Siswa</th>
                <th rowspan="2">NISN</th>
                <th colspan="7">Capaian 7 KAIH</th>
                <th rowspan="2">Skor</th>
            </tr>
            <tr>
                <th style="width: 40px; font-size:9pt;">Bangun</th>
                <th style="width: 40px; font-size:9pt;">Ibadah</th>
                <th style="width: 40px; font-size:9pt;">Olahraga</th>
                <th style="width: 40px; font-size:9pt;">Sarapan</th>
                <th style="width: 40px; font-size:9pt;">Membaca</th>
                <th style="width: 40px; font-size:9pt;">Membantu</th>
                <th style="width: 40px; font-size:9pt;">Menabung</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; foreach($data as $row): 
                $sent = !empty($row['laporan_id']);
                $skor = $row['bangun'] + $row['ibadah'] + $row['olahraga'] + $row['sarapan'] + $row['membaca'] + $row['membantu'] + $row['menabung'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                <td class="text-center"><?= htmlspecialchars($row['nisn']) ?></td>
                <?php if ($sent): ?>
                    <td class="text-center"><?= $row['bangun'] ? 'V' : '-' ?></td>
                    <td class="text-center"><?= $row['ibadah'] ? 'V' : '-' ?></td>
                    <td class="text-center"><?= $row['olahraga'] ? 'V' : '-' ?></td>
                    <td class="text-center"><?= $row['sarapan'] ? 'V' : '-' ?></td>
                    <td class="text-center"><?= $row['membaca'] ? 'V' : '-' ?></td>
                    <td class="text-center"><?= $row['membantu'] ? 'V' : '-' ?></td>
                    <td class="text-center"><?= $row['menabung'] ? 'V' : '-' ?></td>
                    <td class="text-center" style="font-weight:bold;"><?= $skor ?>/7</td>
                <?php else: ?>
                    <td colspan="8" class="text-center" style="color: #666; font-style: italic;">Belum Lapor</td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="ttd-container">
        <div class="ttd-box">
            <p>Balikpapan, <?= fmtDate(date('Y-m-d')) ?></p>
            <p>Mengetahui,<br>Wali Kelas <?= htmlspecialchars($filterKelas) ?></p>
            <div class="ttd-space"></div>
            <p style="text-decoration: underline; font-weight: bold;"><?= htmlspecialchars($nama_guru) ?></p>
            <p>NIP. <?= htmlspecialchars($nip_guru) ?></p>
        </div>
    </div>

</body>
</html>
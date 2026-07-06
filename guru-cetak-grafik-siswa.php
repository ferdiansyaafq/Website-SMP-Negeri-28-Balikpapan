<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['portal_user_id'], $_SESSION['portal_role'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/user_accounts.php';

function pickFirstExistingImage(array $candidates, string $fallback): string
{
    foreach ($candidates as $path) {
        $fsPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (is_file($fsPath)) {
            $v = (int) @filemtime($fsPath);
            return $path . ($v > 0 ? ('?v=' . $v) : '');
        }
    }
    return $fallback;
}

$logoSekolah = pickFirstExistingImage([
    'assets/img/logo-sekolah.png',
    'assets/img/logo-sekolah.jpg',
    'assets/img/logo-sekolah.jpeg',
    'assets/img/logo-sekolah.webp',
    'assets/img/logo-sekolah.svg',
    'assets/img/logo.png',
], 'assets/img/logo-sekolah.svg');

$kopSurat = pickFirstExistingImage([
    'assets/img/kopsurat.png',
    'assets/img/kop-surat.png',
    'assets/img/kopsurat.jpg',
    'assets/img/kopsurat.jpeg',
    'assets/img/kopsurat.webp',
], '');

function ensureLaporanHarianTable(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS laporan_harian (
        id INT AUTO_INCREMENT PRIMARY KEY,
        siswa_id INT NOT NULL,
        tanggal DATE NOT NULL,
        bangun TINYINT(1) NOT NULL DEFAULT 0,
        ibadah TINYINT(1) NOT NULL DEFAULT 0,
        ibadah_catatan VARCHAR(255) NULL,
        olahraga TINYINT(1) NOT NULL DEFAULT 0,
        olahraga_jenis VARCHAR(50) NULL,
        sarapan TINYINT(1) NOT NULL DEFAULT 0,
        sarapan_menu VARCHAR(50) NULL,
        membaca TINYINT(1) NOT NULL DEFAULT 0,
        membaca_judul VARCHAR(255) NULL,
        membaca_menit INT NULL,
        membantu TINYINT(1) NOT NULL DEFAULT 0,
        membantu_jenis VARCHAR(50) NULL,
        menabung TINYINT(1) NOT NULL DEFAULT 0,
        menabung_nominal INT NULL,
        orang_tua_validated_at DATETIME NULL,
        guru_validated_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_siswa_tanggal (siswa_id, tanggal),
        INDEX idx_tanggal (tanggal)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$conn->query($sql)) {
        throw new RuntimeException('Gagal menyiapkan tabel laporan harian.');
    }
}

function bulanIndonesia(DateTimeInterface $date): string
{
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    return ($bulan[(int) $date->format('n')] ?? $date->format('m')) . ' ' . $date->format('Y');
}

function semesterUntukBulan(int $month): int
{
    return $month >= 7 ? 2 : 1;
}

function buatRentangPeriode(string $monthParam, int $year, int $semester): array
{
    $isSemester = $semester !== 0;

    if ($isSemester) {
        $semMonths = $semester === 1 ? [1, 2, 3, 4, 5, 6] : [7, 8, 9, 10, 11, 12];
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $semMonths[0]));
        $end = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $semMonths[count($semMonths) - 1])))->modify('last day of this month');
        return [$isSemester, $monthParam, $year, $semester, $start, $end];
    }

    $monthDate = DateTimeImmutable::createFromFormat('Y-m', $monthParam) ?: new DateTimeImmutable('first day of this month');
    $monthParam = $monthDate->format('Y-m');
    $year = (int) $monthDate->format('Y');
    $start = $monthDate->modify('first day of this month')->setTime(0, 0, 0);
    $end = $monthDate->modify('last day of this month')->setTime(23, 59, 59);

    return [$isSemester, $monthParam, $year, $semester, $start, $end];
}

function statistikKosong(): array
{
    return [
        'submitted' => 0,
        'sum_score' => 0,
        'bangun' => 0,
        'ibadah' => 0,
        'olahraga' => 0,
        'sarapan' => 0,
        'membaca' => 0,
        'membantu' => 0,
        'menabung' => 0,
    ];
}

function muatStatistikLaporan(mysqli $conn, int $siswaId, string $startYmd, string $endYmd): array
{
    $stats = statistikKosong();

    $stmt = $conn->prepare('SELECT bangun, ibadah, olahraga, sarapan, membaca, membantu, menabung FROM laporan_harian WHERE siswa_id = ? AND tanggal BETWEEN ? AND ?');
    if (!$stmt) {
        throw new RuntimeException('Gagal membaca data laporan.');
    }

    $stmt->bind_param('iss', $siswaId, $startYmd, $endYmd);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $stats['submitted']++;
        $score = 0;
        foreach (['bangun', 'ibadah', 'olahraga', 'sarapan', 'membaca', 'membantu', 'menabung'] as $k) {
            $v = (int) ($r[$k] ?? 0);
            $stats[$k] += $v;
            $score += $v;
        }
        $stats['sum_score'] += $score;
    }
    $stmt->close();

    return $stats;
}

if ((string) ($_SESSION['portal_role'] ?? '') !== 'guru') {
    header('Location: logout.php');
    exit;
}

$siswaId = (int) ($_GET['siswa_id'] ?? 0);
if ($siswaId <= 0) {
    header('Location: guru-cetak-laporan.php');
    exit;
}

$monthParam = trim((string) ($_GET['month'] ?? ''));
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = (new DateTimeImmutable('today'))->format('Y-m');
}

$year = (int) ($_GET['year'] ?? (new DateTimeImmutable('today'))->format('Y'));
$semester = (int) ($_GET['semester'] ?? 0); // 0 = bulanan, 1/2 = semester
if (!in_array($semester, [0, 1, 2], true)) {
    $semester = 0;
}

[$isSemester, $monthParam, $year, $semester, $start, $end] = buatRentangPeriode($monthParam, $year, $semester);

$startYmd = $start->format('Y-m-d');
$endYmd = $end->format('Y-m-d');

$printMode = (string) ($_GET['print'] ?? '') === '1';

$conn = getConnection();
$profile = fetchPortalProfileByUserId($conn, (int) $_SESSION['portal_user_id']);
if (!$profile || (($profile['role'] ?? '') !== 'guru')) {
    $conn->close();
    header('Location: logout.php');
    exit;
}
$kelas = trim((string) ($profile['guru_kelas'] ?? ''));
if ($kelas === '') {
    $conn->close();
    header('Location: logout.php');
    exit;
}

$student = null;
$stats = statistikKosong();
$autoAdjustedNotice = '';

try {
    ensureLaporanHarianTable($conn);

    $stmtS = $conn->prepare('SELECT id, nisn, nama_siswa, kelas FROM siswa WHERE id = ? AND kelas = ? LIMIT 1');
    if (!$stmtS) {
        throw new RuntimeException('Gagal membaca data siswa.');
    }
    $stmtS->bind_param('is', $siswaId, $kelas);
    $stmtS->execute();
    $student = $stmtS->get_result()->fetch_assoc() ?: null;
    $stmtS->close();

    if (!$student) {
        throw new RuntimeException('Siswa tidak ditemukan di kelas Anda.');
    }

    $stats = muatStatistikLaporan($conn, $siswaId, $startYmd, $endYmd);

    if ($stats['submitted'] === 0) {
        $stmtLatest = $conn->prepare(
            'SELECT MAX(lh.tanggal) AS latest_tanggal
             FROM laporan_harian lh
             JOIN siswa s ON s.id = lh.siswa_id
             WHERE lh.siswa_id = ? AND s.kelas = ?'
        );
        if (!$stmtLatest) {
            throw new RuntimeException('Gagal membaca periode laporan terbaru.');
        }

        $stmtLatest->bind_param('is', $siswaId, $kelas);
        $stmtLatest->execute();
        $latestRow = $stmtLatest->get_result()->fetch_assoc() ?: null;
        $stmtLatest->close();

        $latestTanggal = trim((string) ($latestRow['latest_tanggal'] ?? ''));
        if ($latestTanggal !== '') {
            $latestDate = new DateTimeImmutable($latestTanggal);
            $requestedLabel = $isSemester
                ? (($semester === 1 ? 'Semester 1 (Jan–Jun)' : 'Semester 2 (Jul–Des)') . ' • ' . $year)
                : bulanIndonesia($start);

            if ($isSemester) {
                $year = (int) $latestDate->format('Y');
                $semester = semesterUntukBulan((int) $latestDate->format('n'));
                $monthParam = $latestDate->format('Y-m');
            } else {
                $monthParam = $latestDate->format('Y-m');
                $year = (int) $latestDate->format('Y');
                $semester = 0;
            }

            [$isSemester, $monthParam, $year, $semester, $start, $end] = buatRentangPeriode($monthParam, $year, $semester);
            $startYmd = $start->format('Y-m-d');
            $endYmd = $end->format('Y-m-d');
            $stats = muatStatistikLaporan($conn, $siswaId, $startYmd, $endYmd);

            if ($stats['submitted'] > 0) {
                $adjustedLabel = $isSemester
                    ? (($semester === 1 ? 'Semester 1 (Jan–Jun)' : 'Semester 2 (Jul–Des)') . ' • ' . $year)
                    : bulanIndonesia($start);
                $autoAdjustedNotice = 'Periode ' . $requestedLabel . ' belum punya data. Grafik otomatis dipindahkan ke ' . $adjustedLabel . ' agar laporan terbaru langsung tampil.';
            }
        }
    }
} catch (Throwable $e) {
    $err = $e->getMessage();
} finally {
    $conn->close();
}

$bulanLabel = bulanIndonesia($start);
$periodeLabel = $isSemester
    ? (($semester === 1 ? 'Semester 1 (Jan–Jun)' : 'Semester 2 (Jul–Des)') . ' • ' . $year)
    : $bulanLabel;
$portalName = (string) ($profile['display_name'] ?? 'Guru');
$avgScore = $stats['submitted'] > 0 ? ((float) $stats['sum_score'] / (float) $stats['submitted']) : 0.0;
$overallPct = max(0.0, min(100.0, ($avgScore / 7.0) * 100.0));
$portalInitial = strtoupper(substr($portalName !== '' ? $portalName : 'G', 0, 1));

$items = [
    'bangun' => 'Bangun Pagi',
    'ibadah' => 'Ibadah',
    'olahraga' => 'Olahraga',
    'sarapan' => 'Sarapan',
    'membaca' => 'Membaca',
    'membantu' => 'Membantu Ortu',
    'menabung' => 'Menabung',
];

$barColors = [
    'bangun'   => ['#e8a87c', '#d4956a'],   // gold
    'ibadah'   => ['#a2a1fb', '#8b8af0'],   // lavender
    'olahraga' => ['#c4b5fd', '#8b5cf6'],   // violet
    'sarapan'  => ['#e8a87c', '#c4956a'],   // warm gold
    'membaca'  => ['#a2a1fb', '#7c7bf0'],   // lavender deep
    'membantu' => ['#d4956a', '#b87d58'],   // copper
    'menabung' => ['#b48af7', '#9061e0'],   // purple
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Grafik Siswa — KAIH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="assets/css/style.css?v=20260324">
    <link rel="stylesheet" href="assets/css/portal.css?v=20260324">
    <link rel="stylesheet" href="assets/css/report-pages.css?v=20260324">
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 8mm 10mm;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            body.report-body {
                background: #fff !important;
                color: #111827 !important;
            }
            body.report-body::before,
            body.report-body::after {
                display: none !important;
            }
            .report-topbar,
            .hero-grid,
            .summary-grid,
            .print-actions {
                display: none !important;
            }
            .report-shell {
                max-width: none;
                padding: 0;
            }
            .print-document-header {
                display: block !important;
            }
            .section-card,
            .report-toolbar,
            .report-toolbar-copy,
            .chart-panel,
            .activity-grid {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .report-card,
            .summary-card {
                border: none !important;
                box-shadow: none !important;
                background: #fff !important;
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
            }
            .print-kop {
                display: block !important;
            }
            .chart-panel,
            .metric-bar {
                border-color: rgba(17, 24, 39, 0.16) !important;
                background: #fff !important;
                box-shadow: none !important;
            }
            .print-sheet,
            .chart-panel,
            .print-meta-grid,
            .print-meta-item,
            .activity-row {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .report-card {
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-kop {
                margin-bottom: 10px !important;
            }
            .print-kop img {
                padding-bottom: 6px !important;
            }
            .print-document-header {
                margin-bottom: 10px !important;
            }
            .print-document-title {
                font-size: 22px !important;
                line-height: 1.1 !important;
            }
            .print-document-subtitle {
                margin-top: 4px !important;
                font-size: 11px !important;
                line-height: 1.45 !important;
            }
            .print-meta-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                gap: 6px !important;
                margin-top: 10px !important;
            }
            .print-meta-item {
                padding: 8px 10px !important;
                border-radius: 10px !important;
            }
            .print-meta-item strong {
                margin-bottom: 2px !important;
                font-size: 10px !important;
                letter-spacing: 0.8px !important;
            }
            .print-meta-item span {
                font-size: 11px !important;
            }
            .report-toolbar {
                margin: 0 !important;
                padding: 0 !important;
            }
            .report-toolbar-copy h3 {
                font-size: 18px !important;
                margin: 0 !important;
            }
            .report-toolbar-copy p,
            .report-toolbar-copy .section-kicker {
                margin: 2px 0 0 !important;
                line-height: 1.35 !important;
            }
            .chart-panel {
                margin-top: 10px !important;
                padding: 12px 12px 10px !important;
            }
            .activity-grid {
                gap: 8px !important;
                margin-top: 8px !important;
            }
            .activity-row {
                grid-template-columns: 112px minmax(0, 1fr) 42px !important;
                gap: 8px !important;
            }
            .activity-name,
            .activity-pct {
                font-size: 11px !important;
            }
            .metric-bar {
                min-width: 0 !important;
                height: 8px !important;
            }
            .metric-value {
                min-width: 42px !important;
                font-size: 11px !important;
            }
            .title-stack h1,
            .summary-value,
            .meter-copy h3,
            .report-toolbar-copy h3,
            .activity-title,
            .metric-value {
                color: #111827 !important;
                text-shadow: none !important;
            }
            .title-stack p,
            .summary-note,
            .report-toolbar-copy p,
            .meter-copy p,
            .chart-label,
            .table-subtitle,
            .stat-chip,
            .legend-pill {
                color: #4b5563 !important;
            }
            .print-document-title,
            .print-meta-item span,
            .print-meta-item strong,
            .activity-name,
            .activity-pct {
                color: #111827 !important;
                text-shadow: none !important;
            }
            .print-document-subtitle {
                color: #4b5563 !important;
            }
        }

        .back-link {
            align-self: flex-start;
        }

        .print-kop {
            display: none;
            width: 100%;
            margin: 0 0 18px;
        }

        .print-kop img {
            width: 100%;
            height: auto;
            display: block;
            border-bottom: 2px solid var(--report-line-strong, rgba(162,161,251,0.18));
            padding-bottom: 10px;
        }

        .activity-grid {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }

        .activity-row {
            display: grid;
            grid-template-columns: minmax(120px, 180px) minmax(0, 1fr) 56px;
            gap: 12px;
            align-items: center;
        }

        .activity-name {
            font-size: 13px;
            font-weight: 800;
            color: var(--report-text);
        }

        .activity-pct {
            font-size: 12px;
            font-weight: 800;
            color: var(--report-text);
            text-align: right;
        }

        @media (max-width: 720px) {
            .activity-row {
                grid-template-columns: 1fr;
            }

            .activity-pct {
                text-align: left;
            }
        }
    </style>
</head>
<body class="report-body role-guru"<?php echo $printMode ? ' onload="window.print()"' : ''; ?>>
<div class="report-shell">
    <header class="report-topbar">
        <div class="topbar-main">
            <div class="brand-row">
                <a class="chip-link back-link print-actions" href="guru-cetak-laporan.php?month=<?php echo urlencode($monthParam); ?>&year=<?php echo (int) $year; ?>&semester=<?php echo (int) ($semester === 0 ? 1 : $semester); ?>">Kembali</a>
                <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo Sekolah" class="brand-logo">
                <div class="title-stack">
                    <p class="title-eyebrow">Grafik Kegiatan Siswa</p>
                    <h1>Lihat detail kegiatan <?php echo htmlspecialchars((string) ($student['nama_siswa'] ?? 'Siswa')); ?> dalam gaya frontend.</h1>
                    <p>Grafik ini tetap mengambil data siswa yang sama untuk tampilan bulanan atau semester, lalu disiapkan langsung untuk dicetak atau disimpan sebagai PDF.</p>
                </div>
            </div>
        </div>
        <div class="top-actions print-actions">
            <div class="chip-static">
                <span class="chip-avatar"><?php echo htmlspecialchars($portalInitial); ?></span>
                <span><?php echo htmlspecialchars($portalName); ?></span>
            </div>
            <a class="chip-link primary" href="guru-cetak-grafik-siswa.php?siswa_id=<?php echo (int) $siswaId; ?>&month=<?php echo urlencode($monthParam); ?>&year=<?php echo (int) $year; ?>&semester=<?php echo (int) $semester; ?>&print=1">Cetak / Simpan PDF</a>
        </div>
    </header>

    <?php if (!empty($err ?? '')): ?>
        <div class="report-flash error"><?php echo htmlspecialchars($err); ?></div>
    <?php elseif ($autoAdjustedNotice !== ''): ?>
        <div class="report-flash success"><?php echo htmlspecialchars($autoAdjustedNotice); ?></div>
    <?php else: ?>
        <section class="hero-grid">
            <article class="report-card hero-card print-sheet">
                <?php if (!empty($kopSurat)): ?>
                    <div class="print-kop" aria-label="Kop surat">
                        <img src="<?php echo htmlspecialchars($kopSurat); ?>" alt="Kop Surat">
                    </div>
                <?php endif; ?>
                <p class="section-kicker">Ringkasan Siswa</p>
                <h2><?php echo htmlspecialchars($periodeLabel); ?> menunjukkan konsistensi kegiatan <?php echo htmlspecialchars((string) ($student['nama_siswa'] ?? 'Siswa')); ?>.</h2>
                <p>Persentase di bawah ini dihitung dari rata-rata skor 7 kegiatan per hari, sehingga guru bisa cepat melihat kebiasaan yang paling konsisten dilakukan siswa.</p>
                <div class="hero-meta">
                    <span class="pill sent">NISN: <?php echo htmlspecialchars((string) ($student['nisn'] ?? '')); ?></span>
                    <span class="pill pending">Laporan: <?php echo (int) $stats['submitted']; ?> hari</span>
                    <span class="pill done">Rata-rata: <?php echo htmlspecialchars(number_format($avgScore, 2, ',', '.')); ?>/7</span>
                </div>
            </article>

            <article class="report-card summary-card meter-card">
                <div class="meter-copy">
                    <span class="summary-label">Progres Umum</span>
                    <h3>Persentase rata-rata kegiatan</h3>
                    <p>Angka ini menunjukkan porsi keterisian kegiatan siswa pada periode yang sedang dilihat.</p>
                </div>
                <div class="meter-ring" style="--progress-deg: <?php echo (int) round(($overallPct / 100) * 360); ?>deg;">
                    <span><?php echo (int) round($overallPct); ?>%</span>
                </div>
            </article>
        </section>

        <section class="summary-grid" aria-label="Identitas dan ringkasan siswa">
            <article class="summary-card">
                <span class="summary-label">Nama Siswa</span>
                <div class="summary-value"><?php echo htmlspecialchars((string) ($student['nama_siswa'] ?? '')); ?></div>
                <div class="summary-note">Data siswa yang dipilih dari kelas <?php echo htmlspecialchars($kelas); ?>.</div>
            </article>
            <article class="summary-card">
                <span class="summary-label">Periode</span>
                <div class="summary-value"><?php echo htmlspecialchars($periodeLabel); ?></div>
                <div class="summary-note">Mode tampil menyesuaikan bulan atau semester yang dipilih guru.</div>
            </article>
            <article class="summary-card">
                <span class="summary-label">Total Laporan</span>
                <div class="summary-value"><?php echo (int) $stats['submitted']; ?> hari</div>
                <div class="summary-note">Jumlah hari yang memiliki data laporan kegiatan.</div>
            </article>
            <article class="summary-card">
                <span class="summary-label">Persentase Umum</span>
                <div class="summary-value"><?php echo (int) round($overallPct); ?>%</div>
                <div class="summary-note">Dihitung dari rata-rata skor per hari pada skala 7 kegiatan.</div>
            </article>
        </section>

        <section class="report-card section-card" aria-label="Grafik kegiatan siswa">
            <div class="print-document-header avoid-break">
                <?php if (!empty($kopSurat)): ?>
                    <div class="print-kop" aria-label="Kop surat">
                        <img src="<?php echo htmlspecialchars($kopSurat); ?>" alt="Kop Surat">
                    </div>
                <?php endif; ?>
                <h2 class="print-document-title">Grafik Kegiatan Siswa</h2>
                <p class="print-document-subtitle">Ringkasan kegiatan siswa untuk periode <?php echo htmlspecialchars($periodeLabel); ?>. Persentase menunjukkan frekuensi tiap kebiasaan dibanding total hari laporan.</p>
                <div class="print-meta-grid">
                    <div class="print-meta-item">
                        <strong>Nama Siswa</strong>
                        <span><?php echo htmlspecialchars((string) ($student['nama_siswa'] ?? '')); ?></span>
                    </div>
                    <div class="print-meta-item">
                        <strong>NISN</strong>
                        <span><?php echo htmlspecialchars((string) ($student['nisn'] ?? '')); ?></span>
                    </div>
                    <div class="print-meta-item">
                        <strong>Periode</strong>
                        <span><?php echo htmlspecialchars($periodeLabel); ?></span>
                    </div>
                    <div class="print-meta-item">
                        <strong>Rata-rata</strong>
                        <span><?php echo htmlspecialchars(number_format($avgScore, 2, ',', '.')); ?>/7 • <?php echo (int) round($overallPct); ?>%</span>
                    </div>
                </div>
            </div>

            <div class="report-toolbar">
                <div class="report-toolbar-copy">
                    <p class="section-kicker">Detail Kebiasaan</p>
                    <h3>Persentase per kegiatan</h3>
                    <p>Batang menunjukkan seberapa sering masing-masing kegiatan dilakukan dibanding total hari laporan pada periode ini.</p>
                </div>
            </div>

            <div class="chart-panel avoid-break" style="margin-top: 18px;">
                <div class="activity-grid" aria-label="Grafik persentase per kegiatan">
                    <?php foreach ($items as $key => $label): ?>
                        <?php
                            $pct = $stats['submitted'] > 0 ? ((float) ($stats[$key] ?? 0) / (float) $stats['submitted']) * 100.0 : 0.0;
                            $pct = max(0.0, min(100.0, $pct));
                            $c = $barColors[$key] ?? ['#e8a87c', '#d4956a'];
                            $grad = 'linear-gradient(135deg, ' . $c[0] . ', ' . $c[1] . ')';
                        ?>
                        <div class="activity-row">
                            <div class="activity-name"><?php echo htmlspecialchars($label); ?></div>
                            <div class="metric-bar"><span style="width: <?php echo (int) round($pct); ?>%; background: <?php echo htmlspecialchars($grad); ?>;"></span></div>
                            <div class="activity-pct"><?php echo (int) round($pct); ?>%</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</div>
</body>
</html>


<?php
session_start();

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

if ((string) ($_SESSION['portal_role'] ?? '') !== 'guru') {
    header('Location: logout.php');
    exit;
}

$conn = getConnection();
$profile = fetchPortalProfileByUserId($conn, (int) $_SESSION['portal_user_id']);
if (!$profile) {
    $conn->close();
    unset($_SESSION['portal_user_id'], $_SESSION['portal_role'], $_SESSION['portal_display_name'], $_SESSION['portal_login_time']);
    header('Location: index.php');
    exit;
}

if (($profile['role'] ?? '') !== 'guru') {
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

$today = new DateTimeImmutable('today');
$defaultSemester = ((int) $today->format('n')) >= 7 ? 2 : 1;

$monthParam = trim((string) ($_GET['month'] ?? ''));
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = $today->format('Y-m');
}

$year = (int) ($_GET['year'] ?? $today->format('Y'));
$semester = (int) ($_GET['semester'] ?? $defaultSemester);
if (!in_array($semester, [1, 2], true)) {
    $semester = $defaultSemester;
}

$monthDate = DateTimeImmutable::createFromFormat('Y-m', $monthParam) ?: new DateTimeImmutable('first day of this month');
$start = $monthDate->modify('first day of this month')->setTime(0, 0, 0);
$end = $monthDate->modify('last day of this month')->setTime(23, 59, 59);
$startYmd = $start->format('Y-m-d');
$endYmd = $end->format('Y-m-d');

$students = [];
$summary = [];
$semesterSeries = [];

try {
    ensureLaporanHarianTable($conn);

    // list siswa
    $stmtS = $conn->prepare('SELECT id, nisn, nama_siswa, kelas FROM siswa WHERE kelas = ? ORDER BY nama_siswa ASC');
    if ($stmtS) {
        $stmtS->bind_param('s', $kelas);
        if ($stmtS->execute()) {
            $res = $stmtS->get_result();
            while ($r = $res->fetch_assoc()) {
                $students[(int) $r['id']] = $r;
                $summary[(int) $r['id']] = [
                    'terkirim' => 0,
                    'valid_any' => 0,
                    'valid_ortu' => 0,
                    'valid_guru' => 0,
                    'score_sum' => 0,
                ];
            }
        }
        $stmtS->close();
    }

    // laporan per kelas per bulan
    $stmt = $conn->prepare(
        'SELECT lh.*
         FROM laporan_harian lh
         JOIN siswa s ON s.id = lh.siswa_id
         WHERE s.kelas = ? AND lh.tanggal BETWEEN ? AND ?'
    );
    if ($stmt) {
        $stmt->bind_param('sss', $kelas, $startYmd, $endYmd);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $sid = (int) ($r['siswa_id'] ?? 0);
                if (!isset($summary[$sid])) continue;
                $score = (int) ($r['bangun'] ?? 0)
                    + (int) ($r['ibadah'] ?? 0)
                    + (int) ($r['olahraga'] ?? 0)
                    + (int) ($r['sarapan'] ?? 0)
                    + (int) ($r['membaca'] ?? 0)
                    + (int) ($r['membantu'] ?? 0)
                    + (int) ($r['menabung'] ?? 0);
                $summary[$sid]['terkirim']++;
                $summary[$sid]['score_sum'] += $score;
                $ortuOk = !empty($r['orang_tua_validated_at']);
                $guruOk = !empty($r['guru_validated_at']);
                if ($ortuOk) $summary[$sid]['valid_ortu']++;
                if ($guruOk) $summary[$sid]['valid_guru']++;
                if ($ortuOk || $guruOk) $summary[$sid]['valid_any']++;
            }
        }
        $stmt->close();
    }

    // ===== Semester series (6 bulan) untuk cetak =====
    // Smt 1: Jul-Dec, Smt 2: Jan-Jun
    $semMonths = $semester === 1 ? [1, 2, 3, 4, 5, 6] : [7, 8, 9, 10, 11, 12];
    $semStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $semMonths[0]));
    $semEnd = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $semMonths[count($semMonths) - 1])))->modify('last day of this month');
    $semStartYmd = $semStart->format('Y-m-d');
    $semEndYmd = $semEnd->format('Y-m-d');

    $semAgg = [];
    foreach ($semMonths as $m) {
        $semAgg[$m] = ['submitted' => 0, 'validated' => 0, 'score_sum' => 0, 'rows' => 0];
    }

    $stmtSem = $conn->prepare(
        'SELECT lh.*
         FROM laporan_harian lh
         JOIN siswa s ON s.id = lh.siswa_id
         WHERE s.kelas = ? AND lh.tanggal BETWEEN ? AND ?
         ORDER BY lh.tanggal ASC'
    );
    if ($stmtSem) {
        $stmtSem->bind_param('sss', $kelas, $semStartYmd, $semEndYmd);
        if ($stmtSem->execute()) {
            $resSem = $stmtSem->get_result();
            while ($r = $resSem->fetch_assoc()) {
                $tgl = (string) ($r['tanggal'] ?? '');
                if ($tgl === '') continue;
                $m = (int) substr($tgl, 5, 2);
                if (!isset($semAgg[$m])) continue;
                $score = (int) ($r['bangun'] ?? 0)
                    + (int) ($r['ibadah'] ?? 0)
                    + (int) ($r['olahraga'] ?? 0)
                    + (int) ($r['sarapan'] ?? 0)
                    + (int) ($r['membaca'] ?? 0)
                    + (int) ($r['membantu'] ?? 0)
                    + (int) ($r['menabung'] ?? 0);
                $semAgg[$m]['submitted']++;
                $semAgg[$m]['score_sum'] += $score;
                $semAgg[$m]['rows']++;
                if (!empty($r['guru_validated_at']) || !empty($r['orang_tua_validated_at'])) {
                    $semAgg[$m]['validated']++;
                }
            }
        }
        $stmtSem->close();
    }

    $labelMap = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
    foreach ($semMonths as $m) {
        $rows = (int) ($semAgg[$m]['rows'] ?? 0);
        $avg = $rows > 0 ? ((float) $semAgg[$m]['score_sum'] / (float) $rows) : 0.0;
        $semesterSeries[] = [
            'month' => $m,
            'label' => $labelMap[$m] ?? (string) $m,
            'avg' => $avg,
            'submitted' => (int) ($semAgg[$m]['submitted'] ?? 0),
            'validated' => (int) ($semAgg[$m]['validated'] ?? 0),
        ];
    }
} catch (Throwable $e) {
    // ignore
} finally {
    $conn->close();
}

$portalName = (string) ($profile['display_name'] ?? 'Guru');
$bulanLabel = bulanIndonesia($start);
$semesterLabel = $semester === 1 ? 'Semester 1 (Jan–Jun)' : 'Semester 2 (Jul–Des)';
$printMode = (string) ($_GET['print'] ?? '') === '1';
$kelasTitle = preg_replace('/^Kelas\s+/i', '', $kelas);
$totalStudents = count($students);
$studentsReported = 0;
$totalSubmitted = 0;
$totalValidAny = 0;
$totalValidOrtu = 0;
$totalValidGuru = 0;
$classScoreSum = 0;
$classScoreRows = 0;

foreach ($summary as $agg) {
    $terkirim = (int) ($agg['terkirim'] ?? 0);
    if ($terkirim > 0) {
        $studentsReported++;
    }
    $totalSubmitted += $terkirim;
    $totalValidAny += (int) ($agg['valid_any'] ?? 0);
    $totalValidOrtu += (int) ($agg['valid_ortu'] ?? 0);
    $totalValidGuru += (int) ($agg['valid_guru'] ?? 0);
    $classScoreSum += (int) ($agg['score_sum'] ?? 0);
    $classScoreRows += $terkirim;
}

$classAverage = $classScoreRows > 0 ? ($classScoreSum / $classScoreRows) : 0.0;
$portalInitial = strtoupper(substr($portalName !== '' ? $portalName : 'G', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan — KAIH</title>
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
            .print-actions,
            .hero-grid,
            .summary-grid,
            .screen-only {
                display: none !important;
            }
            .report-shell {
                max-width: none;
                padding: 0;
            }
            .print-document-header,
            .print-only {
                display: block !important;
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
            .table-wrap,
            .metric-bar {
                border-color: rgba(17, 24, 39, 0.16) !important;
                background: #fff !important;
                box-shadow: none !important;
            }
            .print-sheet,
            .chart-panel,
            .print-meta-grid,
            .print-meta-item {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .report-card {
                padding: 0 !important;
                margin: 0 !important;
            }
            .section-card,
            .report-toolbar,
            .report-toolbar-copy,
            .chart-panel,
            .line-chart-wrap,
            .chart-legend,
            .print-only {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .section-kicker,
            .chart-legend,
            .print-only .table-subtitle {
                margin-top: 0 !important;
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
            .chart-legend {
                gap: 6px !important;
                margin-bottom: 8px !important;
            }
            .legend-pill {
                padding: 5px 8px !important;
                font-size: 10px !important;
            }
            .line-chart-svg .line-stroke {
                stroke: #6366f1 !important;
            }
            .line-chart-svg .area-fill {
                fill: #6366f1 !important;
                opacity: .1;
            }
            .line-chart-svg .dot {
                fill: #6366f1 !important;
            }
            .line-chart-svg .dot.validated {
                fill: #22c55e !important;
            }
            .line-chart-svg .grid-line {
                stroke: #e5e7eb !important;
            }
            .line-chart-svg .grid-text,
            .line-chart-svg .x-label {
                fill: #4b5563 !important;
            }
            .print-only {
                margin-top: 10px !important;
            }
            .print-only .table-subtitle {
                font-size: 10px !important;
                line-height: 1.35 !important;
            }
            .report-table th,
            .report-table td,
            .chart-label,
            .summary-label,
            .summary-note,
            .table-subtitle,
            .report-toolbar-copy p,
            .title-stack p,
            .hero-card p,
            .meter-copy p,
            .legend-pill {
                color: #4b5563 !important;
            }
            .title-stack h1,
            .summary-value,
            .report-toolbar-copy h3,
            .table-title,
            .report-table th,
            .report-table td,
            .metric-value,
            .hero-card h2,
            .meter-copy h3 {
                color: #111827 !important;
                text-shadow: none !important;
            }
            .pill,
            .legend-pill,
            .chip-static,
            .stat-chip,
            .print-meta-item {
                border-color: rgba(17, 24, 39, 0.12) !important;
                background: #fff !important;
            }
            .metric-bar span {
                box-shadow: none !important;
            }
            .print-document-title,
            .print-meta-item span,
            .print-meta-item strong {
                color: #111827 !important;
            }
            .print-document-subtitle {
                color: #4b5563 !important;
            }

            /* ── Daftar siswa (print): tampil setelah grafik ── */
            .daftar-section {
                display: block !important;
                margin-top: 18px !important;
                page-break-before: always;
            }
            .daftar-section .daftar-print-title {
                display: block !important;
            }
            .daftar-section .table-actions,
            .daftar-section .col-aksi {
                display: none !important;
            }
            .daftar-section .table-wrap {
                overflow: visible !important;
            }
            .daftar-section .report-table {
                min-width: 0 !important;
                width: 100% !important;
                font-size: 11px !important;
            }
            .daftar-section .report-table th,
            .daftar-section .report-table td {
                padding: 6px 8px !important;
                white-space: normal !important;
            }
            .daftar-section .report-table th {
                background: #f3f4f6 !important;
                font-size: 10px !important;
            }
            .daftar-section .pill {
                font-size: 10px !important;
                padding: 2px 6px !important;
            }
            .daftar-section .table-subtitle {
                font-size: 9px !important;
            }
            .daftar-section .metric-bar {
                height: 6px !important;
                min-width: 60px !important;
            }
            .daftar-section .metric-value {
                font-size: 10px !important;
                min-width: 32px !important;
            }
        }

        .summary-card .metric-inline {
            margin-top: 12px;
        }

        .table-wrap .report-table {
            min-width: 920px;
        }

        @media (max-width: 720px) {
            .table-wrap .report-table {
                min-width: 760px;
            }
        }
    </style>
</head>
<body class="report-body role-guru"<?php echo $printMode ? ' onload="window.print()"' : ''; ?>>
    <div class="report-shell">
        <header class="report-topbar">
            <div class="topbar-main">
                <div class="brand-row">
                    <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo Sekolah" class="brand-logo">
                    <div class="title-stack">
                        <p class="title-eyebrow">Cetak Laporan Guru</p>
                        <h1>Rekap kelas <?php echo htmlspecialchars((string) $kelasTitle); ?></h1>
                    </div>
                </div>
                <div class="quick-nav" aria-label="Menu Guru">
                    <a href="guru-validasi.php">Validasi</a>
                    <a href="guru-grafik-semester.php">Grafik Semester</a>
                    <a class="active" href="guru-cetak-laporan.php">Cetak Laporan</a>
                </div>
            </div>
            <div class="top-actions">
                <div class="chip-static">
                    <span class="chip-avatar"><?php echo htmlspecialchars($portalInitial); ?></span>
                    <span><?php echo htmlspecialchars($portalName); ?></span>
                </div>
            </div>
        </header>

        <section class="report-card validation-summary-card teacher-summary-card" aria-label="Ringkasan cetak laporan">
            <div class="teacher-summary-head">
                <div class="hero-meta validation-summary-chips teacher-summary-chips">
                    <span class="pill sent">Siswa aktif: <?php echo (int) $studentsReported; ?>/<?php echo (int) $totalStudents; ?></span>
                    <span class="pill done">Validasi total: <?php echo (int) $totalValidAny; ?></span>
                    <span class="pill pending">Rata-rata kelas: <?php echo htmlspecialchars(number_format($classAverage, 1, ',', '.')); ?>/7</span>
                </div>
                <form class="filter-form teacher-summary-form" method="get" action="">
                    <select name="year">
                        <?php for ($yy = ((int) date('Y') - 1); $yy <= ((int) date('Y') + 1); $yy++): ?>
                            <option value="<?php echo (int) $yy; ?>" <?php echo (int) $year === (int) $yy ? 'selected' : ''; ?>><?php echo (int) $yy; ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="semester">
                        <option value="1" <?php echo $semester === 1 ? 'selected' : ''; ?>>Semester 1</option>
                        <option value="2" <?php echo $semester === 2 ? 'selected' : ''; ?>>Semester 2</option>
                    </select>
                    <button type="submit" class="primary-btn">Tampilkan</button>
                </form>
            </div>
            <div class="teacher-summary-actions">
                <a class="chip-link primary" href="guru-cetak-laporan.php?year=<?php echo (int) $year; ?>&semester=<?php echo (int) $semester; ?>&print=1">Cetak / Simpan PDF</a>
                <span class="stat-chip"><strong>Tip</strong>Pilih Save as PDF di dialog print.</span>
            </div>
            <div class="validation-summary-grid teacher-summary-grid summary-grid">
                <article class="validation-summary-item teacher-summary-item">
                    <span class="summary-label">Periode</span>
                    <div class="summary-value"><?php echo htmlspecialchars($semesterLabel . ' • ' . $year); ?></div>
                </article>
                <article class="validation-summary-item teacher-summary-item">
                    <span class="summary-label">Total Siswa</span>
                    <div class="summary-value"><?php echo (int) $totalStudents; ?></div>
                </article>
                <article class="validation-summary-item teacher-summary-item">
                    <span class="summary-label">Laporan Terkirim</span>
                    <div class="summary-value"><?php echo (int) $totalSubmitted; ?></div>
                </article>
                <article class="validation-summary-item teacher-summary-item">
                    <span class="summary-label">Validasi Ortu / Guru</span>
                    <div class="summary-value"><?php echo (int) $totalValidOrtu; ?> / <?php echo (int) $totalValidGuru; ?></div>
                </article>
                <article class="validation-summary-item teacher-summary-item">
                    <span class="summary-label">Rata-rata Kelas</span>
                    <div class="summary-value"><?php echo htmlspecialchars(number_format($classAverage, 1, ',', '.')); ?>/7</div>
                </article>
            </div>
        </section>

        <section class="report-card section-card print-sheet print-sheet-grafik" aria-label="Grafik semester cetak">
            <?php if (!empty($kopSurat)): ?>
                <div class="print-kop" aria-label="Kop surat">
                    <img src="<?php echo htmlspecialchars($kopSurat); ?>" alt="Kop Surat">
                </div>
            <?php endif; ?>

            <div class="print-document-header avoid-break">
                <h2 class="print-document-title">Rekap Laporan Kegiatan Kelas <?php echo htmlspecialchars((string) $kelasTitle); ?></h2>
                <p class="print-document-subtitle">Dokumen rekap guru untuk <?php echo htmlspecialchars($semesterLabel . ' ' . $year); ?>. Grafik menampilkan rata-rata skor kegiatan per bulan dengan penanda validasi.</p>
                <div class="print-meta-grid">
                    <div class="print-meta-item">
                        <strong>Kelas</strong>
                        <span><?php echo htmlspecialchars($kelas); ?></span>
                    </div>
                    <div class="print-meta-item">
                        <strong>Wali / Guru</strong>
                        <span><?php echo htmlspecialchars($portalName); ?></span>
                    </div>
                    <div class="print-meta-item">
                        <strong>Total Siswa</strong>
                        <span><?php echo (int) $totalStudents; ?> siswa</span>
                    </div>
                    <div class="print-meta-item">
                        <strong>Rata-rata Kelas</strong>
                        <span><?php echo htmlspecialchars(number_format($classAverage, 1, ',', '.')); ?>/7</span>
                    </div>
                </div>
            </div>

            <div class="report-toolbar">
                <div class="report-toolbar-copy">
                    <p class="section-kicker">Siap Cetak</p>
                    <h3>Grafik semester kelas <?php echo htmlspecialchars((string) $kelasTitle); ?></h3>
                    <p><?php echo htmlspecialchars($semesterLabel . ' • ' . $year); ?>. Garis menunjukkan rata-rata skor kegiatan dari 0 sampai 7, dan titik hijau menunjukkan sudah ada validasi.</p>
                </div>
            </div>

            <div class="chart-panel avoid-break" style="margin-top: 18px;">
                <div class="chart-legend" aria-label="Legenda grafik semester">
                    <span class="legend-pill"><span class="legend-dot"></span> Rata-rata skor</span>
                    <span class="legend-pill"><span class="legend-dot validated"></span> Sudah divalidasi</span>
                </div>
                <div class="line-chart-wrap" role="img" aria-label="Grafik rata-rata skor per bulan semester">
                    <?php
                    $cW = 500; $cH = 200; $cPL = 30; $cPR = 10; $cPT = 15; $cPB = 28;
                    $cPlotW = $cW - $cPL - $cPR;
                    $cPlotH = $cH - $cPT - $cPB;
                    $cN = count($semesterSeries);
                    $cStep = $cN > 1 ? $cPlotW / ($cN - 1) : 0;
                    $cPts = []; $cDots = [];
                    foreach ($semesterSeries as $i => $p) {
                        $x = $cPL + ($i * $cStep);
                        $avg = (float)($p['avg'] ?? 0);
                        $y = $cPT + $cPlotH - ($cPlotH * min(1, $avg / 7.0));
                        $cPts[] = round($x,1).','.round($y,1);
                        $cDots[] = ['x'=>$x,'y'=>$y,'avg'=>$avg,'label'=>$p['label']??'','sub'=>(int)($p['submitted']??0),'val'=>(int)($p['validated']??0)];
                    }
                    $cPoly = implode(' ', $cPts);
                    $cArea = $cPL.','.(int)($cPT+$cPlotH).' '.$cPoly.' '.round($cPL+($cN-1)*$cStep,1).','.(int)($cPT+$cPlotH);
                    ?>
                    <svg viewBox="0 0 <?php echo $cW; ?> <?php echo $cH; ?>" preserveAspectRatio="none" class="line-chart-svg">
                        <?php for ($g = 0; $g <= 4; $g++): $gy = $cPT + $cPlotH * (1 - $g/4); ?>
                        <line x1="<?php echo $cPL; ?>" y1="<?php echo round($gy,1); ?>" x2="<?php echo $cW-$cPR; ?>" y2="<?php echo round($gy,1); ?>" class="grid-line"/>
                        <text x="<?php echo $cPL-4; ?>" y="<?php echo round($gy+4,1); ?>" class="grid-text" text-anchor="end"><?php echo number_format($g*7/4,1); ?></text>
                        <?php endfor; ?>
                        <polygon points="<?php echo $cArea; ?>" class="area-fill"/>
                        <polyline points="<?php echo $cPoly; ?>" class="line-stroke"/>
                        <?php foreach ($cDots as $dp): ?>
                        <circle cx="<?php echo round($dp['x'],1); ?>" cy="<?php echo round($dp['y'],1); ?>" r="5" class="dot <?php echo $dp['val']>0?'validated':($dp['sub']>0?'':'empty'); ?>">
                            <title><?php echo htmlspecialchars($dp['label'].' · rata-rata '.number_format($dp['avg'],1,',','.').'/7 · laporan '.$dp['sub'].' · valid '.$dp['val']); ?></title>
                        </circle>
                        <?php endforeach; ?>
                        <?php foreach ($cDots as $dp): ?>
                        <text x="<?php echo round($dp['x'],1); ?>" y="<?php echo $cH-4; ?>" class="x-label"><?php echo htmlspecialchars($dp['label']); ?></text>
                        <?php endforeach; ?>
                    </svg>
                </div>
            </div>

            <div class="print-only" style="margin-top: 18px;">
                <div class="table-subtitle">Dokumen ini dicetak untuk rekap guru kelas <?php echo htmlspecialchars($kelas); ?> pada <?php echo htmlspecialchars($semesterLabel . ' ' . $year); ?>.</div>
            </div>
        </section>

        <section class="report-card history-card daftar-section" aria-label="Tabel rekap siswa">
            <h3 class="daftar-print-title" style="display:none;margin:0 0 12px;font-size:16px;color:#111827;">Daftar Rekap Siswa</h3>
            <div class="history-head screen-only">
                <div>
                    <p class="section-kicker">Daftar Siswa</p>
                    <h3>Tabel rekap dan aksi cetak per siswa</h3>
                    <p>Guru tetap bisa membuka detail grafik siswa atau langsung mencetak grafik per siswa dari daftar yang sama.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>NISN</th>
                            <th>Laporan</th>
                            <th>Validasi</th>
                            <th>Rata-rata</th>
                            <th>Grafik Kegiatan</th>
                            <th class="col-aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="7">Tidak ada siswa pada kelas ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $sid => $s): ?>
                                <?php
                                    $agg = $summary[(int) $sid] ?? ['terkirim' => 0, 'valid_any' => 0, 'valid_ortu' => 0, 'valid_guru' => 0, 'score_sum' => 0];
                                    $avg = (int) ($agg['terkirim'] ?? 0) > 0 ? ((float) ($agg['score_sum'] ?? 0) / (float) ($agg['terkirim'] ?? 1)) : 0.0;
                                    $pct = max(0.0, min(100.0, ($avg / 7.0) * 100.0));
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) ($s['nama_siswa'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($s['nisn'] ?? '')); ?></td>
                                    <td><?php echo (int) ($agg['terkirim'] ?? 0); ?></td>
                                    <td>
                                        <span class="pill done"><?php echo (int) ($agg['valid_any'] ?? 0); ?> total</span>
                                        <div class="table-subtitle">Ortu: <?php echo (int) ($agg['valid_ortu'] ?? 0); ?> • Guru: <?php echo (int) ($agg['valid_guru'] ?? 0); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars(number_format($avg, 2, ',', '.')); ?>/7</td>
                                    <td>
                                        <div class="metric-inline" aria-label="Persentase kegiatan">
                                            <div class="metric-bar"><span style="width: <?php echo (int) round($pct); ?>%;"></span></div>
                                            <div class="metric-value"><?php echo (int) round($pct); ?>%</div>
                                        </div>
                                    </td>
                                    <td class="col-aksi">
                                        <div class="table-actions">
                                            <a class="chip-link" href="guru-cetak-grafik-siswa.php?siswa_id=<?php echo (int) $sid; ?>&year=<?php echo (int) $year; ?>&semester=<?php echo (int) $semester; ?>">Lihat</a>
                                            <a class="chip-link primary" href="guru-cetak-grafik-siswa.php?siswa_id=<?php echo (int) $sid; ?>&year=<?php echo (int) $year; ?>&semester=<?php echo (int) $semester; ?>&print=1">Cetak Grafik</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>


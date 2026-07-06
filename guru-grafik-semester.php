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

function bulanLabel(int $month): string
{
    $bulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];
    return $bulan[$month] ?? (string) $month;
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

// Semester sekolah sederhana:
// Smt 1: Jul-Dec, Smt 2: Jan-Jun
$months = $semester === 1 ? [1, 2, 3, 4, 5, 6] : [7, 8, 9, 10, 11, 12];
$start = new DateTimeImmutable(sprintf('%04d-%02d-01', $semester === 1 ? $year : $year, $months[0]));
$end = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $months[count($months) - 1]));
$end = $end->modify('last day of this month');
$startYmd = $start->format('Y-m-d');
$endYmd = $end->format('Y-m-d');

$studentCount = 0;
$monthly = [];
foreach ($months as $m) {
    $monthly[$m] = ['submitted' => 0, 'validated' => 0, 'score_sum' => 0, 'rows' => 0];
}

// Grafik bulanan (per hari)
$monthDate = DateTimeImmutable::createFromFormat('Y-m', $monthParam) ?: new DateTimeImmutable('first day of this month');
$mStart = $monthDate->modify('first day of this month')->setTime(0, 0, 0);
$mEnd = $monthDate->modify('last day of this month')->setTime(23, 59, 59);
$mStartYmd = $mStart->format('Y-m-d');
$mEndYmd = $mEnd->format('Y-m-d');
$daysInMonth = (int) $mStart->format('t');
$monthSeries = [];
$submittedDays = 0;
$validatedDays = 0;

try {
    ensureLaporanHarianTable($conn);

    $resCount = $conn->prepare('SELECT COUNT(*) AS c FROM siswa WHERE kelas = ?');
    if ($resCount) {
        $resCount->bind_param('s', $kelas);
        if ($resCount->execute()) {
            $r = $resCount->get_result();
            $row = $r ? $r->fetch_assoc() : null;
            $studentCount = (int) ($row['c'] ?? 0);
        }
        $resCount->close();
    }

    $stmt = $conn->prepare(
        'SELECT lh.*
         FROM laporan_harian lh
         JOIN siswa s ON s.id = lh.siswa_id
         WHERE s.kelas = ? AND lh.tanggal BETWEEN ? AND ?
         ORDER BY lh.tanggal ASC'
    );
    if ($stmt) {
        $stmt->bind_param('sss', $kelas, $startYmd, $endYmd);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $tgl = (string) ($r['tanggal'] ?? '');
                if ($tgl === '') continue;
                $m = (int) substr($tgl, 5, 2);
                if (!isset($monthly[$m])) continue;
                $score = (int) ($r['bangun'] ?? 0)
                    + (int) ($r['ibadah'] ?? 0)
                    + (int) ($r['olahraga'] ?? 0)
                    + (int) ($r['sarapan'] ?? 0)
                    + (int) ($r['membaca'] ?? 0)
                    + (int) ($r['membantu'] ?? 0)
                    + (int) ($r['menabung'] ?? 0);
                $monthly[$m]['submitted']++;
                $monthly[$m]['score_sum'] += $score;
                $monthly[$m]['rows']++;
                if (!empty($r['guru_validated_at']) || !empty($r['orang_tua_validated_at'])) {
                    $monthly[$m]['validated']++;
                }
            }
        }
        $stmt->close();
    }

    // Query bulanan (per hari) untuk kelas yang sama
    $stmtM = $conn->prepare(
        'SELECT lh.*
         FROM laporan_harian lh
         JOIN siswa s ON s.id = lh.siswa_id
         WHERE s.kelas = ? AND lh.tanggal BETWEEN ? AND ?
         ORDER BY lh.tanggal ASC'
    );
    $byDate = [];
    if ($stmtM) {
        $stmtM->bind_param('sss', $kelas, $mStartYmd, $mEndYmd);
        if ($stmtM->execute()) {
            $resM = $stmtM->get_result();
            while ($r = $resM->fetch_assoc()) {
                $tgl = (string) ($r['tanggal'] ?? '');
                if ($tgl === '') continue;
                $score = (int) ($r['bangun'] ?? 0)
                    + (int) ($r['ibadah'] ?? 0)
                    + (int) ($r['olahraga'] ?? 0)
                    + (int) ($r['sarapan'] ?? 0)
                    + (int) ($r['membaca'] ?? 0)
                    + (int) ($r['membantu'] ?? 0)
                    + (int) ($r['menabung'] ?? 0);
                if (!isset($byDate[$tgl])) {
                    $byDate[$tgl] = ['submitted' => 0, 'validated' => 0, 'score_sum' => 0];
                }
                $byDate[$tgl]['submitted']++;
                $byDate[$tgl]['score_sum'] += $score;
                if (!empty($r['guru_validated_at']) || !empty($r['orang_tua_validated_at'])) {
                    $byDate[$tgl]['validated']++;
                }
            }
        }
        $stmtM->close();
    }

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $tgl = $mStart->setDate((int) $mStart->format('Y'), (int) $mStart->format('m'), $d)->format('Y-m-d');
        $agg = $byDate[$tgl] ?? ['submitted' => 0, 'validated' => 0, 'score_sum' => 0];
        $avg = $agg['submitted'] > 0 ? $agg['score_sum'] / $agg['submitted'] : 0;
        $monthSeries[] = [
            'day' => $d,
            'tanggal' => $tgl,
            'submitted' => (int) $agg['submitted'],
            'validated' => (int) $agg['validated'],
            'avg_score' => (float) $avg,
        ];
        if ($agg['submitted'] > 0) $submittedDays++;
        if ($agg['validated'] > 0) $validatedDays++;
    }
} catch (Throwable $e) {
    // ignore
} finally {
    $conn->close();
}

$series = [];
foreach ($months as $m) {
    $rows = (int) ($monthly[$m]['rows'] ?? 0);
    $avg = $rows > 0 ? ((float) $monthly[$m]['score_sum'] / (float) $rows) : 0.0;
    $series[] = [
        'month' => $m,
        'label' => bulanLabel($m),
        'avg' => $avg,
        'submitted' => (int) ($monthly[$m]['submitted'] ?? 0),
        'validated' => (int) ($monthly[$m]['validated'] ?? 0),
    ];
}

$portalName = (string) ($profile['display_name'] ?? 'Guru');
$semesterLabel = $semester === 1 ? 'Semester 1 (Jan–Jun)' : 'Semester 2 (Jul–Des)';
$bulanLabel = bulanIndonesia($mStart);
$semesterSubmitted = 0;
$semesterValidated = 0;
$semesterScoreSum = 0;
$semesterRows = 0;
$activeMonths = 0;

foreach ($months as $m) {
    $semesterSubmitted += (int) ($monthly[$m]['submitted'] ?? 0);
    $semesterValidated += (int) ($monthly[$m]['validated'] ?? 0);
    $semesterScoreSum += (int) ($monthly[$m]['score_sum'] ?? 0);
    $semesterRows += (int) ($monthly[$m]['rows'] ?? 0);
    if (((int) ($monthly[$m]['submitted'] ?? 0)) > 0) {
        $activeMonths++;
    }
}

$semesterAverage = $semesterRows > 0 ? ($semesterScoreSum / $semesterRows) : 0.0;
$semesterProgressDeg = $studentCount > 0 ? (int) round(($submittedDays / max(1, (int) $mStart->format('t'))) * 360) : 0;
$portalInitial = strtoupper(substr($portalName !== '' ? $portalName : 'G', 0, 1));

// ── AJAX mode: return JSON for live chart updates ──────────────────────────
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'monthly'  => $monthSeries,
        'semester' => $series,
        'stats'    => [
            'studentCount'       => $studentCount,
            'submittedDays'      => $submittedDays,
            'validatedDays'      => $validatedDays,
            'semesterSubmitted'  => $semesterSubmitted,
            'semesterValidated'  => $semesterValidated,
            'semesterAverage'    => round($semesterAverage, 1),
            'activeMonths'       => $activeMonths,
            'progressDeg'        => $semesterProgressDeg,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Semester Guru — KAIH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="assets/css/style.css?v=20260324">
    <link rel="stylesheet" href="assets/css/portal.css?v=20260324">
    <link rel="stylesheet" href="assets/css/report-pages.css?v=20260324">
    <style>
        .meter-card .filter-form {
            width: 100%;
            justify-content: flex-start;
        }

        .meter-card .filter-form > * {
            flex: 1 1 150px;
        }

        .meter-card .filter-form .primary-btn {
            flex: 0 0 auto;
            min-width: 150px;
        }

        @media (max-width: 720px) {
            .meter-card .filter-form > * {
                flex-basis: 100%;
            }

            .table-wrap .report-table {
                min-width: 0;
            }
        }
    </style>
</head>
<body class="report-body role-guru">
    <div class="report-shell">
        <header class="report-topbar">
            <div class="topbar-main">
                <div class="brand-row">
                    <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo Sekolah" class="brand-logo">
                    <div class="title-stack">
                        <p class="title-eyebrow">Grafik Semester Guru</p>
                        <h1>Grafik semester kelas <?php echo htmlspecialchars($kelas); ?></h1>
                    </div>
                </div>
                <div class="quick-nav" aria-label="Menu Guru">
                    <a href="guru-validasi.php">Validasi</a>
                    <a class="active" href="guru-grafik-semester.php">Grafik Semester</a>
                    <a href="guru-cetak-laporan.php">Cetak Laporan</a>
                </div>
            </div>
            <div class="top-actions">
                <div class="chip-static">
                    <span class="chip-avatar"><?php echo htmlspecialchars($portalInitial); ?></span>
                    <span><?php echo htmlspecialchars($portalName); ?></span>
                </div>
            </div>
        </header>

        <section class="report-card validation-summary-card teacher-summary-card" aria-label="Ringkasan semester guru">
            <div class="teacher-summary-head">
                <div class="hero-meta validation-summary-chips teacher-summary-chips">
                    <span class="pill sent">Laporan semester: <?php echo (int) $semesterSubmitted; ?></span>
                    <span class="pill done">Validasi semester: <?php echo (int) $semesterValidated; ?></span>
                    <span class="pill pending">Bulan aktif: <?php echo (int) $activeMonths; ?>/6</span>
                </div>
                <form class="filter-form teacher-summary-form" method="get" action="">
                    <input type="month" name="month" value="<?php echo htmlspecialchars($monthParam); ?>">
                    <input type="number" name="year" min="2020" max="2100" value="<?php echo (int) $year; ?>">
                    <select name="semester">
                        <option value="1" <?php echo $semester === 1 ? 'selected' : ''; ?>>Semester 1</option>
                        <option value="2" <?php echo $semester === 2 ? 'selected' : ''; ?>>Semester 2</option>
                    </select>
                    <button type="submit" class="primary-btn">Tampilkan</button>
                </form>
            </div>
            <div class="validation-summary-grid teacher-summary-grid summary-grid">
                <article class="validation-summary-item validation-summary-count-item teacher-summary-item">
                    <span class="summary-label">Hari Laporan</span>
                    <div class="summary-value"><?php echo (int) $submittedDays; ?>/<?php echo (int) max($daysInMonth, 1); ?></div>
                </article>
                <article class="validation-summary-item teacher-summary-item">
                    <span class="summary-label">Periode</span>
                    <div class="summary-value"><?php echo htmlspecialchars($bulanLabel); ?></div>
                </article>
                <article class="validation-summary-item teacher-summary-item">
                    <span class="summary-label">Jumlah Siswa</span>
                    <div class="summary-value"><?php echo (int) $studentCount; ?></div>
                </article>
                <article class="validation-summary-item teacher-summary-item">
                    <span class="summary-label">Hari Ada Laporan</span>
                    <div class="summary-value"><?php echo (int) $submittedDays; ?> hari</div>
                </article>
                <article class="validation-summary-item teacher-summary-item">
                    <span class="summary-label">Hari Ada Validasi</span>
                    <div class="summary-value"><?php echo (int) $validatedDays; ?> hari</div>
                </article>
                <article class="validation-summary-item teacher-summary-item">
                    <span class="summary-label">Rata-rata Semester</span>
                    <div class="summary-value"><?php echo htmlspecialchars(number_format($semesterAverage, 1, ',', '.')); ?>/7</div>
                </article>
            </div>
        </section>

        <section class="report-card section-card" aria-label="Grafik bulanan">
            <div class="section-head">
                <div>
                    <p class="section-kicker">Visual Harian</p>
                    <h3>Grafik kegiatan bulan <?php echo htmlspecialchars($bulanLabel); ?></h3>
                    <p>Setiap batang mewakili skor rata-rata per hari pada bulan terpilih. Tooltip tetap menampilkan jumlah laporan dan validasi.</p>
                </div>
            </div>

            <div class="chart-panel">
                <div class="chart-legend" aria-label="Legenda grafik bulanan">
                    <span class="legend-pill"><span class="legend-dot"></span> Ada laporan</span>
                    <span class="legend-pill"><span class="legend-dot validated"></span> Sudah divalidasi</span>
                </div>
                <div class="line-chart-wrap" id="monthlyChart">
                    <?php
                    $svgW = 900; $svgH = 220; $padL = 30; $padR = 10; $padT = 15; $padB = 30;
                    $plotW = $svgW - $padL - $padR;
                    $plotH = $svgH - $padT - $padB;
                    $n = count($monthSeries);
                    $step = $n > 1 ? $plotW / ($n - 1) : 0;
                    $pts = []; $ptsSub = []; $ptsVal = [];
                    foreach ($monthSeries as $i => $p) {
                        $x = $padL + ($i * $step);
                        $avg = (float)($p['avg_score'] ?? 0);
                        $y = $padT + $plotH - ($plotH * min(1, $avg / 7.0));
                        $pts[] = round($x,1).','.round($y,1);
                        $ptsSub[] = ['x'=>$x,'y'=>$y,'avg'=>$avg,'day'=>(int)($p['day']??0),'sub'=>(int)($p['submitted']??0),'val'=>(int)($p['validated']??0)];
                    }
                    $polyline = implode(' ', $pts);
                    $areaPts = $padL.','.(int)($padT+$plotH).' '.$polyline.' '.round($padL+($n-1)*$step,1).','.(int)($padT+$plotH);
                    ?>
                    <svg viewBox="0 0 <?= $svgW ?> <?= $svgH ?>" preserveAspectRatio="none" class="line-chart-svg">
                        <!-- Grid lines -->
                        <?php for ($g = 0; $g <= 4; $g++): $gy = $padT + $plotH * (1 - $g/4); ?>
                        <line x1="<?= $padL ?>" y1="<?= round($gy,1) ?>" x2="<?= $svgW-$padR ?>" y2="<?= round($gy,1) ?>" class="grid-line"/>
                        <text x="<?= $padL-4 ?>" y="<?= round($gy+4,1) ?>" class="grid-text" text-anchor="end"><?= number_format($g*7/4,1) ?></text>
                        <?php endfor; ?>
                        <!-- Area fill -->
                        <polygon points="<?= $areaPts ?>" class="area-fill"/>
                        <!-- Line -->
                        <polyline points="<?= $polyline ?>" class="line-stroke"/>
                        <!-- Dots -->
                        <?php foreach ($ptsSub as $dp): ?>
                        <circle cx="<?= round($dp['x'],1) ?>" cy="<?= round($dp['y'],1) ?>" r="4" class="dot <?= $dp['val']>0?'validated':($dp['sub']>0?'':'empty') ?>">
                            <title><?= htmlspecialchars($dp['day'].' • rata-rata '.number_format($dp['avg'],1,',','.').'/7 • laporan '.$dp['sub'].' • valid '.$dp['val']) ?></title>
                        </circle>
                        <?php endforeach; ?>
                        <!-- X labels -->
                        <?php foreach ($ptsSub as $i => $dp): ?>
                        <?php if ($i % max(1, intdiv($n,15)) === 0 || $i === $n-1): ?>
                        <text x="<?= round($dp['x'],1) ?>" y="<?= $svgH-4 ?>" class="x-label"><?= $dp['day'] ?></text>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </svg>
                </div>
            </div>
        </section>

        <section class="report-card history-card" aria-label="Grafik semester">
            <div class="section-head">
                <div>
                    <p class="section-kicker">Visual Semester</p>
                    <h3>Grafik perkembangan 6 bulan</h3>
                    <p>Ringkasan ini memudahkan guru melihat bulan mana yang paling aktif dan bulan mana yang masih perlu dorongan pelaporan.</p>
                </div>
            </div>

            <div class="chart-panel">
                <div class="chart-legend" aria-label="Legenda grafik semester">
                    <span class="legend-pill"><span class="legend-dot"></span> Rata-rata skor</span>
                    <span class="legend-pill"><span class="legend-dot validated"></span> Sudah divalidasi</span>
                </div>
                <div class="line-chart-wrap" id="semesterChart">
                    <?php
                    $sW = 500; $sH = 220; $sPL = 30; $sPR = 10; $sPT = 15; $sPB = 30;
                    $sPlotW = $sW - $sPL - $sPR;
                    $sPlotH = $sH - $sPT - $sPB;
                    $sN = count($series);
                    $sStep = $sN > 1 ? $sPlotW / ($sN - 1) : 0;
                    $sPts = []; $sData = [];
                    foreach ($series as $i => $p) {
                        $x = $sPL + ($i * $sStep);
                        $avg = (float)($p['avg'] ?? 0);
                        $y = $sPT + $sPlotH - ($sPlotH * min(1, $avg / 7.0));
                        $sPts[] = round($x,1).','.round($y,1);
                        $sData[] = ['x'=>$x,'y'=>$y,'avg'=>$avg,'label'=>$p['label']??'','sub'=>(int)($p['submitted']??0),'val'=>(int)($p['validated']??0)];
                    }
                    $sPoly = implode(' ', $sPts);
                    $sArea = $sPL.','.(int)($sPT+$sPlotH).' '.$sPoly.' '.round($sPL+($sN-1)*$sStep,1).','.(int)($sPT+$sPlotH);
                    ?>
                    <svg viewBox="0 0 <?= $sW ?> <?= $sH ?>" preserveAspectRatio="none" class="line-chart-svg">
                        <?php for ($g = 0; $g <= 4; $g++): $gy = $sPT + $sPlotH * (1 - $g/4); ?>
                        <line x1="<?= $sPL ?>" y1="<?= round($gy,1) ?>" x2="<?= $sW-$sPR ?>" y2="<?= round($gy,1) ?>" class="grid-line"/>
                        <text x="<?= $sPL-4 ?>" y="<?= round($gy+4,1) ?>" class="grid-text" text-anchor="end"><?= number_format($g*7/4,1) ?></text>
                        <?php endfor; ?>
                        <polygon points="<?= $sArea ?>" class="area-fill"/>
                        <polyline points="<?= $sPoly ?>" class="line-stroke"/>
                        <?php foreach ($sData as $dp): ?>
                        <circle cx="<?= round($dp['x'],1) ?>" cy="<?= round($dp['y'],1) ?>" r="5" class="dot <?= $dp['val']>0?'validated':($dp['sub']>0?'':'empty') ?>">
                            <title><?= htmlspecialchars($dp['label'].' • rata-rata '.number_format($dp['avg'],1,',','.').'/7 • laporan '.$dp['sub'].' • valid '.$dp['val']) ?></title>
                        </circle>
                        <?php endforeach; ?>
                        <?php foreach ($sData as $dp): ?>
                        <text x="<?= round($dp['x'],1) ?>" y="<?= $sH-4 ?>" class="x-label"><?= htmlspecialchars($dp['label']) ?></text>
                        <?php endforeach; ?>
                    </svg>
                </div>
            </div>
        </section>

        <section class="report-card history-card" aria-label="Tabel semester">
            <div class="history-head">
                <div>
                    <p class="section-kicker">Ringkasan Angka</p>
                    <h3>Tabel rekap bulan per semester</h3>
                    <p>Angka laporan, validasi, dan rata-rata skor tetap tersedia untuk pembacaan yang lebih detail.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th>Laporan</th>
                            <th>Validasi</th>
                            <th>Rata-rata</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($series as $p): ?>
                            <?php $has = ((int) ($p['submitted'] ?? 0)) > 0; ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) ($p['label'] ?? '')); ?></td>
                                <td><?php echo (int) ($p['submitted'] ?? 0); ?></td>
                                <td><?php echo (int) ($p['validated'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars(number_format((float) ($p['avg'] ?? 0), 1, ',', '.')); ?>/7</td>
                                <td>
                                    <?php if (!$has): ?>
                                        <span class="pill pending">Belum ada laporan</span>
                                    <?php elseif (((int) ($p['validated'] ?? 0)) > 0): ?>
                                        <span class="pill done">Sudah ada validasi</span>
                                    <?php else: ?>
                                        <span class="pill sent">Sudah ada laporan</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

<script>
(function(){
  var params = new URLSearchParams(window.location.search);
  params.set('ajax','1');
  var url = window.location.pathname + '?' + params.toString();
  var interval = 15000;

  function fmt(n){ return n.toFixed(1).replace('.',','); }

  /* SVG line chart builder */
  function buildSVG(container, data, cfg){
    var W=cfg.w, H=cfg.h, PL=cfg.pl||30, PR=cfg.pr||10, PT=cfg.pt||15, PB=cfg.pb||30;
    var plotW=W-PL-PR, plotH=H-PT-PB;
    var n=data.length, step=n>1?plotW/(n-1):0;
    var pts=[], dots=[];
    data.forEach(function(p,i){
      var x=PL+i*step;
      var avg=parseFloat(p.avg)||0;
      var y=PT+plotH-(plotH*Math.min(1,avg/7));
      pts.push(x.toFixed(1)+','+y.toFixed(1));
      dots.push({x:x,y:y,avg:avg,label:p.label||'',sub:parseInt(p.submitted)||0,val:parseInt(p.validated)||0});
    });
    var polyStr=pts.join(' ');
    var areaStr=PL+','+(PT+plotH)+' '+polyStr+' '+(PL+(n-1)*step).toFixed(1)+','+(PT+plotH);
    var html='<svg viewBox="0 0 '+W+' '+H+'" preserveAspectRatio="none" class="line-chart-svg">';
    for(var g=0;g<=4;g++){
      var gy=PT+plotH*(1-g/4);
      html+='<line x1="'+PL+'" y1="'+gy.toFixed(1)+'" x2="'+(W-PR)+'" y2="'+gy.toFixed(1)+'" class="grid-line"/>';
      html+='<text x="'+(PL-4)+'" y="'+(gy+4).toFixed(1)+'" class="grid-text" text-anchor="end">'+(g*7/4).toFixed(1)+'</text>';
    }
    html+='<polygon points="'+areaStr+'" class="area-fill"/>';
    html+='<polyline points="'+polyStr+'" class="line-stroke"/>';
    dots.forEach(function(d){
      var cls=d.val>0?'validated':(d.sub>0?'':'empty');
      html+='<circle cx="'+d.x.toFixed(1)+'" cy="'+d.y.toFixed(1)+'" r="'+(cfg.r||4)+'" class="dot '+cls+'">';
      html+='<title>'+d.label+' \u2022 rata-rata '+fmt(d.avg)+'/7 \u2022 laporan '+d.sub+' \u2022 valid '+d.val+'</title></circle>';
    });
    var showEvery=Math.max(1,Math.floor(n/15));
    dots.forEach(function(d,i){
      if(cfg.showAll || i%showEvery===0 || i===n-1)
        html+='<text x="'+d.x.toFixed(1)+'" y="'+(H-4)+'" class="x-label">'+d.label+'</text>';
    });
    html+='</svg>';
    container.innerHTML=html;
  }

  function refresh(){
    fetch(url, {credentials:'same-origin'}).then(function(r){ return r.json(); }).then(function(d){
      // Rebuild monthly line chart
      if(d.monthly){
        var mc=document.getElementById('monthlyChart');
        if(mc){
          var mData=d.monthly.map(function(p){return{avg:p.avg_score,label:String(p.day),submitted:p.submitted,validated:p.validated};});
          buildSVG(mc, mData, {w:900,h:220,pl:30,pr:10,pt:15,pb:30,r:4});
        }
      }
      // Rebuild semester line chart
      if(d.semester){
        var sc=document.getElementById('semesterChart');
        if(sc){
          var sData=d.semester.map(function(p){return{avg:p.avg,label:p.label||'',submitted:p.submitted,validated:p.validated};});
          buildSVG(sc, sData, {w:500,h:220,pl:30,pr:10,pt:15,pb:30,r:5,showAll:true});
        }
      }
      // Update stats
      if(d.stats){
        var s = d.stats;
        var vals = document.querySelectorAll('.summary-grid .summary-value');
        if(vals[0]) vals[0].textContent = s.studentCount;
        if(vals[1]) vals[1].textContent = s.submittedDays+' hari';
        if(vals[2]) vals[2].textContent = s.validatedDays+' hari';
        if(vals[3]) vals[3].textContent = fmt(s.semesterAverage)+'/7';
        var pills = document.querySelectorAll('.hero-meta .pill');
        if(pills[0]) pills[0].textContent = 'Laporan semester: '+s.semesterSubmitted;
        if(pills[1]) pills[1].textContent = 'Validasi semester: '+s.semesterValidated;
        if(pills[2]) pills[2].textContent = 'Bulan aktif: '+s.activeMonths+'/6';
        var ring = document.querySelector('.meter-ring');
        if(ring){
          ring.style.setProperty('--progress-deg', s.progressDeg+'deg');
          var sp = ring.querySelector('span');
          if(sp) sp.textContent = s.submittedDays+'/'+Math.max(1,<?php echo (int)$daysInMonth; ?>);
        }
      }
      // Update tabel semester
      if(d.semester){
        var rows = document.querySelectorAll('.table-wrap .report-table tbody tr');
        d.semester.forEach(function(p, i){
          if(!rows[i]) return;
          var cells = rows[i].querySelectorAll('td');
          if(cells[1]) cells[1].textContent = p.submitted||0;
          if(cells[2]) cells[2].textContent = p.validated||0;
          if(cells[3]) cells[3].textContent = fmt(parseFloat(p.avg)||0)+'/7';
          if(cells[4]){
            var has = (parseInt(p.submitted)||0)>0;
            var isVal = (parseInt(p.validated)||0)>0;
            if(!has) cells[4].innerHTML='<span class="pill pending">Belum ada laporan</span>';
            else if(isVal) cells[4].innerHTML='<span class="pill done">Sudah ada validasi</span>';
            else cells[4].innerHTML='<span class="pill sent">Sudah ada laporan</span>';
          }
        });
      }
    }).catch(function(){});
  }

  setInterval(refresh, interval);
})();
</script>
</body>
</html>


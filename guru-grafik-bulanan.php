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

// Grafik Bulanan dan Semester sekarang digabung di halaman grafik semester.
$monthParam = trim((string) ($_GET['month'] ?? ''));
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = (new DateTimeImmutable('today'))->format('Y-m');
}
header('Location: guru-grafik-semester.php?month=' . urlencode($monthParam));
exit;

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

$monthParam = trim((string) ($_GET['month'] ?? ''));
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = (new DateTimeImmutable('today'))->format('Y-m');
}

$monthDate = DateTimeImmutable::createFromFormat('Y-m', $monthParam) ?: new DateTimeImmutable('first day of this month');
$start = $monthDate->modify('first day of this month')->setTime(0, 0, 0);
$end = $monthDate->modify('last day of this month')->setTime(23, 59, 59);
$startYmd = $start->format('Y-m-d');
$endYmd = $end->format('Y-m-d');
$daysInMonth = (int) $start->format('t');

$rows = [];
$studentCount = 0;
$submittedDays = 0;
$validatedDays = 0;
$series = [];

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
        'SELECT lh.*, s.nama_siswa, s.kelas
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
                $rows[] = $r;
            }
        }
        $stmt->close();
    }

    $byDate = [];
    foreach ($rows as $r) {
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
            $byDate[$tgl] = [
                'submitted' => 0,
                'validated' => 0,
                'score_sum' => 0,
            ];
        }
        $byDate[$tgl]['submitted']++;
        $byDate[$tgl]['score_sum'] += $score;
        if (!empty($r['guru_validated_at']) || !empty($r['orang_tua_validated_at'])) {
            $byDate[$tgl]['validated']++;
        }
    }

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $tgl = $start->setDate((int) $start->format('Y'), (int) $start->format('m'), $d)->format('Y-m-d');
        $agg = $byDate[$tgl] ?? ['submitted' => 0, 'validated' => 0, 'score_sum' => 0];
        $avg = $agg['submitted'] > 0 ? $agg['score_sum'] / $agg['submitted'] : 0;
        $series[] = [
            'day' => $d,
            'tanggal' => $tgl,
            'submitted' => (int) $agg['submitted'],
            'validated' => (int) $agg['validated'],
            'avg_score' => $avg,
        ];
        if ($agg['submitted'] > 0) $submittedDays++;
        if ($agg['validated'] > 0) $validatedDays++;
    }
} catch (Throwable $e) {
    // ignore, show empty state
} finally {
    $conn->close();
}

$bulanLabel = bulanIndonesia($start);
$portalName = (string) ($profile['display_name'] ?? 'Guru');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Bulanan Guru — KAIH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/portal.css">
    <style>
        .brand-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(0,0,0,0.08);
            background: #fff;
            flex: 0 0 44px;
        }
        .guru-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 0.75rem;
        }
        .guru-nav a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.55rem 0.85rem;
            border-radius: 999px;
            border: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.6);
            font-weight: 800;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .guru-nav a.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
        }
        .card {
            background: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.25rem;
            box-shadow: var(--shadow-sm);
        }
        .card-head {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 0.85rem;
        }
        .filters {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
        }
        .filters input[type="month"] {
            padding: 0.55rem 0.75rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: #fff;
            font-weight: 700;
        }
        .summary-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        .summary {
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 0.85rem;
            background: rgba(0, 0, 0, 0.01);
        }
        .summary .label {
            font-size: 0.75rem;
            letter-spacing: 0.7px;
            text-transform: uppercase;
            color: var(--text-light);
            font-weight: 800;
            margin-bottom: 0.25rem;
        }
        .summary .value {
            font-size: 1.15rem;
            font-weight: 900;
            color: var(--text-dark);
        }
        .chart {
            display: grid;
            grid-template-columns: repeat(<?php echo (int) $daysInMonth; ?>, minmax(0, 1fr));
            gap: 6px;
            align-items: end;
            height: 180px;
            padding: 10px;
            border-radius: 14px;
            border: 1px solid var(--border-color);
            background: #f9fafb;
            overflow-x: auto;
        }
        .bar {
            width: 100%;
            border-radius: 10px 10px 6px 6px;
            background: linear-gradient(180deg, rgba(59, 130, 246, 0.95), rgba(37, 99, 235, 0.95));
            position: relative;
            min-height: 6px;
        }
        .bar.none { background: rgba(148, 163, 184, 0.35); }
        .bar.validated { background: linear-gradient(180deg, rgba(16, 185, 129, 0.95), rgba(5, 150, 105, 0.95)); }
        .bar:hover::after {
            content: attr(data-label);
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 100%;
            margin-bottom: 8px;
            padding: 6px 8px;
            border-radius: 10px;
            background: rgba(17, 24, 39, 0.9);
            color: white;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
            pointer-events: none;
        }
        @media (max-width: 900px) {
            .summary-row { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .summary-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="portal-shell">
        <div class="portal-topbar">
            <div>
                <div class="brand-row">
                    <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo Sekolah" class="brand-logo">
                    <div>
                        <p class="top-eyebrow">KAIH</p>
                        <h1><?php echo htmlspecialchars($portalName); ?></h1>
                        <p class="top-meta">Guru • Kelas <?php echo htmlspecialchars($kelas); ?></p>
                    </div>
                </div>
                <div class="guru-nav" aria-label="Menu Guru">
                    <a href="guru-validasi.php">Validasi</a>
                    <a class="active" href="guru-grafik-bulanan.php">Grafik Bulanan</a>
                    <a href="guru-grafik-semester.php">Grafik Semester</a>
                    <a href="guru-cetak-laporan.php">Cetak Laporan</a>
                </div>
            </div>
            <div class="top-actions">
                <a href="logout.php" class="action-link solid">Keluar</a>
            </div>
        </div>

        <main class="portal-main">
            <section class="card" aria-label="Grafik bulanan">
                <div class="card-head">
                    <div>
                        <h3 style="margin:0;">Grafik Bulanan</h3>
                        <div style="color: var(--text-light); font-weight: 700; margin-top: 0.25rem;">
                            <?php echo htmlspecialchars($bulanLabel); ?> • Tinggi bar = rata-rata skor (0–7) dari laporan yang terkirim. Bar hijau = ada validasi guru.
                        </div>
                    </div>
                    <form class="filters" method="get" action="">
                        <input type="month" name="month" value="<?php echo htmlspecialchars($monthParam); ?>">
                        <button type="submit" class="btn btn-secondary btn-sm">Tampilkan</button>
                    </form>
                </div>

                <div class="summary-row">
                    <div class="summary">
                        <div class="label">Jumlah siswa</div>
                        <div class="value"><?php echo (int) $studentCount; ?></div>
                    </div>
                    <div class="summary">
                        <div class="label">Hari ada laporan</div>
                        <div class="value"><?php echo (int) $submittedDays; ?> hari</div>
                    </div>
                    <div class="summary">
                        <div class="label">Hari ada validasi guru</div>
                        <div class="value"><?php echo (int) $validatedDays; ?> hari</div>
                    </div>
                    <div class="summary">
                        <div class="label">Periode</div>
                        <div class="value"><?php echo htmlspecialchars($monthParam); ?></div>
                    </div>
                </div>
            </section>

            <section class="card" aria-label="Grafik rata-rata skor" style="margin-top: 1rem;">
                <div class="chart" role="img" aria-label="Grafik rata-rata skor per hari">
                    <?php foreach ($series as $p): ?>
                        <?php
                            $avg = (float) $p['avg_score'];
                            $height = (int) round(($avg / 7.0) * 100);
                            $height = max(3, min(100, $height));
                            $label = $p['day'] . ' • rata2 ' . number_format($avg, 1, ',', '.') . '/7'
                                . ' • laporan ' . (int) $p['submitted']
                                . ' • valid ' . (int) $p['validated'];
                            $class = 'bar';
                            if ((int) $p['submitted'] === 0) {
                                $class .= ' none';
                            } elseif ((int) $p['validated'] > 0) {
                                $class .= ' validated';
                            }
                        ?>
                        <div class="<?php echo $class; ?>" style="height: <?php echo (int) $height; ?>%;" data-label="<?php echo htmlspecialchars($label); ?>"></div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>


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

function formatBulanIndonesia(DateTimeInterface $date): string
{
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    return ($bulan[(int) $date->format('n')] ?? $date->format('m')) . ' ' . $date->format('Y');
}

$logoSekolah = pickFirstExistingImage([
    'assets/img/logo-sekolah.png',
    'assets/img/logo-sekolah.jpg',
    'assets/img/logo-sekolah.jpeg',
    'assets/img/logo-sekolah.webp',
    'assets/img/logo-sekolah.svg',
    'assets/img/logo.png',
], 'assets/img/logo-sekolah.svg');

$conn = getConnection();
$profile = fetchPortalProfileByUserId($conn, (int) $_SESSION['portal_user_id']);
if (!$profile) {
    $conn->close();
    unset($_SESSION['portal_user_id'], $_SESSION['portal_role'], $_SESSION['portal_display_name'], $_SESSION['portal_login_time']);
    header('Location: index.php');
    exit;
}

if (($profile['role'] ?? '') !== 'orang_tua') {
    $conn->close();
    header('Location: logout.php');
    exit;
}

$siswaId = (int) ($profile['siswa_id'] ?? 0);
if ($siswaId <= 0) {
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

$rowsByTanggal = [];
$namaSiswa = (string) ($profile['nama_siswa'] ?? $profile['display_name'] ?? '');
$inisial = strtoupper(mb_substr(trim($namaSiswa) !== '' ? trim($namaSiswa) : 'S', 0, 1, 'UTF-8'));

try {
    ensureLaporanHarianTable($conn);

    $stmt = $conn->prepare(
        'SELECT lh.*
         FROM laporan_harian lh
         WHERE lh.siswa_id = ? AND lh.tanggal BETWEEN ? AND ?
         ORDER BY lh.tanggal ASC'
    );
    if ($stmt) {
        $stmt->bind_param('iss', $siswaId, $startYmd, $endYmd);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $tglKey = (string) ($r['tanggal'] ?? '');
                if ($tglKey !== '') {
                    $rowsByTanggal[$tglKey] = $r;
                }
            }
        }
        $stmt->close();
    }
} catch (Throwable $e) {
    // Biarkan kosong, halaman tetap menampilkan state tanpa data.
} finally {
    $conn->close();
}

$daysInMonth = (int) $start->format('t');
$series = [];
$maxVal = 0;
$validatedCount = 0;
$submittedCount = 0;

for ($d = 1; $d <= $daysInMonth; $d++) {
    $tgl = $start->setDate((int) $start->format('Y'), (int) $start->format('m'), $d)->format('Y-m-d');
    $row = $rowsByTanggal[$tgl] ?? null;
    $score = 0;
    $anyValidated = false;
    $hasReport = false;

    if ($row) {
        $hasReport = true;
        $score = (int) ($row['bangun'] ?? 0)
            + (int) ($row['ibadah'] ?? 0)
            + (int) ($row['olahraga'] ?? 0)
            + (int) ($row['sarapan'] ?? 0)
            + (int) ($row['membaca'] ?? 0)
            + (int) ($row['membantu'] ?? 0)
            + (int) ($row['menabung'] ?? 0);
        $anyValidated = !empty($row['orang_tua_validated_at']) || !empty($row['guru_validated_at']);
    }

    if ($hasReport) {
        $submittedCount++;
    }
    if ($anyValidated) {
        $validatedCount++;
    }

    $maxVal = max($maxVal, $score);
    $series[] = [
        'day' => $d,
        'tanggal' => $tgl,
        'score' => $score,
        'has_report' => $hasReport,
        'validated' => $anyValidated,
    ];
}

if ($maxVal <= 0) {
    $maxVal = 7;
}

$bulanLabel = formatBulanIndonesia($start);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Bulanan — KAIH</title>
    <link rel="stylesheet" href="assets/css/style.css?v=20260324">
    <link rel="stylesheet" href="assets/css/portal.css?v=20260324">
    <link rel="stylesheet" href="assets/css/report-pages.css?v=20260324">
</head>
<body class="report-body role-ortu">
    <div class="report-shell">
        <header class="report-topbar">
            <div class="topbar-main">
                <div class="brand-row">
                    <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo Sekolah" class="brand-logo">
                    <div class="title-stack">
                        <p class="title-eyebrow">Grafik Orang Tua</p>
                        <h1>Pantau konsistensi kegiatan harian siswa dalam tampilan baru.</h1>
                    </div>
                </div>
                <div class="quick-nav" aria-label="Menu Orang Tua">
                    <a href="ortu-validasi.php">Validasi</a>
                    <a class="active" href="ortu-grafik-bulanan.php">Grafik Bulanan</a>
                </div>
            </div>
            <div class="top-actions">
                <div class="chip-static" aria-label="Nama siswa">
                    <span class="chip-avatar"><?php echo htmlspecialchars($inisial); ?></span>
                    <span><?php echo htmlspecialchars($namaSiswa !== '' ? $namaSiswa : 'Siswa'); ?></span>
                </div>
            </div>
        </header>

        <section class="report-card validation-summary-card monthly-summary-card" aria-label="Ringkasan bulanan">
            <div class="validation-summary-head">
                <div class="hero-meta validation-summary-chips">
                    <span class="pill sent">Laporan masuk: <?php echo (int) $submittedCount; ?> hari</span>
                    <span class="pill done">Tervalidasi: <?php echo (int) $validatedCount; ?> hari</span>
                    <span class="pill pending">Total hari: <?php echo (int) $daysInMonth; ?></span>
                </div>
            </div>
            <div class="validation-summary-grid monthly-summary-grid">
                <article class="validation-summary-item">
                    <span class="summary-label">Jumlah Laporan</span>
                    <div class="summary-value"><?php echo (int) $submittedCount; ?> hari</div>
                    <div class="summary-note">Hari yang memiliki laporan kegiatan terkirim.</div>
                </article>
                <article class="validation-summary-item">
                    <span class="summary-label">Sudah Divalidasi</span>
                    <div class="summary-value"><?php echo (int) $validatedCount; ?> hari</div>
                    <div class="summary-note">Tervalidasi oleh orang tua atau guru.</div>
                </article>
                <article class="validation-summary-item">
                    <span class="summary-label">Hari Dalam Bulan</span>
                    <div class="summary-value"><?php echo (int) $daysInMonth; ?> hari</div>
                    <div class="summary-note">Periode aktif untuk grafik bulan ini.</div>
                </article>
            </div>
        </section>

        <section class="report-card section-card" aria-label="Grafik">
            <div class="section-head">
                <div>
                    <p class="section-kicker">Visual Bulanan</p>
                    <h3>Grafik skor kegiatan per hari</h3>
                    <p>Lihat perubahan skor harian secara cepat dengan visual batang yang sudah disesuaikan ke tema frontend.</p>
                </div>
                <form class="filter-form" method="get" action="">
                    <input type="month" name="month" value="<?php echo htmlspecialchars($monthParam); ?>">
                    <button class="primary-btn" type="submit">Tampilkan</button>
                </form>
            </div>

            <div class="chart-panel">
                <div class="chart-legend" aria-label="Legenda grafik">
                    <span class="legend-pill"><span class="legend-dot"></span> Ada laporan</span>
                    <span class="legend-pill"><span class="legend-dot validated"></span> Sudah divalidasi</span>
                    <span class="legend-pill"><span class="legend-dot empty"></span> Belum ada laporan</span>
                </div>
                <div class="monthly-chart" style="grid-template-columns: repeat(<?php echo (int) $daysInMonth; ?>, minmax(26px, 1fr));" role="img" aria-label="Grafik skor kegiatan per hari">
                    <?php foreach ($series as $p): ?>
                        <?php
                            $h = (int) round(((int) $p['score'] / (int) $maxVal) * 100);
                            $h = max(3, min(100, $h));
                            $label = $p['day'] . ' • ' . ($p['has_report'] ? ($p['score'] . '/7') : '—') . ($p['validated'] ? ' • valid' : '');
                            $class = 'monthly-bar';
                            if (!$p['has_report']) {
                                $class .= ' empty';
                            } elseif ($p['validated']) {
                                $class .= ' validated';
                            }
                        ?>
                        <div class="monthly-bar-wrap">
                            <div class="<?php echo $class; ?>" style="height: <?php echo (int) $h; ?>%;" data-label="<?php echo htmlspecialchars($label); ?>"></div>
                            <span class="monthly-day"><?php echo (int) $p['day']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="report-card history-card" aria-label="Tabel ringkas">
            <div class="history-head">
                <div>
                    <p class="section-kicker">Ringkasan Harian</p>
                    <h3>Tabel status per hari</h3>
                    <p>Data ringkas ini tetap menampilkan skor dan status laporan setiap tanggal.</p>
                </div>
            </div>
            <div class="inline-note">Ringkasan per hari selama bulan <?php echo htmlspecialchars($bulanLabel); ?>.</div>
            <div class="table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Skor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($series as $p): ?>
                            <?php
                                $tgl = (string) $p['tanggal'];
                                $status = !$p['has_report']
                                    ? '<span class="pill pending">Belum terkirim</span>'
                                    : ($p['validated'] ? '<span class="pill done">Sudah divalidasi</span>' : '<span class="pill pending">Belum divalidasi</span>');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tgl); ?></td>
                                <td><?php echo htmlspecialchars(($p['has_report'] ? ($p['score'] . '/7') : '—')); ?></td>
                                <td><?php echo $status; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>

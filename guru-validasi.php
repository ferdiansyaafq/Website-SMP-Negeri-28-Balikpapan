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

if ((string) ($_SESSION['portal_role'] ?? '') !== 'guru') {
    header('Location: logout.php');
    exit;
}

$flash = '';
$flashType = 'success';
$tanggalDb = (new DateTimeImmutable('today'))->format('Y-m-d');
$selectedDate = (string) ($_GET['tanggal'] ?? $tanggalDb);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = $tanggalDb;
}
$laporanKelasHariIni = [];

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

try {
    ensureLaporanHarianTable($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'validate_guru') {
        $laporanId = (int) ($_POST['laporan_id'] ?? 0);
        $kelas = trim((string) ($profile['guru_kelas'] ?? ''));
        if ($laporanId <= 0) {
            throw new RuntimeException('ID laporan tidak valid.');
        }
        if ($kelas === '') {
            throw new RuntimeException('Data kelas guru belum tersedia.');
        }

        $stmt = $conn->prepare(
            'UPDATE laporan_harian lh
             JOIN siswa s ON s.id = lh.siswa_id
             SET lh.guru_validated_at = IFNULL(lh.guru_validated_at, NOW())
             WHERE lh.id = ? AND s.kelas = ?'
        );
        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan validasi guru.');
        }
        $stmt->bind_param('is', $laporanId, $kelas);
        if (!$stmt->execute()) {
            throw new RuntimeException('Gagal menyimpan validasi guru.');
        }
        $stmt->close();

        $flash = 'Validasi guru berhasil disimpan.';
        $flashType = 'success';
    }

    $kelas = trim((string) ($profile['guru_kelas'] ?? ''));
    if ($kelas !== '') {
        $stmt = $conn->prepare(
            'SELECT
              s.id AS siswa_id, s.nisn, s.nama_siswa, s.kelas,
              lh.id AS laporan_id, lh.tanggal,
              lh.bangun, lh.ibadah, lh.olahraga, lh.sarapan, lh.membaca, lh.membantu, lh.menabung,
              lh.orang_tua_validated_at, lh.guru_validated_at
            FROM siswa s
            LEFT JOIN laporan_harian lh ON lh.siswa_id = s.id AND lh.tanggal = ?
            WHERE s.kelas = ?
            ORDER BY s.nama_siswa ASC'
        );
        if ($stmt) {
            $stmt->bind_param('ss', $selectedDate, $kelas);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $laporanKelasHariIni[] = $row;
                }
            }
            $stmt->close();
        }
    }
} catch (Throwable $e) {
    $flash = $e->getMessage();
    $flashType = 'error';
} finally {
    $conn->close();
}

$portalName = (string) ($profile['display_name'] ?? 'Guru');
$kelasLabel = (string) ($profile['guru_kelas'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Guru — KAIH</title>
    <link rel="stylesheet" href="assets/css/style.css?v=20260324">
    <link rel="stylesheet" href="assets/css/portal.css?v=20260324">
    <link rel="stylesheet" href="assets/css/report-pages.css?v=20260324">
</head>
<body class="report-body role-guru">
    <div class="report-shell">
        <?php if ($flash !== ''): ?>
            <div class="report-flash <?php echo $flashType === 'error' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <header class="report-topbar">
            <div class="topbar-main">
                <div class="brand-row">
                    <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo Sekolah" class="brand-logo">
                    <div class="title-stack">
                        <p class="title-eyebrow">Validasi Guru</p>
                        <h1>Review laporan harian kelas <?php echo htmlspecialchars($kelasLabel !== '' ? $kelasLabel : '—'); ?>.</h1>
                        <p>Daftar siswa, status kirim, dan tombol validasi tetap memakai proses backend yang sama. Halaman ini hanya diperbarui agar tampilannya konsisten dengan frontend portal.</p>
                    </div>
                </div>
                <div class="quick-nav" aria-label="Menu Guru">
                    <a class="active" href="guru-validasi.php">Validasi</a>
                    <a href="guru-grafik-semester.php">Grafik Semester</a>
                    <a href="guru-cetak-laporan.php">Cetak Laporan</a>
                </div>
            </div>
            <div class="top-actions">
                <div class="chip-static">
                    <span class="chip-avatar"><?php echo htmlspecialchars(strtoupper(mb_substr($portalName !== '' ? $portalName : 'G', 0, 1, 'UTF-8'))); ?></span>
                    <span><?php echo htmlspecialchars($portalName); ?></span>
                </div>
                <a href="logout.php" class="chip-link danger">Keluar</a>
            </div>
        </header>

        <?php
            $totalSiswa = count($laporanKelasHariIni);
            $totalTerkirim = 0;
            $totalBelumKirim = 0;
            $totalValid = 0;
            $laporanSudahKirim = [];
            $laporanBelumKirim = [];

            foreach ($laporanKelasHariIni as $item) {
                $hasReportItem = !empty($item['laporan_id']) || !empty($item['tanggal']);
                $ortuDoneItem = $hasReportItem && !empty($item['orang_tua_validated_at']);
                $guruDoneItem = $hasReportItem && !empty($item['guru_validated_at']);

                if ($hasReportItem) {
                    $totalTerkirim++;
                    $laporanSudahKirim[] = $item;
                } else {
                    $totalBelumKirim++;
                    $laporanBelumKirim[] = $item;
                }

                if ($ortuDoneItem || $guruDoneItem) {
                    $totalValid++;
                }
            }

        ?>

        <?php
            $pctMelapor   = $totalSiswa > 0 ? round(($totalTerkirim / $totalSiswa) * 100) : 0;
            $pctBelum     = 100 - $pctMelapor;
            $pctValidasi  = $totalSiswa > 0 ? round(($totalValid / $totalSiswa) * 100) : 0;
            // SVG donut math: circumference = 2 * π * r (r=70)
            $circumference = 439.82;
            $dashMelapor   = $circumference * $pctMelapor / 100;
            $dashBelum     = $circumference - $dashMelapor;
        ?>

        <style>
        /* ── Donut Chart Layout ── */
        .summary-with-chart {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 28px;
            align-items: start;
        }
        .donut-card {
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(148,163,184,0.14);
            border-radius: 18px;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
        }
        .donut-card-title {
            font-size: 13px;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: 0.3px;
            text-align: center;
        }
        .donut-wrap {
            position: relative;
            width: 160px;
            height: 160px;
        }
        .donut-wrap svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        .donut-wrap .donut-track {
            fill: none;
            stroke: #e2e8f0;
            stroke-width: 18;
        }
        .donut-wrap .donut-fill {
            fill: none;
            stroke: #2563eb;
            stroke-width: 18;
            stroke-linecap: round;
            transition: stroke-dasharray 0.8s cubic-bezier(.22,1,.36,1);
        }
        .donut-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .donut-pct {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 32px;
            font-weight: 900;
            color: #1e293b;
            line-height: 1;
        }
        .donut-pct-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            margin-top: 2px;
        }
        .donut-legend {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }
        .donut-legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-size: 12.5px;
            color: #475569;
        }
        .donut-legend-item .legend-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .donut-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .donut-legend-dot.melapor { background: #2563eb; }
        .donut-legend-dot.belum   { background: #e2e8f0; }
        .donut-legend-dot.valid   { background: #22c55e; }
        .donut-legend-val {
            font-weight: 800;
            color: #1e293b;
        }
        @media (max-width: 768px) {
            .summary-with-chart {
                grid-template-columns: 1fr;
            }
            .donut-card {
                order: -1;
            }
        }
        </style>

        <section class="report-card validation-summary-card teacher-summary-card" aria-label="Ringkasan kelas guru">
            <div class="teacher-summary-head">
                <div class="hero-meta validation-summary-chips teacher-summary-chips">
                    <span class="pill sent">Tanggal aktif: <?php echo htmlspecialchars($selectedDate); ?></span>
                    <span class="pill <?php echo $totalValid > 0 ? 'done' : 'pending'; ?>">Sudah divalidasi: <?php echo (int) $totalValid; ?></span>
                    <span class="pill <?php echo $totalBelumKirim > 0 ? 'pending' : 'done'; ?>">Belum melapor: <?php echo (int) $totalBelumKirim; ?></span>
                </div>
                <form class="filter-form teacher-summary-form" method="get" action="">
                    <input type="date" name="tanggal" value="<?php echo htmlspecialchars($selectedDate); ?>">
                    <button type="submit" class="teacher-btn">Tampilkan</button>
                </form>
            </div>

            <div class="summary-with-chart">
                <div class="validation-summary-grid teacher-summary-grid summary-grid">
                    <article class="validation-summary-item validation-summary-count-item teacher-summary-item">
                        <span class="summary-label">Progress</span>
                        <div class="summary-value"><?php echo (int) $totalValid; ?>/<?php echo (int) max($totalSiswa, 1); ?></div>
                    </article>
                    <article class="validation-summary-item teacher-summary-item">
                        <span class="summary-label">Kelas</span>
                        <div class="summary-value"><?php echo htmlspecialchars($kelasLabel !== '' ? $kelasLabel : '—'); ?></div>
                    </article>
                    <article class="validation-summary-item teacher-summary-item">
                        <span class="summary-label">Total Siswa</span>
                        <div class="summary-value"><?php echo (int) $totalSiswa; ?></div>
                    </article>
                    <article class="validation-summary-item teacher-summary-item">
                        <span class="summary-label">Belum Melapor</span>
                        <div class="summary-value"><?php echo (int) $totalBelumKirim; ?></div>
                    </article>
                    <article class="validation-summary-item teacher-summary-item">
                        <span class="summary-label">Sudah Divalidasi</span>
                        <div class="summary-value"><?php echo (int) $totalValid; ?></div>
                    </article>
                </div>

                <div class="donut-card">
                    <div class="donut-card-title">Laporan Kegiatan Hari Ini</div>
                    <div class="donut-wrap">
                        <svg viewBox="0 0 160 160">
                            <circle class="donut-track" cx="80" cy="80" r="70"></circle>
                            <circle class="donut-fill" cx="80" cy="80" r="70"
                                stroke-dasharray="<?php echo $dashMelapor; ?> <?php echo $dashBelum; ?>">
                            </circle>
                        </svg>
                        <div class="donut-center">
                            <span class="donut-pct"><?php echo $pctMelapor; ?>%</span>
                            <span class="donut-pct-label">melapor</span>
                        </div>
                    </div>
                    <div class="donut-legend">
                        <div class="donut-legend-item">
                            <span class="legend-left"><span class="donut-legend-dot melapor"></span> Sudah melapor</span>
                            <span class="donut-legend-val"><?php echo (int) $totalTerkirim; ?> (<?php echo $pctMelapor; ?>%)</span>
                        </div>
                        <div class="donut-legend-item">
                            <span class="legend-left"><span class="donut-legend-dot belum"></span> Belum melapor</span>
                            <span class="donut-legend-val"><?php echo (int) $totalBelumKirim; ?> (<?php echo $pctBelum; ?>%)</span>
                        </div>
                        <div class="donut-legend-item">
                            <span class="legend-left"><span class="donut-legend-dot valid"></span> Tervalidasi</span>
                            <span class="donut-legend-val"><?php echo (int) $totalValid; ?> (<?php echo $pctValidasi; ?>%)</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="report-card section-card">
            <div class="section-head">
                <div>
                    <p class="section-kicker">Daftar Laporan</p>
                    <h3>Validasi laporan harian kelas</h3>
                </div>
            </div>

            <?php if (empty($laporanKelasHariIni)): ?>
                <div class="empty-card">
                    <strong>Data kelas belum tersedia.</strong>
                    Belum ada siswa atau belum ada data laporan untuk kelas ini.
                </div>
            <?php else: ?>
                <div class="teacher-groups">
                    <div class="teacher-group">
                        <div class="teacher-group-head">
                            <h4>Sudah Melakukan Laporan</h4>
                            <span><?php echo (int) count($laporanSudahKirim); ?> siswa</span>
                        </div>
                        <?php if (empty($laporanSudahKirim)): ?>
                            <div class="empty-card">
                                <strong>Belum ada laporan masuk.</strong>
                                Tidak ada siswa yang mengirim laporan pada tanggal ini.
                            </div>
                        <?php else: ?>
                            <div class="teacher-list">
                    <?php foreach ($laporanSudahKirim as $row): ?>
                        <?php
                            $hasReport = !empty($row['laporan_id']) || !empty($row['tanggal']);
                            $count = (int) ($row['bangun'] ?? 0)
                                + (int) ($row['ibadah'] ?? 0)
                                + (int) ($row['olahraga'] ?? 0)
                                + (int) ($row['sarapan'] ?? 0)
                                + (int) ($row['membaca'] ?? 0)
                                + (int) ($row['membantu'] ?? 0)
                                + (int) ($row['menabung'] ?? 0);
                            $ortuDone = $hasReport && !empty($row['orang_tua_validated_at']);
                            $guruDone = $hasReport && !empty($row['guru_validated_at']);
                            $anyDone = $ortuDone || $guruDone;
                            if ($guruDone) {
                                $statusLabel = 'Sudah divalidasi guru';
                                $statusClass = 'done';
                            } elseif ($ortuDone) {
                                $statusLabel = 'Sudah divalidasi orang tua';
                                $statusClass = 'done';
                            } else {
                                $statusLabel = 'Sudah melapor, menunggu validasi';
                                $statusClass = 'sent';
                            }
                        ?>
                        <article class="teacher-item">
                            <div class="teacher-top">
                                <div>
                                    <div class="teacher-name"><?php echo htmlspecialchars((string) ($row['nama_siswa'] ?? '')); ?></div>
                                    <div class="teacher-sub">NISN <?php echo htmlspecialchars((string) ($row['nisn'] ?? '')); ?> • Kelas <?php echo htmlspecialchars((string) ($row['kelas'] ?? '')); ?></div>
                                </div>
                                <div class="teacher-score"><?php echo htmlspecialchars(($hasReport ? $count : 0) . '/7'); ?></div>
                            </div>

                            <div class="hero-meta">
                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                            </div>

                            <div class="teacher-actions">
                                <?php if ($hasReport && !$anyDone): ?>
                                    <form method="post">
                                        <input type="hidden" name="action" value="validate_guru">
                                        <input type="hidden" name="laporan_id" value="<?php echo (int) ($row['laporan_id'] ?? 0); ?>">
                                        <button type="submit" class="teacher-btn">Konfirmasi Guru</button>
                                    </form>
                                <?php elseif ($hasReport): ?>
                                    <button type="button" class="teacher-btn btn-disabled" disabled>Sudah divalidasi</button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="teacher-group">
                        <div class="teacher-group-head">
                            <h4>Belum Melakukan Laporan</h4>
                            <span><?php echo (int) count($laporanBelumKirim); ?> siswa</span>
                        </div>
                        <?php if (empty($laporanBelumKirim)): ?>
                            <div class="empty-card">
                                <strong>Semua siswa sudah melapor.</strong>
                                Tidak ada siswa yang tertinggal pada tanggal ini.
                            </div>
                        <?php else: ?>
                            <div class="teacher-list">
                                <?php foreach ($laporanBelumKirim as $row): ?>
                                    <article class="teacher-item">
                                        <div class="teacher-top">
                                            <div>
                                                <div class="teacher-name"><?php echo htmlspecialchars((string) ($row['nama_siswa'] ?? '')); ?></div>
                                                <div class="teacher-sub">NISN <?php echo htmlspecialchars((string) ($row['nisn'] ?? '')); ?> • Kelas <?php echo htmlspecialchars((string) ($row['kelas'] ?? '')); ?></div>
                                            </div>
                                            <div class="teacher-score">0/7</div>
                                        </div>

                                        <div class="hero-meta">
                                            <span class="status-badge pending">Belum melakukan laporan</span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>


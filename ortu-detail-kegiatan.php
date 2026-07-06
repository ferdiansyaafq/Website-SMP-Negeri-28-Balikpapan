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

function formatTanggalIndonesia(string $dateYmd): string
{
    try {
        $date = new DateTimeImmutable($dateYmd);
    } catch (Throwable) {
        return $dateYmd;
    }

    $hari = [
        'Minggu',
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu',
    ];
    $bulan = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $dayName = $hari[(int) $date->format('w')] ?? $date->format('l');
    $day = $date->format('d');
    $month = $bulan[(int) $date->format('n')] ?? $date->format('m');
    $year = $date->format('Y');

    return $dayName . ', ' . $day . ' ' . $month . ' ' . $year;
}

$flash = '';
$flashType = 'success';
$laporan = null;

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
$laporanId = (int) ($_GET['id'] ?? 0);

try {
    ensureLaporanHarianTable($conn);

    if ($siswaId <= 0) {
        throw new RuntimeException('Akun orang tua belum terhubung ke data siswa.');
    }

    if ($laporanId <= 0) {
        throw new RuntimeException('ID laporan tidak valid.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'validate_parent') {
        $stmtValidate = $conn->prepare('UPDATE laporan_harian SET orang_tua_validated_at = IFNULL(orang_tua_validated_at, NOW()) WHERE id = ? AND siswa_id = ?');
        if (!$stmtValidate) {
            throw new RuntimeException('Gagal menyiapkan validasi orang tua.');
        }
        $stmtValidate->bind_param('ii', $laporanId, $siswaId);
        if (!$stmtValidate->execute()) {
            throw new RuntimeException('Gagal menyimpan validasi orang tua.');
        }
        $stmtValidate->close();

        $flash = 'Validasi orang tua berhasil disimpan.';
        $flashType = 'success';
    }

    $stmt = $conn->prepare(
        'SELECT lh.*, s.nama_siswa, s.kelas, s.nisn
         FROM laporan_harian lh
         JOIN siswa s ON s.id = lh.siswa_id
         WHERE lh.id = ? AND lh.siswa_id = ?
         LIMIT 1'
    );
    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan data laporan.');
    }
    $stmt->bind_param('ii', $laporanId, $siswaId);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        $laporan = $res ? $res->fetch_assoc() : null;
    }
    $stmt->close();

    if (!$laporan) {
        throw new RuntimeException('Laporan tidak ditemukan.');
    }
} catch (Throwable $e) {
    $flash = $e->getMessage();
    $flashType = 'error';
} finally {
    $conn->close();
}

$namaSiswa = (string) ($laporan['nama_siswa'] ?? $profile['nama_siswa'] ?? $profile['display_name'] ?? '');
$inisial = strtoupper(mb_substr(trim($namaSiswa) !== '' ? trim($namaSiswa) : 'S', 0, 1, 'UTF-8'));
$tanggalLabel = $laporan ? formatTanggalIndonesia((string) ($laporan['tanggal'] ?? '')) : '—';
$ortuDone = $laporan && !empty($laporan['orang_tua_validated_at']);
$guruDone = $laporan && !empty($laporan['guru_validated_at']);
$anyDone = $ortuDone || $guruDone; // cukup salah satu (ortu/guru)

$count = 0;
if ($laporan) {
    $count = (int) ($laporan['bangun'] ?? 0)
        + (int) ($laporan['ibadah'] ?? 0)
        + (int) ($laporan['olahraga'] ?? 0)
        + (int) ($laporan['sarapan'] ?? 0)
        + (int) ($laporan['membaca'] ?? 0)
        + (int) ($laporan['membantu'] ?? 0)
        + (int) ($laporan['menabung'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detil Kegiatan Siswa — KAIH</title>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Raleway', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #e8edf3 55%, #f1f5f9 100%);
            min-height: 100vh;
            padding: 20px;
            color: #1e293b;
        }
        .container { max-width: 600px; margin: 0 auto; padding-top: 20px; }

        .header {
            background: #ffffff;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(15,23,42,0.08);
            border-bottom: 1px solid rgba(148,163,184,0.20);
        }
        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .header-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .logo-small {
            width: 50px; height: 50px; border-radius: 50%;
            object-fit: cover; border: 2px solid rgba(148,163,184,0.20); background: #f1f5f9; flex-shrink: 0;
        }
        .header-text h1 { font-size: 18px; color: #1e293b; font-weight: 600; }
        .header-text p { font-size: 12px; color: #64748b; }
        .header-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .back-link {
            text-decoration: none;
            color: #2563eb;
            font-weight: 700;
            font-size: 13px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.06);
            border: 1px solid rgba(37, 99, 235, 0.18);
            white-space: nowrap;
        }
        .student-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(148,163,184,0.20);
            box-shadow: 0 1px 3px rgba(15,23,42,0.06);
            white-space: nowrap;
            font-weight: 600;
            color: #1e293b;
            font-size: 13px;
        }
        .student-pill .mini-avatar {
            width: 28px; height: 28px; border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            display: inline-flex; align-items: center; justify-content: center;
            color: #ffffff; font-size: 12px; font-weight: 700;
        }

        .flash {
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 600;
        }
        .flash.success { background: rgba(16,185,129,0.08); color: #059669; border: 1px solid rgba(16,185,129,0.25); }
        .flash.error { background: rgba(239,68,68,0.08); color: #dc2626; border: 1px solid rgba(239,68,68,0.25); }

        .progress-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 18px;
            box-shadow: 0 4px 14px rgba(15,23,42,0.06), 0 2px 4px rgba(15,23,42,0.04);
            border: 1px solid rgba(148,163,184,0.18);
        }
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }
        .progress-title { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
        .progress-date { font-size: 14px; color: #64748b; }
        .progress-circle {
            width: 60px; height: 60px; border-radius: 50%;
            border: 4px solid rgba(37,99,235,0.15);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 16px; color: #0f172a;
            position: relative; background: #f8fafc;
        }
        .progress-circle::before {
            content: '';
            position: absolute;
            top: -4px; left: -4px; right: -4px; bottom: -4px;
            border-radius: 50%;
            border: 4px solid transparent;
            border-top-color: #2563eb;
            border-right-color: #2563eb;
            transform: rotate(var(--rotation, 0deg));
        }

        .status-row { display: flex; flex-wrap: wrap; gap: 8px; }
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid transparent;
        }
        .badge.sent { background: rgba(37, 99, 235, 0.08); color: #2563eb; border-color: rgba(37, 99, 235, 0.20); }
        .badge.pending { background: rgba(245, 158, 11, 0.08); color: #b45309; border-color: rgba(245, 158, 11, 0.20); }
        .badge.done { background: rgba(16, 185, 129, 0.08); color: #059669; border-color: rgba(16, 185, 129, 0.20); }

        .activity-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 14px;
            box-shadow: 0 2px 8px rgba(15,23,42,0.05);
            border: 1px solid rgba(148,163,184,0.18);
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }
        .activity-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; flex-shrink: 0;
        }
        .icon-sunrise { background: rgba(250,204,21,0.10); }
        .icon-pray { background: rgba(37,99,235,0.08); }
        .icon-sport { background: rgba(244,114,182,0.10); }
        .icon-food { background: rgba(52,211,153,0.08); }
        .icon-book { background: rgba(192,132,252,0.10); }
        .icon-help { background: rgba(251,146,60,0.08); }
        .icon-money { background: rgba(74,222,128,0.08); }
        .activity-content { flex: 1; }
        .activity-title { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
        .activity-value { font-size: 12px; color: #2563eb; font-weight: 700; }
        .activity-note {
            margin-top: 10px;
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(148,163,184,0.18);
            border-radius: 12px;
            background: #f8fafc;
            font-size: 13px;
            color: #334155;
            line-height: 1.5;
        }
        .check-pill {
            padding-top: 6px;
            flex-shrink: 0;
        }
        .check-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            padding: 8px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 900;
            border: 1px solid transparent;
            background: rgba(245,158,11,0.08);
            color: #b45309;
            border-color: rgba(245,158,11,0.20);
        }
        .check-badge.on {
            background: rgba(16,185,129,0.08);
            color: #059669;
            border-color: rgba(16,185,129,0.20);
        }

        .validate-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 2px 8px rgba(15,23,42,0.05);
            border: 1px solid rgba(148,163,184,0.18);
            margin-top: 18px;
        }
        .validate-title { font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
        .validate-sub { font-size: 13px; color: #64748b; font-weight: 600; margin-bottom: 12px; }
        .btn {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 800;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            box-shadow: 0 10px 22px -10px rgba(37, 99, 235, 0.40);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 26px -12px rgba(37, 99, 235, 0.50); }
        .btn-disabled { opacity: 0.65; cursor: not-allowed; box-shadow: none; transform: none !important; }
        @media (max-width: 640px) { body { padding: 16px; } }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-inner">
            <div class="header-left">
                <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo Sekolah" class="logo-small">
                <div class="header-text">
                    <h1>KAIH</h1>
                    <p>SMP Negeri 28 Balikpapan</p>
                </div>
            </div>
            <div class="header-right">
                <a href="logout.php" class="back-link">← Kembali</a>
                <div class="student-pill" aria-label="Nama siswa">
                    <span class="mini-avatar"><?php echo htmlspecialchars($inisial); ?></span>
                    <span><?php echo htmlspecialchars($namaSiswa !== '' ? $namaSiswa : 'Siswa'); ?></span>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if ($flash !== ''): ?>
            <div class="flash <?php echo $flashType === 'error' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <?php if ($laporan): ?>
            <div class="progress-card">
                <div class="progress-header">
                    <div>
                        <div class="progress-title">Detil Kegiatan</div>
                        <div class="progress-date"><?php echo htmlspecialchars($tanggalLabel); ?></div>
                    </div>
                    <div class="progress-circle" style="--rotation: <?php echo (int) round(($count / 7) * 360); ?>deg">
                        <?php echo htmlspecialchars($count . '/7'); ?>
                    </div>
                </div>
                <div class="status-row" aria-label="Status laporan">
                    <span class="badge sent">Terkirim</span>
        <span class="badge <?php echo $anyDone ? 'done' : 'pending'; ?>">Validasi: <?php echo $anyDone ? 'Sudah' : 'Belum'; ?></span>
        <span class="badge <?php echo $ortuDone ? 'done' : 'pending'; ?>">Orang Tua: <?php echo $ortuDone ? 'Sudah' : 'Belum'; ?></span>
        <span class="badge <?php echo $guruDone ? 'done' : 'pending'; ?>">Guru: <?php echo $guruDone ? 'Sudah' : 'Belum'; ?></span>
                </div>
            </div>

            <div class="activity-card">
                <div class="activity-icon icon-sunrise">🌅</div>
                <div class="activity-content">
                    <div class="activity-title">Bangun Pagi &amp; Merapikan Tempat Tidur</div>
                    <div class="activity-value">Nilai: Kemandirian &amp; Disiplin</div>
                </div>
                <div class="check-pill">
                    <span class="check-badge <?php echo !empty($laporan['bangun']) ? 'on' : ''; ?>"><?php echo !empty($laporan['bangun']) ? '✓' : '—'; ?></span>
                </div>
            </div>

            <div class="activity-card">
                <div class="activity-icon icon-pray">🙏</div>
                <div class="activity-content">
                    <div class="activity-title">Beribadah (Sholat/Ibadah Pagi)</div>
                    <div class="activity-value">Nilai: Religius &amp; Bertakwa</div>
                    <?php if (trim((string) ($laporan['ibadah_catatan'] ?? '')) !== ''): ?>
                        <div class="activity-note"><?php echo htmlspecialchars((string) $laporan['ibadah_catatan']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="check-pill">
                    <span class="check-badge <?php echo !empty($laporan['ibadah']) ? 'on' : ''; ?>"><?php echo !empty($laporan['ibadah']) ? '✓' : '—'; ?></span>
                </div>
            </div>

            <div class="activity-card">
                <div class="activity-icon icon-sport">🏃</div>
                <div class="activity-content">
                    <div class="activity-title">Berolahraga / Aktivitas Fisik</div>
                    <div class="activity-value">Nilai: Kesehatan &amp; Kesejahteraan</div>
                    <?php if (trim((string) ($laporan['olahraga_jenis'] ?? '')) !== ''): ?>
                        <div class="activity-note"><?php echo htmlspecialchars((string) $laporan['olahraga_jenis']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="check-pill">
                    <span class="check-badge <?php echo !empty($laporan['olahraga']) ? 'on' : ''; ?>"><?php echo !empty($laporan['olahraga']) ? '✓' : '—'; ?></span>
                </div>
            </div>

            <div class="activity-card">
                <div class="activity-icon icon-food">🥗</div>
                <div class="activity-content">
                    <div class="activity-title">Sarapan Sehat &amp; Minum Air</div>
                    <div class="activity-value">Nilai: Pola Hidup Sehat</div>
                    <?php if (trim((string) ($laporan['sarapan_menu'] ?? '')) !== ''): ?>
                        <div class="activity-note"><?php echo htmlspecialchars((string) $laporan['sarapan_menu']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="check-pill">
                    <span class="check-badge <?php echo !empty($laporan['sarapan']) ? 'on' : ''; ?>"><?php echo !empty($laporan['sarapan']) ? '✓' : '—'; ?></span>
                </div>
            </div>

            <div class="activity-card">
                <div class="activity-icon icon-book">📚</div>
                <div class="activity-content">
                    <div class="activity-title">Gemar Membaca (Literasi)</div>
                    <div class="activity-value">Nilai: Bernalar Kritis</div>
                    <?php
                        $membacaJudul = trim((string) ($laporan['membaca_judul'] ?? ''));
                        $membacaMenit = (int) ($laporan['membaca_menit'] ?? 0);
                    ?>
                    <?php if ($membacaJudul !== '' || $membacaMenit > 0): ?>
                        <div class="activity-note">
                            <?php echo htmlspecialchars($membacaJudul !== '' ? $membacaJudul : '—'); ?>
                            <?php if ($membacaMenit > 0): ?>
                                (<?php echo (int) $membacaMenit; ?> menit)
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="check-pill">
                    <span class="check-badge <?php echo !empty($laporan['membaca']) ? 'on' : ''; ?>"><?php echo !empty($laporan['membaca']) ? '✓' : '—'; ?></span>
                </div>
            </div>

            <div class="activity-card">
                <div class="activity-icon icon-help">🤝</div>
                <div class="activity-content">
                    <div class="activity-title">Membantu Orang Tua</div>
                    <div class="activity-value">Nilai: Berbakti &amp; Gotong Royong</div>
                    <?php if (trim((string) ($laporan['membantu_jenis'] ?? '')) !== ''): ?>
                        <div class="activity-note"><?php echo htmlspecialchars((string) $laporan['membantu_jenis']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="check-pill">
                    <span class="check-badge <?php echo !empty($laporan['membantu']) ? 'on' : ''; ?>"><?php echo !empty($laporan['membantu']) ? '✓' : '—'; ?></span>
                </div>
            </div>

            <div class="activity-card">
                <div class="activity-icon icon-money">💰</div>
                <div class="activity-content">
                    <div class="activity-title">Menabung / Hidup Hemat</div>
                    <div class="activity-value">Nilai: Literasi Finansial</div>
                    <?php $nominal = (int) ($laporan['menabung_nominal'] ?? 0); ?>
                    <?php if ($nominal > 0): ?>
                        <div class="activity-note">Rp <?php echo number_format($nominal, 0, ',', '.'); ?></div>
                    <?php endif; ?>
                </div>
                <div class="check-pill">
                    <span class="check-badge <?php echo !empty($laporan['menabung']) ? 'on' : ''; ?>"><?php echo !empty($laporan['menabung']) ? '✓' : '—'; ?></span>
                </div>
            </div>

            <div class="validate-card">
                <div class="validate-title">Validasi Orang Tua</div>
                <div class="validate-sub">Klik tombol di bawah untuk mengonfirmasi laporan ini.</div>

                <?php if ($anyDone): ?>
                    <button type="button" class="btn btn-primary btn-disabled" disabled>Sudah divalidasi</button>
                <?php else: ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="validate_parent">
                        <button type="submit" class="btn btn-primary">Konfirmasi Orang Tua</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>


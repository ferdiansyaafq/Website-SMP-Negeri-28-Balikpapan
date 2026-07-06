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

$conn = getConnection();
$profile = fetchPortalProfileByUserId($conn, (int) $_SESSION['portal_user_id']);
$conn->close();

if (!$profile) {
    unset($_SESSION['portal_user_id'], $_SESSION['portal_role'], $_SESSION['portal_display_name'], $_SESSION['portal_login_time']);
    header('Location: index.php');
    exit;
}

if (($profile['role'] ?? '') !== 'siswa') {
    header('Location: logout.php');
    exit;
}

function formatTanggalIndonesia(DateTimeInterface $date): string
{
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

$namaSiswa = (string) ($profile['nama_siswa'] ?? $profile['display_name'] ?? '');
$inisial = strtoupper(mb_substr(trim($namaSiswa) !== '' ? trim($namaSiswa) : 'S', 0, 1, 'UTF-8'));
$hariIni = formatTanggalIndonesia(new DateTimeImmutable('now'));

$siswaId = (int) ($profile['siswa_id'] ?? 0);
$canSubmit = $siswaId > 0;
$passwordPanelOpen = false;

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

$flash = '';
$flashType = 'success';
$tanggalDb = (new DateTimeImmutable('today'))->format('Y-m-d');
$sudahKirimHariIni = false;
$todayReport = null;
$editReport = null;
$editMode = false;
$editId = 0;

if (isset($_SESSION['_flash'])) {
    $flash = (string) $_SESSION['_flash'];
    $flashType = (string) ($_SESSION['_flash_type'] ?? 'success');
    unset($_SESSION['_flash'], $_SESSION['_flash_type']);
}

if (!$canSubmit) {
    $flash = 'Akun siswa Anda belum terhubung dengan data peserta didik. Silakan hubungi admin untuk sinkronisasi akun.';
    $flashType = 'error';
}

try {
    $connEnsure = getConnection();
    ensureLaporanHarianTable($connEnsure);

    if ($siswaId > 0) {
        $stmtToday = $connEnsure->prepare('SELECT * FROM laporan_harian WHERE siswa_id = ? AND tanggal = ? LIMIT 1');
        if ($stmtToday) {
            $stmtToday->bind_param('is', $siswaId, $tanggalDb);
            if ($stmtToday->execute()) {
                $resultToday  = $stmtToday->get_result();
                $todayReport  = ($resultToday ? $resultToday->fetch_assoc() : null) ?: null;
                $sudahKirimHariIni = !empty($todayReport);
            }
            $stmtToday->close();
        }

        $editParam = (int) ($_GET['edit'] ?? 0);
        if ($editParam > 0) {
            $stmtEd = $connEnsure->prepare('SELECT * FROM laporan_harian WHERE id = ? AND siswa_id = ? AND orang_tua_validated_at IS NULL AND guru_validated_at IS NULL LIMIT 1');
            if ($stmtEd) {
                $stmtEd->bind_param('ii', $editParam, $siswaId);
                if ($stmtEd->execute()) {
                    $resEd = $stmtEd->get_result();
                    $editReport = $resEd ? $resEd->fetch_assoc() : null;
                    if ($editReport) {
                        $editMode = true;
                        $editId = (int) $editReport['id'];
                    }
                }
                $stmtEd->close();
            }
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($confirmPassword === '') {
            $flash = 'Konfirmasi password baru wajib diisi.';
            $flashType = 'error';
            $passwordPanelOpen = true;
        } elseif (!hash_equals($newPassword, $confirmPassword)) {
            $flash = 'Konfirmasi password baru tidak sama.';
            $flashType = 'error';
            $passwordPanelOpen = true;
        } else {
            changePortalUserPassword($connEnsure, (int) $_SESSION['portal_user_id'], $currentPassword, $newPassword, ['siswa']);
            $flash = 'Password akun siswa berhasil diperbarui.';
            $flashType = 'success';
            $passwordPanelOpen = false;
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_report') {
        $editIdPost = (int) ($_POST['edit_id'] ?? 0);
        if (!$canSubmit || $editIdPost <= 0) {
            $flash = 'Tidak bisa mengubah laporan.';
            $flashType = 'error';
        } else {
            $bangun   = !empty($_POST['bangun']) ? 1 : 0;
            $ibadah   = !empty($_POST['ibadah']) ? 1 : 0;
            $ibadahCatatan = trim((string) ($_POST['ibadah_catatan'] ?? ''));
            $olahraga = !empty($_POST['olahraga']) ? 1 : 0;
            $olahragaJenis = trim((string) ($_POST['olahraga_jenis'] ?? ''));
            $sarapan  = !empty($_POST['sarapan']) ? 1 : 0;
            $sarapanMenu   = trim((string) ($_POST['sarapan_menu'] ?? ''));
            $membaca  = !empty($_POST['membaca']) ? 1 : 0;
            $membacaJudul  = trim((string) ($_POST['membaca_judul'] ?? ''));
            $membacaMenit  = (int) ($_POST['membaca_menit'] ?? 0);
            $membantu = !empty($_POST['membantu']) ? 1 : 0;
            $membantuJenis = trim((string) ($_POST['membantu_jenis'] ?? ''));
            $menabung = !empty($_POST['menabung']) ? 1 : 0;
            $menabungNominal = (int) ($_POST['menabung_nominal'] ?? 0);

            $total = $bangun + $ibadah + $olahraga + $sarapan + $membaca + $membantu + $menabung;
            if ($total === 0) {
                $flash = 'Silakan centang minimal satu aktivitas sebelum menyimpan perubahan.';
                $flashType = 'error';
            } else {
                $stmtUp = $connEnsure->prepare(
                    'UPDATE laporan_harian SET
                        bangun = ?, ibadah = ?, ibadah_catatan = NULLIF(?, \'\'),
                        olahraga = ?, olahraga_jenis = NULLIF(?, \'\'),
                        sarapan = ?, sarapan_menu = NULLIF(?, \'\'),
                        membaca = ?, membaca_judul = NULLIF(?, \'\'), membaca_menit = NULLIF(?, 0),
                        membantu = ?, membantu_jenis = NULLIF(?, \'\'),
                        menabung = ?, menabung_nominal = NULLIF(?, 0)
                    WHERE id = ? AND siswa_id = ? AND orang_tua_validated_at IS NULL AND guru_validated_at IS NULL'
                );
                if (!$stmtUp) {
                    throw new RuntimeException('Gagal menyiapkan perubahan laporan.');
                }
                $stmtUp->bind_param(
                    'iisisisisisiisii',
                    $bangun, $ibadah, $ibadahCatatan,
                    $olahraga, $olahragaJenis,
                    $sarapan, $sarapanMenu,
                    $membaca, $membacaJudul, $membacaMenit,
                    $membantu, $membantuJenis,
                    $menabung, $menabungNominal,
                    $editIdPost, $siswaId
                );
                if (!$stmtUp->execute()) {
                    throw new RuntimeException('Gagal menyimpan perubahan laporan.');
                }
                if ($stmtUp->affected_rows > 0) {
                    $_SESSION['_flash'] = 'Laporan berhasil diperbarui.';
                    $_SESSION['_flash_type'] = 'success';
                } else {
                    $_SESSION['_flash'] = 'Laporan tidak bisa diubah (mungkin sudah divalidasi).';
                    $_SESSION['_flash_type'] = 'error';
                }
                $stmtUp->close();
                $connEnsure->close();
                header('Location: progress-harian.php');
                exit;
            }
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_report') {
        if (!$canSubmit) {
            $flash = 'Tidak bisa mengirim laporan karena akun siswa belum terhubung dengan data peserta didik.';
            $flashType = 'error';
        } elseif ($sudahKirimHariIni) {
            $flash = 'Laporan hari ini sudah terkirim. Besok baru bisa kirim laporan lagi.';
            $flashType = 'error';
        } else {
        $bangun = !empty($_POST['bangun']) ? 1 : 0;
        $ibadah = !empty($_POST['ibadah']) ? 1 : 0;
        $ibadahCatatan = trim((string) ($_POST['ibadah_catatan'] ?? ''));

        $olahraga = !empty($_POST['olahraga']) ? 1 : 0;
        $olahragaJenis = trim((string) ($_POST['olahraga_jenis'] ?? ''));

        $sarapan = !empty($_POST['sarapan']) ? 1 : 0;
        $sarapanMenu = trim((string) ($_POST['sarapan_menu'] ?? ''));

        $membaca = !empty($_POST['membaca']) ? 1 : 0;
        $membacaJudul = trim((string) ($_POST['membaca_judul'] ?? ''));
        $membacaMenit = (int) ($_POST['membaca_menit'] ?? 0);

        $membantu = !empty($_POST['membantu']) ? 1 : 0;
        $membantuJenis = trim((string) ($_POST['membantu_jenis'] ?? ''));

        $menabung = !empty($_POST['menabung']) ? 1 : 0;
        $menabungNominal = (int) ($_POST['menabung_nominal'] ?? 0);

        $total = $bangun + $ibadah + $olahraga + $sarapan + $membaca + $membantu + $menabung;
        if ($total === 0) {
            $flash = 'Silakan centang minimal satu aktivitas sebelum mengirim laporan.';
            $flashType = 'error';
        } else {
            $stmt = $connEnsure->prepare(
                'INSERT INTO laporan_harian (
                    siswa_id, tanggal,
                    bangun, ibadah, ibadah_catatan,
                    olahraga, olahraga_jenis,
                    sarapan, sarapan_menu,
                    membaca, membaca_judul, membaca_menit,
                    membantu, membantu_jenis,
                    menabung, menabung_nominal
                ) VALUES (
                    ?, ?,
                    ?, ?, NULLIF(?, \'\'),
                    ?, NULLIF(?, \'\'),
                    ?, NULLIF(?, \'\'),
                    ?, NULLIF(?, \'\'), NULLIF(?, 0),
                    ?, NULLIF(?, \'\'),
                    ?, NULLIF(?, 0)
                )'
            );

            if (!$stmt) {
                throw new RuntimeException('Gagal menyiapkan penyimpanan laporan.');
            }

            $stmt->bind_param(
                'isiisisisisiisii',
                $siswaId,
                $tanggalDb,
                $bangun,
                $ibadah,
                $ibadahCatatan,
                $olahraga,
                $olahragaJenis,
                $sarapan,
                $sarapanMenu,
                $membaca,
                $membacaJudul,
                $membacaMenit,
                $membantu,
                $membantuJenis,
                $menabung,
                $menabungNominal
            );

            if (!$stmt->execute()) {
                if (($connEnsure->errno ?? 0) === 1062) {
                    $flash = 'Laporan hari ini sudah terkirim. Besok baru bisa kirim laporan lagi.';
                    $flashType = 'error';
                } else {
                    throw new RuntimeException('Gagal menyimpan laporan.');
                }
            } else {
                $flash = 'Laporan berhasil dikirim.';
                $flashType = 'success';
                $sudahKirimHariIni = true;
            }
            $stmt->close();
        }
        }
    }
} catch (Throwable $e) {
    $flash = $e->getMessage();
    $flashType = 'error';
}

$riwayat = [];
$riwayatHari = 7;
$riwayatByTanggal = [];
try {
    $connHistory = getConnection();
    ensureLaporanHarianTable($connHistory);
    $startDate = (new DateTimeImmutable('today'))->modify('-' . ($riwayatHari - 1) . ' day')->format('Y-m-d');
    $stmt = $connHistory->prepare('SELECT * FROM laporan_harian WHERE siswa_id = ? AND tanggal BETWEEN ? AND ? ORDER BY tanggal DESC');
    if ($stmt) {
        $stmt->bind_param('iss', $siswaId, $startDate, $tanggalDb);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $tglKey = (string) ($row['tanggal'] ?? '');
                if ($tglKey !== '') {
                    $riwayatByTanggal[$tglKey] = $row;
                }
            }
        }
        $stmt->close();
    }
    $connHistory->close();
} catch (Throwable $e) {
    // Biarkan riwayat kosong bila terjadi masalah.
}

for ($i = 0; $i < $riwayatHari; $i++) {
    $dateKey = (new DateTimeImmutable('today'))->modify('-' . $i . ' day')->format('Y-m-d');
    if (isset($riwayatByTanggal[$dateKey])) {
        $riwayat[] = $riwayatByTanggal[$dateKey];
    } else {
        $riwayat[] = [
            'id' => null,
            'siswa_id' => $siswaId,
            'tanggal' => $dateKey,
            'bangun' => 0,
            'ibadah' => 0,
            'olahraga' => 0,
            'sarapan' => 0,
            'membaca' => 0,
            'membantu' => 0,
            'menabung' => 0,
            'orang_tua_validated_at' => null,
            'guru_validated_at' => null,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KAIH SPANDULA - Progress Harian</title>
    <link rel="stylesheet" href="assets/css/style.css?v=20260324">
    <link rel="stylesheet" href="assets/css/portal.css?v=20260324">
    <link rel="stylesheet" href="assets/css/report-pages.css?v=20260324">
</head>
<body class="report-body role-siswa">
    <div class="report-shell">
        <?php if ($flash !== ''): ?>
            <div class="report-flash <?php echo $flashType === 'error' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <header class="report-topbar">
            <div class="topbar-main">
                <div class="brand-row">
                    <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo Sekolah" class="brand-logo">
                    <div class="title-stack">
                        <p class="title-eyebrow">Kegiatan Harian Siswa</p>
                        <h1>Laporan Kegiatan SISWA</h1>
                        <p>Pilih aktivitas yang sudah dilakukan hari ini, lengkapi detail yang perlu diisi, lalu kirim sekali untuk tanggal <?php echo htmlspecialchars($hariIni); ?>.</p>
                    </div>
                </div>
            </div>
            <div class="top-actions">
                <div class="profile-action-stack">
                    <div class="chip-static" aria-label="Nama siswa">
                        <span class="chip-avatar"><?php echo htmlspecialchars($inisial); ?></span>
                        <span><?php echo htmlspecialchars($namaSiswa !== '' ? $namaSiswa : 'Peserta Didik'); ?></span>
                    </div>
                    <button
                        type="button"
                        class="chip-link password-trigger"
                        id="passwordToggle"
                        aria-expanded="<?php echo $passwordPanelOpen ? 'true' : 'false'; ?>"
                        aria-controls="passwordPanel"
                        onclick="togglePasswordPanel()"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="11" width="18" height="10" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <span>Ubah Password</span>
                    </button>
                    <div class="password-panel <?php echo $passwordPanelOpen ? 'is-open' : ''; ?>" id="passwordPanel">
                        <form method="post" action="" class="password-form">
                            <input type="hidden" name="action" value="change_password">
                            <div class="password-field">
                                <label for="currentPassword">Password Saat Ini</label>
                                <input type="password" id="currentPassword" name="current_password" class="form-input" autocomplete="current-password" required>
                            </div>
                            <div class="password-field">
                                <label for="newPassword">Password Baru</label>
                                <input type="password" id="newPassword" name="new_password" class="form-input" autocomplete="new-password" minlength="6" required>
                            </div>
                            <div class="password-field">
                                <label for="confirmPassword">Konfirmasi Password Baru</label>
                                <input type="password" id="confirmPassword" name="confirm_password" class="form-input" autocomplete="new-password" minlength="6" required>
                            </div>
                            <div class="password-submit-row">
                                <span class="password-note">Minimal 6 karakter.</span>
                                <button type="submit" class="primary-btn password-submit-btn">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
                </div>
                <a href="logout.php" class="chip-link danger">Keluar</a>
            </div>
        </header>

        <section class="overview-layout">
            <article class="report-card unified-summary-card">
                <div class="unified-summary-head">
                    <div>
                    <script>
                    function togglePasswordPanel(forceState) {
                        var panel = document.getElementById('passwordPanel');
                        var toggle = document.getElementById('passwordToggle');
                        if (!panel || !toggle) return;
                        var shouldOpen = typeof forceState === 'boolean' ? forceState : !panel.classList.contains('is-open');
                        panel.classList.toggle('is-open', shouldOpen);
                        toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                    }

                    document.addEventListener('DOMContentLoaded', function () {
                        togglePasswordPanel(<?php echo $passwordPanelOpen ? 'true' : 'false'; ?>);
                    });
                    </script>
                        <p class="section-kicker">Ringkasan Hari Ini</p>
                    </div>
                    <div class="hero-meta">
                        <span class="pill sent">Tanggal: <?php echo htmlspecialchars($hariIni); ?></span>
                        <span class="pill <?php echo $sudahKirimHariIni ? 'done' : 'pending'; ?>"><?php echo $sudahKirimHariIni ? 'Laporan hari ini sudah terkirim' : 'Laporan hari ini belum dikirim'; ?></span>
                        <span class="pill <?php echo $canSubmit ? 'sent' : 'pending'; ?>"><?php echo $canSubmit ? 'Akun siap mengirim' : 'Akun belum sinkron'; ?></span>
                        <span class="pill progress-pill" id="progressValue">0/7</span>
                    </div>
                </div>
            </article>
        </section>

        <section class="report-card section-card form-shell">

            <form method="POST" action="" autocomplete="off">
                <input type="hidden" name="action" value="<?php echo $editMode ? 'edit_report' : 'submit_report'; ?>">
                <?php if ($editMode): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $editId; ?>">
                <?php endif; ?>

                <div class="activity-list">
                    <div class="activity-card" data-activity="bangun">
                        <div class="activity-icon icon-sunrise">🌅</div>
                        <div class="activity-content">
                            <div class="activity-title">Bangun Pagi &amp; Merapikan Tempat Tidur</div>
                            <div class="activity-value">Nilai: Kemandirian &amp; Disiplin</div>
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="bangun" value="1" hidden<?php echo ($editMode && !empty($editReport['bangun'])) ? ' checked' : ''; ?>>
                            <div class="checkbox<?php echo ($editMode && !empty($editReport['bangun'])) ? ' checked' : ''; ?>" onclick="toggleCheck(this)"></div>
                        </div>
                    </div>

                    <div class="activity-card" data-activity="ibadah">
                        <div class="activity-icon icon-pray">🙏</div>
                        <div class="activity-content">
                            <div class="activity-title">Beribadah (Sholat/Ibadah Pagi)</div>
                            <div class="activity-value">Nilai: Religius &amp; Bertakwa</div>
                            <div class="activity-input">
                                <input type="text" name="ibadah_catatan" placeholder="Catatan singkat (opsional)" value="<?php echo $editMode ? htmlspecialchars((string)($editReport['ibadah_catatan'] ?? '')) : ''; ?>">
                            </div>
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="ibadah" value="1" hidden<?php echo ($editMode && !empty($editReport['ibadah'])) ? ' checked' : ''; ?>>
                            <div class="checkbox<?php echo ($editMode && !empty($editReport['ibadah'])) ? ' checked' : ''; ?>" onclick="toggleCheck(this)"></div>
                        </div>
                    </div>

                    <?php $olJenis = $editMode ? (string)($editReport['olahraga_jenis'] ?? '') : ''; ?>
                    <div class="activity-card" data-activity="olahraga">
                        <div class="activity-icon icon-sport">🏃</div>
                        <div class="activity-content">
                            <div class="activity-title">Berolahraga / Aktivitas Fisik</div>
                            <div class="activity-value">Nilai: Kesehatan &amp; Kesejahteraan</div>
                            <div class="activity-input">
                                <select name="olahraga_jenis">
                                    <option value="">Pilih aktivitas</option>
                                    <option value="lari"<?php echo $olJenis==='lari'?' selected':''; ?>>Lari Pagi</option>
                                    <option value="senam"<?php echo $olJenis==='senam'?' selected':''; ?>>Senam</option>
                                    <option value="sepakbola"<?php echo $olJenis==='sepakbola'?' selected':''; ?>>Sepak Bola</option>
                                    <option value="basket"<?php echo $olJenis==='basket'?' selected':''; ?>>Basket</option>
                                    <option value="berenang"<?php echo $olJenis==='berenang'?' selected':''; ?>>Berenang</option>
                                    <option value="lainnya"<?php echo $olJenis==='lainnya'?' selected':''; ?>>Lainnya</option>
                                </select>
                            </div>
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="olahraga" value="1" hidden<?php echo ($editMode && !empty($editReport['olahraga'])) ? ' checked' : ''; ?>>
                            <div class="checkbox<?php echo ($editMode && !empty($editReport['olahraga'])) ? ' checked' : ''; ?>" onclick="toggleCheck(this)"></div>
                        </div>
                    </div>

                    <?php $srMenu = $editMode ? (string)($editReport['sarapan_menu'] ?? '') : ''; ?>
                    <div class="activity-card" data-activity="sarapan">
                        <div class="activity-icon icon-food">🥗</div>
                        <div class="activity-content">
                            <div class="activity-title">Sarapan Sehat &amp; Minum Air</div>
                            <div class="activity-value">Nilai: Pola Hidup Sehat</div>
                            <div class="activity-input">
                                <select name="sarapan_menu">
                                    <option value="">Pilih menu</option>
                                    <option value="nasi"<?php echo $srMenu==='nasi'?' selected':''; ?>>Nasi &amp; Lauk Pauk</option>
                                    <option value="roti"<?php echo $srMenu==='roti'?' selected':''; ?>>Roti &amp; Susu</option>
                                    <option value="bubur"<?php echo $srMenu==='bubur'?' selected':''; ?>>Bubur</option>
                                    <option value="oatmeal"<?php echo $srMenu==='oatmeal'?' selected':''; ?>>Oatmeal</option>
                                    <option value="lainnya"<?php echo $srMenu==='lainnya'?' selected':''; ?>>Lainnya</option>
                                </select>
                            </div>
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="sarapan" value="1" hidden<?php echo ($editMode && !empty($editReport['sarapan'])) ? ' checked' : ''; ?>>
                            <div class="checkbox<?php echo ($editMode && !empty($editReport['sarapan'])) ? ' checked' : ''; ?>" onclick="toggleCheck(this)"></div>
                        </div>
                    </div>

                    <div class="activity-card" data-activity="membaca">
                        <div class="activity-icon icon-book">📚</div>
                        <div class="activity-content">
                            <div class="activity-title">Gemar Membaca (Literasi)</div>
                            <div class="activity-value">Nilai: Bernalar Kritis</div>
                            <div class="input-row">
                                <input type="text" name="membaca_judul" placeholder="Judul bacaan" value="<?php echo $editMode ? htmlspecialchars((string)($editReport['membaca_judul'] ?? '')) : ''; ?>">
                                <input type="number" name="membaca_menit" placeholder="Menit" min="0" value="<?php echo $editMode && !empty($editReport['membaca_menit']) ? (int)$editReport['membaca_menit'] : ''; ?>">
                            </div>
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="membaca" value="1" hidden<?php echo ($editMode && !empty($editReport['membaca'])) ? ' checked' : ''; ?>>
                            <div class="checkbox<?php echo ($editMode && !empty($editReport['membaca'])) ? ' checked' : ''; ?>" onclick="toggleCheck(this)"></div>
                        </div>
                    </div>

                    <?php $mbJenis = $editMode ? (string)($editReport['membantu_jenis'] ?? '') : ''; ?>
                    <div class="activity-card" data-activity="membantu">
                        <div class="activity-icon icon-help">🤝</div>
                        <div class="activity-content">
                            <div class="activity-title">Membantu Orang Tua</div>
                            <div class="activity-value">Nilai: Berbakti &amp; Gotong Royong</div>
                            <div class="activity-input">
                                <select name="membantu_jenis">
                                    <option value="">Pilih aktivitas</option>
                                    <option value="membersihkan"<?php echo $mbJenis==='membersihkan'?' selected':''; ?>>Membersihkan Rumah</option>
                                    <option value="memasak"<?php echo $mbJenis==='memasak'?' selected':''; ?>>Memasak</option>
                                    <option value="mencuci"<?php echo $mbJenis==='mencuci'?' selected':''; ?>>Mencuci Pakaian</option>
                                    <option value="berkebun"<?php echo $mbJenis==='berkebun'?' selected':''; ?>>Berkebun</option>
                                    <option value="lainnya"<?php echo $mbJenis==='lainnya'?' selected':''; ?>>Lainnya</option>
                                </select>
                            </div>
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="membantu" value="1" hidden<?php echo ($editMode && !empty($editReport['membantu'])) ? ' checked' : ''; ?>>
                            <div class="checkbox<?php echo ($editMode && !empty($editReport['membantu'])) ? ' checked' : ''; ?>" onclick="toggleCheck(this)"></div>
                        </div>
                    </div>

                    <div class="activity-card" data-activity="menabung">
                        <div class="activity-icon icon-money">💰</div>
                        <div class="activity-content">
                            <div class="activity-title">Menabung / Hidup Hemat</div>
                            <div class="activity-value">Nilai: Literasi Finansial</div>
                            <div class="input-row with-prefix">
                                <div class="input-prefix">Rp</div>
                                <input type="number" name="menabung_nominal" placeholder="Nominal (opsional)" min="0" value="<?php echo $editMode && !empty($editReport['menabung_nominal']) ? (int)$editReport['menabung_nominal'] : ''; ?>">
                            </div>
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="menabung" value="1" hidden<?php echo ($editMode && !empty($editReport['menabung'])) ? ' checked' : ''; ?>>
                            <div class="checkbox<?php echo ($editMode && !empty($editReport['menabung'])) ? ' checked' : ''; ?>" onclick="toggleCheck(this)"></div>
                        </div>
                    </div>
                </div>

                <div class="submit-wrap">
                    <?php if ($editMode): ?>
                        <button class="submit-btn" type="submit">Simpan Perubahan</button>
                        <a href="progress-harian.php" class="submit-btn" style="background:var(--report-muted,rgba(240,239,232,0.60));text-align:center;text-decoration:none;display:inline-block;">Batal</a>
                        <div class="submit-note">Anda sedang mengedit laporan tanggal <?php echo htmlspecialchars(formatTanggalIndonesia(new DateTimeImmutable($editReport['tanggal']))); ?>. Data yang sudah divalidasi tidak bisa diubah.</div>
                    <?php else: ?>
                        <button class="submit-btn" type="submit" <?php echo ($canSubmit && !$sudahKirimHariIni) ? '' : 'disabled'; ?>>
                            Kirim Laporan Harian
                        </button>
                        <?php if ($sudahKirimHariIni): ?>
                            <div class="submit-note">Laporan sudah terkirim. Besok baru bisa kirim lagi.</div>
                        <?php elseif (!$canSubmit): ?>
                            <div class="submit-note">Akun belum terhubung ke data siswa, jadi submit dinonaktifkan.</div>
                        <?php else: ?>
                            <div class="submit-note">Pastikan checklist sesuai kegiatan nyata sebelum menekan tombol kirim.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="report-card history-card">
            <div class="history-head">
                <div>
                    <p class="section-kicker">Riwayat Singkat</p>
                    <h3>7 hari terakhir</h3>
                    <p>Status kirim dan validasi tetap sama seperti sebelumnya, hanya tampilannya yang diperbarui.</p>
                </div>
            </div>
            <div class="history-list">
                <?php foreach ($riwayat as $lap): ?>
                    <?php
                        $hasReport = !empty($lap['id']);
                        $count = (int) ($lap['bangun'] ?? 0)
                            + (int) ($lap['ibadah'] ?? 0)
                            + (int) ($lap['olahraga'] ?? 0)
                            + (int) ($lap['sarapan'] ?? 0)
                            + (int) ($lap['membaca'] ?? 0)
                            + (int) ($lap['membantu'] ?? 0)
                            + (int) ($lap['menabung'] ?? 0);
                        $tgl = (string) ($lap['tanggal'] ?? '');
                        $tglLabel = $tgl !== '' ? formatTanggalIndonesia(new DateTimeImmutable($tgl)) : '—';
                        $ortuDone = $hasReport && !empty($lap['orang_tua_validated_at']);
                        $guruDone = $hasReport && !empty($lap['guru_validated_at']);
                        $anyDone = $ortuDone || $guruDone;
                    ?>
                    <article class="history-item">
                        <div class="history-top">
                            <div>
                                <div class="history-date"><?php echo htmlspecialchars($tglLabel); ?></div>
                                <div class="history-sub"><?php echo $hasReport ? 'Laporan tersimpan untuk tanggal ini.' : 'Belum ada laporan yang masuk.'; ?></div>
                            </div>
                            <div class="history-progress"><?php echo htmlspecialchars(($hasReport ? $count : 0) . '/7'); ?></div>
                        </div>
                        <div class="hero-meta" aria-label="Status laporan">
                            <?php if ($hasReport): ?>
                                <span class="status-badge sent">Terkirim</span>
                                <span class="status-badge <?php echo $anyDone ? 'done' : 'pending'; ?>">Validasi: <?php echo $anyDone ? 'Sudah' : 'Belum'; ?></span>
                                <span class="status-badge <?php echo $ortuDone ? 'done' : 'pending'; ?>">Orang Tua: <?php echo $ortuDone ? 'Sudah' : 'Belum'; ?></span>
                                <span class="status-badge <?php echo $guruDone ? 'done' : 'pending'; ?>">Guru: <?php echo $guruDone ? 'Sudah' : 'Belum'; ?></span>
                                <?php if (!$anyDone): ?>
                                    <a href="progress-harian.php?edit=<?php echo (int)$lap['id']; ?>" class="status-badge sent" style="text-decoration:none;cursor:pointer;font-weight:600;">✏️ Edit</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="status-badge pending">Belum terkirim</span>
                                <span class="status-badge pending">Validasi: —</span>
                                <span class="status-badge pending">Orang Tua: —</span>
                                <span class="status-badge pending">Guru: —</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <script>
        let checkedCount = 0;
        const totalActivities = 7;

        function updateProgress() {
            checkedCount = document.querySelectorAll('.checkbox.checked').length;
            const progressValue = document.getElementById('progressValue');

            if (progressValue) {
                progressValue.textContent = `${checkedCount}/${totalActivities}`;
            }
        }

        function toggleCheck(element) {
            const wrapper = element.closest('.checkbox-wrapper');
            const input = wrapper ? wrapper.querySelector('input[type="checkbox"]') : null;
            const nextState = !element.classList.contains('checked');
            element.classList.toggle('checked');
            if (input) {
                input.checked = nextState;
            }

            updateProgress();

            if (element.classList.contains('checked')) {
                element.style.transform = 'scale(1.08)';
                setTimeout(function() {
                    element.style.transform = 'scale(1)';
                }, 180);
            }
        }

        document.querySelectorAll('input, select').forEach(function(input) {
            input.addEventListener('focus', function() {
                const card = this.closest('.activity-card');
                if (!card) {
                    return;
                }
                card.style.transform = 'translateY(-2px)';
            });

            input.addEventListener('blur', function() {
                const card = this.closest('.activity-card');
                if (!card) {
                    return;
                }
                card.style.transform = '';
            });
        });

        updateProgress();
    </script>
</body>
</html>

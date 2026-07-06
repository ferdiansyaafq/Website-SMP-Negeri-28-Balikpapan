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

    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $dayName = $hari[(int) $date->format('w')] ?? $date->format('l');
    $day = $date->format('d');
    $month = $bulan[(int) $date->format('n')] ?? $date->format('m');
    $year = $date->format('Y');

    return $dayName . ', ' . $day . ' ' . $month . ' ' . $year;
}

$flash = '';
$flashType = 'success';
$passwordPanelOpen = false;

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

$laporanId = (int) ($_GET['id'] ?? 0);
$laporan = null;

try {
    ensureLaporanHarianTable($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($confirmPassword === '') {
            throw new RuntimeException('Konfirmasi password baru wajib diisi.');
        }
        if (!hash_equals($newPassword, $confirmPassword)) {
            throw new RuntimeException('Konfirmasi password baru tidak sama.');
        }

        changePortalUserPassword($conn, (int) $_SESSION['portal_user_id'], $currentPassword, $newPassword, ['orang_tua']);
        $flash = 'Password akun orang tua berhasil diperbarui.';
        $flashType = 'success';
        $passwordPanelOpen = false;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'validate_parent') {
        $laporanIdPost = (int) ($_POST['laporan_id'] ?? 0);
        if ($laporanIdPost <= 0) {
            throw new RuntimeException('ID laporan tidak valid.');
        }

        $stmtValidate = $conn->prepare('UPDATE laporan_harian SET orang_tua_validated_at = IFNULL(orang_tua_validated_at, NOW()) WHERE id = ? AND siswa_id = ?');
        if (!$stmtValidate) {
            throw new RuntimeException('Gagal menyiapkan validasi orang tua.');
        }
        $stmtValidate->bind_param('ii', $laporanIdPost, $siswaId);
        if (!$stmtValidate->execute()) {
            throw new RuntimeException('Gagal menyimpan validasi orang tua.');
        }
        $stmtValidate->close();

        $flash = 'Validasi orang tua berhasil disimpan.';
        $flashType = 'success';
        $laporanId = $laporanIdPost;
    }

    if ($laporanId > 0) {
        $stmt = $conn->prepare(
            'SELECT lh.*, s.nama_siswa, s.kelas, s.nisn
             FROM laporan_harian lh
             JOIN siswa s ON s.id = lh.siswa_id
             WHERE lh.id = ? AND lh.siswa_id = ?
             LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('ii', $laporanId, $siswaId);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                $laporan = $res ? $res->fetch_assoc() : null;
            }
            $stmt->close();
        }
    }

    if (!$laporan) {
        $stmtLatest = $conn->prepare(
            'SELECT lh.*, s.nama_siswa, s.kelas, s.nisn
             FROM laporan_harian lh
             JOIN siswa s ON s.id = lh.siswa_id
             WHERE lh.siswa_id = ?
             ORDER BY lh.tanggal DESC
             LIMIT 1'
        );
        if ($stmtLatest) {
            $stmtLatest->bind_param('i', $siswaId);
            if ($stmtLatest->execute()) {
                $res = $stmtLatest->get_result();
                $laporan = $res ? $res->fetch_assoc() : null;
            }
            $stmtLatest->close();
        }
    }
} catch (Throwable $e) {
    $flash = $e->getMessage();
    $flashType = 'error';
    if ((string) ($_POST['action'] ?? '') === 'change_password') {
        $passwordPanelOpen = true;
    }
} finally {
    $conn->close();
}

$namaSiswa = (string) ($laporan['nama_siswa'] ?? $profile['nama_siswa'] ?? $profile['display_name'] ?? '');
$inisial = strtoupper(mb_substr(trim($namaSiswa) !== '' ? trim($namaSiswa) : 'S', 0, 1, 'UTF-8'));

$tanggalLabel = $laporan ? formatTanggalIndonesia((string) ($laporan['tanggal'] ?? '')) : '—';
$ortuDone = $laporan && !empty($laporan['orang_tua_validated_at']);
$guruDone = $laporan && !empty($laporan['guru_validated_at']);
$anyDone = $ortuDone || $guruDone; // cukup salah satu (ortu/guru)
$lapId = (int) ($laporan['id'] ?? 0);

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
    <title>Validasi Orang Tua — KAIH</title>
    <link rel="stylesheet" href="assets/css/style.css?v=20260324">
    <link rel="stylesheet" href="assets/css/portal.css?v=20260324">
    <link rel="stylesheet" href="assets/css/report-pages.css?v=20260324">
</head>
<body class="report-body role-ortu">
    <div class="report-shell">
        <?php if ($flash !== ''): ?>
            <div class="report-flash <?php echo $flashType === 'error' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <header class="report-topbar">
            <div class="topbar-main">
                <div class="brand-row">
                    <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo Sekolah" class="brand-logo">
                    <div class="title-stack">
                        <p class="title-eyebrow">Review Orang Tua</p>
                        <h1>Tinjau laporan siswa sebelum memberi konfirmasi.</h1>
                        <p>Halaman ini tetap menampilkan data laporan yang sama. Desainnya diperbarui agar selaras dengan frontend portal dan lebih jelas saat dipakai memantau aktivitas anak.</p>
                    </div>
                </div>
                <div class="quick-nav" aria-label="Menu Orang Tua">
                    <a class="active" href="ortu-validasi.php<?php echo $lapId > 0 ? '?id=' . (int) $lapId : ''; ?>">Validasi</a>
                    <a href="ortu-grafik-bulanan.php">Grafik Bulanan</a>
                </div>
            </div>
            <div class="top-actions">
                <div class="profile-action-stack">
                    <div class="chip-static" aria-label="Nama siswa">
                        <span class="chip-avatar"><?php echo htmlspecialchars($inisial); ?></span>
                        <span><?php echo htmlspecialchars($namaSiswa !== '' ? $namaSiswa : 'Siswa'); ?></span>
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

        <?php if (!$laporan): ?>
            <section class="empty-card">
                <strong>Belum ada laporan harian yang bisa ditinjau.</strong>
                Laporan akan muncul di halaman ini setelah siswa mengirimkan kegiatan hariannya.
            </section>
        <?php else: ?>
            <section class="report-card validation-summary-card">
                <div class="validation-summary-head">
                    <div class="hero-meta validation-summary-chips">
                        <span class="pill sent">Laporan terkirim</span>
                        <span class="pill <?php echo $anyDone ? 'done' : 'pending'; ?>">Validasi umum: <?php echo $anyDone ? 'Sudah' : 'Belum'; ?></span>
                        <span class="pill <?php echo $ortuDone ? 'done' : 'pending'; ?>">Orang Tua: <?php echo $ortuDone ? 'Sudah' : 'Belum'; ?></span>
                        <span class="pill <?php echo $guruDone ? 'done' : 'pending'; ?>">Guru: <?php echo $guruDone ? 'Sudah' : 'Belum'; ?></span>
                    </div>
                </div>
                <div class="validation-summary-grid">
                    <article class="validation-summary-item validation-summary-count-item">
                        <span class="summary-label">Progress</span>
                        <div class="summary-value"><?php echo htmlspecialchars($count . '/7'); ?></div>
                    </article>
                    <article class="validation-summary-item">
                        <span class="summary-label">Tanggal Laporan</span>
                        <div class="summary-value"><?php echo htmlspecialchars($tanggalLabel); ?></div>
                    </article>
                    <article class="validation-summary-item">
                        <span class="summary-label">NISN</span>
                        <div class="summary-value"><?php echo htmlspecialchars((string) ($laporan['nisn'] ?? '—')); ?></div>
                    </article>
                    <article class="validation-summary-item">
                        <span class="summary-label">Kelas</span>
                        <div class="summary-value"><?php echo htmlspecialchars((string) ($laporan['kelas'] ?? '—')); ?></div>
                    </article>
                </div>
            </section>

            <section class="report-card section-card">
                <div class="section-head">
                    <div>
                        <p class="section-kicker">Aktivitas Terkirim</p>
                        <h3>Isi laporan kegiatan siswa</h3>
                        <p>Semua isi tetap bersumber dari data laporan yang sama, hanya disusun lebih rapi agar mudah dipantau.</p>
                    </div>
                </div>

                <div class="activity-list">
                    <div class="activity-card">
                        <div class="activity-icon icon-sunrise">🌅</div>
                        <div class="activity-content">
                            <div class="activity-title">Bangun Pagi &amp; Merapikan Tempat Tidur</div>
                            <div class="activity-value">Nilai: Kemandirian &amp; Disiplin</div>
                        </div>
                        <div class="check-pill"><span class="check-badge <?php echo !empty($laporan['bangun']) ? 'on' : ''; ?>"><?php echo !empty($laporan['bangun']) ? '✓' : '—'; ?></span></div>
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
                        <div class="check-pill"><span class="check-badge <?php echo !empty($laporan['ibadah']) ? 'on' : ''; ?>"><?php echo !empty($laporan['ibadah']) ? '✓' : '—'; ?></span></div>
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
                        <div class="check-pill"><span class="check-badge <?php echo !empty($laporan['olahraga']) ? 'on' : ''; ?>"><?php echo !empty($laporan['olahraga']) ? '✓' : '—'; ?></span></div>
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
                        <div class="check-pill"><span class="check-badge <?php echo !empty($laporan['sarapan']) ? 'on' : ''; ?>"><?php echo !empty($laporan['sarapan']) ? '✓' : '—'; ?></span></div>
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
                        <div class="check-pill"><span class="check-badge <?php echo !empty($laporan['membaca']) ? 'on' : ''; ?>"><?php echo !empty($laporan['membaca']) ? '✓' : '—'; ?></span></div>
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
                        <div class="check-pill"><span class="check-badge <?php echo !empty($laporan['membantu']) ? 'on' : ''; ?>"><?php echo !empty($laporan['membantu']) ? '✓' : '—'; ?></span></div>
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
                        <div class="check-pill"><span class="check-badge <?php echo !empty($laporan['menabung']) ? 'on' : ''; ?>"><?php echo !empty($laporan['menabung']) ? '✓' : '—'; ?></span></div>
                    </div>
                </div>
            </section>

            <section class="report-card section-card">
                <div class="section-head">
                    <div>
                        <p class="section-kicker">Aksi Orang Tua</p>
                        <h3>Konfirmasi laporan</h3>
                        <p>Fungsi tombol tetap sama: menyimpan validasi orang tua pada laporan yang sedang ditinjau.</p>
                    </div>
                </div>
                <div class="validate-actions">
                    <?php if ($anyDone): ?>
                        <button type="button" class="primary-btn btn-disabled" disabled>Sudah divalidasi</button>
                        <div class="helper-note">Status validasi sudah tersimpan sehingga tombol dinonaktifkan.</div>
                    <?php else: ?>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="validate_parent">
                            <input type="hidden" name="laporan_id" value="<?php echo (int) $lapId; ?>">
                            <button type="submit" class="primary-btn">Konfirmasi Orang Tua</button>
                        </form>
                        <div class="helper-note">Pastikan isi laporan sudah sesuai sebelum menekan tombol konfirmasi.</div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
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
</body>
</html>


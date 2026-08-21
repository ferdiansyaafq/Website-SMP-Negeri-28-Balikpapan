<?php
// aplikasi/ortu/monitoring.php
require_once '../includes/header-kaih.php';

// Halaman khusus untuk role orang_tua
if (($_SESSION['role'] ?? '') !== 'orang_tua') {
    header('Location: ../index.php');
    exit;
}

$siswa_id = (int) ($_SESSION['siswa_id'] ?? 0);

// ============================================================
// PASTIKAN TABEL laporan_harian ADA
// ============================================================
function ensureLaporanHarianTableOrtu(PDO $pdo): void
{
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `laporan_harian` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `siswa_id` INT NOT NULL,
            `tanggal` DATE NOT NULL,
            `bangun` TINYINT(1) NOT NULL DEFAULT 0,
            `ibadah` TINYINT(1) NOT NULL DEFAULT 0,
            `ibadah_catatan` VARCHAR(255) NULL,
            `olahraga` TINYINT(1) NOT NULL DEFAULT 0,
            `olahraga_jenis` VARCHAR(50) NULL,
            `sarapan` TINYINT(1) NOT NULL DEFAULT 0,
            `sarapan_menu` VARCHAR(50) NULL,
            `membaca` TINYINT(1) NOT NULL DEFAULT 0,
            `membaca_judul` VARCHAR(255) NULL,
            `membaca_menit` INT NULL,
            `membantu` TINYINT(1) NOT NULL DEFAULT 0,
            `membantu_jenis` VARCHAR(50) NULL,
            `menabung` TINYINT(1) NOT NULL DEFAULT 0,
            `menabung_nominal` INT NULL,
            `orang_tua_validated_at` DATETIME NULL,
            `guru_validated_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_siswa_tanggal` (`siswa_id`, `tanggal`),
            INDEX `idx_tanggal` (`tanggal`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {
        // Abaikan, akan gagal halus di query berikutnya
    }
}
ensureLaporanHarianTableOrtu($pdo);

// Data anak
$anak = null;
if ($siswa_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id, nisn, nama_siswa, kelas FROM siswa WHERE id = ?");
        $stmt->execute([$siswa_id]);
        $anak = $stmt->fetch();
    } catch (PDOException $e) {
        $anak = null;
    }
}

// ============================================================
// FLASH MESSAGE (dari redirect setelah aksi POST)
// ============================================================
$flash = $_SESSION['flash_ortu_msg'] ?? '';
$flashType = $_SESSION['flash_ortu_type'] ?? 'success';
unset($_SESSION['flash_ortu_msg'], $_SESSION['flash_ortu_type']);

// ============================================================
// PROSES AKSI: VALIDASI / BATAL SETUJU
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $laporanId = (int) ($_POST['laporan_id'] ?? 0);

    if ($siswa_id <= 0) {
        $_SESSION['flash_ortu_msg'] = 'Akun orang tua belum terhubung ke data siswa.';
        $_SESSION['flash_ortu_type'] = 'error';
    } elseif ($laporanId > 0 && in_array($action, ['validate_ortu', 'batal_ortu'], true)) {
        try {
            // Pastikan laporan ini memang milik anak dari akun ortu yang sedang login
            $chk = $pdo->prepare('SELECT id FROM laporan_harian WHERE id = ? AND siswa_id = ?');
            $chk->execute([$laporanId, $siswa_id]);

            if ($chk->fetch()) {
                if ($action === 'validate_ortu') {
                    $upd = $pdo->prepare('UPDATE laporan_harian SET orang_tua_validated_at = IFNULL(orang_tua_validated_at, NOW()) WHERE id = ?');
                    $upd->execute([$laporanId]);
                    $_SESSION['flash_ortu_msg'] = 'Kegiatan berhasil divalidasi. Terima kasih sudah memantau anak Anda.';
                    $_SESSION['flash_ortu_type'] = 'success';
                } else {
                    $upd = $pdo->prepare('UPDATE laporan_harian SET orang_tua_validated_at = NULL WHERE id = ?');
                    $upd->execute([$laporanId]);
                    $_SESSION['flash_ortu_msg'] = 'Validasi berhasil dibatalkan. Silakan validasi kembali jika sudah sesuai.';
                    $_SESSION['flash_ortu_type'] = 'success';
                }
            } else {
                $_SESSION['flash_ortu_msg'] = 'Laporan tidak ditemukan atau bukan milik anak Anda.';
                $_SESSION['flash_ortu_type'] = 'error';
            }
        } catch (PDOException $e) {
            $_SESSION['flash_ortu_msg'] = 'Gagal memproses aksi. Silakan coba lagi.';
            $_SESSION['flash_ortu_type'] = 'error';
        }
    }

    $qs = (string) ($_POST['redirect_qs'] ?? '');
    header('Location: monitoring.php' . ($qs !== '' ? ('?' . $qs) : ''));
    exit;
}

// ============================================================
// FILTER BULAN
// ============================================================
$filterBulan = trim((string) ($_GET['bulan'] ?? date('Y-m')));
if (!preg_match('/^\d{4}-\d{2}$/', $filterBulan)) {
    $filterBulan = date('Y-m');
}
$redirectQs = http_build_query(['bulan' => $filterBulan]);

// ============================================================
// AMBIL DATA KEGIATAN SEBULAN
// ============================================================
$laporanList = [];
$statTotal = 0;
$statValidOrtu = 0;
$statPendingOrtu = 0;
$statValidGuru = 0;

if ($siswa_id > 0) {
    try {
        $bd = DateTimeImmutable::createFromFormat('Y-m-d', $filterBulan . '-01') ?: new DateTimeImmutable();
        $start = $bd->format('Y-m-01');
        $end = $bd->format('Y-m-t');

        $stmt = $pdo->prepare('SELECT * FROM laporan_harian WHERE siswa_id = ? AND tanggal BETWEEN ? AND ? ORDER BY tanggal DESC');
        $stmt->execute([$siswa_id, $start, $end]);
        $laporanList = $stmt->fetchAll();

        $statTotal = count($laporanList);
        foreach ($laporanList as $lh) {
            if (!empty($lh['orang_tua_validated_at'])) {
                $statValidOrtu++;
            } else {
                $statPendingOrtu++;
            }
            if (!empty($lh['guru_validated_at'])) {
                $statValidGuru++;
            }
        }
    } catch (PDOException $e) {
        $laporanList = [];
    }
}

$bulanNamesOrtu = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$hariNamesOrtu = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function fmtTanggalOrtu(string $ymd): string
{
    global $bulanNamesOrtu, $hariNamesOrtu;
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);
    if (!$d) {
        return $ymd;
    }
    $hari = $hariNamesOrtu[(int) $d->format('w')] ?? $d->format('l');
    $bulan = $bulanNamesOrtu[(int) $d->format('n')] ?? $d->format('m');
    return $hari . ', ' . (int) $d->format('j') . ' ' . $bulan . ' ' . $d->format('Y');
}

function skorHarianOrtu(array $lh): int
{
    return (int) ($lh['bangun'] ?? 0)
        + (int) ($lh['ibadah'] ?? 0)
        + (int) ($lh['olahraga'] ?? 0)
        + (int) ($lh['sarapan'] ?? 0)
        + (int) ($lh['membaca'] ?? 0)
        + (int) ($lh['membantu'] ?? 0)
        + (int) ($lh['menabung'] ?? 0);
}
?>

<style>
    .modern-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
    .filter-grid { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
    .filter-item { display: flex; flex-direction: column; gap: 5px; }
    .filter-item label { font-size: 12px; font-weight: bold; color: #475569; text-transform: uppercase; }
    .filter-item input { padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; }
    .btn-primary { background: #0284c7; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; }
    .btn-success { background: #10b981; color: #fff; border: none; padding: 9px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; }
    .btn-outline-danger { background: #fff; color: #dc2626; border: 1.5px solid #fecaca; padding: 8px 16px; border-radius: 8px; font-weight: bold; cursor: pointer; }
    .btn-outline-danger:hover { background: #fef2f2; }

    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
    .stat-box { background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; text-align: center; }
    .stat-box .title { font-size: 12px; color: #64748b; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
    .stat-box .value { font-size: 26px; font-weight: 800; color: #1e293b; }

    .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
    .badge-green { background: #d1fae5; color: #059669; }
    .badge-amber { background: #fef3c7; color: #b45309; }
    .badge-blue { background: #dbeafe; color: #2563eb; }

    .accordion-day { border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 12px; overflow: hidden; }
    .accordion-header { background: #f8fafc; padding: 15px 18px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: bold; flex-wrap: wrap; gap: 8px; }
    .accordion-header .badges { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; font-weight: normal; }
    .accordion-body { padding: 18px; display: none; background: #fff; border-top: 1px solid #e2e8f0; }
    .detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
    .detail-item { background: #f1f5f9; padding: 10px 12px; border-radius: 8px; }
    .detail-item strong { display: block; font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 3px; }
    .action-row { margin-top: 16px; padding-top: 14px; border-top: 1px dashed #e2e8f0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .helper-note { font-size: 12px; color: #94a3b8; }

    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 14px; font-weight: 600; }
    .alert-success { background: #d1fae5; color: #059669; border-left: 4px solid #10b981; }
    .alert-error { background: #fee2e2; color: #dc2626; border-left: 4px solid #dc2626; }
</style>

<div class="content-area">
    <div style="margin-bottom: 20px;">
        <h2 style="margin:0; color:#1e293b;">📋 Kegiatan Anak</h2>
        <p style="margin:5px 0 0; color:#64748b;">Cek isi kegiatan harian anak Anda dan kelola validasi orang tua.</p>
    </div>

    <?php if ($flash !== ''): ?>
        <div class="alert <?php echo $flashType === 'error' ? 'alert-error' : 'alert-success'; ?>"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <?php if (!$anak): ?>
        <div class="modern-card" style="text-align:center; color:#64748b; padding:40px;">
            Akun orang tua Anda belum terhubung ke data siswa. Silakan hubungi admin sekolah.
        </div>
    <?php else: ?>
        <div class="modern-card" style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 10px; background:#f0f9ff; border-color:#bae6fd;">
            <div>
                <h3 style="margin:0; color:#0c4a6e;"><?php echo htmlspecialchars($anak['nama_siswa']); ?></h3>
                <p style="margin:5px 0 0; color:#0284c7; font-weight:600;">NISN: <?php echo htmlspecialchars($anak['nisn'] ?? '-'); ?> | Kelas: <?php echo htmlspecialchars($anak['kelas'] ?? '-'); ?></p>
            </div>
        </div>

        <div class="modern-card">
            <form method="GET" class="filter-grid">
                <div class="filter-item">
                    <label>Pilih Bulan</label>
                    <input type="month" name="bulan" value="<?php echo htmlspecialchars($filterBulan); ?>">
                </div>
                <button type="submit" class="btn-primary">Tampilkan</button>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-box"><div class="title">Total Kegiatan</div><div class="value"><?php echo $statTotal; ?></div></div>
            <div class="stat-box"><div class="title">Sudah Divalidasi</div><div class="value" style="color:#059669;"><?php echo $statValidOrtu; ?></div></div>
            <div class="stat-box"><div class="title">Menunggu Validasi</div><div class="value" style="color:#f59e0b;"><?php echo $statPendingOrtu; ?></div></div>
        </div>

        <?php if (empty($laporanList)): ?>
            <div class="modern-card" style="text-align:center; color:#64748b; padding:40px;">
                Belum ada kegiatan anak yang tercatat pada bulan ini.
            </div>
        <?php else: foreach ($laporanList as $idx => $lh):
            $score = skorHarianOrtu($lh);
            $ortuDone = !empty($lh['orang_tua_validated_at']);
            $guruDone = !empty($lh['guru_validated_at']);
            $dayId = 'ortu-day-' . $idx;
        ?>
        <div class="accordion-day">
            <div class="accordion-header" onclick="toggleAccOrtu('<?php echo $dayId; ?>')">
                <div>📅 <?php echo htmlspecialchars(fmtTanggalOrtu($lh['tanggal'])); ?> <span class="badge badge-blue"><?php echo $score; ?>/7 Kegiatan</span></div>
                <div class="badges">
                    <?php echo $ortuDone ? '<span class="badge badge-green">✓ Anda Sudah Validasi</span>' : '<span class="badge badge-amber">Menunggu Validasi Anda</span>'; ?>
                    <?php if ($guruDone): ?><span class="badge badge-blue">✓ Divalidasi Guru</span><?php endif; ?>
                    <span>▼</span>
                </div>
            </div>
            <div class="accordion-body" id="<?php echo $dayId; ?>">
                <div class="detail-grid">
                    <div class="detail-item"><strong>🌅 Bangun Pagi</strong> <?php echo !empty($lh['bangun']) ? 'Sudah' : 'Belum'; ?></div>
                    <div class="detail-item">
                        <strong>🙏 Ibadah</strong> <?php echo !empty($lh['ibadah']) ? 'Sudah' : 'Belum'; ?>
                        <?php if (!empty($lh['ibadah_catatan'])): ?><br><span style="font-size:12px; color:#64748b;"><?php echo htmlspecialchars($lh['ibadah_catatan']); ?></span><?php endif; ?>
                    </div>
                    <div class="detail-item">
                        <strong>🏃 Olahraga</strong> <?php echo !empty($lh['olahraga']) ? 'Sudah' : 'Belum'; ?>
                        <?php if (!empty($lh['olahraga_jenis'])): ?><br><span style="font-size:12px; color:#64748b;"><?php echo htmlspecialchars($lh['olahraga_jenis']); ?></span><?php endif; ?>
                    </div>
                    <div class="detail-item">
                        <strong>🥗 Sarapan</strong> <?php echo !empty($lh['sarapan']) ? 'Sudah' : 'Belum'; ?>
                        <?php if (!empty($lh['sarapan_menu'])): ?><br><span style="font-size:12px; color:#64748b;"><?php echo htmlspecialchars($lh['sarapan_menu']); ?></span><?php endif; ?>
                    </div>
                    <div class="detail-item">
                        <strong>📚 Membaca</strong> <?php echo !empty($lh['membaca']) ? 'Sudah' . (!empty($lh['membaca_menit']) ? ' (' . (int) $lh['membaca_menit'] . ' menit)' : '') : 'Belum'; ?>
                        <?php if (!empty($lh['membaca_judul'])): ?><br><span style="font-size:12px; color:#64748b;"><?php echo htmlspecialchars($lh['membaca_judul']); ?></span><?php endif; ?>
                    </div>
                    <div class="detail-item">
                        <strong>🤝 Membantu Ortu</strong> <?php echo !empty($lh['membantu']) ? 'Sudah' : 'Belum'; ?>
                        <?php if (!empty($lh['membantu_jenis'])): ?><br><span style="font-size:12px; color:#64748b;"><?php echo htmlspecialchars($lh['membantu_jenis']); ?></span><?php endif; ?>
                    </div>
                    <div class="detail-item">
                        <strong>💰 Menabung</strong> <?php echo !empty($lh['menabung']) ? 'Sudah' : 'Belum'; ?>
                        <?php if (!empty($lh['menabung_nominal'])): ?><br><span style="font-size:12px; color:#64748b;">Rp <?php echo number_format((int) $lh['menabung_nominal'], 0, ',', '.'); ?></span><?php endif; ?>
                    </div>
                </div>

                <div class="action-row">
                    <?php if ($ortuDone): ?>
                        <form method="POST" onsubmit="return confirm('Batalkan validasi laporan tanggal <?php echo htmlspecialchars(fmtTanggalOrtu($lh['tanggal'])); ?>?');">
                            <input type="hidden" name="action" value="batal_ortu">
                            <input type="hidden" name="laporan_id" value="<?php echo (int) $lh['id']; ?>">
                            <input type="hidden" name="redirect_qs" value="<?php echo htmlspecialchars($redirectQs); ?>">
                            <button type="submit" class="btn-outline-danger">↩️ Batal Setuju</button>
                        </form>
                        <span class="helper-note">Divalidasi pada <?php echo date('d M Y H:i', strtotime((string) $lh['orang_tua_validated_at'])); ?></span>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="validate_ortu">
                            <input type="hidden" name="laporan_id" value="<?php echo (int) $lh['id']; ?>">
                            <input type="hidden" name="redirect_qs" value="<?php echo htmlspecialchars($redirectQs); ?>">
                            <button type="submit" class="btn-success">✓ Setujui Kegiatan Ini</button>
                        </form>
                        <span class="helper-note">Pastikan isi kegiatan sudah sesuai sebelum menyetujui.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    <?php endif; ?>
</div>

<script>
function toggleAccOrtu(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}
document.addEventListener('DOMContentLoaded', function () {
    var first = document.querySelector('.accordion-body');
    if (first) first.style.display = 'block';
});
</script>
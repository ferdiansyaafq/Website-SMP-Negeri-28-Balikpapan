<?php
// aplikasi/ortu/index.php
require_once '../includes/auth.php';

if (($_SESSION['role'] ?? '') !== 'orang_tua') {
    header('Location: ../index.php');
    exit;
}

require_once '../includes/header-kaih.php';

$siswa_id = (int) ($_SESSION['siswa_id'] ?? 0);

$anak = null;
$totalKegiatan = 0;
$validasiOrtu = 0;
$menungguValidasi = 0;
$terbaru = [];

if ($siswa_id > 0) {
    try {
        $stmt = $pdo->prepare('SELECT id, nisn, nama_siswa, kelas FROM siswa WHERE id = ?');
        $stmt->execute([$siswa_id]);
        $anak = $stmt->fetch();

        $stmt2 = $pdo->prepare('SELECT COUNT(*) FROM laporan_harian WHERE siswa_id = ?');
        $stmt2->execute([$siswa_id]);
        $totalKegiatan = (int) $stmt2->fetchColumn();

        $stmt3 = $pdo->prepare('SELECT COUNT(*) FROM laporan_harian WHERE siswa_id = ? AND orang_tua_validated_at IS NOT NULL');
        $stmt3->execute([$siswa_id]);
        $validasiOrtu = (int) $stmt3->fetchColumn();

        $menungguValidasi = $totalKegiatan - $validasiOrtu;

        $stmt4 = $pdo->prepare('SELECT * FROM laporan_harian WHERE siswa_id = ? ORDER BY tanggal DESC LIMIT 5');
        $stmt4->execute([$siswa_id]);
        $terbaru = $stmt4->fetchAll();
    } catch (PDOException $e) {
        // Tabel laporan_harian mungkin belum ada; biarkan angka tetap 0
    }
}

$bulanNamesOrtuIdx = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];

function fmtTanggalSingkatOrtu(string $ymd): string
{
    global $bulanNamesOrtuIdx;
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);
    if (!$d) {
        return $ymd;
    }
    return (int) $d->format('j') . ' ' . ($bulanNamesOrtuIdx[(int) $d->format('n')] ?? $d->format('m')) . ' ' . $d->format('Y');
}

function skorHarianOrtuIdx(array $lh): int
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
    .ortu-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .ortu-table th, .ortu-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
    .ortu-table th { color: #64748b; text-transform: uppercase; font-size: 11px; }
    .ortu-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
    .ortu-badge-green { background: #d1fae5; color: #059669; }
    .ortu-badge-amber { background: #fef3c7; color: #b45309; }
</style>

<?php if ($anak): ?>
<div class="card" style="margin-bottom: 20px; background:#f0f9ff; border-left: 4px solid #0284c7;">
    <h3 style="margin:0;">👋 Selamat datang, Orang Tua dari <?php echo htmlspecialchars($anak['nama_siswa']); ?></h3>
    <p style="margin:5px 0 0; color:#0284c7;">Kelas <?php echo htmlspecialchars($anak['kelas'] ?? '-'); ?> | NISN <?php echo htmlspecialchars($anak['nisn'] ?? '-'); ?></p>
</div>
<?php else: ?>
<div class="card" style="margin-bottom: 20px; background:#fef2f2; border-left: 4px solid #dc2626;">
    Akun orang tua Anda belum terhubung ke data siswa. Silakan hubungi admin sekolah.
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px;">
    <div class="card" style="text-align: center; border-top: 4px solid #0284c7;">
        <div style="font-size: 32px; font-weight: 800; color: #0284c7;"><?php echo $totalKegiatan; ?></div>
        <div style="color: #64748b; font-size: 14px;">Total Kegiatan Anak</div>
    </div>
    <div class="card" style="text-align: center; border-top: 4px solid #10b981;">
        <div style="font-size: 32px; font-weight: 800; color: #10b981;"><?php echo $validasiOrtu; ?></div>
        <div style="color: #64748b; font-size: 14px;">Telah Divalidasi</div>
    </div>
    <div class="card" style="text-align: center; border-top: 4px solid #f59e0b;">
        <div style="font-size: 32px; font-weight: 800; color: #f59e0b;"><?php echo $menungguValidasi; ?></div>
        <div style="color: #64748b; font-size: 14px;">Menunggu Validasi</div>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">📋 Kegiatan Anak Terbaru</h3>
    <?php if (empty($terbaru)): ?>
        <p style="color: #64748b;">Belum ada kegiatan anak yang tercatat.</p>
    <?php else: ?>
        <table class="ortu-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kegiatan Terisi</th>
                    <th>Status Validasi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($terbaru as $lh): ?>
                <tr>
                    <td><?php echo htmlspecialchars(fmtTanggalSingkatOrtu($lh['tanggal'])); ?></td>
                    <td><?php echo skorHarianOrtuIdx($lh); ?>/7</td>
                    <td>
                        <?php if (!empty($lh['orang_tua_validated_at'])): ?>
                            <span class="ortu-badge ortu-badge-green">✓ Divalidasi</span>
                        <?php else: ?>
                            <span class="ortu-badge ortu-badge-amber">Menunggu</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <div style="margin-top: 18px;">
        <a href="monitoring.php" style="background:#0284c7; color:#fff; padding:10px 20px; border-radius:8px; font-weight:bold; text-decoration:none; display:inline-block;">📋 Lihat & Kelola Semua Kegiatan →</a>
    </div>
</div>

<?php
?>

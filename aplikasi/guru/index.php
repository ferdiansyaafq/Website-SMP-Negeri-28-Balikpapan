<?php
// aplikasi/guru/index.php
require_once '../includes/header-kaih.php';

// Ambil data statistik dari database
$guru_id = $_SESSION['guru_id'] ?? 0;
$kelas_guru = '';

if ($guru_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT kelas FROM guru WHERE id = ?");
        $stmt->execute([$guru_id]);
        $guru = $stmt->fetch();
        if ($guru) {
            $kelas_guru = $guru['kelas'] ?? '';
        }
    } catch (PDOException $e) {
        // Abaikan error
    }
}

// Hitung statistik
$total_siswa = 0;
$kegiatan_hari_ini = 0;
$menunggu_validasi = 0;

if ($kelas_guru) {
    try {
        // Total siswa di kelas guru
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM siswa WHERE kelas = ?");
        $stmt->execute([$kelas_guru]);
        $total_siswa = $stmt->fetchColumn();
        
        // Kegiatan hari ini
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM laporan_harian lh 
                               JOIN siswa s ON lh.siswa_id = s.id 
                               WHERE s.kelas = ? AND lh.tanggal = ?");
        $stmt->execute([$kelas_guru, date('Y-m-d')]);
        $kegiatan_hari_ini = $stmt->fetchColumn();
        
        // Menunggu validasi
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM laporan_harian lh 
                               JOIN siswa s ON lh.siswa_id = s.id 
                               WHERE s.kelas = ? AND lh.tanggal = ? 
                               AND guru_validated_at IS NULL");
        $stmt->execute([$kelas_guru, date('Y-m-d')]);
        $menunggu_validasi = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Tabel mungkin belum ada
    }
}
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px;">
    <div class="card" style="text-align: center; border-top: 4px solid #0284c7;">
        <div style="font-size: 32px; font-weight: 800; color: #0284c7;"><?php echo $total_siswa; ?></div>
        <div style="color: #64748b; font-size: 14px;">Total Siswa</div>
        <?php if ($kelas_guru): ?>
        <div style="color: #94a3b8; font-size: 12px; margin-top: 4px;">Kelas <?php echo htmlspecialchars($kelas_guru); ?></div>
        <?php endif; ?>
    </div>
    <div class="card" style="text-align: center; border-top: 4px solid #10b981;">
        <div style="font-size: 32px; font-weight: 800; color: #10b981;"><?php echo $kegiatan_hari_ini; ?></div>
        <div style="color: #64748b; font-size: 14px;">Kegiatan Hari Ini</div>
    </div>
    <div class="card" style="text-align: center; border-top: 4px solid #f59e0b;">
        <div style="font-size: 32px; font-weight: 800; color: #f59e0b;"><?php echo $menunggu_validasi; ?></div>
        <div style="color: #64748b; font-size: 14px;">Menunggu Validasi</div>
    </div>
</div>

<div class="card">
    <h3>📋 Kegiatan Siswa Hari Ini</h3>
    <?php if ($kegiatan_hari_ini > 0): ?>
        <p style="color: #1e293b;">Ada <?php echo $kegiatan_hari_ini; ?> kegiatan siswa yang perlu diperhatikan.</p>
        <a href="monitoring.php" style="color: #0284c7; text-decoration: none; font-weight: 600;">Lihat detail →</a>
    <?php else: ?>
        <p style="color: #64748b;">Belum ada kegiatan siswa yang perlu dimonitoring hari ini.</p>
    <?php endif; ?>
</div>

<?php
?>
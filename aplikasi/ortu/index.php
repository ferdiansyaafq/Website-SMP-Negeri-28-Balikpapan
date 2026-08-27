<?php
// aplikasi/ortu/index.php
session_start();
require_once '../../config/database.php';
require_once '../includes/header-kaih.php';

$siswa_id = $_SESSION['siswa_id'] ?? 0;

$total_kegiatan = 0;
$telah_divalidasi = 0;
$menunggu_validasi = 0;
$riwayat_terbaru = [];

if ($siswa_id > 0) {
    try {
        // Hitung Total Laporan
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM laporan_harian WHERE siswa_id = ?");
        $stmt->execute([$siswa_id]);
        $total_kegiatan = $stmt->fetchColumn();

        // Hitung Telah Divalidasi (oleh Ortu ATAU Guru)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM laporan_harian WHERE siswa_id = ? AND (orang_tua_validated_at IS NOT NULL OR guru_validated_at IS NOT NULL)");
        $stmt->execute([$siswa_id]);
        $telah_divalidasi = $stmt->fetchColumn();

        // Hitung yang masih pending
        $menunggu_validasi = $total_kegiatan - $telah_divalidasi;

        // Ambil 5 riwayat terbaru untuk tabel mini
        $stmt = $pdo->prepare("SELECT * FROM laporan_harian WHERE siswa_id = ? ORDER BY tanggal DESC LIMIT 5");
        $stmt->execute([$siswa_id]);
        $riwayat_terbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px;">
    <div class="card" style="text-align: center; border-top: 4px solid #0284c7; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.04);">
        <div style="font-size: 32px; font-weight: 800; color: #0284c7;"><?= $total_kegiatan ?></div>
        <div style="color: #64748b; font-size: 14px;">Total Laporan Anak</div>
    </div>
    <div class="card" style="text-align: center; border-top: 4px solid #10b981; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.04);">
        <div style="font-size: 32px; font-weight: 800; color: #10b981;"><?= $telah_divalidasi ?></div>
        <div style="color: #64748b; font-size: 14px;">Telah Divalidasi</div>
    </div>
    <div class="card" style="text-align: center; border-top: 4px solid #f59e0b; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.04);">
        <div style="font-size: 32px; font-weight: 800; color: #f59e0b;"><?= $menunggu_validasi ?></div>
        <div style="color: #64748b; font-size: 14px;">Menunggu Validasi</div>
    </div>
</div>

<div class="card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.04);">
    <h3 style="margin-top: 0; color: #1e293b; font-size: 18px; margin-bottom: 15px;">🕒 Riwayat Kegiatan Terbaru Anak</h3>
    
    <?php if (empty($riwayat_terbaru)): ?>
        <div style="text-align: center; padding: 30px; border: 1px dashed #cbd5e1; border-radius: 8px; color: #94a3b8;">
            Belum ada laporan kegiatan anak yang tercatat.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr>
                        <th style="background: #f8fafc; padding: 12px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0;">Tanggal</th>
                        <th style="background: #f8fafc; padding: 12px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0;">Skor 7 KAIH</th>
                        <th style="background: #f8fafc; padding: 12px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0;">Status Validasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($riwayat_terbaru as $row): 
                        $skor = $row['bangun'] + $row['ibadah'] + $row['olahraga'] + $row['sarapan'] + $row['membaca'] + $row['membantu'] + $row['menabung'];
                        $is_val = !empty($row['orang_tua_validated_at']) || !empty($row['guru_validated_at']);
                    ?>
                        <tr>
                            <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #1e293b;">
                                <?= date('d M Y', strtotime($row['tanggal'])) ?>
                            </td>
                            <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">
                                <span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-weight: 700; font-size: 12px;"><?= $skor ?>/7 Selesai</span>
                            </td>
                            <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">
                                <?= $is_val ? '<span style="color:#16a34a; font-weight:bold;">✔️ Divalidasi</span>' : '<span style="color:#f59e0b; font-weight:bold;">⏳ Menunggu</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top: 15px; text-align: right;">
            <a href="monitoring.php" style="color: #0284c7; text-decoration: none; font-weight: 600; font-size: 14px;">Lihat Selengkapnya ➡️</a>
        </div>
    <?php endif; ?>
</div>
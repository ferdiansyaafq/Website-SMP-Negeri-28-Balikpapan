<?php
// aplikasi/ortu/monitoring.php
session_start();
require_once '../../config/database.php';

if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_laporan = (int)$_GET['id'];
    $aksi = $_GET['aksi'];
    
    try {
        if ($aksi === 'setuju') {
            $sql = "UPDATE laporan_harian SET orang_tua_validated_at = NOW() WHERE id = :id";
        } elseif ($aksi === 'batal') {
            // BATAL SETUJU MERESET KEDUANYA AGAR KEMBALI PENDING
            $sql = "UPDATE laporan_harian SET guru_validated_at = NULL, orang_tua_validated_at = NULL WHERE id = :id";
        }
        
        if (isset($sql)) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id_laporan]);
        }
        
        $nisn_param = isset($_GET['nisn']) ? '&nisn=' . urlencode($_GET['nisn']) : '';
        header("Location: monitoring.php?bulan=" . urlencode($_GET['bulan'] ?? date('Y-m')) . $nisn_param);
        exit;
    } catch (PDOException $e) {}
}

require_once '../includes/header-kaih.php';

$siswa_id_session = $_SESSION['siswa_id'] ?? 0;
$input_nisn = trim($_GET['nisn'] ?? '');
$selected_bulan = $_GET['bulan'] ?? date('Y-m');
$siswa_info = null;

if (!empty($input_nisn)) {
    $stmtS = $pdo->prepare("SELECT * FROM siswa WHERE nisn = ? LIMIT 1");
    $stmtS->execute([$input_nisn]);
    $siswa_info = $stmtS->fetch(PDO::FETCH_ASSOC);
} elseif ($siswa_id_session > 0) {
    $stmtS = $pdo->prepare("SELECT * FROM siswa WHERE id = ? LIMIT 1");
    $stmtS->execute([$siswa_id_session]);
    $siswa_info = $stmtS->fetch(PDO::FETCH_ASSOC);
}

$laporan_anak = [];
if ($siswa_info) {
    $startYmd = $selected_bulan . '-01';
    $endYmd   = date('Y-m-t', strtotime($startYmd));
    $stmtL = $pdo->prepare("SELECT * FROM laporan_harian WHERE siswa_id = ? AND tanggal BETWEEN ? AND ? ORDER BY tanggal DESC");
    $stmtL->execute([$siswa_info['id'], $startYmd, $endYmd]);
    $laporan_anak = $stmtL->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="card" style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); margin: 20px;">
    <h3>👨‍👩‍👧 Monitoring & Validasi Orang Tua</h3>
    <p style="color: #64748b; font-size: 14px;">Pantau dan berikan persetujuan pada laporan aktivitas harian anak Anda.</p>

    <!-- FORM FILTER -->
    <form method="GET" action="" style="margin-bottom: 20px;">
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 13px;">NISN Anak:</label>
                <input type="text" name="nisn" value="<?= htmlspecialchars($input_nisn ?: ($siswa_info['nisn'] ?? '')) ?>" placeholder="Masukkan NISN Anak..." style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; width: 100%;">
            </div>
            <div>
                <label style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 13px;">Pilih Bulan:</label>
                <input type="month" name="bulan" value="<?= htmlspecialchars($selected_bulan) ?>" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1;">
            </div>
            <button type="submit" style="padding: 9px 18px; background: #0284c7; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Cari Data</button>
        </div>
    </form>

    <?php if ($siswa_info): ?>
        <div style="background: #f0f9ff; padding: 15px 20px; border-radius: 10px; border-left: 4px solid #0284c7; margin-bottom: 20px;">
            <h4 style="margin: 0; color: #0369a1; font-size: 16px;"><?= htmlspecialchars($siswa_info['nama_siswa']) ?></h4>
            <p style="margin: 4px 0 0 0; color: #0284c7; font-size: 13px;">NISN: <b><?= htmlspecialchars($siswa_info['nisn']) ?></b> | Kelas: <b><?= htmlspecialchars($siswa_info['kelas']) ?></b></p>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left;">
                        <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Tanggal</th>
                        <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Ringkasan KAIH</th>
                        <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Status Validasi</th>
                        <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($laporan_anak)): ?>
                        <tr><td colspan="4" style="padding: 20px; text-align: center; color: #94a3b8;">Belum ada laporan aktivitas anak pada bulan ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($laporan_anak as $lh): 
                            // STATUS GABUNGAN
                            $is_validated = !empty($lh['orang_tua_validated_at']) || !empty($lh['guru_validated_at']);
                            $score = (int)$lh['bangun'] + (int)$lh['ibadah'] + (int)$lh['olahraga'] + (int)$lh['sarapan'] + (int)$lh['membaca'] + (int)$lh['membantu'] + (int)$lh['menabung'];
                        ?>
                            <tr>
                                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;"><b><?= date('d-m-Y', strtotime($lh['tanggal'])) ?></b></td>
                                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                    <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 12px; font-weight: 700; font-size: 12px;"><?= $score ?>/7 Kegiatan</span>
                                </td>
                                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                    <?= $is_validated ? '<span style="color:#16a34a; font-weight:bold;">✔️ Sudah Divalidasi</span>' : '<span style="color:#f59e0b; font-weight:bold;">⏳ Pending</span>' ?>
                                </td>
                                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                    <?php if ($is_validated): ?>
                                        <a href="?aksi=batal&id=<?= $lh['id'] ?>&nisn=<?= urlencode($siswa_info['nisn']) ?>&bulan=<?= urlencode($selected_bulan) ?>" 
                                           onclick="return confirm('Batalkan persetujuan laporan ini?');" 
                                           style="padding: 6px 12px; background: #ef4444; color: white; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">
                                            Batal Setuju
                                        </a>
                                    <?php else: ?>
                                        <a href="?aksi=setuju&id=<?= $lh['id'] ?>&nisn=<?= urlencode($siswa_info['nisn']) ?>&bulan=<?= urlencode($selected_bulan) ?>" 
                                           style="padding: 6px 12px; background: #0284c7; color: white; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">
                                            Setujui Laporan
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
// aplikasi/guru/monitoring.php
session_start();
require_once '../../config/database.php';

// Logika Aksi Validasi (Gabungan)
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_laporan = (int)$_GET['id'];
    $aksi = $_GET['aksi'];
    
    try {
        if ($aksi === 'setuju') {
            $sql = "UPDATE laporan_harian SET guru_validated_at = NOW() WHERE id = :id";
        } elseif ($aksi === 'batal') {
            // BATAL SETUJU MERESET KEDUANYA AGAR KEMBALI PENDING
            $sql = "UPDATE laporan_harian SET guru_validated_at = NULL, orang_tua_validated_at = NULL WHERE id = :id";
        }
        
        if (isset($sql)) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id_laporan]);
        }
        
        $kelas_param = isset($_GET['kelas']) ? '&kelas=' . urlencode($_GET['kelas']) : '';
        header("Location: monitoring.php?tanggal=" . urlencode($_GET['tanggal'] ?? date('Y-m-d')) . $kelas_param);
        exit;
    } catch (PDOException $e) {
        echo "<script>alert('Gagal memproses validasi: " . $e->getMessage() . "');</script>";
    }
}

require_once '../includes/header-kaih.php';

$guru_id = $_SESSION['guru_id'] ?? $_SESSION['user_id'] ?? 0;
$kelas_guru_default = '';
if ($guru_id > 0) {
    $stmtG = $pdo->prepare("SELECT kelas FROM guru WHERE id = ? OR nip = (SELECT username FROM users WHERE id = ?)");
    $stmtG->execute([$guru_id, $guru_id]);
    $rowG = $stmtG->fetch(PDO::FETCH_ASSOC);
    if ($rowG) { $kelas_guru_default = $rowG['kelas'] ?? ''; }
}

$list_kelas = $pdo->query("SELECT nama_kelas FROM kaih_kelas ORDER BY nama_kelas ASC")->fetchAll(PDO::FETCH_COLUMN);
$selected_kelas = $_GET['kelas'] ?? $kelas_guru_default;
$selected_tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$laporan_siswa = [];
if (!empty($selected_kelas)) {
    $sql = "SELECT s.id AS siswa_id, s.nisn, s.nama_siswa, s.kelas,
                   lh.id AS laporan_id, lh.tanggal, lh.bangun, lh.ibadah, lh.olahraga,
                   lh.sarapan, lh.membaca, lh.membantu, lh.menabung,
                   lh.orang_tua_validated_at, lh.guru_validated_at
            FROM siswa s
            LEFT JOIN laporan_harian lh ON lh.siswa_id = s.id AND lh.tanggal = :tanggal
            WHERE s.kelas = :kelas ORDER BY s.nama_siswa ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':tanggal' => $selected_tanggal, ':kelas' => $selected_kelas]);
    $laporan_siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="card" style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); margin: 20px;">
    <h3>📊 Monitoring & Validasi Laporan Siswa</h3>
    <p style="color: #64748b; font-size: 14px;">Validasi laporan harian siswa kelas bimbingan Anda.</p>

    <form method="GET" action="" style="display: flex; gap: 15px; margin-bottom: 20px; align-items: flex-end; flex-wrap: wrap;">
        <div>
            <label style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 13px;">Pilih Kelas:</label>
            <select name="kelas" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; width: 180px; background: white;">
                <option value="">-- Pilih Kelas --</option>
                <?php foreach ($list_kelas as $k): ?>
                    <option value="<?= htmlspecialchars($k) ?>" <?= $selected_kelas === $k ? 'selected' : '' ?>>Kelas <?= htmlspecialchars($k) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 13px;">Tanggal Laporan:</label>
            <input type="date" name="tanggal" value="<?= htmlspecialchars($selected_tanggal) ?>" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1;">
        </div>
        
        <!-- Tombol Tampilkan, Cetak PDF, & Export Excel -->
        <div style="display: flex; gap: 10px; align-items: flex-end;">
            <button type="submit" style="padding: 9px 18px; background: #0284c7; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Tampilkan Data</button>
            
            <?php if (!empty($selected_kelas)): ?>
            <a href="cetak_laporan.php?kelas=<?= urlencode($selected_kelas) ?>&tanggal=<?= urlencode($selected_tanggal) ?>" target="_blank" style="padding: 9px 18px; background: #10b981; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center;">🖨️ Cetak PDF</a>
            
            <a href="export_to_excel.php?kelas=<?= urlencode($selected_kelas) ?>&tanggal=<?= urlencode($selected_tanggal) ?>" style="padding: 9px 18px; background: #059669; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center;">📊 Export Excel</a>
            <?php endif; ?>
        </div>
    </form>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="background: #f8fafc; text-align: left;">
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">No</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">NISN / Nama Siswa</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Status Lapor</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Status Validasi</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($laporan_siswa)): ?>
                    <tr><td colspan="5" style="padding: 20px; text-align: center; color: #94a3b8;">Silakan pilih kelas terlebih dahulu atau tidak ada siswa di kelas tersebut.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($laporan_siswa as $s): 
                        $is_lapor = !empty($s['laporan_id']);
                        // KONSEP GABUNGAN: True jika salah satu pihak sudah validasi
                        $is_validated = $is_lapor && (!empty($s['orang_tua_validated_at']) || !empty($s['guru_validated_at']));
                    ?>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;"><?= $no++ ?></td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <b><?= htmlspecialchars($s['nama_siswa']) ?></b><br>
                                <small style="color: #64748b;"><?= htmlspecialchars($s['nisn']) ?></small>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <?php if ($is_lapor): ?>
                                    <span style="background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;">Sudah Lapor</span>
                                <?php else: ?>
                                    <span style="background: #fee2e2; color: #b91c1c; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;">Belum</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <?= $is_validated ? '<span style="color:#16a34a; font-weight:bold;">✔️ Sudah Divalidasi</span>' : '<span style="color:#f59e0b; font-weight:bold;">⏳ Pending</span>' ?>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <?php if ($is_lapor): ?>
                                    <?php if ($is_validated): ?>
                                        <a href="?aksi=batal&id=<?= $s['laporan_id'] ?>&kelas=<?= urlencode($selected_kelas) ?>&tanggal=<?= urlencode($selected_tanggal) ?>" 
                                           onclick="return confirm('Batalkan validasi ini? Laporan akan kembali berstatus Pending.');" 
                                           style="padding: 6px 12px; background: #ef4444; color: white; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">
                                            Batal Setuju
                                        </a>
                                    <?php else: ?>
                                        <a href="?aksi=setuju&id=<?= $s['laporan_id'] ?>&kelas=<?= urlencode($selected_kelas) ?>&tanggal=<?= urlencode($selected_tanggal) ?>" 
                                           style="padding: 6px 12px; background: #0284c7; color: white; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">
                                            Setujui
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #cbd5e1; font-size: 12px;">Tidak Ada Laporan</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
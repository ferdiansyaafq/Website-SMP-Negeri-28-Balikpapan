<?php
// aplikasi/guru/absensi.php
session_start();
require_once '../../config/database.php';

// Ambil kelas wali guru dari session
$guru_id = $_SESSION['guru_id'] ?? $_SESSION['user_id'] ?? 0;
$kelas_guru = '';

if ($guru_id > 0) {
    $stmtG = $pdo->prepare("SELECT kelas FROM guru WHERE id = ? OR nip = (SELECT username FROM users WHERE id = ?)");
    $stmtG->execute([$guru_id, $guru_id]);
    $rowG = $stmtG->fetch(PDO::FETCH_ASSOC);
    if ($rowG) { $kelas_guru = $rowG['kelas'] ?? ''; }
}

$filterTanggal = $_GET['tanggal'] ?? date('Y-m-d');
$data_absensi = [];
$total = $hadir = $belum = 0;

if ($kelas_guru !== '') {
    $query = "SELECT s.nisn, s.nama_siswa, s.kelas, a.status, a.updated_at 
              FROM siswa s 
              LEFT JOIN absensi a ON s.id = a.siswa_id AND a.tanggal = :tanggal
              WHERE s.kelas = :kelas
              ORDER BY s.nama_siswa ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':tanggal' => $filterTanggal, ':kelas' => $kelas_guru]);
    $data_absensi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($data_absensi);
    foreach ($data_absensi as $row) {
        if (!empty($row['status']) && strtolower($row['status']) === 'hadir') {
            $hadir++;
        } else {
            $belum++;
        }
    }
}

require_once '../includes/header-kaih.php';
?>

<style>
    .modern-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
    .stat-box { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; }
    .stat-box .title { font-size: 12px; color: #64748b; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
    .stat-box .value { font-size: 24px; font-weight: 800; color: #1e293b; }
    .table-modern { width: 100%; border-collapse: collapse; font-size: 14px; }
    .table-modern th { background: #f1f5f9; padding: 12px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
    .table-modern td { padding: 12px; border-bottom: 1px solid #e2e8f0; color: #334155; }
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
    .btn-primary { background: #0284c7; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s; }
</style>

<div class="content-area" style="padding: 20px;">
    <h2 style="margin-top: 0; color: #1e293b;">📅 Absensi Kelas <?= htmlspecialchars($kelas_guru ?: '(Belum Ditugaskan)') ?></h2>
    <p style="color: #64748b; margin-bottom: 20px;">Pantau data kehadiran siswa di kelas bimbingan Anda.</p>

    <?php if ($kelas_guru === ''): ?>
        <div class="modern-card" style="text-align: center; color: #ef4444; font-weight: bold;">
            Anda belum ditugaskan sebagai Wali Kelas di kelas manapun.
        </div>
    <?php else: ?>
        <!-- FORM FILTER TANGGAL -->
        <div class="modern-card" style="display: flex; gap: 15px; align-items: flex-end;">
            <form method="GET" style="display: flex; gap: 10px; align-items: flex-end;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: bold; color: #475569; margin-bottom: 5px;">Tanggal Absensi</label>
                    <input type="date" name="tanggal" value="<?= htmlspecialchars($filterTanggal) ?>" style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                </div>
                <button type="submit" class="btn-primary">Cari Data</button>
            </form>
        </div>

        <!-- KARTU STATISTIK -->
        <div class="stats-grid">
            <div class="stat-box"><div class="title">Total Siswa Kelas <?= htmlspecialchars($kelas_guru) ?></div><div class="value"><?= $total ?></div></div>
            <div class="stat-box"><div class="title">Sudah Hadir</div><div class="value" style="color:#16a34a;"><?= $hadir ?></div></div>
            <div class="stat-box"><div class="title">Belum Absen</div><div class="value" style="color:#dc2626;"><?= $belum ?></div></div>
        </div>

        <!-- TABEL DATA -->
        <div class="modern-card" style="padding: 0; overflow-x: auto;">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Siswa</th>
                        <th>Status Kehadiran</th>
                        <th>Waktu Absen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_absensi)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 20px; color: #94a3b8;">Belum ada siswa terdaftar di kelas ini.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($data_absensi as $row): 
                            $is_hadir = !empty($row['status']) && strtolower($row['status']) === 'hadir';
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><b><?= htmlspecialchars($row['nama_siswa']) ?></b><br><span style="font-size: 11px; color: #94a3b8;"><?= htmlspecialchars($row['nisn']) ?></span></td>
                            <td>
                                <?= $is_hadir ? '<span class="badge" style="background:#dcfce7; color:#15803d;">✔️ Hadir</span>' : '<span class="badge" style="background:#fee2e2; color:#b91c1c;">❌ Belum Absen</span>' ?>
                            </td>
                            <td style="font-size: 13px; color: #64748b;">
                                <?= $is_hadir ? date('H:i', strtotime($row['updated_at'])) . ' WITA' : '-' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
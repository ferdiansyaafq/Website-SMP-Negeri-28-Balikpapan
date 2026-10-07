<?php
// aplikasi/admin/index.php
session_start();
require_once '../../config/database.php';

// Cek hak akses admin
if (!isset($_SESSION['user_id']) && (string)($_SESSION['portal_role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$tanggal_hari_ini = date('Y-m-d');

// =======================================================
// 1. STATISTIK GLOBAL HARI INI
// =======================================================
$total_siswa = (int)$pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();

$stmtMasuk = $pdo->prepare("SELECT COUNT(*) FROM laporan_harian WHERE tanggal = ?");
$stmtMasuk->execute([$tanggal_hari_ini]);
$laporan_hari_ini = (int)$stmtMasuk->fetchColumn();

// Mencegah minus jika ada data anomali
$belum_lapor = max(0, $total_siswa - $laporan_hari_ini);

$stmtValid = $pdo->prepare("SELECT COUNT(*) FROM laporan_harian WHERE tanggal = ? AND (orang_tua_validated_at IS NOT NULL OR guru_validated_at IS NOT NULL)");
$stmtValid->execute([$tanggal_hari_ini]);
$tervalidasi_hari_ini = (int)$stmtValid->fetchColumn();

// =======================================================
// 2. DATA REKAP KEHADIRAN KAIH PER KELAS (HARI INI)
// =======================================================
$stmtRekap = $pdo->prepare("
    SELECT 
        k.nama_kelas,
        (SELECT COUNT(*) FROM siswa s WHERE s.kelas = k.nama_kelas) AS total_siswa,
        (SELECT COUNT(*) FROM laporan_harian lh JOIN siswa s ON lh.siswa_id = s.id WHERE s.kelas = k.nama_kelas AND lh.tanggal = ?) AS sudah_lapor,
        (SELECT COUNT(*) FROM laporan_harian lh JOIN siswa s ON lh.siswa_id = s.id WHERE s.kelas = k.nama_kelas AND lh.tanggal = ? AND (lh.orang_tua_validated_at IS NOT NULL OR lh.guru_validated_at IS NOT NULL)) AS tervalidasi
    FROM kaih_kelas k
    ORDER BY k.nama_kelas ASC
");
$stmtRekap->execute([$tanggal_hari_ini, $tanggal_hari_ini]);
$rekap_kelas = $stmtRekap->fetchAll(PDO::FETCH_ASSOC);

// =======================================================
// 3. AKTIVITAS TERBARU (LIVE FEED)
// =======================================================
$stmtAktivitas = $pdo->prepare("
    SELECT s.nama_siswa, s.kelas, lh.created_at, 
           (lh.bangun + lh.ibadah + lh.olahraga + lh.sarapan + lh.membaca + lh.membantu + lh.menabung) as skor,
           lh.orang_tua_validated_at, lh.guru_validated_at
    FROM laporan_harian lh
    JOIN siswa s ON lh.siswa_id = s.id
    WHERE lh.tanggal = ?
    ORDER BY lh.created_at DESC LIMIT 6
");
$stmtAktivitas->execute([$tanggal_hari_ini]);
$aktivitas_terbaru = $stmtAktivitas->fetchAll(PDO::FETCH_ASSOC);

// =======================================================
// 4. DATA GRAFIK 7 KAIH GLOBAL HARI INI
// =======================================================
$stmtGrafik = $pdo->prepare("
    SELECT 
        COALESCE(SUM(bangun), 0) as tot_bangun,
        COALESCE(SUM(ibadah), 0) as tot_ibadah,
        COALESCE(SUM(olahraga), 0) as tot_olahraga,
        COALESCE(SUM(sarapan), 0) as tot_sarapan,
        COALESCE(SUM(membaca), 0) as tot_membaca,
        COALESCE(SUM(membantu), 0) as tot_membantu,
        COALESCE(SUM(menabung), 0) as tot_menabung
    FROM laporan_harian 
    WHERE tanggal = ?
");
$stmtGrafik->execute([$tanggal_hari_ini]);
$dataGrafik = $stmtGrafik->fetch(PDO::FETCH_ASSOC);

// Memuat Header UI
require_once '../includes/header-kaih.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body { background-color: #f8fafc; }
    .dashboard-container { padding: 24px; max-width: 1200px; margin: 0 auto; }
    
    /* Grid Layout untuk Card Statistik */
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; width: 100%; }
    .stat-card { background: #ffffff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; border-left: 5px solid; transition: transform 0.2s ease; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    
    /* Warna Custom Tiap Card */
    .card-siswa { border-left-color: #0ea5e9; }
    .card-sudah { border-left-color: #10b981; }
    .card-belum { border-left-color: #ef4444; }
    .card-valid { border-left-color: #8b5cf6; }
    
    .stat-icon { width: 54px; height: 54px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-right: 16px; flex-shrink: 0; }
    .card-siswa .stat-icon { background: #e0f2fe; color: #0ea5e9; }
    .card-sudah .stat-icon { background: #dcfce7; color: #10b981; }
    .card-belum .stat-icon { background: #fee2e2; color: #ef4444; }
    .card-valid .stat-icon { background: #ede9fe; color: #8b5cf6; }
    
    .stat-details h3 { margin: 0; font-size: 32px; font-weight: 800; color: #1e293b; line-height: 1; }
    .stat-details p { margin: 6px 0 0 0; font-size: 13px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    
    /* Grid Bawah: Tabel & Live Feed */
    .grid-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .activity-section { background: #ffffff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .activity-header { font-size: 16px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; text-transform: uppercase; }
    
    /* Tabel Rekap Modern */
    .table-modern { width: 100%; border-collapse: collapse; font-size: 13px; }
    .table-modern th { background: #f8fafc; padding: 12px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; font-weight: 800; }
    .table-modern td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; font-weight: 600;}
    .badge-rekap { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
    
    /* List Live Feed */
    .list-group { list-style: none; padding: 0; margin: 0; }
    .list-group-item { padding: 12px 0; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .list-group-item:last-child { border-bottom: none; padding-bottom: 0;}
    
    @media (max-width: 1024px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .grid-layout { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .stats-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="dashboard-container">
    
    <!-- Bagian Kartu Statistik Utama -->
    <div class="stats-grid">
        <div class="stat-card card-siswa">
            <div class="stat-icon">👥</div>
            <div class="stat-details">
                <h3><?= $total_siswa ?></h3>
                <p>Total Siswa</p>
            </div>
        </div>
        <div class="stat-card card-sudah">
            <div class="stat-icon">✅</div>
            <div class="stat-details">
                <h3><?= $laporan_hari_ini ?></h3>
                <p>Sudah Lapor Hari Ini</p>
            </div>
        </div>
        <div class="stat-card card-belum">
            <div class="stat-icon">⏳</div>
            <div class="stat-details">
                <h3><?= $belum_lapor ?></h3>
                <p>Belum Lapor</p>
            </div>
        </div>
        <div class="stat-card card-valid">
            <div class="stat-icon">🛡️</div>
            <div class="stat-details">
                <h3><?= $tervalidasi_hari_ini ?></h3>
                <p>Tervalidasi Hari Ini</p>
            </div>
        </div>
    </div>

    <!-- GRAFIK KAIH GLOBAL HARI INI -->
    <div class="activity-section" style="margin-bottom: 25px;">
        <div class="activity-header">📊 Grafik Pelaksanaan 7 KAIH (<?= date('d M Y') ?>)</div>
        <div style="position: relative; height: 280px; width: 100%;">
            <canvas id="globalKaihChart"></canvas>
        </div>
    </div>

    <!-- Bagian Detail Bawah -->
    <div class="grid-layout">
        
        <!-- Tabel Rekap Kelas Hari Ini -->
        <div class="activity-section" style="overflow-x: auto;">
            <div class="activity-header">🏫 Rekap Kelas Hari Ini</div>
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th style="text-align:center;">Total Siswa</th>
                        <th style="text-align:center;">Sudah Lapor</th>
                        <th style="text-align:center;">Belum Lapor</th>
                        <th style="text-align:center;">Tervalidasi</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($rekap_kelas)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px; color:#94a3b8;">Data kelas belum tersedia.</td></tr>
                    <?php else: foreach($rekap_kelas as $rk): 
                        $blm = max(0, $rk['total_siswa'] - $rk['sudah_lapor']);
                    ?>
                    <tr>
                        <td style="color:#0284c7; font-weight:800; font-size:15px;"><?= htmlspecialchars($rk['nama_kelas']) ?></td>
                        <td style="text-align:center;"><?= $rk['total_siswa'] ?></td>
                        <td style="text-align:center;">
                            <span class="badge-rekap" style="background:#dcfce7; color:#15803d;"><?= $rk['sudah_lapor'] ?></span>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge-rekap" style="background:#fee2e2; color:#b91c1c;"><?= $blm ?></span>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge-rekap" style="background:#ede9fe; color:#6d28d9;"><?= $rk['tervalidasi'] ?></span>
                        </td>
                        <td style="text-align:center;">
                            <!-- Tombol Detail yang mengarah ke laporan.php dengan filter otomatis -->
                            <a href="laporan.php?tab=kelas&kelas=<?= urlencode($rk['nama_kelas']) ?>&tanggal=<?= $tanggal_hari_ini ?>" 
                               style="padding: 6px 12px; background: #0ea5e9; color: white; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-block; transition: 0.2s;"
                               onmouseover="this.style.background='#0284c7'" 
                               onmouseout="this.style.background='#0ea5e9'">
                                Detail 🔎
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Live Feed Aktivitas -->
        <div class="activity-section">
            <div class="activity-header">⚡ Aktivitas Terbaru</div>
            
            <?php if (empty($aktivitas_terbaru)): ?>
                <div style="text-align:center; padding: 40px 0; color:#94a3b8; font-style:italic;">
                    Belum ada siswa yang mengisi laporan hari ini.
                </div>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($aktivitas_terbaru as $akt): 
                        $time = date('H:i', strtotime($akt['created_at']));
                        $is_val = !empty($akt['orang_tua_validated_at']) || !empty($akt['guru_validated_at']);
                    ?>
                    <li class="list-group-item">
                        <div>
                            <div style="font-weight: 800; color: #1e293b;"><?= htmlspecialchars($akt['nama_siswa']) ?></div>
                            <div style="font-size: 12px; color: #64748b; margin-top:3px;">
                                <span style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-weight:bold;">Kelas <?= htmlspecialchars($akt['kelas']) ?></span> • <?= $akt['skor'] ?>/7 Kegiatan
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 11px; color: #94a3b8; font-weight:bold; margin-bottom: 4px;"><?= $time ?> WITA</div>
                            <?= $is_val ? '<span class="badge-rekap" style="background:#dcfce7; color:#15803d; font-size:10px;">✔️ Val</span>' : '<span class="badge-rekap" style="background:#fef3c7; color:#d97706; font-size:10px;">⏳ Wait</span>' ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SCRIPT RENDER GRAFIK CHART.JS -->
<script>
    const ctxGlobal = document.getElementById('globalKaihChart').getContext('2d');
    new Chart(ctxGlobal, {
        type: 'bar',
        data: {
            labels: ['Bangun Pagi', 'Ibadah', 'Olahraga', 'Sarapan', 'Membaca', 'Membantu', 'Menabung'],
            datasets: [{
                label: 'Jumlah Siswa Mengerjakan',
                data: [
                    <?= (int)($dataGrafik['tot_bangun'] ?? 0) ?>, 
                    <?= (int)($dataGrafik['tot_ibadah'] ?? 0) ?>, 
                    <?= (int)($dataGrafik['tot_olahraga'] ?? 0) ?>, 
                    <?= (int)($dataGrafik['tot_sarapan'] ?? 0) ?>, 
                    <?= (int)($dataGrafik['tot_membaca'] ?? 0) ?>, 
                    <?= (int)($dataGrafik['tot_membantu'] ?? 0) ?>, 
                    <?= (int)($dataGrafik['tot_menabung'] ?? 0) ?>
                ],
                backgroundColor: [
                    '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#14b8a6'
                ],
                borderWidth: 0,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>
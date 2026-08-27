<?php
// aplikasi/admin/laporan-guru.php
session_start();

// Cek hak akses admin
if (!isset($_SESSION['user_id']) && (string)($_SESSION['portal_role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

require_once '../../config/database.php';

/* ============================================================
   LOGIKA AKSI ADMIN (BATAL / SETUJU VALIDASI PAKSA)
   ============================================================ */
if (isset($_GET['aksi_admin']) && isset($_GET['laporan_id'])) {
    $aksi_admin = $_GET['aksi_admin'];
    $laporan_id = (int)$_GET['laporan_id'];
    $back_url = "?tab=validasi&guru_id=" . urlencode($_GET['guru_id'] ?? '') . "&kelas=" . urlencode($_GET['kelas'] ?? '') . "&tanggal=" . urlencode($_GET['tanggal'] ?? '');
    
    try {
        if ($aksi_admin === 'batal') {
            $pdo->prepare("UPDATE laporan_harian SET guru_validated_at = NULL, orang_tua_validated_at = NULL WHERE id = ?")->execute([$laporan_id]);
        } elseif ($aksi_admin === 'setuju') {
            $pdo->prepare("UPDATE laporan_harian SET guru_validated_at = NOW(), orang_tua_validated_at = NOW() WHERE id = ?")->execute([$laporan_id]);
        }
        header("Location: laporan-guru.php" . $back_url);
        exit;
    } catch (PDOException $e) {}
}

require_once '../includes/header-kaih.php';

// Pengaturan Default Filter
$tab = in_array($_GET['tab'] ?? '', ['validasi', 'rekap'], true) ? (string)$_GET['tab'] : 'validasi';
$filterGuruId  = (int)($_GET['guru_id'] ?? 0);
$filterKelas   = trim((string)($_GET['kelas'] ?? ''));
$filterTanggal = trim((string)($_GET['tanggal'] ?? date('Y-m-d')));

$autoSem     = ((int)date('n')) >= 7 ? 2 : 1;
$year        = max(2020, min(2035, (int)($_GET['year'] ?? (int)date('Y'))));
$rawSemester = (int)($_GET['semester'] ?? $autoSem);
$semester    = in_array($rawSemester, [1, 2], true) ? $rawSemester : $autoSem;

// Load Dropdown Data
$allGuru = $pdo->query("SELECT id, nama_guru, kelas AS wali_kelas FROM guru ORDER BY nama_guru ASC")->fetchAll(PDO::FETCH_ASSOC);
$allKelas = $pdo->query("SELECT id, nama_kelas FROM kaih_kelas ORDER BY nama_kelas ASC")->fetchAll(PDO::FETCH_ASSOC);

$guruNama = '';
$guruWaliKelas = '';
if ($filterGuruId > 0) {
    $stmtG = $pdo->prepare("SELECT nama_guru, kelas FROM guru WHERE id = ? LIMIT 1");
    $stmtG->execute([$filterGuruId]);
    $rowG = $stmtG->fetch(PDO::FETCH_ASSOC);
    if ($rowG) {
        $guruNama = (string)$rowG['nama_guru'];
        $guruWaliKelas = (string)$rowG['kelas'];
        if ($filterKelas === '') $filterKelas = $guruWaliKelas;
    }
}

$bulanNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$labelMap = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
$semLabel = ($semester === 1 ? 'Semester 1 (Jan-Jun)' : 'Semester 2 (Jul-Des)') . ' ' . $year;

/* ============================================================
   DATA TAB 1: VALIDASI GURU (HARIAN)
   ============================================================ */
$validasiData = [];
$valTotal = $valTerkirim = $valBelum = $valValid = 0;
$valSudahKirim = [];
$valBelumKirim = [];

if ($tab === 'validasi' && $filterKelas !== '') {
    $stmt = $pdo->prepare(
        'SELECT s.id AS siswa_id, s.nisn, s.nama_siswa, s.kelas,
                lh.id AS laporan_id, lh.tanggal,
                lh.bangun, lh.ibadah, lh.olahraga, lh.sarapan, lh.membaca, lh.membantu, lh.menabung,
                lh.orang_tua_validated_at, lh.guru_validated_at
         FROM siswa s
         LEFT JOIN laporan_harian lh ON lh.siswa_id = s.id AND lh.tanggal = ?
         WHERE s.kelas = ? ORDER BY s.nama_siswa ASC'
    );
    $stmt->execute([$filterTanggal, $filterKelas]);
    $validasiData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($validasiData as $v) {
        $valTotal++;
        if (!empty($v['laporan_id'])) {
            $valTerkirim++;
            $valSudahKirim[] = $v;
            if (!empty($v['orang_tua_validated_at']) || !empty($v['guru_validated_at'])) $valValid++;
        } else {
            $valBelum++;
            $valBelumKirim[] = $v;
        }
    }
}

/* ============================================================
   DATA TAB 2: REKAP SEMESTER & CETAK
   ============================================================ */
$students = [];
$summary = [];
$semAgg = [];
$semMonths = $semester === 1 ? [1,2,3,4,5,6] : [7,8,9,10,11,12];
$semStartYmd = sprintf('%04d-%02d-01', $year, $semMonths[0]);
$semEndYmd = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $semMonths[5])));

$totalStudents = $studentsReported = $totalSubmitted = 0;
$totalValidOrtu = $totalValidGuru = $classScoreSum = $classScoreRows = 0;

if ($tab === 'rekap' && $filterKelas !== '') {
    foreach ($semMonths as $m) {
        $semAgg[$m] = ['submitted'=>0,'validated'=>0,'score_sum'=>0,'rows'=>0];
    }
    
    $stmtS = $pdo->prepare("SELECT id, nisn, nama_siswa, kelas FROM siswa WHERE kelas = ? ORDER BY nama_siswa ASC");
    $stmtS->execute([$filterKelas]);
    foreach ($stmtS->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $students[$r['id']] = $r;
        $summary[$r['id']] = ['terkirim'=>0,'valid_any'=>0,'valid_ortu'=>0,'valid_guru'=>0,'score_sum'=>0];
    }

    if (!empty($students)) {
        $ids = array_keys($students);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, [$semStartYmd, $semEndYmd]);
        
        $stmtL = $pdo->prepare("SELECT * FROM laporan_harian WHERE siswa_id IN ($placeholders) AND tanggal BETWEEN ? AND ? ORDER BY tanggal ASC");
        $stmtL->execute($params);
        
        foreach ($stmtL->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $sid = (int)$r['siswa_id'];
            if (!isset($summary[$sid])) continue;
            
            $score = (int)$r['bangun'] + (int)$r['ibadah'] + (int)$r['olahraga'] + (int)$r['sarapan'] + (int)$r['membaca'] + (int)$r['membantu'] + (int)$r['menabung'];
            $m = (int)date('n', strtotime($r['tanggal']));
            
            $summary[$sid]['terkirim']++;
            $summary[$sid]['score_sum'] += $score;
            if (!empty($r['orang_tua_validated_at'])) $summary[$sid]['valid_ortu']++;
            if (!empty($r['guru_validated_at'])) $summary[$sid]['valid_guru']++;
            if (!empty($r['orang_tua_validated_at']) || !empty($r['guru_validated_at'])) $summary[$sid]['valid_any']++;
            
            if (isset($semAgg[$m])) {
                $semAgg[$m]['submitted']++;
                $semAgg[$m]['score_sum'] += $score;
                $semAgg[$m]['rows']++;
                if (!empty($r['guru_validated_at']) || !empty($r['orang_tua_validated_at'])) $semAgg[$m]['validated']++;
            }
        }
    }
    
    $totalStudents = count($students);
    foreach ($summary as $agg) {
        $t = (int)$agg['terkirim'];
        if ($t > 0) $studentsReported++;
        $totalSubmitted += $t;
        $totalValidOrtu += (int)$agg['valid_ortu'];
        $totalValidGuru += (int)$agg['valid_guru'];
        $classScoreSum += (int)$agg['score_sum'];
        $classScoreRows += $t;
    }
}
$classAvg = $classScoreRows > 0 ? ($classScoreSum / $classScoreRows) : 0.0;
$guruKelasMapJson = json_encode(array_column($allGuru, 'wali_kelas', 'id'), JSON_UNESCAPED_UNICODE);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .modern-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
    .nav-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
    .nav-tab { padding: 10px 20px; border-radius: 8px; text-decoration: none; color: #64748b; font-weight: 600; transition: 0.2s; }
    .nav-tab.active { background: #2563eb; color: #fff; }
    .nav-tab:hover:not(.active) { background: #f1f5f9; color: #1e293b; }
    .filter-grid { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
    .filter-item { display: flex; flex-direction: column; gap: 5px; min-width: 180px; }
    .filter-item label { font-size: 12px; font-weight: bold; color: #475569; text-transform: uppercase; }
    .filter-item select, .filter-item input { padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; }
    .btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
    .stat-box { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; }
    .stat-box .title { font-size: 12px; color: #64748b; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
    .stat-box .value { font-size: 24px; font-weight: 800; color: #1e293b; }
    .table-modern { width: 100%; border-collapse: collapse; font-size: 14px; }
    .table-modern th { background: #f1f5f9; padding: 12px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
    .table-modern td { padding: 12px; border-bottom: 1px solid #e2e8f0; color: #334155; }
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
    .badge-green { background: #d1fae5; color: #059669; }
    .badge-red { background: #fee2e2; color: #dc2626; }
    .badge-blue { background: #dbeafe; color: #2563eb; }
    .metric-bar { width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-top: 5px; }
    .metric-fill { height: 100%; background: #2563eb; border-radius: 4px; }

    /* Print Only Styles */
    .print-header { display: none; }
    .print-signature { display: none; }
    @media print {
        @page { size: A4; margin: 1cm; } /* Margin dikurangi agar muat lebih banyak */
        body { background: white !important; }
        #sidebar, .top-header, .nav-tabs, .filter-grid, .btn-primary, .no-print { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .modern-card { border: none !important; box-shadow: none !important; padding: 0 !important; margin-bottom: 15px !important; }
        .print-header { display: block !important; text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px; }
        .print-header h2 { margin: 0; font-size: 20px; font-family: 'Times New Roman', serif; }
        .print-header p { margin: 5px 0 0; font-size: 13px; font-family: 'Times New Roman', serif; }
        .table-modern th { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; }
        
        /* Memaksa elemen tanda tangan agar tidak diputus / melompat ke halaman baru */
        .print-signature { 
            display: block !important; 
            page-break-inside: avoid; 
            break-inside: avoid;
        }
    }
</style>

<div class="content-area" style="padding: 20px;">
    
    <div style="margin-bottom: 20px;" class="no-print">
        <h2 style="margin:0; color:#1e293b;">Laporan & Evaluasi Guru</h2>
        <p style="margin:5px 0 0; color:#64748b;">Validasi laporan harian dan cetak rekap semester per kelas.</p>
    </div>

    <!-- TABS -->
    <div class="nav-tabs no-print">
        <a href="?tab=validasi<?= $filterGuruId ? '&guru_id='.$filterGuruId : '' ?><?= $filterKelas ? '&kelas='.urlencode($filterKelas) : '' ?>&tanggal=<?= urlencode($filterTanggal) ?>"
            class="nav-tab <?= $tab === 'validasi' ? 'active' : '' ?>">Validasi Harian</a>
        <a href="?tab=rekap<?= $filterGuruId ? '&guru_id='.$filterGuruId : '' ?><?= $filterKelas ? '&kelas='.urlencode($filterKelas) : '' ?>&year=<?= $year ?>&semester=<?= $semester ?>"
            class="nav-tab <?= $tab === 'rekap' ? 'active' : '' ?>">Rekap & Cetak Semester</a>
    </div>

    <!-- ==============================================
         TAB 1: VALIDASI GURU (HARIAN)
         ============================================== -->
    <?php if ($tab === 'validasi'): ?>
    
    <div class="modern-card no-print">
        <form method="GET" class="filter-grid">
            <input type="hidden" name="tab" value="validasi">
            <div class="filter-item">
                <label>Pilih Guru / Wali Kelas</label>
                <select name="guru_id" id="selGuruV">
                    <option value="">Semua Guru</option>
                    <?php foreach ($allGuru as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= $g['id'] == $filterGuruId ? 'selected' : '' ?>><?= $g['nama_guru'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Pilih Kelas</label>
                <select name="kelas" id="selKelasV">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($allKelas as $k): ?>
                    <option value="<?= $k['nama_kelas'] ?>" <?= $k['nama_kelas'] == $filterKelas ? 'selected' : '' ?>><?= $k['nama_kelas'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Tanggal Laporan</label>
                <input type="date" name="tanggal" value="<?= $filterTanggal ?>">
            </div>
            <button type="submit" class="btn-primary">Tampilkan Data</button>
        </form>
    </div>

    <?php if ($filterKelas !== ''): ?>
    <div class="stats-grid">
        <div class="stat-box"><div class="title">Total Siswa</div><div class="value"><?= $valTotal ?></div></div>
        <div class="stat-box"><div class="title">Sudah Lapor</div><div class="value" style="color:#059669;"><?= $valTerkirim ?></div></div>
        <div class="stat-box"><div class="title">Belum Lapor</div><div class="value" style="color:#dc2626;"><?= $valBelum ?></div></div>
        <div class="stat-box"><div class="title">Tervalidasi</div><div class="value" style="color:#2563eb;"><?= $valValid ?></div></div>
    </div>

    <!-- TABEL SUDAH LAPOR -->
    <div class="modern-card" style="padding: 0; overflow-x: auto;">
        <div style="padding: 15px; background: #f8fafc; border-bottom: 2px solid #e2e8f0; font-weight: bold; color: #15803d;">✔️ Sudah Melapor - <?= count($valSudahKirim) ?> Siswa</div>
        <table class="table-modern">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Nama Siswa</th>
                    <th>Kegiatan KAIH</th>
                    <th>Status Validasi</th>
                    <th>Aksi Pengawas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($valSudahKirim)): ?>
                <tr><td colspan="5" style="text-align:center; padding:20px;">Belum ada yang melapor.</td></tr>
                <?php else: foreach ($valSudahKirim as $i => $v): 
                    $score = $v['bangun'] + $v['ibadah'] + $v['olahraga'] + $v['sarapan'] + $v['membaca'] + $v['membantu'] + $v['menabung'];
                    $ortu = !empty($v['orang_tua_validated_at']);
                    $guru = !empty($v['guru_validated_at']);
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><b><?= htmlspecialchars($v['nama_siswa']) ?></b><br><span style="font-size:11px; color:#94a3b8;"><?= $v['nisn'] ?></span></td>
                    <td><span class="badge badge-blue"><?= $score ?>/7 Selesai</span></td>
                    <td>
                        <?php 
                        if ($ortu && $guru) echo '<span class="badge badge-blue">✔️ Ortu & Guru</span>';
                        elseif ($ortu) echo '<span class="badge badge-blue">✔️ Orang Tua</span>';
                        elseif ($guru) echo '<span class="badge badge-blue">✔️ Guru</span>';
                        else echo '<span class="badge" style="background:#f1f5f9; color:#94a3b8;">⏳ Menunggu</span>';
                        ?>
                    </td>
                    <td>
                        <?php if ($ortu || $guru): ?>
                            <a href="?aksi_admin=batal&laporan_id=<?= $v['laporan_id'] ?>&guru_id=<?= $filterGuruId ?>&kelas=<?= urlencode($filterKelas) ?>&tanggal=<?= $filterTanggal ?>" onclick="return confirm('Batal validasi?');" class="badge badge-red" style="text-decoration:none;">Batal Setuju</a>
                        <?php else: ?>
                            <a href="?aksi_admin=setuju&laporan_id=<?= $v['laporan_id'] ?>&guru_id=<?= $filterGuruId ?>&kelas=<?= urlencode($filterKelas) ?>&tanggal=<?= $filterTanggal ?>" onclick="return confirm('Validasi paksa laporan ini?');" class="badge badge-green" style="text-decoration:none;">Validasi Master</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ==============================================
         TAB 2: REKAP & CETAK (SEMESTERAN)
         ============================================== -->
    <?php elseif ($tab === 'rekap'): ?>

    <div class="modern-card no-print">
        <form method="GET" class="filter-grid">
            <input type="hidden" name="tab" value="rekap">
            <div class="filter-item">
                <label>Pilih Guru / Wali Kelas</label>
                <select name="guru_id" id="selGuruR">
                    <option value="">Semua Guru</option>
                    <?php foreach ($allGuru as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= $g['id'] == $filterGuruId ? 'selected' : '' ?>><?= $g['nama_guru'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Pilih Kelas</label>
                <select name="kelas" id="selKelasR">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($allKelas as $k): ?>
                    <option value="<?= $k['nama_kelas'] ?>" <?= $k['nama_kelas'] == $filterKelas ? 'selected' : '' ?>><?= $k['nama_kelas'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item" style="min-width: 100px;">
                <label>Tahun</label>
                <select name="year">
                    <?php for ($yy = (int)date('Y') - 1; $yy <= (int)date('Y') + 1; $yy++): ?>
                    <option value="<?= $yy ?>" <?= $yy === $year ? 'selected' : '' ?>><?= $yy ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Semester</label>
                <select name="semester">
                    <option value="1" <?= $semester === 1 ? 'selected' : '' ?>>Semester 1 (Jan - Jun)</option>
                    <option value="2" <?= $semester === 2 ? 'selected' : '' ?>>Semester 2 (Jul - Des)</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-primary">Tampilkan</button>
                <?php if ($filterKelas !== ''): ?>
                <button type="button" onclick="window.print()" class="btn-primary" style="background:#10b981;">🖨️ Cetak PDF</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($filterKelas !== ''): ?>
    
    <!-- KOP SURAT UNTUK PRINT -->
    <div class="print-header">
        <h2>SMP NEGERI 28 BALIKPAPAN</h2>
        <p>Jl. Mulawarman, Teritip, Kec. Balikpapan Timur, Kota Balikpapan, Kalimantan Timur</p>
        <p style="margin-top: 15px; font-weight: bold; text-decoration: underline;">REKAPITULASI KAIH SEMESTER KELAS <?= htmlspecialchars($filterKelas) ?></p>
        <p>Wali Kelas: <?= $guruNama ?: '(Belum Diset)' ?> | <?= $semLabel ?></p>
    </div>

    <!-- STATISTIK KARTU -->
    <div class="stats-grid">
        <div class="stat-box"><div class="title">Total Siswa</div><div class="value"><?= $totalStudents ?></div></div>
        <div class="stat-box"><div class="title">Siswa Aktif</div><div class="value" style="color:#059669;"><?= $studentsReported ?></div></div>
        <div class="stat-box"><div class="title">Total Laporan</div><div class="value" style="color:#8b5cf6;"><?= $totalSubmitted ?></div></div>
        <div class="stat-box"><div class="title">Val. Ortu / Guru</div><div class="value" style="color:#2563eb;"><?= $totalValidOrtu ?> / <?= $totalValidGuru ?></div></div>
        <div class="stat-box"><div class="title">Rata-Rata Kelas</div><div class="value" style="color:#d97706;"><?= number_format($classAvg, 1, ',', '.') ?>/7</div></div>
    </div>

    <!-- GRAFIK SEMESTER (CHART.JS) -->
    <div class="modern-card">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 16px;">Grafik Rata-rata Kinerja Semester (<?= $semLabel ?>)</h3>
        <div style="position: relative; height: 250px; width: 100%;">
            <canvas id="semesterChart"></canvas>
        </div>
    </div>

    <?php 
    $chartLabels = [];
    $chartAverages = [];
    foreach ($semMonths as $m) {
        $chartLabels[] = $labelMap[$m];
        $rows = (int)($semAgg[$m]['rows'] ?? 0);
        $avg = $rows > 0 ? ((float)$semAgg[$m]['score_sum'] / (float)$rows) : 0.0;
        $chartAverages[] = number_format($avg, 2, '.', '');
    }
    ?>
    <script>
        const ctxSem = document.getElementById('semesterChart').getContext('2d');
        new Chart(ctxSem, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Rata-Rata Skor Kelas (Max 7)',
                    data: <?= json_encode($chartAverages) ?>,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#2563eb'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, max: 7 } }
            }
        });
    </script>

    <!-- TABEL REKAP SISWA -->
    <div class="modern-card" style="padding: 0; overflow-x: auto;">
        <table class="table-modern">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Nama Siswa</th>
                    <th>NISN</th>
                    <th style="text-align:center;">Total Dikirim</th>
                    <th style="text-align:center;">Val. Ortu / Guru</th>
                    <th style="text-align:center;">Rata-Rata Skor</th>
                    <th>Persentase Kinerja</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                <tr><td colspan="7" style="text-align:center; padding:20px;">Belum ada siswa di kelas ini.</td></tr>
                <?php else: $no=1; foreach ($students as $sid => $s): 
                    $agg = $summary[$sid];
                    $avg = $agg['terkirim'] > 0 ? ((float)$agg['score_sum'] / (float)$agg['terkirim']) : 0.0;
                    $pct = max(0, min(100, (int)round(($avg / 7.0) * 100)));
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><b><?= htmlspecialchars($s['nama_siswa']) ?></b></td>
                    <td style="color:#64748b;"><?= htmlspecialchars($s['nisn']) ?></td>
                    <td style="text-align:center; font-weight:bold; color:#059669;"><?= $agg['terkirim'] ?></td>
                    <td style="text-align:center;"><span class="badge badge-blue"><?= $agg['valid_ortu'] ?> / <?= $agg['valid_guru'] ?></span></td>
                    <td style="text-align:center; font-weight:bold;"><?= number_format($avg, 2, ',', '.') ?>/7</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div class="metric-bar"><div class="metric-fill" style="width:<?= $pct ?>%; background: <?= $pct < 50 ? '#ef4444' : ($pct < 80 ? '#f59e0b' : '#10b981') ?>;"></div></div>
                            <span style="font-size:12px; font-weight:bold; width:35px;"><?= $pct ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- KOLOM TANDA TANGAN (PRINT ONLY) -->
    <div class="print-signature" style="margin-top: 30px; text-align: right;">
        <div style="display: inline-block; text-align: center; width: 250px;">
            <p style="font-family: 'Times New Roman', serif; margin: 0;">Balikpapan, <?= date('d F Y') ?></p>
            <p style="font-family: 'Times New Roman', serif; margin: 0;">Wali Kelas <?= htmlspecialchars($filterKelas) ?></p>
            <div style="height: 70px;"></div>
            <p style="font-family: 'Times New Roman', serif; text-decoration: underline; font-weight: bold; margin-bottom: 0;"><?= $guruNama ?: '........................................' ?></p>
            <p style="font-family: 'Times New Roman', serif; margin-top: 5px;">NIP. ........................................</p>
        </div>
    </div>
    
    <?php endif; ?>
    <?php endif; ?>

</div>

<script>
// Auto-fill Filter Logic
const guruMap = <?= $guruKelasMapJson ?>;
document.getElementById('selGuruV')?.addEventListener('change', function() {
    const s = document.getElementById('selKelasV');
    if (this.value && guruMap[this.value]) {
        for (let i=0; i<s.options.length; i++) { if (s.options[i].value === guruMap[this.value]) s.selectedIndex = i; }
    }
});
document.getElementById('selGuruR')?.addEventListener('change', function() {
    const s = document.getElementById('selKelasR');
    if (this.value && guruMap[this.value]) {
        for (let i=0; i<s.options.length; i++) { if (s.options[i].value === guruMap[this.value]) s.selectedIndex = i; }
    }
});
</script>
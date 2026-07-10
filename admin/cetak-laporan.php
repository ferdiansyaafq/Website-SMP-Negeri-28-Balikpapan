<?php
declare(strict_types=1);

require_once '../includes/admin_auth.php';
requireAdminLogin();
require_once '../config/database.php';

function cpPickImage(array $candidates, string $fallback): string
{
    foreach ($candidates as $path) {
        $fs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (is_file($fs)) {
            $v = (int) @filemtime($fs);
            return '../' . $path . ($v > 0 ? ('?v=' . $v) : '');
        }
    }
    return $fallback;
}

$logoSekolah = cpPickImage([
    'assets/img/logo-sekolah.png', 'assets/img/logo-sekolah.jpg',
    'assets/img/logo-sekolah.jpeg', 'assets/img/logo-sekolah.webp',
    'assets/img/logo-sekolah.svg', 'assets/img/logo.png',
], '../assets/img/logo-sekolah.svg');

$kopSurat = cpPickImage([
    'assets/img/kopsurat.png', 'assets/img/kop-surat.png',
    'assets/img/kopsurat.jpg', 'assets/img/kopsurat.jpeg', 'assets/img/kopsurat.webp',
], '');

// ── Input validation ──────────────────────────────────────────────────────────
$filterGuruId  = (int)($_GET['guru_id'] ?? 0);
$filterSiswaId = (int)($_GET['siswa_id'] ?? 0);
$filterKelas   = trim((string)($_GET['kelas'] ?? ''));
$year          = max(2020, min(2035, (int)($_GET['year'] ?? (int)date('Y'))));
$autoSem       = ((int)date('n')) >= 7 ? 2 : 1;
$semester      = in_array((int)($_GET['semester'] ?? $autoSem), [1, 2]) ? (int)$_GET['semester'] : $autoSem;
$autoPrint     = isset($_GET['print']);
$isSiswaMode   = $filterSiswaId > 0;

$conn = getConnection();

// ── Guru info ─────────────────────────────────────────────────────────────────
$guruNama = '';
$kelas    = $filterKelas;
if ($filterGuruId > 0) {
    $stmtG = $conn->prepare("SELECT nama_guru, kelas FROM guru WHERE id = ? LIMIT 1");
    if ($stmtG) {
        $stmtG->bind_param('i', $filterGuruId);
        $stmtG->execute();
        $row = $stmtG->get_result()->fetch_assoc();
        $stmtG->close();
        if ($row) {
            $guruNama = (string)($row['nama_guru'] ?? '');
            if ($kelas === '') $kelas = (string)($row['kelas'] ?? '');
        }
    }
}

if ($kelas === '') {
    echo '<p style="font-family:sans-serif;padding:40px">Kelas tidak ditemukan. <a href="laporan-guru.php">Kembali</a></p>';
    $conn->close();
    exit;
}

$semMonths   = $semester === 1 ? [1,2,3,4,5,6] : [7,8,9,10,11,12];
$semStartYmd = sprintf('%04d-%02d-01', $year, $semMonths[0]);
$semEndYmd   = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $semMonths[count($semMonths)-1])))
                ->modify('last day of this month')->format('Y-m-d');
$labelMap    = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
                7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
$semLabel    = ($semester === 1 ? 'Semester 1 (Jan-Jun)' : 'Semester 2 (Jul-Des)') . ' ' . $year;

if ($isSiswaMode) {
    $student  = null;
    $actStats = ['submitted'=>0,'sum_score'=>0,'bangun'=>0,'ibadah'=>0,'olahraga'=>0,
                 'sarapan'=>0,'membaca'=>0,'membantu'=>0,'menabung'=>0];
    $stmtS = $conn->prepare("SELECT id, nisn, nama_siswa, kelas FROM siswa WHERE id = ? AND kelas = ? LIMIT 1");
    if ($stmtS) {
        $stmtS->bind_param('is', $filterSiswaId, $kelas);
        $stmtS->execute();
        $student = $stmtS->get_result()->fetch_assoc() ?: null;
        $stmtS->close();
    }
    if ($student) {
        $stmtL = $conn->prepare(
            'SELECT bangun,ibadah,olahraga,sarapan,membaca,membantu,menabung
             FROM laporan_harian WHERE siswa_id=? AND tanggal BETWEEN ? AND ?'
        );
        if ($stmtL) {
            $stmtL->bind_param('iss', $filterSiswaId, $semStartYmd, $semEndYmd);
            $stmtL->execute();
            $res = $stmtL->get_result();
            while ($r = $res->fetch_assoc()) {
                $actStats['submitted']++;
                $score = 0;
                foreach (['bangun','ibadah','olahraga','sarapan','membaca','membantu','menabung'] as $k) {
                    $v = (int)($r[$k] ?? 0); $actStats[$k] += $v; $score += $v;
                }
                $actStats['sum_score'] += $score;
            }
            $stmtL->close();
        }
    }
    $conn->close();
    $avgScore   = $actStats['submitted'] > 0 ? ((float)$actStats['sum_score'] / (float)$actStats['submitted']) : 0.0;
    $overallPct = max(0.0, min(100.0, ($avgScore / 7.0) * 100.0));
    $items      = ['bangun'=>'Bangun Pagi','ibadah'=>'Ibadah','olahraga'=>'Olahraga',
                   'sarapan'=>'Sarapan','membaca'=>'Membaca','membantu'=>'Membantu Ortu','menabung'=>'Menabung'];
    $barColors  = [
        'bangun'=>['#14b8a6','#0f766e'],'ibadah'=>['#22c55e','#16a34a'],
        'olahraga'=>['#0ea5e9','#0284c7'],'sarapan'=>['#f59e0b','#d97706'],
        'membaca'=>['#8b5cf6','#6d28d9'],'membantu'=>['#fb7185','#e11d48'],
        'menabung'=>['#10b981','#047857'],
    ];
} else {
    $students = []; $summary = []; $semAgg = [];
    foreach ($semMonths as $m) { $semAgg[$m] = ['submitted'=>0,'validated'=>0,'score_sum'=>0,'rows'=>0]; }
    $stmtS = $conn->prepare("SELECT id, nisn, nama_siswa, kelas FROM siswa WHERE kelas = ? ORDER BY nama_siswa ASC");
    if ($stmtS) {
        $stmtS->bind_param('s', $kelas); $stmtS->execute();
        $res = $stmtS->get_result();
        while ($r = $res->fetch_assoc()) {
            $students[(int)$r['id']] = $r;
            $summary[(int)$r['id']] = ['terkirim'=>0,'valid_any'=>0,'valid_ortu'=>0,'valid_guru'=>0,'score_sum'=>0];
        }
        $stmtS->close();
    }
    if (!empty($students)) {
        $ids = implode(',', array_keys($students));
        $stmtL = $conn->prepare("SELECT lh.* FROM laporan_harian lh WHERE lh.siswa_id IN ($ids) AND lh.tanggal BETWEEN ? AND ? ORDER BY lh.tanggal ASC");
        if ($stmtL) {
            $stmtL->bind_param('ss', $semStartYmd, $semEndYmd); $stmtL->execute();
            $res = $stmtL->get_result();
            while ($r = $res->fetch_assoc()) {
                $sid = (int)($r['siswa_id'] ?? 0); if (!isset($summary[$sid])) continue;
                $score = (int)$r['bangun']+(int)$r['ibadah']+(int)$r['olahraga']+(int)$r['sarapan']+(int)$r['membaca']+(int)$r['membantu']+(int)$r['menabung'];
                $m = (int)substr((string)($r['tanggal'] ?? ''), 5, 2);
                $summary[$sid]['terkirim']++; $summary[$sid]['score_sum'] += $score;
                if (!empty($r['orang_tua_validated_at'])) $summary[$sid]['valid_ortu']++;
                if (!empty($r['guru_validated_at'])) $summary[$sid]['valid_guru']++;
                if (!empty($r['orang_tua_validated_at']) || !empty($r['guru_validated_at'])) $summary[$sid]['valid_any']++;
                if (isset($semAgg[$m])) {
                    $semAgg[$m]['submitted']++; $semAgg[$m]['score_sum'] += $score; $semAgg[$m]['rows']++;
                    if (!empty($r['guru_validated_at']) || !empty($r['orang_tua_validated_at'])) $semAgg[$m]['validated']++;
                }
            }
            $stmtL->close();
        }
    }
    $conn->close();
    $semesterSeries = [];
    foreach ($semMonths as $m) {
        $rows = (int)($semAgg[$m]['rows'] ?? 0);
        $avg  = $rows > 0 ? ((float)$semAgg[$m]['score_sum'] / (float)$rows) : 0.0;
        $semesterSeries[] = ['label'=>$labelMap[$m]??$m,'avg'=>$avg,'submitted'=>(int)($semAgg[$m]['submitted']??0),'validated'=>(int)($semAgg[$m]['validated']??0)];
    }
    $totalStudents = count($students); $studentsReported = 0; $totalSubmitted = 0;
    $totalValidAny = 0; $totalValidOrtu = 0; $totalValidGuru = 0; $classScoreSum = 0; $classScoreRows = 0;
    foreach ($summary as $agg) {
        $t = (int)$agg['terkirim']; if ($t > 0) $studentsReported++;
        $totalSubmitted += $t; $totalValidAny += (int)$agg['valid_any'];
        $totalValidOrtu += (int)$agg['valid_ortu']; $totalValidGuru += (int)$agg['valid_guru'];
        $classScoreSum += (int)$agg['score_sum']; $classScoreRows += $t;
    }
    $classAverage = $classScoreRows > 0 ? ($classScoreSum / $classScoreRows) : 0.0;
    $kelasTitle   = preg_replace('/^Kelas\s+/i', '', $kelas);
}

$adminUser = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak Laporan — KAIH Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/portal.css">
  <link rel="stylesheet" href="../assets/css/report-pages.css">
  <style>
    @media print {
      @page { size: A4 portrait; margin: 8mm 8mm 10mm; }
      * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
      body.report-body { background: #fff !important; color: #111827 !important; }
      body.report-body::before, body.report-body::after { display: none !important; }
      .report-topbar,.hero-grid,.summary-grid,.print-actions,.screen-only { display: none !important; }
      .report-shell { max-width: none; padding: 0; }
      .print-document-header,.print-only { display: block !important; }
      .report-card,.summary-card { border:none !important; box-shadow:none !important; background:#fff !important; backdrop-filter:none !important; -webkit-backdrop-filter:none !important; }
      .print-kop { display: block !important; }
      .chart-panel,.metric-bar { border-color:rgba(17,24,39,.16) !important; background:#fff !important; box-shadow:none !important; }
      .print-sheet,.chart-panel,.print-meta-grid,.print-meta-item,.activity-row { break-inside:avoid; page-break-inside:avoid; }
      .report-card { padding:0 !important; margin:0 !important; }
      .section-card,.report-toolbar,.report-toolbar-copy,.chart-panel,.chart-grid,.chart-legend,.activity-grid { break-inside:avoid; page-break-inside:avoid; }
      .print-kop { margin-bottom:10px !important; }
      .print-kop img { padding-bottom:6px !important; }
      .print-document-header { margin-bottom:10px !important; }
      .print-document-title { font-size:22px !important; line-height:1.1 !important; }
      .print-document-subtitle { margin-top:4px !important; font-size:11px !important; line-height:1.45 !important; color:#4b5563 !important; }
      .print-meta-grid { grid-template-columns:repeat(4,minmax(0,1fr)) !important; gap:6px !important; margin-top:10px !important; }
      .print-meta-item { padding:8px 10px !important; border-radius:10px !important; }
      .print-meta-item strong { margin-bottom:2px !important; font-size:10px !important; letter-spacing:.8px !important; }
      .print-meta-item span { font-size:11px !important; }
      .report-toolbar { margin:0 !important; padding:0 !important; }
      .report-toolbar-copy h3 { font-size:18px !important; margin:0 !important; }
      .report-toolbar-copy p,.report-toolbar-copy .section-kicker { margin:2px 0 0 !important; line-height:1.35 !important; }
      .chart-panel { margin-top:10px !important; padding:12px 12px 10px !important; }
      .chart-legend { gap:6px !important; margin-bottom:8px !important; }
      .legend-pill { padding:5px 8px !important; font-size:10px !important; }
      .chart-grid.compact { min-height:180px !important; gap:10px !important; }
      .chart-col { gap:6px !important; }
      .chart-label { font-size:10px !important; }
      .activity-grid { gap:8px !important; margin-top:8px !important; }
      .activity-row { grid-template-columns:112px minmax(0,1fr) 42px !important; gap:8px !important; }
      .activity-name,.activity-pct { font-size:11px !important; }
      .metric-bar { min-width:0 !important; height:8px !important; }
      .metric-value { min-width:42px !important; font-size:11px !important; }
      .title-stack h1,.summary-value,.meter-copy h3,.report-toolbar-copy h3,.activity-title,.metric-value,
      .print-document-title,.print-meta-item span,.print-meta-item strong,.activity-name,.activity-pct { color:#111827 !important; text-shadow:none !important; }
      .title-stack p,.summary-note,.report-toolbar-copy p,.meter-copy p,.chart-label,.table-subtitle,
      .stat-chip,.legend-pill,.print-document-subtitle,.section-kicker,.chart-legend,.print-only .table-subtitle { color:#4b5563 !important; }
      .report-table th,.report-table td { color:#111827 !important; }
      .pill,.legend-pill,.chip-static,.stat-chip,.print-meta-item { border-color:rgba(17,24,39,.12) !important; background:#fff !important; }
      .metric-bar span,.chart-bar,.chart-bar.validated { box-shadow:none !important; }
      .print-only { margin-top:10px !important; }
      .print-only .table-subtitle { font-size:10px !important; line-height:1.35 !important; }
    }
    .print-kop { display:none; width:100%; margin:0 0 18px; }
    .print-kop img { width:100%; height:auto; display:block; border-bottom:2px solid #111827; padding-bottom:10px; }
    .activity-grid { display:grid; gap:12px; margin-top:18px; }
    .activity-row { display:grid; grid-template-columns:minmax(120px,180px) minmax(0,1fr) 56px; gap:12px; align-items:center; }
    .activity-name { font-size:13px; font-weight:800; color:var(--report-text); }
    .activity-pct  { font-size:12px; font-weight:800; color:var(--report-text); text-align:right; }
    .back-link { align-self:flex-start; }
    .table-wrap .report-table { min-width:920px; }
    @media (max-width:720px) { .table-wrap .report-table { min-width:760px; } .activity-row { grid-template-columns:1fr; } .activity-pct { text-align:left; } }
  </style>
</head>
<body class="report-body role-guru"<?php echo $autoPrint ? ' onload="window.print()"' : ''; ?>>
<div class="report-shell">

  <header class="report-topbar">
    <div class="topbar-main">
      <div class="brand-row">
        <a class="chip-link back-link print-actions" href="laporan-guru.php?tab=rekap&guru_id=<?php echo $filterGuruId; ?>&kelas=<?php echo urlencode($kelas); ?>&year=<?php echo $year; ?>&semester=<?php echo $semester; ?>">&#8592; Kembali</a>
        <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo Sekolah" class="brand-logo">
        <div class="title-stack">
          <p class="title-eyebrow"><?php echo $isSiswaMode ? 'Grafik Kegiatan Siswa' : 'Cetak Laporan Kelas'; ?></p>
          <h1><?php echo $isSiswaMode ? htmlspecialchars('Detail kegiatan ' . ($student['nama_siswa'] ?? 'Siswa') . ' &mdash; ' . $semLabel) : htmlspecialchars('Rekap kelas ' . $kelas . ' &mdash; ' . $semLabel); ?></h1>
        </div>
      </div>
      <?php if (!$isSiswaMode): ?>
      <div class="quick-nav">
        <a href="laporan-guru.php">Laporan Guru</a>
        <a class="active" href="#">Cetak Laporan</a>
      </div>
      <?php endif; ?>
    </div>
    <div class="top-actions print-actions">
      <div class="chip-static">
        <span class="chip-avatar"><?php echo strtoupper(substr($adminUser, 0, 1)); ?></span>
        <span><?php echo htmlspecialchars($adminUser); ?></span>
      </div>
      <a class="chip-link primary" href="<?php echo htmlspecialchars('cetak-laporan.php?' . http_build_query(array_filter(['guru_id'=>$filterGuruId?:null,'siswa_id'=>$filterSiswaId?:null,'kelas'=>$kelas,'year'=>$year,'semester'=>$semester,'print'=>'1']))); ?>">Cetak / Simpan PDF</a>
    </div>
  </header>

<?php if ($isSiswaMode && $student): ?>

  <section class="hero-grid">
    <article class="report-card hero-card print-sheet">
      <?php if ($kopSurat !== ''): ?>
      <div class="print-kop"><img src="<?php echo htmlspecialchars($kopSurat); ?>" alt="Kop Surat"></div>
      <?php endif; ?>
      <p class="section-kicker">Ringkasan Siswa</p>
      <h2><?php echo htmlspecialchars($semLabel); ?> &mdash; kegiatan <strong><?php echo htmlspecialchars($student['nama_siswa']); ?></strong> kelas <?php echo htmlspecialchars($kelas); ?>.</h2>
      <p>Persentase dihitung dari rata-rata skor 7 kegiatan per hari selama semester terpilih.</p>
      <div class="hero-meta">
        <span class="pill sent">NISN: <?php echo htmlspecialchars($student['nisn'] ?? ''); ?></span>
        <span class="pill pending">Laporan: <?php echo $actStats['submitted']; ?> hari</span>
        <span class="pill done">Rata-rata: <?php echo number_format($avgScore, 2, ',', '.'); ?>/7</span>
      </div>
    </article>
    <article class="report-card summary-card meter-card">
      <div class="meter-copy">
        <span class="summary-label">Progres Umum</span>
        <h3>Persentase rata-rata kegiatan</h3>
        <p>Proporsi keterisian kegiatan siswa pada <?php echo htmlspecialchars($semLabel); ?>.</p>
      </div>
      <div class="meter-ring" style="--progress-deg: <?php echo (int)round(($overallPct / 100) * 360); ?>deg;">
        <span><?php echo (int)round($overallPct); ?>%</span>
      </div>
    </article>
  </section>

  <section class="summary-grid">
    <article class="summary-card"><span class="summary-label">Nama Siswa</span><div class="summary-value"><?php echo htmlspecialchars($student['nama_siswa']); ?></div><div class="summary-note">Kelas <?php echo htmlspecialchars($kelas); ?></div></article>
    <article class="summary-card"><span class="summary-label">NISN</span><div class="summary-value"><?php echo htmlspecialchars($student['nisn'] ?? '&mdash;'); ?></div><div class="summary-note">Nomor identitas siswa</div></article>
    <article class="summary-card"><span class="summary-label">Laporan Terkirim</span><div class="summary-value"><?php echo $actStats['submitted']; ?></div><div class="summary-note">Hari laporan dalam <?php echo htmlspecialchars($semLabel); ?></div></article>
    <article class="summary-card"><span class="summary-label">Rata-rata Skor</span><div class="summary-value"><?php echo number_format($avgScore, 1, ',', '.'); ?>/7</div><div class="summary-note">Rata-rata kegiatan per hari</div></article>
  </section>

  <section class="report-card section-card print-sheet">
    <div class="print-document-header avoid-break">
      <?php if ($kopSurat !== ''): ?>
      <div class="print-kop"><img src="<?php echo htmlspecialchars($kopSurat); ?>" alt="Kop Surat"></div>
      <?php endif; ?>
      <h2 class="print-document-title">Grafik Kegiatan Siswa</h2>
      <p class="print-document-subtitle">Ringkasan kegiatan siswa untuk periode <?php echo htmlspecialchars($semLabel); ?>. Persentase menunjukkan frekuensi tiap kebiasaan dibanding total hari laporan.</p>
      <div class="print-meta-grid">
        <div class="print-meta-item"><strong>Nama Siswa</strong><span><?php echo htmlspecialchars($student['nama_siswa']); ?></span></div>
        <div class="print-meta-item"><strong>NISN</strong><span><?php echo htmlspecialchars($student['nisn'] ?? '&mdash;'); ?></span></div>
        <div class="print-meta-item"><strong>Periode</strong><span><?php echo htmlspecialchars($semLabel); ?></span></div>
        <div class="print-meta-item"><strong>Rata-rata</strong><span><?php echo number_format($avgScore, 1, ',', '.'); ?>/7 &bull; <?php echo (int)round($overallPct); ?>%</span></div>
      </div>
    </div>
    <div class="report-toolbar">
      <div class="report-toolbar-copy">
        <p class="section-kicker">Detail Kebiasaan</p>
        <h3>Persentase per kegiatan</h3>
        <p>Batang menunjukkan seberapa sering masing-masing kegiatan dilakukan dibanding total hari laporan pada periode ini.</p>
      </div>
    </div>
    <div class="chart-panel avoid-break" style="margin-top:18px;">
      <div class="activity-grid">
        <?php foreach ($items as $key => $labelAct):
          $count  = (int)$actStats[$key];
          $pct    = $actStats['submitted'] > 0 ? max(0.0, min(100.0, ($count / $actStats['submitted']) * 100.0)) : 0.0;
          $colors = $barColors[$key] ?? ['#6366f1','#4f46e5'];
        ?>
        <div class="activity-row">
          <div class="activity-name"><?php echo htmlspecialchars($labelAct); ?></div>
          <div class="metric-bar"><span style="width:<?php echo (int)round($pct); ?>%;background:linear-gradient(135deg,<?php echo $colors[0]; ?>,<?php echo $colors[1]; ?>);"></span></div>
          <div class="activity-pct"><?php echo (int)round($pct); ?>%</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

<?php elseif (!$isSiswaMode): ?>

  <section class="hero-grid">
    <article class="report-card hero-card">
      <p class="section-kicker">Ringkasan Rekap</p>
      <h2><?php echo htmlspecialchars($semLabel); ?> &mdash; laporan kelas <?php echo htmlspecialchars($kelas); ?>.</h2>
      <p>Rekap ini menampilkan grafik semester siap cetak, tabel per siswa, dan ringkasan validasi kelas.</p>
      <div class="hero-meta">
        <span class="pill sent">Siswa aktif: <?php echo $studentsReported; ?>/<?php echo $totalStudents; ?></span>
        <span class="pill done">Validasi total: <?php echo $totalValidAny; ?></span>
        <span class="pill pending">Rata-rata kelas: <?php echo number_format($classAverage, 1, ',', '.'); ?>/7</span>
      </div>
    </article>
    <article class="report-card summary-card meter-card">
      <div class="meter-copy">
        <span class="summary-label">Filter Cetak</span>
        <h3>Pilih semester rekap kelas</h3>
        <p>Tentukan tahun dan semester, lalu cetak atau simpan PDF.</p>
      </div>
      <div style="width:100%;">
        <form class="filter-form" method="get" action="">
          <input type="hidden" name="guru_id" value="<?php echo $filterGuruId; ?>">
          <input type="hidden" name="kelas" value="<?php echo htmlspecialchars($kelas, ENT_QUOTES); ?>">
          <select name="year">
            <?php for ($yy=(int)date('Y')-1; $yy<=(int)date('Y')+1; $yy++): ?>
            <option value="<?php echo $yy; ?>" <?php echo $yy===$year?'selected':''; ?>><?php echo $yy; ?></option>
            <?php endfor; ?>
          </select>
          <select name="semester">
            <option value="1" <?php echo $semester===1?'selected':''; ?>>Semester 1</option>
            <option value="2" <?php echo $semester===2?'selected':''; ?>>Semester 2</option>
          </select>
          <button type="submit" class="primary-btn">Tampilkan</button>
        </form>
        <div class="stat-chip-row print-actions">
          <a class="chip-link primary" href="cetak-laporan.php?guru_id=<?php echo $filterGuruId; ?>&kelas=<?php echo urlencode($kelas); ?>&year=<?php echo $year; ?>&semester=<?php echo $semester; ?>&print=1">Cetak / Simpan PDF</a>
          <span class="stat-chip"><strong>Tip</strong> Pilih Save as PDF di dialog print.</span>
        </div>
      </div>
    </article>
  </section>

  <section class="summary-grid">
    <article class="summary-card"><span class="summary-label">Total Siswa</span><div class="summary-value"><?php echo $totalStudents; ?></div><div class="summary-note">Terdaftar di kelas <?php echo htmlspecialchars($kelas); ?></div></article>
    <article class="summary-card"><span class="summary-label">Laporan Terkirim</span><div class="summary-value"><?php echo $totalSubmitted; ?></div><div class="summary-note">Total seluruh laporan semester ini</div></article>
    <article class="summary-card"><span class="summary-label">Validasi Ortu / Guru</span><div class="summary-value"><?php echo $totalValidOrtu; ?> / <?php echo $totalValidGuru; ?></div><div class="summary-note">Validasi orang tua dan guru</div></article>
    <article class="summary-card"><span class="summary-label">Rata-rata Kelas</span><div class="summary-value"><?php echo number_format($classAverage, 1, ',', '.'); ?>/7</div><div class="summary-note">Skor rata-rata seluruh siswa</div></article>
  </section>

  <section class="report-card section-card print-sheet">
    <?php if ($kopSurat !== ''): ?>
    <div class="print-kop"><img src="<?php echo htmlspecialchars($kopSurat); ?>" alt="Kop Surat"></div>
    <?php endif; ?>
    <div class="print-document-header avoid-break">
      <h2 class="print-document-title">Rekap Laporan Kegiatan Kelas <?php echo htmlspecialchars($kelasTitle); ?></h2>
      <p class="print-document-subtitle">Dokumen rekap guru untuk <?php echo htmlspecialchars($semLabel); ?>. Grafik menampilkan rata-rata skor kegiatan per bulan dengan penanda validasi.</p>
      <div class="print-meta-grid">
        <div class="print-meta-item"><strong>Kelas</strong><span><?php echo htmlspecialchars($kelas); ?></span></div>
        <div class="print-meta-item"><strong>Wali / Guru</strong><span><?php echo htmlspecialchars($guruNama ?: '&mdash;'); ?></span></div>
        <div class="print-meta-item"><strong>Total Siswa</strong><span><?php echo $totalStudents; ?> siswa</span></div>
        <div class="print-meta-item"><strong>Rata-rata Kelas</strong><span><?php echo number_format($classAverage, 1, ',', '.'); ?>/7</span></div>
      </div>
    </div>
    <div class="report-toolbar">
      <div class="report-toolbar-copy">
        <p class="section-kicker">Siap Cetak</p>
        <h3>Grafik semester kelas <?php echo htmlspecialchars($kelasTitle); ?></h3>
        <p><?php echo htmlspecialchars($semLabel); ?>. Tinggi batang menunjukkan rata-rata skor kegiatan dari 0 sampai 7, dan batang hijau menunjukkan sudah ada validasi.</p>
      </div>
    </div>
    <div class="chart-panel avoid-break" style="margin-top:18px;">
      <div class="chart-legend">
        <span class="legend-pill"><span class="legend-dot"></span> Ada laporan</span>
        <span class="legend-pill"><span class="legend-dot validated"></span> Sudah divalidasi</span>
      </div>
      <div class="chart-grid compact" style="grid-template-columns:repeat(6,minmax(52px,1fr));">
        <?php foreach ($semesterSeries as $p):
          $avg = (float)($p['avg']??0); $h = max(6,min(100,(int)round(($avg/7.0)*100)));
          $has = ((int)($p['submitted']??0)) > 0;
          $cls = 'chart-bar' . (!$has ? ' empty' : ((int)($p['validated']??0) > 0 ? ' validated' : ''));
        ?>
        <div class="chart-col">
          <div class="<?php echo $cls; ?>" style="height:<?php echo $h; ?>%;" data-label="<?php echo htmlspecialchars($p['label'].': rata-rata '.number_format($avg,1,',','.').'/7'); ?>"></div>
          <span class="chart-label"><?php echo htmlspecialchars($p['label']); ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="print-only" style="margin-top:18px;">
      <div class="table-subtitle">Dokumen ini dicetak untuk rekap guru kelas <?php echo htmlspecialchars($kelas); ?> pada <?php echo htmlspecialchars($semLabel); ?>.</div>
    </div>
  </section>

  <section class="report-card history-card screen-only">
    <div class="history-head">
      <div>
        <p class="section-kicker">Daftar Siswa</p>
        <h3>Tabel rekap dan aksi cetak per siswa</h3>
        <p>Klik "Cetak Grafik" untuk mencetak grafik kegiatan tiap siswa secara terpisah.</p>
      </div>
    </div>
    <div class="table-wrap">
      <table class="report-table">
        <thead><tr><th>Nama</th><th>NISN</th><th>Laporan</th><th>Validasi</th><th>Rata-rata</th><th>Grafik</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php if (empty($students)): ?>
          <tr><td colspan="7">Tidak ada siswa di kelas ini.</td></tr>
          <?php else: foreach ($students as $sid => $s):
            $agg = $summary[$sid]??['terkirim'=>0,'valid_any'=>0,'valid_ortu'=>0,'valid_guru'=>0,'score_sum'=>0];
            $avg = (int)$agg['terkirim']>0 ? ((float)$agg['score_sum']/(float)$agg['terkirim']) : 0.0;
            $pct = max(0,min(100,(int)round(($avg/7.0)*100)));
          ?>
          <tr>
            <td><?php echo htmlspecialchars($s['nama_siswa']); ?></td>
            <td><?php echo htmlspecialchars($s['nisn']??''); ?></td>
            <td><?php echo (int)$agg['terkirim']; ?></td>
            <td><span class="pill done"><?php echo (int)$agg['valid_any']; ?> total</span><div class="table-subtitle">Ortu: <?php echo (int)$agg['valid_ortu']; ?> &bull; Guru: <?php echo (int)$agg['valid_guru']; ?></div></td>
            <td><?php echo number_format($avg,2,',','.'); ?>/7</td>
            <td><div class="metric-inline"><div class="metric-bar"><span style="width:<?php echo $pct; ?>%;"></span></div><div class="metric-value"><?php echo $pct; ?>%</div></div></td>
            <td><div class="table-actions">
              <a class="chip-link" href="cetak-laporan.php?siswa_id=<?php echo (int)$sid; ?>&guru_id=<?php echo $filterGuruId; ?>&kelas=<?php echo urlencode($kelas); ?>&year=<?php echo $year; ?>&semester=<?php echo $semester; ?>">Lihat</a>
              <a class="chip-link primary" href="cetak-laporan.php?siswa_id=<?php echo (int)$sid; ?>&guru_id=<?php echo $filterGuruId; ?>&kelas=<?php echo urlencode($kelas); ?>&year=<?php echo $year; ?>&semester=<?php echo $semester; ?>&print=1">Cetak Grafik</a>
            </div></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </section>

<?php else: ?>
  <p style="padding:40px;font-family:sans-serif;">Data siswa tidak ditemukan. <a href="laporan-guru.php">Kembali</a></p>
<?php endif; ?>

</div>
</body>
</html>

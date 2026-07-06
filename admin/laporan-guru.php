<?php
declare(strict_types=1);

require_once '../includes/admin_auth.php';
requireAdminLogin();
require_once '../config/database.php';

$username = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');
$conn = getConnection();

// ── Kop surat image helper ───────────────────────────────────────────────
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
$kopSurat = cpPickImage([
    'assets/img/kopsurat.png', 'assets/img/kop-surat.png',
    'assets/img/kopsurat.jpg', 'assets/img/kopsurat.jpeg', 'assets/img/kopsurat.webp',
], '');

// ── Handle POST: validate_guru ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'validate_guru') {
    $lId    = (int)($_POST['laporan_id'] ?? 0);
    $pKelas = trim((string)($_POST['kelas'] ?? ''));
    if ($lId > 0 && $pKelas !== '') {
        $stmtV = $conn->prepare(
            'UPDATE laporan_harian lh
             JOIN siswa s ON s.id = lh.siswa_id
             SET lh.guru_validated_at = IFNULL(lh.guru_validated_at, NOW())
             WHERE lh.id = ? AND s.kelas = ?'
        );
        if ($stmtV) { $stmtV->bind_param('is', $lId, $pKelas); $stmtV->execute(); $stmtV->close(); }
    }
    $gid = (int)($_POST['guru_id'] ?? 0);
    $tgl = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tanggal'] ?? '') ? $_POST['tanggal'] : date('Y-m-d');
    header('Location: laporan-guru.php?tab=validasi&guru_id='.$gid.'&kelas='.urlencode($pKelas).'&tanggal='.urlencode($tgl).'&ok=1');
    exit;
}

// ── Filter helpers ─────────────────────────────────────────────────────────
$tab   = in_array($_GET['tab'] ?? '', ['validasi', 'rekap']) ? (string)$_GET['tab'] : 'validasi';
$flash = isset($_GET['ok']) ? 'Validasi guru berhasil disimpan.' : '';

$filterGuruId  = (int)($_GET['guru_id'] ?? 0);
$filterKelas   = trim((string)($_GET['kelas'] ?? ''));
$filterTanggal = trim((string)($_GET['tanggal'] ?? date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTanggal)) {
    $filterTanggal = date('Y-m-d');
}

$today          = new DateTimeImmutable('today');
$defaultSem     = ((int)$today->format('n')) >= 7 ? 2 : 1;
$year           = max(2020, min(2035, (int)($_GET['year'] ?? (int)$today->format('Y'))));
$semester       = in_array((int)($_GET['semester'] ?? $defaultSem), [1, 2]) ? (int)$_GET['semester'] : $defaultSem;

// ── Load dropdown data ─────────────────────────────────────────────────────
$allGuru = [];
$res = $conn->query("SELECT id, nama_guru, kelas AS wali_kelas FROM guru ORDER BY nama_guru ASC");
if ($res) { while ($r = $res->fetch_assoc()) { $allGuru[] = $r; } }

$allKelas = [];
$res = $conn->query("SELECT id, nama_kelas FROM kaih_kelas ORDER BY nama_kelas ASC");
if ($res) { while ($r = $res->fetch_assoc()) { $allKelas[] = $r; } }

// ── Auto-detect kelas from guru ────────────────────────────────────────────
$guruWaliKelas = '';
$guruNama      = '';
if ($filterGuruId > 0) {
    $stmtG = $conn->prepare("SELECT nama_guru, kelas FROM guru WHERE id = ? LIMIT 1");
    if ($stmtG) {
        $stmtG->bind_param('i', $filterGuruId);
        $stmtG->execute();
        $rowG = $stmtG->get_result()->fetch_assoc();
        $stmtG->close();
        if ($rowG) {
            $guruWaliKelas = (string)($rowG['kelas'] ?? '');
            $guruNama      = (string)($rowG['nama_guru'] ?? '');
            if ($filterKelas === '') {
                $filterKelas = $guruWaliKelas;
            }
        }
    }
}

// ── Tab Validasi: load data ────────────────────────────────────────────────
$validasiData  = [];
$valSudahKirim = [];
$valBelumKirim = [];
$valTotal = $valTerkirim = $valBelum = $valValid = 0;

if ($tab === 'validasi' && $filterKelas !== '') {
    $stmt = $conn->prepare(
        'SELECT s.id AS siswa_id, s.nisn, s.nama_siswa, s.kelas,
                lh.id AS laporan_id, lh.tanggal,
                lh.bangun, lh.ibadah, lh.olahraga, lh.sarapan,
                lh.membaca, lh.membantu, lh.menabung,
                lh.orang_tua_validated_at, lh.guru_validated_at
         FROM siswa s
         LEFT JOIN laporan_harian lh ON lh.siswa_id = s.id AND lh.tanggal = ?
         WHERE s.kelas = ?
         ORDER BY s.nama_siswa ASC'
    );
    if ($stmt) {
        $stmt->bind_param('ss', $filterTanggal, $filterKelas);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) { $validasiData[] = $r; }
        $stmt->close();
    }
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

// ── Tab Rekap: semester data ───────────────────────────────────────────────
$rekKelas        = $filterKelas;
$students        = [];
$summary         = [];
$semesterSeries  = [];
$studentSeries   = []; // per-student monthly chart data
$totalStudents   = $studentsReported   = $totalSubmitted  = 0;
$totalValidAny   = $totalValidOrtu     = $totalValidGuru  = 0;
$classScoreSum   = $classScoreRows     = 0;

if ($tab === 'rekap' && $rekKelas !== '') {
    $semMonths   = $semester === 1 ? [1,2,3,4,5,6] : [7,8,9,10,11,12];
    $semStartYmd = sprintf('%04d-%02d-01', $year, $semMonths[0]);
    $semEndYmd   = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $semMonths[count($semMonths)-1])))
                    ->modify('last day of this month')->format('Y-m-d');

    $stmtS = $conn->prepare('SELECT id, nisn, nama_siswa, kelas FROM siswa WHERE kelas = ? ORDER BY nama_siswa ASC');
    if ($stmtS) {
        $stmtS->bind_param('s', $rekKelas);
        $stmtS->execute();
        $res = $stmtS->get_result();
        while ($r = $res->fetch_assoc()) {
            $students[(int)$r['id']] = $r;
            $summary[(int)$r['id']]  = ['terkirim'=>0,'valid_any'=>0,'valid_ortu'=>0,'valid_guru'=>0,'score_sum'=>0];
        }
        $stmtS->close();
    }

    // Init per-student activity accumulators
    $kaihFields = ['bangun','ibadah','olahraga','sarapan','membaca','membantu','menabung'];
    $kaihLabels = ['bangun'=>'Bangun Pagi','ibadah'=>'Ibadah','olahraga'=>'Olahraga','sarapan'=>'Sarapan','membaca'=>'Membaca','membantu'=>'Membantu Ortu','menabung'=>'Menabung'];
    $studentActivityAgg = [];
    foreach (array_keys($students) as $_sid) {
        $studentActivityAgg[$_sid] = array_fill_keys($kaihFields, 0);
    }

    $stmtL = $conn->prepare(
        'SELECT lh.* FROM laporan_harian lh
         JOIN siswa s ON s.id = lh.siswa_id
         WHERE s.kelas = ? AND lh.tanggal BETWEEN ? AND ?'
    );
    if ($stmtL) {
        $stmtL->bind_param('sss', $rekKelas, $semStartYmd, $semEndYmd);
        $stmtL->execute();
        $res = $stmtL->get_result();
        while ($r = $res->fetch_assoc()) {
            $sid = (int)($r['siswa_id'] ?? 0);
            if (!isset($summary[$sid])) continue;
            $score = (int)$r['bangun'] + (int)$r['ibadah'] + (int)$r['olahraga']
                   + (int)$r['sarapan'] + (int)$r['membaca'] + (int)$r['membantu'] + (int)$r['menabung'];
            $summary[$sid]['terkirim']++;
            $summary[$sid]['score_sum'] += $score;
            if (!empty($r['orang_tua_validated_at'])) $summary[$sid]['valid_ortu']++;
            if (!empty($r['guru_validated_at']))       $summary[$sid]['valid_guru']++;
            if (!empty($r['orang_tua_validated_at']) || !empty($r['guru_validated_at'])) $summary[$sid]['valid_any']++;
            // Per-student activity sums
            foreach ($kaihFields as $f) {
                $studentActivityAgg[$sid][$f] += (int)$r[$f];
            }
        }
        $stmtL->close();
    }

    foreach ($summary as $agg) {
        if ((int)$agg['terkirim'] > 0) $studentsReported++;
        $totalSubmitted += (int)$agg['terkirim'];
        $totalValidAny  += (int)$agg['valid_any'];
        $totalValidOrtu += (int)$agg['valid_ortu'];
        $totalValidGuru += (int)$agg['valid_guru'];
        $classScoreSum  += (int)$agg['score_sum'];
        $classScoreRows += (int)$agg['terkirim'];
    }
    $totalStudents = count($students);

    // Semester bar chart
    $semAgg = [];
    foreach ($semMonths as $m) { $semAgg[$m] = ['submitted'=>0,'validated'=>0,'score_sum'=>0,'rows'=>0]; }
    $stmtSem = $conn->prepare(
        'SELECT lh.* FROM laporan_harian lh
         JOIN siswa s ON s.id = lh.siswa_id
         WHERE s.kelas = ? AND lh.tanggal BETWEEN ? AND ?
         ORDER BY lh.tanggal ASC'
    );
    if ($stmtSem) {
        $stmtSem->bind_param('sss', $rekKelas, $semStartYmd, $semEndYmd);
        $stmtSem->execute();
        $res = $stmtSem->get_result();
        while ($r = $res->fetch_assoc()) {
            $m = (int)substr((string)($r['tanggal'] ?? ''), 5, 2);
            if (!isset($semAgg[$m])) continue;
            $score = (int)$r['bangun']+(int)$r['ibadah']+(int)$r['olahraga']
                   + (int)$r['sarapan']+(int)$r['membaca']+(int)$r['membantu']+(int)$r['menabung'];
            $semAgg[$m]['submitted']++;
            $semAgg[$m]['score_sum'] += $score;
            $semAgg[$m]['rows']++;
            if (!empty($r['guru_validated_at']) || !empty($r['orang_tua_validated_at'])) $semAgg[$m]['validated']++;
        }
        $stmtSem->close();
    }
    $labelMap = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
                 7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
    foreach ($semMonths as $m) {
        $rows = (int)($semAgg[$m]['rows'] ?? 0);
        $avg  = $rows > 0 ? ((float)$semAgg[$m]['score_sum'] / (float)$rows) : 0.0;
        $semesterSeries[] = [
            'month'     => $m,
            'label'     => $labelMap[$m] ?? (string)$m,
            'avg'       => $avg,
            'submitted' => (int)($semAgg[$m]['submitted'] ?? 0),
            'validated' => (int)($semAgg[$m]['validated'] ?? 0),
        ];
    }

    // Build per-student activity percentage series
    foreach ($studentActivityAgg as $sid => $acts) {
        $total = (int)($summary[$sid]['terkirim'] ?? 0);
        $series = [];
        foreach ($kaihFields as $f) {
            $pct = $total > 0 ? round(((float)$acts[$f] / (float)$total) * 100, 1) : 0.0;
            $series[] = ['field' => $f, 'label' => $kaihLabels[$f], 'count' => (int)$acts[$f], 'total' => $total, 'pct' => $pct];
        }
        $studentSeries[$sid] = $series;
    }
}

$conn->close();

$guruKelasMapJson = json_encode(array_column($allGuru, 'wali_kelas', 'id'), JSON_UNESCAPED_UNICODE);
$classAvg         = $classScoreRows > 0 ? ($classScoreSum / $classScoreRows) : 0.0;
$semLabel         = $semester === 1 ? 'Semester 1 (Jan–Jun)' : 'Semester 2 (Jul–Des)';

function lgPill(string $text, string $type): string {
    return '<span class="pill-'.htmlspecialchars($type, ENT_QUOTES).'">'.htmlspecialchars($text).'</span>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <script>(function(){try{var t=localStorage.getItem('kaih_theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();function toggleTheme(){var h=document.documentElement,d=h.getAttribute('data-theme')==='dark';if(d)h.removeAttribute('data-theme');else h.setAttribute('data-theme','dark');try{localStorage.setItem('kaih_theme',d?'light':'dark');}catch(e){}}</script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan Guru — KAIH Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="../assets/css/siswa.css">
  <style>
  .lap-tabs{display:flex;gap:6px;margin-bottom:24px;flex-wrap:wrap;}
  .lap-tab{padding:8px 22px;border-radius:8px;border:1.5px solid var(--border);background:var(--surface);font-size:13px;font-weight:600;color:var(--muted);text-decoration:none;transition:.15s;}
  .lap-tab.active{background:var(--primary);border-color:var(--primary);color:#fff;}
  .lap-tab:hover:not(.active){border-color:var(--primary);color:var(--primary);}

  .lap-filter-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;}
  .lfg{display:flex;flex-direction:column;gap:5px;min-width:150px;}
  .lfg label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
  .lfg select,.lfg input[type=date],.lfg input[type=month]{padding:8px 10px;border-radius:8px;border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;font-family:inherit;outline:none;}
  .lfg select:focus,.lfg input:focus{border-color:var(--primary);}
  .lap-filter-btn{padding:9px 22px;border:none;border-radius:8px;background:var(--primary);color:#fff;font-size:13px;font-weight:600;cursor:pointer;align-self:flex-end;flex-shrink:0;}
  .lap-filter-btn:hover{opacity:.88;}
  .lap-filter-btn.green{background:#16a34a;}

  .lap-context{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;background:var(--primary-soft);border-radius:10px;font-size:13px;font-weight:500;color:var(--primary);margin-bottom:18px;}

  .lap-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:20px;}
  .lap-stat{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:14px 16px;}
  .lap-stat-label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;}
  .lap-stat-val{font-size:28px;font-weight:800;line-height:1.1;}
  .lap-stat-val.green{color:var(--success);}
  .lap-stat-val.red{color:var(--danger);}
  .lap-stat-val.purple{color:var(--primary);}
  .lap-stat-val.yellow{color:var(--warning,#d97706);}

  .lap-table-wrap{overflow-x:auto;border-radius:12px;border:1px solid var(--border);margin-bottom:20px;}
  .lap-table{width:100%;border-collapse:collapse;font-size:13px;}
  .lap-table th{background:var(--surface-2);padding:10px 14px;text-align:left;font-weight:600;font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);white-space:nowrap;}
  .lap-table td{padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:middle;}
  .lap-table tr:last-child td{border-bottom:none;}
  .lap-table tr:hover td{background:var(--primary-soft);}

  .group-head{font-size:13px;font-weight:700;color:var(--text);margin-bottom:10px;padding:10px 14px;background:var(--surface);border:1px solid var(--border);border-radius:10px;display:flex;align-items:center;gap:8px;}

  .pill-sent{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(34,197,94,.12);color:#16a34a;}
  .pill-notsent{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(239,68,68,.10);color:#dc2626;}
  .pill-valid{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(99,102,241,.12);color:#6366f1;}
  .pill-pending{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(245,158,11,.12);color:#d97706;}
  .pill-kelas{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:var(--primary-soft);color:var(--primary);}

  .lap-empty{text-align:center;padding:48px 20px;color:var(--muted);font-size:14px;}
  .lap-empty strong{display:block;font-size:16px;margin-bottom:6px;color:var(--text);}
  .lap-prompt{text-align:center;padding:40px 20px;color:var(--muted);font-size:14px;background:var(--surface);border:1px solid var(--border);border-radius:12px;}
  .lap-prompt strong{display:block;font-size:15px;color:var(--text);margin-bottom:6px;}

  /* Semester chart */
  .chart-panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:20px;}
  .chart-legend{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px;font-size:12px;}
  .legend-dot{width:10px;height:10px;border-radius:50%;background:var(--primary);display:inline-block;margin-right:5px;}
  .legend-dot.val{background:#22c55e;}

  /* SVG Line chart */
  .line-chart-wrap{width:100%;overflow-x:auto;padding:4px 0;}
  .line-chart-svg{width:100%;height:200px;display:block;}
  .line-chart-svg .grid-line{stroke:var(--border);stroke-width:1;stroke-dasharray:4 4;}
  .line-chart-svg .grid-text{fill:var(--muted);font-size:10px;font-weight:600;}
  .line-chart-svg .area-fill{fill:var(--primary);opacity:.12;}
  .line-chart-svg .line-stroke{fill:none;stroke:var(--primary);stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}
  .line-chart-svg .dot{fill:var(--primary);stroke:var(--surface);stroke-width:2;cursor:pointer;}
  .line-chart-svg .dot:hover{r:7;}
  .line-chart-svg .dot.validated{fill:#22c55e;}
  .line-chart-svg .dot.empty{fill:var(--muted);opacity:.4;}
  .line-chart-svg .x-label{fill:var(--muted);font-size:10px;font-weight:700;text-anchor:middle;}

  /* metric-bar for student table */
  .metric-inline{display:flex;align-items:center;gap:8px;}
  .metric-bar{flex:1;height:6px;border-radius:4px;background:var(--border);overflow:hidden;}
  .metric-bar span{display:block;height:100%;border-radius:4px;background:var(--primary);}
  .metric-value{font-size:11px;font-weight:700;color:var(--muted);white-space:nowrap;}

  /* Activity bars for per-student print */
  .activity-list{list-style:none;padding:0;margin:0;}
  .activity-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #eee;}
  .activity-row:last-child{border-bottom:none;}
  .activity-name{width:120px;font-size:13px;font-weight:600;flex-shrink:0;}
  .activity-bar-wrap{flex:1;height:22px;background:#f1f5f9;border-radius:6px;overflow:hidden;position:relative;}
  .activity-bar-fill{height:100%;border-radius:6px;transition:width .3s;}
  .activity-pct{width:60px;text-align:right;font-size:13px;font-weight:700;flex-shrink:0;}
  .clr-bangun{background:#6366f1;} .clr-ibadah{background:#8b5cf6;}
  .clr-olahraga{background:#f59e0b;} .clr-sarapan{background:#10b981;}
  .clr-membaca{background:#3b82f6;} .clr-membantu{background:#ec4899;}
  .clr-menabung{background:#14b8a6;}

  /* ── Print-only styles ──────────────────────────────────────────────── */
  .print-kop{display:none;}
  .print-class-header{display:none;}
  .student-chart-print{display:none;}
  .no-print{}
  @media print{
    body{background:#fff !important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
    .sidebar,.topbar,.breadcrumb,.lap-tabs,.lap-filter-card,.lap-context,.lap-stats,
    .lap-prompt,.lap-empty,.theme-toggle,#themeToggle,.no-print{display:none !important;}
    .layout{display:block !important;}
    .main-content{margin:0 !important;padding:0 !important;}
    .content-area{padding:0 !important;}
    .print-kop{display:block !important;text-align:center;margin-bottom:14px;}
    .print-kop img{max-width:100%;height:auto;}
    .print-class-header{display:block !important;margin-bottom:16px;}
    .pch-guru{font-size:14px;color:#111827;margin-bottom:2px;}
    .pch-guru strong{font-weight:700;}
    .pch-kelas{font-size:13px;color:#374151;margin-bottom:2px;}
    .pch-kelas strong{font-weight:700;}
    .pch-period{font-size:12px;color:#6b7280;margin-bottom:12px;}
    .pch-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;}
    .pch-stat{border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;}
    .pch-stat-label{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;margin-bottom:3px;}
    .pch-stat-val{font-size:14px;font-weight:700;color:#111827;}
    .chart-panel{break-inside:avoid;border:none;box-shadow:none;page-break-inside:avoid;}
    .lap-table-wrap{border:1px solid #ccc;break-inside:auto;}
    .lap-table th{background:#f3f4f6 !important;}
    .line-chart-svg .line-stroke{stroke:#6366f1 !important;}
    .line-chart-svg .area-fill{fill:#6366f1 !important;opacity:.1;}
    .line-chart-svg .dot{fill:#6366f1 !important;}
    .line-chart-svg .dot.validated{fill:#22c55e !important;}
    .line-chart-svg .grid-line{stroke:#e5e7eb !important;}
    .metric-bar span{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
    .activity-bar-fill{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
    .activity-bar-wrap{background:#f1f5f9 !important;}
    a[href]{color:#000 !important;text-decoration:none !important;}
    /* Student grafik mode: hide class chart + table, show student chart */
    .student-chart-print{display:none !important;}
    .student-chart-print.active{display:block !important;page-break-inside:avoid;margin-top:0;}
    .student-chart-print.active .print-kop{display:block !important;text-align:center;margin-bottom:14px;}
    .student-chart-print.active .print-kop img{max-width:100%;height:auto;}
    body.print-student .chart-panel.class-chart,
    body.print-student .lap-table-wrap{display:none !important;}
    body.print-student .print-kop:not(.student-chart-print .print-kop){display:none !important;}
    body.print-student .print-class-header{display:none !important;}
  }
  </style>
</head>
<body>
<div class="layout">

  <button class="sidebar-toggle" id="sidebarToggle" title="Menu">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  </button>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <!-- ─── Sidebar ─────────────────────────────────────────────────────── -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <div class="logo-box">
          <svg viewBox="0 0 40 40" fill="none">
            <rect width="40" height="40" rx="10" fill="url(#lgG)"/>
            <path d="M10 20L17 13L24 20L17 27L10 20Z" fill="white"/>
            <path d="M18 20L25 13L32 20L25 27L18 20Z" fill="white" fill-opacity=".6"/>
            <defs><linearGradient id="lgG" x1="0" y1="0" x2="40" y2="40"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs>
          </svg>
        </div>
        <div class="logo-text"><span class="logo-title">KAIH</span><span class="logo-sub">Admin Panel</span></div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Menu Utama</div>
      <a href="dashboard.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/></svg>
        <span>Dashboard</span>
      </a>
      <a href="siswa.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span>Data Siswa</span>
      </a>
      <a href="guru.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
        <span>Data Guru</span>
      </a>
      <a href="kelas.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/><polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2"/></svg>
        <span>Data Kelas</span>
      </a>
      <a href="users.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span>Akun &amp; User</span>
      </a>
      <a href="foto.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="2"/><path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Foto Slideshow</span>
      </a>
      <div class="nav-section-label">Laporan</div>
      <a href="laporan.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/><polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2"/><line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span>Laporan Siswa</span>
      </a>
      <a href="laporan-guru.php" class="nav-item active">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="2"/><rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="2"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Laporan Guru</span>
      </a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
        <div class="user-detail">
          <span class="user-name"><?= htmlspecialchars($username) ?></span>
          <span class="user-role">Administrator</span>
        </div>
      </div>
      <a href="logout.php" class="btn-logout" title="Keluar">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
    </div>
  </aside>

  <!-- ─── Main ─────────────────────────────────────────────────────────── -->
  <main class="main-content">

    <header class="topbar">
      <div class="topbar-left">
        <h1 class="page-title">Laporan Guru</h1>
        <p class="page-sub">Validasi kegiatan harian &amp; rekap semester per kelas / guru</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-date">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/><line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/><line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/></svg>
          <?= date('d M Y') ?>
        </div>
        <div class="admin-chip"><div class="admin-dot"></div>Admin</div>
      </div>
    </header>

    <div class="content-area">

      <!-- ── Tabs ── -->
      <div class="lap-tabs">
        <a href="?tab=validasi<?= $filterGuruId > 0 ? '&guru_id='.$filterGuruId : '' ?><?= $filterKelas !== '' ? '&kelas='.urlencode($filterKelas) : '' ?>&tanggal=<?= urlencode($filterTanggal) ?>"
           class="lap-tab <?= $tab === 'validasi' ? 'active' : '' ?>">
          ✅ Validasi Guru
        </a>
        <a href="?tab=rekap<?= $filterGuruId > 0 ? '&guru_id='.$filterGuruId : '' ?><?= $filterKelas !== '' ? '&kelas='.urlencode($filterKelas) : '' ?>&year=<?= $year ?>&semester=<?= $semester ?>"
           class="lap-tab <?= $tab === 'rekap' ? 'active' : '' ?>">
          📊 Rekap &amp; Cetak
        </a>
      </div>

      <?php if ($flash !== ''): ?>
      <div style="background:rgba(34,197,94,.12);border:1.5px solid rgba(34,197,94,.3);border-radius:10px;padding:12px 18px;font-size:13px;font-weight:600;color:#16a34a;margin-bottom:16px;">✓ <?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>

      <?php if ($tab === 'validasi'): ?>
      <!-- ════════════════════════════════════════════════════════════
           TAB: VALIDASI GURU
           ════════════════════════════════════════════════════════════ -->

      <form method="get" action="" class="lap-filter-card">
        <input type="hidden" name="tab" value="validasi">
        <div class="lfg">
          <label>Pilih Guru / Wali Kelas</label>
          <select name="guru_id" id="selGuruV">
            <option value="">— Pilih Guru —</option>
            <?php foreach ($allGuru as $g): ?>
            <option value="<?= (int)$g['id'] ?>"
              <?= (int)$g['id'] === $filterGuruId ? 'selected' : '' ?>
              data-kelas="<?= htmlspecialchars($g['wali_kelas'] ?? '', ENT_QUOTES) ?>">
              <?= htmlspecialchars($g['nama_guru']) ?>
              <?php if (!empty($g['wali_kelas'])): ?>(<?= htmlspecialchars($g['wali_kelas']) ?>)<?php endif; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lfg">
          <label>Kelas</label>
          <select name="kelas" id="selKelasV">
            <option value="">— Pilih Kelas —</option>
            <?php foreach ($allKelas as $k): ?>
            <option value="<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>"
              <?= $k['nama_kelas'] === $filterKelas ? 'selected' : '' ?>>
              <?= htmlspecialchars($k['nama_kelas']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lfg">
          <label>Tanggal</label>
          <input type="date" name="tanggal" value="<?= htmlspecialchars($filterTanggal) ?>">
        </div>
        <button type="submit" class="lap-filter-btn">Tampilkan</button>
      </form>

      <?php if ($filterGuruId > 0 && $guruNama !== ''): ?>
      <div class="lap-context">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        Guru: <strong><?= htmlspecialchars($guruNama) ?></strong>
        <?php if ($guruWaliKelas !== ''): ?>&mdash; Wali Kelas: <strong><?= htmlspecialchars($guruWaliKelas) ?></strong><?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($filterKelas !== ''): ?>

        <div class="lap-stats">
          <div class="lap-stat"><div class="lap-stat-label">Total Siswa</div><div class="lap-stat-val"><?= $valTotal ?></div></div>
          <div class="lap-stat"><div class="lap-stat-label">Sudah Melapor</div><div class="lap-stat-val green"><?= $valTerkirim ?></div></div>
          <div class="lap-stat"><div class="lap-stat-label">Belum Melapor</div><div class="lap-stat-val red"><?= $valBelum ?></div></div>
          <div class="lap-stat"><div class="lap-stat-label">Sudah Divalidasi</div><div class="lap-stat-val purple"><?= $valValid ?></div></div>
          <div class="lap-stat"><div class="lap-stat-label">Belum Divalidasi</div><div class="lap-stat-val yellow"><?= $valTerkirim - $valValid ?></div></div>
        </div>

        <?php if (empty($validasiData)): ?>
        <div class="lap-empty"><strong>Tidak ada siswa di kelas ini.</strong></div>

        <?php else: ?>

          <?php if (!empty($valSudahKirim)): ?>
          <div class="group-head"><span style="color:var(--success)">✅</span> Sudah Melapor &mdash; <?= count($valSudahKirim) ?> siswa</div>
          <div class="lap-table-wrap">
            <table class="lap-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Nama Siswa</th>
                  <th>NISN</th>
                  <th>Kegiatan</th>
                  <th>Status Validasi</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($valSudahKirim as $i => $v):
                  $score = (int)$v['bangun'] + (int)$v['ibadah'] + (int)$v['olahraga']
                         + (int)$v['sarapan'] + (int)$v['membaca'] + (int)$v['membantu'] + (int)$v['menabung'];
                  $ortu  = !empty($v['orang_tua_validated_at']);
                  $guru  = !empty($v['guru_validated_at']);
                  $validated = $ortu || $guru;
                  if ($ortu && $guru)      $valStatusLabel = '✓ Divalidasi Ortu &amp; Guru';
                  elseif ($ortu)           $valStatusLabel = '✓ Divalidasi Orang Tua';
                  elseif ($guru)           $valStatusLabel = '✓ Divalidasi Guru';
                  else                     $valStatusLabel = 'Belum Divalidasi';
                ?>
                <tr>
                  <td style="color:var(--muted)"><?= $i + 1 ?></td>
                  <td style="font-weight:600"><?= htmlspecialchars($v['nama_siswa']) ?></td>
                  <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($v['nisn'] ?? '—') ?></td>
                  <td><span class="pill-kelas"><?= $score ?>/7</span></td>
                  <td><?= $validated ? lgPill($valStatusLabel,'valid') : lgPill($valStatusLabel,'pending') ?></td>
                  <td>
                    <?php if (!$validated): ?>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="action" value="validate_guru">
                      <input type="hidden" name="laporan_id" value="<?= (int)$v['laporan_id'] ?>">
                      <input type="hidden" name="kelas" value="<?= htmlspecialchars($filterKelas, ENT_QUOTES) ?>">
                      <input type="hidden" name="guru_id" value="<?= $filterGuruId ?>">
                      <input type="hidden" name="tanggal" value="<?= htmlspecialchars($filterTanggal, ENT_QUOTES) ?>">
                      <button type="submit" class="lap-filter-btn" style="padding:6px 14px;font-size:12px;">✓ Validasi</button>
                    </form>
                    <?php else: ?>
                    <span style="font-size:12px;color:var(--muted);">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>

          <?php if (!empty($valBelumKirim)): ?>
          <div class="group-head"><span style="color:var(--warning,#d97706)">⏳</span> Belum Melapor &mdash; <?= count($valBelumKirim) ?> siswa</div>
          <div class="lap-table-wrap">
            <table class="lap-table">
              <thead>
                <tr><th>#</th><th>Nama Siswa</th><th>NISN</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($valBelumKirim as $i => $v): ?>
                <tr>
                  <td style="color:var(--muted)"><?= $i + 1 ?></td>
                  <td style="font-weight:600"><?= htmlspecialchars($v['nama_siswa']) ?></td>
                  <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($v['nisn'] ?? '—') ?></td>
                  <td><?= lgPill('Belum Melapor','notsent') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>

        <?php endif; // validasiData ?>

      <?php else: ?>
        <div class="lap-prompt">
          <strong>Pilih Guru atau Kelas</strong>
          Gunakan filter di atas untuk memilih guru (kelas wali akan otomatis terisi), lalu klik <em>Tampilkan</em>.
        </div>
      <?php endif; ?>

      <?php else: ?>
      <!-- ════════════════════════════════════════════════════════════
           TAB: REKAP & CETAK
           ════════════════════════════════════════════════════════════ -->

      <form method="get" action="" class="lap-filter-card">
        <input type="hidden" name="tab" value="rekap">
        <div class="lfg">
          <label>Pilih Guru / Wali Kelas</label>
          <select name="guru_id" id="selGuruR">
            <option value="">— Pilih Guru —</option>
            <?php foreach ($allGuru as $g): ?>
            <option value="<?= (int)$g['id'] ?>"
              <?= (int)$g['id'] === $filterGuruId ? 'selected' : '' ?>
              data-kelas="<?= htmlspecialchars($g['wali_kelas'] ?? '', ENT_QUOTES) ?>">
              <?= htmlspecialchars($g['nama_guru']) ?>
              <?php if (!empty($g['wali_kelas'])): ?>(<?= htmlspecialchars($g['wali_kelas']) ?>)<?php endif; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lfg">
          <label>Kelas</label>
          <select name="kelas" id="selKelasR">
            <option value="">— Pilih Kelas —</option>
            <?php foreach ($allKelas as $k): ?>
            <option value="<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>"
              <?= $k['nama_kelas'] === $filterKelas ? 'selected' : '' ?>>
              <?= htmlspecialchars($k['nama_kelas']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lfg" style="min-width:100px;">
          <label>Tahun</label>
          <select name="year">
            <?php for ($yy = (int)date('Y') - 1; $yy <= (int)date('Y') + 1; $yy++): ?>
            <option value="<?= $yy ?>" <?= $yy === $year ? 'selected' : '' ?>><?= $yy ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="lfg" style="min-width:160px;">
          <label>Semester</label>
          <select name="semester">
            <option value="1" <?= $semester === 1 ? 'selected' : '' ?>>Semester 1 (Jan–Jun)</option>
            <option value="2" <?= $semester === 2 ? 'selected' : '' ?>>Semester 2 (Jul–Des)</option>
          </select>
        </div>
        <button type="submit" class="lap-filter-btn">Tampilkan</button>
        <?php if ($rekKelas !== ''): ?>
        <a href="javascript:window.print()" class="lap-filter-btn green" style="text-decoration:none;padding:9px 22px;">🖨️ Cetak PDF</a>
        <?php endif; ?>
      </form>

      <?php if ($filterGuruId > 0 && $guruNama !== ''): ?>
      <div class="lap-context">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        Guru: <strong><?= htmlspecialchars($guruNama) ?></strong>
        <?php if ($guruWaliKelas !== ''): ?>&mdash; Kelas: <strong><?= htmlspecialchars($guruWaliKelas) ?></strong><?php endif; ?>
        &nbsp;·&nbsp; <?= htmlspecialchars($semLabel . ' ' . $year) ?>
      </div>
      <?php endif; ?>

      <?php if ($rekKelas !== ''): ?>

        <!-- Kop surat (print only) -->
        <?php if ($kopSurat !== ''): ?>
        <div class="print-kop"><img src="<?= htmlspecialchars($kopSurat) ?>" alt="Kop Surat"></div>
        <?php endif; ?>

        <!-- Print-only class header -->
        <div class="print-class-header">
          <div class="pch-guru">Guru: <strong><?= htmlspecialchars($guruNama) ?></strong></div>
          <?php if ($guruWaliKelas !== ''): ?>
          <div class="pch-kelas">&mdash; Kelas: <strong><?= htmlspecialchars($guruWaliKelas) ?></strong></div>
          <?php endif; ?>
          <div class="pch-period">&middot;&nbsp; <?= htmlspecialchars($semLabel . ' ' . $year) ?></div>
          <div class="pch-stats">
            <div class="pch-stat"><div class="pch-stat-label">Total Siswa</div><div class="pch-stat-val"><?= $totalStudents ?></div></div>
            <div class="pch-stat"><div class="pch-stat-label">Siswa Aktif</div><div class="pch-stat-val"><?= $studentsReported ?></div></div>
            <div class="pch-stat"><div class="pch-stat-label">Total Laporan</div><div class="pch-stat-val"><?= $totalSubmitted ?></div></div>
            <div class="pch-stat"><div class="pch-stat-label">Val. Ortu / Guru</div><div class="pch-stat-val"><?= $totalValidOrtu ?> / <?= $totalValidGuru ?></div></div>
            <div class="pch-stat"><div class="pch-stat-label">Rata-rata Kelas</div><div class="pch-stat-val"><?= number_format($classAvg, 1, ',', '.') ?>/7</div></div>
          </div>
        </div>

        <!-- Summary stats -->
        <div class="lap-stats">
          <div class="lap-stat"><div class="lap-stat-label">Total Siswa</div><div class="lap-stat-val"><?= $totalStudents ?></div></div>
          <div class="lap-stat"><div class="lap-stat-label">Siswa Aktif</div><div class="lap-stat-val green"><?= $studentsReported ?></div></div>
          <div class="lap-stat"><div class="lap-stat-label">Total Laporan</div><div class="lap-stat-val purple"><?= $totalSubmitted ?></div></div>
          <div class="lap-stat"><div class="lap-stat-label">Val. Ortu / Guru</div><div class="lap-stat-val" style="font-size:18px;"><?= $totalValidOrtu ?> / <?= $totalValidGuru ?></div></div>
          <div class="lap-stat"><div class="lap-stat-label">Rata-rata Kelas</div><div class="lap-stat-val yellow" style="font-size:20px;"><?= number_format($classAvg, 1, ',', '.') ?>/7</div></div>
        </div>

        <!-- Semester line chart -->
        <?php if (!empty($semesterSeries)): ?>
        <div class="chart-panel class-chart">
          <div style="font-size:13px;font-weight:700;margin-bottom:12px;">Grafik Semester — <?= htmlspecialchars($semLabel . ' ' . $year) ?></div>
          <div class="chart-legend">
            <span><span class="legend-dot"></span> Rata-rata skor</span>
            <span><span class="legend-dot val"></span> Sudah divalidasi</span>
          </div>
          <div class="line-chart-wrap">
            <?php
            $aW = 500; $aH = 200; $aPL = 30; $aPR = 10; $aPT = 15; $aPB = 28;
            $aPlotW = $aW - $aPL - $aPR;
            $aPlotH = $aH - $aPT - $aPB;
            $aN = count($semesterSeries);
            $aStep = $aN > 1 ? $aPlotW / ($aN - 1) : 0;
            $aPts = []; $aDots = [];
            foreach ($semesterSeries as $i => $p) {
                $x = $aPL + ($i * $aStep);
                $avg = (float)($p['avg'] ?? 0);
                $y = $aPT + $aPlotH - ($aPlotH * min(1, $avg / 7.0));
                $aPts[] = round($x,1).','.round($y,1);
                $aDots[] = ['x'=>$x,'y'=>$y,'avg'=>$avg,'label'=>$p['label']??'','sub'=>(int)($p['submitted']??0),'val'=>(int)($p['validated']??0)];
            }
            $aPoly = implode(' ', $aPts);
            $aArea = $aPL.','.(int)($aPT+$aPlotH).' '.$aPoly.' '.round($aPL+($aN-1)*$aStep,1).','.(int)($aPT+$aPlotH);
            ?>
            <svg viewBox="0 0 <?= $aW ?> <?= $aH ?>" preserveAspectRatio="none" class="line-chart-svg">
              <?php for ($g = 0; $g <= 4; $g++): $gy = $aPT + $aPlotH * (1 - $g/4); ?>
              <line x1="<?= $aPL ?>" y1="<?= round($gy,1) ?>" x2="<?= $aW-$aPR ?>" y2="<?= round($gy,1) ?>" class="grid-line"/>
              <text x="<?= $aPL-4 ?>" y="<?= round($gy+4,1) ?>" class="grid-text" text-anchor="end"><?= number_format($g*7/4,1) ?></text>
              <?php endfor; ?>
              <polygon points="<?= $aArea ?>" class="area-fill"/>
              <polyline points="<?= $aPoly ?>" class="line-stroke"/>
              <?php foreach ($aDots as $dp): ?>
              <circle cx="<?= round($dp['x'],1) ?>" cy="<?= round($dp['y'],1) ?>" r="5" class="dot <?= $dp['val']>0?'validated':($dp['sub']>0?'':'empty') ?>">
                <title><?= htmlspecialchars($dp['label'].' · rata-rata '.number_format($dp['avg'],1,',','.').'/7 · laporan '.$dp['sub'].' · valid '.$dp['val']) ?></title>
              </circle>
              <?php endforeach; ?>
              <?php foreach ($aDots as $dp): ?>
              <text x="<?= round($dp['x'],1) ?>" y="<?= $aH-4 ?>" class="x-label"><?= htmlspecialchars($dp['label']) ?></text>
              <?php endforeach; ?>
            </svg>
          </div>
        </div>
        <?php endif; ?>

        <!-- Per-student table -->
        <?php if (!empty($students)): ?>
        <div class="lap-table-wrap">
          <table class="lap-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Nama Siswa</th>
                <th>NISN</th>
                <th>Laporan Dikirim</th>
                <th>Val. Ortu / Guru</th>
                <th>Rata-rata</th>
                <th>Grafik</th>
                <th class="no-print">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $sid => $s):
                $agg  = $summary[$sid] ?? ['terkirim'=>0,'valid_any'=>0,'valid_ortu'=>0,'valid_guru'=>0,'score_sum'=>0];
                $avg  = (int)$agg['terkirim'] > 0 ? ((float)$agg['score_sum'] / (float)$agg['terkirim']) : 0.0;
                $pct  = max(0, min(100, (int)round(($avg / 7.0) * 100)));
              ?>
              <tr>
                <td style="color:var(--muted)"><?= array_search($sid, array_keys($students)) + 1 ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($s['nama_siswa']) ?></td>
                <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($s['nisn'] ?? '—') ?></td>
                <td><?= (int)$agg['terkirim'] ?></td>
                <td><?= (int)$agg['valid_ortu'] ?> / <?= (int)$agg['valid_guru'] ?></td>
                <td><?= number_format($avg, 2, ',', '.') ?>/7</td>
                <td>
                  <div class="metric-inline">
                    <div class="metric-bar"><span style="width:<?= $pct ?>%;"></span></div>
                    <div class="metric-value"><?= $pct ?>%</div>
                  </div>
                </td>
                <td class="no-print">
                  <a href="javascript:void(0)" onclick="cetakGrafik(<?= (int)$sid ?>)" style="font-size:12px;color:var(--primary);text-decoration:none;font-weight:600;">Cetak Grafik →</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Hidden per-student activity panels for printing -->
        <?php foreach ($students as $sid => $s):
          $sSeries = $studentSeries[$sid] ?? [];
          $sAgg    = $summary[$sid] ?? ['terkirim'=>0,'score_sum'=>0,'valid_any'=>0];
          $sAvg    = (int)$sAgg['terkirim'] > 0 ? ((float)$sAgg['score_sum'] / (float)$sAgg['terkirim']) : 0.0;
          $sAvgPct = (int)$sAgg['terkirim'] > 0 ? round(($sAvg / 7.0) * 100, 0) : 0;
        ?>
        <div class="student-chart-print" id="studentChart<?= (int)$sid ?>">

          <?php if (!empty($kopSurat)): ?>
          <div class="print-kop"><img src="<?= htmlspecialchars($kopSurat) ?>" alt="Kop Surat"></div>
          <?php endif; ?>

          <div style="text-align:center;margin-bottom:16px;">
            <div style="font-size:20px;font-weight:800;color:#111827;margin-bottom:4px;">Grafik Kegiatan Siswa</div>
            <div style="font-size:12px;color:#6b7280;line-height:1.5;">Ringkasan kegiatan siswa untuk periode <?= htmlspecialchars($semLabel) ?> &bull; <?= (int)$year ?>. Persentase menunjukkan frekuensi tiap kebiasaan dibanding total hari laporan.</div>
          </div>

          <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:18px;">
            <div style="border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;">
              <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;margin-bottom:3px;">Nama Siswa</div>
              <div style="font-size:13px;font-weight:600;color:#111827;"><?= htmlspecialchars($s['nama_siswa']) ?></div>
            </div>
            <div style="border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;">
              <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;margin-bottom:3px;">NISN</div>
              <div style="font-size:13px;font-weight:600;color:#111827;"><?= htmlspecialchars($s['nisn'] ?? '—') ?></div>
            </div>
            <div style="border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;">
              <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;margin-bottom:3px;">Periode</div>
              <div style="font-size:13px;font-weight:600;color:#111827;"><?= htmlspecialchars($semLabel) ?> &bull; <?= (int)$year ?></div>
            </div>
            <div style="border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;">
              <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;margin-bottom:3px;">Rata-rata</div>
              <div style="font-size:13px;font-weight:600;color:#111827;"><?= number_format($sAvg, 2, ',', '.') ?>/7 &bull; <?= (int)$sAvgPct ?>%</div>
            </div>
          </div>

          <div class="chart-panel" style="border:1px solid #e5e7eb;border-radius:12px;padding:16px 18px;">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;margin-bottom:4px;">Detail Kebiasaan</div>
            <div style="font-size:14px;font-weight:700;color:#111827;margin-bottom:4px;">Persentase per kegiatan</div>
            <div style="font-size:12px;color:#6b7280;margin-bottom:14px;">Batang menunjukkan seberapa sering masing-masing kegiatan dilakukan dibanding total hari laporan pada periode ini.</div>
            <ul class="activity-list">
              <?php foreach ($sSeries as $act): ?>
              <li class="activity-row">
                <span class="activity-name"><?= htmlspecialchars($act['label']) ?></span>
                <div class="activity-bar-wrap">
                  <div class="activity-bar-fill clr-<?= htmlspecialchars($act['field']) ?>" style="width:<?= $act['pct'] ?>%;"></div>
                </div>
                <span class="activity-pct"><?= number_format($act['pct'], 1, ',', '.') ?>%</span>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <?php endforeach; ?>
        <?php elseif ($rekKelas !== ''): ?>
        <div class="lap-empty"><strong>Tidak ada siswa di kelas ini.</strong></div>
        <?php endif; ?>

      <?php else: ?>
        <div class="lap-prompt">
          <strong>Pilih Guru atau Kelas &amp; Semester</strong>
          Gunakan filter di atas untuk memilih guru/kelas, tahun, dan semester, lalu klik <em>Tampilkan</em>.
        </div>
      <?php endif; ?>

      <?php endif; // tabs ?>

    </div><!-- .content-area -->
  </main>
</div><!-- .layout -->

<script>
function cetakGrafik(sid) {
  // Hide all student charts, show only the selected one
  document.querySelectorAll('.student-chart-print').forEach(function(el){ el.classList.remove('active'); });
  var panel = document.getElementById('studentChart' + sid);
  if (panel) panel.classList.add('active');
  document.body.classList.add('print-student');
  window.print();
  // Cleanup after print dialog closes
  setTimeout(function(){
    document.body.classList.remove('print-student');
    if (panel) panel.classList.remove('active');
  }, 500);
}

var guruKelasMap = <?= $guruKelasMapJson ?>;

// Validasi tab: guru → kelas
(function(){
    var g = document.getElementById('selGuruV'), k = document.getElementById('selKelasV');
    if (!g || !k) return;
    g.addEventListener('change', function(){
        var gid = this.value;
        if (gid && guruKelasMap[gid]) {
            for (var i = 0; i < k.options.length; i++) {
                if (k.options[i].value === guruKelasMap[gid]) { k.selectedIndex = i; return; }
            }
        }
    });
})();

// Rekap tab: guru → kelas
(function(){
    var g = document.getElementById('selGuruR'), k = document.getElementById('selKelasR');
    if (!g || !k) return;
    g.addEventListener('change', function(){
        var gid = this.value;
        if (gid && guruKelasMap[gid]) {
            for (var i = 0; i < k.options.length; i++) {
                if (k.options[i].value === guruKelasMap[gid]) { k.selectedIndex = i; return; }
            }
        }
    });
})();
</script>

<script>
(function(){
  var btn=document.getElementById('sidebarToggle'),sb=document.getElementById('sidebar'),bd=document.getElementById('sidebarBackdrop');
  if(!btn||!sb)return;
  function toggle(){sb.classList.toggle('open');bd.classList.toggle('active');}
  function close(){sb.classList.remove('open');bd.classList.remove('active');}
  btn.addEventListener('click',toggle);
  if(bd)bd.addEventListener('click',close);
  sb.querySelectorAll('.nav-item').forEach(function(a){a.addEventListener('click',close);});
})();
</script>

<button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Ganti tema">
  <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="2"/><path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>

</body>
</html>

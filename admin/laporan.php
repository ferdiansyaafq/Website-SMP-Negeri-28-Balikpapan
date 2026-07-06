<?php
declare(strict_types=1);

require_once '../includes/admin_auth.php';
requireAdminLogin();
require_once '../config/database.php';

$username = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');
$conn = getConnection();

// ── Filter helpers ─────────────────────────────────────────────────────────
$tab = ($_GET['tab'] ?? 'kelas') === 'siswa' ? 'siswa' : 'kelas';

$filterGuruId   = (int)($_GET['guru_id'] ?? 0);
$filterKelas    = trim((string)($_GET['kelas'] ?? ''));
$filterTanggal  = trim((string)($_GET['tanggal'] ?? date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTanggal)) {
    $filterTanggal = date('Y-m-d');
}

$filterSiswaId  = (int)($_GET['siswa_id'] ?? 0);
$filterBulan    = trim((string)($_GET['bulan'] ?? date('Y-m')));
if (!preg_match('/^\d{4}-\d{2}$/', $filterBulan)) {
    $filterBulan = date('Y-m');
}

// ── Load dropdown data ─────────────────────────────────────────────────────
$allGuru = [];
$res = $conn->query("SELECT id, nama_guru, kelas AS wali_kelas FROM guru ORDER BY nama_guru ASC");
if ($res) { while ($r = $res->fetch_assoc()) { $allGuru[] = $r; } }

$allKelas = [];
$res = $conn->query("SELECT id, nama_kelas FROM kaih_kelas ORDER BY nama_kelas ASC");
if ($res) { while ($r = $res->fetch_assoc()) { $allKelas[] = $r; } }

$allSiswa = [];
$res = $conn->query("SELECT id, nama_siswa, kelas FROM siswa ORDER BY kelas ASC, nama_siswa ASC");
if ($res) { while ($r = $res->fetch_assoc()) { $allSiswa[] = $r; } }

// ── Auto-detect kelas from guru ────────────────────────────────────────────
$guruWaliKelas = '';
$guruNama = '';
if ($filterGuruId > 0) {
    $stmtG = $conn->prepare("SELECT nama_guru, kelas FROM guru WHERE id = ? LIMIT 1");
    if ($stmtG) {
        $stmtG->bind_param('i', $filterGuruId);
        $stmtG->execute();
        $rowG = $stmtG->get_result()->fetch_assoc();
        $stmtG->close();
        if ($rowG) {
            $guruWaliKelas = (string)($rowG['kelas'] ?? '');
            $guruNama = (string)($rowG['nama_guru'] ?? '');
            if ($filterKelas === '') {
                $filterKelas = $guruWaliKelas;
            }
        }
    }
}

// ── Tab Kelas: load data ───────────────────────────────────────────────────
$kelasData = [];
$statTotal = $statTerkirim = $statBelum = $statValid = 0;

if ($tab === 'kelas' && $filterKelas !== '') {
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
        while ($r = $res->fetch_assoc()) { $kelasData[] = $r; }
        $stmt->close();
    }
    $statTotal   = count($kelasData);
    foreach ($kelasData as $it) {
        $sent = !empty($it['laporan_id']);
        if ($sent) $statTerkirim++; else $statBelum++;
        if ($sent && (!empty($it['orang_tua_validated_at']) || !empty($it['guru_validated_at']))) $statValid++;
    }
}

// ── Tab Siswa: load data ───────────────────────────────────────────────────
$siswaInfo    = null;
$siswaLaporan = [];
$statSiswaTotal = $statSiswaDays = 0;

if ($tab === 'siswa' && $filterSiswaId > 0) {
    $stmtS = $conn->prepare("SELECT id, nisn, nama_siswa, kelas FROM siswa WHERE id = ? LIMIT 1");
    if ($stmtS) {
        $stmtS->bind_param('i', $filterSiswaId);
        $stmtS->execute();
        $siswaInfo = $stmtS->get_result()->fetch_assoc();
        $stmtS->close();
    }
    if ($siswaInfo) {
        $bd = DateTimeImmutable::createFromFormat('Y-m', $filterBulan) ?: new DateTimeImmutable();
        $startYmd = $bd->modify('first day of this month')->format('Y-m-d');
        $endYmd   = $bd->modify('last day of this month')->format('Y-m-d');
        $stmtL = $conn->prepare(
            'SELECT lh.id, lh.tanggal,
                    lh.bangun, lh.ibadah, lh.ibadah_catatan,
                    lh.olahraga, lh.olahraga_jenis,
                    lh.sarapan, lh.sarapan_menu,
                    lh.membaca, lh.membaca_judul, lh.membaca_menit,
                    lh.membantu, lh.membantu_jenis,
                    lh.menabung, lh.menabung_nominal,
                    lh.orang_tua_validated_at, lh.guru_validated_at,
                    lh.created_at
             FROM laporan_harian lh
             WHERE lh.siswa_id = ? AND lh.tanggal BETWEEN ? AND ?
             ORDER BY lh.tanggal DESC'
        );
        if ($stmtL) {
            $stmtL->bind_param('iss', $filterSiswaId, $startYmd, $endYmd);
            $stmtL->execute();
            $res = $stmtL->get_result();
            while ($r = $res->fetch_assoc()) { $siswaLaporan[] = $r; }
            $stmtL->close();
        }
        $statSiswaDays = count($siswaLaporan);
        // Max hari kerja in month = days in month
        $daysInMonth = (int)(DateTimeImmutable::createFromFormat('Y-m', $filterBulan) ?: new DateTimeImmutable())->format('t');
        $statSiswaTotal = $daysInMonth;
    }
}

$conn->close();

// ── JS maps ────────────────────────────────────────────────────────────────
$guruKelasMapJson = json_encode(
    array_column($allGuru, 'wali_kelas', 'id'),
    JSON_UNESCAPED_UNICODE
);
$siswaByKelasJson = json_encode(
    array_reduce($allSiswa, function (array $carry, array $s): array {
        $k = $s['kelas'] ?? '';
        $carry[$k][] = ['id' => $s['id'], 'nama' => $s['nama_siswa']];
        return $carry;
    }, []),
    JSON_UNESCAPED_UNICODE
);

// ── Helpers ────────────────────────────────────────────────────────────────
function ik(bool $on): string {
    return $on ? '<span class="ik on">✓</span>' : '<span class="ik off">—</span>';
}
function lapPill(string $text, string $type): string {
    return '<span class="pill-' . htmlspecialchars($type, ENT_QUOTES) . '">' . htmlspecialchars($text) . '</span>';
}
$bulanNames = [
    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
    7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember',
];
function fmtDate(string $ymd): string {
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);
    if (!$d) return $ymd;
    $bulan = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
              7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
    return (int)$d->format('j') . ' ' . ($bulan[(int)$d->format('n')] ?? $d->format('m')) . ' ' . $d->format('Y');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <script>(function(){try{var t=localStorage.getItem('kaih_theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();function toggleTheme(){var h=document.documentElement,d=h.getAttribute('data-theme')==='dark';if(d)h.removeAttribute('data-theme');else h.setAttribute('data-theme','dark');try{localStorage.setItem('kaih_theme',d?'light':'dark');}catch(e){}}</script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan Siswa — KAIH Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="../assets/css/siswa.css">
  <style>
  /* ── Laporan page ── */
  .lap-tabs{display:flex;gap:6px;margin-bottom:24px;}
  .lap-tab{padding:8px 22px;border-radius:8px;border:1.5px solid var(--border);background:var(--surface);font-size:13px;font-weight:600;color:var(--muted);text-decoration:none;transition:.15s;}
  .lap-tab.active{background:var(--primary);border-color:var(--primary);color:#fff;}
  .lap-tab:hover:not(.active){border-color:var(--primary);color:var(--primary);}

  .lap-filter-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;}
  .lfg{display:flex;flex-direction:column;gap:5px;min-width:160px;}
  .lfg label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
  .lfg select,.lfg input[type=date],.lfg input[type=month]{padding:8px 10px;border-radius:8px;border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;font-family:inherit;outline:none;cursor:pointer;}
  .lfg select:focus,.lfg input:focus{border-color:var(--primary);}
  .lap-filter-btn{padding:9px 22px;border:none;border-radius:8px;background:var(--primary);color:#fff;font-size:13px;font-weight:600;cursor:pointer;align-self:flex-end;flex-shrink:0;}
  .lap-filter-btn:hover{opacity:.88;}

  .lap-context{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;background:var(--primary-soft);border-radius:10px;font-size:13px;font-weight:500;color:var(--primary);margin-bottom:18px;}

  .lap-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:20px;}
  .lap-stat{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:14px 16px;}
  .lap-stat-label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;}
  .lap-stat-val{font-size:28px;font-weight:800;line-height:1.1;}
  .lap-stat-val.green{color:var(--success);}
  .lap-stat-val.red{color:var(--danger);}
  .lap-stat-val.purple{color:var(--primary);}
  .lap-stat-val.yellow{color:var(--warning);}

  .lap-table-wrap{overflow-x:auto;border-radius:12px;border:1px solid var(--border);}
  .lap-table{width:100%;border-collapse:collapse;font-size:13px;}
  .lap-table th{background:var(--surface-2);padding:10px 14px;text-align:left;font-weight:600;font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);white-space:nowrap;}
  .lap-table td{padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:middle;}
  .lap-table tr:last-child td{border-bottom:none;}
  .lap-table tr:hover td{background:var(--primary-soft);}
  .lap-table td.acts{display:flex;gap:4px;flex-wrap:wrap;}

  .pill-sent{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(34,197,94,.12);color:#16a34a;}
  .pill-notsent{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(239,68,68,.10);color:#dc2626;}
  .pill-valid{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(99,102,241,.12);color:#6366f1;}
  .pill-pending{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(245,158,11,.12);color:#d97706;}
  .pill-kelas{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:var(--primary-soft);color:var(--primary);}

  .ik{font-size:13px;}
  .ik.on{color:var(--success);}
  .ik.off{color:var(--border);opacity:.5;}

  /* Detail siswa (collapsible days) */
  .siswa-info-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:16px 20px;margin-bottom:18px;display:flex;align-items:center;gap:14px;}
  .siswa-avatar{width:46px;height:46px;border-radius:50%;background:var(--primary);display:grid;place-items:center;font-size:18px;font-weight:800;color:#fff;flex-shrink:0;}
  .siswa-info-name{font-weight:700;font-size:16px;}
  .siswa-info-sub{font-size:12px;color:var(--muted);}

  .lap-day{background:var(--surface);border:1px solid var(--border);border-radius:12px;margin-bottom:10px;overflow:hidden;}
  .lap-day-head{display:flex;align-items:center;justify-content:space-between;padding:13px 18px;cursor:pointer;gap:10px;user-select:none;}
  .lap-day-head:hover{background:var(--primary-soft);}
  .lap-day-date{font-weight:700;font-size:14px;}
  .lap-day-badges{display:flex;gap:6px;flex-wrap:wrap;align-items:center;}
  .lap-day-chevron{font-size:16px;color:var(--muted);transition:transform .2s;flex-shrink:0;}
  .lap-day-chevron.open{transform:rotate(180deg);}
  .lap-day-body{display:none;padding:0 18px 16px;border-top:1px solid var(--border);}
  .lap-day-body.open{display:block;}

  .kg{display:grid;grid-template-columns:repeat(auto-fill,minmax(195px,1fr));gap:10px;margin-top:14px;}
  .ki{background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:10px 14px;}
  .ki-name{font-size:10px;font-weight:700;color:var(--muted);margin-bottom:4px;text-transform:uppercase;letter-spacing:.4px;}
  .ki-val{font-size:13px;font-weight:600;}
  .ki-val.done{color:var(--success);}
  .ki-val.skip{color:var(--muted);}
  .ki-sub{font-size:11px;color:var(--muted);margin-top:3px;}

  .val-row{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;}

  .lap-empty{text-align:center;padding:48px 20px;color:var(--muted);font-size:14px;}
  .lap-empty strong{display:block;font-size:16px;margin-bottom:6px;color:var(--text);}
  .lap-prompt{text-align:center;padding:40px 20px;color:var(--muted);font-size:14px;background:var(--surface);border:1px solid var(--border);border-radius:12px;}
  .lap-prompt strong{display:block;font-size:15px;color:var(--text);margin-bottom:6px;}
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
            <rect width="40" height="40" rx="10" fill="url(#lgLap)"/>
            <path d="M10 20L17 13L24 20L17 27L10 20Z" fill="white"/>
            <path d="M18 20L25 13L32 20L25 27L18 20Z" fill="white" fill-opacity=".6"/>
            <defs><linearGradient id="lgLap" x1="0" y1="0" x2="40" y2="40"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs>
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
      <a href="laporan.php" class="nav-item active">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2"/><line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="10 9 9 9 8 9" stroke="currentColor" stroke-width="2"/></svg>
        <span>Laporan Siswa</span>
      </a>
      <a href="laporan-guru.php" class="nav-item">
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
        <h1 class="page-title">Laporan Siswa</h1>
        <p class="page-sub">Monitor kegiatan harian &amp; validasi per kelas atau per siswa</p>
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
        <a href="?tab=kelas<?= $filterKelas !== '' ? '&kelas='.urlencode($filterKelas) : '' ?>&tanggal=<?= urlencode($filterTanggal) ?>"
           class="lap-tab <?= $tab === 'kelas' ? 'active' : '' ?>">
          📋 Laporan Kelas
        </a>
        <a href="?tab=siswa<?= $filterKelas !== '' ? '&kelas='.urlencode($filterKelas) : '' ?><?= $filterSiswaId > 0 ? '&siswa_id='.$filterSiswaId : '' ?>&bulan=<?= urlencode($filterBulan) ?>"
           class="lap-tab <?= $tab === 'siswa' ? 'active' : '' ?>">
          👤 Detail Siswa
        </a>
      </div>

      <?php if ($tab === 'kelas'): ?>
      <!-- ════════════════════════════════════════════════════════════
           TAB: LAPORAN KELAS
           ════════════════════════════════════════════════════════════ -->

      <form method="get" action="" class="lap-filter-card">
        <input type="hidden" name="tab" value="kelas">

        <!-- Pilih Guru -->
        <div class="lfg">
          <label>Pilih Guru / Wali Kelas</label>
          <select name="guru_id" id="selGuru">
            <option value="">— Semua Guru —</option>
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

        <!-- Pilih Kelas -->
        <div class="lfg">
          <label>Kelas</label>
          <select name="kelas" id="selKelas">
            <option value="">— Pilih Kelas —</option>
            <?php foreach ($allKelas as $k): ?>
            <option value="<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>"
              <?= $k['nama_kelas'] === $filterKelas ? 'selected' : '' ?>>
              <?= htmlspecialchars($k['nama_kelas']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Tanggal -->
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
        <?php if ($guruWaliKelas !== ''): ?>
          &mdash; Wali Kelas: <strong><?= htmlspecialchars($guruWaliKelas) ?></strong>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($filterKelas !== ''): ?>

        <!-- Stats -->
        <div class="lap-stats">
          <div class="lap-stat">
            <div class="lap-stat-label">Total Siswa</div>
            <div class="lap-stat-val"><?= $statTotal ?></div>
          </div>
          <div class="lap-stat">
            <div class="lap-stat-label">Sudah Melapor</div>
            <div class="lap-stat-val green"><?= $statTerkirim ?></div>
          </div>
          <div class="lap-stat">
            <div class="lap-stat-label">Belum Melapor</div>
            <div class="lap-stat-val red"><?= $statBelum ?></div>
          </div>
          <div class="lap-stat">
            <div class="lap-stat-label">Sudah Divalidasi</div>
            <div class="lap-stat-val purple"><?= $statValid ?></div>
          </div>
          <div class="lap-stat">
            <div class="lap-stat-label">Belum Divalidasi</div>
            <div class="lap-stat-val yellow"><?= $statTerkirim - $statValid ?></div>
          </div>
        </div>

        <?php if (empty($kelasData)): ?>
        <div class="lap-empty"><strong>Tidak ada siswa di kelas ini.</strong>Belum ada data siswa untuk kelas <?= htmlspecialchars($filterKelas) ?>.</div>
        <?php else: ?>
        <div class="lap-table-wrap">
          <table class="lap-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Nama Siswa</th>
                <th>NISN</th>
                <th>Status</th>
                <th>Bangun</th>
                <th>Ibadah</th>
                <th>Olahraga</th>
                <th>Sarapan</th>
                <th>Membaca</th>
                <th>Membantu</th>
                <th>Menabung</th>
                <th>Val. Ortu</th>
                <th>Val. Guru</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($kelasData as $i => $it):
                $sent = !empty($it['laporan_id']);
                $ortu = $sent && !empty($it['orang_tua_validated_at']);
                $guru = $sent && !empty($it['guru_validated_at']);
              ?>
              <tr>
                <td style="color:var(--muted)"><?= $i + 1 ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($it['nama_siswa']) ?></td>
                <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($it['nisn'] ?? '—') ?></td>
                <td><?= $sent ? lapPill('Terkirim','sent') : lapPill('Belum','notsent') ?></td>
                <?php if ($sent): ?>
                <td><?= ik((bool)$it['bangun']) ?></td>
                <td><?= ik((bool)$it['ibadah']) ?></td>
                <td><?= ik((bool)$it['olahraga']) ?></td>
                <td><?= ik((bool)$it['sarapan']) ?></td>
                <td><?= ik((bool)$it['membaca']) ?></td>
                <td><?= ik((bool)$it['membantu']) ?></td>
                <td><?= ik((bool)$it['menabung']) ?></td>
                <?php else: ?>
                <td colspan="7" style="color:var(--muted);font-size:12px;text-align:center">—</td>
                <?php endif; ?>
                <td><?= $ortu ? lapPill('✓ Ortu','valid') : lapPill('Belum','pending') ?></td>
                <td><?= $guru ? lapPill('✓ Guru','valid') : lapPill('Belum','pending') ?></td>
                <td>
                  <a href="?tab=siswa&kelas=<?= urlencode($it['kelas'] ?? '') ?>&siswa_id=<?= (int)$it['siswa_id'] ?>&bulan=<?= urlencode(substr($filterTanggal, 0, 7)) ?>"
                     style="font-size:12px;color:var(--primary);text-decoration:none;font-weight:600;">
                    Detail →
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="lap-prompt">
          <strong>Pilih Guru atau Kelas</strong>
          Gunakan filter di atas untuk memilih guru (kelas wali akan otomatis terisi) atau pilih kelas langsung, kemudian klik <em>Tampilkan</em>.
        </div>
      <?php endif; ?>

      <?php elseif ($tab === 'validasi'): ?>
      <!-- ════════════════════════════════════════════════════════════
           TAB: VALIDASI GURU
           ════════════════════════════════════════════════════════════ -->

      <form method="get" action="" class="lap-filter-card">
        <input type="hidden" name="tab" value="validasi">
        <div class="lfg">
          <label>Pilih Guru / Wali Kelas</label>
          <select name="guru_id" id="selGuruV">
            <option value="">— Semua / Manual —</option>
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
        <?php if ($guruWaliKelas !== ''): ?>
          &mdash; Wali Kelas: <strong><?= htmlspecialchars($guruWaliKelas) ?></strong>
        <?php endif; ?>
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
        <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:10px;padding:10px 14px;background:var(--surface);border:1px solid var(--border);border-radius:10px;display:flex;align-items:center;gap:8px;">
          <span style="color:var(--success)">✅</span> Sudah Melapor — <?= count($valSudahKirim) ?> siswa
        </div>
        <div class="lap-table-wrap" style="margin-bottom:20px;">
          <table class="lap-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Nama Siswa</th>
                <th>NISN</th>
                <th>Kegiatan</th>
                <th>Val. Ortu</th>
                <th>Val. Guru</th>
                <th>Aksi Validasi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($valSudahKirim as $i => $v):
                $score = (int)$v['bangun'] + (int)$v['ibadah'] + (int)$v['olahraga']
                       + (int)$v['sarapan'] + (int)$v['membaca'] + (int)$v['membantu'] + (int)$v['menabung'];
                $ortu  = !empty($v['orang_tua_validated_at']);
                $guru  = !empty($v['guru_validated_at']);
              ?>
              <tr>
                <td style="color:var(--muted)"><?= $i + 1 ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($v['nama_siswa']) ?></td>
                <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($v['nisn'] ?? '—') ?></td>
                <td><span class="pill-kelas"><?= $score ?>/7</span></td>
                <td><?= $ortu ? lapPill('✓ Ortu','valid') : lapPill('Belum','pending') ?></td>
                <td><?= $guru ? lapPill('✓ Guru','valid') : lapPill('Belum','pending') ?></td>
                <td>
                  <?php if (!$guru): ?>
                  <form method="post" style="display:inline">
                    <input type="hidden" name="action" value="validate_guru">
                    <input type="hidden" name="laporan_id" value="<?= (int)$v['laporan_id'] ?>">
                    <input type="hidden" name="kelas" value="<?= htmlspecialchars($filterKelas, ENT_QUOTES) ?>">
                    <input type="hidden" name="guru_id" value="<?= $filterGuruId ?>">
                    <input type="hidden" name="tanggal" value="<?= htmlspecialchars($filterTanggal, ENT_QUOTES) ?>">
                    <button type="submit" class="lap-filter-btn" style="padding:6px 14px;font-size:12px;">✓ Validasi Guru</button>
                  </form>
                  <?php else: ?>
                  <span style="font-size:12px;color:var(--success);font-weight:600;">Sudah Divalidasi ✓</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <?php if (!empty($valBelumKirim)): ?>
        <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:10px;padding:10px 14px;background:var(--surface);border:1px solid var(--border);border-radius:10px;display:flex;align-items:center;gap:8px;">
          <span style="color:var(--warning,#d97706)">⏳</span> Belum Melapor — <?= count($valBelumKirim) ?> siswa
        </div>
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
                <td><?= lapPill('Belum Melapor','notsent') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <?php if (empty($valSudahKirim) && empty($valBelumKirim)): ?>
        <div class="lap-empty"><strong>Tidak ada data siswa.</strong></div>
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
           TAB: DETAIL SISWA
           ════════════════════════════════════════════════════════════ -->

      <form method="get" action="" class="lap-filter-card">
        <input type="hidden" name="tab" value="siswa">

        <!-- Pilih Kelas -->
        <div class="lfg">
          <label>Kelas</label>
          <select name="kelas" id="selKelasS" onchange="filterSiswaByKelas(this.value)">
            <option value="">— Pilih Kelas —</option>
            <?php foreach ($allKelas as $k): ?>
            <option value="<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>"
              <?= $k['nama_kelas'] === $filterKelas ? 'selected' : '' ?>>
              <?= htmlspecialchars($k['nama_kelas']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Pilih Siswa -->
        <div class="lfg">
          <label>Siswa</label>
          <select name="siswa_id" id="selSiswa">
            <option value="">— Pilih Siswa —</option>
            <?php foreach ($allSiswa as $s): ?>
            <option value="<?= (int)$s['id'] ?>"
              class="opt-siswa"
              data-kelas="<?= htmlspecialchars($s['kelas'] ?? '', ENT_QUOTES) ?>"
              <?= (int)$s['id'] === $filterSiswaId ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['nama_siswa']) ?>
              <?php if (!empty($s['kelas'])): ?>(<?= htmlspecialchars($s['kelas']) ?>)<?php endif; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Bulan -->
        <div class="lfg">
          <label>Bulan</label>
          <input type="month" name="bulan" value="<?= htmlspecialchars($filterBulan) ?>">
        </div>

        <button type="submit" class="lap-filter-btn">Tampilkan</button>
      </form>

      <?php if ($filterSiswaId > 0 && $siswaInfo): ?>

        <!-- Siswa info card -->
        <div class="siswa-info-card">
          <div class="siswa-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($siswaInfo['nama_siswa'], 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
          <div>
            <div class="siswa-info-name"><?= htmlspecialchars($siswaInfo['nama_siswa']) ?></div>
            <div class="siswa-info-sub">
              NISN: <?= htmlspecialchars($siswaInfo['nisn'] ?? '—') ?> &nbsp;·&nbsp;
              <span class="pill-kelas"><?= htmlspecialchars($siswaInfo['kelas'] ?? '—') ?></span>
              &nbsp;·&nbsp;
              <?=
                (function() use ($filterBulan, $bulanNames): string {
                    $parts = explode('-', $filterBulan);
                    $m = (int)($parts[1] ?? 0);
                    $y = $parts[0] ?? '';
                    return ($bulanNames[$m] ?? $filterBulan) . ' ' . $y;
                })()
              ?>
            </div>
          </div>
          <div style="margin-left:auto;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <div class="lap-stat" style="min-width:0;padding:10px 16px;">
              <div class="lap-stat-label" style="font-size:10px">Hari Laporan</div>
              <div class="lap-stat-val green" style="font-size:22px"><?= $statSiswaDays ?></div>
            </div>
            <div class="lap-stat" style="min-width:0;padding:10px 16px;">
              <div class="lap-stat-label" style="font-size:10px">Hari di Bulan</div>
              <div class="lap-stat-val" style="font-size:22px"><?= $statSiswaTotal ?></div>
            </div>
          </div>
        </div>

        <?php if (empty($siswaLaporan)): ?>
        <div class="lap-empty">
          <strong>Belum ada laporan.</strong>
          <?= htmlspecialchars($siswaInfo['nama_siswa']) ?> belum mengirim laporan kegiatan harian pada bulan ini.
        </div>
        <?php else: ?>

        <!-- Per-day collapsible cards -->
        <?php foreach ($siswaLaporan as $idx => $lh):
          $score  = (int)$lh['bangun'] + (int)$lh['ibadah'] + (int)$lh['olahraga']
                  + (int)$lh['sarapan'] + (int)$lh['membaca'] + (int)$lh['membantu'] + (int)$lh['menabung'];
          $ortuOk = !empty($lh['orang_tua_validated_at']);
          $guruOk = !empty($lh['guru_validated_at']);
          $dayId  = 'day-' . $idx;
        ?>
        <div class="lap-day">
          <div class="lap-day-head" onclick="toggleDay('<?= $dayId ?>')">
            <span class="lap-day-date"><?= fmtDate($lh['tanggal']) ?></span>
            <div class="lap-day-badges">
              <span class="pill-kelas"><?= $score ?>/7 kegiatan</span>
              <?= $ortuOk ? lapPill('✓ Ortu','valid') : lapPill('Ortu belum','pending') ?>
              <?= $guruOk ? lapPill('✓ Guru','valid') : lapPill('Guru belum','pending') ?>
            </div>
            <span class="lap-day-chevron" id="chv-<?= $dayId ?>">▼</span>
          </div>
          <div class="lap-day-body" id="<?= $dayId ?>">
            <div class="kg">

              <div class="ki">
                <div class="ki-name">Bangun Pagi</div>
                <div class="ki-val <?= $lh['bangun'] ? 'done' : 'skip' ?>"><?= $lh['bangun'] ? 'Sudah ✓' : 'Belum' ?></div>
              </div>

              <div class="ki">
                <div class="ki-name">Ibadah</div>
                <div class="ki-val <?= $lh['ibadah'] ? 'done' : 'skip' ?>"><?= $lh['ibadah'] ? 'Sudah ✓' : 'Belum' ?></div>
                <?php if (!empty($lh['ibadah_catatan'])): ?>
                <div class="ki-sub"><?= htmlspecialchars($lh['ibadah_catatan']) ?></div>
                <?php endif; ?>
              </div>

              <div class="ki">
                <div class="ki-name">Olahraga</div>
                <div class="ki-val <?= $lh['olahraga'] ? 'done' : 'skip' ?>"><?= $lh['olahraga'] ? 'Sudah ✓' : 'Belum' ?></div>
                <?php if (!empty($lh['olahraga_jenis'])): ?>
                <div class="ki-sub"><?= htmlspecialchars($lh['olahraga_jenis']) ?></div>
                <?php endif; ?>
              </div>

              <div class="ki">
                <div class="ki-name">Sarapan</div>
                <div class="ki-val <?= $lh['sarapan'] ? 'done' : 'skip' ?>"><?= $lh['sarapan'] ? 'Sudah ✓' : 'Belum' ?></div>
                <?php if (!empty($lh['sarapan_menu'])): ?>
                <div class="ki-sub"><?= htmlspecialchars($lh['sarapan_menu']) ?></div>
                <?php endif; ?>
              </div>

              <div class="ki">
                <div class="ki-name">Membaca</div>
                <div class="ki-val <?= $lh['membaca'] ? 'done' : 'skip' ?>"><?= $lh['membaca'] ? 'Sudah ✓' : 'Belum' ?></div>
                <?php if (!empty($lh['membaca_judul'])): ?>
                <div class="ki-sub"><?= htmlspecialchars($lh['membaca_judul']) ?><?= !empty($lh['membaca_menit']) ? ' · ' . (int)$lh['membaca_menit'] . ' mnt' : '' ?></div>
                <?php endif; ?>
              </div>

              <div class="ki">
                <div class="ki-name">Membantu</div>
                <div class="ki-val <?= $lh['membantu'] ? 'done' : 'skip' ?>"><?= $lh['membantu'] ? 'Sudah ✓' : 'Belum' ?></div>
                <?php if (!empty($lh['membantu_jenis'])): ?>
                <div class="ki-sub"><?= htmlspecialchars($lh['membantu_jenis']) ?></div>
                <?php endif; ?>
              </div>

              <div class="ki">
                <div class="ki-name">Menabung</div>
                <div class="ki-val <?= $lh['menabung'] ? 'done' : 'skip' ?>"><?= $lh['menabung'] ? 'Sudah ✓' : 'Belum' ?></div>
                <?php if (!empty($lh['menabung_nominal'])): ?>
                <div class="ki-sub">Rp <?= number_format((int)$lh['menabung_nominal'], 0, ',', '.') ?></div>
                <?php endif; ?>
              </div>

            </div><!-- .kg -->

            <div class="val-row">
              <?php if ($ortuOk): ?>
              <span class="pill-valid">✓ Divalidasi Orang Tua — <?= htmlspecialchars(date('d M Y H:i', strtotime($lh['orang_tua_validated_at']))) ?></span>
              <?php else: ?>
              <span class="pill-pending">Belum divalidasi orang tua</span>
              <?php endif; ?>
              <?php if ($guruOk): ?>
              <span class="pill-valid">✓ Divalidasi Guru — <?= htmlspecialchars(date('d M Y H:i', strtotime($lh['guru_validated_at']))) ?></span>
              <?php else: ?>
              <span class="pill-pending">Belum divalidasi guru</span>
              <?php endif; ?>
              <span style="font-size:11px;color:var(--muted);align-self:center">Dikirim: <?= htmlspecialchars(date('d M Y H:i', strtotime($lh['created_at']))) ?></span>
            </div>

          </div><!-- .lap-day-body -->
        </div><!-- .lap-day -->
        <?php endforeach; ?>

        <?php endif; // siswaLaporan empty ?>

      <?php elseif ($filterSiswaId > 0): ?>
        <div class="lap-empty"><strong>Siswa tidak ditemukan.</strong></div>
      <?php else: ?>
        <div class="lap-prompt">
          <strong>Pilih Kelas &amp; Siswa</strong>
          Pilih kelas untuk memfilter daftar siswa, pilih nama siswa, lalu pilih bulan dan klik <em>Tampilkan</em>.
        </div>
      <?php endif; ?>

      <?php endif; // tabs ?>

    </div><!-- .content-area -->
  </main>
</div><!-- .layout -->

<script>
// ── Guru → auto-fill kelas (Tab Kelas) ──────────────────────────────────
var guruKelasMap = <?= $guruKelasMapJson ?>;

(function(){
    var selGuru = document.getElementById('selGuru');
    var selKelas = document.getElementById('selKelas');
    if (!selGuru || !selKelas) return;
    selGuru.addEventListener('change', function(){
        var gid = this.value;
        if (gid && guruKelasMap[gid]) {
            // Find matching option in kelas select
            var opts = selKelas.options;
            for (var i = 0; i < opts.length; i++) {
                if (opts[i].value === guruKelasMap[gid]) {
                    selKelas.selectedIndex = i;
                    return;
                }
            }
        }
        // If no match or blank, don't reset kelas
    });
})();

// ── Guru → auto-fill kelas (Tab Validasi) ───────────────────────────────
(function(){
    var selGuruV  = document.getElementById('selGuruV');
    var selKelasV = document.getElementById('selKelasV');
    if (!selGuruV || !selKelasV) return;
    selGuruV.addEventListener('change', function(){
        var gid = this.value;
        if (gid && guruKelasMap[gid]) {
            var opts = selKelasV.options;
            for (var i = 0; i < opts.length; i++) {
                if (opts[i].value === guruKelasMap[gid]) {
                    selKelasV.selectedIndex = i;
                    return;
                }
            }
        }
    });
})();

// ── Kelas → filter siswa options (Tab Siswa) ─────────────────────────────
function filterSiswaByKelas(kelas) {
    var selSiswa = document.getElementById('selSiswa');
    if (!selSiswa) return;
    var opts = selSiswa.querySelectorAll('option.opt-siswa');
    opts.forEach(function(opt) {
        if (!kelas || opt.getAttribute('data-kelas') === kelas) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
    // Reset selection if current selection doesn't match
    var cur = selSiswa.value;
    if (cur) {
        var curOpt = selSiswa.querySelector('option[value="' + cur + '"]');
        if (curOpt && curOpt.style.display === 'none') {
            selSiswa.value = '';
        }
    }
}

// ── Apply filter on page load for tab siswa ──────────────────────────────
(function(){
    var selKelasS = document.getElementById('selKelasS');
    if (selKelasS && selKelasS.value) {
        filterSiswaByKelas(selKelasS.value);
    }
})();

// ── Collapsible day cards ─────────────────────────────────────────────────
function toggleDay(id) {
    var body = document.getElementById(id);
    var chv  = document.getElementById('chv-' + id);
    if (!body) return;
    var isOpen = body.classList.toggle('open');
    if (chv) chv.classList.toggle('open', isOpen);
}
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

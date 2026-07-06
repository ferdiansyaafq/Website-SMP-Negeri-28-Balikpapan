<?php
require_once '../includes/admin_auth.php';
requireAdminLogin();
require_once '../config/database.php';
require_once '../includes/user_accounts.php';

$conn    = getConnection();
$success = '';
$error   = '';

function hasWaliKelasAssigned(string $kelas): bool
{
  return trim($kelas) !== '';
}

function findKelasIdByName(mysqli $conn, string $kelasName): ?int
{
  $kelasName = trim($kelasName);
  if ($kelasName === '') {
    return null;
  }

  $stmt = $conn->prepare('SELECT id FROM kaih_kelas WHERE nama_kelas = ? LIMIT 1');
  if ($stmt) {
    $stmt->bind_param('s', $kelasName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
      return (int) $row['id'];
    }
  }

  // Fallback: toleransi format lama (mis. '7A' vs 'Kelas 7A').
  $abbr = trim((string) preg_replace('/^Kelas\s+/i', '', $kelasName));
  if ($abbr === '') {
    return null;
  }

  $variants = [];
  if (strcasecmp($abbr, $kelasName) !== 0) {
    $variants[] = $abbr;
  }
  $variants[] = 'Kelas ' . $abbr;

  foreach ($variants as $variant) {
    $variant = trim($variant);
    if ($variant === '') {
      continue;
    }
    $stmt2 = $conn->prepare('SELECT id FROM kaih_kelas WHERE nama_kelas = ? LIMIT 1');
    if (!$stmt2) {
      continue;
    }
    $stmt2->bind_param('s', $variant);
    $stmt2->execute();
    $row2 = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
    if ($row2) {
      return (int) $row2['id'];
    }
  }

  return null;
}

function syncSiswaWaliByGuruChange(mysqli $conn, ?array $oldGuru, array $newGuru): void
{
  $oldKelas = trim((string) ($oldGuru['kelas'] ?? ''));
  $oldKelasId = hasWaliKelasAssigned($oldKelas) ? findKelasIdByName($conn, $oldKelas) : null;

  $newKelas = trim((string) ($newGuru['kelas'] ?? ''));
  $newKelasId = hasWaliKelasAssigned($newKelas) ? findKelasIdByName($conn, $newKelas) : null;

  // Catatan: wali kelas ditentukan dari kolom "Kelas Wali" (kelas), bukan dari teks jabatan.
  // Sinkronisasi ini hanya membantu mengisi wali_kelas_id siswa yang masih kosong.
  if ($newKelasId && $newKelasId !== $oldKelasId) {
    $stmtBind = $conn->prepare("UPDATE siswa SET wali_kelas_id = ? WHERE TRIM(REPLACE(LOWER(kelas), 'kelas ', '')) = TRIM(REPLACE(LOWER(?), 'kelas ', '')) AND (wali_kelas_id IS NULL OR wali_kelas_id = 0)");
    if ($stmtBind) {
      $stmtBind->bind_param('is', $newKelasId, $newKelas);
      $stmtBind->execute();
      $stmtBind->close();
    }
  }
}

/* ============================================================
   HANDLE POST ACTIONS
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ---- TAMBAH & EDIT ---- */
    if ($action === 'simpan') {
      $id        = intval($_POST['id'] ?? 0);
      $nip       = trim($_POST['nip'] ?? '');
      $nama_guru = trim($_POST['nama_guru'] ?? '');
      $jabatan   = trim($_POST['jabatan'] ?? '');
      $kelas     = trim($_POST['kelas'] ?? '');
      $kelas_id  = intval($_POST['kelas_id'] ?? 0);
      $alamat    = trim($_POST['alamat'] ?? '');
      $no_hp     = trim($_POST['no_hp'] ?? '');
      $oldGuru   = null;

      if ($id > 0) {
        $oldGuru = fetchSingleRow($conn, 'SELECT id, jabatan, kelas FROM guru WHERE id = ? LIMIT 1', 'i', [$id]);
        if (!$oldGuru) {
          $error = 'Data guru tidak ditemukan.';
        }
      }

      if (empty($error) && $kelas_id > 0) {
        $kelasRow = fetchSingleRow($conn, 'SELECT id, nama_kelas FROM kaih_kelas WHERE id = ? LIMIT 1', 'i', [$kelas_id]);
        if (!$kelasRow) {
          $error = 'Kelas yang dipilih tidak valid.';
        } else {
          $kelas = trim((string) $kelasRow['nama_kelas']);
        }
      }

      if (empty($error) && (empty($nip) || empty($nama_guru) || empty($jabatan))) {
        $error = 'NIP, Nama Guru, dan Jabatan wajib diisi.';
      }

      if (empty($error)) {
      $conn->begin_transaction();
      try {
        if ($id === 0) {
          $cek = $conn->prepare("SELECT id FROM guru WHERE nip = ?");
          if (!$cek) {
            throw new RuntimeException('Gagal memeriksa duplikasi NIP.');
          }
          $cek->bind_param('s', $nip);
          $cek->execute();
          if ($cek->get_result()->num_rows > 0) {
            $cek->close();
            throw new RuntimeException('NIP sudah terdaftar. Gunakan NIP yang berbeda.');
          }
          $cek->close();

          $stmt = $conn->prepare("INSERT INTO guru (nip, nama_guru, jabatan, kelas, alamat, no_hp, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
          if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan penyimpanan data guru.');
          }
          $stmt->bind_param('ssssss', $nip, $nama_guru, $jabatan, $kelas, $alamat, $no_hp);
          if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Gagal menambahkan data guru.');
          }
          $id = (int) $stmt->insert_id;
          $stmt->close();
          $success = 'Data guru berhasil ditambahkan.';
        } else {
          $cek = $conn->prepare("SELECT id FROM guru WHERE nip = ? AND id != ?");
          if (!$cek) {
            throw new RuntimeException('Gagal memeriksa duplikasi NIP.');
          }
          $cek->bind_param('si', $nip, $id);
          $cek->execute();
          if ($cek->get_result()->num_rows > 0) {
            $cek->close();
            throw new RuntimeException('NIP sudah digunakan guru lain.');
          }
          $cek->close();

          $stmt = $conn->prepare("UPDATE guru SET nip=?, nama_guru=?, jabatan=?, kelas=?, alamat=?, no_hp=?, updated_at=NOW() WHERE id=?");
          if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan pembaruan data guru.');
          }
          $stmt->bind_param('ssssssi', $nip, $nama_guru, $jabatan, $kelas, $alamat, $no_hp, $id);
          if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Gagal memperbarui data guru.');
          }
          $stmt->close();
          $success = 'Data guru berhasil diperbarui.';
        }

        syncGuruAccount($conn, $id, $nip);
        syncSiswaWaliByGuruChange($conn, $oldGuru, [
            'id' => $id,
            'kelas' => $kelas,
        ]);
        $conn->commit();
      } catch (Throwable $e) {
        $conn->rollback();
        $error = $e->getMessage();
        $success = '';
            }
        }
    }

    /* ---- BULK HAPUS ---- */
    if ($action === 'bulk_hapus') {
        $rawIds = $_POST['ids'] ?? [];
        $ids = array_values(array_filter(array_map('intval', (array) $rawIds), fn($v) => $v > 0));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $conn->begin_transaction();
            try {
                foreach ($ids as $delId) {
                    deleteGuruAccount($conn, $delId);
                    $guruBefore = fetchSingleRow($conn, 'SELECT id, jabatan, kelas FROM guru WHERE id = ? LIMIT 1', 'i', [$delId]);
                    if ($guruBefore) syncSiswaWaliByGuruChange($conn, $guruBefore, ['jabatan'=>'', 'kelas'=>'']);
                }
                $stmt = $conn->prepare("DELETE FROM guru WHERE id IN ($placeholders)");
                if (!$stmt) throw new RuntimeException('Gagal menyiapkan penghapusan massal.');
                $stmt->bind_param($types, ...$ids);
                if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Gagal menghapus data guru.'); }
                $stmt->close();
                $conn->commit();
                $success = count($ids) . ' data guru berhasil dihapus.';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    /* ---- HAPUS ---- */
    if ($action === 'hapus') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
        $conn->begin_transaction();
        try {
          $guruBeforeDelete = fetchSingleRow($conn, 'SELECT id, jabatan, kelas FROM guru WHERE id = ? LIMIT 1', 'i', [$id]);

          deleteGuruAccount($conn, $id);

          $stmt = $conn->prepare("DELETE FROM guru WHERE id = ?");
          if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan penghapusan data guru.');
          }
          $stmt->bind_param('i', $id);
          if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Gagal menghapus data guru.');
          }
          $stmt->close();

          $conn->commit();
          $success = 'Data guru berhasil dihapus.';
        } catch (Throwable $e) {
          $conn->rollback();
          $error = $e->getMessage();
          $success = '';
        }
        }
    }
}

/* ============================================================
   FETCH DATA
   ============================================================ */
$search   = trim($_GET['q'] ?? '');
$per_page = 10;
$page     = max(1, intval($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

$where  = '';
$params = [];
$types  = '';
if ($search !== '') {
    $like  = "%$search%";
    $where = "WHERE nip LIKE ? OR nama_guru LIKE ? OR jabatan LIKE ? OR no_hp LIKE ?";
    $params = [$like, $like, $like, $like];
    $types  = 'ssss';
}

// Total rows
$stmtC = $conn->prepare("SELECT COUNT(*) as total FROM guru $where");
if ($types) $stmtC->bind_param($types, ...$params);
$stmtC->execute();
$total_rows = $stmtC->get_result()->fetch_assoc()['total'];
$stmtC->close();
$total_pages = ceil($total_rows / $per_page);

// Data guru
$sql   = "SELECT * FROM guru $where ORDER BY id DESC LIMIT ? OFFSET ?";
$stmtD = $conn->prepare($sql);
$allParams = array_merge($params, [$per_page, $offset]);
$allTypes  = $types . 'ii';
$stmtD->bind_param($allTypes, ...$allParams);
$stmtD->execute();
$guruList = $stmtD->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtD->close();

// Stats
$totalGuru = $conn->query("SELECT COUNT(*) as c FROM guru")->fetch_assoc()['c'];

$kelasOptionResult = $conn->query('SELECT id, nama_kelas FROM kaih_kelas ORDER BY nama_kelas');
$kelasOptions = $kelasOptionResult ? $kelasOptionResult->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();

$adminName = htmlspecialchars($_SESSION['admin_username']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<script>(function(){try{var t=localStorage.getItem('kaih_theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();function toggleTheme(){var h=document.documentElement,d=h.getAttribute('data-theme')==='dark';if(d)h.removeAttribute('data-theme');else h.setAttribute('data-theme','dark');try{localStorage.setItem('kaih_theme',d?'light':'dark');}catch(e){}}</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Guru — KAIH Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=20260316h">
<link rel="stylesheet" href="../assets/css/siswa.css?v=20260316h">
<link rel="stylesheet" href="../assets/css/guru.css?v=20260316h">
</head>
<body>
<div class="layout">

  <button class="sidebar-toggle" id="sidebarToggle" title="Menu">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  </button>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <div class="logo-box">
          <svg viewBox="0 0 40 40" fill="none">
            <rect width="40" height="40" rx="10" fill="url(#lg2)"/>
            <path d="M10 20L17 13L24 20L17 27L10 20Z" fill="white"/>
            <path d="M18 20L25 13L32 20L25 27L18 20Z" fill="white" fill-opacity=".6"/>
            <defs><linearGradient id="lg2" x1="0" y1="0" x2="40" y2="40"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs>
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
      <a href="guru.php" class="nav-item active">
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
      <a href="laporan-guru.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="2"/><rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="2"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Laporan Guru</span>
      </a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= strtoupper(substr($adminName,0,1)) ?></div>
        <div class="user-detail">
          <span class="user-name"><?= $adminName ?></span>
          <span class="user-role">Administrator</span>
        </div>
      </div>
      <a href="logout.php" class="btn-logout" title="Keluar">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main-content">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <h1 class="page-title">Data Guru</h1>
        <p class="page-sub">Kelola data guru &mdash; total <strong><?= $totalGuru ?> guru</strong> terdaftar</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-date">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/><line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/><line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/></svg>
          <?= date('d M Y') ?>
        </div>
        <div class="admin-chip"><div class="admin-dot"></div>Admin</div>
      </div>
    </header>

    <!-- CONTENT -->
    <div class="content-area">

      <!-- Alert Messages -->
      <?php if ($success): ?>
      <div class="alert-toast success" id="toastMsg">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#22c55e" stroke-width="2"/><path d="M9 12l2 2 4-4" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?= htmlspecialchars($success) ?>
        <button onclick="this.parentElement.remove()">×</button>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert-toast error" id="toastMsg">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#ef4444" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/></svg>
        <?= htmlspecialchars($error) ?>
        <button onclick="this.parentElement.remove()">×</button>
      </div>
      <?php endif; ?>

      <!-- Toolbar -->
      <div class="toolbar">
        <form method="GET" action="" class="search-form" id="searchForm">
          <div class="search-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <input type="text" name="q" id="searchInput" placeholder="Cari NIP, nama, jabatan, atau HP..." value="<?= htmlspecialchars($search) ?>" class="search-input" autocomplete="off">
            <?php if ($search): ?><a href="guru.php" class="search-clear" title="Hapus">×</a><?php endif; ?>
          </div>
        </form>
        <button class="btn-primary" onclick="openModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
          Tambah Guru
        </button>
      </div>

      <!-- Table -->
      <div class="table-card">
        <div class="table-header">
          <h3>Daftar Guru</h3>
          <span class="table-count"><?= $total_rows ?> data ditemukan</span>
        </div>

        <!-- Bulk action toolbar -->
        <form method="POST" id="frmBulkGuru" action="">
          <input type="hidden" name="action" value="bulk_hapus">
          <div id="bulkToolbarGuru" style="display:none;align-items:center;gap:10px;padding:10px 16px;background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2);border-radius:8px;margin-bottom:10px;">
            <span id="bulkCountGuru" style="font-size:.875rem;color:#dc2626;font-weight:600;">0 dipilih</span>
            <button type="submit" id="btnBulkDelGuru"
              style="display:flex;align-items:center;gap:6px;padding:6px 14px;background:#dc2626;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.875rem;font-weight:600;"
              onclick="return confirm('Hapus ' + document.querySelectorAll(\'input[name=\"ids[]\"]\').length + ' data guru yang dipilih? Tindakan ini tidak dapat dibatalkan.')">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              Hapus Terpilih
            </button>
            <button type="button"
              style="padding:6px 12px;background:transparent;border:1px solid #94a3b8;border-radius:6px;cursor:pointer;font-size:.875rem;color:#64748b;"
              onclick="clearSelectionGuru()">Batal Pilih</button>
          </div>

        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width:36px;text-align:center;"><input type="checkbox" id="chkAllGuru" title="Pilih Semua" style="cursor:pointer;width:16px;height:16px;"></th>
                <th style="width:50px">No</th>
                <th>NIP</th>
                <th>Nama Guru</th>
                <th>Jabatan</th>
                <th>Kelas Wali</th>
                <th>No. HP</th>
                <th>Terdaftar</th>
                <th style="width:120px;text-align:center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($guruList)): ?>
              <tr>
                <td colspan="9" class="empty-state">
                  <div class="empty-wrap">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/></svg>
                    <p><?= $search ? 'Tidak ada guru yang cocok dengan pencarian.' : 'Belum ada data guru. Klik "Tambah Guru" untuk memulai.' ?></p>
                  </div>
                </td>
              </tr>
              <?php else: ?>
              <?php $no = $offset + 1; foreach ($guruList as $g): ?>
              <tr class="table-row">
                <td style="text-align:center;"><input type="checkbox" name="ids[]" value="<?= $g['id'] ?>" class="chk-guru" style="cursor:pointer;width:16px;height:16px;"></td>
                <td class="td-no"><?= $no++ ?></td>
                <td><span class="nisn-badge nip-color"><?= htmlspecialchars($g['nip']) ?></span></td>
                <td>
                  <div class="name-wrap">
                    <div class="avatar-sm guru-av"><?= strtoupper(substr($g['nama_guru'],0,1)) ?></div>
                    <div>
                      <div><?= htmlspecialchars($g['nama_guru']) ?></div>
                      <?php if ($g['alamat']): ?>
                      <div class="guru-alamat" title="<?= htmlspecialchars($g['alamat']) ?>"><?= htmlspecialchars(mb_strimwidth($g['alamat'], 0, 35, '...')) ?></div>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>
                <td><span class="jabatan-chip"><?= htmlspecialchars($g['jabatan']) ?></span></td>
                <td class="td-wali"><?= $g['kelas'] ? htmlspecialchars($g['kelas']) : '<span class="td-none">—</span>' ?></td>
                <td class="td-hp">
                  <?php if ($g['no_hp']): ?>
                  <a href="tel:<?= htmlspecialchars($g['no_hp']) ?>" class="hp-link"><?= htmlspecialchars($g['no_hp']) ?></a>
                  <?php else: ?><span class="td-none">—</span><?php endif; ?>
                </td>
                <td class="td-date"><?= date('d M Y', strtotime($g['created_at'])) ?></td>
                <td class="td-action">
                  <button type="button" class="btn-edit" title="Edit"
                    onclick="openEdit(
                      <?= $g['id'] ?>,
                      '<?= addslashes($g['nip']) ?>',
                      '<?= addslashes($g['nama_guru']) ?>',
                      '<?= addslashes($g['jabatan']) ?>',
                      '<?= addslashes($g['kelas']) ?>',
                      '<?= addslashes($g['alamat']) ?>',
                      '<?= addslashes($g['no_hp']) ?>'
                    )">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  </button>
                  <button type="button" class="btn-delete" title="Hapus"
                    onclick="confirmDelete(<?= $g['id'] ?>, '<?= addslashes($g['nama_guru']) ?>')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2"/></svg>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <span class="page-info">Halaman <?= $page ?> dari <?= $total_pages ?></span>
          <div class="page-btns">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="page-btn">‹ Prev</a>
            <?php endif; ?>
            <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
            <a href="?page=<?= $i ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="page-btn">Next ›</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
        </form><!-- /frmBulkGuru -->

    </div><!-- /content-area -->
  </main>
</div><!-- /layout -->

<!-- ============================================================
     MODAL TAMBAH / EDIT GURU
     ============================================================ -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
  <div class="modal-box modal-lg" id="modalBox">
    <div class="modal-head">
      <div class="modal-icon blue">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
      </div>
      <div>
        <h3 class="modal-title" id="modalTitle">Tambah Guru Baru</h3>
        <p class="modal-sub" id="modalSub">Isi data guru baru dengan lengkap</p>
      </div>
      <button class="modal-close" onclick="closeModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="POST" action="" id="formGuru">
      <input type="hidden" name="action" value="simpan">
      <input type="hidden" name="id" id="fieldId" value="0">
      <div class="modal-body">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">NIP <span class="req">*</span></label>
            <input type="text" name="nip" id="fieldNip" class="form-input" placeholder="Nomor Induk Pegawai" maxlength="30" required>
          </div>
          <div class="form-group">
            <label class="form-label">Jabatan <span class="req">*</span></label>
            <input type="text" name="jabatan" id="fieldJabatan" class="form-input" placeholder="Contoh: Wali Kelas, Guru BK, Kepala Sekolah" maxlength="100" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Nama Lengkap Guru <span class="req">*</span></label>
          <input type="text" name="nama_guru" id="fieldNama" class="form-input" placeholder="Nama lengkap guru" maxlength="100" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Kelas Wali <span class="req" id="kelasReqMark" style="display:none">*</span></label>
            <select name="kelas_id" id="fieldKelasId" class="form-select">
              <option value="">— Pilih Kelas —</option>
              <?php foreach ($kelasOptions as $k): ?>
              <option value="<?= (int) $k['id'] ?>" data-nama="<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="form-hint-ok" id="kelasHint">Kosongkan jika bukan wali kelas</span>
            <?php if (empty($kelasOptions)): ?>
            <p class="form-hint">⚠️ Belum ada kelas. Tambahkan kelas terlebih dahulu.</p>
            <?php endif; ?>
            <input type="hidden" name="kelas" id="fieldKelas" value="">
          </div>
          <div class="form-group">
            <label class="form-label">No. HP / WhatsApp</label>
            <div class="input-hp-wrap">
              <span class="hp-prefix">+62</span>
              <input type="text" name="no_hp" id="fieldHp" class="form-input input-hp" placeholder="812xxxxx" maxlength="20">
            </div>
          </div>
        </div>

        <div class="form-group mb-0">
          <label class="form-label">Alamat</label>
          <textarea name="alamat" id="fieldAlamat" class="form-textarea" placeholder="Alamat lengkap guru (opsional)" rows="3"></textarea>
        </div>

      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn-save" id="btnSave">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="17 21 17 13 7 13 7 21" stroke="currentColor" stroke-width="2"/><polyline points="7 3 7 8 15 8" stroke="currentColor" stroke-width="2"/></svg>
          <span id="btnSaveText">Simpan Data</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     MODAL KONFIRMASI HAPUS
     ============================================================ -->
<div class="modal-overlay" id="deleteOverlay" onclick="closeDelete(event)">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <div class="modal-icon danger">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2"/></svg>
      </div>
      <div>
        <h3 class="modal-title">Hapus Data Guru</h3>
        <p class="modal-sub">Tindakan ini tidak dapat dibatalkan</p>
      </div>
      <button class="modal-close" onclick="closeDelete()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="delete-confirm-msg">
        Apakah Anda yakin ingin menghapus data guru:<br>
        <strong id="deleteName"></strong>?
      </div>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="hapus">
      <input type="hidden" name="id" id="deleteId">
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeDelete()">Batal</button>
        <button type="submit" class="btn-danger">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          Ya, Hapus
        </button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/guru.js?v=20260316i"></script>

<script>
/* ---- Bulk select guru ---- */
(function() {
  function getBoxes()   { return document.querySelectorAll('input.chk-guru'); }
  function getChecked() { return document.querySelectorAll('input.chk-guru:checked'); }
  var chkAll  = document.getElementById('chkAllGuru');
  var toolbar = document.getElementById('bulkToolbarGuru');
  var countEl = document.getElementById('bulkCountGuru');

  function updateBulkUI() {
    var n = getChecked().length;
    if (countEl) countEl.textContent = n + ' dipilih';
    if (toolbar) toolbar.style.display = n > 0 ? 'flex' : 'none';
    if (chkAll) {
      var total = getBoxes().length;
      chkAll.checked = n > 0 && n === total;
      chkAll.indeterminate = n > 0 && n < total;
    }
  }

  if (chkAll) {
    chkAll.addEventListener('change', function() {
      getBoxes().forEach(function(b) { b.checked = chkAll.checked; });
      updateBulkUI();
    });
  }

  document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('chk-guru')) updateBulkUI();
  });

  window.clearSelectionGuru = function() {
    getBoxes().forEach(function(b) { b.checked = false; });
    if (chkAll) { chkAll.checked = false; chkAll.indeterminate = false; }
    updateBulkUI();
  };
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

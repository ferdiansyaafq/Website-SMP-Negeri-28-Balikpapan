<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

require_once '../config/database.php';
require_once '../includes/user_accounts.php';

$conn    = getConnection();
$success = '';
$error   = '';
$managedStudentDefaultPassword = getManagedStudentDefaultPassword();

/* ============================================================
   HANDLE POST ACTIONS
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ---- TAMBAH ADMIN ---- */
    if ($action === 'create_admin') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if (empty($username) || empty($password)) {
            $error = 'Username dan password admin wajib diisi.';
        } else {
            $conn->begin_transaction();
            try {
                createAdminAccount($conn, $username, $password);
                $conn->commit();
                $success = 'Akun admin "' . htmlspecialchars($username) . '" berhasil dibuat.';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    /* ---- EDIT ADMIN ---- */
    if ($action === 'update_admin') {
        $userId   = intval($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if (empty($username)) {
            $error = 'Username admin wajib diisi.';
        } elseif ($userId <= 0) {
            $error = 'ID admin tidak valid.';
        } else {
            $conn->begin_transaction();
            try {
                updateAdminCredentials($conn, $userId, $username, $password !== '' ? $password : null);
                $conn->commit();
                $success = 'Data akun admin berhasil diperbarui.';
                if ($userId === (int) $_SESSION['admin_id']) {
                    $_SESSION['admin_username'] = $username;
                }
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    /* ---- HAPUS ADMIN ---- */
    if ($action === 'hapus_admin') {
        $userId = intval($_POST['user_id'] ?? 0);
        if ($userId === (int) $_SESSION['admin_id']) {
            $error = 'Tidak dapat menghapus akun admin yang sedang aktif.';
        } elseif ($userId <= 0) {
            $error = 'ID admin tidak valid.';
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
                if (!$stmt) throw new RuntimeException('Gagal menyiapkan penghapusan akun admin.');
                $stmt->bind_param('i', $userId);
                if (!$stmt->execute()) throw new RuntimeException('Gagal menghapus akun admin.');
                $stmt->close();
                $conn->commit();
                $success = 'Akun admin berhasil dihapus.';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    /* ---- SYNC ALL ---- */
    if ($action === 'sync_all') {
        $conn->begin_transaction();
        try {
            $result = syncAllManagedAccounts($conn);
            $conn->commit();
            $success = 'Sinkronisasi selesai: ' . $result['siswa'] . ' akun siswa, ' . $result['orang_tua'] .
                       ' akun orang tua, dan ' . $result['guru'] . ' akun guru dipastikan aktif.';
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }

        /* ---- RESET SEMUA PASSWORD SISWA & ORANG TUA ---- */
        if ($action === 'reset_all_student_passwords') {
          $conn->begin_transaction();
          try {
            $studentResult = $conn->query('SELECT id, nisn FROM siswa ORDER BY id');
            if (!$studentResult) {
              throw new RuntimeException('Gagal membaca data siswa untuk reset massal.');
            }

            $resetCount = 0;
            while ($student = $studentResult->fetch_assoc()) {
              syncStudentAccounts($conn, (int) $student['id'], (string) $student['nisn'], true);
              $resetCount++;
            }

            $conn->commit();
            $success = 'Reset semua password siswa dan orang tua selesai. ' . $resetCount . ' siswa diproses dengan password default ' . $managedStudentDefaultPassword . '.';
          } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
          }
        }

    /* ---- BUAT AKUN GURU ---- */
    if ($action === 'create_guru_account') {
        $guruId = intval($_POST['guru_id'] ?? 0);
        $guru   = fetchSingleRow($conn, 'SELECT id, nip, nama_guru FROM guru WHERE id = ? LIMIT 1', 'i', [$guruId]);
        if (!$guru) {
            $error = 'Data guru tidak ditemukan.';
        } else {
            $conn->begin_transaction();
            try {
                syncGuruAccount($conn, $guruId, $guru['nip'], null, false);
                $conn->commit();
                $success = 'Akun login ' . htmlspecialchars($guru['nama_guru']) . ' berhasil dibuat (password = NIP).';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    /* ---- UPDATE PASSWORD GURU ---- */
    if ($action === 'update_guru_password') {
        $guruId = intval($_POST['guru_id'] ?? 0);
        $newPwd = trim($_POST['new_password'] ?? '');
        $guru   = fetchSingleRow($conn, 'SELECT id, nip, nama_guru FROM guru WHERE id = ? LIMIT 1', 'i', [$guruId]);
        if (!$guru) {
            $error = 'Data guru tidak ditemukan.';
        } elseif ($newPwd === '') {
            $error = 'Password baru guru wajib diisi.';
        } else {
            $conn->begin_transaction();
            try {
                syncGuruAccount($conn, $guruId, $guru['nip'], $newPwd, false);
                $conn->commit();
                $success = 'Password login ' . htmlspecialchars($guru['nama_guru']) . ' berhasil diperbarui.';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    /* ---- RESET GURU KE NIP ---- */
    if ($action === 'reset_guru_to_nip') {
        $guruId = intval($_POST['guru_id'] ?? 0);
        $guru   = fetchSingleRow($conn, 'SELECT id, nip, nama_guru FROM guru WHERE id = ? LIMIT 1', 'i', [$guruId]);
        if (!$guru) {
            $error = 'Data guru tidak ditemukan.';
        } else {
            $conn->begin_transaction();
            try {
                syncGuruAccount($conn, $guruId, $guru['nip'], null, true);
                $conn->commit();
                $success = 'Password login ' . htmlspecialchars($guru['nama_guru']) . ' direset ke NIP.';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    /* ---- HAPUS AKUN GURU ---- */
    if ($action === 'hapus_guru_account') {
        $guruId = intval($_POST['guru_id'] ?? 0);
        $guru   = fetchSingleRow($conn, 'SELECT id, nama_guru FROM guru WHERE id = ? LIMIT 1', 'i', [$guruId]);
        if (!$guru) {
            $error = 'Data guru tidak ditemukan.';
        } else {
            $conn->begin_transaction();
            try {
                deleteGuruAccount($conn, $guruId);
                $conn->commit();
                $success = 'Akun login ' . htmlspecialchars($guru['nama_guru']) . ' berhasil dihapus.';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    /* ---- BUAT AKUN SISWA ---- */
    if ($action === 'create_siswa_account') {
        $siswaId = intval($_POST['siswa_id'] ?? 0);
        $siswa   = fetchSingleRow($conn, 'SELECT id, nisn, nama_siswa FROM siswa WHERE id = ? LIMIT 1', 'i', [$siswaId]);
        if (!$siswa) {
            $error = 'Data siswa tidak ditemukan.';
        } else {
            $conn->begin_transaction();
            try {
                syncStudentAccounts($conn, $siswaId, $siswa['nisn']);
                $conn->commit();
              $success = 'Akun login ' . htmlspecialchars($siswa['nama_siswa']) . ' dan orang tua berhasil dibuat dengan password default ' . htmlspecialchars($managedStudentDefaultPassword) . '.';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    /* ---- UPDATE PASSWORD SISWA ---- */
    if ($action === 'update_siswa_password') {
        $siswaId  = intval($_POST['siswa_id'] ?? 0);
        $pwdSiswa = trim($_POST['pwd_siswa'] ?? '');
        $pwdOrt   = trim($_POST['pwd_ort'] ?? '');
        $siswa    = fetchSingleRow($conn, 'SELECT id, nisn, nama_siswa FROM siswa WHERE id = ? LIMIT 1', 'i', [$siswaId]);
        if (!$siswa) {
            $error = 'Data siswa tidak ditemukan.';
        } elseif ($pwdSiswa === '' && $pwdOrt === '') {
            $error = 'Isi minimal satu password yang ingin diperbarui.';
        } else {
            $conn->begin_transaction();
            try {
                if ($pwdSiswa !== '') {
                    upsertLinkedUser($conn, 'siswa', $siswa['nisn'], $siswa['nisn'], null, $siswaId, $pwdSiswa, false);
                }
                if ($pwdOrt !== '') {
                    $ortUser = buildParentUsername($siswa['nisn']);
                    upsertLinkedUser($conn, 'orang_tua', $ortUser, $ortUser, null, $siswaId, $pwdOrt, false);
                }
                $conn->commit();
                $success = 'Password login ' . htmlspecialchars($siswa['nama_siswa']) . ' berhasil diperbarui.';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    /* ---- HAPUS AKUN SISWA ---- */
    if ($action === 'hapus_siswa_account') {
        $siswaId = intval($_POST['siswa_id'] ?? 0);
        $siswa   = fetchSingleRow($conn, 'SELECT id, nama_siswa FROM siswa WHERE id = ? LIMIT 1', 'i', [$siswaId]);
        if (!$siswa) {
            $error = 'Data siswa tidak ditemukan.';
        } else {
            $conn->begin_transaction();
            try {
                deleteStudentAccounts($conn, $siswaId);
                $conn->commit();
                $success = 'Akun login ' . htmlspecialchars($siswa['nama_siswa']) . ' dan orang tua berhasil dihapus.';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
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
$like     = $search !== '' ? "%$search%" : null;

/* Admin — paginated + searchable */
$countSql = "SELECT COUNT(*) AS total FROM users WHERE role = 'admin'" . ($like ? " AND username LIKE ?" : "");
$stmtC    = $conn->prepare($countSql);
if ($like) $stmtC->bind_param('s', $like);
$stmtC->execute();
$total_rows  = (int) $stmtC->get_result()->fetch_assoc()['total'];
$stmtC->close();
$total_pages = (int) ceil($total_rows / $per_page);

$dataSql = "SELECT id, username, created_at, updated_at FROM users WHERE role = 'admin'" .
           ($like ? " AND username LIKE ?" : "") .
           " ORDER BY id ASC LIMIT ? OFFSET ?";
$stmtD   = $conn->prepare($dataSql);
if ($like) { $stmtD->bind_param('sii', $like, $per_page, $offset); } else { $stmtD->bind_param('ii', $per_page, $offset); }
$stmtD->execute();
$adminList  = $stmtD->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtD->close();
$totalAdmin = (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'")->fetch_assoc()['c'];

/* Guru */
$guruList = $conn->query(
    "SELECT g.id, g.nip, g.nama_guru, g.jabatan,
            u.id AS user_id, u.username AS login_username
     FROM guru g
     LEFT JOIN users u ON u.guru_id = g.id AND u.role = 'guru'
     ORDER BY g.nama_guru ASC"
)->fetch_all(MYSQLI_ASSOC);

/* Siswa */
$siswaList = $conn->query(
    "SELECT s.id, s.nisn, s.nama_siswa, s.kelas,
            us.id AS siswa_uid, us.username AS login_siswa,
            uo.id AS ort_uid, uo.username AS login_ort
     FROM siswa s
     LEFT JOIN users us ON us.siswa_id = s.id AND us.role = 'siswa'
     LEFT JOIN users uo ON uo.siswa_id = s.id AND uo.role = 'orang_tua'
     ORDER BY s.nama_siswa ASC"
)->fetch_all(MYSQLI_ASSOC);

$conn->close();
$adminName = htmlspecialchars($_SESSION['admin_username']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<script>(function(){try{var t=localStorage.getItem('kaih_theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();function toggleTheme(){var h=document.documentElement,d=h.getAttribute('data-theme')==='dark';if(d)h.removeAttribute('data-theme');else h.setAttribute('data-theme','dark');try{localStorage.setItem('kaih_theme',d?'light':'dark');}catch(e){}}</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Akun &amp; User &mdash; KAIH Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/siswa.css">
<link rel="stylesheet" href="../assets/css/guru.css">
<link rel="stylesheet" href="../assets/css/users.css?v=20260327-2">
</head>
<body data-managed-student-default-password="<?= htmlspecialchars($managedStudentDefaultPassword) ?>">
<div class="layout">

  <button class="sidebar-toggle" id="sidebarToggle" title="Menu">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  </button>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <div class="logo-box">
          <svg viewBox="0 0 40 40" fill="none">
            <rect width="40" height="40" rx="10" fill="url(#lgU)"/>
            <path d="M10 20L17 13L24 20L17 27L10 20Z" fill="white"/>
            <path d="M18 20L25 13L32 20L25 27L18 20Z" fill="white" fill-opacity=".6"/>
            <defs><linearGradient id="lgU" x1="0" y1="0" x2="40" y2="40"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs>
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
      <a href="users.php" class="nav-item active">
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
        <div class="user-avatar"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
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

  <main class="main-content">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <h1 class="page-title">Akun &amp; User</h1>
        <p class="page-sub">Kelola akun login &mdash; <strong><?= $totalAdmin ?></strong> admin &bull; <strong><?= count($guruList) ?></strong> guru &bull; <strong><?= count($siswaList) ?></strong> siswa</p>
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

      <?php if ($success): ?>
      <div class="alert-toast success" id="toastMsg">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#22c55e" stroke-width="2"/><path d="M9 12l2 2 4-4" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?= htmlspecialchars($success) ?>
        <button onclick="this.parentElement.remove()">&times;</button>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert-toast error" id="toastMsg">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#ef4444" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/></svg>
        <?= htmlspecialchars($error) ?>
        <button onclick="this.parentElement.remove()">&times;</button>
      </div>
      <?php endif; ?>

      <!-- SYNC BAR -->
      <div class="sync-bar">
        <div class="sync-bar-left">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M23 4v6h-6M1 20v-6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <span>Sinkronkan akun login guru dan siswa dengan data master agar selalu terbarui</span>
        </div>
        <form method="POST" action="">
          <input type="hidden" name="action" value="sync_all">
          <button type="submit" class="btn-sync">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M23 4v6h-6M1 20v-6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Sinkronkan Semua
          </button>
        </form>
      </div>

      <!-- ============================================================
           UNIFIED TABLE CARD
           ============================================================ -->
      <div class="table-card">

        <!-- Table Header: Title + Role Tab Selector -->
        <div class="table-header users-table-header">
          <div class="table-header-left">
            <h3 id="tableTitle">Daftar Admin</h3>
            <span class="table-count" id="tableCountBadge"><?= $total_rows ?> data</span>
          </div>
          <div class="role-tab-bar">
            <button class="role-tab active" data-tab="admin" onclick="switchTab('admin')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              Admin
              <span class="role-badge" id="role-count-admin"><?= $totalAdmin ?></span>
            </button>
            <button class="role-tab" data-tab="guru" onclick="switchTab('guru')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
              Guru
              <span class="role-badge" id="role-count-guru"><?= count($guruList) ?></span>
            </button>
            <button class="role-tab" data-tab="siswa" onclick="switchTab('siswa')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              Siswa &amp; OT
              <span class="role-badge" id="role-count-siswa"><?= count($siswaList) ?></span>
            </button>
          </div>
        </div>

        <!-- ---- ADMIN TOOLBAR ---- -->
        <div class="card-toolbar" id="toolbar-admin">
          <form method="GET" action="" class="search-form" id="searchForm">
            <div class="search-wrap">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              <input type="text" name="q" id="searchInput" placeholder="Cari username admin..." value="<?= htmlspecialchars($search) ?>" class="search-input" autocomplete="off">
              <?php if ($search): ?><a href="users.php" class="search-clear" title="Hapus">&times;</a><?php endif; ?>
            </div>
          </form>
          <button class="btn-primary" onclick="openAdminModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
            Tambah Admin
          </button>
        </div>

        <!-- ---- GURU TOOLBAR ---- -->
        <div class="card-toolbar" id="toolbar-guru" style="display:none">
          <div class="search-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <input type="text" id="guruSearch" placeholder="Cari nama guru, NIP, atau jabatan..." class="search-input" autocomplete="off">
          </div>
          <span class="count-label"><?= count($guruList) ?> guru terdaftar</span>
        </div>

        <!-- ---- SISWA TOOLBAR ---- -->
        <div class="card-toolbar" id="toolbar-siswa" style="display:none">
          <div class="search-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <input type="text" id="siswaSearch" placeholder="Cari nama siswa, kelas, atau NISN..." class="search-input" autocomplete="off">
          </div>
          <div class="student-toolbar-meta">
            <span class="count-label"><?= count($siswaList) ?> siswa terdaftar</span>
            <span class="password-default-note">Password default/reset siswa dan orang tua: <strong><?= htmlspecialchars($managedStudentDefaultPassword) ?></strong>. Jika password sudah diubah sendiri oleh user, admin tidak bisa membaca password aktif lama dan perlu reset ulang.</span>
          </div>
          <button type="button" class="btn-sync btn-sync-danger" onclick="openResetAllPasswordsModal()">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M23 4v6h-6M1 20v-6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              Reset Semua Password
          </button>
        </div>

        <!-- ---- TABLE ---- -->
        <div class="table-responsive">
          <table class="data-table">

            <thead>
              <!-- Admin columns -->
              <tr id="thead-admin">
                <th style="width:50px">No</th>
                <th>Username</th>
                <th>Terdaftar</th>
                <th>Terakhir Diperbarui</th>
                <th style="width:120px;text-align:center">Aksi</th>
              </tr>
              <!-- Guru columns -->
              <tr id="thead-guru" style="display:none">
                <th style="width:50px">No</th>
                <th>Nama Guru</th>
                <th>NIP</th>
                <th>Jabatan</th>
                <th>Status Akun</th>
                <th style="width:100px;text-align:center">Aksi</th>
              </tr>
              <!-- Siswa columns -->
              <tr id="thead-siswa" style="display:none">
                <th style="width:50px">No</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Login Siswa</th>
                <th>Login Orang Tua</th>
                <th style="width:100px;text-align:center">Aksi</th>
              </tr>
            </thead>

            <!-- ====== ADMIN TBODY ====== -->
            <tbody id="tbody-admin">
              <?php if (empty($adminList)): ?>
              <tr>
                <td colspan="5" class="empty-state">
                  <div class="empty-wrap">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <p><?= $search ? 'Tidak ada admin yang cocok dengan pencarian.' : 'Belum ada akun admin.' ?></p>
                  </div>
                </td>
              </tr>
              <?php else: ?>
              <?php $ano = ($page - 1) * $per_page + 1; foreach ($adminList as $row): ?>
              <tr class="table-row">
                <td class="td-no"><?= $ano++ ?></td>
                <td>
                  <div class="name-wrap">
                    <div class="avatar-sm <?= $row['id'] === (int)$_SESSION['admin_id'] ? 'av-self' : '' ?>"><?= strtoupper(substr($row['username'], 0, 1)) ?></div>
                    <div>
                      <div><?= htmlspecialchars($row['username']) ?></div>
                      <?php if ($row['id'] === (int)$_SESSION['admin_id']): ?>
                      <div class="self-label">Akun Anda</div>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>
                <td class="td-muted"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                <td class="td-muted"><?= date('d M Y, H:i', strtotime($row['updated_at'])) ?></td>
                <td class="td-action">
                  <button class="btn-edit" title="Edit" onclick="editAdmin(<?= $row['id'] ?>, '<?= addslashes($row['username']) ?>')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  </button>
                  <button class="btn-delete <?= $row['id'] === (int)$_SESSION['admin_id'] ? 'btn-muted' : '' ?>"
                    title="Hapus"
                    <?php if ($row['id'] !== (int)$_SESSION['admin_id']): ?>
                    onclick="confirmDeleteAdmin(<?= $row['id'] ?>, '<?= addslashes($row['username']) ?>')"
                    <?php else: ?>disabled<?php endif; ?>>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2"/></svg>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>

            <!-- ====== GURU TBODY ====== -->
            <tbody id="tbody-guru" style="display:none">
              <?php if (empty($guruList)): ?>
              <tr>
                <td colspan="6" class="empty-state">
                  <div class="empty-wrap">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/></svg>
                    <p>Belum ada data guru. Tambahkan guru di menu Data Guru.</p>
                  </div>
                </td>
              </tr>
              <?php else: ?>
              <?php $gno = 1; foreach ($guruList as $guru): ?>
              <tr class="table-row" data-guru-row>
                <td class="td-no"><?= $gno++ ?></td>
                <td>
                  <div class="name-wrap">
                    <div class="avatar-sm guru-av"><?= strtoupper(substr($guru['nama_guru'], 0, 1)) ?></div>
                    <div>
                      <div><?= htmlspecialchars($guru['nama_guru']) ?></div>
                      <div class="guru-alamat"><?= htmlspecialchars($guru['login_username'] ?: $guru['nip']) ?></div>
                    </div>
                  </div>
                </td>
                <td><span class="nisn-badge nip-color"><?= htmlspecialchars($guru['nip']) ?></span></td>
                <td><span class="jabatan-chip"><?= htmlspecialchars($guru['jabatan'] ?: '—') ?></span></td>
                <td>
                  <span class="status-chip <?= $guru['user_id'] ? 'status-ok' : 'status-pending' ?>">
                    <?= $guru['user_id'] ? 'Siap Login' : 'Belum Dibuat' ?>
                  </span>
                  <?php if ($guru['user_id']): ?>
                  <div class="credential-preview credential-preview-inline">
                    <span class="credential-label">Password default/reset</span>
                    <span class="credential-secret" data-visible="false" data-password-value="<?= htmlspecialchars($guru['nip']) ?>">••••••••</span>
                    <button type="button" class="credential-toggle" onclick="toggleCredentialPassword(this)">Lihat</button>
                  </div>
                  <?php endif; ?>
                </td>
                <td class="td-action">
                  <?php if ($guru['user_id']): ?>
                  <button class="btn-edit" title="Edit Password"
                    onclick="editGuruPwd(<?= $guru['id'] ?>, '<?= addslashes($guru['nama_guru']) ?>', '<?= addslashes($guru['nip']) ?>')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  </button>
                  <button class="btn-delete" title="Hapus Akun"
                    onclick="confirmDeleteGuru(<?= $guru['id'] ?>, '<?= addslashes($guru['nama_guru']) ?>')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2"/></svg>
                  </button>
                  <?php else: ?>
                  <button class="btn-add-account"
                    onclick="buatAkunGuru(<?= $guru['id'] ?>, '<?= addslashes($guru['nama_guru']) ?>', '<?= addslashes($guru['nip']) ?>')">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                    Buat Akun
                  </button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>

            <!-- ====== SISWA TBODY ====== -->
            <tbody id="tbody-siswa" style="display:none">
              <?php if (empty($siswaList)): ?>
              <tr>
                <td colspan="6" class="empty-state">
                  <div class="empty-wrap">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/></svg>
                    <p>Belum ada data siswa. Tambahkan siswa di menu Data Siswa.</p>
                  </div>
                </td>
              </tr>
              <?php else: ?>
              <?php $sno = 1; foreach ($siswaList as $siswa): $hasAkun = (bool) $siswa['siswa_uid']; ?>
              <tr class="table-row" data-siswa-row>
                <td class="td-no"><?= $sno++ ?></td>
                <td>
                  <div class="name-wrap">
                    <div class="avatar-sm siswa-av"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></div>
                    <span><?= htmlspecialchars($siswa['nama_siswa']) ?></span>
                  </div>
                </td>
                <td>
                  <?= $siswa['kelas']
                    ? '<span class="kelas-chip">' . htmlspecialchars($siswa['kelas']) . '</span>'
                    : '<span class="td-none">—</span>' ?>
                </td>
                <td>
                  <span class="nisn-badge <?= $hasAkun ? '' : 'nisn-grey' ?>" title="<?= $hasAkun ? 'Akun aktif' : 'Belum ada akun' ?>">
                    <?= htmlspecialchars($siswa['nisn']) ?>
                  </span>
                  <?php if ($hasAkun): ?>
                  <div class="credential-preview">
                    <span class="credential-label">Password default/reset</span>
                    <span class="credential-secret" data-visible="false" data-password-value="<?= htmlspecialchars($managedStudentDefaultPassword) ?>">••••••••</span>
                    <button type="button" class="credential-toggle" onclick="toggleCredentialPassword(this)">Lihat</button>
                  </div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="nisn-badge parent-code <?= $hasAkun ? '' : 'nisn-grey' ?>">
                    <?= htmlspecialchars(buildParentUsername($siswa['nisn'])) ?>
                  </span>
                  <?php if ($hasAkun): ?>
                  <div class="credential-preview">
                    <span class="credential-label">Password default/reset</span>
                    <span class="credential-secret" data-visible="false" data-password-value="<?= htmlspecialchars($managedStudentDefaultPassword) ?>">••••••••</span>
                    <button type="button" class="credential-toggle" onclick="toggleCredentialPassword(this)">Lihat</button>
                  </div>
                  <?php endif; ?>
                </td>
                <td class="td-action">
                  <?php if ($hasAkun): ?>
                  <button class="btn-edit" title="Edit Password"
                    onclick="editSiswaPwd(<?= $siswa['id'] ?>, '<?= addslashes($siswa['nama_siswa']) ?>', '<?= addslashes($siswa['nisn']) ?>')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  </button>
                  <button class="btn-delete" title="Hapus Akun"
                    onclick="confirmDeleteSiswa(<?= $siswa['id'] ?>, '<?= addslashes($siswa['nama_siswa']) ?>')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2"/></svg>
                  </button>
                  <?php else: ?>
                  <button class="btn-add-account"
                    onclick="buatAkunSiswa(<?= $siswa['id'] ?>, '<?= addslashes($siswa['nama_siswa']) ?>', '<?= addslashes($siswa['nisn']) ?>')">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                    Buat Akun
                  </button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>

          </table>
        </div>

        <!-- Admin pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="table-footer" id="pagination-wrap">
          <div class="page-btns">
            <?php if ($page > 1): ?>
            <a href="?tab=admin&page=<?= $page - 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="page-btn">&#8249; Prev</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <a href="?tab=admin&page=<?= $i ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
            <a href="?tab=admin&page=<?= $page + 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="page-btn">Next &#8250;</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

      </div><!-- /.table-card -->

    </div><!-- /content-area -->
  </main>
</div><!-- /layout -->

<!-- ============================================================
     MODAL TAMBAH / EDIT ADMIN
     ============================================================ -->
<div class="modal-overlay" id="adminModal" onclick="closeAdminModal(event)">
  <div class="modal-box" id="adminModalBox">
    <div class="modal-head">
      <div class="modal-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <div>
        <h3 class="modal-title" id="adminModalTitle">Tambah Admin Baru</h3>
        <p class="modal-sub" id="adminModalSub">Isi username dan password akun admin baru</p>
      </div>
      <button class="modal-close" onclick="closeAdminModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="POST" action="" id="formAdmin">
      <input type="hidden" name="action" id="adminModalAction" value="create_admin">
      <input type="hidden" name="user_id" id="adminModalId" value="0">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Username <span class="req">*</span></label>
          <input type="text" name="username" id="adminModalUsername" class="form-input" placeholder="Username admin" maxlength="50" required autocomplete="off">
        </div>
        <div class="form-group mb-0">
          <label class="form-label">Password <span class="req" id="adminPwdReq">*</span></label>
          <input type="password" name="password" id="adminModalPassword" class="form-input" placeholder="Password admin" maxlength="100" autocomplete="new-password">
          <span class="form-hint-ok" id="adminPwdHint" style="display:none">Kosongkan jika tidak ingin mengganti password</span>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeAdminModal()">Batal</button>
        <button type="submit" class="btn-save">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="17 21 17 13 7 13 7 21" stroke="currentColor" stroke-width="2"/><polyline points="7 3 7 8 15 8" stroke="currentColor" stroke-width="2"/></svg>
          <span id="adminModalSaveTxt">Simpan Admin</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     MODAL KONFIRMASI HAPUS ADMIN
     ============================================================ -->
<div class="modal-overlay" id="deleteAdminModal" onclick="closeDeleteAdmin(event)">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <div class="modal-icon danger">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2"/></svg>
      </div>
      <div>
        <h3 class="modal-title">Hapus Akun Admin</h3>
        <p class="modal-sub">Tindakan ini tidak dapat dibatalkan</p>
      </div>
      <button class="modal-close" onclick="closeDeleteAdmin()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="delete-confirm-msg">
        Akun admin ini akan dihapus permanen:<br>
        <strong id="deleteAdminName"></strong>
      </div>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="hapus_admin">
      <input type="hidden" name="user_id" id="deleteAdminId">
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeDeleteAdmin()">Batal</button>
        <button type="submit" class="btn-danger">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          Ya, Hapus
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     MODAL AKUN LOGIN GURU
     ============================================================ -->
<div class="modal-overlay" id="guruModal" onclick="closeGuruModal(event)">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-icon blue">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <div>
        <h3 class="modal-title" id="guruModalTitle">Akun Login Guru</h3>
        <p class="modal-sub" id="guruModalSub">Nama guru</p>
      </div>
      <button class="modal-close" onclick="closeGuruModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="POST" action="" id="formGuru">
      <input type="hidden" name="action" id="guruModalAction" value="create_guru_account">
      <input type="hidden" name="guru_id" id="guruModalId">
      <div class="modal-body">
        <div class="form-group mb-0">
          <label class="form-label">Password <span class="req" id="guruPwdReqSpan" style="display:none">*</span></label>
          <div class="password-input-wrap">
            <input type="password" name="new_password" id="guruModalPwd" class="form-input" placeholder="Password baru (kosongkan = pakai NIP)" maxlength="100" autocomplete="new-password">
            <button type="button" class="password-input-toggle" onclick="togglePasswordInput('guruModalPwd', this)">Lihat</button>
          </div>
          <span class="form-hint-ok" id="guruPwdHint">Username login = NIP guru. Kosongkan password = gunakan NIP sebagai password.</span>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeGuruModal()">Batal</button>
        <button type="button" class="btn-cancel btn-reset-nip" id="guruResetNipBtn" style="display:none" onclick="submitGuruResetToNip()">Reset ke NIP</button>
        <button type="submit" class="btn-save">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="17 21 17 13 7 13 7 21" stroke="currentColor" stroke-width="2"/></svg>
          <span id="guruModalSaveTxt">Buat Akun</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     MODAL KONFIRMASI HAPUS AKUN GURU
     ============================================================ -->
<div class="modal-overlay" id="deleteGuruModal" onclick="closeDeleteGuru(event)">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <div class="modal-icon danger">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2"/></svg>
      </div>
      <div>
        <h3 class="modal-title">Hapus Akun Guru</h3>
        <p class="modal-sub">Guru tidak dapat login setelah dihapus</p>
      </div>
      <button class="modal-close" onclick="closeDeleteGuru()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="delete-confirm-msg">
        Akun login <strong id="deleteGuruName"></strong> akan dihapus.<br>Data guru tetap tersimpan.
      </div>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="hapus_guru_account">
      <input type="hidden" name="guru_id" id="deleteGuruId">
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeDeleteGuru()">Batal</button>
        <button type="submit" class="btn-danger">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          Ya, Hapus
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     MODAL KONFIRMASI RESET SEMUA PASSWORD SISWA & ORANG TUA
     ============================================================ -->
<div class="modal-overlay" id="resetAllPasswordsModal" onclick="closeResetAllPasswordsModal(event)">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <div class="modal-icon danger">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M23 4v6h-6M1 20v-6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <div>
        <h3 class="modal-title">Reset Semua Password</h3>
        <p class="modal-sub">Khusus akun siswa dan orang tua</p>
      </div>
      <button class="modal-close" onclick="closeResetAllPasswordsModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="delete-confirm-msg reset-all-copy">
        Semua password akun <strong>siswa</strong> dan <strong>orang tua</strong> akan direset ke:
        <div class="reset-all-password-badge"><?= htmlspecialchars($managedStudentDefaultPassword) ?></div>
        Password yang sebelumnya sudah diubah user akan tertimpa oleh nilai default ini.
      </div>
    </div>
    <form method="POST" action="" id="resetAllPasswordsForm">
      <input type="hidden" name="action" value="reset_all_student_passwords">
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeResetAllPasswordsModal()">Batal</button>
        <button type="submit" class="btn-danger">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M23 4v6h-6M1 20v-6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          Ya, Reset Semua
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     MODAL AKUN LOGIN SISWA & ORANG TUA
     ============================================================ -->
<div class="modal-overlay" id="siswaModal" onclick="closeSiswaModal(event)">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-icon green">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
      </div>
      <div>
        <h3 class="modal-title" id="siswaModalTitle">Buat Akun Login Siswa</h3>
        <p class="modal-sub" id="siswaModalSub">Nama siswa</p>
      </div>
      <button class="modal-close" onclick="closeSiswaModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="POST" action="" id="formSiswa">
      <input type="hidden" name="action" id="siswaModalAction" value="create_siswa_account">
      <input type="hidden" name="siswa_id" id="siswaModalId">
      <div class="modal-body">

        <!-- Info buat akun baru -->
        <div id="siswaCreateInfo">
          <p style="font-size:0.85rem;color:#94a3b8;margin-bottom:12px;">Akun akan dibuat dengan data berikut. Password default siswa dan orang tua: <strong><?= htmlspecialchars($managedStudentDefaultPassword) ?></strong>.</p>
          <div class="akun-info-box">
            <div class="akun-info-row">
              <span class="akun-info-label">Login Siswa (username)</span>
              <span class="akun-info-pwd" id="siswaBuatNisn">—</span>
            </div>
            <div class="akun-info-row">
              <span class="akun-info-label">Password Siswa</span>
              <span class="akun-info-pwd" id="siswaBuatPwdSiswa">—</span>
            </div>
            <div class="akun-info-divider"></div>
            <div class="akun-info-row">
              <span class="akun-info-label">Login Orang Tua (username)</span>
              <span class="akun-info-pwd" id="siswaBuatOrtUser">—</span>
            </div>
            <div class="akun-info-row">
              <span class="akun-info-label">Password Orang Tua</span>
              <span class="akun-info-pwd" id="siswaBuatPwdOrt">—</span>
            </div>
          </div>
        </div>

        <!-- Form edit password -->
        <div id="siswaEditForm" style="display:none">
          <p class="form-hint-ok" style="display:block;margin-bottom:12px;">Password aktif lama tidak bisa dibaca ulang dari sistem. Gunakan field ini untuk mengganti password baru atau pakai reset agar kembali ke <strong><?= htmlspecialchars($managedStudentDefaultPassword) ?></strong>.</p>
          <div class="form-group">
            <label class="form-label">Password Baru Siswa</label>
            <div class="password-input-wrap">
              <input type="password" name="pwd_siswa" id="siswaPwdSiswaInput" class="form-input" placeholder="Kosongkan jika tidak diubah" maxlength="100" autocomplete="new-password">
              <button type="button" class="password-input-toggle" onclick="togglePasswordInput('siswaPwdSiswaInput', this)">Lihat</button>
            </div>
          </div>
          <div class="form-group mb-0">
            <label class="form-label">Password Baru Orang Tua</label>
            <div class="password-input-wrap">
              <input type="password" name="pwd_ort" id="siswaPwdOrtInput" class="form-input" placeholder="Kosongkan jika tidak diubah" maxlength="100" autocomplete="new-password">
              <button type="button" class="password-input-toggle" onclick="togglePasswordInput('siswaPwdOrtInput', this)">Lihat</button>
            </div>
          </div>
        </div>

      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeSiswaModal()">Batal</button>
        <button type="submit" class="btn-save">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="17 21 17 13 7 13 7 21" stroke="currentColor" stroke-width="2"/></svg>
          <span id="siswaModalSaveTxt">Buat Akun</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     MODAL KONFIRMASI HAPUS AKUN SISWA
     ============================================================ -->
<div class="modal-overlay" id="deleteSiswaModal" onclick="closeDeleteSiswa(event)">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <div class="modal-icon danger">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2"/></svg>
      </div>
      <div>
        <h3 class="modal-title">Hapus Akun Siswa &amp; Orang Tua</h3>
        <p class="modal-sub">Keduanya akan dihapus sekaligus</p>
      </div>
      <button class="modal-close" onclick="closeDeleteSiswa()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="delete-confirm-msg">
        Akun login <strong id="deleteSiswaName"></strong> beserta akun orang tuanya akan dihapus.<br>Data siswa tetap tersimpan.
      </div>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="hapus_siswa_account">
      <input type="hidden" name="siswa_id" id="deleteSiswaId">
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeDeleteSiswa()">Batal</button>
        <button type="submit" class="btn-danger">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          Ya, Hapus
        </button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/users.js?v=20260327-2"></script>

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

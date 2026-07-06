<?php
require_once '../includes/admin_auth.php';
requireAdminLogin();
require_once '../config/database.php';

$conn    = getConnection();
$success = '';
$error   = '';

// Sinkron ringan untuk data lama siswa agar Data Kelas selalu update.
// - Jika siswa sudah punya wali_kelas_id, pastikan teks kelas mengikuti kaih_kelas.
// - Jika wali_kelas_id kosong tapi kolom kelas terisi, coba hubungkan berdasarkan nama kelas (toleransi 'Kelas 7A' vs '7A').
try {
  $conn->query(
    "UPDATE siswa s\n" .
    "JOIN kaih_kelas k ON k.id = s.wali_kelas_id\n" .
    "SET s.kelas = k.nama_kelas, s.updated_at = NOW()\n" .
    "WHERE s.wali_kelas_id IS NOT NULL AND s.wali_kelas_id != 0\n" .
    "  AND (s.kelas IS NULL OR s.kelas = '' OR s.kelas <> k.nama_kelas)"
  );

  $conn->query(
    "UPDATE siswa s\n" .
    "JOIN kaih_kelas k\n" .
    "  ON TRIM(REPLACE(LOWER(s.kelas), 'kelas ', '')) = TRIM(REPLACE(LOWER(k.nama_kelas), 'kelas ', ''))\n" .
    "SET s.wali_kelas_id = k.id, s.kelas = k.nama_kelas, s.updated_at = NOW()\n" .
    "WHERE (s.wali_kelas_id IS NULL OR s.wali_kelas_id = 0)\n" .
    "  AND s.kelas IS NOT NULL AND s.kelas <> ''"
  );
} catch (Throwable $e) {
  // Abaikan jika DB tidak mendukung query tertentu; halaman tetap bisa berjalan.
}

/* ============================================================
   HANDLE POST ACTIONS
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ---- TAMBAH & EDIT ---- */
    if ($action === 'simpan') {
        $id         = intval($_POST['id'] ?? 0);
        $nama_kelas = trim($_POST['nama_kelas']);

        if (empty($nama_kelas)) {
            $error = 'Nama kelas wajib diisi.';
        } else {
            if ($id === 0) {
                // Cek duplikat
                $cek = $conn->prepare("SELECT id FROM kaih_kelas WHERE nama_kelas = ?");
                $cek->bind_param('s', $nama_kelas);
                $cek->execute();
                if ($cek->get_result()->num_rows > 0) {
                    $error = 'Nama kelas sudah ada. Gunakan nama yang berbeda.';
                } else {
                    $stmt = $conn->prepare("INSERT INTO kaih_kelas (nama_kelas, created_at, updated_at) VALUES (?, NOW(), NOW())");
                    $stmt->bind_param('s', $nama_kelas);
                    $stmt->execute() ? $success = "Kelas \"$nama_kelas\" berhasil ditambahkan." : $error = 'Gagal menambahkan kelas.';
                    $stmt->close();
                }
                $cek->close();
            } else {
                $cek = $conn->prepare("SELECT id FROM kaih_kelas WHERE nama_kelas = ? AND id != ?");
                $cek->bind_param('si', $nama_kelas, $id);
                $cek->execute();
                if ($cek->get_result()->num_rows > 0) {
                    $error = 'Nama kelas sudah digunakan kelas lain.';
                } else {
                    $stmt = $conn->prepare("UPDATE kaih_kelas SET nama_kelas=?, updated_at=NOW() WHERE id=?");
                    $stmt->bind_param('si', $nama_kelas, $id);
                    $stmt->execute() ? $success = "Kelas \"$nama_kelas\" berhasil diperbarui." : $error = 'Gagal memperbarui kelas.';
                    $stmt->close();
                }
                $cek->close();
            }
        }
    }

    /* ---- HAPUS ---- */
    if ($action === 'hapus') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            // Cek apakah ada siswa yang masih pakai kelas ini
            $cek = $conn->prepare("SELECT COUNT(*) as c FROM siswa WHERE wali_kelas_id = ?");
            $cek->bind_param('i', $id);
            $cek->execute();
            $jml = $cek->get_result()->fetch_assoc()['c'];
            $cek->close();

            if ($jml > 0) {
                $error = "Tidak dapat menghapus kelas ini karena masih digunakan oleh $jml siswa.";
            } else {
                $stmt = $conn->prepare("DELETE FROM kaih_kelas WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute() ? $success = 'Kelas berhasil dihapus.' : $error = 'Gagal menghapus kelas.';
                $stmt->close();
            }
        }
    }
}

/* ============================================================
   FETCH DATA
   ============================================================ */
$search   = trim($_GET['q'] ?? '');
$per_page = 12;
$page     = max(1, intval($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

$where  = $search ? "WHERE k.nama_kelas LIKE ?" : '';
$params = $search ? ["%$search%"] : [];
$types  = $search ? 's' : '';

// Total
$stmtC = $conn->prepare("SELECT COUNT(*) as total FROM kaih_kelas k $where");
if ($types) $stmtC->bind_param($types, ...$params);
$stmtC->execute();
$total_rows  = $stmtC->get_result()->fetch_assoc()['total'];
$stmtC->close();
$total_pages = ceil($total_rows / $per_page);

// Data kelas + jumlah siswa + nama wali kelas dari tabel guru
$sql = "SELECT k.*,
        COUNT(DISTINCT s.id) AS jumlah_siswa,
        GROUP_CONCAT(DISTINCT g.nama_guru ORDER BY g.nama_guru SEPARATOR ', ') AS nama_wali
        FROM kaih_kelas k
        LEFT JOIN siswa s ON s.wali_kelas_id = k.id
  LEFT JOIN guru g ON TRIM(REPLACE(LOWER(g.kelas), 'kelas ', '')) = TRIM(REPLACE(LOWER(k.nama_kelas), 'kelas ', ''))
        $where
        GROUP BY k.id
        ORDER BY k.nama_kelas
        LIMIT ? OFFSET ?";
$stmtD = $conn->prepare($sql);
$allP  = array_merge($params, [$per_page, $offset]);
$allT  = $types . 'ii';
$stmtD->bind_param($allT, ...$allP);
$stmtD->execute();
$kelasList = $stmtD->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtD->close();

// Stats
$totalKelas = $conn->query("SELECT COUNT(*) as c FROM kaih_kelas")->fetch_assoc()['c'];
$totalSiswa = $conn->query("SELECT COUNT(*) as c FROM siswa")->fetch_assoc()['c'];
$conn->close();

$adminName = htmlspecialchars($_SESSION['admin_username']);

// Warna badge kelas otomatis berdasarkan index
$colors = ['purple','blue','teal','orange','pink','green','cyan','amber'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<script>(function(){try{var t=localStorage.getItem('kaih_theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();function toggleTheme(){var h=document.documentElement,d=h.getAttribute('data-theme')==='dark';if(d)h.removeAttribute('data-theme');else h.setAttribute('data-theme','dark');try{localStorage.setItem('kaih_theme',d?'light':'dark');}catch(e){}}</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Kelas — KAIH Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=20260316f">
<link rel="stylesheet" href="../assets/css/siswa.css?v=20260316f">
<link rel="stylesheet" href="../assets/css/kelas.css?v=20260316f">
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
            <rect width="40" height="40" rx="10" fill="url(#lgK)"/>
            <path d="M10 20L17 13L24 20L17 27L10 20Z" fill="white"/>
            <path d="M18 20L25 13L32 20L25 27L18 20Z" fill="white" fill-opacity=".6"/>
            <defs><linearGradient id="lgK" x1="0" y1="0" x2="40" y2="40"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs>
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
      <a href="kelas.php" class="nav-item active">
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
        <h1 class="page-title">Data Kelas</h1>
        <p class="page-sub">Kelola data kelas &mdash; total <strong><?= $totalKelas ?> kelas</strong>, <strong><?= $totalSiswa ?> siswa</strong> terdaftar</p>
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

      <!-- Alerts -->
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
            <input type="text" name="q" id="searchInput" placeholder="Cari nama kelas..." value="<?= htmlspecialchars($search) ?>" class="search-input" autocomplete="off">
            <?php if ($search): ?><a href="kelas.php" class="search-clear">×</a><?php endif; ?>
          </div>
        </form>
        <button class="btn-primary" onclick="openModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
          Tambah Kelas
        </button>
      </div>

      <!-- Kelas Cards Grid -->
      <?php if (empty($kelasList)): ?>
      <div class="kelas-empty">
        <div class="empty-wrap">
          <svg width="60" height="60" viewBox="0 0 24 24" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="1.5"/><polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="1.5"/></svg>
          <p><?= $search ? "Tidak ada kelas dengan nama \"$search\"." : 'Belum ada data kelas. Klik "Tambah Kelas" untuk memulai.' ?></p>
        </div>
      </div>
      <?php else: ?>
      <div class="kelas-grid" id="kelasGrid">
        <?php foreach ($kelasList as $i => $k):
          $color = $colors[$i % count($colors)];
        ?>
        <div class="kelas-card kelas-card-<?= $color ?>" data-id="<?= $k['id'] ?>">
          <div class="kelas-card-top">
            <div class="kelas-icon kelas-icon-<?= $color ?>">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/><polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2"/></svg>
            </div>
            <div class="kelas-actions">
              <button class="btn-edit-sm" title="Edit Kelas"
                onclick="openEdit(<?= $k['id'] ?>, '<?= addslashes($k['nama_kelas']) ?>')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              </button>
              <button class="btn-delete-sm" title="Hapus Kelas"
                onclick="confirmDelete(<?= $k['id'] ?>, '<?= addslashes($k['nama_kelas']) ?>', <?= $k['jumlah_siswa'] ?>)">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              </button>
            </div>
          </div>

          <div class="kelas-name"><?= htmlspecialchars($k['nama_kelas']) ?></div>

          <div class="kelas-stats">
            <div class="kelas-stat">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              <span><strong><?= $k['jumlah_siswa'] ?></strong> Siswa</span>
            </div>
            <div class="kelas-stat">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
              <span><?= $k['nama_wali'] ? htmlspecialchars(mb_strimwidth($k['nama_wali'], 0, 22, '...')) : '<em>Belum ada wali</em>' ?></span>
            </div>
          </div>

          <div class="kelas-footer">
            <span class="kelas-date">Dibuat <?= date('d M Y', strtotime($k['created_at'])) ?></span>
            <a href="siswa.php?q=<?= urlencode($k['nama_kelas']) ?>" class="kelas-link" title="Lihat siswa kelas ini">
              Lihat Siswa
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
      <div class="pagination" style="margin-top:20px;">
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
      <?php endif; ?>

    </div><!-- /content-area -->
  </main>
</div>

<!-- ============================================================
     MODAL TAMBAH / EDIT KELAS
     ============================================================ -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
  <div class="modal-box modal-kelas" id="modalBox">
    <div class="modal-head">
      <div class="modal-icon teal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/><polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2"/></svg>
      </div>
      <div>
        <h3 class="modal-title" id="modalTitle">Tambah Kelas Baru</h3>
        <p class="modal-sub" id="modalSub">Masukkan nama kelas yang akan ditambahkan</p>
      </div>
      <button class="modal-close" onclick="closeModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="POST" action="" id="formKelas">
      <input type="hidden" name="action" value="simpan">
      <input type="hidden" name="id" id="fieldId" value="0">
      <div class="modal-body">
        <div class="form-group mb-0">
          <label class="form-label">Nama Kelas <span class="req">*</span></label>
          <input type="text" name="nama_kelas" id="fieldNamaKelas" class="form-input form-input-lg" placeholder="Contoh: Kelas 7A, Kelas 8B, Kelas 9C" maxlength="50" required>
          <span class="form-hint-ok">Nama kelas harus unik dan belum terdaftar</span>
        </div>

        <!-- Quick Add Buttons -->
        <div class="quick-add">
          <span class="quick-label">Tambah cepat:</span>
          <div class="quick-chips">
            <?php
            $quickKelas = ['7A','7B','7C','8A','8B','8C','9A','9B','9C'];
            foreach ($quickKelas as $qk):
            ?>
            <button type="button" class="quick-chip" onclick="setKelas('Kelas <?= $qk ?>')">Kelas <?= $qk ?></button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn-save btn-teal" id="btnSave">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="17 21 17 13 7 13 7 21" stroke="currentColor" stroke-width="2"/><polyline points="7 3 7 8 15 8" stroke="currentColor" stroke-width="2"/></svg>
          <span id="btnSaveText">Simpan Kelas</span>
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
        <h3 class="modal-title">Hapus Kelas</h3>
        <p class="modal-sub">Tindakan ini tidak dapat dibatalkan</p>
      </div>
      <button class="modal-close" onclick="closeDelete()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="delete-confirm-msg">
        Apakah Anda yakin ingin menghapus:<br>
        <strong id="deleteName"></strong>
        <div id="deleteWarning" class="delete-warning" style="display:none"></div>
      </div>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="hapus">
      <input type="hidden" name="id" id="deleteId">
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeDelete()">Batal</button>
        <button type="submit" class="btn-danger" id="btnDeleteConfirm">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          Ya, Hapus
        </button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/kelas.js"></script>

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

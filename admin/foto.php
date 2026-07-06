<?php
declare(strict_types=1);

require_once '../includes/admin_auth.php';
requireAdminLogin();
require_once '../config/database.php';

$conn = getConnection();
$username = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');

// Auto-create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS foto_slideshow (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    judul VARCHAR(255) DEFAULT '',
    urutan INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$uploadDir = __DIR__ . '/../assets/img/slideshow/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$success = '';
$error   = '';

// ---------- HANDLE POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CSRF check
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $error = 'Token CSRF tidak valid.';
    } else {
        // UPLOAD
        if ($action === 'upload' && !$error) {
            if (!isset($_FILES['foto']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
                $error = 'Pilih file foto terlebih dahulu.';
            } else {
                $files = $_FILES['foto'];
                // Normalize to array for multiple upload
                if (!is_array($files['name'])) {
                    $files = [
                        'name'     => [$files['name']],
                        'tmp_name' => [$files['tmp_name']],
                        'error'    => [$files['error']],
                        'size'     => [$files['size']],
                        'type'     => [$files['type']],
                    ];
                }

                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                $uploaded = 0;

                // Get max urutan
                $maxUrutan = (int)($conn->query("SELECT COALESCE(MAX(urutan),0) FROM foto_slideshow")->fetch_row()[0] ?? 0);

                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

                    // Validate MIME via finfo
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($files['tmp_name'][$i]);
                    if (!in_array($mime, $allowedTypes, true)) {
                        $error = 'Format file tidak didukung: ' . htmlspecialchars($files['name'][$i]);
                        continue;
                    }
                    if ($files['size'][$i] > $maxSize) {
                        $error = 'Ukuran file terlalu besar (maks 5MB): ' . htmlspecialchars($files['name'][$i]);
                        continue;
                    }

                    $ext = match($mime) {
                        'image/jpeg' => '.jpg',
                        'image/png'  => '.png',
                        'image/webp' => '.webp',
                        'image/gif'  => '.gif',
                        default      => '.jpg',
                    };
                    $newName = 'slide_' . time() . '_' . bin2hex(random_bytes(4)) . $ext;
                    $dest = $uploadDir . $newName;

                    if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                        $maxUrutan++;
                        $judul = trim($_POST['judul'] ?? '');
                        $stmt = $conn->prepare("INSERT INTO foto_slideshow (filename, judul, urutan) VALUES (?, ?, ?)");
                        $stmt->bind_param('ssi', $newName, $judul, $maxUrutan);
                        $stmt->execute();
                        $stmt->close();
                        $uploaded++;
                    }
                }
                if ($uploaded > 0 && !$error) {
                    $success = "$uploaded foto berhasil diupload.";
                }
            }
        }

        // DELETE
        if ($action === 'delete' && !$error) {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("SELECT filename FROM foto_slideshow WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();

                if ($row) {
                    $filePath = $uploadDir . $row['filename'];
                    if (is_file($filePath)) {
                        unlink($filePath);
                    }
                    $stmt = $conn->prepare("DELETE FROM foto_slideshow WHERE id = ?");
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $stmt->close();
                    $success = 'Foto berhasil dihapus.';
                } else {
                    $error = 'Foto tidak ditemukan.';
                }
            }
        }

        // BULK DELETE
        if ($action === 'bulk_delete' && !$error) {
            $ids = $_POST['ids'] ?? '';
            $idArr = array_filter(array_map('intval', explode(',', $ids)));
            if (empty($idArr)) {
                $error = 'Pilih foto yang akan dihapus.';
            } else {
                $placeholders = implode(',', array_fill(0, count($idArr), '?'));
                $types = str_repeat('i', count($idArr));

                // Get filenames first
                $stmt = $conn->prepare("SELECT filename FROM foto_slideshow WHERE id IN ($placeholders)");
                $stmt->bind_param($types, ...$idArr);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $filePath = $uploadDir . $row['filename'];
                    if (is_file($filePath)) {
                        unlink($filePath);
                    }
                }
                $stmt->close();

                $stmt = $conn->prepare("DELETE FROM foto_slideshow WHERE id IN ($placeholders)");
                $stmt->bind_param($types, ...$idArr);
                $stmt->execute();
                $deleted = $stmt->affected_rows;
                $stmt->close();
                $success = "$deleted foto berhasil dihapus.";
            }
        }

        // UPDATE JUDUL
        if ($action === 'update_judul' && !$error) {
            $id = (int)($_POST['id'] ?? 0);
            $judul = trim($_POST['judul'] ?? '');
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE foto_slideshow SET judul = ? WHERE id = ?");
                $stmt->bind_param('si', $judul, $id);
                $stmt->execute();
                $stmt->close();
                $success = 'Judul berhasil diperbarui.';
            }
        }

        // REORDER
        if ($action === 'reorder' && !$error) {
            $order = $_POST['order'] ?? '';
            $ids = array_filter(array_map('intval', explode(',', $order)));
            $pos = 1;
            foreach ($ids as $id) {
                $stmt = $conn->prepare("UPDATE foto_slideshow SET urutan = ? WHERE id = ?");
                $stmt->bind_param('ii', $pos, $id);
                $stmt->execute();
                $stmt->close();
                $pos++;
            }
            $success = 'Urutan foto berhasil diperbarui.';
        }
    }

    // PRG redirect
    if ($success) $_SESSION['flash_success'] = $success;
    if ($error) $_SESSION['flash_error'] = $error;
    header('Location: foto.php');
    exit;
}

// Flash messages
if (isset($_SESSION['flash_success'])) { $success = $_SESSION['flash_success']; unset($_SESSION['flash_success']); }
if (isset($_SESSION['flash_error']))   { $error = $_SESSION['flash_error'];     unset($_SESSION['flash_error']); }

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load photos
$photos = [];
$result = $conn->query("SELECT * FROM foto_slideshow ORDER BY urutan ASC, id ASC");
while ($row = $result->fetch_assoc()) {
    $photos[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <script>(function(){try{var t=localStorage.getItem('kaih_theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();function toggleTheme(){var h=document.documentElement,d=h.getAttribute('data-theme')==='dark';if(d)h.removeAttribute('data-theme');else h.setAttribute('data-theme','dark');try{localStorage.setItem('kaih_theme',d?'light':'dark');}catch(e){}}</script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Foto Slideshow — KAIH Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="../assets/css/siswa.css">
  <link rel="stylesheet" href="../assets/css/foto.css">
</head>
<body>
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
            <rect width="40" height="40" rx="10" fill="url(#lgDash)"/>
            <path d="M10 20L17 13L24 20L17 27L10 20Z" fill="white"/>
            <path d="M18 20L25 13L32 20L25 27L18 20Z" fill="white" fill-opacity=".6"/>
            <defs><linearGradient id="lgDash" x1="0" y1="0" x2="40" y2="40"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs>
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
      <a href="foto.php" class="nav-item active">
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

  <main class="main-content">

    <header class="topbar">
      <div class="topbar-left">
        <h1 class="page-title">Foto Slideshow</h1>
        <p class="page-sub">Kelola foto yang tampil di halaman depan website</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-date">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/><line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/><line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/></svg>
          <?= date('d M Y') ?>
        </div>
        <div class="admin-chip"><div class="admin-dot"></div>Admin</div>
      </div>
    </header>

    <?php if ($success): ?>
    <div class="flash flash-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="foto-upload-card">
      <div class="upload-header">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="17 8 12 3 7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="3" x2="12" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span>Upload Foto Baru</span>
      </div>
      <form method="POST" enctype="multipart/form-data" class="upload-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="upload">
        <div class="upload-dropzone" id="dropzone">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" class="dropzone-icon">
            <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="1.5"/>
            <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <div class="dropzone-text">Seret foto ke sini atau <label for="fotoInput" class="dropzone-browse">pilih file</label></div>
          <div class="dropzone-hint">JPG, PNG, WebP &bull; Maks 5MB per file</div>
          <input type="file" name="foto[]" id="fotoInput" multiple accept="image/jpeg,image/png,image/webp,image/gif" class="drop-input">
          <div class="dropzone-preview" id="dropPreview"></div>
        </div>
        <div class="upload-row">
          <input type="text" name="judul" placeholder="Judul / keterangan (opsional)" class="upload-judul">
          <button type="submit" class="btn-primary btn-upload">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><polyline points="17 8 12 3 7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="3" x2="12" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Upload
          </button>
        </div>
      </form>
    </div>

    <!-- Photos Grid -->
    <div class="foto-section">
      <div class="foto-section-header">
        <h2 class="foto-section-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="2"/><path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Daftar Foto (<?= count($photos) ?>)
        </h2>
        <div class="foto-actions-top">
          <button type="button" class="btn-outline btn-sm" id="btnSelectAll" onclick="toggleSelectAll()" style="display:<?= count($photos) > 0 ? 'inline-flex' : 'none' ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Pilih Semua
          </button>
          <form method="POST" id="frmBulkDelete" onsubmit="return confirmBulkDelete()" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="bulk_delete">
            <input type="hidden" name="ids" id="bulkIds" value="">
            <button type="submit" class="btn-danger btn-sm" id="btnBulkDelete" style="display:none">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2"/></svg>
              Hapus Terpilih (<span id="selectedCount">0</span>)
            </button>
          </form>
        </div>
      </div>

      <?php if (empty($photos)): ?>
      <div class="foto-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" opacity="0.35">
          <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
          <circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="1.5"/>
          <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <p>Belum ada foto. Upload foto pertama di atas.</p>
      </div>
      <?php else: ?>
      <div class="foto-grid" id="fotoGrid">
        <?php foreach ($photos as $i => $photo): ?>
        <div class="foto-card" data-id="<?= $photo['id'] ?>">
          <div class="foto-select">
            <input type="checkbox" class="foto-cb" value="<?= $photo['id'] ?>" onchange="updateBulkUI()">
          </div>
          <div class="foto-img-wrap">
            <img src="../assets/img/slideshow/<?= htmlspecialchars($photo['filename']) ?>" alt="<?= htmlspecialchars($photo['judul'] ?: 'Slide ' . ($i+1)) ?>" loading="lazy">
            <div class="foto-overlay">
              <span class="foto-order">#<?= $i + 1 ?></span>
            </div>
          </div>
          <div class="foto-info">
            <div class="foto-title"><?= htmlspecialchars($photo['judul'] ?: 'Slide ' . ($i+1)) ?></div>
            <div class="foto-meta"><?= date('d M Y', strtotime($photo['created_at'])) ?></div>
          </div>
          <div class="foto-card-actions">
            <button type="button" class="btn-icon btn-edit" title="Edit Judul" onclick="editJudul(<?= $photo['id'] ?>, this)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2"/></svg>
            </button>
            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus foto ini?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $photo['id'] ?>">
              <button type="submit" class="btn-icon btn-delete" title="Hapus">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2"/></svg>
              </button>
            </form>
            <button type="button" class="btn-icon btn-drag" title="Drag untuk urutkan">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="9" cy="6" r="1" fill="currentColor"/><circle cx="15" cy="6" r="1" fill="currentColor"/><circle cx="9" cy="12" r="1" fill="currentColor"/><circle cx="15" cy="12" r="1" fill="currentColor"/><circle cx="9" cy="18" r="1" fill="currentColor"/><circle cx="15" cy="18" r="1" fill="currentColor"/></svg>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Save order button, hidden by default -->
      <form method="POST" id="frmReorder" style="display:none; margin-top:12px; text-align:center;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="reorder">
        <input type="hidden" name="order" id="orderInput" value="">
        <button type="submit" class="btn-primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/></svg>
          Simpan Urutan
        </button>
      </form>
      <?php endif; ?>
    </div>

    <!-- Edit Judul Modal -->
    <div class="modal-overlay" id="modalEditJudul">
      <div class="modal-box-sm">
        <div class="modal-header-sm">
          <h3>Edit Judul Foto</h3>
          <button type="button" class="modal-close-sm" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" id="frmEditJudul">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="hidden" name="action" value="update_judul">
          <input type="hidden" name="id" id="editId" value="">
          <div class="form-group-sm">
            <label>Judul / Keterangan</label>
            <input type="text" name="judul" id="editJudulInput" class="form-input-sm" placeholder="Masukkan judul...">
          </div>
          <div class="modal-actions-sm">
            <button type="button" class="btn-outline btn-sm" onclick="closeEditModal()">Batal</button>
            <button type="submit" class="btn-primary btn-sm">Simpan</button>
          </div>
        </form>
      </div>
    </div>

  </main>
</div>

<script>
// Dropzone behavior
const dropzone = document.getElementById('dropzone');
const fotoInput = document.getElementById('fotoInput');
const preview = document.getElementById('dropPreview');

if (dropzone) {
  ['dragenter','dragover'].forEach(e => {
    dropzone.addEventListener(e, ev => { ev.preventDefault(); dropzone.classList.add('drag-over'); });
  });
  ['dragleave','drop'].forEach(e => {
    dropzone.addEventListener(e, ev => { ev.preventDefault(); dropzone.classList.remove('drag-over'); });
  });
  dropzone.addEventListener('drop', ev => {
    fotoInput.files = ev.dataTransfer.files;
    showPreview(ev.dataTransfer.files);
  });
  fotoInput.addEventListener('change', () => showPreview(fotoInput.files));
}

function showPreview(files) {
  preview.innerHTML = '';
  Array.from(files).forEach(f => {
    if (!f.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = e => {
      const div = document.createElement('div');
      div.className = 'preview-thumb';
      div.innerHTML = '<img src="' + e.target.result + '"><span>' + f.name + '</span>';
      preview.appendChild(div);
    };
    reader.readAsDataURL(f);
  });
}

// Bulk select
function toggleSelectAll() {
  const cbs = document.querySelectorAll('.foto-cb');
  const allChecked = Array.from(cbs).every(c => c.checked);
  cbs.forEach(c => c.checked = !allChecked);
  updateBulkUI();
}

function updateBulkUI() {
  const cbs = document.querySelectorAll('.foto-cb:checked');
  const count = cbs.length;
  document.getElementById('btnBulkDelete').style.display = count > 0 ? 'inline-flex' : 'none';
  document.getElementById('selectedCount').textContent = count;
  const ids = Array.from(cbs).map(c => c.value).join(',');
  document.getElementById('bulkIds').value = ids;
}

function confirmBulkDelete() {
  const count = document.querySelectorAll('.foto-cb:checked').length;
  return count > 0 && confirm('Hapus ' + count + ' foto yang dipilih?');
}

// Edit judul modal
function editJudul(id, btn) {
  const card = btn.closest('.foto-card');
  const title = card.querySelector('.foto-title').textContent.trim();
  document.getElementById('editId').value = id;
  document.getElementById('editJudulInput').value = title.startsWith('Slide ') ? '' : title;
  document.getElementById('modalEditJudul').classList.add('active');
  setTimeout(() => document.getElementById('editJudulInput').focus(), 100);
}
function closeEditModal() {
  document.getElementById('modalEditJudul').classList.remove('active');
}
document.getElementById('modalEditJudul').addEventListener('click', function(e) {
  if (e.target === this) closeEditModal();
});

// Drag-and-drop reorder
const grid = document.getElementById('fotoGrid');
if (grid) {
  let dragCard = null;
  grid.querySelectorAll('.foto-card').forEach(card => {
    const handle = card.querySelector('.btn-drag');
    handle.addEventListener('mousedown', () => { card.setAttribute('draggable', 'true'); });
    card.addEventListener('dragstart', e => {
      dragCard = card;
      card.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    card.addEventListener('dragend', () => {
      card.classList.remove('dragging');
      card.removeAttribute('draggable');
      dragCard = null;
      updateOrder();
    });
    card.addEventListener('dragover', e => {
      e.preventDefault();
      if (!dragCard || dragCard === card) return;
      const rect = card.getBoundingClientRect();
      const mid = rect.left + rect.width / 2;
      if (e.clientX < mid) {
        grid.insertBefore(dragCard, card);
      } else {
        grid.insertBefore(dragCard, card.nextSibling);
      }
    });
  });
}

function updateOrder() {
  const cards = document.querySelectorAll('.foto-card');
  const ids = Array.from(cards).map(c => c.dataset.id).join(',');
  document.getElementById('orderInput').value = ids;
  document.getElementById('frmReorder').style.display = 'block';
  // Update order numbers
  cards.forEach((c, i) => {
    const orderSpan = c.querySelector('.foto-order');
    if (orderSpan) orderSpan.textContent = '#' + (i + 1);
  });
}

// Auto-dismiss flash
document.querySelectorAll('.flash').forEach(el => {
  setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateY(-10px)'; setTimeout(() => el.remove(), 300); }, 4000);
});
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

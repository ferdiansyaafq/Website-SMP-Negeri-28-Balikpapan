<?php
// aplikasi/admin/berita.php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/site_images.php';

// Cek hak akses admin
$isAdminSession = (isset($_SESSION['user_id']) && (string)($_SESSION['role'] ?? $_SESSION['portal_role'] ?? '') === 'admin')
    || (isset($_SESSION['admin_id']) && (string)($_SESSION['admin_role'] ?? '') === 'admin');
if (!$isAdminSession) {
    header('Location: ../../login.php');
    exit;
}

// Pastikan tabel berita tersedia di database
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `berita` (
          `id` int NOT NULL AUTO_INCREMENT,
          `jenis` enum('berita','pengumuman') NOT NULL DEFAULT 'berita',
          `tanggal` date NOT NULL,
          `kategori` varchar(50) NOT NULL DEFAULT 'Pengumuman',
          `judul` varchar(255) NOT NULL,
          `ringkasan` varchar(500) NOT NULL,
          `isi` text NOT NULL,
          `gambar` varchar(255) DEFAULT NULL,
          `status` enum('draft','terbit') NOT NULL DEFAULT 'terbit',
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_berita_publikasi` (`status`, `jenis`, `tanggal`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {
    // Abaikan jika tabel sudah ada
}

$flash = '';
$flashType = 'success';
$uploadDir = '../../assets/img/berita/';
$allowedKategori = ['Pengumuman', 'Akademik', 'Prestasi'];

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/**
 * Helper upload gambar berita
 */
function uploadGambarBerita(array $file, string $uploadDir, ?string &$errorMsg = null): ?string
{
    if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errorMsg = 'Gagal mengunggah file gambar (kode error: ' . (int)$file['error'] . ').';
        return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowedExts, true)) {
        $errorMsg = 'Format gambar tidak didukung. Gunakan JPG, JPEG, PNG, atau WEBP.';
        return null;
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $safeBase = trim($safeBase, '_');
    if ($safeBase === '') {
        $safeBase = 'image';
    }

    $fileName = 'berita_' . time() . '_' . substr(md5(uniqid('', true)), 0, 6) . '_' . $safeBase . '.' . $ext;
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $errorMsg = 'Gagal menyimpan file gambar ke folder server.';
        return null;
    }

    return 'assets/img/berita/' . $fileName;
}

// Proses CRUD (Tambah, Edit, Hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $judul     = trim($_POST['judul'] ?? '');
        $tanggal   = trim($_POST['tanggal'] ?? date('Y-m-d'));
        $kategori  = trim($_POST['kategori'] ?? 'Pengumuman');
        $ringkasan = trim($_POST['ringkasan'] ?? '');
        $isi       = trim($_POST['isi'] ?? '');
        $status    = 'terbit';

        if (!in_array($kategori, $allowedKategori, true)) {
            $kategori = 'Pengumuman';
        }
        $jenis = (strcasecmp($kategori, 'Pengumuman') === 0) ? 'pengumuman' : 'berita';

        // Jika ringkasan kosong namun isi ada, ambil otomatis dari isi
        if ($ringkasan === '' && $isi !== '') {
            $ringkasan = mb_substr($isi, 0, 220);
        }
        // Jika isi kosong namun ringkasan ada, gunakan ringkasan sebagai isi
        if ($isi === '' && $ringkasan !== '') {
            $isi = $ringkasan;
        }

        $uploadError = null;
        $gambar = null;
        if (!empty($_FILES['gambar']['name'])) {
            $gambar = uploadGambarBerita($_FILES['gambar'], $uploadDir, $uploadError);
        }

        if ($uploadError !== null) {
            $flash = $uploadError;
            $flashType = 'error';
        } elseif ($judul === '' || $tanggal === '' || $ringkasan === '') {
            $flash = 'Nama Berita, Tanggal, Tipe Berita, dan Ringkasan/Isi wajib diisi!';
            $flashType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO berita (jenis, tanggal, kategori, judul, ringkasan, isi, gambar, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$jenis, $tanggal, $kategori, $judul, $ringkasan, $isi, $gambar, $status]);
                $flash = 'Berita "' . $judul . '" berhasil ditambahkan dan ditampilkan pada website!';
            } catch (PDOException $e) {
                $flash = 'Gagal menambahkan berita: ' . $e->getMessage();
                $flashType = 'error';
            }
        }
    } elseif ($action === 'edit') {
        $id          = (int)($_POST['id'] ?? 0);
        $judul       = trim($_POST['judul'] ?? '');
        $tanggal     = trim($_POST['tanggal'] ?? date('Y-m-d'));
        $kategori    = trim($_POST['kategori'] ?? 'Pengumuman');
        $ringkasan   = trim($_POST['ringkasan'] ?? '');
        $isi         = trim($_POST['isi'] ?? '');
        $status      = 'terbit';
        $gambar_lama = trim($_POST['gambar_lama'] ?? '');
        $gambar      = $gambar_lama !== '' ? $gambar_lama : null;

        if (!in_array($kategori, $allowedKategori, true)) {
            $kategori = 'Pengumuman';
        }
        $jenis = (strcasecmp($kategori, 'Pengumuman') === 0) ? 'pengumuman' : 'berita';

        if ($ringkasan === '' && $isi !== '') {
            $ringkasan = mb_substr($isi, 0, 220);
        }
        if ($isi === '' && $ringkasan !== '') {
            $isi = $ringkasan;
        }

        $uploadError = null;
        if (!empty($_FILES['gambar']['name'])) {
            $newGambar = uploadGambarBerita($_FILES['gambar'], $uploadDir, $uploadError);
            if ($newGambar !== null) {
                // Hapus file upload lama jika merupakan file hasil upload (berita_*)
                if ($gambar_lama !== '' && str_starts_with(basename($gambar_lama), 'berita_') && is_file('../../' . $gambar_lama)) {
                    @unlink('../../' . $gambar_lama);
                }
                $gambar = $newGambar;
            }
        }

        if ($uploadError !== null) {
            $flash = $uploadError;
            $flashType = 'error';
        } elseif ($id <= 0 || $judul === '' || $tanggal === '' || $ringkasan === '') {
            $flash = 'Nama Berita, Tanggal, Tipe Berita, dan Ringkasan/Isi wajib diisi!';
            $flashType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE berita
                    SET jenis = ?, tanggal = ?, kategori = ?, judul = ?, ringkasan = ?, isi = ?, gambar = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$jenis, $tanggal, $kategori, $judul, $ringkasan, $isi, $gambar, $status, $id]);
                $flash = 'Data berita berhasil diperbarui!';
            } catch (PDOException $e) {
                $flash = 'Gagal memperbarui berita: ' . $e->getMessage();
                $flashType = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmtCek = $pdo->prepare("SELECT gambar FROM berita WHERE id = ?");
                $stmtCek->execute([$id]);
                $row = $stmtCek->fetch(PDO::FETCH_ASSOC);

                if ($row && !empty($row['gambar'])) {
                    $oldPath = $row['gambar'];
                    if (str_starts_with(basename($oldPath), 'berita_') && is_file('../../' . $oldPath)) {
                        @unlink('../../' . $oldPath);
                    }
                }

                $stmtDel = $pdo->prepare("DELETE FROM berita WHERE id = ?");
                $stmtDel->execute([$id]);
                $flash = 'Berita berhasil dihapus dari sistem!';
            } catch (PDOException $e) {
                $flash = 'Gagal menghapus berita.';
                $flashType = 'error';
            }
        }
    }
}

// Cari ID berita yang saat ini tampil di halaman utama (Berita Terkini di index.php)
$featuredIds = [];
try {
    $stmtFeatured = $pdo->query("
        (SELECT id FROM berita WHERE status = 'terbit' AND kategori = 'Pengumuman' ORDER BY tanggal DESC, id DESC LIMIT 1)
        UNION ALL
        (SELECT id FROM berita WHERE status = 'terbit' AND kategori = 'Akademik' ORDER BY tanggal DESC, id DESC LIMIT 1)
        UNION ALL
        (SELECT id FROM berita WHERE status = 'terbit' AND kategori = 'Prestasi' ORDER BY tanggal DESC, id DESC LIMIT 1)
    ");
    $featuredIds = array_map('intval', $stmtFeatured->fetchAll(PDO::FETCH_COLUMN));
} catch (PDOException $e) {
    $featuredIds = [];
}

// Filter & Pencarian
$filterKategori = trim($_GET['kategori'] ?? '');
$searchQuery    = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM berita WHERE 1=1";
$params = [];

if ($filterKategori !== '' && in_array($filterKategori, $allowedKategori, true)) {
    $sql .= " AND kategori = ?";
    $params[] = $filterKategori;
}
if ($searchQuery !== '') {
    $sql .= " AND (judul LIKE ? OR ringkasan LIKE ? OR isi LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY tanggal DESC, id DESC";
$stmtList = $pdo->prepare($sql);
$stmtList->execute($params);
$daftar_berita = $stmtList->fetchAll(PDO::FETCH_ASSOC);

foreach ($daftar_berita as $idx => $item) {
    $resolved = resolveSiteImagePath($item['gambar'] ?? null);
    $daftar_berita[$idx]['gambar_preview'] = $resolved ? ('../../' . $resolved) : null;
}

// Hitung statistik per tipe berita
$stats = [
    'total'      => 0,
    'Pengumuman' => 0,
    'Akademik'   => 0,
    'Prestasi'   => 0,
];
try {
    $stmtCount = $pdo->query("SELECT kategori, COUNT(*) AS total FROM berita GROUP BY kategori");
    while ($r = $stmtCount->fetch(PDO::FETCH_ASSOC)) {
        $stats['total'] += (int)$r['total'];
        $kat = $r['kategori'];
        if (isset($stats[$kat])) {
            $stats[$kat] += (int)$r['total'];
        }
    }
} catch (PDOException $e) {
}

require_once __DIR__ . '/../includes/header-kaih.php';
?>

<style>
    .berita-admin-wrap {
        max-width: 1200px;
        margin: 0 auto;
    }
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 22px;
    }
    .stat-mini {
        background: white;
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
        border-left: 5px solid #0284c7;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .stat-mini.pengumuman { border-left-color: #0284c7; }
    .stat-mini.akademik { border-left-color: #10b981; }
    .stat-mini.prestasi { border-left-color: #f59e0b; }
    .stat-mini .label {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .stat-mini .val {
        font-size: 26px;
        font-weight: 800;
        color: #1e293b;
        margin-top: 4px;
    }
    .stat-mini .icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: #f0f9ff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .form-card, .table-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
        margin-bottom: 24px;
    }

    .section-title-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 2px solid #f1f5f9;
    }
    .section-title-bar h2 {
        font-size: 19px;
        color: #1e293b;
        font-weight: 800;
        margin: 0;
    }
    .section-title-bar p {
        font-size: 13px;
        color: #64748b;
        margin: 3px 0 0;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 14px;
        margin-bottom: 14px;
    }
    .form-group {
        margin-bottom: 14px;
    }
    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }
    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        color: #1e293b;
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-control:focus {
        outline: none;
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
    }

    .filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 20px;
    }
    .filter-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .filter-tab {
        padding: 7px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        background: #f1f5f9;
        color: #475569;
        transition: all 0.2s;
        border: 1px solid transparent;
    }
    .filter-tab:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
    .filter-tab.active {
        background: #0284c7;
        color: white;
        box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
    }

    .search-box-wrap {
        display: flex;
        align-items: center;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        overflow: hidden;
        background: white;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .search-box-wrap:focus-within {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
    }
    .search-box-wrap input {
        border: none;
        outline: none;
        padding: 8px 14px;
        font-size: 13px;
        width: 220px;
        color: #1e293b;
        font-family: inherit;
    }
    .search-box-wrap button {
        border: none;
        background: #0284c7;
        color: white;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.2s;
    }
    .search-box-wrap button:hover {
        background: #0369a1;
    }

    .badge-tipe {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .badge-tipe.pengumuman { background: #e0f2fe; color: #0284c7; }
    .badge-tipe.akademik   { background: #dcfce7; color: #15803d; }
    .badge-tipe.prestasi   { background: #fef3c7; color: #b45309; }

    .badge-beranda {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 5px;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 10.5px;
        font-weight: 700;
        background: #ede9fe;
        color: #6d28d9;
        white-space: nowrap;
    }

    .tgl-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f8fafc;
        color: #334155;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        border: 1px solid #e2e8f0;
    }

    .news-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }
    .news-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 11.5px;
        letter-spacing: 0.05em;
        padding: 13px 16px;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
    }
    .news-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: #334155;
    }
    .news-table tr:hover td {
        background: #f8fafc;
    }
    .news-table tr:last-child td {
        border-bottom: none;
    }

    .thumb-box {
        width: 88px;
        height: 58px;
        border-radius: 8px;
        overflow: hidden;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        flex-shrink: 0;
    }
    .thumb-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .news-title {
        font-weight: 800;
        font-size: 14.5px;
        color: #1e293b;
        margin-bottom: 4px;
        line-height: 1.35;
    }
    .news-summary {
        font-size: 13px;
        color: #64748b;
        line-height: 1.5;
        max-width: 460px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .btn-primary {
        padding: 10px 20px;
        background: #0284c7;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-primary:hover { background: #0369a1; }

    .action-group {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .btn-action {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid transparent;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.2s;
        line-height: 1.4;
    }
    .btn-edit { background: #fef3c7; color: #b45309; border-color: #fde68a; }
    .btn-edit:hover { background: #fde68a; color: #92400e; }
    .btn-view { background: #e0f2fe; color: #0284c7; border-color: #bae6fd; }
    .btn-view:hover { background: #bae6fd; color: #0369a1; }
    .btn-del  { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
    .btn-del:hover  { background: #fecaca; color: #b91c1c; }

    @media (max-width: 960px) {
        .stats-row { grid-template-columns: repeat(2, 1fr); }
        .form-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 600px) {
        .stats-row { grid-template-columns: 1fr; }
        .form-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="berita-admin-wrap">

    <?php if ($flash !== ''): ?>
        <div style="padding: 13px 18px; margin-bottom: 18px; border-radius: 10px; font-size: 14px; font-weight: 700; border-left: 5px solid <?= $flashType === 'error' ? '#dc2626' : '#16a34a' ?>; background: <?= $flashType === 'error' ? '#fee2e2; color: #991b1b;' : '#dcfce7; color: #166534;' ?>">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <!-- Statistik Berita -->
    <div class="stats-row">
        <div class="stat-mini">
            <div>
                <div class="label">Total Berita</div>
                <div class="val"><?= (int)$stats['total'] ?></div>
            </div>
            <div class="icon">📰</div>
        </div>
        <div class="stat-mini pengumuman">
            <div>
                <div class="label">Tipe Pengumuman</div>
                <div class="val"><?= (int)$stats['Pengumuman'] ?></div>
            </div>
            <div class="icon">📢</div>
        </div>
        <div class="stat-mini akademik">
            <div>
                <div class="label">Tipe Akademik</div>
                <div class="val"><?= (int)$stats['Akademik'] ?></div>
            </div>
            <div class="icon">🎓</div>
        </div>
        <div class="stat-mini prestasi">
            <div>
                <div class="label">Tipe Prestasi</div>
                <div class="val"><?= (int)$stats['Prestasi'] ?></div>
            </div>
            <div class="icon">🏆</div>
        </div>
    </div>

    <!-- Form Tambah Berita Baru -->
    <div class="form-card" id="formTambahBerita">
        <div class="section-title-bar">
            <div>
                <h2>➕ Tambah Berita &amp; Informasi Baru</h2>
                <p>Berita yang diterbitkan otomatis tampil di halaman <strong>Informasi</strong> dan <strong>Berita Terkini (Beranda)</strong> sesuai tipe beritanya.</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="../../index.php" target="_blank" class="btn-action btn-view">🌐 Lihat Beranda</a>
                <a href="../../informasi.php#pengumuman" target="_blank" class="btn-action btn-view">📄 Lihat Halaman Informasi</a>
            </div>
        </div>

        <form method="POST" action="berita.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">

            <div class="form-grid">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="add_judul">Nama / Judul Berita <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="add_judul" name="judul" class="form-control" placeholder="Contoh: PPDB Tahun Ajaran 2026/2027 Resmi Dibuka" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="add_tanggal">Tanggal Berita <span style="color:#ef4444;">*</span></label>
                    <input type="date" id="add_tanggal" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="add_kategori">Tipe Berita <span style="color:#ef4444;">*</span></label>
                    <select id="add_kategori" name="kategori" class="form-control" required>
                        <option value="Pengumuman">Pengumuman</option>
                        <option value="Akademik">Akademik</option>
                        <option value="Prestasi">Prestasi</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="add_ringkasan">Ringkasan Singkat (Tampil di Kartu Berita Terkini &amp; Informasi) <span style="color:#ef4444;">*</span></label>
                    <textarea id="add_ringkasan" name="ringkasan" rows="3" class="form-control" placeholder="Tuliskan deskripsi singkat berita (1-2 kalimat)..." required></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="add_isi">Isi Lengkap Berita (Tampil saat klik "Baca Selengkapnya")</label>
                    <textarea id="add_isi" name="isi" rows="3" class="form-control" placeholder="Tuliskan isi lengkap berita (jika dikosongkan akan otomatis mengikuti ringkasan)..."></textarea>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-top: 16px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <div>
                        <label for="add_gambar" style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 4px;">Gambar / Foto Berita (JPG, PNG, WEBP)</label>
                        <input type="file" id="add_gambar" name="gambar" accept="image/*" onchange="previewAddImage(this)" style="font-size: 13px; color: #475569;">
                    </div>
                    <img id="add_gambar_preview" src="" alt="Preview" style="display: none; width: 72px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;">
                </div>

                <button type="submit" class="btn-primary">
                    💾 Simpan &amp; Terbitkan Berita
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Berita -->
    <div class="table-card">
        <div class="section-title-bar">
            <div>
                <h2>📋 Daftar Berita &amp; Informasi Sekolah</h2>
                <p>Kelola, perbaiki (edit), atau hapus berita yang tampil di Beranda dan halaman Informasi.</p>
            </div>
        </div>

        <div class="filter-bar">
            <div class="filter-tabs">
                <a href="berita.php" class="filter-tab <?= $filterKategori === '' ? 'active' : '' ?>">
                    Semua (<?= (int)$stats['total'] ?>)
                </a>
                <a href="berita.php?kategori=Pengumuman" class="filter-tab <?= $filterKategori === 'Pengumuman' ? 'active' : '' ?>">
                    Pengumuman (<?= (int)$stats['Pengumuman'] ?>)
                </a>
                <a href="berita.php?kategori=Akademik" class="filter-tab <?= $filterKategori === 'Akademik' ? 'active' : '' ?>">
                    Akademik (<?= (int)$stats['Akademik'] ?>)
                </a>
                <a href="berita.php?kategori=Prestasi" class="filter-tab <?= $filterKategori === 'Prestasi' ? 'active' : '' ?>">
                    Prestasi (<?= (int)$stats['Prestasi'] ?>)
                </a>
            </div>

            <form method="GET" action="berita.php" style="display: flex; gap: 8px; align-items: center;">
                <?php if ($filterKategori !== ''): ?>
                    <input type="hidden" name="kategori" value="<?= htmlspecialchars($filterKategori) ?>">
                <?php endif; ?>
                <div class="search-box-wrap">
                    <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Cari nama berita...">
                    <button type="submit">Cari</button>
                </div>
                <?php if ($searchQuery !== '' || $filterKategori !== ''): ?>
                    <a href="berita.php" class="btn-action" style="background:#f1f5f9; color:#475569; padding: 7px 12px; border: 1px solid #cbd5e1;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table class="news-table">
                <thead>
                    <tr>
                        <th style="width: 100px;">Gambar</th>
                        <th>Nama Berita &amp; Ringkasan</th>
                        <th style="width: 145px; text-align: center;">Tanggal</th>
                        <th style="width: 155px; text-align: center;">Tipe Berita</th>
                        <th style="width: 1%; white-space: nowrap; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftar_berita)): ?>
                        <tr>
                            <td colspan="5" style="padding: 36px; text-align: center; color: #94a3b8; font-size: 15px;">
                                Belum ada berita yang sesuai dengan pencarian/filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftar_berita as $b):
                            $katLower = strtolower($b['kategori'] ?? 'pengumuman');
                            $badgeClass = in_array($katLower, ['pengumuman', 'akademik', 'prestasi'], true) ? $katLower : 'pengumuman';
                            $isFeatured = in_array((int)$b['id'], $featuredIds, true);
                            $tglFormatted = date('d M Y', strtotime($b['tanggal']));
                        ?>
                            <tr>
                                <td>
                                    <div class="thumb-box">
                                        <?php if (!empty($b['gambar_preview'])): ?>
                                            <img src="<?= htmlspecialchars($b['gambar_preview']) ?>" alt="<?= htmlspecialchars($b['judul']) ?>">
                                        <?php else: ?>
                                            <span style="font-size: 11px; color: #94a3b8; font-weight: 700;">No Img</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="news-title">
                                        <?= htmlspecialchars($b['judul']) ?>
                                    </div>
                                    <div class="news-summary">
                                        <?= htmlspecialchars($b['ringkasan']) ?>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <div class="tgl-badge">
                                        <span>📅</span> <?= htmlspecialchars($tglFormatted) ?>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <div>
                                        <span class="badge-tipe <?= $badgeClass ?>">
                                            <?= htmlspecialchars($b['kategori']) ?>
                                        </span>
                                    </div>
                                    <?php if ($isFeatured): ?>
                                        <div>
                                            <span class="badge-beranda" title="Tampil di kartu Berita Terkini pada halaman Beranda">
                                                ⭐ Tampil di Beranda
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <div class="action-group">
                                        <button type="button"
                                                class="btn-action btn-edit"
                                                onclick='openModalEdit(<?= json_encode($b, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'
                                                title="Edit Berita">
                                            ✏️ Edit
                                        </button>

                                        <a href="../../berita-detail.php?id=<?= (int)$b['id'] ?>"
                                           target="_blank"
                                           class="btn-action btn-view"
                                           title="Lihat tampilan detail berita">
                                            👁️ Detail
                                        </a>

                                        <form method="POST" action="berita.php" onsubmit="return confirm('Yakin ingin menghapus berita ini?');" style="margin: 0; display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                            <button type="submit" class="btn-action btn-del" title="Hapus Berita">🗑️ Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit Berita -->
<div id="modalEdit" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 1000; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;">
    <div style="background: white; width: 100%; max-width: 650px; padding: 28px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); margin: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 2px solid #f1f5f9;">
            <h3 style="margin: 0; font-size: 20px; color: #1e293b;">✏️ Perbaiki / Edit Berita</h3>
            <button type="button" onclick="closeModalEdit()" style="background: none; border: none; font-size: 22px; color: #64748b; cursor: pointer;">&times;</button>
        </div>

        <form method="POST" action="berita.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="gambar_lama" id="edit_gambar_lama">

            <div class="form-group">
                <label for="edit_judul">Nama / Judul Berita <span style="color:#ef4444;">*</span></label>
                <input type="text" name="judul" id="edit_judul" class="form-control" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label for="edit_tanggal" style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:6px;">Tanggal Berita <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="tanggal" id="edit_tanggal" class="form-control" required>
                </div>
                <div>
                    <label for="edit_kategori" style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:6px;">Tipe Berita <span style="color:#ef4444;">*</span></label>
                    <select name="kategori" id="edit_kategori" class="form-control" required>
                        <option value="Pengumuman">Pengumuman</option>
                        <option value="Akademik">Akademik</option>
                        <option value="Prestasi">Prestasi</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="edit_ringkasan">Ringkasan Singkat (Tampil di Kartu Berita Terkini &amp; Informasi) <span style="color:#ef4444;">*</span></label>
                <textarea name="ringkasan" id="edit_ringkasan" rows="3" class="form-control" required></textarea>
            </div>

            <div class="form-group">
                <label for="edit_isi">Isi Lengkap Berita (Tampil di Halaman Detail Berita)</label>
                <textarea name="isi" id="edit_isi" rows="5" class="form-control"></textarea>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <div style="flex: 1;">
                    <label for="edit_gambar_input">Ganti Gambar Berita (Opsional)</label>
                    <input type="file" name="gambar" id="edit_gambar_input" accept="image/*" onchange="previewEditImage(this)" class="form-control" style="padding: 7px;">
                </div>
                <div id="edit_preview_wrap" style="display: none; text-align: center;">
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Gambar Saat Ini</div>
                    <img id="edit_gambar_preview" src="" alt="Preview Gambar" style="width: 96px; height: 64px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 22px;">
                <button type="submit" style="flex: 1; padding: 11px; background: #0284c7; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer;">
                    💾 Simpan Perubahan
                </button>
                <button type="button" onclick="closeModalEdit()" style="flex: 1; padding: 11px; background: #e2e8f0; color: #334155; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer;">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function previewAddImage(input) {
        const preview = document.getElementById('add_gambar_preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = 'none';
        }
    }

    function previewEditImage(input) {
        const wrap = document.getElementById('edit_preview_wrap');
        const preview = document.getElementById('edit_gambar_preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                wrap.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function openModalEdit(data) {
        document.getElementById('edit_id').value = data.id || '';
        document.getElementById('edit_judul').value = data.judul || '';
        document.getElementById('edit_tanggal').value = data.tanggal || '';

        const allowed = ['Pengumuman', 'Akademik', 'Prestasi'];
        const kat = allowed.includes(data.kategori) ? data.kategori : 'Pengumuman';
        document.getElementById('edit_kategori').value = kat;

        document.getElementById('edit_ringkasan').value = data.ringkasan || '';
        document.getElementById('edit_isi').value = data.isi || '';
        document.getElementById('edit_gambar_lama').value = data.gambar || '';
        document.getElementById('edit_gambar_input').value = '';

        const wrap = document.getElementById('edit_preview_wrap');
        const preview = document.getElementById('edit_gambar_preview');
        if (data.gambar_preview) {
            preview.src = data.gambar_preview;
            wrap.style.display = 'block';
        } else {
            preview.src = '';
            wrap.style.display = 'none';
        }

        document.getElementById('modalEdit').style.display = 'flex';
    }

    function closeModalEdit() {
        document.getElementById('modalEdit').style.display = 'none';
    }

    document.getElementById('modalEdit').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModalEdit();
        }
    });
</script>

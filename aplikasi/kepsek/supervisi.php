<?php
// aplikasi/kepsek/supervisi.php
require_once '../includes/header-kaih.php';
require_once '../../config/database.php';

// Pastikan hanya role kepsek
if (($_SESSION['role'] ?? '') !== 'kepsek') {
    header('Location: ../../login.php');
    exit;
}

$message = '';
$message_type = '';

// Proses Hapus Sesi Supervisi jika diminta
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    try {
        $stmtDel = $pdo->prepare("DELETE FROM supervisi_penilaian WHERE id = ?");
        $stmtDel->execute([$delId]);
        $message = 'Data observasi supervisi berhasil dihapus.';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Gagal menghapus data: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Filter Pencarian
$filterGuru = (int)($_GET['guru_id'] ?? 0);
$filterSemester = trim($_GET['semester'] ?? '');
$filterTahun = trim($_GET['tahun'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');

$sql = "
    SELECT sp.*, g.nama_guru, g.nip, g.kelas as kelas_guru, g.jabatan
    FROM supervisi_penilaian sp
    JOIN guru g ON sp.guru_id = g.id
    WHERE 1=1
";
$params = [];

if ($filterGuru > 0) {
    $sql .= " AND sp.guru_id = ?";
    $params[] = $filterGuru;
}

if (!empty($filterSemester)) {
    $sql .= " AND sp.semester = ?";
    $params[] = $filterSemester;
}

if (!empty($filterTahun)) {
    $sql .= " AND sp.tahun_ajaran = ?";
    $params[] = $filterTahun;
}

if (!empty($searchQuery)) {
    $sql .= " AND (g.nama_guru LIKE ? OR sp.mata_pelajaran LIKE ? OR sp.kelas LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql .= " ORDER BY sp.tanggal_observasi DESC, sp.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftar_supervisi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar guru untuk dropdown filter
$daftar_guru = $pdo->query("SELECT id, nama_guru, jabatan FROM guru WHERE jabatan NOT LIKE '%Kepala Sekolah%' ORDER BY nama_guru ASC")->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar tahun ajaran unik
$daftar_tahun = $pdo->query("SELECT DISTINCT tahun_ajaran FROM supervisi_penilaian ORDER BY tahun_ajaran DESC")->fetchAll(PDO::FETCH_COLUMN);
?>

<link rel="stylesheet" href="../../assets/css/kepsek.css">

<div class="kepsek-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="font-size: 22px; color: #1e293b; font-weight: 700; margin-bottom: 4px;">
                📋 Rekap Observasi & Supervisi Guru
            </h2>
            <p style="font-size: 13px; color: #64748b;">
                Daftar seluruh pelaksanaan supervisi akademik pembelajaran di kelas SMP Negeri 28 Balikpapan.
            </p>
        </div>
        <a href="penilaian.php" class="btn-kepsek-action" style="background: #0284c7; color: white;">
            <span>➕</span> Mulai Observasi & Penilaian Baru
        </a>
    </div>

    <?php if ($message): ?>
        <div style="background: <?= $message_type === 'success' ? '#dcfce7' : '#fee2e2'; ?>; border-left: 4px solid <?= $message_type === 'success' ? '#10b981' : '#ef4444'; ?>; color: <?= $message_type === 'success' ? '#166534' : '#b91c1c'; ?>; padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 600;">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div class="kepsek-card" style="padding: 18px 22px; margin-bottom: 20px;">
        <form method="GET" action="" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div>
                <select name="guru_id" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: white; outline: none;">
                    <option value="">Semua Guru</option>
                    <?php foreach ($daftar_guru as $g): ?>
                        <option value="<?= $g['id']; ?>" <?= ($filterGuru == $g['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($g['nama_guru']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <select name="semester" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: white; outline: none;">
                    <option value="">Semua Semester</option>
                    <option value="Ganjil" <?= ($filterSemester === 'Ganjil') ? 'selected' : ''; ?>>Ganjil</option>
                    <option value="Genap" <?= ($filterSemester === 'Genap') ? 'selected' : ''; ?>>Genap</option>
                </select>
            </div>

            <div>
                <input type="text" name="q" value="<?= htmlspecialchars($searchQuery); ?>" placeholder="Cari nama guru, mapel, kelas..." style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; width: 220px;">
            </div>

            <button type="submit" style="padding: 8px 18px; background: #0284c7; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 13px;">
                Filter
            </button>

            <?php if ($filterGuru || $filterSemester || $filterTahun || $searchQuery): ?>
                <a href="supervisi.php" style="color: #64748b; font-size: 13px; text-decoration: none; padding: 8px;">Reset Filter</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel Data Supervisi -->
    <div class="kepsek-card">
        <div style="overflow-x: auto;">
            <table class="table-kepsek">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal Observasi</th>
                        <th>Nama Guru</th>
                        <th>Mata Pelajaran</th>
                        <th>Kelas</th>
                        <th>Semester / T.A</th>
                        <th>Skor Capaian</th>
                        <th>Predikat</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftar_supervisi)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: #94a3b8; padding: 40px;">
                                Tidak ada data observasi yang sesuai dengan pencarian/filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftar_supervisi as $idx => $sp): 
                            $badgeClass = match ($sp['predikat']) {
                                'Sangat Baik' => 'predikat-sangat-baik',
                                'Baik' => 'predikat-baik',
                                'Cukup' => 'predikat-cukup',
                                default => 'predikat-kurang',
                            };
                        ?>
                        <tr>
                            <td><?= $idx + 1; ?></td>
                            <td><?= date('d/m/Y', strtotime($sp['tanggal_observasi'])); ?></td>
                            <td>
                                <strong><?= htmlspecialchars($sp['nama_guru']); ?></strong>
                                <div style="font-size: 12px; color: #64748b;">NIP. <?= htmlspecialchars($sp['nip']); ?></div>
                            </td>
                            <td><strong><?= htmlspecialchars($sp['mata_pelajaran']); ?></strong></td>
                            <td><?= htmlspecialchars($sp['kelas']); ?></td>
                            <td><?= htmlspecialchars($sp['semester'] . ' ' . $sp['tahun_ajaran']); ?></td>
                            <td>
                                <span style="font-size: 16px; font-weight: 800; color: #0284c7;">
                                    <?= number_format((float)$sp['skor_akhir'], 1); ?>%
                                </span>
                            </td>
                            <td>
                                <span class="predikat-badge <?= $badgeClass; ?>">
                                    <?= htmlspecialchars($sp['predikat']); ?>
                                </span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="analisis.php?id=<?= $sp['id']; ?>" class="btn-kepsek-action" style="padding: 6px 12px; font-size: 12px; background: #e0f2fe; color: #0284c7; box-shadow: none;" title="Lihat Grafik & Analisis Kelemahan">
                                    📊 Grafik & Analisis
                                </a>
                                <a href="cetak.php?id=<?= $sp['id']; ?>" target="_blank" class="btn-kepsek-action" style="padding: 6px 10px; font-size: 12px; background: #f1f5f9; color: #475569; box-shadow: none;" title="Cetak Rapor Observasi">
                                    🖨️
                                </a>
                                <a href="supervisi.php?action=delete&id=<?= $sp['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus data hasil observasi ini? Data yang terhapus tidak dapat dikembalikan.');" class="btn-kepsek-action" style="padding: 6px 10px; font-size: 12px; background: #fee2e2; color: #dc2626; box-shadow: none;" title="Hapus">
                                    🗑️
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

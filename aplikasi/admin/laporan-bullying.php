<?php
session_start();
require_once '../../config/database.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$total = (int) $pdo->query('SELECT COUNT(*) FROM laporan_bullying')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    'SELECT id, nama_pelapor, kontak, status_pelapor, nama_korban, kelas_korban,
            jenis_bullying, tanggal_kejadian, deskripsi, lokasi, status, created_at
     FROM laporan_bullying
     ORDER BY created_at DESC, id DESC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header-kaih.php';
?>

<style>
    .admin-page { max-width: 1250px; margin: 0 auto; padding: 24px; }
    .admin-page h1 { color: #1e293b; margin-bottom: 6px; }
    .admin-page .intro { color: #64748b; margin-bottom: 20px; }
    .table-wrap { overflow-x: auto; background: white; border-radius: 14px; box-shadow: 0 4px 15px rgba(15,23,42,.06); }
    .data-table { width: 100%; min-width: 980px; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 13px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
    .data-table th { background: #f8fafc; color: #475569; font-size: 12px; text-transform: uppercase; }
    .data-table td { color: #334155; font-size: 13px; }
    .data-table tr:last-child td { border-bottom: 0; }
    .badge { display: inline-block; padding: 4px 9px; border-radius: 999px; background: #fee2e2; color: #991b1b; font-size: 11px; font-weight: 700; }
    .description { max-width: 260px; white-space: pre-line; }
    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; }
    .pagination a, .pagination span { padding: 8px 12px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; }
    .pagination a { background: #e0f2fe; color: #0369a1; }
    .pagination .active { background: #0284c7; color: white; }
    .empty { padding: 35px; text-align: center; color: #64748b; }
</style>

<div class="admin-page">
    <h1>🛡️ Laporan Bullying</h1>
    <p class="intro">Daftar laporan perundungan yang diterima dari formulir publik.</p>

    <div class="table-wrap">
        <?php if (!$laporan): ?>
            <div class="empty">Belum ada laporan bullying yang diterima.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pelapor</th>
                        <th>Korban</th>
                        <th>Jenis</th>
                        <th>Lokasi</th>
                        <th>Deskripsi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($laporan as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d M Y H:i', strtotime($item['created_at']))) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($item['nama_pelapor']) ?></strong><br>
                                <small>📞 <?= htmlspecialchars($item['kontak']) ?></small><br>
                                <small style="color: #64748b;"><strong>Agenda:</strong> <?= htmlspecialchars($item['status_pelapor']) ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($item['nama_korban']) ?><br>
                                <small><?= htmlspecialchars($item['kelas_korban']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($item['jenis_bullying']) ?></td>
                            <td><?= htmlspecialchars($item['lokasi']) ?></td>
                            <td class="description"><?= htmlspecialchars($item['deskripsi']) ?></td>
                            <td><span class="badge"><?= htmlspecialchars(ucfirst($item['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Pagination laporan bullying">
            <?php for ($number = 1; $number <= $totalPages; $number++): ?>
                <?php if ($number === $page): ?>
                    <span class="active"><?= $number ?></span>
                <?php else: ?>
                    <a href="?page=<?= $number ?>"><?= $number ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</div>
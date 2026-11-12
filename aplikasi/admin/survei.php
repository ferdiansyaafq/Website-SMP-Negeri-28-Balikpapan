<?php
session_start();
require_once '../../config/database.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $stmtDel = $pdo->prepare('DELETE FROM survei WHERE id = ?');
        $stmtDel->execute([$id]);
        $_SESSION['flash_msg'] = 'Data survei pelayanan berhasil dihapus.';
        $_SESSION['flash_type'] = 'success';
        header('Location: survei.php?page=' . $page);
        exit;
    }
}

if (!empty($_SESSION['flash_msg'])) {
    $flash = $_SESSION['flash_msg'];
    $flashType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

try {
    $total = (int) $pdo->query('SELECT COUNT(*) FROM survei')->fetchColumn();
} catch (PDOException $e) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `survei` (
          `id` int NOT NULL AUTO_INCREMENT,
          `nama_pengisi` varchar(100) NOT NULL,
          `peran` varchar(50) DEFAULT NULL,
          `rating` int NOT NULL,
          `ulasan` text,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        $total = (int) $pdo->query('SELECT COUNT(*) FROM survei')->fetchColumn();
    } catch (Exception $ex) {
        $total = 0;
    }
}
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    'SELECT id, nama_pengisi, peran, rating, ulasan, created_at
     FROM survei
     ORDER BY created_at DESC, id DESC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$survei = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header-kaih.php';
?>

<style>
    .admin-page { max-width: 1150px; margin: 0 auto; padding: 24px; }
    .admin-page h1 { color: #1e293b; margin-bottom: 6px; }
    .admin-page .intro { color: #64748b; margin-bottom: 20px; }
    .table-wrap { overflow-x: auto; background: white; border-radius: 14px; box-shadow: 0 4px 15px rgba(15,23,42,.06); }
    .data-table { width: 100%; min-width: 780px; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 13px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: middle; }
    .data-table th { background: #f8fafc; color: #475569; font-size: 12px; text-transform: uppercase; }
    .data-table td { color: #334155; font-size: 13px; }
    .data-table tr:last-child td { border-bottom: 0; }
    .rating { color: #d97706; font-size: 17px; white-space: nowrap; }
    .review { max-width: 480px; white-space: pre-line; }
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: 0.2s;
    }
    .btn-delete {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fca5a5;
    }
    .btn-delete:hover {
        background: #dc2626;
        color: white;
    }
    .alert-flash {
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .alert-flash.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .alert-flash.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; }
    .pagination a, .pagination span { padding: 8px 12px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; }
    .pagination a { background: #e0f2fe; color: #0369a1; }
    .pagination .active { background: #0284c7; color: white; }
    .empty { padding: 35px; text-align: center; color: #64748b; }
</style>

<div class="admin-page">
    <h1>📝 Survei Pelayanan</h1>
    <p class="intro">Masukan dan penilaian yang dikirim melalui formulir survei publik.</p>

    <?php if ($flash): ?>
        <div class="alert-flash <?= htmlspecialchars($flashType) ?>">
            <span><?= ($flashType === 'success') ? '✅' : '⚠️' ?></span>
            <div><?= htmlspecialchars($flash) ?></div>
        </div>
    <?php endif; ?>

    <div class="table-wrap">
        <?php if (!$survei): ?>
            <div class="empty">Belum ada survei yang diterima.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Pengisi</th>
                        <th>Agenda Kunjungan</th>
                        <th>Kepuasan</th>
                        <th>Kritik & Saran</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $emojiMap = [
                        1 => ['icon' => '😡', 'text' => 'Sangat Tidak Puas'],
                        2 => ['icon' => '🙁', 'text' => 'Tidak Puas'],
                        3 => ['icon' => '😐', 'text' => 'Cukup'],
                        4 => ['icon' => '🙂', 'text' => 'Puas'],
                        5 => ['icon' => '🤩', 'text' => 'Sangat Puas']
                    ];
                    foreach ($survei as $item): 
                        $rate = (int) $item['rating'];
                        $ratingInfo = $emojiMap[$rate] ?? ['icon' => '⭐', 'text' => $rate . '/5'];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d M Y H:i', strtotime($item['created_at']))) ?></td>
                            <td><strong><?= htmlspecialchars($item['nama_pengisi']) ?></strong></td>
                            <td><?= htmlspecialchars($item['peran']) ?></td>
                            <td class="rating">
                                <span title="<?= htmlspecialchars($ratingInfo['text']) ?>" style="font-size: 20px; vertical-align: middle;">
                                    <?= $ratingInfo['icon'] ?>
                                </span>
                                <span style="font-size: 12px; color: #64748b; margin-left: 4px;"><?= htmlspecialchars($ratingInfo['text']) ?></span>
                            </td>
                            <td class="review"><?= htmlspecialchars($item['ulasan']) ?></td>
                            <td style="text-align: center; white-space: nowrap;">
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin MENGHAPUS data survei ini?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                    <button type="submit" class="btn-action btn-delete" title="Hapus Survei">
                                        🗑️ Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Pagination survei">
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

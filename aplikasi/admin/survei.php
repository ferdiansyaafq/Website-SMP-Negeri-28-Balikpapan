<?php
session_start();
require_once '../../config/database.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$total = (int) $pdo->query('SELECT COUNT(*) FROM survei')->fetchColumn();
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
    .admin-page { max-width: 1100px; margin: 0 auto; padding: 24px; }
    .admin-page h1 { color: #1e293b; margin-bottom: 6px; }
    .admin-page .intro { color: #64748b; margin-bottom: 20px; }
    .table-wrap { overflow-x: auto; background: white; border-radius: 14px; box-shadow: 0 4px 15px rgba(15,23,42,.06); }
    .data-table { width: 100%; min-width: 700px; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 13px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
    .data-table th { background: #f8fafc; color: #475569; font-size: 12px; text-transform: uppercase; }
    .data-table td { color: #334155; font-size: 13px; }
    .data-table tr:last-child td { border-bottom: 0; }
    .rating { color: #d97706; font-size: 17px; white-space: nowrap; }
    .review { max-width: 520px; white-space: pre-line; }
    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; }
    .pagination a, .pagination span { padding: 8px 12px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; }
    .pagination a { background: #e0f2fe; color: #0369a1; }
    .pagination .active { background: #0284c7; color: white; }
    .empty { padding: 35px; text-align: center; color: #64748b; }
</style>

<div class="admin-page">
    <h1>📝 Survei Pelayanan</h1>
    <p class="intro">Masukan dan penilaian yang dikirim melalui formulir survei publik.</p>

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

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
        $stmtDel = $pdo->prepare('DELETE FROM buku_tamu WHERE id = ?');
        $stmtDel->execute([$id]);
        $_SESSION['flash_msg'] = 'Data kunjungan buku tamu berhasil dihapus.';
        $_SESSION['flash_type'] = 'success';
        header('Location: buku_tamu.php?page=' . $page);
        exit;
    }
}

if (!empty($_SESSION['flash_msg'])) {
    $flash = $_SESSION['flash_msg'];
    $flashType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

$total = (int) $pdo->query('SELECT COUNT(*) FROM buku_tamu')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    'SELECT id, nama, instansi, kontak, email, tujuan_bertemu, keperluan, tanggal_kunjungan, jam_kunjungan, jumlah_orang, status, created_at
     FROM buku_tamu
     ORDER BY tanggal_kunjungan DESC, jam_kunjungan DESC, id DESC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$tamu = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header-kaih.php';
?>

<style>
    .admin-page { max-width: 1150px; margin: 0 auto; padding: 24px; }
    .admin-page h1 { color: #1e293b; margin-bottom: 6px; }
    .admin-page .intro { color: #64748b; margin-bottom: 20px; }
    .table-wrap { overflow-x: auto; background: white; border-radius: 14px; box-shadow: 0 4px 15px rgba(15,23,42,.06); }
    .data-table { width: 100%; min-width: 850px; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 13px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
    .data-table th { background: #f8fafc; color: #475569; font-size: 12px; text-transform: uppercase; }
    .data-table td { color: #334155; font-size: 13px; }
    .data-table tr:last-child td { border-bottom: 0; }
    .guest-name { font-weight: 700; color: #0284c7; }
    .guest-instansi { font-size: 12px; color: #64748b; }
    .guest-contact { font-size: 12px; color: #475569; white-space: nowrap; }
    .guest-purpose { max-width: 320px; white-space: pre-line; }
    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; }
    .pagination a, .pagination span { padding: 8px 12px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; }
    .pagination a { background: #e0f2fe; color: #0369a1; }
    .pagination .active { background: #0284c7; color: white; }
    .empty { padding: 35px; text-align: center; color: #64748b; }
    .badge-target { background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-block; }
</style>

<div class="admin-page">
    <h1>📖 Buku Tamu Digital</h1>
    <p class="intro">Daftar laporan kehadiran dan kunjungan tamu di lingkungan SMP Negeri 28 Balikpapan.</p>

    <?php if ($flash !== ''): ?>
        <div style="padding: 12px 16px; margin-bottom: 20px; border-radius: 10px; font-size: 14px; font-weight: 600; background: <?= $flashType === 'error' ? '#fee2e2; color: #991b1b; border: 1px solid #fecaca;' : '#dcfce7; color: #166534; border: 1px solid #bbf7d0;' ?>;">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <div class="table-wrap">
        <?php if (!$tamu): ?>
            <div class="empty">Belum ada data kunjungan tamu yang dicatat.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu Kunjungan</th>
                        <th>Identitas Tamu</th>
                        <th>Kontak</th>
                        <th>Tujuan Dituju</th>
                        <th>Keperluan / Laporan</th>
                        <th>Rombongan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tamu as $item): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars(date('d M Y', strtotime($item['tanggal_kunjungan']))) ?></strong>
                                <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars(substr($item['jam_kunjungan'] ?? '00:00', 0, 5)) ?> WITA</div>
                            </td>
                            <td>
                                <div class="guest-name"><?= htmlspecialchars($item['nama']) ?></div>
                                <div class="guest-instansi">🏛️ <?= htmlspecialchars($item['instansi']) ?></div>
                            </td>
                            <td class="guest-contact">
                                📞 <?= htmlspecialchars($item['kontak']) ?>
                                <?php if (!empty($item['email'])): ?>
                                    <div style="font-size: 11px; color: #64748b;">✉️ <?= htmlspecialchars($item['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-target"><?= htmlspecialchars($item['tujuan_bertemu']) ?></span>
                            </td>
                            <td class="guest-purpose"><?= htmlspecialchars($item['keperluan']) ?></td>
                            <td style="text-align: center;">
                                <strong><?= (int)$item['jumlah_orang'] ?></strong> orang
                            </td>
                            <td>
                                <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus data kunjungan tamu ini?');" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                    <button type="submit" style="padding: 5px 10px; background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer;">
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
        <nav class="pagination" aria-label="Pagination buku tamu">
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

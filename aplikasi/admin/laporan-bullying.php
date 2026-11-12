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
        $stmtDel = $pdo->prepare('DELETE FROM laporan_bullying WHERE id = ?');
        $stmtDel->execute([$id]);
        $_SESSION['flash_msg'] = 'Laporan bullying berhasil dihapus.';
        $_SESSION['flash_type'] = 'success';
        header('Location: laporan-bullying.php?page=' . $page);
        exit;
    }

    if ($action === 'update_status' && $id > 0) {
        $newStatus = trim($_POST['status'] ?? '');
        $allowed = ['baru', 'diproses', 'selesai'];
        if (in_array(strtolower($newStatus), $allowed, true)) {
            $stmtUpd = $pdo->prepare('UPDATE laporan_bullying SET status = ? WHERE id = ?');
            $stmtUpd->execute([strtolower($newStatus), $id]);
            $_SESSION['flash_msg'] = 'Status laporan berhasil diperbarui menjadi: ' . ucfirst($newStatus);
            $_SESSION['flash_type'] = 'success';
            header('Location: laporan-bullying.php?page=' . $page);
            exit;
        }
    }
}

if (!empty($_SESSION['flash_msg'])) {
    $flash = $_SESSION['flash_msg'];
    $flashType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

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
    .admin-page { max-width: 1280px; margin: 0 auto; padding: 24px; }
    .admin-page h1 { color: #1e293b; margin-bottom: 6px; }
    .admin-page .intro { color: #64748b; margin-bottom: 20px; }
    .table-wrap { overflow-x: auto; background: white; border-radius: 14px; box-shadow: 0 4px 15px rgba(15,23,42,.06); }
    .data-table { width: 100%; min-width: 1050px; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 13px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: middle; }
    .data-table th { background: #f8fafc; color: #475569; font-size: 12px; text-transform: uppercase; }
    .data-table td { color: #334155; font-size: 13px; }
    .data-table tr:last-child td { border-bottom: 0; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
    .badge-baru { background: #fee2e2; color: #991b1b; }
    .badge-diproses { background: #fef3c7; color: #92400e; }
    .badge-selesai { background: #dcfce7; color: #166534; }
    .description { max-width: 240px; white-space: pre-line; }
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
    .btn-resolve {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
    }
    .btn-resolve:hover {
        background: #16a34a;
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
    <h1>🛡️ Laporan Bullying</h1>
    <p class="intro">Daftar laporan perundungan yang diterima dari formulir publik.</p>

    <?php if ($flash): ?>
        <div class="alert-flash <?= htmlspecialchars($flashType) ?>">
            <span><?= ($flashType === 'success') ? '✅' : '⚠️' ?></span>
            <div><?= htmlspecialchars($flash) ?></div>
        </div>
    <?php endif; ?>

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
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($laporan as $item): 
                        $st = strtolower($item['status'] ?? 'baru');
                        $badgeClass = ($st === 'selesai' || $st === 'diatasi') ? 'badge-selesai' : (($st === 'diproses') ? 'badge-diproses' : 'badge-baru');
                    ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d M Y H:i', strtotime($item['created_at']))) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($item['nama_pelapor']) ?></strong><br>
                                <small>📞 <?= htmlspecialchars($item['kontak']) ?></small><br>
                                <small style="color: #64748b;"><strong>Status:</strong> <?= htmlspecialchars($item['status_pelapor']) ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($item['nama_korban']) ?><br>
                                <small><?= htmlspecialchars($item['kelas_korban']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($item['jenis_bullying']) ?></td>
                            <td><?= htmlspecialchars($item['lokasi']) ?></td>
                            <td class="description"><?= htmlspecialchars($item['deskripsi']) ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($item['status'])) ?></span></td>
                            <td style="text-align: center; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 6px; align-items: center;">
                                    <?php if ($st !== 'selesai' && $st !== 'diatasi'): ?>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Tandai laporan bullying ini sebagai SELESAI / DIATASI?');">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                            <input type="hidden" name="status" value="selesai">
                                            <button type="submit" class="btn-action btn-resolve" title="Tandai Diatasi">
                                                ✓ Selesai
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
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

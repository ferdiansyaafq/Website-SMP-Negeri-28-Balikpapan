<?php
session_start();
require_once '../../config/database.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
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
                                <strong><?= (int)$item['jumlah_orang'] ?></strong> org
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

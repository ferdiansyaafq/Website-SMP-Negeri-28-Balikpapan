<?php
$page_title = 'Detail Berita';
require_once __DIR__ . '/config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$berita = null;

if ($id) {
    $stmt = $pdo->prepare(
        "SELECT id, jenis, tanggal, kategori, judul, ringkasan, isi, gambar
         FROM berita
         WHERE id = :id AND status = 'terbit'
         LIMIT 1"
    );
    $stmt->execute([':id' => $id]);
    $berita = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$berita) {
    http_response_code(404);
}

include 'header.php';
?>

<style>
    .detail-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 24px;
    }

    .detail-card {
        overflow: hidden;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        box-shadow: 0 10px 35px rgba(15, 23, 42, 0.08);
    }

    .detail-image {
        display: flex;
        min-height: 240px;
        align-items: center;
        justify-content: center;
        background: #e2e8f0;
        color: #64748b;
        font-weight: 700;
    }

    .detail-image img {
        width: 100%;
        height: 320px;
        object-fit: cover;
    }

    .detail-content {
        padding: 36px;
    }

    .detail-meta {
        margin-bottom: 12px;
        color: #0284c7;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .detail-content h1 {
        margin: 0 0 14px;
        color: #1e293b;
        font-size: 34px;
        line-height: 1.2;
    }

    .detail-summary {
        margin: 0 0 24px;
        color: #475569;
        font-size: 17px;
        font-weight: 600;
        line-height: 1.7;
    }

    .detail-body {
        color: #475569;
        font-size: 16px;
        line-height: 1.9;
        white-space: pre-line;
    }

    .back-link {
        display: inline-block;
        margin-top: 28px;
        color: #0284c7;
        font-weight: 700;
        text-decoration: none;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    @media (max-width: 600px) {
        .detail-container {
            margin: 24px auto;
            padding: 0 16px;
        }

        .detail-content {
            padding: 24px 20px;
        }

        .detail-content h1 {
            font-size: 26px;
        }

        .detail-image img {
            height: 220px;
        }
    }
</style>

<div class="detail-container">
    <?php if (!$berita): ?>
        <article class="detail-card">
            <div class="detail-content">
                <h1>Berita tidak ditemukan</h1>
                <p class="detail-summary">Berita mungkin sudah dihapus, masih berupa draft, atau tautannya tidak valid.</p>
                <a class="back-link" href="informasi.php">&larr; Kembali ke informasi</a>
            </div>
        </article>
    <?php else: ?>
        <article class="detail-card">
            <div class="detail-image">
                <?php if (!empty($berita['gambar'])): ?>
                    <img src="<?php echo htmlspecialchars($berita['gambar']); ?>" alt="<?php echo htmlspecialchars($berita['judul']); ?>">
                <?php else: ?>
                    Gambar Berita
                <?php endif; ?>
            </div>
            <div class="detail-content">
                <div class="detail-meta">
                    <?php echo htmlspecialchars($berita['jenis'] . ' · ' . $berita['kategori']); ?>
                    &middot;
                    <?php echo strtoupper(date('d M Y', strtotime($berita['tanggal']))); ?>
                </div>
                <h1><?php echo htmlspecialchars($berita['judul']); ?></h1>
                <p class="detail-summary"><?php echo htmlspecialchars($berita['ringkasan']); ?></p>
                <div class="detail-body"><?php echo htmlspecialchars($berita['isi']); ?></div>
                <a class="back-link" href="informasi.php">&larr; Kembali ke informasi</a>
            </div>
        </article>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

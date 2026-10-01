<?php
$page_title = "Informasi";
require_once __DIR__ . '/config/database.php';

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$itemsPerPage = 3;
$totalBerita = 0;
$beritaItems = [];

try {
    $totalBerita = (int) $pdo->query("SELECT COUNT(*) FROM berita WHERE status = 'terbit'")->fetchColumn();
    $totalPages = max(1, (int) ceil($totalBerita / $itemsPerPage));
    $currentPage = min($currentPage, $totalPages);
    $offset = ($currentPage - 1) * $itemsPerPage;

    $stmtBerita = $pdo->prepare(
        "SELECT id, jenis, tanggal, kategori, judul, ringkasan, gambar
         FROM berita
         WHERE status = 'terbit'
         ORDER BY tanggal DESC, id DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmtBerita->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmtBerita->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtBerita->execute();
    $beritaItems = $stmtBerita->fetchAll(PDO::FETCH_ASSOC);

    // Cek apakah file gambar berita ada
    foreach ($beritaItems as $key => $item) {
        $gambarPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $item['gambar'] ?? '');
        $beritaItems[$key]['gambar_exists'] = !empty($item['gambar']) && is_file($gambarPath);
    }
} catch (PDOException $e) {
    $totalPages = 1;
}
include 'header.php';
?>

<style>
.page-header {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: white;
    padding: 60px 40px;
    text-align: center;
}

.page-header h1 {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 10px;
}

.page-header p {
    font-size: 16px;
    opacity: 0.9;
}

.container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 40px;
}

.section {
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    margin-bottom: 30px;
    scroll-margin-top: 100px;
}

.section-title {
    font-size: 28px;
    color: #1e293b;
    margin-bottom: 20px;
    font-weight: 800;
    border-left: 5px solid #0284c7;
    padding-left: 15px;
}

.text-content {
    font-size: 16px;
    color: #475569;
    line-height: 1.9;
    margin-bottom: 15px;
    text-align: justify;
}

/* ========================================= */
/* BERITA & PENGUMUMAN - GRID DENGAN GAMBAR  */
/* ========================================= */
.berita-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 20px;
}

.berita-item {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 22px;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    padding: 20px;
    border-radius: 16px;
    border-left: 5px solid #0284c7;
    transition: all 0.3s ease;
    align-items: center;
}

.berita-item:hover {
    transform: translateX(8px);
    box-shadow: 0 12px 30px rgba(2, 132, 199, 0.15);
    border-left-color: #0369a1;
}

.berita-thumb {
    width: 220px;
    height: 150px;
    border-radius: 12px;
    overflow: hidden;
    background: #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.berita-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.berita-item:hover .berita-thumb img {
    transform: scale(1.08);
}

.thumb-placeholder {
    font-size: 40px;
    color: #94a3b8;
}

.berita-info {
    flex: 1;
    min-width: 0;
}

.berita-meta {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.berita-date-badge {
    background: #0284c7;
    color: white;
    padding: 5px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.berita-category {
    color: #0284c7;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.berita-info h3 {
    font-size: 19px;
    color: #1e293b;
    margin-bottom: 8px;
    font-weight: 800;
    line-height: 1.3;
}

.berita-info h3 a {
    color: inherit;
    text-decoration: none;
}

.berita-info h3 a:hover {
    color: #0284c7;
}

.berita-info p {
    color: #64748b;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 12px;
}

.berita-readmore {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #0284c7;
    text-decoration: underline;
    font-weight: 700;
    font-size: 14px;
    transition: gap 0.3s;
}

.berita-readmore:hover {
    gap: 10px;
    color: #0369a1;
}

/* PAGINATION */
.news-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 28px;
}

.news-pagination a,
.news-pagination span {
    min-width: 36px;
    padding: 9px 12px;
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
}

.news-pagination a {
    background: #e0f2fe;
    color: #0369a1;
}

.news-pagination a:hover,
.news-pagination .active {
    background: #0284c7;
    color: white;
}

/* EMPTY STATE */
.news-empty {
    padding: 40px 30px;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    color: #64748b;
    text-align: center;
    font-size: 15px;
}

/* KAIH */
.kaih-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 20px;
}

.kaih-item {
    display: flex;
    gap: 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
    padding: 20px 25px;
    border-radius: 16px;
    border-left: 5px solid #0284c7;
    transition: all 0.3s ease;
    align-items: flex-start;
}

.kaih-item:hover {
    transform: translateX(8px);
    box-shadow: 0 8px 25px rgba(2,132,199,0.12);
    border-left-color: #0369a1;
}

.kaih-number {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #0284c7, #0369a1);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 800;
    margin-top: 4px;
}

.kaih-content {
    flex: 1;
}

.kaih-content h4 {
    font-size: 18px;
    color: #1e293b;
    font-weight: 700;
    margin-bottom: 4px;
}

.kaih-tag {
    display: inline-block;
    background: #e0f2fe;
    color: #0284c7;
    padding: 3px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 10px;
}

.kaih-content p {
    font-size: 14px;
    color: #475569;
    line-height: 1.8;
    margin: 0;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .page-header {
        padding: 40px 20px;
    }
    .page-header h1 {
        font-size: 26px;
    }
    .container {
        padding: 0 20px;
        margin: 20px auto;
    }
    .section {
        padding: 25px;
    }
    .section-title {
        font-size: 22px;
    }
    .berita-item {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    .berita-thumb {
        width: 100%;
        height: 200px;
    }
    .berita-info h3 {
        font-size: 17px;
    }
}

@media (max-width: 480px) {
    .kaih-item {
        padding: 14px 16px;
        gap: 12px;
    }
    .kaih-number {
        width: 34px;
        height: 34px;
        font-size: 13px;
    }
    .kaih-content h4 {
        font-size: 14px;
    }
    .kaih-tag {
        font-size: 11px;
        padding: 2px 12px;
    }
    .kaih-content p {
        font-size: 12px;
        line-height: 1.6;
    }
    .berita-thumb {
        height: 160px;
    }
    .berita-info h3 {
        font-size: 15px;
    }
    .berita-info p {
        font-size: 13px;
    }
}
</style>

<div class="page-header">
    <h1>Informasi Sekolah</h1>
    <p>Berita, pengumuman, dan kegiatan terkini</p>
</div>

<div class="container">
    <span id="berita" aria-hidden="true"></span>

    <!-- ============================================ -->
    <!-- SECTION BERITA & PENGUMUMAN                  -->
    <!-- ============================================ -->
    <div class="section" id="pengumuman">
        <h2 class="section-title">📰 Berita &amp; Pengumuman</h2>

        <?php if (empty($beritaItems)): ?>
            <div class="news-empty">Belum ada berita atau pengumuman yang diterbitkan.</div>
        <?php else: ?>
            <div class="berita-list">
                <?php foreach ($beritaItems as $item): ?>
                <article class="berita-item">
                    <div class="berita-thumb">
                        <?php if ($item['gambar_exists']): ?>
                            <img src="<?php echo htmlspecialchars($item['gambar']); ?>"
                                 alt="<?php echo htmlspecialchars($item['judul']); ?>">
                        <?php else: ?>
                            <span class="thumb-placeholder">📷</span>
                        <?php endif; ?>
                    </div>
                    <div class="berita-info">
                        <div class="berita-meta">
                            <span class="berita-date-badge"><?php echo strtoupper(date('d M Y', strtotime($item['tanggal']))); ?></span>
                            <span class="berita-category"><?php echo htmlspecialchars(strtoupper($item['jenis'] . ' · ' . $item['kategori'])); ?></span>
                        </div>
                        <h3>
                            <a href="berita-detail.php?id=<?php echo (int) $item['id']; ?>">
                                <?php echo htmlspecialchars($item['judul']); ?>
                            </a>
                        </h3>
                        <p><?php echo htmlspecialchars($item['ringkasan']); ?></p>
                        <a href="berita-detail.php?id=<?php echo (int) $item['id']; ?>" class="berita-readmore">
                            Baca selengkapnya →
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
        <nav class="news-pagination" aria-label="Pagination berita">
            <?php if ($currentPage > 1): ?>
                <a href="?page=<?php echo $currentPage - 1; ?>#berita">Sebelumnya</a>
            <?php endif; ?>
            <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                <?php if ($page === $currentPage): ?>
                    <span class="active" aria-current="page"><?php echo $page; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $page; ?>#berita"><?php echo $page; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?php echo $currentPage + 1; ?>#berita">Berikutnya</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>

    <!-- ============================================ -->
    <!-- SECTION KAIH                                 -->
    <!-- ============================================ -->
    <div class="section" id="kaih">
        <h2 class="section-title">🌟 7 Kebiasaan Anak Indonesia Hebat (KAIH)</h2>
        <p class="text-content">Membangun karakter anak Indonesia yang hebat melalui pembiasaan harian.</p>

        <div class="kaih-list">
            <!-- KAIH 1 -->
            <div class="kaih-item">
                <div class="kaih-number">1</div>
                <div class="kaih-content">
                    <h4>Bangun Pagi &amp; Merapikan Tempat Tidur</h4>
                    <span class="kaih-tag">Kemandirian &amp; Disiplin</span>
                    <p>Membiasakan bangun pagi tepat waktu dan langsung merapikan tempat tidur sendiri. Kebiasaan ini melatih kedisiplinan, tanggung jawab, dan kemandirian anak dalam mengatur kehidupan sehari-hari.</p>
                </div>
            </div>

            <!-- KAIH 2 -->
            <div class="kaih-item">
                <div class="kaih-number">2</div>
                <div class="kaih-content">
                    <h4>Beribadah (Sholat Subuh / Ibadah Pagi)</h4>
                    <span class="kaih-tag">Religius - Beriman &amp; Bertakwa kepada Tuhan YME</span>
                    <p>Melaksanakan ibadah sesuai agama dan kepercayaan masing-masing (sholat subuh, doa pagi, atau ibadah lainnya). Memperkuat dimensi spiritual dan ketangguhan kepada Tuhan Yang Maha Esa sebagai fondasi karakter.</p>
                </div>
            </div>

            <!-- KAIH 3 -->
            <div class="kaih-item">
                <div class="kaih-number">3</div>
                <div class="kaih-content">
                    <h4>Berolahraga / Aktivitas Fisik</h4>
                    <span class="kaih-tag">Menjaga Kesehatan Raga (Kesejahteraan Diri)</span>
                    <p>Melakukan aktivitas fisik ringan seperti jalan pagi, senam, atau olahraga ringan lainnya minimal 15-30 menit. Menjaga kebugaran tubuh dan kesehatan mental melalui olahraga teratur.</p>
                </div>
            </div>

            <!-- KAIH 4 -->
            <div class="kaih-item">
                <div class="kaih-number">4</div>
                <div class="kaih-content">
                    <h4>Sarapan Sehat &amp; Minum Air Putih</h4>
                    <span class="kaih-tag">Pola Hidup Sehat &amp; Fokus Belajar</span>
                    <p>Mengonsumsi sarapan bergizi seimbang dan minum air putih yang cukup sebelum berangkat sekolah. Sarapan penting untuk energi belajar, konsentrasi, dan pertumbuhan optimal.</p>
                </div>
            </div>

            <!-- KAIH 5 -->
            <div class="kaih-item">
                <div class="kaih-number">5</div>
                <div class="kaih-content">
                    <h4>Gemar Membaca (Literasi)</h4>
                    <span class="kaih-tag">Bernalar Kritis &amp; Wawasan Luas</span>
                    <p>Meluangkan waktu membaca buku, artikel, atau bacaan positif minimal 15 menit per hari. Mendukung pembelajaran Bahasa Indonesia dan mengembangkan kemampuan berpikir kritis serta memperluas wawasan.</p>
                </div>
            </div>

            <!-- KAIH 6 -->
            <div class="kaih-item">
                <div class="kaih-number">6</div>
                <div class="kaih-content">
                    <h4>Membantu Orang Tua / Berpamitan</h4>
                    <span class="kaih-tag">Berbakti, Santun, dan Gotong Royong</span>
                    <p>Membantu pekerjaan rumah tangga seperti menyapu, mencuci piring, atau membantu orang tua sebelum berangkat. Juga membiasakan berpamitan dengan sopan. Melatih rasa tanggung jawab, empati, dan hormat kepada orang tua.</p>
                </div>
            </div>

            <!-- KAIH 7 -->
            <div class="kaih-item">
                <div class="kaih-number">7</div>
                <div class="kaih-content">
                    <h4>Menabung / Hidup Hemat</h4>
                    <span class="kaih-tag">Literasi Finansial &amp; Pengendalian Diri</span>
                    <p>Menyediakan sebagian uang jajan untuk ditabung atau membiasakan hidup hemat (tidak jajan berlebihan). Mengenalkan konsep pengelolaan keuangan sederhana, perencanaan masa depan, dan pengendalian diri sejak dini.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
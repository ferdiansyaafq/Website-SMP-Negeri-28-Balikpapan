<?php
$page_title = "Jendela Literasi - SMP Negeri 28 Balikpapan";
include 'header.php';
require_once 'config/database.php';

$search = trim($_GET['search'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');

$kategoriList = [];
try {
    $stmtKat = $pdo->query("SELECT DISTINCT kategori FROM jendela_literasi WHERE status = 'terbit' AND kategori IS NOT NULL AND TRIM(kategori) != '' ORDER BY kategori ASC");
    $kategoriList = $stmtKat->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $kategoriList = [];
}

$query = "SELECT * FROM jendela_literasi WHERE status = 'terbit'";
$params = [];

if ($search !== '') {
    $query .= " AND (judul LIKE ? OR penulis LIKE ? OR penerbit LIKE ? OR sinopsis LIKE ?)";
    $searchWildcard = "%{$search}%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
}

if ($kategori !== '') {
    $query .= " AND kategori = ?";
    $params[] = $kategori;
}

$query .= " ORDER BY id DESC";

$bukuList = [];
try {
    $stmtBuku = $pdo->prepare($query);
    $stmtBuku->execute($params);
    $bukuList = $stmtBuku->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bukuList = [];
}
?>

<link rel="stylesheet" href="assets/css/faq.css">
<link rel="stylesheet" href="assets/css/literasi.css">

<div class="page-header">
    <h1>📚 Jendela Literasi</h1>
    <p>Jelajahi dan nikmati koleksi buku digital, bahan bacaan, serta literasi SMP Negeri 28 Balikpapan.</p>
</div>

<div class="literasi-container">
    <div class="literasi-filter-card">
        <form method="GET" action="literasi.php" class="literasi-search-form">
            <div class="literasi-search-row">
                <input type="text" name="search" class="literasi-search-input" placeholder="Cari judul buku, nama penulis, atau penerbit..." value="<?php echo htmlspecialchars($search); ?>">
                <?php if ($kategori !== ''): ?>
                    <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($kategori); ?>">
                <?php endif; ?>
                <button type="submit" class="literasi-btn-search">🔍 Cari</button>
                <?php if ($search !== '' || $kategori !== ''): ?>
                    <a href="literasi.php" class="literasi-btn-reset">Reset</a>
                <?php endif; ?>
            </div>

            <?php if (!empty($kategoriList)): ?>
                <div class="literasi-categories">
                    <span class="literasi-category-label">Kategori:</span>
                    <a href="literasi.php<?php echo $search !== '' ? '?search=' . urlencode($search) : ''; ?>" class="category-pill <?php echo $kategori === '' ? 'active' : ''; ?>">Semua</a>
                    <?php foreach ($kategoriList as $kat): ?>
                        <?php
                        $katUrl = 'literasi.php?kategori=' . urlencode($kat);
                        if ($search !== '') {
                            $katUrl .= '&search=' . urlencode($search);
                        }
                        ?>
                        <a href="<?php echo htmlspecialchars($katUrl); ?>" class="category-pill <?php echo $kategori === $kat ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($kat); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="literasi-info-bar">
        <div>
            Menampilkan <strong><?php echo count($bukuList); ?></strong> buku literasi
            <?php if ($kategori !== ''): ?>
                pada kategori <strong>"<?php echo htmlspecialchars($kategori); ?>"</strong>
            <?php endif; ?>
            <?php if ($search !== ''): ?>
                dengan kata kunci <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($bukuList)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📖</div>
            <h3>Buku Tidak Ditemukan</h3>
            <p>
                <?php if ($search !== '' || $kategori !== ''): ?>
                    Tidak ada koleksi buku yang cocok dengan filter atau kata kunci pencarian Anda.
                <?php else: ?>
                    Belum ada buku literasi yang diterbitkan saat ini.
                <?php endif; ?>
            </p>
            <?php if ($search !== '' || $kategori !== ''): ?>
                <a href="literasi.php" class="literasi-btn-search" style="text-decoration: none; display: inline-block;">Lihat Semua Buku</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="literasi-grid">
            <?php foreach ($bukuList as $buku): ?>
                <?php
                $hasCover = !empty($buku['cover']) && file_exists(__DIR__ . '/' . ltrim($buku['cover'], '/'));
                $coverUrl = $hasCover ? htmlspecialchars($buku['cover']) : '';

                $hasPdf = !empty($buku['file_pdf']) && file_exists(__DIR__ . '/' . ltrim($buku['file_pdf'], '/'));
                $pdfUrl = $hasPdf ? htmlspecialchars($buku['file_pdf']) : '';

                $sinopsisClean = trim((string)$buku['sinopsis']);
                if ($sinopsisClean === '' || $sinopsisClean === '-') {
                    $sinopsisClean = 'Sinopsis belum tersedia untuk buku ini.';
                }

                $linkBaca = trim((string)($buku['link_baca'] ?? ''));
                if ($linkBaca !== '' && !preg_match('~^https?://~i', $linkBaca)) {
                    $linkBaca = 'https://' . $linkBaca;
                }

                $bookData = [
                    'judul' => $buku['judul'],
                    'penulis' => $buku['penulis'],
                    'penerbit' => $buku['penerbit'] ?: '-',
                    'tahun' => $buku['tahun_terbit'] ?: '-',
                    'kategori' => $buku['kategori'] ?: 'Umum',
                    'sinopsis' => $sinopsisClean,
                    'cover' => $coverUrl,
                    'pdf' => $pdfUrl,
                    'link' => $linkBaca
                ];
                $bookJson = htmlspecialchars(json_encode($bookData), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="book-card">
                    <div class="book-cover-wrapper">
                        <?php if ($hasCover): ?>
                            <img src="<?php echo $coverUrl; ?>" alt="Cover <?php echo htmlspecialchars($buku['judul']); ?>" class="book-cover-img" loading="lazy">
                        <?php else: ?>
                            <div class="book-cover-placeholder">
                                <div style="font-size: 40px; margin-bottom: 8px;">📖</div>
                                <div style="font-size: 13px; font-weight: 600; opacity: 0.9; max-width: 90%;"><?php echo htmlspecialchars($buku['judul']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($buku['kategori'])): ?>
                            <span class="book-badge"><?php echo htmlspecialchars($buku['kategori']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="book-content">
                        <h3 class="book-title" title="<?php echo htmlspecialchars($buku['judul']); ?>"><?php echo htmlspecialchars($buku['judul']); ?></h3>
                        <div class="book-author">✍️ <?php echo htmlspecialchars($buku['penulis']); ?></div>
                        <div class="book-meta">
                            <?php if (!empty($buku['penerbit'])): ?>
                                🏢 <?php echo htmlspecialchars($buku['penerbit']); ?>
                            <?php endif; ?>
                            <?php if (!empty($buku['tahun_terbit'])): ?>
                                (<?php echo htmlspecialchars($buku['tahun_terbit']); ?>)
                            <?php endif; ?>
                        </div>
                        <div class="book-synopsis">
                            <?php echo htmlspecialchars($sinopsisClean); ?>
                        </div>

                        <div class="book-actions">
                            <button type="button" class="btn-book-detail" onclick="openBookModal(<?php echo $bookJson; ?>)">
                                Detail
                            </button>

                            <button type="button" class="btn-book-read" onclick="readBook(<?php echo $bookJson; ?>)">
                                📖 Baca Buku
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="bookModal" class="modal-overlay" onclick="handleDetailBackdropClick(event)">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTitle">Detail Buku</h3>
            <button type="button" class="modal-close" onclick="closeBookModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-book-layout">
                <div id="modalCoverContainer"></div>
                <div class="modal-book-meta" style="flex: 1;">
                    <h4 id="modalBookTitle">-</h4>
                    <div class="modal-meta-row">
                        <span class="modal-meta-label">Penulis:</span>
                        <span id="modalBookAuthor">-</span>
                    </div>
                    <div class="modal-meta-row">
                        <span class="modal-meta-label">Penerbit:</span>
                        <span id="modalBookPublisher">-</span>
                    </div>
                    <div class="modal-meta-row">
                        <span class="modal-meta-label">Tahun:</span>
                        <span id="modalBookYear">-</span>
                    </div>
                    <div class="modal-meta-row">
                        <span class="modal-meta-label">Kategori:</span>
                        <span id="modalBookCategory" style="background: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 10px; font-weight: 600; font-size: 11px;">-</span>
                    </div>
                    <div class="modal-meta-row" style="margin-top: 8px;">
                        <span class="modal-meta-label">Format:</span>
                        <span id="modalBookFormat" style="font-size: 12px; font-weight: 600;">-</span>
                    </div>
                </div>
            </div>

            <div class="modal-synopsis-box">
                <div class="modal-synopsis-title">📖 Sinopsis &amp; Deskripsi Buku</div>
                <div id="modalBookSynopsis" class="modal-synopsis-text">-</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-book-detail" onclick="closeBookModal()" style="flex: none; min-width: 90px;">Tutup</button>
            <button type="button" id="modalReadBtn" class="btn-book-read" style="flex: none;" onclick="triggerReadFromDetail()">
                📖 Baca Buku Sekarang
            </button>
        </div>
    </div>
</div>

<div id="pdfModal" class="modal-overlay" onclick="handlePdfBackdropClick(event)">
    <div class="modal-box-pdf">
        <div class="modal-header">
            <div>
                <h3 id="pdfModalTitle" style="margin: 0; font-size: 17px; color: #0f172a;">Membaca Buku</h3>
                <span style="font-size: 12px; color: #64748b;">Pratinjau Isi Buku Digital (PDF)</span>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <a id="pdfOpenNewTab" href="#" target="_blank" rel="noopener noreferrer" class="btn-book-detail" style="padding: 7px 14px; font-size: 12px; text-decoration: none;">
                    Buka Tab Baru ↗
                </a>
                <button type="button" class="modal-close" onclick="closePdfModal()">&times;</button>
            </div>
        </div>
        <div class="pdf-viewer-container">
            <iframe id="pdfViewerFrame" class="pdf-viewer-frame" src="" allow="fullscreen"></iframe>
        </div>
    </div>
</div>

<div id="noPdfModal" class="modal-overlay" onclick="handleNoPdfBackdropClick(event)">
    <div class="modal-box" style="max-width: 480px; text-align: center;">
        <div class="modal-header" style="justify-content: flex-end; border-bottom: none; padding-bottom: 0;">
            <button type="button" class="modal-close" onclick="closeNoPdfModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding-top: 10px;">
            <div style="font-size: 48px; margin-bottom: 12px;">📄</div>
            <h3 id="noPdfBookTitle" style="margin: 0 0 10px 0; font-size: 18px; color: #0f172a;">Judul Buku</h3>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin-bottom: 20px;">
                Berkas PDF buku ini belum diunggah oleh admin sekolah. Anda tetap dapat membaca ringkasan sinopsis atau membaca edisi cetak di perpustakaan sekolah.
            </p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="button" class="btn-book-detail" onclick="closeNoPdfModal(); openBookModal(currentActiveBook);" style="flex: none; padding: 10px 20px;">
                    Lihat Sinopsis
                </button>
                <button type="button" class="btn-book-read" onclick="closeNoPdfModal()" style="flex: none; padding: 10px 20px;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentActiveBook = null;

function readBook(data) {
    currentActiveBook = data;
    if (data.pdf && data.pdf.trim() !== '') {
        openPdfModal(data.judul, data.pdf);
    } else if (data.link && data.link.trim() !== '') {
        window.open(data.link, '_blank', 'noopener,noreferrer');
    } else {
        openNoPdfModal(data.judul);
    }
}

function openPdfModal(judul, pdfPath) {
    document.getElementById('pdfModalTitle').textContent = judul || 'Membaca Buku';
    document.getElementById('pdfOpenNewTab').href = pdfPath;
    document.getElementById('pdfViewerFrame').src = pdfPath;

    const modal = document.getElementById('pdfModal');
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePdfModal() {
    const modal = document.getElementById('pdfModal');
    modal.classList.remove('open');
    document.getElementById('pdfViewerFrame').src = '';
    document.body.style.overflow = '';
}

function handlePdfBackdropClick(event) {
    if (event.target === document.getElementById('pdfModal')) {
        closePdfModal();
    }
}

function openNoPdfModal(judul) {
    document.getElementById('noPdfBookTitle').textContent = judul || 'Buku Literasi';
    const modal = document.getElementById('noPdfModal');
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeNoPdfModal() {
    const modal = document.getElementById('noPdfModal');
    modal.classList.remove('open');
    document.body.style.overflow = '';
}

function handleNoPdfBackdropClick(event) {
    if (event.target === document.getElementById('noPdfModal')) {
        closeNoPdfModal();
    }
}

function openBookModal(data) {
    currentActiveBook = data;
    document.getElementById('modalTitle').textContent = 'Detail Buku';
    document.getElementById('modalBookTitle').textContent = data.judul || '-';
    document.getElementById('modalBookAuthor').textContent = data.penulis || '-';
    document.getElementById('modalBookPublisher').textContent = data.penerbit || '-';
    document.getElementById('modalBookYear').textContent = data.tahun || '-';
    document.getElementById('modalBookCategory').textContent = data.kategori || 'Umum';
    document.getElementById('modalBookSynopsis').textContent = data.sinopsis || 'Tidak ada sinopsis.';

    const formatEl = document.getElementById('modalBookFormat');
    if (data.pdf && data.pdf.trim() !== '') {
        formatEl.innerHTML = '<span style="color: #16a34a;">📄 Berkas PDF Tersedia</span>';
    } else if (data.link && data.link.trim() !== '') {
        formatEl.innerHTML = '<span style="color: #0284c7;">🔗 Tautan Digital Tersedia</span>';
    } else {
        formatEl.innerHTML = '<span style="color: #94a3b8;">Edisi Cetak Perpustakaan</span>';
    }

    const coverContainer = document.getElementById('modalCoverContainer');
    if (data.cover) {
        coverContainer.innerHTML = '<img src="' + encodeURI(data.cover) + '" alt="Cover" class="modal-book-cover">';
    } else {
        coverContainer.innerHTML = '<div class="modal-book-placeholder">📖</div>';
    }

    const modal = document.getElementById('bookModal');
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeBookModal() {
    const modal = document.getElementById('bookModal');
    modal.classList.remove('open');
    document.body.style.overflow = '';
}

function handleDetailBackdropClick(event) {
    if (event.target === document.getElementById('bookModal')) {
        closeBookModal();
    }
}

function triggerReadFromDetail() {
    if (currentActiveBook) {
        closeBookModal();
        readBook(currentActiveBook);
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closePdfModal();
        closeBookModal();
        closeNoPdfModal();
    }
});
</script>

<?php include 'footer.php'; ?>

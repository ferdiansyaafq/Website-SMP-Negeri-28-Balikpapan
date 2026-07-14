<?php
session_start();

function pickFirstExistingImage(array $candidates, string $fallback): string
{
    foreach ($candidates as $path) {
        $fsPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (is_file($fsPath)) {
            $v = (int) @filemtime($fsPath);
            return $path . ($v > 0 ? ('?v=' . $v) : '');
        }
    }
    return $fallback;
}

$logoSekolah = pickFirstExistingImage([
    'assets/img/logo-sekolah.png',
    'assets/img/logo.png',
], 'assets/img/logo-sekolah.svg');

$slideshowPhotos = [];
try {
    $dbSlide = new mysqli('localhost', 'root', '', 'kaih');
    $dbSlide->set_charset('utf8mb4');
    $res = $dbSlide->query("SELECT filename, judul FROM foto_slideshow ORDER BY urutan ASC, id ASC");
    while ($row = $res->fetch_assoc()) {
        $fPath = 'assets/img/slideshow/' . $row['filename'];
        $fsPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fPath);
        if (is_file($fsPath)) {
            $slideshowPhotos[] = [
                'src' => $fPath,
                'alt' => $row['judul'] ?: 'Foto Kegiatan',
            ];
        }
    }
    $dbSlide->close();
} catch (Throwable $e) {
}

if (empty($slideshowPhotos)) {
    $slideshowPhotos = [
        ['src' => 'assets/img/fotoguru1.jpeg', 'alt' => 'Karya Siswa'],
        ['src' => 'assets/img/fotoguru2.jpeg', 'alt' => 'Kegiatan Belajar'],
        ['src' => 'assets/img/fotosiswa.jpg', 'alt' => 'Kegiatan Belajar'],
        ['src' => 'assets/img/karyasiswa.jpg', 'alt' => 'Kegiatan Belajar']
    ];
}

$beritaData = [
    [
        'tanggal' => '10 JUL 2026',
        'kategori' => 'Pengumuman',
        'judul' => 'PPDB Tahun Ajaran 2026/2027 Resmi Dibuka',
        'deskripsi' => 'Penerimaan Peserta Didik Baru untuk tahun ajaran 2026/2027 telah resmi dibuka. Segera daftarkan putra-putri Anda.',
        'gambar' => ''
    ],
    [
        'tanggal' => '05 JUL 2026',
        'kategori' => 'Akademik',
        'judul' => 'Implementasi Kurikulum Merdeka dengan Deep Learning',
        'deskripsi' => 'SMPN 28 Balikpapan resmi menerapkan pendekatan Pembelajaran Mendalam untuk meningkatkan kualitas pendidikan.',
        'gambar' => ''
    ],
    [
        'tanggal' => '01 JUL 2026',
        'kategori' => 'Prestasi',
        'judul' => 'Program 7 Kebiasaan Anak Indonesia Hebat Diluncurkan',
        'deskripsi' => 'Program KAIH resmi diluncurkan untuk membentuk karakter dan kebiasaan positif siswa setiap hari.',
        'gambar' => ''
    ]
];

$fasilitasData = [
    [
        'nama' => 'Ruang Kelas Adaptif',
        'deskripsi' => '9 ruang kelas dengan fasilitas pendukung dan AC',
        'gambar' => ''
    ],
    [
        'nama' => 'Masjid Sekolah',
        'deskripsi' => 'Ruang ibadah yang nyaman untuk kegiatan keagamaan',
        'gambar' => ''
    ],
    [
        'nama' => 'Lapangan Olahraga',
        'deskripsi' => 'Lapangan multifungsi untuk basket, futsal, dan voli',
        'gambar' => ''
    ],
    [
        'nama' => 'Kantin Sehat',
        'deskripsi' => 'Kantin dengan menu gizi seimbang dan bersih',
        'gambar' => ''
    ]
];

$ekskulData = [
    [
        'nama' => 'Pramuka',
        'jadwal' => 'Jumat, 14.00-16.00',
        'pembina' => 'Pembimbing',
        'gambar' => ''
    ],
    [
        'nama' => 'Futsal',
        'jadwal' => 'Selasa & Kamis, 15.00-17.00',
        'pembina' => 'Pembimbing',
        'gambar' => ''
    ],
    [
        'nama' => 'Pencak Silat',
        'jadwal' => 'Selasa, 15.30-17.00',
        'pembina' => 'Pembimbing',
        'gambar' => ''
    ],
    [
        'nama' => 'PMR',
        'jadwal' => 'Kamis, 15.30-17.00',
        'pembina' => 'Pembimbing',
        'gambar' => ''
    ],
    [
        'nama' => 'Memanah',
        'jadwal' => 'Senin, 15.30-17.00',
        'pembina' => 'Pembimbing',
        'gambar' => ''
    ],
    [
        'nama' => 'Kader Lingkungan',
        'jadwal' => 'Sabtu, 08.00-10.00',
        'pembina' => 'Pembimbing',
        'gambar' => ''
    ],
];

include 'header.php';
?>

<style>
.container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 40px;
}

.hero-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    align-items: center;
    background: transparent;
    padding: 40px 0;
    border-radius: 0;
    box-shadow: none;
}

.text-content h2 {
    font-size: 42px;
    color: #1a1a1a;
    margin-bottom: 20px;
    line-height: 1.15;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.text-content h2 span {
    color: #1e6fbf;
    display: inline;
}

.tagline {
    font-size: 18px;
    color: #1e6fbf;
    font-weight: 700;
    font-style: italic;
    margin-bottom: 28px;
    line-height: 1.6;
    letter-spacing: 0.3px;
}

.description {
    font-size: 15.5px;
    color: #4a7ba8;
    line-height: 2;
    margin-bottom: 18px;
    text-align: justify;
    font-weight: 400;
}

.image-section {
    background: #0284c7;
    padding: 20px;
    border-radius: 20px;
    position: relative;
}

.carousel {
    position: relative;
    overflow: hidden;
    border-radius: 15px;
    background: white;
}

.carousel img {
    width: 100%;
    height: 450px;
    object-fit: cover;
    display: block;
}

.carousel-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.9);
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 20px;
    color: #0284c7;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.carousel-btn:hover {
    background: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.carousel-btn.prev { left: 15px; }
.carousel-btn.next { right: 15px; }

.berita-section {
    background: white;
    padding: 60px 0;
    margin-top: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}

.section-header {
    text-align: center;
    margin-bottom: 40px;
}

.section-header h2 {
    font-size: 32px;
    color: #1e293b;
    font-weight: 800;
    margin-bottom: 10px;
}

.section-header h2 span {
    color: #0284c7;
}

.section-header p {
    font-size: 15px;
    color: #64748b;
    max-width: 600px;
    margin: 0 auto;
}

.berita-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    padding: 0 40px;
}

.berita-card {
    background: #f8fafc;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s;
    border: 1px solid #e2e8f0;
}

.berita-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(2,132,199,0.15);
    border-color: #0284c7;
}

.berita-image {
    height: 200px;
    background: #e2e8f0;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.berita-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.berita-image .placeholder {
    color: #94a3b8;
    font-size: 14px;
    font-weight: 600;
}

.berita-date {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
}

.berita-content {
    padding: 20px;
}

.berita-category {
    display: inline-block;
    background: #e0f2fe;
    color: #0284c7;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    margin-bottom: 10px;
    text-transform: uppercase;
}

.berita-content h3 {
    font-size: 18px;
    color: #1e293b;
    margin-bottom: 10px;
    font-weight: 700;
    line-height: 1.4;
}

.berita-content p {
    font-size: 14px;
    color: #64748b;
    line-height: 1.6;
    margin-bottom: 15px;
}

.berita-link {
    color: #0284c7;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: gap 0.3s;
}

.berita-link:hover {
    gap: 10px;
}

.fasilitas-section {
    background: white;
    padding: 60px 0;
    margin-top: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}

.fasilitas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    padding: 0 40px;
}

.fasilitas-card {
    background: #f8fafc;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s;
    border: 1px solid #e2e8f0;
    text-align: center;
}

.fasilitas-card:hover {
    transform: translateY(-5px);
    border-color: #0284c7;
    box-shadow: 0 15px 30px rgba(2,132,199,0.15);
}

.fasilitas-image {
    height: 180px;
    overflow: hidden;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fasilitas-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.fasilitas-image .placeholder {
    color: #94a3b8;
    font-size: 14px;
    font-weight: 600;
}

.fasilitas-card:hover .fasilitas-image img {
    transform: scale(1.05);
}

.fasilitas-body {
    padding: 20px;
}

.fasilitas-body h4 {
    font-size: 16px;
    color: #1e293b;
    margin-bottom: 8px;
    font-weight: 700;
}

.fasilitas-body p {
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
}

.ekskul-section {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    padding: 60px 0;
    margin-top: 40px;
    border-radius: 20px;
    color: white;
}

.ekskul-section .section-header h2 {
    color: white;
}

.ekskul-section .section-header p {
    color: rgba(255,255,255,0.9);
}

.ekskul-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
    padding: 0 40px;
}

.ekskul-card {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s;
    border: 1px solid rgba(255,255,255,0.2);
}

.ekskul-card:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-5px);
}

.ekskul-image {
    height: 160px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.05);
}

.ekskul-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.ekskul-image .placeholder {
    color: rgba(255,255,255,0.6);
    font-size: 14px;
    font-weight: 600;
}

.ekskul-card:hover .ekskul-image img {
    transform: scale(1.05);
}

.ekskul-body {
    padding: 18px 20px 20px;
    text-align: center;
}

.ekskul-body h4 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 8px;
}

.ekskul-body .jadwal {
    font-size: 12px;
    opacity: 0.9;
    margin-bottom: 5px;
}

.ekskul-body .pembina {
    font-size: 11px;
    opacity: 0.8;
}

.features-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 30px;
}

.feature-item {
    background: white;
    padding: 15px 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s;
}

.feature-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
}

.feature-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.feature-text {
    font-size: 14px;
    color: #1e293b;
    font-weight: 600;
}

footer a:hover {
    opacity: 1;
    transform: translateY(-3px);
}

footer a[href*="wa.me"]:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(37,211,102,0.6);
}

@media (max-width: 968px) {
    .hero-section {
        grid-template-columns: 1fr;
        padding: 30px;
    }
    .text-content h2 {
        font-size: 28px;
    }
    .image-section {
        order: -1;
    }
    .carousel img {
        height: 300px;
    }
}

@media (max-width: 768px) {
    .container {
        padding: 0 20px;
        margin: 20px auto;
    }
    .hero-section {
        padding: 25px;
    }
    .text-content h2 {
        font-size: 24px;
    }
    .tagline {
        font-size: 15px;
    }
    .description {
        font-size: 14px;
    }
    .berita-grid,
    .fasilitas-grid,
    .ekskul-grid {
        grid-template-columns: 1fr;
        padding: 0 20px;
    }
    .section-header h2 {
        font-size: 24px;
    }
    .berita-section,
    .fasilitas-section,
    .ekskul-section {
        padding: 40px 0;
    }
}

@media (max-width: 480px) {
    .hero-section {
        padding: 20px;
    }
    .text-content h2 {
        font-size: 22px;
    }
}
</style>

<body> 
<div class="container">
    <div class="hero-section">
        <div class="text-content">
            <h2>SMP Negeri 28 <span>Balikpapan</span></h2>
            <p class="tagline">"Cahaya Pemandu" bagi karakter siswa di pesisir Manggar.</p>
            <p class="description">
                Sistem rapor digital modern yang menjadi mercusuar pendidikan, membimbing setiap siswa menuju prestasi terbaik. KAIH (Karakter Aktivitas Ibadah Harian) hadir sebagai solusi monitoring perkembangan siswa secara komprehensif dan real-time.
            </p>
            <p class="description">
                Dengan teknologi digital terkini, kami memfasilitasi kolaborasi antara guru, siswa, dan orang tua dalam memantau dan mengembangkan karakter serta prestasi akademik siswa setiap harinya.
            </p>
            <div class="features-grid">
                <div class="feature-item">
                    <span class="feature-icon">📋</span>
                    <span class="feature-text">Rapor Digital Terintegrasi</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">⚡</span>
                    <span class="feature-text">Monitoring Aktivitas Harian</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">📊</span>
                    <span class="feature-text">Laporan Perkembangan Siswa</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">💡</span>
                    <span class="feature-text">Kolaborasi Guru & Orang Tua</span>
                </div>
            </div>
        </div>

        <div class="image-section">
            <div class="carousel">
                <img src="<?php echo htmlspecialchars($slideshowPhotos[0]['src']); ?>" 
                     alt="<?php echo htmlspecialchars($slideshowPhotos[0]['alt']); ?>" 
                     id="carousel-image">
                <button class="carousel-btn prev" onclick="changeImage(-1)">‹</button>
                <button class="carousel-btn next" onclick="changeImage(1)">›</button>
            </div>
        </div>
    </div>
</div>

<section class="berita-section">
    <div class="section-header">
        <h2>Berita <span>Terkini</span></h2>
        <p>Informasi dan kabar terbaru dari SMP Negeri 28 Balikpapan</p>
    </div>
    <div class="berita-grid">
        <?php foreach ($beritaData as $item): ?>
        <div class="berita-card">
            <div class="berita-image">
                <?php if (!empty($item['gambar'])): ?>
                    <img src="<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['judul']); ?>">
                <?php else: ?>
                    <span class="placeholder">Gambar Berita</span>
                <?php endif; ?>
                <span class="berita-date"><?php echo $item['tanggal']; ?></span>
            </div>
            <div class="berita-content">
                <span class="berita-category"><?php echo $item['kategori']; ?></span>
                <h3><?php echo $item['judul']; ?></h3>
                <p><?php echo $item['deskripsi']; ?></p>
                <a href="#" class="berita-link">Baca Selengkapnya →</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="fasilitas-section">
    <div class="section-header">
        <h2>Fasilitas <span>Sekolah</span></h2>
        <p>Sarana dan prasarana yang mendukung proses pembelajaran</p>
    </div>
    <div class="fasilitas-grid">
        <?php foreach ($fasilitasData as $item): ?>
        <div class="fasilitas-card">
            <div class="fasilitas-image">
                <?php if (!empty($item['gambar'])): ?>
                    <img src="<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama']); ?>">
                <?php else: ?>
                    <span class="placeholder">Gambar Fasilitas</span>
                <?php endif; ?>
            </div>
            <div class="fasilitas-body">
                <h4><?php echo $item['nama']; ?></h4>
                <p><?php echo $item['deskripsi']; ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="ekskul-section">
    <div class="section-header">
        <h2>Ekstrakurikuler</h2>
        <p>Pengembangan minat dan bakat siswa melalui kegiatan pilihan</p>
    </div>
    <div class="ekskul-grid">
        <?php foreach ($ekskulData as $item): ?>
        <div class="ekskul-card">
            <div class="ekskul-image">
                <?php if (!empty($item['gambar'])): ?>
                    <img src="<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama']); ?>">
                <?php else: ?>
                    <span class="placeholder">Gambar Ekskul</span>
                <?php endif; ?>
            </div>
            <div class="ekskul-body">
                <h4><?php echo $item['nama']; ?></h4>
                <div class="jadwal">📅 <?php echo $item['jadwal']; ?></div>
                <div class="pembina">👨 <?php echo $item['pembina']; ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="footer" style="background: white; padding: 60px 0 40px; border-top: 3px solid #0284c7;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 40px;">
        <h2 style="text-align: center; font-size: 32px; color: #1e293b; margin-bottom: 40px; font-weight: 800;">Lokasi Sekolah</h2>
        <div style="border-radius: 15px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.xxx!2d116.9685572!3d-1.1991462!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2df14f7e778cf3a1%3A0x7264a9c9bc664794!2sSMP%20Negeri%2028%20Balikpapan!5e0!3m2!1sid!2sid!4v1234567890!5m2!1sid!2sid" 
                width="100%" 
                height="100%" 
                style="border:0; min-height: 350px;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>
<script>
const images = <?php echo json_encode($slideshowPhotos); ?>;
let currentIndex = 0;

function changeImage(direction) {
    currentIndex += direction;
    if (currentIndex < 0) currentIndex = images.length - 1;
    if (currentIndex >= images.length) currentIndex = 0;
    
    const img = document.getElementById('carousel-image');
    if (!img) return;
    
    img.style.opacity = '0';
    setTimeout(() => {
        img.src = images[currentIndex].src;
        img.alt = images[currentIndex].alt;
        img.style.opacity = '1';
    }, 200);
}

setInterval(() => {
    changeImage(1);
}, 5000);

document.addEventListener('DOMContentLoaded', function() {
    const carouselImg = document.getElementById('carousel-image');
    if (carouselImg) {
        carouselImg.style.transition = 'opacity 0.3s ease';
    }
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>
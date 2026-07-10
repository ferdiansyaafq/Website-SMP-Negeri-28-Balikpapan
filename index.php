<?php
session_start();

// Fungsi untuk memilih gambar yang ada
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

// Load slideshow photos
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
    // Fallback ke gambar statis
}

if (empty($slideshowPhotos)) {
    $slideshowPhotos = [
        ['src' => 'assets/img/fotoguru1.jpeg', 'alt' => 'Karya Siswa'],
        ['src' => 'assets/img/fotoguru2.jpeg', 'alt' => 'Kegiatan Belajar'],
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMP Negeri 28 Balikpapan - MERCUSUAR</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
        }

        /* ═══════════════════════════════════════════
           HEADER
        ═══════════════════════════════════════════ */
        .header {
            background: white;
            padding: 15px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 4px solid #0284c7;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-shrink: 0;
        }

        .logo {
            width: 55px;
            height: 55px;
            border-radius: 50%;
        }

        .school-name h1 {
            font-size: 20px;
            color: #1e293b;
            margin-bottom: 3px;
            font-weight: 700;
        }

        .school-name p {
            font-size: 13px;
            color: #64748b;
        }

        /* ═══════════════════════════════════════════
           DESKTOP NAVIGATION
        ═══════════════════════════════════════════ */
        .nav {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .nav a {
            text-decoration: none;
            color: #64748b;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 15px;
            white-space: nowrap;
        }

        .nav a:hover,
        .nav a.active {
            background: #e0f2fe;
            color: #0284c7;
        }

        /* ═══════════════════════════════════════════
           HAMBURGER BUTTON
        ═══════════════════════════════════════════ */
        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 8px;
            background: none;
            border: none;
            z-index: 1001;
            gap: 0;
        }

        .hamburger span {
            display: block;
            width: 25px;
            height: 3px;
            background: #0284c7;
            margin: 3px 0;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
        }

        /* ═══════════════════════════════════════════
           MOBILE MENU PANEL
        ═══════════════════════════════════════════ */
        .mobile-menu {
            position: fixed;
            top: 0;
            left: -300px;
            width: 280px;
            height: 100vh;
            background: white;
            box-shadow: 2px 0 15px rgba(0,0,0,0.15);
            transition: left 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }

        .mobile-menu.active {
            left: 0;
        }

        .mobile-menu-header {
            background: #0284c7;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mobile-menu-header h3 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .mobile-menu-close {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .mobile-menu-items {
            padding: 20px 0;
        }

        .mobile-menu-items a {
            display: block;
            padding: 15px 20px;
            color: #1e293b;
            text-decoration: none;
            font-weight: 600;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        .mobile-menu-items a:hover,
        .mobile-menu-items a.active {
            background: #e0f2fe;
            color: #0284c7;
            padding-left: 25px;
        }

        /* ═══════════════════════════════════════════
           OVERLAY
        ═══════════════════════════════════════════ */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            backdrop-filter: blur(3px);
        }

        .overlay.active {
            display: block;
        }

       /* ═══════════════════════════════════════════
   MAIN CONTENT
═══════════════════════════════════════════ */
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
    background: transparent; /* Hapus background putih */
    padding: 40px 0;
    border-radius: 0;
    box-shadow: none; /* Hapus shadow */
}

.text-content h2 {
    font-size: 42px;
    color: #1a1a1a; /* Hitam pekat untuk "SMP Negeri 28" */
    margin-bottom: 20px;
    line-height: 1.15;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.text-content h2 span {
    color: #1e6fbf; /* Biru untuk "Balikpapan" */
    display: block; /* "Balikpapan" pindah ke baris baru */
}

.tagline {
    font-size: 18px;
    color: #1e6fbf; /* Biru */
    font-weight: 700;
    font-style: italic;
    margin-bottom: 28px;
    line-height: 1.6;
    letter-spacing: 0.3px;
}

.description {
    font-size: 15.5px;
    color: #4a7ba8; /* Biru keabu-abuan terang */
    line-height: 2;
    margin-bottom: 18px;
    text-align: justify; /* Rata kanan-kiri */
    font-weight: 400;
}

        /* ═══════════════════════════════════════════
           IMAGE CAROUSEL
        ═══════════════════════════════════════════ */
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

        /* ═══════════════════════════════════════════
           RESPONSIVE - TABLET
        ═══════════════════════════════════════════ */
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

            .nav a {
                padding: 8px 15px;
                font-size: 14px;
            }
        }

        /* ═══════════════════════════════════════════
           RESPONSIVE - MOBILE
        ═══════════════════════════════════════════ */
        @media (max-width: 768px) {
            .header {
                padding: 12px 20px;
            }

            .school-name h1 {
                font-size: 14px;
            }

            .school-name p {
                font-size: 11px;
            }

            .logo {
                width: 45px;
                height: 45px;
            }

            /* Tampilkan hamburger di mobile */
            .hamburger {
                display: flex;
            }

            /* Sembunyikan nav desktop di mobile */
            .nav {
                display: none;
            }

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
        }

        /* ═══════════════════════════════════════════
           RESPONSIVE - SMALL MOBILE
        ═══════════════════════════════════════════ */
        @media (max-width: 480px) {
            .school-name h1 {
                font-size: 13px;
            }

            .school-name p {
                font-size: 10px;
            }

            .logo {
                width: 40px;
                height: 40px;
            }

            .mobile-menu {
                width: 250px;
            }

            .hero-section {
                padding: 20px;
            }

            .text-content h2 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleMenu()"></div>

    <!-- Mobile Menu Panel -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <h3>MENU</h3>
            <button class="mobile-menu-close" onclick="toggleMenu()">×</button>
        </div>
        <div class="mobile-menu-items">
            <a href="index.php" class="active">Beranda</a>
            <a href="profil.php">Profil</a>
            <a href="informasi.php">Informasi</a>
            <a href="fitur.php">Fitur</a>
            <a href="panduan.php">Panduan</a>
            <a href="kontak.php">Kontak</a>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo-section">
                <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo SMP 28" class="logo">
                <div class="school-name">
                    <h1>SMP NEGERI 28 BALIKPAPAN</h1>
                    <p>MERCUSUAR (Media Rekap Cerdas Unjuk Aktivitas Rapor)</p>
                </div>
            </div>
            
            <!-- Desktop Navigation -->
            <nav class="nav">
                <a href="index.php" class="active">Beranda</a>
                <a href="profil.php">Profil</a>
                <a href="informasi.php">Informasi</a>
                <a href="fitur.php">Fitur</a>
                <a href="panduan.php">Panduan</a>
                <a href="kontak.php">Kontak</a>
            </nav>
            
            <!-- Hamburger Menu Button -->
            <button class="hamburger" id="hamburger" onclick="toggleMenu()" aria-label="Toggle Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <!-- Main Content -->
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
    <!-- Fitur Grid -->
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

    <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); margin-top: 80px; padding: 60px 0 30px; color: white; position: relative; overflow: hidden;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 40px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">
                
                <!-- Jam Layanan -->
                <div>
                    <h4 style="font-size: 18px; margin-bottom: 10px; font-weight: 700;">Jam Layanan</h4>
                    <p style="margin-bottom: 10px; opacity: 0.9; line-height: 1.8;">
                        Senin - Jumat (07.00 - 12.00 WITA)<br>
                    </p>
                </div>

                <!-- Menu -->
                <div>
                    <h4 style="font-size: 18px; margin-bottom: 10px; font-weight: 700;">Menu Cepat</h4>
                    <ul style="list-style: none; line-height: 2.2;">
                        <li><a href="profil.php" style="color: white; text-decoration: none; opacity: 0.9; transition: opacity 0.3s;">Profil Sekolah</a></li>
                        <li><a href="informasi.php" style="color: white; text-decoration: none; opacity: 0.9; transition: opacity 0.3s;">Informasi</a></li>
                        <li><a href="fitur.php" style="color: white; text-decoration: none; opacity: 0.9; transition: opacity 0.3s;">Fitur KAIH</a></li>
                        <li><a href="panduan.php" style="color: white; text-decoration: none; opacity: 0.9; transition: opacity 0.3s;">Panduan</a></li>
                    </ul>
                </div>

                <!-- Alamat Sekolah -->
                <div>
                    <h4 style="font-size: 18px; margin-bottom: 10px; font-weight: 700;">Alamat Sekolah</h4>
                    <p style="opacity: 0.9; line-height: 1.8; margin-bottom: 10px;">
                        Jl. Persatuan<br>
                        Kelurahan Manggar Baru<br>
                        Kecamatan Balikpapan Timur<br>
                        Kota Balikpapan, Kalimantan Timur<br>
                        Kode Pos: 76115
                    </p>
                </div>

                <div>
                    <h4 style="font-size: 18px; margin-bottom: 10px; font-weight: 700;">Media Sosial Kami</h4>
                    <div style="display: flex; gap: 12px;">
                    <a href="https://www.instagram.com/smpnegeri28bpp/" target="_blank" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: all 0.3s;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <a href="https://www.youtube.com/@SMPNegeri28BalikpapanTimur" target="_blank" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: all 0.3s;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                    </a>
                    <a href="https://wa.me/6281234567890" target="_blank" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: all 0.3s;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297a11.815 11.815 0 00-8.413-3.476c-6.53 0-11.839 5.309-11.839 11.839 0 2.093.544 4.136 1.579 5.935l-1.436 5.238 5.378-1.413a11.84 11.84 0 005.866 1.483h.005c6.53 0 11.839-5.309 11.839-11.839 0-3.164-1.229-6.135-3.467-8.368"/></svg>
                    </a>
                    <a href="mailto:smpn28balikpapan@gmail.com" target="_blank" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: all 0.3s;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                    </a>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 30px; text-align: center; opacity: 0.8; font-size: 14px;">
                <p>&copy; <?php echo date('Y'); ?> SMP Negeri 28 Balikpapan.</p>
            </div>
        </div>
    </footer>

    <style>
        /* ═══════════════════════════════════════════
   FITUR GRID
   ═══════════════════════════════════════════ */
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

    </style>

    <script>
        // Toggle Mobile Menu
        function toggleMenu() {
            const hamburger = document.getElementById('hamburger');
            const mobileMenu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('overlay');
            
            if (!hamburger || !mobileMenu || !overlay) return;
            
            hamburger.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            overlay.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            if (mobileMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        // Close menu when clicking on a link
        document.querySelectorAll('.mobile-menu-items a').forEach(link => {
            link.addEventListener('click', function() {
                toggleMenu();
            });
        });

        // Close menu when pressing ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const mobileMenu = document.getElementById('mobileMenu');
                if (mobileMenu && mobileMenu.classList.contains('active')) {
                    toggleMenu();
                }
            }
        });

        // Carousel images
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

        // Auto-advance carousel every 5 seconds
        setInterval(() => {
            changeImage(1);
        }, 5000);

        // Add fade transition
        document.addEventListener('DOMContentLoaded', function() {
            const carouselImg = document.getElementById('carousel-image');
            if (carouselImg) {
                carouselImg.style.transition = 'opacity 0.3s ease';
            }
        });
    </script>
</body>
</html>
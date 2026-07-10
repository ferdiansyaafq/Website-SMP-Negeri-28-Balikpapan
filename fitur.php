<?php
session_start();
function pickFirstExistingImage(array $candidates, string $fallback): string {
    foreach ($candidates as $path) {
        $fsPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (is_file($fsPath)) {
            $v = (int) @filemtime($fsPath);
            return $path . ($v > 0 ? ('?v=' . $v) : '');
        }
    }
    return $fallback;
}
$logoSekolah = pickFirstExistingImage(['assets/img/logo-sekolah.png', 'assets/img/logo.png'], 'assets/img/logo-sekolah.svg');

$fiturList = [
    ['icon' => '📋', 'title' => 'Rapor Digital Terintegrasi', 'desc' => 'Sistem rapor digital modern yang mengintegrasikan seluruh data akademik dan non-akademik siswa dalam satu platform. Memudahkan guru, siswa, dan orang tua untuk mengakses informasi perkembangan belajar secara real-time.', 'benefit' => 'Akses 24/7, Data Terpusat, Ramah Lingkungan'],
    ['icon' => '⚡', 'title' => 'Monitoring Aktivitas Harian (KAIH)', 'desc' => 'Pantau 7 Kebiasaan Anak Indonesia Hebat setiap hari: bangun pagi, beribadah, berolahraga, makan sehat, gemar belajar, aktif bermasyarakat, dan tidur tepat waktu.', 'benefit' => 'Real-time Tracking, Notifikasi Otomatis, Laporan Harian'],
    ['icon' => '📊', 'title' => 'Laporan Perkembangan Siswa', 'desc' => 'Grafik dan statistik perkembangan karakter siswa berdasarkan 8 Dimensi Profil Pelajar Pancasila. Laporan dapat dicetak dan dibagikan ke orang tua secara berkala.', 'benefit' => 'Visualisasi Data, Export PDF, Tren Perkembangan'],
    ['icon' => '💡', 'title' => 'Kolaborasi Guru & Orang Tua', 'desc' => 'Platform komunikasi dua arah antara guru dan orang tua untuk berdiskusi tentang perkembangan anak. Validasi kegiatan harian dan pemberian feedback langsung.', 'benefit' => 'Chat Terintegrasi, Validasi Cepat, Feedback Langsung'],
    ['icon' => '🎯', 'title' => 'Projek Penguatan Profil Pelajar Pancasila (P5)', 'desc' => 'Manajemen kegiatan kokurikuler P5 dengan pendekatan Project-Based Learning. Dokumentasi proses dan hasil projek siswa dalam portofolio digital.', 'benefit' => 'Template Projek, Dokumentasi Digital, Penilaian Autentik'],
    ['icon' => '🏆', 'title' => 'Sistem Reward & Apresiasi', 'desc' => 'Pemberian badge dan penghargaan digital untuk siswa yang konsisten menjalankan 7 Kebiasaan Anak Indonesia Hebat. Memotivasi siswa untuk terus berkarakter baik.', 'benefit' => 'Gamifikasi, Leaderboard, Sertifikat Digital'],
    ['icon' => '📱', 'title' => 'Akses Multi-Platform', 'desc' => 'Akses sistem melalui desktop, tablet, maupun smartphone. Tampilan responsif yang optimal di semua perangkat untuk kemudahan penggunaan.', 'benefit' => 'Mobile Friendly, Offline Mode, Sinkronisasi Cloud'],
    ['icon' => '🔒', 'title' => 'Keamanan Data Terjamin', 'desc' => 'Enkripsi password dengan Bcrypt, autentikasi berbasis role (Siswa, Orang Tua, Guru, Admin), dan backup data berkala untuk melindungi privasi pengguna.', 'benefit' => 'Enkripsi Bcrypt, Role-Based Access, Backup Otomatis'],
];

$stats = [
    ['icon' => '👨‍', 'number' => '12', 'label' => 'Guru Aktif'],
    ['icon' => '👨‍🎓', 'number' => '120+', 'label' => 'Siswa Terdaftar'],
    ['icon' => '👨‍‍👧', 'number' => '115+', 'label' => 'Orang Tua'],
    ['icon' => '', 'number' => '7', 'label' => 'Kelas'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fitur KAIH - SMP Negeri 28 Balikpapan</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); min-height: 100vh; }
.header { background: white; padding: 15px 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); position: sticky; top: 0; z-index: 100; border-bottom: 4px solid #0284c7; }
.header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; gap: 15px; }
.logo-section { display: flex; align-items: center; gap: 15px; }
.logo { width: 55px; height: 55px; border-radius: 50%; }
.school-name h1 { font-size: 20px; color: #1e293b; margin-bottom: 3px; font-weight: 700; }
.school-name p { font-size: 13px; color: #64748b; }
.nav { display: flex; gap: 10px; }
.nav a { text-decoration: none; color: #64748b; font-weight: 600; padding: 10px 20px; border-radius: 8px; transition: all 0.3s; font-size: 15px; }
.nav a:hover, .nav a.active { background: #e0f2fe; color: #0284c7; }

.page-header { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: white; padding: 60px 40px; text-align: center; }
.page-header h1 { font-size: 36px; font-weight: 800; margin-bottom: 10px; }
.page-header p { font-size: 16px; opacity: 0.9; max-width: 700px; margin: 0 auto; }

.container { max-width: 1200px; margin: 40px auto; padding: 0 40px; }
.section { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); margin-bottom: 30px; }
.section-title { font-size: 28px; color: #1e293b; margin-bottom: 20px; font-weight: 800; border-left: 5px solid #0284c7; padding-left: 15px; }
.text-content { font-size: 16px; color: #475569; line-height: 1.9; margin-bottom: 15px; text-align: justify; }

/* Stats */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
.stat-card { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: white; padding: 30px; border-radius: 15px; text-align: center; }
.stat-card .icon { font-size: 40px; margin-bottom: 10px; }
.stat-card .number { font-size: 36px; font-weight: 800; margin-bottom: 5px; }
.stat-card .label { font-size: 14px; opacity: 0.9; }

/* Fitur Grid */
.fitur-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px; }
.fitur-card { background: white; border: 2px solid #e0f2fe; border-radius: 15px; padding: 30px; transition: all 0.3s; position: relative; overflow: hidden; }
.fitur-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(2,132,199,0.15); border-color: #0284c7; }
.fitur-card .icon { font-size: 48px; margin-bottom: 15px; }
.fitur-card h3 { color: #1e293b; font-size: 20px; margin-bottom: 12px; font-weight: 700; }
.fitur-card .desc { color: #64748b; font-size: 14px; line-height: 1.7; margin-bottom: 15px; }
.fitur-card .benefits { display: flex; flex-wrap: wrap; gap: 6px; }
.fitur-card .benefit-tag { background: #e0f2fe; color: #0284c7; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

/* 7 Kebiasaan */
.kebiasaan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px; margin-top: 20px; }
.kebiasaan-card { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 25px 20px; border-radius: 15px; text-align: center; border-top: 4px solid #0284c7; transition: all 0.3s; }
.kebiasaan-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(2,132,199,0.2); }
.kebiasaan-card .icon { font-size: 40px; margin-bottom: 10px; }
.kebiasaan-card .number { background: #0284c7; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: 800; font-size: 14px; }
.kebiasaan-card h4 { color: #1e293b; font-size: 15px; font-weight: 700; margin-bottom: 8px; }
.kebiasaan-card p { color: #64748b; font-size: 13px; line-height: 1.6; }

/* Role Cards */
.role-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
.role-card { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 30px; border-radius: 15px; border-left: 5px solid #0284c7; }
.role-card .icon { font-size: 40px; margin-bottom: 15px; }
.role-card h3 { color: #1e293b; font-size: 20px; margin-bottom: 12px; font-weight: 700; }
.role-card ul { list-style: none; }
.role-card ul li { padding: 8px 0; color: #475569; font-size: 14px; border-bottom: 1px solid #e0f2fe; display: flex; align-items: center; gap: 10px; }
.role-card ul li:last-child { border-bottom: none; }
.role-card ul li::before { content: '✓'; color: #0284c7; font-weight: 800; }

@media (max-width: 768px) {
    .header { padding: 12px 20px; }
    .school-name h1 { font-size: 14px; }
    .school-name p { font-size: 11px; }
    .logo { width: 45px; height: 45px; }
    .nav { display: none; }
    .container { padding: 0 20px; margin: 20px auto; }
    .page-header { padding: 40px 20px; }
    .page-header h1 { font-size: 26px; }
    .section { padding: 25px; }
    .section-title { font-size: 22px; }
    .fitur-grid { grid-template-columns: 1fr; }
    .kebiasaan-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>
<header class="header">
    <div class="header-content">
        <div class="logo-section">
            <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo" class="logo">
            <div class="school-name">
                <h1>SMP NEGERI 28 BALIKPAPAN</h1>
                <p>MERCUSUAR (Media Rekap Cerdas Unjuk Aktivitas Rapor)</p>
            </div>
        </div>
        <nav class="nav">
            <a href="index.php">Beranda</a>
            <a href="profil.php">Profil</a>
            <a href="informasi.php">Informasi</a>
            <a href="fitur.php" class="active">Fitur</a>
            <a href="panduan.php">Panduan</a>
            <a href="kontak.php">Kontak</a>
        </nav>
    </div>
</header>

<div class="page-header">
    <h1>Fitur Sistem KAIH</h1>
    <p>MERCUSUAR - Media Rekap Cerdas Unjuk Aktivitas Rapor. Sistem monitoring perkembangan siswa secara komprehensif dan real-time.</p>
</div>

<div class="container">
    <!-- Statistik -->
    <div class="stats-grid">
        <?php foreach ($stats as $stat): ?>
        <div class="stat-card">
            <div class="icon"><?php echo $stat['icon']; ?></div>
            <div class="number"><?php echo $stat['number']; ?></div>
            <div class="label"><?php echo $stat['label']; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Daftar Fitur -->
    <div class="section">
        <h2 class="section-title">🚀 Fitur Unggulan</h2>
        <p class="text-content">Sistem KAIH (Karakter Aktivitas Ibadah Harian) dilengkapi dengan berbagai fitur modern untuk mendukung monitoring perkembangan karakter siswa secara menyeluruh.</p>
        <div class="fitur-grid">
            <?php foreach ($fiturList as $fitur): ?>
            <div class="fitur-card">
                <div class="icon"><?php echo $fitur['icon']; ?></div>
                <h3><?php echo $fitur['title']; ?></h3>
                <p class="desc"><?php echo $fitur['desc']; ?></p>
                <div class="benefits">
                    <?php foreach (explode(', ', $fitur['benefit']) as $b): ?>
                    <span class="benefit-tag"><?php echo $b; ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 7 Kebiasaan -->
    <div class="section">
        <h2 class="section-title">⭐ 7 Kebiasaan Anak Indonesia Hebat</h2>
        <p class="text-content">Program KAIH mengintegrasikan 7 kebiasaan positif yang harus dijalankan siswa setiap hari untuk membentuk karakter Profil Pelajar Pancasila.</p>
        <div class="kebiasaan-grid">
            <div class="kebiasaan-card">
                <div class="number">1</div>
                <div class="icon">🌅</div>
                <h4>Bangun Pagi</h4>
                <p>Membiasakan bangun pagi tepat waktu dan merapikan tempat tidur</p>
            </div>
            <div class="kebiasaan-card">
                <div class="number">2</div>
                <div class="icon">🙏</div>
                <h4>Beribadah Tepat Waktu</h4>
                <p>Melaksanakan ibadah sesuai agama masing-masing</p>
            </div>
            <div class="kebiasaan-card">
                <div class="number">3</div>
                <div class="icon">🏃</div>
                <h4>Berolahraga Rutin</h4>
                <p>Aktivitas fisik minimal 15-30 menit setiap pagi</p>
            </div>
            <div class="kebiasaan-card">
                <div class="number">4</div>
                <div class="icon">🥗</div>
                <h4>Makan Sehat & Bergizi</h4>
                <p>Sarapan bergizi dan minum cukup air putih</p>
            </div>
            <div class="kebiasaan-card">
                <div class="number">5</div>
                <div class="icon"></div>
                <h4>Gemar Belajar</h4>
                <p>Membaca buku atau belajar minimal 15 menit per hari</p>
            </div>
            <div class="kebiasaan-card">
                <div class="number">6</div>
                <div class="icon">🤝</div>
                <h4>Aktif Bermasyarakat</h4>
                <p>Membantu orang tua dan berinteraksi sosial positif</p>
            </div>
            <div class="kebiasaan-card">
                <div class="number">7</div>
                <div class="icon"></div>
                <h4>Tidur Tepat Waktu</h4>
                <p>Tidur cukup dan tepat waktu untuk kesehatan optimal</p>
            </div>
        </div>
    </div>

    <!-- Akses Berdasarkan Role -->
    <div class="section">
        <h2 class="section-title">👥 Akses Berdasarkan Peran</h2>
        <p class="text-content">Sistem KAIH menyediakan akses yang disesuaikan untuk setiap peran pengguna dengan fitur yang relevan.</p>
        <div class="role-grid">
            <div class="role-card">
                <div class="icon">👨‍🎓</div>
                <h3>Siswa</h3>
                <ul>
                    <li>Catat kegiatan harian KAIH</li>
                    <li>Lihat progress pribadi</li>
                    <li>Lihat grafik perkembangan</li>
                    <li>Upload foto kegiatan</li>
                    <li>Lihat rapor digital</li>
                </ul>
            </div>
            <div class="role-card">
                <div class="icon">👨‍👩👧</div>
                <h3>Orang Tua</h3>
                <ul>
                    <li>Pantau aktivitas anak</li>
                    <li>Validasi kegiatan harian</li>
                    <li>Lihat laporan perkembangan</li>
                    <li>Berikan feedback ke guru</li>
                    <li>Download rapor digital</li>
                </ul>
            </div>
            <div class="role-card">
                <div class="icon">👩‍🏫</div>
                <h3>Guru</h3>
                <ul>
                    <li>Validasi laporan siswa</li>
                    <li>Kelola data kelas</li>
                    <li>Lihat grafik bulanan</li>
                    <li>Cetak laporan perkembangan</li>
                    <li>Berikan apresiasi siswa</li>
                </ul>
            </div>
            <div class="role-card">
                <div class="icon">🔐</div>
                <h3>Admin</h3>
                <ul>
                    <li>Kelola data pengguna</li>
                    <li>Import data siswa</li>
                    <li>Kelola slideshow foto</li>
                    <li>Backup database</li>
                    <li>Monitoring sistem</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Teknologi -->
    <div class="section">
        <h2 class="section-title">💻 Teknologi yang Digunakan</h2>
        <p class="text-content">Sistem KAIH dibangun dengan teknologi modern dan aman untuk memastikan performa optimal dan keamanan data.</p>
        <div class="fitur-grid">
            <div class="fitur-card">
                <div class="icon">🐘</div>
                <h3>PHP 7.4+</h3>
                <p>Backend yang handal dengan dukungan PDO untuk keamanan database.</p>
                <div class="benefits"><span class="benefit-tag">PDO</span><span class="benefit-tag">Secure</span></div>
            </div>
            <div class="fitur-card">
                <div class="icon">🗄️</div>
                <h3>MySQL / MariaDB</h3>
                <p>Database relasional untuk menyimpan data siswa, guru, dan kegiatan.</p>
                <div class="benefits"><span class="benefit-tag">InnoDB</span><span class="benefit-tag">UTF8MB4</span></div>
            </div>
            <div class="fitur-card">
                <div class="icon">🎨</div>
                <h3>HTML5 & CSS3</h3>
                <p>Tampilan modern dan responsif yang optimal di semua perangkat.</p>
                <div class="benefits"><span class="benefit-tag">Responsive</span><span class="benefit-tag">Modern</span></div>
            </div>
            <div class="fitur-card">
                <div class="icon">⚙️</div>
                <h3>JavaScript</h3>
                <p>Interaktivitas dinamis untuk carousel, grafik, dan validasi form.</p>
                <div class="benefits"><span class="benefit-tag">Dynamic</span><span class="benefit-tag">Interactive</span></div>
            </div>
            <div class="fitur-card">
                <div class="icon">🔒</div>
                <h3>Bcrypt Password</h3>
                <p>Enkripsi password dengan algoritma Bcrypt untuk keamanan maksimal.</p>
                <div class="benefits"><span class="benefit-tag">Secure</span><span class="benefit-tag">Hashed</span></div>
            </div>
            <div class="fitur-card">
                <div class="icon"></div>
                <h3>Chart.js</h3>
                <p>Visualisasi data perkembangan siswa dalam bentuk grafik interaktif.</p>
                <div class="benefits"><span class="benefit-tag">Charts</span><span class="benefit-tag">Analytics</span></div>
            </div>
        </div>
    </div>
</div>

<footer style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); margin-top: 80px; padding: 60px 0 30px; color: white;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 40px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">
            <div>
                <h4 style="font-size: 18px; margin-bottom: 10px; font-weight: 700;">Jam Layanan</h4>
                <p style="opacity: 0.9; line-height: 1.8;">Senin - Jumat (07.00 - 12.00 WITA)</p>
            </div>
            <div>
                <h4 style="font-size: 18px; margin-bottom: 10px; font-weight: 700;">Menu Cepat</h4>
                <ul style="list-style: none; line-height: 2.2;">
                    <li><a href="profil.php" style="color: white; text-decoration: none; opacity: 0.9;">Profil Sekolah</a></li>
                    <li><a href="informasi.php" style="color: white; text-decoration: none; opacity: 0.9;">Informasi</a></li>
                    <li><a href="fitur.php" style="color: white; text-decoration: none; opacity: 0.9;">Fitur KAIH</a></li>
                    <li><a href="panduan.php" style="color: white; text-decoration: none; opacity: 0.9;">Panduan</a></li>
                </ul>
            </div>
            <div>
                <h4 style="font-size: 18px; margin-bottom: 10px; font-weight: 700;">Alamat Sekolah</h4>
                <p style="opacity: 0.9; line-height: 1.8;">Jl. Persatuan<br>Kelurahan Manggar Baru<br>Kecamatan Balikpapan Timur<br>Kota Balikpapan, Kalimantan Timur<br>Kode Pos: 76115</p>
            </div>
            <div>
                <h4 style="font-size: 18px; margin-bottom: 10px; font-weight: 700;">Media Sosial Kami</h4>
                <div style="display: flex; gap: 12px;">
                    <a href="https://www.instagram.com/smpnegeri28bpp/" target="_blank" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <a href="https://www.youtube.com/@SMPNegeri28BalikpapanTimur" target="_blank" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                    </a>
                </div>
            </div>
        </div>
        <div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 30px; text-align: center; opacity: 0.8; font-size: 14px;">
            <p>&copy; <?php echo date('Y'); ?> SMP Negeri 28 Balikpapan. All rights reserved.</p>
        </div>
    </div>
</footer>
</body>
</html>
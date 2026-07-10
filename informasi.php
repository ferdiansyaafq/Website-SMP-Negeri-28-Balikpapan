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

// Data Kegiatan Ekstrakurikuler
$ekskul = [
    ['nama' => 'Pramuka (Wajib)', 'hari' => 'Jumat', 'waktu' => '14.00 - 16.00', 'lokasi' => 'Lapangan Utama', 'pembina' => 'Ibu Eka & Guru', 'icon' => '️', 'desc' => 'Membentuk karakter kemandirian, kedisiplinan, serta keterampilan kepanduan dan ketahanan mental.'],
    ['nama' => 'Pencak Silat', 'hari' => 'Selasa', 'waktu' => '15.30 - 17.00', 'lokasi' => 'Lapangan Sekolah', 'pembina' => 'Instruktur Silat & Pak Angger', 'icon' => '', 'desc' => 'Melestarikan warisan budaya bangsa, melatih konsentrasi, kekuatan fisik, serta teknik pertahanan diri.'],
    ['nama' => 'Futsal', 'hari' => 'Rabu', 'waktu' => '15.30 - 17.00', 'lokasi' => 'Lapangan Futsal TPI', 'pembina' => 'Guru PJOK', 'icon' => '', 'desc' => 'Mengembangkan bakat olahraga, melatih koordinasi motorik, sportivitas, dan strategi kerja sama tim.'],
    ['nama' => 'PMR', 'hari' => 'Kamis', 'waktu' => '15.30 - 17.00', 'lokasi' => 'Ruang UKS / Kelas', 'pembina' => 'Ibu Rini & Pembina PMI', 'icon' => '', 'desc' => 'Melatih keterampilan pertolongan pertama, kesiapsiagaan bencana, dan jiwa kemanusiaan.'],
    ['nama' => 'Memanah', 'hari' => 'Senin', 'waktu' => '15.30 - 17.00', 'lokasi' => 'Lapangan Sekolah', 'pembina' => 'Cach Broto / Kusuma', 'icon' => '🏹', 'desc' => 'Melatih fokus, konsentrasi, stabilitas emosi, ketepatan, dan kekuatan otot tubuh.'],
    ['nama' => 'Kader Lingkungan', 'hari' => 'Sabtu', 'waktu' => '08.00 - 10.00', 'lokasi' => 'Taman Sekolah', 'pembina' => 'Pak Firman & Tim Adiwiyata', 'icon' => '🌱', 'desc' => 'Mewujudkan sekolah Adiwiyata melalui pengelolaan sampah, penghijauan, dan kampanye gaya hidup ramah lingkungan.'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Informasi - SMP Negeri 28 Balikpapan</title>
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
.page-header p { font-size: 16px; opacity: 0.9; }

.container { max-width: 1200px; margin: 40px auto; padding: 0 40px; }
.section { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); margin-bottom: 30px; }
.section-title { font-size: 28px; color: #1e293b; margin-bottom: 20px; font-weight: 800; border-left: 5px solid #0284c7; padding-left: 15px; }
.section-subtitle { font-size: 20px; color: #0284c7; margin: 25px 0 15px; font-weight: 700; }
.text-content { font-size: 16px; color: #475569; line-height: 1.9; margin-bottom: 15px; text-align: justify; }

/* Info Cards */
.info-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
.info-card { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 25px; border-radius: 15px; border-left: 4px solid #0284c7; }
.info-card .icon { font-size: 32px; margin-bottom: 10px; }
.info-card h4 { color: #0284c7; font-size: 14px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
.info-card p { color: #1e293b; font-size: 15px; font-weight: 600; line-height: 1.6; }

/* Ekstrakurikuler Grid */
.ekskul-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px; }
.ekskul-card { background: white; border: 2px solid #e0f2fe; border-radius: 15px; padding: 25px; transition: all 0.3s; position: relative; overflow: hidden; }
.ekskul-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(2,132,199,0.15); border-color: #0284c7; }
.ekskul-card .icon { font-size: 40px; margin-bottom: 15px; }
.ekskul-card h3 { color: #1e293b; font-size: 18px; margin-bottom: 10px; font-weight: 700; }
.ekskul-card .desc { color: #64748b; font-size: 14px; line-height: 1.7; margin-bottom: 15px; }
.ekskul-card .meta { display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: #475569; }
.ekskul-card .meta span { display: flex; align-items: center; gap: 8px; }
.ekskul-card .meta .label { color: #0284c7; font-weight: 600; min-width: 70px; }

/* Jadwal Table */
.jadwal-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.jadwal-table th { background: #0284c7; color: white; padding: 15px; text-align: left; font-size: 14px; }
.jadwal-table td { padding: 14px 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #334155; }
.jadwal-table tr:hover td { background: #f0f9ff; }
.jadwal-table tr:last-child td { border-bottom: none; }

/* Pengumuman */
.pengumuman-list { display: flex; flex-direction: column; gap: 15px; margin-top: 20px; }
.pengumuman-item { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 20px 25px; border-radius: 12px; border-left: 4px solid #0284c7; display: flex; gap: 15px; align-items: flex-start; }
.pengumuman-item .date { background: #0284c7; color: white; padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.pengumuman-item .content h4 { color: #1e293b; font-size: 16px; margin-bottom: 5px; }
.pengumuman-item .content p { color: #64748b; font-size: 14px; line-height: 1.6; }

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
    .ekskul-grid { grid-template-columns: 1fr; }
    .jadwal-table { font-size: 12px; }
    .jadwal-table th, .jadwal-table td { padding: 10px 8px; }
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
            <a href="informasi.php" class="active">Informasi</a>
            <a href="fitur.php">Fitur</a>
            <a href="panduan.php">Panduan</a>
            <a href="kontak.php">Kontak</a>
        </nav>
    </div>
</header>

<div class="page-header">
    <h1>Informasi Sekolah</h1>
    <p>Berita, pengumuman, dan kegiatan terkini</p>
</div>

<div class="container">
    <!-- Pengumuman -->
    <div class="section">
        <h2 class="section-title">📢 Pengumuman Terbaru</h2>
        <div class="pengumuman-list">
            <div class="pengumuman-item">
                <div class="date">10 JUL 2026</div>
                <div class="content">
                    <h4>Penerimaan Peserta Didik Baru (PPDB) 2026/2027</h4>
                    <p>Pendaftaran PPDB Tahun Ajaran 2026/2027 telah dibuka. Silakan cek informasi lengkap di website resmi Dinas Pendidikan Kota Balikpapan.</p>
                </div>
            </div>
            <div class="pengumuman-item">
                <div class="date">05 JUL 2026</div>
                <div class="content">
                    <h4>Implementasi Kurikulum Merdeka dengan Deep Learning</h4>
                    <p>SMP Negeri 28 Balikpapan resmi menerapkan pendekatan Pembelajaran Mendalam (Deep Learning) untuk Tahun Ajaran 2025/2026.</p>
                </div>
            </div>
            <div class="pengumuman-item">
                <div class="date">01 JUL 2026</div>
                <div class="content">
                    <h4>Program 7 Kebiasaan Anak Indonesia Hebat (KAIH)</h4>
                    <p>Seluruh siswa diwajibkan mengikuti program KAIH untuk membentuk karakter dan kebiasaan positif sehari-hari.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Kegiatan Ekstrakurikuler -->
    <div class="section">
        <h2 class="section-title"> Kegiatan Ekstrakurikuler</h2>
        <p class="text-content">Kegiatan ekstrakurikuler di SMP Negeri 28 Balikpapan diselenggarakan sebagai bagian dari pembentukan karakter dan pengembangan potensi peserta didik secara menyeluruh. Berdasarkan minat dan bakat, difasilitasi oleh guru atau pembina yang kompeten.</p>
        <div class="ekskul-grid">
            <?php foreach ($ekskul as $item): ?>
            <div class="ekskul-card">
                <div class="icon"><?php echo $item['icon']; ?></div>
                <h3><?php echo $item['nama']; ?></h3>
                <p class="desc"><?php echo $item['desc']; ?></p>
                <div class="meta">
                    <span><span class="label">📅 Hari:</span> <?php echo $item['hari']; ?></span>
                    <span><span class="label"> Waktu:</span> <?php echo $item['waktu']; ?></span>
                    <span><span class="label">📍 Lokasi:</span> <?php echo $item['lokasi']; ?></span>
                    <span><span class="label">👨‍🏫 Pembina:</span> <?php echo $item['pembina']; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Jadwal Kegiatan -->
    <div class="section">
        <h2 class="section-title">📅 Jadwal Mingguan Ekstrakurikuler</h2>
        <div style="overflow-x: auto;">
            <table class="jadwal-table">
                <thead>
                    <tr>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Jenis Ekskul</th>
                        <th>Guru Pendamping</th>
                        <th>Lokasi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Senin</td><td>15.30 - 17.00</td><td>Memanah</td><td>Cach Broto / Kusuma</td><td>Lapangan Sekolah</td></tr>
                    <tr><td>Selasa</td><td>15.30 - 17.00</td><td>Pencak Silat</td><td>Instruktur Silat & Pak Angger</td><td>Lapangan Sekolah</td></tr>
                    <tr><td>Rabu</td><td>15.30 - 17.00</td><td>Futsal</td><td>Guru PJOK</td><td>Lapangan Futsal TPI</td></tr>
                    <tr><td>Kamis</td><td>15.30 - 17.00</td><td>PMR</td><td>Ibu Rini & Pembina PMI</td><td>Ruang UKS</td></tr>
                    <tr><td>Jumat</td><td>14.00 - 16.00</td><td>Pramuka (Wajib)</td><td>Ibu Eka & Guru</td><td>Lapangan Utama</td></tr>
                    <tr><td>Sabtu</td><td>08.00 - 10.00</td><td>Kader Lingkungan</td><td>Pak Firman & Tim Adiwiyata</td><td>Taman Sekolah</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Jadwal Harian -->
    <div class="section">
        <h2 class="section-title">⏰ Jadwal Harian Sekolah</h2>
        <div class="info-cards">
            <div class="info-card">
                <div class="icon">🌅</div>
                <h4>07.00 - 07.30</h4>
                <p>Kedatangan, literasi, pembiasaan, Senam Anak Indonesia Hebat, dan Lagu Indonesia Raya</p>
            </div>
            <div class="info-card">
                <div class="icon">📚</div>
                <h4>07.30 - 10.00</h4>
                <p>Jam Pelajaran Inti (Blok 1) dengan pendekatan pembelajaran mendalam</p>
            </div>
            <div class="info-card">
                <div class="icon">☕</div>
                <h4>10.00 - 10.15</h4>
                <p>Istirahat Pertama</p>
            </div>
            <div class="info-card">
                <div class="icon">📖</div>
                <h4>10.15 - 12.00</h4>
                <p>Jam Pelajaran Inti (Blok 2)</p>
            </div>
            <div class="info-card">
                <div class="icon">🍽️</div>
                <h4>12.00 - 13.00</h4>
                <p>Istirahat & Sholat Dhuhur</p>
            </div>
            <div class="info-card">
                <div class="icon">🎨</div>
                <h4>13.00 - 14.30</h4>
                <p>Kegiatan Kokurikuler / P5 (2-3 kali/minggu)</p>
            </div>
        </div>
    </div>

    <!-- Kemitraan -->
    <div class="section">
        <h2 class="section-title">🤝 Kemitraan & Kerja Sama</h2>
        <p class="text-content">SMP Negeri 28 Balikpapan membangun jejaring kemitraan strategis dengan berbagai pihak untuk mendukung implementasi Kurikulum Merdeka dan Pembelajaran Mendalam.</p>
        <div class="info-cards">
            <div class="info-card">
                <div class="icon">🏥</div>
                <h4>Puskesmas Terdekat</h4>
                <p>Edukasi Kesehatan & Imunisasi untuk rekam medis kesehatan siswa</p>
            </div>
            <div class="info-card">
                <div class="icon">🕌</div>
                <h4>Tokoh Agama / Masjid</h4>
                <p>Pendampingan Rohani & Ibadah Jumat untuk program religiositas</p>
            </div>
            <div class="info-card">
                <div class="icon">🏢</div>
                <h4>Perusahaan (CSR)</h4>
                <p>Hibah Perangkat & Infrastruktur untuk penambahan sarana Lab/Multimedia</p>
            </div>
            <div class="info-card">
                <div class="icon">‍🏫</div>
                <h4>MGMP Kota</h4>
                <p>Knowledge Sharing antar Guru untuk peningkatan kompetensi pendidik</p>
            </div>
            <div class="info-card">
                <div class="icon">🏛️</div>
                <h4>Kelurahan / Kecamatan</h4>
                <p>Keamanan dan Penghijauan Sekolah untuk lingkungan yang aman dan asri</p>
            </div>
            <div class="info-card">
                <div class="icon">👨‍👩👧</div>
                <h4>Komite Sekolah & Orang Tua</h4>
                <p>Kelas Inspirasi & Pembangunan Swadaya untuk sense of belonging masyarakat</p>
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
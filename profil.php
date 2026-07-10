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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil Sekolah - SMP Negeri 28 Balikpapan</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); min-height: 100vh; }

/* Header */
.header { background: white; padding: 15px 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); position: sticky; top: 0; z-index: 100; border-bottom: 4px solid #0284c7; }
.header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; gap: 15px; }
.logo-section { display: flex; align-items: center; gap: 15px; }
.logo { width: 55px; height: 55px; border-radius: 50%; }
.school-name h1 { font-size: 20px; color: #1e293b; margin-bottom: 3px; font-weight: 700; }
.school-name p { font-size: 13px; color: #64748b; }
.nav { display: flex; gap: 10px; }
.nav a { text-decoration: none; color: #64748b; font-weight: 600; padding: 10px 20px; border-radius: 8px; transition: all 0.3s; font-size: 15px; }
.nav a:hover, .nav a.active { background: #e0f2fe; color: #0284c7; }

/* Page Header */
.page-header { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: white; padding: 60px 40px; text-align: center; }
.page-header h1 { font-size: 36px; font-weight: 800; margin-bottom: 10px; }
.page-header p { font-size: 16px; opacity: 0.9; }

/* Container */
.container { max-width: 1200px; margin: 40px auto; padding: 0 40px; }

/* Section */
.section { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); margin-bottom: 30px; }
.section-title { font-size: 28px; color: #1e293b; margin-bottom: 20px; font-weight: 800; border-left: 5px solid #0284c7; padding-left: 15px; }
.section-subtitle { font-size: 20px; color: #0284c7; margin: 25px 0 15px; font-weight: 700; }

/* Info Grid */
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
.info-card { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 25px; border-radius: 15px; border-left: 4px solid #0284c7; }
.info-card h4 { color: #0284c7; font-size: 14px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
.info-card p { color: #1e293b; font-size: 16px; font-weight: 600; }

/* Text Content */
.text-content { font-size: 16px; color: #475569; line-height: 1.9; margin-bottom: 15px; text-align: justify; }

/* Visi Misi */
.visi-box { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: white; padding: 30px; border-radius: 15px; margin: 20px 0; }
.visi-box h3 { font-size: 22px; margin-bottom: 15px; }
.visi-box p { font-size: 16px; line-height: 1.8; font-style: italic; }

.misi-list { list-style: none; counter-reset: misi; }
.misi-list li { counter-increment: misi; background: #f8fafc; padding: 18px 20px 18px 70px; border-radius: 12px; margin-bottom: 12px; position: relative; font-size: 15px; line-height: 1.7; color: #334155; border-left: 4px solid #0284c7; }
.misi-list li::before { content: counter(misi); position: absolute; left: 15px; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; background: #0284c7; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; }

/* Teacher Grid */
.teacher-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 20px; }
.teacher-card { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 20px; border-radius: 12px; border-top: 4px solid #0284c7; transition: all 0.3s; }
.teacher-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(2,132,199,0.2); }
.teacher-card .name { font-weight: 700; color: #1e293b; font-size: 15px; margin-bottom: 5px; }
.teacher-card .position { color: #0284c7; font-size: 13px; font-weight: 600; }
.teacher-card .qualification { color: #64748b; font-size: 12px; margin-top: 5px; }

/* Responsive */
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
            <a href="profil.php" class="active">Profil</a>
            <a href="informasi.php">Informasi</a>
            <a href="fitur.php">Fitur</a>
            <a href="panduan.php">Panduan</a>
            <a href="kontak.php">Kontak</a>
        </nav>
    </div>
</header>

<div class="page-header">
    <h1>Profil Sekolah</h1>
    <p>Mengenal lebih dekat SMP Negeri 28 Balikpapan</p>
</div>

<div class="container">
    <!-- Identitas Sekolah -->
    <div class="section">
        <h2 class="section-title">Identitas Sekolah</h2>
        <div class="info-grid">
            <div class="info-card">
                <h4>Nama Sekolah</h4>
                <p>SMP Negeri 28 Balikpapan</p>
            </div>
            <div class="info-card">
                <h4>NPSN</h4>
                <p>70054141</p>
            </div>
            <div class="info-card">
                <h4>Status</h4>
                <p>Negeri</p>
            </div>
            <div class="info-card">
                <h4>Status Kepemilikan</h4>
                <p>Pemerintah Kota Balikpapan</p>
            </div>
            <div class="info-card">
                <h4>SK Pendirian</h4>
                <p>188.45-163/2025</p>
            </div>
            <div class="info-card">
                <h4>Tanggal SK Pendirian</h4>
                <p>28 Februari 2025</p>
            </div>
            <div class="info-card">
                <h4>SK Izin Operasional</h4>
                <p>420/1321/Disdikbud</p>
            </div>
            <div class="info-card">
                <h4>Kepala Sekolah</h4>
                <p>Aris Broto, S.Pd</p>
            </div>
        </div>
    </div>

    <!-- Tentang Sekolah -->
    <div class="section">
        <h2 class="section-title">Tentang Sekolah</h2>
        <p class="text-content">
            SMP Negeri 28 Balikpapan merupakan salah satu lembaga pendidikan formal jenjang menengah pertama yang terletak di wilayah Balikpapan Timur, tepatnya di lingkungan Kelurahan Manggar Baru. Didirikan dengan semangat melayani kebutuhan pendidikan masyarakat di daerah pesisir, sekolah ini hadir sebagai jawaban atas pentingnya akses pendidikan bermutu di wilayah dengan karakteristik sosial ekonomi yang beragam.
        </p>
        <p class="text-content">
            Mayoritas peserta didik di sekolah ini berasal dari keluarga dengan latar belakang pekerjaan sebagai petani, pedagang tradisional, nelayan, serta sebagian sebagai aparatur sipil negara (ASN). Memasuki transformasi pendidikan di tahun 2025, sekolah ini berkomitmen untuk menerapkan pendekatan <strong>Pembelajaran Mendalam (Deep Learning)</strong> sebagai dasar dalam penyelenggaraan proses belajar mengajar.
        </p>
        <p class="text-content">
            Pendekatan ini menjadikan peserta didik sebagai subjek aktif, kreatif, reflektif, dan penuh daya cipta dalam lingkungan belajar yang berkesadaran (mindful), bermakna (meaningful), dan menggembirakan (joyful). Pengembangan pembelajaran berfokus pada pembentukan karakter yang utuh melalui integrasi Profil Pelajar Pancasila dan 7 Kebiasaan Anak Indonesia Hebat (KAIH).
        </p>
    </div>

    <!-- Visi Misi -->
    <div class="section">
        <h2 class="section-title">Visi & Misi</h2>
        <div class="visi-box">
            <h3>🎯 Visi Sekolah</h3>
            <p>"Terwujudnya Peserta Didik yang Berakhlak Mulia, Unggul, Berdaya Saing Global Berbasis Kearifan Lokal dan Berbudaya Lingkungan"</p>
        </div>

        <h3 class="section-subtitle">Misi Sekolah</h3>
        <ol class="misi-list">
            <li>Menyelenggarakan pembelajaran mendalam (deep learning) yang menumbuhkan kemampuan berpikir kritis, reflektif, dan kontekstual dalam setiap mata pelajaran.</li>
            <li>Menumbuhkan lingkungan sekolah yang religius melalui pembiasaan ibadah rutin dan internalisasi nilai-nilai kejujuran, disiplin, serta tata krama dalam interaksi sosial.</li>
            <li>Mengembangkan karakter unggul peserta didik melalui integrasi nilai-nilai Profil Pelajar Pancasila dan 7 Kebiasaan Anak Indonesia Hebat (KAIH).</li>
            <li>Mengintegrasikan teknologi informasi dalam proses pembelajaran dan meningkatkan kemampuan literasi serta komunikasi lintas budaya.</li>
            <li>Menciptakan lingkungan belajar yang berkesadaran (mindful) dengan membangun kesadaran peserta didik terhadap proses belajarnya, emosinya, dan tujuan belajarnya.</li>
            <li>Mengembangkan kurikulum yang mengangkat potensi daerah dan kearifan lokal Balikpapan sebagai identitas diri.</li>
            <li>Merancang pembelajaran bermakna (meaningful) yang relevan dengan kehidupan peserta didik, berbasis pengalaman, serta mendorong eksplorasi dan pemecahan masalah.</li>
            <li>Membangun suasana pembelajaran yang menyenangkan (joyful learning) melalui pendekatan yang humanis, interaktif, dan sesuai dengan minat dan bakat peserta didik.</li>
            <li>Meningkatkan kapasitas guru sebagai fasilitator pembelajaran melalui pelatihan berkelanjutan, kolaborasi profesional, dan budaya refleksi.</li>
            <li>Menciptakan ekosistem sekolah yang bersih, sehat, dan asri melalui program Gerakan ASRI (Aman, Sehat, Resik, dan Indah).</li>
        </ol>
    </div>

    <!-- Tujuan -->
    <div class="section">
        <h2 class="section-title">Tujuan Pendidikan</h2>
        <p class="text-content">Tujuan pendidikan yang hendak dicapai oleh SMP Negeri 28 Balikpapan Tahun Pelajaran 2025/2026 adalah:</p>
        <ol class="misi-list">
            <li>Menghasilkan lulusan yang memiliki karakter kuat sesuai nilai-nilai Pancasila: religius, mandiri, gotong royong, bernalar kritis, kreatif, dan berkebhinekaan global.</li>
            <li>Mengembangkan kemampuan berpikir kritis, reflektif, dan kontekstual dalam memecahkan masalah kehidupan nyata.</li>
            <li>Membiasakan peserta didik dalam 7 Kebiasaan Anak Indonesia Hebat (KAIH) untuk membentuk pola hidup sehat, disiplin, dan tangguh.</li>
            <li>Menciptakan proses pembelajaran yang menginspirasi, bermakna, dan menyenangkan.</li>
            <li>Menumbuhkan kesadaran peserta didik terhadap tanggung jawab pribadi dan sosial.</li>
            <li>Terwujudnya ekosistem sekolah yang bersih, sehat, dan asri melalui program Gerakan ASRI.</li>
            <li>Mewujudkan ekosistem sekolah yang kolaboratif dan adaptif untuk pelaksanaan Kurikulum Merdeka.</li>
        </ol>
    </div>

    <!-- Pendidik & Tenaga Kependidikan -->
    <div class="section">
        <h2 class="section-title">Pendidik & Tenaga Kependidikan</h2>
        <p class="text-content">SMP Negeri 28 Balikpapan memiliki fondasi SDM yang didominasi oleh tenaga pendidik muda dengan kualifikasi akademik S1 yang telah memenuhi standar minimal.</p>
        <div class="teacher-grid">
            <div class="teacher-card">
                <div class="name">Aris Broto, S.Pd</div>
                <div class="position">Plt. Kepala Sekolah</div>
                <div class="qualification">S1</div>
            </div>
            <div class="teacher-card">
                <div class="name">Angger Dalu Gede Wijaya</div>
                <div class="position">Wakil Kepala Sekolah / PJOK</div>
                <div class="qualification">S1</div>
            </div>
            <div class="teacher-card">
                <div class="name">Imil Mahmudah</div>
                <div class="position">Operator / PAI</div>
                <div class="qualification">S1</div>
            </div>
            <div class="teacher-card">
                <div class="name">Firman Ahmad</div>
                <div class="position">Pengurus Barang / IPA / Adiwiyata</div>
                <div class="qualification">S1</div>
            </div>
            <div class="teacher-card">
                <div class="name">Cindy Milenia</div>
                <div class="position">PKN / PPAS</div>
                <div class="qualification">S1</div>
            </div>
            <div class="teacher-card">
                <div class="name">Rini Yulianti</div>
                <div class="position">BK / UKS</div>
                <div class="qualification">S1</div>
            </div>
            <div class="teacher-card">
                <div class="name">Eka Rahmayanti</div>
                <div class="position">Pramuka / IPS</div>
                <div class="qualification">S1</div>
            </div>
            <div class="teacher-card">
                <div class="name">Jenni Roslianthi</div>
                <div class="position">Matematika</div>
                <div class="qualification">S1</div>
            </div>
            <div class="teacher-card">
                <div class="name">Nurhabibah</div>
                <div class="position">TIK</div>
                <div class="qualification">S1</div>
            </div>
            <div class="teacher-card">
                <div class="name">Muh. Alief Ramadhany</div>
                <div class="position">Waka Kurikulum / B.Indonesia</div>
                <div class="qualification">S1</div>
            </div>
            <div class="teacher-card">
                <div class="name">Angelyn May Rotua Lumban Batu</div>
                <div class="position">Waka Kesiswaan / B.Inggris</div>
                <div class="qualification">S1</div>
            </div>
            <div class="teacher-card">
                <div class="name">Ana</div>
                <div class="position">PAK / BK</div>
                <div class="qualification">S1</div>
            </div>
        </div>
    </div>

    <!-- Sarana Prasarana -->
    <div class="section">
        <h2 class="section-title">Sarana & Prasarana</h2>
        <p class="text-content">Sebagai satuan pendidikan yang baru beroperasi, SMP Negeri 28 Balikpapan sedang berada dalam fase krusial penataan sarana prasarana. Saat ini, aktivitas pembelajaran berpusat pada sembilan ruang kelas yang didesain secara adaptif untuk mendukung model pembelajaran kolaboratif dan berdiferensiasi.</p>
        <div class="info-grid">
            <div class="info-card">
                <h4>Ruang Kelas</h4>
                <p>9 Ruang Kelas Adaptif</p>
            </div>
            <div class="info-card">
                <h4>Smart TV</h4>
                <p>4 Unit untuk Multimedia</p>
            </div>
            <div class="info-card">
                <h4>Lapangan</h4>
                <p>Multifungsi (Olahraga & Upacara)</p>
            </div>
            <div class="info-card">
                <h4>Area Terbuka</h4>
                <p>Laboratorium Alam & Taman</p>
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
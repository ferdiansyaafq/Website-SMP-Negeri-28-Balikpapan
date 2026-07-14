<?php
$page_title = "Profil Sekolah - SMP Negeri 28 Balikpapan";
include 'header.php';

function getTeacherPhoto($name) {
    $slug = strtolower(str_replace([' ', '.', ',', "'", "("], '-', $name));
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    $extensions = ['.jpg', '.jpeg', '.png', '.webp', '.svg'];
    
    foreach ($extensions as $ext) {
        $photoPath = 'assets/img/guru/' . $slug . $ext;
        $fsPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $photoPath);
        if (is_file($fsPath)) {
            return $photoPath;
        }
    }
    
    return null;
}

$teachers = [
    ['name' => 'Aris Broto, S.Pd', 'position' => 'Kepala Sekolah', 'initials' => 'AB'],
    ['name' => 'Angger Dalu Gede Wijaya', 'position' => 'Wakil Kepala Sekolah', 'initials' => 'AD'],
    ['name' => 'Muh. Alief Ramadhany', 'position' => 'Waka Kurikulum', 'initials' => 'MR'],
    ['name' => 'Angelyn May Rotua Lumban Batu', 'position' => 'Waka Kesiswaan', 'initials' => 'AL'],
    ['name' => 'Imil Mahmudah', 'position' => 'Guru PAI', 'initials' => 'IM'],
    ['name' => 'Firman Ahmad', 'position' => 'Guru IPA', 'initials' => 'FA'],
    ['name' => 'Cindy Milenia', 'position' => 'Guru PKN', 'initials' => 'CM'],
    ['name' => 'Rini Yulianti', 'position' => 'Guru BK', 'initials' => 'RY'],
    ['name' => 'Eka Rahmayanti', 'position' => 'Guru IPS', 'initials' => 'ER'],
    ['name' => 'Jenni Roslianthi', 'position' => 'Guru Matematika', 'initials' => 'JR'],
    ['name' => 'Nurhabibah', 'position' => 'Guru TIK', 'initials' => 'NH'],
    ['name' => 'Ana', 'position' => 'Guru PAK', 'initials' => 'AN'],
];
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

.section-subtitle {
    font-size: 20px;
    color: #0284c7;
    margin: 25px 0 15px;
    font-weight: 700;
}

.text-content {
    font-size: 16px;
    color: #475569;
    line-height: 1.9;
    margin-bottom: 15px;
    text-align: justify;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.info-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    padding: 25px;
    border-radius: 15px;
    border-left: 4px solid #0284c7;
}

.info-card h4 {
    color: #0284c7;
    font-size: 14px;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.info-card p {
    color: #1e293b;
    font-size: 16px;
    font-weight: 600;
}

.visi-box {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin: 20px 0;
}

.visi-box h3 {
    font-size: 22px;
    margin-bottom: 15px;
}

.visi-box p {
    font-size: 16px;
    line-height: 1.8;
    font-style: italic;
}

.misi-list {
    list-style: none;
    counter-reset: misi;
}

.misi-list li {
    counter-increment: misi;
    background: #f8fafc;
    padding: 18px 20px 18px 70px;
    border-radius: 12px;
    margin-bottom: 12px;
    position: relative;
    font-size: 15px;
    line-height: 1.7;
    color: #334155;
    border-left: 4px solid #0284c7;
}

.misi-list li::before {
    content: counter(misi);
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    width: 40px;
    height: 40px;
    background: #0284c7;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 16px;
}

.teacher-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.teacher-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    padding: 20px 15px;
    border-radius: 12px;
    border-top: 4px solid #0284c7;
    transition: all 0.3s;
    text-align: center;
}

.teacher-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(2,132,199,0.2);
}

.teacher-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #0284c7;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
    font-weight: 700;
    overflow: hidden;
    position: relative;
    flex-shrink: 0;
}

.teacher-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    position: absolute;
    top: 0;
    left: 0;
}

.teacher-avatar span {
    position: relative;
    z-index: 1;
}

.teacher-card .name {
    font-weight: 700;
    color: #1e293b;
    font-size: 15px;
    margin-bottom: 3px;
}

.teacher-card .position {
    color: #0284c7;
    font-size: 12px;
    font-weight: 600;
}

.teacher-card .subject {
    color: #64748b;
    font-size: 11px;
    margin-top: 3px;
}

.teacher-card .qualification {
    color: #94a3b8;
    font-size: 11px;
    margin-top: 3px;
}

.fasilitas-gallery {
    display: flex;
    flex-direction: column;
    gap: 25px;
    margin-top: 20px;
}

.fasilitas-item {
    display: grid;
    grid-template-columns: 35% 65%;
    gap: 0;
    background: #f8fafc;
    border-radius: 15px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.fasilitas-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(2,132,199,0.12);
    border-color: #0284c7;
}

.fasilitas-thumb {
    position: relative;
    overflow: hidden;
    min-height: 200px;
    background: #e2e8f0;
}

.fasilitas-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.fasilitas-item:hover .fasilitas-thumb img {
    transform: scale(1.05);
}

.fasilitas-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 30px 20px 15px;
    background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);
}

.photo-count {
    color: white;
    font-size: 13px;
    font-weight: 600;
    background: rgba(2,132,199,0.85);
    padding: 5px 14px;
    border-radius: 20px;
    display: inline-block;
}

.fasilitas-info {
    padding: 25px 30px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.fasilitas-info h4 {
    font-size: 20px;
    color: #1e293b;
    font-weight: 700;
    margin-bottom: 10px;
}

.fasilitas-info p {
    font-size: 14px;
    color: #64748b;
    line-height: 1.7;
    margin-bottom: 15px;
}

.btn-foto {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #0284c7;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-foto:hover {
    gap: 12px;
    color: #0369a1;
}

@media (max-width: 768px) {
    .fasilitas-item {
        grid-template-columns: 1fr;
    }
    
    .fasilitas-thumb {
        min-height: 180px;
    }
    
    .fasilitas-info {
        padding: 20px;
    }
    
    .fasilitas-info h4 {
        font-size: 18px;
    }
}

@media (max-width: 480px) {
    .fasilitas-thumb {
        min-height: 150px;
    }
    
    .fasilitas-info h4 {
        font-size: 16px;
    }
    
    .fasilitas-info p {
        font-size: 13px;
    }
}

.ekskul-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ekskul-card {
    background: white;
    border: 2px solid #e0f2fe;
    border-radius: 15px;
    padding: 20px;
    transition: all 0.3s;
    text-align: center;
}

.ekskul-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(2,132,199,0.15);
    border-color: #0284c7;
}

.ekskul-card .icon {
    font-size: 36px;
    margin-bottom: 12px;
}

.ekskul-card h3 {
    color: #1e293b;
    font-size: 17px;
    margin-bottom: 8px;
    font-weight: 700;
}

.ekskul-card .desc {
    color: #64748b;
    font-size: 13px;
    line-height: 1.6;
    margin-bottom: 12px;
    text-align: justify;
}

.ekskul-card .meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 12px;
    color: #475569;
}

.ekskul-card .meta span {
    display: flex;
    align-items: center;
    gap: 8px;
}

.ekskul-card .meta .label {
    color: #0284c7;
    font-weight: 600;
    min-width: 65px;
}

.tujuan-list {
    list-style: none;
    counter-reset: tujuan;
}

.tujuan-list li {
    counter-increment: tujuan;
    background: #f8fafc;
    padding: 14px 20px 14px 55px;
    border-radius: 10px;
    margin-bottom: 10px;
    position: relative;
    font-size: 14px;
    line-height: 1.7;
    color: #334155;
    border-left: 3px solid #0284c7;
}

.tujuan-list li::before {
    content: counter(tujuan);
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 30px;
    height: 30px;
    background: #0284c7;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
}

#identitas-sekolah .info-wrapper {
    display: grid;
    grid-template-columns: 35% 65%;
    gap: 30px;
    margin-top: 15px;
    align-items: stretch;
}

#identitas-sekolah .info-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #f0f9ff 0%, #dbeafe 100%);
    border-radius: 20px;
    padding: 30px;
    position: relative;
    overflow: hidden;
    min-height: 300px;
    box-shadow: inset 0 2px 10px rgba(2,132,199,0.08);
}

#identitas-sekolah .info-logo::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(2,132,199,0.05) 0%, transparent 70%);
    border-radius: 50%;
}

#identitas-sekolah .info-logo img {
    width: 100%;
    max-width: 180px;
    height: auto;
    aspect-ratio: 1/1;
    object-fit: contain;
    border-radius: 50%;
    border: 5px solid rgba(2,132,199,0.3);
    padding: 12px;
    background: white;
    box-shadow: 0 8px 30px rgba(2,132,199,0.15);
    position: relative;
    z-index: 1;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

#identitas-sekolah .info-logo img:hover {
    transform: scale(1.03);
    box-shadow: 0 12px 40px rgba(2,132,199,0.25);
}

#identitas-sekolah .info-data {
    background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 20px;
    padding: 30px 35px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    position: relative;
    overflow: hidden;
}

#identitas-sekolah .info-data::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, rgba(2,132,199,0.04) 0%, transparent 70%);
    border-radius: 50%;
}

#identitas-sekolah .info-data .school-name {
    color: #0284c7;
    font-size: 26px;
    margin-bottom: 20px;
    font-weight: 800;
    letter-spacing: -0.5px;
    position: relative;
    z-index: 1;
}

#identitas-sekolah .info-data .school-name::after {
    content: '';
    display: block;
    width: 60px;
    height: 4px;
    background: linear-gradient(90deg, #0284c7, #60a5fa);
    border-radius: 2px;
    margin-top: 8px;
}

#identitas-sekolah .info-data .data-grid {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 6px 20px;
    font-size: 15px;
    line-height: 1.9;
    position: relative;
    z-index: 1;
}

#identitas-sekolah .info-data .data-grid .label {
    color: #64748b;
    font-weight: 600;
    font-size: 14px;
    letter-spacing: 0.3px;
    padding: 4px 0;
    border-bottom: 1px dashed rgba(0,0,0,0.04);
}

#identitas-sekolah .info-data .data-grid .value {
    color: #1e293b;
    font-weight: 500;
    font-size: 15px;
    padding: 4px 0;
    border-bottom: 1px dashed rgba(0,0,0,0.04);
}

#identitas-sekolah .info-data .data-grid .value-bold {
    color: #0284c7;
    font-weight: 700;
    font-size: 15px;
    padding: 4px 0;
    border-bottom: 1px dashed rgba(0,0,0,0.04);
}

#identitas-sekolah .info-data .badge {
    display: inline-block;
    background: linear-gradient(135deg, #0284c7, #0369a1);
    color: white;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-top: 15px;
    align-self: flex-start;
    position: relative;
    z-index: 1;
}

@media (max-width: 968px) {
    #identitas-sekolah .info-wrapper {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    #identitas-sekolah .info-logo {
        min-height: 220px;
        padding: 25px;
    }
    
    #identitas-sekolah .info-logo img {
        max-width: 150px;
    }
    
    #identitas-sekolah .info-data {
        padding: 25px;
    }
    
    #identitas-sekolah .info-data .school-name {
        font-size: 22px;
    }
    
    #identitas-sekolah .info-data .data-grid {
        grid-template-columns: 130px 1fr;
        font-size: 14px;
        gap: 4px 15px;
    }
}

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
    .ekskul-grid {
    grid-template-columns: 1fr;
    }
    .teacher-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .teacher-avatar {
        width: 60px;
        height: 60px;
        font-size: 24px;
    }
    
    #identitas-sekolah .info-logo {
        min-height: 180px;
        padding: 20px;
    }
    
    #identitas-sekolah .info-logo img {
        max-width: 120px;
        padding: 8px;
        border-width: 3px;
    }
    
    #identitas-sekolah .info-data {
        padding: 20px;
    }
    
    #identitas-sekolah .info-data .school-name {
        font-size: 20px;
        margin-bottom: 15px;
    }
    
    #identitas-sekolah .info-data .school-name::after {
        width: 40px;
        height: 3px;
    }
    
    #identitas-sekolah .info-data .data-grid {
        grid-template-columns: 110px 1fr;
        font-size: 13px;
        gap: 3px 12px;
    }
    
    #identitas-sekolah .info-data .data-grid .label,
    #identitas-sekolah .info-data .data-grid .value,
    #identitas-sekolah .info-data .data-grid .value-bold {
        font-size: 13px;
        padding: 3px 0;
    }
}

@media (max-width: 480px) {
    .teacher-grid {
        grid-template-columns: 1fr;
    }
    
    #identitas-sekolah .info-logo {
        min-height: 150px;
        padding: 15px;
    }
    
    #identitas-sekolah .info-logo img {
        max-width: 100px;
        padding: 6px;
    }
    
    #identitas-sekolah .info-data .data-grid {
        grid-template-columns: 1fr;
        gap: 2px;
    }
    
    #identitas-sekolah .info-data .data-grid .label {
        border-bottom: none;
        padding-bottom: 0;
        font-size: 12px;
        color: #94a3b8;
    }
    
    #identitas-sekolah .info-data .data-grid .value,
    #identitas-sekolah .info-data .data-grid .value-bold {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 6px;
        margin-bottom: 2px;
        font-size: 14px;
    }
    
    #identitas-sekolah .info-data .data-grid .value-bold {
        color: #0284c7;
    }
}



@media (max-width: 480px) {
    #lokasi-sekolah {
        padding: 30px 0 !important;
        margin-top: 20px !important;
    }
    
    #lokasi-sekolah h2 {
        font-size: 20px !important;
    }
    
    #lokasi-sekolah .alamat-info p {
        font-size: 12px !important;
    }
}
</style>

<div class="page-header">
    <h1>Profil Sekolah</h1>
    <p>Mengenal lebih dekat SMP Negeri 28 Balikpapan</p>
</div>

<div class="container">
    <div class="section" id="identitas-sekolah">
        <h2 class="section-title">Identitas Sekolah</h2>
        
        <div class="info-wrapper">
            <div class="info-logo">
                <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo SMP Negeri 28 Balikpapan">
            </div>
            
            <div class="info-data">
                <h3 class="school-name">SMP NEGERI 28 BALIKPAPAN</h3>
                <div class="data-grid">
                    <span class="label">Nama Sekolah</span>
                    <span class="value">SMP Negeri 28 Balikpapan</span>
                    
                    <span class="label">NPSN</span>
                    <span class="value">70054141</span>
                    
                    <span class="label">Status</span>
                    <span class="value">NEGERI</span>
                    
                    <span class="label">Status Kepemilikan</span>
                    <span class="value">Pemerintah Kota Balikpapan</span>
                    
                    <span class="label">SK Pendirian</span>
                    <span class="value">188.45-163/2025</span>
                    
                    <span class="label">Tanggal SK Pendirian</span>
                    <span class="value">28 Februari 2025</span>
                    
                    <span class="label">SK Izin Operasional</span>
                    <span class="value">420/1321/Disdikbud</span>
                    
                    <span class="label">Kepala Sekolah</span>
                    <span class="value">Aris Broto, S.Pd</span>
                </div>
            </div>
        </div>
    </div>

    <div class="section" id="visi-misi">
        <h2 class="section-title">Visi & Misi</h2>
        
        <div class="visi-box">
            <h3>Visi Sekolah</h3>
            <p>"Terwujudnya Peserta Didik yang Berakhlak Mulia, Unggul, Berdaya Saing Global Berbasis Kearifan Lokal dan Berbudaya Lingkungan"</p>
        </div>
        
        <h3 class="section-subtitle">Misi Sekolah</h3>
        <ol class="misi-list">
            <li>Menyelenggarakan pembelajaran mendalam (deep learning) yang menumbuhkan kemampuan berpikir kritis, reflektif, dan kontekstual dalam setiap mata pelajaran.</li>
            <li>Menumbuhkan lingkungan sekolah yang religius melalui pembiasaan ibadah rutin dan internalisasi nilai-nilai kejujuran, disiplin, serta tata krama dalam interaksi sosial.</li>
            <li>Mengembangkan karakter unggul peserta didik melalui integrasi nilai-nilai Profil Pelajar Pancasila dan 7 Kebiasaan Anak Indonesia Hebat (KAIH).</li>
            <li>Mengintegrasikan teknologi informasi dalam proses pembelajaran dan meningkatkan kemampuan literasi serta komunikasi lintas budaya untuk mempersiapkan siswa menghadapi tantangan di tingkat internasional.</li>
            <li>Menciptakan lingkungan belajar yang berkesadaran (mindful) dengan membangun kesadaran peserta didik terhadap proses belajarnya, emosinya, dan tujuan belajarnya.</li>
            <li>Menumbuhkan lingkungan sekolah yang religius melalui pembiasaan ibadah rutin dan internalisasi nilai-nilai kejujuran, disiplin, serta tata krama dalam interaksi sosial.</li>
            <li>Mengembangkan kurikulum yang mengangkat potensi daerah dan kearifan lokal Balikpapan sebagai identitas diri dalam memperkuat kecintaan terhadap budaya bangsa.</li>
            <li>Merancang pembelajaran bermakna (meaningful) yang relevan dengan kehidupan peserta didik, berbasis pengalaman, serta mendorong eksplorasi dan pemecahan masalah.</li>
            <li>Membangun suasana pembelajaran yang menyenangkan (joyful learning) melalui pendekatan yang humanis, interaktif, dan sesuai dengan minat dan bakat peserta didik.</li>
            <li>Meningkatkan kapasitas guru sebagai fasilitator pembelajaran melalui pelatihan berkelanjutan, kolaborasi profesional, dan budaya refleksi.</li>
            <li>Menciptakan ekosistem sekolah yang bersih, sehat, dan asri melalui program Gerakan ASRI (Aman, Sehat, Resik, dan Indah), Program pendidikan lingkungan hidup lainnya serta pembiasaan perilaku ramah lingkungan bagi seluruh warga sekolah.</li>
        </ol>
    </div>

    <div class="section" id="tujuan">
        <h2 class="section-title">Tujuan Pendidikan</h2>
        <p class="text-content">Tujuan pendidikan yang hendak dicapai oleh SMP Negeri 28 Balikpapan Tahun Pelajaran 2025/2026 adalah:</p>
        <ol class="tujuan-list">
            <li>Menghasilkan lulusan yang memiliki karakter kuat sesuai nilai-nilai Pancasila: religius, mandiri, gotong royong, bernalar kritis, kreatif, dan berkebhinekaan global.</li>
            <li>Mengembangkan kemampuan berpikir kritis, reflektif, dan kontekstual dalam memecahkan masalah kehidupan nyata.</li>
            <li>Membiasakan peserta didik dalam 7 Kebiasaan Anak Indonesia Hebat (KAIH) untuk membentuk pola hidup sehat, disiplin, dan tangguh.</li>
            <li>Menciptakan proses pembelajaran yang menginspirasi, bermakna, dan menyenangkan, sehingga meningkatkan motivasi dan kecintaan belajar peserta didik.</li>
            <li>Menumbuhkan kesadaran peserta didik terhadap tanggung jawab pribadi dan sosial, serta menjadikan mereka agen perubahan di lingkungan sekitarnya.</li>
            <li>Terwujudnya ekosistem sekolah yang bersih, sehat, dan asri melalui program Gerakan ASRI (Aman, Sehat, Resik, dan Indah), Program pendidikan lingkungan hidup lainnya serta pembiasaan perilaku ramah lingkungan bagi seluruh warga sekolah.</li>
            <li>Mewujudkan ekosistem sekolah yang kolaboratif dan adaptif, yang mendukung pelaksanaan Kurikulum Merdeka secara optimal.</li>
        </ol>
    </div>

    <div class="section" id="tenaga-pendidik">
    <h2 class="section-title">Tenaga Pendidik & Kependidikan</h2>
    <p class="text-content">SMP Negeri 28 Balikpapan memiliki fondasi SDM yang didominasi oleh tenaga pendidik muda dengan kualifikasi akademik S1 yang telah memenuhi standar minimal.</p>
    
    <div class="teacher-grid">
        <?php foreach ($teachers as $teacher): 
            $photo = getTeacherPhoto($teacher['name']);
        ?>
        <div class="teacher-card">
            <div class="teacher-avatar">
                <?php if ($photo): ?>
                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($teacher['name']); ?>">
                <?php else: ?>
                    <span><?php echo $teacher['initials']; ?></span>
                <?php endif; ?>
            </div>
            <div class="name"><?php echo $teacher['name']; ?></div>
            <div class="position"><?php echo $teacher['position']; ?></div>
            <div class="qualification">S1</div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="section" id="fasilitas">
    <h2 class="section-title">Fasilitas Sekolah</h2>
    <p class="text-content">Sebagai satuan pendidikan yang baru beroperasi, SMP Negeri 28 Balikpapan sedang berada dalam fase krusial penataan sarana prasarana. Saat ini, aktivitas pembelajaran berpusat pada sembilan ruang kelas yang didesain secara adaptif untuk mendukung model pembelajaran kolaboratif dan berdiferensiasi.</p>
    
    <div class="fasilitas-gallery">
        <div class="fasilitas-item">
            <div class="fasilitas-thumb">
                <img src="assets/img/fasilitas/ruang-kelas.jpg">
                <div class="fasilitas-overlay">
                </div>
            </div>
            <div class="fasilitas-info">
                <h4>Ruang Kelas</h4>
                <p>9 ruang kelas modern dengan fasilitas multimedia dan AC yang mendukung pembelajaran kolaboratif dan berdiferensiasi.</p>
                <a href="#" class="btn-foto">Lihat Foto →</a>
            </div>
        </div>

        <div class="fasilitas-item">
            <div class="fasilitas-thumb">
                <img src="assets/img/fasilitas/laboratorium.jpg">
                <div class="fasilitas-overlay">
                </div>
            </div>
            <div class="fasilitas-info">
                <h4>Laboratorium Komputer</h4>
                <p>Lab komputer dengan 30 unit PC dan koneksi internet cepat untuk mendukung pembelajaran TIK dan literasi digital.</p>
                <a href="#" class="btn-foto">Lihat Foto →</a>
            </div>
        </div>

        <div class="fasilitas-item">
            <div class="fasilitas-thumb">
                <img src="assets/img/fasilitas/perpus.jpg">
                <div class="fasilitas-overlay">
                </div>
            </div>
            <div class="fasilitas-info">
                <h4>Perpustakaan</h4>
                <p>Koleksi buku fisik dan digital dengan akses online serta sudut baca yang nyaman untuk menumbuhkan budaya literasi.</p>
                <a href="#" class="btn-foto">Lihat Foto →</a>
            </div>
        </div>

        <div class="fasilitas-item">
            <div class="fasilitas-thumb">
                <img src="assets/img/fasilitas/masjid.jpg">
                <div class="fasilitas-overlay">
                </div>
            </div>
            <div class="fasilitas-info">
                <h4>Masjid Sekolah</h4>
                <p>Ruang ibadah yang nyaman dan representatif untuk kegiatan keagamaan dan pembentukan karakter religius siswa.</p>
                <a href="#" class="btn-foto">Lihat 6 Foto →</a>
            </div>
        </div>

        <div class="fasilitas-item">
            <div class="fasilitas-thumb">
                <img src="assets/img/fasilitas/olahraga.jpg">
                <div class="fasilitas-overlay">
                </div>
            </div>
            <div class="fasilitas-info">
                <h4>Lapangan</h4>
                <p>Lapangan serbaguna untuk berbagai kegiatan olahraga seperti basket, futsal, voli, serta upacara dan kegiatan sekolah.</p>
                <a href="#" class="btn-foto">Lihat Foto →</a>
            </div>
        </div>

        <div class="fasilitas-item">
            <div class="fasilitas-thumb">
                <img src="assets/img/fasilitas/kantin.jpg">
                <div class="fasilitas-overlay">
                </div>
            </div>
            <div class="fasilitas-info">
                <h4>Kantin Sekolah</h4>
                <p>Kantin dengan menu gizi seimbang, bersih, dan higienis yang mendukung pola makan sehat bagi seluruh warga sekolah.</p>
                <a href="#" class="btn-foto">Lihat Foto →</a>
            </div>
        </div>
    </div>
</div>
    <div class="section" id="ekskul">
        <h2 class="section-title">Ekstrakurikuler</h2>
        <p class="text-content">Kegiatan ekstrakurikuler di SMP Negeri 28 Balikpapan diselenggarakan sebagai bagian dari pembentukan karakter dan pengembangan potensi peserta didik secara menyeluruh. Berdasarkan minat dan bakat, difasilitasi oleh guru atau yang kompeten.</p>
        
        <div class="ekskul-grid">
            <div class="ekskul-card">
                <div class="icon">⚜️</div>
                <h3>Pramuka</h3>
                <p class="desc">Membentuk karakter kemandirian, kedisiplinan, serta keterampilan kepanduan dan ketahanan mental.</p>
                <div class="meta">
                    <span><span class="label">📅 Hari:</span> Jumat</span>
                    <span><span class="label">⏰ Waktu:</span> 14.00 - 16.00</span>
                    <span><span class="label">📍 Lokasi:</span> Lapangan Utama</span>
                </div>
            </div>
            <div class="ekskul-card">
                <div class="icon">🥋</div>
                <h3>Pencak Silat</h3>
                <p class="desc">Melestarikan warisan budaya bangsa, melatih konsentrasi, kekuatan fisik, serta teknik pertahanan diri.</p>
                <div class="meta">
                    <span><span class="label">📅 Hari:</span> Selasa</span>
                    <span><span class="label">⏰ Waktu:</span> 15.30 - 17.00</span>
                    <span><span class="label">📍 Lokasi:</span> Lapangan Sekolah</span>
                </div>
            </div>
            <div class="ekskul-card">
                <div class="icon">⚽</div>
                <h3>Futsal</h3>
                <p class="desc">Mengembangkan bakat olahraga, melatih koordinasi motorik, sportivitas, dan strategi kerja sama tim.</p>
                <div class="meta">
                    <span><span class="label">📅 Hari:</span> Rabu</span>
                    <span><span class="label">⏰ Waktu:</span> 15.30 - 17.00</span>
                    <span><span class="label">📍 Lokasi:</span> Lapangan Futsal</span>
                </div>
            </div>
            <div class="ekskul-card">
                <div class="icon">🏥</div>
                <h3>PMR</h3>
                <p class="desc">Melatih keterampilan pertolongan pertama, kesiapsiagaan bencana, dan jiwa kemanusiaan.</p>
                <div class="meta">
                    <span><span class="label">📅 Hari:</span> Kamis</span>
                    <span><span class="label">⏰ Waktu:</span> 15.30 - 17.00</span>
                    <span><span class="label">📍 Lokasi:</span> Ruang UKS</span>
                </div>
            </div>
            <div class="ekskul-card">
                <div class="icon">🏹</div>
                <h3>Memanah</h3>
                <p class="desc">Melatih fokus, konsentrasi, stabilitas emosi, ketepatan, dan kekuatan otot tubuh.</p>
                <div class="meta">
                    <span><span class="label">📅 Hari:</span> Senin</span>
                    <span><span class="label">⏰ Waktu:</span> 15.30 - 17.00</span>
                    <span><span class="label">📍 Lokasi:</span> Lapangan Sekolah</span>
                </div>
            </div>
            <div class="ekskul-card">
                <div class="icon">🌱</div>
                <h3>Kader Lingkungan</h3>
                <p class="desc">Mewujudkan sekolah Adiwiyata melalui pengelolaan sampah, penghijauan, dan kampanye gaya hidup ramah lingkungan.</p>
                <div class="meta">
                    <span><span class="label">📅 Hari:</span> Sabtu</span>
                    <span><span class="label">⏰ Waktu:</span> 08.00 - 10.00</span>
                    <span><span class="label">📍 Lokasi:</span> Taman Sekolah</span>
                </div>
            </div>
        </div>
            </div> 
            
    <section id="lokasi" style="background: white; padding: 60px 0 40px; margin-top: 50px;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 40px;">
            <h2 style="text-align: center; font-size: 32px; color: #1e293b; margin-bottom: 40px; font-weight: 800; padding-top: 20px;">Lokasi Sekolah</h2>
            <div style="border-radius: 15px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.xxx!2d116.9685572!3d-1.1991462!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2df14f7e778cf3a1%3A0x7264a9c9bc664794!2sSMP%20Negeri%2028%20Balikpapan!5e0!3m2!1sid!2sid!4v1234567890!5m2!1sid!2sid" 
                    width="100%" 
                    height="400px" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </section>

</div>
</body>
</html>
<?php include 'footer.php'; ?>
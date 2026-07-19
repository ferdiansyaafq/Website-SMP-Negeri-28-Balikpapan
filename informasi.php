<?php
$page_title = "Informasi";
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

.pengumuman-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 20px;
}

.pengumuman-item {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    padding: 20px 25px;
    border-radius: 12px;
    border-left: 4px solid #0284c7;
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.pengumuman-item .date {
    background: #0284c7;
    color: white;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.pengumuman-item .content h4 {
    color: #1e293b;
    font-size: 16px;
    margin-bottom: 5px;
}

.pengumuman-item .content p {
    color: #64748b;
    font-size: 14px;
    line-height: 1.6;
}

.kaih-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.kaih-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    border: 2px solid transparent;
    transition: all 0.3s;
}

.kaih-card:hover {
    transform: translateY(-5px);
    border-color: #0284c7;
    box-shadow: 0 15px 30px rgba(2,132,199,0.15);
}

.kaih-card .icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.kaih-card h4 {
    font-size: 16px;
    color: #1e293b;
    margin-bottom: 8px;
    font-weight: 700;
}

.kaih-card p {
    font-size: 13px;
    color: #64748b;
    line-height: 1.6;
}

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
    .kaih-cards {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-header">
    <h1>Informasi Sekolah</h1>
    <p>Berita, pengumuman, dan kegiatan terkini</p>
</div>

<div class="container">
    <div class="section" id="pengumuman">
        <h2 class="section-title">Pengumuman Terbaru</h2>
        <div class="pengumuman-list">
            <div class="pengumuman-item">
                <div class="date">10 JUL 2026</div>
                <div class="content">
                    <h4>Penerimaan Peserta Didik Baru (PPDB) 2026/2027</h4>
                    <p>Pendaftaran PPDB Tahun Ajaran 2026/2027 telah dibuka.</p>
                </div>
            </div>
            <div class="pengumuman-item">
                <div class="date">05 JUL 2026</div>
                <div class="content">
                    <h4>Implementasi Kurikulum Merdeka dengan Deep Learning</h4>
                    <p>SMP Negeri 28 Balikpapan resmi menerapkan pendekatan Pembelajaran Mendalam.</p>
                </div>
            </div>
            <div class="pengumuman-item">
                <div class="date">01 JUL 2026</div>
                <div class="content">
                    <h4>Program 7 Kebiasaan Anak Indonesia Hebat (KAIH)</h4>
                    <p>Seluruh siswa diwajibkan mengikuti program KAIH.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="section" id="berita">
        <h2 class="section-title">📰 Berita Terkini</h2>
        <div class="pengumuman-list">
            <div class="pengumuman-item">
                <div class="date">10 JUL 2026</div>
                <div class="content">
                    <h4>PPDB Tahun Ajaran 2026/2027 Resmi Dibuka</h4>
                    <p>Penerimaan Peserta Didik Baru untuk tahun ajaran 2026/2027 telah resmi dibuka.</p>
                </div>
            </div>
            <div class="pengumuman-item">
                <div class="date">05 JUL 2026</div>
                <div class="content">
                    <h4>Implementasi Kurikulum Merdeka dengan Deep Learning</h4>
                    <p>SMPN 28 Balikpapan resmi menerapkan pendekatan Pembelajaran Mendalam.</p>
                </div>
            </div>
            <div class="pengumuman-item">
                <div class="date">01 JUL 2026</div>
                <div class="content">
                    <h4>Program 7 Kebiasaan Anak Indonesia Hebat Diluncurkan</h4>
                    <p>Program KAIH resmi diluncurkan untuk membentuk karakter positif siswa.</p>
                </div>
            </div>
        </div>
    </div>

        <div class="section" id="kaih">
        <h2 class="section-title">🌟 7 Kebiasaan Anak Indonesia Hebat (KAIH)</h2>
        <p class="text-content">Membangun karakter anak Indonesia yang hebat melalui pembiasaan harian.</p>
        
        <div class="kaih-list">
            <!-- KAIH 1 -->
            <div class="kaih-item">
                <div class="kaih-number">1</div>
                <div class="kaih-content">
                    <h4>Bangun Pagi & Merapikan Tempat Tidur</h4>
                    <span class="kaih-tag">Kemandirian & Disiplin</span>
                    <p>Membiasakan bangun pagi tepat waktu dan langsung merapikan tempat tidur sendiri. Kebiasaan ini melatih kedisiplinan, tanggung jawab, dan kemandirian anak dalam mengatur kehidupan sehari-hari.</p>
                </div>
            </div>

            <!-- KAIH 2 -->
            <div class="kaih-item">
                <div class="kaih-number">2</div>
                <div class="kaih-content">
                    <h4>Beribadah (Sholat Subuh / Ibadah Pagi)</h4>
                    <span class="kaih-tag">Religius - Beriman & Bertakwa kepada Tuhan YME</span>
                    <p>Melaksanakan ibadah sesuai agama dan kepercayaan masing-masing (sholat subuh, doa pagi, atau ibadah lainnya). Memperkuat dimensi spiritual dan ketanggahan kepada Tuhan Yang Maha Esa sebagai fondasi karakter.</p>
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
                    <h4>Sarapan Sehat & Minum Air Putih</h4>
                    <span class="kaih-tag">Pola Hidup Sehat & Fokus Belajar</span>
                    <p>Mengonsumsi sarapan bergizi seimbang dan minum air putih yang cukup sebelum berangkat sekolah. Sarapan penting untuk energi belajar, konsentrasi, dan pertumbuhan optimal.</p>
                </div>
            </div>

            <!-- KAIH 5 -->
            <div class="kaih-item">
                <div class="kaih-number">5</div>
                <div class="kaih-content">
                    <h4>Gemar Membaca (Literasi)</h4>
                    <span class="kaih-tag">Bernalar Kritis & Wawasan Luas</span>
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
                    <span class="kaih-tag">Literasi Finansial & Pengendalian Diri</span>
                    <p>Menyediakan sebagian uang jajan untuk ditabung atau membiasakan hidup hemat (tidak jajan berlebihan). Mengenalkan konsep pengelolaan keuangan sederhana, perencanaan masa depan, dan pengendalian diri sejak dini.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
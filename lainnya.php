<?php
$page_title = "Lainnya & FAQ - SMP Negeri 28 Balikpapan";
include 'header.php';
?>

<link rel="stylesheet" href="assets/css/faq.css">

<div class="page-header">
    <h1>❓ Pusat Informasi & Layanan</h1>
    <p>Pertanyaan yang Sering Diajukan (FAQ) dan Layanan Pendukung SMP Negeri 28 Balikpapan</p>
</div>

<div class="container">

    <!-- Quick Navigation to Essential Features -->
    <div class="quick-nav-grid">
        <a href="buku_tamu.php" class="quick-nav-card">
            <div>
                <div class="quick-nav-icon">📖</div>
                <h4>Buku Tamu Digital</h4>
                <p>Formulir pencatatan kunjungan resmi untuk dinas, orang tua, masyarakat, atau instansi mitra.</p>
            </div>
            <span class="quick-nav-link">Isi Buku Tamu →</span>
        </a>

        <a href="bullying.php" class="quick-nav-card" style="border-color: #fca5a5;">
            <div>
                <div class="quick-nav-icon">🛡️</div>
                <h4 style="color: #b91c1c;">Ruang Peduli</h4>
                <p>Layanan pengaduan dan perlindungan perundungan. <strong>Kerahasiaan laporan 100% terjamin.</strong></p>
            </div>
            <span class="quick-nav-link" style="color: #dc2626;">Lapor ke Ruang Peduli →</span>
        </a>

        <a href="survey.php" class="quick-nav-card">
            <div>
                <div class="quick-nav-icon">📝</div>
                <h4>Survei Pelayanan</h4>
                <p>Beri penilaian kepuasan dengan emotikon serta sampaikan kritik dan saran perbaikan.</p>
            </div>
            <span class="quick-nav-link">Isi Survei Pelayanan →</span>
        </a>
    </div>

    <!-- FAQ -->
    <div class="section" id="faq">
        <h2 class="section-title">❓ Pertanyaan yang Sering Diajukan</h2>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                Bagaimana prosedur kunjungan kedinasan / tamu ke sekolah?
                <span>▾</span>
            </div>
            <div class="faq-answer">
                Setiap tamu yang berkunjung dapat mengisi formulir melalui menu <a href="buku_tamu.php" style="color:#0284c7;font-weight:700;">Buku Tamu Digital</a> untuk mencatat identitas, pihak yang ingin ditemui, dan agenda kunjungan demi ketertiban serta kelancaran pelayanan.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                Bagaimana cara melaporkan tindakan bullying / perundungan?
                <span>▾</span>
            </div>
            <div class="faq-answer">
                Anda dapat melaporkan kejadian melalui layanan <a href="bullying.php" style="color:#dc2626;font-weight:700;">Ruang Peduli</a>. <strong>Kami menjamin 100% menjaga kerahasiaan identitas dan laporan Anda.</strong> Anda juga dapat langsung menghubungi kontak tim pendamping (PIC) yang tercantum di halaman tersebut.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                Apa itu KAIH?
                <span>▾</span>
            </div>
            <div class="faq-answer">
                KAIH (Karakter Aktivitas Ibadah Harian) adalah sistem monitoring digital untuk mencatat dan memantau perkembangan karakter, aktivitas, dan ibadah siswa setiap hari.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                Bagaimana cara mendaftar PPDB?
                <span>▾</span>
            </div>
            <div class="faq-answer">
                Pendaftaran PPDB dapat dilakukan secara online melalui website resmi Dinas Pendidikan Kota Balikpapan atau datang langsung ke sekolah sesuai jadwal resmi.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                Apa saja ekstrakurikuler yang tersedia?
                <span>▾</span>
            </div>
            <div class="faq-answer">
                Kami menyediakan Pramuka (wajib), Pencak Silat, Futsal, PMR, Memanah, dan Kader Lingkungan.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                Bagaimana sistem pembelajaran di SMPN 28 Balikpapan?
                <span>▾</span>
            </div>
            <div class="faq-answer">
                Kami menerapkan pendekatan Deep Learning (Pembelajaran Mendalam) yang berfokus pada pembelajaran yang berkesadaran (mindful), bermakna (meaningful), dan menyenangkan (joyful).
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                Bagaimana cara menyampaikan kritik dan saran pelayanan?
                <span>▾</span>
            </div>
            <div class="faq-answer">
                Anda dapat mengisi <a href="survey.php" style="color:#0284c7;font-weight:700;">Survei Pelayanan</a> dengan memilih tingkat kepuasan berformat emotikon serta menuliskan agenda kunjungan dan kritik konstruktif untuk kemajuan sekolah.
            </div>
        </div>

    </div>

    <!-- CTA Card untuk Survey -->
    <div class="cta-card">
        <h3>📝 Punya Masukan untuk Sekolah?</h3>
        <p>Suara Anda sangat berarti untuk peningkatan kualitas pendidikan di SMP Negeri 28 Balikpapan.</p>
        <a href="survey.php" class="btn-cta">Isi Survei Pelayanan →</a>
    </div>

</div>

<script src="assets/js/faq.js"></script>

<?php include 'footer.php'; ?>
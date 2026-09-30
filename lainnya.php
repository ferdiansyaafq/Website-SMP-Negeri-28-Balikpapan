<?php
$page_title = "FAQ - SMP Negeri 28 Balikpapan";
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
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 40px;
}

.section {
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.section-title {
    font-size: 28px;
    color: #1e293b;
    margin-bottom: 20px;
    font-weight: 800;
    border-left: 5px solid #0284c7;
    padding-left: 15px;
}

.faq-item {
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 15px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.faq-question {
    padding: 20px 25px;
    font-weight: 700;
    color: #1e293b;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s;
}

.faq-question:hover {
    background: #e0f2fe;
    color: #0284c7;
}

.faq-answer {
    padding: 0 25px 20px;
    color: #475569;
    line-height: 1.8;
    font-size: 15px;
    display: none;
}

.faq-item.open .faq-answer {
    display: block;
}

/* CTA Card untuk Survey */
.cta-card {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: white;
    padding: 40px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(2, 132, 199, 0.3);
}

.cta-card h3 {
    font-size: 24px;
    font-weight: 800;
    margin-bottom: 10px;
}

.cta-card p {
    font-size: 15px;
    opacity: 0.9;
    margin-bottom: 20px;
}

.btn-cta {
    display: inline-block;
    padding: 14px 32px;
    background: white;
    color: #0284c7;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 16px;
    transition: all 0.3s;
}

.btn-cta:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    .page-header { padding: 40px 20px; }
    .page-header h1 { font-size: 26px; }
    .container { padding: 0 20px; margin: 20px auto; }
    .section { padding: 25px; }
    .section-title { font-size: 22px; }
    .cta-card { padding: 30px 20px; }
    .cta-card h3 { font-size: 20px; }
}
</style>

<div class="page-header">
    <h1>❓ FAQ</h1>
    <p>Pertanyaan yang Sering Diajukan</p>
</div>

<div class="container">

    <!-- FAQ -->
    <div class="section">
        <h2 class="section-title">❓ Pertanyaan yang Sering Diajukan</h2>

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
                Pendaftaran PPDB dapat dilakukan secara online melalui website resmi Dinas Pendidikan Kota Balikpapan atau datang langsung ke sekolah.
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
                Bagaimana cara menghubungi sekolah?
                <span>▾</span>
            </div>
            <div class="faq-answer">
                Anda dapat mengisi form survei pelayanan untuk memberikan masukan atau menghubungi pihak sekolah langsung melalui kontak yang tersedia.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                Bagaimana cara melaporkan bullying?
                <span>▾</span>
            </div>
            <div class="faq-answer">
                Anda dapat melaporkan kasus bullying melalui menu <a href="bullying.php" style="color:#0284c7;font-weight:700;">Pelaporan Bullying</a>. Semua laporan akan dijaga kerahasiaannya.
            </div>
        </div>
    </div>

    <!-- CTA ke Survey -->
    <div class="cta-card">
        <h3>📝 Punya Masukan untuk Sekolah?</h3>
        <p>Suara Anda sangat berarti untuk peningkatan kualitas pendidikan di SMP Negeri 28 Balikpapan.</p>
        <a href="survey.php" class="btn-cta">Isi Survei Pelayanan →</a>
    </div>

</div>

<script>
function toggleFaq(element) {
    const item = element.parentElement;
    item.classList.toggle('open');
}
</script>

<?php include 'footer.php'; ?>
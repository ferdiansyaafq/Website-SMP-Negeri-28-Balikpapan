<?php
$page_title = "Lainnya - Login, FAQ, Kontak";
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

.login-card {
    max-width: 500px;
    margin: 0 auto;
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    padding: 40px;
    border-radius: 20px;
    text-align: center;
    color: white;
}

.login-card h3 {
    font-size: 24px;
    margin-bottom: 15px;
}

.login-card p {
    font-size: 15px;
    margin-bottom: 25px;
    opacity: 0.9;
}

.login-btn {
    display: inline-block;
    background: white;
    color: #0284c7;
    padding: 15px 40px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 700;
    font-size: 16px;
    transition: all 0.3s;
}

.login-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
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

.contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.contact-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    padding: 35px 30px;
    border-radius: 15px;
    border-left: 4px solid #0284c7;
    transition: all 0.3s;
    text-align: center;
}

.contact-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(2,132,199,0.2);
}

.contact-card .icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.contact-card h4 {
    font-size: 14px;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.contact-card p {
    color: #1e293b;
    font-size: 16px;
    font-weight: 600;
    line-height: 1.6;
}

.contact-card a {
    text-decoration: none;
    font-weight: 600;
}

.contact-card .desc {
    color: #64748b;
    font-size: 13px;
    margin-top: 8px;
    font-weight: 400;
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
    .contact-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-header">
    <h1>Lainnya</h1>
    <p>Login, FAQ, dan Kontak</p>
</div>
    <div class="section" id="faq">
        <h2 class="section-title">❓ Pertanyaan yang Sering Diajukan (FAQ)</h2>
        
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
                Anda dapat menghubungi kami melalui email atau WhatsApp yang tersedia di bagian kontak di bawah ini.
            </div>
        </div>
    </div>

    <!-- Kontak -->
    <div class="section" id="kontak">
        <h2 class="section-title">📞 Hubungi Kami</h2>
        <p class="text-content">Kami siap melayani dan menerima masukan dari Anda. Hubungi kami melalui email atau WhatsApp.</p>
        
        <div class="contact-grid">
            <!-- Email -->
            <div class="contact-card">
                <div class="icon">✉️</div>
                <h4 style="color: #0284c7;">Email</h4>
                <p>
                    <a href="mailto:admin@smpn28balikpapan.sch.id" style="color: #0284c7;">
                        admin@smpn28balikpapan.sch.id
                    </a>
                </p>
                <p class="desc">Kami akan merespon dalam 1x24 jam</p>
            </div>
            
            <!-- WhatsApp -->
            <div class="contact-card" style="border-left-color: #25D366;">
                <div class="icon">💬</div>
                <h4 style="color: #25D366;">WhatsApp</h4>
                <p>
                    <a href="https://wa.me/6285349310534" target="_blank" style="color: #25D366;">
                        +62 853-4931-0534
                    </a>
                </p>
                <p class="desc">Chat langsung dengan admin sekolah</p>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFaq(element) {
    const item = element.parentElement;
    item.classList.toggle('open');
}
</script>

<?php include 'footer.php'; ?>
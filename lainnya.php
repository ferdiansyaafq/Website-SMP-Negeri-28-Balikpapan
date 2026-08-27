<?php
$page_title = "Lainnya - Login, FAQ, Survei Pelayanan";
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

/* ========================================= */
/* CSS UNTUK RATING BINTANG INTERAKTIF       */
/* ========================================= */
.star-rating {
    display: flex;
    flex-direction: row-reverse; /* Membalik urutan agar efek hover CSS berfungsi ke kiri */
    justify-content: flex-end;
    gap: 5px;
}
.star-rating input {
    display: none; /* Sembunyikan radio button asli */
}
.star-rating label {
    font-size: 40px;
    color: #d1d5db; /* Warna abu-abu bawaan */
    cursor: pointer;
    transition: color 0.2s ease-in-out;
}
/* Efek menyala saat di-hover atau diklik */
.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #fbbf24; /* Warna kuning menyala */
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
}
</style>

<div class="page-header">
    <h1>Lainnya</h1>
    <p>Login, FAQ dan Form Survei Pelayanan</p>
</div>

<div class="container">
    
    <!-- ================= SECTION FAQ ================= -->
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
                Anda dapat mengisi form survei pelayanan di bawah ini untuk memberikan masukan atau menghubungi pihak sekolah.
            </div>
        </div>
    </div>

    <!-- ================= SECTION FORM SURVEI ================= -->
    <div class="section" id="Survei-Pelayanan">
        <h2 class="section-title">📝 Form Survei Pelayanan</h2>
        <p style="margin-bottom: 25px; color: #475569;">Kami siap melayani dan menerima masukan dari Anda demi peningkatan kualitas sekolah.</p>

        <div class="survei-container" style="background: #ffffff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 40px; max-width: 700px; margin: 0 auto; border: 1px solid #f0f0f0;">
            
            <form action="proses_survei.php" method="POST">
                <!-- Input Nama -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; color: #333; display: block; margin-bottom: 8px;">Nama Lengkap</label>
                    <input type="text" name="nama_pengisi" required placeholder="Masukkan nama Anda..." style="width: 100%; padding: 14px 15px; border: 2px solid #eef2f5; border-radius: 12px; outline: none; transition: 0.3s; background-color: #fcfcfc;">
                </div>

                <!-- Pilih Peran -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; color: #333; display: block; margin-bottom: 8px;">Status</label>
                    <select name="peran" required style="width: 100%; padding: 14px 15px; border: 2px solid #eef2f5; border-radius: 12px; outline: none; background-color: #fcfcfc; cursor: pointer;">
                        <option value="Siswa">Siswa</option>
                        <option value="Orang Tua">Orang Tua / Wali Murid</option>
                        <option value="Masyarakat">Masyarakat Umum</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <!-- Rating Bintang Interaktif -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; color: #333; display: block; margin-bottom: 8px;">Beri Nilai (1 - 5 Bintang)</label>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" required />
                        <label for="star5" title="5 Bintang">★</label>
                        
                        <input type="radio" id="star4" name="rating" value="4" />
                        <label for="star4" title="4 Bintang">★</label>
                        
                        <input type="radio" id="star3" name="rating" value="3" />
                        <label for="star3" title="3 Bintang">★</label>
                        
                        <input type="radio" id="star2" name="rating" value="2" />
                        <label for="star2" title="2 Bintang">★</label>
                        
                        <input type="radio" id="star1" name="rating" value="1" />
                        <label for="star1" title="1 Bintang">★</label>
                    </div>
                </div>

                <!-- Ulasan -->
                <div style="margin-bottom: 25px;">
                    <label style="font-weight: 600; color: #333; display: block; margin-bottom: 8px;">Ulasan & Saran</label>
                    <textarea name="ulasan" rows="4" required placeholder="Tuliskan pengalaman atau saran Anda di sini..." style="width: 100%; padding: 14px 15px; border: 2px solid #eef2f5; border-radius: 12px; outline: none; resize: vertical; background-color: #fcfcfc;"></textarea>
                </div>

                <!-- Tombol Submit -->
                <button type="submit" style="background: #0d6efd; color: white; border: none; padding: 15px 20px; width: 100%; border-radius: 12px; font-weight: bold; font-size: 16px; cursor: pointer; box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3); transition: transform 0.2s;">
                    Kirim Ulasan
                </button>
            </form>
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
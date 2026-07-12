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
<title>Lainnya</title>
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
.text-content { font-size: 16px; color: #475569; line-height: 1.9; margin-bottom: 15px; text-align: justify; }

/* Contact Grid */
.contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
.contact-card { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 30px; border-radius: 15px; border-left: 4px solid #0284c7; transition: all 0.3s; }
.contact-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(2,132,199,0.2); }
.contact-card .icon { font-size: 36px; margin-bottom: 15px; }
.contact-card h4 { color: #0284c7; font-size: 14px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
.contact-card p { color: #1e293b; font-size: 15px; font-weight: 600; line-height: 1.6; }
.contact-card a { color: #0284c7; text-decoration: none; font-weight: 600; }
.contact-card a:hover { text-decoration: underline; }

/* Form */
.contact-form { max-width: 700px; margin: 0 auto; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; color: #1e293b; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 14px 16px; border: 2px solid #e0f2fe; border-radius: 10px; font-size: 15px; font-family: inherit; transition: all 0.3s; background: #f8fafc; }
.form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #0284c7; background: white; box-shadow: 0 0 0 3px rgba(2,132,199,0.1); }
.form-group textarea { resize: vertical; min-height: 120px; }
.submit-btn { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: white; padding: 14px 40px; border: none; border-radius: 10px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s; width: 100%; }
.submit-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(2,132,199,0.3); }

/* Map */
.map-container { border-radius: 15px; overflow: hidden; margin-top: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.map-container iframe { width: 100%; height: 400px; border: none; }

/* Social */
.social-grid { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 20px; }
.social-card { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 20px 30px; border-radius: 12px; display: flex; align-items: center; gap: 15px; text-decoration: none; color: #1e293b; transition: all 0.3s; border: 2px solid transparent; }
.social-card:hover { border-color: #0284c7; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(2,132,199,0.15); }
.social-card .icon { font-size: 28px; }
.social-card .info h4 { font-size: 14px; color: #0284c7; margin-bottom: 3px; }
.social-card .info p { font-size: 13px; color: #64748b; }

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
    .contact-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="page-header">
    <h1>Hubungi Kami</h1>
    <p>Kami siap membantu dan menerima masukan dari Anda</p>
</div>

<div class="container">
    <!-- Info lainnya -->
    <div class="section">
        <h2 class="section-title"> Informasi lainnya</h2>
        <p class="text-content">Silakan hubungi kami melalui salah satu渠道 berikut. Kami akan merespon pesan Anda secepat mungkin.</p>
        <div class="contact-grid">
            <div class="contact-card">
                <div class="icon">📍</div>
                <h4>Alamat</h4>
                <p>Jl. Persatuan RT.50<br>Kelurahan Manggar Baru<br>Kecamatan Balikpapan Timur<br>Kota Balikpapan, Kalimantan Timur<br>Kode Pos: 76115</p>
            </div>
            <div class="contact-card">
                <div class="icon">📞</div>
                <h4>Telepon</h4>
                <p>(0542) 123456</p>
            </div>
            <div class="contact-card">
                <div class="icon">📧</div>
                <h4>Email</h4>
                <p><a href="mailto:smpn28balikpapan@gmail.com">smpn28balikpapan@gmail.com</a></p>
            </div>
            <div class="contact-card">
                <div class="icon">⏰</div>
                <h4>Jam Layanan</h4>
                <p>Senin - Jumat<br>07.00 - 12.00 WITA<br><br>Sabtu - Minggu<br>Tutup</p>
            </div>
        </div>
    </div>

    <!-- Form lainnya -->
    <div class="section">
        <h2 class="section-title">✉️ Kirim Pesan</h2>
        <p class="text-content">Punya pertanyaan, saran, atau masukan? Isi form di bawah ini dan kami akan segera menghubungi Anda.</p>
        <form class="contact-form" onsubmit="handleSubmit(event)">
            <div class="form-group">
                <label for="nama">Nama Lengkap *</label>
                <input type="text" id="nama" name="nama" required placeholder="Masukkan nama lengkap Anda">
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required placeholder="contoh@email.com">
            </div>
            <div class="form-group">
                <label for="telepon">Nomor Telepon</label>
                <input type="tel" id="telepon" name="telepon" placeholder="08xxxxxxxxxx">
            </div>
            <div class="form-group">
                <label for="kategori">Kategori *</label>
                <select id="kategori" name="kategori" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="info">Informasi Umum</option>
                    <option value="ppdb">PPDB / Pendaftaran</option>
                    <option value="akademik">Akademik</option>
                    <option value="kaih">Sistem KAIH</option>
                    <option value="saran">Saran & Masukan</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>
            <div class="form-group">
                <label for="pesan">Pesan *</label>
                <textarea id="pesan" name="pesan" required placeholder="Tulis pesan Anda di sini..."></textarea>
            </div>
            <button type="submit" class="submit-btn">Kirim Pesan →</button>
        </form>
    </div>

    <!-- Lokasi -->
    <div class="section">
        <h2 class="section-title">🗺️ Lokasi Sekolah</h2>
        <p class="text-content">Kunjungi kami langsung di alamat berikut. Sekolah terletak di Kelurahan Manggar Baru, Balikpapan Timur.</p>
        <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.1234567890123!2d116.85123456789012!3d-1.2345678901234567!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMcKwMTQnMDQuNCJTIDExNsKwNTEnMDQuNCJF!5e0!3m2!1sid!2sid!4v1234567890123!5m2!1sid!2sid" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>

    <!-- Media Sosial -->
    <div class="section">
        <h2 class="section-title"> Ikuti Media Sosial Kami</h2>
        <p class="text-content">Dapatkan informasi terbaru dan update kegiatan sekolah melalui media sosial resmi kami.</p>
        <div class="social-grid">
            <a href="https://www.instagram.com/smpnegeri28bpp/" target="_blank" class="social-card">
                <div class="icon">📷</div>
                <div class="info">
                    <h4>Instagram</h4>
                    <p>@smpnegeri28bpp</p>
                </div>
            </a>
            <a href="https://www.youtube.com/@SMPNegeri28BalikpapanTimur" target="_blank" class="social-card">
                <div class="icon">▶️</div>
                <div class="info">
                    <h4>YouTube</h4>
                    <p>SMP Negeri 28 Balikpapan Timur</p>
                </div>
            </a>
            <a href="https://wa.me/6281234567890" target="_blank" class="social-card">
                <div class="icon"></div>
                <div class="info">
                    <h4>WhatsApp</h4>
                    <p>Chat langsung dengan admin</p>
                </div>
            </a>
            <a href="mailto:smpn28balikpapan@gmail.com" class="social-card">
                <div class="icon">✉️</div>
                <div class="info">
                    <h4>Email</h4>
                    <p>smpn28balikpapan@gmail.com</p>
                </div>
            </a>
        </div>
    </div>
</div>

 <footer style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); margin-top: 80px; padding: 60px 0 30px; color: white; position: relative; overflow: hidden;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 40px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">
                <div>
                    <h4 style="font-size: 18px; margin-bottom: 10px; font-weight: 700;">Jam Layanan</h4>
                    <p style="margin-bottom: 10px; opacity: 0.9; line-height: 1.8;">
                        Senin - Jumat (07.00 - 12.00 WITA)<br>
                    </p>
                </div>

                <div>
                    <h4 style="font-size: 18px; margin-bottom: 10px; font-weight: 700;">Menu Cepat</h4>
                    <ul style="list-style: none; line-height: 2.2;">
                        <li><a href="profil.php" style="color: white; text-decoration: none; opacity: 0.9; transition: opacity 0.3s;">Profil Sekolah</a></li>
                        <li><a href="informasi.php" style="color: white; text-decoration: none; opacity: 0.9; transition: opacity 0.3s;">Informasi</a></li>
                        <li><a href="fitur.php" style="color: white; text-decoration: none; opacity: 0.9; transition: opacity 0.3s;">KAIH</a></li>
                        <li><a href="panduan.php" style="color: white; text-decoration: none; opacity: 0.9; transition: opacity 0.3s;">Panduan</a></li>
                    </ul>
                </div>

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

            <div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 30px; text-align: center; opacity: 0.8; font-size: 14px;">
                <p>&copy; <?php echo date('Y'); ?> SMP Negeri 28 Balikpapan.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const hamburger = document.getElementById('hamburger');
            const mobileMenu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('overlay');
            
            if (!hamburger || !mobileMenu || !overlay) return;
            
            hamburger.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            overlay.classList.toggle('active');
            
            if (mobileMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        document.querySelectorAll('.mobile-menu-items a').forEach(link => {
            link.addEventListener('click', function() {
                toggleMenu();
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const mobileMenu = document.getElementById('mobileMenu');
                if (mobileMenu && mobileMenu.classList.contains('active')) {
                    toggleMenu();
                }
            }
        });

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

        setInterval(() => {
            changeImage(1);
        }, 5000);

        document.addEventListener('DOMContentLoaded', function() {
            const carouselImg = document.getElementById('carousel-image');
            if (carouselImg) {
                carouselImg.style.transition = 'opacity 0.3s ease';
            }
        });
    </script>
</body>
</html>
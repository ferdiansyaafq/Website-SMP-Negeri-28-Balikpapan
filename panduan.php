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
<title>Panduan - SMP Negeri 28 Balikpapan</title>
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

/* Tabs */
.tab-container { margin-top: 20px; }
.tab-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; border-bottom: 2px solid #e0f2fe; padding-bottom: 10px; }
.tab-btn { padding: 12px 24px; background: #f0f9ff; border: none; border-radius: 10px 10px 0 0; cursor: pointer; font-weight: 600; color: #64748b; transition: all 0.3s; font-size: 14px; }
.tab-btn.active { background: #0284c7; color: white; }
.tab-content { display: none; }
.tab-content.active { display: block; animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Steps */
.steps { display: flex; flex-direction: column; gap: 15px; margin-top: 20px; }
.step { display: flex; gap: 20px; align-items: flex-start; background: #f8fafc; padding: 20px; border-radius: 12px; border-left: 4px solid #0284c7; }
.step-number { background: #0284c7; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; flex-shrink: 0; }
.step-content h4 { color: #1e293b; font-size: 16px; margin-bottom: 8px; font-weight: 700; }
.step-content p { color: #64748b; font-size: 14px; line-height: 1.7; }

/* Info Box */
.info-box { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 20px; border-radius: 12px; margin: 15px 0; border-left: 4px solid #0284c7; }
.info-box h4 { color: #0284c7; margin-bottom: 10px; font-size: 16px; }
.info-box p, .info-box li { color: #475569; font-size: 14px; line-height: 1.7; }
.info-box ul { margin-left: 20px; margin-top: 8px; }

/* Warning Box */
.warning-box { background: #fef3c7; padding: 20px; border-radius: 12px; margin: 15px 0; border-left: 4px solid #f59e0b; }
.warning-box h4 { color: #92400e; margin-bottom: 10px; font-size: 16px; }
.warning-box p { color: #78350f; font-size: 14px; line-height: 1.7; }

/* FAQ */
.faq-item { background: #f8fafc; border-radius: 12px; margin-bottom: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
.faq-question { padding: 18px 20px; cursor: pointer; font-weight: 700; color: #1e293b; display: flex; justify-content: space-between; align-items: center; transition: background 0.3s; }
.faq-question:hover { background: #f0f9ff; }
.faq-answer { padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s ease; color: #64748b; font-size: 14px; line-height: 1.7; }
.faq-item.open .faq-answer { padding: 0 20px 18px; max-height: 300px; }
.faq-item.open .faq-question { background: #f0f9ff; color: #0284c7; }

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
    .tab-buttons { flex-direction: column; }
    .tab-btn { border-radius: 8px; }
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
            <a href="fitur.php">Fitur</a>
            <a href="panduan.php" class="active">Panduan</a>
            <a href="kontak.php">Kontak</a>
        </nav>
    </div>
</header>

<div class="page-header">
    <h1>Panduan Penggunaan</h1>
    <p>Panduan lengkap menggunakan sistem KAIH untuk semua pengguna</p>
</div>

<div class="container">
    <div class="section">
        <h2 class="section-title">📖 Panduan Sistem KAIH</h2>
        <p class="text-content">Sistem KAIH (Karakter Aktivitas Ibadah Harian) adalah platform digital untuk monitoring perkembangan karakter siswa. Berikut panduan lengkap untuk setiap peran pengguna.</p>

        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="showTab('siswa')">‍🎓 Siswa</button>
                <button class="tab-btn" onclick="showTab('ortu')">👨‍‍👧 Orang Tua</button>
                <button class="tab-btn" onclick="showTab('guru')">👩‍ Guru</button>
                <button class="tab-btn" onclick="showTab('faq')"> FAQ</button>
            </div>

            <!-- Tab Siswa -->
            <div id="siswa" class="tab-content active">
                <h3 style="color: #0284c7; margin-bottom: 15px; font-size: 20px;">Panduan untuk Siswa</h3>
                <p class="text-content">Sebagai siswa, kamu dapat mencatat kegiatan harian KAIH dan melihat perkembangan karaktermu.</p>

                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Login ke Sistem</h4>
                            <p>Buka halaman utama mercusuar.xyz, klik tombol <strong>"Peserta Didik"</strong>, lalu masukkan <strong>NISN</strong> dan <strong>password</strong> kamu. Password awal adalah NISN kamu.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Masuk ke Dashboard</h4>
                            <p>Setelah login, kamu akan diarahkan ke halaman <strong>Progress Harian</strong>. Di sini kamu bisa melihat kegiatan yang sudah dicatat.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Catat Kegiatan Harian</h4>
                            <p>Klik tombol <strong>"Tambah Kegiatan"</strong>, pilih jenis kegiatan KAIH (Bangun Pagi, Beribadah, Berolahraga, dll), isi keterangan, dan upload foto bukti kegiatan.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Lihat Grafik Perkembangan</h4>
                            <p>Buka menu <strong>"Grafik Bulanan"</strong> untuk melihat statistik kegiatanmu dalam bentuk grafik. Pantau progressmu setiap bulan!</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <h4>Logout</h4>
                            <p>Jangan lupa klik tombol <strong>"Logout"</strong> setelah selesai menggunakan sistem untuk keamanan akunmu.</p>
                        </div>
                    </div>
                </div>

                <div class="info-box">
                    <h4>💡 Tips untuk Siswa</h4>
                    <ul>
                        <li>Catat kegiatan setiap hari agar progressmu konsisten</li>
                        <li>Upload foto bukti kegiatan yang jelas</li>
                        <li>Jangan bagikan password kamu kepada siapapun</li>
                        <li>Segera ganti password jika sudah bisa login</li>
                    </ul>
                </div>
            </div>

            <!-- Tab Orang Tua -->
            <div id="ortu" class="tab-content">
                <h3 style="color: #0284c7; margin-bottom: 15px; font-size: 20px;">Panduan untuk Orang Tua</h3>
                <p class="text-content">Sebagai orang tua, kamu dapat memantau dan memvalidasi kegiatan harian anakmu.</p>

                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Login sebagai Orang Tua</h4>
                            <p>Klik tombol <strong>"Orang Tua"</strong> di halaman utama. Masukkan kode dengan format <strong>ORT + NISN anak</strong> (contoh: ORT1234567890) dan password.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Lihat Kegiatan Anak</h4>
                            <p>Setelah login, kamu akan melihat daftar kegiatan yang dilaporkan anakmu. Setiap kegiatan memiliki status <strong>"Menunggu Validasi"</strong> atau <strong>"Tervalidasi"</strong>.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Validasi Kegiatan</h4>
                            <p>Klik tombol <strong>"Validasi"</strong> pada kegiatan anakmu. Berikan konfirmasi apakah kegiatan tersebut benar-benar dilakukan. Kamu juga bisa memberikan komentar.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Lihat Detail & Grafik</h4>
                            <p>Klik <strong>"Detail Kegiatan"</strong> untuk melihat foto dan keterangan lengkap. Buka <strong>"Grafik Bulanan"</strong> untuk melihat tren perkembangan anak.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <h4>Cetak Laporan</h4>
                            <p>Kamu dapat mencetak laporan perkembangan anak untuk disimpan atau dibagikan ke guru.</p>
                        </div>
                    </div>
                </div>

                <div class="warning-box">
                    <h4>️ Penting!</h4>
                    <p>Password awal orang tua adalah <strong>ORT + NISN anak</strong>. Segera ganti password setelah login pertama kali untuk keamanan.</p>
                </div>
            </div>

            <!-- Tab Guru -->
            <div id="guru" class="tab-content">
                <h3 style="color: #0284c7; margin-bottom: 15px; font-size: 20px;">Panduan untuk Guru</h3>
                <p class="text-content">Sebagai guru, kamu dapat memvalidasi kegiatan siswa, mengelola data kelas, dan melihat statistik perkembangan.</p>

                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Login sebagai Guru</h4>
                            <p>Klik tombol <strong>"Guru"</strong> di halaman utama. Masukkan <strong>NIP</strong> dan <strong>password</strong>. Password awal adalah NIP kamu.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Dashboard Guru</h4>
                            <p>Dashboard menampilkan ringkasan jumlah siswa, kegiatan yang perlu divalidasi, dan statistik kelas secara keseluruhan.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Validasi Kegiatan Siswa</h4>
                            <p>Buka menu <strong>"Validasi"</strong> untuk melihat daftar kegiatan siswa yang menunggu validasi. Klik <strong>"Setujui"</strong> atau <strong>"Tolak"</strong> dengan memberikan alasan.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Grafik & Laporan</h4>
                            <p>Akses menu <strong>"Grafik Bulanan"</strong> dan <strong>"Grafik Semester"</strong> untuk melihat tren perkembangan kelas. Cetak laporan untuk dokumentasi.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <h4>Cetak Laporan Siswa</h4>
                            <p>Pilih siswa tertentu dan klik <strong>"Cetak Laporan"</strong> untuk menghasilkan rapor digital yang bisa dibagikan ke orang tua.</p>
                        </div>
                    </div>
                </div>

                <div class="info-box">
                    <h4> Tips untuk Guru</h4>
                    <ul>
                        <li>Validasi kegiatan siswa secara berkala (minimal 1x seminggu)</li>
                        <li>Berikan feedback konstruktif saat menolak kegiatan</li>
                        <li>Gunakan grafik untuk identifikasi siswa yang perlu perhatian khusus</li>
                        <li>Cetak laporan di akhir semester untuk arsip</li>
                    </ul>
                </div>
            </div>

            <!-- Tab FAQ -->
            <div id="faq" class="tab-content">
                <h3 style="color: #0284c7; margin-bottom: 15px; font-size: 20px;">Pertanyaan yang Sering Diajukan</h3>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Bagaimana cara login pertama kali?</span>
                        <span>▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Untuk siswa: gunakan NISN sebagai username dan password. Untuk orang tua: gunakan format ORT+NISN. Untuk guru: gunakan NIP. Setelah login pertama, segera ganti password kamu.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Lupa password, apa yang harus dilakukan?</span>
                        <span>▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Hubungi guru kelas atau admin sekolah untuk mereset password kamu. Admin dapat mereset password melalui panel admin.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Apa itu 7 Kebiasaan Anak Indonesia Hebat?</span>
                        <span>▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>7 KAIH adalah: (1) Bangun Pagi, (2) Beribadah Tepat Waktu, (3) Berolahraga Rutin, (4) Makan Sehat & Bergizi, (5) Gemar Belajar, (6) Aktif Bermasyarakat, (7) Tidur Tepat Waktu.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Bagaimana cara upload foto kegiatan?</span>
                        <span>▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Saat mencatat kegiatan, klik tombol "Upload Foto", pilih foto dari galeri atau kamera, lalu klik "Simpan". Pastikan foto jelas dan relevan dengan kegiatan.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Apakah sistem bisa diakses dari HP?</span>
                        <span>▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Ya! Sistem KAIH bersifat responsif dan bisa diakses dari smartphone, tablet, maupun komputer. Buka mercusuar.xyz dari browser HP kamu.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Berapa lama kegiatan harus divalidasi?</span>
                        <span>▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Orang tua diharapkan memvalidasi kegiatan dalam waktu 1-3 hari setelah kegiatan dicatat. Guru akan melakukan validasi akhir secara berkala.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Bagaimana cara mencetak rapor digital?</span>
                        <span>▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Guru dan orang tua dapat mencetak rapor digital melalui menu "Cetak Laporan" di dashboard. Rapor akan di-generate dalam format PDF yang siap dicetak.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kontak Bantuan -->
    <div class="section">
        <h2 class="section-title"> Butuh Bantuan?</h2>
        <p class="text-content">Jika kamu mengalami kendala dalam menggunakan sistem KAIH, jangan ragu untuk menghubungi kami melalui:</p>
        <div class="info-box">
            <h4> Email</h4>
            <p>smpn28balikpapan@gmail.com</p>
        </div>
        <div class="info-box">
            <h4>📱 WhatsApp</h4>
            <p>Hubungi admin sekolah melalui WhatsApp untuk bantuan cepat.</p>
        </div>
        <div class="info-box">
            <h4>🏫 Langsung ke Sekolah</h4>
            <p>Kunjungi ruang guru SMP Negeri 28 Balikpapan pada jam layanan (Senin-Jumat, 07.00-12.00 WITA).</p>
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

<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    event.target.classList.add('active');
}

function toggleFaq(element) {
    const item = element.parentElement;
    item.classList.toggle('open');
}
</script>
</body>
</html>
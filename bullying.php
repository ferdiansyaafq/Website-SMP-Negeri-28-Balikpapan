<?php
$page_title = "Ruang Peduli - Layanan Pengaduan & Perlindungan Siswa | SMP Negeri 28 Balikpapan";
include 'header.php';
?>

<link rel="stylesheet" href="assets/css/bullying.css">

<div class="page-header">
    <h1>🛡️ Ruang Peduli</h1>
    <p>Layanan Pengaduan & Perlindungan Siswa SMP Negeri 28 Balikpapan. Bersama kita wujudkan lingkungan sekolah yang aman, inklusif, dan bebas perundungan.</p>
</div>

<div class="container">

    <!-- Jaminan Kerahasiaan Sesuai Permintaan -->
    <div class="confidentiality-banner">
        <div class="confidentiality-icon">🔒</div>
        <div class="confidentiality-text">
            <h3>Jaminan Kerahasiaan 100% Terlindungi</h3>
            <p><strong>Kami jamin menjaga kerahasiaan identitas dan laporan Anda.</strong> Seluruh informasi yang masuk hanya diakses langsung oleh Satgas Pencegahan & Penanganan Kekerasan (TPPK) / Guru BK untuk perlindungan dan penanganan terbaik.</p>
        </div>
    </div>

    <!-- Kontak PIC (Person In Charge) - Nomor Sementara / Dummy yang Bisa Diubah Kapan Saja -->
    <div class="pic-section">
        <div class="pic-section-header">
            <div>
                <h3 class="pic-section-title">📞 Kontak Layanan Cepat (PIC / Tim Pendamping)</h3>
                <p class="pic-section-subtitle">Jika Anda atau rekan Anda membutuhkan bantuan segera atau pendampingan darurat:</p>
            </div>
        </div>

        <div class="pic-grid">
            <!-- PIC Kontak Tunggal: Tim Satgas TPPK & BK -->
            <div class="pic-card" style="max-width: 500px; margin: 0 auto; width: 100%;">
                <div>
                    <span class="pic-role-badge">Tim Satgas TPPK & Guru BK</span>
                    <h4 class="pic-name">Ibu Siti Rahmawati, S.Pd</h4>
                    <p class="pic-title">Koordinator Layanan Konseling & Tim Satgas Pencegahan Kekerasan</p>
                </div>
                <div class="pic-contact-action">
                    <a href="https://wa.me/6281234567890?text=Halo%20Ibu%20Siti%2C%20saya%20butuh%20konseling%20atau%20ingin%20berbicara%20terkait%20situasi%20di%20sekolah." target="_blank" rel="noopener noreferrer" class="btn-contact btn-wa">
                        💬 WhatsApp (0812-3456-7890)
                    </a>
                    <a href="tel:081234567890" class="btn-contact btn-phone">
                        📞 Telepon
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Pengaduan Ruang Peduli -->
    <div class="bullying-form">
        <h2 class="form-title">📝 Formulir Pengaduan Ruang Peduli</h2>
        <p class="form-subtitle">Sampaikan kejadian yang Anda alami atau ketahui. Isi data selengkap mungkin agar tim kami dapat bertindak tepat sasaran.</p>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert alert-success">
                ✅ Laporan Anda telah kami terima dengan aman. Tim Satgas / Guru BK akan segera menindaklanjuti dengan kerahasiaan penuh.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
            <div class="alert alert-error">
                ❌ Gagal mengirim laporan. Pastikan seluruh kolom wajib telah diisi atau hubungi nomor PIC di atas secara langsung.
            </div>
        <?php endif; ?>

        <form action="proses_bullying.php" method="POST">

            <div class="form-group">
                <label>Nama Pelapor <span class="required">*</span></label>
                <input type="text" name="nama_pelapor" required placeholder="Masukkan nama Anda (atau ketik 'Anonim' jika ingin merahasiakan nama)">
                <span class="hint">Anda berhak menulis "Anonim" bila merasa lebih nyaman.</span>
            </div>

            <div class="form-group">
                <label>Nomor HP / WhatsApp / Email Pelapor <span class="required">*</span></label>
                <input type="text" name="kontak" required placeholder="Contoh: 0812xxxxxxxx atau email@domain.com">
                <span class="hint">Kontak ini dijaga kerahasiaannya dan hanya dipakai tim sekolah untuk konfirmasi dan update penanganan.</span>
            </div>

            <div class="form-group">
                <label>Agenda Kunjungan <span class="required">*</span></label>
                <textarea name="status_pelapor" rows="3" required placeholder="Tuliskan tujuan atau agenda kunjungan Anda ke sekolah..."></textarea>
                <span class="hint">Jelaskan maksud kedatangan atau agenda kunjungan Anda secara jelas.</span>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Nama Siswa yang Mengalami (Korban) <span class="required">*</span></label>
                    <input type="text" name="nama_korban" required placeholder="Nama siswa korban">
                </div>
                <div class="form-group">
                    <label>Kelas Korban <span class="required">*</span></label>
                    <input type="text" name="kelas_korban" required placeholder="Contoh: 7A, 8B, atau 9C">
                </div>
            </div>

            <div class="form-group">
                <label>Bentuk Perundungan / Masalah <span class="required">*</span></label>
                <select name="jenis_bullying" required>
                    <option value="">-- Pilih bentuk kejadian --</option>
                    <option value="Fisik">Fisik (Pemukulan, dorongan, penendangan, pemalakan, dll.)</option>
                    <option value="Verbal">Verbal (Ejekan nama orang tua, hinaan fisik, ancaman, fitnah)</option>
                    <option value="Sosial">Relasional / Sosial (Pengucilan, penyebaran rumor, hasutan)</option>
                    <option value="Cyber">Cyberbullying (Pelecehan online di media sosial / grup WhatsApp)</option>
                    <option value="Seksual">Pelecehan Seksual / Perilaku Tidak Pantas</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Perkiraan Tanggal Kejadian <span class="required">*</span></label>
                    <input type="date" name="tanggal_kejadian" required>
                </div>
                <div class="form-group">
                    <label>Lokasi Kejadian <span class="required">*</span></label>
                    <input type="text" name="lokasi" required placeholder="Contoh: Kantin, Lapangan Olahraga, Grup WhatsApp, dll.">
                </div>
            </div>

            <div class="form-group">
                <label>Saksi yang Melihat (Opsional)</label>
                <input type="text" name="saksi" placeholder="Tuliskan nama teman, guru, atau saksi lain jika ada">
            </div>

            <div class="form-group">
                <label>Deskripsi Kejadian Secara Rinci <span class="required">*</span></label>
                <textarea name="deskripsi" rows="5" required placeholder="Ceritakan kronologi singkat: apa yang terjadi, siapa pelakunya (jika diketahui), dan dampak yang dirasakan..."></textarea>
                <span class="hint">Semua rincian yang Anda berikan sangat berarti bagi perlindungan dan solusi yang adil.</span>
            </div>

            <button type="submit" class="btn-submit">
                🛡️ Kirim Laporan ke Ruang Peduli
            </button>

        </form>

    </div>

</div>

<script src="assets/js/bullying.js"></script>

<?php include 'footer.php'; ?>
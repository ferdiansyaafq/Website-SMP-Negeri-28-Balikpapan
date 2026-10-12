<?php
$page_title = "Ruang Peduli - Layanan Pengaduan & Perlindungan Siswa | SMP Negeri 28 Balikpapan";
include 'header.php';
?>

<style>
.page-header {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: white;
    padding: 60px 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.page-header::before {
    content: '🛡️';
    position: absolute;
    right: 40px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 120px;
    opacity: 0.08;
}
.page-header h1 {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 10px;
    position: relative;
    z-index: 1;
}
.page-header p {
    font-size: 16px;
    opacity: 0.9;
    position: relative;
    z-index: 1;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
}

.container {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 40px;
}

/* Confidentiality Banner */
.confidentiality-banner {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    padding: 20px 24px;
    border-radius: 16px;
    margin-bottom: 24px;
    border: 1px solid #bbf7d0;
}
.confidentiality-icon {
    font-size: 36px;
    flex-shrink: 0;
}
.confidentiality-text h3 {
    color: #166534;
    font-size: 17px;
    font-weight: 800;
    margin-bottom: 6px;
}
.confidentiality-text p {
    color: #166534;
    font-size: 13.5px;
    line-height: 1.6;
    margin: 0;
}

/* PIC Section */
.pic-section {
    background: #fef2f2;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid #fecaca;
}
.pic-section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 18px;
}
.pic-section-title {
    color: #991b1b;
    font-size: 17px;
    font-weight: 800;
    margin-bottom: 6px;
}
.pic-section-subtitle {
    color: #991b1b;
    font-size: 13.5px;
    margin: 0;
}
.pic-grid {
    display: flex;
    justify-content: center;
}
.pic-card {
    background: white;
    padding: 20px;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(220,38,38,0.1);
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.pic-role-badge {
    display: inline-block;
    background: #fee2e2;
    color: #991b1b;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.pic-name {
    font-size: 18px;
    color: #1e293b;
    margin-bottom: 4px;
    font-weight: 800;
}
.pic-title {
    color: #64748b;
    font-size: 13px;
    margin: 0;
}
.pic-contact-action {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.btn-contact {
    flex: 1;
    min-width: 140px;
    padding: 12px 16px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s;
}
.btn-wa {
    background: #25D366;
    color: white;
}
.btn-wa:hover {
    background: #1eb955;
    transform: translateY(-2px);
}
.btn-phone {
    background: #dc2626;
    color: white;
}
.btn-phone:hover {
    background: #b91c1c;
    transform: translateY(-2px);
}

/* Form */
.bullying-form {
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}

.bullying-form .form-title {
    font-size: 24px;
    color: #1e293b;
    margin-bottom: 8px;
    font-weight: 800;
}

.bullying-form .form-subtitle {
    color: #64748b;
    font-size: 15px;
    margin-bottom: 25px;
}

.bullying-form .form-group {
    margin-bottom: 20px;
}

.bullying-form .form-group label {
    font-weight: 600;
    color: #1e293b;
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
}

.bullying-form .form-group label .required {
    color: #dc2626;
    margin-left: 2px;
}

.bullying-form .form-group input,
.bullying-form .form-group select,
.bullying-form .form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    outline: none;
    transition: all 0.3s;
    background-color: #fafafa;
    font-family: inherit;
    font-size: 14px;
    color: #1e293b;
}

.bullying-form .form-group input:focus,
.bullying-form .form-group select:focus,
.bullying-form .form-group textarea:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    background-color: white;
}

.bullying-form .form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.bullying-form .form-group .hint {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 4px;
    display: block;
}

.bullying-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Alert */
.alert {
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Submit Button */
.btn-submit {
    background: #dc2626;
    color: white;
    border: none;
    padding: 16px 24px;
    width: 100%;
    border-radius: 14px;
    font-weight: 700;
    font-size: 17px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(220, 38, 38, 0.3);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(220, 38, 38, 0.4);
    background: #b91c1c;
}

.btn-submit:active {
    transform: translateY(0);
}

/* Responsive */
@media (max-width: 768px) {
    .page-header { padding: 40px 20px; }
    .page-header h1 { font-size: 26px; }
    .container { padding: 0 20px; margin: 20px auto; }
    .bullying-form { padding: 24px; }
    .bullying-form .form-row { grid-template-columns: 1fr; gap: 0; }
    .confidentiality-banner { flex-direction: column; align-items: center; text-align: center; }
    .pic-section { padding: 20px 16px; }
    .pic-contact-action { flex-direction: column; }
    .btn-contact { min-width: 100%; }
}
</style>

<div class="page-header">
    <h1>🛡️ Ruang Peduli</h1>
    <p>Layanan Pengaduan &amp; Perlindungan Siswa SMP Negeri 28 Balikpapan. Bersama kita wujudkan lingkungan sekolah yang aman, inklusif, dan bebas perundungan.</p>
</div>

<div class="container">

    <!-- Jaminan Kerahasiaan -->
    <div class="confidentiality-banner">
        <div class="confidentiality-icon">🔒</div>
        <div class="confidentiality-text">
            <h3>Jaminan Kerahasiaan 100% Terlindungi</h3>
            <p><strong>Kami jamin menjaga kerahasiaan identitas dan laporan Anda.</strong> Seluruh informasi yang masuk hanya diakses langsung oleh Satgas Pencegahan &amp; Penanganan Kekerasan (TPPK) / Guru BK untuk perlindungan dan penanganan terbaik.</p>
        </div>
    </div>

    <!-- Kontak PIC -->
    <div class="pic-section">
        <div class="pic-section-header">
            <div>
                <h3 class="pic-section-title">📞 Kontak Layanan Cepat (PIC / Tim Pendamping)</h3>
                <p class="pic-section-subtitle">Jika Anda atau rekan Anda membutuhkan bantuan segera atau pendampingan darurat:</p>
            </div>
        </div>

        <div class="pic-grid">
            <div class="pic-card" style="max-width: 500px; margin: 0 auto; width: 100%;">
                <div>
                    <span class="pic-role-badge">Tim Satgas TPPK &amp; Guru BK</span>
                    <h4 class="pic-name">Ibu Siti Rahmawati, S.Pd</h4>
                    <p class="pic-title">Koordinator Layanan Konseling &amp; Tim Satgas Pencegahan Kekerasan</p>
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

    <!-- Form Pengaduan -->
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

            <!-- Nama Pelapor -->
            <div class="form-group">
                <label>Nama Pelapor <span class="required">*</span></label>
                <input type="text" name="nama_pelapor" required placeholder="Masukkan nama Anda (atau ketik 'Anonim' jika ingin merahasiakan nama)">
                <span class="hint">Anda berhak menulis "Anonim" bila merasa lebih nyaman.</span>
            </div>

            <!-- Kontak -->
            <div class="form-group">
                <label>Nomor HP / WhatsApp / Email Pelapor <span class="required">*</span></label>
                <input type="text" name="kontak" required placeholder="Contoh: 0812xxxxxxxx atau email@domain.com">
                <span class="hint">Kontak ini dijaga kerahasiaannya dan hanya dipakai tim sekolah untuk konfirmasi dan update penanganan.</span>
            </div>

            <!-- Status Pelapor -->
            <div class="form-group">
                <label>Status Pelapor <span class="required">*</span></label>
                <select name="status_pelapor" required>
                    <option value="">Pilih status Anda...</option>
                    <option value="Siswa">Siswa</option>
                    <option value="Orang Tua">Orang Tua / Wali Murid</option>
                    <option value="Guru">Guru</option>
                    <option value="Masyarakat">Masyarakat Umum</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <!-- Nama & Kelas Korban -->
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

            <!-- Jenis Bullying -->
            <div class="form-group">
                <label>Bentuk Perundungan / Masalah <span class="required">*</span></label>
                <select name="jenis_bullying" id="selectJenis" required onchange="toggleLainnya(this)">
                    <option value="">-- Pilih bentuk kejadian --</option>
                    <option value="Fisik">Fisik (Pemukulan, dorongan, penendangan, pemalakan, dll.)</option>
                    <option value="Verbal">Verbal (Ejekan nama orang tua, hinaan fisik, ancaman, fitnah)</option>
                    <option value="Sosial">Relasional / Sosial (Pengucilan, penyebaran rumor, hasutan)</option>
                    <option value="Cyber">Cyberbullying (Pelecehan online di media sosial / grup WhatsApp)</option>
                    <option value="Seksual">Pelecehan Seksual / Perilaku Tidak Pantas</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <!-- Keterangan Tambahan jika memilih Lainnya -->
            <div class="form-group" id="inputLainnya" style="display: none;">
                <label>Keterangan Jenis Lainnya <span class="required">*</span></label>
                <input type="text" name="jenis_bullying_lainnya" placeholder="Jelaskan bentuk bullying lainnya...">
            </div>

            <!-- Tanggal & Lokasi -->
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

            <!-- Saksi -->
            <div class="form-group">
                <label>Saksi yang Melihat (Opsional)</label>
                <input type="text" name="saksi" placeholder="Tuliskan nama teman, guru, atau saksi lain jika ada">
            </div>

            <!-- Deskripsi -->
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

<script>
function toggleLainnya(select) {
    const inputLainnya = document.getElementById('inputLainnya');
    if (select.value === 'Lainnya') {
        inputLainnya.style.display = 'block';
    } else {
        inputLainnya.style.display = 'none';
    }
}
</script>

<?php include 'footer.php'; ?>
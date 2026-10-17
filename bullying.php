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
    box-sizing: border-box;
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

/* ============================================================
   PIC SECTION - ESTETIK MINIMALIS
   ============================================================ */
.pic-elegant {
    margin-top: 30px;
    padding-top: 25px;
    border-top: 2px dashed #e2e8f0;
}

.pic-elegant-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 800;
    color: #991b1b;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 14px;
}

.pic-elegant-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, #fecaca, transparent);
}

.pic-elegant-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 18px;
    background: linear-gradient(135deg, #ffffff, #fef2f2);
    border: 1.5px solid #fecaca;
    border-radius: 16px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.04);
}

.pic-elegant-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(220, 38, 38, 0.12);
    border-color: #fca5a5;
}

.pic-elegant-avatar {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 800;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
    letter-spacing: -0.5px;
}

.pic-elegant-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.pic-elegant-name {
    font-size: 15px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pic-elegant-role {
    font-size: 12px;
    color: #991b1b;
    font-weight: 600;
    line-height: 1.3;
}

.pic-elegant-wa {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 18px;
    background: linear-gradient(135deg, #25D366, #1eb955);
    color: white;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 700;
    font-size: 13px;
    transition: all 0.3s ease;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
    white-space: nowrap;
}

.pic-elegant-wa:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37, 211, 102, 0.45);
    filter: brightness(1.05);
}

.pic-elegant-wa svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.pic-elegant-wa .wa-text {
    font-size: 13px;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header { padding: 40px 20px; }
    .page-header h1 { font-size: 26px; }
    .page-header::before { font-size: 60px; right: 15px; }
    .container { padding: 0 20px; margin: 20px auto; }
    .bullying-form { padding: 24px; }
    .bullying-form .form-row { grid-template-columns: 1fr; gap: 0; }
    .confidentiality-banner { flex-direction: column; align-items: center; text-align: center; }
}

@media (max-width: 600px) {
    .pic-elegant-card {
        flex-wrap: wrap;
        gap: 12px;
    }
    .pic-elegant-info {
        flex: 1;
        min-width: calc(100% - 60px);
    }
    .pic-elegant-wa {
        width: 100%;
        justify-content: center;
        padding: 11px 16px;
    }
    .pic-elegant-name {
        white-space: normal;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .bullying-form { padding: 18px; }
    .bullying-form .form-title { font-size: 20px; }
    .btn-submit { font-size: 15px; padding: 14px 18px; }
    .pic-elegant-avatar {
        width: 40px;
        height: 40px;
        font-size: 17px;
    }
    .pic-elegant-name {
        font-size: 13px;
    }
    .pic-elegant-role {
        font-size: 11px;
    }
    .pic-elegant-wa {
        font-size: 12px;
        padding: 10px 14px;
    }
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
            <p><strong>Kami jamin menjaga kerahasiaan identitas dan laporan Anda.</strong> Seluruh informasi yang masuk hanya diakses langsung oleh Guru BK untuk perlindungan dan penanganan terbaik.</p>
        </div>
    </div>

    <!-- Form Pengaduan -->
    <div class="bullying-form">
        <h2 class="form-title">📝 Formulir Pengaduan Ruang Peduli</h2>
        <p class="form-subtitle">Sampaikan kejadian yang Anda alami atau ketahui. Isi data selengkap mungkin agar tim kami dapat bertindak tepat sasaran.</p>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert alert-success">
                ✅ Laporan Anda telah kami terima dengan aman. Guru BK akan segera menindaklanjuti dengan kerahasiaan penuh.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
            <div class="alert alert-error">
                ❌ Gagal mengirim laporan. Pastikan seluruh kolom wajib telah diisi atau hubungi nomor PIC di bawah secara langsung.
            </div>
        <?php endif; ?>

        <form action="proses_bullying.php" method="POST">

            <div class="form-group">
                <label>Nama Pelapor <span class="required">*</span></label>
                <input type="text" name="nama_pelapor" required placeholder="Masukkan nama Anda (atau ketik 'Anonim' jika ingin merahasiakan nama)">
                <span class="hint">Anda berhak menulis "Anonim" bila ingin merasa lebih nyaman.</span>
            </div>

            <div class="form-group">
                <label>Nomor HP / WhatsApp / Email Pelapor <span class="required">*</span></label>
                <input type="text" name="kontak" required placeholder="Contoh: 0812xxxxxxxx atau email@domain.com">
                <span class="hint">Kontak ini dijaga kerahasiaannya dan hanya dipakai tim sekolah untuk konfirmasi dan update penanganan.</span>
            </div>

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

            <div class="form-row">
                <div class="form-group">
                    <label>Nama Korban <span class="required">*</span></label>
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
                    <label>Tanggal Kejadian <span class="required">*</span></label>
                    <input type="date" name="tanggal_kejadian" required>
                </div>
                <div class="form-group">
                    <label>Lokasi Kejadian <span class="required">*</span></label>
                    <input type="text" name="lokasi" required placeholder="Contoh: Kantin, Lapangan Olahraga, Grup WhatsApp, dll.">
                </div>
            </div>

            <!-- Saksi -->
            <div class="form-group">
                <label>Saksi (Opsional)</label>
                <input type="text" name="saksi" placeholder="Tuliskan nama teman, guru, atau saksi lain jika ada">
            </div>

            <!-- Deskripsi -->
            <div class="form-group">
                <label>Deskripsi Kejadian<span class="required">*</span></label>
                <textarea name="deskripsi" rows="5" required placeholder="Ceritakan kronologi singkat: apa yang terjadi, siapa pelakunya (jika diketahui), dan dampak yang dirasakan..."></textarea>
            </div>

            <button type="submit" class="btn-submit">
                Kirim Laporan
            </button>
        </form>

        <div class="pic-elegant">
            <div class="pic-elegant-label">📞 Kontak Layanan Cepat (PIC)</div>

            <div class="pic-elegant-card">
                <div class="pic-elegant-avatar">AB</div>
                <div class="pic-elegant-info">
                    <div class="pic-elegant-name">Bapak Aris Broto, S.Pd</div>
                    <div class="pic-elegant-role">Kepala Sekolah</div>
                </div>
                <a href="https://wa.me/6281256781945" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="pic-elegant-wa"
                   aria-label="Hubungi via WhatsApp">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    <span class="wa-text">WhatsApp</span>
                </a>
            </div>
        </div>

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
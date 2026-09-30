<?php
$page_title = "Pelaporan Bullying - SMP Negeri 28 Balikpapan";
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
}

.container {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 40px;
}

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

/* Trust Badge */
.trust-badge {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    background: #f0fdf4;
    padding: 16px 20px;
    border-radius: 14px;
    margin-bottom: 24px;
    border: 1px solid #bbf7d0;
}

.trust-badge .icon {
    font-size: 28px;
    flex-shrink: 0;
    margin-top: 2px;
}

.trust-badge .text {
    font-size: 14px;
    color: #166534;
    font-weight: 500;
    line-height: 1.6;
}

.trust-badge .text strong {
    font-weight: 700;
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

/* Contact Emergency */
.emergency-contact {
    margin-top: 20px;
    padding: 16px 20px;
    background: #fef2f2;
    border-radius: 12px;
    border: 1px solid #fecaca;
    text-align: center;
}

.emergency-contact p {
    margin: 0;
    font-size: 14px;
    color: #991b1b;
    font-weight: 500;
}

.emergency-contact a {
    color: #dc2626;
    font-weight: 700;
    text-decoration: none;
}

.emergency-contact a:hover {
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        padding: 40px 20px;
    }
    .page-header h1 {
        font-size: 26px;
    }
    .page-header::before {
        font-size: 60px;
        right: 15px;
    }
    .container {
        padding: 0 20px;
        margin: 20px auto;
    }
    .bullying-form {
        padding: 24px;
    }
    .bullying-form .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}

@media (max-width: 480px) {
    .bullying-form {
        padding: 16px;
    }
    .bullying-form .form-group input,
    .bullying-form .form-group select,
    .bullying-form .form-group textarea {
        padding: 10px 12px;
        font-size: 13px;
    }
    .btn-submit {
        padding: 14px 18px;
        font-size: 15px;
    }
    .trust-badge {
        padding: 12px 14px;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .trust-badge .icon {
        font-size: 32px;
    }
    .bullying-form .form-title {
        font-size: 20px;
    }
    .emergency-contact p {
        font-size: 13px;
    }
}
</style>

<div class="page-header">
    <h1>🛡️ Pelaporan Bullying</h1>
    <p>Laporkan perundungan dengan aman dan rahasia</p>
</div>

<div class="container">

    <div class="bullying-form">
        <h2 class="form-title">Form Laporan Perundungan</h2>
        <p class="form-subtitle">Isi data dengan lengkap agar kami bisa segera menindaklanjuti laporan Anda.</p>

        <div class="trust-badge">
            <span class="icon">🔒</span>
            <div class="text">
                <strong>Laporan Anda aman dan rahasia.</strong> 
                Identitas pelapor akan dilindungi sesuai dengan kebijakan perlindungan anak. 
                Semua data hanya akan diakses oleh tim penanganan khusus sekolah.
            </div>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert alert-success">
                ✅ Laporan bullying berhasil dikirim! Tim kami akan segera menindaklanjuti dan menghubungi Anda dalam waktu 2x24 jam.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
            <div class="alert alert-error">
                ❌ Gagal mengirim laporan. Silakan coba lagi atau hubungi pihak sekolah langsung di nomor darurat di bawah.
            </div>
        <?php endif; ?>

        <form action="proses_bullying.php" method="POST">

            <div class="form-group">
                <label>Nama Pelapor <span class="required">*</span></label>
                <input type="text" name="nama_pelapor" required placeholder="Masukkan nama Anda (bisa anonim jika diisi dengan 'Anonim')">
                <span class="hint">Anda bisa menulis "Anonim" jika ingin melaporkan secara rahasia.</span>
            </div>

            <div class="form-group">
                <label>Email atau No HP <span class="required">*</span></label>
                <input type="text" name="kontak" required placeholder="Masukkan email atau nomor HP untuk konfirmasi">
                <span class="hint">Kami akan menghubungi Anda untuk konfirmasi laporan dan perkembangan penanganan.</span>
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
        <input type="text" name="nama_korban" required placeholder="Nama siswa yang menjadi korban">
    </div>
    <div class="form-group">
        <label>Kelas Korban <span class="required">*</span></label>
        <input type="text" name="kelas_korban" required placeholder="Contoh: 7A, 8B, 9C">
    </div>
</div>

<div class="form-group">
    <label>Jenis Bullying <span class="required">*</span></label>
    <select name="jenis_bullying" required>
        <option value="">Pilih jenis bullying...</option>
        <option value="Fisik">Fisik (memukul, menendang, mendorong, dll.)</option>
        <option value="Verbal">Verbal (mengejek, menghina, mengancam, dll.)</option>
        <option value="Sosial">Sosial (mengucilkan, menyebarkan gossip, dll.)</option>
        <option value="Cyber">Cyber Bullying (media sosial, pesan, dll.)</option>
        <option value="Seksual">Pelecehan Seksual</option>
        <option value="Lainnya">Lainnya</option>
    </select>
</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tanggal Kejadian <span class="required">*</span></label>
                    <input type="date" name="tanggal_kejadian" required>
                </div>
                <div class="form-group">
                    <label>Lokasi Kejadian <span class="required">*</span></label>
                    <input type="text" name="lokasi" required placeholder="Contoh: Halaman sekolah, Kelas 7A, Kantin, dll.">
                </div>
            </div>

            <div class="form-group">
                <label>Deskripsi Kejadian <span class="required">*</span></label>
                <textarea name="deskripsi" rows="5" required placeholder="Ceritakan secara detail kejadian yang dialami atau dilihat..."></textarea>
                <span class="hint">Semakin detail laporan Anda, semakin cepat kami bisa menindaklanjutinya.</span>
            </div>

            <button type="submit" class="btn-submit">
                🛡️ Kirim Laporan Bullying
            </button>

        </form>

        <div class="emergency-contact">
            <p>
                📞 <strong>Butuh bantuan segera?</strong> Hubungi layanan bantuan: 
                <a href="tel:08123456789">0812-3456-7890</a> 
                (Konseling Sekolah)
            </p>
        </div>

    </div>

</div>

<?php include 'footer.php'; ?>
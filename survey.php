<?php
$page_title = "Survei Pelayanan - SMP Negeri 28 Balikpapan";
include 'header.php';
require_once 'config/database.php';

$pesan = '';
$tipe_pesan = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $ulasan = trim($_POST['ulasan'] ?? '');

    if ($nama === '' || $status === '' || $rating === 0 || $ulasan === '') {
        $pesan = 'Mohon lengkapi semua field terlebih dahulu.';
        $tipe_pesan = 'error';
    } else {
        try {
            $sql = "INSERT INTO survei (nama_pengisi, peran, rating, ulasan, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama, $status, $rating, $ulasan]);
            $pesan = 'Terima kasih! Survei Anda berhasil dikirim.';
        } catch (PDOException $e) {
            $pesan = 'Gagal menyimpan data: ' . $e->getMessage();
            $tipe_pesan = 'error';
        }
    }
}
?>
<style>
/* PAGE HEADER - SAMA SEPERTI BULLYING */
.page-header {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: white;
    padding: 60px 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.page-header::before {
    content: '📝';
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

/* CONTAINER */
.container {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 40px;
}

.survey-form {
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}
.survey-form .form-title {
    font-size: 24px;
    color: #1e293b;
    margin-bottom: 8px;
    font-weight: 800;
}
.survey-form .form-subtitle {
    color: #64748b;
    font-size: 15px;
    margin-bottom: 25px;
}

/* FORM GROUP */
.survey-form .form-group {
    margin-bottom: 20px;
}
.survey-form .form-group label {
    font-weight: 600;
    color: #1e293b;
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
}
.survey-form .form-group label .required {
    color: #dc2626;
    margin-left: 2px;
}
.survey-form .form-group input,
.survey-form .form-group select,
.survey-form .form-group textarea {
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
.survey-form .form-group input:focus,
.survey-form .form-group select:focus,
.survey-form .form-group textarea:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
    background-color: white;
}
.survey-form .form-group textarea {
    resize: vertical;
    min-height: 100px;
}
.survey-form .form-group .hint {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 4px;
    display: block;
}

/* TRUST BADGE */
.trust-badge {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    background: #f0f9ff;
    padding: 16px 20px;
    border-radius: 14px;
    margin-bottom: 24px;
    border: 1px solid #bae6fd;
}
.trust-badge .icon {
    font-size: 28px;
    flex-shrink: 0;
    margin-top: 2px;
}
.trust-badge .text {
    font-size: 14px;
    color: #075985;
    font-weight: 500;
    line-height: 1.6;
}
.trust-badge .text strong {
    font-weight: 700;
}

/* STAR RATING */
.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}
.star-rating input { display: none; }
.star-rating label {
    font-size: 40px;
    color: #d1d5db;
    cursor: pointer;
    transition: color 0.2s ease-in-out;
    margin: 0;
}
.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #fbbf24;
}

/* BUTTON SUBMIT */
.btn-submit {
    background: #0284c7;
    color: white;
    border: none;
    padding: 16px 24px;
    width: 100%;
    border-radius: 14px;
    font-weight: 700;
    font-size: 17px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(2, 132, 199, 0.3);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(2, 132, 199, 0.4);
    background: #0369a1;
}
.btn-submit:active { transform: translateY(0); }

/* ALERT */
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

/* BACK BUTTON */
.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 20px;
    color: #64748b;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-back:hover {
    color: #0284c7;
    transform: translateX(-3px);
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .page-header { padding: 40px 20px; }
    .page-header h1 { font-size: 26px; }
    .page-header::before { font-size: 60px; right: 15px; }
    .container { padding: 0 20px; margin: 20px auto; }
    .survey-form { padding: 24px; }
    .star-rating label { font-size: 34px; }
}
@media (max-width: 480px) {
    .survey-form { padding: 16px; }
    .survey-form .form-group input,
    .survey-form .form-group select,
    .survey-form .form-group textarea {
        padding: 10px 12px;
        font-size: 13px;
    }
    .btn-submit { padding: 14px 18px; font-size: 15px; }
    .trust-badge {
        padding: 12px 14px;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .trust-badge .icon { font-size: 32px; }
    .survey-form .form-title { font-size: 20px; }
    .star-rating label { font-size: 32px; }
}
</style>
<!-- PAGE HEADER -->
<div class="page-header">
    <h1>📝 Survei Pelayanan</h1>
    <p>Bantu kami meningkatkan kualitas layanan pendidikan</p>
</div>

<!-- CONTAINER -->
<div class="container">

    <a href="lainnya.php" class="btn-back">← Kembali ke FAQ</a>

    <div class="survey-form">
        <h2 class="form-title">Form Survei Pelayanan</h2>
        <p class="form-subtitle">Kami siap melayani dan menerima masukan dari Anda demi peningkatan kualitas sekolah.</p>

        <!-- Notifikasi -->
        <?php if ($pesan): ?>
            <div class="alert alert-<?= $tipe_pesan ?>">
                <?= $tipe_pesan === 'success' ? '✅' : '❌' ?> 
                <?= htmlspecialchars($pesan) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Nama -->
            <div class="form-group">
                <label>Nama<span class="required">*</span></label>
                <input type="text" name="nama" required placeholder="Masukkan nama Anda..." value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
            </div>

            <!-- Status -->
            <div class="form-group">
                <label>Status Pengguna <span class="required">*</span></label>
                <select name="status" required>
                    <option value="" disabled <?= empty($_POST['status']) ? 'selected' : '' ?>>-- Pilih status Anda --</option>
                    <option value="Siswa" <?= ($_POST['status'] ?? '') === 'Siswa' ? 'selected' : '' ?>>Siswa</option>
                    <option value="Orang Tua" <?= ($_POST['status'] ?? '') === 'Orang Tua' ? 'selected' : '' ?>>Orang Tua / Wali Murid</option>
                    <option value="Guru" <?= ($_POST['status'] ?? '') === 'Guru' ? 'selected' : '' ?>>Guru</option>
                    <option value="Masyarakat" <?= ($_POST['status'] ?? '') === 'Masyarakat' ? 'selected' : '' ?>>Masyarakat Umum</option>
                    <option value="Lainnya" <?= ($_POST['status'] ?? '') === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                </select>
            </div>

            <!-- Rating -->
            <div class="form-group">
                <label>Beri Penilaian (1-5 Bintang) <span class="required">*</span></label>
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" required <?= ($_POST['rating'] ?? '') == 5 ? 'checked' : '' ?> />
                    <label for="star5" title="Sangat Bagus">★</label>

                    <input type="radio" id="star4" name="rating" value="4" <?= ($_POST['rating'] ?? '') == 4 ? 'checked' : '' ?> />
                    <label for="star4" title="Bagus">★</label>

                    <input type="radio" id="star3" name="rating" value="3" <?= ($_POST['rating'] ?? '') == 3 ? 'checked' : '' ?> />
                    <label for="star3" title="Cukup">★</label>

                    <input type="radio" id="star2" name="rating" value="2" <?= ($_POST['rating'] ?? '') == 2 ? 'checked' : '' ?> />
                    <label for="star2" title="Buruk">★</label>

                    <input type="radio" id="star1" name="rating" value="1" <?= ($_POST['rating'] ?? '') == 1 ? 'checked' : '' ?> />
                    <label for="star1" title="Sangat Buruk">★</label>
                </div>
                <span class="hint">Klik bintang untuk memberi penilaian.</span>
            </div>

            <!-- Ulasan -->
            <div class="form-group">
                <label>Ulasan & Saran Peningkatan <span class="required">*</span></label>
                <textarea name="ulasan" rows="5" required placeholder="Ceritakan pengalaman Anda atau saran untuk kami..."><?= htmlspecialchars($_POST['ulasan'] ?? '') ?></textarea>
                <span class="hint">Semakin detail masukan Anda, semakin baik untuk peningkatan layanan.</span>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-submit">
                📩 Kirim Ulasan Sekarang
            </button>
        </form>
    </div>

</div>

<?php include 'footer.php'; ?>
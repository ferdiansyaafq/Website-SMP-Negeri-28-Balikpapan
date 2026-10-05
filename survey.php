<?php
$page_title = "Survei Pelayanan - SMP Negeri 28 Balikpapan";
include 'header.php';
require_once 'config/database.php';

$pesan = '';
$tipe_pesan = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $agenda_kunjungan = trim($_POST['agenda_kunjungan'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $kritik_saran = trim($_POST['kritik_saran'] ?? '');

    if ($nama === '' || $agenda_kunjungan === '' || $rating === 0 || $kritik_saran === '') {
        $pesan = 'Mohon lengkapi semua kolom terlebih dahulu.';
        $tipe_pesan = 'error';
    } else {
        try {
            $sql = "INSERT INTO survei (nama_pengisi, peran, rating, ulasan, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama, $agenda_kunjungan, $rating, $kritik_saran]);
            $pesan = 'Terima kasih banyak! Penilaian, kritik, dan saran Anda berhasil dikirim untuk evaluasi layanan kami.';
            // Reset POST values on success
            $_POST = [];
        } catch (PDOException $e) {
            $pesan = 'Gagal menyimpan data: ' . $e->getMessage();
            $tipe_pesan = 'error';
        }
    }
}
?>

<link rel="stylesheet" href="assets/css/survey.css">

<!-- PAGE HEADER -->
<div class="page-header" id="Survei-Pelayanan">
    <h1>📝 Survei Pelayanan</h1>
    <p>Bantu kami meningkatkan mutu dan kualitas layanan pendidikan di SMP Negeri 28 Balikpapan</p>
</div>

<!-- CONTAINER -->
<div class="container">

    <a href="lainnya.php" class="btn-back">← Kembali ke FAQ / Menu Lainnya</a>

    <div class="survey-form">
        <h2 class="form-title">Formulir Kepuasan Pelayanan</h2>
        <p class="form-subtitle">Masukan Anda sangat berharga bagi kami dalam mewujudkan pelayanan sekolah yang transparan, ramah, dan profesional.</p>

        <!-- Notifikasi -->
        <?php if ($pesan): ?>
            <div class="alert alert-<?= $tipe_pesan ?>">
                <?= $tipe_pesan === 'success' ? '✅' : '❌' ?> 
                <?= htmlspecialchars($pesan) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="survey-form-tag">
            <!-- Nama -->
            <div class="form-group">
                <label>Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="nama" required placeholder="Masukkan nama lengkap Anda..." value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                <span class="hint">Nama Anda akan disimpan dengan aman untuk keperluan dokumentasi survei.</span>
            </div>

            <!-- Agenda Kunjungan (Sebelumnya Status Pengguna) -->
            <div class="form-group">
                <label>Agenda Kunjungan <span class="required">*</span></label>
                <textarea name="agenda_kunjungan" rows="3" required placeholder="Tuliskan tujuan atau agenda kunjungan Anda ke sekolah (contoh: Mengurus surat keterangan, konsultasi akademik siswa, kunjungan dinas, dll.)..."><?= htmlspecialchars($_POST['agenda_kunjungan'] ?? '') ?></textarea>
                <span class="hint">Tuliskan agenda atau keperluan kedatangan Anda secara jelas.</span>
            </div>

            <!-- Penilaian Berdasarkan Emot Kepuasan (1-5) -->
            <div class="form-group">
                <label>Tingkat Kepuasan Pelayanan <span class="required">*</span></label>
                <div class="emoji-rating-wrapper">
                    <div class="emoji-rating-grid">
                        <!-- 1 - Sangat Tidak Puas -->
                        <div class="emoji-rating-item" data-rate="1">
                            <input type="radio" id="emoji1" name="rating" value="1" required <?= (($_POST['rating'] ?? '') == 1) ? 'checked' : '' ?> />
                            <label for="emoji1" class="emoji-rating-label">
                                <span class="emoji-icon">😡</span>
                                <span class="emoji-text">Sangat Tidak Puas</span>
                            </label>
                        </div>

                        <!-- 2 - Tidak Puas -->
                        <div class="emoji-rating-item" data-rate="2">
                            <input type="radio" id="emoji2" name="rating" value="2" <?= (($_POST['rating'] ?? '') == 2) ? 'checked' : '' ?> />
                            <label for="emoji2" class="emoji-rating-label">
                                <span class="emoji-icon">🙁</span>
                                <span class="emoji-text">Tidak Puas</span>
                            </label>
                        </div>

                        <!-- 3 - Cukup -->
                        <div class="emoji-rating-item" data-rate="3">
                            <input type="radio" id="emoji3" name="rating" value="3" <?= (($_POST['rating'] ?? '') == 3) ? 'checked' : '' ?> />
                            <label for="emoji3" class="emoji-rating-label">
                                <span class="emoji-icon">😐</span>
                                <span class="emoji-text">Cukup</span>
                            </label>
                        </div>

                        <!-- 4 - Puas -->
                        <div class="emoji-rating-item" data-rate="4">
                            <input type="radio" id="emoji4" name="rating" value="4" <?= (($_POST['rating'] ?? '') == 4) ? 'checked' : '' ?> />
                            <label for="emoji4" class="emoji-rating-label">
                                <span class="emoji-icon">🙂</span>
                                <span class="emoji-text">Puas</span>
                            </label>
                        </div>

                        <!-- 5 - Sangat Puas -->
                        <div class="emoji-rating-item" data-rate="5">
                            <input type="radio" id="emoji5" name="rating" value="5" <?= (($_POST['rating'] ?? '') == 5) ? 'checked' : '' ?> />
                            <label for="emoji5" class="emoji-rating-label">
                                <span class="emoji-icon">🤩</span>
                                <span class="emoji-text">Sangat Puas</span>
                            </label>
                        </div>
                    </div>

                    <div id="selectedRatingFeedback" class="selected-feedback-text">
                        Pilih salah satu ekspresi kepuasan di atas.
                    </div>
                </div>
                <span class="hint">Klik salah satu ekspresi emotikon yang paling menggambarkan pengalaman pelayanan Anda.</span>
            </div>

            <!-- Kritik dan Saran (Sebelumnya Ulasan dan Saran) -->
            <div class="form-group">
                <label>Kritik dan Saran <span class="required">*</span></label>
                <textarea name="kritik_saran" rows="5" required placeholder="Tuliskan kritik yang membangun dan saran perbaikan untuk pelayanan SMP Negeri 28 Balikpapan..."><?= htmlspecialchars($_POST['kritik_saran'] ?? '') ?></textarea>
                <span class="hint">Setiap kritik dan saran Anda menjadi bahan perbaikan mutu layanan kami ke depan.</span>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-submit">
                📩 Kirim Penilaian, Kritik & Saran
            </button>
        </form>
    </div>

</div>

<script src="assets/js/survey.js"></script>

<?php include 'footer.php'; ?>
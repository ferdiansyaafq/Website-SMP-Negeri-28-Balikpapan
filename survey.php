<?php
$page_title = "Survei Pelayanan - SMP Negeri 28 Balikpapan";
include 'header.php';
require_once 'config/database.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pengisi = trim($_POST['nama_pengisi'] ?? '');
    $agenda_kunjungan = trim($_POST['agenda_kunjungan'] ?? ''); // Menyimpan keterangan agenda kunjungan
    $rating = (int)($_POST['rating'] ?? 5);
    $ulasan = trim($_POST['ulasan'] ?? ''); // Kritik & Saran

    if ($nama_pengisi !== '' && $ulasan !== '') {
        try {
            // Kolom 'peran' di database digunakan untuk menyimpan keterangan Agenda Kunjungan
            $stmt = $pdo->prepare("INSERT INTO survei (nama_pengisi, peran, rating, ulasan, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$nama_pengisi, $agenda_kunjungan, $rating, $ulasan]);
            $success = true;
        } catch (PDOException $e) {
            $error = "Gagal menyimpan survei: " . $e->getMessage();
        }
    } else {
        $error = "Mohon lengkapi nama dan kritik & saran!";
    }
}
?>

<style>
.page-header {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: white;
    padding: 60px 40px;
    text-align: center;
}
.page-header h1 { font-size: 36px; font-weight: 800; margin-bottom: 10px; }
.container { max-width: 800px; margin: 40px auto; padding: 0 40px; }
.survey-form { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
.form-group { margin-bottom: 20px; }
.form-group label { font-weight: 600; color: #1e293b; display: block; margin-bottom: 6px; font-size: 14px; }
.form-group input, .form-group textarea {
    width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-family: inherit; font-size: 14px; background: #fafafa;
}
.form-group input:focus, .form-group textarea:focus { border-color: #0284c7; background: white; }
.form-group textarea { resize: vertical; min-height: 120px; }

/* Styling Emotikon Rating */
.emoji-rating {
    display: flex;
    gap: 15px;
    justify-content: space-between;
    margin-top: 8px;
}
.emoji-option {
    flex: 1;
    text-align: center;
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    background: #fafafa;
    font-size: 14px;
    font-weight: 600;
}
.emoji-option input { display: none; }
.emoji-option:hover, .emoji-option input:checked + span {
    border-color: #0284c7;
    background: #e0f2fe;
    color: #0284c7;
}

.btn-submit {
    background: #0284c7; color: white; border: none; padding: 14px 24px; width: 100%; border-radius: 12px; font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.3s;
}
.btn-submit:hover { background: #0369a1; }
</style>

<div class="page-header" id="Survei-Pelayanan">
    <h1>⭐ Survei Pelayanan</h1>
    <p>Berikan penilaian dan masukan untuk kemajuan sekolah</p>
</div>

<div class="container">
    <div class="survey-form">
        <h2 style="font-size: 22px; color: #1e293b; margin-bottom: 20px; font-weight: 800;">Formulir Kepuasan & Masukan</h2>

        <?php if ($success): ?>
            <div style="background: #dcfce7; color: #166534; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 600;">
                ✅ Terima kasih! Kritik & saran Anda berhasil dikirim.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 600;">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Nama Pengisi</label>
                <input type="text" name="nama_pengisi" required placeholder="Masukkan nama Anda">
            </div>

            <!-- Status diubah jadi Agenda Kunjungan (berupa input teks keterangan, bukan dropdown) -->
            <div class="form-group">
                <label>Agenda Kunjungan</label>
                <input type="text" name="agenda_kunjungan" required placeholder="Contoh: Konsultasi Layanan Siswa / Kunjungan Rutin">
            </div>

            <!-- Menu Rating Emotikon -->
            <div class="form-group">
                <label>Penilaian Kepuasan (Rating)</label>
                <div class="emoji-rating">
                    <label class="emoji-option">
                        <input type="radio" name="rating" value="5" checked>
                        <span>🤩 Sangat Baik</span>
                    </label>
                    <label class="emoji-option">
                        <input type="radio" name="rating" value="4">
                        <span>😊 Baik</span>
                    </label>
                    <label class="emoji-option">
                        <input type="radio" name="rating" value="3">
                        <span>😐 Cukup</span>
                    </label>
                    <label class="emoji-option">
                        <input type="radio" name="rating" value="2">
                        <span>😞 Kurang</span>
                    </label>
                </div>
            </div>

            <!-- Ulasan diubah jadi Kritik & Saran -->
            <div class="form-group">
                <label>Kritik & Saran</label>
                <textarea name="ulasan" required placeholder="Tuliskan kritik, saran, atau masukan membangun untuk pelayanan sekolah..."></textarea>
            </div>

            <button type="submit" class="btn-submit">Kirim Kritik & Saran</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
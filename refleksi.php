<?php
// refleksi.php
$page_title = "Refleksi Pembelajaran - SMP Negeri 28 Balikpapan";
include 'header.php';
require_once 'config/database.php';

// Ambil daftar kelas aktif
$kelasList = [];
try {
    $stmtK = $pdo->query("SELECT nama_kelas FROM kaih_kelas WHERE nama_kelas != 'Belum Ada Kelas' ORDER BY tingkat ASC, nama_kelas ASC");
    $kelasList = $stmtK->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}

// Auto-fill jika siswa sedang login
$auto_nama = '';
$auto_kelas = '';
if (!empty($_SESSION['siswa_id'])) {
    try {
        $stmtS = $pdo->prepare("SELECT nama_siswa, kelas FROM siswa WHERE id = ?");
        $stmtS->execute([(int)$_SESSION['siswa_id']]);
        $s = $stmtS->fetch(PDO::FETCH_ASSOC);
        if ($s) {
            $auto_nama = $s['nama_siswa'];
            $auto_kelas = $s['kelas'];
        }
    } catch (PDOException $e) {}
}
?>

<style>
    .refleksi-page {
        max-width: 760px;
        margin: 40px auto 60px;
        padding: 0 20px;
    }
    .refleksi-card {
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        padding: 35px 30px;
        border: 1px solid #e2e8f0;
    }
    .refleksi-header {
        text-align: center;
        margin-bottom: 28px;
    }
    .refleksi-header h2 {
        font-size: 26px;
        color: #1e293b;
        font-weight: 800;
        margin-bottom: 8px;
    }
    .refleksi-header p {
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
        margin: 0;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        font-size: 14px;
        outline: none;
        transition: 0.2s;
        box-sizing: border-box;
        font-family: inherit;
        background: #f8fafc;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #0284c7;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
    }
    .btn-submit {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #0284c7, #0369a1);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.2s;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(2, 132, 199, 0.35);
    }
    .login-prompt {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 13px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .login-prompt a {
        color: #0284c7;
        font-weight: 700;
        text-decoration: underline;
    }
</style>

<div class="refleksi-page">
    <div class="refleksi-card">
        <div class="refleksi-header">
            <h2>📝 Refleksi Pembelajaran Siswa</h2>
            <p>Ceritakan hal menarik yang kamu pelajari, kendala belajarmu, dan kesan belajarmu hari ini.</p>
        </div>

        <?php if (empty($_SESSION['siswa_id'])): ?>
            <div class="login-prompt">
                <span>💡</span>
                <div>
                    Punya akun siswa? <a href="login.php">Masuk ke Portal Siswa</a> agar refleksimu tersimpan rapi di riwayat akunmu!
                </div>
            </div>
        <?php endif; ?>

        <form action="proses_refleksi.php" method="POST">
            <div class="form-group">
                <label for="nama_siswa">Nama Lengkap Siswa <span style="color: #ef4444;">*</span></label>
                <input type="text" name="nama_siswa" id="nama_siswa" value="<?= htmlspecialchars($auto_nama); ?>" required placeholder="Contoh: Muhammad Rizki" autocomplete="name">
            </div>

            <div class="form-group">
                <label for="kelas">Kelas <span style="color: #ef4444;">*</span></label>
                <select name="kelas" id="kelas" required>
                    <option value="">-- Pilih Kelas Anda --</option>
                    <?php foreach ($kelasList as $k): ?>
                        <option value="<?= htmlspecialchars($k); ?>" <?= ($auto_kelas === $k) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($k); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="mata_pelajaran">Mata Pelajaran Hari Ini <span style="color: #ef4444;">*</span></label>
                <input type="text" name="mata_pelajaran" id="mata_pelajaran" required placeholder="Contoh: Matematika / IPA / Bahasa Indonesia">
            </div>

            <div class="form-group">
                <label for="isi_refleksi">Apa yang kamu pelajari, pengalaman berkesan, atau kendala belajarmu? <span style="color: #ef4444;">*</span></label>
                <textarea name="isi_refleksi" id="isi_refleksi" rows="5" required placeholder="Tuliskan cerita pembelajaranmu hari ini secara jujur..."></textarea>
            </div>

            <button type="submit" class="btn-submit">
                🚀 Kirim Refleksi Belajar
            </button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>

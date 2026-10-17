<?php
$page_title = "Buku Tamu - SMP Negeri 28 Balikpapan";
include 'header.php';
require_once 'config/database.php';

$pesan = '';
$tipe_pesan = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $instansi = trim($_POST['instansi'] ?? '');
    $kontak = trim($_POST['kontak'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tujuan_bertemu = trim($_POST['tujuan_bertemu'] ?? '');
    $keperluan = trim($_POST['keperluan'] ?? '');
    $tanggal_kunjungan = trim($_POST['tanggal_kunjungan'] ?? '');
    $jam_kunjungan = trim($_POST['jam_kunjungan'] ?? '');
    $jumlah_orang = max(1, (int) ($_POST['jumlah_orang'] ?? 1));

    if ($nama === '' || $instansi === '' || $kontak === '' || $tujuan_bertemu === '' || $keperluan === '' || $tanggal_kunjungan === '' || $jam_kunjungan === '') {
        $pesan = 'Mohon lengkapi semua kolom wajib bertanda bintang (*).';
        $tipe_pesan = 'error';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO buku_tamu (
                    nama, instansi, kontak, email, tujuan_bertemu, keperluan,
                    tanggal_kunjungan, jam_kunjungan, jumlah_orang, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                )'
            );
            $stmt->execute([
                $nama,
                $instansi,
                $kontak,
                $email ?: null,
                $tujuan_bertemu,
                $keperluan,
                $tanggal_kunjungan,
                $jam_kunjungan,
                $jumlah_orang
            ]);
            $pesan = 'Terima kasih! Laporan kehadiran buku tamu Anda berhasil dicatat oleh sistem sekolah.';
            $_POST = [];
        } catch (PDOException $e) {
            $pesan = 'Gagal menyimpan data buku tamu: ' . $e->getMessage();
            $tipe_pesan = 'error';
        }
    }
}
?>

<link rel="stylesheet" href="assets/css/buku-tamu.css">

<style>
    /* Tambahan CSS agar teks placeholder pada input date & time terlihat jelas */
    input[type="date"]::before,
    input[type="time"]::before {
        content: attr(placeholder);
        color: #94a3b8;
        width: 100%;
    }
    
    /* Menyembunyikan placeholder saat input sudah terisi (diklik) */
    input[type="date"]:focus::before,
    input[type="date"]:valid::before,
    input[type="time"]:focus::before,
    input[type="time"]:valid::before {
        content: "" !important;
    }
</style>

<div class="page-header">
    <h1>📖 Buku Tamu Digital</h1>
    <p>Layanan Penerimaan Tamu & Pencatatan Kunjungan Resmi SMP Negeri 28 Balikpapan</p>
</div>

<div class="container">

    <a href="sistem.php" class="btn-back">← Kembali ke Menu Sistem</a>

    <div class="guest-info-banner">
        <div class="guest-info-icon">🏛️</div>
        <div class="guest-info-text">
            <h3>Selamat Datang di SMP Negeri 28 Balikpapan</h3>
            <p>Silakan mengisi formulir buku tamu di bawah ini untuk mendokumentasikan maksud kedatangan Anda secara tertib, nyaman, dan transparan.</p>
        </div>
    </div>

    <div class="guest-form-card">
        <h2 class="form-title">Formulir Laporan Tamu</h2>
        <p class="form-subtitle">Lengkapi identitas diri serta tujuan kedatangan Anda di lingkungan sekolah.</p>

        <?php if ($pesan): ?>
            <div class="alert alert-<?= $tipe_pesan ?>">
                <?= $tipe_pesan === 'success' ? '✅' : '❌' ?>
                <?= htmlspecialchars($pesan) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="guest-book-form">

            <div class="form-row">
                <div class="form-group">
                    <label>Nama Lengkap Tamu <span class="required">*</span></label>
                    <input type="text" name="nama" required placeholder="Contoh: Bpk. Jackson Prakoso" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Instansi / Asal Lembaga <span class="required">*</span></label>
                    <input type="text" name="instansi" required placeholder="Contoh: Dinas Pendidikan" value="<?= htmlspecialchars($_POST['instansi'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Nomor Telepon / WhatsApp <span class="required">*</span></label>
                    <input type="text" name="kontak" required placeholder="Contoh: 081234567890" value="<?= htmlspecialchars($_POST['kontak'] ?? '') ?>">
                    <span class="hint">Untuk keperluan konfirmasi dan tindak lanjut penerimaan tamu.</span>
                </div>
                <div class="form-group">
                    <label>Alamat Email (Opsional)</label>
                    <input type="email" name="email" placeholder="Contoh: hendy@gmail.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Pihak yang Dituju <span class="required">*</span></label>
                    <select name="tujuan_bertemu" required>
                        <option value="">Pilih pihak yang ingin ditemui</option>
                        <option value="Kepala Sekolah" <?= (($_POST['tujuan_bertemu'] ?? '') === 'Kepala Sekolah') ? 'selected' : '' ?>>Kepala Sekolah</option>
                        <option value="Wakil Kepala Sekolah (Kurikulum / Kesiswaan)" <?= (($_POST['tujuan_bertemu'] ?? '') === 'Wakil Kepala Sekolah (Kurikulum / Kesiswaan)') ? 'selected' : '' ?>>Wakil Kepala Sekolah (Kurikulum / Kesiswaan)</option>
                        <option value="Tata Usaha (TU) / Administrasi" <?= (($_POST['tujuan_bertemu'] ?? '') === 'Tata Usaha (TU) / Administrasi') ? 'selected' : '' ?>>Tata Usaha (TU) / Administrasi</option>
                        <option value="Guru Mata Pelajaran / Wali Kelas" <?= (($_POST['tujuan_bertemu'] ?? '') === 'Guru Mata Pelajaran / Wali Kelas') ? 'selected' : '' ?>>Guru Mata Pelajaran / Wali Kelas</option>
                        <option value="Bimbingan Konseling (BK)" <?= (($_POST['tujuan_bertemu'] ?? '') === 'Bimbingan Konseling (BK)') ? 'selected' : '' ?>>Bimbingan Konseling (BK)</option>
                        <option value="Komite Sekolah" <?= (($_POST['tujuan_bertemu'] ?? '') === 'Komite Sekolah') ? 'selected' : '' ?>>Komite Sekolah</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah Tamu</label>
                    <input type="number" name="jumlah_orang" min="1" max="100" value="<?= htmlspecialchars($_POST['jumlah_orang'] ?? '1') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tanggal Kunjungan <span class="required">*</span></label>
                    <!-- Value diubah agar tidak otomatis date('Y-m-d') jika kosong -->
                    <input type="date" id="tanggal_kunjungan" name="tanggal_kunjungan" required placeholder="Pilih Tanggal" value="<?= htmlspecialchars($_POST['tanggal_kunjungan'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Waktu / Jam Kedatangan <span class="required">*</span></label>
                    <!-- Value diubah agar tidak otomatis date('H:i') jika kosong -->
                    <input type="time" id="jam_kunjungan" name="jam_kunjungan" required placeholder="Pilih Jam" value="<?= htmlspecialchars($_POST['jam_kunjungan'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Maksud, Keperluan, atau Laporan Kunjungan <span class="required">*</span></label>
                <textarea name="keperluan" rows="4" required placeholder="Tuliskan secara jelas maksud atau laporan kedatangan Anda di sekolah..."><?= htmlspecialchars($_POST['keperluan'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-submit">
                📥 Simpan Laporan Buku Tamu
            </button>

        </form>
    </div>

</div>

<script src="assets/js/buku-tamu.js"></script>

<?php include 'footer.php'; ?>
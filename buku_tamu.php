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
    $tanggal_kunjungan = trim($_POST['tanggal_kunjungan'] ?? date('Y-m-d'));
    $jam_kunjungan = trim($_POST['jam_kunjungan'] ?? date('H:i'));
    $jumlah_orang = max(1, (int) ($_POST['jumlah_orang'] ?? 1));

    if ($nama === '' || $instansi === '' || $kontak === '' || $tujuan_bertemu === '' || $keperluan === '') {
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

<div class="page-header">
    <h1>📖 Buku Tamu Digital</h1>
    <p>Layanan Penerimaan Tamu & Pencatatan Kunjungan Resmi SMP Negeri 28 Balikpapan</p>
</div>

<div class="container">

    <a href="lainnya.php" class="btn-back">← Kembali ke FAQ / Menu Lainnya</a>

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
                    <input type="text" name="nama" required placeholder="Contoh: Bpk. Bambang Sutrisno" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Instansi / Asal Lembaga <span class="required">*</span></label>
                    <input type="text" name="instansi" required placeholder="Contoh: Dinas Pendidikan, Orang Tua Siswa, Media, dll." value="<?= htmlspecialchars($_POST['instansi'] ?? '') ?>">
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
                    <input type="email" name="email" placeholder="Contoh: nama@domain.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Pihak / Bagian yang Dituju <span class="required">*</span></label>
                    <select name="tujuan_bertemu" required>
                        <option value="">-- Pilih Bagian / Pihak yang Ingin Ditemui --</option>
                        <option value="Kepala Sekolah" <?= (($_POST['tujuan_bertemu'] ?? '') === 'Kepala Sekolah') ? 'selected' : '' ?>>Kepala Sekolah</option>
                        <option value="Wakil Kepala Sekolah (Kurikulum / Kesiswaan)" <?= (($_POST['tujuan_bertemu'] ?? '') === 'Wakil Kepala Sekolah (Kurikulum / Kesiswaan)') ? 'selected' : '' ?>>Wakil Kepala Sekolah (Kurikulum / Kesiswaan)</option>
                        <option value="Tata Usaha (TU) / Administrasi" <?= (($_POST['tujuan_bertemu'] ?? '') === 'Tata Usaha (TU) / Administrasi') ? 'selected' : '' ?>>Tata Usaha (TU) / Administrasi</option>
                        <option value="Guru Mata Pelajaran / Wali Kelas" <?= (($_POST['tujuan_bertemu'] ?? '') === 'Guru Mata Pelajaran / Wali Kelas') ? 'selected' : '' ?>>Guru Mata Pelajaran / Wali Kelas</option>
                        <option value="Bimbingan Konseling (BK)" <?= (($_POST['tujuan_bertemu'] ?? '') === 'Bimbingan Konseling (BK)') ? 'selected' : '' ?>>Bimbingan Konseling (BK)</option>
                        <option value="Komite Sekolah" <?= (($_POST['tujuan_bertemu'] ?? '') === 'Komite Sekolah') ? 'selected' : '' ?>>Komite Sekolah</option>
                        <option value="Lainnya" <?= (($_POST['tujuan_bertemu'] ?? '') === 'Lainnya') ? 'selected' : '' ?>>Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah Tamu / Rombongan</label>
                    <input type="number" name="jumlah_orang" min="1" max="100" value="<?= htmlspecialchars($_POST['jumlah_orang'] ?? '1') ?>">
                    <span class="hint">Jumlah orang dalam rombongan kunjungan.</span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tanggal Kunjungan <span class="required">*</span></label>
                    <input type="date" id="tanggal_kunjungan" name="tanggal_kunjungan" required value="<?= htmlspecialchars($_POST['tanggal_kunjungan'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="form-group">
                    <label>Waktu / Jam Kedatangan <span class="required">*</span></label>
                    <input type="time" id="jam_kunjungan" name="jam_kunjungan" required value="<?= htmlspecialchars($_POST['jam_kunjungan'] ?? date('H:i')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Maksud, Keperluan, atau Laporan Kunjungan <span class="required">*</span></label>
                <textarea name="keperluan" rows="4" required placeholder="Tuliskan secara jelas maksud atau laporan kedatangan Anda di sekolah..."><?= htmlspecialchars($_POST['keperluan'] ?? '') ?></textarea>
                <span class="hint">Penjelasan agenda memudahkan tim piket atau pimpinan sekolah dalam menyambut Anda.</span>
            </div>

            <button type="submit" class="btn-submit">
                📥 Simpan Laporan Buku Tamu
            </button>

        </form>
    </div>

</div>

<script src="assets/js/buku-tamu.js"></script>

<?php include 'footer.php'; ?>

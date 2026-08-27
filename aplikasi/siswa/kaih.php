<?php
// aplikasi/siswa/kaih.php
require_once '../includes/header-kaih.php';

$message = '';
$message_type = '';
$siswa_id = $_SESSION['siswa_id'] ?? 0;
$nama_siswa = 'Siswa';

// Ambil nama siswa
if ($siswa_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT nama_siswa FROM siswa WHERE id = ?");
        $stmt->execute([$siswa_id]);
        $siswa = $stmt->fetch();
        if ($siswa) {
            $nama_siswa = explode(' ', $siswa['nama_siswa'])[0];
        }
    } catch (PDOException $e) {}
}

// ============================================================
// AUTO-CREATE TABEL
// ============================================================
function ensureLaporanHarianTable($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `laporan_harian` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `siswa_id` INT NOT NULL,
            `tanggal` DATE NOT NULL,
            `bangun` TINYINT(1) NOT NULL DEFAULT 0,
            `bangun_keterangan` VARCHAR(255) NULL,
            `ibadah` TINYINT(1) NOT NULL DEFAULT 0,
            `ibadah_catatan` VARCHAR(255) NULL,
            `olahraga` TINYINT(1) NOT NULL DEFAULT 0,
            `olahraga_jenis` VARCHAR(50) NULL,
            `sarapan` TINYINT(1) NOT NULL DEFAULT 0,
            `sarapan_menu` VARCHAR(50) NULL,
            `membaca` TINYINT(1) NOT NULL DEFAULT 0,
            `membaca_judul` VARCHAR(255) NULL,
            `membaca_menit` INT NULL,
            `membantu` TINYINT(1) NOT NULL DEFAULT 0,
            `membantu_jenis` VARCHAR(50) NULL,
            `menabung` TINYINT(1) NOT NULL DEFAULT 0,
            `menabung_keterangan` VARCHAR(255) NULL,
            `orang_tua_validated_at` DATETIME NULL,
            `guru_validated_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_siswa_tanggal` (`siswa_id`, `tanggal`),
            INDEX `idx_tanggal` (`tanggal`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
ensureLaporanHarianTable($pdo);

// ============================================================
// CEK & TAMBAHKAN KOLOM KETERANGAN
// ============================================================
function ensureKeteranganColumns($pdo) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM laporan_harian LIKE 'bangun_keterangan'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE laporan_harian ADD COLUMN bangun_keterangan VARCHAR(255) NULL AFTER bangun");
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM laporan_harian LIKE 'menabung_keterangan'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE laporan_harian ADD COLUMN menabung_keterangan VARCHAR(255) NULL AFTER menabung");
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM laporan_harian LIKE 'membaca_judul'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE laporan_harian ADD COLUMN membaca_judul VARCHAR(255) NULL AFTER membaca");
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
ensureKeteranganColumns($pdo);

// ============================================================
// PROSES SIMPAN KAIH
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_kaih'])) {
    $tanggal = date('Y-m-d');
    
    $bangun = isset($_POST['bangun']) && $_POST['bangun'] == 1 ? 1 : 0;
    $ibadah = isset($_POST['ibadah']) && $_POST['ibadah'] == 1 ? 1 : 0;
    $olahraga = isset($_POST['olahraga']) && $_POST['olahraga'] == 1 ? 1 : 0;
    $sarapan = isset($_POST['sarapan']) && $_POST['sarapan'] == 1 ? 1 : 0;
    $membaca = isset($_POST['membaca']) && $_POST['membaca'] == 1 ? 1 : 0;
    $membantu = isset($_POST['membantu']) && $_POST['membantu'] == 1 ? 1 : 0;
    $menabung = isset($_POST['menabung']) && $_POST['menabung'] == 1 ? 1 : 0;
    
    $bangun_keterangan = trim($_POST['bangun_keterangan'] ?? '');
    $membaca_judul = trim($_POST['membaca_judul'] ?? '');
    $menabung_keterangan = trim($_POST['menabung_keterangan'] ?? '');
    
    if ($siswa_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM laporan_harian WHERE siswa_id = ? AND tanggal = ?");
            $stmt->execute([$siswa_id, $tanggal]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $pdo->prepare("UPDATE laporan_harian SET 
                    bangun = ?, ibadah = ?, olahraga = ?, sarapan = ?, 
                    membaca = ?, membantu = ?, menabung = ?,
                    bangun_keterangan = ?, membaca_judul = ?, menabung_keterangan = ?,
                    updated_at = NOW() 
                    WHERE siswa_id = ? AND tanggal = ?");
                $stmt->execute([
                    $bangun, $ibadah, $olahraga, $sarapan, 
                    $membaca, $membantu, $menabung,
                    $bangun_keterangan, $membaca_judul, $menabung_keterangan,
                    $siswa_id, $tanggal
                ]);
                $message = '✅ Data KAIH berhasil diperbarui!';
                $message_type = 'success';
            } else {
                $stmt = $pdo->prepare("INSERT INTO laporan_harian (
                    siswa_id, tanggal, bangun, ibadah, olahraga, sarapan, membaca, membantu, menabung,
                    bangun_keterangan, membaca_judul, menabung_keterangan,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([
                    $siswa_id, $tanggal,
                    $bangun, $ibadah, $olahraga, $sarapan, $membaca, $membantu, $menabung,
                    $bangun_keterangan, $membaca_judul, $menabung_keterangan
                ]);
                $message = '✅ Data KAIH berhasil disimpan! Terus jaga kebiasaan baik ya! 🎉';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = '❌ Gagal menyimpan data: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = '❌ Data siswa tidak ditemukan. Silakan login ulang.';
        $message_type = 'error';
    }
}

// Ambil data hari ini
$data_hari_ini = null;
if ($siswa_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM laporan_harian WHERE siswa_id = ? AND tanggal = ?");
        $stmt->execute([$siswa_id, date('Y-m-d')]);
        $data_hari_ini = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

$total_terisi = 0;
if ($data_hari_ini) {
    $total_terisi = 
        ($data_hari_ini['bangun'] ?? 0) +
        ($data_hari_ini['ibadah'] ?? 0) +
        ($data_hari_ini['olahraga'] ?? 0) +
        ($data_hari_ini['sarapan'] ?? 0) +
        ($data_hari_ini['membaca'] ?? 0) +
        ($data_hari_ini['membantu'] ?? 0) +
        ($data_hari_ini['menabung'] ?? 0);
}

$today = date('Y-m-d');
$last_submit = $_SESSION['last_kaih_date'] ?? '';
if ($last_submit !== $today) {
    $_SESSION['last_kaih_date'] = $today;
}

$motivation = '';
$emoji = '';
if ($total_terisi >= 7) {
    $motivation = 'Wow! Kamu benar-benar hebat! Pertahankan ya! 💪';
    $emoji = '🌟';
} elseif ($total_terisi >= 5) {
    $motivation = 'Semangat! Tinggal sedikit lagi! 🔥';
    $emoji = '🔥';
} elseif ($total_terisi >= 3) {
    $motivation = 'Good job! Teruskan kebiasaan baikmu! ✨';
    $emoji = '✨';
} elseif ($total_terisi >= 1) {
    $motivation = 'Langkah kecil hari ini, kebiasaan besar besok! 🚀';
    $emoji = '🚀';
} else {
    $motivation = 'Ayo mulai kebiasaan baikmu hari ini! 😊';
    $emoji = '🌱';
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap');
    
    .kaih-container {
        max-width: 600px;
        margin: 0 auto;
        font-family: 'Poppins', 'Segoe UI', system-ui, sans-serif;
        padding: 0 12px;
    }

    .kaih-header {
        background: linear-gradient(135deg, #0284c7, #0369a1, #38bdf8);
        border-radius: 24px;
        padding: 30px 24px 24px;
        margin-bottom: 20px;
        text-align: center;
        color: white;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(2, 132, 199, 0.3);
    }
    .kaih-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 200px;
        height: 200px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
    }
    .kaih-header::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -10%;
        width: 150px;
        height: 150px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }
    .kaih-header .header-emoji {
        font-size: 40px;
        display: block;
        margin-bottom: 4px;
        position: relative;
        z-index: 1;
    }
    .kaih-header .title {
        font-size: 22px;
        font-weight: 800;
        position: relative;
        z-index: 1;
        letter-spacing: -0.5px;
    }
    .kaih-header .subtitle {
        font-size: 14px;
        opacity: 0.9;
        margin-top: 4px;
        position: relative;
        z-index: 1;
        font-weight: 400;
    }
    .kaih-header .subtitle strong {
        font-weight: 700;
    }

    .progress-card {
        background: white;
        border-radius: 20px;
        padding: 20px 20px 18px;
        margin-bottom: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #f1f5f9;
        text-align: center;
    }
    .progress-card .label {
        font-size: 13px;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .progress-card .number-wrap {
        display: flex;
        align-items: baseline;
        justify-content: center;
        gap: 4px;
        margin: 2px 0;
    }
    .progress-card .number {
        font-size: 52px;
        font-weight: 800;
        background: linear-gradient(135deg, #0284c7, #0369a1, #38bdf8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.1;
    }
    .progress-card .number span {
        font-size: 24px;
        color: #cbd5e1;
        -webkit-text-fill-color: #cbd5e1;
        font-weight: 600;
    }
    .progress-bar-track {
        width: 100%;
        height: 10px;
        background: #f1f5f9;
        border-radius: 10px;
        margin-top: 12px;
        overflow: hidden;
    }
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #38bdf8, #0284c7, #0369a1);
        border-radius: 10px;
        transition: width 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        width: 0%;
    }
    .progress-card .status-text {
        font-size: 14px;
        font-weight: 600;
        margin-top: 10px;
        padding: 8px 16px;
        border-radius: 30px;
        display: inline-block;
    }
    .progress-card .status-text.done {
        background: #dcfce7;
        color: #16a34a;
    }
    .progress-card .status-text.pending {
        background: #e0f2fe;
        color: #075985;
    }
    .progress-card .status-text.empty {
        background: #f1f5f9;
        color: #94a3b8;
    }
    .progress-card .motivation {
        font-size: 13px;
        color: #64748b;
        margin-top: 8px;
        font-weight: 500;
    }
    .progress-card .motivation .emoji-big {
        font-size: 20px;
    }

    /* ===== HABIT ITEMS - Perbaikan Tata Letak ===== */
    .habit-item {
        background: white;
        border-radius: 16px;
        padding: 16px 18px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
        border: 2px solid #f1f5f9;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    .habit-item:hover {
        border-color: #bae6fd;
        box-shadow: 0 6px 20px rgba(2, 132, 199, 0.08);
    }

    /* HEADER: Judul di kiri, Opsi di kanan */
    .habit-item .habit-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        flex-wrap: wrap;
    }

    .habit-item .habit-left {
        flex: 1;
        min-width: 140px;
    }

    .habit-item .habit-right {
        flex-shrink: 0;
        display: flex;
        gap: 6px;
        align-items: center;
        padding-top: 2px;
    }

    .habit-item .label {
        font-weight: 600;
        font-size: 15px;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .habit-item .label .icon {
        font-size: 20px;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e0f2fe;
        border-radius: 10px;
        flex-shrink: 0;
    }
    .habit-item .description {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 2px;
        padding-left: 42px;
        line-height: 1.4;
    }

    /* Opsi Ya/Tidak */
    .habit-item .options label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
        font-weight: 600;
        color: #94a3b8;
        padding: 4px 14px;
        border-radius: 30px;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
        background: #f8fafc;
        font-family: 'Poppins', sans-serif;
    }
    .habit-item .options label:hover {
        background: #e0f2fe;
        transform: scale(1.02);
    }
    .habit-item .options label input[type="radio"] {
        display: none;
    }
    .habit-item .options label.checked-ya {
        background: #dcfce7;
        border-color: #86efac;
        color: #15803d;
        box-shadow: 0 2px 8px rgba(22, 163, 74, 0.15);
    }
    .habit-item .options label.checked-tidak {
        background: #fee2e2;
        border-color: #fca5a5;
        color: #b91c1c;
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.1);
    }

    /* Keterangan di bawah */
    .habit-item .keterangan-wrapper {
        margin-top: 10px;
        padding-left: 42px;
        display: none;
    }
    .habit-item .keterangan-wrapper.show {
        display: block;
    }
    .habit-item .keterangan-wrapper .label-keterangan {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        display: block;
        margin-bottom: 4px;
    }
    .habit-item .keterangan-wrapper textarea {
        width: 100%;
        padding: 8px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s;
        background: #fafafa;
        resize: vertical;
        min-height: 50px;
    }
    .habit-item .keterangan-wrapper textarea:focus {
        outline: none;
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        background: white;
    }

    .btn-simpan {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #0284c7, #0369a1);
        color: white;
        border: none;
        border-radius: 16px;
        font-size: 17px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 8px;
        box-shadow: 0 4px 16px rgba(2, 132, 199, 0.3);
        font-family: 'Poppins', sans-serif;
        letter-spacing: -0.3px;
    }
    .btn-simpan:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 32px rgba(2, 132, 199, 0.4);
    }

    .tips-box {
        margin-top: 16px;
        padding: 14px 18px;
        background: linear-gradient(135deg, #e0f2fe, #bae6fd);
        border-radius: 16px;
        border: 1px dashed #38bdf8;
        text-align: center;
        color: #075985;
        font-size: 13px;
        font-weight: 500;
    }
    .tips-box strong {
        color: #0284c7;
    }
    .tips-box .sparkle {
        font-size: 18px;
    }

    .alert {
        padding: 14px 18px;
        border-radius: 14px;
        margin-bottom: 18px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
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

    @media (max-width: 500px) {
        .kaih-container { padding: 0 6px; }
        .kaih-header { padding: 24px 16px 20px; }
        .kaih-header .title { font-size: 19px; }
        .kaih-header .subtitle { font-size: 13px; }
        .progress-card .number { font-size: 42px; }
        
        .habit-item { padding: 12px 14px; }
        .habit-item .habit-header {
            flex-direction: column;
            align-items: stretch;
        }
        .habit-item .habit-right {
            justify-content: flex-start;
            padding-left: 42px;
            margin-top: 2px;
        }
        .habit-item .label { font-size: 14px; }
        .habit-item .label .icon { width: 28px; height: 28px; font-size: 16px; }
        .habit-item .description { padding-left: 38px; font-size: 11px; }
        .habit-item .keterangan-wrapper { padding-left: 0; }
        .habit-item .options label { padding: 4px 14px; font-size: 13px; }
        
        .btn-simpan { font-size: 15px; padding: 14px; }
        .progress-card .status-text { font-size: 13px; }
    }

    @media (max-width: 400px) {
        .habit-item .options label { padding: 3px 10px; font-size: 12px; }
        .habit-item .label { font-size: 13px; }
        .habit-item .label .icon { width: 24px; height: 24px; font-size: 14px; }
        .habit-item .description { padding-left: 34px; font-size: 10px; }
        .habit-item .habit-right { padding-left: 34px; }
        .habit-item .keterangan-wrapper textarea { font-size: 12px; padding: 6px 10px; min-height: 40px; }
    }
</style>

<div class="kaih-container">

    <div class="kaih-header">
        <span class="header-emoji">🌟</span>
        <div class="title">Hai, <?php echo htmlspecialchars($nama_siswa); ?>!</div>
        <div class="subtitle">
            Yuk catat <strong>7 Kebiasaan</strong> baikmu hari ini ✨
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="progress-card">
        <div class="label">🎯 Progress Hari Ini</div>
        <div class="number-wrap">
            <div class="number">
                <?php echo $total_terisi; ?><span>/7</span>
            </div>
        </div>
        <div class="progress-bar-track">
            <div class="progress-bar-fill" style="width: <?php echo ($total_terisi / 7) * 100; ?>%;"></div>
        </div>
        <div class="status-text 
            <?php echo $total_terisi >= 7 ? 'done' : ($total_terisi > 0 ? 'pending' : 'empty'); ?>">
            <?php if ($total_terisi >= 7): ?>
                🎉 Lengkap! Kamu Hebat!
            <?php elseif ($total_terisi >= 4): ?>
                💪 Semangat! <?php echo $total_terisi; ?>/7 selesai
            <?php elseif ($total_terisi > 0): ?>
                🚀 Mulai bagus! <?php echo $total_terisi; ?>/7
            <?php else: ?>
                🌱 Ayo mulai hari ini!
            <?php endif; ?>
        </div>
        <div class="motivation">
            <span class="emoji-big"><?php echo $emoji; ?></span>
            <?php echo $motivation; ?>
        </div>
    </div>

    <form method="POST" action="">
        <input type="hidden" name="simpan_kaih" value="1">

        <!-- 1. Bangun Pagi -->
        <div class="habit-item">
            <div class="habit-header">
                <div class="habit-left">
                    <div class="label">
                        <span class="icon">🌅</span> Bangun Pagi
                    </div>
                    <div class="description">Bangun sebelum pukul 05.30 dan siapkan diri untuk beraktivitas</div>
                </div>
                <div class="habit-right">
                    <div class="options">
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['bangun'] == 1) ? 'checked-ya' : ''; ?>">
                            <input type="radio" name="bangun" value="1" <?php echo ($data_hari_ini && $data_hari_ini['bangun'] == 1) ? 'checked' : ''; ?> required> Ya
                        </label>
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['bangun'] == 0) ? 'checked-tidak' : ''; ?>">
                            <input type="radio" name="bangun" value="0" <?php echo ($data_hari_ini && $data_hari_ini['bangun'] == 0) ? 'checked' : ''; ?>> Tidak
                        </label>
                    </div>
                </div>
            </div>
            <div class="keterangan-wrapper <?php echo ($data_hari_ini && $data_hari_ini['bangun'] == 1) ? 'show' : ''; ?>">
                <span class="label-keterangan">📝 Ceritakan pengalaman bangun pagimu:</span>
                <textarea name="bangun_keterangan" placeholder="Contoh: Saya bangun pukul 05.00, langsung sholat subuh dan bersiap ke sekolah..."><?php echo $data_hari_ini['bangun_keterangan'] ?? ''; ?></textarea>
            </div>
        </div>

        <!-- 2. Beribadah -->
        <div class="habit-item">
            <div class="habit-header">
                <div class="habit-left">
                    <div class="label">
                        <span class="icon">🕌</span> Beribadah
                    </div>
                    <div class="description">Laksanakan ibadah sesuai keyakinan dan ajaran agama masing-masing</div>
                </div>
                <div class="habit-right">
                    <div class="options">
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['ibadah'] == 1) ? 'checked-ya' : ''; ?>">
                            <input type="radio" name="ibadah" value="1" <?php echo ($data_hari_ini && $data_hari_ini['ibadah'] == 1) ? 'checked' : ''; ?> required> Ya
                        </label>
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['ibadah'] == 0) ? 'checked-tidak' : ''; ?>">
                            <input type="radio" name="ibadah" value="0" <?php echo ($data_hari_ini && $data_hari_ini['ibadah'] == 0) ? 'checked' : ''; ?>> Tidak
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Berolahraga -->
        <div class="habit-item">
            <div class="habit-header">
                <div class="habit-left">
                    <div class="label">
                        <span class="icon">⚽</span> Berolahraga
                    </div>
                    <div class="description">Lakukan aktivitas fisik minimal 30 menit untuk menjaga kesehatan</div>
                </div>
                <div class="habit-right">
                    <div class="options">
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['olahraga'] == 1) ? 'checked-ya' : ''; ?>">
                            <input type="radio" name="olahraga" value="1" <?php echo ($data_hari_ini && $data_hari_ini['olahraga'] == 1) ? 'checked' : ''; ?> required> Ya
                        </label>
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['olahraga'] == 0) ? 'checked-tidak' : ''; ?>">
                            <input type="radio" name="olahraga" value="0" <?php echo ($data_hari_ini && $data_hari_ini['olahraga'] == 0) ? 'checked' : ''; ?>> Tidak
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Sarapan Sehat -->
        <div class="habit-item">
            <div class="habit-header">
                <div class="habit-left">
                    <div class="label">
                        <span class="icon">🥗</span> Sarapan Sehat
                    </div>
                    <div class="description">Konsumsi makanan bergizi seimbang di pagi hari untuk energi belajar</div>
                </div>
                <div class="habit-right">
                    <div class="options">
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['sarapan'] == 1) ? 'checked-ya' : ''; ?>">
                            <input type="radio" name="sarapan" value="1" <?php echo ($data_hari_ini && $data_hari_ini['sarapan'] == 1) ? 'checked' : ''; ?> required> Ya
                        </label>
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['sarapan'] == 0) ? 'checked-tidak' : ''; ?>">
                            <input type="radio" name="sarapan" value="0" <?php echo ($data_hari_ini && $data_hari_ini['sarapan'] == 0) ? 'checked' : ''; ?>> Tidak
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Gemar Belajar -->
        <div class="habit-item">
            <div class="habit-header">
                <div class="habit-left">
                    <div class="label">
                        <span class="icon">📚</span> Gemar Belajar
                    </div>
                    <div class="description">Luangkan waktu minimal 1 jam untuk membaca dan belajar hal baru</div>
                </div>
                <div class="habit-right">
                    <div class="options">
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['membaca'] == 1) ? 'checked-ya' : ''; ?>">
                            <input type="radio" name="membaca" value="1" <?php echo ($data_hari_ini && $data_hari_ini['membaca'] == 1) ? 'checked' : ''; ?> required> Ya
                        </label>
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['membaca'] == 0) ? 'checked-tidak' : ''; ?>">
                            <input type="radio" name="membaca" value="0" <?php echo ($data_hari_ini && $data_hari_ini['membaca'] == 0) ? 'checked' : ''; ?>> Tidak
                        </label>
                    </div>
                </div>
            </div>
            <div class="keterangan-wrapper <?php echo ($data_hari_ini && $data_hari_ini['membaca'] == 1) ? 'show' : ''; ?>">
                <span class="label-keterangan">📖 Apa yang kamu pelajari / baca hari ini?</span>
                <textarea name="membaca_judul" placeholder="Contoh: Saya membaca buku IPA tentang tata surya dan belajar 5 kosakata baru..."><?php echo $data_hari_ini['membaca_judul'] ?? ''; ?></textarea>
            </div>
        </div>

        <!-- 6. Membantu Orang Tua -->
        <div class="habit-item">
            <div class="habit-header">
                <div class="habit-left">
                    <div class="label">
                        <span class="icon">🤝</span> Membantu Orang Tua
                    </div>
                    <div class="description">Tunjukkan rasa sayang dengan membantu pekerjaan rumah tangga</div>
                </div>
                <div class="habit-right">
                    <div class="options">
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['membantu'] == 1) ? 'checked-ya' : ''; ?>">
                            <input type="radio" name="membantu" value="1" <?php echo ($data_hari_ini && $data_hari_ini['membantu'] == 1) ? 'checked' : ''; ?> required> Ya
                        </label>
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['membantu'] == 0) ? 'checked-tidak' : ''; ?>">
                            <input type="radio" name="membantu" value="0" <?php echo ($data_hari_ini && $data_hari_ini['membantu'] == 0) ? 'checked' : ''; ?>> Tidak
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. Menabung -->
        <div class="habit-item">
            <div class="habit-header">
                <div class="habit-left">
                    <div class="label">
                        <span class="icon">💰</span> Menabung
                    </div>
                    <div class="description">Sisihkan sebagian uang saku untuk ditabung sebagai kebiasaan baik</div>
                </div>
                <div class="habit-right">
                    <div class="options">
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['menabung'] == 1) ? 'checked-ya' : ''; ?>">
                            <input type="radio" name="menabung" value="1" <?php echo ($data_hari_ini && $data_hari_ini['menabung'] == 1) ? 'checked' : ''; ?> required> Ya
                        </label>
                        <label class="<?php echo ($data_hari_ini && $data_hari_ini['menabung'] == 0) ? 'checked-tidak' : ''; ?>">
                            <input type="radio" name="menabung" value="0" <?php echo ($data_hari_ini && $data_hari_ini['menabung'] == 0) ? 'checked' : ''; ?>> Tidak
                        </label>
                    </div>
                </div>
            </div>
            <div class="keterangan-wrapper <?php echo ($data_hari_ini && $data_hari_ini['menabung'] == 1) ? 'show' : ''; ?>">
                <span class="label-keterangan">💰 Ceritakan pengalaman menabungmu:</span>
                <textarea name="menabung_keterangan" placeholder="Contoh: Hari ini saya menabung Rp5.000 dari uang saku, rencana mau ditabung untuk membeli buku..."><?php echo $data_hari_ini['menabung_keterangan'] ?? ''; ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn-simpan">
            💾 <?php echo ($data_hari_ini) ? 'Update KAIH Hari Ini' : 'Simpan KAIH Hari Ini'; ?>
        </button>
    </form>

    <div class="tips-box">
        <span class="sparkle">💡</span>
        <strong>Tips:</strong> Catat kebiasaan baikmu setiap hari! 
        Semakin lengkap, semakin <strong>keren</strong> karaktermu! 🚀
    </div>

</div>

<script>
document.querySelectorAll('.habit-item input[type="radio"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var parent = this.closest('.habit-item');
        var options = parent.querySelector('.options');
        var keterangan = parent.querySelector('.keterangan-wrapper');
        
        options.querySelectorAll('label').forEach(function(label) {
            label.classList.remove('checked-ya', 'checked-tidak');
        });
        
        if (this.checked) {
            var label = this.closest('label');
            if (this.value == 1) {
                label.classList.add('checked-ya');
                if (keterangan) {
                    keterangan.classList.add('show');
                }
            } else {
                label.classList.add('checked-tidak');
                if (keterangan) {
                    keterangan.classList.remove('show');
                }
            }
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const fill = document.querySelector('.progress-bar-fill');
    if (fill) {
        const targetWidth = fill.style.width;
        fill.style.width = '0%';
        setTimeout(function() {
            fill.style.width = targetWidth;
        }, 100);
    }
});
</script>

<?php
?>
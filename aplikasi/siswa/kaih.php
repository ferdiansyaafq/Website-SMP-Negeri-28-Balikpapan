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
            `menabung_nominal` INT NULL,
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
    
    if ($siswa_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM laporan_harian WHERE siswa_id = ? AND tanggal = ?");
            $stmt->execute([$siswa_id, $tanggal]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $pdo->prepare("UPDATE laporan_harian SET 
                    bangun = ?, ibadah = ?, olahraga = ?, sarapan = ?, 
                    membaca = ?, membantu = ?, menabung = ?, updated_at = NOW() 
                    WHERE siswa_id = ? AND tanggal = ?");
                $stmt->execute([$bangun, $ibadah, $olahraga, $sarapan, $membaca, $membantu, $menabung, $siswa_id, $tanggal]);
                $message = '✅ Data KAIH berhasil diperbarui!';
                $message_type = 'success';
            } else {
                $stmt = $pdo->prepare("INSERT INTO laporan_harian (
                    siswa_id, tanggal, bangun, ibadah, olahraga, sarapan, membaca, membantu, menabung, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$siswa_id, $tanggal, $bangun, $ibadah, $olahraga, $sarapan, $membaca, $membantu, $menabung]);
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

// Motivasi berdasarkan progress
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
    /* ============================================================
       DESIGN GEN Z - MODERN, FUN, COLORFUL
       ============================================================ */
    
    /* Font & Base */
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap');
    
    .kaih-container {
        max-width: 600px;
        margin: 0 auto;
        font-family: 'Poppins', 'Segoe UI', system-ui, sans-serif;
        padding: 0 12px;
    }

    /* ===== HEADER ===== */
    .kaih-header {
        background: linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7);
        border-radius: 24px;
        padding: 30px 24px 24px;
        margin-bottom: 20px;
        text-align: center;
        color: white;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(99, 102, 241, 0.3);
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

    /* ===== PROGRESS CARD ===== */
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
        background: linear-gradient(135deg, #6366f1, #a855f7);
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
        background: linear-gradient(90deg, #6366f1, #a855f7, #ec4899);
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
        background: #fef3c7;
        color: #d97706;
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

    /* ===== HABIT ITEMS ===== */
    .habit-item {
        background: white;
        border-radius: 16px;
        padding: 14px 18px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
        border: 2px solid #f1f5f9;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        cursor: pointer;
    }
    .habit-item:hover {
        border-color: #e2e8f0;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    }
    .habit-item .label {
        font-weight: 600;
        font-size: 15px;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .habit-item .label .icon {
        font-size: 22px;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        border-radius: 12px;
    }
    .habit-item .options {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-shrink: 0;
    }
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
        background: #f1f5f9;
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

    /* ===== SUBMIT BUTTON ===== */
    .btn-simpan {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        border: none;
        border-radius: 16px;
        font-size: 17px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 8px;
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
        font-family: 'Poppins', sans-serif;
        letter-spacing: -0.3px;
    }
    .btn-simpan:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 32px rgba(99, 102, 241, 0.4);
    }
    .btn-simpan:active {
        transform: translateY(0);
    }

    /* ===== TIPS ===== */
    .tips-box {
        margin-top: 16px;
        padding: 14px 18px;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-radius: 16px;
        border: 1px dashed #cbd5e1;
        text-align: center;
        color: #475569;
        font-size: 13px;
        font-weight: 500;
    }
    .tips-box strong {
        color: #6366f1;
    }
    .tips-box .sparkle {
        font-size: 18px;
    }

    /* ===== ALERT ===== */
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

    /* ===== RESPONSIVE ===== */
    @media (max-width: 500px) {
        .kaih-container { padding: 0 6px; }
        .kaih-header { padding: 24px 16px 20px; }
        .kaih-header .title { font-size: 19px; }
        .kaih-header .subtitle { font-size: 13px; }
        .progress-card .number { font-size: 42px; }
        .habit-item {
            padding: 12px 14px;
            flex-wrap: wrap;
            gap: 6px 0;
        }
        .habit-item .label {
            font-size: 14px;
            width: 100%;
        }
        .habit-item .label .icon {
            width: 30px;
            height: 30px;
            font-size: 18px;
        }
        .habit-item .options {
            width: 100%;
            justify-content: flex-start;
            padding-left: 46px;
        }
        .habit-item .options label {
            padding: 4px 14px;
            font-size: 13px;
        }
        .btn-simpan { font-size: 15px; padding: 14px; }
        .progress-card .status-text { font-size: 13px; }
    }

    @media (max-width: 400px) {
        .habit-item .options label {
            padding: 3px 10px;
            font-size: 12px;
        }
        .habit-item .label {
            font-size: 13px;
        }
        .habit-item .label .icon {
            width: 26px;
            height: 26px;
            font-size: 16px;
        }
        .habit-item .options {
            padding-left: 38px;
        }
    }
</style>

<div class="kaih-container">

    <!-- ============================================================
         HEADER
         ============================================================ -->
    <div class="kaih-header">
        <span class="header-emoji">🌟</span>
        <div class="title">Hai, <?php echo htmlspecialchars($nama_siswa); ?>!</div>
        <div class="subtitle">
            Yuk catat <strong>7 Kebiasaan</strong> baikmu hari ini ✨
        </div>
    </div>

    <!-- ============================================================
         ALERT
         ============================================================ -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         PROGRESS
         ============================================================ -->
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

    <!-- ============================================================
         FORM
         ============================================================ -->
    <form method="POST" action="">
        <input type="hidden" name="simpan_kaih" value="1">

        <!-- 1. Bangun Pagi -->
        <div class="habit-item">
            <div class="label">
                <span class="icon">🌅</span> Bangun Pagi
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['bangun'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="bangun" value="1" <?php echo ($data_hari_ini && $data_hari_ini['bangun'] == 1) ? 'checked' : ''; ?> required> Ya
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['bangun'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="bangun" value="0" <?php echo ($data_hari_ini && $data_hari_ini['bangun'] == 0) ? 'checked' : ''; ?>> Tidak
                </label>
            </div>
        </div>

        <!-- 2. Beribadah -->
        <div class="habit-item">
            <div class="label">
                <span class="icon">🕌</span> Beribadah
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['ibadah'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="ibadah" value="1" <?php echo ($data_hari_ini && $data_hari_ini['ibadah'] == 1) ? 'checked' : ''; ?> required> Ya
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['ibadah'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="ibadah" value="0" <?php echo ($data_hari_ini && $data_hari_ini['ibadah'] == 0) ? 'checked' : ''; ?>> Tidak
                </label>
            </div>
        </div>

        <!-- 3. Berolahraga -->
        <div class="habit-item">
            <div class="label">
                <span class="icon">⚽</span> Berolahraga
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['olahraga'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="olahraga" value="1" <?php echo ($data_hari_ini && $data_hari_ini['olahraga'] == 1) ? 'checked' : ''; ?> required> Ya
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['olahraga'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="olahraga" value="0" <?php echo ($data_hari_ini && $data_hari_ini['olahraga'] == 0) ? 'checked' : ''; ?>> Tidak
                </label>
            </div>
        </div>

        <!-- 4. Sarapan Sehat -->
        <div class="habit-item">
            <div class="label">
                <span class="icon">🥗</span> Sarapan Sehat
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['sarapan'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="sarapan" value="1" <?php echo ($data_hari_ini && $data_hari_ini['sarapan'] == 1) ? 'checked' : ''; ?> required> Ya
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['sarapan'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="sarapan" value="0" <?php echo ($data_hari_ini && $data_hari_ini['sarapan'] == 0) ? 'checked' : ''; ?>> Tidak
                </label>
            </div>
        </div>

        <!-- 5. Gemar Belajar -->
        <div class="habit-item">
            <div class="label">
                <span class="icon">📚</span> Gemar Belajar
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['membaca'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="membaca" value="1" <?php echo ($data_hari_ini && $data_hari_ini['membaca'] == 1) ? 'checked' : ''; ?> required> Ya
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['membaca'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="membaca" value="0" <?php echo ($data_hari_ini && $data_hari_ini['membaca'] == 0) ? 'checked' : ''; ?>> Tidak
                </label>
            </div>
        </div>

        <!-- 6. Membantu Orang Tua -->
        <div class="habit-item">
            <div class="label">
                <span class="icon">🤝</span> Membantu Orang Tua
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['membantu'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="membantu" value="1" <?php echo ($data_hari_ini && $data_hari_ini['membantu'] == 1) ? 'checked' : ''; ?> required> Ya
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['membantu'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="membantu" value="0" <?php echo ($data_hari_ini && $data_hari_ini['membantu'] == 0) ? 'checked' : ''; ?>> Tidak
                </label>
            </div>
        </div>

        <!-- 7. Menabung -->
        <div class="habit-item">
            <div class="label">
                <span class="icon">💰</span> Menabung
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['menabung'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="menabung" value="1" <?php echo ($data_hari_ini && $data_hari_ini['menabung'] == 1) ? 'checked' : ''; ?> required> Ya
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['menabung'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="menabung" value="0" <?php echo ($data_hari_ini && $data_hari_ini['menabung'] == 0) ? 'checked' : ''; ?>> Tidak
                </label>
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-simpan">
            💾 <?php echo ($data_hari_ini) ? 'Update KAIH Hari Ini' : 'Simpan KAIH Hari Ini'; ?>
        </button>
    </form>

    <!-- ============================================================
         TIPS
         ============================================================ -->
    <div class="tips-box">
        <span class="sparkle">💡</span>
        <strong>Tips:</strong> Catat kebiasaan baikmu setiap hari! 
        Semakin lengkap, semakin <strong>keren</strong> karaktermu! 🚀
    </div>

</div>

<script>
// Styling otomatis saat radio dipilih
document.querySelectorAll('.habit-item input[type="radio"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var parent = this.closest('.options');
        parent.querySelectorAll('label').forEach(function(label) {
            label.classList.remove('checked-ya', 'checked-tidak');
        });
        if (this.checked) {
            var label = this.closest('label');
            if (this.value == 1) {
                label.classList.add('checked-ya');
            } else {
                label.classList.add('checked-tidak');
            }
        }
    });
});

// Animasi progress bar saat load
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
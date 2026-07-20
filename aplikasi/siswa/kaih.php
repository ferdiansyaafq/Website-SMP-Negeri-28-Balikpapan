<?php
// aplikasi/siswa/kaih.php
require_once '../includes/header-kaih.php';

$message = '';
$message_type = '';
$siswa_id = $_SESSION['siswa_id'] ?? 0;

// ============================================================
// AUTO-CREATE TABEL JIKA BELUM ADA
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
// PROSES SIMPAN KAIH (DIPERBAIKI)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_kaih'])) {
    $tanggal = date('Y-m-d');
    
    // === PERBAIKAN UTAMA: Gunakan pengecekan yang benar ===
    $bangun = isset($_POST['bangun']) && $_POST['bangun'] == 1 ? 1 : 0;
    $ibadah = isset($_POST['ibadah']) && $_POST['ibadah'] == 1 ? 1 : 0;
    $olahraga = isset($_POST['olahraga']) && $_POST['olahraga'] == 1 ? 1 : 0;
    $sarapan = isset($_POST['sarapan']) && $_POST['sarapan'] == 1 ? 1 : 0;
    $membaca = isset($_POST['membaca']) && $_POST['membaca'] == 1 ? 1 : 0;
    $membantu = isset($_POST['membantu']) && $_POST['membantu'] == 1 ? 1 : 0;
    $menabung = isset($_POST['menabung']) && $_POST['menabung'] == 1 ? 1 : 0;
    
    // Tambahan field opsional
    $ibadah_catatan = trim($_POST['ibadah_catatan'] ?? '');
    $olahraga_jenis = trim($_POST['olahraga_jenis'] ?? '');
    $sarapan_menu = trim($_POST['sarapan_menu'] ?? '');
    $membaca_judul = trim($_POST['membaca_judul'] ?? '');
    $membaca_menit = intval($_POST['membaca_menit'] ?? 0);
    $membantu_jenis = trim($_POST['membantu_jenis'] ?? '');
    $menabung_nominal = intval($_POST['menabung_nominal'] ?? 0);
    
    if ($siswa_id > 0) {
        try {
            // Cek apakah sudah ada data hari ini
            $stmt = $pdo->prepare("SELECT id FROM laporan_harian WHERE siswa_id = ? AND tanggal = ?");
            $stmt->execute([$siswa_id, $tanggal]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update data yang sudah ada
                $stmt = $pdo->prepare("UPDATE laporan_harian SET 
                    bangun = ?, ibadah = ?, ibadah_catatan = ?, 
                    olahraga = ?, olahraga_jenis = ?, 
                    sarapan = ?, sarapan_menu = ?, 
                    membaca = ?, membaca_judul = ?, membaca_menit = ?, 
                    membantu = ?, membantu_jenis = ?, 
                    menabung = ?, menabung_nominal = ?, 
                    updated_at = NOW() 
                    WHERE siswa_id = ? AND tanggal = ?");
                $stmt->execute([
                    $bangun, $ibadah, $ibadah_catatan,
                    $olahraga, $olahraga_jenis,
                    $sarapan, $sarapan_menu,
                    $membaca, $membaca_judul, $membaca_menit,
                    $membantu, $membantu_jenis,
                    $menabung, $menabung_nominal,
                    $siswa_id, $tanggal
                ]);
                $message = '✅ Data KAIH berhasil diperbarui!';
                $message_type = 'success';
            } else {
                // Insert data baru
                $stmt = $pdo->prepare("INSERT INTO laporan_harian (
                    siswa_id, tanggal, 
                    bangun, ibadah, ibadah_catatan, 
                    olahraga, olahraga_jenis, 
                    sarapan, sarapan_menu, 
                    membaca, membaca_judul, membaca_menit, 
                    membantu, membantu_jenis, 
                    menabung, menabung_nominal, 
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([
                    $siswa_id, $tanggal,
                    $bangun, $ibadah, $ibadah_catatan,
                    $olahraga, $olahraga_jenis,
                    $sarapan, $sarapan_menu,
                    $membaca, $membaca_judul, $membaca_menit,
                    $membantu, $membantu_jenis,
                    $menabung, $menabung_nominal
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

// Ambil data hari ini jika sudah ada
$data_hari_ini = null;
if ($siswa_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM laporan_harian WHERE siswa_id = ? AND tanggal = ?");
        $stmt->execute([$siswa_id, date('Y-m-d')]);
        $data_hari_ini = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Tabel mungkin belum ada
    }
}

// Hitung total KAIH hari ini
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
?>

<style>
    .kaih-form-container {
        max-width: 700px;
        margin: 0 auto;
    }
    .kaih-item {
        background: white;
        border-radius: 12px;
        padding: 18px 20px;
        margin-bottom: 12px;
        border-left: 4px solid #0284c7;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .kaih-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transform: translateX(4px);
    }
    .kaih-item .label {
        font-weight: 600;
        color: #1e293b;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .kaih-item .label .num {
        background: #0284c7;
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .kaih-item .options {
        display: flex;
        gap: 20px;
        align-items: center;
    }
    .kaih-item .options label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        color: #475569;
        padding: 6px 14px;
        border-radius: 20px;
        transition: all 0.2s;
    }
    .kaih-item .options label:hover {
        background: #f1f5f9;
    }
    .kaih-item .options label input[type="radio"] {
        width: 18px;
        height: 18px;
        accent-color: #0284c7;
        cursor: pointer;
    }
    .kaih-item .options label.checked-ya {
        background: #dcfce7;
        color: #16a34a;
    }
    .kaih-item .options label.checked-tidak {
        background: #fee2e2;
        color: #dc2626;
    }
    .btn-submit-kaih {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #0284c7, #0369a1);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 17px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 10px;
    }
    .btn-submit-kaih:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(2,132,199,0.3);
    }
    .alert {
        padding: 15px 18px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
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
    .progress-badge {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }
    .progress-badge.done { background: #dcfce7; color: #16a34a; }
    .progress-badge.pending { background: #fef3c7; color: #d97706; }
</style>

<div class="kaih-form-container">

    <!-- Progress Header -->
    <div style="background: white; border-radius: 16px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
        <div style="font-size: 14px; color: #64748b;">📊 Progress KAIH Hari Ini</div>
        <div style="font-size: 48px; font-weight: 800; color: #0284c7; margin: 5px 0;">
            <?php echo $total_terisi; ?><span style="font-size: 24px; color: #94a3b8;">/7</span>
        </div>
        <div style="width: 100%; background: #e2e8f0; height: 8px; border-radius: 4px; margin-top: 8px; overflow: hidden;">
            <div style="width: <?php echo ($total_terisi / 7) * 100; ?>%; background: linear-gradient(90deg, #0284c7, #10b981); height: 100%; border-radius: 4px; transition: width 0.5s;"></div>
        </div>
        <div style="margin-top: 10px;">
            <?php if ($total_terisi >= 7): ?>
                <span class="progress-badge done">✅ Lengkap! Pertahankan!</span>
            <?php elseif ($total_terisi >= 4): ?>
                <span class="progress-badge" style="background: #dbeafe; color: #2563eb;">📈 Sudah <?php echo $total_terisi; ?> dari 7</span>
            <?php elseif ($total_terisi > 0): ?>
                <span class="progress-badge pending">⏳ <?php echo $total_terisi; ?> dari 7 terisi</span>
            <?php else: ?>
                <span style="color: #94a3b8; font-size: 14px;">✨ Mulai catat kebiasaanmu hari ini!</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="simpan_kaih" value="1">
        
        <!-- 1. Bangun Pagi -->
        <div class="kaih-item">
            <div class="label">
                <span class="num">1</span>
                🌅 Bangun Pagi
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['bangun'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="bangun" value="1" <?php echo ($data_hari_ini && $data_hari_ini['bangun'] == 1) ? 'checked' : ''; ?> required>
                    <span>Ya</span>
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['bangun'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="bangun" value="0" <?php echo ($data_hari_ini && $data_hari_ini['bangun'] == 0) ? 'checked' : ''; ?>>
                    <span>Tidak</span>
                </label>
            </div>
        </div>

        <!-- 2. Beribadah -->
        <div class="kaih-item">
            <div class="label">
                <span class="num">2</span>
                🕌 Beribadah
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['ibadah'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="ibadah" value="1" <?php echo ($data_hari_ini && $data_hari_ini['ibadah'] == 1) ? 'checked' : ''; ?> required>
                    <span>Ya</span>
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['ibadah'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="ibadah" value="0" <?php echo ($data_hari_ini && $data_hari_ini['ibadah'] == 0) ? 'checked' : ''; ?>>
                    <span>Tidak</span>
                </label>
            </div>
        </div>

        <!-- 3. Berolahraga -->
        <div class="kaih-item">
            <div class="label">
                <span class="num">3</span>
                ⚽ Berolahraga
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['olahraga'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="olahraga" value="1" <?php echo ($data_hari_ini && $data_hari_ini['olahraga'] == 1) ? 'checked' : ''; ?> required>
                    <span>Ya</span>
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['olahraga'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="olahraga" value="0" <?php echo ($data_hari_ini && $data_hari_ini['olahraga'] == 0) ? 'checked' : ''; ?>>
                    <span>Tidak</span>
                </label>
            </div>
        </div>

        <!-- 4. Sarapan Sehat -->
        <div class="kaih-item">
            <div class="label">
                <span class="num">4</span>
                🥗 Sarapan Sehat
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['sarapan'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="sarapan" value="1" <?php echo ($data_hari_ini && $data_hari_ini['sarapan'] == 1) ? 'checked' : ''; ?> required>
                    <span>Ya</span>
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['sarapan'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="sarapan" value="0" <?php echo ($data_hari_ini && $data_hari_ini['sarapan'] == 0) ? 'checked' : ''; ?>>
                    <span>Tidak</span>
                </label>
            </div>
        </div>

        <!-- 5. Gemar Belajar -->
        <div class="kaih-item">
            <div class="label">
                <span class="num">5</span>
                📚 Gemar Belajar
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['membaca'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="membaca" value="1" <?php echo ($data_hari_ini && $data_hari_ini['membaca'] == 1) ? 'checked' : ''; ?> required>
                    <span>Ya</span>
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['membaca'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="membaca" value="0" <?php echo ($data_hari_ini && $data_hari_ini['membaca'] == 0) ? 'checked' : ''; ?>>
                    <span>Tidak</span>
                </label>
            </div>
        </div>

        <!-- 6. Membantu Orang Tua -->
        <div class="kaih-item">
            <div class="label">
                <span class="num">6</span>
                🤝 Membantu Orang Tua
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['membantu'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="membantu" value="1" <?php echo ($data_hari_ini && $data_hari_ini['membantu'] == 1) ? 'checked' : ''; ?> required>
                    <span>Ya</span>
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['membantu'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="membantu" value="0" <?php echo ($data_hari_ini && $data_hari_ini['membantu'] == 0) ? 'checked' : ''; ?>>
                    <span>Tidak</span>
                </label>
            </div>
        </div>

        <!-- 7. Tidur Tepat Waktu -->
        <div class="kaih-item">
            <div class="label">
                <span class="num">7</span>
                😴 Tidur Tepat Waktu
            </div>
            <div class="options">
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['menabung'] == 1) ? 'checked-ya' : ''; ?>">
                    <input type="radio" name="menabung" value="1" <?php echo ($data_hari_ini && $data_hari_ini['menabung'] == 1) ? 'checked' : ''; ?> required>
                    <span>Ya</span>
                </label>
                <label class="<?php echo ($data_hari_ini && $data_hari_ini['menabung'] == 0) ? 'checked-tidak' : ''; ?>">
                    <input type="radio" name="menabung" value="0" <?php echo ($data_hari_ini && $data_hari_ini['menabung'] == 0) ? 'checked' : ''; ?>>
                    <span>Tidak</span>
                </label>
            </div>
        </div>

        <!-- Tombol Submit -->
        <button type="submit" class="btn-submit-kaih">
            💾 <?php echo ($data_hari_ini) ? 'Update KAIH Hari Ini' : 'Simpan KAIH Hari Ini'; ?>
        </button>
    </form>

    <!-- Informasi tambahan -->
    <div style="margin-top: 20px; padding: 15px 20px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1; text-align: center;">
        <p style="color: #64748b; font-size: 13px; margin: 0;">
            💡 <strong>Tips:</strong> Catat kebiasaan baikmu setiap hari! 
            Semakin lengkap, semakin baik perkembangan karaktermu.
        </p>
    </div>

</div>

<script>
// Auto-check radio styling
document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var parent = this.closest('.options');
        // Reset semua label di group
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
</script>
<?php
?>
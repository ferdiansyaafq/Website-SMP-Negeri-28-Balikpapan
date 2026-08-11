<?php
// aplikasi/siswa/kaih.php
session_start();
require_once '../includes/header-kaih.php';
require_once '../../config/database.php';

$message = '';
$message_type = '';
$siswa_id = $_SESSION['siswa_id'] ?? 0;

// ============================================================
// AUTO-CREATE TABEL & UPDATE KOLOM JIKA BELUM ADA
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
            `olahraga_jenis` VARCHAR(255) NULL,
            `sarapan` TINYINT(1) NOT NULL DEFAULT 0,
            `sarapan_menu` VARCHAR(255) NULL,
            `membaca` TINYINT(1) NOT NULL DEFAULT 0,
            `membaca_judul` VARCHAR(255) NULL,
            `membaca_menit` INT NULL,
            `membantu` TINYINT(1) NOT NULL DEFAULT 0,
            `membantu_jenis` VARCHAR(255) NULL,
            `menabung` TINYINT(1) NOT NULL DEFAULT 0,
            `menabung_nominal` INT NULL,
            `orang_tua_validated_at` DATETIME NULL,
            `guru_validated_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_siswa_tanggal` (`siswa_id`, `tanggal`),
            INDEX `idx_tanggal` (`tanggal`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Cek dan tambahkan kolom bangun_catatan jika belum ada
        $checkCol = $pdo->query("SHOW COLUMNS FROM `laporan_harian` LIKE 'bangun_catatan'");
        if ($checkCol->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `laporan_harian` ADD `bangun_catatan` VARCHAR(255) NULL AFTER `bangun`");
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
ensureLaporanHarianTable($pdo);

// Ambil data hari ini DULUAN sebelum proses simpan
$tanggal_hari_ini = date('Y-m-d');
$data_hari_ini = null;
$is_validated = false;

if ($siswa_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM laporan_harian WHERE siswa_id = ? AND tanggal = ?");
        $stmt->execute([$siswa_id, $tanggal_hari_ini]);
        $data_hari_ini = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data_hari_ini) {
            $is_validated = !empty($data_hari_ini['orang_tua_validated_at']) || !empty($data_hari_ini['guru_validated_at']);
        }
    } catch (PDOException $e) {}
}

// ============================================================
// PROSES SIMPAN KAIH (Beserta Catatan Deskriptif)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_kaih'])) {
    if ($is_validated) {
        $message = 'Gagal menyimpan: Laporan hari ini sudah divalidasi dan tidak dapat diubah.';
        $message_type = 'error';
    } else {
        // Tangkap Radio Button
        $bangun = isset($_POST['bangun']) && $_POST['bangun'] == 1 ? 1 : 0;
        $ibadah = isset($_POST['ibadah']) && $_POST['ibadah'] == 1 ? 1 : 0;
        $olahraga = isset($_POST['olahraga']) && $_POST['olahraga'] == 1 ? 1 : 0;
        $sarapan = isset($_POST['sarapan']) && $_POST['sarapan'] == 1 ? 1 : 0;
        $membaca = isset($_POST['membaca']) && $_POST['membaca'] == 1 ? 1 : 0;
        $membantu = isset($_POST['membantu']) && $_POST['membantu'] == 1 ? 1 : 0;
        $menabung = isset($_POST['menabung']) && $_POST['menabung'] == 1 ? 1 : 0;
        
        // Tangkap Input Deskripsi/Catatan (Jika dikosongkan, default ke string kosong)
        $bangun_catatan = $bangun ? trim($_POST['bangun_catatan'] ?? '') : '';
        $ibadah_catatan = $ibadah ? trim($_POST['ibadah_catatan'] ?? '') : '';
        $olahraga_jenis = $olahraga ? trim($_POST['olahraga_jenis'] ?? '') : '';
        $sarapan_menu = $sarapan ? trim($_POST['sarapan_menu'] ?? '') : '';
        $membaca_judul = $membaca ? trim($_POST['membaca_judul'] ?? '') : '';
        $membantu_jenis = $membantu ? trim($_POST['membantu_jenis'] ?? '') : '';
        $menabung_nominal = $menabung ? intval($_POST['menabung_nominal'] ?? 0) : 0;
        
        if ($siswa_id > 0) {
            try {
                if ($data_hari_ini) {
                    $stmt = $pdo->prepare("UPDATE laporan_harian SET 
                        bangun = ?, bangun_catatan = ?, 
                        ibadah = ?, ibadah_catatan = ?, 
                        olahraga = ?, olahraga_jenis = ?, 
                        sarapan = ?, sarapan_menu = ?, 
                        membaca = ?, membaca_judul = ?, 
                        membantu = ?, membantu_jenis = ?, 
                        menabung = ?, menabung_nominal = ?, 
                        updated_at = NOW() WHERE siswa_id = ? AND tanggal = ?");
                    $stmt->execute([
                        $bangun, $bangun_catatan, $ibadah, $ibadah_catatan, $olahraga, $olahraga_jenis, 
                        $sarapan, $sarapan_menu, $membaca, $membaca_judul, $membantu, $membantu_jenis, 
                        $menabung, $menabung_nominal, $siswa_id, $tanggal_hari_ini
                    ]);
                    $message = '✨ Data KAIH dan detail kegiatan berhasil diperbarui!';
                    $message_type = 'success';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO laporan_harian (
                        siswa_id, tanggal, bangun, bangun_catatan, ibadah, ibadah_catatan, olahraga, olahraga_jenis, 
                        sarapan, sarapan_menu, membaca, membaca_judul, membantu, membantu_jenis, menabung, menabung_nominal, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([
                        $siswa_id, $tanggal_hari_ini, $bangun, $bangun_catatan, $ibadah, $ibadah_catatan, $olahraga, $olahraga_jenis, 
                        $sarapan, $sarapan_menu, $membaca, $membaca_judul, $membantu, $membantu_jenis, $menabung, $menabung_nominal
                    ]);
                    $message = '✨ Laporan harianmu berhasil disimpan! Terus pertahankan ya!';
                    $message_type = 'success';
                }
                
                // Refresh data
                $stmt = $pdo->prepare("SELECT * FROM laporan_harian WHERE siswa_id = ? AND tanggal = ?");
                $stmt->execute([$siswa_id, $tanggal_hari_ini]);
                $data_hari_ini = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $message = '❌ Gagal menyimpan data: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

$total_terisi = 0;
if ($data_hari_ini) {
    $total_terisi = ($data_hari_ini['bangun'] ?? 0) + ($data_hari_ini['ibadah'] ?? 0) + ($data_hari_ini['olahraga'] ?? 0) + ($data_hari_ini['sarapan'] ?? 0) + ($data_hari_ini['membaca'] ?? 0) + ($data_hari_ini['membantu'] ?? 0) + ($data_hari_ini['menabung'] ?? 0);
}

// ============================================================
// QUERY AMBIL RIWAYAT KAIH SISWA (30 HARI TERAKHIR)
// ============================================================
$riwayat_kaih = [];
if ($siswa_id > 0) {
    try {
        $stmtR = $pdo->prepare("SELECT * FROM laporan_harian WHERE siswa_id = ? ORDER BY tanggal DESC LIMIT 30");
        $stmtR->execute([$siswa_id]);
        $riwayat_kaih = $stmtR->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}
?>

<style>
    .kaih-form-container { max-width: 700px; margin: 0 auto 40px; }
    
    /* Perubahan Struktur Card untuk Input Deskripsi */
    .kaih-item-wrapper { background: white; border-radius: 12px; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-left: 4px solid #0284c7; overflow: hidden; transition: all 0.3s; }
    .kaih-item-wrapper:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .kaih-item { padding: 18px 20px; display: flex; justify-content: space-between; align-items: center; }
    
    .kaih-desc { padding: 0 20px 18px 20px; border-top: 1px dashed #e2e8f0; margin-top: -5px; display: none; }
    .desc-input { width: 100%; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: #f8fafc; color: #1e293b; box-sizing: border-box; }
    .desc-input:focus { border-color: #0284c7; background: white; }
    
    .kaih-item .label { font-weight: 600; color: #1e293b; font-size: 15px; display: flex; align-items: center; gap: 10px; }
    .kaih-item .label .num { background: #0284c7; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
    .kaih-item .options { display: flex; gap: 20px; align-items: center; }
    .kaih-item .options label { display: flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 500; cursor: pointer; color: #475569; padding: 6px 14px; border-radius: 20px; transition: all 0.2s; }
    .kaih-item .options label input[type="radio"] { width: 18px; height: 18px; accent-color: #0284c7; cursor: pointer; }
    .kaih-item .options label.checked-ya { background: #dcfce7; color: #16a34a; }
    .kaih-item .options label.checked-tidak { background: #fee2e2; color: #dc2626; }
    
    .btn-submit-kaih { width: 100%; padding: 14px; background: linear-gradient(135deg, #0284c7, #0369a1); color: white; border: none; border-radius: 12px; font-size: 17px; font-weight: 700; cursor: pointer; margin-top: 10px; }
    .btn-disabled { background: #cbd5e1 !important; color: #64748b !important; cursor: not-allowed !important; }
    .alert { padding: 15px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    
    .table-modern { width: 100%; border-collapse: collapse; font-size: 14px; }
    .table-modern th { background: #f1f5f9; padding: 12px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
    .table-modern td { padding: 12px; border-bottom: 1px solid #e2e8f0; color: #334155; }
</style>

<div class="kaih-form-container">
    
    <!-- Progress Header dengan Status Validasi -->
    <div style="background: white; border-radius: 16px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
        <div style="font-size: 14px; color: #64748b;">📈 Progress KAIH Hari Ini</div>
        <div style="font-size: 48px; font-weight: 800; color: #0284c7; margin: 5px 0;">
            <?= $total_terisi ?><span style="font-size: 24px; color: #94a3b8;">/7</span>
        </div>
        <div style="width: 100%; background: #e2e8f0; height: 8px; border-radius: 4px; margin-top: 8px; overflow: hidden;">
            <div style="width: <?= ($total_terisi / 7) * 100 ?>%; background: linear-gradient(90deg, #0284c7, #10b981); height: 100%; border-radius: 4px; transition: width 0.5s;"></div>
        </div>
        
        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
            <div style="font-size: 12px; color: #64748b; margin-bottom: 8px; text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Status Laporan:</div>
            <?php if (!$data_hari_ini): ?>
                <span style="background: #f1f5f9; color: #475569; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 700;">📝 Belum Mengisi</span>
            <?php elseif ($is_validated): ?>
                <span style="background: #dcfce7; color: #16a34a; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 700;">✔️ Sudah Divalidasi</span>
            <?php else: ?>
                <span style="background: #fef3c7; color: #d97706; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 700;">⏳ Menunggu Validasi</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
    <?php endif; ?>

    <!-- FORM PENGISIAN DENGAN DESKRIPSI -->
    <form method="POST" action="">
        <input type="hidden" name="simpan_kaih" value="1">
        
        <?php
        // Data struktur form
        $items = [
            ['name' => 'bangun', 'label' => '🌅 Bangun Pagi', 'desc_name' => 'bangun_catatan', 'desc_ph' => 'Jam berapa kamu bangun? (Misal: 05.00 pagi)', 'type' => 'text'],
            ['name' => 'ibadah', 'label' => '🕌 Beribadah', 'desc_name' => 'ibadah_catatan', 'desc_ph' => 'Ibadah apa saja yang dilakukan hari ini?', 'type' => 'text'],
            ['name' => 'olahraga', 'label' => '⚽ Berolahraga', 'desc_name' => 'olahraga_jenis', 'desc_ph' => 'Olahraga apa? (Misal: Senam / Lari / Bersepeda)', 'type' => 'text'],
            ['name' => 'sarapan', 'label' => '🍳 Sarapan Sehat', 'desc_name' => 'sarapan_menu', 'desc_ph' => 'Makan pakai menu sehat apa hari ini?', 'type' => 'text'],
            ['name' => 'membaca', 'label' => '📖 Gemar Belajar', 'desc_name' => 'membaca_judul', 'desc_ph' => 'Judul Buku atau materi apa yang kamu baca/pelajari?', 'type' => 'text'],
            ['name' => 'membantu', 'label' => '🧹 Membantu Ortu', 'desc_name' => 'membantu_jenis', 'desc_ph' => 'Membantu melakukan apa? (Misal: Menyapu lantai)', 'type' => 'text'],
            ['name' => 'menabung', 'label' => '💰 Menabung', 'desc_name' => 'menabung_nominal', 'desc_ph' => 'Nominal tabungan hari ini (Misal: 5000)', 'type' => 'number']
        ];
        
        $no = 1;
        foreach ($items as $item):
            $isCheckedYa = ($data_hari_ini && $data_hari_ini[$item['name']] == 1);
            $isCheckedTidak = ($data_hari_ini && $data_hari_ini[$item['name']] == 0 && $data_hari_ini !== null);
        ?>
        <div class="kaih-item-wrapper">
            <div class="kaih-item">
                <div class="label"><span class="num"><?= $no++ ?></span> <?= $item['label'] ?></div>
                <div class="options">
                    <label class="<?= $isCheckedYa ? 'checked-ya' : '' ?>">
                        <input type="radio" name="<?= $item['name'] ?>" value="1" <?= $isCheckedYa ? 'checked' : '' ?> <?= $is_validated ? 'disabled' : 'required' ?>> Ya
                    </label>
                    <label class="<?= $isCheckedTidak ? 'checked-tidak' : '' ?>">
                        <input type="radio" name="<?= $item['name'] ?>" value="0" <?= $isCheckedTidak ? 'checked' : '' ?> <?= $is_validated ? 'disabled' : '' ?>> Tidak
                    </label>
                </div>
            </div>
            <!-- Kotak Deskripsi (Muncul jika Ya) -->
            <div class="kaih-desc" style="display: <?= $isCheckedYa ? 'block' : 'none' ?>;">
                <input type="<?= $item['type'] ?>" 
                       name="<?= $item['desc_name'] ?>" 
                       class="desc-input" 
                       placeholder="<?= $item['desc_ph'] ?>" 
                       value="<?= htmlspecialchars($data_hari_ini[$item['desc_name']] ?? '') ?>" 
                       <?= $is_validated ? 'disabled' : '' ?>>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($is_validated): ?>
            <button type="button" class="btn-submit-kaih btn-disabled">🔒 Laporan Hari Ini Telah Divalidasi</button>
        <?php else: ?>
            <button type="submit" class="btn-submit-kaih"><?= ($data_hari_ini) ? 'Update KAIH Hari Ini' : 'Simpan KAIH Hari Ini' ?></button>
        <?php endif; ?>
    </form>

    <!-- RIWAYAT & STATUS VALIDASI SISWA LENGKAP -->
    <div style="background: white; border-radius: 16px; padding: 25px; margin-top: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 18px; margin-bottom: 15px;">📅 Riwayat & Status Laporan KAIH</h3>
        
        <?php if(empty($riwayat_kaih)): ?>
            <div style="text-align: center; color: #94a3b8; padding: 20px; border: 1px dashed #cbd5e1; border-radius: 8px;">Kamu belum pernah mengisi laporan KAIH.</div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach($riwayat_kaih as $idx => $lh): 
                    $skor = $lh['bangun'] + $lh['ibadah'] + $lh['olahraga'] + $lh['sarapan'] + $lh['membaca'] + $lh['membantu'] + $lh['menabung'];
                    $is_val = !empty($lh['orang_tua_validated_at']) || !empty($lh['guru_validated_at']);
                    $dayId = 'history-' . $idx;
                ?>
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <div style="background: #f8fafc; padding: 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: bold;" onclick="document.getElementById('<?= $dayId ?>').style.display = document.getElementById('<?= $dayId ?>').style.display === 'block' ? 'none' : 'block'">
                        <div>
                            📅 <?= date('d M Y', strtotime($lh['tanggal'])) ?> 
                            <span style="background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 12px; font-weight: 700; font-size: 11px; margin-left: 10px;"><?= $skor ?>/7 Kegiatan</span>
                        </div>
                        <div style="font-size:12px; font-weight:normal;">
                            <?= $is_val ? '<span style="color:#16a34a; font-weight:bold;">✔️ Divalidasi</span>' : '<span style="color:#f59e0b; font-weight:bold;">⏳ Menunggu</span>' ?>
                        </div>
                    </div>
                    
                    <div id="<?= $dayId ?>" style="padding: 15px; display: none; background: #fff; border-top: 1px solid #e2e8f0;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                            <div style="background: #f1f5f9; padding: 10px; border-radius: 6px;"><strong>🌅 Bangun Pagi</strong><br><span style="font-size:12px; color:#64748b;"><?= $lh['bangun'] ? htmlspecialchars($lh['bangun_catatan'] ?? 'Tepat Waktu') : 'Belum' ?></span></div>
                            <div style="background: #f1f5f9; padding: 10px; border-radius: 6px;"><strong>🕌 Ibadah</strong><br><span style="font-size:12px; color:#64748b;"><?= $lh['ibadah'] ? htmlspecialchars($lh['ibadah_catatan'] ?? 'Sudah') : 'Belum' ?></span></div>
                            <div style="background: #f1f5f9; padding: 10px; border-radius: 6px;"><strong>⚽ Olahraga</strong><br><span style="font-size:12px; color:#64748b;"><?= $lh['olahraga'] ? htmlspecialchars($lh['olahraga_jenis'] ?? 'Sudah') : 'Belum' ?></span></div>
                            <div style="background: #f1f5f9; padding: 10px; border-radius: 6px;"><strong>🍳 Sarapan</strong><br><span style="font-size:12px; color:#64748b;"><?= $lh['sarapan'] ? htmlspecialchars($lh['sarapan_menu'] ?? 'Sudah') : 'Belum' ?></span></div>
                            <div style="background: #f1f5f9; padding: 10px; border-radius: 6px;"><strong>📖 Belajar</strong><br><span style="font-size:12px; color:#64748b;"><?= $lh['membaca'] ? htmlspecialchars($lh['membaca_judul'] ?? 'Sudah') : 'Belum' ?></span></div>
                            <div style="background: #f1f5f9; padding: 10px; border-radius: 6px;"><strong>🧹 Membantu Ortu</strong><br><span style="font-size:12px; color:#64748b;"><?= $lh['membantu'] ? htmlspecialchars($lh['membantu_jenis'] ?? 'Sudah') : 'Belum' ?></span></div>
                            <div style="background: #f1f5f9; padding: 10px; border-radius: 6px;"><strong>💰 Menabung</strong><br><span style="font-size:12px; color:#64748b;"><?= $lh['menabung'] ? 'Rp ' . number_format((int)$lh['menabung_nominal'], 0, ',', '.') : 'Belum' ?></span></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
<?php if (!$is_validated): ?>
document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var wrapper = this.closest('.kaih-item-wrapper');
        var descBox = wrapper.querySelector('.kaih-desc');
        var descInput = wrapper.querySelector('.desc-input');
        
        // Reset warna label
        var parent = this.closest('.options');
        parent.querySelectorAll('label').forEach(function(label) { 
            label.classList.remove('checked-ya', 'checked-tidak'); 
        });
        
        if (this.checked) {
            var label = this.closest('label');
            if (this.value == 1) { 
                label.classList.add('checked-ya'); 
                if (descBox) {
                    descBox.style.display = 'block';
                    if (descInput) descInput.setAttribute('required', 'required');
                }
            } else { 
                label.classList.add('checked-tidak'); 
                if (descBox) {
                    descBox.style.display = 'none';
                    if (descInput) descInput.removeAttribute('required');
                }
            }
        }
    });
});
<?php endif; ?>
</script>
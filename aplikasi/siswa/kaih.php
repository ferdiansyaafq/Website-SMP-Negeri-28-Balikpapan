<?php
// aplikasi/siswa/kaih.php
require_once '../includes/header-kaih.php';

$message = '';
$message_type = '';
$siswa_id = $_SESSION['siswa_id'] ?? 0;
$nama_siswa = 'Siswa';

function cekWaktuKAIH() {
    // Set timezone ke WITA (Asia/Makassar)
    date_default_timezone_set('Asia/Makassar');
    
    $hari = date('N'); 
    $jam = date('H:i'); 
    
    if ($jam < '08:00' || $jam > '21:00') {
        return [
            'status' => false,
            'pesan' => '❌ Form KAIH hanya dapat diisi pukul 08.00 - 21.00 WITA!'
        ];
    }
    
    return ['status' => true];
}

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
        // Pastikan kolom jenis olahraga, ibadah catatan, dan membantu jenis berukuran cukup
        $pdo->exec("ALTER TABLE laporan_harian MODIFY COLUMN olahraga_jenis VARCHAR(255) NULL");
        $pdo->exec("ALTER TABLE laporan_harian MODIFY COLUMN membantu_jenis VARCHAR(255) NULL");
        $pdo->exec("ALTER TABLE laporan_harian MODIFY COLUMN ibadah_catatan VARCHAR(255) NULL");
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
    
    // ============================================================
    // CEK WAKTU KAIH
    // ============================================================
    $cek = cekWaktuKAIH();
    if (!$cek['status']) {
        $message = $cek['pesan'];
        $message_type = 'error';
    } else {
        // Lanjutkan proses simpan KAIH
        $tanggal = date('Y-m-d');
        
        $bangun = isset($_POST['bangun']) && $_POST['bangun'] == 1 ? 1 : 0;
        $ibadah = isset($_POST['ibadah']) && $_POST['ibadah'] == 1 ? 1 : 0;
        $olahraga = isset($_POST['olahraga']) && $_POST['olahraga'] == 1 ? 1 : 0;
        $sarapan = isset($_POST['sarapan']) && $_POST['sarapan'] == 1 ? 1 : 0;
        $membaca = isset($_POST['membaca']) && $_POST['membaca'] == 1 ? 1 : 0;
        $membantu = isset($_POST['membantu']) && $_POST['membantu'] == 1 ? 1 : 0;
        $menabung = isset($_POST['menabung']) && $_POST['menabung'] == 1 ? 1 : 0;
        
        // 1. Bangun Pagi
        $bangun_keterangan = ($bangun == 1) ? trim($_POST['bangun_keterangan'] ?? '') : '';
        
        // 2. Beribadah (Sholat 5 Waktu & Kegiatan Lainnya)
        $ibadah_catatan = '';
        if ($ibadah == 1) {
            $sholat_post = $_POST['sholat'] ?? [];
            if (!is_array($sholat_post)) $sholat_post = [];
            $valid_sholat = ['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'];
            $sholat_terpilih = array_intersect($valid_sholat, $sholat_post);
            
            $ibadah_lainnya = trim($_POST['ibadah_lainnya'] ?? '');
            
            $ibadah_parts = [];
            if (!empty($sholat_terpilih)) {
                $count_sholat = count($sholat_terpilih);
                $status_lengkap = ($count_sholat === 5) ? ' (5/5 Lengkap)' : " ($count_sholat/5 Waktu)";
                $ibadah_parts[] = 'Sholat: ' . implode(', ', $sholat_terpilih) . $status_lengkap;
            }
            if (!empty($ibadah_lainnya)) {
                $ibadah_parts[] = 'Kegiatan Lain: ' . $ibadah_lainnya;
            }
            $ibadah_catatan = implode(' | ', $ibadah_parts);
        }
        
        // 3. Berolahraga
        $olahraga_jenis = ($olahraga == 1) ? trim($_POST['olahraga_jenis'] ?? '') : '';
        
        // 4. Gemar Membaca (Fiksi & Non Fiksi)
        $membaca_judul = '';
        if ($membaca == 1) {
            $baca_parts = [];
            $baca_fiksi = isset($_POST['baca_fiksi']) && $_POST['baca_fiksi'] == 1;
            $judul_fiksi = trim($_POST['judul_fiksi'] ?? '');
            if ($baca_fiksi || !empty($judul_fiksi)) {
                $baca_parts[] = '[Fiksi] ' . ($judul_fiksi !== '' ? $judul_fiksi : '-');
            }
            
            $baca_nonfiksi = isset($_POST['baca_nonfiksi']) && $_POST['baca_nonfiksi'] == 1;
            $judul_nonfiksi = trim($_POST['judul_nonfiksi'] ?? '');
            if ($baca_nonfiksi || !empty($judul_nonfiksi)) {
                $baca_parts[] = '[Non Fiksi] ' . ($judul_nonfiksi !== '' ? $judul_nonfiksi : '-');
            }
            
            $membaca_judul = implode(' ; ', $baca_parts);
        }
        
        // 5. Membantu Orang Tua
        $membantu_jenis = ($membantu == 1) ? trim($_POST['membantu_jenis'] ?? '') : '';
        
        // 6. Menabung
        $menabung_keterangan = ($menabung == 1) ? trim($_POST['menabung_keterangan'] ?? '') : '';
        
        if ($siswa_id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM laporan_harian WHERE siswa_id = ? AND tanggal = ?");
                $stmt->execute([$siswa_id, $tanggal]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $stmt = $pdo->prepare("UPDATE laporan_harian SET 
                        bangun = ?, ibadah = ?, olahraga = ?, sarapan = ?, 
                        membaca = ?, membantu = ?, menabung = ?,
                        bangun_keterangan = ?, ibadah_catatan = ?, olahraga_jenis = ?,
                        membaca_judul = ?, membantu_jenis = ?, menabung_keterangan = ?,
                        updated_at = NOW() 
                        WHERE siswa_id = ? AND tanggal = ?");
                    $stmt->execute([
                        $bangun, $ibadah, $olahraga, $sarapan, 
                        $membaca, $membantu, $menabung,
                        $bangun_keterangan, $ibadah_catatan, $olahraga_jenis,
                        $membaca_judul, $membantu_jenis, $menabung_keterangan,
                        $siswa_id, $tanggal
                    ]);
                    $message = '✅ Data KAIH berhasil diperbarui!';
                    $message_type = 'success';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO laporan_harian (
                        siswa_id, tanggal, bangun, ibadah, olahraga, sarapan, membaca, membantu, menabung,
                        bangun_keterangan, ibadah_catatan, olahraga_jenis,
                        membaca_judul, membantu_jenis, menabung_keterangan,
                        created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([
                        $siswa_id, $tanggal,
                        $bangun, $ibadah, $olahraga, $sarapan, $membaca, $membantu, $menabung,
                        $bangun_keterangan, $ibadah_catatan, $olahraga_jenis,
                        $membaca_judul, $membantu_jenis, $menabung_keterangan
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

// Ekstraksi data Ibadah untuk form hari ini
$saved_ibadah_catatan = $data_hari_ini['ibadah_catatan'] ?? '';
$sholat_checked = [
    'Subuh' => (stripos($saved_ibadah_catatan, 'Subuh') !== false),
    'Dzuhur' => (stripos($saved_ibadah_catatan, 'Dzuhur') !== false),
    'Ashar' => (stripos($saved_ibadah_catatan, 'Ashar') !== false),
    'Maghrib' => (stripos($saved_ibadah_catatan, 'Maghrib') !== false),
    'Isya' => (stripos($saved_ibadah_catatan, 'Isya') !== false),
];
$saved_ibadah_lainnya = '';
if (preg_match('/Kegiatan Lain:\s*(.*?)(?:\||$)/i', $saved_ibadah_catatan, $m_lain)) {
    $saved_ibadah_lainnya = trim($m_lain[1]);
} elseif (!empty($saved_ibadah_catatan) && !preg_match('/Sholat:/i', $saved_ibadah_catatan)) {
    $saved_ibadah_lainnya = $saved_ibadah_catatan;
}

// Ekstraksi data Olahraga
$saved_olahraga_jenis = $data_hari_ini['olahraga_jenis'] ?? '';

// Ekstraksi data Membaca (Fiksi & Non Fiksi)
$saved_membaca_judul = $data_hari_ini['membaca_judul'] ?? '';
$is_fiksi_checked = (stripos($saved_membaca_judul, '[Fiksi]') !== false);
$is_nonfiksi_checked = (stripos($saved_membaca_judul, '[Non Fiksi]') !== false);
$saved_judul_fiksi = '';
$saved_judul_nonfiksi = '';

if (preg_match('/\[Fiksi\]\s*([^;]+)/i', $saved_membaca_judul, $mf)) {
    $saved_judul_fiksi = trim($mf[1]);
    if ($saved_judul_fiksi === '-') $saved_judul_fiksi = '';
    $is_fiksi_checked = true;
}
if (preg_match('/\[Non\s*Fiksi\]\s*([^;]+)/i', $saved_membaca_judul, $mnf)) {
    $saved_judul_nonfiksi = trim($mnf[1]);
    if ($saved_judul_nonfiksi === '-') $saved_judul_nonfiksi = '';
    $is_nonfiksi_checked = true;
}
if (!$is_fiksi_checked && !$is_nonfiksi_checked && !empty($saved_membaca_judul)) {
    $is_nonfiksi_checked = true;
    $saved_judul_nonfiksi = $saved_membaca_judul;
}

// Ekstraksi data Membantu Orang Tua
$saved_membantu_jenis = $data_hari_ini['membantu_jenis'] ?? '';

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

    /* ============================================================
       HEADER + PROGRESS - MODERN & DEWASA (1 KOTAK)
       ============================================================ */
    .kaih-header {
        background: linear-gradient(135deg, #0f172a, #1e293b, #334155);
        border-radius: 20px;
        padding: 28px 28px 24px;
        margin-bottom: 20px;
        color: white;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(15, 23, 42, 0.25);
        border: 1px solid rgba(255,255,255,0.05);
    }

    .kaih-header::before {
        content: '';
        position: absolute;
        top: -60%;
        right: -20%;
        width: 280px;
        height: 280px;
        background: radial-gradient(circle, rgba(56, 189, 248, 0.08), transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .kaih-header::after {
        content: '';
        position: absolute;
        bottom: -40%;
        left: -10%;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.06), transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .header-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        z-index: 1;
    }

    .header-greeting {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .greeting-emoji {
        font-size: 32px;
        line-height: 1;
    }

    .greeting-text {
        display: flex;
        flex-direction: column;
    }

    .greeting-name {
        font-size: 20px;
        font-weight: 700;
        letter-spacing: -0.3px;
        line-height: 1.2;
    }

    .greeting-sub {
        font-size: 13px;
        opacity: 0.7;
        font-weight: 400;
        letter-spacing: 0.2px;
    }

    .header-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,0.08);
        backdrop-filter: blur(8px);
        padding: 6px 16px 6px 12px;
        border-radius: 30px;
        border: 1px solid rgba(255,255,255,0.06);
    }

    .badge-icon {
        font-size: 14px;
    }

    .badge-text {
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        opacity: 0.8;
    }

    .header-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
        margin: 16px 0 18px;
        position: relative;
        z-index: 1;
    }

    /* ===== PROGRESS DI DALAM HEADER ===== */
    .header-progress {
        position: relative;
        z-index: 1;
    }

    .progress-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 10px;
    }

    .progress-number {
        display: flex;
        align-items: baseline;
        gap: 2px;
    }

    .number-main {
        font-size: 28px;
        font-weight: 800;
        color: white;
        line-height: 1;
        letter-spacing: -0.5px;
    }

    .number-total {
        font-size: 16px;
        font-weight: 600;
        opacity: 0.5;
        margin-left: 2px;
    }

    .progress-percentage {
        font-size: 18px;
        font-weight: 700;
        color: #38bdf8;
        letter-spacing: -0.3px;
    }

    .progress-bar-track {
        width: 100%;
        height: 6px;
        background: rgba(255,255,255,0.1);
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 12px;
    }

    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #38bdf8, #0284c7, #6366f1);
        border-radius: 10px;
        transition: width 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        width: 0%;
    }

    .progress-status {
        font-size: 13px;
        font-weight: 500;
        opacity: 0.8;
        display: inline-block;
    }

    .progress-status.status-done {
        color: #4ade80;
    }

    .progress-status.status-pending {
        color: #38bdf8;
    }

    .progress-status.status-empty {
        color: rgba(255,255,255,0.4);
    }

    /* ============================================================
       HABIT ITEMS - TIDAK DIUBAH (SESUAI PERMINTAAN)
       ============================================================ */
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

    /* ============================================================
       RESPONSIVE
       ============================================================ */
    @media (max-width: 500px) {
        .kaih-container { padding: 0 6px; }
        
        .kaih-header {
            padding: 20px 18px 18px;
        }
        
        .header-top {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        
        .header-badge {
            align-self: flex-start;
        }
        
        .greeting-name {
            font-size: 18px;
        }
        
        .greeting-sub {
            font-size: 12px;
        }
        
        .greeting-emoji {
            font-size: 26px;
        }
        
        .number-main {
            font-size: 24px;
        }
        
        .number-total {
            font-size: 14px;
        }
        
        .progress-percentage {
            font-size: 16px;
        }
        
        .progress-row {
            margin-bottom: 8px;
        }
        
        .progress-status {
            font-size: 12px;
        }
        
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
    }

    @media (max-width: 400px) {
        .kaih-header {
            padding: 16px 14px 14px;
        }
        
        .greeting-name {
            font-size: 16px;
        }
        
        .greeting-sub {
            font-size: 11px;
        }
        
        .greeting-emoji {
            font-size: 22px;
        }
        
        .header-badge {
            padding: 4px 12px 4px 10px;
        }
        
        .badge-text {
            font-size: 10px;
        }
        
        .number-main {
            font-size: 20px;
        }
        
        .number-total {
            font-size: 13px;
        }
        
        .progress-percentage {
            font-size: 14px;
        }
        
        .progress-status {
            font-size: 11px;
        }
        
        .habit-item .options label { padding: 3px 10px; font-size: 12px; }
        .habit-item .label { font-size: 13px; }
        .habit-item .label .icon { width: 24px; height: 24px; font-size: 14px; }
        .habit-item .description { padding-left: 34px; font-size: 10px; }
        .habit-item .habit-right { padding-left: 34px; }
        .habit-item .keterangan-wrapper textarea { font-size: 12px; padding: 6px 10px; min-height: 40px; }
    }
</style>

<div class="kaih-container">

    <!-- ============================================================
         HEADER + PROGRESS - MODERN & DEWASA (1 KOTAK)
         ============================================================ -->
    <div class="kaih-header">
        <div class="header-top">
            <div class="header-greeting">
                <span class="greeting-emoji">👋</span>
                <div class="greeting-text">
                    <div class="greeting-name">Halo, <?php echo htmlspecialchars($nama_siswa); ?></div>
                    <div class="greeting-sub">Catat 7 kebiasaan baikmu hari ini</div>
                </div>
            </div>
            <div class="header-badge">
                <span class="badge-icon">📋</span>
                <span class="badge-text">KAIH</span>
            </div>
        </div>
        
        <div class="header-divider"></div>
        
        <!-- Progress di dalam header -->
        <div class="header-progress">
            <div class="progress-row">
                <div class="progress-number">
                    <span class="number-main"><?php echo $total_terisi; ?></span>
                    <span class="number-total">/ 7</span>
                </div>
                <div class="progress-percentage">
                    <?php echo round(($total_terisi / 7) * 100); ?>%
                </div>
            </div>
            <div class="progress-bar-track">
                <div class="progress-bar-fill" style="width: <?php echo ($total_terisi / 7) * 100; ?>%;"></div>
            </div>
            <div class="progress-status 
                <?php echo $total_terisi >= 7 ? 'status-done' : ($total_terisi > 0 ? 'status-pending' : 'status-empty'); ?>">
                <?php if ($total_terisi >= 7): ?>
                    ✅ Selesai! Kamu hebat!
                <?php elseif ($total_terisi >= 4): ?>
                    ⏳ <?php echo $total_terisi; ?> dari 7 selesai
                <?php elseif ($total_terisi > 0): ?>
                    🌱 <?php echo $total_terisi; ?> dari 7 terisi
                <?php else: ?>
                    ✨ Belum ada yang dicatat
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         FORM KAIH - TIDAK DIUBAH
         ============================================================ -->
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
            <div class="keterangan-wrapper <?php echo ($data_hari_ini && $data_hari_ini['ibadah'] == 1) ? 'show' : ''; ?>" style="margin-top: 14px;">
                <span class="label-keterangan" style="display:block; margin-bottom:10px; font-weight: 600; font-size: 14px; color: #1e293b;">🕌 Untuk yang beragama Islam, tandai sholat 5 waktu yang dikerjakan:</span>
                <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom: 18px;">
                    <?php
                        $sholat_list = ['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'];
                        $ibadah_catatan = $data_hari_ini['ibadah_catatan'] ?? '';
                        foreach ($sholat_list as $s) {
                            $checked = (strpos($ibadah_catatan, $s) !== false) ? 'checked' : '';
                            echo "<label style='display:inline-flex; align-items:center; gap:8px; padding:10px 16px; background:#f8fafc; border:1.5px solid #cbd5e1; border-radius:10px; font-size:14px; font-weight:600; color:#334155; cursor:pointer; transition:all 0.2s;'>
                                    <input type='checkbox' name='sholat[]' value='$s' $checked style='width:18px; height:18px; accent-color:#059669; cursor:pointer;'> $s
                                  </label>";
                        }
                    ?>
                </div>
                <span class="label-keterangan" style="display:block; margin-bottom:8px; font-weight: 600; font-size: 14px; color: #1e293b;">🙏 Untuk yang non-Islam (Kegiatan Lainnya):</span>
                <textarea name="ibadah_lainnya" placeholder="Jelaskan kegiatan ibadah/keagamaan yang dilakukan..."><?php 
                    if (strpos($ibadah_catatan, 'Kegiatan Lain: ') !== false) {
                        $parts = explode('Kegiatan Lain: ', $ibadah_catatan);
                        echo htmlspecialchars($parts[1]);
                    }
                ?></textarea>
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
            <div class="keterangan-wrapper <?php echo ($data_hari_ini && $data_hari_ini['olahraga'] == 1) ? 'show' : ''; ?>">
                <span class="label-keterangan">🏃 Apa minat atau jenis olahraga yang kamu lakukan?</span>
                <textarea name="olahraga_jenis" placeholder="Contoh: Bermain sepak bola, lari pagi, senam, dll..."><?php echo htmlspecialchars($data_hari_ini['olahraga_jenis'] ?? ''); ?></textarea>
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
                <span class="label-keterangan" style="display:block; margin-bottom:5px;">📖 Apa yang kamu pelajari / baca hari ini?</span>
                
                <?php
                    $baca_judul = $data_hari_ini['membaca_judul'] ?? '';
                    $is_fiksi = strpos($baca_judul, '[Fiksi]') !== false;
                    $is_nonfiksi = strpos($baca_judul, '[Non Fiksi]') !== false;
                    
                    $judul_fiksi = '';
                    $judul_nonfiksi = '';
                    
                    if ($is_fiksi) {
                        preg_match('/\[Fiksi\](.*?)(;|$)/', $baca_judul, $matches);
                        $judul_fiksi = trim($matches[1] ?? '');
                        if ($judul_fiksi == '-') $judul_fiksi = '';
                    }
                    if ($is_nonfiksi) {
                        preg_match('/\[Non Fiksi\](.*?)(;|$)/', $baca_judul, $matches);
                        $judul_nonfiksi = trim($matches[1] ?? '');
                        if ($judul_nonfiksi == '-') $judul_nonfiksi = '';
                    }
                ?>
                
                <div style="margin-bottom: 10px;">
                    <label style="font-size:12px; cursor:pointer;"><input type="checkbox" name="baca_fiksi" value="1" <?php echo $is_fiksi ? 'checked' : ''; ?>> Fiksi</label>
                    <input type="text" name="judul_fiksi" placeholder="Judul buku fiksi..." value="<?php echo htmlspecialchars($judul_fiksi); ?>" style="width:100%; padding:8px; margin-top:5px; border:1px solid #ddd; border-radius:4px; font-size:13px;">
                </div>
                
                <div>
                    <label style="font-size:12px; cursor:pointer;"><input type="checkbox" name="baca_nonfiksi" value="1" <?php echo $is_nonfiksi ? 'checked' : ''; ?>> Non Fiksi</label>
                    <input type="text" name="judul_nonfiksi" placeholder="Judul buku / materi non fiksi..." value="<?php echo htmlspecialchars($judul_nonfiksi); ?>" style="width:100%; padding:8px; margin-top:5px; border:1px solid #ddd; border-radius:4px; font-size:13px;">
                </div>
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
            <div class="keterangan-wrapper <?php echo ($data_hari_ini && $data_hari_ini['membantu'] == 1) ? 'show' : ''; ?>">
                <span class="label-keterangan">🧹 Apa yang kamu lakukan untuk membantu orang tua?</span>
                <textarea name="membantu_jenis" placeholder="Contoh: Menyapu rumah, mencuci piring, menjaga adik..."><?php echo htmlspecialchars($data_hari_ini['membantu_jenis'] ?? ''); ?></textarea>
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
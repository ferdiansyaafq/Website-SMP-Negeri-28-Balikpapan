<?php
// aplikasi/siswa/refleksi.php
require_once '../includes/header-kaih.php';

$message = '';
$message_type = '';
$siswa_id = $_SESSION['siswa_id'] ?? 0;
$nama_siswa = 'Siswa';
$kelas = '';

// Ambil data siswa
if ($siswa_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT nama_siswa, kelas FROM siswa WHERE id = ?");
        $stmt->execute([$siswa_id]);
        $siswa = $stmt->fetch();
        if ($siswa) {
            $nama_siswa = $siswa['nama_siswa'];
            $kelas = $siswa['kelas'] ?? '-';
        }
    } catch (PDOException $e) {}
}

// ============================================================
// AUTO-CREATE TABEL REFLEKSI
// ============================================================
function ensureRefleksiTable($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `refleksi` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `siswa_id` INT NOT NULL,
            `tanggal` DATE NOT NULL,
            `bulan` VARCHAR(20) NULL,
            `semester` VARCHAR(20) NOT NULL DEFAULT 'Ganjil',
            `tahun_ajaran` VARCHAR(20) NOT NULL,
            `pelajaran_favorit` TEXT NULL,
            `pelajaran_sulit` TEXT NULL,
            `pencapaian` TEXT NULL,
            `kendala` TEXT NULL,
            `pengalaman_berkesan` TEXT NULL,
            `saran` TEXT NULL,
            `target_kedepan` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_siswa_tanggal` (`siswa_id`, `tanggal`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Pastikan kolom bulan sudah ada di tabel refleksi
        $stmt = $pdo->query("SHOW COLUMNS FROM refleksi LIKE 'bulan'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE refleksi ADD COLUMN bulan VARCHAR(20) NULL AFTER tanggal");
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
ensureRefleksiTable($pdo);

// Daftar Bulan Indonesia
$bulan_list = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$bulan_sekarang = $bulan_list[date('m')] ?? 'Januari';
$auto_semester = in_array((int)date('n'), [1, 2, 3, 4, 5, 6]) ? 'Genap' : 'Ganjil';

// ============================================================
// PROSES SIMPAN REFLEKSI BULANAN
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_refleksi'])) {
    $tanggal = date('Y-m-d');
    $bulan = trim($_POST['bulan'] ?? $bulan_sekarang);
    $semester = in_array((int)date('n'), [1, 2, 3, 4, 5, 6]) ? 'Genap' : 'Ganjil';
    $tahun_ajaran = trim($_POST['tahun_ajaran'] ?? (date('Y') . '/' . (date('Y') + 1)));
    $pelajaran_favorit = trim($_POST['pelajaran_favorit'] ?? '');
    $pelajaran_sulit = trim($_POST['pelajaran_sulit'] ?? '');
    $pencapaian = trim($_POST['pencapaian'] ?? '');
    $kendala = trim($_POST['kendala'] ?? '');
    $pengalaman_berkesan = trim($_POST['pengalaman_berkesan'] ?? '');
    $saran = trim($_POST['saran'] ?? '');
    $target_kedepan = trim($_POST['target_kedepan'] ?? '');
    
    if ($siswa_id > 0) {
        try {
            // Cek apakah sudah ada refleksi untuk siswa pada bulan & tahun ajaran ini
            $stmt = $pdo->prepare("SELECT id FROM refleksi WHERE siswa_id = ? AND (bulan = ? OR (bulan IS NULL AND semester = ?)) AND tahun_ajaran = ?");
            $stmt->execute([$siswa_id, $bulan, $bulan, $tahun_ajaran]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $pdo->prepare("UPDATE refleksi SET 
                    bulan = ?, semester = ?, tahun_ajaran = ?, pelajaran_favorit = ?, 
                    pelajaran_sulit = ?, pencapaian = ?, kendala = ?, 
                    pengalaman_berkesan = ?, saran = ?, target_kedepan = ?,
                    updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([
                    $bulan, $semester, $tahun_ajaran, $pelajaran_favorit,
                    $pelajaran_sulit, $pencapaian, $kendala,
                    $pengalaman_berkesan, $saran, $target_kedepan,
                    $existing['id']
                ]);
                $message = "✅ Refleksi bulan $bulan berhasil diperbarui!";
                $message_type = 'success';
            } else {
                $stmt = $pdo->prepare("INSERT INTO refleksi (
                    siswa_id, tanggal, bulan, semester, tahun_ajaran,
                    pelajaran_favorit, pelajaran_sulit, pencapaian,
                    kendala, pengalaman_berkesan, saran, target_kedepan,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([
                    $siswa_id, $tanggal, $bulan, $semester, $tahun_ajaran,
                    $pelajaran_favorit, $pelajaran_sulit, $pencapaian,
                    $kendala, $pengalaman_berkesan, $saran, $target_kedepan
                ]);
                $message = "✅ Refleksi bulan $bulan berhasil disimpan! Terus refleksikan pembelajaranmu! 🎉";
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = '❌ Gagal menyimpan refleksi: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = '❌ Data siswa tidak ditemukan. Silakan login ulang.';
        $message_type = 'error';
    }
}

// Ambil data refleksi untuk bulan terpilih atau terakhir
$data_refleksi = null;
if ($siswa_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM refleksi WHERE siswa_id = ? AND (bulan = ? OR tanggal LIKE ?) ORDER BY tanggal DESC LIMIT 1");
        $stmt->execute([$siswa_id, $bulan_sekarang, date('Y-m') . '%']);
        $data_refleksi = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data_refleksi) {
            $stmt = $pdo->prepare("SELECT * FROM refleksi WHERE siswa_id = ? ORDER BY tanggal DESC LIMIT 1");
            $stmt->execute([$siswa_id]);
            $data_refleksi = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {}
}

// Ambil semua refleksi untuk riwayat
$riwayat_refleksi = [];
if ($siswa_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM refleksi WHERE siswa_id = ? ORDER BY tanggal DESC");
        $stmt->execute([$siswa_id]);
        $riwayat_refleksi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

$bulan_options = array_values($bulan_list);
$tahun_ajaran_options = [];
for ($i = date('Y') - 2; $i <= date('Y') + 1; $i++) {
    $tahun_ajaran_options[] = $i . '/' . ($i + 1);
}
?>

<style>
    .refleksi-container {
        max-width: 700px;
        margin: 0 auto;
        padding: 0 10px;
    }

    .refleksi-card {
        background: white;
        border-radius: 16px;
        padding: 24px 22px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }

    .refleksi-card h3 {
        color: #1e293b;
        font-size: 18px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .refleksi-card .subtitle {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        font-size: 14px;
        color: #1e293b;
        margin-bottom: 4px;
    }

    .form-group label .required {
        color: #ef4444;
    }

    .form-group .hint {
        font-size: 12px;
        color: #94a3b8;
        font-weight: 400;
        display: block;
        margin-top: 2px;
    }

    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
        font-family: 'Segoe UI', system-ui, sans-serif;
        transition: all 0.3s;
        background: white;
        color: #1e293b;
    }

    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
    }

    .form-group textarea {
        min-height: 80px;
        resize: vertical;
    }

    .form-group select {
        cursor: pointer;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .btn-simpan {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #0284c7, #0369a1);
        color: white;
        border: none;
        border-radius: 14px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 8px;
        box-shadow: 0 4px 16px rgba(2, 132, 199, 0.3);
        font-family: 'Segoe UI', system-ui, sans-serif;
    }

    .btn-simpan:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 32px rgba(2, 132, 199, 0.4);
    }

    .btn-simpan:active {
        transform: translateY(0);
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

    .riwayat-item {
        background: #f8fafc;
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 12px;
        border-left: 4px solid #0284c7;
    }

    .riwayat-item .date {
        font-size: 12px;
        color: #94a3b8;
        font-weight: 600;
    }

    .riwayat-item .content {
        font-size: 14px;
        color: #1e293b;
        margin-top: 4px;
    }

    .riwayat-item .content strong {
        color: #475569;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #94a3b8;
    }

    .empty-state .icon {
        font-size: 48px;
        margin-bottom: 12px;
    }
    .empty-state p {
        font-size: 14px;
    }

    .badge-semester {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        background: #e0f2fe;
        color: #0284c7;
    }

    @media (max-width: 600px) {
        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }
        .refleksi-card {
            padding: 16px 14px;
        }
        .refleksi-card h3 {
            font-size: 16px;
        }
        .btn-simpan {
            font-size: 14px;
            padding: 12px;
        }
        .form-group textarea {
            min-height: 60px;
            font-size: 13px;
        }
        .form-group label {
            font-size: 13px;
        }
    }

    @media (max-width: 480px) {
        .refleksi-container {
            padding: 0 4px;
        }
        .refleksi-card {
            padding: 12px 10px;
            border-radius: 12px;
        }
        .riwayat-item {
            padding: 12px 14px;
        }
        .riwayat-item .content {
            font-size: 13px;
        }
    }
</style>

<div class="refleksi-container">

    <!-- Header -->
    <div class="refleksi-card" style="text-align: center; background: linear-gradient(135deg, #0284c7, #0369a1); color: white;">
        <h3 style="color: white; justify-content: center;">📝 Refleksi Bulanan Pembelajaran</h3>
        <div style="font-size: 14px; opacity: 0.9;">
            <?php echo htmlspecialchars($nama_siswa); ?> - <?php echo htmlspecialchars($kelas); ?>
        </div>
        <div style="font-size: 12px; opacity: 0.7; margin-top: 4px;">
            <?php echo tanggalIndo(); ?>
        </div>
    </div>

    <!-- Alert -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Form Refleksi -->
    <div class="refleksi-card">
        <h3>✍️ Tulis Refleksi Bulanan</h3>
        <div class="subtitle">
            Ceritakan pengalaman, pembelajaran, dan pencapaianmu selama satu bulan ini.
        </div>

        <form method="POST" action="">
            <input type="hidden" name="simpan_refleksi" value="1">

            <div class="form-row">
                <div class="form-group">
                    <label>Bulan Refleksi <span class="required">*</span></label>
                    <select name="bulan" required>
                        <?php foreach ($bulan_options as $opt): 
                            $isSelected = false;
                            if ($data_refleksi) {
                                $isSelected = (($data_refleksi['bulan'] ?? '') === $opt || ($data_refleksi['semester'] ?? '') === $opt);
                            } else {
                                $isSelected = ($opt === $bulan_sekarang);
                            }
                        ?>
                            <option value="<?php echo $opt; ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                <?php echo $opt; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tahun Ajaran <span class="required">*</span></label>
                    <select name="tahun_ajaran" required>
                        <?php foreach ($tahun_ajaran_options as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php echo ($data_refleksi && ($data_refleksi['tahun_ajaran'] ?? '') == $opt) ? 'selected' : ''; ?>>
                                <?php echo $opt; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>📖 Pelajaran Favorit</label>
                <textarea name="pelajaran_favorit" placeholder="Contoh: Saya sangat suka pelajaran IPA karena ..."><?php echo $data_refleksi['pelajaran_favorit'] ?? ''; ?></textarea>
                <span class="hint">Tulis mata pelajaran yang paling kamu sukai bulan ini dan alasannya.</span>
            </div>

            <div class="form-group">
                <label>📚 Pelajaran yang Sulit</label>
                <textarea name="pelajaran_sulit" placeholder="Contoh: Saya kesulitan di pelajaran Matematika karena ..."><?php echo $data_refleksi['pelajaran_sulit'] ?? ''; ?></textarea>
                <span class="hint">Tulis mata pelajaran yang menurutmu sulit dan tantangannya.</span>
            </div>

            <div class="form-group">
                <label>🏆 Pencapaian Selama Bulan Ini</label>
                <textarea name="pencapaian" placeholder="Contoh: Nilai ulangan saya meningkat, saya bisa memahami bab baru ..."><?php echo $data_refleksi['pencapaian'] ?? ''; ?></textarea>
                <span class="hint">Apa saja hal baik yang sudah kamu capai bulan ini?</span>
            </div>

            <div class="form-group">
                <label>🤔 Kendala yang Dihadapi</label>
                <textarea name="kendala" placeholder="Contoh: Saya kesulitan fokus saat belajar di rumah karena ..."><?php echo $data_refleksi['kendala'] ?? ''; ?></textarea>
                <span class="hint">Apa saja kesulitan yang kamu hadapi selama belajar di bulan ini?</span>
            </div>

            <div class="form-group">
                <label>💫 Pengalaman Paling Berkesan Bulan Ini</label>
                <textarea name="pengalaman_berkesan" placeholder="Contoh: Pengalaman paling berkesan adalah saat praktikum bersama teman..."><?php echo $data_refleksi['pengalaman_berkesan'] ?? ''; ?></textarea>
                <span class="hint">Ceritakan momen yang paling berkesan selama bulan ini.</span>
            </div>

            <div class="form-group">
                <label>💡 Saran untuk Guru / Sekolah</label>
                <textarea name="saran" placeholder="Contoh: Saya berharap pembelajaran lebih interaktif dan ada sesi tanya jawab ..."><?php echo $data_refleksi['saran'] ?? ''; ?></textarea>
                <span class="hint">Tulis saran atau masukan untuk guru atau sekolah.</span>
            </div>

            <div class="form-group">
                <label>🎯 Target untuk Bulan Depan</label>
                <textarea name="target_kedepan" placeholder="Contoh: Saya ingin lebih aktif bertanya di kelas dan menyelesaikan tugas tepat waktu ..."><?php echo $data_refleksi['target_kedepan'] ?? ''; ?></textarea>
                <span class="hint">Apa target yang ingin kamu capai di bulan berikutnya?</span>
            </div>

            <button type="submit" class="btn-simpan">
                💾 <?php echo ($data_refleksi) ? 'Update Refleksi Bulanan' : 'Simpan Refleksi Bulanan'; ?>
            </button>
        </form>
    </div>

    <!-- Riwayat Refleksi -->
    <div class="refleksi-card">
        <h3>📜 Riwayat Refleksimu</h3>
        
        <?php if (empty($riwayat_refleksi)): ?>
            <div class="empty-state">
                <div class="icon">📝</div>
                <p>Belum ada refleksi yang ditulis.</p>
                <p style="font-size: 12px; color: #cbd5e1;">Mulai tulis refleksi pembelajaran bulananmu sekarang!</p>
            </div>
        <?php else: ?>
            <?php foreach ($riwayat_refleksi as $row): 
                $label_periode = !empty($row['bulan']) ? 'Bulan ' . $row['bulan'] : ($row['semester'] ?? 'Ganjil');
            ?>
                <div class="riwayat-item">
                    <div class="date">
                        <?php echo formatTanggalIndo($row['tanggal']); ?> 
                        <span class="badge-semester"><?php echo htmlspecialchars($label_periode); ?></span>
                        <span class="badge-semester" style="background: #dbeafe; color: #1d4ed8;"><?php echo htmlspecialchars($row['tahun_ajaran'] ?? ''); ?></span>
                    </div>
                    <div class="content">
                        <?php if (!empty($row['pelajaran_favorit'])): ?>
                            <div><strong>📖 Favorit:</strong> <?php echo htmlspecialchars($row['pelajaran_favorit']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($row['pencapaian'])): ?>
                            <div><strong>🏆 Pencapaian:</strong> <?php echo htmlspecialchars($row['pencapaian']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($row['pengalaman_berkesan'])): ?>
                            <div><strong>💫 Berkesan:</strong> <?php echo htmlspecialchars($row['pengalaman_berkesan']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($row['target_kedepan'])): ?>
                            <div><strong>🎯 Target:</strong> <?php echo htmlspecialchars($row['target_kedepan']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php
?>
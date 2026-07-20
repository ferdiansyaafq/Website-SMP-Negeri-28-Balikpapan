<?php
// aplikasi/siswa/absensi.php
require_once '../includes/header-kaih.php';

$message = '';
$message_type = '';
$siswa_id = $_SESSION['siswa_id'] ?? 0;

// Ambil nama siswa
$nama_siswa = 'Siswa';
if ($siswa_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT nama_siswa FROM siswa WHERE id = ?");
        $stmt->execute([$siswa_id]);
        $siswa = $stmt->fetch();
        if ($siswa) {
            $nama_siswa = $siswa['nama_siswa'];
        }
    } catch (PDOException $e) {
        // Abaikan
    }
}

// ============================================================
// FUNGSI UNTUK CEK & TAMBAHKAN KOLOM TABEL (KOMPATIBEL)
// ============================================================
function ensureAbsensiColumns($pdo) {
    try {
        // Cek apakah kolom deskripsi ada
        $stmt = $pdo->query("SHOW COLUMNS FROM absensi LIKE 'deskripsi'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE absensi ADD COLUMN deskripsi VARCHAR(255) NULL AFTER status");
        }
        
        // Cek apakah kolom catatan ada
        $stmt = $pdo->query("SHOW COLUMNS FROM absensi LIKE 'catatan'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE absensi ADD COLUMN catatan TEXT NULL AFTER deskripsi");
        }
        
        // Cek apakah kolom updated_at ada
        $stmt = $pdo->query("SHOW COLUMNS FROM absensi LIKE 'updated_at'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE absensi ADD COLUMN updated_at DATETIME NULL AFTER created_at");
        }
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Pastikan kolom tabel ada
ensureAbsensiColumns($pdo);

// ============================================================
// PROSES ABSENSI
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['absen'])) {
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $status = 'hadir';
    $deskripsi = 'Sesi kelas reguler';
    $catatan = 'Presensi mandiri';
    
    if ($siswa_id > 0) {
        try {
            // Cek apakah sudah absen pada tanggal tersebut
            $stmt = $pdo->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND tanggal = ?");
            $stmt->execute([$siswa_id, $tanggal]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update data yang sudah ada
                $stmt = $pdo->prepare("UPDATE absensi SET 
                    status = ?, 
                    deskripsi = ?, 
                    catatan = ?,
                    updated_at = NOW()
                    WHERE siswa_id = ? AND tanggal = ?");
                $stmt->execute([$status, $deskripsi, $catatan, $siswa_id, $tanggal]);
                $message = '✅ Absensi berhasil diperbarui!';
                $message_type = 'success';
            } else {
                // Insert data baru
                $stmt = $pdo->prepare("INSERT INTO absensi (
                    siswa_id, tanggal, status, deskripsi, catatan, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$siswa_id, $tanggal, $status, $deskripsi, $catatan]);
                $message = '✅ Absensi berhasil! 🎉';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = '❌ Gagal absen: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = '❌ Data siswa tidak ditemukan. Silakan login ulang.';
        $message_type = 'error';
    }
}

// ============================================================
// AMBIL DATA ABSENSI
// ============================================================
// Ambil data absensi minggu ini (Senin - Jumat)
$today = new DateTime();
$dayOfWeek = $today->format('N'); // 1 = Senin, 7 = Minggu

// Cari hari Senin minggu ini
$monday = clone $today;
if ($dayOfWeek == 1) {
    $monday = $today;
} else {
    $monday->modify('last monday');
}

$friday = clone $monday;
$friday->modify('+4 days');

$absensi_mingguan = [];
$hari_minggu = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
$tanggal_minggu = [];
$current = clone $monday;
for ($i = 0; $i < 5; $i++) {
    $tanggal_minggu[] = clone $current;
    $current->modify('+1 day');
}

// Ambil data absensi mingguan
if ($siswa_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM absensi 
                               WHERE siswa_id = ? 
                               AND tanggal BETWEEN ? AND ? 
                               ORDER BY tanggal ASC");
        $stmt->execute([$siswa_id, $monday->format('Y-m-d'), $friday->format('Y-m-d')]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as $row) {
            $absensi_mingguan[$row['tanggal']] = $row;
        }
    } catch (PDOException $e) {
        // Abaikan
    }
}

// Ambil riwayat absensi (bulanan)
$bulan_ini = date('Y-m');
$riwayat_absensi = [];
if ($siswa_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM absensi 
                               WHERE siswa_id = ? 
                               AND DATE_FORMAT(tanggal, '%Y-%m') = ?
                               ORDER BY tanggal DESC");
        $stmt->execute([$siswa_id, $bulan_ini]);
        $riwayat_absensi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Abaikan
    }
}

// Hitung statistik
$total_hadir = 0;
$total_tidak = 0;
foreach ($riwayat_absensi as $row) {
    if ($row['status'] === 'hadir') {
        $total_hadir++;
    } else {
        $total_tidak++;
    }
}

// Hitung hadir minggu ini
$hadir_minggu = 0;
foreach ($tanggal_minggu as $tgl) {
    $tgl_str = $tgl->format('Y-m-d');
    if (isset($absensi_mingguan[$tgl_str]) && $absensi_mingguan[$tgl_str]['status'] === 'hadir') {
        $hadir_minggu++;
    }
}
?>

<style>
    .absensi-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .absensi-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }

    .absensi-card h3 {
        color: #1e293b;
        font-size: 18px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .absensi-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .absensi-table th {
        background: #f8fafc;
        padding: 12px 14px;
        text-align: left;
        font-weight: 700;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .absensi-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .absensi-table tr:hover td {
        background: #f8fafc;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-hadir { background: #dcfce7; color: #16a34a; }
    .status-izin { background: #fef3c7; color: #d97706; }
    .status-sakit { background: #fee2e2; color: #dc2626; }
    .status-alpha { background: #f1f5f9; color: #94a3b8; }

    .btn-hadir {
        padding: 6px 20px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-hadir:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(16,185,129,0.4);
    }

    .btn-hadir:disabled {
        background: #94a3b8;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .btn-hadir.sudah {
        background: #dcfce7;
        color: #16a34a;
        cursor: default;
    }

    .btn-hadir.sudah:hover {
        transform: none;
        box-shadow: none;
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

    .stat-rekap {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 12px;
        margin-bottom: 15px;
    }

    .stat-item {
        background: #f8fafc;
        padding: 12px 15px;
        border-radius: 12px;
        text-align: center;
    }

    .stat-item .number {
        font-size: 24px;
        font-weight: 800;
        color: #1e293b;
    }

    .stat-item .label {
        font-size: 12px;
        color: #64748b;
        margin-top: 3px;
    }

    .stat-item.hadir .number { color: #10b981; }
    .stat-item.tidak .number { color: #ef4444; }
    .stat-item.total .number { color: #0284c7; }

    @media (max-width: 768px) {
        .absensi-table {
            font-size: 12px;
        }
        .absensi-table th,
        .absensi-table td {
            padding: 8px 10px;
        }
        .btn-hadir {
            padding: 4px 14px;
            font-size: 12px;
        }
        .stat-rekap {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 480px) {
        .stat-rekap {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<div class="absensi-container">

    <!-- Alert Message -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Absensi Mingguan -->
    <div class="absensi-card">
        <h3>📊 Absensi Minggu Ini (<?php echo $monday->format('d M Y'); ?> - <?php echo $friday->format('d M Y'); ?>)</h3>
        
        <div class="stat-rekap">
            <div class="stat-item total">
                <div class="number">5</div>
                <div class="label">Total Hari</div>
            </div>
            <div class="stat-item hadir">
                <div class="number"><?php echo $hadir_minggu; ?></div>
                <div class="label">Klik untuk Hadir</div>
            </div>
            <div class="stat-item tidak">
                <div class="number"><?php echo 5 - $hadir_minggu; ?></div>
                <div class="label">Belum Hadir</div>
            </div>
        </div>

        <table class="absensi-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Tanggal</th>
                    <th style="width: 25%;">Deskripsi</th>
                    <th style="width: 25%;">Status</th>
                    <th style="width: 25%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($tanggal_minggu as $tgl): 
                    $tgl_str = $tgl->format('Y-m-d');
                    $data = $absensi_mingguan[$tgl_str] ?? null;
                    $is_today = ($tgl_str === date('Y-m-d'));
                    $is_future = ($tgl_str > date('Y-m-d'));
                    
                    // Status untuk ditampilkan
                    $status_text = '-';
                    $status_class = '';
                    $is_hadir = false;
                    
                    if ($data) {
                        $status_text = ucfirst($data['status']);
                        $status_class = 'status-' . $data['status'];
                        $is_hadir = ($data['status'] === 'hadir');
                    }
                ?>
                <tr>
                    <td>
                        <strong><?php echo $tgl->format('D'); ?></strong>
                        <?php echo $tgl->format('d M Y'); ?>
                        <?php if ($is_today): ?>
                            <span style="background: #0284c7; color: white; padding: 2px 10px; border-radius: 12px; font-size: 10px; font-weight: 700; margin-left: 5px;">HARI INI</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $data ? htmlspecialchars($data['deskripsi'] ?? 'Sesi kelas reguler') : 'Sesi kelas reguler'; ?></td>
                    <td>
                        <?php if ($data): ?>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size: 13px;">Belum absen</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_future): ?>
                            <span style="color: #94a3b8; font-size: 12px;">Belum waktunya</span>
                        <?php elseif ($is_hadir): ?>
                            <button class="btn-hadir sudah" disabled>
                                Sudah Hadir
                            </button>
                        <?php elseif ($data && !$is_hadir): ?>
                            <span style="color: #94a3b8; font-size: 12px;">Status: <?php echo ucfirst($data['status']); ?></span>
                        <?php else: ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="absen" value="1">
                                <input type="hidden" name="tanggal" value="<?php echo $tgl_str; ?>">
                                <button type="submit" class="btn-hadir">
                                    Hadir
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Riwayat Absensi Bulanan -->
    <div class="absensi-card">
        <h3>📜 Riwayat Absensi Bulan <?php echo date('F Y'); ?></h3>
        
        <div class="stat-rekap">
            <div class="stat-item total">
                <div class="number"><?php echo count($riwayat_absensi); ?></div>
                <div class="label">Total Absensi</div>
            </div>
            <div class="stat-item hadir">
                <div class="number"><?php echo $total_hadir; ?></div>
                <div class="label">Hadir</div>
            </div>
            <div class="stat-item tidak">
                <div class="number"><?php echo $total_tidak; ?></div>
                <div class="label">Tidak Hadir</div>
            </div>
        </div>

        <?php if (empty($riwayat_absensi)): ?>
            <p style="color: #94a3b8; text-align: center; padding: 20px;">
                Belum ada riwayat absensi bulan ini.
            </p>
        <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="absensi-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Deskripsi</th>
                        <th>Status</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($riwayat_absensi as $row): ?>
                    <tr>
                        <td><?php echo date('D, d M Y', strtotime($row['tanggal'])); ?></td>
                        <td><?php echo htmlspecialchars($row['deskripsi'] ?? 'Sesi kelas reguler'); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['catatan'] ?? 'Presensi mandiri'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php
?>
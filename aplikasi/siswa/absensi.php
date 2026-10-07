<?php
// aplikasi/siswa/absensi.php
require_once '../includes/header-kaih.php';
require_once '../../config/database.php';

$message = '';
$message_type = '';
$siswa_id = $_SESSION['siswa_id'] ?? 0;

// ============================================================
// FUNGSI SUDAH ADA DI HEADER-KAIH.PHP
// JANGAN DEKLARASIKAN ULANG!
// ============================================================

// ============================================================
// CEK WAKTU & HARI UNTUK ABSENSI
// ============================================================
function cekWaktuAbsensi() {
    // Set timezone ke WITA (Asia/Makassar)
    date_default_timezone_set('Asia/Makassar');
    
    $hari = date('N'); // 1=Senin, 7=Minggu
    $jam = date('H:i'); // Format 24 jam
    
    // Absensi hanya Senin-Jumat (1-5)
    if ($hari < 1 || $hari > 5) {
        return [
            'status' => false,
            'pesan' => '❌ Absensi hanya berlaku hari Senin - Jumat!'
        ];
    }
    
    if ($jam < '07:15' || $jam > '07:30') {
        return [
            'status' => false,
            'pesan' => '❌ Absensi hanya berlaku pukul 07.15 - 07.30 WITA!'
        ];
    }
    
    return ['status' => true];
}

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
    } catch (PDOException $e) {}
}

// ============================================================
// CEK & TAMBAHKAN KOLOM TABEL
// ============================================================
function ensureAbsensiColumns($pdo) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM absensi LIKE 'deskripsi'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE absensi ADD COLUMN deskripsi VARCHAR(255) NULL AFTER status");
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM absensi LIKE 'catatan'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE absensi ADD COLUMN catatan TEXT NULL AFTER deskripsi");
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM absensi LIKE 'updated_at'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE absensi ADD COLUMN updated_at DATETIME NULL AFTER created_at");
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
ensureAbsensiColumns($pdo);

// ============================================================
// CEK & CATAT ABSENSI TERLEWAT (ALPHA)
// ============================================================
function cekDanCatatAbsenTerlewat($pdo, $siswa_id) {
    if ($siswa_id <= 0) return;
    
    try {
        $today = new DateTime();
        $dayOfWeek = $today->format('N');
        
        // Cari Senin minggu ini
        $monday = clone $today;
        if ($dayOfWeek == 1) {
            $monday = $today;
        } else {
            $monday->modify('last monday');
        }
        
        // Loop dari Senin sampai Jumat
        $current = clone $monday;
        for ($i = 0; $i < 5; $i++) {
            $tgl_str = $current->format('Y-m-d');
            
            // Jika tanggal sudah lewat (lebih kecil dari hari ini)
            if ($tgl_str < date('Y-m-d')) {
                // Cek apakah sudah ada absensi untuk tanggal ini
                $stmt = $pdo->prepare("SELECT id, status FROM absensi WHERE siswa_id = ? AND tanggal = ?");
                $stmt->execute([$siswa_id, $tgl_str]);
                $existing = $stmt->fetch();
                
                // Jika belum ada absensi, catat sebagai ALPHA
                if (!$existing) {
                    $stmt = $pdo->prepare("INSERT INTO absensi (
                        siswa_id, tanggal, status, deskripsi, catatan, created_at, updated_at
                    ) VALUES (?, ?, 'alpha', 'Sesi kelas reguler', 'Otomatis (Absen)', NOW(), NOW())");
                    $stmt->execute([$siswa_id, $tgl_str]);
                }
            }
            
            $current->modify('+1 day');
        }
    } catch (PDOException $e) {
        // Abaikan error
    }
}

// Jalankan fungsi pengecekan absensi terlewat
cekDanCatatAbsenTerlewat($pdo, $siswa_id);

// ============================================================
// PROSES ABSENSI
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['absen'])) {
    
    // ============================================================
    // CEK WAKTU DAN HARI ABSENSI - TAMBAHKAN INI!
    // ============================================================
    $cek = cekWaktuAbsensi();
    if (!$cek['status']) {
        $message = $cek['pesan'];
        $message_type = 'error';
    } else {
        // Lanjutkan proses absensi
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $status = 'hadir';
        $deskripsi = 'Sesi kelas reguler';
        $catatan = 'Presensi mandiri';
        
        if ($siswa_id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND tanggal = ?");
                $stmt->execute([$siswa_id, $tanggal]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $stmt = $pdo->prepare("UPDATE absensi SET 
                        status = ?, deskripsi = ?, catatan = ?, updated_at = NOW()
                        WHERE siswa_id = ? AND tanggal = ?");
                    $stmt->execute([$status, $deskripsi, $catatan, $siswa_id, $tanggal]);
                    $message = '✅ Absensi berhasil diperbarui!';
                    $message_type = 'success';
                } else {
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
}

// ============================================================
// AMBIL DATA MINGGU INI
// ============================================================
$today = new DateTime();
$dayOfWeek = $today->format('N');
$monday = clone $today;
if ($dayOfWeek == 1) {
    $monday = $today;
} else {
    $monday->modify('last monday');
}
$friday = clone $monday;
$friday->modify('+4 days');

$tanggal_minggu = [];
$current = clone $monday;
for ($i = 0; $i < 5; $i++) {
    $tanggal_minggu[] = clone $current;
    $current->modify('+1 day');
}

$absensi_mingguan = [];
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
    } catch (PDOException $e) {}
}

$hadir_minggu = 0;
foreach ($tanggal_minggu as $tgl) {
    $tgl_str = $tgl->format('Y-m-d');
    if (isset($absensi_mingguan[$tgl_str]) && $absensi_mingguan[$tgl_str]['status'] === 'hadir') {
        $hadir_minggu++;
    }
}
?>

<style>
    .absensi-container { max-width: 900px; margin: 0 auto; padding: 0 10px; }
    .absensi-card {
        background: white;
        border-radius: 16px;
        padding: 20px 18px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }
    .absensi-card h3 {
        color: #1e293b;
        font-size: 17px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .absensi-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    .absensi-table th {
        background: #f8fafc;
        padding: 10px 12px;
        text-align: center;
        font-weight: 700;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .absensi-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        text-align: center;
        font-size: 13px;
    }
    .absensi-table tr:hover td { background: #f8fafc; }

    .status-badge {
        display: inline-block;
        padding: 3px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-hadir { background: #dcfce7; color: #16a34a; }
    .status-izin { background: #fef3c7; color: #d97706; }
    .status-sakit { background: #fee2e2; color: #dc2626; }
    .status-alpha { background: #f1f5f9; color: #94a3b8; }

    .btn-hadir {
        padding: 5px 18px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 20px;
        font-size: 12px;
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
        padding: 12px 16px;
        border-radius: 12px;
        margin-bottom: 18px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
    }
    .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    .stat-rekap {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-bottom: 15px;
    }
    .stat-item {
        background: #f8fafc;
        padding: 10px 12px;
        border-radius: 12px;
        text-align: center;
    }
    .stat-item .number { font-size: 22px; font-weight: 800; color: #1e293b; }
    .stat-item .label { font-size: 11px; color: #64748b; margin-top: 2px; }
    .stat-item.hadir .number { color: #10b981; }
    .stat-item.tidak .number { color: #ef4444; }
    .stat-item.total .number { color: #0284c7; }

    @media (max-width: 768px) {
        .absensi-card { padding: 14px 12px; }
        .absensi-card h3 { font-size: 15px; }
        .absensi-table { font-size: 12px; }
        .absensi-table th, .absensi-table td { padding: 7px 6px; font-size: 11px; }
        .absensi-table th { font-size: 10px; }
        .btn-hadir { padding: 4px 12px; font-size: 11px; }
        .stat-item .number { font-size: 18px; }
        .stat-item .label { font-size: 10px; }
        .stat-rekap { gap: 6px; }
    }

    @media (max-width: 480px) {
        .absensi-container { padding: 0 4px; }
        .absensi-card { padding: 10px 8px; border-radius: 12px; }
        .absensi-card h3 { font-size: 13px; margin-bottom: 10px; }
        .absensi-table th, .absensi-table td { padding: 5px 4px; font-size: 10px; }
        .absensi-table th { font-size: 9px; }
        .status-badge { padding: 2px 10px; font-size: 10px; }
        .btn-hadir { padding: 3px 10px; font-size: 10px; }
        .stat-item { padding: 6px 8px; }
        .stat-item .number { font-size: 16px; }
        .stat-item .label { font-size: 9px; }
        .stat-rekap { grid-template-columns: repeat(3, 1fr); gap: 4px; }
        .alert { font-size: 12px; padding: 10px 12px; }
    }
</style>

<div class="absensi-container">

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="absensi-card">
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 10px 14px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 13px; color: #166534;">
            <span style="font-size: 18px;">⏰</span>
            <div><strong>Jadwal Presensi:</strong> Hari Senin - Jumat pukul <strong>07.15 - 07.30 WITA</strong>. Pastikan absen tepat waktu.</div>
        </div>
        <h3>📊 Absensi Minggu Ini (<?php echo formatTanggalIndo($monday->format('Y-m-d')); ?> - <?php echo formatTanggalIndo($friday->format('Y-m-d')); ?>)</h3>
        
        <div class="stat-rekap">
            <div class="stat-item total">
                <div class="number">5</div>
                <div class="label">Total Hari</div>
            </div>
            <div class="stat-item hadir">
                <div class="number"><?php echo $hadir_minggu; ?></div>
                <div class="label">Hadir</div>
            </div>
            <div class="stat-item tidak">
                <div class="number"><?php echo 5 - $hadir_minggu; ?></div>
                <div class="label">Belum Hadir</div>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="absensi-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Deskripsi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tanggal_minggu as $tgl): 
                        $tgl_str = $tgl->format('Y-m-d');
                        $data = $absensi_mingguan[$tgl_str] ?? null;
                        $is_today = ($tgl_str === date('Y-m-d'));
                        $is_future = ($tgl_str > date('Y-m-d'));
                        
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
                            <strong><?php echo hariIndo($tgl->format('l')); ?></strong>
                            <?php echo $tgl->format('d') . ' ' . bulanIndo($tgl->format('F')) . ' ' . $tgl->format('Y'); ?>
                            <?php if ($is_today): ?>
                                <span style="background: #0284c7; color: white; padding: 1px 8px; border-radius: 10px; font-size: 9px; font-weight: 700; margin-left: 3px; display: inline-block;">HARI INI</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $data ? htmlspecialchars($data['deskripsi'] ?? 'Sesi kelas reguler') : 'Sesi kelas reguler'; ?></td>
                        <td>
                            <?php if ($data): ?>
                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            <?php else: ?>
                                <span style="color: #94a3b8; font-size: 11px;">Belum absen</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_future): ?>
                                <span style="color: #94a3b8; font-size: 11px;">Belum waktunya</span>
                            <?php elseif ($is_hadir): ?>
                                <button class="btn-hadir sudah" disabled>Hadir</button>
                            <?php elseif ($data && !$is_hadir): ?>
                                <span style="color: #94a3b8; font-size: 11px;">Status: <?php echo ucfirst($data['status']); ?></span>
                            <?php else: ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="absen" value="1">
                                    <input type="hidden" name="tanggal" value="<?php echo $tgl_str; ?>">
                                    <button type="submit" class="btn-hadir">Klik Untuk Hadir</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php
?>

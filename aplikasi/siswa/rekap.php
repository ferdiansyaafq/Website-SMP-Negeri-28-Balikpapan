<?php
// aplikasi/siswa/rekap.php
require_once '../includes/header-kaih.php';

$siswa_id = $_SESSION['siswa_id'] ?? 0;
$nama_siswa = 'Siswa';
$kelas = '';

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
// AMBIL BULAN YANG DIPILIH DARI GET
// ============================================================
$bulan_terpilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$bulan = date('m', strtotime($bulan_terpilih . '-01'));
$tahun = date('Y', strtotime($bulan_terpilih . '-01'));

// ============================================================
// FUNGSI CEK & LENGKAPI ABSENSI BULANAN
// ============================================================
function lengkapiAbsensiBulanan($pdo, $siswa_id, $bulan, $tahun) {
    if ($siswa_id <= 0) return;
    
    try {
        $first_day = date('Y-m-d', strtotime("$tahun-$bulan-01"));
        $last_day = date('Y-m-t', strtotime("$tahun-$bulan-01"));
        
        $current = new DateTime($first_day);
        $end = new DateTime($last_day);
        
        while ($current <= $end) {
            $tgl_str = $current->format('Y-m-d');
            $hari = $current->format('N');
            
            if ($hari >= 1 && $hari <= 5) {
                $stmt = $pdo->prepare("SELECT id, status FROM absensi WHERE siswa_id = ? AND tanggal = ?");
                $stmt->execute([$siswa_id, $tgl_str]);
                $existing = $stmt->fetch();
                
                if (!$existing && $tgl_str < date('Y-m-d')) {
                    $stmt = $pdo->prepare("INSERT INTO absensi (
                        siswa_id, tanggal, status, deskripsi, catatan, created_at, updated_at
                    ) VALUES (?, ?, 'alpha', 'Sesi kelas reguler', 'Tidak absen (otomatis)', NOW(), NOW())");
                    $stmt->execute([$siswa_id, $tgl_str]);
                }
            }
            
            $current->modify('+1 day');
        }
    } catch (PDOException $e) {}
}

// ============================================================
// FUNGSI LENGKAPI DATA KAIH BULANAN
// ============================================================
function lengkapiKAIHBulanan($pdo, $siswa_id, $bulan, $tahun) {
    if ($siswa_id <= 0) return;
    
    try {
        $first_day = date('Y-m-d', strtotime("$tahun-$bulan-01"));
        $last_day = date('Y-m-t', strtotime("$tahun-$bulan-01"));
        
        $current = new DateTime($first_day);
        $end = new DateTime($last_day);
        
        while ($current <= $end) {
            $tgl_str = $current->format('Y-m-d');
            $hari = $current->format('N');
            
            if ($hari >= 1 && $hari <= 5 && $tgl_str < date('Y-m-d')) {
                $stmt = $pdo->prepare("SELECT id FROM laporan_harian WHERE siswa_id = ? AND tanggal = ?");
                $stmt->execute([$siswa_id, $tgl_str]);
                $existing = $stmt->fetch();
                
                if (!$existing) {
                    $stmt = $pdo->prepare("INSERT INTO laporan_harian (
                        siswa_id, tanggal, bangun, ibadah, ibadah_catatan,
                        olahraga, olahraga_jenis, sarapan, sarapan_menu,
                        membaca, membaca_judul, membaca_menit,
                        membantu, membantu_jenis, menabung, menabung_nominal,
                        created_at, updated_at
                    ) VALUES (
                        ?, ?, 0, 0, NULL,
                        0, NULL, 0, NULL,
                        0, NULL, 0,
                        0, NULL, 0, NULL,
                        NOW(), NOW()
                    )");
                    $stmt->execute([$siswa_id, $tgl_str]);
                }
            }
            
            $current->modify('+1 day');
        }
    } catch (PDOException $e) {}
}

// ============================================================
// AMBIL DATA REKAP
// ============================================================
$bulan_ini = $tahun . '-' . $bulan;
$rekap_absensi = [];
$rekap_kaih = [];

$nama_bulan = bulanIndo(date('F', strtotime($bulan_ini . '-01')));
$tahun_display = $tahun;

if ($siswa_id > 0) {
    // LENGKAPI DATA ABSENSI BULAN INI
    lengkapiAbsensiBulanan($pdo, $siswa_id, $bulan, $tahun);
    
    // LENGKAPI DATA KAIH BULAN INI
    lengkapiKAIHBulanan($pdo, $siswa_id, $bulan, $tahun);
    
    // Rekap Absensi
    try {
        $stmt = $pdo->prepare("SELECT tanggal, status, deskripsi, catatan FROM absensi 
                               WHERE siswa_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ?
                               ORDER BY tanggal DESC");
        $stmt->execute([$siswa_id, $bulan_ini]);
        $rekap_absensi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}

    // Rekap KAIH
    try {
        $stmt = $pdo->prepare("SELECT tanggal, bangun, ibadah, olahraga, sarapan, membaca, membantu, menabung 
                               FROM laporan_harian 
                               WHERE siswa_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ?
                               ORDER BY tanggal DESC");
        $stmt->execute([$siswa_id, $bulan_ini]);
        $rekap_kaih = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// ============================================================
// HITUNG STATISTIK ABSENSI
// ============================================================
$total_absen = count($rekap_absensi);
$total_hadir = 0;
$total_alpha = 0;
$total_izin = 0;
$total_sakit = 0;

foreach ($rekap_absensi as $row) {
    $status = strtolower($row['status'] ?? '');
    if ($status === 'hadir') {
        $total_hadir++;
    } elseif ($status === 'alpha') {
        $total_alpha++;
    } elseif ($status === 'izin') {
        $total_izin++;
    } elseif ($status === 'sakit') {
        $total_sakit++;
    }
}

// ============================================================
// HITUNG STATISTIK KAIH
// ============================================================
$total_kaih = count($rekap_kaih);
$total_kebiasaan = 0;

foreach ($rekap_kaih as $row) {
    $total_kebiasaan += 
        ($row['bangun'] ?? 0) + ($row['ibadah'] ?? 0) + ($row['olahraga'] ?? 0) + 
        ($row['sarapan'] ?? 0) + ($row['membaca'] ?? 0) + ($row['membantu'] ?? 0) + 
        ($row['menabung'] ?? 0);
}

// ============================================================
// FUNGSI UNTUK STATUS BADGE
// ============================================================
function getStatusBadge($status) {
    $status = strtolower($status);
    $badges = [
        'hadir' => '<span class="status-badge status-hadir">Hadir</span>',
        'alpha' => '<span class="status-badge status-alpha">Tidak Absen</span>',
        'izin' => '<span class="status-badge status-izin">Izin</span>',
        'sakit' => '<span class="status-badge status-sakit">Sakit</span>'
    ];
    return $badges[$status] ?? '<span class="status-badge">' . ucfirst($status) . '</span>';
}

// ============================================================
// HITUNG JUMLAH HARI SEKOLAH DI BULAN INI
// ============================================================
function getHariSekolahBulan($bulan, $tahun) {
    $count = 0;
    $first_day = date('Y-m-d', strtotime("$tahun-$bulan-01"));
    $last_day = date('Y-m-t', strtotime("$tahun-$bulan-01"));
    
    $current = new DateTime($first_day);
    $end = new DateTime($last_day);
    
    while ($current <= $end) {
        $hari = $current->format('N');
        if ($hari >= 1 && $hari <= 5) {
            $count++;
        }
        $current->modify('+1 day');
    }
    return $count;
}

$total_hari_sekolah = getHariSekolahBulan($bulan, $tahun);

// ============================================================
// FUNGSI UNTUK MENGHASILKAN OPSI BULAN (1 TAHUN TERAKHIR)
// ============================================================
function getBulanOptions($selected) {
    $options = '';
    $current = new DateTime();
    $current->modify('-11 months'); // Mulai dari 11 bulan yang lalu
    
    for ($i = 0; $i < 12; $i++) {
        $value = $current->format('Y-m');
        $label = bulanIndo($current->format('F')) . ' ' . $current->format('Y');
        $selected_attr = ($value == $selected) ? 'selected' : '';
        $options .= '<option value="' . $value . '" ' . $selected_attr . '>' . $label . '</option>';
        $current->modify('+1 month');
    }
    return $options;
}
?>

<style>
    .rekap-container { max-width: 900px; margin: 0 auto; padding: 0 10px; }
    .rekap-card {
        background: white;
        border-radius: 16px;
        padding: 20px 18px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }
    .rekap-card h3 {
        color: #1e293b;
        font-size: 17px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .rekap-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    .rekap-table th {
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
    .rekap-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        text-align: center;
        font-size: 13px;
    }
    .rekap-table tr:hover td { background: #f8fafc; }

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

    .stat-rekap {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
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
    .stat-item.alpha .number { color: #94a3b8; }

    .badge-kebiasaan {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        margin: 1px;
    }
    .badge-kebiasaan.done { background: #dcfce7; color: #16a34a; }
    .badge-kebiasaan.not-done { background: #fee2e2; color: #dc2626; }
    .badge-kebiasaan.empty { background: #f1f5f9; color: #94a3b8; }

    /* ============================================================
       DROPDOWN FILTER BULAN
       ============================================================ */
    .filter-bulan-wrapper {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-bottom: 15px;
        gap: 10px;
        flex-wrap: wrap;
    }
    .filter-bulan-wrapper label {
        font-size: 14px;
        font-weight: 600;
        color: #475569;
    }
    .filter-bulan-wrapper select {
        padding: 8px 16px;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        background: white;
        cursor: pointer;
        transition: all 0.3s;
        font-family: 'Segoe UI', system-ui, sans-serif;
        min-width: 160px;
    }
    .filter-bulan-wrapper select:hover {
        border-color: #0284c7;
    }
    .filter-bulan-wrapper select:focus {
        outline: none;
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2,132,199,0.1);
    }

    @media (max-width: 768px) {
        .rekap-card { padding: 14px 12px; }
        .rekap-card h3 { font-size: 15px; }
        .rekap-table { font-size: 12px; }
        .rekap-table th, .rekap-table td { padding: 7px 6px; font-size: 11px; }
        .rekap-table th { font-size: 10px; }
        .stat-item .number { font-size: 18px; }
        .stat-item .label { font-size: 10px; }
        .stat-rekap { gap: 6px; }
        .filter-bulan-wrapper {
            justify-content: center;
        }
        .filter-bulan-wrapper select {
            padding: 6px 12px;
            font-size: 13px;
            min-width: 140px;
        }
    }

    @media (max-width: 600px) {
        .stat-rekap { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 480px) {
        .rekap-container { padding: 0 4px; }
        .rekap-card { padding: 10px 8px; border-radius: 12px; }
        .rekap-card h3 { font-size: 13px; margin-bottom: 10px; }
        .rekap-table th, .rekap-table td { padding: 5px 4px; font-size: 10px; }
        .rekap-table th { font-size: 9px; }
        .status-badge { padding: 2px 10px; font-size: 10px; }
        .stat-item { padding: 6px 8px; }
        .stat-item .number { font-size: 16px; }
        .stat-item .label { font-size: 9px; }
        .stat-rekap { grid-template-columns: repeat(2, 1fr); gap: 4px; }
        .badge-kebiasaan { font-size: 8px; padding: 1px 5px; }
        .filter-bulan-wrapper {
            flex-direction: column;
            align-items: stretch;
            gap: 6px;
        }
        .filter-bulan-wrapper select {
            width: 100%;
            min-width: unset;
            padding: 8px 12px;
            font-size: 13px;
        }
        .filter-bulan-wrapper label {
            font-size: 13px;
            text-align: center;
        }
    }
</style>

<div class="rekap-container">

    <!-- ============================================================
         HEADER REKAP
         ============================================================ -->
    <div class="rekap-card" style="text-align: center; background: linear-gradient(135deg, #0284c7, #0369a1); color: white;">
        <h3 style="color: white; justify-content: center;">
            📊 Rekap Bulan <?php echo $nama_bulan . ' ' . $tahun_display; ?>
        </h3>
        <div style="font-size: 14px; opacity: 0.9;">
            <?php echo htmlspecialchars($nama_siswa); ?> - <?php echo htmlspecialchars($kelas); ?>
        </div>
    </div>

    <!-- ============================================================
         FILTER BULAN - DROPDOWN
         ============================================================ -->
    <div class="filter-bulan-wrapper">
        <label for="bulan_filter">📅 Pilih Bulan:</label>
        <form method="GET" action="" id="formFilterBulan">
            <select name="bulan" id="bulan_filter" onchange="this.form.submit()">
                <?php echo getBulanOptions($bulan_terpilih); ?>
            </select>
        </form>
    </div>

    <!-- ============================================================
         REKAP ABSENSI
         ============================================================ -->
    <div class="rekap-card">
        <h3>📋 Rekap Absensi Bulan <?php echo $nama_bulan . ' ' . $tahun_display; ?></h3>
        
        <div class="stat-rekap">
            <div class="stat-item total">
                <div class="number"><?php echo $total_absen . '/' . $total_hari_sekolah; ?></div>
                <div class="label">Total Absen</div>
            </div>
            <div class="stat-item hadir">
                <div class="number"><?php echo $total_hadir; ?></div>
                <div class="label">Hadir</div>
            </div>
            <div class="stat-item alpha">
                <div class="number"><?php echo $total_alpha; ?></div>
                <div class="label">Tidak Absen</div>
            </div>
            <div class="stat-item" style="background: #dbeafe;">
                <div class="number" style="color: #2563eb;"><?php echo $total_hari_sekolah > 0 ? round(($total_hadir / $total_hari_sekolah) * 100) : 0; ?>%</div>
                <div class="label">Kehadiran</div>
            </div>
        </div>

        <?php if (empty($rekap_absensi)): ?>
            <p style="color: #94a3b8; text-align: center; padding: 20px; font-size: 14px;">
                Belum ada data absensi bulan ini.
            </p>
        <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="rekap-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Deskripsi</th>
                        <th>Status</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rekap_absensi as $row): ?>
                    <tr>
                        <td><?php echo formatTanggalIndo($row['tanggal']); ?></td>
                        <td><?php echo htmlspecialchars($row['deskripsi'] ?? 'Sesi kelas reguler'); ?></td>
                        <td><?php echo getStatusBadge($row['status']); ?></td>
                        <td><?php echo htmlspecialchars($row['catatan'] ?? 'Presensi mandiri'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ============================================================
         REKAP KAIH
         ============================================================ -->
    <div class="rekap-card">
        <h3>🌟 Rekap KAIH Bulan <?php echo $nama_bulan . ' ' . $tahun_display; ?></h3>
        
        <div class="stat-rekap">
            <div class="stat-item total">
                <div class="number"><?php echo $total_kaih; ?></div>
                <div class="label">Total Hari</div>
            </div>
            <div class="stat-item hadir">
                <div class="number"><?php echo $total_kebiasaan; ?></div>
                <div class="label">Total Kebiasaan</div>
            </div>
            <div class="stat-item tidak">
                <div class="number"><?php echo $total_kaih > 0 ? round(($total_kebiasaan / ($total_kaih * 7)) * 100) : 0; ?>%</div>
                <div class="label">Rata-rata</div>
            </div>
            <div class="stat-item" style="background: #dbeafe;">
                <div class="number" style="color: #2563eb;">
                    <?php echo $total_kaih > 0 ? round($total_kebiasaan / $total_kaih, 1) : 0; ?>
                </div>
                <div class="label">Rata-rata/Hari</div>
            </div>
        </div>

        <?php if (empty($rekap_kaih)): ?>
            <p style="color: #94a3b8; text-align: center; padding: 20px; font-size: 14px;">
                Belum ada data KAIH bulan ini.
            </p>
        <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="rekap-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>🌅 Bangun</th>
                        <th>🕌 Ibadah</th>
                        <th>⚽ Olahraga</th>
                        <th>🥗 Sarapan</th>
                        <th>📚 Belajar</th>
                        <th>🤝 Membantu</th>
                        <th>😴 Tidur</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rekap_kaih as $row): 
                        $total = ($row['bangun'] ?? 0) + ($row['ibadah'] ?? 0) + ($row['olahraga'] ?? 0) + 
                                 ($row['sarapan'] ?? 0) + ($row['membaca'] ?? 0) + ($row['membantu'] ?? 0) + 
                                 ($row['menabung'] ?? 0);
                        
                        $class_bangun = $row['bangun'] ? 'done' : 'not-done';
                        $class_ibadah = $row['ibadah'] ? 'done' : 'not-done';
                        $class_olahraga = $row['olahraga'] ? 'done' : 'not-done';
                        $class_sarapan = $row['sarapan'] ? 'done' : 'not-done';
                        $class_membaca = $row['membaca'] ? 'done' : 'not-done';
                        $class_membantu = $row['membantu'] ? 'done' : 'not-done';
                        $class_menabung = $row['menabung'] ? 'done' : 'not-done';
                    ?>
                    <tr>
                        <td><?php echo formatTanggalIndo($row['tanggal']); ?></td>
                        <td><span class="badge-kebiasaan <?php echo $class_bangun; ?>"><?php echo $row['bangun'] ? '✅' : '❌'; ?></span></td>
                        <td><span class="badge-kebiasaan <?php echo $class_ibadah; ?>"><?php echo $row['ibadah'] ? '✅' : '❌'; ?></span></td>
                        <td><span class="badge-kebiasaan <?php echo $class_olahraga; ?>"><?php echo $row['olahraga'] ? '✅' : '❌'; ?></span></td>
                        <td><span class="badge-kebiasaan <?php echo $class_sarapan; ?>"><?php echo $row['sarapan'] ? '✅' : '❌'; ?></span></td>
                        <td><span class="badge-kebiasaan <?php echo $class_membaca; ?>"><?php echo $row['membaca'] ? '✅' : '❌'; ?></span></td>
                        <td><span class="badge-kebiasaan <?php echo $class_membantu; ?>"><?php echo $row['membantu'] ? '✅' : '❌'; ?></span></td>
                        <td><span class="badge-kebiasaan <?php echo $class_menabung; ?>"><?php echo $row['menabung'] ? '✅' : '❌'; ?></span></td>
                        <td><strong><?php echo $total; ?>/7</strong></td>
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
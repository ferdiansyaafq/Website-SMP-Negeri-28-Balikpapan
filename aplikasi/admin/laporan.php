<?php
// aplikasi/admin/laporan.php
require_once '../includes/header-kaih.php'; // Header tetap
require_once '../../config/database.php';   // Path sudah disesuaikan, memanggil $pdo

/* ============================================================
   LOGIKA BACKEND (VERSI PDO)
   ============================================================ */
$tab = ($_GET['tab'] ?? 'kelas') === 'siswa' ? 'siswa' : 'kelas';
$filterGuruId   = (int)($_GET['guru_id'] ?? 0);
$filterKelas    = trim((string)($_GET['kelas'] ?? ''));
$filterTanggal  = trim((string)($_GET['tanggal'] ?? date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTanggal)) {
    $filterTanggal = date('Y-m-d');
}
$filterSiswaId  = (int)($_GET['siswa_id'] ?? 0);
$filterBulan    = trim((string)($_GET['bulan'] ?? date('Y-m')));
if (!preg_match('/^\d{4}-\d{2}$/', $filterBulan)) {
    $filterBulan = date('Y-m');
}

// Load dropdown data pakai PDO
$allGuru = $pdo->query("SELECT id, nama_guru, kelas AS wali_kelas FROM guru ORDER BY nama_guru ASC")->fetchAll(PDO::FETCH_ASSOC);
$allKelas = $pdo->query("SELECT id, nama_kelas FROM kaih_kelas ORDER BY nama_kelas ASC")->fetchAll(PDO::FETCH_ASSOC);
$allSiswa = $pdo->query("SELECT id, nama_siswa, kelas FROM siswa ORDER BY kelas ASC, nama_siswa ASC")->fetchAll(PDO::FETCH_ASSOC);

$guruWaliKelas = ''; $guruNama = '';
if ($filterGuruId > 0) {
    $stmtG = $pdo->prepare("SELECT nama_guru, kelas FROM guru WHERE id = ? LIMIT 1");
    $stmtG->execute([$filterGuruId]);
    $rowG = $stmtG->fetch(PDO::FETCH_ASSOC);
    if ($rowG) {
        $guruWaliKelas = (string)($rowG['kelas'] ?? '');
        $guruNama = (string)($rowG['nama_guru'] ?? '');
        if ($filterKelas === '') $filterKelas = $guruWaliKelas;
    }
}

// Data Tab Kelas
$kelasData = [];
$statTotal = $statTerkirim = $statBelum = $statValid = 0;
if ($tab === 'kelas' && $filterKelas !== '') {
    $stmt = $pdo->prepare(
        'SELECT s.id AS siswa_id, s.nisn, s.nama_siswa, s.kelas,
                lh.id AS laporan_id, lh.tanggal,
                lh.bangun, lh.ibadah, lh.olahraga, lh.sarapan,
                lh.membaca, lh.membantu, lh.menabung,
                lh.orang_tua_validated_at, lh.guru_validated_at
         FROM siswa s
         LEFT JOIN laporan_harian lh ON lh.siswa_id = s.id AND lh.tanggal = ?
         WHERE s.kelas = ? ORDER BY s.nama_siswa ASC'
    );
    $stmt->execute([$filterTanggal, $filterKelas]);
    $kelasData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $statTotal = count($kelasData);
    foreach ($kelasData as $it) {
        $sent = !empty($it['laporan_id']);
        if ($sent) $statTerkirim++; else $statBelum++;
        if ($sent && (!empty($it['orang_tua_validated_at']) || !empty($it['guru_validated_at']))) $statValid++;
    }
}

// Data Tab Siswa
$siswaInfo = null; $siswaLaporan = []; $statSiswaTotal = $statSiswaDays = 0;
if ($tab === 'siswa' && $filterSiswaId > 0) {
    $stmtS = $pdo->prepare("SELECT id, nisn, nama_siswa, kelas FROM siswa WHERE id = ? LIMIT 1");
    $stmtS->execute([$filterSiswaId]);
    $siswaInfo = $stmtS->fetch(PDO::FETCH_ASSOC);
    
    if ($siswaInfo) {
        $bd = DateTimeImmutable::createFromFormat('Y-m', $filterBulan) ?: new DateTimeImmutable();
        $startYmd = $bd->modify('first day of this month')->format('Y-m-d');
        $endYmd   = $bd->modify('last day of this month')->format('Y-m-d');
        
        $stmtL = $pdo->prepare(
            'SELECT lh.id, lh.tanggal, lh.bangun, lh.ibadah, lh.ibadah_catatan,
                    lh.olahraga, lh.olahraga_jenis, lh.sarapan, lh.sarapan_menu,
                    lh.membaca, lh.membaca_judul, lh.membaca_menit, lh.membantu, lh.membantu_jenis,
                    lh.menabung, lh.menabung_nominal, lh.orang_tua_validated_at, lh.guru_validated_at, lh.created_at
             FROM laporan_harian lh WHERE lh.siswa_id = ? AND lh.tanggal BETWEEN ? AND ? ORDER BY lh.tanggal DESC'
        );
        $stmtL->execute([$filterSiswaId, $startYmd, $endYmd]);
        $siswaLaporan = $stmtL->fetchAll(PDO::FETCH_ASSOC);
        
        $statSiswaDays = count($siswaLaporan);
        $daysInMonth = (int)(DateTimeImmutable::createFromFormat('Y-m', $filterBulan) ?: new DateTimeImmutable())->format('t');
        $statSiswaTotal = $daysInMonth;
    }
}

// Helper Data & Fungsi UI
$guruKelasMapJson = json_encode(array_column($allGuru, 'wali_kelas', 'id'), JSON_UNESCAPED_UNICODE);
$bulanNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
function fmtDate(string $ymd): string {
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);
    global $bulanNames;
    return $d ? (int)$d->format('j') . ' ' . ($bulanNames[(int)$d->format('n')] ?? $d->format('m')) . ' ' . $d->format('Y') : $ymd;
}
function iconCheck(bool $status) {
    return $status ? '<span style="color:#10b981; font-weight:bold;">✓</span>' : '<span style="color:#cbd5e1;">-</span>';
}
?>

<!-- ============================================================
     USER INTERFACE (UI) MODERN
     ============================================================ -->
<style>
    .modern-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
    .nav-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
    .nav-tab { padding: 10px 20px; border-radius: 8px; text-decoration: none; color: #64748b; font-weight: 600; transition: 0.2s; }
    .nav-tab.active { background: #2563eb; color: #fff; }
    .nav-tab:hover:not(.active) { background: #f1f5f9; color: #1e293b; }
    .filter-grid { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
    .filter-item { display: flex; flex-direction: column; gap: 5px; min-width: 180px; }
    .filter-item label { font-size: 12px; font-weight: bold; color: #475569; text-transform: uppercase; }
    .filter-item select, .filter-item input { padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; }
    .filter-item select:focus, .filter-item input:focus { border-color: #2563eb; }
    .btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
    
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
    .stat-box { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; }
    .stat-box .title { font-size: 12px; color: #64748b; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
    .stat-box .value { font-size: 24px; font-weight: 800; color: #1e293b; }
    
    .table-modern { width: 100%; border-collapse: collapse; font-size: 14px; }
    .table-modern th { background: #f1f5f9; padding: 12px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
    .table-modern td { padding: 12px; border-bottom: 1px solid #e2e8f0; color: #334155; }
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
    .badge-green { background: #d1fae5; color: #059669; }
    .badge-red { background: #fee2e2; color: #dc2626; }
    .badge-blue { background: #dbeafe; color: #2563eb; }
    
    .accordion-day { border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 10px; overflow: hidden; }
    .accordion-header { background: #f8fafc; padding: 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: bold; }
    .accordion-body { padding: 15px; display: none; background: #fff; border-top: 1px solid #e2e8f0; }
    .detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
    .detail-item { background: #f1f5f9; padding: 10px; border-radius: 6px; }
    .detail-item strong { display: block; font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 3px; }
</style>

<div class="content-area" style="padding: 20px;">
    
    <div style="margin-bottom: 20px;">
        <h2 style="margin:0; color:#1e293b;">Laporan Aktivitas Siswa</h2>
        <p style="margin:5px 0 0; color:#64748b;">Pantau kegiatan harian, cek 7 indikator, dan status validasi.</p>
    </div>

    <!-- TABS -->
    <div class="nav-tabs">
        <a href="?tab=kelas<?= $filterKelas ? '&kelas='.urlencode($filterKelas) : '' ?>&tanggal=<?= urlencode($filterTanggal) ?>" 
           class="nav-tab <?= $tab === 'kelas' ? 'active' : '' ?>">Laporan per Kelas</a>
        <a href="?tab=siswa<?= $filterKelas ? '&kelas='.urlencode($filterKelas) : '' ?><?= $filterSiswaId ? '&siswa_id='.$filterSiswaId : '' ?>&bulan=<?= urlencode($filterBulan) ?>" 
           class="nav-tab <?= $tab === 'siswa' ? 'active' : '' ?>">Detail per Siswa</a>
    </div>

    <!-- ==============================================
         VIEW: TAB LAPORAN KELAS
         ============================================== -->
    <?php if ($tab === 'kelas'): ?>
    <div class="modern-card">
        <form method="GET" class="filter-grid">
            <input type="hidden" name="tab" value="kelas">
            <div class="filter-item">
                <label>Guru / Wali Kelas</label>
                <select name="guru_id" id="selGuru">
                    <option value="">Semua Guru</option>
                    <?php foreach ($allGuru as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= $g['id'] == $filterGuruId ? 'selected' : '' ?>><?= $g['nama_guru'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Pilih Kelas</label>
                <select name="kelas" id="selKelas">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($allKelas as $k): ?>
                    <option value="<?= $k['nama_kelas'] ?>" <?= $k['nama_kelas'] == $filterKelas ? 'selected' : '' ?>><?= $k['nama_kelas'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Tanggal Laporan</label>
                <input type="date" name="tanggal" value="<?= $filterTanggal ?>">
            </div>
            <button type="submit" class="btn-primary">Tampilkan Data</button>
        </form>
    </div>

    <?php if ($filterKelas !== ''): ?>
    <div class="stats-grid">
        <div class="stat-box"><div class="title">Total Siswa</div><div class="value"><?= $statTotal ?></div></div>
        <div class="stat-box"><div class="title">Sudah Lapor</div><div class="value" style="color:#059669;"><?= $statTerkirim ?></div></div>
        <div class="stat-box"><div class="title">Belum Lapor</div><div class="value" style="color:#dc2626;"><?= $statBelum ?></div></div>
        <div class="stat-box"><div class="title">Tervalidasi</div><div class="value" style="color:#2563eb;"><?= $statValid ?></div></div>
    </div>

    <div class="modern-card" style="padding: 0; overflow-x: auto;">
        <table class="table-modern">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Nama Siswa</th>
                    <th>Status Laporan</th>
                    <th style="text-align:center;" title="Bangun Pagi">🌞</th>
                    <th style="text-align:center;" title="Ibadah">🤲</th>
                    <th style="text-align:center;" title="Olahraga">🏃</th>
                    <th style="text-align:center;" title="Sarapan">🍳</th>
                    <th style="text-align:center;" title="Membaca">📚</th>
                    <th style="text-align:center;" title="Membantu">🧹</th>
                    <th style="text-align:center;" title="Menabung">💰</th>
                    <th>Validasi Ortu</th>
                    <th>Validasi Guru</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kelasData)): ?>
                <tr><td colspan="13" style="text-align:center; padding:20px;">Belum ada data untuk kelas ini.</td></tr>
                <?php else: foreach ($kelasData as $i => $row): 
                    $sent = !empty($row['laporan_id']);
                    $ortu = $sent && !empty($row['orang_tua_validated_at']);
                    $guru = $sent && !empty($row['guru_validated_at']);
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td style="font-weight: bold;"><?= $row['nama_siswa'] ?> <br><span style="font-size:11px; color:#94a3b8; font-weight:normal;"><?= $row['nisn'] ?></span></td>
                    <td><?= $sent ? '<span class="badge badge-green">Terkirim</span>' : '<span class="badge badge-red">Belum</span>' ?></td>
                    
                    <?php if ($sent): ?>
                        <td style="text-align:center;"><?= iconCheck((bool)$row['bangun']) ?></td>
                        <td style="text-align:center;"><?= iconCheck((bool)$row['ibadah']) ?></td>
                        <td style="text-align:center;"><?= iconCheck((bool)$row['olahraga']) ?></td>
                        <td style="text-align:center;"><?= iconCheck((bool)$row['sarapan']) ?></td>
                        <td style="text-align:center;"><?= iconCheck((bool)$row['membaca']) ?></td>
                        <td style="text-align:center;"><?= iconCheck((bool)$row['membantu']) ?></td>
                        <td style="text-align:center;"><?= iconCheck((bool)$row['menabung']) ?></td>
                    <?php else: ?>
                        <td colspan="7" style="text-align:center; color:#cbd5e1; font-size:12px;">Tidak ada data</td>
                    <?php endif; ?>

                    <td><?= $ortu ? '<span class="badge badge-blue">✓ Ortu</span>' : '<span class="badge" style="background:#f1f5f9; color:#94a3b8;">Pending</span>' ?></td>
                    <td><?= $guru ? '<span class="badge badge-blue">✓ Guru</span>' : '<span class="badge" style="background:#f1f5f9; color:#94a3b8;">Pending</span>' ?></td>
                    <td><a href="?tab=siswa&kelas=<?= urlencode($row['kelas'] ?? '') ?>&siswa_id=<?= $row['siswa_id'] ?>&bulan=<?= substr($filterTanggal, 0, 7) ?>" style="color:#2563eb; text-decoration:none; font-weight:bold; font-size:12px;">Detail ➔</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ==============================================
         VIEW: TAB DETAIL SISWA
         ============================================== -->
    <?php elseif ($tab === 'siswa'): ?>
    <div class="modern-card">
        <form method="GET" class="filter-grid">
            <input type="hidden" name="tab" value="siswa">
            <div class="filter-item">
                <label>Filter Kelas</label>
                <select name="kelas" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($allKelas as $k): ?>
                    <option value="<?= $k['nama_kelas'] ?>" <?= $k['nama_kelas'] == $filterKelas ? 'selected' : '' ?>><?= $k['nama_kelas'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Pilih Siswa</label>
                <select name="siswa_id">
                    <option value="">-- Cari Siswa --</option>
                    <?php foreach ($allSiswa as $s): 
                        if($filterKelas !== '' && $s['kelas'] !== $filterKelas) continue;
                    ?>
                    <option value="<?= $s['id'] ?>" <?= $s['id'] == $filterSiswaId ? 'selected' : '' ?>><?= $s['nama_siswa'] ?> (<?= $s['kelas'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Pilih Bulan</label>
                <input type="month" name="bulan" value="<?= $filterBulan ?>">
            </div>
            <button type="submit" class="btn-primary">Tampilkan Histori</button>
        </form>
    </div>

    <?php if ($filterSiswaId > 0 && $siswaInfo): ?>
    <div class="modern-card" style="display: flex; justify-content: space-between; align-items: center; background: #eff6ff; border-color: #bfdbfe;">
        <div>
            <h3 style="margin:0; color:#1e3a8a;"><?= $siswaInfo['nama_siswa'] ?></h3>
            <p style="margin:5px 0 0; color:#3b82f6; font-weight:bold;">NISN: <?= $siswaInfo['nisn'] ?> | Kelas: <?= $siswaInfo['kelas'] ?></p>
        </div>
        <div style="text-align: right;">
            <p style="margin:0; font-size:12px; color:#64748b; text-transform:uppercase;">Total Laporan Bulan Ini</p>
            <h2 style="margin:0; color:#2563eb; font-size:32px;"><?= $statSiswaDays ?> <span style="font-size:16px; color:#94a3b8;">/ <?= $statSiswaTotal ?> Hari</span></h2>
        </div>
    </div>

    <!-- Accordion Histori -->
    <?php if (empty($siswaLaporan)): ?>
        <div class="modern-card" style="text-align:center; color:#64748b; padding: 40px;">Belum ada laporan dari siswa ini pada bulan terpilih.</div>
    <?php else: foreach ($siswaLaporan as $idx => $lh): 
        $score = (int)$lh['bangun'] + (int)$lh['ibadah'] + (int)$lh['olahraga'] + (int)$lh['sarapan'] + (int)$lh['membaca'] + (int)$lh['membantu'] + (int)$lh['menabung'];
        $dayId = 'detail-' . $idx;
    ?>
    <div class="accordion-day">
        <div class="accordion-header" onclick="document.getElementById('<?= $dayId ?>').style.display = document.getElementById('<?= $dayId ?>').style.display === 'block' ? 'none' : 'block'">
            <div>
                📅 <?= fmtDate($lh['tanggal']) ?> 
                <span class="badge badge-blue" style="margin-left: 10px;"><?= $score ?>/7 Kegiatan</span>
            </div>
            <div style="font-size:12px; font-weight:normal;">
                <?= !empty($lh['orang_tua_validated_at']) ? '<span style="color:#059669;">✓ Val Ortu</span>' : '<span style="color:#cbd5e1;">Pending Ortu</span>' ?> | 
                <?= !empty($lh['guru_validated_at']) ? '<span style="color:#059669;">✓ Val Guru</span>' : '<span style="color:#cbd5e1;">Pending Guru</span>' ?>
                ▼
            </div>
        </div>
        
        <div class="accordion-body" id="<?= $dayId ?>">
            <div class="detail-grid">
                <div class="detail-item"><strong>🌞 Bangun Pagi</strong> <?= $lh['bangun'] ? '<span style="color:#059669;font-weight:bold;">Tepat Waktu</span>' : 'Belum' ?></div>
                <div class="detail-item"><strong>🤲 Ibadah</strong> <?= $lh['ibadah'] ? 'Sudah' : 'Belum' ?><br><span style="font-size:12px; color:#64748b;"><?= htmlspecialchars($lh['ibadah_catatan']) ?></span></div>
                <div class="detail-item"><strong>🏃 Olahraga</strong> <?= $lh['olahraga'] ? 'Sudah' : 'Belum' ?><br><span style="font-size:12px; color:#64748b;"><?= htmlspecialchars($lh['olahraga_jenis']) ?></span></div>
                <div class="detail-item"><strong>🍳 Sarapan</strong> <?= $lh['sarapan'] ? 'Sudah' : 'Belum' ?><br><span style="font-size:12px; color:#64748b;"><?= htmlspecialchars($lh['sarapan_menu']) ?></span></div>
                <div class="detail-item"><strong>📚 Membaca</strong> <?= $lh['membaca'] ? 'Sudah (' . $lh['membaca_menit'] . ' Menit)' : 'Belum' ?><br><span style="font-size:12px; color:#64748b;"><?= htmlspecialchars($lh['membaca_judul']) ?></span></div>
                <div class="detail-item"><strong>🧹 Membantu Ortu</strong> <?= $lh['membantu'] ? 'Sudah' : 'Belum' ?><br><span style="font-size:12px; color:#64748b;"><?= htmlspecialchars($lh['membantu_jenis']) ?></span></div>
                <div class="detail-item"><strong>💰 Menabung</strong> <?= $lh['menabung'] ? 'Sudah' : 'Belum' ?><br><span style="font-size:12px; color:#64748b;">Rp <?= number_format((int)$lh['menabung_nominal'], 0, ',', '.') ?></span></div>
            </div>
            <div style="margin-top:15px; font-size:11px; color:#94a3b8; border-top:1px dashed #e2e8f0; padding-top:10px;">
                Dikirim: <?= date('d M Y H:i', strtotime($lh['created_at'])) ?>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
    
    <?php elseif($tab === 'siswa'): ?>
    <div class="modern-card" style="text-align:center; color:#64748b; padding: 40px;">
        Silakan pilih filter Kelas dan Nama Siswa di atas untuk melihat detail laporannya.
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<script>
const guruKelasMap = <?= $guruKelasMapJson ?>;
document.getElementById('selGuru')?.addEventListener('change', function() {
    const selKelas = document.getElementById('selKelas');
    if (this.value && guruKelasMap[this.value]) {
        for (let i = 0; i < selKelas.options.length; i++) {
            if (selKelas.options[i].value === guruKelasMap[this.value]) {
                selKelas.selectedIndex = i;
                break;
            }
        }
    }
});
</script>
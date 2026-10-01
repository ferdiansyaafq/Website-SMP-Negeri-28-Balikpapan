<?php
$page_title = "Jurnal Guru - SMP Negeri 28 Balikpapan";
include 'header.php';
include 'config/database.php';

// ============================================================
// 1. DATA MASTER
// ============================================================
$guru_list = $pdo->query("SELECT * FROM guru ORDER BY nama_guru ASC")->fetchAll();
$kelas_list = $pdo->query("SELECT * FROM kaih_kelas ORDER BY nama_kelas ASC")->fetchAll();

// ============================================================
// 2. FILTER
// ============================================================
$filter_guru = $_GET['guru'] ?? '';
$filter_kelas = $_GET['kelas'] ?? '';
$filter_tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// ============================================================
// 3. QUERY JURNAL
// ============================================================
$sql = "SELECT jg.*, 
               g.nama_guru, g.nip,
               k.nama_kelas
        FROM jurnal_guru jg
        LEFT JOIN guru g ON jg.guru_id = g.id
        LEFT JOIN kaih_kelas k ON jg.kelas_id = k.id
        WHERE 1=1";
$params = [];

if ($filter_guru) { 
    $sql .= " AND jg.guru_id = ?"; 
    $params[] = $filter_guru; 
}
if ($filter_kelas) { 
    $sql .= " AND jg.kelas_id = ?"; 
    $params[] = $filter_kelas; 
}
if ($filter_tanggal) { 
    $sql .= " AND jg.tanggal = ?"; 
    $params[] = $filter_tanggal; 
}

$sql .= " ORDER BY jg.tanggal DESC, jg.jam_mulai ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jurnal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 4. STATISTIK
// ============================================================
$total_guru = count($guru_list);
$total_jurnal = count($jurnal);
$total_kelas = count($kelas_list);

$total_hadir = 0;
$total_tidak_hadir = 0;
foreach ($jurnal as $j) {
    $total_hadir += (int)($j['jumlah_hadir'] ?? 0);
    $total_tidak_hadir += (int)($j['jumlah_tidak_hadir'] ?? 0);
}
$total_siswa = $total_hadir + $total_tidak_hadir;

// Jadwal hari ini (untuk ringkasan)
$hari_ini = date('Y-m-d');
$jadwal_hari_ini = array_filter($jurnal, function($j) use ($hari_ini) {
    return $j['tanggal'] === $hari_ini;
});

// Map nama hari
function getHariIndo($tanggal) {
    $hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
             'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    return $hari[date('l', strtotime($tanggal))] ?? date('l', strtotime($tanggal));
}
?>

<style>
.page-header {
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    color: white;
    padding: 50px 40px;
    text-align: center;
}
.page-header h1 { font-size: 32px; font-weight: 800; margin-bottom: 8px; }
.page-header p { font-size: 15px; opacity: 0.9; }

.container { max-width: 1200px; margin: 30px auto; padding: 0 40px; }

/* ============================================================
   STATISTIK CARD
   ============================================================ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}
.stat-card {
    background: white;
    padding: 22px 20px;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    text-align: center;
    border-top: 4px solid #7c3aed;
    transition: all 0.3s;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(124,58,237,0.15); }
.stat-card .number { font-size: 30px; font-weight: 800; color: #7c3aed; line-height: 1.1; }
.stat-card .label { font-size: 12px; color: #64748b; font-weight: 600; margin-top: 6px; text-transform: uppercase; letter-spacing: 0.5px; }

/* ============================================================
   FILTER BAR
   ============================================================ */
.filter-bar {
    background: white;
    padding: 20px 25px;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    margin-bottom: 25px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: flex-end;
}
.filter-group { flex: 1; min-width: 170px; }
.filter-group label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.filter-group select,
.filter-group input {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    font-family: inherit;
    background: #fafafa;
    transition: 0.2s;
}
.filter-group select:focus,
.filter-group input:focus {
    outline: none;
    border-color: #7c3aed;
    background: white;
}
.btn-filter {
    padding: 11px 24px;
    background: #7c3aed;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    font-size: 14px;
    transition: 0.2s;
}
.btn-filter:hover { background: #6d28d9; transform: translateY(-2px); }

/* ============================================================
   JURNAL CARD
   ============================================================ */
.jurnal-card {
    background: white;
    border-radius: 16px;
    padding: 22px 25px;
    margin-bottom: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    border-left: 5px solid #7c3aed;
    transition: 0.3s;
}
.jurnal-card:hover { 
    transform: translateX(5px); 
    box-shadow: 0 8px 25px rgba(124,58,237,0.15);
}

.jurnal-meta { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }

.badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.badge-guru { background: #ede9fe; color: #6d28d9; }
.badge-kelas { background: #e0f2fe; color: #0369a1; }
.badge-tanggal { background: #f1f5f9; color: #475569; }
.badge-jam { background: #fef3c7; color: #92400e; }
.badge-hari { background: #dbeafe; color: #1e40af; }

.jurnal-title { 
    font-size: 17px; 
    font-weight: 800; 
    color: #1e293b; 
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.jurnal-section {
    background: #f8fafc;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 10px;
}
.jurnal-section .section-label {
    font-size: 11px;
    font-weight: 800;
    color: #7c3aed;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    display: block;
}
.jurnal-section .section-content {
    color: #334155;
    font-size: 14px;
    line-height: 1.6;
}

.jurnal-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 10px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed #e2e8f0;
    font-size: 13px;
    color: #64748b;
}
.jurnal-info .info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8fafc;
    padding: 8px 12px;
    border-radius: 8px;
}
.jurnal-info .info-item strong { color: #1e293b; }

/* ============================================================
   EMPTY STATE
   ============================================================ */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
    background: white;
    border-radius: 16px;
}
.empty-state .icon { font-size: 64px; margin-bottom: 15px; }

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 768px) {
    .container { padding: 0 20px; }
    .page-header { padding: 35px 20px; }
    .page-header h1 { font-size: 24px; }
    .filter-group { min-width: 100%; }
    .btn-filter { width: 100%; }
    .jurnal-card { padding: 18px 16px; }
    .stat-card .number { font-size: 24px; }
}

@media (max-width: 480px) {
    .jurnal-title { font-size: 15px; }
    .jurnal-info { grid-template-columns: 1fr; }
}
</style>

<div class="page-header">
    <h1>📖 Jurnal Guru</h1>
    <p>Materi pembelajaran, jadwal mengajar, dan kehadiran siswa</p>
</div>

<div class="container">

    <!-- ============================================================
         STATISTIK
         ============================================================ -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="number"><?= $total_guru ?></div>
            <div class="label">👨‍🏫 Total Guru</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $total_jurnal ?></div>
            <div class="label">📖 Total Jurnal</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $total_kelas ?></div>
            <div class="label">🏫 Total Kelas</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $total_siswa ?></div>
            <div class="label">👥 Total Siswa</div>
        </div>
    </div>
    <form method="GET" class="filter-bar">
        <div class="filter-group">
            <label>Pilih Guru</label>
            <select name="guru">
                <option value="">-- Semua Guru --</option>
                <?php foreach ($guru_list as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= ($filter_guru == $g['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['nama_guru']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Pilih Kelas</label>
            <select name="kelas">
                <option value="">-- Semua Kelas --</option>
                <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= ($filter_kelas == $k['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['nama_kelas']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Pilih Tanggal</label>
            <input type="date" name="tanggal" value="<?= htmlspecialchars($filter_tanggal) ?>">
        </div>

        <button type="submit" class="btn-filter">🔍 Filter</button>
    </form>

    <!-- ============================================================
         DAFTAR JURNAL
         ============================================================ -->
    <?php if (empty($jurnal)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p style="font-size: 16px; font-weight: 600;">Belum ada jurnal guru untuk filter ini.</p>
            <p style="font-size: 13px; color: #cbd5e1; margin-top: 6px;">Coba ubah filter guru, kelas, atau tanggal.</p>
        </div>
    <?php else: ?>
        <?php foreach ($jurnal as $j): ?>
            <div class="jurnal-card">
                <!-- META: Guru, Kelas, Tanggal, Jam -->
                <div class="jurnal-meta">
                    <span class="badge badge-guru">👨‍🏫 <?= htmlspecialchars($j['nama_guru'] ?? '-') ?></span>
                    <span class="badge badge-kelas">🏫 <?= htmlspecialchars($j['nama_kelas'] ?? '-') ?></span>
                    <span class="badge badge-hari">📆 <?= getHariIndo($j['tanggal']) ?></span>
                    <span class="badge badge-tanggal">📅 <?= date('d M Y', strtotime($j['tanggal'])) ?></span>
                    <span class="badge badge-jam">⏰ <?= substr($j['jam_mulai'], 0, 5) ?> - <?= substr($j['jam_selesai'], 0, 5) ?></span>
                </div>

                <!-- MATA PELAJARAN -->
                <div class="jurnal-title">
                    📖 <?= htmlspecialchars($j['mata_pelajaran']) ?>
                </div>

                <!-- MATERI -->
                <div class="jurnal-section">
                    <span class="section-label">📝 Materi yang Diajarkan</span>
                    <div class="section-content">
                        <?= nl2br(htmlspecialchars($j['materi'])) ?>
                    </div>
                </div>

                <!-- JADWAL -->
                <div class="jurnal-section">
                    <span class="section-label">📅 Jadwal</span>
                    <div class="section-content">
                        <strong><?= getHariIndo($j['tanggal']) ?>, <?= date('d M Y', strtotime($j['tanggal'])) ?></strong><br>
                        Jam: <strong><?= substr($j['jam_mulai'], 0, 5) ?> - <?= substr($j['jam_selesai'], 0, 5) ?></strong> |
                        Kelas: <strong><?= htmlspecialchars($j['nama_kelas'] ?? '-') ?></strong>
                    </div>
                </div>

                
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
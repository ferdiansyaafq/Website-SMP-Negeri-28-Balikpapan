<?php
$page_title = "Jurnal Guru - SMP Negeri 28 Balikpapan";
require_once '../includes/header-kaih.php';
require_once __DIR__ . '/../../config/database.php';

// ====== Ambil data guru yang login ======
$guru_id = $_SESSION['guru_id'] ?? null;
$guru_kelas_id = $_SESSION['kelas_id'] ?? null;

if (!$guru_id && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT guru_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $guru_id = $stmt->fetchColumn();
}

$kelasList = $pdo->query("SELECT id, nama_kelas FROM kaih_kelas ORDER BY nama_kelas")->fetchAll(PDO::FETCH_ASSOC);

// ====== Filter minggu ======
$minggu_input = $_GET['minggu'] ?? date('Y-m-d');
$ts = strtotime($minggu_input);
$senin = date('Y-m-d', strtotime('monday this week', $ts));
$minggu = date('Y-m-d', strtotime('sunday this week', $ts));

$pesan = '';
$tipe = 'success';

// ====== Proses POST ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_jurnal'])) {
    $kelas_id       = (int)($_POST['kelas_id'] ?? 0);
    $tanggal        = $_POST['tanggal'] ?? date('Y-m-d');
    $jam_mulai      = $_POST['jam_mulai'] ?? '07:00';
    $jam_selesai    = $_POST['jam_selesai'] ?? '08:30';
    $jam_ke         = trim($_POST['jam_ke'] ?? '');
    $mata_pelajaran = trim($_POST['mata_pelajaran'] ?? '');
    $materi         = trim($_POST['materi'] ?? '');
    $uraian         = trim($_POST['uraian_materi'] ?? '');
    $catatan        = trim($_POST['catatan'] ?? '');
    $absensi        = $_POST['absensi'] ?? [];  // ← array [siswa_id => status]

    if (!$kelas_id || !$tanggal || !$mata_pelajaran || !$materi) {
        $pesan = 'Mohon lengkapi: Kelas, Tanggal, Mata Pelajaran, dan Materi.';
        $tipe = 'error';
    } else {
        try {
            $pdo->beginTransaction();

            // Hitung jumlah hadir & tidak hadir
            $hadir = 0; $tidak = 0;
            foreach ($absensi as $status) {
                if ($status === 'hadir') $hadir++;
                else $tidak++;
            }

            // Simpan jurnal
            $catatan_full = "Jam ke: $jam_ke\nUraian: $uraian\nCatatan: $catatan";
            $sql = "INSERT INTO jurnal_guru 
                    (guru_id, kelas_id, tanggal, jam_mulai, jam_selesai, mata_pelajaran, materi,
                     jumlah_hadir, jumlah_tidak_hadir, catatan)
                    VALUES (?,?,?,?,?,?,?,?,?,?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $guru_id, $kelas_id, $tanggal, $jam_mulai, $jam_selesai,
                $mata_pelajaran, $materi, $hadir, $tidak, $catatan_full
            ]);

            // Simpan absensi per siswa (pakai REPLACE biar tidak dobel)
            if (!empty($absensi)) {
                $stmtAbs = $pdo->prepare("
                    INSERT INTO absensi (siswa_id, tanggal, status, deskripsi, catatan, created_at)
                    VALUES (?, ?, ?, 'Sesi kelas reguler', 'Diinput guru via Jurnal', NOW())
                    ON DUPLICATE KEY UPDATE 
                        status = VALUES(status),
                        catatan = VALUES(catatan),
                        updated_at = NOW()
                ");
                foreach ($absensi as $siswa_id => $status) {
                    if (!in_array($status, ['hadir','izin','sakit','alpha'])) continue;
                    $stmtAbs->execute([(int)$siswa_id, $tanggal, $status]);
                }
            }

            $pdo->commit();
            $pesan = 'Jurnal dan absensi berhasil disimpan.';

        } catch (PDOException $e) {
            $pdo->rollBack();
            $pesan = 'Gagal menyimpan: ' . $e->getMessage();
            $tipe = 'error';
        }
    }
}

// ====== Ambil jurnal minggu ini ======
$sql = "SELECT jg.*, k.nama_kelas
        FROM jurnal_guru jg
        LEFT JOIN kaih_kelas k ON jg.kelas_id = k.id
        WHERE jg.guru_id = ? AND jg.tanggal BETWEEN ? AND ?
        ORDER BY jg.tanggal DESC, jg.jam_mulai ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$guru_id, $senin, $minggu]);
$jurnalList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../../assets/css/jurnal_guru.css">

<div class="dashboard-wrapper">

    <div class="dashboard-header">
        <div>
            <h1>📓 Jurnal Guru</h1>
            <p class="subtitle">Catat kegiatan mengajar harian dan lihat rekap mingguan</p>
        </div>
        <div class="header-date"><?= date('l, d F Y') ?></div>
    </div>

    <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe ?>">
            <?= $tipe === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <!-- ====== FORM INPUT ====== -->
    <div class="card">
        <h2 class="card-title">Tambah Jurnal Mengajar</h2>

        <form method="POST" class="form-jurnal" id="formJurnal">
            <div class="form-grid">
                <div class="form-group">
                    <label>Kelas <span class="required">*</span></label>
                    <select name="kelas_id" id="selectKelas" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($kelasList as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= ($guru_kelas_id == $k['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tanggal <span class="required">*</span></label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label>Jam Ke-</label>
                    <input type="text" name="jam_ke" placeholder="Contoh: 3-4">
                </div>

                <div class="form-group">
                  <label>Jam Mulai</label>
                  <input type="text" name="jam_mulai" placeholder="Contoh: 07:00" pattern="[0-9]{2}:[0-9]{2}" maxlength="5" title="Format: HH:MM (contoh 07:00)">
                </div>

                <div class="form-group">
                    <label>Jam Selesai</label>
                    <input type="text" name="jam_selesai" placeholder="Contoh: 08:30" pattern="[0-9]{2}:[0-9]{2}" maxlength="5" title="Format: HH:MM (contoh 08:30)">
                </div>

                <div class="form-group">
                    <label>Mata Pelajaran <span class="required">*</span></label>
                    <input type="text" name="mata_pelajaran" placeholder="Contoh: Bahasa Indonesia" required>
                </div>

                <div class="form-group full-width">
                    <label>Materi <span class="required">*</span></label>
                    <input type="text" name="materi" placeholder="Contoh: Teks Deskripsi" required>
                </div>

                <div class="form-group full-width">
                    <label>Uraian Materi</label>
                    <textarea name="uraian_materi" rows="3" placeholder="Jelaskan ringkasan materi yang diajarkan..."></textarea>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- KEHADIRAN SISWA — DROPDOWN PER SISWA         -->
            <!-- ============================================ -->
            <div class="absensi-block" id="absensiBlock">
                <div class="absensi-header">
                    <label>✅ Kehadiran Siswa</label>
                    <div class="absensi-actions">
                        <button type="button" class="btn-mini btn-mini-green" onclick="setAllAbsensi('hadir')">Semua Hadir</button>
                        <button type="button" class="btn-mini btn-mini-red" onclick="setAllAbsensi('alpha')">Semua Alpha</button>
                        <span class="absensi-counter" id="absensiCounter">0 / 0 Hadir</span>
                    </div>
                </div>

                <div class="absensi-list" id="absensiList">
                    <div class="absensi-empty">Pilih kelas terlebih dahulu untuk memuat daftar siswa.</div>
                </div>
            </div>

            <div class="form-group full-width" style="margin-top:18px">
                <label>Catatan Tambahan</label>
                <textarea name="catatan" rows="2" placeholder="Catatan khusus (opsional)..."></textarea>
            </div>

            <button type="submit" name="simpan_jurnal" class="btn-primary">
                💾 Simpan Jurnal & Absensi
            </button>
        </form>
    </div>

    <!-- ====== REKAP MINGGUAN ====== -->
    <div class="card">
        <div class="card-header-row">
            <h2 class="card-title">📅 Rekap Mingguan</h2>

            <form method="GET" class="filter-form">
                <label>Pilih Minggu:</label>
                <input type="date" name="minggu" value="<?= htmlspecialchars($minggu_input) ?>">
                <button type="submit" class="btn-filter">Tampilkan</button>
            </form>

            <a class="btn-excel" href="export_jurnal_guru.php?minggu=<?= urlencode($senin) ?>&guru_id=<?= (int)$guru_id ?>">
                ⬇️ Unduh Excel
            </a>
        </div>

        <p class="periode-info">
            Periode: <strong><?= date('d M Y', strtotime($senin)) ?></strong>
            s/d <strong><?= date('d M Y', strtotime($minggu)) ?></strong>
        </p>

        <div class="table-wrapper">
            <table class="table-jurnal">
                <thead>
                    <tr>
                        <th>No</th><th>Tanggal</th><th>Jam Ke-</th><th>Jam</th>
                        <th>Kelas</th><th>Mata Pelajaran</th><th>Materi</th>
                        <th>Uraian Materi</th><th>Hadir</th><th>Tidak Hadir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jurnalList)): ?>
                        <tr><td colspan="10" class="text-center">Belum ada jurnal pada minggu ini.</td></tr>
                    <?php else: $no=1; foreach ($jurnalList as $row):
                        $cat = $row['catatan'] ?? '';
                        preg_match('/Jam ke:\s*(.+)/', $cat, $mJam);
                        preg_match('/Uraian:\s*(.+?)(?:\nCatatan:|$)/s', $cat, $mUraian);
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td><?= htmlspecialchars(trim($mJam[1] ?? '-')) ?></td>
                            <td><?= substr($row['jam_mulai'],0,5) ?> - <?= substr($row['jam_selesai'],0,5) ?></td>
                            <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['mata_pelajaran']) ?></td>
                            <td><?= htmlspecialchars($row['materi']) ?></td>
                            <td class="uraian-cell"><?= nl2br(htmlspecialchars(trim($mUraian[1] ?? '-'))) ?></td>
                            <td><span class="badge-hadir"><?= $row['jumlah_hadir'] ?></span></td>
                            <td><span class="badge-tidak"><?= $row['jumlah_tidak_hadir'] ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ====== JAVASCRIPT ====== -->
<script>
const selectKelas = document.getElementById('selectKelas');
const absensiList = document.getElementById('absensiList');
const absensiCounter = document.getElementById('absensiCounter');

// Muat daftar siswa saat kelas berubah
selectKelas.addEventListener('change', function () {
    const kelasId = this.value;
    if (!kelasId) {
        absensiList.innerHTML = '<div class="absensi-empty">Pilih kelas terlebih dahulu untuk memuat daftar siswa.</div>';
        updateCounter();
        return;
    }

    absensiList.innerHTML = '<div class="absensi-loading">⏳ Memuat daftar siswa...</div>';

    fetch('get_siswa.php?kelas_id=' + encodeURIComponent(kelasId))
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data.length) {
                absensiList.innerHTML = '<div class="absensi-empty">Tidak ada siswa di kelas ini.</div>';
                updateCounter();
                return;
            }

            let html = '';
            res.data.forEach((s, i) => {
                html += `
                <div class="absensi-row">
                    <div class="absensi-nama">
                        <span class="absensi-no">${i + 1}.</span>
                        <span>${escapeHtml(s.nama_siswa)}</span>
                        <small class="absensi-nisn">${escapeHtml(s.nisn)}</small>
                    </div>
                    <div class="absensi-options">
                        ${radioOption(s.id, 'hadir', 'Hadir', 'green', true)}
                        ${radioOption(s.id, 'sakit', 'Sakit', 'yellow')}
                        ${radioOption(s.id, 'izin', 'Izin', 'blue')}
                        ${radioOption(s.id, 'alpha', 'Alpha', 'red')}
                    </div>
                </div>`;
            });
            absensiList.innerHTML = html;
            bindAbsensiEvents();
            updateCounter();
        })
        .catch(() => {
            absensiList.innerHTML = '<div class="absensi-empty">Gagal memuat daftar siswa.</div>';
        });
});

function radioOption(siswaId, value, label, color, checked = false) {
    const name = 'absensi[' + siswaId + ']';
    const id = 'abs_' + siswaId + '_' + value;
    return `
        <label class="absensi-opt absensi-opt-${color}" for="${id}">
            <input type="radio" id="${id}" name="${name}" value="${value}" ${checked ? 'checked' : ''}>
            <span>${label}</span>
        </label>`;
}

function bindAbsensiEvents() {
    document.querySelectorAll('.absensi-opt input[type="radio"]').forEach(r => {
        r.addEventListener('change', updateCounter);
    });
}

function setAllAbsensi(status) {
    document.querySelectorAll('.absensi-row').forEach(row => {
        const r = row.querySelector(`input[type="radio"][value="${status}"]`);
        if (r) { r.checked = true; }
    });
    updateCounter();
}

function updateCounter() {
    const total = document.querySelectorAll('.absensi-row').length;
    const hadir = document.querySelectorAll('.absensi-opt-hadir input:checked').length;
    absensiCounter.textContent = `${hadir} / ${total} Hadir`;
}

function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}

// Kalau kelas sudah terpilih saat load (misal dari session), muat otomatis
if (selectKelas.value) {
    selectKelas.dispatchEvent(new Event('change'));
}
</script>
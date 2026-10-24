<?php
// aplikasi/kepsek/penilaian.php
require_once '../includes/header-kaih.php';
require_once '../../config/database.php';

// Pastikan hanya role kepsek
if (($_SESSION['role'] ?? '') !== 'kepsek') {
    header('Location: ../../login.php');
    exit;
}

$error = '';
$kepsek_id = $_SESSION['user_id'] ?? 0;

// Ambil daftar guru (kecuali kepala sekolah sendiri)
$stmtGuru = $pdo->query("SELECT id, nip, nama_guru, kelas, jabatan FROM guru WHERE jabatan != 'Kepala Sekolah' ORDER BY nama_guru ASC");
$daftar_guru = $stmtGuru->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar kelas dari kaih_kelas
$daftar_kelas = $pdo->query("SELECT nama_kelas FROM kaih_kelas WHERE nama_kelas != 'Belum Ada Kelas' ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll(PDO::FETCH_COLUMN);

// Ambil seluruh indikator rubrik dari database
$stmtRubrik = $pdo->query("SELECT * FROM supervisi_rubrik ORDER BY urutan ASC, id ASC");
$rubrik_items = $stmtRubrik->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan indikator berdasarkan aspek
$rubrik_by_aspek = [];
foreach ($rubrik_items as $item) {
    $rubrik_by_aspek[$item['aspek']][] = $item;
}

// Skala Poin Pilihan Ganda & Bobot Persentase
$pilihan_skor = [
    'A' => ['persen' => 100.0, 'label' => 'Sangat Baik (100%)', 'desc' => 'Seluruh kriteria terpenuhi sangat konsisten'],
    'B' => ['persen' => 75.0,  'label' => 'Baik (75%)',        'desc' => 'Sebagian besar kriteria terpenuhi efektif'],
    'C' => ['persen' => 50.0,  'label' => 'Cukup (50%)',       'desc' => 'Kriteria terpenuhi sebagian, butuh penguatan'],
    'D' => ['persen' => 25.0,  'label' => 'Kurang (25%)',      'desc' => 'Sebagian besar belum tampak'],
    'E' => ['persen' => 0.0,   'label' => 'Sangat Kurang (0%)','desc' => 'Tidak tampak sama sekali'],
];

// Proses Simpan Penilaian Form Rubrik
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guru_id = (int)($_POST['guru_id'] ?? 0);
    $tanggal_observasi = trim($_POST['tanggal_observasi'] ?? '');
    $semester = trim($_POST['semester'] ?? 'Ganjil');
    $tahun_ajaran = trim($_POST['tahun_ajaran'] ?? '2026/2027');
    $mata_pelajaran = trim($_POST['mata_pelajaran'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $catatan_kelebihan = trim($_POST['catatan_kelebihan'] ?? '');
    $catatan_kelemahan = trim($_POST['catatan_kelemahan'] ?? '');
    $rekomendasi_tindak_lanjut = trim($_POST['rekomendasi_tindak_lanjut'] ?? '');

    $rubrik_answers = $_POST['rubrik'] ?? [];

    if ($guru_id <= 0 || empty($tanggal_observasi) || empty($mata_pelajaran) || empty($kelas)) {
        $error = 'Harap lengkapi informasi guru, mata pelajaran, kelas, dan tanggal observasi!';
    } elseif (count($rubrik_answers) < count($rubrik_items)) {
        $error = 'Harap jawab semua ' . count($rubrik_items) . ' indikator rubrik penilaian sebelum menyimpan!';
    } else {
        try {
            $pdo->beginTransaction();

            // Hitung total skor persentase
            $total_persen = 0.0;
            $detail_records = [];

            foreach ($rubrik_items as $item) {
                $rubrik_id = (int)$item['id'];
                $pilihan = $rubrik_answers[$rubrik_id] ?? 'C';
                $skor = $pilihan_skor[$pilihan]['persen'] ?? 50.0;
                $catatan_khusus = trim($_POST['catatan_rubrik'][$rubrik_id] ?? '');

                $total_persen += $skor;
                $detail_records[] = [
                    'rubrik_id' => $rubrik_id,
                    'pilihan' => $pilihan,
                    'skor_persen' => $skor,
                    'catatan_khusus' => $catatan_khusus
                ];
            }

            $skor_akhir = round($total_persen / count($rubrik_items), 2);

            // Tentukan predikat
            if ($skor_akhir >= 86.0) {
                $predikat = 'Sangat Baik';
            } elseif ($skor_akhir >= 71.0) {
                $predikat = 'Baik';
            } elseif ($skor_akhir >= 56.0) {
                $predikat = 'Cukup';
            } else {
                $predikat = 'Kurang';
            }

            // Simpan ke tabel supervisi_penilaian
            $stmtInsert = $pdo->prepare("
                INSERT INTO supervisi_penilaian 
                (guru_id, kepsek_id, tanggal_observasi, semester, tahun_ajaran, mata_pelajaran, kelas, skor_akhir, predikat, catatan_kelebihan, catatan_kelemahan, rekomendasi_tindak_lanjut, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmtInsert->execute([
                $guru_id, $kepsek_id, $tanggal_observasi, $semester, $tahun_ajaran, 
                $mata_pelajaran, $kelas, $skor_akhir, $predikat,
                $catatan_kelebihan, $catatan_kelemahan, $rekomendasi_tindak_lanjut
            ]);
            $penilaian_id = (int)$pdo->lastInsertId();

            // Simpan detail jawaban rubrik
            $stmtDetail = $pdo->prepare("
                INSERT INTO supervisi_penilaian_detail 
                (penilaian_id, rubrik_id, pilihan, skor_persen, catatan_khusus)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($detail_records as $rec) {
                $stmtDetail->execute([
                    $penilaian_id,
                    $rec['rubrik_id'],
                    $rec['pilihan'],
                    $rec['skor_persen'],
                    $rec['catatan_khusus']
                ]);
            }

            $pdo->commit();

            // Sesuai ketentuan: langsung tampilkan grafiknya setelah mengisi rubrik penilaian
            header("Location: analisis.php?id=" . $penilaian_id . "&saved=1");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal menyimpan penilaian supervisi: ' . $e->getMessage();
        }
    }
}
?>

<link rel="stylesheet" href="../../assets/css/kepsek.css">
<script src="../../assets/js/kepsek.js"></script>

<div class="kepsek-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="font-size: 22px; color: #1e293b; font-weight: 700; margin-bottom: 4px;">
                📝 Rubrik Observasi & Penilaian Supervisi Guru
            </h2>
            <p style="font-size: 13px; color: #64748b;">
                Pilihlah opsi A (100%), B (75%), C (50%), D (25%), atau E (0%) untuk setiap indikator kompetensi mengajar guru.
            </p>
        </div>
        <a href="supervisi.php" class="btn-kepsek-action" style="background: #f1f5f9; color: #475569; box-shadow: none;">
            ← Kembali ke Daftar Supervisi
        </a>
    </div>

    <?php if ($error): ?>
        <div style="background: #fee2e2; border-left: 4px solid #ef4444; color: #b91c1c; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 600;">
            ⚠️ <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="formRubrikSupervisi" action="">
        <!-- Informasi Umum Observasi -->
        <div class="kepsek-card">
            <div class="kepsek-card-title">
                <h3>📌 Data Pelaksanaan Supervisi Guru</h3>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Pilih Guru yang Diobservasi <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="guru_id" id="selectGuru" required style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none; background: white;" onchange="updateGuruInfo(this)">
                        <option value="">-- Pilih Guru --</option>
                        <?php foreach ($daftar_guru as $g): ?>
                            <option value="<?= $g['id']; ?>" 
                                    data-kelas="<?= htmlspecialchars($g['kelas'] ?? ''); ?>"
                                    data-jabatan="<?= htmlspecialchars($g['jabatan'] ?? ''); ?>"
                                    <?= (isset($_POST['guru_id']) && (int)$_POST['guru_id'] === (int)$g['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($g['nama_guru']); ?> (<?= htmlspecialchars($g['jabatan'] ?? 'Guru'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Mata Pelajaran <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="mata_pelajaran" id="inputMapel" required 
                           value="<?= htmlspecialchars($_POST['mata_pelajaran'] ?? ''); ?>"
                           placeholder="Cth: Bahasa Indonesia, Matematika, IPA" 
                           style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Kelas yang Diobservasi <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="kelas" id="inputKelas" required style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none; background: white;">
                        <option value="">-- Pilih Kelas --</option>
                        <?php 
                        $selectedKelas = $_POST['kelas'] ?? '';
                        foreach ($daftar_kelas as $kls): 
                        ?>
                            <option value="<?= htmlspecialchars($kls); ?>" <?= ($selectedKelas === $kls) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($kls); ?>
                            </option>
                        <?php endforeach; ?>
                        
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Tanggal Observasi <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="date" name="tanggal_observasi" required 
                           value="<?= htmlspecialchars($_POST['tanggal_observasi'] ?? date('Y-m-d')); ?>"
                           style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Semester
                    </label>
                    <select name="semester" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none; background: white;">
                        <option value="Ganjil" <?= (($_POST['semester'] ?? '') === 'Ganjil') ? 'selected' : ''; ?>>Semester Ganjil</option>
                        <option value="Genap" <?= (($_POST['semester'] ?? '') === 'Genap') ? 'selected' : ''; ?>>Semester Genap</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Tahun Ajaran
                    </label>
                    <input type="text" name="tahun_ajaran" 
                           value="<?= htmlspecialchars($_POST['tahun_ajaran'] ?? '2026/2027'); ?>"
                           style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none;">
                </div>
            </div>
        </div>

        <!-- Bagian Rubrik Penilaian per Aspek -->
        <?php foreach ($rubrik_by_aspek as $aspek_nama => $items): ?>
            <div class="rubrik-section">
                <div class="rubrik-section-header">
                    <span>📂</span>
                    <span>Aspek: <?= htmlspecialchars($aspek_nama); ?></span>
                </div>

                <?php foreach ($items as $idx => $r): ?>
                    <div class="rubrik-item">
                        <div class="rubrik-item-title">
                            <?= ($r['urutan']); ?>. <?= htmlspecialchars($r['indikator']); ?>
                        </div>
                        <div class="rubrik-item-desc">
                            <?= htmlspecialchars($r['deskripsi']); ?>
                        </div>

                        <!-- Radio Pilihan Ganda A - E -->
                        <div class="rubrik-options-grid">
                            <?php foreach ($pilihan_skor as $poin => $opt): 
                                $inputName = "rubrik[{$r['id']}]";
                                $isSelected = isset($_POST['rubrik'][$r['id']]) && $_POST['rubrik'][$r['id']] === $poin;
                                $classOpt = 'opt-' . strtolower($poin);
                            ?>
                                <label class="rubrik-option-label <?= $classOpt; ?> <?= $isSelected ? 'selected' : ''; ?>">
                                    <input type="radio" 
                                           name="<?= $inputName; ?>" 
                                           value="<?= $poin; ?>" 
                                           data-skor="<?= $opt['persen']; ?>" 
                                           required 
                                           <?= $isSelected ? 'checked' : ''; ?>>
                                    <div class="rubrik-option-point">Poin <?= $poin; ?></div>
                                    <div class="rubrik-option-pct"><?= $opt['persen']; ?>%</div>
                                    <div class="rubrik-option-desc"><?= $opt['label']; ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Catatan Khusus Indikator (Opsional) -->
                        <div style="margin-top: 12px;">
                            <input type="text" 
                                   name="catatan_rubrik[<?= $r['id']; ?>]" 
                                   value="<?= htmlspecialchars($_POST['catatan_rubrik'][$r['id']] ?? ''); ?>"
                                   placeholder="Catatan observasi khusus untuk poin ini (opsional)..." 
                                   style="width: 100%; padding: 7px 12px; border: 1px dashed #cbd5e1; border-radius: 8px; font-size: 12px; outline: none; background: #fafafa;">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <!-- Catatan Kualitatif & Rekomendasi Kepala Sekolah -->
        <div class="kepsek-card">
            <div class="kepsek-card-title">
                <h3>💬 Catatan Kualitatif & Rekomendasi Pembinaan</h3>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #166534; margin-bottom: 6px;">
                        ⭐ Kekuatan & Kelebihan Guru yang Terlihat:
                    </label>
                    <textarea name="catatan_kelebihan" rows="4" 
                              placeholder="Tuliskan aspek-aspek pembelajaran yang sudah sangat baik dilakukan oleh guru di kelas..."
                              style="width: 100%; padding: 12px; border: 1.5px solid #bbf7d0; border-radius: 10px; font-size: 13px; outline: none; font-family: inherit;"><?= htmlspecialchars($_POST['catatan_kelebihan'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #991b1b; margin-bottom: 6px;">
                        ⚠️ Area Kelemahan yang Butuh Peningkatan:
                    </label>
                    <textarea name="catatan_kelemahan" rows="4" 
                              placeholder="Tuliskan bagian-bagian spesifik di mana guru tampak kurang optimal atau perlu perbaikan..."
                              style="width: 100%; padding: 12px; border: 1.5px solid #fecaca; border-radius: 10px; font-size: 13px; outline: none; font-family: inherit;"><?= htmlspecialchars($_POST['catatan_kelemahan'] ?? ''); ?></textarea>
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: #075985; margin-bottom: 6px;">
                    🎯 Rekomendasi Tindak Lanjut & Supervisi Klinis Kepala Sekolah:
                </label>
                <textarea name="rekomendasi_tindak_lanjut" rows="3" 
                          placeholder="Rencana tindak lanjut, misalnya: bimbingan teman sejawat, workshop pembuatan modul ajar, supervisi klinis lanjutan..."
                          style="width: 100%; padding: 12px; border: 1.5px solid #bae6fd; border-radius: 10px; font-size: 13px; outline: none; font-family: inherit;"><?= htmlspecialchars($_POST['rekomendasi_tindak_lanjut'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Sticky Floating Score Bar -->
        <div class="floating-score-box">
            <div class="floating-score-info">
                <div>
                    <div style="font-size: 12px; opacity: 0.8;">Estimasi Skor Kinerja:</div>
                    <div class="floating-score-val" id="liveScoreValue">0.0%</div>
                </div>
                <div>
                    <span id="livePredikat" class="predikat-badge predikat-cukup">Belum Dinilai</span>
                </div>
                <div style="font-size: 12px; opacity: 0.85;">
                    Terisi: <strong id="liveAnsweredCount">0</strong> / <span id="liveTotalQuestions"><?= count($rubrik_items); ?></span> Indikator
                </div>
            </div>
            <div>
                <button type="submit" class="btn-kepsek-action" style="background: #10b981; color: white;">
                    💾 Simpan & Tampilkan Grafik Hasil
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function updateGuruInfo(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    if (!selectedOption || !selectedOption.value) return;

    const jabatan = selectedOption.getAttribute('data-jabatan') || '';
    const kelas = selectedOption.getAttribute('data-kelas') || '';

    const inputMapel = document.getElementById('inputMapel');
    const inputKelas = document.getElementById('inputKelas');

    if (jabatan && inputMapel && !inputMapel.value) {
        // Ekstrak nama mapel dari jabatan, cth "Guru B. Indonesia" -> "Bahasa Indonesia"
        let mapelClean = jabatan.replace(/guru/i, '').trim();
        if (mapelClean.startsWith('B.')) mapelClean = mapelClean.replace('B.', 'Bahasa');
        inputMapel.value = mapelClean;
    }

    if (kelas && inputKelas && !inputKelas.value) {
        for (let i = 0; i < inputKelas.options.length; i++) {
            if (inputKelas.options[i].value === kelas) {
                inputKelas.selectedIndex = i;
                break;
            }
        }
    }
}
</script>

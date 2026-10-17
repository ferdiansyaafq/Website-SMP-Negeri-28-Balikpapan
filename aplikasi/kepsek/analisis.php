<?php
// aplikasi/kepsek/analisis.php
require_once '../includes/header-kaih.php';
require_once '../../config/database.php';

// Pastikan hanya role kepsek
if (($_SESSION['role'] ?? '') !== 'kepsek') {
    header('Location: ../../login.php');
    exit;
}

$penilaian_id = (int)($_GET['id'] ?? 0);
$is_saved = isset($_GET['saved']);

// Jika tidak ada ID yang diberikan, ambil penilaian terbaru
if ($penilaian_id <= 0) {
    $latestId = $pdo->query("SELECT id FROM supervisi_penilaian ORDER BY tanggal_observasi DESC, id DESC LIMIT 1")->fetchColumn();
    if ($latestId) {
        $penilaian_id = (int)$latestId;
    }
}

// Ambil seluruh daftar sesi supervisi untuk opsi dropdown pemilih
$stmtAll = $pdo->query("
    SELECT sp.id, sp.tanggal_observasi, sp.mata_pelajaran, sp.kelas, sp.skor_akhir, sp.predikat, g.nama_guru 
    FROM supervisi_penilaian sp
    JOIN guru g ON sp.guru_id = g.id
    ORDER BY sp.tanggal_observasi DESC, sp.id DESC
");
$semua_supervisi = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

$penilaian = null;
$detail_items = [];
$aspek_scores = [];
$kelemahan_items = [];
$kekuatan_items = [];

if ($penilaian_id > 0) {
    // Ambil data sesi supervisi
    $stmt = $pdo->prepare("
        SELECT sp.*, g.nama_guru, g.nip, g.alamat, g.no_hp
        FROM supervisi_penilaian sp
        JOIN guru g ON sp.guru_id = g.id
        WHERE sp.id = ?
    ");
    $stmt->execute([$penilaian_id]);
    $penilaian = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($penilaian) {
        // Ambil rincian penilaian per indikator
        $stmtDetail = $pdo->prepare("
            SELECT spd.*, r.aspek, r.kode_aspek, r.indikator, r.deskripsi, r.urutan
            FROM supervisi_penilaian_detail spd
            JOIN supervisi_rubrik r ON spd.rubrik_id = r.id
            WHERE spd.penilaian_id = ?
            ORDER BY r.urutan ASC, r.id ASC
        ");
        $stmtDetail->execute([$penilaian_id]);
        $detail_items = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

        // Agregat skor per aspek untuk Radar Chart
        $aspek_group = [];
        foreach ($detail_items as $item) {
            $aspek_group[$item['aspek']][] = (float)$item['skor_persen'];

            // Klasifikasi Kelemahan vs Kekuatan
            // Skor <= 50% adalah kelemahan signifikan, 75% adalah cukup/perlu pemantauan, >= 75% adalah kekuatan
            if ((float)$item['skor_persen'] <= 50.0) {
                $kelemahan_items[] = [
                    'indikator' => $item['indikator'],
                    'aspek' => $item['aspek'],
                    'pilihan' => $item['pilihan'],
                    'skor' => (float)$item['skor_persen'],
                    'deskripsi' => $item['deskripsi'],
                    'catatan' => $item['catatan_khusus'],
                    'tingkat' => ((float)$item['skor_persen'] <= 25.0) ? 'Kritis' : 'Perlu Perbaikan'
                ];
            } elseif ((float)$item['skor_persen'] == 75.0) {
                // Kelemahan minor / ruang pengembangan jika ada
                if (count($kelemahan_items) < 3) {
                    $kelemahan_items[] = [
                        'indikator' => $item['indikator'],
                        'aspek' => $item['aspek'],
                        'pilihan' => $item['pilihan'],
                        'skor' => (float)$item['skor_persen'],
                        'deskripsi' => $item['deskripsi'],
                        'catatan' => $item['catatan_khusus'],
                        'tingkat' => 'Pengembangan'
                    ];
                }
            } else {
                $kekuatan_items[] = [
                    'indikator' => $item['indikator'],
                    'aspek' => $item['aspek'],
                    'pilihan' => $item['pilihan'],
                    'skor' => (float)$item['skor_persen'],
                    'deskripsi' => $item['deskripsi'],
                    'catatan' => $item['catatan_khusus']
                ];
            }
        }

        // Hitung rata-rata per aspek
        foreach ($aspek_group as $aspek => $scores) {
            $aspek_scores[$aspek] = round(array_sum($scores) / count($scores), 1);
        }
    }
}

// Rekomendasi pembinaan otomatis berdasarkan indikator lemah
function getRekomendasiOtomatis($indikator) {
    if (stripos($indikator, 'Modul Ajar') !== false || stripos($indikator, 'RPP') !== false) {
        return 'Pendampingan penyusunan Modul Ajar berdiferensiasi bersama Guru Penggerak / Kurikulum.';
    } elseif (stripos($indikator, 'Asesmen') !== false) {
        return 'Pelatihan penyusunan asesmen formatif interaktif (Quizizz, lembar refleksi mandiri) agar pemahaman murid terpantau berkala.';
    } elseif (stripos($indikator, 'Deep Learning') !== false || stripos($indikator, 'Materi') !== false) {
        return 'Fasilitasi studi kasus kontekstual dan pertanyaan pemantik HOTS untuk memperdalam pemahaman konsep siswa.';
    } elseif (stripos($indikator, 'Media') !== false || stripos($indikator, 'Teknologi') !== false) {
        return 'Optimalisasi pemanfaatan Chromebook, proyektor, dan platform digital PMM dalam kegiatan interaktif kelas.';
    } elseif (stripos($indikator, 'Keaktifan') !== false || stripos($indikator, 'Keterlibatan') !== false) {
        return 'Penerapan metode pembelajaran kelompok kooperatif (Jigsaw, Numbered Heads Together, Gallery Walk).';
    } elseif (stripos($indikator, 'Manajemen') !== false || stripos($indikator, 'Waktu') !== false) {
        return 'Penyusunan alokasi waktu kegiatan pembuka, inti, dan penutup yang lebih terstruktur dengan timer visual.';
    } else {
        return 'Supervisi klinis lanjutan dalam 2 pekan ke depan untuk memantau implementasi perbaikan.';
    }
}
?>

<link rel="stylesheet" href="../../assets/css/kepsek.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../../assets/js/kepsek.js"></script>

<div class="kepsek-container">
    <!-- Notifikasi berhasil simpan jika baru redirect -->
    <?php if ($is_saved): ?>
        <div style="background: #dcfce7; border-left: 4px solid #10b981; color: #166534; padding: 16px 20px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <strong>✨ Rubrik Penilaian Berhasil Disimpan!</strong>
                <div style="font-size: 13px; margin-top: 2px;">Berikut adalah hasil pemetaan grafik kompetensi dan diagnostik bagian kelemahan guru yang diobservasi.</div>
            </div>
            <a href="cetak.php?id=<?= $penilaian_id; ?>" target="_blank" class="btn-kepsek-action" style="padding: 8px 16px; font-size: 13px;">
                🖨️ Cetak Rapor Observasi
            </a>
        </div>
    <?php endif; ?>

    <!-- Header & Dropdown Pemilih Observasi -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="font-size: 22px; color: #1e293b; font-weight: 700; margin-bottom: 4px;">
                📊 Analisis Grafik & Diagnostik Kelemahan Guru
            </h2>
            <p style="font-size: 13px; color: #64748b;">
                Visualisasi komprehensif performa pedagogis guru dan deteksi area yang memerlukan pendampingan kepala sekolah.
            </p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <?php if (!empty($semua_supervisi)): ?>
            <form method="GET" action="" style="display: flex; gap: 8px; align-items: center;">
                <label for="pilihSupervisi" style="font-size: 13px; font-weight: 600; color: #475569;">Pilih Observasi:</label>
                <select name="id" id="pilihSupervisi" onchange="this.form.submit()" style="padding: 9px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: white; font-weight: 600; color: #1e293b;">
                    <?php foreach ($semua_supervisi as $spItem): ?>
                        <option value="<?= $spItem['id']; ?>" <?= ($spItem['id'] == $penilaian_id) ? 'selected' : ''; ?>>
                            <?= date('d/m/Y', strtotime($spItem['tanggal_observasi'])); ?> - <?= htmlspecialchars($spItem['nama_guru']); ?> (<?= htmlspecialchars($spItem['mata_pelajaran']); ?>) - <?= number_format((float)$spItem['skor_akhir'], 1); ?>%
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>

            <a href="penilaian.php" class="btn-kepsek-action" style="background: #0284c7; color: white;">
                <span>➕</span> Nilai Guru Lain
            </a>
            <?php if ($penilaian): ?>
            <a href="cetak.php?id=<?= $penilaian['id']; ?>" target="_blank" class="btn-kepsek-action" style="background: #f1f5f9; color: #334155; box-shadow: none;">
                <span>🖨️</span> Cetak
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$penilaian): ?>
        <div class="kepsek-card" style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 45px; margin-bottom: 12px;">📋</div>
            <h3 style="color: #1e293b; font-size: 18px; margin-bottom: 8px;">Belum Ada Sesi Supervisi yang Dipilih</h3>
            <p style="color: #64748b; font-size: 14px; max-width: 500px; margin: 0 auto 20px auto;">
                Silakan isi rubrik penilaian observasi guru terlebih dahulu untuk menghasilkan visualisasi grafik dan analisis kelemahan.
            </p>
            <a href="penilaian.php" class="btn-kepsek-action">Mulai Observasi Guru Baru</a>
        </div>
    <?php else: 
        $badgeClass = match ($penilaian['predikat']) {
            'Sangat Baik' => 'predikat-sangat-baik',
            'Baik' => 'predikat-baik',
            'Cukup' => 'predikat-cukup',
            default => 'predikat-kurang',
        };
    ?>

        <!-- Kartu Profil Observasi Guru -->
        <div class="kepsek-card" style="border-left: 6px solid #0284c7;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <span style="font-size: 12px; font-weight: 700; color: #0284c7; text-transform: uppercase; letter-spacing: 0.5px;">Profil Hasil Observasi</span>
                    <h2 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 4px 0;">
                        <?= htmlspecialchars($penilaian['nama_guru']); ?>
                    </h2>
                    <div style="font-size: 13px; color: #64748b; display: flex; gap: 15px; flex-wrap: wrap;">
                        <span><strong>NIP:</strong> <?= htmlspecialchars($penilaian['nip']); ?></span>
                        <span>•</span>
                        <span><strong>Mata Pelajaran:</strong> <?= htmlspecialchars($penilaian['mata_pelajaran']); ?></span>
                        <span>•</span>
                        <span><strong>Kelas:</strong> <?= htmlspecialchars($penilaian['kelas']); ?></span>
                        <span>•</span>
                        <span><strong>Tanggal:</strong> <?= date('d F Y', strtotime($penilaian['tanggal_observasi'])); ?></span>
                        <span>•</span>
                        <span><strong>Semester:</strong> <?= htmlspecialchars($penilaian['semester'] . ' ' . $penilaian['tahun_ajaran']); ?></span>
                    </div>
                </div>

                <div style="text-align: right; background: #f8fafc; padding: 12px 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 12px; color: #64748b; font-weight: 600;">Skor Capaian Akhir</div>
                    <div style="font-size: 28px; font-weight: 800; color: #0284c7; line-height: 1.1;">
                        <?= number_format((float)$penilaian['skor_akhir'], 1); ?>%
                    </div>
                    <div style="margin-top: 4px;">
                        <span class="predikat-badge <?= $badgeClass; ?>">
                            Predikat: <?= htmlspecialchars($penilaian['predikat']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2 GRAFIK UTAMA: Radar Chart & Bar Chart Indikator -->
        <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 24px; margin-bottom: 25px;">
            <!-- Grafik Radar: Keseimbangan Aspek Kompetensi -->
            <div class="kepsek-card">
                <div class="kepsek-card-title">
                    <h3>🎯 Radar Keseimbangan Aspek Kompetensi</h3>
                    <span style="font-size: 12px; color: #64748b; font-weight: 600;">Skor per Dimensi</span>
                </div>
                <div class="chart-container-box">
                    <canvas id="radarGuruChart"></canvas>
                </div>
                <p style="font-size: 12px; color: #64748b; text-align: center; margin-top: 8px;">
                    Cekukan ke arah dalam pada grafik jaring laba-laba menunjukkan aspek di mana guru mengalami penurunan performa.
                </p>
            </div>

            <!-- Grafik Bar: Skor Seluruh Indikator Rinci -->
            <div class="kepsek-card">
                <div class="kepsek-card-title">
                    <h3>📊 Capaian Setiap Indikator Pembelajaran</h3>
                    <div style="display: flex; gap: 8px; font-size: 11px;">
                        <span style="color: #ef4444; font-weight: 700;">● &lt;50% Kritis</span>
                        <span style="color: #f59e0b; font-weight: 700;">● &lt;75% Lemah</span>
                        <span style="color: #10b981; font-weight: 700;">● ≥75% Baik</span>
                    </div>
                </div>
                <div class="chart-container-box" style="height: 380px;">
                    <canvas id="barGuruChart"></canvas>
                </div>
            </div>
        </div>

        <!-- SECTION UTAMA: ANALISIS KELEMAHAN GURU (WEAKNESS DIAGNOSTICS) -->
        <div class="diagnostic-grid">
            <!-- Box Kelemahan Guru (Merah / Warning) -->
            <div class="diagnostic-card weakness">
                <h4>
                    <span>⚠️</span>
                    <span>Analisis Kelemahan Guru (Bagian yang Perlu Ditingkatkan)</span>
                </h4>
                <p style="font-size: 13px; color: #7f1d1d; margin-bottom: 14px; line-height: 1.45;">
                    Berdasarkan isian rubrik penilaian observasi, berikut adalah <strong>indikator-indikator di mana guru tersebut masih lemah</strong> beserta panduan rekomendasi pembinaan kepala sekolah:
                </p>

                <?php if (empty($kelemahan_items)): ?>
                    <div style="background: white; border-radius: 10px; padding: 20px; text-align: center; color: #166534;">
                        <div style="font-size: 28px; margin-bottom: 6px;">🎉</div>
                        <strong>Tidak Ada Kelemahan Signifikan Ditemukan!</strong>
                        <p style="font-size: 12px; color: #4b5563; margin-top: 4px;">Seluruh indikator pembelajaran dinilai dengan predikat Sangat Baik (100%).</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($kelemahan_items as $k): 
                        $badgeBg = ($k['skor'] <= 25) ? '#fee2e2' : '#fef3c7';
                        $badgeColor = ($k['skor'] <= 25) ? '#b91c1c' : '#b45309';
                    ?>
                        <div class="diagnostic-item">
                            <div class="title">
                                <span><?= htmlspecialchars($k['indikator']); ?></span>
                                <span style="background: <?= $badgeBg; ?>; color: <?= $badgeColor; ?>; font-size: 11px; padding: 2px 8px; border-radius: 6px; font-weight: 800;">
                                    Poin <?= $k['pilihan']; ?> (<?= $k['skor']; ?>%)
                                </span>
                            </div>
                            <div class="desc">
                                <strong>Dimensi:</strong> <?= htmlspecialchars($k['aspek']); ?><br>
                                <?= htmlspecialchars($k['deskripsi']); ?>
                            </div>
                            <?php if (!empty($k['catatan'])): ?>
                                <div style="font-size: 12px; color: #b91c1c; margin-top: 6px; font-style: italic;">
                                    📝 Catatan saat observasi: "<?= htmlspecialchars($k['catatan']); ?>"
                                </div>
                            <?php endif; ?>
                            <div class="recommendation">
                                <strong>💡 Rekomendasi Supervisi Klinis:</strong> <?= getRekomendasiOtomatis($k['indikator']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($penilaian['catatan_kelemahan'])): ?>
                    <div style="margin-top: 15px; padding: 12px; background: #fff5f5; border-radius: 10px; border: 1px solid #fed7d7;">
                        <strong style="font-size: 13px; color: #9b2c2c;">Catatan Khusus Kepala Sekolah:</strong>
                        <p style="font-size: 13px; color: #742a2a; margin-top: 4px; line-height: 1.4;">
                            <?= nl2br(htmlspecialchars($penilaian['catatan_kelemahan'])); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Box Kekuatan Guru (Hijau / Strength) -->
            <div class="diagnostic-card strength">
                <h4>
                    <span>⭐</span>
                    <span>Kekuatan & Keunggulan Guru</span>
                </h4>
                <p style="font-size: 13px; color: #14532d; margin-bottom: 14px; line-height: 1.45;">
                    Aspek-aspek pembelajaran yang telah dikuasai dengan sangat baik dan dapat dijadikan best-practice di sekolah:
                </p>

                <?php if (empty($kekuatan_items)): ?>
                    <div style="background: white; border-radius: 10px; padding: 20px; text-align: center; color: #9a3412;">
                        <p style="font-size: 13px;">Belum ada indikator yang mencapai predikat Sangat Baik (100%). Perlu bimbingan menyeluruh.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($kekuatan_items as $s): ?>
                        <div class="diagnostic-item">
                            <div class="title">
                                <span><?= htmlspecialchars($s['indikator']); ?></span>
                                <span style="background: #dcfce7; color: #15803d; font-size: 11px; padding: 2px 8px; border-radius: 6px; font-weight: 800;">
                                    Poin <?= $s['pilihan']; ?> (<?= $s['skor']; ?>%)
                                </span>
                            </div>
                            <div class="desc">
                                <strong>Dimensi:</strong> <?= htmlspecialchars($s['aspek']); ?><br>
                                <?= htmlspecialchars($s['deskripsi']); ?>
                            </div>
                            <?php if (!empty($s['catatan'])): ?>
                                <div style="font-size: 12px; color: #15803d; margin-top: 6px; font-style: italic;">
                                    📝 Catatan saat observasi: "<?= htmlspecialchars($s['catatan']); ?>"
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($penilaian['catatan_kelebihan'])): ?>
                    <div style="margin-top: 15px; padding: 12px; background: #f0fdf4; border-radius: 10px; border: 1px solid #bbf7d0;">
                        <strong style="font-size: 13px; color: #166534;">Apresiasi Kepala Sekolah:</strong>
                        <p style="font-size: 13px; color: #14532d; margin-top: 4px; line-height: 1.4;">
                            <?= nl2br(htmlspecialchars($penilaian['catatan_kelebihan'])); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($penilaian['rekomendasi_tindak_lanjut'])): ?>
                    <div style="margin-top: 15px; padding: 12px; background: #f0f9ff; border-radius: 10px; border: 1px solid #bae6fd;">
                        <strong style="font-size: 13px; color: #0369a1;">🎯 Rencana Tindak Lanjut Kepala Sekolah:</strong>
                        <p style="font-size: 13px; color: #075985; margin-top: 4px; line-height: 1.4;">
                            <?= nl2br(htmlspecialchars($penilaian['rekomendasi_tindak_lanjut'])); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabel Rincian Seluruh Indikator Rubrik -->
        <div class="kepsek-card">
            <div class="kepsek-card-title">
                <h3>📋 Tabel Evaluasi Rubrik Lengkap</h3>
                <a href="cetak.php?id=<?= $penilaian['id']; ?>" target="_blank" class="btn-kepsek-action" style="padding: 6px 14px; font-size: 12px; background: #0284c7; color: white;">
                    🖨️ Cetak Rapor Resmi
                </a>
            </div>

            <div style="overflow-x: auto;">
                <table class="table-kepsek">
                    <thead>
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th>Aspek & Indikator Penilaian</th>
                            <th style="text-align: center; width: 120px;">Pilihan Rubrik</th>
                            <th style="text-align: center; width: 120px;">Capaian (%)</th>
                            <th>Status & Catatan Pengamatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail_items as $d): 
                            $pct = (float)$d['skor_persen'];
                            $badgeColor = '#10b981';
                            $statusTxt = 'Sangat Baik';
                            if ($pct <= 25) { $badgeColor = '#ef4444'; $statusTxt = 'Sangat Lemah / Kritis'; }
                            elseif ($pct <= 50) { $badgeColor = '#f97316'; $statusTxt = 'Lemah / Perlu Bimbingan'; }
                            elseif ($pct <= 75) { $badgeColor = '#0284c7'; $statusTxt = 'Baik'; }
                        ?>
                        <tr>
                            <td><?= $d['urutan']; ?></td>
                            <td>
                                <strong style="color: #1e293b;"><?= htmlspecialchars($d['indikator']); ?></strong>
                                <div style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($d['aspek']); ?></div>
                            </td>
                            <td style="text-align: center;">
                                <span style="display: inline-block; width: 34px; height: 34px; line-height: 34px; border-radius: 50%; background: #f1f5f9; font-weight: 800; color: #1e293b; font-size: 15px;">
                                    <?= htmlspecialchars($d['pilihan']); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span style="font-size: 16px; font-weight: 800; color: <?= $badgeColor; ?>;">
                                    <?= number_format($pct, 1); ?>%
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 12px; font-weight: 700; color: <?= $badgeColor; ?>;">
                                    <?= $statusTxt; ?>
                                </span>
                                <?php if (!empty($d['catatan_khusus'])): ?>
                                    <div style="font-size: 12px; color: #475569; margin-top: 3px;">
                                        <em>"<?= htmlspecialchars($d['catatan_khusus']); ?>"</em>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php if ($penilaian && !empty($aspek_scores)): 
    // Persiapkan data JSON untuk Chart.js
    $radarLabels = array_keys($aspek_scores);
    $radarData = array_values($aspek_scores);

    $barLabels = [];
    $barData = [];
    foreach ($detail_items as $item) {
        // Singkat nama indikator agar rapi di sumbu Y chart
        $shortIndikator = (strlen($item['indikator']) > 45) ? substr($item['indikator'], 0, 42) . '...' : $item['indikator'];
        $barLabels[] = $shortIndikator;
        $barData[] = (float)$item['skor_persen'];
    }
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Render Radar Chart
    renderRadarKompetensi(
        'radarGuruChart',
        <?= json_encode($radarLabels); ?>,
        <?= json_encode($radarData); ?>
    );

    // Render Bar Chart
    renderBarIndikator(
        'barGuruChart',
        <?= json_encode($barLabels); ?>,
        <?= json_encode($barData); ?>
    );
});
</script>
<?php endif; ?>

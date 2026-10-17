<?php
// aplikasi/kepsek/index.php
require_once '../includes/header-kaih.php';
require_once '../../config/database.php';

// Pastikan hanya role kepsek yang dapat mengakses
if (($_SESSION['role'] ?? '') !== 'kepsek') {
    header('Location: ../../login.php');
    exit;
}

$kepsek_id = $_SESSION['user_id'] ?? 0;

// 1. Data Statistik Utama
$total_guru = (int)$pdo->query("SELECT COUNT(*) FROM guru WHERE jabatan NOT LIKE '%Kepala Sekolah%'")->fetchColumn();
$total_supervisi = (int)$pdo->query("SELECT COUNT(*) FROM supervisi_penilaian")->fetchColumn();

// Rata-rata skor seluruh guru
$rata_skor = $pdo->query("SELECT AVG(skor_akhir) FROM supervisi_penilaian")->fetchColumn();
$rata_skor_fmt = $rata_skor ? number_format((float)$rata_skor, 1) : '0.0';

// Guru yang perlu pembinaan (skor < 70)
$perlu_bimbingan = (int)$pdo->query("SELECT COUNT(*) FROM supervisi_penilaian WHERE skor_akhir < 70")->fetchColumn();

// 2. Daftar Observasi & Supervisi Terakhir (Limit 5)
$stmtTerbaru = $pdo->query("
    SELECT sp.*, g.nama_guru, g.nip 
    FROM supervisi_penilaian sp
    JOIN guru g ON sp.guru_id = g.id
    ORDER BY sp.tanggal_observasi DESC, sp.id DESC
    LIMIT 5
");
$supervisi_terbaru = $stmtTerbaru->fetchAll(PDO::FETCH_ASSOC);

// 3. Agregat Rata-rata Aspek Sekolah untuk Grafik Radar Overview
$stmtAspek = $pdo->query("
    SELECT r.aspek, AVG(spd.skor_persen) as rata_aspek
    FROM supervisi_penilaian_detail spd
    JOIN supervisi_rubrik r ON spd.rubrik_id = r.id
    GROUP BY r.aspek
    ORDER BY MIN(r.urutan) ASC
");
$data_aspek = $stmtAspek->fetchAll(PDO::FETCH_ASSOC);

$labelsAspek = [];
$scoresAspek = [];
foreach ($data_aspek as $row) {
    $labelsAspek[] = $row['aspek'];
    $scoresAspek[] = round((float)$row['rata_aspek'], 1);
}
?>

<link rel="stylesheet" href="../../assets/css/kepsek.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../../assets/js/kepsek.js"></script>

<div class="kepsek-container">
    <!-- Banner Sambutan Kepala Sekolah -->
    <div class="kepsek-hero-card">
        <div>
            <h2>Selamat Datang di Portal Supervisi Kepala Sekolah</h2>
            <p>
                Sistem Evaluasi Kinerja dan Supervisi Akademik SMP Negeri 28 Balikpapan. Pantau mutu pembelajaran guru, lakukan observasi kelas dengan rubrik terstandar, dan diagnosis area kompetensi yang memerlukan pendampingan klinis.
            </p>
        </div>
        <div class="kepsek-hero-actions">
            <a href="penilaian.php" class="btn-kepsek-action">
                <span>📝</span> Mulai Penilaian Guru Baru
            </a>
            <a href="supervisi.php" class="btn-kepsek-action btn-kepsek-secondary">
                <span>📋</span> Rekap Supervisi
            </a>
        </div>
    </div>

    <!-- Statistik Utama -->
    <div class="kepsek-stats-grid">
        <div class="kepsek-stat-card card-guru-total">
            <div class="stat-icon-wrapper">👨‍🏫</div>
            <div class="stat-info">
                <div class="stat-value"><?= $total_guru; ?></div>
                <div class="stat-label">Total Tenaga Pendidik</div>
            </div>
        </div>

        <div class="kepsek-stat-card card-supervisi-selesai">
            <div class="stat-icon-wrapper">✅</div>
            <div class="stat-info">
                <div class="stat-value"><?= $total_supervisi; ?></div>
                <div class="stat-label">Observasi Dilakukan</div>
            </div>
        </div>

        <div class="kepsek-stat-card card-skor-rata">
            <div class="stat-icon-wrapper">📊</div>
            <div class="stat-info">
                <div class="stat-value"><?= $rata_skor_fmt; ?>%</div>
                <div class="stat-label">Rata-rata Kinerja Guru</div>
            </div>
        </div>

        <div class="kepsek-stat-card card-perlu-bimbingan">
            <div class="stat-icon-wrapper">💡</div>
            <div class="stat-info">
                <div class="stat-value"><?= $perlu_bimbingan; ?></div>
                <div class="stat-label">Perlu Pendampingan Klinis</div>
            </div>
        </div>
    </div>

    <!-- Grid Grafik & Aktivitas Supervisi Terkini -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 25px;">
        <!-- Grafik Radar Mutu Sekolah -->
        <div class="kepsek-card">
            <div class="kepsek-card-title">
                <h3>📈 Radar Capaian Kompetensi Guru</h3>
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">Agregat Sekolah</span>
            </div>
            
            <?php if (!empty($labelsAspek)): ?>
                <div class="chart-container-box">
                    <canvas id="radarSekolahChart"></canvas>
                </div>
                <p style="font-size: 12px; color: #64748b; text-align: center; margin-top: 10px;">
                    Grafik menunjukkan keseimbangan kompetensi guru pada tiap aspek pedagogis & profesional.
                </p>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; color: #94a3b8;">
                    <div style="font-size: 40px; margin-bottom: 10px;">📊</div>
                    <p style="font-weight: 600;">Belum ada data supervisi yang tersimpan.</p>
                    <p style="font-size: 13px; margin-top: 5px;">Mulai isi rubrik penilaian guru untuk melihat visualisasi grafik.</p>
                    <a href="penilaian.php" class="btn-kepsek-action" style="margin-top: 15px;">Mulai Observasi Sekarang</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Panduan & Rubrik Observasi -->
        <div class="kepsek-card">
            <div class="kepsek-card-title">
                <h3>🎯 Skema Poin Rubrik Observasi</h3>
                <span class="predikat-badge predikat-baik">Standar Kurikulum Merdeka</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #f0fdf4; border-radius: 10px; border-left: 4px solid #10b981;">
                    <div>
                        <strong style="color: #15803d; font-size: 14px;">Poin A - 100% (Sangat Baik)</strong>
                        <div style="font-size: 12px; color: #166534;">Memenuhi seluruh indikator secara konsisten, menginspirasi murid & rekan.</div>
                    </div>
                    <span class="predikat-badge predikat-sangat-baik">100%</span>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #f0f9ff; border-radius: 10px; border-left: 4px solid #0284c7;">
                    <div>
                        <strong style="color: #0369a1; font-size: 14px;">Poin B - 75% (Baik)</strong>
                        <div style="font-size: 12px; color: #075985;">Memenuhi sebagian besar kriteria pembelajaran dengan efektif dan terstruktur.</div>
                    </div>
                    <span class="predikat-badge predikat-baik">75%</span>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #fffbeb; border-radius: 10px; border-left: 4px solid #f59e0b;">
                    <div>
                        <strong style="color: #b45309; font-size: 14px;">Poin C - 50% (Cukup)</strong>
                        <div style="font-size: 12px; color: #78350f;">Kriteria terpenuhi sebagian, memerlukan penguatan pada penerapan di kelas.</div>
                    </div>
                    <span class="predikat-badge predikat-cukup">50%</span>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #fff7ed; border-radius: 10px; border-left: 4px solid #f97316;">
                    <div>
                        <strong style="color: #c2410c; font-size: 14px;">Poin D - 25% (Kurang)</strong>
                        <div style="font-size: 12px; color: #9a3412;">Sebagian besar indikator belum terlihat, perlu pendampingan supervisi klinis.</div>
                    </div>
                    <span class="predikat-badge predikat-kurang">25%</span>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #fef2f2; border-radius: 10px; border-left: 4px solid #ef4444;">
                    <div>
                        <strong style="color: #b91c1c; font-size: 14px;">Poin E - 0% (Sangat Kurang)</strong>
                        <div style="font-size: 12px; color: #7f1d1d;">Indikator tidak tampak sama sekali dalam pelaksanaan proses belajar mengajar.</div>
                    </div>
                    <span class="predikat-badge predikat-kurang">0%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat Supervisi Terakhir -->
    <div class="kepsek-card">
        <div class="kepsek-card-title">
            <h3>📑 Hasil Observasi & Supervisi Terkini</h3>
            <a href="supervisi.php" style="color: #0284c7; text-decoration: none; font-size: 13px; font-weight: 700;">Lihat Semua Data →</a>
        </div>

        <div style="overflow-x: auto;">
            <table class="table-kepsek">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Guru</th>
                        <th>Kelas / Mapel</th>
                        <th>Semester</th>
                        <th>Skor Akhir</th>
                        <th>Predikat</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($supervisi_terbaru)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 35px;">
                            Belum ada sesi observasi guru. Silakan tekan tombol <strong>Mulai Penilaian Guru Baru</strong> untuk melakukan evaluasi.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($supervisi_terbaru as $sp): 
                            $badgeClass = match ($sp['predikat']) {
                                'Sangat Baik' => 'predikat-sangat-baik',
                                'Baik' => 'predikat-baik',
                                'Cukup' => 'predikat-cukup',
                                default => 'predikat-kurang',
                            };
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($sp['tanggal_observasi'])); ?></td>
                            <td>
                                <strong><?= htmlspecialchars($sp['nama_guru']); ?></strong>
                                <div style="font-size: 12px; color: #64748b;">NIP. <?= htmlspecialchars($sp['nip']); ?></div>
                            </td>
                            <td>
                                <span style="font-weight: 600;"><?= htmlspecialchars($sp['mata_pelajaran']); ?></span>
                                <div style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($sp['kelas']); ?></div>
                            </td>
                            <td><?= htmlspecialchars($sp['semester'] . ' ' . $sp['tahun_ajaran']); ?></td>
                            <td>
                                <span style="font-size: 16px; font-weight: 800; color: #0369a1;">
                                    <?= number_format((float)$sp['skor_akhir'], 1); ?>%
                                </span>
                            </td>
                            <td>
                                <span class="predikat-badge <?= $badgeClass; ?>">
                                    <?= htmlspecialchars($sp['predikat']); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <a href="analisis.php?id=<?= $sp['id']; ?>" class="btn-kepsek-action" style="padding: 6px 14px; font-size: 12px; background: #e0f2fe; color: #0284c7; box-shadow: none;">
                                    📊 Lihat Grafik & Analisis
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($labelsAspek)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    renderRadarKompetensi(
        'radarSekolahChart', 
        <?= json_encode($labelsAspek); ?>, 
        <?= json_encode($scoresAspek); ?>
    );
});
</script>
<?php endif; ?>

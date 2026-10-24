<?php
// aplikasi/kepsek/cetak.php
session_start();
require_once '../../config/database.php';

// Pastikan user adalah kepsek atau admin
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['kepsek', 'admin'], true)) {
    header('Location: ../../login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID observasi tidak valid.");
}

// Ambil data sesi supervisi
$stmt = $pdo->prepare("
    SELECT sp.*, g.nama_guru, g.nip, g.kelas as kelas_guru, g.jabatan, g.alamat, g.no_hp
    FROM supervisi_penilaian sp
    JOIN guru g ON sp.guru_id = g.id
    WHERE sp.id = ?
");
$stmt->execute([$id]);
$penilaian = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$penilaian) {
    die("Data supervisi tidak ditemukan.");
}

// Ambil detail rubrik
$stmtDetail = $pdo->prepare("
    SELECT spd.*, r.aspek, r.indikator, r.deskripsi, r.urutan
    FROM supervisi_penilaian_detail spd
    JOIN supervisi_rubrik r ON spd.rubrik_id = r.id
    WHERE spd.penilaian_id = ?
    ORDER BY r.urutan ASC, r.id ASC
");
$stmtDetail->execute([$id]);
$detail_items = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

function tglIndo($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instrumen Observasi Supervisi Guru - <?= htmlspecialchars($penilaian['nama_guru']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Times New Roman', Times, serif;
        }

        body {
            background: #f1f5f9;
            color: #111827;
            padding: 20px;
        }

        .paper {
            background: white;
            max-width: 820px;
            margin: 0 auto;
            padding: 40px 45px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* Kop Surat Resmi */
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 3px double #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .kop-logo {
            width: 75px;
            height: 75px;
            object-fit: contain;
            margin-right: 20px;
        }

        .kop-teks {
            text-align: center;
            flex: 1;
        }

        .kop-teks h3 {
            font-size: 14px;
            font-weight: normal;
            letter-spacing: 0.5px;
        }

        .kop-teks h2 {
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .kop-teks h1 {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .kop-teks p {
            font-size: 11px;
            margin-top: 4px;
            color: #333;
        }

        .judul-dokumen {
            text-align: center;
            margin-bottom: 22px;
        }

        .judul-dokumen h3 {
            font-size: 16px;
            text-decoration: underline;
            text-transform: uppercase;
            font-weight: bold;
        }

        .judul-dokumen p {
            font-size: 13px;
            margin-top: 3px;
        }

        /* Identitas Grid */
        .identitas-grid {
            width: 100%;
            margin-bottom: 20px;
            font-size: 13px;
            border-collapse: collapse;
        }

        .identitas-grid td {
            padding: 4px 6px;
            vertical-align: top;
        }

        /* Tabel Penilaian */
        .tabel-rubrik {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .tabel-rubrik th, .tabel-rubrik td {
            border: 1px solid #333;
            padding: 6px 8px;
        }

        .tabel-rubrik th {
            background: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Box Rekomendasi & Catatan */
        .box-catatan {
            border: 1px solid #444;
            padding: 10px 14px;
            margin-bottom: 15px;
            font-size: 12px;
            background: #fafafa;
        }

        .box-catatan h4 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        /* Tanda Tangan */
        .ttd-container {
            display: flex;
            justify-content: space-between;
            margin-top: 35px;
            font-size: 13px;
        }

        .ttd-box {
            text-align: center;
            width: 250px;
        }

        .ttd-space {
            height: 70px;
        }

        .no-print-bar {
            max-width: 820px;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-print {
            background: #0284c7;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .paper {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <a href="analisis.php?id=<?= $penilaian['id']; ?>" style="color: #475569; text-decoration: none; font-size: 14px; font-weight: 600;">
            ← Kembali ke Analisis Grafik
        </a>
        <button onclick="window.print()" class="btn-print">
            🖨️ Cetak Dokumen / Simpan PDF
        </button>
    </div>

    <div class="paper">
        <!-- KOP SURAT RESMI -->
        <div class="kop-surat">
            <img src="../../assets/img/logo-sekolah1.jpeg" alt="Logo SMPN 28" class="kop-logo">
            <div class="kop-teks">
                <h3>PEMERINTAH KOTA BALIKPAPAN</h3>
                <h2>DINAS PENDIDIKAN DAN KEBUDAYAAN</h2>
                <h1>SMP NEGERI 28 BALIKPAPAN</h1>
                <p>Jl. Manggar Baru, Balikpapan Timur, Kota Balikpapan, Kalimantan Timur | NPSN: 69978713</p>
            </div>
        </div>

        <div class="judul-dokumen">
            <h3>INSTRUMEN SUPERVISI AKADEMIK OBSERVASI PEMBELAJARAN</h3>
            <p>Tahun Ajaran <?= htmlspecialchars($penilaian['tahun_ajaran']); ?> - Semester <?= htmlspecialchars($penilaian['semester']); ?></p>
        </div>

        <!-- IDENTITAS PELAKSANAAN OBSERVASI -->
        <table class="identitas-grid">
            <tr>
                <td style="width: 25%;"><strong>Nama Guru</strong></td>
                <td style="width: 2%;">:</td>
                <td style="width: 40%;"><?= htmlspecialchars($penilaian['nama_guru']); ?></td>
                <td style="width: 15%;"><strong>Hari / Tanggal</strong></td>
                <td style="width: 2%;">:</td>
                <td style="width: 20%;"><?= tglIndo($penilaian['tanggal_observasi']); ?></td>
            </tr>
            <tr>
                <td><strong>NIP</strong></td>
                <td>:</td>
                <td><?= htmlspecialchars($penilaian['nip']); ?></td>
                <td><strong>Kelas</strong></td>
                <td>:</td>
                <td><?= htmlspecialchars($penilaian['kelas']); ?></td>
            </tr>
            <tr>
                <td><strong>Mata Pelajaran</strong></td>
                <td>:</td>
                <td><?= htmlspecialchars($penilaian['mata_pelajaran']); ?></td>
                <td><strong>Nama Supervisor</strong></td>
                <td>:</td>
                <td>Aris Broto, S.Pd (Kepala Sekolah)</td>
            </tr>
        </table>

        <!-- TABEL RINCIAN INDIKATOR RUBRIK -->
        <table class="tabel-rubrik">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Aspek & Indikator Observasi</th>
                    <th style="width: 60px;">Pilihan</th>
                    <th style="width: 70px;">Skor (%)</th>
                    <th style="width: 180px;">Catatan Pengamatan Supervisor</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $currentAspek = '';
                foreach ($detail_items as $d): 
                    if ($d['aspek'] !== $currentAspek):
                        $currentAspek = $d['aspek'];
                ?>
                    <tr style="background: #f8fafc; font-weight: bold;">
                        <td colspan="5" style="padding: 5px 8px; color: #0f172a;">
                            DIMENSI: <?= htmlspecialchars($currentAspek); ?>
                        </td>
                    </tr>
                <?php endif; ?>
                    <tr>
                        <td class="text-center"><?= $d['urutan']; ?></td>
                        <td>
                            <strong><?= htmlspecialchars($d['indikator']); ?></strong>
                            <div style="font-size: 11px; color: #555;"><?= htmlspecialchars($d['deskripsi']); ?></div>
                        </td>
                        <td class="text-center" style="font-weight: bold; font-size: 13px;">
                            Poin <?= htmlspecialchars($d['pilihan']); ?>
                        </td>
                        <td class="text-center" style="font-weight: bold;">
                            <?= number_format((float)$d['skor_persen'], 1); ?>%
                        </td>
                        <td>
                            <?= htmlspecialchars($d['catatan_khusus'] ?: '-'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr style="background: #f1f5f9; font-weight: bold; font-size: 13px;">
                    <td colspan="3" class="text-right">SKOR RATA-RATA AKHIR OBSERVASI:</td>
                    <td class="text-center" style="color: #0369a1; font-size: 14px;">
                        <?= number_format((float)$penilaian['skor_akhir'], 1); ?>%
                    </td>
                    <td style="color: #15803d;">
                        PREDIKAT: <?= strtoupper(htmlspecialchars($penilaian['predikat'])); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- CATATAN KELEMAHAN & KEKUATAN -->
        <div class="box-catatan">
            <h4>1. Kekuatan Guru yang Tampak:</h4>
            <p><?= nl2br(htmlspecialchars($penilaian['catatan_kelebihan'] ?: 'Guru menguasai materi pembelajaran dengan baik dan menjalin komunikasi yang positif dengan murid.')); ?></p>
        </div>

        <div class="box-catatan" style="background: #fff8f8; border-color: #e57373;">
            <h4 style="color: #c62828;">2. Analisis Kelemahan / Bagian yang Perlu Ditingkatkan:</h4>
            <p><?= nl2br(htmlspecialchars($penilaian['catatan_kelemahan'] ?: 'Perlu penguatan pada pemanfaatan asesmen formatif dan penerapan media interaktif digital.')); ?></p>
        </div>

        <div class="box-catatan" style="background: #f0f7ff; border-color: #90caf9;">
            <h4 style="color: #1565c0;">3. Rekomendasi & Tindak Lanjut Kepala Sekolah:</h4>
            <p><?= nl2br(htmlspecialchars($penilaian['rekomendasi_tindak_lanjut'] ?: 'Supervisi klinis lanjutan dan bimbingan teknis komunitas belajar sekolah.')); ?></p>
        </div>

        <!-- TANDA TANGAN -->
        <div class="ttd-container">
            <div class="ttd-box">
                <p>Mengetahui,</p>
                <p>Kepala SMP Negeri 28 Balikpapan</p>
                <div class="ttd-space"></div>
                <p style="text-decoration: underline; font-weight: bold;">Aris Broto, S.Pd</p>
                <p>NIP. 198301172009031005</p>
            </div>

            <div class="ttd-box">
                <p>Balikpapan, <?= tglIndo($penilaian['tanggal_observasi']); ?></p>
                <p>Guru yang Diobservasi</p>
                <div class="ttd-space"></div>
                <p style="text-decoration: underline; font-weight: bold;"><?= htmlspecialchars($penilaian['nama_guru']); ?></p>
                <p>NIP. <?= htmlspecialchars($penilaian['nip']); ?></p>
            </div>
        </div>
    </div>

</body>
</html>

<?php
// Panggil koneksi PDO
require_once '../../config/pdo.php'; 

// 1. TANGKAP PARAMETER DENGAN AMAN
$tab =$_GET['tab'] ?? 'validasi';
$guru_id =$_GET['guru_id'] ?? '';
$kelas_id =$_GET['kelas_id'] ?? '';
$tanggal =$_GET['tanggal'] ?? date('Y-m-d');
$semester =$_GET['semester'] ?? 'Ganjil';
$isSubmitted = isset($_GET['tampilkan']); // Cek apakah tombol Tampilkan diklik

// 2. AMBIL DATA GURU DARI DATABASE
try {
    $stmtGuru =$pdo->query("SELECT * FROM guru ORDER BY nama_guru ASC");
    $listGuru =$stmtGuru->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {$listGuru = [];
}

// 3. AMBIL DATA KELAS DARI DATABASE
try {
    $stmtKelas =$pdo->query("SELECT * FROM kaih_kelas ORDER BY nama_kelas ASC");
    $listKelas =$stmtKelas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
        $stmtKelas =$pdo->query("SELECT * FROM tb_kelas ORDER BY nama_kelas ASC");
        $listKelas =$stmtKelas->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {$listKelas = [];
    }
}

// 4. AMBIL DATA SISWA & LAPORAN HANYA JIKA TOMBOL TAMPILKAN DIKLIK
$listLaporan = [];
if ($isSubmitted) {
    try {
        if (!empty($kelas_id)) {
            $stmtSiswa =$pdo->prepare("SELECT * FROM siswa WHERE id_kelas = ? ORDER BY nama ASC");
            $stmtSiswa->execute([$kelas_id]);
            $listLaporan =$stmtSiswa->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmtSiswa =$pdo->query("SELECT * FROM siswa ORDER BY nama ASC LIMIT 50");
            $listLaporan =$stmtSiswa->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {$listLaporan = [];
    }
}

// Memanggil Header & Sidebar Admin
include '../includes/header-kaih.php'; 
?>

<style>
    /* CSS Modern menyesuaikan UI baru */
    .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
    .page-subtitle { font-size: 14px; color: #6b7280; margin-bottom: 24px; }
    
    .card { background: #ffffff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
    
    .tab-container { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px; }
    .tab-btn { 
        padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 14px; 
        text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;
    }
    .tab-btn.active { background-color: #2563eb; color: #ffffff; }
    .tab-btn:not(.active) { background-color: transparent; color: #6b7280; border: 1px solid transparent; }
    .tab-btn:not(.active):hover { background-color: #f3f4f6; color: #374151; }

    .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end; }
    .form-group label { display: block; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
    .form-control { 
        width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; 
        font-size: 14px; outline: none; transition: border-color 0.2s; 
    }
    .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    
    .btn-primary { 
        background-color: #2563eb; color: white; border: none; padding: 10px 20px; 
        border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; 
    }
    .btn-primary:hover { background-color: #1d4ed8; }

    .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; font-size: 14px; }

    /* Styling Tabel Modern */
    .table-responsive { width: 100%; overflow-x: auto; }
    .data-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
    .data-table th { background-color: #f9fafb; color: #374151; font-weight: 600; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; }
    .data-table td { padding: 12px 16px; border-bottom: 1px solid #f3f4f6; color: #4b5563; vertical-align: middle; }
    .badge-status { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; }
    .badge-belum { background-color: #fee2e2; color: #991b1b; }
    .badge-sudah { background-color: #d1fae5; color: #065f46; }
</style>

<div class="content-wrapper" style="padding: 24px;">
    
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
        <div>
            <h1 class="page-title">Laporan Guru</h1>
            <p class="page-subtitle">Validasi kegiatan harian & rekap semester per kelas / guru</p>
        </div>
    </div>

    <div class="tab-container">
        <a href="?tab=validasi&guru_id=<?= $guru_id ?>&kelas_id=<?= $kelas_id ?>&tanggal=<?= $tanggal ?><?= $isSubmitted ? '&tampilkan=1' : '' ?>" class="tab-btn <?= $tab === 'validasi' ? 'active' : '' ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Validasi Guru
        </a>
        <a href="?tab=rekap&guru_id=<?= $guru_id ?>&kelas_id=<?= $kelas_id ?>&tanggal=<?= $tanggal ?><?= $isSubmitted ? '&tampilkan=1' : '' ?>" class="tab-btn <?= $tab === 'rekap' ? 'active' : '' ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Rekap & Cetak
        </a>
    </div>

    <!-- FILTER CARD -->
    <div class="card">
        <form method="GET" action="">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            
            <div class="filter-grid">
                <!-- DROPDOWN GURU (Tanpa onchange submit otomatis) -->
                <div class="form-group">
                    <label>Pilih Guru / Wali Kelas</label>
                    <select name="guru_id" class="form-control">
                        <option value="">-- Pilih Guru --</option>
                        <option value="semua" <?= $guru_id === 'semua' ? 'selected' : '' ?>>Semua Guru</option>
                        <?php foreach($listGuru as$g): 
                            $idG =$g['id_guru'] ?? $g['id'] ?? '';$namaG = $g['nama_guru'] ?? $g['nama'] ?? '';
                        ?>
                            <option value="<?= $idG ?>" <?= $guru_id ==$idG ? 'selected' : '' ?>>
                                <?= htmlspecialchars($namaG) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- DROPDOWN KELAS -->
                <div class="form-group">
                    <label>Kelas</label>
                    <select name="kelas_id" class="form-control">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach($listKelas as$k): 
                            $idK =$k['id_kelas'] ?? $k['id'] ?? '';$namaK = $k['nama_kelas'] ?? $k['kelas'] ?? '';
                        ?>
                            <option value="<?= $idK ?>" <?= $kelas_id ==$idK ? 'selected' : '' ?>>
                                <?= htmlspecialchars($namaK) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($tanggal) ?>">
                </div>

                <div class="form-group" style="padding-bottom: 2px;">
                    <!-- Tombol Tampilkan dengan name="tampilkan" -->
                    <button type="submit" name="tampilkan" value="1" class="btn-primary">Tampilkan</button>
                </div>
            </div>
        </form>
    </div>

    <!-- CONTENT DATA CARD -->
    <div class="card">
        <?php if (!$isSubmitted): ?>
            <div class="empty-state">
                <p style="font-weight: 600; color: #374151; font-size: 16px; margin-bottom: 8px;">Pilih Guru atau Kelas</p>
                <p>Gunakan filter di atas untuk memilih guru atau kelas, lalu klik Tampilkan.</p>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 16px; font-weight: 600; color: #374151;">
                Menampilkan Data Laporan tanggal: <span style="color: #2563eb;"><?= htmlspecialchars($tanggal) ?></span>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Siswa</th>
                            <th>NIS / NISN</th>
                            <th>Status Laporan</th>
                            <th>Validasi Ortu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($listLaporan)): ?>
                            <?php $no = 1; foreach($listLaporan as$row): 
                                $namaSiswa =$row['nama'] ?? $row['nama_siswa'] ?? 'Tanpa Nama';$nisSiswa = $row['nis'] ?? $row['nisn'] ?? '-';
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td style="font-weight: 600; color: #1f2937;"><?= htmlspecialchars($namaSiswa) ?></td>
                                    <td><?= htmlspecialchars($nisSiswa) ?></td>
                                    <td><span class="badge-status badge-belum">Belum</span></td>
                                    <td><span style="color: #6b7280; font-style: italic;">Pending</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #6b7280; padding: 20px;">Tidak ada data siswa ditemukan untuk filter ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>
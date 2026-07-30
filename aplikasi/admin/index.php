<?php
// aplikasi/admin/index.php
session_start();
require_once '../../config/database.php';

// Cek hak akses admin
if (!isset($_SESSION['user_id']) && (string)($_SESSION['portal_role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// --- Query untuk mengambil total data ---
// Catatan: Sesuaikan nama tabel (siswa, guru, kaih_kelas) jika di databasemu berbeda

// 1. Hitung Total Siswa
$stmtSiswa = $pdo->query("SELECT COUNT(*) FROM siswa");
$total_siswa = $stmtSiswa->fetchColumn();

// 2. Hitung Total Guru 
$stmtGuru = $pdo->query("SELECT COUNT(*) FROM guru");
$total_guru = $stmtGuru->fetchColumn();

// 3. Hitung Total Kelas 
$stmtKelas = $pdo->query("SELECT COUNT(*) FROM kaih_kelas");
$total_kelas = $stmtKelas->fetchColumn();

// 4. Hitung Total Laporan (Sementara diset 0, sesuaikan querynya nanti kalau tabel laporan sudah siap)
// $stmtLaporan = $pdo->query("SELECT COUNT(*) FROM laporan");
// $total_laporan = $stmtLaporan->fetchColumn();
$total_laporan = 0; 

// Memuat Header UI
require_once '../includes/header-kaih.php';
?>

<!-- Styling Khusus Dashboard -->
<style>
    /* Ubah warna background halaman agar card putih lebih menonjol */
    body {
        background-color: #f8fafc; 
    }

    .dashboard-container {
        padding: 24px;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Grid Layout untuk Card Statistik */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr); /* Kuncinya di sini: Paksa jadi 4 kolom menyamping */
        gap: 20px;
        margin-bottom: 30px;
        width: 100%; /* Memaksa grid memenuhi lebar layar */
    }

    /* Tambahan agar tetap responsif dan tidak gepeng kalau dibuka di layar kecil/HP */
    @media (max-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr); /* Berubah jadi 2 kolom di layar sedang */
        }
    }

    @media (max-width: 640px) {
        .stats-grid {
            grid-template-columns: 1fr; /* Berubah jadi numpuk ke bawah hanya di layar HP */
        }
    }

    /* Desain Card Utama */
    .stat-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        display: flex;
        align-items: center;
        border-left: 5px solid; /* Garis aksen warna tetap dipertahankan di kiri */
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    /* Warna Aksen Tiap Card */
    .card-siswa { border-left-color: #0ea5e9; }
    .card-guru { border-left-color: #10b981; }
    .card-kelas { border-left-color: #f59e0b; }
    .card-laporan { border-left-color: #8b5cf6; }

    /* Desain Lingkaran Icon */
    .stat-icon {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-right: 16px;
        flex-shrink: 0;
    }

    .card-siswa .stat-icon { background: #e0f2fe; color: #0ea5e9; }
    .card-guru .stat-icon { background: #d1fae5; color: #10b981; }
    .card-kelas .stat-icon { background: #fef3c7; color: #f59e0b; }
    .card-laporan .stat-icon { background: #ede9fe; color: #8b5cf6; }

    /* Desain Teks Angka & Label */
    .stat-details h3 {
        margin: 0;
        font-size: 32px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
    }

    .stat-details p {
        margin: 6px 0 0 0;
        font-size: 14px;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Box Section Aktivitas Terbaru */
    .activity-section {
        background: #ffffff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .activity-header {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 12px;
    }

    .activity-empty {
        color: #94a3b8;
        font-style: italic;
        text-align: center;
        padding: 40px 0;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px dashed #cbd5e1;
    }
</style>

<div class="dashboard-container">
    
    <!-- Bagian Kartu Statistik -->
    <div class="stats-grid">
        
        <!-- Card Total Siswa -->
        <div class="stat-card card-siswa">
            <div class="stat-icon">🎓</div>
            <div class="stat-details">
                <h3><?php echo htmlspecialchars($total_siswa); ?></h3>
                <p>Total Siswa</p>
            </div>
        </div>

        <!-- Card Total Guru -->
        <div class="stat-card card-guru">
            <div class="stat-icon">👨‍🏫</div>
            <div class="stat-details">
                <h3><?php echo htmlspecialchars($total_guru); ?></h3>
                <p>Total Guru</p>
            </div>
        </div>

        <!-- Card Total Kelas -->
        <div class="stat-card card-kelas">
            <div class="stat-icon">🏫</div>
            <div class="stat-details">
                <h3><?php echo htmlspecialchars($total_kelas); ?></h3>
                <p>Total Kelas</p>
            </div>
        </div>

        <!-- Card Total Laporan -->
        <div class="stat-card card-laporan">
            <div class="stat-icon">📑</div>
            <div class="stat-details">
                <h3><?php echo htmlspecialchars($total_laporan); ?></h3>
                <p>Total Laporan</p>
            </div>
        </div>

    </div>

    <!-- Bagian Aktivitas Terbaru -->
    <div class="activity-section">
        <div class="activity-header">
            📋 <span>Aktivitas Terbaru</span>
        </div>
        
        <div class="activity-empty">
            Belum ada aktivitas terbaru hari ini.
        </div>
    </div>

</div>
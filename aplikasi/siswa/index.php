<?php
// aplikasi/siswa/index.php
require_once '../includes/header-kaih.php';
require_once '../../config/database.php';

// Ambil statistik dari database
$siswa_id = $_SESSION['siswa_id'] ?? 0;
$total_kaih = 0;
$tervalidasi = 0;
$menunggu = 0;
$total_refleksi = 0;

if ($siswa_id > 0) {
    try {
        // Total KAIH
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM laporan_harian WHERE siswa_id = ?");
        $stmt->execute([$siswa_id]);
        $total_kaih = $stmt->fetchColumn();
        
        // Tervalidasi
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM laporan_harian WHERE siswa_id = ? AND (orang_tua_validated_at IS NOT NULL OR guru_validated_at IS NOT NULL)");
        $stmt->execute([$siswa_id]);
        $tervalidasi = $stmt->fetchColumn();
        
        // Menunggu validasi
        $menunggu = $total_kaih - $tervalidasi;
        
        // Total Refleksi
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM refleksi WHERE siswa_id = ?");
        $stmt->execute([$siswa_id]);
        $total_refleksi = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Jika tabel belum ada, abaikan
    }
}
?>

<style>
    /* ============================================================
       DASHBOARD SISWA - RESPONSIF
       ============================================================ */
    
    .dashboard-wrapper {
        max-width: 850px;
        margin: 0 auto;
        padding: 0 12px;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-top: 10px;
    }

    .dashboard-card {
        background: white;
        border-radius: 16px;
        padding: 30px 20px 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        text-align: center;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        cursor: pointer;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-height: 180px;
        width: 100%;
        position: relative;
        overflow: hidden;
    }

    .dashboard-card::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .dashboard-card:hover::after {
        opacity: 1;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.08);
    }

    .dashboard-card .card-icon {
        font-size: 36px;
        margin-bottom: 8px;
        display: block;
    }

    .dashboard-card h3 {
        color: #1e293b;
        font-size: 18px;
        margin-bottom: 4px;
        font-weight: 700;
    }

    .dashboard-card p {
        color: #64748b;
        font-size: 13px;
        margin: 0;
        line-height: 1.4;
    }

    .dashboard-card .btn-action {
        margin-top: 14px;
        padding: 6px 22px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-block;
        border: none;
        cursor: pointer;
    }

    .dashboard-card .btn-action:hover {
        transform: scale(1.05);
    }

    .badge-count {
        background: #f1f5f9;
        color: #475569;
        padding: 2px 14px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        margin-top: 10px;
        display: inline-block;
    }

    /* ============================================================
       WARNA CARD
       ============================================================ */

    /* Absensi - Biru */
    .card-absensi {
        border-color: #e0f2fe;
    }
    .card-absensi::after {
        background: linear-gradient(90deg, #0284c7, #38bdf8);
    }
    .card-absensi:hover {
        border-color: #0284c7;
    }
    .card-absensi .btn-action {
        background: #0284c7;
        color: white;
    }
    .card-absensi .btn-action:hover {
        background: #0369a1;
    }

    /* KAIH - Hijau */
    .card-kaih {
        border-color: #d1fae5;
    }
    .card-kaih::after {
        background: linear-gradient(90deg, #10b981, #34d399);
    }
    .card-kaih:hover {
        border-color: #10b981;
    }
    .card-kaih .btn-action {
        background: #10b981;
        color: white;
    }
    .card-kaih .btn-action:hover {
        background: #059669;
    }

    /* Refleksi - Ungu */
    .card-refleksi {
        border-color: #ede9fe;
        grid-column: span 1;
    }
    .card-refleksi::after {
        background: linear-gradient(90deg, #8b5cf6, #a78bfa);
    }
    .card-refleksi:hover {
        border-color: #8b5cf6;
    }
    .card-refleksi .btn-action {
        background: #8b5cf6;
        color: white;
    }
    .card-refleksi .btn-action:hover {
        background: #7c3aed;
    }

    /* ============================================================
       RESPONSIF
       ============================================================ */

    @media (max-width: 768px) {
        .dashboard-wrapper {
            padding: 0 8px;
        }

        .dashboard-grid {
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .dashboard-card {
            padding: 22px 16px 20px;
            min-height: 150px;
        }

        .dashboard-card .card-icon {
            font-size: 30px;
        }

        .dashboard-card h3 {
            font-size: 16px;
        }

        .dashboard-card p {
            font-size: 12px;
        }

        .dashboard-card .btn-action {
            padding: 5px 16px;
            font-size: 11px;
            margin-top: 12px;
        }

        /* Refleksi tetap full width di tablet */
        .card-refleksi {
            grid-column: span 2;
        }

        .badge-count {
            font-size: 11px;
            padding: 2px 12px;
            margin-top: 8px;
        }
    }

    @media (max-width: 480px) {
        .dashboard-wrapper {
            padding: 0 4px;
        }

        .dashboard-grid {
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 6px;
        }

        .dashboard-card {
            padding: 16px 10px 14px;
            min-height: 120px;
            border-radius: 12px;
        }

        .dashboard-card .card-icon {
            font-size: 26px;
            margin-bottom: 4px;
        }

        .dashboard-card h3 {
            font-size: 14px;
            margin-bottom: 2px;
        }

        .dashboard-card p {
            font-size: 11px;
        }

        .dashboard-card .btn-action {
            padding: 4px 12px;
            font-size: 10px;
            margin-top: 8px;
            border-radius: 14px;
        }

        /* Refleksi full width di HP */
        .card-refleksi {
            grid-column: span 2;
        }

        .badge-count {
            font-size: 10px;
            padding: 1px 10px;
            margin-top: 6px;
        }
    }

    @media (max-width: 380px) {
        .dashboard-grid {
            gap: 8px;
        }

        .dashboard-card {
            padding: 12px 8px 10px;
            min-height: 100px;
            border-radius: 10px;
        }

        .dashboard-card .card-icon {
            font-size: 22px;
        }

        .dashboard-card h3 {
            font-size: 13px;
        }

        .dashboard-card p {
            font-size: 10px;
        }

        .dashboard-card .btn-action {
            padding: 3px 10px;
            font-size: 9px;
            margin-top: 6px;
        }

        .badge-count {
            font-size: 9px;
            padding: 1px 8px;
            margin-top: 4px;
        }
    }
</style>

<div class="dashboard-wrapper">

    <div class="dashboard-grid">
        
        <!-- ============================================================
             CARD ABSENSI
             ============================================================ -->
        <a href="absensi.php" class="dashboard-card card-absensi">
            <span class="card-icon">📋</span>
            <h3>Absensi</h3>
            <p>Catat kehadiran hari ini</p>
            <span class="btn-action">Isi Absensi</span>
        </a>

        <!-- ============================================================
             CARD KAIH
             ============================================================ -->
        <a href="kaih.php" class="dashboard-card card-kaih">
            <span class="card-icon">🌟</span>
            <h3>KAIH</h3>
            <p>7 Kebiasaan Anak Indonesia Hebat</p>
            <span class="btn-action">Isi Formulir</span>
        </a>

        <!-- ============================================================
             CARD REFLEKSI
             ============================================================ -->
        <a href="refleksi.php" class="dashboard-card card-refleksi">
            <span class="card-icon">📝</span>
            <h3>Refleksi</h3>
            <p>Tulis pembelajaran &amp; pengalamanmu</p>
            <span class="btn-action">Tulis Refleksi</span>
            <span class="badge-count">📊 <?php echo $total_refleksi; ?> refleksi</span>
        </a>

    </div>

</div>

<?php
?>
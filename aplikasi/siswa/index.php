<?php
// aplikasi/siswa/index.php
require_once '../includes/header-kaih.php';

// Ambil statistik dari database
$siswa_id = $_SESSION['siswa_id'] ?? 0;
$total_kaih = 0;
$tervalidasi = 0;
$menunggu = 0;

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
    } catch (PDOException $e) {
        // Jika tabel belum ada, abaikan
    }
}
?>

<!-- Tambahkan CSS untuk responsive -->
<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        margin-top: 10px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        padding: 0 10px;
    }

    .dashboard-grid .card-absensi {
        order: 1;
    }

    .dashboard-grid .card-kaih {
        order: 2;
    }

    .dashboard-card {
        background: white;
        border-radius: 16px;
        padding: 30px 25px;           /* Kurangi padding */
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        text-align: center;
        transition: all 0.3s;
        border: 2px solid transparent;
        cursor: pointer;
        min-height: 200px;            /* Kurangi tinggi minimum */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-decoration: none;
        max-width: 500px;             /* Batasi lebar maksimum */
        margin: 0 auto;               /* Center card */
        width: 100%;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .dashboard-card h3 {
        color: #1e293b;
        font-size: 20px;
        margin-bottom: 8px;
        font-weight: 700;
    }

    .dashboard-card p {
        color: #64748b;
        font-size: 14px;
        margin: 0;
    }

    .dashboard-card .btn-absensi {
        margin-top: 15px;
        background: #0284c7;
        color: white;
        padding: 6px 20px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .dashboard-card .btn-absensi:hover {
        background: #0369a1;
        transform: scale(1.05);
    }

    .dashboard-card .btn-kaih {
        margin-top: 15px;
        background: #10b981;
        color: white;
        padding: 6px 20px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .dashboard-card .btn-kaih:hover {
        background: #059669;
        transform: scale(1.05);
    }

    /* Mode Mobile - Ubah menjadi 1 kolom dengan Absensi di atas */
    @media (max-width: 768px) {
        .dashboard-grid {
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding: 0 15px;
        }

        .dashboard-grid .card-absensi {
            order: 1;
        }

        .dashboard-grid .card-kaih {
            order: 2;
        }

        .dashboard-card {
            padding: 25px 20px;
            min-height: 150px;
        }

        .dashboard-card h3 {
            font-size: 18px;
        }
    }

    @media (max-width: 480px) {
        .dashboard-grid {
            gap: 12px;
            padding: 0 8px;
        }

        .dashboard-card {
            padding: 20px 15px;
            min-height: 130px;
        }

        .dashboard-card h3 {
            font-size: 16px;
        }

        .dashboard-card p {
            font-size: 13px;
        }

        .dashboard-card .btn-absensi,
        .dashboard-card .btn-kaih {
            padding: 5px 16px;
            font-size: 11px;
        }
    }
</style>

<div class="dashboard-grid">
    
    <!-- Card Absensi - Akan di atas di mode mobile -->
    <a href="absensi.php" class="dashboard-card card-absensi">
        <h3>Absensi</h3>
        <p>Catat kehadiran hari ini</p>
        <div class="btn-absensi">
            Isi Absensi
        </div>
    </a>

    <!-- Card KAIH - Akan di bawah di mode mobile -->
    <a href="kaih.php" class="dashboard-card card-kaih">
        <h3>KAIH</h3>
        <p>Catat 7 Kebiasaan Anak Indonesia Hebat</p>
        <div class="btn-kaih">
            Isi Formulir
        </div>
    </a>

</div>

<?php
?>
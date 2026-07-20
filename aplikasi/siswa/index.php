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

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 10px;">
    
    <!-- Card Absensi -->
    <a href="absensi.php" style="text-decoration: none; display: block;">
        <div style="background: white; border-radius: 16px; padding: 35px 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; transition: all 0.3s; border: 2px solid transparent; cursor: pointer; min-height: 200px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <h3 style="color: #1e293b; font-size: 20px; margin-bottom: 8px; font-weight: 700;">Absensi</h3>
            <p style="color: #64748b; font-size: 14px; margin: 0;">Catat kehadiran hari ini</p>
            <div style="margin-top: 15px; background: #0284c7; color: white; padding: 6px 20px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                Klik untuk absen
            </div>
        </div>
    </a>

    <!-- Card KAIH -->
    <a href="kaih.php" style="text-decoration: none; display: block;">
        <div style="background: white; border-radius: 16px; padding: 35px 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; transition: all 0.3s; border: 2px solid transparent; cursor: pointer; min-height: 200px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <h3 style="color: #1e293b; font-size: 20px; margin-bottom: 8px; font-weight: 700;">KAIH</h3>
            <p style="color: #64748b; font-size: 14px; margin: 0;">Catat 7 Kebiasaan Anak Indonesia Hebat</p>
            <div style="margin-top: 15px; background: #10b981; color: white; padding: 6px 20px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                Isi formulir
            </div>
        </div>
    </a>

</div>

<?php
?>
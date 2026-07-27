<?php
// aplikasi/admin/index.php
session_start();
require_once '../../config/database.php'; // Hubungkan ke database

// Cek hak akses admin
if (!isset($_SESSION['user_id']) && (string)($_SESSION['portal_role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Inisialisasi variabel nilai awal
$total_siswa = 0;
$total_guru = 0;
$total_kelas = 0;
$total_laporan = 0; // Fitur laporan belum ada, biarkan 0

try {
    // Hitung total Siswa
    $stmtSiswa = $pdo->query("SELECT COUNT(*) FROM siswa");
    $total_siswa = $stmtSiswa->fetchColumn();

    // Hitung total Guru
    $stmtGuru = $pdo->query("SELECT COUNT(*) FROM guru");
    $total_guru = $stmtGuru->fetchColumn();

    // Hitung total Kelas
    $stmtKelas = $pdo->query("SELECT COUNT(*) FROM kaih_kelas");
    $total_kelas = $stmtKelas->fetchColumn();
    
} catch (Exception $e) {
    // Jika terjadi error (misal tabel belum ada), biarkan tetap 0
}

// Panggil Header
require_once '../includes/header-kaih.php';
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px;">
    <div class="card" style="text-align: center; border-top: 4px solid #0284c7;">
        <div style="font-size: 32px; font-weight: 800; color: #0284c7;"><?php echo (int)$total_siswa; ?></div>
        <div style="color: #64748b; font-size: 14px;">Total Siswa</div>
    </div>
    <div class="card" style="text-align: center; border-top: 4px solid #10b981;">
        <div style="font-size: 32px; font-weight: 800; color: #10b981;"><?php echo (int)$total_guru; ?></div>
        <div style="color: #64748b; font-size: 14px;">Total Guru</div>
    </div>
    <div class="card" style="text-align: center; border-top: 4px solid #f59e0b;">
        <div style="font-size: 32px; font-weight: 800; color: #f59e0b;"><?php echo (int)$total_kelas; ?></div>
        <div style="color: #64748b; font-size: 14px;">Total Kelas</div>
    </div>
    <div class="card" style="text-align: center; border-top: 4px solid #6366f1;">
        <div style="font-size: 32px; font-weight: 800; color: #6366f1;"><?php echo (int)$total_laporan; ?></div>
        <div style="color: #64748b; font-size: 14px;">Total Laporan</div>
    </div>
</div>

<div class="card">
    <h3>📋 Aktivitas Terbaru</h3>
    <p style="color: #64748b;">Belum ada aktivitas terbaru.</p>
</div>

<?php
// Penutup file
?>
<?php
// Mulai session dengan aman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil nama file saat ini untuk menandai menu aktif
$current_page = basename($_SERVER['PHP_SELF']);

// ==============================================================
// LOGIKA PENENTUAN ROLE SUPER AMAN
// Deteksi dari semua kemungkinan session yang diset saat login
// ==============================================================
$role_aktif = 'admin'; // Default

if (!empty($_SESSION['portal_role'])) {
    $role_aktif = strtolower($_SESSION['portal_role']);
} elseif (!empty($_SESSION['role'])) {
    $role_aktif = strtolower($_SESSION['role']);
} elseif (!empty($_SESSION['guru_id'])) {
    $role_aktif = 'guru';
} elseif (!empty($_SESSION['username']) && strpos(strtoupper($_SESSION['username']), 'ORT') === 0) {
    // Orang tua punya username diawali ORT
    $role_aktif = 'orang_tua';
} elseif (!empty($_SESSION['siswa_id'])) {
    $role_aktif = 'siswa';
}

if ($role_aktif == 'orang tua') $role_aktif = 'orang_tua';

// Penyesuaian Judul Dashboard & Sidebar berdasarkan Role
$judul_sidebar = "Admin KAIH";
$judul_header = "Admin Dashboard";

if ($role_aktif == 'guru') {
    $judul_sidebar = "Guru KAIH";
    $judul_header = "Portal Guru";
} elseif ($role_aktif == 'orang_tua') {
    $judul_sidebar = "Wali Murid";
    $judul_header = "Portal Orang Tua";
} elseif ($role_aktif == 'siswa') {
    $judul_sidebar = "Siswa KAIH";
    $judul_header = "Portal Siswa";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $judul_header ?> - KAIH</title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap');
        
        body { margin: 0; padding: 0; font-family: 'Nunito', sans-serif; background-color: #f8fafc; color: #334155; }
        .app-container { display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* SIDEBAR */
        .sidebar { width: 260px; background-color: #0284c7; color: white; display: flex; flex-direction: column; transition: transform 0.3s ease, margin-left 0.3s ease; flex-shrink: 0; z-index: 50; box-shadow: 4px 0 10px rgba(0,0,0,0.05); }
        .sidebar.hidden { margin-left: -260px; }
        .sidebar-brand { text-align: center; padding: 25px 15px 15px; }
        .sidebar-brand-logo { background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; display: inline-block; margin-bottom: 10px; font-weight: 800; font-size: 14px; }
        .sidebar-brand h2 { margin: 0; font-size: 18px; font-weight: 800; }
        .sidebar-brand p { margin: 4px 0 0; font-size: 11px; color: #e0f2fe; }
        
        .sidebar-profile { background-color: #0ea5e9; margin: 10px 15px 20px; padding: 15px; border-radius: 12px; text-align: center; }
        .sidebar-profile h4 { margin: 0; font-size: 16px; font-weight: 700; }
        .sidebar-profile p { margin: 2px 0 0; font-size: 12px; color: #e0f2fe; }
        
        .sidebar-menu { flex: 1; display: flex; flex-direction: column; gap: 5px; padding: 0 15px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: #e0f2fe; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 600; transition: all 0.2s; }
        .nav-link:hover { background-color: rgba(255, 255, 255, 0.1); color: white; transform: translateX(3px); }
        .nav-link.active { background-color: rgba(255, 255, 255, 0.2); color: white; font-weight: 800; }
        .nav-link.logout { margin-top: auto; margin-bottom: 20px; color: #fca5a5; }
        .nav-link.logout:hover { background-color: #ef4444; color: white; }
        
        /* MAIN CONTENT */
        .main-content { flex: 1; display: flex; flex-direction: column; min-width: 0; transition: width 0.3s ease; }
        .top-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 30px; background: transparent; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .hamburger-btn { background-color: #f1f5f9; border: none; border-radius: 8px; padding: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #475569; transition: 0.2s; }
        .hamburger-btn:hover { background-color: #e2e8f0; color: #0f172a; }
        .header-title { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; }
        .header-date { font-size: 14px; color: #64748b; font-weight: 600; }
    </style>
</head>
<body>

<div class="app-container">
    
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-logo">☁ Logo</div>
            <h2><?= $judul_sidebar ?></h2>
            <p>SMP Negeri 28 Balikpapan</p>
        </div>
        
        <div class="sidebar-profile">
            <h4><?= htmlspecialchars($_SESSION['username'] ?? 'User'); ?></h4>
            <p><?= ucwords(str_replace('_', ' ', $role_aktif)); ?></p>
            
            <!-- Link Ubah Password Universal -->
            <a href="../ganti_password.php" style="display: inline-block; margin-top: 8px; font-size: 11px; color: #bae6fd; text-decoration: none; border: 1px solid rgba(255,255,255,0.3); padding: 4px 10px; border-radius: 12px; transition: 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='transparent'">
                🔒 Ubah Password
            </a>
        </div>
        
        <div class="sidebar-menu">
            <?php if ($role_aktif == 'admin'): ?>
                <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : ''; ?>">📊 Dashboard</a>
                <a href="siswa.php" class="nav-link <?= $current_page == 'siswa.php' ? 'active' : ''; ?>">👨‍🎓 Kelola Siswa</a>
                <a href="guru.php" class="nav-link <?= $current_page == 'guru.php' ? 'active' : ''; ?>">👨‍🏫 Kelola Guru</a>
                <a href="kelas.php" class="nav-link <?= $current_page == 'kelas.php' ? 'active' : ''; ?>">🏫 Kelola Kelas</a>
                <a href="users.php" class="nav-link <?= $current_page == 'users.php' ? 'active' : ''; ?>">🔐 Kelola Akun</a>
                <a href="laporan.php" class="nav-link <?= $current_page == 'laporan.php' ? 'active' : ''; ?>">📄 Laporan Siswa</a>
                <a href="laporan-guru.php" class="nav-link <?= $current_page == 'laporan-guru.php' ? 'active' : ''; ?>">📑 Laporan Guru</a>
                
            <?php elseif ($role_aktif == 'guru'): ?>
                <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : ''; ?>">📊 Dashboard</a>
                <a href="monitoring.php" class="nav-link <?= $current_page == 'monitoring.php' ? 'active' : ''; ?>">📝 Monitoring Siswa</a>
                <a href="absensi.php" class="nav-link <?= $current_page == 'absensi.php' ? 'active' : ''; ?>">📅 Rekap Absensi</a>
                
            <?php elseif ($role_aktif == 'orang_tua'): ?>
                <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : ''; ?>">📊 Dashboard</a>
                <a href="monitoring.php" class="nav-link <?= $current_page == 'monitoring.php' ? 'active' : ''; ?>">👀 Monitoring Anak</a>
                
            <?php elseif ($role_aktif == 'siswa'): ?>
                <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : ''; ?>">📊 Dashboard</a>
                <a href="absensi.php" class="nav-link <?= $current_page == 'absensi.php' ? 'active' : ''; ?>">✋ Absensi</a>
                <a href="kaih.php" class="nav-link <?= $current_page == 'kaih.php' ? 'active' : ''; ?>">📋 Form KAIH</a>
            <?php endif; ?>
            
            <a href="../../logout.php" class="nav-link logout" onclick="return confirm('Yakin ingin keluar dari portal ini?');">🚪 Logout</a>
        </div>
    </div>
    
    <!-- MAIN CONTENT -->
    <div class="main-content" id="main-content">
        
        <!-- HEADER ATAS -->
        <div class="top-header">
            <div class="header-left">
                <!-- Tombol Garis 3 Pemicu -->
                <button id="sidebarToggle" class="hamburger-btn" title="Sembunyikan/Tampilkan Menu">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h2 class="header-title"><?= $judul_header ?></h2>
            </div>
            
            <div class="header-right" style="display: flex; align-items: center; gap: 20px;">
                
                <!-- TEMPAT LONCENG NOTIFIKASI -->
                <div class="notif-wrapper" style="position: relative; cursor: pointer; padding: 5px;" title="Notifikasi Sistem">
                    <svg width="24" height="24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <!-- Titik Merah Penanda Ada Notif -->
                    <span style="position: absolute; top: 3px; right: 5px; width: 10px; height: 10px; background: #ef4444; border-radius: 50%; border: 2px solid #f8fafc;"></span>
                </div>

                <!-- Tanggal Otomatis -->
                <div class="header-date">
                    <?= date('l, d F Y'); ?>
                </div>
            </div>
        </div>
        
        <script>
            document.getElementById('sidebarToggle').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('hidden');
            });
        </script>
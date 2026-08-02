<?php
// Ambil nama file saat ini untuk menandai menu mana yang sedang aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KAIH</title>
    
    <style>
        /* Font modern */
        @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap');
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Nunito', sans-serif;
            background-color: #f8fafc;
            color: #334155;
        }
        
        /* Layout Utama Flexbox */
        .app-container {
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* ---------------------------------
           SIDEBAR STYLING 
        --------------------------------- */
        .sidebar {
            width: 260px;
            background-color: #0284c7; /* Warna Biru KAIH */
            color: white;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, margin-left 0.3s ease;
            flex-shrink: 0;
            z-index: 50;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
        }
        
        /* Class penting: Saat ditambahkan, sidebar geser ke kiri */
        .sidebar.hidden {
            margin-left: -260px;
        }
        
        /* Logo & Nama Sekolah */
        .sidebar-brand {
            text-align: center;
            padding: 25px 15px 15px;
        }
        .sidebar-brand-logo {
            background: rgba(255,255,255,0.2); 
            padding: 5px 15px; 
            border-radius: 20px; 
            display: inline-block; 
            margin-bottom: 10px; 
            font-weight: 800; 
            font-size: 14px;
        }
        .sidebar-brand h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
        }
        .sidebar-brand p {
            margin: 4px 0 0;
            font-size: 11px;
            color: #e0f2fe;
        }
        
        /* Profil Admin Card */
        .sidebar-profile {
            background-color: #0ea5e9;
            margin: 10px 15px 20px;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }
        .sidebar-profile h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }
        .sidebar-profile p {
            margin: 2px 0 0;
            font-size: 12px;
            color: #e0f2fe;
        }
        
        /* Navigasi Menu */
        .sidebar-menu {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 0 15px;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #e0f2fe;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(3px);
        }
        /* Menu aktif (Sesuai halaman saat ini) */
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 800;
        }
        .nav-link.logout {
            margin-top: auto;
            margin-bottom: 20px;
            color: #fca5a5;
        }
        .nav-link.logout:hover {
            background-color: #ef4444;
            color: white;
        }
        
        /* ---------------------------------
           MAIN CONTENT & HEADER 
        --------------------------------- */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0; /* Penting agar tabel di dalam tidak meluap */
            transition: width 0.3s ease;
        }
        
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            background: transparent;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        /* Tombol Hamburger 3 Garis */
        .hamburger-btn {
            background-color: #f1f5f9;
            border: none;
            border-radius: 8px;
            padding: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            transition: 0.2s;
        }
        .hamburger-btn:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }
        .header-title {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            color: #1e293b;
        }
        .header-date {
            font-size: 14px;
            color: #64748b;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="app-container">
    
    <!-- AREA KIRI: SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-logo">☁ Logo</div>
            <h2>Admin KAIH</h2>
            <p>SMP Negeri 28 Balikpapan</p>
        </div>
        
        <div class="sidebar-profile">
            <!-- Ambil session role jika ada, kalau tidak fallback tulisan default -->
            <h4><?php echo htmlspecialchars($_SESSION['username'] ?? 'admin'); ?></h4>
            <p><?php echo ucfirst(htmlspecialchars($_SESSION['portal_role'] ?? 'Admin')); ?></p>
        </div>
        
        <div class="sidebar-menu">
            <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                📊 Dashboard
            </a>
            <a href="siswa.php" class="nav-link <?php echo $current_page == 'siswa.php' ? 'active' : ''; ?>">
                👨‍🎓 Kelola Siswa
            </a>
            <a href="guru.php" class="nav-link <?php echo $current_page == 'guru.php' ? 'active' : ''; ?>">
                👨‍🏫 Kelola Guru
            </a>
            <a href="kelas.php" class="nav-link <?php echo $current_page == 'kelas.php' ? 'active' : ''; ?>">
                🏫 Kelola Kelas
            </a>
            <a href="users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                🔐 Kelola Akun
            </a>
            <a href="laporan.php" class="nav-link <?php echo $current_page == 'laporan.php' ? 'active' : ''; ?>">
                📄 Laporan Siswa
            </a>
            <a href="laporan-guru.php" class="nav-link <?php echo $current_page == 'laporan-guru.php' ? 'active' : ''; ?>">
                📑 Laporan Guru
            </a>
            
            <a href="../../logout.php" class="nav-link logout" onclick="return confirm('Yakin ingin keluar dari portal Admin?');">
                🚪 Logout
            </a>
        </div>
    </div>
    
    <!-- AREA KANAN: KONTEN UTAMA -->
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
                <h2 class="header-title">Admin Dashboard</h2>
            </div>
            
            <!-- Tanggal Otomatis (Mengikuti gambar) -->
            <div class="header-date">
                <?php echo date('l, d F Y'); ?>
            </div>
        </div>
        
        <!-- SCRIPT JS UNTUK TOMBOL GARIS 3 -->
        <script>
            document.getElementById('sidebarToggle').addEventListener('click', function() {
                // Menambahkan/menghapus class 'hidden' dari sidebar
                document.getElementById('sidebar').classList.toggle('hidden');
            });
        </script>
        
        <!-- 
        ========================================================================
        Konten halaman (tabel users, kelas, siswa, dll) otomatis akan muncul 
        di bawah ini karena di-include (require) dari file-file tersebut. 
        ======================================================================== 
        -->
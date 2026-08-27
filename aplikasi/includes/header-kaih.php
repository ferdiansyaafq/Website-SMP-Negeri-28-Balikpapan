<?php
// aplikasi/includes/header-kaih.php
require_once __DIR__ . '/auth.php';

date_default_timezone_set('Asia/Jakarta');

// ============================================================
// FUNGSI TANGGAL INDONESIA (LENGKAP) - DEKLARASI DI SINI
// ============================================================
function hariIndo($day) {
    $hari = [
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];
    return $hari[$day] ?? $day;
}

function hariIndoSingkat($day) {
    $hari = [
        'Mon' => 'Sen',
        'Tue' => 'Sel',
        'Wed' => 'Rab',
        'Thu' => 'Kam',
        'Fri' => 'Jum',
        'Sat' => 'Sab',
        'Sun' => 'Min'
    ];
    return $hari[$day] ?? $day;
}

function bulanIndo($month) {
    $bulan = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Maret',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Juni',
        'July' => 'Juli',
        'August' => 'Agustus',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Desember'
    ];
    return $bulan[$month] ?? $month;
}

function bulanIndoSingkat($month) {
    $bulan = [
        'Jan' => 'Jan',
        'Feb' => 'Feb',
        'Mar' => 'Mar',
        'Apr' => 'Apr',
        'May' => 'Mei',
        'Jun' => 'Jun',
        'Jul' => 'Jul',
        'Aug' => 'Agu',
        'Sep' => 'Sep',
        'Oct' => 'Okt',
        'Nov' => 'Nov',
        'Dec' => 'Des'
    ];
    return $bulan[$month] ?? $month;
}

function tanggalIndo($date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    $timestamp = strtotime($date);
    return hariIndo(date('l', $timestamp)) . ', ' . date('d', $timestamp) . ' ' . bulanIndo(date('F', $timestamp)) . ' ' . date('Y', $timestamp);
}

function formatTanggalIndo($date) {
    $timestamp = strtotime($date);
    return hariIndo(date('l', $timestamp)) . ', ' . date('d', $timestamp) . ' ' . bulanIndo(date('F', $timestamp)) . ' ' . date('Y', $timestamp);
}

function formatTanggalIndoSingkat($date) {
    $timestamp = strtotime($date);
    return hariIndoSingkat(date('D', $timestamp)) . ' ' . date('d', $timestamp) . ' ' . bulanIndoSingkat(date('M', $timestamp)) . ' ' . date('Y', $timestamp);
}

$role = $_SESSION['role'];
$nama = $user['nama_lengkap'] ?? $_SESSION['username'];
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KAIH - <?php echo ucfirst($role); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; min-height: 100vh; }

        .sidebar {
            position: fixed; left: 0; top: 0; width: 260px; height: 100vh;
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: white; padding: 25px 20px; overflow-y: auto; z-index: 1000;
        }
        .sidebar .logo { text-align: center; margin-bottom: 25px; }
        .sidebar .logo img {
            width: 60px; height: 60px; border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.3); object-fit: cover;
        }
        .sidebar .logo h2 { font-size: 18px; margin-top: 10px; }
        .sidebar .logo p { font-size: 12px; opacity: 0.8; }

        .sidebar .user-info {
            background: rgba(255,255,255,0.1); padding: 15px; border-radius: 12px;
            margin-bottom: 20px; text-align: center;
        }
        .sidebar .user-info .name { font-weight: 700; font-size: 16px; }
        .sidebar .user-info .role { font-size: 12px; opacity: 0.8; text-transform: capitalize; }

        .sidebar .menu a {
            display: block; padding: 11px 16px; color: rgba(255,255,255,0.8);
            text-decoration: none; border-radius: 10px; transition: all 0.3s;
            margin-bottom: 3px; font-size: 14px;
        }
        .sidebar .menu a:hover, .sidebar .menu a.active {
            background: rgba(255,255,255,0.15); color: white;
        }
        .sidebar .menu a .icon { margin-right: 10px; }

        .sidebar .logout {
            margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
        }
        .sidebar .logout a { color: #fca5a5; }
        .sidebar .logout a:hover { background: rgba(252,165,165,0.15); }

        .main-content {
            margin-left: 260px; padding: 25px 35px; min-height: 100vh;
        }
        .main-content .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px;
        }
        .main-content .header h1 { font-size: 26px; color: #1e293b; }
        .main-content .header .date { color: #64748b; font-size: 14px; }

        .card {
            background: white; padding: 22px; border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px;
        }
        .card h3 { font-size: 18px; color: #1e293b; margin-bottom: 12px; }

        /* HAMBURGER BUTTON - MOBILE */
        .hamburger-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            flex-direction: column;
            gap: 5px;
        }
        .hamburger-toggle span {
            display: block;
            width: 28px;
            height: 3px;
            background: #1e293b;
            border-radius: 3px;
            transition: all 0.3s;
        }
        .hamburger-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        .hamburger-toggle.active span:nth-child(2) {
            opacity: 0;
        }
        .hamburger-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        @media (max-width: 768px) {
            .hamburger-toggle {
                display: flex;
            }
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 280px;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar-overlay.active {
                display: block;
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .main-content .header h1 {
                font-size: 20px;
            }
            .main-content .header .date {
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 10px;
            }
            .main-content .header {
                flex-wrap: wrap;
                gap: 10px;
            }
            .main-content .header h1 {
                font-size: 17px;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="<?php echo BASE_URL; ?>assets/img/logo-sekolah.png" alt="Logo Sekolah">
        <h2><?php echo ucfirst($role); ?> KAIH</h2>
        <p>SMP Negeri 28 Balikpapan</p>
    </div>

    <div class="user-info">
        <div class="name"><?php echo htmlspecialchars($nama); ?></div>
        <div class="role"><?php echo ucfirst($role); ?></div>
    </div>

    <nav class="menu">
        <?php if ($role === 'admin'): ?>
            <!-- MENU ADMIN -->
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <span class="icon">📊</span> Dashboard
            </a>
            <a href="siswa.php" class="<?php echo ($current_page == 'siswa.php') ? 'active' : ''; ?>">
                <span class="icon">👨‍🎓</span> Kelola Siswa
            </a>
            <a href="guru.php" class="<?php echo ($current_page == 'guru.php') ? 'active' : ''; ?>">
                <span class="icon">👨‍🏫</span> Kelola Guru
            </a>
            <a href="kelas.php" class="<?php echo ($current_page == 'kelas.php') ? 'active' : ''; ?>">
                <span class="icon">🏫</span> Kelola Kelas
            </a>
            <a href="laporan.php" class="<?php echo ($current_page == 'laporan.php') ? 'active' : ''; ?>">
                <span class="icon">📄</span> Laporan
            </a>
            <a href="refleksi.php" class="<?php echo ($current_page == 'refleksi.php') ? 'active' : ''; ?>">
                <span class="icon">📝</span> Refleksi
            </a>

        <?php elseif ($role === 'guru'): ?>
            <!-- MENU GURU -->
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <span class="icon">📊</span> Dashboard
            </a>
            <a href="monitoring.php" class="<?php echo ($current_page == 'monitoring.php') ? 'active' : ''; ?>">
                <span class="icon">📋</span> Monitoring Siswa
            </a>
            <a href="rekap.php" class="<?php echo ($current_page == 'rekap.php') ? 'active' : ''; ?>">
                <span class="icon">📊</span> Rekap
            </a>
            <a href="refleksi.php" class="<?php echo ($current_page == 'refleksi.php') ? 'active' : ''; ?>">
                <span class="icon">📝</span> Refleksi
            </a>

        <?php elseif ($role === 'siswa'): ?>
            <!-- MENU SISWA -->
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <span class="icon">📊</span> Dashboard
            </a>
            <a href="absensi.php" class="<?php echo ($current_page == 'absensi.php') ? 'active' : ''; ?>">
                <span class="icon">📋</span> Absensi
            </a>
            <a href="kaih.php" class="<?php echo ($current_page == 'kaih.php') ? 'active' : ''; ?>">
                <span class="icon">📝</span> Formulir KAIH
            </a>
            <a href="rekap.php" class="<?php echo ($current_page == 'rekap.php') ? 'active' : ''; ?>">
                <span class="icon">📊</span> Rekap
            </a>
            <a href="refleksi.php" class="<?php echo ($current_page == 'refleksi.php') ? 'active' : ''; ?>">
                <span class="icon">✍️</span> Refleksi
            </a>

        <?php elseif ($role === 'orang_tua'): ?>
            <!-- MENU ORANG TUA -->
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <span class="icon">📊</span> Dashboard
            </a>
            <a href="monitoring.php" class="<?php echo ($current_page == 'monitoring.php') ? 'active' : ''; ?>">
                <span class="icon">👨‍👩‍👧</span> Monitoring Anak
            </a>
            <a href="rekap.php" class="<?php echo ($current_page == 'rekap.php') ? 'active' : ''; ?>">
                <span class="icon">📊</span> Rekap
            </a>
            <a href="refleksi.php" class="<?php echo ($current_page == 'refleksi.php') ? 'active' : ''; ?>">
                <span class="icon">📝</span> Refleksi
            </a>
        <?php endif; ?>

        <div class="logout">
            <a href="../../logout.php"><span class="icon">🚪</span> Logout</a>
        </div>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <button class="hamburger-toggle" id="hamburgerToggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <h1><?php echo ucfirst($role); ?> Dashboard</h1>
        </div>
        <span class="date"><?php echo tanggalIndo(); ?></span>
    </div>

<script>
// Toggle sidebar mobile
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburgerToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (hamburger && sidebar && overlay) {
        function toggleSidebar() {
            hamburger.classList.toggle('active');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
        }

        hamburger.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
    }
});
</script>

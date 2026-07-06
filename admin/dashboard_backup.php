<?php
declare(strict_types=1);

require_once '../includes/admin_auth.php';
requireAdminLogin();
require_once '../config/database.php';

$username = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');
$conn = getConnection();
$success = '';
$error = '';

// Get counts
$totalSiswa = (int) ($conn->query("SELECT COUNT(*) FROM siswa")->fetch_row()[0] ?? 0);
$totalGuru = (int) ($conn->query("SELECT COUNT(*) FROM guru")->fetch_row()[0] ?? 0);
$totalUsers = (int) ($conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0] ?? 0);

// Get active tab
$tab = $_GET['tab'] ?? 'dashboard';
$tab = in_array($tab, ['dashboard', 'siswa', 'guru', 'user'], true) ? $tab : 'dashboard';

// Data queries
$siswaList = [];
$guruList = [];
$userList = [];

if ($tab === 'siswa' || $tab === 'dashboard') {
    $result = $conn->query("SELECT s.id, s.nisn, s.nama_siswa, s.kelas, k.nama_kelas FROM siswa s LEFT JOIN kaih_kelas k ON s.wali_kelas_id = k.id ORDER BY s.nama_siswa");
    while ($row = $result->fetch_assoc()) {
        $siswaList[] = $row;
    }
}

if ($tab === 'guru' || $tab === 'dashboard') {
    $result = $conn->query("SELECT id, nip, nama_guru, kelas, jabatan, no_hp FROM guru ORDER BY nama_guru");
    while ($row = $result->fetch_assoc()) {
        $guruList[] = $row;
    }
}

if ($tab === 'user' || $tab === 'dashboard') {
    $result = $conn->query("SELECT u.id, u.username, u.role, g.nama_guru, s.nama_siswa FROM users u LEFT JOIN guru g ON u.guru_id = g.id LEFT JOIN siswa s ON u.siswa_id = s.id ORDER BY u.username");
    while ($row = $result->fetch_assoc()) {
        $userList[] = $row;
    }
}

// Get kelas list for selects
$kelasList = [];
$result = $conn->query("SELECT id, nama_kelas FROM kaih_kelas ORDER BY nama_kelas");
while ($row = $result->fetch_assoc()) {
    $kelasList[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — KAIH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=20260316f">
</head>
<body>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-box">
                        <svg viewBox="0 0 40 40" fill="none">
                            <rect width="40" height="40" rx="10" fill="url(#logo-grad)"/>
                            <path d="M10 20L17 13L24 20L17 27L10 20Z" fill="white"/>
                            <path d="M18 20L25 13L32 20L25 27L18 20Z" fill="white" fill-opacity="0.6"/>
                            <defs>
                                <linearGradient id="logo-grad" x1="0" y1="0" x2="40" y2="40">
                                    <stop stop-color="#6366f1"/>
                                    <stop offset="1" stop-color="#8b5cf6"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <div class="logo-text">
                        <span class="logo-title">KAIH</span>
                        <span class="logo-sub">Admin Panel</span>
                    </div>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-label">Menu Utama</div>
                <a href="dashboard.php" class="nav-item active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                        <rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                        <rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                        <rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="siswa.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span>Data Siswa</span>
                </a>
                <a href="guru.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span>Data Guru</span>
                </a>
                <a href="kelas.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/>
                        <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span>Data Kelas</span>
                </a>
                <a href="users.php" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span>Akun &amp; User</span>
                </a>

                <div class="nav-section-label">Laporan</div>
                <a href="#" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
                        <polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2"/>
                        <line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2"/>
                        <line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span>Laporan Siswa</span>
                    <span class="nav-badge">Soon</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
                    <div class="user-detail">
                        <span class="user-name"><?= $username ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                </div>
                <a href="logout.php" class="btn-logout" title="Keluar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-sub">Selamat datang kembali, <strong><?= $username ?></strong></p>
                </div>
                <div class="topbar-right">
                    <div class="topbar-date">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                            <line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/>
                            <line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/>
                            <line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <?= date('d M Y') ?>
                    </div>
                    <div class="admin-chip">
                        <div class="admin-dot"></div>
                        Admin
                    </div>
                </div>
            </header>

            <div class="content-area">
                <div class="welcome-banner">
                    <div class="banner-content">
                        <h2 class="banner-title">Sistem Informasi KAIH</h2>
                        <p class="banner-text">Panel administrasi untuk mengelola data siswa, guru, kelas, dan akun. Login terakhir: <?= $loginTime ?>.</p>
                        <div class="banner-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?= number_format($totalSiswa) ?></span>
                                <span class="stat-label">Siswa</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?= number_format($totalGuru) ?></span>
                                <span class="stat-label">Guru</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?= number_format($totalKelas) ?></span>
                                <span class="stat-label">Kelas</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?= number_format($totalUsers) ?></span>
                                <span class="stat-label">Users</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon primary">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($totalSiswa) ?></h3>
                            <p>Total Siswa</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon success">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($totalGuru) ?></h3>
                            <p>Total Guru</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon warning">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/>
                                    <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($totalKelas) ?></h3>
                            <p>Total Kelas</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon danger">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($totalUsers) ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <button class="theme-toggle" id="themeToggle" title="Toggle Theme" type="button">
        <svg id="sunIcon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="2"/>
            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-width="2"/>
        </svg>
        <svg id="moonIcon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: none;">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke="currentColor" stroke-width="2"/>
        </svg>
    </button>

    <script>
        // Theme Management
        const themeToggle = document.getElementById('themeToggle');
        const sunIcon = document.getElementById('sunIcon');
        const moonIcon = document.getElementById('moonIcon');
        const html = document.documentElement;

        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-theme', savedTheme);

        function updateThemeIcon() {
            if (html.getAttribute('data-theme') === 'dark') {
                sunIcon.style.display = 'none';
                moonIcon.style.display = 'block';
            } else {
                sunIcon.style.display = 'block';
                moonIcon.style.display = 'none';
            }
        }

        updateThemeIcon();

        themeToggle.addEventListener('click', () => {
            const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon();
        });

        // Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');
        });
    </script>

 </body>
 </html>

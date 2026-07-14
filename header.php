<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('pickFirstExistingImage')) {
    function pickFirstExistingImage(array $candidates, string $fallback): string {
        foreach ($candidates as $path) {
            $fsPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            if (is_file($fsPath)) {
                $v = (int) @filemtime($fsPath);
                return $path . ($v > 0 ? ('?v=' . $v) : '');
            }
        }
        return $fallback;
    }
}

$logoSekolah = pickFirstExistingImage([
    'assets/img/logo-sekolah.png',
    'assets/img/logo.png',
], 'assets/img/logo-sekolah.svg');

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/x-icon" href="assets/img/logo-sekolah.png">
<link rel="shortcut icon" type="image/x-icon" href="assets/img/logo-sekolah.png">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); min-height: 100vh; }

.header {
    background: white;
    padding: 15px 40px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 4px solid #0284c7;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-shrink: 0;
}

.logo {
    width: 55px;
    height: 55px;
    border-radius: 50%;
}

.school-name h1 {
    font-size: 20px;
    color: #1e293b;
    margin-bottom: 3px;
    font-weight: 700;
}

.school-name p {
    font-size: 13px;
    color: #64748b;
}

.nav {
    display: flex;
    gap: 5px;
    align-items: center;
}

.nav a, .nav-item > a {
    text-decoration: none;
    color: #64748b;
    font-weight: 600;
    padding: 10px 18px;
    border-radius: 8px;
    transition: all 0.3s;
    font-size: 15px;
    white-space: nowrap;
}

.nav a:hover, .nav a.active, .nav-item > a:hover, .nav-item > a.active {
    background: #e0f2fe;
    color: #0284c7;
}

.nav-item {
    position: relative;
}

.nav-item > a {
    display: flex;
    align-items: center;
    gap: 5px;
}

.arrow {
    font-size: 10px;
    transition: transform 0.3s;
}

.nav-item:hover .arrow {
    transform: rotate(180deg);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 10px;
    background: white;
    min-width: 240px;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    padding: 10px 0;
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.3s ease;
    z-index: 1001;
    border: 1px solid #e2e8f0;
}

.nav-item:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-menu a {
    display: block;
    padding: 10px 20px;
    color: #475569;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    white-space: nowrap;
}

.dropdown-menu a:hover {
    background: #e0f2fe;
    color: #0284c7;
    padding-left: 25px;
}

.hamburger {
    display: none;
    flex-direction: column;
    cursor: pointer;
    padding: 8px;
    background: none;
    border: none;
    z-index: 1001;
    gap: 0;
}

.hamburger span {
    display: block;
    width: 25px;
    height: 3px;
    background: #0284c7;
    margin: 3px 0;
    border-radius: 3px;
    transition: all 0.3s ease;
}

.hamburger.active span:nth-child(1) {
    transform: rotate(45deg) translate(6px, 6px);
}

.hamburger.active span:nth-child(2) {
    opacity: 0;
}

.hamburger.active span:nth-child(3) {
    transform: rotate(-45deg) translate(6px, -6px);
}

.mobile-menu {
    position: fixed;
    top: 0;
    left: -300px;
    width: 280px;
    height: 100vh;
    background: white;
    box-shadow: 2px 0 15px rgba(0,0,0,0.15);
    transition: left 0.3s ease;
    z-index: 1000;
    overflow-y: auto;
}

.mobile-menu.active {
    left: 0;
}

.mobile-menu-header {
    background: #0284c7;
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mobile-menu-header h3 {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
}

.mobile-menu-close {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.mobile-menu-items {
    padding: 20px 0;
}

.mobile-menu-items > a,
.mobile-menu-items .mobile-dropdown-toggle {
    display: block;
    padding: 15px 20px;
    color: #1e293b;
    text-decoration: none;
    font-weight: 600;
    border-bottom: 1px solid #e2e8f0;
    transition: all 0.3s;
    cursor: pointer;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    font-size: 15px;
    font-family: inherit;
}

.mobile-menu-items > a:hover,
.mobile-menu-items > a.active,
.mobile-menu-items .mobile-dropdown-toggle:hover {
    background: #e0f2fe;
    color: #0284c7;
    padding-left: 25px;
}

.mobile-menu-items .mobile-dropdown-toggle {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mobile-submenu {
    display: none;
    background: #f8fafc;
}

.mobile-submenu.open {
    display: block;
}

.mobile-submenu a {
    display: block;
    padding: 12px 20px 12px 35px;
    color: #475569;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    border-bottom: 1px solid #e2e8f0;
    transition: all 0.2s;
}

.mobile-submenu a:hover {
    background: #e0f2fe;
    color: #0284c7;
    padding-left: 40px;
}

.overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
    backdrop-filter: blur(3px);
}

.overlay.active {
    display: block;
}

@media (max-width: 968px) {
    .nav a, .nav-item > a {
        padding: 8px 12px;
        font-size: 14px;
    }
}

@media (max-width: 768px) {
    .header {
        padding: 12px 20px;
    }
    .school-name h1 {
        font-size: 14px;
    }
    .school-name p {
        font-size: 11px;
    }
    .logo {
        width: 45px;
        height: 45px;
    }
    .hamburger {
        display: flex;
    }
    .nav {
        display: none;
    }
}

@media (max-width: 480px) {
    .school-name h1 {
        font-size: 13px;
    }
    .school-name p {
        font-size: 10px;
    }
    .logo {
        width: 40px;
        height: 40px;
    }
    .mobile-menu {
        width: 250px;
    }
}
</style>
</head>
<body>

<div class="overlay" id="overlay" onclick="toggleMenu()"></div>
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <h3>MENU</h3>
        <button class="mobile-menu-close" onclick="toggleMenu()">×</button>
    </div>
    <div class="mobile-menu-items">
        <a href="index.php" class="<?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">Beranda</a>
        
        <button class="mobile-dropdown-toggle" onclick="toggleSubmenu(event, 'submenu-profil')">
            Profil <span class="arrow">▾</span>
        </button>
        <div class="mobile-submenu" id="submenu-profil">
            <a href="profil.php#identitas-sekolah">Identitas</a>
            <a href="profil.php#visi-misi">Visi & Misi</a>    
            <a href="profil.php#tenaga-pendidik">Tenaga Pendidik</a>
            <a href="profil.php#fasilitas">Fasilitas</a>
            <a href="profil.php#ekskul">Ekstrakurikuler</a>
            <a href="profil.php#lokasi">Lokasi</a>
        </div>
        
        <button class="mobile-dropdown-toggle" onclick="toggleSubmenu(event, 'submenu-info')">
            Informasi <span class="arrow">▾</span>
        </button>
        <div class="mobile-submenu" id="submenu-info">
            <a href="informasi.php#pengumuman">Pengumuman</a>
            <a href="informasi.php#berita">Berita</a>
            <a href="informasi.php#kaih">KAIH</a>
        </div>
        
        <button class="mobile-dropdown-toggle" onclick="toggleSubmenu(event, 'submenu-lainnya')">
            Lainnya <span class="arrow">▾</span>
        </button>
        <div class="mobile-submenu" id="submenu-lainnya">
            <a href="lainnya.php#login">Login</a>
            <a href="lainnya.php#faq">FAQ</a>
            <a href="lainnya.php#kontak">Kontak</a>
        </div>
    </div>
</div>

<header class="header">
    <div class="header-content">
        <div class="logo-section">
            <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo SMP 28" class="logo">
            <div class="school-name">
                <h1>SMP NEGERI 28 BALIKPAPAN</h1>
                <p>MERCUSUAR (Media Rekap Cerdas Unjuk Aktivitas Rapor)</p>
            </div>
        </div>
        
        <nav class="nav">
            <a href="index.php" class="<?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">Beranda</a>
            
            <div class="nav-item">
                <a href="profil.php" class="<?php echo ($current_page === 'profil.php') ? 'active' : ''; ?>">
                    Profil <span class="arrow">▾</span>
                </a>
                <div class="dropdown-menu">
                    <a href="profil.php#identitas-sekolah">Identitas</a>
                    <a href="profil.php#visi-misi">Visi & Misi</a>
                    <a href="profil.php#tenaga-pendidik">Tenaga Pendidik</a>
                    <a href="profil.php#fasilitas">Fasilitas</a>
                    <a href="profil.php#ekskul">Ekstrakurikuler</a>
                    <a href="profil.php#lokasi">Lokasi</a>
                </div>
            </div>
            
            <div class="nav-item">
                <a href="informasi.php" class="<?php echo ($current_page === 'informasi.php') ? 'active' : ''; ?>">
                    Informasi <span class="arrow">▾</span>
                </a>
                <div class="dropdown-menu">
                    <a href="informasi.php#pengumuman">Pengumuman</a>
                    <a href="informasi.php#berita">Berita</a>
                    <a href="informasi.php#kaih">KAIH</a>
                </div>
            </div>
            
            <div class="nav-item">
                <a href="lainnya.php" class="<?php echo ($current_page === 'lainnya.php') ? 'active' : ''; ?>">
                    Lainnya <span class="arrow">▾</span>
                </a>
                <div class="dropdown-menu">
                    <a href="lainnya.php#login">Login</a>
                    <a href="lainnya.php#faq">FAQ</a>
                    <a href="lainnya.php#kontak">Kontak</a>
                </div>
            </div>
        </nav>
        
        <button class="hamburger" id="hamburger" onclick="toggleMenu()" aria-label="Toggle Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>

<script>
function toggleMenu() {
    const hamburger = document.getElementById('hamburger');
    const mobileMenu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('overlay');
    
    if (!hamburger || !mobileMenu || !overlay) return;
    hamburger.classList.toggle('active');
    mobileMenu.classList.toggle('active');
    overlay.classList.toggle('active');
    
    if (mobileMenu.classList.contains('active')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

function toggleSubmenu(e, id) {
    e.preventDefault();
    const submenu = document.getElementById(id);
    
    document.querySelectorAll('.mobile-submenu').forEach(function(sm) {
        if (sm.id !== id) {
            sm.classList.remove('open');
        }
    });
    
    submenu.classList.toggle('open');
}

document.querySelectorAll('.mobile-menu-items a').forEach(function(link) {
    link.addEventListener('click', function() {
        toggleMenu();
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileMenu && mobileMenu.classList.contains('active')) {
            toggleMenu();
        }
    }
});
</script>
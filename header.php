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
            <a href="index.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'class="active"' : ''; ?>>Beranda</a>
            <a href="profil.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'profil.php') ? 'class="active"' : ''; ?>>Profil</a>
            <a href="informasi.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'informasi.php') ? 'class="active"' : ''; ?>>Informasi</a>
            <a href="fitur.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'fitur.php') ? 'class="active"' : ''; ?>>Fitur</a>
            <a href="panduan.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'panduan.php') ? 'class="active"' : ''; ?>>Panduan</a>
            <a href="lainnya.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'lainnya.php') ? 'class="active"' : ''; ?>>Lainnya</a>
        </nav>
        
        <button class="hamburger" id="hamburger" onclick="toggleMenu()" aria-label="Toggle Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>

<div class="overlay" id="overlay" onclick="toggleMenu()"></div>

<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <h3>MENU</h3>
        <button class="mobile-menu-close" onclick="toggleMenu()">×</button>
    </div>
    <div class="mobile-menu-items">
        <a href="index.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'class="active"' : ''; ?>>Beranda</a>
        <a href="profil.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'profil.php') ? 'class="active"' : ''; ?>>Profil</a>
        <a href="informasi.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'informasi.php') ? 'class="active"' : ''; ?>>Informasi</a>
        <a href="fitur.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'fitur.php') ? 'class="active"' : ''; ?>>Fitur</a>
        <a href="panduan.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'panduan.php') ? 'class="active"' : ''; ?>>Panduan</a>
        <a href="lainnya.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'lainnya.php') ? 'class="active"' : ''; ?>>Lainnya</a>
    </div>
</div>

<style>
    .header {
        background: white;
        padding: 15px 40px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        position: sticky;
        top: 0;
        z-index: 100;
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
        object-fit: cover;
    }

    .school-name h1 {
        font-size: 20px;
        color: #1e293b;
        margin-bottom: 3px;
        font-weight: 700;
        line-height: 1.2;
    }

    .school-name p {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }

    .nav {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .nav a {
        text-decoration: none;
        color: #64748b;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 8px;
        transition: all 0.3s;
        font-size: 15px;
        white-space: nowrap;
    }

    .nav a:hover,
    .nav a.active {
        background: #e0f2fe;
        color: #0284c7;
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
        padding: 0;
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

    .mobile-menu-items a {
        display: block;
        padding: 15px 20px;
        color: #1e293b;
        text-decoration: none;
        font-weight: 600;
        border-bottom: 1px solid #e2e8f0;
        transition: all 0.3s;
    }

    .mobile-menu-items a:hover,
    .mobile-menu-items a.active {
        background: #e0f2fe;
        color: #0284c7;
        padding-left: 25px;
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
        .nav a {
            padding: 8px 15px;
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

    document.querySelectorAll('.mobile-menu-items a').forEach(link => {
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
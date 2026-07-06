<?php
/**
 * Header Component
 * Digunakan di seluruh halaman website
 */

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'KAIH - Sistem Informasi'; ?></title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/assets/css/header.css">
    <link rel="stylesheet" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/assets/css/footer.css">
    
    <!-- Additional Page-Specific CSS -->
    <?php if(isset($page_css)): ?>
        <link rel="stylesheet" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/assets/css/<?php echo $page_css; ?>">
    <?php endif; ?>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header Navigation -->
    <header class="header">
        <nav class="navbar">
            <div class="navbar-container">
                <!-- Logo Section -->
                <div class="navbar-logo">
                    <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/index.php" class="logo-link">
                        <div class="logo-placeholder">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <span class="logo-text">KAIH</span>
                    </a>
                </div>

                <!-- Hamburger Menu -->
                <div class="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <!-- Navigation Menu -->
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/index.php" 
                           class="nav-link <?php echo ($current_page == 'index') ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Beranda
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a href="javascript:void(0)" class="nav-link dropdown-toggle">
                            <i class="fas fa-cog"></i> Admin
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/admin/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                            <li><a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/admin/siswa.php"><i class="fas fa-users"></i> Data Siswa</a></li>
                            <li><a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/admin/guru.php"><i class="fas fa-chalkboard-user"></i> Data Guru</a></li>
                            <li><a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/admin/kelas.php"><i class="fas fa-door-open"></i> Data Kelas</a></li>
                            <li><a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/admin/users.php"><i class="fas fa-user-shield"></i> Kelola User</a></li>
                        </ul>
                    </li>


                </ul>

                <!-- Right Side Menu -->
                <div class="nav-right">
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Cari...">
                        <button class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">

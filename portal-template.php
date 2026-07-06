<?php
/**
 * Portal - Halaman Utama Portal
 * Template dengan header dan footer baru
 */

$page_title = 'Portal - KAIH';
$page_css = 'portal.css';
include 'includes/header.php';
?>

        <!-- Portal Header -->
        <section class="portal-header">
            <div class="container">
                <h1>Portal KAIH</h1>
                <p>Sistem Informasi Akademik Terpadu</p>
            </div>
        </section>

        <!-- Portal Content -->
        <section class="portal-content">
            <div class="container">
                <div class="row cols-2">
                    <!-- Card 1: Data Siswa -->
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="portal-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3>Data Siswa</h3>
                            <p class="text-muted">Kelola informasi data siswa</p>
                            <a href="admin/siswa.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-right"></i> Lihat Selengkapnya
                            </a>
                        </div>
                    </div>

                    <!-- Card 2: Data Guru -->
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="portal-icon">
                                <i class="fas fa-chalkboard-user"></i>
                            </div>
                            <h3>Data Guru</h3>
                            <p class="text-muted">Kelola informasi data guru</p>
                            <a href="admin/guru.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-right"></i> Lihat Selengkapnya
                            </a>
                        </div>
                    </div>

                    <!-- Card 3: Data Kelas -->
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="portal-icon">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <h3>Data Kelas</h3>
                            <p class="text-muted">Kelola informasi kelas</p>
                            <a href="admin/kelas.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-right"></i> Lihat Selengkapnya
                            </a>
                        </div>
                    </div>

                    <!-- Card 4: Dashboard -->
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="portal-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3>Dashboard Admin</h3>
                            <p class="text-muted">Lihat ikhtisar data akademik</p>
                            <a href="admin/dashboard.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-right"></i> Buka Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Card 5: Laporan -->
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="portal-icon">
                                <i class="fas fa-file-chart-line"></i>
                            </div>
                            <h3>Laporan</h3>
                            <p class="text-muted">Buat dan lihat laporan</p>
                            <a href="#" class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-right"></i> Lihat Laporan
                            </a>
                        </div>
                    </div>

                    <!-- Card 6: Pengaturan -->
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="portal-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <h3>Pengaturan</h3>
                            <p class="text-muted">Atur konfigurasi sistem</p>
                            <a href="#" class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-right"></i> Pengaturan
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Info Section -->
                <section class="portal-info">
                    <h2>Informasi Penting</h2>
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>Tahun Akademik</h4>
                            <p class="value">2025/2026</p>
                        </div>
                        <div class="info-card">
                            <h4>Total Siswa</h4>
                            <p class="value">1</p>
                        </div>
                        <div class="info-card">
                            <h4>Total Guru</h4>
                            <p class="value">1</p>
                        </div>
                        <div class="info-card">
                            <h4>Total Kelas</h4>
                            <p class="value">8</p>
                        </div>
                    </div>
                </section>
            </div>
        </section>

<?php include 'includes/footer.php'; ?>

<?php
declare(strict_types=1);

require_once '../includes/admin_auth.php';
requireAdminLogin();
require_once '../config/database.php';

$username = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');
$conn = getConnection();
// Counts
$totalSiswa = (int) ($conn->query("SELECT COUNT(*) FROM siswa")->fetch_row()[0] ?? 0);
$totalGuru  = (int) ($conn->query("SELECT COUNT(*) FROM guru")->fetch_row()[0] ?? 0);
$totalKelas = (int) ($conn->query("SELECT COUNT(*) FROM kaih_kelas")->fetch_row()[0] ?? 0);
$totalUsers = (int) ($conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0] ?? 0);

// Data untuk grafik (always load so dashboard chart works even after switching tabs)
$siswaPerKelas = [];
$result = $conn->query("SELECT k.nama_kelas, COUNT(s.id) as total FROM kaih_kelas k LEFT JOIN siswa s ON k.id = s.wali_kelas_id GROUP BY k.id, k.nama_kelas ORDER BY k.nama_kelas");
while ($row = $result->fetch_assoc()) {
    $siswaPerKelas[] = $row;
}

$userByRole = [];
$result = $conn->query("SELECT role, COUNT(*) as total FROM users GROUP BY role");
while ($row = $result->fetch_assoc()) {
    $userByRole[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <script>(function(){try{var t=localStorage.getItem('kaih_theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();function toggleTheme(){var h=document.documentElement,d=h.getAttribute('data-theme')==='dark';if(d)h.removeAttribute('data-theme');else h.setAttribute('data-theme','dark');try{localStorage.setItem('kaih_theme',d?'light':'dark');}catch(e){}}</script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — KAIH Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="../assets/css/siswa.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="layout">

  <button class="sidebar-toggle" id="sidebarToggle" title="Menu">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  </button>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <div class="logo-box">
          <svg viewBox="0 0 40 40" fill="none">
            <rect width="40" height="40" rx="10" fill="url(#lgDash)"/>
            <path d="M10 20L17 13L24 20L17 27L10 20Z" fill="white"/>
            <path d="M18 20L25 13L32 20L25 27L18 20Z" fill="white" fill-opacity=".6"/>
            <defs><linearGradient id="lgDash" x1="0" y1="0" x2="40" y2="40"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs>
          </svg>
        </div>
        <div class="logo-text"><span class="logo-title">KAIH</span><span class="logo-sub">Admin Panel</span></div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Menu Utama</div>
      <a href="dashboard.php" class="nav-item active">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/></svg>
        <span>Dashboard</span>
      </a>
      <a href="siswa.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span>Data Siswa</span>
      </a>
      <a href="guru.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
        <span>Data Guru</span>
      </a>
      <a href="kelas.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/><polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2"/></svg>
        <span>Data Kelas</span>
      </a>
      <a href="users.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span>Akun &amp; User</span>
      </a>
      <a href="foto.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="2"/><path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Foto Slideshow</span>
      </a>
      <div class="nav-section-label">Laporan</div>
      <a href="laporan.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/><polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2"/><line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span>Laporan Siswa</span>
      </a>
      <a href="laporan-guru.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="2"/><rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="2"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Laporan Guru</span>
      </a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
        <div class="user-detail">
          <span class="user-name"><?= htmlspecialchars($username) ?></span>
          <span class="user-role">Administrator</span>
        </div>
      </div>
      <a href="logout.php" class="btn-logout" title="Keluar">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
    </div>
  </aside>

  <main class="main-content">

    <header class="topbar">
      <div class="topbar-left">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-sub">Ringkasan data sekolah &mdash; selamat datang, <strong><?= htmlspecialchars($username) ?></strong></p>
      </div>
      <div class="topbar-right">
        <div class="topbar-date">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/><line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/><line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/></svg>
          <?= date('d M Y') ?>
        </div>
        <div class="admin-chip"><div class="admin-dot"></div>Admin</div>
      </div>
    </header>

    <div class="content-area">

      <section class="dash-stats">
        <a class="dash-stat" href="siswa.php">
          <div class="dash-stat-title">Total Siswa</div>
          <div class="dash-stat-value"><?= $totalSiswa ?></div>
          <div class="dash-stat-sub">Kelola data siswa</div>
        </a>
        <a class="dash-stat" href="guru.php">
          <div class="dash-stat-title">Total Guru</div>
          <div class="dash-stat-value"><?= $totalGuru ?></div>
          <div class="dash-stat-sub">Kelola data guru</div>
        </a>
        <a class="dash-stat" href="kelas.php">
          <div class="dash-stat-title">Total Kelas</div>
          <div class="dash-stat-value"><?= $totalKelas ?></div>
          <div class="dash-stat-sub">Kelola data kelas</div>
        </a>
        <a class="dash-stat" href="users.php">
          <div class="dash-stat-title">Total User</div>
          <div class="dash-stat-value"><?= $totalUsers ?></div>
          <div class="dash-stat-sub">Kelola akun &amp; user</div>
        </a>
        <a class="dash-stat" href="laporan.php">
          <div class="dash-stat-title">Laporan Siswa</div>
          <div class="dash-stat-value">📋</div>
          <div class="dash-stat-sub">Monitor kegiatan harian</div>
        </a>
      </section>

      <section class="dash-grid">

        <div class="dash-card">
          <div class="dash-card-head">
            <h3>📚 Siswa per Kelas</h3>
          </div>
          <div class="dash-chart" style="height: 320px;">
            <canvas id="siswaPerKelasChart"></canvas>
          </div>
        </div>

        <div class="dash-card">
          <div class="dash-card-head">
            <h3>👥 Distribusi Role</h3>
          </div>
          <div class="dash-chart" style="height: 320px;">
            <canvas id="userRoleChart"></canvas>
          </div>
        </div>

        <div class="dash-card">
          <div class="dash-card-head">
            <h3>📊 Guru vs Siswa</h3>
          </div>
          <div class="dash-chart" style="height: 320px;">
            <canvas id="guruSiswaChart"></canvas>
          </div>
        </div>

        <div class="dash-card">
          <div class="dash-card-head">
            <h3>📈 Statistik Cepat</h3>
          </div>
          <?php
            $ratioGuruSiswa = $totalSiswa > 0 ? round($totalGuru / $totalSiswa, 2) : 0;
            $ratioPercent = $totalSiswa > 0 ? min(100, ($totalGuru / $totalSiswa) * 100) : 0;
          ?>
          <div class="dash-metrics">
            <div class="dash-metric">
              <div class="dash-metric-row"><span>Guru per Siswa</span><strong><?= $ratioGuruSiswa ?></strong></div>
              <div class="dash-progress"><div class="dash-progress-bar" style="width: <?= $ratioPercent ?>%;"></div></div>
            </div>
            <div class="dash-metric">
              <div class="dash-metric-row"><span>User Aktif</span><strong class="ok"><?= $totalUsers ?></strong></div>
              <div class="dash-progress"><div class="dash-progress-bar ok" style="width: 100%;"></div></div>
            </div>
            <div class="dash-metric">
              <div class="dash-metric-row"><span>Kelas Tersedia</span><strong class="warn"><?= $totalKelas ?></strong></div>
              <div class="dash-progress"><div class="dash-progress-bar warn" style="width: 100%;"></div></div>
            </div>
          </div>
        </div>

      </section>
    </div>
  </main>

</div>

<script>
  // Charts
  document.addEventListener('DOMContentLoaded', function () {
    const siswaPerKelasCtx = document.getElementById('siswaPerKelasChart');
    if (siswaPerKelasCtx) {
      const siswaPerKelasData = <?= json_encode($siswaPerKelas) ?>;
      const labels = siswaPerKelasData.map(item => item.nama_kelas);
      const data = siswaPerKelasData.map(item => item.total);

      new Chart(siswaPerKelasCtx, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Jumlah Siswa',
            data,
            backgroundColor: '#6366f1',
            borderColor: '#4f46e5',
            borderWidth: 1,
            borderRadius: 8
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: true,
              position: 'bottom',
              labels: { font: { size: 12 }, padding: 14 }
            }
          },
          scales: {
            y: { beginAtZero: true, ticks: { font: { size: 12 } } },
            x: { ticks: { font: { size: 12 } } }
          }
        }
      });
    }

    const userRoleCtx = document.getElementById('userRoleChart');
    if (userRoleCtx) {
      const userByRoleData = <?= json_encode($userByRole) ?>;
      const roleLabels = userByRoleData.map(item => {
        const roleMap = { admin: 'Admin', guru: 'Guru', siswa: 'Siswa', orang_tua: 'Orang Tua' };
        return roleMap[item.role] || item.role;
      });
      const roleData = userByRoleData.map(item => item.total);
      const roleColors = ['#6366f1', '#22c55e', '#f59e0b', '#ef4444'];

      new Chart(userRoleCtx, {
        type: 'doughnut',
        data: {
          labels: roleLabels,
          datasets: [{
            data: roleData,
            backgroundColor: roleColors.slice(0, roleLabels.length),
            borderColor: '#ffffff',
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: true,
              position: 'bottom',
              labels: { font: { size: 12 }, padding: 14 }
            }
          }
        }
      });
    }

    const guruSiswaCtx = document.getElementById('guruSiswaChart');
    if (guruSiswaCtx) {
      const totalSiswa = <?= (int) $totalSiswa ?>;
      const totalGuru = <?= (int) $totalGuru ?>;

      new Chart(guruSiswaCtx, {
        type: 'line',
        data: {
          labels: ['Jumlah'],
          datasets: [
            {
              label: 'Siswa',
              data: [totalSiswa],
              borderColor: '#6366f1',
              backgroundColor: 'rgba(99, 102, 241, 0.12)',
              tension: 0.35,
              fill: true,
              borderWidth: 3,
              pointRadius: 6,
              pointBackgroundColor: '#4f46e5'
            },
            {
              label: 'Guru',
              data: [totalGuru],
              borderColor: '#22c55e',
              backgroundColor: 'rgba(34, 197, 94, 0.12)',
              tension: 0.35,
              fill: true,
              borderWidth: 3,
              pointRadius: 6,
              pointBackgroundColor: '#16a34a'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: true,
              position: 'bottom',
              labels: { font: { size: 12 }, padding: 14 }
            }
          },
          scales: {
            y: { beginAtZero: true, ticks: { font: { size: 12 } } },
            x: { ticks: { font: { size: 12 } } }
          }
        }
      });
    }
  });
</script>

<script>
(function(){
  var btn=document.getElementById('sidebarToggle'),sb=document.getElementById('sidebar'),bd=document.getElementById('sidebarBackdrop');
  if(!btn||!sb)return;
  function toggle(){sb.classList.toggle('open');bd.classList.toggle('active');}
  function close(){sb.classList.remove('open');bd.classList.remove('active');}
  btn.addEventListener('click',toggle);
  if(bd)bd.addEventListener('click',close);
  sb.querySelectorAll('.nav-item').forEach(function(a){a.addEventListener('click',close);});
})();
</script>

<button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Ganti tema">
  <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="2"/><path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>
</body>
</html>

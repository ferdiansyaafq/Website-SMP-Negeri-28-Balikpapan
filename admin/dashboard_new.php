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
    $result = $conn->query("SELECT u.id, u.username, u.role, DATE_FORMAT(u.created_at, '%d %b %Y') as created_date FROM users u ORDER BY u.username");
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
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text: #1f2937;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --bg: #f3f4f6;
            --bg-white: #ffffff;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; width: 100%; }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .container {
            display: grid;
            grid-template-columns: 1fr;
            padding: 1.5rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .header-left p {
            font-size: 14px;
            color: var(--text-muted);
        }

        .header-right {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: var(--bg-white);
            border-radius: 10px;
            border: 1px solid var(--border);
            font-size: 13px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-white);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .stat-card h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            font-size: 13px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tabs {
            display: flex;
            gap: 0.5rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 1rem;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
            font-family: inherit;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tab-btn:hover {
            color: var(--text);
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .section {
            background: var(--bg-white);
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            display: none;
        }

        .section.active {
            display: block;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-header h2 {
            font-size: 18px;
            font-weight: 700;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 12px;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .section-content {
            padding: 1.5rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .table thead th {
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid var(--border);
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--bg);
        }

        .table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .table tbody tr:hover {
            background: var(--bg);
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-edit {
            padding: 0.5rem 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-edit:hover {
            background: var(--primary-dark);
        }

        .btn-delete {
            padding: 0.5rem 1rem;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-white);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            padding: 2rem;
        }

        .modal-header {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .logout-btn {
            padding: 0.5rem 1rem;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }

        .logout-btn:hover {
            background: #dc2626;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .table {
                font-size: 12px;
            }

            .table thead th,
            .table tbody td {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>Dashboard Admin</h1>
                <p>Kelola data siswa, guru, dan pengguna sistem KAIH</p>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
                    <div>
                        <div style="font-weight: 600;"><?= $username ?></div>
                        <div style="font-size: 11px; color: var(--text-muted);">Administrator</div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= number_format($totalSiswa) ?></h3>
                <p>Total Siswa</p>
            </div>
            <div class="stat-card">
                <h3><?= number_format($totalGuru) ?></h3>
                <p>Total Guru</p>
            </div>
            <div class="stat-card">
                <h3><?= number_format($totalUsers) ?></h3>
                <p>Total Users</p>
            </div>
        </div>

        <div class="tabs">
            <button class="tab-btn active" data-tab="dashboard">📊 Dashboard</button>
            <button class="tab-btn" data-tab="siswa">👥 Siswa</button>
            <button class="tab-btn" data-tab="guru">👨‍🏫 Guru</button>
            <button class="tab-btn" data-tab="user">👤 User</button>
        </div>

        <!-- Dashboard Tab -->
        <div id="dashboard" class="section active">
            <div class="section-header">
                <h2>Ringkasan Data</h2>
            </div>
            <div class="section-content">
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Gunakan tab di atas untuk mengelola data siswa, guru, dan akun pengguna.</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div style="background: var(--bg); padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--primary);">
                        <div style="font-size: 24px; font-weight: 700; color: var(--primary);"><?= number_format($totalSiswa) ?></div>
                        <div style="font-size: 13px; color: var(--text-muted); margin-top: 0.5rem;">👥 Data Siswa Terdaftar</div>
                    </div>
                    <div style="background: var(--bg); padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--success);">
                        <div style="font-size: 24px; font-weight: 700; color: var(--success);"><?= number_format($totalGuru) ?></div>
                        <div style="font-size: 13px; color: var(--text-muted); margin-top: 0.5rem;">👨‍🏫 Data Guru Terdaftar</div>
                    </div>
                    <div style="background: var(--bg); padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--warning);">
                        <div style="font-size: 24px; font-weight: 700; color: var(--warning);"><?= number_format($totalUsers) ?></div>
                        <div style="font-size: 13px; color: var(--text-muted); margin-top: 0.5rem;">👤 Akun User Terdaftar</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Siswa Tab -->
        <div id="siswa" class="section">
            <div class="section-header">
                <h2>Data Siswa</h2>
                <button class="btn btn-primary btn-small" onclick="openSiswaModal()">+ Tambah Siswa</button>
            </div>
            <div class="section-content">
                <?php if (count($siswaList) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($siswaList as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['nisn']) ?></td>
                            <td><?= htmlspecialchars($s['nama_siswa']) ?></td>
                            <td><?= htmlspecialchars($s['nama_kelas'] ?? '-') ?></td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn-edit btn-small" onclick="editSiswa(<?= $s['id'] ?>)">Edit</button>
                                    <button class="btn-delete btn-small" onclick="deleteSiswa(<?= $s['id'] ?>, '<?= htmlspecialchars($s['nama_siswa']) ?>')">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 0.5rem;">Belum ada data siswa</h3>
                    <p>Klik tombol "Tambah Siswa" untuk memulai.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Guru Tab -->
        <div id="guru" class="section">
            <div class="section-header">
                <h2>Data Guru</h2>
                <button class="btn btn-primary btn-small" onclick="openGuruModal()">+ Tambah Guru</button>
            </div>
            <div class="section-content">
                <?php if (count($guruList) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>NIP</th>
                            <th>Nama Guru</th>
                            <th>Jabatan</th>
                            <th>Kelas Wali</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($guruList as $g): ?>
                        <tr>
                            <td><?= htmlspecialchars($g['nip']) ?></td>
                            <td><?= htmlspecialchars($g['nama_guru']) ?></td>
                            <td><?= htmlspecialchars($g['jabatan'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($g['kelas'] ?? '-') ?></td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn-edit btn-small" onclick="editGuru(<?= $g['id'] ?>)">Edit</button>
                                    <button class="btn-delete btn-small" onclick="deleteGuru(<?= $g['id'] ?>, '<?= htmlspecialchars($g['nama_guru']) ?>')">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 0.5rem;">Belum ada data guru</h3>
                    <p>Klik tombol "Tambah Guru" untuk memulai.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- User Tab -->
        <div id="user" class="section">
            <div class="section-header">
                <h2>Akun User</h2>
                <button class="btn btn-primary btn-small" onclick="openUserModal()">+ Tambah User</button>
            </div>
            <div class="section-content">
                <?php if (count($userList) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Terdaftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userList as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><span style="padding: 0.25rem 0.75rem; background: var(--bg); border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase;"><?= htmlspecialchars($u['role']) ?></span></td>
                            <td><?= htmlspecialchars($u['created_date']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn-delete btn-small" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 0.5rem;">Belum ada akun user</h3>
                    <p>Klik tombol "Tambah User" untuk memulai.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Tab Navigation
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.getAttribute('data-tab');
                document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.getElementById(tab).classList.add('active');
                btn.classList.add('active');
            });
        });

        // Modal functions
        function openSiswaModal() {
            alert('Feature coming soon!');
        }

        function openGuruModal() {
            alert('Feature coming soon!');
        }

        function openUserModal() {
            alert('Feature coming soon!');
        }

        function editSiswa(id) {
            alert('Edit siswa: ' + id);
        }

        function editGuru(id) {
            alert('Edit guru: ' + id);
        }

        function deleteSiswa(id, nama) {
            if (confirm('Hapus siswa ' + nama + '?')) {
                window.location.href = 'siswa.php?action=delete&id=' + id;
            }
        }

        function deleteGuru(id, nama) {
            if (confirm('Hapus guru ' + nama + '?')) {
                window.location.href = 'guru.php?action=delete&id=' + id;
            }
        }

        function deleteUser(id, username) {
            if (confirm('Hapus user ' + username + '?')) {
                window.location.href = 'users.php?action=delete&id=' + id;
            }
        }
    </script>

</body>
</html>

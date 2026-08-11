<?php
session_start();

// Cek apakah ada request untuk ganti role
if (isset($_GET['login_as'])) {
    $role = $_GET['login_as'];
    
    // Bersihkan session lama biar nggak bentrok
    session_destroy();
    session_start();
    
    // Set Session Instan berdasarkan Role
    if ($role == 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'Admin Sakti';
        $_SESSION['portal_role'] = 'admin';
        header("Location: aplikasi/admin/index.php");
    } 
    elseif ($role == 'guru') {
        $_SESSION['user_id'] = 2;
        $_SESSION['guru_id'] = 8; // Ganti dengan ID Guru yang ada di database-mu
        $_SESSION['username'] = 'Guru Sakti';
        $_SESSION['portal_role'] = 'guru';
        header("Location: aplikasi/guru/index.php");
    } 
    elseif ($role == 'siswa') {
        $_SESSION['user_id'] = 3;
        $_SESSION['siswa_id'] = 10; // Ganti dengan ID Siswa yang ada di database-mu
        $_SESSION['username'] = 'Siswa Sakti';
        $_SESSION['portal_role'] = 'siswa';
        header("Location: aplikasi/siswa/index.php");
    } 
    elseif ($role == 'ortu') {
        $_SESSION['user_id'] = 4;
        $_SESSION['siswa_id'] = 10; // Ganti dengan ID Anak yang sama dengan ID Siswa di atas
        $_SESSION['username'] = 'ORT_Sakti';
        $_SESSION['portal_role'] = 'orang_tua';
        header("Location: aplikasi/ortu/index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>God Mode - Fast Login</title>
    <style>
        body { font-family: sans-serif; background: #1e293b; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: #0f172a; padding: 40px; border-radius: 12px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h2 { margin-top: 0; color: #38bdf8; }
        .btn-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
        .btn { padding: 15px; border: none; border-radius: 8px; font-weight: bold; font-size: 16px; cursor: pointer; color: white; text-decoration: none; transition: 0.2s; }
        .btn:hover { transform: translateY(-3px); }
        .btn-admin { background: #ef4444; }
        .btn-guru { background: #10b981; }
        .btn-siswa { background: #0ea5e9; }
        .btn-ortu { background: #f59e0b; }
    </style>
</head>
<body>

<div class="container">
    <h2>⚡ God Mode Switcher</h2>
    <p>Pilih role untuk bypass login (Development Only)</p>
    
    <div class="btn-grid">
        <a href="?login_as=admin" class="btn btn-admin">💻 Login Admin</a>
        <a href="?login_as=guru" class="btn btn-guru">👨‍🏫 Login Guru</a>
        <a href="?login_as=siswa" class="btn btn-siswa">👨‍🎓 Login Siswa</a>
        <a href="?login_as=ortu" class="btn btn-ortu">👨‍👩‍👧 Login Ortu</a>
    </div>
</div>

</body>
</html>
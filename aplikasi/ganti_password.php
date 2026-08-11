<?php
// aplikasi/ganti_password.php
session_start();
require_once '../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$message_type = '';
$username = $_SESSION['username'];

// Proses Ganti Password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'Semua kolom wajib diisi!';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Password baru dan konfirmasi password tidak cocok!';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password baru minimal harus 6 karakter!';
        $message_type = 'error';
    } else {
        try {
            // Ambil hash password saat ini dari database
            $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($old_password, $user['password'])) {
                // Enkripsi password baru
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update ke database
                $stmtUpdate = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE username = ?");
                $stmtUpdate->execute([$new_hash, $username]);
                
                $message = '✨ Password berhasil diubah! Silakan gunakan password baru pada login berikutnya.';
                $message_type = 'success';
            } else {
                $message = 'Password saat ini (lama) salah!';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Set variabel untuk header agar tidak error
$_SESSION['portal_role'] = $_SESSION['portal_role'] ?? $_SESSION['role'] ?? 'user';
// Include header (karena kita ada di root 'aplikasi', path-nya menyesuaikan)
require_once 'includes/header-kaih.php';
?>

<style>
    .pw-container { max-width: 500px; margin: 40px auto; background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .pw-title { margin-top: 0; color: #1e293b; font-size: 22px; font-weight: 800; margin-bottom: 5px; text-align: center; }
    .pw-subtitle { color: #64748b; font-size: 14px; text-align: center; margin-bottom: 25px; }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 700; color: #475569; font-size: 13px; margin-bottom: 8px; }
    .form-group input { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; box-sizing: border-box; outline: none; transition: 0.2s; }
    .form-group input:focus { border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
    
    .btn-submit { width: 100%; padding: 14px; background: #0284c7; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .btn-submit:hover { background: #0369a1; transform: translateY(-2px); }
    
    .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 14px; text-align: center; }
    .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
</style>

<div class="pw-container">
    <h2 class="pw-title">🔒 Ubah Password</h2>
    <p class="pw-subtitle">Amankan akun portal KAIH kamu secara berkala</p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Password Saat Ini (Lama)</label>
            <input type="password" name="old_password" placeholder="Masukkan password lama..." required>
        </div>
        
        <div class="form-group">
            <label>Password Baru</label>
            <input type="password" name="new_password" placeholder="Minimal 6 karakter..." required minlength="6">
        </div>
        
        <div class="form-group">
            <label>Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" placeholder="Ketik ulang password baru..." required minlength="6">
        </div>
        
        <button type="submit" class="btn-submit">Simpan Password Baru</button>
    </form>
</div>

<?php 
// Kosongkan fungsi klik menu yang nyangkut di header karena beda path folder
echo '<script>document.querySelectorAll(".nav-link").forEach(el => { if(el.getAttribute("href") === "../ganti_password.php") el.setAttribute("href", "#"); });</script>';
?>
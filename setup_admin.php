<?php
require_once 'config/database.php';

$username = 'admin';
$password = 'admin123';
$hash     = password_hash($password, PASSWORD_BCRYPT);

$conn = getConnection();

// Hapus semua akun admin lama
$conn->query("DELETE FROM users WHERE role = 'admin'");

// Buat akun admin baru
$stmt = $conn->prepare("INSERT INTO users (username, password, role, created_at, updated_at) VALUES (?, ?, 'admin', NOW(), NOW())");
$stmt->bind_param('ss', $username, $hash);
$ok = $stmt->execute();

if ($ok) {
    echo "<h2 style='font-family:sans-serif;color:green'>✅ Akun admin berhasil dibuat!</h2>";
    echo "<p style='font-family:sans-serif'>Username: <strong>$username</strong></p>";
    echo "<p style='font-family:sans-serif'>Password: <strong>$password</strong></p>";
    echo "<p style='font-family:sans-serif'>Hash: <code>$hash</code></p>";
    echo "<br><a href='admin/login.php' style='font-family:sans-serif'>→ Pergi ke halaman Login</a>";
} else {
    echo "<h2 style='font-family:sans-serif;color:red'>❌ Gagal: " . $conn->error . "</h2>";
}

$stmt->close();
$conn->close();
?>

<?php
// aplikasi/includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$role = $_SESSION['role'] ?? '';
$allowed_roles = ['admin', 'guru', 'siswa', 'orang_tua'];

if (!in_array($role, $allowed_roles)) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Ambil data user
require_once __DIR__ . '/../../config/database.php';

// Pastikan $pdo tersedia
if (!isset($pdo)) {
    // Jika menggunakan database.php dengan PDO
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=kaih;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

try {
    $stmt = $pdo->prepare("SELECT u.*, 
        CASE 
            WHEN u.role = 'guru' THEN g.nama_guru 
            WHEN u.role = 'siswa' THEN s.nama_siswa 
            WHEN u.role = 'orang_tua' THEN 'Orang Tua'
            ELSE u.username 
        END as nama_lengkap
        FROM users u
        LEFT JOIN guru g ON u.guru_id = g.id
        LEFT JOIN siswa s ON u.siswa_id = s.id
        WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user = ['nama_lengkap' => $_SESSION['username']];
}

// Konstanta path
define('BASE_URL', '/Website-SMP-Negeri-28/');
define('KAIH_URL', BASE_URL . 'aplikasi/');
?>
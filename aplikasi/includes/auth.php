<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$applicationPathPosition = strpos($scriptPath, '/aplikasi/');
$basePath = $applicationPathPosition === false ? '' : substr($scriptPath, 0, $applicationPathPosition);
$loginUrl = $basePath . '/login.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $loginUrl);
    exit;
}

$role = $_SESSION['role'] ?? '';
$allowed_roles = ['admin', 'guru', 'siswa', 'orang_tua', 'kepsek'];

if (!in_array($role, $allowed_roles)) {
    session_destroy();
    header('Location: ' . $loginUrl);
    exit;
}

if (strpos($scriptPath, '/aplikasi/admin/') !== false && $role !== 'admin') {
    header('Location: ' . $loginUrl);
    exit;
}
if (strpos($scriptPath, '/aplikasi/guru/') !== false && $role !== 'guru' && $role !== 'admin') {
    header('Location: ' . $loginUrl);
    exit;
}
if (strpos($scriptPath, '/aplikasi/siswa/') !== false && $role !== 'siswa' && $role !== 'admin') {
    header('Location: ' . $loginUrl);
    exit;
}
if (strpos($scriptPath, '/aplikasi/ortu/') !== false && $role !== 'orang_tua' && $role !== 'admin') {
    header('Location: ' . $loginUrl);
    exit;
}
if (strpos($scriptPath, '/aplikasi/kepsek/') !== false && $role !== 'kepsek' && $role !== 'admin') {
    header('Location: ' . $loginUrl);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

if (!isset($pdo)) {
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
            WHEN u.role = 'kepsek' THEN COALESCE(g.nama_guru, 'Aris Broto, S.Pd (Kepala Sekolah)')
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

define('BASE_URL', $basePath . '/');
define('KAIH_URL', BASE_URL . 'aplikasi/');
?>
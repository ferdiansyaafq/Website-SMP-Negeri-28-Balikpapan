<?php
// aplikasi/includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cegah caching browser agar halaman internal tidak bisa dibuka dari history/tombol Back setelah logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$applicationPathPosition = strpos($scriptPath, '/aplikasi/');
$basePath = $applicationPathPosition === false ? '' : substr($scriptPath, 0, $applicationPathPosition);
$loginUrl = ($basePath !== '' ? $basePath : '') . '/login.php';

// 2. Cek Login Global: jika belum login sama sekali, langsung lempar ke login.php
if (!isset($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: ' . $loginUrl);
    exit;
}

$role = strtolower((string)($_SESSION['role'] ?? ''));
$allowed_roles = ['admin', 'guru', 'siswa', 'orang_tua', 'kepsek'];

if (!in_array($role, $allowed_roles, true)) {
    session_destroy();
    header('Location: ' . $loginUrl);
    exit;
}

// 3. Strict Role-Based Access Control (RBAC): Cegah nembak URL lintas role
$targetPortal = '';
if (strpos($scriptPath, '/aplikasi/admin/') !== false) {
    $targetPortal = 'admin';
} elseif (strpos($scriptPath, '/aplikasi/guru/') !== false) {
    $targetPortal = 'guru';
} elseif (strpos($scriptPath, '/aplikasi/siswa/') !== false) {
    $targetPortal = 'siswa';
} elseif (strpos($scriptPath, '/aplikasi/ortu/') !== false) {
    $targetPortal = 'orang_tua';
} elseif (strpos($scriptPath, '/aplikasi/kepsek/') !== false) {
    $targetPortal = 'kepsek';
}

if ($targetPortal !== '') {
    // Validasi: User hanya boleh mengakses portal yang sesuai dengan rolenya
    if ($role !== $targetPortal && $role !== 'admin') {
        switch ($role) {
            case 'guru':
                header('Location: ' . $basePath . '/aplikasi/guru/index.php');
                break;
            case 'siswa':
                header('Location: ' . $basePath . '/aplikasi/siswa/index.php');
                break;
            case 'orang_tua':
                header('Location: ' . $basePath . '/aplikasi/ortu/index.php');
                break;
            case 'kepsek':
                header('Location: ' . $basePath . '/aplikasi/kepsek/index.php');
                break;
            default:
                header('Location: ' . $loginUrl);
        }
        exit;
    }
}

// Ambil data user
require_once __DIR__ . '/../../config/database.php';

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
    $user = ['nama_lengkap' => $_SESSION['username'] ?? 'User'];
}

// Konstanta path
define('BASE_URL', $basePath . '/');
define('KAIH_URL', BASE_URL . 'aplikasi/');
?>
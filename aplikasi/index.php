<?php
// aplikasi/index.php
require_once 'includes/auth.php';

$role = $_SESSION['role'];

switch ($role) {
    case 'admin':
        header('Location: admin/index.php');
        break;
    case 'guru':
        header('Location: guru/index.php');
        break;
    case 'siswa':
        header('Location: siswa/index.php');
        break;
    case 'orang_tua':
        header('Location: ortu/index.php');
        break;
    default:
        header('Location: ../index.php');
}
exit;
?>
<?php
if (!defined('APP_TIMEZONE')) {
    if (PHP_OS_FAMILY === 'Windows') {
        define('APP_TIMEZONE', 'Asia/Jakarta');
        define('DB_TIMEZONE', '+07:00');
    } else {
        define('APP_TIMEZONE', 'Asia/Makassar');
        define('DB_TIMEZONE', '+08:00');
    }
}
date_default_timezone_set(APP_TIMEZONE);

$host = 'localhost';
$dbname = 'kaih';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET time_zone = '" . DB_TIMEZONE . "'");
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>

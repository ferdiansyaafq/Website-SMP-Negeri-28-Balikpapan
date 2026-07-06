<?php
require 'config/database.php';
require 'includes/user_accounts.php';

echo "PHP Test Started\n";
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\nZIP Extension Check\n";
echo "- extension_loaded('zip'): " . (extension_loaded('zip') ? 'YES' : 'NO') . "\n";
echo "- class_exists('ZipArchive'): " . (class_exists('ZipArchive') ? 'YES' : 'NO') . "\n";
if (class_exists('ZipArchive')) {
    echo "- ZipArchive version: " . (defined('ZipArchive::LIBZIP_VERSION') ? ZipArchive::LIBZIP_VERSION : 'n/a') . "\n";
}

try {
    $conn = getConnection();
    echo "✓ Database connection OK\n";
    
    $result = $conn->query('SELECT COUNT(*) as cnt FROM siswa');
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✓ Siswa table: " . $row['cnt'] . " records\n";
    } else {
        echo "✗ Query failed: " . $conn->error . "\n";
    }
    
    $result = $conn->query('SELECT * FROM siswa LIMIT 1');
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row) {
            echo "✓ Sample siswa data found: " . json_encode($row) . "\n";
        }
    }
    
    // Test login function exists
    if (function_exists('attemptPortalLogin')) {
        echo "✓ attemptPortalLogin function available\n";
    } else {
        echo "✗ attemptPortalLogin function NOT found\n";
    }
    
    $conn->close();
    echo "✓ Connection closed\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nNow testing login.php rendering...\n";
ob_start();
include 'login.php';
$output = ob_get_clean();
echo "Output length: " . strlen($output) . " bytes\n";
echo "First 200 chars: " . substr($output, 0, 200) . "\n";

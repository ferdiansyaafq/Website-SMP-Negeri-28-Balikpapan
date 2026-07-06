<?php
require_once '../includes/admin_auth.php';
requireAdminLogin();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $conn = getConnection();

    // Get total students
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM siswa");
    $stmt->execute();
    $totalSiswa = $stmt->get_result()->fetch_assoc()['total'];

    // Get total teachers
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM guru");
    $stmt->execute();
    $totalGuru = $stmt->get_result()->fetch_assoc()['total'];

    // Get total classes
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM kaih_kelas");
    $stmt->execute();
    $totalKelas = $stmt->get_result()->fetch_assoc()['total'];

    // Get total users
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    $totalUsers = $stmt->get_result()->fetch_assoc()['total'];

    echo json_encode([
        'success' => true,
        'totalSiswa' => $totalSiswa,
        'totalGuru' => $totalGuru,
        'totalKelas' => $totalKelas,
        'totalUsers' => $totalUsers
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
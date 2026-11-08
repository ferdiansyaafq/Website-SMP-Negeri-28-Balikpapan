<?php
// aplikasi/admin/hapus_kelas.php
session_start();
require_once '../../config/database.php';

// Cek hak akses admin
$isAdminSession = (isset($_SESSION['user_id']) && (string)($_SESSION['role'] ?? $_SESSION['portal_role'] ?? '') === 'admin')
    || (isset($_SESSION['admin_id']) && (string)($_SESSION['admin_role'] ?? '') === 'admin');
if (!$isAdminSession) {
    header('Location: ../../login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['flash_msg'] = 'ID kelas tidak valid.';
    $_SESSION['flash_type'] = 'error';
    header('Location: kelas.php');
    exit;
}

try {
    // 1. Ambil data kelas
    $stmt = $pdo->prepare("SELECT id, nama_kelas FROM kaih_kelas WHERE id = ?");
    $stmt->execute([$id]);
    $kelas = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$kelas) {
        $_SESSION['flash_msg'] = 'Data kelas tidak ditemukan.';
        $_SESSION['flash_type'] = 'error';
        header('Location: kelas.php');
        exit;
    }

    $nama_kelas = $kelas['nama_kelas'];

    // Cegah penghapusan kelas dummy bawaan sistem
    if ($nama_kelas === 'Belum Ada Kelas') {
        $_SESSION['flash_msg'] = 'Kelas sistem "Belum Ada Kelas" tidak boleh dihapus.';
        $_SESSION['flash_type'] = 'error';
        header('Location: kelas.php');
        exit;
    }

    // 2. Cek apakah ada siswa yang terdaftar di kelas ini
    $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE kelas = ?");
    $stmtCek->execute([$nama_kelas]);
    $jumlah_siswa = (int)$stmtCek->fetchColumn();

    if ($jumlah_siswa > 0) {
        $_SESSION['flash_msg'] = "Tidak dapat menghapus {$nama_kelas} karena masih terdapat {$jumlah_siswa} siswa terdaftar. Pindahkan atau hapus data siswa terlebih dahulu.";
        $_SESSION['flash_type'] = 'error';
        header('Location: kelas.php');
        exit;
    }

    $pdo->beginTransaction();

    // 3. Lepaskan relasi wali kelas pada tabel guru jika ada
    $stmtGuru = $pdo->prepare("UPDATE guru SET kelas = NULL WHERE kelas = ?");
    $stmtGuru->execute([$nama_kelas]);

    // 4. Hapus kelas dari kaih_kelas
    $stmtDel = $pdo->prepare("DELETE FROM kaih_kelas WHERE id = ?");
    $stmtDel->execute([$id]);

    $pdo->commit();

    $_SESSION['flash_msg'] = "{$nama_kelas} berhasil dihapus.";
    $_SESSION['flash_type'] = 'success';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_msg'] = 'Gagal menghapus kelas: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'error';
}

header('Location: kelas.php');
exit;

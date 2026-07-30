<?php
// aplikasi/admin/tambah_kelas.php
session_start();
require_once '../../config/database.php';

// Cek hak akses admin
if (!isset($_SESSION['user_id']) && (string)($_SESSION['portal_role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Proses Simpan Data jika form di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelas = trim($_POST['nama_kelas']);

    if (!empty($nama_kelas)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO kaih_kelas (nama_kelas) VALUES (:nama_kelas)");
            $stmt->execute([':nama_kelas' => $nama_kelas]);
            
            // Redirect kembali ke halaman kelas setelah berhasil
            header('Location: kelas.php');
            exit;
        } catch (PDOException $e) {
            $error_msg = "Gagal menambah kelas (mungkin nama kelas sudah ada): " . $e->getMessage();
        }
    } else {
        $error_msg = "Nama kelas tidak boleh kosong!";
    }
}

// Memuat Header UI
require_once '../includes/header-kaih.php';
?>

<style>
    body { background-color: #f8fafc; }
    .form-container {
        max-width: 600px;
        margin: 40px auto;
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
    }
    .form-container h2 {
        margin-top: 0;
        color: #1e293b;
        margin-bottom: 24px;
    }
    .form-group { margin-bottom: 20px; }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #475569;
    }
    .form-group input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        box-sizing: border-box;
    }
    .btn-submit {
        background: #0ea5e9;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-batal {
        background: #f1f5f9;
        color: #475569;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        margin-left: 10px;
        display: inline-block;
    }
    .alert {
        padding: 12px;
        background-color: #fef2f2;
        color: #991b1b;
        border-radius: 6px;
        margin-bottom: 20px;
        border: 1px solid #f87171;
    }
</style>

<div class="form-container">
    <h2>➕ Tambah Data Kelas Baru</h2>
    
    <?php if (!empty($error_msg)): ?>
        <div class="alert"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="nama_kelas">Nama Kelas (Contoh: 7E, 9A)</label>
            <input type="text" id="nama_kelas" name="nama_kelas" placeholder="Masukkan nama kelas..." required>
        </div>
        
        <div>
            <button type="submit" class="btn-submit">Simpan Kelas</button>
            <a href="kelas.php" class="btn-batal">Batal</a>
        </div>
    </form>
</div>  
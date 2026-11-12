<?php
session_start();
require_once '../../config/database.php';

$isAdminSession = (isset($_SESSION['user_id']) && (string)($_SESSION['role'] ?? $_SESSION['portal_role'] ?? '') === 'admin')
    || (isset($_SESSION['admin_id']) && (string)($_SESSION['admin_role'] ?? '') === 'admin');
if (!$isAdminSession) {
    header('Location: ../../login.php');
    exit;
}

$id_kelas = $_GET['id'] ?? null;

if (!$id_kelas) {
    header('Location: kelas.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM kaih_kelas WHERE id = :id");
$stmt->execute([':id' => $id_kelas]);
$kelas = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kelas) {
    die("Data kelas tidak ditemukan.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelas_baru = trim($_POST['nama_kelas']);

    if (!empty($nama_kelas_baru)) {
        if (!preg_match('/^kelas\s+/i', $nama_kelas_baru)) {
            $nama_kelas_baru = 'Kelas ' . strtoupper(trim($nama_kelas_baru));
        } else {
            $nama_kelas_baru = preg_replace_callback('/^kelas\s+(.*)$/i', function($m) {
                return 'Kelas ' . strtoupper(trim($m[1]));
            }, $nama_kelas_baru);
        }
        $tingkat = preg_match('/\d+/', $nama_kelas_baru, $m) ? $m[0] : null;
        $nama_lama = $kelas['nama_kelas'];
        try {
            $pdo->beginTransaction();
            $update_stmt = $pdo->prepare("UPDATE kaih_kelas SET nama_kelas = :nama_kelas, tingkat = :tingkat, updated_at = NOW() WHERE id = :id");
            $update_stmt->execute([
                ':nama_kelas' => $nama_kelas_baru,
                ':tingkat' => $tingkat,
                ':id' => $id_kelas
            ]);
            
            if ($nama_lama !== $nama_kelas_baru) {
                $stmtS = $pdo->prepare("UPDATE siswa SET kelas = ? WHERE kelas = ?");
                $stmtS->execute([$nama_kelas_baru, $nama_lama]);

                $stmtG = $pdo->prepare("UPDATE guru SET kelas = ? WHERE kelas = ?");
                $stmtG->execute([$nama_kelas_baru, $nama_lama]);
            }
            $pdo->commit();
            $_SESSION['flash_msg'] = "Kelas {$nama_kelas_baru} berhasil diperbarui!";
            $_SESSION['flash_type'] = 'success';
            header('Location: kelas.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = "Gagal memperbarui data: " . $e->getMessage();
        }
    } else {
        $error_msg = "Nama kelas tidak boleh kosong!";
    }
}

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
    <h2>✏️ Edit Data Kelas</h2>
    
    <?php if (!empty($error_msg)): ?>
        <div class="alert"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="nama_kelas">Nama Kelas</label>
            <input type="text" id="nama_kelas" name="nama_kelas" value="<?php echo htmlspecialchars($kelas['nama_kelas']); ?>" required>
        </div>
        
        <div>
            <button type="submit" class="btn-submit">Simpan Perubahan</button>
            <a href="kelas.php" class="btn-batal">Batal</a>
        </div>
    </form>
</div>
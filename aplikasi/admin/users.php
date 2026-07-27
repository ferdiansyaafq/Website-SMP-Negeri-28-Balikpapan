<?php
// aplikasi/admin/users.php
session_start();
require_once '../../config/database.php';

// Cek hak akses admin
if (!isset($_SESSION['user_id']) && (string)($_SESSION['portal_role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$flash = '';
$flashType = 'success';

// Proses Aksi (Reset Password & Hapus User Manual)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Aksi Reset Password Satuan
    if ($action === 'reset_password') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                // Password default: 123456
                $new_password = password_hash('123456', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_password, $id]);
                $flash = "Password berhasil di-reset menjadi: 123456";
            } catch (Exception $e) {
                $flash = "Gagal me-reset password.";
                $flashType = "error";
            }
        }
    } 
    // Aksi Reset Password Massal Berdasarkan Role
    elseif ($action === 'reset_massal') {
        $target_role = $_POST['target_role'] ?? '';
        if (in_array($target_role, ['siswa', 'guru', 'orang_tua'])) {
            try {
                $new_password = password_hash('123456', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE role = ?");
                $stmt->execute([$new_password, $target_role]);
                $affected = $stmt->rowCount();
                $flash = "Berhasil me-reset password $affected akun $target_role menjadi: 123456";
            } catch (Exception $e) {
                $flash = "Terjadi kesalahan saat reset massal.";
                $flashType = "error";
            }
        } else {
            $flash = "Role tidak valid untuk reset massal.";
            $flashType = "error";
        }
    }
}

// Fitur Pencarian & Filter Role
$search = trim($_GET['q'] ?? '');
$filter_role = trim($_GET['role'] ?? '');

// Smart Query: Gabungkan (JOIN) dengan tabel siswa dan guru agar tahu pemilik akun
$query = "
    SELECT u.*, 
           s.nama_siswa, 
           g.nama_guru 
    FROM users u
    LEFT JOIN siswa s ON u.siswa_id = s.id
    LEFT JOIN guru g ON u.guru_id = g.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $query .= " AND (u.username LIKE ? OR s.nama_siswa LIKE ? OR g.nama_guru LIKE ?)";
    array_push($params, "%$search%", "%$search%", "%$search%");
}

if ($filter_role !== '') {
    $query .= " AND u.role = ?";
    $params[] = $filter_role;
}

$query .= " ORDER BY u.role ASC, u.username ASC";

$stmtUsers = $pdo->prepare($query);
$stmtUsers->execute($params);
$daftar_users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// Header UI
require_once '../includes/header-kaih.php';
?>

<div class="card" style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); margin: 20px;">
    <?php if ($flash !== ''): ?>
        <div style="padding: 10px 15px; margin-bottom: 15px; border-radius: 8px; font-size: 14px; font-weight: 600; background: <?php echo $flashType === 'error' ? '#fee2e2; color: #991b1b;' : '#dcfce7; color: #166534;'; ?>">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h3 style="margin: 0;">🔐 Manajemen Akun & Password</h3>
        
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- Form Pencarian & Filter -->
            <form method="GET" action="" style="display: flex; gap: 5px;">
                <select name="role" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; background: white;">
                    <option value="">Semua Role</option>
                    <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="guru" <?php echo $filter_role === 'guru' ? 'selected' : ''; ?>>Guru</option>
                    <option value="siswa" <?php echo $filter_role === 'siswa' ? 'selected' : ''; ?>>Siswa</option>
                    <option value="orang_tua" <?php echo $filter_role === 'orang_tua' ? 'selected' : ''; ?>>Orang Tua</option>
                </select>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari Username/Nama..." style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none;">
                <button type="submit" style="padding: 8px 12px; background: #64748b; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Filter</button>
            </form>

            <button onclick="openModalMassal()" style="padding: 8px 16px; background: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                ⚠️ Reset Password Massal
            </button>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="background: #f8fafc; text-align: left;">
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Username</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Role</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Pemilik / Keterangan</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_users)): ?>
                    <tr>
                        <td colspan="4" style="padding: 20px; text-align: center; color: #94a3b8;">Tidak ada akun ditemukan</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftar_users as $u): ?>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;"><b><?php echo htmlspecialchars($u['username']); ?></b></td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <?php 
                                    $bg = '#e2e8f0'; $col = '#475569';
                                    if($u['role'] === 'admin') { $bg = '#fee2e2'; $col = '#b91c1c'; }
                                    if($u['role'] === 'guru') { $bg = '#f3e8ff'; $col = '#7e22ce'; }
                                    if($u['role'] === 'siswa') { $bg = '#e0f2fe'; $col = '#0284c7'; }
                                    if($u['role'] === 'orang_tua') { $bg = '#dcfce7'; $col = '#15803d'; }
                                ?>
                                <span style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: capitalize;">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $u['role'])); ?>
                                </span>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <?php 
                                    if ($u['role'] === 'siswa') echo htmlspecialchars($u['nama_siswa'] ?? 'Data Terhapus');
                                    elseif ($u['role'] === 'guru') echo htmlspecialchars($u['nama_guru'] ?? 'Data Terhapus');
                                    elseif ($u['role'] === 'orang_tua') echo "Orang Tua dari: " . htmlspecialchars($u['nama_siswa'] ?? 'Data Terhapus');
                                    else echo "Administrator System";
                                ?>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <form method="POST" action="" onsubmit="return confirm('Reset password akun <?php echo htmlspecialchars($u['username']); ?> menjadi: 123456?');" style="margin: 0;">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                    <button type="submit" style="padding: 6px 12px; background: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                                        Reset Password
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Reset Massal -->
<div id="modalMassal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 100%; max-width: 400px; padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 18px; color: #1e293b;">⚠️ Reset Password Massal</h3>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.5;">
            Fitur ini akan mengubah <b>seluruh password</b> pada role yang dipilih menjadi <b>123456</b>. Gunakan dengan sangat hati-hati!
        </p>
        
        <form method="POST" action="" onsubmit="return confirm('PERINGATAN! Anda yakin ingin mereset SEMUA password pada role yang dipilih?');">
            <input type="hidden" name="action" value="reset_massal">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">Pilih Role Target</label>
                <select name="target_role" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: white;">
                    <option value="">-- Pilih Role --</option>
                    <option value="siswa">Semua Siswa</option>
                    <option value="orang_tua">Semua Orang Tua</option>
                    <option value="guru">Semua Guru</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex: 1; padding: 10px; background: #ef4444; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Eksekusi Reset!</button>
                <button type="button" onclick="closeModalMassal()" style="flex: 1; padding: 10px; background: #e2e8f0; color: #334155; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModalMassal() { document.getElementById('modalMassal').style.display = 'flex'; }
    function closeModalMassal() { document.getElementById('modalMassal').style.display = 'none'; }
</script>
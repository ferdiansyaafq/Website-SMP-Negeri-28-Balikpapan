<?php
// aplikasi/admin/users.php
session_start();
require_once '../../config/database.php';

// Cek hak akses admin
$isAdminSession = (isset($_SESSION['user_id']) && (string)($_SESSION['role'] ?? $_SESSION['portal_role'] ?? '') === 'admin')
    || (isset($_SESSION['admin_id']) && (string)($_SESSION['admin_role'] ?? '') === 'admin');
if (!$isAdminSession) {
    header('Location: ../../login.php');
    exit;
}

$flash = '';
$flashType = 'success';

// Handle Reset Password (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'reset_single') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmtU = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
            $stmtU->execute([$id]);
            $u = $stmtU->fetch(PDO::FETCH_ASSOC);

            if ($u) {
                // Tentukan default password berdasarkan role
                $newPass = '123456';
                if ($u['role'] === 'kepsek') {
                    $newPass = '12345678';
                } elseif ($u['role'] === 'siswa' || $u['role'] === 'orang_tua') {
                    $newPass = 'smpn28bpp';
                } elseif ($u['role'] === 'admin') {
                    $newPass = 'admin123';
                }

                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmtUpd = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmtUpd->execute([$hash, $id]);

                $flash = "Password untuk akun <strong>" . htmlspecialchars($u['username']) . "</strong> berhasil direset menjadi: <code>" . htmlspecialchars($newPass) . "</code>";
                $flashType = 'success';
            } else {
                $flash = "Akun tidak ditemukan.";
                $flashType = 'error';
            }
        }
    } elseif ($action === 'reset_massal') {
        $targetRole = trim($_POST['target_role'] ?? '');
        if (in_array($targetRole, ['guru', 'siswa', 'orang_tua'], true)) {
            $newPass = ($targetRole === 'guru') ? '123456' : 'smpn28bpp';
            $hash = password_hash($newPass, PASSWORD_DEFAULT);

            $stmtUpd = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE role = ?");
            $stmtUpd->execute([$hash, $targetRole]);
            $count = $stmtUpd->rowCount();

            $labelRole = ($targetRole === 'guru') ? 'Guru' : (($targetRole === 'siswa') ? 'Siswa' : 'Orang Tua');
            $flash = "Berhasil mereset password {$count} akun <strong>{$labelRole}</strong> menjadi default: <code>{$newPass}</code>";
            $flashType = 'success';
        } else {
            $flash = "Pilih kategori role yang valid untuk reset massal.";
            $flashType = 'error';
        }
    }
}

// Ambil parameter filter dari form pencarian
$search = trim($_GET['q'] ?? '');
$filterRole = trim($_GET['role'] ?? '');

// Query ke database dengan relasi nama pemilik akun (Guru / Siswa / Admin)
$query = "
    SELECT u.id, u.username, u.role, u.password,
           COALESCE(g.nama_guru, s.nama_siswa, CASE WHEN u.role = 'admin' THEN 'Administrator Sistem' ELSE '-' END) AS keterangan
    FROM users u
    LEFT JOIN guru g ON (u.guru_id = g.id OR u.username = g.nip)
    LEFT JOIN siswa s ON (u.siswa_id = s.id OR u.username = s.nisn)
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $query .= " AND (u.username LIKE ? OR g.nama_guru LIKE ? OR s.nama_siswa LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filterRole !== '') {
    $query .= " AND u.role = ?";
    $params[] = $filterRole;
}

$query .= " ORDER BY u.role ASC, u.username ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$daftar_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Memuat Header UI
require_once '../includes/header-kaih.php';
?>

<!-- Styling Khusus Tabel Akun -->
<style>
    .table-container {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        padding: 24px;
        margin: 20px;
    }
    .table-kaih {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    .table-kaih th {
        text-align: left;
        padding: 12px 15px;
        border-bottom: 2px solid #e2e8f0;
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .table-kaih td {
        padding: 12px 15px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
    }
    .table-kaih tr:hover td {
        background-color: #f8fafc;
    }
    .badge-role {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-align: center;
    }
    .role-admin { background-color: #fee2e2; color: #dc2626; }
    .role-guru { background-color: #e0f2fe; color: #0284c7; }
    .role-siswa { background-color: #fef3c7; color: #d97706; }
    .role-orangtua { background-color: #f3e8ff; color: #9333ea; }
    .role-kepsek { background-color: #dcfce7; color: #166534; }
    
    .btn-reset {
        background-color: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-reset:hover {
        background-color: #fee2e2;
        border-color: #f87171;
    }
    .btn-reset-massal {
        background: #ef4444;
        color: white;
        border: none;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-reset-massal:hover {
        background: #dc2626;
    }
    .alert-box {
        padding: 12px 18px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
    }
    .alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
    }
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    /* Modal Massal */
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .modal-content {
        background: white;
        border-radius: 12px;
        padding: 24px;
        max-width: 440px;
        width: 90%;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
</style>

<div class="table-container">
    
    <?php if ($flash !== ''): ?>
        <div class="alert-box <?php echo $flashType === 'error' ? 'alert-error' : 'alert-success'; ?>">
            <?php echo $flash; ?>
        </div>
    <?php endif; ?>

    <!-- Baris Judul & Filter Pencarian -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h3 style="margin: 0; font-size: 20px; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                🔐 Manajemen Akun & Password
            </h3>
            <p style="margin: 4px 0 0; color: #64748b; font-size: 13px;">Kelola akun login dan reset password default bagi warga sekolah.</p>
        </div>
        
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <form method="GET" action="" style="display: flex; gap: 8px;">
                <!-- Dropdown Filter Role -->
                <select name="role" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: white;">
                    <option value="">Semua Role</option>
                    <option value="admin" <?php echo $filterRole == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="guru" <?php echo $filterRole == 'guru' ? 'selected' : ''; ?>>Guru</option>
                    <option value="siswa" <?php echo $filterRole == 'siswa' ? 'selected' : ''; ?>>Siswa</option>
                    <option value="orang_tua" <?php echo $filterRole === 'orang_tua' ? 'selected' : ''; ?>>Orang Tua</option>
                    <option value="kepsek" <?php echo $filterRole == 'kepsek' ? 'selected' : ''; ?>>Kepala Sekolah</option>
                </select>
                
                <!-- Input Cari Nama/Username -->
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari Username/Nama..." style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; width: 190px;">
                
                <button type="submit" style="padding: 8px 16px; background: #0284c7; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">Cari</button>
            </form>
            
            <button type="button" class="btn-reset-massal" onclick="openModalMassal()">
                ⚡ Reset Massal
            </button>
        </div>
    </div>

    <!-- Tabel Data Akun -->
    <div style="overflow-x: auto;">
        <table class="table-kaih">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Pemilik / Keterangan</th>
                    <th>Status Password</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_users)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">Tidak ada data akun ditemukan.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($daftar_users as $u): 
                        $roleClass = 'role-siswa';
                        $roleText = strtolower($u['role']);
                        if ($roleText === 'admin') $roleClass = 'role-admin';
                        elseif ($roleText === 'guru') $roleClass = 'role-guru';
                        elseif (strpos($roleText, 'orang') !== false) $roleClass = 'role-orangtua';
                        elseif ($roleText === 'kepsek') $roleClass = 'role-kepsek';

                        // Default hint password
                        $defaultHint = '123456';
                        if ($roleText === 'kepsek') $defaultHint = '12345678';
                        elseif ($roleText === 'siswa' || $roleText === 'orang_tua') $defaultHint = 'smpn28bpp';
                        elseif ($roleText === 'admin') $defaultHint = 'admin123';
                    ?>
                    <tr>
                        <td style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($u['username']); ?></td>
                        <td>
                            <span class="badge-role <?php echo $roleClass; ?>">
                                <?php echo ucwords(htmlspecialchars(str_replace('_', ' ', $u['role']))); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($u['keterangan'] ?? '-'); ?></td>
                        
                        <!-- Kolom Status Password Aman (Bcrypt) -->
                        <td>
                            <span style="font-family: monospace; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; color: #475569; font-size: 12px; font-weight: 600;" title="Terenkripsi Bcrypt (Default: <?php echo $defaultHint; ?>)">
                                🔒 Terenkripsi (Default: <?= $defaultHint; ?>)
                            </span>
                        </td>
                        
                        <td style="text-align: center;">
                            <form method="POST" action="" onsubmit="return confirm('Reset password akun <?php echo htmlspecialchars($u['username']); ?> ke default (<?= $defaultHint; ?>)?');" style="display: inline; margin: 0;">
                                <input type="hidden" name="action" value="reset_single">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn-reset">
                                    🔑 Reset Password
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

<!-- Modal Reset Password Massal -->
<div class="modal-overlay" id="modalMassal">
    <div class="modal-content">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 18px;">⚡ Reset Password Massal</h3>
        <p style="color: #64748b; font-size: 13px; line-height: 1.5;">
            Fitur ini akan mengembalikan password seluruh akun pada kategori yang dipilih ke password standar sekolah.
        </p>

        <form method="POST" action="" onsubmit="return confirm('PENTING: Apakah Anda yakin ingin mereset password massal untuk role yang dipilih?');">
            <input type="hidden" name="action" value="reset_massal">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Pilih Role Akun:</label>
                <select name="target_role" required style="width: 100%; padding: 10px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: white;">
                    <option value="">-- Pilih Kategori --</option>
                    <option value="guru">Seluruh Guru (Default: 123456)</option>
                    <option value="siswa">Seluruh Siswa (Default: smpn28bpp)</option>
                    <option value="orang_tua">Seluruh Orang Tua (Default: smpn28bpp)</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeModalMassal()" style="padding: 8px 16px; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    Batal
                </button>
                <button type="submit" style="padding: 8px 18px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
                    Reset Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModalMassal() {
        document.getElementById('modalMassal').style.display = 'flex';
    }
    function closeModalMassal() {
        document.getElementById('modalMassal').style.display = 'none';
    }
    window.onclick = function(e) {
        const modal = document.getElementById('modalMassal');
        if (e.target === modal) {
            closeModalMassal();
        }
    };
</script>

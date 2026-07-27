<?php
// aplikasi/admin/guru.php
session_start();
require_once '../../config/database.php';

// Cek hak akses admin
if (!isset($_SESSION['user_id']) && (string)($_SESSION['portal_role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$flash = '';
$flashType = 'success';

// Proses CRUD (Tambah, Edit, Hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nip = trim($_POST['nip'] ?? '');
        $nama_guru = trim($_POST['nama_guru'] ?? '');
        $jabatan = trim($_POST['jabatan'] ?? '');

        if ($nip !== '' && $nama_guru !== '' && $jabatan !== '') {
            try {
                $pdo->beginTransaction();
                
                // 1. Masukkan data ke tabel guru
                $stmt = $pdo->prepare("INSERT INTO guru (nip, nama_guru, jabatan, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute([$nip, $nama_guru, $jabatan]);
                $guru_id = $pdo->lastInsertId();
                
                // 2. Buat akun login Guru otomatis (password default: 123456)
                $pass_guru = password_hash('123456', PASSWORD_DEFAULT);
                $stmtUsr = $pdo->prepare("INSERT INTO users (username, password, role, guru_id, created_at, updated_at) VALUES (?, ?, 'guru', ?, NOW(), NOW())");
                $stmtUsr->execute([$nip, $pass_guru, $guru_id]);
                
                $pdo->commit();
                $flash = "Data guru dan akun login berhasil ditambahkan!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = "Gagal menambah guru (NIP mungkin sudah terdaftar).";
                $flashType = "error";
            }
        } else {
            $flash = "Semua kolom wajib diisi!";
            $flashType = "error";
        }

    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nip = trim($_POST['nip'] ?? '');
        $nama_guru = trim($_POST['nama_guru'] ?? '');
        $jabatan = trim($_POST['jabatan'] ?? '');
        $nip_lama = trim($_POST['nip_lama'] ?? '');

        if ($id > 0 && $nip !== '' && $nama_guru !== '' && $jabatan !== '') {
            try {
                $pdo->beginTransaction();
                
                // 1. Update data guru
                $stmt = $pdo->prepare("UPDATE guru SET nip = ?, nama_guru = ?, jabatan = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$nip, $nama_guru, $jabatan, $id]);

                // 2. Jika NIP berubah, update username login
                if ($nip !== $nip_lama) {
                    $stmtUsr = $pdo->prepare("UPDATE users SET username = ? WHERE guru_id = ? AND role = 'guru'");
                    $stmtUsr->execute([$nip, $id]);
                }
                
                $pdo->commit();
                $flash = "Data guru berhasil diubah!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = "Gagal mengubah guru (NIP mungkin bentrok).";
                $flashType = "error";
            }
        } else {
            $flash = "Semua kolom wajib diisi!";
            $flashType = "error";
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->beginTransaction();
                // Hapus relasi akun users
                $stmtU = $pdo->prepare("DELETE FROM users WHERE guru_id = ?");
                $stmtU->execute([$id]);
                
                // Hapus data guru
                $stmtG = $pdo->prepare("DELETE FROM guru WHERE id = ?");
                $stmtG->execute([$id]);
                
                $pdo->commit();
                $flash = "Data guru berhasil dihapus!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = "Gagal menghapus guru.";
                $flashType = "error";
            }
        }
    }
}

// Fitur Pencarian & Ambil Data Guru
$search = trim($_GET['q'] ?? '');
$query = "SELECT * FROM guru";
$params = [];

if ($search !== '') {
    $query .= " WHERE nip LIKE ? OR nama_guru LIKE ? OR jabatan LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$query .= " ORDER BY nama_guru ASC";

$stmtGuru = $pdo->prepare($query);
$stmtGuru->execute($params);
$daftar_guru = $stmtGuru->fetchAll(PDO::FETCH_ASSOC);

// Header UI
require_once '../includes/header-kaih.php';
?>

<div class="card" style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); margin: 20px;">
    <?php if ($flash !== ''): ?>
        <div style="padding: 10px 15px; margin-bottom: 15px; border-radius: 8px; font-size: 14px; font-weight: 600; background: <?php echo $flashType === 'error' ? '#fee2e2; color: #991b1b;' : '#dcfce7; color: #166534;'; ?>">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
        <h3 style="margin: 0;">👨‍🏫 Data Guru</h3>
        
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- Form Pencarian -->
            <form method="GET" action="" style="display: flex; gap: 5px;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari NIP / Nama / Jabatan..." style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none;">
                <button type="submit" style="padding: 8px 12px; background: #64748b; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Cari</button>
            </form>

            <button onclick="openModalTambah()" style="padding: 8px 16px; background: #0284c7; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                + Tambah Guru
            </button>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="background: #f8fafc; text-align: left;">
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">NIP</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Nama</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Jabatan</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_guru)): ?>
                    <tr>
                        <td colspan="4" style="padding: 20px; text-align: center; color: #94a3b8;">Tidak ada data guru ditemukan</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftar_guru as $g): ?>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;"><b><?php echo htmlspecialchars($g['nip']); ?></b></td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;"><?php echo htmlspecialchars($g['nama_guru']); ?></td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <span style="background: #f3e8ff; color: #7e22ce; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                    <?php echo htmlspecialchars($g['jabatan']); ?>
                                </span>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <div style="display: flex; gap: 5px;">
                                    <button type="button" onclick="openModalEdit(<?php echo htmlspecialchars(json_encode([
                                        'id' => $g['id'],
                                        'nip' => $g['nip'],
                                        'nama_guru' => $g['nama_guru'],
                                        'jabatan' => $g['jabatan']
                                    ])); ?>)" style="padding: 6px 12px; background: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                                        Edit
                                    </button>
                                    <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus guru ini beserta akun login-nya?');" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$g['id']; ?>">
                                        <button type="submit" style="padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Tambah Guru -->
<div id="modalTambah" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 100%; max-width: 400px; padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 18px; color: #1e293b;">Tambah Guru Baru</h3>
        <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">Akun login guru akan dibuat otomatis dengan password default: <b>123456</b></p>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">NIP / NUPTK</label>
                <input type="text" name="nip" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;" placeholder="Contoh: 198001012005011001">
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">Nama Guru</label>
                <input type="text" name="nama_guru" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;" placeholder="Contoh: Siti Aminah, S.Pd">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">Jabatan / Guru Mapel</label>
                <input type="text" name="jabatan" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;" placeholder="Contoh: Guru Matematika">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex: 1; padding: 10px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Simpan</button>
                <button type="button" onclick="closeModalTambah()" style="flex: 1; padding: 10px; background: #e2e8f0; color: #334155; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Form Edit Guru -->
<div id="modalEdit" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 100%; max-width: 400px; padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 18px; color: #1e293b;">Edit Data Guru</h3>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="nip_lama" id="edit_nip_lama">
            
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">NIP / NUPTK</label>
                <input type="text" name="nip" id="edit_nip" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">Nama Guru</label>
                <input type="text" name="nama_guru" id="edit_nama" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">Jabatan / Guru Mapel</label>
                <input type="text" name="jabatan" id="edit_jabatan" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex: 1; padding: 10px; background: #f59e0b; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Update</button>
                <button type="button" onclick="closeModalEdit()" style="flex: 1; padding: 10px; background: #e2e8f0; color: #334155; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModalTambah() { document.getElementById('modalTambah').style.display = 'flex'; }
    function closeModalTambah() { document.getElementById('modalTambah').style.display = 'none'; }

    function openModalEdit(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_nip_lama').value = data.nip;
        document.getElementById('edit_nip').value = data.nip;
        document.getElementById('edit_nama').value = data.nama_guru;
        document.getElementById('edit_jabatan').value = data.jabatan;
        document.getElementById('modalEdit').style.display = 'flex';
    }
    function closeModalEdit() { document.getElementById('modalEdit').style.display = 'none'; }
</script>
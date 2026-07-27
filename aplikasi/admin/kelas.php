<?php
// aplikasi/admin/kelas.php
session_start();
require_once '../../config/database.php';

// Cek hak akses admin
if (!isset($_SESSION['user_id']) && (string)($_SESSION['portal_role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$flash = '';
$flashType = 'success';

// Proses CRUD (Tambah, Edit, Hapus Kelas)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nama_kelas = trim($_POST['nama_kelas'] ?? '');
        if ($nama_kelas !== '') {
            try {
                $stmt = $pdo->prepare("INSERT INTO kaih_kelas (nama_kelas) VALUES (?)");
                $stmt->execute([$nama_kelas]);
                $flash = "Kelas berhasil ditambahkan!";
            } catch (Exception $e) {
                $flash = "Gagal menambah kelas (Nama kelas mungkin sudah ada).";
                $flashType = "error";
            }
        } else {
            $flash = "Nama kelas wajib diisi!";
            $flashType = "error";
        }

    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama_kelas = trim($_POST['nama_kelas'] ?? '');
        $nama_kelas_lama = trim($_POST['nama_kelas_lama'] ?? '');

        if ($id > 0 && $nama_kelas !== '') {
            try {
                $pdo->beginTransaction();
                
                // 1. Update nama kelas di master
                $stmt = $pdo->prepare("UPDATE kaih_kelas SET nama_kelas = ? WHERE id = ?");
                $stmt->execute([$nama_kelas, $id]);
                
                // 2. Cascade: update nama kelas pada semua siswa yang memakai kelas lama
                if ($nama_kelas !== $nama_kelas_lama) {
                    $stmtSiswa = $pdo->prepare("UPDATE siswa SET kelas = ?, updated_at = NOW() WHERE kelas = ?");
                    $stmtSiswa->execute([$nama_kelas, $nama_kelas_lama]);
                }

                $pdo->commit();
                $flash = "Data kelas berhasil diubah!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = "Gagal mengubah kelas.";
                $flashType = "error";
            }
        } else {
            $flash = "Nama kelas wajib diisi!";
            $flashType = "error";
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM kaih_kelas WHERE id = ?");
                $stmt->execute([$id]);
                $flash = "Kelas berhasil dihapus!";
            } catch (Exception $e) {
                $flash = "Gagal menghapus kelas. Pastikan tidak ada data terkait.";
                $flashType = "error";
            }
        }
    }
}

// Fitur Pencarian & Ambil data kelas beserta hitungan siswa
$search = trim($_GET['q'] ?? '');
$query = "SELECT k.id, k.nama_kelas, COUNT(s.id) as jumlah_siswa 
          FROM kaih_kelas k 
          LEFT JOIN siswa s ON k.nama_kelas = s.kelas ";
$params = [];

if ($search !== '') {
    $query .= " WHERE k.nama_kelas LIKE ? ";
    $params[] = "%$search%";
}
$query .= " GROUP BY k.id, k.nama_kelas ORDER BY k.nama_kelas ASC";

$stmtKelas = $pdo->prepare($query);
$stmtKelas->execute($params);
$daftar_kelas = $stmtKelas->fetchAll(PDO::FETCH_ASSOC);

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
        <h3 style="margin: 0;">🏫 Data Kelas</h3>
        
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- Form Pencarian -->
            <form method="GET" action="" style="display: flex; gap: 5px;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari Kelas..." style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none;">
                <button type="submit" style="padding: 8px 12px; background: #64748b; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Cari</button>
            </form>

            <button onclick="openModalTambah()" style="padding: 8px 16px; background: #0284c7; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                + Tambah Kelas
            </button>
        </div>
    </div>

    <!-- Tampilan Card Grid Kelas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <?php if (empty($daftar_kelas)): ?>
            <div style="grid-column: 1 / -1; padding: 20px; text-align: center; color: #94a3b8;">
                Tidak ada data kelas ditemukan.
            </div>
        <?php else: ?>
            <?php foreach ($daftar_kelas as $k): ?>
                <div style="background: #f0f9ff; padding: 20px; border-radius: 12px; border: 1px solid #e0f2fe; text-align: center; display: flex; flex-direction: column; justify-content: center; position: relative;">
                    
                    <div style="font-size: 18px; font-weight: 700; color: #0284c7; margin-bottom: 5px;">
                        Kelas <?php echo htmlspecialchars($k['nama_kelas']); ?>
                    </div>
                    <div style="color: #64748b; font-size: 14px; margin-bottom: 15px;">
                        <?php echo (int)$k['jumlah_siswa']; ?> Siswa
                    </div>
                    
                    <!-- Grup Tombol Aksi -->
                    <div style="display: flex; gap: 8px; justify-content: center; margin-top: auto;">
                        <button type="button" onclick="openModalEdit(<?php echo htmlspecialchars(json_encode([
                            'id' => $k['id'],
                            'nama_kelas' => $k['nama_kelas']
                        ])); ?>)" style="padding: 4px 10px; background: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                            Edit
                        </button>

                        <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus kelas ini? Pastikan tidak ada siswa yang masih terdaftar di kelas ini.');" style="margin: 0;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int)$k['id']; ?>">
                            <button type="submit" style="padding: 4px 10px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Form Tambah Kelas -->
<div id="modalTambah" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 100%; max-width: 400px; padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 18px; color: #1e293b;">Tambah Kelas Baru</h3>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">Nama Kelas</label>
                <input type="text" name="nama_kelas" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;" placeholder="Contoh: 7A, 8B, 9C">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex: 1; padding: 10px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Simpan</button>
                <button type="button" onclick="closeModalTambah()" style="flex: 1; padding: 10px; background: #e2e8f0; color: #334155; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Form Edit Kelas -->
<div id="modalEdit" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 100%; max-width: 400px; padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 18px; color: #1e293b;">Edit Data Kelas</h3>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="nama_kelas_lama" id="edit_nama_kelas_lama">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">Nama Kelas</label>
                <input type="text" name="nama_kelas" id="edit_nama_kelas" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
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
        document.getElementById('edit_nama_kelas_lama').value = data.nama_kelas;
        document.getElementById('edit_nama_kelas').value = data.nama_kelas;
        document.getElementById('modalEdit').style.display = 'flex';
    }
    function closeModalEdit() { document.getElementById('modalEdit').style.display = 'none'; }
</script>
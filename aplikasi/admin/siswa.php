<?php
// aplikasi/admin/siswa.php
session_start();
require_once '../../config/database.php';

// Cek hak akses admin
if (!isset($_SESSION['user_id']) && (string)($_SESSION['portal_role'] ?? '') !== 'Admin') {
    header('Location: ../../login.php');
    exit;
}

$flash = '';
$flashType = 'success';

// Proses CRUD (Tambah, Edit, Hapus, & Import)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nisn = trim($_POST['nisn'] ?? '');
        $nama_siswa = trim($_POST['nama_siswa'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');

        if ($nisn !== '' && $nama_siswa !== '' && $kelas !== '') {
            try {
                $pdo->beginTransaction();

                // 1. Masukkan data ke tabel siswa
                $stmt = $pdo->prepare("INSERT INTO siswa (nisn, nama_siswa, kelas, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute([$nisn, $nama_siswa, $kelas]);
                $siswa_id = $pdo->lastInsertId();

                // 2. Buat akun login Siswa otomatis (password default: 123456)
                $pass_siswa = password_hash('123456', PASSWORD_DEFAULT);
                $stmtUsr = $pdo->prepare("INSERT INTO users (username, password, role, siswa_id, created_at, updated_at) VALUES (?, ?, 'siswa', ?, NOW(), NOW())");
                $stmtUsr->execute([$nisn, $pass_siswa, $siswa_id]);

                // 3. Buat akun login Orang Tua otomatis (username: ORT + NISN)
                $username_ortu = 'ORT' . $nisn;
                $pass_ortu = password_hash('123456', PASSWORD_DEFAULT);
                $stmtOrtu = $pdo->prepare("INSERT INTO users (username, password, role, siswa_id, created_at, updated_at) VALUES (?, ?, 'orang_tua', ?, NOW(), NOW())");
                $stmtOrtu->execute([$username_ortu, $pass_ortu, $siswa_id]);

                $pdo->commit();
                $flash = "Data siswa dan akun login berhasil ditambahkan!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = "Gagal menambah siswa (NISN mungkin sudah terdaftar).";
                $flashType = "error";
            }
        } else {
            $flash = "Semua kolom wajib diisi!";
            $flashType = "error";
        }

    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nisn = trim($_POST['nisn'] ?? '');
        $nama_siswa = trim($_POST['nama_siswa'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $nisn_lama = trim($_POST['nisn_lama'] ?? '');

        if ($id > 0 && $nisn !== '' && $nama_siswa !== '' && $kelas !== '') {
            try {
                $pdo->beginTransaction();

                // 1. Update tabel siswa
                $stmt = $pdo->prepare("UPDATE siswa SET nisn = ?, nama_siswa = ?, kelas = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$nisn, $nama_siswa, $kelas, $id]);

                // 2. Jika NISN berubah, update juga username di tabel users
                if ($nisn !== $nisn_lama) {
                    $stmtUsr = $pdo->prepare("UPDATE users SET username = ? WHERE siswa_id = ? AND role = 'siswa'");
                    $stmtUsr->execute([$nisn, $id]);

                    $username_ortu = 'ORT' . $nisn;
                    $stmtOrtu = $pdo->prepare("UPDATE users SET username = ? WHERE siswa_id = ? AND role = 'orang_tua'");
                    $stmtOrtu->execute([$username_ortu, $id]);
                }

                $pdo->commit();
                $flash = "Data siswa berhasil diubah!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = "Gagal mengubah siswa (NISN mungkin bentrok).";
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
                // Hapus relasi akun users terlebih dahulu
                $stmtU = $pdo->prepare("DELETE FROM users WHERE siswa_id = ?");
                $stmtU->execute([$id]);

                // Hapus data siswa
                $stmtS = $pdo->prepare("DELETE FROM siswa WHERE id = ?");
                $stmtS->execute([$id]);

                $pdo->commit();
                $flash = "Data siswa berhasil dihapus!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = "Gagal menghapus siswa.";
                $flashType = "error";
            }
        }
    } elseif ($action === 'import') {
        // --- LOGIKA IMPORT CSV ---
        if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['file_csv']['tmp_name'];
            $sukses = 0;
            $gagal = 0;

            if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
                fgetcsv($handle, 1000, ","); // Lewati Header

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $nisn = trim($data[0] ?? '');
                    $nama_siswa = trim($data[1] ?? '');
                    $kelas = trim($data[2] ?? '');

                    if ($nisn !== '' && $nama_siswa !== '' && $kelas !== '') {
                        try {
                            $pdo->beginTransaction();
                            $stmt = $pdo->prepare("INSERT INTO siswa (nisn, nama_siswa, kelas, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                            $stmt->execute([$nisn, $nama_siswa, $kelas]);
                            $siswa_id = $pdo->lastInsertId();

                            $pass_siswa = password_hash('123456', PASSWORD_DEFAULT);
                            $stmtUsr = $pdo->prepare("INSERT INTO users (username, password, role, siswa_id, created_at, updated_at) VALUES (?, ?, 'siswa', ?, NOW(), NOW())");
                            $stmtUsr->execute([$nisn, $pass_siswa, $siswa_id]);

                            $username_ortu = 'ORT' . $nisn;
                            $pass_ortu = password_hash('123456', PASSWORD_DEFAULT);
                            $stmtOrtu = $pdo->prepare("INSERT INTO users (username, password, role, siswa_id, created_at, updated_at) VALUES (?, ?, 'orang_tua', ?, NOW(), NOW())");
                            $stmtOrtu->execute([$username_ortu, $pass_ortu, $siswa_id]);

                            $pdo->commit();
                            $sukses++;
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $gagal++;
                        }
                    }
                }
                fclose($handle);
                $flash = "Proses Import Selesai! Berhasil: $sukses siswa, Gagal (Duplikat/Error): $gagal data.";
                $flashType = $gagal > 0 ? "error" : "success";
            } else {
                $flash = "Gagal membaca file CSV!";
                $flashType = "error";
            }
        } else {
            $flash = "Silakan pilih file CSV yang valid.";
            $flashType = "error";
        }
    }
}

// Fitur Pencarian & Ambil Data Siswa
$search = trim($_GET['q'] ?? '');
$query = "SELECT * FROM siswa";
$params = [];

if ($search !== '') {
    $query .= " WHERE nisn LIKE ? OR nama_siswa LIKE ? OR kelas LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$query .= " ORDER BY nama_siswa ASC";

$stmtSiswa = $pdo->prepare($query);
$stmtSiswa->execute($params);
$daftar_siswa = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

// Tarik Dinamis Data Kelas dari Database
$daftar_kelas = [];
$stmtKelas = $pdo->query("SELECT id, nama_kelas FROM kaih_kelas ORDER BY nama_kelas ASC");
if ($stmtKelas) {
    $daftar_kelas = $stmtKelas->fetchAll(PDO::FETCH_ASSOC);
}

// Header bawaan tampilan UI asli
require_once '../includes/header-kaih.php';
?>

<div class="card" style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); margin: 20px;">
    <?php if ($flash !== ''): ?>
        <div style="padding: 10px 15px; margin-bottom: 15px; border-radius: 8px; font-size: 14px; font-weight: 600; background: <?php echo $flashType === 'error' ? '#fee2e2; color: #991b1b;' : '#dcfce7; color: #166534;'; ?>">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
        <h3 style="margin: 0;">📋 Data Siswa</h3>
        
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- Form Pencarian -->
            <form method="GET" action="" style="display: flex; gap: 5px;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari NISN / Nama..." style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none;">
                <button type="submit" style="padding: 8px 12px; background: #64748b; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Cari</button>
            </form>

            <button onclick="openModalImport()" style="padding: 8px 16px; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                📥 Import CSV
            </button>
            <button onclick="openModalTambah()" style="padding: 8px 16px; background: #0284c7; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                + Tambah Siswa
            </button>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="background: #f8fafc; text-align: left;">
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">NISN</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Nama</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Kelas</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_siswa)): ?>
                    <tr>
                        <td colspan="4" style="padding: 20px; text-align: center; color: #94a3b8;">Tidak ada data siswa ditemukan</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftar_siswa as $s): ?>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;"><b><?php echo htmlspecialchars($s['nisn']); ?></b></td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;"><?php echo htmlspecialchars($s['nama_siswa']); ?></td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <span style="background: #e0f2fe; color: #0284c7; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                    <?php echo htmlspecialchars($s['kelas']); ?>
                                </span>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <div style="display: flex; gap: 5px;">
                                    <button type="button" onclick="openModalEdit(<?php echo htmlspecialchars(json_encode([
                                        'id' => $s['id'],
                                        'nisn' => $s['nisn'],
                                        'nama_siswa' => $s['nama_siswa'],
                                        'kelas' => $s['kelas']
                                    ])); ?>)" style="padding: 6px 12px; background: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                                        Edit
                                    </button>
                                    <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus siswa ini beserta akun login-ya?');" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
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

<!-- Modal Form Import Siswa -->
<div id="modalImport" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 100%; max-width: 400px; padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 18px; color: #1e293b;">Import Data Siswa (CSV)</h3>
        <div style="font-size: 12px; color: #64748b; margin-bottom: 15px; line-height: 1.5;">
            Buat file Excel dengan urutan kolom berikut, lalu <b>Save As -> CSV (Comma delimited)</b>:<br>
            <b>Kolom A:</b> NISN<br>
            <b>Kolom B:</b> Nama Siswa<br>
            <b>Kolom C:</b> Kelas (Misal: 7A)<br>
            <i style="color: #ef4444;">*Baris pertama (judul kolom) akan dilewati sistem otomatis.</i>
        </div>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import">
            <div style="margin-bottom: 20px;">
                <input type="file" name="file_csv" accept=".csv" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex: 1; padding: 10px; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Mulai Import</button>
                <button type="button" onclick="closeModalImport()" style="flex: 1; padding: 10px; background: #e2e8f0; color: #334155; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Form Tambah Siswa -->
<div id="modalTambah" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 100%; max-width: 400px; padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 18px; color: #1e293b;">Tambah Siswa Baru</h3>
        <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">Akun login otomatis dibuat dengan password: <b>123456</b></p>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">NISN</label>
                <input type="text" name="nisn" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;" placeholder="Contoh: 1234567890">
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">Nama Siswa</label>
                <input type="text" name="nama_siswa" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;" placeholder="Contoh: Budi Santoso">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">Kelas</label>
                <select name="kelas" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: white;">
                    <option value="">-- Pilih Kelas --</option>
                    <?php if(empty($daftar_kelas)): ?>
                        <option value="" disabled>Belum ada kelas di database!</option>
                    <?php else: ?>
                        <?php foreach ($daftar_kelas as $k): ?>
                            <option value="<?php echo htmlspecialchars($k['nama_kelas']); ?>"><?php echo htmlspecialchars($k['nama_kelas']); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex: 1; padding: 10px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Simpan</button>
                <button type="button" onclick="closeModalTambah()" style="flex: 1; padding: 10px; background: #e2e8f0; color: #334155; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Form Edit Siswa -->
<div id="modalEdit" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 100%; max-width: 400px; padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 18px; color: #1e293b;">Edit Data Siswa</h3>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="nisn_lama" id="edit_nisn_lama">
            
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">NISN</label>
                <input type="text" name="nisn" id="edit_nisn" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">Nama Siswa</label>
                <input type="text" name="nama_siswa" id="edit_nama" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">Kelas</label>
                <select name="kelas" id="edit_kelas" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: white;">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($daftar_kelas as $k): ?>
                        <option value="<?php echo htmlspecialchars($k['nama_kelas']); ?>"><?php echo htmlspecialchars($k['nama_kelas']); ?></option>
                    <?php endforeach; ?>
                </select>
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
    
    function openModalImport() { document.getElementById('modalImport').style.display = 'flex'; }
    function closeModalImport() { document.getElementById('modalImport').style.display = 'none'; }

    function openModalEdit(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_nisn_lama').value = data.nisn;
        document.getElementById('edit_nisn').value = data.nisn;
        document.getElementById('edit_nama').value = data.nama_siswa;
        document.getElementById('edit_kelas').value = data.kelas;
        document.getElementById('modalEdit').style.display = 'flex';
    }
    function closeModalEdit() { document.getElementById('modalEdit').style.display = 'none'; }
</script>

<?php // Tutup atau muat footer jika diperlukan di akhir file ?>
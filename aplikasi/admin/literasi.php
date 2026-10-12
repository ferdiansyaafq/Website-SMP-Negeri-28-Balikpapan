<?php
// aplikasi/admin/literasi.php
session_start();
require_once '../../config/database.php';

// Cek hak akses admin
if (!isset($_SESSION['user_id']) && (string)($_SESSION['portal_role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$flash = '';
$flashType = 'success';
$uploadDir = '../../assets/img/literasi/';

// Buat folder upload otomatis jika belum ada
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Proses CRUD (Tambah, Edit, Hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $judul        = trim($_POST['judul'] ?? '');
        $penulis      = trim($_POST['penulis'] ?? '');
        $penerbit     = trim($_POST['penerbit'] ?? '');
        $tahun_terbit = trim($_POST['tahun_terbit'] ?? '');
        $kategori     = trim($_POST['kategori'] ?? '');
        $sinopsis     = trim($_POST['sinopsis'] ?? '');
        $link_baca    = trim($_POST['link_baca'] ?? '');
        $status       = trim($_POST['status'] ?? 'terbit');
        $cover        = '';

        // Handle upload cover gambar
        if (!empty($_FILES['cover']['name'])) {
            $coverName = time() . '_' . basename($_FILES['cover']['name']);
            if (move_uploaded_file($_FILES['cover']['tmp_name'], $uploadDir . $coverName)) {
                // Simpan path relatif yang sesuai dengan data database Irsyad sebelumnya
                $cover = 'assets/img/literasi/' . $coverName;
            }
        }

        if ($judul !== '' && $penulis !== '') {
            try {
                $stmt = $pdo->prepare("INSERT INTO jendela_literasi (judul, penulis, penerbit, tahun_terbit, kategori, sinopsis, cover, link_baca, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$judul, $penulis, $penerbit, $tahun_terbit, $kategori, $sinopsis, $cover, $link_baca, $status]);
                $flash = "Buku literasi berhasil ditambahkan!";
            } catch (Exception $e) {
                $flash = "Gagal menambah data: " . $e->getMessage();
                $flashType = "error";
            }
        } else {
            $flash = "Judul dan Penulis wajib diisi!";
            $flashType = "error";
        }
    } elseif ($action === 'edit') {
        $id           = (int)($_POST['id'] ?? 0);
        $judul        = trim($_POST['judul'] ?? '');
        $penulis      = trim($_POST['penulis'] ?? '');
        $penerbit     = trim($_POST['penerbit'] ?? '');
        $tahun_terbit = trim($_POST['tahun_terbit'] ?? '');
        $kategori     = trim($_POST['kategori'] ?? '');
        $sinopsis     = trim($_POST['sinopsis'] ?? '');
        $link_baca    = trim($_POST['link_baca'] ?? '');
        $status       = trim($_POST['status'] ?? 'terbit');
        $cover_lama   = $_POST['cover_lama'] ?? '';
        $cover        = $cover_lama;

        if (!empty($_FILES['cover']['name'])) {
            $coverName = time() . '_' . basename($_FILES['cover']['name']);
            if (move_uploaded_file($_FILES['cover']['tmp_name'], $uploadDir . $coverName)) {
                $cover = 'assets/img/literasi/' . $coverName;
                // Hapus file cover lama jika ada di server
                if (!empty($cover_lama) && file_exists('../../' . $cover_lama)) {
                    @unlink('../../' . $cover_lama);
                }
            }
        }

        if ($id > 0 && $judul !== '') {
            try {
                $stmt = $pdo->prepare("UPDATE jendela_literasi SET judul = ?, penulis = ?, penerbit = ?, tahun_terbit = ?, kategori = ?, sinopsis = ?, cover = ?, link_baca = ?, status = ? WHERE id = ?");
                $stmt->execute([$judul, $penulis, $penerbit, $tahun_terbit, $kategori, $sinopsis, $cover, $link_baca, $status, $id]);
                $flash = "Data buku berhasil diperbarui!";
            } catch (Exception $e) {
                $flash = "Gagal memperbarui data: " . $e->getMessage();
                $flashType = "error";
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                // Ambil info cover untuk dihapus filenya
                $stmtCek = $pdo->prepare("SELECT cover FROM jendela_literasi WHERE id = ?");
                $stmtCek->execute([$id]);
                $row = $stmtCek->fetch();

                if ($row && !empty($row['cover']) && file_exists('../../' . $row['cover'])) {
                    @unlink('../../' . $row['cover']);
                }

                $stmt = $pdo->prepare("DELETE FROM jendela_literasi WHERE id = ?");
                $stmt->execute([$id]);
                $flash = "Buku berhasil dihapus dari sistem!";
            } catch (Exception $e) {
                $flash = "Gagal menghapus data.";
                $flashType = "error";
            }
        }
    }
}

// Ambil Semua Data Buku Literasi
$stmtData = $pdo->query("SELECT * FROM jendela_literasi ORDER BY id DESC");
$daftar_buku = $stmtData->fetchAll(PDO::FETCH_ASSOC);

// Memuat Header UI Admin KAIH
require_once '../includes/header-kaih.php';
?>

<div class="card" style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); margin: 20px;">
    <?php if ($flash !== ''): ?>
        <div style="padding: 10px 15px; margin-bottom: 15px; border-radius: 8px; font-size: 14px; font-weight: 600; background: <?php echo $flashType === 'error' ? '#fee2e2; color: #991b1b;' : '#dcfce7; color: #166534;'; ?>">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
        <h3 style="margin: 0;">📚 Manajemen Jendela Literasi</h3>
        <button onclick="openModalTambah()" style="padding: 8px 16px; background: #0284c7; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
            + Tambah Buku
        </button>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="background: #f8fafc; text-align: left;">
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Cover</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Judul & Penulis</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Kategori</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Penerbit / Tahun</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Status</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_buku)): ?>
                    <tr>
                        <td colspan="6" style="padding: 20px; text-align: center; color: #94a3b8;">Belum ada data buku literasi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftar_buku as $b): ?>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <?php if (!empty($b['cover'])): ?>
                                    <img src="../../<?php echo htmlspecialchars($b['cover']); ?>" alt="Cover" style="width: 50px; height: 70px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 70px; background: #e2e8f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #64748b;">No Img</div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <b><?php echo htmlspecialchars($b['judul']); ?></b><br>
                                <span style="font-size: 12px; color: #64748b;">Oleh: <?php echo htmlspecialchars($b['penulis']); ?></span>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <span style="background: #e0f2fe; color: #0284c7; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;"><?php echo htmlspecialchars($b['kategori']); ?></span>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #475569;">
                                <?php echo htmlspecialchars($b['penerbit']); ?> (<?php echo htmlspecialchars($b['tahun_terbit']); ?>)
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <span style="font-size: 12px; font-weight: bold; color: <?php echo $b['status'] === 'terbit' ? '#10b981' : '#f59e0b'; ?>;">
                                    <?php echo ucfirst(htmlspecialchars($b['status'])); ?>
                                </span>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                <div style="display: flex; gap: 5px;">
                                    <button type="button" onclick='openModalEdit(<?php echo json_encode($b, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' style="padding: 6px 12px; background: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">Edit</button>
                                    
                                    <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus buku ini?');" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                        <button type="submit" style="padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">Hapus</button>
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

<!-- Modal Tambah Buku -->
<div id="modalTambah" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;">
    <div style="background: white; width: 100%; max-width: 550px; padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin: auto;">
        <h3 style="margin-top: 0; margin-bottom: 15px;">Tambah Buku Literasi Baru</h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Judul Buku *</label>
                <input type="text" name="judul" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Penulis *</label>
                    <input type="text" name="penulis" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Penerbit</label>
                    <input type="text" name="penerbit" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Tahun Terbit</label>
                    <input type="number" name="tahun_terbit" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Kategori</label>
                    <input type="text" name="kategori" placeholder="Contoh: Novel / Fiksi" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Sinopsis</label>
                <textarea name="sinopsis" rows="4" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;"></textarea>
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Link Baca (URL)</label>
                <input type="url" name="link_baca" placeholder="https://..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Status</label>
                    <select name="status" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: white;">
                        <option value="terbit">Terbit</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Cover Buku</label>
                    <input type="file" name="cover" accept="image/*" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" style="flex: 1; padding: 10px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Simpan Buku</button>
                <button type="button" onclick="closeModalTambah()" style="flex: 1; padding: 10px; background: #e2e8f0; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Buku -->
<div id="modalEdit" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;">
    <div style="background: white; width: 100%; max-width: 550px; padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin: auto;">
        <h3 style="margin-top: 0; margin-bottom: 15px;">Edit Buku Literasi</h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="cover_lama" id="edit_cover_lama">
            
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Judul Buku *</label>
                <input type="text" name="judul" id="edit_judul" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Penulis *</label>
                    <input type="text" name="penulis" id="edit_penulis" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Penerbit</label>
                    <input type="text" name="penerbit" id="edit_penerbit" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Tahun Terbit</label>
                    <input type="number" name="tahun_terbit" id="edit_tahun_terbit" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Kategori</label>
                    <input type="text" name="kategori" id="edit_kategori" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Sinopsis</label>
                <textarea name="sinopsis" id="edit_sinopsis" rows="4" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;"></textarea>
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Link Baca (URL)</label>
                <input type="url" name="link_baca" id="edit_link_baca" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Status</label>
                    <select name="status" id="edit_status" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: white;">
                        <option value="terbit">Terbit</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Ganti Cover (Opsional)</label>
                    <input type="file" name="cover" accept="image/*" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" style="flex: 1; padding: 10px; background: #f59e0b; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Update Buku</button>
                <button type="button" onclick="closeModalEdit()" style="flex: 1; padding: 10px; background: #e2e8f0; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModalTambah() { document.getElementById('modalTambah').style.display = 'flex'; }
    function closeModalTambah() { document.getElementById('modalTambah').style.display = 'none'; }
    
    function openModalEdit(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_judul').value = data.judul;
        document.getElementById('edit_penulis').value = data.penulis;
        document.getElementById('edit_penerbit').value = data.penerbit;
        document.getElementById('edit_tahun_terbit').value = data.tahun_terbit;
        document.getElementById('edit_kategori').value = data.kategori;
        document.getElementById('edit_sinopsis').value = data.sinopsis;
        document.getElementById('edit_link_baca').value = data.link_baca;
        document.getElementById('edit_status').value = data.status;
        document.getElementById('edit_cover_lama').value = data.cover;
        document.getElementById('modalEdit').style.display = 'flex';
    }
    function closeModalEdit() { document.getElementById('modalEdit').style.display = 'none'; }
</script>
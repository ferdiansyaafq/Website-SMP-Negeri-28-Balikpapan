<?php
// aplikasi/admin/kelas.php
session_start();
require_once '../../config/database.php';

// Cek hak akses admin
if (!isset($_SESSION['user_id']) && (string)($_SESSION['portal_role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Tangkap pencarian jika ada
$search = $_GET['search'] ?? '';

// --- Query Mengambil Data Kelas beserta Wali Kelasnya ---
$query = "
    SELECT 
        k.id, 
        k.nama_kelas, 
        (SELECT COUNT(*) FROM siswa s WHERE s.kelas = k.nama_kelas) AS jumlah_siswa,
        (SELECT nama_guru FROM guru g WHERE g.kelas = k.nama_kelas LIMIT 1) AS nama_wali
    FROM kaih_kelas k
";

if (!empty($search)) {
    $query .= " WHERE k.nama_kelas LIKE :search";
}

$query .= " ORDER BY k.nama_kelas ASC";

$stmt = $pdo->prepare($query);
if (!empty($search)) {
    $stmt->bindValue(':search', '%' . $search . '%');
}
$stmt->execute();
$kelas_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Memuat Header UI
require_once '../includes/header-kaih.php';
?>

<style>
    body { background-color: #f8fafc; }

    .kelas-container {
        padding: 24px;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        box-sizing: border-box;
    }

    /* Header Section - Menyamping ujung ke ujung */
    .kelas-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        width: 100%;
    }

    .kelas-header h2 {
        margin: 0;
        font-size: 24px;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .search-box {
        display: flex;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        overflow: hidden;
        background: #fff;
    }

    .search-box input {
        padding: 8px 12px;
        border: none;
        outline: none;
        width: 220px;
    }

    .search-box button {
        background: #64748b;
        color: white;
        border: none;
        padding: 8px 16px;
        cursor: pointer;
    }

    .btn-tambah {
        background: #0ea5e9;
        color: white;
        text-decoration: none;
        padding: 9px 16px;
        border-radius: 6px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    /* Grid Layout untuk Card */
    .kelas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }

    /* Desain Card */
    .kelas-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 24px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        border: 1px solid #f1f5f9;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
        position: relative;
    }

    .kelas-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
        border-color: #bae6fd;
    }

    .kelas-card h3 {
        margin: 0 0 15px 0;
        font-size: 24px;
        color: #0f172a;
        font-weight: 700;
    }

    .wali-kelas {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: #f8fafc;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 13px;
        color: #64748b;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        width: fit-content;
        margin-left: auto;
        margin-right: auto;
    }

    .badge-siswa {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #e0f2fe;
        color: #0284c7;
        padding: 8px 20px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 24px;
    }

    .badge-siswa strong {
        margin-left: 4px;
        font-weight: 700;
    }

    /* Tombol Aksi */
    .card-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
    }

    .btn-edit, .btn-hapus {
        flex: 1;
        padding: 10px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
        border: none;
        cursor: pointer;
        transition: background 0.2s;
        position: relative; 
        z-index: 2; 
    }

    .btn-edit { background: #fef08a; color: #854d0e; }
    .btn-edit:hover { background: #fde047; }

    .btn-hapus { background: #fecdd3; color: #9f1239; }
    .btn-hapus:hover { background: #fda4af; }
</style>

<div class="kelas-container">
    
    <!-- Header: Judul & Aksi Menyamping -->
    <div class="kelas-header">
        <h2>🏫 Data Kelas</h2>
        
        <div class="header-actions">
            <form method="GET" action="" class="search-box">
                <input type="text" name="search" placeholder="Cari Kelas..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Cari</button>
            </form>
            <a href="tambah_kelas.php" class="btn-tambah">+ Tambah Kelas</a>
        </div>
    </div>

    <!-- Grid Layout Kelas -->
    <div class="kelas-grid">
        <?php if (count($kelas_data) > 0): ?>
            <?php foreach ($kelas_data as $row): ?>
                
                <!-- Card Utama: Menggunakan data-href sebagai penyimpan link -->
                <div class="kelas-card" data-href="siswa.php?q=<?php echo urlencode($row['nama_kelas']); ?>">
                    
                    <h3>Kelas <?php echo htmlspecialchars($row['nama_kelas']); ?></h3>
                    
                    <div class="wali-kelas">
                        👤 <?php echo !empty($row['nama_wali']) ? htmlspecialchars($row['nama_wali']) : 'Belum Ada Wali Kelas'; ?>
                    </div>
                    
                    <div class="badge-siswa" title="Lihat daftar siswa kelas <?php echo htmlspecialchars($row['nama_kelas']); ?>">
                        Jumlah: <strong><?php echo $row['jumlah_siswa']; ?> Siswa</strong>
                    </div>

                    <div class="card-actions">
                        <a href="edit_kelas.php?id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                        <a href="hapus_kelas.php?id=<?php echo $row['id']; ?>" class="btn-hapus" onclick="return confirm('Yakin ingin menghapus kelas ini?');">Hapus</a>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #64748b; background: #fff; border-radius: 12px;">
                Belum ada data kelas yang ditambahkan atau tidak ada hasil pencarian.
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- SCRIPT UNTUK MENGATUR KLIK CARD -->
<script>
    document.querySelectorAll('.kelas-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // Cek apakah elemen yang diklik adalah link (tag <a>) atau berada di dalam tag <a>
            if (!e.target.closest('a')) {
                window.location.href = this.getAttribute('data-href');
            }
        });
    });
</script>
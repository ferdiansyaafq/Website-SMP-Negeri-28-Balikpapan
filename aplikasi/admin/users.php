<?php
// aplikasi/admin/users.php
session_start();
require_once '../../config/database.php';

// Cek hak akses admin
if (!isset($_SESSION['user_id']) && (string)($_SESSION['portal_role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Ambil parameter filter dari form pencarian
$search = trim($_GET['q'] ?? '');
$filterRole = trim($_GET['role'] ?? '');

// Query ke database (Kolom keterangan dihapus sementara)
$query = "SELECT id, username, role, password FROM users WHERE 1=1";
$params = [];

// Jika ada pencarian nama/keterangan
if ($search !== '') {
    // Pencarian hanya berdasarkan username saja untuk sementara
    $query .= " AND (username LIKE ?)";
    $params[] = "%$search%";
}

// Jika ada filter role (jabatan)
if ($filterRole !== '') {
    $query .= " AND role = ?";
    $params[] = $filterRole;
}

$query .= " ORDER BY role ASC, username ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$daftar_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Memuat Header UI (yang sudah memiliki fitur sidebar toggle garis 3)
require_once '../includes/header-kaih.php';
?>

<!-- Styling Tambahan Khusus untuk Tabel Akun -->
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
        color: #475569;
        font-weight: 700;
    }
    .table-kaih td {
        padding: 12px 15px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
        font-weight: 600;
    }
    .table-kaih tr:hover td {
        background-color: #f8fafc;
    }
    
    /* Warna Warni Badge Role */
    .badge-role {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
    }
    .role-admin { background: #fee2e2; color: #ef4444; }
    .role-guru { background: #f3e8ff; color: #a855f7; }
    .role-siswa { background: #e0f2fe; color: #0284c7; }
    .role-orangtua { background: #dcfce7; color: #166534; }
    
    /* Tombol Aksi */
    .btn-reset {
        background: #f59e0b;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-reset:hover {
        background: #d97706;
    }
    .btn-reset-massal {
        background: #ef4444;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }
</style>

<div class="table-container">
    
    <!-- Baris Judul & Filter Pencarian -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <h3 style="margin: 0; font-size: 20px; color: #1e293b; display: flex; align-items: center; gap: 10px;">
            🔐 Manajemen Akun & Password
        </h3>
        
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <form method="GET" action="" style="display: flex; gap: 8px;">
                <!-- Dropdown Filter Role -->
                <select name="role" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: white;">
                    <option value="">Semua Role</option>
                    <option value="admin" <?php echo $filterRole == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="guru" <?php echo $filterRole == 'guru' ? 'selected' : ''; ?>>Guru</option>
                    <option value="siswa" <?php echo $filterRole == 'siswa' ? 'selected' : ''; ?>>Siswa</option>
                    <option value="orang tua" <?php echo $filterRole == 'orang tua' ? 'selected' : ''; ?>>Orang Tua</option>
                </select>
                
                <!-- Input Cari Nama/Username -->
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari Username/Nama..." style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; width: 200px;">
                
                <button type="submit" style="padding: 8px 16px; background: #64748b; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">Filter</button>
            </form>
            
            <button class="btn-reset-massal">
                ⚠ Reset Password Massal
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
                    <th>Password</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_users)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">Tidak ada data akun ditemukan.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($daftar_users as $u): 
                        // Menentukan warna badge berdasarkan tipe role
                        $roleClass = 'role-siswa';
                        $roleText = strtolower($u['role']);
                        if ($roleText === 'admin') $roleClass = 'role-admin';
                        elseif ($roleText === 'guru') $roleClass = 'role-guru';
                        elseif (strpos($roleText, 'orang') !== false) $roleClass = 'role-orangtua';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td>
                            <span class="badge-role <?php echo $roleClass; ?>">
                                <?php echo ucwords(htmlspecialchars($u['role'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($u['keterangan'] ?? '-'); ?></td>
                        
                        <!-- Kolom Password dengan Fitur Toggle/Intip -->
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span id="pw-text-<?php echo $u['id']; ?>" style="font-family: monospace; letter-spacing: 2px; color: #64748b; font-size: 14px;">
                                    ••••••••
                                </span>
                                
                                <button type="button" 
                                        data-id="<?php echo $u['id']; ?>" 
                                        data-pw="<?php echo htmlspecialchars($u['password']); ?>"
                                        onclick="togglePassword(this)" 
                                        title="Tampilkan Password"
                                        style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 2px; display: flex; align-items: center; transition: 0.2s;">
                                    <!-- Icon Mata (Default tertutup/disamarkan) -->
                                    <svg id="pw-icon-<?php echo $u['id']; ?>" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                        
                        <td>
                            <!-- Tombol Reset Belum Ada Logika Backend-nya, Baru Tampil Saja -->
                            <button type="button" class="btn-reset" onclick="return confirm('Yakin ingin mereset password untuk akun <?php echo htmlspecialchars($u['username']); ?>?');">
                                Reset Password
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Script JavaScript untuk Fitur Show/Hide Password -->
<script>
    function togglePassword(btn) {
        // Ambil data dari atribut HTML tombol yang diklik
        const id = btn.getAttribute('data-id');
        const realPw = btn.getAttribute('data-pw');
        
        // Ambil elemen teks dan icon berdasarkan ID unik
        const pwSpan = document.getElementById('pw-text-' + id);
        const iconSvg = document.getElementById('pw-icon-' + id);
        
        // Cek apakah password sedang disamarkan
        if (pwSpan.innerText === '••••••••') {
            // Tampilkan Password Asli
            pwSpan.innerText = realPw;
            pwSpan.style.letterSpacing = 'normal'; // Kembalikan spasi huruf menjadi normal
            pwSpan.style.color = '#ef4444'; // Ubah warna jadi merah agar lebih berhati-hati
            pwSpan.style.fontWeight = '700';
            btn.style.color = '#ef4444';
            
            // Ubah Icon SVG menjadi "Mata Dicoret" (Eye-slash)
            iconSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
        } else {
            // Sembunyikan Password Kembali
            pwSpan.innerText = '••••••••';
            pwSpan.style.letterSpacing = '2px';
            pwSpan.style.color = '#64748b';
            pwSpan.style.fontWeight = 'normal';
            btn.style.color = '#94a3b8';
            
            // Ubah Icon SVG menjadi "Mata Terbuka"
            iconSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
        }
    }
</script>
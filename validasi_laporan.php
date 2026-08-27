<?php
// Pastikan session sudah dimulai jika menggunakan fitur login
// session_start();

// 1. Konfigurasi Koneksi PDO (Sesuaikan dengan file koneksimu jika sudah dipisah)
$host = 'localhost';
$dbname = 'kaih';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi Gagal: " . $e->getMessage());
}

// 2. Query untuk mengambil laporan yang BELUM divalidasi (Antrean Pending)
$sql = "SELECT lh.*, s.nama_siswa 
        FROM laporan_harian lh 
        JOIN siswa s ON lh.siswa_id = s.id 
        WHERE lh.guru_validated_at IS NULL 
        ORDER BY lh.tanggal DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$laporan_pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Query untuk mengambil data yang SUDAH divalidasi (Riwayat Validasi)
$sql_riwayat = "SELECT lh.*, s.nama_siswa 
                FROM laporan_harian lh 
                JOIN siswa s ON lh.siswa_id = s.id 
                WHERE lh.guru_validated_at IS NOT NULL 
                ORDER BY lh.tanggal DESC";
$stmt_riwayat = $pdo->query($sql_riwayat);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Laporan KAIH - SMPN 28 Balikpapan</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; }
        th { background-color: #f4f4f4; }
        .catatan { font-size: 0.85em; color: #555; }
        .btn-setuju { background-color: #28a745; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; display: inline-block; margin-bottom: 5px; }
        .btn-tolak { background-color: #dc3545; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; display: inline-block; }
        .btn-batal { background-color: #ffc107; color: black; padding: 6px 12px; text-decoration: none; border-radius: 4px; display: inline-block; margin-bottom: 5px; font-weight: bold;}
    </style>
</head>
<body>

    <!-- BAGIAN 1: DAFTAR ANTREAN PENDING -->
    <h2>Daftar Laporan Harian (Menunggu Validasi)</h2>
    <p>Berikut adalah laporan siswa yang membutuhkan persetujuan.</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Siswa</th>
                <th>Tanggal</th>
                <th>Bangun</th>
                <th>Ibadah</th>
                <th>Olahraga</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($laporan_pending) > 0): ?>
                <?php $no = 1; foreach ($laporan_pending as $row): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['nama_siswa']); ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                        <td><?= htmlspecialchars($row['bangun']); ?></td>
                        
                        <!-- Menampilkan data utama beserta catatannya -->
                        <td>
                            <?= htmlspecialchars($row['ibadah']); ?> <br>
                            <?php if(!empty($row['ibadah_catatan'])): ?>
                                <span class="catatan">Catatan: <?= htmlspecialchars($row['ibadah_catatan']); ?></span>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <?= htmlspecialchars($row['olahraga']); ?> <br>
                            <?php if(!empty($row['olahraga_jenis'])): ?>
                                <span class="catatan">Jenis: <?= htmlspecialchars($row['olahraga_jenis']); ?></span>
                            <?php endif; ?>
                        </td>
                        
                        <!-- Tombol Aksi yang melempar ID laporan ke file proses -->
                        <td>
                            <a href="proses_validasi.php?aksi=setuju&id=<?= $row['id']; ?>" class="btn-setuju">Setuju</a>
                            <a href="proses_validasi.php?aksi=tolak&id=<?= $row['id']; ?>" class="btn-tolak">Tolak</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; font-style: italic;">Semua laporan sudah divalidasi.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <br><br><hr><br>

    <!-- BAGIAN 2: DAFTAR RIWAYAT SELESAI (Bisa Batal Setuju) -->
    <h2>Riwayat Validasi (Selesai)</h2>
    <p>Daftar laporan yang sudah disetujui. Anda bisa membatalkan validasi jika terjadi kesalahan.</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Siswa</th>
                <th>Tanggal</th>
                <th>Bangun</th>
                <th>Ibadah</th>
                <th>Olahraga</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no_riwayat = 1;
            if ($stmt_riwayat->rowCount() > 0): 
                while ($row_riwayat = $stmt_riwayat->fetch(PDO::FETCH_ASSOC)): 
            ?>
                <tr>
                    <td><?= $no_riwayat++; ?></td>
                    <td><?= htmlspecialchars($row_riwayat['nama_siswa']); ?></td>
                    <td><?= date('d-m-Y', strtotime($row_riwayat['tanggal'])); ?></td>
                    <td><?= htmlspecialchars($row_riwayat['bangun']); ?></td>
                    <td>
                        <?= htmlspecialchars($row_riwayat['ibadah']); ?> <br>
                        <?php if(!empty($row_riwayat['ibadah_catatan'])): ?>
                            <span class="catatan">Catatan: <?= htmlspecialchars($row_riwayat['ibadah_catatan']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($row_riwayat['olahraga']); ?> <br>
                        <?php if(!empty($row_riwayat['olahraga_jenis'])): ?>
                            <span class="catatan">Jenis: <?= htmlspecialchars($row_riwayat['olahraga_jenis']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Tombol Batal Setuju -->
                        <a href="proses_validasi.php?aksi=batal&id=<?= $row_riwayat['id']; ?>" onclick="return confirm('Yakin ingin membatalkan validasi laporan ini?');" class="btn-batal">Batal Setuju</a>
                    </td>
                </tr>
            <?php 
                endwhile; 
            else: 
            ?>
                <tr>
                    <td colspan="7" style="text-align: center; font-style: italic;">Belum ada laporan yang divalidasi.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
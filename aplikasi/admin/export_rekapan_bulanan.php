<?php
// aplikasi/admin/export_rekapan_bulanan.php
session_start();
$isAdminSession = (isset($_SESSION['user_id']) && (string)($_SESSION['role'] ?? $_SESSION['portal_role'] ?? '') === 'admin')
    || (isset($_SESSION['admin_id']) && (string)($_SESSION['admin_role'] ?? '') === 'admin');
if (!$isAdminSession) {
    header('Location: ../../login.php');
    exit;
}

// 1. Panggil koneksi database
require_once '../../config/database.php'; 

// 2. Tangkap parameter bulan dan tahun dari URL
$bulan_pilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilih = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// 3. Set header untuk mendownload file Excel
$nama_file = "Rekap_Kegiatan_Bulanan_Siswa_" . $bulan_pilih . "_" . $tahun_pilih . ".xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$nama_file");
?>

<!-- 4. Buat struktur tabel HTML -->
<table border="1">
    <thead>
        <tr>
            <th style="background-color: #f2f2f2;">No</th>
            <th style="background-color: #f2f2f2;">Tanggal</th>
            <th style="background-color: #f2f2f2;">Nama Siswa</th>
            <th style="background-color: #f2f2f2;">Kelas</th>
            <th style="background-color: #f2f2f2;">Skor Kegiatan (Max 7)</th>
            <th style="background-color: #f2f2f2;">Validasi Orang Tua</th>
            <th style="background-color: #f2f2f2;">Validasi Guru</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // 5. Query Database menggunakan PDO berdasarkan tabel asli KAIH
        $query = "SELECT lh.tanggal, s.nama_siswa, s.kelas, 
                         (lh.bangun + lh.ibadah + lh.olahraga + lh.sarapan + lh.membaca + lh.membantu + lh.menabung) as total_skor,
                         lh.orang_tua_validated_at, lh.guru_validated_at
                  FROM laporan_harian lh
                  JOIN siswa s ON lh.siswa_id = s.id
                  WHERE MONTH(lh.tanggal) = :bulan AND YEAR(lh.tanggal) = :tahun
                  ORDER BY lh.tanggal ASC, s.nama_siswa ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute(['bulan' => $bulan_pilih, 'tahun' => $tahun_pilih]);
        $no = 1;

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $status_ortu = $row['orang_tua_validated_at'] ? "Sudah (" . $row['orang_tua_validated_at'] . ")" : "Belum";
                $status_guru = $row['guru_validated_at'] ? "Sudah (" . $row['guru_validated_at'] . ")" : "Belum";
                
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . $row['tanggal'] . "</td>";
                echo "<td>" . htmlspecialchars($row['nama_siswa']) . "</td>";
                echo "<td>" . htmlspecialchars($row['kelas']) . "</td>";
                echo "<td>" . $row['total_skor'] . " / 7</td>";
                echo "<td>" . $status_ortu . "</td>";
                echo "<td>" . $status_guru . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='7' style='text-align:center;'>Data tidak ditemukan untuk periode ini.</td></tr>";
        }
        ?>
    </tbody>
</table>

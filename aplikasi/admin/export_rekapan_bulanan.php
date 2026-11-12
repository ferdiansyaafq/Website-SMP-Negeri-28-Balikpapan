<?php
include '../../config/database.php'; 

$bulan_pilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilih = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

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
        $query = "SELECT s.nama_siswa, s.kelas, lh.tanggal, 
                         (lh.bangun + lh.ibadah + lh.olahraga + lh.sarapan + lh.membaca + lh.membantu + lh.menabung) as skor,
                         lh.orang_tua_validated_at, lh.guru_validated_at
                  FROM laporan_harian lh
                  JOIN siswa s ON lh.siswa_id = s.id
                  WHERE MONTH(lh.tanggal) = ? AND YEAR(lh.tanggal) = ?
                  ORDER BY lh.tanggal ASC, s.kelas ASC, s.nama_siswa ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$bulan_pilih, $tahun_pilih]);
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $no = 1;
        if (count($result) > 0) {
            foreach ($result as $row) {
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . date('d-m-Y', strtotime($row['tanggal'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['nama_siswa']) . "</td>";
                echo "<td>" . htmlspecialchars($row['kelas']) . "</td>";
                echo "<td style='text-align: center;'>" . $row['skor'] . " / 7</td>";
                
                $val_ortu = !empty($row['orang_tua_validated_at']) ? 'Sudah Val (' . date('H:i', strtotime($row['orang_tua_validated_at'])) . ')' : 'Belum';
                $val_guru = !empty($row['guru_validated_at']) ? 'Sudah Val (' . date('H:i', strtotime($row['guru_validated_at'])) . ')' : 'Belum';
                
                echo "<td>" . $val_ortu . "</td>";
                echo "<td>" . $val_guru . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='7' style='text-align: center;'>Tidak ada data laporan di bulan dan tahun tersebut.</td></tr>";
        }
        ?>
    </tbody>
</table>
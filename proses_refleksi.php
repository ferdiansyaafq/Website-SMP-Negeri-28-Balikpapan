<?php
include 'config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nama_siswa     = trim($_POST['nama_siswa'] ?? '');
    $kelas          = trim($_POST['kelas'] ?? '');
    $mata_pelajaran = trim($_POST['mata_pelajaran'] ?? '');
    $isi_refleksi   = trim($_POST['isi_refleksi'] ?? '');

    try {
        $cleanKelas = trim(preg_replace('/^kelas\s+/i', '', $kelas));
        $fullKelas = 'Kelas ' . $cleanKelas;

        $studentStmt = $pdo->prepare(
            'SELECT id FROM siswa 
             WHERE LOWER(TRIM(nama_siswa)) = LOWER(TRIM(:nama_siswa)) 
               AND (LOWER(TRIM(kelas)) = LOWER(TRIM(:fullKelas)) 
                 OR LOWER(TRIM(kelas)) = LOWER(TRIM(:cleanKelas))
                 OR LOWER(TRIM(REPLACE(kelas, "Kelas ", ""))) = LOWER(TRIM(:cleanKelas))) 
             LIMIT 1'
        );
        $studentStmt->execute([
            ':nama_siswa' => $nama_siswa,
            ':fullKelas'  => $fullKelas,
            ':cleanKelas' => $cleanKelas
        ]);
        $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            $fallbackStmt = $pdo->prepare('SELECT id FROM siswa WHERE LOWER(TRIM(nama_siswa)) = LOWER(TRIM(:nama_siswa)) LIMIT 1');
            $fallbackStmt->execute([':nama_siswa' => $nama_siswa]);
            $student = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$student) {
            echo "<script>
                    alert('Data siswa atas nama \"".addslashes($nama_siswa)."\" di kelas \"".addslashes($kelas)."\" tidak ditemukan. Silakan periksa kembali ejaan nama Anda.');
                    window.history.back();
                  </script>";
            exit();
        }

        $sql = "INSERT INTO refleksi (
                    siswa_id, tanggal, semester, tahun_ajaran,
                    pelajaran_favorit, pengalaman_berkesan
                ) VALUES (
                    :siswa_id, CURDATE(), 'Ganjil', :tahun_ajaran,
                    :pelajaran_favorit, :pengalaman_berkesan
                )";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':siswa_id'            => $student['id'],
            ':tahun_ajaran'        => date('Y') . '/' . (date('Y') + 1),
            ':pelajaran_favorit'   => $mata_pelajaran,
            ':pengalaman_berkesan' => $isi_refleksi
        ]);

        echo "<script>
                alert('Refleksi pembelajaran berhasil dikirim! Tetap semangat belajarnya.');
                window.location.href = 'refleksi.php';
              </script>";
        exit();

    } catch (Throwable $e) {
        echo "<script>
                alert('Terjadi kesalahan saat menyimpan data refleksi. Silakan coba kembali.');
                window.history.back();
              </script>";
        exit();
    }

} else {
    header("Location: refleksi.php");
    exit();
}
?>

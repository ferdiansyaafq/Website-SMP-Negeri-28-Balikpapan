<?php
// 1. Panggil file database yang ada di dalam folder config
include 'config/database.php';

// 2. Cek apakah ada data yang dikirim dari form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 3. Tangkap data dari form
    $nama_siswa     = trim($_POST['nama_siswa'] ?? '');
    $kelas          = trim($_POST['kelas'] ?? '');
    $mata_pelajaran = trim($_POST['mata_pelajaran'] ?? '');
    $isi_refleksi   = trim($_POST['isi_refleksi'] ?? '');

    try {
        $studentStmt = $pdo->prepare(
            'SELECT id FROM siswa WHERE nama_siswa = :nama_siswa AND kelas = :kelas LIMIT 1'
        );
        $studentStmt->execute([':nama_siswa' => $nama_siswa, ':kelas' => $kelas]);
        $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            throw new RuntimeException('Data siswa tidak ditemukan. Pastikan nama dan kelas sesuai data sekolah.');
        }

        $sql = "INSERT INTO refleksi (
                    siswa_id, tanggal, semester, tahun_ajaran,
                    pelajaran_favorit, pengalaman_berkesan
                ) VALUES (
                    :siswa_id, CURDATE(), 'Ganjil', :tahun_ajaran,
                    :pelajaran_favorit, :pengalaman_berkesan
                )";
        
        $stmt = $pdo->prepare($sql);
        
        // 5. Eksekusi query
        $stmt->execute([
            ':siswa_id'            => $student['id'],
            ':tahun_ajaran'       => date('Y') . '/' . (date('Y') + 1),
            ':pelajaran_favorit'  => $mata_pelajaran,
            ':pengalaman_berkesan' => $isi_refleksi
        ]);

        // 6. Jika berhasil, beri notifikasi dan kembalikan ke halaman refleksi
        echo "<script>
                alert('Refleksi pembelajaran berhasil dikirim! Tetap semangat belajarnya.');
                window.location.href = 'refleksi.php';
              </script>";
        exit();

    } catch (Throwable $e) {
        die("Gagal menyimpan data refleksi: " . $e->getMessage());
    }

} else {
    // Keamanan: Tolak akses langsung ke file ini
    header("Location: refleksi.php");
    exit();
}
?>
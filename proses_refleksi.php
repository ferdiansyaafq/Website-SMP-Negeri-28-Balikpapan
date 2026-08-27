<?php
// 1. Panggil file database yang ada di dalam folder config
include 'config/database.php';

// 2. Cek apakah ada data yang dikirim dari form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 3. Tangkap data dari form
    $nama_siswa     = $_POST['nama_siswa'];
    $kelas          = $_POST['kelas'];
    $mata_pelajaran = $_POST['mata_pelajaran'];
    $isi_refleksi   = $_POST['isi_refleksi'];

    try {
        // 4. Siapkan query PDO untuk mencegah SQL Injection
        $sql = "INSERT INTO refleksi_siswa (nama_siswa, kelas, mata_pelajaran, isi_refleksi) 
                VALUES (:nama_siswa, :kelas, :mata_pelajaran, :isi_refleksi)";
        
        $stmt = $pdo->prepare($sql);
        
        // 5. Eksekusi query
        $stmt->execute([
            ':nama_siswa'     => $nama_siswa,
            ':kelas'          => $kelas,
            ':mata_pelajaran' => $mata_pelajaran,
            ':isi_refleksi'   => $isi_refleksi
        ]);

        // 6. Jika berhasil, beri notifikasi dan kembalikan ke halaman refleksi
        echo "<script>
                alert('Refleksi pembelajaran berhasil dikirim! Tetap semangat belajarnya.');
                window.location.href = 'refleksi.php';
              </script>";
        exit();

    } catch (PDOException $e) {
        die("Gagal menyimpan data refleksi: " . $e->getMessage());
    }

} else {
    // Keamanan: Tolak akses langsung ke file ini
    header("Location: refleksi.php");
    exit();
}
?>
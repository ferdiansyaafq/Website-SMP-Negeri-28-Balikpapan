<?php
// 1. Panggil file database.php milikmu
include 'config/database.php';

// 2. Cek apakah ada data yang dikirim dari form (metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 3. Tangkap data dari form lainnya.php
    $nama_pengisi = $_POST['nama_pengisi'];
    $peran        = $_POST['peran'];
    $rating       = $_POST['rating'];
    $ulasan       = $_POST['ulasan'];

    try {
        // 4. Siapkan query SQL menggunakan Prepared Statement PDO (Sangat aman dari SQL Injection)
        $sql = "INSERT INTO survei_pelayanan (nama_pengisi, peran, rating, ulasan) 
                VALUES (:nama_pengisi, :peran, :rating, :ulasan)";
        
        $stmt = $pdo->prepare($sql);
        
        // 5. Eksekusi query dengan memasukkan data form ke dalam database
        $stmt->execute([
            ':nama_pengisi' => $nama_pengisi,
            ':peran'        => $peran,
            ':rating'       => $rating,
            ':ulasan'       => $ulasan
        ]);

        // 6. Jika berhasil, munculkan pop-up sukses dan kembalikan user ke form
        echo "<script>
                alert('Terima kasih! Ulasan Anda berhasil dikirim.');
                window.location.href = 'lainnya.php#kontak';
              </script>";
        exit();

    } catch (PDOException $e) {
        // Jika terjadi error saat proses simpan ke database
        die("Gagal menyimpan ulasan: " . $e->getMessage());
    }

} else {
    // Jika ada yang mencoba akses file ini secara langsung lewat URL, lempar balik ke lainnya.php
    header("Location: lainnya.php");
    exit();
}
?>
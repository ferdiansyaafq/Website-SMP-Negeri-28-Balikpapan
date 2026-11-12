<?php
require_once __DIR__ . '/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nama_pengisi = $_POST['nama_pengisi'];
    $peran        = $_POST['peran'];
    $rating       = $_POST['rating'];
    $ulasan       = $_POST['ulasan'];

    try {
        $sql = "INSERT INTO survei (nama_pengisi, peran, rating, ulasan) 
                VALUES (:nama_pengisi, :peran, :rating, :ulasan)";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':nama_pengisi' => $nama_pengisi,
            ':peran'        => $peran,
            ':rating'       => $rating,
            ':ulasan'       => $ulasan
        ]);

        echo "<script>
                alert('Terima kasih! Ulasan Anda berhasil dikirim.');
                window.location.href = '../survey.php';
              </script>";
        exit();

    } catch (PDOException $e) {
        die("Gagal menyimpan ulasan: " . $e->getMessage());
    }

} else {
    header("Location: ../survey.php");
    exit();
}
?>
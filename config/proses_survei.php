<?php
require_once __DIR__ . '/database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_pengisi = trim((string)($_POST['nama_pengisi'] ?? ''));
    $peran        = trim((string)($_POST['peran'] ?? ''));
    $rating       = trim((string)($_POST['rating'] ?? ''));
    $ulasan       = trim((string)($_POST['ulasan'] ?? ''));

    if ($nama_pengisi !== '' && $rating !== '') {
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
        echo "<script>
                alert('Mohon isi nama dan pilih tingkat kepuasan pelayanan.');
                window.location.href = '../survey.php';
              </script>";
        exit();
    }
} else {
    header("Location: ../survey.php");
    exit();
}

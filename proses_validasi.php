<?php
// Pastikan kredensial database sesuai dengan Laragon kamu
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

if (isset($_GET['id']) && isset($_GET['aksi'])) {
    $id_laporan = $_GET['id'];
    $aksi = $_GET['aksi'];

    try {
        if ($aksi == 'setuju') {
            // Logika Setuju: Isi dengan waktu saat ini (NOW)
            $sql = "UPDATE laporan_harian 
                    SET guru_validated_at = NOW(), orang_tua_validated_at = NOW() 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id_laporan]);

            echo "<script>
                    alert('Laporan berhasil divalidasi!');
                    window.location.href = 'validasi_laporan.php';
                  </script>";
                  
        } elseif ($aksi == 'batal') {
            // Logika Batal Setuju: Kembalikan nilai jadi NULL
            $sql = "UPDATE laporan_harian 
                    SET guru_validated_at = NULL, orang_tua_validated_at = NULL 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id_laporan]);

            echo "<script>
                    alert('Validasi dibatalkan! Laporan kembali ke antrean.');
                    window.location.href = 'validasi_laporan.php';
                  </script>";
        }
        
    } catch(PDOException $e) {
        echo "<script>alert('Gagal memproses: " . $e->getMessage() . "');</script>";
    }
} else {
    header("Location: validasi_laporan.php");
    exit();
}
?>
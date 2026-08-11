<?php
// 1. Panggil koneksi database kamu
// Sesuaikan path ini kalau file database.php ada di dalam folder config atau aplikasi/config
require_once 'config/database.php'; 

$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Tangkap data dari form Bento Grid
    $nama = $_POST['nama'] ?? '';
    $status = $_POST['status'] ?? '';
    $rating = (int)($_POST['rating'] ?? 0);
    $ulasan = $_POST['ulasan'] ?? '';
    
    // 3. Masukkan ke database pakai PDO
    try {
        // PERHATIAN: Ganti 'tabel_survey' dengan nama tabel asli buatanmu di database KAIH!
        // Pastikan juga nama kolomnya (nama, status, rating, ulasan) sudah sesuai.
        $sql = "INSERT INTO survei_pelayanan (nama_pengisi, peran, rating, ulasan, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $status, $rating, $ulasan]);
        
        $pesan = 'Terima kasih! Survei Anda berhasil dikirim.';
    } catch (PDOException $e) {
        // Kalau error, pesannya bakal ganti jadi warna merah (bisa disesuaikan CSS-nya)
        $pesan = 'Gagal menyimpan data: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survei Pelayanan KAIH</title>
    <style>
        /* Konsep UI Recraft / Modern Bento Grid */
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 40px 20px;
            color: #1e293b;
            display: flex;
            justify-content: center;
        }

        .bento-container {
            max-width: 800px;
            width: 100%;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        /* Bento Cards */
        .bento-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
        }

        .bento-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -5px rgba(0,0,0,0.08), 0 8px 10px -6px rgba(0,0,0,0.04);
            border-color: #cbd5e1;
        }

        /* Pengaturan Ukuran Grid (Bento Style) */
        .header-card { grid-column: span 2; background: linear-gradient(135deg, #2563eb, #3b82f6); color: white; border: none; }
        .header-card h1 { margin: 0 0 8px 0; font-size: 28px; font-weight: 800; }
        .header-card p { margin: 0; opacity: 0.9; font-size: 15px; line-height: 1.5; }
        
        .identitas-card { grid-column: span 1; }
        .rating-card { grid-column: span 1; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; }
        .ulasan-card { grid-column: span 2; }
        .action-card { grid-column: span 2; background: transparent; box-shadow: none; border: none; padding: 0; }

        @media (max-width: 640px) {
            .bento-container { grid-template-columns: 1fr; }
            .identitas-card, .rating-card { grid-column: span 2; }
        }

        /* Form Elements */
        label { display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        input[type="text"], select, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            color: #1e293b;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
            background-color: #f8fafc;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #3b82f6;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        .form-group { margin-bottom: 20px; }
        .form-group:last-child { margin-bottom: 0; }

        /* Sistem Bintang CSS Murni (Tanpa JS) */
        .rating-group {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 8px;
        }
        .rating-group input { display: none; }
        .rating-group label {
            cursor: pointer;
            font-size: 40px;
            color: #e2e8f0;
            transition: color 0.2s, transform 0.2s;
            margin: 0;
        }
        /* Efek nyala saat di-hover dan dipilih */
        .rating-group label:hover,
        .rating-group label:hover ~ label,
        .rating-group input:checked ~ label {
            color: #fbbf24;
        }
        .rating-group label:hover { transform: scale(1.15); }

        /* Chunky Button Recraft Style */
        .btn-submit {
            width: 100%;
            background-color: #1e293b;
            color: #ffffff;
            border: none;
            padding: 16px;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 0 #0f172a; /* Efek tombol 3D */
        }
        .btn-submit:hover {
            transform: translateY(2px);
            box-shadow: 0 2px 0 #0f172a;
        }
        .btn-submit:active {
            transform: translateY(4px);
            box-shadow: none;
        }

        /* Notifikasi Sukses */
        .alert-success {
            grid-column: span 2;
            background-color: #dcfce7;
            color: #166534;
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            text-align: center;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body>

    <form method="POST" action="" class="bento-container">
        
        <?php if ($pesan): ?>
            <div class="alert-success">✨ <?= htmlspecialchars($pesan) ?></div>
        <?php endif; ?>

        <!-- KOTAK 1: Header Info -->
        <div class="bento-card header-card">
            <h1>Survei Pelayanan KAIH</h1>
            <p>Kami siap melayani dan menerima masukan dari Anda demi peningkatan kualitas sistem SMP Negeri 28 Balikpapan. Suara Anda sangat berarti!</p>
        </div>

        <!-- KOTAK 2: Data Diri (Bento Kiri) -->
        <div class="bento-card identitas-card">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" placeholder="Ketik nama Anda..." required>
            </div>
            <div class="form-group">
                <label>Status Pengguna</label>
                <select name="status" required>
                    <option value="" disabled selected>Pilih status...</option>
                    <option value="Siswa">Siswa</option>
                    <option value="Guru">Guru</option>
                    <option value="Orang Tua">Orang Tua</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
        </div>

        <!-- KOTAK 3: Rating Bintang (Bento Kanan) -->
        <div class="bento-card rating-card">
            <label style="margin-bottom: 15px; font-size: 15px;">Beri Penilaian (1-5)</label>
            <div class="rating-group">
                <input type="radio" id="star5" name="rating" value="5" required />
                <label for="star5" title="Sangat Bagus">★</label>
                
                <input type="radio" id="star4" name="rating" value="4" />
                <label for="star4" title="Bagus">★</label>
                
                <input type="radio" id="star3" name="rating" value="3" />
                <label for="star3" title="Cukup">★</label>
                
                <input type="radio" id="star2" name="rating" value="2" />
                <label for="star2" title="Buruk">★</label>
                
                <input type="radio" id="star1" name="rating" value="1" />
                <label for="star1" title="Sangat Buruk">★</label>
            </div>
        </div>

        <!-- KOTAK 4: Textarea Ulasan (Full Width) -->
        <div class="bento-card ulasan-card">
            <div class="form-group">
                <label>Ulasan & Saran Peningkatan</label>
                <textarea name="ulasan" rows="4" placeholder="Ceritakan pengalaman Anda menggunakan portal KAIH atau saran untuk kami..." required></textarea>
            </div>
        </div>

        <!-- KOTAK 5: Tombol Submit (Chunky) -->
        <div class="bento-card action-card">
            <button type="submit" class="btn-submit">Kirim Ulasan Sekarang</button>
        </div>

    </form>

</body>
</html>
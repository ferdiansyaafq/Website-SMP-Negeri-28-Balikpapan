<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refleksi Pembelajaran - SMPN 28 Balikpapan</title>
    <!-- Pastikan file CSS kamu di-link di sini -->
</head>
<body>

    <!-- Bagian Form Refleksi -->
    <div style="max-width: 600px; margin: 50px auto; font-family: sans-serif;">
        <h2>Refleksi Pembelajaran Siswa</h2>
        <p>Ceritakan pengalaman belajarmu hari ini.</p>

        <form action="proses_refleksi.php" method="POST">
            <div style="margin-bottom: 15px;">
                <label for="nama_siswa" style="display:block; margin-bottom:5px;">Nama Lengkap:</label>
                <input type="text" name="nama_siswa" id="nama_siswa" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="kelas" style="display:block; margin-bottom:5px;">Kelas:</label>
                <input type="text" name="kelas" id="kelas" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="mata_pelajaran" style="display:block; margin-bottom:5px;">Mata Pelajaran:</label>
                <input type="text" name="mata_pelajaran" id="mata_pelajaran" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="isi_refleksi" style="display:block; margin-bottom:5px;">Apa yang kamu pelajari dan adakah kendala hari ini?</label>
                <textarea name="isi_refleksi" id="isi_refleksi" rows="6" required style="width: 100%; padding: 8px;"></textarea>
            </div>

            <button type="submit" style="padding: 10px 20px; background-color: #007BFF; color: white; border: none; cursor: pointer;">Kirim Refleksi</button>
        </form>
    </div>

</body>
</html>
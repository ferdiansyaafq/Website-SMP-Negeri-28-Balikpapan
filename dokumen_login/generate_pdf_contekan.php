<?php
// generate_pdf_contekan.php
require_once __DIR__ . '/config/database.php';

$outputDir = __DIR__ . '/dokumen_login';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// Logo base64
$logoPath = __DIR__ . '/assets/img/logo-sekolah1.jpeg';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/jpeg;base64,' . base64_encode($logoData);
}

// Shared CSS styles for print-ready documents
$baseCss = '
    @page {
        size: A4 portrait;
        margin: 12mm 15mm 15mm 15mm;
    }
    @media print {
        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .page-break {
            page-break-after: always;
            break-after: page;
        }
        .no-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }
    }
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    body {
        font-family: "Segoe UI", Roboto, -apple-system, BlinkMacSystemFont, Arial, sans-serif;
        color: #1e293b;
        background: #ffffff;
        font-size: 11px;
        line-height: 1.4;
    }
    .header-kop {
        display: flex;
        align-items: center;
        border-bottom: 2.5px solid #0f172a;
        padding-bottom: 10px;
        margin-bottom: 14px;
    }
    .header-kop .logo-img {
        width: 65px;
        height: 65px;
        object-fit: contain;
        margin-right: 15px;
    }
    .header-kop .kop-text {
        flex: 1;
        text-align: center;
    }
    .header-kop h2 {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }
    .header-kop h1 {
        font-size: 17px;
        font-weight: 900;
        color: #0284c7;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }
    .header-kop p {
        font-size: 10px;
        color: #475569;
    }
    .doc-title-box {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-left: 5px solid #0284c7;
        padding: 8px 12px;
        border-radius: 6px;
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .doc-title-box .title-left h3 {
        font-size: 13px;
        font-weight: 700;
        color: #0369a1;
        margin-bottom: 2px;
    }
    .doc-title-box .title-left p {
        font-size: 10px;
        color: #64748b;
    }
    .doc-title-box .meta-right {
        text-align: right;
        font-size: 10px;
        color: #0369a1;
        font-weight: 600;
    }
    .info-card-summary {
        display: flex;
        gap: 10px;
        margin-bottom: 14px;
    }
    .info-card {
        flex: 1;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 8px 10px;
        border-radius: 6px;
    }
    .info-card .card-label {
        font-size: 9px;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 2px;
    }
    .info-card .card-value {
        font-size: 12px;
        font-weight: 700;
        color: #0f172a;
    }
    .info-card .card-sub {
        font-size: 9px;
        color: #64748b;
    }
    .badge-pass {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
        font-family: Consolas, "Courier New", monospace;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        display: inline-block;
    }
    .badge-user {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        font-family: Consolas, "Courier New", monospace;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        display: inline-block;
    }
    .badge-ortu {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
        font-family: Consolas, "Courier New", monospace;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        display: inline-block;
    }
    .badge-role {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .role-guru { background: #e0f2fe; color: #0369a1; }
    .role-kepsek { background: #f3e8ff; color: #7e22ce; }
    .role-admin { background: #fee2e2; color: #b91c1c; }
    .role-siswa { background: #dcfce7; color: #15803d; }

    table.data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        font-size: 10px;
    }
    table.data-table th {
        background: #0f172a;
        color: #ffffff;
        font-weight: 600;
        text-align: left;
        padding: 6px 8px;
        border: 1px solid #0f172a;
        font-size: 9.5px;
        text-transform: uppercase;
    }
    table.data-table td {
        padding: 5px 8px;
        border: 1px solid #cbd5e1;
        vertical-align: middle;
    }
    table.data-table tbody tr:nth-child(even) {
        background: #f8fafc;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .footer-doc {
        margin-top: 15px;
        padding-top: 8px;
        border-top: 1px dashed #cbd5e1;
        display: flex;
        justify-content: space-between;
        color: #64748b;
        font-size: 9px;
    }
    .panduan-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 8px 12px;
        margin-top: 10px;
    }
    .panduan-box h4 {
        font-size: 10px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 4px;
    }
    .panduan-box ol {
        margin-left: 16px;
        font-size: 9.5px;
        color: #475569;
    }
';

// ==============================================================================
// 1. GENERATE DOKUMEN 1: CONTEKAN LOGIN GURU & STAF
// ==============================================================================
echo "[-] Mempersiapkan data Guru & Staf...\n";
$stmtG = $pdo->query("
    SELECT 
        u.id as user_id,
        u.username,
        u.role,
        g.id as guru_id,
        g.nip,
        g.nama_guru,
        g.kelas as kelas_wali,
        g.jabatan,
        g.no_hp
    FROM users u
    LEFT JOIN guru g ON u.guru_id = g.id
    WHERE u.role IN ('guru', 'kepsek', 'admin')
    ORDER BY 
        CASE u.role 
            WHEN 'admin' THEN 1 
            WHEN 'kepsek' THEN 2 
            WHEN 'guru' THEN 3 
            ELSE 4 
        END,
        g.nama_guru ASC
");
$listGuru = $stmtG->fetchAll(PDO::FETCH_ASSOC);

$htmlGuru = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Contekan Login Guru & Staf - SMPN 28 Balikpapan</title>
    <style>' . $baseCss . '</style>
</head>
<body>
    <div class="header-kop">
        ' . ($logoBase64 ? '<img src="' . $logoBase64 . '" class="logo-img" alt="Logo">' : '') . '
        <div class="kop-text">
            <h2>Pemerintah Kota Balikpapan • Dinas Pendidikan dan Kebudayaan</h2>
            <h1>SMP NEGERI 28 BALIKPAPAN</h1>
            <p>Sistem KAIH (Karakter, Akhlak, Integritas, dan Humanisme) & Portal Informasi Sekolah</p>
        </div>
    </div>

    <div class="doc-title-box">
        <div class="title-left">
            <h3>LEMBAR CONTEKAN INFORMASI LOGIN - GURU & TENAGA PENDIDIK</h3>
            <p>Daftar akun resmi untuk mengakses Sistem KAIH & Administrasi Sekolah</p>
        </div>
        <div class="meta-right">
            Tahun Ajaran: 2026/2027<br>
            Waktu Cetak: ' . date('d F Y, H:i') . ' WITA
        </div>
    </div>

    <div class="info-card-summary">
        <div class="info-card">
            <div class="card-label">Portal Login</div>
            <div class="card-value">Halaman Login Sistem</div>
            <div class="card-sub">Menu: "Masuk" / login.php</div>
        </div>
        <div class="info-card">
            <div class="card-label">Total Akun Pendidik</div>
            <div class="card-value">' . count($listGuru) . ' Akun Terdaftar</div>
            <div class="card-sub">Admin, Kepala Sekolah & Guru</div>
        </div>
        <div class="info-card">
            <div class="card-label">Ketentuan Password</div>
            <div class="card-value">Password Default</div>
            <div class="card-sub">Bisa diganti di menu akun</div>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">No</th>
                <th>Nama Lengkap & NIP</th>
                <th>Jabatan / Tugas</th>
                <th>Wali Kelas</th>
                <th>Role Sistem</th>
                <th>Username Login</th>
                <th>Password</th>
            </tr>
        </thead>
        <tbody>';

$no = 1;
foreach ($listGuru as $row) {
    $nama = !empty($row['nama_guru']) ? htmlspecialchars($row['nama_guru']) : 'Administrator Sistem';
    $nip = !empty($row['nip']) ? htmlspecialchars($row['nip']) : '-';
    $jabatan = !empty($row['jabatan']) ? htmlspecialchars($row['jabatan']) : 'Super Admin';
    $wali = !empty($row['kelas_wali']) ? htmlspecialchars($row['kelas_wali']) : '-';
    $role = $row['role'];

    $roleBadgeClass = 'role-' . $role;
    $roleLabel = ucfirst($role);
    if ($role === 'kepsek') $roleLabel = 'Kepala Sekolah';

    // Password per role
    $passText = '123456';
    if ($role === 'admin') $passText = 'admin123';
    elseif ($role === 'kepsek') $passText = '12345678';

    $htmlGuru .= '<tr>
        <td class="text-center">' . $no++ . '</td>
        <td>
            <strong>' . $nama . '</strong><br>
            <span style="color:#64748b; font-size:9px;">NIP: ' . $nip . '</span>
        </td>
        <td>' . $jabatan . '</td>
        <td>' . $wali . '</td>
        <td><span class="badge-role ' . $roleBadgeClass . '">' . $roleLabel . '</span></td>
        <td><span class="badge-user">' . htmlspecialchars($row['username']) . '</span></td>
        <td><span class="badge-pass">' . $passText . '</span></td>
    </tr>';
}

$htmlGuru .= '</tbody>
    </table>

    <div class="panduan-box no-break">
        <h4>Petunjuk Penggunaan Akun Guru & Staf:</h4>
        <ol>
            <li>Buka halaman login sekolah melalui peramban (browser) di komputer atau ponsel pintar Anda.</li>
            <li>Ketikkan <strong>Username</strong> (NIP untuk Bapak/Ibu Guru) dan <strong>Password</strong> yang tertera pada tabel di atas.</li>
            <li>Setelah berhasil masuk, Bapak/Ibu Guru dapat mengisi Jurnal Mengajar Harian, memantau absensi KAIH siswa, survei kepuasan, dan rekapitulasi kelas.</li>
            <li>Demi keamanan data, disarankan untuk mengganti password bawaan setelah login pertama kali melalui menu <em>Ganti Password</em>.</li>
        </ol>
    </div>

    <div class="footer-doc">
        <span>Sistem Informasi SMP Negeri 28 Balikpapan</span>
        <span>Dokumen Rahasia & Internal Sekolah • Halaman 1 dari 1</span>
    </div>
</body>
</html>';

file_put_contents($outputDir . '/Contekan_Login_Guru.html', $htmlGuru);
echo " [✓] Berhasil generate: dokumen_login/Contekan_Login_Guru.html\n";

// ==============================================================================
// 2. GENERATE DOKUMEN 2: CONTEKAN LOGIN SISWA KELAS 8 (PER KELAS)
// ==============================================================================
echo "[-] Mempersiapkan data Siswa Kelas 8...\n";
$stmtK8 = $pdo->query("
    SELECT 
        s.id as siswa_id,
        s.nisn,
        s.nama_siswa,
        s.kelas,
        s.jenis_kelamin,
        u.username as username_siswa
    FROM siswa s
    JOIN users u ON u.siswa_id = s.id AND u.role = 'siswa'
    WHERE s.kelas IN ('Kelas 8A', 'Kelas 8B', 'Kelas 8C', 'Kelas 8D')
    ORDER BY s.kelas ASC, s.nama_siswa ASC
");
$siswaKelas8 = $stmtK8->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan per kelas
$groupedK8 = [
    'Kelas 8A' => [],
    'Kelas 8B' => [],
    'Kelas 8C' => [],
    'Kelas 8D' => [],
];
foreach ($siswaKelas8 as $row) {
    if (isset($groupedK8[$row['kelas']])) {
        $groupedK8[$row['kelas']][] = $row;
    }
}

$htmlK8 = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Contekan Login Siswa Kelas 8 - SMPN 28 Balikpapan</title>
    <style>' . $baseCss . '</style>
</head>
<body>';

$totalK8Classes = count($groupedK8);
$currentClassIdx = 0;

foreach ($groupedK8 as $namaKelas => $daftarMurid) {
    $currentClassIdx++;
    $isLastClass = ($currentClassIdx === $totalK8Classes);
    $pageBreakClass = $isLastClass ? '' : 'page-break';

    $jmlL = 0;
    $jmlP = 0;
    foreach ($daftarMurid as $m) {
        if ($m['jenis_kelamin'] === 'L') $jmlL++;
        else $jmlP++;
    }

    $htmlK8 .= '<div class="' . $pageBreakClass . '">
        <div class="header-kop">
            ' . ($logoBase64 ? '<img src="' . $logoBase64 . '" class="logo-img" alt="Logo">' : '') . '
            <div class="kop-text">
                <h2>Pemerintah Kota Balikpapan • Dinas Pendidikan dan Kebudayaan</h2>
                <h1>SMP NEGERI 28 BALIKPAPAN</h1>
                <p>Sistem KAIH (Karakter, Akhlak, Integritas, dan Humanisme) & Portal Informasi Siswa</p>
            </div>
        </div>

        <div class="doc-title-box">
            <div class="title-left">
                <h3>LEMBAR CONTEKAN LOGIN SISWA & ORANG TUA - ' . strtoupper($namaKelas) . '</h3>
                <p>Daftar akun login Sistem KAIH Tahun Ajaran 2026/2027</p>
            </div>
            <div class="meta-right">
                Unit Kelas: ' . $namaKelas . '<br>
                Total: ' . count($daftarMurid) . ' Siswa (L: ' . $jmlL . ' | P: ' . $jmlP . ')
            </div>
        </div>

        <div class="info-card-summary">
            <div class="info-card">
                <div class="card-label">Format Username Siswa</div>
                <div class="card-value">Nomor NISN</div>
                <div class="card-sub">Contoh: 0127513482</div>
            </div>
            <div class="info-card">
                <div class="card-label">Password Bawaan (Default)</div>
                <div class="card-value"><span class="badge-pass" style="font-size:12px;">smpn28bpp</span></div>
                <div class="card-sub">Sama untuk Siswa & Orang Tua</div>
            </div>
            <div class="info-card">
                <div class="card-label">Format Username Ortu</div>
                <div class="card-value"><span class="badge-ortu" style="font-size:11px;">ORT + NISN</span></div>
                <div class="card-sub">Contoh: ORT0127513482</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th style="width: 75px;">NISN</th>
                    <th>Nama Lengkap Siswa</th>
                    <th style="width: 28px;" class="text-center">L/P</th>
                    <th style="width: 90px;">Username Siswa</th>
                    <th style="width: 80px;">Password</th>
                    <th style="width: 100px;">Username Ortu</th>
                </tr>
            </thead>
            <tbody>';

    $noSiswa = 1;
    foreach ($daftarMurid as $siswa) {
        $nisn = htmlspecialchars($siswa['nisn']);
        $nama = htmlspecialchars($siswa['nama_siswa']);
        $jk = htmlspecialchars($siswa['jenis_kelamin']);
        $uSiswa = htmlspecialchars($siswa['username_siswa']);
        $uOrtu = 'ORT' . $nisn;

        $htmlK8 .= '<tr>
            <td class="text-center">' . $noSiswa++ . '</td>
            <td><strong>' . $nisn . '</strong></td>
            <td>' . $nama . '</td>
            <td class="text-center">' . $jk . '</td>
            <td><span class="badge-user">' . $uSiswa . '</span></td>
            <td><span class="badge-pass">smpn28bpp</span></td>
            <td><span class="badge-ortu">' . $uOrtu . '</span></td>
        </tr>';
    }

    $htmlK8 .= '</tbody>
        </table>

        <div class="panduan-box no-break">
            <h4>Catatan & Petunjuk untuk Siswa & Wali Kelas:</h4>
            <ol>
                <li>Lembar ini dapat dicetak oleh Wali Kelas sebagai kartu pegangan/arsip absensi dan panduan pengisian mandiri.</li>
                <li>Siswa masuk ke portal menggunakan <strong>Username: NISN</strong> dan <strong>Password: smpn28bpp</strong> untuk mengisi presensi KAIH harian, buku literasi, dan refleksi diri.</li>
                <li>Orang Tua/Wali Murid dapat masuk menggunakan akun dengan awalan <strong>ORT[NISN]</strong> untuk memantau grafik perkembangan dan karakter ananda tercinta.</li>
            </ol>
        </div>

        <div class="footer-doc">
            <span>Sistem Informasi KAIH SMP Negeri 28 Balikpapan</span>
            <span>Bagian: ' . $namaKelas . ' • Lembar Arsip Resmi</span>
        </div>
    </div>';
}

$htmlK8 .= '</body>
</html>';

file_put_contents($outputDir . '/Contekan_Login_Siswa_Kelas_8.html', $htmlK8);
echo " [✓] Berhasil generate: dokumen_login/Contekan_Login_Siswa_Kelas_8.html (Total: " . count($siswaKelas8) . " siswa)\n";

// ==============================================================================
// 3. GENERATE DOKUMEN 3: CONTEKAN LOGIN MURID BARU (PPDB 2026/2027)
// ==============================================================================
echo "[-] Mempersiapkan data Murid Baru...\n";
$stmtMb = $pdo->query("
    SELECT 
        s.id as siswa_id,
        s.nisn,
        s.nama_siswa,
        s.kelas,
        s.jenis_kelamin,
        u.username as username_siswa
    FROM siswa s
    JOIN users u ON u.siswa_id = s.id AND u.role = 'siswa'
    WHERE s.kelas = 'Belum Ada Kelas'
    ORDER BY s.nama_siswa ASC
");
$daftarMuridBaru = $stmtMb->fetchAll(PDO::FETCH_ASSOC);

// Murid baru ada 100 orang. Agar rapi dicetak, kita bagi per halaman (~33-34 siswa per halaman atau tabel terbagi rapi)
$chunkSize = 34;
$chunksMb = array_chunk($daftarMuridBaru, $chunkSize);
$totalChunksMb = count($chunksMb);

$jmlMbL = 0;
$jmlMbP = 0;
foreach ($daftarMuridBaru as $m) {
    if ($m['jenis_kelamin'] === 'L') $jmlMbL++;
    else $jmlMbP++;
}

$htmlMb = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Contekan Login Murid Baru - SMPN 28 Balikpapan</title>
    <style>' . $baseCss . '</style>
</head>
<body>';

$globalNo = 1;
foreach ($chunksMb as $pageIdx => $chunkList) {
    $isLastPage = ($pageIdx + 1 === $totalChunksMb);
    $pageBreakClass = $isLastPage ? '' : 'page-break';

    $htmlMb .= '<div class="' . $pageBreakClass . '">
        <div class="header-kop">
            ' . ($logoBase64 ? '<img src="' . $logoBase64 . '" class="logo-img" alt="Logo">' : '') . '
            <div class="kop-text">
                <h2>Pemerintah Kota Balikpapan • Dinas Pendidikan dan Kebudayaan</h2>
                <h1>SMP NEGERI 28 BALIKPAPAN</h1>
                <p>Sistem KAIH (Karakter, Akhlak, Integritas, dan Humanisme) & Portal Informasi Siswa Baru</p>
            </div>
        </div>

        <div class="doc-title-box">
            <div class="title-left">
                <h3>LEMBAR CONTEKAN LOGIN MURID BARU (PPDB 2026/2027)</h3>
                <p>Daftar akun login Siswa Baru & Orang Tua sebelum pembagian kelas definitif</p>
            </div>
            <div class="meta-right">
                Halaman: ' . ($pageIdx + 1) . ' dari ' . $totalChunksMb . '<br>
                Total: ' . count($daftarMuridBaru) . ' Siswa Baru (L: ' . $jmlMbL . ' | P: ' . $jmlMbP . ')
            </div>
        </div>';

    if ($pageIdx === 0) {
        $htmlMb .= '
        <div class="info-card-summary">
            <div class="info-card">
                <div class="card-label">Format Username Siswa</div>
                <div class="card-value">Nomor NISN</div>
                <div class="card-sub">Contoh: 0152877503</div>
            </div>
            <div class="info-card">
                <div class="card-label">Password Bawaan (Default)</div>
                <div class="card-value"><span class="badge-pass" style="font-size:12px;">smpn28bpp</span></div>
                <div class="card-sub">Sama untuk Siswa & Orang Tua</div>
            </div>
            <div class="info-card">
                <div class="card-label">Format Username Ortu</div>
                <div class="card-value"><span class="badge-ortu" style="font-size:11px;">ORT + NISN</span></div>
                <div class="card-sub">Contoh: ORT0152877503</div>
            </div>
        </div>';
    }

    $htmlMb .= '
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th style="width: 75px;">NISN</th>
                    <th>Nama Lengkap Siswa</th>
                    <th style="width: 28px;" class="text-center">L/P</th>
                    <th style="width: 90px;">Username Siswa</th>
                    <th style="width: 80px;">Password</th>
                    <th style="width: 100px;">Username Ortu</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($chunkList as $siswa) {
        $nisn = htmlspecialchars($siswa['nisn']);
        $nama = htmlspecialchars($siswa['nama_siswa']);
        $jk = htmlspecialchars($siswa['jenis_kelamin']);
        $uSiswa = htmlspecialchars($siswa['username_siswa']);
        $uOrtu = 'ORT' . $nisn;

        $htmlMb .= '<tr>
            <td class="text-center">' . $globalNo++ . '</td>
            <td><strong>' . $nisn . '</strong></td>
            <td>' . $nama . '</td>
            <td class="text-center">' . $jk . '</td>
            <td><span class="badge-user">' . $uSiswa . '</span></td>
            <td><span class="badge-pass">smpn28bpp</span></td>
            <td><span class="badge-ortu">' . $uOrtu . '</span></td>
        </tr>';
    }

    $htmlMb .= '</tbody>
        </table>';

    if ($isLastPage) {
        $htmlMb .= '
        <div class="panduan-box no-break">
            <h4>Petunjuk Bagi Murid Baru & Orang Tua:</h4>
            <ol>
                <li>Akun ini langsung aktif dan dapat digunakan untuk mengakses portal sekolah serta pengenalan sistem KAIH.</li>
                <li>Setelah pembagian kelas resmi dilakukan oleh pihak sekolah, nama kelas siswa akan diperbarui secara otomatis di dalam sistem.</li>
                <li>Simpan nomor NISN dan contekan login ini baik-baik. Jika ada kendala login, silakan hubungi Tim IT / Panitia PPDB SMPN 28 Balikpapan.</li>
            </ol>
        </div>';
    }

    $htmlMb .= '
        <div class="footer-doc">
            <span>Sistem Informasi KAIH SMP Negeri 28 Balikpapan • Murid Baru 2026/2027</span>
            <span>Halaman ' . ($pageIdx + 1) . ' dari ' . $totalChunksMb . '</span>
        </div>
    </div>';
}

$htmlMb .= '</body>
</html>';

file_put_contents($outputDir . '/Contekan_Login_Siswa_Murid_Baru.html', $htmlMb);
echo " [✓] Berhasil generate: dokumen_login/Contekan_Login_Siswa_Murid_Baru.html (Total: " . count($daftarMuridBaru) . " siswa baru)\n";

echo "\nSemua file HTML siap dikonversi ke PDF!\n";

<?php
// Ekstensi untuk generate per kelas individu Kelas 8 jika diperlukan
require_once __DIR__ . '/config/database.php';

$outputDir = __DIR__ . '/dokumen_login';

$logoPath = __DIR__ . '/assets/img/logo-sekolah1.jpeg';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/jpeg;base64,' . base64_encode($logoData);
}

$baseCss = '
    @page { size: A4 portrait; margin: 12mm 15mm 15mm 15mm; }
    @media print {
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .page-break { page-break-after: always; break-after: page; }
        .no-break { page-break-inside: avoid; break-inside: avoid; }
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: "Segoe UI", Roboto, Arial, sans-serif; color: #1e293b; background: #fff; font-size: 11px; line-height: 1.4; }
    .header-kop { display: flex; align-items: center; border-bottom: 2.5px solid #0f172a; padding-bottom: 10px; margin-bottom: 14px; }
    .header-kop .logo-img { width: 65px; height: 65px; object-fit: contain; margin-right: 15px; }
    .header-kop .kop-text { flex: 1; text-align: center; }
    .header-kop h2 { font-size: 15px; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-bottom: 2px; }
    .header-kop h1 { font-size: 17px; font-weight: 900; color: #0284c7; text-transform: uppercase; margin-bottom: 2px; }
    .header-kop p { font-size: 10px; color: #475569; }
    .doc-title-box { background: #f0f9ff; border: 1px solid #bae6fd; border-left: 5px solid #0284c7; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
    .doc-title-box .title-left h3 { font-size: 13px; font-weight: 700; color: #0369a1; margin-bottom: 2px; }
    .doc-title-box .title-left p { font-size: 10px; color: #64748b; }
    .doc-title-box .meta-right { text-align: right; font-size: 10px; color: #0369a1; font-weight: 600; }
    .info-card-summary { display: flex; gap: 10px; margin-bottom: 14px; }
    .info-card { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 10px; border-radius: 6px; }
    .info-card .card-label { font-size: 9px; text-transform: uppercase; font-weight: 700; color: #64748b; margin-bottom: 2px; }
    .info-card .card-value { font-size: 12px; font-weight: 700; color: #0f172a; }
    .info-card .card-sub { font-size: 9px; color: #64748b; }
    .badge-pass { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-family: Consolas, monospace; font-weight: 700; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
    .badge-user { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-family: Consolas, monospace; font-weight: 700; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
    .badge-ortu { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-family: Consolas, monospace; font-weight: 700; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
    table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10px; }
    table.data-table th { background: #0f172a; color: #fff; font-weight: 600; text-align: left; padding: 6px 8px; border: 1px solid #0f172a; font-size: 9.5px; text-transform: uppercase; }
    table.data-table td { padding: 5px 8px; border: 1px solid #cbd5e1; vertical-align: middle; }
    table.data-table tbody tr:nth-child(even) { background: #f8fafc; }
    .text-center { text-align: center; }
    .footer-doc { margin-top: 15px; padding-top: 8px; border-top: 1px dashed #cbd5e1; display: flex; justify-content: space-between; color: #64748b; font-size: 9px; }
    .panduan-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; margin-top: 10px; }
    .panduan-box h4 { font-size: 10px; font-weight: 700; color: #334155; margin-bottom: 4px; }
    .panduan-box ol { margin-left: 16px; font-size: 9.5px; color: #475569; }
';

$kelasList = ['Kelas 8A', 'Kelas 8B', 'Kelas 8C', 'Kelas 8D'];

foreach ($kelasList as $kelas) {
    $stmt = $pdo->prepare("
        SELECT s.nisn, s.nama_siswa, s.kelas, s.jenis_kelamin, u.username as username_siswa
        FROM siswa s
        JOIN users u ON u.siswa_id = s.id AND u.role = 'siswa'
        WHERE s.kelas = ?
        ORDER BY s.nama_siswa ASC
    ");
    $stmt->execute([$kelas]);
    $murids = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $codeKelas = str_replace(' ', '_', $kelas);
    $html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Contekan Login ' . $kelas . '</title><style>' . $baseCss . '</style></head><body>';
    $html .= '<div class="header-kop">' . ($logoBase64 ? '<img src="' . $logoBase64 . '" class="logo-img" alt="Logo">' : '') . '<div class="kop-text"><h2>Pemerintah Kota Balikpapan • Dinas Pendidikan dan Kebudayaan</h2><h1>SMP NEGERI 28 BALIKPAPAN</h1><p>Sistem KAIH (Karakter, Akhlak, Integritas, dan Humanisme) & Portal Informasi Siswa</p></div></div>';
    $html .= '<div class="doc-title-box"><div class="title-left"><h3>LEMBAR CONTEKAN LOGIN SISWA & ORANG TUA - ' . strtoupper($kelas) . '</h3><p>Daftar akun login Sistem KAIH Tahun Ajaran 2026/2027</p></div><div class="meta-right">Unit: ' . $kelas . '<br>Total: ' . count($murids) . ' Siswa</div></div>';
    $html .= '<div class="info-card-summary"><div class="info-card"><div class="card-label">Format Username Siswa</div><div class="card-value">Nomor NISN</div></div><div class="info-card"><div class="card-label">Password Default</div><div class="card-value"><span class="badge-pass" style="font-size:12px;">smpn28bpp</span></div></div><div class="info-card"><div class="card-label">Format Username Ortu</div><div class="card-value"><span class="badge-ortu" style="font-size:11px;">ORT + NISN</span></div></div></div>';
    $html .= '<table class="data-table"><thead><tr><th style="width:25px;" class="text-center">No</th><th style="width:75px;">NISN</th><th>Nama Lengkap Siswa</th><th style="width:28px;" class="text-center">L/P</th><th style="width:90px;">Username Siswa</th><th style="width:80px;">Password</th><th style="width:100px;">Username Ortu</th></tr></thead><tbody>';

    $no = 1;
    foreach ($murids as $m) {
        $uOrtu = 'ORT' . $m['nisn'];
        $html .= '<tr><td class="text-center">' . $no++ . '</td><td><strong>' . htmlspecialchars($m['nisn']) . '</strong></td><td>' . htmlspecialchars($m['nama_siswa']) . '</td><td class="text-center">' . htmlspecialchars($m['jenis_kelamin']) . '</td><td><span class="badge-user">' . htmlspecialchars($m['username_siswa']) . '</span></td><td><span class="badge-pass">smpn28bpp</span></td><td><span class="badge-ortu">' . $uOrtu . '</span></td></tr>';
    }
    $html .= '</tbody></table>';
    $html .= '<div class="panduan-box no-break"><h4>Petunjuk Siswa & Wali Kelas:</h4><ol><li>Login di halaman login sistem menggunakan NISN dan Password: smpn28bpp.</li><li>Akun Orang Tua menggunakan awalan ORT + NISN dengan Password: smpn28bpp.</li></ol></div>';
    $html .= '<div class="footer-doc"><span>Sistem Informasi KAIH SMP Negeri 28 Balikpapan</span><span>Dokumen Resmi • ' . $kelas . '</span></div></body></html>';

    $htmlFile = $outputDir . '/Contekan_Login_Siswa_' . $codeKelas . '.html';
    $pdfFile = $outputDir . '/Contekan_Login_Siswa_' . $codeKelas . '.pdf';
    file_put_contents($htmlFile, $html);
    echo "Generated HTML for $kelas\n";
}

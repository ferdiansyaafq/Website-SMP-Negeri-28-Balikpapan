<?php
session_start();
require_once 'config/database.php';
require_once 'includes/user_accounts.php';

// Redirect already-logged-in users
if (isset($_SESSION['portal_user_id'], $_SESSION['portal_role'])) {
    $r = (string)$_SESSION['portal_role'];
    if ($r === 'siswa')      { header('Location: progress-harian.php'); exit; }
    if ($r === 'orang_tua') { header('Location: ortu-validasi.php');   exit; }
    if ($r === 'guru')      { header('Location: guru-validasi.php');    exit; }
    header('Location: logout.php'); exit;
}

// Handle portal login POST (siswa / orang_tua / guru) submitted from modals
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['role'])) {
    $postRole = trim((string)($_POST['role']       ?? ''));
    $postId   = trim((string)($_POST['identifier'] ?? ''));
    $postPw   = trim((string)($_POST['password']   ?? ''));
    $errMsg   = '';
    $dbConn   = getConnection();
    try {
        $res = attemptPortalLogin($dbConn, $postRole, $postId, $postPw);
        if (!empty($res['success'])) {
            session_regenerate_id(true);
            $_SESSION['portal_user_id']     = (int)$res['user']['id'];
            $_SESSION['portal_role']         = $res['user']['role'];
            $_SESSION['portal_display_name'] = $res['user']['display_name'];
            $_SESSION['portal_login_time']   = time();
            $dbConn->close();
            $dest = match ($res['user']['role']) {
                'siswa'     => 'progress-harian.php',
                'orang_tua' => 'ortu-validasi.php',
                'guru'      => 'guru-validasi.php',
                default     => 'logout.php',
            };
            header('Location: ' . $dest);
            exit;
        }
        $errMsg = $res['message'] ?? 'Username atau password salah.';
    } catch (Throwable $e) {
        $errMsg = 'Username atau password salah.';
    }
    $dbConn->close();
    header('Location: index.php?login_err=' . urlencode($errMsg) . '&login_role=' . urlencode($postRole));
    exit;
}

function pickFirstExistingImage(array $candidates, string $fallback): string
{
    foreach ($candidates as $path) {
        $fsPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (is_file($fsPath)) {
            $v = (int) @filemtime($fsPath);
            return $path . ($v > 0 ? ('?v=' . $v) : '');
        }
    }
    return $fallback;
}

$logoSekolah = pickFirstExistingImage([
    'assets/img/logo-sekolah.png',
    'assets/img/logo-sekolah.jpg',
    'assets/img/logo-sekolah.jpeg',
    'assets/img/logo-sekolah.webp',
    'assets/img/logo-sekolah.svg',
    'assets/img/logo.png',
], 'assets/img/logo-sekolah.svg');

// Load slideshow photos from database, fallback to static files
$slideshowPhotos = [];
try {
    $dbSlide = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $dbSlide->set_charset('utf8mb4');
    // Auto-create table if not exists
    $dbSlide->query("CREATE TABLE IF NOT EXISTS foto_slideshow (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        judul VARCHAR(255) DEFAULT '',
        urutan INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $res = $dbSlide->query("SELECT filename, judul FROM foto_slideshow ORDER BY urutan ASC, id ASC");
    while ($row = $res->fetch_assoc()) {
        $fPath = 'assets/img/slideshow/' . $row['filename'];
        $fsPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fPath);
        if (is_file($fsPath)) {
            $v = (int) @filemtime($fsPath);
            $slideshowPhotos[] = [
                'src'  => $fPath . ($v > 0 ? '?v=' . $v : ''),
                'alt'  => $row['judul'] ?: 'Foto Slideshow',
            ];
        }
    }
    $dbSlide->close();
} catch (Throwable $e) {
    // silently fallback
}

// Fallback to static files if no DB photos
if (empty($slideshowPhotos)) {
    $fotoGuru1 = pickFirstExistingImage([
        'assets/img/fotoguru1.jpg', 'assets/img/fotoguru1.jpeg',
        'assets/img/fotoguru1.png', 'assets/img/fotoguru1.webp',
    ], 'assets/img/foto-guru.svg');
    $fotoGuru2 = pickFirstExistingImage([
        'assets/img/fotoguru2.jpg', 'assets/img/fotoguru2.jpeg',
        'assets/img/fotoguru2.png', 'assets/img/fotoguru2.webp',
    ], 'assets/img/foto-guru.svg');
    $fotoGuru3 = pickFirstExistingImage([
        'assets/img/fotoguru3.jpg', 'assets/img/fotoguru3.jpeg',
        'assets/img/fotoguru3.png', 'assets/img/fotoguru3.webp',
    ], 'assets/img/foto-guru.svg');
    $slideshowPhotos = [
        ['src' => $fotoGuru1, 'alt' => 'Foto 1'],
        ['src' => $fotoGuru2, 'alt' => 'Foto 2'],
        ['src' => $fotoGuru3, 'alt' => 'Foto 3'],
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMP Negeri 28 Balikpapan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
    /* ═══════════════════════════════════════════
       RESET & BASE
    ═══════════════════════════════════════════ */
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

    :root {
        /* colors — light blue palette */
        --ink:      #f0f4f8;
        --ink2:     #e8edf3;
        --ink3:     #f1f5f9;
        --leaf:     #2563eb;
        --leaf-d:   #1d4ed8;
        --gold:     #2563eb;
        --coral:    #1d4ed8;
        --sky:      #60a5fa;
        --lilac:    #93c5fd;
        --paper:    #1e293b;
        --paper2:   #334155;
        --cream:    #0f172a;
        --surface:  rgba(37,99,235,0.04);
        --surface2: rgba(37,99,235,0.06);
        --line:     rgba(148,163,184,0.16);
        --line2:    rgba(148,163,184,0.24);
        --text:     #1e293b;
        --muted:    #64748b;
        --radius:   18px;
        --radius-lg:28px;
    }
    html { scroll-behavior:smooth; }

    body {
        font-family:'Plus Jakarta Sans', sans-serif;
        background: var(--ink);
        color: var(--text);
        min-height:100vh;
        overflow-x:hidden;
        transition: background 0.35s, color 0.35s;
    }
    /* ═══════════════════════════════════════════
       GRAIN TEXTURE OVERLAY
    ═══════════════════════════════════════════ */
    body::before {
        content:'';
        position:fixed; inset:0; z-index:0; pointer-events:none;
        opacity:0.035;
        background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='g'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23g)'/%3E%3C/svg%3E");
        background-repeat:repeat;
        background-size:200px;
    }
    /* ═══════════════════════════════════════════
       MESH BG BLOBS
    ═══════════════════════════════════════════ */
    .mesh {
        position:fixed; inset:0; z-index:0; pointer-events:none; overflow:hidden;
    }
    .blob {
        position:absolute; border-radius:50%;
        filter:blur(110px); opacity:0.12;
        animation:blobDrift 26s ease-in-out infinite;
    }
    .b1 { width:650px; height:650px; background:#93c5fd; top:-180px; left:-150px; animation-delay:0s; }
    .b2 { width:500px; height:500px; background:#a5b4fc; bottom:-100px; right:-100px; animation-delay:-9s; }
    .b3 { width:380px; height:380px; background:#bfdbfe; top:40%; left:55%; animation-delay:-18s; }
    @keyframes blobDrift {
        0%,100% { transform:translate(0,0) scale(1); }
        33%      { transform:translate(40px,-60px) scale(1.08); }
        66%      { transform:translate(-30px,45px) scale(0.94); }
    }
    /* ═══════════════════════════════════════════
       NAVBAR
    ═══════════════════════════════════════════ */
    .nav {
        position:fixed; top:0; left:0; right:0; z-index:300;
        display:flex; align-items:center; justify-content:space-between;
        padding:0 32px;
        height:64px;
        background: rgba(255,255,255,0.80);
        backdrop-filter:blur(22px); -webkit-backdrop-filter:blur(22px);
        border-bottom:1px solid var(--line);
    }
    .nav-brand { display:flex; align-items:center; gap:11px; text-decoration:none; }
    .nav-logo {
        width:56px; height:56px; border-radius:50%; overflow:hidden;
        border:1.5px solid rgba(37,99,235,0.25);
        box-shadow:0 0 12px rgba(37,99,235,0.12);
        flex-shrink:0; background:#ffffff;
    }
    .nav-logo img { width:100%; height:100%; object-fit:cover; }
    .nav-wordmark { font-family:'Playfair Display',serif; font-weight:700; font-size:16px; color:var(--text); letter-spacing:0.01em; line-height:1.3; }
    .nav-wordmark .nav-school { display:block; font-size:12px; font-weight:600; color:var(--muted); letter-spacing:0.02em; font-family:'Plus Jakarta Sans',sans-serif; }

    .nav-right { display:flex; align-items:center; gap:10px; }
    .nav-badge {
        position:relative;
        font-size:10px; font-weight:700; letter-spacing:1.4px; text-transform:uppercase;
        color:#ffffff;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #2563eb 100%);
        background-size: 200% 200%;
        animation: badgeShimmer 3s ease-in-out infinite;
        border:1px solid rgba(37,99,235,0.4);
        padding:5px 14px; border-radius:999px;
        box-shadow:
            0 0 12px rgba(37,99,235,0.20),
            0 0 24px rgba(37,99,235,0.08),
            inset 0 1px 0 rgba(255,255,255,0.18);
        overflow:hidden;
    }
    .nav-badge::before {
        content:'';
        position:absolute; inset:0;
        background: linear-gradient(105deg, transparent 35%, rgba(255,255,255,0.28) 50%, transparent 65%);
        background-size:250% 100%;
        animation: badgeSweep 2.8s ease-in-out infinite;
        border-radius:inherit;
    }
    @keyframes badgeShimmer {
        0%,100% { background-position:0% 50%; box-shadow:0 0 12px rgba(37,99,235,0.20),0 0 24px rgba(37,99,235,0.08); }
        50%     { background-position:100% 50%; box-shadow:0 0 18px rgba(37,99,235,0.28),0 0 32px rgba(37,99,235,0.12); }
    }
    @keyframes badgeSweep {
        0%      { background-position:200% 0; }
        100%    { background-position:-50% 0; }
    }
    /* ═══════════════════════════════════════════
       HERO — FULL-BLEED SPLIT
    ═══════════════════════════════════════════ */
    .hero {
        position:relative; z-index:1;
        min-height:100svh;
        display:grid;
        grid-template-columns:1fr 1fr;
        padding-top:64px;
        overflow:hidden;
        isolation:isolate;
    }

    /* Left text panel */
    .hero-left {
        position:relative;
        z-index:5;
        display:flex; flex-direction:column; justify-content:center;
        padding:92px 144px 92px 64px;
        gap:28px;
        margin-right:-92px;
        pointer-events:none;
    }
    .hero-left::before {
        content:'';
        position:absolute;
        inset:0 -118px 0 0;
        border-radius:0;
        background:
            radial-gradient(circle at 18% 18%, rgba(37,99,235,0.06), transparent 30%),
            linear-gradient(90deg, rgba(241,245,249,0.99) 0%, rgba(241,245,249,0.97) 54%, rgba(240,244,248,0.90) 74%, rgba(240,244,248,0.52) 88%, rgba(240,244,248,0.18) 95%, rgba(240,244,248,0) 100%);
        box-shadow:none;
        -webkit-mask-image:none;
        mask-image:none;
        transform:none;
        pointer-events:none;
        animation:none;
    }
    .hero-left::after {
        content:none;
    }
    .hero-left > * {
        position:relative;
        z-index:1;
        pointer-events:auto;
    }
    .hero-eyebrow {
        display:inline-flex; align-items:center; gap:8px;
        font-size:11px; font-weight:700; letter-spacing:2.5px; text-transform:uppercase;
        color:var(--gold);
        background:rgba(37,99,235,0.06); border:1px solid rgba(37,99,235,0.18);
        padding:6px 14px; border-radius:999px; width:fit-content;
        box-shadow:0 12px 32px rgba(15,23,42,0.06), inset 0 1px 0 rgba(255,255,255,0.80);
    }
    .hero-eyebrow::before { content:''; display:block; width:6px; height:6px; border-radius:50%; background:var(--gold); animation:pulse 2s ease-in-out infinite; }
    @keyframes pulse { 0%,100%{opacity:1;transform:scale(1);}50%{opacity:0.5;transform:scale(0.7);} }

    .hero-h1 {
        font-family:'Fraunces', serif;
        font-size:clamp(38px, 5.5vw, 68px);
        font-weight:900;
        line-height:1.0;
        letter-spacing:-0.03em;
        color: var(--text);
        text-shadow:0 12px 30px rgba(15,23,42,0.06);
    }
    .hero-h1 em { font-style:italic; font-weight:300; color:var(--leaf); }
    .hero-h1 .outline-text {
        -webkit-text-stroke: 2px var(--leaf);
        color: transparent;
        text-shadow:0 0 24px rgba(37,99,235,0.12);
    }

    .hero-sub {
        font-size:16px; line-height:1.8; color:var(--muted); max-width:440px;
        text-shadow:none;
    }

    .hero-cta-row {
        display:flex; gap:12px; flex-wrap:wrap;
    }
    .btn-primary {
        display:inline-flex; align-items:center; gap:8px;
        background:var(--leaf); color:#ffffff;
        font-weight:800; font-size:14px; padding:13px 26px; border-radius:999px;
        border:none; cursor:pointer; text-decoration:none;
        transition:transform 0.22s, box-shadow 0.22s, background 0.22s;
        box-shadow:0 6px 24px rgba(37,99,235,0.20);
        font-family:'Raleway','Plus Jakarta Sans',sans-serif;
    }
    .btn-primary:hover { transform:translateY(-3px); box-shadow:0 12px 36px rgba(37,99,235,0.30); background:#3b82f6; }
    .btn-outline {
        display:inline-flex; align-items:center; gap:8px;
        background:transparent; color:var(--text);
        font-weight:700; font-size:14px; padding:12px 22px; border-radius:999px;
        border:1.5px solid var(--line2); cursor:pointer; text-decoration:none;
        transition:border-color 0.22s, background 0.22s;
        font-family:'Plus Jakarta Sans',sans-serif;
    }
    .btn-outline:hover { border-color:rgba(148,163,184,0.35); background:var(--surface2); }

    .hero-stat-row {
        display:flex;
        gap:16px;
        flex-wrap:wrap;
    }
    .hero-stat-card {
        min-width:132px;
        padding:18px 18px 16px;
        border-radius:22px;
        border:1px solid rgba(148,163,184,0.16);
        background:
            linear-gradient(180deg, rgba(255,255,255,0.92) 0%, rgba(255,255,255,0.80) 100%),
            rgba(241,245,249,0.94);
        box-shadow:
            0 16px 32px rgba(15,23,42,0.06);
        transition:transform 0.35s cubic-bezier(.22,1,.36,1), border-color 0.35s ease, box-shadow 0.35s ease;
        position:relative;
        overflow:hidden;
    }
    .hero-stat-card::before {
        content:'';
        position:absolute;
        inset:-1px;
        background:linear-gradient(135deg, rgba(37,99,235,0.06), transparent 46%, transparent 60%, rgba(99,102,241,0.04));
        opacity:1;
        pointer-events:none;
    }
    .hero-stat-card:hover {
        transform:translateY(-6px);
        border-color:rgba(37,99,235,0.20);
        box-shadow:0 24px 52px rgba(15,23,42,0.10), inset 0 1px 0 rgba(255,255,255,0.90);
    }
    .hero-stat-num {
        font-family:'Fraunces',serif; font-size:32px; font-weight:700; color:var(--text); line-height:1;
        display:block;
        text-shadow:none;
        position:relative;
        z-index:1;
    }
    .hero-stat-label {
        font-size:11px; color:var(--muted); margin-top:6px;
        position:relative;
        z-index:1;
    }

    /* Right slideshow panel */
    .hero-right {
        position:relative;
        overflow:hidden;
        z-index:1;
        margin-left:-96px;
        padding-left:96px;
    }
    .hero-slideshow {
        position:absolute; inset:0;
        touch-action:pan-y;
    }
    .hslide {
        position:absolute; inset:0;
        display:flex; align-items:center; justify-content:center;
        opacity:0;
        transform:translate3d(100%, 0, 0);
        transition:transform 1.8s cubic-bezier(.19,1,.22,1), opacity 1.8s ease;
        will-change:transform, opacity;
        background:
            radial-gradient(circle at 50% 42%, rgba(37,99,235,0.04), transparent 42%),
            linear-gradient(180deg, rgba(241,245,249,0.96) 0%, rgba(232,237,243,0.98) 100%);
    }
    .hslide.active { opacity:1; transform:translate3d(0, 0, 0); z-index:3; }
    .hslide.prev { opacity:0.3; transform:translate3d(-100%, 0, 0); z-index:1; }
    .hslide.next { opacity:0.3; transform:translate3d(100%, 0, 0); z-index:2; }
    .hslide img {
        width:100%; height:100%;
        object-fit:contain;
        object-position:center;
        display:block;
        padding:0;
        transform:scale(1.02);
        transition:transform 1.8s cubic-bezier(.19,1,.22,1);
        position:relative;
        z-index:1;
    }
    .hslide.active img { transform:scale(1.06); }
    .hslide.prev img { transform:scale(1.02); }
    .hslide.next img { transform:scale(1.02); }
    /* Diagonal mask on left edge */
    .hero-right::before {
        content:none;
    }
    /* Gradient fade bottom */
    .hero-right::after {
        content:'';
        position:absolute; bottom:0; left:0; right:0; height:200px; z-index:3; pointer-events:none;
        background:linear-gradient(to top, var(--ink), transparent);
    }

    /* Slide controls */
    .hslide-dots {
        position:absolute; bottom:32px; right:32px; z-index:10;
        display:flex; gap:6px; align-items:center;
    }
    .hslide-nav {
        position:absolute; inset:0; z-index:10; pointer-events:none;
    }
    .hnav-btn {
        position:absolute; top:50%; transform:translateY(-50%);
        width:48px; height:48px; border-radius:50%;
        border:1px solid rgba(148,163,184,0.20);
        background:rgba(255,255,255,0.80);
        color:var(--text);
        backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px);
        cursor:pointer;
        pointer-events:auto;
        display:flex; align-items:center; justify-content:center;
        font-size:22px; line-height:1;
        transition:transform 0.25s ease, background 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
    }
    .hnav-btn:hover {
        background:rgba(255,255,255,0.95);
        border-color:rgba(37,99,235,0.25);
        box-shadow:0 10px 30px rgba(15,23,42,0.10);
    }
    .hnav-btn:active { transform:translateY(-50%) scale(0.96); }
    .hnav-prev { left:22px; }
    .hnav-next { right:22px; }
    .hsdot {
        width:8px; height:8px; border-radius:4px; border:none; cursor:pointer;
        background:rgba(148,163,184,0.30); padding:0;
        transition:width 0.3s, background 0.3s;
    }
    .hsdot.active { width:24px; background:var(--leaf); box-shadow:0 0 8px rgba(37,99,235,0.30); }

    /* Hero scroll hint */
    .scroll-hint {
        position:absolute; bottom:32px; left:64px; z-index:10;
        display:flex; align-items:center; gap:10px;
        font-size:11px; letter-spacing:1.5px; text-transform:uppercase; color:var(--muted);
    }
    .scroll-line { width:40px; height:1px; background:var(--muted); }

    /* ═══════════════════════════════════════════
       LOGIN CARDS ROW
    ═══════════════════════════════════════════ */
    .login-section {
        position:relative; z-index:1;
        padding:0 64px 100px;
    }

    .login-strip {
        background:var(--ink2);
        border:1px solid var(--line);
        border-radius:var(--radius-lg);
        display:grid; grid-template-columns:repeat(3,1fr);
        overflow:hidden;
    }

    .lcard {
        position:relative;
        padding:36px 32px;
        display:flex; flex-direction:column; gap:18px;
        cursor:pointer; text-decoration:none; color:inherit;
        overflow:hidden;
        transition:background 0.3s;
        border-right: 1px solid var(--line);
    }
    .lcard:last-child { border-right:none; }
    .lcard:hover { background:var(--surface2); }

    /* Accent color top indicator */
    .lcard::before {
        content:'';
        position:absolute; top:0; left:0; right:0; height:3px;
        background:var(--lc, var(--leaf));
        transform:scaleX(0); transform-origin:left;
        transition:transform 0.35s cubic-bezier(.22,1,.36,1);
    }
    .lcard:hover::before { transform:scaleX(1); }

    .lc-siswa   { --lc:#60a5fa; }
    .lc-ortu    { --lc:#3b82f6; }
    .lc-guru    { --lc:#2563eb; }

    .lcard-icon {
        width:56px; height:56px; border-radius:16px;
        background:var(--lc-bg, rgba(37,99,235,0.08));
        border:1px solid var(--lc-border, rgba(37,99,235,0.14));
        display:flex; align-items:center; justify-content:center;
        font-size:26px;
        transition:transform 0.25s;
    }
    .lcard:hover .lcard-icon { transform:scale(1.08) rotate(-5deg); }
    .lc-siswa .lcard-icon { --lc-bg:rgba(96,165,250,0.08);  --lc-border:rgba(96,165,250,0.18); }
    .lc-ortu  .lcard-icon { --lc-bg:rgba(59,130,246,0.08);  --lc-border:rgba(59,130,246,0.18); }
    .lc-guru  .lcard-icon { --lc-bg:rgba(37,99,235,0.08);  --lc-border:rgba(37,99,235,0.18); }

    .lcard-title { font-size:17px; font-weight:800; color:var(--text); }
    .lcard-sub   { font-size:13px; color:var(--muted); line-height:1.6; flex:1; }
    .lcard-arrow {
        display:inline-flex; align-items:center; justify-content:center;
        width:32px; height:32px; border-radius:50%;
        border:1px solid var(--line2); color:var(--text);
        font-size:16px; font-weight:700;
        transition:background 0.22s, transform 0.22s, border-color 0.22s;
        align-self:flex-end;
    }
    .lcard:hover .lcard-arrow { background:var(--lc); border-color:var(--lc); color:#ffffff; transform:translateX(4px); }

    /* ═══════════════════════════════════════════
       SECTION COMMON
    ═══════════════════════════════════════════ */
    .section {
        position:relative; z-index:1;
        padding: 100px 64px;
    }
    .section-label {
        display:inline-flex; align-items:center; gap:8px;
        font-size:11px; font-weight:700; letter-spacing:2.5px; text-transform:uppercase;
        color:var(--leaf); margin-bottom:16px;
    }
    .section-label::before { content:''; width:20px; height:2px; background:var(--leaf); border-radius:2px; }
    .section-title {
        font-family:'Fraunces', serif;
        font-size:clamp(26px, 3.8vw, 46px);
        font-weight:900; letter-spacing:-0.025em; line-height:1.1;
        color:var(--text); margin-bottom:14px;
    }

    .section-desc { font-size:15px; color:var(--muted); line-height:1.8; max-width:560px; }

    /* ═══════════════════════════════════════════
       HABITS — HORIZONTAL SCROLL TRACK
    ═══════════════════════════════════════════ */
    .habits-section { padding-bottom:0; }
    .habits-header { margin-bottom:48px; }

    .habits-track-wrap {
        position:relative;
        margin:0 -64px;
        padding:0 64px 60px;
        overflow-x:auto;
        scrollbar-width:none;
        -ms-overflow-style:none;
        cursor:grab;
    }
    .habits-track-wrap::-webkit-scrollbar { display:none; }
    .habits-track-wrap:active { cursor:grabbing; }

    .habits-track {
        display:flex;
        gap:16px;
        width:max-content;
    }

    .hcard {
        width:280px;
        flex-shrink:0;
        background:var(--ink2);
        border:1px solid var(--line);
        border-radius:var(--radius-lg);
        padding:28px 24px;
        position:relative;
        overflow:hidden;
        transition:transform 0.28s cubic-bezier(.22,1,.36,1), box-shadow 0.28s ease, border-color 0.28s;
        cursor:default;
    }
    .hcard:hover {
        transform:translateY(-10px);
        box-shadow:0 24px 60px rgba(15,23,42,0.10), 0 0 0 1px var(--hcc, rgba(37,99,235,0.18));
        border-color:var(--hcc, rgba(37,99,235,0.18));
    }
    /* Big watermark number */
    .hcard-watermark {
        position:absolute;
        right:-10px; top:-10px;
        font-family:'Fraunces',serif;
        font-size:130px; font-weight:900; line-height:1;
        color:var(--hcc, rgba(37,99,235,0.06));
        opacity:0.10;
        user-select:none; pointer-events:none;
        letter-spacing:-0.05em;
    }
    .hcard:hover .hcard-watermark { opacity:0.22; }
    .hcard-emoji {
        font-size:42px;
        display:block;
        margin-bottom:20px;
        transition:transform 0.25s;
    }
    .hcard:hover .hcard-emoji { transform:scale(1.15) rotate(-8deg); }
    .hcard-num {
        display:inline-block;
        font-size:10px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase;
        color:var(--hcl, var(--leaf));
        background:var(--hcbg, rgba(37,99,235,0.06));
        border:1px solid var(--hcbg2, rgba(37,99,235,0.12));
        padding:3px 10px; border-radius:999px;
        margin-bottom:11px;
    }
    .hcard-title { font-size:15px; font-weight:800; color:var(--text); margin-bottom:8px; line-height:1.3; }
    .hcard-body  { font-size:13px; color:var(--muted); line-height:1.75; }

    /* Per-card accent vars */
    .hc1{--hcc:rgba(96,165,250,0.18);--hcl:#3b82f6;--hcbg:rgba(96,165,250,0.06);--hcbg2:rgba(96,165,250,0.14);}
    .hc2{--hcc:rgba(59,130,246,0.18);--hcl:#2563eb;--hcbg:rgba(59,130,246,0.06);--hcbg2:rgba(59,130,246,0.14);}
    .hc3{--hcc:rgba(37,99,235,0.18);--hcl:#1d4ed8;--hcbg:rgba(37,99,235,0.06);--hcbg2:rgba(37,99,235,0.14);}
    .hc4{--hcc:rgba(79,70,229,0.18);--hcl:#6366f1;--hcbg:rgba(79,70,229,0.06);--hcbg2:rgba(79,70,229,0.14);}
    .hc5{--hcc:rgba(96,165,250,0.18);--hcl:#3b82f6;--hcbg:rgba(96,165,250,0.06);--hcbg2:rgba(96,165,250,0.14);}
    .hc6{--hcc:rgba(59,130,246,0.14);--hcl:#2563eb;--hcbg:rgba(59,130,246,0.05);--hcbg2:rgba(59,130,246,0.10);}
    .hc7{--hcc:rgba(37,99,235,0.14);--hcl:#1d4ed8;--hcbg:rgba(37,99,235,0.05);--hcbg2:rgba(37,99,235,0.10);}

    /* Drag progress track */
    .habits-progress {
        display:flex; align-items:center; gap:12px;
        padding:0 64px; padding-bottom:80px; margin-top:-20px;
    }
    .hpbar { flex:1; height:2px; background:var(--line); border-radius:2px; overflow:hidden; }
    .hpfill { height:100%; width:0%; background:var(--leaf); border-radius:2px; transition:width 0.1s linear; }
    .hp-label { font-size:11px; color:var(--muted); white-space:nowrap; }

    /* ═══════════════════════════════════════════
       TUJUAN — SPLIT BENTO
    ═══════════════════════════════════════════ */
    .tujuan-section { background:var(--ink2); }

    .tujuan-inner {
        display:grid; grid-template-columns:1fr 1.4fr; gap:48px; align-items:start;
    }
    .tujuan-left { position:sticky; top:100px; }
    .tujuan-tagline {
        font-family:'Fraunces',serif;
        font-size:clamp(28px, 3.2vw, 42px);
        font-weight:700; font-style:italic;
        line-height:1.2; letter-spacing:-0.02em;
        color:var(--text); margin-bottom:20px;
    }
    .tujuan-tagline strong { font-style:normal; font-weight:900; color:var(--leaf); }
    .tujuan-para { font-size:14.5px; line-height:1.9; color:var(--muted); margin-bottom:24px; }
    .tujuan-pill-row { display:flex; flex-wrap:wrap; gap:8px; }
    .tujuan-pill {
        font-size:12px; font-weight:700;
        padding:7px 15px; border-radius:999px;
        border:1px solid var(--line2);
        color:var(--text); background:var(--surface);
        transition:background 0.2s, border-color 0.2s, transform 0.2s;
        cursor:default;
    }
    .tujuan-pill:hover { background:rgba(37,99,235,0.06); border-color:rgba(37,99,235,0.18); transform:scale(1.04); }

    /* Right: profil cards */
    .profil-grid {
        display:grid; grid-template-columns:1fr 1fr; gap:14px;
    }
    .pcard {
        background:var(--ink3);
        border:1px solid var(--line);
        border-radius:var(--radius);
        padding:22px 20px;
        position:relative; overflow:hidden;
        transition:transform 0.25s, border-color 0.25s, box-shadow 0.25s;
        cursor:default;
    }
    .pcard:hover {
        transform:translateY(-6px);
        border-color:var(--pc, rgba(37,99,235,0.22));
        box-shadow:0 16px 44px rgba(15,23,42,0.08), 0 0 0 1px var(--pc, rgba(37,99,235,0.12));
    }
    /* Corner accent dot */
    .pcard::after {
        content:'';
        position:absolute; top:18px; right:18px;
        width:8px; height:8px; border-radius:50%;
        background:var(--pc, rgba(37,99,235,0.40));
        opacity:0.7;
    }
    .pcard-icon { font-size:28px; margin-bottom:12px; display:block; }
    .pcard-name { font-size:14px; font-weight:800; color:var(--text); margin-bottom:5px; }
    .pcard-desc { font-size:12.5px; color:var(--muted); line-height:1.65; }
    .pc1{--pc:rgba(96,165,250,0.25);} .pc2{--pc:rgba(59,130,246,0.25);}
    .pc3{--pc:rgba(37,99,235,0.25);} .pc4{--pc:rgba(79,70,229,0.25);}
    .pc5{--pc:rgba(96,165,250,0.25);} .pc6{--pc:rgba(59,130,246,0.18);}

    /* ═══════════════════════════════════════════
       VISI MISI
    ═══════════════════════════════════════════ */
    .visimisi-section { background:var(--ink); }
    .visimisi-inner {
        display:grid; grid-template-columns:1fr 1.6fr; gap:56px; align-items:start;
    }
    .visi-box {
        background:var(--ink2);
        border:1px solid var(--line);
        border-radius:var(--radius-lg);
        padding:32px 28px;
        position:relative; overflow:hidden;
    }
    .visi-box::before {
        content:'';
        position:absolute; top:0; left:0; right:0; height:3px;
        background:linear-gradient(90deg, var(--leaf), var(--gold));
    }
    .visi-label {
        font-size:10px; font-weight:800; letter-spacing:2px; text-transform:uppercase;
        color:var(--leaf); margin-bottom:14px; display:block;
    }
    .visi-text {
        font-family:'Fraunces',serif;
        font-size:clamp(15px, 1.6vw, 18px);
        font-weight:700; font-style:italic;
        line-height:1.65; letter-spacing:-0.01em;
        color:var(--text);
    }
    .misi-list { display:flex; flex-direction:column; gap:12px; }
    .misi-group {
        background:var(--ink2);
        border:1px solid var(--line);
        border-radius:var(--radius);
        overflow:hidden;
        transition:border-color 0.25s;
    }
    .misi-group:hover { border-color:rgba(37,99,235,0.16); }
    .misi-group-header {
        display:flex; align-items:center; gap:14px;
        padding:16px 20px;
        cursor:pointer;
        user-select:none;
    }
    .misi-group-icon {
        width:38px; height:38px; border-radius:12px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
        font-size:18px;
        background:var(--mi-bg, rgba(37,99,235,0.06));
        border:1px solid var(--mi-border, rgba(37,99,235,0.12));
    }
    .misi-group-title { font-size:13px; font-weight:800; color:var(--text); flex:1; line-height:1.3; }
    .misi-chevron {
        width:20px; height:20px; border-radius:50%;
        border:1px solid var(--line2);
        display:flex; align-items:center; justify-content:center;
        font-size:10px; color:var(--muted); flex-shrink:0;
        transition:transform 0.25s, background 0.25s;
    }
    .misi-group.open .misi-chevron { transform:rotate(180deg); background:rgba(37,99,235,0.08); }
    .misi-group-body {
        max-height:0; overflow:hidden;
        transition:max-height 0.35s ease;
        padding:0 20px;
    }
    .misi-group.open .misi-group-body { max-height:200px; }
    .misi-item {
        display:flex; align-items:flex-start; gap:10px;
        padding:8px 0;
        border-top:1px solid var(--line);
        font-size:12px; color:var(--muted); line-height:1.6;
    }
    .misi-item::before { content:'›'; color:var(--leaf); font-size:15px; line-height:1.4; flex-shrink:0; }
    /* Misi group colour vars */
    .mg1{--mi-bg:rgba(96,165,250,0.06);--mi-border:rgba(96,165,250,0.12);}
    .mg2{--mi-bg:rgba(59,130,246,0.06);--mi-border:rgba(59,130,246,0.12);}
    .mg3{--mi-bg:rgba(37,99,235,0.06);--mi-border:rgba(37,99,235,0.12);}
    .mg4{--mi-bg:rgba(79,70,229,0.06);--mi-border:rgba(79,70,229,0.12);}
    .mg5{--mi-bg:rgba(96,165,250,0.06);--mi-border:rgba(96,165,250,0.12);}

    .footer {
        position:relative; z-index:1;
        border-top:1px solid var(--line);
        padding:32px 64px;
        display:flex; align-items:center; justify-content:space-between;
        gap:16px; flex-wrap:wrap;
    }
    .footer-copy { font-size:12px; color:var(--muted); }
    .footer-mark { font-family:'Fraunces',serif; font-size:14px; font-weight:700; color:var(--leaf); }
    .footer-right { display:flex; align-items:center; gap:16px; }
    .footer-admin-link {
        width:28px; height:28px; border-radius:50%;
        display:inline-flex; align-items:center; justify-content:center;
        border:1px solid rgba(148,163,184,0.18);
        color:var(--muted); text-decoration:none;
        font-size:13px; line-height:1;
        opacity:0.25; transition:opacity 0.2s, border-color 0.2s, background 0.2s;
    }
    .footer-admin-link:hover { opacity:0.7; border-color:rgba(148,163,184,0.35); background:var(--surface2); }

    /* Admin modal accent overrides */
    #modal-admin .modal-box::before {
        background:linear-gradient(90deg, transparent, #3b82f6, transparent);
    }
    #modal-admin .modal-submit {
        background:#2563eb; color:#ffffff;
        box-shadow:0 4px 18px rgba(37,99,235,0.22);
    }
    #modal-admin .modal-submit:hover {
        background:#1d4ed8;
        box-shadow:0 8px 28px rgba(37,99,235,0.32);
    }
    #modal-admin .modal-submit:disabled {
        opacity:0.55; cursor:not-allowed; transform:none;
    }

    /* ═══════════════════════════════════════════
       MODALS
    ═══════════════════════════════════════════ */
    .modal-overlay {
        display:none; position:fixed; inset:0;
        background:rgba(15,23,42,0.50);
        backdrop-filter:blur(18px); -webkit-backdrop-filter:blur(18px);
        z-index:500; align-items:center; justify-content:center; padding:20px;
    }
    .modal-overlay.active { display:flex; animation:mFade 0.18s ease; }
    @keyframes mFade { from{opacity:0;}to{opacity:1;} }
    .modal-box {
        width:100%; max-width:420px;
        background:var(--ink2);
        border:1px solid var(--line2);
        border-radius:var(--radius-lg);
        padding:32px 28px 26px;
        animation:mSlide 0.26s cubic-bezier(.34,1.56,.64,1);
        position:relative; overflow:hidden;
    }
    .modal-box::before {
        content:''; position:absolute; top:0; left:0; right:0; height:2px;
        background:linear-gradient(90deg, transparent, var(--leaf), transparent);
    }
    @keyframes mSlide { from{opacity:0;transform:translateY(24px) scale(0.95);}to{opacity:1;transform:none;} }
    .modal-emoji { font-size:38px; margin-bottom:14px; display:block; }
    .modal-title { font-family:'Fraunces',serif; font-size:22px; font-weight:700; color:var(--text); margin-bottom:4px; }
    .modal-sub { font-size:13px; color:var(--muted); margin-bottom:20px; }

    .form-field { margin-top:14px; }
    .form-field label { display:block; font-size:12px; font-weight:700; color:var(--muted); margin-bottom:6px; letter-spacing:0.3px; }
    .form-field input {
        width:100%; padding:11px 14px;
        background:var(--surface2); border:1px solid var(--line);
        border-radius:11px; color:var(--text); font-size:14px;
        font-family:'Plus Jakarta Sans',sans-serif;
        transition:border-color 0.2s, background 0.2s, box-shadow 0.2s;
    }
    .form-field input::placeholder { color:rgba(100,116,139,0.40); }
    .form-field input:focus { outline:none; border-color:var(--leaf); background:rgba(37,99,235,0.03); box-shadow:0 0 0 3px rgba(37,99,235,0.08); }
    .form-hint { font-size:11px; color:var(--muted); margin-top:4px; opacity:0.7; }
    .modal-submit {
        width:100%; margin-top:18px; padding:13px;
        background:var(--leaf); color:#ffffff;
        border:none; border-radius:12px; cursor:pointer;
        font-weight:800; font-size:14px; font-family:'Plus Jakarta Sans',sans-serif;
        transition:transform 0.2s, box-shadow 0.2s, background 0.2s;
        box-shadow:0 4px 18px rgba(37,99,235,0.22);
    }
    .modal-submit:hover { transform:translateY(-2px); box-shadow:0 8px 28px rgba(37,99,235,0.32); }
    .modal-cancel {
        width:100%; margin-top:8px; padding:11px;
        background:transparent; color:var(--muted);
        border:1px solid var(--line); border-radius:12px; cursor:pointer;
        font-size:13px; font-weight:700; font-family:'Plus Jakarta Sans',sans-serif;
        transition:border-color 0.2s, color 0.2s;
    }
    .modal-cancel:hover { border-color:var(--line2); color:var(--text); }

    /* ═══════════════════════════════════════════
       SCROLL REVEAL
    ═══════════════════════════════════════════ */
    .reveal { opacity:0; transform:translateY(30px); transition:opacity 0.6s ease, transform 0.6s ease; }
    .reveal.on { opacity:1; transform:none; }
    .d1{transition-delay:.08s;} .d2{transition-delay:.16s;} .d3{transition-delay:.24s;}
    .d4{transition-delay:.32s;} .d5{transition-delay:.40s;} .d6{transition-delay:.48s;}

    /* ═══════════════════════════════════════════
       RESPONSIVE
    ═══════════════════════════════════════════ */
    @media(max-width:900px){
        .hero { grid-template-columns:1fr; min-height:auto; }
        .hero-right { height:380px; position:relative; margin-left:0; padding-left:0; }
        .hero-right::before { background:linear-gradient(180deg, var(--ink) 0%, transparent 30%); }
        .hero-left {
            padding:40px 28px;
            margin-right:0;
        }
        .hero-left::before {
            inset:0;
            transform:none;
            border-radius:0;
            animation:none;
        }
        .hero-left::after { content:none; }
        .scroll-hint { left:28px; bottom:16px; }
        .login-section { padding:0 20px 60px; }
        .login-strip { grid-template-columns:1fr; }
        .lcard { border-right:none; border-bottom:1px solid var(--line); }
        .lcard:last-child { border-bottom:none; }
        .section { padding:70px 24px; }
        .habits-track-wrap { margin:0 -24px; padding:0 24px 48px; }
        .habits-progress { padding:0 24px 60px; }
        .tujuan-inner { grid-template-columns:1fr; gap:32px; }
        .tujuan-left { position:static; }
        .profil-grid { grid-template-columns:1fr 1fr; }
        .footer { padding:24px 24px; flex-direction:column; text-align:center; }
        .nav { padding:0 20px; }
        .hero-stat-row { gap:20px; }
        .nav-wordmark { font-size:14px; }
        .nav-wordmark .nav-school { font-size:11px; }
        .nav-logo { width:46px; height:46px; }
        .hnav-btn { width:38px; height:38px; font-size:18px; }
        .hnav-prev { left:10px; }
        .hnav-next { right:10px; }
        .hslide-dots { bottom:20px; right:20px; }
        .visimisi-inner { grid-template-columns:1fr; gap:32px; }
    }
    @media(max-width:520px){
        .profil-grid { grid-template-columns:1fr; }
        .hcard { width:240px; }
        .hero-stat-row { gap:12px; }
        .hero-stat-card { min-width:calc(50% - 6px); padding:14px 14px 12px; }
        .hero-stat-num { font-size:24px; }
        .hero-stat-label { font-size:11px; }
        .hero-h1 { font-size:clamp(28px, 8vw, 38px); }
        .hero-sub { font-size:15px; }
        .hero-eyebrow { font-size:10px; padding:6px 12px; letter-spacing:1.5px; }
        .hero-right { height:280px; }
        .section-title { font-size:clamp(22px, 6vw, 32px); }
        .section-label { font-size:11px; letter-spacing:2px; }
        .section-desc { font-size:15px; }
        .login-section { padding:0 14px 40px; }
        .lcard { padding:20px; }
        .lcard-icon { width:44px; height:44px; border-radius:12px; font-size:22px; }
        .lcard-title { font-size:15px; }
        .lcard-sub { font-size:13px; }
        .section { padding:50px 16px; }
        .habits-track-wrap { margin:0 -16px; padding:0 16px 40px; }
        .hcard { width:220px; padding:22px 18px; }
        .hcard-emoji { font-size:34px !important; width:48px !important; height:48px !important; border-radius:12px !important; }
        .hcard-title { font-size:14px; }
        .hcard-body { font-size:13px; }
        .tujuan-tagline { font-size:clamp(22px, 5.5vw, 32px); }
        .tujuan-para { font-size:14.5px; }
        .tujuan-pill { font-size:11.5px; padding:7px 13px; }
        .pcard { padding:16px 14px; }
        .pcard-icon { width:38px !important; height:38px !important; border-radius:10px !important; font-size:20px !important; }
        .pcard-title { font-size:13px; }
        .pcard-sub { font-size:12px; }
        .misi-group { padding:14px 16px; }
        .misi-group-icon { width:32px; height:32px; font-size:15px; border-radius:10px; }
        .misi-group-title { font-size:13px; }
        .misi-group-body ul li { font-size:12.5px; }
        .footer { padding:18px 16px; }
        .footer-copy { font-size:12px; }
        .modal-overlay { padding:12px; }
        .modal-box { padding:24px 20px 20px; max-width:100%; border-radius:20px; }
        .modal-title { font-size:18px; }
        .modal-emoji { font-size:32px !important; width:52px !important; height:52px !important; }
        .form-field input { font-size:16px; padding:10px 12px; }
        .modal-sub { font-size:13px; }
        .modal-submit { padding:12px; font-size:14px; }
        .nav { height:56px; padding:0 14px; }
        .nav-logo { width:40px; height:40px; }
        .nav-wordmark { font-size:14px; }
        .nav-wordmark .nav-school { font-size:11px; }
        .nav-badge { font-size:10px; padding:4px 10px; letter-spacing:1px; }
        .btn-primary { font-size:14px; padding:11px 20px; }
        .btn-outline { font-size:13px; padding:10px 18px; }
        .scroll-hint { display:none; }
        .hnav-btn { width:34px; height:34px; font-size:16px; }
        .hnav-prev { left:8px; }
        .hnav-next { right:8px; }
        .mouse-glow { display:none; }
    }
    @media(max-width:360px){
        .hero-left { padding:30px 16px; }
        .hero-h1 { font-size:26px; }
        .hero-stat-card { min-width:100%; }
        .hcard { width:200px; padding:18px 14px; }
        .login-section { padding:0 10px 30px; }
        .section { padding:40px 12px; }
        .nav-wordmark { display:none; }
        .nav-badge { font-size:9px; padding:3px 8px; }
    }

    /* ═══════════════════════════════════════════
       ENHANCED 3D & VISUAL EFFECTS
    ═══════════════════════════════════════════ */

    /* Scroll Progress Bar */
    .scroll-progress {
        position:fixed; top:0; left:0; height:3px; z-index:999;
        background:linear-gradient(90deg, var(--leaf), var(--lilac), var(--gold));
        width:0%; transition:width 0.08s linear;
    }

    /* Mouse Spotlight */
    .mouse-glow {
        position:fixed; width:500px; height:500px; border-radius:50%;
        background:radial-gradient(circle, rgba(37,99,235,0.025) 0%, transparent 70%);
        pointer-events:none; z-index:1; transform:translate(-50%,-50%);
        transition:opacity 0.4s; opacity:0;
    }
    body:hover .mouse-glow { opacity:1; }

    /* Particles */
    .particles { position:fixed; inset:0; z-index:0; pointer-events:none; overflow:hidden; }
    .particle {
        position:absolute; border-radius:50%; opacity:0;
        animation:particleFloat linear infinite;
    }
    @keyframes particleFloat {
        0%   { opacity:0; transform:translateY(100vh) scale(0); }
        8%   { opacity:0.7; }
        85%  { opacity:0.15; }
        100% { opacity:0; transform:translateY(-10vh) scale(1); }
    }

    /* New keyframes */
    @keyframes glowPulse {
        0%,100% { box-shadow:0 0 20px rgba(37,99,235,0.08), 0 0 60px rgba(37,99,235,0.02); }
        50%     { box-shadow:0 0 30px rgba(37,99,235,0.14), 0 0 80px rgba(37,99,235,0.04); }
    }
    @keyframes gradientShift {
        0%   { background-position:0% 50%; }
        50%  { background-position:100% 50%; }
        100% { background-position:0% 50%; }
    }
    @keyframes shimmer {
        0%   { background-position:-200% 0; }
        100% { background-position:200% 0; }
    }
    @keyframes blobGlow {
        0%   { opacity:0.08; filter:blur(110px); }
        100% { opacity:0.14; filter:blur(140px); }
    }
    @keyframes floatY {
        0%,100% { transform:translateY(0); }
        50%     { transform:translateY(-12px); }
    }

    /* Enhanced Blobs */
    .blob { animation:blobDrift 26s ease-in-out infinite, blobGlow 8s ease-in-out infinite alternate !important; }
    .b4 { width:420px; height:420px; background:var(--lilac); top:60%; left:10%; animation-delay:-14s; }

    /* Enhanced Navbar — Glassmorphism Pro */
    .nav {
        background:rgba(255,255,255,0.60) !important;
        backdrop-filter:blur(36px) saturate(1.8) !important;
        -webkit-backdrop-filter:blur(36px) saturate(1.8) !important;
        border-bottom:1px solid rgba(148,163,184,0.12) !important;
        transition:all 0.5s cubic-bezier(.22,1,.36,1);
    }
    .nav.scrolled {
        background:rgba(255,255,255,0.88) !important;
        border-bottom-color:rgba(148,163,184,0.18) !important;
        box-shadow:0 8px 40px rgba(15,23,42,0.06), 0 0 0 1px rgba(148,163,184,0.08);
    }
    .nav-logo { transition:transform 0.4s cubic-bezier(.22,1,.36,1), box-shadow 0.4s ease; }
    .nav-brand:hover .nav-logo {
        transform:scale(1.12) rotate(6deg);
        box-shadow:0 0 22px rgba(37,99,235,0.20);
    }
    .nav-badge {
        box-shadow:0 0 14px rgba(37,99,235,0.08);
        animation:glowPulse 5s ease-in-out infinite;
    }

    /* Animated Gradient Hero Title */
    .hero-h1 em {
        background:linear-gradient(135deg, var(--leaf), var(--lilac), var(--sky), var(--leaf)) !important;
        background-size:300% 300% !important;
        -webkit-background-clip:text !important;
        -webkit-text-fill-color:transparent !important;
        animation:gradientShift 5s ease infinite;
    }
    .hero-h1 .outline-text {
        text-shadow:0 0 40px rgba(37,99,235,0.15), 0 0 80px rgba(37,99,235,0.04) !important;
    }

    /* 3D Transform Base */
    .hero-stat-card, .lcard, .hcard, .pcard, .misi-group, .visi-box {
        transform-style:preserve-3d;
    }

    /* Hero Stat Cards — 3D Glass */
    .hero-stat-card {
        background:linear-gradient(135deg, rgba(255,255,255,0.85) 0%, rgba(255,255,255,0.70) 100%), rgba(241,245,249,0.90) !important;
        backdrop-filter:blur(24px); -webkit-backdrop-filter:blur(24px);
        border:1px solid rgba(148,163,184,0.14) !important;
        animation:glowPulse 6s ease-in-out infinite, floatY 6s ease-in-out infinite;
    }
    .hero-stat-card:nth-child(2) { animation-delay:-2s, -2s; }
    .hero-stat-card:nth-child(3) { animation-delay:-4s, -4s; }
    .hero-stat-card:hover {
        border-color:rgba(37,99,235,0.20) !important;
        box-shadow:0 32px 64px rgba(15,23,42,0.08), 0 0 30px rgba(37,99,235,0.06), inset 0 1px 0 rgba(255,255,255,0.90) !important;
    }
    .hero-stat-num {
        background:linear-gradient(180deg, var(--text), rgba(30,41,59,0.65));
        -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    }

    /* Login Strip — Glass */
    .login-strip {
        background:rgba(255,255,255,0.70) !important;
        backdrop-filter:blur(28px); -webkit-backdrop-filter:blur(28px);
        border:1px solid rgba(148,163,184,0.14) !important;
        box-shadow:0 24px 64px rgba(15,23,42,0.06);
        overflow:visible !important;
    }
    .lcard { position:relative; }
    .lcard:hover {
        transform:translateY(-10px) scale(1.02) !important;
        background:rgba(37,99,235,0.02) !important;
        box-shadow:0 24px 56px rgba(15,23,42,0.08), 0 0 20px rgba(37,99,235,0.04);
        z-index:2;
    }
    .lcard-icon { transition:transform 0.4s cubic-bezier(.22,1,.36,1), box-shadow 0.35s ease !important; }
    .lcard:hover .lcard-icon {
        transform:scale(1.18) rotate(-10deg) translateZ(20px) !important;
        box-shadow:0 14px 32px rgba(15,23,42,0.10);
    }

    /* Habit Cards — 3D Depth */
    .hcard {
        background:linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01)), var(--ink2) !important;
        backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px);
    }
    .hcard:hover {
        transform:translateY(-16px) rotateX(3deg) scale(1.03) !important;
        box-shadow:0 32px 72px rgba(15,23,42,0.10), 0 0 0 1px var(--hcc, rgba(37,99,235,0.18)), 0 0 32px var(--hcc, rgba(37,99,235,0.04)) !important;
    }
    .hcard .hcard-emoji { filter:drop-shadow(0 4px 12px rgba(15,23,42,0.08)); }
    .hcard:hover .hcard-emoji {
        transform:scale(1.3) rotate(-12deg) translateZ(30px) !important;
        filter:drop-shadow(0 10px 24px rgba(15,23,42,0.12));
    }

    /* Profil Cards — 3D Glass */
    .pcard {
        background:linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01)), var(--ink3) !important;
        backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px);
    }
    .pcard:hover {
        transform:translateY(-12px) rotateX(4deg) scale(1.03) !important;
        box-shadow:0 28px 56px rgba(15,23,42,0.08), 0 0 28px var(--pc, rgba(37,99,235,0.06)), 0 0 0 1px var(--pc, rgba(37,99,235,0.12)) !important;
    }
    .pcard .pcard-icon { transition:transform 0.35s cubic-bezier(.22,1,.36,1); }
    .pcard:hover .pcard-icon { transform:scale(1.35) translateZ(20px); }

    /* Visi Box — Animated Gradient Border */
    .visi-box {
        background:linear-gradient(135deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)), var(--ink2) !important;
        backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px);
    }
    .visi-box::before {
        background:linear-gradient(90deg, var(--leaf), var(--gold), var(--lilac), var(--leaf)) !important;
        background-size:200% 100% !important;
        animation:shimmer 4s linear infinite;
    }

    /* Misi — Enhanced */
    .misi-group:hover {
        border-color:rgba(37,99,235,0.18) !important;
        box-shadow:0 10px 28px rgba(15,23,42,0.06), 0 0 16px rgba(37,99,235,0.03);
    }
    .misi-group.open {
        border-color:rgba(37,99,235,0.12);
        box-shadow:0 14px 36px rgba(15,23,42,0.08), 0 0 20px rgba(37,99,235,0.03);
    }

    /* Primary Button — Gradient + Shimmer */
    .btn-primary {
        background:linear-gradient(135deg, var(--leaf), var(--leaf-d)) !important;
        color:#ffffff !important;
        box-shadow:0 6px 24px rgba(37,99,235,0.20), 0 0 16px rgba(37,99,235,0.04) !important;
        position:relative; overflow:hidden;
    }
    .btn-primary::after {
        content:''; position:absolute; inset:0;
        background:linear-gradient(90deg, transparent, rgba(255,255,255,0.18), transparent);
        transform:translateX(-100%); transition:transform 0.6s ease;
    }
    .btn-primary:hover::after { transform:translateX(100%); }
    .btn-primary:hover {
        box-shadow:0 14px 40px rgba(37,99,235,0.30), 0 0 28px rgba(37,99,235,0.06) !important;
        background:linear-gradient(135deg, #3b82f6, var(--leaf)) !important;
    }

    /* Section Label Glow */
    .section-label { text-shadow:0 0 20px rgba(37,99,235,0.12); }
    .section-label::before { box-shadow:0 0 10px rgba(37,99,235,0.18); }

    /* Tujuan Pills — 3D Hover */
    .tujuan-pill {
        transition:background 0.2s, border-color 0.2s, transform 0.3s cubic-bezier(.22,1,.36,1), box-shadow 0.3s !important;
    }
    .tujuan-pill:hover {
        transform:scale(1.08) translateY(-4px) !important;
        box-shadow:0 10px 24px rgba(15,23,42,0.06), 0 0 14px rgba(37,99,235,0.04);
    }

    /* Enhanced Modal — 3D Glass */
    .modal-overlay { backdrop-filter:blur(22px) saturate(1.3) !important; -webkit-backdrop-filter:blur(22px) saturate(1.3) !important; }
    .modal-box {
        background:linear-gradient(135deg, rgba(255,255,255,0.96), rgba(241,245,249,0.98)) !important;
        backdrop-filter:blur(44px) saturate(1.5) !important;
        -webkit-backdrop-filter:blur(44px) saturate(1.5) !important;
        border:1px solid rgba(148,163,184,0.16) !important;
        box-shadow:0 36px 88px rgba(15,23,42,0.10), 0 0 44px rgba(37,99,235,0.03), inset 0 1px 0 rgba(255,255,255,0.90) !important;
        animation:modal3DIn 0.45s cubic-bezier(.34,1.56,.64,1) !important;
    }
    @keyframes modal3DIn {
        from { opacity:0; transform:translateY(44px) scale(0.88) perspective(800px) rotateX(12deg); }
        to   { opacity:1; transform:none; }
    }
    .modal-box::before {
        height:3px !important;
        background:linear-gradient(90deg, transparent, var(--leaf), var(--lilac), transparent) !important;
        background-size:200% 100% !important;
        animation:shimmer 3s linear infinite !important;
    }
    .form-field input:focus {
        border-color:var(--leaf) !important;
        background:rgba(37,99,235,0.03) !important;
        box-shadow:0 0 0 3px rgba(37,99,235,0.06), 0 0 22px rgba(37,99,235,0.03) !important;
    }
    .modal-submit {
        background:linear-gradient(135deg, var(--leaf), var(--leaf-d)) !important;
        color:#ffffff !important;
        box-shadow:0 4px 18px rgba(37,99,235,0.18), 0 0 18px rgba(37,99,235,0.04) !important;
    }
    .modal-submit:hover {
        transform:translateY(-3px) !important;
        box-shadow:0 10px 32px rgba(37,99,235,0.26), 0 0 28px rgba(37,99,235,0.06) !important;
    }

    /* Footer Brand Gradient */
    .footer-mark {
        background:linear-gradient(135deg, var(--leaf), var(--lilac));
        -webkit-background-clip:text; -webkit-text-fill-color:transparent; text-shadow:none;
    }

    /* Enhanced Scroll Reveal */
    .reveal {
        opacity:0 !important; transform:translateY(44px) scale(0.97) !important;
        transition:opacity 0.75s cubic-bezier(.22,1,.36,1), transform 0.75s cubic-bezier(.22,1,.36,1) !important;
    }
    .reveal.on { opacity:1 !important; transform:none !important; }

    /* Eyebrow Glow */
    .hero-eyebrow { position:relative; }
    .hero-eyebrow::after {
        content:''; position:absolute; inset:-5px; border-radius:999px; z-index:-1;
        background:radial-gradient(ellipse, rgba(37,99,235,0.06), transparent 70%);
        filter:blur(8px);
    }

    /* Interactive 3D Tilt (JS-driven) */
    [data-tilt] { transition:transform 0.12s ease-out; }

    /* ═══════════════════════════════════════════
       3D ICON SYSTEM (Spline-inspired)
    ═══════════════════════════════════════════ */
    @keyframes iconGlowPulse {
        0%, 100% { box-shadow:0 0 12px var(--ig, rgba(37,99,235,0.08)), 0 6px 20px rgba(15,23,42,0.06); }
        50%      { box-shadow:0 0 28px var(--ig, rgba(37,99,235,0.18)), 0 10px 36px rgba(15,23,42,0.10); }
    }
    @keyframes icon3dFloat {
        0%, 100% { transform:translateY(0) rotateX(0deg) rotateY(0deg); }
        25%      { transform:translateY(-5px) rotateX(3deg) rotateY(-3deg); }
        50%      { transform:translateY(-8px) rotateX(-2deg) rotateY(4deg); }
        75%      { transform:translateY(-4px) rotateX(1deg) rotateY(-2deg); }
    }
    @keyframes iconBorderSpin { to { transform:rotate(360deg); } }

    /* ── Login Card 3D Icons ── */
    .lcard-icon { position:relative !important; overflow:hidden !important; }
    .lcard-icon svg {
        position:relative; z-index:2;
        width:28px; height:28px;
        stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; fill:none;
        transition:transform 0.5s cubic-bezier(.22,1,.36,1), filter 0.5s ease;
    }
    .lc-siswa .lcard-icon svg { stroke:#3b82f6; filter:drop-shadow(0 2px 8px rgba(59,130,246,0.30)); }
    .lc-ortu .lcard-icon svg  { stroke:#2563eb; filter:drop-shadow(0 2px 8px rgba(37,99,235,0.30)); }
    .lc-guru .lcard-icon svg  { stroke:#1d4ed8; filter:drop-shadow(0 2px 8px rgba(29,78,216,0.30)); }
    .lcard:hover .lcard-icon svg { transform:scale(1.18); }
    .lc-siswa:hover .lcard-icon svg { filter:drop-shadow(0 4px 20px rgba(59,130,246,0.50)); }
    .lc-ortu:hover .lcard-icon svg  { filter:drop-shadow(0 4px 20px rgba(37,99,235,0.50)); }
    .lc-guru:hover .lcard-icon svg  { filter:drop-shadow(0 4px 20px rgba(29,78,216,0.50)); }

    .lcard-icon .glow-ring {
        position:absolute; top:-50%; left:-50%; width:200%; height:200%;
        background:conic-gradient(from 0deg, transparent, var(--lc, var(--leaf)) 8%, transparent 16%);
        animation:iconBorderSpin 3s linear infinite;
        opacity:0; transition:opacity 0.5s; z-index:0;
    }
    .lcard:hover .glow-ring { opacity:0.16; }
    .lcard-icon .glow-bg {
        position:absolute; inset:1.5px; border-radius:14.5px;
        background:var(--lc-bg, rgba(37,99,235,0.06)); z-index:1;
    }

    /* ── Habit Card Emoji → Glass Container ── */
    .hcard-emoji {
        display:inline-flex !important; align-items:center; justify-content:center;
        width:60px; height:60px; border-radius:16px;
        background:linear-gradient(135deg, var(--hcbg, rgba(37,99,235,0.06)), rgba(255,255,255,0.01)) !important;
        border:1px solid var(--hcbg2, rgba(37,99,235,0.10)) !important;
        font-size:30px !important;
        animation:iconGlowPulse 5s ease-in-out infinite;
        --ig:var(--hcc, rgba(37,99,235,0.10));
    }
    .hc1 .hcard-emoji{animation-delay:0s} .hc2 .hcard-emoji{animation-delay:-.7s}
    .hc3 .hcard-emoji{animation-delay:-1.4s} .hc4 .hcard-emoji{animation-delay:-2.1s}
    .hc5 .hcard-emoji{animation-delay:-2.8s} .hc6 .hcard-emoji{animation-delay:-3.5s}
    .hc7 .hcard-emoji{animation-delay:-4.2s}
    .hcard:hover .hcard-emoji { box-shadow:0 0 28px var(--hcc, rgba(37,99,235,0.18)), 0 12px 32px rgba(15,23,42,0.10) !important; }

    /* ── Profil Card Icon → Glass Container ── */
    .pcard-icon {
        display:inline-flex !important; align-items:center; justify-content:center;
        width:48px; height:48px; border-radius:14px;
        background:linear-gradient(135deg, var(--pc, rgba(37,99,235,0.06)), rgba(255,255,255,0.02)) !important;
        border:1px solid var(--pc, rgba(37,99,235,0.10)) !important;
        font-size:24px !important;
        animation:iconGlowPulse 5.5s ease-in-out infinite;
        --ig:var(--pc, rgba(37,99,235,0.08));
    }
    .pc1 .pcard-icon{animation-delay:0s} .pc2 .pcard-icon{animation-delay:-.9s}
    .pc3 .pcard-icon{animation-delay:-1.8s} .pc4 .pcard-icon{animation-delay:-2.7s}
    .pc5 .pcard-icon{animation-delay:-3.6s} .pc6 .pcard-icon{animation-delay:-4.5s}
    .pcard:hover .pcard-icon { box-shadow:0 0 24px var(--pc, rgba(37,99,235,0.14)), 0 8px 28px rgba(15,23,42,0.08) !important; }

    /* ── Misi Group Icon → 3D Float ── */
    .misi-group-icon {
        animation:icon3dFloat 6s ease-in-out infinite !important;
        box-shadow:0 2px 8px rgba(15,23,42,0.05);
        transition:box-shadow 0.3s ease !important;
    }
    .mg1 .misi-group-icon{animation-delay:0s} .mg2 .misi-group-icon{animation-delay:-1.2s}
    .mg3 .misi-group-icon{animation-delay:-2.4s} .mg4 .misi-group-icon{animation-delay:-3.6s}
    .mg5 .misi-group-icon{animation-delay:-4.8s}
    .misi-group:hover .misi-group-icon { box-shadow:0 0 18px var(--mi-border, rgba(37,99,235,0.14)), 0 6px 20px rgba(15,23,42,0.08) !important; }

    /* ── Modal Emoji → Glass Container ── */
    .modal-emoji {
        display:inline-flex !important; align-items:center; justify-content:center;
        width:62px; height:62px; border-radius:18px;
        background:linear-gradient(135deg, rgba(37,99,235,0.05), rgba(255,255,255,0.01)) !important;
        border:1px solid rgba(37,99,235,0.08) !important;
        animation:iconGlowPulse 5s ease-in-out infinite;
        --ig:rgba(37,99,235,0.08);
    }

    /* ── Spline 3D Viewer Container ── */
    .spline-scene {
        position:relative; border-radius:var(--radius-lg); overflow:hidden;
        background:rgba(241,245,249,0.80); border:1px solid rgba(148,163,184,0.14);
        box-shadow:0 0 30px rgba(37,99,235,0.03), 0 20px 60px rgba(15,23,42,0.06);
    }
    .spline-scene spline-viewer { width:100%; height:100%; border:none; }
    .spline-scene::before {
        content:''; position:absolute; inset:0; z-index:1; border-radius:inherit;
        box-shadow:inset 0 0 50px rgba(37,99,235,0.02); pointer-events:none;
    }

    </style>
</head>
<body>

<!-- Scroll Progress Bar -->
<div class="scroll-progress" id="scrollProgress"></div>
<!-- Mouse Spotlight -->
<div class="mouse-glow" id="mouseGlow"></div>

<!-- mesh background -->
<div class="mesh">
    <div class="blob b1"></div>
    <div class="blob b2"></div>
    <div class="blob b3"></div>
    <div class="blob b4"></div>
</div>

<!-- Floating Particles -->
<div class="particles" id="particlesContainer"></div>

<!-- ═══ NAVBAR ═══ -->
<nav class="nav">
    <a class="nav-brand" href="#">
        <div class="nav-logo">
            <img src="<?php echo htmlspecialchars($logoSekolah); ?>" alt="Logo">
        </div>
        <span class="nav-wordmark">SMP Negeri 28 Balikpapan<span class="nav-school">KAIH &mdash; Karakter Anak Indonesia Hebat</span></span>
    </a>
    <div class="nav-right">
        <span class="nav-badge">SPANDULA</span>
    </div>
</nav>

<!-- ═══ HERO ═══ -->
<section class="hero" id="hero">

    <!-- Left -->
    <div class="hero-left reveal">
        <div class="hero-eyebrow">Program Karakter Nasional</div>
        <h1 class="hero-h1">
            7 Kebiasaan<br>
            Anak<br>
            Indonesia Hebat
        </h1>
        <p class="hero-sub">Membangun karakter Profil Pelajar Pancasila melalui tujuh pembiasaan harian yang sederhana namun bermakna.</p>
        <div class="hero-cta-row">
            <a href="#habits" class="btn-outline">Lihat Program ↓</a>
            <a href="#visimisi" class="btn-outline">Visi &amp; Misi ↓</a>
        </div>
        <div class="hero-stat-row">
            <div class="hero-stat-card" data-tilt>
                <div class="hero-stat-num" data-target="7">0</div>
                <div class="hero-stat-label">Kebiasaan Harian</div>
            </div>
            <div class="hero-stat-card" data-tilt>
                <div class="hero-stat-num" data-target="3">0</div>
                <div class="hero-stat-label">Peran Pengguna</div>
            </div>
            <div class="hero-stat-card" data-tilt>
                <div class="hero-stat-num" data-target="6">0</div>
                <div class="hero-stat-label">Profil Pelajar Pancasila</div>
            </div>
        </div>
        <div class="scroll-hint">
            <div class="scroll-line"></div>
            Scroll untuk jelajahi
        </div>
    </div>

    <!-- Right: slideshow -->
    <div class="hero-right">
        <div class="hero-slideshow" id="heroSlide">
            <?php foreach ($slideshowPhotos as $si => $slide): ?>
            <div class="hslide<?= $si === 0 ? ' active' : '' ?>">
                <img src="<?php echo htmlspecialchars($slide['src']); ?>" alt="<?php echo htmlspecialchars($slide['alt']); ?>">
            </div>
            <?php endforeach; ?>
        </div>
        <div class="hslide-dots">
            <?php foreach ($slideshowPhotos as $si => $slide): ?>
            <button class="hsdot<?= $si === 0 ? ' active' : '' ?>" data-i="<?= $si ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>

</section>

<!-- ═══ LOGIN CARDS ═══ -->
<div class="login-section" id="login">
    <div class="login-strip reveal">
        <a class="lcard lc-siswa" href="index.php#login" onclick="return openModal(event,'siswa')" data-tilt>
            <div class="lcard-icon">
                <span class="glow-ring"></span>
                <span class="glow-bg"></span>
                <svg viewBox="0 0 24 24"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.1 2.7 3 6 3s6-1.9 6-3v-5"/><line x1="22" y1="10" x2="22" y2="16"/></svg>
            </div>
            <div>
                <div class="lcard-title">Peserta Didik</div>
                <div class="lcard-sub">Catat dan lihat progress kebiasaan harian kamu setiap hari.</div>
            </div>
            <div class="lcard-arrow">→</div>
        </a>
        <a class="lcard lc-ortu" href="index.php#login" onclick="return openModal(event,'orang_tua')" data-tilt>
            <div class="lcard-icon">
                <span class="glow-ring"></span>
                <span class="glow-bg"></span>
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <div class="lcard-title">Orang Tua</div>
                <div class="lcard-sub">Pantau perkembangan karakter dan kebiasaan putra-putri Anda.</div>
            </div>
            <div class="lcard-arrow">→</div>
        </a>
        <a class="lcard lc-guru" href="index.php#login" onclick="return openModal(event,'guru')" data-tilt>
            <div class="lcard-icon">
                <span class="glow-ring"></span>
                <span class="glow-bg"></span>
                <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
            <div>
                <div class="lcard-title">Guru</div>
                <div class="lcard-sub">Kelola data siswa, validasi laporan, dan lihat grafik perkembangan kelas.</div>
            </div>
            <div class="lcard-arrow">→</div>
        </a>
    </div>
</div>

<!-- ═══ VISI & MISI ═══ -->
<section class="section visimisi-section" id="visimisi">
    <div class="visimisi-inner">

        <!-- Visi -->
        <div class="reveal">
            <div class="section-label">Identitas Sekolah</div>
            <h2 class="section-title">Visi &amp; Misi<br>SMP Negeri 28</h2>
            <p class="section-desc" style="margin-bottom:28px">Landasan arah pengembangan sekolah menuju generasi yang unggul dan berkarakter.</p>
            <div class="visi-box">
                <span class="visi-label">Visi Sekolah</span>
                <div class="visi-text">&ldquo;Terwujudnya Peserta Didik yang Berakhlak Mulia, Unggul, Berdaya Saing Global, Berbasis Kearifan Lokal, dan Peduli Lingkungan.&rdquo;</div>
            </div>
        </div>

        <!-- Misi accordion -->
        <div class="misi-list reveal">
            <div class="misi-group mg1 open">
                <div class="misi-group-header" onclick="toggleMisi(this)">
                    <div class="misi-group-icon">🕌</div>
                    <div class="misi-group-title">Membentuk Karakter &amp; Akhlak Mulia</div>
                    <div class="misi-chevron">&#x25BE;</div>
                </div>
                <div class="misi-group-body">
                    <div class="misi-item">Menyelenggarakan pembiasaan nilai-nilai religius dan budi pekerti luhur dalam setiap aspek kehidupan sekolah.</div>
                    <div class="misi-item">Menciptakan lingkungan sekolah yang religius, jujur, disiplin, dan bertanggung jawab.</div>
                </div>
            </div>
            <div class="misi-group mg2">
                <div class="misi-group-header" onclick="toggleMisi(this)">
                    <div class="misi-group-icon">🏆</div>
                    <div class="misi-group-title">Mewujudkan Keunggulan Akademik &amp; Non-Akademik</div>
                    <div class="misi-chevron">&#x25BE;</div>
                </div>
                <div class="misi-group-body">
                    <div class="misi-item">Melaksanakan proses pembelajaran yang inovatif, kreatif, dan menantang untuk melejitkan potensi kecerdasan intelektual siswa.</div>
                    <div class="misi-item">Memfasilitasi pengembangan bakat dan minat peserta didik melalui kegiatan ekstrakurikuler dan pembinaan prestasi yang intensif.</div>
                </div>
            </div>
            <div class="misi-group mg3">
                <div class="misi-group-header" onclick="toggleMisi(this)">
                    <div class="misi-group-icon">🌐</div>
                    <div class="misi-group-title">Meningkatkan Daya Saing Global</div>
                    <div class="misi-chevron">&#x25BE;</div>
                </div>
                <div class="misi-group-body">
                    <div class="misi-item">Mengintegrasikan penguasaan teknologi informasi dan komunikasi (TIK) dalam proses pembelajaran.</div>
                    <div class="misi-item">Mengembangkan kemampuan komunikasi bahasa asing dan keterampilan berpikir kritis sesuai standar kompetensi internasional.</div>
                </div>
            </div>
            <div class="misi-group mg4">
                <div class="misi-group-header" onclick="toggleMisi(this)">
                    <div class="misi-group-icon">🎎</div>
                    <div class="misi-group-title">Menguatkan Kemandirian Berbasis Kearifan Lokal</div>
                    <div class="misi-chevron">&#x25BE;</div>
                </div>
                <div class="misi-group-body">
                    <div class="misi-item">Mengintegrasikan nilai-nilai budaya daerah dan kearifan lokal ke dalam kurikulum dan proyek penguatan profil pelajar Pancasila.</div>
                    <div class="misi-item">Menanamkan rasa cinta tanah air dan kebanggaan terhadap warisan budaya bangsa kepada seluruh warga sekolah.</div>
                </div>
            </div>
            <div class="misi-group mg5">
                <div class="misi-group-header" onclick="toggleMisi(this)">
                    <div class="misi-group-icon">🌿</div>
                    <div class="misi-group-title">Membangun Budaya Lingkungan (Adiwiyata)</div>
                    <div class="misi-chevron">&#x25BE;</div>
                </div>
                <div class="misi-group-body">
                    <div class="misi-item">Mewujudkan lingkungan sekolah yang bersih, sehat, asri, dan nyaman (Green School).</div>
                    <div class="misi-item">Menerapkan praktik pelestarian lingkungan, pencegahan pencemaran, dan pengelolaan sampah secara mandiri.</div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- ═══ 7 HABITS ═══ -->
<section class="section habits-section" id="habits">
    <div class="habits-header reveal">
        <div class="section-label">7 Kebiasaan KAIH</div>
        <h2 class="section-title">Kebiasaan Anak<br>Indonesia Hebat</h2>
        <p class="section-desc">Tujuh pembiasaan harian yang membentuk karakter Profil Pelajar Pancasila. Geser untuk melihat semua.</p>
    </div>

    <div class="habits-track-wrap" id="habitsWrap">
        <div class="habits-track" id="habitsTrack">

            <div class="hcard hc1">
                <span class="hcard-emoji">🌅</span>
                <div class="hcard-title">Bangun Pagi &amp; Merapikan Tempat Tidur</div>
                <div class="hcard-body">Membiasakan bangun pagi tepat waktu dan langsung merapikan tempat tidur. Melatih kedisiplinan dan kemandirian sejak dini.</div>
            </div>

            <div class="hcard hc2">
                <span class="hcard-emoji">🙏</span>
                <div class="hcard-title">Beribadah — Sholat Subuh / Ibadah Pagi</div>
                <div class="hcard-body">Melaksanakan ibadah sesuai agama masing-masing. Memperkuat dimensi spiritual sebagai fondasi karakter yang kokoh.</div>
            </div>

            <div class="hcard hc3">
                <span class="hcard-emoji">🏃</span>
                <div class="hcard-title">Berolahraga / Aktivitas Fisik</div>
                <div class="hcard-body">Aktivitas fisik minimal 15–30 menit setiap pagi. Menjaga kebugaran fisik dan kesehatan mental melalui gerak teratur.</div>
            </div>

            <div class="hcard hc4">
                <span class="hcard-emoji">🥗</span>
                <div class="hcard-title">Sarapan Sehat &amp; Minum Air Putih</div>
                <div class="hcard-body">Sarapan bergizi dan minum cukup air sebelum berangkat sekolah. Penting untuk energi belajar dan pertumbuhan optimal.</div>
            </div>

            <div class="hcard hc5">
                <span class="hcard-emoji">📚</span>
                <div class="hcard-title">Gemar Membaca (Literasi)</div>
                <div class="hcard-body">Membaca buku atau bacaan positif minimal 15 menit per hari. Mengembangkan kemampuan berpikir kritis dan memperluas wawasan.</div>
            </div>

            <div class="hcard hc6">
                <span class="hcard-emoji">🤝</span>
                <div class="hcard-title">Membantu Orang Tua / Berpamitan</div>
                <div class="hcard-body">Membantu pekerjaan rumah tangga dan berpamitan sebelum berangkat. Melatih empati dan hormat kepada orang tua.</div>
            </div>

            <div class="hcard hc7">
                <span class="hcard-emoji">💰</span>
                <div class="hcard-title">Menabung / Hidup Hemat</div>
                <div class="hcard-body">Menyisihkan sebagian uang jajan untuk ditabung. Mengenalkan pengelolaan keuangan dan pengendalian diri sejak usia dini.</div>
            </div>

        </div>
    </div>
    <div class="habits-progress">
        <div class="hpbar"><div class="hpfill" id="hpFill"></div></div>
        <span class="hp-label" id="hpLabel">1 / 7</span>
    </div>
</section>

<!-- ═══ TUJUAN ═══ -->
<section class="section tujuan-section" id="tujuan">
    <div class="tujuan-inner">
        <div class="tujuan-left reveal">
            <div class="section-label">Tujuan Program</div>
            <div class="tujuan-tagline">Tumbuh jadi pribadi yang <strong>hebat</strong> dan <strong>berkarakter.</strong></div>
            <p class="tujuan-para">Program KAIH dirancang untuk membentuk karakter <strong>Profil Pelajar Pancasila</strong> melalui pembiasaan sederhana yang konsisten. Dengan menjalankan 7 kebiasaan ini setiap hari, peserta didik tumbuh menjadi pribadi yang disiplin, religius, sehat, cerdas, peduli, dan bijak mengelola kehidupan.</p>
            <div class="tujuan-pill-row">
                <span class="tujuan-pill">Beriman &amp; Bertakwa</span>
                <span class="tujuan-pill">Mandiri</span>
                <span class="tujuan-pill">Bernalar Kritis</span>
                <span class="tujuan-pill">Gotong Royong</span>
                <span class="tujuan-pill">Kreatif</span>
                <span class="tujuan-pill">Berkebhinekaan Global</span>
            </div>
        </div>
        <div class="profil-grid">
            <div class="pcard pc1 reveal d1" data-tilt>
                <span class="pcard-icon">💎</span>
                <div class="pcard-name">Beriman &amp; Bertakwa</div>
                <div class="pcard-desc">Berakhlak mulia kepada Tuhan, sesama manusia, dan alam semesta.</div>
            </div>
            <div class="pcard pc2 reveal d2" data-tilt>
                <span class="pcard-icon">✨</span>
                <div class="pcard-name">Mandiri</div>
                <div class="pcard-desc">Bertanggung jawab atas proses dan hasil belajarnya sendiri.</div>
            </div>
            <div class="pcard pc3 reveal d3" data-tilt>
                <span class="pcard-icon">💡</span>
                <div class="pcard-name">Bernalar Kritis</div>
                <div class="pcard-desc">Memproses informasi secara objektif, reflektif, dan logis.</div>
            </div>
            <div class="pcard pc4 reveal d4" data-tilt>
                <span class="pcard-icon">🤝</span>
                <div class="pcard-name">Gotong Royong</div>
                <div class="pcard-desc">Berkolaborasi, peduli, dan berbagi dengan orang lain.</div>
            </div>
            <div class="pcard pc5 reveal d5" data-tilt>
                <span class="pcard-icon">🎨</span>
                <div class="pcard-name">Kreatif</div>
                <div class="pcard-desc">Menghasilkan karya dan gagasan orisinal yang bermakna.</div>
            </div>
            <div class="pcard pc6 reveal d6" data-tilt>
                <span class="pcard-icon">🌍</span>
                <div class="pcard-name">Berkebhinekaan Global</div>
                <div class="pcard-desc">Menghargai keberagaman budaya dan identitas Indonesia di dunia.</div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ FOOTER ═══ -->
<footer class="footer">
    <span class="footer-copy">© 2025 SMP Negeri 28 Balikpapan &bull; Program KAIH SPANDULA</span>
    <div class="footer-right">
        <span class="footer-mark">KAIH</span>
        <a href="admin/login.php" class="footer-admin-link" onclick="return openAdminModal(event)" title="Admin" aria-label="Admin login">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </a>
    </div>
</footer>

<!-- ═══ MODALS ═══ -->

<!-- Siswa -->
<div class="modal-overlay" id="modal-siswa" onclick="closeModal(event,'siswa')">
    <div class="modal-box" onclick="event.stopPropagation()">
        <span class="modal-emoji">🧑‍🎓</span>
        <div class="modal-title">Login Peserta Didik</div>
        <div class="modal-sub">Masukkan NISN dan password kamu</div>
        <div id="siswaErrBox" style="display:none;margin-top:8px;margin-bottom:2px;padding:10px 14px;background:rgba(220,38,38,0.12);border:1px solid rgba(220,38,38,0.28);border-radius:10px;font-size:12px;color:#fca5a5;line-height:1.5;text-align:left;"></div>
        <form method="POST" action="index.php" autocomplete="on">
            <input type="hidden" name="role" value="siswa">
            <div class="form-field">
                <label for="s-id">NISN</label>
                <input id="s-id" name="identifier" inputmode="numeric" autocomplete="username" placeholder="Contoh: 1234567890" required>
                <div class="form-hint">Masukkan NISN kamu yang terdiri dari 10 digit</div>
            </div>
            <div class="form-field">
                <label for="s-pw">Password</label>
                <input id="s-pw" name="password" type="password" autocomplete="current-password" placeholder="Password kamu" required>
                <div class="form-hint">Password awal = NISN (sampai diubah admin)</div>
            </div>
            <button type="submit" class="modal-submit">Masuk →</button>
            <button type="button" class="modal-cancel" onclick="closeModal(null,'siswa')">Batal</button>
        </form>
    </div>
</div>

<!-- Orang Tua -->
<div class="modal-overlay" id="modal-orang_tua" onclick="closeModal(event,'orang_tua')">
    <div class="modal-box" onclick="event.stopPropagation()">
        <span class="modal-emoji">👨‍👩‍👧</span>
        <div class="modal-title">Login Orang Tua</div>
        <div class="modal-sub">Gunakan format ORT + NISN anak</div>
        <div id="ortuErrBox" style="display:none;margin-top:8px;margin-bottom:2px;padding:10px 14px;background:rgba(220,38,38,0.12);border:1px solid rgba(220,38,38,0.28);border-radius:10px;font-size:12px;color:#fca5a5;line-height:1.5;text-align:left;"></div>
        <form method="POST" action="index.php" autocomplete="on">
            <input type="hidden" name="role" value="orang_tua">
            <div class="form-field">
                <label for="o-id">Kode ORT + NISN</label>
                <input id="o-id" name="identifier" autocomplete="username" placeholder="Contoh: ORT1234567890" required>
                <div class="form-hint">Format: ORT diikuti NISN siswa</div>
            </div>
            <div class="form-field">
                <label for="o-pw">Password</label>
                <input id="o-pw" name="password" type="password" autocomplete="current-password" placeholder="Password akun" required>
                <div class="form-hint">Password awal = ORT + NISN</div>
            </div>
            <button type="submit" class="modal-submit">Masuk →</button>
            <button type="button" class="modal-cancel" onclick="closeModal(null,'orang_tua')">Batal</button>
        </form>
    </div>
</div>

<!-- Admin -->
<div class="modal-overlay" id="modal-admin" onclick="closeModal(event,'admin')">
    <div class="modal-box" onclick="event.stopPropagation()">
        <span class="modal-emoji">🔐</span>
        <div class="modal-title">Admin Panel</div>
        <div class="modal-sub">Akses terbatas untuk administrator sistem</div>
        <div id="adminErrBox" style="display:none;margin-top:8px;margin-bottom:2px;padding:10px 14px;background:rgba(220,38,38,0.12);border:1px solid rgba(220,38,38,0.28);border-radius:10px;font-size:12px;color:#fca5a5;line-height:1.5;text-align:left;"></div>
        <form method="POST" action="admin/login.php" id="adminLoginForm" autocomplete="on">
            <input type="hidden" name="csrf" id="adminCsrfToken" value="">
            <div class="form-field">
                <label for="a-user">Username</label>
                <input id="a-user" name="username" autocomplete="username" placeholder="Username admin" required>
            </div>
            <div class="form-field">
                <label for="a-pw">Password</label>
                <input id="a-pw" name="password" type="password" autocomplete="current-password" placeholder="Password admin" required>
            </div>
            <button type="submit" class="modal-submit" id="adminSubmitBtn">Masuk →</button>
            <button type="button" class="modal-cancel" onclick="closeModal(null,'admin')">Batal</button>
        </form>
    </div>
</div>

<!-- Guru -->
<div class="modal-overlay" id="modal-guru" onclick="closeModal(event,'guru')">
    <div class="modal-box" onclick="event.stopPropagation()">
        <span class="modal-emoji">👩‍🏫</span>
        <div class="modal-title">Login Guru</div>
        <div class="modal-sub">Masukkan NIP dan password akun guru</div>
        <div id="guruErrBox" style="display:none;margin-top:8px;margin-bottom:2px;padding:10px 14px;background:rgba(220,38,38,0.12);border:1px solid rgba(220,38,38,0.28);border-radius:10px;font-size:12px;color:#fca5a5;line-height:1.5;text-align:left;"></div>
        <form method="POST" action="index.php" autocomplete="on">
            <input type="hidden" name="role" value="guru">
            <div class="form-field">
                <label for="g-id">NIP</label>
                <input id="g-id" name="identifier" autocomplete="username" placeholder="Masukkan NIP" required>
                <div class="form-hint">Password awal = NIP (sampai diubah admin)</div>
            </div>
            <div class="form-field">
                <label for="g-pw">Password</label>
                <input id="g-pw" name="password" type="password" autocomplete="current-password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="modal-submit">Masuk →</button>
            <button type="button" class="modal-cancel" onclick="closeModal(null,'guru')">Batal</button>
        </form>
    </div>
</div>

<script>
/* ── MODAL ── */
function openModal(e,role){
    if(e)e.preventDefault();
    var m=document.getElementById('modal-'+role);
    if(!m)return false;
    m.classList.add('active'); m.setAttribute('aria-hidden','false');
    document.body.style.overflow='hidden';
    var inp=m.querySelector('input[name="identifier"]');
    if(inp)setTimeout(function(){inp.focus();},60);
    return false;
}
function closeModal(e,role){
    if(e&&!e.target.classList.contains('modal-overlay'))return;
    var m=document.getElementById('modal-'+role);
    if(!m)return;
    m.classList.remove('active'); m.setAttribute('aria-hidden','true');
    document.body.style.overflow='';
}
document.addEventListener('keydown',function(e){
    if(e.key!=='Escape')return;
    var a=document.querySelector('.modal-overlay.active');
    if(!a)return;
    a.classList.remove('active'); a.setAttribute('aria-hidden','true');
    document.body.style.overflow='';
});

/* ── HERO SLIDESHOW ── */
(function(){
    var wrap=document.getElementById('heroSlide');
    if(!wrap)return;
    var slides=wrap.querySelectorAll('.hslide');
    var dots=document.querySelectorAll('.hsdot');
    var prevBtn=document.getElementById('heroPrev');
    var nextBtn=document.getElementById('heroNext');
    var cur=0, timer=null, total=slides.length;
    var touchStartX=0, touchDeltaX=0;
    function render(){
        slides.forEach(function(slide,index){
            slide.classList.remove('active','prev','next');
            if(index===cur){
                slide.classList.add('active');
            }else if(index===(cur-1+total)%total){
                slide.classList.add('prev');
            }else{
                slide.classList.add('next');
            }
        });
        dots.forEach(function(dot,index){
            dot.classList.toggle('active',index===cur);
        });
    }
    function go(n){
        cur=(n+total)%total;
        render();
    }
    function prev(){ go(cur-1); }
    function next(){ go(cur+1); }
    function start(){ timer=setInterval(next,3000); }
    function stop(){ clearInterval(timer); }
    dots.forEach(function(d){
        d.addEventListener('click',function(){
            stop(); go(parseInt(d.getAttribute('data-i'))); start();
        });
    });
    if(prevBtn){
        prevBtn.addEventListener('click',function(){
            stop(); prev(); start();
        });
    }
    if(nextBtn){
        nextBtn.addEventListener('click',function(){
            stop(); next(); start();
        });
    }
    wrap.addEventListener('touchstart',function(e){
        if(!e.touches || e.touches.length!==1)return;
        touchStartX=e.touches[0].clientX;
        touchDeltaX=0;
        stop();
    },{passive:true});
    wrap.addEventListener('touchmove',function(e){
        if(!e.touches || e.touches.length!==1)return;
        touchDeltaX=e.touches[0].clientX-touchStartX;
    },{passive:true});
    wrap.addEventListener('touchend',function(){
        if(Math.abs(touchDeltaX)>45){
            if(touchDeltaX<0){
                next();
            }else{
                prev();
            }
        }
        touchStartX=0;
        touchDeltaX=0;
        start();
    });
    wrap.addEventListener('touchcancel',function(){
        touchStartX=0;
        touchDeltaX=0;
        start();
    });
    wrap.addEventListener('mouseenter',stop);
    wrap.addEventListener('mouseleave',start);
    render();
    start();
})();

/* ── HABIT TRACK DRAG SCROLL & PROGRESS ── */
(function(){
    var wrap=document.getElementById('habitsWrap');
    var track=document.getElementById('habitsTrack');
    var fill=document.getElementById('hpFill');
    var label=document.getElementById('hpLabel');
    if(!wrap||!track)return;
    var TOTAL=7;
    function upd(){
        var maxScroll=wrap.scrollWidth-wrap.clientWidth;
        var pct=maxScroll>0?wrap.scrollLeft/maxScroll:0;
        if(fill)fill.style.width=(pct*100)+'%';
        var idx=Math.round(pct*(TOTAL-1))+1;
        if(label)label.textContent=idx+' / '+TOTAL;
    }
    wrap.addEventListener('scroll',upd,{passive:true});

    /* Drag to scroll */
    var down=false, startX=0, scrollX=0;
    wrap.addEventListener('mousedown',function(e){ down=true; startX=e.pageX; scrollX=wrap.scrollLeft; wrap.style.userSelect='none'; });
    document.addEventListener('mousemove',function(e){ if(!down)return; wrap.scrollLeft=scrollX-(e.pageX-startX); });
    document.addEventListener('mouseup',function(){ down=false; wrap.style.userSelect=''; });

    /* Touch scroll indicator */
    wrap.addEventListener('touchmove',upd,{passive:true});
    upd();
})();

/* ── MISI ACCORDION ── */
function toggleMisi(header){
    var group = header.parentElement;
    var isOpen = group.classList.contains('open');
    // close all
    document.querySelectorAll('.misi-group.open').forEach(function(g){ g.classList.remove('open'); });
    // open clicked if it was closed
    if(!isOpen) group.classList.add('open');
}

/* ── ADMIN MODAL ── */
function openAdminModal(e, errMsg){
    if(e)e.preventDefault();
    var m=document.getElementById('modal-admin');
    if(!m)return false;
    var csrfInput=document.getElementById('adminCsrfToken');
    var submitBtn=document.getElementById('adminSubmitBtn');
    // Show error if passed
    var errBox=document.getElementById('adminErrBox');
    if(errBox){
        if(errMsg){ errBox.textContent=errMsg; errBox.style.display='block'; }
        else { errBox.style.display='none'; }
    }
    // Fetch fresh CSRF token each time modal opens
    if(csrfInput){
        csrfInput.value='';
        if(submitBtn){ submitBtn.disabled=true; }
        fetch('admin/api/csrf.php')
            .then(function(r){return r.json();})
            .then(function(d){
                if(d.ok&&csrfInput)csrfInput.value=d.token;
                if(submitBtn)submitBtn.disabled=false;
            })
            .catch(function(){
                if(errBox){ errBox.textContent='Gagal memuat token keamanan. Muat ulang halaman dan coba lagi.'; errBox.style.display='block'; }
                if(submitBtn)submitBtn.disabled=true;
            });
    }
    m.classList.add('active'); m.setAttribute('aria-hidden','false');
    document.body.style.overflow='hidden';
    var inp=m.querySelector('input[name="username"]');
    if(inp)setTimeout(function(){inp.focus();},60);
    return false;
}

/* ── SCROLL REVEAL ── */
(function(){
    var els=document.querySelectorAll('.reveal');
    var obs=new IntersectionObserver(function(entries){
        entries.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('on'); obs.unobserve(e.target); } });
    },{threshold:0.09});
    els.forEach(function(el){ obs.observe(el); });
})();

/* ═══════════════════════════════════════════
   ENHANCED 3D EFFECTS & INTERACTIVITY
═══════════════════════════════════════════ */

/* ── SCROLL PROGRESS BAR ── */
(function(){
    var bar=document.getElementById('scrollProgress');
    if(!bar)return;
    window.addEventListener('scroll',function(){
        var h=document.documentElement.scrollHeight-window.innerHeight;
        bar.style.width=(h>0?(window.scrollY/h)*100:0)+'%';
    },{passive:true});
})();

/* ── NAVBAR SCROLL EFFECT ── */
(function(){
    var nav=document.querySelector('.nav');
    if(!nav)return;
    var last=0;
    window.addEventListener('scroll',function(){
        var y=window.scrollY;
        nav.classList.toggle('scrolled',y>60);
        last=y;
    },{passive:true});
})();

/* ── MOUSE SPOTLIGHT ── */
(function(){
    var glow=document.getElementById('mouseGlow');
    if(!glow)return;
    var rAF=null;
    document.addEventListener('mousemove',function(e){
        if(rAF)return;
        rAF=requestAnimationFrame(function(){
            glow.style.left=e.clientX+'px';
            glow.style.top=e.clientY+'px';
            rAF=null;
        });
    });
})();

/* ── FLOATING PARTICLES ── */
(function(){
    var container=document.getElementById('particlesContainer');
    if(!container)return;
    var colors=['rgba(37,99,235,0.20)','rgba(59,130,246,0.15)','rgba(96,165,250,0.12)','rgba(79,70,229,0.15)'];
    var count=28;
    for(var i=0;i<count;i++){
        var p=document.createElement('div');
        p.className='particle';
        var size=Math.random()*4+2;
        p.style.width=size+'px';
        p.style.height=size+'px';
        p.style.left=Math.random()*100+'%';
        p.style.background=colors[Math.floor(Math.random()*colors.length)];
        p.style.animationDuration=(Math.random()*14+10)+'s';
        p.style.animationDelay=(Math.random()*16)+'s';
        container.appendChild(p);
    }
})();

/* ── 3D TILT ON MOUSE ── */
(function(){
    var cards=document.querySelectorAll('[data-tilt]');
    cards.forEach(function(card){
        card.addEventListener('mousemove',function(e){
            var rect=card.getBoundingClientRect();
            var x=e.clientX-rect.left;
            var y=e.clientY-rect.top;
            var cx=rect.width/2;
            var cy=rect.height/2;
            var rX=((y-cy)/cy)*-8;
            var rY=((x-cx)/cx)*8;
            card.style.transform='perspective(800px) rotateX('+rX+'deg) rotateY('+rY+'deg) translateZ(8px) scale(1.02)';
        });
        card.addEventListener('mouseleave',function(){
            card.style.transform='';
        });
    });
})();

/* ── ANIMATED NUMBER COUNTERS ── */
(function(){
    var nums=document.querySelectorAll('.hero-stat-num[data-target]');
    if(!nums.length)return;
    var animated=false;
    var obs=new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
            if(!entry.isIntersecting||animated)return;
            animated=true;
            nums.forEach(function(el){
                var target=parseInt(el.getAttribute('data-target'))||0;
                var dur=1200;
                var start=performance.now();
                function tick(now){
                    var pct=Math.min((now-start)/dur,1);
                    var ease=1-Math.pow(1-pct,3);
                    el.textContent=Math.round(target*ease);
                    if(pct<1)requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            });
            obs.disconnect();
        });
    },{threshold:0.3});
    nums.forEach(function(n){obs.observe(n);});
})();

/* ── PARALLAX BLOBS ON SCROLL ── */
(function(){
    var blobs=document.querySelectorAll('.blob');
    if(!blobs.length)return;
    var speeds=[0.03,-0.02,0.015,-0.025];
    window.addEventListener('scroll',function(){
        var y=window.scrollY;
        blobs.forEach(function(blob,i){
            var sp=speeds[i%speeds.length];
            blob.style.transform='translateY('+(y*sp)+'px)';
        });
    },{passive:true});
})();

<?php
$adminErr = trim((string)($_GET['admin_err'] ?? ''));
if ($adminErr !== ''):
    $safeErr = htmlspecialchars($adminErr, ENT_QUOTES, 'UTF-8');
?>
(function(){
    window.addEventListener('load', function(){
        openAdminModal(null, <?= json_encode($safeErr) ?>);
    });
})();
<?php endif; ?>

<?php
$loginErr  = trim((string)($_GET['login_err']  ?? ''));
$loginRole = trim((string)($_GET['login_role'] ?? ''));
if ($loginErr !== '' && in_array($loginRole, ['siswa','orang_tua','guru'], true)):
?>
(function(){
    var roleToBox = {'siswa':'siswaErrBox','orang_tua':'ortuErrBox','guru':'guruErrBox'};
    var role = <?= json_encode($loginRole) ?>;
    var msg  = <?= json_encode($loginErr) ?>;
    window.addEventListener('load', function(){
        openModal(null, role);
        var box = document.getElementById(roleToBox[role]);
        if(box){ box.textContent = msg; box.style.display='block'; }
    });
})();
<?php endif; ?>

</script>
</body>
</html>

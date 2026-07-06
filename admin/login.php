<?php
declare(strict_types=1);

use Random\Engine\Secure;
use Random\Randomizer;

require_once '../includes/admin_auth.php';
require_once '../config/pdo.php';

enum UserRole: string
{
    case Admin = 'admin';
}

final class AdminLoginSpec
{
    public const string API_VERSION = 'v1';
    public const string CSRF_COOKIE = 'kaih_csrf';

    public const array CONFIG = [
        'csrf_cookie' => self::CSRF_COOKIE,
        'role_admin' => 'admin',
    ];
}

final readonly class CsrfToken
{
    public function __construct(
        public string $value,
    ) {
    }
}

// Required by spec: class alias for readonly properties.
class_alias(CsrfToken::class, 'LoginCsrfToken');

final class CsrfDoubleSubmit
{
    public function __construct(
        private readonly Randomizer $randomizer,
    ) {
    }

    public function issue(): CsrfToken
    {
        $length = (int) round($this->randomizer->getFloat(32.0, 48.0));
        $seed = $this->randomizer->getBytes(64);
        $bytes = $this->randomizer->getBytesFromString($seed, $length);
        $token = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

        return new CsrfToken($token);
    }

    public function setCookie(CsrfToken $token): void
    {
        $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        setcookie(
            AdminLoginSpec::CSRF_COOKIE,
            $token->value,
            [
                'expires' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => false,
                'samesite' => 'Lax',
            ]
        );
    }

    public function validate(string $provided): bool
    {
        $cookie = (string) ($_COOKIE[AdminLoginSpec::CSRF_COOKIE] ?? '');
        if ($cookie === '' || $provided === '') {
            return false;
        }

        return hash_equals($cookie, $provided);
    }
}

final readonly class AdminIdentity
{
    public function __construct(
        public int $id,
        public string $username,
        public UserRole $role,
    ) {
    }
}

final class AdminAuthRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findAdminByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, password, role FROM users WHERE username = :username AND role = :role LIMIT 1'
        );
        $stmt->execute([
            'username' => $username,
            'role' => UserRole::Admin->value,
        ]);

        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function upgradePasswordHash(int $id, string $newHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password = :hash, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'hash' => $newHash,
            'id' => $id,
        ]);
    }
}

startAdminSession();
$error = '';

$constant = 'API_VERSION';
header('X-API-Version: ' . AdminLoginSpec::{$constant});

if (isCurrentAdminSessionValid()) {
    header('Location: dashboard.php');
    exit;
}

// GET requests: redirect to frontend (login via modal on index.php)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    header('Location: ../index.php');
    exit;
}

$lockState = getAdminLoginLockState();
if (!empty($_GET['expired'])) {
    $error = match (true) {
        true => 'Sesi Anda telah berakhir. Silakan login kembali.',
    };
}

$csrfService = new CsrfDoubleSubmit(new Randomizer(new Secure()));

// Issue token on GET (or if cookie is missing), and embed it in the form.
$csrfTokenValue = (string) ($_COOKIE[AdminLoginSpec::CSRF_COOKIE] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || $csrfTokenValue === '') {
    $token = $csrfService->issue();
    $csrfService->setCookie($token);
    $csrfTokenValue = $token->value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');

    $username = '';
    $password = '';
    $csrfProvided = '';

    if (str_contains(strtolower($contentType), 'application/json')) {
        $raw = file_get_contents('php://input');
        $raw = is_string($raw) ? trim($raw) : '';
        if ($raw !== '' && json_validate($raw)) {
            $data = json_decode($raw, true);
            if (is_array($data)) {
                $username = trim((string) ($data['username'] ?? ''));
                $password = (string) ($data['password'] ?? '');
                $csrfProvided = (string) ($data['csrf'] ?? '');
            }
        }
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $csrfProvided = (string) ($_POST['csrf'] ?? '');
    }

    if (!empty($lockState['locked'])) {
        $wait = (int) ($lockState['retry_after'] ?? 0);
        $error = 'Terlalu banyak percobaan login. Coba lagi dalam ' . $wait . ' detik.';
    } elseif (!$csrfService->validate($csrfProvided)) {
        $error = 'Permintaan tidak valid (CSRF). Silakan refresh halaman dan coba lagi.';
    } elseif ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $repo = new AdminAuthRepository(getPdo());
            $row = $repo->findAdminByUsername($username);

            $hash = $row['password'] ?? null;
            $credentialsOk = is_array($row)
                && is_string($hash)
                && password_verify($password, $hash);

            if (!$credentialsOk) {
                $failedState = registerAdminLoginFailure();
                if (!empty($failedState['locked'])) {
                    $wait = (int) ($failedState['retry_after'] ?? ADMIN_LOGIN_LOCKOUT_SECONDS);
                    $error = 'Terlalu banyak percobaan login. Coba lagi dalam ' . $wait . ' detik.';
                } else {
                    $error = 'Username atau password admin tidak sesuai.';
                }

                // Refresh lock state for UI disable.
                $lockState = getAdminLoginLockState();
            } else {
                $adminId = (int) ($row['id'] ?? 0);
                $adminUsername = (string) ($row['username'] ?? '');

                if (password_needs_rehash($hash, PASSWORD_ARGON2ID)) {
                    $newHash = password_hash($password, PASSWORD_ARGON2ID);
                    if (is_string($newHash) && $newHash !== '') {
                        $repo->upgradePasswordHash($adminId, $newHash);
                    }
                }

                $identity = new AdminIdentity(
                    id: $adminId,
                    username: $adminUsername,
                    role: UserRole::Admin,
                );

                // Nullsafe operator example.
                $safeUsername = $identity?->username;

                completeAdminLogin([
                    'id' => $identity->id,
                    'username' => $safeUsername ?? $adminUsername,
                    'role' => $identity->role->value,
                ]);

                // Rotate CSRF after successful login.
                $token = $csrfService->issue();
                $csrfService->setCookie($token);

                header('Location: dashboard.php');
                exit;
            }
        } catch (Throwable $e) {
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}

// All non-success POST paths fall through here — redirect back to frontend with error
$redirectErr = urlencode($error !== '' ? $error : 'Login gagal. Silakan coba lagi.');
header('Location: ../index.php?admin_err=' . $redirectErr);
exit;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — KAIH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --danger: #dc2626;
            --text: #1f2937;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --bg: #f3f4f6;
            --bg-white: #ffffff;
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.15);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #e8edf3 50%, #eef2f7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
        }

        .auth-card {
            background: var(--bg-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            position: relative;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary) 0%, #3b82f6 100%);
        }

        .auth-header {
            padding: 3rem 2rem 2rem;
            text-align: center;
            background: linear-gradient(180deg, rgba(37, 99, 235, 0.02) 0%, transparent 100%);
        }

        .auth-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }

        .auth-logo svg {
            width: 28px;
            height: 28px;
        }

        .auth-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .auth-header p {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0;
        }

        .auth-body {
            padding: 2rem;
        }

        .alert {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            font-size: 13px;
            margin-bottom: 18px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-icon {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 1px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #9ca3af;
            pointer-events: none;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 11px 14px 11px 40px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text);
            background: var(--bg-white);
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        input[type="text"]:disabled,
        input[type="password"]:disabled {
            background: #f9fafb;
            color: #9ca3af;
            cursor: not-allowed;
            border-color: #e5e7eb;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            padding: 6px;
            cursor: pointer;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .password-toggle:hover {
            background: rgba(107, 114, 128, 0.1);
            color: var(--text-muted);
        }

        .password-toggle:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn {
            width: 100%;
            border: none;
            border-radius: var(--radius-md);
            padding: 12px 16px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
            filter: brightness(1.08);
        }

        .btn:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn svg {
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn:not(:disabled) svg {
            animation: none;
        }

        .form-footer {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 12px;
            color: var(--text-muted);
        }

        .lockout-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 999px;
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #7f1d1d;
            font-weight: 600;
        }

        .lockout-badge svg {
            width: 14px;
            height: 14px;
        }

        @media (max-width: 512px) {
            .auth-header {
                padding: 2rem 1.5rem 1.5rem;
            }

            .auth-body {
                padding: 1.5rem;
            }

            .auth-header h1 {
                font-size: 20px;
            }

            .auth-header p {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

    <main class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L3 6V12C3 16.97 7.02 21.6 12 22C16.98 21.6 21 16.97 21 12V6L12 2Z" fill="rgba(255,255,255,0.95)"/>
                        <path d="M9 12L11 14L15 10" stroke="rgba(37, 99, 235, 1)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h1>Admin</h1>
                <p>Masuk ke panel administrasi</p>
            </div>

            <div class="auth-body">
                <?php if (!empty($error)): ?>
                <div class="alert">
                    <div class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>

                <?php $isLocked = !empty($lockState['locked']); ?>

                <form method="POST" action="" id="loginForm" autocomplete="on">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfTokenValue, ENT_QUOTES | ENT_HTML5) ?>" />

                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                placeholder="admin"
                                autocomplete="username"
                                required
                                <?= $isLocked ? 'disabled' : '' ?>
                            />
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                required
                                <?= $isLocked ? 'disabled' : '' ?>
                            />
                            <button type="button" class="password-toggle" id="togglePassword" aria-label="Tampilkan password" title="Tampilkan password" <?= $isLocked ? 'disabled' : '' ?>>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button class="btn" type="submit" id="submitBtn" <?= $isLocked ? 'disabled' : '' ?>>
                        <span id="btnText">Masuk Sekarang</span>
                        <span id="btnSpinner" style="display:none;" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="9" stroke="white" stroke-width="3" stroke-dasharray="30" stroke-dashoffset="10"/>
                            </svg>
                        </span>
                    </button>

                    <div class="form-footer">
                        <div class="lockout-badge">
                            <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 1C6.48 1 2 5.48 2 11h2c0-3.87 3.13-7 7-7s7 3.13 7 7c0 2.74-1.58 5.13-3.87 6.31L9 11h3V9H2v7h2v-2.52c2.75 1.54 6.09 1.54 8.84 0z"/>
                            </svg>
                            Akses Admin
                        </div>
                        <?php if ($isLocked): ?>
                            <span style="color: var(--danger); font-weight: 700; font-size: 11px;">⏱ Tunggu <?= (int) ($lockState['retry_after'] ?? 0) ?>s</span>
                        <?php endif; ?>
                    </div>
                </form>

                <div style="margin-top: 18px; padding-top: 18px; border-top: 1px solid var(--border); text-align: center; color: var(--text-muted); font-size: 12px;">
                    © <?= date('Y') ?> KAIH Systems. Semua hak dilindungi.
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('loginForm');

            // Password visibility toggle
            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    toggleBtn.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
                });
            }

            // Form submission
            if (form && submitBtn) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    const btnText = document.getElementById('btnText');
                    const btnSpinner = document.getElementById('btnSpinner');
                    if (btnText) btnText.style.display = 'none';
                    if (btnSpinner) btnSpinner.style.display = 'inline';
                });
            }

            // Auto-focus
            const usernameInput = document.getElementById('username');
            if (usernameInput && !usernameInput.value) {
                usernameInput.focus();
            }
        });
    </script>
</body>
</html>

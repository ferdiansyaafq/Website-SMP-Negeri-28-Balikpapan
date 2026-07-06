<?php

const ADMIN_SESSION_TIMEOUT = 28800; // 8 hours
const ADMIN_MAX_LOGIN_ATTEMPTS = 5;
const ADMIN_LOGIN_LOCKOUT_SECONDS = 300; // 5 minutes

function startAdminSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $cookieParams = session_get_cookie_params();

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookieParams['path'] ?? '/',
        'domain' => $cookieParams['domain'] ?? '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function isAdminAuthenticated(): bool
{
    return isset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_role'])
        && $_SESSION['admin_role'] === 'admin';
}

function hasAdminSessionExpired(): bool
{
    $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? ($_SESSION['login_time'] ?? 0));
    if ($lastActivity <= 0) {
        return true;
    }

    return (time() - $lastActivity) > ADMIN_SESSION_TIMEOUT;
}

function refreshAdminSessionActivity(): void
{
    $now = time();
    $_SESSION['admin_last_activity'] = $now;
    if (!isset($_SESSION['login_time'])) {
        $_SESSION['login_time'] = $now;
    }
}

function clearAdminAuthSession(): void
{
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_username'],
        $_SESSION['admin_role'],
        $_SESSION['login_time'],
        $_SESSION['admin_last_activity']
    );
}

function requireAdminLogin(): void
{
    startAdminSession();

    if (!isAdminAuthenticated()) {
        header('Location: login.php');
        exit;
    }

    if (hasAdminSessionExpired()) {
        clearAdminAuthSession();
        header('Location: login.php?expired=1');
        exit;
    }

    refreshAdminSessionActivity();
}

function isCurrentAdminSessionValid(): bool
{
    startAdminSession();

    if (!isAdminAuthenticated()) {
        return false;
    }

    if (hasAdminSessionExpired()) {
        clearAdminAuthSession();
        return false;
    }

    refreshAdminSessionActivity();
    return true;
}

function getAdminLoginLockState(): array
{
    startAdminSession();

    $lockUntil = (int) ($_SESSION['admin_login_lock_until'] ?? 0);
    $retryAfter = max(0, $lockUntil - time());

    if ($retryAfter === 0 && $lockUntil > 0) {
        unset($_SESSION['admin_login_lock_until'], $_SESSION['admin_login_attempts'], $_SESSION['admin_login_last_attempt']);
    }

    return [
        'locked' => $retryAfter > 0,
        'retry_after' => $retryAfter,
    ];
}

function registerAdminLoginFailure(): array
{
    startAdminSession();

    $state = getAdminLoginLockState();
    if (!empty($state['locked'])) {
        return $state;
    }

    $attempts = (int) ($_SESSION['admin_login_attempts'] ?? 0) + 1;
    $_SESSION['admin_login_attempts'] = $attempts;
    $_SESSION['admin_login_last_attempt'] = time();

    if ($attempts >= ADMIN_MAX_LOGIN_ATTEMPTS) {
        $_SESSION['admin_login_attempts'] = 0;
        $_SESSION['admin_login_lock_until'] = time() + ADMIN_LOGIN_LOCKOUT_SECONDS;

        return [
            'locked' => true,
            'retry_after' => ADMIN_LOGIN_LOCKOUT_SECONDS,
        ];
    }

    return [
        'locked' => false,
        'retry_after' => 0,
        'remaining_attempts' => ADMIN_MAX_LOGIN_ATTEMPTS - $attempts,
    ];
}

function clearAdminLoginThrottle(): void
{
    unset($_SESSION['admin_login_attempts'], $_SESSION['admin_login_last_attempt'], $_SESSION['admin_login_lock_until']);
}

function authenticateAdminCredentials(mysqli $conn, string $username, string $password): ?array
{
    $username = trim($username);

    if ($username === '' || $password === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND role = 'admin' LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan proses login admin.');
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row || !password_verify($password, $row['password'])) {
        return null;
    }

    return $row;
}

function completeAdminLogin(array $admin): void
{
    startAdminSession();
    session_regenerate_id(true);

    $_SESSION['admin_id'] = (int) ($admin['id'] ?? 0);
    $_SESSION['admin_username'] = (string) ($admin['username'] ?? '');
    $_SESSION['admin_role'] = 'admin';
    $_SESSION['login_time'] = time();
    $_SESSION['admin_last_activity'] = time();

    clearAdminLoginThrottle();
}

function logoutAdmin(): void
{
    startAdminSession();
    clearAdminAuthSession();
    clearAdminLoginThrottle();
    session_regenerate_id(true);
}

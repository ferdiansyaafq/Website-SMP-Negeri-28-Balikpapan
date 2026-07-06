<?php

declare(strict_types=1);

use Random\Engine\Secure;
use Random\Randomizer;

require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/pdo.php';

final class ApiSpec
{
    public const string API_VERSION = 'v1';

    public const string CSRF_COOKIE = 'kaih_csrf';
    public const string CSRF_HEADER = 'X-CSRF-Token';

    public const array CONFIG = [
        'csrf_cookie' => self::CSRF_COOKIE,
        'csrf_header' => self::CSRF_HEADER,
        'session_key_admin_id' => 'admin_id',
    ];
}

final readonly class LoginPayload
{
    public function __construct(
        public string $username,
        public string $password,
        public string $csrf,
    ) {
    }
}

final readonly class AdminIdentity
{
    public function __construct(
        public int $id,
        public string $username,
        public string $role,
    ) {
    }
}

// Class alias as required (alias keeps readonly properties).
class_alias(AdminIdentity::class, __NAMESPACE__ . '\\AdminUser');

final readonly class AdminSession
{
    public function __construct(
        public int $adminId,
        public string $sessionId,
    ) {
    }

    public function regenerate(string $newSessionId): self
    {
        $clone = clone $this;

        // PHP 8.3 readonly amendments: allow modifying once on clone.
        $clone->sessionId = $newSessionId;

        return $clone;
    }
}

final class JsonResponse
{
    public function __construct(
        public readonly int $status,
        public readonly array $body,
        public readonly array $headers = [],
    ) {
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');

        $constant = 'API_VERSION';
        header('X-API-Version: ' . ApiSpec::{$constant});

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo json_encode($this->body, JSON_UNESCAPED_SLASHES);
    }
}

abstract class JsonController
{
    abstract public function handle(): JsonResponse;
}

final class CsrfDoubleSubmit
{
    public function __construct(
        private readonly Randomizer $randomizer,
    ) {
    }

    public function validate(string $providedToken): bool
    {
        $cookie = $_COOKIE[ApiSpec::CSRF_COOKIE] ?? '';

        if ($cookie === '' || $providedToken === '') {
            return false;
        }

        return hash_equals($cookie, $providedToken);
    }

    public function newServerToken(): string
    {
        $length = (int) round($this->randomizer->getFloat(32.0, 48.0));
        $seed = $this->randomizer->getBytes(64);
        $bytes = $this->randomizer->getBytesFromString($seed, $length);

        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
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
            'role' => 'admin',
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

final class AdminLoginController extends JsonController
{
    public function __construct(
        private readonly AdminAuthRepository $repo,
        private readonly CsrfDoubleSubmit $csrf,
    ) {
    }

    #[Override]
    public function handle(): JsonResponse
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return new JsonResponse(status: 405, body: [
                'ok' => false,
                'error' => 'Method Not Allowed',
            ]);
        }

        $lockState = getAdminLoginLockState();
        if (!empty($lockState['locked'])) {
            return new JsonResponse(status: 429, body: [
                'ok' => false,
                'error' => 'Terlalu banyak percobaan login. Coba lagi nanti.',
                'retry_after' => (int) ($lockState['retry_after'] ?? 0),
            ]);
        }

        $raw = file_get_contents('php://input');
        $raw = is_string($raw) ? trim($raw) : '';

        if ($raw === '' || !json_validate($raw)) {
            return new JsonResponse(status: 400, body: [
                'ok' => false,
                'error' => 'Invalid JSON payload.',
            ]);
        }

        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            return new JsonResponse(status: 400, body: [
                'ok' => false,
                'error' => 'Invalid JSON object.',
            ]);
        }

        $csrfProvided = (string) ($data['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (!$this->csrf->validate($csrfProvided)) {
            return new JsonResponse(status: 403, body: [
                'ok' => false,
                'error' => 'CSRF validation failed.',
            ]);
        }

        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $password === '') {
            return new JsonResponse(status: 422, body: [
                'ok' => false,
                'error' => 'username dan password wajib diisi.',
            ]);
        }

        $row = $this->repo->findAdminByUsername($username);
        $hash = $row['password'] ?? null;

        if (!is_string($hash) || $row === null || !password_verify($password, $hash)) {
            $state = registerAdminLoginFailure();
            if (!empty($state['locked'])) {
                return new JsonResponse(status: 429, body: [
                    'ok' => false,
                    'error' => 'Terlalu banyak percobaan login. Coba lagi nanti.',
                    'retry_after' => (int) ($state['retry_after'] ?? 0),
                ]);
            }

            return new JsonResponse(status: 401, body: [
                'ok' => false,
                'error' => 'Username atau password salah.',
            ]);
        }

        $adminId = (int) ($row['id'] ?? 0);
        $adminUsername = (string) ($row['username'] ?? '');

        // Upgrade hash to Argon2id if needed.
        if (password_needs_rehash($hash, PASSWORD_ARGON2ID)) {
            $newHash = password_hash($password, PASSWORD_ARGON2ID);
            if (is_string($newHash) && $newHash !== '') {
                $this->repo->upgradePasswordHash($adminId, $newHash);
            }
        }

        completeAdminLogin([
            'id' => $adminId,
            'username' => $adminUsername,
            'role' => 'admin',
        ]);

        // Example of readonly amendments usage.
        $session = new AdminSession(adminId: $adminId, sessionId: session_id());
        $session2 = $session->regenerate(session_id());

        // Nullsafe operator example.
        $identity = new AdminIdentity(id: $adminId, username: $adminUsername, role: 'admin');
        $safeUsername = $identity?->username;

        $tokenRotation = $this->csrf->newServerToken();

        return new JsonResponse(
            status: 200,
            body: [
                'ok' => true,
                'user' => [
                    'id' => $identity->id,
                    'username' => htmlspecialchars($safeUsername ?? '', ENT_QUOTES | ENT_HTML5),
                    'role' => $identity->role,
                ],
                'session' => [
                    'admin_id' => $session2->adminId,
                    'session_id' => $session2->sessionId,
                ],
                'csrf' => [
                    'cookie' => ApiSpec::CSRF_COOKIE,
                    'header' => ApiSpec::CSRF_HEADER,
                    'rotated_token' => $tokenRotation,
                ],
                'meta' => [
                    'api_version' => ApiSpec::API_VERSION,
                    'config' => ApiSpec::CONFIG,
                ],
            ],
            headers: [
                // Demonstrate dynamic class constant fetch for header name as well.
                (ApiSpec::{'CSRF_HEADER'}) => $tokenRotation,
            ]
        );
    }
}

$pdo = getPdo();
$csrf = new CsrfDoubleSubmit(new Randomizer(new Secure()));
$controller = new AdminLoginController(
    repo: new AdminAuthRepository($pdo),
    csrf: $csrf,
);

$controller->handle()->send();

<?php

declare(strict_types=1);

use Random\Engine\Secure;
use Random\Randomizer;

final class ApiSpec
{
    public const string API_VERSION = 'v1';

    public const string CSRF_COOKIE = 'kaih_csrf';
    public const string CSRF_HEADER = 'X-CSRF-Token';

    public const array CONFIG = [
        'csrf_cookie' => self::CSRF_COOKIE,
        'csrf_header' => self::CSRF_HEADER,
    ];
}

/**
 * CSRF double-submit cookie endpoint.
 *
 * - Sets a cookie (not HttpOnly, so JS can read and send it back)
 * - Returns the same token in JSON
 */
final class CsrfDoubleSubmit
{
    public function __construct(
        private readonly Randomizer $randomizer,
    ) {
    }

    public function issueToken(): string
    {
        $length = (int) round($this->randomizer->getFloat(32.0, 48.0));

        // Use getBytesFromString() as required by spec.
        $seed = $this->randomizer->getBytes(64);
        $bytes = $this->randomizer->getBytesFromString($seed, $length);

        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public function setCookie(string $token): void
    {
        $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        setcookie(
            ApiSpec::CSRF_COOKIE,
            $token,
            [
                'expires' => 0,
                'path' => '/',
                'secure' => $isSecure,
                'httponly' => false,
                'samesite' => 'Lax',
            ]
        );
    }
}

header('Content-Type: application/json; charset=utf-8');

$constant = 'API_VERSION';
header('X-API-Version: ' . ApiSpec::{$constant});

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'error' => 'Method Not Allowed',
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

$service = new CsrfDoubleSubmit(new Randomizer(new Secure()));
$token = $service->issueToken();
$service->setCookie($token);

http_response_code(200);
echo json_encode([
    'ok' => true,
    'token' => $token,
    'cookie' => ApiSpec::CSRF_COOKIE,
    'header' => ApiSpec::CSRF_HEADER,
    'config' => ApiSpec::CONFIG,
], JSON_UNESCAPED_SLASHES);

<?php
require_once __DIR__ . '/config.php';

/**
 * Create a secure token (no JWT library needed - pure PHP).
 * Format: base64url( header.payload.signature )
 */
function createToken(array $payload): string {
    $header  = base64url(['alg' => 'HS256', 'typ' => 'TOKEN']);
    $payload['exp'] = time() + TOKEN_TTL;
    $body    = base64url($payload);
    $sig     = hash_hmac('sha256', "$header.$body", JWT_SECRET, true);
    return "$header.$body." . rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
}

function base64url(array $data): string {
    return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
}

/**
 * Verify a token and return its payload, or null if invalid.
 */
function verifyToken(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$header, $body, $sig] = $parts;
    $expected = hash_hmac('sha256', "$header.$body", JWT_SECRET, true);
    $expected = rtrim(strtr(base64_encode($expected), '+/', '-_'), '=');

    if (!hash_equals($expected, $sig)) return null;

    $payload = json_decode(base64_decode(strtr($body, '-_', '+/')), true);
    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) return null;

    return $payload;
}

/**
 * Get token from request header.
 */
function getTokenFromRequest(): ?string {
    return $_SERVER['HTTP_X_AUTH_TOKEN']
        ?? (function_exists('apache_request_headers')
            ? (apache_request_headers()['X-Auth-Token'] ?? null)
            : null);
}

/**
 * Require valid auth token – returns user array or calls fail().
 */
function requireAuth(): array {
    $token   = getTokenFromRequest();
    if (!$token) fail('Authentication required.', 401);

    $payload = verifyToken($token);
    if (!$payload) fail('Invalid or expired session. Please log in again.', 401);

    // Verify user still exists in DB
    $stmt = db()->prepare('SELECT id, name, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$payload['uid']]);
    $user = $stmt->fetch();
    if (!$user) fail('User not found.', 401);

    return $user;
}

/**
 * For PHP pages (dashboard.php) — validate via session or token cookie.
 * Returns user array or null.
 */
function validateSession(): ?array {
    // Try session first
    if (!empty($_SESSION['user_id'])) {
        $stmt = db()->prepare('SELECT id, name, email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }

    // Try cookie token
    $token = $_COOKIE['mg_token'] ?? '';
    if ($token) {
        $payload = verifyToken($token);
        if ($payload) {
            $stmt = db()->prepare('SELECT id, name, email FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$payload['uid']]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                return $user;
            }
        }
    }

    return null;
}

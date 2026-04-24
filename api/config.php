<?php
/**
 * MANDALA GALLERY – CONFIG
 * Edit DB credentials below after creating your MySQL database in the panel.
 */

// ── DATABASE ──────────────────────────────────────────────
define('DB_HOST', 'sql100.infinityfree.com');
define('DB_NAME', 'if0_41728866_ghimireaastha');      // From the bold text in your sidebar
define('DB_USER', 'if0_41728866');      // Usually the same as DB_NAME on shared hosts
define('DB_PASS', 'TFsCQTeTDfrn'); // The UUID you sent previously

// ── SECURITY ──────────────────────────────────────────────
// Generate one at: https://www.uuidgenerator.net/
// Must be at least 32 characters.
define('JWT_SECRET', 'db9023fb-9f4b-4917-b623-17b179ecbbfe');

// Token lifetime in seconds (7 days)
define('TOKEN_TTL', 60 * 60 * 24 * 7);

// ── UPLOADS ───────────────────────────────────────────────
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_BYTES', 8 * 1024 * 1024); // 8 MB
define('ALLOWED_MIME', ['image/jpeg', 'image/png', 'image/webp']);

// ── SITE ──────────────────────────────────────────────────
define('SITE_URL', 'https://ghimireaastha.infinityfree.me');

// ── DB CONNECTION (singleton) ─────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
            exit;
        }
    }
    return $pdo;
}

// ── JSON RESPONSE HELPERS ─────────────────────────────────
function ok($data = null, $message = ''): void {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $message]);
    exit;
}

function fail(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// ── CORS + JSON HEADER ────────────────────────────────────
function initAPI(): void {
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === SITE_URL || $origin === 'https://www.' . ltrim(SITE_URL, 'https://')) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

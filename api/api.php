<?php
/**
 * MANDALA GALLERY – MAIN API
 * Single entry-point for all AJAX calls.
 * URL: /api/api.php?action=xxx  (GET) or body {action:xxx} (POST)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

initAPI();

// ── ROUTE ─────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$action = '';

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
} else {
    // Read JSON body for POST
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? $_POST['action'] ?? '';
}

// ── PUBLIC ROUTES (no auth) ───────────────────────────────
switch ($action) {

    case 'mandalas':
        $cat   = $_GET['category'] ?? '';
        $sql   = 'SELECT id, title, description, category, created_at FROM mandalas WHERE is_public=1';
        $params = [];
        if ($cat && in_array($cat, ['geometric','floral','spiritual','abstract'])) {
            $sql  .= ' AND category=?';
            $params[] = $cat;
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        ok($stmt->fetchAll());

    case 'mandala':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) fail('Invalid ID');
        $stmt = db()->prepare('SELECT id, title, description, category, created_at FROM mandalas WHERE id=? AND is_public=1 LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) fail('Not found', 404);
        ok($row);

    case 'blogs':
        $stmt = db()->prepare('SELECT id, title, excerpt, created_at FROM blog_posts WHERE is_published=1 ORDER BY created_at DESC LIMIT 20');
        $stmt->execute();
        ok($stmt->fetchAll());

    case 'blog_post':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) fail('Invalid ID');
        $stmt = db()->prepare('SELECT id, title, excerpt, content, created_at FROM blog_posts WHERE id=? AND is_published=1 LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) fail('Not found', 404);
        ok($row);

    case 'login':
        $email    = filter_var($body['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $body['password'] ?? '';
        if (!$email || !$password) fail('Email and password required.');

        $stmt = db()->prepare('SELECT id, name, email, password_hash FROM users WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            fail('Invalid email or password.', 401);
        }

        $token = createToken(['uid' => $user['id'], 'email' => $user['email']]);

        // Set cookie (httpOnly for extra security)
        setcookie('mg_token', $token, [
            'expires'  => time() + TOKEN_TTL,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ok(['token' => $token, 'user' => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']]]);

    case 'logout':
        setcookie('mg_token', '', time() - 3600, '/');
        session_destroy();
        ok(null, 'Logged out');

    // ── PROTECTED ROUTES (require auth) ──────────────────
    default:
        $user = requireAuth();
        handleProtected($action, $body ?? [], $user);
}

// ── PROTECTED HANDLER ─────────────────────────────────────
function handleProtected(string $action, array $body, array $user): void {

    switch ($action) {

        case 'upload_mandala':
            handleUpload($user);

        case 'delete_mandala':
            $id = (int)($body['id'] ?? 0);
            if (!$id) fail('Invalid ID');

            // Get file path first
            $stmt = db()->prepare('SELECT image_path FROM mandalas WHERE id=? AND user_id=? LIMIT 1');
            $stmt->execute([$id, $user['id']]);
            $row = $stmt->fetch();
            if (!$row) fail('Not found or not yours', 404);

            // Delete file
            $path = UPLOAD_DIR . $row['image_path'];
            if (file_exists($path)) @unlink($path);

            // Delete thumb if exists
            $thumb = UPLOAD_DIR . 'thumbs/' . $row['image_path'];
            if (file_exists($thumb)) @unlink($thumb);

            db()->prepare('DELETE FROM mandalas WHERE id=? AND user_id=?')->execute([$id, $user['id']]);
            ok(null, 'Deleted');

        case 'admin_mandalas':
            $stmt = db()->prepare('SELECT id, title, category, created_at FROM mandalas WHERE user_id=? ORDER BY created_at DESC');
            $stmt->execute([$user['id']]);
            ok($stmt->fetchAll());

        case 'create_blog':
            $title   = trim($body['title'] ?? '');
            $excerpt = trim($body['excerpt'] ?? '');
            $content = trim($body['content'] ?? '');
            if (!$title || !$excerpt || !$content) fail('All fields required');

            db()->prepare('INSERT INTO blog_posts (user_id, title, excerpt, content, is_published, created_at) VALUES (?,?,?,?,1,NOW())')
               ->execute([$user['id'], $title, $excerpt, $content]);
            ok(null, 'Published');

        case 'delete_blog':
            $id = (int)($body['id'] ?? 0);
            db()->prepare('DELETE FROM blog_posts WHERE id=? AND user_id=?')->execute([$id, $user['id']]);
            ok(null, 'Deleted');

        case 'admin_blogs':
            $stmt = db()->prepare('SELECT id, title, created_at FROM blog_posts WHERE user_id=? ORDER BY created_at DESC');
            $stmt->execute([$user['id']]);
            ok($stmt->fetchAll());

        case 'stats':
            $m  = db()->prepare('SELECT COUNT(*) FROM mandalas WHERE user_id=?'); $m->execute([$user['id']]);
            $b  = db()->prepare('SELECT COUNT(*) FROM blog_posts WHERE user_id=?'); $b->execute([$user['id']]);
            $dl = db()->prepare('SELECT COUNT(*) FROM download_log WHERE user_id=?'); $dl->execute([$user['id']]);
            ok(['mandalas' => (int)$m->fetchColumn(), 'blogs' => (int)$b->fetchColumn(), 'downloads' => (int)$dl->fetchColumn()]);

        case 'get_settings':
            $stmt = db()->prepare('SELECT artist_initials, artist_name FROM user_settings WHERE user_id=? LIMIT 1');
            $stmt->execute([$user['id']]);
            ok($stmt->fetch() ?: (object)[]);

        case 'save_settings':
            $initials    = substr(trim($body['artist_initials'] ?? ''), 0, 10);
            $artist_name = substr(trim($body['artist_name'] ?? ''), 0, 80);
            $stmt = db()->prepare('SELECT id FROM user_settings WHERE user_id=? LIMIT 1');
            $stmt->execute([$user['id']]);
            if ($stmt->fetch()) {
                db()->prepare('UPDATE user_settings SET artist_initials=?, artist_name=? WHERE user_id=?')
                   ->execute([$initials, $artist_name, $user['id']]);
            } else {
                db()->prepare('INSERT INTO user_settings (user_id, artist_initials, artist_name) VALUES (?,?,?)')
                   ->execute([$user['id'], $initials, $artist_name]);
            }
            ok(null, 'Saved');

        case 'change_password':
            $current = $body['current'] ?? '';
            $new     = $body['new_password'] ?? '';
            if (strlen($new) < 8) fail('Password must be at least 8 characters');

            $stmt = db()->prepare('SELECT password_hash FROM users WHERE id=? LIMIT 1');
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch();
            if (!password_verify($current, $row['password_hash'])) fail('Current password is incorrect', 401);

            db()->prepare('UPDATE users SET password_hash=? WHERE id=?')
               ->execute([password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]), $user['id']]);
            ok(null, 'Password updated');

        default:
            fail('Unknown action', 400);
    }
}

// ── FILE UPLOAD ───────────────────────────────────────────
function handleUpload(array $user): void {
    if (!isset($_FILES['image'])) fail('No file received');

    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) fail('Upload error code: ' . $file['error']);
    if ($file['size'] > MAX_FILE_BYTES)   fail('File too large (max 8 MB)');

    // Verify MIME type from actual file content
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_MIME)) fail('Invalid file type. Use JPG, PNG or WEBP.');

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? '');

    if (!$title) fail('Title is required');
    if (!in_array($category, ['geometric','floral','spiritual','abstract'])) fail('Invalid category');

    // Safe filename
    $ext      = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
    $filename = bin2hex(random_bytes(12)) . '.' . $ext;

    // Ensure upload dir exists
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    if (!is_dir(UPLOAD_DIR . 'thumbs/')) mkdir(UPLOAD_DIR . 'thumbs/', 0755, true);

    $dest = UPLOAD_DIR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) fail('Could not save file. Check folder permissions.');

    // Create thumbnail (using GD — no composer needed)
    createThumb($dest, UPLOAD_DIR . 'thumbs/' . $filename, 600, $mime);

    // Insert into DB
    db()->prepare('INSERT INTO mandalas (user_id, title, description, category, image_path, is_public, created_at) VALUES (?,?,?,?,?,1,NOW())')
       ->execute([$user['id'], $title, $description, $category, $filename]);

    ok(null, 'Uploaded successfully');
}

function createThumb(string $src, string $dest, int $maxW, string $mime): void {
    try {
        switch ($mime) {
            case 'image/jpeg': $img = imagecreatefromjpeg($src); break;
            case 'image/png':  $img = imagecreatefrompng($src);  break;
            case 'image/webp': $img = imagecreatefromwebp($src); break;
            default: return;
        }
        if (!$img) return;

        $w = imagesx($img); $h = imagesy($img);
        if ($w <= $maxW) { copy($src, $dest); imagedestroy($img); return; }

        $nw = $maxW; $nh = (int)round($h * $maxW / $w);
        $thumb = imagecreatetruecolor($nw, $nh);

        // Preserve transparency for PNG
        if ($mime === 'image/png') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }
        imagecopyresampled($thumb, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        switch ($mime) {
            case 'image/jpeg': imagejpeg($thumb, $dest, 85); break;
            case 'image/png':  imagepng($thumb, $dest, 8);   break;
            case 'image/webp': imagewebp($thumb, $dest, 82); break;
        }
        imagedestroy($img);
        imagedestroy($thumb);
    } catch (Throwable $e) {
        // Non-fatal — thumbnail creation failure doesn't break upload
    }
}

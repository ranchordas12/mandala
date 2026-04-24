<?php
/**
 * PROTECTED DOWNLOAD — /api/download.php?id=N
 *
 * Serves the image with your artist initials / name embedded in
 * the EXIF / IPTC metadata so ownership is provable.
 * Works using PHP's built-in GD + Imagick (if available) or raw JPEG EXIF injection.
 */
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); exit('Invalid request'); }

// ── Get mandala info ────────────────────────────────────────
$stmt = db()->prepare('SELECT m.*, s.artist_initials, s.artist_name
    FROM mandalas m
    LEFT JOIN user_settings s ON s.user_id = m.user_id
    WHERE m.id=? AND m.is_public=1 LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) { http_response_code(404); exit('Not found'); }

$srcPath = UPLOAD_DIR . $row['image_path'];
if (!file_exists($srcPath)) { http_response_code(404); exit('File missing'); }

// ── Log download ────────────────────────────────────────────
try {
    db()->prepare('INSERT INTO download_log (mandala_id, user_id, ip, downloaded_at) VALUES (?,?,?,NOW())')
       ->execute([$id, $row['user_id'], $_SERVER['REMOTE_ADDR'] ?? '']);
} catch (Throwable $e) { /* non-fatal */ }

// ── Prepare metadata strings ────────────────────────────────
$initials    = $row['artist_initials'] ?: 'AG';
$artistName  = $row['artist_name']     ?: 'Aastha Ghimire';
$copyright   = 'Copyright © ' . date('Y') . ' ' . $artistName . ' (' . $initials . '). All rights reserved.';
$artist      = $artistName . ' | ' . $initials;
$description = $row['title'] . ' — Mandala by ' . $artistName;

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($srcPath);

$safeTitle = preg_replace('/[^a-z0-9_-]/i', '_', $row['title']);
$filename  = $safeTitle . '_by_' . $initials . '.' . pathinfo($srcPath, PATHINFO_EXTENSION);

// ── Attempt Imagick EXIF embed (best, available on many hosts) ──
if (extension_loaded('imagick')) {
    try {
        $img = new Imagick($srcPath);
        $img->setImageProperty('exif:Copyright', $copyright);
        $img->setImageProperty('exif:Artist', $artist);
        $img->setImageProperty('exif:ImageDescription', $description);
        $img->setImageProperty('Exif:UserComment', 'Protected artwork – ' . SITE_URL);
        $img->setImageFormat(pathinfo($srcPath, PATHINFO_EXTENSION));

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Protected-By: ' . $initials);
        header('Cache-Control: no-store');

        echo $img->getImageBlob();
        $img->destroy();
        exit;
    } catch (Throwable $e) {
        // fall through to GD method
    }
}

// ── Fallback: GD re-encode with comment ─────────────────────
// JPEG allows embedding a comment block which most EXIF readers pick up.
if ($mime === 'image/jpeg') {
    $img = @imagecreatefromjpeg($srcPath);
    if ($img) {
        ob_start();
        imagejpeg($img, null, 90);
        $jpegData = ob_get_clean();
        imagedestroy($img);

        // Inject JPEG COM segment (comment) with ownership string
        $comment  = "Artist: $artist | $copyright | " . SITE_URL;
        $jpegData = injectJpegComment($jpegData, $comment);

        header('Content-Type: image/jpeg');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($jpegData));
        header('X-Protected-By: ' . $initials);
        header('Cache-Control: no-store');
        echo $jpegData;
        exit;
    }
}

// ── Last resort: serve file as-is ───────────────────────────
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Protected-By: ' . $initials);
header('Cache-Control: no-store');
readfile($srcPath);
exit;

// ── JPEG Comment Injection ───────────────────────────────────
function injectJpegComment(string $data, string $comment): string {
    // Insert COM segment right after SOI marker (first 2 bytes: FF D8)
    if (substr($data, 0, 2) !== "\xFF\xD8") return $data;

    $len = strlen($comment) + 2; // 2 bytes for length field itself
    $com = "\xFF\xFE" . pack('n', $len) . $comment;

    return "\xFF\xD8" . $com . substr($data, 2);
}

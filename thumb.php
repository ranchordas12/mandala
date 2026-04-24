<?php
/**
 * THUMBNAIL SERVE — /api/thumb.php?id=N
 */
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); exit; }

$stmt = db()->prepare('SELECT image_path FROM mandalas WHERE id=? AND is_public=1 LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) { http_response_code(404); exit; }

// Prefer thumbnail, fallback to original
$thumb = UPLOAD_DIR . 'thumbs/' . $row['image_path'];
$orig  = UPLOAD_DIR . $row['image_path'];
$path  = file_exists($thumb) ? $thumb : (file_exists($orig) ? $orig : null);

if (!$path) { http_response_code(404); exit; }

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($path);

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;

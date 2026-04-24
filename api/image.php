<?php
/**
 * FULL IMAGE SERVE — /api/image.php?id=N
 * Serves the original image file from the uploads folder.
 */
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); exit; }

$stmt = db()->prepare('SELECT image_path, title FROM mandalas WHERE id=? AND is_public=1 LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) { http_response_code(404); exit; }

$path = UPLOAD_DIR . $row['image_path'];
if (!file_exists($path)) { http_response_code(404); exit; }

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($path);

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=3600');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;

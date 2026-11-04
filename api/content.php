<?php
/**
 * GET /api/content.php
 * Devuelve, en una sola respuesta, slides, items (servicios y productos),
 * nosotros (imágenes, valores, historia) y ajustes. Sale del caché JSON del servidor
 * (sin tocar la BD) y se valida con ETag: si no cambió, responde 304 sin cuerpo.
 */
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/content.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    json_fail('Método no permitido.', 405);
}

$raw  = content_raw();
$etag = '"' . md5($raw) . '"';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('ETag: ' . $etag);
header('Cache-Control: public, max-age=0, must-revalidate');
$f = content_path();
if (is_file($f)) { header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int)filemtime($f)) . ' GMT'); }

$sent = trim(str_replace('W/', '', (string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')));
if ($sent === $etag) {
    http_response_code(304);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') { echo $raw; }

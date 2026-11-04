<?php
/**
 * Hoja de estilos dinámica (archivo CSS externo, no estilos en línea):
 * aplica los colores opcionales definidos en Ajustes del sitio.
 * Se carga después de variables.css y solo si hay colores configurados.
 */
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/content.php';

$a = content_get()['ajustes'];
$acento = valid_hex((string)$a['color_acento']) ? strtolower((string)$a['color_acento']) : '';
$css = '';
if ($acento !== '') {
    $texto = valid_hex((string)$a['color_acento_texto']) ? strtolower((string)$a['color_acento_texto']) : '';
    if ($texto === '') { // blanco o casi negro, según la luminosidad del acento
        [$r, $g, $b] = [hexdec(substr($acento, 1, 2)), hexdec(substr($acento, 3, 2)), hexdec(substr($acento, 5, 2))];
        $texto = (0.299 * $r + 0.587 * $g + 0.114 * $b) > 160 ? '#16324a' : '#ffffff';
    }
    $css = ":root{--accent:{$acento};--accent-ink:{$texto}}\n";
}
$etag = '"' . md5($css) . '"';
header('Content-Type: text/css; charset=utf-8');
header('ETag: ' . $etag);
header('Cache-Control: public, max-age=31536000, immutable'); // la URL lleva ?v=versión
if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); exit; }
echo "/* Tema dinámico */\n" . $css;

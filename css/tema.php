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
    [$r, $g, $b] = [hexdec(substr($acento, 1, 2)), hexdec(substr($acento, 3, 2)), hexdec(substr($acento, 5, 2))];
    $texto = valid_hex((string)$a['color_acento_texto']) ? strtolower((string)$a['color_acento_texto']) : '';
    if ($texto === '') { // blanco o casi negro, según la luminosidad del acento
        $texto = (0.299 * $r + 0.587 * $g + 0.114 * $b) > 160 ? '#16324a' : '#ffffff';
    }
    // --accent2: el mismo color un 12 % más oscuro (botones al pasar el cursor)
    $hover = sprintf('#%02x%02x%02x', (int)round($r * 0.88), (int)round($g * 0.88), (int)round($b * 0.88));
    // --accent-soft: el acento al 12 % de opacidad (fondo de los íconos)
    $suave = "rgba({$r},{$g},{$b},.12)";
    $css = ":root{--accent:{$acento};--accent2:{$hover};--accent-ink:{$texto};--accent-soft:{$suave}}\n";
}
$etag = '"' . md5($css) . '"';
header('Content-Type: text/css; charset=utf-8');
header('ETag: ' . $etag);
header('Cache-Control: public, max-age=31536000, immutable'); // la URL lleva ?v=versión
if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); exit; }
echo "/* Tema dinámico */\n" . $css;

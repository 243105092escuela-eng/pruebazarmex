<?php
/** Fragmentos HTML compartidos (se usan en index.php; app.js genera el mismo marcado). */
declare(strict_types=1);

require_once __DIR__ . '/icons.php';

function placeholder_uri(string $label, int $hue): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 600"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
         . '<stop offset="0" stop-color="hsl(' . $hue . ',45%,38%)"/><stop offset="1" stop-color="hsl(' . ($hue + 40) . ',50%,24%)"/></linearGradient></defs>'
         . '<rect width="1600" height="600" fill="url(#g)"/><text x="800" y="320" fill="rgba(255,255,255,.7)" font-family="sans-serif" font-size="56" text-anchor="middle">'
         . htmlspecialchars($label, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</text></svg>';
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

/** Ruta del .webp hermano si existe en disco; null si no. */
function webp_sibling(?string $rel): ?string
{
    if (!$rel) { return null; }
    $w = preg_replace('/\.(jpe?g|png)$/i', '.webp', $rel);
    return ($w && $w !== $rel && is_file(ROOT_PATH . '/' . $w)) ? $w : null;
}

/**
 * <picture> con WebP cuando existe; si no hay imagen, usa el marcador generado.
 * $o: priority (bool, primera imagen visible), label, hue
 */
function picture_html(?string $img, ?string $webp, string $alt, int $w, int $h, array $o = []): string
{
    $attrs = ' width="' . $w . '" height="' . $h . '"'
           . (!empty($o['priority']) ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"');
    if (!$img) {
        return '<img src="' . e(placeholder_uri((string)($o['label'] ?? 'Imagen'), (int)($o['hue'] ?? 200))) . '" alt="' . e($alt) . '"' . $attrs . '>';
    }
    $h2 = '<picture>';
    if ($webp) { $h2 .= '<source type="image/webp" srcset="' . e(url($webp)) . '">'; }
    return $h2 . '<img src="' . e(url($img)) . '" alt="' . e($alt) . '"' . $attrs . '></picture>';
}

function slide_html(array $s, int $i, int $n, bool $active): string
{
    $img = picture_html($s['imagen'] ?? null, $s['webp'] ?? null, (string)$s['titulo'], 1600, 600, [
        'priority' => $i === 0, 'label' => 'Imagen ' . ($i + 1), 'hue' => 200 + $i * 35,
    ]);
    $cta = !empty($s['enlace']) ? '<a class="btn" href="' . e((string)$s['enlace']) . '">Contáctanos</a>' : '';
    return '<div class="slide' . ($active ? ' on' : '') . '" role="group" aria-label="' . ($i + 1) . ' de ' . $n . '" data-id="' . (int)$s['id'] . '">'
         . $img
         . '<div class="cap"><div class="wrap"><h1>' . e((string)$s['titulo']) . '</h1><p>' . e((string)$s['subtitulo']) . '</p>' . $cta . '</div></div></div>';
}

function dots_html(int $n): string
{
    $h = '';
    for ($i = 0; $i < $n; $i++) {
        $h .= '<button aria-label="Ir a la diapositiva ' . ($i + 1) . '" data-i="' . $i . '" class="' . ($i === 0 ? 'on' : '') . '"></button>';
    }
    return $h;
}

function valor_html(array $v): string
{
    return '<div class="card val">' . valor_icono_svg((string)$v['icono']) . '<h3>' . e((string)$v['titulo']) . '</h3><p>' . e((string)$v['texto']) . '</p></div>';
}

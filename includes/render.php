<?php
/** Fragmentos HTML compartidos (se usan en index.php; app.js genera el mismo marcado). */
declare(strict_types=1);

require_once __DIR__ . '/icons.php';

/** Marcador con texto centrado (lo usa el panel para "Sin logo", "Sin imagen", etc.). */
function placeholder_uri(string $label, int $hue): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 600"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
         . '<stop offset="0" stop-color="hsl(' . $hue . ',45%,38%)"/><stop offset="1" stop-color="hsl(' . ($hue + 40) . ',50%,24%)"/></linearGradient></defs>'
         . '<rect width="1600" height="600" fill="url(#g)"/><text x="800" y="320" fill="rgba(255,255,255,.7)" font-family="sans-serif" font-size="56" text-anchor="middle">'
         . htmlspecialchars($label, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</text></svg>';
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

/**
 * Marcador del sitio público: degradado con un ícono discreto en la esquina y sin texto,
 * para que no se encime con los títulos del carrusel y de Nosotros.
 * Debe coincidir con ph() de js/app.js.
 */
function placeholder_plain_uri(int $hue): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 600"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
         . '<stop offset="0" stop-color="hsl(' . $hue . ',35%,40%)"/><stop offset="1" stop-color="hsl(' . ($hue + 40) . ',40%,22%)"/></linearGradient></defs>'
         . '<rect width="1600" height="600" fill="url(#g)"/><g transform="translate(1300 460)" fill="none" stroke="rgba(255,255,255,.28)" stroke-width="6" stroke-linejoin="round">'
         . '<rect x="-60" y="-45" width="120" height="90" rx="10"/><circle cx="-25" cy="-15" r="11"/><path d="M-60 35l40-35 25 20 20-15 35 30"/></g></svg>';
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
 * $o: priority (bool, primera imagen visible), hue
 */
function picture_html(?string $img, ?string $webp, string $alt, int $w, int $h, array $o = []): string
{
    $attrs = ' width="' . $w . '" height="' . $h . '"'
           . (!empty($o['priority']) ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"');
    if (!$img) {
        return '<img src="' . e(placeholder_plain_uri((int)($o['hue'] ?? 200))) . '" alt="' . e($alt) . '"' . $attrs . '>';
    }
    $h2 = '<picture>';
    if ($webp) { $h2 .= '<source type="image/webp" srcset="' . e(url($webp)) . '">'; }
    return $h2 . '<img src="' . e(url($img)) . '" alt="' . e($alt) . '"' . $attrs . '></picture>';
}

/**
 * Marca: logo (si hay) + nombre; sin logo, la inicial en un cuadro + nombre.
 * $priority = true solo para el logo del encabezado (primera imagen visible).
 */
function brand_html(string $logo, string $logoWebp, string $empresa, bool $priority): string
{
    if ($logo !== '') {
        $img = picture_html($logo, $logoWebp !== '' ? $logoWebp : null, '', 160, 40, ['priority' => $priority]);
    } else {
        $ini = mb_strtoupper(mb_substr((string)preg_replace('/[^\p{L}\p{N}]/u', '', $empresa), 0, 1, 'UTF-8'), 'UTF-8');
        $img = '<span class="mark" aria-hidden="true">' . e($ini !== '' ? $ini : 'E') . '</span>';
    }
    return $img . '<span class="lname">' . e($empresa) . '</span>';
}

/** Botones del slide: el enlace configurado en el panel (o "Solicitar cotización") + "Ver servicios". */
function slide_ctas(string $enlace): string
{
    $main = $enlace !== ''
        ? '<a class="btn" href="' . e($enlace) . '">Contáctanos</a>'
        : '<a class="btn" href="#servicios">Solicitar cotización</a>';
    return '<div class="ctas">' . $main . '<a class="btn ghost" href="#svcSec">Ver servicios</a></div>';
}

function slide_html(array $s, int $i, int $n, bool $active): string
{
    $img = picture_html($s['imagen'] ?? null, $s['webp'] ?? null, '', 1600, 600, [
        'priority' => $i === 0, 'hue' => 200 + $i * 35,
    ]);
    $tag = $i === 0 ? 'h1' : 'h2'; // un solo h1 por página
    return '<div class="slide' . ($active ? ' on' : '') . '" role="group" aria-label="' . ($i + 1) . ' de ' . $n . '" data-id="' . (int)$s['id'] . '">'
         . $img
         . '<div class="cap"><div class="wrap"><div class="cap-in"><' . $tag . ' class="h1">' . e((string)$s['titulo']) . '</' . $tag . '>'
         . '<p>' . e((string)$s['subtitulo']) . '</p>' . slide_ctas((string)($s['enlace'] ?? '')) . '</div></div></div></div>';
}

function dots_html(int $n): string
{
    $h = '';
    for ($i = 0; $i < $n; $i++) {
        $h .= '<button type="button" aria-label="Ir a la diapositiva ' . ($i + 1) . '" data-i="' . $i . '" class="' . ($i === 0 ? 'on' : '') . '"></button>';
    }
    return $h;
}

function valor_html(array $v): string
{
    return '<div class="card val"><span class="ico">' . valor_icono_svg((string)$v['icono']) . '</span><h3>' . e((string)$v['titulo']) . '</h3><p>' . e((string)$v['texto']) . '</p></div>';
}

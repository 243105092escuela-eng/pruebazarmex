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

/* ------------------------------------------------------------------ */
/*  Secciones del rediseño: cifras, «¿Por qué elegirnos?», testimonios,*/
/*  preguntas frecuentes, secciones extra, redes sociales y WhatsApp   */
/* ------------------------------------------------------------------ */

/** Texto con saltos de línea -> párrafos <p> (escapados). */
function paras_html(string $s): string
{
    $out = '';
    foreach (preg_split('/\n+/', $s) ?: [] as $p) {
        $p = trim($p);
        if ($p !== '') { $out .= '<p>' . e($p) . '</p>'; }
    }
    return $out;
}

/** Iniciales (máx. 2) para el avatar de un testimonio sin foto. */
function iniciales(string $nombre): string
{
    $ini = '';
    foreach (array_slice(preg_split('/\s+/u', trim($nombre)) ?: [], 0, 2) as $w) {
        $ini .= mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return $ini !== '' ? $ini : '?';
}

/** Enlace de WhatsApp (wa.me) o '' si no hay número. */
function whatsapp_url(array $a): string
{
    $num = preg_replace('/\D/', '', (string)($a['whatsapp'] ?? ''));
    if ($num === '' || $num === null) { return ''; }
    $msg = trim((string)($a['whatsapp_msg'] ?? ''));
    return 'https://wa.me/' . $num . ($msg !== '' ? '?text=' . rawurlencode($msg) : '');
}

/** Íconos de las redes que tienen enlace (https://…). '' si no hay ninguna. */
function redes_html(array $a): string
{
    $h = '';
    foreach (redes_sociales() as $k => [$nombre, $paths, $contorno]) {
        $link = trim((string)($a[$k] ?? ''));
        if (!preg_match('~^https?://[^\s]+$~i', $link)) { continue; }
        $h .= '<a href="' . e($link) . '" target="_blank" rel="noopener" aria-label="' . e($nombre) . '" title="' . e($nombre) . '">'
            . '<svg viewBox="0 0 24 24"' . ($contorno ? ' class="st"' : '') . ' aria-hidden="true">' . $paths . '</svg></a>';
    }
    return $h;
}

function cifras_html(array $cifras): string
{
    $h = '';
    foreach ($cifras as $c) { $h .= '<div class="stat"><b>' . e((string)$c['numero']) . '</b><span>' . e((string)$c['texto']) . '</span></div>'; }
    return $h;
}

function punto_html(array $p): string
{
    return '<div class="feat"><span class="ico">' . valor_icono_svg((string)$p['icono']) . '</span><h3>' . e((string)$p['titulo']) . '</h3><p>' . e((string)$p['texto']) . '</p></div>';
}

function testimonio_html(array $t): string
{
    $av = $t['imagen']
        ? picture_html($t['imagen'], $t['webp'] ?? null, '', 96, 96)
        : e(iniciales((string)$t['nombre']));
    return '<figure class="card tst">'
         . '<svg class="q" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 18v-5.5C4 8.4 6.2 6 9.5 5.5l.6 1.9C8.3 8 7.5 9.4 7.4 11H10v7zm10 0v-5.5c0-4.1 2.2-6.5 5.5-7l.6 1.9c-1.8.6-2.6 2-2.7 3.6H20v7z"/></svg>'
         . '<blockquote>' . e((string)$t['texto']) . '</blockquote>'
         . '<figcaption class="who"><span class="av">' . $av . '</span><div><b>' . e((string)$t['nombre']) . '</b><span>' . e((string)$t['cargo']) . '</span></div></figcaption>'
         . '</figure>';
}

function pregunta_html(array $q): string
{
    return '<details><summary>' . e((string)$q['pregunta']) . '</summary><div class="ans">' . paras_html((string)$q['respuesta']) . '</div></details>';
}

/**
 * Secciones extra de una página. $soft: si la primera lleva fondo suave
 * (para alternar con la sección anterior).
 */
function secciones_html(array $lista, bool $soft): string
{
    $h = '';
    foreach (array_values($lista) as $i => $s) {
        $conImg = !empty($s['imagen']);
        $cls = 'wrap blk reveal' . ($conImg ? ' has-img' . ($s['lado'] === 'izquierda' ? ' img-left' : '') : '');
        $h .= '<div class="sec' . ((($i % 2) === 0) === $soft ? ' soft' : '') . '"><div class="' . $cls . '">'
            . '<div class="blk-txt">' . ($s['titulo'] !== '' ? '<h2>' . e((string)$s['titulo']) . '</h2>' : '') . paras_html((string)$s['texto']) . '</div>'
            . ($conImg ? '<div class="blk-img">' . picture_html($s['imagen'], $s['webp'] ?? null, (string)$s['titulo'], 1200, 900) . '</div>' : '')
            . '</div></div>';
    }
    return $h;
}

/** Ícono de la pestaña: inicial de la empresa con los colores de la marca (cuando no hay logo). */
function favicon_uri(string $empresa): string
{
    $ini = mb_strtoupper(mb_substr((string)preg_replace('/[^\p{L}\p{N}]/u', '', $empresa), 0, 1, 'UTF-8'), 'UTF-8') ?: 'E';
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#16324a"/>'
         . '<rect x="14" y="48" width="36" height="5" rx="2.5" fill="#e0861a"/>'
         . '<text x="32" y="41" font-family="Segoe UI,Arial,sans-serif" font-weight="800" font-size="34" fill="#fff" text-anchor="middle">'
         . htmlspecialchars($ini, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</text></svg>';
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

/** URL absoluta (para og:image, que las redes necesitan completa). */
function absolute_url(string $path): string
{
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
    return ($host !== '' ? (is_https() ? 'https://' : 'http://') . $host : '') . url($path);
}

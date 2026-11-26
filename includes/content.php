<?php
/**
 * Contenido público: ajustes + slides + items + Nosotros en una sola estructura,
 * guardada en cache/content.json. El panel la regenera cada vez que se guarda algo.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/render.php';

function ajustes_defaults(): array
{
    return [
        'nombre_empresa' => 'Mi empresa', 'logo' => '',
        'historia_p1' => '', 'historia_p2' => '',
        'email' => '', 'telefono' => '',
        'texto_home' => '', 'texto_servicios' => '', 'texto_footer' => 'Todos los derechos reservados.',
        'color_acento' => '', 'color_acento_texto' => '',
        'eslogan' => '', 'direccion' => '', 'horario' => '',
        'whatsapp' => '', 'whatsapp_msg' => '',
        'mision' => '', 'vision' => '',
        'red_facebook' => '', 'red_instagram' => '', 'red_tiktok' => '',
        'red_linkedin' => '', 'red_youtube' => '', 'red_x' => '',
    ];
}

function ajustes_all(): array
{
    $out = ajustes_defaults();
    foreach (db()->query('SELECT clave, valor FROM ajustes')->fetchAll() as $r) {
        if (array_key_exists($r['clave'], $out)) { $out[$r['clave']] = (string)$r['valor']; }
    }
    return $out;
}

function ajustes_save(array $kv): void
{
    $allowed = ajustes_defaults();
    $st = db()->prepare('INSERT INTO ajustes (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
    foreach ($kv as $k => $v) {
        if (array_key_exists($k, $allowed)) { $st->execute([$k, $v]); }
    }
}

function content_path(): string { return CACHE_PATH . '/content.json'; }

function content_build(): array
{
    $pdo = db();
    $aj  = ajustes_all();
    $aj['logo_webp'] = webp_sibling($aj['logo'] ?: null) ?? '';

    $slides = $pdo->query('SELECT id, imagen, titulo, subtitulo, enlace FROM slides WHERE activo = 1 ORDER BY orden, id LIMIT ' . MAX_SLIDES_ACTIVOS)->fetchAll();
    foreach ($slides as &$s) { $s['id'] = (int)$s['id']; $s['webp'] = webp_sibling($s['imagen']); } unset($s);

    $items = $pdo->query('SELECT id, tipo, nombre, slug, descripcion, precio, imagen FROM items WHERE activo = 1 ORDER BY orden, id')->fetchAll();
    foreach ($items as &$it) { $it['id'] = (int)$it['id']; $it['webp'] = webp_sibling($it['imagen']); } unset($it);

    $imgs = ['superior' => ['imagen' => null, 'webp' => null], 'inferior' => ['imagen' => null, 'webp' => null]];
    foreach ($pdo->query('SELECT posicion, imagen FROM nosotros_imagenes')->fetchAll() as $r) {
        $imgs[$r['posicion']] = ['imagen' => $r['imagen'], 'webp' => webp_sibling($r['imagen'])];
    }
    $valores = $pdo->query('SELECT id, titulo, texto, icono FROM valores WHERE activo = 1 ORDER BY orden, id')->fetchAll();
    foreach ($valores as &$v) { $v['id'] = (int)$v['id']; } unset($v);

    $cifras = $pdo->query('SELECT id, numero, texto FROM cifras WHERE activo = 1 ORDER BY orden, id')->fetchAll();
    $puntos = $pdo->query('SELECT id, titulo, texto, icono FROM puntos WHERE activo = 1 ORDER BY orden, id')->fetchAll();
    $testimonios = $pdo->query('SELECT id, nombre, cargo, texto, imagen FROM testimonios WHERE activo = 1 ORDER BY orden, id')->fetchAll();
    foreach ($testimonios as &$t) { $t['webp'] = webp_sibling($t['imagen']); } unset($t);
    $preguntas = $pdo->query('SELECT id, pregunta, respuesta FROM preguntas WHERE activo = 1 ORDER BY orden, id')->fetchAll();
    $secciones = ['inicio' => [], 'nosotros' => []];
    foreach ($pdo->query('SELECT id, pagina, titulo, texto, imagen, lado FROM secciones WHERE activo = 1 ORDER BY orden, id')->fetchAll() as $s) {
        $s['webp'] = webp_sibling($s['imagen']);
        $secciones[$s['pagina']][] = $s;
    }

    $data = [
        'ajustes'  => $aj,
        'slides'   => $slides,
        'items'    => $items,
        'nosotros' => [
            'imagenes' => $imgs,
            'valores'  => $valores,
            'historia' => array_values(array_filter([$aj['historia_p1'], $aj['historia_p2']], 'strlen')),
        ],
        'inicio'   => [
            'cifras'      => $cifras,
            'puntos'      => $puntos,
            'testimonios' => $testimonios,
            'preguntas'   => $preguntas,
        ],
        'secciones' => $secciones,
    ];
    $data['version'] = substr(md5(json_encode($data, JSON_UNESCAPED_UNICODE)), 0, 12);
    return $data;
}

function content_write(array $data): string
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (is_dir(CACHE_PATH) && is_writable(CACHE_PATH)) {
        $tmp = content_path() . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
            @rename($tmp, content_path()) || @unlink($tmp);
        }
    } else {
        error_log('cache/ no es escribible: el contenido se consulta a la BD en cada visita.');
    }
    return $json;
}

/** JSON crudo del contenido (desde caché; si no existe, se construye y se guarda). */
function content_raw(): string
{
    $f = content_path();
    if (is_file($f)) {
        $raw = @file_get_contents($f);
        if ($raw !== false && $raw !== '') { return $raw; }
    }
    return content_write(content_build());
}

function content_get(): array
{
    return json_decode(content_raw(), true, 512, JSON_THROW_ON_ERROR);
}

/** Llamar después de cualquier cambio hecho desde el panel. */
function content_rebuild(): void
{
    content_write(content_build());
}

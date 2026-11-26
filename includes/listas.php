<?php
/**
 * Listas editables del panel con el mismo comportamiento que «Valores» y «Carrusel»:
 * agregar, editar, ocultar, reordenar (⠿ o flechas) y eliminar.
 * Se usan para: cifras, «¿Por qué elegirnos?», testimonios, preguntas frecuentes y secciones extra.
 *
 * Cada lista se describe en listas_def(); las columnas y tablas salen solo de ahí
 * (nunca de lo que envía el navegador), así que se pueden usar en el SQL sin riesgo.
 */
declare(strict_types=1);

require_once __DIR__ . '/icons.php';

/**
 * Tipos de campo: text, textarea, select (con 'opciones'), icono.
 * 'imagen' (opcional): carpeta de uploads/ y ancho máximo.
 */
function listas_def(): array
{
    return [
        'cifras' => [
            'tabla' => 'cifras', 'max' => 8, 'cosa' => 'cifra', 'agregar' => 'Agregar cifra',
            'vacio' => 'No hay cifras: la franja de debajo del carrusel no se muestra.',
            'campos' => [
                'numero' => ['t' => 'text', 'max' => 20, 'label' => 'Número', 'ph' => 'Ej. +10', 'req' => true],
                'texto'  => ['t' => 'text', 'max' => 80, 'label' => 'Texto', 'ph' => 'Ej. años de experiencia', 'req' => true],
            ],
            'nuevo' => ['numero' => '0', 'texto' => 'Nuevo dato'],
        ],
        'puntos' => [
            'tabla' => 'puntos', 'max' => 8, 'cosa' => 'punto', 'agregar' => 'Agregar punto',
            'vacio' => 'No hay puntos: la sección «¿Por qué elegirnos?» no se muestra.',
            'campos' => [
                'icono'  => ['t' => 'icono', 'label' => 'Ícono'],
                'titulo' => ['t' => 'text', 'max' => 80, 'label' => 'Título', 'ph' => 'Título', 'req' => true],
                'texto'  => ['t' => 'text', 'max' => 255, 'label' => 'Texto', 'ph' => 'Texto breve'],
            ],
            'nuevo' => ['icono' => 'check', 'titulo' => 'Nuevo punto', 'texto' => 'Descripción breve.'],
        ],
        'testimonios' => [
            'tabla' => 'testimonios', 'max' => 12, 'cosa' => 'testimonio', 'agregar' => 'Agregar testimonio',
            'vacio' => 'No hay testimonios: la sección no se muestra.',
            'imagen' => ['carpeta' => 'testimonios', 'ancho' => 300, 'label' => 'Foto'],
            'campos' => [
                'nombre' => ['t' => 'text', 'max' => 100, 'label' => 'Nombre', 'ph' => 'Nombre del cliente', 'req' => true],
                'cargo'  => ['t' => 'text', 'max' => 120, 'label' => 'Puesto / empresa', 'ph' => 'Puesto, Empresa (opcional)'],
                'texto'  => ['t' => 'textarea', 'max' => 600, 'label' => 'Opinión', 'ph' => 'Lo que opina del servicio', 'req' => true, 'rows' => 3],
            ],
            'nuevo' => ['nombre' => 'Nombre del cliente', 'cargo' => '', 'texto' => 'Escribe aquí la opinión del cliente.'],
        ],
        'preguntas' => [
            'tabla' => 'preguntas', 'max' => 20, 'cosa' => 'pregunta', 'agregar' => 'Agregar pregunta',
            'vacio' => 'No hay preguntas: la sección no se muestra.',
            'campos' => [
                'pregunta'  => ['t' => 'text', 'max' => 255, 'label' => 'Pregunta', 'ph' => 'Pregunta', 'req' => true],
                'respuesta' => ['t' => 'textarea', 'max' => 2000, 'label' => 'Respuesta', 'ph' => 'Respuesta (Enter para separar párrafos)', 'req' => true, 'rows' => 3, 'multi' => true],
            ],
            'nuevo' => ['pregunta' => 'Nueva pregunta', 'respuesta' => 'Escribe aquí la respuesta.'],
        ],
        'secciones' => [
            'tabla' => 'secciones', 'max' => 12, 'cosa' => 'sección', 'agregar' => 'Agregar sección',
            'vacio' => 'Todavía no hay secciones extra.',
            'imagen' => ['carpeta' => 'secciones', 'ancho' => 1200, 'label' => 'Imagen'],
            'campos' => [
                'pagina' => ['t' => 'select', 'label' => 'Página', 'opciones' => ['inicio' => 'Inicio', 'nosotros' => 'Nosotros']],
                'lado'   => ['t' => 'select', 'label' => 'Imagen a la', 'opciones' => ['derecha' => 'Derecha', 'izquierda' => 'Izquierda']],
                'titulo' => ['t' => 'text', 'max' => 150, 'label' => 'Título', 'ph' => 'Título (opcional)'],
                'texto'  => ['t' => 'textarea', 'max' => 4000, 'label' => 'Texto', 'ph' => 'Texto (Enter para separar párrafos)', 'req' => true, 'rows' => 4, 'multi' => true],
            ],
            'nuevo' => ['pagina' => 'inicio', 'lado' => 'derecha', 'titulo' => 'Nueva sección', 'texto' => 'Escribe aquí el contenido de la sección.'],
        ],
    ];
}

function lista_def(string $key): array
{
    $all = listas_def();
    if (!isset($all[$key])) { json_fail('Lista no válida.', 400); }
    return $all[$key];
}

/** Columnas que se leen de la tabla (id, campos, imagen, orden, activo). */
function lista_cols(array $d): string
{
    $cols = array_merge(['id'], array_keys($d['campos']), isset($d['imagen']) ? ['imagen'] : [], ['orden', 'activo']);
    return implode(', ', array_map(fn($c) => "`$c`", $cols));
}

function lista_rows(array $d): array
{
    return db()->query('SELECT ' . lista_cols($d) . ' FROM `' . $d['tabla'] . '` ORDER BY orden, id')->fetchAll();
}

function lista_row_html(string $key, array $d, array $r, int $i): string
{
    $id = (int)$r['id'];
    $img = $d['imagen'] ?? null;
    $icono = null;
    foreach ($d['campos'] as $c => $f) { if ($f['t'] === 'icono') { $icono = $c; } }
    $media = '';
    if ($img) {
        $media = '<div class="thumb-col">' . ui_thumb($r['imagen'] ?? null, 'Sin ' . mb_strtolower($img['label'], 'UTF-8'), 160 + $i * 40)
               . '<input class="file-in" type="file" name="imagen" accept="image/jpeg,image/png,image/webp" tabindex="-1" aria-label="Elegir ' . e(mb_strtolower($img['label'], 'UTF-8')) . '">'
               . '<button type="button" class="btn alt sm" data-act="pick">' . ($r['imagen'] ? 'Cambiar ' : 'Elegir ') . e(mb_strtolower($img['label'], 'UTF-8')) . '</button>'
               . ($r['imagen'] ? ui_switch('quitar_imagen', false, 'Quitar ' . mb_strtolower($img['label'], 'UTF-8')) : '')
               . '</div>';
    } elseif ($icono) {
        $media = '<div class="val-prev" data-prev>' . valor_icono_svg((string)$r[$icono]) . '</div>';
    }
    $fields = '';
    $meta = '';
    foreach ($d['campos'] as $c => $f) {
        $v = (string)($r[$c] ?? '');
        $aria = ' aria-label="' . e($f['label']) . '"';
        if ($f['t'] === 'text') {
            $fields .= '<input name="' . e($c) . '" maxlength="' . (int)$f['max'] . '" value="' . e($v) . '"' . $aria . ' placeholder="' . e($f['ph'] ?? '') . '">';
        } elseif ($f['t'] === 'textarea') {
            $fields .= '<textarea name="' . e($c) . '" rows="' . (int)($f['rows'] ?? 3) . '" maxlength="' . (int)$f['max'] . '"' . $aria . ' placeholder="' . e($f['ph'] ?? '') . '">' . e($v) . '</textarea>';
        } elseif ($f['t'] === 'select' || $f['t'] === 'icono') {
            $ops = $f['t'] === 'icono' ? array_map(fn($x) => $x[0], valor_iconos()) : $f['opciones'];
            $o = '';
            foreach ($ops as $k => $lab) {
                $o .= '<option value="' . e((string)$k) . '"' . ($f['t'] === 'icono' ? ' data-svg="' . e(valor_icono_svg((string)$k)) . '"' : '') . ((string)$k === $v ? ' selected' : '') . '>' . e($lab) . '</option>';
            }
            $meta .= '<label class="sel"><span>' . e($f['label']) . '</span><select name="' . e($c) . '">' . $o . '</select></label>';
        }
    }
    $meta .= ui_switch('activo', (bool)$r['activo'], 'Visible', 'data-act="toggle"');
    return '<article class="row' . ($media === '' ? ' no-media' : '') . ((int)$r['activo'] ? '' : ' inactive') . '" data-id="' . $id . '">'
         . '<button type="button" class="handle" aria-label="Arrastrar para reordenar" title="Arrastrar para reordenar">⠿</button>'
         . $media
         . '<div class="fields">' . $fields . '<div class="meta">' . $meta . '</div><span class="err" data-err="row"></span></div>'
         . '<div class="side-act"><button type="button" class="btn sm" data-act="save">Guardar</button>'
         . '<div class="actions"><button type="button" class="btn alt sm icon" data-act="up" aria-label="Subir">↑</button><button type="button" class="btn alt sm icon" data-act="down" aria-label="Bajar">↓</button></div>'
         . '<button type="button" class="btn alt danger sm" data-act="del">Eliminar</button></div>'
         . '</article>';
}

function lista_payload(string $key, string $msg = ''): array
{
    $d = lista_def($key);
    $h = '';
    $rows = lista_rows($d);
    foreach ($rows as $i => $r) { $h .= lista_row_html($key, $d, $r, $i); }
    return ['ok' => true, 'message' => $msg, 'lista' => $key, 'html' => $h ?: '<p class="empty">' . e($d['vacio']) . '</p>', 'total' => count($rows), 'max' => $d['max']];
}

/** Tarjeta del panel con su botón «+ Agregar» y la lista. */
function lista_card_html(string $key, string $titulo, string $hint): string
{
    $d = lista_def($key);
    $p = lista_payload($key);
    return '<div class="card lista" data-lista="' . e($key) . '" data-max="' . (int)$d['max'] . '">'
         . '<h2>' . e($titulo) . '</h2><p class="hint">' . $hint . '</p>'
         . '<div class="toolbar"><button type="button" class="btn" data-add' . ($p['total'] >= $d['max'] ? ' disabled' : '') . '>+ ' . e($d['agregar']) . '</button></div>'
         . '<div class="list">' . $p['html'] . '</div></div>';
}

/** Lee y valida los campos enviados. @return array [columna => valor] */
function lista_leer(array $d): array
{
    $out = [];
    $err = [];
    foreach ($d['campos'] as $c => $f) {
        if ($f['t'] === 'select') {
            $v = post_str($c, 30);
            if (!array_key_exists($v, $f['opciones'])) { $err[] = $f['label'] . ': opción no válida.'; }
        } elseif ($f['t'] === 'icono') {
            $v = post_str($c, 30);
            if (!array_key_exists($v, valor_iconos())) { $err[] = 'Ícono no válido.'; }
        } else {
            $v = post_str($c, (int)$f['max'], $f['t'] === 'textarea');
            if (!empty($f['req']) && $v === '') { $err[] = $f['label'] . ' es obligatorio.'; }
        }
        $out[$c] = $v;
    }
    if ($err) { json_fail(implode(' ', $err), 422); }
    return $out;
}

/** Atiende las acciones del panel para las listas permitidas en esta página (solo POST). */
function lista_handle(array $permitidas): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { return; }
    csrf_require();
    $key = post_str('lista', 20);
    if (!in_array($key, $permitidas, true)) { json_fail('Lista no válida.', 400); }
    $d = lista_def($key);
    $t = $d['tabla'];
    $pdo = db();
    $id = post_int('id');

    switch (post_str('action', 20)) {
        case 'crear':
            $n = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            if ($n >= $d['max']) { json_fail('Máximo ' . $d['max'] . ' elementos.', 422); }
            $cols = array_keys($d['nuevo']);
            $sql = "INSERT INTO `$t` (" . implode(', ', array_map(fn($c) => "`$c`", $cols)) . ', orden, activo) VALUES (' . str_repeat('?, ', count($cols)) . '?, 1)';
            $pdo->prepare($sql)->execute([...array_values($d['nuevo']), next_orden($t)]);
            content_rebuild();
            json_out(lista_payload($key, 'Se agregó: edítalo y pulsa «Guardar».'));

        case 'guardar':
            $st = $pdo->prepare('SELECT ' . lista_cols($d) . " FROM `$t` WHERE id = ?");
            $st->execute([$id]);
            $cur = $st->fetch();
            if (!$cur) { json_fail('El elemento ya no existe.', 404); }
            $vals = lista_leer($d);
            $vals['activo'] = post_bool('activo') ? 1 : 0;
            $nueva = null;
            $borrar = null;
            if (isset($d['imagen'])) {
                if (upload_present('imagen')) {
                    try { $nueva = image_save('imagen', $d['imagen']['carpeta'], $d['imagen']['ancho']); }
                    catch (UploadError $e) { json_fail($e->getMessage(), 422); }
                    $vals['imagen'] = $nueva;
                    $borrar = $cur['imagen'] ?: null;
                } elseif (post_bool('quitar_imagen')) {
                    $vals['imagen'] = null;
                    $borrar = $cur['imagen'] ?: null;
                }
            }
            $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($vals)));
            try {
                $pdo->prepare("UPDATE `$t` SET $set WHERE id = ?")->execute([...array_values($vals), $id]);
            } catch (Throwable $e) { image_delete($nueva); throw $e; }
            image_delete($borrar);
            content_rebuild();
            $rows = lista_rows($d);
            foreach ($rows as $i => $r) {
                if ((int)$r['id'] === $id) { json_out(['ok' => true, 'message' => 'Guardado.', 'row' => lista_row_html($key, $d, $r, $i)]); }
            }
            json_fail('El elemento ya no existe.', 404);

        case 'activo':
            $pdo->prepare("UPDATE `$t` SET activo = ? WHERE id = ?")->execute([post_bool('activo') ? 1 : 0, $id]);
            content_rebuild();
            json_out(['ok' => true, 'message' => post_bool('activo') ? 'Visible en el sitio.' : 'Oculto en el sitio.']);

        case 'eliminar':
            $img = null;
            if (isset($d['imagen'])) {
                $st = $pdo->prepare("SELECT imagen FROM `$t` WHERE id = ?");
                $st->execute([$id]);
                $img = $st->fetchColumn() ?: null;
            }
            $pdo->prepare("DELETE FROM `$t` WHERE id = ?")->execute([$id]);
            image_delete($img);
            content_rebuild();
            json_out(lista_payload($key, 'Eliminado.'));

        case 'mover':
            $dir = post_str('dir', 4);
            if (!in_array($dir, ['up', 'down'], true)) { json_fail('Movimiento no válido.', 422); }
            move_in_list($t, array_column(lista_rows($d), 'id'), $id, $dir);
            content_rebuild();
            json_out(lista_payload($key));

        case 'ordenar':
            reorder_subset($t, post_ids('ids'));
            content_rebuild();
            json_out(['ok' => true, 'message' => 'Orden guardado.']);
    }
    json_fail('Acción no válida.', 400);
}

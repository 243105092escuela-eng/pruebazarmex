<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_layout.php';
require __DIR__ . '/../includes/upload.php';
require __DIR__ . '/../includes/content.php';

$user = require_login();

function slides_all(): array
{
    return db()->query('SELECT id, imagen, titulo, subtitulo, enlace, orden, activo FROM slides ORDER BY orden, id')->fetchAll();
}

function slides_activos(): int
{
    return (int)db()->query('SELECT COUNT(*) FROM slides WHERE activo = 1')->fetchColumn();
}

function slide_row_html(array $s, int $i): string
{
    $id = (int)$s['id'];
    ob_start(); ?>
<article class="row<?= (int)$s['activo'] ? '' : ' inactive' ?>" data-id="<?= $id ?>">
  <button type="button" class="handle" aria-label="Arrastrar para reordenar" title="Arrastrar para reordenar">⠿</button>
  <div class="thumb-col">
    <?= ui_thumb($s['imagen'], 'Imagen ' . ($i + 1), 200 + $i * 35) ?>
    <input class="file-in" type="file" name="imagen" accept="image/jpeg,image/png,image/webp" tabindex="-1" aria-label="Elegir imagen">
    <button type="button" class="btn alt sm" data-act="pick">Cambiar imagen</button>
  </div>
  <div class="fields">
    <input name="titulo" maxlength="150" value="<?= e($s['titulo']) ?>" aria-label="Título" placeholder="Título">
    <input name="subtitulo" maxlength="255" value="<?= e($s['subtitulo']) ?>" aria-label="Subtítulo" placeholder="Subtítulo">
    <input name="enlace" maxlength="255" value="<?= e($s['enlace']) ?>" aria-label="Enlace del botón" placeholder="Enlace del botón (ej. #servicios). Vacío = sin botón">
    <div class="meta"><?= ui_switch('activo', (bool)$s['activo'], 'Visible en el sitio', 'data-act="toggle"') ?></div>
    <span class="err" data-err="row"></span>
  </div>
  <div class="side-act">
    <button type="button" class="btn sm" data-act="save">Guardar</button>
    <div class="actions"><button type="button" class="btn alt sm icon" data-act="up" aria-label="Subir">↑</button><button type="button" class="btn alt sm icon" data-act="down" aria-label="Bajar">↓</button></div>
    <button type="button" class="btn alt danger sm" data-act="del">Quitar</button>
  </div>
</article>
<?php
    return (string)ob_get_clean();
}

function slides_list_html(): string
{
    $all = slides_all();
    if (!$all) { return '<p class="empty">No hay diapositivas. Agrega la primera.</p>'; }
    $h = '';
    foreach ($all as $i => $s) { $h .= slide_row_html($s, $i); }
    return $h;
}

function slides_payload(string $msg = ''): array
{
    $t = (int)db()->query('SELECT COUNT(*) FROM slides')->fetchColumn();
    return ['ok' => true, 'message' => $msg, 'html' => slides_list_html(), 'activos' => slides_activos(), 'total' => $t, 'max_total' => MAX_SLIDES_TOTAL];
}

/* ------------------------- acciones (JSON) ------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $pdo = db();
    $act = post_str('action', 20);

    switch ($act) {
        case 'crear':
            $total = (int)$pdo->query('SELECT COUNT(*) FROM slides')->fetchColumn();
            if ($total >= MAX_SLIDES_TOTAL) { json_fail('Llegaste al máximo de ' . MAX_SLIDES_TOTAL . ' diapositivas. Elimina alguna para agregar otra.', 422); }
            $activo = slides_activos() < MAX_SLIDES_ACTIVOS ? 1 : 0;
            $pdo->prepare('INSERT INTO slides (imagen, titulo, subtitulo, enlace, orden, activo) VALUES (NULL, ?, ?, ?, ?, ?)')
                ->execute(['Nuevo título', 'Nuevo subtítulo', '#servicios', next_orden('slides'), $activo]);
            content_rebuild();
            json_out(slides_payload($activo ? 'Diapositiva agregada.' : 'Se agregó desactivada: ya hay ' . MAX_SLIDES_ACTIVOS . ' activas.'));

        case 'guardar':
            $id = post_int('id');
            $st = $pdo->prepare('SELECT id, imagen, activo FROM slides WHERE id = ?');
            $st->execute([$id]);
            $cur = $st->fetch();
            if (!$cur) { json_fail('La diapositiva ya no existe.', 404); }
            $titulo = post_str('titulo', 150);
            $sub    = post_str('subtitulo', 255);
            $enlace = post_str('enlace', 255);
            $activo = post_bool('activo') ? 1 : 0;
            if ($titulo === '')          { json_fail('El título es obligatorio.', 422); }
            if (!valid_link($enlace))    { json_fail('El enlace debe empezar con #, / o http(s)://', 422); }
            if ($activo && !(int)$cur['activo'] && slides_activos() >= MAX_SLIDES_ACTIVOS) {
                json_fail('Solo puede haber ' . MAX_SLIDES_ACTIVOS . ' diapositivas activas. Desactiva otra primero.', 422);
            }
            $nueva = null;
            if (upload_present('imagen')) {
                try { $nueva = image_save('imagen', 'carrusel', 1600); }
                catch (UploadError $e) { json_fail($e->getMessage(), 422); }
            }
            try {
                $pdo->prepare('UPDATE slides SET titulo = ?, subtitulo = ?, enlace = ?, activo = ?, imagen = COALESCE(?, imagen) WHERE id = ?')
                    ->execute([$titulo, $sub, $enlace, $activo, $nueva, $id]);
            } catch (Throwable $t) { image_delete($nueva); throw $t; }
            if ($nueva) { image_delete($cur['imagen']); }
            content_rebuild();
            $all = slides_all();
            $idx = 0; $fresh = null;
            foreach ($all as $i => $s) { if ((int)$s['id'] === $id) { $idx = $i; $fresh = $s; } }
            json_out(['ok' => true, 'message' => 'Diapositiva guardada.', 'row' => slide_row_html($fresh, $idx), 'activos' => slides_activos()]);

        case 'activo':
            $id = post_int('id');
            $activo = post_bool('activo') ? 1 : 0;
            $st = $pdo->prepare('SELECT activo FROM slides WHERE id = ?');
            $st->execute([$id]);
            $cur = $st->fetchColumn();
            if ($cur === false) { json_fail('La diapositiva ya no existe.', 404); }
            if ($activo && !(int)$cur && slides_activos() >= MAX_SLIDES_ACTIVOS) {
                json_fail('Solo puede haber ' . MAX_SLIDES_ACTIVOS . ' diapositivas activas. Desactiva otra primero.', 422, ['revert' => true]);
            }
            $pdo->prepare('UPDATE slides SET activo = ? WHERE id = ?')->execute([$activo, $id]);
            content_rebuild();
            json_out(['ok' => true, 'message' => $activo ? 'Diapositiva visible.' : 'Diapositiva oculta.', 'activos' => slides_activos()]);

        case 'eliminar':
            $id = post_int('id');
            $st = $pdo->prepare('SELECT imagen FROM slides WHERE id = ?');
            $st->execute([$id]);
            $img = $st->fetchColumn();
            if ($img === false) { json_fail('La diapositiva ya no existe.', 404); }
            $pdo->prepare('DELETE FROM slides WHERE id = ?')->execute([$id]);
            image_delete($img ?: null);
            content_rebuild();
            json_out(slides_payload('Diapositiva eliminada.'));

        case 'mover':
            $dir = post_str('dir', 4);
            if (!in_array($dir, ['up', 'down'], true)) { json_fail('Movimiento no válido.', 422); }
            move_in_list('slides', array_column(slides_all(), 'id'), post_int('id'), $dir);
            content_rebuild();
            json_out(slides_payload());

        case 'ordenar':
            reorder_subset('slides', post_ids('ids'));
            content_rebuild();
            json_out(['ok' => true, 'message' => 'Orden guardado.']);
    }
    json_fail('Acción no válida.', 400);
}

/* ------------------------- página ------------------------- */
$total = (int)db()->query('SELECT COUNT(*) FROM slides')->fetchColumn();
admin_head('Carrusel', 'carrusel', $user);
?>
<p class="hint">Hasta <?= MAX_SLIDES_ACTIVOS ?> diapositivas visibles a la vez. Imagen recomendada: horizontal, 1600×600 px (JPG, PNG o WebP, máx. 3 MB). Arrastra el tirador ⠿ o usa las flechas para cambiar el orden.</p>
<div class="toolbar">
  <span class="tag" id="counter" data-max="<?= MAX_SLIDES_ACTIVOS ?>">Visibles: <?= slides_activos() ?> de <?= MAX_SLIDES_ACTIVOS ?></span>
  <button type="button" class="btn" id="add" data-max="<?= MAX_SLIDES_TOTAL ?>"<?= $total >= MAX_SLIDES_TOTAL ? ' disabled' : '' ?>>+ Agregar diapositiva</button>
</div>
<div id="list"><?= slides_list_html() ?></div>
<?php admin_foot();

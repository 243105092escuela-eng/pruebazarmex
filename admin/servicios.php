<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_layout.php';
require __DIR__ . '/../includes/upload.php';
require __DIR__ . '/../includes/content.php';

$user = require_login();

/** Filtros recibidos del navegador (tipo, búsqueda y página). */
function items_filters(array $src): array
{
    $tipo = clean_text($src['ftipo'] ?? '', 10);
    return [
        'tipo' => in_array($tipo, ['Servicio', 'Producto'], true) ? $tipo : '',
        'q'    => clean_text($src['fq'] ?? '', 80),
        'page' => max(1, (int)(is_string($src['page'] ?? null) && ctype_digit($src['page']) ? $src['page'] : 1)),
    ];
}

function items_where(array $f): array
{
    $w = []; $p = [];
    if ($f['tipo'] !== '') { $w[] = 'tipo = ?'; $p[] = $f['tipo']; }
    if ($f['q'] !== '') {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $f['q']) . '%';
        $w[] = '(nombre LIKE ? OR descripcion LIKE ?)'; $p[] = $like; $p[] = $like;
    }
    return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $p];
}

function items_fetch(array $f): array
{
    [$where, $params] = items_where($f);
    $st = db()->prepare("SELECT COUNT(*) FROM items $where");
    $st->execute($params);
    $total = (int)$st->fetchColumn();
    $pages = max(1, (int)ceil($total / PAGINA_ADMIN));
    $page  = min($f['page'], $pages);
    $st = db()->prepare("SELECT id, tipo, nombre, descripcion, precio, imagen, orden, activo FROM items $where ORDER BY orden, id LIMIT ? OFFSET ?");
    foreach ($params as $i => $v) { $st->bindValue($i + 1, $v); }
    $st->bindValue(count($params) + 1, PAGINA_ADMIN, PDO::PARAM_INT);
    $st->bindValue(count($params) + 2, ($page - 1) * PAGINA_ADMIN, PDO::PARAM_INT);
    $st->execute();
    return ['rows' => $st->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
}

function item_row_html(array $s, int $i): string
{
    $id = (int)$s['id'];
    ob_start(); ?>
<article class="row<?= (int)$s['activo'] ? '' : ' inactive' ?>" data-id="<?= $id ?>"
  data-tipo="<?= e($s['tipo']) ?>" data-nombre="<?= e($s['nombre']) ?>" data-desc="<?= e($s['descripcion']) ?>"
  data-precio="<?= e((string)$s['precio']) ?>" data-activo="<?= (int)$s['activo'] ?>" data-img="<?= e($s['imagen'] ? url($s['imagen']) : '') ?>">
  <button type="button" class="handle" aria-label="Arrastrar para reordenar" title="Arrastrar para reordenar">⠿</button>
  <div class="thumb-col"><?= ui_thumb($s['imagen'], $s['nombre'], 30 + $i * 40) ?></div>
  <div class="fields">
    <div class="meta"><span class="tag <?= $s['tipo'] === 'Servicio' ? 'servicio' : '' ?>"><?= e($s['tipo']) ?></span><span class="row-title"><?= e($s['nombre']) ?></span></div>
    <p class="row-sub"><?= e(truncate($s['descripcion'], 140)) ?></p>
    <div class="meta"><?php if ($s['precio'] !== null && $s['precio'] !== ''): ?><strong><?= e($s['precio']) ?></strong><?php endif; ?>
      <?= ui_switch('activo', (bool)$s['activo'], 'Visible en el sitio', 'data-act="toggle"') ?></div>
  </div>
  <div class="side-act">
    <button type="button" class="btn sm" data-act="edit">Editar</button>
    <div class="actions"><button type="button" class="btn alt sm icon" data-act="up" aria-label="Subir">↑</button><button type="button" class="btn alt sm icon" data-act="down" aria-label="Bajar">↓</button></div>
    <button type="button" class="btn alt danger sm" data-act="del">Eliminar</button>
  </div>
</article>
<?php
    return (string)ob_get_clean();
}

function items_payload(array $f, string $msg = ''): array
{
    $r = items_fetch($f);
    $h = '';
    foreach ($r['rows'] as $i => $s) { $h .= item_row_html($s, ($r['page'] - 1) * PAGINA_ADMIN + $i); }
    if ($h === '') { $h = '<p class="empty">No hay elementos' . ($f['tipo'] !== '' || $f['q'] !== '' ? ' con ese filtro' : '. Agrega el primero') . '.</p>'; }
    return ['ok' => true, 'message' => $msg, 'html' => $h, 'pager' => paginate_nav($r['page'], $r['pages']), 'page' => $r['page'], 'total' => $r['total']];
}

/* ------------------------- acciones (JSON) ------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $pdo = db();
    $act = post_str('action', 20);
    $f   = items_filters($_POST);

    switch ($act) {
        case 'listar':
            json_out(items_payload($f));

        case 'guardar':
            $id     = post_int('id');
            $tipo   = post_str('tipo', 10);
            $nombre = post_str('nombre', 150);
            $desc   = post_str('descripcion', 2000, true);
            $precio = post_str('precio', 60);
            $activo = post_bool('activo') ? 1 : 0;
            $err = [];
            if (!in_array($tipo, ['Servicio', 'Producto'], true)) { $err['tipo'] = 'Elige el tipo.'; }
            if ($nombre === '')                                   { $err['nombre'] = 'El nombre es obligatorio.'; }
            if ($desc === '')                                     { $err['descripcion'] = 'La descripción es obligatoria.'; }
            if ($err) { json_fail('Revisa los campos marcados.', 422, ['errors' => $err]); }

            $cur = null;
            if ($id > 0) {
                $st = $pdo->prepare('SELECT id, nombre, imagen FROM items WHERE id = ?');
                $st->execute([$id]);
                $cur = $st->fetch();
                if (!$cur) { json_fail('El elemento ya no existe.', 404); }
            }
            $nueva = null;
            if (upload_present('imagen')) {
                try { $nueva = image_save('imagen', 'servicios', 800); }
                catch (UploadError $e) { json_fail($e->getMessage(), 422, ['errors' => ['imagen' => $e->getMessage()]]); }
            }
            $quitar = !$nueva && post_bool('quitar_imagen');
            try {
                if ($cur) {
                    $slug = $cur['nombre'] === $nombre ? null : unique_slug($nombre, $id);
                    $pdo->prepare('UPDATE items SET tipo = ?, nombre = ?, descripcion = ?, precio = ?, activo = ?,
                                   slug = COALESCE(?, slug), imagen = CASE WHEN ? = 1 THEN NULL ELSE COALESCE(?, imagen) END WHERE id = ?')
                        ->execute([$tipo, $nombre, $desc, $precio !== '' ? $precio : null, $activo, $slug, $quitar ? 1 : 0, $nueva, $id]);
                } else {
                    $pdo->prepare('INSERT INTO items (tipo, nombre, slug, descripcion, precio, imagen, orden, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                        ->execute([$tipo, $nombre, unique_slug($nombre), $desc, $precio !== '' ? $precio : null, $nueva, next_orden('items'), $activo]);
                    $f['page'] = PHP_INT_MAX; // se muestra la última página, donde queda el nuevo
                }
            } catch (Throwable $t) { image_delete($nueva); throw $t; }
            if ($cur && ($nueva || $quitar)) { image_delete($cur['imagen']); }
            content_rebuild();
            json_out(items_payload($f, $cur ? 'Cambios guardados.' : 'Elemento agregado.'));

        case 'activo':
            $id = post_int('id');
            $st = $pdo->prepare('UPDATE items SET activo = ? WHERE id = ?');
            $st->execute([post_bool('activo') ? 1 : 0, $id]);
            content_rebuild();
            json_out(['ok' => true, 'message' => post_bool('activo') ? 'Elemento visible.' : 'Elemento oculto.']);

        case 'eliminar':
            $id = post_int('id');
            $st = $pdo->prepare('SELECT imagen FROM items WHERE id = ?');
            $st->execute([$id]);
            $img = $st->fetchColumn();
            if ($img === false) { json_fail('El elemento ya no existe.', 404); }
            $pdo->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);
            image_delete($img ?: null);
            content_rebuild();
            json_out(items_payload($f, 'Elemento eliminado.'));

        case 'mover':
            $dir = post_str('dir', 4);
            if (!in_array($dir, ['up', 'down'], true)) { json_fail('Movimiento no válido.', 422); }
            [$where, $params] = items_where($f);
            $st = $pdo->prepare("SELECT id FROM items $where ORDER BY orden, id");
            $st->execute($params);
            move_in_list('items', $st->fetchAll(PDO::FETCH_COLUMN), post_int('id'), $dir);
            content_rebuild();
            json_out(items_payload($f));

        case 'ordenar':
            reorder_subset('items', post_ids('ids'));
            content_rebuild();
            json_out(['ok' => true, 'message' => 'Orden guardado.']);
    }
    json_fail('Acción no válida.', 400);
}

/* ------------------------- página ------------------------- */
$init = items_payload(items_filters($_GET));
admin_head('Servicios y productos', 'servicios', $user);
?>
<p class="hint">Cada elemento aparece en Home (agrupado como servicio o producto) y en el menú desplegable del formulario. Arrastra ⠿ o usa las flechas para ordenar.</p>
<div class="toolbar">
  <select id="fTipo" aria-label="Filtrar por tipo"><option value="">Todos</option><option>Servicio</option><option>Producto</option></select>
  <div class="grow"><input id="fQ" type="search" placeholder="Buscar por nombre o descripción…" aria-label="Buscar" maxlength="80"></div>
  <button type="button" class="btn" id="add">+ Agregar</button>
</div>
<div id="list"><?= $init['html'] ?></div>
<div id="pager"><?= $init['pager'] ?></div>

<dialog id="itemDlg" class="dlg" aria-labelledby="itemTitle" data-ph="<?= e(placeholder_uri('Sin imagen', 210)) ?>">
  <form id="itemForm" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="0">
    <div class="dlg-h"><h2 id="itemTitle">Elemento</h2><button type="button" class="btn alt sm" id="itemX" aria-label="Cerrar">✕</button></div>
    <div class="dlg-b grid2">
      <div class="f"><label for="i_tipo">Tipo</label><select id="i_tipo" name="tipo"><option>Servicio</option><option>Producto</option></select><span class="err" data-err="tipo"></span></div>
      <div class="f"><label for="i_precio">Precio (opcional)</label><input id="i_precio" name="precio" maxlength="60" placeholder="Ej. $1,500 MXN o Desde $200"><span class="err" data-err="precio"></span></div>
      <div class="f full"><label for="i_nombre">Nombre</label><input id="i_nombre" name="nombre" maxlength="150"><span class="err" data-err="nombre"></span></div>
      <div class="f full"><label for="i_desc">Descripción</label><textarea id="i_desc" name="descripcion" rows="4" maxlength="2000"></textarea><span class="err" data-err="descripcion"></span></div>
      <div class="f full"><span class="lbl">Imagen (800 px de ancho, JPG/PNG/WebP, máx. 3 MB)</span>
        <div class="img-pick">
          <img class="thumb" id="i_prev" src="" alt="Vista previa" width="200" height="110">
          <div class="f"><input class="file-in" type="file" id="i_file" name="imagen" accept="image/jpeg,image/png,image/webp" tabindex="-1" aria-label="Elegir imagen">
            <button type="button" class="btn alt sm" id="i_pick">Elegir imagen</button>
            <label class="switch"><input type="checkbox" name="quitar_imagen" value="1" id="i_quitar"><span class="track" aria-hidden="true"></span><span>Quitar imagen actual</span></label>
          </div>
        </div>
        <span class="err" data-err="imagen"></span></div>
      <div class="f full"><?= ui_switch('activo', true, 'Visible en el sitio') ?></div>
    </div>
    <div class="dlg-f"><button type="button" class="btn alt" id="itemNo">Cancelar</button><button type="submit" class="btn" id="itemGo">Guardar</button></div>
  </form>
</dialog>
<?php admin_foot();

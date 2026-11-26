<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_layout.php';
require __DIR__ . '/../includes/upload.php';
require __DIR__ . '/../includes/content.php';

$user = require_login();

function nos_imagenes(): array
{
    $out = ['superior' => null, 'inferior' => null];
    foreach (db()->query('SELECT posicion, imagen FROM nosotros_imagenes')->fetchAll() as $r) { $out[$r['posicion']] = $r['imagen']; }
    return $out;
}

function imagen_block_html(string $pos, ?string $img): string
{
    $label = $pos === 'superior' ? 'Imagen superior' : 'Imagen inferior';
    ob_start(); ?>
<div class="img-block" data-pos="<?= e($pos) ?>">
  <span class="lbl"><?= e($label) ?></span>
  <div class="img-pick">
    <?= ui_thumb($img, $label, $pos === 'superior' ? 160 : 260, 'thumb') ?>
    <div class="f">
      <input class="file-in" type="file" name="imagen" accept="image/jpeg,image/png,image/webp" tabindex="-1" aria-label="Elegir <?= e(strtolower($label)) ?>">
      <div class="actions">
        <button type="button" class="btn alt sm" data-act="pick">Elegir imagen</button>
        <button type="button" class="btn sm" data-act="save-img">Guardar</button>
      </div>
      <label class="switch"><input type="checkbox" name="quitar" value="1"><span class="track" aria-hidden="true"></span><span>Quitar (usar marcador)</span></label>
    </div>
  </div>
</div>
<?php
    return (string)ob_get_clean();
}

function valores_all(): array
{
    return db()->query('SELECT id, titulo, texto, icono, orden, activo FROM valores ORDER BY orden, id')->fetchAll();
}

function valor_row_html(array $v): string
{
    ob_start(); ?>
<article class="row<?= (int)$v['activo'] ? '' : ' inactive' ?>" data-id="<?= (int)$v['id'] ?>">
  <button type="button" class="handle" aria-label="Arrastrar para reordenar" title="Arrastrar para reordenar">⠿</button>
  <div class="val-prev" data-prev><?= valor_icono_svg($v['icono']) ?></div>
  <div class="fields">
    <input name="titulo" maxlength="80" value="<?= e($v['titulo']) ?>" aria-label="Título del valor" placeholder="Título">
    <input name="texto" maxlength="255" value="<?= e($v['texto']) ?>" aria-label="Texto del valor" placeholder="Texto breve">
    <div class="meta">
      <select name="icono" aria-label="Ícono">
<?php foreach (valor_iconos() as $k => [$lab]): ?>
        <option value="<?= e($k) ?>" data-svg="<?= e(valor_icono_svg($k)) ?>"<?= $k === $v['icono'] ? ' selected' : '' ?>><?= e($lab) ?></option>
<?php endforeach; ?>
      </select>
      <?= ui_switch('activo', (bool)$v['activo'], 'Visible', 'data-act="toggle"') ?>
    </div>
  </div>
  <div class="side-act">
    <button type="button" class="btn sm" data-act="save">Guardar</button>
    <div class="actions"><button type="button" class="btn alt sm icon" data-act="up" aria-label="Subir">↑</button><button type="button" class="btn alt sm icon" data-act="down" aria-label="Bajar">↓</button></div>
    <button type="button" class="btn alt danger sm" data-act="del">Eliminar</button>
  </div>
</article>
<?php
    return (string)ob_get_clean();
}

function valores_payload(string $msg = ''): array
{
    $h = '';
    foreach (valores_all() as $v) { $h .= valor_row_html($v); }
    $n = (int)db()->query('SELECT COUNT(*) FROM valores')->fetchColumn();
    return ['ok' => true, 'message' => $msg, 'html' => $h ?: '<p class="empty">No hay valores.</p>', 'total' => $n];
}

/* ------------------------- acciones (JSON) ------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $pdo = db();
    $act = post_str('action', 20);

    switch ($act) {
        case 'imagen':
            $pos = post_str('posicion', 10);
            if (!in_array($pos, ['superior', 'inferior'], true)) { json_fail('Posición no válida.', 422); }
            $actual = nos_imagenes()[$pos];
            $nueva = null;
            if (upload_present('imagen')) {
                try { $nueva = image_save('imagen', 'nosotros', 1600); }
                catch (UploadError $e) { json_fail($e->getMessage(), 422); }
            } elseif (!post_bool('quitar')) {
                json_fail('Elige una imagen o marca «Quitar».', 422);
            }
            try {
                $pdo->prepare('INSERT INTO nosotros_imagenes (posicion, imagen) VALUES (?, ?) ON DUPLICATE KEY UPDATE imagen = VALUES(imagen)')->execute([$pos, $nueva]);
            } catch (Throwable $t) { image_delete($nueva); throw $t; }
            image_delete($actual);
            content_rebuild();
            json_out(['ok' => true, 'message' => $nueva ? 'Imagen actualizada.' : 'Imagen quitada.', 'html' => imagen_block_html($pos, $nueva)]);

        case 'historia':
            $p1 = post_str('historia_p1', 2000, true);
            $p2 = post_str('historia_p2', 2000, true);
            ajustes_save(['historia_p1' => $p1, 'historia_p2' => $p2]);
            content_rebuild();
            json_out(['ok' => true, 'message' => 'Historia guardada.']);

        case 'mision':
            ajustes_save([
                'eslogan' => post_str('eslogan', 200),
                'mision'  => post_str('mision', 1000, true),
                'vision'  => post_str('vision', 1000, true),
            ]);
            content_rebuild();
            json_out(['ok' => true, 'message' => 'Textos guardados.']);

        case 'crear':
            $n = (int)$pdo->query('SELECT COUNT(*) FROM valores')->fetchColumn();
            if ($n >= MAX_VALORES) { json_fail('Máximo ' . MAX_VALORES . ' valores.', 422); }
            $pdo->prepare('INSERT INTO valores (titulo, texto, icono, orden, activo) VALUES (?, ?, ?, ?, 1)')
                ->execute(['Nuevo valor', 'Describe este valor.', 'check', next_orden('valores')]);
            content_rebuild();
            json_out(valores_payload('Valor agregado.'));

        case 'guardar':
            $id = post_int('id');
            $titulo = post_str('titulo', 80);
            $texto  = post_str('texto', 255);
            $icono  = post_str('icono', 30);
            $activo = post_bool('activo') ? 1 : 0;
            if ($titulo === '') { json_fail('El título es obligatorio.', 422); }
            if (!array_key_exists($icono, valor_iconos())) { json_fail('Ícono no válido.', 422); }
            $st = $pdo->prepare('UPDATE valores SET titulo = ?, texto = ?, icono = ?, activo = ? WHERE id = ?');
            $st->execute([$titulo, $texto, $icono, $activo, $id]);
            content_rebuild();
            $r = $pdo->prepare('SELECT id, titulo, texto, icono, orden, activo FROM valores WHERE id = ?');
            $r->execute([$id]);
            $v = $r->fetch();
            if (!$v) { json_fail('El valor ya no existe.', 404); }
            json_out(['ok' => true, 'message' => 'Valor guardado.', 'row' => valor_row_html($v)]);

        case 'activo':
            $pdo->prepare('UPDATE valores SET activo = ? WHERE id = ?')->execute([post_bool('activo') ? 1 : 0, post_int('id')]);
            content_rebuild();
            json_out(['ok' => true, 'message' => post_bool('activo') ? 'Valor visible.' : 'Valor oculto.']);

        case 'eliminar':
            $pdo->prepare('DELETE FROM valores WHERE id = ?')->execute([post_int('id')]);
            content_rebuild();
            json_out(valores_payload('Valor eliminado.'));

        case 'mover':
            $dir = post_str('dir', 4);
            if (!in_array($dir, ['up', 'down'], true)) { json_fail('Movimiento no válido.', 422); }
            move_in_list('valores', array_column(valores_all(), 'id'), post_int('id'), $dir);
            content_rebuild();
            json_out(valores_payload());

        case 'ordenar':
            reorder_subset('valores', post_ids('ids'));
            content_rebuild();
            json_out(['ok' => true, 'message' => 'Orden guardado.']);
    }
    json_fail('Acción no válida.', 400);
}

/* ------------------------- página ------------------------- */
$imgs = nos_imagenes();
$aj   = ajustes_all();
$vp   = valores_payload();
admin_head('Nosotros', 'nosotros', $user);
?>
<div class="card">
  <h2>Imágenes</h2>
  <p class="hint">Dos imágenes anchas (1600 px de ancho, JPG/PNG/WebP, máx. 3 MB): una arriba y otra abajo de la sección Nosotros.</p>
  <div id="imgs"><?= imagen_block_html('superior', $imgs['superior']) ?><hr class="sep"><?= imagen_block_html('inferior', $imgs['inferior']) ?></div>
</div>

<div class="card">
  <h2>Eslogan, misión y visión</h2>
  <p class="hint">El eslogan aparece bajo el título «Nosotros», en el pie de página y al compartir el enlace. Si dejas vacía la misión o la visión, esa tarjeta no se muestra.</p>
  <form data-action="mision" novalidate>
    <?= csrf_field() ?>
    <div class="f"><label for="m_esl">Eslogan</label><input id="m_esl" name="eslogan" maxlength="200" value="<?= e($aj['eslogan']) ?>"></div>
    <div class="f"><label for="m_mis">Misión</label><textarea id="m_mis" name="mision" rows="3" maxlength="1000"><?= e($aj['mision']) ?></textarea></div>
    <div class="f"><label for="m_vis">Visión</label><textarea id="m_vis" name="vision" rows="3" maxlength="1000"><?= e($aj['vision']) ?></textarea></div>
    <div class="actions"><button type="submit" class="btn">Guardar textos</button></div>
  </form>
</div>

<div class="card">
  <h2>Valores</h2>
  <p class="hint">Se muestran como tarjetas con ícono. Puedes editar, ocultar, reordenar (⠿ o flechas), agregar (hasta <?= MAX_VALORES ?>) o eliminar.</p>
  <div class="toolbar"><button type="button" class="btn" id="add" data-max="<?= MAX_VALORES ?>"<?= $vp['total'] >= MAX_VALORES ? ' disabled' : '' ?>>+ Agregar valor</button></div>
  <div id="list"><?= $vp['html'] ?></div>
</div>

<div class="card">
  <h2>Historia</h2>
  <form id="histForm" novalidate>
    <?= csrf_field() ?>
    <div class="f"><label for="p1">Primer párrafo</label><textarea id="p1" name="historia_p1" rows="4" maxlength="2000"><?= e($aj['historia_p1']) ?></textarea></div>
    <div class="f"><label for="p2">Segundo párrafo</label><textarea id="p2" name="historia_p2" rows="4" maxlength="2000"><?= e($aj['historia_p2']) ?></textarea></div>
    <div class="actions"><button type="submit" class="btn" id="histGo">Guardar historia</button></div>
  </form>
</div>
<?php admin_foot();

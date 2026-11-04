<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_layout.php';
require __DIR__ . '/../includes/upload.php';
require __DIR__ . '/../includes/content.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    if (post_str('action', 20) !== 'guardar') { json_fail('Acción no válida.', 400); }

    $a = ajustes_all();
    $nombre = post_str('nombre_empresa', 100);
    $email  = post_str('email', 150);
    $tel    = post_str('telefono', 40);
    $err = [];
    if ($nombre === '')                                          { $err['nombre_empresa'] = 'El nombre de la empresa es obligatorio.'; }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $err['email'] = 'Escribe un correo válido.'; }
    if ($tel !== '' && !preg_match('/^[+\d][\d\s()\-.]{5,}$/', $tel)) { $err['telefono'] = 'Escribe un teléfono válido.'; }
    $acento = '';
    if (post_bool('usar_acento')) {
        $acento = strtolower(post_str('color_acento', 7));
        if (!valid_hex($acento)) { $err['color_acento'] = 'Color no válido.'; }
    }
    $texto = '';
    if ($acento !== '' && post_bool('usar_acento_texto')) {
        $texto = strtolower(post_str('color_acento_texto', 7));
        if (!valid_hex($texto)) { $err['color_acento_texto'] = 'Color no válido.'; }
    }
    if ($err) { json_fail('Revisa los campos marcados.', 422, ['errors' => $err]); }

    $logo = $a['logo'];
    $nuevo = null;
    if (upload_present('logo')) {
        try { $nuevo = image_save('logo', 'ajustes', 400, true); }
        catch (UploadError $e) { json_fail($e->getMessage(), 422, ['errors' => ['logo' => $e->getMessage()]]); }
        $logo = $nuevo;
    } elseif (post_bool('quitar_logo')) {
        $logo = '';
    }
    try {
        ajustes_save([
            'nombre_empresa'     => $nombre,
            'logo'               => $logo,
            'email'              => $email,
            'telefono'           => $tel,
            'texto_home'         => post_str('texto_home', 300, true),
            'texto_servicios'    => post_str('texto_servicios', 300, true),
            'texto_footer'       => post_str('texto_footer', 200),
            'color_acento'       => $acento,
            'color_acento_texto' => $texto,
        ]);
    } catch (Throwable $t) { image_delete($nuevo); throw $t; }
    if ($logo !== $a['logo']) { image_delete($a['logo'] ?: null); }
    content_rebuild();
    json_out(['ok' => true, 'message' => 'Ajustes guardados.', 'logo' => $logo ? url($logo) : '']);
}

$a = ajustes_all();
$ph = placeholder_uri('Sin logo', 210);
admin_head('Ajustes del sitio', 'ajustes', $user);
?>
<form id="setForm" novalidate>
<?= csrf_field() ?>
<div class="card">
  <h2>Empresa</h2>
  <div class="grid2">
    <div class="f full"><label for="s_nombre">Nombre de la empresa</label><input id="s_nombre" name="nombre_empresa" maxlength="100" value="<?= e($a['nombre_empresa']) ?>"><span class="err" data-err="nombre_empresa"></span></div>
    <div class="f full"><span class="lbl">Logo (opcional · máx. 400 px de ancho; PNG con transparencia recomendado)</span>
      <div class="img-pick">
        <img class="thumb contain" id="s_prev" src="<?= e($a['logo'] ? url($a['logo']) : $ph) ?>" data-ph="<?= e($ph) ?>" alt="Vista previa del logo" width="200" height="110">
        <div class="f">
          <input class="file-in" type="file" id="s_file" name="logo" accept="image/jpeg,image/png,image/webp" tabindex="-1" aria-label="Elegir logo">
          <button type="button" class="btn alt sm" id="s_pick">Elegir logo</button>
          <label class="switch"><input type="checkbox" name="quitar_logo" value="1" id="s_quitar"><span class="track" aria-hidden="true"></span><span>Quitar logo actual</span></label>
        </div>
      </div>
      <span class="err" data-err="logo"></span></div>
  </div>
</div>

<div class="card">
  <h2>Contacto</h2>
  <p class="hint">Aparece en el pie de página del sitio.</p>
  <div class="grid2">
    <div class="f"><label for="s_email">Correo de contacto</label><input id="s_email" name="email" type="email" maxlength="150" value="<?= e($a['email']) ?>"><span class="err" data-err="email"></span></div>
    <div class="f"><label for="s_tel">Teléfono</label><input id="s_tel" name="telefono" type="tel" maxlength="40" value="<?= e($a['telefono']) ?>"><span class="err" data-err="telefono"></span></div>
  </div>
</div>

<div class="card">
  <h2>Textos</h2>
  <div class="grid2">
    <div class="f full"><label for="s_home">Texto de introducción en Home</label><textarea id="s_home" name="texto_home" rows="2" maxlength="300"><?= e($a['texto_home']) ?></textarea></div>
    <div class="f full"><label for="s_serv">Texto de la página «Servicios y productos»</label><textarea id="s_serv" name="texto_servicios" rows="2" maxlength="300"><?= e($a['texto_servicios']) ?></textarea></div>
    <div class="f full"><label for="s_foot">Texto del pie de página (después de «© año Empresa.»)</label><input id="s_foot" name="texto_footer" maxlength="200" value="<?= e($a['texto_footer']) ?>"></div>
  </div>
</div>

<div class="card">
  <h2>Colores (opcional)</h2>
  <p class="hint">Si no activas un color, el sitio usa la paleta original.</p>
  <div class="grid2">
    <div class="f"><?= ui_switch('usar_acento', $a['color_acento'] !== '', 'Personalizar color de acento') ?>
      <input type="color" name="color_acento" value="<?= e(valid_hex($a['color_acento']) ? $a['color_acento'] : '#e0861a') ?>" aria-label="Color de acento"><span class="err" data-err="color_acento"></span></div>
    <div class="f"><?= ui_switch('usar_acento_texto', $a['color_acento_texto'] !== '', 'Elegir color del texto sobre el acento') ?>
      <input type="color" name="color_acento_texto" value="<?= e(valid_hex($a['color_acento_texto']) ? $a['color_acento_texto'] : '#ffffff') ?>" aria-label="Color del texto sobre el acento"><span class="hint">Si no lo eliges, se usa blanco u oscuro automáticamente según el contraste.</span></div>
  </div>
</div>

<div class="actions"><button type="submit" class="btn" id="setGo">Guardar ajustes</button></div>
</form>
<?php admin_foot();

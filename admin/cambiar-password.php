<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_layout.php';

$user = require_login(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $actual = is_string($_POST['actual'] ?? null) ? substr($_POST['actual'], 0, 200) : '';
    $nueva  = is_string($_POST['nueva'] ?? null) ? substr($_POST['nueva'], 0, 200) : '';
    $conf   = is_string($_POST['confirmar'] ?? null) ? substr($_POST['confirmar'], 0, 200) : '';

    $st = db()->prepare('SELECT password_hash FROM usuarios WHERE id = ?');
    $st->execute([$user['id']]);
    if (!password_verify($actual, (string)$st->fetchColumn())) {
        json_fail('La contraseña actual no es correcta.', 422, ['errors' => ['actual' => 'Contraseña incorrecta.']]);
    }
    if ($msg = password_policy_error($nueva)) { json_fail($msg, 422, ['errors' => ['nueva' => $msg]]); }
    if ($nueva === $actual)                    { json_fail('La nueva contraseña debe ser distinta de la actual.', 422, ['errors' => ['nueva' => 'Debe ser distinta.']]); }
    if ($nueva !== $conf)                      { json_fail('La confirmación no coincide.', 422, ['errors' => ['confirmar' => 'No coincide.']]); }

    db()->prepare('UPDATE usuarios SET password_hash = ?, debe_cambiar_password = 0 WHERE id = ?')
        ->execute([password_hash($nueva, PASSWORD_DEFAULT), $user['id']]);
    session_regenerate_id(true);
    json_out(['ok' => true, 'message' => 'Contraseña actualizada.', 'redirect' => url('admin/index.php')]);
}

admin_head('Cambiar contraseña', 'password', $user);
?>
<div class="card narrow">
<?php if ($user['debe_cambiar_password']): ?>
  <p class="hint"><strong>Por seguridad, debes cambiar tu contraseña temporal antes de continuar.</strong></p>
<?php endif; ?>
  <form id="pwForm" class="grid2" novalidate>
    <?= csrf_field() ?>
    <div class="f full"><label for="actual">Contraseña actual</label><input id="actual" name="actual" type="password" autocomplete="current-password"><span class="err" data-err="actual"></span></div>
    <div class="f full"><label for="nueva">Nueva contraseña</label><input id="nueva" name="nueva" type="password" autocomplete="new-password"><span class="err" data-err="nueva"></span><span class="hint">Mínimo 10 caracteres, con al menos una letra y un número.</span></div>
    <div class="f full"><label for="confirmar">Repite la nueva contraseña</label><input id="confirmar" name="confirmar" type="password" autocomplete="new-password"><span class="err" data-err="confirmar"></span></div>
    <div class="f full"><button class="btn" type="submit">Guardar contraseña</button></div>
  </form>
</div>
<?php admin_foot();

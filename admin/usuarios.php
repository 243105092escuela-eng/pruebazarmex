<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_layout.php';

$user = require_admin();

function usr_row_html(array $u, int $meId): string
{
    $self = (int)$u['id'] === $meId;
    ob_start(); ?>
<tr data-id="<?= (int)$u['id'] ?>" data-nombre="<?= e($u['nombre']) ?>" data-email="<?= e($u['email']) ?>" data-rol="<?= e($u['rol']) ?>" data-activo="<?= (int)$u['activo'] ?>" data-self="<?= $self ? 1 : 0 ?>">
  <td><strong><?= e($u['nombre']) ?></strong><?= $self ? ' <span class="tag">tú</span>' : '' ?><br><small class="muted"><?= e($u['email']) ?></small></td>
  <td><?= e($u['rol']) ?></td>
  <td><?= ui_switch('activo', (bool)$u['activo'], 'Activo', 'data-act="toggle"' . ($self ? ' disabled' : '')) ?></td>
  <td><?= $u['ultimo_login'] ? e(fmt_date($u['ultimo_login'])) : '<span class="muted">Nunca</span>' ?><?= (int)$u['debe_cambiar_password'] ? '<br><small class="muted">Debe cambiar contraseña</small>' : '' ?></td>
  <td><div class="acts"><button type="button" class="btn alt sm" data-act="edit">Editar</button><?php if (!$self): ?><button type="button" class="btn alt danger sm" data-act="del">Eliminar</button><?php endif; ?></div></td>
</tr>
<?php
    return (string)ob_get_clean();
}

function usr_payload(int $page, int $meId, string $msg = ''): array
{
    $pdo = db();
    $total = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    $pages = max(1, (int)ceil($total / PAGINA_ADMIN));
    $page  = min(max(1, $page), $pages);
    $st = $pdo->prepare('SELECT id, nombre, email, rol, activo, debe_cambiar_password, ultimo_login FROM usuarios ORDER BY nombre, id LIMIT ? OFFSET ?');
    $st->bindValue(1, PAGINA_ADMIN, PDO::PARAM_INT);
    $st->bindValue(2, ($page - 1) * PAGINA_ADMIN, PDO::PARAM_INT);
    $st->execute();
    $h = '';
    foreach ($st->fetchAll() as $u) { $h .= usr_row_html($u, $meId); }
    return ['ok' => true, 'message' => $msg, 'html' => $h, 'pager' => paginate_nav($page, $pages), 'page' => $page];
}

/** ¿Quedaría al menos un admin activo si se quita a $exceptId? */
function otros_admins_activos(int $exceptId): int
{
    $st = db()->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin' AND activo = 1 AND id <> ?");
    $st->execute([$exceptId]);
    return (int)$st->fetchColumn();
}

/* ------------------------- acciones (JSON) ------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $pdo  = db();
    $act  = post_str('action', 20);
    $page = post_int('page', 1);
    $me   = $user['id'];

    switch ($act) {
        case 'listar':
            json_out(usr_payload($page, $me));

        case 'guardar':
            $id     = post_int('id');
            $nombre = post_str('nombre', 100);
            $email  = mb_strtolower(post_str('email', 150));
            $rol    = post_str('rol', 10);
            $activo = post_bool('activo') ? 1 : 0;
            $pass   = is_string($_POST['password'] ?? null) ? substr($_POST['password'], 0, 200) : '';
            $err = [];
            if ($nombre === '')                               { $err['nombre'] = 'El nombre es obligatorio.'; }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL))   { $err['email'] = 'Escribe un correo válido.'; }
            if (!in_array($rol, ['admin', 'editor'], true))   { $err['rol'] = 'Elige un rol.'; }
            if ($id === 0 || $pass !== '') {
                if ($m = password_policy_error($pass))        { $err['password'] = $m; }
            }
            if ($err) { json_fail('Revisa los campos marcados.', 422, ['errors' => $err]); }

            $dup = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ?');
            $dup->execute([$email, $id]);
            if ($dup->fetch()) { json_fail('Ya existe un usuario con ese correo.', 422, ['errors' => ['email' => 'Correo ya registrado.']]); }

            if ($id === 0) {
                $pdo->prepare('INSERT INTO usuarios (nombre, email, password_hash, rol, activo, debe_cambiar_password) VALUES (?, ?, ?, ?, ?, 1)')
                    ->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT), $rol, $activo]);
                json_out(usr_payload(PHP_INT_MAX, $me, 'Usuario creado. Deberá cambiar su contraseña al entrar.'));
            }
            $st = $pdo->prepare('SELECT id, rol, activo FROM usuarios WHERE id = ?');
            $st->execute([$id]);
            $cur = $st->fetch();
            if (!$cur) { json_fail('El usuario ya no existe.', 404); }
            if ($id === $me) { $rol = $cur['rol']; $activo = 1; $pass = ''; } // sobre sí mismo: no rol/estado/clave aquí
            $quitaAdmin = $cur['rol'] === 'admin' && (int)$cur['activo'] === 1 && ($rol !== 'admin' || !$activo);
            if ($quitaAdmin && otros_admins_activos($id) < 1) { json_fail('Debe quedar al menos un administrador activo.', 422); }
            $sql = 'UPDATE usuarios SET nombre = ?, email = ?, rol = ?, activo = ?' . ($pass !== '' ? ', password_hash = ?, debe_cambiar_password = 1' : '') . ' WHERE id = ?';
            $params = [$nombre, $email, $rol, $activo];
            if ($pass !== '') { $params[] = password_hash($pass, PASSWORD_DEFAULT); }
            $params[] = $id;
            $pdo->prepare($sql)->execute($params);
            json_out(usr_payload($page, $me, 'Usuario actualizado.'));

        case 'activo':
            $id = post_int('id');
            $activo = post_bool('activo') ? 1 : 0;
            if ($id === $me) { json_fail('No puedes desactivar tu propia cuenta.', 422); }
            $st = $pdo->prepare('SELECT rol, activo FROM usuarios WHERE id = ?');
            $st->execute([$id]);
            $cur = $st->fetch();
            if (!$cur) { json_fail('El usuario ya no existe.', 404); }
            if (!$activo && $cur['rol'] === 'admin' && (int)$cur['activo'] === 1 && otros_admins_activos($id) < 1) {
                json_fail('Debe quedar al menos un administrador activo.', 422);
            }
            $pdo->prepare('UPDATE usuarios SET activo = ? WHERE id = ?')->execute([$activo, $id]);
            json_out(['ok' => true, 'message' => $activo ? 'Usuario activado.' : 'Usuario desactivado.']);

        case 'eliminar':
            $id = post_int('id');
            if ($id === $me) { json_fail('No puedes eliminar tu propia cuenta.', 422); }
            $st = $pdo->prepare('SELECT rol, activo FROM usuarios WHERE id = ?');
            $st->execute([$id]);
            $cur = $st->fetch();
            if (!$cur) { json_fail('El usuario ya no existe.', 404); }
            if ($cur['rol'] === 'admin' && (int)$cur['activo'] === 1 && otros_admins_activos($id) < 1) {
                json_fail('Debe quedar al menos un administrador activo.', 422);
            }
            $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
            json_out(usr_payload($page, $me, 'Usuario eliminado.'));
    }
    json_fail('Acción no válida.', 400);
}

/* ------------------------- página ------------------------- */
$init = usr_payload(1, $user['id']);
admin_head('Usuarios', 'usuarios', $user);
?>
<p class="hint">Los <strong>administradores</strong> gestionan todo, incluidos los usuarios. Los <strong>editores</strong> gestionan el contenido y las solicitudes. Un usuario nuevo (o con contraseña restablecida) debe cambiarla en su primer ingreso.</p>
<div class="toolbar"><button type="button" class="btn" id="add">+ Agregar usuario</button></div>
<div class="tbl-wrap"><table class="tbl">
  <thead><tr><th>Usuario</th><th>Rol</th><th>Estado</th><th>Último ingreso</th><th><span class="sr">Acciones</span></th></tr></thead>
  <tbody id="rows"><?= $init['html'] ?></tbody>
</table></div>
<div id="pager"><?= $init['pager'] ?></div>

<dialog id="usrDlg" class="dlg" aria-labelledby="usrTitle">
  <form id="usrForm" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="0">
    <div class="dlg-h"><h2 id="usrTitle">Usuario</h2><button type="button" class="btn alt sm" id="usrX" aria-label="Cerrar">✕</button></div>
    <div class="dlg-b grid2">
      <div class="f"><label for="u_nombre">Nombre</label><input id="u_nombre" name="nombre" maxlength="100"><span class="err" data-err="nombre"></span></div>
      <div class="f"><label for="u_email">Correo</label><input id="u_email" name="email" type="email" maxlength="150" autocomplete="off"><span class="err" data-err="email"></span></div>
      <div class="f"><label for="u_rol">Rol</label><select id="u_rol" name="rol"><option value="editor">Editor</option><option value="admin">Administrador</option></select><span class="err" data-err="rol"></span></div>
      <div class="f"><label for="u_pass" id="u_passLbl">Contraseña</label><input id="u_pass" name="password" type="password" maxlength="200" autocomplete="new-password"><span class="err" data-err="password"></span></div>
      <div class="f full"><span class="hint" id="u_hint">Mínimo 10 caracteres, con letras y números.</span><?= ui_switch('activo', true, 'Cuenta activa') ?></div>
    </div>
    <div class="dlg-f"><button type="button" class="btn alt" id="usrNo">Cancelar</button><button type="submit" class="btn" id="usrGo">Guardar</button></div>
  </form>
</dialog>
<?php admin_foot();

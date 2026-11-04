<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/csrf.php';

session_boot();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $email = mb_strtolower(post_str('email', 150));
    $pass  = is_string($_POST['password'] ?? null) ? substr($_POST['password'], 0, 200) : '';
    if ($email === '' || $pass === '') { json_fail('Escribe tu correo y tu contraseña.', 422); }
    $r = login_attempt($email, $pass);
    if (!$r['ok']) { json_fail($r['error'], $r['status'] ?? 401); }
    json_out(['ok' => true, 'redirect' => url($r['user']['debe_cambiar_password'] ? 'admin/cambiar-password.php' : 'admin/index.php')]);
}

if (auth_user()) { redirect('admin/index.php'); }
send_security_headers(true);
?>
<!DOCTYPE html>
<html lang="es" data-base="<?= e(base_path()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title>Iniciar sesión · Panel</title>
<link rel="icon" href="data:,">
<link rel="stylesheet" href="<?= e(asset('css/variables.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<script defer src="<?= e(asset('admin/js/admin.js')) ?>"></script>
</head>
<body class="adm auth" data-page="login">
<main class="auth-card">
  <h1>Panel de administración</h1>
  <p class="auth-note">Inicia sesión con tu correo y contraseña.</p>
  <form id="loginForm" novalidate>
    <?= csrf_field() ?>
    <div class="auth-msg" id="msg" role="alert"></div>
    <div class="f"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" autocomplete="username" required autofocus></div>
    <div class="f"><label for="password">Contraseña</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
    <button class="btn" type="submit" id="go">Entrar</button>
  </form>
</main>
</body>
</html>

<?php
/**
 * Sesión segura, login con bloqueo por IP, roles y cambio obligatorio de contraseña.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/** Hash falso para igualar tiempos cuando el correo no existe. */
const DUMMY_HASH = '$2y$11$l7DrderNz1JkmFJlK.JTmuA/ef40dprM0gbxws0.hVeJxXCmqzGQ6';

function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) { return; }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string)SESSION_IDLE_SEG);
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_path() . '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Usuario autenticado (revalidado en BD en cada petición) o null. */
function auth_user(): ?array
{
    static $cache = false;
    if ($cache !== false) { return $cache; }
    session_boot();
    $uid = $_SESSION['uid'] ?? null;
    if (!$uid) { return $cache = null; }
    if (time() - (int)($_SESSION['last'] ?? 0) > SESSION_IDLE_SEG) {
        logout_clear();
        return $cache = null;
    }
    $st = db()->prepare('SELECT id, nombre, email, rol, activo, debe_cambiar_password FROM usuarios WHERE id = ? LIMIT 1');
    $st->execute([(int)$uid]);
    $u = $st->fetch();
    if (!$u || !(int)$u['activo']) {
        logout_clear();
        return $cache = null;
    }
    $_SESSION['last'] = time();
    $u['id'] = (int)$u['id'];
    $u['debe_cambiar_password'] = (int)$u['debe_cambiar_password'];
    return $cache = $u;
}

/**
 * Exige sesión. En peticiones AJAX responde JSON 401; en páginas redirige al login.
 * Si el usuario debe cambiar su contraseña, solo deja entrar a esa página.
 */
function require_login(bool $allowPasswordChange = false): array
{
    $u = auth_user();
    if (!$u) {
        if (is_ajax() || $_SERVER['REQUEST_METHOD'] === 'POST') {
            json_fail('Tu sesión expiró. Inicia sesión de nuevo.', 401, ['login' => true]);
        }
        redirect('admin/login.php');
    }
    if ($u['debe_cambiar_password'] && !$allowPasswordChange) {
        if (is_ajax() || $_SERVER['REQUEST_METHOD'] === 'POST') {
            json_fail('Debes cambiar tu contraseña para continuar.', 403, ['redirect' => url('admin/cambiar-password.php')]);
        }
        redirect('admin/cambiar-password.php');
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if ($u['rol'] !== 'admin') {
        if (is_ajax() || $_SERVER['REQUEST_METHOD'] === 'POST') { json_fail('No tienes permiso para esta acción.', 403); }
        redirect('admin/index.php');
    }
    return $u;
}

function logout_clear(): void
{
    session_boot();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'domain' => $p['domain'],
            'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) { session_destroy(); }
}

/* ---------------- Límite de intentos por IP ---------------- */

/** Segundos que faltan para poder reintentar (0 = no bloqueado). */
function login_locked_seconds(string $ip): int
{
    $st = db()->prepare('SELECT COUNT(*) AS n, UNIX_TIMESTAMP(MAX(creado_en)) AS ult FROM intentos_login WHERE ip = ? AND creado_en > DATE_SUB(NOW(), INTERVAL ' . LOGIN_VENTANA_SEG . ' SECOND)');
    $st->execute([$ip]);
    $r = $st->fetch();
    if ((int)$r['n'] < LOGIN_MAX_INTENTOS) { return 0; }
    return max(1, (int)$r['ult'] + LOGIN_VENTANA_SEG - time());
}

function login_register_fail(string $ip, string $email): void
{
    $pdo = db();
    $pdo->prepare('INSERT INTO intentos_login (ip, email) VALUES (?, ?)')->execute([$ip, mb_substr($email, 0, 150)]);
    if (random_int(1, 20) === 1) { // limpieza ocasional
        $pdo->exec('DELETE FROM intentos_login WHERE creado_en < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }
}

function login_clear_fails(string $ip): void
{
    db()->prepare('DELETE FROM intentos_login WHERE ip = ?')->execute([$ip]);
}

function login_remaining(string $ip): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM intentos_login WHERE ip = ? AND creado_en > DATE_SUB(NOW(), INTERVAL ' . LOGIN_VENTANA_SEG . ' SECOND)');
    $st->execute([$ip]);
    return max(0, LOGIN_MAX_INTENTOS - (int)$st->fetchColumn());
}

/** @return array{ok:bool,error?:string,status?:int,user?:array} */
function login_attempt(string $email, string $password): array
{
    $ip = client_ip();
    $wait = login_locked_seconds($ip);
    if ($wait > 0) {
        $min = (int)ceil($wait / 60);
        return ['ok' => false, 'status' => 429,
            'error' => "Demasiados intentos fallidos. Intenta de nuevo en {$min} minuto" . ($min === 1 ? '' : 's') . '.'];
    }
    $st = db()->prepare('SELECT id, password_hash, activo, debe_cambiar_password FROM usuarios WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $u = $st->fetch();
    $valid = password_verify($password, $u ? $u['password_hash'] : DUMMY_HASH);
    if (!$u || !$valid || !(int)$u['activo']) {
        login_register_fail($ip, $email);
        $left = login_remaining($ip);
        $msg = 'Correo o contraseña incorrectos.';
        if ($left > 0 && $left <= 2) { $msg .= " Te quedan {$left} intento" . ($left === 1 ? '' : 's') . '.'; }
        if ($left === 0) { $msg = 'Demasiados intentos fallidos. Intenta de nuevo en ' . (int)(LOGIN_VENTANA_SEG / 60) . ' minutos.'; }
        return ['ok' => false, 'status' => $left === 0 ? 429 : 401, 'error' => $msg];
    }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    login_clear_fails($ip);
    session_boot();
    session_regenerate_id(true);
    $_SESSION['uid']  = (int)$u['id'];
    $_SESSION['last'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    db()->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')->execute([$u['id']]);
    return ['ok' => true, 'user' => ['id' => (int)$u['id'], 'debe_cambiar_password' => (int)$u['debe_cambiar_password']]];
}

/** @return string|null mensaje de error o null si cumple la política */
function password_policy_error(string $p): ?string
{
    if (mb_strlen($p) < 10)            { return 'La contraseña debe tener al menos 10 caracteres.'; }
    if (mb_strlen($p) > 200)           { return 'La contraseña es demasiado larga.'; }
    if (!preg_match('/\p{L}/u', $p))   { return 'La contraseña debe incluir al menos una letra.'; }
    if (!preg_match('/\d/', $p))       { return 'La contraseña debe incluir al menos un número.'; }
    return null;
}

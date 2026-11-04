<?php
/** Token CSRF por sesión. Se envía en el campo "csrf" o en la cabecera X-CSRF-Token. */
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function csrf_token(): string
{
    session_boot();
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    session_boot();
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && $sent !== '' && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

/** Corta la petición si el token no es válido. */
function csrf_require(): void
{
    if (!csrf_valid()) {
        json_fail('La sesión de seguridad venció. Recarga la página e inténtalo de nuevo.', 419, ['csrf' => true]);
    }
}

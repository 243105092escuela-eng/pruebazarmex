<?php
declare(strict_types=1);

if (defined('APP_BOOT')) { return; }
define('APP_BOOT', true);

define('ROOT_PATH',   dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('CACHE_PATH',  ROOT_PATH . '/cache');
define('LOG_PATH',    ROOT_PATH . '/logs');

/* ---- Reglas de negocio (ajustables) ---- */
const MAX_UPLOAD_BYTES       = 3 * 1024 * 1024; // 3 MB
const MAX_SLIDES_ACTIVOS   = 5;
const MAX_SLIDES_TOTAL     = 10;
const MAX_VALORES          = 12;
const LOGIN_MAX_INTENTOS   = 5;                 // fallos permitidos...
const LOGIN_VENTANA_SEG    = 900;               // ...en 15 min; luego se bloquea ese tiempo
const SESSION_IDLE_SEG     = 7200;              // 2 h de inactividad
const SOLICITUD_MAX_HORA   = 5;                 // envíos por IP y hora
const PAGINA_ADMIN         = 10;
const SESSION_NAME         = 'ZARMEXSID';

/* ---- Archivo de entorno ---- */
function env_values(): array
{
    static $vars = null;
    if ($vars !== null) { return $vars; }
    $vars = [];
    $candidates = [];
    $custom = getenv('ZARMEX_ENV_FILE');
    if ($custom) { $candidates[] = $custom; }
    $candidates[] = dirname(ROOT_PATH) . '/.zarmex.env'; // fuera de la carpeta pública (recomendado)
    $candidates[] = ROOT_PATH . '/.env';                  // dentro, bloqueado por .htaccess
    foreach ($candidates as $file) {
        if (!is_file($file) || !is_readable($file)) { continue; }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) { continue; }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
                $v = substr($v, 1, -1);
            }
            if (!array_key_exists($k, $vars)) { $vars[$k] = $v; } // el primer archivo manda
        }
    }
    return $vars;
}

function env(string $key, ?string $default = null): ?string
{
    $v = env_values();
    if (array_key_exists($key, $v)) { return $v[$key]; }
    $g = getenv($key);
    return $g !== false ? $g : $default;
}

define('APP_DEBUG', env('APP_ENV', 'production') === 'development');

/* ---- Zona horaria (PHP y MySQL usan la misma) ---- */
$tz = env('APP_TZ', 'America/Mexico_City');
if (!@date_default_timezone_set($tz)) { date_default_timezone_set('UTC'); }

/* ---- Errores: en producción nunca se muestran; se escriben en logs/ ---- */
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
if (is_dir(LOG_PATH) && is_writable(LOG_PATH)) {
    ini_set('error_log', LOG_PATH . '/php-error.log');
}
error_reporting(E_ALL);

require_once __DIR__ . '/helpers.php';

set_exception_handler(static function (Throwable $e): void {
    error_log('[' . get_class($e) . '] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    $wantsJson = is_ajax()
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/');
    if ($wantsJson) {
        if (!headers_sent()) { header('Content-Type: application/json; charset=utf-8'); }
        echo json_encode(['ok' => false, 'error' => APP_DEBUG ? $e->getMessage() : 'Error interno del servidor.'], JSON_UNESCAPED_UNICODE);
    } else {
        if (!headers_sent()) { header('Content-Type: text/html; charset=utf-8'); }
        echo '<!DOCTYPE html><html lang="es"><meta charset="utf-8"><title>Error</title>'
           . '<body><h1>Servicio no disponible</h1>'
           . '<p>Ocurrió un error. Intenta de nuevo en unos minutos.</p>'
           . (APP_DEBUG ? '<pre>' . htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') . '</pre>' : '')
           . '</body></html>';
    }
    exit(1);
});

/**
 * Cabeceras de seguridad. La CSP es estricta porque el sitio no usa
 * <style>, style="" ni <script> en línea.
 */
function send_security_headers(bool $admin = false): void
{
    if (headers_sent()) { return; }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    // blob: solo en el panel (vista previa de imágenes antes de subirlas)
    $img = $admin ? "'self' data: blob:" : "'self' data:";
    header("Content-Security-Policy: default-src 'self'; img-src {$img}; style-src 'self'; script-src 'self'; "
         . "connect-src 'self'; font-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
    if (is_https()) {
        header('Strict-Transport-Security: max-age=15552000');
    }
    if ($admin) {
        header('Cache-Control: no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow');
    }
}

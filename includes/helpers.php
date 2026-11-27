<?php
/** Utilidades generales: escape, URLs, JSON, entradas, ordenamiento y paginación. */
declare(strict_types=1);

/* ------------------------------------------------------------------ */
/*  Salida segura y URLs                                              */
/* ------------------------------------------------------------------ */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') { return true; }
    if (env('TRUST_PROXY', '0') === '1' && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') { return true; }
    return ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (env('TRUST_PROXY', '0') === '1' && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $first = trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) { $ip = $first; }
    }
    return substr((string)$ip, 0, 45);
}

/**
 * Privacidad: la IP de las solicitudes solo sirve para el límite de envíos por hora,
 * así que se borra pasados IP_RETENCION_DIAS. Los intentos de login fallidos (IP y correo)
 * solo importan 15 minutos: se eliminan pasado un día. Se llama al recibir una solicitud
 * y al abrir el inicio del panel, así no hace falta una tarea programada en el hosting.
 */
function purgar_ips_viejas(PDO $pdo): void
{
    try {
        $pdo->exec("UPDATE solicitudes SET ip = '' WHERE ip <> '' AND creado_en < DATE_SUB(NOW(), INTERVAL " . (int)IP_RETENCION_DIAS . ' DAY)');
        $pdo->exec('DELETE FROM intentos_login WHERE creado_en < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    } catch (Throwable $t) {
        error_log('purgar_ips_viejas: ' . $t->getMessage()); // no debe impedir guardar la solicitud
    }
}

function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

/** Carpeta web donde vive el sitio ('' si está en la raíz, '/miempresa' si está en una subcarpeta). */
function base_path(): string
{
    static $base = null;
    if ($base !== null) { return $base; }

    $forced = env('APP_BASE', '');
    if ($forced !== null && $forced !== '') {
        return $base = '/' . trim($forced, '/');
    }
    $root = rtrim(str_replace('\\', '/', (string)realpath(ROOT_PATH)), '/');
    $file = str_replace('\\', '/', (string)realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $name = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($file !== '' && str_starts_with(strtolower($file), strtolower($root))) {
        $rel = substr($file, strlen($root)); // p. ej. /admin/carrusel.php
        if ($rel !== '' && str_ends_with($name, $rel)) {
            return $base = rtrim(substr($name, 0, -strlen($rel)), '/');
        }
    }
    $doc = rtrim(str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    if ($doc !== '' && str_starts_with(strtolower($root), strtolower($doc))) {
        return $base = rtrim(substr($root, strlen($doc)), '/');
    }
    return $base = '';
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

/** URL de un estático con ?v=fecha-de-modificación (permite caché larga sin quedarse viejo). */
function asset(string $path): string
{
    $f = ROOT_PATH . '/' . ltrim($path, '/');
    return url($path) . '?v=' . (is_file($f) ? filemtime($f) : 0);
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/* ------------------------------------------------------------------ */
/*  JSON                                                              */
/* ------------------------------------------------------------------ */
function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function json_fail(string $message, int $code = 400, array $extra = []): never
{
    json_out(['ok' => false, 'error' => $message] + $extra, $code);
}

/* ------------------------------------------------------------------ */
/*  Entradas (siempre se leen con estas funciones)                    */
/* ------------------------------------------------------------------ */
function clean_text(mixed $v, int $max = 255, bool $multiline = false): string
{
    if (!is_string($v)) { return ''; }
    $v = str_replace("\r\n", "\n", $v);
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '';
    if (!$multiline) { $v = preg_replace('/\s+/u', ' ', $v) ?? ''; }
    return mb_substr(trim($v), 0, $max, 'UTF-8');
}

function post_str(string $key, int $max = 255, bool $multiline = false): string
{
    return clean_text($_POST[$key] ?? '', $max, $multiline);
}

function post_int(string $key, int $default = 0): int
{
    $v = $_POST[$key] ?? null;
    return is_string($v) && preg_match('/^-?\d{1,10}$/', $v) ? (int)$v : $default;
}

function post_bool(string $key): bool
{
    $v = $_POST[$key] ?? '';
    return $v === '1' || $v === 'on' || $v === 'true';
}

/** @return int[] */
function post_ids(string $key): array
{
    $v = $_POST[$key] ?? [];
    if (!is_array($v)) { return []; }
    $out = [];
    foreach ($v as $x) {
        if (is_string($x) && ctype_digit($x)) { $out[] = (int)$x; }
    }
    return array_values(array_unique($out));
}

function valid_link(string $s): bool
{
    if ($s === '') { return true; }
    return (bool)preg_match('~^(#[A-Za-z0-9_\-]*|/[^\s]*|https?://[^\s]+)$~', $s) && !preg_match('~^//~', $s);
}

function valid_hex(string $s): bool
{
    return (bool)preg_match('/^#[0-9a-fA-F]{6}$/', $s);
}

function slugify(string $s): string
{
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = strtr($s, [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'ñ' => 'n', 'ç' => 'c',
    ]);
    $s = trim((string)preg_replace('/[^a-z0-9]+/', '-', $s), '-');
    return $s === '' ? 'item' : substr($s, 0, 140);
}

function unique_slug(string $name, int $ignoreId = 0): string
{
    $base = slugify($name);
    $slug = $base;
    $st = db()->prepare('SELECT 1 FROM items WHERE slug = ? AND id <> ? LIMIT 1');
    for ($i = 2; $i < 500; $i++) {
        $st->execute([$slug, $ignoreId]);
        if (!$st->fetchColumn()) { return $slug; }
        $slug = $base . '-' . $i;
    }
    return $base . '-' . bin2hex(random_bytes(3));
}

function fmt_date(?string $d): string
{
    if (!$d) { return ''; }
    $t = strtotime($d);
    return $t ? date('d/m/Y H:i', $t) : '';
}

function truncate(string $s, int $n): string
{
    return mb_strlen($s, 'UTF-8') > $n ? rtrim(mb_substr($s, 0, $n - 1, 'UTF-8')) . '…' : $s;
}

/* ------------------------------------------------------------------ */
/*  Orden de filas (slides, items, valores)                           */
/* ------------------------------------------------------------------ */
const TABLAS_ORDENABLES = ['slides', 'items', 'valores', 'cifras', 'puntos', 'testimonios', 'preguntas', 'secciones'];

function next_orden(string $table): int
{
    if (!in_array($table, TABLAS_ORDENABLES, true)) { throw new InvalidArgumentException('tabla'); }
    return (int)db()->query("SELECT COALESCE(MAX(orden), 0) + 1 FROM `$table`")->fetchColumn();
}

/** @param int[] $ordered ids de toda la tabla en su nuevo orden */
function renumber(string $table, array $ordered): void
{
    if (!in_array($table, TABLAS_ORDENABLES, true)) { throw new InvalidArgumentException('tabla'); }
    $pdo = db();
    $own = !$pdo->inTransaction();
    if ($own) { $pdo->beginTransaction(); }
    $st = $pdo->prepare("UPDATE `$table` SET orden = ? WHERE id = ?");
    foreach (array_values($ordered) as $i => $id) { $st->execute([$i + 1, $id]); }
    if ($own) { $pdo->commit(); }
}

/**
 * Reordena un subconjunto de filas (por ejemplo, la página visible o un filtro)
 * sin alterar la posición del resto: los ids ocupan los mismos "huecos".
 * @param int[] $ids ids en el orden deseado
 */
function reorder_subset(string $table, array $ids): void
{
    if (!in_array($table, TABLAS_ORDENABLES, true)) { throw new InvalidArgumentException('tabla'); }
    $ids = array_map('intval', $ids);
    if (count($ids) < 2) { return; }
    $all = array_map('intval', db()->query("SELECT id FROM `$table` ORDER BY orden, id")->fetchAll(PDO::FETCH_COLUMN));
    $set = array_flip($ids);
    $slots = [];
    foreach ($all as $pos => $id) { if (isset($set[$id])) { $slots[] = $pos; } }
    if (count($slots) !== count($ids)) { json_fail('Los elementos a ordenar no son válidos.', 422); }
    foreach ($slots as $k => $pos) { $all[$pos] = $ids[$k]; }
    renumber($table, $all);
}

/**
 * Mueve una fila arriba/abajo respecto a su vecina dentro de $visibleIds
 * (la lista ya filtrada y ordenada, de toda la tabla, no solo de la página).
 * @param int[] $visibleIds
 */
function move_in_list(string $table, array $visibleIds, int $id, string $dir): void
{
    $ids = array_map('intval', $visibleIds);
    $i = array_search($id, $ids, true);
    if ($i === false) { json_fail('Elemento no encontrado.', 404); }
    $j = $dir === 'up' ? $i - 1 : $i + 1;
    if (!isset($ids[$j])) { return; }
    [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
    reorder_subset($table, $ids);
}

/* ------------------------------------------------------------------ */
/*  Paginación                                                        */
/* ------------------------------------------------------------------ */
function paginate_nav(int $page, int $pages): string
{
    if ($pages <= 1) { return ''; }
    $h = '<nav class="pager" aria-label="Paginación">';
    $h .= '<button type="button" class="btn alt sm" data-page="' . max(1, $page - 1) . '"' . ($page <= 1 ? ' disabled' : '') . '>‹ Anterior</button>';
    $h .= '<span class="pager-info">Página ' . $page . ' de ' . $pages . '</span>';
    $h .= '<button type="button" class="btn alt sm" data-page="' . min($pages, $page + 1) . '"' . ($page >= $pages ? ' disabled' : '') . '>Siguiente ›</button>';
    return $h . '</nav>';
}

<?php
/**
 * GET  /api/solicitud.php  -> { csrf: "token" }   (se pide al abrir el formulario; así las
 *                              visitas que no usan el formulario no generan sesión)
 * POST /api/solicitud.php  -> guarda la solicitud (CSRF + honeypot + límite por IP + validación)
 */
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/csrf.php';

send_security_headers();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_out(['ok' => true, 'csrf' => csrf_token()]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    json_fail('Método no permitido.', 405);
}

csrf_require();

// Honeypot: un humano no ve este campo. Si viene lleno, se responde "éxito" y no se guarda nada.
if (post_str('sitio_web', 200) !== '') {
    json_out(['ok' => true, 'message' => '¡Solicitud enviada! Te contactaremos pronto.']);
}

$pdo = db();
$ip  = client_ip();

// Límite de envíos por IP
$st = $pdo->prepare('SELECT COUNT(*) FROM solicitudes WHERE ip = ? AND creado_en > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
$st->execute([$ip]);
if ((int)$st->fetchColumn() >= SOLICITUD_MAX_HORA) {
    json_fail('Has enviado varias solicitudes seguidas. Inténtalo de nuevo en un rato.', 429);
}

// Validación en servidor (mismas reglas que el cliente + las de seguridad)
$d = [
    'nombre'      => post_str('nombre', 120),
    'email'       => post_str('email', 150),
    'tel'         => post_str('tel', 30),
    'empresa'     => post_str('empresa', 120),
    'servicio'    => post_int('servicio'),
    'plazo'       => post_str('plazo', 30),
    'cantidad'    => post_str('cantidad', 9),
    'descripcion' => post_str('descripcion', 3000, true),
];
$err = [];
if (mb_strlen($d['nombre']) < 3)                                        { $err['nombre'] = 'Escribe tu nombre completo.'; }
if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL))                     { $err['email']  = 'Escribe un correo válido.'; }
if (!preg_match('/^[+\d][\d\s()\-]{6,}$/', $d['tel']))                   { $err['tel']    = 'Escribe un teléfono con al menos 7 dígitos.'; }
if (mb_strlen($d['descripcion']) < 10)                                   { $err['desc']   = 'Cuéntanos un poco más (mínimo 10 caracteres).'; }
if (!post_bool('terms'))                                                 { $err['terms']  = 'Debes aceptar los términos.'; }

$plazos = ['Sin prisa', 'Este mes', 'Esta semana', 'Urgente'];
if ($d['plazo'] === '') { $d['plazo'] = 'Sin prisa'; }
if (!in_array($d['plazo'], $plazos, true))                               { $err['plazo']  = 'Elige un plazo válido.'; }

$cantidad = null;
if ($d['cantidad'] !== '') {
    if (!ctype_digit($d['cantidad']) || (int)$d['cantidad'] < 1 || (int)$d['cantidad'] > 1000000) {
        $err['cant'] = 'Escribe una cantidad válida (1 o más).';
    } else {
        $cantidad = (int)$d['cantidad'];
    }
}

$item = false;
if ($d['servicio'] > 0) {
    $st = $pdo->prepare('SELECT id, nombre FROM items WHERE id = ? AND activo = 1 LIMIT 1');
    $st->execute([$d['servicio']]);
    $item = $st->fetch();
}
if (!$item)                                                              { $err['servicio'] = 'Elige un servicio.'; }

if ($err) {
    json_fail('Revisa los campos marcados.', 422, ['errors' => $err]);
}

purgar_ips_viejas($pdo);

$pdo->prepare(
    'INSERT INTO solicitudes (nombre, email, telefono, empresa, item_id, item_nombre, plazo, cantidad, descripcion, ip)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
)->execute([
    $d['nombre'], $d['email'], $d['tel'], $d['empresa'] !== '' ? $d['empresa'] : null,
    (int)$item['id'], $item['nombre'], $d['plazo'], $cantidad, $d['descripcion'], $ip,
]);

json_out(['ok' => true, 'message' => '¡Solicitud enviada! Te contactaremos pronto.']);

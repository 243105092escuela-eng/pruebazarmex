<?php
/**
 * Crea (o restablece) un administrador desde la línea de comandos.
 * Útil en producción: en lugar de usar el usuario de ejemplo del seed.
 *
 *   php database/crear_admin.php correo@dominio.com "Nombre Apellido"
 *
 * Genera una contraseña temporal aleatoria, la muestra UNA vez y obliga a cambiarla
 * en el primer ingreso. No se puede ejecutar desde el navegador.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo desde la línea de comandos.');
}

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';

$email  = mb_strtolower(trim($argv[1] ?? ''));
$nombre = trim($argv[2] ?? 'Administrador');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php database/crear_admin.php correo@dominio.com \"Nombre\"\n");
    exit(1);
}

$alfabeto = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$pass = '';
for ($i = 0; $i < 14; $i++) { $pass .= $alfabeto[random_int(0, strlen($alfabeto) - 1)]; }
$pass .= random_int(10, 99); // garantiza dígitos

$pdo = db();
$st = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
$st->execute([$email]);
$hash = password_hash($pass, PASSWORD_DEFAULT);
if ($id = $st->fetchColumn()) {
    $pdo->prepare("UPDATE usuarios SET password_hash = ?, rol = 'admin', activo = 1, debe_cambiar_password = 1 WHERE id = ?")->execute([$hash, $id]);
    echo "Usuario existente restablecido.\n";
} else {
    $pdo->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol, activo, debe_cambiar_password) VALUES (?, ?, ?, 'admin', 1, 1)")->execute([$nombre, $email, $hash]);
    echo "Administrador creado.\n";
}
echo "Correo:               {$email}\n";
echo "Contraseña temporal:  {$pass}\n";
echo "Deberá cambiarla al iniciar sesión. Guárdala ahora: no se vuelve a mostrar.\n";

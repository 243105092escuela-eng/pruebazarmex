<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_layout.php';

$user = require_login();
$pdo  = db();

$n = $pdo->query(
    "SELECT
       (SELECT COUNT(*) FROM items WHERE tipo = 'Producto') AS productos,
       (SELECT COUNT(*) FROM items WHERE tipo = 'Servicio') AS servicios,
       (SELECT COUNT(*) FROM slides WHERE activo = 1) AS slides,
       (SELECT COUNT(*) FROM solicitudes WHERE estado = 'nueva') AS nuevas,
       (SELECT COUNT(*) FROM solicitudes) AS total"
)->fetch();
$recientes = $pdo->query('SELECT id, nombre, item_nombre, estado, creado_en FROM solicitudes ORDER BY creado_en DESC, id DESC LIMIT 5')->fetchAll();

$writable = static fn(string $d): bool => is_dir($d) && is_writable($d);
$checks = [
    ['PHP ' . PHP_VERSION, version_compare(PHP_VERSION, '8.0.0', '>=') ? 'ok' : 'bad'],
    ['Extensión GD (procesa imágenes)', extension_loaded('gd') ? 'ok' : 'bad'],
    ['Versión WebP disponible', function_exists('imagewebp') ? 'ok' : 'warn'],
    ['Carpeta uploads/ con permiso de escritura', $writable(UPLOAD_PATH . '/carrusel') && $writable(UPLOAD_PATH . '/servicios') && $writable(UPLOAD_PATH . '/nosotros') && $writable(UPLOAD_PATH . '/ajustes') ? 'ok' : 'bad'],
    ['Carpeta cache/ con permiso de escritura', $writable(CACHE_PATH) ? 'ok' : 'warn'],
    ['Carpeta logs/ con permiso de escritura', $writable(LOG_PATH) ? 'ok' : 'warn'],
    ['Errores ocultos al público (APP_ENV=production)', APP_DEBUG ? 'warn' : 'ok'],
    ['Conexión segura (HTTPS)', is_https() ? 'ok' : 'warn'],
];

admin_head('Inicio', 'inicio', $user);
?>
<div class="stats">
  <a class="stat" href="<?= e(url('admin/servicios.php')) ?>"><b><?= (int)$n['productos'] ?></b><span>Productos</span></a>
  <a class="stat" href="<?= e(url('admin/servicios.php')) ?>"><b><?= (int)$n['servicios'] ?></b><span>Servicios</span></a>
  <a class="stat<?= (int)$n['nuevas'] > 0 ? ' hot' : '' ?>" href="<?= e(url('admin/solicitudes.php')) ?>"><b><?= (int)$n['nuevas'] ?></b><span>Solicitudes nuevas</span></a>
  <a class="stat" href="<?= e(url('admin/carrusel.php')) ?>"><b><?= (int)$n['slides'] ?>/<?= MAX_SLIDES_ACTIVOS ?></b><span>Diapositivas activas</span></a>
</div>

<div class="card">
  <h2>Últimas solicitudes</h2>
<?php if (!$recientes): ?>
  <p class="empty">Aún no has recibido solicitudes.</p>
<?php else: ?>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Fecha</th><th>Nombre</th><th>Servicio o producto</th><th>Estado</th></tr></thead>
    <tbody>
<?php foreach ($recientes as $r): ?>
      <tr><td><?= e(fmt_date($r['creado_en'])) ?></td><td><?= e($r['nombre']) ?></td><td><?= e((string)$r['item_nombre']) ?></td><td><span class="pill <?= e($r['estado']) ?>"><?= e($r['estado']) ?></span></td></tr>
<?php endforeach; ?>
    </tbody>
  </table></div>
  <p><a href="<?= e(url('admin/solicitudes.php')) ?>">Ver las <?= (int)$n['total'] ?> solicitudes →</a></p>
<?php endif; ?>
</div>

<div class="card">
  <h2>Estado del sistema</h2>
  <ul class="checks">
<?php foreach ($checks as [$label, $st]): ?>
    <li class="<?= $st === 'ok' ? '' : e($st) ?>"><?= e($label) ?></li>
<?php endforeach; ?>
  </ul>
</div>
<?php admin_foot();

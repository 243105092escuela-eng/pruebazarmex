<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_layout.php';

require __DIR__ . '/../includes/upload.php';
require __DIR__ . '/../includes/dashboard.php';

$user = require_login();
$pdo  = db();
purgar_ips_viejas($pdo);

$n = $pdo->query(
    "SELECT
       (SELECT COUNT(*) FROM items WHERE tipo = 'Producto') AS productos,
       (SELECT COUNT(*) FROM items WHERE tipo = 'Servicio') AS servicios,
       (SELECT COUNT(*) FROM slides WHERE activo = 1) AS slides,
       (SELECT COUNT(*) FROM solicitudes WHERE estado = 'nueva') AS nuevas,
       (SELECT COUNT(*) FROM solicitudes) AS total"
)->fetch();
$dash = dash_datos($pdo);
[$compTxt, $compCls] = dash_comparacion($dash['esteMes'], $dash['mesAnt']);
$recientes = $pdo->query('SELECT id, nombre, item_nombre, estado, creado_en FROM solicitudes ORDER BY creado_en DESC, id DESC LIMIT 5')->fetchAll();

$writable = static fn(string $d): bool => is_dir($d) && is_writable($d);
$checks = [
    ['PHP ' . PHP_VERSION, version_compare(PHP_VERSION, '8.0.0', '>=') ? 'ok' : 'bad'],
    ['Extensión GD (procesa imágenes)', extension_loaded('gd') ? 'ok' : 'bad'],
    ['Versión WebP disponible', function_exists('imagewebp') ? 'ok' : 'warn'],
    ['Carpeta uploads/ con permiso de escritura', count(array_filter(IMG_CARPETAS, fn($c) => !$writable(UPLOAD_PATH . '/' . $c))) === 0 ? 'ok' : 'bad'],
    ['Carpeta cache/ con permiso de escritura', $writable(CACHE_PATH) ? 'ok' : 'warn'],
    ['Carpeta logs/ con permiso de escritura', $writable(LOG_PATH) ? 'ok' : 'warn'],
    ['Errores ocultos al público (APP_ENV=production)', APP_DEBUG ? 'warn' : 'ok'],
    ['Conexión segura (HTTPS)', is_https() ? 'ok' : 'warn'],
];

/*
 * El estado del sistema es información técnica para quien instala el sitio, no para el dueño.
 * Solo aparece si algo grave falla (con un aviso sencillo) o al abrir admin/?estado.
 */
$hayFalla = in_array('bad', array_column($checks, 1), true);
$verEstado = isset($_GET['estado']);

admin_head('Inicio', 'inicio', $user);
?>
<?php if ($hayFalla && !$verEstado): ?>
<div class="card alerta">
  <h2>Hay un problema técnico en el servidor</h2>
  <p>Algunas funciones (por ejemplo, subir imágenes) pueden no funcionar. Avisa a quien administra tu sitio web.</p>
  <p><a href="<?= e(url('admin/?estado')) ?>">Ver detalles técnicos</a></p>
</div>
<?php endif; ?>

<div class="stats">
  <a class="stat" href="<?= e(url('admin/solicitudes.php')) ?>"><b><?= $dash['esteMes'] ?></b><span>Solicitudes este mes</span><small class="trend <?= e($compCls) ?>"><?= e($compTxt) ?></small></a>
  <a class="stat" href="<?= e(url('admin/solicitudes.php')) ?>"><b><?= $dash['estaSemana'] ?></b><span>Solicitudes esta semana</span></a>
  <a class="stat<?= $dash['pendientes'] > 0 ? ' hot' : '' ?>" href="<?= e(url('admin/solicitudes.php')) ?>"><b><?= $dash['pendientes'] ?></b><span>Pendientes por atender</span><small class="trend neutral">Nuevas y vistas</small></a>
  <a class="stat" href="<?= e(url('admin/solicitudes.php')) ?>"><b><?= $dash['total'] ?></b><span>Solicitudes en total</span></a>
</div>

<div class="card dash">
  <div class="dash-h">
    <h2>Solicitudes recibidas</h2>
    <div class="seg" role="group" aria-label="Periodo">
      <button type="button" class="on" data-periodo="semanas" aria-pressed="true">Por semana</button><button type="button" data-periodo="meses" aria-pressed="false">Por mes</button>
    </div>
  </div>
  <div data-chart="semanas"><?= dash_barras($dash['semanas'], 'Solicitudes por semana, últimas 12 semanas') ?><p class="hint">Últimas 12 semanas. La barra más oscura es la semana actual.</p></div>
  <div data-chart="meses" hidden><?= dash_barras($dash['meses'], 'Solicitudes por mes, últimos 12 meses') ?><p class="hint">Últimos 12 meses. La barra más oscura es el mes actual.</p></div>
</div>

<div class="dash-2">
  <div class="card">
    <h2>Lo más solicitado</h2>
    <p class="hint">Servicios y productos con más solicitudes en los últimos 12 meses.</p>
    <?= dash_ranking($dash['top'], 'Aún no hay solicitudes en los últimos 12 meses.') ?>
  </div>
  <div class="card">
    <h2>Estado de las solicitudes</h2>
    <p class="hint">Cómo van todas las solicitudes recibidas. Cámbialas de estado en «Solicitudes recibidas».</p>
    <?= dash_ranking(array_map(fn($k, $l) => ['label' => $l, 'n' => $dash['estados'][$k], 'cls' => 'st-' . $k], array_keys(dash_estados()), dash_estados()), 'Aún no hay solicitudes.') ?>
  </div>
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

<?php if ($verEstado): ?>
<div class="card">
  <h2>Estado del sistema</h2>
  <ul class="checks">
<?php foreach ($checks as [$label, $st]): ?>
    <li class="<?= $st === 'ok' ? '' : e($st) ?>"><?= e($label) ?></li>
<?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<?php admin_foot();

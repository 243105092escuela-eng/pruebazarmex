<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_layout.php';

$user = require_login();

const ESTADOS = ['nueva', 'vista', 'atendida', 'archivada'];

function sol_filters(array $src): array
{
    $e = clean_text($src['festado'] ?? '', 12);
    return [
        'estado' => in_array($e, ESTADOS, true) ? $e : '',
        'q'      => clean_text($src['fq'] ?? '', 80),
        'page'   => max(1, (int)(is_string($src['page'] ?? null) && ctype_digit($src['page']) ? $src['page'] : 1)),
    ];
}

function sol_where(array $f): array
{
    $w = []; $p = [];
    if ($f['estado'] !== '') { $w[] = 'estado = ?'; $p[] = $f['estado']; }
    if ($f['q'] !== '') {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $f['q']) . '%';
        $cols = ['nombre', 'email', 'telefono', 'empresa', 'item_nombre', 'descripcion'];
        $w[] = '(' . implode(' OR ', array_map(static fn($c) => "$c LIKE ?", $cols)) . ')';
        foreach ($cols as $_) { $p[] = $like; }
    }
    return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $p];
}

function sol_row_html(array $r): string
{
    ob_start(); ?>
<tr data-id="<?= (int)$r['id'] ?>"<?= $r['estado'] === 'nueva' ? ' class="is-new"' : '' ?>>
  <td><?= e(fmt_date($r['creado_en'])) ?></td>
  <td><strong><?= e($r['nombre']) ?></strong><br><small class="muted"><?= e($r['email']) ?> · <?= e($r['telefono']) ?></small></td>
  <td><?= e((string)$r['item_nombre']) ?></td>
  <td><?= e($r['plazo']) ?></td>
  <td><select data-act="estado" aria-label="Estado de <?= e($r['nombre']) ?>">
<?php foreach (ESTADOS as $s): ?>
    <option value="<?= $s ?>"<?= $s === $r['estado'] ? ' selected' : '' ?>><?= ucfirst($s) ?></option>
<?php endforeach; ?>
  </select></td>
  <td><div class="acts"><button type="button" class="btn alt sm" data-act="ver">Ver</button><button type="button" class="btn alt danger sm" data-act="del">Eliminar</button></div></td>
</tr>
<?php
    return (string)ob_get_clean();
}

function sol_payload(array $f, string $msg = ''): array
{
    $pdo = db();
    [$where, $params] = sol_where($f);
    $st = $pdo->prepare("SELECT COUNT(*) FROM solicitudes $where");
    $st->execute($params);
    $total = (int)$st->fetchColumn();
    $pages = max(1, (int)ceil($total / PAGINA_ADMIN));
    $page  = min($f['page'], $pages);
    $st = $pdo->prepare("SELECT id, nombre, email, telefono, item_nombre, plazo, estado, creado_en FROM solicitudes $where ORDER BY creado_en DESC, id DESC LIMIT ? OFFSET ?");
    foreach ($params as $i => $v) { $st->bindValue($i + 1, $v); }
    $st->bindValue(count($params) + 1, PAGINA_ADMIN, PDO::PARAM_INT);
    $st->bindValue(count($params) + 2, ($page - 1) * PAGINA_ADMIN, PDO::PARAM_INT);
    $st->execute();
    $h = '';
    foreach ($st->fetchAll() as $r) { $h .= sol_row_html($r); }
    if ($h === '') { $h = '<tr><td colspan="6" class="empty">No hay solicitudes' . ($f['estado'] !== '' || $f['q'] !== '' ? ' con ese filtro' : ' todavía') . '.</td></tr>'; }
    return ['ok' => true, 'message' => $msg, 'html' => $h, 'pager' => paginate_nav($page, $pages), 'page' => $page, 'total' => $total, 'nuevas' => solicitudes_nuevas()];
}

/** Evita la inyección de fórmulas al abrir el CSV en Excel/Sheets. */
function csv_safe(mixed $v): string
{
    $s = (string)$v;
    return $s !== '' && strpbrk($s[0], "=+-@\t\r") !== false ? "'" . $s : $s;
}

/* ------------------------- exportar CSV (GET) ------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['export'] ?? '') === 'csv') {
    $f = sol_filters($_GET);
    [$where, $params] = sol_where($f);
    $st = db()->prepare("SELECT id, creado_en, nombre, email, telefono, empresa, item_nombre, plazo, cantidad, descripcion, estado, ip FROM solicitudes $where ORDER BY creado_en DESC, id DESC");
    $st->execute($params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="solicitudes-' . date('Ymd-His') . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel respete los acentos
    fputcsv($out, ['ID', 'Fecha', 'Nombre', 'Correo', 'Teléfono', 'Empresa', 'Servicio o producto', 'Plazo', 'Cantidad', 'Descripción', 'Estado', 'IP'], ',', '"', '\\');
    while ($r = $st->fetch()) {
        fputcsv($out, array_map('csv_safe', [$r['id'], $r['creado_en'], $r['nombre'], $r['email'], $r['telefono'], $r['empresa'], $r['item_nombre'], $r['plazo'], $r['cantidad'], $r['descripcion'], $r['estado'], $r['ip']]), ',', '"', '\\');
    }
    fclose($out);
    exit;
}

/* ------------------------- acciones (JSON) ------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $pdo = db();
    $act = post_str('action', 20);
    $f   = sol_filters($_POST);

    switch ($act) {
        case 'listar':
            json_out(sol_payload($f));

        case 'ver':
            $id = post_int('id');
            $st = $pdo->prepare('SELECT id, nombre, email, telefono, empresa, item_nombre, plazo, cantidad, descripcion, estado, ip, creado_en FROM solicitudes WHERE id = ?');
            $st->execute([$id]);
            $r = $st->fetch();
            if (!$r) { json_fail('La solicitud ya no existe.', 404); }
            if ($r['estado'] === 'nueva') {
                $pdo->prepare("UPDATE solicitudes SET estado = 'vista' WHERE id = ? AND estado = 'nueva'")->execute([$id]);
                $r['estado'] = 'vista';
            }
            $rows = [
                'Fecha' => fmt_date($r['creado_en']), 'Nombre' => $r['nombre'], 'Correo' => null, 'Teléfono' => null,
                'Empresa' => (string)$r['empresa'], 'Servicio o producto' => (string)$r['item_nombre'], 'Plazo' => $r['plazo'],
                'Cantidad' => $r['cantidad'] !== null ? (string)$r['cantidad'] : '', 'Estado' => ucfirst($r['estado']), 'IP' => $r['ip'],
            ];
            $h = '<dl class="dl">';
            foreach ($rows as $k => $v) {
                if ($k === 'Correo')        { $v = '<a href="mailto:' . e($r['email']) . '">' . e($r['email']) . '</a>'; }
                elseif ($k === 'Teléfono')  { $v = '<a href="tel:' . e(preg_replace('/[^\d+]/', '', $r['telefono'])) . '">' . e($r['telefono']) . '</a>'; }
                else                        { $v = e($v); }
                $h .= '<dt>' . e($k) . '</dt><dd>' . ($v !== '' ? $v : '—') . '</dd>';
            }
            $h .= '<dt>Descripción</dt><dd class="pre">' . e($r['descripcion']) . '</dd></dl>';
            json_out(['ok' => true, 'html' => $h, 'title' => $r['nombre'], 'estado' => $r['estado'], 'nuevas' => solicitudes_nuevas()]);

        case 'estado':
            $estado = post_str('estado', 12);
            if (!in_array($estado, ESTADOS, true)) { json_fail('Estado no válido.', 422); }
            $st = $pdo->prepare('UPDATE solicitudes SET estado = ? WHERE id = ?');
            $st->execute([$estado, post_int('id')]);
            json_out(['ok' => true, 'message' => 'Estado: ' . $estado . '.', 'nuevas' => solicitudes_nuevas()]);

        case 'eliminar':
            $pdo->prepare('DELETE FROM solicitudes WHERE id = ?')->execute([post_int('id')]);
            json_out(sol_payload($f, 'Solicitud eliminada.'));
    }
    json_fail('Acción no válida.', 400);
}

/* ------------------------- página ------------------------- */
$init = sol_payload(sol_filters($_GET));
admin_head('Solicitudes recibidas', 'solicitudes', $user);
?>
<div class="toolbar">
  <select id="fEstado" aria-label="Filtrar por estado"><option value="">Todos los estados</option><?php foreach (ESTADOS as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?></select>
  <div class="grow"><input id="fQ" type="search" placeholder="Buscar por nombre, correo, teléfono, empresa o mensaje…" aria-label="Buscar" maxlength="80"></div>
  <a class="btn alt" id="exp" href="<?= e(url('admin/solicitudes.php') . '?export=csv') ?>" data-base="<?= e(url('admin/solicitudes.php') . '?export=csv') ?>">Exportar CSV</a>
</div>
<div class="tbl-wrap"><table class="tbl">
  <thead><tr><th>Fecha</th><th>Contacto</th><th>Servicio o producto</th><th>Plazo</th><th>Estado</th><th><span class="sr">Acciones</span></th></tr></thead>
  <tbody id="rows"><?= $init['html'] ?></tbody>
</table></div>
<div id="pager"><?= $init['pager'] ?></div>

<dialog id="viewDlg" class="dlg" aria-labelledby="viewTitle">
  <div class="dlg-h"><h2 id="viewTitle">Solicitud</h2><button type="button" class="btn alt sm" id="viewX" aria-label="Cerrar">✕</button></div>
  <div class="dlg-b" id="viewBody"></div>
  <div class="dlg-f"><button type="button" class="btn" id="viewOk">Cerrar</button></div>
</dialog>
<?php admin_foot();

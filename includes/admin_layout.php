<?php
/** Estructura común de las páginas del panel: cabecera, menú lateral y pie. */
declare(strict_types=1);

require_once __DIR__ . '/csrf.php';

function admin_menu(): array
{
    // clave, etiqueta, archivo, trazos del ícono, solo-admin
    return [
        ['inicio',      'Inicio',                'admin/index.php',       '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>', false],
        ['carrusel',    'Carrusel',              'admin/carrusel.php',    '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-8 8"/>', false],
        ['servicios',   'Servicios y productos', 'admin/servicios.php',   '<path d="M21 8l-9-5-9 5 9 5z"/><path d="M3 8v8l9 5 9-5V8M12 13v8"/>', false],
        ['pagina-inicio', 'Página de inicio',    'admin/pagina-inicio.php', '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 13h3M8 16h8"/>', false],
        ['testimonios', 'Testimonios',           'admin/testimonios.php', '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/><path d="M9 10h6M9 13h4"/>', false],
        ['preguntas',   'Preguntas frecuentes',  'admin/preguntas.php',   '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 2-2.5 2-2.5 4M12 17h.01"/>', false],
        ['nosotros',    'Nosotros',              'admin/nosotros.php',    '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M16 5a3 3 0 0 1 0 6M18 14c2 .8 3 3 3 6"/>', false],
        ['secciones',   'Secciones extra',       'admin/secciones.php',   '<rect x="3" y="3" width="18" height="7" rx="1.5"/><rect x="3" y="14" width="18" height="7" rx="1.5"/>', false],
        ['ajustes',     'Ajustes del sitio',     'admin/ajustes.php',     '<path d="M4 6h10M18 6h2M4 12h2M10 12h10M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="8" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>', false],
        ['solicitudes', 'Solicitudes recibidas', 'admin/solicitudes.php', '<path d="M3 13l3-8h12l3 8v6H3z"/><path d="M3 13h5l1 3h6l1-3h5"/>', false],
        ['usuarios',    'Usuarios',              'admin/usuarios.php',    '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>', true],
    ];
}

function solicitudes_nuevas(): int
{
    return (int)db()->query("SELECT COUNT(*) FROM solicitudes WHERE estado = 'nueva'")->fetchColumn();
}

function admin_head(string $title, string $page, array $user): void
{
    send_security_headers(true);
    $nuevas = solicitudes_nuevas();
    ?>
<!DOCTYPE html>
<html lang="es" data-base="<?= e(base_path()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e($title) ?> · Panel</title>
<link rel="icon" href="data:,">
<link rel="stylesheet" href="<?= e(asset('css/variables.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<script defer src="<?= e(asset('admin/js/admin.js')) ?>"></script>
</head>
<body class="adm" data-page="<?= e($page) ?>">
<a class="skip" href="#main">Saltar al contenido</a>
<header class="topbar">
  <button type="button" id="menuBtn" aria-label="Abrir menú" aria-expanded="false" aria-controls="side">☰</button>
  <strong>Panel de administración</strong>
</header>
<aside id="side" class="side" aria-label="Menú del panel">
  <div class="side-brand"><strong>Panel de administración</strong><small><?= e($user['nombre']) ?> · <?= e($user['rol']) ?></small></div>
  <nav class="side-nav">
<?php foreach (admin_menu() as [$key, $label, $file, $icon, $onlyAdmin]):
        if ($onlyAdmin && $user['rol'] !== 'admin') { continue; } ?>
    <a class="nav-i<?= $key === $page ? ' on' : '' ?>" href="<?= e(url($file)) ?>"<?= $key === $page ? ' aria-current="page"' : '' ?>>
      <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg><span><?= e($label) ?></span>
<?php if ($key === 'solicitudes'): ?>      <span class="badge" id="badgeNuevas"<?= $nuevas > 0 ? '' : ' hidden' ?>><?= $nuevas ?></span>
<?php endif; ?>
    </a>
<?php endforeach; ?>
  </nav>
  <div class="side-foot">
    <a class="nav-i<?= $page === 'password' ? ' on' : '' ?>" href="<?= e(url('admin/cambiar-password.php')) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M16 7l3 3"/></svg><span>Cambiar contraseña</span></a>
    <a class="nav-i" href="<?= e(url('')) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/></svg><span>Ver sitio público</span></a>
    <form method="post" action="<?= e(url('admin/logout.php')) ?>">
      <?= csrf_field() ?>
      <button class="nav-i out" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4H4v16h5M16 8l4 4-4 4M20 12H9"/></svg><span>Cerrar sesión</span></button>
    </form>
  </div>
</aside>
<div class="scrim" id="scrim"></div>
<main id="main" class="main">
<h1 class="page-t"><?= e($title) ?></h1>
<?php
}

function admin_foot(): void
{
    ?>
</main>
<div class="toasts" id="toasts" role="status" aria-live="polite"></div>
<dialog id="confirmDlg" class="dlg dlg-sm" aria-labelledby="confirmTitle">
  <div class="dlg-b">
    <h2 id="confirmTitle">¿Confirmas?</h2>
    <p id="confirmMsg"></p>
  </div>
  <div class="dlg-f"><button type="button" class="btn alt" id="confirmNo">Cancelar</button><button type="button" class="btn danger" id="confirmYes">Eliminar</button></div>
</dialog>
</body>
</html>
<?php
}

/** Interruptor accesible (checkbox). */
function ui_switch(string $name, bool $checked, string $label, string $extra = ''): string
{
    return '<label class="switch"><input type="checkbox" name="' . e($name) . '" value="1"' . ($checked ? ' checked' : '') . ($extra ? ' ' . $extra : '') . '>'
         . '<span class="track" aria-hidden="true"></span><span>' . e($label) . '</span></label>';
}

/** Miniatura con marcador cuando no hay imagen. */
function ui_thumb(?string $rel, string $label, int $hue, string $class = 'thumb'): string
{
    $src = $rel ? url($rel) : placeholder_uri($label, $hue);
    return '<img class="' . e($class) . '" src="' . e($src) . '" alt="" width="140" height="88" loading="lazy">';
}

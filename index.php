<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/content.php';

send_security_headers();

$c       = content_get();
$a       = $c['ajustes'];
$slides  = $c['slides'];
$nos     = $c['nosotros'];
$empresa = $a['nombre_empresa'] !== '' ? $a['nombre_empresa'] : 'Mi empresa';
$nSlides = count($slides);
$tema    = valid_hex((string)$a['color_acento']);
$contacto = array_filter([
    $a['email']    !== '' ? '<a href="mailto:' . e($a['email']) . '">' . e($a['email']) . '</a>' : '',
    $a['telefono'] !== '' ? '<a href="tel:' . e(preg_replace('/[^\d+]/', '', $a['telefono'])) . '">' . e($a['telefono']) . '</a>' : '',
]);
?>
<!DOCTYPE html>
<html lang="es" data-base="<?= e(base_path()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Home | <?= e($empresa) ?></title>
<meta name="application-name" content="<?= e($empresa) ?>">
<?php if ($a['texto_home'] !== ''): ?><meta name="description" content="<?= e(truncate($a['texto_home'], 160)) ?>">
<?php endif; ?>
<link rel="icon" href="<?= $a['logo'] ? e(url($a['logo'])) : 'data:,' ?>">
<link rel="stylesheet" href="<?= e(asset('css/variables.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/layout.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/responsive.css')) ?>">
<?php if ($tema): ?><link rel="stylesheet" href="<?= e(url('css/tema.php') . '?v=' . $c['version']) ?>">
<?php endif; ?>
<script defer src="<?= e(asset('js/app.js')) ?>"></script>
</head>
<body>
<header><div class="wrap"><nav aria-label="Principal">
<a href="<?= e(url('admin/')) ?>" class="logo" title="Ir al panel de administración">
    <?php if ($a['logo']): ?><?= picture_html($a['logo'], $c['ajustes']['logo_webp'] ?: null, '', 160, 40, ['priority' => true]) ?><?php endif; ?><span><?= e($empresa) ?></span>
</a>
<button id="burger" aria-label="Abrir menú" aria-expanded="false">☰</button>
<ul id="menu">
<li><a href="#home" data-v="home" class="on">Home</a></li>
<li><a href="#nosotros" data-v="nosotros">Nosotros</a></li>
<li><a href="#servicios" data-v="servicios">Servicios y productos</a></li>
</ul></nav></div></header>

<main>
<section id="home" class="view view-home">
<?php if ($nSlides > 0): ?>
<div class="hero" id="hero" aria-roledescription="carrusel" aria-label="Destacados">
<div id="slides"><?= slide_html($slides[0], 0, $nSlides, true) ?></div>
<button class="arrow" id="prev" aria-label="Anterior"<?= $nSlides > 1 ? '' : ' hidden' ?>>‹</button>
<button class="arrow" id="next" aria-label="Siguiente"<?= $nSlides > 1 ? '' : ' hidden' ?>>›</button>
<div class="dots" id="dots"><?= dots_html($nSlides) ?></div>
</div>
<?php endif; ?>
<div class="wrap home-body">
<?php if ($a['texto_home'] !== ''): ?><p class="lead flush"><?= e($a['texto_home']) ?></p>
<?php endif; ?>
<div id="svcList"><p class="muted">Cargando…</p></div>
<p class="cta"><a class="btn" href="#servicios">Hacer una solicitud</a></p>
</div>
</section>

<section id="nosotros" class="view view-about">
<div class="static" id="abTop"><?= picture_html($nos['imagenes']['superior']['imagen'], $nos['imagenes']['superior']['webp'], 'Imagen de la empresa', 1600, 600, ['label' => 'Imagen superior', 'hue' => 160]) ?></div>
<div class="wrap pt48">
<h2>Nuestros valores</h2>
<div class="grid" id="values"><?php foreach ($nos['valores'] as $v) { echo valor_html($v); } ?></div>
<div class="story mt56">
<h2>Nuestra historia</h2>
<?php foreach ($nos['historia'] as $p): ?><p><?= e($p) ?></p>
<?php endforeach; ?>
</div></div>
<div class="static mt40" id="abBottom"><?= picture_html($nos['imagenes']['inferior']['imagen'], $nos['imagenes']['inferior']['webp'], 'Imagen del equipo', 1600, 600, ['label' => 'Imagen inferior', 'hue' => 260]) ?></div>
</section>

<section id="servicios" class="view">
<div class="wrap">
<h2>Solicitar un servicio o producto</h2>
<?php if ($a['texto_servicios'] !== ''): ?><p class="lead"><?= e($a['texto_servicios']) ?></p>
<?php endif; ?>

<form id="form" novalidate>
<div class="f"><label for="nombre">Nombre completo *</label><input id="nombre" name="nombre" autocomplete="name" maxlength="120"><span class="err"></span></div>
<div class="f"><label for="email">Correo electrónico *</label><input id="email" name="email" type="email" autocomplete="email" maxlength="150"><span class="err"></span></div>
<div class="f"><label for="tel">Teléfono *</label><input id="tel" name="tel" type="tel" autocomplete="tel" maxlength="30"><span class="err"></span></div>
<div class="f"><label for="empresa">Empresa (opcional)</label><input id="empresa" name="empresa" maxlength="120"><span class="err"></span></div>
<div class="f"><label for="servicio">Servicio o producto *</label><select id="servicio" name="servicio"><option value="">Cargando…</option></select><span class="err"></span></div>
<div class="f"><label for="plazo">Prioridad / plazo</label><select id="plazo" name="plazo"><option>Sin prisa</option><option>Este mes</option><option>Esta semana</option><option>Urgente</option></select><span class="err"></span></div>
<div class="f"><label for="cant">Cantidad (opcional)</label><input id="cant" name="cantidad" type="number" min="1"><span class="err"></span></div>
<div class="f full"><label for="desc">Describe lo que necesitas *</label><textarea id="desc" name="descripcion" rows="4" maxlength="3000"></textarea><span class="err"></span></div>
<div class="hp" aria-hidden="true"><label for="sitio_web">No llenar este campo</label><input id="sitio_web" name="sitio_web" type="text" tabindex="-1" autocomplete="off"></div>
<div class="f full"><label class="check"><input type="checkbox" id="terms" name="terms" value="1"> Acepto los términos y la política de privacidad *</label><span class="err"></span></div>
<div class="f full"><button class="btn" type="submit" id="send">Enviar solicitud</button></div>
</form>
<div class="ok" id="ok" role="status"></div>
</div></section>
</main>

<footer><div class="wrap"><span>© <?= date('Y') ?> <?= e($empresa) ?>.<?= $a['texto_footer'] !== '' ? ' ' . e($a['texto_footer']) : '' ?></span><span><?= implode(' · ', $contacto) ?></span></div></footer>
</body>
</html>
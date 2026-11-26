<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/content.php';

send_security_headers();

$c       = content_get();
$a       = $c['ajustes'] + ajustes_defaults(); // + defaults: el caché puede ser de una versión anterior
$slides  = $c['slides'];
$nos     = $c['nosotros'];
$empresa = $a['nombre_empresa'] !== '' ? $a['nombre_empresa'] : 'Mi empresa';
$nSlides = count($slides);
$tema    = valid_hex((string)$a['color_acento']);
$descripcion = $a['eslogan'] !== '' ? truncate($a['eslogan'], 160) : ($a['texto_home'] !== '' ? truncate($a['texto_home'], 160) : '');
$ini     = $c['inicio'] ?? [];
$cifras  = $ini['cifras'] ?? [];
$puntos  = $ini['puntos'] ?? [];
$testimonios = $ini['testimonios'] ?? [];
$preguntas   = $ini['preguntas'] ?? [];
$secc    = $c['secciones'] ?? [];
$redes   = redes_html($a);
$wa      = whatsapp_url($a);
// Imagen al compartir: la del primer slide si tiene; si no, la genérica de img/
$ogImg   = absolute_url(!empty($slides[0]['imagen']) ? (string)$slides[0]['imagen'] : 'img/compartir.png');
$mv      = array_values(array_filter([['objetivo', 'Misión', $a['mision']], ['ojo', 'Visión', $a['vision']]], fn($x) => trim($x[2]) !== ''));

// Datos de contacto: [clave del ícono, etiqueta, enlace]
$contacto = [];
if ($a['telefono'] !== '') {
    $contacto[] = ['telefono', 'Teléfono', '<a href="tel:' . e((string)preg_replace('/[^\d+]/', '', $a['telefono'])) . '">' . e($a['telefono']) . '</a>'];
}
if ($a['email'] !== '') {
    $contacto[] = ['correo', 'Correo', '<a href="mailto:' . e($a['email']) . '">' . e($a['email']) . '</a>'];
}
if ($a['direccion'] !== '') { $contacto[] = ['ubicacion', 'Ubicación', e($a['direccion'])]; }
if ($a['horario'] !== '')   { $contacto[] = ['horario', 'Horario', e($a['horario'])]; }
$hayInfo = $contacto || $redes !== '' || $wa !== '';
$hayHistoria = count($nos['historia']) > 0;
?>
<!DOCTYPE html>
<html lang="es" data-base="<?= e(base_path()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Inicio | <?= e($empresa) ?></title>
<meta name="application-name" content="<?= e($empresa) ?>">
<meta name="theme-color" content="#16324a">
<?php if ($descripcion !== ''): ?><meta name="description" content="<?= e($descripcion) ?>">
<?php endif; ?>
<!-- Vista previa al compartir el enlace (WhatsApp, Facebook, etc.) -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($empresa) ?>">
<meta property="og:title" content="<?= e($empresa) ?>">
<?php if ($descripcion !== ''): ?><meta property="og:description" content="<?= e($descripcion) ?>">
<?php endif; ?>
<meta property="og:locale" content="es_MX">
<meta property="og:image" content="<?= e($ogImg) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="<?= $a['logo'] ? e(url($a['logo'])) : e(favicon_uri($empresa)) ?>">
<script src="<?= e(asset('js/tema-inicial.js')) ?>"></script>
<link rel="preload" href="<?= e(url('fonts/inter-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
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
<header id="top"><div class="wrap"><nav aria-label="Principal">
<a href="#home" class="logo" aria-label="<?= e($empresa) ?>: ir al inicio"><?= brand_html($a['logo'], (string)($a['logo_webp'] ?? ''), $empresa, true) ?></a>
<div class="navr">
<ul id="menu">
<li><a href="#home" data-v="home" class="on">Inicio</a></li>
<li><a href="#nosotros" data-v="nosotros">Nosotros</a></li>
<li><a href="#servicios" data-v="servicios" class="cta">Solicitar servicio</a></li>
</ul>
<button id="themeBtn" class="iconbtn" type="button" aria-label="Cambiar tema"><span class="moon"><?= ui_icon('luna') ?></span><span class="sun"><?= ui_icon('sol') ?></span></button>
<a id="openAdmin" class="iconbtn" href="<?= e(url('admin/')) ?>" aria-label="Panel de administración" title="Panel de administración"><?= ui_icon('usuario') ?></a>
<button id="burger" class="iconbtn" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="menu"><?= ui_icon('menu') ?></button>
</div>
</nav></div></header>

<main>
<!-- ============ INICIO ============ -->
<section id="home" class="view">
<?php if ($nSlides > 0): ?>
<div class="hero" id="hero" aria-roledescription="carrusel" aria-label="Destacados">
<div id="slides"><?= slide_html($slides[0], 0, $nSlides, true) ?></div>
<button class="arrow" id="prev" type="button" aria-label="Anterior"<?= $nSlides > 1 ? '' : ' hidden' ?>><?= ui_icon('anterior') ?></button>
<button class="arrow" id="next" type="button" aria-label="Siguiente"<?= $nSlides > 1 ? '' : ' hidden' ?>><?= ui_icon('siguiente') ?></button>
<div class="dots" id="dots"<?= $nSlides > 1 ? '' : ' hidden' ?>><?= dots_html($nSlides) ?></div>
</div>
<?php endif; ?>

<?php if ($cifras): ?>
<div class="wrap"><div class="stats reveal<?= $nSlides > 0 ? '' : ' flat' ?>"><?= cifras_html($cifras) ?></div></div>
<?php endif; ?>

<?php if ($puntos): ?>
<div class="sec">
<div class="wrap">
<div class="head reveal">
<span class="eyebrow">¿Por qué elegirnos?</span>
<h2>Trabajamos para que tú te enfoques en lo importante</h2>
<p>Te acompañamos desde la primera llamada hasta la entrega, con atención cercana y resultados medibles.</p>
</div>
<div class="grid g4 reveal"><?php foreach ($puntos as $p) { echo punto_html($p); } ?></div>
</div>
</div>
<?php endif; ?>

<div class="sec soft" id="svcSec">
<div class="wrap">
<div class="head reveal">
<span class="eyebrow">Lo que ofrecemos</span>
<h2>Servicios y productos</h2>
<p><?= $a['texto_home'] !== '' ? e($a['texto_home']) : 'Conoce nuestras soluciones. Para contratar un servicio o pedir un producto, da clic en «Solicitar».' ?></p>
</div>
<div id="svcList" class="reveal"><p class="empty">Cargando…</p></div>
</div>
</div>

<?php if ($testimonios): ?>
<div class="sec" id="tstSec">
<div class="wrap">
<div class="head reveal"><span class="eyebrow">Testimonios</span><h2>Lo que dicen nuestros clientes</h2></div>
<div class="grid g3 reveal"><?php foreach ($testimonios as $t) { echo testimonio_html($t); } ?></div>
</div>
</div>
<?php endif; ?>

<?php if ($preguntas): ?>
<div class="sec soft" id="faqSec">
<div class="wrap">
<div class="head reveal"><span class="eyebrow">Preguntas frecuentes</span><h2>¿Tienes dudas?</h2><p>Aquí respondemos las preguntas más comunes. Si no encuentras la tuya, escríbenos.</p></div>
<div class="faq reveal"><?php foreach ($preguntas as $q) { echo pregunta_html($q); } ?></div>
</div>
</div>
<?php endif; ?>

<?php
// Las secciones extra alternan el fondo empezando por el contrario de la última sección
$ultimaSoft = $preguntas ? true : ($testimonios ? false : true);
echo secciones_html($secc['inicio'] ?? [], !$ultimaSoft);
?>

<div class="band"><div class="wrap band-in reveal">
<div><h2>¿Listo para empezar?</h2><p>Cuéntanos lo que necesitas y te enviamos una propuesta sin compromiso.</p></div>
<a class="btn" href="#servicios">Solicitar cotización</a>
</div></div>
</section>

<!-- ============ NOSOTROS ============ -->
<section id="nosotros" class="view">
<div class="pagehead" id="abTop"><?= picture_html($nos['imagenes']['superior']['imagen'], $nos['imagenes']['superior']['webp'], '', 1600, 600, ['hue' => 160]) ?>
<div class="ov"><div class="wrap"><h1>Nosotros</h1><?php if ($a['eslogan'] !== ''): ?><p><?= e($a['eslogan']) ?></p><?php endif; ?></div></div></div>

<?php if ($mv): ?>
<div class="sec">
<div class="wrap">
<div class="head reveal"><span class="eyebrow">Quiénes somos</span><h2>Nuestro propósito</h2></div>
<div class="grid <?= count($mv) > 1 ? 'g2' : 'g1' ?> reveal"><?php foreach ($mv as [$ico, $tit, $txt]): ?><div class="card mv"><h3><span class="ico"><?= ui_icon($ico) ?></span><?= e($tit) ?></h3><?= paras_html($txt) ?></div><?php endforeach; ?></div>
</div>
</div>
<?php endif; ?>

<div class="sec<?= $mv ? ' soft' : '' ?>">
<div class="wrap split reveal<?= $hayHistoria ? '' : ' solo' ?>">
<?php if ($hayHistoria): ?>
<div class="story"><span class="eyebrow">Nuestra historia</span><h2>Cómo empezamos</h2>
<?php foreach ($nos['historia'] as $p): ?><p><?= e($p) ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>
<div class="split-img" id="abBottom"><?= picture_html($nos['imagenes']['inferior']['imagen'], $nos['imagenes']['inferior']['webp'], 'Nuestro equipo', 1600, 1200, ['hue' => 260]) ?></div>
</div>
</div>

<?php if ($nos['valores']): ?>
<div class="sec<?= $mv ? '' : ' soft' ?>">
<div class="wrap">
<div class="head reveal"><span class="eyebrow">Lo que nos mueve</span><h2>Nuestros valores</h2></div>
<div class="grid g3 reveal" id="values"><?php foreach ($nos['valores'] as $v) { echo valor_html($v); } ?></div>
</div>
</div>
<?php endif; ?>

<?php
// Fondo de la última sección de Nosotros: historia (suave si hay misión/visión) y valores (lo contrario)
$ultimaSoft = $nos['valores'] ? !$mv : (bool)$mv;
echo secciones_html($secc['nosotros'] ?? [], !$ultimaSoft);
?>
</section>

<!-- ============ SOLICITAR ============ -->
<section id="servicios" class="view">
<div class="sec">
<div class="wrap">
<div class="head reveal">
<span class="eyebrow">Contacto</span>
<h2>Solicitar un servicio o producto</h2>
<p><?= $a['texto_servicios'] !== '' ? e($a['texto_servicios']) : 'Elige lo que necesitas y cuéntanos los detalles. Te responderemos con una propuesta.' ?></p>
</div>
<div class="contact reveal<?= $hayInfo ? '' : ' solo' ?>">
<?php if ($hayInfo): ?>
<aside class="info">
<h3>Hablemos</h3>
<p>¿Prefieres contactarnos directamente? Estamos para ayudarte.</p>
<ul>
<?php foreach ($contacto as [$ico, $etq, $link]): ?><li><span class="ico"><?= ui_icon($ico) ?></span><div><small><?= e($etq) ?></small><?= $link ?></div></li>
<?php endforeach; ?>
</ul>
<?php if ($redes !== ''): ?><div class="social"><?= $redes ?></div><?php endif; ?>
<?php if ($wa !== ''): ?><a class="btn wa-btn" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= whatsapp_svg() ?> Escríbenos por WhatsApp</a><?php endif; ?>
</aside>
<?php endif; ?>
<div>
<form id="form" novalidate>
<div class="f"><label for="nombre">Nombre completo *</label><input id="nombre" name="nombre" autocomplete="name" maxlength="120"><span class="err"></span></div>
<div class="f"><label for="email">Correo electrónico *</label><input id="email" name="email" type="email" autocomplete="email" maxlength="150"><span class="err"></span></div>
<div class="f"><label for="tel">Teléfono *</label><input id="tel" name="tel" type="tel" autocomplete="tel" maxlength="30"><span class="err"></span></div>
<div class="f"><label for="empresa">Empresa (opcional)</label><input id="empresa" name="empresa" autocomplete="organization" maxlength="120"><span class="err"></span></div>
<div class="f"><label for="servicio">Servicio o producto *</label><select id="servicio" name="servicio"><option value="">Cargando…</option></select><span class="err"></span></div>
<div class="f"><label for="plazo">Prioridad / plazo</label><select id="plazo" name="plazo"><option>Sin prisa</option><option>Este mes</option><option>Esta semana</option><option>Urgente</option></select><span class="err"></span></div>
<div class="f"><label for="cant">Cantidad (opcional)</label><input id="cant" name="cantidad" type="number" min="1"><span class="err"></span></div>
<div class="f full"><label for="desc">Describe lo que necesitas *</label><textarea id="desc" name="descripcion" rows="4" maxlength="3000"></textarea><span class="err"></span></div>
<div class="hp" aria-hidden="true"><label for="sitio_web">No llenar este campo</label><input id="sitio_web" name="sitio_web" type="text" tabindex="-1" autocomplete="off"></div>
<div class="f full"><label class="check"><input type="checkbox" id="terms" name="terms" value="1"> <span>Acepto los términos y la política de privacidad *</span></label><span class="err"></span></div>
<div class="f full"><button class="btn" type="submit" id="send">Enviar solicitud</button></div>
</form>
<div class="ok" id="ok" role="status"></div>
</div>
</div>
</div>
</div>
</section>
</main>

<footer><div class="wrap">
<div class="fgrid">
<div>
<a href="#home" class="logo flogo" aria-label="<?= e($empresa) ?>: ir al inicio"><?= brand_html($a['logo'], (string)($a['logo_webp'] ?? ''), $empresa, false) ?></a>
<?php if ($descripcion !== ''): ?><p><?= e($descripcion) ?></p>
<?php endif; ?>
</div>
<div><h4>Navegación</h4><ul><li><a href="#home">Inicio</a></li><li><a href="#nosotros">Nosotros</a></li><li><a href="#svcSec">Servicios y productos</a></li><li><a href="#servicios">Solicitar servicio</a></li></ul></div>
<?php if ($contacto): ?>
<div><h4>Contacto</h4><ul><?php foreach ($contacto as $x): ?><li><?= $x[2] ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<?php if ($redes !== ''): ?>
<div><h4>Síguenos</h4><div class="social"><?= $redes ?></div></div>
<?php endif; ?>
</div>
<div class="fbot"><span>© <?= date('Y') ?> <?= e($empresa) ?>.<?= $a['texto_footer'] !== '' ? ' ' . e($a['texto_footer']) : '' ?></span><span><a href="<?= e(url('admin/')) ?>">Panel de administración</a></span></div>
</div></footer>
<?php if ($wa !== ''): ?>
<a class="wa" href="<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp"><?= whatsapp_svg() ?></a>
<?php endif; ?>
</body>
</html>

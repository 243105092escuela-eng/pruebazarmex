<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_layout.php';
require __DIR__ . '/../includes/upload.php';
require __DIR__ . '/../includes/content.php';
require __DIR__ . '/../includes/listas.php';

$user = require_login();

lista_handle(['cifras', 'puntos']);

admin_head('Página de inicio', 'pagina-inicio', $user);
echo lista_card_html('cifras', 'Cifras', 'Aparecen en una franja debajo del carrusel (por ejemplo «+10 · años de experiencia»). Hasta 8; se ven mejor 3 o 4.');
echo lista_card_html('puntos', '¿Por qué elegirnos?', 'Tarjetas con ícono que aparecen después de las cifras. Hasta 8; se ven mejor 4.');
admin_foot();

<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_layout.php';
require __DIR__ . '/../includes/upload.php';
require __DIR__ . '/../includes/content.php';
require __DIR__ . '/../includes/listas.php';

$user = require_login();

lista_handle(['testimonios']);

admin_head('Testimonios', 'testimonios', $user);
echo lista_card_html('testimonios', 'Testimonios', 'Opiniones de clientes en la página de inicio. La foto es opcional (cuadrada, JPG/PNG/WebP, máx. 3 MB); si no hay, se muestran sus iniciales.');
admin_foot();

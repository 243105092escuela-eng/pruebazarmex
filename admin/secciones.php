<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_layout.php';
require __DIR__ . '/../includes/upload.php';
require __DIR__ . '/../includes/content.php';
require __DIR__ . '/../includes/listas.php';

$user = require_login();

lista_handle(['secciones']);

admin_head('Secciones extra', 'secciones', $user);
echo lista_card_html('secciones', 'Secciones extra', 'Bloques libres con título, texto e imagen opcional, en Inicio (antes de «¿Listo para empezar?») o al final de Nosotros. Aparecen en el orden de esta lista.');
admin_foot();

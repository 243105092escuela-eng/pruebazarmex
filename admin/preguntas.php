<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_layout.php';
require __DIR__ . '/../includes/upload.php';
require __DIR__ . '/../includes/content.php';
require __DIR__ . '/../includes/listas.php';

$user = require_login();

lista_handle(['preguntas']);

admin_head('Preguntas frecuentes', 'preguntas', $user);
echo lista_card_html('preguntas', 'Preguntas frecuentes', 'Se muestran en la página de inicio y se abren al dar clic. Para separar párrafos en la respuesta, usa Enter.');
admin_foot();

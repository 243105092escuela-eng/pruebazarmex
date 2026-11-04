-- =====================================================================
--  Zarmex · Datos de ejemplo (equivalentes a los del sitio estático)
--  Importar DESPUÉS de schema.sql y una sola vez (en una base vacía).
--
--  Usuario inicial:  admin@ejemplo.com   Contraseña temporal: Cambiar#2026
--  El sistema obliga a cambiarla en el primer ingreso.
--  EN PRODUCCIÓN: cambia el correo o crea tu propio admin con
--  database/crear_admin.php y elimina este usuario.
-- =====================================================================
SET NAMES utf8mb4;

INSERT INTO usuarios (nombre, email, password_hash, rol, activo, debe_cambiar_password) VALUES
('Administrador', 'admin@ejemplo.com', '$2y$11$J17GwNOwsTGGPPSWisgzH.HeNHwGGTPg4wf1xr8CsQVXojrXnTEdW', 'admin', 1, 1);

INSERT INTO ajustes (clave, valor) VALUES
('nombre_empresa',  '[Nombre de la empresa]'),
('logo',            NULL),
('historia_p1',     'Aquí va el texto del origen de la empresa: cómo empezó, quiénes la fundaron y qué problema querían resolver.'),
('historia_p2',     'Un segundo párrafo para contar la trayectoria, los logros y hacia dónde va la empresa.'),
('email',           'contacto@ejemplo.com'),
('telefono',        '+00 000 000 0000'),
('texto_home',      'Conoce lo que ofrecemos. Para contratar un servicio o pedir un producto, usa el formulario.'),
('texto_servicios', 'Elige lo que necesitas y cuéntanos los detalles. Te responderemos con una propuesta.'),
('texto_footer',    'Todos los derechos reservados.'),
('color_acento',        ''),
('color_acento_texto',  '');

INSERT INTO slides (imagen, titulo, subtitulo, enlace, orden, activo) VALUES
(NULL, 'Título de la diapositiva 1', 'Subtítulo breve que explica la propuesta.', '#servicios', 1, 1),
(NULL, 'Título de la diapositiva 2', 'Subtítulo breve que explica la propuesta.', '#servicios', 2, 1),
(NULL, 'Título de la diapositiva 3', 'Subtítulo breve que explica la propuesta.', '#servicios', 3, 1);

INSERT INTO items (tipo, nombre, slug, descripcion, precio, imagen, orden, activo) VALUES
('Servicio', 'Servicio 1',  'servicio-1',  'Descripción del servicio 1. Edítala desde el panel.',  NULL,    NULL, 1, 1),
('Servicio', 'Servicio 2',  'servicio-2',  'Descripción del servicio 2. Edítala desde el panel.',  NULL,    NULL, 2, 1),
('Servicio', 'Servicio 3',  'servicio-3',  'Descripción del servicio 3. Edítala desde el panel.',  NULL,    NULL, 3, 1),
('Producto', 'Producto 1',  'producto-1',  'Descripción del producto 1. Edítala desde el panel.',  '$0.00', NULL, 4, 1),
('Producto', 'Producto 2',  'producto-2',  'Descripción del producto 2. Edítala desde el panel.',  '$0.00', NULL, 5, 1);

INSERT INTO nosotros_imagenes (posicion, imagen) VALUES
('superior', NULL),
('inferior', NULL);

INSERT INTO valores (titulo, texto, icono, orden, activo) VALUES
('Compromiso',        'Cumplimos lo que prometemos.',           'corazon',  1, 1),
('Calidad',           'Cuidamos cada detalle del trabajo.',     'estrella', 2, 1),
('Innovación',        'Buscamos mejores formas de hacerlo.',    'bombilla', 3, 1),
('Confianza',         'Relaciones claras y transparentes.',     'escudo',   4, 1),
('Trabajo en equipo', 'Juntos llegamos más lejos.',             'equipo',   5, 1),
('Responsabilidad',   'Respondemos por nuestros resultados.',   'check',    6, 1);

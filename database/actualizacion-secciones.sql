-- =====================================================================
--  Zarmex · Actualización: secciones nuevas del rediseño
--  (cifras, "¿Por qué elegirnos?", testimonios, preguntas frecuentes,
--   secciones extra, misión/visión, eslogan, dirección, horario,
--   WhatsApp y redes sociales)
--
--  SOLO para sitios que ya estaban instalados antes de esta versión.
--  En una instalación nueva NO hace falta: schema.sql y seed.sql ya lo incluyen.
--
--  USO EN phpMyAdmin: selecciona la base -> pestaña "Importar" -> este archivo.
--  Se puede importar más de una vez sin duplicar nada.
-- =====================================================================
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Cifras de Inicio (debajo del carrusel: "+10 años de experiencia", etc.)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cifras (
  id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  numero VARCHAR(20) NOT NULL,
  texto  VARCHAR(80) NOT NULL DEFAULT '',
  orden  INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_cifras_activo_orden (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- "¿Por qué elegirnos?" en Inicio (tarjetas con ícono)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS puntos (
  id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo VARCHAR(80)  NOT NULL,
  texto  VARCHAR(255) NOT NULL DEFAULT '',
  icono  VARCHAR(30)  NOT NULL DEFAULT 'check',
  orden  INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_puntos_activo_orden (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Testimonios de clientes (Inicio)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS testimonios (
  id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  cargo  VARCHAR(120) NOT NULL DEFAULT '',
  texto  VARCHAR(600) NOT NULL DEFAULT '',
  imagen VARCHAR(255) NULL DEFAULT NULL,
  orden  INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_testimonios_activo_orden (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Preguntas frecuentes (Inicio)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS preguntas (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pregunta  VARCHAR(255) NOT NULL,
  respuesta TEXT NOT NULL,
  orden     INT NOT NULL DEFAULT 0,
  activo    TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_preguntas_activo_orden (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Secciones libres (título, texto e imagen opcional) en Inicio o Nosotros
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS secciones (
  id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pagina ENUM('inicio','nosotros') NOT NULL DEFAULT 'inicio',
  titulo VARCHAR(150) NOT NULL DEFAULT '',
  texto  TEXT NOT NULL,
  imagen VARCHAR(255) NULL DEFAULT NULL,
  lado   ENUM('derecha','izquierda') NOT NULL DEFAULT 'derecha',
  orden  INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_secciones_pagina (pagina, activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Ajustes nuevos (no cambia los que ya existan)
-- ---------------------------------------------------------------------
INSERT IGNORE INTO ajustes (clave, valor) VALUES
('eslogan',         'Soluciones profesionales a la medida de tu empresa.'),
('direccion',       'Ciudad, Estado, México'),
('horario',         'Lunes a viernes, 9:00 a 18:00 h'),
('whatsapp',        ''),
('whatsapp_msg',    'Hola, me gustaría recibir información sobre sus servicios.'),
('mision',          'Ofrecer a nuestros clientes soluciones de calidad, con atención cercana y precios justos, que les ayuden a crecer.'),
('vision',          'Ser la empresa de referencia en nuestro ramo, reconocida por la confianza y las relaciones de largo plazo con nuestros clientes.'),
('red_facebook',    ''),
('red_instagram',   ''),
('red_tiktok',      ''),
('red_linkedin',    ''),
('red_youtube',     ''),
('red_x',           '');

-- ---------------------------------------------------------------------
-- Datos de ejemplo (solo si la tabla está vacía)
-- ---------------------------------------------------------------------
INSERT INTO cifras (numero, texto, orden, activo)
SELECT * FROM (
  SELECT '+10' AS numero, 'años de experiencia' AS texto, 1 AS orden, 1 AS activo
  UNION ALL
  SELECT '+200', 'clientes satisfechos',     2, 1
  UNION ALL
  SELECT '24 h', 'tiempo de respuesta',      3, 1
  UNION ALL
  SELECT '100%', 'cotizaciones a la medida', 4, 1
) AS ejemplo
WHERE NOT EXISTS (SELECT 1 FROM cifras);

INSERT INTO puntos (titulo, texto, icono, orden, activo)
SELECT * FROM (
  SELECT 'Respuesta rápida' AS titulo, 'Atendemos tu solicitud el mismo día hábil.' AS texto, 'reloj' AS icono, 1 AS orden, 1 AS activo
  UNION ALL
  SELECT 'A tu medida',         'Cada propuesta se adapta a tus necesidades y presupuesto.', 'lista',  2, 1
  UNION ALL
  SELECT 'Calidad garantizada', 'Cuidamos cada detalle y respondemos por el resultado.',     'escudo', 3, 1
  UNION ALL
  SELECT 'Equipo experto',      'Personal capacitado y con experiencia en el ramo.',         'equipo', 4, 1
) AS ejemplo
WHERE NOT EXISTS (SELECT 1 FROM puntos);

INSERT INTO testimonios (nombre, cargo, texto, imagen, orden, activo)
SELECT * FROM (
  SELECT 'Nombre del cliente 1' AS nombre, 'Puesto, Empresa 1' AS cargo, 'Aquí va la opinión del cliente sobre el servicio que recibió. Edítala desde el panel de administración.' AS texto, NULL AS imagen, 1 AS orden, 1 AS activo
  UNION ALL
  SELECT 'Nombre del cliente 2', 'Puesto, Empresa 2', 'Aquí va la opinión del cliente sobre el servicio que recibió. Edítala desde el panel de administración.', NULL, 2, 1
  UNION ALL
  SELECT 'Nombre del cliente 3', 'Puesto, Empresa 3', 'Aquí va la opinión del cliente sobre el servicio que recibió. Edítala desde el panel de administración.', NULL, 3, 1
) AS ejemplo
WHERE NOT EXISTS (SELECT 1 FROM testimonios);

INSERT INTO preguntas (pregunta, respuesta, orden, activo)
SELECT * FROM (
  SELECT '¿Cómo solicito una cotización?' AS pregunta, 'Llena el formulario de «Solicitar servicio» o escríbenos por WhatsApp. Te respondemos con una propuesta a la medida.' AS respuesta, 1 AS orden, 1 AS activo
  UNION ALL
  SELECT '¿Cuánto tardan en responder?',   'Normalmente respondemos el mismo día hábil.',                       2, 1
  UNION ALL
  SELECT '¿Qué formas de pago aceptan?',   'Escribe aquí las formas de pago que acepta la empresa.',            3, 1
  UNION ALL
  SELECT '¿Dan factura?',                  'Escribe aquí la respuesta.',                                        4, 1
) AS ejemplo
WHERE NOT EXISTS (SELECT 1 FROM preguntas);

-- =====================================================================
--  Zarmex · Estructura de la base de datos (MySQL 5.7+ / MariaDB 10.3+)
--  Motor InnoDB · utf8mb4
--
--  USO EN phpMyAdmin:
--    1) Crea la base (pestaña "Bases de datos"): nombre  zarmex
--       cotejamiento  utf8mb4_unicode_ci
--    2) Selecciónala en el panel izquierdo
--    3) Pestaña "Importar" -> schema.sql -> Continuar
--    4) Repite con seed.sql
--  (No incluye CREATE DATABASE para funcionar también en hosting compartido,
--   donde normalmente no hay permiso para crear bases.)
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Usuarios del panel
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre                VARCHAR(100) NOT NULL,
  email                 VARCHAR(150) NOT NULL,
  password_hash         VARCHAR(255) NOT NULL,
  rol                   ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  activo                TINYINT(1) NOT NULL DEFAULT 1,
  debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 1,
  creado_en             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_login          DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email),
  KEY idx_usuarios_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Intentos fallidos de inicio de sesión (bloqueo temporal por IP)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS intentos_login (
  id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip        VARCHAR(45) NOT NULL,
  email     VARCHAR(150) NULL DEFAULT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_intentos_ip_fecha (ip, creado_en),
  KEY idx_intentos_fecha (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Carrusel (máx. 5 activos; se valida en el servidor)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS slides (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  imagen    VARCHAR(255) NULL DEFAULT NULL,
  titulo    VARCHAR(150) NOT NULL,
  subtitulo VARCHAR(255) NOT NULL DEFAULT '',
  enlace    VARCHAR(255) NOT NULL DEFAULT '',
  orden     INT NOT NULL DEFAULT 0,
  activo    TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_slides_activo_orden (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Servicios y productos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS items (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo        ENUM('Servicio','Producto') NOT NULL DEFAULT 'Servicio',
  nombre      VARCHAR(150) NOT NULL,
  slug        VARCHAR(160) NOT NULL,
  descripcion TEXT NOT NULL,
  precio      VARCHAR(60) NULL DEFAULT NULL,
  imagen      VARCHAR(255) NULL DEFAULT NULL,
  orden       INT NOT NULL DEFAULT 0,
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_items_slug (slug),
  KEY idx_items_activo_orden (activo, orden),
  KEY idx_items_tipo_activo (tipo, activo),
  KEY idx_items_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Imágenes estáticas de "Nosotros" (superior / inferior)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS nosotros_imagenes (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  posicion ENUM('superior','inferior') NOT NULL,
  imagen   VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_nosotros_posicion (posicion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Valores de la empresa (sección Nosotros)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS valores (
  id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo VARCHAR(80)  NOT NULL,
  texto  VARCHAR(255) NOT NULL DEFAULT '',
  icono  VARCHAR(30)  NOT NULL DEFAULT 'check',
  orden  INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_valores_activo_orden (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Ajustes del sitio (clave / valor)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ajustes (
  clave          VARCHAR(60) NOT NULL,
  valor          TEXT NULL,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Solicitudes recibidas por el formulario público
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS solicitudes (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(120) NOT NULL,
  email       VARCHAR(150) NOT NULL,
  telefono    VARCHAR(30)  NOT NULL,
  empresa     VARCHAR(120) NULL DEFAULT NULL,
  item_id     INT UNSIGNED NULL DEFAULT NULL,
  item_nombre VARCHAR(150) NULL DEFAULT NULL,
  plazo       VARCHAR(30)  NOT NULL DEFAULT 'Sin prisa',
  cantidad    INT UNSIGNED NULL DEFAULT NULL,
  descripcion TEXT NOT NULL,
  estado      ENUM('nueva','vista','atendida','archivada') NOT NULL DEFAULT 'nueva',
  ip          VARCHAR(45)  NOT NULL DEFAULT '',
  creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_solicitudes_estado (estado),
  KEY idx_solicitudes_creado (creado_en),
  KEY idx_solicitudes_estado_creado (estado, creado_en),
  KEY idx_solicitudes_ip_creado (ip, creado_en),
  KEY idx_solicitudes_item (item_id),
  CONSTRAINT fk_solicitudes_item FOREIGN KEY (item_id) REFERENCES items (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

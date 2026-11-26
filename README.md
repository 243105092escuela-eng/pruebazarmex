# Zarmex · Sitio dinámico con panel de administración

Sitio público (Home · Nosotros · Servicios y productos) alimentado por MySQL/MariaDB, más un panel de
administración en `/admin/`. PHP 8 + PDO, JavaScript vanilla, sin librerías ni CDN: funciona offline en local.

---

## 1. Requisitos

| Requisito | Detalle |
|---|---|
| PHP | 8.0 o superior, con extensiones `pdo_mysql`, `gd` (con soporte WebP deseable), `fileinfo`, `mbstring` |
| Base de datos | MySQL 5.7+ o MariaDB 10.3+ |
| Servidor | Apache con `.htaccess` habilitado (`AllowOverride All`). Recomendados: `mod_rewrite`, `mod_headers`, `mod_deflate`, `mod_expires` |
| Entorno local | XAMPP, WAMP o Laragon (todos incluyen lo anterior) |

El **estado del sistema** (si falta GD, WebP, HTTPS o permisos de escritura) no se muestra al dueño del sitio: se consulta abriendo `/admin/?estado` con la sesión iniciada. Si algo grave falla, el inicio del panel muestra un aviso sencillo para que contacte a quien administra el sitio.

---

## 2. Instalación en local (XAMPP / WAMP / Laragon)

1. **Copia la carpeta** del proyecto dentro de `htdocs` (XAMPP), `www` (WAMP) o `www` (Laragon). Ejemplo: `htdocs/zarmex/`.
2. **Inicia Apache y MySQL** desde el panel de tu entorno.
3. **Crea la base de datos** en phpMyAdmin (`http://localhost/phpmyadmin`):
   pestaña **Bases de datos** → nombre `zarmex` → cotejamiento **`utf8mb4_unicode_ci`** → *Crear*.
4. **Importa el SQL**: selecciona la base `zarmex` en el panel izquierdo → pestaña **Importar** →
   elige `database/schema.sql` → *Importar*. Repite con `database/seed.sql` (una sola vez).
   > **¿Ya tenías el sitio instalado de antes?** No vuelvas a importar `schema.sql` ni `seed.sql`: importa solo
   > `database/actualizacion-secciones.sql`. Crea las tablas de las secciones nuevas (cifras, «¿Por qué elegirnos?»,
   > testimonios, preguntas frecuentes, secciones extra) y los ajustes nuevos sin tocar lo que ya tienes. Se puede
   > importar más de una vez sin duplicar nada.
5. **Credenciales** (opcional en local): XAMPP/WAMP/Laragon usan por defecto usuario `root` sin contraseña,
   que ya es el valor por defecto del proyecto. Si las tuyas son otras, copia `.env.example` como `.env` y edítalo.
   Pon `APP_ENV=development` mientras desarrollas para ver errores en pantalla.
6. **Permisos de escritura**: PHP debe poder escribir en `uploads/*/`, `cache/` y `logs/`.
   En Windows normalmente ya es así. En Linux/macOS: `chmod -R 775 uploads cache logs`
   (y que el dueño sea el usuario del servidor web, p. ej. `chown -R www-data uploads cache logs`).
7. **Abre el sitio**: `http://localhost/zarmex/`

### URLs y usuario inicial

| | URL |
|---|---|
| Sitio público | `http://localhost/zarmex/` |
| Panel de administración | `http://localhost/zarmex/admin/` (no hay enlace en el menú público) |

Usuario del seed: **`admin@ejemplo.com`** · contraseña temporal: **`Cambiar#2026`**.
En el **primer ingreso el sistema obliga a cambiar la contraseña** (mínimo 10 caracteres, con letras y números).

> **Para producción** no uses el usuario del seed. Tras importar solo `schema.sql` (sin `seed.sql`) o después de
> importar ambos, crea tu propio administrador por línea de comandos y elimina `admin@ejemplo.com` desde *Usuarios*:
>
> ```
> php database/crear_admin.php tu@correo.com "Tu Nombre"
> ```
> Muestra una contraseña temporal aleatoria (una sola vez) y exige cambiarla al entrar. También sirve para
> **recuperar el acceso** si olvidas la contraseña (restablece al usuario si el correo ya existe).

---

## 3. Qué hace cada parte

**Sitio público.** `index.php` pinta desde el servidor el primer slide, textos, valores, historia, footer e imágenes de
Nosotros (sin parpadeo). `js/app.js` carga el resto con **una sola petición** a `api/content.php` (con `ETag`: si no hay
cambios responde `304`). Mantiene carrusel, menú hamburguesa, vistas por hash y tarjetas agrupadas.

**Diseño.** Tipografías Inter (texto) y Poppins (títulos) servidas desde `fonts/` (licencia OFL, sin Google Fonts),
encabezado con modo claro/oscuro y acceso al panel, secciones con antetítulo, banda «¿Listo para empezar?» y footer en
columnas. El modo oscuro solo afecta al sitio público: `js/tema-inicial.js` (en `<head>`, antes de los CSS para que no
parpadee) aplica la elección guardada del visitante o, si nunca eligió, la del sistema; el panel siempre se ve en claro.
El botón «Solicitar» de cada tarjeta abre el formulario con ese servicio ya elegido.

**Formulario.** Validación en cliente y en servidor, token CSRF, campo trampa anti-spam (honeypot) y límite de 5 envíos
por IP por hora. Se guarda en `solicitudes`; el select se llena desde la BD.

**Panel (`/admin/`).** Inicio (resumen del negocio: solicitudes de este mes comparadas con el anterior, de la semana, pendientes y totales; gráfica por semana o por mes; lo más solicitado y el estado de las solicitudes; se calcula con la tabla `solicitudes` en `includes/dashboard.php`), Carrusel, Servicios y productos, Página de inicio (cifras y «¿Por qué
elegirnos?»), Testimonios, Preguntas frecuentes, Nosotros (imágenes, eslogan, misión, visión, valores, historia),
Secciones extra (bloques libres con imagen en Inicio o Nosotros), Ajustes del sitio (nombre, logo, contacto con dirección
y horario, WhatsApp, redes sociales, textos, footer y colores), Solicitudes (filtro, búsqueda, detalle, estado,
eliminar, CSV) y Usuarios (solo administradores). Todo por fetch/JSON, con vista previa de imágenes y avisos.
Las listas nuevas (cifras, puntos, testimonios, preguntas y secciones) comparten un mismo módulo, `includes/listas.php`:
se agregan, editan, ocultan, reordenan (⠿ o flechas) y eliminan igual que los valores. Si una lista queda vacía o con
todo oculto, su sección no aparece en el sitio.

**Al compartir el enlace.** El título y la descripción salen del nombre de la empresa y del eslogan; la imagen es la del
primer slide (o `img/compartir.png` si no tiene). Sin logo, el ícono de la pestaña es la inicial de la empresa.

**Roles.** `admin`: todo. `editor`: contenido y solicitudes (sin Usuarios).

**Imágenes.** Se guardan como archivos en `uploads/` (en la BD solo la ruta). Al subir: se valida tamaño (3 MB), MIME real
(`finfo`) y extensión (jpg/png/webp); se re-codifica con GD (elimina metadatos y cargas ocultas), se redimensiona
(1600 px carrusel y Nosotros, 800 px tarjetas, 400 px logo), se renombra al azar y se genera una versión `.webp`.
Al reemplazar o eliminar se borran los archivos anteriores.

**Caché.** Cada vez que el admin guarda algo se regenera `cache/content.json`. Las visitas públicas leen ese archivo
(no consultan la BD). Si borras el archivo, se regenera solo.

**Colores.** En *Ajustes* puedes activar un color de acento propio; se aplica mediante `css/tema.php`
(una hoja de estilos externa, sin estilos en línea), que también calcula el tono para el cursor (`--accent2`) y el
fondo suave de los íconos (`--accent-soft`). Si no lo activas se usa la paleta de `css/variables.css`.

---

## 4. Configuración (`.env`)

Se lee, en este orden, de: `ZARMEX_ENV_FILE` (variable de entorno) → `../.zarmex.env` (**una carpeta arriba del sitio,
fuera de `public_html`: lo más seguro**) → `.env` dentro del proyecto (bloqueado por `.htaccess`).

| Variable | Por defecto | Para qué |
|---|---|---|
| `APP_ENV` | `production` | `development` muestra errores; `production` los oculta y los registra en `logs/php-error.log` |
| `DB_HOST` `DB_PORT` | `127.0.0.1` `3306` | Servidor de BD |
| `DB_NAME` `DB_USER` `DB_PASS` | `zarmex` `root` *(vacío)* | Credenciales |
| `APP_TZ` | `America/Mexico_City` | Zona horaria de PHP y MySQL |
| `TRUST_PROXY` | `0` | `1` solo detrás de un proxy/CDN de confianza (para leer la IP real) |
| `APP_BASE` | *(auto)* | Carpeta web si la detección automática fallara, p. ej. `/miempresa` |

El `.env` real **nunca** debe subirse a un repositorio (ya está en `.gitignore`).

---

## 5. Checklist de despliegue en hosting compartido

- [ ] **Base de datos**: crea la BD y el usuario en el panel del hosting (cPanel → *MySQL Databases*) e impórtala con
      phpMyAdmin (`schema.sql`; `seed.sql` solo si quieres los datos de ejemplo).
- [ ] **Credenciales**: crea el archivo de entorno con los datos del hosting (idealmente `../.zarmex.env`) y
      pon `APP_ENV=production`.
- [ ] **Subir por FTP/SFTP** todo el contenido a `public_html` (o a la subcarpeta deseada). **No subas** `.env` de local ni
      el contenido de `cache/`, `logs/` y `uploads/` de tu máquina.
- [ ] **Permisos**: `uploads/*/`, `cache/` y `logs/` escribibles (755; 775 si el hosting lo requiere).
- [ ] **Administrador propio**: crea el tuyo (`php database/crear_admin.php …` por SSH, o cambia desde el panel la
      contraseña del usuario del seed y su correo) y elimina `admin@ejemplo.com`.
- [ ] **HTTPS**: activa el certificado SSL (Let's Encrypt en cPanel) y descomenta el bloque *HTTPS* del `.htaccess`.
      Con HTTPS, la cookie de sesión se marca `Secure` automáticamente y se envía HSTS.
- [ ] **Errores desactivados**: `APP_ENV=production`. Verifica en `/admin/?estado` que todo esté en ✔.
- [ ] **PHP**: versión 8.0+ y extensiones `gd`, `fileinfo`, `pdo_mysql` activas (cPanel → *Select PHP Version*).
- [ ] **Límites de subida**: `.htaccess` y `.user.ini` piden 4 MB; si tu hosting los ignora, sube `upload_max_filesize`/`post_max_size` en su panel.
- [ ] **Bloqueos**: comprueba que estas URLs den **403/404**: `/includes/config.php`, `/database/schema.sql`, `/cache/content.json`, `/.env`.
- [ ] **Pruebas finales**: envía una solicitud de prueba, entra al panel, sube una imagen y revisa el sitio en móvil.
- [ ] **Respaldos periódicos**: exporta la BD desde phpMyAdmin (*Exportar → SQL*) **y** descarga la carpeta `uploads/`
      (las imágenes no están en la BD). Guárdalos fuera del servidor.

---

## 6. Seguridad incluida

Sentencias preparadas (PDO) en todas las consultas · escape de salida en todo lo que se imprime · token CSRF en todos los
POST · sesión con `HttpOnly`, `SameSite=Lax`, `Secure` bajo HTTPS, regeneración de ID al iniciar sesión y caducidad por
inactividad (2 h) · bloqueo de 15 min tras 5 intentos fallidos por IP · cambio obligatorio de contraseña inicial ·
cabeceras `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` y una **CSP estricta** (sin scripts ni estilos en
línea) · `uploads/` sin ejecución de PHP · `includes/`, `database/`, `cache/`, `logs/` y archivos `.env` bloqueados ·
errores sin detalles en producción (log en `logs/php-error.log`) · el CSV exportado neutraliza inyección de fórmulas.

---

## 7. Problemas frecuentes

| Síntoma | Causa y solución |
|---|---|
| Pantalla "Servicio no disponible" | No conecta a la BD. Revisa `.env` y que la base exista e importe `schema.sql`. Con `APP_ENV=development` verás el detalle; si no, mira `logs/php-error.log`. |
| "Carpeta … no tiene permisos de escritura" | Dale permisos a `uploads/*/`, `cache/` y `logs/`. |
| "La imagen supera el tamaño máximo" con un archivo < 3 MB | El hosting limita `upload_max_filesize` a menos de 3 MB: súbelo en su panel. |
| El sitio carga pero sin estilos/imágenes en una subcarpeta | Define `APP_BASE=/subcarpeta` en el `.env`. |
| Se queda en "Cargando…" | `api/content.php` falla: abre `…/api/content.php` en el navegador para ver el error. |
| No puedo entrar al panel | Tras 5 fallos hay 15 min de bloqueo por IP. Para restablecer: `php database/crear_admin.php tu@correo.com`. |

---

## 8. Archivos: qué cambió respecto al sitio estático original

**Reemplazados** (mismo propósito, ahora dinámicos):
`index.html` → `index.php` · `js/store.js`, `carousel.js`, `render.js`, `nav.js`, `form.js`, `admin.js`, `main.js` → un solo `js/app.js`.

**Modificados:**
`css/base.css` (clases nuevas que sustituyen los `style=""`: `.lead`, `.cta`, `.muted`, `.pt48`, `.mt40`, `.mt56`, `.hp`; `picture`) ·
`css/layout.css` (logo con imagen, `.view-home`, `.view-about`, `.home-body`, enlaces del footer) ·
`css/components.css` (se **eliminó** todo el CSS del admin embebido; `picture`, `.grp`, `.price`, `.check`, estados de `.ok`) ·
`css/responsive.css` (se quitaron las reglas del admin viejo).

**Rediseño posterior** (estilo del sitio estático `pruebazarmex`): se reescribieron `css/variables.css`, `base.css`,
`layout.css`, `components.css` y `responsive.css`, `index.php`, `includes/render.php` y partes de `js/app.js`; se
añadieron `js/tema-inicial.js` y `fonts/`.

**Secciones del rediseño** (segunda parte): cifras, «¿Por qué elegirnos?», misión/visión, testimonios, preguntas
frecuentes, redes sociales, botón de WhatsApp y secciones extra, todas editables desde el panel. Tablas nuevas `cifras`,
`puntos`, `testimonios`, `preguntas` y `secciones`, y ajustes nuevos (`eslogan`, `direccion`, `horario`, `whatsapp`,
`whatsapp_msg`, `mision`, `vision`, `red_*`). Archivos nuevos: `includes/listas.php`, `admin/pagina-inicio.php`,
`admin/testimonios.php`, `admin/preguntas.php`, `admin/secciones.php`, `database/actualizacion-secciones.sql`,
`img/compartir.png` y las carpetas `uploads/testimonios/` y `uploads/secciones/`.

**Eliminado:** el diálogo de admin embebido, el PIN `1234`, el enlace «⚙ Admin», `localStorage` y las imágenes en base64.

**Nuevos:** `admin/` (login, logout, cambiar-password, index, carrusel, servicios, nosotros, ajustes, solicitudes,
usuarios, `js/admin.js`) · `api/` (`content.php`, `solicitud.php`) · `includes/` (config, db, helpers, auth, csrf,
upload, content, render, icons, admin_layout) · `css/admin.css`, `css/tema.php` · `database/` (`schema.sql`, `seed.sql`,
`crear_admin.php`) · `uploads/`, `cache/`, `logs/` · `.htaccess` (raíz y por carpeta), `.user.ini`, `.env.example`,
`.gitignore`, `robots.txt`, `README.md`.

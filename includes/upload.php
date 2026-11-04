<?php
/**
 * Subida de imágenes: valida (tamaño, MIME real, extensión), re-codifica con GD
 * (esto además elimina metadatos y cualquier carga maliciosa embebida), redimensiona,
 * nombra al azar y genera una versión WebP. En la BD solo se guarda la ruta.
 */
declare(strict_types=1);

class UploadError extends RuntimeException {}

const IMG_CARPETAS = ['carrusel', 'servicios', 'nosotros', 'ajustes'];

function upload_present(string $field): bool
{
    return isset($_FILES[$field]) && is_array($_FILES[$field]) && (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

/**
 * @param string $folder  carrusel|servicios|nosotros|ajustes
 * @param int    $maxW    ancho máximo (1600 carrusel/nosotros, 800 tarjetas)
 * @param bool   $alpha   conservar transparencia (logo): guarda PNG en lugar de JPG
 * @return string ruta relativa (p. ej. uploads/servicios/ab12….jpg)
 */
function image_save(string $field, string $folder, int $maxW, bool $alpha = false): string
{
    if (!in_array($folder, IMG_CARPETAS, true)) { throw new UploadError('Carpeta de destino no válida.'); }
    if (!extension_loaded('gd')) { throw new UploadError('El servidor no tiene habilitada la extensión GD.'); }
    $f = $_FILES[$field] ?? null;
    if (!$f || !is_array($f)) { throw new UploadError('No se recibió ninguna imagen.'); }

    switch ((int)$f['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE: throw new UploadError('La imagen supera el tamaño máximo permitido (3 MB).');
        case UPLOAD_ERR_NO_FILE:   throw new UploadError('No se recibió ninguna imagen.');
        default:                   throw new UploadError('No se pudo subir la imagen. Inténtalo de nuevo.');
    }
    $tmp = (string)$f['tmp_name'];
    if (!is_uploaded_file($tmp) && !(defined('APP_TESTING') && is_file($tmp))) { throw new UploadError('Archivo no válido.'); }
    if ((int)$f['size'] > MAX_UPLOAD_BYTES || filesize($tmp) > MAX_UPLOAD_BYTES) {
        throw new UploadError('La imagen supera el tamaño máximo permitido (3 MB).');
    }

    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new UploadError('Formato no permitido. Usa JPG, PNG o WebP.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new UploadError('El archivo no es una imagen JPG, PNG o WebP válida.');
    }
    $info = @getimagesize($tmp);
    if (!$info || $info[0] < 1 || $info[1] < 1) { throw new UploadError('No se pudo leer la imagen.'); }
    [$w, $h] = $info;
    if ($w * $h > 24_000_000) { throw new UploadError('La imagen tiene demasiados píxeles (máximo ~24 MP).'); }

    @ini_set('memory_limit', '256M');
    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($tmp),
        'image/png'  => @imagecreatefrompng($tmp),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
    };
    if (!$src) { throw new UploadError('No se pudo procesar la imagen (¿está dañada?).'); }

    // Corrige la orientación de fotos de celular (EXIF)
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $ex = @exif_read_data($tmp);
        $o = (int)($ex['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($angle !== 0 && ($rot = imagerotate($src, $angle, 0))) {
            $src = $rot;
            $w = imagesx($src);
            $h = imagesy($src);
        }
    }

    $nw = min($w, $maxW);
    $nh = max(1, (int)round($h * $nw / $w));
    $dst = imagecreatetruecolor($nw, $nh);
    if ($alpha) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    } else {
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // aplana transparencias sobre blanco
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($src);

    $dir = UPLOAD_PATH . '/' . $folder;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { throw new UploadError('No se pudo crear la carpeta de imágenes.'); }
    if (!is_writable($dir)) { throw new UploadError('La carpeta uploads/' . $folder . ' no tiene permisos de escritura.'); }

    $name = bin2hex(random_bytes(16));
    $main = $alpha ? "$dir/$name.png" : "$dir/$name.jpg";
    if ($alpha) {
        $ok = imagepng($dst, $main, 8);
    } else {
        imageinterlace($dst, true);
        $ok = imagejpeg($dst, $main, 82);
    }
    if (!$ok) { imagedestroy($dst); throw new UploadError('No se pudo guardar la imagen.'); }
    if (function_exists('imagewebp')) { @imagewebp($dst, "$dir/$name.webp", 80); }
    imagedestroy($dst);
    @chmod($main, 0644);
    @chmod("$dir/$name.webp", 0644);

    return 'uploads/' . $folder . '/' . $name . ($alpha ? '.png' : '.jpg');
}

/** Borra la imagen y su .webp hermano. Solo actúa dentro de /uploads. */
function image_delete(?string $rel): void
{
    if (!$rel || !preg_match('~^uploads/(' . implode('|', IMG_CARPETAS) . ')/[a-f0-9]{32}\.(jpg|png|webp)$~', $rel)) { return; }
    $full = ROOT_PATH . '/' . $rel;
    if (is_file($full)) { @unlink($full); }
    $webp = preg_replace('/\.(jpg|png)$/', '.webp', $full);
    if ($webp && $webp !== $full && is_file($webp)) { @unlink($webp); }
}

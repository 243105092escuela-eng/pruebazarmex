<?php
/** Íconos disponibles para los "valores" (trazos SVG 24x24, sin recursos externos). */
declare(strict_types=1);

function valor_iconos(): array
{
    return [
        'corazon'  => ['Corazón',   '<path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10z"/>'],
        'estrella' => ['Estrella',  '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>'],
        'bombilla' => ['Bombilla',  '<path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3z"/>'],
        'escudo'   => ['Escudo',    '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>'],
        'equipo'   => ['Equipo',    '<circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20c0-3.5 2.7-6 6-6s6 2.5 6 6M15 14c3 0 6 2 6 5"/>'],
        'check'    => ['Check',     '<path d="M5 12l5 5L20 7"/>'],
        'rayo'     => ['Rayo',      '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>'],
        'reloj'    => ['Reloj',     '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
        'mundo'    => ['Mundo',     '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18"/>'],
        'medalla'  => ['Medalla',   '<circle cx="12" cy="9" r="5"/><path d="M8.5 13.5L7 21l5-3 5 3-1.5-7.5"/>'],
        'objetivo' => ['Objetivo',  '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>'],
        'hoja'     => ['Hoja',      '<path d="M5 19c0-9 6-14 15-14 0 9-5 15-14 15"/><path d="M5 19l8-8"/>'],
        'lista'    => ['Lista',     '<path d="M4 7h16M4 12h10M4 17h7"/>'],
        'cohete'   => ['Cohete',    '<path d="M5 15c-1.5 1.5-2 5-2 5s3.5-.5 5-2M9 15l-3-3a12 12 0 0 1 11-9c0 4-1.5 8-9 12z"/><circle cx="15" cy="9" r="1.5"/>'],
        'camion'   => ['Camión (envíos)', '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>'],
        'grafica'  => ['Gráfica',   '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>'],
        'dinero'   => ['Dinero',    '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/>'],
        'telefono' => ['Teléfono',  '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>'],
    ];
}

/** Redes sociales: clave del ajuste => [nombre visible, trazos SVG 24x24, usa contorno en vez de relleno] */
function redes_sociales(): array
{
    return [
        'red_facebook'  => ['Facebook',    '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H7v4h3v8h4v-8h3l1-4h-4V8.5c0-.3.2-.5.5-.5z"/>', false],
        'red_instagram' => ['Instagram',   '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".6"/>', true],
        'red_tiktok'    => ['TikTok',      '<path d="M16.5 3c.4 2.2 1.8 3.7 4 4v3.2a7.6 7.6 0 0 1-4-1.2V15a6 6 0 1 1-6-6h.6v3.3a2.8 2.8 0 1 0 2.2 2.7V3z"/>', false],
        'red_linkedin'  => ['LinkedIn',    '<path d="M4 9h4v12H4zM6 3a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM10 9h4v1.7c.6-1 1.9-2 3.8-2 3.2 0 4.2 2 4.2 5V21h-4v-6.3c0-1.5-.3-2.7-1.8-2.7S14 13.2 14 14.7V21h-4z"/>', false],
        'red_youtube'   => ['YouTube',     '<path fill-rule="evenodd" d="M22 8.2a3 3 0 0 0-2.1-2.1C18 5.6 12 5.6 12 5.6s-6 0-7.9.5A3 3 0 0 0 2 8.2 31 31 0 0 0 1.6 12 31 31 0 0 0 2 15.8a3 3 0 0 0 2.1 2.1c1.9.5 7.9.5 7.9.5s6 0 7.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .4-3.8 31 31 0 0 0-.4-3.8zM10 15V9l5.2 3z"/>', false],
        'red_x'         => ['X (Twitter)', '<path d="M3 3h5l4.2 5.8L17.5 3H21l-7.2 8.2L21.5 21h-5l-4.6-6.3L6.3 21H3l7.6-8.6z"/>', false],
    ];
}

/** Logotipo de WhatsApp (relleno). */
function whatsapp_svg(): string
{
    return '<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3zm0 23.6a10.6 10.6 0 0 1-5.4-1.5l-.4-.2-3.9 1 1-3.8-.2-.4A10.6 10.6 0 1 1 16 26.6zm5.8-7.9c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7 0a8.7 8.7 0 0 1-4.3-3.8c-.3-.6.3-.5.9-1.7a.6.6 0 0 0 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.9.4 3.6 3.6 0 0 0-1.1 2.7 6.3 6.3 0 0 0 1.3 3.3 14.4 14.4 0 0 0 5.5 4.9c2 .9 2.8.9 3.9.8a3.3 3.3 0 0 0 2.1-1.5 2.7 2.7 0 0 0 .2-1.5c-.1-.1-.3-.2-.7-.4z"/></svg>';
}

function valor_icono_svg(string $key): string
{
    $all = valor_iconos();
    $paths = ($all[$key] ?? $all['check'])[1];
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $paths . '</svg>';
}

/** Íconos de la interfaz del sitio público (contacto, tema, menú, carrusel). */
function ui_icon(string $key): string
{
    $paths = [
        'telefono' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
        'correo'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'ubicacion'=> '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'horario'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'objetivo' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'ojo'      => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'luna'     => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
        'sol'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'usuario'  => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'anterior' => '<path d="M15 5l-7 7 7 7"/>',
        'siguiente'=> '<path d="M9 5l7 7-7 7"/>',
    ];
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$key] ?? '') . '</svg>';
}

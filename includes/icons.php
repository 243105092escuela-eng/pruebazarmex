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
    ];
}

function valor_icono_svg(string $key): string
{
    $all = valor_iconos();
    $paths = ($all[$key] ?? $all['check'])[1];
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $paths . '</svg>';
}

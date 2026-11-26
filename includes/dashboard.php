<?php
/**
 * Resumen del negocio para el inicio del panel: cuántas solicitudes llegan (por semana y por mes),
 * qué servicios se piden más y en qué estado están. Todo sale de la tabla `solicitudes`.
 *
 * Las gráficas son SVG dibujados aquí mismo: la CSP del sitio no permite cargar librerías
 * externas ni estilos en línea, y así no se añade nada de JavaScript pesado.
 */
declare(strict_types=1);

const MESES_CORTOS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

/** Etiquetas visibles de cada estado (en el orden en que se muestran). */
function dash_estados(): array
{
    return ['nueva' => 'Nuevas', 'vista' => 'Vistas', 'atendida' => 'Atendidas', 'archivada' => 'Archivadas'];
}

function dash_datos(PDO $pdo): array
{
    $hoy = new DateTimeImmutable('today');
    $iniMes = $hoy->modify('first day of this month');
    $iniMesAnt = $iniMes->modify('-1 month');
    $iniSemana = $hoy->modify('monday this week');
    $desde = $iniMes->modify('-11 months'); // 12 meses, incluido el actual
    $desdeSem = $iniSemana->modify('-11 weeks'); // 12 semanas, incluida la actual
    $desdeQ = min($desde, $desdeSem);

    // Una sola consulta agrupada por día; los totales por semana y mes se arman aquí
    $st = $pdo->prepare('SELECT DATE(creado_en) AS d, COUNT(*) AS n FROM solicitudes WHERE creado_en >= ? GROUP BY DATE(creado_en)');
    $st->execute([$desdeQ->format('Y-m-d')]);
    $porDia = [];
    foreach ($st->fetchAll() as $r) { $porDia[$r['d']] = (int)$r['n']; }

    $meses = [];
    for ($i = 0; $i < 12; $i++) {
        $m = $desde->modify("+$i months");
        $meses[$m->format('Y-m')] = ['label' => MESES_CORTOS[(int)$m->format('n') - 1] . ($m->format('n') === '1' || $i === 0 ? ' ' . $m->format('y') : ''), 'titulo' => MESES_CORTOS[(int)$m->format('n') - 1] . ' ' . $m->format('Y'), 'n' => 0];
    }
    $semanas = [];
    for ($i = 0; $i < 12; $i++) {
        $s = $desdeSem->modify("+$i weeks");
        $semanas[$s->format('Y-m-d')] = ['label' => $s->format('j') . ' ' . MESES_CORTOS[(int)$s->format('n') - 1], 'titulo' => 'Semana del ' . $s->format('j') . ' de ' . MESES_CORTOS[(int)$s->format('n') - 1], 'n' => 0];
    }
    $esteMes = $mesAnt = $estaSemana = 0;
    foreach ($porDia as $d => $n) {
        $f = new DateTimeImmutable($d);
        $km = $f->format('Y-m');
        if (isset($meses[$km])) { $meses[$km]['n'] += $n; }
        $ks = $f->modify('monday this week')->format('Y-m-d');
        if (isset($semanas[$ks])) { $semanas[$ks]['n'] += $n; }
        if ($f >= $iniMes) { $esteMes += $n; }
        elseif ($f >= $iniMesAnt) { $mesAnt += $n; }
        if ($f >= $iniSemana) { $estaSemana += $n; }
    }

    // Servicios más solicitados (últimos 12 meses). Se usa el nombre guardado en la solicitud,
    // así cuentan también las de servicios que después se renombraron o eliminaron.
    // (el alias no puede llamarse «nombre»: esa columna es el nombre del cliente)
    $st = $pdo->prepare("SELECT COALESCE(NULLIF(item_nombre, ''), 'Sin especificar') AS servicio, COUNT(*) AS n
                         FROM solicitudes WHERE creado_en >= ?
                         GROUP BY COALESCE(NULLIF(item_nombre, ''), 'Sin especificar') ORDER BY n DESC, servicio LIMIT 6");
    $st->execute([$desde->format('Y-m-d')]);
    $top = array_map(fn($r) => ['label' => (string)$r['servicio'], 'n' => (int)$r['n']], $st->fetchAll());

    $estados = array_fill_keys(array_keys(dash_estados()), 0);
    foreach ($pdo->query('SELECT estado, COUNT(*) AS n FROM solicitudes GROUP BY estado')->fetchAll() as $r) {
        if (isset($estados[$r['estado']])) { $estados[$r['estado']] = (int)$r['n']; }
    }

    return [
        'esteMes' => $esteMes,
        'mesAnt' => $mesAnt,
        'estaSemana' => $estaSemana,
        'pendientes' => $estados['nueva'] + $estados['vista'],
        'total' => array_sum($estados),
        'meses' => array_values($meses),
        'semanas' => array_values($semanas),
        'top' => $top,
        'estados' => $estados,
    ];
}

/** Texto de comparación con el mes anterior: [texto, clase css]. */
function dash_comparacion(int $actual, int $anterior): array
{
    if ($anterior === 0) {
        return $actual > 0 ? ['Sin solicitudes el mes pasado', 'neutral'] : ['Igual que el mes pasado', 'neutral'];
    }
    $pct = (int)round(($actual - $anterior) * 100 / $anterior);
    if ($pct === 0) { return ['Igual que el mes pasado', 'neutral']; }
    return [($pct > 0 ? '▲ ' : '▼ ') . abs($pct) . '% vs. mes pasado', $pct > 0 ? 'up' : 'down'];
}

/**
 * Gráfica de barras verticales. $items: [['label', 'titulo', 'n'], …]; la última barra
 * (periodo actual) se resalta. Cada barra tiene <title> para ver el número al pasar el mouse.
 */
function dash_barras(array $items, string $aria): string
{
    $W = 640; $H = 230; $top = 22; $bottom = 34; $left = 8; $right = 8;
    $n = count($items);
    $max = max(1, ...array_column($items, 'n'));
    $slot = ($W - $left - $right) / max(1, $n);
    $bw = min(38, $slot * 0.62);
    $alto = $H - $top - $bottom;
    $base = $H - $bottom;
    $svg = '<svg class="chart" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="' . e($aria) . '">';
    // líneas guía (máximo y mitad)
    foreach ([1, 0.5] as $f) {
        $y = round($base - $alto * $f, 1);
        $svg .= '<line class="grid" x1="' . $left . '" x2="' . ($W - $right) . '" y1="' . $y . '" y2="' . $y . '"/>';
    }
    $svg .= '<line class="axis" x1="' . $left . '" x2="' . ($W - $right) . '" y1="' . $base . '" y2="' . $base . '"/>';
    foreach (array_values($items) as $i => $it) {
        $h = $it['n'] > 0 ? max(3, $alto * $it['n'] / $max) : 0;
        $x = $left + $slot * $i + ($slot - $bw) / 2;
        $cx = round($x + $bw / 2, 1);
        $cls = $i === $n - 1 ? 'bar now' : 'bar';
        $svg .= '<g><title>' . e($it['titulo']) . ': ' . $it['n'] . ' solicitud' . ($it['n'] === 1 ? '' : 'es') . '</title>'
              . '<rect class="' . $cls . '" x="' . round($x, 1) . '" y="' . round($base - $h, 1) . '" width="' . round($bw, 1) . '" height="' . round($h, 1) . '" rx="4"/>'
              . ($it['n'] > 0 ? '<text class="val" x="' . $cx . '" y="' . round($base - $h - 6, 1) . '">' . $it['n'] . '</text>' : '')
              // En celular se oculta una etiqueta sí y una no (siempre queda la del periodo actual)
              . '<text class="lbl' . (($n - 1 - $i) % 2 ? ' alt' : '') . '" x="' . $cx . '" y="' . ($H - 12) . '">' . e($it['label']) . '</text></g>';
    }
    return $svg . '</svg>';
}

/**
 * Barras horizontales (ranking o reparto). $items: [['label', 'n', 'cls'?], …].
 * El ancho de cada barra va en un SVG (atributo width), porque la CSP no permite style="".
 */
function dash_ranking(array $items, string $vacio): string
{
    if (!$items || array_sum(array_column($items, 'n')) === 0) { return '<p class="empty">' . e($vacio) . '</p>'; }
    $max = max(1, ...array_column($items, 'n'));
    $h = '<ul class="hbars">';
    foreach ($items as $it) {
        $w = round(100 * $it['n'] / $max, 1);
        $h .= '<li class="' . e($it['cls'] ?? '') . '"><span class="hb-l" title="' . e($it['label']) . '">' . e($it['label']) . '</span>'
            . '<svg class="hb-bar" viewBox="0 0 100 10" preserveAspectRatio="none" aria-hidden="true"><rect class="hb-bg" width="100" height="10"/>'
            . ($it['n'] > 0 ? '<rect class="hb-fill" width="' . max(2, $w) . '" height="10"/>' : '') . '</svg>'
            . '<b>' . $it['n'] . '</b></li>';
    }
    return $h . '</ul>';
}

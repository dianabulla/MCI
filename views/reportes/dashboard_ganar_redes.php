<?php include VIEWS . '/layout/header.php'; ?>
<?php
$anio = (int)($anio ?? date('Y'));
$escala = (string)($escala ?? 'anual');
$periodo = (string)($periodo ?? 'anio');
$redActiva = (string)($red_activa ?? '');
$filtroMinisterio = (string)($filtro_ministerio ?? '');
$filtroLider = (string)($filtro_lider ?? '');
$alcanceGlobalDash = !isset($dashboard_alcance_global) || !empty($dashboard_alcance_global);
$nombreMinisterioAlcance = trim((string)($dashboard_ministerio_nombre ?? ''));
$ministeriosDisp = $ministerios_disponibles ?? [];
$lideresDisp = $lideres_disponibles ?? [];
$reporteRedes = is_array($reporte_redes ?? null) ? $reporte_redes : [];
$redes = is_array($reporteRedes['redes'] ?? null) ? $reporteRedes['redes'] : [];
$jerarquia = is_array($reporteRedes['jerarquia'] ?? null) ? $reporteRedes['jerarquia'] : [];

require_once APP . '/Helpers/DashboardSelector.php';
$dashModuloActivo = 'ganar';

$clavesMetrica = ['gano_iglesia', 'gano_celula', 'total', 'encuentro', 'capacitacion_destino', 'realizan_celula'];
$etiquetasMetrica = [
    'gano_iglesia' => 'Ganó iglesia',
    'gano_celula' => 'Ganó célula',
    'total' => 'Total ganados',
    'encuentro' => 'Universidad de la Vida',
    'capacitacion_destino' => 'Cap. Destino',
    'realizan_celula' => 'Ubicados en célula',
];
$etiquetasCortas = [
    'gano_iglesia' => 'Iglesia',
    'gano_celula' => 'Célula',
    'total' => 'Total',
    'encuentro' => 'UV',
    'capacitacion_destino' => 'Cap. Destino',
    'realizan_celula' => 'Ubicados',
];

$catalogoRedes = [
    'mujeres' => ['titulo' => 'Red Mujeres', 'corto' => 'Mujeres'],
    'hombres' => ['titulo' => 'Red Hombres', 'corto' => 'Hombres'],
    'otros' => ['titulo' => 'Sin red (sin género)', 'corto' => 'Sin género'],
];

$periodos = [
    'anio' => 'Año ' . $anio,
    's1' => '1er semestre',
    's2' => '2do semestre',
];

$qsBase = [
    'url' => 'reportes/dashboard-ganar-redes',
    'anio' => $anio,
    'escala' => $escala,
    'periodo' => $periodo,
];
if ($redActiva !== '') {
    $qsBase['red'] = $redActiva;
}
if ($filtroMinisterio !== '') {
    $qsBase['ministerio'] = $filtroMinisterio;
}
if ($filtroLider !== '') {
    $qsBase['lider'] = $filtroLider;
}

$buildUrl = static function (array $extra) use ($qsBase): string {
    $params = array_merge($qsBase, $extra);
    foreach ($params as $clave => $valor) {
        if ($valor === '' || $valor === null) {
            unset($params[$clave]);
        }
    }

    return PUBLIC_URL . 'index.php?' . http_build_query($params);
};

$urlDashGanar = PUBLIC_URL . 'index.php?url=reportes/dashboard-ganar&anio=' . $anio;
if ($filtroMinisterio !== '') {
    $urlDashGanar .= '&ministerio=' . urlencode($filtroMinisterio);
}
if ($filtroLider !== '') {
    $urlDashGanar .= '&lider=' . urlencode($filtroLider);
}

$metricasDe = static function (array $nodo) use ($clavesMetrica): array {
    $vacias = array_fill_keys($clavesMetrica, 0);
    $rows = is_array($nodo['semestral']['rows'] ?? null) ? $nodo['semestral']['rows'] : [];
    $s1 = is_array($rows[0] ?? null) ? array_merge($vacias, $rows[0]) : $vacias;
    $s2 = is_array($rows[1] ?? null) ? array_merge($vacias, $rows[1]) : $vacias;
    $anioM = is_array($nodo['anual']['totales'] ?? null) ? array_merge($vacias, $nodo['anual']['totales']) : $vacias;

    return ['s1' => $s1, 's2' => $s2, 'anio' => $anioM];
};

$packPeriodo = static function (array $pack, string $periodoActivo) use ($clavesMetrica): array {
    $vacias = array_fill_keys($clavesMetrica, 0);

    return array_merge($vacias, is_array($pack[$periodoActivo] ?? null) ? $pack[$periodoActivo] : $vacias);
};

$contarEquipo = static function (array $nodo): int {
    $n = 0;
    foreach ((array)($nodo['hijos'] ?? []) as $hijo) {
        $hijo = (array)$hijo;
        if ((string)($hijo['tipo'] ?? '') === 'directos' && (int)($hijo['anual']['totales']['total'] ?? 0) <= 0) {
            continue;
        }
        $n++;
        $n += count((array)($hijo['hijos'] ?? []));
    }

    return $n;
};

$contarCelulas = static function (array $nodo) use (&$contarCelulas): int {
    $n = ((string)($nodo['tipo'] ?? '') === 'lider_celula') ? 1 : 0;
    foreach ((array)($nodo['hijos'] ?? []) as $hijo) {
        $n += $contarCelulas((array)$hijo);
    }

    return $n;
};

$filasEquipo = static function (array $nodo): array {
    $filas = [];
    foreach ((array)($nodo['hijos'] ?? []) as $hijo) {
        $hijo = (array)$hijo;
        if ((string)($hijo['tipo'] ?? '') === 'directos' && (int)($hijo['anual']['totales']['total'] ?? 0) <= 0) {
            continue;
        }
        $filas[] = ['nodo' => $hijo, 'nivel' => 1];
        foreach ((array)($hijo['hijos'] ?? []) as $nieto) {
            $filas[] = ['nodo' => (array)$nieto, 'nivel' => 2];
        }
    }

    return $filas;
};

$resumenRed = static function (string $clave) use ($jerarquia, $redes, $metricasDe, $contarCelulas): array {
    $lideres = is_array($jerarquia[$clave]['lideres_12'] ?? null) ? $jerarquia[$clave]['lideres_12'] : [];
    $n12 = 0;
    $nCelulas = 0;
    foreach ($lideres as $nodo) {
        $nodo = (array)$nodo;
        if ((string)($nodo['tipo'] ?? '') === 'lider_12') {
            $n12++;
        }
        $nCelulas += $contarCelulas($nodo);
    }

    return [
        'lideres' => $lideres,
        'n12' => $n12,
        'n_celulas' => $nCelulas,
        'pack' => $metricasDe(is_array($redes[$clave] ?? null) ? $redes[$clave] : []),
    ];
};

$fmt = static function (int $valor): string {
    return $valor === 0 ? '<span class="is-zero">0</span>' : (string)$valor;
};

$vistaHome = $redActiva === '';
$infoRedActiva = $catalogoRedes[$redActiva] ?? null;
?>

<style>
.redes-page { max-width: 1180px; }
.redes-header { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
.redes-header h2 { margin:0; }
.redes-header small { color:#64748b; }
.redes-crumb { margin:0 0 6px; font-size:.82rem; color:#64748b; }
.redes-crumb a { color:#1d4ed8; text-decoration:none; font-weight:600; }
.redes-crumb a:hover { text-decoration:underline; }
.redes-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.redes-pill {
    display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px;
    border:1px solid #d1d5db; background:#fff; color:#334155; font-size:.84rem; font-weight:600; text-decoration:none;
}
.redes-pill.is-active { background:#1e3a8a; border-color:#1e3a8a; color:#fff; }
.redes-help { margin:0 0 16px; font-size:.82rem; color:#64748b; line-height:1.45; }
.dash-filters-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; margin-bottom:16px; }
.dash-filters-form { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
.dash-filters-form .form-group { margin:0; }
.dash-filters-form select { padding:6px 10px; border-radius:8px; border:1px solid #d1d5db; font-size:.88rem; min-width:160px; }
.dash-anio-form { display:flex; gap:6px; align-items:center; }
.dash-anio-form select { padding:4px 10px; border-radius:6px; border:1px solid #d1d5db; }

.redes-home-grid {
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:18px;
}
.redes-hero {
    display:flex; flex-direction:column; gap:10px;
    text-decoration:none; color:inherit;
    background:#fff; border:1px solid #e2e8f0; border-radius:18px;
    padding:22px 22px 18px; box-shadow:0 8px 24px rgba(15,23,42,.06);
    min-height:260px; transition:transform .15s ease, box-shadow .15s ease;
}
.redes-hero:hover { transform:translateY(-2px); box-shadow:0 14px 30px rgba(15,23,42,.1); }
.redes-hero--mujeres { background:linear-gradient(180deg, #fff 0%, #fdf2f8 100%); border-color:#f9a8d4; }
.redes-hero--hombres { background:linear-gradient(180deg, #fff 0%, #eef2ff 100%); border-color:#c7d2fe; }
.redes-hero--otros { grid-column:1 / -1; min-height:auto; background:#f8fafc; }
.redes-hero-kicker { font-size:.78rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#64748b; }
.redes-hero--mujeres .redes-hero-kicker { color:#9d174d; }
.redes-hero--hombres .redes-hero-kicker { color:#3730a3; }
.redes-hero h3 { margin:0; font-size:1.7rem; color:#0f172a; }
.redes-hero-total { font-size:3rem; font-weight:800; line-height:1; color:#0f172a; }
.redes-hero-sub { color:#64748b; font-size:.88rem; margin-top:-4px; }
.redes-hero-stats { display:flex; gap:16px; flex-wrap:wrap; color:#475569; font-size:.86rem; font-weight:600; }
.redes-hero-split {
    display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:4px;
}
.redes-hero-split div {
    background:rgba(255,255,255,.8); border:1px solid #e2e8f0; border-radius:12px; padding:10px 12px;
}
.redes-hero-split span { display:block; font-size:.75rem; color:#64748b; font-weight:600; }
.redes-hero-split b { font-size:1.25rem; color:#0f172a; }
.redes-hero-cta {
    margin-top:auto; align-self:flex-start;
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 14px; border-radius:999px; font-weight:700; font-size:.86rem;
}
.redes-hero--mujeres .redes-hero-cta { background:#9d174d; color:#fff; }
.redes-hero--hombres .redes-hero-cta { background:#3730a3; color:#fff; }
.redes-hero--otros .redes-hero-cta { background:#334155; color:#fff; }

.redes-kpi-grid {
    display:grid;
    grid-template-columns:repeat(6, minmax(0, 1fr));
    gap:10px; margin-bottom:16px;
}
.redes-kpi {
    background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 12px 10px;
}
.redes-kpi span { display:block; font-size:.72rem; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:.03em; }
.redes-kpi b { display:block; margin-top:4px; font-size:1.45rem; color:#0f172a; }
.redes-kpi.is-total { background:#0f172a; border-color:#0f172a; }
.redes-kpi.is-total span, .redes-kpi.is-total b { color:#fff; }

.redes-toolbar { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin:0 0 14px; }
.redes-periodos { display:flex; gap:6px; flex-wrap:wrap; }

.redes-12 {
    background:#fff; border:1px solid #e2e8f0; border-radius:14px;
    margin-bottom:14px; box-shadow:0 1px 4px rgba(15,23,42,.05); overflow:hidden;
}
.redes-12 > summary {
    list-style:none; cursor:pointer;
    display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;
    padding:14px 16px; background:#f8fafc;
}
.redes-12 > summary::-webkit-details-marker { display:none; }
.redes-12-name { font-size:1.05rem; font-weight:800; color:#0f172a; }
.redes-12-meta { margin-top:2px; font-size:.8rem; color:#64748b; }
.redes-12-total { font-size:1.4rem; font-weight:800; color:#1e3a8a; }
.redes-12-total small { display:block; font-size:.7rem; color:#64748b; font-weight:700; text-transform:uppercase; }
.redes-12-body { padding:0 16px 16px; }
.redes-mini-kpis { display:grid; grid-template-columns:repeat(6, minmax(0, 1fr)); gap:8px; margin:12px 0; }
.redes-mini-kpis div {
    border:1px solid #e2e8f0; border-radius:10px; padding:8px 10px; background:#fff;
}
.redes-mini-kpis span { display:block; font-size:.68rem; color:#64748b; font-weight:700; }
.redes-mini-kpis b { font-size:1.05rem; color:#0f172a; }
.redes-table-wrap { overflow-x:auto; border:1px solid #e2e8f0; border-radius:10px; }
.dash-min-table { width:100%; border-collapse:collapse; font-size:.86rem; }
.dash-min-table th { background:#f8fafc; padding:8px 10px; text-align:left; font-size:.74rem; color:#475569; font-weight:700; border-bottom:1px solid #e2e8f0; }
.dash-min-table th.num, .dash-min-table .num { text-align:right; font-variant-numeric:tabular-nums; }
.dash-min-table td { padding:8px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.dash-min-table tr:last-child td { border-bottom:none; }
.dash-min-table .is-total { font-weight:700; }
.dash-min-table .is-zero, .is-zero { color:#94a3b8; font-weight:400; }
.dash-min-table .col-num { width:36px; color:#94a3b8; text-align:center; font-weight:600; }
.redes-equipo td:nth-child(2) { padding-left:22px; }
.redes-nieto td:nth-child(2) { padding-left:38px; }
.redes-nombre { font-weight:600; color:#0f172a; }
.redes-nombre-meta { margin:2px 0 0; font-size:.75rem; color:#64748b; font-weight:400; }
.redes-vacio { padding:28px; text-align:center; color:#94a3b8; background:#fff; border:1px dashed #e2e8f0; border-radius:12px; }
.redes-equipo-vacio { padding:8px 2px 0; font-size:.82rem; color:#94a3b8; }

@media (max-width:860px) {
    .redes-home-grid, .redes-kpi-grid, .redes-mini-kpis { grid-template-columns:1fr 1fr; }
    .redes-hero { min-height:0; }
}
@media (max-width:560px) {
    .redes-home-grid { grid-template-columns:1fr; }
}
@media print {
    .redes-actions, .dash-filters-card { display:none !important; }
    .redes-12 { break-inside:avoid; }
    .redes-12[open] { }
}
</style>

<div class="redes-page">
    <div class="redes-header">
        <div>
            <?php if ($vistaHome): ?>
                <h2>Reporte Ganar por redes · <?= $anio ?></h2>
                <small>
                    Elige una red para ver el detalle por líder de 12
                    <?php if (!$alcanceGlobalDash && $nombreMinisterioAlcance !== ''): ?>
                        · <?= htmlspecialchars($nombreMinisterioAlcance) ?>
                    <?php endif; ?>
                </small>
            <?php else: ?>
                <p class="redes-crumb">
                    <a href="<?= htmlspecialchars($buildUrl(['red' => ''])) ?>">Redes</a>
                    · <?= htmlspecialchars((string)($infoRedActiva['titulo'] ?? '')) ?>
                </p>
                <h2><?= htmlspecialchars((string)($infoRedActiva['titulo'] ?? 'Red')) ?> · <?= $anio ?></h2>
                <small>Embudo del periodo: ganó iglesia + célula, y de esos cuántos se inscribieron y se ubicaron</small>
            <?php endif; ?>
        </div>
        <div class="redes-actions">
            <?php
            DashboardSelector::incluirPartial([
                'activo' => $dashModuloActivo,
                'params' => [
                    'anio' => $anio,
                    'ministerio' => $filtroMinisterio,
                    'lider' => $filtroLider,
                ],
            ]);
            ?>
            <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="dash-anio-form">
                <input type="hidden" name="url" value="reportes/dashboard-ganar-redes">
                <input type="hidden" name="escala" value="<?= htmlspecialchars($escala) ?>">
                <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
                <?php if ($redActiva !== ''): ?>
                    <input type="hidden" name="red" value="<?= htmlspecialchars($redActiva) ?>">
                <?php endif; ?>
                <?php if ($filtroMinisterio !== ''): ?>
                    <input type="hidden" name="ministerio" value="<?= htmlspecialchars($filtroMinisterio) ?>">
                <?php endif; ?>
                <?php if ($filtroLider !== ''): ?>
                    <input type="hidden" name="lider" value="<?= htmlspecialchars($filtroLider) ?>">
                <?php endif; ?>
                <label for="anio_redes" style="font-size:.84rem;color:#475569;">Año:</label>
                <select id="anio_redes" name="anio" onchange="this.form.submit()">
                    <?php for ($y = (int)date('Y'); $y >= 2023; $y--): ?>
                        <option value="<?= $y ?>" <?= $y === $anio ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
            <a class="redes-pill" href="<?= htmlspecialchars($urlDashGanar) ?>">Volver al dashboard</a>
            <a class="redes-pill" href="<?= htmlspecialchars($buildUrl(['exportar' => 1])) ?>">Excel</a>
        </div>
    </div>

    <div class="dash-filters-card">
        <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="dash-filters-form">
            <input type="hidden" name="url" value="reportes/dashboard-ganar-redes">
            <input type="hidden" name="anio" value="<?= $anio ?>">
            <input type="hidden" name="escala" value="<?= htmlspecialchars($escala) ?>">
            <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
            <?php if ($redActiva !== ''): ?>
                <input type="hidden" name="red" value="<?= htmlspecialchars($redActiva) ?>">
            <?php endif; ?>
            <div class="form-group">
                <label style="font-size:.8rem;color:#475569;display:block;margin-bottom:4px;">Ministerio</label>
                <?php if (!$alcanceGlobalDash): ?>
                    <input type="hidden" name="ministerio" value="<?= htmlspecialchars($filtroMinisterio) ?>">
                    <div style="padding:7px 10px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;font-size:.88rem;font-weight:600;color:#0f172a;min-width:160px;">
                        <?= htmlspecialchars($nombreMinisterioAlcance !== '' ? $nombreMinisterioAlcance : 'Tu ministerio') ?>
                    </div>
                <?php else: ?>
                <select name="ministerio" onchange="this.form.submit()">
                    <option value="">Todos (general)</option>
                    <?php foreach ($ministeriosDisp as $min): ?>
                        <option value="<?= (int)($min['Id_Ministerio'] ?? 0) ?>"
                            <?= (string)($min['Id_Ministerio'] ?? '') === $filtroMinisterio ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($min['Nombre_Ministerio'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label style="font-size:.8rem;color:#475569;display:block;margin-bottom:4px;">Líder</label>
                <select name="lider" onchange="this.form.submit()">
                    <option value="">Todos los líderes</option>
                    <?php foreach ($lideresDisp as $lid): ?>
                        <option value="<?= (int)($lid['Id_Persona'] ?? 0) ?>"
                            <?= (string)($lid['Id_Persona'] ?? '') === $filtroLider ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($lid['Nombre_Completo'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($vistaHome): ?>
        <p class="redes-help">
            Embudo de <strong>personas nuevas</strong> del periodo: cuántas ganó en iglesia, cuántas en célula y el total.
            De ese total, cuántas se inscribieron a Encuentro / Universidad de la Vida, cuántas a Capacitación Destino
            y cuántas ya quedaron ubicadas en célula. Encuentro y Cap. Destino salen solo de inscritos reales, no del checklist.
        </p>
        <div class="redes-home-grid">
            <?php foreach (['mujeres', 'hombres'] as $claveHero):
                $info = $catalogoRedes[$claveHero];
                $resumen = $resumenRed($claveHero);
                $packAnio = $packPeriodo($resumen['pack'], 'anio');
                $packS1 = $packPeriodo($resumen['pack'], 's1');
                $packS2 = $packPeriodo($resumen['pack'], 's2');
            ?>
            <a class="redes-hero redes-hero--<?= htmlspecialchars($claveHero) ?>"
               href="<?= htmlspecialchars($buildUrl(['red' => $claveHero])) ?>">
                <div class="redes-hero-kicker">Red</div>
                <h3><?= htmlspecialchars($info['corto']) ?></h3>
                <div class="redes-hero-total"><?= (int)($packAnio['total'] ?? 0) ?></div>
                <div class="redes-hero-sub">ganados en <?= $anio ?></div>
                <div class="redes-hero-stats">
                    <span><?= (int)$resumen['n12'] ?> líder(es) de 12</span>
                    <span><?= (int)$resumen['n_celulas'] ?> célula(s)</span>
                </div>
                <div class="redes-hero-split">
                    <div>
                        <span>1er semestre</span>
                        <b><?= (int)($packS1['total'] ?? 0) ?></b>
                    </div>
                    <div>
                        <span>2do semestre</span>
                        <b><?= (int)($packS2['total'] ?? 0) ?></b>
                    </div>
                </div>
                <span class="redes-hero-cta">Ver detalle</span>
            </a>
            <?php endforeach; ?>

            <?php
            $resumenOtros = $resumenRed('otros');
            if (!empty($resumenOtros['lideres'])):
                $packOtros = $packPeriodo($resumenOtros['pack'], 'anio');
            ?>
            <a class="redes-hero redes-hero--otros" href="<?= htmlspecialchars($buildUrl(['red' => 'otros'])) ?>">
                <div class="redes-hero-kicker">Otras personas</div>
                <h3>Sin género asignado</h3>
                <div class="redes-hero-total"><?= (int)($packOtros['total'] ?? 0) ?></div>
                <div class="redes-hero-sub"><?= count($resumenOtros['lideres']) ?> grupo(s) · ver detalle</div>
                <span class="redes-hero-cta">Ver detalle</span>
            </a>
            <?php endif; ?>
        </div>
    <?php else:
        $resumenActivo = $resumenRed($redActiva);
        $packRed = $packPeriodo($resumenActivo['pack'], $periodo);
        $lideres12 = $resumenActivo['lideres'];
    ?>
        <div class="redes-toolbar">
            <nav class="redes-periodos" aria-label="Periodo del reporte">
                <?php foreach ($periodos as $clavePeriodo => $labelPeriodo): ?>
                    <a class="redes-pill<?= $periodo === $clavePeriodo ? ' is-active' : '' ?>"
                       href="<?= htmlspecialchars($buildUrl(['periodo' => $clavePeriodo, 'red' => $redActiva])) ?>">
                        <?= htmlspecialchars($labelPeriodo) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <a class="redes-pill" href="<?= htmlspecialchars($buildUrl(['red' => ''])) ?>">Cambiar de red</a>
        </div>

        <p class="redes-help">
            De los ganados del periodo (iglesia + célula), Encuentro/UV y Cap. Destino cuentan solo si hay inscripción real.
            Ubicados en célula = de esos mismos ganados, cuántos ya tienen célula asignada.
        </p>

        <div class="redes-kpi-grid">
            <?php foreach ($etiquetasCortas as $claveM => $labelM): ?>
                <div class="redes-kpi<?= $claveM === 'total' ? ' is-total' : '' ?>">
                    <span><?= htmlspecialchars($labelM) ?></span>
                    <b><?= (int)($packRed[$claveM] ?? 0) ?></b>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($lideres12 === []): ?>
            <div class="redes-vacio">No hay líderes de 12 o de célula para mostrar en esta red con los filtros actuales.</div>
        <?php else: ?>
            <?php foreach ($lideres12 as $idx12 => $nodo12):
                $nodo12 = (array)$nodo12;
                $pack12Full = $metricasDe($nodo12);
                $pack12 = $packPeriodo($pack12Full, $periodo);
                $nEquipo = $contarEquipo($nodo12);
                $nCelulas12 = $contarCelulas($nodo12);
                $equipo = $filasEquipo($nodo12);
                $ministerio12 = trim((string)($nodo12['ministerio'] ?? ''));
            ?>
            <details class="redes-12" <?= $idx12 === 0 ? 'open' : '' ?>>
                <summary>
                    <div>
                        <div class="redes-12-name"><?= htmlspecialchars((string)($nodo12['nombre'] ?? '')) ?></div>
                        <div class="redes-12-meta">
                            <?= htmlspecialchars((string)($nodo12['tipo_label'] ?? 'Líder de 12')) ?>
                            <?php if ($ministerio12 !== '' && $ministerio12 !== 'Sin ministerio'): ?>
                                · <?= htmlspecialchars($ministerio12) ?>
                            <?php endif; ?>
                            · <?= $nEquipo ?> en equipo
                            <?php if ($nCelulas12 > 0): ?>
                                · <?= $nCelulas12 ?> célula(s)
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="redes-12-total">
                        <?= (int)($pack12['total'] ?? 0) ?>
                        <small>ganados</small>
                    </div>
                </summary>
                <div class="redes-12-body">
                    <div class="redes-mini-kpis">
                        <?php foreach ($etiquetasCortas as $claveM => $labelM): ?>
                            <div>
                                <span><?= htmlspecialchars($labelM) ?></span>
                                <b><?= $fmt((int)($pack12[$claveM] ?? 0)) ?></b>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($equipo === []): ?>
                        <p class="redes-equipo-vacio">Este líder de 12 no tiene equipo cargado en el reporte.</p>
                    <?php else: ?>
                    <div class="redes-table-wrap">
                        <table class="dash-min-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Líder</th>
                                    <th>Tipo</th>
                                    <?php foreach ($etiquetasCortas as $labelCol): ?>
                                        <th class="num"><?= htmlspecialchars($labelCol) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($equipo as $iFila => $filaEq):
                                $miembro = (array)($filaEq['nodo'] ?? []);
                                $nivel = (int)($filaEq['nivel'] ?? 1);
                                $packMiembro = $packPeriodo($metricasDe($miembro), $periodo);
                                $minis = trim((string)($miembro['ministerio'] ?? ''));
                                $clsFila = $nivel >= 2 ? 'redes-nieto' : 'redes-equipo';
                            ?>
                                <tr class="<?= $clsFila ?>">
                                    <td class="col-num"><?= $iFila + 1 ?></td>
                                    <td>
                                        <span class="redes-nombre"><?= htmlspecialchars((string)($miembro['nombre'] ?? '')) ?></span>
                                        <?php if ($minis !== '' && $minis !== 'Sin ministerio' && $minis !== $ministerio12): ?>
                                            <div class="redes-nombre-meta"><?= htmlspecialchars($minis) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars((string)($miembro['tipo_label'] ?? '')) ?></td>
                                    <?php foreach ($clavesMetrica as $claveM): ?>
                                        <td class="num<?= $claveM === 'total' ? ' is-total' : '' ?>"><?= $fmt((int)($packMiembro[$claveM] ?? 0)) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </details>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>

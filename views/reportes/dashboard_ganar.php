<?php include VIEWS . '/layout/header.php'; ?>
<?php

// ── Variables del controlador ────────────────────────────────────────────────
$anio               = (int)($anio ?? date('Y'));
$filtroMinisterio   = (string)($filtro_ministerio ?? '');
$filtroLider        = (string)($filtro_lider ?? '');
$mesesLabels        = $meses_labels ?? [];
$gananciasMensuales = $ganancias_mensuales ?? [];
$porMinisterio      = $por_ministerio ?? [];
$porEdades          = $por_edades ?? [];
$totalS1            = (int)($total_s1 ?? 0);
$totalS2            = (int)($total_s2 ?? 0);
$totalAnual         = (int)($total_anual ?? 0);
$semaforoS1         = (string)($semaforo_s1 ?? 'rojo');
$semaforoS2         = (string)($semaforo_s2 ?? 'rojo');
$semaforoAnual      = (string)($semaforo_anual ?? 'rojo');
$ministeriosConMeta = $ministerios_con_meta ?? [];
$cumplimientoMetas  = $cumplimiento_metas ?? [];
$ministeriosDisp    = $ministerios_disponibles ?? [];
$lideresDisp        = $lideres_disponibles ?? [];

// Construir URL base del dashboard conservando filtros
$baseUrl = PUBLIC_URL . 'index.php?url=reportes/dashboard-ganar&anio=' . $anio;
if ($filtroMinisterio !== '') {
    $baseUrl .= '&ministerio=' . urlencode($filtroMinisterio);
}
if ($filtroLider !== '') {
    $baseUrl .= '&lider=' . urlencode($filtroLider);
}

// JSON para gráficas
$mesesLabelsJson  = json_encode(array_values($mesesLabels), JSON_UNESCAPED_UNICODE);
$ganarCelulaJson  = json_encode(array_values(array_column($gananciasMensuales, 'celula')));
$ganarIglesiaJson = json_encode(array_values(array_column($gananciasMensuales, 'iglesia')));
$ganarTotalJson   = json_encode(array_values(array_column($gananciasMensuales, 'total')));
$ministerioNombresJson = json_encode(array_column($porMinisterio, 'nombre'), JSON_UNESCAPED_UNICODE);
$ministerioTotalesJson = json_encode(array_column($porMinisterio, 'total'));
$edadesJson        = json_encode(array_values($porEdades));

$mesActual = (int)date('n');
$mesActualLabel = $mesesLabels[$mesActual] ?? date('M');
$totalMesActual = (int)($gananciasMensuales[$mesActual]['total'] ?? 0);
$semaforoMes    = $totalMesActual >= 121 ? 'verde' : ($totalMesActual >= 61 ? 'amarillo' : 'rojo');

// Semáforo helper - retorna clases CSS y texto
$semaforoInfo = [
    'verde'   => ['bg' => '#22c55e', 'label' => 'Excelente',  'icon' => '🟢'],
    'amarillo'=> ['bg' => '#eab308', 'label' => 'En proceso', 'icon' => '🟡'],
    'rojo'    => ['bg' => '#ef4444', 'label' => 'Atención',   'icon' => '🔴'],
];
?>

<style>
/* ── Dashboard Ganar ────────────────────────────────────────────────────── */
.dash-header { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:18px; }
.dash-header h2 { margin:0; }
.dash-header-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }

/* Selector de año */
.dash-anio-form { display:flex; gap:6px; align-items:center; }
.dash-anio-form select { padding:4px 10px; border-radius:6px; border:1px solid #d1d5db; font-size:0.93rem; }

/* Semáforo principal */
.semaforo-wrap { display:flex; flex-direction:column; align-items:center; gap:8px; }
.semaforo-luz {
    width:90px; height:90px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:2rem; font-weight:800; color:#fff;
    box-shadow:0 4px 18px rgba(0,0,0,0.18);
    transition:transform .2s;
}
.semaforo-luz:hover { transform:scale(1.06); }
.semaforo-verde   { background:#22c55e; box-shadow:0 4px 24px rgba(34,197,94,.4); }
.semaforo-amarillo{ background:#eab308; box-shadow:0 4px 24px rgba(234,179,8,.4); }
.semaforo-rojo    { background:#ef4444; box-shadow:0 4px 24px rgba(239,68,68,.4); }
.semaforo-etiqueta{ font-size:.78rem; font-weight:600; color:#64748b; text-align:center; margin-top:2px; }

/* KPI grid */
.dash-kpi-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));
    gap:14px; margin-bottom:22px;
}
.dash-kpi-card {
    background:#fff; border:1px solid #e2e8f0;
    border-radius:12px; padding:18px 16px;
    display:flex; flex-direction:column; gap:6px;
    box-shadow:0 1px 4px rgba(0,0,0,.06);
    position:relative; overflow:hidden;
}
.dash-kpi-card::before {
    content:''; position:absolute; top:0; left:0; right:0;
    height:4px;
}
.dash-kpi-card.kpi-verde::before   { background:#22c55e; }
.dash-kpi-card.kpi-amarillo::before{ background:#eab308; }
.dash-kpi-card.kpi-rojo::before    { background:#ef4444; }
.dash-kpi-label { font-size:.82rem; color:#64748b; font-weight:600; }
.dash-kpi-value { font-size:2.2rem; font-weight:800; line-height:1; color:#1e293b; }
.dash-kpi-sub   { font-size:.78rem; color:#94a3b8; }
.dash-kpi-badge {
    display:inline-block; padding:2px 10px; border-radius:20px;
    font-size:.73rem; font-weight:700; color:#fff; margin-top:2px;
    align-self:flex-start;
}
.badge-verde    { background:#22c55e; }
.badge-amarillo { background:#eab308; }
.badge-rojo     { background:#ef4444; }

/* Semáforo leyenda */
.semaforo-leyenda {
    display:flex; gap:12px; flex-wrap:wrap;
    padding:10px 14px; background:#f8fafc;
    border-radius:8px; border:1px solid #e2e8f0;
    margin-bottom:18px;
}
.semaforo-leyenda-item { display:flex; align-items:center; gap:6px; font-size:.82rem; font-weight:600; color:#374151; }
.leyenda-dot { width:14px; height:14px; border-radius:50%; flex-shrink:0; }

/* Grid charts 2 columnas */
.dash-charts-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(340px, 1fr));
    gap:16px; margin-bottom:22px;
}
.dash-chart-card {
    background:#fff; border:1px solid #e2e8f0;
    border-radius:12px; padding:18px 16px;
    box-shadow:0 1px 4px rgba(0,0,0,.06);
}
.dash-chart-card h4 { margin:0 0 12px 0; font-size:.93rem; color:#374151; }

/* Tabla ministerios con semáforo */
.dash-min-table { width:100%; border-collapse:collapse; font-size:.88rem; }
.dash-min-table th { background:#f1f5f9; padding:8px 10px; text-align:left; font-size:.78rem; color:#475569; font-weight:700; border-bottom:1px solid #e2e8f0; }
.dash-min-table td { padding:8px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.dash-min-table tr:last-child td { border-bottom:none; }
.dash-min-table .progress-bar-wrap { background:#f1f5f9; border-radius:20px; height:10px; overflow:hidden; min-width:80px; }
.dash-min-table .progress-bar-fill { height:100%; border-radius:20px; transition:width .4s; }
.progress-verde   { background:#22c55e; }
.progress-amarillo{ background:#eab308; }
.progress-rojo    { background:#ef4444; }

/* Indicadores semestrales lado a lado */
.dash-semestre-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:22px; }
@media(max-width:700px) { .dash-semestre-grid { grid-template-columns:1fr; } .dash-charts-grid { grid-template-columns:1fr; } }

/* Filtros */
.dash-filters-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; margin-bottom:18px; box-shadow:0 1px 4px rgba(0,0,0,.05); }
.dash-filters-form { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
.dash-filters-form .form-group { margin:0; }
.dash-filters-form select { padding:6px 10px; border-radius:8px; border:1px solid #d1d5db; font-size:.88rem; min-width:160px; }
</style>

<div class="dash-header">
    <div>
        <h2>Dashboard · Ganar</h2>
        <small style="color:#64748b;">Indicadores y gráficas con semáforo · <?= $anio ?></small>
    </div>
    <div class="dash-header-actions">
        <a href="<?= PUBLIC_URL ?>index.php?url=reportes" class="btn btn-secondary" style="font-size:.84rem;">← Volver a reportes</a>
        <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="dash-anio-form">
            <input type="hidden" name="url" value="reportes/dashboard-ganar">
            <?php if ($filtroMinisterio !== ''): ?>
                <input type="hidden" name="ministerio" value="<?= htmlspecialchars($filtroMinisterio) ?>">
            <?php endif; ?>
            <?php if ($filtroLider !== ''): ?>
                <input type="hidden" name="lider" value="<?= htmlspecialchars($filtroLider) ?>">
            <?php endif; ?>
            <label for="anio_select" style="font-size:.84rem; color:#475569; white-space:nowrap;">Año:</label>
            <select id="anio_select" name="anio" onchange="this.form.submit()">
                <?php for ($y = (int)date('Y'); $y >= 2023; $y--): ?>
                    <option value="<?= $y ?>" <?= $y === $anio ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>
</div>

<!-- Filtros ministerio / líder -->
<div class="dash-filters-card">
    <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="dash-filters-form">
        <input type="hidden" name="url" value="reportes/dashboard-ganar">
        <input type="hidden" name="anio" value="<?= $anio ?>">
        <div class="form-group">
            <label style="font-size:.8rem;color:#475569;display:block;margin-bottom:4px;">Ministerio</label>
            <select name="ministerio" onchange="this.form.submit()">
                <option value="">Todos los ministerios</option>
                <?php foreach ($ministeriosDisp as $min): ?>
                    <option value="<?= (int)($min['Id_Ministerio'] ?? 0) ?>"
                        <?= (string)($min['Id_Ministerio'] ?? '') === $filtroMinisterio ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)($min['Nombre_Ministerio'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
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

<!-- Leyenda semáforo -->
<div class="semaforo-leyenda">
    <strong style="font-size:.82rem; color:#374151; margin-right:4px;">Semáforo:</strong>
    <div class="semaforo-leyenda-item">
        <div class="leyenda-dot" style="background:#22c55e;"></div>
        <span>Verde: 121 – 180</span>
    </div>
    <div class="semaforo-leyenda-item">
        <div class="leyenda-dot" style="background:#eab308;"></div>
        <span>Amarillo: 61 – 120</span>
    </div>
    <div class="semaforo-leyenda-item">
        <div class="leyenda-dot" style="background:#ef4444;"></div>
        <span>Rojo: 1 – 60</span>
    </div>
</div>

<!-- ── Indicadores semestrales ─────────────────────────────────────────────── -->
<div class="dash-semestre-grid">
    <?php
    $semestresCard = [
        ['label' => 'Semestre 1', 'sub' => 'Ene – Jun ' . $anio, 'total' => $totalS1, 'sem' => $semaforoS1],
        ['label' => 'Semestre 2', 'sub' => 'Jul – Dic ' . $anio, 'total' => $totalS2, 'sem' => $semaforoS2],
        ['label' => 'Total Anual ' . $anio, 'sub' => 'Ene – Dic', 'total' => $totalAnual, 'sem' => $semaforoAnual],
    ];
    foreach ($semestresCard as $card):
        $info = $semaforoInfo[$card['sem']] ?? $semaforoInfo['rojo'];
    ?>
    <div class="card report-card" style="padding:18px; text-align:center; display:flex; flex-direction:column; align-items:center; gap:12px;">
        <div style="font-size:.88rem; font-weight:700; color:#374151;"><?= htmlspecialchars($card['label']) ?></div>
        <div style="font-size:.78rem; color:#94a3b8;"><?= htmlspecialchars($card['sub']) ?></div>
        <div class="semaforo-luz semaforo-<?= $card['sem'] ?>"><?= $card['total'] ?></div>
        <div class="semaforo-etiqueta">
            <?= $info['icon'] ?> <?= htmlspecialchars($info['label']) ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── KPI Cards ───────────────────────────────────────────────────────────── -->
<?php
$totalCelula  = array_sum(array_column($gananciasMensuales, 'celula'));
$totalIglesia = array_sum(array_column($gananciasMensuales, 'iglesia'));
$metaTotal    = (int)($cumplimientoMetas['totales']['meta'] ?? 0);
$pctMeta      = $metaTotal > 0 ? (int)round(($totalAnual / $metaTotal) * 100) : 0;
$semMeta      = $pctMeta >= 75 ? 'verde' : ($pctMeta >= 40 ? 'amarillo' : 'rojo');
?>
<div class="dash-kpi-grid">
    <div class="dash-kpi-card kpi-<?= $semaforoMes ?>">
        <div class="dash-kpi-label">Ganados este mes (<?= htmlspecialchars($mesActualLabel) ?>)</div>
        <div class="dash-kpi-value"><?= $totalMesActual ?></div>
        <span class="dash-kpi-badge badge-<?= $semaforoMes ?>"><?= $semaforoInfo[$semaforoMes]['label'] ?? '' ?></span>
    </div>
    <div class="dash-kpi-card kpi-<?= $semaforoAnual ?>">
        <div class="dash-kpi-label">Total ganados <?= $anio ?></div>
        <div class="dash-kpi-value"><?= $totalAnual ?></div>
        <div class="dash-kpi-sub">S1: <?= $totalS1 ?> · S2: <?= $totalS2 ?></div>
        <span class="dash-kpi-badge badge-<?= $semaforoAnual ?>"><?= $semaforoInfo[$semaforoAnual]['label'] ?? '' ?></span>
    </div>
    <div class="dash-kpi-card kpi-verde">
        <div class="dash-kpi-label">Ganados en célula</div>
        <div class="dash-kpi-value"><?= $totalCelula ?></div>
        <div class="dash-kpi-sub"><?= $totalAnual > 0 ? round($totalCelula / $totalAnual * 100) : 0 ?>% del total</div>
        <span class="dash-kpi-badge badge-verde">Célula</span>
    </div>
    <div class="dash-kpi-card kpi-verde">
        <div class="dash-kpi-label">Ganados en iglesia</div>
        <div class="dash-kpi-value"><?= $totalIglesia ?></div>
        <div class="dash-kpi-sub"><?= $totalAnual > 0 ? round($totalIglesia / $totalAnual * 100) : 0 ?>% del total</div>
        <span class="dash-kpi-badge badge-verde">Iglesia</span>
    </div>
    <?php if ($metaTotal > 0): ?>
    <div class="dash-kpi-card kpi-<?= $semMeta ?>">
        <div class="dash-kpi-label">Cumplimiento de meta semestral</div>
        <div class="dash-kpi-value"><?= $pctMeta ?>%</div>
        <div class="dash-kpi-sub">Meta: <?= $metaTotal ?> · Ganados: <?= $totalAnual ?></div>
        <span class="dash-kpi-badge badge-<?= $semMeta ?>"><?= $semaforoInfo[$semMeta]['label'] ?? '' ?></span>
    </div>
    <?php endif; ?>
</div>

<!-- ── Gráficas ────────────────────────────────────────────────────────────── -->
<div class="dash-charts-grid">
    <!-- Tendencia mensual -->
    <div class="dash-chart-card" style="grid-column: span 2;">
        <h4>Tendencia mensual de ganados · <?= $anio ?></h4>
        <canvas id="chartTendencia" height="100"></canvas>
    </div>
    <!-- Distribución por ministerio -->
    <div class="dash-chart-card">
        <h4>Distribución por ministerio</h4>
        <canvas id="chartMinisterio" height="200"></canvas>
    </div>
    <!-- Distribución por edades -->
    <div class="dash-chart-card">
        <h4>Distribución por edades</h4>
        <canvas id="chartEdades" height="200"></canvas>
    </div>
</div>

<!-- ── Tabla semáforo por ministerio ──────────────────────────────────────── -->
<?php if (!empty($ministeriosConMeta)): ?>
<div class="card report-card" style="margin-bottom:22px; padding:18px;">
    <h4 style="margin:0 0 14px 0; font-size:.97rem; color:#374151;">
        Cumplimiento de meta por ministerio · Semestre actual
    </h4>
    <small style="color:#64748b; display:block; margin-bottom:12px;">
        Semáforo basado en porcentaje de meta: Verde ≥ 75 % · Amarillo 40–74 % · Rojo &lt; 40 %
    </small>
    <div class="table-container">
        <table class="dash-min-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ministerio</th>
                    <th>Meta</th>
                    <th>Ganados</th>
                    <th>Pendiente</th>
                    <th>% Cumplido</th>
                    <th>Semáforo</th>
                    <th>Progreso</th>
                </tr>
            </thead>
            <tbody>
                <?php $n = 1; foreach ($ministeriosConMeta as $row): ?>
                <tr>
                    <td style="color:#94a3b8;"><?= $n++ ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars((string)($row['ministerio'] ?? '')) ?></td>
                    <td><?= (int)($row['meta'] ?? 0) ?></td>
                    <td><strong><?= (int)($row['ganados'] ?? 0) ?></strong></td>
                    <td style="color:#ef4444;"><?= (int)($row['pendiente'] ?? 0) ?></td>
                    <td><strong><?= (int)($row['pct'] ?? 0) ?>%</strong></td>
                    <td style="text-align:center;">
                        <?php $s = (string)($row['semaforo'] ?? 'rojo'); ?>
                        <span class="dash-kpi-badge badge-<?= $s ?>" style="font-size:.75rem;">
                            <?= ($semaforoInfo[$s]['icon'] ?? '') . ' ' . ($semaforoInfo[$s]['label'] ?? '') ?>
                        </span>
                    </td>
                    <td style="min-width:100px;">
                        <div class="progress-bar-wrap">
                            <div class="progress-bar-fill progress-<?= $s ?>"
                                 style="width:<?= min(100, (int)($row['pct'] ?? 0)) ?>%"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc; font-weight:700;">
                    <td colspan="2">TOTAL</td>
                    <td><?= (int)($cumplimientoMetas['totales']['meta'] ?? 0) ?></td>
                    <td><?= (int)($cumplimientoMetas['totales']['ganados'] ?? 0) ?></td>
                    <td style="color:#ef4444;"><?= (int)($cumplimientoMetas['totales']['pendiente'] ?? 0) ?></td>
                    <td><?= $pctMeta ?>%</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Tabla desglose mensual ──────────────────────────────────────────────── -->
<div class="card report-card" style="margin-bottom:22px; padding:18px;">
    <h4 style="margin:0 0 14px 0; font-size:.97rem; color:#374151;">
        Detalle mensual de ganados · <?= $anio ?>
    </h4>
    <div class="table-container">
        <table class="dash-min-table">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>En célula</th>
                    <th>En iglesia</th>
                    <th>Total</th>
                    <th>Semáforo</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($m = 1; $m <= 12; $m++):
                    $gc   = (int)($gananciasMensuales[$m]['celula'] ?? 0);
                    $gi   = (int)($gananciasMensuales[$m]['iglesia'] ?? 0);
                    $tot  = (int)($gananciasMensuales[$m]['total'] ?? 0);
                    $sm   = $tot >= 121 ? 'verde' : ($tot >= 61 ? 'amarillo' : 'rojo');
                    $info = $semaforoInfo[$sm];
                    $esMesActual = ($m === $mesActual && $anio === (int)date('Y'));
                ?>
                <tr <?= $esMesActual ? 'style="background:#f0fdf4;"' : '' ?>>
                    <td style="font-weight:<?= $esMesActual ? '700' : '400' ?>;">
                        <?= htmlspecialchars($mesesLabels[$m] ?? '??') ?>
                        <?php if ($esMesActual): ?><span style="font-size:.72rem;color:#22c55e;margin-left:4px;">← actual</span><?php endif; ?>
                    </td>
                    <td><?= $gc ?></td>
                    <td><?= $gi ?></td>
                    <td><strong><?= $tot ?></strong></td>
                    <td>
                        <?php if ($tot > 0): ?>
                        <span class="dash-kpi-badge badge-<?= $sm ?>" style="font-size:.73rem;">
                            <?= $info['icon'] . ' ' . htmlspecialchars($info['label']) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:#d1d5db; font-size:.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc; font-weight:700;">
                    <td>TOTAL</td>
                    <td><?= $totalCelula ?></td>
                    <td><?= $totalIglesia ?></td>
                    <td><?= $totalAnual ?></td>
                    <td>
                        <span class="dash-kpi-badge badge-<?= $semaforoAnual ?>" style="font-size:.73rem;">
                            <?= ($semaforoInfo[$semaforoAnual]['icon'] ?? '') . ' ' . htmlspecialchars($semaforoInfo[$semaforoAnual]['label'] ?? '') ?>
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    'use strict';

    const meses       = <?= $mesesLabelsJson ?>;
    const dataCelula  = <?= $ganarCelulaJson ?>;
    const dataIglesia = <?= $ganarIglesiaJson ?>;
    const dataTotal   = <?= $ganarTotalJson ?>;

    // Colores semáforo por mes (total)
    const colorPorTotal = dataTotal.map(v => v >= 121 ? '#22c55e' : (v >= 61 ? '#eab308' : '#ef4444'));

    // ── Gráfica de tendencia (barras apiladas + línea total) ──────────────
    const ctxTend = document.getElementById('chartTendencia');
    if (ctxTend) {
        new Chart(ctxTend, {
            type: 'bar',
            data: {
                labels: meses,
                datasets: [
                    {
                        label: 'En célula',
                        data: dataCelula,
                        backgroundColor: 'rgba(99,102,241,.75)',
                        borderRadius: 4,
                        order: 2
                    },
                    {
                        label: 'En iglesia',
                        data: dataIglesia,
                        backgroundColor: 'rgba(34,197,94,.7)',
                        borderRadius: 4,
                        order: 2
                    },
                    {
                        label: 'Total',
                        data: dataTotal,
                        type: 'line',
                        borderColor: '#f59e0b',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        pointBackgroundColor: colorPorTotal,
                        pointRadius: 5,
                        tension: 0.35,
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            afterBody: function(items) {
                                const idx = items[0]?.dataIndex;
                                const v = dataTotal[idx];
                                if (v === undefined) return [];
                                if (v >= 121) return ['🟢 Excelente (121-180)'];
                                if (v >= 61)  return ['🟡 En proceso (61-120)'];
                                return ['🔴 Atención (1-60)'];
                            }
                        }
                    }
                },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true, ticks: { stepSize: 10 } }
                }
            }
        });
    }

    // ── Gráfica por ministerio (dona) ────────────────────────────────────
    const minNombres = <?= $ministerioNombresJson ?>;
    const minTotales = <?= $ministerioTotalesJson ?>;
    const ctxMin = document.getElementById('chartMinisterio');
    if (ctxMin && minNombres.length > 0) {
        const palette = [
            '#6366f1','#22c55e','#f59e0b','#ef4444','#3b82f6',
            '#a855f7','#14b8a6','#f97316','#ec4899','#10b981',
            '#8b5cf6','#0ea5e9','#eab308','#d946ef','#06b6d4'
        ];
        new Chart(ctxMin, {
            type: 'doughnut',
            data: {
                labels: minNombres,
                datasets: [{
                    data: minTotales,
                    backgroundColor: minNombres.map((_, i) => palette[i % palette.length]),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } }
                }
            }
        });
    }

    // ── Gráfica por edades (barras horizontales) ─────────────────────────
    const edadesLabels = ['Kids (3-8)', 'Teens (9-12)', 'Rocas (13-17)', 'Jóvenes (18-30)', 'Adultos (31-59)', 'Adultos Mayores (60+)', 'Sin dato'];
    const edadesDatos  = <?= $edadesJson ?>;
    const ctxEd = document.getElementById('chartEdades');
    if (ctxEd) {
        new Chart(ctxEd, {
            type: 'bar',
            data: {
                labels: edadesLabels,
                datasets: [{
                    label: 'Personas',
                    data: edadesDatos,
                    backgroundColor: [
                        '#6366f1','#22c55e','#f59e0b','#3b82f6','#a855f7','#14b8a6','#94a3b8'
                    ],
                    borderRadius: 6,
                    borderWidth: 0
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });
    }
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>

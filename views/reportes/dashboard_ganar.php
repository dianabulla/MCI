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
$ministeriosConMeta = $ministerios_con_meta ?? [];
$cumplimientoMetas  = $cumplimiento_metas ?? [];
$ministeriosDisp    = $ministerios_disponibles ?? [];
$lideresDisp        = $lideres_disponibles ?? [];
$dashboardMetasMinisterio = $dashboard_metas_ministerio ?? ['items' => [], 'periodos' => []];

// G12-GANAR totales
$totalesG12 = $totales_g12 ?? ['gi' => 0, 'gc' => 0, 'fv' => 0, 'v' => 0, 'total' => 0];
$g12GI      = (int)($totalesG12['gi'] ?? 0);
$g12GC      = (int)($totalesG12['gc'] ?? 0);
$g12FV      = (int)($totalesG12['fv'] ?? 0);
$g12V       = (int)($totalesG12['v'] ?? 0);

// Indicador mensual por Líder
$lideresH   = $lideres_semanal_hombre ?? [];
$lideresM   = $lideres_semanal_mujer ?? [];
$fechaInicioSem = $fecha_inicio_semanal ?? date('Y-m-d', strtotime('-7 days'));
$fechaFinSem = $fecha_fin_semanal ?? date('Y-m-d');
$semSemanal = [
    'verde'   => ['bg' => '#22c55e', 'label' => 'Cumplida'],
    'amarillo'=> ['bg' => '#eab308', 'label' => 'En progreso'],
    'rojo'    => ['bg' => '#ef4444', 'label' => 'Atención'],
];

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

/* Dashboard de metas (semana/mes/año rotativo) */
.dash-metas-head { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
.dash-metas-dots { display:flex; gap:6px; }
.dash-metas-dot { width:9px; height:9px; border-radius:50%; border:0; background:#b9c9df; padding:0; }
.dash-metas-dot.is-active { background:#1d4ed8; transform:scale(1.15); }
.dash-metas-slide { display:none; }
.dash-metas-slide.is-active { display:block; animation:dashMetasFade .3s ease; }
.dash-metas-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px; }
.dash-metas-card { border:1px solid #d8e4f4; border-radius:12px; background:#fff; padding:10px; }
.dash-metas-card-top { display:flex; justify-content:space-between; align-items:center; gap:8px; }
.dash-metas-card-title { color:#20406f; font-size:13px; font-weight:700; }
.dash-metas-card-status { font-size:11px; font-weight:700; border-radius:999px; padding:2px 8px; white-space:nowrap; }
.dash-metas-gauge { width:114px; height:114px; margin:10px auto; border-radius:50%; background:conic-gradient(var(--gauge-color) calc(var(--gauge-percent) * 1%), #e6edf7 0); display:grid; place-items:center; }
.dash-metas-gauge-inner { width:78px; height:78px; border-radius:50%; background:#fff; display:flex; flex-direction:column; align-items:center; justify-content:center; }
.dash-metas-gauge-inner strong { color:#1f3f70; font-size:16px; line-height:1; }
.dash-metas-gauge-inner small { color:#617692; font-size:10px; }
.dash-metas-metrics { display:grid; grid-template-columns:repeat(3,1fr); gap:6px; }
.dash-metas-metrics > div { background:#f6f9ff; border:1px solid #e3ebf8; border-radius:8px; padding:6px; text-align:center; }
.dash-metas-metrics span { display:block; font-size:10px; color:#6a7f9a; }
.dash-metas-metrics strong { color:#22477a; font-size:14px; }
.dash-metas-pacing { margin-top:8px; font-size:11px; font-weight:700; border-radius:8px; padding:6px 8px; text-align:center; }
.dash-metas-pacing.is-on-time { background:#e8f7ee; color:#1f7a45; }
.dash-metas-pacing.is-late { background:#fff1f1; color:#b63838; }
@keyframes dashMetasFade { from { opacity:.45; transform:translateY(4px);} to { opacity:1; transform:translateY(0);} }
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

<div class="card report-card" style="margin-bottom:22px; padding:14px 14px 12px;">
    <div class="dash-metas-head">
        <div>
            <h4 style="margin:0 0 4px 0; font-size:1rem; color:#1f3f70;">Metas por Ministerio</h4>
            <small style="color:#60708a;">Velocímetro automático: semana, mes y año (justo a tiempo).</small>
        </div>
        <div class="dash-metas-dots" id="dashMetasDots"></div>
    </div>

    <div id="dashMetasSlidesWrap">
        <?php
        $vistasDashMetas = [
            'semana' => ['titulo' => 'Semana', 'sub' => 'Cumplimiento semanal por ministerio'],
            'mes' => ['titulo' => 'Mes', 'sub' => 'Cumplimiento mensual por ministerio'],
            'anio' => ['titulo' => 'Año', 'sub' => 'Cumplimiento anual por ministerio'],
        ];
        $idxDash = 0;
        foreach ($vistasDashMetas as $keyVistaDash => $metaVistaDash):
        ?>
            <section class="dash-metas-slide<?= $idxDash === 0 ? ' is-active' : '' ?>" data-slide-index="<?= $idxDash ?>">
                <div style="margin-bottom:8px;">
                    <strong style="color:#1f3f70;"><?= htmlspecialchars($metaVistaDash['titulo']) ?></strong>
                    <small style="color:#60708a; display:block;"><?= htmlspecialchars($metaVistaDash['sub']) ?></small>
                </div>

                <div class="dash-metas-grid">
                    <?php if (!empty($dashboardMetasMinisterio['items'])): ?>
                        <?php foreach ((array)$dashboardMetasMinisterio['items'] as $itemMetaDash): ?>
                            <?php
                            $bloqueVista = (array)($itemMetaDash[$keyVistaDash] ?? []);
                            $estadoVista = (array)($bloqueVista['estado'] ?? []);
                            $metaVista = (int)($bloqueVista['meta'] ?? 0);
                            $logradoVista = (int)($bloqueVista['logrado'] ?? 0);
                            $esperadoVista = (int)($bloqueVista['esperado'] ?? 0);
                            $porcentajeVista = (float)($bloqueVista['porcentaje'] ?? 0);
                            $porcentajeGauge = max(0, min(100, $porcentajeVista));
                            $colorEstado = (string)($estadoVista['color'] ?? '#d64545');
                            $labelEstado = (string)($estadoVista['label'] ?? 'Crítico');
                            $justoATiempo = !empty($bloqueVista['justo_a_tiempo']);
                            ?>
                            <article class="dash-metas-card">
                                <div class="dash-metas-card-top">
                                    <span class="dash-metas-card-title"><?= htmlspecialchars((string)($itemMetaDash['ministerio'] ?? 'Ministerio')) ?></span>
                                    <span class="dash-metas-card-status" style="background:<?= htmlspecialchars($colorEstado) ?>22;color:<?= htmlspecialchars($colorEstado) ?>;">
                                        <?= htmlspecialchars($labelEstado) ?>
                                    </span>
                                </div>

                                <div class="dash-metas-gauge" style="--gauge-color:<?= htmlspecialchars($colorEstado) ?>; --gauge-percent:<?= htmlspecialchars((string)$porcentajeGauge) ?>;">
                                    <div class="dash-metas-gauge-inner">
                                        <strong><?= number_format($porcentajeVista, 1) ?>%</strong>
                                        <small>cumplimiento</small>
                                    </div>
                                </div>

                                <div class="dash-metas-metrics">
                                    <div><span>Logrado</span><strong><?= $logradoVista ?></strong></div>
                                    <div><span>Meta</span><strong><?= $metaVista ?></strong></div>
                                    <div><span>Esperado</span><strong><?= $esperadoVista ?></strong></div>
                                </div>

                                <div class="dash-metas-pacing <?= $justoATiempo ? 'is-on-time' : 'is-late' ?>">
                                    <?= $justoATiempo ? 'Justo a tiempo' : 'Atrasado frente al ritmo esperado' ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="report-empty-state" style="grid-column:1/-1;">No hay ministerios con metas configuradas para mostrar.</div>
                    <?php endif; ?>
                </div>
            </section>
        <?php $idxDash++; endforeach; ?>
    </div>
</div>

<?php
$metaTotal    = (int)($cumplimientoMetas['totales']['meta'] ?? 0);
$pctMeta      = $metaTotal > 0 ? (int)round(($totalAnual / $metaTotal) * 100) : 0;
?>

<!-- ── G12-GANAR ──────────────────────────────────────────────────────────── -->
<div class="card report-card" style="margin-bottom:22px; padding:18px;">
    <h4 style="margin:0 0 16px 0; font-size:.97rem; color:#374151; font-weight:700;">
        G12-GANAR · <?= $anio ?>
    </h4>
    <div style="display:flex; gap:24px; align-items:flex-start; flex-wrap:wrap;">
        <!-- Tabla resumen -->
        <table style="border-collapse:collapse; min-width:130px; font-size:.92rem;">
            <tbody>
                <tr>
                    <td style="background:#eab308; color:#1a1a1a; font-weight:700; padding:6px 12px; border:1px solid #ccc;">GI</td>
                    <td style="padding:6px 14px; border:1px solid #ccc; font-weight:600;"><?= $g12GI ?></td>
                </tr>
                <tr>
                    <td style="background:#dc2626; color:#fff; font-weight:700; padding:6px 12px; border:1px solid #ccc;">GC</td>
                    <td style="padding:6px 14px; border:1px solid #ccc; font-weight:600;"><?= $g12GC ?></td>
                </tr>
                <tr>
                    <td style="background:#16a34a; color:#fff; font-weight:700; padding:6px 12px; border:1px solid #ccc;">FV</td>
                    <td style="padding:6px 14px; border:1px solid #ccc; font-weight:600;"><?= $g12FV ?></td>
                </tr>
                <tr>
                    <td style="background:#3b82f6; color:#fff; font-weight:700; padding:6px 12px; border:1px solid #ccc;">V</td>
                    <td style="padding:6px 14px; border:1px solid #ccc; font-weight:600;"><?= $g12V ?></td>
                </tr>
            </tbody>
        </table>
        <!-- Gráfica de barras -->
        <div style="flex:1; min-width:260px; max-width:520px;">
            <canvas id="chartG12Ganar" height="160"></canvas>
        </div>
    </div>
    <p style="margin:10px 0 0 0; font-size:.78rem; color:#64748b;">
        GI = Ganados en iglesia · GC = Ganados en célula · FV = Fonovisita · V = Visita
    </p>
</div>

<!-- ── Indicador Mensual por Líder ────────────────────────────────────── -->
<div class="card report-card" style="margin-bottom:22px; padding:18px;">
    <h4 style="margin:0 0 14px 0; font-size:.97rem; color:#374151; font-weight:700;">
        Indicador mensual por Líder
    </h4>
    <small style="color:#64748b; display:block; margin-bottom:14px;">
        Ganados del <?= date('d/m/Y', strtotime($fechaInicioSem)) ?> al <?= date('d/m/Y', strtotime($fechaFinSem)) ?> ·
        Meta personal mensual = (Meta anual del ministerio / 12) ÷ líderes del mismo género
    </small>

    <!-- Hombres -->
    <?php if (!empty($lideresH)): ?>
    <div style="margin-bottom:20px;">
        <h5 style="margin:0 0 10px 0; font-size:.85rem; color:#475569; font-weight:700;">👨 Hombres</h5>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px;">
            <?php foreach ($lideresH as $lid): ?>
            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,.05);">
                <div style="font-size:.8rem; color:#475569; margin-bottom:6px; font-weight:600;">
                    <?= htmlspecialchars(trim($lid['nombre'] . ' ' . $lid['apellido'])) ?>
                </div>
                <div style="font-size:.68rem; color:#64748b; margin-bottom:6px;">
                    <?= htmlspecialchars((string)($lid['ministerio'] ?? 'Sin ministerio')) ?>
                </div>
                <div style="background:<?= $semSemanal[$lid['semaforo']]['bg'] ?>; color:<?= stripos($lid['semaforo'], 'amarillo') !== false ? '#1a1a1a' : '#fff' ?>; font-weight:700; padding:8px; border-radius:6px; font-size:.95rem; margin-bottom:6px;">
                    <?= $lid['ganados'] ?>
                </div>
                <div style="font-size:.72rem; color:#475569; margin-bottom:4px; font-weight:600;">
                    Meta: <?= (int)($lid['meta_personal_mensual'] ?? 0) ?> · Avance: <?= (int)($lid['avance_pct'] ?? 0) ?>%
                </div>
                <div style="font-size:.72rem; color:#64748b;">
                    <?= (int)($lid['meta_personal_mensual'] ?? 0) <= 0 ? 'Sin meta configurada' : htmlspecialchars($semSemanal[$lid['semaforo']]['label']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Mujeres -->
    <?php if (!empty($lideresM)): ?>
    <div>
        <h5 style="margin:0 0 10px 0; font-size:.85rem; color:#475569; font-weight:700;">👩 Mujeres</h5>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px;">
            <?php foreach ($lideresM as $lid): ?>
            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,.05);">
                <div style="font-size:.8rem; color:#475569; margin-bottom:6px; font-weight:600;">
                    <?= htmlspecialchars(trim($lid['nombre'] . ' ' . $lid['apellido'])) ?>
                </div>
                <div style="font-size:.68rem; color:#64748b; margin-bottom:6px;">
                    <?= htmlspecialchars((string)($lid['ministerio'] ?? 'Sin ministerio')) ?>
                </div>
                <div style="background:<?= $semSemanal[$lid['semaforo']]['bg'] ?>; color:<?= stripos($lid['semaforo'], 'amarillo') !== false ? '#1a1a1a' : '#fff' ?>; font-weight:700; padding:8px; border-radius:6px; font-size:.95rem; margin-bottom:6px;">
                    <?= $lid['ganados'] ?>
                </div>
                <div style="font-size:.72rem; color:#475569; margin-bottom:4px; font-weight:600;">
                    Meta: <?= (int)($lid['meta_personal_mensual'] ?? 0) ?> · Avance: <?= (int)($lid['avance_pct'] ?? 0) ?>%
                </div>
                <div style="font-size:.72rem; color:#64748b;">
                    <?= (int)($lid['meta_personal_mensual'] ?? 0) <= 0 ? 'Sin meta configurada' : htmlspecialchars($semSemanal[$lid['semaforo']]['label']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($lideresH) && empty($lideresM)): ?>
    <p style="color:#94a3b8; font-size:.85rem; margin:0;">No hay líderes configurados o sin ganados esta semana.</p>
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

    // ── Gráfica G12-GANAR (barras) ───────────────────────────────────────
    const ctxG12 = document.getElementById('chartG12Ganar');
    if (ctxG12) {
        new Chart(ctxG12, {
            type: 'bar',
            data: {
                labels: ['GI', 'GC', 'FV', 'V'],
                datasets: [{
                    label: 'G12-GANAR',
                    data: [<?= $g12GI ?>, <?= $g12GC ?>, <?= $g12FV ?>, <?= $g12V ?>],
                    backgroundColor: ['#eab308', '#dc2626', '#16a34a', '#3b82f6'],
                    borderRadius: 6,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'G12-GANAR · <?= $anio ?>', font: { size: 14 } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const labels = ['Ganados en iglesia','Ganados en célula','Fonovisita','Visita'];
                                return labels[ctx.dataIndex] + ': ' + ctx.raw;
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 50 } }
                }
            }
        });
    }

    // ── Rotación automática de metas (semana/mes/año) ────────────────────
    const slidesWrap = document.getElementById('dashMetasSlidesWrap');
    const dotsWrap = document.getElementById('dashMetasDots');
    if (slidesWrap && dotsWrap) {
        const slides = Array.from(slidesWrap.querySelectorAll('.dash-metas-slide'));
        if (slides.length > 0) {
            let current = 0;
            let timer = null;

            const activar = (index) => {
                current = index;
                slides.forEach((slide, idx) => {
                    slide.classList.toggle('is-active', idx === current);
                });
                Array.from(dotsWrap.querySelectorAll('.dash-metas-dot')).forEach((dot, idx) => {
                    dot.classList.toggle('is-active', idx === current);
                });
            };

            slides.forEach((_, idx) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'dash-metas-dot' + (idx === 0 ? ' is-active' : '');
                dot.setAttribute('aria-label', 'Ir a vista ' + (idx + 1));
                dot.addEventListener('click', () => {
                    activar(idx);
                    if (timer) {
                        clearInterval(timer);
                    }
                    timer = setInterval(() => activar((current + 1) % slides.length), 7000);
                });
                dotsWrap.appendChild(dot);
            });

            activar(0);
            timer = setInterval(() => activar((current + 1) % slides.length), 7000);
        }
    }
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>

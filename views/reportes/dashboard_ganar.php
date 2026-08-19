<?php include VIEWS . '/layout/header.php'; ?>
<?php
// ── Variables del controlador ────────────────────────────────────────────────
$anio               = (int)($anio ?? date('Y'));
$filtroMinisterio   = (string)($filtro_ministerio ?? '');
$filtroLider        = (string)($filtro_lider ?? '');
$celFiltroDesde     = (string)($cel_filtro_desde ?? '');
$celFiltroHasta     = (string)($cel_filtro_hasta ?? '');

require_once APP . '/Helpers/DashboardSelector.php';
$dashModuloActivo = DashboardSelector::detectarActivo();
$dashModuloTitulo = DashboardSelector::etiquetaActivo($dashModuloActivo);
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
$resumenSemanalLider = $resumen_semanal_lider ?? ['inicio' => '', 'fin' => '', 'rows' => [], 'totales' => []];
$semanalLiderRows = is_array($resumenSemanalLider['rows'] ?? null) ? $resumenSemanalLider['rows'] : [];
$semanalLiderTotales = is_array($resumenSemanalLider['totales'] ?? null) ? $resumenSemanalLider['totales'] : [];
$semanalLiderInicio = (string)($resumenSemanalLider['inicio'] ?? '');
$semanalLiderFin = (string)($resumenSemanalLider['fin'] ?? '');
$reporteCelulasPorLider = $reporte_celulas_por_lider ?? ['anio' => $anio, 'fecha_hasta' => '', 'grupos' => [], 'totales' => []];
$reporteCelulasGrupos = is_array($reporteCelulasPorLider['grupos'] ?? null) ? $reporteCelulasPorLider['grupos'] : [];
$reporteCelulasTotales = is_array($reporteCelulasPorLider['totales'] ?? null) ? $reporteCelulasPorLider['totales'] : [];
$reporteCelulasFechaDesde = (string)($reporteCelulasPorLider['fecha_desde'] ?? '');
$reporteCelulasFechaHasta = (string)($reporteCelulasPorLider['fecha_hasta'] ?? '');
$reporteCelulasRangoPersonalizado = !empty($reporteCelulasPorLider['rango_personalizado']);
$exportCelulasPorLiderUrl = PUBLIC_URL . 'index.php?url=reportes/dashboard-ganar/exportar-celulas-lider&anio=' . $anio;
if ($filtroMinisterio !== '') {
    $exportCelulasPorLiderUrl .= '&ministerio=' . urlencode($filtroMinisterio);
}
if ($filtroLider !== '') {
    $exportCelulasPorLiderUrl .= '&lider=' . urlencode($filtroLider);
}
if ($celFiltroDesde !== '') {
    $exportCelulasPorLiderUrl .= '&cel_desde=' . urlencode($celFiltroDesde);
}
if ($celFiltroHasta !== '') {
    $exportCelulasPorLiderUrl .= '&cel_hasta=' . urlencode($celFiltroHasta);
}
$celFiltroResetUrl = PUBLIC_URL . 'index.php?url=reportes/dashboard-ganar&anio=' . $anio;
if ($filtroMinisterio !== '') {
    $celFiltroResetUrl .= '&ministerio=' . urlencode($filtroMinisterio);
}
if ($filtroLider !== '') {
    $celFiltroResetUrl .= '&lider=' . urlencode($filtroLider);
}
$cumplimientoMetas  = $cumplimiento_metas ?? [];
$ministeriosDisp    = $ministerios_disponibles ?? [];
$lideresDisp        = $lideres_disponibles ?? [];
$dashboardMetasMinisterio = $dashboard_metas_ministerio ?? ['items' => [], 'periodos' => []];

// G12-GANAR totales
$totalesG12 = $totales_g12 ?? ['gi' => 0, 'gc' => 0, 'fv' => 0, 'v' => 0, 'uc' => 0, 'total' => 0];
$g12GI      = (int)($totalesG12['gi'] ?? 0);
$g12GC      = (int)($totalesG12['gc'] ?? 0);
$g12FV      = (int)($totalesG12['fv'] ?? 0);
$g12V       = (int)($totalesG12['v'] ?? 0);
$g12UC      = (int)($totalesG12['uc'] ?? 0);
$g12Total   = (int)($totalesG12['total'] ?? 0);

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
.dash-celulas-rango-form { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-bottom:14px; padding:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; }
.dash-celulas-rango-form .form-group { margin:0; }
.dash-celulas-rango-form label { font-size:.8rem; color:#475569; display:block; margin-bottom:4px; }
.dash-celulas-rango-form input[type="date"] { padding:6px 10px; border-radius:8px; border:1px solid #d1d5db; font-size:.88rem; }
.dash-cel-metrica-btn { border:none; background:none; padding:0; font:inherit; font-weight:600; cursor:pointer; text-decoration:underline; text-underline-offset:2px; }
.dash-cel-metrica-btn.is-alerta { color:#dc2626; }
.dash-cel-metrica-btn.is-neutral { color:#64748b; }
.dash-cel-metrica-btn:hover { opacity:.85; }
.dash-cel-detalle-modal { position:fixed; inset:0; z-index:1200; display:flex; align-items:center; justify-content:center; padding:16px; }
.dash-cel-detalle-modal[hidden] { display:none !important; }
.dash-cel-detalle-backdrop { position:absolute; inset:0; background:rgba(15,23,42,.45); }
.dash-cel-detalle-panel { position:relative; z-index:1; background:#fff; border-radius:12px; max-width:720px; width:100%; max-height:85vh; overflow:auto; padding:18px 20px; box-shadow:0 12px 40px rgba(15,23,42,.2); }
.dash-cel-detalle-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:14px; }
.dash-cel-detalle-head h4 { margin:0; font-size:1.05rem; color:#1e293b; }
.dash-cel-detalle-sub { margin:4px 0 0; font-size:.84rem; color:#64748b; }
.dash-cel-detalle-cerrar { border:none; background:#f1f5f9; width:32px; height:32px; border-radius:8px; font-size:1.25rem; line-height:1; cursor:pointer; color:#475569; }
.dash-cel-detalle-ayuda { margin:0 0 14px; padding:12px 14px; border-radius:10px; font-size:.84rem; line-height:1.45; }
.dash-cel-detalle-ayuda.is-sobre { background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; }
.dash-cel-detalle-ayuda.is-reporte { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
.dash-cel-detalle-ayuda strong { display:block; margin-bottom:4px; font-size:.88rem; }
.dash-cel-detalle-resumen { margin:0 0 10px; font-size:.82rem; color:#475569; font-weight:600; }
.dash-cel-detalle-table { width:100%; border-collapse:collapse; font-size:.84rem; }
.dash-cel-detalle-table th { text-align:left; padding:8px 10px; background:#f8fafc; color:#475569; font-size:.74rem; text-transform:uppercase; letter-spacing:.03em; border-bottom:1px solid #e2e8f0; }
.dash-cel-detalle-table td { padding:10px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.dash-cel-detalle-table tr:last-child td { border-bottom:none; }
.dash-cel-detalle-table .col-num { width:36px; color:#94a3b8; text-align:center; font-weight:600; }
.dash-cel-detalle-table .col-periodo { min-width:180px; }
.dash-cel-detalle-table .periodo-principal { font-weight:600; color:#1e293b; margin:0 0 2px; }
.dash-cel-detalle-table .periodo-fechas { margin:0; font-size:.76rem; color:#64748b; }
.dash-cel-detalle-table .celula-tag { display:inline-block; padding:2px 8px; border-radius:999px; background:#eef2ff; color:#3730a3; font-size:.72rem; font-weight:600; white-space:nowrap; }
.dash-cel-detalle-table .situacion-tag { display:inline-flex; align-items:flex-start; gap:6px; }
.dash-cel-detalle-table .situacion-icon { flex-shrink:0; width:22px; height:22px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:700; }
.dash-cel-detalle-table .situacion-icon.is-sobre { background:#ffedd5; color:#c2410c; }
.dash-cel-detalle-table .situacion-icon.is-reporte { background:#fee2e2; color:#b91c1c; }
.dash-cel-detalle-vacio { padding:20px; text-align:center; color:#94a3b8; font-size:.88rem; }
.dash-filters-form { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
.dash-filters-form .form-group { margin:0; }
.dash-filters-form select { padding:6px 10px; border-radius:8px; border:1px solid #d1d5db; font-size:.88rem; min-width:160px; }

/* Dashboard de metas (semana/mes/año rotativo) */
.dash-metas-head { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
.dash-metas-controls { display:flex; align-items:center; gap:8px; }
.dash-metas-dots { display:flex; gap:6px; }
.dash-metas-dot { width:9px; height:9px; border-radius:50%; border:0; background:#b9c9df; padding:0; }
.dash-metas-dot.is-active { background:#1d4ed8; transform:scale(1.15); }
.dash-metas-arrow {
    width:26px;
    height:26px;
    border-radius:999px;
    border:1px solid #cddbf0;
    background:#ffffff;
    color:#1f3f70;
    font-size:14px;
    font-weight:700;
    line-height:1;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
}
.dash-metas-arrow:hover { background:#eef4ff; }
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
        <h2>Dashboard · <?= htmlspecialchars($dashModuloTitulo, ENT_QUOTES, 'UTF-8') ?></h2>
        <small style="color:#64748b;">Indicadores y gráficas con semáforo · <?= $anio ?></small>
    </div>
    <div class="dash-header-actions">
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
            <input type="hidden" name="url" value="reportes/dashboard-ganar">
            <?php if ($filtroMinisterio !== ''): ?>
                <input type="hidden" name="ministerio" value="<?= htmlspecialchars($filtroMinisterio) ?>">
            <?php endif; ?>
            <?php if ($filtroLider !== ''): ?>
                <input type="hidden" name="lider" value="<?= htmlspecialchars($filtroLider) ?>">
            <?php endif; ?>
            <?php if ($celFiltroDesde !== ''): ?>
                <input type="hidden" name="cel_desde" value="<?= htmlspecialchars($celFiltroDesde) ?>">
            <?php endif; ?>
            <?php if ($celFiltroHasta !== ''): ?>
                <input type="hidden" name="cel_hasta" value="<?= htmlspecialchars($celFiltroHasta) ?>">
            <?php endif; ?>
            <label for="anio_select" style="font-size:.84rem; color:#475569; white-space:nowrap;">Año:</label>
            <select id="anio_select" name="anio" onchange="this.form.submit()">
                <?php for ($y = (int)date('Y'); $y >= 2023; $y--): ?>
                    <option value="<?= $y ?>" <?= $y === $anio ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
        <?php
        $urlReporteRedes = PUBLIC_URL . 'index.php?url=reportes/dashboard-ganar-redes&anio=' . $anio;
        if ($filtroMinisterio !== '') {
            $urlReporteRedes .= '&ministerio=' . urlencode((string)$filtroMinisterio);
        }
        if ($filtroLider !== '') {
            $urlReporteRedes .= '&lider=' . urlencode((string)$filtroLider);
        }
        ?>
        <a href="<?= htmlspecialchars($urlReporteRedes) ?>" class="btn" style="background:#1e3a8a;color:#fff;padding:7px 12px;border-radius:8px;text-decoration:none;font-size:.84rem;font-weight:600;">Reporte por redes</a>
    </div>
</div>

<!-- Filtros ministerio / líder -->
<div class="dash-filters-card">
    <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="dash-filters-form">
        <input type="hidden" name="url" value="reportes/dashboard-ganar">
        <input type="hidden" name="anio" value="<?= $anio ?>">
        <?php if ($celFiltroDesde !== ''): ?>
            <input type="hidden" name="cel_desde" value="<?= htmlspecialchars($celFiltroDesde) ?>">
        <?php endif; ?>
        <?php if ($celFiltroHasta !== ''): ?>
            <input type="hidden" name="cel_hasta" value="<?= htmlspecialchars($celFiltroHasta) ?>">
        <?php endif; ?>
        <div class="form-group">
            <label style="font-size:.8rem;color:#475569;display:block;margin-bottom:4px;">Ministerio</label>
            <select name="ministerio" id="dash-filtro-ministerio" onchange="dashAlCambiarMinisterio(this)">
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
            <select name="lider" id="dash-filtro-lider" onchange="this.form.submit()">
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
        <div class="dash-metas-controls">
            <button type="button" class="dash-metas-arrow" id="dashMetasPrev" aria-label="Vista anterior">&#8592;</button>
            <div class="dash-metas-dots" id="dashMetasDots"></div>
            <button type="button" class="dash-metas-arrow" id="dashMetasNext" aria-label="Vista siguiente">&#8594;</button>
        </div>
    </div>

    <div id="dashMetasSlidesWrap">
        <?php
        $vistasDashMetas = [
            'semestre' => ['titulo' => 'Semestre', 'sub' => 'Cumplimiento del semestre actual (meta S1 o S2 según calendario)'],
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
                <tr>
                    <td style="background:#7c3aed; color:#fff; font-weight:700; padding:6px 12px; border:1px solid #ccc;">UC</td>
                    <td style="padding:6px 14px; border:1px solid #ccc; font-weight:600;"><?= $g12UC ?></td>
                </tr>
            </tbody>
        </table>
        <!-- Gráfica de barras -->
        <div style="flex:1; min-width:260px; max-width:520px;">
            <canvas id="chartG12Ganar" height="160"></canvas>
        </div>
    </div>
    <p style="margin:10px 0 0 0; font-size:.78rem; color:#64748b;">
        GI = Ganados en iglesia · GC = Ganados en célula · FV = Fonovisita · V = Visita ·
        UC = Ubicados en célula (personas nuevas del año con célula asignada<?= $g12Total > 0 ? ' — ' . $g12UC . ' de ' . $g12Total . ' ganados' : '' ?>)
    </p>
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

<!-- ── Resumen semanal por líder (semana pasada) ──────────────────────────── -->
<?php if ($semanalLiderRows !== []): ?>
<div class="card report-card" style="margin-bottom:22px; padding:18px;">
    <h4 style="margin:0 0 14px 0; font-size:.97rem; color:#374151;">
        Resumen semanal por líder principal de 12 · Semana pasada
    </h4>
    <small style="color:#64748b; display:block; margin-bottom:12px;">
        Del <?= $semanalLiderInicio !== '' ? date('d/m/Y', strtotime($semanalLiderInicio)) : '—' ?>
        al <?= $semanalLiderFin !== '' ? date('d/m/Y', strtotime($semanalLiderFin)) : '—' ?>
        (lunes a domingo). Solo líderes principales de 12 del ministerio. Los ganados de su red se suman a cada uno.
    </small>
    <p style="margin:0 0 12px; font-size:.78rem; color:#64748b;">
        G.I = Ganados en iglesia · G.C = Ganados en célula · U.C = Ubicados en célula ·
        V/F = Con visita y/o fonovisita registrada
    </p>
    <div class="table-container">
        <table class="dash-min-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Líder</th>
                    <th>Ministerio</th>
                    <th>G.I</th>
                    <th>G.C</th>
                    <th>Total</th>
                    <th>U.C</th>
                    <th>V/F</th>
                </tr>
            </thead>
            <tbody>
                <?php $nSem = 1; foreach ($semanalLiderRows as $rowSem): ?>
                <tr>
                    <td style="color:#94a3b8;"><?= $nSem++ ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars((string)($rowSem['lider'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($rowSem['ministerio'] ?? '')) ?></td>
                    <td><?= (int)($rowSem['gi'] ?? 0) ?></td>
                    <td><?= (int)($rowSem['gc'] ?? 0) ?></td>
                    <td><strong><?= (int)($rowSem['total'] ?? 0) ?></strong></td>
                    <td><?= (int)($rowSem['uc'] ?? 0) ?></td>
                    <td><?= (int)($rowSem['visita_fono'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc; font-weight:700;">
                    <td colspan="3">TOTAL</td>
                    <td><?= (int)($semanalLiderTotales['gi'] ?? 0) ?></td>
                    <td><?= (int)($semanalLiderTotales['gc'] ?? 0) ?></td>
                    <td><?= (int)($semanalLiderTotales['total'] ?? 0) ?></td>
                    <td><?= (int)($semanalLiderTotales['uc'] ?? 0) ?></td>
                    <td><?= (int)($semanalLiderTotales['visita_fono'] ?? 0) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Reporte operativo de células por líder ─────────────────────────────── -->
<?php if ($reporteCelulasGrupos !== []): ?>
<div class="card report-card" style="margin-bottom:22px; padding:18px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:14px;">
        <h4 style="margin:0; font-size:.97rem; color:#374151;">
            Reporte operativo de células por líder
            <?php if ($reporteCelulasRangoPersonalizado): ?>
                · <?= $reporteCelulasFechaDesde !== '' ? date('d/m/Y', strtotime($reporteCelulasFechaDesde)) : '—' ?>
                – <?= $reporteCelulasFechaHasta !== '' ? date('d/m/Y', strtotime($reporteCelulasFechaHasta)) : '—' ?>
            <?php else: ?>
                · <?= $anio ?>
            <?php endif; ?>
        </h4>
        <a href="<?= htmlspecialchars($exportCelulasPorLiderUrl) ?>" class="btn btn-sm btn-success" title="Exportar esta tabla a Excel">
            <i class="bi bi-file-earmark-excel"></i> Exportar Excel
        </a>
    </div>

    <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="dash-celulas-rango-form">
        <input type="hidden" name="url" value="reportes/dashboard-ganar">
        <input type="hidden" name="anio" value="<?= $anio ?>">
        <?php if ($filtroMinisterio !== ''): ?>
            <input type="hidden" name="ministerio" value="<?= htmlspecialchars($filtroMinisterio) ?>">
        <?php endif; ?>
        <?php if ($filtroLider !== ''): ?>
            <input type="hidden" name="lider" value="<?= htmlspecialchars($filtroLider) ?>">
        <?php endif; ?>
        <div class="form-group">
            <label for="cel_desde">Desde</label>
            <input type="date" id="cel_desde" name="cel_desde" class="form-control"
                   value="<?= htmlspecialchars($celFiltroDesde !== '' ? $celFiltroDesde : $reporteCelulasFechaDesde) ?>"
                   max="<?= htmlspecialchars(date('Y-m-d')) ?>">
        </div>
        <div class="form-group">
            <label for="cel_hasta">Hasta</label>
            <input type="date" id="cel_hasta" name="cel_hasta" class="form-control"
                   value="<?= htmlspecialchars($celFiltroHasta !== '' ? $celFiltroHasta : $reporteCelulasFechaHasta) ?>"
                   max="<?= htmlspecialchars(date('Y-m-d')) ?>">
        </div>
        <button type="submit" class="btn btn-sm btn-primary">Aplicar rango</button>
        <?php if ($reporteCelulasRangoPersonalizado): ?>
            <a href="<?= htmlspecialchars($celFiltroResetUrl) ?>" class="btn btn-sm btn-outline-secondary">Restablecer año</a>
        <?php endif; ?>
    </form>

    <small style="color:#64748b; display:block; margin-bottom:12px;">
        Agrupado por ministerio. Incluye líderes de célula y líderes de 12 que dirigen célula.
        Datos del
        <?= $reporteCelulasFechaDesde !== '' ? date('d/m/Y', strtotime($reporteCelulasFechaDesde)) : '—' ?>
        al <?= $reporteCelulasFechaHasta !== '' ? date('d/m/Y', strtotime($reporteCelulasFechaHasta)) : '—' ?>.
        Semanas sin entregar sobre = semanas con reporte de asistencia pero sin marcar la casilla de sobre entregado.
        Semanas sin reportar = semanas consecutivas desde el último reporte de asistencia (marcar sobre no cuenta como reporte).
        Clic en el número para ver el detalle por semana.
    </small>
    <?php
    $renderMetricaCelulasClick = static function(int $valor, array $detalle, string $tituloModal, string $nombreLider, string $tipo) {
        if ($valor <= 0) {
            return '<span style="color:#64748b;">0</span>';
        }

        $ayuda = $tipo === 'sin_sobre'
            ? 'En estas semanas la célula sí reportó asistencia, pero en la pantalla de Asistencias no quedó marcada la casilla «Sobre entregado».'
            : 'Estas son las semanas consecutivas sin reporte de asistencia de célula. Marcar solo el sobre no cuenta como reporte.';

        $payload = htmlspecialchars(json_encode([
            'titulo' => $tituloModal,
            'lider' => $nombreLider,
            'tipo' => $tipo,
            'ayuda' => $ayuda,
            'total' => $valor,
            'items' => array_values($detalle),
        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

        return '<button type="button" class="dash-cel-metrica-btn is-alerta" data-detalle="' . $payload
            . '" title="Ver detalle de semanas">' . $valor . '</button>';
    };
    ?>
    <div class="table-container">
        <table class="dash-min-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Líder</th>
                    <th>Tipo</th>
                    <th>Células</th>
                    <th>Asistentes</th>
                    <th>Sem. sin entregar sobre</th>
                    <th>Sem. sin reportar</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $nReporteCel = 1;
                foreach ($reporteCelulasGrupos as $grupoCel):
                    $ministerioGrupo = (string)($grupoCel['ministerio'] ?? 'Sin ministerio');
                    $subtotalesGrupo = is_array($grupoCel['subtotales'] ?? null) ? $grupoCel['subtotales'] : [];
                    $lideresGrupo = is_array($grupoCel['lideres'] ?? null) ? $grupoCel['lideres'] : [];
                ?>
                <tr style="background:#eef2ff;">
                    <td colspan="7" style="font-weight:700; color:#3730a3;">
                        Ministerio: <?= htmlspecialchars($ministerioGrupo) ?>
                        · <?= count($lideresGrupo) ?> líder(es)
                        · <?= (int)($subtotalesGrupo['celulas'] ?? 0) ?> célula(s)
                    </td>
                </tr>
                <?php foreach ($lideresGrupo as $rowCel): ?>
                <tr>
                    <td style="color:#94a3b8;"><?= $nReporteCel++ ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars((string)($rowCel['lider'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($rowCel['tipo'] ?? '')) ?></td>
                    <td><?= (int)($rowCel['celulas'] ?? 0) ?></td>
                    <td><?= (int)($rowCel['asistentes'] ?? 0) ?></td>
                    <td><?= $renderMetricaCelulasClick(
                        (int)($rowCel['semanas_sin_entregar_sobre'] ?? 0),
                        is_array($rowCel['detalle_sin_entregar_sobre'] ?? null) ? $rowCel['detalle_sin_entregar_sobre'] : [],
                        'Semanas sin entregar sobre',
                        (string)($rowCel['lider'] ?? ''),
                        'sin_sobre'
                    ) ?></td>
                    <td><?= $renderMetricaCelulasClick(
                        (int)($rowCel['semanas_sin_reportar'] ?? 0),
                        is_array($rowCel['detalle_sin_reportar'] ?? null) ? $rowCel['detalle_sin_reportar'] : [],
                        'Semanas sin reportar célula',
                        (string)($rowCel['lider'] ?? ''),
                        'sin_reportar'
                    ) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#f8fafc; font-weight:600;">
                    <td colspan="3" style="text-align:right;">Subtotal <?= htmlspecialchars($ministerioGrupo) ?></td>
                    <td><?= (int)($subtotalesGrupo['celulas'] ?? 0) ?></td>
                    <td><?= (int)($subtotalesGrupo['asistentes'] ?? 0) ?></td>
                    <td><?= (int)($subtotalesGrupo['semanas_sin_entregar_sobre'] ?? 0) ?></td>
                    <td><?= (int)($subtotalesGrupo['semanas_sin_reportar'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc; font-weight:700;">
                    <td colspan="3">TOTAL GENERAL</td>
                    <td><?= (int)($reporteCelulasTotales['celulas'] ?? 0) ?></td>
                    <td><?= (int)($reporteCelulasTotales['asistentes'] ?? 0) ?></td>
                    <td><?= (int)($reporteCelulasTotales['semanas_sin_entregar_sobre'] ?? 0) ?></td>
                    <td><?= (int)($reporteCelulasTotales['semanas_sin_reportar'] ?? 0) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div id="dashCelDetalleModal" class="dash-cel-detalle-modal" hidden>
        <div class="dash-cel-detalle-backdrop" data-cerrar-detalle-cel="1"></div>
        <div class="dash-cel-detalle-panel" role="dialog" aria-modal="true" aria-labelledby="dashCelDetalleTitulo">
            <div class="dash-cel-detalle-head">
                <div>
                    <h4 id="dashCelDetalleTitulo">Detalle</h4>
                    <p class="dash-cel-detalle-sub" id="dashCelDetalleSub"></p>
                </div>
                <button type="button" class="dash-cel-detalle-cerrar" data-cerrar-detalle-cel="1" aria-label="Cerrar">&times;</button>
            </div>
            <div class="dash-cel-detalle-ayuda" id="dashCelDetalleAyuda" hidden></div>
            <p class="dash-cel-detalle-resumen" id="dashCelDetalleResumen"></p>
            <div class="table-container" id="dashCelDetalleContenedor"></div>
        </div>
    </div>

    <script>
    (function() {
        var modal = document.getElementById('dashCelDetalleModal');
        if (!modal) return;

        var titulo = document.getElementById('dashCelDetalleTitulo');
        var subtitulo = document.getElementById('dashCelDetalleSub');
        var ayuda = document.getElementById('dashCelDetalleAyuda');
        var resumen = document.getElementById('dashCelDetalleResumen');
        var contenedor = document.getElementById('dashCelDetalleContenedor');

        function cerrarModal() {
            modal.hidden = true;
            contenedor.innerHTML = '';
            ayuda.hidden = true;
            ayuda.textContent = '';
            resumen.textContent = '';
        }

        function escapeHtml(text) {
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function iconoSituacion(item) {
            if (item.icono === 'sobre') {
                return '<span class="situacion-icon is-sobre" title="Sobre no entregado">S</span>';
            }
            return '<span class="situacion-icon is-reporte" title="Sin reporte">R</span>';
        }

        function abrirModal(payload) {
            if (!payload || !Array.isArray(payload.items)) return;

            var tipo = payload.tipo === 'sin_sobre' ? 'sin_sobre' : 'sin_reportar';
            var total = payload.total || payload.items.length;

            titulo.textContent = payload.titulo || 'Detalle';
            subtitulo.textContent = payload.lider ? ('Líder: ' + payload.lider) : '';

            ayuda.className = 'dash-cel-detalle-ayuda ' + (tipo === 'sin_sobre' ? 'is-sobre' : 'is-reporte');
            ayuda.innerHTML = '<strong>¿Qué significa esto?</strong>' + escapeHtml(payload.ayuda || '');
            ayuda.hidden = false;

            resumen.textContent = total === 1
                ? '1 semana pendiente en el periodo consultado'
                : (total + ' semanas pendientes en el periodo consultado');

            if (payload.items.length === 0) {
                contenedor.innerHTML = '<div class="dash-cel-detalle-vacio">No hay semanas para mostrar.</div>';
                modal.hidden = false;
                return;
            }

            var mostrarCelula = payload.items.some(function(item) { return !!item.celula; });
            var filas = payload.items.map(function(item, index) {
                var periodo = escapeHtml(item.periodo || ('Semana ' + (item.etiqueta || item.inicio || '')));
                var fechas = escapeHtml(item.etiqueta || '');
                var celula = item.celula
                    ? '<span class="celula-tag">' + escapeHtml(item.celula) + '</span>'
                    : '<span style="color:#94a3b8;">—</span>';
                var situacion = escapeHtml(item.situacion || '');

                return '<tr>'
                    + '<td class="col-num">' + (index + 1) + '</td>'
                    + '<td class="col-periodo"><p class="periodo-principal">' + periodo + '</p>'
                    + (fechas ? '<p class="periodo-fechas">' + fechas + '</p>' : '')
                    + '</td>'
                    + (mostrarCelula ? '<td>' + celula + '</td>' : '')
                    + '<td><span class="situacion-tag">' + iconoSituacion(item) + '<span>' + situacion + '</span></span></td>'
                    + '</tr>';
            }).join('');

            contenedor.innerHTML = '<table class="dash-cel-detalle-table"><thead><tr>'
                + '<th class="col-num">#</th>'
                + '<th>Semana</th>'
                + (mostrarCelula ? '<th>Célula</th>' : '')
                + '<th>Qué ocurrió</th>'
                + '</tr></thead><tbody>' + filas + '</tbody></table>';

            modal.hidden = false;
        }

        document.addEventListener('click', function(ev) {
            var btn = ev.target.closest('.dash-cel-metrica-btn');
            if (btn && btn.dataset.detalle) {
                try {
                    abrirModal(JSON.parse(btn.dataset.detalle));
                } catch (e) {}
                return;
            }
            if (ev.target.closest('[data-cerrar-detalle-cel="1"]')) {
                cerrarModal();
            }
        });

        document.addEventListener('keydown', function(ev) {
            if (ev.key === 'Escape' && !modal.hidden) {
                cerrarModal();
            }
        });
    })();
    </script>
</div>
<?php endif; ?>

<!-- ── Tabla semáforo por ministerio ──────────────────────────────────────── -->
<?php if (!empty($ministeriosConMeta)): ?>
<div class="card report-card" style="margin-bottom:22px; padding:18px;">
    <h4 style="margin:0 0 14px 0; font-size:.97rem; color:#374151;">
        Cumplimiento de meta por ministerio · <?= htmlspecialchars((string)($cumplimientoMetas['titulo'] ?? 'Semestre actual')) ?>
    </h4>
    <small style="color:#64748b; display:block; margin-bottom:12px;">
        Meta del semestre = parte de la meta anual configurada (S1 ene–jun · S2 jul–dic). Semáforo: Verde ≥ 75 % · Amarillo 40–74 % · Rojo &lt; 40 %
    </small>
    <div class="table-container">
        <table class="dash-min-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ministerio</th>
                    <th>Meta semestre</th>
                    <th>Meta anual</th>
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
                    <td style="color:#64748b;"><?= (int)($row['meta_anual'] ?? 0) ?></td>
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
                    <td>—</td>
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
                labels: ['GI', 'GC', 'FV', 'V', 'UC'],
                datasets: [{
                    label: 'G12-GANAR',
                    data: [<?= $g12GI ?>, <?= $g12GC ?>, <?= $g12FV ?>, <?= $g12V ?>, <?= $g12UC ?>],
                    backgroundColor: ['#eab308', '#dc2626', '#16a34a', '#3b82f6', '#7c3aed'],
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
                                const labels = ['Ganados en iglesia','Ganados en célula','Fonovisita','Visita','Ubicados en célula'];
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
    const prevBtn = document.getElementById('dashMetasPrev');
    const nextBtn = document.getElementById('dashMetasNext');
    if (slidesWrap && dotsWrap) {
        const slides = Array.from(slidesWrap.querySelectorAll('.dash-metas-slide'));
        if (slides.length > 0) {
            let current = 0;
            let timer = null;
            const AUTOPLAY_MS = 60000;

            const activar = (index) => {
                current = index;
                slides.forEach((slide, idx) => {
                    slide.classList.toggle('is-active', idx === current);
                });
                Array.from(dotsWrap.querySelectorAll('.dash-metas-dot')).forEach((dot, idx) => {
                    dot.classList.toggle('is-active', idx === current);
                });
            };

            const reiniciarAuto = () => {
                if (timer) {
                    clearInterval(timer);
                }
                timer = setInterval(() => activar((current + 1) % slides.length), AUTOPLAY_MS);
            };

            slides.forEach((_, idx) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'dash-metas-dot' + (idx === 0 ? ' is-active' : '');
                dot.setAttribute('aria-label', 'Ir a vista ' + (idx + 1));
                dot.addEventListener('click', () => {
                    activar(idx);
                    reiniciarAuto();
                });
                dotsWrap.appendChild(dot);
            });

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    activar((current - 1 + slides.length) % slides.length);
                    reiniciarAuto();
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    activar((current + 1) % slides.length);
                    reiniciarAuto();
                });
            }

            activar(0);
            reiniciarAuto();
        }
    }
})();
</script>
<script>
function dashAlCambiarMinisterio(select) {
    const form = select.form;
    const lider = form ? form.querySelector('select[name="lider"]') : null;
    if (lider) {
        lider.value = '';
    }
    if (form) {
        form.submit();
    }
}
</script>

<?php include VIEWS . '/layout/footer.php'; ?>

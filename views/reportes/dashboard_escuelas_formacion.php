<?php include VIEWS . '/layout/header.php'; ?>
<?php
require_once APP . '/Helpers/DashboardSelector.php';
require_once APP . '/Helpers/ProgramasNavegacion.php';

$tituloDashboard = (string)($titulo_dashboard ?? 'Dashboard Escuelas');
$lineaDashboard = (string)($linea_dashboard ?? 'universidad_vida');
$dashModuloActivo = $lineaDashboard === 'capacitacion_destino' ? 'capacitacion_destino' : 'universidad_vida';
$dashModuloTitulo = DashboardSelector::etiquetaActivo($dashModuloActivo);

$rutaDashboard = (string)($ruta_dashboard ?? 'reportes/dashboard-escuelas-uv');
$anio = (int)($anio ?? date('Y'));
$mes = (int)($mes ?? date('n'));
$filtroMinisterio = (string)($filtro_ministerio ?? '');
$filtroLider = (string)($filtro_lider ?? '');
$filtroEncuentroUv = (string)($filtro_encuentro_uv ?? '');
$ministeriosDisp = (array)($ministerios_disponibles ?? []);
$lideresDisp = (array)($lideres_disponibles ?? []);
$metaPorLider = (int)($meta_por_lider ?? 6);
$resumen = (array)($resumen_lideres ?? []);
$lideresHombre = (array)($lideres_hombre ?? []);
$lideresMujer = (array)($lideres_mujer ?? []);
$lideresJoven = (array)($lideres_joven ?? []);
$lideresTeen = (array)($lideres_teen ?? []);
$lideresOtros = (array)($lideres_otros ?? []);
$fechaInicioMes = (string)($fecha_inicio_mes ?? date('Y-m-01'));
$fechaFinMes = (string)($fecha_fin_mes ?? date('Y-m-t'));
$diaTranscurrido = (int)($dia_transcurrido ?? date('j'));
$diasMes = (int)($dias_mes ?? date('t'));
$dashboardMetasMinisterio = (array)($dashboard_metas_ministerio ?? ['items' => []]);
$reporteUvMinisterios = (array)($reporte_uv_ministerios ?? []);
$tablaPagosUv = (array)($tabla_pagos_uv ?? []);
$tablaPagosUvLiderCelula = (array)($tabla_pagos_uv_lider_celula ?? []);
$tablaPagosUvModo = (string)($tabla_pagos_uv_modo ?? 'mensual');
$tablaUvModoConsolidar = (array)($tabla_uv_modo_consolidar ?? []);
$tablaCapModoConsolidar = (array)($tabla_cap_modo_consolidar ?? []);
$tablaPagosCap = (array)($tabla_pagos_cap ?? []);
$totalInscripcionesCapPeriodo = (int)($total_inscripciones_cap_periodo ?? 0);
$totalAsistenciasCap = (int)($total_asistencias_cap ?? 0);
$detalleLideresMinisterioUv = (array)($detalle_lideres_ministerio_uv ?? []);
$nombreMinisterioFiltrado = trim((string)($nombre_ministerio_filtrado ?? ''));

$inscritos = (int)($resumen['inscritos'] ?? 0);
$metaTotal = (int)($resumen['meta'] ?? 0);
$esperadoHoy = (int)($resumen['esperado'] ?? 0);
$avancePct = (int)($resumen['avance_pct'] ?? 0);
$justoATiempo = !empty($resumen['justo_a_tiempo']);

$labelLinea = $lineaDashboard === 'capacitacion_destino' ? 'Capacitación Destino' : 'Universidad de la Vida';

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];

/** Clave estable para filtrar por ministerio en cada tabla (cliente). */
$dashSlugMinisterio = static function($nombre) {
    $s = strtolower(trim(preg_replace('/\s+/u', ' ', (string)$nombre)));
    return $s === '' ? 'sin-ministerio' : $s;
};

$dashAttrsLeaderRow = static function(array $row) use ($dashSlugMinisterio) {
    $insG = (int)($row['inscritos_grupo'] ?? $row['inscritos_mes'] ?? 0);
    $pagG = (int)($row['pagados_grupo'] ?? $row['pagados_lider'] ?? 0);
    $dh = (int)($row['inscritos_hombres_lider'] ?? 0);
    $dm = (int)($row['inscritos_mujeres_lider'] ?? 0);
    $dj = (int)($row['inscritos_jovenes_lider'] ?? 0);
    $dt = (int)($row['inscritos_teens_lider'] ?? 0);
    $slug = $dashSlugMinisterio($row['ministerio'] ?? '');
    return ' data-dash-row="1" data-dash-profile="leader" data-dash-min="' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')
        . '" data-dash-h="' . $dh . '" data-dash-m="' . $dm . '" data-dash-j="' . $dj . '" data-dash-t="' . $dt
        . '" data-dash-ins="' . $insG . '" data-dash-pag="' . $pagG . '"';
};

$dashSlugMinisterioFiltradoUv = $dashSlugMinisterio($nombreMinisterioFiltrado !== '' ? $nombreMinisterioFiltrado : 'ministerio');

$dashAttrsDetalleRow = static function(array $det) use ($dashSlugMinisterioFiltradoUv) {
    $ins = (int)($det['inscritos'] ?? 0);
    $pag = (int)($det['pagados'] ?? 0);
    $dh = (int)($det['hombres'] ?? 0);
    $dm = (int)($det['mujeres'] ?? 0);
    $dj = (int)($det['jovenes'] ?? 0);
    return ' data-dash-row="1" data-dash-profile="detalle" data-dash-min="' . htmlspecialchars($dashSlugMinisterioFiltradoUv, ENT_QUOTES, 'UTF-8')
        . '" data-dash-h="' . $dh . '" data-dash-m="' . $dm . '" data-dash-j="' . $dj . '" data-dash-t="0"'
        . ' data-dash-ins="' . $ins . '" data-dash-pag="' . $pag . '"';
};

$dashAttrsUvMinRow = static function(array $fila) use ($dashSlugMinisterio) {
    $h = (int)($fila['hombres'] ?? 0);
    $m = (int)($fila['mujeres'] ?? 0);
    $j = (int)($fila['jovenes'] ?? 0);
    $tot = (int)($fila['total'] ?? 0);
    $slug = $dashSlugMinisterio($fila['ministerio'] ?? '');
    return ' data-dash-row="1" data-dash-profile="uv-ministerio" data-dash-min="' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')
        . '" data-dash-h="' . $h . '" data-dash-m="' . $m . '" data-dash-j="' . $j . '" data-dash-t="0"'
        . ' data-dash-ins="' . $tot . '" data-dash-pag="0" data-dash-skip-pago="1"';
};

$dashAttrsPagosRow = static function(array $fila) use ($dashSlugMinisterio) {
    $ins = (int)($fila['Inscritos'] ?? 0);
    $pag = (int)($fila['Pagados'] ?? 0);
    $insH = (int)($fila['Inscritos_Hombres'] ?? 0);
    $insM = (int)($fila['Inscritos_Mujeres'] ?? 0);
    $insJ = (int)($fila['Inscritos_Jovenes'] ?? 0);
    $insT = (int)($fila['Inscritos_Teens'] ?? 0);
    $slug = $dashSlugMinisterio($fila['Ministerio'] ?? '');
    return ' data-dash-row="1" data-dash-profile="pagos" data-dash-min="' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')
        . '" data-dash-h="' . $insH . '" data-dash-m="' . $insM . '" data-dash-j="' . $insJ . '" data-dash-t="' . $insT
        . '" data-dash-ins="' . $ins . '" data-dash-pag="' . $pag . '"';
};
?>

<style>
.dashboard-escuelas-wrap { display: flex; flex-direction: column; gap: 16px; }
.dash-head { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; align-items: flex-start; }
.dash-head h2 { margin: 0; }
.dash-toolbar { display: flex; gap: 8px; flex-wrap: wrap; }
.table-actions { display: flex; justify-content: flex-end; margin-bottom: 6px; }
.btn-tabla-export { font-size: 0.74rem; padding: 4px 8px; border-radius: 8px; }

.dash-card {
    background: #fff;
    border: 1px solid #dbe7f3;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
    padding: 14px;
}

.filters-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
.filters-form .group { display: flex; flex-direction: column; gap: 4px; }
.filters-form select { min-width: 160px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px; }

.table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.leader-table { width: 100%; border-collapse: collapse; min-width: 680px; table-layout: auto; }
.leader-table th, .leader-table td { border-bottom: 1px solid #eef2f7; padding: 6px 8px; text-align: left; font-size: 0.8rem; line-height: 1.25; }
.leader-table th { background: #f8fafc; color: #475569; font-size: 0.72rem; text-transform: uppercase; letter-spacing: .03em; }
.leader-table th:nth-child(1), .leader-table td:nth-child(1) { width: 30%; }
.leader-table th:nth-child(2), .leader-table td:nth-child(2) { width: 18%; }
.estado { border-radius: 999px; padding: 2px 8px; font-size: 0.72rem; font-weight: 700; }
.estado.verde { background: #dcfce7; color: #166534; }
.estado.amarillo { background: #fef3c7; color: #92400e; }
.estado.rojo { background: #fee2e2; color: #991b1b; }

.section-title { margin: 0 0 10px 0; font-size: 0.95rem; color: #334155; }

.dash-table-tool-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 8px;
}
.dash-inline-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: flex-end;
}
.dash-inline-filters .group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.dash-inline-filters label,
.dash-inline-filters .dash-group-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
}
.dash-inline-filters .dash-group-label {
    display: block;
    margin-bottom: 2px;
}
.dash-inline-filters select {
    min-width: 118px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 4px 8px;
    font-size: 0.8rem;
    background: #fff;
}
.dash-inline-filters select:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
.dash-filter-hint {
    font-size: 0.72rem;
    color: #94a3b8;
    margin: 0 0 6px 0;
}
.dash-segment-checks {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 14px;
    align-items: center;
    max-width: 380px;
}
.dash-segment-checks label.dash-segment-opt {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.78rem;
    font-weight: 500;
    color: #334155;
    cursor: pointer;
    margin: 0;
}
.dash-segment-checks .dash-segment-opt input {
    margin: 0;
    flex-shrink: 0;
}
.dash-segment-hint {
    font-size: 0.68rem;
    color: #94a3b8;
    font-weight: 400;
    margin-top: 2px;
}

.uv-reporte-principal { border: 2px solid #c5daf5; background: linear-gradient(180deg, #f8fbff 0%, #fff 100%); }
.uv-reporte-title { margin: 0 0 6px; font-size: 1.2rem; color: #1e3a6e; }
.uv-reporte-sub { margin: 0 0 4px; font-size: 0.88rem; color: #475569; line-height: 1.45; }
.uv-reporte-sub-muted { font-size: 0.8rem; color: #64748b; }
.uv-kpi-row { display: flex; flex-wrap: wrap; gap: 10px; margin: 14px 0; }
.uv-kpi { flex: 1 1 120px; min-width: 100px; background: #fff; border: 1px solid #dbe7f3; border-radius: 10px; padding: 10px 12px; }
.uv-kpi span { display: block; font-size: 0.72rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; }
.uv-kpi strong { font-size: 1.35rem; color: #1e293b; }
.uv-kpi-ok strong { color: #166534; }
.uv-kpi-warn strong { color: #b45309; }
.uv-kpi-total strong { color: #1e4a89; }
.uv-kpi-pct { display: block; font-size: 0.78rem; font-style: normal; color: #64748b; margin-top: 2px; font-weight: 600; }
.uv-encuentro-kpis { border: 2px solid #bfdbfe; background: linear-gradient(180deg, #f0f7ff 0%, #fff 100%); margin-bottom: 14px; }
.uv-dash-kpi-note { margin: 0 0 12px; font-size: 0.82rem; color: #475569; line-height: 1.45; }
.uv-dash-kpi-empty { margin: 8px 0 0; font-size: 0.85rem; color: #94a3b8; }
.uv-kpi-row-encuentro .uv-kpi { flex: 1 1 160px; }
.uv-kpi-clickable {
    cursor: pointer;
    text-align: left;
    font: inherit;
    width: auto;
    transition: box-shadow .15s ease, border-color .15s ease, transform .1s ease;
}
.uv-kpi-clickable:hover { box-shadow: 0 2px 8px rgba(30, 74, 137, 0.12); border-color: #93c5fd; }
.uv-kpi-clickable:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
.uv-kpi-clickable.is-active {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    transform: translateY(-1px);
}
.uv-dash-kpi-hint { margin: 0 0 10px; font-size: 0.78rem; color: #64748b; }
.uv-kpi-desglose-panel {
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid #dbe7f3;
}
.uv-kpi-vista-detalle[hidden] { display: none !important; }
#uvKpiVistaIndicadores[hidden] { display: none !important; }
.uv-kpi-detalle-toolbar {
    margin: 0 0 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid #dbe7f3;
}
.uv-kpi-btn-volver {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px 10px 14px;
    border: none;
    border-radius: 10px;
    background: #1e4a89;
    color: #fff;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(30, 74, 137, 0.25);
    transition: background .15s ease, transform .1s ease;
}
.uv-kpi-btn-volver:hover {
    background: #163a6e;
    transform: translateY(-1px);
}
.uv-kpi-btn-volver:active {
    transform: translateY(0);
}
.uv-kpi-btn-volver-flecha {
    font-size: 1.25rem;
    line-height: 1;
    font-weight: 700;
}
.uv-kpi-detalle-titulo { margin: 0 0 8px; color: #1e3a6e; }
.uv-encuentro-kpis.is-detalle {
    border-color: #93c5fd;
}
.uv-kpi-desglose-volver {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 0 0 12px;
    padding: 6px 12px 6px 8px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #fff;
    color: #1e4a89;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s ease, border-color .15s ease;
}
.uv-kpi-desglose-volver:hover {
    background: #f0f7ff;
    border-color: #93c5fd;
}
.uv-kpi-desglose-volver-icon {
    font-size: 1.1rem;
    line-height: 1;
}
.uv-kpi-desglose-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}
.uv-kpi-desglose-head h5 { margin: 0; font-size: 0.95rem; color: #1e3a6e; }
.uv-kpi-desglose-cerrar {
    border: none;
    background: transparent;
    font-size: 1.4rem;
    line-height: 1;
    color: #64748b;
    cursor: pointer;
    padding: 0 4px;
}
.uv-kpi-desglose-cerrar:hover { color: #1e293b; }
.uv-kpi-desglose-note { margin: 0 0 10px; }
.dash-min-table { width:100%; border-collapse:collapse; font-size:.88rem; }
.dash-min-table th { background:#f1f5f9; padding:8px 10px; text-align:left; font-size:.78rem; color:#475569; font-weight:700; border-bottom:1px solid #e2e8f0; }
.dash-min-table td { padding:8px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.dash-min-table tr:last-child td { border-bottom:none; }
.uv-encuentro-bar { display: flex; height: 12px; border-radius: 999px; overflow: hidden; background: #e2e8f0; margin-top: 4px; }
.uv-encuentro-bar__asistio { background: linear-gradient(90deg, #22c55e, #16a34a); transition: width .3s ease; }
.uv-encuentro-bar__pendiente { background: linear-gradient(90deg, #fbbf24, #f59e0b); transition: width .3s ease; }
.uv-encuentro-bar-legend { display: flex; gap: 16px; margin-top: 8px; font-size: 0.75rem; color: #64748b; }
.uv-legend-asistio::before, .uv-legend-pendiente::before { content: ''; display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 6px; vertical-align: middle; }
.uv-legend-asistio::before { background: #22c55e; }
.uv-legend-pendiente::before { background: #f59e0b; }
.uv-reporte-table { min-width: 920px; }
.uv-reporte-table .uv-th-group { text-align: center; font-size: 0.7rem; }
.uv-th-ins { background: #e8f2fc !important; color: #1e4f8a !important; }
.uv-th-pag { background: #e8f7ee !important; color: #166534 !important; }
.uv-th-seg { text-align: center !important; font-size: 0.68rem !important; width: 48px; }
.uv-td-ministerio { font-weight: 600; color: #1e3a5f; min-width: 140px; }
.uv-num { text-align: center; font-variant-numeric: tabular-nums; }
.uv-num-pag { background: #f6fdf8; }
.uv-pend { font-weight: 600; color: #b45309; }
.uv-pct-ok { color: #166534; font-weight: 700; }
.uv-pct-mid { color: #b45309; font-weight: 700; }
.uv-pct-low { color: #b91c1c; font-weight: 700; }
.uv-tfoot-row th { background: #f1f5f9; font-weight: 700; }
.dash-col-seg-hidden { display: none !important; }
.dash-uv-detalle { margin-top: 8px; }
.dash-uv-detalle summary { cursor: pointer; font-weight: 600; color: #475569; padding: 8px 0; }
.uv-dash-intro { margin: 0 0 12px; font-size: 0.88rem; color: #475569; line-height: 1.45; }
.uv-dash-intro-compact { margin: 0 0 10px; font-size: 0.82rem; line-height: 1.4; }
.uv-dash-alineacion-inline { display: inline; margin-left: 0.35rem; }
.uv-dash-alineacion-inline a { color: #1d4ed8; text-decoration: none; }
.uv-dash-alineacion-inline a:hover { text-decoration: underline; }
.uv-dash-tables-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
}
.uv-dash-table-card {
    margin-bottom: 0;
    padding: 10px 12px;
    width: 100%;
    min-width: 0;
}
.uv-dash-table-note { color: #64748b; display: block; margin-bottom: 4px; font-size: 0.75rem; }
.uv-dash-table-card .section-title { margin-bottom: 4px; font-size: 0.9rem; }
.uv-dash-table-card .table-wrap {
    width: 100%;
    overflow-x: auto;
}
.uv-simple-table.leader-table {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    table-layout: fixed;
}
.uv-simple-table col.uv-col-min { width: 28%; }
.uv-simple-table col.uv-col-num { width: 8.5%; }
.uv-simple-table col.uv-col-num-wide { width: 11%; }
.uv-simple-table.leader-table th:nth-child(1),
.uv-simple-table.leader-table td:nth-child(1) {
    text-align: left;
    white-space: normal;
    overflow-wrap: anywhere;
}
.uv-simple-table.leader-table th:nth-child(n+2),
.uv-simple-table.leader-table td:nth-child(n+2) {
    text-align: center;
    padding-left: 2px;
    padding-right: 2px;
}
.uv-simple-table.leader-table th,
.uv-simple-table.leader-table td {
    padding: 3px 5px;
    vertical-align: middle;
    font-size: 0.76rem;
    line-height: 1.15;
}
.uv-simple-table.leader-table thead th {
    text-align: center;
    line-height: 1.1;
    font-size: 0.66rem;
    letter-spacing: 0.02em;
    white-space: nowrap;
    word-break: normal;
    hyphens: none;
}
.uv-simple-table.leader-table thead th:first-child {
    text-align: left;
}
.uv-simple-table.leader-table tfoot th {
    text-align: center;
    font-variant-numeric: tabular-nums;
}
.uv-simple-table.leader-table tfoot th:first-child {
    text-align: left;
}
.uv-simple-table .uv-num { text-align: center !important; font-variant-numeric: tabular-nums; }
.uv-simple-table .uv-num-total { font-weight: 600; }
.uv-simple-table .uv-num-pag { font-weight: 600; color: #166534; }
.uv-dash-alineacion { margin: 0 0 12px; font-size: 0.8rem; color: #64748b; }
.uv-dash-alineacion a { color: #1d4ed8; }
.uv-min-clickable { cursor: pointer; color: #1e40af; text-decoration: underline; text-underline-offset: 2px; }
.uv-min-clickable:hover { color: #1e3a8a; }
.uv-detalle-modal { position: fixed; inset: 0; z-index: 1200; display: flex; align-items: center; justify-content: center; padding: 16px; }
.uv-detalle-modal[hidden] { display: none !important; }
.uv-detalle-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.45); }
.uv-detalle-panel { position: relative; z-index: 1; background: #fff; border-radius: 12px; max-width: 960px; width: 100%; max-height: 90vh; overflow: auto; padding: 16px; box-shadow: 0 12px 40px rgba(15, 23, 42, 0.2); }
.uv-detalle-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
.uv-detalle-head h4 { margin: 0; font-size: 1.1rem; color: #1e293b; }
.uv-detalle-sub { margin: 4px 0 0; font-size: 0.82rem; color: #64748b; }
.uv-detalle-cerrar { border: none; background: #f1f5f9; width: 32px; height: 32px; border-radius: 8px; font-size: 1.25rem; line-height: 1; cursor: pointer; color: #475569; }
.uv-detalle-filtros { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
.uv-det-filt { border: 1px solid #cbd5e1; background: #fff; border-radius: 999px; padding: 4px 12px; font-size: 0.78rem; cursor: pointer; }
.uv-det-filt.active { background: #1e40af; border-color: #1e40af; color: #fff; }
.uv-detalle-alineacion { font-size: 0.72rem; color: #94a3b8; margin: 0 0 10px; line-height: 1.4; }
.uv-detalle-loading { padding: 20px; text-align: center; color: #64748b; }
.uv-detalle-vacio { padding: 16px; text-align: center; color: #94a3b8; }
.uv-detalle-table-wrap { max-height: 50vh; }
.uv-det-ok { color: #166534; font-weight: 600; }
.uv-det-no { color: #94a3b8; }

@media (max-width: 768px) {
    .dashboard-escuelas-wrap { gap: 12px; }
    .dash-card { padding: 10px; }
    .dash-head h2 { font-size: 1.25rem; }
    .dash-toolbar { width: 100%; display: grid; grid-template-columns: 1fr; }
    .dash-toolbar .btn { width: 100%; }
    .filters-form { gap: 8px; }
    .filters-form .group { width: calc(50% - 4px); }
    .filters-form .group label { font-size: 0.78rem; }
    .filters-form select { min-width: 0; width: 100%; font-size: 0.82rem; padding: 5px 8px; }
    .leader-table { min-width: 560px; }
    .uv-dash-tables-grid { max-width: 100%; }
    .uv-simple-table.leader-table { min-width: 0; }
    .uv-simple-table.leader-table th,
    .uv-simple-table.leader-table td {
        padding: 2px 4px;
        font-size: 0.72rem;
    }
    .uv-simple-table.leader-table thead th { font-size: 0.62rem; }
}
.uv-dash-table-card .dash-table-tool-row {
    margin-bottom: 4px;
    gap: 6px;
}
.uv-dash-table-card .dash-inline-filters {
    gap: 4px;
}
.uv-dash-table-card .dash-filter-hint {
    margin-bottom: 4px;
}
.uv-dash-table-card .dash-segment-checks {
    max-width: none;
    gap: 4px 10px;
}
</style>

<?php
if (in_array($lineaDashboard, ['universidad_vida', 'capacitacion_destino'], true)) {
    ProgramasNavegacion::incluirPartial([
        'linea' => $lineaDashboard,
        'seccion' => 'dashboard',
        'forzar' => true,
    ]);
}
?>

<div class="dashboard-escuelas-wrap">
    <div class="dash-head">
        <div>
            <h2>Dashboard · <?= htmlspecialchars($dashModuloTitulo, ENT_QUOTES, 'UTF-8') ?></h2>
            <?php if ($lineaDashboard === 'universidad_vida'): ?>
                <small style="color:#64748b;">Universidad de la Vida · Inscripciones y pagos por ministerio (modo Consolidar)</small>
            <?php elseif ($lineaDashboard === 'capacitacion_destino'): ?>
                <small style="color:#64748b;">Capacitación Destino · Inscripciones y pagos por ministerio y nivel (modo Consolidar)</small>
            <?php else: ?>
                <small style="color:#64748b;">Módulo exclusivo: <?= htmlspecialchars($labelLinea) ?> · Meta por líder (hombre/mujer): <?= $metaPorLider ?> inscritos</small>
            <?php endif; ?>
        </div>
        <div class="dash-toolbar" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <?php
            DashboardSelector::incluirPartial([
                'activo' => $dashModuloActivo,
                'params' => [
                    'anio' => (int)($anio ?? date('Y')),
                    'ministerio' => (string)($filtroMinisterio ?? ''),
                    'lider' => (string)($filtroLider ?? ''),
                    'mes' => (int)($mes ?? date('n')),
                ],
            ]);
            ?>
        </div>
    </div>

    <div class="dash-card">
        <?php if ($lineaDashboard === 'universidad_vida'): ?>
            <p style="margin:0 0 10px;font-size:0.86rem;color:#475569;">
                Filtro por <strong>semestre</strong> (fecha de registro del inscripción, igual que Consolidar).
                Los pagos cuentan si están en la ficha <em>o</em> en movimientos de pago.
            </p>
        <?php elseif ($lineaDashboard === 'capacitacion_destino'): ?>
            <p style="margin:0 0 10px;font-size:0.86rem;color:#475569;">
                Las tablas de inscripciones y pagos usan la misma base que <strong>Consolidar</strong> (todos los niveles).
            </p>
        <?php endif; ?>
        <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="filters-form">
            <input type="hidden" name="url" value="<?= htmlspecialchars($rutaDashboard) ?>">

            <div class="group">
                <label>Año</label>
                <select name="anio" onchange="this.form.submit()">
                    <?php for ($y = (int)date('Y') + 1; $y >= 2023; $y--): ?>
                        <option value="<?= $y ?>" <?= $y === $anio ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <?php if ($lineaDashboard === 'universidad_vida'): ?>
                <?php $semestreUv = (int)($semestre_uv ?? 0); ?>
                <div class="group">
                    <label>Semestre</label>
                    <select name="semestre" onchange="this.form.submit()">
                        <option value="1" <?= $semestreUv === 1 ? 'selected' : '' ?>>Semestre 1 (Ene – Jun)</option>
                        <option value="2" <?= $semestreUv === 2 ? 'selected' : '' ?>>Semestre 2 (Jul – Dic)</option>
                    </select>
                </div>
            <?php else: ?>
                <div class="group">
                    <label>Mes</label>
                    <select name="mes" onchange="this.form.submit()">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>><?= htmlspecialchars($meses[$m] ?? (string)$m) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="group">
                <label>Ministerio</label>
                <select name="ministerio" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach ($ministeriosDisp as $min): ?>
                        <option value="<?= (int)($min['Id_Ministerio'] ?? 0) ?>" <?= (string)($min['Id_Ministerio'] ?? '') === $filtroMinisterio ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($min['Nombre_Ministerio'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="group">
                <label>Líder</label>
                <select name="lider" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach ($lideresDisp as $lid): ?>
                        <option value="<?= (int)($lid['Id_Persona'] ?? 0) ?>" <?= (string)($lid['Id_Persona'] ?? '') === $filtroLider ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($lid['Nombre_Completo'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($lineaDashboard === 'universidad_vida'): ?>
            <div class="group">
                <label for="dash-filtro-encuentro">Asistencia encuentro</label>
                <select name="filtro_encuentro" id="dash-filtro-encuentro" onchange="this.form.submit()"
                    title="Excluye o filtra inscritos según asistencia a día 1 o 2 del encuentro (clases 5 y 6)">
                    <option value="todos" <?= $filtroEncuentroUv === '' || $filtroEncuentroUv === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <option value="excluir_asistieron" <?= $filtroEncuentroUv === 'excluir_asistieron' ? 'selected' : '' ?>>Excluir quienes ya asistieron (día 1 y/o 2)</option>
                    <option value="sin_encuentro" <?= $filtroEncuentroUv === 'sin_encuentro' ? 'selected' : '' ?>>Solo sin asistencia al encuentro</option>
                    <option value="con_al_menos_uno" <?= $filtroEncuentroUv === 'con_al_menos_uno' ? 'selected' : '' ?>>Solo con asistencia (al menos un día)</option>
                    <option value="con_ambos" <?= $filtroEncuentroUv === 'con_ambos' ? 'selected' : '' ?>>Solo asistieron ambos días</option>
                </select>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($lineaDashboard === 'universidad_vida'): ?>
        <?php include VIEWS . '/reportes/partials/uv_dashboard_simple.php'; ?>
    <?php elseif ($lineaDashboard === 'capacitacion_destino'): ?>
        <?php include VIEWS . '/reportes/partials/cap_dashboard_simple.php'; ?>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
(function() {
    const contenedorDashboard = document.querySelector('.dashboard-escuelas-wrap');

    function slugifyTitulo(texto) {
        return String(texto || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'tabla';
    }

    function obtenerTituloDeTabla(wrap, idx) {
        let nodo = wrap.previousElementSibling;
        while (nodo) {
            const esTitulo = /^H[1-6]$/i.test(nodo.tagName || '');
            if (esTitulo) {
                const t = String(nodo.textContent || '').trim();
                if (t !== '') {
                    return t;
                }
            }
            nodo = nodo.previousElementSibling;
        }

        const card = wrap.closest('.dash-card');
        if (card) {
            const firstTitle = card.querySelector('h5.section-title, h4.section-title, h4, h5');
            const t = firstTitle ? String(firstTitle.textContent || '').trim() : '';
            if (t !== '') {
                return t;
            }
        }

        return 'Tabla ' + idx;
    }

    function labelMinisterioDesdeFila(table, tr) {
        const prof = tr.getAttribute('data-dash-profile') || '';
        if (prof === 'leader') {
            return tr.cells[1] ? String(tr.cells[1].textContent || '').trim() : '';
        }
        if (prof === 'detalle') {
            const fixed = table.getAttribute('data-dash-ministry-label') || '';
            return fixed.trim();
        }
        return tr.cells[0] ? String(tr.cells[0].textContent || '').trim() : '';
    }

    function dashGenConfig(genMode) {
        if (genMode === 'ajt') {
            return {
                keys: ['a', 'j', 't'],
                segments: [['a', 'Adultos'], ['j', 'Jóvenes'], ['t', 'Teens']],
                label: 'Segmento',
                hintUv: 'H y M = por género (incluye jóvenes del mismo sexo). J y Teens = por edad. Sin marcar = todos. Si marcas varios, se suman las columnas elegidas.',
                hintDefault: 'Sin marcar = todas las columnas. Si marcas segmentos, se muestran esas columnas y filas con al menos uno de los marcados.'
            };
        }
        if (genMode === 'n123' || genMode === 'hmj') {
            return {
                keys: ['h', 'm', 'j'],
                segments: [['h', 'Nivel 1'], ['m', 'Nivel 2'], ['j', 'Nivel 3']],
                label: 'Nivel',
                hintUv: 'Sin marcar = todos los niveles. Si marcas niveles, se suman y Total / Asistencias / Pagos usan solo esos niveles.',
                hintDefault: 'Sin marcar = todos los niveles. Si marcas niveles, se muestran esas columnas y filas con inscritos en al menos uno de los niveles marcados.'
            };
        }
        return {
            keys: ['h', 'm', 'j', 't'],
            segments: [['h', 'Hombres'], ['m', 'Mujeres'], ['j', 'Jóvenes'], ['t', 'Teens']],
            label: 'Segmento',
            hintUv: 'H y M = por género (incluye jóvenes del mismo sexo). J y Teens = por edad. Sin marcar = todos. Si marcas varios, se suman las columnas elegidas.',
            hintDefault: 'Sin marcar = todas las columnas. Si marcas segmentos, se muestran esas columnas y filas con al menos uno de los marcados.'
        };
    }

    function construirOpcionesMinisterio(table) {
        const map = {};
        table.querySelectorAll('tbody tr[data-dash-row]').forEach(function(tr) {
            const slug = tr.getAttribute('data-dash-min') || '';
            if (!slug) {
                return;
            }
            if (!map[slug]) {
                map[slug] = labelMinisterioDesdeFila(table, tr) || slug;
            }
        });
        const slugs = Object.keys(map).sort(function(a, b) {
            return map[a].localeCompare(map[b], 'es');
        });
        return { map: map, slugs: slugs };
    }

    function obtenerInscritosYPagosFila(tr, table, genKeys) {
        const esPagos = table.getAttribute('data-dash-enable-pago') === '1'
            && tr.getAttribute('data-dash-skip-pago') !== '1';
        const tieneGen = genKeys && genKeys.length > 0;
        if (!tieneGen) {
            return {
                ins: parseInt(tr.getAttribute('data-dash-ins') || '0', 10) || 0,
                pag: parseInt(tr.getAttribute('data-dash-pag') || '0', 10) || 0
            };
        }
        let ins = 0;
        let pag = 0;
        genKeys.forEach(function(g) {
            ins += parseInt(tr.getAttribute('data-dash-' + g) || '0', 10) || 0;
            if (esPagos) {
                pag += parseInt(tr.getAttribute('data-dash-pag-' + g) || '0', 10) || 0;
            }
        });
        return { ins: ins, pag: pag };
    }

    function filaCoincide(tr, table) {
        if (!tr.hasAttribute('data-dash-row')) {
            return true;
        }
        const filt = table._dashFiltro || {};
        const slug = tr.getAttribute('data-dash-min') || '';
        if (filt.min && filt.min !== slug) {
            return false;
        }

        const genMode = table.getAttribute('data-dash-gen-mode') || 'hmjt';
        const genSel = filt.gen;
        const genKeys = (genSel !== 'all' && Array.isArray(genSel) && genSel.length) ? genSel : null;
        if (genKeys) {
            let coincideSegmento = false;
            if (genMode === 'ajt') {
                const a = parseInt(tr.getAttribute('data-dash-a') || '0', 10) || 0;
                const j = parseInt(tr.getAttribute('data-dash-j') || '0', 10) || 0;
                const t = parseInt(tr.getAttribute('data-dash-t') || '0', 10) || 0;
                for (let i = 0; i < genKeys.length; i++) {
                    const g = genKeys[i];
                    if (g === 'a' && a > 0) {
                        coincideSegmento = true;
                        break;
                    }
                    if (g === 'j' && j > 0) {
                        coincideSegmento = true;
                        break;
                    }
                    if (g === 't' && t > 0) {
                        coincideSegmento = true;
                        break;
                    }
                }
            } else {
                const h = parseInt(tr.getAttribute('data-dash-h') || '0', 10) || 0;
                const m = parseInt(tr.getAttribute('data-dash-m') || '0', 10) || 0;
                const j = parseInt(tr.getAttribute('data-dash-j') || '0', 10) || 0;
                const t = parseInt(tr.getAttribute('data-dash-t') || '0', 10) || 0;
                for (let i = 0; i < genKeys.length; i++) {
                    const g = genKeys[i];
                    if (g === 'h' && h > 0) {
                        coincideSegmento = true;
                        break;
                    }
                    if (g === 'm' && m > 0) {
                        coincideSegmento = true;
                        break;
                    }
                    if (g === 'j' && j > 0) {
                        coincideSegmento = true;
                        break;
                    }
                    if (g === 't' && t > 0) {
                        coincideSegmento = true;
                        break;
                    }
                }
            }
            if (!coincideSegmento) {
                return false;
            }
        }

        const enablePago = table.getAttribute('data-dash-enable-pago') === '1';
        if (enablePago && tr.getAttribute('data-dash-skip-pago') !== '1') {
            const cifras = obtenerInscritosYPagosFila(tr, table, genKeys);
            const ins = cifras.ins;
            const pag = cifras.pag;
            const pend = Math.max(0, ins - pag);
            const p = filt.pago || 'all';
            if (genKeys && ins <= 0) {
                return false;
            }
            if (p === 'pend' && pend <= 0) {
                return false;
            }
            if (p === 'ok' && (ins <= 0 || pend > 0)) {
                return false;
            }
        }

        return true;
    }

    function aplicarVisibilidadColumnasSegmento(table) {
        const celdasSeg = table.querySelectorAll('[data-dash-seg]');
        if (!celdasSeg.length) {
            return;
        }
        const genMode = table.getAttribute('data-dash-gen-mode') || 'hmjt';
        const allKeys = dashGenConfig(genMode).keys;
        const filt = table._dashFiltro || {};
        let keysVisibles = allKeys.slice();
        if (filt.gen !== 'all' && Array.isArray(filt.gen) && filt.gen.length > 0) {
            keysVisibles = filt.gen.filter(function(k) {
                return allKeys.indexOf(k) !== -1;
            });
            if (!keysVisibles.length) {
                keysVisibles = allKeys.slice();
            }
        }
        const visSet = {};
        keysVisibles.forEach(function(k) {
            visSet[k] = true;
        });
        celdasSeg.forEach(function(el) {
            const seg = el.getAttribute('data-dash-seg');
            if (visSet[seg]) {
                el.classList.remove('dash-col-seg-hidden');
            } else {
                el.classList.add('dash-col-seg-hidden');
            }
        });
        const n = keysVisibles.length;
        const thIns = table.querySelector('[data-dash-colspan-ins]');
        const thPag = table.querySelector('[data-dash-colspan-pag]');
        if (thIns) {
            thIns.colSpan = Math.max(1, n);
        }
        if (thPag) {
            thPag.colSpan = Math.max(1, n);
        }
    }

    function obtenerSegmentosActivosTabla(table) {
        const genMode = table.getAttribute('data-dash-gen-mode') || 'hmjt';
        const allKeys = dashGenConfig(genMode).keys;
        const filt = table._dashFiltro || {};
        if (filt.gen !== 'all' && Array.isArray(filt.gen) && filt.gen.length > 0) {
            return filt.gen.filter(function(k) {
                return allKeys.indexOf(k) !== -1;
            });
        }
        return allKeys.slice();
    }

    function sumarSegmentosEnFila(tr, keys, prefijoAttr) {
        let suma = 0;
        keys.forEach(function(g) {
            const attr = prefijoAttr ? ('data-dash-' + prefijoAttr + g) : ('data-dash-' + g);
            suma += parseInt(tr.getAttribute(attr) || '0', 10) || 0;
        });
        return suma;
    }

    function sumarPendientesSegmentosEnFila(tr, keys) {
        return Math.max(0, sumarSegmentosEnFila(tr, keys, '') - sumarSegmentosEnFila(tr, keys, 'pag-'));
    }

    function valorColumnaExtraTablaUv(table, tr, keys) {
        const esPagos = table.id === 'tablaUvPagos' || table.id === 'tablaCapPagos' || table.id === 'tablaUvPagosLider';
        if (!esPagos) {
            return sumarSegmentosEnFila(tr, keys, 'asist-');
        }
        const pagoFiltro = (table._dashFiltro && table._dashFiltro.pago) ? table._dashFiltro.pago : 'all';
        if (pagoFiltro === 'pend') {
            return sumarPendientesSegmentosEnFila(tr, keys);
        }
        return sumarSegmentosEnFila(tr, keys, 'pag-');
    }

    function actualizarFilasUvPorSegmento(table) {
        if (!table.classList.contains('uv-simple-table')) {
            return;
        }
        const filt = table._dashFiltro || {};
        const genActivo = filt.gen !== 'all' && Array.isArray(filt.gen) && filt.gen.length > 0;
        const keys = obtenerSegmentosActivosTabla(table);
        const esPagos = table.id === 'tablaUvPagos' || table.id === 'tablaCapPagos' || table.id === 'tablaUvPagosLider';
        const esCapInscritos = table.id === 'tablaCapInscripciones';
        const prefExtra = esPagos ? 'pag-' : 'asist-';

        table.querySelectorAll('tbody tr[data-dash-row]').forEach(function(tr) {
            const tdTotal = tr.querySelector('.uv-dash-col-total');
            const tdExtra = tr.querySelector('.uv-dash-col-extra');
            if (!genActivo) {
                if (tdTotal) {
                    tdTotal.textContent = String(parseInt(tr.getAttribute('data-dash-ins') || '0', 10) || 0);
                }
                if (tdExtra) {
                    tdExtra.textContent = String(parseInt(tr.getAttribute('data-dash-extra') || '0', 10) || 0);
                }
                return;
            }
            if (tdTotal) {
                tdTotal.textContent = String(sumarSegmentosEnFila(tr, keys, ''));
            }
            if (tdExtra) {
                if (esCapInscritos) {
                    tdExtra.textContent = String(parseInt(tr.getAttribute('data-dash-extra') || '0', 10) || 0);
                } else {
                    tdExtra.textContent = String(valorColumnaExtraTablaUv(table, tr, keys));
                }
            }
        });
        const thPag = table.querySelector('thead .uv-dash-col-extra');
        if (thPag && esPagos) {
            const pagoFiltro = (table._dashFiltro && table._dashFiltro.pago) ? table._dashFiltro.pago : 'all';
            thPag.textContent = pagoFiltro === 'pend' ? 'Pend.' : 'Pagos';
        }
    }

    function recalcularTotalesUv(table) {
        if (!table.classList.contains('uv-simple-table')) {
            return;
        }
        const tfoot = table.querySelector('tfoot.js-dash-tfoot tr');
        if (!tfoot) {
            return;
        }
        const filt = table._dashFiltro || {};
        const genActivo = filt.gen !== 'all' && Array.isArray(filt.gen) && filt.gen.length > 0;
        const keys = obtenerSegmentosActivosTabla(table);
        const esPagos = table.id === 'tablaUvPagos' || table.id === 'tablaCapPagos' || table.id === 'tablaUvPagosLider';
        const esCapInscritos = table.id === 'tablaCapInscripciones';
        const prefExtra = esPagos ? 'pag-' : 'asist-';
        const sums = { h: 0, m: 0, j: 0, t: 0, a: 0, total: 0, extra: 0 };

        table.querySelectorAll('tbody tr[data-dash-row]').forEach(function(tr) {
            if (tr.style.display === 'none') {
                return;
            }
            keys.forEach(function(g) {
                sums[g] += parseInt(tr.getAttribute('data-dash-' + g) || '0', 10) || 0;
            });
            if (genActivo) {
                sums.total += sumarSegmentosEnFila(tr, keys, '');
                if (esCapInscritos) {
                    sums.extra += parseInt(tr.getAttribute('data-dash-extra') || '0', 10) || 0;
                } else {
                    sums.extra += valorColumnaExtraTablaUv(table, tr, keys);
                }
            } else {
                sums.total += parseInt(tr.getAttribute('data-dash-ins') || '0', 10) || 0;
                sums.extra += parseInt(tr.getAttribute('data-dash-extra') || '0', 10) || 0;
            }
        });

        keys.forEach(function(g) {
            const th = tfoot.querySelector('[data-dash-seg="' + g + '"]');
            if (th) {
                th.textContent = String(sums[g]);
            }
        });
        const thTotal = tfoot.querySelector('.uv-dash-col-total');
        const thExtra = tfoot.querySelector('.uv-dash-col-extra');
        if (thTotal) {
            thTotal.textContent = String(sums.total);
        }
        if (thExtra) {
            thExtra.textContent = String(sums.extra);
        }
    }

    function aplicarFiltrosTabla(table) {
        const tbody = table.querySelector('tbody');
        const tfoot = table.querySelector('tfoot.js-dash-tfoot');
        if (!tbody) {
            return;
        }

        const esUvSimple = table.classList.contains('uv-simple-table');
        let activo = false;
        const f = table._dashFiltro || {};
        const genActivo = f.gen !== 'all' && Array.isArray(f.gen) && f.gen.length > 0;
        if (f.min || genActivo || (f.pago && f.pago !== 'all')) {
            activo = true;
        }

        tbody.querySelectorAll('tr').forEach(function(tr) {
            if (!tr.hasAttribute('data-dash-row')) {
                tr.style.display = '';
                return;
            }
            const ok = filaCoincide(tr, table);
            tr.style.display = ok ? '' : 'none';
        });

        aplicarVisibilidadColumnasSegmento(table);
        actualizarFilasUvPorSegmento(table);

        if (tfoot) {
            if (esUvSimple) {
                tfoot.style.display = '';
                recalcularTotalesUv(table);
            } else {
                tfoot.style.display = activo ? 'none' : '';
            }
        }
    }

    function conectarFiltrosTablaEstatica(table, toolRow) {
        const genMode = table.getAttribute('data-dash-gen-mode') || 'hmjt';
        const genCfg = dashGenConfig(genMode);
        const enablePago = table.getAttribute('data-dash-enable-pago') === '1';
        const tableId = table.id || '';

        let selMin = toolRow.querySelector('.js-dash-sel-min[data-dash-table="' + tableId + '"]');
        if (!selMin) {
            selMin = toolRow.querySelector('.js-dash-sel-min');
        }
        let selPago = toolRow.querySelector('.js-dash-sel-pago[data-dash-table="' + tableId + '"]');
        if (!selPago) {
            selPago = toolRow.querySelector('.js-dash-sel-pago');
        }
        let btnClear = toolRow.querySelector('.js-dash-btn-limpiar[data-dash-table="' + tableId + '"]');
        if (!btnClear) {
            btnClear = toolRow.querySelector('.js-dash-btn-limpiar');
        }
        let segmentInputs = Array.from(toolRow.querySelectorAll('.js-dash-seg[data-dash-table="' + tableId + '"]'));
        if (!segmentInputs.length) {
            segmentInputs = Array.from(toolRow.querySelectorAll('.js-dash-seg'));
        }

        if (!selMin || !segmentInputs.length) {
            return false;
        }

        const { map: labMap, slugs } = construirOpcionesMinisterio(table);
        selMin.innerHTML = '<option value="">Todos los ministerios</option>';
        slugs.forEach(function(sl) {
            const opt = document.createElement('option');
            opt.value = sl;
            opt.textContent = labMap[sl] || sl;
            selMin.appendChild(opt);
        });

        let tieneDesgloseGen = false;
        table.querySelectorAll('tbody tr[data-dash-row]').forEach(function(tr) {
            genCfg.keys.forEach(function(k) {
                if ((parseInt(tr.getAttribute('data-dash-' + k) || '0', 10) || 0) > 0) {
                    tieneDesgloseGen = true;
                }
            });
        });
        if (!tieneDesgloseGen) {
            segmentInputs.forEach(function(inp) {
                inp.disabled = true;
            });
        }

        table._dashFiltro = { min: '', gen: 'all', pago: 'all' };

        function syncAndApply() {
            let genVal = 'all';
            if (tieneDesgloseGen) {
                const keys = [];
                segmentInputs.forEach(function(inp) {
                    if (inp.checked) {
                        keys.push(inp.value);
                    }
                });
                genVal = keys.length ? keys : 'all';
            }
            table._dashFiltro = {
                min: selMin.value || '',
                gen: genVal,
                pago: (!selPago || selPago.disabled) ? 'all' : (selPago.value || 'all')
            };
            aplicarFiltrosTabla(table);
        }

        selMin.addEventListener('change', syncAndApply);
        segmentInputs.forEach(function(inp) {
            inp.addEventListener('change', syncAndApply);
        });
        if (selPago) {
            selPago.addEventListener('change', syncAndApply);
        }
        if (btnClear) {
            btnClear.addEventListener('click', function() {
                selMin.value = '';
                segmentInputs.forEach(function(inp) {
                    inp.checked = false;
                });
                if (selPago && !selPago.disabled) {
                    selPago.value = 'all';
                }
                syncAndApply();
            });
        }

        aplicarFiltroMinisterioGlobalEnTabla(table);
        return true;
    }

    function crearBarraFiltros(table, wrap) {
        const genMode = table.getAttribute('data-dash-gen-mode') || 'hmjt';
        const genCfg = dashGenConfig(genMode);
        const enablePago = table.getAttribute('data-dash-enable-pago') === '1';

        const bar = document.createElement('div');
        bar.className = 'dash-inline-filters';

        function addGroup(labelText, select) {
            const g = document.createElement('div');
            g.className = 'group';
            const lab = document.createElement('label');
            lab.textContent = labelText;
            g.appendChild(lab);
            g.appendChild(select);
            bar.appendChild(g);
        }

        function addGroupBlock(labelText, innerEl) {
            const g = document.createElement('div');
            g.className = 'group';
            const lab = document.createElement('span');
            lab.textContent = labelText;
            lab.style.fontSize = '0.72rem';
            lab.style.fontWeight = '600';
            lab.style.color = '#64748b';
            g.appendChild(lab);
            g.appendChild(innerEl);
            bar.appendChild(g);
        }

        const selMin = document.createElement('select');
        selMin.innerHTML = '<option value="">Todos los ministerios</option>';
        const { map: labMap, slugs } = construirOpcionesMinisterio(table);
        slugs.forEach(function(sl) {
            const opt = document.createElement('option');
            opt.value = sl;
            opt.textContent = labMap[sl] || sl;
            selMin.appendChild(opt);
        });
        addGroup('Ministerio', selMin);

        const segmentCol = document.createElement('div');
        const segmentWrap = document.createElement('div');
        segmentWrap.className = 'dash-segment-checks';
        const segmentHint = document.createElement('div');
        segmentHint.className = 'dash-segment-hint';
        segmentHint.textContent = table.classList.contains('uv-simple-table')
            ? genCfg.hintUv
            : genCfg.hintDefault;
        segmentCol.appendChild(segmentWrap);
        segmentCol.appendChild(segmentHint);

        const segmentInputs = [];
        genCfg.segments.forEach(function(def) {
            const lab = document.createElement('label');
            lab.className = 'dash-segment-opt';
            const inp = document.createElement('input');
            inp.type = 'checkbox';
            inp.value = def[0];
            lab.appendChild(inp);
            lab.appendChild(document.createTextNode(def[1]));
            segmentWrap.appendChild(lab);
            segmentInputs.push(inp);
        });
        addGroupBlock(genCfg.label, segmentCol);

        let tieneDesgloseGen = false;
        table.querySelectorAll('tbody tr[data-dash-row]').forEach(function(tr) {
            if (genMode === 'ajt') {
                const sa = (parseInt(tr.getAttribute('data-dash-a') || '0', 10) || 0)
                    + (parseInt(tr.getAttribute('data-dash-j') || '0', 10) || 0)
                    + (parseInt(tr.getAttribute('data-dash-t') || '0', 10) || 0);
                if (sa > 0) {
                    tieneDesgloseGen = true;
                }
            } else {
                genCfg.keys.forEach(function(k) {
                    if ((parseInt(tr.getAttribute('data-dash-' + k) || '0', 10) || 0) > 0) {
                        tieneDesgloseGen = true;
                    }
                });
            }
        });
        if (!tieneDesgloseGen) {
            segmentInputs.forEach(function(inp) {
                inp.disabled = true;
            });
            const sinDesgloseMsg = genMode === 'n123' || genMode === 'hmj'
                ? 'Sin inscripciones por nivel en esta tabla para el filtro actual.'
                : 'Sin desglose por segmento en esta tabla para el periodo o línea seleccionados.';
            segmentCol.title = sinDesgloseMsg;
        }

        const selPago = document.createElement('select');
        selPago.innerHTML = '<option value="all">Pagos: todos</option>'
            + '<option value="pend">Con pendiente</option>'
            + '<option value="ok">Sin pendiente (al día)</option>';
        if (!enablePago) {
            selPago.disabled = true;
            selPago.title = 'Esta tabla no incluye estado de pago por fila';
        }
        addGroup('Pagos', selPago);

        const btnClear = document.createElement('button');
        btnClear.type = 'button';
        btnClear.className = 'btn btn-secondary btn-sm';
        btnClear.style.marginTop = '18px';
        btnClear.textContent = 'Limpiar';
        bar.appendChild(btnClear);

        table._dashFiltro = { min: '', gen: 'all', pago: 'all' };

        function syncAndApply() {
            let genVal = 'all';
            if (tieneDesgloseGen) {
                const keys = [];
                segmentInputs.forEach(function(inp) {
                    if (inp.checked) {
                        keys.push(inp.value);
                    }
                });
                genVal = keys.length ? keys : 'all';
            }
            table._dashFiltro = {
                min: selMin.value || '',
                gen: genVal,
                pago: selPago.disabled ? 'all' : (selPago.value || 'all')
            };
            aplicarFiltrosTabla(table);
        }

        selMin.addEventListener('change', syncAndApply);
        segmentInputs.forEach(function(inp) {
            inp.addEventListener('change', syncAndApply);
        });
        selPago.addEventListener('change', syncAndApply);
        btnClear.addEventListener('click', function() {
            selMin.value = '';
            segmentInputs.forEach(function(inp) {
                inp.checked = false;
            });
            if (!selPago.disabled) {
                selPago.value = 'all';
            }
            syncAndApply();
        });

        return bar;
    }

    async function exportarTablaComoPng(tableWrap, indiceTabla, tituloTabla) {
        const exportContainer = document.createElement('div');
        const maxTableWidth = Math.max(contenedorDashboard ? contenedorDashboard.clientWidth : 960, tableWrap.scrollWidth || 0);
        const targetWidth = Math.min(1400, Math.max(960, maxTableWidth + 24));

        exportContainer.style.position = 'fixed';
        exportContainer.style.left = '-10000px';
        exportContainer.style.top = '0';
        exportContainer.style.width = targetWidth + 'px';
        exportContainer.style.background = '#ffffff';
        exportContainer.style.padding = '12px';

        const tituloExport = document.createElement('div');
        tituloExport.style.fontSize = '18px';
        tituloExport.style.fontWeight = '700';
        tituloExport.style.color = '#1e293b';
        tituloExport.style.margin = '0 0 10px 0';
        tituloExport.textContent = tituloTabla || ('Tabla ' + indiceTabla);
        exportContainer.appendChild(tituloExport);

        const clonedWrap = tableWrap.cloneNode(true);
        clonedWrap.style.overflow = 'visible';
        clonedWrap.querySelectorAll('table').forEach(function(table) {
            table.style.minWidth = '0';
            table.style.width = '100%';
        });

        exportContainer.appendChild(clonedWrap);
        document.body.appendChild(exportContainer);

        const canvas = await html2canvas(exportContainer, {
            backgroundColor: '#ffffff',
            scale: 2,
            useCORS: true,
            logging: false,
            windowWidth: exportContainer.scrollWidth,
            windowHeight: exportContainer.scrollHeight
        });

        document.body.removeChild(exportContainer);

        const enlace = document.createElement('a');
        const fecha = new Date().toISOString().slice(0, 10);
        enlace.href = canvas.toDataURL('image/png');
        enlace.download = 'tabla-dashboard-escuelas-' + fecha + '-' + slugifyTitulo(tituloTabla || ('tabla-' + indiceTabla)) + '.png';
        enlace.click();
    }

    function slugMinisterioNombre(nombre) {
        return slugifyTitulo(nombre).replace(/-/g, ' ');
    }

    function aplicarFiltroMinisterioGlobalEnTabla(table) {
        if (table.getAttribute('data-dash-skip-global-ministerio') === '1') {
            return;
        }
        const globalMinId = String(table.getAttribute('data-dash-global-ministerio-id') || '').trim();
        if (!globalMinId) {
            return;
        }
        const globalSelect = document.querySelector('select[name="ministerio"]');
        if (!globalSelect) {
            return;
        }
        const optGlobal = globalSelect.querySelector('option[value="' + globalMinId.replace(/"/g, '') + '"]');
        if (!optGlobal) {
            return;
        }
        const nombreGlobal = slugMinisterioNombre(optGlobal.textContent || '');
        const wrap = table.closest('.table-wrap');
        if (!wrap) {
            return;
        }
        const toolRow = wrap.previousElementSibling;
        if (!toolRow || !toolRow.classList.contains('dash-table-tool-row')) {
            return;
        }
        const selMin = toolRow.querySelector('.dash-inline-filters select');
        if (!selMin) {
            return;
        }
        Array.from(selMin.options).forEach(function(op) {
            if (op.value && slugMinisterioNombre(op.textContent || '') === nombreGlobal) {
                selMin.value = op.value;
            }
        });
        if (typeof table._dashFiltro === 'object') {
            table._dashFiltro.min = selMin.value || '';
            aplicarFiltrosTabla(table);
        }
    }

    function initHerramientasTablasDashboard() {
        if (!contenedorDashboard) {
            return;
        }
        const tableWraps = Array.from(contenedorDashboard.querySelectorAll('.dash-card .table-wrap')).filter(function(wrap) {
            return wrap.querySelector('table') !== null;
        });

        tableWraps.forEach(function(wrap, idx) {
            const table = wrap.querySelector('table');
            if (!table) {
                return;
            }
            const tituloTabla = obtenerTituloDeTabla(wrap, idx + 1);
            const staticToolRow = wrap.parentNode.querySelector(
                '.js-dash-filtros-estaticos[data-dash-table-id="' + (table.id || '') + '"]'
            );

            let toolRow = staticToolRow;
            if (!toolRow) {
                const prev = wrap.previousElementSibling;
                if (prev && prev.classList && prev.classList.contains('dash-table-tool-row')) {
                    toolRow = prev;
                }
            }

            if (!toolRow && table.classList.contains('js-dash-filterable')) {
                toolRow = document.createElement('div');
                toolRow.className = 'dash-table-tool-row';

                const leftCol = document.createElement('div');
                leftCol.style.flex = '1';
                leftCol.style.minWidth = '220px';

                const hint = document.createElement('p');
                hint.className = 'dash-filter-hint';
                const hintGenCfg = dashGenConfig(table.getAttribute('data-dash-gen-mode') || 'hmjt');
                const hintFiltroDetalle = hintGenCfg.label.toLowerCase();
                if (table.classList.contains('uv-simple-table')) {
                    hint.textContent = 'Filtros: ministerio; ' + hintFiltroDetalle
                        + ' (oculta columnas y recalcula totales). Pagos solo en la tabla de pagos.';
                } else {
                    hint.textContent = 'Filtros por tabla: ministerio; ' + hintFiltroDetalle
                        + ' (oculta columnas no marcadas y filtra filas); pagos. El pie de totales se oculta mientras hay filtros activos.';
                }
                leftCol.appendChild(hint);

                const filtros = crearBarraFiltros(table, wrap);
                leftCol.appendChild(filtros);
                toolRow.appendChild(leftCol);
                wrap.parentNode.insertBefore(toolRow, wrap);
            }

            if (staticToolRow && !staticToolRow.dataset.wired && table.classList.contains('js-dash-filterable')) {
                conectarFiltrosTablaEstatica(table, staticToolRow);
                staticToolRow.dataset.wired = '1';
                if (table.getAttribute('data-dash-skip-global-ministerio') !== '1') {
                    aplicarFiltroMinisterioGlobalEnTabla(table);
                }
            } else if (toolRow && !staticToolRow && table.classList.contains('js-dash-filterable')) {
                aplicarFiltroMinisterioGlobalEnTabla(table);
            }

            if (toolRow && typeof html2canvas !== 'undefined' && !toolRow.querySelector('.btn-tabla-export')) {
                const actions = document.createElement('div');
                actions.className = 'table-actions';
                actions.style.marginBottom = '0';
                actions.style.marginLeft = toolRow.classList.contains('js-dash-filtros-estaticos') ? '0' : 'auto';
                if (toolRow.classList.contains('js-dash-filtros-estaticos')) {
                    toolRow.style.display = 'flex';
                    toolRow.style.flexWrap = 'wrap';
                    toolRow.style.alignItems = 'flex-end';
                    toolRow.style.justifyContent = 'space-between';
                    toolRow.style.gap = '10px';
                }

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-success btn-tabla-export';
                btn.textContent = 'Descargar PNG';

                btn.addEventListener('click', async function() {
                    try {
                        btn.disabled = true;
                        btn.textContent = 'Generando...';
                        await exportarTablaComoPng(wrap, idx + 1, tituloTabla);
                    } catch (e) {
                        alert('No se pudo generar la imagen de esta tabla.');
                    } finally {
                        btn.disabled = false;
                        btn.textContent = 'Descargar PNG';
                    }
                });

                actions.appendChild(btn);
                toolRow.appendChild(actions);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHerramientasTablasDashboard);
    } else {
        initHerramientasTablasDashboard();
    }

    if (window.UV_DASH_DETALLE && window.UV_DASH_DETALLE.route) {
        const uvModal = document.getElementById('uvDetalleModal');
        const uvTitulo = document.getElementById('uvDetalleTitulo');
        const uvSub = document.getElementById('uvDetalleSub');
        const uvBody = document.getElementById('uvDetalleBody');
        const uvVacio = document.getElementById('uvDetalleVacio');
        const uvLoading = document.getElementById('uvDetalleLoading');
        const uvAlineacion = document.getElementById('uvDetalleAlineacion');
        const uvCfg = window.UV_DASH_DETALLE;
        let uvEstado = { slug: '', nombre: '', vista: 'todas', gen: [], tipo: 'ministerio' };

        function uvGenDesdeTabla(table) {
            const filt = table && table._dashFiltro ? table._dashFiltro : {};
            if (filt.gen !== 'all' && Array.isArray(filt.gen) && filt.gen.length) {
                return filt.gen.slice();
            }
            return [];
        }

        function uvMarcarFiltrosModal(vista) {
            document.querySelectorAll('.uv-det-filt').forEach(function(btn) {
                btn.classList.toggle('active', btn.getAttribute('data-uv-filt') === vista);
            });
        }

        function uvRenderFilas(personas) {
            if (!uvBody) {
                return;
            }
            uvBody.innerHTML = '';
            if (!personas.length) {
                if (uvVacio) {
                    uvVacio.hidden = false;
                }
                return;
            }
            if (uvVacio) {
                uvVacio.hidden = true;
            }
            personas.forEach(function(p) {
                const tr = document.createElement('tr');
                const pagoTxt = p.pagado
                    ? ('<span class="uv-det-ok">Sí</span>' + (p.valor_pago > 0 ? ' ($' + Math.round(p.valor_pago) + ')' : ''))
                    : '<span class="uv-det-no">No</span>';
                const asisTxt = p.asistencia_real
                    ? '<span class="uv-det-ok">Sí</span>'
                    : '<span class="uv-det-no">No</span>';
                tr.innerHTML = '<td>' + (p.nombre || '—') + '</td>'
                    + '<td>' + (p.cedula || '—') + '</td>'
                    + '<td>' + (p.lider || '—') + '</td>'
                    + '<td>' + (p.segmento || '—') + '</td>'
                    + '<td>' + (p.fecha_registro || '—') + '</td>'
                    + '<td>' + pagoTxt + '</td>'
                    + '<td>' + asisTxt + '</td>';
                uvBody.appendChild(tr);
            });
        }

        function uvCerrarModal() {
            if (!uvModal) {
                return;
            }
            uvModal.hidden = true;
            uvModal.setAttribute('aria-hidden', 'true');
        }

        function uvAbrirModal() {
            if (!uvModal) {
                return;
            }
            uvModal.hidden = false;
            uvModal.setAttribute('aria-hidden', 'false');
        }

        function uvUrlDetalleApi(extraParams) {
            const pageUrl = new URL(window.location.href);
            const apiUrl = new URL(pageUrl.pathname, pageUrl.origin);
            apiUrl.searchParams.set('url', uvCfg.route || 'reportes/dashboard-escuelas-uv-detalle');
            Object.keys(extraParams || {}).forEach(function(key) {
                const val = extraParams[key];
                if (val !== undefined && val !== null && String(val) !== '') {
                    apiUrl.searchParams.set(key, String(val));
                }
            });
            return apiUrl.toString();
        }

        async function uvCargarDetalle() {
            if (!uvEstado.slug) {
                return;
            }
            if (uvLoading) {
                uvLoading.hidden = false;
            }
            if (uvVacio) {
                uvVacio.hidden = true;
                uvVacio.textContent = 'No hay personas para este criterio.';
            }
            const query = {
                anio: String(uvCfg.anio || ''),
                semestre: String(uvCfg.semestre || ''),
                vista: uvEstado.vista || 'todas',
                filtro_ministerio: uvCfg.filtroMinisterio || '',
                filtro_lider: uvCfg.filtroLider || ''
            };
            if (uvEstado.tipo === 'lider') {
                query.lider = uvEstado.slug;
            } else {
                query.ministerio = uvEstado.slug;
            }
            if (uvEstado.gen.length) {
                query.gen = uvEstado.gen.join(',');
            }
            try {
                const res = await fetch(uvUrlDetalleApi(query), {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                const raw = await res.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch (parseErr) {
                    if (res.status === 404) {
                        throw new Error('Ruta no encontrada (404). Recarga la página con Ctrl+F5.');
                    }
                    throw new Error('Respuesta no válida del servidor (¿sesión expirada?).');
                }
                if (!res.ok || !data.ok) {
                    throw new Error(data.mensaje || ('Error al cargar (' + res.status + ')'));
                }
                if (uvTitulo) {
                    const prefijo = (data.tipo === 'lider' || uvEstado.tipo === 'lider') ? 'Líder: ' : '';
                    uvTitulo.textContent = prefijo + (data.ministerio || uvEstado.nombre);
                }
                if (uvSub && data.periodo) {
                    const etq = data.periodo.etiqueta ? (data.periodo.etiqueta + ' ' + data.periodo.anio + ' · ') : '';
                    uvSub.textContent = etq + 'Periodo ' + data.periodo.fecha_inicio + ' a ' + data.periodo.fecha_fin
                        + ' · ' + (data.totales ? data.totales.listado : 0) + ' persona(s)'
                        + ' · ' + (data.totales ? data.totales.pagados : 0) + ' con pago'
                        + ' · ' + (data.totales ? data.totales.asistencias_reales : 0) + ' con asistencia real';
                }
                if (uvAlineacion && data.alineacion) {
                    uvAlineacion.textContent = 'Pagos: ' + data.alineacion.pagos + ' · Asistencias: ' + data.alineacion.asistencias;
                }
                uvRenderFilas(data.personas || []);
            } catch (err) {
                if (uvBody) {
                    uvBody.innerHTML = '';
                }
                if (uvVacio) {
                    uvVacio.hidden = false;
                    uvVacio.textContent = err.message || 'No se pudo cargar el detalle.';
                }
            } finally {
                if (uvLoading) {
                    uvLoading.hidden = true;
                }
            }
        }

        document.querySelectorAll('.uv-dash-tabla-detalle').forEach(function(table) {
            table.querySelectorAll('tbody tr[data-dash-row] .uv-min-clickable').forEach(function(td) {
                td.addEventListener('click', function() {
                    const tr = td.closest('tr');
                    if (!tr) {
                        return;
                    }
                    const slug = tr.getAttribute('data-dash-min') || '';
                    const nombre = String(td.textContent || '').trim();
                    if (!slug) {
                        return;
                    }
                    uvEstado.slug = slug;
                    uvEstado.nombre = nombre;
                    uvEstado.tipo = table.getAttribute('data-uv-detalle-tipo') === 'lider' ? 'lider' : 'ministerio';
                    uvEstado.gen = uvGenDesdeTabla(table);
                    const vistaIni = table.getAttribute('data-uv-vista-inicial') || 'todas';
                    uvEstado.vista = vistaIni;
                    uvMarcarFiltrosModal(vistaIni);
                    uvAbrirModal();
                    uvCargarDetalle();
                });
            });
        });

        document.querySelectorAll('[data-uv-cerrar]').forEach(function(el) {
            el.addEventListener('click', uvCerrarModal);
        });

        document.querySelectorAll('.uv-det-filt').forEach(function(btn) {
            btn.addEventListener('click', function() {
                uvEstado.vista = btn.getAttribute('data-uv-filt') || 'todas';
                uvMarcarFiltrosModal(uvEstado.vista);
                uvCargarDetalle();
            });
        });

        document.addEventListener('keydown', function(ev) {
            if (ev.key === 'Escape' && uvModal && !uvModal.hidden) {
                uvCerrarModal();
            }
        });
    }

})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>

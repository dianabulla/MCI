<?php include VIEWS . '/layout/header.php'; ?>

<?php
$id = (int)($formulario['Id_Formulario'] ?? 0);
$titulo = (string)($formulario['Titulo'] ?? 'Formulario');
$slug = (string)($formulario['Slug'] ?? '');
$urlQr = $slug !== '' ? (PUBLIC_URL . '?url=talleres_publico/qr&slug=' . urlencode($slug)) : '';
$secciones = is_array($secciones ?? null) ? $secciones : [];
$listaRespuestas = is_array($respuestas ?? null) ? $respuestas : [];
$total = count($listaRespuestas);
$statsGraficas = is_array($estadisticas_graficas ?? null) ? $estadisticas_graficas : [];
$graficasList = is_array($statsGraficas['graficas'] ?? null) ? $statsGraficas['graficas'] : [];
$totalHijosTabla = (int)($statsGraficas['total_hijos_tabla'] ?? 0);
$permisosTaller = is_array($permisos_taller ?? null) ? $permisos_taller : [];
$puedeEditar = !empty($permisosTaller['editar']);
$puedeExportar = !empty($permisosTaller['exportar_excel']);
$puedeGraficas = !empty($permisosTaller['ver_graficas']);
$puedeGestionarEnlace = !empty($permisosTaller['gestionar_enlace']);
$puedeEliminarInscripcion = !empty($permisosTaller['eliminar_inscripcion']);
$puedeAsignarMinisterio = !empty($permisosTaller['asignar_ministerio']);
$ministeriosAsignacion = is_array($ministerios_asignacion ?? null) ? $ministerios_asignacion : [];
$abrirMinisterio = trim((string)($_GET['abrir_ministerio'] ?? ''));
if ($abrirMinisterio === '' && (string)($_GET['abrir'] ?? '') === 'sin-ministerio') {
    $abrirMinisterio = 'Sin ministerio asignado';
}
$idResaltar = (int)($_GET['resaltar'] ?? 0);
$esPresentacionNinos = !empty($es_presentacion_ninos);
$esTourLevantate = !empty($es_tour_levantate);
$resumenPorMinisterio = is_array($resumen_por_ministerio ?? null) ? $resumen_por_ministerio : [];
$totalResumenMinisterios = array_sum(array_map(static fn(array $f): int => (int)($f['total'] ?? 0), $resumenPorMinisterio));
$soloGraficas = !empty($solo_graficas);
$tabActiva = (string)($tab_activa ?? 'lista');
$totalInscripciones = (int)($statsGraficas['total'] ?? $total);
$listaPagosTaller = is_array($lista_pagos ?? null) ? $lista_pagos : [];
$totalPagosTaller = 0.0;
foreach ($listaPagosTaller as $pagoFila) {
    $totalPagosTaller += (float)($pagoFila['valor'] ?? 0);
}

function taller_resp_valor(array $valores, string $clave): string {
    $v = $valores[$clave] ?? '';
    return is_array($v) ? implode(', ', array_map('strval', $v)) : trim((string)$v);
}
?>

<style>
.taller-resp-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    margin-bottom: 16px;
}
.taller-resp-toolbar input {
    max-width: 320px;
}
.taller-resp-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.taller-resp-item {
    border: 1px solid #dbeafe;
    border-radius: 10px;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}
.taller-resp-item.taller-resp-resaltado {
    border-color: #15803d;
    box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.25);
}
.taller-resp-item > summary {
    list-style: none;
    cursor: pointer;
    padding: 14px 16px;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 12px 16px;
    align-items: center;
    background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
}
.taller-resp-item > summary::-webkit-details-marker { display: none; }
.taller-resp-item > summary::after {
    content: 'Ver detalle ▾';
    font-size: 0.82rem;
    color: #2563eb;
    font-weight: 600;
    white-space: nowrap;
}
.taller-resp-item[open] > summary::after { content: 'Ocultar ▴'; }
.taller-resp-num {
    font-weight: 700;
    color: #64748b;
    font-size: 0.85rem;
    min-width: 2rem;
}
.taller-resp-resumen {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 20px;
    align-items: baseline;
    min-width: 0;
}
.taller-resp-resumen strong {
    color: #0f172a;
    font-size: 1rem;
}
.taller-resp-meta {
    font-size: 0.85rem;
    color: #64748b;
}
.taller-resp-meta span + span::before {
    content: '·';
    margin: 0 6px;
    color: #cbd5e1;
}
.taller-resp-body {
    padding: 16px 18px 20px;
    border-top: 1px solid #e2e8f0;
}
.taller-resp-seccion {
    margin-bottom: 22px;
}
.taller-resp-seccion:last-child { margin-bottom: 0; }
.taller-resp-seccion h4 {
    margin: 0 0 12px;
    font-size: 0.95rem;
    color: #1e40af;
    padding-bottom: 6px;
    border-bottom: 2px solid #dbeafe;
}
.taller-resp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 12px 20px;
}
.taller-resp-campo {
    min-width: 0;
}
.taller-resp-campo.full-width {
    grid-column: 1 / -1;
}
.taller-resp-campo dt {
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    margin-bottom: 4px;
    line-height: 1.35;
}
.taller-resp-campo dd {
    margin: 0;
    font-size: 0.92rem;
    color: #0f172a;
    line-height: 1.5;
    word-break: break-word;
    white-space: pre-wrap;
}
.taller-resp-campo dd.empty {
    color: #94a3b8;
    font-style: italic;
}
.taller-resp-firma {
    max-width: 320px;
    max-height: 120px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
}
.taller-resp-docs-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 999px;
    background: #ecfdf5;
    border: 1px solid #86efac;
    color: #166534;
    font-size: 0.78rem;
    font-weight: 700;
    white-space: nowrap;
}
.taller-resp-docs-quick {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 6px;
}
.taller-resp-docs-quick a {
    font-size: 0.78rem;
    color: #1d4ed8;
    text-decoration: none;
    border: 1px solid #bfdbfe;
    background: #eff6ff;
    border-radius: 6px;
    padding: 2px 8px;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.taller-resp-docs-quick a:hover {
    background: #dbeafe;
}
.taller-resp-empty {
    text-align: center;
    padding: 48px 20px;
    color: #64748b;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px dashed #cbd5e1;
}
.taller-resp-resumen-ministerios {
    margin: 0 0 22px;
    padding: 16px 18px 18px;
    border: 1px solid #dbeafe;
    border-radius: 12px;
    background: #f8fbff;
}
.taller-min-header {
    display: grid;
    grid-template-columns: 48px 1fr 110px auto;
    gap: 10px;
    align-items: center;
    padding: 10px 16px;
    background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-bottom: 1px solid #e2e8f0;
}
.taller-min-header.taller-min-total {
    text-transform: none;
    letter-spacing: 0;
    font-size: 0.92rem;
    background: #eff6ff;
    border-top: 2px solid #bfdbfe;
    border-bottom: none;
    color: #0f172a;
}
.taller-min-header .col-total,
.taller-min-acordeon .col-total {
    text-align: center;
    font-weight: 700;
    color: #1e40af;
}
.taller-min-acordeon:last-child {
    border-bottom: none;
}
.taller-min-acordeon > summary {
    list-style: none;
    cursor: pointer;
    display: grid;
    grid-template-columns: 48px 1fr 110px auto;
    gap: 10px;
    align-items: center;
    padding: 12px 16px;
    background: #fff;
}
.taller-min-acordeon > summary::-webkit-details-marker { display: none; }
.taller-min-acordeon > summary:hover {
    background: #eff6ff;
}
.taller-min-acordeon[open] > summary {
    background: #dbeafe;
}
.taller-min-num {
    color: #94a3b8;
    font-weight: 700;
}
.taller-min-nombre {
    font-weight: 600;
    color: #0f172a;
}
.taller-min-hint {
    font-size: 0.78rem;
    color: #2563eb;
    font-weight: 600;
    white-space: nowrap;
}
.taller-min-acordeon[open] .taller-min-hint::after {
    content: ' Ocultar ▴';
}
.taller-min-acordeon:not([open]) .taller-min-hint::after {
    content: ' Ver inscritos ▾';
}
.taller-min-lista {
    padding: 10px 12px 14px;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.taller-resp-resumen-ministerios h3 {
    margin: 0 0 6px;
    font-size: 1.05rem;
    color: #0f172a;
}
.taller-resp-resumen-ministerios .subtitulo {
    margin: 0 0 16px;
    font-size: 0.88rem;
    color: #64748b;
}
.taller-resp-tabla-ministerios {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border: 1px solid #dbeafe;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}
.taller-resp-tabla-ministerios th,
.taller-resp-tabla-ministerios td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}
.taller-resp-tabla-ministerios th {
    background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.taller-resp-tabla-ministerios tbody tr:last-child td {
    border-bottom: none;
}
.taller-resp-tabla-ministerios tbody tr:hover {
    background: #f8fafc;
}
.taller-resp-tabla-ministerios .col-total {
    width: 120px;
    text-align: center;
    font-weight: 700;
    color: #1e40af;
}
.taller-resp-tabla-ministerios tfoot td {
    background: #eff6ff;
    font-weight: 700;
    color: #0f172a;
    border-top: 2px solid #bfdbfe;
}
.taller-resp-item.hidden-by-filter,
.taller-min-acordeon.hidden-by-filter { display: none; }
.taller-resp-pago {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
    min-width: 110px;
}
.taller-resp-pago-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #0f766e;
    color: #fff;
    text-decoration: none;
    font-size: 1.1rem;
    box-shadow: 0 2px 6px rgba(15, 118, 110, 0.25);
    border: 0;
    cursor: pointer;
    padding: 0;
}
.taller-resp-pago-btn.eliminar {
    background: #b91c1c;
    box-shadow: 0 2px 6px rgba(185, 28, 28, 0.25);
}
.taller-resp-pago-btn.eliminar:hover {
    background: #991b1b;
    color: #fff;
}
.taller-resp-pago-acciones {
    display: flex;
    gap: 6px;
}
.taller-resp-pago-btn:hover { background: #0d5f58; color: #fff; }
.taller-resp-pago-status {
    font-size: 0.78rem;
    color: #64748b;
    white-space: nowrap;
}
.taller-resp-pago-status.ok { color: #0f766e; font-weight: 600; }
.taller-uv-pagos { display:flex; flex-direction:column; gap:12px; margin: 0 0 22px; }
.taller-uv-pagos h3 { margin:0; font-size:1.15rem; color:#1e3a5f; font-weight:800; }
.taller-uv-pagos .subtitulo { margin:0; font-size:0.88rem; color:#64748b; }
.taller-uv-pagos .li-toolbar { display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end; }
.taller-uv-pagos .li-toolbar label { font-size:0.76rem; color:#475569; font-weight:600; text-transform:uppercase; letter-spacing:.04em; display:block; margin-bottom:4px; }
.taller-uv-pagos .li-toolbar input[type=search],
.taller-uv-pagos .li-toolbar select {
    padding:7px 10px; border:1px solid #c5d5e8; border-radius:8px;
    font-size:0.83rem; color:#1e293b; background:#fff; outline:none;
}
.taller-uv-pagos .li-toolbar input[type=search]:focus,
.taller-uv-pagos .li-toolbar select:focus { border-color:#3b82f6; }
.taller-uv-pagos .li-toolbar input[type=search] { min-width:220px; }
.taller-uv-pagos .li-summary { display:flex; gap:10px; flex-wrap:wrap; }
.taller-uv-pagos .li-stat { background:#f1f7ff; border:1px solid #cfe0f5; border-radius:10px; padding:10px 16px; min-width:130px; flex:1 1 130px; }
.taller-uv-pagos .li-stat strong { display:block; font-size:1.3rem; font-weight:800; color:#1e3a5f; }
.taller-uv-pagos .li-stat span { font-size:0.73rem; color:#4b6482; text-transform:uppercase; letter-spacing:.04em; }
.taller-uv-pagos .li-stat--ok { background:#ecfdf5; border-color:#a7f3d0; }
.taller-uv-pagos .li-stat--ok strong { color:#166534; }
.taller-uv-pagos .li-stat--warn { background:#fffbeb; border-color:#fde68a; }
.taller-uv-pagos .li-stat--warn strong { color:#92400e; }
.taller-uv-pagos .li-stat--filtro { cursor:pointer; text-align:left; font:inherit; appearance:none; -webkit-appearance:none; }
.taller-uv-pagos .li-stat--filtro.is-active { box-shadow:inset 0 0 0 2px #1e4a89; }
.taller-uv-pagos .li-resumen-filtro { margin:0; padding:8px 12px; font-size:0.78rem; color:#1e40af; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; }
.taller-uv-pagos .li-card { background:#fff; border:1px solid #dbe7f3; border-radius:12px; box-shadow:0 1px 4px rgba(15,23,42,.08); overflow:hidden; }
.taller-uv-pagos .li-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.taller-uv-pagos .li-table { width:100%; border-collapse:collapse; white-space:nowrap; }
.taller-uv-pagos .li-table th {
    background:#f8fafc; color:#475569; font-size:0.68rem;
    text-transform:uppercase; letter-spacing:.04em; padding:9px 8px;
    border-bottom:2px solid #dbe7f3; text-align:center;
}
.taller-uv-pagos .li-table th.t-left { text-align:left; }
.taller-uv-pagos .li-table td {
    padding:7px 8px; font-size:0.80rem; color:#1e293b;
    border-bottom:1px solid #eef2f7; text-align:center; vertical-align:middle;
}
.taller-uv-pagos .li-table td.t-left { text-align:left; }
.taller-uv-pagos .li-table tbody tr:hover { background:#f7faff; }
.taller-uv-pagos .li-table tfoot td { background:#f8fafc; border-top:2px solid #dbe7f3; font-weight:700; }
.taller-uv-pagos .pago-status {
    display:inline-flex; align-items:center; border-radius:999px; padding:2px 7px;
    font-size:0.68rem; font-weight:700; border:1px solid #dbe7f3; color:#1e3a5f; background:#f8fbff;
}
.taller-uv-pagos .pago-status.ok { background:#e8f7ee; border-color:#bfe7cc; color:#166534; }
.taller-uv-pagos .pago-status.no { background:#fee2e2; border-color:#f4c5c5; color:#991b1b; }
.taller-uv-pagos .li-empty { padding:18px; text-align:center; color:#64748b; font-size:0.88rem; }
.taller-asig-min {
    grid-column: 1 / -1;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-top: 4px;
    padding-top: 10px;
    border-top: 1px dashed #cbd5e1;
}
.taller-asig-min span.taller-asig-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.taller-asig-min select {
    min-width: 180px;
    flex: 1 1 180px;
    max-width: 280px;
    height: 36px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0 10px;
    font-size: 0.88rem;
    background: #fff;
}
.taller-asig-min button {
    height: 36px;
    border: 0;
    border-radius: 8px;
    padding: 0 14px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
}
.taller-asig-min .btn-antiguo,
.taller-asig-min .btn-nuevo {
    background: #fff;
    color: #334155;
    border: 1px solid #cbd5e1;
}
.taller-asig-min .btn-antiguo:hover,
.taller-asig-min .btn-nuevo:hover {
    border-color: #94a3b8;
}
.taller-asig-min .btn-antiguo.activo {
    background: #475569;
    border-color: #475569;
    color: #fff;
}
.taller-asig-min .btn-nuevo.activo {
    background: #15803d;
    border-color: #15803d;
    color: #fff;
}
.taller-asig-min .btn-guardar {
    background: #1d4ed8;
    color: #fff;
    min-width: 96px;
}
.taller-asig-min .btn-guardar:hover { background: #1e40af; }
.taller-asig-min .taller-asig-hint {
    flex: 1 1 100%;
    font-size: 0.78rem;
    color: #64748b;
    margin: 0;
}
.taller-resp-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0;
}
.taller-resp-tabs button {
    border: none;
    background: transparent;
    padding: 10px 18px;
    font-weight: 600;
    font-size: 0.92rem;
    color: #64748b;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    border-radius: 6px 6px 0 0;
}
.taller-resp-tabs button:hover { color: #2563eb; background: #f8fafc; }
.taller-resp-tabs button.active {
    color: #1d4ed8;
    border-bottom-color: #2563eb;
    background: #eff6ff;
}
.taller-resp-panel { display: none; }
.taller-resp-panel.active { display: block; }
.taller-graf-resumen {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}
.taller-graf-card {
    background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
    border: 1px solid #dbeafe;
    border-radius: 10px;
    padding: 16px 18px;
}
.taller-graf-card strong {
    display: block;
    font-size: 1.75rem;
    color: #1e40af;
    line-height: 1.2;
}
.taller-graf-card span {
    font-size: 0.85rem;
    color: #64748b;
}
.taller-graf-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
    gap: 20px;
}
.taller-graf-item {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.05);
}
.taller-graf-item h5 {
    margin: 0 0 12px;
    font-size: 0.95rem;
    color: #0f172a;
    line-height: 1.35;
}
.taller-graf-item .chart-mount { min-height: 280px; }
.taller-graf-item.donut .chart-mount { min-height: 300px; }
.taller-graf-item.abierta { grid-column: 1 / -1; }
.taller-graf-item.abierta .chart-mount { min-height: 220px; }
.taller-graf-abierta-nota { margin: 0 0 12px; font-size: 0.82rem; color: #64748b; }
.taller-graf-abierta-wrap {
    display: grid;
    grid-template-columns: minmax(260px, 1fr) minmax(320px, 1.4fr);
    gap: 16px;
    align-items: start;
}
.taller-graf-abierta-tabla { width: 100%; border-collapse: collapse; }
.taller-graf-abierta-tabla th {
    background: #f8fafc; color: #475569; font-size: 0.68rem; text-transform: uppercase;
    letter-spacing: .04em; padding: 8px 10px; border-bottom: 2px solid #dbe7f3; text-align: left;
}
.taller-graf-abierta-tabla td {
    padding: 8px 10px; font-size: 0.82rem; border-bottom: 1px solid #eef2f7; vertical-align: top; color: #1e293b;
}
.taller-graf-abierta-tabla .col-cant { text-align: center; font-weight: 800; width: 88px; color: #1e40af; }
.taller-graf-abierta-tabla .variantes { color: #64748b; font-size: 0.78rem; }
@media (max-width: 900px) {
    .taller-graf-abierta-wrap { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .taller-resp-item > summary {
        grid-template-columns: 1fr;
    }
    .taller-resp-item > summary::after {
        justify-self: start;
    }
    .taller-graf-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-header">
    <h2><?= $soloGraficas ? 'Gráficas' : 'Respuestas' ?>: <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
        <?php if ($soloGraficas): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres" class="btn btn-secondary btn-sm">← Elegir taller</a>
        <?php else: ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres" class="btn btn-secondary btn-sm">← Formularios</a>
        <?php endif; ?>
        <?php if ($puedeEditar): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres/editar&id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Editar formulario</a>
        <?php endif; ?>
        <?php if ($puedeExportar): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres/exportar&id=<?= $id ?>" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel"></i> Descargar Excel
        </a>
        <?php endif; ?>
        <?php if (!$soloGraficas && $puedeGraficas && $totalInscripciones > 0 && $graficasList !== []): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres/respuestas&id=<?= $id ?>&tab=graficas" class="btn btn-primary btn-sm">
            <i class="bi bi-bar-chart-line"></i> Ver gráficas
        </a>
        <?php endif; ?>
        <?php if ($puedeGestionarEnlace && !empty($formulario['Activo']) && $slug !== ''): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres_publico&slug=<?= urlencode($slug) ?>" class="btn btn-outline-info btn-sm" target="_blank" rel="noopener">
            Abrir formulario
        </a>
        <a href="<?= htmlspecialchars($urlQr, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm" target="_blank" rel="noopener">
            <i class="bi bi-qr-code"></i> QR inscripción
        </a>
        <?php endif; ?>
    </div>
</div>

<?php
$flashOkResp = $_SESSION['talleres_flash_ok'] ?? '';
$flashErrorResp = $_SESSION['talleres_flash_error'] ?? '';
unset($_SESSION['talleres_flash_ok'], $_SESSION['talleres_flash_error']);
?>
<?php if ($flashOkResp !== ''): ?>
<div class="alert alert-success"><?= htmlspecialchars($flashOkResp, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($flashErrorResp !== ''): ?>
<div class="alert alert-danger"><?= htmlspecialchars($flashErrorResp, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!$soloGraficas && $totalInscripciones > 0 && $puedeGraficas && $graficasList !== []): ?>
<nav class="taller-resp-tabs" aria-label="Vista de respuestas">
    <button type="button" class="<?= $tabActiva === 'lista' ? 'active' : '' ?>" data-tab="lista">Lista de inscripciones</button>
    <button type="button" class="<?= $tabActiva === 'graficas' ? 'active' : '' ?>" data-tab="graficas">Gráficas del cuestionario</button>
</nav>
<?php endif; ?>

<?php if (!$soloGraficas): ?>
<div id="panel-lista" class="taller-resp-panel <?= $tabActiva === 'lista' ? 'active' : '' ?>">
<p class="text-muted">
    <?= $total ?> respuesta(s). Pulse un ministerio para ver sus inscritos y luego una persona para ver el detalle.
    Para análisis masivo, descargue Excel o consulte la pestaña Gráficas.
</p>

<?php
$inscritosPorMinisterio = [];
foreach ($listaRespuestas as $respGrupo) {
    $claveMin = trim((string)($respGrupo['ministerio'] ?? ''));
    if ($claveMin === '') {
        $claveMin = 'Sin ministerio asignado';
    }
    $inscritosPorMinisterio[$claveMin][] = $respGrupo;
}
if ($resumenPorMinisterio === [] && $inscritosPorMinisterio !== []) {
    foreach ($inscritosPorMinisterio as $nombreMin => $listaMin) {
        $resumenPorMinisterio[] = ['ministerio' => $nombreMin, 'total' => count($listaMin)];
    }
}
?>

<?php if ($total > 0): ?>
<div class="taller-resp-toolbar">
    <input type="search" id="filtro-respuestas" class="form-control" placeholder="Buscar por nombre, documento o teléfono…" autocomplete="off">
    <span class="text-muted" style="font-size:0.88rem;" id="filtro-contador"><?= $total ?> mostradas</span>
</div>
<?php endif; ?>

<?php
$ultimoPagoPorRespuesta = [];
foreach ($listaPagosTaller as $pagoFila) {
    $idPagoResp = (int)($pagoFila['id_respuesta'] ?? 0);
    if ($idPagoResp > 0 && !isset($ultimoPagoPorRespuesta[$idPagoResp])) {
        $ultimoPagoPorRespuesta[$idPagoResp] = $pagoFila;
    }
}
$filasEstadoPago = [];
$nPagaron = 0;
$nSinPago = 0;
foreach ($listaRespuestas as $respPago) {
    $valoresPago = is_array($respPago['valores'] ?? null) ? $respPago['valores'] : [];
    $nombrePago = $esPresentacionNinos ? taller_resp_valor($valoresPago, 'nino_nombre') : taller_resp_valor($valoresPago, 'persona_nombre');
    if ($nombrePago === '' && $esPresentacionNinos) {
        $nombrePago = taller_resp_valor($valoresPago, 'padres_nombre');
    }
    $documentoPago = $esPresentacionNinos ? taller_resp_valor($valoresPago, 'nino_documento') : taller_resp_valor($valoresPago, 'persona_documento');
    $telefonoPago = $esPresentacionNinos
        ? taller_resp_valor($valoresPago, 'padres_telefono')
        : taller_resp_valor($valoresPago, 'persona_telefono');
    $ministerioPago = trim((string)($respPago['ministerio'] ?? ''));
    if ($ministerioPago === '') {
        $ministerioPago = 'Sin ministerio asignado';
    }
    $idRespPago = (int)($respPago['id'] ?? 0);
    $totalFilaPago = (float)($respPago['total_pagado'] ?? 0);
    $pagadoFila = $totalFilaPago > 0;
    if ($pagadoFila) {
        $nPagaron++;
    } else {
        $nSinPago++;
    }
    $ultimo = $ultimoPagoPorRespuesta[$idRespPago] ?? [];
    $filasEstadoPago[] = [
        'nombre' => $nombrePago !== '' ? $nombrePago : 'Sin nombre',
        'documento' => $documentoPago,
        'telefono' => $telefonoPago,
        'ministerio' => $ministerioPago,
        'pagado' => $pagadoFila,
        'total' => $totalFilaPago,
        'tipo' => (string)($ultimo['tipo'] ?? ''),
        'metodo' => (string)($ultimo['metodo'] ?? ''),
        'fecha' => (string)($ultimo['fecha'] ?? ''),
        'recibido_por' => (string)($ultimo['recibido_por'] ?? ''),
        'busqueda' => strtolower($nombrePago . ' ' . $documentoPago . ' ' . $telefonoPago . ' ' . $ministerioPago),
    ];
}
?>
<section class="taller-uv-pagos" aria-label="Pagos de este taller">
    <div>
        <h3>Pagos del taller</h3>
        <p class="subtitulo">Igual que Universidad de la Vida: un listado por inscrito. Los pagos se registran en Comunidad → Pagos.</p>
    </div>
    <div class="li-toolbar">
        <div>
            <label for="taller-pago-buscar">Búsqueda</label>
            <input type="search" id="taller-pago-buscar" placeholder="Nombre, documento o teléfono…" autocomplete="off">
        </div>
        <div>
            <label for="taller-pago-filtro">Pago / Abono</label>
            <select id="taller-pago-filtro">
                <option value="todos">Todos</option>
                <option value="pagados">Pagados</option>
                <option value="sin_pago">Sin pago</option>
            </select>
        </div>
    </div>
    <div class="li-summary">
        <button type="button" class="li-stat li-stat--filtro is-active" data-filtro-pago="todos" id="taller-pago-stat-todos">
            <strong><?= count($filasEstadoPago) ?></strong>
            <span>Total inscritos</span>
        </button>
        <button type="button" class="li-stat li-stat--ok li-stat--filtro" data-filtro-pago="pagados" id="taller-pago-stat-pagados">
            <strong><?= $nPagaron ?></strong>
            <span>Con pago</span>
        </button>
        <button type="button" class="li-stat li-stat--warn li-stat--filtro" data-filtro-pago="sin_pago" id="taller-pago-stat-sin">
            <strong><?= $nSinPago ?></strong>
            <span>Sin pago</span>
        </button>
        <div class="li-stat li-stat--ok">
            <strong>$<?= number_format($totalPagosTaller, 0, ',', '.') ?></strong>
            <span>Total recaudado</span>
        </div>
    </div>
    <p id="taller-pago-resumen-filtro" class="li-resumen-filtro" hidden></p>
    <div class="li-card">
        <div class="li-table-wrap">
            <table class="li-table" id="taller-pago-tabla">
                <thead>
                    <tr>
                        <th class="t-left">Nombre</th>
                        <th>Documento</th>
                        <th>Teléfono</th>
                        <th class="t-left">Ministerio</th>
                        <th>Pago / Abono</th>
                        <th>Tipo</th>
                        <th>Método</th>
                        <th>Responsable</th>
                        <th>Fecha pago</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody id="taller-pago-tbody">
                    <?php if ($filasEstadoPago === []): ?>
                    <tr class="taller-pago-empty"><td colspan="10" class="li-empty">Aún no hay inscritos en este taller.</td></tr>
                    <?php else: ?>
                    <?php foreach ($filasEstadoPago as $filaPago): ?>
                    <tr class="taller-pago-fila"
                        data-pago="<?= !empty($filaPago['pagado']) ? '1' : '0' ?>"
                        data-valor="<?= htmlspecialchars((string)$filaPago['total'], ENT_QUOTES, 'UTF-8') ?>"
                        data-busqueda="<?= htmlspecialchars($filaPago['busqueda'], ENT_QUOTES, 'UTF-8') ?>">
                        <td class="t-left"><?= htmlspecialchars($filaPago['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($filaPago['documento'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($filaPago['telefono'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="t-left"><?= htmlspecialchars($filaPago['ministerio'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if (!empty($filaPago['pagado'])): ?>
                            <span class="pago-status ok">$<?= number_format($filaPago['total'], 0, ',', '.') ?></span>
                            <?php else: ?>
                            <span class="pago-status no">Sin pago</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($filaPago['tipo'] !== '' ? $filaPago['tipo'] : '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($filaPago['metodo'] !== '' ? $filaPago['metodo'] : '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($filaPago['recibido_por'] !== '' ? $filaPago['recibido_por'] : '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($filaPago['fecha'] !== '' ? $filaPago['fecha'] : '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>$<?= number_format($filaPago['total'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="9" class="t-left" id="taller-pago-tfoot-label">Total recaudado</td>
                        <td id="taller-pago-tfoot-valor">$<?= number_format($totalPagosTaller, 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</section>

<?php if (empty($listaRespuestas)): ?>
<div class="taller-resp-empty">Aún no hay respuestas para este formulario.</div>
<?php else: ?>
<section class="taller-resp-resumen-ministerios" aria-label="Inscritos por ministerio">
    <h3>Inscritos por ministerio</h3>
    <p class="subtitulo">Haga clic en un ministerio para ver las personas inscritas. En «Sin ministerio asignado» puede asignar ministerio y si es antigua o nueva con un clic.</p>
    <div class="table-container">
        <div class="taller-min-header" aria-hidden="true">
            <span>#</span>
            <span>Ministerio</span>
            <span class="col-total">Inscritos</span>
            <span></span>
        </div>
        <div id="lista-respuestas">
            <?php foreach ($resumenPorMinisterio as $i => $filaResumen): ?>
                <?php
                $nombreMinisterio = (string)($filaResumen['ministerio'] ?? 'Sin ministerio asignado');
                $inscritosMin = $inscritosPorMinisterio[$nombreMinisterio] ?? [];
                $totalMin = count($inscritosMin);
                if ($totalMin === 0) {
                    continue;
                }
                $esSinMinisterio = strcasecmp($nombreMinisterio, 'Sin ministerio asignado') === 0;
                $abrirEsteGrupo = $abrirMinisterio !== ''
                    ? strcasecmp($nombreMinisterio, $abrirMinisterio) === 0
                    : $esSinMinisterio;
                ?>
                <details class="taller-min-acordeon"<?= $abrirEsteGrupo ? ' open' : '' ?>>
                    <summary>
                        <span class="taller-min-num"><?= (int)$i + 1 ?></span>
                        <span class="taller-min-nombre"><?= htmlspecialchars($nombreMinisterio, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="col-total"><?= $totalMin ?></span>
                        <span class="taller-min-hint"></span>
                    </summary>
                    <div class="taller-min-lista taller-resp-list">
                        <?php foreach ($inscritosMin as $idx => $resp): ?>
        <?php
        $valores = is_array($resp['valores'] ?? null) ? $resp['valores'] : [];
        $nombre = $esPresentacionNinos ? taller_resp_valor($valores, 'nino_nombre') : taller_resp_valor($valores, 'persona_nombre');
        if ($nombre === '' && $esPresentacionNinos) {
            $nombre = taller_resp_valor($valores, 'padres_nombre');
        }
        $documento = $esPresentacionNinos ? taller_resp_valor($valores, 'nino_documento') : taller_resp_valor($valores, 'persona_documento');
        $telefono = $esPresentacionNinos
            ? taller_resp_valor($valores, 'padres_telefono')
            : taller_resp_valor($valores, 'persona_telefono');
        $fecha = substr((string)($resp['fecha'] ?? ''), 0, 16);
        $busqueda = strtolower($nombre . ' ' . $documento . ' ' . $telefono . ' ' . $nombreMinisterio);
        $firmaImg = (string)($resp['firma_imagen'] ?? '');
        $idRespuesta = (int)($resp['id'] ?? 0);
        $totalPagado = (float)($resp['total_pagado'] ?? 0);
        $documentosPresentacion = is_array($resp['documentos_presentacion'] ?? null) ? $resp['documentos_presentacion'] : [];
        $totalDocumentos = count($documentosPresentacion);
        $esResaltado = $idResaltar > 0 && $idResaltar === $idRespuesta;
        ?>
        <details class="taller-resp-item<?= $esResaltado ? ' taller-resp-resaltado' : '' ?>" <?= $esResaltado ? 'open ' : '' ?>data-busqueda="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>" id="inscripcion-<?= (int)$idRespuesta ?>">
            <summary>
                <span class="taller-resp-num">#<?= (int)($resp['id'] ?? ($idx + 1)) ?></span>
                <div class="taller-resp-resumen">
                    <strong><?= htmlspecialchars($nombre !== '' ? $nombre : 'Sin nombre', ENT_QUOTES, 'UTF-8') ?></strong>
                    <div class="taller-resp-meta">
                        <?php if ($documento !== ''): ?><span>Doc. <?= htmlspecialchars($documento, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <?php if ($telefono !== ''): ?><span><?= htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <?php if ($fecha !== ''): ?><span><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <?php if ((int)($resp['id_persona'] ?? 0) > 0): ?>
                        <span>Persona #<?= (int)$resp['id_persona'] ?></span>
                        <?php endif; ?>
                        <?php if ($totalDocumentos > 0): ?>
                        <span class="taller-resp-docs-badge" title="<?= $totalDocumentos ?> documento(s) adjunto(s)">📎 <?= $totalDocumentos ?> doc<?= $totalDocumentos === 1 ? '' : 's' ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($totalDocumentos > 0): ?>
                    <div class="taller-resp-docs-quick">
                        <?php foreach (array_slice($documentosPresentacion, 0, 3) as $docResumen): ?>
                        <?php $urlDocRes = trim((string)($docResumen['url'] ?? '')); ?>
                        <?php if ($urlDocRes !== ''): ?>
                        <a href="<?= htmlspecialchars($urlDocRes, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" onclick="event.stopPropagation();" title="<?= htmlspecialchars((string)($docResumen['nombre'] ?? 'Documento'), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string)($docResumen['nombre'] ?? 'Documento'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($totalDocumentos > 3): ?>
                        <span style="font-size:0.78rem;color:#64748b;">+<?= $totalDocumentos - 3 ?> más (ver detalle)</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($puedeEliminarInscripcion || $totalPagado > 0): ?>
                <div class="taller-resp-pago" onclick="event.stopPropagation();">
                    <div class="taller-resp-pago-acciones">
                    <?php if ($puedeEliminarInscripcion): ?>
                    <form method="post" action="<?= PUBLIC_URL ?>?url=talleres/respuestas/eliminar" style="margin:0;" onsubmit="return confirm('¿Eliminar esta inscripción del taller? La persona no se borra de Personas, solo sale de este listado.');">
                        <input type="hidden" name="id" value="<?= (int)$id ?>">
                        <input type="hidden" name="id_respuesta" value="<?= (int)$idRespuesta ?>">
                        <button type="submit" class="taller-resp-pago-btn eliminar" title="Eliminar inscripción" aria-label="Eliminar inscripción">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    </div>
                    <?php if ($totalPagado > 0): ?>
                    <span class="taller-resp-pago-status ok">$<?= number_format($totalPagado, 0, ',', '.') ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($puedeAsignarMinisterio && !$esPresentacionNinos && $esSinMinisterio && $ministeriosAsignacion !== []): ?>
                <form class="taller-asig-min" method="post" action="<?= PUBLIC_URL ?>?url=talleres/respuestas/asignar-ministerio" onclick="event.stopPropagation();" onsubmit="if(!this.id_ministerio.value){alert('Elija el ministerio.');return false;} if(!this.tipo_persona.value){alert('Elija si es antiguo o nuevo.');return false;}">
                    <input type="hidden" name="id" value="<?= (int)$id ?>">
                    <input type="hidden" name="id_respuesta" value="<?= (int)$idRespuesta ?>">
                    <input type="hidden" name="tipo_persona" value="">
                    <span class="taller-asig-label">Asignar</span>
                    <select name="id_ministerio" required aria-label="Ministerio">
                        <option value="">Ministerio…</option>
                        <?php foreach ($ministeriosAsignacion as $minAsig): ?>
                        <option value="<?= (int)$minAsig['id'] ?>"><?= htmlspecialchars((string)$minAsig['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn-antiguo" data-tipo="antiguo">Antiguo</button>
                    <button type="button" class="btn-nuevo" data-tipo="nuevo">Nuevo</button>
                    <button type="submit" class="btn-guardar">Guardar</button>
                    <p class="taller-asig-hint">Elija ministerio, antiguo o nuevo, y pulse Guardar. Se usan los datos de esta inscripción.</p>
                </form>
                <?php endif; ?>
            </summary>
            <div class="taller-resp-body">
                <?php foreach ($secciones as $seccion): ?>
                    <?php $camposSec = is_array($seccion['campos'] ?? null) ? $seccion['campos'] : []; ?>
                    <?php if ($camposSec === []) continue; ?>
                    <section class="taller-resp-seccion">
                        <h4><?= htmlspecialchars((string)($seccion['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h4>
                        <dl class="taller-resp-grid">
                            <?php foreach ($camposSec as $campo): ?>
                                <?php
                                $clave = (string)($campo['clave'] ?? '');
                                $etiqueta = (string)($campo['etiqueta'] ?? $clave);
                                $tipoCampo = (string)($campo['tipo'] ?? 'text');
                                $texto = taller_resp_valor($valores, $clave);
                                $esLargo = $tipoCampo === 'textarea' || $tipoCampo === 'tabla' || mb_strlen($texto) > 80;
                                $claseCampo = 'taller-resp-campo' . ($esLargo ? ' full-width' : '');
                                ?>
                                <div class="<?= $claseCampo ?>">
                                    <dt><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></dt>
                                    <dd class="<?= ($tipoCampo === 'firma' && $firmaImg === '' && $texto === '') || ($tipoCampo !== 'firma' && $texto === '') ? 'empty' : '' ?>">
                                        <?php if ($tipoCampo === 'firma' && $firmaImg !== ''): ?>
                                            <img src="<?= htmlspecialchars($firmaImg, ENT_QUOTES, 'UTF-8') ?>" alt="Firma" class="taller-resp-firma">
                                        <?php elseif ($texto !== ''): ?>
                                            <?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </section>
                <?php endforeach; ?>
                <?php if ($totalDocumentos > 0): ?>
                <section class="taller-resp-seccion">
                    <h4>Documentos adjuntos</h4>
                    <ul class="taller-resp-docs" style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;">
                        <?php foreach ($documentosPresentacion as $doc): ?>
                        <?php
                        $nombreDoc = trim((string)($doc['nombre'] ?? 'Documento'));
                        $urlDoc = trim((string)($doc['url'] ?? ''));
                        $fechaDoc = trim((string)($doc['fecha'] ?? ''));
                        ?>
                        <li style="border:1px solid #dbeafe;border-radius:8px;padding:10px 12px;background:#f8fafc;">
                            <?php if ($urlDoc !== ''): ?>
                            <a href="<?= htmlspecialchars($urlDoc, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" style="font-weight:600;color:#1d4ed8;text-decoration:none;">
                                <?= htmlspecialchars($nombreDoc, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <?php else: ?>
                            <strong><?= htmlspecialchars($nombreDoc, ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php endif; ?>
                            <?php if ($fechaDoc !== ''): ?>
                            <div style="font-size:0.82rem;color:#64748b;margin-top:4px;">Subido: <?= htmlspecialchars($fechaDoc, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>
            </div>
        </details>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
        <div class="taller-min-header taller-min-total">
            <span></span>
            <span>Total inscritos</span>
            <span class="col-total"><?= (int)$total ?></span>
            <span></span>
        </div>
    </div>
</section>
<?php endif; ?>

</div>
<?php endif; ?>

<?php if ($puedeGraficas && $graficasList !== []): ?>
<div id="panel-graficas" class="taller-resp-panel <?= ($soloGraficas || $tabActiva === 'graficas') ? 'active' : '' ?>">
    <p class="text-muted">
        Resumen visual de las <?= $totalInscripciones ?> inscripción(es). Las gráficas se generan a partir de las respuestas guardadas en el cuestionario.
    </p>

    <div class="taller-graf-resumen">
        <div class="taller-graf-card">
            <strong><?= $totalInscripciones ?></strong>
            <span>Inscripciones</span>
        </div>
        <div class="taller-graf-card">
            <strong><?= count($graficasList) ?></strong>
            <span>Indicadores graficados</span>
        </div>
        <?php if ($totalHijosTabla > 0): ?>
        <div class="taller-graf-card">
            <strong><?= $totalHijosTabla ?></strong>
            <span>Hijos registrados (tabla)</span>
        </div>
        <?php endif; ?>
    </div>

    <div class="taller-graf-grid" id="taller-graf-grid">
        <?php foreach ($graficasList as $graf): ?>
            <?php
            $chartId = (string)($graf['id'] ?? 'chart');
            $chartTipo = (string)($graf['tipo'] ?? 'bar');
            $chartTitulo = (string)($graf['titulo'] ?? '');
            $esAbierta = $chartTipo === 'abierta';
            $claseDonut = $chartTipo === 'doughnut' ? ' donut' : '';
            $filasAbiertas = is_array($graf['filas'] ?? null) ? $graf['filas'] : [];
            $totalAbiertas = (int)($graf['total_respuestas'] ?? 0);
            ?>
            <article class="taller-graf-item<?= $claseDonut ?><?= $esAbierta ? ' abierta' : '' ?>">
                <h5><?= htmlspecialchars($chartTitulo, ENT_QUOTES, 'UTF-8') ?></h5>
                <?php if ($esAbierta): ?>
                <p class="taller-graf-abierta-nota">
                    Respuestas abiertas (<?= $totalAbiertas ?> persona<?= $totalAbiertas === 1 ? '' : 's' ?>).
                    Se unifican frases equivalentes o con el mismo sentido (por ejemplo «aprender más de Dios» y «conocer más a Dios»).
                </p>
                <div class="taller-graf-abierta-wrap">
                    <div class="chart-mount" id="<?= htmlspecialchars($chartId, ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="table-container">
                        <table class="taller-graf-abierta-tabla">
                            <thead>
                                <tr>
                                    <th>Respuesta unificada</th>
                                    <th class="col-cant">Personas</th>
                                    <th>Cómo lo escribieron</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filasAbiertas as $filaAb): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($filaAb['grupo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="col-cant"><?= (int)($filaAb['total'] ?? 0) ?></td>
                                    <td class="variantes"><?= htmlspecialchars(implode(' · ', is_array($filaAb['variantes'] ?? null) ? $filaAb['variantes'] : []), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="chart-mount" id="<?= htmlspecialchars($chartId, ENT_QUOTES, 'UTF-8') ?>"></div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</div>
<?php elseif ($soloGraficas): ?>
<div class="taller-resp-empty">Aún no hay datos para graficar en este formulario.</div>
<?php endif; ?>

<script>
(function() {
    const input = document.getElementById('filtro-respuestas');
    const contador = document.getElementById('filtro-contador');
    const items = document.querySelectorAll('.taller-resp-item');
    const grupos = document.querySelectorAll('.taller-min-acordeon');
    if (!input || !items.length) return;

    function filtrar() {
        const q = input.value.trim().toLowerCase();
        let visibles = 0;
        items.forEach(function(el) {
            const ok = q === '' || (el.getAttribute('data-busqueda') || '').indexOf(q) >= 0;
            el.classList.toggle('hidden-by-filter', !ok);
            if (ok) visibles++;
        });
        grupos.forEach(function(grupo) {
            const hayVisibles = Array.from(grupo.querySelectorAll('.taller-resp-item')).some(function(el) {
                return !el.classList.contains('hidden-by-filter');
            });
            grupo.classList.toggle('hidden-by-filter', q !== '' && !hayVisibles);
            if (q !== '' && hayVisibles) {
                grupo.open = true;
            }
        });
        if (contador) {
            contador.textContent = visibles + ' mostradas';
        }
    }

    input.addEventListener('input', filtrar);
})();
</script>
<script>
(function() {
    const buscar = document.getElementById('taller-pago-buscar');
    const filtro = document.getElementById('taller-pago-filtro');
    const filas = document.querySelectorAll('#taller-pago-tbody tr.taller-pago-fila');
    const tfootLabel = document.getElementById('taller-pago-tfoot-label');
    const tfootValor = document.getElementById('taller-pago-tfoot-valor');
    const aviso = document.getElementById('taller-pago-resumen-filtro');
    const cards = document.querySelectorAll('.taller-uv-pagos [data-filtro-pago]');
    if (!filtro || !filas.length) return;

    function formatoDinero(n) {
        return '$' + Math.round(n).toLocaleString('es-CO');
    }

    function aplicarFiltro(origen) {
        const q = buscar ? buscar.value.trim().toLowerCase() : '';
        const modo = filtro.value;
        let visibles = 0;
        let suma = 0;
        filas.forEach(function(tr) {
            const pagado = tr.getAttribute('data-pago') === '1';
            const texto = (tr.getAttribute('data-busqueda') || '');
            let ok = true;
            if (modo === 'pagados' && !pagado) ok = false;
            if (modo === 'sin_pago' && pagado) ok = false;
            if (q !== '' && texto.indexOf(q) === -1) ok = false;
            tr.style.display = ok ? '' : 'none';
            if (ok) {
                visibles++;
                suma += parseFloat(tr.getAttribute('data-valor') || '0') || 0;
            }
        });
        if (tfootLabel) {
            tfootLabel.textContent = 'Total recaudado (' + visibles + ' mostrada' + (visibles === 1 ? '' : 's') + ')';
        }
        if (tfootValor) {
            tfootValor.textContent = formatoDinero(suma);
        }
        if (aviso) {
            if (modo === 'pagados') {
                aviso.hidden = false;
                aviso.textContent = 'Filtro: quién sí pagó';
            } else if (modo === 'sin_pago') {
                aviso.hidden = false;
                aviso.textContent = 'Filtro: quién no pagó';
            } else {
                aviso.hidden = true;
                aviso.textContent = '';
            }
        }
        cards.forEach(function(card) {
            card.classList.toggle('is-active', card.getAttribute('data-filtro-pago') === modo);
        });
    }

    filtro.addEventListener('change', function() { aplicarFiltro('select'); });
    if (buscar) buscar.addEventListener('input', function() { aplicarFiltro('buscar'); });
    cards.forEach(function(card) {
        card.addEventListener('click', function() {
            filtro.value = card.getAttribute('data-filtro-pago') || 'todos';
            aplicarFiltro('card');
        });
    });
    aplicarFiltro('init');
})();
</script>
<script>
(function() {
    document.querySelectorAll('.taller-asig-min').forEach(function(form) {
        form.addEventListener('click', function(ev) {
            const btn = ev.target.closest('[data-tipo]');
            if (!btn || !form.contains(btn)) return;
            ev.preventDefault();
            ev.stopPropagation();
            const tipo = btn.getAttribute('data-tipo') || '';
            const hidden = form.querySelector('input[name="tipo_persona"]');
            if (hidden) hidden.value = tipo;
            form.querySelectorAll('[data-tipo]').forEach(function(el) {
                el.classList.toggle('activo', el === btn);
            });
        });
    });
})();
</script>
<script>
(function() {
    const marcado = document.querySelector('.taller-resp-resaltado');
    if (!marcado) return;
    marcado.scrollIntoView({ behavior: 'smooth', block: 'center' });
})();
</script>

<?php if ($puedeGraficas && $graficasList !== []): ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
(function() {
    const graficasData = <?= json_encode($graficasList, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const colores = ['#2563eb', '#7c3aed', '#059669', '#d97706', '#dc2626', '#0891b2', '#4f46e5', '#be185d', '#65a30d', '#ea580c'];
    const chartInstances = {};
    let graficasRenderizadas = false;

    const tabs = document.querySelectorAll('.taller-resp-tabs button[data-tab]');
    const panelLista = document.getElementById('panel-lista');
    const panelGraficas = document.getElementById('panel-graficas');

    function activarTab(tab) {
        tabs.forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tab);
        });
        if (panelLista) panelLista.classList.toggle('active', tab === 'lista');
        if (panelGraficas) panelGraficas.classList.toggle('active', tab === 'graficas');
        if (tab === 'graficas') {
            renderGraficas();
        }
    }

    tabs.forEach(function(btn) {
        btn.addEventListener('click', function() {
            activarTab(btn.getAttribute('data-tab') || 'lista');
        });
    });

    function renderGraficas() {
        if (graficasRenderizadas || typeof ApexCharts === 'undefined') return;
        graficasData.forEach(function(chartData) {
            const mount = document.getElementById(chartData.id);
            if (!mount || chartInstances[chartData.id]) return;

            const labels = chartData.labels || [];
            const data = chartData.data || [];
            const tipo = chartData.tipo || 'bar';
            const horizontal = !!chartData.horizontal || tipo === 'abierta';

            let options;
            if (tipo === 'doughnut') {
                options = {
                    chart: { type: 'donut', height: 300, toolbar: { show: false } },
                    series: data,
                    labels: labels,
                    colors: colores,
                    legend: { position: 'bottom', fontSize: '12px' },
                    dataLabels: { enabled: true },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '58%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        formatter: function(w) {
                                            return w.globals.seriesTotals.reduce(function(a, b) { return a + b; }, 0);
                                        }
                                    }
                                }
                            }
                        }
                    }
                };
            } else if (horizontal) {
                options = {
                    chart: { type: 'bar', height: Math.max(300, labels.length * 32), toolbar: { show: false } },
                    series: [{ name: 'Respuestas', data: data }],
                    colors: ['#2563eb'],
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 4,
                            barHeight: '72%'
                        }
                    },
                    dataLabels: { enabled: true },
                    xaxis: {
                        categories: labels
                    },
                    yaxis: {
                        labels: {
                            maxWidth: 300,
                            style: { fontSize: '11px' }
                        }
                    },
                    legend: { show: false }
                };
            } else {
                options = {
                    chart: { type: 'bar', height: 300, toolbar: { show: false } },
                    series: [{ name: 'Respuestas', data: data }],
                    colors: colores,
                    xaxis: {
                        categories: labels,
                        labels: { rotate: -35, trim: true, maxHeight: 80 }
                    },
                    yaxis: {
                        min: 0,
                        forceNiceScale: true,
                        labels: { formatter: function(v) { return Math.round(v); } }
                    },
                    dataLabels: { enabled: true },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            borderRadius: 4,
                            columnWidth: '55%',
                            distributed: true
                        }
                    },
                    legend: { show: false }
                };
            }

            chartInstances[chartData.id] = new ApexCharts(mount, options);
            chartInstances[chartData.id].render();
        });
        graficasRenderizadas = true;
    }

    if (document.getElementById('panel-graficas')?.classList.contains('active')) {
        renderGraficas();
    }
})();
</script>
<?php endif; ?>

<?php include VIEWS . '/layout/footer.php'; ?>

<?php include VIEWS . '/layout/header.php'; ?>
<?php
$tituloDashboard = (string)($titulo_dashboard ?? 'Dashboard Escuelas');
$lineaDashboard = (string)($linea_dashboard ?? 'universidad_vida');
$rutaDashboard = (string)($ruta_dashboard ?? 'reportes/dashboard-escuelas-uv');
$anio = (int)($anio ?? date('Y'));
$mes = (int)($mes ?? date('n'));
$filtroMinisterio = (string)($filtro_ministerio ?? '');
$filtroLider = (string)($filtro_lider ?? '');
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
$tablaPagosUv = (array)($tabla_pagos_uv ?? []);
$tablaPagosUvModo = (string)($tabla_pagos_uv_modo ?? 'mensual');
$tablaUvModoConsolidar = (array)($tabla_uv_modo_consolidar ?? []);
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
.dash-inline-filters label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
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
    .leader-table th, .leader-table td { padding: 5px 6px; font-size: 0.74rem; }
    .leader-table th { font-size: 0.66rem; }
}
</style>

<div class="dashboard-escuelas-wrap">
    <div class="dash-head">
        <div>
            <h2><?= htmlspecialchars($tituloDashboard) ?></h2>
            <small style="color:#64748b;">Módulo exclusivo: <?= htmlspecialchars($labelLinea) ?> · Meta por líder (hombre/mujer): <?= $metaPorLider ?> inscritos</small>
        </div>
        <div class="dash-toolbar">
            <a href="<?= PUBLIC_URL ?>index.php?url=reportes/dashboard-ganar" class="btn btn-primary">Dashboard Ganar</a>
        </div>
    </div>

    <div class="dash-card">
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

            <div class="group">
                <label>Mes</label>
                <select name="mes" onchange="this.form.submit()">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>><?= htmlspecialchars($meses[$m] ?? (string)$m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>

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
        </form>
    </div>

    <div class="dash-card">
        <h4 class="section-title">Cumplimiento por líder de célula (meta = <?= $metaPorLider ?>)</h4>
        <small style="color:#64748b;display:block;margin-bottom:10px;">Se incluye líder de célula aunque también sea líder de 12.</small>

        <h5 class="section-title">Líderes hombres</h5>
        <div class="table-wrap">
            <table class="leader-table js-dash-filterable" data-dash-gen-mode="hmjt" data-dash-enable-pago="1">
                <thead>
                    <tr>
                        <th>Líder</th>
                        <th>Ministerio</th>
                        <th>Inscritos</th>
                        <th>Pagados</th>
                        <th>Meta</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lideresHombre)): ?>
                        <?php foreach ($lideresHombre as $row): ?>
                            <tr<?= $dashAttrsLeaderRow($row) ?>>
                                <td><?= htmlspecialchars((string)($row['lider'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($row['ministerio'] ?? 'Sin ministerio')) ?></td>
                                <td><?= (int)($row['inscritos_grupo'] ?? $row['inscritos_mes'] ?? 0) ?></td>
                                <td><?= (int)($row['pagados_grupo'] ?? $row['pagados_lider'] ?? 0) ?></td>
                                <td><?= (int)($row['meta_lider'] ?? 0) ?></td>
                                <td><span class="estado <?= htmlspecialchars((string)($row['semaforo'] ?? 'rojo')) ?>"><?= htmlspecialchars(ucfirst((string)($row['semaforo'] ?? 'rojo'))) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No hay líderes hombres para este filtro.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h5 class="section-title" style="margin-top:14px;">Líderes mujeres</h5>
        <div class="table-wrap">
            <table class="leader-table js-dash-filterable" data-dash-gen-mode="hmjt" data-dash-enable-pago="1">
                <thead>
                    <tr>
                        <th>Líder</th>
                        <th>Ministerio</th>
                        <th>Inscritos</th>
                        <th>Pagados</th>
                        <th>Meta</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lideresMujer)): ?>
                        <?php foreach ($lideresMujer as $row): ?>
                            <tr<?= $dashAttrsLeaderRow($row) ?>>
                                <td><?= htmlspecialchars((string)($row['lider'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($row['ministerio'] ?? 'Sin ministerio')) ?></td>
                                <td><?= (int)($row['inscritos_grupo'] ?? $row['inscritos_mes'] ?? 0) ?></td>
                                <td><?= (int)($row['pagados_grupo'] ?? $row['pagados_lider'] ?? 0) ?></td>
                                <td><?= (int)($row['meta_lider'] ?? 0) ?></td>
                                <td><span class="estado <?= htmlspecialchars((string)($row['semaforo'] ?? 'rojo')) ?>"><?= htmlspecialchars(ucfirst((string)($row['semaforo'] ?? 'rojo'))) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No hay líderes mujeres para este filtro.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h5 class="section-title" style="margin-top:14px;">Líderes jóvenes</h5>
        <div class="table-wrap">
            <table class="leader-table js-dash-filterable" data-dash-gen-mode="hmjt" data-dash-enable-pago="1">
                <thead>
                    <tr>
                        <th>Líder</th>
                        <th>Ministerio</th>
                        <th>Inscritos</th>
                        <th>Pagados</th>
                        <th>Meta</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lideresJoven)): ?>
                        <?php foreach ($lideresJoven as $row): ?>
                            <tr<?= $dashAttrsLeaderRow($row) ?>>
                                <td><?= htmlspecialchars((string)($row['lider'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($row['ministerio'] ?? 'Sin ministerio')) ?></td>
                                <td><?= (int)($row['inscritos_grupo'] ?? $row['inscritos_mes'] ?? 0) ?></td>
                                <td><?= (int)($row['pagados_grupo'] ?? $row['pagados_lider'] ?? 0) ?></td>
                                <td><?= (int)($row['meta_lider'] ?? 0) ?></td>
                                <td><span class="estado <?= htmlspecialchars((string)($row['semaforo'] ?? 'rojo')) ?>"><?= htmlspecialchars(ucfirst((string)($row['semaforo'] ?? 'rojo'))) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No hay líderes jóvenes para este filtro.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h5 class="section-title" style="margin-top:14px;">Líderes teens</h5>
        <div class="table-wrap">
            <table class="leader-table js-dash-filterable" data-dash-gen-mode="hmjt" data-dash-enable-pago="1">
                <thead>
                    <tr>
                        <th>Líder</th>
                        <th>Ministerio</th>
                        <th>Inscritos</th>
                        <th>Pagados</th>
                        <th>Meta</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lideresTeen)): ?>
                        <?php foreach ($lideresTeen as $row): ?>
                            <tr<?= $dashAttrsLeaderRow($row) ?>>
                                <td><?= htmlspecialchars((string)($row['lider'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($row['ministerio'] ?? 'Sin ministerio')) ?></td>
                                <td><?= (int)($row['inscritos_grupo'] ?? $row['inscritos_mes'] ?? 0) ?></td>
                                <td><?= (int)($row['pagados_grupo'] ?? $row['pagados_lider'] ?? 0) ?></td>
                                <td><?= (int)($row['meta_lider'] ?? 0) ?></td>
                                <td><span class="estado <?= htmlspecialchars((string)($row['semaforo'] ?? 'rojo')) ?>"><?= htmlspecialchars(ucfirst((string)($row['semaforo'] ?? 'rojo'))) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No hay líderes teens para este filtro.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($lideresOtros)): ?>
            <h5 class="section-title" style="margin-top:14px;">Líderes sin género identificado</h5>
            <div class="table-wrap">
                <table class="leader-table js-dash-filterable" data-dash-gen-mode="hmjt" data-dash-enable-pago="1">
                    <thead>
                        <tr>
                            <th>Líder</th>
                            <th>Ministerio</th>
                            <th>Inscritos</th>
                            <th>Pagados</th>
                            <th>Meta</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lideresOtros as $row): ?>
                            <tr<?= $dashAttrsLeaderRow($row) ?>>
                                <td><?= htmlspecialchars((string)($row['lider'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($row['ministerio'] ?? 'Sin ministerio')) ?></td>
                                <td><?= (int)($row['inscritos_grupo'] ?? $row['inscritos_mes'] ?? 0) ?></td>
                                <td><?= (int)($row['pagados_grupo'] ?? $row['pagados_lider'] ?? 0) ?></td>
                                <td><?= (int)($row['meta_lider'] ?? 0) ?></td>
                                <td><span class="estado <?= htmlspecialchars((string)($row['semaforo'] ?? 'rojo')) ?>"><?= htmlspecialchars(ucfirst((string)($row['semaforo'] ?? 'rojo'))) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($lineaDashboard === 'universidad_vida'): ?>
        <?php
        $totUvConsHombres = 0;
        $totUvConsMujeres = 0;
        $totUvConsJovenes = 0;
        $totUvConsTotal = 0;
        $totUvConsAsistencias = 0;
        foreach ($tablaUvModoConsolidar as $filaConsUv) {
            $totUvConsHombres += (int)($filaConsUv['hombres'] ?? 0);
            $totUvConsMujeres += (int)($filaConsUv['mujeres'] ?? 0);
            $totUvConsJovenes += (int)($filaConsUv['jovenes'] ?? 0);
            $totUvConsTotal += (int)($filaConsUv['total'] ?? 0);
            $totUvConsAsistencias += (int)($filaConsUv['asistencias_reales'] ?? 0);
        }

        $totInscritosUv = 0;
        $totPagadosUv = 0;
        $totValorUv = 0.0;
        $totInsJovenUv = 0;
        $totInsTeenUv = 0;
        $totPagJovenUv = 0;
        $totPagTeenUv = 0;
        foreach ($tablaPagosUv as $filaPagoUv) {
            $totInscritosUv += (int)($filaPagoUv['Inscritos'] ?? 0);
            $totPagadosUv += (int)($filaPagoUv['Pagados'] ?? 0);
            $totValorUv += (float)($filaPagoUv['Valor_Recaudado'] ?? 0);
            $totInsJovenUv += (int)($filaPagoUv['Inscritos_Jovenes'] ?? 0);
            $totInsTeenUv += (int)($filaPagoUv['Inscritos_Teens'] ?? 0);
            $totPagJovenUv += (int)($filaPagoUv['Pagados_Jovenes'] ?? 0);
            $totPagTeenUv += (int)($filaPagoUv['Pagados_Teens'] ?? 0);
        }
        $totPendientesUv = max(0, $totInscritosUv - $totPagadosUv);
        $totPctUv = $totInscritosUv > 0 ? round(($totPagadosUv / $totInscritosUv) * 100, 1) : 0;
        ?>
        <div class="dash-card">
            <h4 class="section-title">Universidad de la Vida por ministerio (modo Consolidar)</h4>
            <small style="color:#64748b;display:block;margin-bottom:10px;">Mismo criterio de clasificacion del modulo Consolidar para comparar datos 1 a 1.</small>

            <div class="table-wrap">
                <table class="leader-table js-dash-filterable" data-dash-gen-mode="hmjt" data-dash-enable-pago="0">
                    <thead>
                        <tr>
                            <th>Ministerio</th>
                            <th>Hombres</th>
                            <th>Mujeres</th>
                            <th>Jovenes</th>
                            <th>Total</th>
                            <th>Asistencias reales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tablaUvModoConsolidar)): ?>
                            <?php foreach ($tablaUvModoConsolidar as $filaConsUv): ?>
                                <tr<?= $dashAttrsUvMinRow($filaConsUv) ?>>
                                    <td><?= htmlspecialchars((string)($filaConsUv['ministerio'] ?? 'Sin ministerio')) ?></td>
                                    <td><?= (int)($filaConsUv['hombres'] ?? 0) ?></td>
                                    <td><?= (int)($filaConsUv['mujeres'] ?? 0) ?></td>
                                    <td><?= (int)($filaConsUv['jovenes'] ?? 0) ?></td>
                                    <td><?= (int)($filaConsUv['total'] ?? 0) ?></td>
                                    <td><?= (int)($filaConsUv['asistencias_reales'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No hay datos de Universidad de la Vida para este filtro.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="js-dash-tfoot">
                        <tr>
                            <th>TOTAL</th>
                            <th><?= $totUvConsHombres ?></th>
                            <th><?= $totUvConsMujeres ?></th>
                            <th><?= $totUvConsJovenes ?></th>
                            <th><?= $totUvConsTotal ?></th>
                            <th><?= $totUvConsAsistencias ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="dash-card">
            <h4 class="section-title">Pagos Universidad de la Vida (de inscritos)</h4>
            <?php if ($tablaPagosUvModo === 'consolidar'): ?>
                <small style="color:#64748b;display:block;margin-bottom:6px;">Origen: inscripciones de Universidad de la Vida en modo consolidado (misma consulta que «UV por ministerio» y líderes arriba; ficha en inscripción, no el listado por cédula de la pantalla Escuelas → Pagos, que usa movimientos en otra tabla).</small>
                <small style="color:#64748b;display:block;margin-bottom:10px;">Se cuenta como pagado si en esa inscripción hay valor de pago, método o referencia. En filtros de segmento: hombres y mujeres son adultos (excluye jóvenes 13–30 y teens 9–12); jóvenes y teens van aparte.</small>
            <?php else: ?>
                <small style="color:#64748b;display:block;margin-bottom:10px;">Periodo: <?= htmlspecialchars($fechaInicioMes) ?> a <?= htmlspecialchars($fechaFinMes) ?></small>
            <?php endif; ?>

            <div class="table-wrap">
                <table class="leader-table js-dash-filterable" data-dash-gen-mode="hmjt" data-dash-enable-pago="1">
                    <thead>
                        <tr>
                            <th>Ministerio</th>
                            <th>Inscritos</th>
                            <th>Pagados</th>
                            <th>Pendientes</th>
                            <th>Jóvenes</th>
                            <th>Teens</th>
                            <th>Pag. Jóvenes</th>
                            <th>Pag. Teens</th>
                            <th>% Pago</th>
                            <th>Valor recaudado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tablaPagosUv)): ?>
                            <?php foreach ($tablaPagosUv as $filaPagoUv): ?>
                                <?php
                                $ins = (int)($filaPagoUv['Inscritos'] ?? 0);
                                $pag = (int)($filaPagoUv['Pagados'] ?? 0);
                                $pend = max(0, $ins - $pag);
                                $pct = $ins > 0 ? round(($pag / $ins) * 100, 1) : 0;
                                $valor = (float)($filaPagoUv['Valor_Recaudado'] ?? 0);
                                $insJ = (int)($filaPagoUv['Inscritos_Jovenes'] ?? 0);
                                $insT = (int)($filaPagoUv['Inscritos_Teens'] ?? 0);
                                $pagJ = (int)($filaPagoUv['Pagados_Jovenes'] ?? 0);
                                $pagT = (int)($filaPagoUv['Pagados_Teens'] ?? 0);
                                ?>
                                <tr<?= $dashAttrsPagosRow($filaPagoUv) ?>>
                                    <td><?= htmlspecialchars((string)($filaPagoUv['Ministerio'] ?? 'Sin ministerio')) ?></td>
                                    <td><?= $ins ?></td>
                                    <td><?= $pag ?></td>
                                    <td><?= $pend ?></td>
                                    <td><?= $insJ ?></td>
                                    <td><?= $insT ?></td>
                                    <td><?= $pagJ ?></td>
                                    <td><?= $pagT ?></td>
                                    <td><?= number_format($pct, 1) ?>%</td>
                                    <td>$ <?= number_format($valor, 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10">No hay inscritos de Universidad de la Vida para este filtro y periodo.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="js-dash-tfoot">
                        <tr>
                            <th>TOTAL</th>
                            <th><?= $totInscritosUv ?></th>
                            <th><?= $totPagadosUv ?></th>
                            <th><?= $totPendientesUv ?></th>
                            <th><?= $totInsJovenUv ?></th>
                            <th><?= $totInsTeenUv ?></th>
                            <th><?= $totPagJovenUv ?></th>
                            <th><?= $totPagTeenUv ?></th>
                            <th><?= number_format($totPctUv, 1) ?>%</th>
                            <th>$ <?= number_format($totValorUv, 0, ',', '.') ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <?php if ($filtroMinisterio !== '' && !empty($detalleLideresMinisterioUv)): ?>
            <?php
            $totDetIns = 0;
            $totDetHom = 0;
            $totDetMuj = 0;
            $totDetJov = 0;
            $totDetAsi = 0;
            $totDetPag = 0;
            $totDetPen = 0;
            $totDetVal = 0.0;
            foreach ($detalleLideresMinisterioUv as $detLiderUv) {
                $totDetIns += (int)($detLiderUv['inscritos'] ?? 0);
                $totDetHom += (int)($detLiderUv['hombres'] ?? 0);
                $totDetMuj += (int)($detLiderUv['mujeres'] ?? 0);
                $totDetJov += (int)($detLiderUv['jovenes'] ?? 0);
                $totDetAsi += (int)($detLiderUv['asistencias_reales'] ?? 0);
                $totDetPag += (int)($detLiderUv['pagados'] ?? 0);
                $totDetPen += (int)($detLiderUv['pendientes'] ?? 0);
                $totDetVal += (float)($detLiderUv['valor_recaudado'] ?? 0);
            }
            $totDetPct = $totDetIns > 0 ? round(($totDetPag / $totDetIns) * 100, 1) : 0;
            ?>
            <div class="dash-card">
                <h4 class="section-title">Detalle por líderes del ministerio filtrado</h4>
                <small style="color:#64748b;display:block;margin-bottom:10px;">
                    Ministerio: <?= htmlspecialchars($nombreMinisterioFiltrado !== '' ? $nombreMinisterioFiltrado : 'Seleccionado') ?>
                </small>

                <div class="table-wrap">
                    <table class="leader-table js-dash-filterable" data-dash-gen-mode="hmjt" data-dash-enable-pago="1"
                        data-dash-ministry-label="<?= htmlspecialchars($nombreMinisterioFiltrado !== '' ? $nombreMinisterioFiltrado : 'Ministerio filtrado', ENT_QUOTES, 'UTF-8') ?>">
                        <thead>
                            <tr>
                                <th>Líder célula</th>
                                <th>Inscritos</th>
                                <th>Hombres</th>
                                <th>Mujeres</th>
                                <th>Jóvenes</th>
                                <th>Asist. reales</th>
                                <th>Pagados</th>
                                <th>Pendientes</th>
                                <th>% Pago</th>
                                <th>Valor recaudado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalleLideresMinisterioUv as $detLiderUv): ?>
                                <tr<?= $dashAttrsDetalleRow($detLiderUv) ?>>
                                    <td><?= htmlspecialchars((string)($detLiderUv['lider'] ?? 'Sin líder')) ?></td>
                                    <td><?= (int)($detLiderUv['inscritos'] ?? 0) ?></td>
                                    <td><?= (int)($detLiderUv['hombres'] ?? 0) ?></td>
                                    <td><?= (int)($detLiderUv['mujeres'] ?? 0) ?></td>
                                    <td><?= (int)($detLiderUv['jovenes'] ?? 0) ?></td>
                                    <td><?= (int)($detLiderUv['asistencias_reales'] ?? 0) ?></td>
                                    <td><?= (int)($detLiderUv['pagados'] ?? 0) ?></td>
                                    <td><?= (int)($detLiderUv['pendientes'] ?? 0) ?></td>
                                    <td><?= number_format((float)($detLiderUv['pct_pago'] ?? 0), 1) ?>%</td>
                                    <td>$ <?= number_format((float)($detLiderUv['valor_recaudado'] ?? 0), 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="js-dash-tfoot">
                            <tr>
                                <th>TOTAL</th>
                                <th><?= $totDetIns ?></th>
                                <th><?= $totDetHom ?></th>
                                <th><?= $totDetMuj ?></th>
                                <th><?= $totDetJov ?></th>
                                <th><?= $totDetAsi ?></th>
                                <th><?= $totDetPag ?></th>
                                <th><?= $totDetPen ?></th>
                                <th><?= number_format($totDetPct, 1) ?>%</th>
                                <th>$ <?= number_format($totDetVal, 0, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php endif; ?>
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
            if (genMode === 'ajt') {
                const a = parseInt(tr.getAttribute('data-dash-a') || '0', 10) || 0;
                const j = parseInt(tr.getAttribute('data-dash-j') || '0', 10) || 0;
                const t = parseInt(tr.getAttribute('data-dash-t') || '0', 10) || 0;
                for (let i = 0; i < genKeys.length; i++) {
                    const g = genKeys[i];
                    if (g === 'a' && a <= 0) {
                        return false;
                    }
                    if (g === 'j' && j <= 0) {
                        return false;
                    }
                    if (g === 't' && t <= 0) {
                        return false;
                    }
                }
            } else {
                const h = parseInt(tr.getAttribute('data-dash-h') || '0', 10) || 0;
                const m = parseInt(tr.getAttribute('data-dash-m') || '0', 10) || 0;
                const j = parseInt(tr.getAttribute('data-dash-j') || '0', 10) || 0;
                const t = parseInt(tr.getAttribute('data-dash-t') || '0', 10) || 0;
                for (let i = 0; i < genKeys.length; i++) {
                    const g = genKeys[i];
                    if (g === 'h' && h <= 0) {
                        return false;
                    }
                    if (g === 'm' && m <= 0) {
                        return false;
                    }
                    if (g === 'j' && j <= 0) {
                        return false;
                    }
                    if (g === 't' && t <= 0) {
                        return false;
                    }
                }
            }
        }

        const enablePago = table.getAttribute('data-dash-enable-pago') === '1';
        if (enablePago && tr.getAttribute('data-dash-skip-pago') !== '1') {
            const ins = parseInt(tr.getAttribute('data-dash-ins') || '0', 10) || 0;
            const pag = parseInt(tr.getAttribute('data-dash-pag') || '0', 10) || 0;
            const pend = Math.max(0, ins - pag);
            const p = filt.pago || 'all';
            if (p === 'pend' && pend <= 0) {
                return false;
            }
            if (p === 'ok' && ins > 0 && pend > 0) {
                return false;
            }
        }

        return true;
    }

    function aplicarFiltrosTabla(table) {
        const tbody = table.querySelector('tbody');
        const tfoot = table.querySelector('tfoot.js-dash-tfoot');
        if (!tbody) {
            return;
        }

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

        if (tfoot) {
            tfoot.style.display = activo ? 'none' : '';
        }
    }

    function crearBarraFiltros(table, wrap) {
        const genMode = table.getAttribute('data-dash-gen-mode') || 'hmjt';
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
        segmentHint.textContent = 'Marca uno o varios: la fila debe cumplir todos los marcados (Y). Sin marcar = todos.';
        segmentCol.appendChild(segmentWrap);
        segmentCol.appendChild(segmentHint);

        const segmentInputs = [];
        const segmentDefs = genMode === 'ajt'
            ? [['a', 'Adultos'], ['j', 'Jóvenes'], ['t', 'Teens']]
            : [['h', 'Hombres'], ['m', 'Mujeres'], ['j', 'Jóvenes'], ['t', 'Teens']];
        segmentDefs.forEach(function(def) {
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
        addGroupBlock('Segmento', segmentCol);

        let tieneDesgloseGen = false;
        table.querySelectorAll('tbody tr[data-dash-row]').forEach(function(tr) {
            const s = (parseInt(tr.getAttribute('data-dash-h') || '0', 10) || 0)
                + (parseInt(tr.getAttribute('data-dash-m') || '0', 10) || 0)
                + (parseInt(tr.getAttribute('data-dash-j') || '0', 10) || 0)
                + (parseInt(tr.getAttribute('data-dash-t') || '0', 10) || 0);
            if (genMode === 'ajt') {
                const sa = (parseInt(tr.getAttribute('data-dash-a') || '0', 10) || 0)
                    + (parseInt(tr.getAttribute('data-dash-j') || '0', 10) || 0)
                    + (parseInt(tr.getAttribute('data-dash-t') || '0', 10) || 0);
                if (sa > 0) {
                    tieneDesgloseGen = true;
                }
            } else if (s > 0) {
                tieneDesgloseGen = true;
            }
        });
        if (!tieneDesgloseGen) {
            segmentInputs.forEach(function(inp) {
                inp.disabled = true;
            });
            segmentCol.title = 'Sin desglose por segmento en esta tabla para el periodo o línea seleccionados.';
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

    if (contenedorDashboard && typeof html2canvas !== 'undefined') {
        const tableWraps = Array.from(document.querySelectorAll('.dash-card .table-wrap')).filter(function(wrap) {
            return wrap.querySelector('table') !== null;
        });

        tableWraps.forEach(function(wrap, idx) {
            const table = wrap.querySelector('table');
            const tituloTabla = obtenerTituloDeTabla(wrap, idx + 1);

            const toolRow = document.createElement('div');
            toolRow.className = 'dash-table-tool-row';

            if (table && table.classList.contains('js-dash-filterable')) {
                const leftCol = document.createElement('div');
                leftCol.style.flex = '1';
                leftCol.style.minWidth = '220px';

                const hint = document.createElement('p');
                hint.className = 'dash-filter-hint';
                hint.textContent = 'Filtros por tabla: ministerio; segmento (varias casillas a la vez = debe cumplir todas); pagos. El pie de totales se oculta mientras hay filtros activos.';
                leftCol.appendChild(hint);

                const filtros = crearBarraFiltros(table, wrap);
                leftCol.appendChild(filtros);
                toolRow.appendChild(leftCol);
            }

            const actions = document.createElement('div');
            actions.className = 'table-actions';
            actions.style.marginBottom = '0';
            actions.style.marginLeft = 'auto';

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
            wrap.parentNode.insertBefore(toolRow, wrap);
        });
    }

})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>

<?php include VIEWS . '/layout/header.php'; ?>

<?php
$procesoGanar = $proceso_ganar ?? [
    'Ganar' => 0,
    'Consolidar' => 0,
    'Discipular' => 0,
    'Enviar' => 0,
    'Sin_Proceso' => 0,
    'Total' => 0
];

$resumenOrigen = $resumen_origen_ganados ?? [
    'Ganados_Celula' => 0,
    'Ganados_Domingo' => 0,
    'Total' => 0
];

$almasPorEdades = $almas_por_edades ?? [
    'Kids' => 0,
    'Teens' => 0,
    'Rocas' => 0,
    'Jovenes' => 0,
    'Adultos' => 0,
    'Adultos_Mayores' => 0,
    'Sin_Dato' => 0
];

$sumEsperadas = 0;
$sumReales = 0;
foreach (($asistencia_celulas ?? []) as $filaCelula) {
    $sumEsperadas += (int)($filaCelula['Asistencias_Esperadas'] ?? 0);
    $sumReales += (int)($filaCelula['Asistencias_Reales'] ?? 0);
}
$promedioAsistencia = $sumEsperadas > 0 ? round(($sumReales / $sumEsperadas) * 100, 1) : 0;

$cumplimientoMetas = $cumplimiento_metas ?? [
    'titulo' => 'GANAR',
    'inicio' => '',
    'fin' => '',
    'meses' => [],
    'rows' => [],
    'totales' => ['meta' => 0, 'pendiente' => 0, 'ganados' => 0, 'meses' => []]
];

$indicadoresCelulas = $indicadores_celulas ?? [
    'semestre' => ['inicio' => '', 'fin' => ''],
    'totales' => [
        'total_celulas' => 0,
        'nuevas_semestre' => 0,
        'cerradas_semestre' => 0,
        'reportadas_semana' => 0,
        'no_reportadas_semana' => 0
    ],
    'por_ministerio' => [],
    'por_red' => []
];

$tablaAperturasCelulas = $tabla_aperturas_celulas ?? [
    'anio' => (int)date('Y'),
    'meses' => [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
    ],
    'rows' => [],
    'totales' => ['meses' => array_fill(1, 12, 0), 's1' => 0, 's2' => 0, 'anual' => 0],
    'detalle_lideres' => []
];

$tablaGanarMinisterio = $tabla_ganar_ministerio ?? [
    'anio' => (int)date('Y'),
    'meses' => [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
    ],
    'rows' => [],
    'totales' => ['meses' => array_fill(1, 12, 0), 's1' => 0, 's2' => 0, 'anual' => 0],
    'detalle_lideres' => []
];

$reporteGanadosFinSemanaAnterior = $reporte_ganados_fin_semana_anterior ?? [
    'inicio' => '',
    'fin' => '',
    'rows' => [],
    'totales' => ['ganados' => 0, 'asignados' => 0, 'por_verificar' => 0, 'total_domingo' => 0],
    'texto' => ''
];

$reporteEscaleraMesActual = $reporte_escalera_mes_actual ?? [
    'inicio' => date('Y-m-01'),
    'fin' => date('Y-m-t'),
    'mes_label' => date('F Y'),
    'total_personas_mes' => 0,
    'totales_etapa' => [
        'Ganar' => 0,
        'Consolidar' => 0,
        'Discipular' => 0,
        'Enviar' => 0,
        'sin_etapa' => 0,
    ],
    'peldaños' => [
        'Ganar' => [
            'Primer contacto' => 0,
            'Ubicado en celula' => 0,
            'No se dispone' => 0,
        ],
        'Consolidar' => [
            'Universidad de la vida' => 0,
            'Encuentro' => 0,
            'Bautismo' => 0,
        ],
        'Discipular' => [
            'Proyeccion' => 0,
            'Equipo G12' => 0,
            'Capacitacion destino nivel 1' => 0,
        ],
        'Enviar' => [
            'Capacitacion destino nivel 2' => 0,
            'Capacitacion destino nivel 3' => 0,
            'Celula' => 0,
        ],
    ],
];

$tipoReporte = ($tipo_reporte ?? 'personas') === 'celulas' ? 'celulas' : 'personas';
$esReportePersonas = $tipoReporte === 'personas';
$tituloReporte = $esReportePersonas ? 'Reporte de Ganar' : 'Reporte de Célula';
$escalaGanar = (string)($escala_ganar ?? 'semanal');
$ganarLabel = (string)($ganar_label ?? 'Semanal');
$ganarInicio = (string)($ganar_inicio ?? $fecha_inicio ?? '');
$ganarFin = (string)($ganar_fin ?? $fecha_fin ?? '');
$fechaInicioFiltro = (string)($fecha_inicio_filtro ?? '');
$fechaFinFiltro = (string)($fecha_fin_filtro ?? '');
$mesEscaleraSeleccionado = (string)($mes_escalera ?? date('Y-m'));
$mesEscaleraLabel = trim((string)($reporteEscaleraMesActual['mes_label'] ?? ''));
if ($mesEscaleraLabel === '') {
    $mesEscaleraLabel = $mesEscaleraSeleccionado;
}

$filtroMesMeta = (string)($filtro_mes_meta ?? '');
$mesesTabla = $cumplimientoMetas['meses'] ?? [];
if ($filtroMesMeta !== '' && $filtroMesMeta !== 'all') {
    $mesesTabla = array_values(array_filter($mesesTabla, static function($mes) use ($filtroMesMeta) {
        return (string)($mes['key'] ?? '') === $filtroMesMeta;
    }));
}

$tablaEsCompacta = $filtroMesMeta !== 'all';

$parametrosReporteActual = [
    'url' => 'reportes',
    'tipo' => $tipoReporte,
    'escala_ganar' => $escalaGanar,
    'fecha_referencia' => (string)$fecha_referencia,
    'fecha_inicio' => $fechaInicioFiltro,
    'fecha_fin' => $fechaFinFiltro,
    'ministerio' => (string)$filtro_ministerio,
    'lider' => (string)$filtro_lider,
    'celula' => (string)$filtro_celula,
    'mes_meta' => (string)$filtroMesMeta,
    'mes_escalera' => (string)$mesEscaleraSeleccionado
];

$buildReporteUrl = static function(array $overrides = [], array $exclude = []) use ($parametrosReporteActual) {
    $params = array_merge($parametrosReporteActual, $overrides);
    foreach ($exclude as $key) {
        unset($params[$key]);
    }

    return PUBLIC_URL . 'index.php?' . http_build_query($params);
};

$retornoReporteUrl = $buildReporteUrl();

?>

<div class="page-header">
    <div>
        <h2>Reportes Semanales</h2>
        <small style="color:#637087;"><?= htmlspecialchars($tituloReporte) ?></small>
    </div>

</div>

<div class="report-switcher" style="margin-bottom: 18px;">
    <a href="<?= htmlspecialchars($buildReporteUrl(['tipo' => 'personas'])) ?>" class="report-switcher-tab <?= $esReportePersonas ? 'is-active' : '' ?>">
        Ganar
    </a>
    <a href="<?= htmlspecialchars($buildReporteUrl(['tipo' => 'celulas'])) ?>" class="report-switcher-tab <?= !$esReportePersonas ? 'is-active' : '' ?>">
        Célula
    </a>
</div>

<div class="card report-card" style="margin-bottom: 18px;">
    <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="filters-inline" style="padding: 14px;">
        <input type="hidden" name="url" value="reportes">
        <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipoReporte) ?>">

        <?php if (!$esReportePersonas): ?>
        <div class="form-group" style="margin: 0;">
            <label for="fecha_referencia">Semana (domingo a domingo)</label>
            <input type="date" id="fecha_referencia" name="fecha_referencia" class="form-control" value="<?= htmlspecialchars((string)$fecha_referencia) ?>" required>
            <small style="color:#637087;">Rango aplicado: <?= date('d/m/Y', strtotime($fecha_inicio)) ?> - <?= date('d/m/Y', strtotime($fecha_fin)) ?></small>
        </div>
        <?php else: ?>
        <input type="hidden" name="fecha_referencia" value="<?= htmlspecialchars((string)$fecha_referencia) ?>">
        <?php endif; ?>

        <div class="form-group" style="margin: 0;">
            <label for="fecha_inicio">Fecha inicio (personalizada)</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fechaInicioFiltro) ?>">
        </div>

        <div class="form-group" style="margin: 0;">
            <label for="fecha_fin">Fecha fin (personalizada)</label>
            <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($fechaFinFiltro) ?>">
        </div>

        <div class="form-group" style="margin: 0;">
            <label for="filtro_ministerio">Ministerio (opcional)</label>
            <select id="filtro_ministerio" name="ministerio" class="form-control">
                <option value="">Todos los ministerios</option>
                <?php foreach (($ministerios_disponibles ?? []) as $ministerio): ?>
                    <option value="<?= (int)$ministerio['Id_Ministerio'] ?>" <?= ((string)($filtro_ministerio ?? '') === (string)$ministerio['Id_Ministerio']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label for="filtro_lider">Líder de célula (opcional)</label>
            <select id="filtro_lider" name="lider" class="form-control">
                <option value="">Todos los líderes</option>
                <?php foreach (($lideres_disponibles ?? []) as $lider): ?>
                    <option value="<?= (int)$lider['Id_Persona'] ?>" <?= ((string)($filtro_lider ?? '') === (string)$lider['Id_Persona']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lider['Nombre_Completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (!$esReportePersonas): ?>
            <div class="form-group" style="margin: 0;">
                <label for="filtro_celula">Célula (opcional)</label>
                <select id="filtro_celula" name="celula" class="form-control">
                    <option value="">Todas las células</option>
                    <?php foreach (($celulas_disponibles ?? []) as $celula): ?>
                        <option value="<?= (int)$celula['Id_Celula'] ?>" <?= ((string)($filtro_celula ?? '') === (string)$celula['Id_Celula']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($celula['Nombre_Celula']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if ($esReportePersonas): ?>
            <div class="form-group" style="margin: 0;">
                <label for="escala_ganar">Vista ganar</label>
                <select id="escala_ganar" name="escala_ganar" class="form-control">
                    <option value="semanal" <?= $escalaGanar === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                    <option value="mensual" <?= $escalaGanar === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                    <option value="semestral" <?= $escalaGanar === 'semestral' ? 'selected' : '' ?>>Semestral</option>
                    <option value="anual" <?= $escalaGanar === 'anual' ? 'selected' : '' ?>>Anual</option>
                </select>
            </div>

            <div class="form-group" style="margin: 0;">
                <label for="filtro_mes_meta">Mes de metas</label>
                <select id="filtro_mes_meta" name="mes_meta" class="form-control">
                    <option value="all" <?= ($filtroMesMeta === 'all') ? 'selected' : '' ?>>Todo el semestre</option>
                    <?php foreach (($cumplimientoMetas['meses'] ?? []) as $mesMeta): ?>
                        <option value="<?= htmlspecialchars((string)($mesMeta['key'] ?? '')) ?>" <?= ($filtroMesMeta === (string)($mesMeta['key'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($mesMeta['label'] ?? 'MES')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin: 0;">
                <label for="mes_escalera">Mes Escalera del Éxito</label>
                <input type="month" id="mes_escalera" name="mes_escalera" class="form-control" value="<?= htmlspecialchars($mesEscaleraSeleccionado) ?>">
                <small style="color:#637087;">Por defecto trae el mes actual.</small>
            </div>
        <?php endif; ?>

        <div class="filters-actions">
            <button type="submit" class="btn btn-primary">Aplicar</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=reportes&tipo=<?= urlencode($tipoReporte) ?>" class="btn btn-secondary">Resetear</a>
        </div>
    </form>
</div>

<?php if ($esReportePersonas): ?>
<div class="card report-card" style="margin-bottom: 12px; padding: 12px 14px;">
    <strong><?= htmlspecialchars($ganarLabel) ?></strong>
    <span style="color:#64748b;">(<?= htmlspecialchars($ganarInicio) ?> a <?= htmlspecialchars($ganarFin) ?>)</span>
</div>

<div class="report-kpi-grid report-kpi-grid--ganar" style="margin-bottom: 18px;">
    <button type="button" class="report-kpi-card report-kpi-button kpi-celula js-kpi-detalle" data-origen="celula">
        <div class="report-kpi-label">Ganados en célula</div>
        <div class="report-kpi-value"><?= (int)$resumenOrigen['Ganados_Celula'] ?></div>
    </button>
    <button type="button" class="report-kpi-card report-kpi-button kpi-domingo js-kpi-detalle" data-origen="domingo">
        <div class="report-kpi-label">Ganados en domingo</div>
        <div class="report-kpi-value"><?= (int)$resumenOrigen['Ganados_Domingo'] ?></div>
    </button>
    <button type="button" class="report-kpi-card report-kpi-button kpi-asistencia js-kpi-detalle" data-origen="asignados">
        <div class="report-kpi-label">Asignados</div>
        <div class="report-kpi-value"><?= (int)($resumenOrigen['Asignados'] ?? 0) ?></div>
    </button>
</div>

<div id="reporteDetalleModal" class="celula-modal" aria-hidden="true">
    <div class="celula-modal__overlay" data-reporte-close="1"></div>
    <div class="celula-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="reporteDetalleModalTitle">
        <div class="celula-modal__header">
            <h3 id="reporteDetalleModalTitle" class="celula-modal__title">Detalle del reporte</h3>
            <button type="button" class="celula-modal__close" data-reporte-close="1" aria-label="Cerrar">×</button>
        </div>
        <div class="celula-modal__body">
            <div class="table-container">
                <table class="data-table data-table--compacta-celula">
                    <thead>
                        <tr>
                            <th>Persona</th>
                            <th>Líder</th>
                            <th>Célula</th>
                            <th>Ministerio</th>
                            <th>Proceso</th>
                            <th>Fecha registro</th>
                        </tr>
                    </thead>
                    <tbody id="reporteDetalleModalBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card report-card" style="margin-bottom: 22px;">
    <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-bottom:10px;">
        <div>
            <h3 style="margin-bottom:4px;">Reporte de Ganados del fin de semana anterior</h3>
            <small style="color:#60708a;">Rango: <?= htmlspecialchars((string)($reporteGanadosFinSemanaAnterior['inicio'] ?? '')) ?> a <?= htmlspecialchars((string)($reporteGanadosFinSemanaAnterior['fin'] ?? '')) ?></small>
        </div>
    </div>

    <?php if (!empty($reporteGanadosFinSemanaAnterior['rows'])): ?>
        <div class="table-container" style="margin-bottom: 12px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ministerio</th>
                        <th style="width:130px;">Ganados</th>
                        <th style="width:130px;">Asignados</th>
                        <th style="width:140px;">Por verificar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($reporteGanadosFinSemanaAnterior['rows'] ?? []) as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($row['ministerio'] ?? 'Sin ministerio')) ?></td>
                            <td><strong><?= (int)($row['ganados'] ?? 0) ?></strong></td>
                            <td><?= (int)($row['asignados'] ?? 0) ?></td>
                            <td><?= (int)($row['por_verificar'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="reporte-metas-total-row">
                        <td><strong>TOTAL</strong></td>
                        <td><strong><?= (int)($reporteGanadosFinSemanaAnterior['totales']['ganados'] ?? 0) ?></strong></td>
                        <td><strong><?= (int)($reporteGanadosFinSemanaAnterior['totales']['asignados'] ?? 0) ?></strong></td>
                        <td><strong><?= (int)($reporteGanadosFinSemanaAnterior['totales']['por_verificar'] ?? 0) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="report-empty-state">
            Sin registros de domingo para el fin de semana anterior.
        </div>
    <?php endif; ?>
</div>

<div class="card report-card report-metas-card" style="margin-bottom: 22px;">
    <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-bottom:10px;">
        <div>
            <h3 style="margin-bottom:4px;">Ganados por ministerio (<?= (int)($tablaGanarMinisterio['anio'] ?? date('Y')) ?>)</h3>
            <small style="color:#60708a;">Rango aplicado: <?= htmlspecialchars((string)($tablaGanarMinisterio['inicio'] ?? $ganarInicio)) ?> a <?= htmlspecialchars((string)($tablaGanarMinisterio['fin'] ?? $ganarFin)) ?></small>
        </div>
    </div>

    <?php if (!empty($tablaGanarMinisterio['rows'])): ?>
        <div class="table-container reporte-metas-wrap">
            <table class="data-table reporte-metas-table reporte-celulas-abiertas-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:48px;">N°</th>
                        <th rowspan="2" style="width:240px;">MINISTERIO</th>
                        <th colspan="12">MESES</th>
                        <th rowspan="2" style="width:70px;">S1</th>
                        <th rowspan="2" style="width:70px;">S2</th>
                        <th rowspan="2" style="width:76px;">AÑO</th>
                    </tr>
                    <tr>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <th><?= htmlspecialchars((string)($tablaGanarMinisterio['meses'][$m] ?? '')) ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $nroGan = 1; ?>
                    <?php foreach (($tablaGanarMinisterio['rows'] ?? []) as $row): ?>
                        <tr>
                            <td><?= $nroGan++ ?></td>
                            <td>
                                <button type="button" class="report-link-button js-ministerio-ganar" data-ministerio="<?= htmlspecialchars((string)($row['ministerio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($row['ministerio'] ?? 'Sin ministerio')) ?>
                                </button>
                            </td>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <td><?= (int)($row['meses'][$m] ?? 0) ?></td>
                            <?php endfor; ?>
                            <td><strong><?= (int)($row['s1'] ?? 0) ?></strong></td>
                            <td><strong><?= (int)($row['s2'] ?? 0) ?></strong></td>
                            <td><strong><?= (int)($row['anual'] ?? 0) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>

                    <tr class="reporte-metas-total-row">
                        <td colspan="2"><strong>TOTAL</strong></td>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <td><strong><?= (int)($tablaGanarMinisterio['totales']['meses'][$m] ?? 0) ?></strong></td>
                        <?php endfor; ?>
                        <td><strong><?= (int)($tablaGanarMinisterio['totales']['s1'] ?? 0) ?></strong></td>
                        <td><strong><?= (int)($tablaGanarMinisterio['totales']['s2'] ?? 0) ?></strong></td>
                        <td><strong><?= (int)($tablaGanarMinisterio['totales']['anual'] ?? 0) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="report-empty-state">
            Sin ganados registrados para este año.
        </div>
    <?php endif; ?>

    <div id="detalleMinisterioGanar" class="report-subpanel" style="display:none; margin-top:12px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:8px;">
            <h4 id="detalleMinisterioGanarTitulo" style="margin:0;">Líderes con ganados</h4>
            <button type="button" id="detalleMinisterioGanarCerrar" class="btn btn-secondary btn-sm">Cerrar</button>
        </div>
        <div class="table-container">
            <table class="data-table data-table--compacta-celula">
                <thead>
                    <tr>
                        <th>Líder</th>
                        <th style="width:120px;">Ganados</th>
                    </tr>
                </thead>
                <tbody id="detalleMinisterioGanarBody"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="card report-card report-metas-card" style="margin-bottom: 22px;">
    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap; margin-bottom:10px;">
        <div>
            <h3 style="margin-bottom:4px;"><?= htmlspecialchars((string)($cumplimientoMetas['titulo'] ?? 'GANAR')) ?></h3>
            <small style="color:#60708a;">Periodo semestral: <?= htmlspecialchars((string)($cumplimientoMetas['inicio'] ?? '')) ?> a <?= htmlspecialchars((string)($cumplimientoMetas['fin'] ?? '')) ?></small>
        </div>
        <a href="<?= PUBLIC_URL ?>index.php?url=ministerios&return_url=<?= urlencode($retornoReporteUrl) ?>" class="btn btn-secondary btn-sm">Editar metas por ministerio</a>
    </div>

    <div class="table-container reporte-metas-wrap">
        <table class="data-table reporte-metas-table <?= $tablaEsCompacta ? 'reporte-metas-table--compacta' : '' ?>">
            <thead>
                <tr>
                    <th rowspan="2" style="width:48px;">N°</th>
                    <th rowspan="2" style="width:230px;">MINISTERIO</th>
                    <?php foreach ($mesesTabla as $mes): ?>
                        <th colspan="2"><?= htmlspecialchars((string)($mes['label'] ?? 'MES')) ?></th>
                    <?php endforeach; ?>
                    <th rowspan="2" style="width:78px;">META</th>
                    <th rowspan="2" style="width:94px;">PENDIENTE</th>
                    <th rowspan="2" style="width:84px;">GANADOS</th>
                </tr>
                <tr>
                    <?php foreach ($mesesTabla as $mes): ?>
                        <th>Celula</th>
                        <th>Iglesia</th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($cumplimientoMetas['rows'])): ?>
                    <?php $nroMeta = 1; ?>
                    <?php foreach ($cumplimientoMetas['rows'] as $row): ?>
                        <?php
                        $metaRow = (int)($row['meta'] ?? 0);
                        $ganadosRow = (int)($row['ganados'] ?? 0);
                        $pendienteRow = (int)($row['pendiente'] ?? 0);
                        $cumplimiento = $metaRow > 0 ? (($ganadosRow / $metaRow) * 100) : 0;
                        $claseGanados = $cumplimiento >= 100 ? 'ganado-ok' : ($cumplimiento >= 50 ? 'ganado-medio' : 'ganado-bajo');
                        $clasePendiente = $pendienteRow <= 0 ? 'pendiente-ok' : ($cumplimiento >= 50 ? 'pendiente-medio' : 'pendiente-alto');
                        ?>
                        <tr>
                            <td><?= $nroMeta++ ?></td>
                            <td><?= htmlspecialchars((string)($row['ministerio'] ?? 'Sin ministerio')) ?></td>
                            <?php foreach ($mesesTabla as $mes): ?>
                                <?php $mesKey = (string)($mes['key'] ?? ''); ?>
                                <td><?= (int)($row['meses'][$mesKey]['celula'] ?? 0) ?></td>
                                <td><?= (int)($row['meses'][$mesKey]['iglesia'] ?? 0) ?></td>
                            <?php endforeach; ?>
                            <td><?= $metaRow ?></td>
                            <td><span class="meta-pill-cell <?= $clasePendiente ?>"><?= $pendienteRow ?></span></td>
                            <td><span class="meta-pill-cell <?= $claseGanados ?>"><strong><?= $ganadosRow ?></strong></span></td>
                        </tr>
                    <?php endforeach; ?>

                    <tr class="reporte-metas-total-row">
                        <td colspan="2"><strong>TOTAL</strong></td>
                        <?php foreach ($mesesTabla as $mes): ?>
                            <?php $mesKey = (string)($mes['key'] ?? ''); ?>
                            <td><strong><?= (int)($cumplimientoMetas['totales']['meses'][$mesKey]['celula'] ?? 0) ?></strong></td>
                            <td><strong><?= (int)($cumplimientoMetas['totales']['meses'][$mesKey]['iglesia'] ?? 0) ?></strong></td>
                        <?php endforeach; ?>
                        <td><strong><?= (int)($cumplimientoMetas['totales']['meta'] ?? 0) ?></strong></td>
                        <td><strong><?= (int)($cumplimientoMetas['totales']['pendiente'] ?? 0) ?></strong></td>
                        <td><strong><?= (int)($cumplimientoMetas['totales']['ganados'] ?? 0) ?></strong></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="99" class="text-center">Sin datos para construir el cumplimiento de metas en este periodo</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<div class="card report-card report-escalera-card" style="margin-bottom: 22px;">
    <div class="report-escalera-head">
        <div>
            <h3 style="margin-bottom:4px;">Escalera del Éxito</h3>
            <small style="color:#60708a; display:block; margin-bottom:4px;">
                Vista mensual: <strong><?= htmlspecialchars($mesEscaleraLabel) ?></strong>
            </small>
            <small style="color:#60708a; display:block; margin-bottom:4px;">
                Rango aplicado: <?= htmlspecialchars((string)($reporteEscaleraMesActual['inicio'] ?? '')) ?> a <?= htmlspecialchars((string)($reporteEscaleraMesActual['fin'] ?? '')) ?>
            </small>
            <small style="color:#60708a;">
                Fuente: personas activas del rango filtrado, usando los campos <strong>`Proceso`</strong> y <strong>`Escalera_Checklist`</strong>.
            </small>
        </div>
        <div class="escalera-total-pill">
            Total personas del mes: <strong><?= (int)($reporteEscaleraMesActual['total_personas_mes'] ?? 0) ?></strong>
        </div>
    </div>

    <div class="report-kpi-grid report-kpi-grid--escalera" style="margin-bottom: 14px;">
        <button type="button" class="report-kpi-card report-kpi-button kpi-escalera js-escalera-detalle-etapa" data-etapa="Ganar">
            <div class="report-kpi-label">Ganar</div>
            <div class="report-kpi-value"><?= (int)($reporteEscaleraMesActual['totales_etapa']['Ganar'] ?? 0) ?></div>
        </button>
        <button type="button" class="report-kpi-card report-kpi-button kpi-celula js-escalera-detalle-etapa" data-etapa="Consolidar">
            <div class="report-kpi-label">Consolidar</div>
            <div class="report-kpi-value"><?= (int)($reporteEscaleraMesActual['totales_etapa']['Consolidar'] ?? 0) ?></div>
        </button>
        <button type="button" class="report-kpi-card report-kpi-button kpi-asistencia js-escalera-detalle-etapa" data-etapa="Discipular">
            <div class="report-kpi-label">Discipular</div>
            <div class="report-kpi-value"><?= (int)($reporteEscaleraMesActual['totales_etapa']['Discipular'] ?? 0) ?></div>
        </button>
        <button type="button" class="report-kpi-card report-kpi-button kpi-domingo js-escalera-detalle-etapa" data-etapa="Enviar">
            <div class="report-kpi-label">Enviar</div>
            <div class="report-kpi-value"><?= (int)($reporteEscaleraMesActual['totales_etapa']['Enviar'] ?? 0) ?></div>
        </button>
        <button type="button" class="report-kpi-card report-kpi-button js-escalera-detalle-etapa" data-etapa="sin_etapa" style="background:#f8fafc; border:1px solid #d8e2ee;">
            <div class="report-kpi-label">Sin etapa</div>
            <div class="report-kpi-value"><?= (int)($reporteEscaleraMesActual['totales_etapa']['sin_etapa'] ?? 0) ?></div>
        </button>
    </div>
    <div class="report-escalera-help">Haz clic en una <strong>etapa</strong> o en un <strong>peldaño</strong> para ver las personas que lo componen.</div>

    <?php
    $etapasEscaleraUi = [
        'Ganar' => 'ganar',
        'Consolidar' => 'consolidar',
        'Discipular' => 'discipular',
        'Enviar' => 'enviar'
    ];
    $totalEscaleraUi = max(1, (int)($reporteEscaleraMesActual['total_personas_mes'] ?? 0));
    ?>
    <div class="escalera-stage-visual-grid">
        <?php foreach ($etapasEscaleraUi as $etapa => $etapaClase): ?>
            <?php
            $cantidadEtapa = (int)($reporteEscaleraMesActual['totales_etapa'][$etapa] ?? 0);
            $porcentajeEtapa = $totalEscaleraUi > 0 ? round(($cantidadEtapa / $totalEscaleraUi) * 100) : 0;
            $peldañosEtapa = (array)($reporteEscaleraMesActual['peldaños'][$etapa] ?? []);
            ?>
            <div class="escalera-stage-panel escalera-stage-panel--<?= $etapaClase ?>">
                <button type="button" class="escalera-stage-panel-head escalera-stage-panel-head--button js-escalera-detalle-etapa" data-etapa="<?= htmlspecialchars($etapa) ?>">
                    <span class="escalera-etapa-badge etapa-<?= $etapaClase ?>"><?= htmlspecialchars($etapa) ?></span>
                    <strong><?= $cantidadEtapa ?></strong>
                </button>
                <div class="escalera-stage-progress">
                    <span style="width: <?= $cantidadEtapa > 0 ? max(4, $porcentajeEtapa) : 0 ?>%;"></span>
                </div>
                <small class="escalera-stage-progress-label"><?= $porcentajeEtapa ?>% del total del mes</small>

                <div class="escalera-peldano-list">
                    <?php foreach ($peldañosEtapa as $peldaño => $cantidadPeldaño): ?>
                        <button type="button" class="escalera-peldano-item js-escalera-detalle-peldano" data-etapa="<?= htmlspecialchars($etapa) ?>" data-peldano="<?= htmlspecialchars((string)$peldaño) ?>">
                            <span><?= htmlspecialchars((string)$peldaño) ?></span>
                            <strong><?= (int)$cantidadPeldaño ?></strong>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card report-card" style="margin-bottom: 22px;">
    <h3>Almas ganadas por edades</h3>
    <div id="chartEdades"></div>
</div>

<div class="card report-card" style="margin-bottom: 22px;">
    <h3>Almas ganadas por ministerio</h3>
    <div id="chartAlmasMinisterio"></div>
    <details style="margin-top: 14px;">
        <summary>Ver detalle por ministerio</summary>
        <div class="table-container" style="margin-top: 10px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ministerio</th>
                        <th>Hombres</th>
                        <th>Mujeres</th>
                        <th>Jóvenes H.</th>
                        <th>Jóvenes M.</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($almas_ganadas)): ?>
                        <?php foreach ($almas_ganadas as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['Nombre_Ministerio'] ?? 'Sin ministerio') ?></td>
                                <td><?= (int)($item['Hombres'] ?? 0) ?></td>
                                <td><?= (int)($item['Mujeres'] ?? 0) ?></td>
                                <td><?= (int)($item['Jovenes_Hombres'] ?? 0) ?></td>
                                <td><?= (int)($item['Jovenes_Mujeres'] ?? 0) ?></td>
                                <td><strong><?= (int)($item['Total'] ?? 0) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Sin datos para el rango seleccionado</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </details>
</div>
<?php else: ?>
<div class="report-kpi-grid report-kpi-grid--celulas" style="margin-bottom: 18px;">
    <div class="report-kpi-card kpi-escalera">
        <div class="report-kpi-label">Total de células</div>
        <div class="report-kpi-value"><?= (int)($indicadoresCelulas['totales']['total_celulas'] ?? 0) ?></div>
    </div>
    <div class="report-kpi-card kpi-domingo">
        <div class="report-kpi-label">Nuevas en semestre</div>
        <div class="report-kpi-value"><?= (int)($indicadoresCelulas['totales']['nuevas_semestre'] ?? 0) ?></div>
    </div>
    <div class="report-kpi-card kpi-celula">
        <div class="report-kpi-label">Cerradas en semestre</div>
        <div class="report-kpi-value"><?= (int)($indicadoresCelulas['totales']['cerradas_semestre'] ?? 0) ?></div>
    </div>
    <div class="report-kpi-card kpi-asistencia">
        <div class="report-kpi-label">Reportadas semana</div>
        <div class="report-kpi-value"><?= (int)($indicadoresCelulas['totales']['reportadas_semana'] ?? 0) ?></div>
    </div>
    <div class="report-kpi-card kpi-domingo">
        <div class="report-kpi-label">No reportadas semana</div>
        <div class="report-kpi-value"><?= (int)($indicadoresCelulas['totales']['no_reportadas_semana'] ?? 0) ?></div>
    </div>
    <div class="report-kpi-card kpi-asistencia">
        <div class="report-kpi-label">Promedio asistencia</div>
        <div class="report-kpi-value"><?= $promedioAsistencia ?>%</div>
    </div>
</div>

<div class="card report-card" style="margin-bottom: 22px;">
    <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-bottom:12px;">
        <h3 style="margin:0;">Indicadores de células</h3>
        <small style="color:#64748b;">Semestre: <?= htmlspecialchars((string)($indicadoresCelulas['semestre']['inicio'] ?? '')) ?> a <?= htmlspecialchars((string)($indicadoresCelulas['semestre']['fin'] ?? '')) ?></small>
    </div>
    <div id="chartIndicadoresCelulas"></div>
</div>

<div class="report-kpi-grid report-kpi-grid--celulas" style="margin-bottom: 22px;">
    <div class="card report-card" style="padding: 14px;">
        <h3 style="margin-bottom: 10px;">Células por ministerio</h3>
        <?php if (!empty($indicadoresCelulas['por_ministerio'])): ?>
            <div class="report-list-items">
                <?php foreach (($indicadoresCelulas['por_ministerio'] ?? []) as $ministerioNombre => $totalMinisterio): ?>
                    <div class="report-list-item">
                        <span><?= htmlspecialchars((string)$ministerioNombre) ?></span>
                        <strong><?= (int)$totalMinisterio ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="margin:0; color:#64748b;">Sin datos por ministerio</p>
        <?php endif; ?>
    </div>

    <div class="card report-card" style="padding: 14px;">
        <h3 style="margin-bottom: 10px;">Células por red</h3>
        <?php if (!empty($indicadoresCelulas['por_red'])): ?>
            <div class="report-list-items">
                <?php foreach (($indicadoresCelulas['por_red'] ?? []) as $redNombre => $totalRed): ?>
                    <div class="report-list-item">
                        <span><?= htmlspecialchars((string)$redNombre) ?></span>
                        <strong><?= (int)$totalRed ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="margin:0; color:#64748b;">Sin datos por red</p>
        <?php endif; ?>
    </div>
</div>

<div class="card report-card report-metas-card" style="margin-bottom: 22px;">
    <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-bottom:10px;">
        <div>
            <h3 style="margin-bottom:4px;">Células abiertas por ministerio (<?= (int)($tablaAperturasCelulas['anio'] ?? date('Y')) ?>)</h3>
            <small style="color:#60708a;">Mes a mes con acumulados por semestre y total anual</small>
        </div>
    </div>

    <?php if (!empty($tablaAperturasCelulas['rows'])): ?>
        <div class="table-container reporte-metas-wrap">
            <table class="data-table reporte-metas-table reporte-celulas-abiertas-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:48px;">N°</th>
                        <th rowspan="2" style="width:240px;">MINISTERIO</th>
                        <th colspan="12">MESES</th>
                        <th rowspan="2" style="width:70px;">S1</th>
                        <th rowspan="2" style="width:70px;">S2</th>
                        <th rowspan="2" style="width:76px;">AÑO</th>
                    </tr>
                    <tr>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <th><?= htmlspecialchars((string)($tablaAperturasCelulas['meses'][$m] ?? '')) ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $nroCel = 1; ?>
                    <?php foreach (($tablaAperturasCelulas['rows'] ?? []) as $row): ?>
                        <tr>
                            <td><?= $nroCel++ ?></td>
                            <td>
                                <button type="button" class="report-link-button js-ministerio-celula" data-ministerio="<?= htmlspecialchars((string)($row['ministerio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($row['ministerio'] ?? 'Sin ministerio')) ?>
                                </button>
                            </td>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <td><?= (int)($row['meses'][$m] ?? 0) ?></td>
                            <?php endfor; ?>
                            <td><strong><?= (int)($row['s1'] ?? 0) ?></strong></td>
                            <td><strong><?= (int)($row['s2'] ?? 0) ?></strong></td>
                            <td><strong><?= (int)($row['anual'] ?? 0) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>

                    <tr class="reporte-metas-total-row">
                        <td colspan="2"><strong>TOTAL</strong></td>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <td><strong><?= (int)($tablaAperturasCelulas['totales']['meses'][$m] ?? 0) ?></strong></td>
                        <?php endfor; ?>
                        <td><strong><?= (int)($tablaAperturasCelulas['totales']['s1'] ?? 0) ?></strong></td>
                        <td><strong><?= (int)($tablaAperturasCelulas['totales']['s2'] ?? 0) ?></strong></td>
                        <td><strong><?= (int)($tablaAperturasCelulas['totales']['anual'] ?? 0) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="report-empty-state">
            Sin aperturas registradas para este año.
            <br>
            <small>Tip: este cuadro usa la fecha de apertura de cada célula.</small>
        </div>
    <?php endif; ?>

    <div id="detalleMinisterioCelulas" class="report-subpanel" style="display:none; margin-top:12px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:8px;">
            <h4 id="detalleMinisterioCelulasTitulo" style="margin:0;">Líderes con aperturas</h4>
            <button type="button" id="detalleMinisterioCelulasCerrar" class="btn btn-secondary btn-sm">Cerrar</button>
        </div>
        <div class="table-container">
            <table class="data-table data-table--compacta-celula">
                <thead>
                    <tr>
                        <th>Líder</th>
                        <th style="width:120px;">Células abiertas</th>
                    </tr>
                </thead>
                <tbody id="detalleMinisterioCelulasBody"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="card report-card" style="margin-bottom: 22px;">
    <h3>Asistencia a células</h3>
    <div id="chartAsistencia"></div>
    <details style="margin-top: 14px;">
        <summary>Ver detalle por célula</summary>
        <div class="table-container" style="margin-top: 10px;">
            <table class="data-table data-table--compacta-celula">
                <thead>
                    <tr>
                        <th>Célula</th>
                        <th>Líder</th>
                        <th>Inscritos</th>
                        <th>Reuniones</th>
                        <th>Esperadas</th>
                        <th>Reales</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($asistencia_celulas)): ?>
                        <?php foreach ($asistencia_celulas as $celula): ?>
                            <?php
                            $esperadas = (int)($celula['Asistencias_Esperadas'] ?? 0);
                            $reales = (int)($celula['Asistencias_Reales'] ?? 0);
                            $porcentaje = $esperadas > 0 ? round(($reales / $esperadas) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($celula['Nombre_Celula'] ?? '') ?></td>
                                <td><?= htmlspecialchars(trim((string)($celula['Nombre_Lider'] ?? '')) ?: 'Sin líder') ?></td>
                                <td><?= (int)($celula['Total_Inscritos'] ?? 0) ?></td>
                                <td><?= (int)($celula['Reuniones_Realizadas'] ?? 0) ?></td>
                                <td><?= $esperadas ?></td>
                                <td><?= $reales ?></td>
                                <td><?= $porcentaje ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Sin datos de asistencia</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </details>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
const procesoGanar = <?= json_encode($procesoGanar) ?>;
const almasPorEdades = <?= json_encode($almasPorEdades) ?>;
const almasGanadas = <?= json_encode($almas_ganadas ?? []) ?>;
const detalleOrigenGanados = <?= json_encode($detalle_origen_ganados ?? []) ?>;
const detalleEscaleraEtapa = <?= json_encode($reporteEscaleraMesActual['detalles_etapa'] ?? []) ?>;
const detalleEscaleraPeldanos = <?= json_encode($reporteEscaleraMesActual['detalles_peldanos'] ?? []) ?>;
const asistencia = <?= json_encode($asistencia_celulas ?? []) ?>;
const indicadoresCelulas = <?= json_encode($indicadoresCelulas ?? []) ?>;
const detalleLideresAperturas = <?= json_encode($tablaAperturasCelulas['detalle_lideres'] ?? []) ?>;
const detalleLideresGanar = <?= json_encode($tablaGanarMinisterio['detalle_lideres'] ?? []) ?>;
const tipoReporte = <?= json_encode($tipoReporte) ?>;
const nombresCelulas = asistencia.map(x => (x.Nombre_Celula || 'Sin célula'));
const etiquetasCelulas = nombresCelulas.map(nombre => {
    const limpio = String(nombre || '').trim();
    return limpio.length > 20 ? `${limpio.slice(0, 20)}...` : limpio;
});

if (tipoReporte === 'personas') {
    new ApexCharts(document.querySelector('#chartEdades'), {
        chart: { type: 'pie', height: 320 },
        labels: ['Kids (3-8)', 'Teens (9-12)', 'Rocas (13-17)', 'Jóvenes (18-30)', 'Adultos (31-59)', 'Adultos mayores (60+)', 'Sin dato'],
        colors: ['#FFB703', '#8ECAE6', '#3A86FF', '#06D6A0', '#8338EC', '#EF476F', '#ADB5BD'],
        series: [
            parseInt(almasPorEdades.Kids || 0, 10),
            parseInt(almasPorEdades.Teens || 0, 10),
            parseInt(almasPorEdades.Rocas || 0, 10),
            parseInt(almasPorEdades.Jovenes || 0, 10),
            parseInt(almasPorEdades.Adultos || 0, 10),
            parseInt(almasPorEdades.Adultos_Mayores || 0, 10),
            parseInt(almasPorEdades.Sin_Dato || 0, 10)
        ],
        legend: {
            position: 'bottom'
        }
    }).render();

    new ApexCharts(document.querySelector('#chartAlmasMinisterio'), {
        chart: { type: 'bar', height: 330 },
        series: [{
            name: 'Total',
            data: almasGanadas.map(x => parseInt(x.Total || 0, 10))
        }],
        xaxis: { categories: almasGanadas.map(x => x.Nombre_Ministerio || 'Sin ministerio') },
        colors: ['#02a66f']
    }).render();

    const botonesKpiDetalle = document.querySelectorAll('.js-kpi-detalle');
    const reporteDetalleModal = document.querySelector('#reporteDetalleModal');
    const reporteDetalleModalBody = document.querySelector('#reporteDetalleModalBody');
    const reporteDetalleModalTitle = document.querySelector('#reporteDetalleModalTitle');
    const reporteDetalleModalClose = document.querySelectorAll('[data-reporte-close="1"]');

    const escaparHtml = (valor) => String(valor || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const etiquetasOrigen = {
        celula: 'Ganados en célula',
        domingo: 'Ganados en domingo',
        asignados: 'Asignados'
    };

    const abrirDetalleKpi = (origen) => {
        if (!reporteDetalleModal || !reporteDetalleModalBody || !reporteDetalleModalTitle) {
            return;
        }

        const filas = Array.isArray(detalleOrigenGanados[origen]) ? detalleOrigenGanados[origen] : [];
        reporteDetalleModalTitle.textContent = etiquetasOrigen[origen] || 'Detalle del reporte';

        if (!filas.length) {
            reporteDetalleModalBody.innerHTML = '<tr><td colspan="6" class="text-center">Sin registros para este rango</td></tr>';
        } else {
            reporteDetalleModalBody.innerHTML = filas.map((item) => {
                const persona = escaparHtml(`${item.Nombre || ''} ${item.Apellido || ''}`.trim() || 'Sin nombre');
                const lider = escaparHtml(item.Nombre_Lider || 'Sin líder');
                const celula = escaparHtml(item.Nombre_Celula || 'Sin célula');
                const ministerio = escaparHtml(item.Nombre_Ministerio || 'Sin ministerio');
                const proceso = escaparHtml(item.Proceso || '');
                const fecha = escaparHtml(item.Fecha_Registro || '');

                return `<tr><td>${persona}</td><td>${lider}</td><td>${celula}</td><td>${ministerio}</td><td>${proceso}</td><td>${fecha}</td></tr>`;
            }).join('');
        }

        reporteDetalleModal.classList.add('is-open');
        reporteDetalleModal.setAttribute('aria-hidden', 'false');
    };

    const cerrarDetalleKpi = () => {
        if (!reporteDetalleModal) {
            return;
        }
        reporteDetalleModal.classList.remove('is-open');
        reporteDetalleModal.setAttribute('aria-hidden', 'true');
    };

    botonesKpiDetalle.forEach((boton) => {
        boton.addEventListener('click', () => {
            abrirDetalleKpi(String(boton.dataset.origen || ''));
        });
    });

    const etiquetasEscalera = {
        Ganar: 'Ganar',
        Consolidar: 'Consolidar',
        Discipular: 'Discipular',
        Enviar: 'Enviar',
        sin_etapa: 'Sin etapa'
    };

    const abrirDetalleEscalera = (titulo, filas) => {
        if (!reporteDetalleModal || !reporteDetalleModalBody || !reporteDetalleModalTitle) {
            return;
        }

        reporteDetalleModalTitle.textContent = titulo;

        if (!Array.isArray(filas) || !filas.length) {
            reporteDetalleModalBody.innerHTML = '<tr><td colspan="6" class="text-center">Sin personas para este filtro</td></tr>';
        } else {
            reporteDetalleModalBody.innerHTML = filas.map((item) => {
                const persona = escaparHtml(`${item.Nombre || ''} ${item.Apellido || ''}`.trim() || 'Sin nombre');
                const lider = escaparHtml(item.Nombre_Lider || 'Sin líder');
                const celula = escaparHtml(item.Nombre_Celula || 'Sin célula');
                const ministerio = escaparHtml(item.Nombre_Ministerio || 'Sin ministerio');
                const proceso = escaparHtml(item.Proceso || '');
                const fecha = escaparHtml(item.Fecha_Registro || '');
                return `<tr><td>${persona}</td><td>${lider}</td><td>${celula}</td><td>${ministerio}</td><td>${proceso}</td><td>${fecha}</td></tr>`;
            }).join('');
        }

        reporteDetalleModal.classList.add('is-open');
        reporteDetalleModal.setAttribute('aria-hidden', 'false');
    };

    document.querySelectorAll('.js-escalera-detalle-etapa').forEach((boton) => {
        boton.addEventListener('click', () => {
            const etapa = String(boton.dataset.etapa || '');
            const filas = Array.isArray(detalleEscaleraEtapa[etapa]) ? detalleEscaleraEtapa[etapa] : [];
            abrirDetalleEscalera(`Escalera del Éxito - ${etiquetasEscalera[etapa] || etapa}`, filas);
        });
    });

    document.querySelectorAll('.js-escalera-detalle-peldano').forEach((boton) => {
        boton.addEventListener('click', () => {
            const etapa = String(boton.dataset.etapa || '');
            const peldano = String(boton.dataset.peldano || '');
            const filas = Array.isArray((detalleEscaleraPeldanos[etapa] || {})[peldano]) ? (detalleEscaleraPeldanos[etapa] || {})[peldano] : [];
            abrirDetalleEscalera(`Escalera - ${etapa} / ${peldano}`, filas);
        });
    });

    reporteDetalleModalClose.forEach((boton) => {
        boton.addEventListener('click', cerrarDetalleKpi);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && reporteDetalleModal && reporteDetalleModal.classList.contains('is-open')) {
            cerrarDetalleKpi();
        }
    });

    const botonesMinisterioGanar = document.querySelectorAll('.js-ministerio-ganar');
    const panelDetalleGanar = document.querySelector('#detalleMinisterioGanar');
    const bodyDetalleGanar = document.querySelector('#detalleMinisterioGanarBody');
    const tituloDetalleGanar = document.querySelector('#detalleMinisterioGanarTitulo');
    const cerrarDetalleGanar = document.querySelector('#detalleMinisterioGanarCerrar');

    const mostrarDetalleMinisterioGanar = (ministerio) => {
        if (!panelDetalleGanar || !bodyDetalleGanar || !tituloDetalleGanar) {
            return;
        }

        const detalle = detalleLideresGanar[ministerio] || [];
        tituloDetalleGanar.textContent = `Líderes con ganados - ${ministerio}`;

        if (!detalle.length) {
            bodyDetalleGanar.innerHTML = '<tr><td colspan="2" class="text-center">Sin ganados para este ministerio</td></tr>';
        } else {
            bodyDetalleGanar.innerHTML = detalle.map((item) => {
                const lider = String(item.lider || 'Sin líder')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
                const cantidad = parseInt(item.cantidad || 0, 10);
                return `<tr><td>${lider}</td><td><strong>${cantidad}</strong></td></tr>`;
            }).join('');
        }

        panelDetalleGanar.style.display = 'block';
        panelDetalleGanar.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    botonesMinisterioGanar.forEach((boton) => {
        boton.addEventListener('click', () => {
            mostrarDetalleMinisterioGanar(String(boton.dataset.ministerio || 'Sin ministerio'));
        });
    });

    if (cerrarDetalleGanar && panelDetalleGanar) {
        cerrarDetalleGanar.addEventListener('click', () => {
            panelDetalleGanar.style.display = 'none';
        });
    }
} else {
    const tot = indicadoresCelulas.totales || {};

    new ApexCharts(document.querySelector('#chartIndicadoresCelulas'), {
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        series: [{
            name: 'Células',
            data: [
                parseInt(tot.total_celulas || 0, 10),
                parseInt(tot.reportadas_semana || 0, 10),
                parseInt(tot.no_reportadas_semana || 0, 10),
                parseInt(tot.nuevas_semestre || 0, 10),
                parseInt(tot.cerradas_semestre || 0, 10)
            ]
        }],
        xaxis: { categories: ['Total', 'Reportadas', 'No reportadas', 'Nuevas S.', 'Cerradas S.'] },
        dataLabels: { enabled: true },
        colors: ['#2a9d8f']
    }).render();

    new ApexCharts(document.querySelector('#chartAsistencia'), {
        chart: { type: 'line', height: 290, toolbar: { show: false } },
        series: [
            {
                name: 'Esperadas',
                type: 'column',
                data: asistencia.map(x => parseInt(x.Asistencias_Esperadas || 0, 10))
            },
            {
                name: 'Reales',
                type: 'column',
                data: asistencia.map(x => parseInt(x.Asistencias_Reales || 0, 10))
            }
        ],
        xaxis: {
            categories: etiquetasCelulas,
            labels: {
                rotate: -18,
                hideOverlappingLabels: true,
                trim: false,
                style: {
                    fontSize: '11px'
                }
            }
        },
        yaxis: {
            min: 0,
            forceNiceScale: true,
            labels: {
                formatter: function(value) {
                    return Math.round(value);
                }
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '44%'
            }
        },
        legend: {
            position: 'bottom',
            horizontalAlign: 'center'
        },
        tooltip: {
            x: {
                formatter: function(_, { dataPointIndex }) {
                    return nombresCelulas[dataPointIndex] || 'Sin célula';
                }
            }
        },
        colors: ['#f59e0b', '#2563eb']
    }).render();

    const botonesMinisterio = document.querySelectorAll('.js-ministerio-celula');
    const panelDetalle = document.querySelector('#detalleMinisterioCelulas');
    const bodyDetalle = document.querySelector('#detalleMinisterioCelulasBody');
    const tituloDetalle = document.querySelector('#detalleMinisterioCelulasTitulo');
    const cerrarDetalle = document.querySelector('#detalleMinisterioCelulasCerrar');

    const mostrarDetalleMinisterio = (ministerio) => {
        if (!panelDetalle || !bodyDetalle || !tituloDetalle) {
            return;
        }

        const detalle = detalleLideresAperturas[ministerio] || [];
        tituloDetalle.textContent = `Líderes que abrieron células - ${ministerio}`;

        if (!detalle.length) {
            bodyDetalle.innerHTML = '<tr><td colspan="2" class="text-center">Sin aperturas para este ministerio</td></tr>';
        } else {
            bodyDetalle.innerHTML = detalle.map((item) => {
                const lider = String(item.lider || 'Sin líder')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
                const cantidad = parseInt(item.cantidad || 0, 10);
                return `<tr><td>${lider}</td><td><strong>${cantidad}</strong></td></tr>`;
            }).join('');
        }

        panelDetalle.style.display = 'block';
        panelDetalle.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    botonesMinisterio.forEach((boton) => {
        boton.addEventListener('click', () => {
            mostrarDetalleMinisterio(String(boton.dataset.ministerio || 'Sin ministerio'));
        });
    });

    if (cerrarDetalle && panelDetalle) {
        cerrarDetalle.addEventListener('click', () => {
            panelDetalle.style.display = 'none';
        });
    }
}
</script>

<style>
.report-switcher {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.report-switcher-tab {
    display: inline-flex;
    align-items: center;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #d5dfeb;
    background: #ffffff;
    color: #355070;
    font-weight: 700;
    text-decoration: none;
}

.report-switcher-tab.is-active {
    background: #17324d;
    border-color: #17324d;
    color: #ffffff;
}

.report-card {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.report-metas-card {
    padding: 14px;
}

.report-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.report-kpi-grid--ganar {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.report-kpi-grid--celulas {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.report-kpi-grid--escalera {
    grid-template-columns: repeat(5, minmax(0, 1fr));
}

.report-escalera-head {
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:flex-start;
    flex-wrap:wrap;
    margin-bottom:12px;
}

.escalera-total-pill {
    background: #f4f7fb;
    border: 1px solid #d7e0ee;
    padding: 8px 12px;
    border-radius: 10px;
    color: #314766;
}

.report-escalera-help {
    margin: -2px 0 14px;
    font-size: 13px;
    color: #53657f;
}

.escalera-stage-visual-grid {
    display:grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.escalera-stage-panel {
    border: 1px solid #dbe3f4;
    border-radius: 14px;
    background: #fff;
    padding: 14px;
}

.escalera-stage-panel--ganar { background: linear-gradient(180deg, #fffdf4 0%, #ffffff 100%); }
.escalera-stage-panel--consolidar { background: linear-gradient(180deg, #f7fff8 0%, #ffffff 100%); }
.escalera-stage-panel--discipular { background: linear-gradient(180deg, #f7fbff 0%, #ffffff 100%); }
.escalera-stage-panel--enviar { background: linear-gradient(180deg, #fff8fb 0%, #ffffff 100%); }

.escalera-stage-panel-head {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:10px;
}

.escalera-stage-panel-head--button {
    width: 100%;
    border: 0;
    background: transparent;
    padding: 0;
    cursor: pointer;
    text-align: left;
}

.escalera-stage-progress {
    width:100%;
    height:10px;
    border-radius:999px;
    background:#edf2f7;
    overflow:hidden;
    margin-bottom:6px;
}

.escalera-stage-progress span {
    display:block;
    height:100%;
    border-radius:999px;
    background: linear-gradient(90deg, #2f65b5 0%, #6ea8fe 100%);
}

.escalera-stage-progress-label {
    display:block;
    color:#60708a;
    margin-bottom:10px;
}

.escalera-peldano-list {
    display:grid;
    gap:8px;
}

.escalera-peldano-item {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    width: 100%;
    padding:8px 10px;
    border:1px solid #e5ebf5;
    border-radius:10px;
    background:#f9fbff;
    cursor: pointer;
    text-align: left;
}

.escalera-peldano-item:hover,
.escalera-stage-panel-head--button:hover {
    filter: brightness(0.98);
}

.escalera-etapa-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 110px;
    padding: 6px 10px;
    border-radius: 999px;
    font-weight: 700;
}

.etapa-ganar {
    background: #fff7dd;
    color: #8a6500;
}

.etapa-consolidar {
    background: #eaf9ee;
    color: #187a35;
}

.etapa-discipular {
    background: #eef5ff;
    color: #1e73be;
}

.etapa-enviar {
    background: #fff0f6;
    color: #c2185b;
}

.report-kpi-card {
    border-radius: 12px;
    padding: 14px;
    color: #10233d;
}

.report-kpi-button {
    appearance: none;
    width: 100%;
    border-width: 1px;
    border-style: solid;
    text-align: left;
    cursor: pointer;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.report-kpi-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(15, 35, 61, 0.08);
}

.report-kpi-button:focus-visible {
    outline: 3px solid rgba(37, 99, 235, 0.22);
    outline-offset: 2px;
}

.kpi-celula { background: #eef7ff; border: 1px solid #c7dfff; }
.kpi-domingo { background: #fff8e8; border: 1px solid #ffe2a8; }
.kpi-escalera { background: #eefbf1; border: 1px solid #bfe8c9; }
.kpi-asistencia { background: #f8f2ff; border: 1px solid #ddcbff; }

@media (max-width: 1100px) {
    .report-kpi-grid--escalera {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .escalera-stage-visual-grid {
        grid-template-columns: 1fr;
    }
}

.report-kpi-label { font-size: .82rem; color: #475569; }
.report-kpi-value { font-size: 1.8rem; font-weight: 800; }

.report-list-items {
    display: grid;
    gap: 8px;
}

.report-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 10px;
    border: 1px solid #e3ebf5;
    border-radius: 8px;
    background: #f8fbff;
}

.report-link-button {
    border: 0;
    background: transparent;
    color: #1f3f74;
    font-weight: 700;
    text-decoration: underline;
    cursor: pointer;
    padding: 0;
    text-align: left;
}

.report-link-button:hover {
    color: #0f2748;
}

.report-subpanel {
    border: 1px solid #dbe7f5;
    border-radius: 10px;
    background: #f9fcff;
    padding: 12px;
}

.celula-modal {
    position: fixed;
    inset: 0;
    z-index: 1050;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.2s ease;
}

.celula-modal.is-open {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

.celula-modal__overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 27, 46, 0.58);
    backdrop-filter: blur(2px);
    opacity: 0;
    transition: opacity 0.2s ease;
}

.celula-modal__dialog {
    position: relative;
    width: min(1100px, calc(100vw - 36px));
    max-height: calc(100vh - 36px);
    margin: 18px auto;
    border-radius: 14px;
    background: #ffffff;
    box-shadow: 0 18px 45px rgba(20, 39, 72, 0.24);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transform: translateY(22px) scale(0.985);
    opacity: 0;
    transition: transform 0.24s ease, opacity 0.24s ease;
}

.celula-modal.is-open .celula-modal__overlay {
    opacity: 1;
}

.celula-modal.is-open .celula-modal__dialog {
    transform: translateY(0) scale(1);
    opacity: 1;
}

.celula-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid #d9e4f2;
    background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
}

.celula-modal__title {
    margin: 0;
    color: #21457e;
    font-size: 20px;
    font-weight: 700;
}

.celula-modal__close {
    border: 0;
    background: #dbe6f8;
    color: #1e4a89;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
}

.celula-modal__body {
    padding: 14px 18px 18px;
    overflow: auto;
}

.reporte-celulas-abiertas-table {
    min-width: 1520px;
    table-layout: auto;
}

.reporte-celulas-abiertas-table thead tr:nth-child(2) th {
    min-width: 56px;
    font-size: 11px;
    white-space: nowrap;
}

.report-empty-state {
    border: 1px dashed #cdd8e8;
    background: #f8fbff;
    border-radius: 10px;
    padding: 18px;
    color: #334155;
    text-align: center;
}

.data-table--compacta-celula th,
.data-table--compacta-celula td {
    padding: 7px 8px;
    font-size: 13px;
    line-height: 1.25;
}

.data-table--compacta-celula th {
    white-space: nowrap;
}

.data-table--compacta-celula td:nth-child(1) {
    max-width: 220px;
    white-space: normal;
    word-break: break-word;
}

.data-table--compacta-celula td:nth-child(2) {
    max-width: 140px;
    white-space: normal;
    word-break: break-word;
}

details summary {
    cursor: pointer;
    color: #1f3f74;
    font-weight: 600;
}

.reporte-metas-wrap {
    overflow-x: auto;
}

.reporte-metas-table {
    min-width: 1180px;
    border-collapse: separate;
    border-spacing: 0;
}

.reporte-metas-table--compacta {
    min-width: 820px;
}

.reporte-metas-table thead tr:first-child th {
    background: #f0f3f8;
    text-align: center;
    font-weight: 800;
    font-size: 13px;
    white-space: nowrap;
    word-break: normal;
    overflow-wrap: normal;
    writing-mode: horizontal-tb;
    text-orientation: mixed;
    letter-spacing: 0.2px;
}

.reporte-metas-table thead tr:nth-child(2) th {
    background: #f8fafc;
    text-align: center;
    font-weight: 700;
    font-size: 12px;
    white-space: nowrap;
    word-break: normal;
    overflow-wrap: normal;
}

.reporte-metas-table td {
    text-align: center;
    vertical-align: middle;
    padding: 7px 8px;
    font-size: 14px;
}

.reporte-metas-table td:nth-child(2),
.reporte-metas-table th:nth-child(2) {
    text-align: left;
    min-width: 210px;
    white-space: normal;
    word-break: keep-all;
    overflow-wrap: break-word;
    line-height: 1.25;
}

.reporte-metas-table tbody tr:nth-child(even):not(.reporte-metas-total-row) {
    background: #fafcff;
}

.meta-pill-cell {
    display: inline-flex;
    min-width: 52px;
    justify-content: center;
    align-items: center;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 700;
}

.ganado-ok {
    background: #d9fbe5;
    color: #116738;
}

.ganado-medio {
    background: #fff176;
    color: #3f2f00;
}

.ganado-bajo {
    background: #ff4d4f;
    color: #ffffff;
}

.pendiente-ok {
    background: #d9fbe5;
    color: #116738;
}

.pendiente-medio {
    background: #fff6cc;
    color: #7f6000;
}

.pendiente-alto {
    background: #ffe0e0;
    color: #8f1d1d;
}

.reporte-metas-total-row {
    background: #000000;
}

.reporte-metas-total-row td {
    color: #ffffff;
    font-weight: 800;
}

@media (max-width: 1000px) {
    .report-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr));     .report-kpi-grid--escalera { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
}

@media (max-width: 640px) {
    .report-kpi-grid { grid-template-columns: 1fr; }

    .celula-modal__dialog {
        width: calc(100vw - 16px);
        max-height: calc(100vh - 16px);
        margin: 8px auto;
    }

    .celula-modal__header,
    .celula-modal__body {
        padding-left: 12px;
        padding-right: 12px;
    }

    .celula-modal__title {
        font-size: 18px;
    }
}

</style>

<?php include VIEWS . '/layout/footer.php'; ?>

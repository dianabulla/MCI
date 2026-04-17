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

$anioMinisterialTablas = (int)($anio_ministerial_tablas ?? date('Y'));
$tablasMinisterial = $tablas_ministerial ?? [];
$detallesTablasMinisterial = $detalles_tablas_ministerial ?? [];
$tablaGananciaMinisterial = $tabla_ganancia_ministerial ?? [];
$detallesGananciaMinisterial = $detalles_ganancia_ministerial ?? [];

$renderTablaGananciaMinisterial = static function(array $tabla, int $anio) {
    $rows = $tabla['rows'] ?? [];
    $meses = $tabla['meses'] ?? [];
    $totales = $tabla['totales'] ?? [];
    if (empty($rows)) {
        echo '<div class="card report-card" style="margin-bottom:22px;"><div class="report-empty-state">Sin datos para la tabla de ganancia por ministerio.</div></div>';
        return;
    }
    ?>
    <div class="card report-card report-metas-card" style="margin-bottom: 22px;">
        <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-bottom:10px;">
            <div>
                <h3 style="margin-bottom:4px;">Ganancia de almas por ministerio (<?= $anio ?>)</h3>
                <small style="color:#60708a;">Meses por columna: G.C = Ganados Célula, G.I = Ganados Iglesia</small>
            </div>
        </div>
        <div class="table-container reporte-metas-wrap">
            <table class="data-table rpt-ganancia-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-num-sm">N°</th>
                        <th rowspan="2" class="col-ministerio">MINISTERIO</th>
                        <?php foreach ($meses as $m => $label): ?>
                            <th colspan="2" class="col-mes-group"><?= htmlspecialchars($label) ?></th>
                        <?php endforeach; ?>
                        <th rowspan="2" class="col-sub col-anual-head">TOTAL</th>
                    </tr>
                    <tr>
                        <?php foreach ($meses as $m => $label): ?>
                            <th class="col-sub">G.C</th>
                            <th class="col-sub">G.I</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $n = 1; foreach ($rows as $row):
                        $ministerio = (string)($row['ministerio'] ?? '');
                    ?>
                    <tr>
                        <td class="col-num-sm"><?= $n++ ?></td>
                        <td class="col-ministerio-label"><?= htmlspecialchars($ministerio) ?></td>
                        <?php foreach ($meses as $m => $label):
                            $vc = (int)($row['meses'][$m]['celula'] ?? 0);
                            $vi = (int)($row['meses'][$m]['iglesia'] ?? 0);
                        ?>
                            <td><?php if ($vc > 0): ?><button type="button" class="report-link-button js-open-ganancia-main" data-ministerio="<?= htmlspecialchars($ministerio, ENT_QUOTES) ?>" data-col="celula" data-mes="<?= $m ?>"><?= $vc ?></button><?php else: ?><span class="rpt-cero">0</span><?php endif; ?></td>
                            <td><?php if ($vi > 0): ?><button type="button" class="report-link-button js-open-ganancia-main" data-ministerio="<?= htmlspecialchars($ministerio, ENT_QUOTES) ?>" data-col="iglesia" data-mes="<?= $m ?>"><?= $vi ?></button><?php else: ?><span class="rpt-cero">0</span><?php endif; ?></td>
                        <?php endforeach; ?>
                        <?php $at = (int)($row['anual']['total'] ?? 0); ?>
                        <td><?php if ($at > 0): ?><button type="button" class="report-link-button js-open-ganancia-main" data-ministerio="<?= htmlspecialchars($ministerio, ENT_QUOTES) ?>" data-col="total" data-mes="0"><?= $at ?></button><?php else: ?><span class="rpt-cero">0</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="reporte-metas-total-row">
                        <td colspan="2"><strong>TOTAL</strong></td>
                        <?php foreach ($meses as $m => $label):
                            $tc = (int)($totales['meses'][$m]['celula'] ?? 0);
                            $ti = (int)($totales['meses'][$m]['iglesia'] ?? 0);
                        ?>
                            <td><?php if ($tc > 0): ?><button type="button" class="report-link-button js-open-ganancia-main" data-ministerio="__todos__" data-col="celula" data-mes="<?= $m ?>"><?= $tc ?></button><?php else: ?>0<?php endif; ?></td>
                            <td><?php if ($ti > 0): ?><button type="button" class="report-link-button js-open-ganancia-main" data-ministerio="__todos__" data-col="iglesia" data-mes="<?= $m ?>"><?= $ti ?></button><?php else: ?>0<?php endif; ?></td>
                        <?php endforeach; ?>
                        <?php $tat = (int)($totales['anual']['total'] ?? 0); ?>
                        <td><?php if ($tat > 0): ?><button type="button" class="report-link-button js-open-ganancia-main" data-ministerio="__todos__" data-col="total" data-mes="0"><?= $tat ?></button><?php else: ?>0<?php endif; ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php
};

$renderTablaMinisterial = static function(string $tablaKey, array $tabla, array $headers) {
    $rows = $tabla['rows'] ?? [];
    $totales = $tabla['totales'] ?? [];
    $titulo = $tabla['titulo'] ?? strtoupper($tablaKey);
    $cols = array_keys($headers);
    ?>
    <div class="card report-card report-metas-card" style="margin-bottom: 0;">
        <div style="margin-bottom:8px;"><h3 style="margin:0;"><?= htmlspecialchars($titulo) ?></h3></div>
        <div class="table-container reporte-metas-wrap">
            <table class="data-table rpt-min-table">
                <thead>
                    <tr>
                        <th class="col-mes">MES</th>
                        <?php foreach ($headers as $col => $label): ?>
                            <th class="col-num"><?= htmlspecialchars($label) ?></th>
                        <?php endforeach; ?>
                        <th class="col-num">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($m = 1; $m <= 12; $m++): $row = $rows[$m] ?? []; ?>
                        <tr>
                            <td class="col-mes-label"><?= htmlspecialchars((string)($row['mes'] ?? '')) ?></td>
                            <?php foreach ($cols as $col): $val = (int)($row[$col] ?? 0); ?>
                                <td><?php if ($val > 0): ?><button type="button" class="report-link-button js-open-ministerial" data-tabla="<?= htmlspecialchars($tablaKey, ENT_QUOTES) ?>" data-col="<?= htmlspecialchars($col, ENT_QUOTES) ?>" data-mes="<?= $m ?>"><?= $val ?></button><?php else: ?><span class="rpt-cero">&#8212;</span><?php endif; ?></td>
                            <?php endforeach; ?>
                            <?php $total = (int)($row['total'] ?? 0); ?>
                            <td><?php if ($total > 0): ?><button type="button" class="report-link-button js-open-ministerial" data-tabla="<?= htmlspecialchars($tablaKey, ENT_QUOTES) ?>" data-col="total" data-mes="<?= $m ?>"><?= $total ?></button><?php else: ?><span class="rpt-cero">&#8212;</span><?php endif; ?></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
                <tfoot>
                    <tr class="reporte-metas-total-row">
                        <td><strong>TOTAL</strong></td>
                        <?php foreach ($cols as $col): $val = (int)($totales[$col] ?? 0); ?>
                            <td><?php if ($val > 0): ?><button type="button" class="report-link-button js-open-ministerial" data-tabla="<?= htmlspecialchars($tablaKey, ENT_QUOTES) ?>" data-col="<?= htmlspecialchars($col, ENT_QUOTES) ?>" data-mes="0"><?= $val ?></button><?php else: ?>&#8212;<?php endif; ?></td>
                        <?php endforeach; ?>
                        <?php $totTotal = (int)($totales['total'] ?? 0); ?>
                        <td><?php if ($totTotal > 0): ?><button type="button" class="report-link-button js-open-ministerial" data-tabla="<?= htmlspecialchars($tablaKey, ENT_QUOTES) ?>" data-col="total" data-mes="0"><?= $totTotal ?></button><?php else: ?>&#8212;<?php endif; ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php
};

?>

<div class="page-header report-page-header report-shell-head">
    <div>
        <h2>Reportes</h2>
        <small class="report-shell-subtitle"><?= htmlspecialchars($tituloReporte) ?> · Vista tipo panel</small>
    </div>

    <div class="report-shell-actions">
        <span class="report-shell-date"><?= date('D, M j') ?></span>
    </div>

</div>

<div class="report-top-strip" id="reportTopStrip" style="margin-bottom: 14px;">
    <button type="button" class="report-top-strip-tab" data-mode="tablas">Ver tablas</button>
    <button type="button" class="report-top-strip-tab" data-mode="graficos">Ver gráficos</button>
    <button type="button" class="report-top-strip-tab is-active" data-breakdown="ministerio">Por ministerio</button>
    <button type="button" class="report-top-strip-tab" data-breakdown="lider">Por líder</button>
</div>

<div class="card report-card report-toolbar-card" style="margin-bottom: 18px; padding: 14px;">
    <div class="form-group" style="margin:0; max-width: 340px;">
        <label for="selector_reporte" style="margin-bottom:6px;">Reporte a visualizar</label>
        <select id="selector_reporte" class="form-control" onchange="if(this.value){ window.location.href = this.value; }">
            <option value="<?= htmlspecialchars($buildReporteUrl(['tipo' => 'personas'])) ?>" <?= $esReportePersonas ? 'selected' : '' ?>>Reporte de Ganar</option>
            <option value="<?= htmlspecialchars($buildReporteUrl(['tipo' => 'celulas'])) ?>" <?= !$esReportePersonas ? 'selected' : '' ?>>Reporte de Célula</option>
            <option value="<?= PUBLIC_URL ?>index.php?url=reportes/ministerial">Escalera del Éxito</option>
        </select>
    </div>
    <div class="report-toolbar-actions">
        <a href="<?= PUBLIC_URL ?>index.php?url=reportes&tipo=<?= urlencode($tipoReporte) ?>" class="report-icon-btn" title="Refrescar">☁</a>
    </div>
</div>

<div class="card report-card" style="margin-bottom: 22px;">
    <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="filters-inline report-compact-form" style="padding: 18px;">
        <input type="hidden" name="url" value="reportes">
        <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipoReporte) ?>">

        <div class="form-group report-date-group" style="margin: 0;">
            <label for="fecha_referencia">Fecha de la semana (lunes a domingo)</label>
            <input type="date" id="fecha_referencia" name="fecha_referencia" class="form-control" value="<?= htmlspecialchars((string)$fecha_referencia) ?>" required>
            <small style="color:#637087;">Rango aplicado: <?= date('d/m/Y', strtotime($fecha_inicio)) ?> - <?= date('d/m/Y', strtotime($fecha_fin)) ?></small>
        </div>

        <div class="filters-actions">
            <button type="submit" class="btn btn-primary">Aplicar</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=reportes&tipo=<?= urlencode($tipoReporte) ?>" class="btn btn-secondary">Resetear</a>
        </div>
    </form>
</div>

<div id="reportesVisualContainer">

<?php if ($esReportePersonas): ?>
<div class="card report-card" style="margin-bottom: 12px; padding: 12px 14px;">
    <strong><?= htmlspecialchars($ganarLabel) ?></strong>
    <span style="color:#64748b;">(<?= htmlspecialchars($ganarInicio) ?> a <?= htmlspecialchars($ganarFin) ?>)</span>
</div>

<div class="report-kpi-grid report-kpi-grid--ganar" style="margin-bottom: 18px;">
    <button type="button" class="report-kpi-card report-kpi-button kpi-celula js-kpi-detalle" data-origen="celula">
        <div class="report-kpi-icon">👤</div>
        <div class="report-kpi-label">Ganados en célula</div>
        <div class="report-kpi-value"><?= (int)$resumenOrigen['Ganados_Celula'] ?></div>
    </button>
    <button type="button" class="report-kpi-card report-kpi-button kpi-domingo js-kpi-detalle" data-origen="domingo">
        <div class="report-kpi-icon">✅</div>
        <div class="report-kpi-label">Ganados en domingo</div>
        <div class="report-kpi-value"><?= (int)$resumenOrigen['Ganados_Domingo'] ?></div>
    </button>
    <button type="button" class="report-kpi-card report-kpi-button kpi-asistencia js-kpi-detalle" data-origen="asignados">
        <div class="report-kpi-icon">📌</div>
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
        <button type="button" id="toggleGanadosSemanaAnteriorBtn" class="btn btn-secondary">Ver detalle</button>
    </div>

    <div id="reporteGanadosSemanaAnteriorDetalle" style="display:none;">
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
</div>

<div class="ganar-extra-section">
<div class="report-breakdown-block report-breakdown-ministerio">
<?php $renderTablaGananciaMinisterial($tablaGananciaMinisterial, $anioMinisterialTablas); ?>

<div class="rpt-mini-grid" style="margin-bottom: 22px;">
    <?php
    $headersTablasMinisterial = [
        'ganar' => ['gi' => 'G.I', 'gc' => 'G.C', 'v' => 'V'],
        'consolidar' => ['uv' => 'U.V', 'e' => 'E', 'b' => 'B'],
        'discipular' => ['cdm12' => 'CD-M1-2', 'cdm34' => 'CD-M3-4', 'cdm56' => 'CD-M5-6'],
        'enviar' => ['celulas' => '# CELULAS'],
    ];
    foreach ($headersTablasMinisterial as $keyTabla => $headersTabla) {
        $renderTablaMinisterial($keyTabla, $tablasMinisterial[$keyTabla] ?? [], $headersTabla);
    }
    ?>
</div>
</div>

<div class="card report-card report-breakdown-block report-breakdown-lider" style="margin-bottom: 22px;">
    <h3>Ganados por líder (tabla)</h3>
    <div class="table-container" style="margin-top: 10px;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Líder</th>
                    <th style="width: 140px;">Ganados</th>
                </tr>
            </thead>
            <tbody id="tablaGanadosLiderBody">
                <tr>
                    <td colspan="2" class="text-center">Sin datos para el rango seleccionado</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card report-card report-breakdown-block report-breakdown-lider" style="margin-bottom: 22px;">
    <h3>Ganados por líder</h3>
    <div id="chartLideres"></div>
</div>

<div class="card report-card report-breakdown-block report-breakdown-ministerio" style="margin-bottom: 22px;">
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
</div>
<?php else: ?>
<div class="report-kpi-grid report-kpi-grid--celulas" style="margin-bottom: 18px;">
    <div class="report-kpi-card kpi-escalera">
        <div class="report-kpi-icon">🏠</div>
        <div class="report-kpi-label">Total de células</div>
        <div class="report-kpi-value"><?= (int)($indicadoresCelulas['totales']['total_celulas'] ?? 0) ?></div>
    </div>
    <div class="report-kpi-card kpi-domingo">
        <div class="report-kpi-icon">🆕</div>
        <div class="report-kpi-label">Nuevas en semestre</div>
        <div class="report-kpi-value"><?= (int)($indicadoresCelulas['totales']['nuevas_semestre'] ?? 0) ?></div>
    </div>
    <div class="report-kpi-card kpi-celula">
        <div class="report-kpi-icon">⛔</div>
        <div class="report-kpi-label">Cerradas en semestre</div>
        <div class="report-kpi-value"><?= (int)($indicadoresCelulas['totales']['cerradas_semestre'] ?? 0) ?></div>
    </div>
    <div class="report-kpi-card kpi-asistencia">
        <div class="report-kpi-icon">📋</div>
        <div class="report-kpi-label">Reportadas semana</div>
        <div class="report-kpi-value"><?= (int)($indicadoresCelulas['totales']['reportadas_semana'] ?? 0) ?></div>
    </div>
    <div class="report-kpi-card kpi-domingo">
        <div class="report-kpi-icon">⚠️</div>
        <div class="report-kpi-label">No reportadas semana</div>
        <div class="report-kpi-value"><?= (int)($indicadoresCelulas['totales']['no_reportadas_semana'] ?? 0) ?></div>
    </div>
    <div class="report-kpi-card kpi-asistencia">
        <div class="report-kpi-icon">📈</div>
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

</div>

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
const detallesTablasMinisterial = <?= json_encode($detallesTablasMinisterial ?? [], JSON_UNESCAPED_UNICODE) ?>;
const detallesGananciaMinisterial = <?= json_encode($detallesGananciaMinisterial ?? [], JSON_UNESCAPED_UNICODE) ?>;
const tipoReporte = <?= json_encode($tipoReporte) ?>;
const nombresCelulas = asistencia.map(x => (x.Nombre_Celula || 'Sin célula'));
const etiquetasCelulas = nombresCelulas.map(nombre => {
    const limpio = String(nombre || '').trim();
    return limpio.length > 20 ? `${limpio.slice(0, 20)}...` : limpio;
});

const reportTopStrip = document.getElementById('reportTopStrip');
const reportModeButtons = reportTopStrip ? reportTopStrip.querySelectorAll('[data-mode]') : [];
const reportBreakdownButtons = reportTopStrip ? reportTopStrip.querySelectorAll('[data-breakdown]') : [];
const STORAGE_MODE_KEY = 'reportes_view_mode';
const STORAGE_BREAKDOWN_KEY = 'reportes_view_breakdown';

const aplicarModoReporte = (modo) => {
    const root = document.documentElement;
    root.classList.remove('show-report-tables', 'show-report-charts');

    if (modo === 'tablas') {
        root.classList.add('show-report-tables');
    }

    if (modo === 'graficos') {
        root.classList.add('show-report-charts');
    }

    reportModeButtons.forEach((btn) => {
        btn.classList.toggle('is-active', String(btn.dataset.mode || '') === modo);
    });
};

if (reportModeButtons.length) {
    reportModeButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const modoSeleccionado = String(btn.dataset.mode || 'tablas');
            aplicarModoReporte(modoSeleccionado);
            try {
                localStorage.setItem(STORAGE_MODE_KEY, modoSeleccionado);
            } catch (e) {
                // Ignorar errores de almacenamiento del navegador.
            }
        });
    });

    let modoInicial = 'tablas';
    try {
        const guardado = String(localStorage.getItem(STORAGE_MODE_KEY) || '').trim();
        if (guardado === 'tablas' || guardado === 'graficos') {
            modoInicial = guardado;
        }
    } catch (e) {
        // Mantener valor por defecto.
    }

    aplicarModoReporte(modoInicial);
}

const aplicarBreakdownReporte = (tipo) => {
    const root = document.documentElement;
    root.classList.toggle('breakdown-lider', tipo === 'lider');
    root.classList.toggle('breakdown-ministerio', tipo !== 'lider');

    reportBreakdownButtons.forEach((btn) => {
        btn.classList.toggle('is-active', String(btn.dataset.breakdown || '') === tipo);
    });
};

if (reportBreakdownButtons.length) {
    reportBreakdownButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const breakdownSeleccionado = String(btn.dataset.breakdown || 'ministerio');
            aplicarBreakdownReporte(breakdownSeleccionado);
            try {
                localStorage.setItem(STORAGE_BREAKDOWN_KEY, breakdownSeleccionado);
            } catch (e) {
                // Ignorar errores de almacenamiento del navegador.
            }
        });
    });

    let breakdownInicial = 'ministerio';
    try {
        const guardado = String(localStorage.getItem(STORAGE_BREAKDOWN_KEY) || '').trim();
        if (guardado === 'ministerio' || guardado === 'lider') {
            breakdownInicial = guardado;
        }
    } catch (e) {
        // Mantener valor por defecto.
    }

    aplicarBreakdownReporte(breakdownInicial);
}

const toggleGanadosSemanaAnteriorBtn = document.getElementById('toggleGanadosSemanaAnteriorBtn');
const reporteGanadosSemanaAnteriorDetalle = document.getElementById('reporteGanadosSemanaAnteriorDetalle');
if (toggleGanadosSemanaAnteriorBtn && reporteGanadosSemanaAnteriorDetalle) {
    toggleGanadosSemanaAnteriorBtn.addEventListener('click', () => {
        const mostrar = reporteGanadosSemanaAnteriorDetalle.style.display === 'none';
        reporteGanadosSemanaAnteriorDetalle.style.display = mostrar ? 'block' : 'none';
        toggleGanadosSemanaAnteriorBtn.textContent = mostrar ? 'Ocultar detalle' : 'Ver detalle';
    });
}

if (tipoReporte === 'personas') {
    const registrosGanados = ['celula', 'domingo', 'asignados']
        .flatMap((key) => Array.isArray(detalleOrigenGanados[key]) ? detalleOrigenGanados[key] : []);

    const conteoLideresMap = {};
    registrosGanados.forEach((item) => {
        const nombreLider = String(item.Nombre_Lider || 'Sin líder').trim() || 'Sin líder';
        conteoLideresMap[nombreLider] = (conteoLideresMap[nombreLider] || 0) + 1;
    });

    const rankingLideres = Object.entries(conteoLideresMap)
        .map(([lider, total]) => ({ lider, total: parseInt(total || 0, 10) }))
        .sort((a, b) => b.total - a.total)
        .slice(0, 12);

    const tablaGanadosLiderBody = document.getElementById('tablaGanadosLiderBody');
    const escapeRowText = (valor) => String(valor || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    if (tablaGanadosLiderBody) {
        if (!rankingLideres.length) {
            tablaGanadosLiderBody.innerHTML = '<tr><td colspan="2" class="text-center">Sin datos para el rango seleccionado</td></tr>';
        } else {
            tablaGanadosLiderBody.innerHTML = rankingLideres.map((item) => (
                `<tr><td>${escapeRowText(item.lider)}</td><td><strong>${item.total}</strong></td></tr>`
            )).join('');
        }
    }

    new ApexCharts(document.querySelector('#chartLideres'), {
        chart: { type: 'bar', height: 340, toolbar: { show: false } },
        series: [{
            name: 'Ganados',
            data: rankingLideres.map(x => x.total)
        }],
        xaxis: {
            categories: rankingLideres.map(x => x.lider),
            labels: {
                rotate: -20,
                trim: true
            }
        },
        dataLabels: { enabled: true },
        colors: ['#3b82f6']
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

    const nombresMesMinisterial = {
        0: 'Anual', 1: 'Enero', 2: 'Febrero', 3: 'Marzo', 4: 'Abril', 5: 'Mayo', 6: 'Junio',
        7: 'Julio', 8: 'Agosto', 9: 'Septiembre', 10: 'Octubre', 11: 'Noviembre', 12: 'Diciembre'
    };

    const etiquetasColMinisterial = {
        ganar: { gi: 'Ganados Iglesia', gc: 'Ganados Célula', v: 'Otros', total: 'Total' },
        consolidar: { uv: 'Universidad de la Vida', e: 'Encuentro', b: 'Bautismo', total: 'Total' },
        discipular: { cdm12: 'CD-M1-2', cdm34: 'CD-M3-4', cdm56: 'CD-M5-6', total: 'Total' },
        enviar: { celulas: '# Células', total: 'Total' }
    };

    const construirFilaPersona = (item) => {
        const nombre = String(item.nombre || `${item.Nombre || ''} ${item.Apellido || ''}`.trim() || 'Sin nombre');
        const lider = String(item.lider || item.Nombre_Lider || 'Sin líder');
        const celula = String(item.celula || item.Nombre_Celula || 'Sin célula');
        const ministerio = String(item.ministerio || item.Nombre_Ministerio || 'Sin ministerio');
        const proceso = String(item.proceso || item.Proceso || '');
        const fecha = String(item.fecha_registro || item.Fecha_Registro || '');
        return `<tr><td>${escaparHtml(nombre)}</td><td>${escaparHtml(lider)}</td><td>${escaparHtml(celula)}</td><td>${escaparHtml(ministerio)}</td><td>${escaparHtml(proceso)}</td><td>${escaparHtml(fecha)}</td></tr>`;
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

    document.querySelectorAll('.js-open-ministerial').forEach((boton) => {
        boton.addEventListener('click', () => {
            const tabla = String(boton.dataset.tabla || '');
            const col = String(boton.dataset.col || '');
            const mes = parseInt(boton.dataset.mes || '0', 10);
            const datos = ((detallesTablasMinisterial[tabla] || {})[col] || {});
            const filas = mes === 0 ? Object.values(datos).flat() : (datos[mes] || []);
            const etiquetaCol = ((etiquetasColMinisterial[tabla] || {})[col]) || col;
            reporteDetalleModalTitle.textContent = `${String(tabla || '').toUpperCase()} - ${etiquetaCol} - ${nombresMesMinisterial[mes] || ''}`;
            if (!filas.length) {
                reporteDetalleModalBody.innerHTML = '<tr><td colspan="6" class="text-center">Sin personas para este filtro</td></tr>';
            } else {
                reporteDetalleModalBody.innerHTML = filas.map(construirFilaPersona).join('');
            }
            reporteDetalleModal.classList.add('is-open');
            reporteDetalleModal.setAttribute('aria-hidden', 'false');
        });
    });

    document.querySelectorAll('.js-open-ganancia-main').forEach((boton) => {
        boton.addEventListener('click', () => {
            const ministerio = String(boton.dataset.ministerio || '');
            const col = String(boton.dataset.col || '');
            const mes = parseInt(boton.dataset.mes || '0', 10);
            let filas;
            if (ministerio === '__todos__') {
                const allMins = Object.values(detallesGananciaMinisterial);
                const allCols = allMins.map((m) => (m[col] || {}));
                filas = mes === 0 ? allCols.flatMap((c) => Object.values(c)).flat() : allCols.flatMap((c) => (c[mes] || []));
            } else {
                const datos = ((detallesGananciaMinisterial[ministerio] || {})[col] || {});
                filas = mes === 0 ? Object.values(datos).flat() : (datos[mes] || []);
            }
            const etiquetaCol = col === 'celula' ? 'Ganados Célula' : (col === 'iglesia' ? 'Ganados Iglesia' : 'Total');
            const etiquetaMinisterio = ministerio === '__todos__' ? 'Todos los ministerios' : ministerio;
            reporteDetalleModalTitle.textContent = `GANANCIA - ${etiquetaMinisterio} - ${etiquetaCol} - ${nombresMesMinisterial[mes] || ''}`;
            if (!filas.length) {
                reporteDetalleModalBody.innerHTML = '<tr><td colspan="6" class="text-center">Sin personas para este filtro</td></tr>';
            } else {
                reporteDetalleModalBody.innerHTML = filas.map(construirFilaPersona).join('');
            }
            reporteDetalleModal.classList.add('is-open');
            reporteDetalleModal.setAttribute('aria-hidden', 'false');
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

#reportesVisualContainer .table-container,
#reportesVisualContainer details {
    display: none;
}

#reportesVisualContainer .ganar-extra-section {
    display: none;
}

#reportesVisualContainer .report-breakdown-block {
    display: none;
}

html.show-report-tables #reportesVisualContainer .table-container,
html.show-report-tables #reportesVisualContainer details {
    display: block;
}

html.show-report-tables #reportesVisualContainer .ganar-extra-section {
    display: block;
}

html.show-report-tables.breakdown-ministerio #reportesVisualContainer .report-breakdown-ministerio,
html.show-report-charts.breakdown-ministerio #reportesVisualContainer .report-breakdown-ministerio {
    display: block;
}

html.show-report-tables.breakdown-lider #reportesVisualContainer .report-breakdown-lider,
html.show-report-charts.breakdown-lider #reportesVisualContainer .report-breakdown-lider {
    display: block;
}

html.show-report-charts #reportesVisualContainer .ganar-extra-section {
    display: block;
}

html.show-report-charts #reportesVisualContainer .btn-chart-toggle,
html.show-report-charts #reportesVisualContainer .rpt-chart-wrap,
html.show-report-charts #reportesVisualContainer [id^="chart"] {
    display: block !important;
}

.celula-modal .table-container {
    display: block !important;
}

#reportesVisualContainer .btn-chart-toggle,
#reportesVisualContainer .rpt-chart-wrap,
#reportesVisualContainer [id^="chart"] {
    display: none !important;
}

.report-card {
    background: #fff;
    padding: 20px;
    border-radius: 14px;
    border: 1px solid #e1e9f3;
    box-shadow: 0 10px 26px rgba(17, 42, 72, 0.08);
}

.report-shell-head {
    background: linear-gradient(90deg, #1a4f87 0%, #2f6fae 100%);
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid #194a7e;
}

.report-shell-head h2 {
    color: #ffffff;
    margin-bottom: 2px;
}

.report-shell-subtitle {
    color: rgba(235, 243, 255, 0.9);
}

.report-shell-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.report-shell-date {
    background: rgba(255, 255, 255, 0.16);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #ffffff;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 700;
}

.report-shell-head .btn.btn-secondary {
    background: #f3f8ff;
    border: 1px solid #d5e4f8;
    color: #1a4f87;
}

.report-top-strip {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    border-bottom: 2px solid #d8e3f2;
    padding-bottom: 8px;
}

.report-top-strip-tab {
    border: 1px solid #b8d0ea;
    background: #eaf3ff;
    color: #1c466f;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 700;
    cursor: default;
}

.report-top-strip-tab.is-active {
    background: #2b6ba7;
    border-color: #255f95;
    color: #ffffff;
}

.report-toolbar-card {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 12px;
    flex-wrap: wrap;
}

.report-toolbar-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.report-icon-btn {
    appearance: none;
    border: 1px solid #c9d8ea;
    background: #ffffff;
    color: #1d446f;
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(17, 42, 72, 0.08);
}

.report-icon-btn:hover {
    background: #eef5ff;
}

.report-page-header {
    margin-bottom: 16px;
}

.report-compact-form {
    display: flex;
    align-items: end;
    gap: 14px;
    flex-wrap: wrap;
}

.report-date-group {
    min-width: 280px;
    max-width: 360px;
}

.report-compact-form .filters-actions {
    margin-left: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.report-metas-card {
    padding: 14px;
}

.rpt-mini-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.rpt-min-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 320px;
}

.rpt-min-table th {
    background: #1e4a89;
    color: #fff;
    font-size: 12px;
    padding: 7px 8px;
    border: 1px solid #16397a;
    text-align: center;
    white-space: nowrap;
}

.rpt-min-table td {
    border: 1px solid #dde4f0;
    text-align: center;
    padding: 6px 8px;
    font-size: 13px;
}

.rpt-min-table .col-mes {
    min-width: 56px;
}

.rpt-min-table .col-mes-label {
    text-align: left;
    font-weight: 700;
    font-size: 12px;
}

.rpt-ganancia-table {
    border-collapse: collapse;
    width: max-content;
    min-width: 100%;
    table-layout: auto !important;
}

.rpt-ganancia-table th,
.rpt-ganancia-table td {
    white-space: nowrap !important;
    word-break: normal !important;
    overflow-wrap: normal !important;
}

.rpt-ganancia-table th {
    background: #1e4a89;
    color: #fff;
    font-weight: 700;
    text-align: center;
    padding: 4px 5px;
    border: 1px solid #16397a;
    font-size: 12px;
}

.rpt-ganancia-table td {
    text-align: center;
    padding: 4px 5px;
    border: 1px solid #dde4f0;
    font-size: 12px;
}

.rpt-ganancia-table .col-num-sm {
    width: 26px;
}

.rpt-ganancia-table .col-ministerio {
    width: 130px;
    min-width: 130px;
    max-width: 130px;
    text-align: left !important;
}

.rpt-ganancia-table .col-ministerio-label {
    text-align: left !important;
    font-weight: 600;
    font-size: 12px;
    padding-left: 8px !important;
    white-space: normal !important;
    word-break: keep-all !important;
    overflow-wrap: break-word !important;
}

.rpt-ganancia-table .col-mes-group {
    width: 36px;
}

.rpt-ganancia-table .col-sub {
    width: 18px;
    font-size: 11px;
    padding: 4px 3px !important;
}

.rpt-ganancia-table .col-anual-head {
    background: #0c2a54 !important;
    color: #e0eaff !important;
    border-color: #091e3d !important;
}

.rpt-cero {
    color: #97a6bd;
}

.report-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.report-kpi-grid--ganar {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.report-kpi-grid--celulas {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
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
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-height: 118px;
    background: #ffffff;
    border: 1px solid #dce7f4;
    box-shadow: 0 8px 20px rgba(14, 39, 67, 0.08);
}

.report-kpi-icon {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    margin-bottom: 2px;
    background: linear-gradient(180deg, #f0f8ff, #ffffff);
    border: 1px solid #cddff4;
    color: #25588f;
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

.kpi-celula { border-top: 4px solid #4a9ee8; }
.kpi-domingo { border-top: 4px solid #e6a93e; }
.kpi-escalera { border-top: 4px solid #4cbf6e; }
.kpi-asistencia { border-top: 4px solid #7f88e6; }

@media (max-width: 1100px) {
    .report-kpi-grid--escalera {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .escalera-stage-visual-grid {
        grid-template-columns: 1fr;
    }
}

.report-kpi-label { font-size: .82rem; color: #4f6279; font-weight: 600; }
.report-kpi-value { font-size: 1.9rem; font-weight: 800; color: #12335a; }

.report-kpi-grid .report-kpi-card {
    transition: transform .18s ease, box-shadow .18s ease;
}

.report-kpi-grid .report-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(12, 36, 62, 0.12);
}

#toggleGanadosSemanaAnteriorBtn {
    border-radius: 8px;
    font-weight: 700;
}

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
    .report-kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .report-kpi-grid--escalera {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .report-compact-form {
        align-items: stretch;
    }

    .report-date-group {
        min-width: 100%;
        max-width: 100%;
    }
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

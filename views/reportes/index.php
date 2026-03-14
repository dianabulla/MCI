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

$filtroMesMeta = (string)($filtro_mes_meta ?? '');
$mesesTabla = $cumplimientoMetas['meses'] ?? [];
if ($filtroMesMeta !== '' && $filtroMesMeta !== 'all') {
    $mesesTabla = array_values(array_filter($mesesTabla, static function($mes) use ($filtroMesMeta) {
        return (string)($mes['key'] ?? '') === $filtroMesMeta;
    }));
}

$tablaEsCompacta = $filtroMesMeta !== 'all';

?>

<div class="page-header">
    <h2>Reportes Semanales</h2>
    <a href="<?= PUBLIC_URL ?>?url=reportes/exportarExcel&fecha_referencia=<?= urlencode((string)$fecha_referencia) ?>&ministerio=<?= urlencode((string)$filtro_ministerio) ?>&lider=<?= urlencode((string)$filtro_lider) ?>&celula=<?= urlencode((string)$filtro_celula) ?>" class="btn btn-success">
        Exportar Excel
    </a>
</div>

<div class="card report-card" style="margin-bottom: 18px;">
    <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="filters-inline" style="padding: 14px;">
        <input type="hidden" name="url" value="reportes">

        <div class="form-group" style="margin: 0;">
            <label for="fecha_referencia">Semana (domingo a domingo)</label>
            <input type="date" id="fecha_referencia" name="fecha_referencia" class="form-control" value="<?= htmlspecialchars((string)$fecha_referencia) ?>" required>
            <small style="color:#637087;">Rango aplicado: <?= date('d/m/Y', strtotime($fecha_inicio)) ?> - <?= date('d/m/Y', strtotime($fecha_fin)) ?></small>
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

        <div class="filters-actions">
            <button type="submit" class="btn btn-primary">Aplicar</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=reportes" class="btn btn-secondary">Resetear</a>
        </div>
    </form>
</div>

<div class="report-kpi-grid" style="margin-bottom: 18px;">
    <div class="report-kpi-card kpi-celula">
        <div class="report-kpi-label">Ganados en célula</div>
        <div class="report-kpi-value"><?= (int)$resumenOrigen['Ganados_Celula'] ?></div>
    </div>
    <div class="report-kpi-card kpi-domingo">
        <div class="report-kpi-label">Ganados en domingo</div>
        <div class="report-kpi-value"><?= (int)$resumenOrigen['Ganados_Domingo'] ?></div>
    </div>
    <div class="report-kpi-card kpi-escalera">
        <div class="report-kpi-label">Total en escalera</div>
        <div class="report-kpi-value"><?= (int)$procesoGanar['Total'] ?></div>
    </div>
    <div class="report-kpi-card kpi-asistencia">
        <div class="report-kpi-label">Promedio asistencia</div>
        <div class="report-kpi-value"><?= $promedioAsistencia ?>%</div>
    </div>
</div>

<div class="card report-card report-metas-card" style="margin-bottom: 22px;">
    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap; margin-bottom:10px;">
        <div>
            <h3 style="margin-bottom:4px;"><?= htmlspecialchars((string)($cumplimientoMetas['titulo'] ?? 'GANAR')) ?></h3>
            <small style="color:#60708a;">Periodo semestral: <?= htmlspecialchars((string)($cumplimientoMetas['inicio'] ?? '')) ?> a <?= htmlspecialchars((string)($cumplimientoMetas['fin'] ?? '')) ?></small>
        </div>
        <a href="<?= PUBLIC_URL ?>?url=ministerios" class="btn btn-secondary btn-sm">Editar metas por ministerio</a>
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

<div class="card report-card" style="margin-bottom: 22px;">
    <h3>Escalera del Éxito (semanal)</h3>
    <div id="chartEscalera"></div>
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

<div class="card report-card" style="margin-bottom: 22px;">
    <h3>Asistencia a células</h3>
    <div id="chartAsistencia"></div>
    <details style="margin-top: 14px;">
        <summary>Ver detalle por célula</summary>
        <div class="table-container" style="margin-top: 10px;">
            <table class="data-table">
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

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
const procesoGanar = <?= json_encode($procesoGanar) ?>;
const almasPorEdades = <?= json_encode($almasPorEdades) ?>;
const almasGanadas = <?= json_encode($almas_ganadas ?? []) ?>;
const asistencia = <?= json_encode($asistencia_celulas ?? []) ?>;

new ApexCharts(document.querySelector('#chartEscalera'), {
    chart: { type: 'bar', height: 320 },
    series: [{
        name: 'Personas',
        data: [
            parseInt(procesoGanar.Ganar || 0, 10),
            parseInt(procesoGanar.Consolidar || 0, 10),
            parseInt(procesoGanar.Discipular || 0, 10),
            parseInt(procesoGanar.Enviar || 0, 10)
        ]
    }],
    xaxis: { categories: ['Ganar', 'Consolidar', 'Discipular', 'Enviar'] },
    colors: ['#3f8efc']
}).render();

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

new ApexCharts(document.querySelector('#chartAsistencia'), {
    chart: { type: 'line', height: 340 },
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
    xaxis: { categories: asistencia.map(x => x.Nombre_Celula || 'Sin célula') },
    colors: ['#f59e0b', '#2563eb']
}).render();
</script>

<style>
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

.report-kpi-card {
    border-radius: 12px;
    padding: 14px;
    color: #10233d;
}

.kpi-celula { background: #eef7ff; border: 1px solid #c7dfff; }
.kpi-domingo { background: #fff8e8; border: 1px solid #ffe2a8; }
.kpi-escalera { background: #eefbf1; border: 1px solid #bfe8c9; }
.kpi-asistencia { background: #f8f2ff; border: 1px solid #ddcbff; }

.report-kpi-label { font-size: .82rem; color: #475569; }
.report-kpi-value { font-size: 1.8rem; font-weight: 800; }

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
    .report-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 640px) {
    .report-kpi-grid { grid-template-columns: 1fr; }
}

</style>

<?php include VIEWS . '/layout/footer.php'; ?>

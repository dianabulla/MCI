<?php include VIEWS . '/layout/header.php'; ?>

<style>
    .report-header {
        background: #ffffff;
        border-radius: 16px;
        padding: 16px 20px;
        box-shadow: 0 6px 20px rgba(11, 58, 138, 0.12);
        border: 1px solid #eef1f6;
        margin-bottom: 20px;
    }
    .report-title {
        margin: 0;
        font-weight: 700;
        color: #0078D4;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .report-title i {
        background: #0078D4;
        color: #fff;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .summary-card,
    .section-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 18px;
        margin-bottom: 20px;
        border: 1px solid #eef1f6;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
    .section-title {
        font-weight: 700;
        color: #0b3a8a;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }
    .kpi-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid #e6edf7;
        border-radius: 14px;
        padding: 14px 16px;
        box-shadow: 0 4px 12px rgba(11, 58, 138, 0.08);
    }
    .kpi-label {
        color: #4d5b74;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 600;
    }
    .kpi-value {
        color: #0b3a8a;
        font-size: 28px;
        font-weight: 800;
        line-height: 1.1;
        margin-top: 6px;
    }
    .kpi-sub {
        font-size: 12px;
        color: #6c7a92;
        margin-top: 4px;
    }
    .table th {
        background: #0078D4;
        color: white;
        font-weight: 600;
        padding: 8px 10px;
        border: none;
        white-space: normal;
        font-size: 12px;
    }
    .table td {
        padding: 8px 10px;
        vertical-align: top;
        white-space: normal;
        word-break: break-word;
        font-size: 12px;
    }
    .table tbody tr:hover {
        background: #f5f9ff;
    }
    .table {
        table-layout: fixed;
        border-radius: 12px;
        overflow: hidden;
    }
    .total-row {
        font-weight: 700;
        background: #f0f4ff;
    }
    .summary-table th,
    .summary-table td {
        text-align: center;
    }
    .summary-table td:first-child,
    .summary-table th:first-child {
        text-align: left;
    }
    .percent-badge {
        display: inline-block;
        border-radius: 999px;
        padding: 4px 10px;
        font-weight: 700;
        font-size: 11px;
        min-width: 52px;
    }
    .percent-good {
        background: #e8f7ee;
        color: #1f8b4c;
    }
    .percent-mid {
        background: #fff6e6;
        color: #a86a00;
    }
    .percent-low {
        background: #fdecef;
        color: #b4233b;
    }
    .sections-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
    }
    .section-collapse {
        border: 1px solid #eef1f6;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }
    .section-collapse > summary {
        list-style: none;
        cursor: pointer;
        padding: 14px 16px;
        background: #f7faff;
        border-bottom: 1px solid #edf2f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .section-collapse > summary::-webkit-details-marker {
        display: none;
    }
    .collapse-title {
        font-weight: 700;
        color: #0b3a8a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .group-link {
        color: #0b4aa2;
        text-decoration: none;
        font-weight: 700;
    }
    .group-link:hover {
        text-decoration: underline;
    }
    .view-group-btn {
        border: 1px solid #cfe0ff;
        background: #edf4ff;
        color: #0b4aa2;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 11px;
        text-decoration: none;
        font-weight: 700;
    }
    .view-group-btn:hover {
        background: #dfeeff;
        color: #083677;
    }
    .collapse-arrow {
        color: #0b4aa2;
        transition: transform 0.2s ease;
        font-size: 14px;
        font-weight: 700;
    }
    .section-collapse[open] .collapse-arrow {
        transform: rotate(90deg);
    }
    .collapse-content {
        padding: 14px 16px 16px;
    }
    .location-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    .location-table th,
    .location-table td {
        font-size: 12px;
        padding: 7px 9px;
    }
    .puesto-name {
        font-weight: 700;
        color: #0b3a8a;
        margin-bottom: 8px;
    }
    .section-meta {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }
    .meta-pill {
        font-size: 11px;
        font-weight: 600;
        color: #0b4aa2;
        background: #edf4ff;
        border: 1px solid #d9e8ff;
        padding: 4px 9px;
        border-radius: 999px;
    }
    @media (max-width: 992px) {
        .summary-table {
            font-size: 11px;
        }
    }
</style>

<?php
function formatPercentValue($value) {
    if ($value === null) {
        return '-';
    }
    return $value . '%';
}

function formatNumberValue($value) {
    if ($value === null) {
        return '-';
    }
    return number_format((int) $value);
}

function percentClass($value) {
    if ($value === null) {
        return 'percent-mid';
    }
    if ($value >= 80) {
        return 'percent-good';
    }
    if ($value >= 50) {
        return 'percent-mid';
    }
    return 'percent-low';
}

function buildNehemiasListaLink($ministerio, $liderNehemias = null) {
    $params = [
        'url' => 'nehemias/lista',
        'lider' => (string)$ministerio
    ];

    if ($liderNehemias !== null && $liderNehemias !== '') {
        $params['lider_nehemias'] = (string)$liderNehemias;
    }

    return '?' . http_build_query($params);
}

$totalMinisterios = count($sections ?? []);
$totalLideres = (int)($summaryTotals['actual_nehemias'] ?? 0);
$totalVotantes = isset($totalVotantesGeneral)
    ? (int)$totalVotantesGeneral
    : (int)($summaryTotals['actual_votantes'] ?? 0);
$promedioVotantesPorMinisterio = $totalMinisterios > 0 ? round($totalVotantes / $totalMinisterios) : 0;
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center report-header">
        <h2 class="report-title">
            <i class="bi bi-graph-up"></i> Nehemias - Reportes
        </h2>
        <a href="?url=nehemias/lista" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver a lista
        </a>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Ministerios</div>
            <div class="kpi-value"><?= number_format($totalMinisterios) ?></div>
            <div class="kpi-sub">Con registros en Nehemias</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Líderes Nehemias</div>
            <div class="kpi-value"><?= number_format($totalLideres) ?></div>
            <div class="kpi-sub">Total líderes reportados</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Votantes</div>
            <div class="kpi-value"><?= number_format($totalVotantes) ?></div>
            <div class="kpi-sub">Registros acumulados</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Promedio por ministerio</div>
            <div class="kpi-value"><?= number_format($promedioVotantesPorMinisterio) ?></div>
            <div class="kpi-sub">Votantes por ministerio</div>
        </div>
    </div>

    <div class="summary-card">
        <div class="section-title">
            <i class="bi bi-bar-chart-line"></i> Resumen por Ministerio
        </div>
        <div class="table-responsive">
            <table class="table summary-table">
                <thead>
                    <tr>
                        <th>MINISTERIO</th>
                        <th>META VOTANTES</th>
                        <th>VOTANTES</th>
                        <th>Faltantes</th>
                        <th>% Meta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summaryRows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['label']) ?></td>
                            <td><?= formatNumberValue($row['meta_votantes']) ?></td>
                            <td><?= formatNumberValue($row['actual_votantes']) ?></td>
                            <td><?= formatNumberValue($row['faltantes_votantes']) ?></td>
                            <td>
                                <span class="percent-badge <?= percentClass($row['porcentaje_votantes']) ?>">
                                    <?= formatPercentValue($row['porcentaje_votantes']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td>TOTAL</td>
                        <td><?= formatNumberValue($summaryTotals['meta_votantes']) ?></td>
                        <td><?= formatNumberValue($summaryTotals['actual_votantes']) ?></td>
                        <td><?= formatNumberValue($summaryTotals['faltantes_votantes']) ?></td>
                        <td>
                            <span class="percent-badge <?= percentClass($summaryTotals['porcentaje_votantes']) ?>">
                                <?= formatPercentValue($summaryTotals['porcentaje_votantes']) ?>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="location-grid">
        <div class="summary-card">
            <div class="section-title">
                <i class="bi bi-table"></i> Distribución por Puesto (Bloque/Zona)
            </div>
            <div class="section-meta">
                <span class="meta-pill">Total registros: <?= number_format((int)($totalRegistrosUbicacion ?? 0)) ?></span>
                <span class="meta-pill">Top puestos mostrados: <?= number_format(count($puestoChartLabels ?? [])) ?></span>
            </div>
            <div class="table-responsive mt-3">
                <table class="table table-hover location-table">
                    <thead>
                        <tr>
                            <th style="width:70px;">Nro</th>
                            <th>Puesto / Zona</th>
                            <th style="width:120px;">Personas</th>
                            <th style="width:90px;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalChart = 0;
                        foreach (($puestoChartValues ?? []) as $v) {
                            $totalChart += (int)$v;
                        }
                        ?>
                        <?php foreach (($puestoChartLabels ?? []) as $idx => $puestoLabel): ?>
                            <?php
                            $cantidadPuesto = (int)(($puestoChartValues ?? [])[$idx] ?? 0);
                            $porcPuesto = $totalChart > 0 ? round(($cantidadPuesto / $totalChart) * 100) : 0;
                            ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><?= htmlspecialchars($puestoLabel) ?></td>
                                <td><?= number_format($cantidadPuesto) ?></td>
                                <td><span class="percent-badge <?= percentClass($porcPuesto) ?>"><?= $porcPuesto ?>%</span></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="2">TOTAL TOP PUESTOS</td>
                            <td><?= number_format($totalChart) ?></td>
                            <td><span class="percent-badge percent-good">100%</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="sections-grid">
        <?php foreach ($sections as $section): ?>
            <details class="section-collapse">
                <summary>
                    <div class="collapse-title">
                        <i class="bi bi-people-fill"></i> <?= htmlspecialchars($section['label']) ?>
                    </div>
                    <div class="section-meta mb-0">
                        <a class="view-group-btn" href="<?= htmlspecialchars(buildNehemiasListaLink($section['label'])) ?>" onclick="event.stopPropagation();">Ver personas</a>
                        <span class="meta-pill">Líderes: <?= number_format(count($section['rows'])) ?></span>
                        <span class="meta-pill">Votantes: <?= number_format($section['total_votantes']) ?></span>
                        <span class="collapse-arrow">▶</span>
                    </div>
                </summary>
                <div class="collapse-content">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">Nro</th>
                                    <th>Líder Nehemias</th>
                                    <th style="width: 110px;">Votantes</th>
                                    <th style="width: 90px;">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($section['rows'] as $row): ?>
                                    <?php $porcentajeLider = $section['total_votantes'] > 0 ? round(($row['votantes'] / $section['total_votantes']) * 100) : 0; ?>
                                    <tr>
                                        <td><?= $row['nro'] ?></td>
                                        <td>
                                            <a class="group-link" href="<?= htmlspecialchars(buildNehemiasListaLink($section['label'], $row['lider'])) ?>">
                                                <?= htmlspecialchars($row['lider']) ?>
                                            </a>
                                        </td>
                                        <td><?= number_format($row['votantes']) ?></td>
                                        <td>
                                            <span class="percent-badge <?= percentClass($porcentajeLider) ?>"><?= $porcentajeLider ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="2">TOTAL</td>
                                    <td><?= number_format($section['total_votantes']) ?></td>
                                    <td><span class="percent-badge percent-good">100%</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>

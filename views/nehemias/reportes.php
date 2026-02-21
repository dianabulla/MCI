<?php include VIEWS . '/layout/header.php'; ?>


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
    <div class="d-flex justify-content-between align-items-center module-header-card report-header">
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
            <div class="table-responsive mt-3 puesto-table-wrap">
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
                    <div class="table-responsive ministerio-table-wrap">
                        <table class="table table-hover ministerio-detail-table">
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

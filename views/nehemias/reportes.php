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
    .sections-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
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
                            <td><?= formatPercentValue($row['porcentaje_votantes']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td>TOTAL</td>
                        <td><?= formatNumberValue($summaryTotals['meta_votantes']) ?></td>
                        <td><?= formatNumberValue($summaryTotals['actual_votantes']) ?></td>
                        <td><?= formatNumberValue($summaryTotals['faltantes_votantes']) ?></td>
                        <td><?= formatPercentValue($summaryTotals['porcentaje_votantes']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="sections-grid">
        <?php foreach ($sections as $section): ?>
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-people-fill"></i> <?= htmlspecialchars($section['label']) ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Nro</th>
                                <th>Líder Nehemias</th>
                                <th style="width: 110px;">Votantes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($section['rows'] as $row): ?>
                                <tr>
                                    <td><?= $row['nro'] ?></td>
                                    <td><?= htmlspecialchars($row['lider']) ?></td>
                                    <td><?= number_format($row['votantes']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="2">TOTAL</td>
                                <td><?= number_format($section['total_votantes']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>

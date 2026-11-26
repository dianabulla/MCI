<?php
include VIEWS . '/layout/header.php';

$celula = is_array($celula ?? null) ? $celula : [];
$resumen = is_array($resumen ?? null) ? $resumen : [];
$semana = is_array($semana ?? null) ? $semana : [];
$ganadosMes = is_array($ganados_mes ?? null) ? $ganados_mes : [];
$semanasGanados = is_array($ganadosMes['semanas'] ?? null) ? $ganadosMes['semanas'] : [];
$integrantes = is_array($integrantes ?? null) ? $integrantes : [];

$idCelula = (int)($celula['Id_Celula'] ?? 0);
$nombreCelula = (string)($celula['Nombre_Celula'] ?? 'Célula');
$totalIntegrantes = (int)($resumen['total_integrantes'] ?? count($integrantes));
$conEncuentro = (int)($resumen['con_encuentro'] ?? 0);
$conBautismo = (int)($resumen['con_bautismo'] ?? 0);
$conCap = (int)($resumen['con_cap_destino'] ?? 0);
$capN1 = (int)($resumen['cap_n1'] ?? 0);
$capN2 = (int)($resumen['cap_n2'] ?? 0);
$capN3 = (int)($resumen['cap_n3'] ?? 0);
$estaCerrada = !empty($resumen['esta_cerrada']);
$totalGanadosMes = (int)($ganadosMes['total_mes'] ?? 0);
$mesGanadosTexto = (string)($ganadosMes['mes_texto'] ?? date('F Y'));
$exportUrl = PUBLIC_URL . 'index.php?url=celulas/reporte-individual/exportar&id=' . $idCelula;
$detalleUrl = PUBLIC_URL . 'index.php?url=celulas/detalle&id=' . $idCelula;
$listaUrl = PUBLIC_URL . 'index.php?url=celulas';
?>

<div class="page-header">
    <h2>Reporte individual · <?= htmlspecialchars($nombreCelula) ?></h2>
    <div class="celulas-actions-row">
        <a href="<?= htmlspecialchars($exportUrl) ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--excel" title="Descargar Excel">
            <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
            <span>Descargar Excel</span>
        </a>
        <a href="<?= htmlspecialchars($detalleUrl) ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon" title="Celugrama" aria-label="Celugrama">
            <i class="bi bi-bar-chart-steps" aria-hidden="true"></i>
        </a>
        <a href="<?= htmlspecialchars($listaUrl) ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon" title="Volver a células" aria-label="Volver">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>
    </div>
</div>

<div class="ri-celula-wrap">
    <div class="ri-celula-meta">
        <div class="ri-celula-meta-item">
            <span class="ri-celula-label">Fecha de apertura</span>
            <span class="ri-celula-value"><?= htmlspecialchars((string)($resumen['fecha_apertura_texto'] ?? 'No especificada')) ?></span>
        </div>
        <div class="ri-celula-meta-item">
            <?php if ($estaCerrada): ?>
                <span class="ri-celula-label">Fecha de cierre</span>
                <span class="ri-celula-value"><?= htmlspecialchars((string)($resumen['fecha_cierre_texto'] ?? 'No registrada')) ?></span>
            <?php else: ?>
                <span class="ri-celula-label">Tiempo abierta</span>
                <span class="ri-celula-value"><?= htmlspecialchars((string)($resumen['tiempo_abierta_texto'] ?? 'No disponible')) ?></span>
            <?php endif; ?>
        </div>
        <div class="ri-celula-meta-item">
            <span class="ri-celula-label">Estado</span>
            <span class="ri-celula-value"><?= htmlspecialchars((string)($celula['Estado_Celula'] ?? 'Activa')) ?></span>
        </div>
        <div class="ri-celula-meta-item">
            <span class="ri-celula-label">Líder</span>
            <span class="ri-celula-value"><?= htmlspecialchars((string)($celula['Nombre_Lider'] ?? 'No especificado')) ?></span>
        </div>
    </div>

    <div class="ri-celula-ganados-mes">
        <div class="ri-celula-ganados-mes-head">
            <h3>Personas ganadas · <?= htmlspecialchars($mesGanadosTexto) ?></h3>
            <p>Ganados por semana (lun–dom) del mes actual.</p>
        </div>
        <?php if ($semanasGanados !== []): ?>
            <div class="ri-celula-ganados-sheet-wrap">
                <table class="ri-celula-ganados-sheet">
                    <thead>
                        <tr>
                            <?php foreach ($semanasGanados as $semanaMes): ?>
                                <?php $esActual = !empty($semanaMes['es_actual']); ?>
                                <th class="<?= $esActual ? 'is-actual' : '' ?>">
                                    <span class="ri-sheet-semana">S<?= (int)($semanaMes['numero'] ?? 0) ?><?= $esActual ? ' · actual' : '' ?></span>
                                    <span class="ri-sheet-rango"><?= htmlspecialchars((string)($semanaMes['rango_texto'] ?? '')) ?></span>
                                </th>
                            <?php endforeach; ?>
                            <th class="is-total">
                                <span class="ri-sheet-semana">Total mes</span>
                                <span class="ri-sheet-rango"><?= htmlspecialchars($mesGanadosTexto) ?></span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach ($semanasGanados as $semanaMes): ?>
                                <?php
                                $esActual = !empty($semanaMes['es_actual']);
                                $totalSem = (int)($semanaMes['total'] ?? 0);
                                ?>
                                <td class="<?= $esActual ? 'is-actual' : '' ?>">
                                    <strong><?= $totalSem ?></strong>
                                </td>
                            <?php endforeach; ?>
                            <td class="is-total"><strong><?= $totalGanadosMes ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="ri-celula-empty" style="padding:12px 0;">No hay semanas para mostrar en este mes.</p>
        <?php endif; ?>
    </div>

    <div class="ri-celula-funnel">
        <h3>Formación de integrantes</h3>
        <p class="ri-celula-funnel-help">
            De <strong><?= $totalIntegrantes ?></strong> integrantes:
            <strong><?= $conEncuentro ?></strong> ya fueron a encuentro;
            de esos <?= $totalIntegrantes ?>, <strong><?= $conBautismo ?></strong> ya se bautizaron;
            de esos <?= $totalIntegrantes ?>, <strong><?= $conCap ?></strong> están en Capacitación Destino
            (Nivel 1: <?= $capN1 ?>, Nivel 2: <?= $capN2 ?>, Nivel 3: <?= $capN3 ?>).
        </p>
        <div class="ri-celula-kpis">
            <div class="ri-celula-kpi">
                <span class="ri-celula-kpi-num"><?= $totalIntegrantes ?></span>
                <span class="ri-celula-kpi-lbl">Integrantes</span>
            </div>
            <div class="ri-celula-kpi">
                <span class="ri-celula-kpi-num"><?= $conEncuentro ?> <small>/ <?= $totalIntegrantes ?></small></span>
                <span class="ri-celula-kpi-lbl">Encuentro</span>
            </div>
            <div class="ri-celula-kpi">
                <span class="ri-celula-kpi-num"><?= $conBautismo ?> <small>/ <?= $totalIntegrantes ?></small></span>
                <span class="ri-celula-kpi-lbl">Bautizados</span>
            </div>
            <div class="ri-celula-kpi">
                <span class="ri-celula-kpi-num"><?= $conCap ?> <small>/ <?= $totalIntegrantes ?></small></span>
                <span class="ri-celula-kpi-lbl">Cap. Destino</span>
            </div>
        </div>
    </div>

    <div class="ri-celula-tabla-block">
        <h3>Detalle por integrante</h3>
        <?php if ($integrantes !== []): ?>
            <div class="ri-celula-table-wrap">
                <table class="ri-celula-table">
                    <colgroup>
                        <col class="ri-col-num">
                        <col class="ri-col-nombre">
                        <col class="ri-col-tiempo">
                        <col class="ri-col-desde">
                        <col class="ri-col-retiro">
                        <col class="ri-col-obs">
                        <col class="ri-col-flag">
                        <col class="ri-col-flag">
                        <col class="ri-col-flag">
                        <col class="ri-col-nivel">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Integrante</th>
                            <th>Tiempo activo</th>
                            <th>Desde</th>
                            <th>Fecha retiro</th>
                            <th>Observación retiro</th>
                            <th>Encuentro</th>
                            <th>Bautizado</th>
                            <th>Cap. Destino</th>
                            <th>Nivel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($integrantes as $idx => $fila): ?>
                            <?php
                            $siEnc = !empty($fila['encuentro']);
                            $siBau = !empty($fila['bautismo']);
                            $siCap = !empty($fila['cap_destino']);
                            $esRetirado = (($fila['estado_en_celula'] ?? '') === 'retirado');
                            ?>
                            <tr class="<?= $esRetirado ? 'is-retirado' : '' ?>">
                                <td class="ri-td-num"><?= $idx + 1 ?></td>
                                <td class="ri-td-nombre">
                                    <?= htmlspecialchars((string)($fila['nombre'] ?? '')) ?>
                                    <?php if ($esRetirado): ?>
                                        <span class="ri-celula-badge-retiro">Retirado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="ri-td-tiempo"><?= htmlspecialchars((string)($fila['tiempo_activo_texto'] ?? 'No disponible')) ?></td>
                                <td class="ri-td-desde"><?= htmlspecialchars((string)($fila['desde_activo_texto'] ?? '—')) ?></td>
                                <td class="ri-td-retiro"><?= htmlspecialchars((string)($fila['fecha_retiro_texto'] ?? '—')) ?></td>
                                <td class="ri-td-obs" title="<?= htmlspecialchars((string)($fila['observacion_retiro'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($fila['observacion_retiro'] ?? '—')) ?>
                                </td>
                                <td class="ri-td-flag">
                                    <span class="ri-celula-flag <?= $siEnc ? 'is-yes' : 'is-no' ?>"><?= $siEnc ? 'Sí' : 'No' ?></span>
                                </td>
                                <td class="ri-td-flag">
                                    <span class="ri-celula-flag <?= $siBau ? 'is-yes' : 'is-no' ?>"><?= $siBau ? 'Sí' : 'No' ?></span>
                                </td>
                                <td class="ri-td-flag">
                                    <span class="ri-celula-flag <?= $siCap ? 'is-yes' : 'is-no' ?>"><?= $siCap ? 'Sí' : 'No' ?></span>
                                </td>
                                <td class="ri-td-nivel"><?= htmlspecialchars((string)($fila['nivel_cap_texto'] ?? '—')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <strong>Totales</strong>
                                · Activos: <?= $totalIntegrantes ?>
                                · Retiros: <?= (int)($resumen['total_retirados'] ?? 0) ?>
                            </td>
                            <td class="ri-td-flag"><strong><?= $conEncuentro ?></strong></td>
                            <td class="ri-td-flag"><strong><?= $conBautismo ?></strong></td>
                            <td class="ri-td-flag"><strong><?= $conCap ?></strong></td>
                            <td class="ri-td-nivel">
                                <strong>N1: <?= $capN1 ?></strong>
                                · N2: <?= $capN2 ?>
                                · N3: <?= $capN3 ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <p class="ri-celula-empty">Esta célula no tiene integrantes activos registrados.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.celulas-actions-row {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    align-items: center;
}
.celulas-action-btn {
    border: 1px solid #d6e1f0;
    background: #f6f9ff;
    color: #2f4f7a;
    font-weight: 600;
}
.celulas-action-btn:hover {
    background: #edf3fc;
    color: #274368;
}
.celulas-action-btn--icon {
    min-width: 32px;
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.celulas-action-btn--excel {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 32px;
    padding: 0 10px;
    background: #ecfdf5;
    border-color: #86efac;
    color: #166534;
}
.celulas-action-btn--excel:hover {
    background: #dcfce7;
    color: #14532d;
}
.ri-celula-wrap {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.ri-celula-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    padding: 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}
.ri-celula-meta-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.ri-celula-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #64748b;
    font-weight: 700;
}
.ri-celula-value {
    font-size: 1rem;
    color: #0f172a;
    font-weight: 700;
}
.ri-celula-ganados-mes {
    padding: 12px 14px 14px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}
.ri-celula-ganados-mes-head h3 {
    margin: 0 0 2px;
    font-size: 0.95rem;
    color: #1e293b;
}
.ri-celula-ganados-mes-head p {
    margin: 0 0 10px;
    color: #64748b;
    font-size: 0.8rem;
}
.ri-celula-ganados-sheet-wrap {
    overflow-x: auto;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
}
.ri-celula-ganados-sheet {
    width: 100%;
    min-width: 560px;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 0.78rem;
    background: #fff;
}
.ri-celula-ganados-sheet th,
.ri-celula-ganados-sheet td {
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    padding: 6px 8px;
    text-align: center;
    vertical-align: middle;
}
.ri-celula-ganados-sheet th:last-child,
.ri-celula-ganados-sheet td:last-child {
    border-right: 0;
}
.ri-celula-ganados-sheet thead th {
    background: #f1f5f9;
    color: #334155;
    font-weight: 700;
}
.ri-celula-ganados-sheet .ri-sheet-semana {
    display: block;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    line-height: 1.2;
}
.ri-celula-ganados-sheet .ri-sheet-rango {
    display: block;
    margin-top: 2px;
    font-size: 0.66rem;
    font-weight: 500;
    color: #64748b;
    line-height: 1.2;
    white-space: nowrap;
}
.ri-celula-ganados-sheet tbody td {
    background: #fff;
    padding: 8px 6px;
}
.ri-celula-ganados-sheet tbody td strong {
    font-size: 1.05rem;
    font-weight: 800;
    color: #1e3a5f;
}
.ri-celula-ganados-sheet th.is-actual,
.ri-celula-ganados-sheet td.is-actual {
    background: #eff6ff;
}
.ri-celula-ganados-sheet th.is-actual {
    color: #1d4ed8;
    box-shadow: inset 0 2px 0 #3b82f6;
}
.ri-celula-ganados-sheet td.is-actual strong {
    color: #1d4ed8;
}
.ri-celula-ganados-sheet th.is-total,
.ri-celula-ganados-sheet td.is-total {
    background: #ecfdf5;
}
.ri-celula-ganados-sheet th.is-total {
    color: #166534;
    box-shadow: inset 0 2px 0 #22c55e;
}
.ri-celula-ganados-sheet td.is-total strong {
    color: #166534;
    font-size: 1.12rem;
}
.ri-celula-funnel,
.ri-celula-tabla-block {
    padding: 16px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}
.ri-celula-funnel h3,
.ri-celula-tabla-block h3 {
    margin: 0 0 10px;
    font-size: 1rem;
    color: #1e293b;
}
.ri-celula-funnel-help {
    margin: 0 0 14px;
    color: #475569;
    font-size: 0.92rem;
    line-height: 1.45;
}
.ri-celula-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}
.ri-celula-kpi {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 10px;
    text-align: center;
    background: #f8fafc;
}
.ri-celula-kpi-num {
    display: block;
    font-size: 1.25rem;
    font-weight: 800;
    color: #1e3a5f;
}
.ri-celula-kpi-num small {
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
}
.ri-celula-kpi-lbl {
    display: block;
    margin-top: 4px;
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 700;
    text-transform: uppercase;
}
.ri-celula-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}
.ri-celula-table {
    width: 100%;
    min-width: 980px;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 0.84rem;
    margin: 0;
    background: #fff;
}
.ri-celula-table col.ri-col-num { width: 36px; }
.ri-celula-table col.ri-col-nombre { width: 20%; }
.ri-celula-table col.ri-col-tiempo { width: 12%; }
.ri-celula-table col.ri-col-desde { width: 90px; }
.ri-celula-table col.ri-col-retiro { width: 90px; }
.ri-celula-table col.ri-col-obs { width: 22%; }
.ri-celula-table col.ri-col-flag { width: 78px; }
.ri-celula-table col.ri-col-nivel { width: 90px; }
.ri-celula-table thead th {
    background: #eef2ff;
    color: #3730a3;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: 8px 10px;
    border-bottom: 1px solid #c7d2fe;
    vertical-align: middle;
}
.ri-celula-table tbody td,
.ri-celula-table tfoot td {
    padding: 6px 10px;
    border-bottom: 1px solid #eef2f7;
    vertical-align: middle;
    line-height: 1.25;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: normal;
    overflow-wrap: normal;
}
.ri-celula-table tbody tr:nth-child(even) {
    background: #f8fafc;
}
.ri-celula-table tbody tr:hover {
    background: #f1f5f9;
}
.ri-celula-table tbody tr.is-retirado {
    background: #fff7ed;
}
.ri-celula-table tbody tr.is-retirado:nth-child(even) {
    background: #ffedd5;
}
.ri-celula-table .ri-td-num {
    text-align: center;
    color: #64748b;
    font-weight: 600;
}
.ri-celula-table .ri-td-nombre {
    white-space: normal;
    font-weight: 600;
    overflow: visible;
    text-overflow: unset;
}
.ri-celula-table .ri-td-obs {
    white-space: normal;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #9a3412;
    font-size: 0.78rem;
    line-height: 1.3;
}
.ri-celula-badge-retiro {
    display: inline-block;
    margin-left: 6px;
    padding: 1px 6px;
    border-radius: 999px;
    background: #fed7aa;
    color: #9a3412;
    font-size: 0.65rem;
    font-weight: 700;
    vertical-align: middle;
}
.ri-celula-table .ri-td-flag,
.ri-celula-table thead th:nth-child(n+7),
.ri-celula-table .ri-td-nivel,
.ri-celula-table thead th:nth-child(10) {
    text-align: center;
}
.ri-celula-table tfoot td {
    background: #f8fafc;
    border-top: 2px solid #cbd5e1;
    border-bottom: 0;
    font-size: 0.8rem;
    padding: 8px 10px;
    white-space: normal;
}
.ri-celula-flag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 22px;
    padding: 0 8px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.75rem;
    line-height: 1;
}
.ri-celula-flag.is-yes {
    background: #dcfce7;
    color: #166534;
}
.ri-celula-flag.is-no {
    background: #f1f5f9;
    color: #64748b;
}
.ri-celula-empty {
    margin: 0;
    padding: 18px;
    text-align: center;
    color: #64748b;
}
@media (max-width: 760px) {
    .ri-celula-kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>

<?php include VIEWS . '/layout/header.php'; ?>
<?php
$puedeVerPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'ver');

$subprocesosPorEtapa = [
    'Ganar' => ['Primer contacto', 'Asignacion a lideres y ministerio', 'Fonovisita', 'Visita', 'Asignacion a una celula', 'No se dispone'],
    'Consolidar' => ['Universidad de la vida', 'Encuentro', 'Bautismo'],
    'Discipular' => ['Capacitacion destino nivel 1 (modulos 1 y 2)', 'Capacitacion destino nivel 2 (modulos 3 y 4)', 'Capacitacion destino nivel 3 (modulos 5 y 6)'],
    'Enviar' => ['Celula']
];

$subprocesosCodigoPorEtapa = [
    'Ganar' => ['PC', 'ALM', 'FO', 'VI', 'AC', 'ND'],
    'Consolidar' => ['UV', 'EN', 'BA'],
    'Discipular' => ['N1', 'N2', 'N3'],
    'Enviar' => ['CE']
];

$ordenEtapas = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
$indiceEtapa = array_flip($ordenEtapas);

$etapaFiltroActual = (string)($filtroEtapaActual ?? '');
$mapaFiltroAEtapa = [
    'ganar' => 'Ganar',
    'consolidar' => 'Consolidar',
    'discipular' => 'Discipular',
    'enviar' => 'Enviar'
];

$etapasVisibles = $ordenEtapas;
if (isset($mapaFiltroAEtapa[$etapaFiltroActual])) {
    $etapasVisibles = [$mapaFiltroAEtapa[$etapaFiltroActual]];
}

$puedeEditarChecklist = !empty($puedeEditarChecklistEscalera);
$puedeMarcarPrimerContacto = !empty($puedeMarcarPrimerContactoGanar);
$totalColumnasChecklist = 0;
foreach ($etapasVisibles as $etapaVisibleTmp) {
    $totalColumnasChecklist += count($subprocesosPorEtapa[$etapaVisibleTmp] ?? []);
}

$indicesChecklistPorEtapa = [
    'Ganar' => [0, 1, 2, 3, 4, 5],
    'Consolidar' => [0, 1, 2],
    'Discipular' => [0, 1, 2],
    'Enviar' => [2],
];

$reporteMes = $reporteEscaleraMesActual ?? [
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
        'Ganar' => ['Primer contacto' => 0, 'Asignacion a lideres y ministerio' => 0, 'Fonovisita' => 0, 'Visita' => 0, 'Asignacion a una celula' => 0, 'No se dispone' => 0],
        'Consolidar' => ['Universidad de la vida' => 0, 'Encuentro' => 0, 'Bautismo' => 0],
        'Discipular' => ['Capacitacion destino nivel 1 (modulos 1 y 2)' => 0, 'Capacitacion destino nivel 2 (modulos 3 y 4)' => 0, 'Capacitacion destino nivel 3 (modulos 5 y 6)' => 0],
        'Enviar' => ['Celula' => 0],
    ],
];
?>

<div class="page-header">
    <h2>Escalera del Éxito</h2>
    <div class="page-actions personas-mobile-stack">
        <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-nav-pill">Personas</a>
        <a href="<?= PUBLIC_URL ?>?url=personas/ganar" class="btn btn-nav-pill">Almas ganadas</a>
        <a href="<?= PUBLIC_URL ?>?url=personas/escalera" class="btn btn-nav-pill active">Escalera del Éxito</a>
    </div>
</div>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="padding: 12px 16px;">
        <div class="escalera-resumen-grid">
            <a href="<?= PUBLIC_URL ?>?url=personas/escalera&etapa=ganar" class="escalera-resumen-item resumen-ganar <?= ($etapaFiltroActual === 'ganar') ? 'active' : '' ?>">
                <span class="escalera-resumen-label">Ganar</span>
                <strong class="escalera-resumen-value"><?= (int)($totalesEtapa['Ganar'] ?? 0) ?></strong>
            </a>
            <a href="<?= PUBLIC_URL ?>?url=personas/escalera&etapa=consolidar" class="escalera-resumen-item resumen-consolidar <?= ($etapaFiltroActual === 'consolidar') ? 'active' : '' ?>">
                <span class="escalera-resumen-label">Consolidar</span>
                <strong class="escalera-resumen-value"><?= (int)($totalesEtapa['Consolidar'] ?? 0) ?></strong>
            </a>
            <a href="<?= PUBLIC_URL ?>?url=personas/escalera&etapa=discipular" class="escalera-resumen-item resumen-discipular <?= ($etapaFiltroActual === 'discipular') ? 'active' : '' ?>">
                <span class="escalera-resumen-label">Discipular</span>
                <strong class="escalera-resumen-value"><?= (int)($totalesEtapa['Discipular'] ?? 0) ?></strong>
            </a>
            <a href="<?= PUBLIC_URL ?>?url=personas/escalera&etapa=enviar" class="escalera-resumen-item resumen-enviar <?= ($etapaFiltroActual === 'enviar') ? 'active' : '' ?>">
                <span class="escalera-resumen-label">Enviar</span>
                <strong class="escalera-resumen-value"><?= (int)($totalesEtapa['Enviar'] ?? 0) ?></strong>
            </a>
            <a href="<?= PUBLIC_URL ?>?url=personas/escalera&etapa=sin_etapa" class="escalera-resumen-item resumen-sin-etapa <?= ($etapaFiltroActual === 'sin_etapa') ? 'active' : '' ?>">
                <span class="escalera-resumen-label">Sin etapa</span>
                <strong class="escalera-resumen-value"><?= (int)($totalesEtapa['sin_etapa'] ?? 0) ?></strong>
            </a>
            <a href="<?= PUBLIC_URL ?>?url=personas/escalera" class="escalera-resumen-item resumen-todas <?= ($etapaFiltroActual === '') ? 'active' : '' ?>">
                <span class="escalera-resumen-label">Todas</span>
                <strong class="escalera-resumen-value"><?= (int)(($totalesEtapa['Ganar'] ?? 0) + ($totalesEtapa['Consolidar'] ?? 0) + ($totalesEtapa['Discipular'] ?? 0) + ($totalesEtapa['Enviar'] ?? 0) + ($totalesEtapa['sin_etapa'] ?? 0)) ?></strong>
            </a>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="padding: 14px 16px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
            <div>
                <h3 style="margin:0 0 4px 0;">Reporte mensual de la Escalera del Éxito</h3>
                <small style="color:#60708a;">
                    Rango aplicado: <?= htmlspecialchars((string)$reporteMes['inicio']) ?> a <?= htmlspecialchars((string)$reporteMes['fin']) ?>
                </small>
            </div>
            <div class="mes-total-pill">
                Total personas del mes: <strong><?= (int)($reporteMes['total_personas_mes'] ?? 0) ?></strong>
            </div>
        </div>

        <div class="escalera-resumen-grid" style="margin-bottom:14px;">
            <div class="escalera-resumen-item resumen-ganar">
                <span class="escalera-resumen-label">Ganar</span>
                <strong class="escalera-resumen-value"><?= (int)($reporteMes['totales_etapa']['Ganar'] ?? 0) ?></strong>
            </div>
            <div class="escalera-resumen-item resumen-consolidar">
                <span class="escalera-resumen-label">Consolidar</span>
                <strong class="escalera-resumen-value"><?= (int)($reporteMes['totales_etapa']['Consolidar'] ?? 0) ?></strong>
            </div>
            <div class="escalera-resumen-item resumen-discipular">
                <span class="escalera-resumen-label">Discipular</span>
                <strong class="escalera-resumen-value"><?= (int)($reporteMes['totales_etapa']['Discipular'] ?? 0) ?></strong>
            </div>
            <div class="escalera-resumen-item resumen-enviar">
                <span class="escalera-resumen-label">Enviar</span>
                <strong class="escalera-resumen-value"><?= (int)($reporteMes['totales_etapa']['Enviar'] ?? 0) ?></strong>
            </div>
            <div class="escalera-resumen-item resumen-sin-etapa">
                <span class="escalera-resumen-label">Sin etapa</span>
                <strong class="escalera-resumen-value"><?= (int)($reporteMes['totales_etapa']['sin_etapa'] ?? 0) ?></strong>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table escalera-reporte-mensual-table">
                <thead>
                    <tr>
                        <th>Etapa</th>
                        <th>Peldaño</th>
                        <th style="width:120px;">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($reporteMes['peldaños'] ?? []) as $etapa => $peldaños): ?>
                        <?php $primeraFila = true; ?>
                        <?php foreach ($peldaños as $nombrePeldaño => $cantidadPeldaño): ?>
                            <tr>
                                <?php if ($primeraFila): ?>
                                    <td rowspan="<?= count($peldaños) ?>" class="stage-group-cell stage-group-<?= strtolower($etapa) ?>">
                                        <strong><?= htmlspecialchars($etapa) ?></strong>
                                    </td>
                                    <?php $primeraFila = false; ?>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($nombrePeldaño) ?></td>
                                <td><strong><?= (int)$cantidadPeldaño ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="table-container">
    <table class="data-table escalera-matrix-table">
        <thead>
            <tr>
                <th rowspan="2">Nombre</th>
                <th rowspan="2">Líder</th>
                <?php foreach ($etapasVisibles as $etapaNombre): ?>
                    <th colspan="3" class="stage-head stage-head-<?= strtolower($etapaNombre) ?>"><?= htmlspecialchars($etapaNombre) ?></th>
                <?php endforeach; ?>
            </tr>
            <tr class="subproceso-head-row">
                <?php foreach ($etapasVisibles as $etapaNombre): ?>
                    <?php $subprocesos = $subprocesosPorEtapa[$etapaNombre] ?? []; ?>
                    <?php foreach ($subprocesos as $indiceSub => $subproceso): ?>
                        <th class="subcol subcol-<?= strtolower($etapaNombre) ?>" title="<?= htmlspecialchars($subproceso) ?>">
                            <span class="subproceso-label"><?= htmlspecialchars($subproceso) ?></span>
                        </th>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($personas)): ?>
                <?php foreach ($personas as $persona): ?>
                    <?php
                    $etapaActual = trim((string)($persona['Proceso'] ?? ''));
                    $indiceActual = $indiceEtapa[$etapaActual] ?? -1;
                    $etapaOperativa = $indiceActual >= 0 ? $etapaActual : 'Ganar';
                    $checklistRaw = (string)($persona['Escalera_Checklist'] ?? '');
                    $checklistPersona = [];
                    if ($checklistRaw !== '') {
                        $checklistDecodificado = json_decode($checklistRaw, true);
                        if (is_array($checklistDecodificado)) {
                            $checklistPersona = $checklistDecodificado;
                        }
                    }
                    ?>
                    <tr data-persona-id="<?= (int)($persona['Id_Persona'] ?? 0) ?>" data-proceso-actual="<?= htmlspecialchars($etapaOperativa) ?>">
                        <td><?= htmlspecialchars(trim(($persona['Nombre'] ?? '') . ' ' . ($persona['Apellido'] ?? ''))) ?></td>
                        <td><?= htmlspecialchars(trim((string)($persona['Nombre_Lider'] ?? '')) ?: 'Sin líder') ?></td>
                        <?php foreach ($etapasVisibles as $etapaNombre): ?>
                            <?php $subprocesos = $subprocesosPorEtapa[$etapaNombre] ?? []; ?>
                            <?php
                            $indiceBloque = $indiceEtapa[$etapaNombre];
                            $bloqueCompletado = $indiceActual > $indiceBloque;
                            $bloqueActivo = $indiceActual === $indiceBloque;
                            $checklistEtapa = $checklistPersona[$etapaNombre] ?? [];
                            ?>
                            <?php foreach ($subprocesos as $indiceSub => $subproceso): ?>
                                <?php
                                $indiceRealChecklist = (int)($indicesChecklistPorEtapa[$etapaNombre][$indiceSub] ?? $indiceSub);
                                $checkPersistido = array_key_exists($indiceRealChecklist, $checklistEtapa) ? !empty($checklistEtapa[$indiceRealChecklist]) : null;
                                $checked = $checkPersistido !== null ? $checkPersistido : ($bloqueCompletado || ($bloqueActivo && $indiceSub === 0));
                                $puedeEditarCelda = $puedeEditarChecklist && ($etapaNombre === $etapaOperativa);
                                if ($etapaNombre === 'Ganar' && $indiceRealChecklist === 0 && !$puedeMarcarPrimerContacto) {
                                    $puedeEditarCelda = false;
                                }
                                if ($etapaNombre === 'Ganar' && $indiceRealChecklist === 4) {
                                    $puedeEditarCelda = false;
                                }
                                ?>
                                <td class="check-cell <?= $bloqueCompletado ? 'cell-completado' : '' ?> <?= $bloqueActivo ? 'cell-activo' : '' ?>">
                                    <input
                                        type="checkbox"
                                        class="escalera-check-toggle"
                                        data-id-persona="<?= (int)($persona['Id_Persona'] ?? 0) ?>"
                                        data-etapa="<?= htmlspecialchars($etapaNombre) ?>"
                                        data-indice="<?= (int)$indiceRealChecklist ?>"
                                        <?= $checked ? 'checked' : '' ?>
                                        <?= $puedeEditarCelda ? '' : 'disabled' ?>
                                    >
                                </td>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= 2 + $totalColumnasChecklist ?>" class="text-center">No hay personas registradas para esta etapa</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card" style="margin-top: 10px; margin-bottom: 16px;">
    <div class="card-body" style="padding: 10px 14px;">
        <div class="subproceso-legend-grid">
            <div><strong>Ganar:</strong> PC Primer contacto | ALM Asignacion a lideres y ministerio | FO Fonovisita | VI Visita | AC Asignacion a una celula | ND No se dispone</div>
            <div><strong>Consolidar:</strong> UV Universidad de la vida | EN Encuentro | BA Bautismo</div>
            <div><strong>Discipular:</strong> N1 Capacitacion destino nivel 1 (modulos 1 y 2) | N2 Capacitacion destino nivel 2 (modulos 3 y 4) | N3 Capacitacion destino nivel 3 (modulos 5 y 6)</div>
            <div><strong>Enviar:</strong> CE Celula</div>
        </div>
    </div>
</div>

<style>
.escalera-matrix-table th,
.escalera-matrix-table td {
    white-space: normal;
}

.escalera-matrix-table {
    table-layout: fixed;
    width: 100%;
    min-width: 1080px;
}

.escalera-matrix-table thead tr:first-child th {
    text-align: center;
    padding: 6px 6px;
    line-height: 1.1;
}

.stage-head {
    font-weight: 700;
    font-size: 18px;
}

.stage-head-ganar {
    background: #fff7dd;
    color: #8a6500;
}

.stage-head-consolidar {
    background: #eaf9ee;
    color: #187a35;
}

.stage-head-discipular {
    background: #eef5ff;
    color: #1e73be;
}

.stage-head-enviar {
    background: #fff0f6;
    color: #c2185b;
}

.subproceso-head-row th {
    text-align: center;
    font-size: 11px;
    line-height: 1.2;
    padding: 6px 4px;
}

.subcol-ganar {
    background: #fffbeb;
    color: #7c5d00;
}

.subcol-consolidar {
    background: #f2fbf4;
    color: #1c7f3b;
}

.subcol-discipular {
    background: #f5f9ff;
    color: #225fa5;
}

.subcol-enviar {
    background: #fff4f8;
    color: #b11c57;
}

.subproceso-label {
    display: block;
    white-space: normal;
}

.check-cell {
    text-align: center;
    vertical-align: middle;
    width: 36px;
}

.check-cell input {
    width: 12px;
    height: 12px;
}

.check-cell input:enabled {
    cursor: pointer;
}

.cell-completado {
    background: #edf8ef;
}

.cell-activo {
    background: #eef5ff;
}

.escalera-resumen-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 10px;
}

.escalera-resumen-item {
    border: 1px solid #d8e2f1;
    border-radius: 10px;
    background: #f8fbff;
    padding: 10px 12px;
    text-decoration: none;
}

.escalera-resumen-item.active {
    box-shadow: inset 0 0 0 2px rgba(30, 73, 138, 0.2);
    background: #edf4ff;
}

.escalera-resumen-label {
    display: block;
    font-size: 12px;
    color: #5b6b84;
    margin-bottom: 2px;
}

.escalera-resumen-value {
    font-size: 22px;
    color: #244a84;
}

.resumen-ganar {
    border-color: #f1c40f;
}

.resumen-todas {
    border-color: #9fb3ce;
}

.resumen-consolidar {
    border-color: #44c767;
}

.resumen-discipular {
    border-color: #3fa0ff;
}

.resumen-enviar {
    border-color: #e91e63;
}

.resumen-sin-etapa {
    border-color: #cfd8e6;
}

.subproceso-legend-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 6px;
    font-size: 12px;
    color: #4e607b;
}

.escalera-reporte-mensual-table td,
.escalera-reporte-mensual-table th {
    vertical-align: middle;
}

.stage-group-cell {
    font-weight: 700;
    text-align: center;
}

.stage-group-ganar {
    background: #fff9e7;
    color: #8a6500;
}

.stage-group-consolidar {
    background: #eefaf1;
    color: #1c7f3b;
}

.stage-group-discipular {
    background: #f1f7ff;
    color: #225fa5;
}

.stage-group-enviar {
    background: #fff1f7;
    color: #b11c57;
}

.mes-total-pill {
    background: #f4f7fb;
    border: 1px solid #d7e0ee;
    padding: 8px 12px;
    border-radius: 10px;
    color: #314766;
}

@media (max-width: 900px) {
    .escalera-matrix-table th:nth-child(1),
    .escalera-matrix-table td:nth-child(1),
    .escalera-matrix-table th:nth-child(2),
    .escalera-matrix-table td:nth-child(2) {
        width: 150px;
    }
}
</style>

<?php if ($puedeEditarChecklist): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.escalera-check-toggle');
    if (!checkboxes.length) {
        return;
    }

    const endpoint = '<?= PUBLIC_URL ?>?url=personas/actualizarChecklistEscalera';

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', async function () {
            const idPersona = parseInt(this.getAttribute('data-id-persona') || '0', 10);
            const etapa = this.getAttribute('data-etapa') || '';
            const indice = parseInt(this.getAttribute('data-indice') || '-1', 10);
            const marcado = this.checked;

            if (!idPersona || !etapa || indice < 0) {
                this.checked = !marcado;
                alert('No se pudo actualizar este subproceso.');
                return;
            }

            this.disabled = true;

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        id_persona: idPersona,
                        etapa: etapa,
                        indice: indice,
                        marcado: marcado ? 1 : 0
                    })
                });

                const data = await response.json();
                if (!response.ok || !data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Error al guardar el checklist');
                }

                window.location.reload();
            } catch (error) {
                this.checked = !marcado;
                this.disabled = false;
                alert(error.message || 'No se pudo guardar. Intenta de nuevo.');
            }
        });
    });
});
</script>
<?php endif; ?>

<?php include VIEWS . '/layout/footer.php'; ?>


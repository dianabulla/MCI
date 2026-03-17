<?php include VIEWS . '/layout/header.php'; ?>
<?php
$puedeVerPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'ver');

$subprocesosPorEtapa = [
    'Ganar' => ['Primer contacto', 'Ubicado en celula', 'No se dispone'],
    'Consolidar' => ['Universidad de la vida', 'Encuentro', 'Bautismo'],
    'Discipular' => ['Proyeccion', 'Equipo G12', 'Capacitacion destino nivel 1'],
    'Enviar' => ['Capacitacion destino nivel 2', 'Capacitacion destino nivel 3', 'Celula']
];

$subprocesosCodigoPorEtapa = [
    'Ganar' => ['PC', 'UC', 'ND'],
    'Consolidar' => ['UV', 'EN', 'BA'],
    'Discipular' => ['PR', 'G12', 'N1'],
    'Enviar' => ['N2', 'N3', 'CE']
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
$totalColumnasChecklist = count($etapasVisibles) * 3;
?>

<div class="page-header">
    <h2>Escalera del Éxito</h2>
    <div class="page-actions personas-mobile-stack">
        <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-nav-pill">Personas</a>
        <a href="<?= PUBLIC_URL ?>?url=personas/ganar" class="btn btn-nav-pill">Pendiente por consolidar</a>
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
                        <td><?= htmlspecialchars($persona['Nombre_Lider'] ?? 'Sin líder') ?></td>
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
                                $checkPersistido = array_key_exists($indiceSub, $checklistEtapa) ? !empty($checklistEtapa[$indiceSub]) : null;
                                $checked = $checkPersistido !== null ? $checkPersistido : ($bloqueCompletado || ($bloqueActivo && $indiceSub === 0));
                                $puedeEditarCelda = $puedeEditarChecklist && ($etapaNombre === $etapaOperativa);
                                ?>
                                <td class="check-cell <?= $bloqueCompletado ? 'cell-completado' : '' ?> <?= $bloqueActivo ? 'cell-activo' : '' ?>">
                                    <input
                                        type="checkbox"
                                        class="escalera-check-toggle"
                                        data-id-persona="<?= (int)($persona['Id_Persona'] ?? 0) ?>"
                                        data-etapa="<?= htmlspecialchars($etapaNombre) ?>"
                                        data-indice="<?= (int)$indiceSub ?>"
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
            <div><strong>Ganar:</strong> PC Primer contacto | UC Ubicado en celula | ND No se dispone</div>
            <div><strong>Consolidar:</strong> UV Universidad de la vida | EN Encuentro | BA Bautismo</div>
            <div><strong>Discipular:</strong> PR Proyeccion | G12 Equipo G12 | N1 Capacitacion destino nivel 1</div>
            <div><strong>Enviar:</strong> N2 Capacitacion destino nivel 2 | N3 Capacitacion destino nivel 3 | CE Celula</div>
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
    color: #176636;
}

.stage-head-discipular {
    background: #ecf5ff;
    color: #1e65b6;
}

.stage-head-enviar {
    background: #ffeaf2;
    color: #a30f43;
}

.escalera-matrix-table thead tr:first-child th:first-child,
.escalera-matrix-table thead tr:first-child th:nth-child(2) {
    text-align: left;
}

.subproceso-head-row th {
    font-size: 10px;
    font-weight: 700;
    line-height: 1.2;
    text-align: center;
    padding: 4px 4px;
}

.subproceso-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.2;
}

.subcol-ganar .subproceso-label {
    color: #8a6500;
}

.subcol-consolidar .subproceso-label {
    color: #176636;
}

.subcol-discipular .subproceso-label {
    color: #1e65b6;
}

.subcol-enviar .subproceso-label {
    color: #a30f43;
}

.escalera-matrix-table th:nth-child(1),
.escalera-matrix-table td:nth-child(1) {
    width: 180px;
}

.escalera-matrix-table th:nth-child(2),
.escalera-matrix-table td:nth-child(2) {
    width: 190px;
}

.escalera-matrix-table td:nth-child(1),
.escalera-matrix-table td:nth-child(2) {
    font-size: 13px;
    font-weight: 600;
    line-height: 1.3;
    word-break: break-word;
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

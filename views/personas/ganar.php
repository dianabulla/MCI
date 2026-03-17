<?php include VIEWS . '/layout/header.php'; ?>
<?php
$puedeVerPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'ver');
$puedeEditarPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'editar');
$mostrarAcciones = $puedeVerPersona || $puedeEditarPersona;
?>

<div class="page-header">
    <h2>Pendiente por consolidar</h2>
    <div class="page-actions personas-mobile-stack">
        <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-nav-pill">Personas</a>
        <a href="<?= PUBLIC_URL ?>?url=personas/ganar" class="btn btn-nav-pill active">Pendiente por consolidar</a>
        <a href="<?= PUBLIC_URL ?>?url=personas/exportarExcel&modo=ganar" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
        <?php if (AuthController::tienePermiso('personas', 'crear')): ?>
        <a href="<?= PUBLIC_URL ?>?url=personas/crear" class="btn btn-primary">+ Nueva Persona</a>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-info" style="margin-bottom: 20px;">
    <i class="bi bi-info-circle"></i>
    Este apartado muestra personas nuevas en seguimiento de consolidación y separa una lista de <strong>No se dispone</strong> para casos no concretados.
</div>

<div class="card" style="margin-bottom: 20px;">
    <div class="card-body">
        <div class="ganar-shortcuts">
            <div class="ganar-shortcut-item">
                <a href="<?= PUBLIC_URL ?>?url=personas/ganar&origen=celula" class="ganar-shortcut-card <?= (($filtroOrigenActual ?? ($_GET['origen'] ?? '')) === 'celula') ? 'active' : '' ?>">
                    <span class="ganar-shortcut-title"><i class="bi bi-house-heart"></i> Ganados en célula</span>
                    <span class="ganar-shortcut-count"><?= (int)($totalesOrigenPendiente['celula'] ?? 0) ?></span>
                </a>
                <small class="ganar-shortcut-help">Ganado en: Célula</small>
            </div>

            <div class="ganar-shortcut-item">
                <a href="<?= PUBLIC_URL ?>?url=personas/ganar&origen=domingo" class="ganar-shortcut-card <?= (($filtroOrigenActual ?? ($_GET['origen'] ?? '')) === 'domingo') ? 'active' : '' ?>">
                    <span class="ganar-shortcut-title"><i class="bi bi-building"></i> Ganados en iglesia</span>
                    <span class="ganar-shortcut-count"><?= (int)($totalesOrigenPendiente['domingo'] ?? 0) ?></span>
                </a>
                <small class="ganar-shortcut-help">Domingo + Invitado por con dato</small>
            </div>

            <div class="ganar-shortcut-item">
                <a href="<?= PUBLIC_URL ?>?url=personas/ganar&origen=asignados" class="ganar-shortcut-card <?= (($filtroOrigenActual ?? ($_GET['origen'] ?? '')) === 'asignados') ? 'active' : '' ?>">
                    <span class="ganar-shortcut-title"><i class="bi bi-person-check"></i> Asignados</span>
                    <span class="ganar-shortcut-count"><?= (int)($totalesOrigenPendiente['asignados'] ?? 0) ?></span>
                </a>
                <small class="ganar-shortcut-help">Domingo + Invitado por vacío</small>
            </div>

            <div class="ganar-shortcut-item">
                <a href="<?= PUBLIC_URL ?>?url=personas/ganar&origen=no_disponible" class="ganar-shortcut-card <?= (($filtroOrigenActual ?? ($_GET['origen'] ?? '')) === 'no_disponible') ? 'active' : '' ?>">
                    <span class="ganar-shortcut-title"><i class="bi bi-person-dash"></i> No se dispone</span>
                    <span class="ganar-shortcut-count"><?= (int)($totalesOrigenPendiente['no_disponible'] ?? 0) ?></span>
                </a>
                <small class="ganar-shortcut-help">Personas no concretadas (inactivas)</small>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-success" style="margin-bottom: 20px;">
    <i class="bi bi-check-circle"></i>
    Total pendiente por consolidar: <strong><?= count($personas) ?></strong> persona(s)
</div>

<div class="table-container">
    <table class="data-table ganar-table mobile-persona-accordion">
        <thead>
            <tr>
                <th>Nombre Completo</th>
                <th>Cédula</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Célula</th>
                <th>Líder</th>
                <th>Ministerio</th>
                <th>Ganado en</th>
                <th>Fecha Registro</th>
                <?php if ($mostrarAcciones): ?><th class="action-col">Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($personas)): ?>
                <?php foreach ($personas as $persona): ?>
                    <tr>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) ?>">
                                <?= htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) ?>
                            </span>
                            <?php if (!empty($persona['Seguimiento_Observacion']) && (($filtroOrigenActual ?? '') === 'no_disponible')): ?>
                            <div class="no-disponible-note" title="<?= htmlspecialchars($persona['Seguimiento_Observacion']) ?>">
                                Obs: <?= htmlspecialchars($persona['Seguimiento_Observacion']) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($persona['Numero_Documento'] ?? '') ?></td>
                        <td><?= htmlspecialchars($persona['Telefono'] ?? '') ?></td>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($persona['Email'] ?? '') ?>">
                                <?= htmlspecialchars($persona['Email'] ?? '') ?>
                            </span>
                        </td>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($persona['Nombre_Celula'] ?? 'Sin célula') ?>">
                                <?= htmlspecialchars($persona['Nombre_Celula'] ?? 'Sin célula') ?>
                            </span>
                        </td>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($persona['Nombre_Lider'] ?? 'Sin líder') ?>">
                                <?= htmlspecialchars($persona['Nombre_Lider'] ?? 'Sin líder') ?>
                            </span>
                        </td>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($persona['Nombre_Ministerio'] ?? 'Sin ministerio') ?>">
                                <?= htmlspecialchars($persona['Nombre_Ministerio'] ?? 'Sin ministerio') ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $ganadoEn = trim((string)($persona['Tipo_Reunion'] ?? ''));
                            if ($ganadoEn === '') {
                                $ganadoEn = 'Sin dato';
                            }
                            ?>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($ganadoEn) ?>">
                                <?= htmlspecialchars($ganadoEn) ?>
                            </span>
                        </td>
                        <td><?= !empty($persona['Fecha_Registro']) ? date('d/m/Y H:i', strtotime($persona['Fecha_Registro'])) : '' ?></td>
                        <?php if ($mostrarAcciones): ?>
                        <td class="action-col">
                            <div class="action-buttons action-buttons-compact">
                            <?php if (AuthController::tienePermiso('personas', 'ver')): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= $persona['Id_Persona'] ?>" class="action-icon-btn action-icon-info" title="Ver" aria-label="Ver">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button
                                type="button"
                                class="action-icon-btn action-icon-escalera js-escalera-btn"
                                title="Escalera del Exito"
                                aria-label="Escalera del Exito"
                                data-persona-id="<?= (int)($persona['Id_Persona'] ?? 0) ?>"
                                data-persona-asignado="<?= (!empty($persona['Id_Lider']) && !empty($persona['Id_Ministerio']) && !empty($persona['Id_Celula'])) ? '1' : '0' ?>"
                                data-persona-nombre="<?= htmlspecialchars(trim(($persona['Nombre'] ?? '') . ' ' . ($persona['Apellido'] ?? ''))) ?>"
                                data-persona-proceso="<?= htmlspecialchars((string)($persona['Proceso'] ?? '')) ?>"
                                data-persona-checklist='<?= htmlspecialchars((string)($persona['Escalera_Checklist'] ?? ''), ENT_QUOTES, 'UTF-8') ?>'
                            >
                                <i class="bi bi-bar-chart-steps"></i>
                            </button>
                            <?php endif; ?>
                            <?php if (AuthController::tienePermiso('personas', 'editar')): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= $persona['Id_Persona'] ?>" class="action-icon-btn action-icon-warning" title="Editar" aria-label="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $mostrarAcciones ? '10' : '9' ?>" class="text-center">No hay personas pendientes por consolidar</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.proceso-tag {
    display: inline-block;
    font-weight: 700;
    padding-bottom: 2px;
    border-bottom: 3px solid transparent;
}

.proceso-ganar {
    color: #d8b100;
    border-bottom-color: #f1c40f;
}

.proceso-consolidar {
    color: #2e8f3e;
    border-bottom-color: #44c767;
}

.proceso-discipular {
    color: #1e73be;
    border-bottom-color: #3fa0ff;
}

.proceso-enviar {
    color: #c2185b;
    border-bottom-color: #e91e63;
}

.action-buttons-compact {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
}

.ganar-table th.action-col,
.ganar-table td.action-col {
    white-space: nowrap;
    min-width: 126px;
}

.ganar-table .action-buttons.action-buttons-compact {
    flex-wrap: nowrap !important;
    justify-content: flex-start;
}

.ganar-table .action-buttons.action-buttons-compact .action-icon-btn {
    flex: 0 0 auto;
}

.action-icon-btn {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: 1px solid transparent;
    font-size: 14px;
}

.action-icon-info {
    background: #e6f2ff;
    color: #0d6efd;
    border-color: #b7d7ff;
}

.action-icon-warning {
    background: #fff4dd;
    color: #9a6700;
    border-color: #ffd98a;
}

.action-icon-escalera {
    background: #ebe9ff;
    color: #4a3cc9;
    border-color: #cfc8ff;
    cursor: pointer;
}

.ganar-shortcuts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
}

.ganar-shortcut-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.ganar-shortcut-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-decoration: none;
    padding: 12px 14px;
    border: 1px solid #d8e2f1;
    border-radius: 12px;
    background: #f8fbff;
    color: #1f3a66;
}

.ganar-shortcut-card:hover {
    background: #eef5ff;
}

.ganar-shortcut-card.active {
    background: #e7f1ff;
    border-color: #4f8edc;
    box-shadow: inset 0 0 0 1px rgba(79, 142, 220, 0.25);
}

.ganar-shortcut-title {
    font-weight: 700;
    font-size: 16px;
}

.ganar-shortcut-count {
    min-width: 36px;
    height: 30px;
    border-radius: 999px;
    background: #2f65b5;
    color: #fff;
    font-size: 15px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.ganar-shortcut-help {
    color: #6b7a90;
    font-size: 12px;
    line-height: 1.2;
}

.escalera-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 14px;
}

.escalera-modal-backdrop.show {
    display: flex;
}

.escalera-modal {
    width: min(1120px, 96vw);
    max-height: 90vh;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #dbe3f4;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.22);
    overflow: hidden;
}

.escalera-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 14px;
    background: #f4f8ff;
    border-bottom: 1px solid #dbe3f4;
}

.escalera-modal-title {
    margin: 0;
    font-size: 18px;
    color: #1f365f;
}

.escalera-modal-close {
    border: 0;
    background: transparent;
    font-size: 20px;
    line-height: 1;
    color: #6b7a90;
    cursor: pointer;
}

.escalera-modal-body {
    padding: 16px;
    overflow: auto;
}

.escalera-level-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eef4ff;
    color: #244a84;
    border: 1px solid #cfe0ff;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 10px;
}

.escalera-modal-matrix-wrap {
    border: 1px solid #dbe3f4;
    border-radius: 10px;
    overflow: auto;
}

.escalera-modal-matrix {
    width: 100%;
    min-width: 940px;
    border-collapse: collapse;
    table-layout: fixed;
}

.escalera-modal-matrix th,
.escalera-modal-matrix td {
    border: 1px solid #e4ebf7;
    padding: 10px 8px;
    vertical-align: middle;
}

.escalera-modal-matrix th {
    text-align: center;
    font-size: 18px;
    font-weight: 800;
}

.escalera-modal-stage-ganar {
    background: #fff7dd;
    color: #8a6500;
}

.escalera-modal-stage-consolidar {
    background: #eaf9ee;
    color: #176636;
}

.escalera-modal-stage-discipular {
    background: #ecf5ff;
    color: #1e65b6;
}

.escalera-modal-stage-enviar {
    background: #ffeaf2;
    color: #a30f43;
}

.escalera-modal-sub-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #4b5f7f;
    margin-bottom: 4px;
}

.escalera-check-label {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    color: #425573;
    justify-content: center;
}

.escalera-check-label input {
    width: 14px;
    height: 14px;
}

.escalera-check-item.done .escalera-check-label {
    color: #1e7f39;
    font-weight: 700;
}

.escalera-check-label.disabled {
    opacity: 0.65;
}

.escalera-check-icon {
    font-size: 13px;
    color: #1e7f39;
    margin-right: 2px;
}

.escalera-empty-cell {
    background: #f8fafc;
}

.no-disponible-panel {
    margin-top: 12px;
    border: 1px solid #f7d4a0;
    background: #fff8ed;
    border-radius: 10px;
    padding: 10px;
}

.no-disponible-panel label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #9a6700;
    margin-bottom: 6px;
}

.no-disponible-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 6px;
}

.no-disponible-header label {
    margin-bottom: 0;
}

.no-disponible-panel textarea {
    width: 100%;
    min-height: 90px;
    resize: vertical;
    border: 1px solid #dfc7a0;
    border-radius: 8px;
    padding: 8px;
    font-size: 13px;
}

.no-disponible-actions {
    margin-top: 8px;
    display: flex;
    justify-content: flex-end;
}

.escalera-modal-footer-actions {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e4ebf7;
    display: flex;
    justify-content: flex-end;
    position: sticky;
    bottom: 0;
    background: #fff;
}

.no-disponible-save-btn {
    border: 1px solid #c38a1d;
    background: #d89a22;
    color: #fff;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}

.no-disponible-save-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.no-disponible-note {
    margin-top: 4px;
    font-size: 11px;
    color: #9a6700;
    white-space: normal;
}

.escalera-status-msg {
    margin-top: 10px;
    font-size: 12px;
    color: #5b6b84;
    min-height: 18px;
}

.escalera-status-msg.error {
    color: #b42318;
}

.escalera-status-msg.success {
    color: #1e7f39;
}
</style>

<div class="escalera-modal-backdrop" id="escaleraModalBackdrop" aria-hidden="true">
    <div class="escalera-modal" role="dialog" aria-modal="true" aria-labelledby="escaleraModalTitle">
        <div class="escalera-modal-header">
            <h3 class="escalera-modal-title" id="escaleraModalTitle">Escalera del Exito</h3>
            <button type="button" class="escalera-modal-close" id="escaleraModalClose" aria-label="Cerrar">&times;</button>
        </div>
        <div class="escalera-modal-body" id="escaleraModalBody"></div>
    </div>
</div>

<script>
(function() {
    const etapas = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
    const subprocesos = {
        Ganar: ['Asignado a lider', 'Primer contacto', 'Ubicado en celula', 'No se dispone'],
        Consolidar: ['Universidad de la vida', 'Encuentro', 'Bautismo'],
        Discipular: ['Proyeccion', 'Equipo G12', 'Capacitacion destino nivel 1'],
        Enviar: ['Capacitacion destino nivel 2', 'Capacitacion destino nivel 3', 'Celula']
    };

    const backdrop = document.getElementById('escaleraModalBackdrop');
    const closeBtn = document.getElementById('escaleraModalClose');
    const body = document.getElementById('escaleraModalBody');
    const title = document.getElementById('escaleraModalTitle');
    const endpoint = '<?= PUBLIC_URL ?>?url=personas/actualizarChecklistEscalera';

    const modalState = {
        personaId: 0,
        personaNombre: '',
        proceso: 'Ganar',
        checklist: null,
        meta: {
            no_disponible_observacion: ''
        },
        asignadoALider: false,
        botonOrigen: null,
        guardando: false
    };

    function construirChecklistDesdeProceso(proceso) {
        const resultado = {};
        const indiceActual = etapas.indexOf(proceso);
        etapas.forEach((etapa, idx) => {
            const totalSubprocesos = (subprocesos[etapa] || []).length;
            resultado[etapa] = Array(totalSubprocesos).fill(false);
            if (idx < indiceActual) {
                const limiteCompletado = Math.min(3, totalSubprocesos);
                for (let i = 0; i < limiteCompletado; i++) {
                    resultado[etapa][i] = true;
                }
            }
            if (idx === indiceActual) {
                resultado[etapa][0] = true;
            }
        });
        if (indiceActual < 0) {
            resultado.Ganar = [false, false, false, false];
        }
        return resultado;
    }

    function combinarChecklist(base, persistido) {
        const combinado = JSON.parse(JSON.stringify(base));
        if (!persistido || typeof persistido !== 'object') {
            return combinado;
        }

        etapas.forEach(etapa => {
            if (!Array.isArray(persistido[etapa])) {
                return;
            }
            for (let i = 0; i < (subprocesos[etapa] || []).length; i++) {
                if (typeof persistido[etapa][i] !== 'undefined') {
                    combinado[etapa][i] = !!persistido[etapa][i];
                }
            }
        });

        return combinado;
    }

    function extraerMetaChecklist(persistido) {
        const metaBase = {
            no_disponible_observacion: ''
        };

        if (!persistido || typeof persistido !== 'object' || !persistido._meta || typeof persistido._meta !== 'object') {
            return metaBase;
        }

        metaBase.no_disponible_observacion = (persistido._meta.no_disponible_observacion || '').toString().trim();
        return metaBase;
    }

    function abrirModal(personaId, nombre, proceso, checklistRaw, botonOrigen, asignadoALider) {
        let checklistPersistido = null;
        if (checklistRaw) {
            try {
                checklistPersistido = JSON.parse(checklistRaw);
            } catch (e) {
                checklistPersistido = null;
            }
        }

        modalState.personaId = Number(personaId || 0);
        modalState.personaNombre = nombre || 'Persona';
        modalState.proceso = etapas.includes(proceso) ? proceso : 'Ganar';
        modalState.checklist = combinarChecklist(construirChecklistDesdeProceso(modalState.proceso), checklistPersistido);
        modalState.meta = extraerMetaChecklist(checklistPersistido);
        modalState.asignadoALider = !!asignadoALider;
        if (modalState.checklist.Ganar && modalState.checklist.Ganar.length > 0) {
            modalState.checklist.Ganar[0] = modalState.asignadoALider;
        }
        modalState.botonOrigen = botonOrigen || null;
        modalState.guardando = false;

        renderModal();
        backdrop.classList.add('show');
        backdrop.setAttribute('aria-hidden', 'false');
    }

    function renderModal(mensaje, esError) {
        const procesoActual = etapas.includes(modalState.proceso) ? modalState.proceso : 'Ganar';
        const indiceProceso = etapas.indexOf(procesoActual);
        const noDisponibleMarcado = !!(modalState.checklist.Ganar && modalState.checklist.Ganar[3]);

        title.textContent = 'Escalera del Exito - ' + modalState.personaNombre;
        let html = '<div class="escalera-level-pill">Nivel actual: <strong>' + escapeHtml(procesoActual) + '</strong></div>';
        html += '<div class="escalera-modal-matrix-wrap">';
        html += '<table class="escalera-modal-matrix"><thead><tr>';
        html += '<th class="escalera-modal-stage-ganar">Ganar</th>';
        html += '<th class="escalera-modal-stage-consolidar">Consolidar</th>';
        html += '<th class="escalera-modal-stage-discipular">Discipular</th>';
        html += '<th class="escalera-modal-stage-enviar">Enviar</th>';
        html += '</tr></thead><tbody>';

        const totalFilas = Math.max(...etapas.map(etapa => (subprocesos[etapa] || []).length));
        for (let i = 0; i < totalFilas; i++) {
            html += '<tr>';

            etapas.forEach((etapa, etapaIndex) => {
                const nombreSub = (subprocesos[etapa] && subprocesos[etapa][i]) ? subprocesos[etapa][i] : '';
                if (!nombreSub) {
                    html += '<td class="escalera-empty-cell"></td>';
                    return;
                }

                const done = !!(modalState.checklist[etapa] && modalState.checklist[etapa][i]);
                let editable = etapaIndex === indiceProceso;
                if (etapa === 'Ganar' && i === 0) {
                    editable = false;
                }
                if (noDisponibleMarcado && !(etapa === 'Ganar' && i === 3)) {
                    editable = false;
                }
                html += '<td class="escalera-check-item ' + (done ? 'done' : '') + '">';
                html += '<span class="escalera-modal-sub-label">' + escapeHtml(nombreSub) + '</span>';
                html += '<label class="escalera-check-label ' + (editable ? '' : 'disabled') + '">';
                html += '<input type="checkbox" class="js-escalera-check" data-etapa="' + escapeHtml(etapa) + '" data-indice="' + i + '" ' + (done ? 'checked' : '') + ' ' + (editable && !modalState.guardando ? '' : 'disabled') + '>';
                html += '<span>' + (done ? '<span class="escalera-check-icon">&#10003;</span>Completado' : 'Pendiente') + '</span>';
                html += '</label>';
                html += '</td>';
            });

            html += '</tr>';
        }

        html += '</tbody></table></div>';

        if (noDisponibleMarcado) {
            html += '<div class="no-disponible-panel">';
            html += '<div class="no-disponible-header">';
            html += '<label for="js-no-disponible-observacion">Observacion de No se dispone</label>';
            html += '<button type="button" class="no-disponible-save-btn js-guardar-no-disponible" ' + (modalState.guardando ? 'disabled' : '') + '>Guardar observacion</button>';
            html += '</div>';
            html += '<textarea id="js-no-disponible-observacion" ' + (modalState.guardando ? 'disabled' : '') + ' placeholder="Describe por que no se logro concretar esta persona...">' + escapeHtml(modalState.meta.no_disponible_observacion || '') + '</textarea>';
            html += '<div class="no-disponible-actions">';
            html += '<button type="button" class="no-disponible-save-btn js-guardar-no-disponible" ' + (modalState.guardando ? 'disabled' : '') + '>Guardar observacion</button>';
            html += '</div></div>';
            html += '<div class="escalera-modal-footer-actions">';
            html += '<button type="button" class="no-disponible-save-btn js-guardar-no-disponible" ' + (modalState.guardando ? 'disabled' : '') + '>Guardar observacion</button>';
            html += '</div>';
        }

        html += '<div class="escalera-status-msg ' + (mensaje ? (esError ? 'error' : 'success') : '') + '">' + (mensaje ? escapeHtml(mensaje) : '') + '</div>';
        body.innerHTML = html;
        enlazarBotonesGuardarNoDisponible();
    }

    function guardarNoDisponibleDesdeUI() {
        if (modalState.guardando) {
            alert('Ya se esta guardando la informacion.');
            return;
        }

        const textarea = document.getElementById('js-no-disponible-observacion');
        const observacion = textarea ? textarea.value.trim() : '';
        if (observacion === '') {
            alert('Debes escribir una observacion para guardar.');
            renderModal('La observacion es obligatoria para No se dispone', true);
            return;
        }

        modalState.meta.no_disponible_observacion = observacion;
        guardarChecklist('Ganar', 3, true, observacion, {
            cerrarModalExito: true,
            recargarDespues: true,
            mensajeExito: 'Se guardo la informacion con exito'
        });
    }

    function enlazarBotonesGuardarNoDisponible() {
        body.querySelectorAll('.js-guardar-no-disponible').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                guardarNoDisponibleDesdeUI();
            });
        });
    }

    async function guardarChecklist(etapa, indice, marcado, observacionNoDisponible, opciones) {
        if (!modalState.personaId || modalState.guardando) {
            if (modalState.guardando) {
                renderModal('Guardando informacion, espera un momento...');
            }
            return;
        }

        const opts = Object.assign({
            cerrarModalExito: false,
            recargarDespues: false,
            mensajeExito: 'Checklist actualizado'
        }, opciones || {});

        modalState.guardando = true;
        renderModal('Guardando cambios...');

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    id_persona: modalState.personaId,
                    etapa: etapa,
                    indice: indice,
                    marcado: marcado ? 1 : 0,
                    observacion_no_disponible: (etapa === 'Ganar' && indice === 3) ? (observacionNoDisponible || '') : ''
                })
            });

            const contentType = (response.headers.get('content-type') || '').toLowerCase();
            if (contentType.indexOf('application/json') === -1) {
                const raw = await response.text();
                throw new Error('Respuesta invalida del servidor: ' + raw.substring(0, 120));
            }

            const data = await response.json();
            if (!response.ok || !data || !data.success) {
                throw new Error((data && data.message) ? data.message : 'No se pudo guardar el checklist');
            }

            if (data.checklist && typeof data.checklist === 'object') {
                modalState.checklist = combinarChecklist(modalState.checklist, data.checklist);
                modalState.meta = extraerMetaChecklist(data.checklist);
            }
            if (data.proceso && etapas.includes(data.proceso)) {
                modalState.proceso = data.proceso;
            }

            if (modalState.checklist.Ganar && modalState.checklist.Ganar.length > 0) {
                modalState.checklist.Ganar[0] = modalState.asignadoALider;
            }

            if (modalState.botonOrigen) {
                modalState.botonOrigen.setAttribute('data-persona-proceso', modalState.proceso);
                modalState.botonOrigen.setAttribute('data-persona-checklist', JSON.stringify(Object.assign({}, modalState.checklist, { _meta: modalState.meta })));
            }

            modalState.guardando = false;

            if (opts.cerrarModalExito) {
                alert(opts.mensajeExito);
                cerrarModal();
                if (opts.recargarDespues) {
                    window.location.reload();
                }
                return;
            }

            renderModal(opts.mensajeExito);
        } catch (error) {
            modalState.guardando = false;
            if (opts.cerrarModalExito) {
                alert('No se pudo guardar: ' + (error.message || 'Error inesperado'));
            }
            renderModal(error.message || 'Error al guardar', true);
        }
    }

    function cerrarModal() {
        backdrop.classList.remove('show');
        backdrop.setAttribute('aria-hidden', 'true');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    document.querySelectorAll('.js-escalera-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            abrirModal(
                this.getAttribute('data-persona-id') || '0',
                this.getAttribute('data-persona-nombre') || '',
                this.getAttribute('data-persona-proceso') || '',
                this.getAttribute('data-persona-checklist') || '',
                this,
                this.getAttribute('data-persona-asignado') === '1'
            );
        });
    });

    if (body) {
        body.addEventListener('change', function(e) {
            const target = e.target;
            if (!target || !target.classList.contains('js-escalera-check')) {
                return;
            }

            const etapa = target.getAttribute('data-etapa') || '';
            const indice = parseInt(target.getAttribute('data-indice') || '-1', 10);
            if (!etapa || indice < 0) {
                target.checked = !target.checked;
                return;
            }

            if (etapa === 'Ganar' && indice === 3 && target.checked) {
                modalState.checklist.Ganar[3] = true;
                renderModal('Escribe la observacion y guardala para marcar No se dispone');
                return;
            }

            if (etapa === 'Ganar' && indice === 3 && !target.checked) {
                modalState.meta.no_disponible_observacion = '';
                guardarChecklist(etapa, indice, false, '');
                return;
            }

            guardarChecklist(etapa, indice, !!target.checked);
        });

        body.addEventListener('keydown', function(e) {
            const target = e.target;
            if (!target || target.id !== 'js-no-disponible-observacion') {
                return;
            }

            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                guardarNoDisponibleDesdeUI();
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', cerrarModal);
    }

    if (backdrop) {
        backdrop.addEventListener('click', function(e) {
            if (e.target === backdrop) {
                cerrarModal();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && backdrop && backdrop.classList.contains('show')) {
            cerrarModal();
        }
    });
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>


<?php include VIEWS . '/layout/header.php'; ?>

<style>
    .ministerio-personas-table {
        min-width: 860px;
        table-layout: auto;
        font-size: 13px;
    }

    .ministerio-personas-table th,
    .ministerio-personas-table td {
        white-space: nowrap;
        word-break: normal;
        overflow-wrap: normal;
        padding: 8px 10px;
        line-height: 1.2;
    }

    .ministerio-personas-table th:nth-child(2),
    .ministerio-personas-table td:nth-child(2),
    .ministerio-personas-table th:nth-child(5),
    .ministerio-personas-table td:nth-child(5) {
        white-space: normal;
        word-break: keep-all;
        overflow-wrap: break-word;
        min-width: 220px;
    }

    .ministerio-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 8px;
        margin-top: 8px;
    }

    .ministerio-kpi {
        border: 1px solid #d8e2f1;
        border-radius: 10px;
        background: #f8fbff;
        padding: 8px 10px;
    }

    .ministerio-kpi--clickable {
        cursor: pointer;
        transition: border-color .15s, background .15s, box-shadow .15s;
    }

    .ministerio-kpi--clickable:hover {
        border-color: #a8c2ea;
        background: #eef5ff;
    }

    .ministerio-kpi--active {
        border-color: #4f8edc;
        background: #e7f1ff;
        box-shadow: inset 0 0 0 1px rgba(79, 142, 220, 0.25);
    }

    .ministerio-kpi strong {
        display: block;
        font-size: 18px;
        color: #1f4f93;
        line-height: 1.1;
    }

    .ministerio-kpi span {
        font-size: 12px;
        color: #5b6b84;
    }

    .escalera-resumen-ministerio {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 8px;
        margin-bottom: 10px;
    }

    .escalera-resumen-ministerio .meta-pill {
        justify-content: space-between;
        gap: 10px;
        width: 100%;
        display: flex;
        align-items: center;
    }

    .ministerio-chip--clickable {
        cursor: pointer;
        transition: border-color .15s, background .15s, box-shadow .15s;
    }

    .ministerio-chip--clickable:hover {
        border-color: #a8c2ea;
        background: #eef5ff;
    }

    .ministerio-chip--active {
        border-color: #4f8edc !important;
        background: #e7f1ff !important;
        box-shadow: inset 0 0 0 1px rgba(79, 142, 220, 0.25);
    }

    .convenciones-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 8px;
    }

    .convencion-item {
        border: 1px solid #d8e2f1;
        border-radius: 10px;
        background: #f8fbff;
        padding: 8px 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        color: #465a78;
    }

    .convencion-item strong {
        color: #1f4f93;
        font-size: 16px;
    }

    .convencion-item--clickable {
        cursor: pointer;
        transition: border-color .15s, background .15s, box-shadow .15s;
    }

    .convencion-item--clickable:hover {
        border-color: #a8c2ea;
        background: #eef5ff;
    }

    .ministerio-actions-row {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-bottom: 10px;
    }

    .ministerio-personas-panel {
        margin-top: 12px;
        border: 1px solid #d8e2f1;
        border-radius: 10px;
        background: #fff;
        overflow: hidden;
        display: none;
    }

    .ministerio-personas-panel.is-visible {
        display: block;
    }

    .ministerio-personas-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 9px 12px;
        background: #f8fbff;
        border-bottom: 1px solid #d8e2f1;
    }

    .ministerio-personas-panel-title {
        font-size: 13px;
        color: #415772;
        margin: 0;
    }

    .ministerio-personas-count {
        font-weight: 700;
        color: #1f4f93;
    }

    .ministerio-personas-clear {
        border: 1px solid #c7d7ef;
        border-radius: 8px;
        padding: 4px 8px;
        background: #fff;
        color: #35598a;
        font-size: 12px;
        cursor: pointer;
    }

    .ministerio-personas-table thead th {
        padding-top: 10px;
        padding-bottom: 10px;
    }

    .ministerio-personas-table tbody td {
        padding-top: 7px;
        padding-bottom: 7px;
    }

    .ministerio-card > .collapse-content {
        display: none;
    }

    .ministerio-card > summary {
        cursor: pointer;
    }

    .ministerio-card .collapse-arrow {
        transform: none !important;
    }

    .ministerio-modal {
        position: fixed;
        inset: 0;
        z-index: 1050;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    .ministerio-modal.is-open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .ministerio-modal__overlay {
        position: absolute;
        inset: 0;
        background: rgba(15, 27, 46, 0.58);
        backdrop-filter: blur(2px);
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .ministerio-modal__dialog {
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

    .ministerio-modal.is-open .ministerio-modal__overlay {
        opacity: 1;
    }

    .ministerio-modal.is-open .ministerio-modal__dialog {
        transform: translateY(0) scale(1);
        opacity: 1;
    }

    .ministerio-modal__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 18px;
        border-bottom: 1px solid #d9e4f2;
        background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
    }

    .ministerio-modal__title {
        margin: 0;
        color: #21457e;
        font-size: 20px;
        font-weight: 700;
    }

    .ministerio-modal__close {
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

    .ministerio-modal__body {
        padding: 14px 18px 18px;
        overflow: auto;
    }

    .ministerio-modal__body .collapse-content {
        display: block;
    }

    @media (max-width: 800px) {
        .ministerio-modal__dialog {
            width: calc(100vw - 16px);
            max-height: calc(100vh - 16px);
            margin: 8px auto;
        }

        .ministerio-modal__header,
        .ministerio-modal__body {
            padding-left: 12px;
            padding-right: 12px;
        }

        .ministerio-modal__title {
            font-size: 18px;
        }
    }
</style>

<?php
$returnUrl = $return_url ?? null;
$returnUrlParam = $returnUrl ? '&return_url=' . urlencode((string)$returnUrl) : '';
?>

<div class="page-header">
    <h2>Ministerios</h2>
    <?php $puedeCrearMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'crear'); ?>
    <?php $puedeEditarMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'editar'); ?>
    <?php $puedeEliminarMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'eliminar'); ?>
    <?php $puedeGestionarMinisterio = $puedeEditarMinisterio || $puedeEliminarMinisterio; ?>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <?php if (!empty($returnUrl)): ?>
        <a href="<?= htmlspecialchars($returnUrl) ?>" class="btn btn-secondary">← Volver a reportes</a>
        <?php endif; ?>
        <?php if ($puedeCrearMinisterio): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=ministerios/crear<?= $returnUrlParam ?>" class="btn btn-primary">+ Nuevo Ministerio</a>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-bottom: 16px;">
    <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="filters-inline" style="padding: 14px;">
        <input type="hidden" name="url" value="ministerios">
        <?php if (!empty($returnUrl)): ?>
        <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">
        <?php endif; ?>
        <div class="form-group" style="margin: 0;">
            <label for="fecha_referencia">Semana (domingo a domingo)</label>
            <input type="date" id="fecha_referencia" name="fecha_referencia" class="form-control" value="<?= htmlspecialchars((string)($fecha_referencia ?? date('Y-m-d'))) ?>" required>
            <small style="color:#6b7280;">Rango: <?= date('d/m/Y', strtotime((string)($fecha_inicio ?? date('Y-m-d')))) ?> - <?= date('d/m/Y', strtotime((string)($fecha_fin ?? date('Y-m-d')))) ?></small>
        </div>
        <div class="filters-actions">
            <button type="submit" class="btn btn-primary">Aplicar semana</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=ministerios<?= $returnUrlParam ?>" class="btn btn-secondary">Semana actual</a>
        </div>
    </form>
</div>

<?php if (!empty($sections ?? [])): ?>
<div class="sections-grid">
    <?php foreach ($sections as $section): ?>
        <?php $metricas = $section['metricas'] ?? []; ?>
        <details class="section-collapse ministerio-card" data-ministerio-card="1">
            <summary>
                <div class="collapse-title">
                    <i class="bi bi-bank"></i> <?= htmlspecialchars($section['label']) ?>
                </div>
                <div class="section-meta mb-0">
                    <a class="view-group-btn" href="<?= PUBLIC_URL ?>?url=personas&ministerio=<?= (int)$section['id_ministerio'] ?>" onclick="event.stopPropagation();">Ver personas</a>
                    <a class="view-group-btn" href="<?= PUBLIC_URL ?>?url=celulas&ministerio=<?= (int)$section['id_ministerio'] ?>" onclick="event.stopPropagation();">Ver células</a>
                    <span class="meta-pill">Líderes célula: <?= (int)($metricas['lideres_celula'] ?? 0) ?></span>
                    <span class="meta-pill">Asistentes célula: <?= (int)($metricas['asistentes_celula'] ?? 0) ?></span>
                    <span class="collapse-arrow">⤢</span>
                </div>
            </summary>

            <div class="collapse-content">
                <?php if ($puedeGestionarMinisterio): ?>
                <div class="ministerio-actions-row">
                    <?php if ($puedeEditarMinisterio): ?>
                        <a href="<?= PUBLIC_URL ?>?url=ministerios/editar&id=<?= (int)$section['id_ministerio'] ?><?= $returnUrlParam ?>" class="btn btn-sm btn-warning">Editar</a>
                    <?php endif; ?>
                    <?php if ($puedeEliminarMinisterio): ?>
                        <a href="<?= PUBLIC_URL ?>?url=ministerios/eliminar&id=<?= (int)$section['id_ministerio'] ?><?= $returnUrlParam ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este ministerio?')">Eliminar</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($section['descripcion'])): ?>
                    <div class="section-meta">
                        <span class="meta-pill">Descripción: <?= htmlspecialchars($section['descripcion']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="ministerio-kpi-grid">
                    <div class="ministerio-kpi ministerio-kpi--clickable" data-kpi-filter="total_personas">
                        <strong><?= number_format((int)$section['total_personas']) ?></strong>
                        <span>Personas activas del ministerio</span>
                    </div>
                    <div class="ministerio-kpi ministerio-kpi--clickable" data-kpi-filter="celulas">
                        <strong><?= (int)($metricas['celulas'] ?? 0) ?></strong>
                        <span>Células del ministerio</span>
                    </div>
                    <div class="ministerio-kpi ministerio-kpi--clickable" data-kpi-filter="lideres_celula">
                        <strong><?= (int)($metricas['lideres_celula'] ?? 0) ?></strong>
                        <span>Líderes de célula</span>
                    </div>
                    <div class="ministerio-kpi ministerio-kpi--clickable" data-kpi-filter="asistentes_celula">
                        <strong><?= (int)($metricas['asistentes_celula'] ?? 0) ?></strong>
                        <span>Asistentes de célula</span>
                    </div>
                    <div class="ministerio-kpi ministerio-kpi--clickable" data-kpi-filter="ganados_semana_total">
                        <strong><?= (int)($metricas['ganados_semana_total'] ?? 0) ?></strong>
                        <span>Ganados en la semana</span>
                    </div>
                    <div class="ministerio-kpi ministerio-kpi--clickable" data-kpi-filter="ganados_semana_celula">
                        <strong><?= (int)($metricas['ganados_semana_celula'] ?? 0) ?></strong>
                        <span>Ganados en célula</span>
                    </div>
                    <div class="ministerio-kpi ministerio-kpi--clickable" data-kpi-filter="ganados_semana_domingo">
                        <strong><?= (int)($metricas['ganados_semana_domingo'] ?? 0) ?></strong>
                        <span>Ganados en domingo</span>
                    </div>
                </div>

                <div class="ministerio-personas-panel" data-ministerio-personas-panel="1">
                    <div class="ministerio-personas-panel-header">
                        <p class="ministerio-personas-panel-title">Listado de personas: <strong data-ministerio-filtro-label="1">Todas</strong> · <span class="ministerio-personas-count" data-ministerio-count="1"><?= (int)count($section['rows'] ?? []) ?></span></p>
                        <button type="button" class="ministerio-personas-clear" data-ministerio-clear="1">Quitar filtro</button>
                    </div>
                    <div class="table-container" style="margin:0; border-radius:0; border:0;">
                        <div style="overflow-x:auto;">
                            <table class="data-table ministerio-personas-table" style="margin:0;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nombre</th>
                                        <th>Rol</th>
                                        <th>Teléfono</th>
                                        <th>Célula</th>
                                        <th>Ganado en</th>
                                        <th>Fecha registro</th>
                                    </tr>
                                </thead>
                                <tbody data-ministerio-personas-body="1">
                                    <?php if (!empty($section['rows'])): ?>
                                        <?php foreach ($section['rows'] as $row): ?>
                                            <tr
                                                data-match-total-personas="<?= !empty($row['match_total_personas']) ? '1' : '0' ?>"
                                                data-match-celulas="<?= !empty($row['match_celulas']) ? '1' : '0' ?>"
                                                data-match-lideres-celula="<?= !empty($row['match_lideres_celula']) ? '1' : '0' ?>"
                                                data-match-asistentes-celula="<?= !empty($row['match_asistentes_celula']) ? '1' : '0' ?>"
                                                data-match-ganados-semana-total="<?= !empty($row['match_ganados_semana_total']) ? '1' : '0' ?>"
                                                data-match-ganados-semana-celula="<?= !empty($row['match_ganados_semana_celula']) ? '1' : '0' ?>"
                                                data-match-ganados-semana-domingo="<?= !empty($row['match_ganados_semana_domingo']) ? '1' : '0' ?>"
                                                data-match-escalera-uv="<?= !empty($row['match_escalera_uv']) ? '1' : '0' ?>"
                                                data-match-escalera-encuentro="<?= !empty($row['match_escalera_encuentro']) ? '1' : '0' ?>"
                                                data-match-escalera-destino-n1="<?= !empty($row['match_escalera_destino_n1']) ? '1' : '0' ?>"
                                                data-match-escalera-destino-n2="<?= !empty($row['match_escalera_destino_n2']) ? '1' : '0' ?>"
                                                data-match-escalera-destino-n3="<?= !empty($row['match_escalera_destino_n3']) ? '1' : '0' ?>"
                                                data-match-convencion-enero="<?= !empty($row['match_convencion_enero']) ? '1' : '0' ?>"
                                                data-match-convencion-mujeres="<?= !empty($row['match_convencion_mujeres']) ? '1' : '0' ?>"
                                                data-match-convencion-jovenes="<?= !empty($row['match_convencion_jovenes']) ? '1' : '0' ?>"
                                                data-match-convencion-hombres="<?= !empty($row['match_convencion_hombres']) ? '1' : '0' ?>"
                                                data-match-convencion-total="<?= !empty($row['match_convencion_total']) ? '1' : '0' ?>"
                                            >
                                                <td><?= (int)($row['nro'] ?? 0) ?></td>
                                                <td><?= htmlspecialchars((string)($row['nombre'] ?? '')) ?></td>
                                                <td><?= htmlspecialchars((string)($row['rol'] ?? 'Sin rol')) ?></td>
                                                <td><?= htmlspecialchars((string)($row['telefono'] ?? '')) ?></td>
                                                <td><?= htmlspecialchars((string)($row['celula'] ?? '')) ?></td>
                                                <td><?= htmlspecialchars((string)($row['tipo_reunion'] ?? '')) ?></td>
                                                <td>
                                                    <?php $fechaRegistro = (string)($row['fecha_registro'] ?? ''); ?>
                                                    <?= $fechaRegistro !== '' ? htmlspecialchars(date('d/m/Y', strtotime($fechaRegistro))) : '' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr data-ministerio-empty-row="1">
                                            <td colspan="7" class="text-center">No hay personas en este ministerio</td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr data-ministerio-no-match-row="1" style="display:none;">
                                        <td colspan="7" class="text-center">No hay personas para este indicador</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php if (!empty($metricas['escalera'])): ?>
                    <div class="card" style="margin-bottom: 12px;">
                        <div class="card-body" style="padding: 10px 12px;">
                            <?php
                            $uv = (int)($metricas['escalera']['Consolidar']['Universidad de la vida'] ?? 0);
                            $encuentro = (int)($metricas['escalera']['Consolidar']['Encuentro'] ?? 0);
                            $destinoN1 = (int)($metricas['escalera']['Discipular']['Capacitacion destino nivel 1'] ?? 0);
                            $destinoN2 = (int)($metricas['escalera']['Enviar']['Capacitacion destino nivel 2'] ?? 0);
                            $destinoN3 = (int)($metricas['escalera']['Enviar']['Capacitacion destino nivel 3'] ?? 0);
                            ?>
                            <strong style="display:block; margin-bottom:8px;">Reporte Escalera del Éxito (Ministerio)</strong>

                            <div class="escalera-resumen-ministerio">
                                <span class="meta-pill ministerio-chip--clickable" data-kpi-filter="escalera_uv">Universidad de la vida <strong><?= $uv ?></strong></span>
                                <span class="meta-pill ministerio-chip--clickable" data-kpi-filter="escalera_encuentro">Encuentro <strong><?= $encuentro ?></strong></span>
                                <span class="meta-pill ministerio-chip--clickable" data-kpi-filter="escalera_destino_n1">Capacitación destino N1 <strong><?= $destinoN1 ?></strong></span>
                                <span class="meta-pill ministerio-chip--clickable" data-kpi-filter="escalera_destino_n2">Capacitación destino N2 <strong><?= $destinoN2 ?></strong></span>
                                <span class="meta-pill ministerio-chip--clickable" data-kpi-filter="escalera_destino_n3">Capacitación destino N3 <strong><?= $destinoN3 ?></strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="margin-bottom: 12px;">
                        <div class="card-body" style="padding: 10px 12px;">
                            <?php
                            $convencionEnero = (int)($metricas['convenciones']['enero'] ?? 0);
                            $convencionMujeres = (int)($metricas['convenciones']['mujeres'] ?? 0);
                            $convencionJovenes = (int)($metricas['convenciones']['jovenes'] ?? 0);
                            $convencionHombres = (int)($metricas['convenciones']['hombres'] ?? 0);
                            $totalConvenciones = $convencionEnero + $convencionMujeres + $convencionJovenes + $convencionHombres;
                            ?>
                            <strong style="display:block; margin-bottom:8px;">Reporte de convenciones</strong>
                            <div class="convenciones-grid">
                                <div class="convencion-item convencion-item--clickable" data-kpi-filter="convencion_enero">
                                    <span>Convención Enero</span>
                                    <strong><?= $convencionEnero ?></strong>
                                </div>
                                <div class="convencion-item convencion-item--clickable" data-kpi-filter="convencion_mujeres">
                                    <span>Convención Mujeres</span>
                                    <strong><?= $convencionMujeres ?></strong>
                                </div>
                                <div class="convencion-item convencion-item--clickable" data-kpi-filter="convencion_jovenes">
                                    <span>Convención Jóvenes</span>
                                    <strong><?= $convencionJovenes ?></strong>
                                </div>
                                <div class="convencion-item convencion-item--clickable" data-kpi-filter="convencion_hombres">
                                    <span>Convención Hombres</span>
                                    <strong><?= $convencionHombres ?></strong>
                                </div>
                                <div class="convencion-item convencion-item--clickable" data-kpi-filter="convencion_total">
                                    <span>Total convenciones</span>
                                    <strong><?= $totalConvenciones ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </details>
    <?php endforeach; ?>
</div>
<?php else: ?>
    <div class="table-container">
        <p class="text-center" style="padding: 20px; color: #666; margin: 0;">No hay ministerios registrados</p>
    </div>
<?php endif; ?>

<div id="ministerioModal" class="ministerio-modal" aria-hidden="true">
    <div class="ministerio-modal__overlay" data-ministerio-close="1"></div>
    <div class="ministerio-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ministerioModalTitle">
        <div class="ministerio-modal__header">
            <h3 id="ministerioModalTitle" class="ministerio-modal__title">Resumen del ministerio</h3>
            <button type="button" class="ministerio-modal__close" data-ministerio-close="1" aria-label="Cerrar">×</button>
        </div>
        <div id="ministerioModalBody" class="ministerio-modal__body"></div>
    </div>
</div>

<script>
    (function() {
        const modal = document.getElementById('ministerioModal');
        const modalTitle = document.getElementById('ministerioModalTitle');
        const modalBody = document.getElementById('ministerioModalBody');
        const cards = document.querySelectorAll('[data-ministerio-card="1"]');

        if (!modal || !modalTitle || !modalBody || !cards.length) {
            return;
        }

        function abrirModal(title, contentHtml) {
            modalTitle.textContent = title;
            modalBody.innerHTML = contentHtml;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            inicializarFiltroIndicadores(modalBody);
        }

        function cerrarModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            modalBody.innerHTML = '';
            document.body.style.overflow = '';
        }

        cards.forEach(function(card) {
            const summary = card.querySelector(':scope > summary');
            const titleNode = card.querySelector(':scope > summary .collapse-title');
            const contentNode = card.querySelector(':scope > .collapse-content');

            if (!summary || !contentNode) {
                return;
            }

            summary.addEventListener('click', function(event) {
                if (event.target.closest('a')) {
                    return;
                }

                event.preventDefault();
                const title = titleNode ? titleNode.textContent.trim() : 'Resumen del ministerio';
                abrirModal(title, contentNode.outerHTML);
            });
        });

        modal.addEventListener('click', function(event) {
            if (event.target.closest('[data-ministerio-close="1"]')) {
                cerrarModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                cerrarModal();
            }
        });

        const filtroLabels = {
            'total_personas': 'Personas activas del ministerio',
            'celulas': 'Personas con célula',
            'lideres_celula': 'Líderes de célula',
            'asistentes_celula': 'Asistentes de célula',
            'ganados_semana_total': 'Ganados en la semana',
            'ganados_semana_celula': 'Ganados en célula',
            'ganados_semana_domingo': 'Ganados en domingo',
            'escalera_uv': 'Universidad de la vida',
            'escalera_encuentro': 'Encuentro',
            'escalera_destino_n1': 'Capacitación destino N1',
            'escalera_destino_n2': 'Capacitación destino N2',
            'escalera_destino_n3': 'Capacitación destino N3',
            'convencion_enero': 'Convención Enero',
            'convencion_mujeres': 'Convención Mujeres',
            'convencion_jovenes': 'Convención Jóvenes',
            'convencion_hombres': 'Convención Hombres',
            'convencion_total': 'Total convenciones'
        };

        function inicializarFiltroIndicadores(scope) {
            const panel = scope.querySelector('[data-ministerio-personas-panel="1"]');
            if (!panel) {
                return;
            }

            const rows = Array.from(panel.querySelectorAll('tbody tr:not([data-ministerio-empty-row="1"]):not([data-ministerio-no-match-row="1"])'));
            const noMatchRow = panel.querySelector('[data-ministerio-no-match-row="1"]');
            const countNode = panel.querySelector('[data-ministerio-count="1"]');
            const labelNode = panel.querySelector('[data-ministerio-filtro-label="1"]');
            const clearBtn = panel.querySelector('[data-ministerio-clear="1"]');
            const kpis = Array.from(scope.querySelectorAll('.ministerio-kpi--clickable, .ministerio-chip--clickable, .convencion-item--clickable'));

            function desplazarAPanel() {
                try {
                    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } catch (e) {
                    panel.scrollIntoView(true);
                }
            }

            function aplicarFiltro(key) {
                let visibles = 0;

                if (key) {
                    panel.classList.add('is-visible');
                } else {
                    panel.classList.remove('is-visible');
                }

                rows.forEach(function(row) {
                    const mostrar = !key || row.getAttribute('data-match-' + key.replace(/_/g, '-')) === '1';
                    row.style.display = mostrar ? '' : 'none';
                    if (mostrar) {
                        visibles++;
                    }
                });

                if (countNode) {
                    countNode.textContent = String(visibles);
                }

                if (labelNode) {
                    labelNode.textContent = key ? (filtroLabels[key] || 'Indicador') : 'Todas';
                }

                if (noMatchRow) {
                    noMatchRow.style.display = key && visibles === 0 ? '' : 'none';
                }
            }

            kpis.forEach(function(kpi) {
                    kpi.addEventListener('click', function() {
                    const key = String(kpi.getAttribute('data-kpi-filter') || '');
                    kpis.forEach(function(other) {
                        other.classList.remove('ministerio-kpi--active', 'ministerio-chip--active');
                    });
                    kpi.classList.add('ministerio-kpi--active', 'ministerio-chip--active');
                    aplicarFiltro(key);
                    desplazarAPanel();
                });
            });

            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    kpis.forEach(function(kpi) { kpi.classList.remove('ministerio-kpi--active', 'ministerio-chip--active'); });
                    aplicarFiltro('');
                });
            }

            aplicarFiltro('');
        }
    })();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>

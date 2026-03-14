<?php include VIEWS . '/layout/header.php'; ?>

<style>
    .ministerio-personas-table {
        min-width: 860px;
        table-layout: auto;
    }

    .ministerio-personas-table th,
    .ministerio-personas-table td {
        white-space: nowrap;
        word-break: normal;
        overflow-wrap: normal;
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

    .ministerio-actions-row {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-bottom: 10px;
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

<div class="page-header">
    <h2>Ministerios</h2>
    <?php $puedeCrearMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'crear'); ?>
    <?php $puedeEditarMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'editar'); ?>
    <?php $puedeEliminarMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'eliminar'); ?>
    <?php $puedeGestionarMinisterio = $puedeEditarMinisterio || $puedeEliminarMinisterio; ?>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=ministerios/exportarExcel" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
        <?php if ($puedeCrearMinisterio): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=ministerios/crear" class="btn btn-primary">+ Nuevo Ministerio</a>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-bottom: 16px;">
    <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="filters-inline" style="padding: 14px;">
        <input type="hidden" name="url" value="ministerios">
        <div class="form-group" style="margin: 0;">
            <label for="fecha_referencia">Semana (domingo a domingo)</label>
            <input type="date" id="fecha_referencia" name="fecha_referencia" class="form-control" value="<?= htmlspecialchars((string)($fecha_referencia ?? date('Y-m-d'))) ?>" required>
            <small style="color:#6b7280;">Rango: <?= date('d/m/Y', strtotime((string)($fecha_inicio ?? date('Y-m-d')))) ?> - <?= date('d/m/Y', strtotime((string)($fecha_fin ?? date('Y-m-d')))) ?></small>
        </div>
        <div class="filters-actions">
            <button type="submit" class="btn btn-primary">Aplicar semana</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=ministerios" class="btn btn-secondary">Semana actual</a>
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
                <?php if (!empty($section['descripcion'])): ?>
                    <div class="section-meta">
                        <span class="meta-pill">Descripción: <?= htmlspecialchars($section['descripcion']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="ministerio-kpi-grid">
                    <div class="ministerio-kpi">
                        <strong><?= number_format((int)$section['total_personas']) ?></strong>
                        <span>Personas activas del ministerio</span>
                    </div>
                    <div class="ministerio-kpi">
                        <strong><?= (int)($metricas['celulas'] ?? 0) ?></strong>
                        <span>Células del ministerio</span>
                    </div>
                    <div class="ministerio-kpi">
                        <strong><?= (int)($metricas['lideres_celula'] ?? 0) ?></strong>
                        <span>Líderes de célula</span>
                    </div>
                    <div class="ministerio-kpi">
                        <strong><?= (int)($metricas['asistentes_celula'] ?? 0) ?></strong>
                        <span>Asistentes de célula</span>
                    </div>
                    <div class="ministerio-kpi">
                        <strong><?= (int)($metricas['ganados_semana_total'] ?? 0) ?></strong>
                        <span>Ganados en la semana</span>
                    </div>
                    <div class="ministerio-kpi">
                        <strong><?= (int)($metricas['ganados_semana_celula'] ?? 0) ?></strong>
                        <span>Ganados en célula</span>
                    </div>
                    <div class="ministerio-kpi">
                        <strong><?= (int)($metricas['ganados_semana_domingo'] ?? 0) ?></strong>
                        <span>Ganados en domingo</span>
                    </div>
                </div>

                <?php if ($puedeGestionarMinisterio): ?>
                <div class="ministerio-actions-row">
                    <?php if ($puedeEditarMinisterio): ?>
                        <a href="<?= PUBLIC_URL ?>?url=ministerios/editar&id=<?= (int)$section['id_ministerio'] ?>" class="btn btn-sm btn-warning">Editar</a>
                    <?php endif; ?>
                    <?php if ($puedeEliminarMinisterio): ?>
                        <a href="<?= PUBLIC_URL ?>?url=ministerios/eliminar&id=<?= (int)$section['id_ministerio'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este ministerio?')">Eliminar</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

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
                                <span class="meta-pill">Universidad de la vida <strong><?= $uv ?></strong></span>
                                <span class="meta-pill">Encuentro <strong><?= $encuentro ?></strong></span>
                                <span class="meta-pill">Capacitación destino N1 <strong><?= $destinoN1 ?></strong></span>
                                <span class="meta-pill">Capacitación destino N2 <strong><?= $destinoN2 ?></strong></span>
                                <span class="meta-pill">Capacitación destino N3 <strong><?= $destinoN3 ?></strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="margin-bottom: 12px;">
                        <div class="card-body" style="padding: 10px 12px;">
                            <?php $totalConvenciones = $encuentro + $destinoN1 + $destinoN2 + $destinoN3; ?>
                            <strong style="display:block; margin-bottom:8px;">Reporte de convenciones</strong>
                            <div class="convenciones-grid">
                                <div class="convencion-item">
                                    <span>Encuentro</span>
                                    <strong><?= $encuentro ?></strong>
                                </div>
                                <div class="convencion-item">
                                    <span>Convención N1</span>
                                    <strong><?= $destinoN1 ?></strong>
                                </div>
                                <div class="convencion-item">
                                    <span>Convención N2</span>
                                    <strong><?= $destinoN2 ?></strong>
                                </div>
                                <div class="convencion-item">
                                    <span>Convención N3</span>
                                    <strong><?= $destinoN3 ?></strong>
                                </div>
                                <div class="convencion-item">
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
    })();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>

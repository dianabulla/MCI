<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Células</h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=personas/crear&return_to=celulas&return_url=<?= urlencode(PUBLIC_URL . '?url=celulas') ?>" class="btn btn-primary">+ Nueva Persona</a>
        <a href="<?= PUBLIC_URL ?>?url=celulas/exportarExcel<?= !empty($_GET['ministerio']) ? '&ministerio=' . urlencode((string)$_GET['ministerio']) : '' ?><?= !empty($_GET['lider']) ? '&lider=' . urlencode((string)$_GET['lider']) : '' ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
        <?php if (AuthController::tienePermiso('materiales_celulas', 'ver')): ?>
            <a href="<?= PUBLIC_URL ?>?url=celulas/materiales" class="btn btn-secondary">Material Células (PDF)</a>
        <?php endif; ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=celulas/crear" class="btn btn-primary">+ Nueva Célula</a>
    </div>
</div>

<div class="form-container" style="margin-bottom: 20px;">
    <form method="GET" class="filter-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; align-items: end;">
        <input type="hidden" name="url" value="celulas">

        <div class="form-group" style="margin-bottom: 0;">
            <label for="filtro_ministerio">Filtrar por Ministerio</label>
            <select id="filtro_ministerio" name="ministerio" class="form-control">
                <option value="">Todos</option>
                <?php foreach (($ministerios_disponibles ?? []) as $ministerio): ?>
                    <option value="<?= (int)$ministerio['Id_Ministerio'] ?>" <?= ((string)$filtro_ministerio_actual === (string)$ministerio['Id_Ministerio']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label for="filtro_lider">Filtrar por Líder de Célula</label>
            <select id="filtro_lider" name="lider" class="form-control">
                <option value="">Todos</option>
                <?php foreach (($lideres_disponibles ?? []) as $lider): ?>
                    <option value="<?= (int)$lider['Id_Persona'] ?>" <?= ((string)$filtro_lider_actual === (string)$lider['Id_Persona']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lider['Nombre_Completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0; display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= PUBLIC_URL ?>?url=celulas" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<?php if (!empty($sections ?? [])): ?>
<div class="sections-grid">
    <?php foreach ($sections as $section): ?>
        <details class="section-collapse celula-card" data-celula-card="1">
            <summary>
                <div class="collapse-title">
                    <i class="bi bi-people-fill"></i> <?= htmlspecialchars($section['label']) ?>
                </div>
                <div class="section-meta mb-0">
                    <a class="view-group-btn" href="<?= PUBLIC_URL ?>?url=celulas/detalle&id=<?= (int)$section['id_celula'] ?>" onclick="event.stopPropagation();">Ver personas</a>
                    <span class="meta-pill">Líder: <?= htmlspecialchars($section['lider']) ?></span>
                    <span class="meta-pill">Anfitrión: <?= htmlspecialchars($section['anfitrion']) ?></span>
                    <span class="meta-pill">Personas: <?= number_format((int)$section['total_personas']) ?></span>
                    <span class="collapse-arrow">⤢</span>
                </div>
            </summary>

            <div class="collapse-content">
                <div class="section-meta">
                    <?php if (!empty($section['direccion'])): ?>
                        <span class="meta-pill">Dirección: <?= htmlspecialchars($section['direccion']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($section['dia'])): ?>
                        <span class="meta-pill">Día: <?= htmlspecialchars($section['dia']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($section['hora'])): ?>
                        <span class="meta-pill">Hora: <?= htmlspecialchars($section['hora']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <a href="<?= PUBLIC_URL ?>?url=personas/crear&return_to=celulas&return_url=<?= urlencode(PUBLIC_URL . '?url=celulas') ?>&celula=<?= (int)$section['id_celula'] ?>" class="btn btn-sm btn-primary">+ Nueva persona</a>
                    <?php if (AuthController::tienePermiso('asistencias', 'crear')): ?>
                        <a href="<?= PUBLIC_URL ?>?url=asistencias/registrar&celula=<?= (int)$section['id_celula'] ?>" class="btn btn-sm btn-success">Asistencias</a>
                    <?php endif; ?>
                    <a href="<?= PUBLIC_URL ?>?url=celulas/editar&id=<?= (int)$section['id_celula'] ?>" class="btn btn-sm btn-warning">Editar</a>
                    <a href="<?= PUBLIC_URL ?>?url=celulas/eliminar&id=<?= (int)$section['id_celula'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta célula?')">Eliminar</a>
                </div>

                <div class="table-responsive ministerio-table-wrap">
                    <table class="table table-hover ministerio-detail-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Nro</th>
                                <th>Persona</th>
                                <th style="width: 140px;">Teléfono</th>
                                <th style="width: 160px;">Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($section['rows'])): ?>
                                <?php foreach ($section['rows'] as $row): ?>
                                    <tr>
                                        <td><?= (int)$row['nro'] ?></td>
                                        <td>
                                            <a class="group-link" href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= (int)$row['id_persona'] ?>&return_to=celulas">
                                                <?= htmlspecialchars($row['nombre']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($row['telefono'] !== '' ? $row['telefono'] : '—') ?></td>
                                        <td><?= htmlspecialchars($row['documento'] !== '' ? $row['documento'] : '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No hay personas registradas en esta célula</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <span class="meta-pill">Anfitrión: <?= htmlspecialchars($section['anfitrion']) ?></span>
                </div>
            </div>
        </details>
    <?php endforeach; ?>
</div>

<div id="celulaModal" class="celula-modal" aria-hidden="true">
    <div class="celula-modal__overlay" data-celula-close="1"></div>
    <div class="celula-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="celulaModalTitle">
        <div class="celula-modal__header">
            <h3 id="celulaModalTitle" class="celula-modal__title">Detalle de célula</h3>
            <button type="button" class="celula-modal__close" data-celula-close="1" aria-label="Cerrar">×</button>
        </div>
        <div id="celulaModalBody" class="celula-modal__body"></div>
    </div>
</div>
<?php else: ?>
    <div class="table-container">
        <p class="text-center" style="padding: 20px; color: #666; margin: 0;">No hay células registradas</p>
    </div>
<?php endif; ?>

<style>
.celula-card > .collapse-content {
    display: none;
}

.celula-card .collapse-arrow {
    transform: none !important;
}

.celula-card > summary {
    cursor: pointer;
}

.celula-modal {
    position: fixed;
    inset: 0;
    z-index: 1050;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.2s ease;
}

.celula-modal.is-open {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

.celula-modal__overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 27, 46, 0.58);
    backdrop-filter: blur(2px);
    opacity: 0;
    transition: opacity 0.2s ease;
}

.celula-modal__dialog {
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

.celula-modal.is-open .celula-modal__overlay {
    opacity: 1;
}

.celula-modal.is-open .celula-modal__dialog {
    transform: translateY(0) scale(1);
    opacity: 1;
}

.celula-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid #d9e4f2;
    background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
}

.celula-modal__title {
    margin: 0;
    color: #21457e;
    font-size: 20px;
    font-weight: 700;
}

.celula-modal__close {
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

.celula-modal__body {
    padding: 14px 18px 18px;
    overflow: auto;
}

.celula-modal__body .collapse-content {
    display: block;
    padding: 0;
}

.celula-modal__body .ministerio-detail-table {
    min-width: 700px;
}

@media (max-width: 800px) {
    .celula-modal__dialog {
        width: calc(100vw - 16px);
        max-height: calc(100vh - 16px);
        margin: 8px auto;
    }

    .celula-modal__header,
    .celula-modal__body {
        padding-left: 12px;
        padding-right: 12px;
    }

    .celula-modal__title {
        font-size: 18px;
    }
}
</style>

<script>
    (function() {
        const ministerioSelect = document.getElementById('filtro_ministerio');
        const liderSelect = document.getElementById('filtro_lider');

        if (!ministerioSelect || !liderSelect) {
            return;
        }

        const lideres = <?= json_encode($lideres_disponibles ?? []) ?>;
        const liderActual = '<?= htmlspecialchars((string)($filtro_lider_actual ?? ''), ENT_QUOTES) ?>';

        function renderLideres() {
            const ministerioSeleccionado = ministerioSelect.value;
            liderSelect.innerHTML = '';

            const optionTodos = document.createElement('option');
            optionTodos.value = '';
            optionTodos.textContent = 'Todos';
            liderSelect.appendChild(optionTodos);

            const filtrados = lideres.filter(function(lider) {
                if (!ministerioSeleccionado) {
                    return true;
                }
                return String(lider.Id_Ministerio || '') === String(ministerioSeleccionado);
            });

            filtrados.forEach(function(lider) {
                const option = document.createElement('option');
                option.value = String(lider.Id_Persona);
                option.textContent = lider.Nombre_Completo;
                if (String(lider.Id_Persona) === String(liderActual)) {
                    option.selected = true;
                }
                liderSelect.appendChild(option);
            });

            const seleccionadoValido = Array.from(liderSelect.options).some(function(opt) {
                return opt.value === liderSelect.value;
            });

            if (!seleccionadoValido) {
                liderSelect.value = '';
            }
        }

        ministerioSelect.addEventListener('change', function() {
            renderLideres();
        });

        renderLideres();
    })();

    (function() {
        const modal = document.getElementById('celulaModal');
        const modalTitle = document.getElementById('celulaModalTitle');
        const modalBody = document.getElementById('celulaModalBody');
        const cards = document.querySelectorAll('[data-celula-card="1"]');

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
                const title = titleNode ? titleNode.textContent.trim() : 'Detalle de célula';
                abrirModal(title, contentNode.outerHTML);
            });
        });

        modal.addEventListener('click', function(event) {
            if (event.target.closest('[data-celula-close="1"]')) {
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

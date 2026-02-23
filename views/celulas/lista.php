<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Células</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=celulas/crear" class="btn btn-primary">+ Nueva Célula</a>
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
        <details class="section-collapse">
            <summary>
                <div class="collapse-title">
                    <i class="bi bi-people-fill"></i> <?= htmlspecialchars($section['label']) ?>
                </div>
                <div class="section-meta mb-0">
                    <a class="view-group-btn" href="<?= PUBLIC_URL ?>?url=celulas/detalle&id=<?= (int)$section['id_celula'] ?>" onclick="event.stopPropagation();">Ver personas</a>
                    <span class="meta-pill">Líder: <?= htmlspecialchars($section['lider']) ?></span>
                    <span class="meta-pill">Anfitrión: <?= htmlspecialchars($section['anfitrion']) ?></span>
                    <span class="meta-pill">Personas: <?= number_format((int)$section['total_personas']) ?></span>
                    <span class="collapse-arrow">▶</span>
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
<?php else: ?>
    <div class="table-container">
        <p class="text-center" style="padding: 20px; color: #666; margin: 0;">No hay células registradas</p>
    </div>
<?php endif; ?>

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
</script>

<?php include VIEWS . '/layout/footer.php'; ?>

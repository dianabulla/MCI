<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Asistencias</h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=asistencias/exportarExcel<?= !empty($_GET['ministerio']) ? '&ministerio=' . urlencode((string)$_GET['ministerio']) : '' ?><?= !empty($_GET['lider']) ? '&lider=' . urlencode((string)$_GET['lider']) : '' ?><?= !empty($_GET['celula']) ? '&celula=' . urlencode((string)$_GET['celula']) : '' ?><?= !empty($_GET['reporte']) ? '&reporte=' . urlencode((string)$_GET['reporte']) : '' ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
        <a href="<?= PUBLIC_URL ?>index.php?url=asistencias/registrar" class="btn btn-primary">+ Registrar Asistencia</a>
    </div>
</div>

<div class="form-container" style="margin-bottom: 20px;">
    <form method="GET" class="filter-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; align-items: end;">
        <input type="hidden" name="url" value="asistencias">

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

        <div class="form-group" style="margin-bottom: 0;">
            <label for="filtro_celula">Filtrar por Célula</label>
            <select id="filtro_celula" name="celula" class="form-control">
                <option value="">Todas</option>
                <option value="0" <?= ((string)($filtro_celula_actual ?? '') === '0') ? 'selected' : '' ?>>Sin célula</option>
                <?php foreach (($celulas_disponibles ?? []) as $celula): ?>
                    <option value="<?= (int)$celula['Id_Celula'] ?>" <?= ((string)$filtro_celula_actual === (string)$celula['Id_Celula']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($celula['Nombre_Celula']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label for="filtro_reporte">Estado de reporte</label>
            <select id="filtro_reporte" name="reporte" class="form-control">
                <option value="" <?= (($filtro_reporte_actual ?? '') === '') ? 'selected' : '' ?>>Todos</option>
                <option value="con" <?= (($filtro_reporte_actual ?? '') === 'con') ? 'selected' : '' ?>>Han reportado</option>
                <option value="sin" <?= (($filtro_reporte_actual ?? '') === 'sin') ? 'selected' : '' ?>>No han reportado</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0; display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= PUBLIC_URL ?>?url=asistencias" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<?php if (!empty($sections ?? [])): ?>
<div class="sections-grid">
    <?php foreach ($sections as $section): ?>
        <details class="section-collapse">
            <summary>
                <div class="collapse-title">
                    <i class="bi bi-check2-square"></i> <?= htmlspecialchars($section['label']) ?>
                </div>
                <div class="section-meta mb-0">
                    <a class="view-group-btn" href="<?= PUBLIC_URL ?>?url=asistencias/porCelula&id=<?= (int)$section['id_celula'] ?>" onclick="event.stopPropagation();">Ver asistencias</a>
                    <span class="meta-pill">Líder: <?= htmlspecialchars($section['lider']) ?></span>
                    <span class="meta-pill">Anfitrión: <?= htmlspecialchars($section['anfitrion']) ?></span>
                    <span class="meta-pill">Último reporte: <?= htmlspecialchars($section['fecha_ultimo_reporte'] !== '' ? $section['fecha_ultimo_reporte'] : 'Sin reporte') ?></span>
                    <span class="meta-pill">Registros: <?= number_format((int)$section['total_registros']) ?></span>
                    <span class="meta-pill">Sí: <?= number_format((int)$section['total_si']) ?></span>
                    <span class="meta-pill">No: <?= number_format((int)$section['total_no']) ?></span>
                    <span class="collapse-arrow">▶</span>
                </div>
            </summary>

            <div class="collapse-content">
                <div class="mb-3">
                    <a href="<?= PUBLIC_URL ?>?url=asistencias/registrar&celula=<?= (int)$section['id_celula'] ?>" class="btn btn-sm btn-primary">Registrar asistencia</a>
                </div>

                <?php if ($section['fecha_ultimo_reporte'] !== ''): ?>
                <div class="section-meta">
                    <span class="meta-pill">Mostrando último reporte de fecha: <?= htmlspecialchars($section['fecha_ultimo_reporte']) ?></span>
                </div>
                <?php endif; ?>

                <div class="table-responsive ministerio-table-wrap">
                    <table class="table table-hover ministerio-detail-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Nro</th>
                                <th>Persona</th>
                                <th style="width: 150px;">Fecha</th>
                                <th style="width: 100px;">Asistió</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($section['rows'])): ?>
                                <?php foreach ($section['rows'] as $row): ?>
                                    <tr>
                                        <td><?= (int)$row['nro'] ?></td>
                                        <td>
                                            <?php if ((int)$row['id_persona'] > 0): ?>
                                                <a class="group-link" href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= (int)$row['id_persona'] ?>">
                                                    <?= htmlspecialchars($row['persona']) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['persona']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['fecha']) ?></td>
                                        <td>
                                            <span class="badge <?= $row['asistio'] ? 'badge-success' : 'badge-danger' ?>">
                                                <?= $row['asistio'] ? 'Sí' : 'No' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No hay asistencias registradas para el último reporte de esta célula</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
    <?php endforeach; ?>
</div>
<?php else: ?>
    <div class="table-container">
        <p class="text-center" style="padding: 20px; color: #666; margin: 0;">No hay asistencias registradas</p>
    </div>
<?php endif; ?>

<script>
    (function() {
        const ministerioSelect = document.getElementById('filtro_ministerio');
        const liderSelect = document.getElementById('filtro_lider');
        const celulaSelect = document.getElementById('filtro_celula');

        if (!ministerioSelect || !liderSelect || !celulaSelect) {
            return;
        }

        const lideres = <?= json_encode($lideres_disponibles ?? []) ?>;
        const celulas = <?= json_encode($celulas_disponibles ?? []) ?>;
        const liderActual = '<?= htmlspecialchars((string)($filtro_lider_actual ?? ''), ENT_QUOTES) ?>';
        const celulaActual = '<?= htmlspecialchars((string)($filtro_celula_actual ?? ''), ENT_QUOTES) ?>';

        function renderLideres() {
            const ministerioSeleccionado = ministerioSelect.value;
            const celulaSeleccionada = celulaSelect ? celulaSelect.value : '';
            liderSelect.innerHTML = '';

            const optionTodos = document.createElement('option');
            optionTodos.value = '';
            optionTodos.textContent = 'Todos';
            liderSelect.appendChild(optionTodos);

            const filtrados = lideres.filter(function(lider) {
                const coincideMinisterio = !ministerioSeleccionado
                    ? true
                    : String(lider.Id_Ministerio || '') === String(ministerioSeleccionado);

                let coincideCelula = true;
                if (celulaSeleccionada && celulaSeleccionada !== '0') {
                    coincideCelula = celulas.some(function(celula) {
                        return String(celula.Id_Celula || '') === String(celulaSeleccionada)
                            && String(celula.Id_Lider || '') === String(lider.Id_Persona || '');
                    });
                }

                return coincideMinisterio && coincideCelula;
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

        function renderCelulas() {
            const ministerioSeleccionado = ministerioSelect.value;
            const liderSeleccionado = liderSelect.value;

            celulaSelect.innerHTML = '';

            const optionTodas = document.createElement('option');
            optionTodas.value = '';
            optionTodas.textContent = 'Todas';
            celulaSelect.appendChild(optionTodas);

            const optionSinCelula = document.createElement('option');
            optionSinCelula.value = '0';
            optionSinCelula.textContent = 'Sin célula';
            celulaSelect.appendChild(optionSinCelula);

            const filtradas = celulas.filter(function(celula) {
                const coincideMinisterio = !ministerioSeleccionado || ministerioSeleccionado === '0'
                    ? true
                    : String(celula.Id_Ministerio || '') === String(ministerioSeleccionado);

                const coincideLider = !liderSeleccionado || liderSeleccionado === '0'
                    ? true
                    : String(celula.Id_Lider || '') === String(liderSeleccionado);

                return coincideMinisterio && coincideLider;
            });

            filtradas.forEach(function(celula) {
                const option = document.createElement('option');
                option.value = String(celula.Id_Celula);
                option.textContent = celula.Nombre_Celula;
                if (String(celula.Id_Celula) === String(celulaActual)) {
                    option.selected = true;
                }
                celulaSelect.appendChild(option);
            });

            const seleccionadoValido = Array.from(celulaSelect.options).some(function(opt) {
                return opt.value === celulaSelect.value;
            });

            if (!seleccionadoValido) {
                celulaSelect.value = '';
            }
        }

        ministerioSelect.addEventListener('change', function() {
            renderLideres();
            renderCelulas();
        });

        liderSelect.addEventListener('change', function() {
            renderCelulas();
            renderLideres();
        });

        celulaSelect.addEventListener('change', function() {
            renderLideres();
            renderCelulas();
        });

        renderLideres();
        renderCelulas();
    })();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>

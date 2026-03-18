<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Reporte Semanal de Asistencias</h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=asistencias/exportarExcel<?= !empty($_GET['semana']) ? '&semana=' . urlencode((string)$_GET['semana']) : '' ?><?= !empty($_GET['ministerio']) ? '&ministerio=' . urlencode((string)$_GET['ministerio']) : '' ?><?= !empty($_GET['lider']) ? '&lider=' . urlencode((string)$_GET['lider']) : '' ?><?= !empty($_GET['reporte']) ? '&reporte=' . urlencode((string)$_GET['reporte']) : '' ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
        <a href="<?= PUBLIC_URL ?>?url=celulas" class="btn btn-secondary">Ir a Células (registrar)</a>
    </div>
</div>

<?php
$sectionsVisibles = is_array($sections ?? null) ? $sections : [];
$seccionesReportaron = array_values(array_filter($sectionsVisibles, static function ($section) {
    return !empty($section['si_reporto_semana']);
}));
$seccionesNoReportaron = array_values(array_filter($sectionsVisibles, static function ($section) {
    return empty($section['si_reporto_semana']);
}));
$seccionesEntregaronSobre = array_values(array_filter($sectionsVisibles, static function ($section) {
    return !empty($section['entrego_sobre']);
}));
$seccionesReportaronSinSobre = array_values(array_filter($sectionsVisibles, static function ($section) {
    return !empty($section['si_reporto_semana']) && empty($section['entrego_sobre']);
}));
$seccionesNoReportaronConSobre = array_values(array_filter($sectionsVisibles, static function ($section) {
    return empty($section['si_reporto_semana']) && !empty($section['entrego_sobre']);
}));
$seccionesReportaronConSobre = array_values(array_filter($sectionsVisibles, static function ($section) {
    return !empty($section['si_reporto_semana']) && !empty($section['entrego_sobre']);
}));
?>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="padding: 12px 16px;">
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
            <span class="meta-pill"><strong>Semana:</strong> <?= htmlspecialchars((string)($semana_inicio ?? '')) ?> al <?= htmlspecialchars((string)($semana_fin ?? '')) ?></span>
            <span class="meta-pill" style="background:#e9f8ef; border-color:#c6ebd4; color:#1f7a44;">Reportaron: <?= (int)count($seccionesReportaron) ?></span>
            <span class="meta-pill" style="background:#fff0f1; border-color:#f0d0d4; color:#9b2e3a;">No reportaron: <?= (int)count($seccionesNoReportaron) ?></span>
            <span class="meta-pill" style="background:#edf4ff; border-color:#c9dbff; color:#1f4b99;">Entregaron sobre: <?= (int)count($seccionesEntregaronSobre) ?>/<?= (int)count($sectionsVisibles) ?></span>
            <span class="meta-pill" style="background:#e9f8ef; border-color:#bfe4cb; color:#176a39;">Reportó y entregó sobre: <?= (int)count($seccionesReportaronConSobre) ?></span>
            <span class="meta-pill" style="background:#fff8e8; border-color:#f1ddb1; color:#8a6400;">Reportó célula y no entregó sobre: <?= (int)count($seccionesReportaronSinSobre) ?></span>
            <span class="meta-pill" style="background:#f3ecff; border-color:#dac8ff; color:#5b3d99;">No reportó célula y sí entregó sobre: <?= (int)count($seccionesNoReportaronConSobre) ?></span>
            <span class="meta-pill">Total células: <?= (int)count($sectionsVisibles) ?></span>
        </div>
    </div>
</div>

<div class="form-container" style="margin-bottom: 20px;">
    <form method="GET" class="filter-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; align-items: end;">
        <input type="hidden" name="url" value="asistencias">

        <div class="form-group" style="margin-bottom: 0;">
            <label for="filtro_semana">Semana</label>
            <input
                type="week"
                id="filtro_semana"
                name="semana"
                class="form-control"
                value="<?= htmlspecialchars((string)($semana_actual ?? '')) ?>"
            >
        </div>

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
            <label for="filtro_reporte">Estado semanal</label>
            <select id="filtro_reporte" name="reporte" class="form-control">
                <option value="" <?= (($filtro_reporte_actual ?? '') === '') ? 'selected' : '' ?>>Todos</option>
                <option value="sin" <?= (($filtro_reporte_actual ?? '') === 'sin') ? 'selected' : '' ?>>Pendientes por reportar</option>
                <option value="con" <?= (($filtro_reporte_actual ?? '') === 'con') ? 'selected' : '' ?>>Con reporte esta semana</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0; display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= PUBLIC_URL ?>?url=asistencias" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<?php if (!empty($sectionsVisibles)): ?>
<div class="asistencia-semanal-columns">
    <div class="asistencia-semanal-column">
        <div class="asistencia-semanal-column-title">
            <i class="bi bi-check2-circle"></i> Reportaron esta semana (<?= (int)count($seccionesReportaron) ?>)
        </div>

        <?php if (!empty($seccionesReportaron)): ?>
            <?php foreach ($seccionesReportaron as $section): ?>
                <div class="section-collapse asistencia-simple-card">
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                        <div class="collapse-title" style="margin-bottom:0;">
                            <i class="bi bi-check2-square"></i> <?= htmlspecialchars($section['label']) ?>
                        </div>
                        <a class="view-group-btn" href="<?= PUBLIC_URL ?>?url=asistencias/porCelula&id=<?= (int)$section['id_celula'] ?>">Ver detalle</a>
                    </div>

                    <div class="section-meta" style="margin-top:10px;">
                        <span class="meta-pill">Registros semana: <?= number_format((int)$section['total_registros']) ?></span>
                        <span class="meta-pill" style="background:#e9f8ef; border-color:#c6ebd4; color:#1f7a44;">Reportó esta semana</span>
                    </div>

                    <div class="entrego-sobre-row">
                        <label class="entrego-sobre-label">
                            <input
                                type="checkbox"
                                class="entrego-sobre-check"
                                data-id-celula="<?= (int)$section['id_celula'] ?>"
                                data-semana-inicio="<?= htmlspecialchars((string)($semana_inicio ?? '')) ?>"
                                <?= !empty($section['entrego_sobre']) ? 'checked' : '' ?>
                            >
                            Entrego sobre
                        </label>
                        <span class="entrego-sobre-status" aria-live="polite"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="section-collapse asistencia-simple-card">
                <div class="section-meta"><span class="meta-pill">No hay células en esta lista</span></div>
            </div>
        <?php endif; ?>
    </div>

    <div class="asistencia-semanal-column">
        <div class="asistencia-semanal-column-title" style="color:#9b2e3a; border-color:#f0d0d4; background:#fff4f5;">
            <i class="bi bi-x-circle"></i> No reportaron esta semana (<?= (int)count($seccionesNoReportaron) ?>)
        </div>

        <?php if (!empty($seccionesNoReportaron)): ?>
            <?php foreach ($seccionesNoReportaron as $section): ?>
                <div class="section-collapse asistencia-simple-card">
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                        <div class="collapse-title" style="margin-bottom:0;">
                            <i class="bi bi-check2-square"></i> <?= htmlspecialchars($section['label']) ?>
                        </div>
                        <a class="view-group-btn" href="<?= PUBLIC_URL ?>?url=asistencias/porCelula&id=<?= (int)$section['id_celula'] ?>">Ver detalle</a>
                    </div>

                    <div class="section-meta" style="margin-top:10px;">
                        <span class="meta-pill">Registros semana: <?= number_format((int)$section['total_registros']) ?></span>
                        <span class="meta-pill" style="background:#fff0f1; border-color:#f0d0d4; color:#9b2e3a;">No reportó esta semana</span>
                    </div>

                    <div class="entrego-sobre-row">
                        <label class="entrego-sobre-label">
                            <input
                                type="checkbox"
                                class="entrego-sobre-check"
                                data-id-celula="<?= (int)$section['id_celula'] ?>"
                                data-semana-inicio="<?= htmlspecialchars((string)($semana_inicio ?? '')) ?>"
                                <?= !empty($section['entrego_sobre']) ? 'checked' : '' ?>
                            >
                            Entrego sobre
                        </label>
                        <span class="entrego-sobre-status" aria-live="polite"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="section-collapse asistencia-simple-card">
                <div class="section-meta"><span class="meta-pill">No hay células en esta lista</span></div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
    <div class="table-container">
        <p class="text-center" style="padding: 20px; color: #666; margin: 0;">No hay asistencias registradas</p>
    </div>
<?php endif; ?>

<style>
.asistencia-semanal-columns {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.asistencia-semanal-column {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.asistencia-semanal-column-title {
    border: 1px solid #c6ebd4;
    background: #f1fbf4;
    color: #1f7a44;
    border-radius: 10px;
    padding: 10px 12px;
    font-weight: 700;
}

.entrego-sobre-row {
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.entrego-sobre-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}

.entrego-sobre-check {
    width: 16px;
    height: 16px;
    accent-color: #2563eb;
}

.entrego-sobre-status {
    font-size: 12px;
    color: #64748b;
    min-height: 16px;
}

.entrego-sobre-status.success {
    color: #1f7a44;
}

.entrego-sobre-status.error {
    color: #b42318;
}

@media (max-width: 980px) {
    .asistencia-semanal-columns {
        grid-template-columns: 1fr;
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
        let liderActual = '<?= htmlspecialchars((string)($filtro_lider_actual ?? ''), ENT_QUOTES) ?>';

        function renderLideres() {
            const ministerioSeleccionado = ministerioSelect.value;
            const valorPrevioLider = String(liderSelect.value || '');
            liderSelect.innerHTML = '';

            const optionTodos = document.createElement('option');
            optionTodos.value = '';
            optionTodos.textContent = 'Todos';
            liderSelect.appendChild(optionTodos);

            const filtrados = lideres.filter(function(lider) {
                return !ministerioSeleccionado
                    ? true
                    : String(lider.Id_Ministerio || '') === String(ministerioSeleccionado);
            });

            filtrados.forEach(function(lider) {
                const option = document.createElement('option');
                option.value = String(lider.Id_Persona);
                option.textContent = lider.Nombre_Completo;
                liderSelect.appendChild(option);
            });

            const valorDeseado = valorPrevioLider !== '' ? valorPrevioLider : String(liderActual || '');
            const existeDeseado = Array.from(liderSelect.options).some(function(opt) {
                return opt.value === valorDeseado;
            });
            if (existeDeseado) {
                liderSelect.value = valorDeseado;
            }

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
        liderActual = '';

        const checks = Array.from(document.querySelectorAll('.entrego-sobre-check'));
        checks.forEach(function(check) {
            check.addEventListener('change', function() {
                const idCelula = String(check.dataset.idCelula || '');
                const semanaInicio = String(check.dataset.semanaInicio || '');
                const entregoSobre = check.checked ? '1' : '0';
                const status = check.closest('.entrego-sobre-row')?.querySelector('.entrego-sobre-status');
                const valorPrevio = !check.checked;

                check.disabled = true;
                if (status) {
                    status.textContent = 'Guardando...';
                    status.classList.remove('success', 'error');
                }

                fetch('<?= PUBLIC_URL ?>?url=asistencias/actualizarEntregoSobre', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json'
                    },
                    body: 'id_celula=' + encodeURIComponent(idCelula)
                        + '&semana_inicio=' + encodeURIComponent(semanaInicio)
                        + '&entrego_sobre=' + encodeURIComponent(entregoSobre)
                })
                .then(function(response) { return response.text(); })
                .then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Respuesta invalida');
                    }
                })
                .then(function(data) {
                    check.disabled = false;
                    if (!data || !data.success) {
                        throw new Error((data && data.error) ? data.error : 'No se pudo guardar');
                    }

                    if (status) {
                        status.textContent = 'Guardado';
                        status.classList.remove('error');
                        status.classList.add('success');
                        setTimeout(function() {
                            status.textContent = '';
                            status.classList.remove('success');
                        }, 1400);
                    }
                })
                .catch(function(error) {
                    check.disabled = false;
                    check.checked = valorPrevio;
                    if (status) {
                        status.textContent = 'Error: ' + error.message;
                        status.classList.remove('success');
                        status.classList.add('error');
                    }
                });
            });
        });
    })();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>

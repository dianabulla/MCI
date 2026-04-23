<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header" id="top-asistencias">
    <h2>Reporte Semanal de Asistencias</h2>
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

$queryPersistente = [];
if (!empty($_GET['semana'])) {
    $queryPersistente['semana'] = (string)$_GET['semana'];
}
if (!empty($_GET['ministerio'])) {
    $queryPersistente['ministerio'] = (string)$_GET['ministerio'];
}
if (!empty($_GET['lider'])) {
    $queryPersistente['lider'] = (string)$_GET['lider'];
}
if (!empty($_GET['reporte'])) {
    $queryPersistente['reporte'] = (string)$_GET['reporte'];
}
$returnUrlAsistencias = PUBLIC_URL . '?url=asistencias' . (!empty($queryPersistente) ? '&' . http_build_query($queryPersistente) : '');

$fechaSemanaAnterior = date('Y-m-d', strtotime('-7 days'));
try {
    $baseSemana = new DateTimeImmutable((string)($semana_inicio ?? date('Y-m-d')));
    $fechaSemanaAnterior = $baseSemana->modify('-7 days')->format('Y-m-d');
} catch (Throwable $e) {
    $fechaSemanaAnterior = date('Y-m-d', strtotime('-7 days'));
}

?>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="padding: 12px 16px;">
        <strong>Semana:</strong> <?= htmlspecialchars((string)($semana_inicio ?? '')) ?> al <?= htmlspecialchars((string)($semana_fin ?? '')) ?>
    </div>
</div>

<div class="form-container" style="margin-bottom: 20px;">
    <form method="GET" class="filter-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; align-items: end;">
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
<div class="dashboard-grid asistencia-summary-grid" style="grid-template-columns: repeat(2, minmax(0, 320px)); margin:18px 0;">
    <button type="button" class="dashboard-card asistencia-summary-card is-active" data-target-tabla="reportaron" style="border-left-color:#1f7a44; text-align:left; cursor:pointer;">
        <h3>Reportaron esta semana</h3>
        <div class="value" style="color:#1f7a44;"><?= (int)count($seccionesReportaron) ?></div>
        <small style="color:#637087;">Clic para ver el listado completo ordenado.</small>
    </button>

    <button type="button" class="dashboard-card asistencia-summary-card" data-target-tabla="no-reportaron" style="border-left-color:#c92a2a; text-align:left; cursor:pointer;">
        <h3>No reportaron esta semana</h3>
        <div class="value" style="color:#c92a2a;"><?= (int)count($seccionesNoReportaron) ?></div>
        <small style="color:#637087;">Clic para ver el listado completo ordenado.</small>
    </button>
</div>

<div id="tabla-reportaron" class="card asistencia-detalle-card" style="margin-bottom: 18px;">
    <div class="asistencia-detalle-header">
        <div>
            <h3 style="margin-bottom:4px;">Células que reportaron</h3>
            <small style="color:#60708a;">Vista completa de células con reporte para la semana seleccionada</small>
        </div>
    </div>

    <div class="table-container asistencia-table-wrap asistencia-table-wrap--full">
        <table class="data-table asistencia-data-table">
            <thead>
                <tr>
                    <th>Célula</th>
                    <th>Ministerio</th>
                    <th>Estado</th>
                    <th>Entregó sobre</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($seccionesReportaron)): ?>
                    <?php foreach ($seccionesReportaron as $section): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string)($section['label'] ?? 'Sin célula')) ?></strong></td>
                            <td><?= htmlspecialchars((string)($section['ministerio'] ?? 'Sin ministerio')) ?></td>
                            <td><span class="meta-pill" style="background:#e9f8ef; border-color:#c6ebd4; color:#1f7a44;">Reportó esta semana</span></td>
                            <td>
                                <div class="entrego-sobre-row entrego-sobre-row--table">
                                    <label class="entrego-sobre-label">
                                        <span>Sí</span>
                                        <input
                                            type="checkbox"
                                            class="entrego-sobre-check"
                                            data-id-celula="<?= (int)$section['id_celula'] ?>"
                                            data-semana-inicio="<?= htmlspecialchars((string)($semana_inicio ?? '')) ?>"
                                            <?= !empty($section['entrego_sobre']) ? 'checked' : '' ?>
                                        >
                                    </label>
                                    <span class="entrego-sobre-status" aria-live="polite"></span>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <a class="btn btn-sm btn-secondary" href="<?= PUBLIC_URL ?>?url=asistencias/porCelula&id=<?= (int)$section['id_celula'] ?>&return_url=<?= urlencode($returnUrlAsistencias) ?>">Ver detalle</a>
                                    <a class="btn btn-sm btn-primary" href="<?= PUBLIC_URL ?>?url=asistencias/registrar&celula=<?= (int)$section['id_celula'] ?>&fecha=<?= urlencode($fechaSemanaAnterior) ?>&return_url=<?= urlencode($returnUrlAsistencias) ?>" title="Registrar asistencia en una semana anterior">Reportar semana anterior</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="asistencia-empty-cell">No hay células en esta lista</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="tabla-no-reportaron" class="card asistencia-detalle-card" hidden>
    <div class="asistencia-detalle-header">
        <div>
            <h3 style="margin-bottom:4px;">Células que no reportaron</h3>
            <small style="color:#60708a;">Vista completa de células pendientes por reportar en la semana seleccionada</small>
        </div>
    </div>

    <div class="table-container asistencia-table-wrap asistencia-table-wrap--full">
        <table class="data-table asistencia-data-table">
            <thead>
                <tr>
                    <th>Célula</th>
                    <th>Ministerio</th>
                    <th>Estado</th>
                    <th>Entregó sobre</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($seccionesNoReportaron)): ?>
                    <?php foreach ($seccionesNoReportaron as $section): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string)($section['label'] ?? 'Sin célula')) ?></strong></td>
                            <td><?= htmlspecialchars((string)($section['ministerio'] ?? 'Sin ministerio')) ?></td>
                            <td><span class="meta-pill" style="background:#fff0f1; border-color:#f0d0d4; color:#9b2e3a;">No reportó esta semana</span></td>
                            <td>
                                <div class="entrego-sobre-row entrego-sobre-row--table">
                                    <label class="entrego-sobre-label">
                                        <span>Sí</span>
                                        <input
                                            type="checkbox"
                                            class="entrego-sobre-check"
                                            data-id-celula="<?= (int)$section['id_celula'] ?>"
                                            data-semana-inicio="<?= htmlspecialchars((string)($semana_inicio ?? '')) ?>"
                                            <?= !empty($section['entrego_sobre']) ? 'checked' : '' ?>
                                        >
                                    </label>
                                    <span class="entrego-sobre-status" aria-live="polite"></span>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <a class="btn btn-sm btn-secondary" href="<?= PUBLIC_URL ?>?url=asistencias/porCelula&id=<?= (int)$section['id_celula'] ?>&return_url=<?= urlencode($returnUrlAsistencias) ?>">Ver detalle</a>
                                    <a class="btn btn-sm btn-primary" href="<?= PUBLIC_URL ?>?url=asistencias/registrar&celula=<?= (int)$section['id_celula'] ?>&fecha=<?= urlencode($fechaSemanaAnterior) ?>&return_url=<?= urlencode($returnUrlAsistencias) ?>" title="Registrar asistencia en una semana anterior">Reportar semana anterior</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="asistencia-empty-cell">No hay células en esta lista</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
    <div class="table-container">
        <p class="text-center" style="padding: 20px; color: #666; margin: 0;">No hay asistencias registradas</p>
    </div>
<?php endif; ?>

<style>
.asistencia-summary-grid {
    gap: 14px;
}

.asistencia-summary-card {
    appearance: none;
    border-top: 0;
    border-right: 0;
    border-bottom: 0;
    width: 100%;
    transition: transform 0.18s ease, box-shadow 0.18s ease, outline 0.18s ease;
}

.asistencia-summary-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(15, 35, 61, 0.08);
}

.asistencia-summary-card.is-active {
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12), 0 10px 22px rgba(15, 35, 61, 0.08);
}

.asistencia-summary-card:focus-visible {
    outline: 3px solid rgba(37, 99, 235, 0.22);
    outline-offset: 2px;
}

.asistencia-table-wrap {
    margin-top: 0;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    box-shadow: none;
    border: 1px solid #e2e8f0;
}

.asistencia-table-wrap--full {
    margin-top: 14px;
}

.asistencia-detalle-card {
    margin-bottom: 18px;
    scroll-margin-top: 20px;
}

.asistencia-detalle-card:target {
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
}

.asistencia-detalle-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
}

.asistencia-data-table th:nth-child(5),
.asistencia-data-table td:nth-child(5) {
    white-space: nowrap;
}

.asistencia-data-table td {
    vertical-align: middle;
}

.asistencia-data-table th,
.asistencia-data-table td {
    padding: 6px 8px;
    font-size: 12px;
    line-height: 1.25;
}

.asistencia-data-table th {
    font-size: 11.5px;
}

.asistencia-data-table .meta-pill {
    padding: 3px 8px;
    font-size: 11px;
}

.asistencia-data-table .btn {
    padding: 4px 8px;
    font-size: 11px;
    line-height: 1.2;
}

.asistencia-empty-cell {
    text-align: center;
    color: #64748b;
    padding: 18px 12px;
}

.entrego-sobre-row {
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.entrego-sobre-row--table {
    margin-top: 0;
    gap: 4px;
    justify-content: flex-start;
}

.entrego-sobre-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
    color: #334155;
    cursor: pointer;
    font-size: 12px;
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
    .asistencia-summary-grid {
        grid-template-columns: 1fr !important;
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

    (function() {
        const tarjetasResumen = Array.from(document.querySelectorAll('.asistencia-summary-card'));
        if (!tarjetasResumen.length) {
            return;
        }

        const tablasDetalle = {
            reportaron: document.getElementById('tabla-reportaron'),
            'no-reportaron': document.getElementById('tabla-no-reportaron')
        };

        function activarTabla(target, shouldScroll) {
            tarjetasResumen.forEach(function(tarjeta) {
                tarjeta.classList.toggle('is-active', String(tarjeta.dataset.targetTabla || '') === target);
            });

            Object.keys(tablasDetalle).forEach(function(key) {
                const panel = tablasDetalle[key];
                if (!panel) {
                    return;
                }

                if (key === target) {
                    panel.removeAttribute('hidden');
                } else {
                    panel.setAttribute('hidden', 'hidden');
                }
            });

            const panelActivo = tablasDetalle[target];
            if (shouldScroll && panelActivo) {
                panelActivo.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        tarjetasResumen.forEach(function(tarjeta) {
            tarjeta.addEventListener('click', function() {
                const target = String(tarjeta.dataset.targetTabla || 'reportaron');
                activarTabla(target, true);
            });
        });

        activarTabla('reportaron', false);
    })();

</script>

<?php include VIEWS . '/layout/footer.php'; ?>

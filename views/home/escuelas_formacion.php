<?php include VIEWS . '/layout/header.php'; ?>

<?php
$reporteUniversidadVida = $reporteUniversidadVida ?? ['total' => 0, 'rows' => []];
$filtroMinisterio = (string)($filtro_ministerio ?? '');
$filtroLider = (string)($filtro_lider ?? '');
$filtroCelula = (string)($filtro_celula ?? '');
$inscripcionesPublicas = $inscripciones_publicas ?? [];
$resumenInscripciones = $resumen_inscripciones ?? ['total' => 0, 'universidad_vida' => 0, 'capacitacion_destino' => 0, 'otros' => 0];
$filtroInscPrograma = (string)($filtro_insc_programa ?? '');
$filtroInscBuscar = (string)($filtro_insc_buscar ?? '');

$exportUrl = PUBLIC_URL . '?url=home/escuelas-formacion/exportar&' . http_build_query([
    'ministerio' => $filtroMinisterio,
    'lider' => $filtroLider,
    'celula' => $filtroCelula,
]);
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;">Escuelas de Formación</h2>
        <small style="color:#637087;">Reporte de personas en Universidad de la Vida.</small>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= htmlspecialchars($exportUrl) ?>" class="btn btn-secondary">Exportar CSV</a>
        <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/registro-publico" class="btn btn-secondary" target="_blank" rel="noopener">Formulario público</a>
        <a href="<?= PUBLIC_URL ?>?url=home" class="btn btn-primary">Volver al panel</a>
    </div>
</div>

<div class="card report-card" style="margin-bottom:18px; padding:14px;">
    <form method="GET" action="<?= PUBLIC_URL ?>" class="filters-inline" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <input type="hidden" name="url" value="home/escuelas-formacion">

        <div class="form-group" style="margin:0; min-width:220px;">
            <label for="filtro_ministerio">Ministerio</label>
            <select id="filtro_ministerio" name="ministerio" class="form-control">
                <option value="">Todos</option>
                <?php foreach (($ministerios_disponibles ?? []) as $ministerio): ?>
                    <option value="<?= (int)($ministerio['Id_Ministerio'] ?? 0) ?>" <?= $filtroMinisterio === (string)($ministerio['Id_Ministerio'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)($ministerio['Nombre_Ministerio'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin:0; min-width:220px;">
            <label for="filtro_lider">Líder</label>
            <select id="filtro_lider" name="lider" class="form-control">
                <option value="">Todos</option>
                <?php foreach (($lideres_disponibles ?? []) as $lider): ?>
                    <option value="<?= (int)($lider['Id_Persona'] ?? 0) ?>" <?= $filtroLider === (string)($lider['Id_Persona'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)($lider['Nombre_Completo'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin:0; min-width:220px;">
            <label for="filtro_celula">Célula</label>
            <select id="filtro_celula" name="celula" class="form-control">
                <option value="">Todas</option>
                <?php foreach (($celulas_disponibles ?? []) as $celula): ?>
                    <option value="<?= (int)($celula['Id_Celula'] ?? 0) ?>" <?= $filtroCelula === (string)($celula['Id_Celula'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)($celula['Nombre_Celula'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filters-actions" style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">Aplicar</button>
            <a href="<?= PUBLIC_URL ?>?url=home/escuelas-formacion" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="dashboard-grid" style="grid-template-columns: repeat(1, minmax(0, 320px)); margin-bottom:18px;">
    <div class="dashboard-card" style="border-left-color:#1e4a89;">
        <h3>Universidad de la Vida</h3>
        <div class="value" style="color:#1e4a89;"><?= (int)($reporteUniversidadVida['total'] ?? 0) ?></div>
        <small style="color:#637087;">Personas activas en etapa Consolidar.</small>
    </div>
</div>

<div class="card report-card" style="padding:14px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="margin:0;">Detalle Universidad de la Vida</h3>
        <small style="color:#637087;">Total registros: <?= (int)($reporteUniversidadVida['total'] ?? 0) ?></small>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Persona</th>
                    <th>Ministerio</th>
                    <th>Líder</th>
                    <th style="width:170px;">Asiste</th>
                    <th style="width:120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reporteUniversidadVida['rows'])): ?>
                    <?php foreach (($reporteUniversidadVida['rows'] ?? []) as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($row['nombre'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['ministerio'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['lider'] ?? '')) ?></td>
                            <td class="text-center">
                                <div class="uv-state-options">
                                    <label class="uv-state-option <?= !empty($row['va']) ? 'is-active' : '' ?>">
                                        <input
                                            type="radio"
                                            class="js-uv-estado"
                                            name="uv_estado_<?= (int)($row['id_persona'] ?? 0) ?>"
                                            data-persona="<?= (int)($row['id_persona'] ?? 0) ?>"
                                            data-value="1"
                                            <?= !empty($row['va']) ? 'checked' : '' ?>
                                        >
                                        <span>Sí</span>
                                    </label>
                                    <label class="uv-state-option <?= empty($row['va']) ? 'is-active' : '' ?>">
                                        <input
                                            type="radio"
                                            class="js-uv-estado"
                                            name="uv_estado_<?= (int)($row['id_persona'] ?? 0) ?>"
                                            data-persona="<?= (int)($row['id_persona'] ?? 0) ?>"
                                            data-value="0"
                                            <?= empty($row['va']) ? 'checked' : '' ?>
                                        >
                                        <span>No</span>
                                    </label>
                                </div>
                            </td>
                            <td>
                                <div class="table-actions-inline">
                                    <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= (int)($row['id_persona'] ?? 0) ?>" class="action-icon-btn action-icon-info" title="Ver" aria-label="Ver">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (AuthController::tienePermiso('personas', 'editar')): ?>
                                    <a href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= (int)($row['id_persona'] ?? 0) ?>&panel=escalera" class="action-icon-btn action-icon-warning" title="Editar" aria-label="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay personas para Universidad de la Vida con estos filtros.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="dashboard-grid" style="grid-template-columns: repeat(3, minmax(0, 320px)); margin:18px 0;">
    <div class="dashboard-card" style="border-left-color:#0f6a66;">
        <h3>Inscripciones públicas</h3>
        <div class="value" style="color:#0f6a66;"><?= (int)($resumenInscripciones['total'] ?? 0) ?></div>
        <small style="color:#637087;">Total general registrado.</small>
    </div>
    <div class="dashboard-card" style="border-left-color:#1e4a89;">
        <h3>Universidad de la Vida</h3>
        <div class="value" style="color:#1e4a89;"><?= (int)($resumenInscripciones['universidad_vida'] ?? 0) ?></div>
        <small style="color:#637087;">Registros en este programa.</small>
    </div>
    <div class="dashboard-card" style="border-left-color:#7a4e08;">
        <h3>Capacitación Destino</h3>
        <div class="value" style="color:#7a4e08;"><?= (int)($resumenInscripciones['capacitacion_destino'] ?? 0) ?></div>
        <small style="color:#637087;">Registros en este programa.</small>
    </div>
</div>

<div class="card report-card" style="margin-bottom:18px; padding:14px;">
    <form method="GET" action="<?= PUBLIC_URL ?>" class="filters-inline" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <input type="hidden" name="url" value="home/escuelas-formacion">
        <input type="hidden" name="ministerio" value="<?= htmlspecialchars($filtroMinisterio) ?>">
        <input type="hidden" name="lider" value="<?= htmlspecialchars($filtroLider) ?>">
        <input type="hidden" name="celula" value="<?= htmlspecialchars($filtroCelula) ?>">

        <div class="form-group" style="margin:0; min-width:220px;">
            <label for="insc_programa">Programa (inscripciones)</label>
            <select id="insc_programa" name="insc_programa" class="form-control">
                <option value="" <?= $filtroInscPrograma === '' ? 'selected' : '' ?>>Todos</option>
                <option value="universidad_vida" <?= $filtroInscPrograma === 'universidad_vida' ? 'selected' : '' ?>>Universidad de la Vida</option>
                <option value="capacitacion_destino" <?= $filtroInscPrograma === 'capacitacion_destino' ? 'selected' : '' ?>>Capacitación Destino</option>
            </select>
        </div>

        <div class="form-group" style="margin:0; min-width:280px;">
            <label for="insc_buscar">Buscar</label>
            <input type="text" id="insc_buscar" name="insc_buscar" class="form-control" placeholder="Nombre, cédula, teléfono o líder" value="<?= htmlspecialchars($filtroInscBuscar) ?>">
        </div>

        <div class="filters-actions" style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">Filtrar inscripciones</button>
            <a href="<?= PUBLIC_URL ?>?url=home/escuelas-formacion&ministerio=<?= urlencode($filtroMinisterio) ?>&lider=<?= urlencode($filtroLider) ?>&celula=<?= urlencode($filtroCelula) ?>" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="card report-card" style="padding:14px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="margin:0;">Registros del formulario público</h3>
        <small style="color:#637087;">Mostrando <?= (int)count($inscripcionesPublicas) ?> registros recientes (máx. 300)</small>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Nombre</th>
                    <th>Género</th>
                    <th>Cédula</th>
                    <th>Teléfono</th>
                    <th>Líder</th>
                    <th>Ministerio</th>
                    <th>Programa</th>
                    <th>Asistencia clase</th>
                    <th>Persona</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($inscripcionesPublicas)): ?>
                    <?php foreach ($inscripcionesPublicas as $ins): ?>
                        <?php
                        $programaRaw = (string)($ins['Programa'] ?? '');
                        $programaLabel = $programaRaw === 'universidad_vida'
                            ? 'Universidad de la Vida'
                            : ($programaRaw === 'capacitacion_destino' ? 'Capacitación Destino' : $programaRaw);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($ins['Fecha_Registro'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($ins['Nombre'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($ins['Genero'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($ins['Cedula'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($ins['Telefono'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($ins['Lider'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($ins['Nombre_Ministerio'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)$programaLabel) ?></td>
                            <td>
                                <select
                                    class="form-control form-control-sm js-insc-asistencia"
                                    data-inscripcion="<?= (int)($ins['Id_Inscripcion'] ?? 0) ?>"
                                    style="min-width:120px;"
                                >
                                    <?php $asistioClase = array_key_exists('Asistio_Clase', $ins) ? $ins['Asistio_Clase'] : null; ?>
                                    <option value="" <?= $asistioClase === null ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="1" <?= (string)$asistioClase === '1' ? 'selected' : '' ?>>Sí</option>
                                    <option value="0" <?= (string)$asistioClase === '0' ? 'selected' : '' ?>>No</option>
                                </select>
                            </td>
                            <td>
                                <?php if ((int)($ins['Id_Persona'] ?? 0) > 0): ?>
                                    <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= (int)($ins['Id_Persona'] ?? 0) ?>" class="action-icon-btn action-icon-info" title="Ver persona" aria-label="Ver persona">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="color:#8a94a8;">Sin vínculo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center">No hay registros de inscripciones para los filtros seleccionados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    const endpoint = <?= json_encode(PUBLIC_URL . '?url=home/escuelas-formacion/actualizar-estado') ?>;
    const endpointAsistencia = <?= json_encode(PUBLIC_URL . '?url=home/escuelas-formacion/actualizar-asistencia-clase') ?>;
    const updateActiveState = (name) => {
        document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
            const label = input.closest('.uv-state-option');
            if (label) {
                label.classList.toggle('is-active', input.checked);
            }
        });
    };

    document.querySelectorAll('.js-uv-estado').forEach((input) => {
        input.addEventListener('change', async () => {
            const groupName = input.name;
            const radios = Array.from(document.querySelectorAll(`input[name="${groupName}"]`));
            const previous = radios.map((radio) => ({ radio, checked: radio.defaultChecked }));

            try {
                const formData = new FormData();
                formData.append('id_persona', String(input.dataset.persona || '0'));
                formData.append('programa', 'universidad_vida');
                formData.append('va', String(input.dataset.value || '0'));

                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!response.ok || !data.ok) {
                    throw new Error((data && data.error) || 'No se pudo guardar');
                }
                radios.forEach((radio) => {
                    radio.defaultChecked = radio.checked;
                });
                updateActiveState(groupName);

                // Al marcar "Si", refresca para mostrar la inscripcion en el listado inferior.
                if (String(input.dataset.value || '0') === '1') {
                    window.location.reload();
                }
            } catch (error) {
                previous.forEach(({ radio, checked }) => {
                    radio.checked = checked;
                });
                updateActiveState(groupName);
                alert(error.message || 'No se pudo guardar el estado');
            }
        });
    });

    document.querySelectorAll('.js-insc-asistencia').forEach((select) => {
        select.addEventListener('change', async () => {
            const valorAnterior = select.dataset.previousValue ?? '';

            try {
                const formData = new FormData();
                formData.append('id_inscripcion', String(select.dataset.inscripcion || '0'));
                formData.append('asistio', String(select.value || ''));

                const response = await fetch(endpointAsistencia, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();
                if (!response.ok || !data.ok) {
                    throw new Error((data && data.error) || 'No se pudo guardar asistencia de clase');
                }

                select.dataset.previousValue = String(select.value || '');
            } catch (error) {
                select.value = valorAnterior;
                alert(error.message || 'No se pudo guardar asistencia de clase');
            }
        });

        select.dataset.previousValue = String(select.value || '');
    });
}());
</script>

<style>
.uv-state-options {
    display:inline-flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
}

.uv-state-option {
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:4px 8px;
    border:1px solid #d6e0ee;
    border-radius:999px;
    background:#f8fbff;
    font-weight:600;
    cursor:pointer;
}

.uv-state-option.is-active {
    border-color:#1e4a89;
    background:#eaf2ff;
    color:#1e4a89;
}

.table-actions-inline {
    display:flex;
    gap:6px;
    align-items:center;
    justify-content:center;
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>
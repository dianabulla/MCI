<?php include VIEWS . '/layout/header.php'; ?>

<?php
$configModulo = $config_modulo ?? [];
$tituloModulo = (string)($configModulo['titulo'] ?? 'Modulo');
$rutaBase = (string)($configModulo['ruta_base'] ?? 'home');
$rutaAsistencias = (string)($configModulo['ruta_asistencias'] ?? $rutaBase);

$filtroMinisterio = (string)($filtro_ministerio ?? '');
$filtroFechaDesde = (string)($filtro_fecha_desde ?? '');
$filtroFechaHasta = (string)($filtro_fecha_hasta ?? '');
$programaReporte = (string)($programa_reporte ?? '');
$programaReporteLabel = (string)($programa_reporte_label ?? 'Programa');
$rowsAsistencia = $rows_asistencia ?? [];
$fechasClases = $fechas_clases ?? [];
$totalClases = (int)($total_clases ?? 5);
if ($totalClases <= 0) {
    $totalClases = 5;
}

 $ministerioLabelSeleccionado = 'Todos';
if ($filtroMinisterio !== '') {
    foreach (($ministerios_disponibles ?? []) as $ministerioItem) {
        if ((string)($ministerioItem['Id_Ministerio'] ?? '') === $filtroMinisterio) {
            $ministerioLabelSeleccionado = (string)($ministerioItem['Nombre_Ministerio'] ?? 'Todos');
            break;
        }
    }
}

$filtrosActivos = 0;
if ($filtroMinisterio !== '') { $filtrosActivos++; }
if ($filtroFechaDesde !== '') { $filtrosActivos++; }
if ($filtroFechaHasta !== '') { $filtrosActivos++; }
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;"><?= htmlspecialchars($tituloModulo) ?></h2>
        <small style="color:#637087;">Vista Asistencias. Programa actual: <strong><?= htmlspecialchars($programaReporteLabel) ?></strong>.</small>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaBase) ?>" class="btn btn-secondary">Registro</a>
        <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaAsistencias) ?>" class="btn btn-primary">Asistencias</a>
        <a href="<?= PUBLIC_URL ?>?url=home" class="btn btn-secondary">Volver al panel</a>
    </div>
</div>

<div class="card report-card" style="margin-bottom:12px; padding:10px 14px; background:#f6fbff; border-color:#d9e6f5;">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;">
        <div style="font-weight:600;color:#244a74;">
            Filtros activos: <?= (int)$filtrosActivos ?>
        </div>
        <small style="color:#4f6480;">Encabezado por clase con fecha editable.</small>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
        <span class="filter-chip">Ministerio: <?= htmlspecialchars($ministerioLabelSeleccionado) ?></span>
        <span class="filter-chip">Desde: <?= $filtroFechaDesde !== '' ? htmlspecialchars($filtroFechaDesde) : 'Sin filtro' ?></span>
        <span class="filter-chip">Hasta: <?= $filtroFechaHasta !== '' ? htmlspecialchars($filtroFechaHasta) : 'Sin filtro' ?></span>
    </div>
</div>

<div class="card report-card" style="margin-bottom:18px; padding:14px;">
    <form method="GET" action="<?= PUBLIC_URL ?>" class="filters-inline" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <input type="hidden" name="url" value="<?= htmlspecialchars($rutaAsistencias) ?>">

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

        <div class="form-group" style="margin:0; min-width:180px;">
            <label for="filtro_fecha_desde">Desde</label>
            <input type="date" id="filtro_fecha_desde" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($filtroFechaDesde) ?>">
        </div>

        <div class="form-group" style="margin:0; min-width:180px;">
            <label for="filtro_fecha_hasta">Hasta</label>
            <input type="date" id="filtro_fecha_hasta" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($filtroFechaHasta) ?>">
        </div>

        <input type="hidden" name="insc_programa" value="<?= htmlspecialchars($programaReporte) ?>">

        <div class="filters-actions" style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">Aplicar</button>
            <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaAsistencias) ?>" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="card report-card" style="padding:14px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="margin:0;">Matriz de asistencias: <?= htmlspecialchars($programaReporteLabel) ?></h3>
        <small style="color:#637087;">Marca X por clase. Total personas: <?= (int)count($rowsAsistencia) ?></small>
    </div>

    <div class="table-container">
        <table class="data-table asistencia-matriz-table" data-modulo="<?= htmlspecialchars((string)($configModulo['modulo'] ?? '')) ?>" data-programa="<?= htmlspecialchars($programaReporte) ?>">
            <thead>
                <tr>
                    <th class="sticky-col-left">Nombre Completo</th>
                    <th class="sticky-col-left-2">Lider</th>
                    <?php for ($i = 1; $i <= $totalClases; $i++): ?>
                        <th class="th-clase">
                            <div class="clase-head">CL<?= $i ?></div>
                            <input
                                type="date"
                                class="form-control form-control-sm js-fecha-clase"
                                data-clase="<?= $i ?>"
                                value="<?= htmlspecialchars((string)($fechasClases[$i] ?? '')) ?>"
                            >
                        </th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rowsAsistencia)): ?>
                    <?php foreach ($rowsAsistencia as $row): ?>
                        <tr>
                            <td class="sticky-col-left col-nowrap col-nombre"><?= htmlspecialchars((string)($row['nombre'] ?? '')) ?></td>
                            <td class="sticky-col-left-2 col-nowrap col-lider"><?= htmlspecialchars((string)($row['lider'] ?? '')) ?></td>
                            <?php for ($i = 1; $i <= $totalClases; $i++): ?>
                                <?php $activo = !empty($row['clases'][$i]); ?>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn-matriz-x js-asistencia-cell <?= $activo ? 'is-active' : '' ?>"
                                        data-id-persona="<?= (int)($row['id_persona'] ?? 0) ?>"
                                        data-clase="<?= $i ?>"
                                        data-activo="<?= $activo ? '1' : '0' ?>"
                                        aria-label="Marcar asistencia clase <?= $i ?>"
                                    ><?= $activo ? 'X' : '' ?></button>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= 2 + $totalClases ?>" class="text-center">No hay personas para este programa con los filtros seleccionados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    const endpointAsistencia = <?= json_encode(PUBLIC_URL . '?url=home/escuelas-formacion/actualizar-matriz-asistencia') ?>;
    const endpointFecha = <?= json_encode(PUBLIC_URL . '?url=home/escuelas-formacion/actualizar-fecha-clase') ?>;
    const table = document.querySelector('.asistencia-matriz-table');

    if (!table) {
        return;
    }

    const modulo = String(table.dataset.modulo || '');
    const programa = String(table.dataset.programa || '');

    async function postForm(url, payload) {
        const formData = new FormData();
        Object.keys(payload).forEach((key) => {
            formData.append(key, String(payload[key]));
        });

        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const data = await response.json();
        if (!response.ok || !data.ok) {
            throw new Error((data && data.error) || 'No se pudo guardar');
        }
        return data;
    }

    table.querySelectorAll('.js-asistencia-cell').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (btn.disabled) {
                return;
            }

            const idPersona = parseInt(btn.dataset.idPersona || '0', 10);
            const clase = parseInt(btn.dataset.clase || '0', 10);
            if (idPersona <= 0 || clase <= 0) {
                return;
            }

            const nuevoEstado = String(btn.dataset.activo || '0') === '1' ? 0 : 1;
            btn.disabled = true;

            try {
                await postForm(endpointAsistencia, {
                    id_persona: idPersona,
                    modulo: modulo,
                    programa: programa,
                    clase: clase,
                    asistio: nuevoEstado
                });

                btn.dataset.activo = String(nuevoEstado);
                btn.classList.toggle('is-active', nuevoEstado === 1);
                btn.textContent = nuevoEstado === 1 ? 'X' : '';
            } catch (error) {
                alert(error.message || 'No se pudo guardar la asistencia');
            } finally {
                btn.disabled = false;
            }
        });
    });

    table.querySelectorAll('.js-fecha-clase').forEach((input) => {
        input.addEventListener('change', async () => {
            const clase = parseInt(input.dataset.clase || '0', 10);
            if (clase <= 0) {
                return;
            }

            input.disabled = true;
            try {
                await postForm(endpointFecha, {
                    modulo: modulo,
                    programa: programa,
                    clase: clase,
                    fecha: input.value || ''
                });
            } catch (error) {
                alert(error.message || 'No se pudo guardar la fecha');
            } finally {
                input.disabled = false;
            }
        });
    });
}());
</script>

<style>
.filter-chip {
    display:inline-flex;
    align-items:center;
    border:1px solid #c8d7ea;
    background:#ffffff;
    color:#2a4a73;
    border-radius:999px;
    padding:4px 10px;
    font-size:12px;
    font-weight:600;
}

.asistencia-matriz-table {
    table-layout: auto;
    width: max-content;
    min-width: 100%;
}

.asistencia-matriz-table th,
.asistencia-matriz-table td {
    vertical-align: middle;
    padding-top: 8px !important;
    padding-bottom: 8px !important;
}

.asistencia-matriz-table .col-nombre { min-width: 260px; }
.asistencia-matriz-table .col-lider { min-width: 210px; }

.th-clase {
    min-width: 120px;
    text-align: center;
}

.clase-head {
    font-weight: 700;
    margin-bottom: 4px;
}

.btn-matriz-x {
    width: 36px;
    height: 32px;
    border: 1px solid #ced7e8;
    border-radius: 7px;
    background: #f8fbff;
    color: #1f4f92;
    font-weight: 700;
    cursor: pointer;
}

.btn-matriz-x.is-active {
    border-color: #2f65b5;
    background: #e8f2ff;
}

.btn-matriz-x:disabled {
    opacity: 0.6;
    cursor: wait;
}

.col-nowrap {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sticky-col-left,
.sticky-col-left-2 {
    position: sticky;
    z-index: 2;
    background: #fff;
}

.sticky-col-left {
    left: 0;
    z-index: 3;
}

.sticky-col-left-2 {
    left: 260px;
    z-index: 3;
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>

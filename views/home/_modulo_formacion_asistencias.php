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
$fechasClasesHombres = $fechas_clases_hombres ?? $fechasClases;
$fechasClasesMujeres = $fechas_clases_mujeres ?? $fechasClases;
$vistaActual = (string)($vista_actual ?? 'asistencias');
$registroActivo = $vistaActual !== 'asistencias';
$asistenciasActivo = $vistaActual === 'asistencias';
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
    <div class="header-actions">
        <div class="action-group action-group-nav">
            <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaBase) ?>" class="action-pill <?= $registroActivo ? 'is-active' : '' ?>" <?= $registroActivo ? 'aria-current="page"' : '' ?>>Registro</a>
            <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaAsistencias) ?>" class="action-pill <?= $asistenciasActivo ? 'is-active' : '' ?>" <?= $asistenciasActivo ? 'aria-current="page"' : '' ?>>Asistencias</a>
        </div>
        <div class="action-group">
            <a href="<?= PUBLIC_URL ?>?url=home" class="action-pill">Volver al panel</a>
        </div>
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
    <?php
    $rowsAsistenciaHombres = [];
    $rowsAsistenciaMujeres = [];
    $rowsAsistenciaSinClasificar = [];

    foreach ($rowsAsistencia as $rowTmp) {
        $generoRegistro = strtolower(trim((string)($rowTmp['genero'] ?? '')));
        if (strpos($generoRegistro, 'mujer') !== false || strpos($generoRegistro, 'femen') !== false) {
            $rowsAsistenciaMujeres[] = $rowTmp;
            continue;
        }

        if (strpos($generoRegistro, 'hombre') !== false || strpos($generoRegistro, 'mascul') !== false || $generoRegistro === 'm') {
            $rowsAsistenciaHombres[] = $rowTmp;
            continue;
        }

        $rowsAsistenciaSinClasificar[] = $rowTmp;
    }

    $asistenciaVistaInicial = !empty($rowsAsistenciaHombres) ? 'hombres' : (!empty($rowsAsistenciaMujeres) ? 'mujeres' : 'hombres');
    $asistenciaHombresActivo = $asistenciaVistaInicial === 'hombres';
    $asistenciaMujeresActivo = $asistenciaVistaInicial === 'mujeres';

    $hombresRegistrados = (int)count($rowsAsistenciaHombres);
    $mujeresRegistradas = (int)count($rowsAsistenciaMujeres);

    $hombresAsistieron = 0;
    foreach ($rowsAsistenciaHombres as $rowTmp) {
        $asistio = false;
        for ($i = 1; $i <= $totalClases; $i++) {
            if (!empty($rowTmp['clases'][$i])) {
                $asistio = true;
                break;
            }
        }
        if ($asistio) {
            $hombresAsistieron++;
        }
    }

    $mujeresAsistieron = 0;
    foreach ($rowsAsistenciaMujeres as $rowTmp) {
        $asistio = false;
        for ($i = 1; $i <= $totalClases; $i++) {
            if (!empty($rowTmp['clases'][$i])) {
                $asistio = true;
                break;
            }
        }
        if ($asistio) {
            $mujeresAsistieron++;
        }
    }

    $hombresPendientes = max(0, $hombresRegistrados - $hombresAsistieron);
    $mujeresPendientes = max(0, $mujeresRegistradas - $mujeresAsistieron);
    ?>

    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="margin:0;">Matriz de asistencias: <?= htmlspecialchars($programaReporteLabel) ?></h3>
        <small style="color:#637087;">Marca X por clase. Total personas: <?= (int)count($rowsAsistencia) ?></small>
    </div>

    <div class="dashboard-grid" style="margin-bottom:12px;">
        <div class="gender-card dashboard-card <?= $asistenciaHombresActivo ? 'is-active' : '' ?>" style="border-left-color:#1e4a89;">
            <button type="button" class="gender-card-toggle js-gender-view-btn" data-view-target="asistencia_view_hombres">
                <span class="gender-card-title-wrap">
                    <span class="gender-avatar gender-avatar-male" aria-hidden="true">👨</span>
                    <span>Hombres</span>
                </span>
                <span class="gender-card-icon">Ver</span>
            </button>
            <div class="gender-card-metric">
                <div class="gender-kpi-grid">
                    <div class="gender-kpi-box">
                        <span class="kpi-label">Registrados</span>
                        <strong class="kpi-value" style="color:#1e4a89;"><?= $hombresRegistrados ?></strong>
                    </div>
                    <div class="gender-kpi-box">
                        <span class="kpi-label">Asistieron</span>
                        <strong class="kpi-value" style="color:#166534;"><?= $hombresAsistieron ?></strong>
                    </div>
                    <div class="gender-kpi-box">
                        <span class="kpi-label">Pendientes</span>
                        <strong class="kpi-value" style="color:#b45309;"><?= $hombresPendientes ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="gender-card dashboard-card <?= $asistenciaMujeresActivo ? 'is-active' : '' ?>" style="border-left-color:#8b1c62;">
            <button type="button" class="gender-card-toggle js-gender-view-btn" data-view-target="asistencia_view_mujeres">
                <span class="gender-card-title-wrap">
                    <span class="gender-avatar gender-avatar-female" aria-hidden="true">👩</span>
                    <span>Mujeres</span>
                </span>
                <span class="gender-card-icon">Ver</span>
            </button>
            <div class="gender-card-metric">
                <div class="gender-kpi-grid">
                    <div class="gender-kpi-box">
                        <span class="kpi-label">Registradas</span>
                        <strong class="kpi-value" style="color:#8b1c62;"><?= $mujeresRegistradas ?></strong>
                    </div>
                    <div class="gender-kpi-box">
                        <span class="kpi-label">Asistieron</span>
                        <strong class="kpi-value" style="color:#166534;"><?= $mujeresAsistieron ?></strong>
                    </div>
                    <div class="gender-kpi-box">
                        <span class="kpi-label">Pendientes</span>
                        <strong class="kpi-value" style="color:#b45309;"><?= $mujeresPendientes ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="asistencia_view_hombres" class="gender-full-view" <?= $asistenciaHombresActivo ? '' : 'hidden' ?>>
        <div class="table-container">
            <table class="data-table asistencia-matriz-table" data-modulo="<?= htmlspecialchars((string)($configModulo['modulo'] ?? '')) ?>" data-programa="<?= htmlspecialchars($programaReporte) ?>" data-grupo="hombres">
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
                                    value="<?= htmlspecialchars((string)($fechasClasesHombres[$i] ?? '')) ?>"
                                >
                            </th>
                        <?php endfor; ?>
                        <th class="th-total-asistencia">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rowsAsistenciaHombres)): ?>
                        <?php foreach ($rowsAsistenciaHombres as $row): ?>
                            <?php $totalAsistencias = 0; ?>
                            <tr>
                                <td class="sticky-col-left col-nowrap col-nombre"><?= htmlspecialchars((string)($row['nombre'] ?? '')) ?></td>
                                <td class="sticky-col-left-2 col-nowrap col-lider"><?= htmlspecialchars((string)($row['lider'] ?? '')) ?></td>
                                <?php for ($i = 1; $i <= $totalClases; $i++): ?>
                                    <?php $activo = !empty($row['clases'][$i]); ?>
                                    <?php if ($activo) { $totalAsistencias++; } ?>
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
                                <td class="text-center"><strong class="js-total-asistencias"><?= (int)$totalAsistencias ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= 3 + $totalClases ?>" class="text-center">No hay hombres para este programa con los filtros seleccionados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="asistencia_view_mujeres" class="gender-full-view" <?= $asistenciaMujeresActivo ? '' : 'hidden' ?>>
        <div class="table-container">
            <table class="data-table asistencia-matriz-table" data-modulo="<?= htmlspecialchars((string)($configModulo['modulo'] ?? '')) ?>" data-programa="<?= htmlspecialchars($programaReporte) ?>" data-grupo="mujeres">
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
                                    value="<?= htmlspecialchars((string)($fechasClasesMujeres[$i] ?? '')) ?>"
                                >
                            </th>
                        <?php endfor; ?>
                        <th class="th-total-asistencia">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rowsAsistenciaMujeres)): ?>
                        <?php foreach ($rowsAsistenciaMujeres as $row): ?>
                            <?php $totalAsistencias = 0; ?>
                            <tr>
                                <td class="sticky-col-left col-nowrap col-nombre"><?= htmlspecialchars((string)($row['nombre'] ?? '')) ?></td>
                                <td class="sticky-col-left-2 col-nowrap col-lider"><?= htmlspecialchars((string)($row['lider'] ?? '')) ?></td>
                                <?php for ($i = 1; $i <= $totalClases; $i++): ?>
                                    <?php $activo = !empty($row['clases'][$i]); ?>
                                    <?php if ($activo) { $totalAsistencias++; } ?>
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
                                <td class="text-center"><strong class="js-total-asistencias"><?= (int)$totalAsistencias ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= 3 + $totalClases ?>" class="text-center">No hay mujeres para este programa con los filtros seleccionados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($rowsAsistenciaSinClasificar)): ?>
        <small style="display:block; margin-top:10px; color:#637087;">
            Hay <?= (int)count($rowsAsistenciaSinClasificar) ?> persona(s) sin género reconocible y no se muestran en Hombre/Mujer.
        </small>
    <?php endif; ?>
</div>

<script>
(function () {
    const endpointAsistencia = <?= json_encode(PUBLIC_URL . '?url=home/escuelas-formacion/actualizar-matriz-asistencia') ?>;
    const endpointFecha = <?= json_encode(PUBLIC_URL . '?url=home/escuelas-formacion/actualizar-fecha-clase') ?>;
    const tables = document.querySelectorAll('.asistencia-matriz-table');

    if (!tables.length) {
        return;
    }

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

    function actualizarTotalFila(btn) {
        const fila = btn.closest('tr');
        if (!fila) {
            return;
        }

        const totalEl = fila.querySelector('.js-total-asistencias');
        if (!totalEl) {
            return;
        }

        const total = fila.querySelectorAll('.js-asistencia-cell.is-active').length;
        totalEl.textContent = String(total);
    }

    tables.forEach((table) => {
        const modulo = String(table.dataset.modulo || '');
        const programa = String(table.dataset.programa || '');
        const grupo = String(table.dataset.grupo || 'general');

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
                    actualizarTotalFila(btn);
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
                        grupo: grupo,
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
    });

    document.querySelectorAll('.js-gender-view-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = String(btn.dataset.viewTarget || '');
            if (targetId === '') {
                return;
            }

            const targetView = document.getElementById(targetId);
            if (!targetView) {
                return;
            }

            document.querySelectorAll('.gender-full-view').forEach((view) => {
                view.hidden = true;
            });
            targetView.hidden = false;

            document.querySelectorAll('.gender-card').forEach((card) => {
                card.classList.remove('is-active');
            });
            const currentCard = btn.closest('.gender-card');
            if (currentCard) {
                currentCard.classList.add('is-active');
            }
        });
    });
}());
</script>

<style>
.header-actions {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    justify-content:flex-end;
}

.action-group {
    display:inline-flex;
    align-items:center;
    gap:4px;
    padding:4px;
    border:1px solid #d5e2f3;
    border-radius:999px;
    background:#f8fbff;
}

.action-pill {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:7px 12px;
    border:1px solid transparent;
    border-radius:999px;
    color:#2a4a73;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    line-height:1;
    white-space:nowrap;
    transition:all .16s ease;
}

.action-pill:hover {
    background:#edf4ff;
    color:#1c4478;
}

.action-pill.is-active {
    background:#1f5ea8;
    border-color:#1f5ea8;
    color:#ffffff;
    box-shadow:0 1px 3px rgba(20, 58, 101, 0.28);
}

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

.th-total-asistencia {
    min-width: 90px;
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

.gender-card {
    padding:0;
    overflow:hidden;
}

.gender-card.is-active {
    box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.2), 0 4px 10px rgba(0,0,0,0.08);
}

.gender-card-toggle {
    width:100%;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:8px;
    border:0;
    border-radius:0;
    padding:12px 14px;
    background:transparent;
    color:#204a82;
    font-size:16px;
    font-weight:700;
    cursor:pointer;
}

.gender-card-toggle:hover {
    background:#f6f9ff;
}

.gender-card-title-wrap {
    display:inline-flex;
    align-items:center;
    gap:8px;
}

.gender-avatar {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:30px;
    height:30px;
    border-radius:999px;
    font-size:18px;
    line-height:1;
}

.gender-avatar-male {
    background:#e8f2ff;
    border:1px solid #b6cff0;
}

.gender-avatar-female {
    background:#fdeaf4;
    border:1px solid #ebc4d9;
}

.gender-card-icon {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:42px;
    height:24px;
    border-radius:999px;
    border:1px solid #9ab7df;
    background:#fff;
    font-size:12px;
    font-weight:700;
    padding:0 10px;
}

.gender-card-metric {
    padding:0 14px 12px 14px;
    color:#5a6780;
}

.gender-kpi-grid {
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:8px;
}

.gender-kpi-box {
    border:1px solid #d8e4f5;
    border-radius:10px;
    background:#f8fbff;
    padding:8px 8px;
    text-align:center;
}

.kpi-label {
    display:block;
    font-size:11px;
    font-weight:700;
    color:#4e617d;
    text-transform:uppercase;
    letter-spacing:.2px;
    margin-bottom:3px;
}

.kpi-value {
    display:block;
    font-size:22px;
    line-height:1;
}

.gender-full-view {
    border-top:1px solid #e3ebf7;
    padding:10px;
    background:#fff;
    border-radius:8px;
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>

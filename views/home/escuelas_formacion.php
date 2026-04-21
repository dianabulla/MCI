<?php include VIEWS . '/layout/header.php'; ?>

<?php
$reporteUniversidadVida = $reporteUniversidadVida ?? ['total' => 0, 'rows' => []];
$filtroMinisterio = (string)($filtro_ministerio ?? '');
$filtroLider = (string)($filtro_lider ?? '');
$filtroBuscarUv = (string)($filtro_buscar_uv ?? '');
$filtroGenero = (string)($filtro_genero ?? 'todos');
$inscripcionesPublicas = $inscripciones_publicas ?? [];
$resumenInscripciones = $resumen_inscripciones ?? ['total' => 0, 'universidad_vida' => 0, 'encuentro' => 0, 'bautismo' => 0, 'capacitacion_destino' => 0, 'otros' => 0];
$filtroInscPrograma = (string)($filtro_insc_programa ?? '');
$programaReporte = (string)($programa_reporte ?? 'universidad_vida');
$programaReporteLabel = (string)($programa_reporte_label ?? 'Universidad de la Vida');
$vistaActual = (string)($vista_actual ?? 'registro');
$rutaRegistro = 'home/escuelas-formacion';
$rutaAsistencias = 'home/escuelas-formacion/asistencias';
$registroActivo = $vistaActual !== 'asistencias';
$asistenciasActivo = $vistaActual === 'asistencias';

$filtrosActivos = 0;
if ($filtroBuscarUv !== '') { $filtrosActivos++; }
if ($filtroMinisterio !== '') { $filtrosActivos++; }
if ($filtroLider !== '') { $filtrosActivos++; }
if ($filtroGenero !== '' && $filtroGenero !== 'todos') { $filtrosActivos++; }
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;">Escuelas de Formación</h2>
        <small style="color:#637087;">Reporte actual: <strong><?= htmlspecialchars($programaReporteLabel) ?></strong>.</small>
    </div>
    <div class="header-actions">
        <div class="action-group action-group-nav">
            <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaRegistro) ?>" class="action-pill <?= $registroActivo ? 'is-active' : '' ?>" <?= $registroActivo ? 'aria-current="page"' : '' ?>>Registro</a>
            <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaAsistencias) ?>" class="action-pill <?= $asistenciasActivo ? 'is-active' : '' ?>" <?= $asistenciasActivo ? 'aria-current="page"' : '' ?>>Asistencias</a>
        </div>
        <div class="action-group">
            <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/codigos" class="action-pill" target="_blank" rel="noopener">Códigos QR</a>
            <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/registro-publico" class="action-pill" target="_blank" rel="noopener">Formulario público</a>
            <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/asistencia-publica" class="action-pill" target="_blank" rel="noopener">Asistencia pública</a>
            <a href="<?= PUBLIC_URL ?>?url=home" class="action-pill">Volver al panel</a>
        </div>
    </div>
</div>

<div class="card report-card" style="margin-bottom:18px; padding:14px;">
    <div class="filters-toolbar" style="margin-bottom:10px;">
        <button type="button" class="btn btn-secondary js-toggle-filtros-btn" aria-controls="panel-filtros-escuelas" aria-expanded="<?= $filtrosActivos > 0 ? 'true' : 'false' ?>">
            <?= $filtrosActivos > 0 ? 'Ocultar filtros' : 'Mostrar filtros' ?>
        </button>
    </div>

    <div class="filters-panel" id="panel-filtros-escuelas" <?= $filtrosActivos > 0 ? '' : 'hidden' ?>>
        <form method="GET" action="<?= PUBLIC_URL ?>" class="filters-inline" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
            <input type="hidden" name="url" value="home/escuelas-formacion">

            <div class="form-group" style="margin:0; min-width:280px;">
                <label for="filtro_buscar_uv">Buscar por nombre</label>
                <input type="text" id="filtro_buscar_uv" name="buscar_uv" class="form-control" placeholder="Nombre o apellido" value="<?= htmlspecialchars($filtroBuscarUv) ?>">
            </div>

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

            <div class="form-group" style="margin:0; min-width:180px;">
                <label for="filtro_genero">Género</label>
                <select id="filtro_genero" name="genero" class="form-control">
                    <option value="todos" <?= $filtroGenero === 'todos' || $filtroGenero === '' ? 'selected' : '' ?>>Todos</option>
                    <option value="hombres" <?= $filtroGenero === 'hombres' ? 'selected' : '' ?>>Hombres</option>
                    <option value="mujeres" <?= $filtroGenero === 'mujeres' ? 'selected' : '' ?>>Mujeres</option>
                </select>
            </div>

            <div class="filters-actions" style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary">Aplicar</button>
                <a href="<?= PUBLIC_URL ?>?url=home/escuelas-formacion" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="dashboard-grid" style="grid-template-columns: repeat(4, minmax(0, 320px)); margin:18px 0;">
    <div class="dashboard-card" style="border-left-color:#1e4a89;">
        <h3>Universidad de la Vida</h3>
        <div class="value" style="color:#1e4a89;"><?= (int)($resumenInscripciones['universidad_vida'] ?? 0) ?></div>
        <small style="color:#637087;">Registros en este programa.</small>
    </div>
    <div class="dashboard-card" style="border-left-color:#0b7285;">
        <h3>Encuentro</h3>
        <div class="value" style="color:#0b7285;"><?= (int)($resumenInscripciones['encuentro'] ?? 0) ?></div>
        <small style="color:#637087;">Registros en este programa.</small>
    </div>
    <div class="dashboard-card" style="border-left-color:#5f3dc4;">
        <h3>Bautismo</h3>
        <div class="value" style="color:#5f3dc4;"><?= (int)($resumenInscripciones['bautismo'] ?? 0) ?></div>
        <small style="color:#637087;">Registros en este programa.</small>
    </div>
    <div class="dashboard-card" style="border-left-color:#7a4e08;">
        <h3>Capacitación Destino</h3>
        <div class="value" style="color:#7a4e08;"><?= (int)($resumenInscripciones['capacitacion_destino'] ?? 0) ?></div>
        <small style="color:#637087;">General (suma de niveles).</small>
    </div>
</div>

<div class="card report-card" style="padding:14px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="margin:0;">Detalle Pendientes: <?= htmlspecialchars($programaReporteLabel) ?></h3>
        <small style="color:#637087;">Total registros: <?= (int)($reporteUniversidadVida['total'] ?? 0) ?></small>
    </div>

    <div class="table-container">
        <table class="data-table uv-table-ordenada">
            <thead>
                <tr>
                    <th>Persona</th>
                    <th>Líder</th>
                    <th style="width:180px;">Se inscribe</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reporteUniversidadVida['rows'])): ?>
                    <?php foreach (($reporteUniversidadVida['rows'] ?? []) as $row): ?>
                        <tr>
                            <td class="col-nowrap col-nombre"><?= htmlspecialchars((string)($row['nombre'] ?? '')) ?></td>
                            <td class="col-nowrap col-lider"><?= htmlspecialchars((string)($row['lider'] ?? '')) ?></td>
                            <td class="text-center">
                                <div class="uv-checklist-options" data-persona="<?= (int)($row['id_persona'] ?? 0) ?>" data-programa="<?= htmlspecialchars($programaReporte) ?>">
                                    <button type="button" class="uv-check-option js-uv-estado-option" data-value="1" aria-label="Marcar Sí">
                                        <span class="uv-box"></span>
                                        <span>Sí</span>
                                    </button>
                                    <button type="button" class="uv-check-option js-uv-estado-option" data-value="0" aria-label="Marcar No">
                                        <span class="uv-box"></span>
                                        <span>No</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center">No hay personas pendientes para <?= htmlspecialchars($programaReporteLabel) ?> con estos filtros.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card report-card" style="padding:14px;">
    <?php
    $inscripcionesHombres = [];
    $inscripcionesMujeres = [];
    $inscripcionesSinClasificar = [];

    foreach ($inscripcionesPublicas as $ins) {
        $generoRegistro = strtolower(trim((string)($ins['Genero'] ?? '')));
        if (strpos($generoRegistro, 'mujer') !== false || strpos($generoRegistro, 'femen') !== false) {
            $inscripcionesMujeres[] = $ins;
            continue;
        }

        if (strpos($generoRegistro, 'hombre') !== false || strpos($generoRegistro, 'mascul') !== false || $generoRegistro === 'm') {
            $inscripcionesHombres[] = $ins;
            continue;
        }

        $inscripcionesSinClasificar[] = $ins;
    }
    ?>

    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="margin:0;">Registros del formulario público: <?= htmlspecialchars($programaReporteLabel) ?></h3>
        <small style="color:#637087;">Mostrando <?= (int)count($inscripcionesPublicas) ?> registros recientes (máx. 300)</small>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(360px, 1fr)); gap:12px;">
        <div>
            <h4 style="margin:0 0 8px 0;">Hombres (<?= (int)count($inscripcionesHombres) ?>)</h4>
            <div class="table-container">
                <table class="data-table insc-table-ordenada">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Nombre</th>
                            <th>Cédula</th>
                            <th>Teléfono</th>
                            <th>Líder</th>
                            <th>Asistencias</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($inscripcionesHombres)): ?>
                            <?php foreach ($inscripcionesHombres as $ins): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($ins['Fecha_Registro'] ?? '')) ?></td>
                                    <td class="col-nowrap col-nombre"><?= htmlspecialchars((string)($ins['Nombre'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($ins['Cedula'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($ins['Telefono'] ?? '')) ?></td>
                                    <td class="col-nowrap col-lider"><?= htmlspecialchars((string)($ins['Lider'] ?? '')) ?></td>
                                    <?php $numeroAsistencias = ((string)($ins['Asistio_Clase'] ?? '') === '1') ? 1 : 0; ?>
                                    <td><strong><?= $numeroAsistencias ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No hay registros de hombres para los filtros seleccionados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h4 style="margin:0 0 8px 0;">Mujeres (<?= (int)count($inscripcionesMujeres) ?>)</h4>
            <div class="table-container">
                <table class="data-table insc-table-ordenada">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Nombre</th>
                            <th>Cédula</th>
                            <th>Teléfono</th>
                            <th>Líder</th>
                            <th>Asistencias</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($inscripcionesMujeres)): ?>
                            <?php foreach ($inscripcionesMujeres as $ins): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($ins['Fecha_Registro'] ?? '')) ?></td>
                                    <td class="col-nowrap col-nombre"><?= htmlspecialchars((string)($ins['Nombre'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($ins['Cedula'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($ins['Telefono'] ?? '')) ?></td>
                                    <td class="col-nowrap col-lider"><?= htmlspecialchars((string)($ins['Lider'] ?? '')) ?></td>
                                    <?php $numeroAsistencias = ((string)($ins['Asistio_Clase'] ?? '') === '1') ? 1 : 0; ?>
                                    <td><strong><?= $numeroAsistencias ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No hay registros de mujeres para los filtros seleccionados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($inscripcionesSinClasificar)): ?>
        <small style="display:block; margin-top:10px; color:#637087;">
            Hay <?= (int)count($inscripcionesSinClasificar) ?> registro(s) sin género reconocible y no se muestran en Hombre/Mujer.
        </small>
    <?php endif; ?>
</div>

<script>
(function () {
    const endpoint = <?= json_encode(PUBLIC_URL . '?url=home/escuelas-formacion/actualizar-estado') ?>;
    const filtroToggleBtn = document.querySelector('.js-toggle-filtros-btn');
    const filtroPanel = document.getElementById('panel-filtros-escuelas');

    if (filtroToggleBtn && filtroPanel) {
        filtroToggleBtn.addEventListener('click', () => {
            const oculto = filtroPanel.hasAttribute('hidden');
            if (oculto) {
                filtroPanel.removeAttribute('hidden');
                filtroToggleBtn.textContent = 'Ocultar filtros';
                filtroToggleBtn.setAttribute('aria-expanded', 'true');
            } else {
                filtroPanel.setAttribute('hidden', 'hidden');
                filtroToggleBtn.textContent = 'Mostrar filtros';
                filtroToggleBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.querySelectorAll('.js-uv-estado-option').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const contenedor = btn.closest('.uv-checklist-options');
            if (!contenedor || btn.disabled) {
                return;
            }

            const personaId = String(contenedor.dataset.persona || '0');
            const programa = String(contenedor.dataset.programa || 'universidad_vida');
            const valorSeleccionado = String(btn.dataset.value || '');
            if (personaId === '0') {
                return;
            }

            contenedor.querySelectorAll('.js-uv-estado-option').forEach((opcion) => {
                opcion.disabled = true;
            });
            contenedor.classList.add('is-loading');

            try {
                const formData = new FormData();
                formData.append('id_persona', personaId);
                formData.append('programa', programa);
                formData.append('va', valorSeleccionado);

                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!response.ok || !data.ok) {
                    throw new Error((data && data.error) || 'No se pudo guardar');
                }

                btn.classList.add('is-selected');
                contenedor.classList.remove('is-loading');
                contenedor.classList.add('is-done');

                setTimeout(() => {
                    window.location.reload();
                }, 220);
            } catch (error) {
                contenedor.classList.remove('is-loading');
                contenedor.querySelectorAll('.js-uv-estado-option').forEach((opcion) => {
                    opcion.disabled = false;
                });
                alert(error.message || 'No se pudo guardar el estado');
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

.filters-panel {
    border: 1px solid #d6e2f1;
    border-radius: 10px;
    padding: 8px 10px;
    background: #f9fcff;
}

.table-actions-inline {
    display:flex;
    gap:6px;
    align-items:center;
    justify-content:center;
}

.uv-checklist-options {
    display:inline-flex;
    align-items:center;
    gap:8px;
}

.uv-checklist-options.is-loading {
    opacity:.7;
}

.uv-check-option {
    display:inline-flex;
    align-items:center;
    gap:6px;
    border:1px solid #cfd9ea;
    background:#f8fbff;
    color:#2a466a;
    border-radius:8px;
    padding:6px 10px;
    font-weight:600;
    cursor:pointer;
}

.uv-check-option:hover {
    background:#edf4ff;
    border-color:#a6bfdf;
}

.uv-check-option.is-selected {
    background:#e8f2ff;
    border-color:#4f7fbe;
    color:#1d4f92;
}

.uv-box {
    width:14px;
    height:14px;
    border:1px solid #7394bf;
    border-radius:3px;
    display:inline-block;
    background:#fff;
}

.uv-check-option.is-selected .uv-box {
    background:#2f65b5;
    border-color:#2f65b5;
    box-shadow: inset 0 0 0 2px #fff;
}

.uv-table-ordenada,
.insc-table-ordenada {
    table-layout: auto;
    width: max-content;
    min-width: 100%;
}

.uv-table-ordenada th,
.uv-table-ordenada td,
.insc-table-ordenada th,
.insc-table-ordenada td {
    vertical-align: middle;
    padding-top: 9px !important;
    padding-bottom: 9px !important;
}

.col-nowrap {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.uv-table-ordenada .col-nombre { min-width: 260px; }
.uv-table-ordenada .col-ministerio { min-width: 180px; }
.uv-table-ordenada .col-lider { min-width: 210px; }

.insc-table-ordenada .col-nombre { min-width: 240px; }
.insc-table-ordenada .col-lider { min-width: 190px; }
.insc-table-ordenada .col-ministerio { min-width: 180px; }
.insc-table-ordenada .col-programa { min-width: 210px; }
</style>

<?php include VIEWS . '/layout/footer.php'; ?>
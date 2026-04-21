<?php include VIEWS . '/layout/header.php'; ?>

<?php
$configModulo = $config_modulo ?? [];
$tituloModulo = (string)($configModulo['titulo'] ?? 'Modulo');
$rutaBase = (string)($configModulo['ruta_base'] ?? 'home');
$rutaAsistencias = (string)($configModulo['ruta_asistencias'] ?? $rutaBase);
$rutaExportar = (string)($configModulo['ruta_exportar'] ?? 'home');

$reportePendientes = $reporte_pendientes ?? ['total' => 0, 'rows' => []];
$filtroMinisterio = (string)($filtro_ministerio ?? '');
$filtroLider = (string)($filtro_lider ?? '');
$filtroBuscar = (string)($filtro_buscar ?? '');
$filtroGenero = (string)($filtro_genero ?? 'todos');
$inscripcionesPublicas = $inscripciones_publicas ?? [];
$programaReporte = (string)($programa_reporte ?? '');
$programaReporteLabel = (string)($programa_reporte_label ?? 'Programa');
$programasOpciones = $programas_opciones ?? [];
$tarjetasResumen = $tarjetas_resumen ?? [];
$vistaActual = (string)($vista_actual ?? 'registro');
$registroActivo = $vistaActual !== 'asistencias';
$asistenciasActivo = $vistaActual === 'asistencias';

$filtrosActivos = 0;
if ($filtroBuscar !== '') { $filtrosActivos++; }
if ($filtroMinisterio !== '') { $filtrosActivos++; }
if ($filtroLider !== '') { $filtrosActivos++; }
if ($filtroGenero !== '' && $filtroGenero !== 'todos') { $filtrosActivos++; }
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;"><?= htmlspecialchars($tituloModulo) ?></h2>
        <small style="color:#637087;">Vista Registro. Programa actual: <strong><?= htmlspecialchars($programaReporteLabel) ?></strong>.</small>
    </div>
    <div class="header-actions">
        <div class="action-group action-group-nav">
            <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaBase) ?>" class="action-pill <?= $registroActivo ? 'is-active' : '' ?>" <?= $registroActivo ? 'aria-current="page"' : '' ?>>Registro</a>
            <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaAsistencias) ?>" class="action-pill <?= $asistenciasActivo ? 'is-active' : '' ?>" <?= $asistenciasActivo ? 'aria-current="page"' : '' ?>>Asistencias</a>
        </div>
        <div class="action-group">
            <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/codigos" class="action-pill" target="_blank" rel="noopener">Codigos QR</a>
            <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/registro-publico" class="action-pill" target="_blank" rel="noopener">Formulario publico</a>
            <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/asistencia-publica" class="action-pill" target="_blank" rel="noopener">Asistencia publica</a>
            <a href="<?= PUBLIC_URL ?>?url=home" class="action-pill">Volver al panel</a>
        </div>
    </div>
</div>

<div class="card report-card" style="margin-bottom:18px; padding:14px;">
    <div class="filters-toolbar" style="margin-bottom:10px;">
        <button type="button" class="btn btn-secondary js-toggle-filtros-btn" aria-controls="panel-filtros-formacion" aria-expanded="<?= $filtrosActivos > 0 ? 'true' : 'false' ?>">
            <?= $filtrosActivos > 0 ? 'Ocultar filtros' : 'Mostrar filtros' ?>
        </button>
    </div>

    <div class="filters-panel" id="panel-filtros-formacion" <?= $filtrosActivos > 0 ? '' : 'hidden' ?>>
        <form method="GET" action="<?= PUBLIC_URL ?>" class="filters-inline" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
            <input type="hidden" name="url" value="<?= htmlspecialchars($rutaBase) ?>">

            <div class="form-group" style="margin:0; min-width:280px;">
                <label for="filtro_buscar">Buscar por nombre</label>
                <input type="text" id="filtro_buscar" name="buscar" class="form-control" placeholder="Nombre o apellido" value="<?= htmlspecialchars($filtroBuscar) ?>">
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
                <label for="filtro_lider">Lider</label>
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
                <label for="filtro_genero">Genero</label>
                <select id="filtro_genero" name="genero" class="form-control">
                    <option value="todos" <?= $filtroGenero === 'todos' || $filtroGenero === '' ? 'selected' : '' ?>>Todos</option>
                    <option value="hombres" <?= $filtroGenero === 'hombres' ? 'selected' : '' ?>>Hombres</option>
                    <option value="mujeres" <?= $filtroGenero === 'mujeres' ? 'selected' : '' ?>>Mujeres</option>
                </select>
            </div>

            <div class="filters-actions" style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary">Aplicar</button>
                <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaBase) ?>" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card report-card" style="padding:14px; margin-bottom:14px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="margin:0;">Detalle Pendientes: <?= htmlspecialchars($programaReporteLabel) ?></h3>
        <small style="color:#637087;">Total registros: <?= (int)($reportePendientes['total'] ?? 0) ?></small>
    </div>

    <div class="table-container">
        <table class="data-table uv-table-ordenada">
            <thead>
                <tr>
                    <th>Persona</th>
                    <th>Lider</th>
                    <th style="width:180px;">Se inscribe</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reportePendientes['rows'])): ?>
                    <?php foreach (($reportePendientes['rows'] ?? []) as $row): ?>
                        <tr>
                            <td class="col-nowrap col-nombre"><?= htmlspecialchars((string)($row['nombre'] ?? '')) ?></td>
                            <td class="col-nowrap col-lider"><?= htmlspecialchars((string)($row['lider'] ?? '')) ?></td>
                            <td class="text-center">
                                <div class="uv-checklist-options" data-persona="<?= (int)($row['id_persona'] ?? 0) ?>" data-programa="<?= htmlspecialchars($programaReporte) ?>">
                                    <button type="button" class="uv-check-option js-uv-estado-option" data-value="1" aria-label="Marcar Si">
                                        <span class="uv-box"></span>
                                        <span>Si</span>
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
        $esMujer = strpos($generoRegistro, 'mujer') !== false
            || strpos($generoRegistro, 'femen') !== false
            || in_array($generoRegistro, ['f', 'fem', 'female'], true);
        if ($esMujer) {
            $inscripcionesMujeres[] = $ins;
            continue;
        }

        $esHombre = strpos($generoRegistro, 'hombre') !== false
            || strpos($generoRegistro, 'mascul') !== false
            || in_array($generoRegistro, ['m', 'masc', 'male', 'h'], true);
        if ($esHombre) {
            $inscripcionesHombres[] = $ins;
            continue;
        }

        $inscripcionesSinClasificar[] = $ins;
    }

    $registroVistaInicial = !empty($inscripcionesHombres) ? 'hombres' : (!empty($inscripcionesMujeres) ? 'mujeres' : 'hombres');
    $registroHombresActivo = $registroVistaInicial === 'hombres';
    $registroMujeresActivo = $registroVistaInicial === 'mujeres';
    $hombresRegistrados = (int)count($inscripcionesHombres);
    $mujeresRegistradas = (int)count($inscripcionesMujeres);
    $totalRegistrosVisibles = $hombresRegistrados + $mujeresRegistradas;
    ?>

    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="margin:0;">Registros del formulario publico: <?= htmlspecialchars($programaReporteLabel) ?></h3>
        <small style="color:#637087;">Mostrando <?= $totalRegistrosVisibles ?> registros recientes (max. 300)</small>
    </div>

    <div class="dashboard-grid" style="margin-bottom:12px;">
        <div class="gender-card dashboard-card <?= $registroHombresActivo ? 'is-active' : '' ?>" style="border-left-color:#1e4a89;">
            <button type="button" class="gender-card-toggle js-gender-view-btn" data-view-target="registro_view_hombres">
                <span class="gender-card-title-wrap">
                    <span class="gender-avatar gender-avatar-male" aria-hidden="true">👨</span>
                    <span>Hombres</span>
                </span>
                <span class="gender-card-icon">Ver</span>
            </button>
            <div class="gender-card-metric">
                <div class="gender-kpi-single">
                    <span class="kpi-label">Registrados</span>
                    <strong class="kpi-value" style="color:#1e4a89;"><?= $hombresRegistrados ?></strong>
                </div>
            </div>
        </div>

        <div class="gender-card dashboard-card <?= $registroMujeresActivo ? 'is-active' : '' ?>" style="border-left-color:#8b1c62;">
            <button type="button" class="gender-card-toggle js-gender-view-btn" data-view-target="registro_view_mujeres">
                <span class="gender-card-title-wrap">
                    <span class="gender-avatar gender-avatar-female" aria-hidden="true">👩</span>
                    <span>Mujeres</span>
                </span>
                <span class="gender-card-icon">Ver</span>
            </button>
            <div class="gender-card-metric">
                <div class="gender-kpi-single">
                    <span class="kpi-label">Registradas</span>
                    <strong class="kpi-value" style="color:#8b1c62;"><?= $mujeresRegistradas ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div id="registro_view_hombres" class="gender-full-view" <?= $registroHombresActivo ? '' : 'hidden' ?>>
        <div class="table-container">
            <table class="data-table insc-table-ordenada">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre</th>
                        <th>Cedula</th>
                        <th>Telefono</th>
                        <th>Lider</th>
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
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No hay registros de hombres para los filtros seleccionados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="registro_view_mujeres" class="gender-full-view" <?= $registroMujeresActivo ? '' : 'hidden' ?>>
        <div class="table-container">
            <table class="data-table insc-table-ordenada">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre</th>
                        <th>Cedula</th>
                        <th>Telefono</th>
                        <th>Lider</th>
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
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No hay registros de mujeres para los filtros seleccionados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
    const filtroPanel = document.getElementById('panel-filtros-formacion');

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

.filters-panel {
    border: 1px solid #d6e2f1;
    border-radius: 10px;
    padding: 8px 10px;
    background: #f9fcff;
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
.uv-table-ordenada .col-lider { min-width: 210px; }

.insc-table-ordenada .col-nombre { min-width: 240px; }
.insc-table-ordenada .col-lider { min-width: 190px; }

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

.gender-kpi-single {
    border:1px solid #d8e4f5;
    border-radius:10px;
    background:#f8fbff;
    padding:10px 8px;
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
    font-size:26px;
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
<?php include VIEWS . '/layout/header.php'; ?>

<?php
$configModulo = $config_modulo ?? [];
$tituloModulo = (string)($configModulo['titulo'] ?? 'Modulo');
$rutaBase = (string)($configModulo['ruta_base'] ?? 'home');
$rutaAsistencias = (string)($configModulo['ruta_asistencias'] ?? $rutaBase);
$rutaExportar = (string)($configModulo['ruta_exportar'] ?? 'home');
$esFlujoProgramas = strpos($rutaBase, 'programas') === 0;
$urlVolverContextual = PUBLIC_URL . '?url=' . ($esFlujoProgramas ? 'programas' : 'home');
$etiquetaVolverContextual = $esFlujoProgramas ? 'Volver a Programas' : 'Volver al panel';

$reportePendientes = $reporte_pendientes ?? ['total' => 0, 'rows' => []];
$filtroMinisterio = (string)($filtro_ministerio ?? '');
$filtroLider = (string)($filtro_lider ?? '');
$filtroBuscar = (string)($filtro_buscar ?? '');
$filtroGenero = (string)($filtro_genero ?? 'todos');
$inscripcionesPublicas = $inscripciones_publicas ?? [];
$tablaUvMinisterio = $tabla_uv_ministerio ?? [];
$detalleLideresMinisterioUv = $detalle_lideres_ministerio_uv ?? [];
$detalleLideresMinisterioUvJson = json_encode($detalleLideresMinisterioUv, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
if ($detalleLideresMinisterioUvJson === false) {
    $detalleLideresMinisterioUvJson = '{}';
}
$programaReporte = (string)($programa_reporte ?? '');
$programaReporteLabel = (string)($programa_reporte_label ?? 'Programa');
$programaRutaActual = $programaReporte;
if (in_array($programaRutaActual, ['capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
    $programaRutaActual = 'capacitacion_destino';
}
$programasOpciones = $programas_opciones ?? [];
$programasTabs = $programas_tabs ?? [];
$tarjetasResumen = $tarjetas_resumen ?? [];
$vistaActual = (string)($vista_actual ?? 'registro');
$registroActivo = $vistaActual !== 'asistencias';
$asistenciasActivo = $vistaActual === 'asistencias';
$mostrarAccesoAsistencias = $programaRutaActual !== 'capacitacion_destino';
$puedeEditarPersonaFormacion = class_exists('AuthController') && AuthController::tienePermiso('personas', 'editar');
$moduloFormacionActual = strtolower(trim((string)($configModulo['modulo'] ?? '')));
$puedeEditarRegistroFormacion = class_exists('AuthController')
    && (
        AuthController::esAdministrador()
        || AuthController::tienePermiso('escuelas_formacion', 'editar')
        || AuthController::tienePermiso('personas', 'editar')
    );
$puedeEliminarInscripcionFormacion = $moduloFormacionActual === 'consolidar'
    && class_exists('AuthController')
    && AuthController::tienePermiso('personas', 'eliminar');
$esModuloConsolidar = $moduloFormacionActual === 'consolidar';
$mostrarTablaProgramaMinisterio = in_array($moduloFormacionActual, ['consolidar', 'discipular'], true);
$tituloTablaProgramaMinisterio = trim($programaReporteLabel) !== ''
    ? $programaReporteLabel . ' por ministerio'
    : 'Reporte por ministerio';
$mensajeSinInscripcionesPrograma = 'No hay inscripciones de ' . $programaReporteLabel . ' para mostrar.';
$esModuloEnviar = in_array($moduloFormacionActual, ['discipular', 'enviar'], true);
$inscProgramaSolicitado = trim((string)($_GET['insc_programa'] ?? ''));
$mostrarDetalleDiscipular = !($moduloFormacionActual === 'discipular' && $inscProgramaSolicitado === '');

$parametrosRetornoFormacion = $_GET;
if (!isset($parametrosRetornoFormacion['url']) || trim((string)$parametrosRetornoFormacion['url']) === '') {
    $parametrosRetornoFormacion['url'] = $rutaBase;
}
$returnUrlFormacion = '?' . http_build_query($parametrosRetornoFormacion);

$renderAccionesRegistroFormacion = static function(array $ins, int $idPersonaIns, string $segmentoActual) use ($puedeEditarPersonaFormacion, $puedeEditarRegistroFormacion, $puedeEliminarInscripcionFormacion, $returnUrlFormacion, $moduloFormacionActual) {
    $idInscripcion = (int)($ins['Id_Inscripcion'] ?? 0);
    $nombreInscripcion = (string)($ins['Nombre'] ?? '');
    $programaInscripcion = trim((string)($ins['Programa'] ?? ''));
    $esCapacitacionDestinoInscripcion = in_array($programaInscripcion, ['capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true);
    $textoCambio = $esCapacitacionDestinoInscripcion ? 'Cambiar nivel' : 'Cambiar a';

    $puedeMoverPrograma = in_array($programaInscripcion, ['universidad_vida', 'encuentro', 'bautismo', 'capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true);

    ob_start();
    ?>
    <div class="insc-acciones" role="group" aria-label="Acciones del registro">
        <?php if ($puedeEditarRegistroFormacion): ?>
            <?php if ($idPersonaIns > 0): ?>
                <a href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= $idPersonaIns ?>&return_to=formacion&return_url=<?= urlencode($returnUrlFormacion) ?>" class="btn btn-secondary btn-sm btn-insc-icon" title="Editar" aria-label="Editar"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
            <?php else: ?>
                <button type="button" class="btn btn-secondary btn-sm btn-insc-icon" disabled title="Sin persona vinculada" aria-label="Editar (sin persona vinculada)"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
            <?php endif; ?>
            <button type="button" class="btn btn-info btn-sm btn-insc-icon js-cambio-segmento" data-id-inscripcion="<?= $idInscripcion ?>" data-nombre="<?= htmlspecialchars($nombreInscripcion) ?>" data-segmento-actual="<?= htmlspecialchars($segmentoActual) ?>" data-programa-actual="<?= htmlspecialchars($programaInscripcion) ?>" <?= $idInscripcion > 0 ? '' : 'disabled' ?> title="<?= $idInscripcion > 0 ? htmlspecialchars($textoCambio, ENT_QUOTES, 'UTF-8') : 'Inscripción inválida' ?>" aria-label="<?= htmlspecialchars($textoCambio, ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></button>
            <?php if ($puedeMoverPrograma): ?>
                <?php if ($idInscripcion > 0 && $idPersonaIns > 0): ?>
                    <button
                        type="button"
                        class="btn btn-info btn-sm btn-insc-icon js-cambio-programa"
                        data-id-inscripcion="<?= $idInscripcion ?>"
                        data-nombre="<?= htmlspecialchars($nombreInscripcion) ?>"
                        data-programa-actual="<?= htmlspecialchars($programaInscripcion) ?>"
                        title="Mover a otro programa"
                        aria-label="Mover a otro programa"
                    ><i class="bi bi-box-arrow-right" aria-hidden="true"></i></button>
                <?php else: ?>
                    <button type="button" class="btn btn-info btn-sm btn-insc-icon" disabled title="Inscripción inválida" aria-label="Mover a otro programa"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></button>
                <?php endif; ?>
            <?php endif; ?>
        <?php else: ?>
            <button type="button" class="btn btn-secondary btn-sm btn-insc-icon" disabled title="Solo lectura" aria-label="Editar"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
            <button type="button" class="btn btn-info btn-sm btn-insc-icon" disabled title="Sin permiso para cambiar segmento" aria-label="<?= htmlspecialchars($textoCambio, ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></button>
            <?php if ($puedeMoverPrograma): ?>
                <button type="button" class="btn btn-info btn-sm btn-insc-icon" disabled title="Sin permiso para mover de programa" aria-label="Mover a otro programa"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></button>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($puedeEliminarInscripcionFormacion): ?>
            <?php if ($idInscripcion > 0): ?>
                <form method="POST" action="<?= PUBLIC_URL ?>?url=home/cambiar-segmento-inscripcion" class="insc-acciones-form" onsubmit="return confirm('¿Eliminar la inscripción de <?= htmlspecialchars($nombreInscripcion, ENT_QUOTES, 'UTF-8') ?>?');">
                    <input type="hidden" name="accion" value="eliminar_inscripcion">
                    <input type="hidden" name="id_inscripcion" value="<?= $idInscripcion ?>">
                    <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrlFormacion, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-danger btn-sm btn-insc-icon" title="Eliminar inscripción" aria-label="Eliminar inscripción"><i class="bi bi-trash" aria-hidden="true"></i></button>
                </form>
            <?php else: ?>
                <button type="button" class="btn btn-danger btn-sm btn-insc-icon" disabled title="Inscripción inválida" aria-label="Eliminar"><i class="bi bi-trash" aria-hidden="true"></i></button>
            <?php endif; ?>
        <?php elseif ($moduloFormacionActual === 'consolidar'): ?>
            <button type="button" class="btn btn-danger btn-sm btn-insc-icon" disabled title="Sin permiso para eliminar" aria-label="Eliminar"><i class="bi bi-trash" aria-hidden="true"></i></button>
        <?php endif; ?>
    </div>
    <?php
    return (string)ob_get_clean();
};

$filtrosActivos = 0;
if ($filtroBuscar !== '') { $filtrosActivos++; }
if ($filtroMinisterio !== '') { $filtrosActivos++; }
if ($filtroLider !== '') { $filtrosActivos++; }
if ($filtroGenero !== '' && $filtroGenero !== 'todos') { $filtrosActivos++; }

$submodulosDiscipular = [];
if ($moduloFormacionActual === 'discipular') {
    $tarjetasResumenMap = [];
    foreach ($tarjetasResumen as $tarjetaResumen) {
        $tarjetasResumenMap[(string)($tarjetaResumen['programa'] ?? '')] = (int)($tarjetaResumen['total'] ?? 0);
    }

    $programasDiscipular = [
        'capacitacion_destino_nivel_1' => 'Nivel 1',
        'capacitacion_destino_nivel_2' => 'Nivel 2',
        'capacitacion_destino_nivel_3' => 'Nivel 3',
    ];

    foreach ($programasDiscipular as $clavePrograma => $labelPrograma) {
        $paramsSubmodulo = $_GET;
        $paramsSubmodulo['url'] = $rutaBase;
        $paramsSubmodulo['insc_programa'] = $clavePrograma;
        $submodulosDiscipular[] = [
            'programa' => $clavePrograma,
            'label' => $labelPrograma,
            'total' => (int)($tarjetasResumenMap[$clavePrograma] ?? 0),
            'url' => PUBLIC_URL . '?' . http_build_query($paramsSubmodulo),
            'active' => $programaReporte === $clavePrograma,
        ];
    }
}
?>

<?php if (!empty($programasTabs)): ?>
<div class="card report-card" style="margin-bottom:12px; padding:10px 12px;">
    <div class="action-group action-group-nav" style="display:flex; gap:8px; flex-wrap:wrap;">
        <?php foreach ($programasTabs as $tabPrograma): ?>
            <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars((string)($tabPrograma['url'] ?? 'programas')) ?>"
               class="action-pill <?= !empty($tabPrograma['active']) ? 'is-active' : '' ?>"
               <?= !empty($tabPrograma['active']) ? 'aria-current="page"' : '' ?>>
                <?= htmlspecialchars((string)($tabPrograma['label'] ?? 'Sección')) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;"><?= htmlspecialchars($tituloModulo) ?></h2>
        <small style="color:#637087;">Vista Registro. Programa actual: <strong><?= htmlspecialchars($programaReporteLabel) ?></strong>.</small>
    </div>
    <div class="header-actions">
        <div class="action-group action-group-nav">
            <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaBase) ?>" class="action-pill <?= $registroActivo ? 'is-active' : '' ?>" <?= $registroActivo ? 'aria-current="page"' : '' ?>>Registro</a>
            <?php if ($mostrarAccesoAsistencias): ?>
                <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaAsistencias) ?>" class="action-pill <?= $asistenciasActivo ? 'is-active' : '' ?>" <?= $asistenciasActivo ? 'aria-current="page"' : '' ?>>Asistencias</a>
            <?php endif; ?>
        </div>
        <div class="action-group">
            <?php if ($programaRutaActual === 'universidad_vida'): ?>
                <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/registro-publico/universidad-vida" class="action-pill" target="_blank" rel="noopener">Formulario</a>
                <?php if (class_exists('AuthController') && (AuthController::esAdministrador() || AuthController::tienePermiso('material_universidad_vida', 'ver'))): ?>
                    <a href="<?= PUBLIC_URL ?>?url=home/material/universidad-vida" class="action-pill">Material U.V</a>
                <?php endif; ?>
                <?php if ($esModuloConsolidar): ?>
                    <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/pagos/consolidar" class="action-pill">Pagos U. de la Vida</a>
                <?php endif; ?>
                <?php if (class_exists('AuthController') && (AuthController::esAdministrador() || AuthController::tienePermiso('reportes', 'ver'))): ?>
                    <a href="<?= PUBLIC_URL ?>?url=reportes/dashboard-escuelas-uv" class="action-pill">Dashboard U.V</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/registro-publico/capacitacion-destino" class="action-pill" target="_blank" rel="noopener">Formulario</a>
                <?php if (class_exists('AuthController') && (AuthController::esAdministrador() || AuthController::tienePermiso('material_capacitacion_destino', 'ver'))): ?>
                    <a href="<?= PUBLIC_URL ?>?url=home/material/capacitacion-destino" class="action-pill">Material C. Destino</a>
                <?php endif; ?>
                <?php if ($esModuloConsolidar): ?>
                    <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/pagos/enviar" class="action-pill">Pagos Capacitación Destino</a>
                    <?php if (class_exists('AuthController') && (AuthController::esAdministrador() || AuthController::tienePermiso('reportes', 'ver'))): ?>
                    <a href="<?= PUBLIC_URL ?>?url=reportes/dashboard-escuelas-capacitacion" class="action-pill">Dashboard C. Destino</a>
                    <?php endif; ?>
                <?php elseif ($esModuloEnviar): ?>
                    <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/pagos/enviar" class="action-pill">Pagos Capacitación Destino</a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($urlVolverContextual, ENT_QUOTES, 'UTF-8') ?>" class="action-pill"><?= htmlspecialchars($etiquetaVolverContextual, ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>
</div>

<?php if (!empty($submodulosDiscipular) && !$mostrarDetalleDiscipular): ?>
    <div class="dashboard-grid" style="grid-template-columns:repeat(3,minmax(0,1fr)); margin:0 0 14px 0;">
        <?php foreach ($submodulosDiscipular as $submodulo): ?>
            <a href="<?= htmlspecialchars((string)$submodulo['url']) ?>" class="gender-card dashboard-card <?= !empty($submodulo['active']) ? 'is-active' : '' ?>" style="border-left-color:<?= !empty($submodulo['active']) ? '#1f5ea8' : '#7a4e08' ?>; text-decoration:none; color:inherit;">
                <div class="gender-card-toggle" style="cursor:pointer;">
                    <span class="gender-card-title-wrap">
                        <span class="gender-avatar" aria-hidden="true">📘</span>
                        <span>Capacitación Destino - <?= htmlspecialchars((string)$submodulo['label']) ?></span>
                    </span>
                    <span class="gender-card-icon">Ver</span>
                </div>
                <div class="gender-card-metric">
                    <div class="gender-kpi-grid">
                        <div class="gender-kpi-box" style="width:100%;">
                            <span class="kpi-label">Inscritos</span>
                            <strong class="kpi-value" style="color:#7a4e08;"><?= (int)($submodulo['total'] ?? 0) ?></strong>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($moduloFormacionActual === 'discipular' && !$mostrarDetalleDiscipular): ?>
    <div class="card report-card" style="margin-bottom:18px; padding:18px; text-align:center;">
        <h3 style="margin:0 0 8px 0;">Selecciona un nivel de Capacitación Destino</h3>
        <small style="color:#637087;">Al abrir un nivel verás su información independiente de inscritos del formulario, separada por jóvenes, hombres y mujeres.</small>
    </div>
<?php else: ?>

<div class="card report-card" style="margin-bottom:18px; padding:14px;">
    <div class="filters-toolbar" style="margin-bottom:10px;">
        <button type="button" class="btn btn-secondary js-toggle-filtros-btn" aria-controls="panel-filtros-formacion" aria-expanded="<?= $filtrosActivos > 0 ? 'true' : 'false' ?>">
            <?= $filtrosActivos > 0 ? 'Ocultar filtros' : 'Mostrar filtros' ?>
        </button>
    </div>

    <div class="filters-panel" id="panel-filtros-formacion" <?= $filtrosActivos > 0 ? '' : 'hidden' ?>>
        <form method="GET" action="<?= PUBLIC_URL ?>" class="filters-inline" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
            <input type="hidden" name="url" value="<?= htmlspecialchars($rutaBase) ?>">
            <?php if ($programaReporte !== ''): ?>
                <input type="hidden" name="insc_programa" value="<?= htmlspecialchars($programaReporte) ?>">
            <?php endif; ?>

            <div class="form-group" style="margin:0; min-width:280px;">
                <label for="filtro_buscar">Buscar por nombre, cédula o teléfono</label>
                <input type="text" id="filtro_buscar" name="buscar" class="form-control" placeholder="Nombre, cédula o teléfono" value="<?= htmlspecialchars($filtroBuscar) ?>">
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

<?php if ($mostrarTablaProgramaMinisterio): ?>
<div class="card report-card" style="margin-bottom:14px; padding:14px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="margin:0;"><?= htmlspecialchars($tituloTablaProgramaMinisterio) ?></h3>
        <small style="color:#637087;">Inscritos por hombres, mujeres y jóvenes</small>
    </div>

    <div class="table-container formacion-resumen-table-wrap">
        <table class="data-table formacion-resumen-table">
            <thead>
                <tr>
                    <th>Ministerio</th>
                    <th style="color:#1e4a89;">Hombres</th>
                    <th style="color:#8b1c62;">Mujeres</th>
                    <th style="color:#0f766e;">Jóvenes</th>
                    <th style="color:#166534;">Total</th>
                    <th style="color:#7c3aed;">Asistencias reales</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totUvH = 0;
                $totUvM = 0;
                $totUvJ = 0;
                $totUvT = 0;
                $totUvAsis = 0;
                ?>
                <?php if (!empty($tablaUvMinisterio)): ?>
                    <?php foreach ($tablaUvMinisterio as $filaUvMin): ?>
                        <?php
                        $uvH = (int)($filaUvMin['hombres'] ?? 0);
                        $uvM = (int)($filaUvMin['mujeres'] ?? 0);
                        $uvJ = (int)($filaUvMin['jovenes'] ?? 0);
                        $uvT = (int)($filaUvMin['total'] ?? 0);
                        $uvAsis = (int)($filaUvMin['asistencias_reales'] ?? 0);
                        $ministerioNombre = (string)($filaUvMin['ministerio'] ?? 'Sin ministerio');

                        $totUvH += $uvH;
                        $totUvM += $uvM;
                        $totUvJ += $uvJ;
                        $totUvT += $uvT;
                        $totUvAsis += $uvAsis;
                        ?>
                        <tr>
                            <td>
                                <button type="button" class="report-link-button js-open-ministerio-uv" data-ministerio="<?= htmlspecialchars($ministerioNombre, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($ministerioNombre) ?>
                                </button>
                            </td>
                            <td style="color:#1e4a89;"><strong><?= $uvH ?></strong></td>
                            <td style="color:#8b1c62;"><strong><?= $uvM ?></strong></td>
                            <td style="color:#0f766e;"><strong><?= $uvJ ?></strong></td>
                            <td style="color:#166534;"><strong><?= $uvT ?></strong></td>
                            <td style="color:#7c3aed;"><strong><?= $uvAsis ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="reporte-metas-total-row">
                        <td><strong>TOTAL</strong></td>
                        <td style="color:#1e4a89;"><strong><?= $totUvH ?></strong></td>
                        <td style="color:#8b1c62;"><strong><?= $totUvM ?></strong></td>
                        <td style="color:#0f766e;"><strong><?= $totUvJ ?></strong></td>
                        <td style="color:#166534;"><strong><?= $totUvT ?></strong></td>
                        <td style="color:#7c3aed;"><strong><?= $totUvAsis ?></strong></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center"><?= htmlspecialchars($mensajeSinInscripcionesPrograma) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($submodulosDiscipular) && $mostrarDetalleDiscipular): ?>
    <div class="dashboard-grid" style="grid-template-columns:repeat(3,minmax(0,1fr)); margin:0 0 14px 0;">
        <?php foreach ($submodulosDiscipular as $submodulo): ?>
            <a href="<?= htmlspecialchars((string)$submodulo['url']) ?>" class="gender-card dashboard-card <?= !empty($submodulo['active']) ? 'is-active' : '' ?>" style="border-left-color:<?= !empty($submodulo['active']) ? '#1f5ea8' : '#7a4e08' ?>; text-decoration:none; color:inherit;">
                <div class="gender-card-toggle" style="cursor:pointer;">
                    <span class="gender-card-title-wrap">
                        <span class="gender-avatar" aria-hidden="true">📘</span>
                        <span>Capacitación Destino - <?= htmlspecialchars((string)$submodulo['label']) ?></span>
                    </span>
                    <span class="gender-card-icon">Ver</span>
                </div>
                <div class="gender-card-metric">
                    <div class="gender-kpi-grid">
                        <div class="gender-kpi-box" style="width:100%;">
                            <span class="kpi-label">Inscritos</span>
                            <strong class="kpi-value" style="color:#7a4e08;"><?= (int)($submodulo['total'] ?? 0) ?></strong>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card report-card" style="padding:14px;">
    <?php
    $esReporteCapDestino = $programaRutaActual === 'capacitacion_destino';

    $inscripcionesNivel1 = [];
    $inscripcionesNivel2 = [];
    $inscripcionesNivel3 = [];
    $inscripcionesJovenes = [];
    $inscripcionesTeens = [];
    $inscripcionesHombresAdultos = [];
    $inscripcionesMujeresAdultas = [];
    $inscripcionesOtros = [];

    if ($esReporteCapDestino) {
        foreach ($inscripcionesPublicas as $ins) {
            $programaInscripcion = strtolower(trim((string)($ins['Programa'] ?? '')));
            $segmentoPreferido = strtolower(trim((string)($ins['Segmento_Preferido'] ?? '')));
            $nivelPreferido = in_array($segmentoPreferido, ['nivel_1', 'nivel_2', 'nivel_3'], true)
                ? $segmentoPreferido
                : '';

            if ($nivelPreferido !== '') {
                if ($nivelPreferido === 'nivel_1') {
                    $inscripcionesNivel1[] = $ins;
                } elseif ($nivelPreferido === 'nivel_2') {
                    $inscripcionesNivel2[] = $ins;
                } else {
                    $inscripcionesNivel3[] = $ins;
                }
                continue;
            }

            if ($programaInscripcion === 'capacitacion_destino' || $programaInscripcion === 'capacitacion_destino_nivel_1') {
                $inscripcionesNivel1[] = $ins;
            } elseif ($programaInscripcion === 'capacitacion_destino_nivel_2') {
                $inscripcionesNivel2[] = $ins;
            } elseif ($programaInscripcion === 'capacitacion_destino_nivel_3') {
                $inscripcionesNivel3[] = $ins;
            } else {
                $inscripcionesOtros[] = $ins;
            }
        }

        $registroVistaInicial = !empty($inscripcionesNivel1)
            ? 'nivel_1'
            : (!empty($inscripcionesNivel2)
                ? 'nivel_2'
                : 'nivel_3');

        $registroNivel1Activo = $registroVistaInicial === 'nivel_1';
        $registroNivel2Activo = $registroVistaInicial === 'nivel_2';
        $registroNivel3Activo = $registroVistaInicial === 'nivel_3';

        $nivel1Registrados = (int)count($inscripcionesNivel1);
        $nivel2Registrados = (int)count($inscripcionesNivel2);
        $nivel3Registrados = (int)count($inscripcionesNivel3);
        $otrosRegistrados = (int)count($inscripcionesOtros);
        $totalRegistrosVisibles = $nivel1Registrados + $nivel2Registrados + $nivel3Registrados + $otrosRegistrados;
    } else {
        $inscripcionesHombresUnificadas = [];
        $inscripcionesMujeresUnificadas = [];
        $clasificarGeneroRegistro = static function($valorGenero): string {
            $genero = trim((string)$valorGenero);
            if ($genero === '') {
                return 'otro';
            }

            $genero = function_exists('mb_strtolower')
                ? mb_strtolower($genero, 'UTF-8')
                : strtolower($genero);

            $esMujer = strpos($genero, 'mujer') !== false
                || strpos($genero, 'femen') !== false
                || preg_match('/(^|[^a-z])(f|fem|female)([^a-z]|$)/', $genero);
            $esHombre = strpos($genero, 'hombre') !== false
                || strpos($genero, 'mascul') !== false
                || preg_match('/(^|[^a-z])(m|masc|male|h)([^a-z]|$)/', $genero);

            if ($esHombre && !$esMujer) {
                return 'hombre';
            }

            if ($esMujer && !$esHombre) {
                return 'mujer';
            }

            return 'otro';
        };

        foreach ($inscripcionesPublicas as $ins) {
            $edadIns = (int)($ins['Edad'] ?? 0);
            $generoClasificado = $clasificarGeneroRegistro((string)($ins['Genero'] ?? ''));
            $esMujer = $generoClasificado === 'mujer';
            $esHombre = $generoClasificado === 'hombre';

            // Usar segmento preferido si está guardado
            $segmentoPreferido = trim((string)($ins['Segmento_Preferido'] ?? ''));
            $segmentoDeterminado = '';

            if ($segmentoPreferido !== '') {
                // Usar el segmento preferido
                $segmentoDeterminado = $segmentoPreferido;
            } else {
                // Clasificar por edad y género
                if ($edadIns >= 14 && $edadIns <= 28) {
                    $segmentoDeterminado = 'jovenes';
                } elseif ($edadIns >= 9 && $edadIns <= 13) {
                    $segmentoDeterminado = 'teens';
                } elseif (($edadIns >= 29 || $edadIns <= 0) && $esHombre) {
                    $segmentoDeterminado = 'hombres_adultos';
                } elseif (($edadIns >= 29 || $edadIns <= 0) && $esMujer) {
                    $segmentoDeterminado = 'mujeres_adultas';
                }
            }

            $ins['_segmento_actual'] = $segmentoDeterminado;

            if ($esHombre) {
                $inscripcionesHombresUnificadas[] = $ins;
            }

            if ($esMujer) {
                $inscripcionesMujeresUnificadas[] = $ins;
            }

            // Clasificar en el array correspondiente
            if ($segmentoDeterminado === 'jovenes') {
                $inscripcionesJovenes[] = $ins;
            } elseif ($segmentoDeterminado === 'teens') {
                $inscripcionesTeens[] = $ins;
            } elseif ($segmentoDeterminado === 'hombres_adultos') {
                $inscripcionesHombresAdultos[] = $ins;
            } elseif ($segmentoDeterminado === 'mujeres_adultas') {
                $inscripcionesMujeresAdultas[] = $ins;
            } else {
                $inscripcionesOtros[] = $ins;
            }
        }

        $registroVistaInicial = !empty($inscripcionesJovenes)
            ? 'jovenes'
            : (!empty($inscripcionesTeens)
                ? 'teens'
                : (!empty($inscripcionesHombresAdultos)
                    ? 'hombres_adultos'
                    : 'mujeres_adultas'));
        $registroJovenesActivo = $registroVistaInicial === 'jovenes';
        $registroTeensActivo = $registroVistaInicial === 'teens';
        $registroHombresAdultosActivo = $registroVistaInicial === 'hombres_adultos';
        $registroMujeresAdultasActivo = $registroVistaInicial === 'mujeres_adultas';
        $registroHombresUnificadosActivo = false;
        $registroMujeresUnificadasActivo = false;
        $jovenesRegistrados = (int)count($inscripcionesJovenes);
        $teensRegistrados = (int)count($inscripcionesTeens);
        $hombresAdultosRegistrados = (int)count($inscripcionesHombresAdultos);
        $mujeresAdultasRegistradas = (int)count($inscripcionesMujeresAdultas);
        $hombresUnificadosRegistrados = (int)count($inscripcionesHombresUnificadas);
        $mujeresUnificadasRegistradas = (int)count($inscripcionesMujeresUnificadas);
        $otrosRegistrados = (int)count($inscripcionesOtros);
        $totalRegistrosVisibles = $jovenesRegistrados + $teensRegistrados + $hombresAdultosRegistrados + $mujeresAdultasRegistradas + $otrosRegistrados;
    }
    ?>

    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="margin:0;">Registros del formulario publico: <?= htmlspecialchars($programaReporteLabel) ?></h3>
        <small style="color:#637087;">Mostrando <?= $totalRegistrosVisibles ?> registros recientes (max. 300)</small>
    </div>

    <div class="card report-card" style="margin-bottom:12px; padding:0; overflow:hidden;">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= $esReporteCapDestino ? 'Nivel' : 'Segmento' ?></th>
                        <th><?= $esReporteCapDestino ? 'Descripción' : 'Rango de edad' ?></th>
                        <th>Registrados</th>
                        <th style="width:120px;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($esReporteCapDestino): ?>
                    <tr class="registro-resumen-row <?= $registroNivel1Activo ? 'is-active' : '' ?>" data-view-target="registro_view_nivel_1">
                        <td>Nivel 1</td>
                        <td>Introducción</td>
                        <td><strong style="color:#1e6b3c;"><?= $nivel1Registrados ?></strong></td>
                        <td>
                            <button type="button" class="btn btn-sm js-gender-view-btn js-registro-selector <?= $registroNivel1Activo ? 'btn-primary' : 'btn-secondary' ?>" data-view-target="registro_view_nivel_1" aria-controls="registro_view_nivel_1" aria-expanded="<?= $registroNivel1Activo ? 'true' : 'false' ?>">Ver</button>
                        </td>
                    </tr>
                    <tr class="registro-resumen-row <?= $registroNivel2Activo ? 'is-active' : '' ?>" data-view-target="registro_view_nivel_2">
                        <td>Nivel 2</td>
                        <td>Intermedio</td>
                        <td><strong style="color:#7b3fa0;"><?= $nivel2Registrados ?></strong></td>
                        <td>
                            <button type="button" class="btn btn-sm js-gender-view-btn js-registro-selector <?= $registroNivel2Activo ? 'btn-primary' : 'btn-secondary' ?>" data-view-target="registro_view_nivel_2" aria-controls="registro_view_nivel_2" aria-expanded="<?= $registroNivel2Activo ? 'true' : 'false' ?>">Ver</button>
                        </td>
                    </tr>
                    <tr class="registro-resumen-row <?= $registroNivel3Activo ? 'is-active' : '' ?>" data-view-target="registro_view_nivel_3">
                        <td>Nivel 3</td>
                        <td>Avanzado</td>
                        <td><strong style="color:#1e4a89;"><?= $nivel3Registrados ?></strong></td>
                        <td>
                            <button type="button" class="btn btn-sm js-gender-view-btn js-registro-selector <?= $registroNivel3Activo ? 'btn-primary' : 'btn-secondary' ?>" data-view-target="registro_view_nivel_3" aria-controls="registro_view_nivel_3" aria-expanded="<?= $registroNivel3Activo ? 'true' : 'false' ?>">Ver</button>
                        </td>
                    </tr>
                    <?php else: ?>
                    <tr class="registro-resumen-row <?= $registroJovenesActivo ? 'is-active' : '' ?>" data-view-target="registro_view_jovenes">
                        <td>Jóvenes</td>
                        <td>14-28 años</td>
                        <td><strong style="color:#1e6b3c;"><?= $jovenesRegistrados ?></strong></td>
                        <td>
                            <button type="button" class="btn btn-sm js-gender-view-btn js-registro-selector <?= $registroJovenesActivo ? 'btn-primary' : 'btn-secondary' ?>" data-view-target="registro_view_jovenes" aria-controls="registro_view_jovenes" aria-expanded="<?= $registroJovenesActivo ? 'true' : 'false' ?>">Ver</button>
                        </td>
                    </tr>
                    <tr class="registro-resumen-row <?= $registroTeensActivo ? 'is-active' : '' ?>" data-view-target="registro_view_teens">
                        <td>Teens</td>
                        <td>9-13 años</td>
                        <td><strong style="color:#7b3fa0;"><?= $teensRegistrados ?></strong></td>
                        <td>
                            <button type="button" class="btn btn-sm js-gender-view-btn js-registro-selector <?= $registroTeensActivo ? 'btn-primary' : 'btn-secondary' ?>" data-view-target="registro_view_teens" aria-controls="registro_view_teens" aria-expanded="<?= $registroTeensActivo ? 'true' : 'false' ?>">Ver</button>
                        </td>
                    </tr>
                    <tr class="registro-resumen-row <?= $registroHombresAdultosActivo ? 'is-active' : '' ?>" data-view-target="registro_view_hombres_adultos">
                        <td>Hombres</td>
                        <td>29+ años</td>
                        <td><strong style="color:#1e4a89;"><?= $hombresAdultosRegistrados ?></strong></td>
                        <td>
                            <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                <button type="button" class="btn btn-sm js-gender-view-btn js-registro-selector <?= $registroHombresAdultosActivo ? 'btn-primary' : 'btn-secondary' ?>" data-view-target="registro_view_hombres_adultos" aria-controls="registro_view_hombres_adultos" aria-expanded="<?= $registroHombresAdultosActivo ? 'true' : 'false' ?>">Ver</button>
                                <button type="button" class="btn btn-sm js-gender-view-btn js-registro-selector <?= $registroHombresUnificadosActivo ? 'btn-primary' : 'btn-info' ?>" data-view-target="registro_view_hombres_unificados" aria-controls="registro_view_hombres_unificados" aria-expanded="<?= $registroHombresUnificadosActivo ? 'true' : 'false' ?>">Unificar</button>
                            </div>
                        </td>
                    </tr>
                    <tr class="registro-resumen-row <?= $registroMujeresAdultasActivo ? 'is-active' : '' ?>" data-view-target="registro_view_mujeres_adultas">
                        <td>Mujeres</td>
                        <td>29+ años</td>
                        <td><strong style="color:#8b1c62;"><?= $mujeresAdultasRegistradas ?></strong></td>
                        <td>
                            <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                <button type="button" class="btn btn-sm js-gender-view-btn js-registro-selector <?= $registroMujeresAdultasActivo ? 'btn-primary' : 'btn-secondary' ?>" data-view-target="registro_view_mujeres_adultas" aria-controls="registro_view_mujeres_adultas" aria-expanded="<?= $registroMujeresAdultasActivo ? 'true' : 'false' ?>">Ver</button>
                                <button type="button" class="btn btn-sm js-gender-view-btn js-registro-selector <?= $registroMujeresUnificadasActivo ? 'btn-primary' : 'btn-info' ?>" data-view-target="registro_view_mujeres_unificadas" aria-controls="registro_view_mujeres_unificadas" aria-expanded="<?= $registroMujeresUnificadasActivo ? 'true' : 'false' ?>">Unificar</button>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($esReporteCapDestino): ?>
    <div id="registro_view_nivel_1" class="gender-full-view" <?= $registroNivel1Activo ? '' : 'hidden' ?>>
        <div class="registro-view-head">Listado completo · Nivel 1</div>
        <div class="table-container">
            <table class="data-table insc-table-ordenada">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre</th>
                        <th>Edad</th>
                        <th>Genero</th>
                        <th>Cedula</th>
                        <th>Telefono</th>
                        <th>Lider</th>
                        <th class="col-acciones-insc">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inscripcionesNivel1)): ?>
                        <?php foreach ($inscripcionesNivel1 as $ins): ?>
                            <?php $idPersonaIns = (int)($ins['Id_Persona'] ?? 0); ?>
                            <tr>
                                <td data-label="Fecha"><?= htmlspecialchars((string)($ins['Fecha_Registro'] ?? '')) ?></td>
                                <td data-label="Nombre" class="col-nowrap col-nombre"><?= htmlspecialchars((string)($ins['Nombre'] ?? '')) ?></td>
                                <td data-label="Edad"><?= (int)($ins['Edad'] ?? 0) ?></td>
                                <td data-label="Genero"><?= htmlspecialchars((string)($ins['Genero'] ?? '')) ?></td>
                                <td data-label="Cedula"><?= htmlspecialchars((string)($ins['Cedula'] ?? '')) ?></td>
                                <td data-label="Telefono"><?= htmlspecialchars((string)($ins['Telefono'] ?? '')) ?></td>
                                <td data-label="Lider" class="col-nowrap col-lider"><?= htmlspecialchars((string)($ins['Lider'] ?? '')) ?></td>
                                <td data-label="Acción" class="text-center">
                                    <?= $renderAccionesRegistroFormacion($ins, $idPersonaIns, 'nivel_1') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay registros en Nivel 1 con estos filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="registro_view_nivel_2" class="gender-full-view" <?= $registroNivel2Activo ? '' : 'hidden' ?>>
        <div class="registro-view-head">Listado completo · Nivel 2</div>
        <div class="table-container">
            <table class="data-table insc-table-ordenada">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre</th>
                        <th>Edad</th>
                        <th>Genero</th>
                        <th>Cedula</th>
                        <th>Telefono</th>
                        <th>Lider</th>
                        <th class="col-acciones-insc">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inscripcionesNivel2)): ?>
                        <?php foreach ($inscripcionesNivel2 as $ins): ?>
                            <?php $idPersonaIns = (int)($ins['Id_Persona'] ?? 0); ?>
                            <tr>
                                <td data-label="Fecha"><?= htmlspecialchars((string)($ins['Fecha_Registro'] ?? '')) ?></td>
                                <td data-label="Nombre" class="col-nowrap col-nombre"><?= htmlspecialchars((string)($ins['Nombre'] ?? '')) ?></td>
                                <td data-label="Edad"><?= (int)($ins['Edad'] ?? 0) ?></td>
                                <td data-label="Genero"><?= htmlspecialchars((string)($ins['Genero'] ?? '')) ?></td>
                                <td data-label="Cedula"><?= htmlspecialchars((string)($ins['Cedula'] ?? '')) ?></td>
                                <td data-label="Telefono"><?= htmlspecialchars((string)($ins['Telefono'] ?? '')) ?></td>
                                <td data-label="Lider" class="col-nowrap col-lider"><?= htmlspecialchars((string)($ins['Lider'] ?? '')) ?></td>
                                <td data-label="Acción" class="text-center">
                                    <?= $renderAccionesRegistroFormacion($ins, $idPersonaIns, 'nivel_2') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay registros en Nivel 2 con estos filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="registro_view_nivel_3" class="gender-full-view" <?= $registroNivel3Activo ? '' : 'hidden' ?>>
        <div class="registro-view-head">Listado completo · Nivel 3</div>
        <div class="table-container">
            <table class="data-table insc-table-ordenada">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre</th>
                        <th>Edad</th>
                        <th>Genero</th>
                        <th>Cedula</th>
                        <th>Telefono</th>
                        <th>Lider</th>
                        <th class="col-acciones-insc">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inscripcionesNivel3)): ?>
                        <?php foreach ($inscripcionesNivel3 as $ins): ?>
                            <?php $idPersonaIns = (int)($ins['Id_Persona'] ?? 0); ?>
                            <tr>
                                <td data-label="Fecha"><?= htmlspecialchars((string)($ins['Fecha_Registro'] ?? '')) ?></td>
                                <td data-label="Nombre" class="col-nowrap col-nombre"><?= htmlspecialchars((string)($ins['Nombre'] ?? '')) ?></td>
                                <td data-label="Edad"><?= (int)($ins['Edad'] ?? 0) ?></td>
                                <td data-label="Genero"><?= htmlspecialchars((string)($ins['Genero'] ?? '')) ?></td>
                                <td data-label="Cedula"><?= htmlspecialchars((string)($ins['Cedula'] ?? '')) ?></td>
                                <td data-label="Telefono"><?= htmlspecialchars((string)($ins['Telefono'] ?? '')) ?></td>
                                <td data-label="Lider" class="col-nowrap col-lider"><?= htmlspecialchars((string)($ins['Lider'] ?? '')) ?></td>
                                <td data-label="Acción" class="text-center">
                                    <?= $renderAccionesRegistroFormacion($ins, $idPersonaIns, 'nivel_3') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay registros en Nivel 3 con estos filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php else: ?>
    <div id="registro_view_jovenes" class="gender-full-view" <?= $registroJovenesActivo ? '' : 'hidden' ?>>
        <div class="registro-view-head">Listado completo · Jóvenes (14-28 años)</div>
        <div class="table-container">
            <table class="data-table insc-table-ordenada">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre</th>
                        <th>Edad</th>
                        <th>Genero</th>
                        <th>Cedula</th>
                        <th>Telefono</th>
                        <th>Lider</th>
                        <th class="col-acciones-insc">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inscripcionesJovenes)): ?>
                        <?php foreach ($inscripcionesJovenes as $ins): ?>
                            <?php $idPersonaIns = (int)($ins['Id_Persona'] ?? 0); ?>
                            <tr>
                                <td data-label="Fecha"><?= htmlspecialchars((string)($ins['Fecha_Registro'] ?? '')) ?></td>
                                <td data-label="Nombre" class="col-nowrap col-nombre"><?= htmlspecialchars((string)($ins['Nombre'] ?? '')) ?></td>
                                <td data-label="Edad"><?= (int)($ins['Edad'] ?? 0) ?></td>
                                <td data-label="Genero"><?= htmlspecialchars((string)($ins['Genero'] ?? '')) ?></td>
                                <td data-label="Cedula"><?= htmlspecialchars((string)($ins['Cedula'] ?? '')) ?></td>
                                <td data-label="Telefono"><?= htmlspecialchars((string)($ins['Telefono'] ?? '')) ?></td>
                                <td data-label="Lider" class="col-nowrap col-lider"><?= htmlspecialchars((string)($ins['Lider'] ?? '')) ?></td>
                                <td data-label="Acción" class="text-center">
                                    <?= $renderAccionesRegistroFormacion($ins, $idPersonaIns, 'jovenes') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay jóvenes (14-28 años) registrados con estos filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="registro_view_teens" class="gender-full-view" <?= $registroTeensActivo ? '' : 'hidden' ?>>
        <div class="registro-view-head">Listado completo · Teens (9-13 años)</div>
        <div class="table-container">
            <table class="data-table insc-table-ordenada">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre</th>
                        <th>Edad</th>
                        <th>Genero</th>
                        <th>Cedula</th>
                        <th>Telefono</th>
                        <th>Lider</th>
                        <th class="col-acciones-insc">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inscripcionesTeens)): ?>
                        <?php foreach ($inscripcionesTeens as $ins): ?>
                            <?php $idPersonaIns = (int)($ins['Id_Persona'] ?? 0); ?>
                            <tr>
                                <td data-label="Fecha"><?= htmlspecialchars((string)($ins['Fecha_Registro'] ?? '')) ?></td>
                                <td data-label="Nombre" class="col-nowrap col-nombre"><?= htmlspecialchars((string)($ins['Nombre'] ?? '')) ?></td>
                                <td data-label="Edad"><?= (int)($ins['Edad'] ?? 0) ?></td>
                                <td data-label="Genero"><?= htmlspecialchars((string)($ins['Genero'] ?? '')) ?></td>
                                <td data-label="Cedula"><?= htmlspecialchars((string)($ins['Cedula'] ?? '')) ?></td>
                                <td data-label="Telefono"><?= htmlspecialchars((string)($ins['Telefono'] ?? '')) ?></td>
                                <td data-label="Lider" class="col-nowrap col-lider"><?= htmlspecialchars((string)($ins['Lider'] ?? '')) ?></td>
                                <td data-label="Acción" class="text-center">
                                    <?= $renderAccionesRegistroFormacion($ins, $idPersonaIns, 'jovenes') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay teens (9-13 años) registrados con estos filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="registro_view_hombres_adultos" class="gender-full-view" <?= $registroHombresAdultosActivo ? '' : 'hidden' ?>>
        <div class="registro-view-head">Listado completo · Hombres (29+ años)</div>
        <div class="table-container">
            <table class="data-table insc-table-ordenada">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre</th>
                        <th>Edad</th>
                        <th>Cedula</th>
                        <th>Telefono</th>
                        <th>Lider</th>
                        <th class="col-acciones-insc">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inscripcionesHombresAdultos)): ?>
                        <?php foreach ($inscripcionesHombresAdultos as $ins): ?>
                            <?php $idPersonaIns = (int)($ins['Id_Persona'] ?? 0); ?>
                            <tr>
                                <td data-label="Fecha"><?= htmlspecialchars((string)($ins['Fecha_Registro'] ?? '')) ?></td>
                                <td data-label="Nombre" class="col-nowrap col-nombre"><?= htmlspecialchars((string)($ins['Nombre'] ?? '')) ?></td>
                                <td data-label="Edad"><?= (int)($ins['Edad'] ?? 0) ?></td>
                                <td data-label="Cedula"><?= htmlspecialchars((string)($ins['Cedula'] ?? '')) ?></td>
                                <td data-label="Telefono"><?= htmlspecialchars((string)($ins['Telefono'] ?? '')) ?></td>
                                <td data-label="Lider" class="col-nowrap col-lider"><?= htmlspecialchars((string)($ins['Lider'] ?? '')) ?></td>
                                    <td data-label="Acción" class="text-center">
                                        <?= $renderAccionesRegistroFormacion($ins, $idPersonaIns, 'hombres_adultos') ?>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay hombres de 29+ años registrados con estos filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="registro_view_mujeres_adultas" class="gender-full-view" <?= $registroMujeresAdultasActivo ? '' : 'hidden' ?>>
        <div class="registro-view-head">Listado completo · Mujeres (29+ años)</div>
        <div class="table-container">
            <table class="data-table insc-table-ordenada">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre</th>
                        <th>Edad</th>
                        <th>Cedula</th>
                        <th>Telefono</th>
                        <th>Lider</th>
                        <th class="col-acciones-insc">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inscripcionesMujeresAdultas)): ?>
                        <?php foreach ($inscripcionesMujeresAdultas as $ins): ?>
                            <?php $idPersonaIns = (int)($ins['Id_Persona'] ?? 0); ?>
                            <tr>
                                <td data-label="Fecha"><?= htmlspecialchars((string)($ins['Fecha_Registro'] ?? '')) ?></td>
                                <td data-label="Nombre" class="col-nowrap col-nombre"><?= htmlspecialchars((string)($ins['Nombre'] ?? '')) ?></td>
                                <td data-label="Edad"><?= (int)($ins['Edad'] ?? 0) ?></td>
                                <td data-label="Cedula"><?= htmlspecialchars((string)($ins['Cedula'] ?? '')) ?></td>
                                <td data-label="Telefono"><?= htmlspecialchars((string)($ins['Telefono'] ?? '')) ?></td>
                                <td data-label="Lider" class="col-nowrap col-lider"><?= htmlspecialchars((string)($ins['Lider'] ?? '')) ?></td>
                                <td data-label="Acción" class="text-center">
                                    <?= $renderAccionesRegistroFormacion($ins, $idPersonaIns, 'mujeres_adultas') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay mujeres de 29+ años registradas con estos filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="registro_view_hombres_unificados" class="gender-full-view" <?= $registroHombresUnificadosActivo ? '' : 'hidden' ?> >
        <div class="registro-view-head">Listado unificado · Hombres (jóvenes + adultos)</div>
        <div class="table-container">
            <table class="data-table insc-table-ordenada">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre</th>
                        <th>Edad</th>
                        <th>Genero</th>
                        <th>Cedula</th>
                        <th>Telefono</th>
                        <th>Lider</th>
                        <th class="col-acciones-insc">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inscripcionesHombresUnificadas)): ?>
                        <?php foreach ($inscripcionesHombresUnificadas as $ins): ?>
                            <?php $idPersonaIns = (int)($ins['Id_Persona'] ?? 0); ?>
                            <?php $segmentoAccion = (string)($ins['_segmento_actual'] ?? 'hombres_adultos'); ?>
                            <tr>
                                <td data-label="Fecha"><?= htmlspecialchars((string)($ins['Fecha_Registro'] ?? '')) ?></td>
                                <td data-label="Nombre" class="col-nowrap col-nombre"><?= htmlspecialchars((string)($ins['Nombre'] ?? '')) ?></td>
                                <td data-label="Edad"><?= (int)($ins['Edad'] ?? 0) ?></td>
                                <td data-label="Genero"><?= htmlspecialchars((string)($ins['Genero'] ?? '')) ?></td>
                                <td data-label="Cedula"><?= htmlspecialchars((string)($ins['Cedula'] ?? '')) ?></td>
                                <td data-label="Telefono"><?= htmlspecialchars((string)($ins['Telefono'] ?? '')) ?></td>
                                <td data-label="Lider" class="col-nowrap col-lider"><?= htmlspecialchars((string)($ins['Lider'] ?? '')) ?></td>
                                <td data-label="Acción" class="text-center">
                                    <?= $renderAccionesRegistroFormacion($ins, $idPersonaIns, $segmentoAccion) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay hombres para unificar con estos filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <small style="display:block;margin-top:8px;color:#637087;">Total unificado hombres: <?= $hombresUnificadosRegistrados ?></small>
    </div>

    <div id="registro_view_mujeres_unificadas" class="gender-full-view" <?= $registroMujeresUnificadasActivo ? '' : 'hidden' ?> >
        <div class="registro-view-head">Listado unificado · Mujeres (jóvenes + adultas)</div>
        <div class="table-container">
            <table class="data-table insc-table-ordenada">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre</th>
                        <th>Edad</th>
                        <th>Genero</th>
                        <th>Cedula</th>
                        <th>Telefono</th>
                        <th>Lider</th>
                        <th class="col-acciones-insc">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inscripcionesMujeresUnificadas)): ?>
                        <?php foreach ($inscripcionesMujeresUnificadas as $ins): ?>
                            <?php $idPersonaIns = (int)($ins['Id_Persona'] ?? 0); ?>
                            <?php $segmentoAccion = (string)($ins['_segmento_actual'] ?? 'mujeres_adultas'); ?>
                            <tr>
                                <td data-label="Fecha"><?= htmlspecialchars((string)($ins['Fecha_Registro'] ?? '')) ?></td>
                                <td data-label="Nombre" class="col-nowrap col-nombre"><?= htmlspecialchars((string)($ins['Nombre'] ?? '')) ?></td>
                                <td data-label="Edad"><?= (int)($ins['Edad'] ?? 0) ?></td>
                                <td data-label="Genero"><?= htmlspecialchars((string)($ins['Genero'] ?? '')) ?></td>
                                <td data-label="Cedula"><?= htmlspecialchars((string)($ins['Cedula'] ?? '')) ?></td>
                                <td data-label="Telefono"><?= htmlspecialchars((string)($ins['Telefono'] ?? '')) ?></td>
                                <td data-label="Lider" class="col-nowrap col-lider"><?= htmlspecialchars((string)($ins['Lider'] ?? '')) ?></td>
                                <td data-label="Acción" class="text-center">
                                    <?= $renderAccionesRegistroFormacion($ins, $idPersonaIns, $segmentoAccion) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay mujeres para unificar con estos filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <small style="display:block;margin-top:8px;color:#637087;">Total unificado mujeres: <?= $mujeresUnificadasRegistradas ?></small>
    </div>
    <?php endif; ?>

    <?php if (!empty($inscripcionesOtros)): ?>
        <small style="display:block; margin-top:10px; color:#637087;">
            <?php if ($esReporteCapDestino): ?>
                Hay <?= $otrosRegistrados ?> registro(s) sin nivel reconocido en Capacitación Destino.
            <?php else: ?>
                Hay <?= $otrosRegistrados ?> registro(s) sin edad válida, menores de 9 años o mayores de 29 sin género reconocible.
            <?php endif; ?>
        </small>
    <?php endif; ?>
</div>
<?php endif; ?>

<div id="modal-ministerio-uv" class="segmento-modal" hidden>
    <div class="modal-backdrop"></div>
    <div class="modal-content" style="width:95%;max-width:860px;margin:70px auto;padding:20px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);z-index:9999;position:relative;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;border-bottom:1px solid #eee;padding-bottom:12px;">
            <h3 style="margin:0;">Líderes por ministerio</h3>
            <button type="button" class="js-cerrar-modal-ministerio-uv" style="background:none;border:none;font-size:24px;cursor:pointer;color:#666;">×</button>
        </div>
        <div style="margin-bottom:12px;">
            <strong>Ministerio:</strong>
            <span id="modal-ministerio-uv-nombre" style="color:#0a6e6a;">-</span>
        </div>
        <div class="table-container formacion-detalle-ministerio-wrap">
            <table class="data-table data-table--compacta-celula formacion-detalle-ministerio-table">
                <thead>
                    <tr>
                        <th>Líder</th>
                        <th style="color:#1e4a89;">Hombres</th>
                        <th style="color:#8b1c62;">Mujeres</th>
                        <th style="color:#0f766e;">Jóvenes</th>
                        <th style="color:#166534;">Total</th>
                        <th style="color:#7c3aed;">Asistencias reales</th>
                    </tr>
                </thead>
                <tbody id="modal-ministerio-uv-body"></tbody>
            </table>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:12px;border-top:1px solid #eee;margin-top:12px;">
            <button type="button" class="btn btn-secondary js-cerrar-modal-ministerio-uv">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal de Cambio de Segmento -->
<div id="modal-cambio-segmento" class="segmento-modal" hidden>
    <div class="modal-backdrop"></div>
    <div class="modal-content" style="width:90%;max-width:420px;margin:80px auto;padding:20px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);z-index:9999;position:relative;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;border-bottom:1px solid #eee;padding-bottom:12px;">
            <h3 id="modal-cambio-segmento-titulo" style="margin:0;">Cambio de Segmento</h3>
            <button type="button" class="js-cerrar-modal-segmento" style="background:none;border:none;font-size:24px;cursor:pointer;color:#666;">×</button>
        </div>
        <div style="margin-bottom:16px;">
            <p><strong>Persona:</strong> <span id="modal-persona-nombre" style="color:#0a6e6a;">-</span></p>
            <div style="margin-top:12px;">
                <label id="modal-segmento-label" for="modal-segmento-nuevo" style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Nuevo Segmento:</label>
                <select id="modal-segmento-nuevo" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;" <?= $puedeEditarRegistroFormacion ? '' : 'disabled' ?>>
                    <option value="">-- Sin cambio (por edad/género) --</option>
                    <option value="jovenes">Jóvenes</option>
                    <option value="hombres_adultos">Hombres</option>
                    <option value="mujeres_adultas">Mujeres</option>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:12px;border-top:1px solid #eee;">
            <button type="button" class="btn btn-secondary js-cerrar-modal-segmento">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btn-guardar-cambio-segmento" <?= $puedeEditarRegistroFormacion ? '' : 'disabled' ?>>Guardar</button>
        </div>
    </div>
</div>

<!-- Modal de Cambio de Programa -->
<div id="modal-cambio-programa" class="segmento-modal" hidden>
    <div class="modal-backdrop"></div>
    <div class="modal-content" style="width:90%;max-width:420px;margin:80px auto;padding:20px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);z-index:9999;position:relative;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;border-bottom:1px solid #eee;padding-bottom:12px;">
            <h3 style="margin:0;">Cambio de Programa</h3>
            <button type="button" class="js-cerrar-modal-programa" style="background:none;border:none;font-size:24px;cursor:pointer;color:#666;">×</button>
        </div>
        <div style="margin-bottom:16px;">
            <p><strong>Persona:</strong> <span id="modal-programa-persona-nombre" style="color:#0a6e6a;">-</span></p>
            <div style="margin-top:12px;">
                <label for="modal-programa-nuevo" style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Nuevo Programa:</label>
                <select id="modal-programa-nuevo" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;" <?= $puedeEditarRegistroFormacion ? '' : 'disabled' ?>></select>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:12px;border-top:1px solid #eee;">
            <button type="button" class="btn btn-secondary js-cerrar-modal-programa">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btn-guardar-cambio-programa" <?= $puedeEditarRegistroFormacion ? '' : 'disabled' ?>>Guardar</button>
        </div>
    </div>
</div>

<script>
(function () {
    const endpointCambioSegmento = <?= json_encode(PUBLIC_URL . '?url=home/cambiar-segmento-inscripcion') ?>;
    const detalleLideresMinisterioUv = <?= $detalleLideresMinisterioUvJson ?>;
    const filtroToggleBtn = document.querySelector('.js-toggle-filtros-btn');
    const filtroPanel = document.getElementById('panel-filtros-formacion');
    const modalMinisterioUv = document.getElementById('modal-ministerio-uv');
    const modalMinisterioUvNombre = document.getElementById('modal-ministerio-uv-nombre');
    const modalMinisterioUvBody = document.getElementById('modal-ministerio-uv-body');
    const modalCambioSegmento = document.getElementById('modal-cambio-segmento');
    const modalCambioSegmentoTitulo = document.getElementById('modal-cambio-segmento-titulo');
    const modalSegmentoLabel = document.getElementById('modal-segmento-label');
    const modalPersonaNombre = document.getElementById('modal-persona-nombre');
    const modalSegmentoNuevo = document.getElementById('modal-segmento-nuevo');
    const btnGuardarCambioSegmento = document.getElementById('btn-guardar-cambio-segmento');
    const modalCambioPrograma = document.getElementById('modal-cambio-programa');
    const modalProgramaPersonaNombre = document.getElementById('modal-programa-persona-nombre');
    const modalProgramaNuevo = document.getElementById('modal-programa-nuevo');
    const btnGuardarCambioPrograma = document.getElementById('btn-guardar-cambio-programa');
    let idInscripcionModal = 0;
    let idInscripcionProgramaModal = 0;

    const escaparHtml = (valor) => String(valor || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const esProgramaCapDestino = (programa) => {
        const p = String(programa || '').trim();
        return ['capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'].includes(p);
    };

    const normalizarSegmentoPorPrograma = (segmentoRaw, programaActual) => {
        const segmento = String(segmentoRaw || '').trim();
        const programa = String(programaActual || '').trim();

        if (esProgramaCapDestino(programa)) {
            if (['nivel_1', 'nivel_2', 'nivel_3'].includes(segmento)) {
                return segmento;
            }
            if (programa === 'capacitacion_destino_nivel_2') {
                return 'nivel_2';
            }
            if (programa === 'capacitacion_destino_nivel_3') {
                return 'nivel_3';
            }
            return 'nivel_1';
        }

        return segmento === 'teens' ? 'jovenes' : segmento;
    };

    const renderOpcionesCambioSegmento = (programaActual) => {
        if (!modalSegmentoNuevo) {
            return;
        }

        if (esProgramaCapDestino(programaActual)) {
            if (modalCambioSegmentoTitulo) {
                modalCambioSegmentoTitulo.textContent = 'Cambio de Nivel';
            }
            if (modalSegmentoLabel) {
                modalSegmentoLabel.textContent = 'Nuevo Nivel:';
            }

            modalSegmentoNuevo.innerHTML = '';
            [
                { value: '', label: '-- Sin cambio (según programa) --' },
                { value: 'nivel_1', label: 'Nivel 1' },
                { value: 'nivel_2', label: 'Nivel 2' },
                { value: 'nivel_3', label: 'Nivel 3' }
            ].forEach((op) => {
                const option = document.createElement('option');
                option.value = op.value;
                option.textContent = op.label;
                modalSegmentoNuevo.appendChild(option);
            });
            return;
        }

        if (modalCambioSegmentoTitulo) {
            modalCambioSegmentoTitulo.textContent = 'Cambio de Segmento';
        }
        if (modalSegmentoLabel) {
            modalSegmentoLabel.textContent = 'Nuevo Segmento:';
        }

        modalSegmentoNuevo.innerHTML = '';
        [
            { value: '', label: '-- Sin cambio (por edad/género) --' },
            { value: 'jovenes', label: 'Jóvenes' },
            { value: 'hombres_adultos', label: 'Hombres' },
            { value: 'mujeres_adultas', label: 'Mujeres' }
        ].forEach((op) => {
            const option = document.createElement('option');
            option.value = op.value;
            option.textContent = op.label;
            modalSegmentoNuevo.appendChild(option);
        });
    };

    const cerrarModalCambioSegmento = () => {
        if (modalCambioSegmento) {
            modalCambioSegmento.setAttribute('hidden', 'hidden');
        }
    };

    if (modalCambioSegmento) {
        modalCambioSegmento.querySelectorAll('.js-cerrar-modal-segmento, .modal-backdrop').forEach((el) => {
            el.addEventListener('click', cerrarModalCambioSegmento);
        });

        document.querySelectorAll('.js-cambio-segmento').forEach((btn) => {
            btn.addEventListener('click', () => {
                idInscripcionModal = parseInt(btn.dataset.idInscripcion || '0', 10);
                const nombre = String(btn.dataset.nombre || '');
                const segmentoActualRaw = String(btn.dataset.segmentoActual || '');
                const programaActual = String(btn.dataset.programaActual || '');

                renderOpcionesCambioSegmento(programaActual);

                const segmentoActual = normalizarSegmentoPorPrograma(segmentoActualRaw, programaActual);

                if (modalPersonaNombre) {
                    modalPersonaNombre.textContent = nombre;
                }
                if (modalSegmentoNuevo) {
                    modalSegmentoNuevo.value = segmentoActual;
                }

                modalCambioSegmento.removeAttribute('hidden');
            });
        });

        if (btnGuardarCambioSegmento) {
            btnGuardarCambioSegmento.addEventListener('click', async () => {
                if (idInscripcionModal <= 0) {
                    alert('Error: ID de inscripción inválido');
                    return;
                }

                const formData = new FormData();
                formData.append('id_inscripcion', String(idInscripcionModal));
                formData.append('segmento_nuevo', modalSegmentoNuevo ? String(modalSegmentoNuevo.value || '') : '');

                try {
                    const response = await fetch(endpointCambioSegmento, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const raw = await response.text();
                    let data = null;
                    try {
                        data = JSON.parse(raw);
                    } catch (e) {
                        throw new Error('El servidor devolvió una respuesta inválida. Recarga la página e intenta de nuevo.');
                    }
                    if (!response.ok || !data.ok) {
                        throw new Error((data && data.error) || 'No se pudo guardar');
                    }

                    cerrarModalCambioSegmento();
                    alert('Cambio guardado correctamente. Recargando página...');
                    window.location.reload();
                } catch (error) {
                    alert(error.message || 'Error al guardar el cambio');
                }
            });
        }
    }

    const opcionesDestinoPorPrograma = (programaActual) => {
        const p = String(programaActual || '').trim();
        if (['universidad_vida', 'encuentro', 'bautismo'].includes(p)) {
            return [
                { value: 'capacitacion_destino_nivel_1', label: 'Capacitación Destino - Nivel 1' },
                { value: 'capacitacion_destino_nivel_2', label: 'Capacitación Destino - Nivel 2' },
                { value: 'capacitacion_destino_nivel_3', label: 'Capacitación Destino - Nivel 3' }
            ];
        }

        if (['capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'].includes(p)) {
            return [];
        }

        return [];
    };

    const cerrarModalCambioPrograma = () => {
        if (modalCambioPrograma) {
            modalCambioPrograma.setAttribute('hidden', 'hidden');
        }
    };

    if (modalCambioPrograma) {
        modalCambioPrograma.querySelectorAll('.js-cerrar-modal-programa, .modal-backdrop').forEach((el) => {
            el.addEventListener('click', cerrarModalCambioPrograma);
        });

        document.querySelectorAll('.js-cambio-programa').forEach((btn) => {
            btn.addEventListener('click', () => {
                idInscripcionProgramaModal = parseInt(btn.dataset.idInscripcion || '0', 10);
                const nombre = String(btn.dataset.nombre || '');
                const programaActual = String(btn.dataset.programaActual || '');
                const opciones = opcionesDestinoPorPrograma(programaActual);

                if (modalProgramaPersonaNombre) {
                    modalProgramaPersonaNombre.textContent = nombre;
                }

                if (modalProgramaNuevo) {
                    modalProgramaNuevo.innerHTML = '';
                    if (opciones.length === 0) {
                        const opt = document.createElement('option');
                        opt.value = '';
                        opt.textContent = 'Sin destinos disponibles';
                        modalProgramaNuevo.appendChild(opt);
                    } else {
                        opciones.forEach((item) => {
                            const opt = document.createElement('option');
                            opt.value = item.value;
                            opt.textContent = item.label;
                            modalProgramaNuevo.appendChild(opt);
                        });
                    }
                }

                modalCambioPrograma.removeAttribute('hidden');
            });
        });

        if (btnGuardarCambioPrograma) {
            btnGuardarCambioPrograma.addEventListener('click', async () => {
                if (idInscripcionProgramaModal <= 0) {
                    alert('Error: ID de inscripción inválido');
                    return;
                }

                const programaDestino = modalProgramaNuevo ? String(modalProgramaNuevo.value || '') : '';
                if (programaDestino === '') {
                    alert('Selecciona un programa de destino');
                    return;
                }

                const formData = new FormData();
                formData.append('accion', 'mover_programa');
                formData.append('id_inscripcion', String(idInscripcionProgramaModal));
                formData.append('programa_destino', programaDestino);
                formData.append('return_url', <?= json_encode($returnUrlFormacion) ?>);

                try {
                    const response = await fetch(endpointCambioSegmento, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const raw = await response.text();
                    let data = null;
                    try {
                        data = JSON.parse(raw);
                    } catch (e) {
                        throw new Error('No se pudo completar el cambio. Recarga la página e intenta de nuevo.');
                    }

                    if (!response.ok || !data.ok) {
                        throw new Error((data && data.error) || 'No se pudo mover de programa');
                    }

                    cerrarModalCambioPrograma();
                    alert('Programa actualizado correctamente. Recargando página...');
                    window.location.reload();
                } catch (error) {
                    alert(error.message || 'Error al guardar el cambio de programa');
                }
            });
        }
    }

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

    const cerrarModalMinisterioUv = () => {
        if (modalMinisterioUv) {
            modalMinisterioUv.setAttribute('hidden', 'hidden');
        }
    };

    if (modalMinisterioUv) {
        modalMinisterioUv.querySelectorAll('.js-cerrar-modal-ministerio-uv, .modal-backdrop').forEach((el) => {
            el.addEventListener('click', cerrarModalMinisterioUv);
        });

        document.querySelectorAll('.js-open-ministerio-uv').forEach((btn) => {
            btn.addEventListener('click', () => {
                const ministerio = String(btn.dataset.ministerio || 'Sin ministerio');
                const rows = Array.isArray(detalleLideresMinisterioUv[ministerio]) ? detalleLideresMinisterioUv[ministerio] : [];

                if (modalMinisterioUvNombre) {
                    modalMinisterioUvNombre.textContent = ministerio;
                }

                if (modalMinisterioUvBody) {
                    modalMinisterioUvBody.innerHTML = '';

                    if (rows.length === 0) {
                        const tr = document.createElement('tr');
                        tr.innerHTML = '<td colspan="6" class="text-center">No hay líderes registrados para este ministerio.</td>';
                        modalMinisterioUvBody.appendChild(tr);
                    } else {
                        let totalH = 0;
                        let totalM = 0;
                        let totalJ = 0;
                        let totalT = 0;
                        let totalAsis = 0;

                        rows.forEach((row) => {
                            const h = parseInt(row.hombres || 0, 10) || 0;
                            const m = parseInt(row.mujeres || 0, 10) || 0;
                            const j = parseInt(row.jovenes || 0, 10) || 0;
                            const t = parseInt(row.total || 0, 10) || 0;
                            const a = parseInt(row.asistencias_reales || 0, 10) || 0;
                            totalH += h;
                            totalM += m;
                            totalJ += j;
                            totalT += t;
                            totalAsis += a;

                            const tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td>' + escaparHtml(String(row.lider || 'Sin lider')) + '</td>' +
                                '<td style="color:#1e4a89;"><strong>' + String(h) + '</strong></td>' +
                                '<td style="color:#8b1c62;"><strong>' + String(m) + '</strong></td>' +
                                '<td style="color:#0f766e;"><strong>' + String(j) + '</strong></td>' +
                                '<td style="color:#166534;"><strong>' + String(t) + '</strong></td>' +
                                '<td style="color:#7c3aed;"><strong>' + String(a) + '</strong></td>';
                            modalMinisterioUvBody.appendChild(tr);
                        });

                        const trTotal = document.createElement('tr');
                        trTotal.className = 'reporte-metas-total-row';
                        trTotal.innerHTML =
                            '<td><strong>TOTAL</strong></td>' +
                            '<td style="color:#1e4a89;"><strong>' + String(totalH) + '</strong></td>' +
                            '<td style="color:#8b1c62;"><strong>' + String(totalM) + '</strong></td>' +
                            '<td style="color:#0f766e;"><strong>' + String(totalJ) + '</strong></td>' +
                            '<td style="color:#166534;"><strong>' + String(totalT) + '</strong></td>' +
                            '<td style="color:#7c3aed;"><strong>' + String(totalAsis) + '</strong></td>';
                        modalMinisterioUvBody.appendChild(trTotal);
                    }
                }

                modalMinisterioUv.removeAttribute('hidden');
            });
        });
    }

    const isMobileRegistroView = () => window.matchMedia('(max-width: 768px)').matches;

    const resetRegistroSelection = () => {
        document.querySelectorAll('.gender-full-view').forEach((view) => {
            view.hidden = true;
        });

        document.querySelectorAll('.registro-resumen-row').forEach((row) => {
            row.classList.remove('is-active');
        });

        document.querySelectorAll('.js-registro-selector').forEach((selectorBtn) => {
            selectorBtn.classList.remove('btn-primary');
            selectorBtn.classList.add('btn-secondary');
            selectorBtn.setAttribute('aria-expanded', 'false');
        });
    };

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

            const wasOpen = !targetView.hidden;
            const isMobile = isMobileRegistroView();

            resetRegistroSelection();

            if (isMobile && wasOpen) {
                return;
            }

            targetView.hidden = false;

            const resumenRow = document.querySelector('.registro-resumen-row[data-view-target="' + targetId + '"]');
            if (resumenRow) {
                resumenRow.classList.add('is-active');
            }

            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-primary');
            btn.setAttribute('aria-expanded', 'true');

            targetView.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

.segmento-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
}

.segmento-modal[hidden] {
    display: none !important;
}

.segmento-modal:not([hidden]) {
    display: block;
}

.segmento-modal .modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.35);
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

.uv-table-ordenada,
.insc-table-ordenada {
    table-layout: auto;
    width: max-content;
    min-width: 100%;
}

.formacion-resumen-table-wrap {
    max-height: 420px;
    overflow-y: auto;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.formacion-resumen-table {
    width: 100%;
    min-width: 100%;
    table-layout: auto;
}

.formacion-resumen-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #eef4ff;
}

.formacion-detalle-ministerio-wrap {
    max-height: 52vh;
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
}

.formacion-detalle-ministerio-table {
    width: 100%;
    min-width: 0;
    table-layout: auto;
}

.formacion-detalle-ministerio-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #eef4ff;
}

.formacion-detalle-ministerio-table th:first-child,
.formacion-detalle-ministerio-table td:first-child {
    white-space: normal;
    overflow-wrap: anywhere;
}

.uv-table-ordenada th,
.uv-table-ordenada td,
.insc-table-ordenada th,
.insc-table-ordenada td {
    vertical-align: middle;
    padding: 5px 8px !important;
    line-height: 1.35;
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

.col-acciones-insc {
    width: 1%;
    min-width: 132px;
    white-space: nowrap;
    text-align: center;
}

.insc-acciones {
    display: inline-flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.insc-acciones-form {
    display: inline-flex;
    margin: 0;
    align-items: center;
}

.insc-acciones .btn-insc-icon {
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    flex-shrink: 0;
}

.insc-acciones .btn-insc-icon i {
    font-size: 14px;
    line-height: 1;
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
    padding:14px;
    background:#fff;
    border-radius:10px;
    margin-top:8px;
}

.registro-view-head {
    font-weight:700;
    color:#1f3f69;
    margin-bottom:10px;
}

.registro-resumen-row.is-active {
    background:#edf4ff;
}

.gender-full-view .table-container {
    width:100%;
    overflow-x:auto;
}

.gender-full-view .data-table th,
.gender-full-view .data-table td {
    font-size: 13px;
}

@keyframes registroAccordionOpen {
    from {
        opacity: 0;
        transform: translateY(-4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .formacion-resumen-table th,
    .formacion-resumen-table td {
        padding: 10px 8px;
    }

    .insc-acciones {
        flex-wrap: wrap;
        justify-content: center;
        row-gap: 6px;
    }

    .gender-full-view {
        border: 1px solid #d5e2f4;
        border-top: 3px solid #3b6ea8;
        padding: 10px;
    }

    .gender-full-view:not([hidden]) {
        animation: registroAccordionOpen .22s ease-out;
    }

    .registro-view-head {
        font-size: 14px;
        margin-bottom: 8px;
    }

    .gender-full-view .data-table th,
    .gender-full-view .data-table td {
        font-size: 14px;
        white-space: nowrap;
    }
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>
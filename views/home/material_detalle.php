<?php include VIEWS . '/layout/header.php'; ?>

<?php
$modulo = $modulo ?? [];
$temas = $temas ?? [];
$totalArchivos = (int)($total_archivos ?? 0);
$puedeGestionar = !empty($puede_gestionar);
$puedeSubirMaterial = !empty($puede_subir_material);
$mensaje = (string)($mensaje ?? '');
$tipo = (string)($tipo ?? '');
$titulo = (string)($modulo['titulo'] ?? 'Material');
$ruta = (string)($modulo['ruta'] ?? 'home/material');
$color = (string)($modulo['color'] ?? '#2f73b7');
$icono = (string)($modulo['icono'] ?? 'bi bi-journal-bookmark-fill');
$clave = (string)($modulo['clave'] ?? '');
$tieneSubmodulos = !empty($tiene_submodulos);
$esCapacitacionDestino = $clave === 'capacitacion_destino';
$esUniversidadVida = $clave === 'universidad_vida';
$usaTarjetasTipoMaterial = $esCapacitacionDestino || $esUniversidadVida;

if ($esCapacitacionDestino || $esUniversidadVida) {
    require_once APP . '/Helpers/ProgramasNavegacion.php';
    $capSeccionNav = strtolower(trim((string)($_GET['cap_seccion'] ?? '')));
    ProgramasNavegacion::incluirPartial([
        'linea' => $esCapacitacionDestino ? 'capacitacion_destino' : 'universidad_vida',
        'seccion' => ($esCapacitacionDestino && $capSeccionNav === 'inscritos') ? 'asistencias' : 'material',
        'forzar' => true,
    ]);
}
$configCapacitacionDestino = (array)($config_capacitacion_destino ?? []);
$profesoresModulos = (array)($profesores_modulos ?? []);
$restriccionDiscipuloMaterial = (array)($restriccion_discipulo_material ?? []);
$aplicarRestriccionDiscipuloMaterial = !empty($restriccionDiscipuloMaterial['aplicar']) && $esCapacitacionDestino;
$mensajeRestriccionDiscipuloMaterial = trim((string)($restriccionDiscipuloMaterial['mensaje'] ?? ''));
$fechaRestriccionDiscipuloMaterial = trim((string)($restriccionDiscipuloMaterial['fecha'] ?? ''));
$clasesActivasRestriccionDiscipulo = (array)($restriccionDiscipuloMaterial['clases_activas_por_nivel'] ?? []);
$modulosActivosRestriccionDiscipulo = (array)($restriccionDiscipuloMaterial['modulos_activos_por_nivel'] ?? []);
$esDiscipuloCapDestino = !empty($es_discipulo_cap_destino) && $esCapacitacionDestino;
$accesosDiscipuloCapDestino = (array)($accesos_discipulo_cap_destino ?? []);
$inscritosCapNivel = (array)($inscritos_cap_nivel ?? []);
$asistenciasPorPersona = (array)($asistencias_por_persona ?? []);
$totalesAsistenciaPorClase = (array)($totales_asistencia_por_clase ?? []);
$totalClasesCap = (int)($total_clases_cap ?? 10);
if ($totalClasesCap < 1) {
    $totalClasesCap = 10;
}
if (count($totalesAsistenciaPorClase) < $totalClasesCap) {
    $totalesAsistenciaPorClase = array_replace(array_fill(1, $totalClasesCap, 0), $totalesAsistenciaPorClase);
}
$tareasCapNivel = (array)($tareas_cap_nivel ?? []);
$entregasTareasCap = (array)($entregas_tareas_cap ?? []);
$planillaEstudiantesCap = (array)($planilla_estudiantes_cap ?? []);
$hubEntregasPendientesCap = (int)($hub_entregas_pendientes_cap ?? 0);
$tareasDiscipuloCap = (array)($tareas_discipulo_cap ?? []);
$capModuloVistaActual = (int)($cap_modulo_vista ?? ($_GET['cap_modulo'] ?? 0));
$idPersonaActual = (int)($id_persona_actual ?? 0);
$puedeSubirTareas = !empty($puede_subir_tareas);
$rutaDetalleVistas = PUBLIC_URL . '?url=home/material/detalle-vistas&modulo=' . rawurlencode($clave);
$capNivelVista = 0;
$modoSeleccionNivelCap = false;
$vistaCapNivelIndependiente = false;
$esVistaMaestro = !empty($es_vista_maestro) && $esCapacitacionDestino;
$usaFlujoCapHub = !empty($usa_flujo_cap_hub);
$permisosHubCap = (array)($cap_hub_permisos ?? []);
$puedeHubMaterial = !empty($permisosHubCap['ver_material']);
$puedeHubEvaluaciones = !empty($permisosHubCap['ver_evaluaciones']);
$puedeHubInscritos = !empty($permisosHubCap['ver_inscritos']);
$puedeHubTareasGestion = !empty($permisosHubCap['gestionar_tareas']);
$puedeHubTareasEntregar = !empty($permisosHubCap['entregar_tareas']);
$capTareaAcceptArchivos = 'image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.7z,.txt,.mp3,.mp4,.mov,.avi,.mkv,.webm,.wav,.m4a,.ogg,.heic,.heif,.jpeg,.jpg,.png,.gif,.webp';
$rolHubCapEtiqueta = trim((string)($rol_hub_cap_etiqueta ?? ''));
$hubActividadPorModulo = (array)($hub_actividad_por_modulo ?? []);
$hubEvaluacionesActivas = (int)($hub_evaluaciones_activas ?? 0);
$hubTareasActivas = (int)($hub_tareas_activas ?? 0);

if ($esCapacitacionDestino && $usaFlujoCapHub) {
    $nivelSolicitado = (int)($_GET['cap_nivel'] ?? 0);
    if ($nivelSolicitado > 0 && isset($configCapacitacionDestino[$nivelSolicitado])) {
        $capNivelVista = $nivelSolicitado;
    }
    $modoSeleccionNivelCap = $capNivelVista <= 0;
    $vistaCapNivelIndependiente = $capNivelVista > 0;
}

$capSeccionVista = '';
$modoSeleccionModuloCap = false;
$modoHubModuloCapMaestro = false;
$mostrarContenidoDetalleCapMaestro = false;
$mostrarToolbarCap = false;
$mostrarAcademicoCap = false;
$mostrarMaterialCap = true;
$urlClaseMaestroModulo = '';
$resumenModulosCap = [];

if ($esCapacitacionDestino && $usaFlujoCapHub) {
    $capSeccionRaw = strtolower(trim((string)($_GET['cap_seccion'] ?? $_GET['cap_academico'] ?? '')));
    if (in_array($capSeccionRaw, ['material', 'inscritos', 'tareas', 'calificaciones'], true)) {
        $capSeccionVista = $capSeccionRaw;
    }
    if ($capSeccionVista === 'inscritos' && !$puedeHubInscritos) {
        $capSeccionVista = '';
    }
    if ($capSeccionVista === 'calificaciones' && !$puedeHubTareasGestion) {
        $capSeccionVista = '';
    }
    if ($capSeccionVista === 'tareas' && !$puedeHubTareasGestion && !$puedeHubTareasEntregar) {
        $capSeccionVista = '';
    }
    if ($capSeccionVista === 'material' && !$puedeHubMaterial) {
        $capSeccionVista = '';
    }
}

if ($esCapacitacionDestino && $capNivelVista > 0 && isset($configCapacitacionDestino[$capNivelVista])) {
    foreach (array_map('intval', (array)$configCapacitacionDestino[$capNivelVista]) as $numModuloCap) {
        if ($numModuloCap <= 0) {
            continue;
        }
        $keyProfCap = $capNivelVista . '_' . $numModuloCap;
        $profConfigCap = $profesoresModulos[$keyProfCap] ?? [];
        $zoomUrlCap = '';
        $nombreProfCap = '';
        if (is_array($profConfigCap)) {
            $zoomUrlCap = trim((string)($profConfigCap['conexion_zoom_url'] ?? ''));
            $nombreProfCap = trim((string)($profConfigCap['profesor_nombre'] ?? ''));
        } else {
            $nombreProfCap = trim((string)$profConfigCap);
        }
        $totalTemasModulo = 0;
        foreach ($temas as $temaModuloResumen) {
            if ((int)($temaModuloResumen['nivel'] ?? 0) !== $capNivelVista) {
                continue;
            }
            if ((int)($temaModuloResumen['modulo_numero'] ?? 0) !== $numModuloCap) {
                continue;
            }
            $totalTemasModulo++;
        }
        $resumenModulosCap[] = [
            'numero' => $numModuloCap,
            'total_temas' => $totalTemasModulo,
            'zoom_url' => $zoomUrlCap,
            'profesor' => $nombreProfCap,
        ];
    }
    usort($resumenModulosCap, static function (array $a, array $b): int {
        return (int)$a['numero'] <=> (int)$b['numero'];
    });

    if ($esDiscipuloCapDestino && $usaFlujoCapHub && !empty($accesosDiscipuloCapDestino)) {
        $modulosDiscipuloNivel = [];
        foreach ($accesosDiscipuloCapDestino as $accesoDiscTmp) {
            if ((int)($accesoDiscTmp['nivel'] ?? 0) !== $capNivelVista) {
                continue;
            }
            $moduloDiscTmp = (int)($accesoDiscTmp['modulo'] ?? 0);
            if ($moduloDiscTmp > 0) {
                $modulosDiscipuloNivel[$moduloDiscTmp] = true;
            }
        }
        $resumenModulosCap = array_values(array_filter(
            $resumenModulosCap,
            static function (array $item) use ($modulosDiscipuloNivel): bool {
                return isset($modulosDiscipuloNivel[(int)($item['numero'] ?? 0)]);
            }
        ));
    }
}

if ($usaFlujoCapHub && $vistaCapNivelIndependiente) {
    $modulosValidosCap = array_map(static function (array $item): int {
        return (int)$item['numero'];
    }, $resumenModulosCap);
    if ($capModuloVistaActual > 0 && !in_array($capModuloVistaActual, $modulosValidosCap, true)) {
        $capModuloVistaActual = 0;
    }
    if ($capModuloVistaActual <= 0) {
        $modoSeleccionModuloCap = true;
    } elseif ($capSeccionVista === '') {
        $modoHubModuloCapMaestro = true;
    } else {
        $mostrarContenidoDetalleCapMaestro = true;
    }
    if ($modoHubModuloCapMaestro) {
        $keyProfHub = $capNivelVista . '_' . $capModuloVistaActual;
        $profHub = $profesoresModulos[$keyProfHub] ?? [];
        if (is_array($profHub)) {
            $urlClaseMaestroModulo = trim((string)($profHub['conexion_zoom_url'] ?? ''));
        }
    }
}

$mostrarToolbarCap = !$modoSeleccionNivelCap;
$mostrarAcademicoCap = $capNivelVista > 0;
$mostrarMaterialCap = true;
if ($usaFlujoCapHub) {
    $mostrarToolbarCap = $mostrarContenidoDetalleCapMaestro && $capSeccionVista === 'material' && $puedeHubMaterial;
    $mostrarAcademicoCap = $mostrarContenidoDetalleCapMaestro && (
        ($capSeccionVista === 'inscritos' && $puedeHubInscritos)
        || ($capSeccionVista === 'tareas' && ($puedeHubTareasGestion || $puedeHubTareasEntregar))
        || ($capSeccionVista === 'calificaciones' && $puedeHubTareasGestion)
    );
    $mostrarMaterialCap = $mostrarContenidoDetalleCapMaestro && $capSeccionVista === 'material' && $puedeHubMaterial;
    if ($modoSeleccionModuloCap || $modoHubModuloCapMaestro) {
        $mostrarMaterialCap = false;
    }
} elseif ($esCapacitacionDestino && $vistaCapNivelIndependiente) {
    $mostrarToolbarCap = !$modoSeleccionNivelCap;
}

$urlMaterialCapBase = PUBLIC_URL . '?url=' . rawurlencode($ruta);
$urlCapNiveles = $urlMaterialCapBase;
$urlCapModulos = $capNivelVista > 0
    ? $urlMaterialCapBase . '&cap_nivel=' . (int)$capNivelVista
    : $urlMaterialCapBase;
$urlCapHubModulo = ($capNivelVista > 0 && $capModuloVistaActual > 0)
    ? $urlMaterialCapBase . '&cap_nivel=' . (int)$capNivelVista . '&cap_modulo=' . (int)$capModuloVistaActual
    : $urlCapModulos;
$urlEvaluacionesCapModulo = ($capNivelVista > 0 && $capModuloVistaActual > 0)
    ? PUBLIC_URL . '?url=programas/evaluaciones&from_material=1&nivel=' . (int)$capNivelVista . '&modulo=' . (int)$capModuloVistaActual
    : '';
$resumenNivelesCap = [];

if ($esCapacitacionDestino) {
    foreach ($configCapacitacionDestino as $nivelResumen => $modulosResumen) {
        $nivelInt = (int)$nivelResumen;
        $modulosNivel = array_map('intval', (array)$modulosResumen);
        $totalTemasNivel = 0;

        foreach ($temas as $temaResumen) {
            if ((int)($temaResumen['nivel'] ?? 0) !== $nivelInt) {
                continue;
            }
            if (!in_array((int)($temaResumen['modulo_numero'] ?? 0), $modulosNivel, true)) {
                continue;
            }
            $totalTemasNivel++;
        }

        $resumenNivelesCap[] = [
            'nivel' => $nivelInt,
            'total_modulos' => count($modulosNivel),
            'total_temas' => $totalTemasNivel,
        ];
    }

    if ($esDiscipuloCapDestino && $usaFlujoCapHub) {
        $nivelesDiscipuloPermitidos = array_map('intval', (array)($restriccionDiscipuloMaterial['niveles_permitidos'] ?? []));
        if (!empty($nivelesDiscipuloPermitidos)) {
            $resumenNivelesCap = array_values(array_filter(
                $resumenNivelesCap,
                static function (array $item) use ($nivelesDiscipuloPermitidos): bool {
                    return in_array((int)($item['nivel'] ?? 0), $nivelesDiscipuloPermitidos, true);
                }
            ));
        }
    }
}

$temasClase = [];
$temasProfesor = [];
if ($tieneSubmodulos) {
    foreach ($temas as $temaTmp) {
        $categoriaTmp = strtolower(trim((string)($temaTmp['categoria'] ?? 'clase')));
        if ($categoriaTmp === 'profesor') {
            $temasProfesor[] = $temaTmp;
        } else {
            $temasClase[] = $temaTmp;
        }
    }
}
?>

<style>
    .submodulo-wrap {
        border: 1px solid #dbe6f5;
        border-radius: 12px;
        margin-bottom: 14px;
        overflow: hidden;
        background: #fff;
    }

    .submodulo-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-bottom: 1px solid #e6eef9;
    }

    .submodulo-title {
        margin: 0;
        font-size: 16px;
    }

    .submodulo-meta {
        font-size: 12px;
        color: #50647f;
        font-weight: 600;
    }

    .submodulo-clase .submodulo-head {
        background: linear-gradient(180deg, #edf4ff 0%, #f8fbff 100%);
    }

    .submodulo-profesor .submodulo-head {
        background: linear-gradient(180deg, #fff5e8 0%, #fffaf3 100%);
    }

    .submodulo-body {
        padding: 10px;
    }

    .submodulo-tabs {
        display: inline-flex;
        gap: 6px;
        padding: 4px;
        border: 1px solid #d8e2f1;
        border-radius: 999px;
        background: #f7fbff;
        margin-bottom: 12px;
    }

    .submodulo-tab {
        border: 1px solid transparent;
        border-radius: 999px;
        background: transparent;
        color: #31527d;
        font-weight: 700;
        font-size: 13px;
        padding: 7px 12px;
        cursor: pointer;
    }

    .submodulo-tab:hover {
        background: #ebf3ff;
    }

    .submodulo-tab.is-active {
        background: #3c82c8;
        color: #fff;
        border-color: #3c82c8;
        box-shadow: 0 1px 3px rgba(45, 94, 146, 0.22);
    }

    .is-hidden {
        display: none;
    }

    .submodulo-body .table-container {
        overflow-x: auto;
    }

    .submodulo-body .data-table {
        min-width: 1240px;
        table-layout: fixed;
    }

    .submodulo-body .data-table th,
    .submodulo-body .data-table td {
        word-break: normal;
        overflow-wrap: normal;
        white-space: normal;
    }

    .submodulo-body .data-table th.col-titulo,
    .submodulo-body .data-table td.col-titulo {
        width: 240px;
        max-width: 240px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .submodulo-body .data-table th.col-descripcion,
    .submodulo-body .data-table td.col-descripcion {
        width: 520px;
        max-width: 520px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .descripcion-cell {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        min-width: 0;
    }

    .descripcion-preview {
        display: inline-block;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
    }

    .btn-link-compact {
        border: 0;
        background: transparent;
        color: #3c82c8;
        font-weight: 700;
        font-size: 12px;
        cursor: pointer;
        padding: 0;
        text-decoration: underline;
        white-space: nowrap;
    }

    .btn-link-compact:hover {
        color: #2a5f99;
    }

    .submodulo-body .data-table th.col-acciones,
    .submodulo-body .data-table td.col-acciones {
        width: 300px;
    }

    .cap-destino-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
        gap: 12px;
    }

    .cap-destino-grid .submodulo-wrap {
        margin-bottom: 0;
    }

    .cap-nivel-section {
        margin-bottom: 0;
        border: 1px solid #c8d9ef;
        border-radius: 14px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 10px 24px rgba(36, 82, 133, 0.08);
    }

    .cap-niveles-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-top: 10px;
    }

    .cap-nivel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 14px;
        border-radius: 10px 10px 0 0;
        background: linear-gradient(90deg, #2f73b7 0%, #4f8fd0 100%);
        color: #fff;
        margin-bottom: 0;
    }

    .cap-nivel-header small {
        color: #dce9fb;
        font-weight: 600;
    }

    .cap-nivel-label {
        font-size: 15px;
        font-weight: 700;
        margin: 0;
    }

    .cap-nivel-section .cap-destino-grid {
        border-top: none;
        border-radius: 0;
        padding: 10px;
        background: #f8fbff;
        grid-template-columns: 1fr;
    }

    .cap-modulo-profesor-wrap {
        border-left: 1px solid #c8d9ef;
        border-right: 1px solid #c8d9ef;
        background: #f6f9ff;
        padding: 8px 10px;
    }

    .cap-modulo-profesor-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0 2px 0;
        font-size: 12px;
        color: #4a6080;
    }

    .cap-modulo-profesor-nombre {
        font-weight: 600;
        color: #2f73b7;
    }

    .cap-modulo-profesor-form {
        display: none;
        margin-top: 6px;
        padding: 8px;
        background: #f0f5ff;
        border-radius: 8px;
        border: 1px solid #ccdcf5;
    }

    .cap-modulo-profesor-form.is-open {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .cap-destino-main-switch {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }

    .cap-main-tab {
        border: 1px solid #dbe6f5;
        border-radius: 14px;
        padding: 12px 14px;
        background: linear-gradient(180deg, #f7fbff 0%, #eef4ff 100%);
        color: #2f4f78;
        font-weight: 700;
        text-align: left;
        cursor: pointer;
        transition: all .16s ease;
    }

    .cap-main-tab small {
        display: block;
        margin-top: 4px;
        font-weight: 500;
        color: #5f7596;
    }

    .cap-main-tab:hover {
        border-color: #bfd3ee;
        transform: translateY(-1px);
    }

    .cap-main-tab.is-active {
        border-color: #3c82c8;
        background: linear-gradient(180deg, #3c82c8 0%, #2f73b7 100%);
        color: #fff;
        box-shadow: 0 6px 18px rgba(45, 94, 146, 0.22);
    }

    .cap-main-tab.is-active small {
        color: #dbe8ff;
    }

    .cap-destino-grid .submodulo-title {
        font-size: 15px;
    }

    .cap-entry-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
        margin-top: 14px;
        margin-bottom: 16px;
    }

    .cap-entry-card {
        border: 1px solid #d6e3f4;
        border-radius: 16px;
        padding: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f6fbff 100%);
        box-shadow: 0 10px 20px rgba(22, 63, 110, 0.08);
        cursor: pointer;
    }

    .cap-entry-card:hover {
        border-color: #b9d0ec;
        transform: translateY(-1px);
    }

    .cap-entry-card.is-active {
        border-color: #3c82c8;
        background: linear-gradient(180deg, #3c82c8 0%, #2f73b7 100%);
        box-shadow: 0 8px 20px rgba(45, 94, 146, 0.24);
    }

    .cap-entry-card.is-active h4,
    .cap-entry-card.is-active p {
        color: #ffffff;
    }

    .cap-entry-card h4 {
        margin: 0 0 6px 0;
        color: #1f4d84;
    }

    .cap-entry-card p {
        margin: 0;
        color: #617694;
        font-size: 13px;
    }

    .cap-level-selector {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 12px;
        margin-top: 14px;
        margin-bottom: 10px;
    }

    .cap-level-card {
        display: block;
        border: 1px solid #d8e2f1;
        border-radius: 14px;
        background: linear-gradient(160deg, #ffffff 0%, #f7fbff 100%);
        padding: 14px;
        color: #1e2f48;
        text-decoration: none;
        box-shadow: 0 6px 18px rgba(30, 56, 98, 0.08);
        cursor: pointer;
        transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
    }

    .cap-level-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(30, 56, 98, 0.16);
        border-color: #b9d0ec;
    }

    .cap-level-card.is-active {
        border-color: #1f5ea8;
        background: linear-gradient(180deg, #1f5ea8 0%, #1a518f 100%);
        color: #fff;
        box-shadow: 0 10px 24px rgba(23, 62, 110, 0.28);
    }

    .cap-level-card-title {
        margin: 0 0 8px 0;
        font-size: 17px;
        font-weight: 700;
    }

    .cap-level-card-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #4f647f;
    }

    .cap-level-card.is-active .cap-level-card-meta {
        color: #d8e8ff;
    }

    .cap-level-card-enter-btn {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 8px;
        background: #1f5ea8;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.2;
        white-space: nowrap;
    }

    .cap-level-card:hover .cap-level-card-enter-btn {
        background: #174a85;
    }

    .cap-level-card.is-active .cap-level-card-enter-btn {
        background: #fff;
        color: #1f5ea8;
    }

    .cap-level-card.is-active:hover .cap-level-card-enter-btn {
        background: #eef4ff;
    }

    .maestro-welcome-banner {
        border: 1px solid #d8e4f8;
        border-radius: 12px;
        background: linear-gradient(135deg, #f4f8ff 0%, #ffffff 55%);
        padding: 16px 18px;
        margin-bottom: 14px;
    }

    .maestro-welcome-banner h3 {
        margin: 0 0 6px 0;
        font-size: 1.15rem;
        color: #1e4a89;
    }

    .maestro-welcome-banner p {
        margin: 0;
        color: #50647f;
        font-size: 14px;
        line-height: 1.45;
    }

    body.maestro-material-focus .cap-level-selector {
        gap: 16px;
    }

    body.maestro-material-focus .cap-level-card {
        min-height: 118px;
        padding: 18px 20px;
    }

    body.maestro-material-focus .cap-level-card-title {
        font-size: 1.25rem;
    }

    body.maestro-material-focus .cap-module-selector-grid {
        gap: 16px;
    }

    body.maestro-material-focus .cap-module-selector-grid .cap-level-card {
        min-height: 118px;
        padding: 18px 20px;
    }

    .cap-module-pick-screen {
        margin-top: 8px;
        margin-bottom: 24px;
    }

    body.maestro-material-focus .cap-module-pick-screen {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }

    body.maestro-material-focus .cap-module-pick-card {
        min-height: 130px;
    }

    .cap-module-pick-banner {
        margin-bottom: 16px;
    }

    body.cap-modulo-pick-mode #cap-material-panel,
    body.cap-modulo-pick-mode .cap-toolbar,
    body.cap-modulo-pick-mode #cap-academico-panel {
        display: none !important;
    }

    .cap-maestro-nav {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        margin-bottom: 12px;
    }

    .cap-maestro-hub-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
        margin-top: 12px;
    }

    .cap-maestro-hub-card {
        display: block;
        border: 1px solid #d8e2f1;
        border-radius: 14px;
        background: #fff;
        padding: 16px;
        color: #1e2f48;
        text-decoration: none;
        box-shadow: 0 4px 14px rgba(30, 56, 98, 0.07);
        transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
    }

    .cap-maestro-hub-card:hover {
        transform: translateY(-2px);
        border-color: #b9d0ec;
        box-shadow: 0 8px 20px rgba(30, 56, 98, 0.12);
    }

    .cap-maestro-hub-card h4 {
        margin: 0 0 6px 0;
        font-size: 16px;
        color: #1f4f93;
    }

    .cap-maestro-hub-card p {
        margin: 0;
        font-size: 13px;
        color: #5a6f8d;
        line-height: 1.4;
    }

    .cap-maestro-hub-card--active {
        background: linear-gradient(155deg, #e0f2fe 0%, #bae6fd 55%, #7dd3fc 100%);
        border-color: #38bdf8;
        box-shadow: 0 8px 22px rgba(56, 189, 248, 0.28);
    }

    .cap-maestro-hub-card--active h4 {
        color: #0369a1;
    }

    .cap-maestro-hub-card--active p {
        color: #0c4a6e;
    }

    .cap-maestro-hub-card--active:hover {
        border-color: #0ea5e9;
        box-shadow: 0 10px 26px rgba(14, 165, 233, 0.32);
    }

    .cap-maestro-hub-card--empty {
        background: #f1f5f9;
        border-color: #e2e8f0;
        box-shadow: none;
    }

    .cap-maestro-hub-card--empty h4 {
        color: #94a3b8;
    }

    .cap-maestro-hub-card--empty p {
        color: #94a3b8;
    }

    .cap-maestro-hub-card--empty:hover {
        transform: none;
        border-color: #e2e8f0;
        box-shadow: none;
    }

    .cap-level-card.cap-module-pick-card--active {
        background: linear-gradient(155deg, #e0f2fe 0%, #bae6fd 55%, #7dd3fc 100%);
        border-color: #38bdf8;
        box-shadow: 0 8px 22px rgba(56, 189, 248, 0.28);
    }

    .cap-level-card.cap-module-pick-card--active .cap-level-card-title {
        color: #0369a1;
    }

    .cap-level-card.cap-module-pick-card--active .cap-level-card-meta {
        color: #0c4a6e;
    }

    .cap-level-card.cap-module-pick-card--empty {
        background: #f1f5f9;
        border-color: #e2e8f0;
        opacity: 0.95;
    }

    .cap-level-card.cap-module-pick-card--empty .cap-level-card-title,
    .cap-level-card.cap-module-pick-card--empty .cap-level-card-meta {
        color: #94a3b8;
    }

    .cap-categoria-switch {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 0;
    }

    .cap-module-selector {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 0;
    }

    .cap-toolbar {
        border: 1px solid #d6e3f4;
        border-radius: 14px;
        background: #f8fbff;
        padding: 10px;
        margin-bottom: 12px;
        display: flex;
        align-items: flex-end;
        gap: 14px;
        flex-wrap: wrap;
    }

    .cap-toolbar--maestro-material {
        align-items: center;
        justify-content: space-between;
    }

    .cap-lessons-meta--inline {
        margin: 0;
        align-self: center;
        font-size: 12px;
        color: #5a6f8d;
        white-space: nowrap;
    }

    .cap-toolbar-group {
        margin-bottom: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 220px;
    }

    .cap-toolbar-label {
        display: block;
        font-size: 12px;
        font-weight: 800;
        color: #5e7290;
        letter-spacing: .4px;
        text-transform: uppercase;
        margin-bottom: 0;
        line-height: 1.1;
    }

    .cap-view-switch {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }

    .cap-view-btn {
        border: 1px solid #c7d8ee;
        border-radius: 999px;
        background: #ffffff;
        color: #2e5684;
        font-weight: 700;
        font-size: 12px;
        padding: 6px 12px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .cap-view-btn.is-active {
        border-color: #3c82c8;
        background: #3c82c8;
        color: #fff;
    }

    .cap-view-btn .meta {
        color: #6a82a2;
        font-weight: 600;
        font-size: 11px;
    }

    .cap-view-btn.is-active .meta {
        color: #dbe9ff;
    }

    .cap-lessons-meta {
        color: #5b7292;
        font-size: 12px;
        font-weight: 600;
    }

    .cap-academico-panel {
        border: 1px solid #d6e3f4;
        border-radius: 14px;
        background: #f8fbff;
        padding: 10px;
        margin-bottom: 12px;
    }

    .cap-academico-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .cap-academico-section.is-hidden {
        display: none;
    }

    .cap-main-section.is-hidden {
        display: none;
    }

    .cap-inscritos-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border: 1px solid #d9e5f5;
        border-radius: 10px;
        overflow: hidden;
    }

    .cap-inscritos-table th,
    .cap-inscritos-table td {
        border-bottom: 1px solid #edf3fb;
        padding: 7px 8px;
        font-size: 12px;
        color: #2f496d;
        text-align: left;
    }

    .cap-inscritos-table th {
        background: #eef5ff;
        color: #355d8b;
        font-weight: 700;
    }

    .cap-inscritos-table tr:last-child td {
        border-bottom: 0;
    }

    .cap-inscritos-table .cap-clase-col {
        width: 52px;
        min-width: 52px;
        max-width: 52px;
        text-align: center;
        box-sizing: border-box;
    }

    .cap-planilla-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }

    .cap-planilla-contador {
        font-size: 13px;
        color: #2b4f79;
    }

    .cap-planilla-pendientes {
        color: #b45309;
        font-weight: 600;
    }

    .cap-planilla-filtros {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        font-size: 12px;
    }

    #cap-planilla-export-excel {
        margin-left: 4px;
    }

    .cap-planilla-check-pendientes {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin: 0;
        color: #4a6283;
        cursor: pointer;
    }

    .cap-planilla-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        min-width: 720px;
    }

    .cap-planilla-table th,
    .cap-planilla-table td {
        border: 1px solid #dbe6f5;
        padding: 6px 8px;
        vertical-align: top;
        background: #fff;
    }

    .cap-planilla-table th {
        background: #f4f8fd;
        color: #2b4f79;
        font-weight: 700;
        white-space: nowrap;
    }

    .cap-planilla-sticky {
        position: sticky;
        z-index: 2;
        background: #fff;
    }

    .cap-planilla-sticky-1 { left: 0; min-width: 140px; }
    .cap-planilla-sticky-2 { left: 140px; min-width: 100px; }

    .cap-planilla-asistencia-col {
        min-width: 72px;
        text-align: center;
        white-space: nowrap;
    }

    .cap-planilla-tarea-col {
        min-width: 130px;
        max-width: 160px;
        white-space: normal;
        font-size: 11px;
    }

    .cap-planilla-celda-tarea.is-pendiente {
        background: #fffbeb;
    }

    .cap-planilla-celda-tarea.is-calificada {
        background: #f0fdf4;
    }

    .cap-planilla-sin-entrega {
        color: #94a3b8;
        font-size: 11px;
    }

    .cap-planilla-calif-cell {
        display: grid;
        gap: 4px;
        min-width: 120px;
    }

    .cap-planilla-archivo-link {
        color: #2563eb;
        font-size: 14px;
    }

    .cap-planilla-nota-input,
    .cap-planilla-retro-input {
        font-size: 11px;
        padding: 2px 6px;
    }

    .cap-planilla-guardar-btn {
        font-size: 11px;
        padding: 2px 8px;
    }

    .cap-planilla-estado-msg {
        font-size: 10px;
        min-height: 14px;
    }

    .cap-planilla-estado-msg.is-ok { color: #15803d; }
    .cap-planilla-estado-msg.is-error { color: #b91c1c; }
    .cap-planilla-estado-msg.is-saving { color: #64748b; }

    .cap-planilla-ayuda {
        margin: 8px 0 0;
        font-size: 11px;
        color: #64748b;
    }

    .cap-tarea-card {
        border: 1px solid #d9e5f5;
        border-radius: 12px;
        background: #fff;
        padding: 10px;
        margin-bottom: 10px;
    }

    .cap-tarea-title {
        margin: 0 0 4px 0;
        color: #1f4f84;
        font-size: 15px;
    }

    .cap-tarea-meta {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        font-size: 12px;
        color: #5b7292;
        margin-bottom: 8px;
    }

    .cap-tarea-upload-form {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(220px, 1fr) auto;
        gap: 8px;
        align-items: end;
        margin-bottom: 10px;
    }

    .cap-tarea-upload-hint {
        display: block;
        margin-top: 4px;
        font-size: 11px;
        color: #637087;
        line-height: 1.35;
    }

    .cap-entregas-usuario-wrap {
        margin-top: 4px;
        padding: 10px;
        border: 1px solid #e5ebf7;
        border-radius: 10px;
        background: #f8fbff;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .cap-entregas-usuario-title {
        font-size: 13px;
        color: #1f4f93;
    }

    .cap-entrega-usuario-card {
        border: 1px solid #dbe3f0;
        border-radius: 8px;
        padding: 8px;
        background: #fff;
    }

    .cap-entrega-usuario-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 8px;
    }

    .cap-entrega-usuario-cell {
        border: 1px solid #e5ebf7;
        border-radius: 6px;
        padding: 6px 8px;
        min-width: 0;
    }

    .cap-entrega-usuario-label {
        display: block;
        font-size: 11px;
        color: #637087;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .cap-entrega-usuario-value {
        font-size: 12px;
        color: #1f4f93;
        word-break: break-word;
    }

    .cap-entrega-usuario-link {
        font-size: 12px;
        font-weight: 700;
        color: #1f4f93;
        text-decoration: underline;
        word-break: break-word;
    }

    .cap-entrega-usuario-calif-ok {
        color: #14532d;
    }

    .cap-entrega-usuario-calif-pend {
        color: #8a6d1d;
    }

    @media (max-width: 900px) {
        .cap-tarea-upload-form {
            grid-template-columns: 1fr;
        }
    }

    .cap-entrega-item {
        border: 1px dashed #c9d9ef;
        border-radius: 10px;
        padding: 8px;
        background: #fdfefe;
        margin-bottom: 8px;
    }

    .cap-entrega-item:last-child {
        margin-bottom: 0;
    }

    .cap-entrega-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 6px;
    }

    .cap-entrega-calificada {
        font-size: 11px;
        font-weight: 700;
        border-radius: 999px;
        padding: 2px 8px;
        color: #fff;
        background: #2f8f59;
    }

    .cap-entrega-pendiente {
        font-size: 11px;
        font-weight: 700;
        border-radius: 999px;
        padding: 2px 8px;
        color: #fff;
        background: #d59a22;
    }

    @media (min-width: 992px) {
        .cap-toolbar {
            flex-wrap: nowrap;
        }

        .cap-toolbar-group {
            min-width: 0;
        }

        .cap-toolbar-group:nth-child(1) {
            flex: 1.2;
        }

        .cap-toolbar-group:nth-child(2) {
            flex: 1.4;
        }

        .cap-toolbar-group:nth-child(3) {
            flex: 1.1;
        }
    }

    .cap-module-btn {
        border: 1px solid #c7d8ee;
        border-radius: 999px;
        background: #ffffff;
        color: #2e5684;
        font-weight: 700;
        font-size: 12px;
        padding: 6px 12px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .cap-module-btn .meta {
        color: #6a82a2;
        font-weight: 600;
        font-size: 11px;
    }

    .cap-module-btn.is-active {
        border-color: #3c82c8;
        background: #3c82c8;
        color: #fff;
    }

    .cap-module-btn.is-active .meta {
        color: #dbe9ff;
    }

    .cap-categoria-btn {
        border: 1px solid #c7d8ee;
        border-radius: 999px;
        background: #f4f8ff;
        color: #2e5684;
        font-weight: 700;
        font-size: 12px;
        padding: 6px 12px;
        cursor: pointer;
    }

    .cap-categoria-btn.is-active {
        border-color: #3c82c8;
        background: #3c82c8;
        color: #fff;
    }

    .cap-panel {
        display: none;
        margin-top: 12px;
    }

    .cap-panel.is-open {
        display: block;
    }

    .folder-tree-explorer {
        margin-bottom: 14px;
        border: 1px solid #d6e3f4;
        border-radius: 14px;
        background: #f8fbff;
        padding: 12px;
    }

    .folder-tree-row {
        margin-bottom: 10px;
    }

    .folder-tree-row:last-child {
        margin-bottom: 0;
    }

    .folder-tree-label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        color: #5e7290;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .folder-tree-items {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .folder-node {
        border: 1px solid #cdddf1;
        border-radius: 10px;
        background: #fff;
        color: #32577f;
        font-size: 13px;
        font-weight: 700;
        padding: 8px 12px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .folder-node:hover {
        border-color: #adc8e6;
        background: #f2f8ff;
    }

    .folder-node.is-active {
        border-color: #3c82c8;
        background: linear-gradient(180deg, #3c82c8 0%, #2f73b7 100%);
        color: #fff;
    }

    .folder-node .folder-meta {
        font-size: 11px;
        opacity: 0.82;
        font-weight: 600;
    }

    .cap-destino-grid .submodulo-wrap {
        margin-bottom: 10px;
        border: 1px solid #d4e3f5;
    }

    .cap-destino-grid .submodulo-head {
        cursor: pointer;
    }

    .cap-destino-grid .submodulo-body {
        display: none;
    }

    .uv-material-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .uv-material-grid .submodulo-wrap {
        margin-bottom: 0;
    }

    .uv-material-grid .submodulo-body {
        display: block;
    }

    .uv-material-grid .submodulo-wrap.is-hidden {
        display: none;
    }

    .cap-destino-grid .submodulo-wrap.is-selected {
        border-color: #3c82c8;
        box-shadow: 0 6px 14px rgba(45, 94, 146, 0.16);
    }

    .cap-destino-grid .submodulo-wrap.is-selected .submodulo-head {
        background: linear-gradient(180deg, #eef5ff 0%, #f8fbff 100%);
    }

    .cap-modulo-head-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .cap-modulo-eval-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        font-weight: 700;
        color: #2f73b7;
        text-decoration: none;
        border: 1px solid #bdd3ec;
        border-radius: 999px;
        padding: 4px 10px;
        background: #eef5ff;
    }

    .cap-modulo-eval-link:hover {
        color: #1f4f84;
        border-color: #9fc0e4;
        background: #e4efff;
    }

    .cap-modulo-carpeta {
        font-size: 12px;
        font-weight: 700;
        color: #456389;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .cap-nivel-section .submodulo-body {
        padding: 14px;
    }

    .cap-nivel-section .data-table {
        width: 100%;
        min-width: 0;
        table-layout: fixed;
    }

    .cap-nivel-section .data-table th.col-titulo,
    .cap-nivel-section .data-table td.col-titulo {
        width: 42%;
        max-width: 42%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cap-nivel-section .data-table th.col-descripcion,
    .cap-nivel-section .data-table td.col-descripcion {
        width: 10%;
        max-width: 10%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cap-nivel-section .data-table th.col-acciones,
    .cap-nivel-section .data-table td.col-acciones {
        width: 48%;
        max-width: 48%;
    }

    .tema-acciones {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }

    .tema-acciones-row {
        display: contents;
    }

    .tema-acciones-row.is-danger {
        padding-top: 0;
    }

    .cap-nivel-section .descripcion-cell,
    .cap-nivel-section .descripcion-preview {
        display: inline-block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cap-detail-view {
        margin-top: 14px;
        border: 1px solid #dbe6f5;
        border-radius: 12px;
        background: #fff;
        overflow: hidden;
    }

    .cap-detail-view.is-hidden {
        display: none;
    }

    .cap-detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-bottom: 1px solid #e6eef9;
        background: #f7fbff;
    }

    .cap-detail-header h4 {
        margin: 0;
        color: #356ea8;
        font-size: 15px;
    }

    .cap-detail-header small {
        color: #60758f;
        font-weight: 600;
    }

    .cap-detail-body {
        padding: 10px;
    }

    .material-gallery-modal {
        position: fixed;
        inset: 0;
        z-index: 1200;
        display: none;
        background: rgba(8, 14, 27, 0.92);
        backdrop-filter: blur(4px);
    }

    .material-gallery-modal.is-open {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .material-gallery-shell {
        width: min(1200px, 100%);
        max-height: calc(100vh - 40px);
        background: linear-gradient(180deg, #1b2f4d 0%, #234062 100%);
        border: 1px solid rgba(190, 210, 240, 0.16);
        border-radius: 20px;
        box-shadow: 0 18px 50px rgba(0, 0, 0, 0.35);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .material-gallery-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        border-bottom: 1px solid rgba(190, 210, 240, 0.12);
        color: #f4f8ff;
    }

    .material-gallery-topbar h3 {
        margin: 0;
        font-size: 18px;
    }

    .material-gallery-topbar small {
        display: block;
        color: #bdd0ec;
        margin-top: 4px;
    }

    .material-gallery-close {
        border: 0;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        width: 38px;
        height: 38px;
        font-size: 20px;
        cursor: pointer;
    }

    .material-gallery-stage {
        display: grid;
        grid-template-columns: 68px minmax(0, 1fr) 68px;
        align-items: stretch;
        gap: 8px;
        padding: 14px 16px 10px;
        min-height: 0;
        flex: 1;
    }

    .material-gallery-nav {
        border: 0;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        font-size: 28px;
        cursor: pointer;
        transition: background .15s ease;
    }

    .material-gallery-nav:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.14);
    }

    .material-gallery-nav:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }

    .material-gallery-figure {
        min-height: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .material-gallery-frame {
        flex: 1;
        min-height: 360px;
        max-height: calc(100vh - 240px);
        border-radius: 18px;
        overflow: hidden;
        background: rgba(2, 8, 18, 0.78);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .material-gallery-frame img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #0a1220;
    }

    .material-gallery-caption {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        color: #e8f0fc;
        font-size: 14px;
    }

    .material-gallery-caption strong {
        display: block;
        font-size: 15px;
    }

    .material-gallery-caption a {
        color: #8ec5ff;
        font-weight: 700;
        text-decoration: none;
    }

    .material-gallery-caption a:hover {
        text-decoration: underline;
    }

    .material-gallery-thumbs {
        display: flex;
        gap: 10px;
        padding: 0 16px 16px;
        overflow-x: auto;
    }

    .material-gallery-thumb {
        border: 2px solid transparent;
        border-radius: 12px;
        padding: 0;
        background: transparent;
        cursor: pointer;
        overflow: hidden;
        width: 92px;
        min-width: 92px;
        height: 66px;
        opacity: 0.7;
    }

    .material-gallery-thumb.is-active {
        border-color: #7fc2ff;
        opacity: 1;
        box-shadow: 0 0 0 3px rgba(127, 194, 255, 0.16);
    }

    .material-gallery-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .material-item-preview-btn {
        border: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
        background: transparent;
        position: relative;
        display: block;
    }

    .material-item-preview-btn img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .material-item-preview-btn::after {
        content: '‹  ›';
        position: absolute;
        right: 8px;
        bottom: 8px;
        padding: 2px 7px;
        border-radius: 999px;
        background: rgba(0, 0, 0, 0.55);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
    }

    @media (max-width: 768px) {
        .cap-niveles-grid {
            grid-template-columns: 1fr;
        }

        .material-gallery-modal.is-open {
            padding: 10px;
        }

        .material-gallery-shell {
            max-height: calc(100vh - 20px);
        }

        .material-gallery-stage {
            grid-template-columns: 48px minmax(0, 1fr) 48px;
            padding: 10px;
        }

        .material-gallery-frame {
            min-height: 240px;
            max-height: calc(100vh - 260px);
        }

        .material-gallery-caption {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<?php if ($usaFlujoCapHub): ?>
<script>document.body.classList.add('maestro-material-focus');</script>
<?php endif; ?>
<?php if ($usaFlujoCapHub && $modoSeleccionModuloCap): ?>
<script>document.body.classList.add('cap-modulo-pick-mode');</script>
<?php endif; ?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;" data-tour="page-header-cap">
    <div>
        <h2 style="margin:0;"><?php
            if ($usaFlujoCapHub && $mostrarContenidoDetalleCapMaestro && $capModuloVistaActual > 0 && $capSeccionVista === 'tareas') {
                echo 'Tareas módulo ' . (int)$capModuloVistaActual;
            } elseif ($usaFlujoCapHub && $mostrarContenidoDetalleCapMaestro && $capModuloVistaActual > 0 && $capSeccionVista === 'inscritos') {
                echo 'Inscritos módulo ' . (int)$capModuloVistaActual;
            } elseif ($usaFlujoCapHub && $mostrarContenidoDetalleCapMaestro && $capModuloVistaActual > 0 && $capSeccionVista === 'material') {
                echo 'Material módulo ' . (int)$capModuloVistaActual;
            } elseif ($esDiscipuloCapDestino && $usaFlujoCapHub && $capNivelVista > 0 && ($modoSeleccionModuloCap || $modoHubModuloCapMaestro)) {
                echo 'Nivel ' . (int)$capNivelVista;
            } elseif ($esDiscipuloCapDestino && $usaFlujoCapHub && $modoSeleccionNivelCap) {
                echo 'Capacitación Destino';
            } else {
                echo htmlspecialchars($titulo);
            }
        ?></h2>
        <?php if ($usaFlujoCapHub && $modoSeleccionNivelCap): ?>
            <small style="color:#637087;"><?= $esDiscipuloCapDestino ? 'Elige tu nivel activo para evaluaciones y tareas.' : 'Elige un nivel para continuar.' ?></small>
        <?php elseif ($usaFlujoCapHub && $modoSeleccionModuloCap): ?>
            <small style="color:#637087;"><?= $esDiscipuloCapDestino ? 'Elige el módulo para evaluaciones y tareas.' : 'Nivel ' . (int)$capNivelVista . ' · elige el módulo con el que vas a trabajar hoy.' ?></small>
        <?php elseif ($usaFlujoCapHub && $modoHubModuloCapMaestro): ?>
            <small style="color:#637087;">Nivel <?= (int)$capNivelVista ?> · Módulo <?= (int)$capModuloVistaActual ?></small>
        <?php elseif ($usaFlujoCapHub && $capSeccionVista === 'tareas'): ?>
            <small style="color:#637087;">Nivel <?= (int)$capNivelVista ?> · entrega y consulta de tareas del módulo.</small>
        <?php elseif ($usaFlujoCapHub && $capSeccionVista === 'evaluaciones'): ?>
            <small style="color:#637087;">Nivel <?= (int)$capNivelVista ?> · Módulo <?= (int)$capModuloVistaActual ?></small>
        <?php elseif ($usaFlujoCapHub): ?>
            <small style="color:#637087;">Nivel <?= (int)$capNivelVista ?> · Módulo <?= (int)$capModuloVistaActual ?><?= $capSeccionVista !== '' ? ' · ' . htmlspecialchars(ucfirst($capSeccionVista), ENT_QUOTES, 'UTF-8') : '' ?></small>
        <?php else: ?>
            <small style="color:#637087;">Gestiona módulos de material con varios archivos por creación.</small>
        <?php endif; ?>
    </div>
    <?php if (!$usaFlujoCapHub): ?>
        <a href="<?= PUBLIC_URL ?>?url=home/material" class="btn btn-secondary">Volver a Material</a>
    <?php elseif ($usaFlujoCapHub && ($vistaCapNivelIndependiente || $modoSeleccionNivelCap)): ?>
        <a href="<?= PUBLIC_URL ?>?url=home" class="btn btn-secondary" title="Inicio">
            <i class="bi bi-house-door"></i> Inicio
        </a>
        <?php if ($modoHubModuloCapMaestro || $mostrarContenidoDetalleCapMaestro): ?>
            <a href="<?= htmlspecialchars($urlCapHubModulo, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary" title="Volver al módulo">
                <i class="bi bi-arrow-left"></i> Módulo
            </a>
        <?php elseif ($modoSeleccionModuloCap): ?>
            <a href="<?= htmlspecialchars($urlCapNiveles, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary" title="Volver a niveles">
                <i class="bi bi-arrow-left"></i> Niveles
            </a>
        <?php else: ?>
            <a href="<?= htmlspecialchars($urlCapNiveles, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">
                <i class="bi bi-grid-3x3-gap"></i> Ver todos los niveles
            </a>
        <?php endif; ?>
    <?php elseif ($capNivelVista > 0): ?>
        <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">
            <i class="bi bi-grid-3x3-gap"></i> Ver todos los niveles
        </a>
    <?php endif; ?>
</div>

<?php if ($mensaje !== ''): ?>
    <div class="alert alert-<?= $tipo === 'success' ? 'success' : 'danger' ?>" style="margin-top:14px;">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<?php if ($usaFlujoCapHub && $modoSeleccionNivelCap && !$esDiscipuloCapDestino): ?>
<div class="maestro-welcome-banner"<?= $esVistaMaestro ? ' data-tour="maestro-bienvenida"' : '' ?>>
    <?php if ($esVistaMaestro): ?>
        <h3>Bienvenido, maestro</h3>
        <p>Selecciona un nivel para gestionar evaluaciones, tareas e inscritos, y consultar el material de clase y de profesor (sin subir archivos).</p>
    <?php else: ?>
        <h3>Capacitación Destino</h3>
        <p>Selecciona un nivel para gestionar material, evaluaciones, inscritos y tareas según tus permisos.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php
$mostrarTarjetaResumenModulo = (!$usaFlujoCapHub || (!$modoSeleccionModuloCap && !$modoHubModuloCapMaestro))
    && !($esDiscipuloCapDestino && $usaFlujoCapHub);
?>
<?php if ($mostrarTarjetaResumenModulo): ?>
<div class="card" style="margin-top:14px; margin-bottom:14px; padding:14px; border-left:4px solid <?= htmlspecialchars($color) ?>;">
    <div style="display:flex; align-items:center; gap:10px;">
        <span style="width:38px; height:38px; border-radius:10px; background:<?= htmlspecialchars($color) ?>; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:18px;">
            <i class="<?= htmlspecialchars($icono) ?>"></i>
        </span>
        <div>
            <strong style="display:block;"><?= htmlspecialchars($titulo) ?></strong>
            <small style="color:#5a6f8d;"><?= count($temas) ?> tema(s) y <?= $totalArchivos ?> archivo(s), ordenado por creación reciente.</small>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($esCapacitacionDestino && $esVistaMaestro && $puedeGestionar && !$modoSeleccionModuloCap && !$modoHubModuloCapMaestro && !$modoSeleccionNivelCap): ?>
<div class="alert alert-info" style="margin-bottom: 12px;">
    Puedes gestionar evaluaciones, tareas, inscritos y conexiones. La subida de material de clase o de profesor la realiza un administrador.
</div>
<?php elseif ($esCapacitacionDestino && $puedeGestionar && !$puedeSubirMaterial): ?>
<div class="alert alert-warning" style="margin-bottom: 12px;">
    Tienes acceso de gestión en este módulo, pero no cuentas con permiso para subir archivos.
</div>
<?php endif; ?>

<?php
$mostrarFormularioSubidaCap = $puedeGestionar && $puedeSubirMaterial;
if ($esCapacitacionDestino) {
    $mostrarFormularioSubidaCap = $mostrarFormularioSubidaCap && ($vistaCapNivelIndependiente || $modoSeleccionNivelCap);
}
if ($usaFlujoCapHub && ($modoSeleccionModuloCap || $modoHubModuloCapMaestro)) {
    $mostrarFormularioSubidaCap = false;
}
$categoriaSubidaInicial = strtolower(trim((string)($_GET['cap_categoria'] ?? 'profesor')));
if (!in_array($categoriaSubidaInicial, ['clase', 'profesor'], true)) {
    $categoriaSubidaInicial = 'profesor';
}
$formularioSubidaAbierto = $vistaCapNivelIndependiente;
?>
<?php if ($mostrarFormularioSubidaCap): ?>
<div style="margin-bottom: 10px;">
    <button type="button"
        class="btn btn-primary"
        id="btn-toggle-upload-form"
        aria-expanded="<?= $formularioSubidaAbierto ? 'true' : 'false' ?>"
        aria-controls="upload-form-panel"
        onclick="(function(btn){var panel=document.getElementById('upload-form-panel');if(!panel){return;}var abierto=panel.style.display==='block';panel.style.display=abierto?'none':'block';btn.setAttribute('aria-expanded',abierto?'false':'true');btn.textContent=abierto?'Mostrar formulario de subir material':'Ocultar formulario de subir material';})(this);">
        <?= $formularioSubidaAbierto ? 'Ocultar formulario de subir material' : 'Mostrar formulario de subir material' ?>
    </button>
</div>
<div class="form-container" id="upload-form-panel" style="margin-bottom: 16px; display:<?= $formularioSubidaAbierto ? 'block' : 'none' ?>;">
    <h3 style="margin-top:0;">Subir material (profesor / clase)</h3>
    <form method="POST" enctype="multipart/form-data" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>">
        <input type="hidden" name="accion" value="subir">
        <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
        <?php if ($capNivelVista > 0): ?>
            <input type="hidden" name="contexto_nivel" value="<?= (int)$capNivelVista ?>">
        <?php endif; ?>
        <?php if ($capModuloVistaActual > 0): ?>
            <input type="hidden" name="contexto_modulo" value="<?= (int)$capModuloVistaActual ?>">
            <input type="hidden" name="contexto_categoria" value="<?= htmlspecialchars($categoriaSubidaInicial, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <div class="form-group" style="margin-bottom: 12px;">
            <label for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" class="form-control" maxlength="255" required placeholder="Ej: Guía Semana 1">
        </div>
        <div class="form-group" style="margin-bottom: 12px;">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" class="form-control" rows="3" placeholder="Descripción opcional del material"></textarea>
        </div>
        <?php if ($tieneSubmodulos): ?>
            <div class="form-group" style="margin-bottom: 12px;">
                <label for="categoria">Submódulo</label>
                <select id="categoria" name="categoria" class="form-control" required>
                    <option value="clase" <?= $categoriaSubidaInicial === 'clase' ? 'selected' : '' ?>>Material clase</option>
                    <option value="profesor" <?= $categoriaSubidaInicial === 'profesor' ? 'selected' : '' ?>>Material profesor</option>
                </select>
            </div>
        <?php endif; ?>
        <?php if ($esCapacitacionDestino): ?>
            <div class="form-group" style="margin-bottom: 12px;">
                <label for="nivel">Nivel</label>
                <select id="nivel" name="nivel" class="form-control" required>
                    <option value="1" <?= $capNivelVista === 1 || $capNivelVista <= 0 ? 'selected' : '' ?>>Nivel 1</option>
                    <option value="2" <?= $capNivelVista === 2 ? 'selected' : '' ?>>Nivel 2</option>
                    <option value="3" <?= $capNivelVista === 3 ? 'selected' : '' ?>>Nivel 3</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label for="modulo_numero">Módulo</label>
                <select id="modulo_numero" name="modulo_numero" class="form-control" required data-selected="<?= $capModuloVistaActual > 0 ? (int)$capModuloVistaActual : '' ?>"></select>
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label for="leccion">Lección <span class="req">*</span></label>
                <input type="text" id="leccion" name="leccion" class="form-control" maxlength="120" required placeholder="Ej: Lección 1">
            </div>
        <?php endif; ?>
        <div class="form-group" style="margin-bottom: 12px;">
            <label for="material_pdf">Archivo(s)</label>
            <input type="file" id="material_pdf" name="material_pdf[]" class="form-control" multiple required>
            <small style="display:block; margin-top:6px; color:#666;">Máximo 20MB por archivo. Puedes subir varios en un solo tema y se permiten varios formatos (pdf, docx, xlsx, pptx, mp4, etc.).</small>
        </div>
        <button type="submit" class="btn btn-primary">Subir archivos</button>
    </form>
</div>
<?php endif; ?>

<?php if ($usaTarjetasTipoMaterial && (!$esDiscipuloCapDestino || $usaFlujoCapHub)): ?>
<?php if ($esCapacitacionDestino): ?>
    <?php if (!$vistaCapNivelIndependiente): ?>
    <div class="cap-level-selector" id="cap-level-selector" data-tour="cap-level-selector">
        <?php foreach ($resumenNivelesCap as $nivelCard): ?>
            <a class="cap-level-card js-cap-level-card <?= $capNivelVista === (int)$nivelCard['nivel'] ? 'is-active' : '' ?>"
               data-level="<?= (int)$nivelCard['nivel'] ?>"
               href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta, ENT_QUOTES, 'UTF-8') ?>&cap_nivel=<?= (int)$nivelCard['nivel'] ?>">
                <h4 class="cap-level-card-title">Nivel <?= (int)$nivelCard['nivel'] ?></h4>
                <div class="cap-level-card-meta">
                    <span><?= (int)$nivelCard['total_modulos'] ?> módulo(s)</span>
                    <span class="cap-level-card-enter-btn">Entrar</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($usaFlujoCapHub && $modoSeleccionModuloCap): ?>
    <div class="cap-maestro-nav">
        <a class="btn btn-sm btn-secondary" href="<?= htmlspecialchars($urlCapNiveles, ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-arrow-left"></i> Niveles
        </a>
    </div>
    <?php if (!$esDiscipuloCapDestino): ?>
    <div class="maestro-welcome-banner cap-module-pick-banner">
        <h3>Nivel <?= (int)$capNivelVista ?> — elige un módulo</h3>
        <p>Primero selecciona el módulo. Después verás evaluaciones, tareas y el resto de opciones.</p>
    </div>
    <?php endif; ?>
    <?php if (!empty($resumenModulosCap)): ?>
    <?php
        $urlClaseNivelPick = '';
        foreach ($resumenModulosCap as $moduloClaseCard) {
            $urlTmpClase = trim((string)($moduloClaseCard['zoom_url'] ?? ''));
            if ($urlTmpClase !== '') {
                $urlClaseNivelPick = $urlTmpClase;
                break;
            }
        }
        if ($urlClaseNivelPick === '' && $esDiscipuloCapDestino) {
            foreach ($accesosDiscipuloCapDestino as $accesoClaseTmp) {
                if ((int)($accesoClaseTmp['nivel'] ?? 0) !== $capNivelVista) {
                    continue;
                }
                $urlTmpClase = trim((string)($accesoClaseTmp['url_clase'] ?? ''));
                if ($urlTmpClase !== '') {
                    $urlClaseNivelPick = $urlTmpClase;
                    break;
                }
            }
        }
    ?>
    <div class="card report-card cap-clase-modulo-pick" style="padding:14px; margin-bottom:14px;">
        <h3 style="margin:0 0 4px 0;">Acceso a clase</h3>
        <small style="color:#637087;">Link de conexión del nivel <?= (int)$capNivelVista ?>.</small>
        <div style="margin-top:10px;">
            <?php if ($urlClaseNivelPick !== ''): ?>
                <a class="btn btn-sm" style="background:#10b981;color:#fff;" href="<?= htmlspecialchars($urlClaseNivelPick, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Ir a clase</a>
            <?php else: ?>
                <button type="button" class="btn btn-sm" style="background:#94a3b8;color:#fff;" disabled title="Aún no hay link de clase configurado">Ir a clase</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="cap-level-selector cap-module-selector-grid cap-module-pick-screen" id="cap-module-selector-grid" data-tour="cap-module-selector">
        <?php foreach ($resumenModulosCap as $moduloCard): ?>
            <?php
                $keyActividadMod = (int)$capNivelVista . '_' . (int)$moduloCard['numero'];
                $actividadModHub = (array)($hubActividadPorModulo[$keyActividadMod] ?? []);
                $evalActivasMod = (int)($actividadModHub['evaluaciones'] ?? 0);
                $tareasActivasMod = (int)($actividadModHub['tareas'] ?? 0);
                $moduloConActividadHub = $evalActivasMod > 0 || $tareasActivasMod > 0;
                $claseModuloPick = $moduloConActividadHub ? ' cap-module-pick-card--active' : ' cap-module-pick-card--empty';
            ?>
            <a class="cap-level-card cap-module-pick-card<?= $claseModuloPick ?>"
               href="<?= htmlspecialchars($urlMaterialCapBase . '&cap_nivel=' . (int)$capNivelVista . '&cap_modulo=' . (int)$moduloCard['numero'], ENT_QUOTES, 'UTF-8') ?>">
                <h4 class="cap-level-card-title">Módulo <?= (int)$moduloCard['numero'] ?></h4>
                <div class="cap-level-card-meta">
                    <?php if ($esDiscipuloCapDestino): ?>
                        <span><?= $evalActivasMod ?> eval. · <?= $tareasActivasMod ?> tarea(s)</span>
                    <?php else: ?>
                        <span><?= (int)$moduloCard['total_temas'] ?> tema(s) · <?= $evalActivasMod ?> eval. · <?= $tareasActivasMod ?> tarea(s)</span>
                    <?php endif; ?>
                    <?php if (trim((string)($moduloCard['profesor'] ?? '')) !== ''): ?>
                        <strong><?= htmlspecialchars((string)$moduloCard['profesor'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php else: ?>
                        <strong>Entrar</strong>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-warning" style="margin-top:12px;">No hay módulos configurados para este nivel. Contacta al administrador.</div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($usaFlujoCapHub && $modoHubModuloCapMaestro): ?>
    <div class="cap-maestro-nav">
        <a class="btn btn-sm btn-secondary" href="<?= htmlspecialchars($urlCapModulos, ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-arrow-left"></i> Módulos
        </a>
        <a class="btn btn-sm btn-secondary" href="<?= htmlspecialchars($urlCapNiveles, ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-grid-3x3-gap"></i> Niveles
        </a>
    </div>
    <div class="card report-card" style="padding:14px; margin-bottom:14px;">
        <h3 style="margin:0 0 4px 0;">Acceso a clase</h3>
        <small style="color:#637087;">Link de conexión configurado para este módulo.</small>
        <div style="margin-top:10px;">
            <?php if ($urlClaseMaestroModulo !== ''): ?>
                <a class="btn btn-sm" style="background:#10b981;color:#fff;" href="<?= htmlspecialchars($urlClaseMaestroModulo, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Ir a clase</a>
            <?php else: ?>
                <button type="button" class="btn btn-sm" style="background:#94a3b8;color:#fff;" disabled title="Aún no hay link de clase configurado">Ir a clase</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card" style="padding:14px; margin-bottom:14px;" data-tour="hub-modulo-menu">
        <h3 style="margin:0 0 6px 0; color:#1e4a89;">Gestión del módulo <?= (int)$capModuloVistaActual ?></h3>
        <small style="color:#637087;">Elige qué quieres revisar o gestionar.</small>
        <div class="cap-maestro-hub-grid">
            <?php if ($puedeHubMaterial): ?>
            <a class="cap-maestro-hub-card" href="<?= htmlspecialchars($urlCapHubModulo . '&cap_seccion=material', ENT_QUOTES, 'UTF-8') ?>" data-tour="hub-material">
                <h4><i class="bi bi-journal-bookmark"></i> Material y lecciones</h4>
                <p><?= $esDiscipuloCapDestino ? 'Consulta el material de clase y de profesor de tu módulo.' : 'Lecciones, material de clase y de profesor.' ?></p>
            </a>
            <?php endif; ?>
            <?php if ($puedeHubEvaluaciones && $urlEvaluacionesCapModulo !== ''): ?>
            <a class="cap-maestro-hub-card<?= $hubEvaluacionesActivas > 0 ? ' cap-maestro-hub-card--active' : ' cap-maestro-hub-card--empty' ?>" href="<?= htmlspecialchars($urlEvaluacionesCapModulo, ENT_QUOTES, 'UTF-8') ?>" data-tour="hub-evaluaciones" data-tour-maestro="evaluaciones">
                <h4><i class="bi bi-journal-check"></i> Evaluaciones</h4>
                <p><?php if ($hubEvaluacionesActivas > 0): ?>
                    <?= $puedeGestionar ? 'Hay evaluaciones vigentes en este módulo (' . $hubEvaluacionesActivas . ').' : 'Tienes ' . $hubEvaluacionesActivas . ' evaluación(es) activa(s) hoy.' ?>
                <?php else: ?>
                    No hay evaluaciones activas hoy en este módulo.
                <?php endif; ?></p>
            </a>
            <?php endif; ?>
            <?php if ($puedeHubInscritos): ?>
            <a class="cap-maestro-hub-card" href="<?= htmlspecialchars($urlCapHubModulo . '&cap_seccion=inscritos', ENT_QUOTES, 'UTF-8') ?>" data-tour="hub-inscritos">
                <h4><i class="bi bi-people"></i> Inscritos</h4>
                <p>Planilla de asistencia y alumnos del nivel <?= (int)$capNivelVista ?> (<?= count($inscritosCapNivel) ?>).</p>
            </a>
            <?php endif; ?>
            <?php if ($puedeHubTareasGestion): ?>
            <a class="cap-maestro-hub-card<?= $hubEntregasPendientesCap > 0 ? ' cap-maestro-hub-card--active' : '' ?>" href="<?= htmlspecialchars($urlCapHubModulo . '&cap_seccion=calificaciones', ENT_QUOTES, 'UTF-8') ?>" data-tour="hub-calificaciones">
                <h4><i class="bi bi-table"></i> Planilla y calificaciones</h4>
                <p>Notas por estudiante, resumen de asistencia y calificación rápida<?= $hubEntregasPendientesCap > 0 ? ' (' . $hubEntregasPendientesCap . ' entrega(s) pendiente(s)).' : '.' ?></p>
            </a>
            <?php endif; ?>
            <?php if ($puedeHubTareasGestion || $puedeHubTareasEntregar): ?>
            <a class="cap-maestro-hub-card<?= $hubTareasActivas > 0 ? ' cap-maestro-hub-card--active' : ' cap-maestro-hub-card--empty' ?>" href="<?= htmlspecialchars($urlCapHubModulo . '&cap_seccion=tareas', ENT_QUOTES, 'UTF-8') ?>" data-tour="hub-tareas" data-tour-maestro="tareas">
                <h4><i class="bi bi-journal-text"></i> Tareas</h4>
                <p><?php if ($hubTareasActivas > 0): ?>
                    <?= $puedeHubTareasGestion ? 'Gestionar tareas y calificar entregas (' . $hubTareasActivas . ').' : 'Tienes ' . $hubTareasActivas . ' tarea(s) para entregar.' ?>
                <?php else: ?>
                    No hay tareas publicadas en este módulo por ahora.
                <?php endif; ?></p>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($usaFlujoCapHub && $mostrarContenidoDetalleCapMaestro): ?>
    <div class="cap-maestro-nav">
        <a class="btn btn-sm btn-secondary" href="<?= htmlspecialchars($urlCapHubModulo, ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-arrow-left"></i> Volver al módulo
        </a>
    </div>
    <?php elseif (!$usaFlujoCapHub && $capNivelVista > 0): ?>
        <div style="margin-bottom:10px;">
            <a class="btn btn-sm btn-secondary" href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta, ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-arrow-left-short"></i> Volver a niveles
            </a>
        </div>
    <?php endif; ?>
    <?php if ($mostrarToolbarCap): ?>
    <div class="cap-toolbar<?= $usaFlujoCapHub ? ' cap-toolbar--maestro-material' : '' ?>">
        <?php if (!$usaFlujoCapHub): ?>
        <div class="cap-toolbar-group">
            <span class="cap-toolbar-label">Subcarpetas por módulo</span>
            <div class="cap-module-selector" id="cap-module-selector"></div>
        </div>
        <div class="cap-toolbar-group">
            <span class="cap-toolbar-label">Lecciones creadas</span>
            <div class="cap-view-switch">
                <button type="button" class="cap-view-btn js-cap-view-btn is-active" data-cap-view="lecciones">
                    <i class="bi bi-book"></i> Lecciones
                </button>
                <button type="button" class="cap-view-btn js-cap-view-btn" data-cap-view="evaluaciones">
                    <i class="bi bi-journal-check"></i> Evaluaciones
                </button>
                <span class="cap-lessons-meta" id="cap-lessons-count">Lecciones registradas: 0 items</span>
            </div>
        </div>
        <?php else: ?>
        <span class="cap-lessons-meta cap-lessons-meta--inline" id="cap-lessons-count">Lecciones registradas: 0 items</span>
        <?php endif; ?>
        <div class="cap-toolbar-group">
            <span class="cap-toolbar-label">Carpeta de material</span>
            <div class="cap-categoria-switch">
                <button type="button" class="cap-categoria-btn js-cap-categoria-btn is-active" data-categoria="profesor">
                    <i class="bi bi-folder"></i> Material profesor
                </button>
                <button type="button" class="cap-categoria-btn js-cap-categoria-btn" data-categoria="clase">
                    <i class="bi bi-folder"></i> Material clase
                </button>
            </div>
        </div>

        <?php if ($capNivelVista > 0 && !$usaFlujoCapHub): ?>
            <div class="cap-toolbar-group">
                <span class="cap-toolbar-label">Gestión del nivel</span>
                <div class="cap-view-switch">
                    <button type="button" class="cap-view-btn js-cap-academico-btn is-active" data-cap-academico="inscritos">
                        <i class="bi bi-people"></i> Inscritos <span class="meta"><?= count($inscritosCapNivel) ?></span>
                    </button>
                    <button type="button" class="cap-view-btn js-cap-academico-btn" data-cap-academico="tareas">
                        <i class="bi bi-journal-text"></i> Tareas <span class="meta"><?= count($tareasCapNivel) ?></span>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($mostrarAcademicoCap): ?>
        <div id="cap-academico-panel" class="cap-academico-panel cap-main-section<?= $usaFlujoCapHub ? '' : ' is-hidden' ?>">
            <div class="cap-academico-head">
                <strong style="color:#2b4f79;"><?php
                    if ($capSeccionVista === 'tareas') {
                        echo 'Tareas módulo ' . (int)$capModuloVistaActual;
                    } elseif ($capSeccionVista === 'calificaciones') {
                        echo 'Planilla · Módulo ' . (int)$capModuloVistaActual;
                    } elseif ($capSeccionVista === 'inscritos') {
                        echo 'Inscritos · Nivel ' . (int)$capNivelVista;
                    } else {
                        echo $puedeHubInscritos ? 'Gestión académica del nivel ' . (int)$capNivelVista : 'Tareas módulo ' . (int)$capModuloVistaActual;
                    }
                ?></strong>
                <small style="color:#5a6f8d;"><?php
                    if ($capSeccionVista === 'tareas') {
                        echo 'Entrega y seguimiento de tareas de este módulo.';
                    } elseif ($capSeccionVista === 'calificaciones') {
                        echo 'Califica tareas por estudiante con resumen de asistencia al nivel.';
                    } elseif ($capSeccionVista === 'inscritos') {
                        echo 'Planilla de asistencia del nivel actual.';
                    } else {
                        echo $puedeHubInscritos ? 'Inscritos y tareas del módulo actual' : 'Entrega de tareas del módulo actual';
                    }
                ?></small>
            </div>

            <?php if ($puedeHubInscritos): ?>
            <div id="cap-academico-inscritos" class="cap-academico-section<?= ($usaFlujoCapHub && in_array($capSeccionVista, ['tareas', 'calificaciones'], true)) ? ' is-hidden' : '' ?>">
                <?php if (!empty($inscritosCapNivel)): ?>
                    <?php
                        $ministeriosInscritosCap = [];
                        foreach ($inscritosCapNivel as $inscritoMinTmp) {
                            $nombreMinTmp = trim((string)($inscritoMinTmp['ministerio'] ?? 'Sin ministerio'));
                            if ($nombreMinTmp === '') {
                                $nombreMinTmp = 'Sin ministerio';
                            }
                            $ministeriosInscritosCap[$nombreMinTmp] = true;
                        }
                        ksort($ministeriosInscritosCap, SORT_NATURAL | SORT_FLAG_CASE);
                        $totalInscritosCap = count($inscritosCapNivel);
                    ?>
                    <div class="cap-inscritos-toolbar">
                        <div class="cap-inscritos-contador" id="cap-inscritos-contador">
                            <span class="cap-inscritos-contador-num" id="cap-inscritos-contador-num"><?= (int)$totalInscritosCap ?></span>
                            <span>inscrito(s) <small id="cap-inscritos-contador-detalle">de <?= (int)$totalInscritosCap ?> en nivel <?= (int)$capNivelVista ?></small></span>
                        </div>
                        <div class="cap-inscritos-toolbar-actions">
                            <label class="cap-inscritos-filtro-label" for="cap-filtro-ministerio">Ministerio</label>
                            <select id="cap-filtro-ministerio" class="form-control cap-inscritos-filtro-select">
                                <option value="">Todos los ministerios</option>
                                <?php foreach (array_keys($ministeriosInscritosCap) as $nombreMinisterioFiltro): ?>
                                    <option value="<?= htmlspecialchars($nombreMinisterioFiltro, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($nombreMinisterioFiltro) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button
                                type="button"
                                class="btn btn-secondary btn-sm btn-descargar-imagen-tabla"
                                data-tabla-id="cap-inscritos-export-wrap"
                                data-export-title="Inscritos Capacitación Destino — Nivel <?= (int)$capNivelVista ?>"
                                data-filename="inscritos-cap-destino-nivel-<?= (int)$capNivelVista ?>"
                                data-export-subtitle-from="cap-inscritos-filtros-form"
                                data-export-stats-from="cap-inscritos-contador"
                                data-label-default="Descargar imagen"
                            >
                                <i class="bi bi-image"></i> Descargar imagen
                            </button>
                        </div>
                    </div>
                    <form id="cap-inscritos-filtros-form" style="display:none;" aria-hidden="true">
                        <select name="ministerio" id="cap-filtro-ministerio-mirror"></select>
                    </form>
                    <div id="cap-inscritos-export-wrap" style="overflow-x: auto;">
                        <table class="cap-inscritos-table" style="min-width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="position: sticky; left: 0; background: #f8f9fa; z-index: 10;">Nombre</th>
                                    <th style="position: sticky; left: 120px; background: #f8f9fa; z-index: 10;">Cédula</th>
                                    <th colspan="<?= (int)$totalClasesCap ?>" style="text-align:center; background:#e8f0f8;">Planilla de Asistencia</th>
                                    <th colspan="2" style="text-align:center; background:#dbeafe;">Resumen</th>
                                </tr>
                                <tr>
                                    <th style="position: sticky; left: 0; background: #f8f9fa; z-index: 10;"></th>
                                    <th style="position: sticky; left: 120px; background: #f8f9fa; z-index: 10;"></th>
                                    <?php for ($clase = 1; $clase <= $totalClasesCap; $clase++): ?>
                                        <th class="cap-clase-col" style="text-align:center; background:#e8f0f8; padding:6px 3px;">Clase <?= $clase ?></th>
                                    <?php endfor; ?>
                                    <th style="text-align:center; background:#dbeafe; padding:6px 3px; min-width:72px;">Total</th>
                                    <th style="text-align:center; background:#dbeafe; padding:6px 3px; min-width:56px;">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inscritosCapNivel as $inscrito): ?>
                                    <?php
                                        $idPersona = (int)($inscrito['id_persona'] ?? 0);
                                        $asistenciasPersona = !empty($asistenciasPorPersona[$idPersona]) ? (array)$asistenciasPorPersona[$idPersona] : [];
                                        $numAsistenciasCap = count($asistenciasPersona);
                                        $pctAsistenciaCap = $totalClasesCap > 0
                                            ? (int)round(($numAsistenciasCap / $totalClasesCap) * 100)
                                            : 0;
                                        $clasePctCap = 'cap-pct--bajo';
                                        if ($pctAsistenciaCap >= 75) {
                                            $clasePctCap = 'cap-pct--alto';
                                        } elseif ($pctAsistenciaCap >= 50) {
                                            $clasePctCap = 'cap-pct--medio';
                                        }
                                        $ministerioInscrito = trim((string)($inscrito['ministerio'] ?? 'Sin ministerio'));
                                        if ($ministerioInscrito === '') {
                                            $ministerioInscrito = 'Sin ministerio';
                                        }
                                    ?>
                                    <tr class="cap-inscrito-row" data-ministerio="<?= htmlspecialchars($ministerioInscrito, ENT_QUOTES, 'UTF-8') ?>">
                                        <td style="position: sticky; left: 0; background: white; z-index: 5;"><?= htmlspecialchars((string)($inscrito['nombre'] ?? '')) ?></td>
                                        <td style="position: sticky; left: 120px; background: white; z-index: 5;"><?= htmlspecialchars((string)($inscrito['cedula'] ?? '')) ?></td>
                                        <?php for ($clase = 1; $clase <= $totalClasesCap; $clase++): ?>
                                            <td class="cap-clase-col" style="text-align:center; padding:6px 3px;">
                                                <input type="checkbox" class="asistencia-check" 
                                                    data-id-persona="<?= $idPersona ?>" 
                                                    data-clase="<?= $clase ?>"
                                                    data-nivel="<?= (int)$capNivelVista ?>"
                                                    <?= in_array($clase, $asistenciasPersona, true) ? 'checked' : '' ?>
                                                    disabled
                                                    title="Se marca al presentar la evaluación de la lección correspondiente (Clase <?= $clase ?>)"
                                                    style="width:20px; height:20px; cursor:default;">
                                            </td>
                                        <?php endfor; ?>
                                        <td class="cap-asistencia-resumen-total" style="text-align:center; font-weight:700; background:#f8fafc;">
                                            <?= (int)$numAsistenciasCap ?>/<?= (int)$totalClasesCap ?>
                                        </td>
                                        <td class="cap-asistencia-resumen-pct <?= htmlspecialchars($clasePctCap, ENT_QUOTES, 'UTF-8') ?>" style="text-align:center; font-weight:700;">
                                            <?= (int)$pctAsistenciaCap ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="cap-asistencia-totales-row">
                                    <td colspan="2" style="position: sticky; left: 0; background: #e8f0f8; font-weight: 700; color: #2b4f79; z-index: 6;">
                                        Total asistencias
                                    </td>
                                    <?php for ($clase = 1; $clase <= $totalClasesCap; $clase++): ?>
                                        <td class="cap-asistencia-total cap-clase-col" data-clase="<?= $clase ?>" style="text-align:center; background:#e8f0f8; font-weight:700; color:#1e5631; padding:8px 3px;">
                                            <?= (int)($totalesAsistenciaPorClase[$clase] ?? 0) ?>
                                        </td>
                                    <?php endfor; ?>
                                    <td colspan="2" style="background:#e8f0f8;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
                    <script src="<?= ASSETS_URL ?>/js/descargar_tabla_asistencia.js?v=20260526"></script>
                    <script>
                    (function() {
                        var panel = document.getElementById('cap-academico-inscritos');
                        if (!panel) {
                            return;
                        }
                        var filtro = document.getElementById('cap-filtro-ministerio');
                        var filtroMirror = document.getElementById('cap-filtro-ministerio-mirror');
                        var contadorNum = document.getElementById('cap-inscritos-contador-num');
                        var contadorDetalle = document.getElementById('cap-inscritos-contador-detalle');
                        var totalInscritos = <?= (int)$totalInscritosCap ?>;
                        var totalClasesCap = <?= (int)$totalClasesCap ?>;
                        var filas = panel.querySelectorAll('.cap-inscrito-row');
                        var totalesCeldas = panel.querySelectorAll('.cap-asistencia-total');

                        function clasePctResumen(pct) {
                            if (pct >= 75) {
                                return 'cap-pct--alto';
                            }
                            if (pct >= 50) {
                                return 'cap-pct--medio';
                            }
                            return 'cap-pct--bajo';
                        }

                        function recalcularResumenAsistenciaFilas() {
                            filasVisibles().forEach(function(fila) {
                                var asistencias = 0;
                                fila.querySelectorAll('.asistencia-check:checked').forEach(function() {
                                    asistencias++;
                                });
                                var pct = totalClasesCap > 0 ? Math.round((asistencias / totalClasesCap) * 100) : 0;
                                var celdaTotal = fila.querySelector('.cap-asistencia-resumen-total');
                                var celdaPct = fila.querySelector('.cap-asistencia-resumen-pct');
                                if (celdaTotal) {
                                    celdaTotal.textContent = asistencias + '/' + totalClasesCap;
                                }
                                if (celdaPct) {
                                    celdaPct.textContent = pct + '%';
                                    celdaPct.classList.remove('cap-pct--alto', 'cap-pct--medio', 'cap-pct--bajo');
                                    celdaPct.classList.add(clasePctResumen(pct));
                                }
                            });
                        }

                        function filasVisibles() {
                            return Array.prototype.filter.call(filas, function(fila) {
                                return !fila.classList.contains('cap-inscrito-row--oculta');
                            });
                        }

                        function recalcularTotalesAsistencia() {
                            var visibles = filasVisibles();
                            totalesCeldas.forEach(function(celda) {
                                var clase = parseInt(celda.getAttribute('data-clase') || '0', 10);
                                if (!clase) {
                                    return;
                                }
                                var total = 0;
                                visibles.forEach(function(fila) {
                                    var check = fila.querySelector('.asistencia-check[data-clase="' + clase + '"]');
                                    if (check && check.checked) {
                                        total++;
                                    }
                                });
                                celda.textContent = String(total);
                            });
                            recalcularResumenAsistenciaFilas();
                        }

                        function aplicarFiltroMinisterio() {
                            var valor = filtro ? String(filtro.value || '').trim() : '';
                            if (filtroMirror) {
                                filtroMirror.value = valor;
                            }
                            var visibles = 0;
                            filas.forEach(function(fila) {
                                var ministerioFila = String(fila.getAttribute('data-ministerio') || '');
                                var mostrar = valor === '' || ministerioFila === valor;
                                fila.classList.toggle('cap-inscrito-row--oculta', !mostrar);
                                if (mostrar) {
                                    visibles++;
                                }
                            });
                            if (contadorNum) {
                                contadorNum.textContent = String(visibles);
                            }
                            if (contadorDetalle) {
                                if (valor === '') {
                                    contadorDetalle.textContent = 'de ' + totalInscritos + ' en nivel <?= (int)$capNivelVista ?>';
                                } else {
                                    contadorDetalle.textContent = 'en ' + valor + ' (de ' + totalInscritos + ' en el nivel)';
                                }
                            }
                            recalcularTotalesAsistencia();
                        }

                        if (filtro) {
                            filtro.addEventListener('change', aplicarFiltroMinisterio);
                        }
                        aplicarFiltroMinisterio();

                        document.querySelectorAll('#cap-academico-inscritos .btn-descargar-imagen-tabla').forEach(function(btn) {
                            btn.addEventListener('click', function(ev) {
                                var visibles = filasVisibles();
                                if (!visibles.length) {
                                    ev.stopImmediatePropagation();
                                    alert('No hay filas visibles para exportar. Cambia el filtro de ministerio.');
                                }
                            }, true);
                        });
                    })();
                    </script>
                    <p style="margin:8px 0 0; font-size:12px; color:#5a6f8d;">
                        La asistencia de cada clase se registra automáticamente cuando el estudiante presenta la evaluación de esa lección (Lección 3 → Clase 3).
                    </p>
                    
                    <style>
                        .cap-inscritos-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 10px;
                        }
                        .cap-inscritos-table th,
                        .cap-inscritos-table td {
                            border: 1px solid #ddd;
                            padding: 8px;
                            text-align: left;
                        }
                        .cap-inscritos-table th {
                            background-color: #f8f9fa;
                            font-weight: bold;
                            color: #333;
                        }
                        .cap-inscritos-table tbody tr:nth-child(even) {
                            background-color: #f9f9f9;
                        }
                        .cap-inscritos-table tbody tr:hover {
                            background-color: #f0f0f0;
                        }
                        .cap-inscritos-table tfoot .cap-asistencia-totales-row td {
                            border-top: 2px solid #9eb4cc;
                        }
                        .cap-asistencia-resumen-total {
                            color: #1e3a5f;
                        }
                        .cap-asistencia-resumen-pct.cap-pct--alto {
                            color: #15803d;
                            background: #ecfdf5 !important;
                        }
                        .cap-asistencia-resumen-pct.cap-pct--medio {
                            color: #a16207;
                            background: #fffbeb !important;
                        }
                        .cap-asistencia-resumen-pct.cap-pct--bajo {
                            color: #b91c1c;
                            background: #fef2f2 !important;
                        }
                        .asistencia-check:checked {
                            accent-color: #28a745;
                        }
                        .cap-inscritos-toolbar {
                            display: flex;
                            flex-wrap: wrap;
                            align-items: center;
                            justify-content: space-between;
                            gap: 12px;
                            margin-bottom: 12px;
                            padding: 12px 14px;
                            background: #f8fbff;
                            border: 1px solid #d6e3f4;
                            border-radius: 10px;
                        }
                        .cap-inscritos-contador {
                            display: flex;
                            align-items: baseline;
                            gap: 8px;
                            flex-wrap: wrap;
                        }
                        .cap-inscritos-contador-num {
                            font-size: 1.75rem;
                            font-weight: 700;
                            color: #1e4a89;
                            line-height: 1;
                        }
                        .cap-inscritos-contador small {
                            color: #5a6f8d;
                            font-size: 12px;
                        }
                        .cap-inscritos-toolbar-actions {
                            display: flex;
                            flex-wrap: wrap;
                            align-items: center;
                            gap: 8px;
                        }
                        .cap-inscritos-filtro-label {
                            font-size: 12px;
                            font-weight: 600;
                            color: #2b4f79;
                            margin: 0;
                        }
                        .cap-inscritos-filtro-select {
                            min-width: 200px;
                            max-width: 280px;
                        }
                        .cap-inscrito-row--oculta {
                            display: none;
                        }
                    </style>
                <?php else: ?>
                    <div class="alert alert-info" style="margin:0;">No hay inscritos registrados para este nivel.</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div id="cap-academico-tareas" class="cap-academico-section<?= (!$usaFlujoCapHub || $capSeccionVista !== 'tareas') ? ' is-hidden' : '' ?>" data-tour="maestro-seccion-tareas">
                <div style="margin-top:4px;">
                    <?php if ($puedeGestionar): ?>
                        <form method="POST" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" class="form-container" style="margin-bottom:10px;" data-tour="maestro-crear-tarea">
                            <input type="hidden" name="accion" value="crear_tarea">
                            <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                            <input type="hidden" name="nivel" value="<?= (int)$capNivelVista ?>">
                            <input type="hidden" name="modulo_numero" value="<?= (int)$capModuloVistaActual ?>">
                            <input type="hidden" name="contexto_nivel" value="<?= (int)$capNivelVista ?>">
                            <input type="hidden" name="contexto_modulo" value="<?= (int)$capModuloVistaActual ?>">
                            <input type="hidden" name="contexto_academico" value="tareas">
                            <div style="margin-bottom:8px;color:#526886;font-size:12px;">
                                Creando tarea para: <strong>Nivel <?= (int)$capNivelVista ?> · Módulo <?= (int)$capModuloVistaActual ?></strong>
                            </div>
                            <div style="display:grid; grid-template-columns:2fr 2fr 1fr auto; gap:8px; align-items:end;">
                                <div>
                                    <label style="font-size:12px;">Título de la tarea</label>
                                    <input type="text" class="form-control" name="titulo_tarea" maxlength="255" required placeholder="Ej: Taller Lección 2">
                                </div>
                                <div>
                                    <label style="font-size:12px;">Descripción</label>
                                    <input type="text" class="form-control" name="descripcion_tarea" maxlength="500" placeholder="Instrucciones para el discípulo">
                                </div>
                                <div>
                                    <label style="font-size:12px;">Fecha límite</label>
                                    <input type="date" class="form-control" name="fecha_limite_tarea">
                                </div>
                                <button type="submit" class="btn btn-sm btn-success">Crear tarea</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if (!empty($tareasCapNivel)): ?>
                        <?php foreach ($tareasCapNivel as $tarea): ?>
                            <?php
                                $idTarea = (int)($tarea['Id_Tarea'] ?? 0);
                                $entregasTarea = (array)($entregasTareasCap[$idTarea] ?? []);
                                $moduloTarea = (int)($tarea['Modulo_Numero'] ?? 0);
                                $totalEntregasUsuario = (int)($tarea['total_entregas_usuario'] ?? 0);
                            ?>
                            <div class="cap-tarea-card">
                                <h4 class="cap-tarea-title"><?= htmlspecialchars((string)($tarea['Titulo'] ?? 'Tarea')) ?></h4>
                                <div class="cap-tarea-meta">
                                    <span>Módulo <?= $moduloTarea > 0 ? $moduloTarea : 'General' ?></span>
                                    <span>Límite: <?= htmlspecialchars((string)($tarea['Fecha_Limite'] ?? 'Sin fecha')) ?></span>
                                    <span>Entregas: <?= (int)($tarea['total_entregas'] ?? 0) ?></span>
                                    <span>Estudiantes: <?= (int)($tarea['total_estudiantes'] ?? 0) ?></span>
                                    <?php if ($puedeSubirTareas): ?>
                                        <span>Tus archivos: <?= $totalEntregasUsuario ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($puedeGestionar): ?>
                                    <div style="display:flex;justify-content:flex-end;margin:6px 0 8px 0;">
                                        <form method="POST" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" onsubmit="return confirm('¿Seguro que deseas eliminar esta tarea? Esta acción ocultará la tarea creada.');">
                                            <input type="hidden" name="accion" value="eliminar_tarea">
                                            <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                                            <input type="hidden" name="id_tarea" value="<?= $idTarea ?>">
                                            <input type="hidden" name="nivel" value="<?= (int)$capNivelVista ?>">
                                            <input type="hidden" name="modulo_numero" value="<?= $moduloTarea ?>">
                                            <input type="hidden" name="contexto_nivel" value="<?= (int)$capNivelVista ?>">
                                            <input type="hidden" name="contexto_modulo" value="<?= $moduloTarea ?>">
                                            <input type="hidden" name="contexto_academico" value="tareas">
                                            <button type="submit" class="btn btn-sm btn-danger">Eliminar tarea</button>
                                        </form>
                                    </div>
                                <?php endif; ?>

                                <?php if (trim((string)($tarea['Descripcion'] ?? '')) !== ''): ?>
                                    <div style="font-size:12px; color:#4a6283; margin-bottom:8px;"><?= htmlspecialchars((string)$tarea['Descripcion']) ?></div>
                                <?php endif; ?>

                                <?php if ($puedeSubirTareas): ?>
                                    <?php if ($totalEntregasUsuario > 0): ?>
                                        <div class="alert alert-success cap-tarea-entregada-msg" style="margin:8px 0;">
                                            <i class="bi bi-check-circle"></i> Ya entregaste esta tarea. No puedes subir más archivos.
                                        </div>
                                    <?php else: ?>
                                    <form method="POST" enctype="multipart/form-data" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" class="cap-tarea-upload-form">
                                        <input type="hidden" name="accion" value="subir_tarea_entrega">
                                        <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                                        <input type="hidden" name="id_tarea" value="<?= $idTarea ?>">
                                        <input type="hidden" name="nivel" value="<?= (int)$capNivelVista ?>">
                                        <input type="hidden" name="modulo_numero" value="<?= $moduloTarea ?>">
                                        <input type="hidden" name="contexto_nivel" value="<?= (int)$capNivelVista ?>">
                                        <input type="hidden" name="contexto_modulo" value="<?= $moduloTarea ?>">
                                        <input type="hidden" name="contexto_academico" value="tareas">
                                        <div>
                                            <label style="font-size:12px;">Comentario</label>
                                            <input type="text" name="comentario_entrega" class="form-control" maxlength="500" placeholder="Comentario opcional de tu entrega">
                                        </div>
                                        <div>
                                            <label style="font-size:12px;">Archivos (varios a la vez)</label>
                                            <input type="file" name="tarea_archivos[]" class="form-control" multiple required accept="<?= htmlspecialchars($capTareaAcceptArchivos, ENT_QUOTES, 'UTF-8') ?>">
                                            <small class="cap-tarea-upload-hint">Imágenes, audio, video, PDF, Office y más. Máx. 100MB por archivo. Solo una entrega por tarea.</small>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary">Subir tarea</button>
                                    </form>
                                    <?php endif; ?>
                                    <?php
                                        $entregas_usuario = (array)($tarea['entregas_usuario'] ?? []);
                                        $clave_modulo_tarea = $clave;
                                        include VIEWS . '/home/partials/cap_entregas_usuario_tarea.php';
                                    ?>
                                <?php endif; ?>

                                <?php if ($puedeGestionar): ?>
                                    <details data-tour="maestro-calificar-tarea">
                                        <summary class="btn btn-sm btn-secondary" style="cursor:pointer;">Calificar entregas (<?= count($entregasTarea) ?>)</summary>
                                        <div style="margin-top:8px;">
                                            <?php if (!empty($entregasTarea)): ?>
                                                <?php foreach ($entregasTarea as $entrega): ?>
                                                    <?php
                                                        $nombreArchivoEntrega = (string)($entrega['Nombre_Archivo'] ?? '');
                                                        $urlArchivoEntrega = rtrim(PUBLIC_URL, '/') . '/uploads/material_hub_tareas/' . rawurlencode($clave) . '/' . rawurlencode($nombreArchivoEntrega);
                                                        $estaCalificada = strtolower(trim((string)($entrega['Estado_Calificacion'] ?? 'pendiente'))) === 'calificada';
                                                    ?>
                                                    <div class="cap-entrega-item">
                                                        <div class="cap-entrega-head">
                                                            <strong><?= htmlspecialchars((string)($entrega['nombre_persona'] ?? 'Estudiante')) ?></strong>
                                                            <span class="<?= $estaCalificada ? 'cap-entrega-calificada' : 'cap-entrega-pendiente' ?>">
                                                                <?= $estaCalificada ? 'Calificada' : 'Pendiente' ?>
                                                            </span>
                                                        </div>
                                                        <div style="font-size:12px; color:#566f92; margin-bottom:6px;">
                                                            Cédula: <?= htmlspecialchars((string)($entrega['cedula_persona'] ?? '')) ?> ·
                                                            Entrega: <?= htmlspecialchars((string)($entrega['Fecha_Entrega'] ?? '')) ?>
                                                        </div>
                                                        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:6px;">
                                                            <a class="btn btn-sm btn-info" target="_blank" rel="noopener noreferrer" href="<?= htmlspecialchars($urlArchivoEntrega, ENT_QUOTES, 'UTF-8') ?>">Abrir archivo</a>
                                                            <?php if (trim((string)($entrega['Comentario'] ?? '')) !== ''): ?>
                                                                <span style="font-size:12px; color:#4d6689;">Comentario: <?= htmlspecialchars((string)$entrega['Comentario']) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <form method="POST" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" style="display:grid; grid-template-columns:120px 1fr auto; gap:8px; align-items:end;">
                                                            <input type="hidden" name="accion" value="calificar_tarea_entrega">
                                                            <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                                                            <input type="hidden" name="id_entrega" value="<?= (int)($entrega['Id_Entrega'] ?? 0) ?>">
                                                            <input type="hidden" name="nivel" value="<?= (int)$capNivelVista ?>">
                                                            <input type="hidden" name="modulo_numero" value="<?= $moduloTarea ?>">
                                                            <input type="hidden" name="contexto_nivel" value="<?= (int)$capNivelVista ?>">
                                                            <input type="hidden" name="contexto_modulo" value="<?= $moduloTarea ?>">
                                                            <div>
                                                                <label style="font-size:12px;">Nota (0-5)</label>
                                                                <input type="number" step="0.1" min="0" max="5" name="nota_entrega" class="form-control" value="<?= htmlspecialchars((string)($entrega['Nota'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                            </div>
                                                            <div>
                                                                <label style="font-size:12px;">Retroalimentación</label>
                                                                <input type="text" name="retroalimentacion_entrega" class="form-control" maxlength="500" value="<?= htmlspecialchars((string)($entrega['Retroalimentacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                            </div>
                                                            <button type="submit" class="btn btn-sm btn-success">Guardar calificación</button>
                                                        </form>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="alert alert-info" style="margin:0;">Aún no hay entregas para esta tarea.</div>
                                            <?php endif; ?>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info" style="margin:0;">No hay tareas creadas para este nivel todavía.</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($puedeHubTareasGestion): ?>
            <div id="cap-academico-calificaciones" class="cap-academico-section<?= (!$usaFlujoCapHub || $capSeccionVista !== 'calificaciones') ? ' is-hidden' : '' ?>" data-tour="maestro-planilla-calificaciones">
                <?php if (!empty($planillaEstudiantesCap) && !empty($tareasCapNivel)): ?>
                    <?php
                        $ministeriosPlanillaCap = [];
                        foreach ($planillaEstudiantesCap as $estPlanTmp) {
                            $minPlanTmp = trim((string)($estPlanTmp['ministerio'] ?? 'Sin ministerio'));
                            if ($minPlanTmp === '') {
                                $minPlanTmp = 'Sin ministerio';
                            }
                            $ministeriosPlanillaCap[$minPlanTmp] = true;
                        }
                        ksort($ministeriosPlanillaCap, SORT_NATURAL | SORT_FLAG_CASE);
                    ?>
                    <div class="cap-planilla-toolbar">
                        <div class="cap-planilla-contador">
                            <strong><?= count($planillaEstudiantesCap) ?></strong> estudiante(s)
                            <?php if ($hubEntregasPendientesCap > 0): ?>
                                · <span class="cap-planilla-pendientes"><?= (int)$hubEntregasPendientesCap ?> entrega(s) por calificar</span>
                            <?php endif; ?>
                        </div>
                        <div class="cap-planilla-filtros">
                            <label for="cap-planilla-filtro-ministerio">Ministerio</label>
                            <select id="cap-planilla-filtro-ministerio" class="form-control cap-inscritos-filtro-select">
                                <option value="">Todos</option>
                                <?php foreach (array_keys($ministeriosPlanillaCap) as $minPlanilla): ?>
                                    <option value="<?= htmlspecialchars($minPlanilla, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($minPlanilla) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="cap-planilla-check-pendientes">
                                <input type="checkbox" id="cap-planilla-solo-pendientes"> Solo pendientes
                            </label>
                            <button type="button" class="btn btn-sm btn-success" id="cap-planilla-export-excel" title="Descargar planilla en Excel">
                                <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                            </button>
                        </div>
                    </div>
                    <div class="cap-planilla-wrap" style="overflow-x:auto;">
                        <table class="cap-planilla-table" id="cap-planilla-calificaciones">
                            <thead>
                                <tr>
                                    <th class="cap-planilla-sticky cap-planilla-sticky-1">Estudiante</th>
                                    <th class="cap-planilla-sticky cap-planilla-sticky-2">Cédula</th>
                                    <th class="cap-planilla-asistencia-col" title="Asistencia al nivel">Asistencia</th>
                                    <?php foreach ($tareasCapNivel as $tareaCol): ?>
                                        <th class="cap-planilla-tarea-col" title="<?= htmlspecialchars((string)($tareaCol['Titulo'] ?? 'Tarea'), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string)($tareaCol['Titulo'] ?? 'Tarea')) ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($planillaEstudiantesCap as $estudiantePlan): ?>
                                    <?php
                                        $idEstPlan = (int)($estudiantePlan['id_persona'] ?? 0);
                                        $asistEst = (int)($estudiantePlan['asistencias'] ?? 0);
                                        $pctEst = (int)($estudiantePlan['asistencia_pct'] ?? 0);
                                        $pctClass = $pctEst >= 75 ? 'cap-asistencia-alta' : ($pctEst >= 50 ? 'cap-asistencia-media' : 'cap-asistencia-baja');
                                        $tienePendiente = false;
                                        foreach ((array)($estudiantePlan['tareas'] ?? []) as $tEstTmp) {
                                            $entTmp = is_array($tEstTmp['entrega'] ?? null) ? $tEstTmp['entrega'] : null;
                                            if ($entTmp && strtolower(trim((string)($entTmp['Estado_Calificacion'] ?? ''))) !== 'calificada') {
                                                $tienePendiente = true;
                                                break;
                                            }
                                        }
                                        $ministerioEst = trim((string)($estudiantePlan['ministerio'] ?? 'Sin ministerio'));
                                        if ($ministerioEst === '') {
                                            $ministerioEst = 'Sin ministerio';
                                        }
                                    ?>
                                    <tr class="cap-planilla-row" data-ministerio="<?= htmlspecialchars($ministerioEst, ENT_QUOTES, 'UTF-8') ?>" data-tiene-pendiente="<?= $tienePendiente ? '1' : '0' ?>">
                                        <td class="cap-planilla-sticky cap-planilla-sticky-1"><?= htmlspecialchars((string)($estudiantePlan['nombre'] ?? '')) ?></td>
                                        <td class="cap-planilla-sticky cap-planilla-sticky-2"><?= htmlspecialchars((string)($estudiantePlan['cedula'] ?? '')) ?></td>
                                        <td class="cap-planilla-asistencia-col">
                                            <span class="cap-asistencia-resumen <?= $pctClass ?>"><?= $asistEst ?>/<?= (int)$totalClasesCap ?></span>
                                            <small class="cap-asistencia-pct"><?= $pctEst ?>%</small>
                                        </td>
                                        <?php foreach ((array)($estudiantePlan['tareas'] ?? []) as $tareaEst): ?>
                                            <?php
                                                $entregaEst = is_array($tareaEst['entrega'] ?? null) ? $tareaEst['entrega'] : null;
                                                $idEntregaEst = $entregaEst ? (int)($entregaEst['Id_Entrega'] ?? 0) : 0;
                                                $estadoEnt = strtolower(trim((string)($entregaEst['Estado_Calificacion'] ?? '')));
                                                $calificada = $estadoEnt === 'calificada';
                                                $nombreArchivoEst = $entregaEst ? (string)($entregaEst['Nombre_Archivo'] ?? '') : '';
                                                $urlArchivoEst = $nombreArchivoEst !== ''
                                                    ? (rtrim(PUBLIC_URL, '/') . '/uploads/material_hub_tareas/' . rawurlencode($clave) . '/' . rawurlencode($nombreArchivoEst))
                                                    : '';
                                            ?>
                                            <td class="cap-planilla-celda-tarea<?= $calificada ? ' is-calificada' : ($entregaEst ? ' is-pendiente' : '') ?>">
                                                <?php if (!$entregaEst): ?>
                                                    <span class="cap-planilla-sin-entrega">Sin entrega</span>
                                                <?php else: ?>
                                                    <div class="cap-planilla-calif-cell" data-id-entrega="<?= $idEntregaEst ?>">
                                                        <?php if ($urlArchivoEst !== ''): ?>
                                                            <a class="cap-planilla-archivo-link" href="<?= htmlspecialchars($urlArchivoEst, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" title="Ver entrega"><i class="bi bi-paperclip"></i></a>
                                                        <?php endif; ?>
                                                        <input type="number" class="form-control form-control-sm cap-planilla-nota-input" min="0" max="5" step="0.1" value="<?= htmlspecialchars((string)($entregaEst['Nota'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="0-5">
                                                        <input type="text" class="form-control form-control-sm cap-planilla-retro-input" maxlength="120" value="<?= htmlspecialchars((string)($entregaEst['Retroalimentacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Retroalimentación">
                                                        <button type="button" class="btn btn-xs btn-success cap-planilla-guardar-btn" data-id-entrega="<?= $idEntregaEst ?>">Guardar</button>
                                                        <span class="cap-planilla-estado-msg" aria-live="polite"></span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="cap-planilla-ayuda">Escribe la nota (0–5) y pulsa <strong>Guardar</strong> en cada celda. La asistencia corresponde al nivel <?= (int)$capNivelVista ?> (<?= (int)$totalClasesCap ?> clases).</p>
                    <script>
                    (function() {
                        var tabla = document.getElementById('cap-planilla-calificaciones');
                        if (!tabla) return;
                        var urlCalificar = <?= json_encode(rtrim(PUBLIC_URL, '/') . '/index.php?url=home/calificar-tarea-entrega-cap') ?>;
                        var nivelCap = <?= (int)$capNivelVista ?>;
                        var moduloCap = <?= (int)$capModuloVistaActual ?>;

                        function escaparCsvCap(valor) {
                            var txt = String(valor == null ? '' : valor).replace(/\r?\n/g, ' ').trim();
                            if (/[;"\r\n]/.test(txt)) {
                                return '"' + txt.replace(/"/g, '""') + '"';
                            }
                            return txt;
                        }

                        function textoCeldaTareaExport(celda) {
                            if (!celda) {
                                return 'Sin entrega';
                            }
                            if (celda.querySelector('.cap-planilla-sin-entrega')) {
                                return 'Sin entrega';
                            }
                            var notaInput = celda.querySelector('.cap-planilla-nota-input');
                            var retroInput = celda.querySelector('.cap-planilla-retro-input');
                            var nota = notaInput ? String(notaInput.value || '').trim() : '';
                            var retro = retroInput ? String(retroInput.value || '').trim() : '';
                            var estado = celda.classList.contains('is-calificada') ? 'Calificada' : 'Pendiente';
                            var partes = ['Nota: ' + (nota !== '' ? nota : '—'), estado];
                            if (retro !== '') {
                                partes.push(retro);
                            }
                            return partes.join(' | ');
                        }

                        function exportarPlanillaExcel() {
                            if (!tabla) {
                                return;
                            }

                            var theadRow = tabla.querySelector('thead tr');
                            var headers = ['Estudiante', 'Cédula', 'Ministerio', 'Asistencia (clases)', 'Asistencia %'];
                            if (theadRow) {
                                theadRow.querySelectorAll('th.cap-planilla-tarea-col').forEach(function(th) {
                                    headers.push(String(th.textContent || '').trim() || 'Tarea');
                                });
                            }

                            var filasVisibles = Array.from(tabla.querySelectorAll('.cap-planilla-row')).filter(function(fila) {
                                return fila.style.display !== 'none';
                            });

                            if (!filasVisibles.length) {
                                window.alert('No hay filas visibles para exportar. Ajusta el filtro.');
                                return;
                            }

                            var lineas = [];
                            lineas.push(['Reporte', 'Capacitación Destino — Planilla de calificaciones'].map(escaparCsvCap).join(';'));
                            lineas.push(['Nivel', String(nivelCap)].map(escaparCsvCap).join(';'));
                            lineas.push(['Módulo', String(moduloCap)].map(escaparCsvCap).join(';'));
                            lineas.push(['Generado', new Date().toLocaleString('es-CO')].map(escaparCsvCap).join(';'));
                            lineas.push('');
                            lineas.push(headers.map(escaparCsvCap).join(';'));

                            filasVisibles.forEach(function(fila) {
                                var celdas = fila.querySelectorAll('td');
                                var celdaAsist = celdas[2] || null;
                                var asistResumen = celdaAsist ? celdaAsist.querySelector('.cap-asistencia-resumen') : null;
                                var asistPct = celdaAsist ? celdaAsist.querySelector('.cap-asistencia-pct') : null;
                                var row = [
                                    celdas[0] ? String(celdas[0].textContent || '').trim() : '',
                                    celdas[1] ? String(celdas[1].textContent || '').trim() : '',
                                    String(fila.getAttribute('data-ministerio') || ''),
                                    asistResumen ? String(asistResumen.textContent || '').trim() : '',
                                    asistPct ? String(asistPct.textContent || '').trim() : ''
                                ];
                                fila.querySelectorAll('td.cap-planilla-celda-tarea').forEach(function(celdaTarea) {
                                    row.push(textoCeldaTareaExport(celdaTarea));
                                });
                                lineas.push(row.map(escaparCsvCap).join(';'));
                            });

                            var blob = new Blob(['\uFEFF' + lineas.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
                            var urlBlob = URL.createObjectURL(blob);
                            var enlace = document.createElement('a');
                            var fecha = new Date();
                            var stamp = fecha.getFullYear()
                                + String(fecha.getMonth() + 1).padStart(2, '0')
                                + String(fecha.getDate()).padStart(2, '0');
                            enlace.href = urlBlob;
                            enlace.download = 'cap_destino_n' + nivelCap + '_m' + moduloCap + '_planilla_' + stamp + '.csv';
                            document.body.appendChild(enlace);
                            enlace.click();
                            document.body.removeChild(enlace);
                            setTimeout(function() { URL.revokeObjectURL(urlBlob); }, 500);
                        }

                        function filtrarPlanilla() {
                            var minSel = document.getElementById('cap-planilla-filtro-ministerio');
                            var soloPend = document.getElementById('cap-planilla-solo-pendientes');
                            var valorMin = minSel ? String(minSel.value || '') : '';
                            var soloPendiente = soloPend ? soloPend.checked : false;
                            tabla.querySelectorAll('.cap-planilla-row').forEach(function(fila) {
                                var minFila = String(fila.getAttribute('data-ministerio') || '');
                                var tienePend = String(fila.getAttribute('data-tiene-pendiente') || '0') === '1';
                                var okMin = valorMin === '' || minFila === valorMin;
                                var okPend = !soloPendiente || tienePend;
                                fila.style.display = (okMin && okPend) ? '' : 'none';
                            });
                        }

                        var filtroMin = document.getElementById('cap-planilla-filtro-ministerio');
                        var chkPend = document.getElementById('cap-planilla-solo-pendientes');
                        if (filtroMin) filtroMin.addEventListener('change', filtrarPlanilla);
                        if (chkPend) chkPend.addEventListener('change', filtrarPlanilla);

                        var btnExportExcel = document.getElementById('cap-planilla-export-excel');
                        if (btnExportExcel) {
                            btnExportExcel.addEventListener('click', exportarPlanillaExcel);
                        }

                        tabla.addEventListener('click', function(e) {
                            var btn = e.target && e.target.closest ? e.target.closest('.cap-planilla-guardar-btn') : null;
                            if (!btn) return;
                            var celda = btn.closest('.cap-planilla-calif-cell');
                            if (!celda) return;
                            var idEntrega = parseInt(String(btn.getAttribute('data-id-entrega') || celda.getAttribute('data-id-entrega') || '0'), 10);
                            if (idEntrega <= 0) return;
                            var notaInput = celda.querySelector('.cap-planilla-nota-input');
                            var retroInput = celda.querySelector('.cap-planilla-retro-input');
                            var msg = celda.querySelector('.cap-planilla-estado-msg');
                            var fd = new FormData();
                            fd.append('id_entrega', String(idEntrega));
                            fd.append('nota_entrega', notaInput ? String(notaInput.value || '') : '');
                            fd.append('retroalimentacion_entrega', retroInput ? String(retroInput.value || '') : '');
                            fd.append('nivel', String(nivelCap));
                            fd.append('modulo_numero', String(moduloCap));
                            btn.disabled = true;
                            if (msg) { msg.textContent = 'Guardando…'; msg.className = 'cap-planilla-estado-msg is-saving'; }
                            fetch(urlCalificar, { method: 'POST', body: fd, credentials: 'same-origin' })
                                .then(function(r) { return r.json(); })
                                .then(function(data) {
                                    btn.disabled = false;
                                    if (!data || data.ok !== true) {
                                        throw new Error((data && data.error) ? data.error : 'No se pudo guardar.');
                                    }
                                    if (msg) { msg.textContent = 'Guardado'; msg.className = 'cap-planilla-estado-msg is-ok'; }
                                    var td = celda.closest('td');
                                    if (td) {
                                        td.classList.remove('is-pendiente');
                                        td.classList.add('is-calificada');
                                    }
                                    var fila = celda.closest('.cap-planilla-row');
                                    if (fila) {
                                        var quedanPend = false;
                                        fila.querySelectorAll('td.is-pendiente').forEach(function() { quedanPend = true; });
                                        if (!quedanPend) fila.setAttribute('data-tiene-pendiente', '0');
                                    }
                                })
                                .catch(function(err) {
                                    btn.disabled = false;
                                    if (msg) { msg.textContent = err.message || 'Error'; msg.className = 'cap-planilla-estado-msg is-error'; }
                                });
                        });
                    })();
                    </script>
                <?php elseif (empty($tareasCapNivel)): ?>
                    <div class="alert alert-info" style="margin:0;">Crea tareas en la sección <strong>Tareas</strong> para ver la planilla de calificaciones.</div>
                <?php else: ?>
                    <div class="alert alert-info" style="margin:0;">No hay inscritos en este nivel para mostrar la planilla.</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php elseif ($modoSeleccionNivelCap && !$usaFlujoCapHub): ?>
<div class="cap-entry-grid">
    <article class="cap-entry-card js-open-cap-modal" data-target="profesor" role="button" tabindex="0" aria-label="Abrir Material profesor">
        <h4>Material profesor</h4>
        <p>Ver por niveles y subtarjetas por módulo el contenido exclusivo para profesor.</p>
    </article>
    <article class="cap-entry-card js-open-cap-modal" data-target="clase" role="button" tabindex="0" aria-label="Abrir Material clase">
        <h4>Material clase</h4>
        <p>Ver por niveles y subtarjetas por módulo todo el contenido de clase en pantalla grande.</p>
    </article>
</div>
<?php endif; ?>

<?php if ($esCapacitacionDestino): ?>
<div id="cap-inline-panel" class="cap-panel" aria-hidden="true">
<?php endif; ?>
<?php endif; ?>

<div id="cap-material-panel" class="card cap-main-section<?= ($esCapacitacionDestino && !$mostrarMaterialCap) ? ' is-hidden' : '' ?>" style="padding:14px;">
    <h3 style="margin-top:0;">Módulos de material</h3>

    <?php if ($esDiscipuloCapDestino && !$usaFlujoCapHub): ?>
        <div class="alert alert-info" style="margin-bottom:12px;">
            Modo discípulo: aquí solo ves tus accesos activos de hoy.
        </div>

        <?php if (!empty($accesosDiscipuloCapDestino)): ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:10px;">
                <?php foreach ($accesosDiscipuloCapDestino as $acceso): ?>
                    <div style="border:1px solid #dbe6f5;border-radius:10px;padding:12px;background:#fff;">
                        <div style="font-weight:700;color:#2f73b7;">Nivel <?= (int)($acceso['nivel'] ?? 0) ?> · Módulo <?= (int)($acceso['modulo'] ?? 0) ?></div>
                        <div style="font-size:13px;color:#445b78;margin:4px 0 10px 0;">Lección: <?= htmlspecialchars((string)($acceso['leccion'] ?? 'Sin lección')) ?></div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars((string)($acceso['url_evaluacion'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>">Ir a evaluación</a>
                            <?php if (trim((string)($acceso['url_clase'] ?? '')) !== ''): ?>
                                <a class="btn btn-sm btn-success" href="<?= htmlspecialchars((string)$acceso['url_clase'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Entrar a clase</a>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-secondary" disabled>Sin link de clase</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="margin:0; color:#666;">
                <?php if ($mensajeRestriccionDiscipuloMaterial !== ''): ?>
                    <?= htmlspecialchars($mensajeRestriccionDiscipuloMaterial) ?>
                <?php else: ?>
                    No hay accesos para mostrar. Si ya estás inscrito, valida fechas activas y que el líder haya configurado el link de clase en Conexiones.
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($accesosDiscipuloCapDestino)): ?>
            <div class="cap-academico-panel" style="margin-top:12px;">
                <div class="cap-academico-head">
                    <strong style="color:#2b4f79;">Tareas para discípulos</strong>
                    <small style="color:#5a6f8d;">Sube PDF, videos, imágenes y otros archivos de tu tarea</small>
                </div>

                <?php foreach ($accesosDiscipuloCapDestino as $accesoDisc): ?>
                    <?php
                        $nivelDisc = (int)($accesoDisc['nivel'] ?? 0);
                        $moduloDisc = (int)($accesoDisc['modulo'] ?? 0);
                        $tareasModuloDisc = (array)($tareasDiscipuloCap[$nivelDisc . '_' . $moduloDisc] ?? []);
                    ?>
                    <details style="margin-bottom:8px;" open>
                        <summary class="btn btn-sm btn-secondary" style="cursor:pointer;">Nivel <?= $nivelDisc ?> · Módulo <?= $moduloDisc ?> (<?= count($tareasModuloDisc) ?> tarea(s))</summary>
                        <div style="margin-top:8px;">
                            <?php if (!empty($tareasModuloDisc)): ?>
                                <?php foreach ($tareasModuloDisc as $tareaDisc): ?>
                                    <div class="cap-tarea-card">
                                        <h4 class="cap-tarea-title"><?= htmlspecialchars((string)($tareaDisc['Titulo'] ?? 'Tarea')) ?></h4>
                                        <div class="cap-tarea-meta">
                                            <span>Límite: <?= htmlspecialchars((string)($tareaDisc['Fecha_Limite'] ?? 'Sin fecha')) ?></span>
                                            <span>Tus archivos: <?= (int)($tareaDisc['total_entregas_usuario'] ?? 0) ?></span>
                                        </div>
                                        <?php if (trim((string)($tareaDisc['Descripcion'] ?? '')) !== ''): ?>
                                            <div style="font-size:12px; color:#4a6283; margin-bottom:8px;"><?= htmlspecialchars((string)$tareaDisc['Descripcion']) ?></div>
                                        <?php endif; ?>

                                        <?php if ($puedeSubirTareas): ?>
                                            <?php $totalEntregasDisc = (int)($tareaDisc['total_entregas_usuario'] ?? 0); ?>
                                            <?php if ($totalEntregasDisc > 0): ?>
                                                <div class="alert alert-success cap-tarea-entregada-msg" style="margin:8px 0;">
                                                    <i class="bi bi-check-circle"></i> Ya entregaste esta tarea. No puedes subir más archivos.
                                                </div>
                                            <?php else: ?>
                                            <form method="POST" enctype="multipart/form-data" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" class="cap-tarea-upload-form">
                                                <input type="hidden" name="accion" value="subir_tarea_entrega">
                                                <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                                                <input type="hidden" name="id_tarea" value="<?= (int)($tareaDisc['Id_Tarea'] ?? 0) ?>">
                                                <input type="hidden" name="nivel" value="<?= $nivelDisc ?>">
                                                <input type="hidden" name="modulo_numero" value="<?= $moduloDisc ?>">
                                                <input type="hidden" name="contexto_nivel" value="<?= $nivelDisc ?>">
                                                <input type="hidden" name="contexto_modulo" value="<?= $moduloDisc ?>">
                                                <div>
                                                    <label style="font-size:12px;">Comentario</label>
                                                    <input type="text" name="comentario_entrega" class="form-control" maxlength="500" placeholder="Comentario opcional de tu entrega">
                                                </div>
                                                <div>
                                                    <label style="font-size:12px;">Archivos (varios a la vez)</label>
                                                    <input type="file" name="tarea_archivos[]" class="form-control" multiple required accept="<?= htmlspecialchars($capTareaAcceptArchivos, ENT_QUOTES, 'UTF-8') ?>">
                                                    <small class="cap-tarea-upload-hint">Imágenes, audio, video, PDF, Office y más. Máx. 100MB por archivo. Solo una entrega por tarea.</small>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-primary">Subir tarea</button>
                                            </form>
                                            <?php endif; ?>
                                            <?php
                                                $entregas_usuario = (array)($tareaDisc['entregas_usuario'] ?? []);
                                                $clave_modulo_tarea = $clave;
                                                include VIEWS . '/home/partials/cap_entregas_usuario_tarea.php';
                                            ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info" style="margin:0;">No hay tareas publicadas para este módulo por ahora.</div>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>

    <?php if ($aplicarRestriccionDiscipuloMaterial): ?>
        <?php if ($mensajeRestriccionDiscipuloMaterial !== ''): ?>
            <div class="alert alert-warning" style="margin-bottom:10px;">
                <?= htmlspecialchars($mensajeRestriccionDiscipuloMaterial) ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info" style="margin-bottom:10px;">
                Vista discípulo activa para la fecha <?= htmlspecialchars($fechaRestriccionDiscipuloMaterial) ?>:
                solo se muestra tu lección activa y su enlace de acceso.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($tieneSubmodulos && (!$usaTarjetasTipoMaterial || $esUniversidadVida)): ?>
        <div class="submodulo-tabs" role="tablist" aria-label="Submódulos de material">
            <button type="button" class="submodulo-tab is-active js-submodulo-tab" data-target="submodulo-panel-clase" role="tab" aria-selected="true">Material clase</button>
            <button type="button" class="submodulo-tab js-submodulo-tab" data-target="submodulo-panel-profesor" role="tab" aria-selected="false">Material profesor</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($temas)): ?>
        <?php
            if ($tieneSubmodulos && $esCapacitacionDestino) {
                $bloques = [];
                foreach ($configCapacitacionDestino as $nivelTmp => $modulosTmp) {
                    if ($capNivelVista > 0 && (int)$nivelTmp !== $capNivelVista) {
                        continue;
                    }

                    if ($aplicarRestriccionDiscipuloMaterial && !isset($clasesActivasRestriccionDiscipulo[(int)$nivelTmp])) {
                        continue;
                    }

                    foreach ((array)$modulosTmp as $moduloTmp) {
                        if ($aplicarRestriccionDiscipuloMaterial) {
                            $modulosPermitidosNivel = array_map('intval', (array)($modulosActivosRestriccionDiscipulo[(int)$nivelTmp] ?? []));
                            if (!in_array((int)$moduloTmp, $modulosPermitidosNivel, true)) {
                                continue;
                            }
                        }

                        $categoriasIteracion = $aplicarRestriccionDiscipuloMaterial ? ['clase'] : ['profesor', 'clase'];
                        foreach ($categoriasIteracion as $categoriaTmp) {
                            $temasBloqueTmp = array_values(array_filter($temas, static function($temaTmp) use ($nivelTmp, $moduloTmp, $categoriaTmp) {
                                $categoriaTemaTmp = strtolower(trim((string)($temaTmp['categoria'] ?? 'clase')));
                                if ($categoriaTemaTmp !== 'profesor') {
                                    $categoriaTemaTmp = 'clase';
                                }

                                return (int)($temaTmp['nivel'] ?? 0) === (int)$nivelTmp
                                    && (int)($temaTmp['modulo_numero'] ?? 0) === (int)$moduloTmp
                                    && $categoriaTemaTmp === $categoriaTmp;
                            }));

                            $bloques[] = [
                                'titulo' => 'Módulo ' . (int)$moduloTmp,
                                'temas' => $temasBloqueTmp,
                                'nivel' => (int)$nivelTmp,
                                'modulo_numero' => (int)$moduloTmp,
                                'categoria' => $categoriaTmp,
                            ];
                        }
                    }
                }
            } elseif ($tieneSubmodulos && $usaTarjetasTipoMaterial) {
                $bloques = [
                    ['titulo' => 'Material clase',    'temas' => $temasClase,    'categoria' => 'clase'],
                    ['titulo' => 'Material profesor', 'temas' => $temasProfesor, 'categoria' => 'profesor'],
                ];
            } else {
                $bloques = $tieneSubmodulos
                    ? [
                        ['titulo' => 'Material clase',    'temas' => $temasClase],
                        ['titulo' => 'Material profesor', 'temas' => $temasProfesor],
                    ]
                    : [
                        ['titulo' => 'Temas', 'temas' => $temas],
                    ];
            }
        ?>

        <?php if ($usaTarjetasTipoMaterial && !$esCapacitacionDestino): ?><div class="<?= $esUniversidadVida ? 'uv-material-grid' : 'cap-destino-grid' ?>"><?php endif; ?>
        <?php if ($modoSeleccionNivelCap): ?>
            <div class="alert alert-info" style="margin-top:10px;">
                Selecciona un nivel para entrar a su vista independiente y ver todos sus módulos.
            </div>
        <?php else: ?>

        <?php
        $bloqueIndex = 0;
        $lastNivelRender = null;
        foreach ($bloques as $bloque):
            if ($esCapacitacionDestino) {
                $nivelActualRender = (int)($bloque['nivel'] ?? 0);
                if ($nivelActualRender !== $lastNivelRender) {
                    // Cerrar sección anterior
                    if ($lastNivelRender !== null) {
                        echo '</div></div>'; // .cap-destino-grid y .cap-nivel-section
                    }
                    // Abrir nueva sección de nivel
                    echo '<div class="cap-nivel-section" data-modulo-grupo="' . (int)$nivelActualRender . '">';
                    echo '<div class="cap-nivel-header"><h4 class="cap-nivel-label">Nivel ' . (int)$nivelActualRender . '</h4></div>';

                    echo '<div class="cap-destino-grid">';
                    $lastNivelRender = $nivelActualRender;
                }
            }
        ?>
            <?php
                $tituloBloque = (string)($bloque['titulo'] ?? 'Temas');
                $categoriaBloque = strtolower(trim((string)($bloque['categoria'] ?? 'general')));
                if ($categoriaBloque === '') {
                    $categoriaBloque = 'general';
                }

                if ($esCapacitacionDestino) {
                    $tituloBloque = 'Módulo ' . (int)($bloque['modulo_numero'] ?? 0)
                        . ' - Material ' . ($categoriaBloque === 'profesor' ? 'profesor' : 'clase');
                }

                $leccionEvaluacionModulo = 'Sin lección';
                foreach ((array)($bloque['temas'] ?? []) as $temaEvalTmp) {
                    $leccionEvalTmp = trim((string)($temaEvalTmp['leccion'] ?? ''));
                    if ($leccionEvalTmp !== '') {
                        $leccionEvaluacionModulo = $leccionEvalTmp;
                        break;
                    }
                }

                $rutaEvaluacionModulo = PUBLIC_URL
                    . '?url=programas/evaluaciones&from_material=1'
                    . '&nivel=' . (int)($bloque['nivel'] ?? 0)
                    . '&modulo=' . (int)($bloque['modulo_numero'] ?? 0)
                    . '&leccion=' . rawurlencode($leccionEvaluacionModulo);

                $claseCssBloque = 'submodulo-wrap';
                $panelIdBloque = 'submodulo-panel-' . $bloqueIndex;

                // Para cap destino usamos la categoría directamente; para otros módulos miramos el título
                if ($esCapacitacionDestino) {
                    if ($categoriaBloque === 'profesor') {
                        $claseCssBloque .= ' submodulo-profesor';
                    } else {
                        $claseCssBloque .= ' submodulo-clase';
                    }
                } else {
                    if (stripos($tituloBloque, 'profesor') !== false) {
                        $claseCssBloque .= ' submodulo-profesor';
                        if (!$usaTarjetasTipoMaterial || $esUniversidadVida) {
                            $panelIdBloque = 'submodulo-panel-profesor';
                        }
                    } elseif (stripos($tituloBloque, 'clase') !== false) {
                        $claseCssBloque .= ' submodulo-clase';
                        if (!$usaTarjetasTipoMaterial || $esUniversidadVida) {
                            $panelIdBloque = 'submodulo-panel-clase';
                        }
                    }
                }
                $totalTemasBloque = count((array)($bloque['temas'] ?? []));

                $nivelBloqueProf = (int)($bloque['nivel'] ?? 0);
                $moduloBloqueProf = (int)($bloque['modulo_numero'] ?? 0);
                $keyProfesorBloque = $nivelBloqueProf . '_' . $moduloBloqueProf;
                $configProfesorBloque = $profesoresModulos[$keyProfesorBloque] ?? [];
                if (is_array($configProfesorBloque)) {
                    $nombreProfesorBloque = trim((string)($configProfesorBloque['profesor_nombre'] ?? ''));
                    $conexionZoomBloque = trim((string)($configProfesorBloque['conexion_zoom_url'] ?? ''));
                } else {
                    $nombreProfesorBloque = trim((string)$configProfesorBloque);
                    $conexionZoomBloque = '';
                }
                $formIdProfesorBloque = 'form-prof-bloque-' . $bloqueIndex;

                if ($tieneSubmodulos && (!$usaTarjetasTipoMaterial || $esUniversidadVida) && $panelIdBloque === 'submodulo-panel-profesor') {
                    $claseCssBloque .= ' is-hidden';
                }
            ?>

            <?php
                if ($usaTarjetasTipoMaterial && $esCapacitacionDestino) {
                    $claseCssBloque .= ' is-hidden';
                }
            ?>

            <div
                id="<?= htmlspecialchars($panelIdBloque, ENT_QUOTES, 'UTF-8') ?>"
                class="<?= htmlspecialchars($claseCssBloque, ENT_QUOTES, 'UTF-8') ?> js-cap-block"
                data-cap-categoria="<?= htmlspecialchars($categoriaBloque, ENT_QUOTES, 'UTF-8') ?>"
                data-cap-nivel="<?= (int)$nivelBloqueProf ?>"
                data-cap-modulo="<?= (int)$moduloBloqueProf ?>"
                data-cap-titulo="<?= htmlspecialchars($tituloBloque, ENT_QUOTES, 'UTF-8') ?>"
                data-cap-total="<?= (int)$totalTemasBloque ?>"
                role="tabpanel">
                <div class="submodulo-head">
                    <h4 class="submodulo-title">
                        <?= htmlspecialchars($tituloBloque) ?>
                        <?php if ($esCapacitacionDestino): ?>
                            <div class="cap-modulo-carpeta">Carpeta del módulo</div>
                        <?php endif; ?>
                    </h4>
                    <div class="cap-modulo-head-actions">
                        <span class="submodulo-meta"><?= (int)$totalTemasBloque ?> tema(s)</span>
                        <?php if ($esCapacitacionDestino && $categoriaBloque === 'clase' && !$esVistaMaestro): ?>
                            <a class="cap-modulo-eval-link" href="<?= htmlspecialchars($rutaEvaluacionModulo, ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-journal-check"></i> Evaluaciones
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($esCapacitacionDestino): ?>
                    <div class="cap-modulo-profesor-row" style="padding:8px 10px; border-bottom:1px solid #e6eef9; background:#f8fbff;">
                        <i class="bi bi-person-badge" style="font-size:13px;"></i>
                        <span>Profesor de este módulo:</span>
                        <span class="cap-modulo-profesor-nombre">
                            <?= $nombreProfesorBloque !== '' ? htmlspecialchars($nombreProfesorBloque) : '<em style="color:#9aabbd;">Sin asignar</em>' ?>
                        </span>
                        <?php if ($puedeGestionar): ?>
                            <button type="button" class="btn btn-sm btn-secondary js-toggle-profesor-form"
                                data-target="<?= htmlspecialchars($formIdProfesorBloque, ENT_QUOTES, 'UTF-8') ?>"
                                style="font-size:11px; padding:2px 8px;">Editar</button>
                        <?php endif; ?>
                    </div>
                    <div class="cap-modulo-profesor-row" style="padding:8px 10px; border-bottom:1px solid #e6eef9; background:#f8fbff;">
                        <i class="bi bi-link-45deg" style="font-size:13px;"></i>
                        <span><strong>Conexiones:</strong></span>
                        <span class="cap-modulo-profesor-nombre">
                            <?php if ($conexionZoomBloque !== ''): ?>
                                <a href="<?= htmlspecialchars($conexionZoomBloque, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Link de Zoom</a>
                            <?php else: ?>
                                <em style="color:#9aabbd;">Sin enlace de Zoom</em>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if ($puedeGestionar): ?>
                        <form id="<?= htmlspecialchars($formIdProfesorBloque, ENT_QUOTES, 'UTF-8') ?>" method="POST"
                            action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>"
                            class="cap-modulo-profesor-form" style="margin:8px 10px 0 10px;">
                            <input type="hidden" name="accion" value="guardar_profesor_modulo">
                            <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                            <input type="hidden" name="nivel" value="<?= (int)$nivelBloqueProf ?>">
                            <input type="hidden" name="modulo_numero" value="<?= (int)$moduloBloqueProf ?>">
                            <input type="hidden" name="contexto_nivel" value="<?= (int)$nivelBloqueProf ?>">
                            <input type="hidden" name="contexto_modulo" value="<?= (int)$moduloBloqueProf ?>">
                            <input type="hidden" name="contexto_categoria" value="<?= htmlspecialchars($categoriaBloque, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="text" name="profesor_nombre" class="form-control" style="font-size:12px; padding:4px 8px; flex:1; min-width:170px;"
                                placeholder="Nombre del profesor" maxlength="255"
                                value="<?= htmlspecialchars($nombreProfesorBloque, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="url" name="conexion_zoom_url" class="form-control" style="font-size:12px; padding:4px 8px; flex:1; min-width:220px;"
                                placeholder="https://zoom.us/j/..."
                                maxlength="1024"
                                value="<?= htmlspecialchars($conexionZoomBloque, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-sm btn-primary" style="font-size:12px; padding:4px 10px;">Guardar</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="submodulo-body">
                    <div class="table-container" style="margin-bottom:0;">
                        <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-titulo">Título</th>
                            <th class="col-descripcion">Lección</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $temasBloqueRender = (array)($bloque['temas'] ?? []);
                        if ($esCapacitacionDestino && !empty($temasBloqueRender)) {
                            usort($temasBloqueRender, static function(array $a, array $b) {
                                $leccionA = trim((string)($a['leccion'] ?? ''));
                                $leccionB = trim((string)($b['leccion'] ?? ''));

                                $numA = null;
                                $numB = null;
                                if (preg_match('/\d+/', $leccionA, $mA) === 1) {
                                    $numA = (int)$mA[0];
                                }
                                if (preg_match('/\d+/', $leccionB, $mB) === 1) {
                                    $numB = (int)$mB[0];
                                }

                                // Lecciones con número primero (1,2,3...), sin número al final.
                                if ($numA === null && $numB !== null) {
                                    return 1;
                                }
                                if ($numA !== null && $numB === null) {
                                    return -1;
                                }
                                if ($numA !== null && $numB !== null) {
                                    $cmpNum = $numA <=> $numB;
                                    if ($cmpNum !== 0) {
                                        return $cmpNum;
                                    }
                                }

                                $tsA = (int)($a['creado_ts'] ?? 0);
                                $tsB = (int)($b['creado_ts'] ?? 0);
                                return $tsB <=> $tsA;
                            });
                        }
                        ?>
                        <?php if (!empty($temasBloqueRender)): ?>
                            <?php foreach ($temasBloqueRender as $index => $tema): ?>
                                <?php
                                $temaId = 'tema-' . $bloqueIndex . '-' . $index;
                                $temaEditId = 'tema-edit-' . $bloqueIndex . '-' . $index;
                                $temaAgregarArchivosId = 'tema-add-files-' . $bloqueIndex . '-' . $index;
                                $archivosTema = (array)($tema['archivos'] ?? []);
                                $categoriaTema = strtolower(trim((string)($tema['categoria'] ?? 'general')));
                                $imagenesTema = [];
                                foreach ($archivosTema as $archivoTemaGaleria) {
                                    $nombreGaleria = (string)($archivoTemaGaleria['nombre'] ?? '');
                                    $extGaleria = strtolower((string)pathinfo($nombreGaleria, PATHINFO_EXTENSION));
                                    if (!in_array($extGaleria, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                                        continue;
                                    }

                                    $imagenesTema[] = [
                                        'src' => rtrim(PUBLIC_URL, '/') . '/uploads/material_hub/' . rawurlencode($clave) . '/' . rawurlencode($nombreGaleria),
                                        'nombre' => $nombreGaleria,
                                        'abrir' => (string)($archivoTemaGaleria['url'] ?? '#'),
                                    ];
                                }
                                $imagenesTemaJson = json_encode($imagenesTema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                if ($imagenesTemaJson === false) {
                                    $imagenesTemaJson = '[]';
                                }
                                $leccionTemaData = trim((string)($tema['leccion'] ?? ''));
                                if ($leccionTemaData === '') {
                                    $leccionTemaData = 'Sin lección';
                                }
                                ?>
                                <tr class="js-tema-row"
                                    data-tema-key="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>"
                                    data-lote-id="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-cap-nivel="<?= (int)$nivelBloqueProf ?>"
                                    data-cap-modulo="<?= (int)$moduloBloqueProf ?>"
                                    data-cap-categoria="<?= htmlspecialchars($categoriaBloque, ENT_QUOTES, 'UTF-8') ?>"
                                    data-cap-leccion="<?= htmlspecialchars($leccionTemaData, ENT_QUOTES, 'UTF-8') ?>">
                                    <td class="col-titulo" title="<?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material'), ENT_QUOTES, 'UTF-8') ?>">
                                        <strong><?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material')) ?></strong>
                                        <?php if ($esCapacitacionDestino && trim((string)($tema['leccion'] ?? '')) !== ''): ?>
                                            <div style="margin-top:4px;"><small style="color:#5b6f8d; font-weight:600;"><?= htmlspecialchars((string)$tema['leccion']) ?></small></div>
                                        <?php endif; ?>
                                    </td>
                                    <?php $leccionTema = trim((string)($tema['leccion'] ?? '')); ?>
                                    <?php
                                    $leccionNumero = '';
                                    if ($leccionTema !== '' && preg_match('/\d+/', $leccionTema, $mLeccion) === 1) {
                                        $leccionNumero = (string)$mLeccion[0];
                                    }
                                    if ($leccionNumero === '') {
                                        $leccionNumero = '—';
                                    }
                                    ?>
                                    <td class="col-descripcion" title="<?= htmlspecialchars($leccionTema !== '' ? $leccionTema : 'Sin lección', ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="descripcion-cell">
                                            <span class="descripcion-preview"><?= htmlspecialchars($leccionNumero) ?></span>
                                        </span>
                                    </td>
                                    <td class="col-acciones">
                                        <div class="tema-acciones">
                                            <div class="tema-acciones-row">
                                                <button type="button" class="btn btn-sm btn-secondary js-toggle-tema" data-target="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>">Ver archivos</button>
                                                <button type="button" class="btn btn-sm btn-info js-ver-vistas" data-lote="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">Ver quién vio</button>
                                                <?php if (!empty($imagenesTema)): ?>
                                                    <button type="button" class="btn btn-sm btn-warning js-abrir-galeria-tema"
                                                        data-tema="<?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material'), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-images='<?= htmlspecialchars($imagenesTemaJson, ENT_QUOTES, 'UTF-8') ?>'>Presentar</button>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($puedeGestionar): ?>
                                                <div class="tema-acciones-row">
                                                    <?php if ($puedeSubirMaterial): ?>
                                                        <button type="button" class="btn btn-sm btn-success js-toggle-agregar-archivos" data-target="<?= htmlspecialchars($temaAgregarArchivosId, ENT_QUOTES, 'UTF-8') ?>">Agregar archivos</button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-primary js-toggle-editar-tema" data-target="<?= htmlspecialchars($temaEditId, ENT_QUOTES, 'UTF-8') ?>">Editar</button>
                                                </div>
                                                <div class="tema-acciones-row is-danger">
                                                    <button type="button" class="btn btn-sm btn-danger js-eliminar-tema"
                                                        data-lote="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-titulo="<?= htmlspecialchars((string)($tema['titulo'] ?? 'este tema'), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-contexto-nivel="<?= (int)($tema['nivel'] ?? 0) ?>"
                                                        data-contexto-modulo="<?= (int)($tema['modulo_numero'] ?? 0) ?>"
                                                        data-contexto-categoria="<?= htmlspecialchars($categoriaTema, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-contexto-leccion="<?= htmlspecialchars((string)($tema['leccion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">Eliminar clase</button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <?php if ($puedeGestionar): ?>
                                    <tr id="<?= htmlspecialchars($temaEditId, ENT_QUOTES, 'UTF-8') ?>" data-tema-key="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>" style="display:none; background:#fff9f0;">
                                        <td colspan="3">
                                            <form method="POST" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px; align-items:end;">
                                                <input type="hidden" name="accion" value="editar_tema">
                                                <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                                                <input type="hidden" name="lote_id" value="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_categoria" value="<?= htmlspecialchars($categoriaTema, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_open_lote" value="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_open_panel" value="editar">

                                                <div>
                                                    <label style="font-size:12px; color:#576b86;">Título</label>
                                                    <input type="text" name="titulo" class="form-control" maxlength="255" required value="<?= htmlspecialchars((string)($tema['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>

                                                <div>
                                                    <label style="font-size:12px; color:#576b86;">Descripción</label>
                                                    <input type="text" name="descripcion" class="form-control" value="<?= htmlspecialchars((string)($tema['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>

                                                <?php if ($tieneSubmodulos): ?>
                                                    <div>
                                                        <label style="font-size:12px; color:#576b86;">Submódulo</label>
                                                        <select name="categoria" class="form-control">
                                                            <option value="clase" <?= $categoriaTema === 'clase' ? 'selected' : '' ?>>Material clase</option>
                                                            <option value="profesor" <?= $categoriaTema === 'profesor' ? 'selected' : '' ?>>Material profesor</option>
                                                        </select>
                                                    </div>
                                                <?php else: ?>
                                                    <input type="hidden" name="categoria" value="general">
                                                <?php endif; ?>

                                                <?php if ($esCapacitacionDestino): ?>
                                                    <?php
                                                        $nivelTemaEdit = (int)($tema['nivel'] ?? 0);
                                                        $nivelTemaEdit = in_array($nivelTemaEdit, [1, 2, 3], true) ? $nivelTemaEdit : 1;
                                                        $moduloTemaEdit = (int)($tema['modulo_numero'] ?? 0);
                                                        $leccionTemaEdit = trim((string)($tema['leccion'] ?? ''));
                                                    ?>
                                                    <div>
                                                        <label style="font-size:12px; color:#576b86;">Nivel</label>
                                                        <select name="nivel" class="form-control js-cap-destino-nivel">
                                                            <option value="1" <?= $nivelTemaEdit === 1 ? 'selected' : '' ?>>Nivel 1</option>
                                                            <option value="2" <?= $nivelTemaEdit === 2 ? 'selected' : '' ?>>Nivel 2</option>
                                                            <option value="3" <?= $nivelTemaEdit === 3 ? 'selected' : '' ?>>Nivel 3</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label style="font-size:12px; color:#576b86;">Módulo</label>
                                                        <select name="modulo_numero" class="form-control js-cap-destino-modulo" data-selected="<?= (int)$moduloTemaEdit ?>"></select>
                                                    </div>
                                                    <div>
                                                        <label style="font-size:12px; color:#576b86;">Lección</label>
                                                        <input type="text" name="leccion" class="form-control" maxlength="120" required value="<?= htmlspecialchars($leccionTemaEdit, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: Lección 1">
                                                    </div>
                                                <?php else: ?>
                                                    <input type="hidden" name="nivel" value="0">
                                                    <input type="hidden" name="modulo_numero" value="0">
                                                    <input type="hidden" name="leccion" value="">
                                                <?php endif; ?>

                                                <div>
                                                    <button type="submit" class="btn btn-sm btn-primary">Guardar cambios</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php if ($puedeGestionar && $puedeSubirMaterial): ?>
                                    <tr id="<?= htmlspecialchars($temaAgregarArchivosId, ENT_QUOTES, 'UTF-8') ?>" data-tema-key="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>" style="display:none; background:#eefaf4;">
                                        <td colspan="3">
                                            <form method="POST" enctype="multipart/form-data" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:10px; align-items:end;">
                                                <input type="hidden" name="accion" value="agregar_archivos_tema">
                                                <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                                                <input type="hidden" name="lote_id" value="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_nivel" value="<?= (int)($tema['nivel'] ?? 0) ?>">
                                                <input type="hidden" name="contexto_modulo" value="<?= (int)($tema['modulo_numero'] ?? 0) ?>">
                                                <input type="hidden" name="contexto_categoria" value="<?= htmlspecialchars($categoriaTema, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_leccion" value="<?= htmlspecialchars((string)($tema['leccion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_open_lote" value="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_open_panel" value="agregar">

                                                <div>
                                                    <label style="font-size:12px; color:#576b86;">Agregar más archivos a este tema</label>
                                                    <input type="file" name="material_pdf[]" class="form-control" multiple required>
                                                    <small style="display:block; margin-top:4px; color:#6b7d95;">Puedes subir varios archivos adicionales (máx. 20MB por archivo).</small>
                                                </div>

                                                <div>
                                                    <button type="submit" class="btn btn-sm btn-success">Subir al tema</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <tr id="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>" data-tema-key="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>" style="display:none; background:#f9fbff;">
                                    <td colspan="3">
                                        <?php if (!empty($archivosTema)): ?>
                                            <div style="display:flex; flex-wrap:wrap; gap:12px;">
                                                <?php foreach ($archivosTema as $indexArchivoActual => $archivo): ?>
                                                    <?php
                                                    $nombreArchivo = (string)($archivo['nombre'] ?? '');
                                                    $extArchivo = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
                                                    $urlDirectaArchivo = rtrim(PUBLIC_URL, '/') . '/uploads/material_hub/' . rawurlencode($clave) . '/' . rawurlencode($nombreArchivo);
                                                    $urlVerArchivo = htmlspecialchars((string)($archivo['url'] ?? '#'));
                                                    $esImagen = in_array($extArchivo, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                    $esVideo  = in_array($extArchivo, ['mp4', 'webm', 'mov']);
                                                    $esPdf    = $extArchivo === 'pdf';
                                                    $esOffice = in_array($extArchivo, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true);
                                                    $urlPreviewOffice = '';
                                                    if ($esOffice) {
                                                        $urlAbsolutaPreview = $urlDirectaArchivo;
                                                        if (stripos($urlAbsolutaPreview, 'http://') !== 0 && stripos($urlAbsolutaPreview, 'https://') !== 0) {
                                                            $hostActual = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . (string)($_SERVER['HTTP_HOST'] ?? '');
                                                            $pathPreview = (string)(parse_url($urlAbsolutaPreview, PHP_URL_PATH) ?? $urlAbsolutaPreview);
                                                            $urlAbsolutaPreview = rtrim($hostActual, '/') . '/' . ltrim($pathPreview, '/');
                                                        }

                                                        $hostPreview = strtolower((string)parse_url($urlAbsolutaPreview, PHP_URL_HOST));
                                                        $esHostLocal = in_array($hostPreview, ['localhost', '127.0.0.1', '::1'], true);
                                                        if (!$esHostLocal && preg_match('/^https?:\/\//i', $urlAbsolutaPreview)) {
                                                            $urlPreviewOffice = 'https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode($urlAbsolutaPreview);
                                                        }
                                                    }
                                                    $indexImagenEnTema = 0;
                                                    if ($esImagen) {
                                                        foreach ($archivosTema as $i => $arch) {
                                                            if ($i >= $indexArchivoActual) break;
                                                            $extArch = strtolower((string)pathinfo((string)($arch['nombre'] ?? ''), PATHINFO_EXTENSION));
                                                            if (in_array($extArch, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                                                                $indexImagenEnTema++;
                                                            }
                                                        }
                                                    }
                                                    // Iconos por tipo para archivos no previsualizable
                                                    $iconosExt = ['docx'=>'bi-file-word','doc'=>'bi-file-word','xlsx'=>'bi-file-excel','xls'=>'bi-file-excel','pptx'=>'bi-file-ppt','ppt'=>'bi-file-ppt','zip'=>'bi-file-zip','rar'=>'bi-file-zip','mp3'=>'bi-file-music','wav'=>'bi-file-music'];
                                                    $iconoCls = $iconosExt[$extArchivo] ?? 'bi-file-earmark';
                                                    ?>
                                                    <div style="width:160px; border:1px solid #dce6f5; border-radius:10px; overflow:hidden; background:#fff; display:flex; flex-direction:column; box-shadow:0 1px 4px rgba(30,74,137,0.08);">
                                                        <!-- Zona de preview -->
                                                        <div style="height:140px; background:#f2f7ff; display:flex; align-items:center; justify-content:center; overflow:hidden; position:relative; border-bottom:1px solid #e1eaf8;">
                                                            <?php if ($esImagen): ?>
                                                                <button type="button"
                                                                    class="material-item-preview-btn js-abrir-galeria-desde-archivo"
                                                                    data-tema="<?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material'), ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-images='<?= htmlspecialchars($imagenesTemaJson, ENT_QUOTES, 'UTF-8') ?>'
                                                                    data-index="<?= (int)$indexImagenEnTema ?>"
                                                                    aria-label="Abrir galería de imágenes">
                                                                    <img src="<?= htmlspecialchars($urlDirectaArchivo, ENT_QUOTES, 'UTF-8') ?>"
                                                                         alt="<?= htmlspecialchars($nombreArchivo, ENT_QUOTES, 'UTF-8') ?>">
                                                                </button>
                                                            <?php elseif ($esVideo): ?>
                                                                <video style="width:100%; height:100%; object-fit:cover; display:block;" muted preload="metadata">
                                                                    <source src="<?= htmlspecialchars($urlDirectaArchivo, ENT_QUOTES, 'UTF-8') ?>#t=0.5">
                                                                </video>
                                                                <div style="position:absolute; bottom:6px; right:8px; background:rgba(0,0,0,0.55); border-radius:4px; padding:2px 6px;">
                                                                    <i class="bi bi-play-fill" style="color:#fff; font-size:14px;"></i>
                                                                </div>
                                                            <?php elseif ($esPdf): ?>
                                                                <div style="width:100%; height:100%; position:relative; background:#ffffff;">
                                                                    <iframe
                                                                        src="<?= htmlspecialchars($urlDirectaArchivo, ENT_QUOTES, 'UTF-8') ?>#page=1&view=FitH&toolbar=0&navpanes=0&scrollbar=0"
                                                                        title="Vista previa PDF"
                                                                        loading="lazy"
                                                                        style="width:100%; height:100%; border:0; pointer-events:none; background:#fff;">
                                                                    </iframe>
                                                                    <div style="position:absolute; top:6px; right:6px; background:rgba(0,0,0,0.55); border-radius:4px; padding:2px 6px; color:#fff; font-size:10px; text-transform:uppercase; letter-spacing:.5px;">
                                                                        PDF
                                                                    </div>
                                                                </div>
                                                            <?php elseif ($esOffice && $urlPreviewOffice !== ''): ?>
                                                                <div style="width:100%; height:100%; position:relative; background:#ffffff;">
                                                                    <iframe
                                                                        src="<?= htmlspecialchars($urlPreviewOffice, ENT_QUOTES, 'UTF-8') ?>"
                                                                        title="Vista previa Office"
                                                                        loading="lazy"
                                                                        style="width:100%; height:100%; border:0; pointer-events:none; background:#fff;">
                                                                    </iframe>
                                                                    <div style="position:absolute; top:6px; right:6px; background:rgba(0,0,0,0.55); border-radius:4px; padding:2px 6px; color:#fff; font-size:10px; text-transform:uppercase; letter-spacing:.5px;">
                                                                        <?= htmlspecialchars(strtoupper($extArchivo)) ?>
                                                                    </div>
                                                                </div>
                                                            <?php elseif ($esOffice): ?>
                                                                <div style="display:flex; flex-direction:column; align-items:center; gap:6px; text-align:center; padding:10px;">
                                                                    <i class="bi <?= htmlspecialchars($iconoCls) ?>" style="font-size:48px; color:#5f86b7;"></i>
                                                                    <span style="color:#5f7ea3; font-size:11px; text-transform:uppercase; letter-spacing:1px;"><?= htmlspecialchars(strtoupper($extArchivo)) ?></span>
                                                                    <small style="color:#7c90ac; line-height:1.25;">Vista previa disponible al abrir el archivo.</small>
                                                                </div>
                                                            <?php else: ?>
                                                                <div style="display:flex; flex-direction:column; align-items:center; gap:6px;">
                                                                    <i class="bi <?= htmlspecialchars($iconoCls) ?>" style="font-size:48px; color:#5f86b7;"></i>
                                                                    <span style="color:#5f7ea3; font-size:11px; text-transform:uppercase; letter-spacing:1px;"><?= htmlspecialchars(strtoupper($extArchivo)) ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <!-- Info y acciones -->
                                                        <div style="padding:8px 10px; flex:1; display:flex; flex-direction:column; gap:6px;">
                                                            <div style="font-size:12px; font-weight:600; color:#1e3a5f; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($nombreArchivo, ENT_QUOTES, 'UTF-8') ?>">
                                                                <?= htmlspecialchars($nombreArchivo) ?>
                                                            </div>
                                                            <div style="font-size:11px; color:#8a9bb5;"><?= number_format((float)($archivo['peso_kb'] ?? 0), 1) ?> KB</div>
                                                            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:auto;">
                                                                <?php if ($esImagen): ?>
                                                                    <button type="button" class="btn btn-sm btn-success js-abrir-galeria-desde-archivo"
                                                                        data-tema="<?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material'), ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-images='<?= htmlspecialchars($imagenesTemaJson, ENT_QUOTES, 'UTF-8') ?>'
                                                                        data-index="<?= (int)$indexImagenEnTema ?>"
                                                                        style="font-size:11px; padding:3px 8px;">Abrir</button>
                                                                <?php else: ?>
                                                                    <a href="<?= $urlVerArchivo ?>" target="_blank" class="btn btn-sm btn-success" style="font-size:11px; padding:3px 8px;">Abrir</a>
                                                                <?php endif; ?>
                                                                <?php if ($puedeGestionar): ?>
                                                                    <form method="POST" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" onsubmit="return confirm('¿Eliminar este archivo?');" style="margin:0;">
                                                                        <input type="hidden" name="accion" value="eliminar">
                                                                        <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                                                                        <input type="hidden" name="archivo" value="<?= htmlspecialchars($nombreArchivo, ENT_QUOTES, 'UTF-8') ?>">
                                                                        <input type="hidden" name="contexto_nivel" value="<?= (int)($tema['nivel'] ?? 0) ?>">
                                                                        <input type="hidden" name="contexto_modulo" value="<?= (int)($tema['modulo_numero'] ?? 0) ?>">
                                                                        <input type="hidden" name="contexto_categoria" value="<?= htmlspecialchars($categoriaTema, ENT_QUOTES, 'UTF-8') ?>">
                                                                        <input type="hidden" name="contexto_leccion" value="<?= htmlspecialchars((string)($tema['leccion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                                        <input type="hidden" name="contexto_open_lote" value="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                                        <input type="hidden" name="contexto_open_panel" value="archivos">
                                                                        <button type="submit" class="btn btn-sm btn-danger" style="font-size:11px; padding:3px 8px;">Eliminar</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color:#6b7d95;">Este tema no tiene archivos.</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center; color:#6b7d95;">No hay temas cargados en este submódulo.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php $bloqueIndex++; endforeach; ?>
        <?php if ($esCapacitacionDestino && $lastNivelRender !== null): ?>
            </div></div><?php // cierre .cap-destino-grid y .cap-nivel-section de la última sección ?>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($usaTarjetasTipoMaterial && !$esCapacitacionDestino): ?></div><?php endif; ?>
    <?php else: ?>
        <?php if ($aplicarRestriccionDiscipuloMaterial && $mensajeRestriccionDiscipuloMaterial !== ''): ?>
            <p style="margin:0; color:#666;"><?= htmlspecialchars($mensajeRestriccionDiscipuloMaterial) ?></p>
        <?php else: ?>
            <p style="margin:0; color:#666;">No hay temas cargados en este módulo.</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php if ($esCapacitacionDestino && $usaTarjetasTipoMaterial && (!$esDiscipuloCapDestino || $usaFlujoCapHub)): ?>
</div>
<?php endif; ?>

<form id="form-eliminar-tema" method="POST" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" style="display:none;">
    <input type="hidden" name="accion" value="eliminar_tema">
    <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
    <input type="hidden" id="form-eliminar-tema-lote" name="lote_id" value="">
    <input type="hidden" id="form-eliminar-tema-contexto-nivel" name="contexto_nivel" value="0">
    <input type="hidden" id="form-eliminar-tema-contexto-modulo" name="contexto_modulo" value="0">
    <input type="hidden" id="form-eliminar-tema-contexto-categoria" name="contexto_categoria" value="">
    <input type="hidden" id="form-eliminar-tema-contexto-leccion" name="contexto_leccion" value="">
</form>

<div id="modal-vistas-material" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; overflow:auto;">
    <div style="background:white; margin:40px auto; padding:30px; border-radius:8px; max-width:700px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0;">Personas que vieron el material</h3>
            <button type="button" class="btn btn-sm" onclick="document.getElementById('modal-vistas-material').style.display='none';" style="padding:5px 10px;">x</button>
        </div>

        <div id="modal-content-loading" style="text-align:center; padding:20px;">
            <p>Cargando...</p>
        </div>

        <div id="modal-content-vistas" style="display:none;">
            <div style="background:#f8f9fa; padding:12px; border-radius:4px; margin-bottom:16px;">
                <strong>Tema:</strong> <span id="modal-tema-nombre"></span>
            </div>

            <div style="background:#f8f9fa; padding:12px; border-radius:4px; margin-bottom:16px;">
                <strong>Total de personas:</strong> <span id="modal-total-personas" style="font-size:1.2em; color:#007bff;">0</span>
            </div>

            <table class="table table-hover" style="margin-bottom:0;">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th style="width:150px;">Ministerio</th>
                        <th style="width:120px;">Vistas</th>
                        <th style="width:160px;">Última vista</th>
                    </tr>
                </thead>
                <tbody id="modal-vistas-list"></tbody>
            </table>
        </div>

        <div id="modal-content-error" style="display:none; background:#f8d7da; padding:12px; border-radius:4px; color:#721c24;"></div>
    </div>
</div>

<div id="material-gallery-modal" class="material-gallery-modal" aria-hidden="true">
    <div class="material-gallery-shell" role="dialog" aria-modal="true" aria-labelledby="material-gallery-title">
        <div class="material-gallery-topbar">
            <div>
                <h3 id="material-gallery-title">Presentación de imágenes</h3>
                <small id="material-gallery-counter">0 / 0</small>
            </div>
            <button type="button" class="material-gallery-close" id="material-gallery-close" aria-label="Cerrar presentación">×</button>
        </div>
        <div class="material-gallery-stage">
            <button type="button" class="material-gallery-nav" id="material-gallery-prev" aria-label="Imagen anterior">‹</button>
            <div class="material-gallery-figure">
                <div class="material-gallery-frame">
                    <img id="material-gallery-image" src="" alt="">
                </div>
                <div class="material-gallery-caption">
                    <div>
                        <strong id="material-gallery-name">Imagen</strong>
                        <span id="material-gallery-help">Usa las flechas del teclado o las miniaturas para navegar.</span>
                    </div>
                    <a id="material-gallery-open" href="#" target="_blank" rel="noopener">Abrir archivo</a>
                </div>
            </div>
            <button type="button" class="material-gallery-nav" id="material-gallery-next" aria-label="Imagen siguiente">›</button>
        </div>
        <div id="material-gallery-thumbs" class="material-gallery-thumbs"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var configCapDestino = <?= json_encode($configCapacitacionDestino, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var rutaEvaluacionesCap = <?= json_encode(PUBLIC_URL . '?url=programas/evaluaciones&from_material=1', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function poblarModulosCapDestino(selectNivel, selectModulo) {
        if (!selectNivel || !selectModulo) {
            return;
        }

        var nivel = String(selectNivel.value || '1');
        var modulos = configCapDestino[nivel] || [];
        var seleccionadoPrevio = String(selectModulo.getAttribute('data-selected') || selectModulo.value || '');

        selectModulo.innerHTML = '';
        modulos.forEach(function(moduloNumero) {
            var opt = document.createElement('option');
            opt.value = String(moduloNumero);
            opt.textContent = 'Módulo ' + String(moduloNumero);
            if (String(moduloNumero) === seleccionadoPrevio) {
                opt.selected = true;
            }
            selectModulo.appendChild(opt);
        });

        if (selectModulo.options.length > 0 && selectModulo.selectedIndex === -1) {
            selectModulo.selectedIndex = 0;
        }

        selectModulo.setAttribute('data-selected', selectModulo.value || '');
    }

    var nivelNuevo = document.getElementById('nivel');
    var moduloNuevo = document.getElementById('modulo_numero');
    if (nivelNuevo && moduloNuevo) {
        poblarModulosCapDestino(nivelNuevo, moduloNuevo);
        nivelNuevo.addEventListener('change', function() {
            moduloNuevo.setAttribute('data-selected', '');
            poblarModulosCapDestino(nivelNuevo, moduloNuevo);
        });
    }

    document.querySelectorAll('.js-cap-destino-nivel').forEach(function(selectNivel) {
        var contenedor = selectNivel.closest('form');
        if (!contenedor) {
            return;
        }

        var selectModulo = contenedor.querySelector('.js-cap-destino-modulo');
        if (!selectModulo) {
            return;
        }

        poblarModulosCapDestino(selectNivel, selectModulo);
        selectNivel.addEventListener('change', function() {
            selectModulo.setAttribute('data-selected', '');
            poblarModulosCapDestino(selectNivel, selectModulo);
        });
    });

    var modalElement = document.getElementById('modal-vistas-material');
    var botones = document.querySelectorAll('.js-ver-vistas');
    var botonesTema = document.querySelectorAll('.js-toggle-tema');
    var galeriaModal = document.getElementById('material-gallery-modal');
    var galeriaTitulo = document.getElementById('material-gallery-title');
    var galeriaContador = document.getElementById('material-gallery-counter');
    var galeriaImagen = document.getElementById('material-gallery-image');
    var galeriaNombre = document.getElementById('material-gallery-name');
    var galeriaAbrir = document.getElementById('material-gallery-open');
    var galeriaPrev = document.getElementById('material-gallery-prev');
    var galeriaNext = document.getElementById('material-gallery-next');
    var galeriaThumbs = document.getElementById('material-gallery-thumbs');
    var galeriaClose = document.getElementById('material-gallery-close');
    var estadoGaleria = {
        items: [],
        index: 0,
        tema: ''
    };

    function renderizarGaleria() {
        if (!galeriaModal || !galeriaImagen || !estadoGaleria.items.length) {
            return;
        }

        if (estadoGaleria.index < 0) {
            estadoGaleria.index = 0;
        }
        if (estadoGaleria.index >= estadoGaleria.items.length) {
            estadoGaleria.index = estadoGaleria.items.length - 1;
        }

        var actual = estadoGaleria.items[estadoGaleria.index];
        galeriaImagen.src = actual.src || '';
        galeriaImagen.alt = actual.nombre || 'Imagen del material';
        galeriaNombre.textContent = actual.nombre || 'Imagen';
        galeriaAbrir.href = actual.abrir || actual.src || '#';
        galeriaContador.textContent = (estadoGaleria.index + 1) + ' / ' + estadoGaleria.items.length;
        galeriaTitulo.textContent = estadoGaleria.tema || 'Presentación de imágenes';
        galeriaPrev.disabled = estadoGaleria.items.length <= 1;
        galeriaNext.disabled = estadoGaleria.items.length <= 1;

        if (galeriaThumbs) {
            Array.prototype.forEach.call(galeriaThumbs.querySelectorAll('.material-gallery-thumb'), function(btn, idx) {
                btn.classList.toggle('is-active', idx === estadoGaleria.index);
            });
        }
    }

    function abrirGaleria(items, tema, indexInicial) {
        if (!galeriaModal || !Array.isArray(items) || !items.length) {
            return;
        }

        estadoGaleria.items = items;
        estadoGaleria.index = typeof indexInicial === 'number' ? indexInicial : 0;
        estadoGaleria.tema = tema || 'Presentación de imágenes';

        if (galeriaThumbs) {
            galeriaThumbs.innerHTML = '';
            items.forEach(function(item, idx) {
                var thumb = document.createElement('button');
                thumb.type = 'button';
                thumb.className = 'material-gallery-thumb';
                thumb.setAttribute('aria-label', 'Ir a imagen ' + (idx + 1));

                var thumbImg = document.createElement('img');
                thumbImg.src = item.src || '';
                thumbImg.alt = item.nombre || ('Imagen ' + (idx + 1));
                thumb.appendChild(thumbImg);

                thumb.addEventListener('click', function() {
                    estadoGaleria.index = idx;
                    renderizarGaleria();
                });

                galeriaThumbs.appendChild(thumb);
            });
        }

        galeriaModal.classList.add('is-open');
        galeriaModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        renderizarGaleria();
    }

    function cerrarGaleria() {
        if (!galeriaModal) {
            return;
        }

        galeriaModal.classList.remove('is-open');
        galeriaModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        estadoGaleria.items = [];
        estadoGaleria.index = 0;
        estadoGaleria.tema = '';

        if (galeriaImagen) {
            galeriaImagen.src = '';
            galeriaImagen.alt = '';
        }
        if (galeriaThumbs) {
            galeriaThumbs.innerHTML = '';
        }
    }

    function moverGaleria(delta) {
        if (!estadoGaleria.items.length) {
            return;
        }

        var total = estadoGaleria.items.length;
        estadoGaleria.index = (estadoGaleria.index + delta + total) % total;
        renderizarGaleria();
    }

    document.querySelectorAll('.js-abrir-galeria-tema').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tema = btn.getAttribute('data-tema') || 'Presentación de imágenes';
            var data = btn.getAttribute('data-images') || '[]';

            try {
                var items = JSON.parse(data);
                abrirGaleria(items, tema, 0);
            } catch (error) {
                console.error('No se pudo abrir la galería del tema.', error);
            }
        });
    });

    document.querySelectorAll('.js-abrir-galeria-desde-archivo').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tema = btn.getAttribute('data-tema') || 'Presentación de imágenes';
            var data = btn.getAttribute('data-images') || '[]';
            var index = parseInt(btn.getAttribute('data-index') || '0', 10);

            try {
                var items = JSON.parse(data);
                abrirGaleria(items, tema, index);
            } catch (error) {
                console.error('No se pudo abrir la galería desde el archivo.', error);
            }
        });
    });

    if (galeriaPrev) {
        galeriaPrev.addEventListener('click', function() {
            moverGaleria(-1);
        });
    }

    if (galeriaNext) {
        galeriaNext.addEventListener('click', function() {
            moverGaleria(1);
        });
    }

    if (galeriaClose) {
        galeriaClose.addEventListener('click', cerrarGaleria);
    }

    botonesTema.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var row = document.getElementById(targetId);
            if (!row) {
                return;
            }
            row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
        });
    });

    var tabsSubmodulo = document.querySelectorAll('.js-submodulo-tab');
    tabsSubmodulo.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            if (!targetId) {
                return;
            }

            tabsSubmodulo.forEach(function(tabBtn) {
                tabBtn.classList.remove('is-active');
                tabBtn.setAttribute('aria-selected', 'false');
            });
            this.classList.add('is-active');
            this.setAttribute('aria-selected', 'true');

            ['submodulo-panel-clase', 'submodulo-panel-profesor'].forEach(function(panelId) {
                var panel = document.getElementById(panelId);
                if (!panel) {
                    return;
                }
                if (panelId === targetId) {
                    panel.classList.remove('is-hidden');
                } else {
                    panel.classList.add('is-hidden');
                }
            });
        });
    });

    var capEntryCards = document.querySelectorAll('.js-open-cap-modal');
    var esCapacitacionDestinoVista = <?= $esCapacitacionDestino ? 'true' : 'false' ?>;
    var capInlinePanel = document.getElementById('cap-inline-panel');

    function marcarTarjetaPrincipalActiva(categoriaObjetivo) {
        var categoria = (categoriaObjetivo || '').toLowerCase();
        capEntryCards.forEach(function(card) {
            var target = (card.getAttribute('data-target') || '').toLowerCase();
            card.classList.toggle('is-active', target === categoria);
        });
    }

    function activarCategoriaPrincipal(categoriaObjetivo) {
        var categoria = (categoriaObjetivo || '').toLowerCase();
        if (!categoria) {
            categoria = 'clase';
        }

        marcarTarjetaPrincipalActiva(categoria);

        document.querySelectorAll('.js-cap-block').forEach(function(panel) {
            var categoriaPanel = (panel.getAttribute('data-cap-categoria') || '').toLowerCase();
            var mostrar = categoriaPanel === categoria;
            panel.classList.toggle('is-hidden', !mostrar);
            panel.classList.remove('is-selected');
            var body = panel.querySelector('.submodulo-body');
            if (body) {
                body.style.display = 'none';
            }
        });

        // Oculta secciones de modulo vacias segun la categoria activa
        document.querySelectorAll('.cap-nivel-section').forEach(function(section) {
            var visibles = section.querySelectorAll('.js-cap-block:not(.is-hidden)').length;
            section.style.display = visibles > 0 ? '' : 'none';
        });

    }

    function normalizarLeccion(texto) {
        return String(texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[^\w\s-]/g, '')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function abrirPanelCapInline() {
        if (!capInlinePanel) {
            return;
        }
        capInlinePanel.classList.add('is-open');
        capInlinePanel.setAttribute('aria-hidden', 'false');
    }

    function aplicarFiltroCapacitacion(state) {
        var nivelObjetivo = String(state.nivel || '');
        var moduloObjetivo = String(state.modulo || '');
        var categoriaObjetivo = String(state.categoria || 'clase').toLowerCase();
        var leccionObjetivo = normalizarLeccion(state.leccion || '');

        abrirPanelCapInline();

        document.querySelectorAll('.cap-nivel-section').forEach(function(section) {
            var nivelSeccion = String(section.getAttribute('data-modulo-grupo') || '');
            section.style.display = nivelSeccion === nivelObjetivo ? '' : 'none';
            section.classList.remove('is-focus-mode');
        });

        document.querySelectorAll('.js-cap-block').forEach(function(panel) {
            var nivelPanel = String(panel.getAttribute('data-cap-nivel') || '');
            var moduloPanel = String(panel.getAttribute('data-cap-modulo') || '');
            var categoriaPanel = String(panel.getAttribute('data-cap-categoria') || '').toLowerCase();

            var mostrar = nivelPanel === nivelObjetivo
                && moduloPanel === moduloObjetivo
                && categoriaPanel === categoriaObjetivo;

            panel.classList.toggle('is-hidden', !mostrar);
            panel.classList.toggle('is-selected', mostrar);
            panel.classList.toggle('is-focused', mostrar);

            var body = panel.querySelector('.submodulo-body');
            if (body) {
                body.style.display = mostrar ? 'block' : 'none';
            }

            if (!mostrar) {
                return;
            }

            panel.querySelectorAll('.js-tema-row').forEach(function(mainRow) {
                var key = String(mainRow.getAttribute('data-tema-key') || '');
                var leccionRow = normalizarLeccion(mainRow.getAttribute('data-cap-leccion') || '');
                var coincideLeccion = (leccionObjetivo === '' || leccionRow === leccionObjetivo);

                mainRow.style.display = coincideLeccion ? '' : 'none';

                if (!coincideLeccion && key !== '') {
                    panel.querySelectorAll('tr[data-tema-key="' + key.replace(/"/g, '\\"') + '"]').forEach(function(relatedRow) {
                        relatedRow.style.display = 'none';
                    });
                }
            });
        });
    }

    var levelCards = document.querySelectorAll('.js-cap-level-card');
    var categoriaBtns = document.querySelectorAll('.js-cap-categoria-btn');
    var capViewBtns = document.querySelectorAll('.js-cap-view-btn');
    var capAcademicoBtns = document.querySelectorAll('.js-cap-academico-btn');
    var capAcademicoInscritos = document.getElementById('cap-academico-inscritos');
    var capAcademicoTareas = document.getElementById('cap-academico-tareas');
    var capAcademicoPanel = document.getElementById('cap-academico-panel');
    var capMaterialPanel = document.getElementById('cap-material-panel');
    var capLessonsCount = document.getElementById('cap-lessons-count');
    var moduleSelector = document.getElementById('cap-module-selector');
    var queryParamsCap = new URLSearchParams(window.location.search || '');
    var capNivelQuery = String(queryParamsCap.get('cap_nivel') || '').trim();
    var capModuloQuery = String(queryParamsCap.get('cap_modulo') || '').trim();
    var capCategoriaQuery = String(queryParamsCap.get('cap_categoria') || '').trim().toLowerCase();
    var capAcademicoQuery = String(queryParamsCap.get('cap_academico') || '').trim().toLowerCase();
    var capRequiereSeleccionNivel = <?= $modoSeleccionNivelCap ? 'true' : 'false' ?>;
    var capRequiereSeleccionModulo = <?= !empty($modoSeleccionModuloCap) ? 'true' : 'false' ?>;
    var capModoHubMaestro = <?= !empty($modoHubModuloCapMaestro) ? 'true' : 'false' ?>;
    var esVistaMaestroCap = <?= $esVistaMaestro ? 'true' : 'false' ?>;
    var usaFlujoCapHub = <?= $usaFlujoCapHub ? 'true' : 'false' ?>;
    var capSeccionQuery = String(queryParamsCap.get('cap_seccion') || queryParamsCap.get('cap_academico') || '').trim().toLowerCase();

    var capVistaState = {
        nivel: '',
        categoria: 'clase',
        modulo: '',
        vista: 'lecciones'
    };

    if (capCategoriaQuery === 'clase' || capCategoriaQuery === 'profesor') {
        capVistaState.categoria = capCategoriaQuery;
    } else if (categoriaBtns.length > 0) {
        categoriaBtns.forEach(function(btn) {
            if (btn.classList.contains('is-active')) {
                capVistaState.categoria = String(btn.getAttribute('data-categoria') || 'clase').toLowerCase();
            }
        });
    }

    function obtenerPanelActivoCap() {
        return document.querySelector('.js-cap-block.is-selected');
    }

    function obtenerLeccionEvaluacionActiva() {
        var panelActivo = obtenerPanelActivoCap();
        if (!panelActivo) {
            return 'Sin lección';
        }

        var primeraFila = panelActivo.querySelector('.js-tema-row');
        if (!primeraFila) {
            return 'Sin lección';
        }

        var leccion = String(primeraFila.getAttribute('data-cap-leccion') || '').trim();
        return leccion !== '' ? leccion : 'Sin lección';
    }

    function actualizarResumenLecciones() {
        if (!capLessonsCount) {
            return;
        }

        var panelActivo = obtenerPanelActivoCap();
        if (!panelActivo) {
            capLessonsCount.textContent = 'Lecciones registradas: 0 items';
            return;
        }

        var total = parseInt(panelActivo.getAttribute('data-cap-total') || '0', 10) || 0;
        capLessonsCount.textContent = 'Lecciones registradas: ' + String(total) + ' items';
    }

    function navegarModuloCap(modulo) {
        var moduloStr = String(modulo || '').trim();
        var nivelStr = String(capVistaState.nivel || '').trim();
        if (moduloStr === '' || nivelStr === '') {
            return;
        }

        var urlActual = new URL(window.location.href);
        urlActual.searchParams.set('cap_nivel', nivelStr);
        urlActual.searchParams.set('cap_modulo', moduloStr);
        urlActual.searchParams.set('cap_categoria', String(capVistaState.categoria || 'clase'));
        window.location.href = urlActual.toString();
    }

    function marcarVistaCap(vista) {
        var vistaObj = vista === 'evaluaciones' ? 'evaluaciones' : 'lecciones';
        capVistaState.vista = vistaObj;
        capViewBtns.forEach(function(btn) {
            var vistaBtn = String(btn.getAttribute('data-cap-view') || 'lecciones').toLowerCase();
            btn.classList.toggle('is-active', vistaBtn === vistaObj);
        });
    }

    function activarVistaAcademicaCap(vista) {
        var objetivo = vista === 'tareas' ? 'tareas' : 'inscritos';

        capAcademicoBtns.forEach(function(btn) {
            var vistaBtn = String(btn.getAttribute('data-cap-academico') || 'inscritos').toLowerCase();
            btn.classList.toggle('is-active', vistaBtn === objetivo);
        });

        if (capAcademicoInscritos) {
            capAcademicoInscritos.classList.toggle('is-hidden', objetivo !== 'inscritos');
        }
        if (capAcademicoTareas) {
            capAcademicoTareas.classList.toggle('is-hidden', objetivo !== 'tareas');
        }

        if (capAcademicoPanel) {
            capAcademicoPanel.classList.remove('is-hidden');
        }
        if (capMaterialPanel) {
            capMaterialPanel.classList.add('is-hidden');
        }
    }

    function abrirEvaluacionesCap() {
        if (!capVistaState.nivel || !capVistaState.modulo) {
            return;
        }

        var leccionEval = obtenerLeccionEvaluacionActiva();
        var url = '<?= PUBLIC_URL ?>?url=programas/evaluaciones&from_material=1'
            + '&nivel=' + encodeURIComponent(String(capVistaState.nivel))
            + '&modulo=' + encodeURIComponent(String(capVistaState.modulo))
            + '&leccion=' + encodeURIComponent(leccionEval);

        window.location.href = url;
    }

    function obtenerBloquesCap(nivel, categoria) {
        var nivelStr = String(nivel || '');
        var categoriaStr = String(categoria || 'clase').toLowerCase();
        return Array.prototype.slice.call(document.querySelectorAll('.js-cap-block')).filter(function(panel) {
            var nivelPanel = String(panel.getAttribute('data-cap-nivel') || '');
            var categoriaPanel = String(panel.getAttribute('data-cap-categoria') || '').toLowerCase();
            return nivelPanel === nivelStr && categoriaPanel === categoriaStr;
        });
    }

    function activarModuloCap(modulo) {
        var moduloStr = String(modulo || '');
        capVistaState.modulo = moduloStr;

        if (capMaterialPanel) {
            capMaterialPanel.classList.remove('is-hidden');
        }
        if (capAcademicoPanel) {
            capAcademicoPanel.classList.add('is-hidden');
        }

        document.querySelectorAll('.cap-nivel-section').forEach(function(section) {
            section.querySelectorAll('.js-cap-block').forEach(function(panel) {
                var nivelPanel = String(panel.getAttribute('data-cap-nivel') || '');
                var categoriaPanel = String(panel.getAttribute('data-cap-categoria') || '').toLowerCase();
                var moduloPanel = String(panel.getAttribute('data-cap-modulo') || '');

                var mostrar = nivelPanel === String(capVistaState.nivel)
                    && categoriaPanel === String(capVistaState.categoria)
                    && moduloPanel === moduloStr;

                panel.classList.toggle('is-hidden', !mostrar);
                panel.classList.toggle('is-selected', mostrar);
                panel.classList.toggle('is-focused', mostrar);

                var body = panel.querySelector('.submodulo-body');
                if (body) {
                    body.style.display = mostrar ? 'block' : 'none';
                }
            });
        });

        if (moduleSelector) {
            moduleSelector.querySelectorAll('.cap-module-btn').forEach(function(btn) {
                btn.classList.toggle('is-active', String(btn.getAttribute('data-modulo') || '') === moduloStr);
            });
        }

        actualizarResumenLecciones();
    }

    function renderizarBotonesModulo() {
        if (!moduleSelector) {
            return;
        }

        moduleSelector.innerHTML = '';
        var bloques = obtenerBloquesCap(capVistaState.nivel, capVistaState.categoria);
        var modulos = [];
        var mapaTotales = {};

        bloques.forEach(function(panel) {
            var modulo = String(panel.getAttribute('data-cap-modulo') || '');
            if (!modulo) {
                return;
            }
            if (modulos.indexOf(modulo) === -1) {
                modulos.push(modulo);
            }
            mapaTotales[modulo] = parseInt(panel.getAttribute('data-cap-total') || '0', 10) || 0;
        });

        modulos.sort(function(a, b) {
            return parseInt(a, 10) - parseInt(b, 10);
        });

        if (!capVistaState.modulo || modulos.indexOf(String(capVistaState.modulo)) === -1) {
            capVistaState.modulo = modulos.length > 0 ? String(modulos[0]) : '';
        }

        modulos.forEach(function(modulo) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cap-module-btn';
            btn.setAttribute('data-modulo', modulo);
            btn.innerHTML = '<span>Módulo ' + modulo + '</span><span class="meta">' + String(mapaTotales[modulo] || 0) + ' tema(s)</span>';
            btn.addEventListener('click', function() {
                navegarModuloCap(modulo);
            });
            moduleSelector.appendChild(btn);
        });

        if (capVistaState.modulo !== '') {
            activarModuloCap(capVistaState.modulo);
        } else {
            actualizarResumenLecciones();
        }
    }

    function activarVistaCapPorNivelYCategoria(nivel, categoria) {
        var nivelStr = String(nivel || '');
        var categoriaStr = String(categoria || 'clase').toLowerCase();
        capVistaState.nivel = nivelStr;
        capVistaState.categoria = categoriaStr;

        abrirPanelCapInline();

        levelCards.forEach(function(card) {
            card.classList.toggle('is-active', String(card.getAttribute('data-level') || '') === nivelStr);
        });

        categoriaBtns.forEach(function(btn) {
            btn.classList.toggle('is-active', String(btn.getAttribute('data-categoria') || '').toLowerCase() === categoriaStr);
        });

        document.querySelectorAll('.cap-nivel-section').forEach(function(section) {
            var nivelSeccion = String(section.getAttribute('data-modulo-grupo') || '');
            var mostrarSeccion = nivelSeccion === nivelStr;
            section.style.display = mostrarSeccion ? '' : 'none';

            section.querySelectorAll('.js-cap-block').forEach(function(panel) {
                panel.classList.add('is-hidden');
                panel.classList.remove('is-selected');
                panel.classList.remove('is-focused');

                var body = panel.querySelector('.submodulo-body');
                if (body) {
                    body.style.display = 'none';
                }
            });
        });

        if (moduleSelector) {
            renderizarBotonesModulo();
        } else if (capVistaState.modulo !== '') {
            activarModuloCap(capVistaState.modulo);
        }
    }
    if (esCapacitacionDestinoVista) {
        if (capRequiereSeleccionNivel || capRequiereSeleccionModulo || capModoHubMaestro) {
            return;
        }

        if (usaFlujoCapHub && capSeccionQuery !== '' && capSeccionQuery !== 'material') {
            if (capSeccionQuery === 'tareas' || capSeccionQuery === 'inscritos') {
                activarVistaAcademicaCap(capSeccionQuery);
            }
            return;
        }

        var nivelInicial = capNivelQuery !== ''
            ? capNivelQuery
            : (levelCards.length > 0 ? String(levelCards[0].getAttribute('data-level') || '1') : '1');
        var categoriaInicial = capVistaState.categoria;
        capVistaState.modulo = capModuloQuery !== '' ? capModuloQuery : '';
        activarVistaCapPorNivelYCategoria(nivelInicial, categoriaInicial);
        if (usaFlujoCapHub && capVistaState.modulo !== '') {
            activarModuloCap(capVistaState.modulo);
        }
        marcarVistaCap('lecciones');

        categoriaBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var nivel = capVistaState.nivel || nivelInicial;
                var categoria = String(btn.getAttribute('data-categoria') || 'clase');
                activarVistaCapPorNivelYCategoria(nivel, categoria);
                marcarVistaCap('lecciones');
                var categoriaSelect = document.getElementById('categoria');
                if (categoriaSelect) {
                    categoriaSelect.value = categoria;
                }
                if (usaFlujoCapHub && capVistaState.modulo !== '') {
                    var urlCategoria = new URL(window.location.href);
                    urlCategoria.searchParams.set('cap_categoria', categoria);
                    window.history.replaceState(null, '', urlCategoria.toString());
                }
            });
        });

        if (!usaFlujoCapHub) {
            capViewBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var vista = String(btn.getAttribute('data-cap-view') || 'lecciones').toLowerCase();
                    if (vista === 'evaluaciones') {
                        marcarVistaCap('evaluaciones');
                        abrirEvaluacionesCap();
                        return;
                    }
                    marcarVistaCap('lecciones');
                    if (capMaterialPanel) {
                        capMaterialPanel.classList.remove('is-hidden');
                    }
                    if (capAcademicoPanel) {
                        capAcademicoPanel.classList.add('is-hidden');
                    }
                });
            });
        }

        if (capAcademicoBtns.length > 0 && !usaFlujoCapHub) {
            var vistaAcademicaInicial = capAcademicoQuery === 'tareas' ? 'tareas' : 'inscritos';
            activarVistaAcademicaCap(vistaAcademicaInicial);
            capAcademicoBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var vista = String(btn.getAttribute('data-cap-academico') || 'inscritos').toLowerCase();
                    activarVistaAcademicaCap(vista);
                });
            });
        }
    } else {
        document.querySelectorAll('.js-open-cap-modal').forEach(function(card) {
            var abrirPanel = function() {
                if (!capInlinePanel) {
                    return;
                }
                var target = card.getAttribute('data-target') || 'clase';
                activarCategoriaPrincipal(target);
                capInlinePanel.classList.add('is-open');
                capInlinePanel.setAttribute('aria-hidden', 'false');
                capInlinePanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            };

            card.addEventListener('click', abrirPanel);
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    abrirPanel();
                }
            });
        });
    }

    document.querySelectorAll('.cap-destino-grid .submodulo-head, .uv-material-grid .submodulo-head').forEach(function(head) {
        head.addEventListener('click', function() {
            var bloque = head.closest('.submodulo-wrap');
            if (!bloque || bloque.classList.contains('is-hidden')) {
                return;
            }

            var body = bloque.querySelector('.submodulo-body');
            if (!body) {
                return;
            }

            if (esCapacitacionDestinoVista) {
                var yaAbierto = body.style.display !== 'none' && body.style.display !== '';
                if (yaAbierto) {
                    body.style.display = 'none';
                    bloque.classList.remove('is-selected');
                    bloque.classList.remove('is-focused');
                    return;
                }

                body.style.display = 'block';
                bloque.classList.add('is-selected');
                bloque.classList.add('is-focused');
                return;
            }

            var abrir = body.style.display === 'none' || body.style.display === '';
            document.querySelectorAll('.js-cap-block').forEach(function(item) {
                var itemBody = item.querySelector('.submodulo-body');
                if (itemBody) {
                    itemBody.style.display = 'none';
                }
                item.classList.remove('is-selected');
            });

            if (abrir) {
                body.style.display = 'block';
                bloque.classList.add('is-selected');
            }
        });
    });

    var botonesEditarTema = document.querySelectorAll('.js-toggle-editar-tema');
    botonesEditarTema.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var row = document.getElementById(targetId);
            if (!row) {
                return;
            }
            row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
        });
    });

    document.querySelectorAll('.js-toggle-profesor-form').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var form = document.getElementById(targetId);
            if (!form) {
                return;
            }
            form.classList.toggle('is-open');
        });
    });

    var botonesAgregarArchivos = document.querySelectorAll('.js-toggle-agregar-archivos');
    botonesAgregarArchivos.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var row = document.getElementById(targetId);
            if (!row) {
                return;
            }
            row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
        });
    });

    botones.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var lote = this.getAttribute('data-lote') || '';
            abrirModalVistas(lote);
        });
    });

    function abrirModalVistas(lote) {
        document.getElementById('modal-content-loading').style.display = 'block';
        document.getElementById('modal-content-vistas').style.display = 'none';
        document.getElementById('modal-content-error').style.display = 'none';
        modalElement.style.display = 'block';

        fetch(<?= json_encode($rutaDetalleVistas, JSON_UNESCAPED_SLASHES) ?> + '&lote=' + encodeURIComponent(lote))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                document.getElementById('modal-content-loading').style.display = 'none';

                if (data.success) {
                    document.getElementById('modal-tema-nombre').textContent = data.tema || 'Tema de material';
                    document.getElementById('modal-total-personas').textContent = data.total_personas;

                    var tbody = document.getElementById('modal-vistas-list');
                    tbody.innerHTML = '';

                    if (data.vistas && data.vistas.length > 0) {
                        data.vistas.forEach(function(vista) {
                            var nombre = (vista.Nombre ? vista.Nombre : '') + ' ' + (vista.Apellido ? vista.Apellido : '');
                            nombre = nombre.trim() || 'Sin nombre';
                            var ministerio = vista.Nombre_Ministerio || 'Sin ministerio';
                            var totalVistas = vista.Total_Vistas || 0;
                            var ultimaVista = vista.Fecha_Ultima_Vista ? new Date(vista.Fecha_Ultima_Vista).toLocaleString('es-ES') : '-';

                            var tr = document.createElement('tr');
                            tr.innerHTML = '<td>' + escapeHtml(nombre) + '</td>' +
                                '<td>' + escapeHtml(ministerio) + '</td>' +
                                '<td>' + String(totalVistas) + '</td>' +
                                '<td>' + escapeHtml(ultimaVista) + '</td>';
                            tbody.appendChild(tr);
                        });
                    } else {
                        var trVacio = document.createElement('tr');
                        trVacio.innerHTML = '<td colspan="4" style="text-align:center; color:#999;">Aún no hay registro de vistas</td>';
                        tbody.appendChild(trVacio);
                    }

                    document.getElementById('modal-content-vistas').style.display = 'block';
                } else {
                    document.getElementById('modal-content-error').textContent = data.message || 'Error al cargar los datos';
                    document.getElementById('modal-content-error').style.display = 'block';
                }
            })
            .catch(function() {
                document.getElementById('modal-content-loading').style.display = 'none';
                document.getElementById('modal-content-error').textContent = 'Error al cargar los datos';
                document.getElementById('modal-content-error').style.display = 'block';
            });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    modalElement.addEventListener('click', function(e) {
        if (e.target === modalElement) {
            modalElement.style.display = 'none';
        }
    });

    if (galeriaModal) {
        galeriaModal.addEventListener('click', function(e) {
            if (e.target === galeriaModal) {
                cerrarGaleria();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (!galeriaModal || !galeriaModal.classList.contains('is-open')) {
            return;
        }

        if (e.key === 'Escape') {
            cerrarGaleria();
            return;
        }

        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            moverGaleria(-1);
            return;
        }

        if (e.key === 'ArrowRight' || e.key === ' ') {
            e.preventDefault();
            moverGaleria(1);
        }
    });

    // === Eliminar clase ===
    var formEliminarTema = document.getElementById('form-eliminar-tema');
    var formEliminarTemaLote = document.getElementById('form-eliminar-tema-lote');
    var formEliminarTemaContextoNivel = document.getElementById('form-eliminar-tema-contexto-nivel');
    var formEliminarTemaContextoModulo = document.getElementById('form-eliminar-tema-contexto-modulo');
    var formEliminarTemaContextoCategoria = document.getElementById('form-eliminar-tema-contexto-categoria');
    var formEliminarTemaContextoLeccion = document.getElementById('form-eliminar-tema-contexto-leccion');

    document.querySelectorAll('.js-eliminar-tema').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var lote = this.getAttribute('data-lote');
            var titulo = this.getAttribute('data-titulo');
            if (!confirm('¿Eliminar la clase "' + titulo + '" y todos sus archivos?\n\nEsta acción no se puede deshacer.')) {
                return;
            }
            formEliminarTemaLote.value = lote;
            if (formEliminarTemaContextoNivel) {
                formEliminarTemaContextoNivel.value = String(this.getAttribute('data-contexto-nivel') || '0');
            }
            if (formEliminarTemaContextoModulo) {
                formEliminarTemaContextoModulo.value = String(this.getAttribute('data-contexto-modulo') || '0');
            }
            if (formEliminarTemaContextoCategoria) {
                formEliminarTemaContextoCategoria.value = String(this.getAttribute('data-contexto-categoria') || '');
            }
            if (formEliminarTemaContextoLeccion) {
                formEliminarTemaContextoLeccion.value = String(this.getAttribute('data-contexto-leccion') || '');
            }
            formEliminarTema.submit();
        });
    });
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
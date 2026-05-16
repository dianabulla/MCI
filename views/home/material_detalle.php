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
$tareasCapNivel = (array)($tareas_cap_nivel ?? []);
$entregasTareasCap = (array)($entregas_tareas_cap ?? []);
$tareasDiscipuloCap = (array)($tareas_discipulo_cap ?? []);
$capModuloVistaActual = (int)($cap_modulo_vista ?? ($_GET['cap_modulo'] ?? 0));
$idPersonaActual = (int)($id_persona_actual ?? 0);
$puedeSubirTareas = !empty($puede_subir_tareas);
$rutaDetalleVistas = PUBLIC_URL . '?url=home/material/detalle-vistas&modulo=' . rawurlencode($clave);
$capNivelVista = 0;
$modoSeleccionNivelCap = false;
$vistaCapNivelIndependiente = false;

if ($esCapacitacionDestino && !$esDiscipuloCapDestino) {
    $nivelSolicitado = (int)($_GET['cap_nivel'] ?? 0);
    if ($nivelSolicitado > 0 && isset($configCapacitacionDestino[$nivelSolicitado])) {
        $capNivelVista = $nivelSolicitado;
    }
    $modoSeleccionNivelCap = $capNivelVista <= 0;
    $vistaCapNivelIndependiente = $capNivelVista > 0;
}
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

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;"><?= htmlspecialchars($titulo) ?></h2>
        <small style="color:#637087;">Gestiona módulos de material con varios archivos por creación.</small>
    </div>
    <a href="<?= PUBLIC_URL ?>?url=home/material" class="btn btn-secondary">Volver a Material</a>
</div>

<?php if ($mensaje !== ''): ?>
    <div class="alert alert-<?= $tipo === 'success' ? 'success' : 'danger' ?>" style="margin-top:14px;">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

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

<?php if ($esCapacitacionDestino && $puedeGestionar && !$puedeSubirMaterial): ?>
<div class="alert alert-warning" style="margin-bottom: 12px;">
    Tienes acceso de gestión en este módulo, pero no cuentas con permiso para subir archivos.
</div>
<?php endif; ?>

<?php if ($puedeGestionar && $puedeSubirMaterial && !$vistaCapNivelIndependiente): ?>
<div style="margin-bottom: 10px;">
    <button type="button"
        class="btn btn-primary"
        id="btn-toggle-upload-form"
        aria-expanded="false"
        aria-controls="upload-form-panel"
        onclick="(function(btn){var panel=document.getElementById('upload-form-panel');if(!panel){return;}var abierto=panel.style.display==='block';panel.style.display=abierto?'none':'block';btn.setAttribute('aria-expanded',abierto?'false':'true');btn.textContent=abierto?'Mostrar formulario de subir material':'Ocultar formulario de subir material';})(this);">
        Mostrar formulario de subir material
    </button>
</div>
<div class="form-container" id="upload-form-panel" style="margin-bottom: 16px; display:none;">
    <h3 style="margin-top:0;">Crear módulo de material</h3>
    <form method="POST" enctype="multipart/form-data" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>">
        <input type="hidden" name="accion" value="subir">
        <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
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
                    <option value="clase">Material clase</option>
                    <option value="profesor">Material profesor</option>
                </select>
            </div>
        <?php endif; ?>
        <?php if ($esCapacitacionDestino): ?>
            <div class="form-group" style="margin-bottom: 12px;">
                <label for="nivel">Nivel</label>
                <select id="nivel" name="nivel" class="form-control" required>
                    <option value="1">Nivel 1</option>
                    <option value="2">Nivel 2</option>
                    <option value="3">Nivel 3</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label for="modulo_numero">Módulo</label>
                <select id="modulo_numero" name="modulo_numero" class="form-control" required>
                    <option value="1">Módulo 1</option>
                    <option value="2">Módulo 2</option>
                </select>
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

<?php if ($usaTarjetasTipoMaterial && !$esDiscipuloCapDestino): ?>
<?php if ($esCapacitacionDestino): ?>
    <?php if (!$vistaCapNivelIndependiente): ?>
    <div class="cap-level-selector" id="cap-level-selector">
        <?php foreach ($resumenNivelesCap as $nivelCard): ?>
            <a class="cap-level-card js-cap-level-card <?= $capNivelVista === (int)$nivelCard['nivel'] ? 'is-active' : '' ?>"
               data-level="<?= (int)$nivelCard['nivel'] ?>"
               href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta, ENT_QUOTES, 'UTF-8') ?>&cap_nivel=<?= (int)$nivelCard['nivel'] ?>"
               target="_blank"
               rel="noopener noreferrer">
                <h4 class="cap-level-card-title">Nivel <?= (int)$nivelCard['nivel'] ?></h4>
                <div class="cap-level-card-meta">
                    <span><?= (int)$nivelCard['total_modulos'] ?> módulo(s)</span>
                    <strong><?= (int)$nivelCard['total_temas'] ?> tema(s)</strong>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($capNivelVista > 0): ?>
        <div style="margin-bottom:10px;">
            <a class="btn btn-sm btn-secondary" href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta, ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-arrow-left-short"></i> Volver a niveles
            </a>
        </div>
    <?php endif; ?>
    <?php if (!$modoSeleccionNivelCap): ?>
    <div class="cap-toolbar">
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

        <?php if ($capNivelVista > 0): ?>
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

    <?php if ($capNivelVista > 0): ?>
        <div id="cap-academico-panel" class="cap-academico-panel cap-main-section is-hidden">
            <div class="cap-academico-head">
                <strong style="color:#2b4f79;">Gestión académica del nivel <?= (int)$capNivelVista ?></strong>
                <small style="color:#5a6f8d;">Inscritos y tareas del nivel actual</small>
            </div>

            <div id="cap-academico-inscritos" class="cap-academico-section">
                <?php if (!empty($inscritosCapNivel)): ?>
                    <div style="overflow-x: auto;">
                        <table class="cap-inscritos-table" style="min-width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="position: sticky; left: 0; background: #f8f9fa; z-index: 10;">Nombre</th>
                                    <th style="position: sticky; left: 120px; background: #f8f9fa; z-index: 10;">Cédula</th>
                                    <th>Teléfono</th>
                                    <th>Inscrito</th>
                                    <th colspan="10" style="text-align:center; background:#e8f0f8;">Planilla de Asistencia</th>
                                </tr>
                                <tr>
                                    <th style="position: sticky; left: 0; background: #f8f9fa; z-index: 10;"></th>
                                    <th style="position: sticky; left: 120px; background: #f8f9fa; z-index: 10;"></th>
                                    <th></th>
                                    <th></th>
                                    <?php for ($clase = 1; $clase <= 10; $clase++): ?>
                                        <th style="text-align:center; width:50px; background:#e8f0f8; padding:6px 3px;">Clase <?= $clase ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inscritosCapNivel as $inscrito): ?>
                                    <?php
                                        $idPersona = (int)($inscrito['id_persona'] ?? 0);
                                        $asistenciasPersona = !empty($asistenciasPorPersona[$idPersona]) ? (array)$asistenciasPorPersona[$idPersona] : [];
                                    ?>
                                    <tr>
                                        <td style="position: sticky; left: 0; background: white; z-index: 5;"><?= htmlspecialchars((string)($inscrito['nombre'] ?? '')) ?></td>
                                        <td style="position: sticky; left: 120px; background: white; z-index: 5;"><?= htmlspecialchars((string)($inscrito['cedula'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($inscrito['telefono'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($inscrito['fecha_registro'] ?? '')) ?></td>
                                        <?php for ($clase = 1; $clase <= 10; $clase++): ?>
                                            <td style="text-align:center; padding:6px 3px;">
                                                <input type="checkbox" class="asistencia-check" 
                                                    data-id-persona="<?= $idPersona ?>" 
                                                    data-clase="<?= $clase ?>"
                                                    data-nivel="<?= (int)$capNivelVista ?>"
                                                    data-modulo="<?= (int)$capModuloVistaActual ?>"
                                                    <?= in_array($clase, $asistenciasPersona) ? 'checked' : '' ?>
                                                    style="width:20px; height:20px; cursor:pointer;">
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
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
                        .asistencia-check:checked {
                            accent-color: #28a745;
                        }
                    </style>

                    <script>
                    document.querySelectorAll('.asistencia-check').forEach(checkbox => {
                        checkbox.addEventListener('change', function() {
                            const idPersona = this.dataset.idPersona;
                            const clase = this.dataset.clase;
                            const nivel = this.dataset.nivel;
                            const checked = this.checked;
                            
                            const datosEnvio = {
                                id_persona: idPersona,
                                clase: clase,
                                nivel: nivel,
                                marcar: checked ? '1' : '0'
                            };
                            
                            console.log('📝 Guardando asistencia:', datosEnvio);
                            
                            // Construir body manualmente
                            let body = '';
                            for (let key in datosEnvio) {
                                if (body) body += '&';
                                body += encodeURIComponent(key) + '=' + encodeURIComponent(datosEnvio[key]);
                            }
                            
                            console.log('📤 Body enviado:', body);
                            
                            fetch('<?= PUBLIC_URL ?>?url=home/guardar-asistencia-clase', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                },
                                body: body
                            })
                            .then(response => {
                                console.log('📨 Status respuesta:', response.status);
                                return response.text().then(text => {
                                    console.log('📄 Respuesta texto:', text);
                                    try {
                                        return JSON.parse(text);
                                    } catch (e) {
                                        throw new Error('No es JSON válido: ' + text.substring(0, 100));
                                    }
                                });
                            })
                            .then(data => {
                                console.log('✅ Datos parseados:', data);
                                if (data && data.success) {
                                    console.log('✔️ Asistencia guardada exitosamente');
                                } else {
                                    this.checked = !checked;
                                    console.error('❌ Error respuesta:', data?.error);
                                    alert('Error al guardar: ' + (data?.error || 'Desconocido'));
                                }
                            })
                            .catch(error => {
                                this.checked = !checked;
                                console.error('❌ Error fetch:', error);
                                alert('Error al guardar asistencia: ' + error.message);
                            });
                        });
                    });
                    </script>
                <?php else: ?>
                    <div class="alert alert-info" style="margin:0;">No hay inscritos registrados para este nivel.</div>
                <?php endif; ?>
            </div>

            <div id="cap-academico-tareas" class="cap-academico-section is-hidden">
                <div style="margin-top:4px;">
                    <?php if ($puedeGestionar): ?>
                        <form method="POST" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" class="form-container" style="margin-bottom:10px;">
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
                                    <form method="POST" enctype="multipart/form-data" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" style="margin-bottom:8px; display:grid; grid-template-columns:2fr 1fr auto; gap:8px; align-items:end;">
                                        <input type="hidden" name="accion" value="subir_tarea_entrega">
                                        <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                                        <input type="hidden" name="id_tarea" value="<?= $idTarea ?>">
                                        <input type="hidden" name="nivel" value="<?= (int)$capNivelVista ?>">
                                        <input type="hidden" name="modulo_numero" value="<?= $moduloTarea ?>">
                                        <input type="hidden" name="contexto_nivel" value="<?= (int)$capNivelVista ?>">
                                        <input type="hidden" name="contexto_modulo" value="<?= $moduloTarea ?>">
                                        <div>
                                            <label style="font-size:12px;">Comentario</label>
                                            <input type="text" name="comentario_entrega" class="form-control" maxlength="500" placeholder="Comentario opcional de tu entrega">
                                        </div>
                                        <div>
                                            <label style="font-size:12px;">Archivos</label>
                                            <input type="file" name="tarea_archivos[]" class="form-control" multiple required>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary">Subir tarea</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($puedeGestionar): ?>
                                    <details>
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
        </div>
    <?php endif; ?>
    <?php endif; ?>
<?php else: ?>
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

<div id="cap-inline-panel" class="cap-panel" aria-hidden="true">
<?php endif; ?>

<div id="cap-material-panel" class="card cap-main-section" style="padding:14px;">
    <h3 style="margin-top:0;">Módulos de material</h3>

    <?php if ($esDiscipuloCapDestino): ?>
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
            <p style="margin:0; color:#666;">No hay accesos para mostrar. Si ya estás inscrito, valida fechas activas y que el líder haya configurado el link de clase en Conexiones.</p>
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
                                            <form method="POST" enctype="multipart/form-data" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" style="display:grid; grid-template-columns:2fr 1fr auto; gap:8px; align-items:end;">
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
                                                    <label style="font-size:12px;">Archivos</label>
                                                    <input type="file" name="tarea_archivos[]" class="form-control" multiple required>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-primary">Subir tarea</button>
                                            </form>
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

    <?php if ($tieneSubmodulos && !$usaTarjetasTipoMaterial): ?>
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

        <?php if ($usaTarjetasTipoMaterial && !$esCapacitacionDestino): ?><div class="cap-destino-grid"><?php endif; ?>
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
                        if (!$usaTarjetasTipoMaterial) {
                            $panelIdBloque = 'submodulo-panel-profesor';
                        }
                    } elseif (stripos($tituloBloque, 'clase') !== false) {
                        $claseCssBloque .= ' submodulo-clase';
                        if (!$usaTarjetasTipoMaterial) {
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

                if ($tieneSubmodulos && !$usaTarjetasTipoMaterial && $panelIdBloque === 'submodulo-panel-profesor') {
                    $claseCssBloque .= ' is-hidden';
                }
            ?>

            <?php
                if ($usaTarjetasTipoMaterial) {
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
                        <?php if ($esCapacitacionDestino && $categoriaBloque === 'clase'): ?>
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

<?php if ($usaTarjetasTipoMaterial && !$esDiscipuloCapDestino): ?>
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

    var capVistaState = {
        nivel: '',
        categoria: 'clase',
        modulo: '',
        vista: 'lecciones'
    };

    if (capCategoriaQuery === 'clase' || capCategoriaQuery === 'profesor') {
        capVistaState.categoria = capCategoriaQuery;
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

        window.open(url, '_blank');
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

        renderizarBotonesModulo();
    }
    if (esCapacitacionDestinoVista) {
        if (capRequiereSeleccionNivel) {
            return;
        }

        var nivelInicial = capNivelQuery !== ''
            ? capNivelQuery
            : (levelCards.length > 0 ? String(levelCards[0].getAttribute('data-level') || '1') : '1');
        var categoriaInicial = capVistaState.categoria;
        capVistaState.modulo = capModuloQuery !== '' ? capModuloQuery : '';
        activarVistaCapPorNivelYCategoria(nivelInicial, categoriaInicial);
        marcarVistaCap('lecciones');

        categoriaBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var nivel = capVistaState.nivel || nivelInicial;
                var categoria = String(btn.getAttribute('data-categoria') || 'clase');
                activarVistaCapPorNivelYCategoria(nivel, categoria);
                marcarVistaCap('lecciones');
            });
        });

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

        if (capAcademicoBtns.length > 0) {
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

    document.querySelectorAll('.cap-destino-grid .submodulo-head').forEach(function(head) {
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
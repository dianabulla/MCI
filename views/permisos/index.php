<?php require_once APP . '/Helpers/PermisosCatalogo.php'; ?>
<?php require_once VIEWS . '/layout/header.php'; ?>

<?php
$catalogo_acciones_modulo = isset($catalogo_acciones_modulo) && is_array($catalogo_acciones_modulo)
    ? $catalogo_acciones_modulo
    : [];
?>

<div class="page-header" style="margin-bottom: 20px;">
    <h2 style="margin: 0;">Administración</h2>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <div class="page-actions personas-mobile-stack" style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>index.php?url=cuentas" class="btn btn-nav-pill">Cuentas</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=roles" class="btn btn-nav-pill">Roles</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=permisos" class="btn btn-nav-pill active">Permisos</a>
        </div>
    </div>
</div>

<style>
/* Pagina de permisos */
.perm-page { max-width: 960px; margin: 0 auto; }

.perm-view-switch {
    display: inline-flex;
    gap: 8px;
    margin-bottom: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    padding: 4px;
    background: #f8fafc;
}

.perm-view-btn {
    border: 1px solid transparent;
    background: transparent;
    color: #334155;
    font-size: 13px;
    font-weight: 700;
    border-radius: 999px;
    padding: 7px 12px;
    cursor: pointer;
}

.perm-view-btn.active {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
}

.perm-view-panel {
    display: none;
}

.perm-view-panel.active {
    display: block;
}

/* Pestanas de roles */
.perm-tabs {
    display: flex;
    flex-wrap: nowrap;
    gap: 6px;
    margin-bottom: 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 2px;
    scrollbar-width: thin;
}
.perm-tab-btn {
    padding: 8px 18px;
    border: 2px solid #d1d5db;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
    background: #f3f4f6;
    color: #374151;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: background .15s, color .15s;
    position: relative;
    bottom: -2px;
}
.perm-tab-btn:hover { background: #e0e7ff; color: #1d4ed8; border-color: #a5b4fc; }
.perm-tab-btn.active {
    background: #fff;
    color: #1d4ed8;
    border-color: #2563eb;
    border-bottom-color: #fff;
    z-index: 1;
}
.perm-tab-btn .perm-tab-badge {
    display: inline-block;
    font-size: 10px;
    padding: 1px 5px;
    border-radius: 10px;
    margin-left: 4px;
    background: #dbeafe;
    color: #1e40af;
    font-weight: 700;
}
.perm-tab-btn.active .perm-tab-badge { background: #2563eb; color: #fff; }

/* Panel por rol */
.perm-panel {
    display: none;
    border: 2px solid #2563eb;
    border-radius: 0 8px 8px 8px;
    background: #fff;
    padding: 20px 20px 12px;
}
.perm-panel.active { display: block; }

/* Barra superior del panel */
.perm-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e5e7eb;
}
.perm-panel-title { font-size: 17px; font-weight: 700; color: #1e3a8a; margin: 0; }
.perm-panel-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.btn-perm-all {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px; border-radius: 6px; border: none;
    font-size: 12px; font-weight: 600; cursor: pointer; transition: opacity .15s;
}
.btn-perm-all:hover { opacity: .85; }
.btn-perm-activar  { background: #2563eb; color: #fff; }
.btn-perm-quitar   { background: #dc2626; color: #fff; }
.btn-perm-solo-ver { background: #059669; color: #fff; }

/* Acceso total badge */
.perm-acceso-total-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 20px;
    background: #dcfce7; color: #15803d;
    font-size: 12px; font-weight: 700;
    border: 1px solid #86efac;
}

/* Tabla de modulos */
.perm-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.perm-table thead th {
    background: #1e3a8a;
    color: #fff;
    text-align: center;
    padding: 8px 10px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .3px;
}
.perm-table thead th:first-child { text-align: left; width: 44%; }
.perm-table tbody tr { border-bottom: 1px solid #e5e7eb; }
.perm-table tbody tr:last-child { border-bottom: none; }
.perm-table tbody tr:hover { background: #f0f4ff; }
.perm-table tbody tr.perm-group-header td {
    background: #f1f5f9;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .5px;
    padding: 5px 10px;
    border-top: 2px solid #e2e8f0;
}
.perm-table td { padding: 9px 10px; vertical-align: middle; }
.perm-table td.perm-check-cell { text-align: center; width: 14%; }
.perm-table td.perm-name { font-size: 13px; color: #1e293b; }
.perm-table td.perm-name small { display: block; color: #94a3b8; font-size: 11px; margin-top: 1px; }

.perm-search-wrap {
    margin: 10px 0 14px;
}

.perm-module-grid {
    border: 2px solid #0f766e;
    border-radius: 10px;
    background: #fff;
    padding: 14px;
}

.perm-module-grid-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.perm-module-grid-head h3 {
    margin: 0;
    font-size: 17px;
    color: #0f766e;
}

.perm-module-search {
    width: 100%;
    max-width: 420px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 13px;
}

.perm-module-table-wrap {
    overflow-x: auto;
}

.perm-module-table {
    width: 100%;
    min-width: 820px;
    border-collapse: collapse;
    font-size: 12px;
}

.perm-module-table th,
.perm-module-table td {
    border-bottom: 1px solid #e2e8f0;
    padding: 8px 6px;
    text-align: center;
}

.perm-module-table th:first-child,
.perm-module-table td:first-child {
    text-align: left;
    width: 300px;
    min-width: 300px;
}

.perm-module-table thead th {
    background: #134e4a;
    color: #fff;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .03em;
}

.perm-module-table tr.perm-group-header td {
    background: #f0fdfa;
    color: #0f766e;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
}

.perm-badges {
    display: inline-flex;
    gap: 4px;
    align-items: center;
    justify-content: center;
}

.perm-module-action {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 11px;
    font-weight: 700;
    color: #334155;
}

.perm-role-total {
    font-size: 11px;
    color: #64748b;
    font-weight: 700;
}

.perm-search-input {
    width: 100%;
    max-width: 460px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 13px;
}

/* Checkbox estilo toggle */
.perm-cb {
    width: 18px; height: 18px;
    cursor: pointer;
    accent-color: #2563eb;
}
.perm-cb:disabled { opacity: .4; cursor: not-allowed; }

/* Toast */
#perm-toast {
    position: fixed;
    bottom: 24px; right: 24px;
    background: #15803d;
    color: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 4px 14px rgba(0,0,0,.18);
    display: none;
    z-index: 9999;
    transition: opacity .3s;
}
#perm-toast.error { background: #dc2626; }

.perm-adv-inline {
    margin-top: 12px;
    padding-top: 10px;
    border-top: 1px dashed #cbd5e1;
}
.perm-adv-inline-title {
    display: block;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748b;
    margin-bottom: 8px;
}
.perm-adv-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 16px;
    align-items: flex-start;
}
.perm-adv-item {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 6px 8px;
    align-items: start;
    max-width: 320px;
    font-size: 12px;
    color: #334155;
}
.perm-adv-item input {
    margin-top: 3px;
}
.perm-adv-item small {
    grid-column: 2;
    display: block;
    color: #94a3b8;
    font-size: 10px;
    line-height: 1.35;
    margin-top: 1px;
}
</style>

<div class="page-header">
    <h2><i class="bi bi-shield-check"></i> Administracion de Permisos</h2>
</div>

<div class="perm-page">

    <?php $modulosObsoletos = is_array($modulos_obsoletos ?? null) ? $modulos_obsoletos : []; ?>
    <div class="alert alert-info" style="margin-bottom:16px;font-size:13px;">
        <strong>Permisos en dos niveles.</strong>
        Ver / Crear / Editar / Eliminar definen el CRUD habitual del módulo.
        Las <strong>acciones avanzadas</strong> (bajo el nombre del módulo) permiten afinar funciones concretas
        (exportar, vistas de un solo programa, etc.). Para guardarlas en base de datos debe existir la columna
        <code>Acciones_Extra</code>: ejecute una vez el script
        <code>docs/sql/2026-05-15_permisos_acciones_extra.sql</code>.
        En código, use <code>AuthController::tienePermiso('modulo', 'clave_accion')</code> con la clave indicada en cada casilla.
    </div>
    <div class="alert alert-secondary" style="margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <div>
                <strong>Limpieza de módulos obsoletos</strong><br>
                <small>Detecta módulos guardados en la tabla de permisos que ya no existen en el código activo.</small>
                <?php if (!empty($modulosObsoletos)): ?>
                    <div style="margin-top:8px;">
                        <small>Módulos detectados: <?= htmlspecialchars(implode(', ', array_map('strval', $modulosObsoletos))) ?></small>
                    </div>
                <?php else: ?>
                    <div style="margin-top:8px;">
                        <small>No se detectaron módulos obsoletos para limpiar.</small>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <button
                    type="button"
                    id="btnLimpiarModulosObsoletos"
                    class="btn btn-sm btn-warning"
                    <?= empty($modulosObsoletos) ? 'disabled' : '' ?>>
                    Limpiar módulos obsoletos
                </button>
            </div>
        </div>
    </div>

    <!-- Aviso roles con acceso total -->
    <div class="alert alert-warning" style="margin-bottom:18px; font-size:13px;">
        <i class="bi bi-shield-fill-exclamation"></i>
        El rol con ID fijo <strong>6</strong> o cuyo nombre es explícitamente <strong>Administrador</strong> (palabra completa) queda protegido y no se puede editar aquí.<br>
        El resto de roles —incluidos coordinadores de Universidad de la Vida u otros— se configuran con los checks de esta pantalla.
    </div>

    <div class="perm-view-switch" role="tablist" aria-label="Vista de permisos">
        <button type="button" class="perm-view-btn active" data-view="roles" role="tab" aria-selected="true">Por rol</button>
        <button type="button" class="perm-view-btn" data-view="modulos" role="tab" aria-selected="false">Por módulo</button>
    </div>

    <div id="perm-view-roles" class="perm-view-panel active">

    <!-- Pestanas de roles -->
    <div class="perm-tabs">
        <?php foreach ($roles as $i => $rol):
            $idRol = $rol['Id_Rol'];
            $nombreRol = htmlspecialchars($rol['Nombre_Rol']);
            // Contar permisos activos (Ver=1) para el badge
            $activos = 0;
            foreach ($modulos as $mk => $mn) {
                if (!empty($permisos[$idRol][$mk]['Puede_Ver'])) $activos++;
            }
        ?>
        <button type="button"
            class="perm-tab-btn <?= $i === 0 ? 'active' : '' ?>"
            data-tab="rol-<?= $idRol ?>">
            <?= $nombreRol ?>
            <span class="perm-tab-badge"><?= $activos ?>/<?= count($modulos) ?></span>
        </button>
        <?php endforeach; ?>
    </div>

    <div class="perm-search-wrap">
        <input type="text" id="perm-search" class="perm-search-input" placeholder="Buscar módulo o descripción...">
    </div>

    <!-- Paneles por rol -->
    <?php
    $rolesProtegidos = [];
    foreach ($roles as $r) {
        if (PermisosCatalogo::esRolProtegidoPermisos((int)$r['Id_Rol'], (string)($r['Nombre_Rol'] ?? ''))) {
            $rolesProtegidos[] = (int)$r['Id_Rol'];
        }
    }

    // Grupos visuales de modulos
    $gruposModulos = [
        'Principal' => [
            'personas'         => ['Personas',         'Permisos generales del modulo (listado y CRUD principal).'],
            'personas_consulta' => ['Personas: solo consulta', 'Ver Discipulos, Universidad de la Vida y fichas; sin menu lateral Ganar-Consolidar ni pestaña Almas ganadas. Si el rol tiene Programas (UV o Cap. Destino) pero no el modulo Personas completo, el panel Personas del inicio se oculta y se entra por Programas.'],
            'personas_formulario_publico' => ['Personas: Formulario publico', 'Controla la visibilidad del boton Formulario publico en Personas.'],
            'personas_plantillas_whatsapp' => ['Personas: Plantillas WhatsApp', 'Controla acceso a Plantillas WhatsApp de Personas.'],
            'personas_ganar_asignados' => ['Pendiente: Atajo Asignados', 'Controla la visibilidad del atajo Asignados en Pendiente por consolidar.'],
            'personas_ganar_reasignados' => ['Pendiente: Atajo Reasignados', 'Controla la visibilidad del atajo Reasignados en Pendiente por consolidar.'],
            'celulas'          => ['Celulas',           'Gestion de celulas y miembros'],
            'ministerios'      => ['Ministerios',       'Ver y gestionar ministerios'],
            'asistencias'      => ['Asistencias',       'Registro de asistencias a celulas'],
            'eventos'          => ['Eventos',           'Eventos y actividades generales'],
            'peticiones'       => ['Peticiones',        'Peticiones de oracion'],
            'reportes'         => ['Reportes',          'Reportes y estadisticas'],
            'transmisiones'    => ['Transmisiones',     'Transmisiones en vivo'],
            'escuelas_formacion' => ['Escuelas de Formacion', 'Registro y asistencias de escuelas de formacion'],
            'escuelas_formacion_marcar_asistencia' => ['Escuelas: Marcar asistencia', 'Permite marcar/desmarcar asistencias en la matriz de Escuelas'],
            'escuelas_formacion_editar_fechas' => ['Escuelas: Editar fechas de clases', 'Permite editar fechas de clases en la matriz de Escuelas'],
            'discipular_evaluaciones' => ['Discipular: Evaluaciones', 'Controla acceso para ver y resolver evaluaciones, y CRUD segun permiso por rol.'],
            'discipular_evaluaciones_fechas' => ['Discipular: Configurar fechas de evaluaciones', 'Permite definir las fechas en las que cada evaluacion estara habilitada para alumnos.'],
            'programas' => ['Programas (consolidado)', 'Menu Programas y registro consolidado. El CRUD limita acciones globales; use acciones avanzadas para UV vs Capacitacion Destino.'],
        ],
        'Material' => [
            'material' => ['Material (panel inicio)', 'Tarjeta del inicio y acceso a home/material. Si desmarcas solo esto, quien tenga solo «Material: Universidad de la Vida» podrá seguir abriendo documentos desde Programas (enlace Material U.V), pero no verá el centro general. Para ocultar todo el material UV, desactiva también ese submódulo.'],
            'materiales_celulas' => ['Material: Celulas', 'Submodulo de material para celulas (permiso dedicado).'],
            'teen' => ['Material: Teens', 'Submodulo de material Teens (permiso dedicado).'],
            'material_universidad_vida' => ['Material: Universidad de la Vida', 'Submodulo independiente de material para Universidad de la Vida.'],
            'material_capacitacion_destino' => ['Material: Capacitacion Destino', 'Submodulo independiente de material para Capacitacion Destino.'],
            'material_capacitacion_destino_subir' => ['Material: Capacitacion Destino (Subir archivos)', 'Define quien puede subir archivos en Capacitacion Destino sin abrir todo el CRUD del modulo.'],
        ],
        'Obsequios' => [
            'entrega_obsequio'   => ['Entrega de Obsequios',  'Registrar entrega de obsequios'],
            'registro_obsequio'  => ['Registro de Obsequios', 'Consultar registros de obsequios'],
        ],
        'Nehemias' => [
            'nehemias'                => ['Nehemias (general)',             'Acceso al modulo Nehemias'],
            'nehemias_cols_cedula'    => ['Ver: Cedula',                   'Columna cedula en Nehemias'],
            'nehemias_cols_telefono'  => ['Ver: Telefono',                 'Columna telefono en Nehemias'],
            'nehemias_cols_subido_link'=> ['Ver: Link subido',             'Columna link subido en Nehemias'],
            'nehemias_cols_bogota_subio'=> ['Ver: En Bogota se le subio',  'Columna especifica del reporte'],
            'nehemias_cols_puesto'    => ['Ver: Puesto',                   'Columna puesto en Nehemias'],
            'nehemias_cols_acepta'    => ['Ver: Acepta',                   'Columna acepta en Nehemias'],
            'nehemias_acciones_editar'=> ['Boton Editar',                  'Permite editar registros Nehemias'],
            'nehemias_acciones_eliminar'=> ['Boton Eliminar',              'Permite eliminar registros Nehemias'],
        ],
        'Sistema' => [
            'roles'    => ['Roles',    'Gestion de roles de usuario'],
            'permisos' => ['Permisos', 'Administracion de permisos'],
        ],
    ];
    ?>

    <?php foreach ($roles as $i => $rol):
        $idRol = (int)($rol['Id_Rol'] ?? 0);
        $esRolProtegido = in_array($idRol, $rolesProtegidos, true);
    ?>
    <div class="perm-panel <?= $i === 0 ? 'active' : '' ?>" id="rol-<?= $idRol ?>">

        <!-- Cabecera del panel -->
        <div class="perm-panel-header">
            <h3 class="perm-panel-title">
                <i class="bi bi-person-badge"></i>
                <?= htmlspecialchars($rol['Nombre_Rol']) ?>
            </h3>
            <div class="perm-panel-actions">
                <?php if ($esRolProtegido): ?>
                <span class="perm-acceso-total-badge">
                    <i class="bi bi-shield-fill-check"></i> Rol protegido por sistema
                </span>
                <?php else: ?>
                <button type="button" class="btn-perm-all btn-perm-solo-ver"
                    data-rol="<?= $idRol ?>" data-accion="solo_ver">
                    <i class="bi bi-eye"></i> Solo leer todo
                </button>
                <button type="button" class="btn-perm-all btn-perm-activar"
                    data-rol="<?= $idRol ?>" data-accion="activar">
                    <i class="bi bi-check-all"></i> Activar Todo
                </button>
                <button type="button" class="btn-perm-all btn-perm-quitar"
                    data-rol="<?= $idRol ?>" data-accion="desactivar">
                    <i class="bi bi-slash-circle"></i> Quitar Todo
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla de modulos -->
        <table class="perm-table">
            <thead>
                <tr>
                    <th>Modulo</th>
                    <th>Ver</th>
                    <th>Crear</th>
                    <th>Editar</th>
                    <th>Eliminar</th>
                </tr>
            </thead>
            <tbody>
                <?php $modulosMostrados = []; ?>
                <?php foreach ($gruposModulos as $grupoNombre => $grupoItems):
                    // Filtrar solo los modulos que existen en $modulos
                    $itemsVis = array_filter($grupoItems, fn($k) => isset($modulos[$k]), ARRAY_FILTER_USE_KEY);
                    if (empty($itemsVis)) continue;
                ?>
                <tr class="perm-group-header">
                    <td colspan="5"><?= $grupoNombre ?></td>
                </tr>
                <?php foreach ($itemsVis as $mk => [$mnombre, $mdesc]):
                    $modulosMostrados[$mk] = true;
                    $permiso = $permisos[$idRol][$mk] ?? null;
                    $pVer  = $permiso ? (int)$permiso['Puede_Ver']    : 0;
                    $pCre  = $permiso ? (int)$permiso['Puede_Crear']  : 0;
                    $pEdi  = $permiso ? (int)$permiso['Puede_Editar'] : 0;
                    $pEli  = $permiso ? (int)$permiso['Puede_Eliminar']:0;
                    $advActions = $catalogo_acciones_modulo[$mk] ?? [];
                    $extrasMap = $permiso ? PermisosCatalogo::mapaDesdeFila((array)$permiso) : [];
                ?>
                <tr>
                    <td class="perm-name">
                        <?= htmlspecialchars($mnombre) ?>
                        <small><?= htmlspecialchars($mdesc) ?></small>
                        <?php if (!empty($advActions)): ?>
                        <div class="perm-adv-inline">
                            <span class="perm-adv-inline-title">Acciones avanzadas</span>
                            <div class="perm-adv-wrap">
                                <?php foreach ($advActions as $ak => $meta):
                                    $pAdv = !empty($extrasMap[$ak]);
                                    $ml = (string)($meta['label'] ?? $ak);
                                    $md = (string)($meta['descripcion'] ?? '');
                                ?>
                                <label class="perm-adv-item">
                                    <input type="checkbox"
                                        class="perm-cb permiso-check"
                                        data-rol="<?= $idRol ?>"
                                        data-modulo="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>"
                                        data-campo="<?= htmlspecialchars('extra:' . $ak, ENT_QUOTES, 'UTF-8') ?>"
                                        title="<?= htmlspecialchars($ml . ' — ' . $md, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $pAdv ? 'checked' : '' ?>
                                        <?= $esRolProtegido ? 'disabled' : '' ?>>
                                    <span><strong><?= htmlspecialchars($ml) ?></strong><small><?= htmlspecialchars($md) ?></small></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php foreach ([
                        ['puede_ver',      $pVer, 'Ver'],
                        ['puede_crear',    $pCre, 'Crear'],
                        ['puede_editar',   $pEdi, 'Editar'],
                        ['puede_eliminar', $pEli, 'Eliminar'],
                    ] as [$campo, $val, $label]): ?>
                    <td class="perm-check-cell">
                        <input type="checkbox"
                            class="perm-cb permiso-check"
                            data-rol="<?= $idRol ?>"
                            data-modulo="<?= $mk ?>"
                            data-campo="<?= $campo ?>"
                            title="<?= $label ?> - <?= htmlspecialchars($mnombre) ?> (<?= htmlspecialchars($rol['Nombre_Rol']) ?>)"
                            <?= $val ? 'checked' : '' ?>
                            <?= $esRolProtegido ? 'disabled' : '' ?>>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>

                <?php
                    $modulosRestantes = array_diff_key($modulos, $modulosMostrados);
                ?>
                <?php if (!empty($modulosRestantes)): ?>
                <tr class="perm-group-header">
                    <td colspan="5">Otros modulos detectados</td>
                </tr>
                <?php foreach ($modulosRestantes as $mk => $mnombre):
                    $permiso = $permisos[$idRol][$mk] ?? null;
                    $pVer  = $permiso ? (int)$permiso['Puede_Ver']    : 0;
                    $pCre  = $permiso ? (int)$permiso['Puede_Crear']  : 0;
                    $pEdi  = $permiso ? (int)$permiso['Puede_Editar'] : 0;
                    $pEli  = $permiso ? (int)$permiso['Puede_Eliminar']:0;
                    $advActions = $catalogo_acciones_modulo[$mk] ?? [];
                    $extrasMap = $permiso ? PermisosCatalogo::mapaDesdeFila((array)$permiso) : [];
                ?>
                <tr>
                    <td class="perm-name">
                        <?= htmlspecialchars((string)$mnombre) ?>
                        <small>Modulo detectado automaticamente en BD o codigo.</small>
                        <?php if (!empty($advActions)): ?>
                        <div class="perm-adv-inline">
                            <span class="perm-adv-inline-title">Acciones avanzadas</span>
                            <div class="perm-adv-wrap">
                                <?php foreach ($advActions as $ak => $meta):
                                    $pAdv = !empty($extrasMap[$ak]);
                                    $ml = (string)($meta['label'] ?? $ak);
                                    $md = (string)($meta['descripcion'] ?? '');
                                ?>
                                <label class="perm-adv-item">
                                    <input type="checkbox"
                                        class="perm-cb permiso-check"
                                        data-rol="<?= $idRol ?>"
                                        data-modulo="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>"
                                        data-campo="<?= htmlspecialchars('extra:' . $ak, ENT_QUOTES, 'UTF-8') ?>"
                                        title="<?= htmlspecialchars($ml . ' — ' . $md, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $pAdv ? 'checked' : '' ?>
                                        <?= $esRolProtegido ? 'disabled' : '' ?>>
                                    <span><strong><?= htmlspecialchars($ml) ?></strong><small><?= htmlspecialchars($md) ?></small></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php foreach ([
                        ['puede_ver',      $pVer, 'Ver'],
                        ['puede_crear',    $pCre, 'Crear'],
                        ['puede_editar',   $pEdi, 'Editar'],
                        ['puede_eliminar', $pEli, 'Eliminar'],
                    ] as [$campo, $val, $label]): ?>
                    <td class="perm-check-cell">
                        <input type="checkbox"
                            class="perm-cb permiso-check"
                            data-rol="<?= $idRol ?>"
                            data-modulo="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>"
                            data-campo="<?= $campo ?>"
                            title="<?= $label ?> - <?= htmlspecialchars((string)$mnombre) ?> (<?= htmlspecialchars($rol['Nombre_Rol']) ?>)"
                            <?= $val ? 'checked' : '' ?>
                            <?= $esRolProtegido ? 'disabled' : '' ?>>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>

    </div>

    <div id="perm-view-modulos" class="perm-view-panel">
        <div class="perm-module-grid">
            <div class="perm-module-grid-head">
                <h3><i class="bi bi-diagram-3"></i> Módulos y roles (editable)</h3>
                <input type="text" id="perm-module-search" class="perm-module-search" placeholder="Buscar módulo o funcionalidad...">
            </div>
            <div class="perm-role-total" style="margin-bottom:10px;">Check marcado = permiso activo. Check desmarcado = sin permiso.</div>
            <div class="perm-module-table-wrap">
                <table class="perm-module-table" id="perm-module-table">
                    <thead>
                        <tr>
                            <th>Módulo / funcionalidad</th>
                            <?php foreach ($roles as $rolCab): ?>
                                <th><?= htmlspecialchars((string)$rolCab['Nombre_Rol']) ?></th>
                            <?php endforeach; ?>
                            <th>Total roles con ver</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $modulosMostradosVistaModulos = [];
                        foreach ($gruposModulos as $grupoNombre => $grupoItems):
                            $itemsVis = array_filter($grupoItems, fn($k) => isset($modulos[$k]), ARRAY_FILTER_USE_KEY);
                            if (empty($itemsVis)) {
                                continue;
                            }
                        ?>
                        <tr class="perm-group-header">
                            <td colspan="<?= count($roles) + 2 ?>"><?= htmlspecialchars((string)$grupoNombre) ?></td>
                        </tr>
                        <?php foreach ($itemsVis as $mk => [$mnombre, $mdesc]):
                            $modulosMostradosVistaModulos[$mk] = true;
                            $totalRolesConVer = 0;
                        ?>
                        <tr class="perm-module-row" data-module-key="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>" data-module-search="<?= htmlspecialchars(strtolower((string)$mnombre . ' ' . (string)$mdesc . ' ' . (string)$mk), ENT_QUOTES, 'UTF-8') ?>">
                            <td>
                                <strong><?= htmlspecialchars((string)$mnombre) ?></strong>
                                <div class="perm-role-total"><?= htmlspecialchars((string)$mdesc) ?></div>
                                <div class="perm-role-total" style="margin-top:8px;font-style:italic;color:#64748b;">
                                    Las acciones avanzadas (exportar, sub-módulos, etc.) se gestionan en la vista <strong>Por rol</strong>.
                                </div>
                            </td>
                            <?php foreach ($roles as $rolCol):
                                $idRolCol = (int)($rolCol['Id_Rol'] ?? 0);
                                $esRolProtegidoCol = in_array($idRolCol, $rolesProtegidos, true);
                                $permisoCol = $permisos[$idRolCol][$mk] ?? null;
                                $pv = !empty($permisoCol['Puede_Ver']);
                                $pc = !empty($permisoCol['Puede_Crear']);
                                $pe = !empty($permisoCol['Puede_Editar']);
                                $pd = !empty($permisoCol['Puede_Eliminar']);
                                if ($pv) {
                                    $totalRolesConVer++;
                                }
                            ?>
                            <td>
                                <span class="perm-badges" title="V=Ver, C=Crear, E=Editar, D=Eliminar">
                                    <label class="perm-module-action" title="Ver - <?= htmlspecialchars((string)$mnombre) ?> (<?= htmlspecialchars((string)$rolCol['Nombre_Rol']) ?>)">
                                        <input type="checkbox"
                                            class="perm-cb permiso-check"
                                            data-rol="<?= $idRolCol ?>"
                                            data-modulo="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>"
                                            data-campo="puede_ver"
                                            <?= $pv ? 'checked' : '' ?>
                                            <?= $esRolProtegidoCol ? 'disabled' : '' ?>>
                                        V
                                    </label>
                                    <label class="perm-module-action" title="Crear - <?= htmlspecialchars((string)$mnombre) ?> (<?= htmlspecialchars((string)$rolCol['Nombre_Rol']) ?>)">
                                        <input type="checkbox"
                                            class="perm-cb permiso-check"
                                            data-rol="<?= $idRolCol ?>"
                                            data-modulo="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>"
                                            data-campo="puede_crear"
                                            <?= $pc ? 'checked' : '' ?>
                                            <?= $esRolProtegidoCol ? 'disabled' : '' ?>>
                                        C
                                    </label>
                                    <label class="perm-module-action" title="Editar - <?= htmlspecialchars((string)$mnombre) ?> (<?= htmlspecialchars((string)$rolCol['Nombre_Rol']) ?>)">
                                        <input type="checkbox"
                                            class="perm-cb permiso-check"
                                            data-rol="<?= $idRolCol ?>"
                                            data-modulo="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>"
                                            data-campo="puede_editar"
                                            <?= $pe ? 'checked' : '' ?>
                                            <?= $esRolProtegidoCol ? 'disabled' : '' ?>>
                                        E
                                    </label>
                                    <label class="perm-module-action" title="Eliminar - <?= htmlspecialchars((string)$mnombre) ?> (<?= htmlspecialchars((string)$rolCol['Nombre_Rol']) ?>)">
                                        <input type="checkbox"
                                            class="perm-cb permiso-check"
                                            data-rol="<?= $idRolCol ?>"
                                            data-modulo="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>"
                                            data-campo="puede_eliminar"
                                            <?= $pd ? 'checked' : '' ?>
                                            <?= $esRolProtegidoCol ? 'disabled' : '' ?>>
                                        D
                                    </label>
                                </span>
                            </td>
                            <?php endforeach; ?>
                            <td class="perm-module-total-ver"><strong><?= (int)$totalRolesConVer ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endforeach; ?>

                        <?php
                        $modulosRestantesVistaModulos = array_diff_key($modulos, $modulosMostradosVistaModulos);
                        if (!empty($modulosRestantesVistaModulos)):
                        ?>
                        <tr class="perm-group-header">
                            <td colspan="<?= count($roles) + 2 ?>">Otros módulos detectados</td>
                        </tr>
                        <?php foreach ($modulosRestantesVistaModulos as $mk => $mnombre):
                            $totalRolesConVer = 0;
                        ?>
                        <tr class="perm-module-row" data-module-key="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>" data-module-search="<?= htmlspecialchars(strtolower((string)$mnombre . ' ' . (string)$mk), ENT_QUOTES, 'UTF-8') ?>">
                            <td>
                                <strong><?= htmlspecialchars((string)$mnombre) ?></strong>
                                <div class="perm-role-total">Módulo detectado automáticamente.</div>
                                <div class="perm-role-total" style="margin-top:8px;font-style:italic;color:#64748b;">
                                    Acciones avanzadas: vista <strong>Por rol</strong>.
                                </div>
                            </td>
                            <?php foreach ($roles as $rolCol):
                                $idRolCol = (int)($rolCol['Id_Rol'] ?? 0);
                                $esRolProtegidoCol = in_array($idRolCol, $rolesProtegidos, true);
                                $permisoCol = $permisos[$idRolCol][$mk] ?? null;
                                $pv = !empty($permisoCol['Puede_Ver']);
                                $pc = !empty($permisoCol['Puede_Crear']);
                                $pe = !empty($permisoCol['Puede_Editar']);
                                $pd = !empty($permisoCol['Puede_Eliminar']);
                                if ($pv) {
                                    $totalRolesConVer++;
                                }
                            ?>
                            <td>
                                <span class="perm-badges" title="V=Ver, C=Crear, E=Editar, D=Eliminar">
                                    <label class="perm-module-action" title="Ver - <?= htmlspecialchars((string)$mnombre) ?> (<?= htmlspecialchars((string)$rolCol['Nombre_Rol']) ?>)">
                                        <input type="checkbox"
                                            class="perm-cb permiso-check"
                                            data-rol="<?= $idRolCol ?>"
                                            data-modulo="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>"
                                            data-campo="puede_ver"
                                            <?= $pv ? 'checked' : '' ?>
                                            <?= $esRolProtegidoCol ? 'disabled' : '' ?>>
                                        V
                                    </label>
                                    <label class="perm-module-action" title="Crear - <?= htmlspecialchars((string)$mnombre) ?> (<?= htmlspecialchars((string)$rolCol['Nombre_Rol']) ?>)">
                                        <input type="checkbox"
                                            class="perm-cb permiso-check"
                                            data-rol="<?= $idRolCol ?>"
                                            data-modulo="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>"
                                            data-campo="puede_crear"
                                            <?= $pc ? 'checked' : '' ?>
                                            <?= $esRolProtegidoCol ? 'disabled' : '' ?>>
                                        C
                                    </label>
                                    <label class="perm-module-action" title="Editar - <?= htmlspecialchars((string)$mnombre) ?> (<?= htmlspecialchars((string)$rolCol['Nombre_Rol']) ?>)">
                                        <input type="checkbox"
                                            class="perm-cb permiso-check"
                                            data-rol="<?= $idRolCol ?>"
                                            data-modulo="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>"
                                            data-campo="puede_editar"
                                            <?= $pe ? 'checked' : '' ?>
                                            <?= $esRolProtegidoCol ? 'disabled' : '' ?>>
                                        E
                                    </label>
                                    <label class="perm-module-action" title="Eliminar - <?= htmlspecialchars((string)$mnombre) ?> (<?= htmlspecialchars((string)$rolCol['Nombre_Rol']) ?>)">
                                        <input type="checkbox"
                                            class="perm-cb permiso-check"
                                            data-rol="<?= $idRolCol ?>"
                                            data-modulo="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>"
                                            data-campo="puede_eliminar"
                                            <?= $pd ? 'checked' : '' ?>
                                            <?= $esRolProtegidoCol ? 'disabled' : '' ?>>
                                        D
                                    </label>
                                </span>
                            </td>
                            <?php endforeach; ?>
                            <td class="perm-module-total-ver"><strong><?= (int)$totalRolesConVer ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- .perm-page -->

<!-- Toast de confirmacion -->
<div id="perm-toast"><i class="bi bi-check-circle"></i> <span id="perm-toast-msg">Permiso actualizado</span></div>

<script>
(function () {
    const ENDPOINT = <?= json_encode(
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        . strtok($_SERVER['REQUEST_URI'] ?? '/public/', '?')
        . '?url=permisos/actualizar'
    ) ?>;

    const ENDPOINT_LIMPIEZA = <?= json_encode(
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        . strtok($_SERVER['REQUEST_URI'] ?? '/public/', '?')
        . '?url=permisos/limpiar-obsoletos'
    ) ?>;

    /* Toast */
    let toastTimer = null;
    function showToast(msg, tipo) {
        const el = document.getElementById('perm-toast');
        const msgEl = document.getElementById('perm-toast-msg');
        msgEl.textContent = msg;
        el.classList.toggle('error', tipo === 'error');
        el.style.display = 'block';
        el.style.opacity = '1';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { el.style.opacity = '0'; setTimeout(() => { el.style.display = 'none'; el.style.opacity = '1'; }, 300); }, 2500);
    }

    /* Actualizar badge de pestana */
    function actualizarBadge(idRol) {
        const panel = document.getElementById('rol-' + idRol);
        if (!panel) return;
        const total   = panel.querySelectorAll('.permiso-check[data-campo="puede_ver"]').length;
        const activos = panel.querySelectorAll('.permiso-check[data-campo="puede_ver"]:checked').length;
        const tab = document.querySelector(`.perm-tab-btn[data-tab="rol-${idRol}"] .perm-tab-badge`);
        if (tab) tab.textContent = activos + '/' + total;
    }

    function syncPermisoCheckboxes(rol, modulo, campo, checked) {
        const selector = `.permiso-check[data-rol="${String(rol).replace(/"/g, '\\"')}"][data-modulo="${String(modulo).replace(/"/g, '\\"')}"][data-campo="${String(campo).replace(/"/g, '\\"')}"]`;
        document.querySelectorAll(selector).forEach((cbSync) => {
            cbSync.checked = !!checked;
        });
    }

    function actualizarTotalesVistaModulos(modulo) {
        const selector = `.perm-module-row[data-module-key="${String(modulo).replace(/"/g, '\\"')}"]`;
        document.querySelectorAll(selector).forEach((row) => {
            const totalConVer = row.querySelectorAll('.permiso-check[data-campo="puede_ver"]:checked').length;
            const totalCell = row.querySelector('.perm-module-total-ver');
            if (totalCell) {
                totalCell.innerHTML = `<strong>${totalConVer}</strong>`;
            }
        });
    }

    /* Guardar un permiso */
    function guardarPermiso(cb, onError) {
        const { rol, modulo, campo } = cb.dataset;
        const valor = cb.checked ? 1 : 0;

        fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: `id_rol=${encodeURIComponent(rol)}&modulo=${encodeURIComponent(modulo)}&campo=${encodeURIComponent(campo)}&valor=${encodeURIComponent(valor)}`
        })
        .then(r => r.text()).then(txt => {
            const data = JSON.parse(txt);
            if (!data.success) throw new Error(data.error || 'Error desconocido');
            syncPermisoCheckboxes(rol, modulo, campo, cb.checked);
            actualizarBadge(rol);
            actualizarTotalesVistaModulos(modulo);
            showToast('Permiso actualizado correctamente');
        })
        .catch(err => {
            cb.checked = !cb.checked;
            showToast('Error: ' + err.message, 'error');
            if (onError) onError();
        });
    }

    /* Checkbox individual */
    document.querySelectorAll('.permiso-check').forEach(cb => {
        cb.addEventListener('change', function () { guardarPermiso(this); });
    });

    /* Botones masivos */
    document.querySelectorAll('.btn-perm-all').forEach(btn => {
        btn.addEventListener('click', function () {
            const idRol = this.dataset.rol;
            const accion = this.dataset.accion;

            const labels = { activar: 'Activar TODO', desactivar: 'Quitar TODO', solo_ver: 'Dejar solo lectura' };
            if (!confirm('¿' + (labels[accion] || accion) + ' para este rol?')) return;

            const panel = document.getElementById('rol-' + idRol);
            const cbs   = panel.querySelectorAll('.permiso-check');

            let lista = [];

            if (accion === 'activar') {
                cbs.forEach(cb => { cb.checked = true; lista.push(cb); });
            } else if (accion === 'desactivar') {
                cbs.forEach(cb => { cb.checked = false; lista.push(cb); });
            } else if (accion === 'solo_ver') {
                cbs.forEach(cb => {
                    cb.checked = (cb.dataset.campo === 'puede_ver');
                    lista.push(cb);
                });
            }

            cbs.forEach(cb => { cb.disabled = true; });

            const promesas = lista.map(cb => {
                const { rol, modulo, campo } = cb.dataset;
                const valor = cb.checked ? 1 : 0;
                return fetch(ENDPOINT, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                    body: `id_rol=${encodeURIComponent(rol)}&modulo=${encodeURIComponent(modulo)}&campo=${encodeURIComponent(campo)}&valor=${encodeURIComponent(valor)}`
                })
                .then(r => r.text())
                .then(txt => {
                    let data;
                    try {
                        data = JSON.parse(txt);
                    } catch (e) {
                        throw new Error('Respuesta inválida del servidor');
                    }

                    if (!data || !data.success) {
                        throw new Error(data?.error || 'Error al guardar permisos');
                    }

                    return true;
                });
            });

            Promise.all(promesas)
                .then(() => {
                    const modulosAfectados = new Set();
                    lista.forEach((cb) => {
                        syncPermisoCheckboxes(cb.dataset.rol, cb.dataset.modulo, cb.dataset.campo, cb.checked);
                        modulosAfectados.add(cb.dataset.modulo);
                    });
                    modulosAfectados.forEach((mk) => actualizarTotalesVistaModulos(mk));
                    cbs.forEach(cb => { cb.disabled = false; });
                    actualizarBadge(idRol);
                    showToast('Permisos actualizados correctamente');
                })
                .catch(err => {
                    cbs.forEach(cb => { cb.disabled = false; });
                    showToast('Error al actualizar: ' + err.message, 'error');
                });
        });
    });

    /* Pestanas */
    document.querySelectorAll('.perm-tab-btn').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.perm-tab-btn').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.perm-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const panel = document.getElementById(this.dataset.tab);
            if (panel) panel.classList.add('active');
        });
    });

    const searchInput = document.getElementById('perm-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = String(this.value || '').toLowerCase().trim();
            document.querySelectorAll('.perm-panel').forEach((panel) => {
                const rows = panel.querySelectorAll('tbody tr:not(.perm-group-header)');
                rows.forEach((row) => {
                    const texto = String(row.querySelector('.perm-name')?.innerText || '').toLowerCase();
                    row.style.display = (term === '' || texto.includes(term)) ? '' : 'none';
                });

                panel.querySelectorAll('tbody tr.perm-group-header').forEach((headerRow) => {
                    let next = headerRow.nextElementSibling;
                    let hayVisible = false;
                    while (next && !next.classList.contains('perm-group-header')) {
                        if (next.style.display !== 'none') {
                            hayVisible = true;
                            break;
                        }
                        next = next.nextElementSibling;
                    }
                    headerRow.style.display = hayVisible ? '' : 'none';
                });
            });
        });
    }

    const viewButtons = document.querySelectorAll('.perm-view-btn');
    const viewRoles = document.getElementById('perm-view-roles');
    const viewModulos = document.getElementById('perm-view-modulos');

    viewButtons.forEach((btn) => {
        btn.addEventListener('click', function () {
            const view = String(this.dataset.view || 'roles');
            viewButtons.forEach((b) => {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');

            if (view === 'modulos') {
                viewRoles.classList.remove('active');
                viewModulos.classList.add('active');
            } else {
                viewModulos.classList.remove('active');
                viewRoles.classList.add('active');
            }
        });
    });

    const moduleSearch = document.getElementById('perm-module-search');
    if (moduleSearch) {
        moduleSearch.addEventListener('input', function () {
            const term = String(this.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.perm-module-row');

            rows.forEach((row) => {
                const hay = String(row.dataset.moduleSearch || '').includes(term);
                row.style.display = (term === '' || hay) ? '' : 'none';
            });

            document.querySelectorAll('#perm-module-table tbody tr.perm-group-header').forEach((headerRow) => {
                let next = headerRow.nextElementSibling;
                let hayVisible = false;
                while (next && !next.classList.contains('perm-group-header')) {
                    if (next.style.display !== 'none') {
                        hayVisible = true;
                        break;
                    }
                    next = next.nextElementSibling;
                }
                headerRow.style.display = hayVisible ? '' : 'none';
            });
        });
    }

    const btnLimpiarObsoletos = document.getElementById('btnLimpiarModulosObsoletos');
    if (btnLimpiarObsoletos) {
        btnLimpiarObsoletos.addEventListener('click', function () {
            if (!confirm('¿Deseas eliminar de la tabla permisos los módulos obsoletos detectados?')) {
                return;
            }

            btnLimpiarObsoletos.disabled = true;

            fetch(ENDPOINT_LIMPIEZA, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                body: 'confirm=1'
            })
            .then(r => r.text())
            .then(txt => {
                const data = JSON.parse(txt);
                if (!data.success) {
                    throw new Error(data.error || 'No se pudo limpiar');
                }

                showToast('Limpieza completada: ' + String(data.deleted_rows || 0) + ' filas eliminadas');
                setTimeout(function() { window.location.reload(); }, 500);
            })
            .catch(err => {
                btnLimpiarObsoletos.disabled = false;
                showToast('Error al limpiar: ' + err.message, 'error');
            });
        });
    }

})();
</script>

<?php require_once VIEWS . '/layout/footer.php'; ?>

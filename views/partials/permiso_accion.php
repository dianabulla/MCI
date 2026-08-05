<?php
/**
 * Muestra el contenido del bloque solo si el usuario tiene permiso.
 * Uso en vista:
 *   $permisoModulo = 'personas';
 *   $permisoAccion = 'crear';
 *   include VIEWS . '/partials/permiso_accion.php';
 *
 * O con acción granular UI:
 *   $permisoUiSubmodulo = 'personas';
 *   $permisoUiAccion = 'exportar_excel';
 *   $permisoUiGranular = true;
 */
if (!class_exists('AuthController') || !AuthController::estaAutenticado()) {
    return;
}

$mostrarPermiso = false;
if (!empty($permisoUiGranular) && !empty($permisoUiSubmodulo) && !empty($permisoUiAccion)) {
    require_once APP . '/Helpers/PermisosUiBuilder.php';
    $mostrarPermiso = PermisosUiBuilder::puedeAccionGranular(
        (string)$permisoUiSubmodulo,
        (string)$permisoUiAccion
    );
} elseif (!empty($permisoModulo)) {
    $mostrarPermiso = AuthController::puedeMostrarAccionModulo(
        (string)$permisoModulo,
        (string)($permisoAccion ?? 'ver')
    );
}

if (!$mostrarPermiso) {
    return;
}

if (isset($permisoContenido) && is_callable($permisoContenido)) {
    $permisoContenido();
} elseif (isset($permisoHtml)) {
    echo (string)$permisoHtml;
}

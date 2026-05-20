<?php
/**
 * Resuelve permisos granulares (columnas / acciones) por submódulo real.
 */
class PermisosUiBuilder {

    /**
     * Acción granular (exportar, editar en fila Nehemías, columna, etc.).
     */
    public static function puedeAccionGranular(string $submodulo, string $accion): bool {
        $accion = strtolower(trim($accion));
        if ($accion === '') {
            return false;
        }

        if (!PermisosUiCatalogo::tieneAccionUi($submodulo, $accion)) {
            return AuthController::tienePermiso($submodulo, $accion);
        }

        return AuthController::puede(PermisosUiCatalogo::claveAccion($submodulo, $accion) . ':ver');
    }

    public static function verColumna(string $submodulo, string $columna): bool {
        $cfg = PermisosUiCatalogo::configuracionPorSubmodulo()[$submodulo]['columnas'] ?? [];
        if (!isset($cfg[$columna])) {
            return true;
        }

        return AuthController::puede(PermisosUiCatalogo::claveColumna($submodulo, $columna) . ':ver');
    }

    /**
     * @return array<string, bool>
     */
    public static function construir(string $submodulo): array {
        $ui = ['modulo' => $submodulo];
        $cfg = PermisosUiCatalogo::configuracionPorSubmodulo()[$submodulo] ?? null;
        if ($cfg === null) {
            return $ui;
        }

        foreach ($cfg['columnas'] as $col => $_label) {
            $ui['ver_' . $col] = self::verColumna($submodulo, (string)$col);
        }

        foreach ($cfg['acciones'] as $accion => $_label) {
            if (in_array($accion, ['editar', 'eliminar'], true) && $submodulo === 'nehemias') {
                $ui['mostrar_boton_' . $accion] = self::puedeAccionGranular($submodulo, $accion);
            } else {
                $ui['mostrar_accion_' . $accion] = self::puedeAccionGranular($submodulo, $accion);
            }
        }

        return $ui;
    }

    /** @deprecated */
    public static function mostrarBoton(string $moduloBase, string $accion): bool {
        return self::puedeAccionGranular($moduloBase, $accion);
    }
}

<?php
/**
 * Permisos granulares del módulo Programas (UV, Cap. Destino, pagos, dashboards, etc.).
 */
require_once APP . '/Helpers/PermisosUiBuilder.php';

class PermisosProgramasAccess {

    public const LINEA_UV = 'universidad_vida';
    public const LINEA_CAP = 'capacitacion_destino';

    /**
     * Acceso amplio legacy: solo administrador o coordinación total.
     * «Ver» del módulo Programas ya NO desbloquea todas las acciones granulares.
     */
    public static function tieneAccesoCompletoProgramas(): bool {
        if (!class_exists('AuthController', false)) {
            return false;
        }
        if (AuthController::esAdministrador()) {
            return true;
        }

        return AuthController::tieneCoordinacionTotalProgramas();
    }

    /**
     * Acción granular en módulo programas (submódulo programas_acciones_* o JSON legado).
     */
    public static function puedeAccionPrograma(string $accion): bool {
        $accion = strtolower(trim($accion));
        if ($accion === '') {
            return false;
        }
        if (self::tieneAccesoCompletoProgramas()) {
            return true;
        }

        if (PermisosUiBuilder::puedeAccionGranular('programas', $accion)) {
            return true;
        }

        return AuthController::tienePermiso('programas', $accion);
    }

    public static function puedeVerLineaUniversidadVida(): bool {
        return self::puedeAccionPrograma('ver_universidad_vida');
    }

    public static function puedeVerLineaCapacitacionDestino(): bool {
        return self::puedeAccionPrograma('ver_capacitacion_destino');
    }

    public static function puedeVerConsolidadoUniversidadVida(): bool {
        return self::puedeVerLineaUniversidadVida();
    }

    public static function puedeVerConsolidadoCapacitacionDestino(): bool {
        return self::puedeVerLineaCapacitacionDestino();
    }

    public static function puedeVerDashboardUniversidadVida(): bool {
        if (AuthController::esAdministrador()) {
            return true;
        }
        return self::puedeAccionPrograma('dashboard_universidad_vida');
    }

    public static function puedeVerDashboardCapacitacionDestino(): bool {
        if (AuthController::esAdministrador()) {
            return true;
        }
        return self::puedeAccionPrograma('dashboard_capacitacion_destino');
    }

    public static function puedeGestionarPagosUniversidadVida(): bool {
        if (self::tieneAccesoCompletoProgramas()) {
            return true;
        }
        return self::puedeAccionPrograma('gestionar_pagos_universidad_vida');
    }

    public static function puedeGestionarPagosCapacitacionDestino(): bool {
        if (self::tieneAccesoCompletoProgramas()) {
            return true;
        }
        return self::puedeAccionPrograma('gestionar_pagos_capacitacion_destino');
    }

    public static function puedeVerFormularioUniversidadVida(): bool {
        return self::puedeAccionPrograma('formulario_universidad_vida');
    }

    public static function puedeVerFormularioCapacitacionDestino(): bool {
        return self::puedeAccionPrograma('formulario_capacitacion_destino');
    }

    public static function puedeVerAsistenciasUniversidadVida(): bool {
        if (self::tieneAccesoCompletoProgramas()) {
            return true;
        }
        return self::puedeAccionPrograma('asistencias_universidad_vida');
    }

    public static function puedeExportarConsolidado(): bool {
        return self::puedeAccionPrograma('exportar_consolidado');
    }

    public static function puedeVerMaterialUniversidadVida(): bool {
        return AuthController::esAdministrador() || AuthController::puede('material_universidad_vida:ver');
    }

    public static function puedeVerMaterialCapacitacionDestino(): bool {
        return AuthController::esAdministrador() || AuthController::puede('material_capacitacion_destino:ver');
    }

    /**
     * @deprecated usar métodos por línea
     */
    public static function puedeVerDashboardEscuelasLinea(string $linea): bool {
        $linea = strtolower(trim($linea));
        if ($linea === self::LINEA_UV) {
            return self::puedeVerDashboardUniversidadVida();
        }
        if ($linea === self::LINEA_CAP) {
            return self::puedeVerDashboardCapacitacionDestino();
        }
        return false;
    }

    /**
     * Flags para tarjetas en programas/landing y HomeController.
     *
     * @return array<string, mixed>
     */
    public static function permisosUiLinea(string $linea): array {
        if ($linea === self::LINEA_UV) {
            return [
                'ver_linea' => self::puedeVerLineaUniversidadVida(),
                'consolidado' => self::puedeVerConsolidadoUniversidadVida(),
                'asistencias' => self::puedeVerAsistenciasUniversidadVida(),
                'dashboard' => self::puedeVerDashboardUniversidadVida(),
                'pagos' => self::puedeGestionarPagosUniversidadVida(),
                'formulario' => self::puedeVerFormularioUniversidadVida(),
                'material' => self::puedeVerMaterialUniversidadVida(),
                'exportar' => self::puedeExportarConsolidado(),
            ];
        }

        return [
            'ver_linea' => self::puedeVerLineaCapacitacionDestino(),
            'consolidado' => self::puedeVerConsolidadoCapacitacionDestino(),
            'asistencias' => false,
            'dashboard' => self::puedeVerDashboardCapacitacionDestino(),
            'pagos' => self::puedeGestionarPagosCapacitacionDestino(),
            'formulario' => self::puedeVerFormularioCapacitacionDestino(),
            'material' => self::puedeVerMaterialCapacitacionDestino(),
            'exportar' => self::puedeExportarConsolidado(),
        ];
    }
}

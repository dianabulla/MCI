<?php
/**
 * Acceso a módulos de administración del sistema (permisos, cuentas).
 * Fase B RBAC: delegar sin ser administrador global.
 */
class GestionSistemaAccess {

    public static function puedeVerMatrizPermisos(): bool {
        return AuthController::esAdministrador()
            || AuthController::puede('permisos:ver');
    }

    public static function puedeEditarMatrizPermisos(): bool {
        return AuthController::esAdministrador()
            || AuthController::puede('permisos:editar');
    }

    public static function puedeVerCuentas(): bool {
        return AuthController::esAdministrador()
            || AuthController::puede('cuentas:ver');
    }

    public static function puedeCrearCuentas(): bool {
        return AuthController::esAdministrador()
            || AuthController::puede('cuentas:crear');
    }

    public static function puedeEditarCuentas(): bool {
        return AuthController::esAdministrador()
            || AuthController::puede('cuentas:editar');
    }

    /**
     * Enlace principal del bloque Administración en menú lateral.
     */
    public static function puedeVerBloqueAdministracion(): bool {
        return self::puedeVerCuentas()
            || AuthController::puedeVerModulo('roles')
            || self::puedeVerMatrizPermisos();
    }

    /**
     * @param array{json?: bool, mensaje?: string} $opciones
     */
    public static function denegarSiNoPuedeVerPermisos(array $opciones = []): void {
        if (self::puedeVerMatrizPermisos()) {
            return;
        }
        require_once APP . '/Helpers/PermisoGuard.php';
        PermisoGuard::exigir('permisos:ver', $opciones);
    }

    /**
     * @param array{json?: bool, mensaje?: string} $opciones
     */
    public static function denegarSiNoPuedeEditarPermisos(array $opciones = []): void {
        if (self::puedeEditarMatrizPermisos()) {
            return;
        }
        require_once APP . '/Helpers/PermisoGuard.php';
        PermisoGuard::exigir('permisos:editar', $opciones);
    }

    /**
     * @param array{json?: bool, mensaje?: string} $opciones
     */
    public static function denegarSiNoPuedeVerCuentas(array $opciones = []): void {
        if (self::puedeVerCuentas()) {
            return;
        }
        require_once APP . '/Helpers/PermisoGuard.php';
        PermisoGuard::exigir('cuentas:ver', $opciones);
    }
}

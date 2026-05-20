<?php
/**
 * Validación de acceso a rutas por permiso y políticas de layout (maestro / discípulo).
 * Requiere AuthController cargado antes de usar.
 */
class RouteGuard {

    /** @var array<string, mixed>|null */
    private static $mapa = null;

    /**
     * @return array<string, mixed>
     */
    private static function obtenerMapa(): array {
        if (self::$mapa === null) {
            $archivo = APP . '/Config/route_permissions.php';
            self::$mapa = is_file($archivo) ? (array)require $archivo : [];
        }
        return self::$mapa;
    }

    public static function puedeAcceder(string $url): bool {
        $url = trim($url, '/');
        if ($url === '') {
            $url = 'home';
        }

        if (!AuthController::estaAutenticado()) {
            return false;
        }

        if (AuthController::esAdministrador()) {
            return true;
        }

        if (strpos($url, 'auth/') === 0) {
            return true;
        }

        $bloqueoLayout = self::evaluarPoliticaLayout($url);
        if ($bloqueoLayout === false) {
            return false;
        }

        $regla = self::resolverRegla($url);
        if ($regla === null) {
            return true;
        }

        return self::evaluarRegla($regla, $url);
    }

    /**
     * false = denegar por política de layout; null = sin restricción de layout.
     */
    private static function evaluarPoliticaLayout(string $url): ?bool {
        if (AuthController::esContextoMaestro()) {
            if (!self::urlPermitidaEnPrefijos($url, self::prefijosMaestro())) {
                return false;
            }
        }

        $contexto = AuthController::getActiveContext();
        if ($contexto === 'discipulo') {
            if (!self::urlPermitidaEnPrefijos($url, self::prefijosDiscipulo())) {
                return false;
            }
        } elseif ($contexto !== 'lider' && $contexto !== 'maestro' && AuthController::esVistaDiscipuloSimplificada()) {
            if (!self::urlPermitidaEnPrefijos($url, self::prefijosDiscipulo())) {
                return false;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private static function prefijosMaestro(): array {
        return [
            'home/material/capacitacion-destino',
            'home/material',
            'programas/evaluaciones',
            'auth/',
            'home',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function prefijosDiscipulo(): array {
        return [
            'programas/evaluaciones',
            'programas/tareas',
            'programas/ir-clase',
            'auth/',
            'home',
        ];
    }

    /**
     * @param array<int, string> $prefijos
     */
    private static function urlPermitidaEnPrefijos(string $url, array $prefijos): bool {
        foreach ($prefijos as $prefijo) {
            $prefijo = trim($prefijo, '/');
            if ($prefijo === '') {
                continue;
            }
            if ($url === $prefijo || strpos($url, $prefijo . '/') === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string|array<string, mixed>|null
     */
    private static function resolverRegla(string $url) {
        $mapa = self::obtenerMapa();

        if (isset($mapa[$url])) {
            return $mapa[$url];
        }

        $mejor = null;
        $longitud = 0;
        foreach ($mapa as $patron => $regla) {
            if (!is_string($patron) || substr($patron, -2) !== '/*') {
                continue;
            }
            $base = rtrim(substr($patron, 0, -2), '/');
            if ($base !== '' && ($url === $base || strpos($url, $base . '/') === 0)) {
                $len = strlen($base);
                if ($len >= $longitud) {
                    $longitud = $len;
                    $mejor = $regla;
                }
            }
        }

        return $mejor;
    }

    /**
     * @param string|array<string, mixed> $regla
     */
    private static function evaluarRegla($regla, string $url): bool {
        if (is_string($regla)) {
            return AuthController::puede($regla);
        }

        if (!is_array($regla)) {
            return true;
        }

        if (!empty($regla['allow'])) {
            return true;
        }

        if (!empty($regla['admin_only'])) {
            return AuthController::esAdministrador();
        }

        if (!empty($regla['layout_maestro']) && AuthController::esContextoMaestro()) {
            return true;
        }

        if (!empty($regla['layout_discipulo']) && AuthController::esVistaDiscipuloSimplificada()) {
            return true;
        }

        if (!empty($regla['any']) && is_array($regla['any'])) {
            foreach ($regla['any'] as $clave) {
                if (AuthController::puede((string)$clave)) {
                    return true;
                }
            }
            return false;
        }

        $checker = trim((string)($regla['checker'] ?? ''));

        if (!empty($regla['inferir_accion'])) {
            $modulo = trim((string)($regla['modulo'] ?? ''));
            if ($modulo !== '') {
                $accion = self::inferirAccionDesdeUrl($url, $modulo);
                if ($accion === 'ver') {
                    $checkerVer = trim((string)($regla['checker_ver'] ?? ''));
                    if ($checkerVer !== '') {
                        return self::evaluarChecker($checkerVer);
                    }
                    if ($checker !== '') {
                        return self::evaluarChecker($checker);
                    }
                }
                return AuthController::puede($modulo . ':' . $accion);
            }
        }

        if ($checker !== '') {
            return self::evaluarChecker($checker);
        }

        $permiso = trim((string)($regla['permiso'] ?? ''));
        if ($permiso !== '') {
            return AuthController::puede($permiso);
        }

        return true;
    }

    private static function evaluarChecker(string $metodo): bool {
        if (!method_exists('AuthController', $metodo)) {
            return false;
        }
        return (bool)AuthController::$metodo();
    }

    private static function inferirAccionDesdeUrl(string $url, string $modulo): string {
        if (preg_match('#/(crear|guardar)(/|$)#', $url)) {
            return 'crear';
        }
        if (preg_match('#/(editar|actualizar|asignar|reasignar)(/|$)#', $url)) {
            return 'editar';
        }
        if (strpos($url, '/eliminar') !== false) {
            return 'eliminar';
        }
        if (strpos($url, 'exportar') !== false) {
            if ($modulo === 'programas') {
                return 'exportar_consolidado';
            }
            return 'exportar_excel';
        }
        return 'ver';
    }

    public static function denegarSiNoPuede(string $url): void {
        if (self::puedeAcceder($url)) {
            return;
        }

        $base = rtrim((string)(defined('PUBLIC_URL') ? PUBLIC_URL : ''), '/');
        header('Location: ' . $base . '/index.php?url=auth/acceso-denegado');
        exit;
    }
}

<?php
/**
 * Verificación uniforme de permisos en controladores (Fase A RBAC).
 * Usar en acciones destructivas o POST sensibles además de RouteGuard.
 */
class PermisoGuard {

    /**
     * Exige modulo:accion; si falla, redirige o responde JSON 403.
     *
     * @param string $clave Ej. personas:eliminar
     * @param array{json?: bool, mensaje?: string, status?: int, redirect?: string} $opciones
     */
    public static function exigir(string $clave, array $opciones = []): void {
        if (AuthController::puede($clave)) {
            return;
        }
        self::denegar($opciones);
    }

    /**
     * Exige al menos uno de los permisos listados.
     *
     * @param array<int, string> $claves
     * @param array{json?: bool, mensaje?: string, status?: int, redirect?: string} $opciones
     */
    public static function exigirCualquiera(array $claves, array $opciones = []): void {
        foreach ($claves as $clave) {
            $clave = trim((string)$clave);
            if ($clave !== '' && AuthController::puede($clave)) {
                return;
            }
        }
        self::denegar($opciones);
    }

    /**
     * @param array{json?: bool, mensaje?: string, status?: int, redirect?: string} $opciones
     */
    private static function denegar(array $opciones): void {
        $usarJson = !empty($opciones['json']) || self::esPeticionAjax();
        $mensaje = trim((string)($opciones['mensaje'] ?? ''));
        if ($mensaje === '') {
            $mensaje = 'No tiene permiso para realizar esta acción.';
        }

        if ($usarJson) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code((int)($opciones['status'] ?? 403));
            }
            echo json_encode([
                'ok' => false,
                'success' => false,
                'error' => $mensaje,
                'mensaje' => $mensaje,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $redirect = trim((string)($opciones['redirect'] ?? ''));
        if ($redirect === '') {
            $base = rtrim((string)(defined('PUBLIC_URL') ? PUBLIC_URL : (defined('BASE_URL') ? BASE_URL . '/public/' : '/')), '/');
            $redirect = $base . '/index.php?url=auth/acceso-denegado';
        }
        header('Location: ' . $redirect);
        exit;
    }

    private static function esPeticionAjax(): bool {
        $xhr = strtolower(trim((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')));
        if ($xhr === 'xmlhttprequest') {
            return true;
        }
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        return strpos($accept, 'application/json') !== false;
    }
}

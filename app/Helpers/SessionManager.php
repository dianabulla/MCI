<?php
/**
 * Sesión: tiempo de inactividad y cierre seguro.
 */
class SessionManager {
    /** Segundos sin actividad antes de cerrar sesión (30 minutos). */
    public const IDLE_TIMEOUT_SECONDS = 1800;

    /**
     * Debe llamarse antes de session_start().
     */
    public static function configure(): void {
        $timeout = self::IDLE_TIMEOUT_SECONDS;
        ini_set('session.gc_maxlifetime', (string)$timeout);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        $params = [
            'lifetime' => 0,
            'path' => self::cookiePath(),
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params($params);
        } else {
            session_set_cookie_params(
                $params['lifetime'],
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    }

    /**
     * Tras session_start(): valida inactividad y renueva marca de actividad.
     */
    public static function bootstrap(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        if (!self::sesionAutenticada()) {
            return;
        }

        $now = time();
        $last = (int)($_SESSION['last_activity'] ?? 0);

        if ($last > 0 && ($now - $last) > self::IDLE_TIMEOUT_SECONDS) {
            self::destroySession();
            self::respondSessionExpired();
        }

        $_SESSION['last_activity'] = $now;
    }

    public static function markLogin(): void {
        $_SESSION['last_activity'] = time();
    }

    public static function destroySession(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'] ?? '/',
                $p['domain'] ?? '',
                !empty($p['secure']),
                !empty($p['httponly'])
            );
        }
        session_destroy();
    }

    public static function respondSessionExpired(): void {
        $isAjax = self::esPeticionAjax();

        if ($isAjax) {
            if (!headers_sent()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            }
            echo json_encode([
                'success' => false,
                'ok' => false,
                'session_expired' => true,
                'mensaje' => 'Tu sesión se cerró por inactividad (más de 30 minutos). Vuelve a iniciar sesión.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $loginUrl = self::loginUrlConSesionExpirada();
        if (!headers_sent()) {
            header('Location: ' . $loginUrl);
        }
        exit;
    }

    public static function loginUrlConSesionExpirada(): string {
        if (function_exists('public_app_url')) {
            return public_app_url('auth/login', ['sesion' => 'expirada']);
        }

        $base = defined('PUBLIC_URL') ? rtrim(PUBLIC_URL, '/') : '';
        return $base . '/index.php?url=auth/login&sesion=expirada';
    }

    private static function sesionAutenticada(): bool {
        return isset($_SESSION['auth_user_id']) || isset($_SESSION['usuario_id']);
    }

    private static function esPeticionAjax(): bool {
        $xhr = strtolower(trim((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')));
        if ($xhr === 'xmlhttprequest') {
            return true;
        }

        $accept = strtolower(trim((string)($_SERVER['HTTP_ACCEPT'] ?? '')));
        return strpos($accept, 'application/json') !== false;
    }

    private static function cookiePath(): string {
        $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/public/index.php'));
        $dir = rtrim(dirname($script), '/');
        if ($dir === '' || $dir === '.') {
            return '/';
        }
        return $dir . '/';
    }
}

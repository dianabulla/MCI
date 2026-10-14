<?php
/**
 * Defensa contra solicitudes enviadas desde otro sitio (CSRF) sin tocar cada formulario.
 *
 * Se aplica solo a rutas privadas (con sesión):
 *  - Toda solicitud POST.
 *  - Las solicitudes GET que cambian datos (eliminar, generar cola, etc.).
 *
 * Regla: si el navegador indica de dónde viene la solicitud (Origin o Referer),
 * debe ser este mismo sitio. Un POST sin ninguna de las dos cabeceras se permite
 * (scripts propios o navegadores que las ocultan); un GET que modifica datos sin
 * cabeceras se bloquea.
 */
class OriginGuard {

    public static function validar(string $url): void {
        $metodo = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $esPost = ($metodo === 'POST');
        $esGetQueModifica = ($metodo === 'GET' && self::rutaModificaPorGet($url));

        if (!$esPost && !$esGetQueModifica) {
            return;
        }

        $origen = self::hostDeCabecera('HTTP_ORIGIN');
        if ($origen === null) {
            $origen = self::hostDeCabecera('HTTP_REFERER');
        }

        if ($origen === '') {
            // Origin: null (página aislada o redirección cruzada) → no es nuestro sitio.
            self::bloquear();
        }

        if ($origen === null) {
            if ($esGetQueModifica) {
                self::bloquear();
            }
            return;
        }

        if (!in_array($origen, self::hostsPropios(), true)) {
            self::bloquear();
        }
    }

    public static function rutaModificaPorGet(string $url): bool {
        $url = trim($url, '/');
        return (bool)preg_match(
            '#(^|/)(eliminar|eliminar-[a-z0-9-]+|generar-cola|procesar-cola|reintentar-fallidos|migrar-consolidados|aplicar|migrar-legacy|crear-presentacion-ninos|crear-tour-levantate)$#i',
            $url
        );
    }

    /**
     * @return string|null null = cabecera ausente; '' = presente pero sin host válido; si no, host normalizado
     */
    private static function hostDeCabecera(string $clave): ?string {
        if (!isset($_SERVER[$clave])) {
            return null;
        }
        $valor = trim((string)$_SERVER[$clave]);
        if ($valor === '') {
            return null;
        }
        $host = parse_url($valor, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return '';
        }
        return self::normalizarHost($host);
    }

    /**
     * @return array<int, string>
     */
    private static function hostsPropios(): array {
        $hosts = [];
        $candidatos = [(string)($_SERVER['HTTP_HOST'] ?? ''), (string)($_SERVER['SERVER_NAME'] ?? '')];

        if (defined('PUBLIC_URL')) {
            $hostPublico = parse_url((string)PUBLIC_URL, PHP_URL_HOST);
            if (is_string($hostPublico)) {
                $candidatos[] = $hostPublico;
            }
        }

        foreach ($candidatos as $candidato) {
            $candidato = trim($candidato);
            if ($candidato === '') {
                continue;
            }
            $host = parse_url('//' . $candidato, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $hosts[] = self::normalizarHost($host);
            }
        }

        return array_values(array_unique($hosts));
    }

    private static function normalizarHost(string $host): string {
        $host = strtolower(trim($host));
        return (strpos($host, 'www.') === 0) ? substr($host, 4) : $host;
    }

    private static function bloquear(): void {
        $xhr = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        http_response_code(403);
        if ($xhr === 'xmlhttprequest' || strpos($accept, 'application/json') !== false) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Solicitud bloqueada por seguridad. Recarga la página e inténtalo de nuevo.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Solicitud bloqueada</title></head>'
            . '<body style="font-family:sans-serif;max-width:520px;margin:60px auto;color:#1e293b;">'
            . '<h2>Solicitud bloqueada por seguridad</h2>'
            . '<p>La acción no se inició desde esta aplicación. Vuelve a la página, recárgala e inténtalo de nuevo.</p>'
            . '</body></html>';
        exit;
    }
}

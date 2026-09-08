<?php
/**
 * Bitácora de quién hizo qué y de intentos fallidos.
 * Nunca lanza excepción: si el log falla, la acción principal sigue.
 */
class Auditoria {
    public static function registrar(array $evento): void {
        try {
            require_once APP . '/Models/AuditoriaEvento.php';
            $modelo = new AuditoriaEvento();
            if (!$modelo->asegurarTabla()) {
                return;
            }

            $accion = substr(trim((string)($evento['accion'] ?? 'otro')), 0, 40);
            $modulo = substr(trim((string)($evento['modulo'] ?? 'sistema')), 0, 60);
            if ($accion === '' || $modulo === '') {
                return;
            }

            $detalle = $evento['detalle'] ?? null;
            if (is_array($detalle)) {
                $detalle = json_encode($detalle, JSON_UNESCAPED_UNICODE);
            }

            $modelo->create([
                'Fecha' => date('Y-m-d H:i:s'),
                'Id_Actor' => self::idActor($evento),
                'Usuario_Actor' => self::usuarioActor($evento),
                'Accion' => $accion,
                'Modulo' => $modulo,
                'Entidad' => self::textoCorto($evento['entidad'] ?? null, 60),
                'Id_Registro' => isset($evento['id_registro']) ? (int)$evento['id_registro'] : null,
                'Exito' => array_key_exists('exito', $evento) ? (((int)$evento['exito']) ? 1 : 0) : 1,
                'Mensaje' => self::textoCorto($evento['mensaje'] ?? null, 255),
                'Detalle' => $detalle !== null && $detalle !== '' ? (string)$detalle : null,
                'Ip' => self::textoCorto($_SERVER['REMOTE_ADDR'] ?? null, 45),
                'Url' => self::textoCorto(self::urlActual(), 255),
            ]);
        } catch (Throwable $e) {
            error_log('Auditoria::registrar: ' . $e->getMessage());
        }
    }

    public static function ok(string $modulo, string $accion, array $extra = []): void {
        self::registrar(array_merge($extra, [
            'modulo' => $modulo,
            'accion' => $accion,
            'exito' => 1,
        ]));
    }

    public static function fallo(string $modulo, string $accion, string $mensaje, array $extra = []): void {
        self::registrar(array_merge($extra, [
            'modulo' => $modulo,
            'accion' => $accion,
            'exito' => 0,
            'mensaje' => $mensaje,
        ]));
    }

    private static function idActor(array $evento): ?int {
        if (isset($evento['id_actor'])) {
            $id = (int)$evento['id_actor'];
            return $id > 0 ? $id : null;
        }
        $id = (int)($_SESSION['usuario_id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private static function usuarioActor(array $evento): ?string {
        if (!empty($evento['usuario_actor'])) {
            return self::textoCorto($evento['usuario_actor'], 120);
        }
        $login = trim((string)($_SESSION['usuario_login'] ?? $_SESSION['usuario_nombre'] ?? ''));
        return $login !== '' ? self::textoCorto($login, 120) : null;
    }

    private static function textoCorto($valor, int $max): ?string {
        $texto = trim((string)$valor);
        if ($texto === '') {
            return null;
        }
        if (function_exists('mb_substr')) {
            return mb_substr($texto, 0, $max);
        }
        return substr($texto, 0, $max);
    }

    private static function urlActual(): string {
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        return $uri !== '' ? $uri : (string)($_GET['url'] ?? '');
    }
}

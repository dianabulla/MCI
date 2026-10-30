<?php
/**
 * Formulario fijo «Presentación de niños»: padres, niño, edad y ministerio.
 * Los datos viven solo en el JSON de la inscripción del taller (sin Personas).
 */

class TallerPresentacionNinosSync {

    public const OPCION_SIN_MINISTERIO = 'sin';

    /**
     * @return array<string, string>
     */
    public static function camposPadres(): array {
        return [
            'padres_nombre' => 'Nombre de los padres o acudiente',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function camposNino(): array {
        return [
            'nino_nombre' => 'Nombre del niño(a)',
            'nino_edad' => 'Edad',
        ];
    }

    /**
     * @return array{ok: bool, errores: array<string, string>, datos: array<string, string>}
     */
    public function procesarPadresDesdePost(array $post): array {
        $nombre = trim((string)($post['padres_nombre'] ?? ''));
        $errores = [];
        if ($nombre === '') {
            $errores['padres_nombre'] = 'Escriba el nombre de los padres o acudiente.';
        }

        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores, 'datos' => []];
        }

        return [
            'ok' => true,
            'errores' => [],
            'datos' => [
                'padres_nombre' => $nombre,
            ],
        ];
    }

    /**
     * @return array{ok: bool, errores: array<string, string>, datos: array<string, string>}
     */
    public function procesarNinoDesdePost(array $post): array {
        $nombre = trim((string)($post['nino_nombre'] ?? ''));
        $edad = trim((string)($post['nino_edad'] ?? ''));
        $errores = [];

        if ($nombre === '') {
            $errores['nino_nombre'] = 'Escriba el nombre del niño(a).';
        }
        if ($edad === '' || !ctype_digit($edad) || (int)$edad < 0 || (int)$edad > 18) {
            $errores['nino_edad'] = 'Indique la edad del niño(a) (0 a 18).';
        }

        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores, 'datos' => []];
        }

        return [
            'ok' => true,
            'errores' => [],
            'datos' => [
                'nino_nombre' => $nombre,
                'nino_edad' => (string)((int)$edad),
            ],
        ];
    }

    /**
     * Ministerio elegido en el formulario público (solo se guarda en el JSON del taller).
     *
     * @param array<int, array{id: int, nombre: string}> $ministeriosCatalogo
     * @return array{ok: bool, errores: array<string, string>, datos: array<string, mixed>}
     */
    public function procesarMinisterioDesdePost(array $post, array $ministeriosCatalogo): array {
        $valor = trim((string)($post['id_ministerio_taller'] ?? ''));
        $errores = [];

        if ($valor === '') {
            $errores['id_ministerio_taller'] = 'Seleccione el ministerio o «Sin ministerio».';
            return ['ok' => false, 'errores' => $errores, 'datos' => []];
        }

        if ($valor === self::OPCION_SIN_MINISTERIO) {
            return [
                'ok' => true,
                'errores' => [],
                'datos' => [
                    'persona_ministerio' => 'Sin ministerio asignado',
                    'ministerio' => 'Sin ministerio asignado',
                ],
            ];
        }

        $idMinisterio = (int)$valor;
        $nombre = '';
        foreach ($ministeriosCatalogo as $fila) {
            if ((int)($fila['id'] ?? 0) === $idMinisterio) {
                $nombre = trim((string)($fila['nombre'] ?? ''));
                break;
            }
        }

        if ($idMinisterio <= 0 || $nombre === '') {
            $errores['id_ministerio_taller'] = 'El ministerio elegido no es válido.';
            return ['ok' => false, 'errores' => $errores, 'datos' => []];
        }

        return [
            'ok' => true,
            'errores' => [],
            'datos' => [
                'persona_ministerio' => $nombre,
                'ministerio' => $nombre,
                '_asignacion_ministerio' => [
                    'id_ministerio' => $idMinisterio,
                    'nombre_ministerio' => $nombre,
                    'origen' => 'formulario_publico',
                ],
            ],
        ];
    }
}

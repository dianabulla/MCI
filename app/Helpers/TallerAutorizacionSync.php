<?php
/**
 * Valida y procesa el bloque fijo de autorización al final del formulario.
 */

class TallerAutorizacionSync {
    public static function textoDefault(): string {
        return 'Declaro que la información suministrada es veraz y autorizo su uso exclusivamente '
            . 'para fines relacionados con el desarrollo del taller.';
    }

    /**
     * @param array<string, mixed>|null $formulario
     */
    public static function textoParaFormulario(?array $formulario): string {
        $custom = trim((string)($formulario['Texto_Autorizacion'] ?? ''));
        return $custom !== '' ? $custom : self::textoDefault();
    }

    /**
     * @return array{ok: bool, errores: array<string, string>, datos: array<string, mixed>}
     */
    public static function procesarDesdePost(array $post): array {
        $acepto = !empty($post['autorizacion_acepto']);
        $firma = trim((string)($post['autorizacion_firma'] ?? ''));
        $fecha = trim((string)($post['autorizacion_fecha'] ?? ''));

        $errores = [];
        if (!$acepto) {
            $errores['autorizacion_acepto'] = 'Debe aceptar la autorización para continuar.';
        }
        if ($firma === '') {
            $errores['autorizacion_firma'] = 'Debe firmar en el espacio indicado.';
        } elseif (strpos($firma, 'data:image') === 0 && strlen($firma) < 500) {
            $errores['autorizacion_firma'] = 'La firma no es válida. Dibuje su firma en el recuadro.';
        }

        if ($fecha === '') {
            $fecha = date('Y-m-d');
        }

        return [
            'ok' => empty($errores),
            'errores' => $errores,
            'datos' => [
                '_autorizacion' => [
                    'acepto' => $acepto,
                    'firma' => $firma,
                    'fecha' => substr($fecha, 0, 10),
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $json
     * @return array<string, string>
     */
    public static function extraerValoresParaTabla(array $json): array {
        $auth = is_array($json['_autorizacion'] ?? null) ? $json['_autorizacion'] : [];
        $firma = (string)($auth['firma'] ?? '');
        $firmaDisplay = (strpos($firma, 'data:image') === 0) ? '[Firma digital]' : $firma;

        return [
            'autorizacion_acepto' => !empty($auth['acepto']) ? 'Sí' : '',
            'autorizacion_firma' => $firmaDisplay,
            'autorizacion_fecha' => (string)($auth['fecha'] ?? ''),
        ];
    }
}

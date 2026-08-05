<?php
/**
 * Bloques padres / niño del formulario «Presentación de niños».
 */

require_once APP . '/Models/Persona.php';

class TallerPresentacionNinosSync {
    private Persona $personaModel;

    public function __construct() {
        $this->personaModel = new Persona();
    }

    /**
     * @return array<string, string>
     */
    public static function camposPadres(): array {
        return [
            'padres_nombre' => 'Nombre del padre/madre o acudiente',
            'padres_documento' => 'Documento de identidad',
            'padres_telefono' => 'Teléfono de contacto',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function camposNino(): array {
        return [
            'nino_nombre' => 'Nombre del niño(a)',
            'nino_documento' => 'Documento del niño(a)',
            'nino_fecha_nacimiento' => 'Fecha de nacimiento',
            'nino_edad' => 'Edad',
        ];
    }

    /**
     * @return array{ok: bool, errores: array<string, string>, id_persona: int, datos: array<string, string>}
     */
    public function procesarPadresDesdePost(array $post): array {
        $documento = preg_replace('/\D+/', '', (string)($post['padres_documento'] ?? ''));
        if ($documento === '') {
            $documento = preg_replace('/\D+/', '', (string)($post['buscar_padres_documento'] ?? ''));
        }

        $errores = [];
        if ($documento === '') {
            $errores['padres_documento'] = 'Escriba el documento del padre, madre o acudiente.';
            return ['ok' => false, 'errores' => $errores, 'id_persona' => 0, 'datos' => []];
        }

        $encontrado = $this->buscarPersonaPorDocumento($documento);
        if ($encontrado) {
            return $this->procesarPadresDesdePersonaExistente($encontrado, $post);
        }

        $nombre = trim((string)($post['padres_nombre'] ?? ''));
        $telefono = preg_replace('/\D+/', '', (string)($post['padres_telefono'] ?? ''));

        if ($nombre === '') {
            $errores['padres_nombre'] = 'Escriba el nombre del padre, madre o acudiente.';
        }
        if ($telefono === '' || strlen($telefono) < 7) {
            $errores['padres_telefono'] = 'Indique un teléfono válido (mínimo 7 dígitos).';
        }

        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores, 'id_persona' => 0, 'datos' => []];
        }

        return [
            'ok' => true,
            'errores' => [],
            'id_persona' => 0,
            'datos' => [
                'padres_nombre' => $nombre,
                'padres_documento' => $documento,
                'padres_telefono' => $telefono,
                'padres_encontrado_bd' => '0',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $persona
     * @return array{ok: bool, errores: array<string, string>, id_persona: int, datos: array<string, string>}
     */
    private function procesarPadresDesdePersonaExistente(array $persona, array $post = []): array {
        $fmt = $this->formatearPersona($persona);
        $idPersona = (int)($persona['Id_Persona'] ?? 0);
        $nombre = trim((string)($fmt['nombre'] ?? ''));
        $telefono = preg_replace('/\D+/', '', (string)($fmt['telefono'] ?? ''));
        $documento = preg_replace('/\D+/', '', (string)($fmt['documento'] ?? ''));

        if ($nombre === '') {
            $nombre = trim((string)($post['padres_nombre'] ?? ''));
        }
        if ($telefono === '') {
            $telefono = preg_replace('/\D+/', '', (string)($post['padres_telefono'] ?? ''));
        }
        if ($documento === '') {
            $documento = preg_replace('/\D+/', '', (string)($post['padres_documento'] ?? ''));
        }

        $errores = [];
        if ($nombre === '') {
            $errores['padres_nombre'] = 'Escriba el nombre del padre, madre o acudiente.';
        }
        if ($telefono === '' || strlen($telefono) < 7) {
            $errores['padres_telefono'] = 'Indique un teléfono válido (mínimo 7 dígitos).';
        }
        if ($documento === '') {
            $errores['padres_documento'] = 'Escriba el documento del padre, madre o acudiente.';
        }
        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores, 'id_persona' => 0, 'datos' => []];
        }

        return [
            'ok' => true,
            'errores' => [],
            'id_persona' => $idPersona,
            'datos' => [
                'padres_nombre' => $nombre,
                'padres_documento' => $documento,
                'padres_telefono' => $telefono,
                'padres_id_persona' => $idPersona > 0 ? (string)$idPersona : '',
                'padres_encontrado_bd' => $idPersona > 0 ? '1' : '0',
            ],
        ];
    }

    /**
     * @return array{ok: bool, errores: array<string, string>, id_persona: int, datos: array<string, string>}
     */
    public function procesarNinoDesdePost(array $post): array {
        $documento = preg_replace('/\D+/', '', (string)($post['nino_documento'] ?? ''));
        if ($documento === '') {
            $documento = preg_replace('/\D+/', '', (string)($post['buscar_nino_documento'] ?? ''));
        }

        $errores = [];
        if ($documento === '') {
            $errores['nino_documento'] = 'Escriba el documento del niño(a).';
            return ['ok' => false, 'errores' => $errores, 'id_persona' => 0, 'datos' => []];
        }

        $encontrado = $this->buscarPersonaPorDocumento($documento);
        if ($encontrado) {
            return $this->procesarNinoDesdePersonaExistente($encontrado, $post);
        }

        $nombre = trim((string)($post['nino_nombre'] ?? ''));
        $fechaNac = trim((string)($post['nino_fecha_nacimiento'] ?? ''));
        if ($fechaNac === '0000-00-00') {
            $fechaNac = '';
        }
        $edad = trim((string)($post['nino_edad'] ?? ''));

        if ($nombre === '') {
            $errores['nino_nombre'] = 'Escriba el nombre del niño(a).';
        }
        if ($fechaNac === '') {
            $errores['nino_fecha_nacimiento'] = 'Indique la fecha de nacimiento.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNac)) {
            $errores['nino_fecha_nacimiento'] = 'La fecha de nacimiento no es válida.';
        } else {
            $edadCalc = self::calcularEdadDesdeFecha($fechaNac);
            if ($edadCalc !== null) {
                $edad = (string)$edadCalc;
            }
        }
        if ($edad === '' || !is_numeric($edad)) {
            $errores['nino_edad'] = 'No se pudo calcular la edad. Verifique la fecha de nacimiento.';
        }

        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores, 'id_persona' => 0, 'datos' => []];
        }

        return [
            'ok' => true,
            'errores' => [],
            'id_persona' => 0,
            'datos' => [
                'nino_nombre' => $nombre,
                'nino_documento' => $documento,
                'nino_fecha_nacimiento' => substr($fechaNac, 0, 10),
                'nino_edad' => $edad,
                'nino_encontrado_bd' => '0',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $persona
     * @return array{ok: bool, errores: array<string, string>, id_persona: int, datos: array<string, string>}
     */
    private function procesarNinoDesdePersonaExistente(array $persona, array $post = []): array {
        $fmt = $this->formatearPersona($persona);
        $idPersona = (int)($persona['Id_Persona'] ?? 0);
        $nombre = trim((string)($fmt['nombre'] ?? ''));
        $documento = preg_replace('/\D+/', '', (string)($fmt['documento'] ?? ''));
        $fechaNac = trim((string)($fmt['fecha_nacimiento'] ?? ''));
        $edad = trim((string)($fmt['edad'] ?? ''));

        if ($nombre === '') {
            $nombre = trim((string)($post['nino_nombre'] ?? ''));
        }
        if ($documento === '') {
            $documento = preg_replace('/\D+/', '', (string)($post['nino_documento'] ?? ''));
        }
        if ($fechaNac === '' || $fechaNac === '0000-00-00') {
            $fechaNac = trim((string)($post['nino_fecha_nacimiento'] ?? ''));
        }
        if ($fechaNac === '0000-00-00') {
            $fechaNac = '';
        }
        if ($edad === '' && $fechaNac !== '') {
            $edadCalc = self::calcularEdadDesdeFecha($fechaNac);
            if ($edadCalc !== null) {
                $edad = (string)$edadCalc;
            }
        }
        if ($edad === '') {
            $edad = trim((string)($post['nino_edad'] ?? ''));
        }

        $errores = [];
        if ($nombre === '') {
            $errores['nino_nombre'] = 'Escriba el nombre del niño(a).';
        }
        if ($documento === '') {
            $errores['nino_documento'] = 'Escriba el documento del niño(a).';
        }
        if ($fechaNac === '') {
            $errores['nino_fecha_nacimiento'] = 'Indique la fecha de nacimiento.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNac)) {
            $errores['nino_fecha_nacimiento'] = 'La fecha de nacimiento no es válida.';
        } else {
            $edadCalc = self::calcularEdadDesdeFecha($fechaNac);
            if ($edadCalc !== null) {
                $edad = (string)$edadCalc;
            }
        }
        if ($edad === '' || !is_numeric($edad)) {
            $errores['nino_edad'] = 'No se pudo calcular la edad. Verifique la fecha de nacimiento.';
        }

        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores, 'id_persona' => 0, 'datos' => []];
        }

        return [
            'ok' => true,
            'errores' => [],
            'id_persona' => $idPersona,
            'datos' => [
                'nino_nombre' => $nombre,
                'nino_documento' => $documento,
                'nino_fecha_nacimiento' => substr($fechaNac, 0, 10),
                'nino_edad' => $edad,
                'nino_id_persona' => $idPersona > 0 ? (string)$idPersona : '',
                'nino_encontrado_bd' => $idPersona > 0 ? '1' : '0',
            ],
        ];
    }

    public static function calcularEdadDesdeFecha(string $fecha): ?int {
        $fecha = substr(trim($fecha), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return null;
        }
        try {
            $nac = new DateTime($fecha);
            $hoy = new DateTime('today');
            if ($nac > $hoy) {
                return null;
            }
            return (int)$nac->diff($hoy)->y;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorDocumento(string $documento): ?array {
        $persona = $this->buscarPersonaPorDocumento($documento);
        return $persona ? $this->formatearPersona($persona) : null;
    }

    /**
     * @param array<string, mixed> $persona
     * @return array<string, mixed>
     */
    public function formatearPersona(array $persona): array {
        $nombre = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));
        $fechaNac = '';
        if (!empty($persona['Fecha_Nacimiento']) && (string)$persona['Fecha_Nacimiento'] !== '0000-00-00') {
            $fechaNac = substr((string)$persona['Fecha_Nacimiento'], 0, 10);
        }
        $edad = trim((string)($persona['Edad'] ?? ''));
        if ($edad === '' && $fechaNac !== '') {
            $calc = self::calcularEdadDesdeFecha($fechaNac);
            if ($calc !== null) {
                $edad = (string)$calc;
            }
        }

        return [
            'id_persona' => (int)($persona['Id_Persona'] ?? 0),
            'nombre' => trim($nombre),
            'documento' => (string)($persona['Numero_Documento'] ?? ''),
            'telefono' => (string)($persona['Telefono'] ?? ''),
            'fecha_nacimiento' => $fechaNac,
            'edad' => $edad,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buscarPersonaPorDocumento(string $documento): ?array {
        $documento = preg_replace('/\D+/', '', trim($documento));
        if ($documento === '') {
            return null;
        }
        $persona = $this->personaModel->buscarParaInscripcionEscuela($documento, '', '');
        if (!is_array($persona) || (int)($persona['Id_Persona'] ?? 0) <= 0) {
            return null;
        }
        $id = (int)$persona['Id_Persona'];
        $completa = $this->personaModel->getById($id);
        return is_array($completa) ? array_merge($persona, $completa) : $persona;
    }
}

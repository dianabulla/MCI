<?php

/**

 * Bloque 1 (datos personales) del formulario público de talleres.

 * No crea ni modifica registros en persona: solo enlaza Id_Persona si ya existe.

 */



require_once APP . '/Models/Persona.php';



class TallerPersonaSync {

    private Persona $personaModel;



    public function __construct() {

        $this->personaModel = new Persona();

    }



    /**

     * @return array{ok: bool, errores: array<string, string>, id_persona: int, extras: array<string, string>, datos_persona: array<string, string>}

     */

    public function procesarDesdePost(array $post): array {

        $nombreCompleto = trim((string)($post['persona_nombre'] ?? ''));

        $apellido = trim((string)($post['persona_apellido'] ?? ''));

        if ($apellido !== '') {

            if ($nombreCompleto === '' || !str_contains($nombreCompleto, $apellido)) {

                $nombreCompleto = trim($nombreCompleto . ' ' . $apellido);

            }

        }

        $documento = preg_replace('/\D+/', '', (string)($post['persona_documento'] ?? ''));

        $tipoDocumento = trim((string)($post['persona_tipo_documento'] ?? ''));

        if ($tipoDocumento === '') {

            $tipoDocumento = 'Cedula de Ciudadania';

        }

        $fechaNac = trim((string)($post['persona_fecha_nacimiento'] ?? ''));

        $edad = trim((string)($post['persona_edad'] ?? ''));

        $telefono = preg_replace('/\D+/', '', (string)($post['persona_telefono'] ?? ''));

        $email = strtolower(trim((string)($post['persona_email'] ?? '')));

        $direccion = trim((string)($post['persona_direccion'] ?? ''));

        $estadoCivil = trim((string)($post['persona_estado_civil'] ?? ''));

        $ocupacion = trim((string)($post['persona_ocupacion'] ?? ''));



        $errores = [];

        if ($nombreCompleto === '') {

            $errores['persona_nombre'] = 'Escriba el nombre.';

        }

        if (array_key_exists('persona_apellido', $post) && $apellido === '') {

            $errores['persona_apellido'] = 'Escriba el apellido.';

        }

        if ($documento === '') {

            $errores['persona_documento'] = 'Escriba el documento de identidad.';

        }

        if ($telefono === '') {

            $errores['persona_telefono'] = 'Escriba un teléfono de contacto.';

        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $errores['persona_email'] = 'El correo electrónico no es válido.';

        }



        if (!empty($errores)) {

            return ['ok' => false, 'errores' => $errores, 'id_persona' => 0, 'extras' => [], 'datos_persona' => []];

        }



        $existente = $this->buscarPersonaExistente($documento, $telefono, $email, $tipoDocumento);

        $idPersona = (int)($existente['Id_Persona'] ?? 0);

        $datosPersona = $this->construirDatosPersonaParaRespuesta($idPersona, $post);



        return [

            'ok' => true,

            'errores' => [],

            'id_persona' => $idPersona,

            'extras' => array_filter([

                'estado_civil' => $estadoCivil,

                'ocupacion' => $ocupacion,

                'persona_apellido' => $apellido,

                'persona_lider' => trim((string)($post['persona_lider'] ?? '')),

                'persona_ministerio' => trim((string)($post['persona_ministerio'] ?? '')),

            ], static function ($v) {

                return trim((string)$v) !== '';

            }),

            'datos_persona' => $datosPersona,

        ];

    }



    /**

     * Igual que procesarDesdePost, pero crea el registro en persona si no existe.

     *

     * @return array{ok: bool, errores: array<string, string>, id_persona: int, extras: array<string, string>, datos_persona: array<string, string>}

     */

    public function procesarDesdePostConCreacion(array $post): array {

        $resultado = $this->procesarDesdePost($post);

        if (!$resultado['ok']) {

            return $resultado;

        }



        $idPersona = (int)($resultado['id_persona'] ?? 0);

        if ($idPersona > 0) {

            return $resultado;

        }

        $documento = preg_replace('/\D+/', '', (string)($post['persona_documento'] ?? ''));

        $telefono = preg_replace('/\D+/', '', (string)($post['persona_telefono'] ?? ''));

        $nehemias = $this->buscarEnNehemias($documento, $telefono);

        if (is_array($nehemias)) {

            if (trim((string)($post['persona_lider'] ?? '')) === '') {

                $post['persona_lider'] = trim((string)($nehemias['Lider_Nehemias'] ?? ''));

            }

            if (trim((string)($post['persona_ministerio'] ?? '')) === '') {

                $post['persona_ministerio'] = trim((string)($nehemias['Lider'] ?? ''));

            }

        }



        $idNueva = $this->crearPersonaDesdePost($post, true);

        if ($idNueva <= 0) {

            return [

                'ok' => false,

                'errores' => ['persona_documento' => 'No se pudo registrar la persona. Intente de nuevo.'],

                'id_persona' => 0,

                'extras' => [],

                'datos_persona' => [],

            ];

        }



        return [

            'ok' => true,

            'errores' => [],

            'id_persona' => $idNueva,

            'extras' => $resultado['extras'],

            'datos_persona' => $this->construirDatosPersonaParaRespuesta($idNueva, $post),

        ];

    }



    /**

     * Datos del bloque persona para guardar en talleres_formulario_respuesta.

     * Si existe en BD, usa los datos exactos del registro (sin modificar persona).

     *

     * @return array<string, string>

     */

    public function construirDatosPersonaParaRespuesta(int $idPersona, array $post): array {

        if ($idPersona > 0) {

            $persona = $this->personaModel->getById($idPersona);

            if (!is_array($persona)) {

                return $this->extraerDatosPersonaDesdePost($post);

            }



            $fmt = $this->formatearPersonaParaFormulario($persona);

            $datos = [

                'persona_nombre' => trim((string)($persona['Nombre'] ?? '')) !== ''
                    ? trim((string)($persona['Nombre'] ?? ''))
                    : (string)($fmt['nombre'] ?? ''),

                'persona_apellido' => trim((string)($persona['Apellido'] ?? '')),

                'persona_documento' => (string)($fmt['documento'] ?? ''),

                'persona_telefono' => (string)($fmt['telefono'] ?? ''),

                'persona_email' => (string)($fmt['email'] ?? ''),

                'persona_direccion' => (string)($fmt['direccion'] ?? ''),

                'persona_fecha_nacimiento' => (string)($fmt['fecha_nacimiento'] ?? ''),

                'persona_edad' => (string)($fmt['edad'] ?? ''),

                'persona_lider' => trim((string)($persona['Nombre_Lider'] ?? $fmt['lider'] ?? '')),

                'persona_ministerio' => trim((string)($persona['Nombre_Ministerio'] ?? $fmt['ministerio'] ?? '')),

            ];



            $estadoCivil = trim((string)($post['persona_estado_civil'] ?? ''));

            $ocupacion = trim((string)($post['persona_ocupacion'] ?? ''));

            if ($estadoCivil !== '') {

                $datos['persona_estado_civil'] = $estadoCivil;

            }

            if ($ocupacion !== '') {

                $datos['persona_ocupacion'] = $ocupacion;

            }



            return array_filter($datos, static function ($v) {

                return trim((string)$v) !== '';

            });

        }



        return $this->extraerDatosPersonaDesdePost($post);

    }



    /**

     * @return array<string, string>

     */

    private function extraerDatosPersonaDesdePost(array $post): array {

        $fechaNac = trim((string)($post['persona_fecha_nacimiento'] ?? ''));

        if ($fechaNac === '0000-00-00') {

            $fechaNac = '';

        }



        $datos = [

            'persona_nombre' => trim((string)($post['persona_nombre'] ?? '')),

            'persona_apellido' => trim((string)($post['persona_apellido'] ?? '')),

            'persona_documento' => preg_replace('/\D+/', '', (string)($post['persona_documento'] ?? '')),

            'persona_telefono' => preg_replace('/\D+/', '', (string)($post['persona_telefono'] ?? '')),

            'persona_email' => strtolower(trim((string)($post['persona_email'] ?? ''))),

            'persona_direccion' => trim((string)($post['persona_direccion'] ?? '')),

            'persona_fecha_nacimiento' => $fechaNac !== '' ? substr($fechaNac, 0, 10) : '',

            'persona_edad' => trim((string)($post['persona_edad'] ?? '')),

            'persona_estado_civil' => trim((string)($post['persona_estado_civil'] ?? '')),

            'persona_ocupacion' => trim((string)($post['persona_ocupacion'] ?? '')),

            'persona_lider' => trim((string)($post['persona_lider'] ?? '')),

            'persona_ministerio' => trim((string)($post['persona_ministerio'] ?? '')),

        ];



        return array_filter($datos, static function ($v) {

            return trim((string)$v) !== '';

        });

    }



    /**

     * @return array<string, mixed>|null

     */

    public function buscarPorDocumento(string $documento): ?array {

        $documento = preg_replace('/\D+/', '', trim($documento));

        if ($documento === '') {

            return null;

        }

        $persona = $this->buscarPersonaExistente($documento, '', '', 'Cedula de Ciudadania');

        if (!$persona) {

            $nehemias = $this->buscarEnNehemias($documento, '');

            if (is_array($nehemias)) {

                return $this->formatearNehemiasParaFormulario($nehemias);

            }

            return null;

        }

        $id = (int)($persona['Id_Persona'] ?? 0);

        if ($id > 0) {

            $completa = $this->personaModel->getById($id);

            if (is_array($completa)) {

                $persona = array_merge($persona, $completa);

            }

        }

        return $this->formatearPersonaParaFormulario($persona);

    }



    /**

     * @return array<string, mixed>|null

     */

    public function buscarPorTelefono(string $telefono): ?array {

        $telefono = preg_replace('/\D+/', '', trim($telefono));

        if ($telefono === '') {

            return null;

        }

        $persona = $this->buscarPersonaExistente('', $telefono, '', '');

        if (!$persona) {

            $nehemias = $this->buscarEnNehemias('', $telefono);

            if (is_array($nehemias)) {

                return $this->formatearNehemiasParaFormulario($nehemias);

            }

            return null;

        }

        $id = (int)($persona['Id_Persona'] ?? 0);

        if ($id > 0) {

            $completa = $this->personaModel->getById($id);

            if (is_array($completa)) {

                $persona = array_merge($persona, $completa);

            }

        }

        return $this->formatearPersonaParaFormulario($persona);

    }



    /**

     * Busca persona existente: documento, luego teléfono, luego correo.

     * No usa solo el nombre para evitar enlazar registros equivocados.

     *

     * @return array<string, mixed>|null

     */

    private function buscarPersonaExistente(string $documento, string $telefono, string $email, string $tipoDocumento = ''): ?array {

        if ($documento !== '') {

            $porDocumento = $this->personaModel->buscarParaInscripcionEscuela($documento, '', '');

            if (is_array($porDocumento) && (int)($porDocumento['Id_Persona'] ?? 0) > 0) {

                return $porDocumento;

            }

        }



        if ($telefono !== '') {

            $porTelefono = $this->personaModel->buscarParaInscripcionEscuela('', $telefono, '');

            if (is_array($porTelefono) && (int)($porTelefono['Id_Persona'] ?? 0) > 0) {

                return $porTelefono;

            }

        }



        if ($email !== '' && $this->personaModel->tieneColumna('Email')) {

            $porEmail = $this->buscarPorEmail($email);

            if (is_array($porEmail) && (int)($porEmail['Id_Persona'] ?? 0) > 0) {

                return $porEmail;

            }

        }



        $duplicado = $this->personaModel->findDuplicateByCedulaOrTelefono($documento, $telefono, null, $tipoDocumento);

        $idDuplicado = (int)($duplicado['Id_Persona'] ?? 0);

        if ($idDuplicado > 0) {

            $completa = $this->personaModel->getById($idDuplicado);

            return is_array($completa) ? $completa : $duplicado;

        }



        return null;

    }



    /**

     * @return array<string, mixed>|null

     */

    private function buscarPorEmail(string $email): ?array {

        $email = strtolower(trim($email));

        if ($email === '') {

            return null;

        }



        $rows = $this->personaModel->query(

            "SELECT Id_Persona, Nombre, Apellido, Numero_Documento, Telefono, Email

             FROM persona

             WHERE LOWER(TRIM(COALESCE(Email, ''))) = ?

             ORDER BY Id_Persona DESC

             LIMIT 1",

            [$email]

        );



        return $rows[0] ?? null;

    }



    /**

     * @param array<string, mixed> $persona

     * @return array<string, mixed>

     */

    public function formatearPersonaParaFormulario(array $persona): array {

        $nombre = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));

        $fechaNac = '';

        if (!empty($persona['Fecha_Nacimiento']) && (string)$persona['Fecha_Nacimiento'] !== '0000-00-00') {

            $fechaNac = substr((string)$persona['Fecha_Nacimiento'], 0, 10);

        }

        return [

            'id_persona' => (int)($persona['Id_Persona'] ?? 0),

            'nombre' => trim($nombre),

            'nombre_pila' => trim((string)($persona['Nombre'] ?? '')),

            'apellido' => trim((string)($persona['Apellido'] ?? '')),

            'documento' => (string)($persona['Numero_Documento'] ?? ''),

            'telefono' => (string)($persona['Telefono'] ?? ''),

            'email' => (string)($persona['Email'] ?? ''),

            'direccion' => (string)($persona['Direccion'] ?? ''),

            'fecha_nacimiento' => $fechaNac,

            'edad' => (string)($persona['Edad'] ?? ''),

            'lider' => trim((string)($persona['Nombre_Lider'] ?? '')),

            'ministerio' => trim((string)($persona['Nombre_Ministerio'] ?? '')),

        ];

    }



    /**

     * @return int Id_Persona creado o 0 si falla

     */

    private function crearPersonaDesdePost(array $post, bool $esTourLevantate = false): int {

        $nombre = trim((string)($post['persona_nombre'] ?? ''));

        $apellido = trim((string)($post['persona_apellido'] ?? ''));

        $documento = preg_replace('/\D+/', '', (string)($post['persona_documento'] ?? ''));

        $telefono = preg_replace('/\D+/', '', (string)($post['persona_telefono'] ?? ''));

        $tipoDocumento = trim((string)($post['persona_tipo_documento'] ?? ''));

        if ($tipoDocumento === '') {

            $tipoDocumento = 'Cedula de Ciudadania';

        }

        $nehemias = null;

        if ($esTourLevantate) {

            $nehemias = $this->buscarEnNehemias($documento, $telefono);

            if (is_array($nehemias)) {

                if ($nombre === '') {

                    $nombre = trim((string)($nehemias['Nombres'] ?? ''));

                }

                if ($apellido === '') {

                    $apellido = trim((string)($nehemias['Apellidos'] ?? ''));

                }

                if ($documento === '') {

                    $documento = preg_replace('/\D+/', '', (string)($nehemias['Numero_Cedula'] ?? ''));

                }

                if ($telefono === '') {

                    $telefono = preg_replace('/\D+/', '', (string)($nehemias['Telefono'] ?? $nehemias['Telefono_Normalizado'] ?? ''));

                }

            }

        }



        $duplicado = $this->personaModel->findDuplicateByCedulaOrTelefono($documento, $telefono, null, $tipoDocumento);

        $idDuplicado = (int)($duplicado['Id_Persona'] ?? 0);

        if ($idDuplicado > 0) {

            return $idDuplicado;

        }



        $this->personaModel->ensureProcesoColumnExists();

        $this->personaModel->ensureCanalCreacionColumnExists();

        $this->personaModel->ensureCreadoPorColumnExists();

        $this->personaModel->ensureEsAntiguoColumnExists();



        $data = [

            'Nombre' => $nombre,

            'Apellido' => $apellido,

            'Tipo_Documento' => $tipoDocumento,

            'Numero_Documento' => $documento !== '' ? $documento : null,

            'Telefono' => $telefono !== '' ? $telefono : null,

            'Invitado_Por' => 'Tour Levántate y Resplandece',

            'Fecha_Registro' => date('Y-m-d H:i:s'),

            'Fecha_Registro_Unix' => time(),

            'Estado_Cuenta' => 'Activo',

        ];

        if ($esTourLevantate) {

            $data['Tipo_Reunion'] = null;

        } else {

            $data['Tipo_Reunion'] = 'Domingo';

        }



        $idRol = $esTourLevantate
            ? $this->obtenerIdRolDiscipuloDefault()
            : $this->obtenerIdRolAsistenteDefault();

        if ($idRol <= 0) {

            $idRol = $this->obtenerIdRolAsistenteDefault();

        }

        if ($idRol > 0) {

            $data['Id_Rol'] = $idRol;

        }



        if ($this->personaModel->tieneColumna('Proceso')) {

            $data['Proceso'] = $esTourLevantate ? null : 'Ganar';

        }

        if ($this->personaModel->tieneColumna('Canal_Creacion')) {

            $data['Canal_Creacion'] = 'Tour Levántate y Resplandece';

        }

        if ($this->personaModel->tieneColumna('Creado_Por')) {

            $data['Creado_Por'] = null;

        }

        if ($this->personaModel->tieneColumna('Es_Antiguo')) {

            $data['Es_Antiguo'] = $esTourLevantate ? 1 : 0;

        }

        if ($this->personaModel->tieneColumna('Origen_Ganar') && !$esTourLevantate) {

            $data['Origen_Ganar'] = 'Domingo';

        }



        try {

            return (int)$this->personaModel->create($data);

        } catch (Throwable $e) {

            error_log('TallerPersonaSync crear persona tour: ' . $e->getMessage());

            return 0;

        }

    }



    /**

     * @return array<string, mixed>|null

     */

    private function buscarEnNehemias(string $documento, string $telefono = ''): ?array {

        static $nehemiasModel = null;

        if ($nehemiasModel === null) {

            if (!class_exists('Nehemias')) {

                require_once APP . '/Models/Nehemias.php';

            }

            try {

                $nehemiasModel = new Nehemias();

            } catch (Throwable $e) {

                return null;

            }

        }



        try {

            return $nehemiasModel->buscarPorDocumentoOTelefono($documento, $telefono);

        } catch (Throwable $e) {

            return null;

        }

    }



    /**

     * @param array<string, mixed> $nehemias

     * @return array<string, mixed>

     */

    private function formatearNehemiasParaFormulario(array $nehemias): array {

        $telefono = trim((string)($nehemias['Telefono'] ?? ''));

        if ($telefono === '') {

            $telefono = trim((string)($nehemias['Telefono_Normalizado'] ?? ''));

        }



        return [

            'id_persona' => 0,

            'nombre' => trim((string)($nehemias['Nombres'] ?? '') . ' ' . (string)($nehemias['Apellidos'] ?? '')),

            'nombre_pila' => trim((string)($nehemias['Nombres'] ?? '')),

            'apellido' => trim((string)($nehemias['Apellidos'] ?? '')),

            'documento' => preg_replace('/\D+/', '', (string)($nehemias['Numero_Cedula'] ?? '')),

            'telefono' => preg_replace('/\D+/', '', $telefono),

            'email' => '',

            'direccion' => '',

            'fecha_nacimiento' => '',

            'edad' => '',

            'lider' => trim((string)($nehemias['Lider_Nehemias'] ?? '')),

            'ministerio' => trim((string)($nehemias['Lider'] ?? '')),

            'origen_externo' => 'nehemias',

        ];

    }



    private function obtenerIdRolDiscipuloDefault(): int {

        try {

            $rows = $this->personaModel->query('SELECT Id_Rol, Nombre_Rol FROM rol ORDER BY Id_Rol ASC');

            foreach ((array)$rows as $row) {

                $nombreRol = strtolower(trim((string)($row['Nombre_Rol'] ?? '')));

                $nombreRol = strtr($nombreRol, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

                if (str_contains($nombreRol, 'discipulo')) {

                    return (int)($row['Id_Rol'] ?? 0);

                }

            }

        } catch (Throwable $e) {

            return 0;

        }



        return 0;

    }



    private function obtenerIdRolAsistenteDefault(): int {

        try {

            $rows = $this->personaModel->query('SELECT Id_Rol, Nombre_Rol FROM rol ORDER BY Id_Rol ASC');

            foreach ((array)$rows as $row) {

                $nombreRol = strtolower(trim((string)($row['Nombre_Rol'] ?? '')));

                $nombreRol = strtr($nombreRol, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

                if (str_contains($nombreRol, 'asistente')) {

                    return (int)($row['Id_Rol'] ?? 0);

                }

            }

        } catch (Throwable $e) {

            return 0;

        }



        return 0;

    }



}


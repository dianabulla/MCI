<?php
/**
 * Controlador de Registro Público de Personas (Nuevos)
 * Este controlador NO requiere autenticación
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/WhatsappLocalQueue.php';
require_once APP . '/Models/WhatsappMensajeTemplate.php';

class RegistroPersonaController extends BaseController {
    private $personaModel;
    private $ministerioModel;
    private $whatsappLocalQueueModel;
    private $whatsappMensajeTemplateModel;
    private $soportaProceso = false;
    private $soportaOrigenGanar = false;
    private $soportaObservacionGanadoEn = false;
    private $soportaCreadoPor = false;
    private $soportaCanalCreacion = false;
    private $soportaEsAntiguo = false;
    private $soportaTratamientoDatos = false;
    private $idRolAsistenteCache = null;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->ministerioModel = new Ministerio();
        $this->whatsappLocalQueueModel = new WhatsappLocalQueue();
        $this->whatsappMensajeTemplateModel = new WhatsappMensajeTemplate();

        $this->personaModel->ensureProcesoColumnExists();
        $this->personaModel->ensureOrigenGanarColumnExists();
        $this->personaModel->ensureObservacionGanadoEnColumnExists();
        $this->personaModel->ensureTipoReunionOtrosValueExists();
        $this->personaModel->repararTipoReunionOtrosSinDato();
        $this->personaModel->ensureCreadoPorColumnExists();
        $this->personaModel->ensureCanalCreacionColumnExists();
        $this->personaModel->ensureEsAntiguoColumnExists();
        $this->personaModel->ensureTratamientoDatosColumnExists();

        $this->soportaProceso = $this->personaModel->tieneColumna('Proceso');
        $this->soportaOrigenGanar = $this->personaModel->tieneColumna('Origen_Ganar');
        $this->soportaObservacionGanadoEn = $this->personaModel->tieneColumna('Observacion_Ganado_En');
        $this->soportaCreadoPor = $this->personaModel->tieneColumna('Creado_Por');
        $this->soportaCanalCreacion = $this->personaModel->tieneColumna('Canal_Creacion');
        $this->soportaEsAntiguo = $this->personaModel->tieneColumna('Es_Antiguo');
        $this->soportaTratamientoDatos = $this->personaModel->tieneColumna('Tratamiento_Datos');
    }

    private function normalizarTratamientoDatosInput($valor): ?string {
        $texto = strtolower(trim((string)$valor));
        if (in_array($texto, ['acepta', 'si', 'sí', '1', 'yes'], true)) {
            return 'Acepta';
        }
        if (in_array($texto, ['no acepta', 'no_acepta', 'no', '0'], true)) {
            return 'No acepta';
        }
        return null;
    }

    private function buildAbsolutePublicUrl($route) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        }
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim(PUBLIC_URL, '/');
        return $scheme . '://' . $host . $base . '/index.php?url=' . urlencode($route);
    }

    private function normalizarTextoMayusculas($valor) {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return '';
        }

        $valor = preg_replace('/\s+/', ' ', $valor);
        return function_exists('mb_strtoupper') ? mb_strtoupper($valor, 'UTF-8') : strtoupper($valor);
    }

    private function obtenerIdRolAsistenteDefault() {
        if ($this->idRolAsistenteCache !== null) {
            return $this->idRolAsistenteCache;
        }

        $this->idRolAsistenteCache = 0;

        try {
            $rows = $this->personaModel->query("SELECT Id_Rol, Nombre_Rol FROM rol ORDER BY Id_Rol ASC");
            foreach ((array)$rows as $row) {
                $nombreRol = strtolower(trim((string)($row['Nombre_Rol'] ?? '')));
                $nombreRol = strtr($nombreRol, [
                    'á' => 'a',
                    'é' => 'e',
                    'í' => 'i',
                    'ó' => 'o',
                    'ú' => 'u',
                    'ü' => 'u',
                    'ñ' => 'n'
                ]);

                if (strpos($nombreRol, 'asistente') !== false) {
                    $this->idRolAsistenteCache = (int)($row['Id_Rol'] ?? 0);
                    break;
                }
            }
        } catch (Exception $e) {
            $this->idRolAsistenteCache = 0;
        }

        return $this->idRolAsistenteCache;
    }

    private function obtenerIdRolDiscipuloDefault() {
        require_once APP . '/Helpers/PermisosCatalogo.php';
        try {
            $rows = $this->personaModel->query("SELECT Id_Rol, Nombre_Rol FROM rol ORDER BY Id_Rol ASC");
            $id = PermisosCatalogo::resolverIdRolDiscipuloDesdeCatalogo((array)$rows);
            if ($id > 0) {
                return $id;
            }
        } catch (Exception $e) {
            return 0;
        }

        return 0;
    }

    private function aplicarAccesoAutomaticoDiscipulo(array &$data) {
        $idRol = (int)($data['Id_Rol'] ?? 0);
        $idRolDiscipulo = (int)$this->obtenerIdRolDiscipuloDefault();
        if ($idRol <= 0 || $idRolDiscipulo <= 0 || $idRol !== $idRolDiscipulo) {
            return;
        }

        $documento = trim((string)($data['Numero_Documento'] ?? ''));
        if ($documento === '') {
            return;
        }

        $data['Estado_Cuenta'] = 'Activo';
    }

    private function normalizarDocumentoInput($valor) {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return '';
        }

        $valor = preg_replace('/\s+/', '', $valor);
        return function_exists('mb_strtoupper') ? mb_strtoupper($valor, 'UTF-8') : strtoupper($valor);
    }

    private function construirMensajeDuplicadoPersona(array $duplicado, $cedula, $telefono) {
        $motivo = (string)($duplicado['_motivo'] ?? '');
        $nombre = trim((string)($duplicado['Nombre'] ?? '') . ' ' . (string)($duplicado['Apellido'] ?? ''));
        $sufijoNombre = $nombre !== '' ? ': ' . $nombre . '.' : '.';

        if ($motivo === 'documento') {
            return 'Ya existe una persona con ese número de documento' . $sufijoNombre;
        }
        if ($motivo === 'telefono') {
            return 'Ya existe una persona con ese teléfono' . $sufijoNombre
                . ' Solo un menor (Registro Civil o Tarjeta de Identidad) puede repetir un teléfono.';
        }

        $cedula = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string)$cedula)));
        $telefono = preg_replace('/\D+/', '', (string)$telefono);
        $duplicadoCedula = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string)($duplicado['Numero_Documento'] ?? ''))));
        $duplicadoTelefono = preg_replace('/\D+/', '', (string)($duplicado['Telefono'] ?? ''));

        if ($cedula !== '' && $duplicadoCedula !== '' && $cedula === $duplicadoCedula) {
            return 'Ya existe una persona con ese número de documento' . $sufijoNombre;
        }
        if ($telefono !== '' && $duplicadoTelefono !== '' && $telefono === $duplicadoTelefono) {
            return 'Ya existe una persona con ese teléfono' . $sufijoNombre
                . ' Solo un menor (Registro Civil o Tarjeta de Identidad) puede repetir un teléfono.';
        }

        return 'Ya existe una persona registrada con esos datos' . $sufijoNombre;
    }

    private function encolarMensajeBienvenida(array $personaNueva) {
        if (!$this->whatsappLocalQueueModel || !$this->whatsappMensajeTemplateModel) {
            return;
        }

        $idPersona = (int)($personaNueva['Id_Persona'] ?? 0);
        $telefonoPersona = (string)($personaNueva['Telefono'] ?? '');
        if ($idPersona <= 0 || trim($telefonoPersona) === '') {
            return;
        }

        $nombrePersona = trim((string)($personaNueva['Nombre'] ?? '') . ' ' . (string)($personaNueva['Apellido'] ?? ''));
        $nombreMinisterio = '';

        if (!empty($personaNueva['Nombre_Ministerio'])) {
            $nombreMinisterio = (string)$personaNueva['Nombre_Ministerio'];
        } elseif (!empty($personaNueva['Id_Ministerio'])) {
            $ministerio = $this->ministerioModel->getById((int)$personaNueva['Id_Ministerio']);
            $nombreMinisterio = (string)($ministerio['Nombre_Ministerio'] ?? '');
        }

        $payload = $this->whatsappMensajeTemplateModel->getTemplatePayload('bienvenida_persona', [
            'persona_nombre' => $nombrePersona,
            'persona_telefono' => $telefonoPersona,
            'persona_id' => $idPersona,
            'ministerio_nombre' => $nombreMinisterio,
        ]);

        $this->whatsappLocalQueueModel->encolar(
            $telefonoPersona,
            (string)($payload['mensaje'] ?? ''),
            'bienvenida_persona',
            'persona:' . $idPersona,
            $payload['media_url'] ?? null,
            $payload['media_tipo'] ?? null
        );
    }

    public function index() {
        $data = [
            'ministerios' => $this->ministerioModel->getAll(),
            'soportaTratamientoDatos' => $this->soportaTratamientoDatos,
            'mensaje' => $_GET['mensaje'] ?? null,
            'tipo_mensaje' => $_GET['tipo'] ?? null,
            'registro_exitoso' => isset($_GET['exito']) && $_GET['exito'] === '1',
            'old' => [
                'nombre' => (string)($_GET['nombre'] ?? ''),
                'apellido' => (string)($_GET['apellido'] ?? ''),
                'telefono' => (string)($_GET['telefono'] ?? ''),
                'id_ministerio' => (string)($_GET['id_ministerio'] ?? ''),
                'invitado_por' => (string)($_GET['invitado_por'] ?? ''),
                'ganado_en' => (string)($_GET['ganado_en'] ?? ''),
                'ganado_en_otro_observacion' => (string)($_GET['ganado_en_otro_observacion'] ?? ''),
                'fecha_nacimiento' => (string)($_GET['fecha_nacimiento'] ?? ''),
                'barrio' => (string)($_GET['barrio'] ?? ''),
                'cedula' => (string)($_GET['cedula'] ?? ''),
                'tipo_documento' => (string)($_GET['tipo_documento'] ?? ''),
                'genero' => (string)($_GET['genero'] ?? '')
            ]
        ];

        $this->view('personas_publico/formulario', $data);
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '?url=registro_personas');
            exit;
        }

        $nombre = $this->normalizarTextoMayusculas($_POST['nombre'] ?? '');
        $apellido = $this->normalizarTextoMayusculas($_POST['apellido'] ?? '');
        $telefono = $this->normalizarTelefono((string)($_POST['telefono'] ?? ''));
        $idMinisterioRaw = trim((string)($_POST['id_ministerio'] ?? ''));
        $idMinisterio = ctype_digit($idMinisterioRaw) ? (int)$idMinisterioRaw : 0;
        $invitadoPor = $this->normalizarTextoMayusculas($_POST['invitado_por'] ?? '');
        $ganadoEnRaw = strtolower(trim((string)($_POST['ganado_en'] ?? '')));
        $ganadoEnOtroObservacion = $this->normalizarTextoMayusculas($_POST['ganado_en_otro_observacion'] ?? '');
        $fechaNacimiento = trim((string)($_POST['fecha_nacimiento'] ?? ''));
        $barrio = $this->normalizarTextoMayusculas($_POST['barrio'] ?? '');
        $tipoDocumento = $this->normalizarTipoDocumentoInput($_POST['tipo_documento'] ?? '');
        $cedula = $this->normalizarDocumentoInput($_POST['cedula'] ?? '');
        $genero = $this->normalizarGeneroBinario($_POST['genero'] ?? '');
        $tipoPersonaInput = 'nueva';

        $errores = [];

        $erroresNombreApellido = $this->validarNombreYApellidoPersona($nombre, $apellido);
        if (!empty($erroresNombreApellido)) {
            $errores = array_merge($errores, $erroresNombreApellido);
        }

        if ($tipoDocumento === '') {
            $errores[] = 'Debe seleccionar el tipo de documento';
        }

        if ($cedula === '') {
            $errores[] = 'La cédula es obligatoria';
        }

        if ($telefono === '') {
            $errores[] = 'El teléfono es obligatorio';
        } elseif (strlen(preg_replace('/\D+/', '', $telefono)) < 7) {
            $errores[] = 'El teléfono debe tener al menos 7 dígitos';
        }

        if ($fechaNacimiento === '' || $fechaNacimiento === '0000-00-00') {
            $errores[] = 'La fecha de nacimiento es obligatoria';
        }

        if ($genero === null || $genero === '') {
            $errores[] = 'Debe seleccionar el género';
        }

        if (!in_array($ganadoEnRaw, ['domingo', 'somos_uno', 'celula', 'otro'], true)) {
            $errores[] = 'Debe seleccionar en qué reunión fue ganado (domingo, Somos Uno, célula u otros)';
        }

        if ($ganadoEnRaw === 'otro' && $ganadoEnOtroObservacion === '') {
            $errores[] = 'Debes escribir una observación cuando seleccionas Otros';
        }

        $tratamientoDatos = null;
        if ($this->soportaTratamientoDatos) {
            $tratamientoDatos = $this->normalizarTratamientoDatosInput($_POST['tratamiento_datos'] ?? '');
            if ($tratamientoDatos === null) {
                $errores[] = 'Debe indicar si acepta o no el tratamiento de datos';
            }
        }

        $duplicado = $this->personaModel->findDuplicateByCedulaOrTelefono($cedula, $telefono, null, $tipoDocumento);
        if (!empty($duplicado)) {
            $errores[] = $this->construirMensajeDuplicadoPersona($duplicado, $cedula, $telefono);
        }

        if (!empty($errores)) {
            $this->redirigirConError($errores, [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'telefono' => $telefono,
                'id_ministerio' => $idMinisterioRaw,
                'invitado_por' => $invitadoPor,
                'ganado_en' => $ganadoEnRaw,
                'ganado_en_otro_observacion' => $ganadoEnOtroObservacion,
                'fecha_nacimiento' => $fechaNacimiento,
                'barrio' => $barrio,
                'cedula' => $cedula,
                'tipo_documento' => $tipoDocumento,
                'genero' => $genero ?? (string)($_POST['genero'] ?? '')
            ]);
        }

        $mapTipoReunion = [
            'domingo' => 'Domingo',
            'somos_uno' => 'Somos Uno',
            'celula' => 'Celula',
            'otro' => 'Otros'
        ];
        $tipoReunion = $mapTipoReunion[$ganadoEnRaw] ?? 'Domingo';

        $data = [
            'Nombre' => $nombre,
            'Apellido' => $apellido,
            'Tipo_Documento' => $tipoDocumento,
            'Numero_Documento' => $cedula,
            'Fecha_Nacimiento' => $fechaNacimiento !== '' ? $fechaNacimiento : null,
            'Genero' => $genero,
            'Telefono' => $telefono !== '' ? $telefono : null,
            'Barrio' => $barrio !== '' ? $barrio : null,
            'Id_Ministerio' => $idMinisterio > 0 ? $idMinisterio : null,
            'Invitado_Por' => $invitadoPor !== '' ? $invitadoPor : null,
            'Tipo_Reunion' => $tipoReunion,
            'Fecha_Registro' => date('Y-m-d H:i:s'),
            // Queda visible en filtros operativos por defecto.
            'Estado_Cuenta' => 'Activo'
        ];

        if ($this->soportaTratamientoDatos && $tratamientoDatos !== null) {
            $data['Tratamiento_Datos'] = $tratamientoDatos;
        }

        $idRolDiscipulo = $this->obtenerIdRolDiscipuloDefault();
        if ($idRolDiscipulo > 0) {
            $data['Id_Rol'] = $idRolDiscipulo;
        } else {
            $idRolAsistente = $this->obtenerIdRolAsistenteDefault();
            if ($idRolAsistente > 0) {
                $data['Id_Rol'] = $idRolAsistente;
            }
        }

        if ($this->soportaCreadoPor) {
            $data['Creado_Por'] = null;
        }

        if ($this->soportaCanalCreacion) {
            $data['Canal_Creacion'] = 'Formulario público';
        }

        if ($this->soportaEsAntiguo) {
            $data['Es_Antiguo'] = 0;
        }

        if ($this->soportaProceso) {
            // Toda persona nueva debe entrar en Pendiente por consolidar.
            $data['Proceso'] = 'Ganar';
        }

        if ($this->soportaObservacionGanadoEn) {
            $data['Observacion_Ganado_En'] = $tipoReunion === 'Otros' ? $ganadoEnOtroObservacion : null;
        }

        if ($this->soportaOrigenGanar) {
            if ($tipoReunion === 'Celula') {
                $data['Origen_Ganar'] = 'Celula';
            } elseif ($tipoReunion === 'Domingo' || $tipoReunion === 'Somos Uno') {
                $data['Origen_Ganar'] = 'Domingo';
            } else {
                $data['Origen_Ganar'] = null;
            }
        }

        $this->aplicarAccesoAutomaticoDiscipulo($data);

        try {
            $idNuevaPersona = (int)$this->personaModel->create($data);

            if ($idNuevaPersona > 0) {
                $idRolNuevo = (int)($data['Id_Rol'] ?? 0);
                $idRolDiscipulo = (int)$this->obtenerIdRolDiscipuloDefault();
                $documento = trim((string)($data['Numero_Documento'] ?? ''));
                if ($documento !== '' && $idRolNuevo > 0 && $idRolDiscipulo > 0 && $idRolNuevo === $idRolDiscipulo) {
                    require_once APP . '/Models/UsuarioAcceso.php';
                    (new UsuarioAcceso())->provisionarParaPersona(
                        $idNuevaPersona,
                        $documento,
                        $documento,
                        $idRolNuevo
                    );
                }
            }

            if ($idNuevaPersona <= 0) {
                $query = http_build_query([
                    'url' => 'registro_personas',
                    'mensaje' => 'No se pudo registrar la persona. Intenta nuevamente.',
                    'tipo' => 'error'
                ]);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            $personaCreada = $this->personaModel->getById($idNuevaPersona);
            if (!empty($personaCreada)) {
                $this->encolarMensajeBienvenida($personaCreada);
            }

            $query = http_build_query([
                'url' => 'registro_personas',
                'mensaje' => 'Registro exitoso. La persona quedó en pendiente por consolidar.',
                'tipo' => 'success',
                'exito' => '1'
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        } catch (Exception $e) {
            $query = http_build_query([
                'url' => 'registro_personas',
                'mensaje' => 'Error al guardar el registro: ' . $e->getMessage(),
                'tipo' => 'error'
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }
    }

    private function normalizarTelefono($telefono) {
        $telefono = trim($telefono);
        if ($telefono === '') {
            return '';
        }

        // Permitir solo dígitos y + para facilitar uso en WhatsApp.
        $telefono = preg_replace('/[^0-9+]/', '', $telefono);

        // Si hay varios +, dejar solo el primero al inicio.
        if (substr_count($telefono, '+') > 1) {
            $telefono = '+' . str_replace('+', '', $telefono);
        }

        if (strpos($telefono, '+') > 0) {
            $telefono = '+' . str_replace('+', '', $telefono);
        }

        return $telefono;
    }

    private function normalizarGeneroBinario($genero) {
        $genero = trim((string)$genero);
        if ($genero === '') {
            return null;
        }

        $generoLower = strtolower($genero);
        if (in_array($generoLower, ['hombre', 'joven hombre', 'joven_hombre', 'masculino', 'm'], true)) {
            return 'Hombre';
        }
        if (in_array($generoLower, ['mujer', 'joven mujer', 'joven_mujer', 'femenino', 'f'], true)) {
            return 'Mujer';
        }

        return null;
    }

    private function normalizarTipoDocumentoInput($valor) {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return '';
        }

        $valorNormalizado = strtoupper($valor);
        $valorNormalizado = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $valorNormalizado);

        $mapa = [
            'CC' => 'Cedula de Ciudadania',
            'CEDULA DE CIUDADANIA' => 'Cedula de Ciudadania',
            'CEDULA CIUDADANIA' => 'Cedula de Ciudadania',
            'CE' => 'Cedula Extranjera',
            'CEDULA EXTRANJERA' => 'Cedula Extranjera',
            'TI' => 'Tarjeta de Identidad',
            'TARJETA DE IDENTIDAD' => 'Tarjeta de Identidad',
            'RC' => 'Registro Civil',
            'REGISTRO CIVIL' => 'Registro Civil'
        ];

        return $mapa[$valorNormalizado] ?? '';
    }

    private function redirigirConError(array $errores, array $old = []) {
        $query = http_build_query(array_merge([
            'url' => 'registro_personas',
            'mensaje' => implode('. ', $errores),
            'tipo' => 'error'
        ], $old));

        header('Location: ' . PUBLIC_URL . '?' . $query);
        exit;
    }
}

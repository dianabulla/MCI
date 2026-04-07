<?php
/**
 * Controlador de Registro Público de Personas (Nuevos)
 * Este controlador NO requiere autenticación
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/Peticion.php';
require_once APP . '/Models/WhatsappLocalQueue.php';
require_once APP . '/Models/WhatsappMensajeTemplate.php';

class RegistroPersonaController extends BaseController {
    private $personaModel;
    private $ministerioModel;
    private $peticionModel;
    private $whatsappLocalQueueModel;
    private $whatsappMensajeTemplateModel;
    private $soportaProceso = false;
    private $soportaOrigenGanar = false;
    private $soportaObservacionGanadoEn = false;
    private $soportaCreadoPor = false;
    private $soportaCanalCreacion = false;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->ministerioModel = new Ministerio();
        $this->peticionModel = new Peticion();
        $this->whatsappLocalQueueModel = new WhatsappLocalQueue();
        $this->whatsappMensajeTemplateModel = new WhatsappMensajeTemplate();

        $this->personaModel->ensureProcesoColumnExists();
        $this->personaModel->ensureOrigenGanarColumnExists();
        $this->personaModel->ensureObservacionGanadoEnColumnExists();
        $this->personaModel->ensureCreadoPorColumnExists();
        $this->personaModel->ensureCanalCreacionColumnExists();

        $this->soportaProceso = $this->personaModel->tieneColumna('Proceso');
        $this->soportaOrigenGanar = $this->personaModel->tieneColumna('Origen_Ganar');
        $this->soportaObservacionGanadoEn = $this->personaModel->tieneColumna('Observacion_Ganado_En');
        $this->soportaCreadoPor = $this->personaModel->tieneColumna('Creado_Por');
        $this->soportaCanalCreacion = $this->personaModel->tieneColumna('Canal_Creacion');
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

    private function normalizarDocumentoInput($valor) {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return '';
        }

        $valor = preg_replace('/\s+/', '', $valor);
        return function_exists('mb_strtoupper') ? mb_strtoupper($valor, 'UTF-8') : strtoupper($valor);
    }

    private function construirMensajeDuplicadoPersona(array $duplicado, $cedula, $telefono) {
        $cedula = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string)$cedula)));
        $telefono = preg_replace('/\D+/', '', (string)$telefono);
        $duplicadoCedula = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string)($duplicado['Numero_Documento'] ?? ''))));
        $duplicadoTelefono = preg_replace('/\D+/', '', (string)($duplicado['Telefono'] ?? ''));

        $campos = [];
        if ($cedula !== '' && $duplicadoCedula !== '' && $cedula === $duplicadoCedula) {
            $campos[] = 'la cédula';
        }
        if ($telefono !== '' && $duplicadoTelefono !== '' && $telefono === $duplicadoTelefono) {
            $campos[] = 'el teléfono';
        }

        $nombre = trim((string)($duplicado['Nombre'] ?? '') . ' ' . (string)($duplicado['Apellido'] ?? ''));
        $detalle = !empty($campos) ? implode(' y ', $campos) : 'los datos registrados';

        return 'Ya existe una persona registrada con ' . $detalle . ($nombre !== '' ? ': ' . $nombre . '.' : '.');
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
            'url_peticiones' => $this->buildAbsolutePublicUrl('peticiones/crear')
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

    private function registrarPeticionSiAplica($idPersona, $peticionTexto) {
        $idPersona = (int)$idPersona;
        $peticionTexto = trim((string)$peticionTexto);

        if ($idPersona <= 0 || $peticionTexto === '') {
            return;
        }

        $fechaHoy = date('Y-m-d');
        $existe = $this->peticionModel->query(
            "SELECT Id_Peticion FROM peticion WHERE Id_Persona = ? AND Descripcion_Peticion = ? AND Fecha_Peticion = ? LIMIT 1",
            [$idPersona, $peticionTexto, $fechaHoy]
        );

        if (!empty($existe)) {
            return;
        }

        $this->peticionModel->create([
            'Id_Persona' => $idPersona,
            'Descripcion_Peticion' => $peticionTexto,
            'Fecha_Peticion' => $fechaHoy,
            'Estado_Peticion' => 'Pendiente'
        ]);
    }

    public function index() {
        $data = [
            'ministerios' => $this->ministerioModel->getAll(),
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
                'peticion' => (string)($_GET['peticion'] ?? ''),
                'cedula' => (string)($_GET['cedula'] ?? '')
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
        $peticion = $this->normalizarTextoMayusculas($_POST['peticion'] ?? '');
        $cedula = $this->normalizarDocumentoInput($_POST['cedula'] ?? '');

        $errores = [];

        if ($nombre === '') {
            $errores[] = 'El nombre es requerido';
        }

        if ($apellido === '') {
            $errores[] = 'Los apellidos son requeridos';
        }

        if ($telefono !== '' && strlen(preg_replace('/\D+/', '', $telefono)) < 7) {
            $errores[] = 'Si registra teléfono, debe tener al menos 7 dígitos';
        }

        if (!in_array($ganadoEnRaw, ['domingo', 'somos_uno', 'celula', 'migrados', 'otro'], true)) {
            $errores[] = 'Debe seleccionar en qué reunión fue ganado (domingo, Somos Uno, célula, migrados u otros)';
        }

        if ($ganadoEnRaw === 'otro' && $ganadoEnOtroObservacion === '') {
            $errores[] = 'Debes escribir una observación cuando seleccionas Otros';
        }

        $duplicado = $this->personaModel->findDuplicateByCedulaOrTelefono($cedula, $telefono);
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
                'peticion' => $peticion,
                'cedula' => $cedula
            ]);
        }

        $mapTipoReunion = [
            'domingo' => 'Domingo',
            'somos_uno' => 'Somos Uno',
            'celula' => 'Celula',
            'migrados' => 'Migrados',
            'otro' => 'Otros'
        ];
        $tipoReunion = $mapTipoReunion[$ganadoEnRaw] ?? 'Domingo';

        $data = [
            'Nombre' => $nombre,
            'Apellido' => $apellido,
            'Tipo_Documento' => $cedula !== '' ? 'Cedula de Ciudadania' : null,
            'Numero_Documento' => $cedula !== '' ? $cedula : null,
            'Fecha_Nacimiento' => $fechaNacimiento !== '' ? $fechaNacimiento : null,
            'Telefono' => $telefono !== '' ? $telefono : null,
            'Barrio' => $barrio !== '' ? $barrio : null,
            'Peticion' => $peticion !== '' ? $peticion : null,
            'Id_Ministerio' => $idMinisterio > 0 ? $idMinisterio : null,
            'Invitado_Por' => $invitadoPor !== '' ? $invitadoPor : null,
            'Tipo_Reunion' => $tipoReunion,
            'Fecha_Registro' => date('Y-m-d H:i:s'),
            'Fecha_Registro_Unix' => time(),
            // Queda visible en filtros operativos por defecto.
            'Estado_Cuenta' => 'Activo'
        ];

        if ($this->soportaCreadoPor) {
            $data['Creado_Por'] = null;
        }

        if ($this->soportaCanalCreacion) {
            $data['Canal_Creacion'] = 'Formulario público';
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

        try {
            $idNuevaPersona = (int)$this->personaModel->create($data);

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

            $this->registrarPeticionSiAplica($idNuevaPersona, $peticion);

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

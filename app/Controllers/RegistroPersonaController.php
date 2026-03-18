<?php
/**
 * Controlador de Registro Público de Personas (Nuevos)
 * Este controlador NO requiere autenticación
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Ministerio.php';

class RegistroPersonaController extends BaseController {
    private $personaModel;
    private $ministerioModel;
    private $soportaProceso = false;
    private $soportaOrigenGanar = false;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->ministerioModel = new Ministerio();

        $this->personaModel->ensureProcesoColumnExists();
        $this->personaModel->ensureOrigenGanarColumnExists();

        $this->soportaProceso = $this->personaModel->tieneColumna('Proceso');
        $this->soportaOrigenGanar = $this->personaModel->tieneColumna('Origen_Ganar');
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
                'ganado_en' => (string)($_GET['ganado_en'] ?? '')
            ]
        ];

        $this->view('personas_publico/formulario', $data);
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '?url=registro_personas');
            exit;
        }

        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $apellido = trim((string)($_POST['apellido'] ?? ''));
        $telefono = $this->normalizarTelefono((string)($_POST['telefono'] ?? ''));
        $idMinisterio = isset($_POST['id_ministerio']) ? (int)$_POST['id_ministerio'] : 0;
        $invitadoPor = trim((string)($_POST['invitado_por'] ?? ''));
        $ganadoEnRaw = strtolower(trim((string)($_POST['ganado_en'] ?? '')));

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

        if (!in_array($ganadoEnRaw, ['domingo', 'celula'], true)) {
            $errores[] = 'Debe seleccionar si fue ganado en domingo o en célula';
        }

        if (!empty($errores)) {
            $this->redirigirConError($errores, [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'telefono' => $telefono,
                'id_ministerio' => (string)$idMinisterio,
                'invitado_por' => $invitadoPor,
                'ganado_en' => $ganadoEnRaw
            ]);
        }

        $tipoReunion = $ganadoEnRaw === 'domingo' ? 'Domingo' : 'Celula';

        $data = [
            'Nombre' => $nombre,
            'Apellido' => $apellido,
            'Telefono' => $telefono !== '' ? $telefono : null,
            'Id_Ministerio' => $idMinisterio > 0 ? $idMinisterio : null,
            'Invitado_Por' => $invitadoPor !== '' ? $invitadoPor : null,
            'Tipo_Reunion' => $tipoReunion,
            'Fecha_Registro' => date('Y-m-d H:i:s'),
            'Fecha_Registro_Unix' => time(),
            // Queda visible en filtros operativos por defecto.
            'Estado_Cuenta' => 'Activo'
        ];

        if ($this->soportaProceso) {
            // Toda persona nueva debe entrar en Pendiente por consolidar.
            $data['Proceso'] = 'Ganar';
        }

        if ($this->soportaOrigenGanar) {
            $data['Origen_Ganar'] = $tipoReunion;
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

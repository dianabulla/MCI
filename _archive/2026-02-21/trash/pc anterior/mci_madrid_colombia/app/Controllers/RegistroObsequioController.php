<?php
/**
 * Controlador de Registro de Obsequio Navideño
 * Este controlador NO requiere autenticación
 */

require_once APP . '/Models/NinoNavidad.php';
require_once APP . '/Models/Ministerio.php';

class RegistroObsequioController extends BaseController {
    private $ninoModel;
    private $ministerioModel;

    public function __construct() {
        $this->ninoModel = new NinoNavidad();
        $this->ministerioModel = new Ministerio();
    }

    /**
     * Mostrar formulario de registro público
     */
    public function index() {
        // Obtener lista de ministerios
        $ministerios = $this->ministerioModel->getAll();

        $data = [
            'ministerios' => $ministerios,
            'mensaje' => $_GET['mensaje'] ?? null,
            'tipo_mensaje' => $_GET['tipo'] ?? null,
            'registro_exitoso' => isset($_GET['exito']) && $_GET['exito'] == '1'
        ];

        $this->view('registro_obsequio/formulario', $data);
    }

    /**
     * Procesar el registro
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '?url=registro_obsequio');
            exit;
        }

        // Validar campos requeridos
        $errores = [];

        if (empty($_POST['nombre_apellidos'])) {
            $errores[] = 'El nombre y apellidos son requeridos';
        }
        if (empty($_POST['fecha_nacimiento'])) {
            $errores[] = 'La fecha de nacimiento es requerida';
        }
        if (empty($_POST['nombre_acudiente'])) {
            $errores[] = 'El nombre del acudiente es requerido';
        }
        if (empty($_POST['telefono_acudiente'])) {
            $errores[] = 'El teléfono del acudiente es requerido';
        }
        if (empty($_POST['barrio'])) {
            $errores[] = 'El barrio es requerido';
        }
        if (empty($_POST['id_ministerio'])) {
            $errores[] = 'El ministerio es requerido';
        }

        if (!empty($errores)) {
            $mensaje = urlencode(implode('. ', $errores));
            header('Location: ' . PUBLIC_URL . '?url=registro_obsequio&mensaje=' . $mensaje . '&tipo=error');
            exit;
        }

        // Validar edad antes de guardar
        $edad = $this->ninoModel->calcularEdad($_POST['fecha_nacimiento']);
        if ($edad >= 11) {
            $mensaje = urlencode('Lo sentimos, el obsequio solo aplica para niños menores de 11 años');
            header('Location: ' . PUBLIC_URL . '?url=registro_obsequio&mensaje=' . $mensaje . '&tipo=error');
            exit;
        }

        // Preparar datos
        $data = [
            'Nombre_Apellidos' => trim($_POST['nombre_apellidos']),
            'Fecha_Nacimiento' => $_POST['fecha_nacimiento'],
            'Nombre_Acudiente' => trim($_POST['nombre_acudiente']),
            'Telefono_Acudiente' => trim($_POST['telefono_acudiente']),
            'Barrio' => trim($_POST['barrio']),
            'Id_Ministerio' => $_POST['id_ministerio']
        ];

        // Registrar
        $resultado = $this->ninoModel->registrarNino($data);

        // Redirigir con mensaje en URL
        $mensaje = urlencode($resultado['message']);
        $tipo = $resultado['success'] ? 'success' : 'error';
        $exito = $resultado['success'] ? '&exito=1' : '';
        
        header('Location: ' . PUBLIC_URL . '?url=registro_obsequio&mensaje=' . $mensaje . '&tipo=' . $tipo . $exito);
        exit;
    }
}

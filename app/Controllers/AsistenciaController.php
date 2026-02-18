<?php
/**
 * Controlador Asistencia
 */

require_once APP . '/Models/Asistencia.php';
require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Helpers/DataIsolation.php';

class AsistenciaController extends BaseController {
    private $asistenciaModel;
    private $celulaModel;
    private $personaModel;

    public function __construct() {
        $this->asistenciaModel = new Asistencia();
        $this->celulaModel = new Celula();
        $this->personaModel = new Persona();
    }

    public function index() {
        // Generar filtro según el rol del usuario
        $filtroAsistencias = DataIsolation::generarFiltroAsistencias();
        
        // Obtener asistencias con aislamiento de rol
        $asistencias = $this->asistenciaModel->getAllWithInfoAndRole($filtroAsistencias);
        
        $this->view('asistencias/lista', ['asistencias' => $asistencias]);
    }

    public function registrar() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('asistencias', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idCelula = $_POST['id_celula'];
            $fecha = $_POST['fecha'];
            $asistencias = $_POST['asistencias'] ?? [];
            $tema = $_POST['tema'] ?? null;
            $ofrenda = ($_POST['ofrenda'] ?? '') !== '' ? $_POST['ofrenda'] : null;
            $tipoCelula = $_POST['tipo_celula'] ?? null;
            $observaciones = $_POST['observaciones'] ?? null;
            
            foreach ($asistencias as $idPersona => $asistio) {
                // Convertir a entero: "1" o 1 = asistió, "0" o vacío = no asistió
                $asistioValor = (int)$asistio;
                $this->asistenciaModel->registrarAsistencia(
                    $idPersona,
                    $idCelula,
                    $fecha,
                    $asistioValor,
                    $tema,
                    $ofrenda,
                    $tipoCelula,
                    $observaciones
                );
            }
            
            $this->redirect('asistencias');
        } else {
            $data = [
                'celulas' => $this->celulaModel->getAll(),
                'personas' => $this->personaModel->getAll()
            ];
            $this->view('asistencias/formulario', $data);
        }
    }

    public function porCelula() {
        $idCelula = $_GET['id'] ?? null;
        
        if (!$idCelula) {
            $this->redirect('asistencias');
        }

        $asistencias = $this->asistenciaModel->getByCelula($idCelula);
        $celula = $this->celulaModel->getById($idCelula);
        
        $this->view('asistencias/porCelula', [
            'asistencias' => $asistencias,
            'celula' => $celula
        ]);
    }
}

<?php
/**
 * Controlador Asistencia
 */

require_once APP . '/Models/Asistencia.php';
require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Persona.php';

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
        $asistencias = $this->asistenciaModel->getAllWithInfo();
        $this->view('asistencias/lista', ['asistencias' => $asistencias]);
    }

    public function registrar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idCelula = $_POST['id_celula'];
            $fecha = $_POST['fecha'];
            $asistencias = $_POST['asistencias'] ?? [];
            
            foreach ($asistencias as $idPersona => $asistio) {
                // Convertir a entero: "1" o 1 = asistió, "0" o vacío = no asistió
                $asistioValor = (int)$asistio;
                $this->asistenciaModel->registrarAsistencia($idPersona, $idCelula, $fecha, $asistioValor);
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

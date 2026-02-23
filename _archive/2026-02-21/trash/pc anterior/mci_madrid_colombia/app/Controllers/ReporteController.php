<?php
/**
 * Controlador de Reportes y Estadísticas
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Asistencia.php';
require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Ministerio.php';

class ReporteController extends BaseController {
    private $personaModel;
    private $asistenciaModel;
    private $celulaModel;
    private $ministerioModel;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->asistenciaModel = new Asistencia();
        $this->celulaModel = new Celula();
        $this->ministerioModel = new Ministerio();
    }

    public function index() {
        // Obtener fechas del mes actual por defecto
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');

        // Datos para gráfico de almas ganadas
        $almasGanadas = $this->personaModel->getAlmasGanadasPorMinisterio($fechaInicio, $fechaFin);
        
        // Datos para gráfico de asistencia
        $asistenciaCelulas = $this->asistenciaModel->getAsistenciaPorCelula($fechaInicio, $fechaFin);

        $data = [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'almas_ganadas' => $almasGanadas,
            'asistencia_celulas' => $asistenciaCelulas
        ];

        $this->view('reportes/index', $data);
    }

    public function almasGanadas() {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');

        $data = $this->personaModel->getAlmasGanadasPorMinisterio($fechaInicio, $fechaFin);
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function asistenciaCelulas() {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');

        $data = $this->asistenciaModel->getAsistenciaPorCelula($fechaInicio, $fechaFin);
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}

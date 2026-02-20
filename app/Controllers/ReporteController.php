<?php
/**
 * Controlador de Reportes y Estadísticas
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Asistencia.php';
require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Helpers/DataIsolation.php';

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
        // Verificar permiso de ver reportes
        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('reportes', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        // Obtener fechas del mes actual por defecto
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');

        // Generar filtros según el rol del usuario
        $filtroRol = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();

        // Datos para gráfico de almas ganadas
        $almasGanadas = $this->personaModel->getAlmasGanadasPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol);
        
        // Datos para gráfico de asistencia
        $asistenciaCelulas = $this->asistenciaModel->getAsistenciaPorCelulaWithRole($fechaInicio, $fechaFin, $filtroCelulas);

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

        // Generar filtro según el rol del usuario
        $filtroRol = DataIsolation::generarFiltroPersonas();

        $data = $this->personaModel->getAlmasGanadasPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol);
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function asistenciaCelulas() {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');

        // Generar filtro según el rol del usuario
        $filtroCelulas = DataIsolation::generarFiltroCelulas();

        $data = $this->asistenciaModel->getAsistenciaPorCelulaWithRole($fechaInicio, $fechaFin, $filtroCelulas);
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}

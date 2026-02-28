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
        $filtroCelula = $_GET['celula'] ?? '';

        // Generar filtros según el rol del usuario
        $filtroRol = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();

        $celulasDisponiblesRaw = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);
        $celulasDisponibles = array_map(static function($celula) {
            return [
                'Id_Celula' => (int)($celula['Id_Celula'] ?? 0),
                'Nombre_Celula' => (string)($celula['Nombre_Celula'] ?? '')
            ];
        }, $celulasDisponiblesRaw);
        $celulaIdsPermitidas = array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulasDisponibles);

        $filtroCelula = ($filtroCelula !== '' && in_array((int)$filtroCelula, $celulaIdsPermitidas, true)) ? (int)$filtroCelula : (($filtroCelula === '0') ? '0' : '');

        // Datos para gráfico de almas ganadas
        $almasGanadas = $this->personaModel->getAlmasGanadasPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol);
        
        // Datos para gráfico de asistencia
        $asistenciaCelulas = $this->asistenciaModel->getAsistenciaPorCelulaWithRole($fechaInicio, $fechaFin, $filtroCelulas);

        if ($filtroCelula !== '') {
            if ((string)$filtroCelula === '0') {
                $asistenciaCelulas = [];
            } else {
                $idCelulaFiltro = (int)$filtroCelula;
                $asistenciaCelulas = array_values(array_filter($asistenciaCelulas, static function($item) use ($idCelulaFiltro) {
                    return (int)($item['Id_Celula'] ?? 0) === $idCelulaFiltro;
                }));
            }
        }

        $data = [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'filtro_celula' => (string)$filtroCelula,
            'celulas_disponibles' => $celulasDisponibles,
            'almas_ganadas' => $almasGanadas,
            'asistencia_celulas' => $asistenciaCelulas
        ];

        $this->view('reportes/index', $data);
    }

    public function exportarExcel() {
        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('reportes', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
        $filtroCelula = $_GET['celula'] ?? '';

        $filtroRol = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();

        $celulasDisponiblesRaw = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);
        $celulaIdsPermitidas = array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulasDisponiblesRaw);
        $filtroCelula = ($filtroCelula !== '' && in_array((int)$filtroCelula, $celulaIdsPermitidas, true)) ? (int)$filtroCelula : (($filtroCelula === '0') ? '0' : '');

        $almasGanadas = $this->personaModel->getAlmasGanadasPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol);
        $asistenciaCelulas = $this->asistenciaModel->getAsistenciaPorCelulaWithRole($fechaInicio, $fechaFin, $filtroCelulas);

        if ($filtroCelula !== '') {
            if ((string)$filtroCelula === '0') {
                $asistenciaCelulas = [];
            } else {
                $idCelulaFiltro = (int)$filtroCelula;
                $asistenciaCelulas = array_values(array_filter($asistenciaCelulas, static function($item) use ($idCelulaFiltro) {
                    return (int)($item['Id_Celula'] ?? 0) === $idCelulaFiltro;
                }));
            }
        }

        $rows = [];

        $rows[] = ['Almas Ganadas por Ministerio', '', '', '', '', '', ''];
        $rows[] = ['Periodo', $fechaInicio . ' a ' . $fechaFin, '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', ''];
        $rows[] = ['Ministerio', 'Hombres', 'Mujeres', 'Jovenes Hombres', 'Jovenes Mujeres', 'Total', ''];
        foreach ($almasGanadas as $item) {
            $rows[] = [
                (string)($item['Nombre_Ministerio'] ?? 'Sin ministerio'),
                (string)($item['Hombres'] ?? 0),
                (string)($item['Mujeres'] ?? 0),
                (string)($item['Jovenes_Hombres'] ?? 0),
                (string)($item['Jovenes_Mujeres'] ?? 0),
                (string)($item['Total'] ?? 0),
                ''
            ];
        }

        $rows[] = ['', '', '', '', '', '', ''];
        $rows[] = ['Asistencia por Celula', '', '', '', '', '', ''];
        $rows[] = ['Celula', 'Lider', 'Inscritos', 'Reuniones', 'Esperadas', 'Reales', 'Porcentaje'];
        foreach ($asistenciaCelulas as $item) {
            $esperadas = (int)($item['Asistencias_Esperadas'] ?? 0);
            $reales = (int)($item['Asistencias_Reales'] ?? 0);
            $porcentaje = $esperadas > 0 ? round(($reales / $esperadas) * 100, 1) : 0;

            $rows[] = [
                (string)($item['Nombre_Celula'] ?? ''),
                (string)(trim((string)($item['Nombre_Lider'] ?? '')) ?: 'Sin lider'),
                (string)($item['Total_Inscritos'] ?? 0),
                (string)($item['Reuniones_Realizadas'] ?? 0),
                (string)$esperadas,
                (string)$reales,
                (string)$porcentaje . '%'
            ];
        }

        $this->exportCsv(
            'reportes_' . date('Ymd_His'),
            ['Seccion', 'Columna 1', 'Columna 2', 'Columna 3', 'Columna 4', 'Columna 5', 'Columna 6'],
            $rows,
            false
        );
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

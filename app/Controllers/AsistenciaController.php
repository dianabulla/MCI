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
        $filtroCelulas = DataIsolation::generarFiltroCelulas();

        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';
        
        // Obtener asistencias con aislamiento de rol
        $asistencias = $this->asistenciaModel->getAllWithInfoAndRole($filtroAsistencias);

        // Base de células visibles para el usuario (opciones de filtros)
        $celulasBase = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);

        $ministeriosDisponibles = [];
        $ministerioIdsPermitidos = [];
        $lideresDisponibles = [];
        $liderIdsPermitidos = [];

        foreach ($celulasBase as $celulaBase) {
            $idMinisterioLider = (int)($celulaBase['Id_Ministerio_Lider'] ?? 0);
            $nombreMinisterioLider = trim((string)($celulaBase['Nombre_Ministerio_Lider'] ?? ''));
            if ($idMinisterioLider > 0 && $nombreMinisterioLider !== '') {
                $ministeriosDisponibles[$idMinisterioLider] = [
                    'Id_Ministerio' => $idMinisterioLider,
                    'Nombre_Ministerio' => $nombreMinisterioLider
                ];
                $ministerioIdsPermitidos[$idMinisterioLider] = true;
            }

            $idLider = (int)($celulaBase['Id_Lider'] ?? 0);
            $nombreLider = trim((string)($celulaBase['Nombre_Lider'] ?? ''));
            if ($idLider > 0 && $nombreLider !== '') {
                $lideresDisponibles[$idLider] = [
                    'Id_Persona' => $idLider,
                    'Nombre_Completo' => $nombreLider,
                    'Id_Ministerio' => $idMinisterioLider
                ];
                $liderIdsPermitidos[$idLider] = true;
            }
        }

        ksort($ministeriosDisponibles);
        ksort($lideresDisponibles);

        $filtroMinisterio = ($filtroMinisterio !== '' && isset($ministerioIdsPermitidos[(int)$filtroMinisterio])) ? (int)$filtroMinisterio : '';
        $filtroLider = ($filtroLider !== '' && isset($liderIdsPermitidos[(int)$filtroLider])) ? (int)$filtroLider : '';

        // Células visibles con filtros aplicados
        $celulas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas, $filtroMinisterio, $filtroLider);

        // Agrupar asistencias por célula
        $asistenciasPorCelula = [];
        foreach ($asistencias as $asistencia) {
            $idCelula = (int)($asistencia['Id_Celula'] ?? 0);
            if ($idCelula <= 0) {
                continue;
            }

            if (!isset($asistenciasPorCelula[$idCelula])) {
                $asistenciasPorCelula[$idCelula] = [];
            }

            $asistenciasPorCelula[$idCelula][] = $asistencia;
        }

        $sections = [];
        foreach ($celulas as $celula) {
            $idCelula = (int)($celula['Id_Celula'] ?? 0);
            $rowsAsistencia = $asistenciasPorCelula[$idCelula] ?? [];

            usort($rowsAsistencia, static function ($a, $b) {
                return strcmp((string)($b['Fecha_Asistencia'] ?? ''), (string)($a['Fecha_Asistencia'] ?? ''));
            });

            $fechaUltimoReporte = '';
            if (!empty($rowsAsistencia)) {
                $fechaUltimoReporte = (string)($rowsAsistencia[0]['Fecha_Asistencia'] ?? '');
            }

            $rowsUltimoReporte = [];
            if ($fechaUltimoReporte !== '') {
                $rowsUltimoReporte = array_values(array_filter($rowsAsistencia, static function ($registro) use ($fechaUltimoReporte) {
                    return (string)($registro['Fecha_Asistencia'] ?? '') === $fechaUltimoReporte;
                }));
            }

            $rows = [];
            $nro = 1;
            $totalSi = 0;
            $totalNo = 0;

            foreach ($rowsUltimoReporte as $registro) {
                $asistio = (int)($registro['Asistio'] ?? 0) === 1;
                if ($asistio) {
                    $totalSi++;
                } else {
                    $totalNo++;
                }

                $rows[] = [
                    'nro' => $nro++,
                    'id_persona' => (int)($registro['Id_Persona'] ?? 0),
                    'persona' => (string)($registro['Nombre_Persona'] ?? 'Sin nombre'),
                    'fecha' => (string)($registro['Fecha_Asistencia'] ?? ''),
                    'asistio' => $asistio
                ];
            }

            $sections[] = [
                'id_celula' => $idCelula,
                'label' => (string)($celula['Nombre_Celula'] ?? 'Célula sin nombre'),
                'lider' => (string)($celula['Nombre_Lider'] ?? 'Sin líder'),
                'anfitrion' => (string)($celula['Nombre_Anfitrion'] ?? 'Sin anfitrión'),
                'fecha_ultimo_reporte' => $fechaUltimoReporte,
                'total_registros' => count($rows),
                'total_si' => $totalSi,
                'total_no' => $totalNo,
                'rows' => $rows
            ];
        }

        $this->view('asistencias/lista', [
            'asistencias' => $asistencias,
            'sections' => $sections,
            'ministerios_disponibles' => array_values($ministeriosDisponibles),
            'lideres_disponibles' => array_values($lideresDisponibles),
            'filtro_ministerio_actual' => (string)$filtroMinisterio,
            'filtro_lider_actual' => (string)$filtroLider
        ]);
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
            $filtroCelulas = DataIsolation::generarFiltroCelulas();
            $filtroPersonas = DataIsolation::generarFiltroPersonas();
            $celulas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);
            $personas = $this->personaModel->getAllWithRole($filtroPersonas);

            $celulaPreseleccionada = null;
            if (isset($_GET['celula']) && $_GET['celula'] !== '') {
                $idCelulaSolicitada = (int) $_GET['celula'];
                foreach ($celulas as $celula) {
                    if ((int) $celula['Id_Celula'] === $idCelulaSolicitada) {
                        $celulaPreseleccionada = $idCelulaSolicitada;
                        break;
                    }
                }
            }

            $data = [
                'celulas' => $celulas,
                'personas' => $personas,
                'celula_preseleccionada' => $celulaPreseleccionada
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

<?php
/**
 * Controlador Asistencia
 */

require_once APP . '/Models/Asistencia.php';
require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/DataIsolation.php';

class AsistenciaController extends BaseController {
    private $asistenciaModel;
    private $celulaModel;
    private $personaModel;

    public function __construct() {
        $this->asistenciaModel = new Asistencia();
        $this->celulaModel = new Celula();
        $this->personaModel = new Persona();
        $this->asistenciaModel->ensureEntregaSobreTableExists();
    }

    private function resolverSemanaFiltro($semanaParam = '') {
        $semanaParam = trim((string)$semanaParam);

        if (preg_match('/^(\d{4})-W(\d{2})$/', $semanaParam, $m)) {
            $anio = (int)$m[1];
            $semana = (int)$m[2];
            if ($semana >= 1 && $semana <= 53) {
                $inicio = (new DateTimeImmutable('today'))->setISODate($anio, $semana, 1);
                $fin = $inicio->modify('+6 days');
                return [$inicio, $fin, $inicio->format('o-\\WW')];
            }
        }

        $hoy = new DateTimeImmutable('today');
        $inicio = $hoy->modify('monday this week');
        $fin = $inicio->modify('+6 days');

        return [$inicio, $fin, $inicio->format('o-\\WW')];
    }

    private function resolverReturnUrlAsistencias($rawReturnUrl = '') {
        $default = PUBLIC_URL . '?url=asistencias';
        $url = trim((string)$rawReturnUrl);
        if ($url === '') {
            return $default;
        }

        if (strpos($url, PUBLIC_URL) !== 0) {
            return $default;
        }

        if (strpos($url, '?url=asistencias') === false) {
            return $default;
        }

        return $url;
    }

    public function index() {
        if (!AuthController::tienePermiso('asistencias', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        // Generar filtro segun el rol del usuario
        $filtroAsistencias = DataIsolation::generarFiltroAsistencias();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();

        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';
        $filtroCelula = $_GET['celula'] ?? '';
        $filtroReporte = $_GET['reporte'] ?? '';
        $filtroReporte = in_array($filtroReporte, ['con', 'sin'], true) ? $filtroReporte : '';
        
        // Obtener asistencias con aislamiento de rol
        $asistencias = $this->asistenciaModel->getAllWithInfoAndRole($filtroAsistencias);

        // Semana seleccionada (lunes a domingo)
        [$inicioSemana, $finSemana, $semanaSeleccionada] = $this->resolverSemanaFiltro($_GET['semana'] ?? '');
        $inicioSemanaStr = $inicioSemana->format('Y-m-d');
        $finSemanaStr = $finSemana->format('Y-m-d');

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

        $celulasDisponibles = [];
        $celulaIdsPermitidos = [];
        foreach ($celulasBase as $celulaBase) {
            $idCelula = (int)($celulaBase['Id_Celula'] ?? 0);
            if ($idCelula <= 0) {
                continue;
            }
            $celulasDisponibles[] = [
                'Id_Celula' => $idCelula,
                'Nombre_Celula' => (string)($celulaBase['Nombre_Celula'] ?? ''),
                'Id_Lider' => (int)($celulaBase['Id_Lider'] ?? 0),
                'Id_Ministerio' => (int)($celulaBase['Id_Ministerio_Lider'] ?? 0)
            ];
            $celulaIdsPermitidos[$idCelula] = true;
        }
        $filtroCelula = ($filtroCelula !== '' && isset($celulaIdsPermitidos[(int)$filtroCelula])) ? (int)$filtroCelula : (($filtroCelula === '0') ? '0' : '');

        // Células visibles con filtros aplicados
        $celulas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas, $filtroMinisterio, $filtroLider);

        if ($filtroCelula !== '') {
            if ((string)$filtroCelula === '0') {
                $celulas = [];
            } else {
                $idCelulaFiltro = (int)$filtroCelula;
                $celulas = array_values(array_filter($celulas, static function($celula) use ($idCelulaFiltro) {
                    return (int)($celula['Id_Celula'] ?? 0) === $idCelulaFiltro;
                }));
            }
        }

        // Agrupar asistencias de la semana por célula
        $asistenciasSemanaPorCelula = [];
        foreach ($asistencias as $asistencia) {
            $idCelula = (int)($asistencia['Id_Celula'] ?? 0);
            if ($idCelula <= 0) {
                continue;
            }

            $fechaAsistencia = substr((string)($asistencia['Fecha_Asistencia'] ?? ''), 0, 10);
            if ($fechaAsistencia < $inicioSemanaStr || $fechaAsistencia > $finSemanaStr) {
                continue;
            }

            if (!isset($asistenciasSemanaPorCelula[$idCelula])) {
                $asistenciasSemanaPorCelula[$idCelula] = [];
            }

            $asistenciasSemanaPorCelula[$idCelula][] = $asistencia;
        }

        $sections = [];
        $reportaron = [];
        $noReportaron = [];
        $estadoEntregoSobre = $this->asistenciaModel->getEstadoEntregoSobrePorCelulaSemana(array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulas), $inicioSemanaStr);

        foreach ($celulas as $celula) {
            $idCelula = (int)($celula['Id_Celula'] ?? 0);
            $rowsAsistencia = $asistenciasSemanaPorCelula[$idCelula] ?? [];

            usort($rowsAsistencia, static function ($a, $b) {
                return strcmp((string)($b['Fecha_Asistencia'] ?? ''), (string)($a['Fecha_Asistencia'] ?? ''));
            });

            $fechasReporteMap = [];
            foreach ($rowsAsistencia as $registro) {
                $fechaRegistro = substr((string)($registro['Fecha_Asistencia'] ?? ''), 0, 10);
                if ($fechaRegistro !== '') {
                    $fechasReporteMap[$fechaRegistro] = true;
                }
            }
            $fechasReporte = array_keys($fechasReporteMap);
            rsort($fechasReporte);

            $rows = [];
            $nro = 1;
            $totalSi = 0;
            $totalNo = 0;

            foreach ($rowsAsistencia as $registro) {
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

            $siReportoSemana = !empty($rowsAsistencia);

            $sectionData = [
                'id_celula' => $idCelula,
                'label' => (string)($celula['Nombre_Celula'] ?? 'Célula sin nombre'),
                'ministerio' => (string)($celula['Nombre_Ministerio_Lider'] ?? 'Sin ministerio'),
                'lider' => (string)($celula['Nombre_Lider'] ?? 'Sin líder'),
                'anfitrion' => (string)($celula['Nombre_Anfitrion'] ?? 'Sin anfitrión'),
                'entrego_sobre' => !empty($estadoEntregoSobre[$idCelula]),
                'si_reporto_semana' => $siReportoSemana,
                'fechas_reporte_semana' => $fechasReporte,
                'total_registros' => count($rows),
                'total_si' => $totalSi,
                'total_no' => $totalNo,
                'rows' => $rows
            ];

            $sections[] = $sectionData;

            if ($siReportoSemana) {
                $reportaron[] = $sectionData;
            } else {
                $noReportaron[] = $sectionData;
            }
        }

        if ($filtroReporte !== '') {
            $sections = array_values(array_filter($sections, static function($section) use ($filtroReporte) {
                $tieneReporte = !empty($section['si_reporto_semana']);
                return $filtroReporte === 'con' ? $tieneReporte : !$tieneReporte;
            }));
        }

        $this->view('asistencias/lista', [
            'asistencias' => $asistencias,
            'sections' => $sections,
            'reportaron' => $reportaron,
            'no_reportaron' => $noReportaron,
            'semana_actual' => $semanaSeleccionada,
            'semana_inicio' => $inicioSemanaStr,
            'semana_fin' => $finSemanaStr,
            'ministerios_disponibles' => array_values($ministeriosDisponibles),
            'lideres_disponibles' => array_values($lideresDisponibles),
            'celulas_disponibles' => array_values($celulasDisponibles),
            'filtro_ministerio_actual' => (string)$filtroMinisterio,
            'filtro_lider_actual' => (string)$filtroLider,
            'filtro_celula_actual' => (string)$filtroCelula,
            'filtro_reporte_actual' => (string)$filtroReporte
        ]);
    }

    public function actualizarEntregoSobre() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Metodo no permitido'], 405);
        }

        if (!AuthController::tienePermiso('asistencias', 'editar')
            && !AuthController::tienePermiso('asistencias', 'crear')) {
            $this->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $idCelula = isset($_POST['id_celula']) ? (int)$_POST['id_celula'] : 0;
        $semanaInicio = trim((string)($_POST['semana_inicio'] ?? ''));
        $entregoSobre = !empty($_POST['entrego_sobre']) ? 1 : 0;

        if ($idCelula <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $semanaInicio)) {
            $this->json(['success' => false, 'error' => 'Datos invalidos'], 422);
        }

        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        if (!$this->celulaModel->existsByIdWithRole($idCelula, $filtroCelulas)) {
            $this->json(['success' => false, 'error' => 'No autorizado para esta celula'], 403);
        }

        $ok = $this->asistenciaModel->guardarEntregoSobreSemana($idCelula, $semanaInicio, $entregoSobre);
        if (!$ok) {
            $this->json(['success' => false, 'error' => 'No se pudo guardar'], 500);
        }

        $this->json([
            'success' => true,
            'id_celula' => $idCelula,
            'semana_inicio' => $semanaInicio,
            'entrego_sobre' => (bool)$entregoSobre
        ]);
    }

    public function exportarExcel() {
        if (!AuthController::tienePermiso('asistencias', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $filtroAsistencias = DataIsolation::generarFiltroAsistencias();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();

        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';
        $filtroCelula = $_GET['celula'] ?? '';
        $filtroReporte = $_GET['reporte'] ?? '';
        $filtroReporte = in_array($filtroReporte, ['con', 'sin'], true) ? $filtroReporte : '';

        [$inicioSemana, $finSemana, $semanaSeleccionada] = $this->resolverSemanaFiltro($_GET['semana'] ?? '');
        $inicioSemanaStr = $inicioSemana->format('Y-m-d');
        $finSemanaStr = $finSemana->format('Y-m-d');

        $asistencias = $this->asistenciaModel->getAllWithInfoAndRole($filtroAsistencias);
        $asistencias = array_values(array_filter($asistencias, static function($asistencia) use ($inicioSemanaStr, $finSemanaStr) {
            $fechaAsistencia = substr((string)($asistencia['Fecha_Asistencia'] ?? ''), 0, 10);
            if ($fechaAsistencia === '') {
                return false;
            }

            return $fechaAsistencia >= $inicioSemanaStr && $fechaAsistencia <= $finSemanaStr;
        }));

        $celulasFiltradas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas, $filtroMinisterio, $filtroLider);
        $celulaIdsPermitidas = array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulasFiltradas);

        if ($filtroCelula !== '') {
            if ((string)$filtroCelula === '0') {
                $celulaIdsPermitidas = [];
            } else {
                $idCelulaFiltro = (int)$filtroCelula;
                $celulaIdsPermitidas = in_array($idCelulaFiltro, $celulaIdsPermitidas, true) ? [$idCelulaFiltro] : [];
            }
        }

        $asistencias = array_values(array_filter($asistencias, static function($asistencia) use ($celulaIdsPermitidas) {
            $idCelula = (int)($asistencia['Id_Celula'] ?? 0);
            return in_array($idCelula, $celulaIdsPermitidas, true);
        }));

        if ($filtroReporte !== '') {
            $celulasConReporte = [];
            foreach ($asistencias as $asistencia) {
                $idCelulaAsistencia = (int)($asistencia['Id_Celula'] ?? 0);
                if ($idCelulaAsistencia > 0) {
                    $celulasConReporte[$idCelulaAsistencia] = true;
                }
            }

            if ($filtroReporte === 'con') {
                $asistencias = array_values(array_filter($asistencias, static function($asistencia) use ($celulasConReporte) {
                    $idCelulaAsistencia = (int)($asistencia['Id_Celula'] ?? 0);
                    return isset($celulasConReporte[$idCelulaAsistencia]);
                }));
            } else {
                $asistencias = [];
            }
        }

        $rows = [];
        foreach ($asistencias as $asistencia) {
            $rows[] = [
                (string)($asistencia['Nombre_Celula'] ?? ''),
                (string)($asistencia['Nombre_Persona'] ?? ''),
                (string)($asistencia['Fecha_Asistencia'] ?? ''),
                ((int)($asistencia['Asistio'] ?? 0) === 1) ? 'Si' : 'No',
                (string)($asistencia['Tema'] ?? ''),
                (string)($asistencia['Tipo_Celula'] ?? ''),
                (string)($asistencia['Observaciones'] ?? '')
            ];
        }

        $this->exportCsv(
            'asistencias_' . str_replace('-W', 'W', $semanaSeleccionada) . '_' . date('Ymd_His'),
            ['Celula', 'Persona', 'Fecha', 'Asistio', 'Tema', 'Tipo Celula', 'Observaciones'],
            $rows
        );
    }

    public function registrar() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('asistencias', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $returnUrl = $this->resolverReturnUrlAsistencias($_POST['return_url'] ?? '');
            $idCelula = $_POST['id_celula'];
            $fecha = $_POST['fecha'];
            $asistencias = $_POST['asistencias'] ?? [];
            $tema = $_POST['tema'] ?? null;
            $tipoCelula = $_POST['tipo_celula'] ?? null;
            $noSeRealizo = !empty($_POST['no_se_realizo']);
            $observaciones = trim((string)($_POST['observaciones'] ?? ''));

            if ($noSeRealizo) {
                foreach ($asistencias as $idPersona => $asistio) {
                    $asistencias[$idPersona] = 0;
                }

                if ($observaciones === '') {
                    $observaciones = 'No se realizó';
                } elseif (stripos($observaciones, 'no se realiz') === false) {
                    $observaciones = 'No se realizó. ' . $observaciones;
                }
            }

            $observaciones = $observaciones !== '' ? $observaciones : null;
            
            foreach ($asistencias as $idPersona => $asistio) {
                // Convertir a entero: "1" o 1 = asistió, "0" o vacío = no asistió
                $asistioValor = (int)$asistio;
                $this->asistenciaModel->registrarAsistencia(
                    $idPersona,
                    $idCelula,
                    $fecha,
                    $asistioValor,
                    $tema,
                    $tipoCelula,
                    $observaciones
                );
            }
            
            header('Location: ' . $returnUrl);
            exit;
        } else {
            $returnUrl = $this->resolverReturnUrlAsistencias($_GET['return_url'] ?? '');
            $filtroCelulas = DataIsolation::generarFiltroCelulas();
            $filtroPersonas = DataIsolation::generarFiltroPersonas();
            $celulas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);
            $personas = $this->personaModel->getAllWithRole($filtroPersonas);
            $idsPersonas = array_map(static function($persona) {
                return (int)($persona['Id_Persona'] ?? 0);
            }, $personas);
            $conteoAsistenciasPorPersona = $this->asistenciaModel->getConteoAsistenciasCompletasPorPersona($idsPersonas);

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

            $fechaPreseleccionada = date('Y-m-d');
            if (!empty($_GET['fecha'])) {
                $fechaSolicitada = trim((string)$_GET['fecha']);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSolicitada)) {
                    $fechaPreseleccionada = $fechaSolicitada;
                }
            }

            $data = [
                'celulas' => $celulas,
                'personas' => $personas,
                'celula_preseleccionada' => $celulaPreseleccionada,
                'fecha_preseleccionada' => $fechaPreseleccionada,
                'return_url' => $returnUrl,
                'conteo_asistencias_por_persona' => $conteoAsistenciasPorPersona
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
        $returnUrl = $this->resolverReturnUrlAsistencias($_GET['return_url'] ?? '');
        
        $this->view('asistencias/porCelula', [
            'asistencias' => $asistencias,
            'celula' => $celula,
            'return_url' => $returnUrl
        ]);
    }
}

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

    /**
     * Miembros activos de una célula sin duplicar por cédula (JSON para reporte).
     */
    public function miembrosCelula() {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthController::puede('asistencias:crear')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permiso']);
            return;
        }

        $idCelula = (int)($_GET['id_celula'] ?? 0);
        if ($idCelula <= 0) {
            echo json_encode(['ok' => true, 'miembros' => [], 'conteo' => [], 'duplicados_ocultos' => 0]);
            return;
        }

        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $celulas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);
        $celulaPermitida = false;
        foreach ($celulas as $celula) {
            if ((int)($celula['Id_Celula'] ?? 0) === $idCelula) {
                $celulaPermitida = true;
                break;
            }
        }

        if (!$celulaPermitida) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Célula no permitida']);
            return;
        }

        $resultado = $this->obtenerMiembrosUnicosActivosPorCelula($idCelula);
        $ids = array_map(static function ($p) {
            return (int)($p['Id_Persona'] ?? 0);
        }, $resultado['miembros']);
        $conteo = $this->asistenciaModel->getConteoAsistenciasPorPersonaYCelula($ids, $idCelula);

        echo json_encode([
            'ok' => true,
            'miembros' => $resultado['miembros'],
            'conteo' => $conteo,
            'duplicados_ocultos' => $resultado['duplicados_ocultos'],
        ]);
    }

    /**
     * @return array{miembros: array<int, array<string, mixed>>, duplicados_ocultos: int}
     */
    private function obtenerMiembrosUnicosActivosPorCelula(int $idCelula): array {
        $sql = "SELECT p.*
                FROM persona p
                WHERE p.Id_Celula = ?
                  AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                ORDER BY p.Apellido, p.Nombre, p.Id_Persona DESC";
        $filas = $this->personaModel->query($sql, [$idCelula]);

        $mapa = [];
        foreach ($filas as $fila) {
            $doc = preg_replace('/\D+/', '', (string)($fila['Numero_Documento'] ?? ''));
            $clave = strlen($doc) >= 5
                ? ('doc:' . $doc)
                : ('id:' . (int)($fila['Id_Persona'] ?? 0));

            if (!isset($mapa[$clave])) {
                $mapa[$clave] = $fila;
                continue;
            }

            $idNuevo = (int)($fila['Id_Persona'] ?? 0);
            $idViejo = (int)($mapa[$clave]['Id_Persona'] ?? 0);
            if ($idNuevo > $idViejo) {
                $mapa[$clave] = $fila;
            }
        }

        $miembros = array_values($mapa);

        return [
            'miembros' => $miembros,
            'duplicados_ocultos' => max(0, count($filas) - count($miembros)),
        ];
    }

    public function index() {
        if (!AuthController::puede('asistencias:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
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
            'filtro_reporte_actual' => (string)$filtroReporte,
            'puede_marcar_entrego_sobre' => DataIsolation::tieneAccesoTotal()
        ]);
    }

    public function actualizarEntregoSobre() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Metodo no permitido'], 405);
        }

        if (!DataIsolation::tieneAccesoTotal()) {
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
        if (!AuthController::puede('asistencias:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
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
        if (!AuthController::puede('asistencias:crear')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
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
            $celulas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);

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
                'celula_preseleccionada' => $celulaPreseleccionada,
                'fecha_preseleccionada' => $fechaPreseleccionada,
                'return_url' => $returnUrl,
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

    private function normalizarChecklistParaNoDisponible($checklist) {
        $normalizado = [
            'Ganar' => [false, false, false, false, false, false],
            'Consolidar' => [false, false, false],
            'Discipular' => [false, false, false],
            'Enviar' => [false, false, false],
            '_meta' => [
                'no_disponible_observacion' => '',
                'convenciones' => []
            ]
        ];

        if (!is_array($checklist)) {
            return $normalizado;
        }

        foreach (['Ganar', 'Consolidar', 'Discipular', 'Enviar'] as $etapa) {
            if (isset($checklist[$etapa]) && is_array($checklist[$etapa])) {
                foreach ($normalizado[$etapa] as $indice => $valor) {
                    $normalizado[$etapa][$indice] = !empty($checklist[$etapa][$indice]);
                }
            }
        }

        if (isset($checklist['_meta']) && is_array($checklist['_meta'])) {
            $normalizado['_meta']['no_disponible_observacion'] = trim((string)($checklist['_meta']['no_disponible_observacion'] ?? ''));
        }

        return $normalizado;
    }

    public function marcarNoDisponible() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Metodo no permitido'], 405);
        }

        if (!AuthController::puede('asistencias:crear') && !AuthController::puede('asistencias:editar')) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $idPersona = isset($_POST['id_persona']) ? (int)$_POST['id_persona'] : 0;
        $observacion = trim((string)($_POST['observacion'] ?? ''));

        if ($idPersona <= 0) {
            $this->json(['success' => false, 'message' => 'Persona invalida'], 422);
        }

        if ($observacion === '') {
            $this->json(['success' => false, 'message' => 'Debes escribir una observacion'], 422);
        }

        if (method_exists($this->personaModel, 'tieneColumna') && !$this->personaModel->tieneColumna('Escalera_Checklist')) {
            $this->json(['success' => false, 'message' => 'Escalera del Exito no disponible en esta base de datos'], 400);
        }

        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        if (!$this->personaModel->puedeEditarEscaleraPorRol($idPersona, $filtroPersonas)) {
            $this->json(['success' => false, 'message' => 'No tienes acceso a esta persona'], 403);
        }

        $persona = $this->personaModel->getById($idPersona);
        if (!$persona) {
            $this->json(['success' => false, 'message' => 'Persona no encontrada'], 404);
        }

        $checklistActual = [];
        $rawChecklist = trim((string)($persona['Escalera_Checklist'] ?? ''));
        if ($rawChecklist !== '') {
            $decoded = json_decode($rawChecklist, true);
            if (is_array($decoded)) {
                $checklistActual = $decoded;
            }
        }

        $checklistNormalizado = $this->normalizarChecklistParaNoDisponible($checklistActual);
        $checklistNormalizado['Ganar'][5] = true;
        $checklistNormalizado['_meta']['no_disponible_observacion'] = $observacion;

        $checklistJson = json_encode($checklistNormalizado, JSON_UNESCAPED_UNICODE);
        if ($checklistJson === false) {
            $this->json(['success' => false, 'message' => 'No se pudo procesar el checklist'], 500);
        }

        $procesoActual = isset($persona['Proceso']) ? (string)$persona['Proceso'] : null;
        $ok = $this->personaModel->updateEscaleraChecklistYProceso($idPersona, $checklistJson, $procesoActual);
        if (!$ok) {
            $this->json(['success' => false, 'message' => 'No se pudo guardar el estado No se dispone'], 500);
        }

        $okSalidaCelula = $this->personaModel->update($idPersona, [
            'Id_Celula' => null
        ]);
        if (!$okSalidaCelula) {
            $this->json(['success' => false, 'message' => 'No se pudo retirar la persona de la celula'], 500);
        }

        $this->personaModel->cambiarEstado($idPersona, 'Inactivo');

        $this->json([
            'success' => true,
            'message' => 'Persona marcada como No se dispone',
            'id_persona' => $idPersona
        ]);
    }
}

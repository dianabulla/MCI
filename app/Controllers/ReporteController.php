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

    private function calcularRangoSemanaDomingoADomingo($fechaReferencia) {
        $timestamp = strtotime((string)$fechaReferencia);
        if ($timestamp === false) {
            $timestamp = time();
        }

        $diaSemana = (int)date('w', $timestamp); // 0 domingo, 6 sabado
        $inicio = strtotime('-' . $diaSemana . ' days', $timestamp);
        $fin = strtotime('+6 days', $inicio);

        return [date('Y-m-d', $inicio), date('Y-m-d', $fin)];
    }

    private function construirOpcionesFiltroMinisterioLider($filtroCelulas) {
        $celulasBase = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);

        $ministeriosDisponibles = [];
        $ministerioIdsPermitidos = [];
        $lideresDisponibles = [];
        $liderIdsPermitidos = [];
        $celulasDisponibles = [];

        foreach ($celulasBase as $celulaBase) {
            $idCelula = (int)($celulaBase['Id_Celula'] ?? 0);
            if ($idCelula > 0) {
                $celulasDisponibles[$idCelula] = [
                    'Id_Celula' => $idCelula,
                    'Nombre_Celula' => (string)($celulaBase['Nombre_Celula'] ?? '')
                ];
            }

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
        ksort($celulasDisponibles);

        return [
            'ministerios_disponibles' => array_values($ministeriosDisponibles),
            'ministerio_ids_permitidos' => $ministerioIdsPermitidos,
            'lideres_disponibles' => array_values($lideresDisponibles),
            'lider_ids_permitidos' => $liderIdsPermitidos,
            'celulas_disponibles' => array_values($celulasDisponibles)
        ];
    }

    private function normalizarTexto($valor) {
        $valor = strtolower(trim((string)$valor));
        return strtr($valor, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);
    }

    private function obtenerContextoSemestre($fechaReferencia) {
        $fecha = DateTimeImmutable::createFromFormat('Y-m-d', (string)$fechaReferencia);
        if (!$fecha) {
            $fecha = new DateTimeImmutable('today');
        }

        $anio = (int)$fecha->format('Y');
        $mes = (int)$fecha->format('n');

        $mesInicio = $mes <= 6 ? 1 : 7;
        $mesFin = $mes <= 6 ? 6 : 12;
        $numeroSemestre = $mes <= 6 ? 1 : 2;
        $nombresMeses = [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE'
        ];

        $inicio = (new DateTimeImmutable(sprintf('%04d-%02d-01', $anio, $mesInicio)))->setTime(0, 0, 0);
        $fin = (new DateTimeImmutable(sprintf('%04d-%02d-01', $anio, $mesFin)))->modify('last day of this month')->setTime(23, 59, 59);

        $meses = [];
        $cursor = $inicio;
        while ($cursor <= $fin) {
            $numeroMes = (int)$cursor->format('n');
            $meses[] = [
                'key' => $cursor->format('Y-m'),
                'label' => $nombresMeses[$numeroMes] ?? mb_strtoupper((string)strftime('%B', (int)$cursor->format('U')))
            ];
            $cursor = $cursor->modify('first day of next month');
        }

        return [
            'inicio' => $inicio->format('Y-m-d'),
            'fin' => $fin->format('Y-m-d'),
            'titulo' => 'GANAR ' . $numeroSemestre . ' SEMESTRE ' . $anio,
            'numero_semestre' => $numeroSemestre,
            'anio' => $anio,
            'meses' => $meses
        ];
    }

    private function construirTablaCumplimientoMetas($fechaReferencia, $filtroRol, $filtroMinisterios, $filtroMinisterio = '', $filtroLider = '', $filtroCelula = '') {
        $semestre = $this->obtenerContextoSemestre($fechaReferencia);

        $ministeriosVisibles = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);
        if ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) {
            $ministeriosVisibles = array_values(array_filter($ministeriosVisibles, static function($ministerio) use ($filtroMinisterio) {
                return (int)($ministerio['Id_Ministerio'] ?? 0) === (int)$filtroMinisterio;
            }));
        }

        $ministerioIds = array_map(static function($ministerio) {
            return (int)($ministerio['Id_Ministerio'] ?? 0);
        }, $ministeriosVisibles);

        $metasDetalle = $this->ministerioModel->getMetasDetalleByMinisterioIds($ministerioIds);

        $idMinisterioFiltro = ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null;
        $idLiderFiltro = ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null;
        $idCelulaFiltro = ($filtroCelula !== '') ? $filtroCelula : null;

        $personasSemestre = $this->personaModel->getWithFiltersAndRole(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            null,
            'Activo',
            $idCelulaFiltro,
            null,
            null
        );

        $mesKeys = array_map(static function($mes) {
            return (string)($mes['key'] ?? '');
        }, $semestre['meses']);

        $rowsMap = [];
        foreach ($ministeriosVisibles as $ministerio) {
            $id = (int)($ministerio['Id_Ministerio'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $meses = [];
            foreach ($mesKeys as $mesKey) {
                $meses[$mesKey] = ['celula' => 0, 'iglesia' => 0];
            }

            $meta = 0;
            if (isset($metasDetalle[$id])) {
                $meta = (int)($semestre['numero_semestre'] === 1
                    ? ($metasDetalle[$id]['meta_ganados_s1'] ?? 0)
                    : ($metasDetalle[$id]['meta_ganados_s2'] ?? 0));
            }
            $rowsMap[$id] = [
                'ministerio' => (string)($ministerio['Nombre_Ministerio'] ?? 'Sin ministerio'),
                'meta' => $meta,
                'pendiente' => $meta,
                'ganados' => 0,
                'meses' => $meses
            ];
        }

        foreach ($personasSemestre as $persona) {
            $idMinisterioPersona = (int)($persona['Id_Ministerio'] ?? 0);
            if ($idMinisterioPersona <= 0 || !isset($rowsMap[$idMinisterioPersona])) {
                continue;
            }

            $fechaRegistro = substr((string)($persona['Fecha_Registro'] ?? ''), 0, 10);
            if ($fechaRegistro < $semestre['inicio'] || $fechaRegistro > $semestre['fin']) {
                continue;
            }

            $mesKey = substr($fechaRegistro, 0, 7);
            if (!isset($rowsMap[$idMinisterioPersona]['meses'][$mesKey])) {
                continue;
            }

            $tipoReunion = $this->normalizarTexto($persona['Tipo_Reunion'] ?? '');
            if (strpos($tipoReunion, 'celula') !== false) {
                $rowsMap[$idMinisterioPersona]['meses'][$mesKey]['celula']++;
                $rowsMap[$idMinisterioPersona]['ganados']++;
            } elseif (strpos($tipoReunion, 'domingo') !== false || strpos($tipoReunion, 'iglesia') !== false) {
                $rowsMap[$idMinisterioPersona]['meses'][$mesKey]['iglesia']++;
                $rowsMap[$idMinisterioPersona]['ganados']++;
            }
        }

        $rows = array_values($rowsMap);
        foreach ($rows as &$row) {
            $row['pendiente'] = max(0, (int)$row['meta'] - (int)$row['ganados']);
        }
        unset($row);

        $totales = [
            'meta' => 0,
            'pendiente' => 0,
            'ganados' => 0,
            'meses' => []
        ];
        foreach ($mesKeys as $mesKey) {
            $totales['meses'][$mesKey] = ['celula' => 0, 'iglesia' => 0];
        }

        foreach ($rows as $row) {
            $totales['meta'] += (int)$row['meta'];
            $totales['ganados'] += (int)$row['ganados'];
            $totales['pendiente'] += (int)$row['pendiente'];

            foreach ($mesKeys as $mesKey) {
                $totales['meses'][$mesKey]['celula'] += (int)($row['meses'][$mesKey]['celula'] ?? 0);
                $totales['meses'][$mesKey]['iglesia'] += (int)($row['meses'][$mesKey]['iglesia'] ?? 0);
            }
        }

        return [
            'titulo' => $semestre['titulo'],
            'inicio' => $semestre['inicio'],
            'fin' => $semestre['fin'],
            'meses' => $semestre['meses'],
            'rows' => $rows,
            'totales' => $totales
        ];
    }

    public function index() {
        // Verificar permiso de ver reportes
        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('reportes', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $fechaReferencia = $_GET['fecha_referencia'] ?? date('Y-m-d');
        [$fechaInicio, $fechaFin] = $this->calcularRangoSemanaDomingoADomingo($fechaReferencia);

        $filtroCelula = $_GET['celula'] ?? '';
        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';
        $filtroMesMeta = $_GET['mes_meta'] ?? '';

        // Generar filtros según el rol del usuario
        $filtroRol = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();

        $opcionesFiltro = $this->construirOpcionesFiltroMinisterioLider($filtroCelulas);
        $celulasDisponibles = $opcionesFiltro['celulas_disponibles'];
        $celulaIdsPermitidas = array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulasDisponibles);

        $filtroCelula = ($filtroCelula !== '' && in_array((int)$filtroCelula, $celulaIdsPermitidas, true)) ? (int)$filtroCelula : (($filtroCelula === '0') ? '0' : '');
        $filtroMinisterio = ($filtroMinisterio !== '' && isset($opcionesFiltro['ministerio_ids_permitidos'][(int)$filtroMinisterio])) ? (int)$filtroMinisterio : '';
        $filtroLider = ($filtroLider !== '' && isset($opcionesFiltro['lider_ids_permitidos'][(int)$filtroLider])) ? (int)$filtroLider : '';

        // Datos para gráfico de almas ganadas
        $almasGanadas = $this->personaModel->getAlmasGanadasPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol, $filtroMinisterio, $filtroLider);

        $resumenOrigenGanados = $this->personaModel->getResumenGanadosOrigenWithRole($fechaInicio, $fechaFin, $filtroRol, $filtroMinisterio, $filtroLider);

        $almasPorEdades = $this->personaModel->getAlmasGanadasPorEdadesWithRole($fechaInicio, $fechaFin, $filtroRol, $filtroMinisterio, $filtroLider);

        // Resumen de etapas del proceso de ganar
        $procesoGanar = $this->personaModel->getResumenProcesoGanarWithRole($fechaInicio, $fechaFin, $filtroRol, $filtroCelula, $filtroMinisterio, $filtroLider);
        
        // Datos para gráfico de asistencia
        $asistenciaCelulas = $this->asistenciaModel->getAsistenciaPorCelulaWithRole($fechaInicio, $fechaFin, $filtroCelulas, $filtroMinisterio, $filtroLider);

        $cumplimientoMetas = $this->construirTablaCumplimientoMetas(
            $fechaReferencia,
            $filtroRol,
            $filtroMinisterios,
            $filtroMinisterio,
            $filtroLider,
            $filtroCelula
        );

        $mesesMetaDisponibles = array_map(static function($mes) {
            return (string)($mes['key'] ?? '');
        }, $cumplimientoMetas['meses'] ?? []);
        $mesReferencia = substr((string)$fechaReferencia, 0, 7);
        if ((string)$filtroMesMeta === 'all') {
            $filtroMesMeta = 'all';
        } elseif (in_array((string)$filtroMesMeta, $mesesMetaDisponibles, true)) {
            $filtroMesMeta = (string)$filtroMesMeta;
        } elseif (in_array($mesReferencia, $mesesMetaDisponibles, true)) {
            // Vista por defecto compacta: mes de la fecha de referencia.
            $filtroMesMeta = $mesReferencia;
        } else {
            $filtroMesMeta = '';
        }

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
            'fecha_referencia' => $fechaReferencia,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'filtro_celula' => (string)$filtroCelula,
            'filtro_ministerio' => (string)$filtroMinisterio,
            'filtro_lider' => (string)$filtroLider,
            'filtro_mes_meta' => $filtroMesMeta,
            'celulas_disponibles' => $celulasDisponibles,
            'ministerios_disponibles' => $opcionesFiltro['ministerios_disponibles'],
            'lideres_disponibles' => $opcionesFiltro['lideres_disponibles'],
            'almas_ganadas' => $almasGanadas,
            'resumen_origen_ganados' => $resumenOrigenGanados,
            'almas_por_edades' => $almasPorEdades,
            'proceso_ganar' => $procesoGanar,
            'asistencia_celulas' => $asistenciaCelulas,
            'cumplimiento_metas' => $cumplimientoMetas
        ];

        $this->view('reportes/index', $data);
    }

    public function exportarExcel() {
        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('reportes', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $fechaReferencia = $_GET['fecha_referencia'] ?? date('Y-m-d');
        [$fechaInicio, $fechaFin] = $this->calcularRangoSemanaDomingoADomingo($fechaReferencia);
        $filtroCelula = $_GET['celula'] ?? '';
        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';

        $filtroRol = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();

        $opcionesFiltro = $this->construirOpcionesFiltroMinisterioLider($filtroCelulas);
        $celulaIdsPermitidas = array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $opcionesFiltro['celulas_disponibles']);

        $filtroMinisterio = ($filtroMinisterio !== '' && isset($opcionesFiltro['ministerio_ids_permitidos'][(int)$filtroMinisterio])) ? (int)$filtroMinisterio : '';
        $filtroLider = ($filtroLider !== '' && isset($opcionesFiltro['lider_ids_permitidos'][(int)$filtroLider])) ? (int)$filtroLider : '';
        $filtroCelula = ($filtroCelula !== '' && in_array((int)$filtroCelula, $celulaIdsPermitidas, true)) ? (int)$filtroCelula : (($filtroCelula === '0') ? '0' : '');

        $almasGanadas = $this->personaModel->getAlmasGanadasPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol, $filtroMinisterio, $filtroLider);
        $procesoGanar = $this->personaModel->getResumenProcesoGanarWithRole($fechaInicio, $fechaFin, $filtroRol, $filtroCelula, $filtroMinisterio, $filtroLider);
        $resumenOrigenGanados = $this->personaModel->getResumenGanadosOrigenWithRole($fechaInicio, $fechaFin, $filtroRol, $filtroMinisterio, $filtroLider);
        $almasPorEdades = $this->personaModel->getAlmasGanadasPorEdadesWithRole($fechaInicio, $fechaFin, $filtroRol, $filtroMinisterio, $filtroLider);
        $asistenciaCelulas = $this->asistenciaModel->getAsistenciaPorCelulaWithRole($fechaInicio, $fechaFin, $filtroCelulas, $filtroMinisterio, $filtroLider);

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
        $rows[] = ['Procesos de Ganar', '', '', '', '', '', ''];
        $rows[] = ['Etapa', 'Cantidad', '', '', '', '', ''];
        $rows[] = ['Ganar', (string)($procesoGanar['Ganar'] ?? 0), '', '', '', '', ''];
        $rows[] = ['Consolidar', (string)($procesoGanar['Consolidar'] ?? 0), '', '', '', '', ''];
        $rows[] = ['Discipular', (string)($procesoGanar['Discipular'] ?? 0), '', '', '', '', ''];
        $rows[] = ['Enviar', (string)($procesoGanar['Enviar'] ?? 0), '', '', '', '', ''];
        $rows[] = ['Sin proceso', (string)($procesoGanar['Sin_Proceso'] ?? 0), '', '', '', '', ''];
        $rows[] = ['Total', (string)($procesoGanar['Total'] ?? 0), '', '', '', '', ''];

        $rows[] = ['', '', '', '', '', '', ''];
        $rows[] = ['Ganados por Origen', '', '', '', '', '', ''];
        $rows[] = ['Ganados en Celula', (string)($resumenOrigenGanados['Ganados_Celula'] ?? 0), '', '', '', '', ''];
        $rows[] = ['Ganados en Domingo', (string)($resumenOrigenGanados['Ganados_Domingo'] ?? 0), '', '', '', '', ''];
        $rows[] = ['Total', (string)($resumenOrigenGanados['Total'] ?? 0), '', '', '', '', ''];

        $rows[] = ['', '', '', '', '', '', ''];
        $rows[] = ['Almas Ganadas por Edad', '', '', '', '', '', ''];
        $rows[] = ['Kids (3-8)', (string)($almasPorEdades['Kids'] ?? 0), '', '', '', '', ''];
        $rows[] = ['Teens (9-12)', (string)($almasPorEdades['Teens'] ?? 0), '', '', '', '', ''];
        $rows[] = ['Rocas (13-17)', (string)($almasPorEdades['Rocas'] ?? 0), '', '', '', '', ''];
        $rows[] = ['Jovenes (18-30)', (string)($almasPorEdades['Jovenes'] ?? 0), '', '', '', '', ''];
        $rows[] = ['Adultos (31-59)', (string)($almasPorEdades['Adultos'] ?? 0), '', '', '', '', ''];
        $rows[] = ['Adultos Mayores (60+)', (string)($almasPorEdades['Adultos_Mayores'] ?? 0), '', '', '', '', ''];
        $rows[] = ['Sin Dato', (string)($almasPorEdades['Sin_Dato'] ?? 0), '', '', '', '', ''];

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

<?php
/**
 * Controlador de Reportes y Estadísticas
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Asistencia.php';
require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/EscuelaFormacionInscripcion.php';
require_once APP . '/Models/EscuelaFormacionEstado.php';
require_once APP . '/Helpers/DataIsolation.php';

class ReporteController extends BaseController {
    private $personaModel;
    private $asistenciaModel;
    private $celulaModel;
    private $ministerioModel;
    private $escuelaInscripcionModel;
    private $escuelaEstadoModel;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->asistenciaModel = new Asistencia();
        $this->celulaModel = new Celula();
        $this->ministerioModel = new Ministerio();
        $this->escuelaInscripcionModel = new EscuelaFormacionInscripcion();
        $this->escuelaEstadoModel = new EscuelaFormacionEstado();
    }

    private function calcularRangoSemanaDomingoADomingo($fechaReferencia) {
        $timestamp = strtotime((string)$fechaReferencia);
        if ($timestamp === false) {
            $timestamp = time();
        }

        $diaSemana = (int)date('N', $timestamp); // 1 lunes, 7 domingo
        $diasDesdeLunes = $diaSemana - 1;
        $inicio = strtotime('-' . $diasDesdeLunes . ' days', $timestamp);
        $fin = strtotime('+6 days', $inicio);

        return [date('Y-m-d', $inicio), date('Y-m-d', $fin)];
    }

    private function normalizarFechaYmd($valor) {
        $valor = trim((string)$valor);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return '';
        }

        $fecha = DateTimeImmutable::createFromFormat('Y-m-d', $valor);
        return $fecha ? $fecha->format('Y-m-d') : '';
    }

    private function normalizarMesYm($valor) {
        $valor = trim((string)$valor);
        if (!preg_match('/^\d{4}-\d{2}$/', $valor)) {
            return '';
        }

        $fecha = DateTimeImmutable::createFromFormat('Y-m-d', $valor . '-01');
        return $fecha ? $fecha->format('Y-m') : '';
    }

    private function formatearMesAnioEspanol($valorYm) {
        $fecha = DateTimeImmutable::createFromFormat('Y-m-d', (string)$valorYm . '-01');
        if (!$fecha) {
            $fecha = new DateTimeImmutable('first day of this month');
        }

        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        $numeroMes = (int)$fecha->format('n');
        return ucfirst($meses[$numeroMes] ?? $fecha->format('F')) . ' ' . $fecha->format('Y');
    }

    private function construirRangoMesCalendario($mesSeleccionado = '') {
        $mesNormalizado = $this->normalizarMesYm($mesSeleccionado);
        if ($mesNormalizado === '') {
            $mesNormalizado = date('Y-m');
        }

        $fechaBase = DateTimeImmutable::createFromFormat('Y-m-d', $mesNormalizado . '-01');
        if (!$fechaBase) {
            $fechaBase = new DateTimeImmutable('first day of this month');
        }

        return [
            'mes' => $fechaBase->format('Y-m'),
            'inicio' => $fechaBase->modify('first day of this month')->format('Y-m-d'),
            'fin' => $fechaBase->modify('last day of this month')->format('Y-m-d'),
            'label' => $this->formatearMesAnioEspanol($fechaBase->format('Y-m'))
        ];
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

    private function resolverTipoReporte($tipoSolicitado) {
        $tipo = strtolower(trim((string)$tipoSolicitado));
        return in_array($tipo, ['personas', 'celulas', 'escuelas'], true) ? $tipo : 'personas';
    }

    private function esOrigenValidoEscuela($tipoReunion): bool {
        $tipo = strtolower(trim((string)$tipoReunion));
        if ($tipo === '') {
            return false;
        }

        if (strpos($tipo, 'migrados') !== false) {
            return false;
        }

        return strpos($tipo, 'celula') !== false
            || strpos($tipo, 'célula') !== false
            || strpos($tipo, 'domingo') !== false
            || strpos($tipo, 'somos uno') !== false
            || strpos($tipo, 'somosuno') !== false
            || strpos($tipo, 'otro') !== false;
    }

    private function construirReporteUniversidadVidaEscuelas(array $personas): array {
        $rows = [];
        $vistos = [];

        foreach ($personas as $persona) {
            if (!$this->esPersonaNueva($persona)) {
                continue;
            }

            if (!$this->esOrigenValidoEscuela($persona['Tipo_Reunion'] ?? '')) {
                continue;
            }

            $idPersona = (int)($persona['Id_Persona'] ?? 0);
            if ($idPersona <= 0 || isset($vistos[$idPersona])) {
                continue;
            }
            $vistos[$idPersona] = true;

            $nombre = trim(trim((string)($persona['Nombre'] ?? '')) . ' ' . trim((string)($persona['Apellido'] ?? '')));
            $rows[] = [
                'id_persona' => $idPersona,
                'nombre' => $nombre !== '' ? $nombre : 'Sin nombre',
                'ministerio' => trim((string)($persona['Nombre_Ministerio'] ?? '')) ?: 'Sin ministerio',
                'lider' => trim((string)($persona['Nombre_Lider'] ?? '')) ?: 'Sin líder',
                'celula' => trim((string)($persona['Nombre_Celula'] ?? '')) ?: 'Sin célula',
                'fecha_registro' => substr(trim((string)($persona['Fecha_Registro'] ?? '')), 0, 10),
            ];
        }

        usort($rows, static function($a, $b) {
            return strcmp((string)$a['nombre'], (string)$b['nombre']);
        });

        return [
            'total' => count($rows),
            'rows' => $rows,
        ];
    }

    private function resolverEscalaGanar($escalaSolicitada) {
        $escala = strtolower(trim((string)$escalaSolicitada));
        return in_array($escala, ['semanal', 'mensual', 'semestral', 'anual'], true) ? $escala : 'semanal';
    }

    private function construirRangoGanar($fechaReferencia, $escalaGanar) {
        $fecha = DateTimeImmutable::createFromFormat('Y-m-d', (string)$fechaReferencia);
        if (!$fecha) {
            $fecha = new DateTimeImmutable('today');
        }

        if ($escalaGanar === 'mensual') {
            return [
                'inicio' => $fecha->modify('first day of this month')->format('Y-m-d'),
                'fin' => $fecha->modify('last day of this month')->format('Y-m-d'),
                'label' => 'Mensual'
            ];
        }

        if ($escalaGanar === 'semestral') {
            $semestre = $this->obtenerContextoSemestre($fecha->format('Y-m-d'));
            return [
                'inicio' => (string)($semestre['inicio'] ?? $fecha->format('Y-m-d')),
                'fin' => (string)($semestre['fin'] ?? $fecha->format('Y-m-d')),
                'label' => 'Semestral'
            ];
        }

        if ($escalaGanar === 'anual') {
            return [
                'inicio' => $fecha->setDate((int)$fecha->format('Y'), 1, 1)->format('Y-m-d'),
                'fin' => $fecha->setDate((int)$fecha->format('Y'), 12, 31)->format('Y-m-d'),
                'label' => 'Anual'
            ];
        }

        [$inicio, $fin] = $this->calcularRangoSemanaDomingoADomingo($fecha->format('Y-m-d'));
        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'label' => 'Semanal'
        ];
    }

    private function construirReporteGanadosFinSemanaAnterior($fechaInicioSemanaActual, $fechaFinSemanaActual, $filtroRol, $filtroMinisterio = '', $filtroLider = '') {
        $inicioAnterior = date('Y-m-d', strtotime((string)$fechaInicioSemanaActual . ' -7 days'));
        $finAnterior = date('Y-m-d', strtotime((string)$fechaFinSemanaActual . ' -7 days'));

        $resumen = $this->personaModel->getResumenGanadosFinSemanaAnteriorPorMinisterioWithRole(
            $inicioAnterior,
            $finAnterior,
            $filtroRol,
            $filtroMinisterio,
            $filtroLider
        );

        $rows = $resumen['rows'] ?? [];
        $totales = $resumen['totales'] ?? [
            'ganados' => 0,
            'asignados' => 0,
            'por_verificar' => 0,
            'total_iglesia' => 0,
            'total_domingo' => 0
        ];

        $lineasTexto = [];
        $lineasTexto[] = 'Reporte de Ganados del fin de semana anterior (' . date('d/m/Y', strtotime($inicioAnterior)) . ' al ' . date('d/m/Y', strtotime($finAnterior)) . ')';
        $lineasTexto[] = '';

        foreach ($rows as $row) {
            $ministerio = (string)($row['ministerio'] ?? 'Sin ministerio');
            $ganados = (int)($row['ganados'] ?? 0);
            $asignados = (int)($row['asignados'] ?? 0);
            $porVerificar = (int)($row['por_verificar'] ?? 0);

            $linea = '. ' . $ministerio . ', ' . $ganados;
            if ($asignados > 0) {
                $linea .= ' (' . $asignados . ' Asignados)';
            }
            if ($porVerificar > 0) {
                $linea .= ' (' . $porVerificar . ' Por verificar)';
            }
            $lineasTexto[] = $linea;
        }

        $lineasTexto[] = '. Por verificar líder, ' . (int)$totales['por_verificar'];
        $lineasTexto[] = '';
        $lineasTexto[] = 'Recuerden dos cosas';
        $lineasTexto[] = '1 No olviden enviarme su líder encargado de la consolidación de su ministerio';
        $lineasTexto[] = '2 Todos deben actualizar el Drive de consolidación Diciembre 2025 y Enero y Febrero de 2026';

        return [
            'inicio' => $inicioAnterior,
            'fin' => $finAnterior,
            'rows' => $rows,
            'totales' => $totales,
            'texto' => implode("\n", $lineasTexto)
        ];
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

    private function fechaDentroDeRango($fecha, $inicio, $fin) {
        $fecha = substr(trim((string)$fecha), 0, 10);
        if ($fecha === '') {
            return false;
        }
        return $fecha >= $inicio && $fecha <= $fin;
    }

    private function construirIndicadoresCelulas($fechaReferencia, $fechaInicioSemana, $fechaFinSemana, $filtroCelulas, $filtroMinisterio = '', $filtroLider = '', $filtroCelula = '') {
        $semestre = $this->obtenerContextoSemestre($fechaReferencia);
        $celulas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas, $filtroMinisterio, $filtroLider);

        if ($filtroCelula !== '') {
            if ((string)$filtroCelula === '0') {
                $celulas = [];
            } else {
                $idCelulaFiltro = (int)$filtroCelula;
                $celulas = array_values(array_filter($celulas, static function($item) use ($idCelulaFiltro) {
                    return (int)($item['Id_Celula'] ?? 0) === $idCelulaFiltro;
                }));
            }
        }

        $asistencia = $this->asistenciaModel->getAsistenciaPorCelulaWithRole($fechaInicioSemana, $fechaFinSemana, $filtroCelulas, $filtroMinisterio, $filtroLider);
        if ($filtroCelula !== '') {
            if ((string)$filtroCelula === '0') {
                $asistencia = [];
            } else {
                $idCelulaFiltro = (int)$filtroCelula;
                $asistencia = array_values(array_filter($asistencia, static function($item) use ($idCelulaFiltro) {
                    return (int)($item['Id_Celula'] ?? 0) === $idCelulaFiltro;
                }));
            }
        }

        $totalCelulas = count($celulas);
        $nuevasSemestre = 0;
        $cerradasSemestre = 0;
        $porMinisterio = [];
        $porRed = [];

        foreach ($celulas as $celula) {
            $ministerio = trim((string)($celula['Nombre_Ministerio_Lider'] ?? ''));
            if ($ministerio === '') {
                $ministerio = 'Sin ministerio';
            }
            if (!isset($porMinisterio[$ministerio])) {
                $porMinisterio[$ministerio] = 0;
            }
            $porMinisterio[$ministerio]++;

            $red = trim((string)($celula['Red'] ?? ''));
            if ($red === '') {
                $red = 'Sin red';
            }
            if (!isset($porRed[$red])) {
                $porRed[$red] = 0;
            }
            $porRed[$red]++;

            $fechaApertura = $celula['Fecha_Apertura'] ?? '';
            if ($this->fechaDentroDeRango($fechaApertura, $semestre['inicio'], $semestre['fin'])) {
                $nuevasSemestre++;
            }

            $estadoCelula = strtolower(trim((string)($celula['Estado_Celula'] ?? '')));
            $fechaCierre = $celula['Fecha_Cierre'] ?? '';
            if ($estadoCelula === 'cerrada') {
                if ($this->fechaDentroDeRango($fechaCierre, $semestre['inicio'], $semestre['fin']) || trim((string)$fechaCierre) === '') {
                    $cerradasSemestre++;
                }
            }
        }

        arsort($porMinisterio);
        arsort($porRed);

        $reportadasSemana = 0;
        $reportadasMap = [];
        foreach ($asistencia as $fila) {
            $idCelula = (int)($fila['Id_Celula'] ?? 0);
            $reporto = (int)($fila['Reuniones_Realizadas'] ?? 0) > 0;
            if ($idCelula > 0) {
                $reportadasMap[$idCelula] = $reporto;
            }
            if ($reporto) {
                $reportadasSemana++;
            }
        }

        $noReportadasSemana = max(0, $totalCelulas - $reportadasSemana);

        $celulaIds = array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulas);
        $estadoEntregoSobre = $this->asistenciaModel->getEstadoEntregoSobrePorCelulaSemana($celulaIds, $fechaInicioSemana);

        $entregaronSobreSinReportar = 0;
        $reportaronSinEntregarSobre = 0;
        foreach ($celulas as $celula) {
            $idCelula = (int)($celula['Id_Celula'] ?? 0);
            if ($idCelula <= 0) {
                continue;
            }

            $reporto = !empty($reportadasMap[$idCelula]);
            $entregoSobre = !empty($estadoEntregoSobre[$idCelula]);

            if ($entregoSobre && !$reporto) {
                $entregaronSobreSinReportar++;
            }

            if ($reporto && !$entregoSobre) {
                $reportaronSinEntregarSobre++;
            }
        }

        return [
            'semestre' => $semestre,
            'totales' => [
                'total_celulas' => $totalCelulas,
                'nuevas_semestre' => $nuevasSemestre,
                'cerradas_semestre' => $cerradasSemestre,
                'reportadas_semana' => $reportadasSemana,
                'no_reportadas_semana' => $noReportadasSemana,
                'entregaron_sobre_sin_reportar' => $entregaronSobreSinReportar,
                'reportaron_sin_entregar_sobre' => $reportaronSinEntregarSobre
            ],
            'por_ministerio' => $porMinisterio,
            'por_red' => $porRed
        ];
    }

    private function construirTablaAperturasCelulasPorMinisterio($fechaReferencia, $filtroCelulas, $filtroMinisterio = '', $filtroLider = '', $filtroCelula = '') {
        $anio = (int)date('Y', strtotime((string)$fechaReferencia ?: date('Y-m-d')));
        if ($anio <= 0) {
            $anio = (int)date('Y');
        }

        $celulas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas, $filtroMinisterio, $filtroLider);
        if ($filtroCelula !== '') {
            if ((string)$filtroCelula === '0') {
                $celulas = [];
            } else {
                $idCelulaFiltro = (int)$filtroCelula;
                $celulas = array_values(array_filter($celulas, static function($item) use ($idCelulaFiltro) {
                    return (int)($item['Id_Celula'] ?? 0) === $idCelulaFiltro;
                }));
            }
        }

        $meses = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        $rowsMap = [];
        $detalleLideres = [];

        foreach ($celulas as $celula) {
            $fechaAperturaRaw = trim((string)($celula['Fecha_Apertura'] ?? ''));
            if ($fechaAperturaRaw === '') {
                continue;
            }

            $ts = strtotime($fechaAperturaRaw);
            if ($ts === false || (int)date('Y', $ts) !== $anio) {
                continue;
            }

            $mes = (int)date('n', $ts);
            if ($mes < 1 || $mes > 12) {
                continue;
            }

            $ministerio = trim((string)($celula['Nombre_Ministerio_Lider'] ?? ''));
            if ($ministerio === '') {
                $ministerio = 'Sin ministerio';
            }

            if (!isset($rowsMap[$ministerio])) {
                $rowsMap[$ministerio] = [
                    'ministerio' => $ministerio,
                    'meses' => array_fill(1, 12, 0),
                    's1' => 0,
                    's2' => 0,
                    'anual' => 0
                ];
            }

            $rowsMap[$ministerio]['meses'][$mes]++;
            $rowsMap[$ministerio]['anual']++;
            if ($mes <= 6) {
                $rowsMap[$ministerio]['s1']++;
            } else {
                $rowsMap[$ministerio]['s2']++;
            }

            $lider = trim((string)($celula['Nombre_Lider'] ?? ''));
            if ($lider === '') {
                $lider = 'Sin líder';
            }

            if (!isset($detalleLideres[$ministerio])) {
                $detalleLideres[$ministerio] = [];
            }
            if (!isset($detalleLideres[$ministerio][$lider])) {
                $detalleLideres[$ministerio][$lider] = 0;
            }
            $detalleLideres[$ministerio][$lider]++;
        }

        ksort($rowsMap);

        $rows = array_values($rowsMap);
        $totales = [
            'meses' => array_fill(1, 12, 0),
            's1' => 0,
            's2' => 0,
            'anual' => 0
        ];

        foreach ($rows as $row) {
            for ($m = 1; $m <= 12; $m++) {
                $totales['meses'][$m] += (int)($row['meses'][$m] ?? 0);
            }
            $totales['s1'] += (int)($row['s1'] ?? 0);
            $totales['s2'] += (int)($row['s2'] ?? 0);
            $totales['anual'] += (int)($row['anual'] ?? 0);
        }

        foreach ($detalleLideres as $ministerio => $lideres) {
            arsort($lideres);
            $detalleLideres[$ministerio] = array_map(static function($nombre, $cantidad) {
                return ['lider' => $nombre, 'cantidad' => $cantidad];
            }, array_keys($lideres), array_values($lideres));
        }

        return [
            'anio' => $anio,
            'meses' => $meses,
            'rows' => $rows,
            'totales' => $totales,
            'detalle_lideres' => $detalleLideres
        ];
    }

    private function construirTablaGanarPorMinisterio($fechaReferencia, $filtroRol, $fechaInicio, $fechaFin, $filtroMinisterio = '', $filtroLider = '', $filtroCelula = '') {
        $anio = (int)date('Y', strtotime((string)$fechaReferencia ?: date('Y-m-d')));
        if ($anio <= 0) {
            $anio = (int)date('Y');
        }

        $idMinisterioFiltro = ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null;
        $idLiderFiltro = ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null;
        $idCelulaFiltro = ($filtroCelula !== '') ? $filtroCelula : null;

        $personas = $this->personaModel->getWithFiltersAndRole(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            null,
            null,
            $idCelulaFiltro,
            null,
            null
        );

        $meses = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        $rowsMap = [];
        $detalleLideres = [];

        foreach ($personas as $persona) {
            if (!$this->esPersonaNueva($persona)) {
                continue;
            }

            $fechaRegistroRaw = trim((string)($persona['Fecha_Registro'] ?? ''));
            if ($fechaRegistroRaw === '') {
                continue;
            }

            $fechaRegistro = substr($fechaRegistroRaw, 0, 10);
            if ($fechaRegistro === '' || $fechaRegistro < $fechaInicio || $fechaRegistro > $fechaFin) {
                continue;
            }

            $ts = strtotime($fechaRegistroRaw);
            if ($ts === false) {
                continue;
            }

            $mes = (int)date('n', $ts);
            if ($mes < 1 || $mes > 12) {
                continue;
            }

            $ministerio = trim((string)($persona['Nombre_Ministerio'] ?? ''));
            if ($ministerio === '') {
                $ministerio = 'Sin ministerio';
            }

            if (!isset($rowsMap[$ministerio])) {
                $rowsMap[$ministerio] = [
                    'ministerio' => $ministerio,
                    'meses' => array_fill(1, 12, 0),
                    's1' => 0,
                    's2' => 0,
                    'anual' => 0
                ];
            }

            $rowsMap[$ministerio]['meses'][$mes]++;
            $rowsMap[$ministerio]['anual']++;
            if ($mes <= 6) {
                $rowsMap[$ministerio]['s1']++;
            } else {
                $rowsMap[$ministerio]['s2']++;
            }

            $lider = trim((string)($persona['Nombre_Lider'] ?? ''));
            if ($lider === '') {
                $lider = 'Sin líder';
            }

            if (!isset($detalleLideres[$ministerio])) {
                $detalleLideres[$ministerio] = [];
            }
            if (!isset($detalleLideres[$ministerio][$lider])) {
                $detalleLideres[$ministerio][$lider] = 0;
            }
            $detalleLideres[$ministerio][$lider]++;
        }

        ksort($rowsMap);

        $rows = array_values($rowsMap);
        $totales = [
            'meses' => array_fill(1, 12, 0),
            's1' => 0,
            's2' => 0,
            'anual' => 0
        ];

        foreach ($rows as $row) {
            for ($m = 1; $m <= 12; $m++) {
                $totales['meses'][$m] += (int)($row['meses'][$m] ?? 0);
            }
            $totales['s1'] += (int)($row['s1'] ?? 0);
            $totales['s2'] += (int)($row['s2'] ?? 0);
            $totales['anual'] += (int)($row['anual'] ?? 0);
        }

        foreach ($detalleLideres as $ministerio => $lideres) {
            arsort($lideres);
            $detalleLideres[$ministerio] = array_map(static function($nombre, $cantidad) {
                return ['lider' => $nombre, 'cantidad' => $cantidad];
            }, array_keys($lideres), array_values($lideres));
        }

        return [
            'anio' => $anio,
            'meses' => $meses,
            'rows' => $rows,
            'totales' => $totales,
            'detalle_lideres' => $detalleLideres,
            'inicio' => $fechaInicio,
            'fin' => $fechaFin
        ];
    }

    private function construirTablaCumplimientoMetas($fechaReferencia, $filtroRol, $filtroMinisterios, $filtroMinisterio = '', $filtroLider = '', $filtroCelula = '') {
        $semestre = $this->obtenerContextoSemestre($fechaReferencia);

        $idMinisterioFiltro = ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null;
        $idLiderFiltro = ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null;
        $idCelulaFiltro = ($filtroCelula !== '') ? $filtroCelula : null;

        $personasSemestre = $this->personaModel->getWithFiltersAndRole(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            null,
            null,
            $idCelulaFiltro,
            null,
            null
        );

        $ministeriosVisibles = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);
        if ($idMinisterioFiltro !== null) {
            $ministeriosVisibles = array_values(array_filter($ministeriosVisibles, static function($ministerio) use ($idMinisterioFiltro) {
                return (int)($ministerio['Id_Ministerio'] ?? 0) === $idMinisterioFiltro;
            }));
        }

        if ($idMinisterioFiltro === null && ($idLiderFiltro !== null || ($idCelulaFiltro !== null && (string)$idCelulaFiltro !== ''))) {
            $ministerioIdsConDatos = [];
            foreach ($personasSemestre as $persona) {
                $idMinisterioPersona = (int)($persona['Id_Ministerio'] ?? 0);
                if ($idMinisterioPersona > 0) {
                    $ministerioIdsConDatos[$idMinisterioPersona] = true;
                }
            }

            if (!empty($ministerioIdsConDatos)) {
                $ministeriosVisibles = array_values(array_filter($ministeriosVisibles, static function($ministerio) use ($ministerioIdsConDatos) {
                    $id = (int)($ministerio['Id_Ministerio'] ?? 0);
                    return isset($ministerioIdsConDatos[$id]);
                }));
            } else {
                $ministeriosVisibles = [];
            }
        }

        $ministerioIds = array_map(static function($ministerio) {
            return (int)($ministerio['Id_Ministerio'] ?? 0);
        }, $ministeriosVisibles);

        $metasDetalle = $this->ministerioModel->getMetasDetalleByMinisterioIds($ministerioIds);

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
            if (!$this->esPersonaNueva($persona)) {
                continue;
            }

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
            } elseif (strpos($tipoReunion, 'domingo') !== false || strpos($tipoReunion, 'iglesia') !== false || strpos($tipoReunion, 'somos uno') !== false || strpos($tipoReunion, 'somosuno') !== false || strpos($tipoReunion, 'viernes') !== false || strpos($tipoReunion, 'otro') !== false) {
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

    private function obtenerMesesAbreviados() {
        return [
            1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR',
            5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO',
            9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC'
        ];
    }

    private function normalizarProcesoValor($valor) {
        $proceso = trim((string)$valor);
        return in_array($proceso, ['Ganar', 'Consolidar', 'Discipular', 'Enviar'], true) ? $proceso : '';
    }

    private function esPersonaNueva(array $persona): bool {
        return (int)($persona['Es_Antiguo'] ?? 1) === 0;
    }

    /**
     * Clasifica el origen de la persona: 'iglesia' | 'celula' | 'otros'
     * - 'iglesia'  → Cualquier origen distinto de Célula
     * - 'celula'   → Tipo_Reunion = Célula
     * - 'otros'    → reservado para compatibilidad (actualmente no se usa)
     */
    private function clasificarOrigenGanar(array $persona): string {
        $tipo = strtolower(trim((string)($persona['Tipo_Reunion'] ?? '')));
        if (strpos($tipo, 'celula') !== false || strpos($tipo, 'célula') !== false) {
            return 'celula';
        }

        return 'iglesia';
    }

    /**
     * Identifica si la persona debe considerarse nueva para U.V según "Ganado en".
     * Incluye: Célula, Domingo, Somos Uno, Otro.
     * Excluye explícitamente Migrados.
     */
    private function esOrigenValidoUniversidadVida(array $persona): bool {
        if (!$this->esPersonaNueva($persona)) {
            return false;
        }

        $tipo = strtolower(trim((string)($persona['Tipo_Reunion'] ?? '')));
        if ($tipo === '' || strpos($tipo, 'migrados') !== false) {
            return false;
        }

        return strpos($tipo, 'celula') !== false
            || strpos($tipo, 'célula') !== false
            || strpos($tipo, 'domingo') !== false
            || strpos($tipo, 'somos uno') !== false
            || strpos($tipo, 'somosuno') !== false
            || strpos($tipo, 'otro') !== false;
    }

    /**
     * Devuelve el checklist decodificado de una persona, o array vacío.
     */
    private function obtenerChecklist(array $persona): array {
        $raw = trim((string)($persona['Escalera_Checklist'] ?? ''));
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Indica si el peldaño $indice de la etapa $etapa está marcado,
     * considerando que etapas anteriores a la actual se dan por completadas.
     */
    private function peldanoMarcado(array $checklist, string $etapa, int $indice, string $procesoActual): bool {
        $ordenEtapas = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
        $idxActual  = array_search($procesoActual, $ordenEtapas, true);
        $idxEtapa   = array_search($etapa, $ordenEtapas, true);

        // Etapas anteriores → se consideran completas
        if ($idxActual !== false && $idxEtapa !== false && $idxEtapa < $idxActual) {
            return true;
        }

        $checksEtapa = $checklist[$etapa] ?? [];
        if (array_key_exists($indice, $checksEtapa)) {
            return !empty($checksEtapa[$indice]);
        }

        // Primer peldaño de la etapa activa = marcado por defecto
        if ($etapa === $procesoActual && $indice === 0) {
            return true;
        }

        return false;
    }

    private function construirDetallePersonaReporteMinisterial(array $persona): array {
        $nombre = trim(trim((string)($persona['Nombre'] ?? '')) . ' ' . trim((string)($persona['Apellido'] ?? '')));
        if ($nombre === '') {
            $nombre = 'Sin nombre';
        }
        $fechaRegistro = substr(trim((string)($persona['Fecha_Registro'] ?? '')), 0, 10);
        return [
            'id_persona'      => (int)($persona['Id_Persona'] ?? 0),
            'nombre'          => $nombre,
            'ministerio'      => trim((string)($persona['Nombre_Ministerio'] ?? '')) ?: 'Sin ministerio',
            'lider'           => trim((string)($persona['Nombre_Lider'] ?? '')) ?: 'Sin líder',
            'celula'          => trim((string)($persona['Nombre_Celula'] ?? '')) ?: 'Sin célula',
            'proceso'         => $this->normalizarProcesoValor($persona['Proceso'] ?? '') ?: 'Sin etapa',
            'fecha_registro'  => $fechaRegistro,
        ];
    }

    /**
     * Construye la tabla GANAR con subcategorías:
     * - GI: Ganados en iglesia
     * - GC: Ganados en célula
     * - FV: Fonovisitas (checklist Ganar índice 2)
     * - V: Visitas (checklist Ganar índice 3)
     *
     * Filas = meses, columnas = subcategorías.
     * Estructura devuelta:
     *   [titulo, anio, meses, columnas, rows[mes => [gi,gc,fv,v,total]], totales, detalles[col][mes][]]
     */
    private function construirTablaGanarMensual(array $personas, int $anio): array {
        $meses = $this->obtenerMesesAbreviados();
        $cols  = ['gi' => 'GI', 'gc' => 'GC', 'fv' => 'FV', 'v' => 'V'];

        $rows    = [];
        $totales = ['gi' => 0, 'gc' => 0, 'fv' => 0, 'v' => 0, 'total' => 0];
        $detalles = [];   // detalles[col][mes][]

        for ($m = 1; $m <= 12; $m++) {
            $rows[$m] = ['mes' => $meses[$m], 'gi' => 0, 'gc' => 0, 'fv' => 0, 'v' => 0, 'total' => 0];
        }

        foreach ($personas as $persona) {
            if (!$this->esPersonaNueva($persona)) {
                continue;
            }

            $fechaYmd = substr(trim((string)($persona['Fecha_Registro'] ?? '')), 0, 10);
            $ts = strtotime($fechaYmd);
            if ($ts === false || (int)date('Y', $ts) !== $anio) {
                continue;
            }
            $mes = (int)date('n', $ts);

            $origen = $this->clasificarOrigenGanar($persona);
            $proceso = $this->normalizarProcesoValor($persona['Proceso'] ?? '');
            $checklist = $this->obtenerChecklist($persona);

            if ($origen === 'iglesia') {
                $rows[$mes]['gi']++;
                $totales['gi']++;
            } elseif ($origen === 'celula') {
                $rows[$mes]['gc']++;
                $totales['gc']++;
            }

            if ($this->peldanoMarcado($checklist, 'Ganar', 2, $proceso)) {
                $rows[$mes]['fv']++;
                $totales['fv']++;
            }

            if ($this->peldanoMarcado($checklist, 'Ganar', 3, $proceso)) {
                $rows[$mes]['v']++;
                $totales['v']++;
            }

            $rows[$mes]['total']++;
            $totales['total']++;

            $detalle = $this->construirDetallePersonaReporteMinisterial($persona);

            if ($origen === 'iglesia') {
                $detalles['gi'][$mes][] = $detalle;
            } elseif ($origen === 'celula') {
                $detalles['gc'][$mes][] = $detalle;
            }

            if ($this->peldanoMarcado($checklist, 'Ganar', 2, $proceso)) {
                $detalles['fv'][$mes][] = $detalle;
            }

            if ($this->peldanoMarcado($checklist, 'Ganar', 3, $proceso)) {
                $detalles['v'][$mes][] = $detalle;
            }

            $detalles['total'][$mes][] = $detalle;
        }

        return [
            'titulo'   => 'GANAR',
            'anio'     => $anio,
            'meses'    => $meses,
            'columnas' => $cols,
            'rows'     => $rows,
            'totales'  => $totales,
            'detalles' => $detalles,
        ];
    }

    private function construirTarjetasUniversidadVida(array $personas): array {
        $resumen = [
            'total' => 0,
            'celula' => 0,
            'iglesia' => 0,
            'otros' => 0,
        ];

        foreach ($personas as $persona) {
            if (!$this->esOrigenValidoUniversidadVida($persona)) {
                continue;
            }

            $resumen['total']++;
            $origen = $this->clasificarOrigenGanar($persona);
            if ($origen === 'celula') {
                $resumen['celula']++;
            } elseif ($origen === 'iglesia') {
                $resumen['iglesia']++;
            } else {
                $resumen['otros']++;
            }
        }

        return $resumen;
    }

    /**
     * Construye una tabla mensual por peldaños para Consolidar, Discipular o Enviar.
     * $peldanos: array asociativo [clave => etiqueta]
     * Estructura devuelta igual que construirTablaGanarMensual.
     */
    private function construirTablaPeldanosMensual(array $personas, int $anio, string $titulo, string $etapa, array $peldanos): array {
        $meses    = $this->obtenerMesesAbreviados();
        $rows     = [];
        $totales  = array_fill_keys(array_keys($peldanos), 0);
        $totales['total'] = 0;
        $detalles = [];

        for ($m = 1; $m <= 12; $m++) {
            $rows[$m] = array_merge(['mes' => $meses[$m]], array_fill_keys(array_keys($peldanos), 0), ['total' => 0]);
        }

        foreach ($personas as $persona) {
            $proceso = $this->normalizarProcesoValor($persona['Proceso'] ?? '');
            if ($etapa !== 'Consolidar' && $proceso !== $etapa) {
                continue;
            }

            $fechaYmd = substr(trim((string)($persona['Fecha_Registro'] ?? '')), 0, 10);
            $ts = strtotime($fechaYmd);
            if ($ts === false || (int)date('Y', $ts) !== $anio) {
                continue;
            }
            $mes = (int)date('n', $ts);

            $checklist = $this->obtenerChecklist($persona);
            $contado = false;
            $detalle = $this->construirDetallePersonaReporteMinisterial($persona);

            foreach ($peldanos as $col => $idx) {
                $marcado = false;
                if ($etapa === 'Consolidar' && $col === 'uv') {
                    $marcado = $this->esOrigenValidoUniversidadVida($persona);
                } else {
                    $marcado = $this->peldanoMarcado($checklist, $etapa, $idx, $proceso);
                }

                if ($marcado) {
                    $rows[$mes][$col]++;
                    $totales[$col]++;
                    $detalles[$col][$mes][] = $detalle;
                    $contado = true;
                }
            }

            if ($contado) {
                $rows[$mes]['total']++;
                $totales['total']++;
                $detalles['total'][$mes][] = $detalle;
            }
        }

        return [
            'titulo'   => $titulo,
            'anio'     => $anio,
            'meses'    => $meses,
            'columnas' => $peldanos,
            'rows'     => $rows,
            'totales'  => $totales,
            'detalles' => $detalles,
        ];
    }

    /**
     * Construye tabla ENVIAR: personas en proceso Enviar que ya están haciendo célula
     * (peldaño índice 2 de Enviar = 'Celula').
     * Columna única: # Células abiertas.
     */
    private function construirTablaEnviarMensual(array $personas, int $anio): array {
        $meses   = $this->obtenerMesesAbreviados();
        $rows    = [];
        $totales = ['celulas' => 0, 'total' => 0];
        $detalles = [];

        for ($m = 1; $m <= 12; $m++) {
            $rows[$m] = ['mes' => $meses[$m], 'celulas' => 0, 'total' => 0];
        }

        foreach ($personas as $persona) {
            $proceso = $this->normalizarProcesoValor($persona['Proceso'] ?? '');
            if ($proceso !== 'Enviar') {
                continue;
            }

            $fechaYmd = substr(trim((string)($persona['Fecha_Registro'] ?? '')), 0, 10);
            $ts = strtotime($fechaYmd);
            if ($ts === false || (int)date('Y', $ts) !== $anio) {
                continue;
            }
            $mes = (int)date('n', $ts);

            $checklist = $this->obtenerChecklist($persona);
            $detalle   = $this->construirDetallePersonaReporteMinisterial($persona);

            // Peldaño índice 2 de Enviar = 'Celula' (ya abrió célula)
            if ($this->peldanoMarcado($checklist, 'Enviar', 2, $proceso)) {
                $rows[$mes]['celulas']++;
                $totales['celulas']++;
                $detalles['celulas'][$mes][] = $detalle;
            }

            $rows[$mes]['total']++;
            $totales['total']++;
            $detalles['total'][$mes][] = $detalle;
        }

        return [
            'titulo'   => 'ENVIAR',
            'anio'     => $anio,
            'meses'    => $meses,
            'columnas' => ['celulas' => '# CELULAS'],
            'rows'     => $rows,
            'totales'  => $totales,
            'detalles' => $detalles,
        ];
    }

    /**
     * Tabla GANAR 2026: filas = ministerios, columnas = meses × (Celula | Iglesia).
     * Incluye TODAS las personas registradas (sin filtro de proceso).
     */
    private function construirTablaGananciaMinisterioPorMes(array $personas, int $anio): array {
        $meses   = $this->obtenerMesesAbreviados();
        $rowsMap = [];
        $totales = [
            'meses' => array_fill(1, 12, ['celula' => 0, 'iglesia' => 0]),
            'anual' => ['celula' => 0, 'iglesia' => 0, 'total' => 0],
        ];
        $detalles = []; // [ministerio][col][mes][]

        foreach ($personas as $persona) {
            if (!$this->esPersonaNueva($persona)) {
                continue;
            }

            $fechaYmd = substr(trim((string)($persona['Fecha_Registro'] ?? '')), 0, 10);
            $ts = strtotime($fechaYmd);
            if ($ts === false || (int)date('Y', $ts) !== $anio) {
                continue;
            }
            $mes = (int)date('n', $ts);

            $ministerio = trim((string)($persona['Nombre_Ministerio'] ?? '')) ?: 'Sin ministerio';
            $origen     = $this->clasificarOrigenGanar($persona);
            $col        = ($origen === 'celula') ? 'celula' : 'iglesia';

            if (!isset($rowsMap[$ministerio])) {
                $rowsMap[$ministerio] = [
                    'ministerio' => $ministerio,
                    'meses' => array_fill(1, 12, ['celula' => 0, 'iglesia' => 0]),
                    'anual' => ['celula' => 0, 'iglesia' => 0, 'total' => 0],
                ];
            }

            $rowsMap[$ministerio]['meses'][$mes][$col]++;
            $rowsMap[$ministerio]['anual'][$col]++;
            $rowsMap[$ministerio]['anual']['total']++;

            $totales['meses'][$mes][$col]++;
            $totales['anual'][$col]++;
            $totales['anual']['total']++;

            $detalle = $this->construirDetallePersonaReporteMinisterial($persona);
            $detalles[$ministerio][$col][$mes][] = $detalle;
            $detalles[$ministerio]['total'][$mes][] = $detalle;
        }

        ksort($rowsMap);

        return [
            'titulo'   => 'Ganancia de almas por ministerio',
            'anio'     => $anio,
            'meses'    => $meses,
            'rows'     => array_values($rowsMap),
            'totales'  => $totales,
            'detalles' => $detalles,
        ];
    }

    /**
     * Tabla CONSOLIDAR por ministerio (anual): U.V, Encuentro, Bautismo.
     * Cada celda es interactiva para mostrar personas.
     */
    private function construirTablaConsolidarPorMinisterio(array $personas, int $anio): array {
        $rowsMap = [];
        $totales = ['uv' => 0, 'e' => 0, 'b' => 0, 'total' => 0];
        $detalles = []; // [ministerio][uv|e|b|total][]

        foreach ($personas as $persona) {
            $fechaYmd = substr(trim((string)($persona['Fecha_Registro'] ?? '')), 0, 10);
            $ts = strtotime($fechaYmd);
            if ($ts === false || (int)date('Y', $ts) !== $anio) {
                continue;
            }

            $proceso = $this->normalizarProcesoValor($persona['Proceso'] ?? '');

            $ministerio = trim((string)($persona['Nombre_Ministerio'] ?? '')) ?: 'Sin ministerio';
            if (!isset($rowsMap[$ministerio])) {
                $rowsMap[$ministerio] = [
                    'ministerio' => $ministerio,
                    'uv' => 0,
                    'e' => 0,
                    'b' => 0,
                    'total' => 0,
                ];
            }

            $checklist = $this->obtenerChecklist($persona);
            $detalle = $this->construirDetallePersonaReporteMinisterial($persona);

            if ($this->esOrigenValidoUniversidadVida($persona)) {
                $rowsMap[$ministerio]['uv']++;
                $totales['uv']++;
                $detalles[$ministerio]['uv'][] = $detalle;
            }
            if ($this->peldanoMarcado($checklist, 'Consolidar', 1, $proceso)) {
                $rowsMap[$ministerio]['e']++;
                $totales['e']++;
                $detalles[$ministerio]['e'][] = $detalle;
            }
            if ($this->peldanoMarcado($checklist, 'Consolidar', 2, $proceso)) {
                $rowsMap[$ministerio]['b']++;
                $totales['b']++;
                $detalles[$ministerio]['b'][] = $detalle;
            }
        }

        foreach ($rowsMap as $ministerio => $row) {
            $rowsMap[$ministerio]['total'] = (int)$row['uv'] + (int)$row['e'] + (int)$row['b'];
            $totales['total'] += $rowsMap[$ministerio]['total'];
            $detalles[$ministerio]['total'] = array_merge(
                $detalles[$ministerio]['uv'] ?? [],
                $detalles[$ministerio]['e'] ?? [],
                $detalles[$ministerio]['b'] ?? []
            );
        }

        ksort($rowsMap);

        return [
            'titulo' => 'CONSOLIDAR POR MINISTERIO',
            'anio' => $anio,
            'rows' => array_values($rowsMap),
            'totales' => $totales,
            'detalles' => $detalles,
        ];
    }

    public function ministerial() {
        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('reportes', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $anio = (int)($_GET['anio'] ?? date('Y'));
        if ($anio < 2020 || $anio > ((int)date('Y') + 1)) {
            $anio = (int)date('Y');
        }

        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider      = $_GET['lider'] ?? '';
        $filtroCelula     = $_GET['celula'] ?? '';

        $filtroRol    = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();

        $opcionesFiltro    = $this->construirOpcionesFiltroMinisterioLider($filtroCelulas);
        $celulasDisponibles = $opcionesFiltro['celulas_disponibles'];
        $celulaIdsPermitidas = array_map(static function($c) {
            return (int)($c['Id_Celula'] ?? 0);
        }, $celulasDisponibles);

        $filtroCelula     = ($filtroCelula !== '' && in_array((int)$filtroCelula, $celulaIdsPermitidas, true)) ? (int)$filtroCelula : (($filtroCelula === '0') ? '0' : '');
        $filtroMinisterio = ($filtroMinisterio !== '' && isset($opcionesFiltro['ministerio_ids_permitidos'][(int)$filtroMinisterio])) ? (int)$filtroMinisterio : '';
        $filtroLider      = ($filtroLider !== '' && isset($opcionesFiltro['lider_ids_permitidos'][(int)$filtroLider])) ? (int)$filtroLider : '';

        $fechaInicioAnio = sprintf('%04d-01-01', $anio);
        $fechaFinAnio    = sprintf('%04d-12-31', $anio);

        $idMinisterioFiltro = ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null;
        $idLiderFiltro      = ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null;
        $idCelulaFiltro     = ($filtroCelula !== '') ? (string)$filtroCelula : null;

        $personasAnio = $this->personaModel->getWithFiltersAndRole(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            null,
            null,
            $idCelulaFiltro,
            null,
            null,
            $fechaInicioAnio,
            $fechaFinAnio
        );

        // GANAR: GI = iglesia, GC = célula, V = otros
        $tablaGanar = $this->construirTablaGanarMensual($personasAnio, $anio);

        // CONSOLIDAR: UV (índice 0), E (índice 1), B (índice 2)
        $tablaConsolidar = $this->construirTablaPeldanosMensual(
            $personasAnio, $anio, 'CONSOLIDAR', 'Consolidar',
            ['uv' => 0, 'e' => 1, 'b' => 2]
        );

        // DISCIPULAR: CD-M1-2 (idx 0), CD-M3-4 (idx 1), CD-M5-6 (idx 2)
        $tablaDiscipular = $this->construirTablaPeldanosMensual(
            $personasAnio, $anio, 'DISCIPULAR', 'Discipular',
            ['cdm12' => 0, 'cdm34' => 1, 'cdm56' => 2]
        );

        // ENVIAR: # células
        $tablaEnviar = $this->construirTablaEnviarMensual($personasAnio, $anio);

        // GANANCIA por ministerio: todas las personas, filas=ministerio, columnas=mes×(Celula|Iglesia)
        $tablaGanancia = $this->construirTablaGananciaMinisterioPorMes($personasAnio, $anio);
        $tablaConsolidarMinisterio = $this->construirTablaConsolidarPorMinisterio($personasAnio, $anio);

        $tablas = [
            'ganar'      => $tablaGanar,
            'consolidar' => $tablaConsolidar,
            'discipular' => $tablaDiscipular,
            'enviar'     => $tablaEnviar,
        ];

        $detallesTablas = [];
        foreach ($tablas as $key => $tabla) {
            $detallesTablas[$key] = $tabla['detalles'] ?? [];
        }

        $this->view('reportes/ministerial', [
            'anio'                    => $anio,
            'filtro_ministerio'       => (string)$filtroMinisterio,
            'filtro_lider'            => (string)$filtroLider,
            'filtro_celula'           => (string)$filtroCelula,
            'ministerios_disponibles' => $opcionesFiltro['ministerios_disponibles'],
            'lideres_disponibles'     => $opcionesFiltro['lideres_disponibles'],
            'celulas_disponibles'     => $celulasDisponibles,
            'tablas_reportes'         => $tablas,
            'detalles_tablas'         => $detallesTablas,
            'tabla_ganancia'          => $tablaGanancia,
            'detalles_ganancia'       => $tablaGanancia['detalles'] ?? [],
            'tabla_consolidar_ministerio' => $tablaConsolidarMinisterio,
            'detalles_consolidar_ministerio' => $tablaConsolidarMinisterio['detalles'] ?? [],
        ]);
    }

    public function index() {
        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('reportes', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $tipoReporte = $this->resolverTipoReporte($_GET['tipo'] ?? 'personas');
        $fechaReferencia = $this->normalizarFechaYmd($_GET['fecha_referencia'] ?? '');
        if ($fechaReferencia === '') {
            $fechaReferencia = date('Y-m-d', strtotime('-7 days'));
        }
        [$fechaInicio, $fechaFin] = $this->calcularRangoSemanaDomingoADomingo($fechaReferencia);
        $fechaInicioPersonalizada = $this->normalizarFechaYmd($_GET['fecha_inicio'] ?? '');
        $fechaFinPersonalizada = $this->normalizarFechaYmd($_GET['fecha_fin'] ?? '');
        if ($fechaInicioPersonalizada !== '' && $fechaFinPersonalizada !== '' && strcmp($fechaInicioPersonalizada, $fechaFinPersonalizada) > 0) {
            [$fechaInicioPersonalizada, $fechaFinPersonalizada] = [$fechaFinPersonalizada, $fechaInicioPersonalizada];
        }
        if ($tipoReporte === 'personas' && $fechaInicioPersonalizada === '' && $fechaFinPersonalizada === '') {
            $fechaInicioPersonalizada = $fechaInicio;
            $fechaFinPersonalizada = $fechaFin;
        }
        $usarRangoPersonalizado = ($fechaInicioPersonalizada !== '' && $fechaFinPersonalizada !== '');
        if ($usarRangoPersonalizado) {
            $fechaInicio = $fechaInicioPersonalizada;
            $fechaFin = $fechaFinPersonalizada;
            $fechaReferencia = $fechaFinPersonalizada;
        }

        $filtroCelula = $_GET['celula'] ?? '';
        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';
        $filtroMesMeta = $_GET['mes_meta'] ?? '';
        $rangoEscalera = $this->construirRangoMesCalendario($_GET['mes_escalera'] ?? '');
        $mesEscalera = (string)($rangoEscalera['mes'] ?? date('Y-m'));
        $fechaInicioEscalera = (string)($rangoEscalera['inicio'] ?? date('Y-m-01'));
        $fechaFinEscalera = (string)($rangoEscalera['fin'] ?? date('Y-m-t'));
        $escalaGanar = $this->resolverEscalaGanar($_GET['escala_ganar'] ?? 'semanal');
        $rangoGanar = $this->construirRangoGanar($fechaReferencia, $escalaGanar);
        $fechaInicioGanar = (string)$rangoGanar['inicio'];
        $fechaFinGanar = (string)$rangoGanar['fin'];
        if ($usarRangoPersonalizado) {
            $rangoGanar = [
                'inicio' => $fechaInicio,
                'fin' => $fechaFin,
                'label' => 'Personalizado'
            ];
            $fechaInicioGanar = $fechaInicio;
            $fechaFinGanar = $fechaFin;
        }

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
        $filtroCelulaGanar = $tipoReporte === 'personas' ? '' : $filtroCelula;

        $almasGanadas = $this->personaModel->getAlmasGanadasPorMinisterioWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, $filtroMinisterio, $filtroLider);

        $resumenOrigenGanados = $this->personaModel->getResumenGanadosOrigenWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, $filtroMinisterio, $filtroLider);
        $detalleOrigenGanados = [
            'celula' => $this->personaModel->getDetalleGanadosOrigenWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, 'celula', $filtroMinisterio, $filtroLider),
            'iglesia' => $this->personaModel->getDetalleGanadosOrigenWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, 'iglesia', $filtroMinisterio, $filtroLider),
            'asignados' => $this->personaModel->getDetalleGanadosOrigenWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, 'asignados', $filtroMinisterio, $filtroLider),
        ];
        // Alias temporal para evitar ruptura en vistas que aun consulten la clave anterior.
        $detalleOrigenGanados['domingo'] = $detalleOrigenGanados['iglesia'];

        $almasPorEdades = $this->personaModel->getAlmasGanadasPorEdadesWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, $filtroMinisterio, $filtroLider);

        $procesoGanar = $this->personaModel->getResumenProcesoGanarWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, $filtroCelulaGanar, $filtroMinisterio, $filtroLider);

        // Escalera del éxito: siempre consultar por mes y traer el mes actual por defecto.
        $reporteEscaleraMesActual = $this->personaModel->getReporteEscaleraMesActual(
            $filtroRol,
            $fechaInicioEscalera,
            $fechaFinEscalera,
            $filtroMinisterio,
            $filtroLider,
            $filtroCelulaGanar
        );
        $reporteEscaleraMesActual['mes_label'] = (string)($rangoEscalera['label'] ?? ($reporteEscaleraMesActual['mes_label'] ?? ''));

        $asistenciaCelulas = $this->asistenciaModel->getAsistenciaPorCelulaWithRole($fechaInicio, $fechaFin, $filtroCelulas, $filtroMinisterio, $filtroLider);
        $indicadoresCelulas = $this->construirIndicadoresCelulas(
            $fechaReferencia,
            $fechaInicio,
            $fechaFin,
            $filtroCelulas,
            $filtroMinisterio,
            $filtroLider,
            $filtroCelula
        );
        $tablaAperturasCelulas = $this->construirTablaAperturasCelulasPorMinisterio(
            $fechaReferencia,
            $filtroCelulas,
            $filtroMinisterio,
            $filtroLider,
            $filtroCelula
        );
        $tablaGanarMinisterio = $this->construirTablaGanarPorMinisterio(
            $fechaReferencia,
            $filtroRol,
            $fechaInicioGanar,
            $fechaFinGanar,
            $filtroMinisterio,
            $filtroLider,
            $filtroCelulaGanar
        );

        $personasRangoGanar = $this->personaModel->getWithFiltersAndRole(
            $filtroRol,
            ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null,
            ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null,
            null,
            null,
            ($filtroCelulaGanar !== '') ? (string)$filtroCelulaGanar : null,
            null,
            null,
            $fechaInicioGanar,
            $fechaFinGanar
        );
        $tarjetasUniversidadVida = $this->construirTarjetasUniversidadVida($personasRangoGanar);
        $reporteGanadosFinSemanaAnterior = $this->construirReporteGanadosFinSemanaAnterior(
            $fechaInicio,
            $fechaFin,
            $filtroRol,
            $filtroMinisterio,
            $filtroLider
        );

        // Tablas ministeriales interactivas para incrustar dentro del reporte de GANAR.
        $anioMinisterial = (int)substr((string)$fechaReferencia, 0, 4);
        if ($anioMinisterial < 2020 || $anioMinisterial > ((int)date('Y') + 1)) {
            $anioMinisterial = (int)date('Y');
        }
        $fechaInicioAnioMinisterial = sprintf('%04d-01-01', $anioMinisterial);
        $fechaFinAnioMinisterial = sprintf('%04d-12-31', $anioMinisterial);
        $idMinisterioFiltroMinisterial = ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null;
        $idLiderFiltroMinisterial = ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null;
        $idCelulaFiltroMinisterial = ($filtroCelulaGanar !== '') ? (string)$filtroCelulaGanar : null;

        $personasAnioMinisterial = $this->personaModel->getWithFiltersAndRole(
            $filtroRol,
            $idMinisterioFiltroMinisterial,
            $idLiderFiltroMinisterial,
            null,
            null,
            $idCelulaFiltroMinisterial,
            null,
            null,
            $fechaInicioAnioMinisterial,
            $fechaFinAnioMinisterial
        );

        $tablaGanarMensualMinisterial = $this->construirTablaGanarMensual($personasAnioMinisterial, $anioMinisterial);
        $tablaConsolidarMensualMinisterial = $this->construirTablaPeldanosMensual(
            $personasAnioMinisterial,
            $anioMinisterial,
            'CONSOLIDAR',
            'Consolidar',
            ['uv' => 0, 'e' => 1, 'b' => 2]
        );
        $tablaDiscipularMensualMinisterial = $this->construirTablaPeldanosMensual(
            $personasAnioMinisterial,
            $anioMinisterial,
            'DISCIPULAR',
            'Discipular',
            ['cdm12' => 0, 'cdm34' => 1, 'cdm56' => 2]
        );
        $tablaEnviarMensualMinisterial = $this->construirTablaEnviarMensual($personasAnioMinisterial, $anioMinisterial);
        $tablaGananciaMinisterial = $this->construirTablaGananciaMinisterioPorMes($personasAnioMinisterial, $anioMinisterial);

        $tablasMinisterial = [
            'ganar' => $tablaGanarMensualMinisterial,
            'consolidar' => $tablaConsolidarMensualMinisterial,
            'discipular' => $tablaDiscipularMensualMinisterial,
            'enviar' => $tablaEnviarMensualMinisterial,
        ];
        $detallesMinisterial = [];
        foreach ($tablasMinisterial as $keyTablaMinisterial => $tablaMinisterial) {
            $detallesMinisterial[$keyTablaMinisterial] = $tablaMinisterial['detalles'] ?? [];
        }

        $cumplimientoMetas = $this->construirTablaCumplimientoMetas(
            $fechaReferencia,
            $filtroRol,
            $filtroMinisterios,
            $filtroMinisterio,
            $filtroLider,
            $filtroCelulaGanar
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

        $estadoEntregoSobreReporte = $this->asistenciaModel->getEstadoEntregoSobrePorCelulaSemana(
            array_map(static function($item) {
                return (int)($item['Id_Celula'] ?? 0);
            }, $asistenciaCelulas),
            $fechaInicio
        );

        foreach ($asistenciaCelulas as &$filaAsistenciaReporte) {
            $idCelulaFila = (int)($filaAsistenciaReporte['Id_Celula'] ?? 0);
            $filaAsistenciaReporte['Entrego_Sobre'] = !empty($estadoEntregoSobreReporte[$idCelulaFila]) ? 1 : 0;
        }
        unset($filaAsistenciaReporte);

        $filtroProgramaEscuelas = trim((string)($_GET['escuela_programa'] ?? ''));
        $filtroBusquedaEscuelas = trim((string)($_GET['escuela_buscar'] ?? ''));
        if (!in_array($filtroProgramaEscuelas, ['', 'universidad_vida', 'encuentro', 'bautismo', 'capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            $filtroProgramaEscuelas = '';
        }

        $personasActivasEscuelas = $this->personaModel->getWithFiltersAndRole(
            $filtroRol,
            ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null,
            ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null,
            null,
            'Activo',
            ($filtroCelula !== '') ? (string)$filtroCelula : null,
            null,
            null,
            null,
            null
        );

        $reporteEscuelasUv = $this->construirReporteUniversidadVidaEscuelas($personasActivasEscuelas);
        $estadosEscuelasUv = $this->escuelaEstadoModel->getEstadosPorPrograma(array_column($reporteEscuelasUv['rows'], 'id_persona'), 'universidad_vida');
        foreach ($reporteEscuelasUv['rows'] as &$rowUvEscuela) {
            $rowUvEscuela['va'] = !empty($estadosEscuelasUv[(int)($rowUvEscuela['id_persona'] ?? 0)]);
        }
        unset($rowUvEscuela);

        $resumenEscuelasInscripciones = $this->escuelaInscripcionModel->getResumenProgramas();
        $inscripcionesEscuelas = $this->escuelaInscripcionModel->getListado($filtroProgramaEscuelas, $filtroBusquedaEscuelas, 200);

        $data = [
            'tipo_reporte' => $tipoReporte,
            'fecha_referencia' => $fechaReferencia,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'fecha_inicio_filtro' => $fechaInicioPersonalizada,
            'fecha_fin_filtro' => $fechaFinPersonalizada,
            'rango_personalizado' => $usarRangoPersonalizado,
            'filtro_celula' => (string)$filtroCelula,
            'filtro_celula_ganar' => (string)$filtroCelulaGanar,
            'filtro_ministerio' => (string)$filtroMinisterio,
            'filtro_lider' => (string)$filtroLider,
            'filtro_mes_meta' => $filtroMesMeta,
            'mes_escalera' => $mesEscalera,
            'escala_ganar' => $escalaGanar,
            'ganar_label' => (string)($rangoGanar['label'] ?? 'Semanal'),
            'ganar_inicio' => $fechaInicioGanar,
            'ganar_fin' => $fechaFinGanar,
            'celulas_disponibles' => $celulasDisponibles,
            'ministerios_disponibles' => $opcionesFiltro['ministerios_disponibles'],
            'lideres_disponibles' => $opcionesFiltro['lideres_disponibles'],
            'almas_ganadas' => $almasGanadas,
            'resumen_origen_ganados' => $resumenOrigenGanados,
            'detalle_origen_ganados' => $detalleOrigenGanados,
            'almas_por_edades' => $almasPorEdades,
            'proceso_ganar' => $procesoGanar,
            'reporte_escalera_mes_actual' => $reporteEscaleraMesActual,
            'asistencia_celulas' => $asistenciaCelulas,
            'cumplimiento_metas' => $cumplimientoMetas,
            'indicadores_celulas' => $indicadoresCelulas,
            'tabla_aperturas_celulas' => $tablaAperturasCelulas,
            'tabla_ganar_ministerio' => $tablaGanarMinisterio,
            'tarjetas_universidad_vida' => $tarjetasUniversidadVida,
            'reporte_ganados_fin_semana_anterior' => $reporteGanadosFinSemanaAnterior,
            'anio_ministerial_tablas' => $anioMinisterial,
            'tablas_ministerial' => $tablasMinisterial,
            'detalles_tablas_ministerial' => $detallesMinisterial,
            'tabla_ganancia_ministerial' => $tablaGananciaMinisterial,
            'detalles_ganancia_ministerial' => $tablaGananciaMinisterial['detalles'] ?? [],
            'reporte_escuelas_uv' => $reporteEscuelasUv,
            'resumen_escuelas_inscripciones' => $resumenEscuelasInscripciones,
            'inscripciones_escuelas' => $inscripcionesEscuelas,
            'filtro_escuela_programa' => $filtroProgramaEscuelas,
            'filtro_escuela_buscar' => $filtroBusquedaEscuelas,
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
        $fechaInicioPersonalizada = $this->normalizarFechaYmd($_GET['fecha_inicio'] ?? '');
        $fechaFinPersonalizada = $this->normalizarFechaYmd($_GET['fecha_fin'] ?? '');
        if ($fechaInicioPersonalizada !== '' && $fechaFinPersonalizada !== '' && strcmp($fechaInicioPersonalizada, $fechaFinPersonalizada) > 0) {
            [$fechaInicioPersonalizada, $fechaFinPersonalizada] = [$fechaFinPersonalizada, $fechaInicioPersonalizada];
        }
        $usarRangoPersonalizado = ($fechaInicioPersonalizada !== '' && $fechaFinPersonalizada !== '');
        if ($usarRangoPersonalizado) {
            $fechaInicio = $fechaInicioPersonalizada;
            $fechaFin = $fechaFinPersonalizada;
            $fechaReferencia = $fechaFinPersonalizada;
        }
        $filtroCelula = $_GET['celula'] ?? '';
        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';
        $rangoEscalera = $this->construirRangoMesCalendario($_GET['mes_escalera'] ?? '');
        $fechaInicioEscalera = (string)($rangoEscalera['inicio'] ?? date('Y-m-01'));
        $fechaFinEscalera = (string)($rangoEscalera['fin'] ?? date('Y-m-t'));
        $tipoReporte = $this->resolverTipoReporte($_GET['tipo'] ?? 'personas');
        $escalaGanar = $this->resolverEscalaGanar($_GET['escala_ganar'] ?? 'semanal');
        $rangoGanar = $this->construirRangoGanar($fechaReferencia, $escalaGanar);
        $fechaInicioGanar = (string)$rangoGanar['inicio'];
        $fechaFinGanar = (string)$rangoGanar['fin'];
        if ($usarRangoPersonalizado) {
            $fechaInicioGanar = $fechaInicio;
            $fechaFinGanar = $fechaFin;
        }

        $filtroRol = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();

        $opcionesFiltro = $this->construirOpcionesFiltroMinisterioLider($filtroCelulas);
        $celulaIdsPermitidas = array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $opcionesFiltro['celulas_disponibles']);

        $filtroMinisterio = ($filtroMinisterio !== '' && isset($opcionesFiltro['ministerio_ids_permitidos'][(int)$filtroMinisterio])) ? (int)$filtroMinisterio : '';
        $filtroLider = ($filtroLider !== '' && isset($opcionesFiltro['lider_ids_permitidos'][(int)$filtroLider])) ? (int)$filtroLider : '';
        $filtroCelula = ($filtroCelula !== '' && in_array((int)$filtroCelula, $celulaIdsPermitidas, true)) ? (int)$filtroCelula : (($filtroCelula === '0') ? '0' : '');

        $almasGanadas = $this->personaModel->getAlmasGanadasPorMinisterioWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, $filtroMinisterio, $filtroLider);
        $procesoGanar = $this->personaModel->getResumenProcesoGanarWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, $filtroCelula, $filtroMinisterio, $filtroLider);
        $resumenOrigenGanados = $this->personaModel->getResumenGanadosOrigenWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, $filtroMinisterio, $filtroLider);
        $almasPorEdades = $this->personaModel->getAlmasGanadasPorEdadesWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, $filtroMinisterio, $filtroLider);

        // Escalera del éxito para exportación con vista mensual y mes actual por defecto.
        $reporteEscaleraMesActual = $this->personaModel->getReporteEscaleraMesActual(
            $filtroRol,
            $fechaInicioEscalera,
            $fechaFinEscalera,
            $filtroMinisterio,
            $filtroLider,
            $filtroCelula
        );
        $reporteEscaleraMesActual['mes_label'] = (string)($rangoEscalera['label'] ?? ($reporteEscaleraMesActual['mes_label'] ?? ''));

        $asistenciaCelulas = $this->asistenciaModel->getAsistenciaPorCelulaWithRole($fechaInicio, $fechaFin, $filtroCelulas, $filtroMinisterio, $filtroLider);
        $cumplimientoMetas = $this->construirTablaCumplimientoMetas(
            $fechaReferencia,
            $filtroRol,
            DataIsolation::generarFiltroMinisterios(),
            $filtroMinisterio,
            $filtroLider,
            $filtroCelula
        );

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

        if ($tipoReporte === 'celulas') {
            $rows[] = ['Reporte de Celulas', '', '', '', '', '', ''];
            $rows[] = ['Periodo', $fechaInicio . ' a ' . $fechaFin, '', '', '', '', ''];
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
        } else {
            $rows[] = ['Reporte de Personas', '', '', '', '', '', ''];
            $rows[] = ['Periodo', $fechaInicio . ' a ' . $fechaFin, '', '', '', '', ''];
            $rows[] = ['', '', '', '', '', '', ''];
            $rows[] = ['Almas Ganadas por Ministerio', '', '', '', '', '', ''];
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
            $rows[] = ['Escalera del Exito - Mes Actual', '', '', '', '', '', ''];
            $rows[] = ['Periodo', (string)($reporteEscaleraMesActual['inicio'] ?? '') . ' a ' . (string)($reporteEscaleraMesActual['fin'] ?? ''), '', '', '', '', ''];
            $rows[] = ['Total personas del mes', (string)($reporteEscaleraMesActual['total_personas_mes'] ?? 0), '', '', '', '', ''];
            $rows[] = ['Etapa', 'Peldaño', 'Cantidad', '', '', '', ''];

            foreach (($reporteEscaleraMesActual['peldaños'] ?? []) as $etapa => $peldaños) {
                foreach ($peldaños as $peldaño => $cantidad) {
                    $rows[] = [
                        (string)$etapa,
                        (string)$peldaño,
                        (string)$cantidad,
                        '',
                        '',
                        '',
                        ''
                    ];
                }
            }

            $rows[] = ['', '', '', '', '', '', ''];
            $rows[] = ['Ganados por Origen', '', '', '', '', '', ''];
            $rows[] = ['Ganados en Celula', (string)($resumenOrigenGanados['Ganados_Celula'] ?? 0), '', '', '', '', ''];
            $rows[] = ['Ganados en Iglesia', (string)($resumenOrigenGanados['Ganados_Iglesia'] ?? ($resumenOrigenGanados['Ganados_Domingo'] ?? 0)), '', '', '', '', ''];
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
            $rows[] = ['Cumplimiento de Metas', '', '', '', '', '', ''];
            $rows[] = ['Ministerio', 'Meta', 'Pendiente', 'Ganados', '', '', ''];
            foreach (($cumplimientoMetas['rows'] ?? []) as $item) {
                $rows[] = [
                    (string)($item['ministerio'] ?? 'Sin ministerio'),
                    (string)($item['meta'] ?? 0),
                    (string)($item['pendiente'] ?? 0),
                    (string)($item['ganados'] ?? 0),
                    '',
                    '',
                    ''
                ];
            }
            $rows[] = [
                'TOTAL',
                (string)($cumplimientoMetas['totales']['meta'] ?? 0),
                (string)($cumplimientoMetas['totales']['pendiente'] ?? 0),
                (string)($cumplimientoMetas['totales']['ganados'] ?? 0),
                '',
                '',
                ''
            ];
        }

        $this->exportCsv(
            'reporte_' . $tipoReporte . '_' . date('Ymd_His'),
            ['Seccion', 'Columna 1', 'Columna 2', 'Columna 3', 'Columna 4', 'Columna 5', 'Columna 6'],
            $rows,
            false
        );
    }

    public function almasGanadas() {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');

        $filtroRol = DataIsolation::generarFiltroPersonas();

        $data = $this->personaModel->getAlmasGanadasPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol);
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function asistenciaCelulas() {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');

        $filtroCelulas = DataIsolation::generarFiltroCelulas();

        $data = $this->asistenciaModel->getAsistenciaPorCelulaWithRole($fechaInicio, $fechaFin, $filtroCelulas);
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}


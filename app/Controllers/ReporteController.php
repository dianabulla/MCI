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
require_once APP . '/Models/EscuelaFormacionAsistenciaClase.php';
require_once APP . '/Helpers/DataIsolation.php';

class ReporteController extends BaseController {
    private $personaModel;
    private $asistenciaModel;
    private $celulaModel;
    private $ministerioModel;
    private $escuelaInscripcionModel;
    private $escuelaEstadoModel;
    private $escuelaAsistenciaClaseModel;

    /** @var array<int, array{pagado: bool, valor: float}> */
    private $mapaPagosMovimientosUv = [];

    /** @var array<string, array{pagado: bool, valor: float}> */
    private $mapaPagosMovimientosUvPorCedula = [];

    /** Filtro activo en dashboard UV (excluir asistencia encuentro, etc.). */
    private string $uvFiltroEncuentroDashboard = '';

    public function __construct() {
        $this->personaModel = new Persona();
        $this->asistenciaModel = new Asistencia();
        $this->celulaModel = new Celula();
        $this->ministerioModel = new Ministerio();
        $this->escuelaInscripcionModel = new EscuelaFormacionInscripcion();
        $this->escuelaEstadoModel = new EscuelaFormacionEstado();
        $this->escuelaAsistenciaClaseModel = new EscuelaFormacionAsistenciaClase();

        // Asegura el filtro de "solo nuevas" en reportes de Ganar.
        $this->personaModel->ensureEsAntiguoColumnExists();
    }

    private function calcularRangoSemanaDomingoADomingo($fechaReferencia) {
        $timestamp = strtotime((string)$fechaReferencia);
        if ($timestamp === false) {
            $timestamp = time();
        }

        // Semana calendario de lunes a domingo.
        $diaSemana = (int)date('N', $timestamp); // 1 lunes, 7 domingo
        $inicio = strtotime('-' . ($diaSemana - 1) . ' days', $timestamp);
        $fin = strtotime('+6 days', $inicio);

        return [date('Y-m-d', $inicio), date('Y-m-d', $fin)];
    }

    /**
     * Semana calendario completa anterior (lunes–domingo) respecto a la fecha dada.
     */
    private function calcularRangoSemanaVencida($fechaReferencia = null) {
        $timestamp = strtotime((string)($fechaReferencia ?: date('Y-m-d')));
        if ($timestamp === false) {
            $timestamp = time();
        }

        $diaSemana = (int)date('N', $timestamp);
        $inicioSemanaActual = strtotime('-' . ($diaSemana - 1) . ' days', $timestamp);
        $finVencida = strtotime('-1 day', $inicioSemanaActual);
        $inicioVencida = strtotime('-6 days', $finVencida);

        return [date('Y-m-d', $inicioVencida), date('Y-m-d', $finVencida)];
    }

    /**
     * Resuelve fechas del reporte evitando rangos huérfanos en la URL.
     *
     * @return array{
     *   fecha_referencia: string,
     *   fecha_inicio: string,
     *   fecha_fin: string,
     *   fecha_inicio_ganar: string,
     *   fecha_fin_ganar: string,
     *   rango_ganar: array{inicio: string, fin: string, label: string},
     *   usar_rango_personalizado: bool,
     *   fecha_inicio_filtro: string,
     *   fecha_fin_filtro: string,
     *   semana_vencida_por_defecto: bool
     * }
     */
    private function resolverRangosFechaReporte(string $tipoReporte, string $escalaGanar): array {
        $fechaReferenciaExplicita = $this->normalizarFechaYmd($_GET['fecha_referencia'] ?? '');
        $fechaInicioPersonalizada = $this->normalizarFechaYmd($_GET['fecha_inicio'] ?? '');
        $fechaFinPersonalizada = $this->normalizarFechaYmd($_GET['fecha_fin'] ?? '');
        if ($fechaInicioPersonalizada !== '' && $fechaFinPersonalizada !== '' && strcmp($fechaInicioPersonalizada, $fechaFinPersonalizada) > 0) {
            [$fechaInicioPersonalizada, $fechaFinPersonalizada] = [$fechaFinPersonalizada, $fechaInicioPersonalizada];
        }

        $semanaVencidaPorDefecto = false;
        $esSemanaActualPorDefecto = false;

        if ($tipoReporte === 'personas') {
            // Por defecto: semana actual (lun–dom) que contiene la fecha de hoy.
            if ($fechaReferenciaExplicita === '') {
                $fechaReferencia = date('Y-m-d');
                [$fechaInicio, $fechaFin] = $this->calcularRangoSemanaDomingoADomingo($fechaReferencia);
                $esSemanaActualPorDefecto = true;
            } else {
                $fechaReferencia = $fechaReferenciaExplicita;
                [$fechaInicio, $fechaFin] = $this->calcularRangoSemanaDomingoADomingo($fechaReferencia);
            }

            $rangoGanar = $this->construirRangoGanar($fechaReferencia, $escalaGanar);
            if ($escalaGanar === 'semanal') {
                $fechaInicioGanar = $fechaInicio;
                $fechaFinGanar = $fechaFin;
                $rangoGanar = [
                    'inicio' => $fechaInicioGanar,
                    'fin' => $fechaFinGanar,
                    'label' => $esSemanaActualPorDefecto ? 'Semana actual' : 'Semanal',
                ];
            } else {
                $fechaInicioGanar = (string)($rangoGanar['inicio'] ?? $fechaInicio);
                $fechaFinGanar = (string)($rangoGanar['fin'] ?? $fechaFin);
            }

            return [
                'fecha_referencia' => $fechaReferencia,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'fecha_inicio_ganar' => $fechaInicioGanar,
                'fecha_fin_ganar' => $fechaFinGanar,
                'rango_ganar' => $rangoGanar,
                'usar_rango_personalizado' => false,
                'fecha_inicio_filtro' => '',
                'fecha_fin_filtro' => '',
                'semana_vencida_por_defecto' => $semanaVencidaPorDefecto,
            ];
        }

        $fechaReferencia = $fechaReferenciaExplicita;
        if ($fechaReferencia === '') {
            $fechaReferencia = date('Y-m-d');
        }
        [$fechaInicio, $fechaFin] = $this->calcularRangoSemanaDomingoADomingo($fechaReferencia);

        $usarRangoPersonalizado = ($fechaInicioPersonalizada !== '' && $fechaFinPersonalizada !== '');
        if ($usarRangoPersonalizado) {
            $fechaInicio = $fechaInicioPersonalizada;
            $fechaFin = $fechaFinPersonalizada;
            $fechaReferencia = $fechaFinPersonalizada;
        }

        $rangoGanar = $this->construirRangoGanar($fechaReferencia, $escalaGanar);
        $fechaInicioGanar = (string)($rangoGanar['inicio'] ?? $fechaInicio);
        $fechaFinGanar = (string)($rangoGanar['fin'] ?? $fechaFin);
        if ($usarRangoPersonalizado) {
            $fechaInicioGanar = $fechaInicio;
            $fechaFinGanar = $fechaFin;
            $rangoGanar = [
                'inicio' => $fechaInicioGanar,
                'fin' => $fechaFinGanar,
                'label' => 'Personalizado',
            ];
        }

        return [
            'fecha_referencia' => $fechaReferencia,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'fecha_inicio_ganar' => $fechaInicioGanar,
            'fecha_fin_ganar' => $fechaFinGanar,
            'rango_ganar' => $rangoGanar,
            'usar_rango_personalizado' => $usarRangoPersonalizado,
            'fecha_inicio_filtro' => $fechaInicioPersonalizada,
            'fecha_fin_filtro' => $fechaFinPersonalizada,
            'semana_vencida_por_defecto' => false,
        ];
    }

    private function normalizarAnioMeta($anio, $anioFallback) {
        $anio = (int)$anio;
        if ($anio >= 2000 && $anio <= 2100) {
            return $anio;
        }
        return (int)$anioFallback;
    }

    /**
     * Metas semana/mes/año del dashboard desde ministerio_meta (configuración del ministerio).
     */
    private function construirMetasDashboardDesdeConfiguracion(array $idsMinisterios, $anioReferencia) {
        $idsMinisterios = array_values(array_unique(array_filter(array_map('intval', $idsMinisterios), static function($id) {
            return $id > 0;
        })));

        if (empty($idsMinisterios)) {
            return [];
        }

        $anioReferencia = (int)$anioReferencia;
        if ($anioReferencia < 2000 || $anioReferencia > 2100) {
            $anioReferencia = (int)date('Y');
        }

        $metasDetalle = $this->ministerioModel->getMetasDetalleByMinisterioIds($idsMinisterios);
        $resultado = [];

        foreach ($idsMinisterios as $idMinisterio) {
            $metaData = (array)($metasDetalle[$idMinisterio] ?? []);
            $metaAnual = max(0, (int)($metaData['meta_anual'] ?? 0));
            $metaMensual = max(0, (int)($metaData['meta_mensual'] ?? 0));
            $metaSemanal = max(0, (int)($metaData['meta_semanal'] ?? 0));
            $anioMeta = $this->normalizarAnioMeta($metaData['anio_meta'] ?? 0, $anioReferencia);

            if ($metaAnual <= 0) {
                $metaAnual = max(0, (int)(($metaData['meta_ganados_s1'] ?? 0) + ($metaData['meta_ganados_s2'] ?? 0)));
            }
            if ($metaMensual <= 0 && $metaAnual > 0) {
                $metaMensual = (int)round($metaAnual / 12);
            }
            if ($metaSemanal <= 0 && $metaAnual > 0) {
                $inicioAnio = new DateTime($anioMeta . '-01-01');
                $finAnio = new DateTime($anioMeta . '-12-31');
                $diasAnio = (int)$inicioAnio->diff($finAnio)->days + 1;
                $semanasAnio = max(1, (int)ceil($diasAnio / 7));
                $metaSemanal = (int)ceil($metaAnual / $semanasAnio);
            }

            $resultado[$idMinisterio] = [
                'meta_anual' => $metaAnual,
                'meta_mensual' => $metaMensual,
                'meta_semanal' => $metaSemanal,
                'anio_meta' => $anioMeta,
                'meta_ganados_s1' => max(0, (int)($metaData['meta_ganados_s1'] ?? 0)),
                'meta_ganados_s2' => max(0, (int)($metaData['meta_ganados_s2'] ?? 0)),
            ];
        }

        return $resultado;
    }

    private function calcularEstadoDashboardMeta($porcentaje) {
        $porcentaje = (float)$porcentaje;
        if ($porcentaje >= 100) {
            return ['key' => 'verde', 'label' => 'Cumplida', 'color' => '#1f9d55'];
        }
        if ($porcentaje >= 85) {
            return ['key' => 'amarillo', 'label' => 'Muy cerca', 'color' => '#d9a600'];
        }
        if ($porcentaje >= 40) {
            return ['key' => 'naranja', 'label' => 'En avance', 'color' => '#f08c00'];
        }
        return ['key' => 'rojo', 'label' => 'Crítico', 'color' => '#d64545'];
    }

    /**
     * Semáforo para semanas sin registrar célula.
     * verde: 0-1 semanas · naranja: 2-3 · rojo: 4+
     */
    private function resolverSemaforoSemanasSinRegistrar(int $semanas): string {
        if ($semanas <= 1) {
            return 'verde';
        }
        if ($semanas <= 3) {
            return 'naranja';
        }
        return 'rojo';
    }

    private function etiquetaSemaforoSemanasSinRegistrar(string $semaforo): string {
        $map = [
            'verde' => 'Al día',
            'naranja' => 'Atención',
            'rojo' => 'Crítico',
        ];
        return $map[$semaforo] ?? '—';
    }

    private function contarGanadosPorMinisterioEnRango(array $personas, $inicio, $fin, array $ministerioIdsPermitidos = []) {
        $resultado = [];
        $idsPermitidos = [];
        foreach ($ministerioIdsPermitidos as $idPermitido) {
            $idsPermitidos[(int)$idPermitido] = true;
        }

        foreach ($personas as $persona) {
            // Solo contar personas nuevas para Ganar.
            if (!$this->esPersonaNueva($persona)) {
                continue;
            }

            $idMinisterio = (int)($persona['Id_Ministerio'] ?? 0);
            if ($idMinisterio <= 0) {
                continue;
            }
            if (!empty($idsPermitidos) && !isset($idsPermitidos[$idMinisterio])) {
                continue;
            }

            $fechaRegistro = substr((string)($persona['Fecha_Registro'] ?? ''), 0, 10);
            if ($fechaRegistro === '' || $fechaRegistro < $inicio || $fechaRegistro > $fin) {
                continue;
            }

            if (!isset($resultado[$idMinisterio])) {
                $resultado[$idMinisterio] = 0;
            }
            $resultado[$idMinisterio]++;
        }

        return $resultado;
    }

    /**
     * Resumen GANAR de la semana pasada (lun–dom) por líder.
     *
     * @return array{
     *   inicio:string,
     *   fin:string,
     *   rows:array<int,array<string,mixed>>,
     *   totales:array{gi:int,gc:int,uc:int,visita_fono:int,total:int,fv:int,v:int}
     * }
     */
    private function construirResumenSemanalGanarPorLider(
        array $personas,
        array $lideresVisibles,
        array $ministerioNombreMap = [],
        array $liderSuperiorPorId = []
    ): array {
        [$inicio, $fin] = $this->calcularRangoSemanaVencida();

        $filaVacia = static function(): array {
            return [
                'gi' => 0,
                'gc' => 0,
                'uc' => 0,
                'fv' => 0,
                'v' => 0,
                'visita_fono' => 0,
                'total' => 0,
            ];
        };

        $rowsMap = [];
        foreach ($lideresVisibles as $lider) {
            $id = (int)($lider['Id_Persona'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $nombre = trim((string)($lider['Nombre_Completo'] ?? ''));
            if ($nombre === '') {
                $nombre = trim((string)($lider['Nombre_Lider'] ?? ''));
            }
            $idMinisterio = (int)($lider['Id_Ministerio'] ?? 0);
            $nombreMinisterio = trim((string)($ministerioNombreMap[$idMinisterio] ?? ''));
            if ($nombreMinisterio === '') {
                $nombreMinisterio = trim((string)($lider['Nombre_Ministerio'] ?? ''));
            }
            $rowsMap[$id] = array_merge([
                'id_lider' => $id,
                'lider' => $nombre !== '' ? $nombre : 'Sin líder',
                'id_ministerio' => $idMinisterio,
                'ministerio' => $nombreMinisterio !== '' ? $nombreMinisterio : 'Sin ministerio',
            ], $filaVacia());
        }

        if ($rowsMap === []) {
            return [
                'inicio' => $inicio,
                'fin' => $fin,
                'rows' => [],
                'totales' => ['gi' => 0, 'gc' => 0, 'uc' => 0, 'fv' => 0, 'v' => 0, 'visita_fono' => 0, 'total' => 0],
            ];
        }

        foreach ($personas as $persona) {
            if (!$this->esPersonaNueva($persona)) {
                continue;
            }

            $fechaRegistro = substr(trim((string)($persona['Fecha_Registro'] ?? '')), 0, 10);
            if ($fechaRegistro === '' || $fechaRegistro < $inicio || $fechaRegistro > $fin) {
                continue;
            }

            $idLiderPersona = (int)($persona['Id_Lider'] ?? 0);
            if ($idLiderPersona <= 0) {
                continue;
            }

            $idLider = $this->resolverLiderPrincipal12ParaResumen($idLiderPersona, $rowsMap, $liderSuperiorPorId);
            if ($idLider <= 0 || !isset($rowsMap[$idLider])) {
                continue;
            }

            $origen = $this->clasificarOrigenGanar($persona);
            if ($origen === 'iglesia') {
                $rowsMap[$idLider]['gi']++;
            } elseif ($origen === 'celula') {
                $rowsMap[$idLider]['gc']++;
            }

            if ($this->personaUbicadaEnCelulaGanar($persona)) {
                $rowsMap[$idLider]['uc']++;
            }

            $proceso = $this->normalizarProcesoValor($persona['Proceso'] ?? '');
            $checklist = $this->obtenerChecklist($persona);
            $tieneFv = $this->peldanoMarcado($checklist, 'Ganar', 2, $proceso);
            $tieneV = $this->peldanoMarcado($checklist, 'Ganar', 3, $proceso);
            if ($tieneFv) {
                $rowsMap[$idLider]['fv']++;
            }
            if ($tieneV) {
                $rowsMap[$idLider]['v']++;
            }
            if ($tieneFv || $tieneV) {
                $rowsMap[$idLider]['visita_fono']++;
            }

            $rowsMap[$idLider]['total']++;
        }

        $rows = array_values($rowsMap);
        usort($rows, static function(array $a, array $b): int {
            $cmpMin = strcasecmp((string)($a['ministerio'] ?? ''), (string)($b['ministerio'] ?? ''));
            if ($cmpMin !== 0) {
                return $cmpMin;
            }
            return strcasecmp((string)($a['lider'] ?? ''), (string)($b['lider'] ?? ''));
        });

        $totales = ['gi' => 0, 'gc' => 0, 'uc' => 0, 'fv' => 0, 'v' => 0, 'visita_fono' => 0, 'total' => 0];
        foreach ($rows as $row) {
            foreach (array_keys($totales) as $k) {
                $totales[$k] += (int)($row[$k] ?? 0);
            }
        }

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'rows' => $rows,
            'totales' => $totales,
        ];
    }

    /**
     * Sube la cadena de liderazgo hasta encontrar un líder principal de 12 del resumen.
     */
    private function resolverLiderPrincipal12ParaResumen(int $idLider, array $rowsMap, array $liderSuperiorPorId): int {
        $visitados = [];
        $actual = $idLider;

        while ($actual > 0 && !isset($visitados[$actual])) {
            $visitados[$actual] = true;
            if (isset($rowsMap[$actual])) {
                return $actual;
            }
            $actual = (int)($liderSuperiorPorId[$actual] ?? 0);
        }

        return 0;
    }

    private function construirMapaSuperiorLideres(string $filtroRol): array {
        $lideres = $this->personaModel->getResumenLideresCelulaWithRole($filtroRol);
        $map = [];
        foreach ((array)$lideres as $lider) {
            $id = (int)($lider['Id_Persona'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $map[$id] = (int)($lider['Id_Lider'] ?? 0);
        }

        return $map;
    }

    /**
     * Líderes principales de 12 configurados por ministerio (máx. 2 por ministerio).
     */
    private function construirLideresPrincipalesResumenSemanalGanar(array $idsMinisteriosVisibles, $filtroLider = ''): array {
        if ($idsMinisteriosVisibles === []) {
            return [];
        }

        $principalesPorMinisterio = $this->ministerioModel->getLideresPrincipalesByMinisterioIds($idsMinisteriosVisibles);
        $idsPorMinisterio = [];
        foreach ($principalesPorMinisterio as $idMinisterio => $cfg) {
            $idMin = (int)$idMinisterio;
            foreach (['id_lider_principal_1', 'id_lider_principal_2'] as $campo) {
                $idLider = (int)($cfg[$campo] ?? 0);
                if ($idLider > 0) {
                    $idsPorMinisterio[$idLider] = $idMin;
                }
            }
        }

        if ($idsPorMinisterio === []) {
            return [];
        }

        $resumen = $this->personaModel->getResumenLideresByIds(array_keys($idsPorMinisterio));
        $lideres = [];
        foreach ($idsPorMinisterio as $idLider => $idMinisterio) {
            $info = $resumen[$idLider] ?? [];
            $lideres[] = [
                'Id_Persona' => $idLider,
                'Nombre_Completo' => trim((string)($info['Nombre_Completo'] ?? '')),
                'Id_Ministerio' => $idMinisterio,
                'Nombre_Ministerio' => trim((string)($info['Nombre_Ministerio'] ?? '')),
            ];
        }

        if ($filtroLider !== '' && (int)$filtroLider > 0) {
            $idLiderFiltro = (int)$filtroLider;
            $lideres = array_values(array_filter(
                $lideres,
                static function(array $lider) use ($idLiderFiltro): bool {
                    return (int)($lider['Id_Persona'] ?? 0) === $idLiderFiltro;
                }
            ));
        }

        return $lideres;
    }

    private function construirDashboardMetasPorMinisterio(array $ministerios, array $metasDetalle, array $conteoSemana, array $conteoMes, array $conteoAnio, $fechaReferencia, array $conteoSemestre = []) {
        $timestampRef = strtotime((string)$fechaReferencia);
        if ($timestampRef === false) {
            $timestampRef = time();
        }

        [$semanaInicio, $semanaFin] = $this->calcularRangoSemanaDomingoADomingo(date('Y-m-d', $timestampRef));
        $mesInicio = date('Y-m-01', $timestampRef);
        $mesFin = date('Y-m-t', $timestampRef);
        $anioReferencia = (int)date('Y', $timestampRef);
        $semestreCtx = $this->obtenerContextoSemestre(date('Y-m-d', $timestampRef));
        $numSemestre = (int)($semestreCtx['numero_semestre'] ?? 1);
        $semestreInicio = (string)($semestreCtx['inicio'] ?? date('Y-m-d', $timestampRef));
        $semestreFin = (string)($semestreCtx['fin'] ?? date('Y-m-d', $timestampRef));

        $diasSemanaTranscurridos = (int)floor((strtotime(date('Y-m-d', $timestampRef)) - strtotime($semanaInicio)) / 86400) + 1;
        $diasSemanaTranscurridos = max(1, min(7, $diasSemanaTranscurridos));

        $diasMesTotal = (int)date('t', $timestampRef);
        $diasMesTranscurridos = (int)date('j', $timestampRef);

        $inicioSemestreTs = strtotime($semestreInicio);
        $finSemestreTs = strtotime($semestreFin);
        $diasSemestreTotal = ($inicioSemestreTs !== false && $finSemestreTs !== false)
            ? (int)floor(($finSemestreTs - $inicioSemestreTs) / 86400) + 1
            : 1;
        $fechaRefDiaSem = strtotime(date('Y-m-d', $timestampRef));
        if ($inicioSemestreTs !== false && $finSemestreTs !== false && $fechaRefDiaSem !== false) {
            if ($fechaRefDiaSem < $inicioSemestreTs) {
                $diasSemestreTranscurridos = 0;
            } elseif ($fechaRefDiaSem > $finSemestreTs) {
                $diasSemestreTranscurridos = $diasSemestreTotal;
            } else {
                $diasSemestreTranscurridos = (int)floor(($fechaRefDiaSem - $inicioSemestreTs) / 86400) + 1;
                $diasSemestreTranscurridos = max(1, min($diasSemestreTotal, $diasSemestreTranscurridos));
            }
        } else {
            $diasSemestreTranscurridos = 1;
        }

        $items = [];
        foreach ($ministerios as $ministerio) {
            $idMinisterio = (int)($ministerio['Id_Ministerio'] ?? 0);
            if ($idMinisterio <= 0) {
                continue;
            }

            $metaData = (array)($metasDetalle[$idMinisterio] ?? []);
            $metaAnual = max(0, (int)($metaData['meta_anual'] ?? 0));
            $metaMensual = max(0, (int)($metaData['meta_mensual'] ?? 0));
            $metaSemanal = max(0, (int)($metaData['meta_semanal'] ?? 0));
            $anioMeta = $this->normalizarAnioMeta($metaData['anio_meta'] ?? 0, $anioReferencia);

            if ($metaAnual <= 0) {
                $metaAnual = max(0, (int)(($metaData['meta_ganados_s1'] ?? 0) + ($metaData['meta_ganados_s2'] ?? 0)));
            }
            if ($metaMensual <= 0 && $metaAnual > 0) {
                $metaMensual = (int)round($metaAnual / 12);
            }
            if ($metaSemanal <= 0 && $metaAnual > 0) {
                $metaSemanal = (int)ceil($metaAnual / 52);
            }

            $metaS1 = max(0, (int)($metaData['meta_ganados_s1'] ?? 0));
            $metaS2 = max(0, (int)($metaData['meta_ganados_s2'] ?? 0));
            if ($metaAnual > 0 && ($metaS1 + $metaS2) !== $metaAnual) {
                [$metaS1, $metaS2] = Ministerio::distribuirMetaAnualEnSemestres($metaAnual, $anioMeta);
            }
            $metaSemestre = Ministerio::metaGanadosPorSemestre([
                'meta_ganados_s1' => $metaS1,
                'meta_ganados_s2' => $metaS2,
            ], $numSemestre);

            $logradoSemana = (int)($conteoSemana[$idMinisterio] ?? 0);
            $logradoMes = (int)($conteoMes[$idMinisterio] ?? 0);
            $logradoAnio = (int)($conteoAnio[$idMinisterio] ?? 0);
            $logradoSemestre = (int)($conteoSemestre[$idMinisterio] ?? 0);

            $porcentajeSemana = $metaSemanal > 0 ? round(($logradoSemana / $metaSemanal) * 100, 1) : 0;
            $porcentajeMes = $metaMensual > 0 ? round(($logradoMes / $metaMensual) * 100, 1) : 0;
            $porcentajeAnio = $metaAnual > 0 ? round(($logradoAnio / $metaAnual) * 100, 1) : 0;
            $porcentajeSemestre = $metaSemestre > 0 ? round(($logradoSemestre / $metaSemestre) * 100, 1) : 0;

            $esperadoSemana = $metaSemanal > 0 ? (int)round($metaSemanal * ($diasSemanaTranscurridos / 7)) : 0;
            $esperadoMes = $metaMensual > 0 ? (int)round($metaMensual * ($diasMesTranscurridos / max(1, $diasMesTotal))) : 0;

            $inicioAnioMeta = strtotime($anioMeta . '-01-01');
            $finAnioMeta = strtotime($anioMeta . '-12-31');
            $diasAnioTotal = (int)floor(($finAnioMeta - $inicioAnioMeta) / 86400) + 1;
            $fechaRefDia = strtotime(date('Y-m-d', $timestampRef));
            if ($anioReferencia < $anioMeta) {
                $diasAnioTranscurridos = 0;
            } elseif ($anioReferencia > $anioMeta) {
                $diasAnioTranscurridos = $diasAnioTotal;
            } else {
                $diasAnioTranscurridos = (int)floor(($fechaRefDia - $inicioAnioMeta) / 86400) + 1;
                $diasAnioTranscurridos = max(1, min($diasAnioTotal, $diasAnioTranscurridos));
            }
            $esperadoAnio = $metaAnual > 0 ? (int)round($metaAnual * ($diasAnioTranscurridos / max(1, $diasAnioTotal))) : 0;
            $esperadoSemestre = $metaSemestre > 0
                ? (int)round($metaSemestre * ($diasSemestreTranscurridos / max(1, $diasSemestreTotal)))
                : 0;

            $justoATiempoSemana = $logradoSemana >= $esperadoSemana;
            $justoATiempoMes = $logradoMes >= $esperadoMes;
            $justoATiempoAnio = $logradoAnio >= $esperadoAnio;
            $justoATiempoSemestre = $logradoSemestre >= $esperadoSemestre;

            $estadoSemana = $this->calcularEstadoDashboardMeta($porcentajeSemana);
            $estadoMes = $this->calcularEstadoDashboardMeta($porcentajeMes);
            $estadoAnio = $this->calcularEstadoDashboardMeta($porcentajeAnio);
            $estadoSemestre = $this->calcularEstadoDashboardMeta($porcentajeSemestre);

            // Si va justo a tiempo, no mostrar estado rojo aunque el % global todavía sea bajo.
            if ($justoATiempoSemana && (($estadoSemana['key'] ?? '') === 'rojo')) {
                $estadoSemana = ['key' => 'naranja', 'label' => 'En ritmo', 'color' => '#f08c00'];
            }
            if ($justoATiempoMes && (($estadoMes['key'] ?? '') === 'rojo')) {
                $estadoMes = ['key' => 'naranja', 'label' => 'En ritmo', 'color' => '#f08c00'];
            }
            if ($justoATiempoAnio && (($estadoAnio['key'] ?? '') === 'rojo')) {
                $estadoAnio = ['key' => 'naranja', 'label' => 'En ritmo', 'color' => '#f08c00'];
            }
            if ($justoATiempoSemestre && (($estadoSemestre['key'] ?? '') === 'rojo')) {
                $estadoSemestre = ['key' => 'naranja', 'label' => 'En ritmo', 'color' => '#f08c00'];
            }

            $items[] = [
                'id_ministerio' => $idMinisterio,
                'ministerio' => (string)($ministerio['Nombre_Ministerio'] ?? 'Sin ministerio'),
                'meta_anual' => $metaAnual,
                'meta_ganados_s1' => $metaS1,
                'meta_ganados_s2' => $metaS2,
                'semestre' => [
                    'meta' => $metaSemestre,
                    'logrado' => $logradoSemestre,
                    'porcentaje' => $porcentajeSemestre,
                    'esperado' => $esperadoSemestre,
                    'justo_a_tiempo' => $justoATiempoSemestre,
                    'estado' => $estadoSemestre,
                    'numero' => $numSemestre,
                    'inicio' => $semestreInicio,
                    'fin' => $semestreFin,
                ],
                'semana' => [
                    'meta' => $metaSemanal,
                    'logrado' => $logradoSemana,
                    'porcentaje' => $porcentajeSemana,
                    'esperado' => $esperadoSemana,
                    'justo_a_tiempo' => $justoATiempoSemana,
                    'estado' => $estadoSemana,
                ],
                'mes' => [
                    'meta' => $metaMensual,
                    'logrado' => $logradoMes,
                    'porcentaje' => $porcentajeMes,
                    'esperado' => $esperadoMes,
                    'justo_a_tiempo' => $justoATiempoMes,
                    'estado' => $estadoMes,
                ],
                'anio' => [
                    'meta' => $metaAnual,
                    'logrado' => $logradoAnio,
                    'porcentaje' => $porcentajeAnio,
                    'esperado' => $esperadoAnio,
                    'justo_a_tiempo' => $justoATiempoAnio,
                    'estado' => $estadoAnio,
                    'anio_meta' => $anioMeta,
                ],
            ];
        }

        return [
            'fecha_referencia' => date('Y-m-d', $timestampRef),
            'periodos' => [
                'semana' => ['inicio' => $semanaInicio, 'fin' => $semanaFin],
                'mes' => ['inicio' => $mesInicio, 'fin' => $mesFin],
                'semestre' => [
                    'inicio' => $semestreInicio,
                    'fin' => $semestreFin,
                    'numero' => $numSemestre,
                    'titulo' => (string)($semestreCtx['titulo'] ?? ('Semestre ' . $numSemestre)),
                ],
                'anio' => ['anio' => $anioReferencia],
            ],
            'items' => $items,
        ];
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
            if ($this->esMinisterioPastoral($nombreMinisterioLider)) {
                continue;
            }

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

    private function esMinisterioPastoral($nombreMinisterio): bool {
        $nombre = strtolower(trim((string)$nombreMinisterio));
        if ($nombre === '') {
            return false;
        }

        $nombre = strtr($nombre, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);

        return $nombre === 'pastoral' || strpos($nombre, 'ministerio pastoral') !== false;
    }

    private function filtrarMinisteriosSinPastoral(array $ministerios): array {
        return array_values(array_filter($ministerios, function($ministerio) {
            $nombre = (string)($ministerio['Nombre_Ministerio'] ?? $ministerio['Nombre_Ministerio_Lider'] ?? '');
            return !$this->esMinisterioPastoral($nombre);
        }));
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

    private function resolverCategoriaCelulaPorRedONombre($red, $nombreCelula) {
        $texto = $this->normalizarTexto(trim((string)$red) . ' ' . trim((string)$nombreCelula));

        if ($texto === '') {
            return 'sin_clasificar';
        }

        if (strpos($texto, 'kid') !== false || strpos($texto, 'nino') !== false || strpos($texto, 'nina') !== false) {
            return 'kids';
        }

        // En este reporte, rocas y teens se consolidan dentro de jóvenes.
        if (strpos($texto, 'joven') !== false || strpos($texto, 'roca') !== false || strpos($texto, 'teen') !== false) {
            return 'jovenes';
        }

        return 'sin_clasificar';
    }

    private function normalizarNombreRedReporteCelulas($red) {
        $redOriginal = trim((string)$red);
        if ($redOriginal === '') {
            return 'Sin red';
        }

        $redNorm = $this->normalizarTexto($redOriginal);
        if ($redNorm === '') {
            return 'Sin red';
        }

        // Unificación solicitada para el reporte: Teens y Rocas se consolidan en Jóvenes.
        if (strpos($redNorm, 'teen') !== false || strpos($redNorm, 'roca') !== false || strpos($redNorm, 'joven') !== false) {
            return 'Jóvenes';
        }

        if (strpos($redNorm, 'kid') !== false || strpos($redNorm, 'nino') !== false || strpos($redNorm, 'nina') !== false) {
            return 'Kids';
        }

        return $redOriginal;
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

        if (strpos($tipo, 'celula') !== false || strpos($tipo, 'célula') !== false || strpos($tipo, 'migrados') !== false) {
            return false;
        }

        return $tipo !== '';
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
        // El rango semanal recibido ya corresponde al contexto seleccionado en filtros.
        // Evitamos restar otra semana para no dejar el reporte corrido dos semanas atrás.
        $inicioAnterior = (string)$fechaInicioSemanaActual;
        $finAnterior = (string)$fechaFinSemanaActual;

        $resumen = $this->personaModel->getResumenGanadosFinSemanaAnteriorPorMinisterioWithRole(
            $inicioAnterior,
            $finAnterior,
            $filtroRol,
            $filtroMinisterio,
            $filtroLider
        );

        $detallePersonas = $this->personaModel->getDetalleGanadosFinSemanaAnteriorPorMinisterioWithRole(
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

        $detallesPorMinisterio = [];
        foreach ($detallePersonas as $itemDetalle) {
            $ministerio = trim((string)($itemDetalle['Nombre_Ministerio'] ?? ''));
            if ($ministerio === '') {
                $ministerio = 'Sin ministerio';
            }

            if (!isset($detallesPorMinisterio[$ministerio])) {
                $detallesPorMinisterio[$ministerio] = [];
            }
            $detallesPorMinisterio[$ministerio][] = $itemDetalle;
        }

        return [
            'inicio' => $inicioAnterior,
            'fin' => $finAnterior,
            'rows' => $rows,
            'totales' => $totales,
            'detalles' => $detallesPorMinisterio,
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

    private function esCelulaNueva($celula) {
        return (int)($celula['Es_Antiguo'] ?? 1) !== 1;
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

            $red = $this->normalizarNombreRedReporteCelulas($celula['Red'] ?? '');
            if (!isset($porRed[$red])) {
                $porRed[$red] = 0;
            }
            $porRed[$red]++;

            $fechaApertura = $celula['Fecha_Apertura'] ?? '';
            if ($this->esCelulaNueva($celula) && $this->fechaDentroDeRango($fechaApertura, $semestre['inicio'], $semestre['fin'])) {
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
            if (!$this->esCelulaNueva($celula)) {
                continue;
            }

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

    private function construirTablasSeguimientoCelulas(array $celulas, array $asistenciaCelulas, $fechaInicioSemana, $fechaReferencia = null) {
        $inicioSemana = trim((string)$fechaInicioSemana);
        $inicioSemanaTs = strtotime($inicioSemana);
        if ($inicioSemanaTs === false) {
            $inicioSemanaTs = strtotime(date('Y-m-d'));
        }

        $calcularInicioSemanaTs = static function(int $timestamp): int {
            $diaSemana = (int)date('N', $timestamp); // 1 lunes, 7 domingo
            $diasDesdeLunes = $diaSemana - 1;
            return strtotime('-' . $diasDesdeLunes . ' days', strtotime(date('Y-m-d', $timestamp)));
        };

        $inicioSemanaTs = $calcularInicioSemanaTs((int)$inicioSemanaTs);

        $anioReferencia = (int)date('Y', strtotime((string)$fechaReferencia ?: date('Y-m-d')));
        if ($anioReferencia <= 0) {
            $anioReferencia = (int)date('Y');
        }
        $inicioAnioTs = strtotime(sprintf('%04d-01-01', $anioReferencia));
        if ($inicioAnioTs === false) {
            $inicioAnioTs = strtotime(date('Y-01-01'));
        }

        $idsCelula = array_values(array_unique(array_filter(array_map(static function($item) {
            return (int)($item['Id_Celula'] ?? 0);
        }, $celulas), static function($id) {
            return $id > 0;
        })));

        $ultimasFechas = $this->asistenciaModel->getUltimaFechaReportePorCelula($idsCelula);
        $asistenciaMap = [];
        foreach ($asistenciaCelulas as $filaAsistencia) {
            $idCelula = (int)($filaAsistencia['Id_Celula'] ?? 0);
            if ($idCelula <= 0) {
                continue;
            }
            $asistenciaMap[$idCelula] = $filaAsistencia;
        }

        $rowsSeguimiento = [];
        $rowsEstado = [];
        $rowsLideresPorRedMap = [];

        foreach ($celulas as $celula) {
            $idCelula = (int)($celula['Id_Celula'] ?? 0);
            if ($idCelula <= 0) {
                continue;
            }

            $ministerio = trim((string)($celula['Nombre_Ministerio_Lider'] ?? ''));
            if ($ministerio === '') {
                $ministerio = 'Sin ministerio';
            }

            $lider = trim((string)($celula['Nombre_Lider'] ?? ''));
            if ($lider === '') {
                $lider = 'Sin líder';
            }

            $nombreCelula = trim((string)($celula['Nombre_Celula'] ?? ''));
            if ($nombreCelula === '') {
                $nombreCelula = 'Sin nombre';
            }

            $red = trim((string)($celula['Red'] ?? ''));
            if ($red === '') {
                $red = 'Sin red';
            }

            $filaAsistencia = $asistenciaMap[$idCelula] ?? [];
            $reportoSemana = (int)($filaAsistencia['Reuniones_Realizadas'] ?? 0) > 0;
            $entregoSobre = !empty($filaAsistencia['Entrego_Sobre']);

            $ultimaFechaReporteRaw = trim((string)($ultimasFechas[$idCelula] ?? ''));
            $ultimaFechaReporte = $ultimaFechaReporteRaw !== '' ? substr($ultimaFechaReporteRaw, 0, 10) : '';
            $ultimaFechaTs = $ultimaFechaReporte !== '' ? strtotime($ultimaFechaReporte) : false;
            $ultimaFechaReporteVisible = ($ultimaFechaTs !== false && $ultimaFechaTs >= $inicioAnioTs)
                ? $ultimaFechaReporte
                : '';

            $semanasSinRegistrar = 0;
            if ($ultimaFechaTs !== false) {
                // Contar por fronteras semanales evita falsos 0 cuando ya cambió de semana.
                $ultimaSemanaTs = $calcularInicioSemanaTs((int)$ultimaFechaTs);
                $baseTs = max($ultimaSemanaTs, $inicioAnioTs);
                $diffDias = (int)floor(($inicioSemanaTs - $baseTs) / 86400);
                $semanasSinRegistrar = $diffDias > 0 ? (int)floor($diffDias / 7) : 0;
            } else {
                $fechaApertura = trim((string)($celula['Fecha_Apertura'] ?? ''));
                $fechaApertura = $fechaApertura !== '' ? substr($fechaApertura, 0, 10) : '';
                $fechaAperturaTs = $fechaApertura !== '' ? strtotime($fechaApertura) : false;
                $aperturaSemanaTs = $fechaAperturaTs !== false ? $calcularInicioSemanaTs((int)$fechaAperturaTs) : false;
                $baseTs = $aperturaSemanaTs !== false ? max($aperturaSemanaTs, $inicioAnioTs) : $inicioAnioTs;
                $diffDiasApertura = (int)floor(($inicioSemanaTs - $baseTs) / 86400);
                $semanasSinRegistrar = $diffDiasApertura > 0 ? (int)floor($diffDiasApertura / 7) : 0;
            }

            $semaforoSeg = $this->resolverSemaforoSemanasSinRegistrar($semanasSinRegistrar);
            $rowsSeguimiento[] = [
                'ministerio' => $ministerio,
                'lider' => $lider,
                'red' => $red,
                'celula' => $nombreCelula,
                'ultima_fecha_reporte' => $ultimaFechaReporteVisible,
                'semanas_sin_registrar' => $semanasSinRegistrar,
                'semaforo' => $semaforoSeg,
                'semaforo_label' => $this->etiquetaSemaforoSemanasSinRegistrar($semaforoSeg),
            ];

            $rowsEstado[] = [
                'ministerio' => $ministerio,
                'lider' => $lider,
                'red' => $red,
                'celula' => $nombreCelula,
                'reportadas_semana' => $reportoSemana ? 1 : 0,
                'no_reportadas_semana' => $reportoSemana ? 0 : 1,
                'entregaron_sobre_sin_reportar' => ($entregoSobre && !$reportoSemana) ? 1 : 0,
                'reportaron_sin_entregar_sobre' => ($reportoSemana && !$entregoSobre) ? 1 : 0,
            ];

            $categoria = $this->resolverCategoriaCelulaPorRedONombre($red, $nombreCelula);
            $rowKey = $this->normalizarTexto($ministerio) . '|' . $this->normalizarTexto($red) . '|' . $this->normalizarTexto($lider);

            if (!isset($rowsLideresPorRedMap[$rowKey])) {
                $rowsLideresPorRedMap[$rowKey] = [
                    'ministerio' => $ministerio,
                    'red' => $red,
                    'lider' => $lider,
                    'celulas_jovenes' => 0,
                    'celulas_rocas' => 0,
                    'celulas_kids' => 0,
                    'celulas_sin_clasificar' => 0,
                    'total_celulas' => 0,
                ];
            }

            if ($categoria === 'jovenes') {
                $rowsLideresPorRedMap[$rowKey]['celulas_jovenes']++;
            } elseif ($categoria === 'rocas') {
                $rowsLideresPorRedMap[$rowKey]['celulas_rocas']++;
            } elseif ($categoria === 'kids') {
                $rowsLideresPorRedMap[$rowKey]['celulas_kids']++;
            } else {
                $rowsLideresPorRedMap[$rowKey]['celulas_sin_clasificar']++;
            }

            $rowsLideresPorRedMap[$rowKey]['total_celulas']++;
        }

        usort($rowsSeguimiento, static function($a, $b) {
            $cmpSemanas = (int)($b['semanas_sin_registrar'] ?? 0) <=> (int)($a['semanas_sin_registrar'] ?? 0);
            if ($cmpSemanas !== 0) {
                return $cmpSemanas;
            }
            $cmpMinisterio = strcmp((string)($a['ministerio'] ?? ''), (string)($b['ministerio'] ?? ''));
            if ($cmpMinisterio !== 0) {
                return $cmpMinisterio;
            }
            return strcmp((string)($a['lider'] ?? ''), (string)($b['lider'] ?? ''));
        });

        usort($rowsEstado, static function($a, $b) {
            $cmpMinisterio = strcmp((string)($a['ministerio'] ?? ''), (string)($b['ministerio'] ?? ''));
            if ($cmpMinisterio !== 0) {
                return $cmpMinisterio;
            }
            return strcmp((string)($a['celula'] ?? ''), (string)($b['celula'] ?? ''));
        });

        $rowsLideresPorRed = array_values($rowsLideresPorRedMap);
        usort($rowsLideresPorRed, static function($a, $b) {
            $cmpMinisterio = strcmp((string)($a['ministerio'] ?? ''), (string)($b['ministerio'] ?? ''));
            if ($cmpMinisterio !== 0) {
                return $cmpMinisterio;
            }

            $cmpRed = strcmp((string)($a['red'] ?? ''), (string)($b['red'] ?? ''));
            if ($cmpRed !== 0) {
                return $cmpRed;
            }

            $cmpTotal = (int)($b['total_celulas'] ?? 0) <=> (int)($a['total_celulas'] ?? 0);
            if ($cmpTotal !== 0) {
                return $cmpTotal;
            }

            return strcmp((string)($a['lider'] ?? ''), (string)($b['lider'] ?? ''));
        });

        $resumenLideresPorRedMap = [];
        foreach ($rowsLideresPorRed as $filaRedTipo) {
            $red = trim((string)($filaRedTipo['red'] ?? ''));
            if ($red === '') {
                $red = 'Sin red';
            }

            if (!isset($resumenLideresPorRedMap[$red])) {
                $resumenLideresPorRedMap[$red] = [
                    'red' => $red,
                    'jovenes' => [],
                    'kids' => [],
                    'sin_clasificar' => [],
                    'total_celulas' => 0,
                ];
            }

            $lider = trim((string)($filaRedTipo['lider'] ?? ''));
            if ($lider === '') {
                $lider = 'Sin líder';
            }

            $cantJovenes = (int)($filaRedTipo['celulas_jovenes'] ?? 0) + (int)($filaRedTipo['celulas_rocas'] ?? 0);
            $cantKids = (int)($filaRedTipo['celulas_kids'] ?? 0);
            $cantSinClasificar = (int)($filaRedTipo['celulas_sin_clasificar'] ?? 0);

            $resumenLideresPorRedMap[$red]['total_celulas'] += (int)($filaRedTipo['total_celulas'] ?? 0);

            if ($cantJovenes > 0) {
                $resumenLideresPorRedMap[$red]['jovenes'][] = [
                    'lider' => $lider,
                    'cantidad' => $cantJovenes,
                ];
            }

            if ($cantKids > 0) {
                $resumenLideresPorRedMap[$red]['kids'][] = [
                    'lider' => $lider,
                    'cantidad' => $cantKids,
                ];
            }

            if ($cantSinClasificar > 0) {
                $resumenLideresPorRedMap[$red]['sin_clasificar'][] = [
                    'lider' => $lider,
                    'cantidad' => $cantSinClasificar,
                ];
            }
        }

        $ordenarLideresResumen = static function($a, $b) {
            $cmpCantidad = (int)($b['cantidad'] ?? 0) <=> (int)($a['cantidad'] ?? 0);
            if ($cmpCantidad !== 0) {
                return $cmpCantidad;
            }
            return strcmp((string)($a['lider'] ?? ''), (string)($b['lider'] ?? ''));
        };

        foreach ($resumenLideresPorRedMap as &$filaResumenRed) {
            usort($filaResumenRed['jovenes'], $ordenarLideresResumen);
            usort($filaResumenRed['kids'], $ordenarLideresResumen);
            usort($filaResumenRed['sin_clasificar'], $ordenarLideresResumen);
        }
        unset($filaResumenRed);

        ksort($resumenLideresPorRedMap);
        $resumenLideresPorRed = array_values($resumenLideresPorRedMap);

        return [
            'seguimiento_lideres' => $rowsSeguimiento,
            'estado_celulas' => $rowsEstado,
            'lideres_por_red_tipo' => $rowsLideresPorRed,
            'resumen_lideres_por_red' => $resumenLideresPorRed,
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
        $ministeriosVisibles = $this->filtrarMinisteriosSinPastoral((array)$ministeriosVisibles);
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
            $metaAnual = 0;
            if (isset($metasDetalle[$id])) {
                $metaData = $metasDetalle[$id];
                $metaAnual = max(0, (int)($metaData['meta_anual'] ?? 0));
                if ($metaAnual <= 0) {
                    $metaAnual = max(0, (int)(($metaData['meta_ganados_s1'] ?? 0) + ($metaData['meta_ganados_s2'] ?? 0)));
                }

                $numSemestre = (int)($semestre['numero_semestre'] ?? 1);
                $meta = Ministerio::metaGanadosPorSemestre($metaData, $numSemestre);
                if ($meta <= 0 && $metaAnual > 0) {
                    [$metaS1, $metaS2] = Ministerio::distribuirMetaAnualEnSemestres(
                        $metaAnual,
                        (int)($metaData['anio_meta'] ?? (int)($semestre['anio'] ?? date('Y')))
                    );
                    $meta = $numSemestre === 2 ? $metaS2 : $metaS1;
                }
            }
            $rowsMap[$id] = [
                'ministerio' => (string)($ministerio['Nombre_Ministerio'] ?? 'Sin ministerio'),
                'meta' => $meta,
                'meta_anual' => $metaAnual,
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

            $origenGanado = $this->clasificarOrigenGanar($persona);
            if ($origenGanado === 'celula') {
                $rowsMap[$idMinisterioPersona]['meses'][$mesKey]['celula']++;
                $rowsMap[$idMinisterioPersona]['ganados']++;
            } elseif ($origenGanado === 'iglesia') {
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
    /**
     * Persona nueva ganada que ya tiene célula asignada en el padrón (Id_Celula > 0).
     */
    private function personaUbicadaEnCelulaGanar(array $persona): bool {
        return (int)($persona['Id_Celula'] ?? 0) > 0;
    }

    private function clasificarOrigenGanar(array $persona): string {
        $tipo = strtolower(trim((string)($persona['Tipo_Reunion'] ?? '')));
        $tipo = strtr($tipo, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
        if ($tipo === '' || strpos($tipo, 'migrados') !== false) {
            return 'otros';
        }
        if (strpos($tipo, 'celula') !== false) {
            return 'celula';
        }

        return 'iglesia';
    }

    /**
     * Identifica si la persona debe considerarse nueva para U.V según "Ganado en".
     * Incluye: Célula, Domingo, Somos Uno, Otro.
     * Excluye explícitamente Migrados.
     */
    private function esOrigenValidoUniversidadVida(array $persona, bool $soloNuevas = true): bool {
        if ($soloNuevas && !$this->esPersonaNueva($persona)) {
            return false;
        }

        $tipo = strtolower(trim((string)($persona['Tipo_Reunion'] ?? '')));
        if ($tipo === '' || strpos($tipo, 'migrados') !== false) {
            return false;
        }

        if (strpos($tipo, 'celula') !== false || strpos($tipo, 'célula') !== false || strpos($tipo, 'migrados') !== false) {
            return false;
        }

        return $tipo !== '';
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
     * - UC: Ubicados en célula (personas nuevas del año con Id_Celula asignada)
     *
     * Filas = meses, columnas = subcategorías.
     * Estructura devuelta:
     *   [titulo, anio, meses, columnas, rows[mes => [gi,gc,fv,v,total]], totales, detalles[col][mes][]]
     */
    private function construirTablaGanarMensual(array $personas, int $anio): array {
        $meses = $this->obtenerMesesAbreviados();
        $cols  = ['gi' => 'GI', 'gc' => 'GC', 'fv' => 'FV', 'v' => 'V', 'uc' => 'UC'];

        $rows    = [];
        $totales = ['gi' => 0, 'gc' => 0, 'fv' => 0, 'v' => 0, 'uc' => 0, 'total' => 0];
        $detalles = [];   // detalles[col][mes][]

        for ($m = 1; $m <= 12; $m++) {
            $rows[$m] = ['mes' => $meses[$m], 'gi' => 0, 'gc' => 0, 'fv' => 0, 'v' => 0, 'uc' => 0, 'total' => 0];
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

            if ($this->personaUbicadaEnCelulaGanar($persona)) {
                $rows[$mes]['uc']++;
                $totales['uc']++;
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

            if ($this->personaUbicadaEnCelulaGanar($persona)) {
                $detalles['uc'][$mes][] = $detalle;
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
                    // En tablas de Consolidar se permite histórico completo (nuevos + antiguos).
                    $marcado = $this->esOrigenValidoUniversidadVida($persona, false);
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

            if ($this->esOrigenValidoUniversidadVida($persona, false)) {
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
        if (!AuthController::esAdministrador() && !AuthController::puede('reportes:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
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
        if (!AuthController::esAdministrador() && !AuthController::puede('reportes:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $tipoReporte = $this->resolverTipoReporte($_GET['tipo'] ?? 'personas');
        $escalaGanar = $this->resolverEscalaGanar($_GET['escala_ganar'] ?? 'semanal');
        $rangosFecha = $this->resolverRangosFechaReporte($tipoReporte, $escalaGanar);
        $fechaReferencia = (string)$rangosFecha['fecha_referencia'];
        $fechaInicio = (string)$rangosFecha['fecha_inicio'];
        $fechaFin = (string)$rangosFecha['fecha_fin'];
        $fechaInicioGanar = (string)$rangosFecha['fecha_inicio_ganar'];
        $fechaFinGanar = (string)$rangosFecha['fecha_fin_ganar'];
        $rangoGanar = (array)$rangosFecha['rango_ganar'];
        $usarRangoPersonalizado = (bool)$rangosFecha['usar_rango_personalizado'];
        $fechaInicioPersonalizada = (string)$rangosFecha['fecha_inicio_filtro'];
        $fechaFinPersonalizada = (string)$rangosFecha['fecha_fin_filtro'];
        $semanaVencidaPorDefecto = (bool)$rangosFecha['semana_vencida_por_defecto'];

        $filtroCelula = $_GET['celula'] ?? '';
        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';
        $filtroMesMeta = $_GET['mes_meta'] ?? '';
        $rangoEscalera = $this->construirRangoMesCalendario($_GET['mes_escalera'] ?? '');
        $mesEscalera = (string)($rangoEscalera['mes'] ?? date('Y-m'));
        $fechaInicioEscalera = (string)($rangoEscalera['inicio'] ?? date('Y-m-01'));
        $fechaFinEscalera = (string)($rangoEscalera['fin'] ?? date('Y-m-t'));

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

        $anioGanar = (int)substr((string)$fechaFinGanar, 0, 4);
        if ($anioGanar < 2020 || $anioGanar > ((int)date('Y') + 1)) {
            $anioGanar = (int)date('Y');
        }
        $fechaInicioAnioGanar = sprintf('%04d-01-01', $anioGanar);
        $fechaFinAnioGanar = sprintf('%04d-12-31', $anioGanar);
        $almasGanadasAnio = $this->personaModel->getAlmasGanadasPorMinisterioWithRole(
            $fechaInicioAnioGanar,
            $fechaFinAnioGanar,
            $filtroRol,
            $filtroMinisterio,
            $filtroLider
        );

        $ganadosAnioHombres = 0;
        $ganadosAnioMujeres = 0;
        foreach ($almasGanadasAnio as $filaAnioGanar) {
            $ganadosAnioHombres += (int)($filaAnioGanar['Hombres'] ?? 0) + (int)($filaAnioGanar['Jovenes_Hombres'] ?? 0);
            $ganadosAnioMujeres += (int)($filaAnioGanar['Mujeres'] ?? 0) + (int)($filaAnioGanar['Jovenes_Mujeres'] ?? 0);
        }

        $resumenOrigenGanados = $this->personaModel->getResumenGanadosOrigenWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, $filtroMinisterio, $filtroLider);
        $detalleOrigenGanados = [
            'celula' => $this->personaModel->getDetalleGanadosOrigenWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, 'celula', $filtroMinisterio, $filtroLider),
            'iglesia' => $this->personaModel->getDetalleGanadosOrigenWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, 'iglesia', $filtroMinisterio, $filtroLider),
            'asignados' => $this->personaModel->getDetalleGanadosOrigenWithRole($fechaInicioGanar, $fechaFinGanar, $filtroRol, 'asignados', $filtroMinisterio, $filtroLider),
            'hombres_anio' => $this->personaModel->getDetalleGanadosGeneroWithRole($fechaInicioAnioGanar, $fechaFinAnioGanar, $filtroRol, 'hombres', $filtroMinisterio, $filtroLider),
            'mujeres_anio' => $this->personaModel->getDetalleGanadosGeneroWithRole($fechaInicioAnioGanar, $fechaFinAnioGanar, $filtroRol, 'mujeres', $filtroMinisterio, $filtroLider),
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

        // KPI anual por género basado en el mismo conjunto de datos de tablas anuales.
        $ganadosAnioHombres = 0;
        $ganadosAnioMujeres = 0;
        $ganadosAnioSinGenero = 0;
        foreach ($personasAnioMinisterial as $personaAnual) {
            if (!$this->esPersonaNueva($personaAnual)) {
                continue;
            }

            $generoNormalizado = strtolower(trim((string)($personaAnual['Genero'] ?? '')));
            if (strpos($generoNormalizado, 'mujer') !== false) {
                $ganadosAnioMujeres++;
            } elseif (strpos($generoNormalizado, 'hombre') !== false) {
                $ganadosAnioHombres++;
            } else {
                $ganadosAnioSinGenero++;
            }
        }

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

        $ministeriosDashboardMetas = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);
        $ministeriosDashboardMetas = $this->filtrarMinisteriosSinPastoral((array)$ministeriosDashboardMetas);
        if ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) {
            $idFiltroMeta = (int)$filtroMinisterio;
            $ministeriosDashboardMetas = array_values(array_filter($ministeriosDashboardMetas, static function($item) use ($idFiltroMeta) {
                return (int)($item['Id_Ministerio'] ?? 0) === $idFiltroMeta;
            }));
        }
        $idsDashboardMetas = array_values(array_filter(array_map(static function($item) {
            return (int)($item['Id_Ministerio'] ?? 0);
        }, $ministeriosDashboardMetas), static function($id) {
            return $id > 0;
        }));

        $idMinisterioFiltro = ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null;
        $idLiderFiltro = ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null;

        // Meta en Escuelas: 6 inscritos por cada célula abierta del ministerio.
        $celulasParaMeta = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas, $idMinisterioFiltro, $idLiderFiltro);
        $conteoCelulasAbiertas = [];
        foreach ((array)$celulasParaMeta as $celulaMeta) {
            $idMin = (int)($celulaMeta['Id_Ministerio_Lider'] ?? 0);
            if ($idMin <= 0) {
                continue;
            }

            $estadoCelula = strtolower(trim((string)($celulaMeta['Estado_Celula'] ?? 'Activa')));
            if ($estadoCelula !== '' && $estadoCelula !== 'activa') {
                continue;
            }

            if (!isset($conteoCelulasAbiertas[$idMin])) {
                $conteoCelulasAbiertas[$idMin] = 0;
            }
            $conteoCelulasAbiertas[$idMin]++;
        }

        $metasDashboardDetalle = [];
        foreach ($idsDashboardMetas as $idMetaMin) {
            $idMetaMin = (int)$idMetaMin;
            $cantidadCelulasAbiertas = (int)($conteoCelulasAbiertas[$idMetaMin] ?? 0);
            $metaMensual = $cantidadCelulasAbiertas * 6;
            $metaSemanal = $metaMensual > 0 ? (int)ceil($metaMensual / 4) : 0;
            $metaAnual = $metaMensual * 12;

            $metasDashboardDetalle[$idMetaMin] = [
                'meta_anual' => $metaAnual,
                'meta_mensual' => $metaMensual,
                'meta_semanal' => $metaSemanal,
                'anio_meta' => (int)substr((string)$fechaReferencia, 0, 4),
            ];
        }

        $personasVisibles = $this->personaModel->getWithFiltersAndRole(
            $filtroRol,
            ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null,
            ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null,
            null,
            null,
            ($filtroCelulaGanar !== '') ? (string)$filtroCelulaGanar : null,
            null,
            null,
            null,
            null
        );

        $rangoSemanaDashboard = $this->calcularRangoSemanaDomingoADomingo($fechaReferencia);
        $mesDashboard = $this->construirRangoMesCalendario(substr((string)$fechaReferencia, 0, 7));
        $anioDashboard = (int)substr((string)$fechaReferencia, 0, 4);
        if ($anioDashboard < 2000 || $anioDashboard > 2100) {
            $anioDashboard = (int)date('Y');
        }

        $conteoSemanaDashboard = $this->contarGanadosPorMinisterioEnRango(
            $personasVisibles,
            (string)($rangoSemanaDashboard[0] ?? date('Y-m-d')),
            (string)($rangoSemanaDashboard[1] ?? date('Y-m-d')),
            $idsDashboardMetas
        );
        $conteoMesDashboard = $this->contarGanadosPorMinisterioEnRango(
            $personasVisibles,
            (string)($mesDashboard['inicio'] ?? date('Y-m-01')),
            (string)($mesDashboard['fin'] ?? date('Y-m-t')),
            $idsDashboardMetas
        );
        $conteoAnioDashboard = $this->contarGanadosPorMinisterioEnRango(
            $personasVisibles,
            sprintf('%04d-01-01', $anioDashboard),
            sprintf('%04d-12-31', $anioDashboard),
            $idsDashboardMetas
        );
        $dashboardMetasMinisterio = $this->construirDashboardMetasPorMinisterio(
            $ministeriosDashboardMetas,
            $metasDashboardDetalle,
            $conteoSemanaDashboard,
            $conteoMesDashboard,
            $conteoAnioDashboard,
            $fechaReferencia
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

        $celulasSeguimientoBase = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas, $filtroMinisterio, $filtroLider);
        if ($filtroCelula !== '') {
            if ((string)$filtroCelula === '0') {
                $celulasSeguimientoBase = [];
            } else {
                $idCelulaSeguimiento = (int)$filtroCelula;
                $celulasSeguimientoBase = array_values(array_filter($celulasSeguimientoBase, static function($item) use ($idCelulaSeguimiento) {
                    return (int)($item['Id_Celula'] ?? 0) === $idCelulaSeguimiento;
                }));
            }
        }
        $tablasSeguimientoCelulas = $this->construirTablasSeguimientoCelulas($celulasSeguimientoBase, $asistenciaCelulas, $fechaInicio, $fechaReferencia);

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
        $tablaEscuelasUvMinisterioGenero = $this->escuelaInscripcionModel->getResumenUvPorMinisterioGenero(
            ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null,
            ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null
        );

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
            'semana_vencida_por_defecto' => $semanaVencidaPorDefecto,
            'celulas_disponibles' => $celulasDisponibles,
            'ministerios_disponibles' => $opcionesFiltro['ministerios_disponibles'],
            'lideres_disponibles' => $opcionesFiltro['lideres_disponibles'],
            'almas_ganadas' => $almasGanadas,
            'ganar_anio_referencia' => $anioGanar,
            'ganar_anio_hombres' => $ganadosAnioHombres,
            'ganar_anio_mujeres' => $ganadosAnioMujeres,
            'ganar_anio_sin_genero' => $ganadosAnioSinGenero,
            'resumen_origen_ganados' => $resumenOrigenGanados,
            'detalle_origen_ganados' => $detalleOrigenGanados,
            'almas_por_edades' => $almasPorEdades,
            'proceso_ganar' => $procesoGanar,
            'reporte_escalera_mes_actual' => $reporteEscaleraMesActual,
            'asistencia_celulas' => $asistenciaCelulas,
            'cumplimiento_metas' => $cumplimientoMetas,
            'dashboard_metas_ministerio' => $dashboardMetasMinisterio,
            'indicadores_celulas' => $indicadoresCelulas,
            'tabla_seguimiento_lideres_celula' => $tablasSeguimientoCelulas['seguimiento_lideres'] ?? [],
            'tabla_estado_semanal_celulas' => $tablasSeguimientoCelulas['estado_celulas'] ?? [],
            'tabla_lideres_por_red_tipo' => $tablasSeguimientoCelulas['lideres_por_red_tipo'] ?? [],
            'tabla_resumen_lideres_por_red' => $tablasSeguimientoCelulas['resumen_lideres_por_red'] ?? [],
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
            'tabla_escuelas_uv_ministerio_genero' => $tablaEscuelasUvMinisterioGenero,
            'filtro_escuela_programa' => $filtroProgramaEscuelas,
            'filtro_escuela_buscar' => $filtroBusquedaEscuelas,
        ];

        $this->view('reportes/index', $data);
    }

    public function exportarExcel() {
        if (!AuthController::esAdministrador() && !AuthController::puede('reportes:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
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

    public function dashboardGanar() {
        if (!AuthController::esAdministrador() && !AuthController::puede('reportes:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $anio = (int)($_GET['anio'] ?? date('Y'));
        if ($anio < 2020 || $anio > ((int)date('Y') + 2)) {
            $anio = (int)date('Y');
        }

        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider      = $_GET['lider'] ?? '';

        $filtroRol        = DataIsolation::generarFiltroPersonas();
        $filtroCelulas    = DataIsolation::generarFiltroCelulas();
        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();

        $opcionesFiltro = $this->construirOpcionesFiltroMinisterioLider($filtroCelulas);
        $filtroMinisterio = ($filtroMinisterio !== '' && isset($opcionesFiltro['ministerio_ids_permitidos'][(int)$filtroMinisterio])) ? (int)$filtroMinisterio : '';
        $filtroLider      = ($filtroLider !== '' && isset($opcionesFiltro['lider_ids_permitidos'][(int)$filtroLider])) ? (int)$filtroLider : '';

        $fechaInicioAnio = sprintf('%04d-01-01', $anio);
        $fechaFinAnio    = sprintf('%04d-12-31', $anio);

        $idMinisterioFiltro = ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null;
        $idLiderFiltro      = ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null;

        $personasAnio = $this->personaModel->getWithFiltersAndRole(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            null,
            null,
            null,
            null,
            null,
            $fechaInicioAnio,
            $fechaFinAnio
        );

        // Totales anuales por mes (para gráfica de tendencia)
        $mesesLabels = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];
        $gananciasMensuales = array_fill(1, 12, ['celula' => 0, 'iglesia' => 0, 'total' => 0]);

        // Acumulado por ministerio
        $porMinisterioMap = [];

        // Datos por edades
        $porEdades = ['Kids' => 0, 'Teens' => 0, 'Rocas' => 0, 'Jovenes' => 0, 'Adultos' => 0, 'Adultos_Mayores' => 0, 'Sin_Dato' => 0];

        foreach ($personasAnio as $persona) {
            if (!$this->esPersonaNueva($persona)) {
                continue;
            }
            $fechaYmd = substr(trim((string)($persona['Fecha_Registro'] ?? '')), 0, 10);
            $ts = strtotime($fechaYmd);
            if ($ts === false || (int)date('Y', $ts) !== $anio) {
                continue;
            }
            $mes    = (int)date('n', $ts);
            $origen = $this->clasificarOrigenGanar($persona);
            $col    = ($origen === 'celula') ? 'celula' : 'iglesia';
            $gananciasMensuales[$mes][$col]++;
            $gananciasMensuales[$mes]['total']++;

            $ministerio = trim((string)($persona['Nombre_Ministerio'] ?? '')) ?: 'Sin ministerio';
            if (!isset($porMinisterioMap[$ministerio])) {
                $porMinisterioMap[$ministerio] = ['nombre' => $ministerio, 'total' => 0, 'celula' => 0, 'iglesia' => 0];
            }
            $porMinisterioMap[$ministerio]['total']++;
            $porMinisterioMap[$ministerio][$col]++;

            // Edades
            $edad = (int)($persona['Edad'] ?? 0);
            if ($edad >= 3 && $edad <= 8) {
                $porEdades['Kids']++;
            } elseif ($edad >= 9 && $edad <= 12) {
                $porEdades['Teens']++;
            } elseif ($edad >= 13 && $edad <= 17) {
                $porEdades['Rocas']++;
            } elseif ($edad >= 18 && $edad <= 30) {
                $porEdades['Jovenes']++;
            } elseif ($edad >= 31 && $edad <= 59) {
                $porEdades['Adultos']++;
            } elseif ($edad >= 60) {
                $porEdades['Adultos_Mayores']++;
            } else {
                $porEdades['Sin_Dato']++;
            }
        }

        // Totales semestrales
        $totalS1 = 0;
        $totalS2 = 0;
        $totalAnual = 0;
        for ($m = 1; $m <= 12; $m++) {
            $t = (int)$gananciasMensuales[$m]['total'];
            $totalAnual += $t;
            if ($m <= 6) {
                $totalS1 += $t;
            } else {
                $totalS2 += $t;
            }
        }

        // Semáforo: Verde 121-180, Amarillo 61-120, Rojo 1-60
        // Se aplica al total de cada semestre y al total anual
        $semaforoFn = static function(int $valor): string {
            if ($valor >= 121) {
                return 'verde';
            }
            if ($valor >= 61) {
                return 'amarillo';
            }
            return 'rojo';
        };

        $mesActual = (int)date('n');
        $mesesTranscurridosS1 = min(6, max(1, $mesActual));
        $mesesTranscurridosS2 = max(0, $mesActual - 6);

        // Cumplimiento de metas (semestre actual)
        $cumplimientoMetas = $this->construirTablaCumplimientoMetas(
            date('Y-m-d'),
            $filtroRol,
            $filtroMinisterios,
            $filtroMinisterio,
            $filtroLider,
            ''
        );

        $ministeriosDashboardMetas = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);
        $ministeriosDashboardMetas = $this->filtrarMinisteriosSinPastoral((array)$ministeriosDashboardMetas);
        if ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) {
            $idFiltroMeta = (int)$filtroMinisterio;
            $ministeriosDashboardMetas = array_values(array_filter($ministeriosDashboardMetas, static function($item) use ($idFiltroMeta) {
                return (int)($item['Id_Ministerio'] ?? 0) === $idFiltroMeta;
            }));
        }
        $idsDashboardMetas = array_values(array_filter(array_map(static function($item) {
            return (int)($item['Id_Ministerio'] ?? 0);
        }, $ministeriosDashboardMetas), static function($id) {
            return $id > 0;
        }));

        $metasDashboardDetalle = $this->construirMetasDashboardDesdeConfiguracion($idsDashboardMetas, $anio);

        $personasVisibles = $this->personaModel->getWithFiltersAndRole(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $fechaReferenciaDashboard = date('Y-m-d');
        $rangoSemanaDashboard = $this->calcularRangoSemanaDomingoADomingo($fechaReferenciaDashboard);
        $mesDashboard = $this->construirRangoMesCalendario(substr((string)$fechaReferenciaDashboard, 0, 7));
        $anioDashboard = (int)substr((string)$fechaReferenciaDashboard, 0, 4);

        $conteoSemanaDashboard = $this->contarGanadosPorMinisterioEnRango(
            $personasVisibles,
            (string)($rangoSemanaDashboard[0] ?? date('Y-m-d')),
            (string)($rangoSemanaDashboard[1] ?? date('Y-m-d')),
            $idsDashboardMetas
        );
        $conteoMesDashboard = $this->contarGanadosPorMinisterioEnRango(
            $personasVisibles,
            (string)($mesDashboard['inicio'] ?? date('Y-m-01')),
            (string)($mesDashboard['fin'] ?? date('Y-m-t')),
            $idsDashboardMetas
        );
        $conteoAnioDashboard = $this->contarGanadosPorMinisterioEnRango(
            $personasVisibles,
            sprintf('%04d-01-01', $anioDashboard),
            sprintf('%04d-12-31', $anioDashboard),
            $idsDashboardMetas
        );
        $semestreDashboard = $this->obtenerContextoSemestre($fechaReferenciaDashboard);
        $conteoSemestreDashboard = $this->contarGanadosPorMinisterioEnRango(
            $personasVisibles,
            (string)($semestreDashboard['inicio'] ?? sprintf('%04d-01-01', $anioDashboard)),
            (string)($semestreDashboard['fin'] ?? sprintf('%04d-12-31', $anioDashboard)),
            $idsDashboardMetas
        );
        $dashboardMetasMinisterio = $this->construirDashboardMetasPorMinisterio(
            $ministeriosDashboardMetas,
            $metasDashboardDetalle,
            $conteoSemanaDashboard,
            $conteoMesDashboard,
            $conteoAnioDashboard,
            $fechaReferenciaDashboard,
            $conteoSemestreDashboard
        );

        // Semáforo por ministerio: % de meta cumplida
        $porMinisterioConMeta = [];
        foreach ($cumplimientoMetas['rows'] ?? [] as $rowMeta) {
            $nombre    = (string)($rowMeta['ministerio'] ?? '');
            $meta      = (int)($rowMeta['meta'] ?? 0);
            $ganados   = (int)($rowMeta['ganados'] ?? 0);
            $pct       = $meta > 0 ? (int)round(($ganados / $meta) * 100) : 0;
            if ($pct >= 75) {
                $semaforo = 'verde';
            } elseif ($pct >= 40) {
                $semaforo = 'amarillo';
            } else {
                $semaforo = 'rojo';
            }
            $porMinisterioConMeta[] = [
                'ministerio' => $nombre,
                'meta'       => $meta,
                'meta_anual' => (int)($rowMeta['meta_anual'] ?? 0),
                'ganados'    => $ganados,
                'pendiente'  => (int)($rowMeta['pendiente'] ?? 0),
                'pct'        => $pct,
                'semaforo'   => $semaforo,
            ];
        }

        arsort($porMinisterioMap);

        // Tabla G12-GANAR: GI, GC, FV, V
        $tablaG12 = $this->construirTablaGanarMensual($personasAnio, $anio);
        $totalesG12 = $tablaG12['totales'] ?? ['gi' => 0, 'gc' => 0, 'fv' => 0, 'v' => 0, 'uc' => 0, 'total' => 0];

        $ministerioNombreMap = [];
        foreach ($ministeriosDashboardMetas as $ministerioMeta) {
            $idMinMap = (int)($ministerioMeta['Id_Ministerio'] ?? 0);
            if ($idMinMap <= 0) {
                continue;
            }
            $nombreMinMap = trim((string)($ministerioMeta['Nombre_Ministerio'] ?? ''));
            if ($nombreMinMap === '') {
                $nombreMinMap = trim((string)($ministerioMeta['ministerio'] ?? ''));
            }
            $ministerioNombreMap[$idMinMap] = $nombreMinMap !== '' ? $nombreMinMap : 'Sin ministerio';
        }

        $lideresDashboardResumen = $this->construirLideresPrincipalesResumenSemanalGanar(
            $idsDashboardMetas,
            $filtroLider
        );
        $liderSuperiorPorId = $this->construirMapaSuperiorLideres($filtroRol);

        $resumenSemanalLider = $this->construirResumenSemanalGanarPorLider(
            $personasVisibles,
            $lideresDashboardResumen,
            $ministerioNombreMap,
            $liderSuperiorPorId
        );

        $this->view('reportes/dashboard_ganar', [
            'anio'                   => $anio,
            'filtro_ministerio'      => (string)$filtroMinisterio,
            'filtro_lider'           => (string)$filtroLider,
            'ministerios_disponibles' => $opcionesFiltro['ministerios_disponibles'],
            'lideres_disponibles'    => $opcionesFiltro['lideres_disponibles'],
            'meses_labels'           => $mesesLabels,
            'ganancias_mensuales'    => $gananciasMensuales,
            'por_ministerio'         => array_values($porMinisterioMap),
            'por_edades'             => $porEdades,
            'total_s1'               => $totalS1,
            'total_s2'               => $totalS2,
            'total_anual'            => $totalAnual,
            'semaforo_s1'            => $semaforoFn($totalS1),
            'semaforo_s2'            => $semaforoFn($totalS2),
            'semaforo_anual'         => $semaforoFn($totalAnual),
            'cumplimiento_metas'     => $cumplimientoMetas,
            'dashboard_metas_ministerio' => $dashboardMetasMinisterio,
            'ministerios_con_meta'   => $porMinisterioConMeta,
            'totales_g12'            => $totalesG12,
            'resumen_semanal_lider' => $resumenSemanalLider,
        ]);
    }

    private function normalizarGeneroLider($generoRaw) {
        $genero = strtolower(trim((string)$generoRaw));
        if (strpos($genero, 'teen') !== false || strpos($genero, 'adolesc') !== false) {
            return 'Teen';
        }
        if (strpos($genero, 'joven') !== false || strpos($genero, 'j\u00f3ven') !== false) {
            return 'Joven';
        }
        if (strpos($genero, 'mujer') !== false || strpos($genero, 'femen') !== false || $genero === 'f') {
            return 'Mujer';
        }
        if (strpos($genero, 'hombre') !== false || strpos($genero, 'mascul') !== false || $genero === 'm') {
            return 'Hombre';
        }
        return 'Otro';
    }

    private function normalizarTextoComparable($valor) {
        $texto = strtolower(trim((string)$valor));
        if ($texto === '') {
            return '';
        }

        $map = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n'
        ];
        $texto = strtr($texto, $map);
        $texto = preg_replace('/\s+/', ' ', $texto);

        return trim((string)$texto);
    }

    private function construirClaveUnicaInscripcionDashboard(array $inscripcion, string $lineaPrograma = 'uv'): string {
        $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
        if ($lineaPrograma === 'cap' && $idPersona > 0) {
            require_once APP . '/Helpers/EscuelaFormacionResumenHelper.php';
            $nivel = EscuelaFormacionResumenHelper::resolverNivelCapacitacionDestino($inscripcion);

            return 'cap:' . $idPersona . ':' . $nivel;
        }

        $prefijoPrograma = '';
        if ($lineaPrograma === 'uv') {
            $programa = strtolower(trim((string)($inscripcion['Programa'] ?? '')));
            $prefijoPrograma = ($programa !== '' ? $programa : 'sin-programa') . ':';
        }

        if ($idPersona > 0) {
            return $prefijoPrograma . 'id:' . $idPersona;
        }

        $cedula = preg_replace('/\D+/', '', (string)($inscripcion['Cedula'] ?? ''));
        if ($cedula !== '') {
            return $prefijoPrograma . 'cc:' . $cedula;
        }

        $telefono = preg_replace('/\D+/', '', (string)($inscripcion['Telefono'] ?? ''));
        if ($telefono !== '') {
            return $prefijoPrograma . 'tel:' . $telefono;
        }

        $nombre = strtolower(trim((string)($inscripcion['Nombre'] ?? '')));
        if ($nombre !== '') {
            return $prefijoPrograma . 'nom:' . $nombre;
        }

        return $prefijoPrograma . 'ins:' . (int)($inscripcion['Id_Inscripcion'] ?? 0);
    }

    /**
     * Fusiona abonos/pagos de varias inscripciones de la misma persona en una sola fila de reporte.
     */
    private function fusionarPagosGrupoInscripcionesDashboard(array $grupo, array $elegida): array {
        $valorMax = (float)($elegida['Valor_Pago'] ?? 0);
        $metodo = trim((string)($elegida['Metodo_Pago'] ?? ''));
        $referencia = trim((string)($elegida['Referencia_Pago'] ?? ''));
        $tipoPago = strtolower(trim((string)($elegida['Tipo_Pago'] ?? '')));

        foreach ($grupo as $otra) {
            $valorMax = max($valorMax, (float)($otra['Valor_Pago'] ?? 0));

            $metodoOtro = trim((string)($otra['Metodo_Pago'] ?? ''));
            if ($metodoOtro !== '') {
                $metodo = $metodoOtro;
            }
            $refOtro = trim((string)($otra['Referencia_Pago'] ?? ''));
            if ($refOtro !== '') {
                $referencia = $refOtro;
            }

            $tipoOtro = strtolower(trim((string)($otra['Tipo_Pago'] ?? '')));
            if ($tipoOtro === 'completo') {
                $tipoPago = 'completo';
            } elseif ($tipoOtro === 'abono' && $tipoPago !== 'completo') {
                $tipoPago = 'abono';
            }
        }

        if ($valorMax > 0) {
            $elegida['Valor_Pago'] = $valorMax;
        }
        if ($metodo !== '') {
            $elegida['Metodo_Pago'] = $metodo;
        }
        if ($referencia !== '') {
            $elegida['Referencia_Pago'] = $referencia;
        }
        if ($tipoPago !== '') {
            $elegida['Tipo_Pago'] = $tipoPago;
        }

        return $elegida;
    }

    private function puntajePrioridadInscripcionDashboard(array $inscripcion): int {
        $puntaje = 0;
        if ((string)($inscripcion['Programa'] ?? '') === 'universidad_vida') {
            $puntaje += 1000;
        }
        if ((float)($inscripcion['Valor_Pago'] ?? 0) > 0) {
            $puntaje += 200;
        }
        $tipo = strtolower(trim((string)($inscripcion['Tipo_Pago'] ?? '')));
        if ($tipo === 'completo') {
            $puntaje += 120;
        } elseif ($tipo === 'abono') {
            $puntaje += 80;
        }
        if (trim((string)($inscripcion['Metodo_Pago'] ?? '')) !== '') {
            $puntaje += 40;
        }
        if (trim((string)($inscripcion['Referencia_Pago'] ?? '')) !== '') {
            $puntaje += 20;
        }

        return $puntaje;
    }

    private function deduplicarInscripcionesDashboard(array $inscripciones, string $lineaPrograma = 'uv'): array {
        $porClave = [];
        foreach ($inscripciones as $inscripcion) {
            $clave = $this->construirClaveUnicaInscripcionDashboard((array)$inscripcion, $lineaPrograma);
            if (!isset($porClave[$clave])) {
                $porClave[$clave] = [];
            }
            $porClave[$clave][] = $inscripcion;
        }

        $deduplicadas = [];
        foreach ($porClave as $grupo) {
            usort($grupo, function ($a, $b) {
                $cmpPrio = $this->puntajePrioridadInscripcionDashboard($b) <=> $this->puntajePrioridadInscripcionDashboard($a);
                if ($cmpPrio !== 0) {
                    return $cmpPrio;
                }

                $fa = (string)($a['Fecha_Registro'] ?? '');
                $fb = (string)($b['Fecha_Registro'] ?? '');
                if ($fa === $fb) {
                    return (int)($b['Id_Inscripcion'] ?? 0) <=> (int)($a['Id_Inscripcion'] ?? 0);
                }

                return strcmp($fb, $fa);
            });

            $elegida = $grupo[0];
            $elegida = $this->fusionarPagosGrupoInscripcionesDashboard($grupo, $elegida);

            $idsRelacionados = [];
            foreach ($grupo as $otraIns) {
                $idRel = (int)($otraIns['Id_Inscripcion'] ?? 0);
                if ($idRel > 0) {
                    $idsRelacionados[] = $idRel;
                }
            }
            if (!empty($idsRelacionados)) {
                $elegida['_ids_inscripcion_relacionadas'] = array_values(array_unique($idsRelacionados));
            }

            $deduplicadas[] = $elegida;
        }

        return $deduplicadas;
    }

    private function prepararMapaPagosMovimientosUv(array $inscripciones): void {
        $ids = [];
        $cedulas = [];
        foreach ($inscripciones as $inscripcion) {
            $idIns = (int)($inscripcion['Id_Inscripcion'] ?? 0);
            if ($idIns > 0) {
                $ids[] = $idIns;
            }
            $cedula = preg_replace('/\D+/', '', (string)($inscripcion['Cedula'] ?? ''));
            if ($cedula !== '') {
                $cedulas[] = $cedula;
            }
        }
        $this->mapaPagosMovimientosUv = $this->escuelaInscripcionModel->getMapaPagosMovimientosPorInscripcion($ids);
        $this->mapaPagosMovimientosUvPorCedula = $this->escuelaInscripcionModel->getMapaPagosMovimientosPorCedula($cedulas);
    }

    private function normalizarCedulaDashboard(string $cedula): string {
        return preg_replace('/\D+/', '', $cedula);
    }

    private function inscripcionTienePagoEnMovimientos(array $inscripcion): bool {
        if ($this->valorPagoMovimientosInscripcion($inscripcion) > 0) {
            return true;
        }

        $ids = [(int)($inscripcion['Id_Inscripcion'] ?? 0)];
        if (!empty($inscripcion['_ids_inscripcion_relacionadas']) && is_array($inscripcion['_ids_inscripcion_relacionadas'])) {
            foreach ($inscripcion['_ids_inscripcion_relacionadas'] as $idRel) {
                $ids[] = (int)$idRel;
            }
        }

        foreach (array_unique(array_filter($ids, static function ($id) {
            return $id > 0;
        })) as $idIns) {
            if (!empty($this->mapaPagosMovimientosUv[$idIns]['pagado'])) {
                return true;
            }
        }

        $cedula = $this->normalizarCedulaDashboard((string)($inscripcion['Cedula'] ?? ''));
        if ($cedula !== '' && !empty($this->mapaPagosMovimientosUvPorCedula[$cedula]['pagado'])) {
            return true;
        }

        return false;
    }

    private function inscripcionTienePagoEnFicha(array $inscripcion): bool {
        $tipoPago = strtolower(trim((string)($inscripcion['Tipo_Pago'] ?? '')));
        if (in_array($tipoPago, ['abono', 'completo'], true)) {
            return true;
        }

        $valorPago = (float)($inscripcion['Valor_Pago'] ?? 0);
        if ($valorPago > 0) {
            return true;
        }

        if (trim((string)($inscripcion['Metodo_Pago'] ?? '')) !== '') {
            return true;
        }

        if (trim((string)($inscripcion['Referencia_Pago'] ?? '')) !== '') {
            return true;
        }

        return false;
    }

    private function valorPagoMovimientosInscripcion(array $inscripcion): float {
        $valorMaxPorIds = 0.0;
        $ids = [(int)($inscripcion['Id_Inscripcion'] ?? 0)];
        if (!empty($inscripcion['_ids_inscripcion_relacionadas']) && is_array($inscripcion['_ids_inscripcion_relacionadas'])) {
            foreach ($inscripcion['_ids_inscripcion_relacionadas'] as $idRel) {
                $ids[] = (int)$idRel;
            }
        }

        foreach (array_unique(array_filter($ids, static function ($id) {
            return $id > 0;
        })) as $idIns) {
            $valorMaxPorIds = max(
                $valorMaxPorIds,
                (float)($this->mapaPagosMovimientosUv[$idIns]['valor'] ?? 0)
            );
        }

        $cedula = $this->normalizarCedulaDashboard((string)($inscripcion['Cedula'] ?? ''));
        $programa = trim((string)($inscripcion['Programa'] ?? ''));
        if ($cedula !== '' && $programa !== '') {
            $valorPrograma = $this->escuelaInscripcionModel->getTotalPagadoMovimientosPorCedulaPrograma($cedula, $programa);
            return max($valorMaxPorIds, $valorPrograma);
        }

        return $valorMaxPorIds;
    }

    /**
     * @return array{semestre: int, fecha_inicio: string, fecha_fin: string, etiqueta: string}
     */
    private function resolverRangoSemestreDashboardUv(int $anio, $semestreInput = null): array {
        $semestre = (int)$semestreInput;
        if ($semestre !== 1 && $semestre !== 2) {
            $semestre = ((int)date('n')) <= 6 ? 1 : 2;
        }

        if ($semestre === 1) {
            return [
                'semestre' => 1,
                'fecha_inicio' => sprintf('%04d-01-01', $anio),
                'fecha_fin' => sprintf('%04d-06-30', $anio),
                'etiqueta' => 'Semestre 1 (Enero – Junio)',
            ];
        }

        return [
            'semestre' => 2,
            'fecha_inicio' => sprintf('%04d-07-01', $anio),
            'fecha_fin' => sprintf('%04d-12-31', $anio),
            'etiqueta' => 'Semestre 2 (Julio – Diciembre)',
        ];
    }

    private function resolverNombreMinisterioInscripcionDashboard(array $inscripcion): string {
        $desdePersona = trim((string)($inscripcion['Nombre_Ministerio_Persona_Actual'] ?? ''));
        $desdeInscripcion = trim((string)($inscripcion['Nombre_Ministerio'] ?? ''));
        $nombre = $desdePersona !== '' ? $desdePersona : $desdeInscripcion;

        return $nombre === '' ? 'Sin ministerio' : $nombre;
    }

    private function resolverNombreLiderCelulaInscripcionDashboard(array $inscripcion): string {
        $desdePersona = trim((string)($inscripcion['Lider_Persona_Actual'] ?? ''));
        $desdeInscripcion = trim((string)($inscripcion['Lider'] ?? ''));
        $nombre = $desdePersona !== '' ? $desdePersona : $desdeInscripcion;

        return $nombre === '' ? 'Sin líder de célula' : $nombre;
    }

    private function resolverIdLiderCelulaInscripcionDashboard(array $inscripcion): int {
        return (int)($inscripcion['Id_Lider_Persona_Actual'] ?? 0);
    }

    private function claveAgrupacionLiderCelulaDashboard(array $inscripcion): string {
        $idLider = $this->resolverIdLiderCelulaInscripcionDashboard($inscripcion);
        if ($idLider > 0) {
            return 'id:' . $idLider;
        }

        $nombre = $this->resolverNombreLiderCelulaInscripcionDashboard($inscripcion);
        $norm = $this->normalizarTextoComparable($nombre);

        return $norm === '' ? 'sin-lider' : ('nom:' . $norm);
    }

    private function slugLiderCelulaDashboard(int $idLider, string $nombreLider): string {
        if ($idLider > 0) {
            return 'id-' . $idLider;
        }

        $slug = $this->slugMinisterioDashboard($nombreLider);

        return $slug === '' ? 'sin-lider-de-celula' : $slug;
    }

    private function inscripcionCoincideSlugLiderCelula(array $inscripcion, string $liderSlug): bool {
        $liderSlug = trim($liderSlug);
        if ($liderSlug === '') {
            return false;
        }

        $idLider = $this->resolverIdLiderCelulaInscripcionDashboard($inscripcion);
        $nombre = $this->resolverNombreLiderCelulaInscripcionDashboard($inscripcion);

        return $this->slugLiderCelulaDashboard($idLider, $nombre) === $liderSlug;
    }

    private function elegirNombreLiderMasCompleto(string $actual, string $candidato): string {
        $actual = trim($actual);
        $candidato = trim($candidato);
        if ($candidato === '') {
            return $actual;
        }
        if ($actual === '') {
            return $candidato;
        }

        return strlen($candidato) > strlen($actual) ? $candidato : $actual;
    }

    private function filtrarInscripcionesPorAislamientoDashboard(array $inscripciones, $filtroRol, ?int $idMinisterioFiltro, ?int $idLiderFiltro): array {
        if (DataIsolation::tieneAccesoTotal()) {
            return array_values($inscripciones);
        }

        $personasPermitidas = $this->personaModel->getWithFiltersAndRole(
            $filtroRol,
            ($idMinisterioFiltro !== null && $idMinisterioFiltro > 0) ? $idMinisterioFiltro : null,
            ($idLiderFiltro !== null && $idLiderFiltro > 0) ? $idLiderFiltro : null,
            null
        );

        $mapPermitidas = [];
        foreach ((array)$personasPermitidas as $personaPermitida) {
            $idPersonaPermitida = (int)($personaPermitida['Id_Persona'] ?? 0);
            if ($idPersonaPermitida > 0) {
                $mapPermitidas[$idPersonaPermitida] = true;
            }
        }

        if (empty($mapPermitidas)) {
            return [];
        }

        return array_values(array_filter($inscripciones, static function($inscripcion) use ($mapPermitidas) {
            $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
            return $idPersona > 0 && isset($mapPermitidas[$idPersona]);
        }));
    }

    /**
     * @return array<int, string>
     */
    private function programasConsultaModoConsolidar(string $lineaPrograma = 'uv'): array {
        if ($lineaPrograma === 'cap') {
            return [
                'capacitacion_destino',
                'capacitacion_destino_nivel_1',
                'capacitacion_destino_nivel_2',
                'capacitacion_destino_nivel_3',
            ];
        }

        return ['universidad_vida', 'encuentro'];
    }

    private function obtenerInscripcionesPublicasModoConsolidar(
        $filtroRol,
        ?int $idMinisterioFiltro,
        ?int $idLiderFiltro,
        ?string $fechaInicio = null,
        ?string $fechaFin = null,
        string $lineaPrograma = 'uv'
    ): array {
        $programasConsulta = $this->programasConsultaModoConsolidar($lineaPrograma);
        $inscripcionesPublicas = [];

        foreach ($programasConsulta as $programaConsulta) {
            // Misma base que Consolidar; tope ampliado para el dashboard.
            $inscripcionesPrograma = $this->escuelaInscripcionModel->getListado(
                $programaConsulta,
                '',
                5000,
                'todos',
                $idMinisterioFiltro,
                $idLiderFiltro,
                5000
            );

            foreach ((array)$inscripcionesPrograma as $inscripcionTmp) {
                $idIns = (int)($inscripcionTmp['Id_Inscripcion'] ?? 0);
                if ($idIns <= 0) {
                    continue;
                }

                $inscripcionesPublicas[$idIns] = $inscripcionTmp;
            }
        }

        $inscripcionesSinDedup = array_values($inscripcionesPublicas);
        $this->prepararMapaPagosMovimientosUv($inscripcionesSinDedup);
        $inscripcionesPublicas = $this->deduplicarInscripcionesDashboard($inscripcionesSinDedup, $lineaPrograma);
        $inscripcionesPublicas = array_values(array_filter($inscripcionesPublicas, static function($inscripcion) use ($programasConsulta) {
            return in_array((string)($inscripcion['Programa'] ?? ''), $programasConsulta, true);
        }));
        $inscripcionesPublicas = $this->filtrarInscripcionesPorAislamientoDashboard($inscripcionesPublicas, $filtroRol, $idMinisterioFiltro, $idLiderFiltro);

        $fechaInicio = trim((string)$fechaInicio);
        $fechaFin = trim((string)$fechaFin);
        if ($fechaInicio !== '' && $fechaFin !== '') {
            $inscripcionesPublicas = array_values(array_filter($inscripcionesPublicas, static function ($inscripcion) use ($fechaInicio, $fechaFin) {
                $fechaRegistro = substr(trim((string)($inscripcion['Fecha_Registro'] ?? '')), 0, 10);
                if ($fechaRegistro === '' || $fechaRegistro === '0000-00-00') {
                    return false;
                }

                return $fechaRegistro >= $fechaInicio && $fechaRegistro <= $fechaFin;
            }));
        }

        foreach ($inscripcionesPublicas as &$inscripcionTmp) {
            $nombreActual = trim((string)($inscripcionTmp['Nombre_Persona_Actual'] ?? ''));
            $generoActual = trim((string)($inscripcionTmp['Genero_Persona_Actual'] ?? ''));
            $edadActual = (int)($inscripcionTmp['Edad_Persona_Actual'] ?? 0);
            $liderActual = trim((string)($inscripcionTmp['Lider_Persona_Actual'] ?? ''));
            $ministerioActual = trim((string)($inscripcionTmp['Nombre_Ministerio_Persona_Actual'] ?? ''));

            if ($nombreActual !== '') {
                $inscripcionTmp['Nombre'] = $nombreActual;
            }
            if ($generoActual !== '') {
                $inscripcionTmp['Genero'] = $generoActual;
            }
            if ($edadActual > 0) {
                $inscripcionTmp['Edad'] = $edadActual;
            }
            if ($liderActual !== '') {
                $inscripcionTmp['Lider'] = $liderActual;
            }
            if ($ministerioActual !== '') {
                $inscripcionTmp['Nombre_Ministerio'] = $ministerioActual;
            }
        }
        unset($inscripcionTmp);

        if ($lineaPrograma === 'uv' && $this->uvFiltroEncuentroDashboard !== '') {
            $inscripcionesPublicas = $this->filtrarInscripcionesUvPorEncuentro(
                $inscripcionesPublicas,
                $this->uvFiltroEncuentroDashboard
            );
        }

        return $inscripcionesPublicas;
    }

    /**
     * @param array<int, bool> $mapAsistenciasPorClase clave = número de clase UV (5 día 1, 6 día 2)
     */
    private function coincideFiltroEncuentroUvDashboard(string $filtroEncuentro, array $mapAsistenciasPorClase): bool {
        $filtroEncuentro = trim($filtroEncuentro);
        if ($filtroEncuentro === '' || $filtroEncuentro === 'todos') {
            return true;
        }

        $d1 = !empty($mapAsistenciasPorClase[5]);
        $d2 = !empty($mapAsistenciasPorClase[6]);

        switch ($filtroEncuentro) {
            case 'excluir_asistieron':
            case 'sin_encuentro':
                return !$d1 && !$d2;
            case 'sin_dia1':
                return !$d1;
            case 'sin_dia2':
                return !$d2;
            case 'con_dia1':
                return $d1;
            case 'con_dia2':
                return $d2;
            case 'con_ambos':
                return $d1 && $d2;
            case 'con_al_menos_uno':
                return $d1 || $d2;
            default:
                return true;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $inscripciones
     * @return array<int, array<string, mixed>>
     */
    private function filtrarInscripcionesUvPorEncuentro(array $inscripciones, string $filtroEncuentro): array {
        $filtroEncuentro = trim($filtroEncuentro);
        if ($filtroEncuentro === '' || $filtroEncuentro === 'todos') {
            return $inscripciones;
        }

        $idsPersona = array_values(array_unique(array_filter(array_map(static function ($inscripcion) {
            return (int)($inscripcion['Id_Persona'] ?? 0);
        }, $inscripciones), static function ($id) {
            return $id > 0;
        })));

        $asistenciasPorPersona = [];
        if (!empty($idsPersona)) {
            $asistenciasPorPersona = $this->escuelaAsistenciaClaseModel->getAsistenciasPorPrograma(
                $idsPersona,
                'consolidar',
                'universidad_vida'
            );
        }

        return array_values(array_filter($inscripciones, function ($inscripcion) use ($filtroEncuentro, $asistenciasPorPersona) {
            $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
            $map = $idPersona > 0 ? (array)($asistenciasPorPersona[$idPersona] ?? []) : [];

            return $this->coincideFiltroEncuentroUvDashboard($filtroEncuentro, $map);
        }));
    }

    /**
     * @param array<int, int> $idsPersona
     * @return array<int, bool>
     */
    private function obtenerMapaAsistenciaPorPrograma(array $idsPersona, string $programa): array {
        $idsPersona = array_values(array_unique(array_filter(array_map('intval', $idsPersona), static function ($id) {
            return $id > 0;
        })));
        $programa = trim($programa);
        if (empty($idsPersona) || $programa === '') {
            return [];
        }

        $personasConAsistencia = [];
        $asistenciasPrograma = $this->escuelaAsistenciaClaseModel->getAsistenciasPorPrograma($idsPersona, 'consolidar', $programa);
        foreach ((array)$asistenciasPrograma as $idPersonaAsistencia => $clasesAsistencia) {
            foreach ((array)$clasesAsistencia as $asistioClase) {
                if (!empty($asistioClase)) {
                    $personasConAsistencia[(int)$idPersonaAsistencia] = true;
                    break;
                }
            }
        }

        return $personasConAsistencia;
    }

    /**
     * Personas con al menos una clase marcada en Capacitación Destino.
     * Incluye planilla de material (modulo_1…3 + capacitacion_destino) y registro al evaluar (discipular + programa por nivel).
     *
     * @param array<int, int> $idsPersona
     * @return array<int, bool>
     */
    private function obtenerMapaAsistenciaCapDestino(array $idsPersona): array {
        $idsPersona = array_values(array_unique(array_filter(array_map('intval', $idsPersona), static function ($id) {
            return $id > 0;
        })));
        if (empty($idsPersona)) {
            return [];
        }

        $personasConAsistencia = [];

        foreach ($this->programasConsultaModoConsolidar('cap') as $programaCap) {
            foreach ($this->obtenerMapaAsistenciaPorPrograma($idsPersona, $programaCap) as $idPersona => $_ok) {
                $personasConAsistencia[$idPersona] = true;
            }

            $asistenciasDiscipular = $this->escuelaAsistenciaClaseModel->getAsistenciasPorPrograma(
                $idsPersona,
                'discipular',
                $programaCap
            );
            foreach ((array)$asistenciasDiscipular as $idPersonaAsistencia => $clasesAsistencia) {
                foreach ((array)$clasesAsistencia as $asistioClase) {
                    if (!empty($asistioClase)) {
                        $personasConAsistencia[(int)$idPersonaAsistencia] = true;
                        break;
                    }
                }
            }
        }

        for ($nivel = 1; $nivel <= 3; $nivel++) {
            $asistenciasMaterial = $this->escuelaAsistenciaClaseModel->getAsistenciasPorPrograma(
                $idsPersona,
                'modulo_' . $nivel,
                'capacitacion_destino'
            );
            foreach ((array)$asistenciasMaterial as $idPersonaAsistencia => $clasesAsistencia) {
                foreach ((array)$clasesAsistencia as $asistioClase) {
                    if (!empty($asistioClase)) {
                        $personasConAsistencia[(int)$idPersonaAsistencia] = true;
                        break;
                    }
                }
            }
        }

        return $personasConAsistencia;
    }

    /**
     * Personas con inscripción activa en Capacitación Destino (cualquier nivel).
     *
     * @param array<int, int> $idsPersona
     * @return array<int, true>
     */
    private function obtenerIdsConInscripcionCapacitacionDestino(array $idsPersona): array {
        $idsPersona = array_values(array_unique(array_filter(array_map('intval', $idsPersona), static function ($id) {
            return $id > 0;
        })));
        if ($idsPersona === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsPersona), '?'));
        $sql = "SELECT DISTINCT Id_Persona
                FROM escuela_formacion_inscripcion
                WHERE Id_Persona IN ({$placeholders})
                  AND Programa IN (
                      'capacitacion_destino',
                      'capacitacion_destino_nivel_1',
                      'capacitacion_destino_nivel_2',
                      'capacitacion_destino_nivel_3'
                  )";

        $rows = $this->personaModel->query($sql, $idsPersona);
        $map = [];
        foreach ((array)$rows as $row) {
            $id = (int)($row['Id_Persona'] ?? 0);
            if ($id > 0) {
                $map[$id] = true;
            }
        }

        return $map;
    }

    /**
     * Pasó a Capacitación Destino: etapa Discipular/Enviar, peldaño Discipular marcado o inscripción Cap.
     *
     * @param array<int, true> $idsConInscripcionCap
     */
    private function personaEnCapacitacionDestinoEscalera(
        array $checklist,
        string $proceso,
        array $persona,
        array $idsConInscripcionCap
    ): bool {
        $idPersona = (int)($persona['Id_Persona'] ?? 0);
        if ($idPersona > 0 && !empty($idsConInscripcionCap[$idPersona])) {
            return true;
        }

        if (in_array($proceso, ['Discipular', 'Enviar'], true)) {
            return true;
        }

        for ($i = 0; $i <= 2; $i++) {
            if ($this->peldanoMarcado($checklist, 'Discipular', $i, $proceso)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Condición SQL sobre alias p (tabla persona) para dashboard UV.
     *
     * @return array{0:string,1:array<int, mixed>}
     */
    private function construirCondicionSqlPersonasDashboard($filtroRol, ?int $idMinisterioFiltro, ?int $idLiderFiltro): array {
        $where = ['(' . trim((string)$filtroRol) . ')'];
        $params = [];

        if ($idMinisterioFiltro !== null && $idMinisterioFiltro > 0) {
            $where[] = 'p.Id_Ministerio = ?';
            $params[] = $idMinisterioFiltro;
        }
        if ($idLiderFiltro !== null && $idLiderFiltro > 0) {
            $where[] = 'p.Id_Lider = ?';
            $params[] = $idLiderFiltro;
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * KPIs UV: personas en alcance según filtros, contadas por Escalera_Checklist (Consolidar).
     *
     * @return array<string, int|float|string>
     */
    private function construirIndicadoresEncuentroUvModoConsolidar(
        $filtroRol,
        ?int $idMinisterioFiltro,
        ?int $idLiderFiltro,
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        unset($fechaInicio, $fechaFin);

        [$condPersonas, $paramsPersonas] = $this->construirCondicionSqlPersonasDashboard(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro
        );

        $personas = $this->personaModel->query(
            "SELECT p.Id_Persona, p.Proceso, p.Escalera_Checklist, p.Id_Lider, p.Id_Ministerio,
                    COALESCE(NULLIF(TRIM(m.Nombre_Ministerio), ''), 'Sin ministerio') AS Nombre_Ministerio
             FROM persona p
             LEFT JOIN ministerio m ON m.Id_Ministerio = p.Id_Ministerio
             WHERE {$condPersonas}",
            $paramsPersonas
        );

        $idsMinisteriosPersonas = [];
        foreach ($personas as $personaTmp) {
            $idMinTmp = (int)($personaTmp['Id_Ministerio'] ?? 0);
            if ($idMinTmp > 0) {
                $idsMinisteriosPersonas[$idMinTmp] = $idMinTmp;
            }
        }
        $contextoLideres12 = $this->construirContextoLideresPrincipales12Escalera(
            (string)$filtroRol,
            array_values($idsMinisteriosPersonas),
            $idLiderFiltro
        );
        $lideres12PorMinisterio = (array)($contextoLideres12['por_ministerio'] ?? []);
        $liderSuperiorPorId = (array)($contextoLideres12['lider_superior_por_id'] ?? []);

        $total = count($personas);
        $conUv = 0;
        $conEncuentro = 0;
        $conBautismo = 0;
        $conCapDestino = 0;
        $desglosePorLider12 = [
            'total' => [],
            'con_uv' => [],
            'sin_uv' => [],
            'encuentro' => [],
            'bautismo' => [],
            'cap_destino' => [],
        ];

        $idsPersonas = [];
        foreach ($personas as $persona) {
            $idPersona = (int)($persona['Id_Persona'] ?? 0);
            if ($idPersona > 0) {
                $idsPersonas[$idPersona] = $idPersona;
            }
        }
        $idsConInscripcionCap = $this->obtenerIdsConInscripcionCapacitacionDestino(array_values($idsPersonas));

        foreach ($personas as $persona) {
            $ministerioNombre = trim((string)($persona['Nombre_Ministerio'] ?? '')) ?: 'Sin ministerio';
            $idMinisterioPersona = (int)($persona['Id_Ministerio'] ?? 0);
            $idLiderPersona = (int)($persona['Id_Lider'] ?? 0);
            $liderResuelto = $this->resolverLiderPrincipal12PersonaEnMinisterio(
                $idLiderPersona,
                $idMinisterioPersona,
                $lideres12PorMinisterio,
                $liderSuperiorPorId
            );
            $idLider12 = (int)($liderResuelto['id_lider'] ?? 0);
            $nombreLider12 = (string)($liderResuelto['lider'] ?? 'Sin líder de 12');
            $ministerioLider = trim((string)($liderResuelto['ministerio'] ?? '')) ?: $ministerioNombre;
            $checklist = $this->obtenerChecklist($persona);
            $proceso = $this->normalizarProcesoValor($persona['Proceso'] ?? '');

            $this->incrementarDesgloseEscaleraPorLider12($desglosePorLider12, 'total', $idLider12, $nombreLider12, $ministerioLider);

            if ($this->peldanoMarcado($checklist, 'Consolidar', 0, $proceso)) {
                $conUv++;
                $this->incrementarDesgloseEscaleraPorLider12($desglosePorLider12, 'con_uv', $idLider12, $nombreLider12, $ministerioLider);
            } else {
                $this->incrementarDesgloseEscaleraPorLider12($desglosePorLider12, 'sin_uv', $idLider12, $nombreLider12, $ministerioLider);
            }
            if ($this->peldanoMarcado($checklist, 'Consolidar', 1, $proceso)) {
                $conEncuentro++;
                $this->incrementarDesgloseEscaleraPorLider12($desglosePorLider12, 'encuentro', $idLider12, $nombreLider12, $ministerioLider);
            }
            if ($this->peldanoMarcado($checklist, 'Consolidar', 2, $proceso)) {
                $conBautismo++;
                $this->incrementarDesgloseEscaleraPorLider12($desglosePorLider12, 'bautismo', $idLider12, $nombreLider12, $ministerioLider);
            }
            if ($this->personaEnCapacitacionDestinoEscalera($checklist, $proceso, $persona, $idsConInscripcionCap)) {
                $conCapDestino++;
                $this->incrementarDesgloseEscaleraPorLider12($desglosePorLider12, 'cap_destino', $idLider12, $nombreLider12, $ministerioLider);
            }
        }

        if ($idMinisterioFiltro !== null && $idMinisterioFiltro > 0) {
            $this->sembrarLideresPrincipales12DesgloseEscalera(
                $desglosePorLider12,
                (array)($lideres12PorMinisterio[$idMinisterioFiltro] ?? [])
            );
        }

        $sinUv = max(0, $total - $conUv);

        $alcance = 'toda_la_iglesia';
        if ($idMinisterioFiltro !== null && $idMinisterioFiltro > 0) {
            $alcance = 'ministerio';
        } elseif ($idLiderFiltro !== null && $idLiderFiltro > 0) {
            $alcance = 'lider';
        } elseif (trim((string)$filtroRol) !== '1=1') {
            $alcance = 'alcance_rol';
        }

        return [
            'total_personas' => $total,
            'con_uv_escalera' => $conUv,
            'sin_uv_escalera' => $sinUv,
            'con_encuentro_escalera' => $conEncuentro,
            'con_bautismo_escalera' => $conBautismo,
            'con_capacitacion_destino' => $conCapDestino,
            'pct_con_uv' => $total > 0 ? round(($conUv / $total) * 100, 1) : 0.0,
            'pct_sin_uv' => $total > 0 ? round(($sinUv / $total) * 100, 1) : 0.0,
            'pct_capacitacion_destino' => $total > 0 ? round(($conCapDestino / $total) * 100, 1) : 0.0,
            'alcance' => $alcance,
            // Compatibilidad con vistas antiguas (ya no usan asistencia Encuentro).
            'asistieron_encuentro' => $conEncuentro,
            'sin_asistencia_encuentro' => $sinUv,
            'pct_asistieron' => $total > 0 ? round(($conEncuentro / $total) * 100, 1) : 0.0,
            'pct_sin_asistencia' => $total > 0 ? round(($sinUv / $total) * 100, 1) : 0.0,
            'desglose_por_lider12' => $this->formatearDesgloseEscaleraPorLider12(
                $desglosePorLider12,
                $idMinisterioFiltro !== null && $idMinisterioFiltro > 0
            ),
        ];
    }

    /**
     * @param array<int> $idsMinisterios
     * @return array{
     *   por_ministerio: array<int, array<int, array{lider:string, ministerio:string, id_lider:int}>>,
     *   lider_superior_por_id: array<int, int>
     * }
     */
    private function construirContextoLideresPrincipales12Escalera(
        string $filtroRol,
        array $idsMinisterios,
        ?int $idLiderFiltro = null
    ): array {
        $idsMinisterios = array_values(array_unique(array_filter(array_map('intval', $idsMinisterios), static function ($id) {
            return $id > 0;
        })));

        $liderSuperiorPorId = $this->construirMapaSuperiorLideres($filtroRol);
        if ($idsMinisterios === []) {
            return [
                'por_ministerio' => [],
                'lider_superior_por_id' => $liderSuperiorPorId,
            ];
        }

        $filtroLiderStr = ($idLiderFiltro !== null && $idLiderFiltro > 0) ? (string)$idLiderFiltro : '';
        $lideresVisibles = $this->construirLideresPrincipalesResumenSemanalGanar($idsMinisterios, $filtroLiderStr);
        $porMinisterio = [];
        foreach ($lideresVisibles as $lider) {
            $idLider = (int)($lider['Id_Persona'] ?? 0);
            $idMinisterio = (int)($lider['Id_Ministerio'] ?? 0);
            if ($idLider <= 0 || $idMinisterio <= 0) {
                continue;
            }

            $nombreLider = trim((string)($lider['Nombre_Completo'] ?? ''));
            if ($nombreLider === '') {
                $nombreLider = 'Sin líder';
            }
            $nombreMinisterio = trim((string)($lider['Nombre_Ministerio'] ?? ''));
            if ($nombreMinisterio === '') {
                $nombreMinisterio = 'Sin ministerio';
            }

            if (!isset($porMinisterio[$idMinisterio])) {
                $porMinisterio[$idMinisterio] = [];
            }
            $porMinisterio[$idMinisterio][$idLider] = [
                'id_lider' => $idLider,
                'lider' => $nombreLider,
                'ministerio' => $nombreMinisterio,
            ];
        }

        return [
            'por_ministerio' => $porMinisterio,
            'lider_superior_por_id' => $liderSuperiorPorId,
        ];
    }

    /**
     * @param array<int, array<int, array{lider:string, ministerio:string, id_lider:int}>> $lideresPorMinisterio
     * @param array<int, int> $liderSuperiorPorId
     * @return array{id_lider:int, lider:string, ministerio:string}
     */
    private function resolverLiderPrincipal12PersonaEnMinisterio(
        int $idLiderPersona,
        int $idMinisterioPersona,
        array $lideresPorMinisterio,
        array $liderSuperiorPorId
    ): array {
        if ($idLiderPersona <= 0 || $idMinisterioPersona <= 0) {
            return ['id_lider' => 0, 'lider' => 'Sin líder de 12', 'ministerio' => 'Sin ministerio'];
        }

        $candidatos = (array)($lideresPorMinisterio[$idMinisterioPersona] ?? []);
        if ($candidatos === []) {
            return ['id_lider' => 0, 'lider' => 'Sin líder de 12', 'ministerio' => 'Sin ministerio'];
        }

        $rowsMap = [];
        foreach ($candidatos as $idLider => $info) {
            $rowsMap[(int)$idLider] = $info;
        }

        $idResuelto = $this->resolverLiderPrincipal12ParaResumen($idLiderPersona, $rowsMap, $liderSuperiorPorId);
        if ($idResuelto > 0 && isset($rowsMap[$idResuelto])) {
            return [
                'id_lider' => $idResuelto,
                'lider' => trim((string)($rowsMap[$idResuelto]['lider'] ?? '')) ?: 'Sin líder de 12',
                'ministerio' => trim((string)($rowsMap[$idResuelto]['ministerio'] ?? '')) ?: 'Sin ministerio',
            ];
        }

        return ['id_lider' => 0, 'lider' => 'Sin líder de 12', 'ministerio' => 'Sin ministerio'];
    }

    /**
     * @param array<string, array<string, array{id_lider:int, lider:string, ministerio:string, total:int}>> $desglosePorLider12
     * @param array<int, array{id_lider:int, lider:string, ministerio:string}> $lideresMinisterio
     */
    private function sembrarLideresPrincipales12DesgloseEscalera(array &$desglosePorLider12, array $lideresMinisterio): void {
        foreach ($lideresMinisterio as $infoLider) {
            $idLider = (int)($infoLider['id_lider'] ?? 0);
            if ($idLider <= 0) {
                continue;
            }
            $nombreLider = trim((string)($infoLider['lider'] ?? '')) ?: 'Sin líder';
            $nombreMinisterio = trim((string)($infoLider['ministerio'] ?? '')) ?: 'Sin ministerio';
            $clave = (string)$idLider;
            foreach (array_keys($desglosePorLider12) as $indicador) {
                if (!isset($desglosePorLider12[$indicador][$clave])) {
                    $desglosePorLider12[$indicador][$clave] = [
                        'id_lider' => $idLider,
                        'lider' => $nombreLider,
                        'ministerio' => $nombreMinisterio,
                        'total' => 0,
                    ];
                }
            }
        }
    }

    /**
     * @param array<string, array<string, array{id_lider:int, lider:string, ministerio:string, total:int}>> $desglosePorLider12
     */
    private function incrementarDesgloseEscaleraPorLider12(
        array &$desglosePorLider12,
        string $indicador,
        int $idLider,
        string $lider,
        string $ministerio
    ): void {
        $clave = $idLider > 0 ? (string)$idLider : 'sin_lider';
        if (!isset($desglosePorLider12[$indicador][$clave])) {
            $desglosePorLider12[$indicador][$clave] = [
                'id_lider' => $idLider,
                'lider' => $lider,
                'ministerio' => $ministerio,
                'total' => 0,
            ];
        }
        $desglosePorLider12[$indicador][$clave]['total']++;
    }

    /**
     * @param array<string, array<string, array{id_lider:int, lider:string, ministerio:string, total:int}>> $desglosePorLider12
     * @return array<string, array<int, array{lider:string, ministerio:string, total:int}>>
     */
    private function formatearDesgloseEscaleraPorLider12(array $desglosePorLider12, bool $incluirCeros = false): array {
        $formateado = [];
        foreach ($desglosePorLider12 as $indicador => $conteosPorClave) {
            $filas = [];
            foreach ($conteosPorClave as $fila) {
                $totalFila = (int)($fila['total'] ?? 0);
                if (!$incluirCeros && $totalFila <= 0) {
                    continue;
                }
                if ($incluirCeros && $totalFila <= 0 && (int)($fila['id_lider'] ?? 0) <= 0) {
                    continue;
                }
                $filas[] = [
                    'lider' => (string)($fila['lider'] ?? 'Sin líder de 12'),
                    'ministerio' => (string)($fila['ministerio'] ?? 'Sin ministerio'),
                    'total' => $totalFila,
                ];
            }
            usort($filas, static function (array $a, array $b): int {
                $cmpMin = strcasecmp((string)($a['ministerio'] ?? ''), (string)($b['ministerio'] ?? ''));
                if ($cmpMin !== 0) {
                    return $cmpMin;
                }
                return strcasecmp((string)($a['lider'] ?? ''), (string)($b['lider'] ?? ''));
            });
            $formateado[$indicador] = $filas;
        }

        return $formateado;
    }

    /**
     * @param array<string, array<string, int>> $desglosePorMinisterio
     * @return array<string, array<int, array{ministerio:string, total:int}>>
     */
    private function formatearDesgloseEscaleraPorMinisterio(array $desglosePorMinisterio): array {
        $formateado = [];
        foreach ($desglosePorMinisterio as $indicador => $conteosPorMinisterio) {
            $filas = [];
            foreach ($conteosPorMinisterio as $ministerio => $totalMinisterio) {
                $totalMinisterio = (int)$totalMinisterio;
                if ($totalMinisterio <= 0) {
                    continue;
                }
                $filas[] = [
                    'ministerio' => (string)$ministerio,
                    'total' => $totalMinisterio,
                ];
            }
            usort($filas, static function (array $a, array $b): int {
                $cmp = ((int)($b['total'] ?? 0)) <=> ((int)($a['total'] ?? 0));
                if ($cmp !== 0) {
                    return $cmp;
                }
                return strcmp((string)($a['ministerio'] ?? ''), (string)($b['ministerio'] ?? ''));
            });
            $formateado[$indicador] = $filas;
        }

        return $formateado;
    }

    private function obtenerMapaAsistenciaRealModoConsolidar(array $inscripcionesPublicas): array {
        $idsPersonaInscritas = array_values(array_unique(array_filter(array_map(static function($inscripcion) {
            return (int)($inscripcion['Id_Persona'] ?? 0);
        }, $inscripcionesPublicas), static function($idPersona) {
            return $idPersona > 0;
        })));

        $personasConAsistenciaReal = [];
        foreach (['universidad_vida', 'encuentro'] as $programaConsulta) {
            foreach ($this->obtenerMapaAsistenciaPorPrograma($idsPersonaInscritas, $programaConsulta) as $idPersona => $_ok) {
                $personasConAsistenciaReal[$idPersona] = true;
            }
        }

        return $personasConAsistenciaReal;
    }

    /**
     * Misma lógica que el listado de inscripciones/pagos UV (EscuelaFormacionRegistroController).
     */
    private function clasificarGeneroBaseUvDashboard(string $genero): string {
        $genero = strtolower(trim($genero));
        if ($genero === '') {
            return 'otro';
        }

        $esMujer = strpos($genero, 'mujer') !== false
            || strpos($genero, 'femen') !== false
            || preg_match('/(^|[^a-z])(f|fem|female)([^a-z]|$)/', $genero);
        $esHombre = strpos($genero, 'hombre') !== false
            || strpos($genero, 'mascul') !== false
            || preg_match('/(^|[^a-z])(m|masc|male|h)([^a-z]|$)/', $genero);

        if ($esHombre && !$esMujer) {
            return 'hombre';
        }
        if ($esMujer && !$esHombre) {
            return 'mujer';
        }

        return 'otro';
    }

    private function resolverSegmentoUvDashboard(array $inscripcion): string {
        $segmentoPreferido = strtolower(trim((string)($inscripcion['Segmento_Preferido'] ?? '')));
        if (in_array($segmentoPreferido, ['jovenes', 'teens', 'hombres_adultos', 'mujeres_adultas'], true)) {
            return $segmentoPreferido;
        }

        $edad = (int)($inscripcion['Edad'] ?? 0);
        $genero = trim((string)($inscripcion['Genero'] ?? ''));
        $generoClasificado = $this->clasificarGeneroBaseUvDashboard($genero);

        if ($edad >= 14 && $edad <= 28) {
            return 'jovenes';
        }
        if ($edad >= 9 && $edad <= 13) {
            return 'teens';
        }
        if (($edad >= 29 || $edad <= 0) && $generoClasificado === 'hombre') {
            return 'hombres_adultos';
        }
        if (($edad >= 29 || $edad <= 0) && $generoClasificado === 'mujer') {
            return 'mujeres_adultas';
        }

        $generoLower = strtolower($genero);
        if ($generoLower !== '' && strpos($generoLower, 'joven') !== false) {
            return 'jovenes';
        }

        return 'otros';
    }

    /**
     * UV dashboard: H/M por género (incluye jóvenes del mismo sexo); J/Teens por segmento de inscripción.
     */
    private function clasificarSegmentoUvInscripcion(array $inscripcion): array {
        $segmento = $this->resolverSegmentoUvDashboard($inscripcion);
        $genero = strtolower(trim((string)($inscripcion['Genero'] ?? '')));
        $esMujer = strpos($genero, 'mujer') !== false
            || strpos($genero, 'femen') !== false
            || in_array($genero, ['f', 'fem', 'female'], true);
        $esHombre = strpos($genero, 'hombre') !== false
            || strpos($genero, 'mascul') !== false
            || in_array($genero, ['m', 'masc', 'male', 'h'], true);
        if ($esHombre && $esMujer) {
            $esHombre = false;
            $esMujer = false;
        }

        return [
            'segmento' => $segmento,
            'es_teen' => $segmento === 'teens',
            'es_joven' => $segmento === 'jovenes',
            'es_hombre_adulto' => $esHombre,
            'es_mujer_adulta' => $esMujer,
        ];
    }

    /**
     * @return array<int, string> claves h|m|j|t activas para la persona
     */
    private function clavesSegmentoUvInscripcion(array $segmento): array {
        $keys = [];
        if (!empty($segmento['es_hombre_adulto'])) {
            $keys[] = 'h';
        }
        if (!empty($segmento['es_mujer_adulta'])) {
            $keys[] = 'm';
        }
        if (!empty($segmento['es_joven'])) {
            $keys[] = 'j';
        }
        if (!empty($segmento['es_teen'])) {
            $keys[] = 't';
        }

        return $keys;
    }

    private function coincideFiltroSegmentoUvDashboard(array $segmento, array $genFiltro): bool {
        if (empty($genFiltro)) {
            return true;
        }

        $keys = $this->clavesSegmentoUvInscripcion($segmento);
        if (empty($keys)) {
            return false;
        }

        foreach ($genFiltro as $generoFiltro) {
            if (in_array($generoFiltro, $keys, true)) {
                return true;
            }
        }

        return false;
    }

    private function construirTablaUvPorMinisterioModoConsolidar(
        $filtroRol,
        ?int $idMinisterioFiltro,
        ?int $idLiderFiltro,
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        $inscripcionesPublicas = $this->obtenerInscripcionesPublicasModoConsolidar(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $fechaInicio,
            $fechaFin
        );
        $personasConAsistenciaReal = $this->obtenerMapaAsistenciaRealModoConsolidar($inscripcionesPublicas);

        $tablaUvPorMinisterioMap = [];
        foreach ($inscripcionesPublicas as $inscripcionUv) {
            if ((string)($inscripcionUv['Programa'] ?? '') !== 'universidad_vida') {
                continue;
            }

            $ministerioNombre = trim((string)($inscripcionUv['Nombre_Ministerio'] ?? ''));
            if ($ministerioNombre === '') {
                $ministerioNombre = 'Sin ministerio';
            }
            if ($this->esMinisterioPastoral($ministerioNombre)) {
                continue;
            }

            $segmento = $this->clasificarSegmentoUvInscripcion($inscripcionUv);

            if (!isset($tablaUvPorMinisterioMap[$ministerioNombre])) {
                $tablaUvPorMinisterioMap[$ministerioNombre] = [
                    'ministerio' => $ministerioNombre,
                    'hombres' => 0,
                    'mujeres' => 0,
                    'jovenes' => 0,
                    'teens' => 0,
                    'asistencias_reales' => 0,
                    'asist_hombres' => 0,
                    'asist_mujeres' => 0,
                    'asist_jovenes' => 0,
                    'asist_teens' => 0,
                    'total' => 0,
                ];
            }

            if (!empty($segmento['es_hombre_adulto'])) {
                $tablaUvPorMinisterioMap[$ministerioNombre]['hombres']++;
            }
            if (!empty($segmento['es_mujer_adulta'])) {
                $tablaUvPorMinisterioMap[$ministerioNombre]['mujeres']++;
            }
            if (!empty($segmento['es_joven'])) {
                $tablaUvPorMinisterioMap[$ministerioNombre]['jovenes']++;
            }
            if (!empty($segmento['es_teen'])) {
                $tablaUvPorMinisterioMap[$ministerioNombre]['teens']++;
            }

            $tablaUvPorMinisterioMap[$ministerioNombre]['total']++;

            $idPersonaInscripcionUv = (int)($inscripcionUv['Id_Persona'] ?? 0);
            if ($idPersonaInscripcionUv > 0 && !empty($personasConAsistenciaReal[$idPersonaInscripcionUv])) {
                $tablaUvPorMinisterioMap[$ministerioNombre]['asistencias_reales']++;
                if (!empty($segmento['es_hombre_adulto'])) {
                    $tablaUvPorMinisterioMap[$ministerioNombre]['asist_hombres']++;
                }
                if (!empty($segmento['es_mujer_adulta'])) {
                    $tablaUvPorMinisterioMap[$ministerioNombre]['asist_mujeres']++;
                }
                if (!empty($segmento['es_joven'])) {
                    $tablaUvPorMinisterioMap[$ministerioNombre]['asist_jovenes']++;
                }
                if (!empty($segmento['es_teen'])) {
                    $tablaUvPorMinisterioMap[$ministerioNombre]['asist_teens']++;
                }
            }
        }

        $tablaUvPorMinisterio = array_values($tablaUvPorMinisterioMap);
        usort($tablaUvPorMinisterio, static function($a, $b) {
            $cmpTotal = ((int)($b['total'] ?? 0)) <=> ((int)($a['total'] ?? 0));
            if ($cmpTotal !== 0) {
                return $cmpTotal;
            }
            return strcmp((string)($a['ministerio'] ?? ''), (string)($b['ministerio'] ?? ''));
        });

        return $tablaUvPorMinisterio;
    }

    private function construirTablaCapPorMinisterioModoConsolidar(
        $filtroRol,
        ?int $idMinisterioFiltro,
        ?int $idLiderFiltro,
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        require_once APP . '/Helpers/EscuelaFormacionResumenHelper.php';

        $inscripcionesPublicas = $this->obtenerInscripcionesPublicasModoConsolidar(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $fechaInicio,
            $fechaFin,
            'cap'
        );

        $idsPersona = array_values(array_unique(array_filter(array_map(static function ($inscripcion) {
            return (int)($inscripcion['Id_Persona'] ?? 0);
        }, $inscripcionesPublicas), static function ($id) {
            return $id > 0;
        })));

        $personasConAsistencia = $this->obtenerMapaAsistenciaCapDestino($idsPersona);

        foreach ($inscripcionesPublicas as &$inscripcionCapTmp) {
            $inscripcionCapTmp['Nombre_Ministerio'] = $this->resolverNombreMinisterioInscripcionDashboard($inscripcionCapTmp);
        }
        unset($inscripcionCapTmp);

        return EscuelaFormacionResumenHelper::construirTablaCapDestinoPorMinisterio(
            $inscripcionesPublicas,
            $personasConAsistencia
        );
    }

    private function construirTablaPagosCapModoConsolidar(
        $filtroRol,
        ?int $idMinisterioFiltro,
        ?int $idLiderFiltro,
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        require_once APP . '/Helpers/EscuelaFormacionResumenHelper.php';

        $inscripcionesPublicas = $this->obtenerInscripcionesPublicasModoConsolidar(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $fechaInicio,
            $fechaFin,
            'cap'
        );

        $tablaPagosMap = [];
        foreach ($inscripcionesPublicas as $inscripcion) {
            if (!EscuelaFormacionResumenHelper::esProgramaCapacitacionDestino((string)($inscripcion['Programa'] ?? ''))) {
                continue;
            }

            $ministerioNombre = $this->resolverNombreMinisterioInscripcionDashboard($inscripcion);

            if (!isset($tablaPagosMap[$ministerioNombre])) {
                $tablaPagosMap[$ministerioNombre] = [
                    'Ministerio' => $ministerioNombre,
                    'Inscritos' => 0,
                    'Pagados' => 0,
                    'Valor_Recaudado' => 0.0,
                    'Inscritos_Nivel_1' => 0,
                    'Inscritos_Nivel_2' => 0,
                    'Inscritos_Nivel_3' => 0,
                    'Pagados_Nivel_1' => 0,
                    'Pagados_Nivel_2' => 0,
                    'Pagados_Nivel_3' => 0,
                ];
            }

            $tablaPagosMap[$ministerioNombre]['Inscritos']++;
            $nivel = EscuelaFormacionResumenHelper::resolverNivelCapacitacionDestino($inscripcion);
            if ($nivel === 'nivel_1') {
                $tablaPagosMap[$ministerioNombre]['Inscritos_Nivel_1']++;
            } elseif ($nivel === 'nivel_2') {
                $tablaPagosMap[$ministerioNombre]['Inscritos_Nivel_2']++;
            } elseif ($nivel === 'nivel_3') {
                $tablaPagosMap[$ministerioNombre]['Inscritos_Nivel_3']++;
            }

            if ($this->esInscripcionUvPagada($inscripcion)) {
                $tablaPagosMap[$ministerioNombre]['Pagados']++;
                $tablaPagosMap[$ministerioNombre]['Valor_Recaudado'] += $this->valorPagoInscripcionUv($inscripcion);
                if ($nivel === 'nivel_1') {
                    $tablaPagosMap[$ministerioNombre]['Pagados_Nivel_1']++;
                } elseif ($nivel === 'nivel_2') {
                    $tablaPagosMap[$ministerioNombre]['Pagados_Nivel_2']++;
                } elseif ($nivel === 'nivel_3') {
                    $tablaPagosMap[$ministerioNombre]['Pagados_Nivel_3']++;
                }
            }
        }

        $tablaPagos = array_values($tablaPagosMap);
        usort($tablaPagos, static function ($a, $b) {
            $cmp = ((int)($b['Inscritos'] ?? 0)) <=> ((int)($a['Inscritos'] ?? 0));
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string)($a['Ministerio'] ?? ''), (string)($b['Ministerio'] ?? ''));
        });

        return $tablaPagos;
    }

    private function construirConteoInscritosPorLiderModoConsolidar(
        $filtroRol,
        ?int $idMinisterioFiltro,
        ?int $idLiderFiltro,
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        $inscripcionesPublicas = $this->obtenerInscripcionesPublicasModoConsolidar(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $fechaInicio,
            $fechaFin
        );

        $conteoPorLider = [];
        foreach ($inscripcionesPublicas as $inscripcion) {
            if ((string)($inscripcion['Programa'] ?? '') !== 'universidad_vida') {
                continue;
            }

            $ministerioNombre = trim((string)($inscripcion['Nombre_Ministerio'] ?? ''));
            if ($this->esMinisterioPastoral($ministerioNombre)) {
                continue;
            }

            $nombreLiderRaw = trim((string)($inscripcion['Lider'] ?? ''));
            $nombreLiderKey = $this->normalizarTextoComparable($nombreLiderRaw);
            if ($nombreLiderKey === '') {
                continue;
            }

            if (!isset($conteoPorLider[$nombreLiderKey])) {
                $conteoPorLider[$nombreLiderKey] = [
                    'Id_Lider_Actual' => 0,
                    'Nombre_Lider_Referencia' => $nombreLiderRaw,
                    'Total' => 0,
                ];
            }
            $conteoPorLider[$nombreLiderKey]['Total']++;
        }

        return array_values($conteoPorLider);
    }

    private function esInscripcionUvPagada(array $inscripcion): bool {
        if ($this->valorPagoMovimientosInscripcion($inscripcion) > 0) {
            return true;
        }

        if ($this->inscripcionTienePagoEnMovimientos($inscripcion)) {
            return true;
        }

        $tipoPago = strtolower(trim((string)($inscripcion['Tipo_Pago'] ?? '')));
        if (in_array($tipoPago, ['abono', 'completo'], true)) {
            return (float)($inscripcion['Valor_Pago'] ?? 0) > 0
                || $this->valorPagoMovimientosInscripcion($inscripcion) > 0;
        }

        if ($this->inscripcionTienePagoEnFicha($inscripcion)) {
            return (float)($inscripcion['Valor_Pago'] ?? 0) > 0;
        }

        return false;
    }

    private function valorPagoInscripcionUv(array $inscripcion): float {
        $valorFicha = (float)($inscripcion['Valor_Pago'] ?? 0);
        $valorMov = $this->valorPagoMovimientosInscripcion($inscripcion);

        return max($valorFicha, $valorMov);
    }

    private function construirResumenInscritosPagadosPorLiderUvModoConsolidar(
        $filtroRol,
        ?int $idMinisterioFiltro,
        ?int $idLiderFiltro,
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        $inscripcionesPublicas = $this->obtenerInscripcionesPublicasModoConsolidar(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $fechaInicio,
            $fechaFin
        );
        $resumen = [];

        foreach ($inscripcionesPublicas as $inscripcion) {
            if ((string)($inscripcion['Programa'] ?? '') !== 'universidad_vida') {
                continue;
            }

            $liderNombre = trim((string)($inscripcion['Lider'] ?? ''));
            $liderKey = $this->normalizarTextoComparable($liderNombre);
            if ($liderKey === '') {
                continue;
            }

            if (!isset($resumen[$liderKey])) {
                $resumen[$liderKey] = [
                    'inscritos' => 0,
                    'pagados' => 0,
                    'inscritos_hombres' => 0,
                    'inscritos_mujeres' => 0,
                    'inscritos_jovenes' => 0,
                    'inscritos_teens' => 0,
                    'pagados_hombres' => 0,
                    'pagados_mujeres' => 0,
                    'pagados_jovenes' => 0,
                    'pagados_teens' => 0,
                ];
            }

            $resumen[$liderKey]['inscritos']++;

            $segmento = $this->clasificarSegmentoUvInscripcion($inscripcion);

            if (!empty($segmento['es_hombre_adulto'])) {
                $resumen[$liderKey]['inscritos_hombres']++;
            }
            if (!empty($segmento['es_mujer_adulta'])) {
                $resumen[$liderKey]['inscritos_mujeres']++;
            }
            if (!empty($segmento['es_joven'])) {
                $resumen[$liderKey]['inscritos_jovenes']++;
            }
            if (!empty($segmento['es_teen'])) {
                $resumen[$liderKey]['inscritos_teens']++;
            }

            if ($this->esInscripcionUvPagada($inscripcion)) {
                $resumen[$liderKey]['pagados']++;
                if (!empty($segmento['es_hombre_adulto'])) {
                    $resumen[$liderKey]['pagados_hombres']++;
                }
                if (!empty($segmento['es_mujer_adulta'])) {
                    $resumen[$liderKey]['pagados_mujeres']++;
                }
                if (!empty($segmento['es_joven'])) {
                    $resumen[$liderKey]['pagados_jovenes']++;
                }
                if (!empty($segmento['es_teen'])) {
                    $resumen[$liderKey]['pagados_teens']++;
                }
            }
        }

        return $resumen;
    }

    private function construirTablaPagosUvModoConsolidar(
        $filtroRol,
        ?int $idMinisterioFiltro,
        ?int $idLiderFiltro,
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        $inscripcionesPublicas = $this->obtenerInscripcionesPublicasModoConsolidar(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $fechaInicio,
            $fechaFin
        );

        $tablaPagosMap = [];
        foreach ($inscripcionesPublicas as $inscripcion) {
            if ((string)($inscripcion['Programa'] ?? '') !== 'universidad_vida') {
                continue;
            }

            $ministerioNombre = trim((string)($inscripcion['Nombre_Ministerio'] ?? ''));
            if ($ministerioNombre === '') {
                $ministerioNombre = 'Sin ministerio';
            }
            if ($this->esMinisterioPastoral($ministerioNombre)) {
                continue;
            }

            if (!isset($tablaPagosMap[$ministerioNombre])) {
                $tablaPagosMap[$ministerioNombre] = [
                    'Ministerio' => $ministerioNombre,
                    'Inscritos' => 0,
                    'Pagados' => 0,
                    'Valor_Recaudado' => 0.0,
                    'Inscritos_Hombres' => 0,
                    'Inscritos_Mujeres' => 0,
                    'Inscritos_Jovenes' => 0,
                    'Inscritos_Teens' => 0,
                    'Pagados_Hombres' => 0,
                    'Pagados_Mujeres' => 0,
                    'Pagados_Jovenes' => 0,
                    'Pagados_Teens' => 0,
                ];
            }

            $tablaPagosMap[$ministerioNombre]['Inscritos']++;

            $segmento = $this->clasificarSegmentoUvInscripcion($inscripcion);

            if (!empty($segmento['es_hombre_adulto'])) {
                $tablaPagosMap[$ministerioNombre]['Inscritos_Hombres']++;
            }
            if (!empty($segmento['es_mujer_adulta'])) {
                $tablaPagosMap[$ministerioNombre]['Inscritos_Mujeres']++;
            }
            if (!empty($segmento['es_joven'])) {
                $tablaPagosMap[$ministerioNombre]['Inscritos_Jovenes']++;
            }
            if (!empty($segmento['es_teen'])) {
                $tablaPagosMap[$ministerioNombre]['Inscritos_Teens']++;
            }

            $valorPago = $this->valorPagoInscripcionUv($inscripcion);
            $estaPagado = $this->esInscripcionUvPagada($inscripcion);

            if ($estaPagado) {
                $tablaPagosMap[$ministerioNombre]['Pagados']++;
                $tablaPagosMap[$ministerioNombre]['Valor_Recaudado'] += $valorPago;
                if (!empty($segmento['es_hombre_adulto'])) {
                    $tablaPagosMap[$ministerioNombre]['Pagados_Hombres']++;
                }
                if (!empty($segmento['es_mujer_adulta'])) {
                    $tablaPagosMap[$ministerioNombre]['Pagados_Mujeres']++;
                }
                if (!empty($segmento['es_joven'])) {
                    $tablaPagosMap[$ministerioNombre]['Pagados_Jovenes']++;
                }
                if (!empty($segmento['es_teen'])) {
                    $tablaPagosMap[$ministerioNombre]['Pagados_Teens']++;
                }
            }
        }

        $tablaPagos = array_values($tablaPagosMap);
        usort($tablaPagos, static function ($a, $b) {
            return strcasecmp((string)($a['Ministerio'] ?? ''), (string)($b['Ministerio'] ?? ''));
        });

        return $tablaPagos;
    }

    /**
     * Pagos UV agrupados por líder de célula (personas bajo su liderazgo).
     *
     * @return array<int, array<string, mixed>>
     */
    private function construirTablaPagosUvPorLiderCelulaModoConsolidar(
        $filtroRol,
        ?int $idMinisterioFiltro,
        ?int $idLiderFiltro,
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        $inscripcionesPublicas = $this->obtenerInscripcionesPublicasModoConsolidar(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $fechaInicio,
            $fechaFin
        );

        $tablaPagosMap = [];
        foreach ($inscripcionesPublicas as $inscripcion) {
            if ((string)($inscripcion['Programa'] ?? '') !== 'universidad_vida') {
                continue;
            }

            $ministerioNombre = $this->resolverNombreMinisterioInscripcionDashboard($inscripcion);
            if ($this->esMinisterioPastoral($ministerioNombre)) {
                continue;
            }

            $liderNombre = $this->resolverNombreLiderCelulaInscripcionDashboard($inscripcion);
            $idLider = $this->resolverIdLiderCelulaInscripcionDashboard($inscripcion);
            $claveLider = $this->claveAgrupacionLiderCelulaDashboard($inscripcion);

            if (!isset($tablaPagosMap[$claveLider])) {
                $tablaPagosMap[$claveLider] = [
                    'Lider' => $liderNombre,
                    'Id_Lider' => $idLider,
                    'Lider_Slug' => $this->slugLiderCelulaDashboard($idLider, $liderNombre),
                    'Inscritos' => 0,
                    'Pagados' => 0,
                    'Valor_Recaudado' => 0.0,
                    'Inscritos_Hombres' => 0,
                    'Inscritos_Mujeres' => 0,
                    'Inscritos_Jovenes' => 0,
                    'Inscritos_Teens' => 0,
                    'Pagados_Hombres' => 0,
                    'Pagados_Mujeres' => 0,
                    'Pagados_Jovenes' => 0,
                    'Pagados_Teens' => 0,
                ];
            } else {
                $tablaPagosMap[$claveLider]['Lider'] = $this->elegirNombreLiderMasCompleto(
                    (string)$tablaPagosMap[$claveLider]['Lider'],
                    $liderNombre
                );
                if ($idLider > 0) {
                    $tablaPagosMap[$claveLider]['Id_Lider'] = $idLider;
                    $tablaPagosMap[$claveLider]['Lider_Slug'] = $this->slugLiderCelulaDashboard(
                        $idLider,
                        (string)$tablaPagosMap[$claveLider]['Lider']
                    );
                }
            }

            $tablaPagosMap[$claveLider]['Inscritos']++;

            $segmento = $this->clasificarSegmentoUvInscripcion($inscripcion);

            if (!empty($segmento['es_hombre_adulto'])) {
                $tablaPagosMap[$claveLider]['Inscritos_Hombres']++;
            }
            if (!empty($segmento['es_mujer_adulta'])) {
                $tablaPagosMap[$claveLider]['Inscritos_Mujeres']++;
            }
            if (!empty($segmento['es_joven'])) {
                $tablaPagosMap[$claveLider]['Inscritos_Jovenes']++;
            }
            if (!empty($segmento['es_teen'])) {
                $tablaPagosMap[$claveLider]['Inscritos_Teens']++;
            }

            if ($this->esInscripcionUvPagada($inscripcion)) {
                $tablaPagosMap[$claveLider]['Pagados']++;
                $tablaPagosMap[$claveLider]['Valor_Recaudado'] += $this->valorPagoInscripcionUv($inscripcion);
                if (!empty($segmento['es_hombre_adulto'])) {
                    $tablaPagosMap[$claveLider]['Pagados_Hombres']++;
                }
                if (!empty($segmento['es_mujer_adulta'])) {
                    $tablaPagosMap[$claveLider]['Pagados_Mujeres']++;
                }
                if (!empty($segmento['es_joven'])) {
                    $tablaPagosMap[$claveLider]['Pagados_Jovenes']++;
                }
                if (!empty($segmento['es_teen'])) {
                    $tablaPagosMap[$claveLider]['Pagados_Teens']++;
                }
            }
        }

        $tablaPagos = array_values($tablaPagosMap);
        usort($tablaPagos, static function ($a, $b) {
            return strcasecmp((string)($a['Lider'] ?? ''), (string)($b['Lider'] ?? ''));
        });

        return $tablaPagos;
    }

    /**
     * Tabla principal UV: inscritos y pagos por ministerio, segmento H/M/J/Teens y asistencias.
     *
     * @return array<int, array<string, mixed>>
     */
    private function construirReporteUvMinisterioDashboard(
        $filtroRol,
        ?int $idMinisterioFiltro,
        ?int $idLiderFiltro,
        string $fechaInicio,
        string $fechaFin
    ): array {
        $tablaPagos = $this->construirTablaPagosUvModoConsolidar(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $fechaInicio,
            $fechaFin
        );
        $tablaUv = $this->construirTablaUvPorMinisterioModoConsolidar(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $fechaInicio,
            $fechaFin
        );

        $asistenciasPorMinisterio = [];
        foreach ($tablaUv as $filaUv) {
            $nombreMin = trim((string)($filaUv['ministerio'] ?? ''));
            if ($nombreMin === '') {
                continue;
            }
            $asistenciasPorMinisterio[$nombreMin] = (int)($filaUv['asistencias_reales'] ?? 0);
        }

        $reporte = [];
        foreach ($tablaPagos as $fila) {
            $ministerio = trim((string)($fila['Ministerio'] ?? 'Sin ministerio'));
            $inscritos = (int)($fila['Inscritos'] ?? 0);
            $pagados = (int)($fila['Pagados'] ?? 0);

            $reporte[] = [
                'ministerio' => $ministerio,
                'inscritos' => $inscritos,
                'pagados' => $pagados,
                'pendientes' => max(0, $inscritos - $pagados),
                'pct_pago' => $inscritos > 0 ? round(($pagados / $inscritos) * 100, 1) : 0.0,
                'valor_recaudado' => (float)($fila['Valor_Recaudado'] ?? 0),
                'ins_hombres' => (int)($fila['Inscritos_Hombres'] ?? 0),
                'ins_mujeres' => (int)($fila['Inscritos_Mujeres'] ?? 0),
                'ins_jovenes' => (int)($fila['Inscritos_Jovenes'] ?? 0),
                'ins_teens' => (int)($fila['Inscritos_Teens'] ?? 0),
                'pag_hombres' => (int)($fila['Pagados_Hombres'] ?? 0),
                'pag_mujeres' => (int)($fila['Pagados_Mujeres'] ?? 0),
                'pag_jovenes' => (int)($fila['Pagados_Jovenes'] ?? 0),
                'pag_teens' => (int)($fila['Pagados_Teens'] ?? 0),
                'asistencias_reales' => (int)($asistenciasPorMinisterio[$ministerio] ?? 0),
            ];
        }

        return $reporte;
    }

    private function construirDetalleLideresMinisterioUvModoConsolidar(
        $filtroRol,
        int $idMinisterioFiltro,
        ?int $idLiderFiltro,
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        $inscripcionesPublicas = $this->obtenerInscripcionesPublicasModoConsolidar(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $fechaInicio,
            $fechaFin
        );
        $personasConAsistenciaReal = $this->obtenerMapaAsistenciaRealModoConsolidar($inscripcionesPublicas);

        $rowsMap = [];
        foreach ($inscripcionesPublicas as $inscripcion) {
            if ((string)($inscripcion['Programa'] ?? '') !== 'universidad_vida') {
                continue;
            }

            $ministerioNombre = trim((string)($inscripcion['Nombre_Ministerio'] ?? ''));
            if ($this->esMinisterioPastoral($ministerioNombre)) {
                continue;
            }

            $liderNombre = trim((string)($inscripcion['Lider'] ?? ''));
            if ($liderNombre === '') {
                $liderNombre = 'Sin lider';
            }
            $liderKey = $this->normalizarTextoComparable($liderNombre);
            if ($liderKey === '') {
                $liderKey = 'sin_lider';
            }

            if (!isset($rowsMap[$liderKey])) {
                $rowsMap[$liderKey] = [
                    'lider' => $liderNombre,
                    'inscritos' => 0,
                    'hombres' => 0,
                    'mujeres' => 0,
                    'jovenes' => 0,
                    'asistencias_reales' => 0,
                    'pagados' => 0,
                    'valor_recaudado' => 0.0,
                ];
            }

            $rowsMap[$liderKey]['inscritos']++;

            $segmento = $this->clasificarSegmentoUvInscripcion($inscripcion);
            if (!empty($segmento['es_hombre_adulto'])) {
                $rowsMap[$liderKey]['hombres']++;
            }
            if (!empty($segmento['es_mujer_adulta'])) {
                $rowsMap[$liderKey]['mujeres']++;
            }
            if (!empty($segmento['es_joven'])) {
                $rowsMap[$liderKey]['jovenes']++;
            }

            $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
            if ($idPersona > 0 && !empty($personasConAsistenciaReal[$idPersona])) {
                $rowsMap[$liderKey]['asistencias_reales']++;
            }

            $valorPago = (float)($inscripcion['Valor_Pago'] ?? 0);
            $estaPagado = $this->esInscripcionUvPagada($inscripcion);
            if ($estaPagado) {
                $rowsMap[$liderKey]['pagados']++;
                $rowsMap[$liderKey]['valor_recaudado'] += $valorPago;
            }
        }

        $rows = array_values($rowsMap);
        foreach ($rows as &$row) {
            $inscritos = (int)($row['inscritos'] ?? 0);
            $pagados = (int)($row['pagados'] ?? 0);
            $row['pendientes'] = max(0, $inscritos - $pagados);
            $row['pct_pago'] = $inscritos > 0 ? round(($pagados / $inscritos) * 100, 1) : 0;
        }
        unset($row);

        usort($rows, static function($a, $b) {
            $cmp = ((int)($b['inscritos'] ?? 0)) <=> ((int)($a['inscritos'] ?? 0));
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp((string)($a['lider'] ?? ''), (string)($b['lider'] ?? ''));
        });

        return $rows;
    }

    private function dashboardEscuelasPorLinea($linea, $titulo, $rutaDashboard) {
        if (!AuthController::puedeVerDashboardEscuelasLinea((string)$linea)) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $anio = (int)($_GET['anio'] ?? date('Y'));
        if ($anio < 2020 || $anio > ((int)date('Y') + 2)) {
            $anio = (int)date('Y');
        }

        $mes = (int)($_GET['mes'] ?? date('n'));
        if ($mes < 1 || $mes > 12) {
            $mes = (int)date('n');
        }
        $semestreUv = (int)($_GET['semestre'] ?? 0);
        $etiquetaPeriodoUv = '';

        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';
        $filtroEncuentroUv = '';
        if ($linea === 'universidad_vida') {
            $filtroEncuentroUv = trim((string)($_GET['filtro_encuentro'] ?? ''));
            $filtrosEncuentroPermitidos = [
                'todos', 'excluir_asistieron', 'sin_encuentro', 'sin_dia1', 'sin_dia2',
                'con_dia1', 'con_dia2', 'con_ambos', 'con_al_menos_uno',
            ];
            if (!in_array($filtroEncuentroUv, $filtrosEncuentroPermitidos, true)) {
                $filtroEncuentroUv = '';
            }
            $this->uvFiltroEncuentroDashboard = ($filtroEncuentroUv !== '' && $filtroEncuentroUv !== 'todos')
                ? $filtroEncuentroUv
                : '';
        }

        $filtroRol = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();

        $opcionesFiltro = $this->construirOpcionesFiltroMinisterioLider($filtroCelulas);
        $filtroMinisterio = ($filtroMinisterio !== '' && isset($opcionesFiltro['ministerio_ids_permitidos'][(int)$filtroMinisterio])) ? (int)$filtroMinisterio : '';
        $filtroLider = ($filtroLider !== '' && isset($opcionesFiltro['lider_ids_permitidos'][(int)$filtroLider])) ? (int)$filtroLider : '';

        $idMinisterioFiltro = ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null;
        $idLiderFiltro = ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null;

        if ($linea === 'universidad_vida') {
            $rangoUv = $this->resolverRangoSemestreDashboardUv($anio, $semestreUv > 0 ? $semestreUv : null);
            $semestreUv = (int)$rangoUv['semestre'];
            $fechaInicioMes = (string)$rangoUv['fecha_inicio'];
            $fechaFinMes = (string)$rangoUv['fecha_fin'];
            $etiquetaPeriodoUv = (string)$rangoUv['etiqueta'];
            $diaTranscurrido = (int)date('j');
            $diasMes = (int)date('t');
        } else {
            $fechaInicioMes = sprintf('%04d-%02d-01', $anio, $mes);
            $fechaFinMes = date('Y-m-t', strtotime($fechaInicioMes));
            $diasMes = (int)date('t', strtotime($fechaInicioMes));

            $mesActualKey = date('Y-m');
            $mesTableroKey = sprintf('%04d-%02d', $anio, $mes);
            if ($mesTableroKey < $mesActualKey) {
                $diaTranscurrido = $diasMes;
            } elseif ($mesTableroKey > $mesActualKey) {
                $diaTranscurrido = 0;
            } else {
                $diaTranscurrido = (int)date('j');
            }
        }

        $metaPorLider = 6;
        $lideresBase = (array)($opcionesFiltro['lideres_disponibles'] ?? []);
        if ($idLiderFiltro !== null && $idLiderFiltro > 0) {
            $lideresBase = array_values(array_filter($lideresBase, static function($item) use ($idLiderFiltro) {
                return (int)($item['Id_Persona'] ?? 0) === $idLiderFiltro;
            }));
        }

        $liderIds = array_values(array_filter(array_map(static function($item) {
            return (int)($item['Id_Persona'] ?? 0);
        }, $lideresBase), static function($id) {
            return $id > 0;
        }));

        $lideresInfo = $this->personaModel->getResumenLideresByIds($liderIds);
        if ($linea === 'universidad_vida') {
            // Alinea los conteos por lider con la misma base usada en el modulo Consolidar.
            $conteoMesPorLiderRows = $this->construirConteoInscritosPorLiderModoConsolidar(
                $filtroRol,
                $idMinisterioFiltro,
                $idLiderFiltro,
                $fechaInicioMes,
                $fechaFinMes
            );
        } else {
            $conteoMesPorLiderRows = $this->escuelaInscripcionModel->getConteoInscritosPorLiderLineaFlexible(
                $linea,
                $fechaInicioMes,
                $fechaFinMes,
                $filtroRol,
                $idMinisterioFiltro,
                $idLiderFiltro
            );
        }

        $mapNombreLiderIds = [];
        foreach ($lideresBase as $liderMapBase) {
            $idLMap = (int)($liderMapBase['Id_Persona'] ?? 0);
            $nomMap = $this->normalizarTextoComparable($liderMapBase['Nombre_Completo'] ?? '');
            if ($idLMap > 0 && $nomMap !== '') {
                if (!isset($mapNombreLiderIds[$nomMap])) {
                    $mapNombreLiderIds[$nomMap] = [];
                }
                $mapNombreLiderIds[$nomMap][] = $idLMap;
            }
        }

        $conteoMesPorLider = [];
        $pagadosPorLider = [];
        $hombresPorLider = [];
        $mujeresPorLider = [];
        $jovenesPorLider = [];
        $teensPorLider = [];
        $pagadosHombresPorLider = [];
        $pagadosMujeresPorLider = [];
        $pagadosJovenesPorLider = [];
        $pagadosTeensPorLider = [];

        if ($linea === 'universidad_vida') {
            $resumenLiderUv = $this->construirResumenInscritosPagadosPorLiderUvModoConsolidar(
                $filtroRol,
                $idMinisterioFiltro,
                $idLiderFiltro,
                $fechaInicioMes,
                $fechaFinMes
            );

            foreach ((array)$resumenLiderUv as $nombreRef => $resumenLiderData) {
                if ($nombreRef === '' || empty($mapNombreLiderIds[$nombreRef])) {
                    continue;
                }

                $idAsignado = (int)$mapNombreLiderIds[$nombreRef][0];
                if ($idAsignado <= 0) {
                    continue;
                }

                if (!isset($conteoMesPorLider[$idAsignado])) {
                    $conteoMesPorLider[$idAsignado] = 0;
                }
                if (!isset($pagadosPorLider[$idAsignado])) {
                    $pagadosPorLider[$idAsignado] = 0;
                }
                if (!isset($hombresPorLider[$idAsignado])) {
                    $hombresPorLider[$idAsignado] = 0;
                }
                if (!isset($mujeresPorLider[$idAsignado])) {
                    $mujeresPorLider[$idAsignado] = 0;
                }
                if (!isset($jovenesPorLider[$idAsignado])) {
                    $jovenesPorLider[$idAsignado] = 0;
                }
                if (!isset($teensPorLider[$idAsignado])) {
                    $teensPorLider[$idAsignado] = 0;
                }
                if (!isset($pagadosHombresPorLider[$idAsignado])) {
                    $pagadosHombresPorLider[$idAsignado] = 0;
                }
                if (!isset($pagadosMujeresPorLider[$idAsignado])) {
                    $pagadosMujeresPorLider[$idAsignado] = 0;
                }
                if (!isset($pagadosJovenesPorLider[$idAsignado])) {
                    $pagadosJovenesPorLider[$idAsignado] = 0;
                }
                if (!isset($pagadosTeensPorLider[$idAsignado])) {
                    $pagadosTeensPorLider[$idAsignado] = 0;
                }

                $conteoMesPorLider[$idAsignado] += (int)($resumenLiderData['inscritos'] ?? 0);
                $pagadosPorLider[$idAsignado] += (int)($resumenLiderData['pagados'] ?? 0);
                $hombresPorLider[$idAsignado] += (int)($resumenLiderData['inscritos_hombres'] ?? 0);
                $mujeresPorLider[$idAsignado] += (int)($resumenLiderData['inscritos_mujeres'] ?? 0);
                $jovenesPorLider[$idAsignado] += (int)($resumenLiderData['inscritos_jovenes'] ?? 0);
                $teensPorLider[$idAsignado] += (int)($resumenLiderData['inscritos_teens'] ?? 0);
                $pagadosHombresPorLider[$idAsignado] += (int)($resumenLiderData['pagados_hombres'] ?? 0);
                $pagadosMujeresPorLider[$idAsignado] += (int)($resumenLiderData['pagados_mujeres'] ?? 0);
                $pagadosJovenesPorLider[$idAsignado] += (int)($resumenLiderData['pagados_jovenes'] ?? 0);
                $pagadosTeensPorLider[$idAsignado] += (int)($resumenLiderData['pagados_teens'] ?? 0);
            }
        } else {
            foreach ((array)$conteoMesPorLiderRows as $conteoRow) {
                $idLiderActual = (int)($conteoRow['Id_Lider_Actual'] ?? 0);
                $nombreRef = $this->normalizarTextoComparable($conteoRow['Nombre_Lider_Referencia'] ?? '');
                $totalRef = (int)($conteoRow['Total'] ?? 0);
                if ($totalRef <= 0) {
                    continue;
                }

                if ($idLiderActual > 0 && isset($lideresInfo[$idLiderActual])) {
                    if (!isset($conteoMesPorLider[$idLiderActual])) {
                        $conteoMesPorLider[$idLiderActual] = 0;
                    }
                    $conteoMesPorLider[$idLiderActual] += $totalRef;
                    continue;
                }

                if ($nombreRef !== '' && !empty($mapNombreLiderIds[$nombreRef])) {
                    $idAsignado = (int)$mapNombreLiderIds[$nombreRef][0];
                    if ($idAsignado > 0) {
                        if (!isset($conteoMesPorLider[$idAsignado])) {
                            $conteoMesPorLider[$idAsignado] = 0;
                        }
                        $conteoMesPorLider[$idAsignado] += $totalRef;
                    }
                }
            }
        }

        $rowsLideres = [];
        $resumen = [
            'inscritos' => 0,
            'meta' => 0,
            'esperado' => 0,
            'justo_a_tiempo' => false,
            'avance_pct' => 0,
        ];

        foreach ($lideresBase as $liderBase) {
            $idLider = (int)($liderBase['Id_Persona'] ?? 0);
            if ($idLider <= 0) {
                continue;
            }

            $info = (array)($lideresInfo[$idLider] ?? []);
            $idMinisterioLider = (int)($info['Id_Ministerio'] ?? 0);
            if ($idMinisterioFiltro !== null && $idMinisterioFiltro > 0 && $idMinisterioLider > 0 && $idMinisterioLider !== $idMinisterioFiltro) {
                continue;
            }

            $generoLider = $this->normalizarGeneroLider($info['Genero'] ?? '');
            $metaLider = ($generoLider === 'Hombre' || $generoLider === 'Mujer') ? $metaPorLider : 0;
            $esperadoHoy = $metaLider > 0 ? (int)round($metaLider * ($diaTranscurrido / max(1, $diasMes))) : 0;
            $inscritosMes = (int)($conteoMesPorLider[$idLider] ?? 0);
            $pagadosLider = (int)($pagadosPorLider[$idLider] ?? 0);
            $inscritosHombresLider = (int)($hombresPorLider[$idLider] ?? 0);
            $inscritosMujeresLider = (int)($mujeresPorLider[$idLider] ?? 0);
            $inscritosJovenesLider = (int)($jovenesPorLider[$idLider] ?? 0);
            $inscritosTeensLider = (int)($teensPorLider[$idLider] ?? 0);
            $pagadosHombresLider = (int)($pagadosHombresPorLider[$idLider] ?? 0);
            $pagadosMujeresLider = (int)($pagadosMujeresPorLider[$idLider] ?? 0);
            $pagadosJovenesLider = (int)($pagadosJovenesPorLider[$idLider] ?? 0);
            $pagadosTeensLider = (int)($pagadosTeensPorLider[$idLider] ?? 0);

            if ($metaLider > 0) {
                $resumen['meta'] += $metaLider;
                $resumen['esperado'] += $esperadoHoy;
            }
            $resumen['inscritos'] += $inscritosMes;

            $semaforo = 'rojo';
            if ($metaLider > 0 && $inscritosMes >= $metaLider) {
                $semaforo = 'verde';
            } elseif ($metaLider > 0 && $inscritosMes >= $esperadoHoy) {
                $semaforo = 'amarillo';
            }

            $rowsLideres[] = [
                'id_lider' => $idLider,
                'lider' => trim((string)($info['Nombre_Completo'] ?? $liderBase['Nombre_Completo'] ?? 'Sin líder')),
                'ministerio' => trim((string)($info['Nombre_Ministerio'] ?? '')) !== ''
                    ? trim((string)$info['Nombre_Ministerio'])
                    : 'Sin ministerio',
                'genero_lider' => $generoLider,
                'inscritos_mes' => $inscritosMes,
                'pagados_lider' => $pagadosLider,
                'inscritos_hombres_lider' => $inscritosHombresLider,
                'inscritos_mujeres_lider' => $inscritosMujeresLider,
                'inscritos_jovenes_lider' => $inscritosJovenesLider,
                'inscritos_teens_lider' => $inscritosTeensLider,
                'pagados_hombres_lider' => $pagadosHombresLider,
                'pagados_mujeres_lider' => $pagadosMujeresLider,
                'pagados_jovenes_lider' => $pagadosJovenesLider,
                'pagados_teens_lider' => $pagadosTeensLider,
                'meta_lider' => $metaLider,
                'esperado_hoy' => $esperadoHoy,
                'justo_a_tiempo' => $metaLider > 0 ? ($inscritosMes >= $esperadoHoy) : false,
                'semaforo' => $semaforo,
                'avance_pct' => $metaLider > 0 ? (int)round(($inscritosMes / $metaLider) * 100) : 0,
                'inscritos_grupo' => $inscritosMes,
                'pagados_grupo' => $pagadosLider,
            ];
        }

        $resumen['justo_a_tiempo'] = $resumen['inscritos'] >= $resumen['esperado'];
        $resumen['avance_pct'] = $resumen['meta'] > 0
            ? (int)round(($resumen['inscritos'] / $resumen['meta']) * 100)
            : 0;

        if ($linea === 'universidad_vida') {
            $lideresHombre = array_values(array_filter($rowsLideres, static function($row) {
                return (int)($row['inscritos_hombres_lider'] ?? 0) > 0;
            }));
            $lideresMujer = array_values(array_filter($rowsLideres, static function($row) {
                return (int)($row['inscritos_mujeres_lider'] ?? 0) > 0;
            }));
            $lideresJoven = array_values(array_filter($rowsLideres, static function($row) {
                return (int)($row['inscritos_jovenes_lider'] ?? 0) > 0;
            }));
            $lideresTeen = array_values(array_filter($rowsLideres, static function($row) {
                return (int)($row['inscritos_teens_lider'] ?? 0) > 0;
            }));

            $lideresHombre = array_values(array_map(static function($row) {
                $row['inscritos_grupo'] = (int)($row['inscritos_hombres_lider'] ?? 0);
                $row['pagados_grupo'] = (int)($row['pagados_hombres_lider'] ?? 0);
                return $row;
            }, $lideresHombre));
            $lideresMujer = array_values(array_map(static function($row) {
                $row['inscritos_grupo'] = (int)($row['inscritos_mujeres_lider'] ?? 0);
                $row['pagados_grupo'] = (int)($row['pagados_mujeres_lider'] ?? 0);
                return $row;
            }, $lideresMujer));
            $lideresJoven = array_values(array_map(static function($row) {
                $row['inscritos_grupo'] = (int)($row['inscritos_jovenes_lider'] ?? 0);
                $row['pagados_grupo'] = (int)($row['pagados_jovenes_lider'] ?? 0);
                return $row;
            }, $lideresJoven));
            $lideresTeen = array_values(array_map(static function($row) {
                $row['inscritos_grupo'] = (int)($row['inscritos_teens_lider'] ?? 0);
                $row['pagados_grupo'] = (int)($row['pagados_teens_lider'] ?? 0);
                return $row;
            }, $lideresTeen));
        } else {
            $lideresHombre = array_values(array_filter($rowsLideres, static function($row) {
                return (string)($row['genero_lider'] ?? '') === 'Hombre';
            }));
            $lideresMujer = array_values(array_filter($rowsLideres, static function($row) {
                return (string)($row['genero_lider'] ?? '') === 'Mujer';
            }));
            $lideresJoven = array_values(array_filter($rowsLideres, static function($row) {
                return (string)($row['genero_lider'] ?? '') === 'Joven';
            }));
            $lideresTeen = array_values(array_filter($rowsLideres, static function($row) {
                return (string)($row['genero_lider'] ?? '') === 'Teen';
            }));
        }
        $lideresOtros = array_values(array_filter($rowsLideres, static function($row) {
            return !in_array((string)($row['genero_lider'] ?? ''), ['Hombre', 'Mujer', 'Joven', 'Teen'], true);
        }));

        $ordenarLideresPorInscritosDesc = static function($a, $b) {
            $cmpIns = ((int)($b['inscritos_grupo'] ?? $b['inscritos_mes'] ?? 0)) <=> ((int)($a['inscritos_grupo'] ?? $a['inscritos_mes'] ?? 0));
            if ($cmpIns !== 0) {
                return $cmpIns;
            }

            $cmpPag = ((int)($b['pagados_grupo'] ?? $b['pagados_lider'] ?? 0)) <=> ((int)($a['pagados_grupo'] ?? $a['pagados_lider'] ?? 0));
            if ($cmpPag !== 0) {
                return $cmpPag;
            }

            return strcmp((string)($a['lider'] ?? ''), (string)($b['lider'] ?? ''));
        };

        usort($lideresHombre, $ordenarLideresPorInscritosDesc);
        usort($lideresMujer, $ordenarLideresPorInscritosDesc);
        usort($lideresJoven, $ordenarLideresPorInscritosDesc);
        usort($lideresTeen, $ordenarLideresPorInscritosDesc);
        usort($lideresOtros, $ordenarLideresPorInscritosDesc);

        $ministeriosDashboardMetas = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);
        $ministeriosDashboardMetas = $this->filtrarMinisteriosSinPastoral((array)$ministeriosDashboardMetas);
        if ($idMinisterioFiltro !== null && $idMinisterioFiltro > 0) {
            $ministeriosDashboardMetas = array_values(array_filter($ministeriosDashboardMetas, static function($item) use ($idMinisterioFiltro) {
                return (int)($item['Id_Ministerio'] ?? 0) === $idMinisterioFiltro;
            }));
        }

        $idsDashboardMetas = array_values(array_filter(array_map(static function($item) {
            return (int)($item['Id_Ministerio'] ?? 0);
        }, $ministeriosDashboardMetas), static function($id) {
            return $id > 0;
        }));

        // Meta para Escuelas: 6 inscritos por cada célula abierta del ministerio.
        $celulasMetaBase = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas, $idMinisterioFiltro, $idLiderFiltro);
        $conteoCelulasPorMinisterio = [];
        foreach ((array)$celulasMetaBase as $celulaMetaRow) {
            $idMinCelula = (int)($celulaMetaRow['Id_Ministerio_Lider'] ?? 0);
            if ($idMinCelula <= 0) {
                continue;
            }

            $estadoCelula = strtolower(trim((string)($celulaMetaRow['Estado_Celula'] ?? 'Activa')));
            if ($estadoCelula !== '' && $estadoCelula !== 'activa') {
                continue;
            }

            if (!isset($conteoCelulasPorMinisterio[$idMinCelula])) {
                $conteoCelulasPorMinisterio[$idMinCelula] = 0;
            }
            $conteoCelulasPorMinisterio[$idMinCelula]++;
        }

        $metasDashboardDetalle = [];
        foreach ($idsDashboardMetas as $idMetaMin) {
            $idMetaMin = (int)$idMetaMin;
            $celulasMinisterio = (int)($conteoCelulasPorMinisterio[$idMetaMin] ?? 0);
            $metaMensual = $celulasMinisterio * 6;
            $metaSemanal = $metaMensual > 0 ? (int)ceil($metaMensual / 4) : 0;
            $metaAnual = $metaMensual * 12;

            $metasDashboardDetalle[$idMetaMin] = [
                'meta_anual' => $metaAnual,
                'meta_mensual' => $metaMensual,
                'meta_semanal' => $metaSemanal,
                'anio_meta' => $anio,
            ];
        }

        $fechaReferenciaDashboard = sprintf('%04d-%02d-%02d', $anio, $mes, max(1, $diaTranscurrido));
        $rangoSemanaDashboard = $this->calcularRangoSemanaDomingoADomingo($fechaReferenciaDashboard);
        $mesDashboard = $this->construirRangoMesCalendario(sprintf('%04d-%02d', $anio, $mes));

        $conteoSemanaDashboard = $this->escuelaInscripcionModel->getConteoInscritosPorMinisterioLinea(
            $linea,
            (string)($rangoSemanaDashboard[0] ?? $fechaInicioMes),
            (string)($rangoSemanaDashboard[1] ?? $fechaFinMes),
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $idsDashboardMetas
        );
        $conteoMesDashboard = $this->escuelaInscripcionModel->getConteoInscritosPorMinisterioLinea(
            $linea,
            (string)($mesDashboard['inicio'] ?? $fechaInicioMes),
            (string)($mesDashboard['fin'] ?? $fechaFinMes),
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $idsDashboardMetas
        );
        $conteoAnioDashboard = $this->escuelaInscripcionModel->getConteoInscritosPorMinisterioLinea(
            $linea,
            sprintf('%04d-01-01', $anio),
            sprintf('%04d-12-31', $anio),
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $idsDashboardMetas
        );

        $dashboardMetasMinisterio = $this->construirDashboardMetasPorMinisterio(
            $ministeriosDashboardMetas,
            $metasDashboardDetalle,
            $conteoSemanaDashboard,
            $conteoMesDashboard,
            $conteoAnioDashboard,
            $fechaReferenciaDashboard
        );

        $tablaPagosUv = [];
        $tablaPagosUvLiderCelula = [];
        $tablaUvModoConsolidar = [];
        $tablaCapModoConsolidar = [];
        $tablaPagosCap = [];
        $reporteUvMinisterios = [];
        $totalInscripcionesUvPeriodo = 0;
        $totalInscripcionesCapPeriodo = 0;
        $totalAsistenciasCap = 0;
        $indicadoresEncuentroUv = [];
        $detalleLideresMinisterioUv = [];
        $nombreMinisterioFiltrado = '';
        if ($linea === 'universidad_vida') {
            $indicadoresEncuentroUv = $this->construirIndicadoresEncuentroUvModoConsolidar(
                $filtroRol,
                $idMinisterioFiltro,
                $idLiderFiltro,
                $fechaInicioMes,
                $fechaFinMes
            );
            $reporteUvMinisterios = $this->construirReporteUvMinisterioDashboard(
                $filtroRol,
                $idMinisterioFiltro,
                $idLiderFiltro,
                $fechaInicioMes,
                $fechaFinMes
            );

            $tablaPagosUv = $this->construirTablaPagosUvModoConsolidar(
                $filtroRol,
                $idMinisterioFiltro,
                $idLiderFiltro,
                $fechaInicioMes,
                $fechaFinMes
            );

            $tablaPagosUvLiderCelula = $this->construirTablaPagosUvPorLiderCelulaModoConsolidar(
                $filtroRol,
                $idMinisterioFiltro,
                $idLiderFiltro,
                $fechaInicioMes,
                $fechaFinMes
            );

            $tablaUvModoConsolidar = $this->construirTablaUvPorMinisterioModoConsolidar(
                $filtroRol,
                $idMinisterioFiltro,
                $idLiderFiltro,
                $fechaInicioMes,
                $fechaFinMes
            );

            $inscripcionesUvPeriodo = $this->obtenerInscripcionesPublicasModoConsolidar(
                $filtroRol,
                $idMinisterioFiltro,
                $idLiderFiltro,
                $fechaInicioMes,
                $fechaFinMes
            );
            foreach ($inscripcionesUvPeriodo as $insUv) {
                if ((string)($insUv['Programa'] ?? '') === 'universidad_vida') {
                    $totalInscripcionesUvPeriodo++;
                }
            }

            if ($idMinisterioFiltro !== null && $idMinisterioFiltro > 0) {
                foreach ((array)($opcionesFiltro['ministerios_disponibles'] ?? []) as $ministerioOpt) {
                    if ((int)($ministerioOpt['Id_Ministerio'] ?? 0) === $idMinisterioFiltro) {
                        $nombreMinisterioFiltrado = trim((string)($ministerioOpt['Nombre_Ministerio'] ?? ''));
                        break;
                    }
                }

                $detalleLideresMinisterioUv = $this->construirDetalleLideresMinisterioUvModoConsolidar(
                    $filtroRol,
                    $idMinisterioFiltro,
                    $idLiderFiltro,
                    $fechaInicioMes,
                    $fechaFinMes
                );
            }
        } elseif ($linea === 'capacitacion_destino') {
            // Cap: misma base que Consolidar (inscripciones activas, sin recorte por mes en tablas).
            $fechaInicioTablasCap = null;
            $fechaFinTablasCap = null;

            $tablaCapModoConsolidar = $this->construirTablaCapPorMinisterioModoConsolidar(
                $filtroRol,
                $idMinisterioFiltro,
                $idLiderFiltro,
                $fechaInicioTablasCap,
                $fechaFinTablasCap
            );

            $tablaPagosCap = $this->construirTablaPagosCapModoConsolidar(
                $filtroRol,
                $idMinisterioFiltro,
                $idLiderFiltro,
                $fechaInicioTablasCap,
                $fechaFinTablasCap
            );

            foreach ($tablaCapModoConsolidar as $filaCap) {
                $totalInscripcionesCapPeriodo += (int)($filaCap['total'] ?? 0);
                $totalAsistenciasCap += (int)($filaCap['asistencias_reales'] ?? 0);
            }
        }

        $this->view('reportes/dashboard_escuelas_formacion', [
            'titulo_dashboard' => $titulo,
            'linea_dashboard' => $linea,
            'ruta_dashboard' => $rutaDashboard,
            'anio' => $anio,
            'mes' => $mes,
            'semestre_uv' => $semestreUv,
            'etiqueta_periodo_uv' => $etiquetaPeriodoUv,
            'filtro_ministerio' => (string)$filtroMinisterio,
            'filtro_lider' => (string)$filtroLider,
            'filtro_encuentro_uv' => $filtroEncuentroUv,
            'ministerios_disponibles' => $opcionesFiltro['ministerios_disponibles'],
            'lideres_disponibles' => $opcionesFiltro['lideres_disponibles'],
            'meta_por_lider' => $metaPorLider,
            'resumen_lideres' => $resumen,
            'lideres_hombre' => $lideresHombre,
            'lideres_mujer' => $lideresMujer,
            'lideres_joven' => $lideresJoven,
            'lideres_teen' => $lideresTeen,
            'lideres_otros' => $lideresOtros,
            'fecha_inicio_mes' => $fechaInicioMes,
            'fecha_fin_mes' => $fechaFinMes,
            'dia_transcurrido' => $diaTranscurrido,
            'dias_mes' => $diasMes,
            'dashboard_metas_ministerio' => $dashboardMetasMinisterio,
            'tabla_pagos_uv' => $tablaPagosUv,
            'tabla_pagos_uv_lider_celula' => $tablaPagosUvLiderCelula,
            'tabla_pagos_uv_modo' => ($linea === 'universidad_vida') ? 'consolidar' : 'mensual',
            'reporte_uv_ministerios' => $reporteUvMinisterios,
            'total_inscripciones_uv_periodo' => $totalInscripcionesUvPeriodo,
            'tabla_uv_modo_consolidar' => $tablaUvModoConsolidar,
            'tabla_cap_modo_consolidar' => $tablaCapModoConsolidar,
            'tabla_pagos_cap' => $tablaPagosCap,
            'total_inscripciones_cap_periodo' => $totalInscripcionesCapPeriodo,
            'total_asistencias_cap' => $totalAsistenciasCap,
            'indicadores_encuentro_uv' => $indicadoresEncuentroUv,
            'detalle_lideres_ministerio_uv' => $detalleLideresMinisterioUv,
            'nombre_ministerio_filtrado' => $nombreMinisterioFiltrado,
        ]);

        $this->uvFiltroEncuentroDashboard = '';
    }

    private function slugMinisterioDashboard(string $nombre): string {
        $s = strtolower(trim(preg_replace('/\s+/u', ' ', $nombre)));
        return $s === '' ? 'sin-ministerio' : $s;
    }

    private function etiquetaSegmentoUvInscripcion(array $segmento): array {
        $labels = [];
        if (!empty($segmento['es_hombre_adulto'])) {
            $labels[] = 'Hombre';
        }
        if (!empty($segmento['es_mujer_adulta'])) {
            $labels[] = 'Mujer';
        }
        if (!empty($segmento['es_joven'])) {
            $labels[] = 'Joven';
        }
        if (!empty($segmento['es_teen'])) {
            $labels[] = 'Teen';
        }

        $keys = $this->clavesSegmentoUvInscripcion($segmento);
        if (empty($labels)) {
            return ['key' => '', 'label' => 'Sin segmento', 'keys' => []];
        }

        return [
            'key' => (string)($keys[0] ?? ''),
            'label' => implode(' · ', $labels),
            'keys' => $keys,
        ];
    }

    /**
     * Detalle de personas por ministerio o líder de célula (JSON) para el dashboard UV.
     */
    public function dashboardEscuelasUvDetalleMinisterio() {
        if (!AuthController::esAdministrador() && !AuthController::puede('reportes:ver')) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['ok' => false, 'mensaje' => 'Sin permiso'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $anio = (int)($_GET['anio'] ?? date('Y'));
        if ($anio < 2020 || $anio > ((int)date('Y') + 2)) {
            $anio = (int)date('Y');
        }
        $semestreUv = (int)($_GET['semestre'] ?? 0);
        $rangoUv = $this->resolverRangoSemestreDashboardUv($anio, $semestreUv > 0 ? $semestreUv : null);

        $liderSlug = trim((string)($_GET['lider'] ?? ''));
        $ministerioSlug = $this->slugMinisterioDashboard((string)($_GET['ministerio'] ?? ''));
        $modoLider = $liderSlug !== '' && $liderSlug !== 'sin-lider-de-celula';
        if (!$modoLider && ($ministerioSlug === '' || $ministerioSlug === 'sin-ministerio')) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(422);
            echo json_encode(['ok' => false, 'mensaje' => 'Ministerio o líder no indicado'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $vista = strtolower(trim((string)($_GET['vista'] ?? 'todas')));
        if (!in_array($vista, ['todas', 'asistencias', 'pagos'], true)) {
            $vista = 'todas';
        }

        $genFiltro = [];
        $genRaw = trim((string)($_GET['gen'] ?? ''));
        if ($genRaw !== '') {
            foreach (explode(',', $genRaw) as $g) {
                $g = strtolower(trim($g));
                if (in_array($g, ['h', 'm', 'j', 't'], true)) {
                    $genFiltro[] = $g;
                }
            }
            $genFiltro = array_values(array_unique($genFiltro));
        }

        $filtroMinisterio = $_GET['filtro_ministerio'] ?? '';
        $filtroLider = $_GET['filtro_lider'] ?? '';
        $filtroRol = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $opcionesFiltro = $this->construirOpcionesFiltroMinisterioLider($filtroCelulas);
        $filtroMinisterio = ($filtroMinisterio !== '' && isset($opcionesFiltro['ministerio_ids_permitidos'][(int)$filtroMinisterio]))
            ? (int)$filtroMinisterio : '';
        $filtroLider = ($filtroLider !== '' && isset($opcionesFiltro['lider_ids_permitidos'][(int)$filtroLider]))
            ? (int)$filtroLider : '';
        $idMinisterioFiltro = ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null;
        $idLiderFiltro = ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null;

        $fechaInicioMes = (string)$rangoUv['fecha_inicio'];
        $fechaFinMes = (string)$rangoUv['fecha_fin'];

        $inscripciones = $this->obtenerInscripcionesPublicasModoConsolidar(
            $filtroRol,
            $idMinisterioFiltro,
            $idLiderFiltro,
            $fechaInicioMes,
            $fechaFinMes
        );
        $personasConAsistencia = $this->obtenerMapaAsistenciaRealModoConsolidar($inscripciones);

        $nombreTitulo = '';
        $personas = [];
        foreach ($inscripciones as $inscripcion) {
            if ((string)($inscripcion['Programa'] ?? '') !== 'universidad_vida') {
                continue;
            }

            $ministerioNombre = trim((string)($inscripcion['Nombre_Ministerio'] ?? ''));
            if ($ministerioNombre === '') {
                $ministerioNombre = 'Sin ministerio';
            }
            if ($this->esMinisterioPastoral($ministerioNombre)) {
                continue;
            }

            if ($modoLider) {
                if (!$this->inscripcionCoincideSlugLiderCelula($inscripcion, $liderSlug)) {
                    continue;
                }
                if ($nombreTitulo === '') {
                    $nombreTitulo = $this->resolverNombreLiderCelulaInscripcionDashboard($inscripcion);
                }
            } else {
                if ($this->slugMinisterioDashboard($ministerioNombre) !== $ministerioSlug) {
                    continue;
                }
                if ($nombreTitulo === '') {
                    $nombreTitulo = $ministerioNombre;
                }
            }

            $segmento = $this->clasificarSegmentoUvInscripcion($inscripcion);
            $segInfo = $this->etiquetaSegmentoUvInscripcion($segmento);
            if (!$this->coincideFiltroSegmentoUvDashboard($segmento, $genFiltro)) {
                continue;
            }

            $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
            $pagado = $this->esInscripcionUvPagada($inscripcion);
            $asistio = $idPersona > 0 && !empty($personasConAsistencia[$idPersona]);

            if ($vista === 'asistencias' && !$asistio) {
                continue;
            }
            if ($vista === 'pagos' && !$pagado) {
                continue;
            }

            $personas[] = [
                'id_inscripcion' => (int)($inscripcion['Id_Inscripcion'] ?? 0),
                'id_persona' => $idPersona,
                'nombre' => trim((string)($inscripcion['Nombre'] ?? '')),
                'cedula' => trim((string)($inscripcion['Cedula'] ?? '')),
                'lider' => trim((string)($inscripcion['Lider'] ?? '')),
                'segmento' => $segInfo['label'],
                'segmento_key' => $segInfo['key'],
                'fecha_registro' => substr(trim((string)($inscripcion['Fecha_Registro'] ?? '')), 0, 10),
                'pagado' => $pagado,
                'asistencia_real' => $asistio,
                'valor_pago' => $this->valorPagoInscripcionUv($inscripcion),
                'metodo_pago' => trim((string)($inscripcion['Metodo_Pago'] ?? '')),
                'referencia_pago' => trim((string)($inscripcion['Referencia_Pago'] ?? '')),
            ];
        }

        usort($personas, static function ($a, $b) {
            $cmp = strcmp((string)($a['nombre'] ?? ''), (string)($b['nombre'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            return ((int)($b['id_inscripcion'] ?? 0)) <=> ((int)($a['id_inscripcion'] ?? 0));
        });

        $totPagados = 0;
        $totAsistencias = 0;
        foreach ($personas as $p) {
            if (!empty($p['pagado'])) {
                $totPagados++;
            }
            if (!empty($p['asistencia_real'])) {
                $totAsistencias++;
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'tipo' => $modoLider ? 'lider' : 'ministerio',
            'ministerio' => $nombreTitulo !== '' ? $nombreTitulo : ($modoLider ? $liderSlug : $ministerioSlug),
            'ministerio_slug' => $modoLider ? '' : $ministerioSlug,
            'lider_slug' => $modoLider ? $liderSlug : '',
            'vista' => $vista,
            'periodo' => [
                'anio' => $anio,
                'semestre' => (int)$rangoUv['semestre'],
                'fecha_inicio' => $fechaInicioMes,
                'fecha_fin' => $fechaFinMes,
                'etiqueta' => (string)$rangoUv['etiqueta'],
            ],
            'personas' => $personas,
            'totales' => [
                'listado' => count($personas),
                'pagados' => $totPagados,
                'asistencias_reales' => $totAsistencias,
            ],
            'alineacion' => [
                'inscripciones' => 'escuela_formacion_inscripcion · listado Consolidar (programa universidad_vida, fecha de registro en el semestre)',
                'pagos' => 'Ficha de inscripción (Valor_Pago, Método, Referencia) y movimientos en escuela_formacion_pago_movimiento',
                'asistencias' => 'escuela_asistencia_clase · módulo consolidar · programa universidad_vida o encuentro (al menos una clase marcada)',
                'nota_pagos_pantalla' => 'Si el pago se registró solo como movimiento y no en la ficha, ahora también se cuenta.',
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function dashboardEscuelasUniversidadVida() {
        $this->dashboardEscuelasPorLinea('universidad_vida', 'Dashboard Escuelas · Universidad de la Vida', 'reportes/dashboard-escuelas-uv');
    }

    public function dashboardEscuelasCapacitacionDestino() {
        $this->dashboardEscuelasPorLinea('capacitacion_destino', 'Dashboard Escuelas · Capacitación Destino', 'reportes/dashboard-escuelas-capacitacion');
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


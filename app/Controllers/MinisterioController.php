<?php
/**
 * Controlador Ministerio
 */

require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Celula.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/DataIsolation.php';
require_once APP . '/Helpers/CodigoJerarquiaHelper.php';

class MinisterioController extends BaseController {
    private $ministerioModel;
    private $personaModel;
    private $celulaModel;

    public function __construct() {
        $this->ministerioModel = new Ministerio();
        $this->personaModel = new Persona();
        $this->celulaModel = new Celula();
    }

    private function normalizarUrlRetorno($returnUrl) {
        $returnUrl = trim((string)$returnUrl);
        if ($returnUrl === '') {
            return null;
        }

        $basePublic = rtrim((string)PUBLIC_URL, '/');

        if ($basePublic !== '' && strpos($returnUrl, $basePublic) === 0) {
            return $returnUrl;
        }

        if (strpos($returnUrl, '?url=') === 0) {
            return $basePublic . $returnUrl;
        }

        if (strpos($returnUrl, 'index.php?url=') === 0) {
            return $basePublic . '/' . ltrim($returnUrl, '/');
        }

        return null;
    }

    private function redirigirConRetorno($returnUrl, $rutaFallback) {
        $urlNormalizada = $this->normalizarUrlRetorno($returnUrl);
        if ($urlNormalizada !== null) {
            header('Location: ' . $urlNormalizada);
            exit;
        }

        $this->redirect($rutaFallback);
    }

    private function redirigirConRetornoConParametros($returnUrl, $rutaFallback, array $params = []) {
        $urlNormalizada = $this->normalizarUrlRetorno($returnUrl);
        if ($urlNormalizada !== null) {
            $separator = strpos($urlNormalizada, '?') !== false ? '&' : '?';
            $query = http_build_query($params);
            header('Location: ' . $urlNormalizada . ($query !== '' ? ($separator . $query) : ''));
            exit;
        }

        if (!empty($params)) {
            $rutaFallback .= '&' . http_build_query($params);
        }

        $this->redirect($rutaFallback);
    }

    private function calcularRangoSemanaDomingoADomingo($fechaReferencia) {
        $timestamp = strtotime((string)$fechaReferencia);
        if ($timestamp === false) {
            $timestamp = time();
        }

        $diaSemana = (int)date('w', $timestamp);
        $inicio = strtotime('-' . $diaSemana . ' days', $timestamp);
        $fin = strtotime('+6 days', $inicio);

        return [date('Y-m-d', $inicio), date('Y-m-d', $fin)];
    }

    private function normalizarTipoReunion($tipoReunion) {
        $valor = strtolower(trim((string)$tipoReunion));
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

    private function normalizarConvencion($convencion) {
        $valor = strtolower(trim((string)$convencion));
        $valor = strtr($valor, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);

        if ($valor === 'convencion enero') {
            return 'enero';
        }

        if ($valor === 'convencion mujeres') {
            return 'mujeres';
        }

        if ($valor === 'convencion jovenes') {
            return 'jovenes';
        }

        if ($valor === 'convencion hombres' || $valor === 'convencion hombre') {
            return 'hombres';
        }

        return '';
    }

    private function extraerConvencionesPersona(array $persona) {
        $convenciones = [];
        $checklistRaw = (string)($persona['Escalera_Checklist'] ?? '');

        if ($checklistRaw !== '') {
            $checklist = json_decode($checklistRaw, true);
            if (is_array($checklist) && isset($checklist['_meta']['convenciones']) && is_array($checklist['_meta']['convenciones'])) {
                foreach ($checklist['_meta']['convenciones'] as $convencion) {
                    $normalizada = $this->normalizarConvencion($convencion);
                    if ($normalizada !== '' && !in_array($normalizada, $convenciones, true)) {
                        $convenciones[] = $normalizada;
                    }
                }
            }
        }

        if (empty($convenciones)) {
            $convencionUnica = $this->normalizarConvencion($persona['Convencion'] ?? '');
            if ($convencionUnica !== '') {
                $convenciones[] = $convencionUnica;
            }
        }

        return $convenciones;
    }

    private function construirChecklistEfectivo(array $persona) {
        $ordenEtapas = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
        $indiceEtapa = array_flip($ordenEtapas);
        $checklist = [];

        $raw = (string)($persona['Escalera_Checklist'] ?? '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $checklist = $decoded;
            }
        }

        $etapaActual = trim((string)($persona['Proceso'] ?? ''));
        $indiceActual = $indiceEtapa[$etapaActual] ?? -1;

        $resultado = [];
        foreach ($ordenEtapas as $etapaNombre) {
            $resultado[$etapaNombre] = [false, false, false];
            $indiceBloque = $indiceEtapa[$etapaNombre];
            $bloqueCompletado = $indiceActual > $indiceBloque;
            $bloqueActivo = $indiceActual === $indiceBloque;

            for ($i = 0; $i < 3; $i++) {
                $persistido = null;
                if (isset($checklist[$etapaNombre]) && is_array($checklist[$etapaNombre]) && array_key_exists($i, $checklist[$etapaNombre])) {
                    $persistido = !empty($checklist[$etapaNombre][$i]);
                }

                $resultado[$etapaNombre][$i] = $persistido !== null
                    ? $persistido
                    : ($bloqueCompletado || ($bloqueActivo && $i === 0));
            }
        }

        return $resultado;
    }

    private function calcularAvanceSemestralPorMinisterio(array $ministerioIds, array $personas, $fechaReferencia) {
        $timestamp = strtotime((string)$fechaReferencia);
        if ($timestamp === false) {
            $timestamp = time();
        }

        $anio = (int)date('Y', $timestamp);
        $mes = (int)date('n', $timestamp);
        $esPrimerSemestre = $mes <= 6;

        $fechaInicio = $esPrimerSemestre
            ? sprintf('%04d-01-01', $anio)
            : sprintf('%04d-07-01', $anio);
        $fechaFin = $esPrimerSemestre
            ? sprintf('%04d-06-30', $anio)
            : sprintf('%04d-12-31', $anio);

        $avance = [];
        foreach ($ministerioIds as $idMinisterio) {
            $avance[$idMinisterio] = [
                'celula' => 0,
                'iglesia' => 0,
                'total' => 0
            ];
        }

        foreach ($personas as $persona) {
            $idMinisterio = (int)($persona['Id_Ministerio'] ?? 0);
            if (!isset($avance[$idMinisterio])) {
                continue;
            }

            $fechaRegistro = substr((string)($persona['Fecha_Registro'] ?? ''), 0, 10);
            if ($fechaRegistro === '' || $fechaRegistro < $fechaInicio || $fechaRegistro > $fechaFin) {
                continue;
            }

            $avance[$idMinisterio]['total']++;

            $tipoReunion = $this->normalizarTipoReunion($persona['Tipo_Reunion'] ?? '');
            if (strpos($tipoReunion, 'celula') !== false) {
                $avance[$idMinisterio]['celula']++;
            }

            if (strpos($tipoReunion, 'domingo') !== false || strpos($tipoReunion, 'iglesia') !== false || strpos($tipoReunion, 'somos uno') !== false || strpos($tipoReunion, 'somosuno') !== false || strpos($tipoReunion, 'viernes') !== false || strpos($tipoReunion, 'otro') !== false) {
                $avance[$idMinisterio]['iglesia']++;
            }
        }

        return [
            'inicio' => $fechaInicio,
            'fin' => $fechaFin,
            'titulo' => $esPrimerSemestre ? ('1er semestre ' . $anio) : ('2do semestre ' . $anio),
            'avance' => $avance
        ];
    }

    private function calcularMetricasMinisterio(array $ministerioIds, array $personas, $fechaInicio, $fechaFin) {
        $metricas = [];

        foreach ($ministerioIds as $idMinisterio) {
            $metricas[$idMinisterio] = [
                'celulas' => 0,
                'lideres_celula' => 0,
                'asistentes_celula' => 0,
                'ganados_semana_total' => 0,
                'ganados_semana_celula' => 0,
                'ganados_semana_domingo' => 0,
                'convenciones' => [
                    'enero' => 0,
                    'mujeres' => 0,
                    'jovenes' => 0,
                    'hombres' => 0
                ],
                'escalera' => [
                    'Ganar' => [
                        'Primer contacto' => 0,
                        'Asignacion a lideres y ministerio' => 0,
                        'Fonovisita' => 0,
                        'Visita' => 0,
                        'Asignacion a una celula' => 0,
                        'No se dispone' => 0
                    ],
                    'Consolidar' => ['Universidad de la vida' => 0, 'Encuentro' => 0, 'Bautismo' => 0],
                    'Discipular' => ['Capacitacion destino nivel 1' => 0, 'Capacitacion destino nivel 2' => 0, 'Capacitacion destino nivel 3' => 0],
                    'Enviar' => ['Celula' => 0]
                ]
            ];
        }

        foreach ($personas as $persona) {
            $idMinisterio = (int)($persona['Id_Ministerio'] ?? 0);
            if (!isset($metricas[$idMinisterio])) {
                continue;
            }

            if ((int)($persona['Id_Rol'] ?? 0) === 3) {
                $metricas[$idMinisterio]['lideres_celula']++;
            }

            $rolNombre = $this->normalizarTipoReunion($persona['Nombre_Rol'] ?? '');
            if ($rolNombre !== '' && strpos($rolNombre, 'asistente') !== false) {
                $metricas[$idMinisterio]['asistentes_celula']++;
            }

            $convencionesPersona = $this->extraerConvencionesPersona($persona);
            foreach ($convencionesPersona as $convencion) {
                if (isset($metricas[$idMinisterio]['convenciones'][$convencion])) {
                    $metricas[$idMinisterio]['convenciones'][$convencion]++;
                }
            }

            $fechaRegistro = substr((string)($persona['Fecha_Registro'] ?? ''), 0, 10);
            if ($fechaRegistro !== '' && $fechaRegistro >= $fechaInicio && $fechaRegistro <= $fechaFin) {
                $metricas[$idMinisterio]['ganados_semana_total']++;
                $tipoReunion = $this->normalizarTipoReunion($persona['Tipo_Reunion'] ?? '');
                if (strpos($tipoReunion, 'celula') !== false) {
                    $metricas[$idMinisterio]['ganados_semana_celula']++;
                }
                if (strpos($tipoReunion, 'domingo') !== false || strpos($tipoReunion, 'iglesia') !== false || strpos($tipoReunion, 'somos uno') !== false || strpos($tipoReunion, 'somosuno') !== false || strpos($tipoReunion, 'viernes') !== false || strpos($tipoReunion, 'otro') !== false) {
                    $metricas[$idMinisterio]['ganados_semana_domingo']++;
                }
            }

            $checklist = $this->construirChecklistEfectivo($persona);
            $mapa = [
                'Ganar' => [
                    0 => 'Primer contacto',
                    1 => 'Asignacion a lideres y ministerio',
                    2 => 'Fonovisita',
                    3 => 'Visita',
                    4 => 'Asignacion a una celula',
                    5 => 'No se dispone'
                ],
                'Consolidar' => ['Universidad de la vida', 'Encuentro', 'Bautismo'],
                'Discipular' => ['Capacitacion destino nivel 1', 'Capacitacion destino nivel 2', 'Capacitacion destino nivel 3'],
                'Enviar' => [2 => 'Celula']
            ];

            foreach ($mapa as $etapa => $subprocesos) {
                foreach ($subprocesos as $indice => $nombre) {
                    if (!empty($checklist[$etapa][$indice])) {
                        $metricas[$idMinisterio]['escalera'][$etapa][$nombre]++;
                    }
                }
            }
        }

        return $metricas;
    }

    private function calcularEstadoMetaPorPorcentaje($porcentaje) {
        $porcentaje = (float)$porcentaje;
        if ($porcentaje >= 85) {
            return [
                'key' => 'verde',
                'label' => 'Va bien',
                'color' => '#1f9d55'
            ];
        }

        if ($porcentaje >= 60) {
            return [
                'key' => 'amarillo',
                'label' => 'En riesgo',
                'color' => '#d9a600'
            ];
        }

        return [
            'key' => 'rojo',
            'label' => 'Crítico',
            'color' => '#d64545'
        ];
    }

    private function calcularAvanceMetasTiempoPorMinisterio(array $ministerioIds, array $personas, array $metasDetalle, $fechaReferencia) {
        $timestampRef = strtotime((string)$fechaReferencia);
        if ($timestampRef === false) {
            $timestampRef = time();
        }

        [$semanaInicio, $semanaFin] = $this->calcularRangoSemanaDomingoADomingo(date('Y-m-d', $timestampRef));
        $mesInicio = date('Y-m-01', $timestampRef);
        $mesFin = date('Y-m-t', $timestampRef);

        $conteo = [];
        foreach ($ministerioIds as $idMinisterioTmp) {
            $conteo[(int)$idMinisterioTmp] = [
                'semana' => 0,
                'mes' => 0,
                'anio' => 0,
            ];
        }

        foreach ($personas as $persona) {
            $idMinisterio = (int)($persona['Id_Ministerio'] ?? 0);
            if (!isset($conteo[$idMinisterio])) {
                continue;
            }

            $fechaRegistro = substr((string)($persona['Fecha_Registro'] ?? ''), 0, 10);
            if ($fechaRegistro === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRegistro)) {
                continue;
            }

            if ($fechaRegistro >= $semanaInicio && $fechaRegistro <= $semanaFin) {
                $conteo[$idMinisterio]['semana']++;
            }

            if ($fechaRegistro >= $mesInicio && $fechaRegistro <= $mesFin) {
                $conteo[$idMinisterio]['mes']++;
            }

            $anioMetaMinisterio = (int)($metasDetalle[$idMinisterio]['anio_meta'] ?? date('Y', $timestampRef));
            if ($anioMetaMinisterio < 2000 || $anioMetaMinisterio > 2100) {
                $anioMetaMinisterio = (int)date('Y', $timestampRef);
            }
            $anioRegistro = (int)substr($fechaRegistro, 0, 4);
            if ($anioRegistro === $anioMetaMinisterio) {
                $conteo[$idMinisterio]['anio']++;
            }
        }

        $resultado = [];
        foreach ($ministerioIds as $idMinisterioTmp) {
            $idMinisterio = (int)$idMinisterioTmp;
            $meta = $metasDetalle[$idMinisterio] ?? [];

            $metaAnual = max(0, (int)($meta['meta_anual'] ?? 0));
            $metaMensual = max(0, (int)($meta['meta_mensual'] ?? 0));
            $metaSemanal = max(0, (int)($meta['meta_semanal'] ?? 0));
            $anioMeta = (int)($meta['anio_meta'] ?? date('Y', $timestampRef));
            if ($anioMeta < 2000 || $anioMeta > 2100) {
                $anioMeta = (int)date('Y', $timestampRef);
            }

            if ($metaAnual <= 0) {
                $metaAnual = max(0, (int)(($meta['meta_ganados_s1'] ?? 0) + ($meta['meta_ganados_s2'] ?? 0)));
            }
            if ($metaMensual <= 0 && $metaAnual > 0) {
                $metaMensual = (int)round($metaAnual / 12);
            }
            if ($metaSemanal <= 0 && $metaAnual > 0) {
                $metaSemanal = (int)ceil($metaAnual / 52);
            }

            $logradoSemana = (int)($conteo[$idMinisterio]['semana'] ?? 0);
            $logradoMes = (int)($conteo[$idMinisterio]['mes'] ?? 0);
            $logradoAnio = (int)($conteo[$idMinisterio]['anio'] ?? 0);

            $porcentajeSemana = $metaSemanal > 0 ? min(200, round(($logradoSemana / $metaSemanal) * 100, 1)) : 0;
            $porcentajeMes = $metaMensual > 0 ? min(200, round(($logradoMes / $metaMensual) * 100, 1)) : 0;
            $porcentajeAnio = $metaAnual > 0 ? min(200, round(($logradoAnio / $metaAnual) * 100, 1)) : 0;

            $diasSemanaTranscurridos = (int)floor((strtotime(date('Y-m-d', $timestampRef)) - strtotime($semanaInicio)) / 86400) + 1;
            $diasSemanaTranscurridos = max(1, min(7, $diasSemanaTranscurridos));
            $esperadoSemana = $metaSemanal > 0 ? (int)round($metaSemanal * ($diasSemanaTranscurridos / 7)) : 0;

            $diasMesTotal = (int)date('t', $timestampRef);
            $diasMesTranscurridos = (int)date('j', $timestampRef);
            $esperadoMes = $metaMensual > 0 ? (int)round($metaMensual * ($diasMesTranscurridos / max(1, $diasMesTotal))) : 0;

            $inicioAnioMeta = strtotime($anioMeta . '-01-01');
            $finAnioMeta = strtotime($anioMeta . '-12-31');
            $diasAnioTotal = (int)floor(($finAnioMeta - $inicioAnioMeta) / 86400) + 1;
            $fechaRefDia = strtotime(date('Y-m-d', $timestampRef));
            if ((int)date('Y', $timestampRef) < $anioMeta) {
                $diasAnioTranscurridos = 0;
            } elseif ((int)date('Y', $timestampRef) > $anioMeta) {
                $diasAnioTranscurridos = $diasAnioTotal;
            } else {
                $diasAnioTranscurridos = (int)floor(($fechaRefDia - $inicioAnioMeta) / 86400) + 1;
                $diasAnioTranscurridos = max(1, min($diasAnioTotal, $diasAnioTranscurridos));
            }
            $esperadoAnio = $metaAnual > 0 ? (int)round($metaAnual * ($diasAnioTranscurridos / max(1, $diasAnioTotal))) : 0;

            $resultado[$idMinisterio] = [
                'semana' => [
                    'meta' => $metaSemanal,
                    'logrado' => $logradoSemana,
                    'porcentaje' => $porcentajeSemana,
                    'esperado' => $esperadoSemana,
                    'justo_a_tiempo' => $logradoSemana >= $esperadoSemana,
                    'estado' => $this->calcularEstadoMetaPorPorcentaje($porcentajeSemana),
                    'rango' => ['inicio' => $semanaInicio, 'fin' => $semanaFin]
                ],
                'mes' => [
                    'meta' => $metaMensual,
                    'logrado' => $logradoMes,
                    'porcentaje' => $porcentajeMes,
                    'esperado' => $esperadoMes,
                    'justo_a_tiempo' => $logradoMes >= $esperadoMes,
                    'estado' => $this->calcularEstadoMetaPorPorcentaje($porcentajeMes),
                    'periodo' => ['inicio' => $mesInicio, 'fin' => $mesFin]
                ],
                'anio' => [
                    'meta' => $metaAnual,
                    'logrado' => $logradoAnio,
                    'porcentaje' => $porcentajeAnio,
                    'esperado' => $esperadoAnio,
                    'justo_a_tiempo' => $logradoAnio >= $esperadoAnio,
                    'estado' => $this->calcularEstadoMetaPorPorcentaje($porcentajeAnio),
                    'anio_meta' => $anioMeta
                ]
            ];
        }

        return $resultado;
    }

    public function index() {
        if (!AuthController::puede('ministerios:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $returnUrl = $this->normalizarUrlRetorno($_GET['return_url'] ?? null);
        $fechaReferencia = $_GET['fecha_referencia'] ?? date('Y-m-d');
        [$fechaInicio, $fechaFin] = $this->calcularRangoSemanaDomingoADomingo($fechaReferencia);

        // Generar filtro según el rol del usuario
        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        
        // Obtener ministerios con aislamiento de rol
        $ministerios = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);

        $ministerioIds = array_map(static function ($ministerio) {
            return (int)($ministerio['Id_Ministerio'] ?? 0);
        }, $ministerios);

        $miembros = $this->personaModel->getActivosByMinisterioIds($ministerioIds);
        $personasVisibles = $this->personaModel->getAllWithRole($filtroPersonas, null, 'Activo');
        $metricasMinisterio = $this->calcularMetricasMinisterio($ministerioIds, $personasVisibles, $fechaInicio, $fechaFin);
        $metasDetalle = $this->ministerioModel->getMetasDetalleByMinisterioIds($ministerioIds);
        $avanceMetasTiempo = $this->calcularAvanceMetasTiempoPorMinisterio($ministerioIds, $personasVisibles, $metasDetalle, $fechaReferencia);

        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $celulasVisibles = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);
        foreach ($celulasVisibles as $celula) {
            $idMinisterioLider = (int)($celula['Id_Ministerio_Lider'] ?? 0);
            if ($idMinisterioLider > 0 && isset($metricasMinisterio[$idMinisterioLider])) {
                $metricasMinisterio[$idMinisterioLider]['celulas']++;
            }
        }

        $miembrosPorMinisterio = [];
        foreach ($miembros as $miembro) {
            $idMinisterio = (int)($miembro['Id_Ministerio'] ?? 0);
            if ($idMinisterio <= 0) {
                continue;
            }

            if (!isset($miembrosPorMinisterio[$idMinisterio])) {
                $miembrosPorMinisterio[$idMinisterio] = [];
            }
            $miembrosPorMinisterio[$idMinisterio][] = $miembro;
        }

        $sections = [];
        foreach ($ministerios as $ministerio) {
            $idMinisterio = (int)($ministerio['Id_Ministerio'] ?? 0);
            $miembrosMinisterio = $miembrosPorMinisterio[$idMinisterio] ?? [];

            $rows = [];
            $nro = 1;
            foreach ($miembrosMinisterio as $miembro) {
                $nombreCompleto = trim(((string)($miembro['Nombre'] ?? '')) . ' ' . ((string)($miembro['Apellido'] ?? '')));
                $fechaRegistro = substr((string)($miembro['Fecha_Registro'] ?? ''), 0, 10);
                $esGanadoSemanaTotal = $fechaRegistro !== '' && $fechaRegistro >= $fechaInicio && $fechaRegistro <= $fechaFin;

                $tipoReunionNorm = $this->normalizarTipoReunion($miembro['Tipo_Reunion'] ?? '');
                $rolNombreNorm = $this->normalizarTipoReunion($miembro['Nombre_Rol'] ?? '');
                $convencionesNorm = $this->extraerConvencionesPersona($miembro);
                $checklist = $this->construirChecklistEfectivo($miembro);

                $esLiderCelula = ((int)($miembro['Id_Rol'] ?? 0) === 3) || (strpos($rolNombreNorm, 'lider de celula') !== false);
                $esLider12 = ((int)($miembro['Id_Rol'] ?? 0) === 8)
                    || (strpos($rolNombreNorm, 'lider de 12') !== false)
                    || (strpos($rolNombreNorm, 'lider 12') !== false)
                    || (strpos($rolNombreNorm, 'lideres de 12') !== false);
                $esAsistenteCelula = strpos($rolNombreNorm, 'asistente') !== false;
                $tieneCelula = trim((string)($miembro['Nombre_Celula'] ?? '')) !== '';

                $rows[] = [
                    'nro' => $nro++,
                    'id_persona' => (int)$miembro['Id_Persona'],
                    'nombre' => $nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre',
                    'rol' => (string)($miembro['Nombre_Rol'] ?? 'Sin rol'),
                    'telefono' => (string)($miembro['Telefono'] ?? ''),
                    'direccion' => (string)($miembro['Direccion'] ?? ''),
                    'genero' => (string)($miembro['Genero'] ?? ''),
                    'id_lider' => (int)($miembro['Id_Lider'] ?? 0),
                    'nombre_lider' => trim((string)($miembro['Nombre_Lider'] ?? '')),
                    'documento' => (string)($miembro['Numero_Documento'] ?? ''),
                    'celula' => (string)($miembro['Nombre_Celula'] ?? ''),
                    'tipo_reunion' => (string)($miembro['Tipo_Reunion'] ?? ''),
                    'fecha_registro' => (string)($miembro['Fecha_Registro'] ?? ''),
                    'match_total_personas' => true,
                    'match_celulas' => $tieneCelula,
                    'match_lideres_celula' => $esLiderCelula,
                    'match_lideres_12' => $esLider12,
                    'match_asistentes_celula' => $esAsistenteCelula,
                    'match_ganados_semana_total' => $esGanadoSemanaTotal,
                    'match_ganados_semana_celula' => $esGanadoSemanaTotal && strpos($tipoReunionNorm, 'celula') !== false,
                    'match_ganados_semana_domingo' => $esGanadoSemanaTotal && (
                        strpos($tipoReunionNorm, 'domingo') !== false
                        || strpos($tipoReunionNorm, 'iglesia') !== false
                        || strpos($tipoReunionNorm, 'somos uno') !== false
                        || strpos($tipoReunionNorm, 'somosuno') !== false
                        || strpos($tipoReunionNorm, 'viernes') !== false
                        || strpos($tipoReunionNorm, 'otro') !== false
                    ),
                    'match_escalera_uv' => !empty($checklist['Consolidar'][0]),
                    'match_escalera_encuentro' => !empty($checklist['Consolidar'][1]),
                    'match_escalera_destino_n1' => !empty($checklist['Discipular'][0]),
                    'match_escalera_destino_n2' => !empty($checklist['Discipular'][1]),
                    'match_escalera_destino_n3' => !empty($checklist['Discipular'][2]),
                    'match_convencion_enero' => in_array('enero', $convencionesNorm, true),
                    'match_convencion_mujeres' => in_array('mujeres', $convencionesNorm, true),
                    'match_convencion_jovenes' => in_array('jovenes', $convencionesNorm, true),
                    'match_convencion_hombres' => in_array('hombres', $convencionesNorm, true),
                    'match_convencion_total' => !empty($convencionesNorm)
                ];
            }

            $sections[] = [
                'id_ministerio' => $idMinisterio,
                'label' => (string)($ministerio['Nombre_Ministerio'] ?? 'Ministerio sin nombre'),
                'descripcion' => (string)($ministerio['Descripcion'] ?? ''),
                'rows' => $rows,
                'total_personas' => count($rows),
                'metricas' => $metricasMinisterio[$idMinisterio] ?? null,
                'metas_detalle' => $metasDetalle[$idMinisterio] ?? null,
                'avance_metas_tiempo' => $avanceMetasTiempo[$idMinisterio] ?? null
            ];
        }

        $this->view('discipular/ministerios/lista', [
            'ministerios' => $ministerios,
            'sections' => $sections,
            'fecha_referencia' => $fechaReferencia,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'meta_guardada' => ($_GET['meta_guardada'] ?? '') === '1',
            'return_url' => $returnUrl
        ]);
    }

    private function obtenerCandidatosLideresPrincipales() {
        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        $filas = $this->personaModel->getCandidatosLideresPrincipalesRows($filtroPersonas);
        $candidatos = [];

        foreach ($filas as $persona) {
            $idPersona = (int)($persona['Id_Persona'] ?? 0);
            if ($idPersona <= 0) {
                continue;
            }

            $jerarquia = $this->personaModel->getJerarquiaByRol((int)($persona['Id_Rol'] ?? 0));
            if (!in_array($jerarquia, ['pastor', 'lider_12', 'lider_144', 'lider_celula'], true)) {
                continue;
            }

            $nombre = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));
            $candidatos[] = [
                'id_persona' => $idPersona,
                'nombre' => $nombre !== '' ? $nombre : ('Persona ' . $idPersona),
                'rol' => (string)($persona['Nombre_Rol'] ?? 'Sin rol'),
                'genero' => (string)($persona['Genero'] ?? ''),
                'id_ministerio' => (int)($persona['Id_Ministerio'] ?? 0),
                'nombre_ministerio' => (string)($persona['Nombre_Ministerio'] ?? ''),
            ];
        }

        usort($candidatos, static function($a, $b) {
            return strcasecmp((string)($a['nombre'] ?? ''), (string)($b['nombre'] ?? ''));
        });

        return $candidatos;
    }

    /**
     * Datos compactos para poblar el selector de asignación en el cliente (misma lógica que las <option>).
     *
     * @param array<int, array<string, mixed>> $personas
     * @return array<int, array<string, mixed>>
     */
    private function serializarPersonasAsignablesParaCliente(array $personas): array {
        $items = [];

        foreach ($personas as $persona) {
            $idPersona = (int)($persona['Id_Persona'] ?? 0);
            if ($idPersona <= 0) {
                continue;
            }

            $idRol = (int)($persona['Id_Rol'] ?? 0);
            $nombreRol = (string)($persona['Nombre_Rol'] ?? '');
            $nombre = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));
            $documento = trim((string)($persona['Numero_Documento'] ?? ''));
            $telefono = trim((string)($persona['Telefono'] ?? ''));
            $email = trim((string)($persona['Email'] ?? ''));
            $nombreLider = trim((string)($persona['Nombre_Lider'] ?? ''));
            $jerarquia = (string)($persona['_jerarquia'] ?? $this->personaModel->getJerarquiaByRol($idRol));
            $esLider12 = 0;
            $nombreRolNorm = strtolower($nombreRol);
            if (
                $idRol === 8
                || strpos($nombreRolNorm, 'lider de 12') !== false
                || strpos($nombreRolNorm, 'lider 12') !== false
                || strpos($nombreRolNorm, 'lideres de 12') !== false
            ) {
                $esLider12 = 1;
            }

            $textoBusqueda = strtolower(trim($nombre . ' ' . $documento . ' ' . $telefono . ' ' . $email . ' ' . $nombreRol . ' ' . $nombreLider));
            $etiqueta = $nombre !== '' ? $nombre : ('Persona ' . $idPersona);
            if ($documento !== '') {
                $etiqueta .= ' | CC ' . $documento;
            }
            if ($telefono !== '') {
                $etiqueta .= ' | TEL ' . $telefono;
            }
            if ($nombreLider !== '') {
                $etiqueta .= ' | Lider actual: ' . $nombreLider;
            }

            $items[] = [
                'id' => $idPersona,
                'ministerio' => (int)($persona['Id_Ministerio'] ?? 0),
                'search' => $textoBusqueda,
                'jerarquia' => $jerarquia,
                'es_lider12' => $esLider12,
                'id_lider_actual' => (int)($persona['Id_Lider'] ?? 0),
                'nombre' => $nombre !== '' ? $nombre : ('Persona ' . $idPersona),
                'documento' => $documento,
                'telefono' => $telefono,
                'email' => $email,
                'nombre_rol' => $nombreRol,
                'nombre_lider_actual' => $nombreLider,
                'etiqueta' => $etiqueta,
            ];
        }

        return $items;
    }

    private function guardarLideresPrincipalesDesdeFormulario($idMinisterio, $idLider1, $idLider2) {
        $idMinisterio = (int)$idMinisterio;
        $idLider1 = (int)$idLider1;
        $idLider2 = (int)$idLider2;
        $idRolLider12 = (int)$this->personaModel->resolverIdRolLider12();
        if ($idRolLider12 <= 0) {
            return ['ok' => false, 'message' => 'No existe un rol de Lider de 12 configurado en la tabla rol.'];
        }

        if ($idLider1 > 0 && $idLider2 > 0 && $idLider1 === $idLider2) {
            return ['ok' => false, 'message' => 'No puedes seleccionar el mismo lider en ambos campos.'];
        }

        $esMinisterioPastoralGuardar = false;
        if ($idMinisterio > 0) {
            $ministerioGuardar = $this->ministerioModel->getById($idMinisterio);
            $nombreMinGuardar = strtolower(trim((string)($ministerioGuardar['Nombre_Ministerio'] ?? '')));
            $nombreMinGuardar = strtr($nombreMinGuardar, [
                'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            ]);
            $esMinisterioPastoralGuardar = strpos($nombreMinGuardar, 'pastor') !== false
                || strpos($nombreMinGuardar, 'pastoral') !== false;
        }

        foreach ([$idLider1, $idLider2] as $idLider) {
            if ($idLider <= 0) {
                continue;
            }

            $persona = $this->personaModel->getById($idLider);
            if (empty($persona)) {
                return ['ok' => false, 'message' => 'Uno de los lideres seleccionados no existe.'];
            }

            $jerarquia = $this->personaModel->getJerarquiaByRol((int)($persona['Id_Rol'] ?? 0));
            if (!in_array($jerarquia, ['pastor', 'lider_12', 'lider_144', 'lider_celula'], true)) {
                return ['ok' => false, 'message' => 'Solo puedes seleccionar personas con rol de liderazgo.'];
            }

            if ($idMinisterio > 0 && !$esMinisterioPastoralGuardar && $jerarquia === 'pastor') {
                return ['ok' => false, 'message' => 'En este ministerio los líderes principales deben ser líderes de 12, no pastores. Use cobertura pastoral general para pastores.'];
            }
        }

        $ok = $this->ministerioModel->setLideresPrincipales($idMinisterio, $idLider1, $idLider2);
        if (!$ok) {
            return ['ok' => false, 'message' => 'No se pudo guardar la configuracion de lideres principales.'];
        }

        // Al asignarse como lideres principales del ministerio, quedan en jerarquia de lider de 12.
        // Si ya son pastores, conservan su rol pastoral.
        foreach ([$idLider1, $idLider2] as $idLider) {
            if ($idLider > 0) {
                $persona = $this->personaModel->getById($idLider);
                if (empty($persona)) {
                    continue;
                }

                $updateData = [
                    'Id_Ministerio' => $idMinisterio,
                ];

                $jerarquiaActual = $this->personaModel->getJerarquiaByRol((int)($persona['Id_Rol'] ?? 0));
                if ($jerarquiaActual !== 'pastor') {
                    $updateData['Id_Rol'] = $idRolLider12;
                }

                $this->personaModel->update($idLider, $updateData);
                if (isset($updateData['Id_Rol'])) {
                    $this->personaModel->ajustarEscaleraPorRol($idLider, (int)$updateData['Id_Rol']);
                }
            }
        }

        return ['ok' => true, 'message' => ''];
    }

    public function lideres() {
        $this->redirect('discipular/ministerios/equipo-principal');
    }

    /**
     * JSON bajo demanda: personas para asignar cupos (no embeber en la carga inicial).
     */
    public function personasAsignablesJson() {
        if (!AuthController::puede('ministerios:ver')) {
            $this->json(['error' => 'Sin permiso'], 403);
            return;
        }

        require_once APP . '/Helpers/DataIsolation.php';
        $filtroPersonas = DataIsolation::generarFiltroPersonas();

        $idMinisterio = 0;
        if (array_key_exists('id_ministerio', $_GET)) {
            $idMinisterio = (int)($_GET['id_ministerio'] ?? 0);
        } else {
            $idMinisterio = (int)(DataIsolation::getUsuarioMinisterioId() ?? 0);
        }

        $idLider = (int)($_GET['id_lider'] ?? 0);

        // Cupos: personas activas; el rol se asciende al asignar.
        $personasAsignablesRaw = $this->personaModel->getPersonasAsignablesParaEquipos(
            $filtroPersonas,
            $idMinisterio,
            false
        );
        $personasAsignablesPreparadas = [];

        foreach ($personasAsignablesRaw as $persona) {
            $idPersona = (int)($persona['Id_Persona'] ?? 0);
            if ($idPersona <= 0) {
                continue;
            }

            $persona['_jerarquia'] = $this->personaModel->getJerarquiaByRol((int)($persona['Id_Rol'] ?? 0));
            $personasAsignablesPreparadas[] = $persona;
        }

        if ($idLider > 0) {
            $personasAsignablesPreparadas = $this->filtrarPersonasAsignablesPorGeneroPastorPrincipal(
                $personasAsignablesPreparadas,
                $idMinisterio,
                $idLider
            );
        }

        $this->json($this->serializarPersonasAsignablesParaCliente($personasAsignablesPreparadas));
    }

    public function equipo12() {
        $this->equipoPrincipal();
    }

    public function equipoPrincipal() {
        if (!AuthController::puede('ministerios:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if (!$this->personaModel->tieneColumna('Numero_Cupo')) {
            $this->personaModel->ensureNumeroCupoColumnExists();
        }

        $esVistaPropiaLider12 = $this->esVistaPropiaLider12Equipo();
        $idUsuarioSesion = (int)($_SESSION['usuario_id'] ?? 0);
        $this->redirigirLider12EquipoPrincipalSiAplica($esVistaPropiaLider12, $idUsuarioSesion);

        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $ministeriosNavegacion = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);
        $numMinisterios = count($ministeriosNavegacion);
        $lideres = $this->personaModel->getResumenLideresCelulaWithRole($filtroPersonas);

        $idMinisterioFiltro = (int)($_GET['id_ministerio'] ?? 0);
        if ($esVistaPropiaLider12 && $idMinisterioFiltro <= 0 && $idUsuarioSesion > 0) {
            $personaSesion = $this->personaModel->getById($idUsuarioSesion);
            $idMinisterioFiltro = (int)($personaSesion['Id_Ministerio'] ?? 0);
        }
        $nombreMinisterioFiltro = '';

        if ($idMinisterioFiltro > 0) {
            $lideres = array_values(array_filter($lideres, static function ($lider) use ($idMinisterioFiltro) {
                return (int)($lider['Id_Ministerio'] ?? 0) === $idMinisterioFiltro;
            }));

            $ministerio = $this->ministerioModel->getById($idMinisterioFiltro);
            $nombreMinisterioFiltro = trim((string)($ministerio['Nombre_Ministerio'] ?? ''));
        }

        $lideres = $this->anexarResumenRedLideres($lideres, $idMinisterioFiltro);

        $tabCarga = strtolower(trim((string)($_GET['tab'] ?? '')));
        $buscarCarga = trim((string)($_GET['buscar'] ?? ''));
        if ($tabCarga === '') {
            $tabCarga = $idMinisterioFiltro > 0 ? 'lideres_144' : 'equipo_principal';
        }
        $cargarDiscipulosCompletos = $buscarCarga !== '' || $tabCarga === 'discipulos';

        $esGeneroMujer = static function ($genero) {
            $g = strtolower(trim((string)$genero));
            return (strpos($g, 'mujer') !== false) || (strpos($g, 'femen') !== false);
        };

        $lideresEquipoPrincipal = array_values(array_filter($lideres, static function($lider) {
            return (int)($lider['Es_Lider_12'] ?? 0) === 1;
        }));
        usort($lideresEquipoPrincipal, static function($a, $b) {
            $na = trim((string)($a['Nombre'] ?? '') . ' ' . (string)($a['Apellido'] ?? ''));
            $nb = trim((string)($b['Nombre'] ?? '') . ' ' . (string)($b['Apellido'] ?? ''));
            return strcasecmp($na, $nb);
        });

        // Separar hombres y mujeres
        $lideresHombres = array_filter($lideresEquipoPrincipal, function($l) {
            $g = strtolower(trim((string)($l['Genero'] ?? '')));
            return strpos($g, 'mujer') === false && strpos($g, 'femen') === false;
        });
        $lideresMujeres = array_filter($lideresEquipoPrincipal, function($l) {
            $g = strtolower(trim((string)($l['Genero'] ?? '')));
            return strpos($g, 'mujer') !== false || strpos($g, 'femen') !== false;
        });

        $idsLideres12 = array_values(array_map(static function($l) {
            return (int)($l['Id_Persona'] ?? 0);
        }, $lideresEquipoPrincipal));

        $idLiderPrincipal1 = 0;
        $idLiderPrincipal2 = 0;
        $cfgPrincipalesMinisterio = [];
        if ($idMinisterioFiltro > 0) {
            $lideresPrincipalesMinisterio = $this->ministerioModel->getLideresPrincipalesByMinisterioIds([$idMinisterioFiltro]);
            $cfgPrincipalesMinisterio = $lideresPrincipalesMinisterio[$idMinisterioFiltro] ?? [];
            $idLiderPrincipal1 = (int)($cfgPrincipalesMinisterio['id_lider_principal_1'] ?? 0);
            $idLiderPrincipal2 = (int)($cfgPrincipalesMinisterio['id_lider_principal_2'] ?? 0);
            foreach ([$idLiderPrincipal1, $idLiderPrincipal2] as $idLiderPrincipalMinisterio) {
                if ($idLiderPrincipalMinisterio > 0 && !in_array($idLiderPrincipalMinisterio, $idsLideres12, true)) {
                    $idsLideres12[] = $idLiderPrincipalMinisterio;
                }
            }
        }

        // En ministerio solo los 2 líderes principales reciben cupos numerados (144 bajo ellos).
        // En cobertura pastoral global, todos los líderes de 12 bajo el pastor.
        $idsLideres12CoberturaCupo = $idsLideres12;
        if ($idMinisterioFiltro > 0) {
            $idsLideres12CoberturaCupo = array_values(array_unique(array_filter([
                $idLiderPrincipal1,
                $idLiderPrincipal2,
            ], static function ($id) {
                return (int)$id > 0;
            })));
        }

        $liderazgoRed = [];
        $totalLideresCelula = 0;
        $totalLideres144 = 0;

        foreach ($lideres as $lider) {
            $idPersona = (int)($lider['Id_Persona'] ?? 0);
            if ($idPersona <= 0) {
                continue;
            }

            $esLider12 = (int)($lider['Es_Lider_12'] ?? 0) === 1;
            $esLiderCelula = (int)($lider['Es_Lider_Celula'] ?? 0) === 1;
            $idLiderSuperior = (int)($lider['Id_Lider'] ?? 0);
            $jerarquiaRol = $this->personaModel->getJerarquiaByRol((int)($lider['Id_Rol'] ?? 0));
            if ((int)($lider['Es_Lider_144'] ?? 0) === 1 || $jerarquiaRol === 'lider_144') {
                $esLider144 = true;
            } elseif ($idMinisterioFiltro > 0) {
                $esLider144 = $esLiderCelula && in_array($idLiderSuperior, $idsLideres12CoberturaCupo, true);
            } else {
                $esLider144 = $esLiderCelula && in_array($idLiderSuperior, $idsLideres12, true);
            }

            if ($esLiderCelula) {
                $totalLideresCelula++;
            }
            if ($esLider144) {
                $totalLideres144++;
            }

            $lider['es_lider_144'] = $esLider144 ? 1 : 0;
            $liderazgoRed[] = $lider;
        }

        $idsLiderazgoParaDiscipulos = [];
        foreach ($liderazgoRed as $liderTmp) {
            $idTmp = (int)($liderTmp['Id_Persona'] ?? 0);
            if ($idTmp > 0) {
                $idsLiderazgoParaDiscipulos[] = $idTmp;
            }
        }

        if ($cargarDiscipulosCompletos) {
            $discipulos = $this->construirDiscipulosRed($liderazgoRed, $filtroPersonas, $idMinisterioFiltro);
            // La red pastoral exige líder o ministerio; fichas del Tour (u otras) sin
            // asignación no salían al buscar aunque existieran en la BD.
            if ($buscarCarga !== '') {
                $idsYa = [];
                foreach ($discipulos as $d) {
                    $idD = (int)($d['Id_Persona'] ?? 0);
                    if ($idD > 0) {
                        $idsYa[$idD] = true;
                    }
                }
                foreach ($liderazgoRed as $l) {
                    $idL = (int)($l['Id_Persona'] ?? 0);
                    if ($idL > 0) {
                        $idsYa[$idL] = true;
                    }
                }
                // Respaldo amplio: cualquier persona visible que coincida y no esté ya listada
                // (cubre rol "Líder de Célula" sin célula, sin asignación, etc.).
                $fueraDeListados = $this->personaModel->buscarPersonasVisiblesNoListadasDiscipular(
                    $buscarCarga,
                    $filtroPersonas,
                    array_keys($idsYa),
                    $idMinisterioFiltro,
                    100
                );
                foreach ($fueraDeListados as $personaFuera) {
                    $idFuera = (int)($personaFuera['Id_Persona'] ?? 0);
                    if ($idFuera <= 0 || isset($idsYa[$idFuera])) {
                        continue;
                    }
                    if ((int)($personaFuera['Sin_Asignacion_Red'] ?? 0) === 1) {
                        $personaFuera['Sin_Asignacion_Red'] = 1;
                    }
                    if ((int)($personaFuera['Rol_Liderazgo_Sin_Celula'] ?? 0) === 1) {
                        $personaFuera['Rol_Liderazgo_Sin_Celula'] = 1;
                    }
                    $discipulos[] = $personaFuera;
                    $idsYa[$idFuera] = true;
                }
            }
            $totalDiscipulosTab = count($discipulos);
        } else {
            $discipulos = [];
            $totalDiscipulosTab = $this->personaModel->contarDiscipulosRedWithRole(
                $filtroPersonas,
                $idsLiderazgoParaDiscipulos,
                $idMinisterioFiltro
            );
        }

        $totalEnRedPastoral = $this->contarPersonasEnRedPastoral($liderazgoRed, $discipulos);
        if (!$cargarDiscipulosCompletos && $totalDiscipulosTab > 0) {
            $totalEnRedPastoral += $totalDiscipulosTab;
        }

        $nombreLiderPrincipal1 = '';
        $nombreLiderPrincipal2 = '';
        $candidatosLideresPrincipales = $this->obtenerCandidatosLideresPrincipales();

        $configLideresKey = $idMinisterioFiltro > 0 ? $idMinisterioFiltro : 0;
        if ($idMinisterioFiltro <= 0) {
            $lideresGuardados = $this->ministerioModel->getLideresPrincipalesByMinisterioIds([0]);
            $configLideres = $lideresGuardados[0] ?? [
                'id_lider_principal_1' => 0,
                'id_lider_principal_2' => 0,
            ];
            $idLiderPrincipal1 = (int)($configLideres['id_lider_principal_1'] ?? 0);
            $idLiderPrincipal2 = (int)($configLideres['id_lider_principal_2'] ?? 0);
        }

        $idsPersonasJerarquia = [$idLiderPrincipal1, $idLiderPrincipal2];
        foreach ($liderazgoRed as $liderRowJer) {
            $idsPersonasJerarquia[] = (int)($liderRowJer['Id_Persona'] ?? 0);
            $idsPersonasJerarquia[] = (int)($liderRowJer['Id_Lider'] ?? 0);
        }
        foreach ($discipulos as $discRowJer) {
            $idsPersonasJerarquia[] = (int)($discRowJer['Id_Lider'] ?? 0);
        }

        $personasJerarquiaMap = $this->obtenerPersonasPorIds($idsPersonasJerarquia);

        if ($idLiderPrincipal1 > 0 && !empty($personasJerarquiaMap[$idLiderPrincipal1])) {
            $personaL1 = $personasJerarquiaMap[$idLiderPrincipal1];
            $nombreLiderPrincipal1 = trim((string)($personaL1['Nombre'] ?? '') . ' ' . (string)($personaL1['Apellido'] ?? ''));
        }

        if ($idLiderPrincipal2 > 0 && !empty($personasJerarquiaMap[$idLiderPrincipal2])) {
            $personaL2 = $personasJerarquiaMap[$idLiderPrincipal2];
            $nombreLiderPrincipal2 = trim((string)($personaL2['Nombre'] ?? '') . ' ' . (string)($personaL2['Apellido'] ?? ''));
        }

        $contactoLiderPrincipal1 = $this->extraerContactoPersona($personasJerarquiaMap[$idLiderPrincipal1] ?? null);
        $contactoLiderPrincipal2 = $this->extraerContactoPersona($personasJerarquiaMap[$idLiderPrincipal2] ?? null);

        $jerarquiaPorLiderId = [];
        foreach ($personasJerarquiaMap as $idPersonaTmp => $personaTmp) {
            $jerarquiaPorLiderId[(int)$idPersonaTmp] = $this->personaModel->getJerarquiaByRol((int)($personaTmp['Id_Rol'] ?? 0));
        }

        $encabezado = $this->construirEncabezadoEquipoPrincipal($lideresEquipoPrincipal, $idMinisterioFiltro, $nombreMinisterioFiltro);
        $encabezado['ministerio_cantidad'] = $numMinisterios;
        $encabezado['equipo_principal_hombres'] = count($lideresHombres);
        $encabezado['equipo_principal_mujeres'] = count($lideresMujeres);

        $equipoDirectoPorLider = $this->construirEquipoDirectoPorLider($liderazgoRed, $discipulos);
        $idsLideresEquipoDirectoDb = $idMinisterioFiltro > 0
            ? $idsLideres12CoberturaCupo
            : array_values(array_unique(array_merge($idsLideres12, [$idLiderPrincipal1, $idLiderPrincipal2])));
        $equipoDirectoPorLider = $this->enriquecerEquipoDirectoDesdeDb($equipoDirectoPorLider, $idsLideresEquipoDirectoDb);
        $codigosJerarquia = CodigoJerarquiaHelper::construirMapaCodigos(
            $idLiderPrincipal1,
            $idLiderPrincipal2,
            $equipoDirectoPorLider
        );
        $nombreMinisterioNorm = strtolower(trim($nombreMinisterioFiltro));
        $nombreMinisterioNorm = strtr($nombreMinisterioNorm, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
        $esMinisterioPastoral = $idMinisterioFiltro > 0 && (
            strpos($nombreMinisterioNorm, 'pastor') !== false
            || strpos($nombreMinisterioNorm, 'pastoral') !== false
        );

        if ($esVistaPropiaLider12 && $idUsuarioSesion > 0 && !in_array($idUsuarioSesion, $idsLideres12CoberturaCupo, true)) {
            $idsLideres12CoberturaCupo[] = $idUsuarioSesion;
        }

        $totalesPersonasMinisterio = ['total' => 0, 'hombres' => 0, 'mujeres' => 0];
        if ($idMinisterioFiltro > 0) {
            $totalesPersonasMinisterio = $this->personaModel->contarPersonasMinisterioPorGenero(
                $idMinisterioFiltro,
                $filtroPersonas
            );
        } else {
            $totalesPersonasMinisterio = $this->personaModel->contarPersonasActivasPorGenero($filtroPersonas);
        }

        $this->view('discipular/ministerios/lideres', [
            'id_ministerio_filtro' => $idMinisterioFiltro,
            'nombre_ministerio_filtro' => $nombreMinisterioFiltro,
            'encabezado_equipo_principal' => $encabezado,
            'lideres_equipo_principal' => $lideresEquipoPrincipal,
            'lideres_equipo_hombres' => $lideresHombres,
            'lideres_equipo_mujeres' => $lideresMujeres,
            'liderazgo_red' => $liderazgoRed,
            'discipulos_red' => $discipulos,
            'personas_asignables_url' => public_app_url('discipular/ministerios/personas-asignables'),
            'jerarquia_por_lider_id' => $jerarquiaPorLiderId,
            'id_lider_principal_1' => $idLiderPrincipal1,
            'id_lider_principal_2' => $idLiderPrincipal2,
            'nombre_lider_principal_1' => $nombreLiderPrincipal1,
            'nombre_lider_principal_2' => $nombreLiderPrincipal2,
            'contacto_lider_principal_1' => $contactoLiderPrincipal1,
            'contacto_lider_principal_2' => $contactoLiderPrincipal2,
            'candidatos_lideres_principales' => $candidatosLideresPrincipales,
            'totales_tabs' => [
                'equipo_principal' => count($lideresEquipoPrincipal),
                'lideres_144' => $totalLideres144,
                'lideres_celula' => $totalLideresCelula,
                'discipulos' => $totalDiscipulosTab,
            ],
            'ministerios_navegacion' => $ministeriosNavegacion,
            'equipo_directo_por_lider' => $equipoDirectoPorLider,
            'codigos_jerarquia' => $codigosJerarquia,
            'es_ministerio_pastoral' => $esMinisterioPastoral,
            'ids_lideres_12_cobertura_cupo' => $idsLideres12CoberturaCupo,
            'es_vista_propia_lider_12' => $esVistaPropiaLider12,
            'id_usuario_sesion' => $idUsuarioSesion,
            'puede_configurar_lideres_principales' => !$esVistaPropiaLider12,
            'totales_personas_ministerio' => $totalesPersonasMinisterio,
            'total_en_red_pastoral' => $totalEnRedPastoral,
        ]);
    }

    /**
     * Personas en la red (no líderes): miembros/discípulos con líder o ministerio asignado.
     *
     * @return array<int, array<string, mixed>>
     */
    private function construirDiscipulosRed(array $liderazgoRed, string $filtroPersonas, int $idMinisterioFiltro = 0): array {
        $idsLiderazgo = [];
        foreach ($liderazgoRed as $lider) {
            $id = (int)($lider['Id_Persona'] ?? 0);
            if ($id > 0) {
                $idsLiderazgo[] = $id;
            }
        }

        return $this->personaModel->getDiscipulosRedWithRole(
            $filtroPersonas,
            $idsLiderazgo,
            $idMinisterioFiltro
        );
    }

    private function contarPersonasEnRedPastoral(array $liderazgoRed, array $discipulos): int {
        $ids = [];
        foreach ($liderazgoRed as $lider) {
            $id = (int)($lider['Id_Persona'] ?? 0);
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
        foreach ($discipulos as $persona) {
            $id = (int)($persona['Id_Persona'] ?? 0);
            if ($id > 0) {
                $ids[$id] = true;
            }
        }

        return count($ids);
    }

    private function esVistaPropiaLider12Equipo(): bool {
        require_once APP . '/Helpers/DataIsolation.php';
        if (DataIsolation::tieneAccesoTotal() || DataIsolation::esPastor()) {
            return false;
        }

        return DataIsolation::esLider12();
    }

    private function redirigirLider12EquipoPrincipalSiAplica(bool $esVistaPropiaLider12, int $idUsuarioSesion): void {
        if (!$esVistaPropiaLider12 || $idUsuarioSesion <= 0) {
            return;
        }

        $persona = $this->personaModel->getById($idUsuarioSesion);
        if (empty($persona)) {
            return;
        }

        $idMinisterioUsuario = (int)($persona['Id_Ministerio'] ?? 0);
        if ($idMinisterioUsuario <= 0) {
            return;
        }

        $idMinisterioGet = (int)($_GET['id_ministerio'] ?? 0);
        $tabGet = strtolower(trim((string)($_GET['tab'] ?? '')));
        $tabsPermitidosLider12 = ['lideres_144', 'lideres_celula', 'discipulos'];

        $paramsBase = [
            'url' => 'discipular/ministerios/equipo-principal',
            'id_ministerio' => $idMinisterioUsuario,
        ];

        if ($idMinisterioGet !== $idMinisterioUsuario) {
            header('Location: ' . PUBLIC_URL . '?' . http_build_query($paramsBase + [
                'tab' => 'lideres_144',
                'cobertura_principal' => $idUsuarioSesion,
            ]));
            exit;
        }

        if ($tabGet === '' || !in_array($tabGet, $tabsPermitidosLider12, true)) {
            header('Location: ' . PUBLIC_URL . '?' . http_build_query($paramsBase + [
                'tab' => 'lideres_144',
                'cobertura_principal' => $idUsuarioSesion,
            ]));
            exit;
        }

        if ($tabGet === 'lideres_144') {
            $coberturaGet = trim((string)($_GET['cobertura_principal'] ?? ''));
            if ($coberturaGet !== (string)$idUsuarioSesion) {
                header('Location: ' . PUBLIC_URL . '?' . http_build_query($paramsBase + [
                    'tab' => 'lideres_144',
                    'cobertura_principal' => $idUsuarioSesion,
                ]));
                exit;
            }
        }
    }

    public function lideresCelula() {
        if (!AuthController::puede('ministerios:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        $lideres = $this->personaModel->getResumenLideresCelulaWithRole($filtroPersonas);

        $idMinisterioFiltro = (int)($_GET['id_ministerio'] ?? 0);
        $nombreMinisterioFiltro = '';

        if ($idMinisterioFiltro > 0) {
            $lideres = array_values(array_filter($lideres, static function ($lider) use ($idMinisterioFiltro) {
                return (int)($lider['Id_Ministerio'] ?? 0) === $idMinisterioFiltro;
            }));

            $ministerio = $this->ministerioModel->getById($idMinisterioFiltro);
            $nombreMinisterioFiltro = trim((string)($ministerio['Nombre_Ministerio'] ?? ''));
        }

        $lideres = $this->anexarResumenRedLideres($lideres, $idMinisterioFiltro);

        $esGeneroMujer = static function ($genero) {
            $g = strtolower(trim((string)$genero));
            return (strpos($g, 'mujer') !== false) || (strpos($g, 'femen') !== false);
        };

        $lideresCelulaHombres = [];
        $lideresCelulaMujeres = [];

        foreach ($lideres as $lider) {
            if ((int)($lider['Es_Lider_Celula'] ?? 0) !== 1) {
                continue;
            }

            $nodo = $this->normalizarNodoEquipo12($lider, $esGeneroMujer);
            if (!empty($nodo['es_mujer'])) {
                $lideresCelulaMujeres[] = $nodo;
            } else {
                $lideresCelulaHombres[] = $nodo;
            }
        }

        usort($lideresCelulaHombres, [$this, 'compararNodosEquipo12']);
        usort($lideresCelulaMujeres, [$this, 'compararNodosEquipo12']);

        $this->view('discipular/ministerios/lideres_celula', [
            'lideres_celula_hombres' => $lideresCelulaHombres,
            'lideres_celula_mujeres' => $lideresCelulaMujeres,
            'id_ministerio_filtro' => $idMinisterioFiltro,
            'nombre_ministerio_filtro' => $nombreMinisterioFiltro,
        ]);
    }

    public function validarCupoLider() {
        if (!AuthController::puede('ministerios:ver')) {
            $this->json(['ok' => false, 'error' => 'No autorizado'], 403);
        }

        $idLider = (int)($_GET['id_lider'] ?? $_POST['id_lider'] ?? 0);

        if ($idLider <= 0) {
            $this->json(['ok' => false, 'error' => 'id_lider es obligatorio'], 422);
        }

        $resumenCupos = $this->personaModel->getResumenCuposNumeradosLider($idLider);
        $limiteEquipo = (int)($resumenCupos['limite_equipo'] ?? 0);

        $this->json([
            'ok' => true,
            'id_lider' => $idLider,
            'equipo_directo' => (int)($resumenCupos['equipo_directo'] ?? 0),
            'red_total' => 0,
            'limite_equipo' => $limiteEquipo,
            'cupos_disponibles' => (int)($resumenCupos['cupos_disponibles'] ?? 0),
            'cupo_lleno' => !empty($resumenCupos['cupo_lleno']),
        ]);
    }

    private function construirUrlRetornoEquipoPrincipal(int $idMinisterio = 0): string {
        $url = 'discipular/ministerios/equipo-principal';
        if ($idMinisterio > 0) {
            $url .= '&id_ministerio=' . $idMinisterio;
        }

        $tab = trim((string)($_POST['tab_retorno'] ?? ''));
        if (!in_array($tab, ['equipo_principal', 'lideres_144', 'lideres_celula', 'discipulos'], true)) {
            $modoCupo = trim((string)($_POST['modo_cupo'] ?? ''));
            if ($idMinisterio > 0 && $modoCupo === 'lider_144') {
                $tab = 'lideres_144';
            }
        }
        if ($tab !== '') {
            $url .= '&tab=' . rawurlencode($tab);
        }

        $cobertura = trim((string)($_POST['cobertura_principal_retorno'] ?? ''));
        if ($cobertura !== '' && ctype_digit($cobertura) && (int)$cobertura > 0) {
            $url .= '&cobertura_principal=' . (int)$cobertura;
        }

        $generoRed = trim((string)($_POST['genero_red_retorno'] ?? ''));
        if (in_array($generoRed, ['hombres', 'mujeres'], true)) {
            $url .= '&genero_red=' . rawurlencode($generoRed);
        }

        return $url;
    }

    public function asignarCupo() {
        if (!AuthController::esAdministrador() && !AuthController::puede('ministerios:editar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('discipular/ministerios/equipo-principal');
            return;
        }

        $this->personaModel->ensureNumeroCupoColumnExists();

        $idLider = (int)($_POST['id_lider'] ?? 0);
        $idPersona = (int)($_POST['id_persona'] ?? 0);
        $idMinisterio = (int)($_POST['id_ministerio'] ?? 0);
        $idPersonaActualSlot = (int)($_POST['id_persona_actual_slot'] ?? 0);
        $numeroCupo = (int)($_POST['numero_cupo'] ?? 0);

        $queryBase = $this->construirUrlRetornoEquipoPrincipal($idMinisterio);

        $bloqueoPastoral = $this->validarAsignacionCupoCoberturaPastoral($idMinisterio);
        if ($bloqueoPastoral !== null) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode($bloqueoPastoral));
            return;
        }

        if ($idLider <= 0 || $idPersona <= 0) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('Selecciona líder y persona para asignar cupo.'));
            return;
        }

        $persona = $this->personaModel->getById($idPersona);
        $lider = $this->personaModel->getById($idLider);
        $personaActualSlot = $idPersonaActualSlot > 0 ? $this->personaModel->getById($idPersonaActualSlot) : null;

        if (empty($persona) || empty($lider)) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('No se encontró la persona o el líder seleccionado.'));
            return;
        }

        if ($idPersonaActualSlot > 0) {
            if (empty($personaActualSlot)) {
                $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('La persona actual del cupo ya no existe.'));
                return;
            }

            if ((int)($personaActualSlot['Id_Lider'] ?? 0) !== $idLider) {
                $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('La persona actual ya no pertenece a ese cupo. Recarga la vista e inténtalo de nuevo.'));
                return;
            }

            if ($idPersonaActualSlot === $idPersona) {
                $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('Esa persona ya ocupa el cupo seleccionado.'));
                return;
            }
        }

        $validacionGenero = $this->validarGeneroCupoPastorPrincipal($idMinisterio, $idLider, $persona, $lider);
        if (!$validacionGenero['ok']) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode((string)$validacionGenero['message']));
            return;
        }

        $idLiderAnterior = (int)($persona['Id_Lider'] ?? 0);
        $yaBajoMismoLider = $idLiderAnterior > 0 && $idLiderAnterior === $idLider;
        $numeroCupoActualPersona = $this->personaModel->tieneColumna('Numero_Cupo')
            ? (int)($persona['Numero_Cupo'] ?? 0)
            : 0;

        // No bloquear si ya está bajo ese líder: suele ser un discípulo al que
        // hay que asignarle casilla (Numero_Cupo) y/o ascender el rol.
        // Solo rechazar si ya ocupa exactamente esa misma casilla y no hay ascenso.

        $ascensoRol = $this->personaModel->resolverRolAscensoPorLider(
            (int)($lider['Id_Rol'] ?? 0),
            (int)($persona['Id_Rol'] ?? 0)
        );

        if (!$ascensoRol['ok']) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode((string)($ascensoRol['message'] ?? 'No se pudo resolver el ascenso de rol.')));
            return;
        }

        if (
            $yaBajoMismoLider
            && $numeroCupo >= 1
            && $numeroCupoActualPersona === $numeroCupo
            && (int)($ascensoRol['id_rol'] ?? 0) <= 0
        ) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('Esa persona ya ocupa el cupo seleccionado.'));
            return;
        }

        $idRolParaValidar = (int)($ascensoRol['id_rol'] ?? 0) > 0
            ? (int)$ascensoRol['id_rol']
            : (int)($persona['Id_Rol'] ?? 0);

        $limiteEquipo = $this->personaModel->limiteEquipoDirectoPorJerarquiaLider($idLider);
        $resumenCupos = $this->personaModel->getResumenCuposNumeradosLider(
            $idLider,
            $yaBajoMismoLider ? $idPersona : null
        );

        // Si eligen una casilla 1–12 libre (o van a sustituir), no bloquear por conteo global.
        $casillaDestinoDisponible = false;
        if ($limiteEquipo > 0 && $numeroCupo >= 1 && $numeroCupo <= 12) {
            $excludePreCheck = array_values(array_filter([$idPersona, $idPersonaActualSlot]));
            $ocupanteCasilla = $this->personaModel->getIdPersonaEnCupoDeLider($idLider, $numeroCupo, $excludePreCheck);
            $casillaDestinoDisponible = $ocupanteCasilla <= 0 || ($idPersonaActualSlot > 0 && $ocupanteCasilla === $idPersonaActualSlot);
        }

        if (
            $limiteEquipo > 0
            && $idPersonaActualSlot <= 0
            && !$yaBajoMismoLider
            && !$casillaDestinoDisponible
            && !empty($resumenCupos['cupo_lleno'])
        ) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('Ese líder ya tiene las 12 casillas del equipo principal ocupadas.'));
            return;
        }

        $validacionJerarquia = $this->personaModel->validarAsignacionJerarquica(
            $idLider,
            $idRolParaValidar,
            $idPersona
        );

        if (!$validacionJerarquia['ok']) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode((string)($validacionJerarquia['message'] ?? 'No se pudo validar la asignación.')));
            return;
        }

        $excludeCupo = array_filter([$idPersona, $idPersonaActualSlot]);
        $usaCuposNumerados = $limiteEquipo > 0;
        if ($usaCuposNumerados) {
            if ($numeroCupo < 1 || $numeroCupo > 12) {
                $numeroCupo = $this->personaModel->primerCupoLibreDeLider($idLider, $excludeCupo);
            }
            if ($numeroCupo < 1 || $numeroCupo > 12) {
                $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('No hay casillas libres en ese equipo (máximo 12).'));
                return;
            }

            $idOcupanteCupo = $this->personaModel->getIdPersonaEnCupoDeLider($idLider, $numeroCupo, $excludeCupo);
            if ($idOcupanteCupo > 0 && $idOcupanteCupo !== $idPersona) {
                $this->personaModel->update($idOcupanteCupo, ['Numero_Cupo' => null]);
            }
        } else {
            $numeroCupo = 0;
        }

        $dataUpdate = [
            'Id_Lider' => $idLider,
        ];
        if ($this->personaModel->tieneColumna('Numero_Cupo')) {
            $dataUpdate['Numero_Cupo'] = $usaCuposNumerados ? $numeroCupo : null;
        }
        if ($this->personaModel->tieneColumna('Fecha_Asignacion_Lider')) {
            $dataUpdate['Fecha_Asignacion_Lider'] = date('Y-m-d H:i:s');
        }

        if ((int)($ascensoRol['id_rol'] ?? 0) > 0) {
            $dataUpdate['Id_Rol'] = (int)$ascensoRol['id_rol'];
        }

        if ((int)($persona['Id_Ministerio'] ?? 0) <= 0 && (int)($lider['Id_Ministerio'] ?? 0) > 0) {
            $dataUpdate['Id_Ministerio'] = (int)$lider['Id_Ministerio'];
        }

        if ($idPersonaActualSlot > 0) {
            $liberarData = ['Id_Lider' => null];
            if ($this->personaModel->tieneColumna('Numero_Cupo')) {
                $liberarData['Numero_Cupo'] = null;
            }
            $okLiberar = $this->personaModel->update($idPersonaActualSlot, $liberarData);
            if (!$okLiberar) {
                $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('No se pudo liberar a la persona que ocupaba ese cupo.'));
                return;
            }
        }

        $ok = $this->personaModel->update($idPersona, $dataUpdate);
        if (!$ok) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('No se pudo guardar la asignación del cupo.'));
            return;
        }

        if (isset($dataUpdate['Id_Rol'])) {
            $this->personaModel->ajustarEscaleraPorRol($idPersona, (int)$dataUpdate['Id_Rol']);
        }

        $mensajeExito = ($idLiderAnterior > 0 && !$yaBajoMismoLider) ? 'Cupo reasignado correctamente.' : 'Cupo asignado correctamente.';
        if ($idPersonaActualSlot > 0 && $numeroCupo > 0) {
            $mensajeExito = 'Cupo ' . $numeroCupo . ' reemplazado correctamente.';
        } elseif ($numeroCupo > 0) {
            $mensajeExito = 'Cupo ' . $numeroCupo . ' asignado correctamente.';
        }
        if (isset($dataUpdate['Id_Rol'])) {
            $jerarquiaObjetivo = (string)($ascensoRol['jerarquia_objetivo'] ?? '');
            $etiquetaRol = [
                'lider_12' => 'Lider de 12',
                'lider_144' => 'Lider de 144',
                'lider_celula' => 'Lider de celula',
            ][$jerarquiaObjetivo] ?? 'nuevo rol';
            $mensajeExito .= ' Promovido a ' . $etiquetaRol . '.';
        }
        if ($idPersonaActualSlot > 0) {
            $mensajeExito .= ' La persona anterior quedó sin líder asignado.';
        }

        $this->redirect($queryBase . '&asignacion_ok=1&asignacion_msg=' . urlencode($mensajeExito));
    }

    /**
     * Quita a una persona del cupo (equipo directo) de un líder sin borrar la persona.
     */
    public function liberarCupo() {
        if (!AuthController::esAdministrador() && !AuthController::puede('ministerios:editar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('discipular/ministerios/equipo-principal');
            return;
        }

        $idLider = (int)($_POST['id_lider'] ?? 0);
        $idPersona = (int)($_POST['id_persona'] ?? 0);
        $idMinisterio = (int)($_POST['id_ministerio'] ?? 0);
        $numeroCupo = (int)($_POST['numero_cupo'] ?? 0);

        $queryBase = $this->construirUrlRetornoEquipoPrincipal($idMinisterio);

        $bloqueoPastoral = $this->validarAsignacionCupoCoberturaPastoral($idMinisterio);
        if ($bloqueoPastoral !== null) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode($bloqueoPastoral));
            return;
        }

        if ($idLider <= 0 || $idPersona <= 0) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('Datos incompletos para quitar del cupo.'));
            return;
        }

        $persona = $this->personaModel->getById($idPersona);
        if (empty($persona)) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('La persona no existe.'));
            return;
        }

        if ((int)($persona['Id_Lider'] ?? 0) !== $idLider) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('Esa persona ya no está bajo ese líder. Recarga la página.'));
            return;
        }

        $liberarData = ['Id_Lider' => null];
        if ($this->personaModel->tieneColumna('Numero_Cupo')) {
            $liberarData['Numero_Cupo'] = null;
        }
        $this->personaModel->ensureNumeroCupoColumnExists();
        $ok = $this->personaModel->update($idPersona, $liberarData);
        if (!$ok) {
            $this->redirect($queryBase . '&asignacion_error=1&asignacion_msg=' . urlencode('No se pudo quitar la persona del cupo.'));
            return;
        }

        $msg = $numeroCupo > 0
            ? 'Casilla ' . $numeroCupo . ' quedó libre.'
            : 'Persona quitada del equipo directo.';
        $this->redirect($queryBase . '&asignacion_ok=1&asignacion_msg=' . urlencode($msg));
    }

    public function reasignarCupo() {
        if (!AuthController::esAdministrador() && !AuthController::puede('ministerios:editar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('discipular/ministerios/equipo-principal');
            return;
        }

        $idLiderNuevo = (int)($_POST['id_lider_nuevo'] ?? 0);
        $idPersona = (int)($_POST['id_persona_reasignar'] ?? 0);
        $idMinisterio = (int)($_POST['id_ministerio'] ?? 0);

        $queryBase = $this->construirUrlRetornoEquipoPrincipal($idMinisterio);

        if ($idLiderNuevo <= 0 || $idPersona <= 0) {
            $this->redirect($queryBase . '&reasignacion_error=1&reasignacion_msg=' . urlencode('Selecciona persona y nuevo líder para reasignar.'));
            return;
        }

        $persona = $this->personaModel->getById($idPersona);
        $liderNuevo = $this->personaModel->getById($idLiderNuevo);

        if (empty($persona) || empty($liderNuevo)) {
            $this->redirect($queryBase . '&reasignacion_error=1&reasignacion_msg=' . urlencode('No se encontró la persona o el líder seleccionado.'));
            return;
        }

        $validacionGenero = $this->validarGeneroCupoPastorPrincipal($idMinisterio, $idLiderNuevo, $persona, $liderNuevo);
        if (!$validacionGenero['ok']) {
            $this->redirect($queryBase . '&reasignacion_error=1&reasignacion_msg=' . urlencode((string)$validacionGenero['message']));
            return;
        }

        $idLiderActual = (int)($persona['Id_Lider'] ?? 0);
        if ($idLiderActual <= 0) {
            $this->redirect($queryBase . '&reasignacion_error=1&reasignacion_msg=' . urlencode('La persona no tiene líder actual para reasignar.'));
            return;
        }

        if ($idLiderActual === $idLiderNuevo) {
            $this->redirect($queryBase . '&reasignacion_error=1&reasignacion_msg=' . urlencode('La persona ya está asignada a ese líder.'));
            return;
        }

        $ascensoRol = $this->personaModel->resolverRolAscensoPorLider(
            (int)($liderNuevo['Id_Rol'] ?? 0),
            (int)($persona['Id_Rol'] ?? 0)
        );

        if (!$ascensoRol['ok']) {
            $this->redirect($queryBase . '&reasignacion_error=1&reasignacion_msg=' . urlencode((string)($ascensoRol['message'] ?? 'No se pudo resolver el ascenso de rol.')));
            return;
        }

        $idRolParaValidar = (int)($ascensoRol['id_rol'] ?? 0) > 0
            ? (int)$ascensoRol['id_rol']
            : (int)($persona['Id_Rol'] ?? 0);

        $resumenCupos = $this->personaModel->getResumenCuposNumeradosLider($idLiderNuevo, $idPersona);

        if (!empty($resumenCupos['cupo_lleno'])) {
            $this->redirect($queryBase . '&reasignacion_error=1&reasignacion_msg=' . urlencode('El nuevo líder no tiene casillas libres en su equipo principal (máximo 12).'));
            return;
        }

        $validacionJerarquia = $this->personaModel->validarAsignacionJerarquica(
            $idLiderNuevo,
            $idRolParaValidar,
            $idPersona
        );

        if (!$validacionJerarquia['ok']) {
            $this->redirect($queryBase . '&reasignacion_error=1&reasignacion_msg=' . urlencode((string)($validacionJerarquia['message'] ?? 'No se pudo validar la reasignación.')));
            return;
        }

        $dataUpdate = [
            'Id_Lider' => $idLiderNuevo,
        ];

        if ((int)($ascensoRol['id_rol'] ?? 0) > 0) {
            $dataUpdate['Id_Rol'] = (int)$ascensoRol['id_rol'];
        }

        if ((int)($liderNuevo['Id_Ministerio'] ?? 0) > 0) {
            $dataUpdate['Id_Ministerio'] = (int)$liderNuevo['Id_Ministerio'];
        }

        $ok = $this->personaModel->update($idPersona, $dataUpdate);
        if (!$ok) {
            $this->redirect($queryBase . '&reasignacion_error=1&reasignacion_msg=' . urlencode('No se pudo guardar la reasignación.'));
            return;
        }

        if (isset($dataUpdate['Id_Rol'])) {
            $this->personaModel->ajustarEscaleraPorRol($idPersona, (int)$dataUpdate['Id_Rol']);
        }

        $mensajeExito = 'Reasignacion realizada correctamente.';
        if (isset($dataUpdate['Id_Rol'])) {
            $jerarquiaObjetivo = (string)($ascensoRol['jerarquia_objetivo'] ?? '');
            $etiquetaRol = [
                'lider_12' => 'Lider de 12',
                'lider_144' => 'Lider de 144',
                'lider_celula' => 'Lider de celula',
            ][$jerarquiaObjetivo] ?? 'nuevo rol';
            $mensajeExito .= ' Reasignado y promovido a ' . $etiquetaRol . '.';
        }

        $this->redirect($queryBase . '&reasignacion_ok=1&reasignacion_msg=' . urlencode($mensajeExito));
    }

    private function construirRedEquipo12(array $lideres, callable $esGeneroMujer) {
        $equiposPorId = [];

        foreach ($lideres as $lider) {
            if ((int)($lider['Es_Lider_12'] ?? 0) !== 1) {
                continue;
            }

            $idPersona = (int)($lider['Id_Persona'] ?? 0);
            if ($idPersona <= 0) {
                continue;
            }

            $equiposPorId[$idPersona] = [
                'lider' => $this->normalizarNodoEquipo12($lider, $esGeneroMujer)
            ];
        }

        $equipos12Hombres = [];
        $equipos12Mujeres = [];

        foreach ($equiposPorId as $equipo) {
            if (!empty($equipo['lider']['es_mujer'])) {
                $equipos12Mujeres[] = $equipo;
            } else {
                $equipos12Hombres[] = $equipo;
            }
        }

        usort($equipos12Hombres, [$this, 'compararEquipos12']);
        usort($equipos12Mujeres, [$this, 'compararEquipos12']);

        return [
            'equipos_12_hombres' => $equipos12Hombres,
            'equipos_12_mujeres' => $equipos12Mujeres,
            'resumen' => [
                'total_equipos_12' => count($equiposPorId),
                'total_hombres' => count($equipos12Hombres),
                'total_mujeres' => count($equipos12Mujeres),
            ],
        ];
    }

    private function normalizarGeneroPersona($generoRaw) {
        $genero = strtolower(trim((string)$generoRaw));
        if ($genero === '') {
            return 'sin_genero';
        }
        return (strpos($genero, 'mujer') !== false || strpos($genero, 'femen') !== false) ? 'mujer' : 'hombre';
    }

    private function validarGeneroCupoPastorPrincipal($idMinisterio, $idLider, array $persona, array $lider) {
        $idMinisterio = (int)$idMinisterio;
        $idLider = (int)$idLider;
        if ($idLider <= 0) {
            return ['ok' => true, 'message' => ''];
        }

        $configKey = $idMinisterio > 0 ? $idMinisterio : 0;
        $config = $this->ministerioModel->getLideresPrincipalesByMinisterioIds([$configKey]);
        $cfgMinisterio = $config[$configKey] ?? null;
        if (!is_array($cfgMinisterio)) {
            return ['ok' => true, 'message' => ''];
        }

        $idPrincipal1 = (int)($cfgMinisterio['id_lider_principal_1'] ?? 0);
        $idPrincipal2 = (int)($cfgMinisterio['id_lider_principal_2'] ?? 0);
        $esPastorPrincipal = ($idLider === $idPrincipal1 || $idLider === $idPrincipal2);
        if (!$esPastorPrincipal) {
            return ['ok' => true, 'message' => ''];
        }

        $generoPersona = $this->normalizarGeneroPersona($persona['Genero'] ?? '');
        $generoLider = $this->normalizarGeneroPersona($lider['Genero'] ?? '');
        if ($generoPersona === 'sin_genero' || $generoLider === 'sin_genero') {
            return ['ok' => false, 'message' => 'No se puede asignar por cupo pastoral sin género definido en líder y persona.'];
        }

        if ($generoPersona !== $generoLider) {
            $etiquetaLider = $idLider === $idPrincipal2 ? 'pastora principal' : 'pastor principal';
            return ['ok' => false, 'message' => 'En Red ' . ($idLider === $idPrincipal2 ? 'Mujeres' : 'Hombres') . ' solo puedes asignar personas del mismo género de la ' . $etiquetaLider . '.'];
        }

        return ['ok' => true, 'message' => ''];
    }

    /**
     * @param array<int, array<string, mixed>> $personas
     * @return array<int, array<string, mixed>>
     */
    private function filtrarPersonasAsignablesPorGeneroPastorPrincipal(array $personas, int $idMinisterio, int $idLider): array {
        $configKey = $idMinisterio > 0 ? $idMinisterio : 0;
        $config = $this->ministerioModel->getLideresPrincipalesByMinisterioIds([$configKey]);
        $cfgMinisterio = $config[$configKey] ?? null;
        if (!is_array($cfgMinisterio)) {
            return $personas;
        }

        $idPrincipal1 = (int)($cfgMinisterio['id_lider_principal_1'] ?? 0);
        $idPrincipal2 = (int)($cfgMinisterio['id_lider_principal_2'] ?? 0);
        if ($idLider !== $idPrincipal1 && $idLider !== $idPrincipal2) {
            return $personas;
        }

        $lider = $this->personaModel->getById($idLider);
        if (empty($lider)) {
            return $personas;
        }

        $generoLider = $this->normalizarGeneroPersona($lider['Genero'] ?? '');
        if ($generoLider === 'sin_genero') {
            return $personas;
        }

        return array_values(array_filter($personas, function ($persona) use ($generoLider) {
            $generoPersona = $this->normalizarGeneroPersona($persona['Genero'] ?? '');
            return $generoPersona !== 'sin_genero' && $generoPersona === $generoLider;
        }));
    }

    private function normalizarNodoEquipo12(array $lider, callable $esGeneroMujer) {
        $nombre = trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? ''));

        return [
            'id_persona' => (int)($lider['Id_Persona'] ?? 0),
            'nombre' => $nombre !== '' ? $nombre : 'Sin nombre',
            'telefono' => trim((string)($lider['Telefono'] ?? '')),
            'direccion' => trim((string)($lider['Direccion'] ?? '')),
            'ministerio' => trim((string)($lider['Nombre_Ministerio'] ?? '')),
            'id_lider' => (int)($lider['Id_Lider'] ?? 0),
            'nombre_lider' => trim((string)($lider['Nombre_Lider'] ?? '')),
            'tipo_liderazgo' => trim((string)($lider['Tipo_Liderazgo'] ?? '')),
            'total_personas' => (int)($lider['Total_Personas'] ?? 0),
            'equipo_directo' => (int)($lider['Equipo_Directo'] ?? 0),
            'red_total' => (int)($lider['Red_Total'] ?? 0),
            'cupos_disponibles' => (int)($lider['Cupos_Disponibles'] ?? 0),
            'cupo_lleno' => !empty($lider['Cupo_Lleno']),
            'ultimo_reporte_celula' => (string)($lider['Ultimo_Reporte_Celula'] ?? ''),
            'es_mujer' => $esGeneroMujer($lider['Genero'] ?? ''),
        ];
    }

    private function compararNodosEquipo12(array $a, array $b) {
        return strcasecmp((string)($a['nombre'] ?? ''), (string)($b['nombre'] ?? ''));
    }

    private function compararEquipos12(array $a, array $b) {
        return $this->compararNodosEquipo12($a['lider'] ?? [], $b['lider'] ?? []);
    }

    private function construirNombreUsuarioActual() {
        $nombreSesion = trim((string)($_SESSION['usuario_nombre'] ?? ''));
        if ($nombreSesion !== '') {
            return $nombreSesion;
        }

        $idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
        if ($idUsuario > 0) {
            $persona = $this->personaModel->getById($idUsuario);
            if (!empty($persona)) {
                $nombre = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));
                if ($nombre !== '') {
                    return $nombre;
                }
            }
        }

        return 'Lider principal';
    }

    /**
     * @param array<int, int> $idsPersona
     * @return array<int, array<string, mixed>> mapa Id_Persona => fila
     */
    private function obtenerPersonasPorIds(array $idsPersona): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idsPersona), static function($id) {
            return $id > 0;
        })));

        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->personaModel->query(
            "SELECT Id_Persona, Nombre, Apellido, Id_Rol, Genero, Email, Telefono
             FROM persona
             WHERE Id_Persona IN ({$placeholders})",
            $ids
        );

        $mapa = [];
        foreach ((array)$rows as $row) {
            $idPersona = (int)($row['Id_Persona'] ?? 0);
            if ($idPersona > 0) {
                $mapa[$idPersona] = $row;
            }
        }

        return $mapa;
    }

    /**
     * @return array<int, array<int, array<string, mixed>|null>> 12 casillas indexadas 0-11
     */
    private function construirEquipoDirectoPorLider(array $liderazgoRed, array $discipulos): array {
        $porLider = [];

        $agregar = static function (array $fila) use (&$porLider): void {
            $idLider = (int)($fila['Id_Lider'] ?? 0);
            $idPersona = (int)($fila['Id_Persona'] ?? 0);
            if ($idLider <= 0 || $idPersona <= 0) {
                return;
            }

            $nombre = trim((string)($fila['Nombre'] ?? '') . ' ' . (string)($fila['Apellido'] ?? ''));
            if (!isset($porLider[$idLider])) {
                $porLider[$idLider] = [];
            }

            $porLider[$idLider][] = [
                'id_persona' => $idPersona,
                'nombre' => $nombre !== '' ? $nombre : ('Persona ' . $idPersona),
                'documento' => trim((string)($fila['Numero_Documento'] ?? '')),
                'telefono' => trim((string)($fila['Telefono'] ?? '')),
                'email' => trim((string)($fila['Email'] ?? '')),
                'nombre_rol' => trim((string)($fila['Nombre_Rol'] ?? '')),
                'numero_cupo' => (int)($fila['Numero_Cupo'] ?? 0),
            ];
        };

        foreach ($liderazgoRed as $fila) {
            $agregar($fila);
        }
        foreach ($discipulos as $fila) {
            $agregar($fila);
        }

        $this->aplicarNumeroCupoDesdeDb($porLider);

        foreach ($porLider as $idLider => $miembros) {
            $porLider[$idLider] = $this->ordenarMiembrosEnDoceCasillas($miembros);
        }

        return $porLider;
    }

    /**
     * En cobertura pastoral global (sin ministerio) solo se permiten cupos bajo pastor/pastora principal.
     */
    private function validarAsignacionCupoCoberturaPastoral(int $idMinisterio): ?string {
        if ($idMinisterio > 0) {
            return null;
        }

        $modoCupo = strtolower(trim((string)($_POST['modo_cupo'] ?? 'pastoral')));
        if ($modoCupo === 'pastoral') {
            return null;
        }

        return 'En cobertura pastoral general solo puede gestionar los 12 cupos bajo pastor/pastora principal. '
            . 'Para líderes de 144 o de célula, entre al ministerio correspondiente.';
    }

    /**
     * Incorpora al mapa de equipo directo las personas con cupo numerado en BD
     * (p. ej. discípulos promovidos a líder de 144 que ya no aparecen en otras listas).
     *
     * @param array<int, array<int, array<string, mixed>|null>> $porLider
     * @param array<int, int> $idsLideres
     * @return array<int, array<int, array<string, mixed>|null>>
     */
    private function enriquecerEquipoDirectoDesdeDb(array $porLider, array $idsLideres): array {
        $idsLideres = array_values(array_unique(array_filter(array_map('intval', $idsLideres), static function ($id) {
            return $id > 0;
        })));

        if (empty($idsLideres) || !$this->personaModel->tieneColumna('Numero_Cupo')) {
            return $porLider;
        }

        $placeholders = implode(',', array_fill(0, count($idsLideres), '?'));
        $rows = $this->personaModel->query(
            "SELECT p.Id_Persona, p.Numero_Documento, p.Nombre, p.Apellido, p.Email, p.Telefono,
                    p.Id_Lider, p.Numero_Cupo, COALESCE(r.Nombre_Rol, '') AS Nombre_Rol
             FROM persona p
             LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
             WHERE p.Id_Lider IN ({$placeholders})
               AND p.Numero_Cupo BETWEEN 1 AND 12
               AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)",
            $idsLideres
        );

        foreach ((array)$rows as $row) {
            $idLider = (int)($row['Id_Lider'] ?? 0);
            $idPersona = (int)($row['Id_Persona'] ?? 0);
            $numeroCupo = (int)($row['Numero_Cupo'] ?? 0);
            if ($idLider <= 0 || $idPersona <= 0 || $numeroCupo < 1 || $numeroCupo > 12) {
                continue;
            }

            $miembros = [];
            $slotsActuales = is_array($porLider[$idLider] ?? null) ? $porLider[$idLider] : [];
            foreach ($slotsActuales as $slot) {
                if (is_array($slot) && !empty($slot['id_persona'])) {
                    $miembros[] = $slot;
                }
            }

            foreach ($miembros as $existente) {
                if ((int)($existente['id_persona'] ?? 0) === $idPersona) {
                    continue 2;
                }
            }

            $nombre = trim((string)($row['Nombre'] ?? '') . ' ' . (string)($row['Apellido'] ?? ''));
            $miembros[] = [
                'id_persona' => $idPersona,
                'nombre' => $nombre !== '' ? $nombre : ('Persona ' . $idPersona),
                'documento' => trim((string)($row['Numero_Documento'] ?? '')),
                'telefono' => trim((string)($row['Telefono'] ?? '')),
                'email' => trim((string)($row['Email'] ?? '')),
                'nombre_rol' => trim((string)($row['Nombre_Rol'] ?? '')),
                'numero_cupo' => $numeroCupo,
            ];

            $porLider[$idLider] = $this->ordenarMiembrosEnDoceCasillas($miembros);
        }

        return $porLider;
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $porLider
     */
    private function aplicarNumeroCupoDesdeDb(array &$porLider): void {
        if (!$this->personaModel->tieneColumna('Numero_Cupo')) {
            return;
        }

        $ids = [];
        foreach ($porLider as $miembros) {
            foreach ($miembros as $miembro) {
                $id = (int)($miembro['id_persona'] ?? 0);
                if ($id > 0) {
                    $ids[$id] = true;
                }
            }
        }

        if (empty($ids)) {
            return;
        }

        $idList = array_keys($ids);
        $placeholders = implode(',', array_fill(0, count($idList), '?'));
        $rows = $this->personaModel->query(
            "SELECT Id_Persona, Numero_Cupo FROM persona WHERE Id_Persona IN ({$placeholders})",
            $idList
        );

        $mapa = [];
        foreach ($rows as $row) {
            $mapa[(int)($row['Id_Persona'] ?? 0)] = (int)($row['Numero_Cupo'] ?? 0);
        }

        foreach ($porLider as $idLider => &$miembros) {
            foreach ($miembros as &$miembro) {
                $id = (int)($miembro['id_persona'] ?? 0);
                if ($id > 0 && isset($mapa[$id]) && (int)($miembro['numero_cupo'] ?? 0) <= 0) {
                    $miembro['numero_cupo'] = $mapa[$id];
                }
            }
            unset($miembro);
        }
        unset($miembros);
    }

    /**
     * @param array<int, array<string, mixed>> $miembros
     * @return array<int, array<string, mixed>|null>
     */
    private function ordenarMiembrosEnDoceCasillas(array $miembros): array {
        $slots = array_fill(0, 12, null);
        $sinCupo = [];

        foreach ($miembros as $miembro) {
            $n = (int)($miembro['numero_cupo'] ?? 0);
            if ($n >= 1 && $n <= 12 && $slots[$n - 1] === null) {
                $miembro['slot_numero'] = $n;
                $slots[$n - 1] = $miembro;
            } else {
                $sinCupo[] = $miembro;
            }
        }

        usort($sinCupo, static function ($a, $b) {
            return strcasecmp((string)($a['nombre'] ?? ''), (string)($b['nombre'] ?? ''));
        });

        foreach ($sinCupo as $miembro) {
            for ($i = 0; $i < 12; $i++) {
                if ($slots[$i] === null) {
                    $miembro['slot_numero'] = $i + 1;
                    $slots[$i] = $miembro;
                    break;
                }
            }
        }

        return $slots;
    }

    /**
     * @param array<string, mixed>|null $persona
     * @return array{email: string, telefono: string}
     */
    private function extraerContactoPersona(?array $persona): array {
        if (!is_array($persona)) {
            return ['email' => '', 'telefono' => ''];
        }

        return [
            'email' => trim((string)($persona['Email'] ?? '')),
            'telefono' => trim((string)($persona['Telefono'] ?? '')),
        ];
    }

    private function construirEncabezadoEquipoPrincipal(array $lideres, $idMinisterioFiltro, $nombreMinisterioFiltro) {
        $nombrePastor = $this->construirNombreUsuarioActual();
        $email = trim((string)($_SESSION['usuario_email'] ?? ''));
        $telefono = trim((string)($_SESSION['usuario_telefono'] ?? ''));

        $idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
        if ($idUsuario > 0) {
            $persona = $this->personaModel->getById($idUsuario);
            if (!empty($persona)) {
                $nombrePersona = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));
                if ($nombrePersona !== '') {
                    $nombrePastor = $nombrePersona;
                }

                if ($email === '') {
                    $email = trim((string)($persona['Email'] ?? ''));
                }

                if ($telefono === '') {
                    $telefono = trim((string)($persona['Telefono'] ?? ''));
                }
            }
        }

        $equipoPrincipal = 0;
        $superiores = [];
        foreach ($lideres as $lider) {
            if ((int)($lider['Es_Lider_12'] ?? 0) !== 1) {
                continue;
            }

            $equipoPrincipal++;
            $nombreSuperior = trim((string)($lider['Nombre_Lider'] ?? ''));
            if ($nombreSuperior !== '' && stripos($nombreSuperior, 'sin lider') === false) {
                $superiores[$nombreSuperior] = (int)($superiores[$nombreSuperior] ?? 0) + 1;
            }
        }

        if (!empty($superiores)) {
            arsort($superiores);
            $principal = (string)array_key_first($superiores);
            if ($principal !== '') {
                $nombrePastor = $principal;
            }
        }

        $ministerioTitulo = trim((string)$nombreMinisterioFiltro);
        if ($ministerioTitulo === '') {
            $ministerioTitulo = 'Todos los ministerios';
        }

        return [
            'nombre' => $nombrePastor,
            'email' => $email,
            'telefono' => $telefono,
            'sede' => 'Madrid',
            'id_usuario' => $idUsuario,
            'equipo_principal' => $equipoPrincipal,
            'ministerio_titulo' => $ministerioTitulo,
            'ministerio_cantidad' => count($lideres),
            'id_ministerio' => (int)$idMinisterioFiltro,
        ];
    }

    private function anexarResumenRedLideres(array $lideres, $idMinisterioFiltro = 0) {
        $idsLideres = array_values(array_filter(array_map(static function($lider) {
            return (int)($lider['Id_Persona'] ?? 0);
        }, $lideres), static function($id) {
            return $id > 0;
        }));

        if (empty($idsLideres)) {
            return $lideres;
        }

        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        // Optimización de carga: construir el mapa de red una sola vez para todos los líderes.
        // Antes se ejecutaba getResumenRedLideresWithRole() por cada líder (N+1).
        $resumenMap = $this->personaModel->getResumenRedLideresWithRole(
            $idsLideres,
            $filtroPersonas,
            (int)$idMinisterioFiltro,
            12
        );
        $equipoPrincipalPorCupo = $this->personaModel->contarEquipoPrincipalPorCupoBatch($idsLideres);

        foreach ($lideres as &$lider) {
            $idLider = (int)($lider['Id_Persona'] ?? 0);
            $jerarquiaLider = $this->personaModel->getJerarquiaByRol((int)($lider['Id_Rol'] ?? 0));
            $limiteEquipo = $jerarquiaLider === 'lider_celula' ? 0 : 12;

            $resumenBase = $resumenMap[$idLider] ?? [
                'equipo_directo' => 0,
                'red_total' => 0,
            ];

            $redTotal = (int)($resumenBase['red_total'] ?? 0);
            if ($limiteEquipo > 0) {
                $equipoDirecto = (int)($equipoPrincipalPorCupo[$idLider] ?? 0);
            } else {
                $equipoDirecto = (int)($resumenBase['equipo_directo'] ?? 0);
            }

            if ($limiteEquipo > 0) {
                $cuposDisponibles = max(0, $limiteEquipo - $equipoDirecto);
                $cupoLleno = $equipoDirecto >= $limiteEquipo;
            } else {
                // Sin límite para esta jerarquía.
                $cuposDisponibles = 9999;
                $cupoLleno = false;
                $limiteEquipo = 0;
            }

            $lider['Equipo_Directo'] = $equipoDirecto;
            $lider['Red_Total'] = $redTotal;
            $lider['Cupos_Disponibles'] = $cuposDisponibles;
            $lider['Cupo_Lleno'] = $cupoLleno;
        }
        unset($lider);

        return $lideres;
    }

    private function usuarioPuedeEditarMinisterio($idMinisterio) {
        $idMinisterio = (int)$idMinisterio;
        if ($idMinisterio <= 0) {
            return false;
        }

        if (AuthController::esAdministrador()) {
            return true;
        }

        if (!AuthController::puede('ministerios:editar')) {
            return false;
        }

        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $ministeriosVisibles = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);
        $idsPermitidos = array_map(static function($row) {
            return (int)($row['Id_Ministerio'] ?? 0);
        }, $ministeriosVisibles);

        if (!in_array($idMinisterio, $idsPermitidos, true)) {
            return false;
        }

        // No admin: solo su propio ministerio.
        $idMinisterioUsuario = (int)(DataIsolation::getUsuarioMinisterioId() ?? 0);
        return $idMinisterioUsuario > 0 && $idMinisterioUsuario === $idMinisterio;
    }

    private function calcularMetasAutomaticasPorAnio($metaAnual, $anioMeta) {
        $metaAnual = max(0, (int)$metaAnual);
        $anioMeta = (int)$anioMeta;
        if ($anioMeta < 2000 || $anioMeta > 2100) {
            $anioMeta = (int)date('Y');
        }

        $inicio = new DateTime($anioMeta . '-01-01');
        $fin = new DateTime($anioMeta . '-12-31');
        $dias = (int)$inicio->diff($fin)->days + 1;
        $semanas = (int)ceil($dias / 7);

        if ($metaAnual <= 0) {
            return [
                'meta_anual' => 0,
                'meta_mensual' => 0,
                'meta_semanal' => 0,
                'anio_meta' => $anioMeta,
                'meta_ganados_s1' => 0,
                'meta_ganados_s2' => 0,
            ];
        }

        $metaMensual = (int)round($metaAnual / 12);
        $metaSemanal = (int)ceil($metaAnual / max(1, $semanas));

        // Distribución anual en semestres usando días reales del año.
        [$metaS1, $metaS2] = Ministerio::distribuirMetaAnualEnSemestres($metaAnual, $anioMeta);

        return [
            'meta_anual' => $metaAnual,
            'meta_mensual' => $metaMensual,
            'meta_semanal' => $metaSemanal,
            'anio_meta' => $anioMeta,
            'meta_ganados_s1' => $metaS1,
            'meta_ganados_s2' => $metaS2,
        ];
    }

    private function construirMetasPayloadDesdePost(array $post) {
        $fechaMeta = trim((string)($post['meta_anio_fecha'] ?? ''));
        $anioMeta = (int)($post['anio_meta'] ?? 0);
        if ($fechaMeta !== '' && preg_match('/^(\d{4})-\d{2}-\d{2}$/', $fechaMeta, $mFechaMeta) === 1) {
            $anioMeta = (int)$mFechaMeta[1];
        }

        $metaAuto = $this->calcularMetasAutomaticasPorAnio((int)($post['meta_anual'] ?? 0), $anioMeta);

        return [
            'meta_anual' => $metaAuto['meta_anual'],
            'meta_mensual' => $metaAuto['meta_mensual'],
            'meta_semanal' => $metaAuto['meta_semanal'],
            'anio_meta' => $metaAuto['anio_meta'],
            'meta_ganados_s1' => $metaAuto['meta_ganados_s1'],
            'meta_ganados_s2' => $metaAuto['meta_ganados_s2'],
            'meta_uv_s1' => (int)($post['meta_uv_s1'] ?? 0),
            'meta_uv_s2' => (int)($post['meta_uv_s2'] ?? 0),
            'meta_encuentro_s1' => (int)($post['meta_encuentro_s1'] ?? 0),
            'meta_encuentro_s2' => (int)($post['meta_encuentro_s2'] ?? 0),
            'meta_n1_s1' => (int)($post['meta_n1_s1'] ?? 0),
            'meta_n1_s2' => (int)($post['meta_n1_s2'] ?? 0),
            'meta_n2_s1' => (int)($post['meta_n2_s1'] ?? 0),
            'meta_n2_s2' => (int)($post['meta_n2_s2'] ?? 0),
            'meta_n3_s1' => (int)($post['meta_n3_s1'] ?? 0),
            'meta_n3_s2' => (int)($post['meta_n3_s2'] ?? 0),
        ];
    }

    private function redirigirEditarMinisterio($idMinisterio, $returnUrl, array $params = []) {
        $query = array_merge(['id' => (int)$idMinisterio], $params);
        if (!empty($returnUrl)) {
            $query['return_url'] = (string)$returnUrl;
        }

        header('Location: ' . public_app_url('discipular/ministerios/editar', $query) . '#metas');
        exit;
    }

    public function guardarMetas() {
        if (!AuthController::puede('ministerios:editar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('discipular/ministerios');
            return;
        }

        $returnUrl = $_POST['return_url'] ?? null;
        $id = (int)($_POST['id_ministerio'] ?? 0);
        if ($id <= 0) {
            $this->redirect('discipular/ministerios');
            return;
        }

        if (!$this->usuarioPuedeEditarMinisterio($id)) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $payloadMetas = $this->construirMetasPayloadDesdePost($_POST);
        $ok = $this->ministerioModel->setMetasDetalle($id, $payloadMetas);
        if (!$ok) {
            $this->redirigirEditarMinisterio($id, $returnUrl, [
                'meta_error' => 1,
                'meta_error_msg' => 'No se pudieron guardar las metas. Verifica permisos de base de datos.',
            ]);
            return;
        }

        $this->redirigirEditarMinisterio($id, $returnUrl, ['meta_guardada' => 1]);
    }

    public function actualizarMeta() {
        $esAdmin = AuthController::esAdministrador();
        $puedeEditar = AuthController::puede('ministerios:editar');
        if (!$esAdmin && !$puedeEditar) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('discipular/ministerios');
            return;
        }

        $idMinisterio = (int)($_POST['id_ministerio'] ?? 0);
        $metaGanados = max(0, (int)($_POST['meta_ganados'] ?? 0));

        if ($idMinisterio <= 0) {
            $this->redirect('discipular/ministerios');
            return;
        }

        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $ministeriosVisibles = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);
        $idsPermitidos = array_map(static function($row) {
            return (int)($row['Id_Ministerio'] ?? 0);
        }, $ministeriosVisibles);

        if (!in_array($idMinisterio, $idsPermitidos, true)) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        // No administradores: solo pueden configurar la meta de su propio ministerio.
        $idMinisterioUsuario = (int)(DataIsolation::getUsuarioMinisterioId() ?? 0);
        if (!$esAdmin && ($idMinisterioUsuario <= 0 || $idMinisterio !== $idMinisterioUsuario)) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $metaAuto = $this->calcularMetasAutomaticasPorAnio($metaGanados, (int)date('Y'));
        $this->ministerioModel->setMetasDetalle($idMinisterio, [
            'meta_anual' => $metaAuto['meta_anual'],
            'meta_mensual' => $metaAuto['meta_mensual'],
            'meta_semanal' => $metaAuto['meta_semanal'],
            'anio_meta' => $metaAuto['anio_meta'],
            'meta_ganados_s1' => $metaAuto['meta_ganados_s1'],
            'meta_ganados_s2' => $metaAuto['meta_ganados_s2'],
        ]);

        $this->redirect('discipular/ministerios', ['meta_guardada' => 1]);
    }

    public function actualizarLideresPrincipales() {
        $esAdmin = AuthController::esAdministrador();
        $puedeEditar = AuthController::puede('ministerios:editar');
        if (!$esAdmin && !$puedeEditar) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('discipular/ministerios');
            return;
        }

        $idMinisterio = (int)($_POST['id_ministerio'] ?? 0);
        $idLider1 = (int)($_POST['id_lider_principal_1'] ?? 0);
        $idLider2 = (int)($_POST['id_lider_principal_2'] ?? 0);
        $returnUrl = $_POST['return_url'] ?? null;
        $idRolLider12 = (int)$this->personaModel->resolverIdRolLider12();

        if ($idRolLider12 <= 0) {
            $this->redirigirConRetornoConParametros($returnUrl, 'discipular/ministerios', [
                'lp_error' => 1,
                'lp_msg' => 'No existe un rol de Lider de 12 configurado en la tabla rol.'
            ]);
            return;
        }

        if ($idMinisterio > 0) {
            $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
            $ministeriosVisibles = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);
            $idsPermitidos = array_map(static function($row) {
                return (int)($row['Id_Ministerio'] ?? 0);
            }, $ministeriosVisibles);

            if (!in_array($idMinisterio, $idsPermitidos, true)) {
                header('Location: ' . public_app_url('auth/acceso-denegado'));
                exit;
            }
        }

        if ($idLider1 > 0 && $idLider2 > 0 && $idLider1 === $idLider2) {
            $this->redirigirConRetornoConParametros($returnUrl, 'discipular/ministerios', [
                'lp_error' => 1,
                'lp_msg' => 'No puedes repetir el mismo lider en ambos cupos.'
            ]);
            return;
        }

        foreach ([$idLider1, $idLider2] as $idLider) {
            if ($idLider <= 0) {
                continue;
            }

            $persona = $this->personaModel->getById($idLider);
            if (empty($persona)) {
                $this->redirigirConRetornoConParametros($returnUrl, 'discipular/ministerios', [
                    'lp_error' => 1,
                    'lp_msg' => 'Uno de los lideres seleccionados no existe.'
                ]);
                return;
            }

            if ($idMinisterio > 0 && (int)($persona['Id_Ministerio'] ?? 0) !== $idMinisterio) {
                $this->redirigirConRetornoConParametros($returnUrl, 'discipular/ministerios', [
                    'lp_error' => 1,
                    'lp_msg' => 'Los lideres deben pertenecer al mismo ministerio.'
                ]);
                return;
            }

            $jerarquia = $this->personaModel->getJerarquiaByRol((int)($persona['Id_Rol'] ?? 0));
            if (!in_array($jerarquia, ['pastor', 'lider_12', 'lider_144', 'lider_celula'], true)) {
                $this->redirigirConRetornoConParametros($returnUrl, 'discipular/ministerios', [
                    'lp_error' => 1,
                    'lp_msg' => 'Solo puedes elegir personas con rol de liderazgo.'
                ]);
                return;
            }
        }

        $ok = $this->ministerioModel->setLideresPrincipales($idMinisterio, $idLider1, $idLider2);
        if (!$ok) {
            $this->redirigirConRetornoConParametros($returnUrl, 'discipular/ministerios', [
                'lp_error' => 1,
                'lp_msg' => 'No se pudieron guardar los lideres principales.'
            ]);
            return;
        }

        foreach ([$idLider1, $idLider2] as $idLider) {
            if ($idLider <= 0) {
                continue;
            }

            $persona = $this->personaModel->getById($idLider);
            if (empty($persona)) {
                continue;
            }

            $updateData = [];

            // En cobertura pastoral general (id 0) no se debe escribir Id_Ministerio=0
            // porque viola la FK persona.Id_Ministerio -> ministerio.Id_Ministerio.
            if ($idMinisterio > 0) {
                $updateData['Id_Ministerio'] = $idMinisterio;
            }

            $jerarquiaActual = $this->personaModel->getJerarquiaByRol((int)($persona['Id_Rol'] ?? 0));
            if ($jerarquiaActual !== 'pastor') {
                $updateData['Id_Rol'] = $idRolLider12;
            }

            if (!empty($updateData)) {
                $this->personaModel->update($idLider, $updateData);
            }
            if (isset($updateData['Id_Rol'])) {
                $this->personaModel->ajustarEscaleraPorRol($idLider, (int)$updateData['Id_Rol']);
            }
        }

        $this->redirigirConRetornoConParametros($returnUrl, 'discipular/ministerios', [
            'lp_ok' => 1,
            'lp_msg' => 'Lideres principales guardados correctamente.'
        ]);
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::puede('ministerios:crear')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $returnUrl = $_POST['return_url'] ?? ($_GET['return_url'] ?? null);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idLiderPrincipal1 = (int)($_POST['id_lider_principal_1'] ?? 0);
            $idLiderPrincipal2 = (int)($_POST['id_lider_principal_2'] ?? 0);
            $fechaMeta = trim((string)($_POST['meta_anio_fecha'] ?? ''));
            $anioMeta = (int)($_POST['anio_meta'] ?? 0);
            if ($fechaMeta !== '' && preg_match('/^(\d{4})-\d{2}-\d{2}$/', $fechaMeta, $mFechaMeta) === 1) {
                $anioMeta = (int)$mFechaMeta[1];
            }

            $metaAuto = $this->calcularMetasAutomaticasPorAnio((int)($_POST['meta_anual'] ?? 0), $anioMeta);

            $data = [
                'Nombre_Ministerio' => $_POST['nombre_ministerio'],
                'Descripcion' => $_POST['descripcion']
            ];
            
            $idMinisterioNuevo = (int)$this->ministerioModel->create($data);
            if ($idMinisterioNuevo > 0) {
                $resultadoLideres = $this->guardarLideresPrincipalesDesdeFormulario($idMinisterioNuevo, $idLiderPrincipal1, $idLiderPrincipal2);
                if (!$resultadoLideres['ok']) {
                    $queryEditar = [
                        'id' => $idMinisterioNuevo,
                        'lp_error' => 1,
                        'lp_msg' => (string)$resultadoLideres['message'],
                    ];
                    if (!empty($returnUrl)) {
                        $queryEditar['return_url'] = (string)$returnUrl;
                    }
                    $this->redirect('discipular/ministerios/editar', $queryEditar);
                    return;
                }
            }

            $this->redirigirConRetorno($returnUrl, 'discipular/ministerios');
        } else {
            $this->view('discipular/ministerios/formulario', [
                'return_url' => $this->normalizarUrlRetorno($returnUrl),
                'candidatos_lideres_principales' => $this->obtenerCandidatosLideresPrincipales(),
                'id_lider_principal_1' => 0,
                'id_lider_principal_2' => 0,
                'lp_error' => ($_GET['lp_error'] ?? '') === '1',
                'lp_msg' => (string)($_GET['lp_msg'] ?? ''),
            ]);
        }
    }

    public function exportarExcel() {
        if (!AuthController::puede('ministerios:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $ministerios = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);

        $rows = [];
        foreach ($ministerios as $ministerio) {
            $rows[] = [
                (string)($ministerio['Nombre_Ministerio'] ?? ''),
                (string)($ministerio['Descripcion'] ?? ''),
                (string)($ministerio['Total_Miembros'] ?? 0)
            ];
        }

        $this->exportCsv(
            'ministerios_' . date('Ymd_His'),
            ['Ministerio', 'Descripcion', 'Total Miembros'],
            $rows
        );
    }

    public function editar() {
        // Verificar permiso de editar
        if (!AuthController::puede('ministerios:editar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $returnUrl = $_POST['return_url'] ?? ($_GET['return_url'] ?? null);
        $id = (int)($_POST['id_ministerio'] ?? ($_GET['id'] ?? 0));
        
        if ($id <= 0) {
            $this->redirect('discipular/ministerios');
        }

        if (!$this->usuarioPuedeEditarMinisterio($id)) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idLiderPrincipal1 = (int)($_POST['id_lider_principal_1'] ?? 0);
            $idLiderPrincipal2 = (int)($_POST['id_lider_principal_2'] ?? 0);

            $data = [
                'Nombre_Ministerio' => $_POST['nombre_ministerio'],
                'Descripcion' => $_POST['descripcion']
            ];
            
            $this->ministerioModel->update($id, $data);

            $resultadoLideres = $this->guardarLideresPrincipalesDesdeFormulario((int)$id, $idLiderPrincipal1, $idLiderPrincipal2);
            if (!$resultadoLideres['ok']) {
                $this->redirigirEditarMinisterio($id, $returnUrl, [
                    'lp_error' => 1,
                    'lp_msg' => (string)$resultadoLideres['message'],
                ]);
                return;
            }

            $this->redirigirEditarMinisterio($id, $returnUrl, ['datos_guardados' => 1]);
        } else {
            $lideresGuardados = $this->ministerioModel->getLideresPrincipalesByMinisterioIds([(int)$id]);
            $lideresMinisterio = $lideresGuardados[(int)$id] ?? [
                'id_lider_principal_1' => 0,
                'id_lider_principal_2' => 0,
            ];

            $data = [
                'ministerio' => $this->ministerioModel->getById($id),
                'metas' => $this->ministerioModel->getMetaDetalleByMinisterioId($id),
                'return_url' => $this->normalizarUrlRetorno($returnUrl),
                'candidatos_lideres_principales' => $this->obtenerCandidatosLideresPrincipales(),
                'id_lider_principal_1' => (int)($lideresMinisterio['id_lider_principal_1'] ?? 0),
                'id_lider_principal_2' => (int)($lideresMinisterio['id_lider_principal_2'] ?? 0),
                'lp_error' => ($_GET['lp_error'] ?? '') === '1',
                'lp_msg' => (string)($_GET['lp_msg'] ?? ''),
                'meta_guardada' => ($_GET['meta_guardada'] ?? '') === '1',
                'meta_error' => ($_GET['meta_error'] ?? '') === '1',
                'meta_error_msg' => (string)($_GET['meta_error_msg'] ?? ''),
                'datos_guardados' => ($_GET['datos_guardados'] ?? '') === '1',
            ];
            $this->view('discipular/ministerios/formulario', $data);
        }
    }

    public function eliminar() {
        // Verificar permiso de eliminar
        if (!AuthController::puede('ministerios:eliminar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $returnUrl = $_GET['return_url'] ?? null;
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->ministerioModel->delete($id);
        }

        $this->redirigirConRetorno($returnUrl, 'discipular/ministerios');
    }
}

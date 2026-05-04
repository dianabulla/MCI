<?php
/**
 * Controlador Ministerio
 */

require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Celula.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/DataIsolation.php';

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
        if (!AuthController::tienePermiso('ministerios', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
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

        $this->view('ministerios/lista', [
            'ministerios' => $ministerios,
            'sections' => $sections,
            'fecha_referencia' => $fechaReferencia,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'meta_guardada' => ($_GET['meta_guardada'] ?? '') === '1',
            'return_url' => $returnUrl
        ]);
    }

    public function lideres() {
        $this->redirect('ministerios/equipo-principal');
    }

    public function equipo12() {
        $this->equipoPrincipal();
    }

    public function equipoPrincipal() {
        if (!AuthController::tienePermiso('ministerios', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
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

        $esGeneroMujer = static function ($genero) {
            $g = strtolower(trim((string)$genero));
            return (strpos($g, 'mujer') !== false) || (strpos($g, 'femen') !== false);
        };

        $redEquipo12 = $this->construirRedEquipo12($lideres, $esGeneroMujer);

        $this->view('ministerios/lideres', [
            'equipos_12_hombres' => $redEquipo12['equipos_12_hombres'],
            'equipos_12_mujeres' => $redEquipo12['equipos_12_mujeres'],
            'resumen_equipo_12' => $redEquipo12['resumen'],
            'id_ministerio_filtro' => $idMinisterioFiltro,
            'nombre_ministerio_filtro' => $nombreMinisterioFiltro,
        ]);
    }

    public function lideresCelula() {
        if (!AuthController::tienePermiso('ministerios', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
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

        $this->view('ministerios/lideres_celula', [
            'lideres_celula_hombres' => $lideresCelulaHombres,
            'lideres_celula_mujeres' => $lideresCelulaMujeres,
            'id_ministerio_filtro' => $idMinisterioFiltro,
            'nombre_ministerio_filtro' => $nombreMinisterioFiltro,
        ]);
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

    private function usuarioPuedeEditarMinisterio($idMinisterio) {
        $idMinisterio = (int)$idMinisterio;
        if ($idMinisterio <= 0) {
            return false;
        }

        if (AuthController::esAdministrador()) {
            return true;
        }

        if (!AuthController::tienePermiso('ministerios', 'editar')) {
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
        $diasS1 = (int)(new DateTime($anioMeta . '-01-01'))->diff(new DateTime($anioMeta . '-06-30'))->days + 1;
        $diasS2 = max(1, $dias - $diasS1);
        $metaS1 = (int)round($metaAnual * ($diasS1 / $dias));
        $metaS2 = max(0, $metaAnual - $metaS1);

        return [
            'meta_anual' => $metaAnual,
            'meta_mensual' => $metaMensual,
            'meta_semanal' => $metaSemanal,
            'anio_meta' => $anioMeta,
            'meta_ganados_s1' => $metaS1,
            'meta_ganados_s2' => $metaS2,
        ];
    }

    public function actualizarMeta() {
        $esAdmin = AuthController::esAdministrador();
        $puedeEditar = AuthController::tienePermiso('ministerios', 'editar');
        if (!$esAdmin && !$puedeEditar) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('ministerios');
            return;
        }

        $idMinisterio = (int)($_POST['id_ministerio'] ?? 0);
        $metaGanados = max(0, (int)($_POST['meta_ganados'] ?? 0));

        if ($idMinisterio <= 0) {
            $this->redirect('ministerios');
            return;
        }

        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $ministeriosVisibles = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);
        $idsPermitidos = array_map(static function($row) {
            return (int)($row['Id_Ministerio'] ?? 0);
        }, $ministeriosVisibles);

        if (!in_array($idMinisterio, $idsPermitidos, true)) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        // No administradores: solo pueden configurar la meta de su propio ministerio.
        $idMinisterioUsuario = (int)(DataIsolation::getUsuarioMinisterioId() ?? 0);
        if (!$esAdmin && ($idMinisterioUsuario <= 0 || $idMinisterio !== $idMinisterioUsuario)) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $this->ministerioModel->setMetaGanados($idMinisterio, $metaGanados);
        $this->redirect('ministerios&meta_guardada=1');
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('ministerios', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $returnUrl = $_POST['return_url'] ?? ($_GET['return_url'] ?? null);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            
            $this->ministerioModel->create($data);
            $this->redirigirConRetorno($returnUrl, 'ministerios');
        } else {
            $this->view('ministerios/formulario', [
                'return_url' => $this->normalizarUrlRetorno($returnUrl)
            ]);
        }
    }

    public function exportarExcel() {
        if (!AuthController::tienePermiso('ministerios', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
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
        if (!AuthController::tienePermiso('ministerios', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $returnUrl = $_POST['return_url'] ?? ($_GET['return_url'] ?? null);
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('ministerios');
        }

        if (!$this->usuarioPuedeEditarMinisterio($id)) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            
            $this->ministerioModel->update($id, $data);
            $this->ministerioModel->setMetasDetalle($id, [
                'meta_anual' => $metaAuto['meta_anual'],
                'meta_mensual' => $metaAuto['meta_mensual'],
                'meta_semanal' => $metaAuto['meta_semanal'],
                'anio_meta' => $metaAuto['anio_meta'],
                'meta_ganados_s1' => $metaAuto['meta_ganados_s1'],
                'meta_ganados_s2' => $metaAuto['meta_ganados_s2'],
                'meta_uv_s1' => $_POST['meta_uv_s1'] ?? 0,
                'meta_uv_s2' => $_POST['meta_uv_s2'] ?? 0,
                'meta_encuentro_s1' => $_POST['meta_encuentro_s1'] ?? 0,
                'meta_encuentro_s2' => $_POST['meta_encuentro_s2'] ?? 0,
                'meta_n1_s1' => $_POST['meta_n1_s1'] ?? 0,
                'meta_n1_s2' => $_POST['meta_n1_s2'] ?? 0,
                'meta_n2_s1' => $_POST['meta_n2_s1'] ?? 0,
                'meta_n2_s2' => $_POST['meta_n2_s2'] ?? 0,
                'meta_n3_s1' => $_POST['meta_n3_s1'] ?? 0,
                'meta_n3_s2' => $_POST['meta_n3_s2'] ?? 0
            ]);
            $this->redirigirConRetorno($returnUrl, 'ministerios');
        } else {
            $data = [
                'ministerio' => $this->ministerioModel->getById($id),
                'metas' => $this->ministerioModel->getMetaDetalleByMinisterioId($id),
                'return_url' => $this->normalizarUrlRetorno($returnUrl)
            ];
            $this->view('ministerios/formulario', $data);
        }
    }

    public function eliminar() {
        // Verificar permiso de eliminar
        if (!AuthController::tienePermiso('ministerios', 'eliminar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $returnUrl = $_GET['return_url'] ?? null;
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->ministerioModel->delete($id);
        }

        $this->redirigirConRetorno($returnUrl, 'ministerios');
    }
}

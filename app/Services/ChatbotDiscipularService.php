<?php

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Helpers/DataIsolation.php';
require_once APP . '/Controllers/AuthController.php';

/**
 * Consultas de red pastoral y metas para el chatbot.
 */
class ChatbotDiscipularService {
    private Persona $personaModel;
    private Ministerio $ministerioModel;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->ministerioModel = new Ministerio();
    }

    /**
     * @param array{red?: string} $params
     * @return array{ok: bool, reply: string, cards: array<int, array<string, mixed>>, links: array<int, array<string, string>>}
     */
    public function reporteEquipoRed(array $params): array {
        $red = $this->normalizarRed((string)($params['red'] ?? ''));
        if ($red === '') {
            return [
                'ok' => false,
                'reply' => 'Indica la red: «red hombres» o «red mujeres».',
                'cards' => [],
                'links' => [],
            ];
        }

        $cfg = $this->ministerioModel->getLideresPrincipalesByMinisterioIds([0]);
        $pastorHombres = (int)($cfg[0]['id_lider_principal_1'] ?? 0);
        $pastorMujeres = (int)($cfg[0]['id_lider_principal_2'] ?? 0);
        $idPastor = $red === 'mujeres' ? $pastorMujeres : $pastorHombres;
        $etiquetaRed = $red === 'mujeres' ? 'Red Mujeres' : 'Red Hombres';

        if ($idPastor <= 0) {
            return [
                'ok' => true,
                'reply' => 'No hay pastor/a principal configurado para «' . $etiquetaRed . '» en la cobertura pastoral.',
                'cards' => [],
                'links' => [
                    ['label' => 'Ir a Discipular', 'url' => public_app_url('discipular/ministerios/equipo-principal')],
                ],
            ];
        }

        $filtro = DataIsolation::generarFiltroPersonas();
        $lideres = $this->personaModel->getLideres12BajoPastorWithRole($idPastor, $filtro, $red);

        if (empty($lideres)) {
            return [
                'ok' => true,
                'reply' => 'No encontré líderes de 12 bajo «' . $etiquetaRed . '» en tu ámbito de acceso.',
                'cards' => [],
                'links' => [
                    ['label' => 'Ver equipo principal', 'url' => public_app_url('discipular/ministerios/equipo-principal')],
                ],
            ];
        }

        $ids = array_values(array_map(static function ($l) {
            return (int)($l['Id_Persona'] ?? 0);
        }, $lideres));
        $cuposPorLider = $this->personaModel->contarEquipoPrincipalPorCupoBatch($ids);

        $completos = [];
        $incompletos = [];
        $cards = [];

        foreach ($lideres as $lider) {
            $id = (int)($lider['Id_Persona'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $ocupados = (int)($cuposPorLider[$id] ?? 0);
            $nombre = trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? ''));
            $ministerio = trim((string)($lider['Nombre_Ministerio'] ?? ''));
            $cupoPastor = (int)($lider['Numero_Cupo'] ?? 0);
            $etiquetaCupo = $cupoPastor > 0 ? ('Cupo pastor ' . $cupoPastor) : '';

            $fila = [
                'nombre' => $nombre,
                'ocupados' => $ocupados,
                'ministerio' => $ministerio,
                'cupo_pastor' => $cupoPastor,
            ];

            if ($ocupados >= 12) {
                $completos[] = $fila;
            } else {
                $incompletos[] = $fila;
            }

            $cards[] = [
                'type' => 'stat',
                'title' => $nombre !== '' ? $nombre : ('Líder #' . $id),
                'subtitle' => $ocupados . '/12',
                'meta' => array_values(array_filter([
                    $ocupados >= 12 ? 'Equipo completo' : 'Faltan ' . (12 - $ocupados),
                    $ministerio !== '' ? $ministerio : '',
                    $etiquetaCupo,
                ])),
                'url' => public_app_url('discipular/ministerios/equipo-principal', ['buscar' => $nombre]),
            ];
        }

        usort($cards, static function ($a, $b) {
            return (int)filter_var($b['subtitle'] ?? '0', FILTER_SANITIZE_NUMBER_INT)
                <=> (int)filter_var($a['subtitle'] ?? '0', FILTER_SANITIZE_NUMBER_INT);
        });

        $total = count($lideres);
        $reply = $etiquetaRed . ': ' . count($completos) . ' de ' . $total . ' líderes de 12 con equipo principal completo (12/12).';
        if (count($incompletos) > 0) {
            $reply .= ' Incompletos: ' . count($incompletos) . '.';
        }

        return [
            'ok' => true,
            'reply' => $reply,
            'cards' => array_slice($cards, 0, 12),
            'links' => [
                ['label' => 'Ver Discipular · Equipo principal', 'url' => public_app_url('discipular/ministerios/equipo-principal')],
            ],
        ];
    }

    /**
     * @param array{periodo_meta?: string, red?: string, solo_bajo?: bool} $params
     * @return array{ok: bool, reply: string, cards: array<int, array<string, mixed>>, links: array<int, array<string, string>>}
     */
    public function reporteMetasBajoRendimiento(array $params): array {
        $periodoMeta = $this->normalizarPeriodoMeta((string)($params['periodo_meta'] ?? 'mes'));
        $red = $this->normalizarRed((string)($params['red'] ?? ''));
        $soloBajo = !isset($params['solo_bajo']) || (bool)$params['solo_bajo'];

        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $ministerios = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);

        $ministerioIds = [];
        $nombresMinisterio = [];
        foreach ((array)$ministerios as $ministerio) {
            $id = (int)($ministerio['Id_Ministerio'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $ministerioIds[] = $id;
            $nombresMinisterio[$id] = trim((string)($ministerio['Nombre_Ministerio'] ?? 'Ministerio'));
        }

        if (empty($ministerioIds)) {
            return [
                'ok' => true,
                'reply' => 'No hay ministerios visibles para evaluar metas.',
                'cards' => [],
                'links' => [],
            ];
        }

        if ($red !== '') {
            $ministerioIds = $this->filtrarMinisterioIdsPorRed($ministerioIds, $red);
            if (empty($ministerioIds)) {
                $etiquetaRed = $red === 'mujeres' ? 'Red Mujeres' : 'Red Hombres';
                return [
                    'ok' => true,
                    'reply' => 'No encontré ministerios de «' . $etiquetaRed . '» con metas en tu ámbito.',
                    'cards' => [],
                    'links' => [
                        ['label' => 'Ver ministerios', 'url' => public_app_url('discipular/ministerios')],
                    ],
                ];
            }
        }

        $metasDetalle = $this->ministerioModel->getMetasDetalleByMinisterioIds($ministerioIds);
        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        $personas = $this->personaModel->getAllWithRole($filtroPersonas, false, 'Activo');
        $fechaRef = date('Y-m-d');

        $avanceMetas = $this->calcularAvanceMetas($ministerioIds, $personas, $metasDetalle, $fechaRef, $periodoMeta);

        $cards = [];
        $criticos = 0;
        $enRiesgo = 0;
        $vaBien = 0;

        foreach ($ministerioIds as $idMinisterio) {
            $bloque = $avanceMetas[$idMinisterio] ?? null;
            if ($bloque === null) {
                continue;
            }

            $meta = (int)($bloque['meta'] ?? 0);
            $logrado = (int)($bloque['logrado'] ?? 0);
            $porcentaje = (float)($bloque['porcentaje'] ?? 0);
            $estado = $bloque['estado'] ?? ['key' => 'gris', 'label' => 'Sin meta'];

            if ($meta <= 0) {
                continue;
            }

            $key = (string)($estado['key'] ?? '');
            if ($key === 'rojo') {
                $criticos++;
            } elseif ($key === 'amarillo') {
                $enRiesgo++;
            } elseif ($key === 'verde') {
                $vaBien++;
            }

            if ($soloBajo && $key === 'verde') {
                continue;
            }

            $nombre = $nombresMinisterio[$idMinisterio] ?? ('Ministerio #' . $idMinisterio);
            $cards[] = [
                'type' => 'stat',
                'title' => $nombre,
                'subtitle' => $logrado . '/' . $meta . ' (' . $porcentaje . '%)',
                'meta' => [(string)($estado['label'] ?? ''), 'Meta ' . $periodoMeta],
                'url' => public_app_url('discipular/ministerios', ['id_ministerio' => $idMinisterio]),
            ];
        }

        usort($cards, static function ($a, $b) {
            return (int)filter_var($a['subtitle'] ?? '0', FILTER_SANITIZE_NUMBER_INT)
                <=> (int)filter_var($b['subtitle'] ?? '0', FILTER_SANITIZE_NUMBER_INT);
        });

        $etiquetaPeriodo = $periodoMeta === 'semestre' ? 'semestre actual' : ($periodoMeta === 'semana' ? 'esta semana' : ($periodoMeta === 'anio' ? 'este año' : 'este mes'));
        $etiquetaRed = $red !== '' ? (' · ' . ($red === 'mujeres' ? 'Red Mujeres' : 'Red Hombres')) : '';

        if (empty($cards)) {
            $reply = $soloBajo
                ? ('No hay ministerios con bajo rendimiento en metas de ganados (' . $etiquetaPeriodo . $etiquetaRed . ').')
                : ('No hay ministerios con meta de ganados configurada (' . $etiquetaPeriodo . $etiquetaRed . ').');
        } else {
            $reply = 'Metas de ganados (' . $etiquetaPeriodo . $etiquetaRed . '): '
                . $criticos . ' crítico(s), ' . $enRiesgo . ' en riesgo, ' . $vaBien . ' va bien.';
            if ($soloBajo) {
                $reply = 'Bajo rendimiento en metas (' . $etiquetaPeriodo . $etiquetaRed . '): '
                    . ($criticos + $enRiesgo) . ' ministerio(s) en riesgo o crítico.';
            }
        }

        return [
            'ok' => true,
            'reply' => $reply,
            'cards' => array_slice($cards, 0, 10),
            'links' => [
                ['label' => 'Ver ministerios y metas', 'url' => public_app_url('discipular/ministerios')],
            ],
        ];
    }

    /**
     * @param array<int, int> $ministerioIds
     * @param array<int, array<string, mixed>> $personas
     * @param array<int, array<string, mixed>> $metasDetalle
     * @return array<int, array{meta: int, logrado: int, porcentaje: float, estado: array{key: string, label: string, color: string}}>
     */
    private function calcularAvanceMetas(array $ministerioIds, array $personas, array $metasDetalle, string $fechaReferencia, string $periodoMeta): array {
        $timestampRef = strtotime($fechaReferencia);
        if ($timestampRef === false) {
            $timestampRef = time();
        }

        [$semanaInicio, $semanaFin] = $this->calcularRangoSemanaDomingoADomingo(date('Y-m-d', $timestampRef));
        $mesInicio = date('Y-m-01', $timestampRef);
        $mesFin = date('Y-m-t', $timestampRef);

        $anio = (int)date('Y', $timestampRef);
        $mes = (int)date('n', $timestampRef);
        $esPrimerSemestre = $mes <= 6;
        $semestreInicio = $esPrimerSemestre ? sprintf('%04d-01-01', $anio) : sprintf('%04d-07-01', $anio);
        $semestreFin = $esPrimerSemestre ? sprintf('%04d-06-30', $anio) : sprintf('%04d-12-31', $anio);

        $anioInicio = sprintf('%04d-01-01', $anio);
        $anioFin = date('Y-m-d', $timestampRef);

        $conteo = [];
        foreach ($ministerioIds as $idMinisterioTmp) {
            $conteo[(int)$idMinisterioTmp] = 0;
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

            $enRango = false;
            if ($periodoMeta === 'semana') {
                $enRango = $fechaRegistro >= $semanaInicio && $fechaRegistro <= $semanaFin;
            } elseif ($periodoMeta === 'semestre') {
                $enRango = $fechaRegistro >= $semestreInicio && $fechaRegistro <= $semestreFin;
            } elseif ($periodoMeta === 'anio') {
                $enRango = $fechaRegistro >= $anioInicio && $fechaRegistro <= $anioFin;
            } else {
                $enRango = $fechaRegistro >= $mesInicio && $fechaRegistro <= $mesFin;
            }

            if ($enRango) {
                $conteo[$idMinisterio]++;
            }
        }

        $resultado = [];
        foreach ($ministerioIds as $idMinisterioTmp) {
            $idMinisterio = (int)$idMinisterioTmp;
            $metaRow = $metasDetalle[$idMinisterio] ?? [];
            $logrado = (int)($conteo[$idMinisterio] ?? 0);

            if ($periodoMeta === 'semestre') {
                $meta = $esPrimerSemestre
                    ? max(0, (int)($metaRow['meta_ganados_s1'] ?? 0))
                    : max(0, (int)($metaRow['meta_ganados_s2'] ?? 0));
            } elseif ($periodoMeta === 'semana') {
                $meta = max(0, (int)($metaRow['meta_semanal'] ?? 0));
            } elseif ($periodoMeta === 'anio') {
                $meta = max(0, (int)($metaRow['meta_anual'] ?? 0));
                if ($meta <= 0) {
                    $meta = max(0, (int)($metaRow['meta_ganados_s1'] ?? 0) + (int)($metaRow['meta_ganados_s2'] ?? 0));
                }
            } else {
                $meta = max(0, (int)($metaRow['meta_mensual'] ?? 0));
                if ($meta <= 0) {
                    $metaAnual = max(0, (int)($metaRow['meta_anual'] ?? 0));
                    if ($metaAnual <= 0) {
                        $metaAnual = max(0, (int)($metaRow['meta_ganados_s1'] ?? 0) + (int)($metaRow['meta_ganados_s2'] ?? 0));
                    }
                    $meta = $metaAnual > 0 ? (int)round($metaAnual / 12) : 0;
                }
            }

            $porcentaje = $meta > 0 ? min(200, round(($logrado / $meta) * 100, 1)) : 0;

            $resultado[$idMinisterio] = [
                'meta' => $meta,
                'logrado' => $logrado,
                'porcentaje' => $porcentaje,
                'estado' => $this->calcularEstadoMetaPorPorcentaje($porcentaje),
            ];
        }

        return $resultado;
    }

    /**
     * @param array<int, int> $ministerioIds
     * @return array<int, int>
     */
    private function filtrarMinisterioIdsPorRed(array $ministerioIds, string $red): array {
        $cfg = $this->ministerioModel->getLideresPrincipalesByMinisterioIds(array_merge([0], $ministerioIds));
        $pastorHombres = (int)($cfg[0]['id_lider_principal_1'] ?? 0);
        $pastorMujeres = (int)($cfg[0]['id_lider_principal_2'] ?? 0);
        $idPastor = $red === 'mujeres' ? $pastorMujeres : $pastorHombres;

        if ($idPastor <= 0) {
            return [];
        }

        $filtro = DataIsolation::generarFiltroPersonas();
        $lideresRed = $this->personaModel->getLideres12BajoPastorWithRole($idPastor, $filtro, $red);
        $ministeriosRed = [];
        foreach ($lideresRed as $lider) {
            $idMin = (int)($lider['Id_Ministerio'] ?? 0);
            if ($idMin > 0) {
                $ministeriosRed[$idMin] = true;
            }
        }

        return array_values(array_filter($ministerioIds, static function ($id) use ($ministeriosRed) {
            return isset($ministeriosRed[(int)$id]);
        }));
    }

    private function calcularRangoSemanaDomingoADomingo(string $fechaReferencia): array {
        $timestamp = strtotime($fechaReferencia);
        if ($timestamp === false) {
            $timestamp = time();
        }

        $diaSemana = (int)date('w', $timestamp);
        $inicio = strtotime('-' . $diaSemana . ' days', $timestamp);
        $fin = strtotime('+6 days', $inicio);

        return [date('Y-m-d', $inicio), date('Y-m-d', $fin)];
    }

    private function calcularEstadoMetaPorPorcentaje(float $porcentaje): array {
        if ($porcentaje >= 85) {
            return ['key' => 'verde', 'label' => 'Va bien', 'color' => '#1f9d55'];
        }
        if ($porcentaje >= 60) {
            return ['key' => 'amarillo', 'label' => 'En riesgo', 'color' => '#d9a600'];
        }
        return ['key' => 'rojo', 'label' => 'Crítico', 'color' => '#d64545'];
    }

    private function normalizarRed(string $red): string {
        $red = mb_strtolower(trim($red), 'UTF-8');
        if ($red === '' || $red === 'ambas') {
            return '';
        }
        if (preg_match('/mujer|femen|pastora/u', $red)) {
            return 'mujeres';
        }
        if (preg_match('/hombre|mascul|pastor/u', $red)) {
            return 'hombres';
        }
        return '';
    }

    private function normalizarPeriodoMeta(string $periodo): string {
        $periodo = mb_strtolower(trim($periodo), 'UTF-8');
        if (preg_match('/semestre/u', $periodo)) {
            return 'semestre';
        }
        if (preg_match('/semana/u', $periodo)) {
            return 'semana';
        }
        if (preg_match('/a[nñ]o|anual/u', $periodo)) {
            return 'anio';
        }
        return 'mes';
    }
}

<?php

require_once APP . '/Models/Persona.php';
require_once APP . '/Helpers/DataIsolation.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Services/ChatbotDiscipularService.php';

/**
 * Ejecuta consultas del chatbot reutilizando filtros de permisos (DataIsolation).
 */
class ChatbotQueryService {
    private Persona $personaModel;
    private ChatbotDiscipularService $discipularService;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->discipularService = new ChatbotDiscipularService();
    }

    /**
     * @return array{ok: bool, reply: string, cards: array<int, array<string, mixed>>, links: array<int, array<string, string>>}
     */
    public function buscarPersonas(string $termino, int $limite = 20): array {
        $termino = trim($termino);
        if ($termino === '') {
            return [
                'ok' => false,
                'reply' => 'Indica un nombre, cédula o teléfono para buscar.',
                'cards' => [],
                'links' => [],
            ];
        }

        $limite = min(40, max(1, $limite));
        $resultado = $this->personaModel->buscarPersonasTextoGlobal($termino, $limite);
        $filas = is_array($resultado['filas'] ?? null) ? $resultado['filas'] : [];
        $total = (int)($resultado['total'] ?? 0);

        if ($total <= 0 || empty($filas)) {
            return [
                'ok' => true,
                'reply' => 'No encontré personas con «' . $termino . '» en toda la base de datos.',
                'cards' => [],
                'links' => [
                    ['label' => 'Ver listado de personas', 'url' => public_app_url('personas', ['buscar' => $termino])],
                ],
            ];
        }

        $cards = [];
        foreach ($filas as $fila) {
            $id = (int)($fila['Id_Persona'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $nombre = trim((string)($fila['Nombre'] ?? '') . ' ' . (string)($fila['Apellido'] ?? ''));
            $lider = trim((string)($fila['Nombre_Lider'] ?? ''));
            $meta = array_filter([
                trim((string)($fila['Nombre_Ministerio'] ?? '')),
                trim((string)($fila['Nombre_Celula'] ?? '')),
                trim((string)($fila['Proceso'] ?? '')),
                trim((string)($fila['Nombre_Rol'] ?? '')),
            ]);
            $doc = trim((string)($fila['Numero_Documento'] ?? ''));
            if ($doc !== '') {
                $meta[] = 'CC ' . $doc;
            }
            $tel = trim((string)($fila['Telefono'] ?? ''));
            if ($tel !== '') {
                $meta[] = 'Tel ' . $tel;
            }
            $estado = trim((string)($fila['Estado_Cuenta'] ?? ''));
            if ($estado !== '' && strcasecmp($estado, 'Activo') !== 0) {
                $meta[] = $estado;
            }

            $cards[] = [
                'type' => 'persona',
                'title' => $nombre !== '' ? $nombre : ('Persona #' . $id),
                'subtitle' => $lider !== '' ? ('Líder: ' . $lider) : 'Sin líder asignado',
                'meta' => array_values($meta),
                'url' => public_app_url('personas/detalle', ['id' => $id]),
            ];
        }

        $mostradas = count($cards);
        if ($total === 1) {
            $reply = 'Encontré 1 persona en toda la base de datos:';
        } elseif ($mostradas < $total) {
            $reply = 'Encontré ' . $total . ' personas en toda la base de datos. Mostrando las ' . $mostradas . ' más relevantes:';
        } else {
            $reply = 'Encontré ' . $total . ' persona(s) en toda la base de datos:';
        }

        return [
            'ok' => true,
            'reply' => $reply,
            'cards' => $cards,
            'links' => [
                ['label' => 'Ver todas en listado', 'url' => public_app_url('personas', ['buscar' => $termino])],
            ],
        ];
    }

    /**
     * @param array{fecha_inicio?: string, fecha_fin?: string, etiqueta?: string} $params
     */
    public function reporteGanados(array $params): array {
        $fechaInicio = (string)($params['fecha_inicio'] ?? date('Y-m-01'));
        $fechaFin = (string)($params['fecha_fin'] ?? date('Y-m-d'));
        $etiqueta = (string)($params['etiqueta'] ?? 'periodo seleccionado');

        $filtro = DataIsolation::generarFiltroPersonas();
        $filas = $this->personaModel->getAlmasGanadasPorMinisterioWithRole($fechaInicio, $fechaFin, $filtro);

        $total = 0;
        $hombres = 0;
        $mujeres = 0;
        $cards = [];

        foreach ((array)$filas as $fila) {
            $n = (int)($fila['Total'] ?? 0);
            $total += $n;
            $hombres += (int)($fila['Hombres'] ?? 0);
            $mujeres += (int)($fila['Mujeres'] ?? 0);
            if ($n <= 0) {
                continue;
            }
            $cards[] = [
                'type' => 'stat',
                'title' => trim((string)($fila['Nombre_Ministerio'] ?? 'Sin ministerio')),
                'subtitle' => (string)$n . ' ganados',
                'meta' => [
                    'H: ' . (int)($fila['Hombres'] ?? 0),
                    'M: ' . (int)($fila['Mujeres'] ?? 0),
                ],
                'url' => '',
            ];
        }

        usort($cards, static function ($a, $b) {
            return (int)filter_var($b['subtitle'] ?? '0', FILTER_SANITIZE_NUMBER_INT)
                <=> (int)filter_var($a['subtitle'] ?? '0', FILTER_SANITIZE_NUMBER_INT);
        });
        $cards = array_slice($cards, 0, 6);

        $reply = 'Almas ganadas (' . $etiqueta . ', ' . $fechaInicio . ' a ' . $fechaFin . '): '
            . $total . ' en total (H: ' . $hombres . ', M: ' . $mujeres . ').';

        return [
            'ok' => true,
            'reply' => $reply,
            'cards' => $cards,
            'links' => [
                ['label' => 'Abrir reportes completos', 'url' => public_app_url('reportes')],
            ],
        ];
    }

    /**
     * Ganados en iglesia y cuántos ya están ubicados en célula.
     *
     * @param array{fecha_inicio?: string, fecha_fin?: string, etiqueta?: string, desglose_semanal?: bool} $params
     */
    public function reporteGanadosIglesiaUbicacion(array $params): array {
        $fechaInicio = (string)($params['fecha_inicio'] ?? date('Y-m-01'));
        $fechaFin = (string)($params['fecha_fin'] ?? date('Y-m-d'));
        $etiqueta = (string)($params['etiqueta'] ?? 'periodo seleccionado');
        $desgloseSemanal = !empty($params['desglose_semanal']);

        $filtro = DataIsolation::generarFiltroPersonas();
        $resumen = $this->personaModel->getResumenGanadosIglesiaUbicacionWithRole($fechaInicio, $fechaFin, $filtro);

        $gi = (int)($resumen['Ganados_Iglesia'] ?? 0);
        $uc = (int)($resumen['Ubicados_Celula'] ?? 0);
        $pend = (int)($resumen['Pendientes_Iglesia'] ?? 0);
        $gc = (int)($resumen['Ganados_Celula'] ?? 0);
        $pct = $gi > 0 ? round(($uc / $gi) * 100, 1) : 0;

        $cards = [
            [
                'type' => 'stat',
                'title' => 'Ganados en iglesia',
                'subtitle' => (string)$gi,
                'meta' => [$etiqueta],
                'url' => '',
            ],
            [
                'type' => 'stat',
                'title' => 'Ubicados en célula',
                'subtitle' => (string)$uc,
                'meta' => [$gi > 0 ? ($pct . '% de iglesia') : ''],
                'url' => '',
            ],
            [
                'type' => 'stat',
                'title' => 'Pendientes iglesia',
                'subtitle' => (string)$pend,
                'meta' => ['Sin célula aún'],
                'url' => '',
            ],
            [
                'type' => 'stat',
                'title' => 'Ganados en célula',
                'subtitle' => (string)$gc,
                'meta' => ['Origen célula'],
                'url' => '',
            ],
        ];

        $reply = 'Ganados en iglesia (' . $etiqueta . ', ' . $fechaInicio . ' a ' . $fechaFin . '): '
            . $gi . ' total. De esos, ' . $uc . ' ya ubicados en célula'
            . ($gi > 0 ? ' (' . $pct . '%)' : '')
            . '. Pendientes: ' . $pend . '. Ganados en célula (origen): ' . $gc . '.';

        if ($desgloseSemanal || $this->periodoCubreVariasSemanas($fechaInicio, $fechaFin)) {
            $semanas = $this->personaModel->getResumenGanadosIglesiaUbicacionPorSemanaWithRole($fechaInicio, $fechaFin, $filtro);
            if (count($semanas) > 1) {
                $reply .= "\n\nDesglose por semana (dom–sáb):";
                foreach (array_slice($semanas, -8) as $sem) {
                    $inicio = (string)($sem['semana_inicio'] ?? '');
                    $fin = (string)($sem['semana_fin'] ?? '');
                    $reply .= "\n• " . $inicio . ' – ' . $fin . ': '
                        . (int)($sem['ganados_iglesia'] ?? 0) . ' iglesia, '
                        . (int)($sem['ubicados_celula'] ?? 0) . ' ubicados, '
                        . (int)($sem['pendientes'] ?? 0) . ' pendientes';
                }
            }
        }

        return [
            'ok' => true,
            'reply' => $reply,
            'cards' => $cards,
            'links' => [
                ['label' => 'Ver reportes Ganar', 'url' => public_app_url('reportes')],
                ['label' => 'Ver pendientes Ganar', 'url' => public_app_url('personas/ganar')],
            ],
        ];
    }

    /**
     * @param array{red?: string} $params
     */
    public function reporteEquipoRed(array $params): array {
        return $this->discipularService->reporteEquipoRed($params);
    }

    /**
     * @param array{periodo_meta?: string, red?: string, solo_bajo?: bool} $params
     */
    public function reporteMetasBajoRendimiento(array $params): array {
        return $this->discipularService->reporteMetasBajoRendimiento($params);
    }

    private function periodoCubreVariasSemanas(string $fechaInicio, string $fechaFin): bool {
        $ts1 = strtotime($fechaInicio);
        $ts2 = strtotime($fechaFin);
        if ($ts1 === false || $ts2 === false) {
            return false;
        }
        return ($ts2 - $ts1) >= (7 * 86400);
    }

    /**
     * @param array{fecha_inicio?: string, fecha_fin?: string, etiqueta?: string} $params
     */
    public function reporteProceso(array $params): array {
        $fechaInicio = (string)($params['fecha_inicio'] ?? date('Y-m-01'));
        $fechaFin = (string)($params['fecha_fin'] ?? date('Y-m-d'));
        $etiqueta = (string)($params['etiqueta'] ?? 'periodo seleccionado');

        $filtro = DataIsolation::generarFiltroPersonas();
        $resumen = $this->personaModel->getResumenProcesoGanarWithRole($fechaInicio, $fechaFin, $filtro);

        $cards = [
            ['type' => 'stat', 'title' => 'Ganar', 'subtitle' => (string)(int)($resumen['Ganar'] ?? 0), 'meta' => [], 'url' => ''],
            ['type' => 'stat', 'title' => 'Consolidar', 'subtitle' => (string)(int)($resumen['Consolidar'] ?? 0), 'meta' => [], 'url' => ''],
            ['type' => 'stat', 'title' => 'Discipular', 'subtitle' => (string)(int)($resumen['Discipular'] ?? 0), 'meta' => [], 'url' => ''],
            ['type' => 'stat', 'title' => 'Enviar', 'subtitle' => (string)(int)($resumen['Enviar'] ?? 0), 'meta' => [], 'url' => ''],
        ];

        $reply = 'Proceso de ganar (' . $etiqueta . '): '
            . (int)($resumen['Total'] ?? 0) . ' personas registradas — '
            . 'Ganar ' . (int)($resumen['Ganar'] ?? 0) . ', '
            . 'Consolidar ' . (int)($resumen['Consolidar'] ?? 0) . ', '
            . 'Discipular ' . (int)($resumen['Discipular'] ?? 0) . ', '
            . 'Enviar ' . (int)($resumen['Enviar'] ?? 0) . '.';

        return [
            'ok' => true,
            'reply' => $reply,
            'cards' => $cards,
            'links' => [
                ['label' => 'Ver reportes', 'url' => public_app_url('reportes')],
            ],
        ];
    }

    /**
     * @return array{ok: bool, reply: string, cards: array<int, array<string, mixed>>, links: array<int, array<string, string>>}
     */
    public function navegar(string $destino): array {
        $mapa = [
            'reportes' => ['label' => 'Reportes', 'url' => public_app_url('reportes'), 'perm' => 'reportes:ver'],
            'personas' => ['label' => 'Personas', 'url' => public_app_url('personas'), 'perm' => 'personas:ver'],
            'discipular' => ['label' => 'Discipular · Equipo principal', 'url' => public_app_url('discipular/ministerios/equipo-principal'), 'perm' => 'ministerios:ver'],
        ];

        $destino = strtolower(trim($destino));
        $info = $mapa[$destino] ?? null;
        if ($info === null) {
            return [
                'ok' => false,
                'reply' => 'No reconozco ese destino.',
                'cards' => [],
                'links' => [],
            ];
        }

        if (!AuthController::puede($info['perm']) && !AuthController::esAdministrador()) {
            if ($destino === 'personas' && AuthController::puedeVerPersonasConsulta()) {
                // consulta permitida
            } else {
                return [
                    'ok' => false,
                    'reply' => 'No tienes permiso para abrir «' . $info['label'] . '».',
                    'cards' => [],
                    'links' => [],
                ];
            }
        }

        return [
            'ok' => true,
            'reply' => 'Te llevo a «' . $info['label'] . '»:',
            'cards' => [],
            'links' => [
                ['label' => $info['label'], 'url' => $info['url']],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function sugerenciasIniciales(): array {
        $sugerencias = ['Ayuda', 'Buscar persona'];
        if (AuthController::puede('reportes:ver') || AuthController::esAdministrador()) {
            $sugerencias[] = 'Ganados iglesia este mes';
            $sugerencias[] = 'Ganados este mes';
            $sugerencias[] = 'Resumen proceso ganar';
        }
        if (AuthController::puede('ministerios:ver') || AuthController::esAdministrador()) {
            $sugerencias[] = 'Red hombres equipos completos';
            $sugerencias[] = 'Bajo rendimiento metas mes';
        }
        return $sugerencias;
    }

    /**
     * @return array{ok: bool, reply: string, cards: array<int, array<string, mixed>>, links: array<int, array<string, string>>}
     */
    public function respuestaAyuda(): array {
        $lineas = [
            'Puedo ayudarte con:',
            '• Buscar personas (nombre, cédula o teléfono)',
        ];
        if (AuthController::puede('reportes:ver') || AuthController::esAdministrador()) {
            $lineas[] = '• Ganados en iglesia y ubicados en célula (semana, mes, año)';
            $lineas[] = '• Reporte de ganados (este mes, mes pasado, esta semana)';
            $lineas[] = '• Resumen del proceso Ganar / Consolidar / Discipular';
        }
        if (AuthController::puede('ministerios:ver') || AuthController::esAdministrador()) {
            $lineas[] = '• Red hombres/mujeres: equipos de 12 completos';
            $lineas[] = '• Metas con bajo rendimiento (mes o semestre)';
        }
        $lineas[] = '• Enlaces rápidos: «ir a reportes», «ir a personas», «ir a discipular»';
        $lineas[] = '';
        $lineas[] = 'Ejemplos: «buscar María López», «ganados iglesia este mes», «red mujeres equipos completos», «bajo rendimiento metas semestre»';

        return [
            'ok' => true,
            'reply' => implode("\n", $lineas),
            'cards' => [],
            'links' => [],
        ];
    }
}

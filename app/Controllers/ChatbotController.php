<?php

require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Services/ChatbotIntentService.php';
require_once APP . '/Services/ChatbotQueryService.php';

class ChatbotController extends BaseController {
    private ChatbotIntentService $intentService;
    private ChatbotQueryService $queryService;

    public function __construct() {
        $this->intentService = new ChatbotIntentService();
        $this->queryService = new ChatbotQueryService();
    }

    /**
     * POST JSON: { "message": "buscar juan" }
     */
    public function consultar() {
        if (!AuthController::puedeUsarChatbotAsistente()) {
            $this->json(['ok' => false, 'error' => 'Sin permiso para el asistente.'], 403);
        }

        $payload = $this->leerPayloadJson();
        $message = trim((string)($payload['message'] ?? $_POST['message'] ?? ''));

        if ($message === '') {
            $this->json([
                'ok' => false,
                'error' => 'Escribe un mensaje.',
                'suggestions' => $this->queryService->sugerenciasIniciales(),
            ], 422);
        }

        $parsed = $this->intentService->parse($message);
        $intent = (string)($parsed['intent'] ?? 'desconocido');
        $params = is_array($parsed['params'] ?? null) ? $parsed['params'] : [];

        $resultado = $this->ejecutarIntent($intent, $params);

        $this->json([
            'ok' => !empty($resultado['ok']),
            'intent' => $intent,
            'reply' => (string)($resultado['reply'] ?? ''),
            'cards' => is_array($resultado['cards'] ?? null) ? $resultado['cards'] : [],
            'links' => is_array($resultado['links'] ?? null) ? $resultado['links'] : [],
            'suggestions' => $this->sugerenciasParaIntent($intent),
        ]);
    }

    /**
     * GET: sugerencias iniciales (al abrir el widget).
     */
    public function sugerencias() {
        if (!AuthController::puedeUsarChatbotAsistente()) {
            $this->json(['ok' => false, 'error' => 'Sin permiso.'], 403);
        }

        $this->json([
            'ok' => true,
            'welcome' => 'Hola, soy el asistente MCI. ¿En qué te ayudo?',
            'suggestions' => $this->queryService->sugerenciasIniciales(),
        ]);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function ejecutarIntent(string $intent, array $params): array {
        switch ($intent) {
            case 'saludo':
                return [
                    'ok' => true,
                    'reply' => 'Hola. Puedo buscar personas, darte reportes rápidos o llevarte a una sección. Escribe «ayuda» para ver ejemplos.',
                    'cards' => [],
                    'links' => [],
                ];

            case 'ayuda':
                return $this->queryService->respuestaAyuda();

            case 'buscar_persona':
                if (!AuthController::puede('personas:ver') && !AuthController::puedeVerPersonasConsulta() && !AuthController::esAdministrador()) {
                    return [
                        'ok' => false,
                        'reply' => 'No tienes permiso para buscar personas.',
                        'cards' => [],
                        'links' => [],
                    ];
                }
                return $this->queryService->buscarPersonas((string)($params['termino'] ?? ''));

            case 'reporte_ganados':
                if (!AuthController::puede('reportes:ver') && !AuthController::esAdministrador()) {
                    return [
                        'ok' => false,
                        'reply' => 'No tienes permiso para ver reportes de ganados.',
                        'cards' => [],
                        'links' => [],
                    ];
                }
                return $this->queryService->reporteGanados($params);

            case 'reporte_ganados_iglesia':
                if (!AuthController::puede('reportes:ver') && !AuthController::esAdministrador()) {
                    return [
                        'ok' => false,
                        'reply' => 'No tienes permiso para ver reportes de ganados.',
                        'cards' => [],
                        'links' => [],
                    ];
                }
                return $this->queryService->reporteGanadosIglesiaUbicacion($params);

            case 'reporte_equipo_red':
                if (!AuthController::puede('ministerios:ver') && !AuthController::esAdministrador()) {
                    return [
                        'ok' => false,
                        'reply' => 'No tienes permiso para ver la red pastoral.',
                        'cards' => [],
                        'links' => [],
                    ];
                }
                return $this->queryService->reporteEquipoRed($params);

            case 'reporte_metas_bajo':
                if (!AuthController::puede('ministerios:ver') && !AuthController::esAdministrador()) {
                    return [
                        'ok' => false,
                        'reply' => 'No tienes permiso para ver metas de ministerios.',
                        'cards' => [],
                        'links' => [],
                    ];
                }
                return $this->queryService->reporteMetasBajoRendimiento($params);

            case 'reporte_proceso':
                if (!AuthController::puede('reportes:ver') && !AuthController::esAdministrador()) {
                    return [
                        'ok' => false,
                        'reply' => 'No tienes permiso para ver reportes del proceso.',
                        'cards' => [],
                        'links' => [],
                    ];
                }
                return $this->queryService->reporteProceso($params);

            case 'navegar':
                return $this->queryService->navegar((string)($params['destino'] ?? ''));

            case 'vacio':
                return [
                    'ok' => false,
                    'reply' => 'Escribe algo para que pueda ayudarte.',
                    'cards' => [],
                    'links' => [],
                ];

            default:
                return [
                    'ok' => true,
                    'reply' => 'No entendí bien. Prueba con «buscar [nombre]», «ganados iglesia este mes», «red hombres equipos completos» o «ayuda».',
                    'cards' => [],
                    'links' => [],
                ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function leerPayloadJson(): array {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<int, string>
     */
    private function sugerenciasParaIntent(string $intent): array {
        if ($intent === 'buscar_persona') {
            return ['Ganados este mes', 'Ayuda'];
        }
        if (in_array($intent, ['reporte_ganados', 'reporte_ganados_iglesia', 'reporte_proceso'], true)) {
            return ['Buscar persona', 'Ganados iglesia este mes', 'Ir a reportes'];
        }
        if (in_array($intent, ['reporte_equipo_red', 'reporte_metas_bajo'], true)) {
            return ['Red mujeres equipos completos', 'Bajo rendimiento metas mes', 'Ir a discipular'];
        }
        return $this->queryService->sugerenciasIniciales();
    }
}

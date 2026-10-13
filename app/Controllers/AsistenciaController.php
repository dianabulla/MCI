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

    private function urlDestinoSinReporteSemanalAsistencias(): string {
        if (AuthController::esAdministrador() || AuthController::puede('reportes:ver')) {
            return PUBLIC_URL . '?url=reportes&tipo=celulas';
        }

        return PUBLIC_URL . '?url=celulas';
    }

    private function redirigirFueraDeReporteSemanalAsistencias(): void {
        header('Location: ' . $this->urlDestinoSinReporteSemanalAsistencias());
        exit;
    }

    private function resolverReturnUrlAsistencias($rawReturnUrl = '') {
        $default = PUBLIC_URL . '?url=celulas';
        $url = trim((string)$rawReturnUrl);
        if ($url === '' || strpos($url, PUBLIC_URL) !== 0) {
            return $default;
        }

        if (preg_match('/[?&]url=asistencias(?:&|#|$)/', $url)) {
            return $default;
        }

        if (strpos($url, '?url=celulas') !== false
            || strpos($url, '?url=reportes') !== false
            || strpos($url, '?url=asistencias/registrar') !== false
        ) {
            return $url;
        }

        return $default;
    }

    private function esPeticionAjaxAsistencia(): bool {
        $xhr = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        if ($xhr === 'xmlhttprequest') {
            return true;
        }
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        return strpos($accept, 'application/json') !== false;
    }

    private function responderRegistroCelula(bool $esAjax, bool $ok, string $mensaje, string $returnUrl, string $fechaHora): void {
        if ($esAjax) {
            if (!$ok) {
                http_response_code(400);
            }
            $this->json([
                'ok' => $ok,
                'success' => $ok,
                'message' => $mensaje,
                'fecha_hora' => $fechaHora,
                'redirect' => $returnUrl,
            ], $ok ? 200 : 400);
        }

        if ($ok) {
            header('Location: ' . $returnUrl);
            exit;
        }

        $_SESSION['asistencia_flash_error'] = $mensaje;
        header('Location: ' . PUBLIC_URL . '?url=asistencias/registrar');
        exit;
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
        $this->redirigirFueraDeReporteSemanalAsistencias();
    }

    private function fechaPorDefectoReporteCelula(): string {
        $hoy = new DateTimeImmutable('today');
        $diaSemana = (int)$hoy->format('N');

        // Semana vencida = lunes a domingo. Si se reporta el domingo, esa fecha
        // es el cierre de esa semana y el lunes ya se ve como vencida.
        if ($diaSemana === 7) {
            return $hoy->format('Y-m-d');
        }

        return $hoy->modify('monday this week')->modify('-1 day')->format('Y-m-d');
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
        $rawSobre = (string)($_POST['entrego_sobre'] ?? '');
        if ($rawSobre !== '0' && $rawSobre !== '1') {
            $this->json(['success' => false, 'error' => 'Datos invalidos'], 422);
        }
        $entregoSobre = $rawSobre === '1' ? 1 : 0;

        $semanaInicio = $this->asistenciaModel->lunesDeSemana($semanaInicio);
        if ($idCelula <= 0 || $semanaInicio === '') {
            $this->json(['success' => false, 'error' => 'Datos invalidos'], 422);
        }

        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        if (!$this->celulaModel->existsByIdWithRole($idCelula, $filtroCelulas)) {
            $this->json(['success' => false, 'error' => 'No autorizado para esta celula'], 403);
        }

        try {
            $this->asistenciaModel->ensureEntregaSobreTableExists();
            $ok = $this->asistenciaModel->guardarEntregoSobreSemana($idCelula, $semanaInicio, $entregoSobre);
        } catch (Throwable $e) {
            error_log('Error al guardar entrego sobre: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'No se pudo guardar'], 500);
        }

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
        $this->redirigirFueraDeReporteSemanalAsistencias();
    }

    public function registrar() {
        // Verificar permiso de crear
        if (!AuthController::puede('asistencias:crear')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $returnUrl = $this->resolverReturnUrlAsistencias($_POST['return_url'] ?? '');
            $idCelula = (int)($_POST['id_celula'] ?? 0);
            $fecha = trim((string)($_POST['fecha'] ?? ''));
            $asistencias = $_POST['asistencias'] ?? [];
            $tema = $_POST['tema'] ?? null;
            $tipoCelula = $_POST['tipo_celula'] ?? null;
            $noSeRealizo = !empty($_POST['no_se_realizo']);
            $observaciones = trim((string)($_POST['observaciones'] ?? ''));
            $esAjax = $this->esPeticionAjaxAsistencia();
            $marca = date('d/m/Y h:i');
            $ampm = ((int)date('G') < 12) ? 'a.m.' : 'p.m.';
            $fechaHora = $marca . ' ' . $ampm;

            try {
                if ($idCelula <= 0) {
                    throw new RuntimeException('Selecciona una célula para reportar.');
                }
                if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                    throw new RuntimeException('Indica una fecha válida para el reporte.');
                }
                if (!is_array($asistencias) || $asistencias === []) {
                    throw new RuntimeException('No se pudo reportar la célula. No hay miembros para registrar.');
                }

                if ($noSeRealizo) {
                    foreach ($asistencias as $idPersona => $asistio) {
                        $asistencias[$idPersona] = 0;
                    }

                    $motivoNoRealizo = trim((string)($_POST['motivo_no_realizo'] ?? ''));
                    $etiquetasMotivo = [
                        'convencion' => 'Convención',
                        'actividades_iglesia' => 'Actividades de la iglesia',
                        'otro' => 'Otro',
                    ];
                    if (!isset($etiquetasMotivo[$motivoNoRealizo])) {
                        throw new RuntimeException('Indica por qué no se realizó la célula.');
                    }

                    $detalleMotivo = $etiquetasMotivo[$motivoNoRealizo];
                    if ($motivoNoRealizo === 'otro') {
                        $otroMotivo = trim((string)($_POST['motivo_no_realizo_otro'] ?? ''));
                        if ($otroMotivo === '') {
                            throw new RuntimeException('Escribe el motivo en Otro.');
                        }
                        $detalleMotivo .= ': ' . $otroMotivo;
                    }

                    $prefijoMotivo = 'No se realizó. Motivo: ' . $detalleMotivo;
                    if ($observaciones === '') {
                        $observaciones = $prefijoMotivo;
                    } elseif (stripos($observaciones, 'no se realiz') === false) {
                        $observaciones = $prefijoMotivo . '. ' . $observaciones;
                    }
                }

                $observaciones = $observaciones !== '' ? $observaciones : null;
                $guardados = 0;
                $fallos = 0;

                foreach ($asistencias as $idPersona => $asistio) {
                    $asistioValor = (int)$asistio;
                    try {
                        $okFila = $this->asistenciaModel->registrarAsistencia(
                            $idPersona,
                            $idCelula,
                            $fecha,
                            $asistioValor,
                            $tema,
                            $tipoCelula,
                            $observaciones
                        );
                        if ($okFila) {
                            $guardados++;
                        } else {
                            $fallos++;
                        }
                    } catch (Throwable $eFila) {
                        error_log('Error al registrar asistencia de célula: ' . $eFila->getMessage());
                        $fallos++;
                    }
                }

                if ($guardados <= 0) {
                    throw new RuntimeException('No se pudo reportar la célula. Inténtalo de nuevo.');
                }

                $mensajeOk = 'Célula reportada con éxito. Fecha y hora: ' . $fechaHora;
                $this->responderRegistroCelula($esAjax, true, $mensajeOk, $returnUrl, $fechaHora);
            } catch (Throwable $e) {
                error_log('Error al reportar célula: ' . $e->getMessage());
                $mensajeError = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'Ocurrió un error y no se pudo reportar la célula. Inténtalo de nuevo.';
                $this->responderRegistroCelula($esAjax, false, $mensajeError, $returnUrl, $fechaHora);
            }
            return;
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

            $fechaPreseleccionada = $this->fechaPorDefectoReporteCelula();
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

    public function quitarDeCelula() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }
        if (!$this->puedeGestionarAccionesReporteCelula()) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $idPersona = (int)($_POST['id_persona'] ?? 0);
        $idCelula = (int)($_POST['id_celula'] ?? 0);
        if ($idPersona <= 0 || $idCelula <= 0) {
            $this->json(['success' => false, 'message' => 'Datos inválidos'], 422);
        }

        $persona = $this->obtenerPersonaAccionReporte($idPersona, $idCelula);
        if ($persona === null) {
            return;
        }

        $ok = $this->personaModel->update($idPersona, ['Id_Celula' => null]);
        if (!$ok) {
            $this->json(['success' => false, 'message' => 'No se pudo quitar a la persona de la célula'], 500);
        }

        $this->json([
            'success' => true,
            'message' => 'Persona eliminada de esta célula',
            'id_persona' => $idPersona,
        ]);
    }

    public function migrarCelula() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }
        if (!$this->puedeGestionarAccionesReporteCelula()) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $idPersona = (int)($_POST['id_persona'] ?? 0);
        $idCelulaOrigen = (int)($_POST['id_celula'] ?? $_POST['id_celula_origen'] ?? 0);
        $idCelulaDestino = (int)($_POST['id_celula_destino'] ?? 0);
        if ($idPersona <= 0 || $idCelulaOrigen <= 0 || $idCelulaDestino <= 0) {
            $this->json(['success' => false, 'message' => 'Datos inválidos'], 422);
        }
        if ($idCelulaDestino === $idCelulaOrigen) {
            $this->json(['success' => false, 'message' => 'Elige una célula distinta'], 422);
        }

        $persona = $this->obtenerPersonaAccionReporte($idPersona, $idCelulaOrigen);
        if ($persona === null) {
            return;
        }

        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $celulas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);
        $origen = null;
        $destino = null;
        foreach ($celulas as $celula) {
            $id = (int)($celula['Id_Celula'] ?? 0);
            if ($id === $idCelulaOrigen) {
                $origen = $celula;
            }
            if ($id === $idCelulaDestino) {
                $destino = $celula;
            }
        }

        if ($origen === null || $destino === null) {
            $this->json(['success' => false, 'message' => 'No tienes acceso a una de las células'], 403);
        }

        $minOrigen = (int)($origen['Id_Ministerio_Lider'] ?? 0);
        $minDestino = (int)($destino['Id_Ministerio_Lider'] ?? 0);
        if ($minOrigen <= 0 || $minDestino !== $minOrigen) {
            $this->json(['success' => false, 'message' => 'Solo se puede migrar a células del mismo ministerio'], 422);
        }

        $datos = ['Id_Celula' => $idCelulaDestino];
        $idLiderDestino = (int)($destino['Id_Lider'] ?? 0);
        if ($idLiderDestino > 0) {
            $datos['Id_Lider'] = $idLiderDestino;
        }
        if ($minDestino > 0) {
            $datos['Id_Ministerio'] = $minDestino;
        }

        $ok = $this->personaModel->update($idPersona, $datos);
        if (!$ok) {
            $this->json(['success' => false, 'message' => 'No se pudo migrar a la otra célula'], 500);
        }

        $this->json([
            'success' => true,
            'message' => 'Persona migrada a ' . (string)($destino['Nombre_Celula'] ?? 'la otra célula'),
            'id_persona' => $idPersona,
            'id_celula_destino' => $idCelulaDestino,
        ]);
    }

    private function puedeGestionarAccionesReporteCelula(): bool {
        return AuthController::puede('asistencias:crear')
            || AuthController::puede('asistencias:editar');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function obtenerPersonaAccionReporte(int $idPersona, int $idCelula): ?array {
        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        if (!$this->celulaModel->existsByIdWithRole($idCelula, $filtroCelulas)) {
            $this->json(['success' => false, 'message' => 'No autorizado para esta célula'], 403);
            return null;
        }

        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        if (!$this->personaModel->puedeEditarEscaleraPorRol($idPersona, $filtroPersonas)) {
            $this->json(['success' => false, 'message' => 'No tienes acceso a esta persona'], 403);
            return null;
        }

        $persona = $this->personaModel->getById($idPersona);
        if (!$persona) {
            $this->json(['success' => false, 'message' => 'Persona no encontrada'], 404);
            return null;
        }

        if ((int)($persona['Id_Celula'] ?? 0) !== $idCelula) {
            $this->json(['success' => false, 'message' => 'La persona ya no pertenece a esta célula'], 422);
            return null;
        }

        return $persona;
    }
}

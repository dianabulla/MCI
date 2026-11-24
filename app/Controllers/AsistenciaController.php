<?php
/**
 * Controlador Asistencia
 */

require_once APP . '/Models/Asistencia.php';
require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/PersonaSalidaCelula.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/DataIsolation.php';
require_once APP . '/Helpers/Auditoria.php';

class AsistenciaController extends BaseController {
    private $asistenciaModel;
    private $celulaModel;
    private $personaModel;
    private $salidaCelulaModel;

    public function __construct() {
        $this->asistenciaModel = new Asistencia();
        $this->celulaModel = new Celula();
        $this->personaModel = new Persona();
        $this->salidaCelulaModel = new PersonaSalidaCelula();
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
     * Rango lun–dom de la semana que cuenta el reporte, p. ej. "del 20 al 26 de octubre".
     */
    private function formatearRangoSemanaReporteVisible(string $fechaYmd): string {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaYmd)) {
            return $fechaYmd !== '' ? $fechaYmd : 'sin fecha';
        }

        $ts = strtotime($fechaYmd);
        if ($ts === false) {
            return $fechaYmd;
        }

        $diaSemana = (int)date('N', $ts);
        $inicioTs = (int)strtotime('-' . ($diaSemana - 1) . ' days', $ts);
        $finTs = (int)strtotime('+6 days', $inicioTs);

        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        $diaIni = (int)date('j', $inicioTs);
        $mesIni = (int)date('n', $inicioTs);
        $anioIni = (int)date('Y', $inicioTs);
        $diaFin = (int)date('j', $finTs);
        $mesFin = (int)date('n', $finTs);
        $anioFin = (int)date('Y', $finTs);
        $mesIniNombre = $meses[$mesIni] ?? '';
        $mesFinNombre = $meses[$mesFin] ?? '';

        if ($mesIni === $mesFin && $anioIni === $anioFin) {
            return 'del ' . $diaIni . ' al ' . $diaFin . ' de ' . $mesIniNombre;
        }

        if ($anioIni === $anioFin) {
            return 'del ' . $diaIni . ' de ' . $mesIniNombre . ' al ' . $diaFin . ' de ' . $mesFinNombre;
        }

        return 'del ' . $diaIni . ' de ' . $mesIniNombre . ' de ' . $anioIni
            . ' al ' . $diaFin . ' de ' . $mesFinNombre . ' de ' . $anioFin;
    }

    /**
     * @return array{nombre: string, entidad: string}
     */
    private function resolverContextoAuditoriaCelula(int $idCelula): array {
        $nombre = '';
        if ($idCelula > 0) {
            try {
                $celula = $this->celulaModel->getById($idCelula);
                $nombre = trim((string)($celula['Nombre_Celula'] ?? ''));
            } catch (Throwable $e) {
                $nombre = '';
            }
        }

        return [
            'nombre' => $nombre !== '' ? $nombre : ('Célula #' . $idCelula),
            'entidad' => 'celula',
        ];
    }

    private function auditarReporteCelulaOk(
        int $idCelula,
        string $fechaSemana,
        int $guardados,
        int $fallos,
        bool $noSeRealizo,
        string $fechaHoraEnvio
    ): void {
        $ctx = $this->resolverContextoAuditoriaCelula($idCelula);
        $rangoSemana = $this->formatearRangoSemanaReporteVisible($fechaSemana);
        $mensaje = $ctx['nombre'] . ' · semana reportada ' . $rangoSemana;
        if ($fallos > 0) {
            $mensaje .= ' · parcial (' . $guardados . ' ok, ' . $fallos . ' fallos)';
        }

        Auditoria::ok('asistencias', 'reportar_celula', [
            'entidad' => $ctx['entidad'],
            'id_registro' => $idCelula,
            'mensaje' => $mensaje,
            'detalle' => [
                'id_celula' => $idCelula,
                'nombre_celula' => $ctx['nombre'],
                'fecha_semana' => $fechaSemana,
                'rango_semana' => $rangoSemana,
                'enviado_en' => $fechaHoraEnvio,
                'miembros_guardados' => $guardados,
                'miembros_fallidos' => $fallos,
                'no_se_realizo' => $noSeRealizo ? 1 : 0,
                'parcial' => $fallos > 0 ? 1 : 0,
            ],
        ]);
    }

    private function auditarReporteCelulaFallo(
        int $idCelula,
        string $fechaSemana,
        string $mensajeError,
        string $fechaHoraEnvio,
        int $guardados = 0,
        int $fallos = 0,
        ?string $detalleTecnico = null
    ): void {
        $ctx = $this->resolverContextoAuditoriaCelula($idCelula);
        $etiquetaCelula = $idCelula > 0
            ? $ctx['nombre']
            : 'Sin célula';
        $rangoSemana = $fechaSemana !== ''
            ? $this->formatearRangoSemanaReporteVisible($fechaSemana)
            : 'sin fecha';

        $mensajeBitacora = $etiquetaCelula . ' · semana reportada ' . $rangoSemana . ' · ' . $mensajeError;
        Auditoria::fallo('asistencias', 'reportar_celula', $mensajeBitacora, [
            'entidad' => $ctx['entidad'],
            'id_registro' => $idCelula > 0 ? $idCelula : null,
            'detalle' => [
                'id_celula' => $idCelula,
                'nombre_celula' => $ctx['nombre'],
                'fecha_semana' => $fechaSemana !== '' ? $fechaSemana : null,
                'rango_semana' => $fechaSemana !== '' ? $rangoSemana : null,
                'enviado_en' => $fechaHoraEnvio,
                'miembros_guardados' => $guardados,
                'miembros_fallidos' => $fallos,
                'error' => $mensajeError,
                'detalle_tecnico' => $detalleTecnico,
            ],
        ]);
    }

    public function index() {
        $this->redirigirFueraDeReporteSemanalAsistencias();
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
                $ultimoErrorFila = '';

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
                            $ultimoErrorFila = 'No se pudo guardar la asistencia de la persona #' . (int)$idPersona;
                        }
                    } catch (Throwable $eFila) {
                        error_log('Error al registrar asistencia de célula: ' . $eFila->getMessage());
                        $fallos++;
                        $ultimoErrorFila = $eFila->getMessage();
                    }
                }

                if ($guardados <= 0) {
                    $this->auditarReporteCelulaFallo(
                        $idCelula,
                        $fecha,
                        'No se pudo reportar la célula. Inténtalo de nuevo.',
                        $fechaHora,
                        $guardados,
                        $fallos,
                        $ultimoErrorFila !== '' ? $ultimoErrorFila : null
                    );
                    throw new RuntimeException('No se pudo reportar la célula. Inténtalo de nuevo.');
                }

                $rangoSemanaVisible = $this->formatearRangoSemanaReporteVisible($fecha);
                $mensajeOk = 'Célula reportada con éxito. Semana reportada ' . $rangoSemanaVisible
                    . '. Enviado: ' . $fechaHora;
                if ($fallos > 0) {
                    $mensajeOk .= '. Atención: ' . $fallos . ' miembro(s) no se guardaron; revisa e intenta de nuevo.';
                    $this->auditarReporteCelulaFallo(
                        $idCelula,
                        $fecha,
                        'Reporte parcial: ' . $guardados . ' guardados, ' . $fallos . ' fallidos.',
                        $fechaHora,
                        $guardados,
                        $fallos,
                        $ultimoErrorFila !== '' ? $ultimoErrorFila : null
                    );
                } else {
                    $this->auditarReporteCelulaOk(
                        $idCelula,
                        $fecha,
                        $guardados,
                        $fallos,
                        $noSeRealizo,
                        $fechaHora
                    );
                }
                $this->responderRegistroCelula($esAjax, true, $mensajeOk, $returnUrl, $fechaHora);
            } catch (Throwable $e) {
                error_log('Error al reportar célula: ' . $e->getMessage());
                $mensajeError = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'Ocurrió un error y no se pudo reportar la célula. Inténtalo de nuevo.';
                // Evitar duplicar el log si ya se registró el fallo total arriba.
                if (!($e instanceof RuntimeException && $mensajeError === 'No se pudo reportar la célula. Inténtalo de nuevo.')) {
                    $this->auditarReporteCelulaFallo(
                        $idCelula,
                        $fecha,
                        $mensajeError,
                        $fechaHora,
                        0,
                        is_array($asistencias) ? count($asistencias) : 0,
                        $e instanceof RuntimeException ? null : $e->getMessage()
                    );
                }
                $this->responderRegistroCelula($esAjax, false, $mensajeError, $returnUrl, $fechaHora);
            }
            return;
        }

        // GET obsoleto: el reporte vive en el modal del Celugrama.
        $idCelula = (int)($_GET['celula'] ?? 0);
        if ($idCelula > 0) {
            $params = ['id' => $idCelula, 'reporte' => '1'];
            $fechaSolicitada = trim((string)($_GET['fecha'] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSolicitada)) {
                $params['fecha'] = $fechaSolicitada;
            }
            header('Location: ' . public_app_url('celulas/detalle', $params));
            exit;
        }

        header('Location: ' . PUBLIC_URL . '?url=celulas');
        exit;
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

        $idCelulaAntes = (int)($persona['Id_Celula'] ?? 0);

        $procesoActual = isset($persona['Proceso']) ? (string)$persona['Proceso'] : null;
        $ok = $this->personaModel->updateEscaleraChecklistYProceso($idPersona, $checklistJson, $procesoActual);
        if (!$ok) {
            $this->json(['success' => false, 'message' => 'No se pudo guardar el estado No se dispone'], 500);
        }

        if ($idCelulaAntes > 0) {
            $this->salidaCelulaModel->registrar(
                $idPersona,
                $idCelulaAntes,
                PersonaSalidaCelula::MOTIVO_NO_SE_DISPONE,
                $observacion
            );
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

        $nombreDestino = trim((string)($destino['Nombre_Celula'] ?? 'otra celula'));
        $this->salidaCelulaModel->registrar(
            $idPersona,
            $idCelulaOrigen,
            PersonaSalidaCelula::MOTIVO_TRANSFERIDA,
            'Transferida a: ' . ($nombreDestino !== '' ? $nombreDestino : 'otra celula'),
            $idCelulaDestino
        );

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

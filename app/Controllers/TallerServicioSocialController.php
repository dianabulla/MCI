<?php
/**
 * Servicio Social — agendamiento de citas (submódulo de Talleres).
 *
 * Las personas del formulario público NO se sincronizan con la base de personas:
 * solo se guardan en talleres_servicio_social_cita.
 */

require_once APP . '/Models/TallerServicioSocial.php';
require_once APP . '/Helpers/ServicioSocialDocumentos.php';
require_once APP . '/Controllers/AuthController.php';

class TallerServicioSocialController extends BaseController {
    private TallerServicioSocial $model;

    public function __construct() {
        $this->model = new TallerServicioSocial();
    }

    private function requiereAccesoAdmin(): void {
        if (!AuthController::puedeAccederModuloTalleres()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }
    }

    private function puedeGestionar(): bool {
        return AuthController::esAdministrador()
            || AuthController::puede('talleres:editar')
            || AuthController::puede('talleres:ver_respuestas');
    }

    private function ipCliente(): string {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        return mb_substr($ip, 0, 45);
    }

    private function idUsuarioSesion(): int {
        return (int)($_SESSION['usuario_id'] ?? $_SESSION['Id_Usuario'] ?? $_SESSION['id_usuario'] ?? 0);
    }

    public function index() {
        $this->requiereAccesoAdmin();

        $filtros = [
            'estado' => trim((string)($_GET['estado'] ?? '')),
            'tipo' => trim((string)($_GET['tipo'] ?? '')),
            'remitido' => trim((string)($_GET['remitido'] ?? '')),
            'buscar' => trim((string)($_GET['buscar'] ?? '')),
            'desde' => trim((string)($_GET['desde'] ?? '')),
            'hasta' => trim((string)($_GET['hasta'] ?? '')),
        ];

        $citas = $this->model->listar($filtros);
        $conteos = $this->model->contarPorEstado();

        $flashOk = (string)($_SESSION['ss_flash_ok'] ?? '');
        $flashError = (string)($_SESSION['ss_flash_error'] ?? '');
        unset($_SESSION['ss_flash_ok'], $_SESSION['ss_flash_error']);

        $this->view('talleres/servicio_social/lista', [
            'citas' => $citas,
            'filtros' => $filtros,
            'conteos' => $conteos,
            'tipos_cita' => TallerServicioSocial::TIPOS_CITA,
            'remitido_por' => TallerServicioSocial::REMITIDO_POR,
            'estados' => TallerServicioSocial::ESTADOS,
            'puede_gestionar' => $this->puedeGestionar(),
            'url_publico' => public_app_url('talleres_publico/servicio-social'),
            'flash_ok' => $flashOk,
            'flash_error' => $flashError,
        ]);
    }

    public function ver() {
        $this->requiereAccesoAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $cita = $id > 0 ? $this->model->getById($id) : null;
        if (empty($cita)) {
            $_SESSION['ss_flash_error'] = 'No se encontró la cita.';
            $this->redirect('talleres/servicio-social');
            return;
        }

        $flashOk = (string)($_SESSION['ss_flash_ok'] ?? '');
        $flashError = (string)($_SESSION['ss_flash_error'] ?? '');
        unset($_SESSION['ss_flash_ok'], $_SESSION['ss_flash_error']);

        $tipoDoc = trim((string)($cita['Tipo_Documento'] ?? ''));
        $documento = trim((string)($cita['Documento'] ?? ''));
        $historiaClinica = ($tipoDoc !== '' && $documento !== '')
            ? $this->model->listarHistoriaPaciente($tipoDoc, $documento)
            : [];
        $citasPaciente = ($tipoDoc !== '' && $documento !== '')
            ? $this->model->listarCitasPaciente($tipoDoc, $documento, $id)
            : [];
        $documentosRemision = ServicioSocialDocumentos::obtenerParaCita($id, $cita['Documentos_Remision'] ?? null);

        $this->view('talleres/servicio_social/detalle', [
            'cita' => $cita,
            'tipos_cita' => TallerServicioSocial::TIPOS_CITA,
            'tipos_documento' => TallerServicioSocial::TIPOS_DOCUMENTO,
            'remitido_por' => TallerServicioSocial::REMITIDO_POR,
            'estados' => TallerServicioSocial::ESTADOS,
            'historia_clinica' => $historiaClinica,
            'citas_paciente' => $citasPaciente,
            'documentos_remision' => $documentosRemision,
            'horarios_sabado' => TallerServicioSocial::HORARIOS_SABADO,
            'puede_gestionar' => $this->puedeGestionar(),
            'flash_ok' => $flashOk,
            'flash_error' => $flashError,
        ]);
    }

    public function actualizar() {
        $this->requiereAccesoAdmin();

        if (!$this->puedeGestionar()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('talleres/servicio-social');
            return;
        }

        $id = (int)($_POST['id_cita'] ?? 0);
        $resultado = $this->model->actualizarGestion($id, [
            'estado' => $_POST['estado'] ?? '',
            'notas_internas' => $_POST['notas_internas'] ?? '',
            'fecha_atencion' => $_POST['fecha_atencion'] ?? '',
            'fecha_preferida' => $_POST['fecha_preferida'] ?? '',
            'hora_preferida' => $_POST['hora_preferida'] ?? '',
        ], $this->idUsuarioSesion());

        if ($resultado['ok']) {
            $_SESSION['ss_flash_ok'] = $resultado['message'];
        } else {
            $_SESSION['ss_flash_error'] = $resultado['message'];
        }

        header('Location: ' . public_app_url('talleres/servicio-social/ver', ['id' => $id]));
        exit;
    }

    public function guardarHistoriaClinica() {
        $this->requiereAccesoAdmin();

        if (!$this->puedeGestionar()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('talleres/servicio-social');
            return;
        }

        $id = (int)($_POST['id_cita'] ?? 0);
        $resultado = $this->model->crearEntradaHistoria($id, [
            'motivo_consulta' => $_POST['motivo_consulta'] ?? '',
            'diagnostico' => $_POST['diagnostico'] ?? '',
            'formula' => $_POST['formula'] ?? '',
            'recomendaciones' => $_POST['recomendaciones'] ?? '',
            'observaciones' => $_POST['observaciones_hc'] ?? '',
            'fecha_atencion' => $_POST['fecha_atencion_hc'] ?? '',
        ], $this->idUsuarioSesion());

        if ($resultado['ok']) {
            $_SESSION['ss_flash_ok'] = $resultado['message'];
        } else {
            $_SESSION['ss_flash_error'] = $resultado['message'];
        }

        header('Location: ' . public_app_url('talleres/servicio-social/ver', ['id' => $id]));
        exit;
    }

    public function exportar() {
        $this->requiereAccesoAdmin();

        $filtros = [
            'estado' => trim((string)($_GET['estado'] ?? '')),
            'tipo' => trim((string)($_GET['tipo'] ?? '')),
            'remitido' => trim((string)($_GET['remitido'] ?? '')),
            'buscar' => trim((string)($_GET['buscar'] ?? '')),
            'desde' => trim((string)($_GET['desde'] ?? '')),
            'hasta' => trim((string)($_GET['hasta'] ?? '')),
        ];

        $citas = $this->model->listar($filtros);
        $headers = [
            'ID', 'Nombre', 'Apellido', 'Tipo documento', 'Documento', 'EPS',
            'Teléfono', 'Email',
            'Fecha preferida', 'Hora', 'Tipo de cita', 'Necesidad principal',
            'Remitido por', 'Detalle remisión', 'Observaciones', 'Estado',
            'Notas internas', 'Fecha atención', 'Creado',
        ];
        $rows = [];
        foreach ($citas as $c) {
            $rows[] = [
                (string)($c['Id_Cita'] ?? ''),
                (string)($c['Nombre'] ?? ''),
                (string)($c['Apellido'] ?? ''),
                TallerServicioSocial::etiquetaTipoDocumento((string)($c['Tipo_Documento'] ?? '')),
                (string)($c['Documento'] ?? ''),
                (string)($c['Nombre_Eps'] ?? ''),
                (string)($c['Telefono'] ?? ''),
                (string)($c['Email'] ?? ''),
                (string)($c['Fecha_Preferida'] ?? ''),
                (string)($c['Hora_Preferida'] ?? ''),
                TallerServicioSocial::etiquetaTipo((string)($c['Tipo_Cita'] ?? '')),
                (string)($c['Necesidad_Principal'] ?? ''),
                TallerServicioSocial::etiquetaRemitido((string)($c['Remitido_Por'] ?? '')),
                (string)($c['Remitido_Detalle'] ?? ''),
                (string)($c['Observaciones'] ?? ''),
                TallerServicioSocial::etiquetaEstado((string)($c['Estado'] ?? '')),
                (string)($c['Notas_Internas'] ?? ''),
                (string)($c['Fecha_Atencion'] ?? ''),
                (string)($c['Fecha_Creacion'] ?? ''),
            ];
        }

        $this->exportCsv('servicio_social_citas_' . date('Ymd_His'), $headers, $rows, false);
    }

    public function formularioPublico() {
        $ok = (string)($_GET['ok'] ?? '') === '1';
        $errores = is_array($_SESSION['ss_pub_errors'] ?? null) ? $_SESSION['ss_pub_errors'] : [];
        $valores = is_array($_SESSION['ss_pub_valores'] ?? null) ? $_SESSION['ss_pub_valores'] : [];
        unset($_SESSION['ss_pub_errors'], $_SESSION['ss_pub_valores']);

        $this->view('talleres/servicio_social/formulario_publico', [
            'ok' => $ok,
            'errores' => $errores,
            'valores' => $valores,
            'tipos_cita' => TallerServicioSocial::TIPOS_CITA,
            'tipos_documento' => TallerServicioSocial::TIPOS_DOCUMENTO,
            'remitido_por' => TallerServicioSocial::REMITIDO_POR,
            'horarios_sabado' => TallerServicioSocial::HORARIOS_SABADO,
            'proximos_sabados' => TallerServicioSocial::proximosSabados(16),
            'puede_agendar_hoy' => TallerServicioSocial::puedeAgendarHoy(),
            'url_buscar_persona' => public_app_url('talleres_publico/servicio-social/buscar-persona'),
            'url_disponibilidad' => public_app_url('talleres_publico/servicio-social/disponibilidad'),
        ]);
    }

    public function horariosSabado() {
        $this->requiereAccesoAdmin();

        if (!$this->puedeGestionar()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $fecha = trim((string)($_GET['fecha'] ?? ''));
        $proximos = TallerServicioSocial::proximosSabados(16);
        if ($fecha === '' || !TallerServicioSocial::esSabado($fecha)) {
            $fecha = $proximos[0]['fecha'] ?? date('Y-m-d');
        }

        $config = $this->model->obtenerConfigHorarioSabado($fecha);
        $habilitadas = $this->model->horasHabilitadasSabado($fecha);
        if ($habilitadas === null) {
            $habilitadas = array_keys(TallerServicioSocial::HORARIOS_SABADO);
        }

        $flashOk = (string)($_SESSION['ss_flash_ok'] ?? '');
        $flashError = (string)($_SESSION['ss_flash_error'] ?? '');
        unset($_SESSION['ss_flash_ok'], $_SESSION['ss_flash_error']);

        $this->view('talleres/servicio_social/horarios', [
            'fecha' => $fecha,
            'proximos_sabados' => $proximos,
            'horarios_sabado' => TallerServicioSocial::HORARIOS_SABADO,
            'horas_habilitadas' => $habilitadas,
            'config' => $config,
            'flash_ok' => $flashOk,
            'flash_error' => $flashError,
        ]);
    }

    public function guardarHorariosSabado() {
        $this->requiereAccesoAdmin();

        if (!$this->puedeGestionar()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('talleres/servicio-social/horarios');
            return;
        }

        $fecha = trim((string)($_POST['fecha'] ?? ''));
        $notas = trim((string)($_POST['notas'] ?? ''));
        $horas = is_array($_POST['horas'] ?? null) ? $_POST['horas'] : [];
        $restaurar = !empty($_POST['restaurar_todos']);

        if ($restaurar) {
            $resultado = $this->model->eliminarHorarioSabado($fecha);
        } else {
            $resultado = $this->model->guardarHorarioSabado($fecha, $horas, $notas, $this->idUsuarioSesion());
        }

        if ($resultado['ok']) {
            $_SESSION['ss_flash_ok'] = $resultado['message'];
        } else {
            $_SESSION['ss_flash_error'] = $resultado['message'];
        }

        header('Location: ' . public_app_url('talleres/servicio-social/horarios', ['fecha' => $fecha]));
        exit;
    }

    public function buscarPersonaPublico(): void {
        header('Content-Type: application/json; charset=utf-8');

        $tipoDocumento = trim((string)($_GET['tipo_documento'] ?? $_POST['tipo_documento'] ?? ''));
        $documento = trim((string)($_GET['documento'] ?? $_POST['documento'] ?? ''));

        if ($documento === '') {
            echo json_encode(['ok' => false, 'mensaje' => 'Indica el número de documento.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (strlen(TallerServicioSocial::normalizarDocumento($documento)) < 3) {
            echo json_encode(['ok' => false, 'mensaje' => 'El documento debe tener al menos 3 caracteres.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $persona = $this->model->buscarEnPersonas($tipoDocumento, $documento);
        $citaSs = $this->model->buscarUltimaCitaPaciente($tipoDocumento, $documento);
        if ($citaSs === null) {
            $citaSs = $this->model->buscarUltimaCitaPorDocumento($documento);
        }

        $combinado = $this->model->combinarDatosPrefill($persona, $citaSs);

        echo json_encode([
            'ok' => $persona !== null || $citaSs !== null,
            'persona' => $persona,
            'servicio_social' => $citaSs ? [
                'id_cita' => (int)($citaSs['Id_Cita'] ?? 0),
                'nombre' => trim((string)($citaSs['Nombre'] ?? '')),
                'apellido' => trim((string)($citaSs['Apellido'] ?? '')),
                'tipo_documento' => trim((string)($citaSs['Tipo_Documento'] ?? '')),
                'documento' => trim((string)($citaSs['Documento'] ?? '')),
                'telefono' => trim((string)($citaSs['Telefono'] ?? '')),
                'email' => trim((string)($citaSs['Email'] ?? '')),
                'nombre_eps' => trim((string)($citaSs['Nombre_Eps'] ?? '')),
                'ultima_fecha' => (string)($citaSs['Fecha_Preferida'] ?? ''),
            ] : null,
            'prefill' => $combinado['prefill'],
            'fuentes' => $combinado['fuentes'],
            'mensaje' => $combinado['mensaje'],
            'citas_anteriores' => $combinado['citas_anteriores'],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function disponibilidadPublico(): void {
        header('Content-Type: application/json; charset=utf-8');

        $fecha = trim((string)($_GET['fecha'] ?? ''));
        if ($fecha === '' || !TallerServicioSocial::esSabado($fecha)) {
            echo json_encode(['ok' => false, 'mensaje' => 'Selecciona un sábado válido.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $disp = $this->model->disponibilidadFecha($fecha);
        $disponibles = 0;
        foreach ($disp['horas'] as $h) {
            if (!empty($h['disponible'])) {
                $disponibles++;
            }
        }

        echo json_encode([
            'ok' => true,
            'fecha' => $fecha,
            'horas' => $disp['horas'],
            'tiene_cupos' => $disponibles > 0,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function guardarPublico() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('talleres_publico/servicio-social');
            return;
        }

        if (!TallerServicioSocial::puedeAgendarHoy()) {
            $_SESSION['ss_pub_errors'] = ['Las solicitudes de cita solo se reciben de lunes a jueves.'];
            $this->redirect('talleres_publico/servicio-social');
            return;
        }

        $datos = [
            'nombre' => $_POST['nombre'] ?? '',
            'apellido' => $_POST['apellido'] ?? '',
            'tipo_documento' => $_POST['tipo_documento'] ?? '',
            'documento' => $_POST['documento'] ?? '',
            'nombre_eps' => $_POST['nombre_eps'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'email' => $_POST['email'] ?? '',
            'fecha_preferida' => $_POST['fecha_preferida'] ?? '',
            'hora_preferida' => $_POST['hora_preferida'] ?? '',
            'tipo_cita' => $_POST['tipo_cita'] ?? '',
            'necesidad_principal' => $_POST['necesidad_principal'] ?? '',
            'remitido_por' => $_POST['remitido_por'] ?? 'ninguno',
            'remitido_detalle' => $_POST['remitido_detalle'] ?? '',
            'observaciones' => $_POST['observaciones'] ?? '',
        ];

        $erroresUpload = ServicioSocialDocumentos::validarUpload($_FILES[ServicioSocialDocumentos::INPUT_NAME] ?? []);
        if ($erroresUpload !== []) {
            $_SESSION['ss_pub_errors'] = $erroresUpload;
            $_SESSION['ss_pub_valores'] = $datos;
            $this->redirect('talleres_publico/servicio-social');
            return;
        }

        $resultado = $this->model->crearCitaPublica($datos, $this->ipCliente());

        if (!$resultado['ok']) {
            $_SESSION['ss_pub_errors'] = $resultado['errors'];
            $_SESSION['ss_pub_valores'] = $datos;
            $this->redirect('talleres_publico/servicio-social');
            return;
        }

        $idCita = (int)($resultado['id'] ?? 0);
        if ($idCita > 0 && ServicioSocialDocumentos::tieneArchivosEnUpload($_FILES[ServicioSocialDocumentos::INPUT_NAME] ?? [])) {
            $upload = $this->model->guardarDocumentosRemision($idCita, $_FILES[ServicioSocialDocumentos::INPUT_NAME] ?? []);
            if (!$upload['ok']) {
                $_SESSION['ss_pub_errors'] = $upload['errors'];
                $_SESSION['ss_pub_valores'] = $datos;
                $this->redirect('talleres_publico/servicio-social');
                return;
            }
        }

        header('Location: ' . public_app_url('talleres_publico/servicio-social', ['ok' => '1']));
        exit;
    }
}

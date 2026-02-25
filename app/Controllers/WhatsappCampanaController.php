<?php
/**
 * Controlador WhatsApp Campañas
 */

require_once APP . '/Models/WhatsappCampana.php';
require_once APP . '/Models/Nehemias.php';
require_once APP . '/Helpers/DataIsolation.php';
require_once APP . '/Controllers/AuthController.php';

class WhatsappCampanaController extends BaseController {
    private $campanaModel;
    private $nehemiasModel;

    public function __construct() {
        $this->campanaModel = new WhatsappCampana();
        $this->nehemiasModel = new Nehemias();
    }

    private function tienePermiso($accion = 'ver') {
        return AuthController::esAdministrador() || AuthController::tienePermiso('nehemias', $accion);
    }

    private function esErrorEsquemaWhatsapp(Exception $e) {
        $mensaje = (string)$e->getMessage();
        $codigo = (string)$e->getCode();

        return $codigo === '42S02' || $codigo === '42S22'
            || strpos($mensaje, 'Base table or view not found') !== false
            || strpos($mensaje, 'doesn\'t exist') !== false
            || strpos($mensaje, 'Unknown column') !== false;
    }

    private function mensajeInstalacionEsquema() {
        return 'El esquema de WhatsApp no está instalado. Ejecuta el script docs/sql/whatsapp_campanas_mvp_20260224.sql en la base de datos mcimadrid.';
    }

    public function index() {
        if (!$this->tienePermiso('ver')) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        try {
            $campanas = $this->campanaModel->getAllWithStats();
        } catch (Exception $e) {
            if ($this->esErrorEsquemaWhatsapp($e)) {
                $campanas = [];
                $mensaje = $this->mensajeInstalacionEsquema();
                $tipo = 'error';
                $this->view('nehemias_whatsapp/lista', compact('campanas', 'mensaje', 'tipo'));
                return;
            }
            throw $e;
        }

        $this->view('nehemias_whatsapp/lista', [
            'campanas' => $campanas,
            'mensaje' => $_GET['mensaje'] ?? null,
            'tipo' => $_GET['tipo'] ?? null
        ]);
    }

    public function crear() {
        if (!$this->tienePermiso('crear')) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        $ministeriosNehemias = array_map(
            static fn($row) => $row['Lider'],
            $this->nehemiasModel->getMinisteriosDistinct()
        );

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $nombre = trim((string)($_POST['nombre'] ?? ''));
                $cuerpo = trim((string)($_POST['cuerpo'] ?? ''));
                $fechaProgramada = trim((string)($_POST['fecha_programada'] ?? ''));

                if ($nombre === '' || $cuerpo === '' || $fechaProgramada === '') {
                    throw new Exception('Nombre, mensaje y fecha programada son obligatorios');
                }

                $usuarioId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;

                $tipoMensaje = $_POST['tipo_mensaje'] ?? 'texto';
                if (!in_array($tipoMensaje, ['texto', 'imagen', 'video', 'documento'], true)) {
                    $tipoMensaje = 'texto';
                }

                $plantilla = [
                    'nombre' => 'Plantilla ' . $nombre,
                    'cuerpo' => $cuerpo,
                    'tipo' => $tipoMensaje,
                    'media_url' => trim((string)($_POST['media_url'] ?? '')) ?: null,
                    'creado_por' => $usuarioId
                ];

                $campana = [
                    'nombre' => $nombre,
                    'objetivo' => trim((string)($_POST['objetivo'] ?? '')) ?: null,
                    'fecha_programada' => $fechaProgramada,
                    'limite_lote' => max(10, (int)($_POST['limite_lote'] ?? 100)),
                    'pausa_segundos' => max(1, (int)($_POST['pausa_segundos'] ?? 5)),
                    'creado_por' => $usuarioId
                ];

                $filtro = [
                    'lider' => trim((string)($_POST['lider'] ?? '')),
                    'lider_nehemias' => trim((string)($_POST['lider_nehemias'] ?? '')),
                    'consentimiento_whatsapp' => isset($_POST['consentimiento_whatsapp']) ? 1 : 0
                ];

                $this->campanaModel->crearCampanaConPlantilla($campana, $plantilla, $filtro);
                $msg = urlencode('Campaña creada correctamente');
                $this->redirect('nehemias/whatsapp-campanas&mensaje=' . $msg . '&tipo=success');
            } catch (Exception $e) {
                if ($this->esErrorEsquemaWhatsapp($e)) {
                    $mensajeError = $this->mensajeInstalacionEsquema();
                } else {
                    $mensajeError = $e->getMessage();
                }
                $this->view('nehemias_whatsapp/formulario', [
                    'ministeriosNehemias' => $ministeriosNehemias,
                    'error' => $mensajeError,
                    'post_data' => $_POST
                ]);
                return;
            }
        }

        $this->view('nehemias_whatsapp/formulario', [
            'ministeriosNehemias' => $ministeriosNehemias
        ]);
    }

    public function generarCola() {
        if (!$this->tienePermiso('editar')) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        $idCampana = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($idCampana <= 0) {
            $msg = urlencode('Campaña inválida');
            $this->redirect('nehemias/whatsapp-campanas&mensaje=' . $msg . '&tipo=error');
        }

        try {
            $insertados = $this->campanaModel->generarColaDesdeFiltro($idCampana);
            $msg = urlencode('Cola generada. Registros agregados: ' . $insertados);
            $this->redirect('nehemias/whatsapp-campanas&mensaje=' . $msg . '&tipo=success');
        } catch (Exception $e) {
            $error = $this->esErrorEsquemaWhatsapp($e)
                ? $this->mensajeInstalacionEsquema()
                : ('Error al generar cola: ' . $e->getMessage());
            $msg = urlencode($error);
            $this->redirect('nehemias/whatsapp-campanas&mensaje=' . $msg . '&tipo=error');
        }
    }

    public function procesarCola() {
        if (!$this->tienePermiso('editar')) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 50;
        $limite = max(1, min($limite, 500));
        $dryRun = isset($_GET['dry_run']) && $_GET['dry_run'] === '1';

        try {
            $resultado = $this->campanaModel->procesarLotePendiente($limite, $dryRun);
            $msg = urlencode('Proceso completado. Total: ' . $resultado['total'] . ', Enviados: ' . $resultado['enviados'] . ', Fallidos: ' . $resultado['fallidos']);
            $this->redirect('nehemias/whatsapp-campanas&mensaje=' . $msg . '&tipo=success');
        } catch (Exception $e) {
            $error = $this->esErrorEsquemaWhatsapp($e)
                ? $this->mensajeInstalacionEsquema()
                : ('Error procesando cola: ' . $e->getMessage());
            $msg = urlencode($error);
            $this->redirect('nehemias/whatsapp-campanas&mensaje=' . $msg . '&tipo=error');
        }
    }

    public function webhook() {
        $config = $this->campanaModel->obtenerConfigActiva();
        $tokenEsperado = trim((string)($config['webhook_verify_token'] ?? ''));

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
            $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';
            $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';

            if ($mode !== '' && $challenge !== '') {
                if ($tokenEsperado === '' || hash_equals($tokenEsperado, (string)$token)) {
                    header('Content-Type: text/plain');
                    echo (string)$challenge;
                    exit;
                }
                http_response_code(403);
                echo 'token invalido';
                exit;
            }

            http_response_code(200);
            echo 'ok';
            exit;
        }

        $input = file_get_contents('php://input');
        $payload = json_decode((string)$input, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        try {
            $resultado = $this->campanaModel->procesarWebhookEstado($payload);
            $this->json([
                'ok' => true,
                'resultado' => $resultado
            ]);
        } catch (Exception $e) {
            $this->json([
                'ok' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

<?php
/**
 * Premiación dentro de Talleres.
 * Solo lee células y personas para sugerir la primera categoría.
 * Lo elegido se guarda en su propia tabla.
 */

require_once APP . '/Models/TallerPremiacion.php';
require_once APP . '/Controllers/AuthController.php';

class TallerPremiacionController extends BaseController {
    private TallerPremiacion $model;

    public function __construct() {
        $this->model = new TallerPremiacion();
    }

    public function index(): void {
        $this->requiereVer();

        $anio = $this->anioPedido((int)($_GET['anio'] ?? (int)date('Y')));
        $flashOk = (string)($_SESSION['premiacion_flash_ok'] ?? '');
        $flashError = (string)($_SESSION['premiacion_flash_error'] ?? '');
        unset($_SESSION['premiacion_flash_ok'], $_SESSION['premiacion_flash_error']);

        $rankings = [
            'celulas' => [],
            'pastores' => [],
            'revelacion' => [],
        ];
        $errorDatos = '';
        try {
            $rankings = $this->model->rankings($anio);
        } catch (Throwable $e) {
            error_log('Premiación rankings: ' . $e->getMessage());
            $errorDatos = 'No se pudo leer el ranking de células. Los otros premios sí se pueden escribir.';
        }

        $this->view('talleres/premiacion/formulario', [
            'anio' => $anio,
            'anios' => $this->aniosDisponibles(),
            'catalogo' => TallerPremiacion::catalogo(),
            'rankings' => $rankings,
            'elegidos' => $this->model->elegidos($anio),
            'resultados' => $this->model->resultadosVotos($anio),
            'votantes' => $this->model->listarVotantes($anio),
            'total_votos' => $this->model->contarVotos($anio),
            'url_voto' => $this->urlVotacionPublica(),
            'puede_guardar' => $this->puedeGuardar(),
            'flash_ok' => $flashOk,
            'flash_error' => $flashError,
            'error_datos' => $errorDatos,
        ]);
    }

    public function votar(): void {
        $anio = (int)date('Y');
        $this->view('talleres_publico/premiacion_votar', [
            'anio' => $anio,
            'categorias' => TallerPremiacion::categoriasVotacion(),
            'errores' => [],
            'valores' => ['nombre' => '', 'telefono' => '', 'voto' => []],
            'ya_voto' => false,
            'gracias' => false,
        ]);
    }

    public function sugerencias(): void {
        $nombres = $this->model->sugerirNombres((string)($_GET['q'] ?? ''));
        $this->json(['ok' => true, 'nombres' => $nombres]);
    }

    public function guardarVoto(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('talleres_publico/premiacion');
        }

        $anio = (int)date('Y');
        $nombre = (string)($_POST['nombre'] ?? '');
        $telefono = (string)($_POST['telefono'] ?? '');
        $seleccion = is_array($_POST['voto'] ?? null) ? $_POST['voto'] : [];
        $ip = mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

        try {
            $this->model->registrarVoto($anio, $nombre, $telefono, $seleccion, $ip);
            $this->view('talleres_publico/premiacion_votar', [
                'anio' => $anio,
                'categorias' => [],
                'errores' => [],
                'valores' => ['nombre' => '', 'telefono' => '', 'voto' => []],
                'ya_voto' => false,
                'gracias' => true,
            ]);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'ya_voto') {
                $this->view('talleres_publico/premiacion_votar', [
                    'anio' => $anio,
                    'categorias' => [],
                    'errores' => [],
                    'valores' => ['nombre' => trim($nombre), 'telefono' => $telefono, 'voto' => $seleccion],
                    'ya_voto' => true,
                    'gracias' => false,
                ]);
                return;
            }
            $this->view('talleres_publico/premiacion_votar', [
                'anio' => $anio,
                'categorias' => TallerPremiacion::categoriasVotacion(),
                'errores' => ['No se pudo guardar el voto. Inténtalo de nuevo.'],
                'valores' => ['nombre' => trim($nombre), 'telefono' => $telefono, 'voto' => $seleccion],
                'ya_voto' => false,
                'gracias' => false,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->view('talleres_publico/premiacion_votar', [
                'anio' => $anio,
                'categorias' => TallerPremiacion::categoriasVotacion(),
                'errores' => [$e->getMessage()],
                'valores' => ['nombre' => trim($nombre), 'telefono' => $telefono, 'voto' => $seleccion],
                'ya_voto' => false,
                'gracias' => false,
            ]);
        }
    }

    public function guardar(): void {
        $this->requiereGuardar();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('talleres/premiacion');
        }

        $anio = $this->anioPedido((int)($_POST['anio'] ?? 0));
        $rankings = [
            'celulas' => [],
            'pastores' => [],
            'revelacion' => [],
        ];
        try {
            $rankings = $this->model->rankings($anio);
        } catch (Throwable $e) {
            error_log('Premiación rankings al guardar: ' . $e->getMessage());
        }

        try {
            $this->model->guardar($anio, $_POST, $this->model->elegidos($anio), $rankings, $this->idUsuario());
            $_SESSION['premiacion_flash_ok'] = 'Premiación ' . $anio . ' guardada. El resto de la plataforma no se modificó.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['premiacion_flash_error'] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Premiación guardar: ' . $e->getMessage());
            $_SESSION['premiacion_flash_error'] = 'No se pudo guardar la premiación.';
        }

        $this->redirect('talleres/premiacion&anio=' . $anio);
    }

    private function requiereVer(): void {
        if (!$this->puedeVer()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }
    }

    private function requiereGuardar(): void {
        if (!$this->puedeGuardar()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }
    }

    private function puedeVer(): bool {
        return AuthController::puede('talleres:ver')
            || AuthController::puede('talleres:ver_respuestas')
            || AuthController::puede('talleres:editar')
            || AuthController::puede('talleres:crear');
    }

    private function puedeGuardar(): bool {
        return AuthController::puede('talleres:editar')
            || AuthController::puede('talleres:crear');
    }

    private function idUsuario(): int {
        return (int)($_SESSION['usuario_id'] ?? $_SESSION['Id_Usuario'] ?? $_SESSION['id_usuario'] ?? 0);
    }

    private function urlVotacionPublica(): string {
        $base = rtrim((string)BASE_URL, '/');
        $ruta = ($base === '' ? '' : $base) . '/votar-premios';
        return absolute_public_app_url($ruta);
    }

    private function anioPedido(int $anio): int {
        $actual = (int)date('Y');
        if ($anio < $actual - 2 || $anio > $actual) {
            return $actual;
        }
        return $anio;
    }

    /**
     * @return list<int>
     */
    private function aniosDisponibles(): array {
        $actual = (int)date('Y');
        return [$actual, $actual - 1, $actual - 2];
    }
}

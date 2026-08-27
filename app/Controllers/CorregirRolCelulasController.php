<?php

require_once APP . '/Controllers/BaseController.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/CorregirRolCelulasLiderCelula.php';

class CorregirRolCelulasController extends BaseController {

    public function index() {
        $this->exigirAdmin();

        global $pdo;
        $roles = CorregirRolCelulasLiderCelula::resolverRoles($pdo);
        $plan = CorregirRolCelulasLiderCelula::armarPlan($pdo);

        $mensaje = trim((string)($_GET['mensaje'] ?? ''));
        $tipo = trim((string)($_GET['tipo'] ?? ''));
        if ($mensaje === '' && isset($_GET['aplicados'])) {
            $n = (int)$_GET['aplicados'];
            $mensaje = $n > 0
                ? ('Se corrigieron ' . $n . ' ficha(s).')
                : 'No había fichas para corregir.';
            $tipo = $n > 0 ? 'success' : 'info';
        }

        $this->view('roles/corregir_celulas', [
            'roles_ids' => $roles,
            'plan' => $plan,
            'mensaje' => $mensaje,
            'tipo' => $tipo,
        ]);
    }

    public function aplicar() {
        $this->exigirAdmin();

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('herramientas/corregir-rol-celulas');
            return;
        }

        if (empty($_POST['confirmar'])) {
            header('Location: ' . public_app_url('herramientas/corregir-rol-celulas', [
                'mensaje' => 'Marca la casilla de confirmación para aplicar el cambio.',
                'tipo' => 'error',
            ]));
            exit;
        }

        global $pdo;
        $resultado = CorregirRolCelulasLiderCelula::aplicar($pdo);

        header('Location: ' . public_app_url('herramientas/corregir-rol-celulas', [
            'mensaje' => (string)($resultado['message'] ?? ''),
            'aplicados' => (int)($resultado['personas'] ?? 0),
            'tipo' => !empty($resultado['ok']) ? 'success' : 'error',
        ]));
        exit;
    }

    private function exigirAdmin(): void {
        if (!AuthController::estaAutenticado() || !AuthController::esAdministrador()) {
            $this->redirect('auth/acceso-denegado');
        }
    }
}

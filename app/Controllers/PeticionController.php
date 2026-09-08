<?php
/**
 * Módulo Peticiones descontinuado.
 * Las rutas antiguas redirigen; la tabla peticion no se borra.
 */

require_once APP . '/Controllers/AuthController.php';

class PeticionController extends BaseController {
    public function __construct() {
        if (AuthController::estaAutenticado()) {
            $this->redirect('home');
        }
        header('Location: ' . public_app_url('auth/login'));
        exit;
    }
}

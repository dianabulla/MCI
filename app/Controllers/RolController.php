<?php
/**
 * Controlador Rol
 */

require_once APP . '/Models/Rol.php';

class RolController extends BaseController {
    private $rolModel;

    public function __construct() {
        $this->rolModel = new Rol();
    }

    public function index() {
        $roles = $this->rolModel->getAllWithPersonCount();
        $this->view('roles/lista', ['roles' => $roles]);
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('roles', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre_Rol' => $_POST['nombre_rol'],
                'Descripcion' => $_POST['descripcion']
            ];
            
            $this->rolModel->create($data);
            $this->redirect('roles');
        } else {
            $this->view('roles/formulario');
        }
    }

    public function editar() {
        // Verificar permiso de editar
        if (!AuthController::tienePermiso('roles', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('roles');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre_Rol' => $_POST['nombre_rol'],
                'Descripcion' => $_POST['descripcion']
            ];
            
            $this->rolModel->update($id, $data);
            $this->redirect('roles');
        } else {
            $data = [
                'rol' => $this->rolModel->getById($id)
            ];
            $this->view('roles/formulario', $data);
        }
    }

    public function eliminar() {
        // Verificar permiso de eliminar
        if (!AuthController::tienePermiso('roles', 'eliminar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->rolModel->delete($id);
        }
        
        $this->redirect('roles');
    }
}

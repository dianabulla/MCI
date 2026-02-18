<?php
/**
 * Controlador Ministerio
 */

require_once APP . '/Models/Ministerio.php';
require_once APP . '/Helpers/DataIsolation.php';

class MinisterioController extends BaseController {
    private $ministerioModel;

    public function __construct() {
        $this->ministerioModel = new Ministerio();
    }

    public function index() {
        // Generar filtro según el rol del usuario
        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        
        // Obtener ministerios con aislamiento de rol
        $ministerios = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);
        $this->view('ministerios/lista', ['ministerios' => $ministerios]);
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('ministerios', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre_Ministerio' => $_POST['nombre_ministerio'],
                'Descripcion' => $_POST['descripcion']
            ];
            
            $this->ministerioModel->create($data);
            $this->redirect('ministerios');
        } else {
            $this->view('ministerios/formulario');
        }
    }

    public function editar() {
        // Verificar permiso de editar
        if (!AuthController::tienePermiso('ministerios', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('ministerios');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre_Ministerio' => $_POST['nombre_ministerio'],
                'Descripcion' => $_POST['descripcion']
            ];
            
            $this->ministerioModel->update($id, $data);
            $this->redirect('ministerios');
        } else {
            $data = [
                'ministerio' => $this->ministerioModel->getById($id)
            ];
            $this->view('ministerios/formulario', $data);
        }
    }

    public function eliminar() {
        // Verificar permiso de eliminar
        if (!AuthController::tienePermiso('ministerios', 'eliminar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->ministerioModel->delete($id);
        }
        
        $this->redirect('ministerios');
    }
}

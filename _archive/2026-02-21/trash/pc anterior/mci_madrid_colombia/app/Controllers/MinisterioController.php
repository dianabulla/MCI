<?php
/**
 * Controlador Ministerio
 */

require_once APP . '/Models/Ministerio.php';

class MinisterioController extends BaseController {
    private $ministerioModel;

    public function __construct() {
        $this->ministerioModel = new Ministerio();
    }

    public function index() {
        $ministerios = $this->ministerioModel->getAllWithMemberCount();
        $this->view('ministerios/lista', ['ministerios' => $ministerios]);
    }

    public function crear() {
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
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->ministerioModel->delete($id);
        }
        
        $this->redirect('ministerios');
    }
}

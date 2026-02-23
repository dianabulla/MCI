<?php
/**
 * Controlador Peticion
 */

require_once APP . '/Models/Peticion.php';
require_once APP . '/Models/Persona.php';

class PeticionController extends BaseController {
    private $peticionModel;
    private $personaModel;

    public function __construct() {
        $this->peticionModel = new Peticion();
        $this->personaModel = new Persona();
    }

    public function index() {
        $peticiones = $this->peticionModel->getAllWithPerson();
        $this->view('peticiones/lista', ['peticiones' => $peticiones]);
    }

    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Id_Persona' => $_POST['id_persona'],
                'Descripcion_Peticion' => $_POST['descripcion_peticion'],
                'Fecha_Peticion' => date('Y-m-d'),
                'Estado_Peticion' => 'Pendiente'
            ];
            
            $this->peticionModel->create($data);
            $this->redirect('peticiones');
        } else {
            $data = [
                'personas' => $this->personaModel->getAll()
            ];
            $this->view('peticiones/formulario', $data);
        }
    }

    public function editar() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('peticiones');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Id_Persona' => $_POST['id_persona'],
                'Descripcion_Peticion' => $_POST['descripcion_peticion'],
                'Estado_Peticion' => $_POST['estado_peticion']
            ];
            
            $this->peticionModel->update($id, $data);
            $this->redirect('peticiones');
        } else {
            $data = [
                'peticion' => $this->peticionModel->getById($id),
                'personas' => $this->personaModel->getAll()
            ];
            $this->view('peticiones/formulario', $data);
        }
    }

    public function eliminar() {
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->peticionModel->delete($id);
        }
        
        $this->redirect('peticiones');
    }
}
